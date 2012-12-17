<?php

if ( ! defined( "INIT_DONE" ) )
{
	print "Improper access! Exiting now...";
	exit();
}

/**
 * Converge Class
 *
 * Methods and functions for handling converge authentication,
 * password generation and update methods
 *
 * @package  Invision Power Board (Adapted to Audith CMS)
 * @author   Matthew Mecham (Adapted by Shahriyar Imanov)
 * @version  2.1
**/
class Ips_Converge
{
	/**
	 * Registry reference
	 *
	 * @var Registry
	**/
	private $Registry;

	/**
	* Converge member array
	*
	* @var array
	*/
	public $member = array();


	/**
	 * Constructor, accepts Registry object
	 *
	 * @param	object Database object
	**/
	public function __construct ( Registry $Registry )
	{
		# Bring-in Registry object reference
		$this->Registry = $Registry;
	}


	/**
	 * Checks for a DB row that matches $email
	 *
	 * @param	string    Email address
	 * @return	boolean
	**/
	public function converge_check_for_member_by_email ( $email )
	{
		$this->Registry->Db->cur_query = array(
				"do"      => "select",
				"fields"  => array( "converge_id" ),
				"table"   => "members_converge",
				"where"   => array( array( "converge_email=?", $email ) )
			);

		if ( count( $result = $this->Registry->Db->simple_exec_query() ) )
		{
			foreach ( $result as $test )
			{
				if ( $test['converge_id'] )
				{
					return TRUE;
				}
				else
				{
					return FALSE;
				}
			}
		}
		else
		{
			return FALSE;
		}
	}


	/**
	 * Updates member's converge DB row password
	 *
	 * @param	string	MD5 hash of new password
	 * @param	string	Email address
	**/
	public function converge_update_password ( $new_md5_pass, $email )
	{
		if ( ! $email or ! $new_md5_pass )
		{
			return FALSE;
		}

		$temp_member = $this->member;

		if ( $email != $this->member['converge_email'] )
		{
			$this->Registry->Db->cur_query = array(
					"do"     => "select",
					"table"  => "members_converge",
					"where"  => array( array( "converge_email=?", $email ) )
				);

			if ( count( $result = $this->Registry->Db->simple_exec_query() ) )
			{
				foreach ( $result as $row )
				{
					$temp_member = $row;
				}
			}
		}

		$new_pass = md5( md5( $temp_member['converge_pass_salt'] ) . $new_md5_pass );

		$this->Registry->Db->cur_query = array(
				"do"       => "update",
				"tables"   => array( "members_converge" ),
				"set"      => array( "converge_pass_hash" => $new_pass ),
				"where"    => "converge_id='" . $temp_member['converge_id'] . "'"
			);

		$this->Registry->Db->simple_exec_query();
	}


	/**
	 * Updates member's converge DB row email address
	 *
	 * @param	string	Current email address
	 * @param	string	New email address
	 * @return	boolean
	**/
	public function converge_update_member ( $curr_email, $new_email )
	{
		if ( ! $curr_email or ! $new_email )
		{
			return FALSE;
		}

		if ( ! $this->member['converge_id'] )
		{
			$this->converge_load_member( $curr_email );

			if ( ! $this->member['converge_id'] )
			{
				return FALSE;
			}
		}

		$this->Registry->Db->cur_query = array(
				"do"       => "update",
				"tables"   => array( "members_converge" ),
				"set"      => array( "converge_email" => $new_email ),
				"where"    => "converge_id='" . $this->member['converge_id'] . "'"
			);

		$result = $this->Registry->Db->simple_exec_query();

		return TRUE;
	}


	/**
	 * Load converge DB row by email address
	 *
	 * @param	string	Current email address
	**/
	public function converge_load_member ( $email )
	{
		$this->member = array();

		if ( $email )
		{
			$this->Registry->Db->cur_query = array(
					"do"     => "select",
					"table"  => "members_converge",
					"where"  => array( array( "converge_email=?", $email ) )
				);

			if ( count( $result = $this->Registry->Db->simple_exec_query() ) )
			{
				foreach ( $result as $row )
				{
					$this->member = $row;
				}
			}
		}
	}


	/**
	 * Load converge DB row by converge (member) ID
	 *
	 * @param  integer  Member ID
	**/
	public function converge_load_member_by_id ( $id )
	{
		$id = intval($id);

		$this->member = array();

		if ( $id )
		{
			$this->Registry->Db->cur_query = array(
					"do"     => "select",
					"table"  => "members_converge",
					"where"  => array( array( "converge_id=?", $this->Registry->Db->quote( $id, "INTEGER" ) ) )
				);

			if ( count( $result = $this->Registry->Db->simple_exec_query() ) )
			{
				foreach ( $result as $row )
				{
					$this->member = $row;
				}
			}
		}
	}


	/**
	 * Check supplied password with converge DB row
	 *
	 * @param	string	MD5 of entered password
	 * @return	boolean
	**/
	public function converge_authenticate_member ( $md5_once_password )
	{
		if ( ! $this->member['converge_pass_hash'] )
		{
			return FALSE;
		}

		if ( $this->member['converge_pass_hash'] == $this->generate_compiled_passhash( $this->member['converge_pass_salt'], $md5_once_password ) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}


	/**
	 * Generates a compiled passhash
	 *
	 * Returns a new MD5 hash of the supplied salt and MD5 hash of the password
	 *
	 * @param	string	User's salt (5 random chars)
	 * @param	string	User's MD5 hash of their password
	 * @return	string	MD5 hash of compiled salted password
	**/
	public function generate_compiled_passhash ( $salt, $md5_once_password )
	{
		return md5( md5( $salt ) . $md5_once_password );
	}


	/**
	 * Generates a password salt
	 *
	 * Returns n length string of any char except backslash
	 *
	 * @param	integer	Length of desired salt, 5 by default
	 * @return	string	n character random string
	**/
	public function generate_password_salt ( $len = 5 )
	{
		$salt = "";

		for ( $i = 0; $i < $len; $i++ )
		{
			$num = rand( 33, 126 );

			if ( $num == '92' )
			{
				$num = 93;
			}

			$salt .= chr( $num );
		}

		return $salt;
	}


	/**
	 * Generates a log in key
	 *
	 * @param	integer	Length of desired random chars to MD5
	 * @return	string	MD5 hash of random characters
	**/
	public function generate_auto_log_in_key ( $len = 60 )
	{
		$pass = $this->generate_password_salt( $len );

		return md5($pass);
	}
}

?>