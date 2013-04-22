<?php

namespace Persephone\Cache;

if ( !defined( "INIT_DONE" ) )
{
	die( "Improper access! Exiting now..." );
}

/**
 * Cache > Drivers > File-system
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
	 * @var \Persephone\Registry
	 */
	private $Registry;

	/**
	 * Unique ID for cache-filenames
	 *
	 * @var string
	 */
	public $identifier;

	/**
	 * FLAG - Whether abstraction failed or not
	 *
	 * @var integer
	 */
	public $crashed = 0;


	/**
	 * Constructor
	 *
	 * @param    \Persephone\Registry  Registry reference
	 * @param    string                Unique-ID used to hash keys
	 * @return   boolean
	 */
	public function __construct ( \Persephone\Registry $Registry , $identifier = "" )
	{
		$this->Registry = $Registry;

		if ( ! is_writeable( PATH_CACHE ) )
		{
			$this->crashed = 1;
			return false;
		}

		if ( ! $identifier )
		{
			$this->identifier = $this->Registry->Input->server('SERVER_NAME');
		}
		else
		{
			$this->identifier = $identifier;
		}

		# Logging
		$this->Registry->logger__do_performance_log( "Cache-Abstraction - Diskcache Identifier: " . $this->identifier , "INFO" );

		unset( $identifier );

		return true;
	}


	/**
	 * Disconnect from remote cache store
	 *
	 * @return   boolean    Whether or not the disconnect attempt was successful - TRUE on success, FALSE otherwise
	 */
	public function disconnect ()
	{
		return TRUE;
	}


	/**
	 * Put data into remote cache store
	 *
	 * @param       string          Cache unique key
	 * @param       string          Cache value to add
	 * @param       integer         [Optional] Time to live
	 * @return      boolean         Whether cache set was successful or not; TRUE on success, FALSE otherwise
	 */
	public function do_put ( $key , $value , $ttl = 0 )
	{
		//--------------
		// Cache file
		//--------------

		# Check possibly existing cache file - we ignore TTL
		$_cache_file_path = PATH_CACHE . "/" . md5( $this->identifier . $key ) . ".php";
		if ( file_exists(  $_cache_file_path ) and is_file( $_cache_file_path ) and ! is_writable( $_cache_file_path ) )
		{
			throw new \Persephone\Exception( "Cache Abstraction - Diskcache: Cache file for key '" . $key . "' is NOT WRITABLE!" );
		}

		# Open file for writing
		$fh = fopen( $_cache_file_path , "wb" );
		$this->Registry->logger__do_log(
				"Cache Abstraction - Diskcache: FOPEN "
					. ( $fh === FALSE ? "failed" : "succeeded" )
					. " for key '" . $key . "'" ,
				$fh === FALSE ? "ERROR" : "INFO"
			);
		if ( ! $fh )
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
		// $value = "'" . addslashes( $value ) . "'";
		// $file_content = "<" . "?php\n\n" . '$value = ' . $value . ";\n" . $extra_flag . "\n?" . ">";
		$file_content = "<" . "?php\n\n" . '$value' . " = <<<" . strtoupper( $key ) . "\n" . $value . "\n" . strtoupper( $key ) . ";\n" . $extra_flag . "\n?" . ">";

		//-----------------------
		// Write cache to file
		//-----------------------

		# LOCK
		$_flock = flock( $fh, LOCK_EX );
		if ( $_flock === false )
		{
			throw new \Persephone\Exception( "Cache Abstraction - Diskcache: FLOCK failed for key '" . $key . "'" );
		}
		else
		{
			$this->Registry->logger__do_log( "Cache Abstraction - Diskcache: FLOCK succeeded for key '" . $key . "'" , "INFO" );
		}

		# Write
		$_fwrite = fwrite( $fh, $file_content );
		if ( $_fwrite === false )
		{
			throw new \Persephone\Exception( "Cache Abstraction - Diskcache: FWRITE failed for key '" . $key . "'" );
		}
		else
		{
			$this->Registry->logger__do_log( "Cache Abstraction - Diskcache: FWRITE succeeded for key '" . $key . "'" , "INFO" );
		}

		# Unlock
		$_flock = flock( $fh, LOCK_UN );
		if ( $_flock === false )
		{
			throw new \Persephone\Exception( "Cache Abstraction - Diskcache: FUNLOCK failed for key '" . $key . "'" );
		}
		else
		{
			$this->Registry->logger__do_log( "Cache Abstraction - Diskcache: FUNLOCK succeeded for key '" . $key . "'" , "INFO" );
		}

		# Close file handler
		$_fclose = fclose( $fh );
		if ( $_fclose === false )
		{
			throw new \Persephone\Exception( "Cache Abstraction - Diskcache: FCLOSE failed for key '" . $key . "'" );
		}
		else
		{
			$this->Registry->logger__do_log( "Cache Abstraction - Diskcache: FCLOSE succeeded for key '" . $key . "'" , "INFO" );
		}

		# chmod
		$_chmod = chmod( $_cache_file_path , 0777 );
		if ( $_chmod === false )
		{
			throw new \Persephone\Exception( "Cache Abstraction - Diskcache: CHMOD failed for key '" . $key . "'" );
		}
		else
		{
			$this->Registry->logger__do_log( "Cache Abstraction - Diskcache: CHMOD succeeded for key '" . $key . "'" , "INFO" );
		}

		return true;
	}


	/**
	 * Update value in remote cache store
	 *
	 * @param       string          Cache unique key
	 * @param       string          Cache value to set
	 * @param       integer         [Optional] Time to live
	 * @return      boolean         Whether cache update was successful or not; TRUE on success, FALSE otherwise
	 */
	public function do_update ( $key , $value , $ttl = 0 )
	{
		$this->do_remove( $key );
		return $this->do_put( $key, $value, $ttl );
	}


	/**
	 * Retrieve a value from remote cache store
	 *
	 * @param       string          Cache unique key
	 * @return      mixed           Cached value
	 */
	public function do_get ( $key )
	{
		$return_val = "";

		if ( file_exists( PATH_CACHE . "/" . md5( $this->identifier . $key ) . ".php" ) )
		{
			require PATH_CACHE . "/" . md5( $this->identifier . $key ) . ".php";

			// $return_val = stripslashes( $value );
			$return_val = $value;

			if ( isset( $is_array ) AND $is_array == 1 )
			{
				$return_val = unserialize( $return_val );
			}

			if ( isset( $ttl ) AND $ttl > 0 )
			{
				if ( $mtime = filemtime( PATH_CACHE . "/" . md5( $this->identifier . $key ) . ".php" ) )
				{
					if ( time() - $mtime > $ttl )
					{
						return FALSE;
					}
				}
			}
		}

		return $return_val;
	}


	/**
	 * Remove a value in the remote cache store
	 *
	 * @param       string          Cache unique key
	 * @return      boolean         Whether cache removal was successful or not; TRUE on success, FALSE otherwise
	 */
	public function do_remove ( $key )
	{
		if ( file_exists( PATH_CACHE . "/" . md5( $this->identifier . $key ) . ".php" ) )
		{
			@unlink( PATH_CACHE . "/" . md5( $this->identifier . $key ) . ".php" );
		}
	}
}
