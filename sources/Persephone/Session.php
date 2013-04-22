<?php

namespace Persephone;

if ( ! defined( "INIT_DONE" ) )
{
	die( "Improper access! Exiting now..." );
}

/**
 * Session class
 *
 * @package  Audith CMS codename Persephone
 * @author   Shahriyar Imanov <shehi@imanov.name>
 * @version  1.0
**/
class Session
{
	/**
	 * Registry reference
	 *
	 * @var Registry
	 */
	private $Registry;

	/**
	 * Client browser User-Agent
	 *
	 * @var array
	 */
	public  $browser = array();

	/**
	 * Whether to update session DB record or not
	 *
	 * @var Boolean
	 */
	private $do_update = true;

	/**
	 * Session data of the session that didn't authorize
	 *
	 * @var array
	 */
	private $_failed_authorization_session_data = array();

	/**
	 * Unique form hash
	 *
	 * @var string
	 **/
	public $form_hash = "";

	/**
	 * Ignored users
	 *
	 * @var array
	 */
	public $ignored_users = array();

	/**
	 * Client IP Address
	 *
	 * @var string
	 */
	public $ip_address = "";

	/**
	 * Is the user a Search Engine Spider?
	 *
	 * @var Boolean
	 */
	private $is_not_human = false;

	/**
	 * Language ID to use
	 *
	 * @var integer
	 */
	public $language_id = 1;

	/**
	 * @var integer
	 */
	private $last_activity;

	/**
	 * @var integer
	 */
	private $last_visit;

	/**
	 * User details
	 *
	 * @var array
	 */
	public $member = array();

	/**
	 * Client OS
	 *
	 * @var string
	 */
	public $operating_system = "";

	/**
	 * Perm mask ID
	 *
	 * @var integer
	 */
	public $perm_id;

	/**
	 * Array of perm masks
	 *
	 * @var array
	 */
	public $perm_id_array = array();

	/**
	 * Session Query Override Keys
	 * Allows one to override a session value
	 * Useful for overriding variables that are not set up at the time when session management records [e.g., for update or kill] are made
	 *
	 * So to populate 'location_key_1' later in script execution:
	 * addQueryKey( 'location_key_1', "..." );
	 *
	 * @var array
	 */
	private $_query_override = array();

	/**
	 * Current session data
	 *
	 * @var array
	 */
	public $session_data = array();

	/**
	 * Session User Id which is expired
	 *
	 * @var integer
	 */
	private $session_dead_id;

	/**
	 * Session Id
	 *
	 * @var string
	 */
	private $session_id;

	/**
	 * Session Name
	 *
	 * @var string
	 */
	private $session_name;

	/**
	 * Session Type
	 *
	 * @var string
	 */
	private $session_type = "";

	/**
	 * Session User Id validated through DB session data
	 *
	 * @var integer
	 */
	private $session_user_id;

	/**
	 * Session data to save into Db during shutdown
	 *
	 * @var array
	 */
	private $sessions_to_save = array();

	/**
	 * Session data to delete from Db during shutdown
	 *
	 * @var array
	 */
	private $sessions_to_kill = array();

	/**
	 * Client User-Agent
	 *
	 * @var string
	 */
	public  $user_agent;


	/**
	 * Constructor
	 *
	 * @param  \Persephone\Registry  Registry object
	 */
	public function __construct( Registry $Registry )
	{
		//-----------
		// Prelim
		//-----------

		$this->Registry = $Registry;
		$this->member =& $this->Registry->member;   // A shortcut :)

		//--------------------------
		// Session save handler
		//--------------------------

		if ( $this->Registry->config['performance']['cache']['_method'] == "memcached" and ! is_null( $this->Registry->Cache->cachelib ) )
		{
			ini_set( "session.save_handler" , "memcached" );
			$_server_array = array();
			foreach ( $this->Registry->config['performance']['cache']['memcache']['connection_pool'] as $_server )
			{
				$_server_array[] = implode( ":" , $_server );
			}
			$_ini_set_status = ini_set( "session.save_path" , $_server_list = implode( "," , $_server_array ) );
			$this->Registry->logger__do_log( "Session: " . ( $_ini_set_status === false ? "Failed to set" : "Successfully set" ) . " ini-directive 'session.save_path' = '" . $_server_list . "' (php-memcached-compatible)" , ( $_ini_set_status === false ? "ERROR" : "INFO" ) );

		}
		elseif ( $this->Registry->config['performance']['cache']['_method'] == "memcache" and ! is_null( $this->Registry->Cache->cachelib ) )
		{
			ini_set( "session.save_handler" , "memcache" );
			$_server_array = array();
			foreach ( $this->Registry->config['performance']['cache']['memcache']['connection_pool'] as $_server )
			{
				$_server_array[] = "tcp://" . implode( ":" , $_server );
			}
			$_ini_set_status = ini_set( "session.save_path" , $_server_list = implode( "," , $_server_array ) );
			$this->Registry->logger__do_log( "Session: " . ( $_ini_set_status === false ? "Failed to set" : "Successfully set" ) . " ini-directive 'session.save_path' = '" . $_server_list . "' (php-memcache-compatible)" , ( $_ini_set_status === false ? "ERROR" : "INFO" ) );

		}
		else
		{
			ini_set( "session.save_handler" , "files" );
			$_ini_set_status = ini_set( "session.save_path" , "" );
			$this->Registry->logger__do_log( "Session: " . ( $_ini_set_status === false ? "Failed to revert" : "Successfully reverted" ) . " ini-directive 'session.save_path' = 'files'" , ( $_ini_set_status === false ? "ERROR" : "INFO" ) );
		}
	}


	/**
	 * Inits SESSION environment
	 *
	 * @return void
	 */
	public function init ()
	{
		//--------------------------
		// Sensitive cookies list
		//--------------------------

		$this->Registry->Input->sensitive_cookies = array_merge( $this->Registry->Input->sensitive_cookies, array( 'stronghold', 'session_id', 'admin_session_id', 'member_id', 'pass_hash' ) );

		//----------------------
		// Client information
		//----------------------

		$this->user_agent = $this->Registry->Input->my_getenv( 'HTTP_USER_AGENT' );
		$this->Registry->Input->sanitize__clean_raw_value_recursive__high( $this->user_agent );
		$this->user_agent = substr( $this->user_agent , 0 , 200 );
		$this->operating_system = $this->fetch_os();
		$this->fetch_ip_address();

		//-------------------------------
		// Init Session Management
		//-------------------------------

		$this->Registry->config['security']['session_expiration'] =
			( $this->Registry->config['security']['session_expiration'] )
			?
			$this->Registry->config['security']['session_expiration']
			:
			3600;

		# Session name
		$this->session_name = $this->Registry->config['cookies']['cookie_id'] . "session_id";

		# Set session cookie params
		$this->session_set_cookie_params(
			$this->Registry->config['security']['session_expiration'],
			$this->Registry->config['cookies']['cookie_path'],
			$this->Registry->config['cookies']['cookie_domain'],
			false,   // 'cookie_secure'
			true     // 'cookie_httponly'
		);

		# ... and session name
		//ini_set( "session.name", $this->session_name );

		# We always assume most secure options...
		ini_set( "session.use_cookies"      , "1" );
		ini_set( "session.use_only_cookies" , "1" );
		ini_set( "session.use_trans_sid"    , "0" );

		# ... and we override them with more unsecure ones, if it is requested explicitly, and only.
		if ( $this->Registry->config['security']['session_via_url'] )
		{
			ini_set( "session.use_cookies"      , "1" );
			ini_set( "session.use_only_cookies" , "0" );
			ini_set( "session.use_trans_sid"    , "1" );
		}

		//-----------------------------------------
		// Return as guest if running a task
		//-----------------------------------------

		# @todo

		//-----------------------------------------
		// Continue!
		//-----------------------------------------

		$cookie['session_id']  = $this->Registry->Input->my_getcookie( "session_id" );
		$cookie['member_id']  = $this->Registry->Input->my_getcookie( "member_id" );
		$cookie['pass_hash']  = $this->Registry->Input->my_getcookie( "pass_hash" );

		if ( $cookie['session_id'] )
		{
			$this->get_session( $cookie['session_id'] );
			$this->session_type = "cookie";
		}
		elseif
		(
			$this->Registry->config['security']['session_via_url']
			and
			isset( $_GET[ $this->session_name ] )
		)
		{
			$this->get_session( $this->Registry->Input->get( $this->session_name ) );
			$this->session_type = "url";
		}
		else
		{
			$this->session_id = 0;
			$this->session_type = "url";
		}

		//-----------------------------------------
		// Do we have a valid session ID?
		//-----------------------------------------

		if ( $this->session_id )
		{
			//----------------------------------------------------
			// We've checked the IP addy and browser, so we
			// can assume that this is a valid session.
			//----------------------------------------------------

			if ( ( $this->session_user_id != 0 ) and ( ! empty( $this->session_user_id ) ) )
			{
				//----------------------------------------------
				// It's a member session, so load the member.
				//----------------------------------------------

				$this->set_member( $this->session_user_id );

				//-------------------------
				// Did we get a member?
				//-------------------------

				if ( ( ! $this->member['id'] ) or ( $this->member['id'] == 0 ) )
				{
					$this->update_guest_session();
				}
				else
				{
					$this->update_member_session();
				}
			}
			else
			{
				$this->update_guest_session();
			}
		}
		else
		{
			//-------------------------------------------------------------
			// We didn't have a session, or the session didn't validate
			// Do we have cookies stored?
			//-------------------------------------------------------------

			if ( $cookie['member_id'] != "" and $cookie['pass_hash'] != "" )
			{
				//-----------------
				// Load member
				//-----------------

				$this->set_member( $cookie['member_id'] );

				//--------------------------
				// INIT log in key stuff
				//--------------------------

				$_ok        = 1;
				$_days      = 0;
				$_sticky    = 1;
				$_time      = ( $this->Registry->config['security']['login_key_expire'] ) ? ( UNIX_TIME_NOW + ( intval( $this->Registry->config['security']['login_key_expire'] ) * 86400 ) ) : 0;

				if ( ! $this->member['id'] or $this->member['id'] == 0 )
				{
					$this->create_guest_session();
				}
				else
				{
					if ( $this->member['login_key'] == $cookie['pass_hash'] )
					{

						//------------------
						// Key expired?
						//------------------

						if ( $this->Registry->config['security']['login_key_expire'] )
						{
							$_sticky    = 0;
							$_days      = $this->Registry->config['security']['login_key_expire'];

							if ( UNIX_TIME_NOW > $this->member['login_key_expire'] )
							{
								$_ok    = 0;
							}
						}

						if ( $_ok == 1 )
						{
							$this->create_member_session();

							//-------------------------------------------------------
							// Change the log in key to make each authentication
							// use a unique token. This means that if a cookie is
							// stolen, the hacker can only use the auth once.
							//-------------------------------------------------------

							if ( $this->Registry->config['security']['login_change_key'] )
							{
								$this->member['login_key'] = $this->generate_autologin_key();

								$this->save_member( $this->member['id'], array( 'members' => array( 'login_key' => $this->member['login_key'], 'login_key_expire' => $_time ) ) );

								$this->Registry->Input->my_setcookie( "pass_hash", $this->member['login_key'], $_sticky, $_days );
							}
						}
						else
						{
							$this->set_member( 0 );
							$this->create_guest_session();
						}
					}
					else
					{
						$this->set_member( 0 );
						$this->create_guest_session();
					}
				}
			}
			else
			{
				$this->create_guest_session();
			}
		}

		//-----------------------------------------
		// Knock out Google Web Accelerator
		//-----------------------------------------

		if( $this->Registry->config['performance']['disable_prefetching'] )
		{
			if ( $this->Registry->Input->my_getenv('HTTP_X_MOZ') and strstr( strtolower( $this->Registry->Input->my_getenv('HTTP_X_MOZ')), "prefetch" ) and $this->member['id'] )
			{
				if ( PHP_SAPI == 'cgi-fcgi' or PHP_SAPI == 'cgi' )
				{
					@header('Status: 403 Forbidden');
				}
				else
				{
					@header('HTTP/1.1 403 Forbidden');
				}

				@header("Cache-Control: no-cache, must-revalidate, max-age=0");
				@header("Expires: 0");
				@header("Pragma: no-cache");

				print "Prefetching or precaching is not allowed. If you have Google Accelerator enabled, please disable";
				exit();
			}
		}

		//-----------------------------------------
		// Still no member id and not a bot?
		//-----------------------------------------

		if ( ( ! isset( $this->member['id'] ) or ! $this->member['id'] ) and ! $this->is_not_human )
		{
			$this->set_member( 0 );

			$this->member['last_activity'] = UNIX_TIME_NOW;
			$this->member['last_visit']    = UNIX_TIME_NOW;
		}

		//----------------------------
		// Set a session ID cookie
		//----------------------------

		$this->Registry->Input->my_setcookie( "session_id", $this->session_id, -1 );

		//--------------------
		// Set user agent
		//--------------------

		$this->member['user_agent_key']     = $this->session_data['uagent_key'];
		$this->member['user_agent_type']    = $this->session_data['uagent_type'];
		$this->member['user_agent_version'] = $this->session_data['uagent_version'];
		$this->member['user_agent_bypass']  = ( $this->Registry->Input->my_getcookie( "uagent_bypass" ) )
			?
			true
			:
			$this->session_data['uagent_bypass'];

		//------------------
		// Can use RTE?
		//------------------

		$this->member['_can_use_rte'] = false;

		if ( $this->member['user_agent_key'] == 'explorer' and $this->member['user_agent_version'] >= 6 )
		{
			$this->member['_can_use_rte'] = true;
		}
		elseif ( $this->member['user_agent_key'] == 'opera' and $this->member['user_agent_version'] >= 9.1 )
		{
			$this->member['_can_use_rte'] = true;
		}
		elseif ( $this->member['user_agent_key'] == 'firefox' and $this->member['user_agent_version'] >= 2 )
		{
			$this->member['_can_use_rte'] = true;
		}
		elseif ( $this->member['user_agent_key'] == 'safari' and $this->member['user_agent_version'] >= 2.1 )
		{
			$this->member['_can_use_rte'] = true;
		}
		elseif ( $this->member['user_agent_key'] == 'chrome' and $this->member['user_agent_version'] >= 0.3 )
		{
			$this->member['_can_use_rte'] = true;
		}

    	//-----------------
    	// Can use FBC?
    	//-----------------

		/* @todo
		if ( $this->Registry->config['facebookconnect']['fbc_enable'] )
		{
			$this->Registry->config['facebookconnect']['fbc_enable'] = 0;

			if ( $this->member['user_agent_key'] == 'explorer' and $this->member['user_agent_version'] >= 6 )
			{
				$this->Registry->config['facebookconnect']['fbc_enable'] = 1;
			}
			elseif ( $this->member['user_agent_key'] == 'safari' and $this->member['user_agent_version'] >= 3 )
			{
				$this->Registry->config['facebookconnect']['fbc_enable'] = 1;
			}
			elseif ( $this->member['user_agent_key'] == 'firefox' and $this->member['user_agent_version'] >= 3 )
			{
				$this->Registry->config['facebookconnect']['fbc_enable'] = 1;
			}
		}
		*/
	}


