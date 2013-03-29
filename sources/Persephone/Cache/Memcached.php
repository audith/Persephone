<?php

namespace Persephone\Cache;

if ( !defined( "INIT_DONE" ) )
{
	die( "Improper access! Exiting now..." );
}

/**
 * Cache > Drivers > php-memcached
 *
 * @package  Audith CMS codename Persephone
 * @author   Shahriyar Imanov <shehi@imanov.name>
 * @version  1.0
 * @uses     PHP PECL Memcached extension
 * @see      http://pecl.php.net/package/memcached
 **/

//require_once( dirname( __FILE__ ) . "/_interface.php" );

class Memcached implements Iface
{
	/**
	 * Registry reference
	 *
	 * @var \Persephone\Registry
	 */
	private $Registry;

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
	 * Memcached class object
	 *
	 * @var Memcached
	 */
	public $link;


	/**
	 * Constructor
	 *
	 * @param    \Persephone\Registry Registry reference
	 * @param    string               Unique-ID used to hash keys
	 * @return   boolean
	 */
	public function __construct ( \Persephone\Registry $Registry, $identifier = "" )
	{
		# Prelim
		$this->Registry = $Registry;

		# Cont.
		if ( !extension_loaded( "memcached" ) )
		{
			$this->crashed = 1;
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
		$this->link = new Memcached( $this->identifier );

		# Connection
		$this->_connect( $this->Registry->config[ 'performance' ][ 'cache' ][ 'memcache' ][ 'connection_pool' ] );

		return true;
	}


	/**
	 * Prepares detailed result/status log message
	 *
	 * @param    string   Message to be prepended to a log
	 * @param    boolean  [Optional] Whether to log the message or not; defaults to FALSE
	 * @param    string   [Optional] Log priority
	 * @return   string   Detailed log message
	 */
	private function _result_codes__do_log ( $message_to_prepend_to_log = "", $do_perform_log = false, $priority = "WARNING" )
	{
		$message_to_log = $message_to_prepend_to_log;
		$_result_code   = $this->link->getResultCode();
		switch ( $_result_code )
		{
			case Memcached::RES_SUCCESS:
			case Memcached::RES_FAILURE:
				break;
			case Memcached::RES_CONNECTION_SOCKET_CREATE_FAILURE:
				$message_to_log .= " Failed to create network socket...";
				break;
			case Memcached::RES_HOST_LOOKUP_FAILURE:
				$message_to_log .= " DNS lookup failed...";
				break;
			case Memcached::RES_NO_SERVERS:
				$message_to_log .= " Server list is empty...";
				break;
			case Memcached::RES_PARTIAL_READ:
				$message_to_log .= " Partial network data read error...";
				break;
			case Memcached::RES_CLIENT_ERROR:
				$message_to_log .= " Error on the client side...";
				break;
			case Memcached::RES_SERVER_ERROR:
				$message_to_log .= " Error on the server side...";
				break;
			case Memcached::RES_NOTFOUND:
				$message_to_log .= " Item not found...";
				break;
			case Memcached::RES_DATA_EXISTS:
				$message_to_log .= " Failed to do compare-and-swap: item you are trying to store has been modified since you last fetched it...";
				break;
			case Memcached::RES_SOME_ERRORS:
				$message_to_log .= " Some errors occurred during multi-get...";
				break;
			case Memcached::RES_UNKNOWN_READ_FAILURE:
				$message_to_log .= " Failed to read network data...";
				break;
			case Memcached::RES_PROTOCOL_ERROR:
				$message_to_log .= " Bad command in memcached protocol...";
				break;
			case Memcached::RES_WRITE_FAILURE:
				$message_to_log .= " Failed to write network data...";
				break;
			case Memcached::RES_TIMEOUT:
				$message_to_log .= " Operation timed out...";
				break;
			case Memcached::RES_BAD_KEY_PROVIDED:
				$message_to_log .= " Bad key detected...";
				break;
			case Memcached::RES_PAYLOAD_FAILURE:
				$message_to_log .= " Payload failure: could not compress/decompress or serialize/unserialize the value...";
				break;
			case Memcached::RES_NOTSTORED:
				$message_to_log .= "; but not because of an error. This normally means that either the condition for an \"add\" or a \"replace\" command wasn't met, or that the item is in a delete queue...";
				break;
		}

		if ( $do_perform_log )
		{
			$this->Registry->logger__do_log( $message_to_log, $priority );
		}

		return $message_to_log;
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
			if ( !count( $server_info ) )
			{
				throw new \Persephone\Exception( "No servers to connect!" );
			}

			if ( ( $return = $this->link->addServers( $server_info ) ) === false )
			{
				$_log_message = $this->_result_codes__do_log( "Cache - Memcached - _connect(): Failed to ADD servers to the server pool." );
				throw new \Persephone\Exception( $_log_message );
			}
		}
		catch ( Exception $e )
		{
			$this->Registry->logger__do_log( $e->getMessage(), "WARNING" );
			$this->crashed = 1;
			return false;
		}

		$this->link->setOption( Memcached::OPT_COMPRESSION, true );
		$this->link->setOption( Memcached::OPT_TCP_NODELAY, true );

		$this->_result_codes__do_log( "Cache - Memcached - _connect(): Succeeded to ADD servers to the server pool.", true, "INFO" );

		return true;
	}


	/**
	 * Disconnect from remote cache store
	 *
	 * @return   boolean    Whether or not the disconnect attempt was successful - TRUE on success, FALSE otherwise
	 */
	public function disconnect ()
	{
		if ( is_object( $this->link ) and $this->link instanceof Memcached )
		{
			unset( $this->link );
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
	public function do_put ( $key, $value, $ttl = 0, $_no_logging = false )
	{
		$return = $this->link->set( md5( $this->identifier . $key ), $value, intval( $ttl ) );
		if ( $_no_logging === false )
		{
			$_log_message = "Cache - Memcached - do_put(): " . ( $return == false ? "Failed" : "Succeeded" ) . " to STORE (PUT) item '" . $key . "'.";
			$this->_result_codes__do_log( $_log_message, true, $return == false ? "WARNING" : "INFO" );
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
	public function do_update ( $key, $value, $ttl = 0 )
	{
		$this->do_remove( $key );
		$return = $this->do_put( $key, $value, $ttl, true );
		// $return = $this->link->replace( md5( $this->identifier . $key ), $value, $ttl );
		$_log_message = "Cache - Memcached - do_update(): " . ( $return == false ? "Failed" : "Succeeded" ) . " to REPLACE item '" . $key . "'.";
		$this->_result_codes__do_log( $_log_message, true, $return == false ? "WARNING" : "INFO" );
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
		$return       = $this->link->get( md5( $this->identifier . $key ) );
		$_log_message = "Cache - Memcached - do_get(): " . ( $return == false ? "Failed" : "Succeeded" ) . " to GET item '" . $key . "'.";
		$this->_result_codes__do_log( $_log_message, true, $return == false ? "WARNING" : "INFO" );
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
		$return       = $this->link->delete( md5( $this->identifier . $key ) );
		$_log_message = "Cache - Memcached - do_remove(): " . ( $return == false ? "Failed" : "Succeeded" ) . " to REMOVE item '" . $key . "'.";
		$this->_result_codes__do_log( $_log_message, true, $return == false ? "WARNING" : "INFO" );
		return $return;
	}
}
?>
