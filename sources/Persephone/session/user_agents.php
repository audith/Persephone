<?php

/**
 * Adapted from: IP.Board v3.0.0 by Invision Power Services
 *
 * Last Updated: $Date: 2009-06-08 08:56:58 -0400 (Mon, 08 Jun 2009) $
 *
 * Owner: Matt Mecham
 * Adapted by: Shahriyar Imanov [Audith Softworks - http://www.audith.com]
 * @author      $Author: matt $
 * @copyright   (c) 2001 - 2009 Invision Power Services, Inc.
 * @license     http://www.invisionpower.com/community/board/license.html
 * @package     Invision Power Board
 * @link        http://www.invisionpower.com
 *
 */

if ( ! defined( "INIT_DONE" ) )
{
	print "Improper access! Exiting now...";
	exit();
}

class Session__User_Agents
{
	/**
	 * Registry reference
	 *
	 * @var Registry
	 */
	private $Registry;

	/**
	 * Error handle
	 *
	 * @var array
	 */
	private $_error_messages = array();

	/**
	 * Message handle
	 *
	 * @var array
	 */
	private $_general_messages = array();

	/**
	 * Constructor
	 *
	 * @param    object    REFERENCE: Registry Object Reference
	 * @return   void
	 */
	public function __construct ( Registry $Registry )
	{
		$this->Registry = $Registry;
	}


	/**
	 * Reset error handle
	 *
	 * @return   void
	 */
	private function _reset_error_handle ()
	{
		$this->_error_messages = array();
	}

	/**
	 * Add an error message
	 *
	 * @param    string     Error message to add
	 * @return   void
	 */
	private function add_error_message ( $error )
	{
		$this->_error_messages[] = $error;
	}

	/**
	 * Fetch error messages
	 *
	 * @return    array    Array of messages or FALSE
	 */
	public function fetch_error_messages ()
	{
		return ( count( $this->_error_messages ) ) ? $this->_error_messages : FALSE;
	}

	/**
	 * Reset error handle
	 *
	 * @return    void
	 */
	private function reset_message_handle ()
	{
		$this->_general_messages = array();
	}

	/**
	 * Add an error message
	 *
	 * @param     string    Error message to add
	 * @return    void
	 */
	private function add_message ( $error )
	{
		$this->_general_messages[] = $error;
	}

	/**
	 * Fetch error messages
	 *
	 * @return    mixed    Array of messages or FALSE
	 */
	public function fetch_messages ()
	{
		return ( count( $this->_general_messages ) ) ? $this->_general_messages : FALSE;
	}

	/**
	 * Rebuilds the master user agents
	 *
	 * @return    boolean    true
	 */
	public function rebuild_master_user_agents()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$user_agents = array();
		$names       = array();
		$count       = 0;

		//-----------------------------------------
		// Get file...
		//-----------------------------------------

		require_once( PATH_SOURCES . "/kernel/session/user_agents/master_list.php" );

		//-----------------------------------------
		// Build names
		//-----------------------------------------

		foreach ( $BROWSERS as $key => $data )
		{
			$names[] = "'" . $key . "'";
		}

		foreach ( $ENGINES as $key => $data )
		{
			$names[] = "'" . $key . "'";
		}

		//-----------------------------------------
		// Delete old 'uns
		//-----------------------------------------

		$this->Registry->Db->cur_query = array(
				'do'	 => "delete",
				'table'  => "user_agents",
				'where'  => "uagent_key IN (" . implode( ",", $names ) . ")",
			);
		$this->Registry->Db->simple_exec_query();

		//-----------------------------------------
		// Add new 'uns
		//-----------------------------------------

		foreach ( $ENGINES as $key => $data )
		{
			foreach ( $data['b_regex'] as $k => $d )
			{
				$_regex   = $k;
				$_capture = $d;
			}

			$this->Registry->Db->cur_query = array(
					'do'	 => "insert",
					'table'  => "user_agents",
					'set'    => array(
							'uagent_name'          => $data['b_title'],
							'uagent_key'           => $key,
							'uagent_regex'         => $_regex,
							'uagent_regex_capture' => intval( $_capture ),
							'uagent_position'      => $count,
							'uagent_type'          => "search",
						)
				);
			$this->Registry->Db->simple_exec_query();

			$count++;
		}

