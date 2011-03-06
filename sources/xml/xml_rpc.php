<?php

/**
 * Invision Power Services
 * IP.Board v3.0.0
 * XML-RPC Server: Send, receive and process XML-RPC requests
 * Last Updated: $Date: 2009-05-27 21:24:22 -0400 (Wed, 27 May 2009) $
 *
 * @author     $Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license     http://www.invisionpower.com/community/board/license.html
 * @package     Invision Power Services Kernel
 * @link     http://www.invisionpower.com
 * @since     6th January 2006
 * @version     $Revision: 273 $
 *
 *
 * Example Usage:
 * <code>
 * SENDING XML-RPC Request
 * (Optional)
 * $xmlrpc->map_type_to_key['key'] = 'string';
 * $xmlrpc->map_type_to_key['key2'] = 'base64';
 * $return = $xmlrpc->send_xml_rpc( 'http://domain.com/xml-rpc_server.php', 'methodNameHere', array( 'key' => 'value', 'key2' => 'value2' ) );
 * if ( $xmlrpc->errors )
 * {
 * 	print_r( $xmlrpc->errors );
 * }
 *
 * Decoding XML-RPC
 * $xmlrpc->decode( $raw_xmlrpc_text );
 *
 * print_r( $xmlrpc->xmlrpc_params );
 * RETURN
 * $xmlrpc->return_true_document();
 * </code>
 *
 */

if ( ! defined( "INIT_DONE" ) )
{
	print "Improper access! Exiting now...";
	exit();
}

class XML_RPC
{
	/**
	 * XML header
	 *
	 * @var string
	 */
	public $header = "";

	/**
	 * DOC type
	 *
	 * @var string
	 */
	public $doc_type = 'UTF-8';

	/**
	 * Error array
	 *
	 * @var array
	 */
	public $errors = array();

	/**
	 * Var types
	 *
	 * @var array
	 */
	public $var_types = array( 'string', 'int', 'i4', 'double', 'dateTime.iso8601', 'base64', 'boolean' );

	/**
	 * Extracted xmlrpc params
	 *
	 * @var array
	 */
	public $xmlrpc_params = array();

	/**
	 * Optionally map types to key
	 *
	 * @var array
	 */
	public $map_type_to_key = array();

	/**
	 * Auth required
	 *
	 * @var string
	 */
	public $auth_user = "";
	public $auth_pass = "";

	/**
	 * Zend_Log object
	 * @var object
	 */
	private $logger;


	/**
	 * Decode an XML RPC document
	 *
	 * @param     string    XML-RPC data
	 * @return    string    Decoded document
	 */
	public function decode_xml_rpc ( $_xml )
	{
		$xml_parser = new XML_RPC_Parser();
		$data = $xml_parser->parse( $_xml );

		if ( isset( $data['methodResponse']['fault'] ) )
		{
			$tmp = $this->adjust_value( $data['methodResponse']['fault']['value'] );
			$this->errors[] = $tmp['faultString'];
		}

		$this->xmlrpc_params = $this->get_params( $data );
		$this->xmlrpc_method_call = $this->get_method_name( $data );

		//------------
		// Debug?
		//------------

		if ( XML_RPC_DEBUG_ON )
		{
			$this->add_debug( "DECODING XML data: " . $_xml );
			$this->add_debug( "DECODE RESULT XML data: " . var_export( $data, TRUE ) );
		}

		return $data;
	}

