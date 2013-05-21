<?php

namespace Persephone\Core\Cache;

if ( !defined( "INIT_DONE" ) )
{
	die( "Improper access! Exiting now..." );
}

/**
 * Recache class - Defines methods for all data re-caching mechanisms
 *
 * @package  Audith CMS codename Persephone
 * @author   Shahriyar Imanov <shehi@imanov.name>
 * @version  1.0
 **/
class Recache
{
	/**
	 * Registry reference
	 *
	 * @var \Persephone\Core\Registry
	 */
	private $Registry;


	/**
	 * Constructor
	 */
	public function __construct ( \Persephone\Core\Registry $Registry )
	{
		//-----------
		// Prelim
		//-----------

		$this->Registry = $Registry;
	}


	/**
	 * Recache wrapper
	 *
	 * @param    string $key        What to cache
	 * @param    array  $params     Additional parameters [optional]
	 *
	 * @return   mixed                  Retrieved data
	 */
	public function main ( $key, $params = array() )
	{
		# You can't ask for SOMEthing that is nothing :)
		if ( empty( $key ) )
		{
			return false;
		}

		# Method name, plus tiny adjustment for some special caches
		$_method_name = $key . "__do_recache";

		# Check whether the requested method exists or not
		if ( !method_exists( $this, $_method_name ) )
		{
			if ( preg_match( '/^fileinfo_(?P<identifier>.+?)$/', $key, $_match ) )
			{
				$_method_name = "fileinfo__do_recache";
				$params       = array( 'identifier' => $_match[ 'identifier' ] );
			}
			elseif ( preg_match( '/^linkinfo_(?P<f_hash>[a-z0-9]{32})_(?P<m_id>\d+)$/i', $key, $_match ) )
			{
				$_method_name = "linkinfo__do_recache";
				$params       = array( 'f_hash' => $_match[ 'f_hash' ], 'm_id' => $_match[ 'm_id' ] );
			}
			else
			{
				# Log and return
				$this->Registry->logger__do_log( "Cache - Recache: No re-cache mechanism defined for key '" . $key . "'", "ERROR" );

				return false;
			}
		}

		# Execute
		$_cache = $this->$_method_name( $params );

		if ( $_cache )
		{
			# Cache Abstraction - Do your thing :) and log the result
			$return = $this->Registry->Cache->cache__do_update(
				array(
				     'name'  => $key,
				     'value' => $_cache,
				     'array' => is_array( $_cache )
					     ? 1
					     : 0
				)
			);
			if ( array_key_exists( $key, $this->Registry->Cache->cache ) ) // Update Cache->cache container if applicable.
			{
				$this->Registry->Cache->cache[ $key ] = $_cache;
			}
			$this->Registry->logger__do_log(
				"Cache - Recache " .
				( $return !== false
					? "succeeded"
					: "completely or partially failed" ) . " for key '" . $key . "'",
				$return !== false
					? "INFO"
					: "ERROR"
			);
		}

		return $_cache;
	}


	/**
	 * BANFILTERS
	 */
	private function banfilters__do_recache ( $params = array() )
	{
		$this->Registry->Db->cur_query = array(
			'do'     => "select",
			'table'  => "ban_filters",
			'fields' => array( "ban_id", "ban_type", "ban_content", "ban_date", "ban_nocache" ),
			'where'  => "ban_nocache=" . $this->Registry->Db->quote( 0, "INTEGER" ),
		);
		$result                        = $this->Registry->Db->simple_exec_query();

		$_cache = array();
		foreach ( $result as $_r )
		{
			$_cache[ $_r[ 'ban_type' ] ][ ] = $_r[ 'ban_content' ];
		}

		return $_cache;
	}


	/**
	 * COMPONENTS_DDL_SKELETON
	 */
	private function components_ddl_skeleton__do_recache ( $params = array() )
	{
		$_join    = array();
		$_join[ ] = array(
			'fields'     => array( "*" ),
			'table'      => array( 'd' => "components_ddl_skel_definitions" ),
			'conditions' => 't.type_name=d.type_name',
		);

		$this->Registry->Db->cur_query = array(
			'do'       => "select",
			'fields'   => array( '_is_enabled' => "is_enabled" ),
			'table'    => array( 't' => "components_ddl_skel_types" ),
			'add_join' => $_join,
			'order'    => array( "t.type_name ASC", "d.order ASC" )
		);
		$result                        = $this->Registry->Db->simple_exec_query();

		$_cache = array();
		foreach ( $result as $_r )
		{
			$_cache[ $_r[ 'type_name' ] ][ '_is_enabled' ] = $_r[ '_is_enabled' ];
			unset( $_r[ '_is_enabled' ] );
			$_cache[ $_r[ 'type_name' ] ][ $_r[ 'subtype_name' ] ] = $_r;
		}

		return $_cache;
	}


