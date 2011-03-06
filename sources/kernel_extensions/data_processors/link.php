<?php

if ( ! defined( "INIT_DONE" ) )
{
	print "Improper access! Exiting now...";
	exit();
}

/**
 * FILE Data Processor
 *
 * @package  Audith CMS codename Persephone
 * @author   Shahriyar Imanov <shehi@imanov.name>
 * @version  1.0
**/

require_once( dirname( __FILE__ ) . "/_interface.php" );

class Data_Processor__Link extends Data_Processor
{
	/**
	 * API Object Reference
	 * @var object
	 */
	public $API;

	/**
	 * Faults/errors/exceptions container
	 * @var array
	 */
	public $faults = array();

	/**
	 * Data/content
	 * @var mixed
	 */
	public $data;

	/**
	 * Validated DDL configuration [ACP > COMPONENTS > DDL management]
	 * @var array
	 */
	public $ddl_config__validated = array();

	/**
	 * Image library the script uses - Imagick or GD
	 * @var string
	 */
	private $graphic_library_used = null;


	/**
	 * Contructor
	 * @param    API    API object reference
	 */
	public function __construct ( API $API )
	{
		parent::__construct( $API );
	}


	/**
	 * __toString()
	 */
	public function __toString ()
	{
	}


	/**
	 * Content fetch - Processor
	 *
	 * @see Data_Processor::get__do_process()
	 * @param   array   Data to be processed
	 * @return  array   Final data
	 */
	public function get__do_process ( $data )
	{
		$this->data = $data;

		$_linked_module = $this->API->Modules->modules__do_load( $this->API->Cache->cache['modules']['by_unique_id'][ $this->data['field']['e_data_definition']['m_unique_id'] ] );
		$this->data['content'] = $this->API->Modules->content__do__by_ref_id(
				$_linked_module,
				$this->data['content'],
				$this->data['field']['e_data_definition']['m_data_definition']
			);

		return $this->data['content'];
	}


	/**
	 * Content put - Processor
	 *
	 * @see Data_Processor::put__do_process()
	 * @return   mixed   (boolean) TRUE on success; (boolean) FALSE or (array) fault-code-message otherwise
	 */
	public function put__do_process ()
	{

	}


	/**
	 * Content delete - Processor
	 *
	 * @return   mixed   (boolean) TRUE on success; (boolean) FALSE or (array) fault-code-message otherwise
	 */
	public function delete__do_process ()
	{

	}


