<?php

//===========================================================================
// Initialize
//===========================================================================

require_once( "../init.php" );

//===========================================================================
// Require_Once() Kernel Classes
//===========================================================================

require_once( PATH_SOURCES  . "/kernel/api.php"                           );
require_once( PATH_SOURCES  . "/kernel/db.php"                            );
// require_once( PATH_SOURCES  . "/kernel/display.php"                       );
require_once( PATH_SOURCES  . "/kernel/input.php"                         );
// require_once( PATH_SOURCES  . "/kernel/modules.php"                       );
require_once( PATH_SOURCES  . "/kernel/session.php"                       );
require_once( PATH_SOURCES  . "/kernel/ips_converge.php"                  );

//===========================================================================
// Instantiate API, disabling Modules support
//===========================================================================

$API = & new API( array( "Display", "Modules" ) );

//===========================================================================
// If IPS Converge is not enabled, return Converge fault response
//===========================================================================

if ( ! $API->config['ipconvergepublic']['ipconverge_public_enable'] )
{
	@header( "Content-type: text/xml" );
	print "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
			<methodResponse>
				<fault>
					<value>
						<struct>
							<member>
								<name>faultCode</name>
								<value>
									<int>1</int>
								</value>
							</member>
							<member>
								<name>faultString</name>
								<value>
									<string>IP.Converge is not enabled from your Admin Control Panel!</string>
								</value>
							</member>
						</struct>
					</value>
				</fault>
			</methodResponse>";
	exit();
}

//===========================================================================
// Bring-in required libs/source
//===========================================================================

require_once( PATH_SOURCES . "/kernel/api_server.php" );
require_once( "./converge_server/handshake_class.php" );

//===========================================================================
// Create the XML-RPC Server
//===========================================================================

$API_Server = new API_Server( $API );
$webservice = new handshake_server( $API, $API_Server );
$processor  = $API_Server->decode_request();

$API_Server->add_object_map( $webservice, "UTF-8" );

//-----------------------------------------
// Saying "info" or actually doing some
// work? Info is used by converge app to
// ensure this file exists and to grab the
// apps name.
//-----------------------------------------

if ( $_REQUEST['info'] )
{
	@header( "Content-type: text/plain" );
	print "<info>\n";
	print "\t<productname>" . htmlspecialchars( $API->config['general']['site_name'] ) . "</productname>\n";
	print "\t<productcode>AUDITH_CMS</productcode>\n";
	print "</info>";
	exit();
}
//-----------------------------------------
// Post log in:
// This is hit after a successful converge
// log in has been made. It's up the the local
// app to check the incoming data, and set
// cookies (optional)
//-----------------------------------------
elseif ( $_REQUEST['postlogin'] )
{
	//----------
	// INIT
	//----------

	$session_id  = addslashes( substr( trim( $_GET['session_id'] ), 0, 32 ) );
	$key         = substr( trim( $_GET['key'] ), 0, 32 );
	$member_id   = intval( $_GET['member_id'] );
	$product_id  = intval( $_GET['product_id'] );
	$set_cookies = intval( $_GET['cookies'] );

	//------------------
	// Get converge
	//------------------

	$this->API->Db->cur_query = array(
			"do"     => "select_one",
			"table " => "converge_local",
			"where" => "converge_active='1' AND converge_product_id='" . $product_id . "'",
	);

	$converge = $this->API->Db->simple_exec_query();

	//-------------------
	// Get member....
	//-------------------

	$this->API->Db->cur_query = array(
			"do"    => "select_one",
			"table" => "sessions",
			"where" => "id='" . $session_id . "' AND member_id='".$member_id . "'",
	);

	$session = $this->API->Db->simple_exec_query();

	if ( $session['member_id'] )
	{
		$this->API->Db->cur_query = array(
				"do"     => "select_one",
				"table"  => "members",
				"where"  => "id='".$member_id . "'",
		);

		$member = $this->API->Db->simple_exec_query();

		if ( md5( $member['member_login_key'] . $converge['converge_api_code'] ) == $key )
		{
			if ( $set_cookies )
			{
				$API->input->my_setcookie( "member_id" , $member['id']        , 1 );
				$API->input->my_setcookie( "pass_hash" , $member['login_key'] , 1 );
			}

			$API->input->my_setcookie( "session_id", $session_id, -1);
			$API->input->stronghold_set_cookie( $member['id'], $member['login_key'] );
		}

		//-----------------------------------------
		// Update session
		//-----------------------------------------

		$this->API->Db->cur_query = array(
				"do"           => "update",
				"tables"       => array( "sessions" ),
				"set"          => array(
						"browser"       => $this->API->Session->user_agent,
						"ip_address"    => $this->API->Session->ip_address
		),
				"where"        => "id='" . $session_id . "'"
				);

				$result = $this->API->Db->simple_exec_query();
	}

	//-----------------------------------------
	// Is this a partial member?
	// Not completed their sign in?
	//-----------------------------------------

	if ( $member['created_remote'] )
	{
		$this->API->Db->cur_query = array(
				"do"     => "select_one",
				"table"  => "members_partial",
				"where"  => "partial_member_id='" . $member['id'] . "'",
		);

		$pmember = $this->API->Db->simple_exec_query();

		if ( $pmember['partial_member_id'] )
		{
			/************************************************
			 * TODO : REDIRECT SO THAT USER COMPLETES HIS LOGIN
			 ************************************************/
		}
		else
		{
			//-----------------------------------------
			// Redirect...
			//-----------------------------------------

			$API->http_redirect( "Location: " . $API->config['page']['working_url'] );
		}
	}
	else
	{
		//-----------------------------------------
		// Redirect...
		//-----------------------------------------

		$API->http_redirect( "Location: " . $API->config['page']['working_url'] );
	}
}
else
{
	$API_Server->get_xml_rpc();
}

?>