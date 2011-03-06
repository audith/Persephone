<?php

if ( ! defined( "INIT_DONE" ) )
{
	print "Improper access! Exiting now...";
	exit();
}

/**
 * Handlers : Login
 *
 * @package  Audith CMS
 * @author   Shahriyar Imanov <shehi@imanov.name>
 * @version  1.0
 */

class Module_Handler
{
	/**
	 * API Object Reference
	 * @var Object
	 */
	private $API;

	/**
	 * Flag :: Account unlocked
	 *
	 * @var integer
	 */
	public $account_unlock = 0;

	/**
	 * Main container for retrieved content
	 * @var mixed
	 */
	private $content;

	/**
	 * Default HTTP header set for the module
	 * @var array
	 */
	public $http_headers_default = array();

	/**
	 * Flag :: ACP Login?
	 * @var integer
	 */
	private $is_admin_auth = 0;

	/**
	 * Login methods
	 * @var array
	 */
	private $login_methods = array();

	/**
	 * Login confs
	 * @var array
	 */
	private $login_confs = array();

	/**
	 * Member details
	 * @var array
	 */
	private $member = array( "id" => 0 );

	/**
	 * Login-Method Container
	 * @var object
	 */
	private $module;

	/**
	 * Module Processor Map [method access]
	 * @var array
	 */
	public $process_map = array();

	/**
	 * Input::post
	 *
	 * @var array
	 */
	private $request;

	/**
	 * Return code
	 * @var string
	 */
	private $return_code = "WRONG_AUTH";

	/**
	 * Return details
	 * @var string
	 */
	private $return_details = "";

	/**
	 * Module's currently running subroutine
	 * @var array
	 */
	private $running_subroutine = array();

	/**
	 * Module Structural Map
	 * @var array
	 */
	public $structural_map = array();


    /**
	 * Constructor
	 *
	 * @param    API    API Object Reference
	 */
	public function __construct ( API $API )
    {
		//----------
    	// Prelim
    	//----------

    	$this->API = $API;
    	$this->request = $this->API->Input->input;
    	$this->request['referer'] = isset( $this->request['referer'] ) ? urldecode( $this->request['referer'] ) : "";
    	$this->member =& $this->API->member;

		require_once( PATH_SOURCES . '/login_auth/login_core.php' );

    	$classes	= array();
    	$configs	= array();
    	$methods	= array();

    	//----------------------
    	// Do we have cache?
    	//----------------------

    	$cache = $this->API->Cache->cache__do_get( "login_methods" );

    	if ( is_array( $cache ) and count( $cache ) )
		{
			foreach ( $cache as $login_method )
			{
				if ( $login_method['login_enabled'] )
				{
					if ( file_exists( PATH_SOURCES . "/login_auth/" . $login_method['login_folder_name'] . "/auth.php" ) )
					{
						$classes[ $login_method['login_order'] ]                = PATH_SOURCES . "/login_auth/" . $login_method['login_folder_name'] . "/auth.php";
						$configs[ $login_method['login_order'] ]                = PATH_SOURCES . "/login_auth/" . $login_method['login_folder_name'] . "/conf.php";
						$this->login_methods[ $login_method['login_order'] ]    = $login_method;
						$this->login_confs[ $login_method['login_order'] ]      = array();

						if ( file_exists( $configs[ $login_method['login_order'] ] ) )
						{
							$LOGIN_CONF	= array();

							require( $configs[ $login_method['login_order'] ] );
							$this->login_confs[ $login_method['login_order'] ]	= $LOGIN_CONF;
						}

						require_once( $classes[ $login_method['login_order'] ] );
						$this->login_modules[ $login_method['login_order'] ]    = new Login_Method( $this->API, $login_method, $this->login_confs[ $login_method['login_order'] ] );
					}
				}
			}
		}

    	//-------------------
    	// No cache info
    	//-------------------

    	else
		{
			$this->API->Db->cur_query = array(
					'do'     => "select_row",
					'table'  => "login_methods",
					'where'  => "login_folder_name=" . $this->API->Db->db->quote( "internal" ),
				);

			$login_method = $this->API->Db->simple_exec_query();

    		if ( $login_method['login_id'] )
			{
	    		$classes[ 0 ]              = PATH_SOURCES . "/login_auth/internal/auth.php";
				$this->login_methods[ 0 ]  = $login_method;
				$this->login_confs[ 0 ]    = array();

				require_once( $classes[ 0 ] );
				$this->login_modules[ 0 ]        = new Login_Method( $this->registry, $login_method, array() );
			}
		}

    	//--------------------------------------------
    	// If we're here, there is no enabled login
    	// handler and internal was deleted
    	//--------------------------------------------

    	if ( ! count( $this->login_modules ) )
		{
			$this->API->logger__do_log( __CLASS__ . ": No login methods available to process user login!" , "ERROR" );
		}

		//----------------------
		// Pass of some data
		//----------------------

		foreach( $this->login_modules as $k => &$obj_reference )
		{
			$obj_reference->is_admin_auth	= $this->is_admin_auth;
			$obj_reference->login_method	= $this->login_methods[ $k ];
			$obj_reference->login_conf		= $this->login_confs[ $k ];
		}

		//------------------
		// STRUCTURAL MAP
		//------------------

		$this->structural_map = array(
				'm_subroutines'                  =>
					array(
							'login'                             =>
								array(
										's_name'                         => 'login',
										's_service_mode'                 => 'write-only',
										's_pathinfo_uri_schema'          => 'login',
										's_pathinfo_uri_schema_parsed'   => 'login',
										's_qstring_parameters'           => array(),
										's_fetch_criteria'               => array(),
										's_data_definition'              => array(),
										'm_unique_id'                    => "{DFE3BB13-AABE820D-E1ECAEC2-0CDC0F82}",
									),
							'logout'                            =>
								array(
										's_name'                         => 'logout',
										's_service_mode'                 => 'read-only',
										's_pathinfo_uri_schema'          => 'logout',
										's_pathinfo_uri_schema_parsed'   => 'logout',
										's_qstring_parameters'           => array(),
										's_fetch_criteria'               => array(),
										's_data_definition'              => array(),
										'm_unique_id'                    => "{DFE3BB13-AABE820D-E1ECAEC2-0CDC0F82}",
									),
							'register'                          =>
								array(
										's_name'                         => 'register',
										's_service_mode'                 => 'read-write',
										's_pathinfo_uri_schema'          => 'register',
										's_pathinfo_uri_schema_parsed'   => 'register',
										's_qstring_parameters'           => array(),
										's_fetch_criteria'               => array(),
										's_data_definition'              => array(),
										'm_unique_id'                    => "{DFE3BB13-AABE820D-E1ECAEC2-0CDC0F82}",
									),
							'validate'                          =>
								array(
										's_name'                         => 'validate',
										's_service_mode'                 => 'read-only',
										's_pathinfo_uri_schema'          => 'validate/mid-(?P<mid>\d+)/aid-(?P<aid>[a-z0-9]{32})',
										's_pathinfo_uri_schema_parsed'   => 'validate/mid-(?P<mid>\d+)/aid-(?P<aid>[a-z0-9]{32})',
										's_qstring_parameters'           => array(
												'mid'                               => array(
														'request_regex'                       => '\d+',
														'_is_mandatory'                       => TRUE,
													),
												'aid'                               => array(
														'request_regex'                       => '[a-z0-9]{32}',
														'_is_mandatory'                       => TRUE,
													),
											),
										's_fetch_criteria'               => array(),
										's_data_definition'              => array(),
										'm_unique_id'                    => "{DFE3BB13-AABE820D-E1ECAEC2-0CDC0F82}",
									),
						),
			);
    }


