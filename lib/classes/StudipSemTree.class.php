<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
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
require_once("lib/classes/DbSnapshot.class.php");
require_once("lib/classes/TreeAbstract.class.php");
require_once("lib/classes/SemesterData.class.php");
require_once("lib/classes/StudipStudyArea.class.php");
require_once "lib/classes/NotificationCenter.class.php";
require_once("config.inc.php");

DbView::addView('sem_tree');

/**
* class to handle the seminar tree
*
* This class provides an interface to the structure of the seminar tree
*
* @access   public
* @author   André Noack <noack@data-quest.de>
* @package
*/
class StudipSemTree extends TreeAbstract {

    var $sem_dates = array();
    var $sem_number = null;
    var $enable_lonely_sem = true;
    var $visible_only = false;
    var $sem_status = array();

    /**
    * constructor
    *
    * do not use directly, call TreeAbstract::GetInstance("StudipRangeTree")
    * @access private
    */
    function StudipSemTree($args) {
        $this->root_name = $GLOBALS['UNI_NAME_CLEAN'];
        if (isset($args['visible_only'])){
            $this->visible_only = $args['visible_only'];
        }
        if (isset($args['sem_number']) ){
            $this->sem_number = $args['sem_number'];
        }
        if ($args['sem_status']){
            $this->sem_status = $args['sem_status'];
        } else {
            foreach ($GLOBALS['SEM_CLASS'] as $key => $value){
                if ($value['bereiche']){
                    foreach ($GLOBALS['SEM_TYPE'] as $type_key => $type_value) {
                        if($type_value['class'] == $key)
                            $this->sem_status[] = $type_key;
                    }
                }
            }
        }

        if (!count($this->sem_status)){
            $this->sem_status[] = -1;
        }

        parent::TreeAbstract(); //calling the baseclass constructor
        if (isset($args['build_index']) ){
            $this->buildIndex();
        }

        $this->sem_dates = SemesterData::GetSemesterArray();

    }

    /**
    * initializes the tree
    *
    * stores all rows from table sem_tree in array $tree_data
    * @access public
    */
    function init(){
        parent::init();

        $db = $this->view->get_query("view:SEM_TREE_GET_DATA_NO_ENTRIES");

        while ($db->next_record()){
            $this->tree_data[$db->f("sem_tree_id")] = array('type' => $db->f('type'), "info" => $db->f("info"),"studip_object_id" => $db->f("studip_object_id"), "entries" => 0);
            if ($db->f("studip_object_id")){
                $name = $db->f("studip_object_name");
            } else {
                $name = $db->f("name");
            }
            $this->storeItem($db->f("sem_tree_id"), $db->f("parent_id"), $name, $db->f("priority"));
        }
    }

    function initEntries(){
        $this->view->params[0] = $this->sem_status;
        $this->view->params[1] = $this->visible_only ? "visible=1" : "1";
        $this->view->params[1] .= (isset($this->sem_number)) ? " AND ((" . $this->view->sem_number_sql
                                . ") IN (" . join(",",$this->sem_number) .") OR ((" . $this->view->sem_number_sql
                                .") <= " . $this->sem_number[count($this->sem_number)-1]
                                . "  AND ((" . $this->view->sem_number_end_sql . ") >= " . $this->sem_number[count($this->sem_number)-1]
                                . " OR (" . $this->view->sem_number_end_sql . ") = -1))) " : "";

        $db = $this->view->get_query("view:SEM_TREE_GET_ENTRIES");
        while ($db->next_record()){
            $this->tree_data[$db->f("sem_tree_id")]['entries'] = $db->f('entries');
        }
        $this->entries_init_done = true;
    }

    function isModuleItem($item_id){
        return isset($GLOBALS['SEM_TREE_TYPES'][$this->getValue($item_id, 'type')]['is_module']);
    }

    function getSemIds($item_id,$ids_from_kids = false){
        if (!$this->tree_data[$item_id])
            return false;
        $this->view->params[0] = $this->sem_status;
        $this->view->params[1] = $this->visible_only ? "visible=1" : "1";
        if ($ids_from_kids && $item_id != 'root'){
            $this->view->params[2] = $this->getKidsKids($item_id);
        }
        $this->view->params[2][] = $item_id;
        $this->view->params[3] = (isset($this->sem_number)) ? " HAVING sem_number IN (" . join(",",$this->sem_number) .") OR (sem_number <= " . $this->sem_number[count($this->sem_number)-1] . "  AND (sem_number_end >= " . $this->sem_number[count($this->sem_number)-1] . " OR sem_number_end = -1)) " : "";
        $ret = false;
        if ($item_id == 'root' && $ids_from_kids) {
            unset($this->view->params[2]);
            $this->view->params = array_values($this->view->params);
            $rs = $this->view->get_query("view:SEM_TREE_GET_SEMIDS_ROOT");
        } else {
            $rs = $this->view->get_query("view:SEM_TREE_GET_SEMIDS");
        }
        while($rs->next_record()){
            $ret[] = $rs->f(0);
        }
        return $ret;
    }

