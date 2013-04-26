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
	 *
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
	 * @param    string                  $message
	 * @param    int                     $code
	 * @param    \Exception              $previous
	 *
	 * @return \Persephone\Exception
	 */
	public function __construct ( $message = null, $code = 0, \Exception $previous = null )
	{
		parent::__construct( $message, (int) $code, $previous );
		$this->_logException();
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
	 * @return void
	 */
	private function _logException ()
	{
		$message_to_log =
			"EXCEPTION: " . __CLASS__ .
			"\n\tMESSAGE: " . self::getMessage() .
			"\n\tFILE: " . self::getFile() .
			"\n\tLINE: " . self::getLine() .
			"\n\tCLASS: " . __CLASS__ .
			"\n\tTRACE:\n" . self::getTraceAsString();

		\Persephone\Registry::logger__do_log( $message_to_log, "ERROR" );
	}
}