	/**
	 * content_do()
	 */
	public function content__do ( & $running_subroutine, $action )
	{
		$this->running_subroutine = $running_subroutine;

		$this->process_map = array(
				'login'                          =>
					array(
							'default'                    => null,
							'do_login'                   => "login",
						),
				'logout'                         =>
					array(
							'default'                    => "logout",
						),
				'register'                       =>
					array(
							'default'                    => "register__do_prepare",
							'do_register'                => "register__do_process",
						),
				'validate'                       =>
					array(
							'default'                    => "validate",
						),
			);

		if (
			! isset( $this->process_map[ $this->running_subroutine['s_name'] ] )
			or
			! isset( $this->process_map[ $this->running_subroutine['s_name'] ][ $action ] )
		)
		{
			header( "HTTP/1.1 400 Bad Request" );
		}
		else
		{
			$_methods = $this->process_map[ $this->running_subroutine['s_name'] ][ $action ];
			$_methods = explode( "|" , $_methods );

			foreach ( $_methods as $_method_name )
			{
				/* Each method alters the content, and the subsequent method uses
				   that altered version (using references) and alters it at the end. */
				$this->content['content'] = $this->$_method_name();
			}
			return $this->content;
		}
	}


	/**
	 * Wrapper for login_authenticate() - returns more information
	 *
	 * @return   mixed   array [0=Words to show, 1=URL to send to, 2=error message language key] or void [if successful]
	 */
	private function login ()
	{
    	$url        = "";
    	$member     = array();
    	$username   = "";
    	$email      = "";
		$password   = trim( $this->request['password'] );
		$errors     = "";
		$core       = array();

		//-----------------------------------------
		// Is this a username or email address?
		//-----------------------------------------

		if ( $this->API->Input->check_email_address( $this->request['username'] ) )
		{
			$email = $this->request['username'];
		}
		else
		{
			$username = $this->request['username'];
		}

		//-------------------
		// Check auth
		//-------------------

		$this->login_authenticate( $username, $email, $password );

		$member = $this->member;

		//------------------------
		// Check return code...
		//------------------------

		if ( $this->return_code != 'SUCCESS' )
		{
			if ( $this->return_code == 'MISSING_DATA' )
			{
				return array( 'faultCode' => 0 , 'faultMessage' => "Missing data!<br />Please fill-in username/email-address and password to continue..." );
			}
			elseif ( $this->return_code == 'ACCOUNT_LOCKED' )
			{
				$extra = "<!-- -->";

				if ( $this->API->config['security']['bruteforce_unlock'] )
				{
					if ( $this->account_unlock )
					{
						$time = UNIX_TIME_NOW - $this->account_unlock;
						$time = ( $this->API->config['security']['bruteforce_period'] - ceil( $time / 60 ) > 0 ) ? $this->API->config['security']['bruteforce_period'] - ceil( $time / 60 ) : 1;
					}
				}

				return array( 'faultCode' => 0 , 'faultMessage' => "Account lock detected!<br />This account will be automatically unlocked in " . $time . " minutes." );
			}
			elseif ( $this->return_code == 'WRONG_OPENID' )
			{
				return array( 'faultCode' => 0 , 'faultMessage' => "OpenID Authentication failed!<br />Please try again later or choose another login method!" );
			}
			elseif ( $this->return_code == 'FLAGGED_REMOTE' )
			{
				return array( 'faultCode' => 0 , 'faultMessage' => "Authentication failed!<br />Our central login system has determined that you must first login at the application you originally registered at, before you can login here!" );
			}
			else
			{
				return array( 'faultCode' => 0 , 'faultMessage' => "Authentication failed!<br />Wrong username/email-address or password was provided!" );
			}
		}

		//-----------------------------------------
		// Is this a partial member?
		// Not completed their sign in?
		//-----------------------------------------

		if ( $member['created_remote'] and isset( $member['full'] ) and !$member['full'] )
		{
			return array( 'responseCode' => 1 , 'responseMessage' => "Authentication successful!<br />Authorization in 2 seconds... Please standby!" , 'extra' => $this->API->Modules->cur_module['m_url_prefix'] . "/complete_login" );
		}

		//-----------------------------------------
		// Generate a new log in key
		//-----------------------------------------

		$_ok     = 1;
		$_time   = ( $this->API->config['security']['login_key_expire'] ) ? ( time() + ( intval($this->API->config['security']['login_key_expire']) * 86400 ) ) : 0;
		$_sticky = $_time ? 0 : 1;
		$_days   = $_time ? $this->API->config['security']['login_key_expire'] : 365;

		if ( $this->API->config['security']['login_change_key'] or !$member['login_key'] or ( $this->API->config['security']['login_key_expire'] and ( time() > $member['login_key_expire'] ) ) )
		{
			$member['login_key'] = $this->API->Session->generate_autologin_key();

			$_save['login_key']         = $member['login_key'];
			$_save['login_key_expire']  = $_time;
		}

		//---------------------
		// Remember me?
		//---------------------

		if ( isset( $this->request['remember_me'] ) and $this->request['remember_me'] )
		{
			$this->API->Input->my_setcookie( "member_id" , $member['id'] , 1 );
			$this->API->Input->my_setcookie( "pass_hash" , $member['login_key'] , $_sticky, $_days );
		}
		else
		{
			$this->API->Input->my_setcookie( "member_id" , $member['id'], 0 );
			$this->API->Input->my_setcookie( "pass_hash" , $member['login_key'], 0 );
		}

		//-----------------------------------------
		// Remove any COPPA cookies previously set
		//-----------------------------------------

		$this->API->Input->my_setcookie( "coppa" , '0', 0);

		//-----------------------------------------
		// Update profile if IP addr missing
		//-----------------------------------------

		if ( $member['ip_address'] == "" or $member['ip_address'] == '127.0.0.1' )
		{
			$_save['ip_address'] = $this->API->Session->ip_address;
		}

		//-----------------------------
		// Create / Update session
		//-----------------------------

		$privacy = ( isset( $this->request['anonymous'] ) and $this->request['anonymous'] ) ? 1 : 0;

		if ( $member['g_hide_online_list'] )
		{
			$privacy = 1;
		}

		$session_id = $this->API->Session->convert_guest_to_member(
				array(
						'member_name'   => $member['display_name'],
						'member_id'     => $member['id'],
						'member_group'  => $member['mgroup'],
						'login_type'    => $privacy
					)
			);

		if ( $this->request['referer'] )
		{
			if ( stripos( $this->request['referer'], '/register' ) or stripos( $this->request['referer'], '/login' ) or stripos( $this->request['referer'], '/lostpass' ) or stripos( $this->request['referer'], '/acp' ) )
			{
				$url = SITE_URL;
			}
			else
			{
				$url = str_replace( '&amp;' , '&' , $this->request['referer'] );
				$url = preg_replace( "#" . $this->API->Session->session_name . "=(\w){32}#", "" , $url );
			}
		}
		else
		{
			$url = SITE_URL;
		}

		//--------------------------
		// Set our privacy status
		//--------------------------

		$_save['login_anonymous']      = intval( $privacy ) . '&1';
		$_save['failed_logins']        = "";
		$_save['failed_login_count']   = 0;

		$this->API->Session->save_member( $member['id'], array( 'members' => $_save ) );

		//-----------------------------------------
		// Clear out any passy change stuff
		//-----------------------------------------

		$this->API->Db->cur_query = array(
				'do'	 => "delete",
				'table'  => "members_validating",
				'where'  => "member_id=" . $this->API->Db->db->quote( $this->member['id'], "INTEGER" ) . " AND lost_pass=" . $this->API->Db->db->quote( 1, "INTEGER" ),
			);
		$this->API->Db->simple_exec_query();

		//-----------------------------------------
		// Redirect them to either the index
		// or where they came from
		//-----------------------------------------

		if ( $this->request['referer'] )
		{
			if ( stripos( $this->request['referer'], "http://" ) === 0 )
			{
				return array( 'responseCode' => 1 , 'responseMessage' => "Authentication successful!<br />Authorization in 2 seconds... Please standby!" , 'extra' => $this->request['referer'] );
			}
		}

		//----------------
		// Still here?
		//----------------

		return array( 'responseCode' => 1 , 'responseMessage' => "Authentication successful!<br />Authorization in 2 seconds... Please standby!" , 'extra' => $url );
	}


