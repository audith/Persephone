<?php

if ( ! defined( "INIT_DONE" ) )
{
	print "Improper access! Exiting now...";
	exit();
}

/**
 * MODULES : Main processor
 *
 * @package  Audith CMS codename Persephone
 * @author   Shahriyar Imanov <shehi@imanov.name>
 * @version  1.0
**/
class Modules
{
	/**
	 * Registry reference
	 * @var Registry
	**/
	private $Registry;

	/**
	 * Array of active modules
	 * @var array
	**/
	public $active_modules = array();

	/**
	 * Current working module
	 * @var string
	**/
	public $cur_module = array();

	/**
	 * Data-storage (for modules) handlers
	 * @var array
	 */
	public $data_storages = array();

	/**
	 * Default DFT (data-field types)
	 * @var array
	**/
	public $dft_default = array();

	/**
	 * Custom data-field types
	 * @var array
	**/
	public $dft_custom = array();


	/**
	 * Constructor
	 *
	 * @param    object    Registry Object Reference
	 * @param    array     $params  Incoming data
	 */
	public function __construct ( Registry $Registry, $params = "" )
	{
		$this->Registry = $Registry;

		# Get active modules from DB
		$this->active_modules = $this->modules__fetch_active();

		# Load requested module
		$this->modules__fetch_working();

		/**
		 * SITE_URL constant
		 */
		if ( ! defined( "SITE_URL" ) )
		{
			$_connection_type = $this->cur_module['m_enforce_ssl'] ? "https" : "http";
			define( "SITE_URL", $_connection_type . "://" . $this->Registry->config['url']['hostname'][ $_connection_type ] );
		}
	}


	/**
	 * Destructor
	 */
	public function _my_destruct ()
	{
		$this->Registry->logger__do_log( __CLASS__ . "::__destruct: Destroying class" , "INFO" );
	}


	/**
	 * Init()
	 *
	 * @return void
	 */
	public function init ()
	{
		# Load current module
		$this->modules__do_load( $this->cur_module );
		$this->Registry->config['page']['running_subroutine'] =& $this->cur_module['running_subroutine'];
	}


	/**
	 * __toString()
	 *
	 * @return    string    Dump of current module
	 */
	public function __toString ()
	{
		// print_r( $this->cur_module );
		return var_export( $this->cur_module, TRUE );
	}


	/**
	 * Build content-based HTTP headers, mostly cache and content-info related (using module-dependency procedures)
	 *
	 * @param   object   REF: Module data object
	 * @return  array    Array of HTTP Headers to be sent
	 */
	public function content__build_http_headers ( &$m )
	{
		# Default values
		$headers = array (
				"content-type"     => "text/html; charset=utf-8",
				// Works with HTTP/1.1 only!
				"cache-control"    => $m['m_enable_caching']
					?
					"public, must-revalidate, max-age=2592000, pre-check=2592000"
					:
					"no-store, no-cache, must-revalidate, max-age=0",
				"pragma"           => $m['m_enable_caching']
					?
					""
					:
					"no-cache",
				"expires"          => $m['m_enable_caching'] ? gmdate( "r", time() + 2592000 ) : gmdate( "r", time() - 86400 ),
				# @todo Localization support
				"content-language" => "en-us"
			);

		# Built-in modules might have their own customized headers
		if ( $m['m_type'] == 'built-in' )
		{
			if ( method_exists( $m['han'], "content__build_http_headers" ) )
			{
				$headers = array_merge( $headers, $m['han']->content__build_http_headers() );
			}
		}

		# Return
		return $headers;
	}


	/**
	 * GET processor - wrapper for data-storage handler
	 *
	 * @param   array    REF: Module info
	 * @return  mixed    (mixed) Fetched content on success; (boolean) FALSE otherwise
	 */
	public function content__do ( &$m )
	{
		//---------------
		// Continue...
		//---------------

		$action = "";
		if ( filter_has_var( INPUT_GET, "do" ) or filter_has_var( INPUT_POST, "do" ) )  // For security reasons, let's check original GET and POST data
		{
			$action = preg_replace( '#[^a-z_-]#' , "" , $this->Registry->Input->request("do") );
		}
		if ( !$action )
		{
			$action = "get";
		}

		//---------------------------------------------------------------
		// RUNNING_SUBROUTINE - SERVICE_MODE : If ='write-only',
		// we don't need to fetch any content. Return NULL...
		//---------------------------------------------------------------

		if ( isset( $m['running_subroutine'] ) and $m['running_subroutine']['s_data_source'] == 'no-fetch' and $action == 'get' )
		{
			return null;
		}

		if ( isset( $m['running_subroutine'] ) )
		{
			# Built-in modules do have Handlers, so facilitate those, if we are on built-in module
			if ( isset( $m['han'] ) and is_object( $m['han'] ) )
			{
				return $m['han']->content__do( $m['running_subroutine'] , $action );
			}
			# ... otherwise, for regular modules, process the request...
			else
			{
				if ( $action == 'get' )
				{
					return $this->content__do_process( $m );
				}
				elseif ( $action == 'put' )
				{

				}
				elseif ( $action == 'edit' )
				{

				}
			}
		}

		return false;
	}


