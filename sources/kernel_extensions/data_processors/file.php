<?php

if ( ! defined( "INIT_DONE" ) )
{
	print "Improper access! Exiting now...";
	exit();
}

/**
 * FILE Data Processor
 *
 * @package  Audith CMS codename Persephone
 * @author   Shahriyar Imanov <shehi@imanov.name>
 * @version  1.0
**/

require_once( dirname( __FILE__ ) . "/_interface.php" );

class Data_Processor__File extends Data_Processor
{
	/**
	 * API Object Reference
	 * @var object
	 */
	public $API;

	/**
	 * Faults/errors/exceptions container
	 * @var array
	 */
	public $faults = array();

	/**
	 * Data/content
	 * @var mixed
	 */
	public $data;

	/**
	 * Validated DDL configuration [ACP > COMPONENTS > DDL management]
	 * @var array
	 */
	public $ddl_config__validated = array();

	/**
	 * Image library the script uses - Imagick or GD
	 * @var string
	 */
	private $graphic_library_used = null;


	/**
	 * Contructor
	 * @param    API    API object reference
	 */
	public function __construct ( API $API )
	{
		parent::__construct( $API );

		//-------------------------------
		// Graphic library being used
		//-------------------------------

		$_is_imagick_available  = false;
		$_is_gd_available       = false;
		if ( class_exists( "Imagick" ) )
		{
			$_is_imagick_available = true;
		}
		if ( extension_loaded( "gd" ) )
		{
			$_is_gd_available = true;
		}
		else
		{
			if ( IN_DEV )
			{
				$this->API->logger__do_log( "Neither GD, nor Imagick is available! Install at least one of these two for the application to work!" , "ERROR" );
			}
			return false;
		}
		if ( $this->API->config['medialibrary']['graphic_library_preferred'] == 'imagick' )
		{
			if ( $_is_imagick_available )
			{
				$this->graphic_library_used = $this->API->classes__do_get( "data_processors__file__image__imagick" );
			}
			else
			{
				$this->graphic_library_used = $this->API->classes__do_get( "data_processors__file__image__gd" );
			}
		}
		elseif (  $this->API->config['medialibrary']['graphic_library_preferred'] == 'gd' )
		{
			if ( $_is_gd_available )
			{
				$this->graphic_library_used = $this->API->classes__do_get( "data_processors__file__image__gd" );
			}
			else
			{
				$this->graphic_library_used = $this->API->classes__do_get( "data_processors__file__image__imagick" );
			}
		}
	}


	/**
	 * __toString()
	 */
	public function __toString ()
	{
		return (string) $this->data['content'];
	}


	/**
	 * Content fetch - Processor
	 *
	 * @see Data_Processor::get__do_process()
	 * @param   array   Data to be processed
	 * @return  array   Final data
	 */
	public function get__do_process ( $data )
	{
		# Bring in data being processed
		$this->data = $data;

		//---------------------
		// Preliminary stuff
		//---------------------

		# Running module
		$m =& $this->API->Modules->cur_module;

		# Running subroutine
		$s =& $this->API->Modules->cur_module['running_subroutine'];

		//-----------------
		// Processing...
		//-----------------

		# READ-ONLY mode
		if ( $s['s_service_mode'] == 'read-only' )
		{
			// NOthing for now...
		}
		elseif ( $s['s_service_mode'] == 'read-write' )
		{
			//$this->data['content'] = html_entity_decode( $this->data['content'], ENT_QUOTES, "UTF-8" );
		}

		# Parse raw data
		$_data_parsed = array();
		if ( strpos( $this->data['content'] , chr(0) ) !== false )
		{
			$this->data['content'] = explode( chr(0) , $this->data['content'] );
		}

		$_data_parsed = $this->file__info__do_get( count( $this->data['content'] ) > 1 ? $this->data['content'][0] : $this->data['content'] );
		switch ( $_data_parsed['_f_type'] )
		{
			case 'image':
				# Thumbnails, watermarked-version
				if ( $_data_parsed['_f_thumbs_small'] === false )
				{
					$this->image__do_thumbnails( $_data_parsed , "_S" );
				}
				if ( $_data_parsed['_f_thumbs_medium'] === false )
				{
					$this->image__do_thumbnails( $_data_parsed , "_M" );
				}
				if ( $_data_parsed['_f_wtrmrk'] === false )
				{
					$this->image__do_watermark( $_data_parsed );
				}
				break;

			case 'audio':

				break;

			case 'video':

				break;
		}

		return $this->data['content'] = $_data_parsed;
	}


