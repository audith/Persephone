<?php

namespace Persephone\Cache;

if ( !defined( "INIT_DONE" ) )
{
	die( "Improper access! Exiting now..." );
}

/**
 * Cache > Drivers Interface
 *
 * @package  Audith CMS codename Persephone
 * @author   Shahriyar Imanov <shehi@imanov.name>
 * @version  1.0
 **/
interface Iface
{
	/**
	 * Constructor
	 *
	 * @param    \Persephone\Registry  Registry reference
	 * @param    string                Unique-ID used to hash keys
	 * @return   boolean
	 */
	public function __construct ( \Persephone\Registry $Registry, $identifier = "" );


	/**
	 * Disconnect from remote cache store
	 *
	 * @return   boolean    Whether or not the disconnect attempt was successful - TRUE on success, FALSE otherwise
	 */
	public function disconnect ();


	/**
	 * Put data into remote cache store
	 *
	 * @param       string          Cache unique key
	 * @param       string          Cache value to add
	 * @param       integer         [Optional] Time to live
	 * @return      boolean         Whether cache set was successful or not; TRUE on success, FALSE otherwise
	 */
	public function do_put ( $key, $value, $ttl = 0 );


	/**
	 * Update value in remote cache store
	 *
	 * @param       string          Cache unique key
	 * @param       string          Cache value to set
	 * @param       integer         [Optional] Time to live
	 * @return      boolean         Whether cache update was successful or not; TRUE on success, FALSE otherwise
	 */
	public function do_update ( $key, $value, $ttl = 0 );


	/**
	 * Retrieve a value from remote cache store
	 *
	 * @param       string          Cache unique key
	 * @return      mixed           Cached value
	 */
	public function do_get ( $key );


	/**
	 * Remove a value in the remote cache store
	 *
	 * @param       string          Cache unique key
	 * @return      boolean         Whether cache removal was successful or not; TRUE on success, FALSE otherwise
	 */
	public function do_remove ( $key );
}

?>
