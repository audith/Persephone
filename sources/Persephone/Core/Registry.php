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
	public $config = array();

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
	public $ob_compression = false;

	/**
	 * Output buffering - Is the topmost buffer active?
	 *
	 * @var boolean
	 */
	public $ob_status = false;

	/**
	 * FLAG: Don't output anything from now on (on shutdown, OB is clean-end'ed)
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
		self::logger__do_log( __METHOD__ . " says: Script start-up." );

		# Start timer
		self::$starttime = self::debug__timer_start();

		//--------------------
		// Read config file
		//--------------------

		# Initial build of configuration information
		if ( $this->configuration() === false )
		{
			header("HTTP/1.1 500");
			exit;
		}

		//---------------------------
		// Instantiate Db Driver
		//---------------------------

		$_db_class_name = '\Persephone\Core\Database\Drivers\\' . ucwords( $this->config[ 'sql' ][ 'driver' ] );
		$this->Db = new $_db_class_name( $this );
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

		$this->config = array_merge( $this->config, $_config );

		//------------------
		// URI parsing
		//------------------

		$this->parse_request_uri();
		self::logger__do_log( __METHOD__ . " says: Request-path = '" . $this->config[ 'page' ][ 'request' ][ 'path' ] . "'", "INFO" );

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
		if ( is_null( self::$_instance ) )
		{
			self::$_instance = new self;
		}

		return self::$_instance;
	}


	/**
	 * Destructor
	 **/
	public function __destruct ()
	{
		# Debug: Stop timer
		self::$totaltime = self::debug__timer_stop();

		# Performance log
		$this->logger__do_performance_log();

		# Flush only if ZLib-Output-Handler is OFF
		if ( $this->ob_status and !$this->ob_compression )
		{
			if ( $this->ob_no_output )
			{
				ob_end_clean();
			}
			else
			{
				ob_end_flush();
			}
		}
	}


	/**
	 * Parses and builds configuration information
	 *
	 * @return  boolean
	 **/
	private function configuration ()
	{
		# Read configuration file
		$_path_to_config_file = PATH_ROOT_WEB . "/config.php";
		try
		{
			if ( is_file( $_path_to_config_file ) and is_readable( $_path_to_config_file ) )
			{
				$this->config = require_once( $_path_to_config_file );
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
		$this->config[ 'runtime' ][ 'loaded_extensions' ] = get_loaded_extensions();

		return true;
	}


	/**
	 * Starts timer
	 *
	 * @return integer
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
	 * @param   integer   $starttime    Starting-time, T-0
	 *
	 * @return  array                   Array containing Stop-time and Time-delta
	 */
	public static function debug__timer_stop ( $starttime = 0 )
	{
		if ( !$starttime )
		{
			$starttime = self::$starttime;
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
	 */
	public function http_redirect ( $url, $http_status_code = 302 )
	{
		# Ensure &amp;s are taken care of

		$url = str_replace( "&amp;", "&", $url );

		if ( $this->config[ 'serverenvironment' ][ 'header_redirect' ] == 'refresh' )
		{
			# @todo : Do we need session id prepended to redir url in case session.use_trans_sid is in effect ?!

			header( "Refresh: 0;url=" . $url );
			exit();
		}
		elseif ( $this->config[ 'serverenvironment' ][ 'header_redirect' ] == 'html' )
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

			// @todo : Do we need session id prepended to redir url in case session.use_trans_sid is in effect ?!

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
	 */
	private function parse_request_uri ()
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
			                                : "" ) . $this->config[ 'url' ][ 'hostname' ][ $_parsed_url[ 'scheme' ] ] . $_parsed_url[ 'path' ] .
		                                ( $_parsed_url[ 'query' ]
			                                ? "?" . $_parsed_url[ 'query' ]
			                                : "" );

		# @todo Redirect to default domain-name if request was sent to different domain
		if ( $_parsed_url[ 'host' ] != $this->config[ 'url' ][ 'hostname' ][ $_parsed_url[ 'scheme' ] ] )
		{
			self::logger__do_log( "Registry: Request redirection to location: " . $_parsed_url[ 'request_uri' ], "INFO" );
			// $this->Registry->http_redirect( $_parsed_url['request_uri'] , 301 );
		}

		$this->config[ 'page' ][ 'request' ] = $_parsed_url;
	}


	/**
	 * Local Logger
	 *
	 * @param     $message      string    Message to log
	 * @param     $priority     string    Priority level [ERROR|WARNING|NOTICE|INFO|DEBUG]
	 *
	 * @return                  boolean   TRUE on success, FALSE otherwise
	 */
	static public function logger__do_log ( $message, $priority = "INFO" )
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

		if ( !is_object( self::$logger ) )
		{
			self::$logger = new \Zend\Log\Logger();

			//-----------------
			// Set "Writers"
			//-----------------

			# stdout
			if ( ini_get( "display_errors" ) != '0' )
			{
				self::$logger->addWriter( $_writer__stdout = new \Zend\Log\Writer\Stream( 'php://output' ) );
				if ( !IN_DEV )
				{
					$_filter__stdout = new \Zend\Log\Filter\Priority( \Zend\Log\Logger::ERR );
					$_writer__stdout->addFilter( $_filter__stdout );
				}
			}

			# error_log
			if ( file_exists( $log_file = ini_get( "error_log" ) ) and is_file( $log_file ) and is_writable( $log_file ) )
			{
				self::$logger->addWriter( $_writer__logfile = new \Zend\Log\Writer\Stream( $log_file ) );
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
				self::$logger->addWriter( new \Zend\Log\Writer\FirePhp );
			}
			*/
			# Registering logger object as our main PHP error-logger
			#\Zend\Log\Logger::registerErrorHandler( self::$logger );
			#\Zend\Log\Logger::registerExceptionHandler( self::$logger );
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
			self::$logger->log(
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
	 * Debug : Logs the memory usage and timedelta
	 *
	 * @param    $location      string     Marker
	 * @param    $time_only     boolean    Whether to log timedelta ONLY, or not - defaults to FALSE
	 *
	 * @return                  void
	 */
	static public function logger__do_performance_log ( $location = "", $time_only = false )
	{
		if ( IN_DEV )
		{
			$_log_message = "";
			$location     = ( $location )
				? " - " . $location
				: "";
			if ( !$time_only )
			{
				$_log_message .= "\n\tMemory Usage" . $location . ": " . ( memory_get_usage( true ) - MEMORY_START ) . " bytes";
			}
			$_timer = self::debug__timer_stop();
			$_log_message .= "\n\tTimedelta" . $location . ": " . $_timer[ 'delta' ] . " secs";
			self::logger__do_log( $_log_message, "INFO" );
		}
	}


	/**
	 * Turn on output buffering
	 */
	public function ob_start ()
	{
		if ( !$this->config[ 'serverenvironment' ][ 'disable_gzip' ] and in_array( "zlib", $this->config[ 'runtime' ][ 'loaded_extensions' ] ) )
		{
			ini_set( "zlib.output_handler", "" );
			ini_set( "zlib.output_compression", "1" );
			$this->ob_compression = true;
			if ( ob_start( /* "ob_gzhandler" */ ) )
			{
				$this->ob_status = true;
				// ini_set( "zlib.output_compression", "0" );
			}
		}
		else
		{
			ini_set( "zlib.output_handler", "" );
			$this->ob_compression = false;
			if ( ob_start() )
			{
				$this->ob_status = true;
			}
		}

		# Performance log
		$this->logger__do_performance_log( "OB Start" );
	}
}