	/**
	 * Content put - Processor
	 *
	 * 1. handle file upload via _put__do_upload() and get uploaded file information at the end
	 * 2. validate file integrity via _put__do_validate() - return FALSE or fault-code-message packet, on failure
	 * 3. move the uploaded file to its /monthly_for_xxxx_xx subfolder under /data folder - return FALSE or fault-code-message packet, on error
	 * 4. create file record in db - return FALSE or fault-code-message packet, on error
	 * 5. create necessary versions of file - images: watermarked versions, thumbs; vids and audios: previews; executables: signed versions etc - return FALSE or fault-code-message packet, on error
	 * 6. apply default or accompanying security descriptors to the file in the record
	 *
	 * @see Data_Processor::put__do_process()
	 * @return   mixed   (boolean) TRUE on success; (boolean) FALSE or (array) fault-code-message otherwise
	 */
	public function put__do_process ()
	{

	}


	/**
	 * Manages file uploads and returns uploaded file information as an associative array
	 *
	 * @return   mixed   (boolean) TRUE on success; (boolean) FALSE or (array) fault-code-message otherwise
	 */
	private function _put__do_upload ()
	{

	}


	/**
	 * Checks file hex-signature and validates the type
	 */
	private function _put__do_validate ()
	{

	}


	/**
	 * Content delete - Processor
	 *
	 * @return   mixed   (boolean) TRUE on success; (boolean) FALSE or (array) fault-code-message otherwise
	 */
	public function delete__do_process ()
	{

	}