	/**
	 * Adjust value of parameter
	 *
	 * @param     string    Curernt node
	 * @return    mixed     Proper cast value
	 */
	public function & adjust_value ( &$current_node )
	{
		if ( is_array( $current_node ) )
		{
			if ( isset( $current_node['array'] ) )
			{
				if ( ! is_array( $current_node['array']['data'] ) )
				{
					$temp = array();
				}
				else
				{
					$temp = &$current_node['array']['data']['value'];

					if ( is_array( $temp ) and array_key_exists( 0, $temp ) )
					{
						$count = count( $temp );

						for ( $n = 0 ; $n < $count ; $n++ )
						{
							$temp2[$n] = & $this->adjust_value( $temp[$n] );
						}

						$temp = &$temp2;

					}
					else
					{
						$temp2 = & $this->adjust_value( $temp );
						$temp = array( &$temp2 );
					}
				}
			}
			elseif ( isset( $current_node['struct'] ) )
			{
				if ( ! is_array( $current_node['struct'] ) )
				{
					return array();
				}
				else
				{
					$temp = &$current_node['struct']['member'];

					if ( is_array( $temp ) and array_key_exists( 0, $temp ) )
					{
						$count = count( $temp );

						for ( $n = 0 ; $n < $count ; $n++ )
						{
							$temp2[$temp[$n]['name']] = & $this->adjust_value( $temp[$n]['value'] );
						}
					}
					else
					{
						$temp2[$temp['name']] = & $this->adjust_value( $temp['value'] );
					}
					$temp = &$temp2;
				}
			}
			else
			{
				$got_it = FALSE;

				foreach( $this->var_types as $type )
				{
					if ( array_key_exists( $type, $current_node ) )
					{
						$temp = &$current_node[$type];
						$got_it = TRUE;
						break;
					}
				}

				if ( ! $got_it )
				{
					$type = 'string';

				}

				switch ( $type )
				{
					case 'int':
	     		case 'i4':
					case 'integer':
					case 'integar':
						$temp = (int)	$temp;
						break;
					case 'string':
						$temp = (string) $temp;
						break;
					case 'double':
						$temp = (double) $temp;
						break;
					case 'boolean':
						$temp = (bool)   $temp;
						break;
					case 'base64':
						$temp = trim( $temp );
						break;
				}
			}
		}
		else
		{
			$temp = (string) $current_node;
		}

		return $temp;
	}

	/**
	 * Get the params from the XML RPC return
	 *
	 * @param     array     Request data
	 * @return    array     Params
	 */
	public function get_params ( $request )
	{
		if ( isset( $request['methodCall']['params'] ) AND is_array( $request['methodCall']['params'] ) )
		{
			$temp = & $request['methodCall']['params']['param'];
		}
		else if ( isset( $request['methodResponse']['params'] ) AND is_array( $request['methodResponse']['params'] ) )
		{
			$temp = & $request['methodResponse']['params']['param'];
		}
		else
		{
			return array();
		}

		if ( is_array( $temp ) and array_key_exists( 0, $temp ) )
		{
			$count = count( $temp );

			for ( $n = 0 ; $n < $count ; $n++)
			{
				$temp2[$n] = & $this->adjust_value( $temp[$n]['value'] );
			}
		}
		else
		{
			$temp2[0] = & $this->adjust_value( $temp['value'] );
		}

		$temp = &$temp2;

		return $temp;
	}

	/**
	 * Get RPC method name
	 *
	 * @param     array     Request params
	 * @return    string    Method name
	 */
	public function get_method_name ( $request )
	{
		return isset( $request['methodCall']['methodName'] ) ? $request['methodCall']['methodName'] : "";
	}

