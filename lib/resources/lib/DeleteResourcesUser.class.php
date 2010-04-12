<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* DeleteResourcesUser.class.php
* 
* kills the to the user (range_id)  linked resources
* 
*
* @author       Cornelis Kater <ckater@gwdg.de>
* @access       public
* @package      resources
* @modulegroup      resources_modules
* @module       VeranstaltungResourcesAssign.class.php
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// DeleteResourcesUser.class.php
// Klasse zum Loeschen aller verknuepften Ressourcen mit einem Objekt
// (Nutzer, Veranstaltung oder Einrichtung)
// Copyright (C) 2002 Cornelis Kater <ckater@gwdg.de>
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

require_once 'lib/functions.php';
require_once $GLOBALS['RELATIVE_PATH_RESOURCES'] . "/lib/AssignObject.class.php";
require_once $GLOBALS['RELATIVE_PATH_RESOURCES'] . "/lib/ResourceObject.class.php";
require_once $GLOBALS['RELATIVE_PATH_RESOURCES'] . "/lib/RoomRequest.class.php";

class DeleteResourcesUser {
    var $db;
    var $db2;
    var $range_id;
    var $object_type;
    
    //Konstruktor
    function DeleteResourcesUser ($range_id) {
        global $RELATIVE_PATH_RESOURCES;
        $this->db = new DB_Seminar;
        $this->db2 = new DB_Seminar;
        $this->range_id = $range_id;
        $this->object_type = get_object_type($this->range_id);
    }
    
    //private
    function deleteForeignAssigns() {
        //all assigns linked to resource
        if ($this->range_id) {
            $query = sprintf("SELECT assign_id FROM resources_assign WHERE assign_user_id = '%s' ", $this->range_id);
            $this->db->query($query);
            while ($this->db->next_record()) {
                $killAssign = AssignObject::Factory($this->db->f("assign_id"));
                $killAssign->delete();
            }
        }
        if ($this->object_type == "sem") {
            $query = sprintf("SELECT assign_id FROM termine LEFT JOIN resources_assign ON (resources_assign.assign_user_id = termine.termin_id) WHERE range_id = '%s' ", $this->range_id);
            $this->db->query($query);
            while ($this->db->next_record()) {
                $killAssign = AssignObject::Factory($this->db->f("assign_id"));
                $killAssign->delete();
            }
        }
    }
    
    //private
    function deleteRequests() {
        if ($this->object_type == "sem") {
            $query = sprintf("SELECT request_id FROM resources_requests WHERE seminar_id = '%s' ", $this->range_id);
            $this->db->query($query);
            while ($this->db->next_record()) {
                $killRequest = new RoomRequest ($this->db->f("request_id"));
                $killRequest->delete();
            }
        } elseif ($this->object_type == "date") {
            $query = sprintf("SELECT request_id FROM resources_requests WHERE termin_id = '%s' ", $this->range_id);
            $this->db->query($query);
            while ($this->db->next_record()) {
                $killRequest = new RoomRequest ($this->db->f("request_id"));
                $killRequest->delete();
            }
        }
    }

    //private
    function deleteForeignPerms() {
        $query = sprintf("DELETE FROM resources_user_resources WHERE user_id = '%s' ", $this->range_id);
        $this->db->query($query);           
    }

    //private
    function deleteOwnerResources() {
        $query = sprintf("SELECT resource_id FROM resources_objects WHERE owner_id = '%s' ", $this->range_id);
        $this->db->query($query);   
        while ($this->db->next_record()) {
            $killResource = ResourceObject::Factory ($this->db->f("resource_id"));
            $killResource->delete();
        }
    }
    
    function delete() {
        if ($this->range_id) {
            $this->deleteForeignAssigns();
            $this->deleteRequests();
            $this->deleteForeignPerms();
            $this->deleteOwnerResources();
        }
    }
}
?>