	/**
	 * Validates incoming new DDL creation request
	 *
	 * @see Data_Processor::modules__ddl__do_validate()
	 * @param   array      Clean input via POST
	 * @param   array      Module info
	 * @return  boolean    TRUE on success, FALSE otherwise
	 */
	public function modules__ddl__do_validate ( &$input, &$m )
	{
		$this->faults = array();
		$this->ddl_config__validated = array();

		//---------------------------------------------------------------
		// Parameters of less importance : Name, Label, "Is Required?"
		//---------------------------------------------------------------

		$_list_of_reserved_names = array( "id" , "tags" , "timestamp" , "submitted_by" , "status_published" , "status_locked" );
		$dft_name         =  strtolower( $input['name'] );
		$dft_label        =  $input['label'];
		$dft_is_required  =  $input['is_required'] ? 1 : 0;

		# Clean-up
		if ( empty( $dft_name ) )
		{
			$this->faults[] = array( 'faultCode' => 701, 'faultMessage' => "NAME__IS_REQUIRED" );
			// "<em>Field Name</em> is a required field!"
		}
		elseif ( in_array( $dft_name, $_list_of_reserved_names ) )
		{
			$this->faults[] = array( 'faultCode' => 701, 'faultMessage' => "NAME__IS_A_RESERVED_KEYWORD" );
			// "<em>Field Name</em> cannot have one of the following reserved values - change your entry:<br />&nbsp;&nbsp;&nbsp;<em>" . implode( ", " , $_list_of_reserved_names ) .  "</em>"
		}
		elseif ( ! preg_match( "#^[a-z][a-z0-9_]+$#" , $dft_name ) )
		{
			$this->faults[] = array( 'faultCode' => 701, 'faultMessage' => "NAME__IS_INVALID" );
			// "<em>Field Name</em> must contain only a lowercase alphanumeric and underscore characters, and must not start with any numerical!"
		}
		elseif ( array_key_exists( $dft_name, $m['m_data_definition'] ) or array_key_exists( $dft_name, $m['m_data_definition_bak'] ) )
		{
			$this->faults[] = array( 'faultCode' => 701, 'faultMessage' => "NAME__NOT_AVAILABLE" );
			// "<em>Field Name</em> not available; either already registered, or exists in backups!"
		}
		if ( empty( $dft_label ) )
		{
			$this->faults[] = array( 'faultCode' => 702, 'faultMessage' => "LABEL__IS_REQUIRED" );
			// "<em>Field Label</em> is a required field!"
		}

		//------------------------------------------------------------------
		// Parameters of great importance (from security point of view) :
		// Maxlength, Regex, Default Options and Value, Unique-ness
		//------------------------------------------------------------------

		# SKELETON

		$skel = array(
				'image'                    =>  array(
						'title'                =>  "image",
						'maxlength'            =>  0,                             // @todo Put ACP setting controller here
						'allowed_filetypes'    =>  "",
						'input_regex'          =>  null,
						'request_regex'        =>  null,
						'default_options'      =>  false,
						'default_value'        =>  null,
						'connector_enabled'    =>  true,
						'connector_length_cap' =>  0,
						'is_unique'            =>  0,
						'is_binary'            =>  true,
					),
				'video'                    =>  array(
						'title'                =>  "video",
						'maxlength'            =>  0,                             // @todo Put ACP setting controller here
						'allowed_filetypes'    =>  "",
						'input_regex'          =>  null,
						'request_regex'        =>  null,
						'default_options'      =>  false,
						'default_value'        =>  null,
						'connector_enabled'    =>  true,
						'connector_length_cap' =>  0,
						'is_unique'            =>  0,
						'is_binary'            =>  true,
					),
				'audio'     =>  array(
						'title'                =>  "audio",
						'maxlength'            =>  0,                             // @todo Put ACP setting controller here
						'allowed_filetypes'    =>  "",
						'input_regex'          =>  null,
						'request_regex'        =>  null,
						'default_options'      =>  false,
						'default_value'        =>  null,
						'connector_enabled'    =>  true,
						'connector_length_cap' =>  0,
						'is_unique'            =>  0,
						'is_binary'            =>  true,
					),
				'any'       =>  array(
						'title'                =>  "any",
						'maxlength'            =>  0,                             // @todo Put ACP setting controller here
						'allowed_filetypes'    =>  "",
						'input_regex'          =>  null,
						'request_regex'        =>  null,
						'default_options'      =>  false,
						'default_value'        =>  null,
						'connector_enabled'    =>  true,
						'connector_length_cap' =>  0,
						'is_unique'            =>  0,
						'is_binary'            =>  true,
					),
			);

		//---------------------------
		// Validation & Processing
		//---------------------------

		# SUB-TYPE: Validation...
		if ( ! isset( $input['subtype'] ) )
		{
			$_skel_subtype_key = 0;
		}
		else
		{
			$_skel_subtype_key = $input['subtype'];
		}

		if ( ! array_key_exists( $_skel_subtype_key, $skel ) )
		{
			$this->faults[] = array( 'faultCode' => 706, 'faultMessage' => "SUBTYPE__IS_INVALID" );
			// "No such data-subtype is defined: <em>" . $input[ $_form_field_name ] . "</em> (for data-type: <em>file</em>)!"
		}
		else
		{
			# SUB-TYPE: Processing...
			$dft_subtype = $skel[ $_skel_subtype_key ]['title'];
		}
		$_skel_subtype_node =& $skel[ $dft_subtype ];


		# Return of Critical Faults - Level 1
		if ( count( $this->faults ) )
		{
			return false;
		}

		# ALLOWED-FILETYPES: Validation...
		$dft_allowed_filetypes = $input['allowed_filetypes'];
		$_mimelist_cache = $this->API->Cache->cache__do_get("mimelist");
		if ( isset( $dft_allowed_filetypes ) and !empty( $dft_allowed_filetypes ) )
		{
			foreach ( $dft_allowed_filetypes as $_ft )
			{
				if ( ! array_key_exists( $_ft, $_mimelist_cache['by_ext'] ) )
				{
					$this->faults[] = array( 'faultCode' => 707, 'faultMessage' => "ALLOWED_FILETYPES__IS_INVALID" );
					// "One or more of the selected file-types appear to be invalid! Please try again for possible configuration updates..."
					continue;
				}
			}
			$dft_allowed_filetypes = implode( "," , $dft_allowed_filetypes );
		}
		else
		{
			$this->faults[] = array( 'faultCode' => 707, 'faultMessage' => "ALLOWED_FILETYPES__IS_REQUIRED" );
			// &quot;Allowed File-types&quot; is a required field.
		}


		# MAXLENGTH: Validation & Processing...
		$dft_maxlength = $_skel_subtype_node['maxlength'];
		if ( isset( $input['maxlength'] ) )
		{
			if ( false === $dft_maxlength = $this->API->Input->file__filesize__do_parse( $input['maxlength'] ) )
			{
				$this->faults[] = array( 'faultCode' => 709, 'faultMessage' => "MAXFILESIZE__INVALID_SYNTAX" );
				// Invalid syntax for &quot;Maximum Filesize&quot; field! At least, enter &quot;0&quot; to disable the setting.
			}
		}

		# DEFAULT VALUE: Validation...
		$dft_default_value = $_skel_subtype_node['default_value'];

		# CONNECTORS & MAX_NR_OF_ITEMS: Processing...
		$dft_connector_enabled = ( isset( $input['connector_enabled'] ) )
			?
			( $input['connector_enabled'] ? 1 : 0 )
			:
			null;
		$dft_connector_length_cap = ( isset( $input['connector_length_cap'] ) )
			?
			( $dft_connector_enabled ? intval( $input['connector_length_cap'] ) : null )
			:
			null;

		//-----------
		// Errors?
		//-----------

		if ( count( $this->faults ) )
		{
			return false;
		}

		//--------------------------------------------------------------
		// Still here? Continue...
		// Updating Module-records and Altering Module content-tables
		//--------------------------------------------------------------

		$this->ddl_config__validated = array(
				'm_unique_id'          =>  $m['m_unique_id'],
				'name'                 =>  $dft_name,
				'label'                =>  $dft_label,
				'type'                 =>  "file",
				'subtype'              =>  $dft_subtype,
				'allowed_filetypes'    =>  $dft_allowed_filetypes,
				'maxlength'            =>  $dft_maxlength,
				'connector_enabled'    =>  $dft_connector_enabled,
				'connector_length_cap' =>  $dft_connector_length_cap,
				'is_required'          =>  $dft_is_required,
			);

		return true;
	}


