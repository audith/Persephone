<?php

namespace Persephone;

if ( !defined( "INIT_DONE" ) )
{
	die( "Improper access! Exiting now..." );
}

class Display
{
	/**
	 * Registry reference
	 *
	 * @var Registry
	 */
	private $Registry;

	/**
	 * Cache id for engine
	 *
	 * @var array
	 */
	public $cache_id = array();

	/**
	 * Content to serve
	 *
	 * @var mixed
	 */
	private $content;

	/**
	 * Current Skin data
	 *
	 * @var mixed
	 */
	public $cur_skin = null;

	/**
	 * Enabled skins
	 *
	 * @var array
	 */
	public $enabled_skins = array();

	/**
	 * Output mode [html, xml/xml-rpc or json], defaults to "html"
	 *
	 * @var string
	 */
	public $output_mode = "html";

	/**
	 * Template bits PARSED
	 *
	 * @var array
	 */
	public $output__parsed = array();

	/**
	 * Container that has all pager information for the current request
	 *
	 * @var array
	 */
	private $pager_instances_container = array();

	/**
	 * Request BIT-NAME
	 *
	 * @var string
	 */
	private $request;

	/**
	 * Smarty instance
	 *
	 * @var Smarty
	 */
	public $smarty;

	/**
	 * Skin assets (CSS and JS repo)
	 *
	 * @var array
	 */
	public $skin_assets = array();

	/**
	 * Template bits
	 *
	 * @var array
	 */
	private $skin_templates = array();


	/**
	 * Constructor
	 *
	 * @param    object    Registry Object Reference
	 */
	public function __construct ( Registry $Registry )
	{
		# Bring-in Registry Object reference
		$this->Registry = $Registry;

		# Still here? Output then... Get default skin
		$this->skin__get_current();

		# Init Display Engine [Pre-requisite: $this->skin__get_current()]
		$this->smarty__init();
	}


	/**
	 * Destructor
	 */
	public function _my_destruct ()
	{
		$this->Registry->logger__do_log( __CLASS__ . "::__destruct: Destroying class" );
	}


	/**
	 * Outputs content using SMARTY Engine
	 *
	 * @return   boolean   TRUE on successful output, FALSE otherwise
	 */
	public function do_display ()
	{
		//-----------------------------------------
		// @todo User / Admin Authentication
		//-----------------------------------------

		//--------------
		// Cache-Id
		//--------------

		# Module-based Cache-Id [for bit caches which are request (or content) independent]
		$this->cache_id[ 'module_based' ] = md5( $this->Registry->Modules->cur_module[ 'm_unique_id_clean' ] );

		# Request-based Cache-Id [for bit caches which are request (or content) dependent]
		$_cache_id__request_based__raw = $this->Registry->Modules->cur_module[ 'm_unique_id_clean' ] . $this->Registry->Modules->cur_module[ 'running_subroutine' ][ 's_name' ];
		if ( isset( $this->Registry->Modules->cur_module[ 'running_subroutine' ][ 'request' ] ) )
		{
			$_cache_id__request_based__raw .= serialize( $this->Registry->Modules->cur_module[ 'running_subroutine' ][ 'request' ] );
		}
		$this->cache_id[ 'request_based' ] = md5( $_cache_id__request_based__raw );
		unset( $_cache_id__request_based__raw );

		//----------------------------------
		// HTTP Response Headers - Build
		//----------------------------------

		$this->Registry->Input->headers[ 'response' ] = $this->Registry->Modules->content__build_http_headers( $this->Registry->Modules->cur_module );

		//----------------------------
		// Content Delivery - Build
		//----------------------------

		# Output mode

		if ( isset( $_GET[ 'output' ] ) and in_array( $_GET[ 'output' ], array( "html", "json" ) ) )
		{
			$this->output_mode = $_GET[ 'output' ];
		}

		# Get it...

		$this->content = $this->Registry->Modules->content__do( $this->Registry->Modules->cur_module );

		if ( $this->Registry->Input->headers[ 'request' ][ '_is_ajax' ] or $this->output_mode == 'json' )
		{
			# @see http://www.ietf.org/rfc/rfc4627.txt
			header( "Content-type: application/json" );
			echo json_encode( $this->content[ 'content' ] );
		}
		else
		{
			if ( $this->content === false )
			{
				# @todo Decide which code to declare? 30x or 404?

				header( "HTTP/1.1 404 Content not found" ); // Don't put exit() here, we still have other headers to add!!!

				// $this->Registry->http_redirect( $this->Registry->Modules->cur_module['m_url_prefix'] );
			}
		}

		//----------------------------------------------------------
		// HTTP Response Headers & Content Delivery - Deploy now
		//----------------------------------------------------------

		# HTTP Response Headers
		foreach ( $this->Registry->Input->headers[ 'response' ] as $_k => $_v )
		{
			header( $_k . ":" . $_v );
		}
		header( "X-Powered-By: Audith CMS codename Persephone" );

		# We are done here if the request is of JSON-type
		if ( $this->Registry->Input->headers[ 'request' ][ '_is_ajax' ] or $this->output_mode == 'json' )
		{
			return true;
		}

		# No XHR here! Regular output then...
		# Good, check if caching is ON, and if cache is avaiable. If so, display from cache, otherwise build everything from scratch
		if ( $this->smarty->caching and $this->smarty->isCached( "wrapper.tpl", $this->cache_id[ 'request_based' ] ) )
		{
			$this->smarty->display( "wrapper.tpl", $this->cache_id[ 'request_based' ] );

			return true;
		}

		# Skin template bits - PHASE 1
		$this->skin__prepare_template_bits();

		# Skin wrapper
		return $this->skin__wrap_it_up();
	}


