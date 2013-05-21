<?php

namespace Persephone\Core\Cache;

if ( !defined( "INIT_DONE" ) )
{
	die( "Improper access! Exiting now..." );
}

/**
 * Cache > Drivers > Diskcache
 *
 * @package  Audith CMS codename Persephone
 * @author   Shahriyar Imanov <shehi@imanov.name>
 * @version  1.0
 */
class Diskcache implements Iface
{
	/**
	 * Registry Reference
	 *
	 * @var \Persephone\Core\Registry
	 */
	private $Registry;

	/**
	 * Unique ID for cache-filenames
	 *
	 * @var string
	 */
	private $identifier;

	/**
	 * FLAG - Whether abstraction failed or not
	 *
	 * @var boolean
	 */
	public $crashed = false;


	/**
	 * Constructor
	 *
	 * @param       \Persephone\Core\Registry    $Registry
	 * @param       string                       $identifier       Unique-ID used to hash keys
	 *
	 * @return      boolean
	 */
	public function __construct ( \Persephone\Core\Registry $Registry, $identifier = "" )
	{
		$this->Registry = $Registry;

		if ( !is_writeable( PATH_CACHE ) )
		{
			$this->crashed = true;

			return false;
		}

		if ( !$identifier )
		{
			$this->identifier = $this->Registry->Input->server( 'SERVER_NAME' );
		}
		else
		{
			$this->identifier = $identifier;
		}

		return true;
	}


	/**
	 * Disconnect from remote cache store
	 *
	 * @return    boolean    Whether or not the disconnect attempt was successful - TRUE on success, FALSE otherwise
	 */
	public function disconnect ()
	{
		return true;
	}


	/**
	 * Put data into remote cache store
	 *
	 * @param       string       $key      Cache unique key
	 * @param       string       $value    Cache value to add
	 * @param       integer      $ttl      [Optional] Time to live
	 *
	 * @return      boolean                Whether cache set was successful or not; TRUE on success, FALSE otherwise
	 * @throws      \Persephone\Exception
	 */
	public function do_put ( $key, $value, $ttl = 0 )
	{
		//--------------
		// Cache file
		//--------------

		# Check possibly existing cache file - we ignore TTL
		try
		{
			$_cache_file_path = PATH_CACHE . "/" . md5( $this->identifier . $key ) . ".php";
			if ( file_exists( $_cache_file_path ) and is_file( $_cache_file_path ) and !is_writable( $_cache_file_path ) )
			{
				throw new \Persephone\Exception( __METHOD__ . " says: Cache file for key '" . $key . "' is NOT WRITABLE!" );
			}
		}
		catch ( \Persephone\Exception $e )
		{
			return false;
		}

		# Open file for writing
		$fh = fopen( $_cache_file_path, "wb" );
		\Persephone\Core\Registry::logger__do_log(
			__METHOD__ . " says: FOPEN " .
			( $fh === false
				? "failed"
				: "succeeded" ) . " for key '" . $key . "'",
			$fh === false
				? "ERROR"
				: "INFO"
		);
		if ( $fh === false )
		{
			return false;
		}

		//-------------------
		// Cache content
		//-------------------

		$extra_flag = "";

		if ( is_array( $value ) )
		{
			$value = serialize( $value );
			$extra_flag .= "\n" . '$is_array = 1;' . "\n\n";
		}

		$extra_flag .= "\n" . '$ttl = ' . $ttl . ";\n\n";
		$file_content = "<" . "?php\n\n" . '$value' . " = <<<'" . strtoupper( $key ) . "'\n" . $value . "\n" . strtoupper( $key ) . ";\n" . $extra_flag . "\n?" . ">";

		//-----------------------
		// Write cache to file
		//-----------------------

		# LOCK
		if ( ( $_flock = flock( $fh, LOCK_EX ) ) === false )
		{
			throw new \Persephone\Exception( __METHOD__ . " says: FLOCK failed for key '" . $key . "'" );
		}
		else
		{
			\Persephone\Core\Registry::logger__do_log( __METHOD__ . " says: FLOCK succeeded for key '" . $key . "'", "INFO" );
		}

		# Write
		if ( ( $_fwrite = fwrite( $fh, $file_content ) ) === false )
		{
			throw new \Persephone\Exception( __METHOD__ . " says: FWRITE failed for key '" . $key . "'" );
		}
		else
		{
			\Persephone\Core\Registry::logger__do_log( __METHOD__ . " says: FWRITE succeeded for key '" . $key . "'", "INFO" );
		}

		# Unlock
		if ( ( $_flock = flock( $fh, LOCK_UN ) ) === false )
		{
			throw new \Persephone\Exception( __METHOD__ . " says: FUNLOCK failed for key '" . $key . "'" );
		}
		else
		{
			\Persephone\Core\Registry::logger__do_log( __METHOD__ . " says: FUNLOCK succeeded for key '" . $key . "'", "INFO" );
		}

		# Close file handler
		if ( ( $_fclose = fclose( $fh ) ) === false )
		{
			throw new \Persephone\Exception( __METHOD__ . " says: FCLOSE failed for key '" . $key . "'" );
		}
		else
		{
			\Persephone\Core\Registry::logger__do_log( __METHOD__ . " says: FCLOSE succeeded for key '" . $key . "'", "INFO" );
		}

		# chmod
		if ( ( $_chmod = chmod( $_cache_file_path, 0777 ) ) === false )
		{
			throw new \Persephone\Exception( __METHOD__ . " says: CHMOD failed for key '" . $key . "'" );
		}
		else
		{
			\Persephone\Core\Registry::logger__do_log( __METHOD__ . " says: CHMOD succeeded for key '" . $key . "'", "INFO" );
		}

		return true;
	}


	/**
	 * Update value in remote cache store
	 *
	 * @param       string       $key      Cache unique key
	 * @param       string       $value    Cache value to set
	 * @param       integer      $ttl      [Optional] Time to live
	 *
	 * @return      boolean                Whether cache update was successful or not; TRUE on success, FALSE otherwise
	 */
	public function do_update ( $key, $value, $ttl = 0 )
	{
		$this->do_remove( $key );
		return $this->do_put( $key, $value, $ttl );
	}


	/**
	 * Retrieve a value from remote cache store
	 *
	 * @param       string     $key     Cache unique key
	 *
	 * @return      mixed               Cached value
	 */
	public function do_get ( $key )
	{
		$return_val = "";

		if ( file_exists( $_cache_file_location = PATH_CACHE . "/" . md5( $this->identifier . $key ) . ".php" ) )
		{
			require $_cache_file_location;

			$return_val = $value;
			if ( isset( $is_array ) and $is_array == 1 )
			{
				$return_val = unserialize( $return_val );
			}

			if ( isset( $ttl ) and $ttl > 0 )
			{
				if ( $mtime = filemtime( $_cache_file_location ) )
				{
					if ( time() - $mtime > $ttl )
					{
						return false;
					}
				}
			}
		}

		return $return_val;
	}


	/**
	 * Remove a value in the remote cache store
	 *
	 * @param       string      $key    Cache unique key
	 *
	 * @return      boolean             Whether cache removal was successful or not; TRUE on success, FALSE otherwise
	 */
	public function do_remove ( $key )
	{
		if ( file_exists( PATH_CACHE . "/" . md5( $this->identifier . $key ) . ".php" ) )
		{
			return unlink( PATH_CACHE . "/" . md5( $this->identifier . $key ) . ".php" );
		}

		return true;
	}
}