	/**
	 * Checks whether the chosen data-field is eligible to be a Title-field or not
	 *
	 * @see Data_Processor::modules__ddl__is_eligible_for_title()
	 * @param    array     DDL-information of the field
	 * @return   boolean   TRUE if yes, FALSE otherwise
	 */
	public function modules__ddl__is_eligible_for_title ( &$ddl_information )
	{
		return false;
	}


	/**
	 * Parses file-resource-location of 3 types: 1) (array) File-info, 2) (string) File-path, either relative or absolute, 3) (string) File-hash
	 *
	 * @param   mixed   One of the following: 1) (array) File-info, 2) (string) File-path, either relative or absolute, 3) (string) File-hash
	 * @return  mixed   (array) Parsed file-resource on success; (boolean) FALSE otherwise
	 */
	protected function file__resource__do_parse ( $file_resource )
	{
		# If its an array, it is file-info
		if ( is_array( $file_resource ) and $file_resource['_diagnostics']['_init_done'] === true )
		{
			return $file_resource;
		}
		elseif ( count( $file_resource ) == 1 )
		{
			$file_resource = str_replace( "\\", "\/", $file_resource );

			# A file-path?
			if ( strpos( $file_resource , '/' ) !== false )
			{
				if ( ! file_exists( $file_resource ) or ! is_file( $file_resource ) )
				{
					return false;
				}
				$_file_hash = hash_file( "md5" , $file_resource );
				return $this->file__info__do_get( $_file_hash );
			}

			# A file-hash?
			elseif ( preg_match( '/^[a-z0-9]{32}$/i' , $file_resource ) )
			{
				return $this->file__info__do_get( $file_resource );
			}
		}

		# Still here? Bad...
		return false;
	}


