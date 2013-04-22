<?php

namespace Persephone\Input;

if ( !defined( "INIT_DONE" ) )
{
	die( "Improper access! Exiting now..." );
}

/**
 * Sanitation class - Manages all kinds of text-based data clean-up
 *
 * @package  Audith CMS codename Persephone
 * @author   Shahriyar Imanov <shehi@imanov.name>
 * @version  1.0
 */
class Sanitation
{
	const SANITIZE_XSS = 1;

	const SANITIZE_SQL = 2;

	const SANITIZE_FILTER_HIGH = 4;

	const SANITIZE_FILTER_LOW = 8;

	const SANITIZE_FILTER = 16;

	const SANITIZE_CONTROL_CHARACTERS = 32;

	const SANITIZE_CRLF = 64;

	const SANITIZE_MD5 = 128;

	const SANITIZE_EXCESSIVE_SEPARATORS = 256;

	const SANITIZE_CUSTOM = 2048;

	private $options;

	/**
	 * @var string
	 */
	private $separator = ",";


	public function __construct ( $options, $custom_function = "", $custom_parameter = null )
	{
		$this->options = $options;
		if ( $this->options & self::SANITIZE_CUSTOM and function_exists( $custom_function ) )
		{
			$this->custom_function = $custom_function;
		}
		if ( $this->options & self::SANITIZE_EXCESSIVE_SEPARATORS and isset( $custom_parameter['separator'] ) and !empty( $custom_parameter['separator'] ) )
		{
			$this->separator = $custom_parameter['separator'];
		}
	}


	function escape ( $value )
	{
		if ( $value = @mysql_real_escape_string( $value ) )
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


	public function parse ( $mixed )
	{
		if ( is_string( $mixed ) )
		{
			$this->options & self::SANITIZE_XSS and $mixed = htmlspecialchars( $mixed, ENT_QUOTES, 'UTF-8' );
			$this->options & self::SANITIZE_SQL and $mixed = $this->escape( $mixed );
			$this->options & self::SANITIZE_FILTER_HIGH and $mixed = filter_var( $mixed, FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_HIGH );
			$this->options & self::SANITIZE_FILTER_LOW and $mixed = filter_var( $mixed, FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_LOW );
			$this->options & self::SANITIZE_FILTER and $mixed = filter_var( $mixed, FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_HIGH | FILTER_FLAG_ENCODE_LOW );
			$this->options & self::SANITIZE_CONTROL_CHARACTERS and $mixed = $this->clean_control_characters( $mixed );
			$this->options & self::SANITIZE_MD5 and $mixed = $this->clean_md5_hash( $mixed );
			$this->options & self::SANITIZE_EXCESSIVE_SEPARATORS and $mixed = $this->clean_excessive_separators( $mixed );
			$this->options & self::SANITIZE_CRLF and $mixed = $this->convert_line_delimiters_to_unix( $mixed );

			return $mixed;
		}

		if ( is_array( $mixed ) )
		{
			$all = array();
			foreach ( $mixed as $key => $data )
			{
				$all[ $key ] = $this->parse( $data );
			}

			return $all;
		}

		return $mixed;
	}

	/**
	 * Removes control characters (hidden spaces)
	 *
	 * @param    string
	 *
	 * @return   string
	 */
	private function clean_control_characters ( $string )
	{
		/**
		 * @see    http://en.wikipedia.org/wiki/Space_(punctuation)
		 * @see    http://www.ascii.cl/htmlcodes.htm
		 */
		$string = str_replace( chr( 160 ), ' ', $string );
		$string = str_replace( chr( 173 ), ' ', $string );
		// $string = str_replace( chr( 240 ), ' ', $string ); // Latin small letter eth

		// $string = str_replace( chr( 0xA0 ), "", $string ); // Remove sneaky spaces	Same as chr 160
		// $string = str_replace( chr( 0x2004 ), "", $string ); // Remove sneaky spaces
		// $string = str_replace( chr( 0x2005 ), "", $string ); // Remove sneaky spaces
		// $string = str_replace( chr( 0x2006 ), "", $string ); // Remove sneaky spaces
		// $string = str_replace( chr( 0x2009 ), "", $string ); // Remove sneaky spaces
		// $string = str_replace( chr( 0x200A ), "", $string ); // Remove sneaky spaces
		// $string = str_replace( chr( 0x200B ), "", $string ); // Remove sneaky spaces
		// $string = str_replace( chr( 0x200C ), "", $string ); // Remove sneaky spaces
		// $string = str_replace( chr( 0x200D ), " ", $string ); // Remove sneaky spaces
		// $string = str_replace( chr( 0x202F ), " ", $string ); // Remove sneaky spaces
		// $string = str_replace( chr( 0x205F ), " ", $string ); // Remove sneaky spaces
		// $string = str_replace( chr( 0x2060 ), "", $string ); // Remove sneaky spaces
		// $string = str_replace( chr( 0xFEFF ), "", $string ); // Remove sneaky spaces

		return $string;
	}

	/**
	 * Convert Windows/MacOS9 line delimiters to their Unix counterpart
	 *
	 * @param    string
	 *
	 * @return   string
	 */
	private function convert_line_delimiters_to_unix ( $string )
	{
		return $string = str_replace( array( "\r\n", "\n\r", "\r" ), "\n", $string );
	}

	/**
	 * Returns a cleaned MD5 hash
	 *
	 * @param    string
	 *
	 * @return   string
	 */
	private function clean_md5_hash ( $string )
	{
		return preg_replace( "/[^a-zA-Z0-9]/", "", substr( $string, 0, 32 ) );
	}

	/**
	 * Cleans excessive leading and trailing + duplicate separator chars from delim-separated-values (such as CSV)
	 *
	 * @param    string
	 *
	 * @return   string
	 */
	private function clean_excessive_separators ( $string )
	{
		# Clean duplicates
		$string = preg_replace( "/" . preg_quote( $this->separator ) . "{2,}/", $this->separator, $string );

		# Clean leading and trailing separators, i.e. trim those
		// Doesn't work if separator is not a char but a string of chars.
		// $i = trim( $i, $sep );
		$string = preg_replace( "/^" . preg_quote( $this->separator ) . "/", "", $string );
		$string = preg_replace( "/" . preg_quote( $this->separator ) . "$/", "", $string );

		return $string;
	}
}