<?php

/**
 * Invision Power Services
 * IP.Board v3.0.0
 * Login handler abstraction : IP.Converge Method
 * Last Updated: $Date: 2009-04-28 01:33:58 -0400 (Tue, 28 Apr 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		Invision Power Board
 * @link		http://www.invisionpower.com
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 4560 $
 *
 */

if ( ! defined( "INIT_DONE" ) )
{
	print "Improper access! Exiting now...";
	exit();
}

class Login_Method extends Login_Core
{
	/**
	 * Login method configuration
	 *
	 * @var array
	 */
	protected $method_config = array();

	/**
	 * API Server object
	 *
	 * @var object
	 */
	private $api_server;


	/**
	 * Constructor
	 *
	 * @param     object     ipsRegistry reference
	 * @param     array      Configuration info for this method
	 * @param     array      Custom configuration info for this method
	 * @return    void
	 */
	public function __construct ( & $API, $method, $conf = array() )
	{
		# Prelim
		$this->method_config = $method;
		$this->API = $API;
		parent::__construct();
	}

	/**
	 * Authenticate the request
	 *
	 * @param     string     Username
	 * @param     string     Email Address
	 * @param     string     Password
	 * @return    boolean    Authentication successful
	 */
	public function authenticate ( $username, $email_address, $password )
	{
		//---------
		// INIT
		//---------

		$md5_once_pass = md5( $password );
		$external_data = '';

		//-----------------------------------------
		// Check admin authentication request
		//-----------------------------------------

		if ( $this->is_admin_auth )
		{
			$this->admin_auth_local( $username, $email_address, $password );

  			if ( $this->return_code == 'SUCCESS' )
  			{
  				return TRUE;
  			}
		}

		//-----------------------------------------
		// Get product ID and code from API
		//-----------------------------------------

		$this->API->Db->cur_query = array(
					'do'	    => "select_row",
					'table'     => "converge_local",
					'where'     => "converge_active=" . $this->API->Db->db->quote( 1, "INTEGER" ),
				);
		$converge = $this->API->Db->simple_exec_query();

		if ( ! $converge['converge_api_code'] )
		{
			$this->return_code = 'WRONG_AUTH';
			return FALSE;
		}

		//-----------------------------------------
		// If the user submitted a name, grab email
		//-----------------------------------------

		if ( $username and !$email_address )
		{
			$temp = $this->API->Session->load_member( $username, 'extendedProfile', 'username' );

			if ( $temp['email'] )
			{
				$email_address = $temp['email'];
			}
		}

		//--------------------------------
		// Auth against converge...
		//--------------------------------

		if ( ! is_object( $this->api_server ) )
		{
			require_once( PATH_SOURCES . '/kernel/api_server.php' );
			$this->api_server = new API_Server();
		}

		$request = array( 'auth_key'          => $converge['converge_api_code'],
						  'product_id'        => $converge['converge_product_id'],
						  'email_address'     => $email_address,
						  'md5_once_password' => $md5_once_pass,
						  'username'          => $username
						);

		$url = $converge['converge_url'] . '/converge_master/converge_server.php';

		//------------------
		// Send request
		//------------------

		$this->api_server->auth_user = $converge['converge_http_user'];
		$this->api_server->auth_pass = $converge['converge_http_pass'];

		$this->api_server->api_send_request( $url, 'convergeAuthenticate', $request );

		//---------------------
		// Handle errors...
		//---------------------

		if ( count( $this->api_server->errors ) )
		{
			$this->return_code = 'WRONG_AUTH';
			return FALSE;
		}
		elseif ( $this->api_server->params['response'] != 'SUCCESS' )
		{
			if ( $this->api_server->params['response'] == 'FLAGGED_REMOTE' )
			{
				$this->return_code = 'FLAGGED_REMOTE';
				return FALSE;
			}

			$this->return_code = 'WRONG_AUTH';
			return FALSE;
		}

		if ( $this->api_server->params['extra_data'] )
		{
			$external_data = unserialize( base64_decode( $this->api_server->params['extra_data'] ) );
		}

		//-------------------
		// Get member...
		//-------------------

		$this->_load_member( $email_address );

		if ( ! $this->member['id'] )
		{
			//---------------------------------------------
			// Got no member - but auth passed - create?
			//---------------------------------------------

			$tmp_display = $this->api_server->params['username'] ? $this->api_server->params['username'] : '';
			$email_address = $email_address ? $email_address : $this->api_server->params['email'];

			$this->member = $this->create_local_member( array(
					'members' => array(
							'name'            => $tmp_display,
							'display_name'    => $tmp_display,
							'password'        => $password,
							'email'           => $email_address,
							'joined'          => $this->api_server->params['joined'],
							'ip_address'      => $this->api_server->params['ipaddress'],
						)
				)
			);
		}

		//-----------
		// Return
		//-----------

		$this->return_code = $this->api_server->params['response'];
		return TRUE;
	}