	/**
	 * Fetches file information from Cache and does basic parsing on it
	 *
	 * @param    string    32-byte-long file checksum hash
	 * @return   mixed     (array) File information on SUCCESS; (boolean) FALSE otherwise
	 */
	public function file__info__do_get ( $identifier )
	{
		//----------------------------------------
		// Identifier - Either f_id or f_hash
		//----------------------------------------

		if ( preg_match( '/^[a-z0-9]{32}$/i' , $identifier ) )
		{
			$identifier = strtolower( $identifier );
		}
		elseif ( preg_match( '/^\d+$/' , $identifier ) )
		{
			$identifier = intval( $identifier );
		}
		else
		{
			return false;
		}

		//-------------------
		// Fetch file-info
		//-------------------

		if ( ( $file_info = $this->API->Cache->cache__do_get( "fileinfo_" . $identifier ) ) === false )
		{
			return false;
		}

		$file_info['_diagnostics'] = array( '_init_done' => false, 'file_exists' => false, 'thumbs_ok' => false );

		//------------------------------------
		// Preliminary parsing of file-info
		//------------------------------------

		# f_hash
		$file_info['f_hash'] = strtolower( $file_info['f_hash'] );

		# _f_type, _f_subtype
		if ( strpos( $file_info['f_mime'], "/" ) !== false )
		{
			$_mime_exploded = explode( "/" , $file_info['f_mime'] );
			$file_info['_f_type'] = $_mime_exploded[0];
			$file_info['_f_subtype'] = $_mime_exploded[1];
			unset( $_mime_exploded );
		}

		# _f_location, _f_dirname - Folder the file is residing
		$file_info['_f_location'] =
			PATH_ROOT_VHOST . "/data/"
			. ( $file_info['_f_dirname'] = "monthly_for_" . date( "Y_m", $file_info['f_timestamp'] ) )
			. "/" . $file_info['f_hash'] . "." . $file_info['f_extension'];
		if ( file_exists( $file_info['_f_location'] ) and is_file( $file_info['_f_location'] ) )
		{
			$file_info['_diagnostics']['file_exists'] = true;
		}

		# f_name
		$file_info['f_name'] = explode( "\n" , $file_info['f_name'] );

		# f_mtime
		$file_info['_f_mtime'] = date( "r" , filemtime( $file_info['_f_location'] ) );

		# Thumbnails
		if ( isset( $file_info['_f_type'] ) and $file_info['_f_type'] == 'image' )
		{
			$file_info['_f_thumbs_small']  = false;
			$file_info['_f_thumbs_medium'] = false;
			$file_info['_f_wtrmrk']        = false;

			if ( file_exists( $_location = $this->API->Input->file__filename__attach_suffix( $file_info['_f_location'] , "_S" ) ) and is_file( $_location ) and is_readable( $_location ) )
			{
				$file_info['_f_thumbs_small'] = true;
			}
			if ( file_exists( $_location = $this->API->Input->file__filename__attach_suffix( $file_info['_f_location'] , "_M" ) ) and is_file( $_location ) and is_readable( $_location ) )
			{
				$file_info['_f_thumbs_medium'] = true;
			}
			if ( $file_info['_f_thumbs_small'] and $file_info['_f_thumbs_medium'] )
			{
				$file_info['_diagnostics']['thumbs_ok'] = true;
			}

			# Watermark
			if ( file_exists( $_location = $this->API->Input->file__filename__attach_suffix( $file_info['_f_location'] , "_W" ) ) and is_file( $_location ) and is_readable( $_location ) )
			{
				$file_info['_f_wtrmrk'] = true;
			}
		}

		# INIT flag - need to make sure it is our info-fetch
		$file_info['_diagnostics']['_init_done'] = true;

		# Return
		return $file_info;
	}


	/**
	 * Gets the soft (symbolic) link for the given file, and creates a new one if not found
	 *
	 * @param     mixed        One of following: 1) (array) valid File-resource, 2) (string) File-path, either relative or absolute, 3) (string) File-hash
	 * @param     array        Additional parameters such as: file-name as requested, user-id, user-ip-address etc
	 * @return    mixed        Link-info on success, FALSE otherwise
	 */
	public function file__link__do_get ( $file_resource , $params = array() )
	{
		//------------------------------------------------
		// Let's make sure we have valid File-resource
		//------------------------------------------------

		$file_resource = $this->file__resource__do_parse( $file_resource );

		//----------------
		// Continue...
		//----------------

		# Don't Re-cache!!!
		if
		(
			( $_link_info = $this->API->Cache->cache__do_get(
					"linkinfo_"
						. $file_resource['f_hash']
						. "_"
						. $params['m_id'],
					true
				) ) === false
		)
		{
			return $this->_file__link__do_set( $file_resource , $params );
		}

		return $_link_info;
	}


