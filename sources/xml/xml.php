<?php

/**
 * Invision Power Services
 * IP.Board v3.0.0
 * XML Handler: Rewritten for PHP5
 * By Matt Mecham
 * Last Updated: $Date: 2009-05-11 04:34:54 -0400 (Mon, 11 May 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		Invision Power Services Kernel
 * @link		http://www.invisionpower.com
 * @since		25th February 2004
 * @version		$Revision: 262 $
 *
 *
 * Example Usage:
 * <code>
 * <productlist name="myname" version="1.0">
 *  <productgroup name="thisgroup">
 *   <product id="1.0">
 *    <description>This is a descrption</description>
 *    <title>Baked Beans</title>
 *    <room store="1">103</room>
 *   </product>
 *	 <product id="2.0">
 *    <description>This is another descrption</description>
 *    <title>Green Beans</title>
 *    <room store="2">104</room>
 *   </product>
 *  </productgroup>
 * </productlist>
 *
 * Creating...
 * $xml = new XML( 'utf-8' );
 * $xml->new_xml_document();
 *
 * /* Create a root element * /
 * $xml->add_element( 'productlist', '', array( 'name' => 'myname', 'version' => '1.0' ) );
 * /* Add a child.... * /
 * $xml->add_element( 'productgroup', 'productlist', array( 'name' => 'thisgroup' ) );
 * $xml->add_element_as_record( 'productgroup',
 * 									array( 'product', array( 'id' => '1.0' ) ),
 * 											array( 'description' => array( 'This is a description' ),
 * 												   'title'		 => array( 'Baked Beans' ),
 * 												   'room'		 => array( '103', array( 'store' => 1 ) )
 * 												)
 * 						);
 * $xml->add_element_as_record( 'productgroup',
 * 									array( 'product', array( 'id' => '2.0' ) ),
 * 											array( 'description' => array( 'This is another description' ),
 * 												   'title'		 => array( 'Green Beans' ),
 * 												   'room'		 => array( '104', array( 'store' => 2 ) )
 * 												)
 * 						);
 *
 * $xmlData = $xml->fetch_document();
 *
 * /*Convering XML into an array * /
 * $xml->load_xml( $xmlData );
 *
 * /* Grabbing specific data values from all 'products'... * /
 * foreach ( $xml->fetch_elements('product') as $products )
 * {
 * 	print $xml->fetch_item( $products, 'title' ) . "\";
 * }
 *
 * /* Prints... * /
 * Baked Beans
 * Green Beans
 *
 * /* Print all array data - auto converts XML_TEXT_NODE and XML_CDATA_SECTION_NODE into #alltext for brevity * /
 * print_r( $xml->fetch_xml_as_array() );
 *
 * /* Prints * /
 * Array
 * (
 *     [productlist] => Array
 *         (
 *             [@attributes] => Array
 *                 (
 *                     [name] => myname
 *                     [version] => 1.0
 *                 )
 *             [productgroup] => Array
 *                 (
 *                     [@attributes] => Array
 *                         (
 *                             [name] => thisgroup
 *                         )
 *                     [product] => Array
 *                         (
 *                             [0] => Array
 *                                 (
 *                                     [@attributes] => Array
 *                                         (
 *                                             [id] => 1.0
 *                                         )
 *                                     [description] => Array
 *                                         (
 *                                             [#alltext] => This is a description
 *                                         )
 *                                     [title] => Array
 *                                         (
 *                                             [#alltext] => Baked Beans
 *                                         )
 *                                     [room] => Array
 *                                         (
 *                                             [@attributes] => Array
 *                                                 (
 *                                                     [store] => 1.0
 *                                                 )
 *                                             [#alltext] => 103
 *                                         )
 *                                 )
 *                             [1] => Array
 *                                 (
 *                                     [@attributes] => Array
 *                                         (
 *                                             [id] => 2.0
 *                                         )
 *                                     [description] => Array
 *                                         (
 *                                             [#alltext] => This is another description
 *                                         )
 *                                     [title] => Array
 *                                         (
 *                                             [#alltext] => Green Beans
 *                                         )
 *                                     [room] => Array
 *                                         (
 *                                             [@attributes] => Array
 *                                                 (
 *                                                     [store] => 1.0
 *                                                 )
 *                                             [#alltext] => 104
 *                                         )
 *                                 )
 *                         )
 *                 )
 *         )
 * )
 *
 * </code>
 *
 */

if ( ! defined( "INIT_DONE" ) )
{
	print "Improper access! Exiting now...";
	exit();
}

