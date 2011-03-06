<?php

if ( ! defined( "INIT_DONE" ) )
{
	print "Improper access! Exiting now...";
	exit();
}

/**
 * Login_Method : Internal
 *
 * @package  Audith CMS
 * @author   Shahriyar Imanov <shehi@imanov.name>
 * @version  1.0
**/
class Login_Method extends Login_Core
{
	/**
	 * Login method configuration
	 * @var array
	 */
	protected $method_config = array();


	/**
	 * Constructor
	 *
	 * @param   object   REFERENCE: API object
	 * @param   array    Configuration info for this method
	 * @param   array    Custom configuration info for this method
	 * @return  void
	**/
	public function __construct ( & $API, $method, $conf = array() )
	{
		# Prelim
		$this->method_config = $method;
		$this->API = $API;
		parent::__construct();
	}


	/**
	 * Authenticate the request
	 *
	 * @param   string     Username
	 * @param   string     Email Address
	 * @param   string     Password
	 * @return  boolean    Authentication successful
	 */
	public function authenticate ( $username, $email_address, $password )
	{
		if ( ( ! $username and ! $email_address ) or ! $password )
		{
			$this->return_code	= 'MISSING_DATA';
			return FALSE;
		}

		return $this->auth_local( $username, $email_address, $password );
	}

	/**
	 * Check if an email already exists
	 *
	 * @param    string     Email Address
	 * @return   boolean    Request was successful
	 */
	public function email_exists_check ( $email )
	{
		$this->API->Db->cur_query = array(
				'do'     => "select_row",
				'fields' => array( "id" ),
				'table'  => "members",
				'where'  => "email=" . $this->API->Db->db->quote( $email ),
			);

		$email_check = $this->API->Db->simple_exec_query();

		$this->return_code = $email_check['id'] ? 'EMAIL_IN_USE' : 'EMAIL_NOT_IN_USE';
		return TRUE;
	}
}

?>