	/**
	 * GET - Fetches all the content requested, according to the Subroutine fetch-criteria
	 *
	 * @param   array    Reference: Module info
	 * @return  mixed    (mixed) Fetched content on success; (boolean) FALSE otherwise
	 *
	 * @todo   404 not found situation is not properly implemented
	 * @todo   Page number isn't implemented into $_cache_key_name__* variables
	 */
	public function content__do_process ( &$m )
	{
		$_cache_key_name__subroutines__content_for =
			"subroutines__content_for__"
			. md5(
					$m['m_unique_id']
					. $m['running_subroutine']['s_name']
					. $this->Registry->config['page']['request']['path']
					. $m['running_subroutine']['page_nr_requested']
				);

		$_cache_key_name__subroutines__keys_for =
			"subroutines__keys_for__"
			. md5(
					$m['m_unique_id']
					. $m['running_subroutine']['s_name']
					. $this->Registry->config['page']['request']['path']
				);

		if
		(
			! $m['running_subroutine']['content']['parsed'] = $this->Registry->Cache->cache__do_get( $_cache_key_name__subroutines__content_for , TRUE )
			or
			! $_cache_of_keys_for_the_result_set = $this->Registry->Cache->cache__do_get( $_cache_key_name__subroutines__keys_for , TRUE )
		)
		{
			//-----------------------------------------------------------------------------------------------
			// EXPLANATION FOR THIS 'IF':
			//   Here we populate two things:
			//     a) list of tables for the Joins of the final SQL-clause, which fetches the content;
			//     b) list of fields which are used to fetch a content from.
			//   If data-target is 'tpl' we use subroutine-data-definition to accomplish this task,
			//   otherwise we have to iterate through whole module-data-definition and build our lists
			//   (mostly because 'update' subroutine doesn't have subroutine-data-definition set).
			//-----------------------------------------------------------------------------------------------

			if ( $m['running_subroutine']['s_data_target'] == 'rdbms' )
			{
				$_fields_to_fetch = array( "id", "tags", "timestamp", "submitted_by", "status_published", "status_locked" );
				$s_data_definition_for_join = array();

				if ( is_array( $m['m_data_definition'] ) and count( $m['m_data_definition'] ) )
				{
					foreach ( $m['m_data_definition'] as $_k => $_v )
					{
						if ( $_v['connector_enabled'] )
						{
							if ( empty( $_v['connector_linked'] ) )
							{
								continue;
							}

							# Connector-unit : 'name' only for Joins
							$s_data_definition_for_join[] = $_k;

							# Connector-unit : 'id' and c-data-definition
							$_fields_to_fetch[ $_k . ".id" ] = $_k . ".id";

							foreach ( $_v['c_data_definition'] as $__k => $__v )
							{
								$_fields_to_fetch[ $_k . "." . $__k ] = $_k . "." . $__k;
							}
						}
						else
						{
							$_fields_to_fetch[] = $_k;
						}
					}
				}

				$return['content'] = $this->content__do_process__by_ref_id( $m , $m['running_subroutine']['request']['id'] , $_fields_to_fetch , FALSE );
				$return['m_data_definition'] = $m['m_data_definition'];
				return $return;
			}
			elseif ( $m['running_subroutine']['s_data_target'] == 'tpl' )
			{
				$_fields_to_fetch = array_merge( array( "id", "tags", "timestamp", "submitted_by", "status_published", "status_locked" ) , $s_data_definition_for_join = array_keys( $m['running_subroutine']['s_data_definition'] ) );

				# Add column name references for connector-enabled fields
				foreach ( $_fields_to_fetch as $_k => $_f )
				{
					//------------------------------
					// Connector-enabled fields
					//------------------------------

					if ( strpos( $_f, "." ) !== FALSE )
					{
						unset( $_fields_to_fetch[ $_k ] );
						$_fields_to_fetch[ $_f ] = $_f;                             // Index needs to be a string, as it will serve as a table alias in a Join-clause

						# Connector-unit's 'id' column
						$_tmp = explode( "." , $_f );
						$_fields_to_fetch[ $_tmp[0] . ".id" ] = $_tmp[0] . ".id";
					}
				}

				# Separate Connector-enabled fields for JOINS
				$_tmp = array();
				foreach ( $s_data_definition_for_join as $key=>&$_column )          // Let only Connector-enabled-fields remain
				{
					if ( strpos( $_column , "." ) !== FALSE )                       // Is it Connector-enabled?
					{
						// @todo Needs to be tested
						// list( $_column, ) = explode( "." , $_column );
						$_tmp = explode( "." , $_column );
						$_column = $_tmp[0];                                        // It is; updating $s_data_definition_for_join
						continue;
					}
					unset( $s_data_definition_for_join[ $key ] );                   // It is not; remove the value frmo $s_data_definition_for_join
				}
				$s_data_definition_for_join = array_unique( $s_data_definition_for_join );
				unset( $_tmp );                                                     // Small garbage
			}

			//---------------------------------------------
			// Parse running subroutine's fetch criteria
			//---------------------------------------------

			$this->subroutines__fetch_criteria__do_parse( $m );

			//-----------------------------
			// Prepare final fetch query
			//-----------------------------

			if
			(
				isset( $m['running_subroutine']['s_fetch_criteria_parsed']['policies'] )
				and
				count( $m['running_subroutine']['s_fetch_criteria_parsed']['policies'] )
			)
			{
				$_subqueries = array();
				foreach ( $m['running_subroutine']['s_fetch_criteria_parsed']['policies'] as $_policy )
				{
					# Merging $s_data_definition_for_join with policy's list-of-connector-tables
					$_list_of_tables_to_process = array_unique( array_merge( $s_data_definition_for_join, $_policy['_list_of_connector_tables'] ) );

					# Continue...
					$_connector_joins = array();
					if ( ! empty( $_list_of_tables_to_process ) )
					{
						foreach ( $_list_of_tables_to_process as $_table_alias )
						{
							$_attach_prefix_reference_makes_us_do_this = "mod_" . $m['m_unique_id_clean'] . "_conn_repo__" . $_table_alias;
							$_connector_joins[] = array(
									array( $_table_alias => $this->Registry->Db->attach_prefix( $_attach_prefix_reference_makes_us_do_this ) ),
									$this->Registry->Db->db->quoteIdentifier( "master.id" )
										. " = "
										. $this->Registry->Db->db->quoteIdentifier( $_table_alias . ".ref_id" ),
									array(),                                        // No list of columns to fetch, we place that in WHERE clause
								);
						}
					}

					$_attach_prefix_reference_makes_us_do_this = "mod_" . $m['m_unique_id_clean'] . "_master_repo";
					$_subquery = $this->Registry->Db->db
						->select()
						->from(
								array( 'master' => $this->Registry->Db->attach_prefix( $_attach_prefix_reference_makes_us_do_this ) ),
								$_fields_to_fetch
							);

					foreach ( $_connector_joins as $_j )
					{
						$_subquery = $_subquery->joinLeft( $_j[0] , $_j[1] , $_j[2] );
					}

					# + Where
					$_subquery = $_subquery->where( $_policy['parsed_policy'] );

					$_subqueries[] = "(" . $_subquery . ")";
				}
			}
			else
			{
				# We have no policy rules...
				if ( ! empty( $s_data_definition_for_join ) )
				{
					$_connector_joins = array();
					foreach ( $s_data_definition_for_join as $_table_alias )
					{
						$_attach_prefix_reference_makes_us_do_this = "mod_" . $m['m_unique_id_clean'] . "_conn_repo__" . $_table_alias;
						$_connector_joins[] = array(
								array( $_table_alias => $this->Registry->Db->attach_prefix( $_attach_prefix_reference_makes_us_do_this ) ),
								$this->Registry->Db->db->quoteIdentifier( "master.id" )
									. " = "
									. $this->Registry->Db->db->quoteIdentifier( $_table_alias . ".ref_id" ),
								array(),                                            // No list of columns to fetch, we place that in WHERE clause
							);
					}
				}

				$_attach_prefix_reference_makes_us_do_this = "mod_" . $m['m_unique_id_clean'] . "_master_repo";
				$_subquery = $this->Registry->Db->db
					->select()
					->from(
							array( 'master' => $this->Registry->Db->attach_prefix( $_attach_prefix_reference_makes_us_do_this ) ),
							$_fields_to_fetch
						);

				if ( isset( $_connector_joins ) and count( $_connector_joins ) )
				{
					foreach ( $_connector_joins as $_j )
					{
						$_subquery = $_subquery->joinLeft( $_j[0] , $_j[1] , $_j[2] );
					}
				}

				# + Where
				// Since we don't have any query policies, we don't have WHERE as well
				// $_subquery = $_subquery->where( $_policy['parsed_policy'] );

				$_subqueries[] = "(" . $_subquery . ")";
			}

			# + UNION
			$_base_query = $this->Registry->Db->db->select()->union( $_subqueries );

			# + ORDER
			if ( count( $m['running_subroutine']['s_fetch_criteria_parsed']['sort_by'] ) )
			{
				$_base_query->order( $m['running_subroutine']['s_fetch_criteria_parsed']['sort_by'] );
			}

			//--------------------------------------------------------------------------------------
			// Execute final fetch query [without LIMIT clause] and cache the total number fetched
			//--------------------------------------------------------------------------------------

			$_cache_key_name__subroutines__keys_for =
				"subroutines__keys_for__"
				. md5(
						$m['m_unique_id']
						. $m['running_subroutine']['s_name']
						. $this->Registry->config['page']['request']['path']
					);

			if (
				! $_cache_of_keys_for_the_result_set =
					$this->Registry->Cache->cache__do_get(
							$_cache_key_name__subroutines__keys_for,
							TRUE
						)
			)
			{
				$this->Registry->Db->cur_query = $this->Registry->Db->db
					->select()
					->from(
							new Zend_Db_Expr( "( " . strval( $_base_query ) . " )" ),
							array(
									'list'  => new Zend_Db_Expr( "DISTINCT( " . $this->Registry->Db->db->quoteIdentifier( "id" ) . " )" ),
									// 'count' => new Zend_Db_Expr( "COUNT( DISTINCT( " . $this->Registry->Db->db->quoteIdentifier( "id" ) . " ) )" ),
								)
						);
				try
				{
					$_r = $this->Registry->Db->db->query( $this->Registry->Db->cur_query )->fetchAll();
					$this->Registry->Db->query_count++;
				}
				catch ( Zend_Db_Exception $e )
				{
					$this->Registry->Db->exception_handler( $e );
					return false;
				}

				$_cache_of_keys_for_the_result_set = array();
				foreach ( $_r as $_i )
				{
					$_cache_of_keys_for_the_result_set[] = $_i['list'];
				}

				$this->Registry->Cache->cache__do_update(
						array(
								'name'    => $_cache_key_name__subroutines__keys_for,
								'value'   => $_cache_of_keys_for_the_result_set,
								'array'   => 1
							)
					);
			}

			//-----------------------------------------------------------------------------
			// Execute final fetch query [with LIMIT clause] and retrieve requested data
			//-----------------------------------------------------------------------------

			# + LIMIT
			$_keys_to_fetch = "";
			if ( count( $m['running_subroutine']['s_fetch_criteria_parsed']['limit'] ) == 2 )
			{
				# We only need the data associated with the key-interval defined by LIMIT and PAGINATION settings of subroutine-fetch-rule
				$_keys_to_fetch = implode(
						", ",
						array_map(
								create_function(
										'$value' ,
										'return $GLOBALS["Registry"]->Db->quote( $value , "INTEGER" );'
									),
								array_slice(
										$_cache_of_keys_for_the_result_set,
										$m['running_subroutine']['s_fetch_criteria_parsed']['limit']['offset'],
										$m['running_subroutine']['s_fetch_criteria_parsed']['limit']['count']
									)
							)
					);
			}
			else
			{
				# No LIMIT or PAGINATION rules avail, so fetch the data associated with all keys.
				$_keys_to_fetch = implode(
						", ",
						array_map(
								create_function(
										'$value' ,
										'return $GLOBALS["Registry"]->Db->quote( $value , "INTEGER" );'
									),
								$_cache_of_keys_for_the_result_set
							)
					);
			}

			# Append WHERE clause which emulated LIMIT and PAGINATION + ORDER clause
			$this->Registry->Db->cur_query = $this->Registry->Db->db
				->select()
				->from(
						new Zend_Db_Expr( "( " . strval( $_base_query ) . " )" )
					);
			if ( ! empty( $_keys_to_fetch ) )
			{
				$this->Registry->Db->cur_query->where(  $this->Registry->Db->db->quoteIdentifier( "id" ) . " IN ( " . $_keys_to_fetch . " )" );
			}
			else
			{
				# No content in this page? Redirect to page 1 then...
				if ( ! $m['running_subroutine']['content']['count'] and $m['running_subroutine']['page_nr_requested'] > 1 )
				{
					$this->Registry->http_redirect( $this->Registry->config['page']['request']['scheme'] . '://' . $this->Registry->config['page']['request']['host'] . $this->Registry->config['page']['request']['path'] );
				}
			}

			try
			{
				$result = $this->Registry->Db->db->query( $this->Registry->Db->cur_query )->fetchAll();
				$this->Registry->Db->query_count++;
			}
			catch ( Zend_Db_Exception $e )
			{
				$this->Registry->Db->exception_handler( $e );
				return false;
			}

			//-----------------------------------------------------------
			// No results?! If so, don't process further - just return
			//-----------------------------------------------------------

			if ( ! count( $result ) )
			{
				$m['running_subroutine']['content']['raw'] = null;
				$return = $m['running_subroutine']['content']['parsed'] = null;
				return $return;
			}

			$m['running_subroutine']['content']['raw'] = $result;

			//---------------------------------------------------------
			// Run fetched data through Data-Processors and parse it
			//---------------------------------------------------------

			foreach ( $m['running_subroutine']['content']['raw'] as $_raw_content )
			{
				if ( ! isset( $m['running_subroutine']['content']['parsed'][ $_raw_content['id'] ] ) )
				{
					$m['running_subroutine']['content']['parsed'][ $_raw_content['id'] ] = array();
				}
				$_node_reference =& $m['running_subroutine']['content']['parsed'][ $_raw_content['id'] ];

				# First, process preset columns
				$this->subroutines__preset_columns__do_parse( $m , $_node_reference , $_raw_content );

				# Continue...
				foreach ( $_raw_content as $_field_name=>$_field_content )
				{
					//------------------------------------------------------------------
					// We have field-name, lets check the data-type it belongs to...
					//------------------------------------------------------------------

					# Continue...
					if ( array_key_exists( $_field_name , $m['running_subroutine']['s_data_definition'] ) )
					{
						# Data-processor instance
						$_data_type = $m['running_subroutine']['s_data_definition'][ $_field_name ]['type'];
						$_processor_instance = $this->Registry->loader( "Data_Processors__" . ucwords( $_data_type ) );

						//-------------------------------
						// Organize content container
						//-------------------------------

						if ( ( $_strpos_dot_in_field_name = strpos( $_field_name , "." ) ) !== FALSE )
						{
							$_field_name_exploded = explode( "." , $_field_name );

							//-------------------------------------------------------------------------------------------------------
							// Why am I doing this? - Simply because, in PHP, variable names can't have dashes (-) in them,
							// and we can't put underscore (_) instead, because it may cause a confusion within our system.
							// Putting anything else would frustrate SMARTY template designers, so we go Java style:
							// we capitalize indices, e.g.: body.body becomes bodyBody
							//-------------------------------------------------------------------------------------------------------

							$_new_field_name = str_replace( "." , "" , $_field_name );
							$_new_field_name[ $_strpos_dot_in_field_name ] = strtoupper( $_new_field_name[ $_strpos_dot_in_field_name ] );

							if ( ! is_null( $_field_content ) )
							{
								$_node_reference
									[ $_new_field_name ]
									[ $_raw_content[ $_field_name_exploded[0] . '.id' ] ]
									= $_processor_instance->get__do_process(
											array(
													'field'    => &$m['running_subroutine']['s_data_definition'][ $_field_name ],
													'content'  => $_field_content
												)
										);
							}
						}
						else
						{
							if ( ! is_null( $_field_content ) )
							{
								$_node_reference[ $_field_name ] = $_processor_instance->get__do_process(
										array(
												'field'    => &$m['running_subroutine']['s_data_definition'][ $_field_name ],
												'content'  => $_field_content
											)
									);
							}
						}
					}
				}
			}

			//---------------------
			// Cache the content
			//---------------------

			$this->Registry->Cache->cache__do_update(
					array(
							'name'    => $_cache_key_name__subroutines__content_for,
							'value'   => $return['content'] = $m['running_subroutine']['content']['parsed'],
							'array'   => 1
						)
				);
		}

		if ( $m['running_subroutine']['s_data_target'] == 'rdbms' )
		{
			$return['m_data_definition'] = $m['m_data_definition'];
		}
		else
		{
			$return['content'] = $m['running_subroutine']['content']['parsed'];
		}

		return $return;
	}