	/**
	 * Create and send an XML document
	 *
	 * @param     string    URL to send XML-RPC data to
	 * @param     string    Method name to request
	 * @param     array   	Array of fields to send (must be in key => value pairings)
	 * @return    boolean     Sent successfully
	 */
	public function send_xml_rpc ( $url, $method_name='', $data_array=array() )
	{
		//-----------------------
		// Build RPC request
		//-----------------------

		$xmldata = $this->build_document( $data_array, $method_name );

		if ( $xmldata )
		{
			//-----------
			// Debug?
			//-----------

			if ( XML_RPC_DEBUG_ON )
			{
				$this->add_debug( "SENDING XML data: " . $xmldata );
			}

			//------------
			// Continue
			//------------

			return $this->post( $url, $xmldata );
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Prints a true document and exits
	 *
	 * @param     string    Value
	 * @return    void
	 */
	public function return_value ( $value )
	{
		$this->generate_header();

		$to_print = $this->header."<methodResponse>
		   <params>
			  <param>
				 <value>{$value}</value>
				 </param>
			  </params>
		   </methodResponse>";

		@header( "Connection: close" );
		@header( "Content-length: " . strlen( $to_print ) );
		@header( "Content-type: text/xml" );
		@header( "Date: " . date( "r" ) );
		print $to_print;

		exit();
	}

	/**
	 * Creates an XML-RPC complex document
	 *
	 * @param     array   	Array of fields to send (must be in key => value pairings)
	 * @param     string    Method name (optional)
	 * @return    string    finished document
	 */
	public function build_document ( $data_array, $method_name='' )
	{
		//---------
		// INIT
		//---------

		$xmldata = "";
		$root_tag = 'methodCall';

		//----------
		// Test
		//----------

		if ( ! is_array( $data_array ) or ! count( $data_array ) )
		{
			return FALSE;
		}

		if ( ! $method_name )
		{
			$root_tag = 'methodResponse';
		}

		$this->generate_header();

		$xmldata = $this->header . "\n";
		$xmldata .= "<".$root_tag.">\n";

		if ( $method_name )
		{
			$xmldata .= "\t<methodName>".$method_name."</methodName>\n";
		}

		$xmldata .= "\t<params>\n";
		$xmldata .= "\t\t<param>\n";
		$xmldata .= "\t\t\t<value>\n";

		if ( isset( $data_array[0] ) AND is_array( $data_array[0] ) )
		{
     	$xmldata .= "\t\t\t<array>\n";
			$xmldata .= "\t\t\t\t<data>\n";

			foreach( $data_array as $k => $v )
			{
				$xmldata .= "\t\t\t\t\t<value>\n";
				$xmldata .= "\t\t\t\t\t\t<struct>\n";

				foreach( $v as $k2 => $v2 )
				{
					$_type = $this->map_type_to_key[ $k2 ] ? $this->map_type_to_key[ $k2 ] : $this->get_string_type( $v2 );

					$xmldata .= "\t\t\t\t\t\t\t<member>\n";
					$xmldata .= "\t\t\t\t\t\t\t\t<name>" . $k2 . "</name>\n";
					if ( strpos( $v2, '>' ) !== FALSE or strpos( $v2, '<' ) !== FALSE or strpos( $v2, '&' ) !== FALSE )
					{
						$xmldata .= "\t\t\t\t\t\t\t\t<value><base64>" . base64_encode( $v2 ) . "</base64></value>\n";
					}
					else
					{
						$xmldata .= "\t\t\t\t\t\t\t\t<value><" . $_type . ">" . htmlspecialchars( $v2 ) . "</" . $_type . "></value>\n";
					}
					$xmldata .= "\t\t\t\t\t\t\t</member>\n";
				}

				$xmldata .= "\t\t\t\t\t\t</struct>\n";
				$xmldata .= "\t\t\t\t\t</value>\n";
			}

			$xmldata .= "\t\t\t\t</data>\n";
			$xmldata .= "\t\t\t</array>\n";
		}
		else
		{
			$xmldata .= "\t\t\t<struct>\n";

			foreach( $data_array as $k => $v )
			{
				if ( is_array( $v ) )
				{
					$xmldata .= $this->build_document_recursive( "", $k, $v, 4 );
				}
				else
				{
					$_type = isset( $this->map_type_to_key[ $k ] ) ? $this->map_type_to_key[ $k ] : $this->get_string_type( $v );

					$xmldata .= "\t\t\t\t<member>\n";
					$xmldata .= "\t\t\t\t\t<name>".$k."</name>\n";
					$xmldata .= "\t\t\t\t\t<value><".$_type.">" . htmlspecialchars( $v ) . "</" . $_type."></value>\n";
					$xmldata .= "\t\t\t\t</member>\n";
				}
			}

			$xmldata .= "\t\t\t</struct>\n";
		}

		$xmldata .= "\t\t\t</value>\n";
		$xmldata .= "\t\t</param>\n";
		$xmldata .= "\t</params>\n";
		$xmldata .= "</".$root_tag.">";

		return $xmldata;
	}

	/**
	 * Recursive method to build document
	 *
	 * @param     string    XML Data
	 * @param     string    Key
	 * @param     string    Value
	 * @param     integer     Depth
	 * @return    string    finished document
	 */
	private function build_document_recursive ( $xmldata, $k, $v, $depth=4 )
	{
		$xmldata .= "\t<member>\n";
		$xmldata .= "\t\t<name>".$k."</name>\n";
		$xmldata .= "\t\t<value>\n";
		$xmldata .= "\t\t\t<array>\n";
		$xmldata .= "\t\t\t\t<data>\n";
		$xmldata .= "\t\t\t\t\t<value>\n";
		$xmldata .= "\t\t\t\t\t\t<struct>\n";

		foreach( $v as $_k => $_v )
		{
			if ( is_array( $_v ) )
			{
				$depth++;
				$xmldata .= $this->build_document_recursive( $xmldata, $k, $v, $depth );
			}
			else
			{
				$_type = isset( $this->map_type_to_key[ $_k ] ) ? $this->map_type_to_key[ $_k ] : $this->get_string_type( $_v );

				$xmldata .= "\t\t\t\t\t\t\t<member>\n";
				$xmldata .= "\t\t\t\t\t\t\t\t<name>" . $_k . "</name>\n";
				if ( strpos( $_v, '>' ) !== FALSE or strpos( $_v, '<' ) !== FALSE or strpos( $_v, '&' ) !== FALSE )
				{
					$xmldata .= "\t\t\t\t\t\t\t\t<value><base64>" . base64_encode( $_v ) . "</base64></value>\n";
				}
				else
				{
					$xmldata .= "\t\t\t\t\t<value><" . $_type . ">" . htmlspecialchars( $_v ) . "</" . $_type . "></value>\n";
				}
				$xmldata .= "\t\t\t\t\t\t\t</member>\n";
			}
		}

		$xmldata .= "\t\t\t\t\t\t</struct>\n";
		$xmldata .= "\t\t\t\t\t</value>\n";
		$xmldata .= "\t\t\t\t</data>\n";
		$xmldata .= "\t\t\t</array>\n";
		$xmldata .= "\t\t</value>\n";
		$xmldata .= "\t</member>\n";

		return $xmldata;
	}

	/**
	 * Prints a true document and exits
	 *
	 * @return    void
	 */
	public function return_true_document ()
	{
		$this->generate_header();

		$to_print = $this->header."
		<methodResponse>
		   <params>
			  <param>
				 <value><boolean>1</boolean></value>
				 </param>
			  </params>
		   </methodResponse>";

		@header( "Connection: close" );
		@header( "Content-length: " . strlen( $to_print ) );
		@header( "Content-type: text/xml" );
		@header( "Date: " . date( "r" ) );
		print $to_print;

		exit();
	}

	/**
	 * Prints a document and exits
	 *
	 * @param     array      Array of params to return in key => value pairs
	 * @return    void
	 */
	public function return_params ( $data_array )
	{
		$to_print = $this->build_document( $data_array );
		@header( "Connection: close" );
		@header( "Content-length: ".strlen( $to_print ) );
		@header( "Content-type: text/xml" );
		@header( "Date: " . date( "r" ) );
		@header( "Pragma: no-cache" );
		@header( "Cache-Control: no-cache" );
		print $to_print;

		exit();
	}

	/**
	 * Prints an error message and exits
	 *
	 * @param     integer   Error code
	 * @param     string    Error Message
	 * @return    void
	 */
	public function return_error ( $error_code, $error_msg )
	{
		$this->generate_header();

		$to_print = $this->header . "
		<methodResponse>
		   <fault>
			  <value>
				 <struct>
					<member>
					   <name>faultCode</name>
					   <value>
						  <int>" . intval( $error_code ) . "</int>
						  </value>
					   </member>
					<member>
					   <name>faultString</name>
					   <value>
						  <string>" . $error_msg . "</string>
						  </value>
					   </member>
					</struct>
				 </value>
					</fault>
		   </methodResponse>";

		@header( "Connection: close" );
		@header( "Content-length: " . strlen( $to_print ) );
		@header( "Content-type: text/xml" );
		@header( "Date: " . date( "r" ) );
		print $to_print;

		exit();
	}

	/**
	 * Create and send an XML document
	 *
	 * @param     string    URL to send XML-RPC data to
	 * @param     array   	XML-RPC data
	 * @return    string    Decoded data
	 */
	public function post ( $file_location, $xmldata='' )
	{
		//----------
		// INIT
		//----------

		$data = null;
		$fsocket_timeout = 10;
		$header = "";

		//----------------
		// Send it..
		//----------------

		$url_parts = parse_url( $file_location );

		if ( ! $url_parts['host'] )
		{
			$this->errors[] = "No host found in the URL '{$file_location}'!";
			return FALSE;
		}

		//----------------
		// Finalize
		//----------------

		$host = $url_parts['host'];

		$port = ( isset( $url_parts['port'] ) ) ? $url_parts['port'] : ( $url_parts['scheme'] == 'https' ? 443 : 80 );

		//--------------------
		// User and pass?
		//--------------------

		if ( ! $this->auth_user AND $url_parts['user'] )
		{
			$this->auth_user = $url_parts['user'];
			$this->auth_pass = $url_parts['pass'];
		}

	  	//--------------------
	  	// Tidy up path
	  	//--------------------

	  	if ( ! empty( $url_parts["path"] ) )
		{
			$path = $url_parts["path"];
		}
		else
		{
			$path = "/";
		}

		if ( ! empty( $url_parts["query"] ) )
		{
			$path .= "?" . $url_parts["query"];
		}

		if ( ! $fp = @fsockopen( $host, $port, $errno, $errstr, $fsocket_timeout ) )
		{
			$this->errors[] = "CONNECTION REFUSED FROM {$host}";
			return FALSE;

		}
		else
		{
			$header = "POST $path HTTP/1.0\r\n";
			$header .= "User-Agent: Audith Persephone XML-RPC Client Library (\$Revision: 100 $)\r\n";
			$header .= "Host: $host\r\n";

			if ( $this->auth_user && $this->auth_pass )
			{
				$this->add_debug( "Authorization: Basic Performed" );

				$header .= "Authorization: Basic " . base64_encode( "{$this->auth_user}:{$this->auth_pass}" )."\r\n";
			}

			$header .= "Connection: close\r\n";
			$header .= "Content-Type: text/xml\r\n";
			$header .= "Content-Length: " . strlen( $xmldata ) . "\r\n\r\n";

			if ( ! fputs( $fp, $header . $xmldata ) )
			{
				$this->errors[] = "Unable to send request to $host!";
				return FALSE;
			}
		 }

		 @stream_set_timeout( $fp, $fsocket_timeout );

		 $status = @socket_get_status( $fp );

		 while( ! feof( $fp ) && ! $status['timed_out'] )
		 {
			$data  .= fgets( $fp, 8192 );
			$status = socket_get_status( $fp );
		 }

		fclose( $fp );

		//-----------------
		// Strip headers
		//-----------------

		$tmp = split( "\r\n\r\n", $data, 2 );
		$data = $tmp[1];

		//-------------
		// Debug?
		//-------------

		if ( XML_RPC_DEBUG_ON )
		{
			if ( $this->auth_pass )
			{
				$_pass = str_repeat( 'x', strlen( $this->auth_pass ) - 1 ) . substr( $this->auth_pass, -1 );
			}
			else
			{
				$_pass = "";
			}

			$this->add_debug( "POST RESPONSE to {$this->auth_user}:{$_pass}@$host{$path}: " . $data );
		}

		//-----------------
		// Continue
		//-----------------

		return $this->decode_xml_rpc( $data );
	}

	/**
	 * Get the XML-RPC string type
	 *
	 * @param     string    String
	 * @return    string    XML-RPC String Type
	 */
	public function get_string_type ( $string )
	{
		$type = gettype( $string );

		switch( $type )
		{
			default:
			case 'string':
				$type = 'string';
				break;
			case 'integer':
				$type = 'int';
				break;
			case 'double':
				$type = 'double';
				break;
			case 'null':
			case 'boolean':
				$type = 'boolean';
				break;
		}

		return $type;
	}

	/**
	 * Add debug message
	 *
	 * @param     string    Log message
	 * @return    boolean     Saved successful
	 */
	public function add_debug ( $msg )
	{
		if ( XML_RPC_DEBUG_FILE and XML_RPC_DEBUG_ON )
		{
			$message = "\n"
				. "SCRIPT NAME: " . $_SERVER["SCRIPT_NAME"] . "\n"
				. gmdate( 'r' ) . ' - ' . $_SERVER['REMOTE_ADDR'] . ' - ' . $msg . "\n\n";


			if ( ! is_object( $this->logger ) )
			{
				if ( ! class_exists( "Zend_Log" ) )
				{
					require_once( "Zend/Log.php" );
					require_once( "Zend/Log/Writer/Stream.php" );
				}

				$this->logger = new Zend_Log();
				if ( file_exists( XML_RPC_DEBUG_FILE ) and is_writable( XML_RPC_DEBUG_FILE ) )
				{
					$this->logger->addWriter( new Zend_Log_Writer_Stream( XML_RPC_DEBUG_FILE ) );
				}
			}

			$this->logger->log( $message, 7 );
		}

		return TRUE;
	}

	/**
	 * Create the XML header
	 *
	 * @return    void
	 */
	protected function generate_header ()
	{
		$this->header = '<?xml version="1.0" encoding="' . $this->doc_type . '" ?>';
	}
}

class XML_RPC_Parser
{
	/**
	 * Parser object
	 *
	 * @var object
	 */
	public $parser;

	/**
	 * Current document
	 *
	 * @var string
	 */
	public $document;

	/**
	 * Current tag
	 *
	 * @var string
	 */
	public $current;

	/**
	 * Parent tag
	 *
	 * @var string
	 */
	public $parent;

	/**
	 * Parents
	 *
	 * @var array
	 */
	public $parents;

	/**
	 * Last opened tag
	 *
	 * @var string
	 */
	public $last_opened_tag;

	/**
	 * Constructor
	 *
	 * @param     string    Data
	 * @return    void
	 */
	public function __construct ( $data=null )
	{
		$this->parser = xml_parser_create();

		xml_parser_set_option( $this->parser, XML_OPTION_CASE_FOLDING, 0 );
		xml_set_object( $this->parser, $this );
		xml_set_element_handler( $this->parser, "_rpc_open", "_rpc_close" );
		xml_set_character_data_handler( $this->parser, "_rpc_data" );
	}

	/**
	 * Destructor
	 *
	 * @return    void
	 */
	public function __destruct ()
	{
		xml_parser_free( $this->parser );
	}

	/**
	 * Parse the XML data
	 *
	 * @param     string    Data
	 * @return    string    Parsed data
	 */
	public function parse ( $data )
	{
		//---------
		// INIT
		//---------

		$this->document = array();
		$this->parent = &$this->document;
		$this->parents = array();
		$this->last_opened_tag = null;

		//---------
		// Parse
		//---------

		xml_parse( $this->parser, $data );

		//-----------------
		// Return...
		//-----------------
		$tmp = $this->document;
		return $tmp;
	}

	/**
	 * Open handler for XML object
	 *
	 * @param     object    Parser reference
	 * @param     string    Tag
	 * @param     array     Attributes
	 * @return    void
	 */
	private function _rpc_open ( $parser, $tag, $attributes )
	{
		//---------
		// INIT
		//---------

		$this->data = "";
		$this->last_opened_tag = $tag;

		if ( array_key_exists( $tag, $this->parent ) )
		{
			if ( is_array( $this->parent[$tag] ) and array_key_exists( 0, $this->parent[$tag] ) )
			{
				$key = is_array( $this->parent[$tag] ) ? count( array_filter( array_keys( $this->parent[$tag] ), 'is_numeric' ) ) : 0;
			}
			else
			{
				$temp = &$this->parent[$tag];
				unset( $this->parent[$tag] );

				$this->parent[$tag][0] = &$temp;

				if ( array_key_exists( $tag ." attr", $this->parent ) )
				{
					$temp = &$this->parent[ $tag ." attr" ];
					unset( $this->parent[ $tag ." attr" ] );
					$this->parent[$tag]["0 attr"] = &$temp;
				}

				$key = 1;
			}

			$this->parent = &$this->parent[$tag];
		}
		else
		{
			$key = $tag;
		}

		if ( $attributes )
		{
			$this->parent[ $key ." attr" ] = $attributes;
		}

		$this->parent[$key] = array();
		$this->parent = &$this->parent[$key];

		$this->_array_unshift_reference( $this->parents, $this->parent );
	}

	/**
	 * Array unshift wrapper
	 *
	 * @param     array     Array
	 * @param     string    Value
	 * @return    array     New array
	 */
	private function _array_unshift_reference ( &$array, &$value )
	{
	   $return = array_unshift( $array, '' );
	   $array[0] =& $value;
	   return $return;
	}

	/**
	 * XML data handler
	 *
	 * @param     object    Parser reference
	 * @param     string    Data
	 * @return    void
	 */
	private function _rpc_data ( $parser, $data )
	{
		if ( $this->last_opened_tag != null )
		{
			$this->data .= $data;
		}
	}

	/**
	 * XML close handler
	 *
	 * @param     object    Parser reference
	 * @param     string    Tag
	 * @return    void
	 */
	private function _rpc_close ( $parser, $tag )
	{
		if ( $this->last_opened_tag == $tag )
		{
			$this->parent = $this->data;
			$this->last_opened_tag = null;
		}

		array_shift( $this->parents );

		$this->parent = &$this->parents[0];
	}
}