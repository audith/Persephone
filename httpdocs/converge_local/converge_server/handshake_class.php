<?php

if ( ! defined( "INIT_DONE" ) )
{
	print "Improper access! Exiting now...";
	exit();
}

/**
 * IP.Converge : Handshake Server Class
 *
 * @package  Invision Power Board (Adapted to Audith CMS)
 * @author   Matthew Mecham (Adapted by Shahriyar Imanov)
 * @version  2.1
 **/
class handshake_server
{
	/**
	 * Defines the service for WSDL
	 * @access Private
	 * @var array
	 **/
	public $__dispatch_map = array();

	/**
	 * API Object Reference
	 * @access Private
	 * @var object
	 **/
	public $API;

	/**
	 * API SERVER Class
	 * @access Private
	 * @var object
	 **/
	public $API_Server;

	/**
	 * Constructor
	 *
	 * @return void
	 **/
	public function __construct ( & $API, & $API_Server )
	{
		//-----------------------------------------
		// Bring-in API and API-Server Object References
		//-----------------------------------------

		$this->API        = $API;
		$this->API_Server = $API_Server;

		//-----------------------------------------
		// Build dispatch list
		//-----------------------------------------

		$this->__dispatch_map[ 'handshakeStart' ] = array(
				'in'  => array(
						'reg_id'           => "int",
						'reg_code'         => "string",
						'reg_date'         => "string",
						'reg_product_id'   => "int",
						'converge_url'     => "string",
						'acp_email'        => "string",
						'acp_md5_password' => "string",
						'http_user'        => "string",
						'http_pass' 	   => "string"
						),
				'out' => array( 'response' => "xmlrpc" )
						);

						$this->__dispatch_map[ 'handshakeEnd' ] = array(
				'in'  => array(
						'reg_id'              => "int",
						'reg_code'            => "string",
						'reg_date'            => "string",
						'reg_product_id'      => "int",
						'converge_url'        => "string",
						'handshake_completed' => "int"
						),
				'out' => array( 'response' => "xmlrpc" )
						);

						$this->__dispatch_map[ 'handshakeRemove' ] = array(
			   'in'  => array(
							'reg_product_id'      => "int",
							'reg_code'            => "string" ),
			   'out' => array( 'response' => "xmlrpc" )
						);
	}

	/**
	 * Returns all data...
	 *
	 * @param  integer  $reg_id           Converge reg ID
	 * @param  string  	$reg_code         Converge API Code (MUST BE PRESENT IN ALL RETURNED API REQUESTS).
	 * @param  integer  $reg_date         Unix stamp of converge request start time
	 * @param  integer  $reg_product_id   Converge product ID (MUST BE PRESENT IN ALL RETURNED API REQUESTS)
	 * @param  string	$converge_url     Converge application base url (no slashes or paths)
	 * @param  string   $acp_email
	 * @param  string   $acp_md5_password
	 * @param  string   $http_user
	 * @param  string   $http_pass
	 * @return xml
	 **/
	public function handshakeStart ( $reg_id="", $reg_code="", $reg_date="", $reg_product_id="", $converge_url="", $acp_email="", $acp_md5_password="", $http_user="", $http_pass="" )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$reg_id			  = intval( $reg_id );
		$reg_code         = $this->API->Input->clean__md5_hash ( $reg_code );
		$reg_date	      = intval( $reg_date );
		$reg_product_id	  = intval( $reg_product_id );
		$converge_url	  = $this->API->Input->clean__makesafe_value( $converge_url, array(), TRUE );
		$acp_email	      = $this->API->Input->clean__makesafe_value( $acp_email, array(), TRUE );
		$acp_md5_password = $this->API->Input->clean__md5_hash( $acp_md5_password );

		//-----------------------------------------
		// Check ACP user
		//-----------------------------------------

