<?php

if ( ! defined( "INIT_DONE" ) )
{
	print "Improper access! Exiting now...";
	exit();
}

/**
 * Hard-disk Cache Storage
 *
 * @package  Audith CMS codename Persephone
 * @author   Shahriyar Imanov <shehi@imanov.name>
 * @version  1.0
**/

require_once( dirname( __FILE__ ) . "/_interface.php" );

class Cache_Lib implements iCache_Lib
{
	/**
	 * API Object reference
	 *
	 * @var object
	 */
	private $API;

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


	public function __construct ( $identifier="" , API $API )
	{
		# Prelim
		$this->API = $API;

		# Cont.
		if ( ! is_writeable( PATH_CACHE ) )
		{
			$this->crashed = 1;
			return FALSE;
		}

		if ( ! $identifier )
		{
			$this->identifier = md5( uniqid( rand(), TRUE ) );
		}
		else
		{
			$this->identifier = $identifier;
		}

		# Logging
		$this->API->logger__do_performance_log( "Cache-Abstraction - Diskcache Identifier: " . $this->identifier , "INFO" );

		unset( $identifier );

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
			$this->API->logger__do_log( "Cache Abstraction - Diskcache: Cache file for key '" . $key . "' is NOT WRITABLE!" , "ERROR" );
		}

		# Open file for writing
		$fh = fopen( $_cache_file_path , "wb" );
		$this->API->logger__do_log(
				"Cache Abstraction - Diskcache: FOPEN "
					. ( $fh === FALSE ? "failed" : "succeeded" )
					. " for key '" . $key . "'" ,
				$fh === FALSE ? "ERROR" : "INFO"
			);
		if ( ! $fh )
		{
			return FALSE;
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
		$this->API->logger__do_log(
				"Cache Abstraction - Diskcache: FLOCK "
					. ( $_flock === FALSE ? "failed" : "succeeded" )
					. " for key '" . $key . "'" ,
				$_flock === FALSE ? "ERROR" : "INFO"
			);

		# Write
		$_fwrite = fwrite( $fh, $file_content );
		$this->API->logger__do_log(
				"Cache Abstraction - Diskcache: FWRITE "
					. ( $_fwrite === FALSE ? "failed" : "succeeded" )
					. " for key '" . $key . "'" ,
				$_fwrite === FALSE ? "ERROR" : "INFO"
			);

		# Unlock
		$_flock = flock( $fh, LOCK_UN );
		$this->API->logger__do_log(
				"Cache Abstraction - Diskcache: FUNLOCK "
					. ( $_flock === FALSE ? "failed" : "succeeded" )
					. " for key '" . $key . "'" ,
				$_flock === FALSE ? "ERROR" : "INFO"
			);

		# Close file handler
		$_fclose = fclose( $fh );
		$this->API->logger__do_log(
				"Cache Abstraction - Diskcache: FCLOSE "
					. ( $_fclose === FALSE ? "failed" : "succeeded" )
					. " for key '" . $key . "'" ,
				$_fclose === FALSE ? "ERROR" : "INFO"
			);

		# ChMod
		$_chmod = chmod( $_cache_file_path , 0777 );
		$this->API->logger__do_log(
				"Cache Abstraction - Diskcache: CHMOD "
					. ( $_chmod === FALSE ? "failed" : "succeeded" )
					. " for key '" . $key . "'" ,
				$_chmod === FALSE ? "ERROR" : "INFO"
			);
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
?>