	/**
	 * Fetches a single row of data from any module
	 *
	 * @param   array     Module info
	 * @param   integer   Row 'id'
	 * @param   array     DFD, to fetch from
	 * @return  mixed     Single-row content from module specified on success, FALSE otherwise
	 */
	public function content__do_process__by_ref_id ( &$m , $ref_id , $fields_to_fetch = array() , $_callback_via_data_processor = TRUE )
	{
		$_fields_to_fetch = array_merge( array( "id", "tags", "timestamp", "submitted_by", "status_published", "status_locked" ) , $fields_to_fetch );

		# Add column name references for connector-enabled fields
		foreach ( $_fields_to_fetch as $_k => $_f )
		{
			//------------------------------
			// Connector-enabled fields
			//------------------------------

			if ( strpos( $_f , "." ) !== FALSE )
			{
				unset( $_fields_to_fetch[ $_k ] );
				$_fields_to_fetch[ $_f ] = $_f;

				# We also need connector-unit's 'id' column
				$_tmp = explode( "." , $_f );
				$_fields_to_fetch[ $_tmp[0] . ".id" ] = $_tmp[0] . ".id";
			}
		}

		# IMPORTANT NOTE: Don't process links of Links :) Possible recursion might occur!

		# Separate Connector-enabled fields for JOINS
		foreach ( $fields_to_fetch as $key=>&$_column )                             // Let only Connector-enabled-fields remain
		{
			if ( strpos( $_column , "." ) !== FALSE )                               // Is it connector-enabled?
			{
				$_tmp = explode( "." , $_column );
				$_column = $_tmp[0];                                                // It is; updating $s_data_definition_for_join
				continue;
			}
			unset( $fields_to_fetch[ $key ] );                                      // It is not: remove the value frmo $s_data_definition_for_join
		}
		$fields_to_fetch = array_unique( $fields_to_fetch );

		//-----------------------------
		// Prepare final fetch query
		//-----------------------------

		# We have no policy rules...
		$_connector_joins = array();
		if ( ! empty( $fields_to_fetch ) )
		{
			foreach ( $fields_to_fetch as $_table_alias )
			{
				$_attach_prefix_reference_makes_us_do_this = "mod_" . $m['m_unique_id_clean'] . "_conn_repo__" . $_table_alias;
				$_connector_joins[] = array(
						array( $_table_alias => $this->Registry->Db->attach_prefix( $_attach_prefix_reference_makes_us_do_this ) ),
						$this->Registry->Db->db->quoteIdentifier( "master.id" )
							. " = "
							. $this->Registry->Db->db->quoteIdentifier( $_table_alias . ".ref_id" ),
						array(),  // No list of columns to fetch, we place that in WHERE clause
					);
			}
		}

		$_attach_prefix_reference_makes_us_do_this = "mod_" . $m['m_unique_id_clean'] . "_master_repo";
		$this->Registry->Db->cur_query = $this->Registry->Db->db
			->select()
			->from(
					array( 'master' => $this->Registry->Db->attach_prefix( $_attach_prefix_reference_makes_us_do_this ) ),
					$_fields_to_fetch
				);

		foreach ( $_connector_joins as $_j )
		{
			$this->Registry->Db->cur_query->joinLeft( $_j[0] , $_j[1] , $_j[2] );
		}

		# + Where
		$this->Registry->Db->cur_query->where( $this->Registry->Db->db->quoteIdentifier( "master.id" ) . "=" . $this->Registry->Db->db->quote( $ref_id , "INTEGER" ) );

		//---------------------------------------------------------
		// Execute final fetch query and retrieve requested data
		//---------------------------------------------------------

		try
		{
			$result = $this->Registry->Db->db->query( $this->Registry->Db->cur_query )->fetchAll();
			$this->Registry->Db->query_count++;
		}
		catch ( Zend_Db_Exception $e )
		{
			$this->Registry->Db->exception_handler( $e );
			return false;
		}

		//-----------------------------------------------------------
		// No results?! If so, don't process further - just return
		//-----------------------------------------------------------

		if ( ! count( $result ) )
		{
			$m['running_subroutine']['content']['raw'] = null;
			$return = $m['running_subroutine']['content']['parsed'] = null;
			return $return;
		}

		$m['running_subroutine']['content']['raw'] = $result;

		//---------------------------------------------------------
		// Run fetched data through Data-Processors and parse it
		//---------------------------------------------------------

		foreach ( $m['running_subroutine']['content']['raw'] as $_raw_content )
		{
			if ( ! isset( $m['running_subroutine']['content']['parsed'] ) )
			{
				$m['running_subroutine']['content']['parsed'] = array();
			}
			$_node_reference =& $m['running_subroutine']['content']['parsed'];

			# First, process preset columns
			$this->subroutines__preset_columns__do_parse( $m , $_node_reference , $_raw_content );

			# Continue...
			foreach ( $_raw_content as $_field_name=>$_field_content )
			{
				//------------------------------------------------------------------
				// We have field-name, lets check the data-type it belongs to...
				//------------------------------------------------------------------

				$_field_node = false;

				# Is it a connector-enabled field?
				if ( ( $_field_name_is_connector_enabled = strpos( $_field_name , "." ) ) !== FALSE )
				{
					$_field_name_exploded = explode( "." , $_field_name );
					if
					(
						array_key_exists( $_field_name_exploded[0] , $m['m_data_definition'] )
						and
						$m['m_data_definition'][ $_field_name_exploded[0] ]['connector_enabled']
						and
						isset( $m['m_data_definition'][ $_field_name_exploded[0] ]['c_data_definition'][ $_field_name_exploded[1] ] )
					)
					{
						$_field_node = $m['m_data_definition'][ $_field_name_exploded[0] ]['c_data_definition'][ $_field_name_exploded[1] ];
					}
				}
				else
				{
					if ( array_key_exists( $_field_name , $m['m_data_definition'] ) )
					{
						$_field_node = $m['m_data_definition'][ $_field_name ];
					}
				}

				// We don't have $m['running_subroutine']['s_data_definition'], so let's improvize
				if ( $_field_node )
				{
					# Data-processor instance:
					# Link-types are not allowed if the method has been called from within a data-processor - because they are possible recursion causes!
					if ( $_field_node['type'] == 'link' and $_callback_via_data_processor )
					{
						continue;
					}
					$_processor_instance = $this->Registry->loader( "Data_Processors__" . ucwords( $_field_node['type'] ) );

					//-------------------------------
					// Organize content container
					//-------------------------------

					if ( $_field_name_is_connector_enabled !== FALSE )
					{
						//-------------------------------------------------------------------------------------------------------
						// Why am I doing this? - Simply because, in PHP, variable names can't have dashes (-) in them,
						// and we can't put underscore (_) instead, because it may cause a confusion within our system.
						// Putting anything else would frustrate SMARTY template designers, so we go Java style:
						// we capitalize indices, e.g.: body.body becomes bodyBody
						//-------------------------------------------------------------------------------------------------------

						$_new_field_name = str_replace( "." , "" , $_field_name );
						$_new_field_name[ $_field_name_is_connector_enabled ] = strtoupper( $_new_field_name[ $_field_name_is_connector_enabled ] );

						if ( ! is_null( $_field_content ) )
						{
							$_node_reference
								[ $_new_field_name ]
								[ $_raw_content[ $_field_name_exploded[0] . '.id' ] ]
								= $_processor_instance->get__do_process(
										array(
												'field'    => &$_field_node,
												'content'  => $_field_content
											)
									);
						}
					}
					else
					{
						if ( ! is_null( $_field_content ) )
						{
							$_node_reference[ $_field_name ] = $_processor_instance->get__do_process(
									array(
											'field'    => &$_field_node,
											'content'  => $_field_content
										)
								);
						}
					}
				}
			}
		}

		return $m['running_subroutine']['content']['parsed'];
	}


