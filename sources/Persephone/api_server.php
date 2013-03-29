<?php

/**
 * Invision Power Services
 * IP.Board v3.0.0
 * This class can act as an API server, handling API requests
 * Last Updated: $Date: 2009-05-25 23:57:26 -0400 (Mon, 25 May 2009) $
 *
 * @author 		$Author: josh $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		Invision Power Services Kernel
 * @link		http://www.invisionpower.com
 * @since		Friday 6th January 2006 (12:24)
 * @version		$Revision: 271 $
 *
 * Examples of sending and receiving API data
 * <code>
 * # APPLICATION: SEND AN API REQUEST and PARSE DATA
 * $api_server->api_send_request( 'http://www.domain.com/xmlrpc.php', 'get_members', array( 'name' => 'matt', 'email' => 'matt@email.com' ) );
 * # APPLICATION: PICK UP REPLY and PARSE
 * print_r( $api_server->params );
 *
 * # SERVER: PARSE DATA, MAKE DATA and RETURN
 * $api_server->api_decode_request( $_SERVER['RAW_HTTP_POST_DATA'] );
 * print $api_server->method_name;
 * print_r( $api_server->params );
 * # SERVER: SEND DATA BACK
 * # Is complex array, so we choose to encode and send with the , 1 flag
 * $api_server->api_send_reply( array( 'matt' => array( 'email' => 'matt@email.com', 'joined' => '01-01-2005' ) ), 1 );
 * </code>
 *
 */

if ( ! defined( "INIT_DONE" ) )
{
	print "Improper access! Exiting now...";
	exit();
}

class API_Server
{
	/**
	 * XML-RPC class
	 *
	 * @var object
	 */
	public $xmlrpc;

	/**
	 * Raw incoming data
	 *
	 * @var string
	 */
	public $raw_request;

	/**
	 * Requested method name
	 *
	 * @var string
	 */
	private $method_name;

	/**
	 * Raw data incoming
	 *
	 * @var string
	 */
	private $_raw_in_data;

	/**
	 * Raw data output
	 *
	 * @var string
	 */
	private $_raw_out_data;

	/**
	 * Method params
	 *
	 * @var array
	 */
	public $params = array();

	/**
	 * XML-RPC serialized 64 key
	 *
	 * @var string
	 */
	private $serialized_key = '__serialized64__';

	/**
	 * Server function object
	 *
	 * @var object
	 */
	private $xml_server;

	/**
	 * Return cookie information
	 *
	 * @var array
	 */
	public $cookies = array();

	/**
	 * XML-RPC cookie serialized 64 key
	 *
	 * @var string
	 */
	private $cookie_serialized_key = '__cookie__serialized64__';

	/**
	 * HTTP Auth required
	 *
	 * @var string
	 * @var string
	 */
	public $auth_user = "";
	public $auth_pass = "";

	/**
	 * Errors array
	 *
	 * @var array
	 */
	public $errors = array();

    /**
	 * Constructor
	 *
	 * @return    void
	 */
	public function __construct ()
	{
		if ( ! is_object( $this->xmlrpc ) )
		{
			require_once( PATH_SOURCES . '/xml/xml_rpc.php' );
			$this->xmlrpc = new XML_RPC();
		}

		$this->cookies = array();
	}

    /**
	 * Add object map to this class
	 *
	 * @param    string    Incoming data
	 * @return   string    api
     */
    public function decode_request ( $incoming="" )
    {
        if ( ! $incoming )
        {
	        # PHP Bug: http://bugs.php.net/bug.php?id=41293

			if ( PHP_VERSION == "5.2.2" )
			{
				$GLOBALS['HTTP_RAW_POST_DATA'] = file_get_contents( "php://input" );
			}

            $incoming = isset( $GLOBALS['HTTP_RAW_POST_DATA'] ) ? $GLOBALS['HTTP_RAW_POST_DATA'] : '';
        }

        //-----------------------------------------
        // Get data and dispatch
        //-----------------------------------------

        $this->api_decode_request( $incoming );

		$this->raw_request = $incoming;

        $api_call = explode( ".", $this->method_name );

		if ( count( $api_call ) > 1 )
        {
            $this->method_name = $api_call[1];

            return $api_call[0];
        }
        else
        {
            return 'default';
        }
    }