	/**
	 * Prepares skin remote (linked) assets (CSS and JS files) for display
	 *
	 * @return  void
	 */
	private function skin__prepare_assets ()
	{
		# Populate current request [subroutine] skin-assets list, by merging that of skin-set's with the one of running-subroutine's.
		foreach ( $this->Registry->Modules->cur_module[ 'running_subroutine' ][ 's_additional_skin_assets' ] as $_additional_asset )
		{
			if ( !in_array( $_additional_asset, $this->cur_skin[ 'set_assets' ], true ) )
			{
				$this->cur_skin[ 'set_assets' ][ ] = $_additional_asset;
			}
		}

		# Continue ...
		if ( is_array( $this->cur_skin[ 'set_assets' ] ) and !empty( $this->cur_skin[ 'set_assets' ] ) )
		{
			$this->skin_assets = array(
				'css' => "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"" . SITE_URL . "/public/min/index.php?f=",
				'js'  => "<script type=\"text/javascript\" src=\"" . SITE_URL . "/public/min/index.php?f=",
			);

			$_assets_parsed = array();
			foreach ( $this->cur_skin[ 'set_assets' ] as $_k => $_v )
			{
				switch ( $_v[ 'scope' ] . "_" . $_v[ 'type' ] )
				{
					case 'global_css':
						$_prefix = rtrim( $this->style_url[ 'global_css' ], "/" );
						break;
					case 'local_css':
						$_prefix = rtrim( $this->style_url[ 'local_css' ], "/" );
						break;
					case 'global_js':
						$_prefix = rtrim( $this->style_url[ 'global_js' ], "/" );
						break;
					case 'local_js':
						$_prefix = rtrim( $this->style_url[ 'local_js' ], "/" );
						break;
				}
				$_assets_parsed[ $_v[ 'type' ] ][ ] = $_prefix . $_v[ 'file' ];
			}

			$this->skin_assets[ 'css' ] .= implode( ",", $_assets_parsed[ 'css' ] ) . "\" />";
			$this->skin_assets[ 'js' ] .= implode( ",", $_assets_parsed[ 'js' ] ) . "\"></script>";
		}
	}


	/**
	 * Fetches the data associated with default skin
	 */
	private function skin__get_current ()
	{
		# Fetch working...
		if ( isset( $this->Registry->Cache->cache[ 'skins' ][ '_default_skin' ] ) and count( $this->Registry->Cache->cache[ 'skins' ][ '_default_skin' ] ) )
		{
			$this->cur_skin =& $this->Registry->Cache->cache[ 'skins' ][ '_default_skin' ];
		}
		else
		{
			trigger_error( "Display-Engine : No enabled skins sets found! Displaying engine cannot continue without a working skin set!", E_USER_ERROR );
			exit();
		}

		# Check if the current member has a preferred skin - if he/she does, set it as working skin
		// @todo   Permission checks
		if ( isset( $this->Registry->Session->member[ 'skin_choice' ] ) and array_key_exists( $this->Registry->Session->member[ 'skin_choice' ], $this->Registry->Cache->cache[ 'skins' ] ) )
		{
			$this->cur_skin = $this->Registry->Cache->cache[ 'skins' ][ $this->Registry->Session->member[ 'skin_choice' ] ];
		}

		// @todo REMOVE
		if ( $this->Registry->Modules->cur_module[ 'm_name' ] == 'acp' )
		{
			$this->cur_skin = $this->Registry->Cache->cache[ 'skins' ][ 1 ];

			return;
		}
	}