	/**
	 * Loads a module
	 *
	 * @param     array    REF: Module info - usually comes from Modules-cache
	 * @return    mixed    Loaded-module instance on success, FALSE otherwise
	 */
	public function modules__do_load ( &$m )
	{
		# Load Handler Class of Built-in-Module

		if ( $m['m_type'] == 'built-in' )
		{
			# Do we have Handler Class file?
			$_han_class_lib = PATH_SOURCES . "/handlers/" . $m['m_handler_class'];
			if ( @is_file( $_han_class_lib ) and @is_readable( $_han_class_lib ) )
			{
				# Fetch ACP Handler
				require_once( $_han_class_lib );

				$m['han'] = new Module_Handler( $this->Registry );
				$m['m_subroutines'] =& $m['han']->structural_map['m_subroutines'];
			}
			else
			{
				throw new Exception( "Couldn't find/load Module Handler Class Library!" );
			}
		}

		//-------------------
		// Continue ...
		//-------------------

		# No module information?!
		if ( ! count( $m ) )
		{
			return false;
		}

		# Is it admin area?
		if ( ! defined( "ACCESS_TO_AREA" ) )
    	{
    		define( "ACCESS_TO_AREA" , "public" );
    	}

		# Module URL prefix
		$_connection_type = $m['m_enforce_ssl'] ? "https" : "http";
		$m['m_url_prefix'] = $_connection_type . "://" . $this->Registry->config['url']['hostname'][ $_connection_type ] . "/" . $m['m_name'];

		//------------------------------------------------------------------------------------
		// Process SUBROUTINES and determine the working one and the request coming with it
		//------------------------------------------------------------------------------------

		$this->subroutines__do_process( $m );
		$m['_is_loaded'] = 1;

		return $m;
	}