class XML
{
	/**
	 * Document character set
	 *
	 * @var string
	 */
	private $doc_char_set = 'utf-8';

	/**
	 * Current document object
	 *
	 * @var object
	 */
	private $dom;

	/**
	 * Array of DOM objects
	 *
	 * @var array
	 */
	private $dom_objects = array();

	/**
	 * XML array
	 *
	 * @var array
	 */
	private $xml_array = array();

	/**
	 * Conversion class
	 *
	 * @var object
	 */
	private static $class_convert_charset;

	/**
	 * Constructor
	 *
	 * @param     string     Character Set
	 * @return    void
	 */
	public function __construct ( $charSet )
	{
		$this->doc_char_set = strtolower( $charSet );
	}

	/**
	 * Create new document
	 *
	 * @return    void
	 */
	public function new_xml_document ()
	{
		$this->dom = new DOMDocument( '1.0', 'UTF-8' );
	}

	/**
	 * Fetch the document
	 *
	 * @return    XML data
	 */
	public function fetch_document ()
	{
		$this->dom->formatOutput = TRUE;
		return $this->dom->saveXML();
	}

	/**
	 * Add element into the document
	 *
	 * @param     string     Name of tag to create
	 * @param     string     [Name of parent tag (optional)]
	 * @param     array      [Attributes]
	 * @return    void
	 */
	public function add_element ( $tag, $parentTag='', $attributes=array() )
	{
		$this->dom_objects[ $tag ] = $this->_node( $parentTag )->appendChild( new DOMElement( $tag ) );
		$this->add_attributes( $tag, $attributes );
	}

	/**
	 * Add element into the document as a record row
	 * You can pass $tag as either a string or an array
	 *
	 * $xml->add_element_as_record( 'parentTag', 'myTag', $data );
	 * $xml->add_element_as_record( 'parentTag', array( 'myTag', array( 'attr' => 'value' ) ), $data );
	 *
	 * @param     string     Name of parent tag
	 * @param     mixed      Tag wrapper
	 * @param     array      Array of data to add
	 * @return    void
	 */
	public function add_element_as_record ( $parentTag, $tag, $data )
	{
		/* A little set up if you please... */
		$_tag      = $tag;
		$_tag_attr = array();

		if ( is_array( $tag ) )
		{
			$_tag      = $tag[0];
			$_tag_attr = $tag[1];
		}

		$record = $this->_node( $parentTag )->appendChild( new DOMElement( $_tag ) );

		if ( is_array( $_tag_attr ) and count( $_tag_attr ) )
		{
			foreach ( $_tag_attr as $k => $v )
			{
				$record->appendChild( new DOMAttr( $k, $v ) );
			}
		}

		/* Now to add the data */
		if ( is_array( $data ) and count( $data ) )
		{
			foreach ( $data as $rowTag => $rowData )
			{
				/* You can pass an array.. or not if you don't need attributes */
				if ( ! is_array( $rowData ) )
				{
					$rowData = array( 0 => $rowData );
				}

				if ( preg_match( "/['\"\[\]<>&]/", $rowData[0] ) )
				{
					$_child = $record->appendChild( new DOMElement( $rowTag ) );
					$_child->appendChild( new DOMCDATASection( $this->_input_to_xml( $rowData[0] ) ) );
				}
				else
				{
					$_child = $record->appendChild( new DOMElement( $rowTag, $this->_input_to_xml( $rowData[0] ) ) );
				}

				if ( $rowData[1] )
				{
					foreach ( $rowData[1] as $_k => $_v )
					{
						$_child->appendChild( new DOMAttr( $_k, $_v ) );
					}
				}

				unset( $_child );
			}
		}
	}

	/**
	 * Add attributes to a node
	 *
	 * @param     string     Name of tag
	 * @param     array      Array of attributes in key => value format
	 * @return    void
	 */
	public function add_attributes ( $tag, $data )
	{
		if ( is_array( $data ) and count( $data ) )
		{
			foreach ( $data as $k => $v )
			{
				$this->_node( $tag )->appendChild( new DOMAttr( $k, $v ) );
			}
		}
	}

	/**
	 * Load a document from a file
	 *
	 * @param     string     File name
	 * @return    void
	 */
	public function load ( $filename )
	{
		$this->dom = new DOMDocument;
		$this->dom->load( $filename );
	}

	/**
	 * Load a document from a string
	 *
	 * @param     string     XML Data
	 * @return    void
	 */
	public function load_xml ( $xmlData )
	{
		$this->dom = new DOMDocument;
		$this->dom->loadXML( $xmlData );
	}

