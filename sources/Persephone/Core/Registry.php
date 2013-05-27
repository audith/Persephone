<?php

namespace Persephone\Core;

if ( !defined( "INIT_DONE" ) )
{
	die( "Improper access! Exiting now..." );
}

/**
 * Registry class
 *
 * @package  Audith CMS codename Persephone
 * @author   Shahriyar Imanov <shehi@imanov.name>
 * @version  1.0
 */
final class Registry
{
	/**
	 * Self-Instance [Singleton implementation]
	 *
	 * @var Registry
	 */
	private static $_instance;

	/**
	 * Registered Classes
	 *
	 * @var array
	 */
	public $classes = array();

	/**
	 * @var array
	 */
	public static $config = array();

	/**
	 * Instance of DB class
	 *
	 * @var Database
	 */
	public $Db;

	/**
	 * @var Display
	 */
	public $Display;

	/**
	 * @var Input
	 */
	public $Input;

	/**
	 * @var \Zend\Log\Logger
	 */
	public static $logger;

	/**
	 * Current member data
	 *
	 * @var array
	 */
	public $member = array();

	/**
	 * @var Modules
	 */
	public $Modules;

	/**
	 * Output buffering - Is compression turned on?
	 *
	 * @var boolean
	 */
	public static $ob_compression = false;

	/**
	 * Output buffering - Is the topmost buffer active?
	 *
	 * @var boolean
	 */
	public static $ob_status = false;

	/**
	 * FLAG: Don't output anything from now on - on shutdown phase, output-buffer is ob_end_clean()'ed...
	 *
	 * @var boolean
	 */
	public $ob_no_output = false;

	/**
	 * @var Session
	 */
	public $Session;

	/**
	 * @var float
	 */
	static public $starttime;

	/**
	 * @var float
	 */
	static public $totaltime;

	/**
	 * @var array
	 */
	static public $time_delta = array();


	/**
	 * Constructor - [Singleton implementation - cannot be accessed directly]
	 **/
	private function __construct ()
	{
		//------------------------------------
		// Start Exception handlers & Debug
		//------------------------------------

		# First log
		static::logger__do_log( __METHOD__ . " says: Script start-up." );

		# Start timer
		static::$starttime = static::debug__timer_start();

		//--------------------
		// Read config file
		//--------------------

		# Initial build of configuration information
		if ( static::configuration() === false )
		{
			header("HTTP/1.1 500");
			exit;
		}

		//---------------------------
		// Instantiate Db Driver
		//---------------------------

		$_db_class_name = '\Persephone\Core\Database\Drivers\\' . ucwords( static::$config[ 'sql' ][ 'driver' ] );
		$this->Db = new $_db_class_name( $this );

		/*
		//------------------------
		// Instantiate Input class
		//------------------------

		$this->Input = new Input( $this );

		//----------------------------------------
		// Instantiate Caching Storage Management
		//----------------------------------------

		$this->Cache = new Cache( $this );

		//------------------
		// Cache::init()
		//------------------

		if ( isset( $this->Cache ) and is_object( $this->Cache ) )
		{
			$this->Cache->init();
		}

		//-----------------------------
		// Instantiate Session class
		//-----------------------------

		$this->Session = new Session( $this );

		//-------------------------------------------------
		// Fetch additional configuration settings...
		//-------------------------------------------------

		foreach ( $this->Cache->cache[ 'settings' ][ 'by_key' ] as $_k => $_v )
		{
			foreach ( $_v as $__k => $__v )
			{
				if ( !is_array( $__v ) )
				{
					continue;
				}
				$_conf_value = null;
				switch ( $__v[ 'conf_type' ] )
				{
					case 'yes_no':
						if ( $__v[ 'conf_value' ] == '1' )
						{
							(int) $_conf_value = 1;
						}
						elseif ( $__v[ 'conf_value' ] == '0' )
						{
							(int) $_conf_value = 0;
						}
						else
						{
							(int) $_conf_value = $__v[ 'conf_default' ];
						}
						break;

					case 'input':
					case 'textarea':
					case 'dropdown':
						if ( strval( $__v[ 'conf_value' ] ) == '' )
						{
							(string) $_conf_value = $__v[ 'conf_default' ];
						}
						else
						{
							(string) $_conf_value = $__v[ 'conf_value' ];
						}
						break;
				}
				$_config[ $_k ][ $__k ] = $_conf_value;
			}
		}

		static::$config = array_merge( static::$config, $_config );

		//------------------
		// URI parsing
		//------------------

		static::parse_request_uri();
		static::logger__do_log( __METHOD__ . " says: Request-path = '" . static::$config[ 'page' ][ 'request' ][ 'path' ] . "'", "INFO" );

		//-------------------------------------------------------
		// Output buffering with GZip compression
		// NOTE: Must come before Session::init(),
		// or we won't be able to use Session-to-URL-Rewriter
		//-------------------------------------------------------

		$this->ob_start();

		//--------------------------------
		// Instantiate Module class
		//--------------------------------

		$this->Modules = new Modules( $this );

		//--------------------
		// Session::init()
		//--------------------

		$this->Session->init();

		//------------------------------
		// Instantiate Display class
		//------------------------------

		$this->Display = new Display( $this );

		//------------------
		// Module::init()
		//------------------

		if ( isset( $this->Modules ) and is_object( $this->Modules ) )
		{
			$this->Modules->init();

			# Load cache required by the working module
			if ( is_array( $this->Modules->cur_module[ 'm_cache_array' ] ) and count( $this->Modules->cur_module[ 'm_cache_array' ] ) )
			{
				$this->Cache->cache__do_load( $this->Modules->cur_module[ 'm_cache_array' ] );
			}
		}


		//-----------------------------
		// Registering Destructors
		//-----------------------------

		# Destroy in reverse order of creation, to avoid problems of dependency-failures, during shutdown operations
		register_shutdown_function( array( $this->Display, "_my_destruct" ) );
		register_shutdown_function( array( $this->Modules, "_my_destruct" ) );
		register_shutdown_function( array( $this->Session, "_my_destruct" ) );
		register_shutdown_function( array( $this->Input, "_my_destruct" ) );
		register_shutdown_function( array( $this->Cache, "_my_destruct" ) );
		register_shutdown_function( array( $this->Db, "_my_destruct" ) );

		*/
	}


