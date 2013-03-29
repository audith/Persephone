<?php

namespace Persephone\Cache;

if ( !defined( "INIT_DONE" ) )
{
	die( "Improper access! Exiting now..." );
}

/**
 * Cache > Drivers > APC
 *
 * @package      Audith CMS codename Persephone
 * @author       Shahriyar Imanov <shehi@imanov.name>
 * @version      1.0
 **/

//require_once( dirname( __FILE__ ) . "/_interface.php" );

class Apc implements Iface
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
	 * Constructor
	 *
	 * @param    \Persephone\Registry Registry              reference
	 * @param                         string                Unique-ID used to hash keys
	 * @return   boolean
	 */
	public function __construct ( \Persephone\Registry $Registry, $identifier = "" )
	{
		# Prelim
		$this->Registry = $Registry;

		# Cont.
		if ( !function_exists( "apc_fetch" ) )
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

		return true;

	}


	/**
	 * Disconnect from remote cache store
	 *
	 * @return   boolean    Whether or not the disconnect attempt was successful - TRUE on success, FALSE otherwise
	 */
	public function disconnect ()
	{
		return true;
	}

	/**
	 * Put data into remote cache store
	 *
	 * @param       string          Cache unique key
	 * @param       string          Cache value to add
	 * @param       integer         [Optional] Time to live
	 * @return      boolean         Whether cache set was successful or not; TRUE on success, FALSE otherwise
	 */
	public function do_put ( $key, $value, $ttl = 0 )
	{
		$ttl = $ttl > 0 ? intval( $ttl ) : 0;

		return apc_store( md5( $this->identifier . $key ), $value, $ttl );
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
		$_log_message = "Cache - APC - do_update(): " . ( $return == false ? "Failed" : "Succeeded" ) . " to REPLACE item '" . $key . "'.";
		$this->Registry->logger__do_log( $_log_message , $return == false ? "WARNING" : "INFO" );
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
		$return_val = "";

		$return_val = apc_fetch( md5( $this->identifier . $key ) );

		return $return_val;
	}

	/**
	 * Remove a value in the remote cache store
	 *
	 * @param       string          Cache unique key
	 * @return      boolean         Whether cache removal was successful or not; TRUE on success, FALSE otherwise
	 */
	public function do_remove ( $key )
	{
		apc_delete( md5( $this->identifier . $key ) );
	}
}
?>