	/**
	 * Authenticate the user - creates account if possible
	 *
	 * @param   string     Username
	 * @param   string     Email Address
	 * @param   string     Password
	 * @return  boolean    Authenticate successful
	 */
	private function login_authenticate ( $username, $email_address, $password )
	{
		$redirect = "";
		foreach( $this->login_modules as $k => $obj_reference )
		{
			$obj_reference->authenticate( $username, $email_address, $password );
			$this->return_code     = ( $obj_reference->return_code == 'SUCCESS' ? 'SUCCESS' : $obj_reference->return_code );
			$this->member          = $obj_reference->member;
			$this->account_unlock  = ( $obj_reference->account_unlock ) ? $obj_reference->account_unlock : $this->account_unlock;

			# Locked
			if ( $this->return_code == 'ACCOUNT_LOCKED' )
			{
				return FALSE;
			}

			if ( $this->return_code == 'SUCCESS' )
			{
				break;
			}
			else
			{
				//-----------------------------------------
				// Want to redirect somewhere to login?
				//-----------------------------------------

				if ( ! $redirect and $this->login_methods[ $k ]['login_login_url'] )
				{
					$redirect = $this->login_methods[ $k ]['login_login_url'];
				}
			}
  		}

		//-----------------------------------------
		// If we found a login url, go to it now
		// but only if we aren't already logged in
		//-----------------------------------------

  		if ( $this->return_code != 'SUCCESS' and $redirect )
  		{
  			$this->API->http_redirect( $redirect , null );
  		}

  		return ( $this->return_code == 'SUCCESS' ) ? TRUE : FALSE;
	}


	/**
	 * Checks if password authenticates
	 *
	 * @param    string     Username
	 * @param    string     Email Address
	 * @param    string     Password
	 * @return   boolean    Password check successful
	 */
  	private function login_password_check ( $username, $email_address, $password )
  	{
		foreach ( $this->login_modules as $k => $obj_reference )
		{
			$obj_reference->authenticate( $username, $email_address, $password );
			$this->return_code = ( $obj_reference->return_code == 'SUCCESS' ? 'SUCCESS' : 'FAIL' );
			$this->member = $obj_reference->member;

			if ( $this->return_code == 'SUCCESS' )
			{
				break;
			}
  		}

  		return ( $this->return_code == 'SUCCESS' ) ? TRUE : FALSE;
  	}


	/**
	 * Just log the user in.
	 * DOES NOT CHECK FOR A USERNAME OR PASSWORD!!!
	 * << USE WITH CAUTION >>
	 *
	 * @param    integer    Member ID to log in
	 * @param    boolean    Set cookies
	 * @return   mixed      FALSE on error or array [0=Words to show, 1=URL to send to] on success
	 */
	private function login_without_checking_credentials ( $member_id, $do_set_cookies = TRUE )
	{
		//--------------------
		// Load member info
		//--------------------

		$member = $this->load_member( $member_id, "all" );
		if ( ! $member['id'] )
		{
			return FALSE;
		}

		//---------------------------------
		// Is this a partial member?
		// Not completed their sign in?
		//---------------------------------

		if ( $member['created_remote'] and ( isset( $member['full'] ) and !$member['full'] ) )
		{
			return array( 'responseCode' => 1 , 'responseMessage' => "Authentication successful!<br />Authorization in 2 seconds... Please standby!" , 'extra' => $this->API->Modules->cur_module['m_url_prefix'] . "/complete_login" );
		}

		//---------------
		// A login key
		//---------------

		$_time = ( $this->API->config['security']['login_key_expire'] ) ? ( time() + ( intval( $this->API->config['security']['login_key_expire'] ) * 86400 ) ) : 0;
		$_sticky = $_time ? 0 : 1;
		$_days = $_time ? intval( $this->API->config['security']['login_key_expire'] ) : 365;

		if ( $this->API->config['security']['login_change_key'] or !$member['login_key'] or ( $this->API->config['security']['login_key_expire'] and ( time() > $member['login_key_expire'] ) ) )
		{
			$member['login_key'] = $this->API->Session->generate_autologin_key();
			$_save['login_key']         = $member['login_key'];
			$_save['login_key_expire']  = $_time;
		}

		//-------------------------
		// Cookies... Yummeee!!!
		//-------------------------

		if ( $do_set_cookies )
		{
			$this->API->Input->my_setcookie( "member_id" , $member['id'] , 1 );
			$this->API->Input->my_setcookie( "pass_hash" , $member['login_key'] , $_sticky , $_days );
		}
		else
		{
			$this->API->Input->my_setcookie( "member_id" , $member['id'] , 0 );
			$this->API->Input->my_setcookie( "pass_hash" , $member['login_key'] , 0 );
		}

		//--------------------------------------------
		// Remove any COPPA cookies previously set
		//--------------------------------------------

		$this->API->Input->my_setcookie("coppa", '0', 0);

		//--------------------------------------
		// Update profile if IP addr missing
		//--------------------------------------

		if ( $member['ip_address'] == "" OR $member['ip_address'] == '127.0.0.1' )
		{
			$_save['ip_address'] = $this->API->Session->ip_address;
		}

		//---------------------------
		// Create / Update session
		//---------------------------

		$privacy = 0;

		if( $member['g_hide_online_list'] )
		{
			$privacy = 1;
		}

		$session_id = $this->API->Session->convert_guest_to_member(
				array(
						'member_name'   => $member['display_name'],
						'member_id'     => $member['id'],
						'member_group'  => $member['mgroup'],
						'login_type'    => $privacy
					)
			);

		if ( $this->request['referer'] )
		{
			if ( stripos( $this->request['referer'], '/register' ) or stripos( $this->request['referer'], '/login' ) or stripos( $this->request['referer'], '/lostpass' ) or stripos( $this->request['referer'], '/acp' ) )
			{
				$url = SITE_URL;
			}
			else
			{
				$url = str_replace( '&amp;' , '&' , $this->request['referer'] );
				$url = preg_replace( "#" . $this->API->Session->session_name . "=(\w){32}#", "" , $url );
			}
		}
		else
		{
			$url = SITE_URL;
		}

		//--------------------------
		// Set our privacy status
		//--------------------------

		$_save['login_anonymous']      = intval( $privacy ) . '&1';
		$_save['failed_logins']        = "";
		$_save['failed_login_count']   = 0;

		$this->API->Session->save_member( $member['id'], array( 'members' => $_save ) );

		//-----------------------------------------
		// Clear out any passy change stuff
		//-----------------------------------------

		$this->API->Db->cur_query = array(
				'do'	 => "delete",
				'table'  => "members_validating",
				'where'  => "member_id=" . $this->API->Db->db->quote( $this->member['id'], "INTEGER" ) . " AND lost_pass=" . $this->API->Db->db->quote( 1, "INTEGER" ),
			);
		$this->API->Db->simple_exec_query();

		//-----------------------------------------
		// Redirect them to either the index
		// or where they came from
		//-----------------------------------------

		if ( $this->request['referer'] )
		{
			if ( stripos( $this->request['referer'], "http://" ) === 0 )
			{
				return array( 'responseCode' => 1 , 'responseMessage' => "Authentication successful!<br />Authorization in 2 seconds... Please standby!" , 'extra' => $this->request['referer'] );
			}
		}

		//----------------
		// Still here?
		//----------------

		return array( 'responseCode' => 1 , 'responseMessage' => "Authentication successful!<br />Authorization in 2 seconds... Please standby!" , 'extra' => $url );
	}


