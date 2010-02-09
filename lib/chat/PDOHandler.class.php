<?php
# Lifter007: TODO
# Lifter003: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// PDOHandler.class.php
// simple wrapper class for persistent storage of php variables in Mysl db
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
* Simple wrapper class for mysql based sotrage
*
*
*
* @access	public
* @author	André Noack <andre.noack@gmx.net>
* @package	Chat
*/

class PDOHandler {
	/**
	* name of db table
	*
	* @access	private
	* @var		string
	*/
	var $table_name;

	/**
	* constructor
	*
	* @access	public
	* @param	string	$db_name
	* @param	string	$table_name
	*/
	function PDOHandler($table_name = "chat_data") {
		$this->table_name = $table_name;
	}

	/**
	* stores a variable in shared memory
	*
	* @access	public
	* @param	mixed	&$what	variable to store (call by reference)
	* @param	integer	$key	the key under which to store
	*/
	function store(&$what,$key) {
		$db = DBManager::get();
		$contents = addslashes(serialize($what));
		$db->exec("REPLACE INTO {$this->table_name} (id, data) VALUES ($key, '$contents')");
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
		$db = DBManager::get();
		$result = $db->query("SELECT data FROM {$this->table_name} WHERE id=$key");

		if (($row = $result->fetch())) {
			$what = unserialize($row['data']);
		}
		return true;
	}
}
?>
