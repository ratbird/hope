<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO

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


if (version_compare(PHP_VERSION, '5.2', '<')) {
	require_once('vendor/phpxmlrpc/xmlrpc.inc');
	require_once('vendor/phpxmlrpc/jsonrpc.inc');
	require_once('vendor/phpxmlrpc/json_extension_api.inc');
}

/**
* LockRules.class.php
*
*
*
* @author     Mark Sievers <msievers@uos.de>
* @access     public
* @modulegroup
* @module
* @package
*/

class LockRules {
	
	
	public static function Check($seminar_id, $attribute){
		static $lockdata = null;
		if(!$GLOBALS['SEMINAR_LOCK_ENABLE']) return false;
		if(!isset($lockdata[$seminar_id])){
				$l = new LockRules();
				$lockdata[$seminar_id] = $l->getSemLockRule($seminar_id);
		}
		return isset($lockdata[$seminar_id]['attributes'][$attribute]) && LockRules::CheckLockRulePermission($seminar_id, $lockdata[$seminar_id]['permission']);
	}
	
	public static function CheckLockRulePermission($seminar_id, $permission){
		if($permission == 'admin') $check_perm = 'root';
		elseif($permission == 'dozent') $check_perm = 'admin';
		else $check_perm = 'dozent';
		return ($permission == 'root' || !$GLOBALS['perm']->have_studip_perm($check_perm, $seminar_id));
	}
	
	function getAllLockRules($return_rules_for_root = false) {
		$i = 0;
		$lockdata = array();
		if(!$return_rules_for_root) $where = " WHERE permission IN('tutor','dozent') ";
		foreach (DBManager::get()->query("SELECT * FROM lock_rules " . $where . " ORDER BY name") as $row) {
			$lockdata[$i++] = $this->wrapLockRules($row);
		}
		
		if (!sizeof($lockdata)) {
			return 0;
		}
		return $lockdata;
	}
	
	function getSemLockRule($sem_id) {
		$stmt = DBManager::get()->prepare(
			"SELECT lock_rule FROM seminare WHERE Seminar_id = ?");
		$result = $stmt->execute(array($sem_id));
		if (!$result) {
			echo "Error! query not succeeded";
			return 0;
		}
		$row = $stmt->fetch();
		if ($row === FALSE) {
			return 0;
		}
		
		return $this->getLockRule($row["lock_rule"]);
	}
	
	function getLockRule($lock_id) {
		
		$stmt = DBManager::get()->prepare(
			"SELECT * FROM lock_rules WHERE lock_id = ?");
		$result = $stmt->execute(array($lock_id));
		if (!$result) {
			echo "Error! query not succeeded";
			return 0;
		}
		$row = $stmt->fetch();
		if ($row === FALSE) {
			return 0;
		}
		
		return $this->wrapLockRules($row);
	}
	
	function wrapLockRules($row) {
		$lockdata = array();
		$lockdata["lock_id"]     = $row["lock_id"];
		$lockdata["name"]        = $row["name"];
		$lockdata["description"] = $row["description"];
		$lockdata["permission"] = $row["permission"];
		$lockdata['attributes']  = json_decode($row["attributes"], true);
		return $lockdata;
	}
	
	function insertNewLockRule($lockdata) {
		$lock_id = md5(uniqid("Legolas",1));
		
		$json_attributes = json_encode($lockdata['attributes']);
		
		$stmt = DBManager::get()->prepare(
			"INSERT INTO lock_rules (lock_id, permission, name, description, attributes) ".
			"VALUES (?, ?, ?, ?, ?)");
		
		$result = $stmt->execute(array($lock_id,
			$lockdata["permission"],
			$lockdata["name"],
			$lockdata["description"],
			$json_attributes));
		
		if (!$result) {
			echo "Error! insert_query not succeeded";
			return 0;
		}
		
		return $lock_id;
	}
	
	function updateExistingLockRule($lockdata) {
		
		$stmt = DBManager::get()->prepare(
			"UPDATE lock_rules SET ".
			"permission=?, name=?, description=?, attributes=? ".
			"WHERE lock_id=?");
		
		return $stmt->execute(array($lockdata["permission"],
			$lockdata["name"],
			$lockdata["description"],
			json_encode($lockdata['attributes']),
			$lockdata["lock_id"])) ? 1 : 0;
	}
	
	function getLockRuleByName($name) {
		$stmt = DBManager::get()->prepare("SELECT lock_id FROM lock_rules ".
			"WHERE name=?");
		if  (!$stmt->execute(array($name))) {
			echo "Error! query not succeeded";
			return 0;
		}
		$row = $stmt->fetch();
		if ($row === FALSE) {
			return 0;
		}
		return $row["lock_id"];
	}
	
	function deleteLockRule($lock_id) {
		$stmt = DBManager::get()->prepare(
			"DELETE FROM lock_rules ".
			"WHERE lock_id=?");
		
		return $stmt->execute(array($lock_id)) ? 1 : 0;
	}
	
}
