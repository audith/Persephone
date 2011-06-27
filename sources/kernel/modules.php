<?php

if ( ! defined( "INIT_DONE" ) )
{
	print "Improper access! Exiting now...";
	exit();
}

/**
 * MODULES : Main processor
 *
 * @package  Audith CMS codename Persephone
 * @author   Shahriyar Imanov <shehi@imanov.name>
 * @version  1.0
**/
class Modules
{
	/**
	 * API Object Reference
	 * @var Object
	**/
	private $API;

	/**
	 * Array of active modules
	 * @var array
	**/
	public $active_modules  = array();

	/**
	 * Current working module
	 * @var string
	**/
	public $cur_module = array();

	/**
	 * Data-storage (for modules) handlers
	 * @var array
	 */
	public $data_storages = array();

	/**
	 * Default DFT (data-field types)
	 * @var array
	**/
	public $dft_default = array();

	/**
	 * Custom data-field types
	 * @var array
	**/
	public $dft_custom = array();


	/**
	 * Constructor
	 *
	 * @param    object    API Object Reference
	 * @param    array     $params  Incoming data
	 */
	public function __construct ( API $API, $params = "" )
	{
		# Bring-in API object reference
		$this->API = $API;

		# Get active modules from DB
		$this->active_modules = $this->modules__fetch_active();

		# Load requested module
		$this->modules__fetch_working();

		/**
		 * SITE_URL constant
		 */
		if ( ! defined( "SITE_URL" ) )
		{
			$_connection_type = $this->cur_module['m_enforce_ssl'] ? "https" : "http";
			define( "SITE_URL", $_connection_type . "://" . $this->API->config['url']['hostname'][ $_connection_type ] );
		}
	}


	/**
	 * Destructor
	 */
	public function _my_destruct ()
	{
		$this->API->logger__do_log( __CLASS__ . "::__destruct: Destroying class" , "INFO" );
	}


	/**
	 * Init()
	 *
	 * @return void
	 */
	public function init ()
	{
		# Load current module
		$this->modules__do_load( $this->cur_module );
		$this->API->config['page']['running_subroutine'] =& $this->cur_module['running_subroutine'];
	}


	/**
	 * __toString()
	 *
	 * @return    string    Dump of current module
	 */
	public function __toString ()
	{
		// print_r( $this->cur_module );
		return var_export( $this->cur_module, TRUE );
	}


	/**
	 * Build content-based HTTP headers, mostly cache and content-info related (using module-dependency procedures)
	 *
	 * @param   object   REF: Module data object
	 * @return  array    Array of HTTP Headers to be sent
	 */
	public function content__build_http_headers ( &$m )
	{
		# Default values
		$headers = array (
				"content-type"     => "text/html; charset=utf-8",
				// Works with HTTP/1.1 only!
				"cache-control"    => $m['m_enable_caching']
					?
					"public, must-revalidate, max-age=2592000, pre-check=2592000"
					:
					"no-store, no-cache, must-revalidate, max-age=0",
				"pragma"           => $m['m_enable_caching']
					?
					""
					:
					"no-cache",
				"expires"          => $m['m_enable_caching'] ? gmdate( "r", time() + 2592000 ) : gmdate( "r", time() - 86400 ),
				# @todo Localization support
				"content-language" => "en-us"
			);

		# Built-in modules might have their own customized headers
		if ( $m['m_type'] == 'built-in' )
		{
			if ( method_exists( $m['han'], "content__build_http_headers" ) )
			{
				$headers = array_merge( $headers, $m['han']->content__build_http_headers() );
			}
		}

		# Return
		return $headers;
	}


	/**
	 * GET processor - wrapper for data-storage handler
	 *
	 * @param   array    REF: Module info
	 * @return  mixed    (mixed) Fetched content on success; (boolean) FALSE otherwise
	 */
	public function content__do ( &$m )
	{
		//---------------
		// Continue...
		//---------------

		$action = "";
		if ( isset( $this->API->Input->input['do'] ) )
		{
			$action = preg_replace( '#[^a-z_-]#' , "" , $this->API->Input->input['do'] );
		}
		if ( !$action )
		{
			$action = "get";
		}

		//---------------------------------------------------------------
		// RUNNING_SUBROUTINE - SERVICE_MODE : If ='write-only',
		// we don't need to fetch any content. Return NULL...
		//---------------------------------------------------------------

		if ( isset( $m['running_subroutine'] ) and $m['running_subroutine']['s_service_mode'] == 'write-only' and $action == 'get' )
		{
			return null;
		}

		if ( isset( $m['running_subroutine'] ) )
		{
			# Built-in modules do have Handlers, so facilitate those, if we are on built-in module
			if ( isset( $m['han'] ) and is_object( $m['han'] ) )
			{
				return $m['han']->content__do( $m['running_subroutine'] , $action );
			}
			# ... otherwise, for regular modules, process the request...
			else
			{
				# Initialize module data-storage handler
				if ( !isset( $this->data_storages['by_module'][ $m['m_unique_id_clean'] ] ) or !is_object( $this->data_storages['by_module'][ $m['m_unique_id_clean'] ] ) )
				{
					if ( ( $this->data_storages['by_module'][ $m['m_unique_id_clean'] ] = $this->API->classes__do_get( "data_storages__" . $m['m_data_storage'] ) ) === false )
					{
						unset( $this->data_storages['by_module'][ $m['m_unique_id_clean'] ] );
						throw new Exception( "Failed to initialize data-storage library: '" . $m['m_data_storage'] . "'!" );
					}
					$this->API->logger__do_log( "Successfully initialized data-storage library: '" . $m['m_data_storage'] . "'." , "INFO" );
				}

				if ( $action == 'get' )
				{
					return $this->data_storages['by_module'][ $m['m_unique_id_clean'] ]->get__do_process( $m );
				}
				elseif ( $action == 'put' )
				{

				}
				elseif ( $action == 'edit' )
				{

				}
			}
		}

		return false;
	}