		if ( ! $acp_email AND ! $acp_md5_password )
		{
			$this->API_Server->api_send_error( 500, "Missing ACP Email address and/or ACP Password" );
			return FALSE;
		}
		else
		{
			$this->API->IPS_Converge->converge_load_member( $acp_email );

			if ( ! $this->API->IPS_Converge->member['converge_id'] )
			{
				$this->API_Server->api_send_error( 501, "ACP Email or Password Incorrect" );
				return FALSE;
			}
			else
			{
				//-----------------------------------------
				// Get member
				//-----------------------------------------

				$this->API->Db->cur_query = array(
						"do"     => "select",
						"fields" => array( "m.*" ),
						"table"  => array( "m"=>"members" ),
						"add_join" => array(
				0 => array(
										"fields"    => array( "g.*" ),
										"table"     => "groups AS g",
										"where"     => "ON g.g_id = m.mgroup",
										"join_type" => "INNER"
										)
										),
						"where" => "id='" . intval( $this->API->IPS_Converge->member['converge_id'] ) . "'",
						"order" => NULL,
						"limit" => NULL
										);

										$result = $this->API->Db->simple_exec_query();

										if ( $result->numRows() )
										{
											$member = $result->fetchRow( DB_FETCHMODE_ASSOC );
										}

										//-----------------------------------------
										// Are we an admin?
										//-----------------------------------------

										if ( $member['g_access_cp'] != 1 )
										{
											$this->API_Server->api_send_error( 501, "The ACP member does not have ACP access" );
											return FALSE;
										}

										//-----------------------------------------
										// Are we a root admin?
										//-----------------------------------------

										if ( $member['mgroup'] != $this->API->config['security']['admin_group'] )
										{
											$this->API_Server->api_send_error( 501, "The ACP member is not a root admin" );
											return FALSE;
										}

										//-----------------------------------------
										// Check password...
										//-----------------------------------------

										if ( $this->API->IPS_Converge->converge_authenticate_member( $acp_md5_password ) != TRUE )
										{
											$this->API_Server->api_send_error( 501, "ACP Email or Password Incorrect" );
											return FALSE;
										}
			}
		}

		//-----------------------------------------
		// Just send it all back and start
		// a row in the converge_local table with
		// the info, but don't flag as active...
		//-----------------------------------------

		$reply = array (
				'master_response' => 1,
				'reg_id'          => $reg_id,
				'reg_code'        => $reg_code,
				'reg_date'        => $reg_date,
				'reg_product_id'  => $reg_product_id,
				'converge_url'    => $converge_url
		);

		//-----------------------------------------
		// Add into DB
		//-----------------------------------------

		$this->API->Db->cur_query = array(
				"do"          => "insert",
				"table"       => "converge_local",
				"set"         => array(
						'converge_api_code'   => $reg_code,
						'converge_product_id' => $reg_product_id,
						'converge_added'      => $reg_date,
						'converge_ip_address' => $this->API->Input->my_getenv( "REMOTE_ADDR" ),
						'converge_url'        => $converge_url,
						'converge_active'     => 0,
						'converge_http_user'  => $http_user,
						'converge_http_pass'  => $http_pass
		)
		);

		$result = $this->API->Db->simple_exec_query();

		//-----------------------------------------
		// Send reply...
		//-----------------------------------------