	/**
	 * Destructor
	 */
	public function _my_destruct ()
	{
		# Update sessions...
		$_saved = 0;
		if ( is_array( $this->sessions_to_save ) and count( $this->sessions_to_save ) )
		{
			foreach( $this->sessions_to_save as $sid => $data )
			{
				if ( $sid )
				{
					if ( isset( $this->_query_override[ $sid ] ) and is_array( $this->_query_override[ $sid ] ) and count( $this->_query_override[ $sid ] ) )
					{
						foreach( $data as $field => $value )
						{
							if ( isset( $this->_query_override[ $sid ][ $field ] ) )
							{
								$data[ $field ] = $this->_query_override[ $sid ][ $field ];
							}
						}
					}

					$this->Registry->Db->cur_query = array(
							'do'	 => "update",
							'tables' => "sessions",
							'set'    => $data,
							'where'  => "id=" . $this->Registry->Db->quote( $sid ),

							'force_data_type' => array( 'member_name' => "string" )
						);
					$_saved += $this->Registry->Db->simple_exec_query();
				}
			}
			$this->sessions_to_save = array();
		}

		# Remove sessions
		$_killed = 0;
		if ( is_array( $this->sessions_to_kill ) and count( $this->sessions_to_kill ) )
		{
			$this->Registry->Db->cur_query = array(
					'do'	 => "delete",
					'table'  => "sessions",
					'where'  => "id IN('" . implode( "','", array_keys( $this->sessions_to_kill ) ) . "')",
				);
			$_killed += $this->Registry->Db->simple_exec_query();
		}
		$this->sessions_to_kill = array();

		$this->Registry->logger__do_log( __CLASS__ . ": " . $_saved . " sessions updated, " . $_killed . " sessions deleted" , "INFO" );
		$this->Registry->logger__do_log( __CLASS__ . "::__destruct: Destroying class" , "INFO" );
	}


	/**
	 * Assign a key with a value
	 *
	 * @param    string    Key
	 * @param    string    Value
	 * @param    string    [Session ID, will default to current session if none found]
	 * @return   void
	 */
	public function add_query_key ( $key, $value, $session_id = "" )
	{
		$session_id = ( $session_id ) ? $this->Registry->Input->sanitize__md5_hash( $session_id ) : $this->session_id;

		if ( $key )
		{
			$this->_query_override[ $session_id ][ $key ] = $value;
		}
	}


	/**
	 * Allow This Session
	 *
	 * @param   string   Session ID
	 * @param   bool
	 */
	private function allow_authorize_attempt ( $session_id, $session_data )
	{
		$this->session_data     = $session_data;
		$this->session_id       = $this->session_data['id'];
		$this->session_user_id  = $this->session_data['member_id'];
		$this->last_click       = $this->session_data['running_time'];
		$this->location         = $this->session_data['location'];

		if ( IN_DEV )
		{
			$this->Registry->logger__do_log( __CLASS__ . "::kill_authorize_attempt: Authorization attempt SUCCESSFUL: Session ID = " . $session_id . ", Member ID = " . $this->session_data['member_id'] , "INFO" );
		}

		return true;
	}


	/**
	 * Kill session
	 *
	 * @param    string     Session ID
	 * @param    bool
	 */
	private function kill_authorize_attempt ( $session_id, $session_data )
	{
		$this->_failed_authorization_session_data = $session_data;
		$this->session_dead_id  = $session_id;
		$this->session_id       = 0;
		$this->session_user_id  = 0;
		$this->session_data     = array();

		if ( IN_DEV )
		{
			$this->Registry->logger__do_log( __CLASS__ . "::kill_authorize_attempt: Authorization attempt FAILED: Session ID = " . $session_id , "INFO" );
		}

		return false;
	}


	/**
	 * Check supplied password with database
	 *
	 * @param    string	    Key: either (member)id or email
	 * @param    string	    MD5 of entered password
	 * @return   boolean    Password is correct
	 */
	public function authenticate_member ( $member_key, $md5_once_password )
	{
		# Load member
		$member = $this->load_member( $member_key );

		if ( ! $member['id'] )
		{
			return false;
		}

		if ( $member['pass_hash'] == $this->generate_compiled_pass_hash( $member['pass_salt'], $md5_once_password ) )
		{
			return true;
		}
		else
		{
			return false;
		}
	}