	/**
	 * Validates incoming new DDL creation request
	 *
	 * @see Data_Processor::modules__ddl__do_validate()
	 * @param   array      Clean input via POST
	 * @param   array      Module info
	 * @return  boolean    TRUE on success, FALSE otherwise
	 */
	public function modules__ddl__do_validate ( &$input, &$m )
	{
		$this->faults = array();
		$this->ddl_config__validated = array();

		//---------------------------------------------------------------
		// Parameters of less importance : Name, Label, "Is Required?"
		//---------------------------------------------------------------

		$_list_of_reserved_names = array( "id" , "tags" , "timestamp" , "submitted_by" , "status_published" , "status_locked" );
		$dft_name         =  strtolower( $input['name'] );
		$dft_label        =  $input['label'];
		$dft_is_required  =  $input['is_required'] ? 1 : 0;

		# Clean-up
		if ( empty( $dft_name ) )
		{
			$this->faults[] = array( 'faultCode' => 701, 'faultMessage' => "NAME__IS_REQUIRED" );
			// "<em>Field Name</em> is a required field!"
		}
		elseif ( in_array( $dft_name, $_list_of_reserved_names ) )
		{
			$this->faults[] = array( 'faultCode' => 701, 'faultMessage' => "NAME__IS_A_RESERVED_KEYWORD" );
			// "<em>Field Name</em> cannot have one of the following reserved values - change your entry:<br />&nbsp;&nbsp;&nbsp;<em>" . implode( ", " , $_list_of_reserved_names ) .  "</em>"
		}
		elseif ( ! preg_match( "#^[a-z][a-z0-9_]+$#" , $dft_name ) )
		{
			$this->faults[] = array( 'faultCode' => 701, 'faultMessage' => "NAME__IS_INVALID" );
			// "<em>Field Name</em> must contain only a lowercase alphanumeric and underscore characters, and must not start with any numerical!"
		}
		elseif ( array_key_exists( $dft_name, $m['m_data_definition'] ) or array_key_exists( $dft_name, $m['m_data_definition_bak'] ) )
		{
			$this->faults[] = array( 'faultCode' => 701, 'faultMessage' => "NAME__NOT_AVAILABLE" );
			// "<em>Field Name</em> not available; either already registered, or exists in backups!"
		}
		if ( empty( $dft_label ) )
		{
			$this->faults[] = array( 'faultCode' => 702, 'faultMessage' => "LABEL__IS_REQUIRED" );
			// "<em>Field Label</em> is a required field!"
		}

		//------------------------------
		// Continue... - 'links_with'
		//------------------------------

		# Module name
		if ( empty( $input['links_with'] ) )
		{
			$this->faults[] = array( 'faultCode' => 704 , 'faultMessage' => "MODULE_NAME__IS_REQUIRED" );
			return false;
		}

		if ( preg_match( '/^[0-9a-z]{32}$/i' , $input['links_with'] ) )
		{
			$input['links_with'] = "{" . implode( "-", str_split( strtoupper( $input['links_with'] ), 8 ) ) . "}";
		}
		if ( ! array_key_exists( $input['links_with'] , $this->API->Cache->cache['modules']['by_unique_id'] ) )
		{
			$this->faults[] = array( 'faultCode' => 704 , 'faultMessage' => "MODULE_NAME__IS_INVALID" );
			return false;
		}
		$l =& $this->API->Cache->cache['modules']['by_unique_id'][ $input['links_with'] ];

		$_list_of_corrupt_fields                    = array();
		$_list_of_fields_with_nonlinked_connectors  = array();
		$_list_of_inexistent_fields                 = array();

		//-------------------------------------
		// 'links_with__e_data_definition'
		//-------------------------------------

		if ( empty( $input['links_with__e_data_definition'] ) )
		{
			$this->faults[] = array( 'faultCode' => 705 , 'faultMessage' => "E_DATA_DEFINITION__IS_REQUIRED" );
			return false;
		}

		foreach ( $input['links_with__e_data_definition'] as $_f )
		{
			# Connector-enabled field?
			if ( strpos( $_f , "." ) !== false )
			{
				$__f = explode( "." , $_f );
				if ( count( $__f ) != 2 )
				{
					$_list_of_corrupt_fields[] = $_f;
				}
			}
			else
			{
				$__f = array( $_f );
			}

			# Field exists within Data-definition of the linked module
			if ( array_key_exists( $__f[0] , $l['m_data_definition'] ) )
			{
				# Connector-enabled-field: Is it linked and valid?
				if ( isset( $__f[1] ) and ( ! $l['m_data_definition'][ $__f[0] ]['connector_linked'] or ! isset( $l['m_data_definition'][ $__f[0] ]['c_data_definition'][ $__f[1] ] ) ) )
				{
					$_list_of_fields_with_nonlinked_connectors[] = $_f;
				}
			}
			else
			{
				$_list_of_inexistent_fields[] = $_f;
			}
		}

		if ( count( $_list_of_corrupt_fields ) )
		{
			$this->faults[] = array( 'faultCode' => 705 , 'faultMessage' => "Fatal error! Invalid data-definition structure detected! Please inform the admin about the situation and DO NOT continue with what were you doing!!! Problematic fields are as follows:<br /><i>" . implode( ", " , $_list_of_corrupt_fields ) . "</i>" );
		}

		if ( count( $_list_of_fields_with_nonlinked_connectors ) )
		{
			$this->faults[] = array( 'faultCode' => 705 , 'faultMessage' => "Invalid data-definition provided for external linking! The connectors for the following fields you provided, are not linked:<br /><i>" . implode( ", " , $_list_of_fields_with_nonlinked_connectors ) . "</i>" );
		}

		if ( count( $_list_of_inexistent_fields ) )
		{
			$this->faults[] = array( 'faultCode' => 705 , 'faultMessage' => "Invalid data-definition provided for external linking! Module '" . $l['m_name'] . "' does not have following DDL-components:<br /><i>" . implode( ", ", $_list_of_inexistent_fields ) . "</i>" );
		}

		if ( count( $this->faults ) )
		{
			return false;
		}

		//--------------------------------------------------------------
		// Still here? Continue...
		// Updating Module-records and Altering Module content-tables
		//--------------------------------------------------------------

		$this->ddl_config__validated = array(
				'm_unique_id'          =>  $m['m_unique_id'],
				'name'                 =>  $dft_name,
				'label'                =>  $dft_label,
				'type'                 =>  "link",
				'maxlength'            =>  10,
				'request_regex'        =>  '\d{1,10}',
				'input_regex'          =>  '\d{1,10}',
				'is_required'          =>  $dft_is_required,
				'is_unique'            =>  $input['is_unique'] ? 1 : 0,
				'is_numeric'           =>  1,
				'e_data_definition'    =>  $input['links_with'] . "\n" . implode( "\n" , $input['links_with__e_data_definition'] ),
			);

		return true;
	}


	/**
	 * Checks whether the chosen data-field is eligible to be a Title-field or not
	 *
	 * @see Data_Processor::modules__ddl__is_eligible_for_title()
	 * @param    array     DDL-information of the field
	 * @return   boolean   TRUE if yes, FALSE otherwise
	 */
	public function modules__ddl__is_eligible_for_title ( &$ddl_information )
	{
		return false;
	}
}