	/**
	 * __clone() not permitted for Singleton-implementation
	 */
	private function __clone ()
	{
	}


	/**
	 * Registry Init [Singleton implementation - can have ONLY one instance of class throughout the system]
	 *
	 * @return    object    Registry Object
	 */
	public static function init ()
	{
		if ( is_null( static::$_instance ) )
		{
			static::$_instance = new static;
		}

		return static::$_instance;
	}


	/**
	 * Destructor
	 **/
	public function __destruct ()
	{
		# Debug: Stop timer
		static::$totaltime = static::debug__timer_stop();

		# Flush only if ZLib-Output-Handler is OFF
		if ( static::$ob_status and !static::$ob_compression )
		{
			if ( $this->ob_no_output )
			{
				ob_end_clean();  // ob_no_output flag has engaged, we don't need any buffer, thus sending its content to abyss :P
			}
			else
			{
				ob_end_flush();  // we still need the content of buffer, thus flushing it to client-side...
			}
		}
	}


	/**
	 * Parses and builds configuration information
	 *
	 * @return  boolean
	 **/
	private static function configuration ()
	{
		# Read configuration file
		$_path_to_config_file = PATH_ROOT_WEB . "/config.php";
		try
		{
			if ( is_file( $_path_to_config_file ) and is_readable( $_path_to_config_file ) )
			{
				static::$config = require_once( $_path_to_config_file );
			}
			else
			{
				throw new \Persephone\Exception( __METHOD__ . " says: Could not open configuration file (<strong>" . $_path_to_config_file . "</strong>): File not found or not readable!" );
			}
		}
		catch ( \Persephone\Exception $e )
		{
			return false;
		}

		# Loaded PHP extensions
		static::$config[ 'runtime' ][ 'loaded_extensions' ] = get_loaded_extensions();

		return true;
	}


	/**
	 * Starts timer
	 *
	 * @return      int
	 * @todo                To be migrated to Registry\Debug class
	 */
	public static function debug__timer_start ()
	{
		$_starttime = microtime();
		$_starttime = explode( ' ', $_starttime );
		$_starttime = $_starttime[ 1 ] + $_starttime[ 0 ];

		return $_starttime;
	}


	/**
	 * Stops timer
	 *
	 * @param   integer   $starttime    Starting-time, t(0)
	 *
	 * @return  array                   Array containing Stop-time and Time-delta
	 * @todo                            To be migrated to Registry\Debug class
	 */
	public static function debug__timer_stop ( $starttime = 0 )
	{
		if ( !$starttime )
		{
			$starttime = static::$starttime;
		}

		$mtime    = microtime();
		$mtime    = explode( ' ', $mtime );
		$mtime    = $mtime[ 1 ] + $mtime[ 0 ];
		$stoptime = $mtime;

		$return[ 'stoptime' ] = $stoptime;
		$return[ 'delta' ]    = round( ( $stoptime - $starttime ), 5 );

		return $return;
	}


