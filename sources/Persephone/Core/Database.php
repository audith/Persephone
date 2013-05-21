<?php

namespace Persephone\Core;

use \Zend\Db\Adapter\Adapter;
use \Zend\Db\Adapter\Driver\ConnectionInterface;
use \Zend\Db\Sql\Expression;
use Zend\Db\Sql\SqlInterface;

if ( !defined( "INIT_DONE" ) )
{
	die( "Improper access! Exiting now..." );
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
	 * Registry reference
	 *
	 * @var \Persephone\Core\Registry
	 */
	protected $Registry;

	/**
	 * \Zend\Db\Adapter\Adapter object
	 *
	 * @var \Zend\Db\Adapter\Adapter
	 */
	public $adapter;

	/**
	 * Current query
	 *
	 * @var array
	 */
	public $cur_query = array();

	/**
	 * Options specific to this driver
	 *
	 * @var array
	 */
	public $driver_options = array();

	/**
	 * Toggle telling to execute shutdown queries during shutdown
	 *
	 * @var boolean
	 */
	protected $is_shutdown = false;

	/**
	 * Zend-Db Adapter object
	 *
	 * @var \Zend\Db\Adapter\Platform\Mysql
	 */
	public $platform;

	/**
	 * SQL query count (for Debug purposes)
	 *
	 * @var integer
	 */
	public $query_count = 0;

	/**
	 * List of all SQL queries executed (for Debug purposes)
	 *
	 * @var integer
	 */
	public $query_list = array();

	/**
	 * Queries to be run during shutdown
	 *
	 * @var array
	 */
	protected $shutdown_queries = array();

	/**
	 * SQL abstraction layer
	 *
	 * @var \Zend\Db\Sql\Sql
	 */
	public $sql;

	/**
	 * Usage of shutdown queries allowed
	 *
	 * @var boolean
	 */
	public $use_shutdown = true;


	/**
	 * Magic method __toString()
	 *
	 * @return                       string|null                 String-ifyed query
	 * @throws                       \Persephone\Exception
	 */
	public function __toString ()
	{
		if ( !IN_DEV )
		{
			throw new \Persephone\Exception ( "Can't dump SQL queries outside Development mode! Please activate Development mode [/init.php - IN_DEV setting]..." );
		}

		if ( $this->cur_query instanceof SqlInterface )
		{
			return $this->sql->getSqlStringForSqlObject( $this->cur_query );
		}
		elseif ( is_string( $this->cur_query ) and !empty( $this->cur_query ) )
		{
			return $this->cur_query;
		}

		return "";
	}


	/**
	 * Destructor
	 */
	public function _my_destruct ()
	{
		# Run shutdown queries
		$this->use_shutdown                                     = false;
		$_problematic_queries_during_simple_exec_query_shutdown = $this->simple_exec_query_shutdown();
		if ( count( $_problematic_queries_during_simple_exec_query_shutdown ) )
		{
			$message = "MESSAGE: Problems occured during Database::simple_exec_query_shutdown().";
			$message .= "\nDUMP: " . var_export( $_problematic_queries_during_simple_exec_query_shutdown, true ) . "\n\n";
			$this->Registry->logger__do_log( $message, "ERROR" );
		}

		$this->Registry->logger__do_log( __CLASS__ . "::__destruct: Destroying class", "INFO" );
	}


	/**
	 * Attaches DB table name prefix to the default table name
	 *
	 * @param     string|array    Table name(s) as string (array)
	 *
	 * @return    string|array    New names with an attached prefix
	 */
	public function attach_prefix ( $t )
	{
		is_array( $t )
			? array_walk( $t, array( $this, "attach_prefix" ) )
			: $t = $this->Registry->config[ 'sql' ][ 'table_prefix' ] . $t;

		return $t;
	}


	/**
	 * Initiates a transaction
	 *
	 * @return      \Zend\Db\Adapter\Driver\ConnectionInterface
	 */
	public function begin_transaction ()
	{
		return $this->adapter->getDriver()->getConnection()->beginTransaction();
	}


	/**
	 * Marks changes made during the transaction as committed
	 *
	 * @return      \Zend\Db\Adapter\Driver\ConnectionInterface
	 */
	public function commit ()
	{
		return $this->adapter->getDriver()->getConnection()->commit();
	}


	/**
	 * Discards (rolls-back) the changes made during the transaction
	 *
	 * @return      \Zend\Db\Adapter\Driver\ConnectionInterface
	 */
	public function rollback ()
	{
		return $this->adapter->getDriver()->getConnection()->rollback();
	}


	/**
	 * The last value generated in the scope of the current database connection
	 *
	 * @return      integer   LAST_INSERT_ID
	 * @throws      \Persephone\Exception
	 */
	public function last_insert_id ()
	{
		if ( !is_object( $this->adapter ) or !( $this->adapter instanceof Adapter ) )
		{
			throw new \Persephone\Exception( __METHOD__ . " says: Database adapter not initialized!" );
		}

		return $this->adapter->getDriver()->getConnection()->getLastGeneratedValue();
	}


	/**
	 * Determines the referenced tables, and the count of referenced rows (latter is on-demand)
	 *
	 * @param       $referenced_table_name      string      Referenced table name
	 * @param       $_params                    array       Parameters containing information for querying referenced data statistics
	 *
	 * @usage       array( '_do_count' => true|false, 'referenced_column_name' => '<column_name>', 'value_to_check' => <key_to_check_against> )
	 * @return                                  array       Reference and possibly, data statistics information (row-count)
	 */
	abstract public function check_for_references ( $referenced_table_name, $_params = array() );


	/**
	 * Prepares column-data for ALTER query for a given module data-field-type
	 *
	 * @param       $df_data                            array       Data-field info
	 * @param       $we_need_this_for_master_table      boolean     Whether translated info will be applied to "_master_repo" tables or not (related to Connector-enabled fields only!)
	 *
	 * @return                                          array       Column info
	 */
	abstract public function modules__ddl_column_type_translation ( $df_data, $we_need_this_for_master_table = false );


	/**
	 * Returns the table structure for any of the module tables
	 *
	 * @param       $suffix     string      Table suffix, determining specific table
	 *
	 * @return                  array       Table structure
	 */
	abstract public function modules__default_table_structure ( $suffix );


	/**
	 * Quotes SQL identifiers before passing them into SQL query.
	 *
	 * @param       $identifier     string|string[]
	 *
	 * @return                      string
	 * @throws                      \Persephone\Exception
	 */
	public function quoteIdentifier ( $identifier )
	{
		if ( !is_object( $this->adapter ) or !( $this->adapter instanceof Adapter ) )
		{
			throw new \Persephone\Exception( __METHOD__ . " says: Database adapter not initialized!" );
		}

		if ( is_array( $identifier ) )
		{
			return $this->platform->quoteIdentifierChain( $identifier );
		}
		else
		{
			return $this->platform->quoteIdentifier( $identifier );
		}
	}


	/**
	 * Quotes values before passing them into SQL query.
	 *
	 * @param       $value      string|string[]
	 *
	 * @return                  string
	 * @throws                  \Persephone\Exception
	 */
	public function quoteValue ( $value )
	{
		if ( !is_object( $this->adapter ) or !( $this->adapter instanceof Adapter ) )
		{
			throw new \Persephone\Exception( __METHOD__ . " says: Database adapter not initialized!" );
		}

		if ( is_array( $value ) )
		{
			return $this->platform->quoteValueList( $value );
		}
		else
		{
			return $this->platform->quoteValue( $value );
		}
	}


	/**
	 * ALIAS to Database::quoteValue()
	 *
	 * @param       $value      string|string[]
	 *
	 * @return                  string
	 * @throws                  \Persephone\Exception
	 * @deprecated
	 */
	public function quote ( $value )
	{
		return $this->quoteValue( $value );
	}


	/**
	 * Simple DELETE query
	 *
	 * @param      array                    array( "do"=>"delete", "table"=>"" , "where"=>array() )
	 *
	 * @return     boolean|int              # of affected [deleted] rows on success, FALSE otherwise
	 * @throws     \Persephone\Exception
	 */
	abstract protected function simple_delete_query ( $sql );


	/**
	 * Simple query
	 *
	 * @return  mixed   $result   Result set for data retrieval queries; # of affected rows for data manipulation queries
	 */
	public function simple_exec_query ()
	{
		# Query counter
		if ( !$this->is_shutdown )
		{
			if ( IN_DEV )
			{
				$this->query_count++;
				$this->query_list[ ] = $this->cur_query;
			}
		}

		//-----------------------------------------------------------------------------------------------------------------------------------------
		// Force data-type: Only works with INSERTs, UPDATEs and REPLACEs (since they are the ones with $this->cur_query['set'] being availabie
		//-----------------------------------------------------------------------------------------------------------------------------------------

		if ( isset( $this->cur_query[ 'set' ] )
		     and
		     count( $this->cur_query[ 'set' ] )
		     and
		     isset( $this->cur_query[ 'force_data_type' ] )
		     and
		     is_array( $this->cur_query[ 'force_data_type' ] )
		     and
		     count( $this->cur_query[ 'force_data_type' ] )
		)
		{
			$_forced_cols = array_keys( $this->cur_query[ 'force_data_type' ] );
			foreach ( $this->cur_query[ 'set' ] as $_k => &$_v )
			{
				if ( in_array( $_k, $_forced_cols ) )
				{
					switch ( $this->cur_query[ 'force_data_type' ][ $_k ] )
					{
						case 'int':
						case 'integer':
							$_v = intval( $_v );
							break;
						case 'float':
							$_v = floatval( $_v );
							break;
						case 'string':
							$_v = strval( $_v );
							break;
						case 'null':
						case null:
							$_v = null;
							break;
					}
				}
			}
		}

		$result = false;
		switch ( $this->cur_query[ "do" ] )
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
		if ( !$this->use_shutdown )
		{
			# Use shutdown mode
			$this->is_shutdown = true;
			$_any_problems     = array();
			if ( is_array( $this->shutdown_queries ) and count( $this->shutdown_queries ) )
			{
				foreach ( $this->shutdown_queries as $query )
				{
					# Exec
					$this->cur_query = $query;
					if ( false === $this->simple_exec_query() )
					{
						$_any_problems[ ] = $query;
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
				$this->query_list[ ] = $this->cur_query;
			}

			# Not a shutdown yet, cache queries
			$this->shutdown_queries[ ] = $this->cur_query;
			$this->cur_query           = array();

			return true;
		}
	}


	/**
	 * Simple INSERT query
	 *
	 * @param     array      array( "do"=>"insert", "table"=>"", "set"=>array() )
	 *
	 * @return    integer    # of affected rows
	 */
	abstract protected function simple_insert_query ( $sql );


	/**
	 * Simple REPLACE query
	 *
	 * @param   array              array( "do"=>"replace", "table"=>"", "set"=>array( associative array of column_name => value pairs , ... , ... ) )
	 *
	 * @return  integer|boolean    # of affected rows on success, FALSE otherwise
	 */
	abstract protected function simple_replace_query ( $sql );


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
	 * @return      \Zend\Db\ResultSet\ResultSet|boolean
	 */
	abstract protected function simple_select_query ( $sql );


	/**
	 * Simple UPDATE query [w/ MULTITABLE UPDATE support]
	 *
	 * @param   array
	 *
	 * @return  integer|boolean    # of affected rows on success, FALSE otherwise
	 * @usage   array(
	"do"        => "update",
	"tables"    => array|string [elements can be key=>value pairs ("table aliases") or strings],
	"set"       => assoc array of column_name-value pairs
	"where"     => array|string
	)
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
	 *
	 * @return   mixed      # of affected rows on success, FALSE otherwise
	 */
	abstract protected function simple_alter_table ( $sql );


	/**
	 * Drops table(s)
	 *
	 * @param       string[]            $tables     List of tables to be dropped
	 *
	 * @return      integer|boolean                 # of affected rows on success, FALSE otherwise
	 */
	abstract public function simple_exec_drop_table ( $tables );


	/**
	 * Builds "CREATE TABLE ..." query from Table-Structure Array and executes it
	 *
	 * @param       array       $struct     Struct array
	 *
	 * @return      integer                 # of queries executed
	 */
	abstract public function simple_exec_create_table_struct ( $struct );
}
