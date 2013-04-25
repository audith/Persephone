<?php

namespace Persephone;

if ( !defined( "INIT_DONE" ) )
{
	die( "Improper access! Exiting now..." );
}

/**
 * Cache class - Manages all types of cache
 *
 * @package  Audith CMS codename Persephone
 * @author   Shahriyar Imanov <shehi@imanov.name>
 * @version  1.0
 **/
class Cache
{
	/**
	 * Registry reference
	 *
	 * @var Registry
	 */
	private $Registry;

	/**
	 * Cached data
	 *
	 * @var array
	 */
	public $cache = array();

	/**
	 * List of data to be cached
	 *
	 * @var array
	 */
	public $cache_array = array();

	/**
	 * Instance for loaded cache library
	 *
	 * @var object
	 */
	public $cachelib;


	/**
	 * Constructor
	 *
	 * @param   \Persephone\Registry    $Registry
	 *
	 * @return  void
	 */
	public function __construct ( \Persephone\Registry $Registry )
	{
		$this->Registry = $Registry;
	}


	/**
	 * Destructor
	 */
	public function _my_destruct ()
	{
		\Persephone\Registry::logger__do_log( __METHOD__ . " says: Destroying class", "INFO" );
	}


	public function init ()
	{
		//------------------------------
		// Set up path for diskcache
		//------------------------------

		if ( !defined( 'PATH_CACHE' ) )
		{
			if ( !empty( $this->Registry->config[ 'performance' ][ 'cache' ][ 'diskcache' ][ 'cache_path' ] ) )
			{
				define( 'PATH_CACHE', $this->Registry->config[ 'performance' ][ 'cache' ][ 'diskcache' ][ 'cache_path' ] );
			}
			else
			{
				define( 'PATH_CACHE', PATH_ROOT_VHOST . "/cache" );
			}
		}

		try
		{
			//-----------------
			// php-memcached
			//-----------------

			if ( $this->Registry->config[ 'performance' ][ 'cache' ][ '_method' ] == 'memcached' )
			{
				if ( extension_loaded( "memcached" ) )
				{
					$this->cachelib = new \Persephone\Cache\Memcached( $this->Registry );
					if ( $this->cachelib->crashed )
					{
						throw new \Persephone\Exception( __METHOD__ . " says: Memcached failed to connect!" );
					}
				}
				else
				{
					$this->Registry->config[ 'performance' ][ 'cache' ][ '_method' ] = "diskcache";
					\Persephone\Registry::logger__do_log( __METHOD__ . " says: PHP-Memcached not found! Reverting to Disk-cache...", "WARNING" );
				}
			}

			//-----------------
			// php-memcache
			//-----------------

			if ( $this->Registry->config[ 'performance' ][ 'cache' ][ '_method' ] == 'memcache' )
			{
				if ( class_exists( "Memcache" ) )
				{
					$this->cachelib = new \Persephone\Cache\Memcache( $this->Registry );
					if ( $this->cachelib->crashed )
					{
						throw new \Persephone\Exception( __METHOD__ . " says: Memcache failed to connect!" );
					}
				}
				else
				{
					$this->Registry->config[ 'performance' ][ 'cache' ][ '_method' ] = "diskcache";
					\Persephone\Registry::logger__do_log( __METHOD__ . " says: PHP-Memcache not found! Reverting to Disk-cache...", "WARNING" );
				}
			}

			//-------
			// APC
			//-------

			elseif ( $this->Registry->config[ 'performance' ][ 'cache' ][ '_method' ] == 'apc' )
			{
				if ( function_exists( "apc_fetch" ) )
				{
					$this->cachelib = new \Persephone\Cache\Apc( $this->Registry );
				}
				else
				{
					$this->Registry->config[ 'performance' ][ 'cache' ][ '_method' ] = "diskcache";
					\Persephone\Registry::logger__do_log( __METHOD__ . " says: PHP-APC not found! Reverting to Disk-cache...", "WARNING" );
				}
			}

			//------------------------
			// diskcache - fallback
			//------------------------

			if ( $this->Registry->config[ 'performance' ][ 'cache' ][ '_method' ] == 'diskcache' )
			{
				$this->cachelib = new \Persephone\Cache\Diskcache( $this->Registry );
			}
		}
		catch ( Exception $e )
		{
			$this->Registry->logger__do_log( "XXXCache - init() : " . $e->getMessage(), "WARNING" );
		}

		//-----------------
		// Did it crash?
		//-----------------

		if ( is_object( $this->cachelib ) and $this->cachelib->crashed )
		{
			unset( $this->cachelib );
			$this->cachelib = null;
			\Persephone\Registry::logger__do_log( __METHOD__ . " says: All available caching mechanisms CRASHED!", "ERROR" );
		}

		//----------------------
		// Load primary cache
		//----------------------

		$this->cache__init_load();
	}


