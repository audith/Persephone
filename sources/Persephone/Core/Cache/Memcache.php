<?php

namespace Persephone\Core\Cache;

if ( !defined( "INIT_DONE" ) )
{
	die( "Improper access! Exiting now..." );
}

/**
 * Memcache Storage
 *
 * @package      Audith CMS codename Persephone
 * @author       Shahriyar Imanov <shehi@imanov.name>
 * @version      1.0
 */
class Memcache implements Iface
{
	/**
	 * Registry reference
	 *
	 * @var \Persephone\Core\Registry
	 */
	private $Registry;

	/**
	 * Unique ID for cache-filenames
	 *
	 * @var string
	 */
	private $identifier;

	/**
	 * FLAG - Whether abstraction failed or not
	 *
	 * @var boolean
	 */
	public $crashed = false;

	/**
	 * Memcache connection link
	 *
	 * @var Memcache
	 */
	private $link;


	/**
	 * Constructor
	 *
	 * @param       \Persephone\Core\Registry    $Registry
	 * @param       string                       $identifier       Unique-ID used to hash keys
	 *
	 * @return      boolean
	 */
	public function __construct ( \Persephone\Core\Registry $Registry, $identifier = "" )
	{
		# Prelim
		$this->Registry = $Registry;

		# Cont.
		if ( !class_exists( "Memcache" ) )
		{
			$this->crashed = true;

			return false;
		}

		if ( !$identifier )
		{
			$this->identifier = $this->Registry->Input->server( 'SERVER_NAME' );
		}
		else
		{
			$this->identifier = $identifier;
		}

		unset( $identifier );

		# Local object instantiation
		$this->link = new \Memcache();

		# Connection
		return $this->_connect( $this->Registry->config[ 'performance' ][ 'cache' ][ 'memcache' ][ 'connection_pool' ] );
	}


	/**
	 * Connect to memcache server
	 *
	 * @param       array                       $server_info        Connection information
	 *
	 * @return      boolean                                         Whether connection was established successfully or not - TRUE on success, FALSE otherwise
	 * @throws      \Persephone\Exception
	 */
	private function _connect ( $server_info = array() )
	{
		try
		{
			if ( !count( $server_info ) )
			{
				throw new \Persephone\Exception( "No servers to connect!" );
			}

			foreach ( $server_info as $_server )
			{
				if ( count( $_server ) != 2 or ( !isset( $_server[ 0 ] ) or empty( $_server[ 0 ] ) ) or ( !isset( $_server[ 1 ] ) or empty( $_server[ 1 ] ) ) )
				{
					throw new \Persephone\Exception( __METHOD__ . " says: Invalid server information!" );
				}

				if ( !is_object( $this->link ) )
				{
					throw new \Persephone\Exception( __METHOD__ . " says: Link not instantiated?!" );
				}

				if ( !$this->link->addServer( $_server[ 0 ], $_server[ 1 ] ) )
				{
					throw new \Persephone\Exception( __METHOD__ . " says: Connection to " . $_server[ 0 ] . ":" . $_server[ 1 ] . " failed!" );
				}
			}
		}
		catch ( \Persephone\Exception $e )
		{
			$_log_message = __METHOD__ . " says:" . $e->getMessage();
			\Persephone\Core\Registry::logger__do_log( $_log_message, "WARNING" );
			$this->crashed = 1;

			return false;
		}

		if ( method_exists( $this->link, "setCompressThreshold" ) )
		{
			$this->link->setCompressThreshold( 51200, 0.2 );
		}

		\Persephone\Core\Registry::logger__do_log( __METHOD__ . " says: Succeeded to ADD servers to the server pool.", "INFO" );

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
	 * @param       string          $key            Cache unique key
	 * @param       string          $value          Cache value to add
	 * @param       integer         $ttl            [Optional] Time to live
	 * @param       boolean         $_no_logging    [Optional] Whether to log the PUT action or not
	 *
	 * @return      boolean                         Whether cache set was successful or not; TRUE on success, FALSE otherwise
	 */
	public function do_put ( $key, $value, $ttl = 0, $_no_logging = false )
	{
		if ( in_array( "zlib", $this->Registry->config[ 'runtime' ][ 'loaded_extensions' ] ) )
		{
			$return = $this->link->set( md5( $this->identifier . $key ), $value, MEMCACHE_COMPRESSED, intval( $ttl ) );
		}
		else
		{
			$return = $this->link->set( md5( $this->identifier . $key ), $value, 0, intval( $ttl ) );
		}

		if ( $_no_logging === false )
		{
			$_log_message = __METHOD__ . " says: " .
			                ( $return == false
				                ? "Failed"
				                : "Succeeded" ) . " to STORE (PUT) item '" . $key . "'.";
			\Persephone\Core\Registry::logger__do_log(
				$_log_message,
				$return == false
					? "WARNING"
					: "INFO"
			);
		}

		return $return;
	}


	/**
	 * Update value in remote cache store
	 *
	 * @param       string       $key      Cache unique key
	 * @param       string       $value    Cache value to set
	 * @param       integer      $ttl      [Optional] Time to live
	 *
	 * @return      boolean                Whether cache update was successful or not; TRUE on success, FALSE otherwise
	 */
	public function do_update ( $key, $value, $ttl = 0 )
	{
		$this->do_remove( $key );
		$return       = $this->do_put( $key, $value, $ttl, true );
		$_log_message = __METHOD__ . " says: " .
		                ( $return == false
			                ? "Failed"
			                : "Succeeded" ) . " to REPLACE item '" . $key . "'.";
		\Persephone\Core\Registry::logger__do_log(
			$_log_message,
			$return == false
				? "WARNING"
				: "INFO"
		);

		return $return;
	}


	/**
	 * Retrieve a value from remote cache store
	 *
	 * @param       string       $key      Cache unique key
	 *
	 * @return      mixed                  Cached value
	 */
	public function do_get ( $key )
	{
		$return       = $this->link->get( md5( $this->identifier . $key ) );
		$_log_message = __METHOD__ . " says: " .
		                ( $return == false
			                ? "Failed"
			                : "Succeeded" ) . " to GET item '" . $key . "'.";
		\Persephone\Core\Registry::logger__do_log(
			$_log_message,
			$return == false
				? "WARNING"
				: "INFO"
		);

		return $return;
	}


	/**
	 * Remove a value in the remote cache store
	 *
	 * @param       string       $key       Cache unique key
	 *
	 * @return      boolean                 Whether cache removal was successful or not; TRUE on success, FALSE otherwise
	 */
	public function do_remove ( $key )
	{
		$return       = $this->link->delete( md5( $this->identifier . $key ) );
		$_log_message = __METHOD__ . " says: " .
		                ( $return == false
			                ? "Failed"
			                : "Succeeded" ) . " to REMOVE item '" . $key . "'.";
		\Persephone\Core\Registry::logger__do_log(
			$_log_message,
			$return == false
				? "WARNING"
				: "INFO"
		);

		return $return;
	}
}
