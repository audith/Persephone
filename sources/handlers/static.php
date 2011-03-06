<?php
if ( ! defined( "INIT_DONE" ) )
{
	print "Improper access! Exiting now...";
	exit();
}

/**
 * Handlers : Static-Media Server
 *
 * @package  Audith CMS codename Persephone
 * @author   Shahriyar Imanov <shehi@imanov.name>
 * @version  1.0
 */

class Module_Handler
{
	/**
	 * API Object Reference
	 * @var object
	 */
	private $API;

	/**
	 * Main container for retrieved content
	 * @var mixed
	 */
	private $content;

	/**
	 * Default HTTP header set for the module
	 * @var array
	 */
	public $http_headers_default = array();

	/**
	 * Module Processor Map [method access]
	 * @var array
	 */
	public $processor_map = array();

	/**
	 * Module's currently running subroutine
	 * @var array
	 */
	private $running_subroutine = array();

	/**
	 * Module Structural Map
	 * @var array
	 */
	public $structural_map = array();


    /**
	 * Constructor - Inits Handler
	 *
	 * @param    API    API Object Reference
	 */
	public function __construct ( API $API )
    {
		//-----------
		// Prelim
		//-----------
    	$this->API = $API;

		//------------------
		// STRUCTURAL MAP
		//------------------

		$this->structural_map = array(
				'm_subroutines'                  =>
					array(
							'download'                          =>
								array(
										's_name'                         => 'download',
										's_service_mode'                 => 'read-only',
										's_pathinfo_uri_schema'          => 'download\/(?:(?P<file_variation>[a-z]+)\-)?(?P<identifier>[0-9]+)(?:\/(?P<f_name>[^/]+))?',
										's_pathinfo_uri_schema_parsed'   => 'download\/(?:(?P<file_variation>[a-z]+)\-)?(?P<identifier>[0-9]+)(?:\/(?P<f_name>[^/]+))?',
										's_qstring_parameters'           => array(
												'file_variation'                    => array(
														'request_regex'                       => '[a-z]+',
														'_is_mandatory'                       => FALSE,
													),
												'identifier'                        => array(
														'request_regex'                       => '[0-9]+',
														'_is_mandatory'                       => TRUE,
													),
												'f_name'                            => array(
														'request_regex'                       => '[^/]+',
														'_is_mandatory'                       => FALSE,
													),
											),
										's_fetch_criteria'               => array(),
										's_data_definition'              => array(),
										'm_unique_id'                    => "{712C0C6D-EA8D425C-40A00129-C6426729}",
									),
							'stream'                            =>
								array(
										's_name'                         => 'stream',
										's_service_mode'                 => 'read-only',
										's_pathinfo_uri_schema'          => 'stream\/(?:(?P<file_variation>[a-z]+)\-)?(?P<identifier>[0-9]+)(?:\/(?P<f_name>[^/]+))?',
										's_pathinfo_uri_schema_parsed'   => 'stream\/(?:(?P<file_variation>[a-z]+)\-)?(?P<identifier>[0-9]+)(?:\/(?P<f_name>[^/]+))?',
										's_qstring_parameters'           => array(
												'file_variation'                    => array(
														'request_regex'                       => '[a-z]+',
														'_is_mandatory'                       => FALSE,
													),
												'identifier'                        => array(
														'request_regex'                       => '[0-9]+',
														'_is_mandatory'                       => TRUE,
													),
												'f_name'                            => array(
														'request_regex'                       => '[^/]+',
														'_is_mandatory'                       => FALSE,
													),
											),
										's_fetch_criteria'               => array(),
										's_data_definition'              => array(),
										'm_unique_id'                    => "{712C0C6D-EA8D425C-40A00129-C6426729}",
									),
							'upload'                            =>
								array(
										's_name'                         => 'upload',
										's_service_mode'                 => 'read-only',
										's_pathinfo_uri_schema'          => 'upload',
										's_pathinfo_uri_schema_parsed'   => 'upload',
										's_qstring_parameters'           => array(),
										's_fetch_criteria'               => array(),
										's_data_definition'              => array(),
										'm_unique_id'                    => "{712C0C6D-EA8D425C-40A00129-C6426729}",
									),
							'delete'                            =>
								array(
										's_name'                         => 'delete',
										's_service_mode'                 => 'read-only',
										's_pathinfo_uri_schema'          => 'delete-(?P<f_id>[1-9][0-9]*)',
										's_pathinfo_uri_schema_parsed'   => 'delete-(?P<f_id>[1-9][0-9]*)',
										's_qstring_parameters'           => array(
												'f_id'                              => array(
														'request_regex'                       => '[1-9][0-9]*',
														'_is_mandatory'                       => TRUE,
													),
											),
										's_fetch_criteria'               => array(),
										's_data_definition'              => array(),
										'm_unique_id'                    => "{712C0C6D-EA8D425C-40A00129-C6426729}",
									),
						),
			);
    }