	/**
	 * Wrapper function: Fetch elements based on tag name
	 *
	 * @param     string     Tag  Name to fetch from the DOM tree
	 * @param     object     Node to start from
	 * @return    array      Node elements
	 */
	public function fetch_elements ( $tag, $node=null )
	{
		$start		= $node ? $node : $this->dom;
		$_elements = $start->getElementsByTagName( $tag );

		return ( $_elements->length ) ? $_elements : array();
	}

	/**
	 * Wrapper function: Fetch all items within a parent tag
	 *
	 * @param     object     DOM object as returned from getElementsByTagName
	 * @param     array      array of node names to skip
	 * @return    array      Array of elements
	 */
	public function fetch_elements_from_record ( $dom, $skip=array() )
	{
		$array = array();

		foreach ( $dom->childNodes as $node )
		{
			if ( $node->nodeType == XML_ELEMENT_NODE )
			{
				if ( is_array( $skip ) )
				{
					if ( in_array( $node->nodeName, $skip ) )
					{
						continue;
					}
				}

				$array[ $node->nodeName ] = $this->_xml_to_output( $node->nodeValue );
			}
		}

		return $array;
	}

	/**
	 * Wrapper function: Fetch items from an element node
	 *
	 * @param     object     DOM object as returned from getElementsByTagName
	 * @param     string     [Optional: Tag name if the DOM is a parent]
	 * @return    string     Returned item
	 */
	public function fetch_item ( $dom, $tag='' )
	{
		if ( $tag )
		{
			$_child = $dom->getElementsByTagName( $tag );
			return $this->_xml_to_output( $_child->item(0)->firstChild->nodeValue );
		}
		else
		{
			return $this->_xml_to_output( $dom->nodeValue );
		}
	}

	/**
	 * Wrapper function: Fetch attributes from an element node's item
	 *
	 * @param     object     DOM object as returned from getElementsByTagName
	 * @param     string     Attribute name required...
	 * @param     string     [Optional: Tag name if the DOM is a parent]
	 * @return    string     Attribute
	 */
	public function fetch_attribute ( $dom, $attribute, $tag='' )
	{
		if ( $tag )
		{
			$_child = $dom->getElementsByTagName( $tag );
			return $_child->item(0)->getAttribute( $attribute );
		}
		else
		{
			return $dom->getAttribute( $attribute );
		}
	}

	/**
	 * Wrapper function: Fetch all attributes from an element node's item
	 *
	 * @param     object     DOM object as returned from getElementsByTagName
	 * @param     string     Tag name to fetch attribute from
	 * @return    array      Array of node items
	 */
	public function fetch_attributes_as_array ( $dom, $tag )
	{
		$attrs      = array();
		$_child     = $dom->getElementsByTagName( $tag );
		$attributes = $_child->item(0)->attributes;

		foreach ( $attributes as $val )
		{
			$attrs[ $val->nodeName ] = $val->nodeValue;
		}

		return $attrs;
	}

	/**
	 * Fetch entire DOM tree into a single array
	 *
	 * @return    array
	 */
	public function fetch_xml_as_array ()
	{
		return $this->_fetch_xml_as_array( $this->dom );
	}

	/**
	 * Internal function to recurse through and collect nodes and data
	 *
	 * @param     DOM object     Node element
	 * @return    array
	 */
	private function _fetch_xml_as_array( $node )
	{
		$xml_array = array();

		if ( $node->nodeType == XML_TEXT_NODE )
		{
			$xml_array = $node->nodeValue;
		}
		else if ( $node->nodeType == XML_CDATA_SECTION_NODE )
		{
			$xml_array = $this->_xml_to_output( $node->nodeValue );
		}
		else
		{
			if ( $node->hasAttributes() )
			{
				$attributes = $node->attributes;

				if ( ! is_null( $attributes ) )
				{
					foreach ( $attributes as $index => $attr )
					{
						$xml_array['@attributes'][ $attr->name ] = $attr->value;
					}
				}
			}

			if ( $node->hasChildNodes() )
			{
				$children  = $node->childNodes;
				$occurance = array();

				foreach ( $children as $nc)
			    {
					if ( $nc->nodeName != '#text' and $nc->nodeName != '#cdata-section' )
					{
			    		$occurance[ $nc->nodeName ]++;
					}
			    }

				for ( $i = 0 ; $i < $children->length ; $i++ )
				{
					$child = $children->item( $i );
					$_name = $child->nodeName;

					if ( $child->nodeName == '#text' or $child->nodeName == '#cdata-section' )
					{
						$_name = '#alltext';
					}

					if ( $occurance[ $child->nodeName ] > 1 )
					{
						$xml_array[ $_name ][] = $this->_fetch_xml_as_array( $child, $ignoreDOMTags );
					}
					else
					{
						$xml_array[ $_name ] = $this->_fetch_xml_as_array( $child, $ignoreDOMTags );
					}
				}
			}
		}

		return $xml_array;
	}