	/**
	 * HTTP redirect
	 *
	 * @param   string     $url                     URL to load
	 * @param   int        $http_status_code        Status code to deploy with, defaults to 302
	 *
	 * @return  void
	 * @todo                                        To be migrated to Controller class
	 */
	public static function http_redirect ( $url, $http_status_code = 302 )
	{
		# Ensure &amp;s are taken care of

		$url = str_replace( "&amp;", "&", $url );

		if ( static::$config[ 'serverenvironment' ][ 'header_redirect' ] == 'refresh' )
		{
			# @todo : Do we need session id prepended to redir url in case session.use_trans_sid is in effect ?!

			header( "Refresh: 0;url=" . $url );
			exit();
		}
		elseif ( static::$config[ 'serverenvironment' ][ 'header_redirect' ] == 'html' )
		{
			## @todo : Do we need session id prepended to redir url in case session.use_trans_sid is in effect ?!

			$url = str_replace( '&amp;', '&', $url );
			echo( "<html><head><meta http-equiv='refresh' content='0; url=$url'></head><body></body></html>" );
			exit();
		}
		else
		{
			//--------------------------------------------------------------
			// Is Redirect URI absolute? HTTP/1.1 requires it to be so!!!
			//--------------------------------------------------------------

			if ( !preg_match( '#^https?:\/\/#', $url ) )
			{
				trigger_error( "Redirect URI is not an absolute URL (as required by HTTP/1.1 specs for Location header)!", E_USER_ERROR );
			}

			//----------------------------------------------
			// Redirect with appropriate status code
			//----------------------------------------------

			$http_status_code
				? header( "Location: " . $url, true, $http_status_code )
				: header( "Location: " . $url );
			exit();
		}
	}


	/**
	 * My parse_url() that parses current REQUEST_URI; additionally, it makes sure that working domain is valid - redirects to valid one otherwise
	 *
	 * @return   mixed   Array of parsed URL or FALSE on failure
	 * @todo             To be migrated to Controller class
	 */
	private static function parse_request_uri ()
	{
		$_url = ( empty( $_SERVER[ 'HTTPS' ] ) or $_SERVER[ 'HTTPS' ] == 'off' )
			? "http://"
			: "https://";
		$_url .= $_SERVER[ 'HTTP_HOST' ]
			? $_SERVER[ 'HTTP_HOST' ]
			: $_SERVER[ 'SERVER_NAME' ];
		$_url .= $_SERVER[ 'REQUEST_URI' ];

		$_parsed_url                    = parse_url( $_url );
		$_parsed_url[ 'path' ]          = trim( $_parsed_url[ 'path' ], '\/' );
		$_parsed_url[ 'path_exploded' ] = explode( "/", $_parsed_url[ 'path' ] );
		$_parsed_url[ 'path' ]          = "/" . $_parsed_url[ 'path' ];

		$_parsed_url[ 'request_uri' ] = $_parsed_url[ 'scheme' ] . "://" .
		                                ( ( isset( $_parsed_url[ 'user' ] ) and isset( $_parsed_url[ 'pass' ] ) )
			                                ? $_parsed_url[ 'user' ] . ":" . $_parsed_url[ 'pass' ] . "@"
			                                : "" ) . static::$config[ 'url' ][ 'hostname' ][ $_parsed_url[ 'scheme' ] ] . $_parsed_url[ 'path' ] .
		                                ( $_parsed_url[ 'query' ]
			                                ? "?" . $_parsed_url[ 'query' ]
			                                : "" );

		if ( $_parsed_url[ 'host' ] != static::$config[ 'url' ][ 'hostname' ][ $_parsed_url[ 'scheme' ] ] )
		{
			static::logger__do_log( "Registry: Request redirection to location: " . $_parsed_url[ 'request_uri' ], "INFO" );
			// \Persephone\Core\Registry::http_redirect( $_parsed_url['request_uri'] , 301 );
		}

		static::$config[ 'page' ][ 'request' ] = $_parsed_url;
	}