	/**
	 * Load the member
	 *
	 * @param     string     Email Address
	 * @return    void
	 */
	private function _load_member ( $email_address )
	{
		$this->member = $this->API->Session->load_member( $email_address, 'groups' );

		if ( $this->member['id'] )
		{
			$this->API->Session->set_member( $this->member['id'] );
		}
	}

	/**
	 * Check if an email already exists
	 *
	 * @param     string     Email Address
	 * @return    boolean    Request was successful
	 */
	public function email_exists_check ( $email )
	{
		//-----------------------------------------
		// Get product ID and code from API
		//-----------------------------------------

		$converge = $this->API->Cache->cache__do_get( "converge" );

		if ( ! $converge['converge_api_code'] )
		{
			$this->return_code = 'WRONG_AUTH';
			return FALSE;
		}

		//--------------------------------
		// Auth against converge...
		//--------------------------------

		if ( ! is_object( $this->api_server ) )
		{
			require_once( PATH_SOURCES . "api_server.php" );
			$this->api_server = new API_Server();
		}

		$request = array(
				'auth_key'       => $converge['converge_api_code'],
				'product_id'     => $converge['converge_product_id'],
				'email_address'  => $email,
			);

		$url = $converge['converge_url'] . '/converge_master/converge_server.php';

		//------------------
		// Send request
		//------------------

		$this->api_server->auth_user = $converge['converge_http_user'];
		$this->api_server->auth_pass = $converge['converge_http_pass'];

		$this->api_server->api_send_request( $url, 'convergeCheckEmail', $request );

		//----------------------
		// Handle errors...
		//----------------------

		if ( count( $this->api_server->errors ) )
		{
			$this->return_code = 'WRONG_AUTH';
			return FALSE;
		}

		$this->return_code = $this->api_server->params['response'];
		return TRUE;
	}

	/**
	 * Change an email address
	 *
	 * @param     string     Old Email Address
	 * @param     string     New Email Address
	 * @return    boolean    Request was successful
	 */
	public function change_email ( $old_email, $new_email )
	{
		//-----------------------------------------
		// Get product ID and code from API
		//-----------------------------------------

		$converge = $this->API->Cache->cache__do_get( "converge" );

		if ( ! $converge['converge_api_code'] )
		{
			$this->return_code = 'WRONG_AUTH';
			return FALSE;
		}

		//-----------------------------
		// Auth against converge...
		//-----------------------------

		if ( ! is_object( $this->api_server ) )
		{
			require_once( PATH_SOURCES . "/kernel/api_server.php" );
			$this->api_server = new API_Server();
		}

		$request = array(
				'auth_key'      => $converge['converge_api_code'],
				'product_id'    => $converge['converge_product_id'],
				'email_address' => $new_email,
			);

		$url = $converge['converge_url'] . '/converge_master/converge_server.php';

		//-------------------
		// Send request
		//-------------------

		$this->api_server->auth_user = $converge['converge_http_user'];
		$this->api_server->auth_pass = $converge['converge_http_pass'];

		$this->api_server->api_send_request( $url, 'convergeCheckEmail', $request );

		//----------------------
		// Handle errors...
		//----------------------

		if ( count( $this->api_server->errors ) )
		{
			$this->return_code = 'WRONG_AUTH';
			return FALSE;
		}

		if ( $this->api_server->params['response'] == 'EMAIL_NOT_IN_USE' )
		{
			//-----------------
			// Change email
			//-----------------

			$request = array(
					'auth_key'          => $converge['converge_api_code'],
					'product_id'        => $converge['converge_product_id'],
					'old_email_address' => $old_email,
					'new_email_address' => $new_email,
				);

			$url = $converge['converge_url'] . '/converge_master/converge_server.php';

			//------------------
			// Send request
			//------------------

			$this->api_server->api_send_request( $url, 'convergeChangeEmail', $request );

			//----------------------
			// Handle errors...
			//----------------------

			if ( count( $this->api_server->errors ) )
			{
				$this->return_code = 'WRONG_AUTH';
				return FALSE;
			}
		}

		$this->return_code = $this->api_server->params['response'];
		return TRUE;
	}


	/**
	 * Change a password
	 *
	 * @param     string     Email Address
	 * @param     string     New Password
	 * @return    boolean    Request was successful
	 */
	public function change_pass ( $email, $new_pass )
	{
		//--------------------------------------
		// Get product ID and code from API
		//--------------------------------------

		$converge = $this->API->Cache->cache__do_get( "converge" );

		if ( ! $converge['converge_api_code'] )
		{
			$this->return_code = 'WRONG_AUTH';
			return FALSE;
		}

		//-----------------------------
		// Auth against converge...
		//-----------------------------

		if ( ! is_object( $this->api_server ) )
		{
			require_once( PATH_SOURCES . "/kernel/api_server.php" );
			$this->api_server = new API_Server();
		}

		$request = array(
				'auth_key'          => $converge['converge_api_code'],
				'product_id'        => $converge['converge_product_id'],
				'email_address'     => $email,
				'md5_once_password' => $new_pass,
			);

		$url = $converge['converge_url'] . '/converge_master/converge_server.php';

		//------------------
		// Send request
		//------------------

		$this->api_server->auth_user = $converge['converge_http_user'];
		$this->api_server->auth_pass = $converge['converge_http_pass'];

		$this->api_server->api_send_request( $url, 'convergeChangePassword', $request );

		//----------------------
		// Handle errors...
		//----------------------

		if ( count( $this->api_server->errors ) )
		{
			$this->return_code = 'WRONG_AUTH';
			return FALSE;
		}

		$this->return_code = $this->api_server->params['response'];
		return TRUE;
	}