	/**
	 * Get active modules from DB
	 *
	 * @return  array  Array of active (enabled) modules
	 */
	private function modules__fetch_active ()
	{
		if ( isset( $this->Registry->Cache->cache['modules']['by_name'] ) and is_array( $this->Registry->Cache->cache['modules']['by_name'] ) and count( $this->Registry->Cache->cache['modules']['by_name'] ) )
		{
			foreach ( $this->Registry->Cache->cache['modules']['by_name'] as $m_name=>$m_data )
			{
				if ( $m_data['m_is_enabled'] == 1 or IN_DEV )
				{
					$a_m[ $m_name ] = $m_data;
				}
			}

			return $a_m;

		}
		else
		{
			# No active modules.
			return null;
		}
	}


	/**
	 * Get currently working module
	 *
	 * @return   boolean   TRUE on success, FALSE otherwise
	 */
	private function modules__fetch_working ()
	{
		# Checking PATH_INFO
		$possible_module_name_from_path_info = $this->Registry->config['page']['request']['path_exploded'][0];

		# Do we have the requested module amongst our Active (enabled) modules?
		if ( array_key_exists( $possible_module_name_from_path_info, $this->active_modules ) )
		{
			$this->cur_module =& $this->active_modules[ $possible_module_name_from_path_info ];
			return true;
		}
		else
		{
			$this->cur_module = null;
			return false;
		}
	}


