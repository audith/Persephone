<?php

if ( ! defined( "INIT_DONE" ) )
{
	print "Improper access! Exiting now...";
	exit();
}

/**
 * APC Cache Storage
 *
 * @package  Invision Power Board
 * @author   Matthew Mecham @ IPS
 * @version  1.0
**/

require_once( dirname( __FILE__ ) . "/_interface.php" );

class Cache_Lib implements iCache_Lib
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


	public function __construct ( $identifier="" , API $API )
	{
		# Prelim
		$this->API = $API;

		# Cont.
		if ( ! function_exists( "apc_fetch" ) )
		{
			$this->crashed = 1;
			return FALSE;
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

	}


	public function disconnect ()
	{
		return TRUE;
	}


	public function do_put ( $key, $value, $ttl=0 )
	{
		$ttl = $ttl > 0 ? intval($ttl) : 0;

		apc_store(
				md5( $this->identifier . $key ),
				$value,
				$ttl
			);
	}

	public function do_get ( $key )
	{
		$return_val = "";

		$return_val = apc_fetch( md5( $this->identifier . $key ) );

		return $return_val;
	}

	public function do_remove ( $key )
	{
		apc_delete( md5( $this->identifier . $key ) );
	}
}
?>