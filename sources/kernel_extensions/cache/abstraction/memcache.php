<?php

if ( ! defined( "INIT_DONE" ) )
{
	print "Improper access! Exiting now...";
	exit();
}

/**
 * Memcache Storage
 *
 * @package  Invision Power Board
 * @author   Matthew Mecham @ IPS
 * @version  1.0
**/

/**
* Basic Usage Examples
* <code>
* $cache = new cache_lib( 'identifier' );
* Update:
* $db->do_put( 'key', 'value' [, 'ttl'] );
* Remove:
* $db->do_remove( 'key' );
* Retrieve:
* $db->do_get( 'key' );
* </code>
*/

class Cache_Lib
{
	/**
	 * API Object reference
	 *
	 * @var object
	 */
	private $API;

	/**
	 * Unique ID for cache-filenames
	 *
	 * @var string
	 */
	public $identifier;

	/**
	 * FLAG - Whether abstraction failed or not
	 *
	 * @var integer
	 */
	public $crashed = 0;

	/**
	 * Memcache connection link - TRUE on success, FALSE otherwise
	 *
	 * @var boolean
	 */
	public $link;


	public function __construct ( $identifier="" , $API )
	{
		# Prelim
		$this->API = $API;

		# Cont.
		if ( ! class_exists( "Memcache" ) )
		{
			$this->crashed = 1;
			return false;
		}

		if ( !$identifier )
		{
			$this->identifier = md5( uniqid( rand(), TRUE ) );
		}
		else
		{
			$this->identifier = $identifier;
		}

		unset( $identifier );

		# Local object instantiation
		$this->link = new Memcache();

		# Connection
		$this->_connect( $this->API->config['performance']['cache']['memcache']['connection_pool'] );
	}


	/**
	 * Connect to memcache server
	 *
	 * @param       array           Connection information
	 * @return      boolean         Whether connection was established successfully or not - TRUE on success, FALSE otherwise
	 * @throws      Exception
	 */
	private function _connect ( $server_info = array() )
	{
		try
		{
			if ( ! count( $server_info ) )
			{
				throw new Exception( "No servers to connect!" );
			}

			foreach ( $server_info as $_server )
			{
				if ( count( $_server ) != 2 or ( ! isset( $_server[0] ) or empty( $_server[0] ) ) or ( ! isset( $_server[1] ) or empty( $_server[1] ) ) )
				{
					throw new Exception( "Invalid server information!" );
				}

				if ( ! is_object( $this->link ) )
				{
					throw new Exception( "Link not instantiated?!" );
				}

				if ( ! $this->link->addServer( $_server[0], $_server[1] ) )
				{
					throw new Exception( "Connection to " . $_server[0] . ":" . $_server[1] . " failed!" );
				}
			}
		}
		catch ( Exception $e )
		{
			$_log_message = "Cache - memcache - _connect():" . $e->getMessage();
			$this->API->logger__do_log( $_log_message , "WARNING" );
			$this->crashed = 1;
			return false;
		}

		if ( method_exists( $this->link , "setCompressThreshold" ) )
		{
			$this->link->setCompressThreshold( 51200, 0.2 );
		}

		$this->API->logger__do_log( "Cache - Memcache - _connect(): Succeeded to ADD servers to the server pool." , "INFO" );

		return true;
	}


	/**
	 * Disconnect from remote cache store
	 *
	 * @return   boolean    Whether or not the disconnect attempt was successful - TRUE on success, FALSE otherwise
	 */
	public function disconnect ()
	{
		if ( is_object( $this->link ) and $this->link instanceof Memcache )
		{
			return $this->link->close();
		}
		return true;
	}


	/**
	 * Put data into remote cache store
	 *
	 * @param       string          Cache unique key
	 * @param       string          Cache value to add
	 * @param       integer         [Optional] Time to live
	 * @param       boolean         [Optional] Whether to log the PUT action or not
	 * @return      boolean         Whether cache set was successful or not; TRUE on success, FALSE otherwise
	 */
	public function do_put ( $key , $value , $ttl = 0 , $_no_logging = FALSE )
	{
		if (  in_array( "zlib", $this->API->config['runtime']['loaded_extensions'] ) )
		{
			$return = $this->link->set( md5( $this->identifier . $key ), $value, MEMCACHE_COMPRESSED, intval( $ttl ) );
		}
		else
		{
			$return = $this->link->set( md5( $this->identifier . $key ), $value, 0, intval( $ttl ) );
		}

		if ( $_no_logging === FALSE )
		{
			$_log_message = "Cache - Memcache - do_put(): " . ( $return == FALSE ? "Failed" : "Succeeded" ) . " to STORE (PUT) item '" . $key . "'.";
			$this->API->logger__do_log( $_log_message , $return == FALSE ? "WARNING" : "INFO" );
		}

		return $return;
	}


	/**
	 * Update value in remote cache store
	 *
	 * @param       string          Cache unique key
	 * @param       string          Cache value to set
	 * @param       integer         [Optional] Time to live
	 * @return      boolean         Whether cache update was successful or not; TRUE on success, FALSE otherwise
	 */
	public function do_update ( $key , $value , $ttl = 0 )
	{
		$this->do_remove( $key );
		$return = $this->do_put( $key , $value, $ttl, TRUE );
		$_log_message = "Cache - Memcache - do_update(): " . ( $return == FALSE ? "Failed" : "Succeeded" ) . " to REPLACE item '" . $key . "'.";
		$this->API->logger__do_log( $_log_message , $return == FALSE ? "WARNING" : "INFO" );
		return $return;
	}


	/**
	 * Retrieve a value from remote cache store
	 *
	 * @param       string          Cache unique key
	 * @return      mixed           Cached value
	 */
	public function do_get ( $key )
	{
		$return = $this->link->get( md5( $this->identifier . $key ) );
		$_log_message = "Cache - Memcache - do_get(): " . ( $return == FALSE ? "Failed" : "Succeeded" ) . " to GET item '" . $key . "'.";
		$this->API->logger__do_log( $_log_message , $return == FALSE ? "WARNING" : "INFO" );
		return $return;
	}


	/**
	 * Remove a value in the remote cache store
	 *
	 * @param       string          Cache unique key
	 * @return      boolean         Whether cache removal was successful or not; TRUE on success, FALSE otherwise
	 */
	public function do_remove ( $key )
	{
		$return = $this->link->delete( md5( $this->identifier . $key ) );
		$_log_message = "Cache - Memcache - do_remove(): " . ( $return == FALSE ? "Failed" : "Succeeded" ) . " to REMOVE item '" . $key . "'.";
		$this->API->logger__do_log( $_log_message , $return == FALSE ? "WARNING" : "INFO" );
		return $return;
	}
}
?>