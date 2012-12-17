<?php

if ( ! defined( "INIT_DONE" ) )
{
	print "Improper access! Exiting now...";
	exit();
}

/**
 * Cache > Drivers > APC
 *
 * @package      Audith CMS codename Persephone
 * @author       Shahriyar Imanov <shehi@imanov.name>
 * @version      1.0
 *
 * @license		 http://www.invisionpower.com/community/board/license.html
 * @copyright    Matthew Mecham, Invision Power Board v1.3
**/

require_once( dirname( __FILE__ ) . "/_interface.php" );

class Cache__Drivers__Apc implements iCache_Drivers
{
	/**
	 * Registry reference
	 *
	 * @var Registry
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
		if ( ! function_exists( "apc_fetch" ) )
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