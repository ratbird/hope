<?php
# Lifter007: TODO
# Lifter003: TEST
/**
* StudipScmEntry.class.php
*
*
*
*
* @author	André Noack <noack@data-quest>, Suchi & Berg GmbH <info@data-quest.de>
* @access	public
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
//
// Copyright (C) 2006 André Noack, Suchi & Berg GmbH <info@data-quest.de>
// 
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

require_once 'lib/classes/SimpleORMap.class.php';

define('STUDIPSCMENTRY_DB_TABLE', 'scm');

class StudipScmEntry extends SimpleORMap {

	public static function GetSCMEntriesForRange($range_id, $as_objects = false){
		$ret = array();
		$query = "SELECT " . STUDIPSCMENTRY_DB_TABLE . ".* FROM "
					. STUDIPSCMENTRY_DB_TABLE . " WHERE range_id='$range_id' ORDER BY mkdate";
		$rs = DBManager::get()->query($query);
		while ($row = $rs->fetch(PDO::FETCH_ASSOC)){
			if (!$as_objects){
				$ret[$row['scm_id']] = $row;
			} else {
				$ret[$row['scm_id']] = new StudipScmEntry();
				$ret[$row['scm_id']]->setData($row, true);
				$ret[$row['scm_id']]->is_new = false;
			}
		}
		return $ret;
	}
	
	public static function GetNumSCMEntriesForRange($range_id){
		$query = "SELECT COUNT(*) FROM "
					. STUDIPSCMENTRY_DB_TABLE . " WHERE range_id='$range_id'";
		return DBManager::get()
				->query($query)
				->fetchColumn();
	}
	
	public static function DeleteSCMEntriesForRange($range_ids){
		if (!is_array($range_ids)){
			$range_ids = array($range_ids);
		}
		$query = "DELETE FROM " . STUDIPSCMENTRY_DB_TABLE . " WHERE range_id IN ('" . join("','", $range_ids). "')";
		return DBManager::get()->exec($query);
	}
	
	function __construct($id = null){
		parent::__construct($id);
	}

}

?>