	public function __isset ( $key )
	{

	}



	/**
	 * Initializes caching mechanism and loads preliminary data to cache
	 *
	 * @return  mixed   FALSE on error, ASSOC ARRAY otherwise (with NULL's if no cache is avail, with data otherwise).
	 */
	public function cache__init_load ()
	{
		# What to cache
		$_cache_list = array( "member_groups", "settings", "modules", "skins" );

		# Execute
		$return = $this->cache__do_load( $_cache_list );

		# CACHE LOADED flag
		if ( !defined( 'CACHE_LOADED' ) )
		{
			define( 'CACHE_LOADED', $return === true
				? 1
				: 0 );
		}

		# Log
		$this->Registry->logger__do_log(
			"Cache: " .
			( $return === true
				? "SUCCEEDED"
				: "FAILED" ) . " loading initial cache for keys: " . implode( ",", array_map( array( $this->Registry->Db->platform, "quoteValue" ), $_cache_list ) ),
			$return === true
				? "INFO"
				: "ERROR"
		);

		return $return;
	}


	/**
	 * Gets cache from cache sources, not setting it to any container [for on demand usage]
	 *
	 * @param   $key                        string|string[]         Key(s) to fetch
	 * @param   $all_methods_exhausted      boolean                 Whether recache operation been executed in previous recursions of the method
	 *
	 * @return                              string|boolean|null     (string) Value if not empty, (NULL) NULL if empty, (boolean) FALSE if no cache available; OR (associative array) set of all of those
	 * @throws  \Persephone\Exception
	 */
	public function cache__do_get ( $key = "", $all_methods_exhausted = false )
	{
		if ( empty( $key ) )
		{
			return false;
		}

		if ( is_object( $this->cachelib ) )
		{
			$_cache = array();
			if ( is_array( $key ) )
			{
				foreach ( $key as $_k )
				{
					if ( isset( $this->cache[ $_k ] ) )
					{
						$_cache[ $_k ] = $this->cache[ $_k ];
					}
					else
					{
						$_cache[ $_k ] = $this->cachelib->do_get( $_k );
					}
				}
				unset( $_k );
			}
			else
			{
				if ( isset( $this->cache[ $key ] ) )
				{
					$_cache[ $key ] = $this->cache[ $key ];
				}
				else
				{
					$_cache[ $key ] = $this->cachelib->do_get( $key );
				}
			}

			//-------------------------
			// Any missing cache?
			//-------------------------

			$_cache_array = array(); // Container for keys which missed the fetch
			foreach ( $_cache as $_cache_key => &$_cache_value )
			{
				if ( !empty ( $_cache_value ) )
				{
					$_cache_value = $_cache_value == 'EMPTY'
						? null
						: $_cache_value;
				}
				else
				{
					$_cache_array[ ] = $_cache_key; // Fill-in the container with the keys that missed the fetch
				}
			}

			//-----------------------
			// Generate cache list
			//-----------------------

			if ( count( $_cache_array ) )
			{
				$_cache_list = implode( ",", array_map( array( $this->Registry->Db, "quote" ), $_cache_array ) );

				//-------------------------------------------------------------
				// Missing cache - part 1: Get from DB... and Put in place
				//-------------------------------------------------------------

				$this->Registry->Db->cur_query = array(
					"do"    => "select",
					"table" => "cache_store",
					"where" => "cs_key IN (" . $_cache_list . ")",
				);
				$result = $this->Registry->Db->simple_exec_query();

				if ( count( $result ) )
				{
					foreach ( $result as $_row )
					{
						if ( isset( $_row[ 'cs_value' ] ) )
						{
							if ( empty( $_row[ 'cs_value' ] ) )
							{
								$_row[ 'cs_value' ] = "EMPTY";
							}
							else
							{
								if ( $_row[ 'cs_array' ] )
								{
									$_row[ 'cs_value' ] = unserialize( $_row[ 'cs_value' ] );
								}
							}

							if ( is_object( $this->cachelib ) )
							{
								$this->cachelib->do_put( $_row[ 'cs_key' ], $_row[ 'cs_value' ] );
							}

							$_cache[ $_row[ 'cs_key' ] ] = $_row[ 'cs_value' ] == 'EMPTY'
								? null
								: $_row[ 'cs_value' ];
							unset( $_cache_array[ array_search( $_row[ 'cs_key' ], $_cache_array ) ] );
						}
					}
				}

				//---------------------------------------------------------------------
				// Missing cache - part 2: Even Db lacks some of the cache records.
				// Initiate Recache mechanisms and get the stuff from the Source.
				//---------------------------------------------------------------------

				if ( count( $_cache_array ) and !$all_methods_exhausted )
				{
					foreach ( $_cache_array as $_k )
					{
						$_recache      = new Cache\Recache( $this->Registry );
						$_cache[ $_k ] = $_recache->main( $_k );
					}
				}
			}

			return is_array( $key )
				? $_cache
				: $_cache[ $key ];
		}
		else
		{
			throw new \Persephone\Exception( __CLASS__ . "::cache__do_get(): No cache abstraction! Can't fetch cache!", "ERROR" );
		}
	}


