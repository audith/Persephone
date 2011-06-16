<?php

if ( ! defined( "INIT_DONE" ) )
{
	print "Improper access! Exiting now...";
	exit();
}

/**
 * Data-sources parent-class
 *
 * @package  Audith CMS codename Persephone
 * @author   Shahriyar Imanov <shehi@imanov.name>
 * @version  1.0
**/
abstract class Data_Sources
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
	 * GET - Fetches all the content requested, according to the Subroutine fetch-criteria
	 *
	 * @param   array    Reference: Module info
	 * @return  mixed    (mixed) Fetched content on success; (boolean) FALSE otherwise
	 *
	 * @todo   404 not found situation is not properly implemented
	 * @todo   Page number isn't implemented into $_cache_key_name__* variables
	 */
	abstract public function get__do_process ( &$m );


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
	abstract public function modules__ddl__do_validate( &$input , &$m , &$ddl_config__validated , &$faults );

	/**
	 * Checks whether the chosen data-field is eligible to be a Title-field or not.
	 *
	 * @param    array     DDL-information of the field.
	 * @return   boolean   TRUE if yes, FALSE otherwise.
	 */
	abstract public function modules__ddl__is_eligible_for_title ( &$ddl_information );
}

?>