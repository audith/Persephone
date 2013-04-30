<?php

namespace Persephone\Input;

if ( !defined( "INIT_DONE" ) )
{
	die( "Improper access! Exiting now..." );
}

/**
 * Validation class - Manages all kinds of text-based data validation
 *
 * @package  Audith CMS codename Persephone
 * @author   Shahriyar Imanov <shehi@imanov.name>
 * @version  1.0
 */
class Validation
{
	const FILTER_EMAIL = 1;

	const FILTER_CHARACTER_ENCODING = 2;

	/**
	 * @var \Persephone\Registry
	 */
	private $Registry;

	/**
	 * @var int
	 */
	public $iteration = 0;

	/**
	 * @var int
	 */
	private $flags;

	/**
	 * Encoding to check against
	 *
	 * @var string
	 */
	private $target_character_encoding = "";

	/**
	 * Encoding type for double-checking, since mb_check_encoding() function sometimes does wrong encoding checks
	 *
	 * @var string
	 */
	private $secondary_character_encoding = "";


	public function __construct ( $options, $custom_parameter = null )
	{
		$this->flags = $options;
		if ( $this->flags & self::FILTER_CHARACTER_ENCODING and isset( $custom_parameter[ 'target_character_encoding' ] ) )
		{
			$this->target_character_encoding = $custom_parameter[ 'target_character_encoding' ];
			if ( isset( $custom_parameter[ 'secondary_character_encoding' ] ) )
			{
				$this->secondary_character_encoding = $custom_parameter[ 'secondary_character_encoding' ];
			}
		}
	}


	/**
	 * @param       mixed           $mixed      Stuff to clean
	 *
	 * @return      mixed|null
	 */
	public static function init ( $mixed )
	{
		# Crafty hacker could send something like &foo[][][][][][]....to kill Apache process
		# We should never have an globals array deeper than 10..
		if ( $this->iteration > 10 )
		{
			\Persephone\Registry::logger__do_log( __METHOD__ . " says: Iteration counter exceeded its max of 10, thus Validation has been halted!", "WARN" );
			return null;
		}

		if ( is_string( $mixed ) )
		{
			$this->flags & self::FILTER_EMAIL and $mixed = filter_var( trim( $mixed ), FILTER_VALIDATE_EMAIL );
			$this->flags & self::FILTER_CHARACTER_ENCODING and $mixed = $this->check_character_encoding( $mixed );
			self::$flags & self::FILTER_ENCLOSING_PARENTHESES_PAIRS and $mixed = self::check_enclosing_parentheses_pairs( $mixed );
		}

		if ( is_array( $mixed ) or is_object( $mixed ) )
		{
			$this->iteration++;
			foreach ( $mixed as &$data )
			{
				$data = $this->init( $data );
			}
		}

		return $mixed;
	}


	public function escape ( $value )
	{
		if ( $value = mysql_real_escape_string( $value ) )
		{
			return $value;
		}

		$return = '';
		for ( $i = 0; $i < strlen( $value ); ++$i )
		{
			$char = $value[ $i ];
			$ord  = ord( $char );
			if ( $char !== "'" && $char !== "\"" && $char !== '\\' && $ord >= 32 && $ord <= 126 )
			{
				$return .= $char;
			}
			else
			{
				$return .= '\\x' . dechex( $ord );
			}
		}

		return $return;
	}


	/**
	 * Check if the string is valid for the specified encoding
	 *
	 * @param   string          $string        Byte stream to check
	 *
	 * @return  boolean|null                   Returns TRUE on success, FALSE on failure and NULL if MBString extension is not available
	 */
	private function check_character_encoding ( $string )
	{
		# Do we have Registry object set? If not, let's set it.
		if ( !( $this->Registry instanceof \Persephone\Registry ) )
		{
			$this->Registry = \Persephone\Registry::init();
		}

		# Do we have php-mbstring set? If not, issue error and return
		if ( !in_array( "mbstring", $this->Registry->config[ 'runtime' ][ 'loaded_extensions' ] ) or empty( $this->target_character_encoding ) )
		{
			$this->Registry->logger__do_log( __METHOD__ . " says: php-mbstring not found or method-call is missing a parameter!", "ERROR" );

			return null;
		}

		# Still here? Let's continue...
		if ( $this->secondary_character_encoding )
		{
			if ( mb_check_encoding( $string, $this->target_character_encoding )
			     and
			     mb_substr_count(
				     $string,
				     '?',
				     $this->secondary_character_encoding
			     ) == mb_substr_count( mb_convert_encoding( $string, $this->target_character_encoding, $this->secondary_character_encoding ), '?', $this->target_character_encoding )
			)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			if ( mb_check_encoding( $string, $this->target_character_encoding ) )
			{
				return true;
			}
			else
			{
				return false;
			}
		}
	}