	/**
	 * FILEINFO
	 */
	private function fileinfo__do_recache ( $params = array() )
	{
		$this->Registry->Db->cur_query = array(
			'do'    => "select_row",
			'table' => "media_library",
			'where' => is_numeric( $params[ 'identifier' ] )
				? "f_id=" . $this->Registry->Db->quote( $params[ 'identifier' ], "INTEGER" )
				: "f_hash=" . $this->Registry->Db->quote( $params[ 'identifier' ] ),
		);
		$_cache                        = $this->Registry->Db->simple_exec_query();

		# Parsing dimensions
		if ( strpos( $_cache[ 'f_dimensions' ], "x" ) !== false and $_cache[ 'f_dimensions' ] != 'x' )
		{
			$_dimensions = explode( "x", $_cache[ 'f_dimensions' ] );
			unset( $_cache[ 'f_dimensions' ] );
			$_cache[ 'f_dimensions' ][ 'width' ]  = $_dimensions[ 0 ];
			$_cache[ 'f_dimensions' ][ 'height' ] = $_dimensions[ 1 ];
		}

		return $_cache;
	}


	/**
	 * IGNORED_USERS
	 */
	private function ignored_users__do_recache ( $params = array() )
	{
		$member_data = ( !is_array( $params[ 'member_data' ] ) )
			? $this->Registry->Session->load_member( $params[ 'member_data' ], "all" )
			: $params[ 'member_data' ];

		$this->Registry->Db->cur_query = array(
			'do'    => "select",
			'table' => "ignored_users",
			'where' => "ignore_owner_id=" . $this->Registry->Db->quote( $member_data[ 'id' ], "INTEGER" ),
		);

		$result = $this->Registry->Db->simple_exec_query();

		$_cache = array();
		foreach ( $result as $_r )
		{
			$_cache[ $_r[ 'ignore_ignore_id' ] ] = array(
				'ignore_ignore_id' => $_r[ 'ignore_ignore_id' ],
				'ignore_messages'  => $_r[ 'ignore_messages' ],
				'ignore_topics'    => $_r[ 'ignore_topics' ]
			);
		}

		return $_cache;
	}


	/**
	 * LINKINFO
	 */
	private function linkinfo__do_recache ( $params = array() )
	{
		$this->Registry->Db->cur_query = array(
			'do'     => "select_row",
			'table'  => "media_library_links",
			'fields' => array(
				"f_hash",
				"l_hash",
				"l_time_to_die",
				"m_id",
				"l_name",
				'm_ip_address' => new \Zend\Db\Sql\Expression( "INET_NTOA( CONV( " . $this->Registry->Db->platform->quoteIdentifier( "m_ip_address" ) . ", 2, 10 ) )" ),
			),
			'where'  => array(
				array( "f_hash = " . $this->Registry->Db->platform->quoteValue( $params[ 'f_hash' ] ) ),
				array( "m_id = " . $this->Registry->Db->platform->quoteValue( $params[ 'm_id' ] ) ),
			),
		);

		return $this->Registry->Db->simple_exec_query();
	}


	/**
	 * LOGIN_METHODS
	 */
	private function login_methods__do_recache ( $params = array() )
	{
		$this->Registry->Db->cur_query = array(
			'do'    => "select",
			'table' => "login_methods",
			'where' => "login_enabled=" . $this->Registry->Db->platform->quoteValue( 1, "INTEGER" ),
			'order' => array( "login_order ASC" )
		);

		$result = $this->Registry->Db->simple_exec_query();

		$_cache = array();
		foreach ( $result as $_r )
		{
			$_cache[ $_r[ 'login_id' ] ] = $_r;
		}

		return $_cache;
	}


	/**
	 * MEMBER_GROUPS
	 */
	private function member_groups__do_recache ( $params = array() )
	{
		$this->Registry->Db->cur_query = array(
			"do"    => "select",
			"table" => "groups",
		);

		$_cache = array();
		if ( count( $result = $this->Registry->Db->simple_exec_query() ) )
		{
			$_r = array();
			foreach ( $result as $row )
			{
				foreach ( $row as $_k => $_v )
				{
					if ( $_k == 'g_dname_change' )
					{
						$_g_displayname_unit                    = explode( ":", $this->Registry->Input->clean__excessive_separators( $_v, ":" ) );
						$_r[ 'g_dname_change' ][ 'amount' ]     = intval( $_g_displayname_unit[ 0 ] );
						$_r[ 'g_dname_change' ][ 'timedelta' ]  = intval( $_g_displayname_unit[ 1 ] );
						$_r[ 'g_dname_change' ][ 'cond_value' ] = intval( $_g_displayname_unit[ 2 ] );
						$_r[ 'g_dname_change' ][ 'cond_unit' ]  = $_g_displayname_unit[ 3 ];
					}
					else
					{
						$_r[ $_k ] = $_v;
					}
				}
				$_cache[ $_r[ 'g_id' ] ] = $_r;
			}
		}

		return $_cache;
	}


