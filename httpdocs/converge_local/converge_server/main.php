<?php

if ( ! defined( "INIT_DONE" ) )
{
	print "Improper access! Exiting now...";
	exit();
}

/**
 * IPS.Converge Server Class
 *
 * @package	Invision Power Board ( Adapted to Audith CMS)
 * @author	Matthew Mecham @ IPS ( Adapted by Shahriyar Imanov <shehi@imanov.name> )
 * @version	3.0
 **/

class Converge_Server
{
	/**
	 * Defines the service for WSDL
	 * @access Private
	 * @var array
	 **/
	private $__dispatch_map = array();

	/**
	 * API Object Reference
	 * @access Private
	 * @var object
	 **/
	private $API;

	/**
	 * API Server Object Reference
	 * @access Private
	 * @var object
	 **/
	private $API_Server;


	/**
	 * Converge_Server :: Constructor
	 *
	 * @return void
	 **/
	public function __construct ( & $API, & $API_Server )
	{
		//-----------------------------------------
		// Bring-in API- & API-Server- Object References
		//-----------------------------------------

		$this->API        = $API;
		$this->API_Server = $API_Server;

		//-----------------------------------------
		// Load allowed methods and build dispatch
		// list
		//-----------------------------------------

		$this->__dispatch_map = require_once( PATH_ROOT_WEB . "converge_local/converge_server/allowed_methods.php" );
	}

	/**
	 * Converge_Server :: requestData()
	 *
	 * Returns extra data from this application
	 *
	 * EACH BATCH MUST BE ORDERED BY ID ASC (low to high)
	 *
	 * @param  string  $auth_key  	Authentication Key
	 * @param  int	   $product_id  Product ID
	 * @param  int	   $limit_a		SQL limit a
	 * @param  int	   $limit_b		SQL limit b
	 * @return xml
	 **/
	private function requestData ( $auth_key, $product_id, $email_address, $getdata_key )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$auth_key	   = $this->API->Input->clean__md5_hash( $auth_key );
		$product_id	   = intval( $product_id );
		$email_address = $this->API->Input->clean__makesafe_value( $email_address, array(), TRUE );
		$getdata_key   = $this->API->Input->clean__makesafe_value( $getdata_key, array(), TRUE );

		//-----------------------------------------
		// Authenticate
		//-----------------------------------------

