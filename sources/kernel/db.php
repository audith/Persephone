<?php

if ( ! defined( "INIT_DONE" ) )
{
	print "Improper access! Exiting now...";
	exit();
}

/**
 * Database class [Abstraction]
 *
 * @package  Audith CMS codename Persephone
 * @author   Shahriyar Imanov <shehi@imanov.name>
 * @version  1.0
 */
abstract class Database
{
	/**
	 * Current query
	 * @var array
	 */
	public $cur_query = array();

	/**
	 * Toggle telling to execute shutdown queries during shutdown
	 * @var boolean
	 */
	protected $is_shutdown = false;

	/**
	 * SQL query count (for Debug purposes)
	 * @var integer
	 */
	public $query_count = 0;

	/**
	 * List of all SQL queries executed (for Debug purposes)
	 * @var integer
	 */
	public $query_list = array();

	/**
	 * Queries to be run during shutdown
	 * @var array
	 */
	protected $shutdown_queries = array();

	/**
	 * Usage of shutdown queries allowed
	 * @var boolean
	 */
	public $use_shutdown = true;


	/**
	 * Constructor
	 *
	 * @param    object    API Object Reference
	 */
	abstract public function __construct ( API $API );


	/**
	 * Destructor
	 */
	public function _my_destruct ()
	{
		# Run shutdown queries
		$this->use_shutdown = false;
		$_problematic_queries_during_simple_exec_query_shutdown = $this->simple_exec_query_shutdown();
		if ( count( $_problematic_queries_during_simple_exec_query_shutdown ) )
		{
			$message  = "MESSAGE: Problems occured during Database::simple_exec_query_shutdown().";
			$message .= "\nDUMP: " . var_export( $_problematic_queries_during_simple_exec_query_shutdown, true ) . "\n\n";
			$this->API->logger__do_log( $message, "ERROR" );
		}

		$this->API->logger__do_log( __CLASS__ . "::__destruct: Destroying class" , "INFO" );
	}


	/**
	 * Attaches DB table name prefix to the default table name - Build 20080716
	 *
	 * @param   mixed    $t   Table name(s) as string (array)
	 * @return  mixed    $t   New names with an attached prefix
	 */
	public function attach_prefix ( &$t )
	{
		is_array( $t )
			?
			array_walk( $t, array( $this, "attach_prefix" ) )
			:
			$t = $this->API->config['sql']['table_prefix'] . $t;

		return $t;
	}


	/**
	 * DB Disconnect
	 *
	 * @return   bool    TRUE on success, FALSE on failure
	 */
	protected function disconnect ()
	{
		return $this->db->disconnect();
	}


	/**
	 * Initiates a transaction
	 *
	 * @return    object   Zend_Db_Adapter_Abstract object instance
	 */
	public function begin_transaction ()
	{
		return $this->db->beginTransaction();
	}


	/**
	 * Marks changes made during the transaction as committed
	 *
	 * @return    object   Zend_Db_Adapter_Abstract object instance
	 */
	public function commit ()
	{
		return $this->db->commit();
	}


	/**
	 * Discards (rolls-back) the changes made during the transaction
	 *
	 * @return    object   Zend_Db_Adapter_Abstract object instance
	 */
	public function rollback ()
	{
		return $this->db->rollback();
	}


	/**
	 * Build "is null" and "is not null" string
	 *
	 * @param     boolean     is null flag
	 * @return    string      [Optional] SQL-formatted "is null" or "is not null" string
	 */
	abstract public function build__is_null( $is_null = true );


	/**
	 * The last value generated in the scope of the current database connection
	 *
	 * @return   integer   LAST_INSERT_ID
	 */
	abstract public function last_insert_id ();


	/**
	 * Prepares column-data for ALTER query for a given module data-field-type
	 *
	 * @param   array      Data-field info
	 * @param   boolean    Whether translated info will be applied to "_master_repo" tables or not (related to Connector-enabled fields only!)
	 * @return  array      Column info
	 */
	abstract public function modules__ddl_column_type_translation ( $df_data , $we_need_this_for_master_table = false );


	/**
	 * Returns the table structure for any of the module tables
	 *
	 * @param   array   Table suffix, determining specific table
	 * @return  array   Table structure
	 */
	abstract public function modules__default_table_structure ( $suffix );


	/**
	 * Quotes values before passing them into SQL query.
	 *
	 * @param    mixed    Value to quote
	 * @param    string   Type of value
	 * @return   mixed    Quoted value
	 * @throws   Exception
	 */
	public function quote ( $value , $type = null )
	{
		if ( ! is_object( $this->db ) )
		{
			throw new Exception( "Database - quote(): Zend_Db not initialized!" );
			exit();
		}

		return $this->db->quote( $value, $type );
	}


	/**
	 * Simple DELETE query
	 *
	 * @param      array       array( "do"=>"delete", "table"=>"" , "where"=>array() )
	 * @return     integer     # of affected [deleted] rows
	 */
	abstract protected function simple_delete_query ( $sql );


