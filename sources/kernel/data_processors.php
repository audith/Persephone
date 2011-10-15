<?php

if ( ! defined( "INIT_DONE" ) )
{
	print "Improper access! Exiting now...";
	exit();
}

/**
 * MODULES : CORE.ALPHANUMERIC
 *
 * @package  Audith CMS codename Persephone
 * @author   Shahriyar Imanov <shehi@imanov.name>
 * @version  1.0
**/
abstract class Data_Processors
{
	/**
	 * Constructor
	 *
	 * @param  object  API reference.
	 */
	public function __construct ( API $API )
	{
		$this->API = $API;
	}


	/**
	 * __toString()
	 */
	abstract public function __toString ();


	/**
	 * Content fetch - Processor.
	 * This method DOES NOT "get" anything, it justs preps the data for servicing!!!
	 *
	 * @param   array   Data to be processed.
	 * @return  array   Final data.
	 */
	abstract public function get__do_process ( $data );


	/**
	 * Content put - Processor.
	 * This method DOES NOT "put" anything, it justs preps the data for servicing!!!
	 *
	 * @return   mixed   (boolean) TRUE on success; (boolean) FALSE or (array) fault-code-message otherwise.
	 */
	abstract public function put__do_process ();


	/**
	 * Content delete - Processor.
	 * This method DOES NOT "delete" anything, it justs preps the data for servicing!!!
	 *
	 * @return   mixed   (boolean) TRUE on success; (boolean) FALSE or (array) fault-code-message otherwise.
	 */
	abstract public function delete__do_process ();


	/**
	 * Validates incoming new DDL creation request
	 *
	 * @param   array      Clean input via POST.
	 * @param   array      Module info.
	 * @param   array      Validated DDL-configuration. Used on return. Defaults to empty array.
	 * @param   array      Array of errors occured. Used on return. Defaults to empty array.
	 * @return  boolean    TRUE on success, FALSE otherwise.
	 */
	abstract public function modules__ddl__do_validate( &$input , &$m , &$ddl_config__validated = array() , &$faults = array() );

	/**
	 * Checks whether the chosen data-field is eligible to be a Title-field or not.
	 *
	 * @param    array     DDL-information of the field.
	 * @return   boolean   TRUE if yes, FALSE otherwise.
	 */
	abstract public function modules__ddl__is_eligible_for_title ( &$ddl_information );
}