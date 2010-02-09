<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* SemesterData.class.php
* 
* 
*
* @author		Mark Sievers <msievers@uos.de> 
* @access		public
* @modulegroup	core
* @module			
* @package		studip_core
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// SemesterData.class.php
// Klasse für SemesterVerwaltung
// Copyright (C) 2003 Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
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



class SemesterData {
	var $db;

	function &GetInstance($refresh_cache = false){
		
		static $semester_object;
		
		if ($refresh_cache){
			$semester_object = null;
		}
		if (is_object($semester_object)){
			return $semester_object;
		} else {
			$semester_object = new SemesterData();
			return $semester_object;
		}
	}
	
	function GetSemesterArray(){
		static $all_semester;
		if (is_null($all_semester)){
			$semester =& SemesterData::GetInstance();
			$all_semester = $semester->getAllSemesterData();
			array_unshift($all_semester,0);
			$all_semester[0] = array("name" => sprintf(_("vor dem %s"),$all_semester[1]['name']),'past' => true);
		}
		return $all_semester;
	}
	
	function GetSemesterIndexById($semester_id){
		$index = false;
		foreach(SemesterData::GetSemesterArray() as $i => $sem){
			if($sem['semester_id'] == $semester_id) {
				$index = $i;
				break;
			}
		}
		return $index;
	}
	
	function GetSemesterIdByIndex($semester_index){
		$old_style_semester = SemesterData::GetSemesterArray();
		return isset($old_style_semester[$semester_index]['semester_id']) ? $old_style_semester[$semester_index]['semester_id'] : null;
	}
	
	function GetSemesterIdByDate($timestamp){
		$one_semester = SemesterData::GetInstance()->getSemesterDataByDate($timestamp);
		return isset($one_semester['semester_id']) ? $one_semester['semester_id'] : null;
	}
	
	function GetSemesterSelector($select_attributes = null, $default = 0, $option_value = 'semester_id', $include_all = true){
		$semester = SemesterData::GetSemesterArray();
		unset($semester[0]);
		if($include_all) $semester[] = array('name' => _("alle"), 'semester_id' => 0);
		$semester = array_reverse($semester, true);
		if(!$select_attributes['name']) $select_attributes['name'] = 'sem_select';
		$out = chr(10) . '<select ';
		foreach($select_attributes as $key => $value){
			$out .= ' ' . $key .'="'.$value.'" ';
		}
		$out .= '>';
		foreach($semester as $sem_key => $one_sem){
			$one_sem['key'] = $sem_key;
			$out .= "\n<option value=\"{$one_sem[$option_value]}\" "
				. ($one_sem[$option_value] == $default ? "selected" : "")
				. ">" . htmlReady($one_sem['name']) . "</option>";
		}
		$out .= chr(10) . '</select>';
		return $out;
	}
	
	function SemesterData() {
		$this->db = new DB_Seminar;
	}

	function getAllSemesterData() {
		// alle Semester holen
		$i=0;
		$sql = "SELECT * FROM semester_data order by beginn";
		if  (!$this->db->cache_query($sql)) {
			echo "Error! query not succeeded";
			return 0;
		}
		if ($this->db->num_rows()==0) {
			return 0;
		}
		while ($this->db->next_record()) {
			$semesterdata[$i] = $this->wrapSemesterData();
			$i++;
		}
		return $semesterdata;
	}

	function deleteSemester($semester_id) {
		$sql = "DELETE FROM semester_data WHERE semester_id='".$semester_id."'";
		if  (!$this->db->query($sql)) {
			echo "Error! query not succeeded";
			return 0;
		}
		return 1;
	}

	function getSemesterData($semester_id) {
		// ein bestimmtes Semester holen
		$sql = "SELECT * FROM semester_data WHERE semester_id='".$semester_id."'";
		if  (!$this->db->cache_query($sql)) {
			echo "Error! query not succeeded";
			return 0;
		}
		if ($this->db->num_rows()==0) {
			return 0;
		}
		$this->db->next_record(); 
		return $this->wrapSemesterData();

	}