	/**
	 * Fetches only part of cache-data (i.e., one element of cache-data-array)
	 *
	 * @param   string   $key   Cache to fetch
	 * @param   string   $part  Part to fetch
	 *
	 * @return  mixed    FALSE if part does not exist, cache-data otherwise
	 */
	public function cache__do_get_part ( $key, $part )
	{
		if ( strpos( $part, "," ) !== false )
		{
			$_parts = explode( ",", $part );
		}
		else
		{
			$_parts = array( $part );
		}

		if ( ( $_node = $this->cache__do_get( $key ) ) !== false )
		{
			foreach ( $_parts as $_part )
			{
				if ( isset( $_node[ $_part ] ) )
				{
					$_node = $_node[ $_part ];
				}
				else
				{
					return false;
				}
			}

			return $_node;
		}
		else
		{
			return false;
		}
	}


	/**
	 * Loads cache-data from cache sources to $this->cache container for global use
	 *
	 * @param   array   $_cache_array   Cacheable items/elements
	 *
	 * @return  mixed                   NULL if data not avail, TRUE if data is avail. (implicitly: FALSE on error - db_error etc).
	 */
	public function cache__do_load ( $_cache_array = array() )
	{
		if ( !is_array( $_cache_array ) or !count( $_cache_array ) )
		{
			return false;
		}

		$_problematic_keys = array();
		if ( is_object( $this->cachelib ) )
		{
			$_cache = $this->cache__do_get( $_cache_array );
			foreach ( $_cache_array as $key )
			{
				if ( $_cache[ $key ] !== false )
				{
					$this->cache[ $key ] = $_cache[ $key ];
				}
				else
				{
					$_problematic_keys[ ] = "'" . $key . "'";
				}
			}
		}

		if ( count( $_problematic_keys ) )
		{
			$_msg = "Cache loading completed with some problems! Following keys didn't have valid cache-data associated with them: ";
			$this->Registry->logger__do_log( __CLASS__ . "::cache__do_load(): " . $_msg . implode( ", ", $_problematic_keys ), "WARNING" );
		}

		return true;
	}


	/**
	 * Recache wrapper
	 *
	 * @param   string|string[]     Item/element OR array of those, to recache
	 *
	 * @return  boolean             TRUE for success, FALSE for otherwise
	 */
	public function cache__do_recache ( $key )
	{
		if ( empty( $key ) )
		{
			return false;
		}

		$_problematic_keys = array();
		$_recache_obj      = new \Persephone\Cache\Recache( $this->Registry );

		if ( is_array( $key ) )
		{
			foreach ( $key as $_k )
			{
				if ( !$_recache_obj->main( $_k ) )
				{
					$_problematic_keys[ ] = $_k;
				}
			}
		}
		else
		{
			if ( !$_recache_obj->main( $key ) )
			{
				$_problematic_keys[ ] = $key;
			}
		}

		if ( empty( $_problematic_keys ) )
		{
			$this->Registry->logger__do_log(
				__CLASS__ . "::cache__do_recache: Recache completed SUCCESSFULLY without any problems for keys: " .
				( is_array( $key )
					? implode( ", ", $key )
					: $key ),
				"INFO"
			);

			return true;
		}
		else
		{
			$this->Registry->logger__do_log(
				__CLASS__ . "::cache__do_recache: Recache completed with PARTIAL-to-NO SUCCESS with problems for keys: " .
				( is_array( $key )
					? implode( ", ", $key )
					: $key ) . ". Problematic keys: " . implode( ", ", $_problematic_keys ),
				"ERROR"
			);

			return false;
		}
	}