		# Reset count
		$count = 1000;

		foreach ( $BROWSERS as $key => $data )
		{
			foreach ( $data['b_regex'] as $k => $d )
			{
				$_regex   = $k;
				$_capture = $d;
			}

			if ( $data['b_position'] )
			{
				$count = $data['b_position'];
			}

			$this->Registry->Db->cur_query = array(
					'do'	 => "insert",
					'table'  => "user_agents",
					'set'    => array(
							'uagent_name'          => $data['b_title'],
							'uagent_key'           => $key,
							'uagent_regex'         => $_regex,
							'uagent_regex_capture' => intval( $_capture ),
							'uagent_position'      => $count,
							'uagent_type'          => "browser",
						)
				);
			$this->Registry->Db->simple_exec_query();

			$count++;
		}

		$this->rebuild_user_agent_caches();

		return TRUE;
	}

	/**
	 * Saves a user agent group
	 *
	 * @param     string    Group Title
	 * @param     array     Array of raw useragent data (array( key => array( 'uagent_id', ..etc ) )
	 * @param     integer   [Optional: Group ID. If passed, we update, if not, we add]
	 * @return    integer   User group id
	 */
	public function save_user_agent_group ( $ugroup_title, $ugroup_data, $ugroup_id=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$data = array();

		//-----------------------------------------
		// Fix up data
		//-----------------------------------------

		if ( is_array( $ugroup_data ) )
		{
			foreach ( $ugroup_data as $key => $array )
			{
				$data[ $key ] = array( 'uagent_id'       => $array['uagent_id'],
									   'uagent_key'      => $array['uagent_key'],
									   'uagent_type'     => $array['uagent_type'],
									   'uagent_versions' => $array['uagent_versions'] );
			}
		}

		//-----------------------------------------
		// Updating or what?
		//-----------------------------------------

		if ( $ugroup_id )
		{
			$this->Registry->Db->cur_query = array(
					'do'	 => "update",
					'tables' => "user_agents_groups",
					'set'    => array(
							'ugroup_title' => $ugroup_title,
							'ugroup_array' => serialize( $data ),
						),
					'where'  => "ugroup_id=" . $this->Registry->Db->db->quote( $ugroup_id, "INTEGER" ),
				);
			$this->Registry->Db->simple_exec_query();
		}
		else
		{
			$this->Registry->Db->cur_query = array(
					"do"	 => "insert",
					"table"  => "user_agents_groups",
					"set"    => array(
							'ugroup_title' => $ugroup_title,
							'ugroup_array' => serialize( $data ),
						)
				);
			$this->Registry->Db->simple_exec_query();

			$ugroup_id = $this->Registry->Db->last_insert_id();
		}

		$this->rebuild_user_agent_group_caches();

		return $ugroup_id;
	}

	/**
	 * Fetch all agent groups
	 *
	 * @return    array    Array of data
	 */
	public function fetch_groups ()
	{
		//-----------------------------------------
		// Try and get the skin from the cache
		//-----------------------------------------

		$user_agent_groups = array();

		//-----------------------------------------
		// Get em!!
		//-----------------------------------------

		$this->Registry->Db->cur_query = array(
				'do'	 => "select",
				'table'  => "user_agents_groups",
				'order'  => array( "ugroup_title ASC" ),
			);

		$result = $this->Registry->Db->simple_exec_query();

		foreach ( $result as $row )
		{
			/* Unpack data */
			$row['_group_array'] = ( $row['ugroup_array'] ) ? unserialize( $row['ugroup_array'] ) : $row['_group_array'];
			$row['_array_count'] = count( $row['_group_array'] );

			$user_agent_groups[ $row['ugroup_id'] ] = $row;
		}

		return $user_agent_groups;
	}

	/**
	 * Fetch agents from the DB
	 *
	 * @param     integer   [Optional: Group ID of agents to return]
	 * @return    array     Array of agents
	 */
	public function fetch_agents ( $group_id=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$user_agents = array();

		//-----------------------------------------
		// Get em!!
		//-----------------------------------------

		if ( ! $group_id )
		{
			$this->Registry->Db->cur_query = array(
					'do'	 => "select",
					'table'  => "user_agents",
					'order'  => array( "uagent_position ASC", "uagent_key ASC" ),
				);
			$result = $this->Registry->Db->simple_exec_query();

			foreach ( $result as $row )
			{
				$user_agents[ $row['uagent_id'] ] = $row;
			}
		}
		else
		{
			$this->Registry->Db->cur_query = array(
					'do'	 => "select_row",
					'table'  => "user_agents_groups",
					'where'  => "ugroup_id=" . $this->Registry->Db->db->quote( $group_id, "INTEGER" ),
				);

			$u_group = $this->Registry->Db->simple_exec_query();

			$user_agents = ( $u_group['ugroup_array'] ) ? unserialize( $u_group['ugroup_array'] ) : array();
		}

		return $user_agents;
	}


	/**
	 * Recaches the user-agents where-ever it needs recaching!
	 *
	 * @return    void
	 */
	public function rebuild_user_agent_caches ()
	{
		$this->Registry->Cache->cache__do_load( array("useragents") );

		//-----------------------------------------
		// Now rebuild groups
		//-----------------------------------------

		$this->rebuild_user_agent_group_caches();
	}

	/**
	 * Recaches the user-agents where-ever it needs recaching!
	 *
	 * @return    void
	 */
	public function rebuild_user_agent_group_caches ()
	{
		$this->Registry->Cache->cache__do_load( array("useragentgroups") );
	}


	/**
	 * Save user agents after edit
	 *
	 * @param     integer     User Agent ID
	 * @param     string      User Agent "Key"
	 * @param     string      User Agent Name (Human title)
	 * @param     string      User Agent Regex
	 * @param     integer     User Agent Regex Capture parenthesis #
	 * @param     string      User Agent Type (browser, search engine, other)
	 * @param     integer     User Agent Position
	 * @return    array       ... of data
	 * <code>
	 * Exception Codes:
	 * NO_SUCH_UAGENT:     Could not locate user agent
	 * UAGENT_EXISTS:      Could not rename user agent key
	 * MISSING_DATA:       Fields are missing
	 * REGEX_INCorRECT:    The regex is incorrect
	 * </code>
	 */
	public function save_user_agent_from_edit ( $uagent_id, $uagent_key, $uagent_name, $uagent_regex, $uagent_regex_capture, $uagent_type, $uagent_position )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$uagent_id              = intval( $uagent_id );
		$uagent_key             = strtolower( $this->Registry->Input->clean__makesafe_alphanumerical( $uagent_key ) );
		$uagent_name            = $uagent_name;
		$uagent_regex           = $uagent_regex;
		$uagent_regex_capture   = intval( $uagent_regex_capture );
		$uagent_type            = $this->Registry->Input->clean__makesafe_alphanumerical( $uagent_type );
		$uagent_position        = intval( $uagent_position );

		if ( ! $uagent_id or ! $uagent_key or ! $uagent_name or ! $uagent_regex or ! $uagent_type )
		{
			throw new \Persephone\Exception( 'MISSING_DATA' );
	    }

		//-----------------------------------------
		// Fetch user agent data
		//-----------------------------------------

		$this->Registry->Db->cur_query = array(
				"do"	 => "select_row",
				"table"  => "user_agents",
				"where"  => "uagent_id=" . $this->Registry->Db->db->quote( $uagent_id, "INTEGER" ),
			);
		$useragent = $this->Registry->Db->simple_exec_query();

		if ( ! $useragent['uagent_id'] )
		{
			throw new \Persephone\Exception( 'NO_SUCH_UAGENT' );
		}

		//-----------------------------------------
		// Did we change the key?
		//-----------------------------------------

		if ( $useragent['uagent_key'] != $uagent_key )
		{
			$this->Registry->Db->cur_query = array(
					"do"	 => "select_row",
					"table"  => "user_agents",
					"where"  => "uagent_key=" . $this->Registry->Db->db->quote( $uagent_key ),
				);
			$user_agent_test = $this->Registry->Db->simple_exec_query();

			if ( $user_agent_test['uagent_id'] )
			{
				throw new \Persephone\Exception( "UAGENT_EXISTS" );
			}
		}

		//-----------------------------------------
		// Test syntax
		//-----------------------------------------

		if ( $this->test_regex( $uagent_regex ) !== TRUE )
		{
			throw new \Persephone\Exception( "REGEX_INCORRECT" );
		}

		//-----------------------------------------
		// Update
		//-----------------------------------------

		$this->Registry->Db->cur_query = array(
				'do'	 => "update",
				'tables' => "user_agents",
				'set'    => array(
						'uagent_key'           => $uagent_key,
						'uagent_name'          => $uagent_name,
						'uagent_regex'         => $uagent_regex,
						'uagent_regex_capture' => $uagent_regex_capture,
						'uagent_position'      => $uagent_position,
						'uagent_type'          => $uagent_type
					),
				'where'  => "uagent_id=" . $this->Registry->Db->db->quote( $uagent_id, "INTEGER" ),
			);
		$this->Registry->Db->simple_exec_query();

		//-----------------------------------------
		// Recache
		//-----------------------------------------

		$this->rebuild_user_agent_caches();

		//-----------------------------------------
		// Return
		//-----------------------------------------

		return $uagent_id;
	}

	/**
	 * Save replacement after add
	 *
	 * @param     string     User Agent "Key"
	 * @param     string     User Agent Name (Human title)
	 * @param     string     User Agent Regex
	 * @param     integer    User Agent Regex Capture parenthesis #
	 * @param     string     User Agent Type (browser, search engine, other)
	 * @param     integer    User Agent Position
	 * @return    array      ... of data
	 * <code>
	 * Exception Codes:
	 * UAGENT_EXISTS:    Could not rename user agent key
	 * MISSING_DATA:     Fields are missing
	 * </code>
	 */
	public function save_user_agent_from_add ( $uagent_key, $uagent_name, $uagent_regex, $uagent_regex_capture, $uagent_type, $uagent_position )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$uagent_key             = strtolower( $this->Registry->Input->clean__makesafe_alphanumerical( $uagent_key ) );
		$uagent_name            = $uagent_name;
		$uagent_regex           = $uagent_regex;
		$uagent_regex_capture   = intval( $uagent_regex_capture );
		$uagent_type            = $this->Registry->Input->clean__makesafe_alphanumerical( $uagent_type );
		$uagent_position        = intval( $uagent_position );

		if ( ! $uagent_key or ! $uagent_name or ! $uagent_regex or ! $uagent_type )
		{
			throw new \Persephone\Exception( 'MISSING_DATA' );
	    }

		//-----------------------------------------
		// Check for an existing user agent
		//-----------------------------------------

		$this->Registry->Db->cur_query = array(
				"do"	 => "select_row",
				"table"  => "user_agents",
				"where"  => "uagent_key=" . $this->Registry->Db->db->quote( $uagent_key ),
			);
		$user_agent_test = $this->Registry->Db->simple_exec_query();

		if ( $user_agent_test['uagent_id'] )
		{
			throw new \Persephone\Exception( "UAGENT_EXISTS" );
		}


		//-----------------------------------------
		// Update
		//-----------------------------------------

		$this->Registry->Db->cur_query = array(
				"do"	 => "insert",
				"table"  => "user_agents_groups",
				"set"    => array(
						'uagent_key'           => $uagent_key,
						'uagent_name'          => $uagent_name,
						'uagent_regex'         => $uagent_regex,
						'uagent_regex_capture' => $uagent_regex_capture,
						'uagent_position'      => $uagent_position,
						'uagent_type'          => $uagent_type
					)
			);
		$this->Registry->Db->simple_exec_query();

		$uagent_id = $$this->Registry->Db->last_insert_id();

		//-----------------------------------------
		// Recache
		//-----------------------------------------

		$this->rebuild_user_agent_caches();

		//-----------------------------------------
		// Return
		//-----------------------------------------

		return $uagent_id;
	}


	/**
	 * Removes a user agent group
	 *
	 * @param     integer    UA Group ID
	 * @return    void
	 */
	public function remove_user_agent_group ( $ugroup_id )
	{
		//-----------------------------------------
		// Remove it
		//-----------------------------------------

		$this->Registry->Db->cur_query = array(
				'do'	 => "delete",
				'table'  => "user_agents_groups",
				'where'  => "ugroup_id=" . $this->Registry->Db->db->quote( $ugroup_id, "INTEGER" ),
			);
		$this->Registry->Db->simple_exec_query();

		//-----------------------------------------
		// Recache
		//-----------------------------------------

		$this->rebuild_user_agent_group_caches();
	}


	/**
	 * Reverts / Removes Replacement
	 *
	 * @param     integer    User Agent ID
	 * @return    array      All user agents for this skin set
	 * <code>
	 * Exception Codes:
	 * NO_SUCH_UAGENT:   Could not locate user agent
	 * </code>
	 */
	public function remove_user_agent ( $uagent_id )
	{
		//-----------------------------------------
		// Fetch replacement data
		//-----------------------------------------

		$this->Registry->Db->cur_query = array(
				"do"	 => "select_row",
				"table"  => "user_agents",
				"where"  => "uagent_id=" . $this->Registry->Db->db->quote( $uagent_id, "INTEGER" ),
			);
		$user_agent_test = $this->Registry->Db->simple_exec_query();

		if ( ! $user_agent_test['uagent_id'] )
		{
			throw new \Persephone\Exception( "NO_SUCH_UAGENT" );
		}

		//-----------------------------------------
		// Remove it...
		//-----------------------------------------

		$this->Registry->Db->cur_query = array(
				'do'	 => "delete",
				'table'  => "user_agents",
				'where'  => "uagent_id=" . $this->Registry->Db->db->quote( $uagent_id, "INTEGER" ),
			);
		$this->Registry->Db->simple_exec_query();

		//-----------------------------------------
		// Recache
		//-----------------------------------------

		$this->rebuild_user_agent_caches();

		//-----------------------------------------
		// Grab the adjusted user agents
		//-----------------------------------------

		$useragents = $this->fetch_agents();

		//-----------------------------------------
		// Reeee-turn
		//-----------------------------------------

		return $useragents;
	}

	/**
	 * Test the user's user-agent for a match in the database
	 *
	 * @param     string    [Optional: user agent raw string]
	 * @return    array     array[ 'uagent_id' => int, 'uagent_key' => string, 'uagent_name' => string, 'uagent_version' => int ]
	 */
	public function find_user_agent_id ( $user_agent='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$user_agent    = ( $user_agent ) ? $user_agent : $this->Registry->Session->user_agent;
		$user_agent_return = array(
				'uagent_id'      => 0,
				'uagent_key'     => null,
				'uagent_name'    => null,
				'uagent_type'    => null,
				'uagent_version' => 0
			);

		//-----------------------------------------
		// Test in the DB
		//-----------------------------------------

		$user_agent_cache = $this->Registry->Cache->cache__do_get( "useragents" );

		foreach( $user_agent_cache as $key => $data )
		{
			$regex = str_replace( '#', '\\#', $data['uagent_regex'] );

			if ( ! preg_match( "#{$regex}#i", $user_agent, $matches ) )
			{
				continue;
			}
			else
			{
				//-----------------------------------------
				// Okay, we got a match - finalize
				//-----------------------------------------

				if ( $data['uagent_regex_capture'] )
				{
					 $version = $matches[ $data['uagent_regex_capture'] ];
				}
				else
				{
					$version = 0;
				}

				$user_agent_return = array(
						'uagent_id'      => $data['uagent_id'],
						'uagent_key'     => $data['uagent_key'],
						'uagent_name'    => $data['uagent_name'],
						'uagent_type'    => $data['uagent_type'],
						'uagent_version' => floatval( $version )
					);

				break;
			}
		}

		return $user_agent_return;
	}

	/**
	 * Test regex for errors
	 *
	 * @param     string     Regex
	 * @return    boolean    (True is OK)
	 */
	public function test_regex( $regex )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$return = "";
		$regex  = str_replace( '#', '\\#', $regex );
		$this->reset_message_handle();

		//-----------------------------------------
		// Test...
		//-----------------------------------------

		ob_start();
		eval( "preg_match( '#" . $regex . "#', 'this is just a test' );" );
		$return = ob_get_contents();
		ob_end_clean();

		//-----------------------------------------
		// More data...
		//-----------------------------------------

		$this->add_message( $return );

		//-----------------------------------------
		// Return
		//-----------------------------------------

		return $return ? FALSE : TRUE;
	}
}