	/**
	 * MIMELIST
	 */
	private function mimelist__do_recache ( $params = array() )
	{
		# Fetch data-types

		$this->Registry->Db->cur_query = array(
			'do'     => "select",
			'fields' => array( "type_extension", "type_extension_other", "type_description", "type_mime", "type_library" ),
			'table'  => "data_processors__types",
			'order'  => array( "type_extension ASC", "type_mime ASC" )
		);
		$result                        = $this->Registry->Db->simple_exec_query();

		$_cache = array();
		foreach ( $result as $r )
		{
			//------------------
			// Hex Signatures
			//------------------

			$this->Registry->Db->cur_query = array(
				'do'     => "select",
				'fields' => array(
					"type_extension",
					"type_hex_id" => new \Zend\Db\Sql\Expression( "HEX(" . $this->Registry->Db->platform->quoteIdentifier( "type_hex_id" ) . ")" ),
					"type_hex_id_offset",
					"type_signature_description",
				),
				'table'  => "data_processors__signatures",
				'where'  => "type_extension=" . $this->Registry->Db->platform->quoteValue( $r[ 'type_extension' ] ),
			);
			$result_                       = $this->Registry->Db->simple_exec_query();

			$_signatures = array();
			foreach ( $result_ as $r_ )
			{
				$_signatures[ ] = array(
					'type_extension'             => $r_[ 'type_extension' ],
					'type_hex_id'                => $r_[ 'type_hex_id' ],
					'type_hex_id_offset'         => $r_[ 'type_hex_id_offset' ],
					'type_signature_description' => $r_[ 'type_signature_description' ],
				);
			}
			//---------------
			// Continue...
			//---------------

			preg_match( '/^(?P<type>[a-z]+)\/(?P<subtype>[-a-z\.0-9]+)$/', $r[ 'type_mime' ], $_matches );
			$_cache[ 'by_ext' ][ $r[ 'type_extension' ] ] = $_cache[ 'by_type' ][ $_matches[ 'type' ] ][ $r[ 'type_extension' ] ] = array(
				'type_extension'       => $r[ 'type_extension' ],
				'type_extension_other' => $r[ 'type_extension_other' ],
				'type_description'     => $r[ 'type_description' ],
				'type_mime'            => $r[ 'type_mime' ],
				'type_library'         => $r[ 'type_library' ],
				'_signatures'          => $_signatures,
			);
		}

		return $_cache;
	}


