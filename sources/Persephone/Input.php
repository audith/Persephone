<?php

namespace Persephone;

use Persephone\Input\Parsable;

if ( !defined( "INIT_DONE" ) )
{
	die( "Improper access! Exiting now..." );
}

/**
 * Input class - Manages all types of incoming data, including Server, Env and User-input information
 *
 * @package  Audith CMS codename Persephone
 * @author   Shahriyar Imanov <shehi@imanov.name>
 * @version  1.0
 */
class Input implements \ArrayAccess, \IteratorAggregate, \JsonSerializable
{
	/**
	 * Flag which allows "manipulating values" (Disabled by default)
	 *
	 * @var int
	 */
	const ALLOW_SET = 1;

	/**
	 * Flag which allows "retrieval of values" (Enabled by default)
	 *
	 * @var int
	 */
	const ALLOW_GET = 2;

	/**
	 * Flag which disables both ALLOW_SET and ALLOW_GET flags
	 *
	 * @var int
	 */
	const ALLOW_NONE = 4;

	/**
	 * Container that carries manipulated/requested aggregate data
	 *
	 * @var array
	 */
	private $data = array();

	/**
	 * List of components to ignore while traversing through the aggregate
	 *
	 * @var array
	 */
	private $ignore = array();

	private $offsetFilter = array();

	/**
	 * Access flags
	 *
	 * @var int
	 */
	private $flags;

	/**
	 * Filters to be applied on aggregate container
	 *
	 * @var null|Input\Parsable
	 */
	private $filter = null;

	/**
	 * Registry reference
	 *
	 * @var \Persephone\Registry
	 */
	private $Registry;

	/**
	 * HTTP Headers
	 *
	 * @var array
	 */
	public $headers = array( 'request' => array( '_is_ajax' => false ) );


	/**
	 * Constructor
	 *
	 * @param   array|\Traversable      $data       Aggregate data.
	 * @param   int                     $flags      Access flags, defaults to self::ALLOW_GET being set.
	 * @param   \Persephone\Registry    $Registry   Registry object reference
	 *
	 * @throws \Persephone\Exception
	 */
	public function __construct ( $data, Parsable $filter = null, $flags = Input::ALLOW_GET )
	{
		/**
		 * Whether $data parameter is \Traversable or not.
		 *
		 * @var boolean
		 */
		$_is_data_traversable = $data instanceof \Traversable;

		if ( !is_array( $data ) and !$_is_data_traversable )
		{
			throw new \Persephone\Exception( __METHOD__ . " says: Incoming data-container is neither an Array, or \\Traversable!" );
		}

		$this->data  = !$_is_data_traversable
			? iterator_to_array( $data )
			: $data;
		$this->filter = $filter;
		$this->flags = $flags;
	}


	/**
	 * Create a new iterator from an ArrayObject instance
	 * @see  IteratorAggregate::getIterator()
	 * @link http://php.net/manual/en/arrayobject.getiterator.php
	 */
	public function getIterator ()
	{
		return new \ArrayIterator( $this->data );
	}


	/**
	 * This iterator allows to unset and modify values and keys while iterating over Arrays and Objects in the same way as the ArrayIterator. Additionally it is possible to iterate over the current iterator entry.
	 *
	 * @link http://php.net/manual/en/class.recursivearrayiterator.php
	 */
	public function getRecursiveIterator ()
	{
		return new \RecursiveArrayIterator( $this->data );
	}


	/**
	 * Specify data which should be serialized to JSON
	 * @see         JsonSerializable::jsonSerialize()
	 * @link        http://php.net/manual/en/jsonserializable.jsonserialize.php
	 *
	 * @return      mixed
	 */
	public function jsonSerialize ()
	{
		return $this->data;
	}


	/**
	 * Magic method allows a class to decide how it will react when it is treated like a string.
	 * @link http://www.php.net/manual/en/language.oop5.magic.php#object.tostring
	 *
	 * @return string
	 */
	public function __toString ()
	{
		return json_encode( $this->jsonSerialize() );
	}


	/**
	 * Magic method, riggered by calling isset() or empty() on inaccessible properties.
	 * @link http://www.php.net/manual/en/language.oop5.overloading.php#object.isset
	 *
	 * @param   mixed   $offset
	 *
	 * @return  boolean
	 */
	public function __isset ( $offset )
	{
		return $this->offsetExists( $offset );
	}


	/**
	 * Returns whether the requested index exists or not	 *
	 * @see     ArrayAccess::offsetExists()
	 * @link    http://www.php.net/manual/en/arrayobject.offsetexists.php
	 *
	 * @param   mixed       $offset
	 *
	 * @return  boolean
	 */
	public function offsetExists ( $offset )
	{
		return isset( $this->data[ $offset ] );
	}


	/**
	 * Unsets the value at the specified index
	 * @see     ArrayAccess::offsetUnset()
	 * @link    http://www.php.net/manual/en/arrayobject.offsetunset.php
	 *
	 * @param   mixed   $offset
	 */
	public function offsetUnset ( $offset )
	{
		unset( $this->data[ $offset ] );
	}


	/**
	 * Setter overloader
	 *
	 * @param   mixed   $offset
	 * @param   mixed   $value
	 */
	public function __set ( $offset, $value )
	{
		$this->offsetSet( $offset, $value );
	}


	/**
	 * Builds ignore list to be used by Parsable filters. Keys listed in this list, will be ignored and *not* filtered, when the aggregate is traversed.
	 */
	public function ignore ()
	{
		$this->ignore = array_fill_keys( func_get_args(), true );
	}


	/**
	 * Assigns a value to the specified offset.
	 * @see     ArrayAccess::offsetSet()
	 * @link    http://php.net/manual/en/arrayobject.offsetset.php
	 * @throws  \Persephone\Exception
	 *
	 * @param   mixed                       $offset
	 * @param   mixed                       $value
	 */
	public function offsetSet ( $offset, $value )
	{
		if ( $this->flags ^ self::ALLOW_SET )
		{
			throw new \Persephone\Exception( __METHOD__ . " says: Offset assignment disabled!" );
		}

		$this->data[ $offset ] = $value;
	}


	/**
	 * Magic method, triggered when invoking inaccessible methods in an object context.
	 * @link    http://www.php.net/manual/en/language.oop5.overloading.php#object.call
	 *
	 * @param   mixed           $offset
	 * @param   mixed           $value
	 *
	 * @return  mixed|null|void
	 *
	 */
	public function __call ( $offset, $value )
	{
		if ( !empty( $value ) > 0 )
		{
			return $this->offsetSet( $offset, $value );
		}

		return $this->offsetGet( $offset );
	}


	/**
	 * Magic method, called when a script tries to call an object as a function.	 *
	 * @link http://www.php.net/manual/en/language.oop5.magic.php#object.invoke
	 *
	 * @param       string          $offset
	 *
	 * @return      mixed|null
	 */
	public function __invoke ( $offset )
	{
		return $this->offsetGet( $offset );
	}


	/**
	 * Set a filter for a particular offset
	 * @throws \Persephone\Exception
	 *
	 * @param   mixed                       $offset
	 * @param   Parsable                    $filter
	 */
	public function offsetFilter ( $offset, Parsable $filter )
	{
		if ( !$filter instanceof Parsable )
		{
			throw new \Persephone\Exception( __METHOD__ . " says: Invalid filter added to list!" );
		}
		$this->offsetFilter[ $offset ] = $filter;
	}


	/**
	 * Returns the value at the specified index
	 *
	 * @see     ArrayAccess::offsetGet()
	 * @link    http://www.php.net/manual/en/arrayobject.offsetget.php
	 * @throws  \Persephone\Exception
	 *
	 * @param   mixed   $offset
	 *
	 * @return  mixed
	 */
	public function offsetGet ( $offset )
	{
		# is ALLOW_GET flag on?
		if ( $this->flags ^ self::ALLOW_GET )
		{
			throw new \Persephone\Exception( __METHOD__ . " says: Offset retrieval disabled!" );
		}

		# Filter to use
		$filter = isset( $this->offsetFilter[ $offset ] )
			? $this->offsetFilter[ $offset ]
			: $this->filter;

		# Add ignore rule
		isset( $this->ignore[ $offset ] ) and $filter = null;

		# Illegal string-offset Fix
		return $this->offsetExists( $offset )
			? ( $filter
				? $filter->parse( $offset, $this->data[ $offset ] )
				: $this->data[ $offset ] )
			: null;
	}


	/**
	 * triggered when invoking inaccessible methods in an object context.
	 *
	 * @param   string      $path
	 *
	 * @return  mixed
	 */
	public function find ( $path )
	{
		$path = explode( ".", $path );
		if ( $var = $this->offsetGet( array_shift( $path ) ) )
		{
			return $this->getValue( $path, $var );
		}

		return $var;
	}


	/**
	 * Fetches "paths" value from "data" container
	 *
	 * @param   array   $paths
	 * @param   array   $data
	 *
	 * @return  mixed|null
	 */
	private function getValue ( array $paths, array $data )
	{
		$temp = $data;
		foreach ( $paths as $index )
		{
			$temp = isset( $temp[ $index ] )
				? $temp[ $index ]
				: null;
		}

		return $temp;
	}