	/**
	 * Create new member
	 * Very basic functionality at this point.
	 *
	 * @param    array     Fields to save in the following format:
	 *                     array( 'members'                 => array(
	 *                                                                'email'   => "test@test.com",
	 *                                                                'joined'  => UNIX_TIMESTAMP_NOW
	 *                                                         ),
	 *                            'members_pfields_content' => array( 'key'     => 'value' ),
	 *                     );
	 *					   Tables: members, members_pfields_content.
	 * @param    bool      Flag to attempt to auto create a name if the desired is taken
	 * @return   array     Final member Data including member_id
	 *
	 * EXCEPTION CODES:
	 * CUSTOM_FIELDS_EMPTY    - Custom fields were not populated
	 * CUSTOM_FIELDS_INVALID  - Custom fields were invalid
	 * CUSTOM_FIELDS_TOOBIG   - Custom fields too big
	 */
	public function create_member ( $tables = array(), $auto_create_name = false )
	{
		//-----------
		// Prelim
		//-----------

		$_final_tables  = array();
		$password       = "";

		//--------------
		// Proceed...
		//--------------

		foreach( $tables as $table => $data )
		{
			if ( $table == 'members' )
			{
				/* Magic password field */
				$password = ( isset( $data['password'] ) ) ? trim( $data['password'] ) : $this->create_password();
				$md_5_password = md5( $password );

				unset( $data['password'] );
			}

			$_final_tables[ $table ] = $data;
		}

		/* @todo

		//------------------------------
		// Custom profile field stuff
		//------------------------------

		require_once( IPS_ROOT_PATH . 'sources/classes/customfields/profileFields.php' );
		$fields  = new customProfileFields();

		if ( is_array( $_final_tables['pfields_content'] ) AND count( $_final_tables['pfields_content'] ) )
		{
			$fields->member_data = $_final_tables['pfields_content'];
		}

		$_cfieldMode	= 'normal';

		$fields->initData( 'edit' );
		$fields->parseToSave( $_final_tables['pfields_content'], 'register' );

		# Check
		if( count( $fields->error_fields['empty'] ) )
		{
			//throw new Exception( 'CUSTOM_FIELDS_EMPTY' );
		}

		if( count( $fields->error_fields['invalid'] ) )
		{
			//throw new Exception( 'CUSTOM_FIELDS_INVALID' );
		}

		if( count( $fields->error_fields['toobig'] ) )
		{
			//throw new Exception( 'CUSTOM_FIELDS_TOOBIG' );
		}

		*/

		//-----------------------------------------
		// Make sure the account doesn't exist
		//-----------------------------------------

		if ( $_final_tables['members']['email'] )
		{
			$existing = $this->load_member( $_final_tables['members']['email'], 'all' );

			if ( $existing['id'] )
			{
				$existing['full']     = true;
				$existing['timenow']  = UNIX_TIME_NOW;

				return $existing;
			}
		}

		//-----------------------------------------
		// Fix up usernames and display names
		//-----------------------------------------

		# Ensure we have a display name

		if ( $auto_create_name )
		{
			$_final_tables['members']['display_name'] = ( $_final_tables['members']['display_name'] ) ? $_final_tables['members']['display_name'] : $_final_tables['members']['name'];
		}

		//------------------------
		// Clean up characters
		//------------------------

		if ( $_final_tables['members']['name'] )
		{
			$_user_name = $this->names__do_clean_and_check( $_final_tables['members']['name'], array(), 'name' );

			if ( $_user_name['errors'] )
			{
				$_final_tables['members']['name'] = "";
			}
			else
			{
				$_final_tables['members']['name'] = $_user_name['username'];
			}
		}

		if ( $_final_tables['members']['display_name'] )
		{
			$_display_name = $this->names__do_clean_and_check( $_final_tables['members']['display_name'] );

			if( $_display_name['errors'] )
			{
				$_final_tables['members']['display_name'] = "";
			}
			else
			{
				$_final_tables['members']['display_name'] = $_display_name['display_name'];
			}
		}

		//--------------------------------
		// Remove some basic HTML tags
		//--------------------------------

		$_final_tables['members']['display_name'] = str_replace( array( '<', '>', '"' ), '', $_final_tables['members']['display_name'] );
		$_final_tables['members']['name']         = str_replace( array( '<', '>', '"' ), '', $_final_tables['members']['name'] );

		//-----------------------------------
		// Make sure the names are unique
		//-----------------------------------

		if ( $_final_tables['members']['display_name'] )
		{
			if ( $this->names__do_check_if_exists( $_final_tables['members']['display_name'], array(), "display_name", true ) === true )
			{
				if ( $auto_create_name === true )
				{
					# Now, make sure we have a unique display name
					$this->Registry->Db->cur_query = array(
							'do'     => "select_row",
							'fields' => array(
									'max' => new Zend_Db_Expr( "MAX(id)" ),
								),
							'table'  => "members",
							'where'  => "l_display_name LIKE " . $this->Registry->Db->quote( strtolower( $_final_tables['members']['display_name'] ) . "%" ),
						);
					$max = $this->Registry->Db->simple_exec_query();

					if ( $max['max'] )
					{
						$_num = $max['max'] + 1;
						$_final_tables['members']['display_name'] = $_final_tables['members']['display_name'] . "_" . $_num;
					}
				}
				else
				{
					$_final_tables['members']['display_name'] = "";
				}
			}
		}

		if ( $_final_tables['members']['name'] )
		{
			if ( $this->names__do_check_if_exists( $_final_tables['members']['name'], array(), "name", true ) === true )
			{
				if ( $auto_create_name === true )
				{
					# Now, make sure we have a unique display name
					$this->Registry->Db->cur_query = array(
							'do'     => "select_row",
							'fields' => array(
									'max' => new Zend_Db_Expr( "MAX(id)" ),
								),
							'table'  => "members",
							'where'  => "l_username LIKE " . $this->Registry->Db->quote( strtolower( $_final_tables['members']['name'] ) . "%" ),
						);

					$max = $this->Registry->Db->simple_exec_query();

					if ( $max['max'] )
					{
						$_num = $max['max'] + 1;
						$_final_tables['members']['name'] = $_final_tables['members']['name'] . "_" . $_num;
					}
				}
				else
				{
					$_final_tables['members']['name'] = "";
				}
			}
		}

		//--------------------------------
		// Populate member table(s)
		//--------------------------------

		$_final_tables['members']['l_username']             = isset( $_final_tables['members']['name'] ) ? strtolower($_final_tables['members']['name']) : '';
		$_final_tables['members']['joined']	                = $_final_tables['members']['joined'] ? $_final_tables['members']['joined'] : UNIX_TIME_NOW;
		$_final_tables['members']['email']                  = $_final_tables['members']['email'] ? $_final_tables['members']['email'] : $_final_tables['members']['name'] . '@' . $_final_tables['members']['joined'];
		$_final_tables['members']['mgroup']                 = $_final_tables['members']['mgroup'] ? $_final_tables['members']['mgroup'] : $this->Registry->config['security']['member_group'];
		$_final_tables['members']['ip_address']             = $_final_tables['members']['ip_address'] ? $_final_tables['members']['ip_address'] : $this->ip_address;
		$_final_tables['members']['created_remote']         = isset( $_final_tables['members']['created_remote'] ) ? intval( $_final_tables['members']['created_remote'] ) : 0;
		$_final_tables['members']['login_key']              = $this->generate_autologin_key();
		$_final_tables['members']['login_key_expire']       = ( $this->Registry->config['security']['login_key_expire'] ) ? ( UNIX_TIME_NOW + ( intval( $this->Registry->config['security']['login_key_expire'] ) * 86400 ) ) : 0;
		$_final_tables['members']['email_pm']               = 1;
		// $_final_tables['members']['view_avs']               = 1;
		$_final_tables['members']['restrict_post']          = isset( $_final_tables['members']['restrict_post'] ) ? intval( $_final_tables['members']['restrict_post'] ) : 0;
		// $_final_tables['members']['view_pop']               = 1;
		//$_final_tables['members']['msg_count_total']         = 0;
		//$_final_tables['members']['msg_count_new']           = 0;
		//$_final_tables['members']['msg_show_notification']   = 1;
		$_final_tables['members']['coppa_user']             = 0;
		// $_final_tables['members']['auto_track']             = intval( $_final_tables['members']['auto_track'] );
		$_final_tables['members']['last_visit']             = isset( $_final_tables['members']['last_visit'] ) ? intval( $_final_tables['members']['last_visit'] ) : UNIX_TIME_NOW;
		$_final_tables['members']['last_activity']          = isset( $_final_tables['members']['last_activity'] ) ? intval( $_final_tables['members']['last_activity'] ) : UNIX_TIME_NOW;
		// @todo $_final_tables['members']['language']         = IPSLib::getDefaultLanguage();
		$_final_tables['members']['editor_choice']          = $this->Registry->config['userprofiles']['default_editor'];
		$_final_tables['members']['pass_salt']              = $this->generate_password_salt( 5 );
		$_final_tables['members']['pass_hash']              = $this->generate_compiled_pass_hash( $_final_tables['members']['pass_salt'], $md_5_password );
		$_final_tables['members']['display_name']           = isset($_final_tables['members']['display_name']) ? $_final_tables['members']['display_name'] : "";
		$_final_tables['members']['l_display_name']         = isset($_final_tables['members']['display_name']) ? strtolower( $_final_tables['members']['display_name'] ) : "";
		//$_final_tables['members']['fb_uid']                  = isset($_final_tables['members']['fb_uid']) ? $_final_tables['members']['fb_uid'] : 0;
		//$_final_tables['members']['fb_emailhash']            = isset($_final_tables['members']['fb_emailhash']) ? strtolower($_final_tables['members']['fb_emailhash']) : '';
		$_final_tables['members']['seo_name']               = $this->Registry->Input->make_seo_title( $_final_tables['members']['display_name'] );
		//$_final_tables['members']['bw_is_spammer']           = intval( $_final_tables['members']['bw_is_spammer'] );

		//--------------------
		// Insert: MEMBERS
		//--------------------

		$this->Registry->Db->cur_query = array(
				'do'     => "insert",
				'table'  => "members",
				'set'    => $_final_tables['members'],
				'force_data_type'  => array(
						'name'             => 'string',
						'l_username'       => 'string',
					    'display_name'     => 'string',
					    'l_display_name'   => 'string',
					    'seo_name'         => 'string',
					    'email'            => 'string'
					)
			);

		//---------------------
		// Get the member id
		//---------------------

		$this->Registry->Db->simple_exec_query();
		$_final_tables['members']['id'] = $this->Registry->Db->last_insert_id();

		/* @todo
		//---------------------------
		// Insert: PROFILE PORTAL
		//---------------------------

		$_final_tables['profile_portal']['pp_member_id']              = $_final_tables['members']['member_id'];
		$_final_tables['profile_portal']['pp_setting_count_friends']  = 1;
		$_final_tables['profile_portal']['pp_setting_count_comments'] = 1;

		ipsRegistry::DB()->insert( 'profile_portal', $_final_tables['profile_portal'] );

		*/

		/* @todo
		//--------------------------------------------
		// Insert into the custom profile fields DB
		//--------------------------------------------

		$fields->out_fields['member_id'] = $_final_tables['members']['member_id'];

		ipsRegistry::DB()->delete( 'pfields_content', 'member_id=' . $_final_tables['members']['member_id'] );
		ipsRegistry::DB()->insert( 'pfields_content', $fields->out_fields );
		*/

		//---------------------------------
		// Insert into partial ID table
		//---------------------------------

		$full_account = false;

		if ( $_final_tables['members']['display_name'] and $_final_tables['members']['name'] and $_final_tables['members']['email'] != $_final_tables['members']['name'] . '@' . $_final_tables['members']['joined'] )
		{
			$full_account = true;
		}

		if ( ! $full_account )
		{
			$this->Registry->Db->cur_query = array(
					'do'     => "insert",
					'table'  => "members_partial",
					'set'    => array(
							'partial_member_id' => $_final_tables['members']['id'],
							'partial_date'      => $_final_tables['members']['joined'],
							'partial_email_ok'  => ( $_final_tables['members']['email'] == $_final_tables['members']['name'] . '@' . $_final_tables['members']['joined'] ) ? 0 : 1,
						),
				);

			$this->Registry->Db->simple_exec_query();
		}

		// @todo IPSMember::updateSearchIndex( $_final_tables['members']['id'] );
		//IPSLib::runMemberSync( 'onCreateAccount', $_final_tables['members'] );

		return array_merge( $_final_tables['members'], /* @todo $_final_tables['profile_portal'], $fields->out_fields, */ array( 'timenow' => $_final_tables['members']['joined'], 'full' => $full_account ) );
	}


	/**
	 * Load member
	 *
	 * @param 	string	Member key: Either ID or email address OR array of IDs when $key_type is either ID or not set OR a list of $key_type strings (email address, name, etc)
	 * @param 	string	Extra tables to load(all, none or comma delisted tables) Tables: members, groups, sessions.
	 * @param	string  Key type. Leave it blank to auto-detect or specify "id", "email", "username", "displayname".
	 * @return	array   Array containing member data
	 *
	 * @author bfarber @ IPS
	 *
	 * <code>
	 * # Single member
	 * $member = load( 1, 'groups,sessions' );
	 * $member = load( 'matt@email.com', 'all' );
	 * $member = load( 'MattM', 'all', 'displayname' ); // Can also use 'username', 'email' or 'id'
	 * # Multiple members
	 * $members = load( array( 1, 2, 10 ), 'all' );
	 * $members = load( array( 'MattM, 'JoeD', 'DaveP' ), 'all', 'displayname' );
	 * </code>
	 */
	public function load_member ( $member_key, $extra_tables = "all", $key_type = "" )
	{
		//--------------------
		// Preliminary work
		//--------------------

		$_member_value = 0;
		$members = array();
		$_multiple_ids = array();
		$_member_field = "";
		$_joins = array();
		$_tables = array( 'members_pfields_content' => 0, 'groups' => 0, 'sessions' => 0 );

		//------------
		// key_type
		//------------

		if ( ! $key_type )
		{
			if ( is_array( $member_key ) )
			{
				$_multiple_ids = $member_key;
				$_member_field = 'id';
			}
			else
			{
				if ( strstr( $member_key, '@' ) )
				{
					$_member_value = $this->Registry->Db->quote( strtolower( $member_key ) );
					$_member_field = 'email';
				}
				else
				{
					$_member_value = intval( $member_key );
					$_member_field = 'id';
				}
			}
		}
		else
		{
			switch( $key_type )
			{
				default:
				case 'id':
					if ( is_array( $member_key ) )
					{
						$_multiple_ids = $member_key;
					}
					else
					{
						$_member_value = is_numeric( $member_key ) ? $member_key : 0;
					}
					$_member_field = 'id';
					break;
				/*
				case 'fb_uid':
					if ( is_array( $member_key ) )
					{
						$_multiple_ids = $member_key;
					}
					else
					{
						$_member_value = intval( $member_key );
					}
					$_member_field = 'fb_uid';
					break;
				*/
				case 'email':
					if ( is_array( $member_key ) )
					{
						array_walk( $member_key, create_function( '&$v,$k', '$v=$this->Registry->Db->quote( strtolower( $v ) );' ) );
						$_multiple_ids = $member_key;
					}
					else
					{
						$_member_value = $this->Registry->Db->quote( strtolower( $member_key ) );
					}
					$_member_field = 'email';
					break;
				case 'username':
					if ( is_array( $member_key ) )
					{
						array_walk( $member_key, create_function( '&$v,$k', '$v=$this->Registry->Db->quote( strtolower( $v ) );' ) );
						$_multiple_ids = $member_key;
					}
					else
					{
						$_member_value = $this->Registry->Db->quote( strtolower( $member_key ) );
					}
					$_member_field = 'l_username';
					break;
				case 'displayname':
					if ( is_array( $member_key ) )
					{
						array_walk( $member_key, create_function( '&$v,$k', '$v=$this->Registry->Db->quote( strtolower( $v ) );' ) );
						$_multiple_ids = $member_key;
					}
					else
					{
						$_member_value = $this->Registry->Db->quote( strtolower( $member_key ) );
					}
					$_member_field = 'l_display_name';
					break;
			}
		}

		//---------------------
		// Sort out joins...
		//---------------------

		if ( $extra_tables == 'all' )
		{
			foreach( $_tables as $_table => $_val )
			{
				# Let's not load sessions unless specifically requested
				if ( $_table == 'sessions' )
				{
					continue;
				}

				$_tables[ $_table ] = 1;
			}
		}
		else if ( $extra_tables )
		{
			$_extra_tables = explode( ",", $extra_tables );

			foreach( $_extra_tables as $_t )
			{
				$_t = trim( $_t );

				if ( isset( $_tables[ $_t ] ) )
				{
					$_tables[ $_t ] = 1;
				}
			}
		}

		//----------------
		// Fix up joins
		//----------------

		if ( $_tables['members_pfields_content'] )
		{
			$joins[] = array(
					'fields'      => array( "p.*" ),
			 		'table'       => array( 'p' => "members_pfields_content" ),
					'conditions'  => 'p.member_id = m.id',
					'join_type'   => 'LEFT'
				);
		}

		if ( $_tables['groups'] )
		{
			$_joins[] = array(
					'fields'      => array( "g.*" ),
			 		'table'       => array( 'g' => "groups" ),
					'conditions'  => 'g.g_id = m.mgroup',
					'join_type'   => 'LEFT'
				);
		}

		if ( $_tables['sessions'] )
		{
			$_joins[] = array(
					'fields'      => array( "s.*" ),
			 		'table'       => array( 's' => "sessions" ),
					'conditions'  => 's.member_id = m.id',
					'join_type'   => 'LEFT'
				);
		}

		//---------------
		// Build Query
		//---------------

		if ( count( $_joins ) )
		{
			$this->Registry->Db->cur_query = array(
					'do'	    => "select",
					'fields'    => array( "*", 'my_member_id' => "id" ),
					'table'     => array( 'm' => "members" ),
					'where'     => ( is_array( $_multiple_ids ) and count( $_multiple_ids ) ) ? $_member_field . ' IN (' . implode( ',', $_multiple_ids ) . ')' : $_member_field . '=' . $_member_value,
					'add_join'  => $_joins,
				);
		}
		else
		{
			$this->Registry->Db->cur_query = array(
					'do'	    => "select",
					'table'     => array( 'm' => 'members' ),
					'where'     => ( is_array( $_multiple_ids ) and count( $_multiple_ids ) ) ? $_member_field . ' IN (' . implode( ',', $_multiple_ids ) . ')' : $_member_field . '=' . $_member_value,
				);
		}

		//------------
		// Execute
		//------------

		$result = $this->Registry->Db->simple_exec_query();

		foreach ( $result as $_mem )
		{
			if ( isset( $_mem['my_member_id'] ) )
			{
				$_mem['id'] = $_mem['my_member_id'];
			}

			//---------------------------------------------------
			// Be sure we properly apply secondary permissions
			//---------------------------------------------------

			if ( $_tables['groups'] )
			{
				$_mem = $this->mgroup_others__do_parse( $_mem );
			}

			//----------------
			// Unblockable
			//----------------

			$_mem['_can_be_ignored'] = $this->ignored_users__is_ignorable( $_mem['mgroup'], $_mem['mgroup_others'] );

			/* Add to array */
			$members[ $_mem['id'] ] = $_mem;
		}

		//------------------------------------------------
		// Return just a single if we only sent one id
		//------------------------------------------------

		return ( is_array( $_multiple_ids ) and count( $_multiple_ids ) ) ? $members : array_shift( $members );
	}