    function getSemData($item_id,$sem_data_from_kids = false){
        if (!$this->tree_data[$item_id])
            return false;
        $this->view->params[0] = $this->sem_status;
        $this->view->params[1] = $this->visible_only ? "visible=1" : "1";
        if ($sem_data_from_kids && $item_id != 'root'){
            $this->view->params[2] = $this->getKidsKids($item_id);
        }
        $this->view->params[2][] = $item_id;
        $this->view->params[3] = (isset($this->sem_number)) ? " HAVING sem_number IN (" . join(",",$this->sem_number) .") OR (sem_number <= " . $this->sem_number[count($this->sem_number)-1] . "  AND (sem_number_end >= " . $this->sem_number[count($this->sem_number)-1] . " OR sem_number_end = -1)) " : "";
        if ($item_id == 'root' && $sem_data_from_kids) {
            unset($this->view->params[2]);
            $this->view->params = array_values($this->view->params);
            $rs = $this->view->get_query("view:SEM_TREE_GET_SEMDATA_ROOT");
        } else {
            $rs = $this->view->get_query("view:SEM_TREE_GET_SEMDATA");
        }
        return new DbSnapshot($rs);
    }

    function getLonelySemData($item_id){
        if (!$institut_id = $this->tree_data[$item_id]['studip_object_id'])
            return false;
        $this->view->params[0] = $this->sem_status;
        $this->view->params[1] = $this->visible_only ? "visible=1" : "1";
        $this->view->params[2] = $institut_id;
        $this->view->params[3] = (isset($this->sem_number)) ? " HAVING sem_number IN (" . join(",",$this->sem_number) .") OR (sem_number <= " . $this->sem_number[count($this->sem_number)-1] . "  AND (sem_number_end >= " . $this->sem_number[count($this->sem_number)-1] . " OR sem_number_end = -1)) " : "";
        return new DbSnapshot($this->view->get_query("view:SEM_TREE_GET_LONELY_SEM_DATA"));
    }

    function getNumEntries($item_id, $num_entries_from_kids = false){
        if (!$this->tree_data[$item_id])
            return false;
        if (!$this->entries_init_done) $this->initEntries();

        if ($this->enable_lonely_sem && $this->tree_data[$item_id]["studip_object_id"] && !isset($this->tree_data[$item_id]["lonely_sem"])){
            $this->view->params[0] = $this->sem_status;
            $this->view->params[1] = $this->visible_only ? "visible=1" : "1";
            $this->view->params[2] = $this->tree_data[$item_id]["studip_object_id"];
            $this->view->params[3] = (isset($this->sem_number)) ? " HAVING sem_number IN (" . join(",",$this->sem_number) .") OR (sem_number <= " . $this->sem_number[count($this->sem_number)-1] . "  AND (sem_number_end >= " . $this->sem_number[count($this->sem_number)-1] . " OR sem_number_end = -1)) " : "";
            $db2 = $this->view->get_query("view:SEM_TREE_GET_NUM_LONELY_SEM");
            while ($db2->next_record()){
                $this->tree_data[$item_id]['entries'] += $db2->f(0);
                $this->tree_data[$item_id]['lonely_sem'] += $db2->f(0);
            }
        }
        return parent::getNumEntries($item_id, $num_entries_from_kids);
        /*
        if (!$num_entries_from_kids){
            return $this->tree_data[$item_id]["entries"];
        } else {
            $item_list = $this->getKidsKids($item_id);
            $item_list[] = $item_id;
            $ret = 0;
            $num_items = count($item_list);
            for ($i = 0; $i < $num_items; ++$i){
                $ret += $this->tree_data[$item_list[$i]]["entries"];
            }
            return $ret;
        }
        */
    }

    function getAdminRange($item_id){
        if (!$this->tree_data[$item_id])
            return false;
        if ($item_id == "root")
            return "root";
        $ret_id = $item_id;
        while (!$this->tree_data[$ret_id]['studip_object_id']){
            $ret_id = $this->tree_data[$ret_id]['parent_id'];
            if ($ret_id == "root")
                break;
        }
        return $ret_id;
    }

