<?php

if ( ! defined( "INIT_DONE" ) )
{
	print "Improper access! Exiting now...";
	exit();
}

/**
 * Data-sources > RDBMS
 *
 * @package  Audith CMS codename Persephone
 * @author   Shahriyar Imanov <shehi@imanov.name>
 * @version  1.0
 */

require_once( PATH_SOURCES . "/kernel/data_sources.php" );

class Data_Sources__Rdbms extends Data_Sources
{
	/**
	 * List of alowed Data-targets for Rdbms-data-source
	 *
	 * @var array
	 */
	private $allowed_data_targets = array(
			'rdbms' => "",
			'tpl'   => ""
		);


	/**
	 * Contructor
	 * @param    Registry     Registry object reference
	 */
	public function __construct ( Registry $Registry )
	{
		parent::__construct( $Registry );
	}

	/**
	 * Creates a new Module Subroutine
	 *
	 * @param    array   Parsed subroutine-configuration
	 * @param    array   Incoming subroutine-configuration
	 * @return   array   Array containing status code pairs (either responseCode-responseMessage (on SUCCESS); or faultCodes-faultMessages (otherwise) )
	 */
	public function modules__subroutines__do_validate ( &$subroutine , &$input )
	{
		$faults = array();
		$m =& $this->Registry->Cache->cache['modules']['by_unique_id'][ $input['m_unique_id'] ];

		//-------------------------------------------------------------
		// DDL Information - Only required if Data-source is RDBMS.
		//-------------------------------------------------------------

		if ( !isset( $input['s_data_definition'] ) or !count( $input['s_data_definition'] ) )
		{
			$faults[] = array( 'faultCode' => 700, 'faultMessage' => "At least 1 (one) <em>data source</em> must be selected!" );
		}
		else
		{
			foreach ( $input['s_data_definition'] as $_field )
			{
				$subroutine['s_data_definition'][ $_field ] = array( 'name' => $_field );
			}
		}

		// $subroutine['s_data_definition'] = null;

		//-------------------
		// Subroutine Name
		//-------------------

		$input['s_name'] = strtolower( $input['s_name'] );
		if ( $input['s_name'] )
		{
			if ( !preg_match( '#^[a-z][a-z0-9_]{0,31}$#' , $input['s_name'] ) )
			{
				$faults[] = array( 'faultCode' => 701, 'faultMessage' => "<em>Subroutine name</em> syntax error - it must start with a letter and may contain only alphanumeric characters!" );
			}
			if ( array_key_exists( $input['s_name'] , $m['m_subroutines'] ) )
			{
				$faults[] = array( 'faultCode' => 701, 'faultMessage' => "<em>Subroutine name</em> is not available!" );
			}
		}
		else
		{
			$faults[] = array( 'faultCode' => 701, 'faultMessage' => "<em>Subroutine name</em> is a required field!" );
		}
		$subroutine['s_name'] = $input['s_name'];

		//------------------------
		// Path-info URI Schema
		//------------------------

		# Building list of fields and their respective request-regex
		$_list_of_usable_fields = array( "{id}" => '\d{1,10}' , "{timestamp}" => '\d{1,10}' , "{submitted_by}" => '\d{1,8}' );
		$_list_of_unusable_fields = array();
		foreach ( $m['m_data_definition'] as $_field_name=>$_field_data )
		{
			# Connector-enabled fields ...
			if ( $_field_data['connector_enabled'] )
			{
				if ( $_field_data['connector_linked'] )
				{
					foreach ( $_field_data['c_data_definition'] as $__field_name=>$__field_data )
					{
						# Certain fields are not eligible to exist inside URI-Schema (identified by non-existent request-regex)
						if ( !$__field_data['request_regex'] or empty( $__field_data['request_regex'] ) )
						{
							$_list_of_unusable_fields[ '{' . $_field_name . "." . $__field_name . '}' ] = false;
							continue;
						}
						$_list_of_usable_fields[ '{' . $_field_name . "." . $__field_name . '}' ] = $__field_data['request_regex'];
					}
				}
				else
				{
					# Adding both the standalone and the dot-version field, to prevent admin misuse or mistake
					$_list_of_unusable_fields[ '{' . $_field_name . '}' ] = false;
					$_list_of_unusable_fields[ '{' . $_field_name . "." . $_field_name . '}' ] = false;
				}
			}
			else
			{
				# ... and the rest
				if ( !$_field_data['request_regex'] or empty( $_field_data['request_regex'] ) )
				{
					# Certain fields are not eligible to exist inside URI-Schema (identified by non-existent request-regex)
					$_list_of_unusable_fields[ '{' . $_field_name . '}' ] = false;
					continue;
				}
				$_list_of_usable_fields[ '{' . $_field_name . '}' ] = $_field_data['request_regex'];
			}
		}

		# Path-Info : Character fix
		$_convert_from  = array( "&gt;" , "&lt;" , "&#33;" );
		$_convert_to    = array( ">"    , "<"    , "!"     );
		$subroutine['s_pathinfo_uri_schema'] = str_replace( $_convert_from, $_convert_to, $input['s_pathinfo_uri_schema'] );

		if ( $subroutine['s_pathinfo_uri_schema'] )
		{
			# Make sure UPDATE and INSERT preset schemas are not used
			// @todo Remove or enhance this control
			/*
			if ( preg_match( '#^(?:update|insert)\-#i' , $subroutine['s_pathinfo_uri_schema'] ) )
			{
				$faults[] = array( 'faultCode' => 702, 'faultMessage' => "<em>Path Info - URI Schema</em>: Reserved pattern detected! You cannot re-use patterns reserved for preset '<em>update</em>' and '<em>insert</em>' subroutines! Change the schema." );
			}
			*/

			# The dash (-) character is quoted in PHP 5.3.0 and later, so put it at the beginning of the list
			if ( !preg_match( '#^[a-z0-9' . preg_quote('-.,\+/*?[](){}=!<>|:_') . ']+$#i' , $subroutine['s_pathinfo_uri_schema'] ) )
			{
				$faults[] = array( 'faultCode' => 702, 'faultMessage' => "<em>Path Info - URI Schema</em> may only contain alphanumeric characters, plus any of the following: <strong>. , \ + / * ? [ ^ ] $ ( ) { } = ! < > | : -</strong>" );
			}

			# Any leading or trailing slashes?
			if ( preg_match( '#^\/+#' , $subroutine['s_pathinfo_uri_schema'] ) or preg_match( '#\/+$#' , $subroutine['s_pathinfo_uri_schema'] ) )
			{
				$faults[] = array( 'faultCode' => 702, 'faultMessage' => "Remove all leading and trailing slashes from within <em>Path Info - URI Schema</em>!" );
			}

			# Do parentheses match?
			if ( $this->Registry->Input->check_enclosing_parentheses( $subroutine['s_pathinfo_uri_schema'] ) === false )
			{
				$faults[] = array( 'faultCode' => 702, 'faultMessage' => "Parentheses within <em>Path Info - URI Schema</em> do not match!" );
			}
			else
			{
				//----------------------------------
				// Confirm Path-Info RegEx works
				//----------------------------------

				# Cleanup
				$_s_pathinfo_uri_schema = str_replace( array_keys( $_list_of_usable_fields ) , "" , $subroutine['s_pathinfo_uri_schema'] );

				# Validate
				if ( @preg_match_all( "#" . $_s_pathinfo_uri_schema . "#", "", $_ ) === false )
				{
					$faults[] = array( 'faultCode' => 702, 'faultMessage' => "<em>Path Info - URI Schema</em>: Invalid RegEx syntax detected!" );
				}

				//-------------------------------------------------
				// Named Backreferences : Lets parse their names
				//-------------------------------------------------

				if ( preg_match_all( '#(?<=\(\?P\<)[a-z][a-zA-Z0-9_]*(?=\>)#' , $subroutine['s_pathinfo_uri_schema'] , $_named_backreferences ) )
				{
					$_invalid_named_backreferences = array();
					foreach ( $_named_backreferences[0] as $_refs )
					{
						if ( array_key_exists( "{".$_refs."}", $_list_of_usable_fields ) )
						{
							$_invalid_named_backreferences[] = $_refs;
						}
					}
					if ( count( $_invalid_named_backreferences ) )
					{
						$faults[] = array( 'faultCode' => 702, 'faultMessage' => "<em>Path Info - URI Schema</em> - Following custom-references are already in use by field-references:<br />&nbsp;&nbsp;&nbsp;<i>" . implode( ", " , $_invalid_named_backreferences ) . "</i>" );
					}
					unset( $_invalid_named_backreferences );
				}
			}
		}
		else
		{
			$faults[] = array( 'faultCode' => 702, 'faultMessage' => "<em>Path Info - URI Schema</em> is a required field!" );
		}

		//---------------------------------
		// Path-info URI Schema [parsed]
		//---------------------------------

		# OCCURING FIELDS : Detect occuring fields
		preg_match_all( '#\{([a-z][.a-z0-9_]*)\}#' , $subroutine['s_pathinfo_uri_schema'] , $_field_shortcuts_occuring_in_uri_schema );

		# OCCURING + CAPTURED FIELDS : Detect captured (!) occuring fields
		preg_match_all( '#\(\{([a-z][.a-z0-9_]*)\}\)#' , $subroutine['s_pathinfo_uri_schema'] , $_field_backreferences );

		# CAPTURED CUSTOM VALUES
		preg_match_all( '#\((?<!\{)[^)]*\)#u' , $subroutine['s_pathinfo_uri_schema'] , $_captured_custom_values );

		# Lets check which occuring fields are invalid
		$_occuring_invalid_fields  = array();                             // Invalid fields - those that do not exist
		$_occuring_unusable_fields = array();                             // Unusable fields - those which aren't allowed inside Path-Info
		for ( $i = 0 ; $i < count( $_field_shortcuts_occuring_in_uri_schema[0] ) ; $i++ )
		{
			# We have the field-name in our list of valid field names, so skip it (no problems here)...
			if ( array_key_exists( $_field_shortcuts_occuring_in_uri_schema[0][ $i ], $_list_of_usable_fields ) )
			{
				continue;
			}

			# Either there is no such field-name (wrong-string), or its request-regex is FALSE (field can't be used in SCHEMA)
			if ( array_key_exists( '{' . $_field_shortcuts_occuring_in_uri_schema[1][ $i ] . '}' , $_list_of_unusable_fields ) )
			{
				$_occuring_unusable_fields[] = $_field_shortcuts_occuring_in_uri_schema[1][ $i ];
			}
			elseif ( !array_key_exists( '{' . $_field_shortcuts_occuring_in_uri_schema[1][ $i ] . '}' , $_list_of_unusable_fields ) and !array_key_exists( '{' . $_field_shortcuts_occuring_in_uri_schema[1][ $i ] . '}' , $_list_of_usable_fields ) )
			{
				$_occuring_invalid_fields[] = $_field_shortcuts_occuring_in_uri_schema[1][ $i ];
			}
		}

		if ( count( $_occuring_unusable_fields ) )
		{
			$faults[] = array( 'faultCode' => 702, 'faultMessage' => "Following field-names cannot be used inside URI-Schema (usually, because of their extreme sizes, or their types) - remove them:<br />&nbsp;&nbsp;&nbsp;<em>" . implode( ", " , $_occuring_unusable_fields ) . "</em>" );
			unset( $_occuring_unusable_fields );
		}

		if ( count( $_occuring_invalid_fields ) )
		{
			$faults[] = array( 'faultCode' => 702, 'faultMessage' => "Invalid field-names detected inside URI-Schema, which do not exist - remove them:<br />&nbsp;&nbsp;&nbsp;<em>" . implode( ", " , $_occuring_invalid_fields ) . "</em>" );
			unset( $_occuring_invalid_fields );
		}

		# Still here? Continue...
		$subroutine['s_pathinfo_uri_schema_parsed'] = $subroutine['s_pathinfo_uri_schema'];
		while ( list( $_field_name , $_field_request_regex ) = each( $_list_of_usable_fields ) )
		{
			// Handling capturable version of the data-field
			$subroutine['s_pathinfo_uri_schema_parsed'] =
				preg_replace(
					"#\(" . preg_quote( $_field_name ) . "\)#",
					"(?P<"
						. substr( $_field_name, 1, strlen( $_field_name ) - 2 )      // Getting rid of curly parantheses in a fastest way possible
						. ">" . $_field_request_regex . ")",
					$subroutine['s_pathinfo_uri_schema_parsed']
				);
			// Handling the standard version of the data-field
			$subroutine['s_pathinfo_uri_schema_parsed'] = preg_replace( "#" . preg_quote( $_field_name ) . "#" , $_field_request_regex , $subroutine['s_pathinfo_uri_schema_parsed'] );
		}
		$subroutine['s_pathinfo_uri_schema_parsed'] = str_replace( "/" , '\/' , $subroutine['s_pathinfo_uri_schema_parsed'] );

		//-----------------------
		// Q-String Parameters
		//-----------------------

		$subroutine['s_qstring_parameters'] = array();
		if ( isset( $_field_backreferences[1] ) )
		{
			foreach ( $_field_backreferences[1] as $_ref )
			{
				$subroutine['s_qstring_parameters'][ $_ref ] = array(
						'request_regex'      =>  $_list_of_usable_fields[ "{" . $_ref . "}" ],
						'_is_mandatory'      =>  true
					);
			}
		}
		if ( isset( $_named_backreferences[0] ) )
		{
			foreach ( $_named_backreferences[0] as $_ref )
			{
				$subroutine['s_qstring_parameters'][ $_ref ] = array(
						'request_regex'      =>  $_list_of_usable_fields[ "{" . $_ref . "}" ],
						'_is_mandatory'      =>  true
					);
			}
		}

		//------------------------------------------------------
		// Fetch Criteria - Queries & Query Groups (Policies)
		//------------------------------------------------------

		$subroutine['s_fetch_criteria'] = array();
		if ( $input['s_fetch_criteria__all_or_selected'] == 'selected' )
		{
			$subroutine['s_fetch_criteria']['do_fetch_all_or_selected'] = 'selected';

			//----------------------------
			// Fetch Criteria - Queries
			//----------------------------

			$subroutine['s_fetch_criteria']['rules'] = array();
			$_list_of_broken_rules = array();
			$_i = 0;
			foreach ( $input['s_fetch_criteria']['rules'] as $_rule )
			{
				//-----------------
				// Empty Queries
				//-----------------

				if ( !$_rule['value'] )
				{
					if ( $_rule['math_operator'] != 'IS NULL' and $_rule['math_operator'] != 'IS NOT NULL' )
					{
						$faults[] = array( 'faultCode' => "705-" . $_i, 'faultMessage' => "Missing value in <em>Fetch Criteria - Queries &amp; Query Groups (Policies)</em> fieldset! Fill-in or remove the query set." );
						$_list_of_broken_rules[ $_i ] = true;
						$_i++;
						continue;
					}
				}

				//----------------------------
				// Validation of References
				//----------------------------

				if ( preg_match_all( '/(?<!&#36;)&#36;([a-zA-Z][a-zA-Z0-9_]+)/' , $_rule['value'] , $_references_occuring_in_query_value ) )
				{
					$_noncaptured_field_backreferences_in_query  = array();          // Field-references which are not captured [Fault reporting]
					$_invalid_field_backreferences_in_query      = array();          // Field-references which do not exist at all [Fault reporting]
					$_noncaptured_custom_backreferences_in_query = array();          // Custom-references which are not captured [Fault reporting]

					# Let's mark all passing references
					foreach ( $_references_occuring_in_query_value[1] as $_reference )
					{
						# Captured reference is not numeric - it gotta be field-name reference.
						# Each field name can only be paired with its own reference! Let's make sure of this...
						if ( $_reference != $_rule['field_name'] and in_array( $_reference, $_field_backreferences[1] ) )
						{
							$faults[] = array( 'faultCode' => "705-" . $_i, 'faultMessage' => "Following invalid data-field reference(s) detected in <em>Fetch Criteria - Queries &amp; Query Groups (Policies)</em> fieldset:<br />&nbsp;&nbsp;&nbsp;<em>\$" . $_reference . "</em><br />A data-field can only use its own captured reference (in this case: <em>" . $_rule['field_name'] . "</em> data-field can only use captured <em>\$" . $_rule['field_name'] . "</em>)" );
							$_list_of_broken_rules[ $_i ] = true;
							$_i++;
							continue;
						}

						# ... and let's make sure these references do get captured in Path-Info ...
						if ( in_array( $_reference, $_field_backreferences[1] ) or in_array( $_reference, $_named_backreferences[0] ) )
						{
							$_rule['value'] = preg_replace( '/(?<!&#36;)&#36;((?>' . $_reference . '))/' , "<backreference>\\1</backreference>" , $_rule['value'] );
						}
						elseif ( !in_array( $_reference, $_field_backreferences[1] ) and in_array( $_reference, $_field_shortcuts_occuring_in_uri_schema[1] ) )
						{
							$_noncaptured_field_backreferences_in_query[ $_reference ] = "\$" . $_reference;
						}
						else
						{
							$_invalid_field_backreferences_in_query[ $_reference ] = "\$" . $_reference;
						}
					}

					if ( count( $_noncaptured_field_backreferences_in_query ) )
					{
						$faults[] = array( 'faultCode' => "705-" . $_i, 'faultMessage' => "Following data-fields are not captured, thus cannot be referenced to in <em>Fetch Criteria - Queries &amp; Query Groups (Policies)</em> fieldset - either escape or fix these references:<br />&nbsp;&nbsp;&nbsp;<em>" . implode( ", " , $_noncaptured_field_backreferences_in_query ) . "</em>" );
						$_list_of_broken_rules[ $_i ] = true;
						$_i++;
						continue;
					}

					if ( count( $_invalid_field_backreferences_in_query ) )
					{
						$faults[] = array( 'faultCode' => "705-" . $_i, 'faultMessage' => "Following invalid data-field reference(s) detected in <em>Fetch Criteria - Queries &amp; Query Groups (Policies)</em> fieldset:<br />&nbsp;&nbsp;&nbsp;<em>" . implode( ", " , $_invalid_field_backreferences_in_query ) . "</em><br />Either escape appropriate $ signs; or remove invalid references; or update your URI-Schema with missing captures." );
						$_list_of_broken_rules[ $_i ] = true;
						$_i++;
						continue;
					}
				}

				# ... and finally, one small insurance policy - we can't have <backreference /> tag or its closing pair as a part of rule-value
				if ( preg_match( '#&lt;\/?backreference&gt;#' , $_rule['value'] ) )
				{
					$faults[] = array( 'faultCode' => "705-" . $_i, 'faultMessage' => "The tag &lt;backreference&gt; alongside its closing counterpart, is a reserved phrase and cannot be used anywhere inside <em>Fetch Criteria - Queries &amp; Query Groups (Policies)</em> fieldset! Remove any occurrances of it." );
					$_list_of_broken_rules[ $_i ] = true;
					$_i++;
				}


				# Un-$-escape the remaining df-reference-alikes considering them as a regular string
				$_rule['value'] = preg_replace( '/(?<=&#36;)&#36;((?>[a-z][a-z0-9_]+))/' , "\\1" , $_rule['value'] );

				//--------------------------------------------------------------------
				// Checking parentheses in queries belonging to numeric data-fields
				//--------------------------------------------------------------------

				if ( isset( $m['m_data_definition'][ $_rule['field_name'] ] ) )
				{
					if ( $m['m_data_definition'][ $_rule['field_name'] ]['is_numeric'] == 1 and $_rule['type_of_expr_in_value'] != 'math' and $_rule['type_of_expr_in_value'] != 'zend_db_expr' )
					{
						$faults[] = array( 'faultCode' => "709-" . $_i, 'faultMessage' => "'<em>Generic Value</em>' expression-type does not work in pair with numeric fields; select either '<em>Mathematical Value</em>' or '<em>Zend_Db_Expr</em>'!" );
					}
					else
					{
						if ( !$this->Registry->Input->check_enclosing_parentheses( $_rule['value'] ) )
						{
							$faults[] = array( 'faultCode' => "705-" . $_i, 'faultMessage' => "Opening and closing parentheses do not match inside <em>Fetch Criteria - Queries &amp; Query Groups (Policies)</em> fieldset! Queries related to numeric data-fields require this criteria!" );
							$_list_of_broken_rules[ $_i ] = true;
							$_i++;
						}
					}
				}
				elseif ( in_array( $_rule['field_name'], array( "id", "timestamp", "submitted_by" ) ) )
				{
					if ( $_rule['type_of_expr_in_value'] != 'math' and $_rule['type_of_expr_in_value'] != 'zend_db_expr' )
					{
						$faults[] = array( 'faultCode' => "709-" . $_i, 'faultMessage' => "'<em>Generic Value</em>' expression-type does not work in pair with numeric fields; select either '<em>Mathematical Value</em>' or '<em>Zend_Db_Expr</em>'!" );
					}
				}

				# Raw
				$subroutine['s_fetch_criteria']['rules'][] = array(
						'field_name'             => $_rule['field_name'],
						'math_operator'          => $_rule['math_operator'],
						'type_of_expr_in_value'  => $_rule['type_of_expr_in_value'],
						'value'                  => $_rule['value']
					);

				$_i++;
			}
			unset( $_rule );

			//--------------------------------------------
			// Fetch Criteria - Query Groups [Policies]
			//--------------------------------------------

			$subroutine['s_fetch_criteria']['policies'] = array();
			$_i = 0;
			foreach ( $input['s_fetch_criteria']['policies'] as $_policy )
			{
				# Do parantheses match?
				$_operators = '(?:\s(?:OR|XOR|AND|NOT)\s)?';
				$pattern = array( '/\s\s+/' , '/\(\s/' , '/\s\)/' );
				$replacement = array( " " , "(" , ")" );
				$_policy = strtoupper( "(" . preg_replace( $pattern , $replacement , $_policy ) . ")" );
				preg_match_all(
					'/
					\(
						(?>
							(?>
								(?>
									(?: \d* | (?R) )
									' . $_operators . '
									(?> \d+ | (?R) )
								)
								|
								(?R)
							)*
						)
					\)
					/xi' , $_policy , $_parentheses_check_matches );
				if ( $_policy != @$_parentheses_check_matches[0][0] )
				{
					$faults[] = array( 'faultCode' => "704-" . $_i, 'faultMessage' => "Syntax Error in <em>Fetch Criteria - Queries &amp; Query Groups (Policies)</em> fieldset!" );
					$_i++;
					continue;
				}

				# Are all policy shortcuts of queries valid?
				preg_match_all( '#(?>\d+)#' , $_policy , $_shortcut_matches );
				$_invalid_shortcuts = array();
				foreach ( $_shortcut_matches[0] as $_match )
				{
					if ( array_key_exists( intval( $_match ) - 1 , $_list_of_broken_rules ) )
					{
						$_invalid_shortcuts[] = "<i>" . $_match . "</i>";
					}

					if ( intval( $_match ) > count( $input['s_fetch_criteria']['rules'] ) )
					{
						$_invalid_shortcuts[] = "<i>" . $_match . "</i>";
					}
				}
				$_invalid_shortcuts = implode( ", " , $_invalid_shortcuts );
				if ( $_invalid_shortcuts )
				{
					$faults[] = array( 'faultCode' => "704-" . $_i, 'faultMessage' => "Invalid shortcuts (" . $_invalid_shortcuts . ") detected within Query Policy " . ( $_i + 1 ) . " in <em>Fetch Criteria - Queries &amp; Query Groups (Policies)</em> fieldset!" );
					$_i++;
					continue;
				}
				unset( $_invalid_shortcuts );

				# Raw
				$subroutine['s_fetch_criteria']['policies'][] = $_policy;

				$_i++;
			}
		}
		elseif ( $input['s_fetch_criteria__all_or_selected'] == 'all' )
		{
			$subroutine['s_fetch_criteria']['do_fetch_all_or_selected'] = 'all';
		}

		//--------------------------
		// Fetch Criteria - Limit
		//--------------------------

		if ( !preg_match( '/^\d*$/' , $input['s_fetch_criteria']['limit'] ) )
		{
			$faults[] = array( 'faultCode' => 706, 'faultMessage' => "<em>Fetch Criteria - Limit</em> field accepts only numeric values!" );
		}
		else
		{
			if ( isset( $input['s_fetch_criteria']['limit'] ) and !empty( $input['s_fetch_criteria']['limit'] ) )
			{
				$subroutine['s_fetch_criteria']['limit'] = intval( $input['s_fetch_criteria']['limit'] );
			}
			else
			{
				$subroutine['s_fetch_criteria']['limit'] = null;
			}
		}

		//-------------------------------
		// Fetch Criteria - Pagination
		//-------------------------------

		if ( !preg_match( '/^\d*$/' , $input['s_fetch_criteria']['pagination'] ) )
		{
			$faults[] = array( 'faultCode' => 707, 'faultMessage' => "<em>Fetch Criteria - Pagination</em> field accepts only numeric values!" );
		}
		else
		{
			if ( isset( $input['s_fetch_criteria']['pagination'] ) and !empty( $input['s_fetch_criteria']['pagination'] ) )
			{
				$subroutine['s_fetch_criteria']['pagination'] = intval( $input['s_fetch_criteria']['pagination'] );
			}
			else
			{
				$subroutine['s_fetch_criteria']['pagination'] = null;
			}
		}

		//----------------------------
		// Fetch Criteria - Sorting
		//----------------------------

		$subroutine['s_fetch_criteria']['do_sort'] = $input['s_fetch_criteria__do_perform_sorting'] ? 1 : 0;
		if ( $subroutine['s_fetch_criteria']['do_sort'] )
		{
			$_fields_sorted_already = array();
			$_i = 0;
			foreach ( $input['s_fetch_criteria']['sort_by'] as $_sort_criteria )
			{
				if ( !in_array( $_sort_criteria['field_name'] , $_fields_sorted_already ) )
				{
					$subroutine['s_fetch_criteria']['sort_by'][] = array(
							'field_name'  =>  $_sort_criteria['field_name'],
							'dir'         =>  strtoupper( $_sort_criteria['dir'] )
						);
					$_fields_sorted_already[] = $_sort_criteria['field_name'];
				}
				else
				{
					$faults[] = array( 'faultCode' => "708-" . $_i, 'faultMessage' => "One or more duplicate field-names detected in <em>Fetch Criteria - Sorting</em> fieldset! Remove those..." );
				}
				$_i++;
			}
		}

		return $faults;
	}
}