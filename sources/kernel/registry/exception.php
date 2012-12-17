<?php

if ( !defined( "INIT_DONE" ) )
{
	print "Improper access! Exiting now...";
	exit();
}

class Registry__Exception extends Exception
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
	 * @param    string      $msg
	 * @param    int         $code
	 * @param    Exception   $previous
	 * @param    Registry    Registry object reference
	 * @return   void
	 */
    public function __construct ( $message = null , $code = 0 , Exception $previous = null , Registry $Registry )
	{
		$this->Registry = $Registry;

		if ( version_compare( PHP_VERSION, "5.3.0", "<" ) )
		{
			parent::__construct( $message, (int) $code );
			$this->_previous = $previous;
		}
		else
		{
			parent::__construct( $message, (int) $code, $previous );
		}
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
			return $this->_logException();
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
		if ( version_compare( PHP_VERSION, '5.3.0', '<' ) )
		{
			if ( null !== ( $e = $this->getPrevious() ) )
			{
				return $e->__toString() . "\n\nNext " . parent::__toString();
			}
		}

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
	 */
	private function _logException ()
	{
		$message_to_log = "EXCEPTION: " . __CLASS__
		. "\nMESSAGE: " . self::getMessage()
		. "\nFILE: " . self::getFile()
		. "\nLINE: " . self::getLine()
		. "\nCLASS: " . __CLASS__
		. "\nTRACE:\n" . self::getTraceAsString()
		. ( ( strpos( __CLASS__, "Zend_Db_" ) === 0 ) ? "\nSQL-QUERY:\n" . var_export( $this->Registry->Db->cur_query, true ) : "" )
		. "\n\n";

		return $this->Registry->logger__do_log( $message_to_log, "ERROR" );
	}
}