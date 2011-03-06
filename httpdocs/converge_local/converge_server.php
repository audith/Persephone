<?php

/**
 * Main executable wrapper.
 *
 * Set-up and load module to run
 *
 * @package	Invision Power Board ( Adapted to Audith CMS)
 * @author	Matthew Mecham @ IPS ( Adapted by Shahriyar Imanov <shehi@imanov.name> )
 * @version	2.1
 **/

//-----------------------------------------
// Matches IP address of requesting API
// Set to 0 to not match with IP address
//-----------------------------------------

define( "CVG_IP_MATCH", 1 );

require_once( "../init.php" );

//===========================================================================
// Require_Once() Kernel Classes
//===========================================================================

require_once( PATH_SOURCES  . "/kernel/api_debug.php"                     );
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

//--------------------------------
//  Turn off shutdown
//--------------------------------

$API->Db->use_shutdown = 0;

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

require_once( PATH_ROOT_WEB . "/converge_local/converge_server/main.php" );
require_once( PATH_SOURCES  . "/kernel/api_server.php"                   );

//===========================================================================
// Create the XML-RPC Server
//===========================================================================

$API_Server = new API_Server( $API );
$webservice = new Converge_Server( $API, $API_Server );
$API        = $API_Server->decode_request();

$API_Server->add_object_map( $webservice, "UTF-8" );

//-----------------------------------------
// Process....
//-----------------------------------------

$API_Server->get_xml_rpc();

?>