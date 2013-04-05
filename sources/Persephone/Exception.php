<?php

namespace Persephone;

if ( !defined( "INIT_DONE" ) )
{
	die( "Improper access! Exiting now..." );
}

class Exception extends \Exception
{
	/**
	 * Registry reference
	 * @var      Registry
	 */
	private $Registry;

	/**
	 * @var      null|Exception
	 */
	private $_previous = null;


	/**
	 * Construct the exception
	 *
	 * @param    string                  $msg
	 * @param    int                     $code
	 * @param    \Exception              $previous
	 * @return   void
	 */
    public function __construct ( $message = null , $code = 0 , \Exception $previous = null )
	{
		# Singleton will return the same object
		$this->Registry = \Persephone\Registry::init();

		parent::__construct( $message, (int) $code, $previous );

		$this->_logException();
	}


	/**
	 * Overloading
	 *
	 * For PHP < 5.3.0, provides access to the getPrevious() method.
	 *
	 * @param    string   $method
	 * @param    array    $args
	 * @return   mixed
	 */
	public function __call ( $method , array $arguments )
	{
		if ( 'getprevious' == strtolower( $method ) )
		{
			return $this->_getPrevious();
		}
		elseif ( 'logexception' == strtolower ( $method ) )
		{
			$this->_logException();
		}

		return null;
	}


	/**
	 * String representation of the exception
	 *
	 * @return string
	 */
	public function __toString ()
	{
		return parent::__toString();
	}

	/**
	 * Returns previous Exception
	 *
	 * @return Exception|null
	 */
	protected function _getPrevious ()
	{
		return $this->_previous;
	}

	/**
	 * Logs exception message
	 *
	 * return void
	 */
	private function _logException ()
	{
		$message_to_log = "EXCEPTION: " . __CLASS__
		. "\nMESSAGE: " . self::getMessage()
		. "\nFILE: " . self::getFile()
		. "\nLINE: " . self::getLine()
		. "\nCLASS: " . __CLASS__
		. "\nTRACE:\n" . self::getTraceAsString()
		. "\nLAST SQL QUERY:\n" . ( !empty( $this->Registry->Db->cur_query ) ? strval( $this->Registry->Db ) : "--none--" )
		. "\n\n";

		\Persephone\Registry::logger__do_log( $message_to_log, "ERROR" );
	}
}