	/**
	 * Creates a soft (symbolic) link for the given file
	 *
	 * @param     mixed        One of following: 1) (array) valid File-resource, 2) (string) File-path, either relative or absolute, 3) (string) File-hash
	 * @param     array        Additional parameters such as: file-name as requested, user-id, user-ip-address etc
	 * @return    mixed        Link-info on success, FALSE otherwise
	 */
	private function _file__link__do_set ( $file_resource , $params )
	{
		//------------------------------------------------
		// Let's make sure we have valid File-resource
		//------------------------------------------------

		$file_resource = $this->file__resource__do_parse( $file_resource );

		//------------------
		// Link directory
		//------------------

		$_dirname = "/static/" . strtoupper( md5( $file_resource['_f_dirname'] ) );

		if ( ! file_exists( PATH_ROOT_WEB . $_dirname ) or ! is_dir( PATH_ROOT_WEB . $_dirname ) )
		{
			$_mkdir = mkdir( PATH_ROOT_WEB . $_dirname , 0777 , true );
			$this->API->logger__do_log(
					"Modules - Data_Processors - FILE: "
						. ( $_mkdir === false ? "Failed" : "Succeeded" )
						. " to MKDIR '" . PATH_ROOT_WEB . $_dirname . "'" ,
					$_mkdir === false ? "ERROR" : "INFO"
				);
		}

		$_chdir = chdir( PATH_ROOT_WEB . $_dirname );
		$this->API->logger__do_log(
				"Modules - Data_Processors - FILE: "
					. ( $_chdir === false ? "Failed" : "Succeeded" )
					. " to CHDIR to '" . PATH_ROOT_WEB . $_dirname . "'" ,
				$_chdir === false ? "ERROR" : "INFO"
			);

		//---------------------
		// Data gathering
		//---------------------

		$_link_info = array(
				'f_hash'        => $file_resource['f_hash'],
				'l_hash'        => $params['_enable_ip_check'] === true
					?
					md5( $file_resource['f_hash'] . $params['m_id'] . $params['m_ip_address'] )
					:
					md5( $file_resource['f_hash'] . $params['m_id'] ),
				'l_time_to_die' => $params['time_to_die'],
				'm_id'          => $params['m_id'],
				'm_ip_address'  => $params['m_ip_address'],
			);

		//-------------------
		// Actual linking
		//-------------------

		if ( file_exists( $_link_info['l_hash'] ) and ! is_dir( $_link_info['l_hash'] ) )
		{
			# Is it writable?
			if ( ! is_writable( $_link_info['l_hash'] ) )
			{
				# No?! Bad, log it...
				$this->API->logger__do_log(
						"Modules - Data_Processors - FILE: Symbolic-link marked for deletion is not writable: '/static/" . $_link_info['l_hash'] . "'",
						"WARNING"
					);
			}

			# Good, delete it
			if ( ( $_unlink = unlink( $_link_info['l_hash'] ) ) === false )
			{
				$this->API->logger__do_log(
						"Modules - Data_Processors - FILE: Symbolic-link marked for deletion could not be deleted: '/static/" . $_link_info['l_hash'] . "'",
						"ERROR"
					);
				return false;
			}
		}

		$_link = symlink( $file_resource['_f_location'] , $_link_info['l_hash'] );

		//-----------------------
		// Db-record and cache
		//-----------------------

		if ( $_link === true )
		{
			$_link_info['l_name'] = $_dirname . "/" . $_link_info['l_hash'];
			$this->API->Db->cur_query = array(
					'do'               =>  "replace",
					'table'            =>  "media_library_links",
					'set'              =>  array(
							'f_hash'         => $_link_info['f_hash'],
							'l_hash'         => $_link_info['l_hash'],
							'l_name'         => $_link_info['l_name'],
							'l_time_to_die'  => $_link_info['l_time_to_die'],
							'm_id'           => $_link_info['m_id'],
							'm_ip_address'   => new Zend_Db_Expr(
									"CONV( INET_ATON( "
										. $this->API->Db->db->quote( $_link_info['m_ip_address'] )
										. " ), 10, 2 )"
								),
						),
					'force_data_type'  => array(
							'm_id'           => "int",
						),
				);
			$_db_result = $this->API->Db->simple_exec_query_shutdown();
			$this->API->logger__do_log(
					"Modules - Data_Processors - FILE: "
						. ( $_db_result === false ? "Failed" : "Succeeded" )
						. " to STORE link-info for '" . "linkinfo_" . $_link_info['f_hash'] . "_" . $_link_info['m_id'] . "' in Db." ,
					$_db_result === false ? "ERROR" : "INFO"
				);

			# Recache
			$_recache_processor_obj = $this->API->classes__do_get( "Recache" );
			$_recache_processor_obj->main( "linkinfo_" . $_link_info['f_hash'] . "_" . $_link_info['m_id'] );

			# Return the link details
			return $_link_info;
		}

		# Still here? We have a problem then...
		return false;
	}