		if ( $this->__authenticate( $auth_key, $product_id ) !== FALSE )
		{
			//-----------------------------------------
			// Grab local extension file
			//-----------------------------------------

			/*
			 require_once( PATH_ROOT_WEB . "/converge_local/apis/local_extension.php" );
			 $extension = new local_extension( $this->api );

			 if ( is_callable( array( $extension, $getdata_key ) ) )
			 {
				$data = @call_user_func( array( $extension, $getdata_key), $email_address );
				}

				$return = array( 'data' => base64_encode( serialize( $data ) ) );

				# return complex data
				$this->API_Server->api_send_reply( $return );
				*/
			exit();
		}
	}

	/**
	 * Converge_Server :: onMemberDelete()
	 *
	 * Deletes the member. Keep in mind that the member may not be in the local DB if they've not yet visited this site.
	 *
	 * This will return a param "response" with either
	 * - FAILED			 (Unknown failure)
	 * - SUCCESS		 (Added OK)
	 *
	 *
	 * @param  int	   $product_id 	  	   			Product ID
	 * @param  string  $auth_key	   	   			Authentication Key
	 * @param  string  $multiple_email_addresses	Comma delimited list of email addresses
	 * @return xml
	 **/
	private function onMemberDelete ( $auth_key, $product_id, $multiple_email_addresses="" )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$return	    = "FAILED";
		$emails	    = explode( ",", $this->API->Db->escape_string( $this->API->Input->clean__makesafe_value( $multiple_email_addresses, array(), TRUE ) ) );
		$member_ids = array();
		$auth_key   = $this->API->Input->clean__md5_hash( $auth_key );
		$product_id = intval( $product_id );

		//-----------------------------------------
		// Authenticate
		//-----------------------------------------

		if ( $this->__authenticate( $auth_key, $product_id ) !== FALSE )
		{
			//-----------------------------------------
			// Get member IDs
			//-----------------------------------------

			$this->API->Db->cur_query = array(
					"do"     => "select",
					"table"  => "members",
					"where"  => "email IN ('" . implode( "','", $emails ) . "')"
					);

					$result = $this->API->Db->simple_exec_query();

					while ( $result->fetchInto( $row, DB_FETCHMODE_ASSOC ) )
					{
						$member_ids[ $row['id'] ] = $row['id'];
					}

					//-----------------------------------------
					// Remove the members
					//-----------------------------------------

					if ( count( $member_ids ) )
					{
						//-----------------------------------------
						// Get the member class
						//-----------------------------------------

						require_once( ROOT_PATH . "sources/action_admin/member.php" );
						$lib		   =  new ad_member();
						$lib->ipsclass =& $this->api;

						# Set up
						$this->api->member['mgroup'] = $this->api->vars['admin_group'];

						$lib->member_delete_do( $member_ids );
					}

					//-----------------------------------------
					// return
					//-----------------------------------------

					$return = 'SUCCESS';

					$this->API_Server->api_send_reply( array( 'complete'   => 1,
			 												'response'   => $return ) );
					exit();
		}
	}

	/**
	 * Converge_Server::onPasswordChange()
	 *
	 * handles new password change
	 *
	 * This will return a param "response" with either
	 * - FAILED			 (Unknown failure)
	 * - SUCCESS		 (Added OK)
	 *
	 *
	 * @param  int	   $product_id 	  	   		Product ID
	 * @param  string  $auth_key	   	   		Authentication Key
	 * @param  string  $email_address  			Email address
	 * @param  string  $md5_once_password		Plain text password hashed by MD5
	 * @return xml
	 **/
	private function onPasswordChange( $auth_key, $product_id, $email_address, $md5_once_password )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$auth_key		  = $this->api->txt_md5_clean( $auth_key );
		$product_id		= intval( $product_id );
		$email_address	   = $this->api->parse_clean_value( $email_address );
		$md5_once_password = $this->api->txt_md5_clean( $md5_once_password );
		$return			= 'FAILED';

		//-----------------------------------------
		// Authenticate
		//-----------------------------------------

		if ( $this->__authenticate( $auth_key, $product_id ) !== FALSE )
		{
			//-----------------------------------------
			// Update: CONVERGE
			//-----------------------------------------

			$salt	 = $this->api->converge->generate_password_salt(5);
			$passhash = $this->api->converge->generate_compiled_passhash( $salt, $md5_once_password );

			$converge = array(
							   'converge_pass_hash' => $passhash,
							   'converge_pass_salt' => str_replace( '\\', "\\\\", $salt )
			);

			$this->API->Db->do_update( 'members_converge', $converge, "converge_email='" . $this->API->Db->add_slashes( $email_address )  . "'" );

			$return = 'SUCCESS';

			$this->API_Server->api_send_reply( array( 'complete'   => 1,
			 												'response'   => $return ) );
			exit();
		}
	}

	/**
	 * Converge_Server::onEmailChange()
	 *
	 * Updates the local app's DB
	 *
	 * This will return a param "response" with either
	 * - FAILED			 (Unknown failure)
	 * - SUCCESS		 (Added OK)
	 *
	 *
	 * @param  int	   $product_id 	  	   		Product ID
	 * @param  string  $auth_key	   	   		Authentication Key
	 * @param  string  $old_email_address  		Existing email address
	 * @param  string  $new_email_address  		NEW email address to change
	 * @return xml
	 **/
	private function onEmailChange( $auth_key, $product_id, $old_email_address, $new_email_address )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$auth_key		  = $this->api->txt_md5_clean( $auth_key );
		$product_id		= intval( $product_id );
		$old_email_address = $this->api->parse_clean_value( $old_email_address );
		$new_email_address = $this->api->parse_clean_value( $new_email_address );
		$return			= 'FAILED';

		//-----------------------------------------
		// Get member
		//-----------------------------------------

		$member = $this->API->Db->build_and_exec_query( array( 'select' => '*',
																	'from'   => 'members',
																	'where'  => "email='" . $this->API->Db->add_slashes( $old_email_address ) . "'" ) );

		//-----------------------------------------
		// Authenticate
		//-----------------------------------------

		if ( $this->__authenticate( $auth_key, $product_id ) !== FALSE )
		{
			if ( $old_email_address AND $new_email_address )
			{
				$this->API->Db->do_update( 'members_converge', array( 'converge_email' => $new_email_address ), "converge_email='" . $this->API->Db->add_slashes( $old_email_address ) . "'" );

				$this->API->Db->do_update( 'members'		 , array( 'email'		  => $new_email_address ), "email='"		  . $this->API->Db->add_slashes( $old_email_address ) . "'" );

				//-----------------------------------------
				// Update member's username?
				// This happens when a converge member is
				// created
				//-----------------------------------------

				if ( $member['email'] == $old_email_address )
				{
					$this->API->Db->do_update( 'members', array( 'name' => $new_email_address ), "id='" . $member['id'] . "'" );
				}

				$return = 'SUCCESS';
			}

			$this->API_Server->api_send_reply( array( 'complete'   => 1,
			 												'response'   => $return ) );
			exit();
		}
	}


	/**
	 * Converge_Server::importMembers()
	 *
	 * Returns a batch of members to import
	 * Important!
	 * Each member row must return the following:
	 * - email_address
	 * - pass_salt (5 chr salt)
	 * - password  (md5 hash of: md5( md5( $salt ) . md5( $raw_pass ) );
	 * - ip_address (optional)
	 * - join_date (optional)
	 *
	 * EACH BATCH MUST BE ORDERED BY ID ASC (low to high)
	 *
	 * @param  string  $auth_key  	Authentication Key
	 * @param  int	   $product_id  Product ID
	 * @param  int	   $limit_a		SQL limit a
	 * @param  int	   $limit_b		SQL limit b
	 * @return xml
	 **/
	private function importMembers( $auth_key, $product_id, $limit_a, $limit_b )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$auth_key   = $this->api->txt_md5_clean( $auth_key );
		$product_id = intval( $product_id );
		$limit_a	= intval( $limit_a );
		$limit_b	= intval( $limit_b );

		//-----------------------------------------
		// Authenticate
		//-----------------------------------------

		if ( $this->__authenticate( $auth_key, $product_id ) !== FALSE )
		{
			//-----------------------------------------
			// INIT
			//-----------------------------------------

			$members = array();
			$done	= 0;

			//-----------------------------------------
			// Get Data
			//-----------------------------------------

			/* - This causes all sorts of mass hysteria and mysql madness - no index gets properly used and server will crash
			 Let's just not send the IP for now.  We can worry about this later if it's needed, but converge doesn't use it.
			 $this->API->Db->build_query( array( 'select' 	=> 'c.*',
			 'from'   	=> array( 'members_converge' => 'c' ),
			 'order'  	=> 'c.converge_id ASC',
			 'limit'  	=> array( $limit_a, $limit_b ),
			 'add_join'	=> array(
			 array(
			 'type'		=> 'left',
			 'select'	=> 'm.ip_address',
			 'where'		=> 'm.email=c.converge_email',
			 'from'		=> array( 'members' => 'm' ),
			 )
			 )
			 ) 		);*/

			$this->API->Db->build_query( array( 'select' 	=> '*',
													 'from'   	=> 'members_converge',
													 'order'  	=> 'converge_id ASC',
													 'limit'  	=> array( $limit_a, $limit_b ),
			) 		);

			$this->API->Db->exec_query();


			while( $row = $this->API->Db->fetch_row() )
			{
				$members[ $row['converge_id'] ] = array( 'email_address' => $row['converge_email'],
														 'pass_salt'	 => $row['converge_pass_salt'],
														 'password'	  => $row['converge_pass_hash'],
														 'ip_address'	 => $row['ip_address'],
														 'join_date'	 => $row['converge_joined'] );
			}

			if ( ! count( $members ) )
			{
				$done = 1;
			}

			$return = array( 'complete' => $done,
							 'members'  => $members );

			# return complex data
			$this->API_Server->api_send_reply( $return, 1 );
			exit();
		}
	}

	/**
	 * Converge_Server::getMembersInfo()
	 *
	 * IP.Converge uses this to gather how many users the local application has,
	 * and the last ID entered into the local application’s member table.
	 *
	 * Expected repsonse:
	 * count   => The number of users
	 * last_id => The last ID
	 *
	 * @param  string  $auth_key  	Authentication Key
	 * @param  int	  $product_id 	Product ID
	 * @return xml
	 **/
	private function getMembersInfo( $auth_key, $product_id )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$auth_key   = $this->api->txt_md5_clean( $auth_key );
		$product_id = intval( $product_id );

		//-----------------------------------------
		// Authenticate
		//-----------------------------------------

		if ( $this->__authenticate( $auth_key, $product_id ) !== FALSE )
		{
			//-----------------------------------------
			// Get Data
			//-----------------------------------------

			$member_count = $this->API->Db->build_and_exec_query( array( 'select' => 'COUNT(*) as count',
																			  'from'   => 'members' ) );

			$member_last  = $this->API->Db->build_and_exec_query( array( 'select' => 'MAX(id) as max',
																			  'from'   => 'members' ) );


			$this->API_Server->api_send_reply( array( 'count'   => intval( $member_count['count'] ),
			 												'last_id' => intval( $member_last['max'] ) ) );
			exit();
		}
	}

	/**
	 * Converge_Server::convergeLogOut()
	 *
	 * Logs in the member out of local application
	 *
	 * This will return a param "response" with either
	 * - FAILED			 (Unknown failure)
	 * - SUCCESS		 (Added OK)
	 *
	 *
	 * @param  string  $auth_key	   Authentication Key
	 * @param  int	   $product_id 	   Product ID
	 * @param  string  $email_address  Email address of user logged in
	 * @return xml
	 **/
	private function convergeLogOut( $auth_key, $product_id, $email_address='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$auth_key	  = $this->api->txt_md5_clean( $auth_key );
		$product_id	= intval( $product_id );
		$email_address = $this->api->parse_clean_value( $email_address );
		$update		= array();

		//-----------------------------------------
		// Authenticate
		//-----------------------------------------

		if ( $this->__authenticate( $auth_key, $product_id ) !== FALSE )
		{
			//-----------------------------------------
			// Get member
			//-----------------------------------------

			$member = $this->API->Db->build_and_exec_query( array( 'select' => '*',
																		'from'   => 'members',
																		'where'  => "email='" . $this->API->Db->add_slashes( $email_address ) . "'" ) );

			//-----------------------------------------
			// If we've got a member, delete their session
			// and change the log in key so that the members
			// auto-log in cookies won't work.
			//-----------------------------------------

			if ( $member['id'] )
			{
				$update['member_login_key'] = $this->api->converge->generate_auto_log_in_key();
				$update['login_anonymous']  = '0&0';
				$update['last_visit']	   = time();
				$update['last_activity']	= time();

				$this->API->Db->do_update( 'members', $update, 'id=' . $member['id'] );

				//-----------------------------------------
				// Delete session
				//-----------------------------------------

				$this->API->Db->build_and_exec_query( array( 'delete' => 'sessions',
																  'where'  => 'member_id='.$member['id'] ) );
			}

			//-----------------------------------------
			// Add cookies
			//-----------------------------------------

			$this->API_Server->api_add_cookie_data( array( 'name'   => $this->api->vars['cookie_id'] . 'member_id',
														  		 'value'  => 0,
														  		 'path'   => $this->api->vars['cookie_path'],
														  		 'domain' => $this->api->vars['cookie_domain'],
														  		 'sticky' => 1 ) );

			$this->API_Server->api_add_cookie_data( array( 'name'   => $this->api->vars['cookie_id'] . 'pass_hash',
														  		 'value'  => 0,
														  		 'path'   => $this->api->vars['cookie_path'],
														  		 'domain' => $this->api->vars['cookie_domain'],
														  		 'sticky' => 1 ) );

			$this->API_Server->api_add_cookie_data( array( 'name'   => $this->api->vars['cookie_id'] . 'session_id',
														  		 'value'  => 0,
														  		 'path'   => $this->api->vars['cookie_path'],
														  		 'domain' => $this->api->vars['cookie_domain'],
														  		 'sticky' => 0 ) );

			$this->API_Server->api_send_reply( array( 'complete'   => 1,
			 												'response'   => 'SUCCESS' ) );
			exit();
		}
	}

	/**
	 * Converge_Server::convergeLogIn()
	 *
	 * Logs in the member to the local application
	 *
	 * This must return
	 * - complete   [ All done.. ]
	 * - session_id [ Session ID created ]*
	 * - member_id  [ Member's log in ID / email ]
	 * - log_in_key [ Member's log in key or password ]
	 * -- RESPONSE
	 * - FAILED			 (Unknown failure)
	 * - SUCCESS		 (Added OK)
	 *
	 * The session key and password/log in key will be posted to
	 * this apps handshake API so that the app can return cookies.
	 *
	 * @param  int	   $product_id 	  	   Product ID
	 * @param  string  $auth_key	   	   Authentication Key
	 * @param  string  $email_address  	   Email address of user logged in
	 * @param  string  $md5_once_password  The plain text password, hashed once
	 * @param  string  $ip_address  	   IP Address of registree
	 * @param  string  $unix_join_date	 The member's join date in unix format
	 * @param  string  $timezone	 	   The member's timezone
	 * @param  string  $dst_autocorrect	The member's DST autocorrect settings
	 * @return xml
	 **/
	private function convergeLogIn( $auth_key, $product_id, $email_address='', $md5_once_password='', $ip_address='', $unix_join_date='', $timezone=0, $dst_autocorrect=0, $extra_data='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$auth_key		  = $this->api->txt_md5_clean( $auth_key );
		$product_id		= intval( $product_id );
		$email_address	 = $this->api->parse_clean_value( $email_address );
		$md5_once_password = $this->api->txt_md5_clean( $md5_once_password );
		$ip_address		= $this->api->parse_clean_value( $ip_address );
		$unix_join_date	= intval( $unix_join_date );
		$timezone		  = intval( $timezone );
		$dst_autocorrect   = intval( $dst_autocorrect );
		$extra_data		= $this->api->parse_clean_value( $extra_data );
		$return			= 'FAILED';

		//-----------------------------------------
		// Authenticate
		//-----------------------------------------

		if ( $this->__authenticate( $auth_key, $product_id ) !== FALSE )
		{
			//-----------------------------------------
			// Extra data?
			//-----------------------------------------

			if ( $exta_data )
			{
				$external_data = unserialize( base64_decode( $extra_data ) );
			}

			//-----------------------------------------
			// Get member
			//-----------------------------------------

			$this->api->member = $this->API->Db->build_and_exec_query( array( 'select' => '*',
																						'from'   => 'members',
																						'where'  => "email='" . $this->API->Db->add_slashes( $email_address ) . "'" ) );

			//-----------------------------------------
			// No such user? Create one!
			// FAIL SAFE
			//-----------------------------------------

			if ( ! $this->api->member['id'] )
			{
				$unix_join_date	= $unix_join_date	? $unix_join_date	: time();
				$md5_once_password = $md5_once_password ? $md5_once_password : md5( $email_address . $unix_join_date . uniqid( microtime() ) );
				$ip_address		= $ip_address		? $ip_address		: '127.0.0.1';

				$this->api->member = $this->__create_user_account( $email_address, $md5_once_password, $ip_address, $unix_join_date, $timezone, $dst_autocorrect );
				$return = 'SUCCESS';
			}
			else
			{
				$return = 'SUCCESS';
			}

			//-----------------------------------------
			// Start session
			//-----------------------------------------

			$session = $this->__create_user_session( $this->api->member );

			//-----------------------------------------
			// Add cookies
			//-----------------------------------------

			$this->API_Server->api_add_cookie_data( array( 'name'   => $this->api->vars['cookie_id'] . 'member_id',
														  		 'value'  => $session['id'],
														  		 'path'   => $this->api->vars['cookie_path'],
														  		 'domain' => $this->api->vars['cookie_domain'],
														  		 'sticky' => 1 ) );

			$this->API_Server->api_add_cookie_data( array( 'name'   => $this->api->vars['cookie_id'] . 'pass_hash',
														  		 'value'  => $session['member_login_key'],
														  		 'path'   => $this->api->vars['cookie_path'],
														  		 'domain' => $this->api->vars['cookie_domain'],
														  		 'sticky' => 1 ) );

			$this->API_Server->api_add_cookie_data( array( 'name'   => $this->api->vars['cookie_id'] . 'session_id',
														  		 'value'  => $session['_session_id'],
														  		 'path'   => $this->api->vars['cookie_path'],
														  		 'domain' => $this->api->vars['cookie_domain'],
														  		 'sticky' => 0 ) );

			$this->API_Server->api_send_reply( array( 'complete'   => 1,
															'response'   => $return,
			 												'session_id' => $session['_session_id'],
															'member_id'  => $session['id'],
			 												'log_in_key' => $session['member_login_key'] ) );
			exit();
		}
	}

	/**
	 * Converge_Server::__create_user_session()
	 *
	 * Has to return at least the member ID, member log in key and session ID
	 *
	 *
	 * @param  array  $member  	Array of member information
	 * @return array  $session	Session information
	 **/
	private function __create_user_session( $member )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$update = array();

		//-----------------------------------------
		// Generate a new log in key
		//-----------------------------------------

		if ( $this->api->vars['login_change_key'] OR ! $member['member_login_key'] )
		{
			$update['member_login_key'] = $this->api->converge->generate_auto_log_in_key();
		}

		//-----------------------------------------
		// Set our privacy status
		//-----------------------------------------

		$update['login_anonymous'] = '0&1';

		//-----------------------------------------
		// Update member?
		//-----------------------------------------

		if ( is_array( $update ) and count( $update ) )
		{
			$this->API->Db->do_update( 'members', $update, 'id=' . $member['id'] );
		}

		//-----------------------------------------
		// Still here? Create a new session
		//-----------------------------------------

		require_once( ROOT_PATH . 'sources/classes/class_session.php' );
		$session		   =  new session();
		$session->ipsclass =& $this->api;
		$session->time_now =  UNIX_TIME_NOW;
		$session->member   =  $member;

		$session->create_member_session();

		$session->member['_session_id'] = $session->session_id;

		return $session->member;
	}

	/**
	 * Converge_Server::__create_user_account()
	 *
	 * Routine to create a local user account
	 *
	 *
	 * @param  string  $email_address  	   Email address of user logged in
	 * @param  string  $md5_once_password  The plain text password, hashed once
	 * @param  string  $ip_address  	   IP Address of registree
	 * @param  string  $unix_join_date	 The member's join date in unix format
	 * @param  string  $timezone	 	   The member's timezone
	 * @param  string  $dst_autocorrect	The member's DST autocorrect settings
	 * @return array   $member			   Newly created member array
	 **/
	private function __create_user_account( $email_address='', $md5_once_password, $ip_address, $unix_join_date, $timezone=0, $dst_autocorrect=0 )
	{
		//-----------------------------------------
		// Check to make sure there's not already
		// a member registered.
		//-----------------------------------------

		$member = $this->API->Db->build_and_exec_query( array( 'select' => '*',
																	'from'   => 'members',
																	'where'  => "email='" . $this->API->Db->add_slashes( $email_address ) . "'" ) );

		if ( $member['id'] )
		{
			return $member;
		}

		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$unix_join_date = $unix_join_date ? $unix_join_date : time();
		$ip_address	 = $ip_address	 ? $ip_address	 : $this->api->ip_address;

		//-----------------------------------------
		// Grab module..
		//-----------------------------------------

		require( ROOT_PATH . "sources/loginauth/login_core.php" );
		$login_core		   = new login_core();
		$login_core->ipsclass = $this->api;

		//-----------------------------------------
		// Create member
		//-----------------------------------------

		$login_core->tmp_display = 'cvg_' . rand();

		$member = $login_core->_create_local_member( $email_address, $md5_once_password, $email_address, $unix_join_date, $ip_address );

		return $member;
	}

	/**
	 * Converge_Server::__authenticate()
	 *
	 * Checks to see if the request is allowed
	 *
	 * @param  string $key			Authenticate Key
	 * @param  string $product_id   Product ID
	 * @access Private
	 * @return string		 Error message, if any
	 **/
	private function __authenticate( $key, $product_id )
	{
		//-----------------------------------------
		// Check converge users API DB
		//-----------------------------------------

		$info = $this->API->Db->build_and_exec_query( array( 'select' => '*',
																  'from'   => 'converge_local',
																  'where'  => "converge_product_id=" . intval($product_id) . " AND converge_active=1 AND converge_api_code='{$key}'" ) );

		//-----------------------------------------
		// Got a user?
		//-----------------------------------------

		if ( ! $info['converge_api_code'] )
		{
			$this->API_Server->api_send_error( 100, 'Unauthorized User' );
			return FALSE;
		}
		else if ( CVG_IP_MATCH AND ( $this->api->my_getenv('REMOTE_ADDR') != $info['converge_ip_address'] ) )
		{
			$this->API_Server->api_send_error( 101, 'IP ADDRESS not registered' );
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}


}
?>