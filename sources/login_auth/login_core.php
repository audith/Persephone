<?php

if ( ! defined( "INIT_DONE" ) )
{
	print "Improper access! Exiting now...";
	exit();
}

/**
 * Core Login class
 *
 * @package  Audith CMS
 * @author   Shahriyar Imanov <shehi@imanov.name>
 * @version  1.0
**/
abstract class Login_Core
{
	/**
	 * API Object Reference
	 * @var object
	**/
	protected $API;

	/**
	 * Unlock account time left
	 * @var integer
	**/
	public $account_unlock = 0;

	/**
	 * Authentication errors
	 * @var array
	**/
	public $auth_errors = array();

	/**
	 * Flag : Admin authentication
	 * @var boolean
	**/
	public $is_admin_auth = FALSE;

	/**
	 * Member information
	 * @var array
	**/
	public $member = array();

	/**
	 * Return code
	 * @var string
	**/
	public $return_code = "";

	/**
	 * Force email check
	 * @var boolean
	 */
	private $_force_email_check = FALSE;


	/**
	 * Constructor
	 *
	 * @param   object   API object reference
	 * @return  void
	 */
	public function __construct ()
	{
		# Member details
		$this->member =& $this->API->member;
	}


	/**
	 * Normal authentication routine for the login method
	 *
	 * @param   string    Username  [Username or Email Address must be supplied]
	 * @param   string    Email Address  [Username or Email Address must be supplied]
	 * @param   string    Password
	 * @return  boolean
	 */
	abstract protected function authenticate ( $username, $email_address, $password );


	/**
	 * Local authentication
	 *
	 * @param    string	    Username
	 * @param    string	    Email Address
	 * @param    string	    Password
	 * @return   boolean    Authentication successful
	 */
	public function auth_local ( $username, $email_address, $password )
	{
		$password = md5( $password );

		//------------------
		// Type of login
		//------------------

		$type = "username";

		if ( is_array( $this->method_config ) and $this->method_config['login_folder_name'] == 'internal' )
		{
			$type = $this->method_config['login_user_id'];
		}

		if ( $this->_force_email_check === TRUE or ( $email_address and ! $username ) )
		{
			$type = "email";
		}

		switch ( $type )
		{
			case 'username':
				if ( $this->API->Input->mb_strlen( $username ) > 32 )
				{
					$this->return_code = 'NO_USER';
					return FALSE;
				}
				$this->member = $this->API->Session->load_member( $username, 'groups', 'username' );
				break;

			case 'email':
				$this->member = $this->API->Session->load_member( $email_address, 'groups', 'email' );
				break;
		}

		//------------------
		// Got an account
		//------------------

		if ( ! $this->member['id'] )
		{
			$this->return_code = 'NO_USER';
			return FALSE;
		}

		//-----------------------------
		// Verify it is not blocked
		//-----------------------------

		if ( ! $this->_check_failed_logins() )
		{
			return FALSE;
		}

		//---------------------
		// Check password...
		//---------------------

		if ( $this->API->Session->authenticate_member( $this->member['id'], $password ) != TRUE )
		{
			if ( ! $this->_append_failed_login() )
			{
				return FALSE;
			}

			$this->return_code = 'WRONG_AUTH';
			return FALSE;
		}
		else
		{
			$this->return_code = 'SUCCESS';
			return FALSE;
		}
	}


	/**
	 * Admin authentication
	 *
	 * @param    string    Username
	 * @param    string    Email Address
	 * @param    string    Password
	 * @return   boolean   Authentication successful
	 */
	public function admin_auth_local ( $username, $email_address, $password )
	{
		return $this->auth_local( $username, $email_address, $password );
	}


