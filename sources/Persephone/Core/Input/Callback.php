<?php
namespace Persephone\Core\Input;

if ( !defined( "INIT_DONE" ) )
{
	die( "Improper access! Exiting now..." );
}

/**
 * Callback Parsable implementation for \Persephone\Input
 *
 * @package  Audith CMS codename Persephone
 * @author   Oleku Konko <https://github.com/olekukonko/>
 * @version  1.0
 */

class Callback implements Parsable
{
	/**
	 * Flag indicating RegEx filter is applied on the keys of aggregate, rather than its values. By default this flag On.
	 */
	const USE_KEY = 1;

	/**
	 * Flag indicating RegEx filter is applied on values of the aggregate, rather than its keys.
	 */
	const USE_VALUE = 2;

	/**
	 * Flags
	 *
	 * @var int
	 */
	private $flags;

	/**
	 * List of callable assigned to keys of the aggregate
	 *
	 * @var array
	 */
	private $callback_registry = array();

	/**
	 * List of RegEx condition rules - the aggregate will be traversed by these rules and attached callback will be applied on matching elements.
	 *
	 * @var array
	 */
	private $match = array();


	/**
	 * Adds callback to our callback-registry
	 *
	 * @param   mixed       $key
	 * @param   Callable    callback
	 */
	public function add ( $key, Callable $callback )
	{
		$this->callback_registry[ $key ] = $callback;
	}


	/**
	 * Attaches optional RegEx conditionals to callbacks for further, RegEx enhanced parsing
	 *
	 * @param   string      $regex
	 * @param   Callable    $callback
	 * @param   int         $flags
	 */
	public function match ( $regex, Callable $callback, $flags = Callback::USE_KEY )
	{
		$condition           = new \stdClass();
		$condition->regex    = $regex;
		$condition->callback = $callback;
		$condition->flags    = $flags;
		$this->match[ ]      = $condition;
	}


	/**
	 * @param string $var
	 *
	 * @return number
	 */
	public function is_regex ( $var )
	{
		$regex = '/^((?:(?:[^?+*{}()[\]\\|]+|\\.|\[(?:\^?\\.|\^[^\\]|[^\\^])(?:[^\]\\]+|\\.)*\]|\((?:\?[:=!]|\?<[=!]|\?>)?(?1)??\)|\(\?(?:R|[+-]?\d+)\))(?:(?:[?+*]|\{\d+(?:,\d*)?\})[?+]?)?|\|)*)$/';

		return preg_match( $regex, $var );
	}


	/**
	 * @param       mixed           $key        Index/offset etc to parse
	 * @param       mixed           $mixed      Value to parse
	 *
	 * @return      mixed|null
	 */
	public function parse ( $key, $mixed )
	{
		if ( isset( $this->callback_registry[ $key ] ) )
		{
			$mixed = call_user_func( $this->callback_registry[ $key ], $key, $mixed );
		}

		if ( !empty( $this->match ) )
		{
			foreach ( $this->match as $condition )
			{
				if ( $condition->flags & self::USE_KEY )
				{
					preg_match( $condition->regex, $key ) and $mixed = call_user_func( $condition->callback, $key, $mixed );
				}

				if ( $condition->flags & self::USE_VALUE )
				{
					is_string( $mixed ) and preg_match( $condition->regex, $mixed ) and $mixed = call_user_func( $condition->callback, $key, $mixed );
				}
			}
		}

		return $mixed;
	}
}