	/**
	 * Method overloading for Input class - gets value from within requested superglobal
	 *
	 * @param     string                    $name         The name of the method being called.
	 * @param     array                     $arguments    Enumerated array containing the parameters passed to the $name'd method.
	 *
	 * @return    mixed                                   Value gotten from super-global
	 * @throws    \Persephone\Exception                   In case, if method is not one of the pre-defined Six.
	 */
	public function __call ( $name, $arguments )
	{
		if ( !in_array( $name, array( "post", "get", "cookie", "request", "server", "env" ) ) )
		{
			throw new \Persephone\Exception( "Input::" . $name . "() not declared!" );
		}

		$_name_of_superglobal             = "_" . strtoupper( $name );
		$_max_iteration_level_for_cleanup = in_array( $name, array( "server", "env" ) )
			? 1
			: 10;

		# $arguments[0] is the index of the value, to be fetched from within the array.
		if ( !empty( $arguments[ 0 ] ) and array_key_exists( $arguments[ 0 ], $this->$name ) )
		{
			return $this->{$name}[ $arguments[ 0 ] ];
		}
		elseif ( !empty( $arguments[ 0 ] ) and array_key_exists( $arguments[ 0 ], $GLOBALS[ $_name_of_superglobal ] ) )
		{
			return $this->{$name}[ $arguments[ 0 ] ] = $this->sanitize__clean_raw_value__medium( $GLOBALS[ $_name_of_superglobal ][ $arguments[ 0 ] ], array(), true );
		}
		elseif ( !empty( $arguments[ 0 ] ) and !array_key_exists( $arguments[ 0 ], $GLOBALS[ $_name_of_superglobal ] ) )
		{
			return null;
		}
		else
		{
			if ( $this->_is_cleanup_done_for[ $name ] === true )
			{
				return $this->$name;
			}
			$this->_is_cleanup_done_for[ $name ] = true;

			return $this->$name = $this->sanitize__clean_raw_value_recursive__high( $GLOBALS[ $_name_of_superglobal ], $_max_iteration_level_for_cleanup );
		}
	}


	/**
	 * Destructor
	 */
	public function _my_destruct ()
	{
		$this->Registry->logger__do_log( __CLASS__ . "::__destruct: Destroying class" );
	}


	/**
	 * Inits INPUT environment
	 *
	 * @return void
	 */
	public function init ()
	{
		//--------------------
		// Request headers
		//--------------------

		foreach ( $_SERVER as $_k => $_v )
		{
			if ( strpos( $_k, 'HTTP_' ) === 0 )
			{
				$_k                                              = str_replace( array( "HTTP_", "_" ), array( "", "-" ), $_k );
				$this->headers[ 'request' ][ strtoupper( $_k ) ] = $_v;
			}
		}

		# Is it an XMLHttpRequest?
		if ( isset( $this->headers[ 'request' ][ 'X-REQUESTED-WITH' ] ) and $this->headers[ 'request' ][ 'X-REQUESTED-WITH' ] == 'XMLHttpRequest' )
		{
			$this->headers[ 'request' ][ '_is_ajax' ] = true;
		}

		//---------------------------------------------------
		// Strip slashes added by MAGIC_QUOTES_GPC mode
		//---------------------------------------------------

		if ( MAGIC_QUOTES_GPC_ON )
		{
			$_POST   = $this->sanitize__stripslashes( $_POST );
			$_GET    = $this->sanitize__stripslashes( $_GET );
			$_COOKIE = $this->sanitize__stripslashes( $_COOKIE );
		}

		//-----------------------------------------
		// Perform basic cleanup on globals
		//-----------------------------------------

		$this->clean__globals( $_POST );
		$this->clean__globals( $_GET );
		$this->clean__globals( $_COOKIE );
		$this->clean__globals( $_REQUEST );

		//----------------------------------
		// SERVER['PATH_INFO'] "exploded"
		//----------------------------------

		$this->Registry->config[ 'page' ][ 'request' ] = $this->parse__parse_request_uri__medium();

		# Request path
		$this->Registry->logger__do_log( "Input::init() : Request-path = '" . $this->Registry->config[ 'page' ][ 'request' ][ 'path' ] . "'", "INFO" );

		//-----------------------------
		// Make a safe QUERY STRING
		//-----------------------------

		$_qs                     = $this->my_getenv( "QUERY_STRING" );
		$this->query_string_safe = $this->clean__excessive_separators( $this->sanitize__clean_raw_value__medium( $_qs, array( "urldecode" ), true ), "&amp;" );
		$this->query_string_real = str_replace( '&amp;', '&', $this->query_string_safe );

		//----------------
		// Format it...
		//----------------

		$this->query_string_formatted = preg_replace( "#s=([a-z0-9]){32}#", '', $this->query_string_safe );
	}