	/**
	 * Add object map to this class
	 *
	 * @param     object		Server class object
	 * @param     string     Document type
	 * @return    boolean
	 */
	public function add_object_map ( $server_class, $doc_type='UTF-8' )
	{
		$this->xmlrpc->doc_type = $doc_type;

		if ( is_object( $server_class ) )
		{
			$this->xml_server =& $server_class;

			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Get the XML-RPC data
	 *
	 * @param     string     Incoming data
	 * @return    mixed      Boolean, Output error
	 */
	public function get_xml_rpc ( $incoming="" )
	{
		if ( ! $this->xml_server )
		{
			return FALSE;
		}

		//-----------------------------------------
		// Got function?
		//-----------------------------------------

		if ( $this->method_name and is_array( $this->xml_server->__dispatch_map[ $this->method_name ] ) )
		{
			$func    = $this->method_name;
			$_params = array();

			//-----------------------------------------
			// Figure out params to use...
			//-----------------------------------------

			if ( is_array( $this->params ) and is_array( $this->xml_server->__dispatch_map[ $func ]['in'] ) )
			{
				foreach ( $this->xml_server->__dispatch_map[ $func ]['in'] as $field => $type )
				{
					$_var = $this->params[ $field ];

					switch ($type)
					{
						default:
						case 'string':
							$_var = (string) $_var;
							break;
		                case 'int':
		 				case 'i4':
							$_var = (int)    $_var;
							break;
		                case 'double':
							$_var = (double) $_var;
							break;
		                case 'boolean':
							$_var = (bool)   $_var;
							break;
						case 'base64':
							$_var = trim( $_var );
							break;
						case 'struct':
							$_var = is_array( $_var ) ? $_var : (string) $_var;
							break;
		            }

					$_params[ $field ] = $_var;
				}
			}

			if ( is_array( $_params ) )
			{
				@call_user_func_array( array( &$this->xml_server, $func), $_params );
			}
			else
			{
				@call_user_func( array( &$this->xml_server, $func), $_params );
			}
		}
		else
		{
			//-----------------------------------------
			// Return false
			//-----------------------------------------

			$this->api_send_error( 100, 'No methodRequest function -' . htmlspecialchars( $this->method_name ) . ' defined / found' );
			exit();
		}
	}

	/**
	 * Set a cookie for the API request
	 *
	 * @param     array      Array of params to send
	 * @return    void
	 */
	public function api_add_cookie_data ( $data )
	{
		if ( $data['name'] )
		{
			$this->cookies[ $data['name'] ] = $data;
		}
	}

	/**
	 * Return API Request
	 *
	 * @param     array      Array of params to send
	 * @param     integer    Complex data: Encode before sending
	 * @param     array      Forced data type mapping
	 * @return    void
	 */
	public function api_send_reply ( $data=array(), $complex_data=0, $force=array() )
	{
		//-----------------------------------------
		// Cookies?
		//-----------------------------------------

		if ( is_array( $this->cookies ) and count( $this->cookies ) )
		{
			$data[ $this->cookie_serialized_key ] = $this->_encode_base64_array( $this->cookies );
			$this->xmlrpc->map_type_to_key[ $this->cookie_serialized_key ] = 'base64';
		}

		//-----------------------------------------
		// Check
		//-----------------------------------------

		if ( ! is_array( $data ) )
		{
			$this->xmlrpc->return_value( $data );
		}
		elseif ( ! count( $data ) )
		{
			# No data? Just return true
			$this->xmlrpc->return_true_document();
		}

		//-----------------------------------------
		// Complex data?
		//-----------------------------------------

		if ( $complex_data )
		{
			$_tmp = $data;
			$data = array();
			$data[ $this->serialized_key ] = $this->_encode_base64_array( $_tmp );
			$this->xmlrpc->map_type_to_key[ $this->serialized_key ] = 'base64';
		}

		//-----------------------------------------
		// Force type?
		//-----------------------------------------

		if ( is_array( $force ) and count($force) > 0 )
		{
			foreach ( $force as $key => $type )
			{
				$this->xmlrpc->map_type_to_key[ $key ] = $type;
			}
		}

		//-----------------------------------------
		// Send...
		//-----------------------------------------

		$this->xmlrpc->return_params( $data );
	}

	/**
	 * Return API Request (ERROR)
	 *
	 * @param     integer   Error Code
	 * @param     string  	Error message
	 * @return    void
	 */
	public function api_send_error ( $error_code, $error_msg )
	{
		$this->xmlrpc->return_error( $error_code, $error_msg );
	}

	/**
	 * Decode API Request
	 *
	 * @param     string     Raw data picked up
	 * @return    void
	 */
	public function api_decode_request ( $raw_data )
	{
		//-----------------------------------------
		// Get data...
		//-----------------------------------------

		$raw = $this->xmlrpc->decode_xml_rpc( $raw_data );

		//-----------------------------------------
		// Process return data
		//-----------------------------------------

		$this->api_process_data( $raw );
	}

	/**
	 * Send API Request
	 *
	 * @param     string     URL to send request to
	 * @param     string     Method name for API to pick up
	 * @param     array      Data to send
	 * @param     integer    Complex data: Encode before sending
	 * @return    boolean    Data sent successfully
	 */
	public function api_send_request ( $url, $method_name, $data=array(), $complex_data=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$return_data             = array();
		$raw                     = array();
		$this->xmlrpc->errors    = array();
		$this->xmlrpc->auth_user = $this->auth_user;
		$this->xmlrpc->auth_pass = $this->auth_pass;

		//-----------------------------------------
		// Cookies?
		//-----------------------------------------

		if ( is_array( $this->cookies ) and count( $this->cookies ) )
		{
			$data[ $this->cookie_serialized_key ] = $this->_encode_base64_array( $this->cookies );
			$this->xmlrpc->map_type_to_key[ $this->cookie_serialized_key ] = 'base64';
		}

		//-----------------------------------------
		// Complex data?
		//-----------------------------------------

		if ( $complex_data )
		{
			$_tmp = $data;
			$data = array();
			$data[ $this->serialized_key ] = $this->_encode_base64_array( $_tmp );
			$this->xmlrpc->map_type_to_key[ $this->serialized_key ] = 'base64';
		}

		//-----------------------------------------
		// Get data...
		//-----------------------------------------

		$return_data = $this->xmlrpc->sendXmlRpc( $url, $method_name, $data );

		if ( count( $this->xmlrpc->errors ) )
		{
			$this->errors = $this->xmlrpc->errors;
			return FALSE;
		}

		//-----------------------------------------
		// Process return data
		//-----------------------------------------

		$this->api_process_data( $return_data );

		return TRUE;
	}

	/**
	 * Process returned data
	 *
	 * @param     array      Raw array
	 * @return    array      Cleaned array
	 */
	public function api_process_data ( $raw=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$_params              = $this->xmlrpc->get_params( $raw );
		$this->method_name    = $this->xmlrpc->get_method_name( $raw );
		$this->params         = array();
		$this->_raw_in_data   = var_export( $raw, TRUE );
		$this->_raw_out_data  = var_export( $_params, TRUE );

		//-----------------------------------------
		// Debug?
		//-----------------------------------------

		if ( XML_RPC_DEBUG_ON )
		{
			$this->xmlrpc->add_debug( "API_PROCESS_DECODE: IN PARAMS:  " . var_export( $raw, TRUE ) );
			$this->xmlrpc->add_debug( "API_PROCESS_DECODE: OUT PARAMS: " . var_export( $_params, TRUE ) );
		}

		//-----------------------------------------
		// Fix up params
		//-----------------------------------------

		if ( isset( $_params[0]) and is_array( $_params[0] ) )
		{
			foreach ( $_params[0] as $k => $v )
			{
				if ( $k != '' and $k == $this->serialized_key )
				{
					$_tmp = $this->_decode_base64_array( $v );

					if ( is_array( $_tmp ) and count( $_tmp ) )
					{
						$this->params = array_merge( $this->params, $_tmp );
					}
				}
				elseif ( $k != '' and $k == $this->cookie_serialized_key )
				{
					$_cookies = $this->_decode_base64_array( $v );

					if ( is_array( $_cookies ) and count( $_cookies ) )
					{
						foreach ( $_cookies as $cookie_data )
						{
							if ( $cookie_data['sticky'] == 1 )
					        {
					        	$cookie_data['expires'] = time() + 60*60*24*365;
					        }

							$cookie_data['path'] = $cookie_data['path'] ? $cookie_data['path'] : '/';

					        @setcookie( $cookie_data['name'], $cookie_data['value'], $cookie_data['expires'], $cookie_data['path'], $cookie_data['domain'] );

							if ( XML_RPC_DEBUG_ON )
							{
								$this->xmlrpc->add_debug( "API_PROCESS_DECODE: SETTING COOKIE:  " . var_export( $cookie_data, TRUE ) );
							}
						}
					}
				}
				else
				{
					$this->params[ $k ] = $v;
				}
			}
		}
		elseif ( is_array( $_params ) )
		{
			$i = 0;
			foreach ( $_params as $v )
			{
				$this->params['param'.$i] = $v;
				$i++;
			}
		}
	}

	/**
	 * Encode array
	 *
	 * @param     array      Raw array
	 * @return    string     Encoded string
	 */
	private function _encode_base64_array ( $array )
	{
		return base64_encode( serialize( $array ) );
	}

	/**
	 * Decode array
	 *
	 * @param     string     Encoded string
	 * @return    array      Raw array
	 */
	private function _decode_base64_array ( $data )
	{
		if ( ! is_array( $data ) )
		{
			return unserialize( base64_decode( $data ) );
		}
		else
		{
			return $data;
		}
	}
}

?>