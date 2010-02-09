<?php
# Lifter002: DONE
# Lifter007: TODO
# Lifter003: TODO

/**
 * StudIPUser.class.php
 *
 * PHP version 5
 *
 * @author  	Dennis Reil <dennis.reil@offis.de>
 * @author  	Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @package 	pluginengine
 * @subpackage 	core
 * @copyright 	2009 Stud.IP
 * @license 	http://www.gnu.org/licenses/gpl.html GPL Licence 3
 */

/**
 * User-Object which should be used in plugins
 *
 */
class StudIPUser
{
	//TODO: (mriehe) should be private, but some plugins will not work... (bad direct usage)
	public $userid;
	public $username;
	public $permission;
	public $surname;
	public $givenname;
	public $assignedroles;

	/**
	 * Initialize a user object for the current user or the given user id.
	 */
	public function __construct($id = NULL)
	{
		$this->setUserid($id ? $id : $GLOBALS['auth']->auth['uid']);
	}

	/**
	 * TODO: (mlunzena) what a bad design, the whole idea of filling a user by setting an ID smells
	 *
	 * @deprecated
	 *
	 * @param int $id
	 */
	public function setUserid($id)
	{
		$this->userid = $id;
		$this->permission = new Permission ( $id );
		$stmt = DBManager::get ()->prepare ( "SELECT Vorname, Nachname, username " . "FROM auth_user_md5 " . "WHERE user_id=?" );
		$stmt->execute ( array ($id ) );
		if (($row = $stmt->fetch ()) !== FALSE)
		{
			$this->givenname = $row ['Vorname'];
			$this->surname = $row ['Nachname'];
			$this->username = $row ['username'];
		}
	}

	/**
	 * Enter description here...
	 *
	 * @return string
	 */
	public function getSurname()
	{
		return $this->surname;
	}

	/**
	 * Enter description here...
	 *
	 * @return string
	 */
	public function getGivenname()
	{
		return $this->givenname;
	}

	/**
	 * Enter description here...
	 *
	 * @return string (md5)
	 */
	public function getUserid()
	{
		return $this->userid;
	}

	/**
	 * Enter description here...
	 *
	 * @return permission-object
	 */
	public function getPermission()
	{
		return $this->permission;
	}

	/**
	 * Enter description here...
	 *
	 * @return string
	 */
	public function getUsername()
	{
		return $this->username;
	}

	/**
	 * checks, if this user is identical to the otheruser
	 *
	 * @param StudIPUser $otheruser
	 * @return boolean same user or not
	 */
	public function isSameUser(StudIPUser $otheruser)
	{
		return $otheruser->getUserid () === $this->getUserid ();
	}

	/**
	 * Enter description here...
	 *
	 * @param boolean $withimplicit
	 * @return array
	 */
	public function getAssignedRoles($withimplicit = false)
	{
		return RolePersistence::getAssignedRoles ( $this->userid, $withimplicit );
	}

	/**
	 * Enter description here...
	 *
	 * @param string $assignedrole
	 * @return boolean
	 */
	public function isAssignedRole($assignedrole = '')
	{
		return RolePersistence::isAssignedRole ( $this->userid, $assignedrole );
	}
}