	/**
	 * Checks enclosing parentheses, matching opening and closing ones
	 *
	 * @param    string    $string      String to check
	 *
	 * @return   boolean                TRUE if successful, FALSE otherwise
	 */
	public function check_enclosing_parentheses_pairs ( $string )
	{
		$string = "(" . $string . ")";
		preg_match_all(
			'/
				\(
					(?:
						(?:
							(?>
								[^()]+
							)
							|
							(?R)
						)*
					)
				\)
			/xi',
			$string,
			$_parentheses_check_matches
		);

		if ( $string != $_parentheses_check_matches[ 0 ][ 0 ] )
		{
			return false;
		}

		return true;
	}


	/**
	 * Validates file extension by checking its contents in a BINARY level
	 *
	 * @param       string   $full_path_to_file    FULL-ABSOLUTE path to file
	 *
	 * @return      mixed                          TRUE on success; FALSE or RESULT CODES otherwise
	 * @usage       RESULT CODES:
	 *     "IS_NOT_FILE"       - Either it is not a regular file, or it does not exist at all
	 *     "IS_NOT_READABLE"   - File is not READABLE
	 *     "FILETYPE_INVALID"  - No such filetype-record was found in our MIMELIST
	 */
	public function file__extension__do_validate ( $full_path_to_file )
	{
		//----------
		// Prelim
		//----------

		# Do we have Registry object set? If not, let's set it.
		if ( !( $this->Registry instanceof \Persephone\Registry ) )
		{
			$this->Registry = \Persephone\Registry::init();
		}

		# Does it exist and is it a regular file?
		if ( is_file( $full_path_to_file ) !== true )
		{
			if ( IN_DEV )
			{
				$this->Registry->logger__do_log( __METHOD__ . " says: " . $full_path_to_file . " is NOT a REGULAR FILE or does NOT EXIST at all!", "ERROR" );
			}

			return "IS_NOT_FILE";
		}

		# Is it readable?
		if ( is_readable( $full_path_to_file ) !== true )
		{
			$this->Registry->logger__do_log( __METHOD__ . " says: Cannot READ file: " . $full_path_to_file, "ERROR" );

			return "IS_NOT_READABLE";
		}

		$_file_content                     = null;
		$_file_path__parsed                = pathinfo( $full_path_to_file );
		$_file_path__parsed[ 'extension' ] = strtolower( $_file_path__parsed[ 'extension' ] );

		# MIMELIST cache
		$_mimelist_cache = $this->Registry->Cache->cache__do_get_part( "mimelist", "by_ext" );

		//-----------------
		// Continue...
		//-----------------

		if ( !isset( $_mimelist_cache[ $_file_path__parsed[ 'extension' ] ] ) )
		{
			$this->Registry->logger__do_log( __METHOD__ . " says: '" . $_file_path__parsed[ 'extension' ] . "', NO SUCH FILETYPE IN our MIMELIST records!", "ERROR" );

			return "FILETYPE_INVALID";
		}

		if ( !empty( $_mimelist_cache[ $_file_path__parsed[ 'extension' ] ][ '_signatures' ] ) )
		{
			foreach ( $_mimelist_cache[ $_file_path__parsed[ 'extension' ] ][ '_signatures' ] as $_sigs )
			{
				# file_get_contents() parameters
				$_offset        = $_sigs[ 'type_hex_id_offset' ];
				$_length        = strlen( $_sigs[ 'type_hex_id' ] ) / 2; // 'FF' as a string is 2-bytes-long, as a hex-value 1-byte-long
				$_file_contents = file_get_contents( $full_path_to_file, FILE_BINARY, null, $_offset, $_length );
				if ( strtoupper( bin2hex( $_file_contents ) ) == $_sigs[ 'type_hex_id' ] )
				{
					return true;
				}
			}
		}
		else
		{
			# This extension does not have signatures in our records, so skip the check
			return true;
		}

		$this->Registry->logger__do_log( __METHOD__ . " says: File '" . $full_path_to_file . "' FAILED validation!", "ERROR" );

		return false;
	}
}