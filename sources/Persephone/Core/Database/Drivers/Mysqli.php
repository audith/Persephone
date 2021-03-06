<?php

namespace Persephone\Core\Database\Drivers;
use \Persephone\Core\Database;
use \Zend\Db\Adapter\Adapter;
use \Zend\Db\Sql\Sql;
use \Zend\Db\Sql\Expression;

if ( !defined( "INIT_DONE" ) )
{
	die( "Improper access! Exiting now..." );
}

/**
 * MYSQLi DRIVER
 *
 * @package  Audith CMS codename Persephone
 * @author   Shahriyar Imanov <shehi@imanov.name>
 * @version  1.0
 */
class Mysqli extends \Persephone\Core\Database
{
	/**
	 * Options specific to this driver
	 *
	 * @var array
	 */
	public $driver_options = array(
		'buffer_results' => true
	);

	/**
	 * SQL abstraction layer
	 *
	 * @var \Zend\Db\Sql\Sql
	 */
	public $sql;


	/**
	 * Constructor
	 *
	 * @param    \Persephone\Core\Registry Registry Object Reference
	 */
	public function __construct ( \Persephone\Core\Registry $Registry )
	{
		$this->Registry = $Registry;

		$_driver = array(
			'driver'   => "Mysqli",
			'host'     => &\Persephone\Core\Registry::$config[ 'sql' ][ 'host' ],
			'username' => &\Persephone\Core\Registry::$config[ 'sql' ][ 'user' ],
			'password' => &\Persephone\Core\Registry::$config[ 'sql' ][ 'passwd' ],
			'dbname'   => &\Persephone\Core\Registry::$config[ 'sql' ][ 'dbname' ],
			'options'  => $this->driver_options,
		);

		$this->platform = new \Zend\Db\Adapter\Platform\Mysql();
		$this->adapter  = new \Zend\Db\Adapter\Adapter( $_driver, $this->platform );
		$this->sql      = new Sql( $this->adapter );
	}


	/**
	 * Determines the referenced tables, and the count of referenced rows (latter is on-demand)
	 *
	 * @param       $referenced_table_name      string      Referenced table name
	 * @param       $_params                    array       Parameters containing information for querying referenced data statistics
	 *
	 * @usage       array(
	 *                  '_do_count' => true|false,
	 *                  'referenced_column_name' => '<column_name>',
	 *                  'value_to_check' => <key_to_check_against>
	 *              )
	 * @return                                  array       Reference and possibly, data statistics information (row-count)
	 */
	public function check_for_references ( $referenced_table_name, $_params = array() )
	{
		//----------------------------------
		// Fetching reference information
		//----------------------------------

		$this->cur_query = array(
			'do'     => "select",
			'fields' => array( "table_name", "column_name", "referenced_column_name" ),
			'table'  => array( "information_schema.KEY_COLUMN_USAGE" ),
			'where'  => array(
				array( 'table_schema = ' . $this->platform->quoteValue( \Persephone\Core\Registry::$config[ 'sql' ][ 'dbname' ] ) ),
				array( 'referenced_table_name = ' . $this->platform->quoteValue( $referenced_table_name ) ),
			)
		);
		$reference_information = $this->simple_exec_query();

		//----------------------------------------
		// Fetching referenced data statistics
		//----------------------------------------

		$_data_statistics = array();
		if ( !empty( $_params ) and $_params[ '_do_count' ] === true and !empty( $_params[ 'referenced_column_name' ] ) and !empty( $_params[ 'value_to_check' ] ) )
		{
			foreach ( $reference_information as $_r )
			{
				if ( $_r[ 'referenced_column_name' ] != $_params[ 'referenced_column_name' ] )
				{
					continue;
				}

				$this->cur_query = array(
					'do'     => "select_one",
					'fields' => array( new Expression( "count(*)" ) ),
					'table'  => $_r[ 'table_name' ],
					'where'  => $_r[ 'table_name' ] . "." . $_r[ 'column_name' ] . "=" .
					            ( is_int( $_params[ 'value_to_check' ] )
						            ? $this->platform->quoteValue( $_params[ 'value_to_check' ] )
						            : $this->platform->quoteValue( $_params[ 'value_to_check' ] ) ),
				);
				$_data_statistics[ $_r[ 'table_name' ] ] = $this->simple_exec_query();
			}
		}

		//----------
		// Return
		//----------

		return array( 'reference_information' => $reference_information, '_data_statistics' => $_data_statistics );
	}