	/**
	 * Queries the entity given and seeks its HTML-encoded version, or vice versa
	 *
	 * @param   string   $matches       Entity to query
	 * @param   bool     $_do_flip      Whether to flip the map and seek the other way around, or not
	 *
	 * @return  string
	 */
	public function parse__query_html_entities__low ( $matches, $_do_flip = false )
	{
		$table = array(
			/* 'quot'    => '&#34;', 'amp' => '&#38;', 'lt' => '&#60;', 'gt' => '&#62;', */
			'OElig'    => '&#338;',
			'oelig'    => '&#339;',
			'Scaron'   => '&#352;',
			'scaron'   => '&#353;',
			'Yuml'     => '&#376;',
			'circ'     => '&#710;',
			'tilde'    => '&#732;',
			'ensp'     => '&#8194;',
			'emsp'     => '&#8195;',
			'thinsp'   => '&#8201;',
			'zwnj'     => '&#8204;',
			'zwj'      => '&#8205;',
			'lrm'      => '&#8206;',
			'rlm'      => '&#8207;',
			'ndash'    => '&#8211;',
			'mdash'    => '&#8212;',
			'lsquo'    => '&#8216;',
			'rsquo'    => '&#8217;',
			'sbquo'    => '&#8218;',
			'ldquo'    => '&#8220;',
			'rdquo'    => '&#8221;',
			'bdquo'    => '&#8222;',
			'dagger'   => '&#8224;',
			'Dagger'   => '&#8225;',
			'permil'   => '&#8240;',
			'lsaquo'   => '&#8249;',
			'rsaquo'   => '&#8250;',
			'euro'     => '&#8364;',
			'fnof'     => '&#402;',
			'Alpha'    => '&#913;',
			'Beta'     => '&#914;',
			'Gamma'    => '&#915;',
			'Delta'    => '&#916;',
			'Epsilon'  => '&#917;',
			'Zeta'     => '&#918;',
			'Eta'      => '&#919;',
			'Theta'    => '&#920;',
			'Iota'     => '&#921;',
			'Kappa'    => '&#922;',
			'Lambda'   => '&#923;',
			'Mu'       => '&#924;',
			'Nu'       => '&#925;',
			'Xi'       => '&#926;',
			'Omicron'  => '&#927;',
			'Pi'       => '&#928;',
			'Rho'      => '&#929;',
			'Sigma'    => '&#931;',
			'Tau'      => '&#932;',
			'Upsilon'  => '&#933;',
			'Phi'      => '&#934;',
			'Chi'      => '&#935;',
			'Psi'      => '&#936;',
			'Omega'    => '&#937;',
			'alpha'    => '&#945;',
			'beta'     => '&#946;',
			'gamma'    => '&#947;',
			'delta'    => '&#948;',
			'epsilon'  => '&#949;',
			'zeta'     => '&#950;',
			'eta'      => '&#951;',
			'theta'    => '&#952;',
			'iota'     => '&#953;',
			'kappa'    => '&#954;',
			'lambda'   => '&#955;',
			'mu'       => '&#956;',
			'nu'       => '&#957;',
			'xi'       => '&#958;',
			'omicron'  => '&#959;',
			'pi'       => '&#960;',
			'rho'      => '&#961;',
			'sigmaf'   => '&#962;',
			'sigma'    => '&#963;',
			'tau'      => '&#964;',
			'upsilon'  => '&#965;',
			'phi'      => '&#966;',
			'chi'      => '&#967;',
			'psi'      => '&#968;',
			'omega'    => '&#969;',
			'thetasym' => '&#977;',
			'upsih'    => '&#978;',
			'piv'      => '&#982;',
			'bull'     => '&#8226;',
			'hellip'   => '&#8230;',
			'prime'    => '&#8242;',
			'Prime'    => '&#8243;',
			'oline'    => '&#8254;',
			'frasl'    => '&#8260;',
			'weierp'   => '&#8472;',
			'image'    => '&#8465;',
			'real'     => '&#8476;',
			'trade'    => '&#8482;',
			'alefsym'  => '&#8501;',
			'larr'     => '&#8592;',
			'uarr'     => '&#8593;',
			'rarr'     => '&#8594;',
			'darr'     => '&#8595;',
			'harr'     => '&#8596;',
			'crarr'    => '&#8629;',
			'lArr'     => '&#8656;',
			'uArr'     => '&#8657;',
			'rArr'     => '&#8658;',
			'dArr'     => '&#8659;',
			'hArr'     => '&#8660;',
			'forall'   => '&#8704;',
			'part'     => '&#8706;',
			'exist'    => '&#8707;',
			'empty'    => '&#8709;',
			'nabla'    => '&#8711;',
			'isin'     => '&#8712;',
			'notin'    => '&#8713;',
			'ni'       => '&#8715;',
			'prod'     => '&#8719;',
			'sum'      => '&#8721;',
			'minus'    => '&#8722;',
			'lowast'   => '&#8727;',
			'radic'    => '&#8730;',
			'prop'     => '&#8733;',
			'infin'    => '&#8734;',
			'ang'      => '&#8736;',
			'and'      => '&#8743;',
			'or'       => '&#8744;',
			'cap'      => '&#8745;',
			'cup'      => '&#8746;',
			'int'      => '&#8747;',
			'there4'   => '&#8756;',
			'sim'      => '&#8764;',
			'cong'     => '&#8773;',
			'asymp'    => '&#8776;',
			'ne'       => '&#8800;',
			'equiv'    => '&#8801;',
			'le'       => '&#8804;',
			'ge'       => '&#8805;',
			'sub'      => '&#8834;',
			'sup'      => '&#8835;',
			'nsub'     => '&#8836;',
			'sube'     => '&#8838;',
			'supe'     => '&#8839;',
			'oplus'    => '&#8853;',
			'otimes'   => '&#8855;',
			'perp'     => '&#8869;',
			'sdot'     => '&#8901;',
			'lceil'    => '&#8968;',
			'rceil'    => '&#8969;',
			'lfloor'   => '&#8970;',
			'rfloor'   => '&#8971;',
			'lang'     => '&#9001;',
			'rang'     => '&#9002;',
			'loz'      => '&#9674;',
			'spades'   => '&#9824;',
			'clubs'    => '&#9827;',
			'hearts'   => '&#9829;',
			'diams'    => '&#9830;',
			'nbsp'     => ' ',
			'iexcl'    => '&#161;',
			'cent'     => '&#162;',
			'pound'    => '&#163;',
			'curren'   => '&#164;',
			'yen'      => '&#165;',
			'brvbar'   => '&#166;',
			'sect'     => '&#167;',
			'uml'      => '&#168;',
			'copy'     => '&#169;',
			'ordf'     => '&#170;',
			'laquo'    => '&#171;',
			'not'      => '&#172;',
			'shy'      => '&#173;',
			'reg'      => '&#174;',
			'macr'     => '&#175;',
			'deg'      => '&#176;',
			'plusmn'   => '&#177;',
			'sup2'     => '&#178;',
			'sup3'     => '&#179;',
			'acute'    => '&#180;',
			'micro'    => '&#181;',
			'para'     => '&#182;',
			'middot'   => '&#183;',
			'cedil'    => '&#184;',
			'sup1'     => '&#185;',
			'ordm'     => '&#186;',
			'raquo'    => '&#187;',
			'frac14'   => '&#188;',
			'frac12'   => '&#189;',
			'frac34'   => '&#190;',
			'iquest'   => '&#191;',
			'Agrave'   => '&#192;',
			'Aacute'   => '&#193;',
			'Acirc'    => '&#194;',
			'Atilde'   => '&#195;',
			'Auml'     => '&#196;',
			'Aring'    => '&#197;',
			'AElig'    => '&#198;',
			'Ccedil'   => '&#199;',
			'Egrave'   => '&#200;',
			'Eacute'   => '&#201;',
			'Ecirc'    => '&#202;',
			'Euml'     => '&#203;',
			'Igrave'   => '&#204;',
			'Iacute'   => '&#205;',
			'Icirc'    => '&#206;',
			'Iuml'     => '&#207;',
			'ETH'      => '&#208;',
			'Ntilde'   => '&#209;',
			'Ograve'   => '&#210;',
			'Oacute'   => '&#211;',
			'Ocirc'    => '&#212;',
			'Otilde'   => '&#213;',
			'Ouml'     => '&#214;',
			'times'    => '&#215;',
			'Oslash'   => '&#216;',
			'Ugrave'   => '&#217;',
			'Uacute'   => '&#218;',
			'Ucirc'    => '&#219;',
			'Uuml'     => '&#220;',
			'Yacute'   => '&#221;',
			'THORN'    => '&#222;',
			'szlig'    => '&#223;',
			'agrave'   => '&#224;',
			'aacute'   => '&#225;',
			'acirc'    => '&#226;',
			'atilde'   => '&#227;',
			'auml'     => '&#228;',
			'aring'    => '&#229;',
			'aelig'    => '&#230;',
			'ccedil'   => '&#231;',
			'egrave'   => '&#232;',
			'eacute'   => '&#233;',
			'ecirc'    => '&#234;',
			'euml'     => '&#235;',
			'igrave'   => '&#236;',
			'iacute'   => '&#237;',
			'icirc'    => '&#238;',
			'iuml'     => '&#239;',
			'eth'      => '&#240;',
			'ntilde'   => '&#241;',
			'ograve'   => '&#242;',
			'oacute'   => '&#243;',
			'ocirc'    => '&#244;',
			'otilde'   => '&#245;',
			'ouml'     => '&#246;',
			'divide'   => '&#247;',
			'oslash'   => '&#248;',
			'ugrave'   => '&#249;',
			'uacute'   => '&#250;',
			'ucirc'    => '&#251;',
			'uuml'     => '&#252;',
			'yacute'   => '&#253;',
			'thorn'    => '&#254;',
			'yuml'     => '&#255;'
		);

		if ( $_do_flip === true )
		{
			$table  = array_flip( $table );
			$lookup = '&#' . $matches[ 1 ] . ';';

			return ( isset( $table[ $lookup ] ) )
				? '&' . $table[ $lookup ] . ';'
				: $lookup;
		}

		return ( isset( $table[ $matches[ 1 ] ] ) )
			? $table[ $matches[ 1 ] ]
			: '&' . $matches[ 1 ] . ';';
	}


	/**
	 * Performs basic cleaning (Null characters, etc) on globals
	 *
	 * @param   array     $data         Incoming data to clean
	 * @param   integer   $iteration    Incoming data array depth
	 *
	 * @return  void
	 * @author  concept by Matthew Mecham @ IPS; adapted by Shahriyar Imanov @ Audith
	 */
	public function clean__globals ( &$data, $iteration = 0 )
	{
		# Crafty hacker could send something like &foo[][][][][][]....to kill Apache process
		# We should never have an globals array deeper than 10..
		if ( $iteration >= 10 )
		{
			return;
		}

		foreach ( $data as $k => $v )
		{
			if ( is_array( $v ) )
			{
				$this->clean__globals( $data[ $k ], ++$iteration );
			}
			else
			{
				# Null byte characters
				$v = str_replace( chr( '0' ), '', $v );
				$v = str_replace( "\0", '', $v );
				$v = str_replace( "\x00", '', $v );
				$v = str_replace( '%00', '', $v );

				# File traversal
				$v = str_replace( '../', "&#046;&#046;/", $v );

				# RTL override
				$v = str_replace( '&#8238;', '', $v );

				$data[ $k ] = $v;
			}
		}
	}


	/**
	 * Clean's incoming-array keys (usually _GET, _POST), ensures no funny business with form elements [Build 20080120]
	 *
	 * @param    String  Key name
	 *
	 * @return   String  Cleaned key name
	 */
	public function sanitize__clean_raw_key__low ( $key )
	{
		if ( $key === "" )
		{
			return "";
		}

		$key = htmlspecialchars( urldecode( $key ) );
		$key = str_replace( "..", "", $key );
		$key = preg_replace( '/\_\_(.+?)\_\_/', "", $key );
		$key = preg_replace( '/^([\w\.\-\_]+)$/', "$1", $key );

		return $key;
	}


	/**
	 * Clean a string to remove all non-alphanumeric characters
	 *
	 * @param   string   Input string
	 * @param   string   Additional characters to preserve
	 *
	 * @return  string   Parsed string
	 */
	public function clean__makesafe_alphanumerical ( $val, $_characters_to_preserve = "" )
	{
		if ( !empty( $_characters_to_preserve ) )
		{
			$_characters_to_preserve = preg_quote( $_characters_to_preserve, "/" );
		}

		$val = preg_replace( "/&(?:#[0-9]+|[a-z]+);/i", "", $val );
		$val = preg_replace( "/[^a-z0-9\-\_" . $_characters_to_preserve . "]/i", "", $val );

		return $val;
	}