	/**
	 * content__do()
	 */
	public function content__do ( &$running_subroutine, $action )
	{
		$this->running_subroutine = $running_subroutine;

		$this->processor_map = array(
				'download'                       =>
					array(
							'default'                              => "download",
						),
				'stream'                         =>
					array(
							'default'                              => "stream",
						),
				'upload'                         =>
					array(
							'default'                              => "upload",
							'resume'                               => "upload__do_resume",
						),
				'delete'                         =>
					array(
							'default'                              => "delete",
						),
			);

		if (
			! isset( $this->processor_map[ $this->running_subroutine['s_name'] ] )
			or
			! isset( $this->processor_map[ $this->running_subroutine['s_name'] ][ $action ] )
		)
		{
			header( "HTTP/1.1 400 Bad Request" );
		}
		else
		{
			$_methods = $this->processor_map[ $this->running_subroutine['s_name'] ][ $action ];
			$_methods = explode( "|" , $_methods );

			foreach ( $_methods as $_method_name )
			{
				/* Each method alters the content, and the subsequent method uses
				   that altered version (using references) and alters it at the end. */
				$this->content['content'] = $this->$_method_name();
			}
			return $this->content;
		}
	}


	/**
	 * Deletes file from Media-Library
	 *
	 * @todo User authorization [ACL]
	 */
	private function delete ()
	{
		try
		{
			//----------------------
			// Is it a duplicate?
			//----------------------

			$_file_processor_obj = $this->API->classes__do_get( "data_processors__file" );
			if ( ( $_file_info = $_file_processor_obj->file__info__do_get( $this->running_subroutine['request']['f_id'] ) ) == FALSE )
			{
				throw new Exception( "Delete failed! File does not exist..." );
			}

			# Original file
			if
			(
				file_exists( $_file_info['_f_location'] )
				and
				(
					! is_file( $_file_info['_f_location'] )
					or
					! is_writable( $_file_info['_f_location'] )
				)
			)
			{
				throw new Exception( "Delete failed! Check file permissions for: <i>" . $_file_info['_f_location'] . "</i> and/or its variations!" );
			}

			# IMAGES - Variations
			if ( $_file_info['_f_type'] == 'image' )
			{
				# _M
				if ( $_file_info['_f_thumbs_medium'] )
				{
					if ( ! is_writable( $_path_to_delete = $this->API->Input->file__filename__attach_suffix( $_file_info['_f_location'] , "_M" ) ) )
					{
						throw new Exception( "Delete failed! Check file permissions for: <i>" . $_path_to_delete . "</i>!" );
					}
				}

				# _S
				if ( $_file_info['_f_thumbs_small'] )
				{
					if ( ! is_writable( $_path_to_delete = $this->API->Input->file__filename__attach_suffix( $_file_info['_f_location'] , "_S" ) ) )
					{
						throw new Exception( "Delete failed! Check file permissions for: <i>" . $_path_to_delete . "</i>!" );
					}
				}

				# _W
				if ( $_file_info['_f_wtrmrk'] )
				{
					if ( ! is_writable( $_path_to_delete = $this->API->Input->file__filename__attach_suffix( $_file_info['_f_location'] , "_W" ) ) )
					{
						throw new Exception( "Delete failed! Check file permissions for: <i>" . $_path_to_delete . "</i>!" );
					}
				}
			}

			//--------------------
			// Actual deletion
			//--------------------

			@unlink( $_file_info['_f_location'] );
			@unlink( $this->API->Input->file__filename__attach_suffix( $_file_info['_f_location'] , "_M" ) );
			@unlink( $this->API->Input->file__filename__attach_suffix( $_file_info['_f_location'] , "_S" ) );
			@unlink( $this->API->Input->file__filename__attach_suffix( $_file_info['_f_location'] , "_W" ) );

			$this->API->logger__do_log( "Modules - Static - delete() : Successfully deleted file [hash: " . $_file_info['f_hash']. "]" , "INFO" );

			//----------------
			// Db cleanup
			//----------------

			$this->API->Db->cur_query = array(
					'do'     => "delete",
					'table'  => "media_library",
					'where'  => "f_id=" . $this->API->Db->db->quote( $_file_info['f_id'] , "INTEGER" ),
				);
			if ( ! $this->API->Db->simple_exec_query() )
			{
				throw new Exception( "Delete failed! Couldn't remove Db-record..." );
			}

			//-----------------
			// Cache cleanup
			//-----------------

			$this->API->Cache->cache__do_remove( "fileinfo_" . $_file_info['f_id'] );
			$this->API->Cache->cache__do_remove( "fileinfo_" . $_file_info['f_hash'] );
			$this->API->Cache->cache__do_recache( "total_nr_of_attachments" );

			//-----------------------
			// Still here? Good...
			//-----------------------

			return array( 'responseCode' => 1, 'responseMessage' => "Delete successful! Refreshing in 2 seconds..." );
		}
		catch ( Exception $e )
		{
			$this->API->logger__do_log( "Modules - Static - delete() : " . $e->getMessage() , "ERROR" );
			return array( 'faultCode' => 0, 'faultMessage' => $e->getMessage() );
		}
	}


