<?php

if ( ! defined( "INIT_DONE" ) )
{
	print "Improper access! Exiting now...";
	exit();
}

/**
 * Data-targets > TPL
 *
 * @package  Audith CMS codename Persephone
 * @author   Shahriyar Imanov <shehi@imanov.name>
 * @version  1.0
 */

require_once( PATH_SOURCES . "/kernel/data_targets.php" );

class Data_Sources__Targets extends Data_Targets
{
	/**
	 * Contructor
	 * @param    Registry     Registry reference
	 */
	public function __construct ( Registry $Registry )
	{
		parent::__construct( $Registry );
	}}