		$this->API_Server->api_send_reply( $reply );
	}

	/**
	 * Returns all data...
	 *
	 * @param  integer  $reg_id               Converge reg ID
	 * @param  string  	$reg_code             Converge API Code (MUST BE PRESENT IN ALL RETURNED API REQUESTS).
	 * @param  integer  $reg_date             Unix stamp of converge request start time
	 * @param  integer  $reg_product_id	      Converge product ID (MUST BE PRESENT IN ALL RETURNED API REQUESTS)
	 * @param  string	$converge_url         Converge application base url (no slashes or paths)
	 * @param  integer  $handshake_completed  All done flag
	 * @return xml
	 **/
	public function handshakeEnd ( $reg_id="", $reg_code="", $reg_date="", $reg_product_id="", $converge_url="", $handshake_completed="" )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$reg_id			     = intval( $reg_id );
		$reg_code            = $this->API->Input->clean__md5_hash( $reg_code );
		$reg_date	         = intval( $reg_date );
		$reg_product_id	     = intval( $reg_product_id );
		$converge_url	     = $this->API->Input->clean__makesafe_value( $converge_url, array(), TRUE );
		$handshake_completed = intval( $handshake_completed );

		//-----------------------------------------
		// Grab data from the DB
		//-----------------------------------------

		$this->API->Db->cur_query = array(
				"do"     => "select_one",
				"table"  => "converge_local",
				"where"  => "converge_api_code='" . $reg_code . "' AND converge_product_id='" . $reg_product_id . "'"
				);

				$converge = $this->API->Db->simple_exec_query();

				//-----------------------------------------
				// Got it?
				//-----------------------------------------

				if ( $converge['converge_api_code'] )
				{
					$this->API->Db->cur_query = array(
					"do"           => "update",
					"tables"       => array( "converge_local" ),
					"set"          => array( "converge_active" => 0 )
					);

					$result = $this->API->Db->simple_exec_query();

					$this->API->Db->cur_query = array(
					"do"           => "update",
					"tables"       => array( "converge_local" ),
					"set"          => array( "converge_active" => 1 ),
					"where"        => "converge_api_code='" . $reg_code . "'"
					);

					$result = $this->API->Db->simple_exec_query();

					//-----------------------------------------
					// Sort out some vars
					//-----------------------------------------

					$_full_url = preg_replace( "#/$#", "", $converge_url ) . '/?p='.$reg_product_id;

					//-----------------------------------------
					// Update...
					//-----------------------------------------

					$this->API->Db->cur_query = array(
					"do"           => "update",
					"tables"       => array( "conf_settings" ),
					"set"          => array( 'conf_value=?' => NULL ),
					"where"        => "conf_key=?"
					);

					$result = $this->API->Db->simple_exec_query(
					array(
					array( "1", "ipconverge_enabled" ),
					array( $converge_url, "ipconverge_url" ),
					array( $reg_product_id, "ipconverge_pid" ),
					array( "converge", "login_key" ),
					array( "email", "login_usertype" )
					)
					);

					/*****************************************************************
					 * TODO : MIGHT NEED TO REBUILD CACHES, USING ACP CLASS METHODS
					 *****************************************************************/

					//-----------------------------------------
					// Switch over log in methods
					//-----------------------------------------

					$this->API->Db->cur_query = array(
					"do"           => "update",
					"tables"       => array( "login_methods" ),
					"set"          => array( 'login_enabled' => 0 )
					);

					$result = $this->API->Db->simple_exec_query();

					$this->API->Db->cur_query = array(
					"do"           => "update",
					"tables"       => array( "login_methods" ),
					"set"          => array(
							'login_enabled'      => 1,
							'login_login_url'    => "",
							'login_maintain_url' => "",
							'login_user_id'		 => "email",
							'login_logout_url'	 => "",
							'login_register_url' => ""
							),
					"where"        => "login_folder_name='ipconverge'"
					);

					$result = $this->API->Db->simple_exec_query();

					$this->API_Server->api_send_reply( array( "handshake_updated" => 1 ) );
				}
				else
				{
					$this->API_Server->api_send_error( 500, "Could not locate a Converge handshake to update" );
					return FALSE;
				}
	}

	/**
	 * Unconverges an application
	 *
	 * @param  integer  $reg_id  		Converge reg ID
	 * @param  string  	$reg_code   	Converge API Code (MUST BE PRESENT IN ALL RETURNED API REQUESTS).
	 * @return xml
	 **/
	public function handshakeRemove ( $reg_product_id='', $reg_code='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$reg_product_id = intval( $reg_product_id );
		$reg_code       = $this->API->Input->clean__md5_hash( $reg_code );

		//-----------------------------------------
		// Grab data from the DB
		//-----------------------------------------

		$this->API->Db->cur_query = array(
				"do"     => "select_row",
				"table"  => "converge_local",
				"where"  => "converge_api_code='" . $reg_code . "' AND converge_product_id='" . $reg_product_id . "'"
				);

				$converge = $this->API->Db->simple_exec_query();

				//-----------------------------------------
				// Check
				//-----------------------------------------

				if ( $converge['converge_active'] )
				{
					//-----------------------------------------
					// Remove app stuff
					//-----------------------------------------

					$this->API->Db->cur_query = array(
					"do"      => "delete",
					"table"   => "converge_local",
					"where"   => array( "converge_product_id=?", intval( $reg_product_id ) )
					);

					$result = $this->API->Db->simple_exec_query();

					//-----------------------------------------
					// Sort out some vars
					//-----------------------------------------

					$_full_url = preg_replace( "#/$#", "", $converge_url ) . '/?p='.$reg_product_id;

					//-----------------------------------------
					// Update...
					//-----------------------------------------

					$this->API->Db->cur_query = array(
					"do"           => "update",
					"tables"       => array( "conf_settings" ),
					"set"          => array( 'conf_value=?' => NULL ),
					"where"        => "conf_key=?"
					);

					$result = $this->API->Db->simple_exec_query(
					array(
					array( "0", "ipconverge_enabled" ),
					array( "", "ipconverge_url" ),
					array( 0, "ipconverge_pid" ),
					array( "internal", "login_key" ),
					array( "username", "login_usertype" )
					)
					);

					/**************************************************************
					 * TODO : MIGHT NEED TO REBUILD CACHES, USING ACP CLASS METHODS
					 **************************************************************/

					//-----------------------------------------
					// Switch over log in methods
					//-----------------------------------------

					$this->API->Db->cur_query = array(
					"do"           => "update",
					"tables"       => array( "login_methods" ),
					"set"          => array( 'login_enabled' => 0 )
					);

					$result = $this->API->Db->simple_exec_query();

					$this->API->Db->cur_query = array(
					"do"           => "update",
					"tables"       => array( "login_methods" ),
					"set"          => array(
							'login_enabled'      => 1,
							'login_login_url'    => "",
							'login_maintain_url' => "",
							'login_user_id'		 => "email",
							'login_logout_url'	 => "",
							'login_register_url' => ""
							),
					"where"        => "login_folder_name='internal'"
					);

					$result = $this->API->Db->simple_exec_query();

					$this->API_Server->api_send_reply( array( 'handshake_removed' => 1 ) );
				}
				else
				{
					$this->API_Server->api_send_reply( array( 'handshake_removed' => 0 ) );
				}

	}

}

?>