	/**
	 * Uploads file into Media-Library
	 *
	 * @throws Exception
	 * @todo User authorization [ACL]
	 */
	private function upload ()
	{
		//-----------
		// Prelim
		//-----------

		$_partition_size = 0;
		if ( intval( $this->API->config['performance']['upload_chunk_size'] ) > 0 )
		{
			$_partition_size = intval( $this->API->config['performance']['upload_chunk_size'] ) * 1024;
		}
		$_source_file_handle = null;
		$_target_file_handle = null;

		try
		{
			if ( ! isset( $this->API->Input->post['fileLength'] ) )
			{
				throw new Exception( "fileLength parameter is missing!" );
			}
			if ( ! isset( $this->API->Input->post['fileName'] ) )
			{
				throw new Exception( "fileName parameter is missing!" );
			}
			if ( ! isset( $this->API->Input->post['md5'] ) )
			{
				throw new Exception( "md5 parameter is missing!" );
			}
			if ( ! isset( $this->API->Input->post['partitionMd5'] ) )
			{
				throw new Exception( "partitionMd5 parameter is missing!" );
			}
			if ( ! isset( $this->API->Input->post['partitionIndex'] ) )
			{
				throw new Exception( "partitionIndex parameter is missing!" );
			}
			if ( ! isset( $this->API->Input->post['partitionCount'] ) )
			{
				throw new Exception( "partitionCount parameter is missing!" );
			}

			$_file_length              = $this->API->Input->post['fileLength'];
			$_file_name                = $this->API->Input->post['fileName'];
			$_file_md5_checksum        = $this->API->Input->post['md5'];
			$_partition_md5_checksum   = $this->API->Input->post['partitionMd5'];
			$_partition_index          = $this->API->Input->post['partitionIndex'];
			$_partition_count          = $this->API->Input->post['partitionCount'];

			$_filename_parsed          = pathinfo( $_file_name );
			$_file_extension           = strtolower( $_filename_parsed['extension'] );
			$_file_mime                = $this->API->Cache->cache__do_get_part( "mimelist" , "by_ext," . $_file_extension . ",type_mime" );

			$_target_file              = PATH_DATA . "/_temp/" . md5( $_file_length . $_file_name ) . "." . $_file_extension;
			$_partition_file_name      = $_FILES['file']['tmp_name'];

			//----------------------
			// Is it a duplicate?
			//----------------------

			$_file_processor_obj = $this->API->classes__do_get( "data_processors__file" );
			if ( ( $_existing_file_record = $_file_processor_obj->file__info__do_get( $_file_md5_checksum ) ) !== FALSE )
			{
				if ( in_array( $_file_name, $_existing_file_record['f_name'] ) )
				{
					throw new Exception( "Duplicate upload attempt! File already exists in our systems..." );
				}
				else
				{
					if ( file_exists( $_target_file ) )
					{
						@unlink( $_target_file );
					}

					$this->API->Db->cur_query = array(
							'do'        =>  "update",
							'tables'    =>  "media_library",
							'set'       =>  array(
									'f_name'      => trim( implode( "\n" , array_merge( $_existing_file_record['f_name'] , array( $_file_name ) ) ) ),
								),
							'where'     =>  "f_hash=" . $this->API->Db->db->quote( $_file_md5_checksum ),
						);
					if ( ! $this->API->Db->simple_exec_query() )
					{
						throw new Exception( "Failed to update Db-record!" );
					}

					$this->API->Cache->cache__do_remove( "fileinfo_" . $_existing_file_record['f_hash'] );
					$this->API->Cache->cache__do_remove( "fileinfo_" . $_existing_file_record['f_id']   );

					$this->API->logger__do_log( "Modules - Static - upload() : Successfully updated file-record [hash: " . $_file_md5_checksum . "]" , "INFO" );
					echo "Message: File ('" . $_file_name . "') successfully uploaded!\n";
					echo "MessageTitle: Upload successful!";
					exit();
				}
			}

			//-----------------------------
			// Is it a valid MIME-type?
			//-----------------------------

			if ( $_file_mime === FALSE )
			{
				throw new Exception( "MIME is invalid or not allowed!" );
			}

			//--------------------------------
			// Is it the last partition?
			//--------------------------------

			$_is_this_last_partition_being_uploaded = ( $_partition_index + 1 == $_partition_count );

			//-------------------------------------------------------------
			// Does the provided checksum value match to the real one?
			//-------------------------------------------------------------

			$_partition_md5_checksum__calculated = hash_file( "md5" , $_partition_file_name );
			if ( $_partition_md5_checksum__calculated != $_partition_md5_checksum )
			{
				throw new Exception( "Checksum mismatch - provided checksum value (for the current partition) does not match to the calculated one!" );
			}

			//------------------------
			// Prepare file-handles
			//------------------------

			if ( ! $_source_file_handle = fopen( $_partition_file_name , "rb" ) )
			{
				throw new Exception( "Failed to access the partition source file (" . $_partition_file_name . ")!" );
			}

			$mode = "r+b";
			if ( ! file_exists( $_target_file ) or ! is_file( $_target_file ) )
			{
				$mode = "wb";
			}
			# 'w' (and 'w+') will truncate the file, but 'a' will always append, even if we seek; so r+ is correct, unless the file doesn't exist.
			if ( ! $_target_file_handle = fopen( $_target_file , $mode ) )
			{
				throw new Exception( "Failed to access the staging file (" . $_target_file .")!" );
			}

			# Note: do not just use mode 'a' and always append to end of file. Due to corruption or other resume behavior, we might
			# need to overrwrite some parts of the file! Additionally, we must use a safe variant of seek, to avoid integer limit problems
			$where = bcmul( $_partition_index , $_partition_size );
			if ( $this->API->Input->file__fseek_safe( $_target_file_handle , $where ) != 0 )
			{
				throw new Exception( "Failed to find resume position!" );
			}

			$_partition_file_size = filesize( $_partition_file_name );    // Input::file__filesize__do_get() not needed here: 1) it is slower, 2) partitions are always small enough
			if ( ! $_is_this_last_partition_being_uploaded and $_partition_file_size != $_partition_size )
			{
				throw new Exception( "Transfered partition is of the wrong size!" );
			}

			if ( stream_copy_to_stream( $_source_file_handle , $_target_file_handle ) != $_partition_file_size )
			{
				throw new Exception( "Failed to append data to destination file!" );
			}

			fclose( $_target_file_handle );
			fclose( $_source_file_handle );

			//----------------------------------------------
			// If we are finished, perform final handling
			//----------------------------------------------

			if ( $_is_this_last_partition_being_uploaded )
			{
				$_file_md5_checksum__calculated = hash_file( "md5" , $_target_file );
				if ( $_file_md5_checksum__calculated != $_file_md5_checksum )
				{
					# The final staging file is broken, delete it so no resume is tried!
					@unlink( $_target_file );
					throw new Exception( "Checksum mismatch - provided checksum value (for final file) does not match to the calculated one!" );
				}

				//--------------------------------
				// Validate file mime and type
				//--------------------------------

				if ( $this->API->Input->file__extension__do_validate( $_target_file ) === TRUE )
				{
					// $_image_size = getimagesize( $_f );
					$_file_mtime = filemtime( $_target_file );
					$this->API->Db->cur_query = array(
							'do'   =>  "replace",
							'table' => "media_library",
							'set'  =>  array(
									'f_hash'      => $_file_md5_checksum,
									'f_name'      => $_file_name,
									'f_extension' => $_file_extension,
									'f_size'      => $_file_length,
									'f_mime'      => $_file_mime,
									'f_timestamp' => new Zend_Db_Expr( "UNIX_TIMESTAMP('" . date( "Ymd" , $_file_mtime ) . "')" ),

								),
						);
					if ( ! $this->API->Db->simple_exec_query() )
					{
						@unlink( $_target_file );
						throw new Exception( "Failed to insert Db-record!" );
					}
					else
					{
						if ( ! file_exists( $_dir_to_move_into = PATH_DATA . "/monthly_for_" . date( "Y_m" , $_file_mtime ) ) or ! is_dir( $_dir_to_move_into ) )
						{
							mkdir( $_dir_to_move_into );
						}
						if ( ! rename( $_target_file , $_dir_to_move_into . "/" . $_file_md5_checksum . "." . $_file_extension ) )
						{
							throw new Exception( "Failed to move the staging file to its final location!" );
						}
					}

					$this->API->logger__do_log( "Modules - Static - upload() : Successfully uploaded file [hash: " . $_file_md5_checksum . "]" , "INFO" );
					echo "Message: File ('" . $_file_name . "') successfully uploaded!\n";
					echo "MessageTitle: Upload successful!";
					exit;
				}
				else
				{
					throw new Exception( "Failed to validate file-mime!" );
				}
			}
			else
			{
				$this->API->logger__do_log( "Modules - Static - upload() : Successfully uploaded partition [file-id: '" . $_file_name . "'; partition: " . ( $_partition_index + 1 ) . "/" . $_partition_count . "]" , "INFO" );
			}
		}
		catch ( Exception $e )
		{
			if ( isset ( $_source_file_handle ) )
			{
				fclose( $_source_file_handle );
			}
			if ( isset( $_target_file_handle ) )
			{
				fclose( $_target_file_handle );
			}
			$this->API->logger__do_log( "Modules - Static - upload() : " . $e->getMessage() , "ERROR" );
			echo "Error: " . $e->getMessage();
		}
		exit();
	}