	/**
	 * Encode CDATA XML attribute (Make safe for transport)
	 *
	 * @param     string     Raw data
	 * @return    string     Converted Data
	 */
	private function _xml_convert_safe_cdata ( $v )
	{
		$v = str_replace( "<![CDATA[", "<!#^#|CDATA|", $v );
		$v = str_replace( "]]>"      , "|#^#]>"      , $v );

		return $v;
	}

	/**
	 * Decode CDATA XML attribute (Make safe for transport)
	 *
	 * @param     string     Raw data
	 * @return    string     Converted Data
	 */
	private function _xml_unconvert_safe_cdata ( $v )
	{
		$v = str_replace( "<!#^#|CDATA|", "<![CDATA[", $v );
		$v = str_replace( "|#^#]>"      , "]]>"      , $v );

		return $v;
	}

	/**
	 * Return a tag object
	 *
	 * @param     string     Name of tag
	 * @return    object
	 */
	private function _node ( $tag )
	{
		if ( isset( $this->dom_objects[ $tag ] ) )
		{
			return $this->dom_objects[ $tag ];
		}
		else
		{
			return $this->dom;
		}
	}

	/**
	 * Convert from native to UTF-8 for saving XML
	 *
	 * @param     string     Input Text
	 * @return    string     Converted Text ready for XML saving
	 */
	private function _input_to_xml ( $text )
	{
		/* Do we need to make safe on CDATA? */
		if ( preg_match( "/['\"\[\]<>&]/", $text ) )
		{
			$text = $this->_xml_convert_safe_cdata( $text );
		}

		/* Using UTF-8 */
		if ( $this->doc_char_set == 'utf-8' )
		{
			return $text;
		}
		/* Are we using the most common ISO-8559-1... */
		else if ( $this->doc_char_set == 'iso-8859-1' )
		{
			return utf8_encode( $text );
		}
		else
		{
			return $this->_convert_charsets( $text, $this->doc_char_set, 'utf-8' );
		}
	}

	/**
	 * Convert from UTF-8 to native for saving XML
	 *
	 * @param     string     Input Text
	 * @return    string     Converted Text ready for returning to app
	 */
	private function _xml_to_output( $text )
	{
		/* Unconvert cdata */
		$text = $this->_xml_unconvert_safe_cdata( $text );

		/* Using UTF-8 */
		if ( $this->doc_char_set == 'utf-8' )
		{
			return $text;
		}
		/* Are we using the most common ISO-8559-1... */
		elseif ( $this->doc_char_set == 'iso-8859-1' )
		{
			return utf8_decode( $text );
		}
		else
		{
			return $this->_convert_charsets( $text, 'utf-8', $this->doc_char_set );
		}
	}

	/**
	 * Convert a string between charsets. XML will always be UTF-8
	 *
	 * @param     string     Input String
	 * @param     string     Current char set
	 * @param     string     Destination char set
	 * @return    string     Parsed string
	 * @todo      [Future] If an error is set in class_convert_charset, show it or log it somehow
	 */
	private function _convert_charsets ( $text, $original_cset, $destination_cset="UTF-8" )
	{
		$original_cset    = strtolower( $original_cset );
		$destination_cset = strtolower( $destination_cset );
		$t                = $text;

		//-----------------------------------------
		// Not the same?
		//-----------------------------------------

		if ( $destination_cset == $original_cset )
		{
			return $t;
		}

		if ( ! is_object( self::$class_convert_charset ) )
		{
			require_once( PATH_LIBS . '/IPS_Sources/classConvertCharset.php' );
			self::$class_convert_charset = new classConvertCharset();

			/*
			if ( function_exists( 'mb_convert_encoding' ) )
			{
				self::$class_convert_charset->method = 'mb';
			}
			elseif ( function_exists( 'iconv' ) )
			{
				self::$class_convert_charset->method = 'iconv';
			}
			elseif ( function_exists( 'recode_string' ) )
			{
				self::$class_convert_charset->method = 'recode';
			}
			else
			{
			*/
				self::$class_convert_charset->method = 'internal';
			// }
		}

		$text = self::$class_convert_charset->convertEncoding( $text, $original_cset, $destination_cset );

		return $text ? $text : $t;
	}
}