	/**
	 * Prepares column-data for ALTER query for a given module data-field-type
	 *
	 * @param       $df_data                            array       Data-field info
	 * @param       $we_need_this_for_master_table      boolean     Whether translated info will be applied to "_master_repo" tables or not (related to Connector-enabled fields only!)
	 *
	 * @return                                          array       Column info
	 */
	public function modules__ddl_column_type_translation ( $df_data, $we_need_this_for_master_table = false )
	{
		if ( $we_need_this_for_master_table === true and ( isset( $df_data[ 'connector_enabled' ] ) and $df_data[ 'connector_enabled' ] == '1' ) )
		{
			$_col_info = array(
				'type'    => "MEDIUMTEXT",
				'length'  => null,
				'default' => "",
				'extra'   => null,
				'attribs' => null,
				'is_null' => false
			);
		}
		else
		{
			if ( $df_data[ 'type' ] == 'alphanumeric' )
			{
				//---------------------------
				// Alphanumeric : Mixed data
				//---------------------------

				if ( $df_data[ 'subtype' ] == 'string' )
				{
					# Default value
					if ( !isset( $df_data[ 'default_value' ] ) or is_null( $df_data[ 'default_value' ] ) )
					{
						if ( $df_data[ 'is_required' ] )
						{
							$_default_value = "";
							$_is_null       = false;
						}
						else
						{
							$_default_value = null;
							$_is_null       = true;
						}
					}
					else
					{
						$_default_value = $df_data[ 'default_value' ];
						$_is_null       = false;
					}

					# Continue...
					if ( $df_data[ 'maxlength' ] <= 255 )
					{
						$_col_info = array(
							'type'    => "VARCHAR",
							'length'  => $df_data[ 'maxlength' ],
							'default' => $_default_value,
							'attribs' => null,
							'is_null' => $_is_null,
							'indexes' => $df_data[ 'is_unique' ]
								? array( 'UNIQUE' => true )
								: array()
						);
					}
					elseif ( $df_data[ 'maxlength' ] <= 65535 )
					{
						$_col_info = array(
							'type'    => "TEXT",
							'length'  => null,
							'default' => $_default_value,
							'attribs' => null,
							'is_null' => $_is_null
						);
					}
					elseif ( $df_data[ 'maxlength' ] <= 16777215 )
					{
						$_col_info = array(
							'type'    => "MEDIUMTEXT",
							'length'  => null,
							'default' => $_default_value,
							'attribs' => null,
							'is_null' => $_is_null
						);
					}
					else
					{
						# Anything larger than 16 Megabytes is not accepted through a regular input-form-fields
					}
				}

				//--------------------------
				// Alphanumeric : Integer
				//--------------------------

				elseif ( preg_match( '#^integer_(?P<attrib>(?:un)?signed)_(?P<bit_length>\d{1,2})$#', $df_data[ 'subtype' ], $_dft_subtype ) )
				{
					# Default value
					if ( !isset( $df_data[ 'default_value' ] ) or is_null( $df_data[ 'default_value' ] ) )
					{
						if ( $df_data[ 'is_required' ] )
						{
							$_default_value = 0;
							$_is_null       = false;
						}
						else
						{
							$_default_value = null;
							$_is_null       = true;
						}
					}
					else
					{
						$_default_value = intval( $df_data[ 'default_value' ] );
						$_is_null       = false;
					}

					$_col_info = array(
						'length'  => $df_data[ 'maxlength' ],
						'default' => $_default_value,
						'attribs' => ( $_dft_subtype[ 'attrib' ] == 'unsigned' )
							? "UNSIGNED"
							: "SIGNED",
						'is_null' => $_is_null
					);

					# The rest...
					switch ( $_dft_subtype[ 'bit_length' ] )
					{
						case 8:
							$_col_info[ 'type' ] = "TINYINT";
							break;

						case 16:
							$_col_info[ 'type' ] = "SMALLINT";
							break;

						case 24:
							$_col_info[ 'type' ] = "MEDIUMINT";
							break;

						case 32:
							$_col_info[ 'type' ] = "INT";
							break;

						case 64:
							$_col_info[ 'type' ] = "BIGINT";
							break;
					}
				}

				//--------------------------
				// Alphanumeric : Decimal
				//--------------------------

				elseif ( $df_data[ 'subtype' ] == 'decimal_signed' or $df_data[ 'subtype' ] == 'decimal_unsigned' )
				{
					# Default value
					if ( !isset( $df_data[ 'default_value' ] ) or is_null( $df_data[ 'default_value' ] ) or !is_numeric( $df_data[ 'default_value' ] ) )
					{
						if ( $df_data[ 'is_required' ] )
						{
							$_default_value = 0.00;
							$_is_null       = false;
						}
						else
						{
							$_default_value = null;
							$_is_null       = true;
						}
					}
					else
					{
						$_default_value = floatval( $df_data[ 'default_value' ] );
						$_is_null       = false;
					}

					# The rest...
					$_col_info = array(
						'type'    => "DECIMAL",
						'length'  => $df_data[ 'maxlength' ],
						'default' => $_default_value,
						'attribs' => ( $df_data[ 'subtype' ] == 'decimal_unsigned' )
							? "UNSIGNED"
							: "SIGNED",
						'is_null' => $_is_null,
					);
				}
				elseif ( in_array( $df_data[ 'subtype' ], array( "dropdown", "multiple" ) ) )
				{
					# Default value
					if ( !isset( $df_data[ 'default_value' ] ) or is_null( $df_data[ 'default_value' ] ) )
					{
						$_default_value = null;
						$_is_null       = true;
					}
					else
					{
						$_default_value = $df_data[ 'default_value' ];
						$_is_null       = false;
					}

					# The rest...
					switch ( $df_data[ 'subtype' ] )
					{
						case 'dropdown':
							$_col_info = array(
								'type'    => "ENUM",
								'length'  => $df_data[ 'maxlength' ],
								'default' => $_default_value,
								'attribs' => null,
								'is_null' => $_is_null
							);
							break;

						case 'multiple':
							$_col_info = array(
								'type'    => "SET",
								'length'  => $df_data[ 'maxlength' ],
								'default' => $_default_value,
								'attribs' => null,
								'is_null' => $_is_null
							);
							break;
					}
				}
			}
			elseif ( $df_data[ 'type' ] == 'file' )
			{
				//--------
				// File
				//--------

				# Files are represented with their 32-char-long MD5 checksum hashes
				$_col_info = array(
					'type'    => "VARCHAR",
					'length'  => 32,
					'default' => null,
					'attribs' => null,
					'is_null' => true
				);
			}
			elseif ( $df_data[ 'type' ] == 'link' )
			{
				//--------
				// Link
				//--------

				# All Links are references to 'id' column of module master tables
				$_col_info = array(
					'type'    => "INT",
					'length'  => 10,
					'default' => null,
					'attribs' => null,
					'is_null' => true
				);
			}
		}
		$_col_info[ 'extra' ] = null;

		$_col_info[ 'name' ] = $df_data[ 'name' ];

		$_col_info[ 'comment' ] = $df_data[ 'label' ];
		$_col_info[ 'comment' ] = html_entity_decode( $_col_info[ 'comment' ], ENT_QUOTES, "UTF-8" ); // Decoding characters
		$_col_info[ 'comment' ] = preg_replace( "/'{2,}/", "'", $_col_info[ 'comment' ] ); // Excessive single-quotes
		if ( mb_strlen( $_col_info[ 'comment' ] ) > 255 ) // Truncate here, as you might get trailing single-quotes later on, for comments with strlen() close to 255.
		{
			$_col_info[ 'comment' ] = mb_substr( $_col_info[ 'comment' ], 0, 255 );
		}
		$_col_info[ 'comment' ] = str_replace( "'", "''", $_col_info[ 'comment' ] ); // Single quotes are doubled in number. This is MySQL syntax!

		return $_col_info;
	}