	/**
	 * Resumes partial upload and is called at the beginning of each upload session.
	 *
	 * @throws Exception
	 * @todo User authorization [ACL]
	 */
	private function upload__do_resume ()
	{
		//-----------
		// Prelim
		//-----------

		$_partition_size = 0;
		if ( intval( $this->API->config['performance']['upload_chunk_size'] ) > 0 )
		{
			$_partition_size = intval( $this->API->config['performance']['upload_chunk_size'] ) * 1024;
		}
		$_source_file_handle = null;
		$_target_file_handle = null;

		try
		{
			if ( ! isset( $this->API->Input->post['fileLength'] ) )
			{
				throw new Exception( "fileLength parameter is missing!" );
			}
			if ( ! isset( $this->API->Input->post['fileName'] ) )
			{
				throw new Exception( "fileName parameter is missing!" );
			}

			$_file_id                  = UNIX_TIME_NOW;
			$_file_length              = $this->API->Input->post['fileLength'];
			$_file_name                = $this->API->Input->post['fileName'];
			$_partition_index          = 0;

			$_filename_parsed          = pathinfo( $_file_name );
			$_file_extension           = strtolower( $_filename_parsed['extension'] );

			$_target_file              = PATH_DATA . "/_temp/" . md5( $_file_length . $_file_name ) . "." . $_file_extension;

			//------------------------------------------------
			// Do we have an incomplete file on the server?
			//------------------------------------------------

			if ( file_exists( $_target_file ) and is_file( $_target_file ) )
			{
				if ( ! is_readable( $_target_file ) )
				{
					throw new Exception( "Can't read the file! Access denied!" );
				}

				# Get file size
				$_file_size = $this->API->Input->file__filesize__do_get( $_target_file );

				# Calculate partition-index
				$_partition_index = bcdiv( $_file_size, $_partition_size, 0 );
			}

			//-----------
			// Output
			//-----------

			header("HTTP/1.1 200 Ok");
			echo "fileId: " . $_file_id . "\n";
			echo "partitionIndex: " . $_partition_index;

			$this->API->logger__do_log( "Modules - Static - upload() : Successfully resumed file-upload [file-name: '" . $_file_name . "'] at index: " . $_partition_index , "INFO" );
		}
		catch ( Exception $e )
		{
			$this->API->logger__do_log( "Modules - Static - upload() : " . $e->getMessage() , "ERROR" );
			echo "Error: " . $e->getMessage();
		}
		exit();
	}


