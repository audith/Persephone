<?php

if ( ! defined( "INIT_DONE" ) )
{
	print "Improper access! Exiting now...";
	exit();
}

/**
 * Data-sources abstraction class
 *
 * @package  Audith CMS codename Persephone
 * @author   Shahriyar Imanov <shehi@imanov.name>
 * @version  1.0
 */
abstract class Data_Sources
{
	/**
	 * Registry reference
	 * @var Registry
	**/
	public $Registry;


	/**
	 * Constructor
	 *
	 * @param  object  Registry reference.
	 */
	public function __construct ( Registry $Registry )
	{
		$this->Registry = $Registry;
	}

	/**
	 * Disable __toString() magic-function
	 */
	private function __toString ()
	{
		return false;
	}

	/**
	 * Creates a new Module Subroutine
	 *
	 * @param    array   Parsed subroutine-configuration
	 * @param    array   Incoming subroutine-configuration
	 * @return   array   Array containing status code pairs (either responseCode-responseMessage (on SUCCESS); or faultCodes-faultMessages (otherwise) )
	 */
	abstract public function modules__subroutines__do_validate ( &$subroutine , &$input );
}