    function InsertItem($item_id, $parent_id, $item_name, $item_info, $priority, $studip_object_id, $type){
        $view = new DbView();
        $view->params = array($item_id,$parent_id,$item_name,$priority,$item_info,$studip_object_id, $type);
        $rs = $view->get_query("view:SEM_TREE_INS_ITEM");
        // Logging
        log_event("STUDYAREA_ADD",$item_id);
        return $rs->affected_rows();
    }

    function UpdateItem($item_id, $item_name, $item_info, $type){
        $view = new DbView();
        $view->params = array($item_name,$item_info,$type,$item_id);
        $rs = $view->get_query("view:SEM_TREE_UPD_ITEM");
        return $rs->affected_rows();
    }

    function DeleteItems($items_to_delete){
        $view = new DbView();
        $view->params[0] = (is_array($items_to_delete)) ? $items_to_delete : array($items_to_delete);
        $view->auto_free_params = false;
        $rs = $view->get_query("view:SEM_TREE_DEL_ITEM");
        $deleted['items'] = $rs->affected_rows();
        $rs = $view->get_query("view:SEMINAR_SEM_TREE_DEL_RANGE");
        $deleted['entries'] = $rs->affected_rows();
        // Logging
        foreach ($items_to_delete as $item_id) {
            log_event("STUDYAREA_DELETE",$item_id);
         }
        return $deleted;
    }

    function DeleteSemEntries($item_ids = null, $sem_entries = null){
        $view = new DbView();
        if ($item_ids && $sem_entries) {
            $sem_tree_ids = $view->params[0] = (is_array($item_ids)) ? $item_ids : array($item_ids);
            $seminar_ids = $view->params[1] = (is_array($sem_entries)) ? $sem_entries : array($sem_entries);
            $rs = $view->get_query("view:SEMINAR_SEM_TREE_DEL_SEM_RANGE");
            $ret = $rs->affected_rows();
            // Logging
            foreach ($sem_tree_ids as $range) {
                foreach ($seminar_ids as $sem) {
                    log_event("SEM_DELETE_STUDYAREA",$sem,$range);
                }
            }
            if($ret){
                foreach ($sem_tree_ids as $sem_tree_id){
                    $studyarea = StudipStudyArea::find($sem_tree_id);
                    if($studyarea->isModule()) {
                        foreach ($seminar_ids as $seminar_id) {
                            NotificationCenter::postNotification('CourseRemovedFromModule', $studyarea, array('module_id' => $sem_tree_id, 'course_id' => $seminar_id));
                        }
                    }
                }
            }
        } elseif ($item_ids){
            $view->params[0] = (is_array($item_ids)) ? $item_ids : array($item_ids);
            // Logging
            foreach ($view->params[0] as $range) {
                log_event("SEM_DELETE_STUDYAREA","all",$range);
            }
            $rs = $view->get_query("view:SEMINAR_SEM_TREE_DEL_RANGE");
            $ret = $rs->affected_rows();
        } elseif ($sem_entries){
            $view->params[0] = (is_array($sem_entries)) ? $sem_entries : array($sem_entries);
            // Logging
            foreach ($view->params[0] as $sem) {
                log_event("SEM_DELETE_STUDYAREA",$sem,"all");
            }
            $rs = $view->get_query("view:SEMINAR_SEM_TREE_DEL_SEMID_RANGE");
            $ret = $rs->affected_rows();
        } else {
            $ret = false;
        }

        return $ret;
    }

    function InsertSemEntry($sem_tree_id, $seminar_id){
        $view = new DbView();
        $view->params[0] = $seminar_id;
        $view->params[1] = $sem_tree_id;
        $rs = $view->get_query("view:SEMINAR_SEM_TREE_INS_ITEM");
        if($ret = $rs->affected_rows()){
            // Logging
            log_event("SEM_ADD_STUDYAREA",$seminar_id,$sem_tree_id);
            $studyarea = StudipStudyArea::find($sem_tree_id);
            if($studyarea->isModule()){
                NotificationCenter::postNotification('CourseAddedToModule', $studyarea, array('module_id' => $sem_tree_id, 'course_id' => $seminar_id));
            }
        }
        return $ret;
    }
}
//$test = TreeAbstract::GetInstance("StudipSemTree");
//echo "<pre>";
//echo strtolower(get_class($test)) .  "\n";
//print_r($test->tree_data);
?>