	/**
	 * Append a failed login
	 *
	 * @return   boolean   Account ok or not
	 */
	private function _append_failed_login ()
	{
		if ( $this->API->config['security']['bruteforce_attempts'] > 0 )
		{
			$failed_logins 	 = explode( "," , $this->API->Input->clean__excessive_separators( $this->member['failed_logins'], "," ) );
			$failed_logins[] = UNIX_TIME_NOW . '-' . $this->API->Session->ip_address;

			$failed_count    = 0;
			$total_failed    = 0;
			$non_expired_att = array();

			foreach ( $failed_logins as $entry )
			{
				list( $timestamp, $ipaddress ) = explode( "-", $entry );

				if ( ! $timestamp )
				{
					continue;
				}

				$total_failed++;

				if ( $ipaddress != $this->API->Session->ip_address )
				{
					continue;
				}

				if (
					$this->API->config['security']['bruteforce_period'] > 0
					and
					$timestamp < time() - ( $this->API->config['security']['bruteforce_period'] * 60 )
				)
				{
					continue;
				}

				$failed_count++;
				$non_expired_att[] = $entry;
			}

			if( $this->member['id'] )
			{
				$this->API->Session->save_member( $this->member['email'], array(
						'members' => array(
								'failed_logins'      => implode( ",", $non_expired_att ),
								'failed_login_count' => $total_failed,
							),
						)
					);
			}

			if ( $failed_count >= $this->API->config['security']['bruteforce_attempts'] )
			{
				if ( $this->API->config['security']['bruteforce_unlock'] )
				{
					sort($non_expired_att);
					$oldest_entry  = array_shift( $non_expired_att );
					list( $oldest, ) = explode( "-", $oldest_entry );
					$this->account_unlock = $oldest;
				}

				$this->return_code = 'ACCOUNT_LOCKED';
				return FALSE;
			}
		}

		return TRUE;
	}


	/**
	 * Check failed logins
	 *
	 * @return    boolean    Account ok or not
	 */
	private function _check_failed_logins ()
	{
		if ( $this->API->config['security']['bruteforce_attempts'] > 0 )
		{
			$failed_attempts = explode( "," , $this->API->Input->clean__excessive_separators( $this->member['failed_logins'] , "," ) );
			$failed_count    = 0;
			$total_failed    = 0;
			$_this_ip_failed   = 0;
			$non_expired_att = array();

			if ( is_array( $failed_attempts ) and count( $failed_attempts ) )
			{
				foreach ( $failed_attempts as $entry )
				{
					if ( ! strpos( $entry, "-" ) )
					{
						continue;
					}

					list ( $timestamp, $ip_address ) = explode( "-", $entry );

					if ( ! $timestamp )
					{
						continue;
					}

					$total_failed++;

					if ( $ip_address != $this->API->Session->ip_address )
					{
						continue;
					}

					$_this_ip_failed++;

					if (
						$this->API->config['security']['bruteforce_period']
						and
						$timestamp < UNIX_TIME_NOW - ( $this->API->config['security']['bruteforce_period'] * 60 )
					)
					{
						continue;
					}

					$non_expired_att[] = $entry;
					$failed_count++;
				}

				sort( $non_expired_att );
				$oldest_entry  = array_shift( $non_expired_att );
				list( $oldest, ) = explode( "-", $oldest_entry );
			}

			if ( $_this_ip_failed >= $this->API->config['security']['bruteforce_attempts'] )
			{
				if ( $this->API->config['security']['bruteforce_unlock'] )
				{
					if ( $failed_count >= $this->API->config['security']['bruteforce_attempts'] )
					{
						$this->account_unlock  = $oldest;
						$this->return_code     = 'ACCOUNT_LOCKED';

						return FALSE;
					}
				}
				else
				{
					$this->return_code = 'ACCOUNT_LOCKED';

					return FALSE;
				}
			}
		}

		return TRUE;
	}


	/**
	 * Create a local member account [public interface]
	 *
	 * @param    array     Member Information [members, members_pfields]
	 * @return   array     New member information
	 * @deprecated         Just redirects to Session::create_member()
	 */
	public function create_local_member ( $member )
	{
		$member['members']['created_remote'] = TRUE;
		$member['members']['display_name']   = ( $member['members']['display_name'] ) ? $member['members']['display_name'] : $member['members']['name'];

		return $this->API->Session->create_member( $member );
	}


	/**
	 * Force email check flag, currently used for Facebook
	 *
	 * @param 	boolean
	 * @return  null
	 */
	public function set_force_email_check ( $boolean )
	{
		$this->_force_email_check = ( $boolean ) ? TRUE : FALSE;
	}
}

?>