	function wrapSemesterData() {
		$semesterdata = array();
		$semesterdata["past"] = ($this->db->f("ende") < time());
		$semesterdata["semester_id"]	= $this->db->f("semester_id");
		$semesterdata["name"] 			= $this->db->f("name");
		$semesterdata["description"] 	= $this->db->f("description");
		$semesterdata["semester_token"]	= $this->db->f("semester_token");
		$semesterdata["beginn"]		= $this->db->f("beginn");
		$semesterdata["ende"]	= $this->db->f("ende");
		$semesterdata["vorles_beginn"]	= $this->db->f("vorles_beginn");
		$semesterdata["vorles_ende"]	= $this->db->f("vorles_ende");
		return $semesterdata;
	}

	function getSemesterDataByDate($timestamp) {
		$sql = "SELECT * FROM semester_data WHERE beginn <= '".$timestamp."' AND ende >= '".$timestamp."'";
		if (!$this->db->query($sql)) {
			echo "Error! Query not succeeded!";
			return 0;
		}
		if ($this->db->num_rows()==0) {
			return 0;
		}
		$this->db->next_record();
		$semester = $this->wrapSemesterData();
		$first_sem_week = (int)strftime('%U', $semester['vorles_beginn']);
		$current_sem_week = (int)strftime('%U', $timestamp);
		$last_sem_week = (int)strftime('%U', $semester['vorles_ende']);
		if(strftime('%Y', $timestamp) > strftime('%Y', $semester['vorles_beginn'])){
			$current_sem_week += 52;
		}
		if($last_sem_week < $first_sem_week){
			$last_sem_week += 52;
		}
		if($current_sem_week >= $first_sem_week && $current_sem_week <= $last_sem_week){
			$semester['sem_week_number'] = $current_sem_week - $first_sem_week + 1; 
		} else {
			$semester['sem_week_number'] = false;
		}
		return $semester;
	}

	function getCurrentSemesterData() {
		return $this->getSemesterDataByDate(time());
	}
	
	function getNextSemesterData($timestamp = false) {
		if (!$timestamp){
			$timestamp = time();
		}
		$this->db->query("SELECT * FROM semester_data WHERE beginn > $timestamp ORDER BY beginn LIMIT 1");
		if ($this->db->next_record()){
			return $this->wrapSemesterData();
		}
		return false;
	}

	function insertNewSemester($semesterdata) {
		// Diese Funktion fügt ein neues Semester ein!
		//echo "<pre>".print_r($semesterdata)."</pre>";
		$semester_id = md5(uniqid("Aragorn"));
		$sql = 	"INSERT INTO semester_data (semester_id,name,description,semester_token,beginn,ende,vorles_beginn,vorles_ende) ".
				"VALUES ('".$semester_id."','".$semesterdata["name"]."','".$semesterdata["description"]."','".$semesterdata["semester_token"]."','".$semesterdata["beginn"]."','".$semesterdata["ende"]."','".$semesterdata["vorles_beginn"]."','".$semesterdata["vorles_ende"]."')";
		//echo $sql;
		if (!$this->db->query($sql)) {
			echo "Error! insert_query not succeeded";
			return 0;
		}
		return $semester_id;
	}
// update!!!	
	function updateExistingSemester($semesterdata) {
		// editiert ein vorhandenes Semester
		//echo "<pre>".print_r($semesterdata)."</pre>";
   		if (!$this->db->query("UPDATE semester_data SET ".
    	            "name='".$semesterdata["name"]."',beginn='".$semesterdata["beginn"]."',".
                    "ende='".$semesterdata["ende"]."',".
                    "vorles_beginn='".$semesterdata["vorles_beginn"]."',".
                    "vorles_ende='".$semesterdata["vorles_ende"]."',".
                    "description='".$semesterdata["description"]."' ".
                    "WHERE semester_id='".$semesterdata["semester_id"]."'")) {
                        echo "Fehler! Einf&uuml;gen in die DB!";
                        return 0;
                    }
    	else return 1;
	}

}


?>