	/**
	 * Simple query
	 *
	 * @param   mixed    $params   Scalar or vectoral data parameter for PEAR query prepare() and exec()
	 * @return  mixed    $result   Result set for data retrieval queries; # of affected rows for data manipulation queries
	 */
	public function simple_exec_query ()
	{
		# Query counter
		if ( ! $this->is_shutdown )
		{
			$this->query_count++;
			if ( IN_DEV )
			{
				$this->query_list[] = $this->cur_query;
			}
		}

		//-----------------------------------------------------------------------------------------------------------------------------------------
		// Force data-type: Only works with INSERTs, UPDATEs and REPLACEs (since they are the ones with $this->cur_query['set'] being availabie
		//-----------------------------------------------------------------------------------------------------------------------------------------

		if (
			isset( $this->cur_query['set'] )
			and
			count( $this->cur_query['set'] )
			and
			isset( $this->cur_query['force_data_type'] )
			and
			is_array( $this->cur_query['force_data_type'] )
			and
			count( $this->cur_query['force_data_type'] )
		)
		{
			$_forced_cols = array_keys( $this->cur_query['force_data_type'] );
			foreach ( $this->cur_query['set'] as $_k=>&$_v )
			{
				if ( in_array( $_k, $_forced_cols ) )
				{
					switch ( $this->cur_query['force_data_type'][ $_k ] )
					{
						case 'int':
							$_v = intval( $_v );
							break;
						case 'float':
							$_v = floatval( $_v );
							break;
						case 'string':
							$_v = strval( $_v );
							break;
						case 'null':
							$_v = new Zend_Db_Expr("NULL");
							break;
					}
				}
			}
		}

		switch ( $this->cur_query["do"] )
		{
			case 'select':
			case 'select_one':
			case 'select_row':
				$result = $this->simple_select_query( $this->cur_query );
				break;

			case 'insert':
				$result = $this->simple_insert_query( $this->cur_query );
				break;

			case 'replace':
				$result = $this->simple_replace_query( $this->cur_query );
				break;

			case 'update':
				$result = $this->simple_update_query( $this->cur_query );
				break;

			case 'delete':
				$result = $this->simple_delete_query( $this->cur_query );
				break;

			case 'alter':
				$result = $this->simple_alter_table( $this->cur_query );
				break;

		}

		# Clear the current query container

		$this->cur_query   = array();
		$this->is_shutdown = false;

		return $result;
	}


	/**
	 * Execute cached shutdown queries
	 *
	 * @return    mixed    Array of problematic queries [empty array if no problems occur]
	 */
	public function simple_exec_query_shutdown ()
	{
		if ( ! $this->use_shutdown )
		{
			# Use shutdown mode
			$this->is_shutdown = true;
			$_any_problems = array();
			if ( is_array( $this->shutdown_queries ) and count( $this->shutdown_queries ) )
			{
				foreach ( $this->shutdown_queries as $query )
				{
					# Exec
					$this->cur_query = $query;
					if ( false === $this->simple_exec_query() )
					{
						$_any_problems[] = $query;
					}
				}
			}

			return $_any_problems;
		}
		else
		{
			# Query counter
			$this->query_count++;
			if ( IN_DEV )
			{
				$this->query_list[] = $this->cur_query;
			}

			# Not a shutdown yet, cache queries
			$this->shutdown_queries[] = $this->cur_query;
			$this->cur_query = array();

			return true;
		}
	}


	/**
	 * Simple INSERT query
	 *
	 * @param     array      array( "do"=>"insert", "table"=>"", "set"=>array() )
	 * @return    integer    # of affected rows
	 */
	abstract protected function simple_insert_query ( $sql );


	/**
	 * Simple REPLACE query
	 *
	 * @param   array    array( "do"=>"replace", "table"=>"", "set"=>array( associative array of column_name => value pairs , ... , ... ) )
	 * @return  void
	 */
	abstract protected function simple_replace_query ( $sql );

	/**
	 * Simple SELECT query
	 *
	 * @param    array    array(
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
											"join_type"   => "INNER|CROSS|LEFT|RIGHT|NATURAL"
												"
										),
										1 => array()
									),
								"group"       => array(),
								"having"      => array(),
								"order"       => array(),
								"limit"       => array(offset, count),
								"limit_page"  => array(page, count)
							)
	 * @return    mixed     Result set
	 */
	abstract protected function simple_select_query ( $sql );


	/**
	 * Simple UPDATE query [w/ MULTITABLE UPDATE support]
	 *
	 * @param    array    array(
	 							"do"          => "update",
								"tables"      => mixed array [elements can be key=>value pairs ("table aliases") or strings],
								"set"         => assoc array of column_name-value pairs
								"where"       => array of strings | string
							)
	 * @return   integer    # of rows affected
	 */
	abstract protected function simple_update_query ( $sql );


	/**
	 * Simple ALTER TABLE query
	 *
	 * @param    array      array(
	 							"do"          => "alter",
								"table"       => string,
								"action"      => "add_column"|"drop_column"|"change_column"|"add_key"
								"col_info"    => column info to parse
							)
	 * @return   mixed      # of affected rows on success, FALSE otherwise
	 */
	abstract protected function simple_alter_table ( $sql );


	/**
	 * Drops table(s)
	 *
	 * @param    array     List of tables to be dropped
	 * @return   mixed     # of affected rows on success, FALSE otherwise
	 */
	abstract public function simple_exec_drop_table ( $tables );


	/**
	 * Builds "CREATE TABLE ..." query from Table-Structure Array and executes it
	 *
	 * @param    array     Struct array
	 * @return   integer   # of queries executed
	 */
	abstract public function simple_exec_create_table_struct ( $struct );
}