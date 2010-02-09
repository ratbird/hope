<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
// +---------------------------------------------------------------------------+
// DbCrossTableView.class.php
// Class to provide simple Cross Table Views
// Mainly for MySql, may work with other DBs (not tested) 
// Copyright (c) 2002 André Noack <noack@data-quest.de>
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

require_once("DbView.class.php");
require_once("DbSnapshot.class.php");

/**
* Class to provide simple Cross Table Views
*
* Only tested with MySql, needs MySql >= 3.23
* Uses DB abstraction layer of PHPLib
*
* @access	public	
* @author	André Noack <noack@data-quest.de>
* @package	DBTools
*/
class DbCrossTableView extends DbView{

	var $db_class_name = "DB_Seminar";
	var $query_to_transform = "";
	var $transformed_qery = "";
	var $field_list = array();
	var $transform_field = "";
	var $transform_func = "";
	var $pivot_field = "";
	var $pivot_field_names = array();
	var $pivot_field_table = "";
	
	function DbCrossTableView($db_class_name = ""){
		$base_class = strtolower(get_parent_class($this));
		//parent::$base_class($item_id); //calling the baseclass constructor 
		$this->$base_class($db_class_name); //calling the baseclass constructor PHP < 4.1.0
	}
		
	function getTruncQuery($query){
		$trunc_query = false;
		if ($pos = strpos(strtoupper($query),"WHERE")){
			$trunc_query = substr($query,0,$pos);
		} elseif ($pos = strpos(strtoupper($query),"GROUP")) {
			$trunc_query = substr($query,0,$pos);
		} else {
			$trunc_query = $query;
		}
		return $trunc_query;
	}
	
	function setQuery(){
		$this->query_to_transform = $this->get_parsed_query(func_get_args());
		$splitted_query = preg_split("/FROM/i",$this->query_to_transform);
		$trunc_query = "SELECT * FROM ". $this->getTruncQuery($splitted_query[1]) . " WHERE 1 LIMIT 1";
		$this->db->query($trunc_query);
		if(!$this->db->next_record()){
			$this->halt("No query available or empty result!");
		}
		$meta_data = $this->db->metadata();
		for($i = 0;$i < count($meta_data);++$i){ 
			$this->field_list[] = $meta_data[$i]['name'];
		}
	}
		
	function getPivotFieldNames($query){
		if ($this->pivot_field_table) {
			$tables = $this->pivot_field_table . " WHERE 1 AND NOT ISNULL(" . $this->pivot_field . ") ";
		} else {
			$splitted_query = preg_split("/(GROUP)|(ORDER)|(LIMIT)/i",$query);
			if (strpos(strtoupper($splitted_query[0]),"WHERE") === false){
				$tables = " " . $splitted_query[0] . " WHERE 1 AND NOT ISNULL(" . $this->pivot_field . ") ";
			} else {
				$tables = " " . $splitted_query[0] ." AND NOT ISNULL(" . $this->pivot_field . ") ";
			}
		}
		$this->db->query("SELECT DISTINCT " . $this->pivot_field . " FROM " . $tables);
		while ($this->db->next_record()){
			$this->pivot_field_names[] = $this->db->f(0);
		}
	}
	
	function doTransform(){
		if (!$this->query_to_transform){
			$this->halt("No query available!");
		}
		if (!in_array($this->transform_field,$this->field_list)){
			$this->halt("transform field unknown!");
		}
		if (!in_array($this->pivot_field,$this->field_list)){
			$this->halt("pivot field unknown!");
		}
		$splitted_query = preg_split("/FROM/i",$this->query_to_transform);
		$this->getPivotFieldNames($splitted_query[1]);
		if (!count($this->pivot_field_names)){
			$this->halt("No pivot field names found!");
		}
		if (!$this->transform_func){
			$this->transform_func = "max";
		}
		for ($i = 0; $i < count($this->pivot_field_names); ++$i){ 
			$transformers .= ", " .$this->transform_func . "(if(" . $this->pivot_field ."='" . $this->pivot_field_names[$i] ."'," . $this->transform_field . 
							",NULL)) AS `" . $this->pivot_field_names[$i] . "` ";
		}
		$this->transformed_query = $splitted_query[0] . $transformers . " FROM " . $splitted_query[1];
		return true;
	}
		
	function getResultSet(){
		if (!$this->transformed_query){
			$this->doTransform();
		}
		return new $this->db_class_name($this->transformed_query);
	}
	
	function getSnapshot(){
		if (!$this->transformed_query){
			$this->doTransform();
		}
		return new DbSnapshot(new $this->db_class_name($this->transformed_query));
	}
	
	function getTempTable($pk = "", $type = "MyISAM"){
		if (!$this->transformed_query){
			$this->doTransform();
		}
		$this->pk = $pk;
		$this->temp_table_type = $type;
		return trim($this->get_temp_table($this->transformed_query));
	}
}
?>
