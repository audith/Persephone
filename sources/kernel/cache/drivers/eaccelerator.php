<?php

if ( ! defined( "INIT_DONE" ) )
{
	print "Improper access! Exiting now...";
	exit();
}

/**
 * Cache > Drivers > eaccelerator
 *
 * @package  Invision Power Board
 * @author   Matthew Mecham @ IPS
 * @version  1.0
**/

require_once( dirname( __FILE__ ) . "/_interface.php" );

class Cache__Drivers__Eaccelerator implements iCache_Drivers
{
	/**
	 * Registry reference
	 *
	 * @var object
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


	public function __construct ( Registry $Registry, $identifier = "" )
	{
		# Prelim
		$this->Registry = $Registry;

		# Cont.
		if ( ! function_exists( "eaccelerator_get" ) )
		{
			$this->crashed = 1;
			return FALSE;
		}

		if ( !$identifier )
		{
			$this->identifier = $this->Registry->Input->server('SERVER_NAME');
		}
		else
		{
			$this->identifier = $identifier;
		}

		unset( $identifier );

	}


	public function disconnect ()
	{
		if ( function_exists( "eaccelerator_gc" ) )
		{
			eaccelerator_gc();
		}

		return TRUE;
	}


	public function do_put ( $key, $value, $ttl=0 )
	{
		eaccelerator_lock( md5( $this->identifier . $key ) );

		eaccelerator_put(
				md5( $this->identifier . $key ),
				$value,
				intval($ttl)
			);

		eaccelerator_unlock( md5( $this->identifier . $key ) );
	}

	public function do_get ( $key )
	{
		$return_val = eaccelerator_get( md5( $this->identifier . $key ) );

		return $return_val;
	}

	function do_remove ( $key )
	{
		eaccelerator_rm( md5( $this->identifier . $key ) );
	}
}
?>