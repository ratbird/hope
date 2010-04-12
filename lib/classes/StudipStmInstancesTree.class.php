<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// SemTree.class.php
// Class to handle structure of the "seminar tree"
// 
// Copyright (c) 2003 André Noack <noack@data-quest.de> 
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
require_once("lib/classes/TreeAbstract.class.php");
require_once("lib/classes/SemesterData.class.php");

/**
* class to handle the seminar tree
*
* This class provides an interface to the structure of the seminar tree
*
* @access   public
* @author   André Noack <noack@data-quest.de>
* @package  
*/
class StudipStmInstancesTree extends TreeAbstract {

    
    /**
    * constructor
    *
    * do not use directly, call TreeAbstract::GetInstance("StudipRangeTree")
    * @access private
    */ 
    function StudipStmInstancesTree($args) {
        $this->root_name = $GLOBALS['UNI_NAME_CLEAN'];
        parent::TreeAbstract(); //calling the baseclass constructor 
    }

    /**
    * initializes the tree
    *
    * stores all rows from table sem_tree in array $tree_data
    * @access public
    */
    function init(){
        parent::init();

        $db = $this->view->get_query("SELECT DISTINCT his_stg.dtxt as studiengang,his_abschl.ltxt as abschluss, his_abschl.abint, his_stg.stg
                                    FROM stm_abstract_assign
                                    LEFT JOIN his_stg ON his_stg.stg=stm_abstract_assign.stg
                                    LEFT JOIN his_abschl ON his_abschl.abint=stm_abstract_assign.abschl
                                    ORDER BY his_abschl.ltxt , his_stg.dtxt");
        while ($db->next_record()){
            $this->storeItem($db->f("abint"), 'root' , $db->f("abschluss") , 0);
            $this->storeItem($db->f("abint") . '-' . $db->f('stg'), $db->f("abint") , $db->f("studiengang") , 0);
        }
        $db = $this->view->get_query("SELECT si.stm_instance_id,sam.*,his_stg.dtxt,his_abschl.ltxt  FROM stm_instances si
                                    LEFT JOIN stm_abstract_assign sam ON si.stm_abstr_id=sam.stm_abstr_id
                                    LEFT JOIN his_stg ON his_stg.stg=sam.stg
                                    LEFT JOIN his_abschl ON his_abschl.abint=sam.abschl
                                    WHERE complete=1 ORDER BY ltxt, dtxt");
        while ($db->next_record()){
            $entries[$db->f("abschl") . '-' . $db->f('stg')][$db->f("stm_instance_id")] = true; 
        }
        
        if (is_array($entries)){
            foreach($entries as $key => $value){
                if (is_array($value)){
                    $this->tree_data[$key]['stm_instances'] = array_keys($value);
                    $this->tree_data[$key]['entries'] = count($value);
                }
            }
        }

    }
    
    function initEntries(){
        $this->entries_init_done = true;
    }
    
    function storeItem($item_id,$parent_id,$name,$priority){
        if (!isset($this->tree_data[$item_id])){
                $this->tree_data[$item_id]["parent_id"] = $parent_id; 
                $this->tree_data[$item_id]["priority"] = $priority;
                $this->tree_data[$item_id]["name"] = $name;
                $this->tree_childs[$parent_id][] = $item_id;
                ++$this->tree_num_childs[$parent_id];
        }
        return;
    }
    
    function getStmIds($item_id, $ids_from_kids = false){
        if(!$ids_from_kids) return $this->tree_data[$item_id]['stm_instances'];
        elseif($this->getNumEntries($item_id,1)){
            $ret = array();
            foreach($this->getKidsKids($item_id) as $kid){
                if(is_array($this->tree_data[$kid]['stm_instances'])){
                    $ret = array_merge($ret, $this->tree_data[$kid]['stm_instances']);
                }
            }
            return $ret;
        }
        return false;
    }

}
/*
$test = TreeAbstract::GetInstance("StudipStmInstancesTree");
echo "<pre>";
echo get_class($test) .  "\n";
print_r($test->tree_data);
*/
?>