	/**
	 * Returns the table structure for any of the module tables
	 *
	 * @param       $suffix     string      Table suffix, determining specific table
	 *
	 * @return                  array       Table structure
	 */
	public final function modules__default_table_structure ( $suffix )
	{
		$_struct[ 'master_repo' ] = array(
			'col_info' => array(
				'id'                    => array(
					'type'      => "int",
					'length'    => 10,
					'collation' => null,
					'attribs'   => "unsigned",
					'is_null'   => false,
					'default'   => null,
					'extra'     => "auto_increment",
					'indexes'   => array(
						"PRIMARY" => true
					)
				),
				'tags'                  => array(
					'type'      => "mediumtext",
					'length'    => null,
					'collation' => null,
					'attribs'   => null,
					'is_null'   => true,
					'default'   => null,
					'extra'     => null,
					'indexes'   => null
				),
				'timestamp'             => array(
					'type'      => "int",
					'length'    => 10,
					'collation' => null,
					'attribs'   => "unsigned",
					'is_null'   => false,
					'default'   => null,
					'extra'     => null,
					'indexes'   => null
				),
				'submitted_by'          => array(
					'type'      => "mediumint",
					'length'    => 8,
					'collation' => null,
					'attribs'   => "unsigned",
					'is_null'   => false,
					'default'   => 0,
					'extra'     => null,
					'indexes'   => null
				),
				'status_published'      => array(
					'type'      => "tinyint",
					'length'    => 1,
					'collation' => null,
					'attribs'   => null,
					'is_null'   => false,
					'default'   => 0,
					'extra'     => null,
					'indexes'   => null
				),
				'status_locked'         => array(
					'type'      => "tinyint",
					'length'    => 1,
					'collation' => null,
					'attribs'   => null,
					'is_null'   => false,
					'default'   => 0,
					'extra'     => null,
					'indexes'   => null
				),
				'_x_data_compatibility' => array(
					'type'      => "varchar",
					'length'    => 255,
					'collation' => null,
					'attribs'   => null,
					'is_null'   => true,
					'default'   => null,
					'extra'     => null,
					'indexes'   => null
				),
			),
			'comment'  => ""
		);

		$_struct[ 'comments' ] = array(); // @todo Comments

		$_struct[ 'tags' ] = array(); // @todo Tags

		$_struct[ 'connector_repo' ] = array(
			'col_info' => array(
				'id'     => array(
					'type'      => "int",
					'length'    => 10,
					'collation' => null,
					'attribs'   => "unsigned",
					'is_null'   => false,
					'default'   => null,
					'extra'     => "auto_increment",
					'indexes'   => array(
						'PRIMARY' => true
					),
				),
				'ref_id' => array(
					'type'      => "int",
					'length'    => 10,
					'collation' => null,
					'attribs'   => "unsigned",
					'is_null'   => false,
					'default'   => null,
					'extra'     => null,
				),
			),
		);

		return $_struct[ $suffix ];
	}


	/**
	 * Simple DELETE query
	 *
	 * @param      array                    $sql    array( "do"=>"delete", "table"=>"" , "where"=>array() )
	 *
	 * @return     boolean|int                      # of affected [deleted] rows on success, FALSE otherwise
	 * @throws     \Persephone\Exception
	 */
	protected final function simple_delete_query ( $sql )
	{
		# "From"
		if ( !empty( $sql[ 'table' ] ) )
		{
			$table = $this->attach_prefix( $sql[ 'table' ] );
		}
		else
		{
			throw new \Persephone\Exception( __METHOD__ . " says: No or bad table references specified for DELETE query!" );
		}

		# "Where"
		$where = array();
		if ( isset( $sql[ 'where' ] ) )
		{
			# array-of-strings VS just a plain string
			if ( !is_array( $sql[ 'where' ] ) and !empty( $sql[ 'where' ] ) )
			{
				$where[ ] = $sql[ 'where' ];
			}
			elseif ( is_array( $sql[ 'where' ] ) and count( $sql[ 'where' ] ) )
			{
				$where = $sql[ 'where' ];
			}
		}

		if ( count( $where ) )
		{
			$delete = $this->sql->delete( $table, $where );

			( IN_DEV and $this->cur_query = $this->sql->getSqlStringForSqlObject( $delete ) );  // For debug

			$statement = $this->sql->prepareStatementForSqlObject( $delete );
		}
		else
		{
			( IN_DEV and $this->cur_query = "TRUNCATE TABLE " . $table );  // For debug

			$statement = $this->adapter->getDriver()->createStatement( "TRUNCATE TABLE " . $table );
		}

		\Persephone\Core\Registry::logger__do_log( $this->cur_query, "DEBUG" );

		$result = $statement->execute();
		if ( $result instanceof \Zend\Db\Adapter\Driver\ResultInterface and $result->isQueryResult() )
		{
			$resultSet = new \Zend\Db\ResultSet\ResultSet();
			$return = $resultSet->initialize( $result )->count();

			return $return;
		}

		return $result;
	}


	/**
	 * Simple INSERT query
	 *
	 * @param     array      array( "do"=>"insert", "table"=>"", "set"=>array() )
	 *
	 * @return    integer    # of affected rows
	 */
	protected function simple_insert_query ( $sql )
	{
		# "Into"
		if ( !empty( $sql[ 'table' ] ) )
		{
			$table = $this->attach_prefix( $sql[ 'table' ] );
		}
		else
		{
			throw new \Persephone\Exception( __METHOD__ . " says: No or bad table references specified for INSERT query!" );
		}

		# Data
		if ( isset( $sql[ 'set' ] ) and is_array( $sql[ 'set' ] ) and count( $sql[ 'set' ] ) )
		{
			$data =& $sql[ 'set' ];
		}
		else
		{
			throw new \Persephone\Exception( __METHOD__ . " says: No data specified for INSERT query!" );
		}

		# EXEC
		$insert = $this->sql->insert( $table, $data );
		\Persephone\Core\Registry::logger__do_log( $this->cur_query = $this->sql->getSqlStringForSqlObject( $insert ), "DEBUG" );

		return $this->adapter->getDriver()->getConnection()->getLastGeneratedValue();
	}


