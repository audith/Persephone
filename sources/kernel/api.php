<?php

if ( !defined( "INIT_DONE" ) )
{
	print "Improper access! Exiting now...";
	exit();
}

/**
 * API class
 *
 * @package  Audith CMS codename Persephone
 * @author   Shahriyar Imanov <shehi@imanov.name>
 * @version  1.0
**/
final class API
{
	/**
	 * Self-Instance [Singleton implementation]
	 * @var object
	 */
	private static $_instance;

	/**
	 * Configuration data
	 * @var array
	 */
	public $API_Server;

	/**
	 * Registered Classes
	 * @var array
	 */
	public $classes = array();

	/**
	 * Configuration data
	 * @var array
	 */
	public $config  = array();

	/**
	 * Instance of DB class
	 * @var object
	 */
	public $Db;

	/**
	 * Instance of Display class
	 * @var object
	 */
	public $Display;

	/**
	 * Instance of Input class
	 * @var object
	 */
	public $Input;

	/**
	 * Instance of IPS Converge
	 * @var object
	 */
	public $IPS_Converge;

	/**
	 * Zend_Log object
	 * @var object
	 */
	public $logger;

	/**
	 * Current member data
	 * @var array
	 */
	public $member = array();

	/**
	 * Instance of Modules class
	 * @var object
	 */
	public $Modules;

	/**
	 * Output buffering - Is compression turned on?
	 * @var boolean
	 */
	public $ob_compression = false;

	/**
	 * Output buffering - Is the topmost buffer active?
	 * @var boolean
	 */
	public $ob_status = false;

	/**
	 * FLAG: Don't output anything from now on (on shutdown, OB is clean-end'ed)
	 * @var boolean
	 */
	public $ob_no_output = false;

	/**
	 * Instance of Session class
	 * @var object
	 */
	public $Session;

	/**
	 * Debug variables
	 * @var mixed
	 */
	public $starttime;
	public $totaltime;
	public $time_delta = array();