	/**
	 * Creates a thumbnail for the given image-file, using the given width and height, and saves the final file using the suffix provided in the original folder.
	 *
	 * @uses      PHP PECL Imagick extension
	 *
	 * @param     mixed        One of following: 1) (array) valid File-resource, 2) (string) File-path, either relative or absolute, 3) (string) File-hash
	 * @param     string       Suffix to use when saving the thumbnail file
	 * @return    boolean      TRUE on success, FALSE otherwise
	 */
	public function image__do_thumbnails ( $file_resource, $file_suffix )
	{
		//------------------------
		// Parse file-resource
		//------------------------

		$file_resource = $this->file__resource__do_parse( $file_resource );

		//----------------------------
		// Do we have actual file?
		//----------------------------

		if ( ! $file_resource['_diagnostics']['file_exists'] )
		{
			$this->logger__do_log( "Media ['" . $file_resource['_f_location'] . "'] not exists or inaccessible!" , "ERROR" );
			return false;
		}

		//------------------------------
		// Is it an image file?
		//------------------------------

		if ( $file_resource['_f_type'] != 'image' )
		{
			$this->logger__do_log( "Media ['" . $file_resource['f_hash'] . "'] is not an image file!" , "ERROR" );
			return false;
		}

		//--------------
		// Dimensions
		//--------------

		switch ( $file_suffix )
		{
			case '_S':
				$_position_of_x_in_dimension_string = strpos( $this->API->config['medialibrary']['thumb_small_dimensions'] , "x" );
				if ( strpos( $_position_of_x_in_dimension_string , "x" ) !== false and strpos( $_position_of_x_in_dimension_string , "x" ) !== 0 )
				{
					$_dimensions = explode( "x" , strtolower( $this->API->config['medialibrary']['thumb_small_dimensions'] ) );
				}
				else
				{
					# We have problematic thumbnail dimension setting, let's revert to default value
					$_cache__dimensions = $this->API->Cache->cache__do_get_part( "settings" , "by_key,medialibrary,thumb_small_dimensions,conf_default" );
					$_dimensions = explode( "x" , $_cache__dimensions );
				}
				break;

			case '_M':
				$_position_of_x_in_dimension_string = strpos( $this->API->config['medialibrary']['thumb_medium_dimensions'] , "x" );
				if ( strpos( $_position_of_x_in_dimension_string , "x" ) !== false and strpos( $_position_of_x_in_dimension_string , "x" ) !== 0 )
				{
					$_dimensions = explode( "x" , strtolower( $this->API->config['medialibrary']['thumb_medium_dimensions'] ) );
				}
				else
				{
					# We have problematic thumbnail dimension setting, let's revert to default value
					$_cache__dimensions = $this->API->Cache->cache__do_get_part( "settings" , "by_key,medialibrary,thumb_medium_dimensions,conf_default" );
					$_dimensions = explode( "x" , $_cache__dimensions );
				}
				break;
			default:
				$this->API->logger__do_log( "Modules - Data Processors - FILE::image__do_thumbnails() : Invalid thumbnail file-prefix provided!" );
				return false;
				break;

		}

		//--------------
		// Continue...
		//--------------

		return $this->graphic_library_used->do_thumbnails( $file_resource , $file_suffix , $_dimensions[0] , $_dimensions[1] );
	}


	/**
	 * Adds a watermark to an image
	 *
	 * @param     mixed     One of following: 1) (array) valid File-resource, 2) (string) File-path, either relative or absolute, 3) (string) File-hash
	 * @return    boolean   TRUE on success, FALSE otherwise
	 */
	public function image__do_watermark ( $file_resource )
	{
		//------------------------
		// Parse file-resource
		//------------------------

		$file_resource = $this->file__resource__do_parse( $file_resource );

		//----------------------------
		// Do we have actual file?
		//----------------------------

		if ( ! $file_resource['_diagnostics']['file_exists'] )
		{
			$this->API->logger__do_log( "Media ['" . $file_resource['_f_location'] . "'] not exists or inaccessible!" , "ERROR" );
			return false;
		}

		//------------------------------
		// Is it an image file?
		//------------------------------

		if ( $file_resource['_f_type'] != 'image' )
		{
			$this->logger__do_log( "Media ['" . $file_resource['f_hash'] . "'] is not an image file!" , "ERROR" );
			return false;
		}

		return $this->graphic_library_used->do_watermark( $file_resource );
	}
}