	/**
	 * MODULES
	 */
	private function modules__do_recache ( $params = array() )
	{
		$this->Registry->Db->cur_query = array(
			'do'     => "select",
			'fields' => array(
				"m_unique_id",
				"m_name",
				"m_description",
				"m_type",
				"m_enforce_ssl",
				"m_title_column",
				"m_extras",
				"m_cache_array",
				"m_enable_caching",
				"m_can_disable",
				"m_can_remove",
				"m_is_enabled"
			),
			'table'  => "modules",
			'where'  => "m_type != " . $this->Registry->Db->platform->quoteValue( "connector" ),
		);

		$_cache = array();
		if ( count( $result = $this->Registry->Db->simple_exec_query() ) )
		{
			foreach ( $result as $row )
			{
				//---------------
				// Module info
				//---------------

				$_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_name' ] = $row[ 'm_name' ];
				$_cache[ 'by_name' ][ $row[ 'm_name' ] ][ 'm_name' ]           =& $_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_name' ];

				$_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_unique_id' ] = $row[ 'm_unique_id' ];
				$_cache[ 'by_name' ][ $row[ 'm_name' ] ][ 'm_unique_id' ]           =& $_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_unique_id' ];

				$_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_unique_id_clean' ] = preg_replace( '#[^a-z0-9]#', "", strtolower( $row[ 'm_unique_id' ] ) );
				$_cache[ 'by_name' ][ $row[ 'm_name' ] ][ 'm_unique_id_clean' ]           =& $_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_unique_id_clean' ];

				$_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_description' ] = $row[ 'm_description' ];
				$_cache[ 'by_name' ][ $row[ 'm_name' ] ][ 'm_description' ]           =& $_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_description' ];

				$_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_type' ] = $row[ 'm_type' ];
				$_cache[ 'by_name' ][ $row[ 'm_name' ] ][ 'm_type' ]           =& $_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_type' ];

				$_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_enforce_ssl' ] = $row[ 'm_enforce_ssl' ];
				$_cache[ 'by_name' ][ $row[ 'm_name' ] ][ 'm_enforce_ssl' ]           =& $_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_enforce_ssl' ];

				$_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_title_column' ] = $row[ 'm_title_column' ];
				$_cache[ 'by_name' ][ $row[ 'm_name' ] ][ 'm_title_column' ]           =& $_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_title_column' ];

				$_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_extras' ] = unserialize( $row[ 'm_extras' ] );
				$_cache[ 'by_name' ][ $row[ 'm_name' ] ][ 'm_extras' ]           =& $_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_extras' ];

				$_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_cache_array' ] = unserialize( $row[ 'm_cache_array' ] );
				$_cache[ 'by_name' ][ $row[ 'm_name' ] ][ 'm_cache_array' ]           =& $_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_cache_array' ];

				$_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_enable_caching' ] = $row[ 'm_enable_caching' ];
				$_cache[ 'by_name' ][ $row[ 'm_name' ] ][ 'm_enable_caching' ]           =& $_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_enable_caching' ];

				$_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_can_disable' ] = $row[ 'm_can_disable' ];
				$_cache[ 'by_name' ][ $row[ 'm_name' ] ][ 'm_can_disable' ]           =& $_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_can_disable' ];

				$_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_can_remove' ] = $row[ 'm_can_remove' ];
				$_cache[ 'by_name' ][ $row[ 'm_name' ] ][ 'm_can_remove' ]           =& $_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_can_remove' ];

				$_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_is_enabled' ] = $row[ 'm_is_enabled' ];
				$_cache[ 'by_name' ][ $row[ 'm_name' ] ][ 'm_is_enabled' ]           =& $_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_is_enabled' ];

				//---------------
				// Module DDL
				//---------------

				# Built-in modules don't need M_DFD and Subroutines from DB
				if ( $row[ 'm_type' ] != 'built-in' )
				{
					$_m_data_definition        = array();
					$_m_data_definition_bak    = array();
					$_m_data_definition_merged = array();

					$this->Registry->Db->cur_query = array(
						'do'     => "select",
						'fields' => array(
							"m_unique_id",
							"name",
							"label",
							"type",
							"subtype",
							"maxlength",
							"allowed_filetypes",
							"connector_length_cap",
							"input_regex",
							"request_regex",
							"default_options",
							"default_value",
							"connector_enabled",
							"connector_linked",
							"e_data_definition",
							"is_html_allowed",
							"position",
							"is_required",
							"is_unique",
							"is_numeric",
							"is_backup"
						),
						'table'  => "modules_data_definition",
						'where'  => "m_unique_id=" . $this->Registry->Db->platform->quoteValue( $row[ 'm_unique_id' ] ),
						'order'  => array( "position ASC", "is_backup ASC" ),
					);
					if ( count( $result_ = $this->Registry->Db->simple_exec_query() ) )
					{
						foreach ( $result_ as $row_ )
						{
							# Connector-Units
							if ( $row_[ 'connector_enabled' ] and !empty( $row_[ 'connector_linked' ] ) )
							{
								$this->Registry->Db->cur_query = array(
									'do'     => "select",
									'fields' => array(
										"m_unique_id",
										"name",
										"label",
										"type",
										"subtype",
										"maxlength",
										"allowed_filetypes",
										"connector_length_cap",
										"input_regex",
										"request_regex",
										"default_options",
										"default_value",
										"e_data_definition",
										"is_html_allowed",
										"is_required",
										"is_unique",
										"is_numeric",
										"is_backup"
									),
									'table'  => "modules_data_definition",
									'where'  => "m_unique_id=" . $this->Registry->Db->platform->quoteValue( $row_[ 'connector_linked' ] ),
									'order'  => array( "position ASC", "name ASC" ),
								);
								if ( count( $result__ = $this->Registry->Db->simple_exec_query() ) )
								{
									foreach ( $result__ as $row__ )
									{
										# External ('Link') DDL - Fields-to-fetch-from
										if ( !empty( $row__[ 'e_data_definition' ] ) )
										{
											$__tmp                                = explode( "\n", $row__[ 'e_data_definition' ] );
											$__e_data_definition[ 'm_unique_id' ] = $__tmp[ 0 ];
											unset( $__tmp[ 0 ] );
											$__e_data_definition[ 'm_data_definition' ] = array_values( $__tmp );
											$row__[ 'e_data_definition' ]               = $__e_data_definition;
										}

										# The rest...
										if ( !empty( $row__[ 'default_options' ] ) )
										{
											$row__[ 'default_options' ] = unserialize( $row__[ 'default_options' ] );
										}
										if ( empty( $row__[ 'request_regex' ] ) or is_null( $row__[ 'request_regex' ] ) )
										{
											$row__[ 'request_regex' ] = false;
										}
										if ( empty( $row__[ 'input_regex' ] ) or is_null( $row__[ 'input_regex' ] ) )
										{
											$row__[ 'input_regex' ] = false;
										}
										if ( !$row__[ 'is_backup' ] )
										{
											$row_[ 'c_data_definition' ][ $row__[ 'name' ] ] = $row__;
										}
										else
										{
											$row_[ 'c_data_definition_bak' ][ $row__[ 'name' ] ] = $row__;
										}
										// $_m_data_definition_merged[ $row_['name'] . ucwords( $row__['name'] ) ] = $row__;
									}
								}
							}

							# External ('Link') DDL - Fields-to-fetch-from
							if ( !empty( $row_[ 'e_data_definition' ] ) )
							{
								$_tmp                                = explode( "\n", $row_[ 'e_data_definition' ] );
								$_e_data_definition[ 'm_unique_id' ] = $_tmp[ 0 ];
								unset( $_tmp[ 0 ] );
								$_e_data_definition[ 'm_data_definition' ] = array_values( $_tmp );
								$row_[ 'e_data_definition' ]               = $_e_data_definition;
							}

							# The rest...
							if ( !empty( $row_[ 'default_options' ] ) )
							{
								$row_[ 'default_options' ] = unserialize( $row_[ 'default_options' ] );
							}
							if ( empty( $row_[ 'request_regex' ] ) or is_null( $row_[ 'request_regex' ] ) )
							{
								$row_[ 'request_regex' ] = false;
							}
							if ( empty( $row_[ 'input_regex' ] ) or is_null( $row_[ 'input_regex' ] ) )
							{
								$row_[ 'input_regex' ] = false;
							}
							if ( !$row_[ 'is_backup' ] )
							{
								$_m_data_definition[ $row_[ 'name' ] ] = $row_;
							}
							else
							{
								$_m_data_definition_bak[ $row_[ 'name' ] ] = $row_;
							}
						}
					}

					//----------------------
					// Module Subroutines
					//----------------------

					$_subroutines                  = array();
					$this->Registry->Db->cur_query = array(
						'do'     => "select",
						'table'  => "modules_subroutines",
						'fields' => array(
							"s_name",
							"s_data_source",
							"s_data_target",
							"s_pathinfo_uri_schema",
							"s_pathinfo_uri_schema_parsed",
							"s_qstring_parameters",
							"s_fetch_criteria",
							"m_unique_id",
							"s_data_definition",
							"s_additional_skin_assets",
							"s_can_remove"
						),
						'where'  => array( "m_unique_id=" . $this->Registry->Db->platform->quoteValue( $row[ 'm_unique_id' ] ) )
					);

					if ( count( $_result = $this->Registry->Db->simple_exec_query() ) )
					{
						foreach ( $_result as $_row )
						{
							$_s_data_definition = unserialize( $_row[ 's_data_definition' ] );
							if ( count( $_s_data_definition ) )
							{
								foreach ( $_s_data_definition as $_s_data_definition_unit )
								{
									# Connector-enabled fields - we need parent name, not Connector-Unit child name
									$_c_unit_element_names = array(); // Temporary container
									if ( strpos( $_s_data_definition_unit[ 'name' ], "." ) !== false )
									{
										$_c_unit_element_names = explode( ".", $_s_data_definition_unit[ 'name' ] );
									}
									else
									{
										$_c_unit_element_names[ 0 ] = $_s_data_definition_unit[ 'name' ];
									}

									# Link M_DFD to S_DFD [IMPORTANT NOTE: DO NOT REFERENCE (!) TO M_DFD, TAKE ONLY ITS COPY!!!]
									isset( $_c_unit_element_names[ 1 ] )
										? $_s_data_definition[ $_s_data_definition_unit[ 'name' ] ] =
										$_m_data_definition[ $_c_unit_element_names[ 0 ] ][ 'c_data_definition' ][ $_c_unit_element_names[ 1 ] ]
										: $_s_data_definition[ $_s_data_definition_unit[ 'name' ] ] = $_m_data_definition[ $_c_unit_element_names[ 0 ] ];

									# Flag M_DFD as non-deletable [NOTE: Only master-DFD has this flag, not Connector-Unit DFD!!!]
									if ( !isset( $_m_data_definition[ $_c_unit_element_names[ 0 ] ][ 'used_in' ] ) )
									{
										# Lets first initiate the variable, if it's not been done so already
										$_m_data_definition[ $_c_unit_element_names[ 0 ] ][ 'used_in' ] = array();
									}
									if ( !in_array( $_row[ 's_name' ], $_m_data_definition[ $_c_unit_element_names[ 0 ] ][ 'used_in' ] ) )
									{
										$_m_data_definition[ $_c_unit_element_names[ 0 ] ][ 'used_in' ][ ] = $_row[ 's_name' ];
									}
								}
							}

							$_subroutines[ $_row[ 's_name' ] ] = array(
								's_data_source'                => $_row[ 's_data_source' ],
								's_data_target'                => $_row[ 's_data_target' ],
								's_data_definition'            => $_s_data_definition,
								's_name'                       => $_row[ 's_name' ],
								's_pathinfo_uri_schema'        => $_row[ 's_pathinfo_uri_schema' ],
								's_pathinfo_uri_schema_parsed' => $_row[ 's_pathinfo_uri_schema_parsed' ],
								's_qstring_parameters'         => unserialize( $_row[ 's_qstring_parameters' ] ),
								's_fetch_criteria'             => unserialize( $_row[ 's_fetch_criteria' ] ),
								'm_unique_id'                  => $_row[ 'm_unique_id' ],
								's_additional_skin_assets'     => ( !is_null( $_row[ 's_additional_skin_assets' ] ) )
									? unserialize( $_row[ 's_additional_skin_assets' ] )
									: array(),
								's_can_remove'                 => (boolean) $_row[ 's_can_remove' ],
							);
						}
					}
					$_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_data_definition' ] = $_m_data_definition;
					$_cache[ 'by_name' ][ $row[ 'm_name' ] ][ 'm_data_definition' ]           =& $_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_data_definition' ];

					$_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_data_definition_bak' ] = $_m_data_definition_bak;
					$_cache[ 'by_name' ][ $row[ 'm_name' ] ][ 'm_data_definition_bak' ]           =& $_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_data_definition_bak' ];

					$_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_data_definition_count' ] = count( $_m_data_definition );
					$_cache[ 'by_name' ][ $row[ 'm_name' ] ][ 'm_data_definition_count' ]           =& $_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_data_definition_count' ];

					$_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_subroutines' ] = $_subroutines;
					$_cache[ 'by_name' ][ $row[ 'm_name' ] ][ 'm_subroutines' ]           =& $_cache[ 'by_unique_id' ][ $row[ 'm_unique_id' ] ][ 'm_subroutines' ];
				}
			}
		}

		return $_cache;
	}