	/**
	 * Determines the running subroutine and processes the request parameters
	 *
	 * @param    array     REF: Module data
	 * @return   boolean   TRUE if there is a running subroutine, FALSE otherwise (meaning, we are on index page of module)
	 */
	private function subroutines__do_process ( &$m )
	{
		if ( ! isset( $m['m_subroutines'] ) or ! count( $m['m_subroutines'] ) )
		{
			return null;
		}

		# Continue...
		foreach ( $m['m_subroutines'] as $_s_name=>$_s_data )
		{
			if ( preg_match( '#^' . $_s_data['s_pathinfo_uri_schema_parsed'] . '$#i', str_replace( "/" . $m['m_name'] . "/" , "" , $this->Registry->config['page']['request']['path'] ) , $_match ) )
			{
				# Got it...
				$m['running_subroutine'] =& $m['m_subroutines'][ $_s_name ];
				foreach ( $_match as $_field_name => $_field_value )
				{
					if ( is_numeric( $_field_name ) )
					{
						continue;
					}
					$m['running_subroutine']['request'][ $_field_name ] = $_field_value;
				}

				# Page number
				$m['running_subroutine']['page_nr_requested'] =
					isset( $_GET['_page'][ $m['running_subroutine']['s_name'] ] )
					?
					intval( $_GET['_page'][ $m['running_subroutine']['s_name'] ] )
					:
					1;

				return true;
			}
		}

		# Still no results? Index it is, then...
		$m['running_subroutine'] = null;
		return false;
	}


/**
	 * Subroutines - Parses the fetch-criteria to a valid "WHERE" SQL-clause
	 *
	 * @param    array    Ref.: Module info
	 * @return   void
	 */
	private function subroutines__fetch_criteria__do_parse ( &$m )
	{
		//------------------------------------
		// Parsing Fetch Criteria - Queries
		//------------------------------------

		if ( isset( $m['running_subroutine']['s_fetch_criteria']['rules'] ) and count( $m['running_subroutine']['s_fetch_criteria']['rules'] ) )
		{
			foreach ( $m['running_subroutine']['s_fetch_criteria']['rules'] as $_rule )
			{
				$_rule__connector_tables = array();

				//----------------------------
				// List of connector-tables
				//----------------------------

				if ( strpos( $_rule['field_name'] , "." ) !== FALSE )
				{
					$_field_name_exploded = explode( "." , $_rule['field_name'] );
					$_rule__connector_tables[] = $_field_name_exploded[0];
					unset( $_field_name_exploded );
				}
				else
				{
					$_rule['field_name'] = "master." . $_rule['field_name'];
				}

				//------------------
				// Parsing VALUE
				//------------------

				# Let's first parse the value
				$GLOBALS['_tmp'] =& $m['running_subroutine']['request'];    // A way around... Otherwise Lambda-function will turn it to a nightmare
				$_rule['value'] = preg_replace_callback(
						'#<backreference>((?>[a-z][a-z0-9_]+))<\/backreference>#' ,
						// PHP 5.3.0 or later:
						//function( $matches ) {
						//		return $GLOBALS['_tmp'][ $matches[1] ];
						//	} ,
						create_function(
								'$matches' ,
								'return $GLOBALS["_tmp"][ $matches[1] ];'
							) ,
						$_rule['value']
					);
				unset( $GLOBALS['_tmp'] );

				//---------------
				// Continue...
				//---------------

				$_rule__parsed =
					$this->Registry->Db->db->quoteIdentifier( $_rule['field_name'] )
					. " "
					. html_entity_decode( $_rule['math_operator'] , ENT_COMPAT , "UTF-8" );

				if ( $_rule['math_operator'] != 'IS NULL' and $_rule['math_operator'] != 'IS NOT NULL' )
				{
					$_rule__parsed .= " ";

					isset( $m['m_data_definition'][ $_rule['field_name'] ]['subtype'] )
						?
						$_subtype =& $m['m_data_definition'][ $_rule['field_name'] ]['subtype']
						:
						$_subtype = null;

					if ( $_rule['type_of_expr_in_value'] == 'math' )
					{
						if
						(
							isset( $m['m_data_definition'][ $_rule['field_name'] ] )
							and
							$m['m_data_definition'][ $_rule['field_name'] ]['is_numeric'] === true
							and
							preg_match( '#^decimal_#' , $_subtype )
						)
						{
							# FLOAT/DECIMAL
							$_rule['value'] = $this->Registry->Input->clean__makesafe_mathematical( $_rule['value'], true );    // Cleanup
							$_rule__parsed .= $this->Registry->Db->quote( eval( "return " . $_rule['value'] . ";"), "FLOAT" );
						}
						else
						{
							# INTEGER
							$_rule['value'] = $this->Registry->Input->clean__makesafe_mathematical( $_rule['value'] );          // Cleanup
							$_rule__parsed .= $this->Registry->Db->quote( eval( "return " . $_rule['value'] . ";"), "INTEGER" );
						}
					}
					elseif ( $_rule['type_of_expr_in_value'] == 'zend_db_expr' )
					{
						# Zend_Db_Expr
						$_rule__parsed .= new Zend_Db_Expr( $_rule['value'] );
					}
					else
					{
						# GENERIC
						$_rule__parsed .= $this->Registry->Db->quote( $_rule['value'] );
					}
				}

				$m['running_subroutine']['s_fetch_criteria_parsed']['rules'][] = array(
						'_list_of_connector_tables' => $_rule__connector_tables,
						'parsed_rule'               => $_rule__parsed,
					);
			}
		}

		//-------------------------------------------
		// Parsing Fetch Criteria - Query Policies
		//-------------------------------------------

		if ( isset( $m['running_subroutine']['s_fetch_criteria']['policies'] ) and count( $m['running_subroutine']['s_fetch_criteria']['policies'] ) )
		{
			foreach ( $m['running_subroutine']['s_fetch_criteria']['policies'] as $_policy )
			{
				$GLOBALS['_tmp'] =& $m['running_subroutine']['s_fetch_criteria_parsed']['rules'];  // A way around... Otherwise Lambda-function will turn it to a nightmare

				$_list_of_connector_tables = array();
				preg_match_all( '#((?>\d+))#' , $_policy , $_list_of_queries_used_in_this_policy );
				$_list_of_queries_used_in_this_policy = $_list_of_queries_used_in_this_policy[0];  // Only need full-matches
				foreach ( $_list_of_queries_used_in_this_policy as $_query_shortcut )
				{
					if ( ! empty( $m['running_subroutine']['s_fetch_criteria_parsed']['rules'][ $_query_shortcut-1 ]['_list_of_connector_tables'] ) )
					{
						$_list_of_connector_tables = array_merge(
								$_list_of_connector_tables,
								$m['running_subroutine']['s_fetch_criteria_parsed']['rules'][ $_query_shortcut-1 ]['_list_of_connector_tables']
							);
					}
				}

				$_parsed_policy = preg_replace_callback(
						'#((?>\d+))#' ,
						// PHP 5.3.0 or later:
						//function( $matches ) {
						//		return $GLOBALS['_tmp'][ intval( $matches[0] )-1 ]['parsed_rule'];
						//	} ,

						create_function(
								'$matches' ,
								'return $GLOBALS["_tmp"][ intval( $matches[0] )-1 ]["parsed_rule"];'
							) ,
						$_policy
					);
				$m['running_subroutine']['s_fetch_criteria_parsed']['policies'][] = array(
						'_list_of_connector_tables' => array_unique( $_list_of_connector_tables ),  // Removing duplicate values
						'parsed_policy'             => $_parsed_policy,
					);
				unset( $GLOBALS['_tmp'] );
			}
		}

		//------------------------------------
		// Parsing Fetch Criteria - LIMIT
		//------------------------------------

		# Get page number
		$m['running_subroutine']['s_fetch_criteria_parsed']['limit'] = array();
		$_page_number = 1;
		if ( isset( $_GET['_page'][ $m['running_subroutine']['s_name'] ] ) )
		{
			if ( intval( $_GET['_page'][ $m['running_subroutine']['s_name'] ] ) > 0 )
			{
				$_page_number = intval( $_GET['_page'][ $m['running_subroutine']['s_name'] ] );
			}
			else
			{
				$this->Registry->http_redirect( $this->Registry->config['page']['request']['scheme'] . '://' . $this->Registry->config['page']['request']['host'] . $this->Registry->config['page']['request']['path'] , 301 );
			}

		}

		# Validate the page number against the LIMIT
		if ( $m['running_subroutine']['s_fetch_criteria']['limit'] )
		{
			if ( $m['running_subroutine']['s_fetch_criteria']['limit'] == 1 and $_page_number > 1 )
			{
				$this->Registry->http_redirect( $this->Registry->config['page']['request']['scheme'] . '://' . $this->Registry->config['page']['request']['host'] . $this->Registry->config['page']['request']['path'] , 301 );
			}
			if ( $m['running_subroutine']['s_fetch_criteria']['pagination'] )
			{
				if ( ( $_page_number - 1 ) * $m['running_subroutine']['s_fetch_criteria']['pagination'] > $m['running_subroutine']['s_fetch_criteria']['limit'] )
				{
					$this->Registry->http_redirect( $this->Registry->config['page']['request']['scheme'] . '://' . $this->Registry->config['page']['request']['host'] . $this->Registry->config['page']['request']['path'] , 302 );
				}
				$m['running_subroutine']['s_fetch_criteria_parsed']['limit']['offset'] = ( $_page_number - 1 ) * $m['running_subroutine']['s_fetch_criteria']['pagination'];
				$m['running_subroutine']['s_fetch_criteria_parsed']['limit']['count'] = $m['running_subroutine']['s_fetch_criteria']['pagination'];
			}
			else
			{
				$m['running_subroutine']['s_fetch_criteria_parsed']['limit']['offset'] = 0;
				$m['running_subroutine']['s_fetch_criteria_parsed']['limit']['count'] = $m['running_subroutine']['s_fetch_criteria']['limit'];
			}
		}
		else
		{
			if ( $m['running_subroutine']['s_fetch_criteria']['pagination'] )
			{
				# Continue...
				$m['running_subroutine']['s_fetch_criteria_parsed']['limit']['offset'] = ( $_page_number - 1 ) * $m['running_subroutine']['s_fetch_criteria']['pagination'];
				$m['running_subroutine']['s_fetch_criteria_parsed']['limit']['count'] = $m['running_subroutine']['s_fetch_criteria']['pagination'];
			}
		}

		//------------------------------------
		// Parsing Fetch Criteria - ORDER
		//------------------------------------

		$m['running_subroutine']['s_fetch_criteria_parsed']['sort_by'] = array();
		if ( $m['running_subroutine']['s_fetch_criteria']['do_sort'] )
		{
			foreach ( $m['running_subroutine']['s_fetch_criteria']['sort_by'] as $_sort_criteria )
			{
				$m['running_subroutine']['s_fetch_criteria_parsed']['sort_by'][] = $_sort_criteria['field_name'] . " " . $_sort_criteria['dir'];
			}
		}
	}


	/**
	 * Subroutines - Parser for processing preset module fields, such as 'id', 'tags' etc
	 * @param    array    REF: Module info
	 * @param    array    REF: Where to attach the parsed data
	 * @param    array    REF: Raw data to parse
	 * @return   void
	 */
	private function subroutines__preset_columns__do_parse ( &$m , &$node_reference , &$raw_content )
	{
		foreach ( $raw_content as $_field_name=>$_field_content )
		{
			switch ( $_field_name )
			{
				default:
					continue;
				case 'id':
					$node_reference['id'] = intval( $_field_content );
					break;
				case 'tags':
					# Do we have "Tags" feature enabled for this module?
					if ( in_array( "tags", $m['m_extras'] ) )
					{
						// @todo  - Tags
					}
					else
					{
						$node_reference['tags'] = null;
					}
					break;

				case 'timestamp':
					// @todo  - Time Date
					break;

				case 'submitted_by':
					// @todo  - Users
					break;

				case 'status_published':
					$node_reference['status_published'] = $_field_content ? 1 : 0;
					break;

				case 'status_locked':
					$node_reference['status_locked'] = $_field_content ? 1 : 0;
					break;
			}
		}
	}
}