	/**
	 * Updates cache
	 *
	 * @param    array                      Cache values (name, value, donow)
	 *
	 * @return   boolean                    TRUE if successful, FALSE otherwise
	 * @throws   \Persephone\Exception
	 */
	public function cache__do_update ( $v = array() )
	{
		$_cachelib_return = false;

		$v[ 'donow' ] = isset( $v[ 'donow' ] )
			? $v[ 'donow' ]
			: 0;

		//----------------
		// Next...
		//----------------

		if ( $v[ 'name' ] )
		{
			# Determine 'value' and 'array'...
			if ( isset( $v[ 'value' ] ) and $v[ 'value' ] )
			{
				$value = $v[ 'value' ];
			}
			elseif ( isset( $this->cache[ $v[ 'name' ] ] ) )
			{
				$value = $this->cache[ $v[ 'name' ] ];
			}
			else
			{
				$this->Registry->logger__do_log( "Cache: UPDATE failed; no value provided!", "ERROR" );

				return false;
			}

			if ( !isset( $v[ 'array' ] ) and is_array( $value ) )
			{
				$v[ 'array' ] = 1;
			}

			# Non-DB Caching
			if ( is_object( $this->cachelib ) )
			{
				if ( !$value )
				{
					$value = "EMPTY";
				}
				$_cachelib_return = $this->cachelib->do_update( $v[ 'name' ], $value );

				# Log
				$this->Registry->logger__do_log(
					"Cache: UPDATE via Abstraction " .
					( $_cachelib_return === false
						? "failed"
						: "succeeded" ) . " for key '" . $v[ 'name' ] . "'",
					$_cachelib_return === false
						? "ERROR"
						: "INFO"
				);
			}

			# DB Caching
			if ( $v[ 'array' ] )
			{
				$value = serialize( $value );
			}

			$this->Registry->Db->cur_query = array(
				'do'       => "replace",
				'table'    => "cache_store",
				'set'      => array(
					'cs_array' => ( isset( $v[ 'array' ] )
						? $v[ 'array' ]
						: 0 ),
					'cs_key'   => $v[ 'name' ],
					'cs_value' => $value,
				),
				'force_data_type' => array(
					'cs_array' => "int"
				),
			);

			if ( $v[ 'donow' ] )
			{
				$_db_return = $this->Registry->Db->simple_exec_query();

				# Log
				$this->Registry->logger__do_log(
					"Cache: UPDATE on Database " .
					( $_db_return === false
						? "failed"
						: "succeeded" ) . " for key '" . $v[ 'name' ] . "'",
					$_db_return === false
						? "ERROR"
						: "INFO"
				);

				# Return
				return ( $_cachelib_return !== false and $_db_return !== false );
			}
			else
			{
				$this->Registry->Db->simple_exec_query_shutdown();
			}
		}
		else
		{
			# Log
			throw new Exception( "Cache: UPDATE failed; no key provided! - " . var_export( $v, true ), "ERROR" );
		}
	}


	/**
	 * Removes a key from cache
	 *
	 * @param     string     Cache unique key
	 *
	 * @return    boolean    Whether cache removal was successful or not; TRUE on success, FALSE otherwise
	 */
	public function cache__do_remove ( $key )
	{
		# Cleanup at Db level
		$this->Registry->Db->cur_query = array(
			'do'    => "delete",
			'table' => "cache_store",
			'where' => "cs_key=" . $this->Registry->Db->platform->quoteValue( $key ),
		);
		$this->Registry->Db->simple_exec_query_shutdown();

		# Cleanup at Cache abstraction level
		return $this->cachelib->do_remove( $key );
	}
}
