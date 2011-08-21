<?php

if ( ! defined( "INIT_DONE" ) )
{
	print "Improper access! Exiting now...";
	exit();
}

/**
 * FILE Data Processor - Image - Imagick wrapper
 *
 * @package  Audith CMS codename Persephone
 * @uses     PHP GD lib
 * @author   Shahriyar Imanov <shehi@imanov.name>
 * @version  1.0
**/
final class Data_Processors__File__Image__Gd
{
	/**
	 * API Object reference
	 * @var object
	 */
	public $API;

	/**
	 * Ratio of original image's dimension in a given direction to that of the watermark image
	 * @var integer
	 */
	private $_original_image__to__watermark__ratio = 5;


	/**
	 * Contructor
	 * @param    API    API object reference
	 */
	public function __construct ( API $API )
	{
		$this->API = $API;
		$this->API->logger__do_log( "Loaded GD library." , "INFO" );
	}


	/**
	 * Creates a thumbnail for the given image-file, using the given width and height, and saves the final file using the suffix provided in the original folder.
	 *
	 * @param     array        File-resource
	 * @param     string       Suffix to use when saving the thumbnail file
	 * @param     integer      Width
	 * @param     integer      Height
	 * @return    boolean      TRUE on success, FALSE otherwise
	 */
	public function do_thumbnails ( $file_resource, $file_suffix, $width, $height )
	{
		//------------------------------
		// Do we have the actual file?
		//------------------------------

		if ( ! $file_resource['_diagnostics']['file_exists'] )
		{
			$this->logger__do_log( "Media ['" . $file_resource['f_hash'] . "'] not exists or inaccessible!" , "ERROR" );
			return FALSE;
		}

		//----------------------
		// Image to resize
		//----------------------

		switch ( $file_resource['_f_subtype'] )
		{
			case 'jpeg':
				$image_to_resize = ( imagetypes() and IMG_JPEG ) ?
					imagecreatefromjpeg( $file_resource['_f_location'] ) : FALSE;
				break;
			case 'gif':
				$image_to_resize = ( imagetypes() and IMG_GIF ) ?
					imagecreatefromgif( $file_resource['_f_location'] ) : FALSE;
				break;
			case 'png':
				$image_to_resize = ( imagetypes() and IMG_PNG ) ?
					imagecreatefrompng( $file_resource['_f_location'] ) : FALSE;
				break;
			case 'vnd.wap.wbmp':
				$image_to_resize = ( imagetypes() and IMG_WBMP ) ?
					imagecreatefromwbmp( $file_resource['_f_location'] ) : FALSE;
				break;
			default:
				$image_to_resize = FALSE;
				break;
		}
		if ( $image_to_resize === FALSE )
		{
			return FALSE;
		}

		$_image_to_resize__geometry = array(
				'width'  => $file_resource['f_dimensions']['width'],
				'height' => $file_resource['f_dimensions']['height']
			);

		//----------
		// Resize
		//----------

		# Ratio coefficients - original image
		$_image_to_resize__geometry__ratio_coefficient = $_image_to_resize__geometry['width'] / $_image_to_resize__geometry['height'];

		# Ratio coefficients - final image
		$_thumbnails_dimensions__ratio_coefficient = $width / $height;

		# Final dimensions - preset values
		$_image_to_resize__after_resize = array( 'width' => $width , 'height' => $height );
		if ( $_thumbnails_dimensions__ratio_coefficient > $_image_to_resize__geometry__ratio_coefficient )
		{
			$_image_to_resize__after_resize['width'] = $height * $_image_to_resize__geometry__ratio_coefficient;
		}
		else
		{
			$_image_to_resize__after_resize['height'] = $width / $_image_to_resize__geometry__ratio_coefficient;
		}

		# Final image
		$final_image = imagecreatetruecolor( $_image_to_resize__after_resize['width'] , $_image_to_resize__after_resize['height'] );

		# Resize to fit
		imagecopyresampled(
				$final_image,                                              // dst_image
				$image_to_resize,                                          // src_image
				0,                                                         // dst_x
				0,                                                         // dst_y
				0,                                                         // src_x
				0,                                                         // src_y
				$_image_to_resize__after_resize['width'],                  // dst_w
				$_image_to_resize__after_resize['height'],                 // dst_h
				$_image_to_resize__geometry['width'],                      // src_w
				$_image_to_resize__geometry['height']                      // src_h
			);

		//------------------
		// Write to file
		//------------------

		$_file_save_as = $this->API->Input->file__filename__attach_suffix( $file_resource['_f_location'] , $file_suffix );
		$return        = FALSE;
		switch ( $file_resource['_f_subtype'] )
		{
			case 'jpeg':
				$return = imagejpeg( $final_image , $_file_save_as );
				break;
			case 'gif':
				$return = imagegif( $final_image , $_file_save_as );
				break;
			case 'png':
				imagesavealpha( $final_image, TRUE );
				$return = imagepng( $final_image , $_file_save_as );
				break;
			case 'vnd.wap.wbmp':
				$return = imagewbmp( $final_image , $_file_save_as );
				break;
		}

		imagedestroy( $image_to_resize );
		imagedestroy( $final_image );

		return $return;
	}


