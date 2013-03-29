<?php

if ( ! defined( "INIT_DONE" ) )
{
	print "Improper access! Exiting now...";
	exit();
}

/**
 * Data-targets abstraction class
 *
 * @package  Audith CMS codename Persephone
 * @author   Shahriyar Imanov <shehi@imanov.name>
 * @version  1.0
 */
abstract class Data_Targets
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
}