	/**
	 * Fetch/Display template BITS
	 */
	private function skin__prepare_template_bits ()
	{
		//-----------------------------------------
		// @todo Default Meta-Generator values
		//-----------------------------------------

		$_generator = array(
			"title"            => "",
			"keywords"         => "powered by audith persephone, audith cms",
			"description"      => "",
			"owner"            => "hostmaster@audith.com",
			"author"           => "Audith Designs",
			"robots"           => "index,follow,archive",
			"google_verify_v1" => $this->Registry->config[ 'general' ][ 'google_verify_v1' ]
				? $this->Registry->config[ 'general' ][ 'google_verify_v1' ]
				: ""
		);

		$this->smarty->assign( "GENERATOR", $_generator );

		//----------------------
		// Primary assignments
		//----------------------

		$_url =& $this->Registry->config[ 'page' ][ 'request' ];
		$this->smarty->assign( "SITE_URL", $_url[ 'scheme' ] . "://" . $this->Registry->config[ 'url' ][ 'hostname' ][ $_url[ 'scheme' ] ] );
		$this->smarty->assign( "SITE_DIR", PATH_ROOT_WEB );
		$this->smarty->assign( "SITE_NAME", $this->Registry->config[ 'general' ][ 'site_name' ] );
		$this->smarty->assign( "SITE_MOTTO", $this->Registry->config[ 'general' ][ 'site_motto' ] );
		$this->smarty->assign( "MODULE_URL", $this->Registry->Modules->cur_module[ 'm_url_prefix' ] );
		$this->smarty->assign( "REQUEST_URL", $this->Registry->config[ 'page' ][ 'request' ][ 'request_uri' ] );

		$this->smarty->assign( "STYLE_IMAGES_URL", "/public/style/" . $this->cur_skin[ 'set_id' ] . "/images" );

		$this->smarty->assign( "MEMBER", $this->Registry->member );
		$this->smarty->assign( "CONFIG", $this->Registry->config );
		$this->smarty->assign( "PAGE", $this->Registry->config[ 'page' ] );

		//----------------------------------------
		// Parse template bits : Global + Local
		//----------------------------------------

		$this->skin_assets[ 'css' ] = "";
		$this->skin_assets[ 'js' ]  = "";

		# Prepare remote assets - which are always attached, never inline
		$this->skin__prepare_assets();

		# Prepare local assets
		$this->request      = $this->Registry->Modules->cur_module[ 'm_unique_id_clean' ] . "-" . $this->Registry->Modules->cur_module[ 'running_subroutine' ][ 's_name' ];
		$_bit_name_w_prefix = "bits:" . $this->request;
		if ( $this->smarty->templateExists( $_bit_name_w_prefix . "-css" ) )
		{
			$this->skin_assets[ 'css' ] .=
				"\n<style type=\"text/css\" id=\"" . $_bit_name_w_prefix . "-css\">\n" . $this->smarty->fetch( $_bit_name_w_prefix . "-css", $this->cache_id[ 'request_based' ] ) . "\n</style>\n";
		}
		if ( $this->smarty->templateExists( $_bit_name_w_prefix . "-js" ) )
		{
			$this->skin_assets[ 'js' ] .= "\n<script type=\"text/javascript\" defer=\"defer\" id=\"" . $_bit_name_w_prefix . "-js\">//<![CDATA[\n" .
			                              $this->smarty->fetch( $_bit_name_w_prefix . "-js", $this->cache_id[ 'request_based' ] ) . "\n//]]></script>\n";
		}

		# Favicon + the rest...
		$_m_unique_id_clean = $this->Registry->Modules->cur_module[ 'm_unique_id_clean' ];
		$this->smarty->assign( "FAVICON", $this->cur_skin[ 'set_favicon' ] );
		$this->smarty->assign( "RSS", array() ); // @todo RSS
		$this->output__parsed = array(
			'meta_generator' => $this->smarty->fetch( "bits:" . $_m_unique_id_clean . "-meta_generator", null, $this->cache_id[ 'module_based' ] ),
			'favicon'        => $this->smarty->fetch( "bits:" . $_m_unique_id_clean . "-favicon", null, $this->cache_id[ 'module_based' ] ),
			'rss'            => $this->smarty->fetch( "bits:" . $_m_unique_id_clean . "-rss", null, $this->cache_id[ 'module_based' ] ),
			'logo'           => $this->smarty->fetch( "bits:" . $_m_unique_id_clean . "-logo", null, $this->cache_id[ 'module_based' ] ),
			'header'         => $this->smarty->fetch( "bits:" . $_m_unique_id_clean . "-header", null, $this->cache_id[ 'module_based' ] ),
			'navigation'     => $this->smarty->fetch( "bits:" . $_m_unique_id_clean . "-navigation", null, $this->cache_id[ 'module_based' ] ),
			'search_form'    => $this->smarty->fetch( "bits:" . $_m_unique_id_clean . "-search_form", null, $this->cache_id[ 'module_based' ] ),
			'memberbar'      => $this->smarty->fetch( "bits:" . $_m_unique_id_clean . "-memberbar", null, $this->cache_id[ 'module_based' ] ),
			'footer'         => $this->smarty->fetch( "bits:" . $_m_unique_id_clean . "-footer", null, $this->cache_id[ 'module_based' ] ),
			'copyright'      => $this->smarty->fetch( "bits:" . $_m_unique_id_clean . "-copyright", null, $this->cache_id[ 'module_based' ] ),
			'css'            => $this->skin_assets[ 'css' ],
			'js'             => $this->skin_assets[ 'js' ]
		);

		# Garbage collection
		$this->skin_assets = array();

		//-----------
		// GARBAGE
		//-----------

		$this->smarty->clearAssign( "GENERATOR" );
		$this->smarty->clearAssign( "CSS_CONTENT" );
		$this->smarty->clearAssign( "CSS_FILES" );
		$this->smarty->clearAssign( "JS_CONTENT" );
		$this->smarty->clearAssign( "JS_FILES" );
		$this->smarty->clearAssign( "FAVICON" );
		$this->smarty->clearAssign( "RSS" );

		//-----------------------------
		// CONTENT, COUNT and M_DFD
		//-----------------------------

		if ( isset( $this->content[ 'content' ] ) and !empty( $this->content[ 'content' ] ) )
		{
			$this->smarty->assign( "CONTENT", $this->content[ 'content' ] );
			$this->smarty->assign( "COUNT", count( $this->content[ 'content' ] ) );
		}
		else
		{
			$this->smarty->assign( "CONTENT", $this->content[ 'content' ] );
			$this->smarty->assign( "COUNT", 0 );
		}

		if ( isset( $this->content[ 'm_data_definition' ] ) and !empty( $this->content[ 'm_data_definition' ] ) )
		{
			$this->smarty->assign( "M_DFD", $this->content[ 'm_data_definition' ] );
		}

		if ( !$this->output__parsed[ $this->request ] = $this->smarty->fetch( $_bit_name_w_prefix, $this->cache_id[ 'request_based' ] ) )
		{
			$this->output__parsed[ $this->request ] = $this->smarty->fetch( "temporary.tpl", $this->cache_id[ 'request_based' ] );
		}
	}


	/**
	 * Fetch/Display main skin wrapper
	 */
	private function skin__wrap_it_up ()
	{
		//-------------------------
		// Assign WRAPPER values
		//-------------------------

		$this->smarty->assign( "GENERATOR", $this->output__parsed[ 'meta_generator' ] );
		$this->smarty->assign( "FAVICON", $this->output__parsed[ 'favicon' ] );
		$this->smarty->assign( "CSS", $this->output__parsed[ 'css' ] );
		$this->smarty->assign( "JAVASCRIPT", $this->output__parsed[ 'js' ] );
		$this->smarty->assign( "RSS", $this->output__parsed[ 'rss' ] );
		$this->smarty->assign( "LOGO", $this->output__parsed[ 'logo' ] );
		$this->smarty->assign( "HEADER", $this->output__parsed[ 'header' ] );
		$this->smarty->assign( "NAVIGATION", $this->output__parsed[ 'navigation' ] );
		$this->smarty->assign( "SEARCH_FORM", $this->output__parsed[ 'search_form' ] );
		$this->smarty->assign( "MEMBERBAR", $this->output__parsed[ 'memberbar' ] );
		$this->smarty->assign( "FOOTER", $this->output__parsed[ 'footer' ] );
		$this->smarty->assign( "COPYRIGHT", $this->output__parsed[ 'copyright' ] );

		$this->smarty->assign( "CONTENT", $this->output__parsed[ $this->request ] );

		//------------
		// Display
		//------------

		return $this->smarty->display( "wrapper.tpl", $this->cache_id[ 'request_based' ], $this->cache_id[ 'module_based' ] );
	}