	/**
	 * Simple REPLACE query
	 *
	 * @param       array                       array( "do"=>"replace", "table"=>"", "set"=>array( associative array of column_name => value pairs , ... , ... ) )
	 *
	 * @return      integer|boolean             # of affected rows on success, FALSE otherwise
	 * @throws      \Persephone\Exception
	 */
	protected final function simple_replace_query ( $sql )
	{
		# "Into"
		if ( isset( $sql[ 'table' ] ) and !empty( $sql[ 'table' ] ) )
		{
			$table = $this->attach_prefix( $sql[ 'table' ] );
		}
		else
		{
			throw new \Persephone\Exception( __METHOD__ . " says: No or bad table references specified for REPLACE query!" );
		}

		# "SET"
		if ( !isset( $sql[ 'set' ] ) or !is_array( $sql[ 'set' ] ) or !count( $sql[ 'set' ] ) )
		{
			throw new \Persephone\Exception( __METHOD__ . " says: No data specified for REPLACE query!" );
		}
		$_set = array();
		foreach ( $sql[ 'set' ] as $_col => $_val )
		{
			if ( $_val instanceof Expression )
			{
				$_val = $_val->getExpression();
				unset( $sql[ 'set' ][ $_col ] );
			}
			else
			{
				$_val = "?";
			}
			$_set[ ] = $this->platform->quoteIdentifier( $_col ) . ' = ' . $_val;
		}

		//------------------------------
		// Build the REPLACE statement
		//------------------------------

		$this->cur_query = "REPLACE INTO " . $table . " SET " . implode( ", ", $_set );
		\Persephone\Core\Registry::logger__do_log( $this->cur_query, "DEBUG" );

		//----------------------------------------------------------------
		// Execute the statement and return the number of affected rows
		//----------------------------------------------------------------

		$statement = $this->adapter->query( $this->cur_query );
		$result = $statement->execute( array_values( $sql[ 'set' ] ) );

		return $result->count();
	}


	/**
	 * Simple SELECT query
	 *
	 * @param       array
	 *
	 * @usage       array(
						"do"          => "select",
						"distinct"    => TRUE | FALSE,           - enables you to add the DISTINCT  keyword to your SQL query
						"fields"      => array(),
						"table"       => array() [when correlation names are used] | string,
						"where"       => "" | array( array() ),  - multidimensional array, containing conditions and possible parameters for placeholders
						"add_join"    => array(
							0 => array (
								"fields"      => array(),
								"table"       => array(),    - where count = 1
								"conditions"  => "",
								"join_type"   => "INNER|LEFT|RIGHT"
							),
							1 => array()
						),
						"group"       => array(),
						"having"      => array(),
						"order"       => array(),
						"limit"       => array(offset, count)
					)
	 * @throws      \Persephone\Exception
	 * @return      array|boolean
	 */
	protected final function simple_select_query ( $sql )
	{
		$select = $this->sql->select();

		# Columns
		$fields = array();
		if ( isset( $sql[ 'fields' ] ) and !empty( $sql[ 'fields' ] ) )
		{
			if ( is_array( $sql[ 'fields' ] ) )
			{
				$fields = $sql[ 'fields' ];
			}
			elseif ( is_string( $sql[ 'fields' ] ) )
			{
				$fields = array( $sql[ 'fields' ] );
			}
		}
		else
		{
			$fields = array();
		}
		if ( count( $fields ) )
		{
			$select->columns( $fields );
		}

		# "From"
		if ( isset( $sql[ 'table' ] ) and !empty( $sql[ 'table' ] ) )
		{
			$tables = $this->attach_prefix( $sql[ 'table' ] );
		}
		else
		{
			throw new \Persephone\Exception( __METHOD__ . " says: No or bad table references specified for SELECT query!" );
		}
		if ( isset( $sql[ 'distinct' ] ) and $sql[ 'distinct' ] === true )
		{
			$select->quantifier( "DISTINCT" );
		}
		$select->from( $tables );

		# "Where"
		$where = array();
		if ( isset( $sql[ 'where' ] ) and !empty( $sql[ 'where' ] ) )
		{
			# Backward compatibility
			if ( !is_array( $sql[ 'where' ] ) )
			{
				$where = array( $sql[ 'where' ] );
			}
			else
			{
				$where = $sql[ 'where' ];
			}
		}
		if ( count( $where ) ) // Apply only if there is a need
		{
			$select->where( $where );
		}

		# "Join"
		if ( isset( $sql[ 'add_join' ] ) and count( $sql[ 'add_join' ] ) )
		{
			foreach ( $sql[ 'add_join' ] as $add_join )
			{
				$join_table      = array();
				$join_conditions = array();

				# "Join" table
				if ( isset( $add_join[ 'table' ] ) )
				{
					if ( is_array( $add_join[ 'table' ] ) and count( $add_join[ 'table' ] ) )
					{
						$join_table = array_merge( $join_table, $this->attach_prefix( $add_join[ 'table' ] ) );
					}
					else
					{
						$join_table = array_merge( $join_table, array( $this->attach_prefix( $add_join[ 'table' ] ) ) );
					}
				}
				else
				{
					throw new \Persephone\Exception( __METHOD__ . " says: No table references specified for JOIN clause in SELECT query" );
					continue; // Failed "Join", continue to the next one...
				}

				# "Join" conditions
				if ( isset( $add_join[ 'conditions' ] ) and !empty( $add_join[ 'conditions' ] ) )
				{
					$join_conditions = $add_join[ 'conditions' ];
				}
				else
				{
					if ( $add_join[ 'join_type' ] != 'CROSS' and $add_join[ 'join_type' ] != 'NATURAL' )
					{
						throw new \Persephone\Exception( __METHOD__ . " says: No conditions specified for JOIN clause in SELECT query" );
						continue; // Failed "Join", continue to the next one...
					}
				}

				# "Join" fields
				$join_fields = array();
				if ( isset( $add_join[ 'fields' ] ) and $add_join[ 'fields' ] )
				{
					if ( is_array( $add_join[ 'fields' ] ) )
					{
						if ( count( $add_join[ 'fields' ] ) )
						{
							$join_fields = $add_join[ 'fields' ];
						}
						else
						{
							$join_fields = array();
						}
					}
					else
					{
						$join_fields = array( $add_join[ 'fields' ] );
					}
				}

				# "Join" finalize...

				switch ( $add_join[ 'join_type' ] )
				{
					case 'INNER':
						$select->join( $join_table, $join_conditions, $join_fields, $select::JOIN_INNER );
						break;

					case 'LEFT':
						$select->join( $join_table, $join_conditions, $join_fields, $select::JOIN_LEFT );
						break;

					case 'RIGHT':
						$select->join( $join_table, $join_conditions, $join_fields, $select::JOIN_RIGHT );
						break;

					default:
						$select->join( $join_table, $join_conditions, $join_fields );
						break;
				}
			}
		}

		# "Group By"
		$group  = array();
		$having = array();
		if ( isset( $sql[ 'group' ] ) and !empty( $sql[ 'group' ] ) )
		{
			if ( is_array( $sql[ 'group' ] ) )
			{
				$group = $sql[ 'group' ];
			}
			else
			{
				$group = array( $sql[ 'group' ] );
			}

			# "Having"
			if ( isset( $sql[ 'having' ] ) and !empty( $sql[ 'having' ] ) )
			{
				if ( is_array( $sql[ 'having' ] ) )
				{
					$having = $sql[ 'having' ];
				}
				else
				{
					$having = array( $sql[ 'having' ] );
				}
			}
		}
		if ( count( $group ) ) // Apply only if there is a need
		{
			$select->group( $group );
		}
		if ( count( $having ) ) // Apply only if there is a need
		{
			$select->having( $having );
		}

		# "Order By"
		$order = array();
		if ( isset( $sql[ 'order' ] ) and !empty( $sql[ 'order' ] ) )
		{
			if ( is_array( $sql[ 'order' ] ) )
			{
				$order = $sql[ 'order' ];
			}
			else
			{
				$order = array( $sql[ 'order' ] );
			}
		}
		if ( count( $order ) ) // Apply only if there is a need
		{
			$select->order( $order );
		}

		# "Limit"
		if ( $sql[ 'do' ] == 'select_row' )
		{
			$select->limit( 1 )->offset( 0 );
		}
		elseif ( isset( $sql[ 'limit' ] ) and is_array( $sql[ 'limit' ] ) and count( $sql[ 'limit' ] ) == 2 )
		{
			$select->limit( intval( $sql[ 'limit' ][ 1 ] ) )->offset( intval( $sql[ 'limit' ][ 0 ] ) );
		}

		# EXEC

		\Persephone\Core\Registry::logger__do_log( $this->cur_query = $this->sql->getSqlStringForSqlObject( $select ), "DEBUG" );

		$statement = $this->sql->prepareStatementForSqlObject( $select );
		$result = $statement->execute();

		if ( $result instanceof \Zend\Db\Adapter\Driver\ResultInterface and $result->isQueryResult() )
		{
			$resultSet = new \Zend\Db\ResultSet\ResultSet();
			return $resultSet->initialize( $result )->toArray();
		}

		return $result;
	}