	/**
	 * Constructor - [Singleton implementation - cannot be accessed directly]
	 *
	 * @param    array    List of disabled classes, which don't need to be initialized (NOTE: Db and Input classes are mandatory, meaning, they cannot be disabled).
	**/
	private function __construct ( $disabled_classes )
	{
		//----------------
		// Start Debug
		//----------------

		# Set Exception handler
		set_exception_handler( array( $this, "exception_handler" ) );

		# Start timer
		$this->starttime = $this->debug__timer_start();

		# Zend_Log
		if ( ! class_exists( "Zend_Log" ) )
		{
			$this->loader( "Zend_Log", false );
			$this->loader( "Zend_Log_Writer_Stream", false );
		}

		# Performance log
		$this->logger__do_performance_log( "Zend_Log Init" );

		//--------------------
		// Read config file
		//--------------------

		# Loaded PHP extensions
		$this->config['runtime']['loaded_extensions'] = get_loaded_extensions();

		# Fetch primary configuration settings from config-file
		$this->config = array_merge( $this->config__file_read(), $this->config );

		//---------------------------
		// Instantiate Db Driver
		//---------------------------

		$this->Db = $this->loader( "Db__Drivers__" . ucwords( $this->config['sql']['driver'] ) );

		# Performance log
		$this->logger__do_performance_log( "Db::__construct()" );

		//------------------------
		// Instantiate Input class
		//------------------------

		$this->Input = $this->loader( "Input" );

		# Performance log
		$this->logger__do_performance_log( "Input::__construct()" );

		//----------------------------------------
		// Instantiate Caching Storage Management
		//----------------------------------------

		if ( !in_array( "Cache", $disabled_classes ) )
		{
			$this->Cache = $this->loader( "Cache" );

			# Performance log
			$this->logger__do_performance_log( "Cache::__construct()" );
		}

		//------------------
		// Cache::init()
		//------------------

		if ( isset( $this->Cache ) and is_object( $this->Cache ) )
		{
			$this->Cache->init();

			# Performance log
			$this->logger__do_performance_log( "Cache::init()" );
		}

		//-----------------------------
		// Instantiate Session class
		//-----------------------------

		if ( ! in_array( "Session", $disabled_classes ) )
		{
			$this->Session = $this->loader( "Session" );

			# Performance log
			$this->logger__do_performance_log( "Session::__construct()" );
		}

		//-------------------------------------------------
		// Fetch additional configuration settings...
		//-------------------------------------------------

		foreach ( $this->Cache->cache['settings']['by_key'] as $_k=>$_v )
		{
			foreach ( $_v as $__k=>$__v )
			{
				if ( ! is_array( $__v ) )
				{
					continue;
				}
				switch ( $__v['conf_type'] )
				{
					case 'yes_no':
						if ( $__v['conf_value'] == '1' )
						{
							(int) $_conf_value = 1;
						}
						elseif ( $__v['conf_value'] == '0' )
						{
							(int) $_conf_value = 0;
						}
						else
						{
							(int) $_conf_value = $__v['conf_default'];
						}
						break;

					case 'input':
					case 'textarea':
					case 'dropdown':
						if ( strval( $__v['conf_value'] ) == '' )
						{
							(string) $_conf_value = $__v['conf_default'];
						}
						else
						{
							(string) $_conf_value = $__v['conf_value'];
						}
						break;
				}
				$_config[ $_k ][ $__k ] = $_conf_value;
			}
		}

		$this->config = array_merge( $this->config, $_config );

		# Performance log
		$this->logger__do_performance_log( "API::config [final]" );

		//------------------
		// INPUT::init()
		//------------------

		$this->Input->init();

		# Performance log
		$this->logger__do_performance_log( "Input::init()" );

		//-------------------------------------------------------
		// Output buffering with GZip compression
		// NOTE: Must come before Session::init(),
		// or we won't be able to use Session-to-URL-Rewriter
		//-------------------------------------------------------

		$this->ob_start();

		//--------------------------------
		// Instantiate Module class
		//--------------------------------

		if ( ! in_array( "Modules", $disabled_classes ) )
		{
			$this->Modules = $this->loader( "Modules" );

			# Performance log
			$this->logger__do_performance_log( "Modules::__construct()" );
		}

		//--------------------
		// Session::init()
		//--------------------

		if ( isset( $this->Session ) and is_object( $this->Session ) )
		{
			$this->Session->init();

			# Performance log
			$this->logger__do_performance_log( "Session::init()" );
		}

		//------------------------------
		// Instantiate Display class
		//------------------------------

		if ( ! in_array( "Display", $disabled_classes ) )
		{
			$this->Display = $this->loader( "Display" );

			# Performance log
			$this->logger__do_performance_log( "Display::__construct()" );
		}

		//------------------
		// Module::init()
		//------------------

		if ( isset( $this->Modules ) and is_object( $this->Modules ) )
		{
			$this->Modules->init();

			# Load cache required by the working module
			if ( is_array( $this->Modules->cur_module['m_cache_array'] ) and count( $this->Modules->cur_module['m_cache_array'] ) )
			{
				$this->Cache->cache__do_load( $this->Modules->cur_module['m_cache_array'] );
			}

			# Performance log
			$this->logger__do_performance_log( "Modules::init()" );
		}

		//------------------------------
		// Instantiate Converge class
		//------------------------------

		if ( ! in_array( "Ips_Converge", $disabled_classes ) and $this->config['ipconverge']['ipconverge_enabled'] )
		{
			$this->IPS_Converge = $this->loader( "Ips_Converge" );

			# Performance log
			$this->logger__do_performance_log( "Converge::__construct()" );
		}

		//-----------------------------
		// Registering Destructors
		//-----------------------------

		# Destroy in reverse order of creation, to avoid problems of dependency-failures, during shutdown operations
		register_shutdown_function( array( $this->Display, "_my_destruct" ) );
		register_shutdown_function( array( $this->Modules, "_my_destruct" ) );
		register_shutdown_function( array( $this->Session, "_my_destruct" ) );
		register_shutdown_function( array( $this->Input  , "_my_destruct" ) );
		register_shutdown_function( array( $this->Cache  , "_my_destruct" ) );
		register_shutdown_function( array( $this->Db     , "_my_destruct" ) );

		# Performance log
		$this->logger__do_performance_log( "API::__construct()" );
	}