	/**
	 * MODULES_CONNECTORS
	 */
	private function modules_connectors__do_recache ( $params = array() )
	{
		$this->Registry->Db->cur_query = array(
			'do'     => "select",
			'fields' => array( "m_unique_id" ),
			'table'  => "modules",
			'where'  => "m_type = " . $this->Registry->Db->platform->quoteValue( "connector" ),
		);

		$_cache = array();
		if ( count( $result = $this->Registry->Db->simple_exec_query() ) )
		{
			foreach ( $result as $row )
			{
				//---------------
				// Module info
				//---------------

				$_cache[ $row[ 'm_unique_id' ] ][ 'm_unique_id' ]       = $row[ 'm_unique_id' ];
				$_cache[ $row[ 'm_unique_id' ] ][ 'm_unique_id_clean' ] = preg_replace( '#[^a-z0-9]#', "", strtolower( $row[ 'm_unique_id' ] ) );

				//---------------
				// Module DDL
				//---------------

				$_m_data_definition     = array();
				$_m_data_definition_bak = array();

				$this->Registry->Db->cur_query = array(
					'do'     => "select",
					'fields' => array(
						"m_unique_id",
						"name",
						"label",
						"type",
						"subtype",
						"maxlength",
						"allowed_filetypes",
						"connector_length_cap",
						"input_regex",
						"request_regex",
						"default_options",
						"default_value",
						"is_html_allowed",
						"position",
						"is_required",
						"is_unique",
						"is_numeric",
						"is_backup"
					),
					'table'  => "modules_data_definition",
					'where'  => "m_unique_id=" . $this->Registry->Db->platform->quoteValue( $row[ 'm_unique_id' ] ),
					'order'  => array( "position ASC", "is_backup ASC" ),
				);
				if ( count( $result_ = $this->Registry->Db->simple_exec_query() ) )
				{
					foreach ( $result_ as $row_ )
					{
						if ( empty( $row_[ 'request_regex' ] ) or is_null( $row_[ 'request_regex' ] ) )
						{
							$row_[ 'request_regex' ] = false;
						}
						if ( empty( $row_[ 'input_regex' ] ) or is_null( $row_[ 'input_regex' ] ) )
						{
							$row_[ 'input_regex' ] = false;
						}
						if ( !$row_[ 'is_backup' ] )
						{
							$_m_data_definition[ $row_[ 'name' ] ] = $row_;
						}
						else
						{
							$_m_data_definition_bak[ $row_[ 'name' ] ] = $row_;
						}
					}
				}

				$_cache[ $row[ 'm_unique_id' ] ][ 'm_data_definition' ]       = $_m_data_definition;
				$_cache[ $row[ 'm_unique_id' ] ][ 'm_data_definition_bak' ]   = $_m_data_definition_bak;
				$_cache[ $row[ 'm_unique_id' ] ][ 'm_data_definition_count' ] = count( $_m_data_definition );
			}
		}

		return $_cache;
	}