	/**
	 * Log a user out
	 *
	 * @param   integer    Flag to check md5 key
	 * @return  mixed      Error message or array [0=immediate|redirect, 1=words to show, 2=URL to send to]
	 */
	private function logout( $check_key = TRUE )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		if ( $check_key and isset( $this->request['k'] ) )
		{
			# Check for funny business
			if ( $this->request['k'] != $this->API->Session->form_hash )
			{
				// @todo $this->registry->getClass('output')->showError( 'bad_logout_key', 2012 );
			}
		}

		//-----------------------------------------
		// Set some cookies
		//-----------------------------------------

		$this->API->Input->my_setcookie( "member_id" , "0"  );
		$this->API->Input->my_setcookie( "pass_hash" , "0"  );
		$this->API->Input->my_setcookie( "anonlogin" , "-1" );

		if( is_array( $_COOKIE ) )
 		{
 			foreach( $_COOKIE as $cookie => $value)
 			{
 				$cookie = str_replace( $this->API->config['cookies']['cookie_id'] , "" , $cookie );
 				$this->API->Input->my_setcookie( $cookie, '-', -1 );
 			}
 		}

		//------------------------
		// Logout callbacks...
		//------------------------

		$this->logout_callback();

		//------------
		// Do it..
		//------------

		$this->API->Session->convert_member_to_guest();

		list( $privacy, $loggedin ) = explode( '&', $this->member['login_anonymous'] );

		$this->API->Session->save_member(
				$this->member['id'], array(
						'members' => array(
								'login_anonymous' => $privacy . "&0",
								'last_activity'   => UNIX_TIME_NOW
							)
					)
			);


		//------------
		// Return..
		//------------

		$url = "";

		if ( $this->request['referer'] )
		{
			if ( stripos( $this->request['referer'], "http://" ) === 0 )
			{
				$this->API->http_redirect( $this->request['referer'] );
			}
		}