	/**
	 * Serves files as downloads/attachments
	 *
	 * @todo User authorization [ACL]
	 */
	private function download ()
	{
		# Prelim
		$_file_processor_object = $this->API->classes__do_get("data_processors__file");
		$file_info = $_file_processor_object->file__info__do_get( $this->running_subroutine['request']['identifier'] );

		if ( ! isset( $this->running_subroutine['request']['f_name'] ) )
		{
			$this->running_subroutine['request']['f_name'] = $file_info['f_hash'] . "." . $file_info['f_extension'];
		}

		$_file_location = $file_info['_f_location'];

		# Images - watermarked versions, thumbnails etc
		if ( $file_info['_f_type'] == 'image' )
		{
			if ( isset( $this->running_subroutine['request']['file_variation'] ) )
			{
				switch ( $_file_variation = $this->running_subroutine['request']['file_variation'] )
				{
					case 's':
					case 'm':
						if ( ! $file_info['_diagnostics']['thumbs_ok'] )
						{
							$this->_file__image__do_thumbnails( $file_info );
						}
						break;
					case 'w':
						if ( $file_info['_f_wtrmrk'] === FALSE )
						{
							$this->_file__image__do_watermark( $file_info );
						}
						break;
					default:
						# @todo User authorization [ACL]
						$_file_variation = null;
						/*
						$this->API->http_redirect(
								SITE_URL
									. "/static/download/l-"
									. $this->running_subroutine['request']['identifier']
									. "/"
									. $this->running_subroutine['request']['f_name'],
								301
							);
						*/
				}
			}
			$_file_location = ! empty( $_file_variation )
				?
				$this->API->Input->file__filename__attach_suffix( $file_info['_f_location'] , "_" . strtoupper( $_file_variation ) )
				:
				$file_info['_f_location']
				;
		}
		$_file_stats = stat( $_file_location );
		$_file_stats['etag'] = $file_info['f_hash'] . ( isset( $_file_variation ) ? "-" . $_file_variation : "" ) . "-" . $_file_stats['mtime'];

		# If-Modified-Since
		$_request_headers =& $this->API->Input->headers['request'];
		if ( isset( $_request_headers['IF-NONE-MATCH'] ) and $_request_headers['IF-NONE-MATCH'] == $_file_stats['etag'] )
		{
			header( "ETag: " . $_file_stats['etag'] );
			header( "Last-modified: " . date( "r" , $_file_stats['mtime'] ) );
			header( "HTTP/1.1 304 Not modified" );
			exit;
		}
		elseif ( isset( $_request_headers['IF-MODIFIED-SINCE'] ) )
		{
			if ( $_file_stats['mtime'] <= strtotime( $_request_headers['IF-MODIFIED-SINCE'] ) )
			{
				header( "ETag: " . $_file_stats['etag'] );
				header( "Last-modified: " . date( "r" , $_file_stats['mtime'] ) );
				header( "HTTP/1.1 304 Not modified" );
				exit;
			}
		}

		# Is accessible?
		if ( ! is_readable( $_file_location ) )
		{
			header( "HTTP/1.1 403 Forbidden" );                                          // @todo Should be replaced with error-page-handlers
			exit;
		}

		# Disable output-buffering
		if ( $this->API->ob_status )
		{
			if ( $this->API->ob_compression )
			{
				ini_set( "zlib.output_handler" , "0" );
			}
			ob_end_clean();
		}

		# Does file exist?
		if ( ! $file_info['_diagnostics']['file_exists'] )
		{
			header( "HTTP/1.1 404 Resource not found" );                                 // @todo Should be replaced with error-page-handlers
			return null;
		}

		# Open file for reading
		$_fh = fopen( $_file_location , "rb" );
		if ( $_fh === FALSE )
		{
			header( "HTTP/1.1 500 Internal server error!" );                             // @todo Should be replaced with error-page-handlers
			return null;
		}

		$_transmission_begin = 0;
		$_transmission_end   = $_file_stats['size'];

		# Are we resuming?
		if ( isset( $_SERVER['HTTP_RANGE'] ) )
		{
			if ( preg_match( '/bytes=\h*(?P<begin>\d+)-(?P<end>\d*)[\D.*]?/i' , $_SERVER['HTTP_RANGE'] , $_matches ) )
			{
				$_transmission_begin = intval( $_matches['begin'] );
				if ( ! empty( $_matches['end'] ) )
				{
					$_transmission_end = $_matches['end'];
				}
			}
		}

		# Little insurance
		set_time_limit( 0 );

		# Headers - Cache-control, Pragma and Expires headers are sent from Modules class; no need to set those here again
		if ( $_transmission_begin > 0 or $_transmission_end < $file_info['f_size'] )
		{
			header( "HTTP/1.1 206 Partial content" );
		}
		else
		{
			header( "HTTP/1.1 200 Ok" );
		}
		header( "Accept-ranges: bytes" );
		header( "Content-description: Downloads powered by Audith CMS codename Persephone" );
		header( "Content-type: " . $file_info['f_mime'] );
		header( "Content-disposition: attachment; filename=\"" . $this->running_subroutine['request']['f_name'] . "\"" );
		header( "Content-length: " . $_file_stats['size'] );
		header( "Content-range: bytes " . $_transmission_begin . "-" . $_transmission_end . "/" . $file_info['f_size'] );
		header( "Content-transfer-encoding: " . ( $file_info['_f_type'] == 'text' ? "ascii" : "binary" ) );
		header( "Last-modified: " . date( "r" , $_file_stats['mtime'] ) );
		header( "ETag: " . $_file_stats['etag'] );
		header( "X-Md5-checksum: " . $file_info['f_hash'] );
		header( "Connection: close" );

		# File-cursor
		$_file_cursor = $_transmission_begin;
		$this->API->Input->file__fseek_safe( $_fh , $_transmission_begin , SEEK_SET );

		# Send data with bandwidth management
		$_chunk_size      = intval( $this->API->config['performance']['download_chunk_size'] ) * 1024;
		$_transfer_quota  = intval( $this->API->config['performance']['download_speed_limit'] ) * 1024;
		$_time_quota      = 1000000;
		$_timestart       = intval( $this->API->debug__timer_start() );
		while( ! feof( $_fh ) and $_file_cursor < $_transmission_end and ( connection_status() == 0 ) )
		{
			# File-send
			print fread( $_fh, min( $_chunk_size, $_transmission_end - $_file_cursor ) );
			$_file_cursor    += $_chunk_size;

			# Bandwidth quota management
			$_time_quota     -= intval( $this->API->debug__timer_stop( $_timestart ) );
			$_transfer_quota -= $_chunk_size;

			# Out of quota? Then wait for the remainder of 1 second :) and reset counters
			if ( $_transfer_quota <= 0 and $_time_quota > 0 )
			{
				usleep( $_time_quota );
				$_time_quota      = 1000000;
				$_transfer_quota  = intval( $this->API->config['performance']['download_speed_limit'] ) * 1024;
			}
		}

		# Close file
		fclose( $_fh );
		exit;
	}