	/**
	 * Pager
	 *
	 * @param   integer   Total number of items
	 * @param   integer   Number of items per page
	 * @param   integer   Range of paging - to the left and right
	 * @param   integer   Current page number
	 *
	 * @return  mixed     Parsed Pager info [array] on success, FALSE [boolean] otherwise
	 */
	private function misc__pager__do_parse ( $total_nr_of_items, $nr_of_items_per_page, $range, $current_page )
	{
		//----------------------
		// Preliminary checks
		//----------------------

		if ( !$current_page >= 1
		     or
		     !$nr_of_items_per_page >= 1
		     or
		     !$range >= 1
		)
		{
			return false;
		}

		//---------------
		// Continue...
		//---------------

		# Total number of pages
		$total_nr_of_pages = 1;
		if ( $total_nr_of_items % $nr_of_items_per_page == 0 )
		{
			if ( ( $_total_nr_of_pages = $total_nr_of_items / $nr_of_items_per_page ) != 0 )
			{
				$total_nr_of_pages = floor( $_total_nr_of_pages );
				unset( $_total_nr_of_pages );
			}
			else
			{
				// Blind-spot: Do nothing here, $total_nr_of_pages = 1 already :)
			}
		}
		else
		{
			$total_nr_of_pages = ceil( $total_nr_of_items / $nr_of_items_per_page );
		}

		# Is left, or right side off-the-range?
		$_left_side_is_off_range    = false;
		$_right_side_is_off_range   = false;
		$_current_page_is_off_range = 0;
		if ( $current_page - $range <= 1 )
		{
			$_left_side_is_off_range = true;
		}
		if ( $current_page + $range >= $total_nr_of_pages )
		{
			$_right_side_is_off_range = true;
		}
		if ( $current_page > $total_nr_of_pages )
		{
			$_current_page_is_off_range = $current_page;
			$current_page               = $total_nr_of_pages;
		}

		//------------------------------------
		// Prepare list of pager-components
		//------------------------------------

		# Init
		$components = array();

		# Left-hand side
		if ( $_left_side_is_off_range )
		{
			# Left-hand-side, up to Current-page (latter exclusive)
			for ( $_i = 1; $_i < $current_page; $_i++ )
			{
				$components[ ] = array(
					'value'       => $_i,
					'_is_first'   => ( $_i == 1
						? true
						: false ),
					'_is_last'    => false,
					'_is_current' => false,
					'_is_dump'    => false,
				);
			}
		}
		else
		{
			# First page
			$components[ ] = array(
				'value'       => 1,
				'_is_first'   => true,
				'_is_last'    => false,
				'_is_current' => false,
				'_is_dump'    => false,
			);
			# DUMP
			if ( $current_page - $range - 1 > 1 )
			{
				$components[ ] = array(
					'value'       => "...",
					'_is_first'   => false,
					'_is_last'    => false,
					'_is_current' => false,
					'_is_dump'    => true,
				);
			}
			# Left-hand-side, up to Current-page (latter exclusive)
			for ( $_i = $current_page - $range; $_i < $current_page; $_i++ )
			{
				$components[ ] = array(
					'value'       => $_i,
					'_is_first'   => false,
					'_is_last'    => false,
					'_is_current' => false,
					'_is_dump'    => false,
				);
			}
		}

		# Current-page
		$components[ ] = array(
			'value'       => $current_page,
			'_is_first'   => ( $_i == 1
				? true
				: false ),
			'_is_last'    => ( $_i == $total_nr_of_pages
				? true
				: false ),
			'_is_current' => $_current_page_is_off_range
				? false
				: true,
			'_is_dump'    => false,
		);

		if ( $_current_page_is_off_range )
		{
			$components[ ] = array(
				'value'       => $_current_page_is_off_range . " (off-range!!!)",
				'_is_first'   => false,
				'_is_last'    => false,
				'_is_current' => true,
				'_is_dump'    => false,
			);
		}

		# Right-hand side
		if ( $_right_side_is_off_range )
		{
			# Right-hand-side, up to Current-page (latter exclusive)
			for ( $_i = $current_page + 1; $_i <= $total_nr_of_pages; $_i++ )
			{
				$components[ ] = array(
					'value'       => $_i,
					'_is_first'   => false,
					'_is_last'    => ( $_i == $total_nr_of_pages
						? true
						: false ),
					'_is_current' => false,
					'_is_dump'    => false,
				);
			}
		}
		else
		{
			# Right-hand-side, up to Current-page (latter exclusive)
			for ( $_i = $current_page + 1; $_i <= $current_page + $range; $_i++ )
			{
				$components[ ] = array(
					'value'       => $_i,
					'_is_first'   => false,
					'_is_last'    => false,
					'_is_current' => false,
					'_is_dump'    => false,
				);
			}

			# DUMP
			if ( $current_page + $range + 1 < $total_nr_of_pages )
			{
				$components[ ] = array(
					'value'       => "...",
					'_is_first'   => false,
					'_is_last'    => false,
					'_is_current' => false,
					'_is_dump'    => true,
				);
			}

			# First page
			$components[ ] = array(
				'value'       => $total_nr_of_pages,
				'_is_first'   => false,
				'_is_last'    => true,
				'_is_current' => false,
				'_is_dump'    => false,
			);
		}

		# RETURN
		return $components;
	}