	/**
	 * Save member
	 *
	 * @param   integer    Member key: Either Array, ID or email address. If it's an array, it must be in the format:
	 *                     array( 'members' => array( 'field' => 'id', 'value' => 1 ) ) - useful for passing custom fields through
	 * @param 	array      Fields to save in the following format:
	 *                     array( 'members' => array( 'email' => 'test@test.com',
	 *                                                'joined'   => time() ),
	 *                                                'members_pfields_content' => array( 'updated' => 1234567890 ) );
	 *					   Tables: members, members_pfields_content.
	 * @return  boolean	   TRUE if the save was successful, FALSE otherwise
	 *
	 * Exception Error Codes:
	 * NO_DATA 	          : No data to save
	 * NO_VALID_KEY       : No valid key to save
	 * NO_AUTO_LOAD       : Could not autoload the member as (s)he does not exist
	 * INCORRECT_TABLE    : Table, one is attempting to save to, does not exist
	 * NO_MEMBER_GROUP_ID : Member group ID is in the array but blank
	 */
	public function save_member ( $member_key, $save = array() )
	{
		$member_id      = 0;
		$member_email   = '';
		$member_field   = '';
		$_updated       = 0;
		$member_k_array = array( 'members' => array(), 'members_pfields_content' => array() );
		$_tables        = array_keys( $save );

		if ( ! is_array( $save ) or ! count( $save ) )
		{
			throw new Exception( 'NO_DATA' );
		}

		//-----------------------------------------
		// ID or email?
		//-----------------------------------------

		if ( ! is_array( $member_key ) )
		{
			if ( strstr( $member_key, '@' ) )
			{
				$member_k_array['members'] = array( 'field' => "email",
				 									'value' => $this->Registry->Db->quote( strtolower( $member_key ) ) );

				/* Check to see if we've got more than the 'members' table to save on. */

				$_got_more_than_members = false;

				foreach( $_tables as $table )
				{
					if ( $table != 'members' )
					{
						$_got_more_than_members = true;
						break;
					}
				}

				if ( $_got_more_than_members === true )
				{
					/* Get the ID */
					$_member = $this->load_member( $member_key, 'members' );

					if ( $_member['id'] )
					{
						$member_k_array['members_pfields_content'] = array( 'field' => "member_id" , 'value' => $_member['id'] );
					}
					else
					{
						throw new Exception( "NO_AUTO_LOAD" );
					}
				}
			}
			else
			{
				$member_k_array['members']                 = array( 'field' => 'id'        , 'value' => intval( $member_key ) );
				$member_k_array['members_pfields_content'] = array( 'field' => 'member_id' , 'value' => intval( $member_key ) );
			}
		}
		else
		{
			$_member_k_array = $member_k_array;

			foreach( $member_key as $table => $data )
			{
				if ( ! in_array( $table, array_keys( $_member_k_array ) ) )
				{
					throw new Exception( 'INCORRECT_TABLE' );
				}

				$member_k_array[ $table ] = $data;
			}
		}

		if ( ! is_array( $member_k_array ) or ! count( $member_k_array ) )
		{
			throw new Exception( 'NO_DATA' );
		}

		//---------------
		// Now save...
		//---------------

		foreach ( $save as $table => $data )
		{
			if ( $table == 'members_pfields_content' )
			{
				$data[ $member_k_array[ $table ]['field'] ] = $member_k_array[ $table ]['value'];

				//-------------------
				// Does row exist?
				//-------------------

				$this->Registry->Db->cur_query = array(
						'do'     => "select_row",
						'fields' => array( "member_id" ),
						'table'  => array( "members_pfields_content" ),
						'where'  => 'member_id=' . $this->Registry->Db->quote( $data['id'] , "INTEGER" )
					);
				$check = $this->Registry->Db->simple_exec_query();

				if( ! $check['member_id'] )
				{
					$this->Registry->Db->cur_query = array(
							"do"	 => "insert",
							"table"  => $table,
							"set"    => $data
						);
				}
				else
				{
					$this->Registry->Db->cur_query = array(
							'do'	 => "update",
							'tables' => $table,
							'set'    => $data,
							'where'  => 'member_id=' . $this->Registry->Db->quote( $data['id'] , "INTEGER" )
						);
				}
				$_updated += $this->Registry->Db->simple_exec_query();
			}
			else
			{
				if ( $table == 'members' )
				{
					/* Make sure we have a value for member_group_id if passed */
					if ( isset( $data['mgroup'] ) and ! $data['mgroup'] )
					{
						throw new Exception( "NO_MEMBER_GROUP_ID" );
					}

					/* Some stuff that can end up  here */
					unset( $data['_can_be_ignored'] );

					$this->Registry->Db->cur_query['force_data_type'] = array(
							'name'              => "string",
							'title'             => "string",
							'l_username'        => "string",
							'display_name'      => "string",
							'l_display_name'    => "string",
							'seo_name'          => "string",
							'msg_count_total'   => "int",
							'msg_count_new'     => "int",
						);
				}

				$this->Registry->Db->cur_query = array(
						'do'	 => "update",
						'tables' => $table,
						'set'    => $data,
						'where'  => $member_k_array[ $table ]['field'] . '=' . $member_k_array[ $table ]['value']
					);
				$_updated += $this->Registry->Db->simple_exec_query();
			}
		}

		return ( $_updated > 0 ) ? true : false;
	}


	/**
	 * Create a guest session
	 *
	 * @return    boolean     Created successfully
	 */
	private function create_guest_session ()
	{
		//--------
		// Init
		//--------

		$query        = array();
		$member_name  = "";
		$member_group = $this->Registry->config['security']['guest_group'];
		$login_type   = 0;

		//--------------------------------
		// Remove the defunct sessions
		//--------------------------------

		if ( $this->session_dead_id )
		{
			$query[] = "id=" . $this->Registry->Db->quote( $this->session_dead_id );
		}

		if ( $this->Registry->config['security']['match_ipaddress'] == 1 )
		{
			$query[] = "ip_address=" . $this->Registry->Db->quote( $this->ip_address );
		}

		$this->session_id = md5( uniqid( microtime(), true ) . $this->ip_address . $this->user_agent );

		//------------------
		// Still update?
		//------------------

		if ( ! $this->do_update )
		{
			return false;
		}

		$user_agent = $this->process_user_agent( "create" );

		//-----------------------------------------
		// Is it a search engine?
		//-----------------------------------------

		if ( $user_agent['uagent_type'] == 'search' )
		{
			$this->session_id  = substr( $user_agent['uagent_key'] . "=" . str_replace( ".", "", $this->ip_address ) . '_session', 0, 60 );
			$member_name       = $user_agent['uagent_name'];
			$member_group      = $this->Registry->config['searchenginespiders']['spider_group'];
			$login_type        = intval( $this->Registry->config['searchenginespiders']['spider_anon'] );

			if ( IN_DEV )
			{
				$this->Registry->logger__do_log( __CLASS__ . "::create_guest_session: Creating SEARCH ENGINE session: " . $this->session_id , "INFO" );
			}

			$query[] = "id=" . $this->Registry->Db->quote( $this->session_id );
		}
		else
		{
			if ( IN_DEV )
			{
				$this->Registry->logger__do_log( __CLASS__ . "::create_guest_session: Creating GUEST session: " . $this->session_id , "INFO" );
			}
		}

		//-----------------------------
		// Got anything to remove?
		//-----------------------------

		if ( count( $query ) )
		{
			$this->destroy_sessions( implode( " OR ", $query ) );
		}

		//--------------------------
		// Insert the new session
		//--------------------------

		$data = array(
						'id'                => $this->session_id,
						'member_name'       => $member_name,
						'member_id'         => 0,
						'member_group'      => $member_group,
						'login_type'        => $login_type,
						'running_time'      => UNIX_TIME_NOW,
						'ip_address'        => $this->ip_address,
						'browser'           => $this->user_agent,
						'location'          => "",   # @todo
						'in_error'          => 0,
						'uagent_key'        => $user_agent['uagent_key'],
						'uagent_version'    => $user_agent['uagent_version'],
						'uagent_type'       => $user_agent['uagent_type'],
						'uagent_bypass'     => intval( $user_agent['uagent_bypass'] )
			);

		$this->Registry->Db->cur_query = array(
				'do'	 => "insert",
				'table'  => "sessions",
				'set'    => $data,

				'force_data_type' => array( 'member_name' => "string" ),
			);
		$this->Registry->Db->simple_exec_query_shutdown();

		//---------------
		// Force data
		//---------------

		$this->session_data = array(
				'uagent_key'     => $user_agent['uagent_key'],
				'uagent_version' => $user_agent['uagent_version'],
				'uagent_type'    => $user_agent['uagent_type'],
				'uagent_bypass'  => $user_agent['uagent_bypass'],
				'id'             => $data['id']
			);

		//------------------------------------------------------------------------------------
		// Before this function is called, a guest is set up via Session::set_member(0)
		// We want to override this now to provide search engine settings for the 'member'
		//------------------------------------------------------------------------------------

		if ( $user_agent['uagent_type'] == 'search' )
		{
			$this->set_search_engine( $user_agent );

			/* Reset some data */
			$this->session_type = 'cookie';
			$this->session_id   = "";
		}

		/* Set type */
		$this->member['_session_type'] = "create";

		return true;
	}


	/**
	 * Create a member session
	 *
	 * @return    boolean    Created successfully
	 */
	private function create_member_session ()
	{
		if ( $this->member['id'] )
		{
			//-------------------------------
			// Remove the defunct sessions
			//-------------------------------

			$this->destroy_sessions( "member_id=" . $this->member['id'] );

			$this->session_id = md5( uniqid( microtime(), true ) . $this->ip_address . $this->user_agent );

			//-----------------------
			// Get module settings
			//-----------------------

			// @todo $vars = $this->_getLocationSettings();

			//---------------------
			// Still update?
			//---------------------

			if ( ! $this->do_update )
			{
				return false;
			}

			if ( IN_DEV )
			{
				$this->Registry->logger__do_log( __CLASS__ . "::create_member_session: Creating MEMBER session: " . $this->session_id , "INFO" );
			}

			//--------------------------
			// Get useragent stuff
			//--------------------------

			$user_agent = $this->process_user_agent( "create" );

			//----------------------------
			// Insert the new session
			//----------------------------

			$data = array(
					'id'                => $this->session_id,
					'member_name'       => $this->member['display_name'],
					'seo_name'          => $this->fetch_seo_name( $this->member ),
					'member_id'         => intval( $this->member['id'] ),
					'member_group'      => $this->member['mgroup'],
					'login_type'        => intval( substr($this->member['login_anonymous'] , 0 , 1 ) ),
					'running_time'      => UNIX_TIME_NOW,
					'ip_address'        => $this->ip_address,
					'browser'           => $this->user_agent,
					'location'          => "", // @todo
					'in_error'          => 0,
					'uagent_key'        => $user_agent['uagent_key'],
					'uagent_version'    => $user_agent['uagent_version'],
					'uagent_type'       => $user_agent['uagent_type'],
					'uagent_bypass'     => intval( $user_agent['uagent_bypass'] )
				);


			$this->Registry->Db->cur_query = array(
					'do'	 => "insert",
					'table'  => "sessions",
					'set'    => $data,

					'force_data_type' => array( 'member_name' => "string" ),
				);
			$this->Registry->Db->simple_exec_query_shutdown();

			//----------------
			// Force data
			//----------------

			$this->session_data = array(
					'uagent_key'     => $user_agent['uagent_key'],
					'uagent_version' => $user_agent['uagent_version'],
					'uagent_type'    => $user_agent['uagent_type'],
					'uagent_bypass'  => $user_agent['uagent_bypass'],
					'id'             => $data['id']
				);

			//--------------------------------------------------------------
			// If this is a member, update their last visit times, etc.
			//--------------------------------------------------------------

			if ( UNIX_TIME_NOW - $this->member['last_activity'] > $this->Registry->config['security']['session_expiration'] )
			{
				//-----------------------------------------
				// Reset the topics read cookie..
				//-----------------------------------------

				list( $be_anon, $loggedin ) = explode( '&', $this->member['login_anonymous'] );

				$update = array(
						'login_anonymous' => $be_anon . "&1",
						'last_visit'      => $this->member['last_activity'],
						'last_activity'   => UNIX_TIME_NOW
					);

				//-----------------------------------------
				// Fix up the last visit/activity times.
				//-----------------------------------------

				$this->member['last_activity'] = UNIX_TIME_NOW;
				$this->member['last_visit']    = $this->member['last_activity'];
			}

			$this->Registry->Input->my_setcookie( "pass_hash", $this->member['login_key'], ( $this->Registry->config['security']['login_key_expire'] ? 0 : 1 ), $this->Registry->config['security']['login_key_expire'] );

			$update['login_key_expire']	= UNIX_TIME_NOW + ( $this->Registry->config['security']['login_key_expire'] * 86400 );

			$this->save_member( $this->member['id'], array( 'members' => $update ) );
		}
		else
		{
			$this->create_guest_session();
		}

		//----------------------------------------------------------------------------------------
		// Before this function is called, a guest is set up via Session::set_member(0)
		// We want to override this now to provide search engine settings for the 'member'
		//----------------------------------------------------------------------------------------

		if ( $user_agent['uagent_type'] == 'search' )
		{
			$this->set_search_engine( $user_agent );

			/* Reset some data */
			$this->session_type = "cookie";
			$this->session_id   = "";
		}

		/* Set type */
		$this->member['_session_type'] = 'create';

		return true;
	}


