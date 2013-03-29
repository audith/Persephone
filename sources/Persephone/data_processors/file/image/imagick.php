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
 * @author   Shahriyar Imanov <shehi@imanov.name>
 * @version  1.0
**/
final class Data_Processors__File__Image__Imagick
{
	/**
	 * Registry reference
	 * @var Registry
	 */
	public $Registry;

	/**
	 * Ratio of original image's dimension in a given direction to that of the watermark image
	 * @var integer
	 */
	private $_original_image__to__watermark__ratio = 5;


	/**
	 * Contructor
	 * @param    Registry    Registry object reference
	 */
	public function __construct ( Registry $Registry )
	{
		$this->Registry = $Registry;
		$this->Registry->logger__do_log( "Loaded Imagick library!" , "INFO" );
	}


	/**
	 * Creates a thumbnail for the given image-file, using the given width and height, and saves the final file using the suffix provided in the original folder.
	 *
	 * @uses      PHP PECL Imagick extension
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

		//---------------
		// ImageMagick
		//---------------

		# Load original image and change working directory
		$image = new Imagick( $file_resource['_f_location'] );

		# Is a valid item?
		if ( ! $image->valid() )
		{
			return FALSE;
		}

		# Small thumbs
		# Dimensions from Config
		$_dimensions = explode( "x" , strtolower( $this->Registry->config['medialibrary']['thumb_small_dimensions'] ) );

		# Resize to fit
		$image->thumbnailImage( $width, $height );

		# Write to the file
		if
		(
			! $image->writeImage(
					$this->Registry->Input->file__filename__attach_suffix( $file_resource['_f_location'] , $file_suffix )
				)
		)
		{
			return FALSE;
		}

		return TRUE;
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

		if ( ! $image_to_watermark = new Imagick( $file_resource['_f_location'] ) )
		{
			return FALSE;
		}

		// $_image_to_watermark__geometry = $image_to_watermark->getImageGeometry();
		$_image_to_watermark__geometry = array(
				'width'  => $file_resource['f_dimensions']['width'],
				'height' => $file_resource['f_dimensions']['height']
			);

		# Sharpness - specific to Imagick
		$_sharpness = 0.5;
		if ( $_image_to_watermark__geometry['width'] <= 300 )
		{
			$_sharpness = 0.1;
		}

		//-----------------------------------------------------
		// Is it below watermark-able image dimension limit?
		//-----------------------------------------------------

		if ( strpos( $this->Registry->config['medialibrary']['watermark_threshold'], "x" ) !== FALSE )
		{
			$this->Registry->config['medialibrary']['watermark_threshold'] = explode( "x" , $this->Registry->config['medialibrary']['watermark_threshold'] );
		}
		if
		(
			$_image_to_watermark__geometry['width'] <= $this->Registry->config['medialibrary']['watermark_threshold'][0]
			and
			$_image_to_watermark__geometry['height'] <= $this->Registry->config['medialibrary']['watermark_threshold'][1]
		)
		{
			# Apparently it is - duplicate the original image without watermarking it, but mark it as if it is so.
			if
			(
				! copy( $file_resource['_f_location'] , $this->Registry->Input->file__filename__attach_suffix( $file_resource['_f_location'] , "_W" ) )
			)
			{
				$this->Registry->logger__do_log(
						"Modules - Data_Processors - FILE - IMAGE - IMAGICK: "
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

		$watermark = new Imagick( PATH_ROOT_WEB . "/public/images/watermarks/logo.png" );
		$_watermark__geometry = $watermark->getImageGeometry();

		//----------------------------
		// Watermark image - resize
		//----------------------------

		$_watermark__geometry__after_resize['width']  = intval( $_image_to_watermark__geometry['width']  / $this->_original_image__to__watermark__ratio );
		$_watermark__geometry__after_resize['height'] = intval( $_image_to_watermark__geometry['height'] / $this->_original_image__to__watermark__ratio );
		$watermark->resizeImage(
				$_watermark__geometry['width'] >= $_watermark__geometry['height'] ?
					$_watermark__geometry__after_resize['width'] : null,
				$_watermark__geometry['width'] < $_watermark__geometry['height'] ?
					$_watermark__geometry__after_resize['height'] : null,
				imagick::FILTER_CUBIC,
				$_sharpness
			);
		$_watermark__geometry = $watermark->getImageGeometry();

		//------------------------------
		// Watermark image - position
		//------------------------------

		$_watermark_position__x = 0;
		$_watermark_position__y = 0;
		switch ( $this->Registry->config['medialibrary']['watermark_position'] )
		{
			case 'sw':
				$_watermark_position__y = $_image_to_watermark__geometry['height'] - $_watermark__geometry['height'];
				break;
			case 'ne':
				$_watermark_position__x = $_image_to_watermark__geometry['width'] - $_watermark__geometry['width'];
				break;
			case 'se':
				$_watermark_position__y = $_image_to_watermark__geometry['height'] - $_watermark__geometry['height'];
				$_watermark_position__x = $_image_to_watermark__geometry['width'] - $_watermark__geometry['width'];
				break;
		}

		//---------------
		// Execute :)
		//---------------

		$image_to_watermark->compositeImage( $watermark, imagick::COMPOSITE_OVER, $_watermark_position__x, $_watermark_position__y );

		//------------------
		// Write to file
		//------------------

		if
		(
			! $image_to_watermark->writeImage(
					$this->Registry->Input->file__filename__attach_suffix( $file_resource['_f_location'] , "_W" )
				)
		)
		{
			return FALSE;
		}

		return TRUE;
	}
}
?>