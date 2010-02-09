<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// ShmHandler.class.php
// simple wrapper class for php shared memory functions 
// Copyright (c) 2002 André Noack <andre.noack@gmx.net>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+


/**
* Simple wrapper class for PHP shared memory funktions
*
* 
*
* @access	public	
* @author	André Noack <andre.noack@gmx.net>
* @package	Chat
*/

class ShmHandler
{
	/**
	* Shared Memory Key
	*
	* Key muss eindeutig sein (auf dieser Maschine!)
	* @access	private
	* @var		integer	$shmKey		Key muss eindeutig sein (auf dieser Maschine!)
	*/
	var $shmKey;         //Shared Memory Key
	/**
	* Shared Memory Size
	*
	* in Bytes
	* @access	private
	* @var		integer	$shmSize	in Bytes
	*/
	var $shmSize;     //Shared Memory Size in Bytes
	/**
	* shared memory handle
	*
	* @access	private
	* @var		object (resource)	$shmid
	*/
	var $shmid;
	/**
	* semaphore handle
	*
	* @access	private
	* @var	object (resource)	$semid
	*/
	var $semid;                  //semaphore handle
	/**
	* turn debug mode on/off
	*
	* @access	public
	* @var	boolean
	*/
	var $debug = true;
	
	/**
	* constructor
	*
	* does nothing special, just acquires handles for a memory segment and a semaphore to it
	*
	* @access	public
	* @param	integer	$key	must be unique on this machine
	* @param	integer	$size	in bytes
	*/
	function ShmHandler($key=98374,$size=131072) {
		$this->shmKey=$key;
		$this->shmSize=$size;
		if (!$this->shmid = shm_attach($this->shmKey, $this->shmSize, 0600))
		$this->halt("shm_attach fehlgeschlagen!");
		if (!$this->semid = sem_get($this->shmKey ,1))
		$this->halt("sem_get fehlgeschlagen!");
	}

	/**
	* acquire semaphore lock
	*
	* @access	private
	*/
	function getLock() {
		if (!sem_acquire($this->semid))
		$this->halt("sem_acquire fehlgeschlagen!");
	}
	
	/**
	* release semaphore lock
	*
	* @access	private
	*/
	function releaseLock() {
		if (!sem_release($this->semid))
		$this->halt("sem_release fehlgeschlagen!");
	}

	/**
	* stores a variable in shared memory
	*
	* @access	public	
	* @param	mixed	&$what	variable to store (call by reference)
	* @param	integer	$key	the key under which to store
	*/
	function store(&$what,$key) {
		$this->getLock();
		if (!@shm_put_var($this->shmid, $key, $what))
		$this->halt("Fehler beim Schreiben von $key");
		$this->releaseLock();
		return true;
	}

	/**
	* restores a variable from shared memory
	*
	* @access	public	
	* @param	mixed	&$what	variable to restore (call by reference)
	* @param	integer	$key	the key from which to store
	*/
	function restore(&$what,$key) {
		$this->getLock();
		$what = @shm_get_var($this->shmid, $key);
		$this->releaseLock();
	return true;
	}

	/**
	* release shared memory segment
	*
	* @access	public
	*/
	
	function dispose(){
		if (!shm_remove($this->shmKey))
		$this->halt("shm_remove fehlgeschlagen!");
	}

	/**
	* print error message and exit script
	*
	* @access	private
	* @param	string	$msg	the message to print
	*/
	function halt($msg){
		echo $msg."<br>";
		die;
	}
}
?>