	/**
	 * Change a login name
	 *
	 * @param     string     Old Name
	 * @param     string     New Name
	 * @param     string     User's email address
	 * @return    boolean    Request was successful
	 */
	public function change_name ( $old_name, $new_name, $email_address )
	{
		//-----------------------------------------
		// Get product ID and code from API
		//-----------------------------------------

		$converge = $this->API->Cache->cache__do_get( "converge" );

		if ( ! $converge['converge_api_code'] )
		{
			$this->return_code = 'WRONG_AUTH';
			return FALSE;
		}

		//-----------------------------
		// Auth against converge...
		//-----------------------------

		if ( ! is_object( $this->api_server ) )
		{
			require_once( PATH_SOURCES . "/kernel/api_server.php" );
			$this->api_server = new API_Server();
		}

		$request = array(
				'auth_key'       => $converge['converge_api_code'],
				'product_id'     => $converge['converge_product_id'],
				'email_address'  => $email_address,
				'old_username'   => $old_name,
				'new_username'   => $new_name,
			);

		$url = $converge['converge_url'] . '/converge_master/converge_server.php';

		//-------------------
		// Send request
		//-------------------

		$this->api_server->auth_user = $converge['converge_http_user'];
		$this->api_server->auth_pass = $converge['converge_http_pass'];

		$this->api_server->api_send_request( $url, 'convergeChangeUsername', $request );

		//----------------------
		// Handle errors...
		//----------------------

		if ( count( $this->api_server->errors ) )
		{
			$this->return_code = 'WRONG_AUTH';
			return FALSE;
		}

		$this->return_code = $this->api_server->params['response'];
		return TRUE;
	}

	/**
	 * Create an account in IP.Converge
	 *
	 * @param     array      Member information
	 * @return    boolean    Request was successful
	 */
	public function create_account ( $member = array() )
	{
		if ( ! is_array( $member ) )
		{
			$this->return_code = 'FAIL';
			return FALSE;
		}

		//-----------------------------------------
		// Get product ID and code from API
		//-----------------------------------------

		$converge = $this->API->Cache->cache__do_get( "converge" );

		if ( ! $converge['converge_api_code'] )
		{
			$this->return_code = 'WRONG_AUTH';
			return FALSE;
		}

		//-------------------------------
		// Auth against converge...
		//-------------------------------

		if ( ! is_object( $this->api_server ) )
		{
			require_once( PATH_SOURCES . '/kernel/api_server.php' );
			$this->api_server = new API_Server();
		}

		$request = array(
				'auth_key'        => $converge['converge_api_code'],
				'product_id'      => $converge['converge_product_id'],
				'email_address'   => $member['email'],
			);

		$url = $converge['converge_url'] . '/converge_master/converge_server.php';

		//------------------
		// Send request
		//------------------

		$this->api_server->auth_user = $converge['converge_http_user'];
		$this->api_server->auth_pass = $converge['converge_http_pass'];

		$this->api_server->api_send_request( $url, 'convergeCheckEmail', $request );

		//-----------------------
		// Handle errors...
		//-----------------------

		if ( count( $this->api_server->errors ) )
		{
			$this->return_code = 'WRONG_AUTH';
			return FALSE;
		}

		if ( $this->api_server->params['response'] == 'EMAIL_NOT_IN_USE' )
		{
			$request = array(
					'auth_key'          => $converge['converge_api_code'],
					'product_id'        => $converge['converge_product_id'],
					'email_address'     => $member['email'],
					'md5_once_password' => md5( $member['password'] ),
					'ip_address'        => $member['ip_address'],
					'unix_join_date'    => $member['joined'],
					'username'          => $member['username'],
				);

			$url = $converge['converge_url'] . '/converge_master/converge_server.php';

			//------------------
			// Send request
			//------------------

			$this->api_server->api_send_request( $url, 'convergeAddMember', $request );

			//----------------------
			// Handle errors...
			//----------------------

			if ( count( $this->api_server->errors ) )
			{
				$this->return_details = implode( '<br />', $this->api_server->errors );
				$this->return_code = $this->api_server->params['response'];
				return FALSE;
			}
		}

		$this->return_code = $this->api_server->params['response'];
		return TRUE;
	}
}