	/**
	 * Serves files in an inline fashion
	 *
	 * @todo User authorization [ACL]
	 */
	private function stream ()
	{
		# Prelim
		$_file_processor_object = $this->API->classes__do_get("data_processors__file");
		$file_info = $_file_processor_object->file__info__do_get( $this->running_subroutine['request']['identifier'] );

		if ( ! isset( $this->running_subroutine['request']['f_name'] ) )
		{
			$this->running_subroutine['request']['f_name'] = $file_info['f_hash'] . "." . $file_info['f_extension'];
		}

		$_file_location = $file_info['_f_location'];

		# Images - watermarked versions, thumbnails etc
		if ( $file_info['_f_type'] == 'image' )
		{
			if ( isset( $this->running_subroutine['request']['file_variation'] ) )
			{
				switch ( $_file_variation = $this->running_subroutine['request']['file_variation'] )
				{
					case 's':
					case 'm':
						if ( ! $file_info['_diagnostics']['thumbs_ok'] )
						{
							$this->_file__image__do_thumbnails( $file_info );
						}
						break;
					case 'w':
						if ( $file_info['_f_wtrmrk'] === FALSE )
						{
							$this->_file__image__do_watermark( $file_info );
						}
						break;
					default:
						# @todo User authorization [ACL]
						$_file_variation = null;
						/*
						$this->API->http_redirect(
								SITE_URL
									. "/static/download/l-"
									. $this->running_subroutine['request']['identifier']
									. "/"
									. $this->running_subroutine['request']['f_name'],
								301
							);
						*/
				}
			}
			$_file_location = ! empty( $_file_variation )
				?
				$this->API->Input->file__filename__attach_suffix( $file_info['_f_location'] , "_" . strtoupper( $_file_variation ) )
				:
				$file_info['_f_location']
				;
		}
		$_file_stats = stat( $_file_location );
		$_file_stats['etag'] = $file_info['f_hash'] . ( isset( $_file_variation ) ? "-" . $_file_variation : "" ) . "-" . $_file_stats['mtime'];

		# If-Modified-Since
		$_request_headers =& $this->API->Input->headers['request'];
		if ( isset( $_request_headers['IF-NONE-MATCH'] ) and $_request_headers['IF-NONE-MATCH'] == $_file_stats['etag'] )
		{
			header( "ETag: " . $_file_stats['etag'] );
			header( "Last-modified: " . date( "r" , $_file_stats['mtime'] ) );
			header( "HTTP/1.1 304 Not modified" );
			exit;
		}
		elseif ( isset( $_request_headers['IF-MODIFIED-SINCE'] ) )
		{
			if ( $_file_stats['mtime'] <= strtotime( $_request_headers['IF-MODIFIED-SINCE'] ) )
			{
				header( "ETag: " . $_file_stats['etag'] );
				header( "Last-modified: " . date( "r" , $_file_stats['mtime'] ) );
				header( "HTTP/1.1 304 Not modified" );
				exit;
			}
		}

		# Is accessible?
		if ( ! is_readable( $_file_location ) )
		{
			header( "HTTP/1.1 403 Forbidden" );                                          // @todo Should be replaced with error-page-handlers
			exit;
		}

		# Disable output-buffering
		if ( $this->API->ob_status )
		{
			if ( $this->API->ob_compression )
			{
				ini_set( "zlib.output_handler" , "0" );
			}
			ob_end_clean();
		}

		# Does file exist?
		if ( ! $file_info['_diagnostics']['file_exists'] )
		{
			header( "HTTP/1.1 404 Resource not found" );                                 // @todo Should be replaced with error-page-handlers
			return null;
		}

		# Open file for reading
		$_fh = fopen( $_file_location , "rb" );
		if ( $_fh === FALSE )
		{
			header( "HTTP/1.1 500 Internal server error!" );                             // @todo Should be replaced with error-page-handlers
			return null;
		}

		$_transmission_begin = 0;
		$_transmission_end   = $_file_stats['size'];

		# Are we resuming?
		if ( isset( $_SERVER['HTTP_RANGE'] ) )
		{
			if ( preg_match( '/bytes=\h*(?P<begin>\d+)-(?P<end>\d*)[\D.*]?/i' , $_SERVER['HTTP_RANGE'] , $_matches ) )
			{
				$_transmission_begin = intval( $_matches['begin'] );
				if ( ! empty( $_matches['end'] ) )
				{
					$_transmission_end = $_matches['end'];
				}
			}
		}

		# Little insurance
		set_time_limit( 0 );

		# Headers - Cache-control, Pragma and Expires headers are sent from Modules class; no need to set those here again
		if ( $_transmission_begin > 0 or $_transmission_end < $file_info['f_size'] )
		{
			header( "HTTP/1.1 206 Partial content" );
		}
		else
		{
			header( "HTTP/1.1 200 Ok" );
		}
		header( "Accept-ranges: bytes" );
		header( "Content-description: Downloads powered by Audith CMS codename Persephone" );
		header( "Content-type: " . $file_info['f_mime'] );
		header( "Content-disposition: inline; filename=\"" . $this->running_subroutine['request']['f_name'] . "\"" );
		header( "Content-length: " . $_file_stats['size'] );
		header( "Content-range: bytes " . $_transmission_begin . "-" . $_transmission_end . "/" . $file_info['f_size'] );
		header( "Content-transfer-encoding: " . ( $file_info['_f_type'] == 'text' ? "ascii" : "binary" ) );
		header( "Last-modified: " . date( "r" , $_file_stats['mtime'] ) );
		header( "ETag: " . $_file_stats['etag'] );
		header( "X-Md5-checksum: " . $file_info['f_hash'] );
		header( "Connection: close" );

		# File-cursor
		$_file_cursor = $_transmission_begin;
		$this->API->Input->file__fseek_safe( $_fh , $_transmission_begin , SEEK_SET );

		# Send data with bandwidth management
		$_chunk_size      = intval( $this->API->config['performance']['download_chunk_size'] ) * 1024;
		$_transfer_quota  = intval( $this->API->config['performance']['download_speed_limit'] ) * 1024;
		$_time_quota      = 1000000;
		$_timestart       = intval( $this->API->debug__timer_start() );
		while( ! feof( $_fh ) and $_file_cursor < $_transmission_end and ( connection_status() == 0 ) )
		{
			# File-send
			print fread( $_fh, min( $_chunk_size, $_transmission_end - $_file_cursor ) );
			$_file_cursor    += $_chunk_size;

			# Bandwidth quota management
			$_time_quota     -= intval( $this->API->debug__timer_stop( $_timestart ) );
			$_transfer_quota -= $_chunk_size;

			# Out of quota? Then wait for the remainder of 1 second :) and reset counters
			if ( $_transfer_quota <= 0 and $_time_quota > 0 )
			{
				usleep( $_time_quota );
				$_time_quota      = 1000000;
				$_transfer_quota  = intval( $this->API->config['performance']['download_speed_limit'] ) * 1024;
			}
		}

		# Close file
		fclose( $_fh );
		exit;
		/*
		$_params = array(
				'f_name'            => $this->running_subroutine['request']['f_name'],
				'time_to_die'       => null,                                                       // @todo Later to be manipulated by Zend_ACL
				'm_id'              => $this->API->member['id'],
				'm_ip_address'      => $this->API->Session->ip_address,
				'_enable_ip_check'  => FALSE,
			);
		$_link_info = $_file_processor_object->file__link__do_get( $file_info, $_params );
		*/
	}