	/**
	 * PROFILEFIELDS
	 */
	private function profilefields__do_recache ( $params = array() )
	{
		$_join[ ] = array(
			'fields'     => array( "g.pf_group_name", "g.pf_group_key" ),
			'table'      => array( 'g' => "members_pfields_groups" ),
			'conditions' => 'g.pf_group_id=d.pf_group_id',
			'join_type'  => 'LEFT'
		);

		$this->Registry->Db->cur_query = array(
			'do'       => "select",
			'fields'   => "d.*",
			'table'    => array( 'd' => 'members_pfields_data' ),
			'add_join' => $_join,
			'order'    => array( "d.pf_group_id ASC", "d.pf_position ASC", "d.pf_id ASC" )
		);

		$_cache = array();
		if ( count( $result = $this->Registry->Db->simple_exec_query() ) )
		{
			foreach ( $result as $row )
			{
				$_cache[ $row[ 'pf_id' ] ] /* = $_cache['by_id'][ $row['pf_id'] ] */ /* = $_cache['by_key'][ $row['pf_key'] ] */ = $row;
			}
		}

		return $_cache;
	}


	/**
	 * SETTINGS
	 */
	private function settings__do_recache ( $params = array() )
	{
		$this->Registry->Db->cur_query = array(
			'do'       => "select",
			'fields'   => array( "*" ),
			'table'    => array( 'g' => "conf_settings_groups" ),
			'add_join' => array(
				array(
					'fields'     => array( "*" ),
					'table'      => array( 'c' => "conf_settings" ),
					'conditions' => "g.conf_group_id = c.conf_group",
					'join_type'  => "INNER"
				)
			),
			'order'    => array( "g.conf_group_title ASC", "c.conf_position ASC", "c.conf_id ASC" )
		);

		$_cache = array();
		if ( count( $result = $this->Registry->Db->simple_exec_query() ) )
		{
			foreach ( $result as $row )
			{
				$_cache[ 'by_id' ][ $row[ 'conf_group_id' ] ][ 'conf_group_id' ]       = $row[ 'conf_group_id' ];
				$_cache[ 'by_key' ][ $row[ 'conf_group_keyword' ] ][ 'conf_group_id' ] =& $_cache[ 'by_id' ][ $row[ 'conf_group_id' ] ][ 'conf_group_id' ];

				$_cache[ 'by_id' ][ $row[ 'conf_group_id' ] ][ 'conf_group_title' ]       = $row[ 'conf_group_title' ];
				$_cache[ 'by_key' ][ $row[ 'conf_group_keyword' ] ][ 'conf_group_title' ] =& $_cache[ 'by_id' ][ $row[ 'conf_group_id' ] ][ 'conf_group_title' ];

				$_cache[ 'by_id' ][ $row[ 'conf_group_id' ] ][ 'conf_group_desc' ]       = $row[ 'conf_group_desc' ];
				$_cache[ 'by_key' ][ $row[ 'conf_group_keyword' ] ][ 'conf_group_desc' ] =& $_cache[ 'by_id' ][ $row[ 'conf_group_id' ] ][ 'conf_group_desc' ];

				$_cache[ 'by_id' ][ $row[ 'conf_group_id' ] ][ 'conf_group_count' ]       = $row[ 'conf_group_count' ];
				$_cache[ 'by_key' ][ $row[ 'conf_group_keyword' ] ][ 'conf_group_count' ] =& $_cache[ 'by_id' ][ $row[ 'conf_group_id' ] ][ 'conf_group_count' ];

				$_cache[ 'by_id' ][ $row[ 'conf_group_id' ] ][ 'conf_group_noshow' ]       = $row[ 'conf_group_noshow' ];
				$_cache[ 'by_key' ][ $row[ 'conf_group_keyword' ] ][ 'conf_group_noshow' ] =& $_cache[ 'by_id' ][ $row[ 'conf_group_id' ] ][ 'conf_group_noshow' ];

				$_cache[ 'by_id' ][ $row[ 'conf_group_id' ] ][ 'conf_group_keyword' ]       = $row[ 'conf_group_keyword' ];
				$_cache[ 'by_key' ][ $row[ 'conf_group_keyword' ] ][ 'conf_group_keyword' ] =& $_cache[ 'by_id' ][ $row[ 'conf_group_id' ] ][ 'conf_group_keyword' ];

				$_cache[ 'by_key' ][ $row[ 'conf_group_keyword' ] ][ $row[ 'conf_key' ] ] = array(
					'conf_id'          => $row[ 'conf_id' ],
					'conf_title'       => $row[ 'conf_title' ],
					'conf_description' => $row[ 'conf_description' ],
					'conf_type'        => $row[ 'conf_type' ],
					'conf_key'         => $row[ 'conf_key' ],
					'conf_value'       => $row[ 'conf_value' ],
					'conf_default'     => $row[ 'conf_default' ],
					'conf_extra'       => $row[ 'conf_extra' ],
					'conf_show'        => $row[ 'conf_show' ],
					'conf_position'    => $row[ 'conf_position' ],
					'conf_start_group' => $row[ 'conf_start_group' ],
					'conf_end_group'   => $row[ 'conf_end_group' ]
				);
				$_cache[ 'by_id' ][ $row[ 'conf_group_id' ] ][ $row[ 'conf_id' ] ]        =& $_cache[ 'by_key' ][ $row[ 'conf_group_keyword' ] ][ $row[ 'conf_key' ] ];
			}
		}

		return $_cache;
	}