	/**
	 * Local Logger
	 *
	 * @param     $message      string    Message to log
	 * @param     $priority     string    Priority level [ERROR|WARNING|NOTICE|INFO|DEBUG]
	 *
	 * @return                  boolean   TRUE on success, FALSE otherwise
	 */
	public static function logger__do_log ( $message, $priority = "INFO" )
	{
		//-----------------------
		// Priority method map
		//-----------------------

		$_method_map = array(
			'EMERG'    => \Zend\Log\Logger::EMERG,
			'ALERT'    => \Zend\Log\Logger::ALERT,
			'CRIT'     => \Zend\Log\Logger::CRIT,
			'CRITICAL' => \Zend\Log\Logger::CRIT,
			'ERR'      => \Zend\Log\Logger::ERR,
			'ERROR'    => \Zend\Log\Logger::ERR,
			'WARN'     => \Zend\Log\Logger::WARN,
			'WARNING'  => \Zend\Log\Logger::WARN,
			'NOTICE'   => \Zend\Log\Logger::NOTICE,
			'INFO'     => \Zend\Log\Logger::INFO,
			'DEBUG'    => \Zend\Log\Logger::DEBUG
		);

		if ( !array_key_exists( $priority, $_method_map ) )
		{
			return false;
		}

		//------------------------------------------------------
		// Instantiate Zend_Log object if not done so already
		//------------------------------------------------------

		if ( !is_object( static::$logger ) )
		{
			static::$logger = new \Zend\Log\Logger();

			//-----------------
			// Set "Writers"
			//-----------------

			# stdout
			if ( ini_get( "display_errors" ) != '0' )
			{
				static::$logger->addWriter( $_writer__stdout = new \Zend\Log\Writer\Stream( 'php://output' ) );
				if ( !IN_DEV )
				{
					$_filter__stdout = new \Zend\Log\Filter\Priority( \Zend\Log\Logger::ERR );
					$_writer__stdout->addFilter( $_filter__stdout );
				}
			}

			# error_log
			if ( file_exists( $log_file = ini_get( "error_log" ) ) and is_file( $log_file ) and is_writable( $log_file ) )
			{
				static::$logger->addWriter( $_writer__logfile = new \Zend\Log\Writer\Stream( $log_file ) );
				if ( !IN_DEV )
				{
					$_filter__logfile = new \Zend\Log\Filter\Priority( \Zend\Log\Logger::ERR );
					$_writer__logfile->addFilter( $_filter__logfile );
				}
			}

			# Fire-PHP
			/*
			if ( IN_DEV )
			{
				require_once PATH_LIBS . "/FirePHPCore/fb.php";
				static::$logger->addWriter( new \Zend\Log\Writer\FirePhp );
			}
			*/
			# Registering logger object as our main PHP error-logger
			#\Zend\Log\Logger::registerErrorHandler( static::$logger );
			#\Zend\Log\Logger::registerExceptionHandler( static::$logger );
		}

		//---------------
		// Continue...
		//---------------

		try
		{
			# IN_DEV flag required for non-ERROR logging
			if ( $_method_map[ $priority ] != \Zend\Log\Logger::ERR and !IN_DEV )
			{
				return true;
			}

			# Log event
			static::$logger->log(
				( $_method_map[ $priority ]
					? $_method_map[ $priority ]
					: \Zend\Log\Logger::DEBUG ),
				$message . "\n"
			);
		}
		catch ( \Persephone\Exception $e )
		{
			return false;
		}

		return true;
	}


	/**
	 * Turn on output buffering
	 */
	public function ob_start ()
	{
		if ( !static::$config[ 'serverenvironment' ][ 'disable_gzip' ] and in_array( "zlib", static::$config[ 'runtime' ][ 'loaded_extensions' ] ) )
		{
			ini_set( "zlib.output_handler", "" ) and ini_set( "zlib.output_compression", "1" ) and static::$ob_compression = true;
			( ob_start() === true ) and static::$ob_status = true;
			( static::$ob_compression === true ) and static::logger__do_log( __METHOD__ . " says: Failed to set output-buffer compression!" );
			( static::$ob_status === true ) and static::logger__do_log( __METHOD__ . " says: Failed to activate output-buffering!" );
		}
		else
		{
			ini_set( "zlib.output_compression", "0" ) and static::$ob_compression = false;
			( ob_start() === true ) and $this->ob_status = true;
			( static::$ob_compression === false ) and static::logger__do_log( __METHOD__ . " says: Failed to set output-buffer compression!" );
			( static::$ob_status === true ) and static::logger__do_log( __METHOD__ . " says: Failed to activate output-buffering!" );
		}

		static::logger__do_log( __METHOD__ . " says: Output-buffering initialized!" );
	}
}