	/**
	 * Initialize SMARTY default params and settings
	 *
	 * @param   array   Skin information
	 */
	public final function smarty__init ( $skin = null )
	{
		if ( is_null( $skin ) )
		{
			$skin =& $this->cur_skin;
		}

		# Fetching SMARTY Libs
		require_once( PATH_LIBS . "/Smarty/Smarty.class.php" );

		# Initiating Smarty Object
		if ( !$this->smarty = new Smarty() )
		{
			throw new \Persephone\Exception( "Display: Couldn't instantiate Smarty template-engine!" );
		}

		# Some SMARTY settings
		$this->smarty->cache_lifetime       = $this->Registry->config[ 'display' ][ 'cache_lifetime' ];
		$this->smarty->caching              = $this->Registry->Modules->cur_module[ 'm_enable_caching' ]
			? Smarty::CACHING_LIFETIME_CURRENT
			: Smarty::CACHING_OFF;
		$this->smarty->cache_modified_check = true;
		$this->smarty->compile_check        = true;
		$this->smarty->auto_literal         = false;
		$this->smarty->debugging            = ( !IN_DEV )
			? false
			: $this->Registry->config[ 'display' ][ 'debugging' ];

		# Reassigning Smarty Delimiters
		$this->smarty->left_delimiter  = '{{';
		$this->smarty->right_delimiter = '}}';

		# Smarty Dirs
		$this->smarty->setCacheDir( PATH_TEMPLATES . "/" . $skin[ 'set_id' ] . "/cache" );
		$this->smarty->setCompileDir( PATH_TEMPLATES . "/" . $skin[ 'set_id' ] . "/templates_c" );
		$this->smarty->setConfigDir( PATH_TEMPLATES . "/" . $skin[ 'set_id' ] . "/config" );
		// $this->smarty->setPluginsDir( PATH_TEMPLATES . "/"  . $skin['set_id'] . "/plugins" );
		$this->smarty->setTemplateDir( PATH_TEMPLATES . "/" . $skin[ 'set_id' ] . "/templates" );

		# Skin URLs
		$this->style_url = array(
			'images'     => SITE_URL . "/public/style/" . $skin[ 'set_id' ] . "/images",
			'global_css' => "/public/css",
			'global_js'  => "/public/js",
			'local_css'  => "/public/style/" . $skin[ 'set_id' ] . "/css",
			'local_js'   => "/public/style/" . $skin[ 'set_id' ] . "/js",
		);

		# Register SMARTY resource named "bits"
		$this->smarty->registerResource(
			"bits",
			array(
			     array( $this, "smarty__resource__bits__get_template" ),
			     array( $this, "smarty__resource__bits__get_timestamp" ),
			     array( $this, "smarty__resource__bits__get_secure" ),
			     array( $this, "smarty__resource__bits__get_trusted" ),
			)
		);

		//-------------------------------------
		// EXTENDING SMARTY - Custom blocks
		//-------------------------------------

		$this->smarty->registerPlugin( "block", "dynamic", array( $this, "smarty__plugin__block__do_not_cache_dynamic" ), false );

		//---------------------------------------
		// EXTENDING SMARTY - Custom modifiers
		//---------------------------------------

		$this->smarty->registerPlugin( "modifier", "text2form", array( $this->Registry->Input, "text2form" ) );
		$this->smarty->registerPlugin( "modifier", "filesize_h", array( $this->Registry->Input, "file__filesize__do_parse" ) );
		$this->smarty->registerPlugin( "modifier", "filetype", array( $this->Registry->Input, "file__filetype__do_parse" ) );
		$this->smarty->registerPlugin( "modifier", "array_display_range", array( $this, "smarty__plugin__modifier__array_display_range" ) );
		$this->smarty->registerPlugin( "modifier", "m_unique_id_clean", array( $this, "smarty__plugin__modifier__m_unique_id_clean" ) );

		// $this->smarty->registerPlugin( "modifier" , "urlencode"            , "urlencode"                                                     );

		//---------------------------------------
		// EXTENDING SMARTY - Custom Functions
		//---------------------------------------

		$this->smarty->registerPlugin( "function", "pager", array( $this, "smarty__plugin__function__pager" ) );
		$this->smarty->registerPlugin( "function", "http_build_query", array( $this, "smarty__plugin__function__http_build_query" ) );
		$this->smarty->registerPlugin( "function", "recaptcha", array( $this, "smarty__plugin__function__recaptcha" ) );

		return true;
	}


	/**
	 * EXT: Controlling cacheability for dynamic content
	 */
	public function smarty__plugin__block__do_not_cache_dynamic ( $params, $content, $smarty_object, &$repeat, $template )
	{
		return $content;
	}


	/**
	 * SMARTY EXTENDED - "recaptcha" function
	 *
	 * @param    array    ** not used **
	 * @param    object   Smarty object reference
	 *
	 * @return   string   ReCaptcha HTML-code
	 */
	public function smarty__plugin__function__recaptcha ( $params, $smarty_object )
	{
		if ( $this->Registry->config[ 'security' ][ 'enable_captcha' ] )
		{
			try
			{
				if ( ( !isset( $this->Registry->config[ 'security' ][ 'recaptcha_public_key' ] ) or empty( $this->Registry->config[ 'security' ][ 'recaptcha_public_key' ] ) )
				     or
				     ( !isset( $this->Registry->config[ 'security' ][ 'recaptcha_private_key' ] ) or empty( $this->Registry->config[ 'security' ][ 'recaptcha_private_key' ] ) )
				)
				{
					throw new \Persephone\Exception( "Public and/or private key not found! Make sure both are set in order to use ReCaptcha feature..." );
				}
				$_recaptcha_obj = $this->Registry->loader( "Zend_Captcha_ReCaptcha" );
				$_recaptcha_obj->setPubKey( $this->Registry->config[ 'security' ][ 'recaptcha_public_key' ] );
				$_recaptcha_obj->setPrivKey( $this->Registry->config[ 'security' ][ 'recaptcha_private_key' ] );

				return $_recaptcha_obj->getService()->getHTML();
			}
			catch ( Exception $e )
			{
				$this->Registry->logger__do_log( "Display - smarty__plugin__function__recaptcha() : " . $e->getMessage(), "ERROR" );
			}
		}

		return "";
	}