	/**
	 * Create a random 15 character password
	 *
	 * @return    string     Password
	 */
	public function create_password ()
	{
		$pass = "";

		$unique_id  = uniqid( mt_rand(), true );
		$prefix	    = $this->generate_password_salt();
		$unique_id .= md5( $prefix );

		usleep( mt_rand( 15000, 1000000 ) );
		// Hmm, wonder how long we slept for

		$new_uniqid = uniqid( mt_rand(), true );

		$final_rand = md5( $unique_id . $new_uniqid );

		for ( $i = 0; $i < 15; $i++)
		{
			$pass .= $final_rand{ mt_rand(0, 31) };
		}

		return $pass;
	}


	/**
	 * Converts a guest session to a member session
	 *
	 * @param    array    Array of incoming data (member_id, member_name, member_group, login_type)
	 * @return   string   Current session ID
	 */
	public function convert_guest_to_member ( $data )
	{
		/* Delete old sessions */
		$this->destroy_sessions( "ip_address='" . $this->ip_address . "' AND id != '" . $this->session_id . "'" );

		/* Fetch member */
		$member = $this->load_member( $data['member_id'], 'members' );

		/* Update this session directly */
		$this->Registry->Db->cur_query = array(
				'do'	 => "update",
				'tables' => "sessions",
				'set'    => array(
						'member_name'			=> $data['member_name'],
						'seo_name'				=> $this->Registry->Input->make_seo_title( $data['member_name'] ),
						'member_id'				=> $data['member_id'],
						'running_time'			=> UNIX_TIME_NOW,
						'member_group'			=> $data['member_group'],
						'login_type'			=> $data['login_type'],
					),
				'where'  => "id=" . $this->Registry->Db->quote( $this->session_id )
			);
		$this->Registry->Db->simple_exec_query_shutdown();

		/* Remove from update and delete array */
		unset( $this->sessions_to_save[ $this->session_id ] );
		unset( $this->sessions_to_kill[ $this->session_id ] );

		$_update	= array( 'last_activity' => UNIX_TIME_NOW );

		if ( $member['last_activity'] )
		{
			$_update['last_visit'] = $member['last_activity'];
		}
		else
		{
			$_update['last_visit'] = time();
		}

		/* Make sure last activity and last visit are up to date */
		$this->save_member( $data['member_id'], array( 'members' => $_update ) );

		/* Set cookie */
		$this->Registry->Input->my_setcookie( "session_id", $this->session_id, -1 );

		if ( IN_DEV )
		{
			$this->Registry->logger__do_log( __CLASS__ . "::convert_guest_to_member(): " . $data['member_id'] . ", " . $this->session_id . " " . serialize( $data ) /* . 'sessions-' . $this->_memberData['member_id'] */ , "INFO" );
		}

		/* Set type */
		$this->member['_session_type'] = "update";

		return $this->session_id;
	}


	/**
	 * Converts a member session to a guest session
	 *
	 * @return   string     Current session ID
	 */
	public function convert_member_to_guest ()
	{
		/* Delete old sessions */
		$this->destroy_sessions( "ip_address=" . $this->Registry->Db->quote( $this->ip_address ) . " AND id != " . $this->Registry->Db->quote( $this->session_id ) );

		/* Update this session directly */
		$this->Registry->Db->cur_query = array(
				'do'	 => "update",
				'tables' => "sessions",
				'set'    => array(
						'member_name'			=> "",
						'seo_name'				=> "",
						'member_id'				=> 0,
						'running_time'			=> UNIX_TIME_NOW,
						'member_group'			=> $this->Registry->config['security']['guest_group'],
					),
				'where'  => "id=" . $this->Registry->Db->quote( $this->session_id )
			);
		$this->Registry->Db->simple_exec_query_shutdown();

		/* Remove from update and delete array */
		unset( $this->sessions_to_save[ $this->session_id ] );
		unset( $this->sessions_to_kill[ $this->session_id ] );

		/* Set cookie */
		$this->Registry->Input->my_setcookie( "session_id", $this->session_id, -1 );

		/* Save markers... */
		// @todo  KEEPING here for later reference
		// $this->registry->classItemMarking->writeMyMarkersToDB();

		if ( IN_DEV )
		{
			$this->Registry->logger__do_log( __CLASS__ . "::convert_member_to_guest: " . $this->session_id , "INFO" );
		}

		/* Set type */
		$this->member['_session_type'] = "update";

		return $this->session_id;
	}


	/**
	 * Kill sessions
	 *
	 * @param    string    Any extra WHERE stuff
	 * @return   void
	 */
	private function destroy_sessions ( $where = "" )
	{
		$where .= ( $where ) ? ' OR ' : '';
		$where .= 'running_time < ' . ( UNIX_TIME_NOW - $this->Registry->config['security']['session_expiration'] );

		//-------------------------------------------
		// Grab session data to delete on destruct
		//-------------------------------------------

		$this->Registry->Db->cur_query = array(
				'do'     => "select",
				'table'  => "sessions",
				'where'  => $where
			);

		$result = $this->Registry->Db->simple_exec_query();

		foreach ( $result as $row )
		{
			$this->sessions_to_kill[ $row['id'] ] = $row;
		}

		if ( IN_DEV )
		{
			$this->Registry->logger__do_log( __CLASS__ . "::destroy_sessions: " . $where , "INFO" );
		}
	}


	/**
	 * Checks whether given email is taken or not
	 *
	 * @param    string    Email to check
	 * @return   boolean   TRUE if it exists, FALSE otherwise
	 */
	public function email__do_check_if_exists ( $email )
	{
		$_check = $this->load_member( $email, "" );
		if ( $_check['id'] )
		{
			return true;
		}
		else
		{
			return false;
		}
	}


