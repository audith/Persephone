<?php

if ( ! defined( "INIT_DONE" ) )
{
	print "Improper access! Exiting now...";
	exit();
}

/**
 * Data-sources: RDBMS
 *
 * @package  Audith CMS codename Persephone
 * @author   Shahriyar Imanov <shehi@imanov.name>
 * @version  1.0
**/

require_once( dirname( __FILE__ ) . "/_abstraction.php" );

class Data_Sources__Rdbms extends Data_Sources
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
	 * GET - Fetches all the content requested, according to the Subroutine fetch-criteria
	 *
	 * @param   array    Reference: Module info
	 * @return  mixed    (mixed) Fetched content on success; (boolean) FALSE otherwise
	 *
	 * @todo   404 not found situation is not properly implemented
	 * @todo   Page number isn't implemented into $_cache_key_name__* variables
	 */
	public function get__do_process ( &$m )
	{
		$_cache_key_name__subroutines__content_for =
			"subroutines__content_for__"
			. md5(
					$m['m_unique_id']
					. $m['running_subroutine']['s_name']
					. $this->API->config['page']['request']['path']
					. $m['running_subroutine']['page_nr_requested']
				);

		$_cache_key_name__subroutines__keys_for =
			"subroutines__keys_for__"
			. md5(
					$m['m_unique_id']
					. $m['running_subroutine']['s_name']
					. $this->API->config['page']['request']['path']
				);

		if
		(
			! $m['running_subroutine']['content']['parsed'] = $this->API->Cache->cache__do_get( $_cache_key_name__subroutines__content_for , TRUE )
			or
			! $_cache_of_keys_for_the_result_set = $this->API->Cache->cache__do_get( $_cache_key_name__subroutines__keys_for , TRUE )
		)
		{
			//-----------------------------------------------------------------------------------------------
			// EXPLANATION FOR THIS 'IF':
			//   Here we populate two things:
			//     a) list of tables for the Joins of the final SQL-clause, which fetches the content;
			//     b) list of fields which are used to fetch a content from.
			//   If service-mode is 'read-only' we use subroutine-data-definition to accomplish this task,
			//   otherwise we have to iterate through whole module-data-definition and build our lists
			//   (mostly because 'update' subroutine doesn't have subroutine-data-definition set).
			//-----------------------------------------------------------------------------------------------

			if ( $m['running_subroutine']['s_service_mode'] == 'read-write' )
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

				$return['content'] = $this->get__do_process__by_ref_id( $m , $m['running_subroutine']['request']['id'] , $_fields_to_fetch , FALSE );
				$return['m_data_definition'] = $m['m_data_definition'];
				return $return;
			}
			elseif ( $m['running_subroutine']['s_service_mode'] == 'read-only' )
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
									array( $_table_alias => $this->API->Db->attach_prefix( $_attach_prefix_reference_makes_us_do_this ) ),
									$this->API->Db->db->quoteIdentifier( "master.id" )
										. " = "
										. $this->API->Db->db->quoteIdentifier( $_table_alias . ".ref_id" ),
									array(),                                        // No list of columns to fetch, we place that in WHERE clause
								);
						}
					}

					$_attach_prefix_reference_makes_us_do_this = "mod_" . $m['m_unique_id_clean'] . "_master_repo";
					$_subquery = $this->API->Db->db
						->select()
						->from(
								array( 'master' => $this->API->Db->attach_prefix( $_attach_prefix_reference_makes_us_do_this ) ),
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
								array( $_table_alias => $this->API->Db->attach_prefix( $_attach_prefix_reference_makes_us_do_this ) ),
								$this->API->Db->db->quoteIdentifier( "master.id" )
									. " = "
									. $this->API->Db->db->quoteIdentifier( $_table_alias . ".ref_id" ),
								array(),                                            // No list of columns to fetch, we place that in WHERE clause
							);
					}
				}

				$_attach_prefix_reference_makes_us_do_this = "mod_" . $m['m_unique_id_clean'] . "_master_repo";
				$_subquery = $this->API->Db->db
					->select()
					->from(
							array( 'master' => $this->API->Db->attach_prefix( $_attach_prefix_reference_makes_us_do_this ) ),
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
			$_base_query = $this->API->Db->db->select()->union( $_subqueries );

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
						. $this->API->config['page']['request']['path']
					);

			if (
				! $_cache_of_keys_for_the_result_set =
					$this->API->Cache->cache__do_get(
							$_cache_key_name__subroutines__keys_for,
							TRUE
						)
			)
			{
				$this->API->Db->cur_query = $this->API->Db->db
					->select()
					->from(
							new Zend_Db_Expr( "( " . strval( $_base_query ) . " )" ),
							array(
									'list'  => new Zend_Db_Expr( "DISTINCT( " . $this->API->Db->db->quoteIdentifier( "id" ) . " )" ),
									// 'count' => new Zend_Db_Expr( "COUNT( DISTINCT( " . $this->API->Db->db->quoteIdentifier( "id" ) . " ) )" ),
								)
						);
				try
				{
					$_r = $this->API->Db->db->query( $this->API->Db->cur_query )->fetchAll();
					$this->API->Db->query_count++;
				}
				catch ( Zend_Db_Exception $e )
				{
					$this->API->Db->exception_handler( $e );
					return FALSE;
				}

				$_cache_of_keys_for_the_result_set = array();
				foreach ( $_r as $_i )
				{
					$_cache_of_keys_for_the_result_set[] = $_i['list'];
				}

				$this->API->Cache->cache__do_update(
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
										'return $GLOBALS["API"]->Db->quote( $value , "INTEGER" );'
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
										'return $GLOBALS["API"]->Db->quote( $value , "INTEGER" );'
									),
								$_cache_of_keys_for_the_result_set
							)
					);
			}

			# Append WHERE clause which emulated LIMIT and PAGINATION + ORDER clause
			$this->API->Db->cur_query = $this->API->Db->db
				->select()
				->from(
						new Zend_Db_Expr( "( " . strval( $_base_query ) . " )" )
					);
			if ( ! empty( $_keys_to_fetch ) )
			{
				$this->API->Db->cur_query->where(  $this->API->Db->db->quoteIdentifier( "id" ) . " IN ( " . $_keys_to_fetch . " )" );
			}
			else
			{
				# No content in this page? Redirect to page 1 then...
				if ( ! $m['running_subroutine']['content']['count'] and $m['running_subroutine']['page_nr_requested'] > 1 )
				{
					$this->API->http_redirect( $this->API->config['page']['request']['scheme'] . '://' . $this->API->config['page']['request']['host'] . $this->API->config['page']['request']['path'] );
				}
			}

			try
			{
				$result = $this->API->Db->db->query( $this->API->Db->cur_query )->fetchAll();
				$this->API->Db->query_count++;
			}
			catch ( Zend_Db_Exception $e )
			{
				$this->API->Db->exception_handler( $e );
				return FALSE;
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
						$_processor_instance = $this->API->classes__do_get( "data_processors__" . $_data_type );

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

			$this->API->Cache->cache__do_update(
					array(
							'name'    => $_cache_key_name__subroutines__content_for,
							'value'   => $return['content'] = $m['running_subroutine']['content']['parsed'],
							'array'   => 1
						)
				);
		}

		if ( $m['running_subroutine']['s_service_mode'] == 'write-only' )
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
	public function get__do_process__by_ref_id ( &$m , $ref_id , $fields_to_fetch = array() , $_callback_via_data_processor = TRUE )
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
						array( $_table_alias => $this->API->Db->attach_prefix( $_attach_prefix_reference_makes_us_do_this ) ),
						$this->API->Db->db->quoteIdentifier( "master.id" )
							. " = "
							. $this->API->Db->db->quoteIdentifier( $_table_alias . ".ref_id" ),
						array(),  // No list of columns to fetch, we place that in WHERE clause
					);
			}
		}

		$_attach_prefix_reference_makes_us_do_this = "mod_" . $m['m_unique_id_clean'] . "_master_repo";
		$this->API->Db->cur_query = $this->API->Db->db
			->select()
			->from(
					array( 'master' => $this->API->Db->attach_prefix( $_attach_prefix_reference_makes_us_do_this ) ),
					$_fields_to_fetch
				);

		foreach ( $_connector_joins as $_j )
		{
			$this->API->Db->cur_query->joinLeft( $_j[0] , $_j[1] , $_j[2] );
		}

		# + Where
		$this->API->Db->cur_query->where( $this->API->Db->db->quoteIdentifier( "master.id" ) . "=" . $this->API->Db->db->quote( $ref_id , "INTEGER" ) );

		//---------------------------------------------------------
		// Execute final fetch query and retrieve requested data
		//---------------------------------------------------------

		try
		{
			$result = $this->API->Db->db->query( $this->API->Db->cur_query )->fetchAll();
			$this->API->Db->query_count++;
		}
		catch ( Zend_Db_Exception $e )
		{
			$this->API->Db->exception_handler( $e );
			return FALSE;
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

				$_field_node = FALSE;

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
					$_processor_instance = $this->API->classes__do_get( "data_processors__" . $_field_node['type'] );

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
						'default_value'        =>  null,
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
						'default_value'        =>  null,
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
						'default_value'        =>  null,
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
						'default_value'        =>  null,
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
						'default_value'        =>  null,
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
						'default_value'        =>  null,
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
						'default_value'        =>  null,
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
						'default_value'        =>  null,
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
						'default_value'        =>  null,
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
						'default_value'        =>  null,
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
						'default_value'        =>  null,
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
						'default_value'        =>  null,
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
						'default_value'        =>  null,
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
			$faults[] = array( 'faultCode' => 706, 'faultMessage' => "SUBTYPE__IS_INVALID" ); // "No such data-subtype is defined: <em>" . $input[ $_form_field_name ] . "</em> (for data-type: <em>alphanumeric</em>)!"
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
						$faults[] = array( 'faultCode' => 708, 'faultMessage' => "DEFAULT_OPTIONS__IS_REQUIRED_FOR_DROPDOWN_MULTIPLE" ); // "<em>Default Options</em> is a required field for '<em>Preset Single/Multiple Select</em>' data-types!"
					}
					else
					{
						$input['default_options'] = explode( "\n", $input['default_options'] );
						foreach ( $input['default_options'] as $_option )
						{
							$__option = explode( "=", $_option );
							if ( count( $__option ) != 2 )
							{
								$faults[] = array( 'faultCode' => 708, 'faultMessage' => "DEFAULT_OPTIONS__ONE_EQ_PER_LINE_ONLY" ); // "<em>Default Options</em>: Each line must contain one and only one 'equals' (=) sign!"
							}
							elseif ( strlen( $__option[0] ) == 0 or strlen( $__option[1] ) == 0 )
							{
								$faults[] = array( 'faultCode' => 708, 'faultMessage' => "DEFAULT_OPTIONS__EQ_EITHER_SIDE_EMPTY" ); // "<em>Default Options</em>: Neither side of 'equals' (=) sign can be empty!"
							}
							elseif ( ! preg_match( '#^[a-z0-9_]+$#i' , $__option[0] ) )
							{
								$faults[] = array( 'faultCode' => 708, 'faultMessage' => "DEFAULT_OPTIONS__INVALID_CHARACTERS_INSIDE_KEY" ); // "<em>Default Options</em>: Left side of 'equals' (=) signs - i.e. the 'keys' of the options can contain <em>Perl-'word'-characters</em> ('<em>\w</em>') only!"
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
							$faults[] = array( 'faultCode' => 709, 'faultMessage' => "PRECISION_SCALE__INVALID_ENTRY__REVERTING_TO_DEFAULT", 'faultExtra' => "10,0" ); // "Invalid entry for <em>Precision &amp; Scale</em>! Assuming default value ('<em>10,0</em>'). Re-submit the form!"
						}
						elseif ( $_dft_maxlength[1] > 64 )
						{
							$faults[] = array( 'faultCode' => 709, 'faultMessage' => "PRECISION_SCALE__PRECISION_EXCEEDS_MAXIMUM" ); // "Invalid entry for <em>Precision &amp; Scale</em>! Number to the <em>left of comma</em> cannot exceed 64! You entered <em>" . $_dft_maxlength[1] . "<em> which is more than 64!"
						}
						elseif ( $_dft_maxlength[1] <= $_dft_maxlength[2] )
						{
							$faults[] = array( 'faultCode' => 709, 'faultMessage' => "PRECISION_SCALE__PRECISION_SMALLER_THAN_SCALE", 'faultExtra' => $_dft_maxlength[1] . ",0" ); // "Invalid entry for <em>Precision &amp; Scale</em>! Assuming value to be '<em>" . $_dft_maxlength[1] . ",0</em>'. Re-submit the form!"
						}
						else
						{
							# MAXLENGTH: Processing (for ALPHANUMERIC\DECIMAL)...
							$dft_maxlength = $_dft_maxlength[0];
						}
					}
					else
					{
						$faults[] = array( 'faultCode' => 709, 'faultMessage' => "PRECISION_SCALE__INVALID_ENTRY__REVERTING_TO_DEFAULT", 'faultExtra' => "10,0" ); // "Invalid entry for <em>Precision &amp; Scale</em>! Assuming default value ('<em>10,0</em>'). Re-submit the form!"
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
				if ( $input['default_value'] != '' )
				{
					if ( $dft_maxlength > 255 )
					{
						$faults[] = array( 'faultCode' => 710, 'faultMessage' => "DEFAULT_VALUE__STRING__BIGSTRING_DETECTED" ); // "255+ character-long '<em>General String</em>' subtype-field cannot have a default value! Leave <em>Default Value</em> field empty!"
					}
					elseif ( $dft_maxlength <= 255 and strlen( $input['default_value'] ) > $dft_maxlength )
					{
						$faults[] = array( 'faultCode' => 710, 'faultMessage' => "DEFAULT_VALUE__STRING__IS_LONGER_THAN_MAXLENGTH" ); // "A " . $dft_maxlength . "-character-long field cannot have a '<em>Default Value</em>' with " . strlen( $input['default_value'] ) . " characters! Change either '<em>Default Value</em>' or '<em>Field Max-Length</em>' setting! Also be aware of multi-byte characters!"
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
				# DEFAULT VALUE: Processing (for ALPHANUMERIC\INTEGER)...
				if ( $input['default_value'] != '' )
				{
					if ( ! preg_match( '#^' . $dft_input_regex . '$#', $input['default_value'] ) )
					{
						$faults[] = array( 'faultCode' => 710, 'faultMessage' => "DEFAULT_VALUE__INTEGER_UNSIGNED__INVALID_ENTRY" ); // "Invalid entry for <em>Default Value</em>: Entry needs to be numeric; and positive, if data-subtype is an unsigned integer! And it cannot violate the range ('<em>" . $_skel_subtype_node['min_value'] . "/" . $_skel_subtype_node['max_value'] ."</em>') of selected data-subtype!"
					}
					else
					{
						if ( $input['default_value'] < $_skel_subtype_node['min_value'] or $input['default_value'] > $_skel_subtype_node['max_value'] )
						{
							$faults[] = array( 'faultCode' => 710, 'faultMessage' => "DEFAULT_VALUE__INTEGER__OUT_OF_RANGE" ); // "Invalid entry for <em>Default Value</em>: Value beyond the range ('<em>" . $_skel_subtype_node['min_value'] . "/" . $_skel_subtype_node['max_value'] ."</em>') of select data-subtype!"
						}
						$dft_default_value = intval( $input['default_value'] );
					}
				}
				break;

			case 'decimal_signed':
			case 'decimal_unsigned':
				if ( $input['default_value'] != '' )
				{
					if ( $dft_subtype == 'decimal_unsigned' and $input['default_value'] < 0 )
					{
						$faults[] = array( 'faultCode' => 710, 'faultMessage' => "DEFAULT_VALUE__NUMERIC_UNSIGNED__ENTRY_IS_SIGNED" ); // "Unsigned data-types cannot have a negative <em>Default Value</em>s!"
					}
					if ( ! preg_match( '#^' . $dft_input_regex . '$#', $input['default_value'] ) )
					{
						$faults[] = array( 'faultCode' => 710, 'faultMessage' => "DEFAULT_VALUE__DECIMAL__COMPLIANCE_WITH_PRECISION_SCALE_FAILURE" ); // "<em>Default Value</em> does not comply with <em>Precision &amp; Scale</em> setting! Fix either one."
					}
					# DEFAULT VALUE: Processing (for ALPHANUMERIC\DECIMAL)...
					$dft_default_value = floatVal( $input['default_value'] );
				}
				break;

			case 'dropdown':
			case 'multiple':
				if ( $input['default_value'] != '' )
				{
					if ( preg_match_all( '#^(?P<first>[a-z0-9_]+)(?:,(?P<rest>[a-z0-9_]+))*$#i' , $input['default_value'] , $_values ) )
					{
						if ( empty( $_values['rest'][0] ) )
						{
							unset( $_values['rest'][0] );
						}
						if ( $dft_subtype == 'dropdown' and count( $_values['rest'] ) )
						{
							$faults[] = array( 'faultCode' => 710, 'faultMessage' => "DEFAULT_VALUE__DROPDOWN__MORE_THAN_ONE_VALUES_PROVIDED" ); // "<em>Default Value</em>: 'Preset Single Select [Dropdown or Radio]' data-type can have only one default value!"
						}
						if ( ! array_key_exists( $_values['first'][0], $dft_default_options ) )
						{
							$faults[] = array( 'faultCode' => 710, 'faultMessage' => "DEFAULT_VALUE__DROPDOWN__OUT_OF_RANGE" ); // "<em>Default Value</em>: One or more default values could not be found among the values provided in default options!"
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
		if ( ! is_null( $dft_default_value ) and $dft_is_unique )
		{
			$faults[] = array( 'faultCode' => 710, 'faultMessage' => "DEFAULT_VALUE__UNIQUE_CANNOT_HAVE_DEFAULT_VALUE" );
		}
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
						'is_required'          =>  $input['is_required'] ? 1 : 0,
						'is_numeric'           =>  $_skel_subtype_node['is_numeric'],
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
					$this->API->Db->db->quoteIdentifier( $_rule['field_name'] )
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
							$_rule['value'] = $this->API->Input->clean__makesafe_mathematical( $_rule['value'], true );    // Cleanup
							$_rule__parsed .= $this->API->Db->quote( eval( "return " . $_rule['value'] . ";"), "FLOAT" );
						}
						else
						{
							# INTEGER
							$_rule['value'] = $this->API->Input->clean__makesafe_mathematical( $_rule['value'] );          // Cleanup
							$_rule__parsed .= $this->API->Db->quote( eval( "return " . $_rule['value'] . ";"), "INTEGER" );
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
						$_rule__parsed .= $this->API->Db->quote( $_rule['value'] );
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
				$this->API->http_redirect( $this->API->config['page']['request']['scheme'] . '://' . $this->API->config['page']['request']['host'] . $this->API->config['page']['request']['path'] , 301 );
			}

		}

		# Validate the page number against the LIMIT
		if ( $m['running_subroutine']['s_fetch_criteria']['limit'] )
		{
			if ( $m['running_subroutine']['s_fetch_criteria']['limit'] == 1 and $_page_number > 1 )
			{
				$this->API->http_redirect( $this->API->config['page']['request']['scheme'] . '://' . $this->API->config['page']['request']['host'] . $this->API->config['page']['request']['path'] , 301 );
			}
			if ( $m['running_subroutine']['s_fetch_criteria']['pagination'] )
			{
				if ( ( $_page_number - 1 ) * $m['running_subroutine']['s_fetch_criteria']['pagination'] > $m['running_subroutine']['s_fetch_criteria']['limit'] )
				{
					$this->API->http_redirect( $this->API->config['page']['request']['scheme'] . '://' . $this->API->config['page']['request']['host'] . $this->API->config['page']['request']['path'] , 302 );
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