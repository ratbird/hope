<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* StudipStmInstanceElement.class.php
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
// StudipStmInstanceElement.class.php
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


define('STUDIPSTMINSTANCEELEMENT_DB_TABLE', 'stm_instances_elements');
define('LANGUAGE_ID',"09c438e63455e3e1b3deabe65fdbc087");

require_once "lib/classes/SimpleORMap.class.php";
require_once "lib/classes/StudipStmInstance.class.php";
require_once "lib/classes/Seminar.class.php";

class StudipStmInstanceElement extends SimpleORMap {
    
    
    static function GetElementsByInstance ($stm_instance_id, $as_objects = false){
        $ret = array();
        $db = DBManager::get()->prepare("
        SELECT stm_abstract_elements.element_id,stm_instances.stm_instance_id,stm_instances_elements.sem_id
        FROM stm_instances LEFT JOIN stm_abstract_elements ON stm_instances.stm_abstr_id = stm_abstract_elements.stm_abstr_id
        LEFT JOIN stm_instances_elements ON stm_abstract_elements.element_id = stm_instances_elements.element_id
        AND stm_instances_elements.stm_instance_id = stm_instances.stm_instance_id
        WHERE stm_instances.stm_instance_id=? ORDER BY elementgroup, position");
        if($db->execute(array($stm_instance_id))){
            while($row = $db->fetch()){
                if (!$as_objects){
                    $ret[] = array($row[0],$row[1],$row[2]);
                } else {
                    $ret[$row[0].'-'.$row[1].'-'.$row[2]] = new StudipStmInstanceElement($row[0],$row[1],$row[2]);
                }
            }
        }
        return $ret;
    }
    
    static function GetElementsByInstanceParticipant ($stm_instance_id, $user_id, $as_objects = false){
        $ret = array();
        $db = DBManager::get()->prepare("SELECT sie.element_id,sie.stm_instance_id,sie.sem_id FROM stm_instances_user siu
         INNER JOIN stm_instances_elements sie ON siu.stm_instance_id=sie.stm_instance_id AND siu.element_id=sie.element_id
          WHERE siu.stm_instance_id=? AND siu.user_id=?");
        if($db->execute(array($stm_instance_id, $user_id))){
            while($row = $db->fetch()){
                if (!$as_objects){
                    $ret[] = array($row[0],$row[1],$row[2]);
                } else {
                    $ret[$row[0].'-'.$row[1].'-'.$row[2]] = new StudipStmInstanceElement($row[0],$row[1],$row[2]);
                }
            }
        }
        return $ret;
    }
    
    function __construct ($element_id = null, $stm_instance_id = null, $sem_id = null) {
        parent::__construct(array($stm_instance_id, $element_id, $sem_id));
        if ($this->is_new) {
            $this->setValue('stm_instance_id', $stm_instance_id);
            $this->setValue('element_id', $element_id);
            $this->setValue('sem_id', $sem_id);
        }
    }
    
    function restore () {
        $where_query = $this->getWhereQuery();
        if ($where_query){
            $query = "SELECT stm_instances_elements.*,stm_abstract_elements.* , seminare.Name as seminar_name, stm_element_types.abbrev as type_abbrev, stm_element_types.name as type_name FROM {$this->db_table}
                        LEFT JOIN stm_abstract_elements USING(element_id) 
                        LEFT JOIN seminare ON seminare.Seminar_id=stm_instances_elements.sem_id
                        INNER JOIN stm_element_types ON(stm_abstract_elements.element_type_id = stm_element_types.element_type_id AND lang_id='".LANGUAGE_ID."') WHERE "
                    . join(" AND ", $where_query) . " " ;
            $rs = DBManager::get()->query($query)->fetchAll(PDO::FETCH_ASSOC);
            if (isset($rs[0])) {
                $this->content = $rs[0];
                $this->is_new = false;
                return true;
            }
        } else if($this->getValue('stm_instance_id') && $this->getValue('element_id')){
            $query = sprintf("SELECT stm_abstract_elements.* , stm_element_types.abbrev as type_abbrev, stm_element_types.name as type_name 
                    FROM stm_instances LEFT JOIN stm_abstract_elements ON stm_instances.stm_abstr_id = stm_abstract_elements.stm_abstr_id
                    INNER JOIN stm_element_types ON(stm_abstract_elements.element_type_id = stm_element_types.element_type_id AND stm_element_types.lang_id='".LANGUAGE_ID."') 
                    WHERE stm_instances.stm_instance_id='%s' AND stm_abstract_elements.element_id='%s'", $this->getValue('stm_instance_id'),$this->getValue('element_id'));
            $rs = DBManager::get()->query($query)->fetchAll(PDO::FETCH_ASSOC);
            if (isset($rs[0])) {
                $this->content = $rs[0];
            }
        }
        $this->is_new = true;
        return FALSE;
    }
    
    function getValue($field){
        switch ($field){
            case 'semester_txt':
            $ret = ( $this->getValue('semester') == 0 ? _("kein") : ( $this->getValue('semester') == 1 ? _("Sommersemester") : _("Wintersemester")));
            break;
            case 'dozenten':
            $sem = Seminar::GetInstance($this->getValue('sem_id'));
            $ret = $sem->getMembers('dozent');
            break;
            case 'type_abbrev':
            $ret = ($this->content['type_abbrev'] ? $this->content['type_abbrev'] : strtoupper(substr($this->getValue('type_name'), 0, 5)));
            break;
            default:
            $ret = parent::getValue($field);
        }
        return $ret;
    }
}
?>