	/**
	 * Simple UPDATE query [w/ MULTITABLE UPDATE support]
	 *
	 * @param   $sql               array
	 * @usage   array(
					"do"        => "update",
					"tables"    => string|string[] [elements can be key=>value pairs ("table aliases") or strings],
					"set"       => assoc array of column_name-value pairs
					"where"     => string|string[]
				)
	 *
	 * @return  integer|boolean    # of affected rows on success, FALSE otherwise
	 * @throws  \Persephone\Exception
	 */
	protected final function simple_update_query ( $sql )
	{
		//----------------
		// Asset-check
		//----------------

		if ( !isset( $sql[ 'tables' ] ) or !count( $sql[ 'tables' ] ) )
		{
			return false;
		}
		if ( !count( $sql[ 'set' ] ) )
		{
			return false;
		}

		//----------
		// Tables
		//----------

		$_tables = array();
		if ( !is_array( $sql[ 'tables' ] ) )
		{
			$sql[ 'tables' ] = array( $sql[ 'tables' ] );
		}
		foreach ( $sql[ 'tables' ] as $_table )
		{
			# If "table name aliases" are used
			if ( is_array( $_table ) and count( $_table ) )
			{
				foreach ( $_table as $_alias => $_table_name )
				{
					if ( is_numeric( $_alias ) )
					{
						$_tables[ ] = $this->platform->quoteIdentifier( $this->attach_prefix( $_table_name ) );
					}
					else
					{
						$_tables[ ] = $this->platform->quoteIdentifierInFragment( $this->attach_prefix( $_table_name ) . " AS " . $_alias );
					}
				}
			}
			# If its just an array of strings - i.e. no "table name aliases"
			else
			{
				$_tables[ ] = $this->platform->quoteIdentifier( $this->attach_prefix( $_table ) );
			}
		}

		//---------
		// "SET"
		//---------

		$_set = array();
		foreach ( $sql[ 'set' ] as $_col => $_val )
		{
			if ( $_val instanceof Expression )
			{
				$_val = $_val->getExpression();
				unset( $sql[ 'set' ][ $_col ] );
			}
			else
			{
				$_val = "?";
			}
			$_set[ ] = $this->platform->quoteIdentifier( $_col ) . ' = ' . $this->adapter->driver->formatParameterName( $_val );
		}

		//-----------
		// "WHERE"
		//-----------

		$_where = !is_array( $sql[ 'where' ] )
			? array( $sql[ 'where' ] )
			: $sql[ 'where' ];

		foreach ( $_where as $_cond => &$_term )
		{
			# is $_cond an int? (i.e. not a condition, rather a numeric index)
			if ( is_int( $_cond ) )
			{
				# $_term is the full condition
				if ( $_term instanceof Expression )
				{
					$_term = $_term->getExpression();
				}
			}
			else
			{
				# $_cond is the condition with placeholder,
				# and $_term is quoted into the condition
				if ( strpos( $_cond, "?" ) !== false )
				{
					# We always have one placeholder and its corresponding value, that's why we don't str_replace() all question marks, but just one.
					$_term = substr_replace( $_cond, $this->quoteValue( $_term ), strpos( $_cond, "?" ), 1 );
				}
				else
				{
					throw new \Persephone\Exception( __METHOD__ . " says: Invalid condition struct in WHERE clause, couldn't parse the query! ");
				}
			}
			$_term = '(' . $_term . ')';
		}

		$_where = implode( ' AND ', $_where );

		//------------------------------
		// Build the UPDATE statement
		//------------------------------

		$this->cur_query = "UPDATE " . implode( ", ", $_tables ) . " SET " . implode( ", ", $_set ) .
		                   ( $_where
			                   ? " WHERE " . $_where
			                   : "" );

		\Persephone\Core\Registry::logger__do_log( $this->cur_query, "DEBUG" );

		/**
		 * @var $statement \Zend\Db\Adapter\Driver\StatementInterface
		 */
		$statement = $this->adapter->getDriver()->createStatement( $this->cur_query );

		//----------------------------------------------------------------
		// Execute the statement and return the number of affected rows
		//----------------------------------------------------------------

		$result = $statement->execute( $sql['set'] );

		if ( $result instanceof \Zend\Db\Adapter\Driver\ResultInterface and $result->isQueryResult() )
		{
			$resultSet = new \Zend\Db\ResultSet\ResultSet();
			$return = $resultSet->initialize( $result )->count();

			return $return;
		}

		return $result;
	}


