<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* StudipStmInstanceUsers.class.php
* 
* 
* 
*
* @author   André Noack <noack@data-quest.de>
*           Suchi & Berg GmbH <info@data-quest.de>
* @access   public
* @package  
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// StudipStmInstanceUsers.class.php
// 
// Copyright (C) 2006 André Noack <noack@data-quest.de>,
// Suchi & Berg GmbH <info@data-quest.de>
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

require_once "lib/classes/StudipStmInstance.class.php";
require_once "lib/classes/Seminar.class.php";

class StudipStmInstanceUsers {

	function GetInstanceUsers($instance_id){
		$ret = array();
		$db = new DB_Seminar();
		$db->query("SELECT DISTINCT user_id FROM stm_instances_user WHERE stm_instance_id='$instance_id'");
		while($db->next_record()){
			$ret[] = $db->f(0);
		}
		return $ret;
	}
	
	function IsInstanceUser($user_id, $instance_id){
		$db = new DB_Seminar();
		$db->query("");
	}
	
	function IsInstanceSemUser($user_id, $seminar_id){
		$db = new DB_Seminar();
		$db->query("SELECT * FROM stm_instances_user siu INNER JOIN stm_instances_elements sie ON siu.stm_instance_id=sie.stm_instance_id AND siu.element_id=sie.element_id
		WHERE user_id='$user_id' AND sem_id='$seminar_id' LIMIT 1");
		return $db->next_record();
	}
}
?>