	/**
	 * SMARTY EXTENDED - "pager" function
	 *
	 * @param    array    Parameters: (int) "total_nr_of_items", (int) "nr_of_items_per_page", (int) "current_page", (int) "range", (string) "format"="anchor|dropdown"
	 * @param    object   Smarty object reference
	 *
	 * @return   string   Pager instance output
	 */
	public function smarty__plugin__function__pager ( $params, $smarty_object )
	{
		if ( !isset( $params[ 'instance' ] ) )
		{
			$params[ 'instance' ] = $this->Registry->Modules->cur_module[ 'running_subroutine' ][ 's_name' ];
		}

		//--------------------------------------------------------------------------------
		// Do we have the working copy of requested pager instance? If so, return it...
		//--------------------------------------------------------------------------------

		if ( isset( $this->pager_instances_container[ $params[ 'instance' ] ] ) )
		{
			$_instance =& $this->pager_instances_container[ $params[ 'instance' ] ];
			if ( $_instance[ 'params' ] === $params )
			{
				return $_instance[ 'output' ];
				exit;
			}
		}

		//-------------------------------------------------------
		// Guess we don't have a working instance; continue...
		// $param - Preliminary checks
		//-------------------------------------------------------

		# TOTAL_NR_OF_ITEMS [required]
		if ( !isset( $params[ 'total_nr_of_items' ] ) )
		{
			throw new \Persephone\Exception ( "Display Engine: Missing argument - \$total_nr_of_items" );

			return false;
		}
		$_total_nr_of_items = intval( $params[ 'total_nr_of_items' ] );

		# CURRENT PAGE [optional]
		if ( !isset( $params[ 'current_page' ] ) or !$params[ 'current_page' ] >= 1 )
		{
			if ( isset( $_GET[ '_page' ][ $params[ 'instance' ] ] ) and is_numeric( $_GET[ '_page' ][ $params[ 'instance' ] ] ) and $_GET[ '_page' ][ $params[ 'instance' ] ] >= 1 )
			{
				$_current_page = intval( $_GET[ '_page' ][ $params[ 'instance' ] ] );
			}
			else
			{
				$_current_page = 1;
			}
		}
		else
		{
			$_current_page = intval( $params[ 'current_page' ] );
		}

		# NR_OF_ITEMS_PER_PAGE [optional]
		if ( !isset( $params[ 'nr_of_items_per_page' ] ) or !$params[ 'nr_of_items_per_page' ] >= 1 )
		{
			$_nr_of_items_per_page = 20;
		}
		else
		{
			$_nr_of_items_per_page = intval( $params[ 'nr_of_items_per_page' ] );
		}

		# RANGE [optional]
		if ( !isset( $params[ 'range' ] ) or !$params[ 'range' ] >= 1 )
		{
			$_range = 3;
		}
		else
		{
			$_range = intval( $params[ 'range' ] );
		}

		# FORMAT [optional]
		if ( !isset( $params[ 'format' ] ) or !in_array( $params[ 'format' ], array( "dropdown", "anchor" ) ) )
		{
			$_format = "anchor";
		}
		else
		{
			$_format = $params[ 'format' ];
		}

		//--------------------
		// Parse and Return
		//--------------------

		if ( ( $_pager_info__parsed = $this->misc__pager__do_parse( $_total_nr_of_items, $_nr_of_items_per_page, $_range, $_current_page ) ) === false )
		{
			throw new \Persephone\Exception ( "Display Engine: FAILED to parse Pager information!" );

			return false;
		}

		$smarty_object->assign( "PAGER", $_pager_info__parsed );
		$smarty_object->assign( "instance", $params[ 'instance' ] );
		$smarty_object->clearCache( "pager_" . $_format . ".tpl" ); // Let's clear the cache

		if ( isset( $this->pager_instances_container[ $params[ 'instance' ] ] ) )
		{
			$_instance =& $this->pager_instances_container[ $params[ 'instance' ] ];
			if ( $_instance[ 'params' ] !== $params )
			{
				throw new \Persephone\Exception ( "Pager-Instance on " . $params[ 'instance' ] . " is in use! Choose another instance identifier!" );
				exit;
			}
		}

		$this->pager_instances_container[ $params[ 'instance' ] ] = array(
			'params' => $params,
			'output' => $smarty_object->fetch( "pager_" . $_format . ".tpl" ),
		);

		return $this->pager_instances_container[ $params[ 'instance' ] ][ 'output' ];
	}


	/**
	 * SMARTY EXTENDED - "http_build_query" function : Manipulates the current QUERY_STRING (adds, alters, removes values)
	 *
	 * @param   array    Parameter to pass to Input::query_string__do_process()
	 * @param   object   Smarty object reference
	 *
	 * @return  mixed    (boolean) FALSE if error, (string) formatted QUERY_STRING otherwise
	 */
	public function smarty__plugin__function__http_build_query ( $params, $smarty_object )
	{
		return $this->Registry->Input->query_string__do_process( array( 'key' => $params[ 'key' ], 'value' => $params[ 'value' ] ), $params[ 'action' ], false );
	}


	/**
	 * SMARTY EXTENDED - "array_display_range" modifier : Displays the min-max range for a given array
	 *
	 * @param   array    Array to display a min-max range
	 *
	 * @return  mixed    (boolean) FALSE on error, (string) Range-string otherwise
	 */
	public function smarty__plugin__modifier__array_display_range ( $array )
	{
		if ( !is_array( $array ) or !count( $array ) )
		{
			return false;
		}
		$array = array_values( array_unique( $array ) );
		sort( $array, SORT_NUMERIC );
		if ( ( $_count = count( $array ) ) == 1 )
		{
			return $array[ 0 ];
		}
		else
		{
			return $array[ 0 ] . "-" . $array[ $_count - 1 ];
		}
	}


