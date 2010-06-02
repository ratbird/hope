<?php
# Lifter007: TODO
# Lifter003: TEST
/**
* StudipAdmissionGroup.class.php
*
*
*
*
* @author   André Noack <noack@data-quest>, Suchi & Berg GmbH <info@data-quest.de>
* @access   public
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
//
// Copyright (C) 2005 André Noack <noack@data-quest>,
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

require_once 'lib/classes/SimpleORMap.class.php';
require_once 'lib/classes/Seminar.class.php';

define('STUDIPADMISSIONGROUP_DB_TABLE', 'admission_group');

class StudipAdmissionGroup extends SimpleORMap {
    
    public $members = array();
    public $deleted_members = array();
    
    function GetAdmissionGroupBySeminarId($seminar_id){
        $sem = Seminar::GetInstance($seminar_id);
        if($sem->admission_group){
            return new StudipAdmissionGroup($sem->admission_group);
        } else {
            return null;
        }
    }
    
    function __construct($id = null){
        $this->db_table = STUDIPADMISSIONGROUP_DB_TABLE;
        parent::__construct($id);
    }

    function restore(){
        $ret = parent::restore();
        $this->restoreMembers();
        return $ret;
    }

    function restoreMembers(){
        $this->members = array();
        if (!$this->isNew()){
            $where_query = $this->getWhereQuery();
            $rs = DBManager::get()->query(sprintf("SELECT Seminar_id FROM seminare WHERE admission_group='%s' ORDER BY Name", $this->getId()));
            while($seminar_id = $rs->fetchColumn()){
                $this->members[$seminar_id] = Seminar::GetInstance($seminar_id);
            }
        }
        return count($this->members);
    }

    function store(){
        $ret = $this->storeMembers();
        $ret += parent::store();
        return $ret;
    }

    function storeMembers(){
            $ret = 0;
            if (count($this->members)){
                foreach($this->getMemberIds() as $seminar_id){
                    $ret += $this->members[$seminar_id]->store();
                }
            }
            if (count($this->deleted_members)){
                foreach(array_keys($this->deleted_members) as $seminar_id){
                    $ret += $this->deleted_members[$seminar_id]->store();
                }
            }
        return $ret;
    }

    function getMemberIds(){
        return array_keys($this->members);
    }
    
    function getNumMembers(){
        return count($this->members);
    }
    
    function isMember($seminar_id){
        return isset($this->members[$seminar_id]);
    }

    function addMember($seminar_id){
        if($this->isNew() && !$this->getId()) $this->setId($this->getNewId());
        if (!$this->isMember($seminar_id)){
            $this->members[$seminar_id] = Seminar::GetInstance($seminar_id);
            if(!$this->members[$seminar_id]->is_new){
                $this->members[$seminar_id]->admission_group = $this->getId();
            } else {
                unset($this->members[$seminar_id]);
            }
        }
        return isset($this->members[$seminar_id]);
    }

    function deleteMember($seminar_id){
        if ($this->isMember($seminar_id)){
            $this->members[$seminar_id]->admission_group = '';
            $this->deleted_members[$seminar_id] = $this->members[$seminar_id];
            unset($this->members[$seminar_id]);
            return true;
        } else {
            return false;
        }
    }

    function setData($data, $reset = false){
        $count = parent::setData($data, $reset);
        if ($reset){
            $this->restoreMembers();
        }
        return $count;
    }

    function delete() {
        foreach($this->getMemberIds() as $seminar_id){
            $this->deleteMember($seminar_id);
        }
        $this->storeMembers();
        parent::delete();
        return true;
    }
    
    function getValue($field){
        switch ($field){
                case "admission_type":
                    $this->getMemberValues($field);
                break;
                default:
                $ret = parent::getValue($field);
        }
        return $ret;
    }
    
    function getMemberValues($field){
        $ret = array();
        foreach($this->getMemberIds() as $sem){
            $ret[] = (string)$this->members[$sem]->$field;
        }
        return $ret;
    }
    
    function setUniqueMemberValue($field, $value){
        $ret = array();
        foreach($this->getMemberIds() as $sem){
            $ret[] = ( $this->members[$sem]->$field = trim($value) );
        }
        return $ret;
    }
    
    function getUniqueMemberValue($field, $values = null){
        if(is_null($values)) $values = $this->getMemberValues($field);
        $uvalue = array_unique($values);
        if(count($uvalue) > 1) return null;
        else return current($uvalue);
    }
    
    function setMinimumContingent(){
        $ret = array();
        $db = DBManager::get();
        foreach($this->getMemberIds() as $seminar_id){
            $rs = $db->query("SELECT studiengang_id FROM admission_seminar_studiengang WHERE seminar_id = '$seminar_id' LIMIT 1");
            if(!$rs->fetchColumn()){
                $affected_rows = $db->exec("INSERT INTO admission_seminar_studiengang (studiengang_id,quota,seminar_id) VALUES('all', '100', '$seminar_id')");
                if($affected_rows) $ret[] = $seminar_id;
            }
        }
        return $ret;
    }
    
    function checkUserSubscribedtoGroup($user_id, $waitlist = false){
        $table = $waitlist ? 'admission_seminar_user' : 'seminar_user' ;
        $seminar_id = DBManager::get()
                    ->query("SELECT seminar_id FROM $table WHERE seminar_id IN ('".join("','", $this->getMemberIds())."') AND user_id='$user_id'")
                    ->fetchColumn();
        return $seminar_id;
    }
    
    function checkUserSubscribedtoGroupWaitingList($user_id){
        return $this->checkUserSubscribedtoGroup($user_id, true);
    }
}
?>