	/**
	 * Adds a watermark to an image
	 *
	 * @param     array     File-resource
	 * @return    boolean   TRUE on success, FALSE otherwise
	 */
	public function do_watermark ( $file_resource )
	{
		//------------------------------
		// Do we have the actual file?
		//------------------------------

		if ( ! $file_resource['_diagnostics']['file_exists'] )
		{
			$this->logger__do_log( "Media ['" . $file_resource['f_hash'] . "'] not exists or inaccessible!" , "ERROR" );
			return FALSE;
		}

		//----------------------
		// Image to watermark
		//----------------------

		switch ( $file_resource['_f_subtype'] )
		{
			case 'jpeg':
				$image_to_watermark = ( imagetypes() and IMG_JPEG ) ?
					imagecreatefromjpeg( $file_resource['_f_location'] ) : FALSE;
				break;
			case 'gif':
				$image_to_watermark = ( imagetypes() and IMG_GIF ) ?
					imagecreatefromgif( $file_resource['_f_location'] ) : FALSE;
				break;
			case 'png':
				$image_to_watermark = ( imagetypes() and IMG_PNG ) ?
					imagecreatefrompng( $file_resource['_f_location'] ) : FALSE;
				break;
			case 'vnd.wap.wbmp':
				$image_to_watermark = ( imagetypes() and IMG_WBMP ) ?
					imagecreatefromwbmp( $file_resource['_f_location'] ) : FALSE;
				break;
			default:
				$image_to_watermark = FALSE;
				break;
		}
		if ( $image_to_watermark === FALSE )
		{
			return FALSE;
		}

		$_image_to_watermark__geometry = array(
				'width'  => $file_resource['f_dimensions']['width'],
				'height' => $file_resource['f_dimensions']['height']
			);

		//-----------------------------------------------------
		// Is it below watermark-able image dimension limit?
		//-----------------------------------------------------

		if ( is_string( $this->API->config['medialibrary']['watermark_threshold'] ) and strpos( $this->API->config['medialibrary']['watermark_threshold'], "x" ) !== FALSE )
		{
			$this->API->config['medialibrary']['watermark_threshold'] = explode( "x" , $this->API->config['medialibrary']['watermark_threshold'] );
		}
		if
		(
			$_image_to_watermark__geometry['width'] <= $this->API->config['medialibrary']['watermark_threshold'][0]
			and
			$_image_to_watermark__geometry['height'] <= $this->API->config['medialibrary']['watermark_threshold'][1]
		)
		{
			# Apparently it is - duplicate the original image without watermarking it, but mark it as if it is so.
			if
			(
				! copy( $file_resource['_f_location'] , $this->API->Input->file__filename__attach_suffix( $file_resource['_f_location'] , "_W" ) )
			)
			{
				$this->API->logger__do_log(
						"Modules - Data_Processors - FILE - IMAGE - GD: "
							. ( $_chdir === FALSE ? "Failed" : "Succeeded" )
							. " to COPY image '" . $file_resource['_f_location'] . "'" ,
						$_chdir === FALSE ? "ERROR" : "INFO"
					);
				return FALSE;
			}
			return TRUE;
		}

		//-------------------
		// Watermark image
		//-------------------

		$watermark = imagecreatefrompng( PATH_ROOT_WEB . "/public/images/watermarks/logo.png" );
		$_watermark__geometry = array( 'width' => imagesx( $watermark ), 'height' => imagesy( $watermark ) );

		//----------------------------
		// Watermark image - resize
		//----------------------------

		$_watermark__geometry__ratio_coefficient = $_watermark__geometry['width'] / $_watermark__geometry['height'];
		if ( $_watermark__geometry['width'] >= $_watermark__geometry['height'] )
		{
			$_watermark__geometry__after_resize['width']  = intval( $_image_to_watermark__geometry['width'] / $this->_original_image__to__watermark__ratio );
			$_watermark__geometry__after_resize['height'] = intval( $_watermark__geometry__after_resize['width'] / $_watermark__geometry__ratio_coefficient );
		}
		else
		{
			$_watermark__geometry__after_resize['height'] = intval( $_image_to_watermark__geometry['height'] / $this->_original_image__to__watermark__ratio );
			$_watermark__geometry__after_resize['width']  = intval( $_watermark__geometry__after_resize['height'] * $_watermark__geometry__ratio_coefficient );
		}

		//------------------------------
		// Watermark image - position
		//------------------------------

		$_watermark_position__x = 0;
		$_watermark_position__y = 0;
		switch ( $this->API->config['medialibrary']['watermark_position'] )
		{
			case 'sw':
				$_watermark_position__y = $_image_to_watermark__geometry['height'] - $_watermark__geometry__after_resize['height'];
				break;
			case 'ne':
				$_watermark_position__x = $_image_to_watermark__geometry['width'] - $_watermark__geometry__after_resize['width'];
				break;
			case 'se':
				$_watermark_position__y = $_image_to_watermark__geometry['height'] - $_watermark__geometry__after_resize['height'];
				$_watermark_position__x = $_image_to_watermark__geometry['width'] - $_watermark__geometry__after_resize['width'];
				break;
		}

		//---------------
		// Execute :)
		//---------------

		imagecopyresampled(
				$image_to_watermark,                                       // dst_image
				$watermark,                                                // src_image
				$_watermark_position__x,                                   // dst_x
				$_watermark_position__y,                                   // dst_y
				0,                                                         // src_x
				0,                                                         // src_y
				$_watermark__geometry__after_resize['width'],              // dst_w
				$_watermark__geometry__after_resize['height'],             // dst_h
				$_watermark__geometry['width'],                            // src_w
				$_watermark__geometry['height']                            // src_h
			);

		//------------------
		// Write to file
		//------------------

		$_file_save_as = $this->API->Input->file__filename__attach_suffix( $file_resource['_f_location'] , "_W" );
		$return        = FALSE;
		switch ( $file_resource['_f_subtype'] )
		{
			case 'jpeg':
				$return = imagejpeg( $image_to_watermark , $_file_save_as );
				break;
			case 'gif':
				$return = imagegif( $image_to_watermark , $_file_save_as );
				break;
			case 'png':
				imagesavealpha( $image_to_watermark, TRUE );
				$return = imagepng( $image_to_watermark , $_file_save_as );
				break;
			case 'vnd.wap.wbmp':
				$return = imagewbmp( $image_to_watermark , $_file_save_as );
				break;
		}

		imagedestroy( $image_to_watermark );
		imagedestroy( $watermark );

		return $return;
	}
}
?>