	/**
	 * SMARTY EXTENDED - "m_unique_id_clean" modifier : Cleans module-unique-id from non alphanumeric characters and lowercase's it.
	 *
	 * @param   array    Array to display a min-max range
	 *
	 * @return  mixed    (boolean) FALSE on error, (string) Range-string otherwise
	 */
	public function smarty__plugin__modifier__m_unique_id_clean ( $m_unique_id )
	{
		return strtolower( preg_replace( '/[^a-z0-9]/i', "", $m_unique_id ) );
	}


	/**
	 * CHECK_TPL_NAME utility for SMARTY : RESOURCES : DB resource register-functions
	 *
	 * @param    string     Template name
	 * @param    object     Smarty object reference
	 *
	 * @return   boolean    TRUE if template was found and retrieved, FALSE otherwise
	 * @todo     File-system related code below is for development purposes only and is to be removed in production version!
	 */
	public function smarty__resource__bits__check_tpl_name ( $tpl_name, $smarty_object )
	{
		if ( !isset( $tpl_name ) or empty( $tpl_name ) )
		{
			return false;
		}

		$template_bit_information = array();

		//-----------------------------
		// Parsing template name
		//-----------------------------

		$tmp = explode( "-", $tpl_name );
		if ( empty( $tmp[ 1 ] ) )
		{
			throw new \Persephone\Exception( "Display: Templates - Empty 'bit_name' detected!" );

			return false;
		}
		if ( !empty( $tmp[ 0 ] ) and !preg_match( '/^[a-z0-9]{32}$/i', $tmp[ 0 ] ) )
		{
			throw new \Persephone\Exception( "Display: Templates - Empty 'm_unique_id_clean' detected!" );
		}

		if ( empty( $tmp[ 0 ] ) )
		{
			$template_bit_information[ 'm_unique_id' ] = $template_bit_information[ 'm_unique_id_clean' ] = null;
		}
		else
		{
			$template_bit_information[ 'm_unique_id' ]       = "{" . implode( "-", str_split( strtoupper( $tmp[ 0 ] ), 8 ) ) . "}";
			$template_bit_information[ 'm_unique_id_clean' ] = $tmp[ 0 ];
		}
		$template_bit_information[ 't_bit_name' ] = $tmp[ 1 ];
		$template_bit_information[ 't_type' ]     = ( !isset( $tmp[ 2 ] ) or empty( $tmp[ 2 ] ) )
			? "tpl"
			: $tmp[ 2 ];
		unset( $tmp );

		//-----------------------------------------------------------------
		// Template file
		// @todo Remove support for template-bit being cached-as-a-file.
		//       Otherwise it's impossible determine "primary" template-
		//       bit's existense if cache-file has been deleted.
		//-----------------------------------------------------------------

		# Path to template file
		$_fs__full_path = array(
			'primary'   => $smarty_object->getTemplateDir( 0 ) // "Primary" (module-specific) template-bit
			               . "/" . $template_bit_information[ 'm_unique_id_clean' ] . "/" . $template_bit_information[ 't_bit_name' ] . "." . $template_bit_information[ 't_type' ],
			'secondary' => $smarty_object->getTemplateDir( 0 ) // "Secondary" (global) template-bit
			               . "/" . $template_bit_information[ 't_bit_name' ] . "." . $template_bit_information[ 't_type' ]
		);

		$template_bit_information[ 'fs__exists' ]    = false;
		$template_bit_information[ 'fs__full_path' ] = "";

		# Do we have "primary" template-bit? If yes, has it expired?
		if (
			file_exists( $_fs__full_path[ 'primary' ] ) and is_file( $_fs__full_path[ 'primary' ] ) and ( filemtime( $_fs__full_path[ 'primary' ] ) + $smarty_object->cache_lifetime > UNIX_TIME_NOW )
		)
		{
			$template_bit_information[ 'fs__exists' ]    = true;
			$template_bit_information[ 'fs__full_path' ] = $_fs__full_path[ 'primary' ];
		}

		# In case if we don't have "primary" template-bit, do we have "secondary" one? If yes, has it expired?
		if ( $template_bit_information[ 'fs__exists' ] === false and
		     file_exists( $_fs__full_path[ 'secondary' ] ) and
		     is_file( $_fs__full_path[ 'secondary' ] ) and ( filemtime( $_fs__full_path[ 'secondary' ] ) + $smarty_object->cache_lifetime > UNIX_TIME_NOW )
		)
		{
			$template_bit_information[ 'fs__exists' ]    = true;
			$template_bit_information[ 'fs__full_path' ] = $_fs__full_path[ 'secondary' ];
		}

		unset( $_fs__full_path ); // GC

		# Create file if it does not exist
		if ( !$template_bit_information[ 'fs__exists' ] )
		{
			# Retrieve template-bit from Db
			$this->Registry->Db->cur_query = array(
				'do'     => "select",
				'table'  => "skin_templates",
				'fields' => array( "t_id", "s_set_id", "m_unique_id", "t_group_name", "t_bit_name", "t_timestamp", "t_source", "t_type", "t_can_remove" ),
				'where'  => array(
					//array( "( m_unique_id=" . $this->Registry->Db->quote( $template_bit_information['m_unique_id'] ) . " OR m_unique_id IS NULL ) OR t_bit_name='" . $template_bit_information['bit_name'] . "'" ),
					array( "( m_unique_id=" . $this->Registry->Db->quote( $template_bit_information[ 'm_unique_id' ] ) . " OR m_unique_id IS NULL )" ),
					array( "s_set_id=" . $this->Registry->Db->quote( $this->cur_skin[ 'set_id' ], "INTEGER" ) ),
					array( "t_bit_name=" . $this->Registry->Db->quote( $template_bit_information[ 't_bit_name' ] ) ),
					array( "t_type=" . $this->Registry->Db->quote( $template_bit_information[ 't_type' ] ) ),
				),
				'order'  => array( "t_type ASC", "m_unique_id ASC" )
			);
			$_result                       = $this->Registry->Db->simple_exec_query();

			# Did we get template data?
			if ( empty( $_result ) )
			{
				return false;
			}

			foreach ( $_result as $_row )
			{
				$template_bit_information = array_merge( $template_bit_information, $_row );
			}
			$template_bit_information[ 'm_unique_id_clean' ] = ( !is_null( $template_bit_information[ 'm_unique_id' ] ) )
				? strtolower( preg_replace( '/[^a-z0-9]/i', "", $template_bit_information[ 'm_unique_id' ] ) )
				: null;

			# Write template-source to a new template-file
			$template_bit_information[ 'fs__full_path' ] = $smarty_object->getTemplateDir( 0 ) .
			                                               ( ( !is_null( $template_bit_information[ 'm_unique_id_clean' ] ) )
				                                               ? "/" . $template_bit_information[ 'm_unique_id_clean' ]
				                                               : "" ) . "/" . $template_bit_information[ 't_bit_name' ] . "." . $template_bit_information[ 't_type' ];

			if ( !file_exists( $_tpl_dir = dirname( $template_bit_information[ 'fs__full_path' ] ) ) or !is_dir( $_tpl_dir ) )
			{
				# Create necessary directories
				if ( mkdir( $_tpl_dir, 0777, true ) === false )
				{
					throw new \Persephone\Exception( "Display: Couldn't create Smarty::template_dir folder!" );
				}
			}

			if ( !( $_fh = fopen( $template_bit_information[ 'fs__full_path' ], "wb" ) ) )
			{
				$this->Registry->logger__do_log( "Display: Couldn't create/open template-file ['" . $tpl_name . "'] for writing!", "WARNING" );
			}
			if ( !flock( $_fh, LOCK_EX ) )
			{
				$this->Registry->logger__do_log( "Display: Couldn't lock template-file ['" . $tpl_name . "'] for writing!", "WARNING" );
			}
			if ( !fwrite( $_fh, $template_bit_information[ 't_source' ] ) )
			{
				$this->Registry->logger__do_log( "Display: Couldn't write into template-file ['" . $tpl_name . "']!", "WARNING" );
			}
			if ( !flock( $_fh, LOCK_UN ) )
			{
				$this->Registry->logger__do_log( "Display: Couldn't unlock template-file ['" . $tpl_name . "']!", "WARNING" );
			}
			fclose( $_fh );
			$template_bit_information[ 'fs__exists' ] = true;
		}

		# Set write permissions if not-writable
		if ( $template_bit_information[ 'fs__exists' ] and is_readable( file_exists( $template_bit_information[ 'fs__full_path' ] ) ) )
		{
			if ( !chmod( $template_bit_information[ 'fs__full_path' ], 0777 ) )
			{
				$this->Registry->logger__do_log( "Display: Couldn't chmod() template-file ['" . $tpl_name . "']!", "WARNING" );
			}
		}

		return $template_bit_information;
		// @todo $this->skin_templates[ $tpl_name ]
	}