	/**
	 * Creates thumbnails for FILE data-types of 'image' type
	 *
	 * @param    array       File-info
	 * @return   boolean     TRUE on success; FALSE otherwise
	 */
	private function _file__image__do_thumbnails ( $file_info )
	{
		//----------------------------
		// Do we have actual file?
		//----------------------------

		if ( ! is_array( $file_info ) or empty( $file_info ) or $file_info['_diagnostics']['file_exists'] === FALSE )
		{
			return FALSE;
		}

		//---------------
		// Continue...
		//---------------

		# Check for existing thumbnails, create them if necessary
		if ( ! $file_info['_diagnostics']['thumbs_ok'] )
		{
			# FILE data-processor object instance
			$_file_processor_obj = $this->API->classes__do_get( "data_processors__file" );

			# Small thumbs
			if ( $file_info['_f_thumbs_small'] === FALSE )
			{
				if ( $_file_processor_obj->image__do_thumbnails( $file_info , "_S" ) === TRUE )
				{
					$file_info['_f_thumbs_small'] = TRUE;
				}
				else
				{
					return FALSE;
				}
			}

			# Medium thumbs
			if ( $file_info['_f_thumbs_medium'] === FALSE )
			{
				if ( $_file_processor_obj->image__do_thumbnails( $file_info , "_M" ) === TRUE )
				{
					$file_info['_f_thumbs_medium'] = TRUE;
				}
				else
				{
					return FALSE;
				}
			}
		}

		return TRUE;
	}


	/**
	 * Adds a watermark to an image file
	 *
	 * @param    array      File-info [usually received via FILE::file__info__do_get() ]
	 * @return   boolean    TRUE on success, FALSE otherwise
	 */
	private function _file__image__do_watermark ( $file_info )
	{
		# FILE data-processor object instance
		$_file_processor_obj = $this->API->classes__do_get( "data_processors__file" );

		# Continue
		return $_file_processor_obj->image__do_watermark( $file_info );
	}
}