	/**
	 * Simple ALTER TABLE query
	 *
	 * @param   $sql        array
	 * @usage   array(
					"do"          => "alter",
					"table"       => string,
					"action"      => "add_column"|"drop_column"|"change_column"|"add_key"
					"col_info"    => column info to parse
				)
	 *
	 * @return              mixed      # of affected rows on success, FALSE otherwise
	 *
	 */
	protected function simple_alter_table ( $sql )
	{
		if ( !$sql[ 'table' ] )
		{
			return false;
		}

		# Let's clean-up COMMENT a bit
		if ( isset( $sql[ 'comment' ] ) )
		{
			$sql[ 'comment' ] = html_entity_decode( $sql[ 'comment' ], ENT_QUOTES, "UTF-8" ); // Decoding characters
			$sql[ 'comment' ] = preg_replace( "/'{2,}/", "'", $sql[ 'comment' ] ); // Excessive single-quotes
			if ( mb_strlen( $sql[ 'comment' ] ) > 60 ) // Truncate here, as you might get trailing single-quotes later on, for comments with strlen() close to 60.
			{
				$sql[ 'comment' ] = mb_substr( $sql[ 'comment' ], 0, 60 );
			}
			$sql[ 'comment' ] = str_replace( "'", "''", $sql[ 'comment' ] ); // Single quotes are doubled in number. This is MySQL syntax!
		}
		else
		{
			$sql[ 'comment' ] = "";
		}

		$this->cur_query = "ALTER TABLE " . $this->platform->quoteIdentifier( $this->attach_prefix( $sql[ 'table' ] ) ) . " ";

		switch ( $sql[ 'action' ] )
		{
			//----------------
			// "ADD COLUMN"
			//----------------

			case 'add_column':
				$this->cur_query .= "ADD ";
				switch ( strtolower( $sql[ 'col_info' ][ 'type' ] ) )
				{
					case 'tinyint':
					case 'smallint':
					case 'mediumint':
					case 'int':
					case 'bigint':
					case 'float':
					case 'double':
					case 'decimal':
						$this->cur_query .= "`" . $sql[ 'col_info' ][ 'name' ] . "` " . $sql[ 'col_info' ][ 'type' ] .
						                    ( $sql[ 'col_info' ][ 'length' ]
							                    ? "(" . $sql[ 'col_info' ][ 'length' ] . ")"
							                    : "" ) . " " . $sql[ 'col_info' ][ 'attribs' ] .
						                    ( $sql[ 'col_info' ][ 'is_null' ]
							                    ? " NULL"
							                    : " NOT NULL" ) // . ( $sql['col_info']['extra']            ? " " . $sql['col_info']['extra'] : "" )
						                    .
						                    ( $sql[ 'col_info' ][ 'default' ] !== null
							                    ? " DEFAULT '" . $sql[ 'col_info' ][ 'default' ] . "'"
							                    : " DEFAULT NULL" ) .
						                    ( $sql[ 'col_info' ][ 'comment' ]
							                    ? " COMMENT '" . $sql[ 'col_info' ][ 'comment' ] . "'"
							                    : "" );
						break;

					case 'varchar':
					case 'char':
					case 'tinytext':
					case 'mediumtext':
					case 'text':
					case 'longtext':
						$this->cur_query .= "`" . $sql[ 'col_info' ][ 'name' ] . "` " . $sql[ 'col_info' ][ 'type' ] .
						                    ( $sql[ 'col_info' ][ 'length' ]
							                    ? "(" . $sql[ 'col_info' ][ 'length' ] . ")"
							                    : "" ) . " collate utf8_unicode_ci" . " " . $sql[ 'col_info' ][ 'attribs' ] .
						                    ( $sql[ 'col_info' ][ 'is_null' ]
							                    ? " NULL"
							                    : " NOT NULL" ) // . ( $sql['col_info']['extra']            ? " " . $sql['col_info']['extra'] : "" )
						                    .
						                    ( $sql[ 'col_info' ][ 'default' ] !== null
							                    ? " DEFAULT '" . $sql[ 'col_info' ][ 'default' ] . "'"
							                    : " DEFAULT NULL" ) .
						                    ( $sql[ 'col_info' ][ 'comment' ]
							                    ? " COMMENT '" . $sql[ 'col_info' ][ 'comment' ] . "'"
							                    : "" );
						break;
					case 'enum':
					case 'set':
						$this->cur_query .= "`" . $sql[ 'col_info' ][ 'name' ] . "` " . $sql[ 'col_info' ][ 'type' ] .
						                    ( $sql[ 'col_info' ][ 'length' ]
							                    ? "(" . $sql[ 'col_info' ][ 'length' ] . ")"
							                    : "" ) . " collate utf8_unicode_ci" .
						                    ( $sql[ 'col_info' ][ 'is_null' ]
							                    ? " NULL"
							                    : " NOT NULL" ) .
						                    ( $sql[ 'col_info' ][ 'default' ] !== null
							                    ? " DEFAULT '" . $sql[ 'col_info' ][ 'default' ] . "'"
							                    : " DEFAULT NULL" ) .
						                    ( $sql[ 'col_info' ][ 'comment' ]
							                    ? " COMMENT '" . $sql[ 'col_info' ][ 'comment' ] . "'"
							                    : "" );
						break;
				}

				# KEYs

				if ( isset( $sql[ 'col_info' ][ 'indexes' ] ) and is_array( $sql[ 'col_info' ][ 'indexes' ] ) and count( $sql[ 'col_info' ][ 'indexes' ] ) )
				{
					foreach ( $sql[ 'col_info' ][ 'indexes' ] as $k => $v )
					{
						if ( $v === true )
						{
							$this->cur_query .= ", ADD " . $k . " (" . $sql[ 'col_info' ][ 'name' ] . ")";
						}
					}
				}
				break;

			case 'drop_column':
				$i = 0;
				foreach ( $sql[ 'col_info' ] as $col_info )
				{
					$this->cur_query .= ( ( $i == 0 )
						? "DROP `"
						: ", DROP `" ) . $col_info[ 'name' ] . "` ";
					$i++;
				}
				break;

			case 'change_column':
				$i = 0;
				foreach ( $sql[ 'col_info' ] as $col_info )
				{
					$this->cur_query .= ( ( $i == 0 )
						? "CHANGE `"
						: ", CHANGE `" ) . $col_info[ 'old_name' ] . "` ";
					switch ( strtolower( $col_info[ 'type' ] ) )
					{
						case 'tinyint':
						case 'smallint':
						case 'mediumint':
						case 'int':
						case 'bigint':
						case 'float':
						case 'double':
						case 'decimal':
							$this->cur_query .= "`" . $col_info[ 'name' ] . "` " . $col_info[ 'type' ] .
							                    ( $col_info[ 'length' ]
								                    ? "(" . $col_info[ 'length' ] . ")"
								                    : "" ) . " " . $col_info[ 'attribs' ] .
							                    ( $col_info[ 'is_null' ]
								                    ? " NULL"
								                    : " NOT NULL" ) // . ( $col_info['extra']            ? " " . $col_info['extra'] : "" )
							                    .
							                    ( $col_info[ 'default' ] !== null
								                    ? " DEFAULT '" . $col_info[ 'default' ] . "'"
								                    : " DEFAULT NULL" ) .
							                    ( $col_info[ 'comment' ]
								                    ? " COMMENT '" . $col_info[ 'comment' ] . "'"
								                    : "" );
							break;

						case 'varchar':
						case 'char':
						case 'tinytext':
						case 'mediumtext':
						case 'text':
						case 'longtext':
							$this->cur_query .= "`" . $col_info[ 'name' ] . "` " . $col_info[ 'type' ] .
							                    ( $col_info[ 'length' ]
								                    ? "(" . $col_info[ 'length' ] . ")"
								                    : "" ) . " collate utf8_unicode_ci" . " " . $col_info[ 'attribs' ] .
							                    ( $col_info[ 'is_null' ]
								                    ? " NULL"
								                    : " NOT NULL" ) // . ( $col_info['extra']            ? " " . $col_info['extra'] : "" )
							                    .
							                    ( $col_info[ 'default' ] !== null
								                    ? " DEFAULT '" . $col_info[ 'default' ] . "'"
								                    : " DEFAULT NULL" ) .
							                    ( $col_info[ 'comment' ]
								                    ? " COMMENT '" . $col_info[ 'comment' ] . "'"
								                    : "" );
							break;
						case 'enum':
						case 'set':
							$this->cur_query .= "`" . $col_info[ 'name' ] . "` " . $col_info[ 'type' ] .
							                    ( $col_info[ 'length' ]
								                    ? "(" . $col_info[ 'length' ] . ")"
								                    : "" ) . " collate utf8_unicode_ci" .
							                    ( $col_info[ 'is_null' ]
								                    ? " NULL"
								                    : " NOT NULL" ) .
							                    ( $col_info[ 'default' ] !== null
								                    ? " DEFAULT '" . $col_info[ 'default' ] . "'"
								                    : " DEFAULT NULL" ) .
							                    ( $col_info[ 'comment' ]
								                    ? " COMMENT '" . $col_info[ 'comment' ] . "'"
								                    : "" );
							break;
					}

					# KEYs

					if ( isset( $col_info[ 'indexes' ] ) and is_array( $col_info[ 'indexes' ] ) and count( $col_info[ 'indexes' ] ) )
					{
						foreach ( $col_info[ 'indexes' ] as $k => $v )
						{
							if ( $v === true )
							{
								$this->cur_query .= ", ADD " . $k . " (" . $col_info[ 'name' ] . ")";
							}
						}
					}

					$i++;
				}
				break;

			//-------------
			// "COMMENT"
			//-------------

			case 'comment':
				$this->cur_query .= " COMMENT '" . $sql[ 'comment' ] . "'";
				break;
		}

		\Persephone\Core\Registry::logger__do_log( $this->cur_query, "DEBUG" );

		$statement = $this->adapter->query( $this->cur_query );
		$result = $statement->execute();

		if ( $result instanceof \Zend\Db\Adapter\Driver\ResultInterface and $result->isQueryResult() )
		{
			$resultSet = new \Zend\Db\ResultSet\ResultSet();
			$return = $resultSet->initialize( $result )->count();

			return $return;
		}

		return $result;
	}


