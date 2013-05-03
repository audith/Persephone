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
 * @author   Oleku Konko
 * @see      http://stackoverflow.com/questions/13906822/php-illegal-string-offset/15034807
 * @version  1.0
 */
class Sanitation implements Parsable
{
	const FILTER_NONE = 1;

	const FILTER_XSS = 2;

	const FILTER_SQL = 4;

	const FILTER_HIGH = 8;

	const FILTER_LOW = 16;

	const FILTER_HIGH_LOW = 24;

	const FILTER_CONTROL_CHARACTERS = 32;

	const FILTER_CRLF = 64;

	const FILTER_MD5 = 128;

	const FILTER_EXCESSIVE_SEPARATORS = 256;

	const FILTER_CUSTOM = 2048;

	const FILTER_ALL = 4095;

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
	 * @var string
	 */
	private $separator = ",";


	public function __construct ( $flags, $custom_parameter = null )
	{
		$this->Registry = \Persephone\Registry::init();
		$this->flags  = $flags;

		if ( $this->flags & self::FILTER_EXCESSIVE_SEPARATORS and isset( $custom_parameter[ 'separator' ] ) and !empty( $custom_parameter[ 'separator' ] ) )
		{
			$this->separator = $custom_parameter[ 'separator' ];
		}
	}


	/**
	 * @param       mixed           $key        Index/offset etc to parse
	 * @param       mixed           $mixed      Value to parse
	 *
	 * @return      mixed|null
	 */
	public function parse ( $key, $mixed )
	{
		# Crafty hacker could send something like &foo[][][][][][]....to kill Apache process
		# We should never have an globals array deeper than 10..
		if ( $this->iteration > 10 )
		{
			\Persephone\Registry::logger__do_log( __METHOD__ . " says: Iteration counter exceeded its max of 10, thus Sanitation has been halted!", "WARN" );
			return null;
		}

		if ( is_string( $mixed ) )
		{
			$this->flags & self::FILTER_XSS and $mixed = htmlspecialchars( $mixed, ENT_QUOTES, 'UTF-8' );
			$this->flags & self::FILTER_SQL and $mixed = $this->clean_for_sql_injection( $mixed );
			$this->flags & self::FILTER_HIGH and $mixed = filter_var( $mixed, FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_HIGH );
			$this->flags & self::FILTER_LOW and $mixed = filter_var( $mixed, FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_LOW );
			$this->flags & self::FILTER_HIGH_LOW and $mixed = filter_var( $mixed, FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_HIGH | FILTER_FLAG_ENCODE_LOW );
			$this->flags & self::FILTER_CONTROL_CHARACTERS and $mixed = $this->clean_control_characters( $mixed );
			$this->flags & self::FILTER_MD5 and $mixed = $this->clean_md5_hash( $mixed );
			$this->flags & self::FILTER_EXCESSIVE_SEPARATORS and $mixed = $this->clean_excessive_separators( $mixed );
			$this->flags & self::FILTER_CRLF and $mixed = $this->convert_line_delimiters_to_unix( $mixed );
		}

		if ( is_array( $mixed ) or is_object( $mixed ) )
		{
			$this->iteration++;
			foreach ( $mixed as &$data )
			{
				$data = $this->parse( $data );
			}
		}

		return $mixed;
	}


	private function clean_for_sql_injection ( $value )
	{
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


	/**
	 * Clean a string to remove all non-arithmetic characters [non- numericals, arithmetic operators and parentheses] and then
	 * makes sure the final expression is a valid mathematical/arithmetic expression, PHP-wise. Usually for eval()'s...
	 * IMPORTANT NOTE: PHP math functions are not supported!
	 *
	 * @param   string    $val                              Input String
	 * @param   boolean   $allow_decimal_point              Whether to allow decimal-point in regex control or not
	 * @param   boolean   $check_enclosing_parentheses      Whether to perform enclosing parentheses check or not
	 *
	 * @return  mixed     Parsed String on success; FALSE otherwise
	 */
	private function clean_non_arithmetic_characters ( $val, $allow_decimal_point = false, $check_enclosing_parentheses = false )
	{
		if ( $check_enclosing_parentheses )
		{
			if ( !Validation::check_enclosing_parentheses_pairs( $val ) )
			{
				return false;
			}
		}

		$val = preg_replace( "/&(?:#[0-9]+|[a-z]+);/i", "", $val ); // Getting rid of all HTML entities
		if ( $allow_decimal_point )
		{
			$val = preg_replace( '#[^0-9\-\+\*\/\(\)\.]+#', "", $val ); // Remove non numericals, leave decimal-point
			$val = preg_replace( '#(?<=\d)\.(?!\d)#', "", $val ); // Remove trailing decimal points (e.g. "0." )
			$val = preg_replace( '#(?<!\d)\.(?=\d)#', "", $val ); // Remove leader decimal points (e.g. ".0" )
		}
		else
		{
			$val = preg_replace( '#[^0-9\-\+\*\/\(\)]+#', "", $val ); // Remove non numericals
		}
		$val = preg_replace( '#^[\+\*\/]+#', "", $val ); // Remove leading arithmetics, leave leading (-) for signs
		$val = preg_replace( '#[\-\+\*\/]+$#', "", $val ); // Remove trailing arithmetics
		$val = preg_replace( '#(?<=\()[^0-9\-]+(?=\d)#', "", $val ); // Remove leading arithmetics [within parentheses], leave leading (-) for signs
		$val = preg_replace( '#(?<=\d)[^0-9]+(?=\))#', "", $val ); // Remove trailing arithmetics [within parentheses]

		return $val;
	}
}