	/**
	 * SMARTY : RESOURCES : BITS : GET_TEMPLATE
	 *
	 * @param    string    Template name
	 * @param    string    REF: Template source code
	 * @param    object    Smarty object reference
	 *
	 * @return   boolean   TRUE on success, FALSE otherwise
	 */
	public function smarty__resource__bits__get_template ( $tpl_name, &$tpl_source, $smarty_object )
	{
		# Get template-bit information
		if ( ( $template_bit_information = $this->smarty__resource__bits__check_tpl_name( $tpl_name, $smarty_object ) ) === false )
		{
			return false;
		}

		# Get template-source from template-file if not done so yet
		if ( !isset( $template_bit_information[ 't_source' ] ) or empty( $template_bit_information[ 't_source' ] ) )
		{
			if ( ( $tpl_source = $template_bit_information[ 't_source' ] = file_get_contents( $template_bit_information[ 'fs__full_path' ] ) ) === false )
			{
				$this->Registry->logger__do_log( "Display: Couldn't read template-file ['" . $tpl_name . "']!", "ERROR" );

				return false;
			}

			if ( empty( $tpl_source ) )
			{
				return false;
			}
		}

		return true;
	}


	/**
	 * SMARTY : RESOURCES : BITS : GET_TIMESTAMP
	 *
	 * @param    string    Template name
	 * @param    integer   REF: Template timestamp
	 * @param    object    Smarty object reference
	 *
	 * @return   boolean   TRUE on success, FALSE otherwise
	 */
	public function smarty__resource__bits__get_timestamp ( $tpl_name, &$tpl_timestamp, $smarty_object )
	{
		# Get template-bit information
		if ( ( $template_bit_information = $this->smarty__resource__bits__check_tpl_name( $tpl_name, $smarty_object ) ) === false )
		{
			return false;
		}

		# Get template-source from template-file if not done so yet
		if ( !isset( $template_bit_information[ 't_timestamp' ] ) or intval( $template_bit_information[ 't_timestamp' ] ) <= 0 )
		{
			if ( ( $template_bit_information[ 't_timestamp' ] = filemtime( $template_bit_information[ 'fs__full_path' ] ) ) === false )
			{
				$this->Registry->logger__do_log( "Display: Couldn't determine timestamp for template-file ['" . $tpl_name . "']!", "ERROR" );

				return false;
			}
		}
		if ( ( $tpl_timestamp = $template_bit_information[ 't_timestamp' ] ) > 0 )
		{
			return true;
		}

		return false;
	}


	/**
	 * SMARTY : RESOURCES : BITS : GET_SECURE
	 *
	 * @param   string   Template name
	 * @param   object   SMARTY object reference
	 */
	public function smarty__resource__bits__get_secure ( $tpl_name, $smarty_object )
	{
		# Assume all templates are secure
		return true;
	}


	/**
	 * SMARTY : RESOURCES : BITS : GET_TRUSTED
	 *
	 * @param   string   Template name
	 * @param   object   Smarty object reference
	 */
	public function smarty__resource__bits__get_trusted ( $tpl_name, $smarty_object )
	{
		# Not used for templates
	}
}