	/**
	 * Drops table(s)
	 *
	 * @param       string[]            $tables     List of tables to be dropped
	 *
	 * @return      integer|boolean                 # of affected rows on success, FALSE otherwise
	 */
	public function simple_exec_drop_table ( $tables )
	{
		//-----------------
		// Build and exec
		//-----------------

		if ( !is_array( $tables ) )
		{
			return false;
		}
		else
		{
			if ( !count( $tables ) )
			{
				return false;
			}
			else
			{
				$tables = $this->attach_prefix( $tables );
				foreach ( $tables as &$table )
				{
					$table = $this->platform->quoteIdentifier( $table );
				}
				$this->cur_query = "DROP TABLE IF EXISTS " . implode( ", ", $tables );
			}
		}

		\Persephone\Core\Registry::logger__do_log( $this->cur_query, "DEBUG" );

		$statement = $this->adapter->query( $this->cur_query );
		$result = $statement->execute();

		if ( $result instanceof \Zend\Db\Adapter\Driver\ResultInterface and $result->isQueryResult() )
		{
			$resultSet = new \Zend\Db\ResultSet\ResultSet();
			$return = $resultSet->initialize( $result )->count();

			return $return;
		}

		return $result;
	}


	/**
	 * Builds "CREATE TABLE ..." query from Table-Structure Array and executes it
	 *
	 * @param       array       $struct     Struct array
	 *
	 * @return      integer                 # of queries executed
	 */
	public function simple_exec_create_table_struct ( $struct )
	{
		if ( !count( $struct ) )
		{
			return 0;
		}

		//-----------------
		// Build and exec
		//-----------------

		$return = 0;
		foreach ( $struct[ 'tables' ] as $table => $data )
		{
			//------------------------
			// DROP TABLE IF EXISTS
			//------------------------

			$this->simple_exec_drop_table( array( $table ) );

			//--------------------------------
			// Build CREATE TABLE statement
			//--------------------------------

			$this->cur_query = "CREATE TABLE IF NOT EXISTS " . $this->platform->quoteIdentifier( $this->attach_prefix( $table ), true ) . " (\n";

			# CREATE TABLE...
			# Reset KEY data
			$key = array();

			foreach ( $data[ 'col_info' ] as $_column_name => $_column_struct )
			{
				# Handling columns
				switch ( strtolower( $_column_struct[ 'type' ] ) )
				{
					case 'tinyint':
					case 'smallint':
					case 'mediumint':
					case 'int':
					case 'bigint':
					case 'float':
					case 'double':
					case 'decimal':
						$this->cur_query .= $this->platform->quoteIdentifier( $_column_name, true ) . " " . $_column_struct[ 'type' ] .
						                    ( $_column_struct[ 'length' ]
							                    ? "(" . $_column_struct[ 'length' ] . ")"
							                    : "" ) . " " . $_column_struct[ 'attribs' ] .
						                    ( $_column_struct[ 'is_null' ]
							                    ? " NULL"
							                    : " NOT NULL" ) .
						                    ( $_column_struct[ 'extra' ]
							                    ? " " . $_column_struct[ 'extra' ]
							                    : "" ) .
						                    ( $_column_struct[ 'default' ] !== null
							                    ? " DEFAULT '" . $_column_struct[ 'default' ] . "'"
							                    : "" ) . ",\n";
						break;

					case 'varchar':
					case 'char':
					case 'tinytext':
					case 'mediumtext':
					case 'text':
					case 'longtext':
						$this->cur_query .= $this->platform->quoteIdentifier( $_column_name ) . " " . $_column_struct[ 'type' ] .
						                    ( $_column_struct[ 'length' ]
							                    ? "(" . $_column_struct[ 'length' ] . ")"
							                    : "" ) . " collate utf8_unicode_ci" . " " . $_column_struct[ 'attribs' ] .
						                    ( $_column_struct[ 'is_null' ]
							                    ? " NULL"
							                    : " NOT NULL" ) .
						                    ( $_column_struct[ 'extra' ]
							                    ? " " . $_column_struct[ 'extra' ]
							                    : "" ) .
						                    ( $_column_struct[ 'default' ] !== null
							                    ? " DEFAULT '" . $_column_struct[ 'default' ] . "'"
							                    : "" ) . ",\n";
						break;
				}

				# Handling KEYs
				if ( isset( $_column_struct[ 'indexes' ] ) and is_array( $_column_struct[ 'indexes' ] ) and count( $_column_struct[ 'indexes' ] >= 1 ) )
				{
					foreach ( $_column_struct[ 'indexes' ] as $k => $v )
					{
						if ( $v === true )
						{
							$key[ ] = $k . " KEY " . $this->platform->quoteIdentifier( $_column_name ) . "(" . $this->platform->quoteIdentifier( $_column_name ) . ")";
						}
						# $v is index_type, e.g. "USING {BTREE | HASH}"
						else
						{
							$key[ ] = $k . " KEY " . $v . " " . $this->platform->quoteIdentifier( $_column_name ) . "(" . $this->platform->quoteIdentifier( $_column_name ) . ")";
						}
					}
				}
			}

			# Attaching KEYs to the rest of query
			$this->cur_query .= implode( ",\n", $key ) . "\n";

			# Finalizing CREATE TABLE ... query
			$data[ 'storage_engine' ] = ( isset( $data[ 'storage_engine' ] ) and !empty( $data[ 'storage_engine' ] ) )
				? $data[ 'storage_engine' ]
				: "MyISAM";
			$data[ 'charset' ]        = ( isset( $data[ 'charset' ] ) and !empty( $data[ 'charset' ] ) )
				? $data[ 'charset' ]
				: "utf8";
			$data[ 'collate' ]        = ( isset( $data[ 'collate' ] ) and !empty( $data[ 'collate' ] ) )
				? $data[ 'collate' ]
				: "utf8_unicode_ci";

			# Let's clean-up COMMENT a bit
			if ( isset( $data[ 'comment' ] ) )
			{
				$data[ 'comment' ] = html_entity_decode( $data[ 'comment' ], ENT_QUOTES, "UTF-8" ); // Decoding characters
				$data[ 'comment' ] = preg_replace( "/'{2,}/", "'", $data[ 'comment' ] ); // Excessive single-quotes
				if ( mb_strlen( $data[ 'comment' ] ) > 60 ) // Truncate here, as you might get trailing single-quotes later on, for comments with strlen() close to 60.
				{
					$data[ 'comment' ] = mb_substr( $data[ 'comment' ], 0, 60 );
				}
				$data[ 'comment' ] = str_replace( "'", "''", $data[ 'comment' ] ); // Escaping single quotes within comments...
			}
			else
			{
				$data[ 'comment' ] = "";
			}

			$this->cur_query .=
				") ENGINE=" . $data[ 'storage_engine' ] . " DEFAULT CHARACTER SET=" . $data[ 'charset' ] . " COLLATE=" . $data[ 'collate' ] . " COMMENT='" . $data[ 'comment' ] . "';\n\n";

			# Execute
			\Persephone\Core\Registry::logger__do_log( $this->cur_query, "DEBUG" );

			$statement = $this->adapter->query( $this->cur_query );
			$result = $statement->execute();

			if ( $result instanceof \Zend\Db\Adapter\Driver\ResultInterface and $result->isQueryResult() )
			{
				$resultSet = new \Zend\Db\ResultSet\ResultSet();
				$return += $resultSet->initialize( $result )->count();
			}

			$return++;
		}

		return $return;
	}
}