		$this->API->http_redirect( SITE_URL );
	}


	/**
	 * Logout callback - called when a user logs out
	 *
	 * @return    mixed    Possible redirection based on login method config, else array of messages
	 */
  	private function logout_callback ()
  	{
  		$returns   = array();
  		$redirect  = "";

		foreach ( $this->login_modules as $k => $obj_reference )
		{
			if ( method_exists( $obj_reference, 'logout_callback' ) )
			{
				$returns[] = $obj_reference->logout_callback();
			}

			//-----------------------------------------
			// Grab first logout callback url found
			//-----------------------------------------

			if ( ! $redirect and $this->login_methods[ $k ]['login_logout_url'] )
			{
				$redirect = $this->login_methods[ $k ]['login_logout_url'];
			}
  		}

		//-----------------------------------------
		// If we found a logout url, go to it now
		//-----------------------------------------

  		if ( $redirect )
  		{
  			$this->API->http_redirect( $redirect, null );
  		}

  		return $returns;
  	}


  	private function register__do_prepare ()
  	{
  		if ( $this->member['id'] )
  		{
  			//------------------------
			// Deleting cookies
			//------------------------

  			$this->API->Input->my_setcookie( "member_id" , "0"  );
  			$this->API->Input->my_setcookie( "pass_hash" , "0"  );
  			$this->API->Input->my_setcookie( "anonlogin" , "-1" );

  			if ( is_array( $_COOKIE ) )
  			{
  				foreach ( $_COOKIE as $cookie => $value )
  				{
  					$cookie = str_replace( $this->API->config['cookies']['cookie_id'], "", $cookie );
  					$this->API->Input->my_setcookie( $cookie , "-" , -1 );
  				}
  			}

  			//------------------------
			// Logout callbacks...
			//------------------------

			$this->logout_callback();

			//------------
			// Do it..
			//------------

			$this->API->Session->convert_member_to_guest();

			list( $privacy, $loggedin ) = explode( '&', $this->member['login_anonymous'] );

			$this->API->Session->save_member(
					$this->member['id'], array(
							'members' => array(
									'login_anonymous' => $privacy . "&0",
									'last_activity'   => UNIX_TIME_NOW
								)
						)
				);
	  	}

	  	//-------------------------------------------------
		// LOGIN-METHODS : Any other registration URL?
		//-------------------------------------------------

		$cache = $this->API->Cache->cache__do_get( "login_methods" );

    	if ( is_array( $cache ) and count( $cache ) )
		{
			foreach ( $cache as $login_method )
			{
				if ( $login_method['login_register_url'] )
				{
					$this->API->http_redirect( $login_method['login_register_url'] );
					exit();
				}
			}
		}

		//-----------
		// Referer
		//-----------

		if ( isset( $this->API->Input->input['referer'] ) )
		{
			$return['referer'] = $this->API->Input->input['referer'];
		}


		return $return;
  	}


  	private function register__do_process ()
  	{
  		//-------------
  		// Prelim
  		//-------------

  		$faults = array();
  		$_save  = array();
  		$_email    = strtolower( trim( $this->request['email'] ) );
  		$_password = trim( $this->request['password'] );

  		//----------------------------
  		// Registrations disabled?
  		//----------------------------

  		if ( $this->API->config['security']['no_reg'] )
  		{
  			$faults[] = array( 'faultCode' => 0 , 'faultMessage' => "New registrations has been disabled by site administrator!" );
  		}

  		//------------------------------------------
  		// LOGIN-METHODS : Do we need username?
  		//------------------------------------------

  		$_uses_username = FALSE;

  		$cache = $this->API->Cache->cache__do_get( "login_methods" );

    	if ( is_array( $cache ) and count( $cache ) )
		{
			foreach ( $cache as $login_method )
			{
				if ( $login_method['login_user_id'] == 'username' )
				{
					$_uses_username = TRUE;
				}
			}
		}

		if ( ! $_uses_username )
		{
			$this->request['username'] = $this->request['display_name'];
		}

		//---------------
		// Check names
		//---------------

		$_username_valid = $this->API->Session->names__do_clean_and_check( $this->request['username'] , array() , "name" );
		$_display_name_valid = $this->API->Session->names__do_clean_and_check( $this->request['display_name'] ? $this->request['display_name'] : $this->request['username'] , array() , "display_name" );

		if ( is_array( $_username_valid['errors'] ) and count( $_username_valid['errors'] ) )
		{
			foreach ( $_username_valid['errors'] as $key => $error )
			{
				$faults[] = array( 'faultCode' => 701 , 'faultMessage' => $error );
			}
		}

		if ( is_array( $_display_name_valid['errors'] ) and count( $_display_name_valid['errors'] ) )
		{
			foreach ( $_display_name_valid['errors'] as $key => $error )
			{
				if ( ! $this->API->config['namesettings']['auth_allow_dnames'] and ! ( is_array( $_username_valid['errors'] ) and count( $_username_valid['errors'] ) ) )
				{
					$faults[] = array( 'faultCode' => 701 , 'faultMessage' => $error );
				}
				else
				{
					$faults[] = array( 'faultCode' => 702 , 'faultMessage' => $error );
				}
			}
		}

		//-----------
  		// E-mail
  		//-----------

  		if ( $_email != $this->request['email_repeat'] )
  		{
  			$faults[] = array( 'faultCode' => 704 , 'faultMessage' => "E-mail addresses do not match! Make sure e-mail addresses you entered do match..." );
  		}

  		if ( ! $_email or strlen( $_email ) < 6 or ! $this->API->Input->check_email_address( $_email ) )
  		{
  			$faults[] = array( 'faultCode' => 703 , 'faultMessage' => "Invalid e-mail address provided!" );
  		}

  		//-----------
  		// Password
  		//-----------

  		if ( empty( $_password ) )
  		{
  			$faults[] = array( 'faultCode' => 705 , 'faultMessage' => "Password is a required field!" );
  		}
  		elseif ( strlen( $_password ) < 3 )
  		{
  			$faults[] = array( 'faultCode' => 705 , 'faultMessage' => "Password is too short! Enter something between 3-32 characters..." );
  		}

  		if ( $_password != $this->request['password_repeat'] )
  		{
  			$faults[] = array( 'faultCode' => 706 , 'faultMessage' => "Passwords do not match! Make sure passwords you entered do match..." );
  		}

		//------------------
		// RETURN part-1
		//------------------
/*
		if ( count( $faults ) )
		{
			return $faults;
		}
*/


		//---------------------
		// EMAIL : Is taken?
		//---------------------

		if ( $this->API->Session->email__do_check_if_exists( $_email ) )
		{
			$faults[] = array( 'faultCode' => 703 , 'faultMessage' => "The email address is already in use by another member!" );
		}
		else
		{
			if ( $this->email_exists_check( $_email ) !== FALSE )
			{
				$faults[] = array( 'faultCode' => 703 , 'faultMessage' => "The email address is already in use by another member!" );
			}
		}

		//----------------------
		// EMAIL : Is Banned?
		//----------------------

		if ( $this->API->Session->is_banned( 'email' , $_email ) === TRUE )
		{
			$faults[] = array( 'faultCode' => 703 , 'faultMessage' => "The email address you provided is not accepted by this web site! Give a different address..." );
		}

		//-----------
		// CAPTCHA
		//-----------

		if ( $this->API->config['security']['enable_captcha'] )
		{
			$_recaptcha_obj = $this->API->classes__do_get("ReCaptcha");
			$_recaptcha_obj->setPubKey( $this->API->config['security']['recaptcha_public_key'] );
			$_recaptcha_obj->setPrivKey( $this->API->config['security']['recaptcha_private_key'] );

			if ( ! isset( $this->request["recaptcha_response_field"] ) or empty( $this->request["recaptcha_response_field"] ) )
			{
				$faults[] = array( 'faultCode' => 0 , 'faultMessage' => "CAPTCHA is a required field!" );
			}
			else
			{
				$_recaptcha_result = $_recaptcha_obj->getService()->verify(
						$this->request["recaptcha_challenge_field"],
						$this->request["recaptcha_response_field"]
					);

				if ( ! $_recaptcha_result->isValid() )
				{
					$faults[] = array( 'faultCode' => 0 , 'faultMessage' => "CAPTCHA wasn't entered correctly!" );
				}
			}
		}

		//-------------------------
		// TOS : Agree checkbox
		//-------------------------

		if ( ! isset( $this->request['terms_agree'] ) or $this->request['terms_agree'] != '1' )
		{
			$faults[] = array( 'faultCode' => 707 , 'faultMessage' => "You have to agree to our Terms of Service in order to be able to register!" );
		}

		//------------------
		// RETURN part-2
		//------------------

		if ( count( $faults ) )
		{
			return $faults;
		}



		//----------------
		// Member-group
		//----------------

		$_mgroup = $this->API->config['security']['member_group'];
		if ( $this->API->config['security']['reg_auth_type'] != 'none' )
		{
			$_mgroup = $this->API->config['security']['auth_group'];
		}

		//-----------------------
		// Member Data to SAVE
		//-----------------------

		$member = array(
				'name'                => $this->request['username'],
				'password'            => $_password,
				'display_name'        => $this->API->config['namesettings']['auth_allow_dnames'] ? $this->request['display_name'] : $this->request['username'],
				'email'               => $_email,
				'mgroup'              => $_mgroup,
				'joined'              => UNIX_TIME_NOW,
				'ip_address'          => $this->API->Session->ip_address,
				// 'coppa_user'          => $coppa,
				'allow_admin_mails'   => 1,
				'hide_email'          => 1,
			);

  		//----------------------
  		// Create the account
  		//----------------------

		$member = $this->API->Session->create_member( array( 'members' => $member ) );

		$this->create_account(
				array(
						'email'			=> $member['email'],
						'joined'		=> $member['joined'],
						'password'		=> $_password,
						'ip_address'	=> $member['ip_address'],
						'username'		=> $member['display_name'],
					)
			);

		//-----------------
		// Validation
		//-----------------

		$validate_key = md5( $this->API->Session->create_password() . UNIX_TIME_NOW );

		if (
			( $this->API->config['security']['reg_auth_type'] == 'user' )
			or
			( $this->API->config['security']['reg_auth_type'] == 'admin' )
			or
			( $this->API->config['security']['reg_auth_type'] == 'admin_user' )
		)
		{
			//-------------------------------------------------------------------------------------------
			// We want to validate all reg's via email, after email verificiation has taken place,
			// we restore their previous group and remove the validate_key
			//-------------------------------------------------------------------------------------------

			$this->API->Db->cur_query = array(
					'do'     => "insert",
					'table'  => "members_validating",
					'set'    => array(
							'vid'             => $validate_key,
							'member_id'       => $member['id'],
						    'real_group'      => $this->API->config['security']['member_group'],
						    'temp_group'      => $this->API->config['security']['auth_group'],
						    'entry_date'      => UNIX_TIME_NOW,
							// 'coppa_user'      => $coppa,  @todo
						    'new_reg'         => 1,
							'ip_address'      => $member['ip_address']
						)
				);
			$this->API->Db->simple_exec_query();

			if ( $this->API->config['security']['reg_auth_type'] == 'user' or $this->API->config['security']['reg_auth_type'] == 'admin_user' )
			{
				# EMAIL VALIDATION or EMAIL+ADMIN REVIEW
				$_variables_to_assign_to_email_template = array(
						'member'                    =>  $member,
						'validation_link'           =>  SITE_URL . "/users/validate/mid-" . urlencode( $member['id'] ) . "/aid-" . urlencode( $validate_key ),
					);
				$this->API->Display->smarty->assign( "CONTENT" , $_variables_to_assign_to_email_template );

				$_email_body = $this->API->Display->smarty->fetch( "bits:register__validation_instructions-email" , $this->API->Display->cache_id['request_based']['encoded'] );

				require_once( "Zend/Mail.php" );
				$mail = new Zend_Mail();
				$mail->setBodyText( $_email_body )
				     ->setFrom( $this->API->config['email']['email_out'] )
				     ->addTo( $member['email'] )
				     ->setSubject( $this->API->config['general']['site_name'] . ": Account Validation" )
				     ->send();

				return array( 'responseCode' => 1 , 'responseMessage' => "You have successfully completed your registration!<br /><br />Please be advised that, the administrator has requested e-mail address validation for all registering members! In a few moments (usually instantly) you will be receiving an email containing necessary instructions in order to complete your registration!<br /><br />Redirecting to login page in 15 seconds... <a href=\"/users/login/\">Click here if you don't wish to wait!</a>" , 'extra' => SITE_URL . "/users/login/" );
			}
			elseif( $this->API->config['security']['reg_auth_type'] == 'admin' )
			{
				# ADMIN REVIEW
				return array( 'responseCode' => 2 , 'responseMessage' => "You have successfully completed your registration!<br /><br />Please be advised that, the administrator has requested to review and approve all registering members personally! Until that happens, you are welcome to browse our website!<br /><br />Redirecting to login page in 15 seconds... <a href=\"/users/login/\">Click here if you don't wish to wait!</a>" , 'extra' => SITE_URL . "/users/login/" );
			}
		}
		else
		{
			/* STATS CACHE @todo
			// We don't want to preview, or get them to validate via email.
			if ( $member['display_name'] and $member['id'] )
			{
				$stat_cache['last_mem_name']      = $member['display_name'];
				$stat_cache['last_mem_name_seo']  = $this->API->Input->make_seo_title( $member['display_name'] );
				$stat_cache['last_mem_id']        = $member['id'];
			}

			$stat_cache['mem_count'] += 1;

			$this->API->Cache->setCache( 'stats', $stat_cache, array( 'array' => 1 ) );
			*/

			# NO VALIDATION

			$_variables_to_assign_to_email_template = array(
					'member' =>  $member
				);
			$this->API->Display->smarty->assign( "CONTENT" , $_variables_to_assign_to_email_template );

			$_email_body = $this->API->Display->smarty->fetch( "bits:register__welcome_message-email" , $this->API->Display->cache_id['request_based']['encoded'] );

			require_once( "Zend/Mail.php" );
			$mail = new Zend_Mail();
			$mail->setBodyText( $_email_body )
			     ->setFrom( $this->API->config['email']['email_out'] )
			     ->addTo( $member['email'] )
			     ->setSubject( $this->API->config['general']['site_name'] . ": Welcome Aboard!" )
			     ->send();

			//---------------------
			// Fix up session
			//---------------------

			$this->API->Input->my_setcookie( 'pass_hash' , $member['login_key'], 1 );
			$this->API->Input->my_setcookie( 'member_id' , $member['id']       , 1 );

			$privacy = $this->request['privacy'] ? 1 : 0;

			if ( $member['g_hide_online_list'] )
			{
				$privacy = 1;
			}

			$this->API->Session->convert_guest_to_member(
					array(
							'member_name'   => $member['display_name'],
							'member_id'     => $member['id'],
							'member_group'  => $member['mgroup'],
							'login_type'    => $privacy
						)
				);

			return array( 'responseCode' => 3 , 'responseMessage' => "You have successfully completed your registration!<br /><br />Redirecting for autologin in 2 seconds... <a href=\"/users/login/?action=do_autologin&from_reg=1\">Click here to do that now.</a>" , 'extra' => "/users/login/?action=do_autologin&from_reg=1" );
		}
  	}


  	/**
  	 * User E-mail activation of an account
  	 */
  	private function validate ()
  	{
  		//----------
		// Prelim
		//----------

  		$_mid   = intval( urldecode( $this->running_subroutine['request']['mid'] ) );
		$_aid   = substr( $this->API->Input->clean__md5_hash( urldecode( $this->running_subroutine['request']['aid'] ) ), 0, 32 );
		$_type  = ( isset( $this->request['type'] ) and $this->request['type'] )
			?
			$this->request['type']
			:
			'reg';

		//-----------------------------------------------------
		// Attempt to get the profile of the requesting user
		//-----------------------------------------------------

		$member = $this->API->Session->load_member( $_mid, 'members' );

		if ( ! $member['id'] )
		{
			return array( "faultCode" => 0 , "faultMessage" => "No such member found in our records!" );
		}

		//-------------------------
		// Get validating info..
		//-------------------------

		if ( $_type == 'lostpass' )
		{
			$this->API->Db->cur_query = array(
					'do'     => "select_row",
					'table'  => "members_validating",
					'where'  => array(
							array( "member_id=" . $this->API->Db->db->quote( $_mid , "INTEGER" ) ),
							array( "lost_pass=" . $this->API->Db->db->quote( 1 , "INTEGER" ) ),
						)
				);
		}
		else if ( $_type == 'newemail' )
		{
			$this->API->Db->cur_query = array(
					'do'     => "select_row",
					'table'  => "members_validating",
					'where'  => array(
							array( "member_id=" . $this->API->Db->db->quote( $_mid , "INTEGER" ) ),
							array( "email_chg=" . $this->API->Db->db->quote( 1 , "INTEGER" ) ),
						)
				);
		}
		else
		{
			$this->API->Db->cur_query = array(
					'do'     => "select_row",
					'table'  => "members_validating",
					'where'  => array(
							array( "member_id=" . $this->API->Db->db->quote( $_mid , "INTEGER" ) ),
						)
				);
		}

		$validate = $this->API->Db->simple_exec_query();

		//--------------
		// Checks...
		//--------------

		if ( $validate['new_reg'] == 1 and $this->API->config['security']['reg_auth_type'] == "admin" )
		{
			return array( "faultCode" => 0 , "faultMessage" => "Activation Failed!<br /><br />Please be advised that, the administrator has requested to review and approve all registering members personally! Until that happens, you are welcome to browse our website!" );
		}

		if ( $validate['vid'] != $_aid )
		{
			return array( "faultCode" => 0 , "faultMessage" => "Invalid validation key!" );
		}

		//----------------------
		// REGISTER VALIDATE
		//----------------------

		if ( $validate['new_reg'] == 1 )
		{
			if ( ! $validate['real_group'] )
			{
				$validate['real_group'] = $this->API->config['security']['member_group'];
			}

			//-------------------------
			// SELF-VERIFICATION...
			//-------------------------

			if ( $this->API->config['security']['reg_auth_type'] != 'admin_user' )
			{
				$this->API->Session->save_member( $member['id'], array( 'members' => array( 'mgroup' => $validate['real_group'] ) ) );

				# Reset newest member

				$_stats_cache = $this->API->Cache->cache__do_get( "stats" );
				if ( $member['display_name'] and $member['id'] )
				{
					$_stats_cache['last_mem_name']      = $member['display_name'];
					$_stats_cache['last_mem_name_seo']  = $this->API->Input->make_seo_title( $member['display_name'] );
					$_stats_cache['last_mem_id']        = $member['id'];
				}
				$stat_cache['mem_count'] += 1;
				$this->API->Cache->cache__do_update( array( 'name' => "stats", 'value' => $_stats_cache, 'array' => 1 ) );

				# Remove "dead" validation

				$this->API->Db->cur_query = array(
						'do'	 => "delete",
						'table'  => "members_validating",
						'where'  => "vid=" . $this->API->Db->db->quote( $validate['vid'] ),
					);
				$this->API->Db->simple_exec_query();

				return array( "responseCode" => 1 , "responseMessage" => "Account Activation Successful!<br /><br />Please log-in to continue..." );
			}

			//---------------------------
			// ADMIN-VERIFICATION...
			//---------------------------

			else
			{
				//---------------------
				// Update DB row...
				//---------------------

				$this->API->Db->cur_query = array(
						'do'	 => "update",
						'tables' => "members_validating",
						'set'    => array( 'user_verified' => 1 ),
						'where'  => "vid=" . $this->API->Db->db->quote( $validate['vid'] ),

						'force_data_type' => array( 'member_name' => "string" )
					);
				$this->API->Db->simple_exec_query();

				return array( "responseCode" => 1 , "responseMessage" => "Activation Successful!<br /><br />Please be advised that, the administrator has requested to review and approve all registering members personally! Until that happens, you are welcome to browse our website!" );
			}
		}

		//-----------------------
		// LOST PASS VALIDATE
		//-----------------------

		elseif ( $validate['lost_pass'] == 1 )
		{
			//------------
			// Prelim
			//------------

			$save_array = array();

			//-----------------------------------
			// Generate a new random password
			//-----------------------------------

			$_new_pass = $this->API->Input->create_password();

			//-------------------------
			// Generate a new salt
			//-------------------------

			$_salt = $this->API->Session->generate_password_salt(5);
			$_salt = str_replace( '\\', "\\\\", $_salt );

			//--------------------
			// New log in key
			//--------------------

			$_key = $this->API->Session->generate_autologin_key();

			//--------------
			// Update...
			//--------------

			$save_array['pass_salt']        = $_salt;
			$save_array['pass_hash']        = md5( md5( $_salt ) . md5( $_new_pass ) );
			$save_array['login_key']        = $_key;
			$save_array['login_key_expire'] = $this->API->config['security']['login_key_expire'] * 60 * 60 * 24;

			$this->change_pass( $member['email'], md5( $_new_pass ) );

	    	if ( $this->return_code != 'METHOD_NOT_DEFINED' and $this->return_code != 'SUCCESS' )
	    	{
				return array( "faultCode" => 0 , "faultMessage" => "There was an error changing your password in one of our external systems! Please notify an administrator." );
	    	}

	    	$this->API->Session->save_member( $member['id'], array( 'members' => $save_array ) );

			//--------------------------
			// Send out the email...
			//--------------------------

	    	# EMAIL VALIDATION or EMAIL+ADMIN REVIEW
			$_variables_to_assign_to_email_template = array(
					'member'                      =>  $member,
					'usercp_passwd_change_link'   =>  SITE_URL . "/users/password",
					'new_passwd'                  =>  $_new_pass,
				);
			$this->API->Display->smarty->assign( "CONTENT" , $_variables_to_assign_to_email_template );

			$_email_body = $this->API->Display->smarty->fetch( "bits:usercp__generate_and_email_random_passwd-email" , $this->API->Display->cache_id['request_based']['encoded'] );

			require_once( "Zend/Mail.php" );
			$mail = new Zend_Mail();
			$mail->setBodyText( $_email_body )
			     ->setFrom( $this->API->config['email']['email_out'] )
			     ->addTo( $member['email'] )
			     ->setSubject( $this->API->config['general']['site_name'] . ": Your New Password" )
			     ->send();

			# Remove "dead" validation

			$this->API->Db->cur_query = array(
					'do'	 => "delete",
					'table'  => "members_validating",
					'where'  => array(
							array("vid=" . $this->API->Db->db->quote( $validate['vid'] ) ),
							array("lost_pass=" . $this->API->Db->db->quote( 1 , "INTEGER" ) ),
						),
				);
			$this->API->Db->simple_exec_query();

			return array( "responseCode" => 1 , "responseMessage" => "Password Generation Successful!<br /><br />Your new random password has been generated and emailed to you..." );
		}

		//---------------------
		// EMAIL ADDY CHANGE
		//---------------------

		elseif ( $validate['email_chg'] == 1 )
		{
			if ( ! $validate['real_group'] )
			{
				$validate['real_group'] = $this->API->config['security']['member_group'];
			}

			$this->API->Session->save_member( $member['id'], array( 'members' => array( 'mgroup' => intval( $validate['real_group'] ) ) ) );

			$this->API->Input->my_setcookie( "member_id", $member['id']        , 1 );
			$this->API->Input->my_setcookie( "pass_hash", $member['login_key'] , 1 );

			//------------------------------
			// Remove "dead" validation
			//------------------------------

			$this->API->Db->cur_query = array(
					'do'	 => "delete",
					'table'  => "members_validating",
					'where'  => array(
							array("vid=" . $this->API->Db->db->quote( $validate['vid'] ) ),
							array("email_chg=" . $this->API->Db->db->quote( 1 , "INTEGER" ) ),
						),
				);
			$this->API->Db->simple_exec_query();

			// @todo REDIRECT TO USERCP - EMAIL CHANGE page
			exit;
		}
  	}


	/**
	 * Check if the email is already in use
	 *
	 * @param    string     Email address
	 * @return   boolean    Authenticate successful
	 */
	private function email_exists_check ( $email )
	{
		$this->return_code = "METHOD_NOT_DEFINED";

		foreach ( $this->login_modules as $k => $obj_reference )
		{
			if ( method_exists( $obj_reference, 'email_exists_check' ) )
			{
				$obj_reference->email_exists_check( $email );
				$this->return_code = $obj_reference->return_code;

				if ( $this->return_code and ! in_array( $this->return_code, array( "EMAIL_NOT_IN_USE", "METHOD_NOT_DEFINED", "WRONG_AUTH", "WRONG_OPENID" ) ) )
				{
					break;
				}
			}
  		}

		if ( $this->return_code and ! in_array( $this->return_code, array( "EMAIL_NOT_IN_USE", "METHOD_NOT_DEFINED", "WRONG_AUTH", "WRONG_OPENID" ) ) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}



	/**
	 * Change a user's email address
	 *
	 * @param    string     Old Email address
	 * @param    string     New Email address
	 * @return   boolean    Email changed successfully
	 */
	private function change_email ( $old_email, $new_email )
	{
		$this->return_code = "METHOD_NOT_DEFINED";

		foreach ( $this->login_modules as $k => $obj_reference )
		{
			if ( method_exists( $obj_reference, "change_email" ) )
			{
				$obj_reference->change_email( $old_email, $new_email );
				$this->return_code = $obj_reference->return_code;
			}
  		}

  		return ( $this->return_code == 'SUCCESS' ) ? TRUE : FALSE;
	}


	/**
	 * Change a login name
	 *
	 * @param    string     Old Name
	 * @param    string     New Name
	 * @param    string     User's email address
	 * @return   boolean    Request was successful
	 */
	private function change_name ( $old_name, $new_name, $email_address )
  	{
  		$this->return_code = "METHOD_NOT_DEFINED";

		foreach ( $this->login_modules as $k => $obj_reference )
		{
			if ( method_exists( $obj_reference, "change_name" ) )
			{
				$obj_reference->change_name( $old_name, $new_name, $email_address );
				$this->return_code = $obj_reference->return_code;
			}
  		}

  		return ( $this->return_code == 'SUCCESS' ) ? TRUE : FALSE;
  	}


	/**
	 * Change a user's password
	 *
	 * @param    string     Email address
	 * @param    string     New password
	 * @return   boolean    Password changed successfully
	 */
	private function change_pass ( $email, $new_pass )
	{
		$this->return_code = "METHOD_NOT_DEFINED";

		foreach ( $this->login_modules as $k => $obj_reference )
		{
			if ( method_exists( $obj_reference, "change_pass" ) )
			{
				$obj_reference->change_pass( $email, $new_pass );
				$this->return_code = $obj_reference->return_code;
			}
  		}

  		return ( $this->return_code == 'SUCCESS' ) ? TRUE : FALSE;
	}


	/**
	 * Create a user's account throughout all enabled Login-methods
	 *
	 * @param    array      Array of member information
	 * @return   boolean    Account created successfully
	 */
	private function create_account ( $member=array() )
	{
		if( ! is_array( $member ) )
	  	{
		  	$this->return_code = "FAIL";
		  	return FALSE;
	  	}

	  	$this->return_code = "";

		foreach ( $this->login_modules as $k => $obj_reference )
		{
			if ( method_exists( $obj_reference, "create_account" ) )
			{
				$obj_reference->create_account( $member );
				$this->return_code     = $obj_reference->return_code;
				$this->return_details .= $obj_reference->return_details . '<br />';
			}
  		}
	}


	/**
	 * Determine email address or username login
	 *
	 * @return   integer    [1=Username, 2=Email, 3=Both]
	 */
  	private function email_or_username ()
  	{
  		$username  = FALSE;
  		$email     = FALSE;

		foreach( $this->login_methods as $k => $method )
		{
			if ( $method['login_user_id'] == 'username' )
			{
				$username = TRUE;
			}
			elseif ( $method['login_user_id'] == 'email' )
			{
				$email = TRUE;
			}
  		}

  		if ( $username and ! $email )
  		{
  			return 1;
  		}
  		elseif( ! $username and $email )
  		{
  			return 2;
  		}
  		elseif( $username and $email )
  		{
  			return 3;
  		}

		//-----------------------------------------
		// If we're here, none of the methods
		//	want username or email, which is bad
		//-----------------------------------------

  		else
  		{
  			return 1;
  		}
  	}


	/**
	 * Get additional login form HTML add/replace
	 *
	 * @return   mixed    Null or Array [0=Add or replace flag, 1=Array of HTML blocks to add/replace with]
	 */
  	private function additional_form_HTML()
  	{
  		$has_more_than_one  = FALSE;
  		$additional_details = array();
  		$add_or_replace     = null;

  		if ( count( $this->login_methods ) > 1 )
  		{
  			$has_more_than_one = TRUE;
  			$add_or_replace    = 'add';
  		}

		foreach ( $this->login_methods as $k => $method )
		{
			if ( !$has_more_than_one )
			{
				if ( $method['login_replace_form'] == 1 )
				{
					$add_or_replace	= "replace";
				}
				else
				{
					$add_or_replace	= "add";
				}
			}

			if ( $this->is_admin_auth )
			{
				if ( $method['login_alt_acp_html'] )
				{
					$additional_details[] = $method['login_alt_acp_html'];
				}
			}
			else
			{
				if ( $method['login_alt_login_html'] )
				{
					$additional_details[] = $method['login_alt_login_html'];
				}
			}
  		}

		if ( count( $additional_details ) )
		{
			return array( $add_or_replace, $additional_details );
		}
		else
		{
			return null;
		}
  	}


	/**
	 * Alternate login URL redirection
	 *
	 * @return    mixed    Possible redirection based on login method config, else FALSE
	 */
  	private function check_login_url_redirect ()
  	{
  		$redirect = "";

		foreach ( $this->login_modules as $k => $obj_reference )
		{
			if ( method_exists( $obj_reference, 'check_login_url_redirect' ) )
			{
				$obj_reference->check_login_url_redirect();
			}

			//-----------------------------------------
			// Grab first logout callback url found
			//-----------------------------------------

			if ( ! $redirect and $this->login_methods[ $k ]['login_login_url'] )
			{
				$redirect = $this->login_methods[ $k ]['login_login_url'];
			}
  		}

		//-----------------------------------------
		// If we found a logout url, go to it now
		//-----------------------------------------

  		if ( $redirect )
  		{
  			$this->API->http_redirect( $redirect, null );
  		}

  		return FALSE;
  	}


	/**
	 * User maintenance callback
	 *
	 * @return    mixed    Possible redirection based on login method config, else FALSE
	 */
  	private function check_maintenance_redirect ()
  	{
  		$redirect = "";

		foreach ( $this->login_modules as $k => $obj_reference )
		{
			if ( method_exists( $obj_reference, 'check_maintenance_redirect' ) )
			{
				$obj_reference->check_maintenance_redirect();
			}

			//-----------------------------------------
			// Grab first logout callback url found
			//-----------------------------------------

			if ( ! $redirect and $this->login_methods[ $k ]['login_maintain_url'] )
			{
				$redirect = $this->login_methods[ $k ]['login_maintain_url'];
			}
  		}

		//-----------------------------------------
		// If we found a logout url, go to it now
		//-----------------------------------------

  		if ( $redirect )
  		{
  			$this->API->http_redirect( $redirect, null );
  		}

  		return FALSE;
  	}
}