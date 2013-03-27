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
	const SAVE_XSS = 1;

	const SAVE_SQL = 2;

	const SAVE_FILTER_HIGH = 4;

	const SAVE_FILTER_LOW = 8;

	const SAVE_FILTER = 16;

	private $options;


	public function __construct ( $options )
	{
		$this->options = $options;
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
			$this->options & self::SAVE_XSS and $mixed = htmlspecialchars( $mixed, ENT_QUOTES, 'UTF-8' );
			$this->options & self::SAVE_SQL and $mixed = $this->escape( $mixed );
			$this->options & self::SAVE_FILTER_HIGH and $mixed = filter_var( $mixed, FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_HIGH );
			$this->options & self::SAVE_FILTER_LOW and $mixed = filter_var( $mixed, FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_LOW );
			$this->options & self::SAVE_FILTER and $mixed = filter_var( $mixed, FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_HIGH | FILTER_FLAG_ENCODE_LOW );

			return $mixed;
		}

		if ( is_array( $mixed ) )
		{
			$all = array();
			foreach ( $mixed as $data )
			{
				$all[ ] = $this->parse( $data );
			}

			return $all;
		}

		return $mixed;
	}
}