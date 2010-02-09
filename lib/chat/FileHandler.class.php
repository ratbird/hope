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
* Simple wrapper class for file based sotrage
*
* 
*
* @access	public	
* @author	André Noack <andre.noack@gmx.net>
* @package	Chat
*/

class FileHandler {
	/**
	* Shared Memory Key
	*
	* Key muss eindeutig sein (auf dieser Maschine!)
	* @access	private
	* @var		integer	$shmKey		Key muss eindeutig sein (auf dieser Maschine!)
	*/
	var $file_path;         //Shared Memory Key
	
	/**
	* Shared Memory Key
	*
	* Key muss eindeutig sein (auf dieser Maschine!)
	* @access	private
	* @var		integer	$shmKey		Key muss eindeutig sein (auf dieser Maschine!)
	*/
	var $file_name;         //Shared Memory Key
	
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
	function FileHandler($file_name = "chat_data" ,$file_path = "@") {
   		global $TMP_PATH;
 	      	if($file_path == "@:"){
 	        	$file_path = $TMP_PATH;
 	        }  
		$this->file_name = $file_name;
		$this->file_path = $file_path;
	}

	
	/**
	* stores a variable in shared memory
	*
	* @access	public	
	* @param	mixed	&$what	variable to store (call by reference)
	* @param	integer	$key	the key under which to store
	*/
	function store(&$what,$key) {
		$file_name = $this->file_path . "/" . $this->file_name . $key;
		$contents = serialize($what);
		$handle = fopen ($file_name, "rb+");
		if ($handle === false) {
			$handle = fopen($file_name, 'xb');
		}
		if (flock($handle, LOCK_EX)){
			ftruncate($handle, 0);
			fwrite ($handle, $contents);
			flock($handle, LOCK_UN);
			fclose ($handle);
		} else {
			fclose ($handle);
			$this->halt("Fehler beim Schreiben von $key");
		}
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
		$file_name = $this->file_path . "/" . $this->file_name . $key;
		$handle = fopen ($file_name, "rb");
		if ($handle){
			if (flock($handle, LOCK_SH)){
				while (!feof($handle)) {
					$contents .= fread($handle, 8192);
				}
				flock($handle, LOCK_UN);
				fclose ($handle);
			} else {
				fclose ($handle);
				$this->halt("Fehler beim Lesen von $key");
			}
			if ($contents){
				$what = unserialize($contents);
			}
		}
		return true;
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