	/**
	 * __clone() not permitted for Singleton-implementation
	 */
	private function __clone ()
	{
	}


	/**
	 * API Init [Singleton implementation - can have ONLY one instance of class throughout the system]
	 *
	 * @return    object    API Object
	 */
	public static function init ( $disabled_classes = array() )
	{
		if ( self::$_instance === null )
		{
			self::$_instance = new self( $disabled_classes );
		}
		return self::$_instance;
	}


	/**
	 * Destructor
	**/
	public function __destruct()
	{
		# Debug: Stop timer
		$this->totaltime = $this->debug__timer_stop();

		# Performance log
		$this->logger__do_performance_log();

		# Flush only if ZLib-Output-Handler is OFF
		if ( $this->ob_status and ! $this->ob_compression )
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
	 * Retrieves class handles
	 *
	 * @param    string    Key
	 * @param    boolean   Whether to init the class, or not [i.e. load-only]
	 * return    mixed     (object) Retrieved object on SUCCESS, (boolean) FALSE otherwise
	 */
	public function loader ( $key , $_do_init = true )
	{
		//-----------------------------------------------------------------------------------------
		// Do some magic here to retreive common classes without having to initialize them first
		//-----------------------------------------------------------------------------------------

		# Do we have it in our class-registry? If yes, return it...
		if ( $this->_loader__is_loaded( $key ) )
		{
			return $this->classes[ $key ];
		}

		//-----------------------------------------------------------------
		// Class not in our class-Registry, let's register and return it
		//-----------------------------------------------------------------

		# Zend Framework class? If so, use Zend_Loader to load and init the class
		if ( preg_match( '/^Zend_/' , $key ) )
		{
			require_once 'Zend/Loader.php';
			Zend_Loader::loadClass( $key );
			if ( $_do_init )
			{
				return new $key();
			}
			else
			{
				return true;
			}
		}

		# Otherwise, run our own Autoloader
		$_library_location = PATH_SOURCES . "/kernel/" . strtolower( str_replace( "__", '/', $key ) ) . ".php";
		if ( !file_exists( $_library_location ) or !is_file( $_library_location ) )
		{
			throw new Exception( "Couldn't locate library file for class: '" . $_library_location . "'!" );
			return false;
		}
		require_once( $_library_location );

		if ( ! class_exists( $key ) )
		{
			throw new Exception( "Couldn't find class '" . $key . "', although its library was loaded!" );
			return false;
		}

		if ( !$_do_init )
		{
			return true;
		}

		if ( $this->_loader__do_register( $key , new $key( $this ) ) === false )
		{
			throw new Exception( "Could not REGISTER class '" . $key . "'" );
			return false;
		}

		return $this->classes[ $key ];
	}


	/**
	 * Stores class handles to prevent having to re-initialize them constantly
	 *
	 * @param   string    Key
	 * @param   object    Object to store
	 * @return  boolean   TRUE on success, FALSE otherwise
	 */
	private function _loader__do_register ( $key = "", $value = "" )
	{
		if ( ! $key or ! $value )
		{
			throw new Exception( "Missing a key or value" );
			return false;
		}
		else if ( ! is_object( $value ) )
		{
			throw new Exception( "$value is not an object" );
			return false;
		}

		$this->classes[ $key ] = $value;
		return true;
	}


	/**
	 * Checks if a class is loaded
	 *
	 * @param    string     Key
	 * @return   boolean    Loaded or not
	 */
	private function _loader__is_loaded ( $key )
	{
		return ( isset( $this->classes[ $key ] ) and is_object( $this->classes[ $key ] ) ) ? true : false;
	}


	/**
	 * Read configuration file
	 *
	 * @return  array  Primary configuration settings
	**/
	private function config__file_read ()
	{
		# Read configuration file
		$_path_to_config_file = PATH_ROOT_WEB . "/config.php";
		if ( is_file( $_path_to_config_file ) and is_readable( $_path_to_config_file ) )
		{
			return require_once( $_path_to_config_file );
		}
		else
		{
			$this->logger__do_log( "Could not open configuration file (<strong>" . $_path_to_config_file . "</strong>): File not found!" , "ERROR" );
			exit;
		}
	}


	/**
	 * Starts timer
	 *
	 * @return void
	 */
	public function debug__timer_start ()
	{
		$_starttime  = microtime();
		$_starttime  = explode( ' ', $_starttime );
		$_starttime  = $_starttime[1] + $_starttime[0];
		return $_starttime;
	}

	/**
	 * Stops timer
	 *
	 * @param   integer   Starting-time, T-0
	 *
	 * @return  array     Array containing Stop-time and Time-delta
	 */
	public function debug__timer_stop ( $starttime = 0 )
	{
		if ( ! $starttime )
		{
			$starttime = $this->starttime;
		}

		$mtime     = microtime();
		$mtime     = explode( ' ', $mtime );
		$mtime     = $mtime[1] + $mtime[0];
		$stoptime  = $mtime;

		$return['stoptime'] = $stoptime;
		$return['delta'] = round( ( $stoptime - $starttime ), 5 );
		return $return;
	}


	/**
	 * DB Exception handler
	 *
	 * @param  object  Exception object reference
	 *
	 * @return void
	 */
	public function exception_handler ( $e )
	{
		$message = "EXCEPTION: " . ( $_exception_class = get_class( $e ) )
			. "\nMESSAGE: " . $e->getMessage()
			. "\nFILE: " . $e->getFile()
			. "\nLINE: " . $e->getLine()
			. "\nCLASS: " . __CLASS__
			. "\nTRACE:\n" . $e->getTraceAsString()
			. ( ( strpos( $_exception_class, "Zend_Db_" ) === 0 ) ? "\nSQL-QUERY:\n" . var_export( $this->Db->cur_query, true ) : "" )
			. "\n\n";

		$this->logger__do_log( $message, "ERROR" );
	}


	/**
	 * HTTP redirect
	 *
	 * @param   string   URL to load
	 * @param   string   Status code to deploy with, defaults to 302
	 *
	 * @return  void
	**/
	public function http_redirect ( $url, $http_status_code = 302 )
	{
		# Ensure &amp;s are taken care of

		$url = str_replace( "&", "&", $url );

		if ( $this->config['serverenvironment']['header_redirect'] == 'refresh' )
		{
			# @todo : Do we need session id prepended to redir url in case session.use_trans_sid is in effect ?!

			header( "Refresh: 0;url=" . $url );
			exit();
		}
		elseif ( $this->config['serverenvironment']['header_redirect'] == 'html' )
		{
			## @todo : Do we need session id prepended to redir url in case session.use_trans_sid is in effect ?!

			$url = str_replace( '&', '&', str_replace( '&', '&', $url ) );
			echo("<html><head><meta http-equiv='refresh' content='0; url=$url'></head><body></body></html>");
			exit();
		}
		else
		{
			//--------------------------------------------------------------
			// Is Redirect URI absolute? HTTP/1.1 requires it to be so!!!
			//--------------------------------------------------------------

			if ( ! preg_match( '#^https?:\/\/#', $url ) )
			{
				trigger_error( "Redirect URI is not an absolute URL (as required by HTTP/1.1 specs for Location header)!", E_USER_ERROR );
			}

			// @todo : Do we need session id prepended to redir url in case session.use_trans_sid is in effect ?!

			//----------------------------------------------
			// Redirect with appropriate status code
			//----------------------------------------------

			$http_status_code ? header( "Location: " . $url, true, $http_status_code ) : header( "Location: " . $url );
			exit();
		}
	}


	/**
	 * Local Logger [facilitates Zend_Log]
	 *
	 * @param  string  Message to log
	 * @param  string  Priority level [ERROR|WARNING|NOTICE|INFO|DEBUG]
	 *
	 * @return void
	 */
	public function logger__do_log ( $message, $priority = "INFO" )
	{
		//-----------------------
		// Priority method map
		//-----------------------

		$_method_map = array(
				'ERROR'   =>  3,
				'WARNING' =>  4,
				'NOTICE'  =>  5,
				'INFO'    =>  6,
				'DEBUG'   =>  7
			);

		if ( ! array_key_exists( $priority , $_method_map ) )
		{
			return false;
		}

		//------------------------------------------------------
		// Instantiate Zend_Log object if not done so already
		//------------------------------------------------------

		if ( ! is_object( $this->logger ) )
		{
			$this->logger = new Zend_Log();

			//-----------------
			// Set "Writers"
			//-----------------

			# stdout
			if ( ini_get( "display_errors" ) != '0' )
			{
				$this->logger->addWriter( new Zend_Log_Writer_Stream('php://output') );
			}

			# error_log
			$log_file = ini_get( "error_log" );
			if ( file_exists( $log_file ) and is_writable( $log_file ) )
			{
				$this->logger->addWriter( new Zend_Log_Writer_Stream( $log_file ) );
			}

			# FIREPHP
			if ( IN_DEV )
			{
				require_once( "Zend/Log/Writer/Firebug.php" );
				$this->logger->addWriter( new Zend_Log_Writer_Firebug() );

				require_once( "Zend/Controller/Request/Http.php" );
				$request = new Zend_Controller_Request_Http();
				require_once( "Zend/Controller/Response/Http.php" );
				$response = new Zend_Controller_Response_Http();
				require_once( "Zend/Wildfire/Channel/HttpHeaders.php" );
				$channel = Zend_Wildfire_Channel_HttpHeaders::getInstance();
				$channel->setRequest($request);
				$channel->setResponse($response);

				# Start output buffering
				//ob_start();
			}
		}

		//---------------
		// Continue...
		//---------------

		try {
			# IN_DEV flag required for non-ERROR logging
			if ( $_method_map[ $priority ] != 3 and ! IN_DEV )
			{
				return true;
			}

			# Log event
			$this->logger->log( $message, ( intval( $_method_map[ $priority ] ) ? $_method_map[ $priority ] : 7 ) );
			if ( isset( $channel ) and is_object( $channel ) )
			{
				# FIREPHP - Flush log data to browser
				$channel->flush();
				$response->sendHeaders();
			}
		}
		catch ( Zend_Log_Exception $e )
		{
			# Logging failed... "Log" the failure itself :-d
			$message = "<pre>EXCEPTION: " . get_class( $e )
				. "\nMESSAGE: " . $e->getMessage()
				. "\nFILE: " . __FILE__
				. "\nLINE: " . __LINE__
				. "\nCLASS: " . __CLASS__
				. ( IN_DEV ? "\nTRACE:\n" . $e->getTraceAsString() : "" )
				. "\n</pre>";
			trigger_error( $message, E_USER_WARNING );
			return false;
		}
		return true;
	}


	/**
	 * Debug : Logs the memory usage and timedelta
	 *
	 * @param    string     Marker
	 * @param    boolean    Whether to log timedelta ONLY, or not - defaults to FALSE
	 *
	 * @return   void
	 */
	public function logger__do_performance_log ( $location = "" , $time_only = false )
	{
		if ( IN_DEV )
		{
			$_log_message = "";
			$location = ( $location ) ? " - " . $location : "";
			if ( ! $time_only )
			{
				$_log_message .= "\n\tMemory Usage" . $location . ": " . memory_get_usage( true ) . " bytes";
			}
			$_timer = $this->debug__timer_stop();
			$_log_message .= "\n\tTimedelta" . $location . ": " . $_timer['delta'] . " secs";
			$this->logger__do_log( $_log_message , "INFO" );
		}
	}


	/**
	 * Turn on output buffering
	 */
	public function ob_start ()
	{
		if ( ! $this->config['serverenvironment']['disable_gzip'] and in_array( "zlib", $this->config['runtime']['loaded_extensions'] ) )
		{
			ini_set( "zlib.output_handler"     , "1" );
			ini_set( "zlib.output_compression" , "1" );
			$this->ob_compression = true;
			if ( ob_start( /* "ob_gzhandler" */ ) )
			{
				$this->ob_status = true;
				// ini_set( "zlib.output_compression", "0" );
			}
		}
		else
		{
			ini_set( "zlib.output_handler" , "0" );
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