	/**
	 * Loads a module
	 *
	 * @param     array    REF: Module info - usually comes from Modules-cache
	 * @return    mixed    Loaded-module instance on success, FALSE otherwise
	 */
	public function modules__do_load ( &$m )
	{
		# Load Handler Class of Built-in-Module

		if ( $m['m_type'] == 'built-in' )
		{
			# Do we have Handler Class file?
			$_han_class_lib = PATH_SOURCES . "/handlers/" . $m['m_handler_class'];
			if ( @is_file( $_han_class_lib ) and @is_readable( $_han_class_lib ) )
			{
				# Fetch ACP Handler
				require_once( $_han_class_lib );

				$m['han'] = new Module_Handler( $this->API );
				$m['m_subroutines'] =& $m['han']->structural_map['m_subroutines'];
			}
			else
			{
				throw new Exception( "Couldn't find/load Module Handler Class Library!" );
			}
		}

		//-------------------
		// Continue ...
		//-------------------

		# No module information?!
		if ( ! count( $m ) )
		{
			return FALSE;
		}

		# Is it admin area?
		if ( ! defined( "ACCESS_TO_AREA" ) )
    	{
    		define( "ACCESS_TO_AREA" , "public" );
    	}

		# Its not a built-in module and it has no DDL configuration?! Its impossible, since you can't ENABLE master module without valid DDL configuration
		if ( $m['m_type'] != 'built-in' and ! $m['m_data_definition'] )
		{
			throw new Exception( "Module (\"" . $m['m_name'] . "\") without DDL config?!" );
		}

		# Module URL prefix
		$_connection_type = $m['m_enforce_ssl'] ? "https" : "http";
		$m['m_url_prefix'] = $_connection_type . "://" . $this->API->config['url']['hostname'][ $_connection_type ] . "/" . $m['m_name'];

		//------------------------------------------------------------------------------------
		// Process SUBROUTINES and determine the working one and the request coming with it
		//------------------------------------------------------------------------------------

		$this->subroutines__do_process( $m );
		$m['_is_loaded'] = 1;

		return $m;
	}


	/**
	 * Get active modules from DB
	 *
	 * @return  array  Array of active (enabled) modules
	 */
	private function modules__fetch_active ()
	{
		if ( isset( $this->API->Cache->cache['modules']['by_name'] ) and is_array( $this->API->Cache->cache['modules']['by_name'] ) and count( $this->API->Cache->cache['modules']['by_name'] ) )
		{
			foreach ( $this->API->Cache->cache['modules']['by_name'] as $m_name=>$m_data )
			{
				if ( $m_data['m_is_enabled'] == 1 or IN_DEV )
				{
					$a_m[ $m_name ] = $m_data;
				}
			}

			return $a_m;

		}
		else
		{
			# No active modules? Ok then...
			return null;
		}
	}


	/**
	 * Get currently working module
	 *
	 * @return   boolean   TRUE on success, FALSE otherwise
	 */
	private function modules__fetch_working ()
	{
		# Checking PATH_INFO
		$possible_module_name_from_path_info = $this->API->config['page']['request']['path_exploded'][0];

		# Do we have the requested module amongst our Active (enabled) modules?
		if ( array_key_exists( $possible_module_name_from_path_info, $this->active_modules ) )
		{
			$this->cur_module =& $this->active_modules[ $possible_module_name_from_path_info ];
			return TRUE;
		}
		else
		{
			$this->cur_module = null;
			return FALSE;
		}
	}


	/**
	 * Determines the running subroutine and processes the request parameters
	 *
	 * @param    array     REF: Module data
	 * @return   boolean   TRUE if there is a running subroutine, FALSE otherwise (meaning, we are on index page of module)
	 */
	private function subroutines__do_process ( &$m )
	{
		if ( ! isset( $m['m_subroutines'] ) or ! count( $m['m_subroutines'] ) )
		{
			return null;
		}

		# Continue...
		foreach ( $m['m_subroutines'] as $_s_name=>$_s_data )
		{
			if ( preg_match( '#^' . $_s_data['s_pathinfo_uri_schema_parsed'] . '$#i', str_replace( "/" . $m['m_name'] . "/" , "" , $this->API->config['page']['request']['path'] ) , $_match ) )
			{
				# Got it...
				$m['running_subroutine'] =& $m['m_subroutines'][ $_s_name ];
				foreach ( $_match as $_field_name => $_field_value )
				{
					if ( is_numeric( $_field_name ) )
					{
						continue;
					}
					$m['running_subroutine']['request'][ $_field_name ] = $_field_value;
				}

				# Page number
				$m['running_subroutine']['page_nr_requested'] =
					isset( $_GET['_page'][ $m['running_subroutine']['s_name'] ] )
					?
					intval( $_GET['_page'][ $m['running_subroutine']['s_name'] ] )
					:
					1;

				return TRUE;
			}
		}

		# Still no results? Index it is, then...
		$m['running_subroutine'] = null;
		return FALSE;
	}
}