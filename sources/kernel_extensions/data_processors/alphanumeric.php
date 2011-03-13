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

require_once( dirname( __FILE__ ) . "/_interface.php" );

class Data_Processor__Alphanumeric extends Data_Processor
{
	/**
	 * API Object Reference
	 * @var object
	**/
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
		return (string) $this->data['content'];
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
		# Bring in data being processed
		$this->data = $data;

		//---------------------
		// Preliminary stuff
		//---------------------

		# Running module
		$m =& $this->API->Modules->cur_module;

		//-----------------
		// Processing...
		//-----------------

		# READ-ONLY mode
		if ( $m['running_subroutine']['s_service_mode'] == 'read-only' )
		{
			// NOthing for now...
		}
		elseif ( $m['running_subroutine']['s_service_mode'] == 'read-write' )
		{
			//$this->data['content'] = html_entity_decode( $this->data['content'], ENT_QUOTES, "UTF-8" );
		}

		switch ( $this->data['field']['subtype'] )
		{
			case 'string':

				//----------
				// BBCode
				//----------

				$this->data['content'] = preg_replace( '#\[url=&quot; (.+) &quot;\] (.+) \[\/url\]#Ux' , "<a href=\"\\1\" title=\"External Link\" rel=\"nofollow\">\\2</a>" , $this->data['content'] );

				if ( ! $this->data['field']['is_html_allowed'] )
				{
					$this->data['content'] = strip_tags( $this->data['content'] , "<br>" );
				}
				else
				{
					// $this->data['content'] = strip_tags( $this->data['content'] , "<br><p><ul><ol><li><strong><em><span><a><blockquote><sub><sup>" );
				}

				settype( $this->data['content'], "string" );
				break;

			case 'integer_signed_8':
			case 'integer_unsigned_8':
			case 'integer_signed_16':
			case 'integer_unsigned_16':
			case 'integer_signed_24':
			case 'integer_unsigned_24':
			case 'integer_signed_32':
			case 'integer_unsigned_32':
			case 'integer_signed_64':
			case 'integer_unsigned_64':
				settype( $this->data['content'], "integer" );
				break;

			case 'decimal_signed':
			case 'decimal_unsigned':
				settype( $this->data['content'], "float" );
				break;

			case 'dropdown':
			case 'multiple':
				settype( $this->data['content'], "string" );
				break;
		}

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
	 * @param   array      Clean input via POST.
	 * @param   array      Module info.
	 * @param   array      Validated DDL-configuration. Used on return. Defaults to empty array.
	 * @param   array      Array of errors occured. Used on return. Defaults to empty array.
	 * @return  boolean    TRUE on success, FALSE otherwise.
	 */
	public function modules__ddl__do_validate ( &$input , &$m , &$ddl_config__validated = array() , &$faults = array() )
	{
		# SKELETON
		$skel = array(
				'string'                  =>  array(
						'title'                =>  "string",
						'maxlength'            =>  255,
						'input_regex'          =>  null,
						'request_regex'        =>  "[a-z0-9-]{1,%s}",
						'default_options'      =>  null,
						'default_value'        =>  "",
						'connector_enabled'    =>  false,
						'connector_length_cap' =>  1,
						'is_unique'            =>  0,
						'is_numeric'           =>  false
					),
				'integer_signed_8'        =>  array(
						'title'                =>  "integer_signed_8",
						'maxlength'            =>  4,
						'input_regex'          =>  "[+-]?\d{1,%u}",
						'request_regex'        =>  "[+-]?\d{1,%u}",
						'default_options'      =>  null,
						'default_value'        =>  0,
						'connector_enabled'    =>  false,
						'connector_length_cap' =>  1,
						'min_value'            =>  -128,
						'max_value'            =>  127,
						'is_unique'            =>  0,
						'is_numeric'           =>  true
					),
				'integer_unsigned_8'      =>  array(
						'title'                =>  "integer_unsigned_8",
						'maxlength'            =>  3,
						'input_regex'          =>  "[+]?\d{1,%u}",
						'request_regex'        =>  "[+]?\d{1,%u}",
						'default_options'      =>  null,
						'default_value'        =>  0,
						'connector_enabled'    =>  false,
						'connector_length_cap' =>  1,
						'min_value'            =>  0,
						'max_value'            =>  255,
						'is_unique'            =>  0,
						'is_numeric'           =>  true
					),
				'integer_signed_16'       =>  array(
						'title'                =>  "integer_signed_16",
						'maxlength'            =>  6,
						'input_regex'          =>  "[+-]?\d{1,%u}",
						'request_regex'        =>  "[+-]?\d{1,%u}",
						'default_options'      =>  null,
						'default_value'        =>  0,
						'connector_enabled'    =>  false,
						'connector_length_cap' =>  1,
						'min_value'            =>  -32768,
						'max_value'            =>  32767,
						'is_unique'            =>  0,
						'is_numeric'           =>  true
					),
				'integer_unsigned_16'     =>  array(
						'title'                =>  "integer_unsigned_16",
						'maxlength'            =>  5,
						'input_regex'          =>  "[+]?\d{1,%u}",
						'request_regex'        =>  "[+]?\d{1,%u}",
						'default_options'      =>  null,
						'default_value'        =>  0,
						'connector_enabled'    =>  false,
						'connector_length_cap' =>  1,
						'min_value'            =>  0,
						'max_value'            =>  65535,
						'is_unique'            =>  0,
						'is_numeric'           =>  true
					),
				'integer_signed_24'       =>  array(
						'title'                =>  "integer_signed_24",
						'maxlength'            =>  8,
						'input_regex'          =>  "[+-]?\d{1,%u}",
						'request_regex'        =>  "[+-]?\d{1,%u}",
						'default_options'      =>  null,
						'default_value'        =>  0,
						'connector_enabled'    =>  false,
						'connector_length_cap' =>  1,
						'min_value'            =>  -8388608,
						'max_value'            =>  8388607,
						'is_unique'            =>  0,
						'is_numeric'           =>  true
					),
				'integer_unsigned_24'     =>  array(
						'title'                =>  "integer_unsigned_24",
						'maxlength'            =>  8,
						'input_regex'          =>  "[+]?\d{1,%u}",
						'request_regex'        =>  "[+]?\d{1,%u}",
						'default_options'      =>  null,
						'default_value'        =>  0,
						'connector_enabled'    =>  false,
						'connector_length_cap' =>  1,
						'min_value'            =>  0,
						'max_value'            =>  16777215,
						'is_unique'            =>  0,
						'is_numeric'           =>  true
					),
				'integer_signed_32'       =>  array(
						'title'                =>  "integer_signed_32",
						'maxlength'            =>  11,
						'input_regex'          =>  "[+-]?\d{1,%u}",
						'request_regex'        =>  "[+-]?\d{1,%u}",
						'default_options'      =>  null,
						'default_value'        =>  0,
						'connector_enabled'    =>  false,
						'connector_length_cap' =>  1,
						'min_value'            =>  -2147483648,
						'max_value'            =>  2147483647,
						'is_unique'            =>  0,
						'is_numeric'           =>  true
					),
				'integer_unsigned_32'     =>  array(
						'title'                =>  "integer_unsigned_32",
						'maxlength'            =>  10,
						'input_regex'          =>  "[+]?\d{1,%u}",
						'request_regex'        =>  "[+]?\d{1,%u}",
						'default_options'      =>  null,
						'default_value'        =>  0,
						'connector_enabled'    =>  false,
						'connector_length_cap' =>  1,
						'min_value'            =>  0,
						'max_value'            =>  4294967295,
						'is_unique'            =>  0,
						'is_numeric'           =>  true
					),
				'integer_signed_64'       =>  array(
						'title'                =>  "integer_signed_64",
						'maxlength'            =>  20,
						'input_regex'          =>  "[+-]?\d{1,%u}",
						'request_regex'        =>  "[+-]?\d{1,%u}",
						'default_options'      =>  null,
						'default_value'        =>  0,
						'connector_enabled'    =>  false,
						'connector_length_cap' =>  1,
						'min_value'            =>  -9223372036854775808,
						'max_value'            =>  9223372036854775807,
						'is_unique'            =>  0,
						'is_numeric'           =>  true
					),
				'integer_unsigned_64'     =>  array(
						'title'                =>  "integer_unsigned_64",
						'maxlength'            =>  20,
						'input_regex'          =>  "[+]?\d{1,%u}",
						'request_regex'        =>  "[+]?\d{1,%u}",
						'default_options'      =>  null,
						'default_value'        =>  0,
						'connector_enabled'    =>  false,
						'connector_length_cap' =>  1,
						'min_value'            =>  0,
						'max_value'            =>  18446744073709551615,
						'is_unique'            =>  0,
						'is_numeric'           =>  true
					),
				'decimal_signed'     =>  array(
						'title'                =>  "decimal_signed",
						'maxlength'            =>  "10,0",
						'input_regex'          =>  "[+-]?\d{1,%u}(?:\.\d{0,%u})?",
						'request_regex'        =>  "[+-]?\d{1,%u}(?:\.\d{0,%u})?",
						'default_options'      =>  null,
						'default_value'        =>  0,
						'connector_enabled'    =>  false,
						'connector_length_cap' =>  1,
						'min_value'            =>  null,
						'max_value'            =>  null,
						'is_unique'            =>  0,
						'is_numeric'           =>  true
					),
				'decimal_unsigned'   =>  array(
						'title'                =>  "decimal_unsigned",
						'maxlength'            =>  "10,0",
						'input_regex'          =>  "[+]?\d{1,%u}(?:\.\d{0,%u})?",
						'request_regex'        =>  "[+]?\d{1,%u}(?:\.\d{0,%u})?",
						'default_options'      =>  null,
						'default_value'        =>  0,
						'connector_enabled'    =>  false,
						'connector_length_cap' =>  1,
						'min_value'            =>  null,
						'max_value'            =>  null,
						'is_unique'            =>  0,
						'is_numeric'           =>  true
					),
				'dropdown'           =>  array(
						'title'                =>  'dropdown',
						'maxlength'            =>  "",
						'input_regex'          =>  "(?:%s)",
						'request_regex'        =>  "(?:%s)",
						'default_options'      =>  "",
						'default_value'        =>  null,
						'connector_enabled'    =>  false,
						'connector_length_cap' =>  1,
						'min_value'            =>  null,
						'max_value'            =>  null,
						'is_unique'            =>  0,
						'is_numeric'           =>  false
					),
				'multiple'           =>  array(
						'title'                =>  'multiple',
						'maxlength'            =>  "",
						'input_regex'          =>  "(?:%s)+",
						'request_regex'        =>  "(?:%s)(?>,(?:%s))+",
						'default_options'      =>  "",
						'default_value'        =>  null,
						'connector_enabled'    =>  false,
						'connector_length_cap' =>  1,
						'min_value'            =>  null,
						'max_value'            =>  null,
						'is_unique'            =>  0,
						'is_numeric'           =>  false
					),
			);

		//---------------------------
		// Validation & Processing
		//---------------------------

		# SUB-TYPE: Validation...
		if ( ! isset( $input['subtype'] ) )
		{
			$_skel_subtype_key = 0;
		}
		else
		{
			$_skel_subtype_key = $input['subtype'];
		}

		if ( ! array_key_exists( $_skel_subtype_key, $skel ) )
		{
			$faults[] = array( 'faultCode' => 706, 'faultMessage' => "SUBTYPE__IS_INVALID" );
			// "No such data-subtype is defined: <em>" . $input[ $_form_field_name ] . "</em> (for data-type: <em>alphanumeric</em>)!"
		}
		else
		{
			# SUB-TYPE: Processing...
			$dft_subtype = $skel[ $_skel_subtype_key ]['title'];
		}
		$_skel_subtype_node =& $skel[ $dft_subtype ];

		# Return of Critical Faults - Level 1
		if ( count( $faults ) )
		{
			return $faults;
		}

		# DEFAULT OPTIONS: Proccessing (for ALPHANUMERIC)...
		$dft_default_options = null;
		if ( ! is_null( $_skel_subtype_node['default_options'] ) )
		{
			switch ( $dft_subtype )
			{
				case 'dropdown':
				case 'multiple':
					# DEFAULT OPTIONS: Validation (for DROPDOWN, MULTIPLE)...
					if ( ! $input['default_options'] )
					{
						$faults[] = array( 'faultCode' => 708, 'faultMessage' => "DEFAULT_OPTIONS__IS_REQUIRED_FOR_DROPDOWN_MULTIPLE" );
						// "<em>Default Options</em> is a required field for '<em>Preset Single/Multiple Select</em>' data-types!"
					}
					else
					{
						$input['default_options'] = explode( "\n", $input['default_options'] );
						foreach ( $input['default_options'] as $_option )
						{
							$__option = explode( "=", $_option );
							if ( count( $__option ) != 2 )
							{
								$faults[] = array( 'faultCode' => 708, 'faultMessage' => "DEFAULT_OPTIONS__ONE_EQ_PER_LINE_ONLY" );
								// "<em>Default Options</em>: Each line must contain one and only one 'equals' (=) sign!"
							}
							elseif ( strlen( $__option[0] ) == 0 or strlen( $__option[1] ) == 0 )
							{
								$faults[] = array( 'faultCode' => 708, 'faultMessage' => "DEFAULT_OPTIONS__EQ_EITHER_SIDE_EMPTY" );
								// "<em>Default Options</em>: Neither side of 'equals' (=) sign can be empty!"
							}
							elseif ( ! preg_match( '#^[a-z0-9_]+$#i' , $__option[0] ) )
							{
								$faults[] = array( 'faultCode' => 708, 'faultMessage' => "DEFAULT_OPTIONS__INVALID_CHARACTERS_INSIDE_KEY" );
								// "<em>Default Options</em>: Left side of 'equals' (=) signs - i.e. the 'keys' of the options can contain <em>Perl-'word'-characters</em> ('<em>\w</em>') only!"
							}
							# DEFAULT OPTIONS: Processing (for DROPDOWN, MULTIPLE)...
							$dft_default_options[ $__option[0] ] = $__option[1];

							# For later reference...
							$_dft_maxlength_for_dropdown_and_multiple[] = "'" . $__option[0] . "'";

							$_dft_regex_for_dropdown_multiple[] = preg_quote( $__option[0] );
						}

						# For later reference...
						$_dft_maxlength_for_dropdown_and_multiple = implode( "," , $_dft_maxlength_for_dropdown_and_multiple );

						# For later reference...
						$_dft_regex_for_dropdown_multiple = implode( "|", $_dft_regex_for_dropdown_multiple );
					}
					break;
			}
		}

		# MAXLENGTH: Validation...
		$dft_maxlength = $_skel_subtype_node['maxlength'];

		switch ( $dft_subtype )
		{
			case 'string':
				if ( intval( $input['maxlength'] ) > 0 )
				{
					# MAXLENGTH: Processing (for ALPHANUMERIC\STRING)...
					$dft_maxlength = intval( $input['maxlength'] );
				}
				break;

			case 'decimal_signed':
			case 'decimal_unsigned':
				if ( isset( $input['maxlength'] ) and ! empty( $input['maxlength'] ) )
				{
					if ( preg_match( '#^(\d{1,2}),(\d{1,2})$#', $input['maxlength'], $_dft_maxlength ) )
					{
						if ( $_dft_maxlength[1] == 0 )
						{
							$faults[] = array( 'faultCode' => 709, 'faultMessage' => "PRECISION_SCALE__INVALID_ENTRY__REVERTING_TO_DEFAULT", 'faultExtra' => "10,0" );
							// "Invalid entry for <em>Precision &amp; Scale</em>! Assuming default value ('<em>10,0</em>'). Re-submit the form!"
						}
						elseif ( $_dft_maxlength[1] > 64 )
						{
							$faults[] = array( 'faultCode' => 709, 'faultMessage' => "PRECISION_SCALE__PRECISION_EXCEEDS_MAXIMUM" );
							// "Invalid entry for <em>Precision &amp; Scale</em>! Number to the <em>left of comma</em> cannot exceed 64! You entered <em>" . $_dft_maxlength[1] . "<em> which is more than 64!"
						}
						elseif ( $_dft_maxlength[1] <= $_dft_maxlength[2] )
						{
							$faults[] = array( 'faultCode' => 709, 'faultMessage' => "PRECISION_SCALE__PRECISION_SMALLER_THAN_SCALE", 'faultExtra' => $_dft_maxlength[1] . ",0" );
							// "Invalid entry for <em>Precision &amp; Scale</em>! Assuming value to be '<em>" . $_dft_maxlength[1] . ",0</em>'. Re-submit the form!"
						}
						else
						{
							# MAXLENGTH: Processing (for ALPHANUMERIC\DECIMAL)...
							$dft_maxlength = $_dft_maxlength[0];
						}
					}
					else
					{
						$faults[] = array( 'faultCode' => 709, 'faultMessage' => "PRECISION_SCALE__INVALID_ENTRY__REVERTING_TO_DEFAULT", 'faultExtra' => "10,0" );
						// "Invalid entry for <em>Precision &amp; Scale</em>! Assuming default value ('<em>10,0</em>'). Re-submit the form!"
					}
				}
				break;

			case 'dropdown':
			case 'multiple':
				# MAXLENGTH: Processing (for DROPDOWN, MULTIPLE)...
				$dft_maxlength = $_dft_maxlength_for_dropdown_and_multiple;
				unset( $_dft_maxlength_for_dropdown_and_multiple );
				break;
		}

		# INPUT-REGEX: Validation...
		switch ( $dft_subtype )
		{
			case 'decimal_signed':
			case 'decimal_unsigned':
				$_dft_maxlength = explode( ",", $dft_maxlength );
				# INPUT-REGEX: Processing (for ALPHANUMERIC\DECIMAL)...
				$dft_input_regex = sprintf( $_skel_subtype_node['input_regex'] , intval( $_dft_maxlength[0] - $_dft_maxlength[1] ) , $_dft_maxlength[1] );
				unset( $_dft_maxlength );
				break;

			case 'dropdown':
			case 'multiple':
				# INPUT-REGEX: Processing (for DROPDOWN)...
				$dft_input_regex = sprintf( $_skel_subtype_node['input_regex'], $_dft_regex_for_dropdown_multiple );
				break;
		}
		if ( ! isset( $dft_input_regex ) )
		{
			$dft_input_regex = ( $_skel_subtype_node['input_regex'] !== false )
				?
				sprintf( $_skel_subtype_node['input_regex'], $dft_maxlength )
				:
				false;
		}

		# REQUEST-REGEX: Validation...
		switch ( $dft_subtype )
		{
			case 'string':
				if ( $dft_maxlength > 255 )
				{
					# REQUEST-REGEX: Processing (for ALPHANUMERIC\STRING)...
					$dft_request_regex = null;
				}
				break;

			case 'decimal_signed':
			case 'decimal_unsigned':
				$_dft_maxlength = explode( ",", $dft_maxlength );
				# REQUEST-REGEX: Processing (for ALPHANUMERIC\DECIMAL)...
				$dft_request_regex = sprintf( $_skel_subtype_node['request_regex'] , intval( $_dft_maxlength[0] - $_dft_maxlength[1] ) , $_dft_maxlength[1] );
				unset( $_dft_maxlength );
				break;

			case 'dropdown':
			case 'multiple':
				# REQUEST-REGEX: Processing (for DROPDOWN)...
				$dft_request_regex = sprintf( $_skel_subtype_node['request_regex'], $_dft_regex_for_dropdown_multiple, $_dft_regex_for_dropdown_multiple );
				unset( $_dft_regex_for_dropdown_multiple );
				break;
		}

		if ( ! isset( $dft_request_regex ) )
		{
			$dft_request_regex = ( $_skel_subtype_node['request_regex'] !== false )
				?
				sprintf( $_skel_subtype_node['request_regex'], $dft_maxlength )
				:
				false;
		}

		# DEFAULT VALUE: Validation...
		$dft_default_value = $_skel_subtype_node['default_value'];

		switch ( $dft_subtype )
		{
			case 'string':
				if ( ! empty( $input['default_value'] ) )
				{
					if ( $dft_maxlength > 255 )
					{
						$faults[] = array( 'faultCode' => 710, 'faultMessage' => "DEFAULT_VALUE__STRING__BIGSTRING_DETECTED" );
						// "255+ character-long '<em>General String</em>' subtype-field cannot have a default value! Leave <em>Default Value</em> field empty!"
					}
					elseif ( $dft_maxlength <= 255 and strlen( $input['default_value'] ) > $dft_maxlength )
					{
						$faults[] = array( 'faultCode' => 710, 'faultMessage' => "DEFAULT_VALUE__STRING__IS_LONGER_THAN_MAXLENGTH" );
						// "A " . $dft_maxlength . "-character-long field cannot have a '<em>Default Value</em>' with " . strlen( $input['default_value'] ) . " characters! Change either '<em>Default Value</em>' or '<em>Field Max-Length</em>' setting! Also be aware of multi-byte characters!"
					}
					# DEFAULT VALUE: Processing (for ALPHANUMERIC\STRING)...
					$dft_default_value = $input['default_value'];
				}
				break;

			case 'integer_signed_8':
			case 'integer_unsigned_8':
			case 'integer_signed_16':
			case 'integer_unsigned_16':
			case 'integer_signed_24':
			case 'integer_unsigned_24':
			case 'integer_signed_32':
			case 'integer_unsigned_32':
			case 'integer_signed_64':
			case 'integer_unsigned_64':
				if ( ! empty( $input['default_value'] ) )
				{
					if ( ! preg_match( '#^' . $dft_input_regex . '$#', $input['default_value'] ) )
					{
						$faults[] = array( 'faultCode' => 710, 'faultMessage' => "DEFAULT_VALUE__INTEGER_UNSIGNED__INVALID_ENTRY" );
						// "Invalid entry for <em>Default Value</em>: Entry needs to be numeric; and positive, if data-subtype is an unsigned integer! And it cannot violate the range ('<em>" . $_skel_subtype_node['min_value'] . "/" . $_skel_subtype_node['max_value'] ."</em>') of selected data-subtype!"
					}
					else
					{
						if ( $input['default_value'] < $_skel_subtype_node['min_value'] or $input['default_value'] > $_skel_subtype_node['max_value'] )
						{
							$faults[] = array( 'faultCode' => 710, 'faultMessage' => "DEFAULT_VALUE__INTEGER__OUT_OF_RANGE" );
							// "Invalid entry for <em>Default Value</em>: Value beyond the range ('<em>" . $_skel_subtype_node['min_value'] . "/" . $_skel_subtype_node['max_value'] ."</em>') of select data-subtype!"
						}
						# DEFAULT VALUE: Processing (for ALPHANUMERIC\INTEGER)...
						$dft_default_value = intval( $input['default_value'] );
					}
				}
				break;

			case 'decimal_signed':
			case 'decimal_unsigned':
				if ( ! empty( $input['default_value'] ) )
				{
					if ( $dft_subtype == 'decimal_unsigned' and $input['default_value'] < 0 )
					{
						$faults[] = array( 'faultCode' => 710, 'faultMessage' => "DEFAULT_VALUE__NUMERIC_UNSIGNED__ENTRY_IS_SIGNED" );
						// "Unsigned data-types cannot have a negative <em>Default Value</em>s!"
					}
					if ( ! preg_match( '#^' . $dft_input_regex . '$#', $input['default_value'] ) )
					{
						$faults[] = array( 'faultCode' => 710, 'faultMessage' => "DEFAULT_VALUE__DECIMAL__COMPLIANCE_WITH_PRECISION_SCALE_FAILURE" );
						// "<em>Default Value</em> does not comply with <em>Precision &amp; Scale</em> setting! Fix either one."
					}
					# DEFAULT VALUE: Processing (for ALPHANUMERIC\DECIMAL)...
					$dft_default_value = floatVal( $input['default_value'] );
				}
				break;

			case 'dropdown':
			case 'multiple':
				if ( ! empty( $input['default_value'] ) )
				{
					if ( preg_match_all( '#^(?P<first>[a-z0-9_]+)(?:,(?P<rest>[a-z0-9_]+))*$#i' , $input['default_value'] , $_values ) )
					{
						if ( empty( $_values['rest'][0] ) )
						{
							unset( $_values['rest'][0] );
						}
						if ( $dft_subtype == 'dropdown' and count( $_values['rest'] ) )
						{
							$faults[] = array( 'faultCode' => 710, 'faultMessage' => "DEFAULT_VALUE__DROPDOWN__MORE_THAN_ONE_VALUES_PROVIDED" );
							// "<em>Default Value</em>: 'Preset Single Select [Dropdown or Radio]' data-type can have only one default value!"
						}
						if ( ! array_key_exists( $_values['first'][0], $dft_default_options ) )
						{
							$faults[] = array( 'faultCode' => 710, 'faultMessage' => "DEFAULT_VALUE__DROPDOWN__OUT_OF_RANGE" );
							// "<em>Default Value</em>: One or more default values could not be found among the values provided in default options!"
						}
						if ( count( $_values['rest'] ) )
						{
							foreach ( $_values['rest'] as $_value )
							{
								if ( ! array_key_exists( $_value, $dft_default_options ) )
								{
									$faults[] = array( 'faultCode' => 710, 'faultMessage' => "DEFAULT_VALUE__DROPDOWN__OUT_OF_RANGE" );
								}
							}
						}
						# DEFAULT VALUE: Processing (for DROPDOWN, MULTIPLE)...
						$dft_default_value = $input['default_value'];
					}
					else
					{
						$faults[] = array( 'faultCode' => 710, 'faultMessage' => "DEFAULT_VALUE__DROPDOWN__OUT_OF_RANGE" );
					}
				}
				break;
		}

		# CONNECTORS & MAX_NR_OF_ITEMS: Processing...
		$dft_connector_enabled = ( isset( $input['connector_enabled'] ) )
			?
			( $input['connector_enabled'] ? 1 : 0 )
			:
			null;
		$dft_connector_length_cap = ( isset( $input['connector_length_cap'] ) )
			?
			( $dft_connector_enabled ? intval( $input['connector_length_cap'] ) : null )
			:
			null;
		switch ( $dft_subtype )
		{
			case 'dropdown':
			case 'multiple':
				$dft_connector_enabled = ( is_null( $dft_connector_enabled ) ) ? null : 0;
				$dft_connector_length_cap = ( is_null( $dft_connector_length_cap ) ) ? null : $_skel_subtype_node['connector_length_cap'];
				break;
		}

		# UNIQUE-NESS: Processing...
		$dft_is_unique = ( isset( $input['is_unique'] ) and $input['is_unique'] ) ? 1 : 0;
		switch ( $dft_subtype )
		{
			case 'dropdown':
			case 'multiple':
				$dft_is_unique = $_skel_subtype_node['is_unique'];
				break;
		}

		# HTML-ALLOWED: Processing...
		$dft_is_html_allowed = 0;
		switch ( $dft_subtype )
		{
			case 'string':
				if ( $dft_maxlength > 255 )
				{
					$dft_is_html_allowed = $input['is_html_allowed'] ? 1 : 0;
				}
				break;
		}

		//-----------
		// Errors?
		//-----------

		if ( count( $faults ) )
		{
			return $faults;
		}

		//--------------------------------------------------------------
		// Still here? Continue...
		// Updating Module-records and Altering Module content-tables
		//--------------------------------------------------------------

		$ddl_config__validated = array_merge(
				$ddl_config__validated,
				array(
						'm_unique_id'          =>  $m['m_unique_id'],
						'type'                 =>  "alphanumeric",
						'subtype'              =>  $dft_subtype,
						'maxlength'            =>  $dft_maxlength,
						'input_regex'          =>  $dft_input_regex,
						'request_regex'        =>  $dft_request_regex,
						'default_options'      =>  serialize( $dft_default_options ),
						'default_value'        =>  $dft_default_value,
						'connector_enabled'    =>  $dft_connector_enabled,
						'connector_length_cap' =>  $dft_connector_length_cap,
						'is_html_allowed'      =>  $dft_is_html_allowed,
						'is_unique'            =>  $dft_is_unique,
						'is_numeric'           =>  $_skel_subtype_node['is_numeric']
					)
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
		switch ( $ddl_information['subtype'] )
		{
			case 'string':
				if ( $ddl_information['maxlength'] > 255 || $ddl_information['connector_enabled'] )
				{
					return false;
				}
				break;

			case 'dropdown':
			case 'multiple':
				return false;
				break;
		}

		return true;
	}
}