	/**
	 * Get accessing IP address
	 */
	private function fetch_ip_address ()
	{
		$addrs = array();

		if ( $this->Registry->config['security']['xforward_matching'] )
		{
			foreach ( array_reverse( explode( ',', $this->Registry->Input->my_getenv( 'HTTP_X_FORWARDED_FOR' ) ) ) as $x_f )
			{
				$x_f = trim( $x_f );

				if ( preg_match( '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $x_f ) )
				{
					$addrs[] = $x_f;
				}
			}

			$addrs[] = $this->Registry->Input->my_getenv( 'HTTP_CLIENT_IP' );
			$addrs[] = $this->Registry->Input->my_getenv( 'HTTP_X_CLUSTER_CLIENT_IP' );
			$addrs[] = $this->Registry->Input->my_getenv( 'HTTP_PROXY_USER' );
		}

		$addrs[] = $this->Registry->Input->my_getenv( 'REMOTE_ADDR' );

		# Do we have one yet?
		foreach ( $addrs as $ip )
		{
			if ( !empty( $ip ) and filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) !== false )
			{
				$this->ip_address = $ip;
				break;
			}
		}

		//----------------------------------------
		// Make sure we take a valid IP address
		// if not, check for valid DNS
		//----------------------------------------

		if ( empty( $this->ip_address ) and !$this->Registry->Input->my_getenv('SHELL') and $this->Registry->Input->my_getenv('SESSIONNAME') != 'Console' )
		{
			if ( filter_has_var( INPUT_SERVER, "REMOTE_HOST" ) )
			{
				$this->ip_address = filter_input( INPUT_SERVER, "REMOTE_HOST", FILTER_VALIDATE_IP );
			}

			# Ok. now its bad :(
			if ( !$this->ip_address )
			{
				throw new Exception( "Could not determine Client's IP-address!" );
			}
		}
		return;
	}


	/**
	 * Get client's OS
	 *
	 * @return String Client OS
	 */
	private function fetch_os ()
	{
		$useragent = strtolower( $this->Registry->Input->my_getenv( 'HTTP_USER_AGENT' ) );

		if ( strstr( $useragent, 'mac' ) )
		{
			return "mac";
		}

		if ( preg_match( '#wi(n|n32|ndows)#', $useragent ) )
		{
			return "windows";
		}

		return "unknown";
	}


	/**
	 * Fetches SEO name, updating the table if required
	 *
	 * @param     array     Member data
	 * @return    string    SEO Name
	 */
	public function fetch_seo_name ( $m_data )
	{
		if ( ! is_array( $m_data ) or ! $m_data['id'] )
		{
			return;
		}

		if ( isset( $m_data['seo_name'] ) and ( $m_data['seo_name'] ) )
		{
			return $m_data['seo_name'];
		}
		elseif ( isset( $m_data['display_name'] ) and ( $m_data['display_name'] ) )
		{
			$_seo_name = $this->Registry->Input->make_seo_title( $m_data['display_name'] );

			$this->Registry->Db->cur_query = array(
					'do'	 => "update",
					'tables' => "members",
					'set'    => array(
							'seo_name' => $_seo_name,
						),
					'where'  => "id=" . $this->Registry->Db->quote( $m_data['id'], "INTEGER" ),
				);
			$this->Registry->Db->simple_exec_query();

			return $_seo_name;
		}
		else
		{
			return '-';
		}
	}


	/**
	 * Generates a login key
	 *
	 * @param    integer    Length of desired random chars to MD5
	 * @return   string     MD5 hash of random characters
	 */
	public function generate_autologin_key ( $len = 60 )
	{
		$pass = $this->generate_password_salt( $len );
		return md5( $pass );
	}


	/**
	 * Generates a compiled passhash.
	 * Returns a new MD5 hash of the supplied salt and MD5 hash of the password
	 *
	 * @param    string     User's salt (5 random chars)
	 * @param    string     User's MD5 hash of their password
	 * @return   string     MD5 hash of compiled salted password
	 */
	public function generate_compiled_pass_hash ( $salt, $md5_once_password )
	{
		return md5( md5( $salt ) . $md5_once_password );
	}


	/**
	 * Generates a random salt for passwords etc. Returns n length string of any character except backslash.
	 *
	 * @param    integer    Length for salt
	 * @return   string     n-character random string
	 */
	public function generate_password_salt ( $len = 5 )
	{
		$salt = "";
		for ( $_i=0; $_i<$len; $_i++ )
		{
			$_num = mt_rand( 33, 126 );
			if ( $_num == '92' )
			{
				$_num = 93;
			}
			$salt .= chr( $_num );
		}
		return $salt;
	}


	/**
	 * Retrieve a session based on a session id
	 *
	 * @param     string      Session id
	 * @return    boolean     Retrieved successfully
	 */
	public function get_session( $session_id="" )
	{
		//----------
		// INIT
		//----------

		$result     = array();
		$session_id = $this->Registry->Input->sanitize__md5_hash( $session_id );

		if ( $session_id )
		{
			$this->Registry->Db->cur_query = array(
					'do'	 => "select_row",
					'table'  => "sessions",
					'where'  => "id=" . $this->Registry->Db->platform->quoteValue( $session_id ),
				);
			$_session = $this->Registry->Db->simple_exec_query();

			if ( !empty( $_session ) and $_session['id'] )
			{
				# Kill any long running search thread...
				/* @todo
				if( $this->settings['kill_search_after'] )
				{
					if( $_session['search_thread_id'] and $_session['search_thread_time'] and $_session['search_thread_time'] < (time() - $this->settings['kill_search_after']) )
					{
						$this->DB->return_die	= true;
						$this->DB->kill( $_session['search_thread_id'] );
						$this->DB->return_die	= false;

						$this->DB->update( 'sessions', array( 'search_thread_id' => 0, 'search_thread_time' => 0 ), "id='" . $session_id . "'" );
					}
				}
				*/

				/* Test for browser.... */
				if ( $this->Registry->config['security']['match_browser'] )
				{
					if ( $_session['browser'] != $this->user_agent )
					{
						return $this->kill_authorize_attempt( $session_id, $_session );
					}
				}

				/* Test for IP Address... */
				if ( $this->Registry->config['security']['match_ipaddress'] )
				{
					if ( $_session['ip_address'] != $this->ip_address )
					{
						return $this->kill_authorize_attempt( $session_id, $_session );
					}
				}

				/* Still here? */
				return $this->allow_authorize_attempt( $session_id, $_session );
			}
			else
			{
				return $this->kill_authorize_attempt( $session_id, array() );
			}
		}
		else
		{
			return $this->kill_authorize_attempt( "", array() );
		}
	}


	/**
	 * Fetches Ignored users data
	 *
	 * @param     array    Member data
	 * @return    array    Array of ignored users
	 */
	public function ignored_users__do_fetch ( $member_data )
	{
		$ignored_users = array();

		if ( $member_data['id'] )
		{
			$ignored_users = unserialize( $member_data['ignored_users'] );
			return ( is_array( $ignored_users ) ) ? $ignored_users : array();
		}

		return $ignored_users;
	}


	/**
	 * Check to see if a member is ignorable or not
	 *
	 * @param   integer     Member's primary group ID
	 * @param   string      Comma delisted list of 'other' member groups
	 * @return  boolean     TRUE (member is ignorable) or FALSE (member can not be ignored)
	 */
	public function ignored_users__is_ignorable ( $mgroup, $mgroup_others )
	{
		if ( ! is_array( $this->Registry->config['userprofiles']['cannot_ignore_groups'] ) )
		{
			$this->Registry->config['userprofiles']['cannot_ignore_groups'] = ! empty ( $this->Registry->config['userprofiles']['cannot_ignore_groups'] )
				?
				explode( ",", $this->Registry->config['userprofiles']['cannot_ignore_groups'] )
				:
				array();
		}

		$_my_groups = array( $mgroup );

 		if ( $mgroup_others )
 		{
	 		$_my_groups = array_merge( $_my_groups, explode( "," , $mgroup_others ) );
 		}

 		foreach ( $_my_groups as $member_group )
 		{
	 		if ( in_array( $mgroup, $this->Registry->config['userprofiles']['cannot_ignore_groups'] ) )
	 		{
		 		return false;
	 		}
	 	}

		return true;
	}


	/**
	 * Check to see if a member is banned (or not)
	 *
	 * @param    string    Type of ban check (ip, name, email)
	 * @param    string    String to check
	 * @return   boolean   TRUE (banned) - FALSE (not banned)
	 */
	public function is_banned ( $type, $string )
	{
		$_banfilters_cache = $this->Registry->Cache->cache__do_get( "banfilters" );

		if ( is_array( $_banfilters_cache ) and count( $_banfilters_cache ) )
		{
			foreach( $_banfilters_cache as $entry )
			{
				$ip = str_replace( '\*', '.*', preg_quote( trim( $entry ), "/") );

				if ( $ip and preg_match( "/^" . $ip . "$/", $string ) )
				{
					return true;
				}
			}
		}

		return false;
	}


	/**
	 * Check to see if a member is in a group or not
	 *
	 * @param    mixed      Either INT (member_id) OR Array of member data [MUST at least include member_group_id and mgroup_others]
	 * @param    mixed      Either INT (group ID) or array of group IDs
	 * @param    boolean    TRUE (default, check secondary groups also), FALSE (check primary only)
	 * @return   boolean    TRUE (is in group) - FALSE (not in group)
	 */
	public function mgroup__is_in_group ( $member, $group, $_check_secondary = true )
	{
		$member_data  = ( is_array( $member ) ) ? $member : $this->load_member( $member, 'members' );
		$group        = ( is_array( $group ) )  ? $group  : array( $group );
		$others	      = explode( ',', $member_data['mgroup_others'] );

		if ( ! $member_data['mgroup'] or ! count( $group ) )
		{
			return false;
		}

		foreach ( $group as $gid )
		{
			if ( $gid == $member_data['mgroup'] )
			{
				return true;
			}

			if ( $_check_secondary and is_array( $others ) and count( $others ) )
			{
				if ( in_array( $gid, $others ) )
				{
					return true;
				}
			}
		}

		return false;
	}


	/**
	 * Set up a member's secondary groups
	 *
	 * @param    array    Member data
	 * @return   array    Member data with secondary group perms set properly
	 */
	public function mgroup_others__do_parse ( $m_data )
	{
		if ( isset( $m_data['mgroup_others'] ) and $m_data['mgroup_others'] )
		{
			$cache			= $this->Registry->Cache->cache['groups'];
			$groups_id		= explode( ',', $m_data['mgroup_others'] );
			$exclude		= array( 'g_title', 'g_icon', 'g_prefix', 'g_suffix', 'g_photo_max_vars' );
			$less_is_more	= array( 'g_search_flood' );
			$zero_is_best	= array( 'g_attach_max', /* 'g_attach_per_post', 'g_edit_cutoff', */ 'g_max_messages' );

			if ( count( $groups_id ) )
			{
				foreach( $groups_id as $pid )
				{
					if ( ! isset( $cache[ $pid ]['g_id'] ) or ! $cache[ $pid ]['g_id'] )
					{
						continue;
					}

					//-------------------------
					// Loop through and mix
					//-------------------------

					foreach( $cache[ $pid ] as $k => $v )
					{
						if ( ! in_array( $k, $exclude ) )
						{
							//-----------------------
							// Add to perm id list
							//-----------------------

							if ( $k == 'g_perm_id' )
							{
								$m_data['g_perm_id'] .= ',' . $v;
							}
							else if ( in_array( $k, $zero_is_best ) )
							{
								if ( $m_data[ $k ] == 0 )
								{
									continue;
								}
								elseif( $v == 0 )
								{
									$m_data[ $k ] = 0;
								}
								elseif ( $v > $m_data[ $k ] )
								{
									$m_data[ $k ] = $v;
								}
							}
							elseif ( in_array( $k, $less_is_more ) )
							{
								if ( $v < $m_data[ $k ] )
								{
									$m_data[ $k ] = $v;
								}
							}
							else
							{
								if ( $v > $m_data[ $k ] )
								{
									$m_data[ $k ] = $v;
								}
							}
						}
					}
				}
			}

			//-----------------
			// Tidy perms_id
			//-----------------

			$rmp = array();
			$tmp = explode( ',', $this->Registry->Input->clean__excessive_separators( $m_data['g_perm_id'], "," ) );

			if ( count( $tmp ) )
			{
				foreach( $tmp as $t )
				{
					$rmp[ $t ] = $t;
				}
			}

			if ( count( $rmp ) )
			{
				$m_data['g_perm_id'] = implode( ',', $rmp );
			}
		}

		return $m_data;
	}


	/**
	 * Check for an existing display or user name
	 *
	 * @param    string    Name to check
	 * @param    array     [ Optional Member Array ]
	 * @param    string	   name or display_name
	 * @param    boolean   Ignore display name changes check (e.g. for registration)
	 * @param    boolean   Do not clean name again (e.g. coming from $this->names__do_clean_and_check() )
	 * @return   mixed     Either an exception or ( TRUE if name exists. FALSE if name does *not* exist )
	 * Error Codes:
	 * NO_PERMISSION        This user cannot change their display name at all
	 * NO_MORE_CHANGES      The user cannot change their display name again in this time period
	 * NO_NAME              No display name (or shorter than 3 chars was given)
	 * ILLEGAL_CHARS        The display name contains illegal characters
	 */
	public function names__do_check_if_exists ( $name, $member = array(), $field = "display_name", $ignore = false, $cleaned = true )
	{
		if ( ! $cleaned )
		{
			$_cleaned_name  = $this->names__do_clean( $name, $field );
			$name           = $_cleaned_name['name'];

			if ( count( $_cleaned_name['errors'] ) )
			{
				throw new Exception( "ILLEGAL_CHARS" );
			}
		}

		//--------
		// INIT
		//--------

		$error           = "";
		$ban_filters     = array();
		$member          = ( isset( $member['id'] ) ) ? $member : $this->member;
		$_check_field    = ( $field == 'display_name' ) ? 'l_display_name' : 'l_username';
		if ( $member['id'] )
		{
			$_time_check = UNIX_TIME_NOW - 86400 * $this->member['g_dname_change']['timedelta'];
		}

		//-----------------
		// Public checks
		//-----------------

		if ( ACCESS_TO_AREA != 'admin' and $ignore != true )
		{
			if ( ! $this->Registry->config['namesettings']['auth_allow_dnames'] or $member['g_dname_changes'] < 1 or $member['g_dname_date'] < 1 )
			{
				throw new Exception( "NO_PERMISSION" );
			}

			/* Check new permissions */
			$_g = $this->Registry->Cache->cache__do_get_part( "member_groups" , $member['mgroup'] );

			if ( $_g['g_dname_change']['cond_value'] )
			{
				if ( $_g['g_dname_change']['cond_unit'] == 'days' )
				{
					/* days */
					if ( $member['joined'] > ( time() - ( 86400 * $_g['g_dname_change']['cond_value'] ) ) )
					{
						throw new Exception( "NO_PERMISSION" );
					}
				}
				else
				{
					/* @todo Posts */
					if ( $member['posts'] < $_g['g_dname_change']['cond_value'] )
					{
						throw new Exception( "NO_PERMISSION" );
					}
				}
			}

			//------------------------------
			// Grab # changes > 24 hours
			//------------------------------

			if ( $member['id'] )
			{
				$this->Registry->Db->cur_query = array(
						'do'     => "select_row",
						'fields' => array(
								'count'     => new Zend_Db_Expr( "COUNT(*)" ),
								'min_date'  => new Zend_Db_Expr( "MIN(dname_date)" ),
							),
						'table'  => "members_dname_changes",
						'where'  => array(
								"dname_member_id = " . $this->Registry->Db->quote( $member['id'] , "INTEGER" ),
								"dname_date > " . $_time_check,
							),
					);

				$name_count = $this->Registry->Db->simple_exec_query();

				$name_count['count']    = intval( $name_count['count'] );
				$name_count['min_date'] = intval( $name_count['min_date'] ) ? intval( $name_count['min_date'] ) : $_time_check;

				if ( intval( $name_count['count'] ) >= $member['g_dname_change']['amount'] )
				{
					throw new Exception( "NO_MORE_CHANGES" );
				}
			}
		}

		//-----------------------------
		// Are they banned [NAMES]?
		//-----------------------------

		$ban_filters = $this->Registry->Cache->cache__do_get( "banfilters" );

		if ( ACCESS_TO_AREA != 'admin' )
		{
			if ( isset( $ban_filters['name'] ) and is_array( $ban_filters['name'] ) and count( $ban_filters['name'] ) )
			{
				foreach ( $ban_filters['name'] as $n )
				{
					if ( $n == "" )
					{
						continue;
					}

					$n = str_replace( '\*', '.*' ,  preg_quote( $n, "/" ) );

					if ( preg_match( '/^' . $n . '$/i', $name ) )
					{
						return true;
						break;
					}
				}
			}
		}

		//-----------------------------
		// Check for existing name.
		//-----------------------------

		$this->Registry->Db->cur_query = array(
				'do'     => "select_row",
				'fields' => array( $field, "id" ),
				'table'  => "members",
				'where'  => array(
						array( $_check_field . " = ?"  , $this->Registry->Db->quote( strtolower( $name ) )        ),
						array( "id != ?"               , $this->Registry->Db->quote( $member['id'], "INTEGER" )   ),
					),
			);
		$result = $this->Registry->Db->simple_exec_query();
		if ( ! empty( $result ) )
 		{
 			return true;
		}

		//------------------------------------------------------------
		// Not allowed to select another's user- or display- name
		//------------------------------------------------------------

		if ( $this->Registry->config['namesettings']['auth_dnames_nologinname'] )
		{
			$this->Registry->Db->cur_query = array(
					'do'     => "select_row",
					'fields' => array( $field, "id" ),
					'table'  => "members",
					'where'  => $_check_field . "=" . $this->Registry->Db->quote( strtolower( $name ) ),
				);
			$check_name = $this->Registry->Db->simple_exec_query();
			if ( ! empty( $check_name ) )
	 		{
	 			if ( $member['id'] and $check_name['id'] != $member['id'] )
				{
					return true;
				}
			}
		}

		//--------------------------
		// Test for unicode name
		//--------------------------

		$_unicode_name = $this->names__do_get_unicode_version( $name );
		if ( $_unicode_name != $name )
		{
			//-----------------------------
			// Check for existing name.
			//-----------------------------

			$this->Registry->Db->cur_query = array(
					'do'     => "select_row",
					'fields' => array( "id" , "display_name" , "email" ),
					'table'  => "members",
					'where'  => $_check_field . "=" . $this->Registry->Db->quote( strtolower( $_unicode_name ) )
						. " AND id != " . $this->Registry->Db->quote( $member['id'] , "INTEGER" ),
				);
			$result = $this->Registry->Db->simple_exec_query();
			if ( ! empty( $result ) )
			{
				return true;
			}
		}

		return false;
	}


	/**
	 * Clean a username or display name
	 *
	 * @param    string	    Name
	 * @param    string     Field (name or display_name)
	 * @return   array      array( 'name' => $cleaned_name, 'errors' => array() )
	 */
	private function names__do_clean ( $name, $field='display_name' )
	{
		$original = $name;
		$name = trim( $name );
		$name = preg_replace( "/\s{2,}/", " ", $name );

		//------------------------
		// Remove line breaks
		//------------------------

		if ( $this->Registry->config['namesettings']['usernames_nobr'] )
		{
			$name = $this->Registry->Input->br2nl( $name );
			$name = str_replace( array( "\n", "\r" ), "", $name );
		}

		//-----------------------------------------
		// Remove sneaky spaces
		//-----------------------------------------

		if ( $this->Registry->config['security']['strip_space_chr'] )
		{
			/* use hexdec to convert between '0xAD' and chr */
			$name = $this->Registry->Input->sanitize__clean_control_characters__low( $name );
		}

		//-------------------------
		// Trim after above ops
		//-------------------------

		$name = trim( $name );

		//----------------------
		// Test unicode name
		//----------------------

		$unicode_name = $this->names__do_get_unicode_version( $name );

		//------------------------
		// Do we have a name?
		//------------------------

		if ( $field == 'name' or ( $field == 'display_name' and $this->Registry->config['namesettings']['auth_allow_dnames'] ) )
		{
			if ( ! $name or $this->Registry->Input->mb_strlen( $name ) < 3 or $this->Registry->Input->mb_strlen( $name ) > $this->Registry->config['namesettings']['max_user_name_length'] )
			{
				$key = ( $field == 'display_name' ) ? 'Display name' : 'Username';

				return array( 'name' => $original, 'errors' => array( $key . " exceeds length limit of 3-" . $this->Registry->config['namesettings']['max_user_name_length'] . " characters!" ) );
			}
		}

		//-----------------------------------------
		// Blocking certain chars in username?
		//-----------------------------------------

		if ( ! empty( $this->Registry->config['namesettings']['username_characters'] ) )
		{
			$check_against = preg_quote( $this->Registry->config['namesettings']['username_characters'], "/" );

			if ( ! preg_match( "/^[" . $check_against . "]+$/i", $name ) )
			{
				return array( 'name' => $original, 'errors' => array( "Username contains illegal characters! " . $this->Registry->config['namesettings']['username_errormsg'] . $this->Registry->config['namesettings']['username_characters'] ) );
			}
		}

		//-----------------------------------------
		// Manually check against bad chars
		//-----------------------------------------

		if (
			strpos( $unicode_name, '&#92;'   ) !== false or
			strpos( $unicode_name, '&#quot;' ) !== false or
			strpos( $unicode_name, '&#36;'   ) !== false or
			strpos( $unicode_name, '&#lt;'   ) !== false or
			strpos( $unicode_name, '$'       ) !== false or
			strpos( $unicode_name, ']'       ) !== false or
			strpos( $unicode_name, '['       ) !== false or
			strpos( $unicode_name, ','       ) !== false or
			strpos( $unicode_name, '|'       ) !== false or
			strpos( $unicode_name, '&#gt;'   ) !== false
		)
		{
			return array( 'name' => $original, 'errors' => array( "Username contains illegal characters!" ) );
		}

		return array( 'name' => $name, 'errors' => array() );
	}


	/**
	 * Cleans a username or display name, also checks for any errors
	 *
	 * @param    string     Username or display name to clean and check
	 * @param    array      [ Optional Member Array ]
	 * @param    string     name or display_name
	 * @return   array      Returns an array with 2 keys: 'username' OR 'display_name' => the cleaned username, 'errors' => any errors found
	 **/
	public function names__do_clean_and_check ( $name, $member=array(), $field='display_name' )
	{
		//-------------------------
		// Clean the name first
		//-------------------------

		$_cleaned_name = $this->names__do_clean( $name, $field );
		if ( count( $_cleaned_name['errors'] ) )
		{
			if ( $field == 'display_name' )
			{
				return array( 'display_name' => $_cleaned_name['name'], 'errors' => array( 'dname' => $_cleaned_name['errors'][0] ) );
			}
			else
			{
				return array( 'username' => $_cleaned_name['name'], 'errors' => array( 'username' => $_cleaned_name['errors'][0] ) );
			}
		}

		//---------------------------------------------
		// Name is clean, make sure it doesn't exist
		//---------------------------------------------

		try
		{
			if ( ! $this->names__do_check_if_exists( $_cleaned_name['name'], $member, $field, true, true ) )
			{
				if ( $field == 'display_name' )
				{
					return array( 'display_name' => $_cleaned_name['name'], 'errors' => array() );
				}
				else
				{
					return array( 'username' => $_cleaned_name['name'], 'errors' => array() );
				}
			}
			else
			{
				if ( $field == 'display_name' )
				{
					return array( 'display_name' => $_cleaned_name['name'], 'errors' => array( 'dname' => "The display name is already taken by another member" ) );
				}
				else
				{
					return array( 'username' => $_cleaned_name['name'], 'errors' => array( 'username' => "The username is already taken by another member!" ) );
				}
			}
		}
		catch( Exception $e )
		{
			//-------------------------------------------
			// Name exists, let's return appropriately
			//-------------------------------------------

			if ( $field == 'display_name' )
			{
				switch ( $e->getMessage() )
				{
					case 'NO_NAME':
						return array( 'display_name' => $_cleaned_name['name'], 'errors' => array( 'dname' => "Name must be longer than 3 characters and shorter than %s!" ) );
					break;

					case 'ILLEGAL_CHARS':
						return array( 'members_display_name' => $_cleaned_name['name'], 'errors' => array( 'dname' => "Contains one or more of these illegal characters: [ ] | ; ,  &#036; &#92; &lt; &gt; &quot;" ) );
					break;
				}
			}
			else
			{
				switch( $e->getMessage() )
				{
					case 'NO_NAME':
						return array( 'username' => $_cleaned_name['name'], 'errors' => array( 'username' => "Name must be longer than 3 characters and shorter than %s!" ) );
					break;

					case 'ILLEGAL_CHARS':
						return array( 'username' => $_cleaned_name['name'], 'errors' => array( 'username' => "Contains one or more of these illegal characters: [ ] | ; ,  &#036; &#92; &lt; &gt; &quot;" ) );
					break;
				}
			}
		}
	}


	/**
	 * Get unicode version of name
	 *
	 * @access	protected
	 * @param	string		Name
	 * @return	string		Unicode Name
	 */
	private function names__do_get_unicode_version ( $name )
	{
		$unicode_name  = preg_replace_callback( '/&#([0-9]+);/si', create_function( '$matches', 'return chr($matches[1]);' ), $name );
		$unicode_name  = str_replace( "'" , '&#39;', $name );
		$unicode_name  = str_replace( "\\", '&#92;', $name );

		return $unicode_name;
	}


	/**
	 * Process current user agent
	 *
	 * @param    string     Type of session (update/create)
	 * @return   array      Array of user agent info from the DB
	 */
	private function process_user_agent ( $type = "update" )
	{
		//----------
		// INIT
		//----------

		$user_agent = array(
				'uagent_key'     => "__NONE__",
				'uagent_version' => 0,
				'uagent_name'    => "",
				'uagent_type'    => "",
				'uagent_bypass'  => 0
			);

		//-------------------------
		// Do we need to update?
		//-------------------------

		if ( empty( $this->session_data['uagent_key'] ) or $this->session_data['uagent_key'] == '__NONE__' or ( $this->user_agent != $this->session_data['browser'] ) )
		{
			if ( IN_DEV )
			{
				$this->Registry->logger__do_log( __CLASS__ . "::process_user_agent: Retrieving user agent information from the DB" , "INFO" );
			}

			//-----------------------------------------
			// Get useragent stuff
			//-----------------------------------------

			$user_agent = new \Persephone\Session\User_agents( $this->Registry );
			$user_agent = $user_agent->find_user_agent_id( $this->user_agent );

			if ( $user_agent['uagent_key'] === null )
			{
				$user_agent = array(
						'uagent_key'     => "__NONE__",
						'uagent_version' => 0,
						'uagent_name'    => "",
						'uagent_type'    => "",
						'uagent_bypass'  => 0
					);
			}
			else
			{
				$user_agent['uagent_bypass'] = 0;
			}
		}
		else
		{
			$user_agent['uagent_key']     = $this->session_data['uagent_key'];
			$user_agent['uagent_version'] = $this->session_data['uagent_version'];
			$user_agent['uagent_type']    = $this->session_data['uagent_type'];
			$user_agent['uagent_bypass']  = $this->session_data['uagent_bypass'];
			# For search engines only
			$user_agent['uagent_name']    = $this->session_data['member_name'];
		}

		return $user_agent;
	}


	/**
	 * Sets session cookie parameters
	 *
	 * @param  integer   Specifies the lifetime of the cookie in seconds which is sent to the browser. The value 0 means "until the browser is closed." Defaults to 0.
	 * @param  string    Specifies path to set in session_cookie. Defaults to /.
	 * @param  string    Specifies the domain to set in session_cookie. Default is none at all meaning the host name of the server which generated the cookie according to cookies specification.
	 * @param  boolean   Specifies whether cookies should only be sent over secure connections. Defaults to off.
	 * @param  boolean   Marks the cookie as accessible only through the HTTP protocol. This means that the cookie won't be accessible by scripting languages, such as JavaScript.
	 */
	private function session_set_cookie_params ( $lifetime = 0, $path = "/", $domain = "", $secure = false, $httponly = false )
	{
		if ( version_compare( PHP_VERSION, "5.2", ">=" ) )
		{
			# PHP 5.2.0 or later
			session_set_cookie_params( $lifetime, $path, $domain, $secure, $httponly );
		}
		else
		{
			# PHP 4.0.4 or later
			session_set_cookie_params( $lifetime, $path, $domain, $secure );
		}

		return;
	}


	/**
	 * Sets up a search engine's data and permissions
	 *
	 * @param    array    Array of useragent information
	 * @return   void
	 */
	public function set_search_engine( $user_agent )
	{
		$group = $this->Registry->Cache->cache__do_get_part( "member_groups", intval( $this->Registry->config['searchenginespiders']['spider_group'] ) );

		foreach ( $group as $k => $v )
		{
			$this->member[ $k ] = $v;
		}

		# Fix up member and group data
		$this->member['display_name']         = $user_agent['uagent_name'];
		$this->member['_display_name']        = $user_agent['uagent_name'];
		$this->member['name']                 = $user_agent['uagent_name'];
		$this->member['mgroup']               = $this->member['g_id'];
		$this->member['restrict_post']        = 1;
		$this->member['g_use_search']         = 0;
		$this->member['g_email_friend']       = 0;
		$this->member['g_edit_profile']       = 0;
		$this->member['g_use_pm']             = 0;
		$this->member['g_is_supmod']          = 0;
		$this->member['g_access_cp']          = 0;
		$this->member['g_access_offline']     = 0;
		$this->member['g_avoid_flood']        = 0;
		$this->member['id']                   = 0;
		$this->member['_cache']               = array();

		# Fix up permission strings
		$this->perm_id = $group['g_perm_id'];
		$this->perm_id_array = explode( ",", $group['g_perm_id'] );

		# It's allliiiiiiveeeeee
		$this->is_not_human = true;

		# Logging?
		if ( $this->Registry->config['searchenginespiders']['spider_visit'] )
		{
			$this->Registry->Db->cur_query = array(
					'do'	 => "insert",
					'table'  => "spider_logs",
					'set'    => array(
							'bot'          => $user_agent['uagent_key'],
							'query_string' => htmlentities( strip_tags( str_replace( '\\', '',  str_replace( "'", "", my_getenv('QUERY_STRING')) ) ) ),
							'ip_address'   => $this->ip_address,
							'entry_date'   => UNIX_TIME_NOW,
						)
				);
			$this->Registry->Db->simple_exec_query_shutdown();
		}
	}


	/**
	 * Set current member to the member ID specified
	 *
	 * @param     integer    Member ID
	 * @return    void
	 */
	public function set_member ( $member_id )
	{
		//--------------
		// INIT
		//--------------

		$member_id = intval( $member_id );
		$addrs     = array();

		//---------------------------------------------
		// If we have a member ID, set up the member
		//---------------------------------------------

		if ( $member_id )
		{
			$this->member = $this->load_member( $member_id );

			if ( $this->member['id'] )
			{
				$this->set_up_member();

				# Form hash
				$this->form_hash = md5( $this->member['email'] . "&" . $this->member['login_key'] . "&" . $this->member['joined'] );
			}
			else
			{
				$this->member = $this->set_up_guest();

				$this->perm_id = ( isset( $this->member['org_perm_id'] ) and $this->member['org_perm_id'] )
					?
					$this->member['org_perm_id']
					:
					$this->member['g_perm_id'];
				$this->perm_id_array = explode( ",", $this->perm_id );

				# Form hash
				$this->form_hash = md5("this is only here to prevent it breaking on guests");
			}

			/* Get the ignored users */

			if ( defined( "ACCESS_TO_AREA" ) and ACCESS_TO_AREA != 'admin' )
			{
				/* Ok, Fetch ignored users */
				$this->ignored_users = $this->ignored_users__do_fetch( $this->member );
			}
		}
		else
		{
			$this->member = $this->set_up_guest();

			$this->perm_id = ( isset( $this->member['org_perm_id'] ) )
					?
					$this->member['org_perm_id']
					:
					$this->member['g_perm_id'];
			$this->perm_id_array = explode( ",", $this->perm_id );

			# Form hash
			$this->form_hash = md5("this is only here to prevent it breaking on guests");

			$this->Registry->Input->my_setcookie( "member_id" , "0", -1  );
			$this->Registry->Input->my_setcookie( "pass_hash" , "0", -1  );
		}

		if ( $this->member['id'] )
		{
			$this->language_id = $this->member['language'];
		}
		elseif ( $this->Registry->Input->my_getcookie('language') )
		{
			$this->language_id = $this->Registry->Input->my_getcookie('language');
		}
	}


	/**
	 * Set up defaults for a guest user
	 *
	 * @param    string    Guest name
	 * @return   array     Guest record
	 */
	private function set_up_guest ( $name = "Guest" )
	{
		$cache = $this->Registry->Cache->cache__do_load( array("member_groups") );

		$array = array(
				'name'              => $name,
				'display_name'      => $name,
				'_display_name'     => $name,
				'seo_name'          => $this->Registry->Input->make_seo_title( $name ),
				'id'                => 0,
				'password'          => "",
				'email'             => "",
				'title'             => "",
				'mgroup'            => $this->Registry->config['security']['guest_group'],
				'g_title'           => $cache[ $this->Registry->config['security']['guest_group'] ]['g_title'],
				'joined'            => "",
				'location'          => "",
				'auto_dst'          => 0,
				'is_mod'            => 0,
				'last_visit'        => UNIX_TIME_NOW,
				'login_anonymous'   => "",
				'mgroup_others'     => "",
				'org_perm_id'       => "",
				'_cache'            => array( 'qr_open' => 0, 'friends' => array() ),
				'ignored_users'     => null,
				'editor_choice'     => "std",
				'_group_formatted'  => $cache[ $this->Registry->config['security']['guest_group'] ]['g_title'], $this->Registry->config['security']['guest_group'],
			);

		return is_array( $cache[ $this->Registry->config['security']['guest_group'] ] ) ? array_merge( $array, $cache[ $this->Registry->config['security']['guest_group'] ] ) : $array;
	}


	/**
	 * Set up a member
	 *
	 * @return   void
	 */
	private function set_up_member ()
	{
		//----------
		// INIT
		//----------

		$cache = $this->Registry->Cache->cache__do_load( array("member_groups") );

		//-----------------
		// Unpack cache
		//-----------------

		if ( isset( $this->member['cache'] ) )
		{
			$this->member['_cache'] = unserialize( $this->member['cache'] );
		}
		else
		{
			$this->member['_cache'] = array();
		}

		if ( ! isset( $this->member['_cache']['friends'] ) or ! is_array( $this->member['_cache']['friends'] ) )
		{
			$this->member['_cache']['friends'] = array();
		}

		//--------------------------------
		// Set up main 'display' group
		//--------------------------------

		if ( is_array( $cache[ $this->member['mgroup'] ] ) )
		{
			$this->member = array_merge( $this->member, $cache[ $this->member['mgroup'] ] );
		}

		//--------------------------
		// Work out permissions
		//--------------------------

		$this->member = $this->mgroup_others__do_parse( $this->member );

		$this->perm_id = ( isset( $this->member['org_perm_id'] ) and $this->member['org_perm_id'] )
			?
			$this->member['org_perm_id']
			:
			$this->member['g_perm_id'];
		$this->perm_id_array = explode( ",", $this->perm_id );

		//-----------------------------------------------------
		// Synchronise the last visit and activity times if
		// we have some in the member profile
		//-----------------------------------------------------

		if ( ! $this->member['last_activity'] )
	   	{
	   		$this->member['last_activity'] = UNIX_TIME_NOW;
	   	}

		//-----------------------------------------------------
		// If there hasn't been a cookie update in 2 hours,
		// we assume that they've gone and come back
		//-----------------------------------------------------

		if ( ! $this->member['last_visit'] )
		{
			//----------------------------------
			// No last visit set, do so now!
			//----------------------------------

			$this->Registry->Db->cur_query = array(
					'do'	 => "update",
					'tables' => "members",
					'set'    => array(
							'last_visit'    => $this->member['last_activity'],
							'last_activity' => UNIX_TIME_NOW,
						),
					'where'  => "id=" . $this->Registry->Db->quote( $this->member['id'], "INTEGER" ),
				);
			$this->Registry->Db->simple_exec_query_shutdown();
			$this->member['last_visit'] = $this->member['last_activity'];
		}
		elseif ( ( UNIX_TIME_NOW - $this->member['last_activity'] ) > 300 )
		{
			//-------------------------------------------------
			// If the last click was longer than 5 mins ago
			// and this is a member. Update their profile.
			//-------------------------------------------------

			list( $be_anon, $loggedin ) = explode( '&', $this->member['login_anonymous'] );

			$this->Registry->Db->cur_query = array(
					'do'	 => "update",
					'tables' => "members",
					'set'    => array(
							'login_anonymous'  => $be_anon . "&1",
							'last_activity'    => UNIX_TIME_NOW,
						),
					'where'  => "id=" . $this->Registry->Db->quote( $this->member['id'], "INTEGER" ),
				);
			$this->Registry->Db->simple_exec_query_shutdown();
		}

		//----------------------------------------
		// Knock out some large text fields
		// we don't need on a day-to-day basis.
		//----------------------------------------

		unset( $this->member['notes'], $this->member['bio'], $this->member['links'] );
	}


	/**
	 * Update a guest's session
	 *
	 * @return    boolean    Updated successfully
	 */
	private function update_guest_session ()
	{
		//---------
		// INIT
		//---------

		$member_name  = "";
		$mgroup = $this->Registry->config['security']['guest_group'];
		$login_type = intval( $this->Registry->Cache->cache['member_groups'][ $this->Registry->config['security']['guest_group'] ]['g_hide_from_list'] );

		//-----------------------------------------
		// Make sure we have a session id.
		//-----------------------------------------

		if ( ! $this->session_id )
		{
			$this->create_guest_session();
			return false;
		}

		//------------------------
		// Get module settings
		//------------------------

		/* @todo $vars = $this->_getLocationSettings(); */

		//------------------
		// Still update?
		//------------------

		if ( ! $this->do_update )
		{
			return false;
		}

		$user_agent = $this->process_user_agent( "update" );

		//---------------------------
		// Is it a search engine?
		//---------------------------

		if ( $user_agent['uagent_type'] == 'search' )
		{
			$this->session_id = substr( $user_agent['uagent_key'] . "=" . str_replace( '.', '', $this->ip_address ) . "_session", 0, 60 );
			$member_name      = $user_agent['uagent_name'];
			$mgroup           = $this->Registry->config['searchenginespiders']['spider_group'];
			$login_type       = intval( $this->Registry->config['searchenginespiders']['spider_anon'] );

			if ( IN_DEV )
			{
				$this->Registry->logger__do_log( __CLASS__ . "::update_guest_session: Updating SEARCH ENGINE session: " . $this->session_data['id'] , "INFO" );
			}
		}
		else
		{
			if ( IN_DEV )
			{
				$this->Registry->logger__do_log( __CLASS__ . "::update_guest_session: Updating GUEST session: " . $this->session_data['id'] , "INFO" );
			}
		}

		$_session_data = array(
				'member_name'     => $member_name,
				'member_id'       => 0,
				'member_group'    => $mgroup,
				'login_type'      => $login_type,
				'running_time'    => UNIX_TIME_NOW,
				'location'        => "", // @todo
				'in_error'        => 0,
				'uagent_key'      => $user_agent['uagent_key'],
				'uagent_version'  => $user_agent['uagent_version'],
				'uagent_type'     => $user_agent['uagent_type'],
			);

		//----------------------------------------------------------------------------------------
		// Before this function is called, a guest is set up via Session::set_member(0)
		// We want to override this now to provide search engine settings for the 'member'
		//----------------------------------------------------------------------------------------

		if ( $user_agent['uagent_type'] == 'search' )
		{
			$this->set_search_engine( $user_agent );

			# Reset some data
			$this->session_type = "cookie";
			$this->session_id   = "";
		}

		# Set type
		$this->member['_session_type'] = "update";

		# Mark for SAVE
		$this->sessions_to_save[ $this->session_id ] = $_session_data;

		return true;
	}


	/**
	 * Update a member's session
	 *
	 * @return    boolean    Updated successfully
	 */
	private function update_member_session ()
	{
		//-----------------------------------------
		// Make sure we have a session id.
		//-----------------------------------------

		if ( ! $this->session_id )
		{
			$this->create_member_session();
			return true;
		}

		if ( ! $this->member['id'] )
		{
			$this->set_member( 0 );
			$this->create_guest_session();
			return false;
		}

		if ( ( UNIX_TIME_NOW - $this->member['last_activity'] ) > $this->Registry->config['security']['session_expiration'] )
		{
			// Session is expired - create new session
			$this->create_member_session();
			return true;
		}

		//-------------------------
		// Get module settings
		//-------------------------

		// @todo $vars = $this->_getLocationSettings();

		//------------------
		// Still update?
		//------------------

		if ( ! $this->do_update )
		{
			return true;
		}

		if ( IN_DEV )
		{
			$this->Registry->logger__do_log( __CLASS__ . "::update_guest_session: Updating MEMBER session: " . $this->session_data['id'] , "INFO" );
		}

		$user_agent = $this->process_user_agent( "update" );

		$this->Registry->Input->my_setcookie( "pass_hash", $this->member['login_key'], ( $this->Registry->config['security']['login_key_expire'] ? 0 : 1 ), $this->Registry->config['security']['login_key_expire'] );

		/* Save the last click */
		$this->member['last_click'] = $this->session_data['running_time'];

		//----------------
		// Set up data
		//----------------

		$session_data = array(
				'member_name'			=> $this->member['display_name'],
				'seo_name'				=> $this->fetch_seo_name( $this->member ),
				'member_id'				=> intval($this->member['id']),
				'member_group'			=> $this->member['mgroup'],
				'login_type'			=> intval( substr( $this->member['login_anonymous'], 0, 1 ) ),
				'running_time'			=> UNIX_TIME_NOW,
				'location'				=> "", // @todo
				'in_error'				=> 0,
				'uagent_key'			=> $user_agent['uagent_key'],
				'uagent_version'		=> $user_agent['uagent_version'],
				'uagent_type'			=> $user_agent['uagent_type'],
			);

		//----------------------------------------------------------------------------------------
		// Before this function is called, a guest is set up via Session::set_member(0)
		// We want to override this now to provide search engine settings for the 'member'
		//----------------------------------------------------------------------------------------

		if ( $user_agent['uagent_type'] == 'search' )
		{
			$this->set_search_engine( $user_agent );

			/* Reset some data */
			$this->session_type = "cookie";
			$this->session_id   = "";
		}

		/* Set type */
		$this->member['_session_type'] = "update";

		$this->sessions_to_save[ $this->session_id ] = $session_data;

		return true;
	}
}

?>