	/**
	 * Clean a string to remove all non-arithmetic characters [non- numericals, arithmetic operators and parentheses] and then
	 * makes sure the final expression is a valid mathematical/arithmetic expression, PHP-wise. Usually for eval()'s...
	 * IMPORTANT NOTE: PHP math functions are not supported!
	 *
	 * @param   string    Input String
	 * @param   boolean   Whether to allow decimal-point in regex control or not
	 * @param   boolean   Whether to perform enclosing parentheses check or not
	 *
	 * @return  mixed     Parsed String on success; FALSE otherwise
	 */
	public function clean__makesafe_mathematical ( $val, $allow_decimal_point = false, $check_enclosing_parentheses = false )
	{
		if ( $check_enclosing_parentheses )
		{
			if ( !$this->validate__check_enclosing_parentheses_pairs__medium( $val ) )
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


	/**
	 * Convert HTML line break tags to \n
	 *
	 * @param    string   Input text
	 *
	 * @return   string   Parsed text
	 */
	public function br2nl ( $t )
	{
		$t = str_replace( array( "\r", "\n" ), '', $t );
		$t = str_replace( array( "<br />", "<br>" ), "\n", $t );

		return $t;
	}


	/**
	 * Base64 encode for URLs
	 *
	 * @param    string    Data
	 */
	public function base64_encode_urlsafe ( $data )
	{
		return strtr( base64_encode( $data ), '+/=', '-_,' );;
	}


	/**
	 * Base64 decode for URLs
	 *
	 * @param    string    Data
	 *
	 * @return   string    Data
	 */
	public function base64_decode_urlsafe ( $data )
	{
		return base64_decode( strtr( $data, '-_,', '+/=' ) );
	}


	/**
	 * Converts accented characters into their plain alphabetic counterparts
	 *
	 * @param   string   Text
	 *
	 * @return  string   Cleaned text
	 */
	public function parse__convert_accents_to_english__high ( $s )
	{
		if ( !preg_match( '/[\x80-\xff]/', $s ) )
		{
			return $s;
		}

		if ( $this->is_utf8( $s ) )
		{
			$_chr = array(
				/* Latin-1 Supplement */
				chr( 195 ) . chr( 128 )              => 'Ae',
				chr( 195 ) . chr( 129 )              => 'A',
				chr( 195 ) . chr( 130 )              => 'A',
				chr( 195 ) . chr( 131 )              => 'A',
				chr( 195 ) . chr( 132 )              => 'A',
				chr( 195 ) . chr( 133 )              => 'A',
				chr( 195 ) . chr( 135 )              => 'C',
				chr( 195 ) . chr( 136 )              => 'E',
				chr( 195 ) . chr( 137 )              => 'E',
				chr( 195 ) . chr( 138 )              => 'E',
				chr( 195 ) . chr( 139 )              => 'E',
				chr( 195 ) . chr( 140 )              => 'I',
				chr( 195 ) . chr( 141 )              => 'I',
				chr( 195 ) . chr( 142 )              => 'I',
				chr( 195 ) . chr( 143 )              => 'I',
				chr( 195 ) . chr( 145 )              => 'N',
				chr( 195 ) . chr( 146 )              => 'O',
				chr( 195 ) . chr( 147 )              => 'O',
				chr( 195 ) . chr( 148 )              => 'O',
				chr( 195 ) . chr( 149 )              => 'O',
				chr( 195 ) . chr( 150 )              => 'Oe',
				chr( 195 ) . chr( 153 )              => 'U',
				chr( 195 ) . chr( 154 )              => 'U',
				chr( 195 ) . chr( 155 )              => 'U',
				chr( 195 ) . chr( 156 )              => 'Ue',
				chr( 195 ) . chr( 157 )              => 'Y',
				chr( 195 ) . chr( 159 )              => 'ss',
				chr( 195 ) . chr( 160 )              => 'a',
				chr( 195 ) . chr( 161 )              => 'a',
				chr( 195 ) . chr( 162 )              => 'a',
				chr( 195 ) . chr( 163 )              => 'a',
				chr( 195 ) . chr( 164 )              => 'ae',
				chr( 195 ) . chr( 165 )              => 'a',
				chr( 195 ) . chr( 167 )              => 'c',
				chr( 195 ) . chr( 168 )              => 'e',
				chr( 195 ) . chr( 169 )              => 'e',
				chr( 195 ) . chr( 170 )              => 'e',
				chr( 195 ) . chr( 171 )              => 'e',
				chr( 195 ) . chr( 172 )              => 'i',
				chr( 195 ) . chr( 173 )              => 'i',
				chr( 195 ) . chr( 174 )              => 'i',
				chr( 195 ) . chr( 175 )              => 'i',
				chr( 195 ) . chr( 177 )              => 'n',
				chr( 195 ) . chr( 178 )              => 'o',
				chr( 195 ) . chr( 179 )              => 'o',
				chr( 195 ) . chr( 180 )              => 'o',
				chr( 195 ) . chr( 181 )              => 'o',
				chr( 195 ) . chr( 182 )              => 'oe',
				chr( 195 ) . chr( 185 )              => 'u',
				chr( 195 ) . chr( 186 )              => 'u',
				chr( 195 ) . chr( 187 )              => 'u',
				chr( 195 ) . chr( 188 )              => 'ue',
				chr( 195 ) . chr( 189 )              => 'y',
				chr( 195 ) . chr( 191 )              => 'y', /* Latin Extended-A */
				chr( 196 ) . chr( 128 )              => 'A',
				chr( 196 ) . chr( 129 )              => 'a',
				chr( 196 ) . chr( 130 )              => 'A',
				chr( 196 ) . chr( 131 )              => 'a',
				chr( 196 ) . chr( 132 )              => 'A',
				chr( 196 ) . chr( 133 )              => 'a',
				chr( 196 ) . chr( 134 )              => 'C',
				chr( 196 ) . chr( 135 )              => 'c',
				chr( 196 ) . chr( 136 )              => 'C',
				chr( 196 ) . chr( 137 )              => 'c',
				chr( 196 ) . chr( 138 )              => 'C',
				chr( 196 ) . chr( 139 )              => 'c',
				chr( 196 ) . chr( 140 )              => 'C',
				chr( 196 ) . chr( 141 )              => 'c',
				chr( 196 ) . chr( 142 )              => 'D',
				chr( 196 ) . chr( 143 )              => 'd',
				chr( 196 ) . chr( 144 )              => 'D',
				chr( 196 ) . chr( 145 )              => 'd',
				chr( 196 ) . chr( 146 )              => 'E',
				chr( 196 ) . chr( 147 )              => 'e',
				chr( 196 ) . chr( 148 )              => 'E',
				chr( 196 ) . chr( 149 )              => 'e',
				chr( 196 ) . chr( 150 )              => 'E',
				chr( 196 ) . chr( 151 )              => 'e',
				chr( 196 ) . chr( 152 )              => 'E',
				chr( 196 ) . chr( 153 )              => 'e',
				chr( 196 ) . chr( 154 )              => 'E',
				chr( 196 ) . chr( 155 )              => 'e',
				chr( 196 ) . chr( 156 )              => 'G',
				chr( 196 ) . chr( 157 )              => 'g',
				chr( 196 ) . chr( 158 )              => 'G',
				chr( 196 ) . chr( 159 )              => 'g',
				chr( 196 ) . chr( 160 )              => 'G',
				chr( 196 ) . chr( 161 )              => 'g',
				chr( 196 ) . chr( 162 )              => 'G',
				chr( 196 ) . chr( 163 )              => 'g',
				chr( 196 ) . chr( 164 )              => 'H',
				chr( 196 ) . chr( 165 )              => 'h',
				chr( 196 ) . chr( 166 )              => 'H',
				chr( 196 ) . chr( 167 )              => 'h',
				chr( 196 ) . chr( 168 )              => 'I',
				chr( 196 ) . chr( 169 )              => 'i',
				chr( 196 ) . chr( 170 )              => 'I',
				chr( 196 ) . chr( 171 )              => 'i',
				chr( 196 ) . chr( 172 )              => 'I',
				chr( 196 ) . chr( 173 )              => 'i',
				chr( 196 ) . chr( 174 )              => 'I',
				chr( 196 ) . chr( 175 )              => 'i',
				chr( 196 ) . chr( 176 )              => 'I',
				chr( 196 ) . chr( 177 )              => 'i',
				chr( 196 ) . chr( 178 )              => 'IJ',
				chr( 196 ) . chr( 179 )              => 'ij',
				chr( 196 ) . chr( 180 )              => 'J',
				chr( 196 ) . chr( 181 )              => 'j',
				chr( 196 ) . chr( 182 )              => 'K',
				chr( 196 ) . chr( 183 )              => 'k',
				chr( 196 ) . chr( 184 )              => 'k',
				chr( 196 ) . chr( 185 )              => 'L',
				chr( 196 ) . chr( 186 )              => 'l',
				chr( 196 ) . chr( 187 )              => 'L',
				chr( 196 ) . chr( 188 )              => 'l',
				chr( 196 ) . chr( 189 )              => 'L',
				chr( 196 ) . chr( 190 )              => 'l',
				chr( 196 ) . chr( 191 )              => 'L',
				chr( 197 ) . chr( 128 )              => 'l',
				chr( 197 ) . chr( 129 )              => 'L',
				chr( 197 ) . chr( 130 )              => 'l',
				chr( 197 ) . chr( 131 )              => 'N',
				chr( 197 ) . chr( 132 )              => 'n',
				chr( 197 ) . chr( 133 )              => 'N',
				chr( 197 ) . chr( 134 )              => 'n',
				chr( 197 ) . chr( 135 )              => 'N',
				chr( 197 ) . chr( 136 )              => 'n',
				chr( 197 ) . chr( 137 )              => 'N',
				chr( 197 ) . chr( 138 )              => 'n',
				chr( 197 ) . chr( 139 )              => 'N',
				chr( 197 ) . chr( 140 )              => 'O',
				chr( 197 ) . chr( 141 )              => 'o',
				chr( 197 ) . chr( 142 )              => 'O',
				chr( 197 ) . chr( 143 )              => 'o',
				chr( 197 ) . chr( 144 )              => 'O',
				chr( 197 ) . chr( 145 )              => 'o',
				chr( 197 ) . chr( 146 )              => 'OE',
				chr( 197 ) . chr( 147 )              => 'oe',
				chr( 197 ) . chr( 148 )              => 'R',
				chr( 197 ) . chr( 149 )              => 'r',
				chr( 197 ) . chr( 150 )              => 'R',
				chr( 197 ) . chr( 151 )              => 'r',
				chr( 197 ) . chr( 152 )              => 'R',
				chr( 197 ) . chr( 153 )              => 'r',
				chr( 197 ) . chr( 154 )              => 'S',
				chr( 197 ) . chr( 155 )              => 's',
				chr( 197 ) . chr( 156 )              => 'S',
				chr( 197 ) . chr( 157 )              => 's',
				chr( 197 ) . chr( 158 )              => 'S',
				chr( 197 ) . chr( 159 )              => 's',
				chr( 197 ) . chr( 160 )              => 'S',
				chr( 197 ) . chr( 161 )              => 's',
				chr( 197 ) . chr( 162 )              => 'T',
				chr( 197 ) . chr( 163 )              => 't',
				chr( 197 ) . chr( 164 )              => 'T',
				chr( 197 ) . chr( 165 )              => 't',
				chr( 197 ) . chr( 166 )              => 'T',
				chr( 197 ) . chr( 167 )              => 't',
				chr( 197 ) . chr( 168 )              => 'U',
				chr( 197 ) . chr( 169 )              => 'u',
				chr( 197 ) . chr( 170 )              => 'U',
				chr( 197 ) . chr( 171 )              => 'u',
				chr( 197 ) . chr( 172 )              => 'U',
				chr( 197 ) . chr( 173 )              => 'u',
				chr( 197 ) . chr( 174 )              => 'U',
				chr( 197 ) . chr( 175 )              => 'u',
				chr( 197 ) . chr( 176 )              => 'U',
				chr( 197 ) . chr( 177 )              => 'u',
				chr( 197 ) . chr( 178 )              => 'U',
				chr( 197 ) . chr( 179 )              => 'u',
				chr( 197 ) . chr( 180 )              => 'W',
				chr( 197 ) . chr( 181 )              => 'w',
				chr( 197 ) . chr( 182 )              => 'Y',
				chr( 197 ) . chr( 183 )              => 'y',
				chr( 197 ) . chr( 184 )              => 'Y',
				chr( 197 ) . chr( 185 )              => 'Z',
				chr( 197 ) . chr( 186 )              => 'z',
				chr( 197 ) . chr( 187 )              => 'Z',
				chr( 197 ) . chr( 188 )              => 'z',
				chr( 197 ) . chr( 189 )              => 'Z',
				chr( 197 ) . chr( 190 )              => 'z',
				chr( 197 ) . chr( 191 )              => 's', /* Euro Sign */
				chr( 226 ) . chr( 130 ) . chr( 172 ) => 'E', /* GBP (Pound) Sign */
				chr( 194 ) . chr( 163 )              => ''
			);

			$s = strtr( $s, $_chr );
		}
		else
		{
			$_chr      = array();
			$_dblChars = array();

			/* We assume ISO-8859-1 if not UTF-8 */
			$_chr[ 'in' ] =
				chr( 128 ) . chr( 131 ) . chr( 138 ) . chr( 142 ) . chr( 154 ) . chr( 158 ) . chr( 159 ) . chr( 162 ) . chr( 165 ) . chr( 181 ) . chr( 192 ) . chr( 193 ) . chr( 194 ) . chr( 195 ) .
				chr( 199 ) . chr( 200 ) . chr( 201 ) . chr( 202 ) . chr( 203 ) . chr( 204 ) . chr( 205 ) . chr( 206 ) . chr( 207 ) . chr( 209 ) . chr( 210 ) . chr( 211 ) . chr( 212 ) . chr( 213 ) .
				chr( 217 ) . chr( 218 ) . chr( 219 ) . chr( 220 ) . chr( 221 ) . chr( 224 ) . chr( 225 ) . chr( 226 ) . chr( 227 ) . chr( 231 ) . chr( 232 ) . chr( 233 ) . chr( 234 ) . chr( 235 ) .
				chr( 236 ) . chr( 237 ) . chr( 238 ) . chr( 239 ) . chr( 241 ) . chr( 242 ) . chr( 243 ) . chr( 244 ) . chr( 245 ) . chr( 249 ) . chr( 250 ) . chr( 251 ) . chr( 252 ) . chr( 253 ) .
				chr( 255 ) . chr( 191 ) . chr( 182 ) . chr( 179 ) . chr( 166 ) . chr( 230 ) . chr( 198 ) . chr( 175 ) . chr( 172 ) . chr( 188 ) . chr( 163 ) . chr( 161 ) . chr( 177 );

			$_chr[ 'out' ] = "EfSZszYcYuAAAACEEEEIIIINOOOOUUUUYaaaaceeeeiiiinoooouuuuyyzslScCZZzLAa";

			$s                  = strtr( $s, $_chr[ 'in' ], $_chr[ 'out' ] );
			$_dblChars[ 'in' ]  = array(
				chr( 140 ),
				chr( 156 ),
				chr( 196 ),
				chr( 197 ),
				chr( 198 ),
				chr( 208 ),
				chr( 214 ),
				chr( 216 ),
				chr( 222 ),
				chr( 223 ),
				chr( 228 ),
				chr( 229 ),
				chr( 230 ),
				chr( 240 ),
				chr( 246 ),
				chr( 248 ),
				chr( 254 )
			);
			$_dblChars[ 'out' ] = array( 'Oe', 'oe', 'Ae', 'Aa', 'Ae', 'DH', 'Oe', 'Oe', 'TH', 'ss', 'ae', 'aa', 'ae', 'dh', 'oe', 'oe', 'th' );
			$s                  = str_replace( $_dblChars[ 'in' ], $_dblChars[ 'out' ], $s );
		}

		return $s;
	}


	/**
	 * Convert Windows/MacOS9 line delimiters to their Unix counterpart
	 *
	 * @param    string    Input String
	 *
	 * @return   string    Parsed String
	 */
	public function sanitize__convert_line_delimiters_to_unix__low ( &$val )
	{
		return $val = str_replace( array( "\r\n", "\n\r", "\r" ), "\n", $val );
	}


	/**
	 * Convert a string between charsets
	 *
	 * @param   string    Input String
	 * @param   string    Current char set
	 * @param   string    Destination char set
	 *
	 * @return  string    Parsed string
	 * @todo    [Future] If an error is set in classConvertCharset, show it or log it somehow
	 */
	public function parse__convert_text_character_encoding__high ( $text, $from_encoding, $to_encoding = "UTF-8" )
	{
		$from_encoding = strtolower( $from_encoding );
		$t             = $text;

		//-----------------
		// Not the same?
		//-----------------

		if ( $to_encoding == $from_encoding )
		{
			return $t;
		}

		if ( !is_object( $this->encoding_converter ) )
		{
			require_once( PATH_LIBS . '/IPS_Sources/classConvertCharset.php' );
			$this->encoding_converter = new classConvertCharset();

			/*if ( function_exists( 'mb_convert_encoding' ) )
			{
				$this->encoding_converter->method = 'mb';
			}
			elseif ( function_exists( 'iconv' ) )
			{
				$this->encoding_converter->method = 'iconv';
			}
			elseif ( function_exists( 'recode_string' ) )
			{
				$this->encoding_converter->method = 'recode';
			}
			else
			{
			*/
			$this->encoding_converter->method = 'internal';
			//}
		}

		$text = $this->encoding_converter->convertEncoding( $text, $from_encoding, $to_encoding );

		return $text
			? $text
			: $t;
	}


	/**
	 * Similar to htmlspecialchars(), but is more careful with entities in &#123; format.
	 *
	 * @param    string    Input String
	 *
	 * @return   string    Parsed String
	 */
	public function htmlspecialchars ( $t )
	{
		$t = preg_replace( "/&(?!#[0-9]+;)/s", '&amp;', $t ); // Use forward look up to only convert & not &#123;
		$t = str_replace( "<", "&lt;", $t );
		$t = str_replace( ">", "&gt;", $t );
		$t = str_replace( '"', "&quot;", $t );
		$t = str_replace( "'", "&#39;", $t );

		return $t;
	}


	/**
	 * Get the true length of a multi-byte character string
	 *
	 * @param    string     Input String
	 *
	 * @return   integer    String length
	 */
	public function mb_strlen ( $t )
	{
		if ( function_exists( 'mb_list_encodings' ) )
		{
			$encodings = mb_list_encodings();

			if ( in_array( "UTF-8", array_map( 'strtoupper', $encodings ) ) )
			{
				if ( mb_internal_encoding() != 'UTF-8' )
				{
					mb_internal_encoding( "UTF-8" );
				}

				return mb_strlen( $t );
			}
		}

		return strlen( preg_replace( "/&#([0-9]+);/", "-", $t ) );
	}


	/**
	 * Convert text for use in form elements (text-input and textarea)
	 *
	 * @param    mixed      Input String/Array (of strings)
	 *
	 * @return   string     Parsed String
	 */
	public function raw2form ( &$t )
	{
		if ( is_array( $t ) )
		{
			array_walk( $t, array( $this, "raw2form" ) );
		}
		else
		{
			$t = str_replace( '$', "&#36;", $t );

			/*
			if ( MAGIC_QUOTES_GPC_ON )
			{
				$t = stripslashes($t);
			}
			*/

			$t = preg_replace( '/\\\(?!&amp;#|\?#)/', "&#92;", $t );

			return $t;
		}
	}


	/**
	 * Convert text for use in form elements (text-input and textarea)
	 *
	 * @param    string   Input String
	 *
	 * @return   string   Parsed String
	 */
	public function text2form ( &$t )
	{
		if ( is_array( $t ) )
		{
			array_walk( $t, array( $this, "text2form" ) );
		}
		else
		{
			$t = str_replace( "&#38;", "&", $t );
			$t = str_replace( "&#60;", "<", $t );
			$t = str_replace( "&#62;", ">", $t );
			$t = str_replace( "&#34;", '"', $t );
			$t = str_replace( "&#39;", "'", $t );
			$t = str_replace( "&#33;", "!", $t );
			$t = str_replace( "&#46;&#46;/", "../", $t );
		}
	}


	/**
	 * Cleaned form data back to text
	 *
	 * @param    string    Input String
	 *
	 * @return   string    Parsed String
	 */
	public function form2text ( &$t )
	{
		if ( is_array( $t ) )
		{
			array_walk( $t, array( $this, "form2text" ) );
		}
		else
		{
			$t = str_replace( "&", "&#038;", $t );
			$t = str_replace( "<", "&#060;", $t );
			$t = str_replace( ">", "&#062;", $t );
			$t = str_replace( '"', "&#034;", $t );
			$t = str_replace( "'", '&#039;', $t );
		}
	}


	/**
	 * Converts string to hexadecimal
	 *
	 * @param    string   String to convert
	 *
	 * @return   string   Resulting hexadecimal value
	 */
	public function strhex ( $string )
	{
		$hex = "";
		for ( $i = 0; $i < strlen( $string ); $i++ )
		{
			$hex .= dechex( ord( $string[ $i ] ) );
		}

		return $hex;
	}


	/**
	 * Converts hexadecimal to string
	 *
	 * @param    string   Hexadecimal to convert
	 *
	 * @return   string   Resulting string
	 */
	public function hexstr ( $hex )
	{
		$string = "";
		for ( $i = 0; $i < strlen( $hex ) - 1; $i += 2 )
		{
			$string .= chr( hexdec( $hex[ $i ] . $hex[ $i + 1 ] ) );
		}

		return $string;
	}


	/**
	 * Make an SEO title for use in the URL
	 *
	 * @param    string    Raw SEO title or text
	 *
	 * @return   string    Cleaned up SEO title
	 */
	public function make_seo_title ( $text )
	{
		if ( !$text )
		{
			return "";
		}

		// $text = str_replace( array( '`', ' ', '+', '.', '?', '_', '#' ), '-', $text );

		# Doesn't need converting?
		/*
		if ( preg_match( "#^[a-zA-Z0-9\-]+$#", $_text ) )
		{
			$_text = $this->clean__excessive_separators( $_text, "-" );
			return $_text;
		}
		*/

		# Strip all HTML tags first
		$text = strip_tags( $text );

		# Preserve %data
		$text = preg_replace( '#%([a-fA-F0-9][a-fA-F0-9])#', '-xx-$1-xx-', $text );
		$text = str_replace( array( '%', '`' ), '', $text );
		$text = preg_replace( '#-xx-([a-fA-F0-9][a-fA-F0-9])-xx-#', '%$1', $text );

		# Convert accented chars
		$text = $this->parse__convert_accents_to_english__high( $text );

		# Convert it
		if ( function_exists( 'mb_strtolower' ) )
		{
			$text = mb_strtolower( $text, "UTF-8" );
		}

		$text = $this->utf8_encode_to_specific_length( $text, 250 );

		# Finish off
		$text = strtolower( $text );

		$text = preg_replace( '#&.+?;#', '', $text );
		$text = preg_replace( '#[^%a-z0-9 _-]#', '', $text );

		$text = str_replace( array( '`', ' ', '+', '.', '?', '_', '#' ), '-', $text );
		$text = $this->clean__excessive_separators( $text, "-" );

		return ( $text )
			? $text
			: '-';
	}


	/**
	 * Check if the string is valid for the specified encoding - Build 20080614
	 *
	 * @param   string     Byte stream to check
	 * @param   string     Expected encoding
	 * @param   string     Encoding type for double-checking, since mb_check_encoding() function of MBString extension sometimes does wrong encoding checks
	 *
	 * @return  boolean    Returns 1 on success, 0 on failure and -1 if MBString extension is not available
	 */
	public function validate__check_mbstring_encoding ( $string, $target_encoding, $secondary_encoding = null )
	{
		if ( !in_array( "mbstring", $this->Registry->config[ 'runtime' ][ 'loaded_extensions' ] ) )
		{
			return -1;
		}

		if ( $secondary_encoding )
		{
			if ( mb_check_encoding( $string, $target_encoding )
			     and
			     mb_substr_count(
				     $string,
				     '?',
				     $secondary_encoding
			     ) == mb_substr_count( mb_convert_encoding( $string, $target_encoding, $secondary_encoding ), '?', $target_encoding )
			)
			{
				return 1;
			}
			else
			{
				return 0;
			}
		}
		else
		{
			if ( mb_check_encoding( $string, $target_encoding ) )
			{
				return 1;
			}
			else
			{
				return 0;
			}
		}
	}


	/**
	 * Fetches environmental variable by key - Build 20080824
	 *
	 * @param  string $key  ENV var key to fetch a value for
	 *
	 * @return string       Environment variable value requested
	 */
	public function my_getenv ( $key )
	{
		if ( is_array( $_SERVER ) and count( $_SERVER ) )
		{
			if ( isset( $_SERVER[ $key ] ) )
			{
				$return = $_SERVER[ $key ];
			}
		}

		if ( !isset( $return ) or empty( $return ) )
		{
			$return = getenv( $key );
		}

		return $return;
	}


	/**
	 * Get a cookie
	 *
	 * @param   String   Cookie name
	 *
	 * @return  Mixed    Cookie value on success, FALSE on failure
	 */
	public function my_getcookie ( $name )
	{
		if ( isset( $this->_cookie_set[ $name ] ) )
		{
			return $this->_cookie_set[ $name ];
		}

		$cookie_id = $this->Registry->config[ 'cookies' ][ 'cookie_id' ];

		if ( isset( $_COOKIE[ $cookie_id . $name ] ) )
		{
			return $this->sanitize__clean_raw_value__medium( $_COOKIE[ $cookie_id . $name ], array( "urldecode" ), true );
		}
		else
		{
			return false;
		}
	}


	/**
	 * My setcookie() function
	 *
	 * @param   string    Cookie name
	 * @param   mixed     Cookie value
	 * @param   integer   Is cookie sticky (lifespan = 1 year)
	 * @param   integer   Cookie lifetime
	 *
	 * @return  void
	 */
	public function my_setcookie ( $name, $value = "", $is_sticky = 1, $expires_x_days = 0 )
	{
		# Auto-serialize arrays
		if ( is_array( $value ) )
		{
			$value = serialize( $value );
		}

		# Expiry time
		if ( $is_sticky )
		{
			$lifetime   = 86400 * 365;
			$expires_at = UNIX_TIME_NOW + $lifetime;
		}
		else if ( $expires_x_days and is_numeric( $expires_x_days ) )
		{
			$lifetime   = 86400 * $expires_x_days;
			$expires_at = UNIX_TIME_NOW + $lifetime;
		}
		else
		{
			$expires_at = false;
		}

		# Cookie domain and path
		$cookie_id       = $this->Registry->config[ 'cookies' ][ 'cookie_id' ];
		$cookie_domain   = $this->Registry->config[ 'cookies' ][ 'cookie_domain' ];
		$cookie_path     = $this->Registry->config[ 'cookies' ][ 'cookie_path' ];
		$cookie_secure   = false;
		$cookie_httponly = false;

		if ( in_array( $name, $this->sensitive_cookies ) )
		{
			$cookie_httponly = true;
		}

		# Set cookie
		setcookie( $cookie_id . $name, $value, $expires_at, $cookie_path, $cookie_domain, $cookie_secure, $cookie_httponly );

		# Internal Cookie-set
		$this->_cookie_set[ $name ] = $value;
	}


	/**
	 * My parse_url() that parses current REQUEST_URI; additionally, it makes sure that working domain is valid - redirects to valid one otherwise
	 *
	 * @return   mixed   Array of parsed URL or FALSE on failure
	 */
	private function parse__parse_request_uri__medium ()
	{
		$_url = ( empty( $_SERVER[ 'HTTPS' ] ) or $_SERVER[ 'HTTPS' ] == 'off' )
			? "http://"
			: "https://";
		$_url .= $_SERVER[ 'HTTP_HOST' ]
			? $_SERVER[ 'HTTP_HOST' ]
			: $_SERVER[ 'SERVER_NAME' ];
		$_url .= $_SERVER[ 'REQUEST_URI' ];
		$_parsed_url                    = parse_url( $_url );
		$_parsed_url[ 'path' ]          = trim( $_parsed_url[ 'path' ], '\/' );
		$_parsed_url[ 'path_exploded' ] = explode( "/", $_parsed_url[ 'path' ] );
		$_parsed_url[ 'path' ]          = "/" . $_parsed_url[ 'path' ];

		$_parsed_url[ 'request_uri' ] = $_parsed_url[ 'scheme' ] . "://" .
		                                ( ( @$_parsed_url[ 'user' ] and @$_parsed_url[ 'pass' ] )
			                                ? $_parsed_url[ 'user' ] . ":" . $_parsed_url[ 'pass' ] . "@"
			                                : "" ) . $this->Registry->config[ 'url' ][ 'hostname' ][ $_parsed_url[ 'scheme' ] ] . $_parsed_url[ 'path' ] .
		                                ( @$_parsed_url[ 'query' ]
			                                ? "?" . $_parsed_url[ 'query' ]
			                                : "" );

		# Redirect to default domain-name if request was sent to different domain
		if ( $_parsed_url[ 'host' ] != $this->Registry->config[ 'url' ][ 'hostname' ][ $_parsed_url[ 'scheme' ] ] )
		{
			$this->Registry->logger__do_log( "Registry: Request redirection to location: " . $_parsed_url[ 'request_uri' ], "INFO" );
			//$this->Registry->http_redirect( $_parsed_url['request_uri'] , 301 );
		}

		return $_parsed_url;
	}


	/**
	 * Validates file extension by checking its contents in a BINARY level
	 *
	 * @param    string   FULL-ABSOLUTE path to file
	 *
	 * @return   mixed    TRUE on success; FALSE or RESULT CODES otherwise
	 * RESULT CODES:
	 *     "IS_NOT_FILE"       - Either it is not a regular file, or it does not exist at all
	 *     "IS_NOT_READABLE"   - File is not READABLE
	 *     "FILETYPE_INVALID"  - No such filetype-record was found in our MIMELIST
	 */
	public function file__extension__do_validate ( $full_path_to_file )
	{
		//----------
		// Prelim
		//----------

		# Does it exist and is it a regular file?
		if ( is_file( $full_path_to_file ) !== true )
		{
			if ( IN_DEV )
			{
				$this->Registry->logger__do_log( __CLASS__ . "::" . __METHOD__ . " - " . $full_path_to_file . " is NOT a REGULAR FILE or does NOT EXIST at all!", "ERROR" );
			}

			return "IS_NOT_FILE";
		}

		# Is it readable?
		if ( is_readable( $full_path_to_file ) !== true )
		{
			if ( IN_DEV )
			{
				$this->Registry->logger__do_log( __CLASS__ . "::" . __METHOD__ . " - Cannot READ file: " . $full_path_to_file, "ERROR" );
			}

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
			if ( IN_DEV )
			{
				$this->Registry->logger__do_log( __CLASS__ . "::" . __METHOD__ . " - " . $_file_path__parsed[ 'extension' ] . " - NO SUCH FILETYPE IN our MIMELIST records!", "ERROR" );
			}

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

		if ( IN_DEV )
		{
			$this->Registry->logger__do_log( __CLASS__ . "::" . __METHOD__ . " - File: " . $full_path_to_file . " FAILED VALIDATION!", "ERROR" );
		}

		return false;
	}


	/**
	 * Attaches a suffix to filename (prior to extension)
	 *
	 * @param     string     Absolute or relative filepath
	 * @param     string     Suffix to be attached
	 *
	 * @return    string     Final filepath
	 */
	public function file__filename__attach_suffix ( $absolute_or_relative_filepath, $suffix )
	{
		return preg_replace( '/(\.[a-z0-9]+)$/i', $suffix . "\\1", $absolute_or_relative_filepath );
	}


	/**
	 * Calculates filesize : >4GB savvy :)
	 * NOTE: This method is slower than standard filesize(), so use only when necessary
	 *
	 * @param    string    Path to file
	 *
	 * @return   mixed     (float) Filesize parsed on success; (boolean) FALSE otherwise
	 */
	public function file__filesize__do_get ( $path )
	{
		if ( file_exists( $path ) and is_file( $path ) )
		{
			$_filesize = filesize( $path );
			if ( $_filesize < 0 )
			{
				if ( !strtolower( substr( PHP_OS, 0, 3 ) ) == 'win' )
				{
					$_filesize = trim( exec( "stat -c%s " . $path ) );
				}
				else
				{
					$_filesize = trim( exec( "for %v in (\"" . $path . "\") do @echo %~zv" ) );
				}
			}
			settype( $_filesize, "float" );

			return $_filesize;
		}
		else
		{
			return false;
		}
	}


	/**
	 * Raw filesize parsing
	 *
	 * @param     string    Filesize to parse
	 *
	 * @return    mixed     (array) Parsed filesize with correct suffix on success; (boolean) FALSE otherwise
	 */
	public function file__filesize__do_parse ( $filesize )
	{
		//-------------------------------------------------------------------------------------
		// Is it in bytes, or does it use decimal-suffixes ("K" for kilo, "Ki" for kibi, etc)
		//-------------------------------------------------------------------------------------

		$filesize = preg_replace( '/[^\d\.ikmgtpe]/i', "", $filesize );
		if ( !preg_match( '/^(?P<coefficient>[\d]+\.?[\d]*)\s*(?P<suffix>[a-z]{0,2})$/i', $filesize, $_matches ) )
		{
			return false;
		}

		# Fix Suffixes
		switch ( strtolower( $_matches[ 'suffix' ] ) )
		{
			case 'k':
				$_matches[ 'suffix' ] = " Ki";
				break;
			case 'm':
				$_matches[ 'suffix' ] = " Mi";
				break;
			case 'g':
				$_matches[ 'suffix' ] = " Gi";
				break;
			case 't':
				$_matches[ 'suffix' ] = " Ti";
				break;
			case 'p':
				$_matches[ 'suffix' ] = " Pi";
				break;
			case 'e':
				$_matches[ 'suffix' ] = " Ei";
				break;
		}

		if ( $_matches[ 'coefficient' ] > 900 ) // In order to show the coefficients < 1
		{
			# Update suffixes
			switch ( strtolower( $_matches[ 'suffix' ] ) )
			{
				case 'ki':
					$_matches[ 'suffix' ] = " Mi";
					break;
				case 'mi':
					$_matches[ 'suffix' ] = " Gi";
					break;
				case 'gi':
					$_matches[ 'suffix' ] = " Ti";
					break;
				case 'ti':
					$_matches[ 'suffix' ] = " Pi";
					break;
				case 'pi':
					$_matches[ 'suffix' ] = " Ei";
					break;
				default:
					$_matches[ 'suffix' ] = " Ki";
					break;
			}
			$_matches[ 'coefficient' ] /= 1024;

			return $this->file__filesize__do_parse( $_matches[ 'coefficient' ] . $_matches[ 'suffix' ] );
		}
		# Base-code for Recursion
		else
		{
			return sprintf( "%.2f", $_matches[ 'coefficient' ] ) . " " . $_matches[ 'suffix' ];
		}
	}


	/**
	 * Determines the type of file according to its MIME-type.
	 *
	 * @param    string    MIME-type
	 *
	 * @return   string    File-type - one of the following: audio, video, image, application
	 */
	public function file__filetype__do_parse ( $mime )
	{
		$_mime_exploded = explode( "/", $mime );

		return $_mime_exploded[ 0 ];
	}


	/**
	 * Safe fseek() - presumably should work with >2Gb files as well, but failed so far
	 *
	 * @param   resource   File handler, typically created by fopen()
	 * @param   integer    Position to seek
	 *
	 * @return  integer    0 on success, -1 otherwise
	 */
	public function file__fseek_safe ( $file_handler, $position )
	{
		if ( !is_resource( $file_handler ) )
		{
			return -1;
		}

		fseek( $file_handler, 0, SEEK_SET );

		if ( bccomp( $position, PHP_INT_MAX ) != 1 )
		{
			return fseek( $file_handler, $position, SEEK_SET );
		}

		$t_offset = PHP_INT_MAX;
		$position -= $t_offset;

		while ( fseek( $file_handler, $t_offset, SEEK_CUR ) === 0 )
		{
			if ( bccomp( $position, PHP_INT_MAX ) == 1 )
			{
				$t_offset = PHP_INT_MAX;
				$position -= $t_offset;
			}
			elseif ( $position > 0 )
			{
				$t_offset = $position;
				$position = 0;
			}
			else
			{
				return 0;
			}
		}

		return -1;
	}


	/**
	 * Manipulates the current QUERY_STRING (adds, alters, removes values)
	 *
	 * @param   array    Parameter to add/alter/remove in format array( 'key' => string [ , 'value' => mixed ] )
	 * @param   string   What to do.
	 * @param   boolean  Whether to update $this->query_string_* properties or not
	 *
	 * @return  mixed    (boolean) FALSE if error, (string) formatted QUERY_STRING otherwise
	 */
	public function query_string__do_process ( $parameter, $action = "", $_do_update_internals = false )
	{
		if ( !is_array( $parameter )
		     or
		     ( is_array( $parameter )
		       and
		       ( # 'alter_add' requires both KEY and VALUE pairs to exist
			       ( in_array( $action, array( "alter_add", "+" ) )
			         and
			         ( !isset( $parameter[ 'key' ] )
			           or
			           !isset( $parameter[ 'value' ] ) ) )
			       or
			       # 'delete' requires KEY to exist
			       ( in_array( $action, array( "delete", "-" ) )
			         and
			         !isset( $parameter[ 'key' ] ) ) ) )
		)
		{
			return false;
		}
		else
		{
			$_parameter[ 'key' ]   = $this->sanitize__clean_raw_key__low( $parameter[ 'key' ] );
			$_parameter[ 'value' ] = $this->sanitize__clean_raw_value__medium( $parameter[ 'value' ], array( "urlencode" ), true );
		}

		$_new_query_string = null;
		if ( preg_match( '/^(?P<key>[^\[\]]+)(?:\[(?P<index>[^\[\]]+)\])?$/i', $_parameter[ 'key' ], $_array_matches ) )
		{
			$_new_query_string              = $this->get;
			$_what_to_match_in_query_string = null;
			if ( isset( $_array_matches[ 'key' ] ) )
			{
				if ( isset( $_array_matches[ 'index' ] ) )
				{
					$_what_to_match_in_query_string =& $_new_query_string[ $_array_matches[ 'key' ] ][ $_array_matches[ 'index' ] ];
				}
				else
				{
					$_what_to_match_in_query_string =& $_new_query_string[ $_array_matches[ 'key' ] ];
				}
			}
		}

		switch ( $action )
		{
			case 'alter_add':
			case '+':
				$_what_to_match_in_query_string = $_parameter[ 'value' ];
				break;
			case 'delete':
			case '-':
				if ( isset( $_what_to_match_in_query_string ) )
				{
					unset( $_what_to_match_in_query_string );
				}
				break;
		}

		if ( $_do_update_internals )
		{
			$this->query_string_safe = $_new_query_string;

			array_walk( $this->query_string_safe, "urldecode" );
			array_walk( $this->query_string_safe, array( $this, "clean__excessive_separators" ) );

			$this->query_string_safe      = $this->clean__excessive_separators( $this->sanitize__clean_raw_value__medium( $this->query_string_safe, array( "urldecode" ), true ), "&amp;" );
			$this->query_string_real      = str_replace( '&amp;', '&', $this->query_string_safe );
			$this->query_string_formatted = preg_replace( "#s=([a-z0-9]){32}#", '', $this->query_string_safe );
		}

		return http_build_query( $_new_query_string );
	}


	/**
	 * Parses data from/to member's ban-line DB record
	 *
	 * @param    mixed    Data to parse from/to
	 *
	 * @return   mixed    Data parsed
	 */
	public function session__handle_ban_line ( $bline )
	{
		if ( is_array( $bline ) )
		{
			# Set ( 'timespan' 'unit' )

			$factor = $bline[ 'unit' ] == 'd'
				? 86400
				: 3600;

			$date_end = time() + ( $bline[ 'timespan' ] * $factor );

			return time() . ':' . $date_end . ':' . $bline[ 'timespan' ] . ':' . $bline[ 'unit' ];
		}
		else
		{
			$arr = array();

			list( $arr[ 'date_start' ], $arr[ 'date_end' ], $arr[ 'timespan' ], $arr[ 'unit' ] ) = explode( ":", $bline );

			return $arr;
		}
	}


	/**
	 * Manually utf8 encode to a specific length
	 * Based on notes found at php.net
	 *
	 * @param    string     Raw text
	 * @param    integer    Length
	 *
	 * @return   string
	 * @author         $Author: matt $
	 * @copyright      (c) 2001 - 2010 Invision Power Services, Inc.
	 * @license        http://www.invisionpower.com/community/board/license.html
	 * @package        Invision Power Board
	 */
	public function utf8_encode_to_specific_length ( $string, $len = 0 )
	{
		$_unicode        = '';
		$_values         = array();
		$_nOctets        = 1;
		$_unicode_length = 0;
		$_string_length  = strlen( $string );

		for ( $i = 0; $i < $_string_length; $i++ )
		{
			$value = ord( $string[ $i ] );

			if ( $value < 128 )
			{
				if ( $len and ( $_unicode_length >= $len ) )
				{
					break;
				}

				$_unicode .= chr( $value );
				$_unicode_length++;
			}
			else
			{
				if ( count( $_values ) == 0 )
				{
					$_nOctets = ( $value < 224 )
						? 2
						: 3;
				}

				$_values[ ] = $value;

				if ( $len and ( $_unicode_length + ( $_nOctets * 3 ) ) > $len )
				{
					break;
				}

				if ( count( $_values ) == $_nOctets )
				{
					if ( $_nOctets == 3 )
					{
						$_unicode .= '%' . dechex( $_values[ 0 ] ) . '%' . dechex( $_values[ 1 ] ) . '%' . dechex( $_values[ 2 ] );
						$_unicode_length += 9;
					}
					else
					{
						$_unicode .= '%' . dechex( $_values[ 0 ] ) . '%' . dechex( $_values[ 1 ] );
						$_unicode_length += 6;
					}

					$_values  = array();
					$_nOctets = 1;
				}
			}
		}

		return $_unicode;
	}


	/**
	 * Converts UFT-8 into HTML entities (&#1xxx;) for correct display in browsers
	 *
	 * @param     string    UTF8 Encoded string
	 *
	 * @return    string    ..converted into HTML entities (similar to what a browser does with POST)
	 * @author         $Author: matt $
	 * @copyright      (c) 2001 - 2010 Invision Power Services, Inc.
	 * @license        http://www.invisionpower.com/community/board/license.html
	 * @package        Invision Power Board
	 */
	public function utf8__multibyte_sequence_to_html_entities ( $string )
	{
		/*
		 * @see http://en.wikipedia.org/wiki/UTF-8#Description
		 */

		# Four-byte chars
		$string = preg_replace(
			"/([\360-\364])([\200-\277])([\200-\277])([\200-\277])/e",
			"'&#' . ( ( ord('\\1') - 240 ) * 262144 + ( ord('\\2') - 128 ) * 4096 + ( ord('\\3') - 128 ) * 64 + ( ord('\\4') - 128 ) ) . ';'",
			$string
		);

		# Three-byte chars
		$string = preg_replace( "/([\340-\357])([\200-\277])([\200-\277])/e", "'&#' . ( ( ord('\\1') - 224 ) * 4096 + ( ord('\\2') - 128 ) * 64 + ( ord('\\3') - 128 ) ) . ';'", $string );

		# Two-byte chars
		$string = preg_replace( "/([\300-\337])([\200-\277])/e", "'&#' . ( ( ord('\\1') - 192 ) * 64 + ( ord('\\2') - 128 ) ) . ';'", $string );

		return $string;
	}


	/**
	 * Convert decimal character code (e.g.: 36899 for &#36899; ) to utf-8
	 *
	 * @param     mixed       Character code - either numeric code or complete entity with leading &# and trailing ;
	 *
	 * @return    string      Character
	 * @author         $Author: matt $
	 * @copyright      (c) 2001 - 2009 Invision Power Services, Inc.
	 * @license        http://www.invisionpower.com/community/board/license.html
	 * @package        Invision Power Board
	 */
	private function parse__convert_html_entities_to_utf8_multibyte_sequence__medium ( $code = 0 )
	{
		if ( preg_match( '/^\&\#\d+;$/', $code ) )
		{
			$code = preg_replace( '/[^\d]/', "", $code );
		}
		elseif ( !preg_match( '/^\d+$/', $code ) )
		{
			return chr( 0 );
		}

		$return = '';

		if ( $code < 0 )
		{
			return chr( 0 );
		}
		elseif ( $code <= 0x007f )
		{
			$return .= chr( $code );
		}
		elseif ( $code <= 0x07ff )
		{
			$return .= chr( 0xc0 | ( $code >> 6 ) );
			$return .= chr( 0x80 | ( $code & 0x003f ) );
		}
		elseif ( $code <= 0xffff )
		{
			$return .= chr( 0xe0 | ( $code >> 12 ) );
			$return .= chr( 0x80 | ( ( $code >> 6 ) & 0x003f ) );
			$return .= chr( 0x80 | ( $code & 0x003f ) );
		}
		elseif ( $code <= 0x10ffff )
		{
			$return .= chr( 0xf0 | ( $code >> 18 ) );
			$return .= chr( 0x80 | ( ( $code >> 12 ) & 0x3f ) );
			$return .= chr( 0x80 | ( ( $code >> 6 ) & 0x3f ) );
			$return .= chr( 0x80 | ( $code & 0x3f ) );
		}
		else
		{
			return chr( 0 );
		}

		return $return;
	}


	/**
	 * Seems like UTF-8?
	 *
	 * @param     string      Raw text
	 *
	 * @return    boolean
	 * @author    hmdker at gmail dot com
	 * @link      http://php.net/utf8_encode
	 */
	public function is_utf8 ( $s )
	{
		/*
    	 * @see http://en.wikipedia.org/wiki/UTF-8#Description
    	 */
		$c       = 0;
		$b       = 0;
		$byte_nr = 0;
		$len     = strlen( $s );
		for ( $i = 0; $i < $len; $i++ )
		{
			$c = ord( $s[ $i ] );
			if ( $c > 128 )
			{
				if ( $c >= 254 )
				{
					return false;
				}
				elseif ( $c >= 252 )
				{
					$byte_nr = 6; // Start of 6-byte sequence
				}
				elseif ( $c >= 248 )
				{
					$byte_nr = 5; // Start of 5-byte sequence
				}
				elseif ( $c >= 240 )
				{
					$byte_nr = 4; // Start of 4-byte sequence
				}
				elseif ( $c >= 224 )
				{
					$byte_nr = 3; // Start of 3-byte sequence
				}
				elseif ( $c >= 192 )
				{
					$byte_nr = 2; // Start of 2-byte sequence
				}
				else
				{
					return false; // Its single-byte sequence and single-byte sequences reside in range of \x0 - \x7F (0 - 127)
				}

				if ( ( $i + $byte_nr ) > $len )
				{
					return false;
				}

				# In UTF-8 encoded multi-byte string, bytes after first-one reside in range of \x80 - \xBF (128-191)
				while ( $byte_nr > 1 )
				{
					$i++;
					$b = ord( $s[ $i ] );
					if ( $b < 128 or $b > 191 )
					{
						return false;
					}
					$byte_nr--;
				}
			}
		}

		return true;
	}
}