	/**
	 * SKINS
	 */
	private function skins__do_recache ( $params = array() )
	{
		$this->Registry->Db->cur_query = array(
			'do'     => "select",
			'fields' => array(
				"set_id",
				"set_name",
				"set_hide_from_list",
				"set_is_default",
				"set_author_email",
				"set_author_name",
				"set_author_url",
				"set_assets",
				"set_favicon",
				"set_css_updated",
				"set_enabled"
			),
			'table'  => "skin_sets",
			// @todo    Skin permissions and useragent-tied-hidden-skins stuff
			// 'where'  => array( array( "set_enabled=?", $this->Registry->Db->quote( 1, "INTEGER" ) ) )
		);

		$_cache = array();
		if ( count( $result = $this->Registry->Db->simple_exec_query() ) )
		{
			foreach ( $result as $row )
			{
				$_cache[ $row[ 'set_id' ] ] = array(
					'set_id'             => $row[ 'set_id' ],
					'set_name'           => $row[ 'set_name' ],
					'set_hide_from_list' => (int) $row[ 'set_hide_from_list' ],
					'set_is_default'     => (int) $row[ 'set_is_default' ],
					'set_author_email'   => $row[ 'set_author_email' ],
					'set_author_name'    => $row[ 'set_author_name' ],
					'set_author_url'     => $row[ 'set_author_url' ],
					'set_assets'         => unserialize( $row[ 'set_assets' ] ),
					'set_favicon'        => $row[ 'set_favicon' ],
					'set_css_updated'    => (int) $row[ 'set_css_updated' ]
				);

				if ( $row[ 'set_is_default' ] )
				{
					if ( isset( $_cache[ '_default_skin' ] ) and count( $_cache[ '_default_skin' ] ) )
					{
						$_msg = "More than one skin sets been flagged as DEFAULT
							(current: \"" . $_cache[ '_default_skin' ][ 'set_id' ] . "\",
							duplicate: \"" . $row[ 'set_id' ] . "\")! Overriding default with the last flagged skin!
							Diagnostics recommended!";
						$this->Registry->logger__do_log( __CLASS__ . "::skins__do_recache(): " . $_msg, "WARNING" );
					}
					$_cache[ '_default_skin' ] =& $_cache[ $row[ 'set_id' ] ];
				}
			}
		}

		return $_cache;
	}


	/**
	 * STATS
	 */
	private function stats__do_recache ( $params = array() )
	{
		$this->Registry->Db->cur_query = array(
			'do'     => "select_row",
			'fields' => array(
				'member_count' => new \Zend\Db\Sql\Expression( "COUNT(id)" ),
			),
			'table'  => "members",
			'where'  => "mgroup != " . $this->Registry->Db->platform->quoteValue( $this->Registry->config[ 'security' ][ 'auth_group' ] ),
		);
		$result                        = $this->Registry->Db->simple_exec_query();

		$_cache                = array();
		$_cache[ 'mem_count' ] = intval( $result[ 'member_count' ] );

		$this->Registry->Db->cur_query = array(
			'do'     => "select_row",
			'fields' => array( "id", "display_name", "seo_name" ),
			'table'  => "members",
			'where'  => array(
				array( "mgroup != " . $this->Registry->Db->platform->quoteValue( $this->Registry->config[ 'security' ][ 'auth_group' ] ) ),
				/*
				array( "display_name != " . $this->Registry->Db->quote( "" ) ),
				array( "display_name " . $this->Registry->Db->build__is_null( FALSE ) ),
				*/
			),
			'order'  => array( "id DESC" ),
		);
		$result                        = $this->Registry->Db->simple_exec_query();

		$_cache[ 'last_mem_name_seo' ] = $result[ 'seo_name' ];
		$_cache[ 'last_mem_name' ]     = $result[ 'display_name' ]
			? $result[ 'display_name' ]
			: $result[ 'name' ];
		$_cache[ 'last_mem_id' ]       = $result[ 'id' ];

		return $_cache;
	}


	/**
	 * SYSTEMVARS
	 */
	private function systemvars__do_recache ( $params = array() )
	{
		$_cache                    = array();
		$_cache[ 'task_next_run' ] = UNIX_TIME_NOW;
		$_cache[ 'load_limit' ]    = $this->Registry->config[ 'performance' ][ 'load_limit' ];

		// $_cache['mail_queue']    = $_cache['mail_queue'];          @todo

		return $_cache;
	}


	/**
	 * TOTAL_NR_OF_ATTACHMENTS
	 */
	private function total_nr_of_attachments__do_recache ( $params = array() )
	{
		$this->Registry->Db->cur_query = array(
			'do'     => "select_one",
			'fields' => array( new \Zend\Db\Sql\Expression( "COUNT(*)" ) ),
			'table'  => "media_library",
		);

		return $this->Registry->Db->simple_exec_query();
	}


	/**
	 * USERAGENTS
	 *
	 * @return   mixed    Retrieved data
	 */
	private function useragents__do_recache ( $params = array() )
	{
		$this->Registry->Db->cur_query = array(
			"do"    => "select",
			"table" => "user_agents",
			"order" => array( "uagent_position ASC", "uagent_key ASC" ),
		);
		$result                        = $this->Registry->Db->simple_exec_query();

		$_cache = array();
		foreach ( $result as $row )
		{
			$_cache[ $row[ 'uagent_key' ] ] = $row;
		}

		return $_cache;
	}


	/**
	 * USERAGENTGROUPS
	 */
	private function useragentgroups__do_recache ( $params = array() )
	{
		$this->Registry->Db->cur_query = array(
			"do"    => "select",
			"table" => "user_agents_groups",
			"order" => array( "ugroup_id ASC" ),
		);
		$result                        = $this->Registry->Db->simple_exec_query();

		$_cache = array();
		foreach ( $result as $row )
		{
			$_cache[ $row[ 'ugroup_id' ] ] = $row;
		}

		return $_cache;
	}
}
