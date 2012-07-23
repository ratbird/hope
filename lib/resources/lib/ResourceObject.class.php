<?
# Lifter002: TODO
# Lifter003: TEST
# Lifter007: TODO
# Lifter010: TODO
/**
* ResourceObject.class.php
* 
* class for a resource-object
* 
*
* @author       Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup      resources
* @module       ResourceObject.class.php
* @package      resources
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// ResourceObject.class.php
// Klasse fuer ein Ressourcen-Object
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

require_once $GLOBALS['RELATIVE_PATH_RESOURCES'] . "/lib/AssignObject.class.php";


/*****************************************************************************
ResourceObject, zentrale Klasse der Ressourcen Objekte
/*****************************************************************************/
class ResourceObject {
    
    function Factory(){
        static $ressource_object_pool;
        $argn = func_num_args();
        if ($argn == 1){
            if ( ($id = func_get_arg(0)) ){
                if (is_object($ressource_object_pool[$id]) && $ressource_object_pool[$id]->getId() == $id){
                    return $ressource_object_pool[$id];
                } else {
                    $ressource_object_pool[$id] = new ResourceObject($id);
                    return $ressource_object_pool[$id];
                }
            }
        }
        return new ResourceObject(func_get_args());
    }
    
    var $id;                //resource_id des Objects;
    var $name;              //Name des Objects
    var $description;           //Beschreibung des Objects;
    var $owner_id;              //Owner_id;
    var $category_id;           //Die Kategorie des Objects
    var $category_name;         //name of the assigned catgory
    var $category_iconnr;           //iconnumber of the assigned catgory
    var $is_room = null;
    var $is_parent = null;
    var $my_state = null;
    
    //Konstruktor
    function ResourceObject($argv) {
        global $user;
        
        $this->user_id = $user->id;
        
        if($argv && !is_array($argv)) {
            $id = $argv;
            $this->restore($id);
        } elseif (count($argv) == 7) {
            $this->name = $argv[0];
            $this->description = $argv[1];
            $this->parent_bind = $argv[2];
            $this->root_id = $argv[3];
            $this->parent_id = $argv[4];
            $this->category_id = $argv[5];
            $this->owner_id = $argv[6];
            if (!$this->id)
                $this->id=$this->createId();
            if (!$this->root_id) {
                $this->root_id = $this->id;
                $this->parent_id = "0";
            }
            $this->chng_flag=FALSE;

        }
    }
    
    function createId()
    {
        return md5(uniqid("DuschDas",1));
    }

    function create()
    {
        $query = "SELECT resource_id FROM resources_objects WHERE resource_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->id));
        $check = $statement->fetchColumn();

        if ($check) {
            $this->chng_flag = TRUE;
            return $this->store();
        }

        return $this->store(TRUE);
    }
    
    function setName($name){
        $this->name= $name;
        $this->chng_flag = TRUE;
    }

    function setDescription($description){
        $this->description= $description;
        $this->chng_flag = TRUE;
    }

    function setCategoryId($category_id){
        $this->category_id=$category_id;
        $this->chng_flag = TRUE;
    }

    function setMultipleAssign($value){
        if ($value) {
            $this->multiple_assign = true;
        } else {
            // multiple assigns where allowed and are not allowed anymore - update
            if ($this->multiple_assign) {
                // update the table resources_temporary_events or bad things will happen
                $this->updateAllAssigns();
            }
            
            $this->multiple_assign = false;
        }
        
        $this->chng_flag = TRUE;
    }

    function setParentBind($parent_bind){
        if ($parent_bind==on)
            $this->parent_bind=TRUE;
        else
            $this->parent_bind=FALSE;
        $this->chng_flag = TRUE;
    }

    function setLockable($lockable){
        if ($lockable == on)
            $this->lockable=TRUE;
        else
            $this->lockable=FALSE;
        $this->chng_flag = TRUE;
    }

    function setOwnerId($owner_id){
        $old_value = $this->owner_id;
        $this->owner_id=$owner_id;
        $this->chng_flag = TRUE;
        if ($old_value != $owner_id)
            return TRUE;
        else
            return FALSE;
    }
    
    function setInstitutId($institut_id){
        $this->institut_id=$institut_id;
        $this->chng_flag = TRUE;
    }


    function getId() {
        return $this->id;
    }

    function getRootId() {
        return $this->root_id;
    }

    function getParentId() {
        return $this->parent_id;
    }

    function getName() {
        return $this->name;
    }

    function getCategoryName() {
        return $this->category_name;
    }

    function getCategoryIconnr() {
        return $this->category_iconnr;
    }

    function getCategoryId() {
        return $this->category_id;
    }

    function getDescription() {
        return $this->description;
    }

    function getOwnerId() {
        return $this->owner_id;
    }

    function getInstitutId() {
        return $this->institut_id;
    }
    
    function getMultipleAssign() {
        return $this->multiple_assign;
    }
    
    function getParentBind() {
        return $this->parent_bind;
    }
    
    function getOwnerType($id='') {
        if (!$id)
            $id=$this->owner_id;

        //Is it a global?
        if ($id == "global"){
            return "global";
        } else if ($id == "all"){
            return "all";
        } else {
            $type = get_object_type($id);
            return ($type == "fak") ? "inst" : $type;
        }
    }
    
    function getOrgaName ($explain=FALSE, $id='') {
        if (!$id) {
            $id=$this->institut_id;
        }

        $query = "SELECT Name FROM Institute WHERE Institut_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($id));
        $name = $statement->fetchColumn();

        if ($name) {
            return $explain
                ? sprintf('%s (%s)', $name, _('Einrichtung'))
                : $name;
        }
    }
    
    function getOwnerName($explain=FALSE, $id='') {
        if (!$id)
            $id=$this->owner_id;

        switch ($this->getOwnerType($id)) {
            case "all":
                if (!$explain)
                    return _("jederR");
                else
                    return _("jedeR (alle Nutzenden)");
            break;
            case "global":
                if (!$explain)
                    return _("Global");
                else
                    return _("Global (zentral verwaltet)");
            break;
            case "user":
                if (!$explain)
                    return get_fullname($id,'full');
                else
                    return get_fullname($id,'full')." ("._("NutzerIn").")";
            break;
            case "inst":
                return $this->getOrgaName($explain, $id);
            break;
            case "sem":
                $query = "SELECT Name FROM seminare WHERE Seminar_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($id));
                $name = $statement->fetchColumn();

                if ($name) {
                    return $explain
                        ? sprintf('%s (%s)', $name, _('Veranstaltung'))
                        : $name;
                }
            break;
        }
    }
    
    /**
     * This function creates a link to show an room in a new window/tab/popup. This function should not be used from outside of this class anymore
     *
     * @param bool   $quick_view
     * @param string $view
     * @param string $view_mode
     * @param int    $timestamp jump to this date in the room-assignment-plan
     *
     * @return string href-part of a link
     */
    private function getLink($quick_view = FALSE, $view ="view_schedule", $view_mode = "no_nav", $timestamp = FALSE) {
        if (func_num_args() == 1) {
            $timestamp = func_get_arg(0);
        }
        return URLHelper::getLink(sprintf ("resources.php?actual_object=%s&%sview=%s&%sview_mode=%s%s", $this->id, ($quick_view) ? "quick_" : "", $view, ($quick_view) ? "quick_" : "", $view_mode, ($timestamp > 0) ? "&start_time=".$timestamp : ""));
    }
    
    function getFormattedLink($javaScript = TRUE, $target_new = TRUE, $quick_view = TRUE, $view ="view_schedule", $view_mode = "no_nav", $timestamp = FALSE, $link_text = FALSE) {
        global $auth;
        
        if (func_num_args() == 1) {
            $timestamp = func_get_arg(0);
            $javaScript = TRUE;
        }

        if (func_num_args() == 2) {
            $timestamp = func_get_arg(0);
            $link_text = func_get_arg(1);
            $javaScript = TRUE;
        }

        
        if ($this->id) {
            if ((!$javaScript) || (!$auth->auth["jscript"]))
                return "<a ".(($target_new) ? "target=\"_blank\"" : "")." href=\"".$this->getLink($quick_view, $view, $view_mode, ($timestamp > 0) ? $timestamp : FALSE)."\">".(($link_text) ? $link_text : $this->getName())."</a>";
            else
                return "<a href=\"javascript:void(null)\" onClick=\"window.open('".$this->getLink($quick_view, $view, $view_mode, ($timestamp > 0) ? $timestamp : FALSE)."','','scrollbars=yes,left=10,top=10,width=1000,height=680,resizable=yes')\" >".(($link_text) ? $link_text : $this->getName())."</a>";
        } else
            return FALSE;
    }
    
    function getOrgaLink ($id='') {
        if (!$id)
            $id=$this->institut_id;
        
        return  sprintf ("institut_main.php?auswahl=%s",$id);   
    }

    
    function getOwnerLink($id='') {
        
        if (!$id)
            $id=$this->owner_id;
        switch ($this->getOwnerType($id)) {
            case "global":
                return '#a';
            case "all":
                return '#a';
            break;
            case "user":
                return  sprintf ("about.php?username=%s",get_username($id));
            break;
            case "inst":
                return  sprintf ("institut_main.php?auswahl=%s",$id);
            break;
            case "sem":
                return  sprintf ("seminar_main.php?auswahl=%s",$id);
            break;
        }
    }
    
    function getPlainProperties($only_requestable = FALSE)
    {
        $query = "SELECT b.name, a.state, b.type, b.options
                  FROM resources_objects_properties AS a
                  LEFT JOIN resources_properties AS b USING (property_id)
                  LEFT JOIN resources_categories_properties AS c USING (property_id)
                  WHERE resource_id = ? AND c.category_id = ?";
        if ($only_requestable) {
            $query .= " AND requestable = 1";
        }
        $query .= " ORDER BY b.name";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $this->id,
            $this->category_id
        ));

        $temp = array();
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $temp[] = sprintf('%s: %s',
                              $row['name'],
                              $row['type'] == 'bool'
                                  ? ($row['state'] ? $row['options'] : '-')
                                  : $row['state']);
        }

        return implode(" \n", $temp);
    }

    function getSeats()
    {
        if (is_null($this->my_state)) {
            $query = "SELECT a.state
                      FROM resources_objects_properties AS a
                      LEFT JOIN resources_properties AS b USING (property_id)
                      LEFT JOIN resources_categories_properties AS c USING (property_id)
                      WHERE resource_id = ? AND c.category_id = ? AND b.system = 2
                      ORDER BY b.name";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                $this->id,
                $this->category_id
            ));
            $this->my_state = $statement->fetchColumn() ?: null;
        }
        return $this->my_state ?: false;
    }

    function isUnchanged()
    {
        return $this->mkdate == $this->chdate;
    }

    function isDeletable()
    {
        return (!$this->isParent() && !$this->isAssigned());
    }

    function isParent()
    {
        if (is_null($this->is_parent)) {
            $query = "SELECT 1
                      FROM resources_objects
                      WHERE parent_id = ?
                      LIMIT 1";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($this->id));
            $this->is_parent = ($statement->fetchColumn() > 0) ?: null;
        }
        return (!is_null($this->is_parent));
    }
    
    function isAssigned()
    {
        if (is_null($this->is_assigned)) {
            $query = "SELECT 1
                      FROM resources_assign
                      WHERE resource_id = ?
                      LIMIT 1";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($this->id));
            $this->is_assigned = ($statement->fetchColumn() > 0) ?: null;
        }
        return (!is_null($this->is_assigned));
    }
    
    function isRoom()
    {
        if (is_null($this->is_room)) {
            $query = "SELECT is_room
                      FROM resources_objects
                      LEFT JOIN resources_categories USING (category_id)
                      WHERE resource_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($this->id));
            $this->is_room = ($statement->fetchColumn() > 0) ?: null;
        }
        return (!is_null($this->is_room));
    }
    
    function isLocked()
    {
        return $this->isRoom() && $this->isLockable() && isLockPeriod('edit');
    }

    function isLockable()
    {
        return $this->lockable;
    }
    
    function flushProperties()
    {
        $query = "DELETE FROM resources_objects_properties
                  WHERE resource_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->id));
        return $statement->rowCount() > 0;
    }
    
    function storeProperty ($property_id, $state)
    {
        $query = "INSERT INTO resources_objects_properties
                    (resource_id, property_id, state)
                  VALUES (?, ?, ?)";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $this->id,
            $property_id,
            $state
        ));
        return $statement->rowCount() > 0;
    }
    
    function deletePerms ($user_id)
    {
        $query = "DELETE FROM resources_user_resources
                  WHERE user_id = ? AND resource_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $user_id,
            $this->id
        ));
        return $statement->rowCount() > 0;
    }
    
    function storePerms ($user_id, $perms = '')
    {
        //User_id zwingend notwendig
        if (!$user_id) {
            return FALSE;
        }

        $query = "SELECT 1
                  FROM resources_user_resources
                  WHERE user_id = ? AND resource_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $user_id,
            $this->id
        ));
        $check = $statement->fetchColumn();

        //neuer Eintrag 
        if (!$check) {
            if (!$perms) {
                $perms = 'autor';
            }
            $query = "INSERT INTO resources_user_resources
                        (perms, user_id, resource_id)
                      VALUES (?, ?, ?)";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                $perms,
                $user_id,
                $this->id
            ));
            return $statement->rowCount() > 0;
        } 

        //alter Eintrag wird veraendert
        if ($perms) {
            $query = "UPDATE resources_user_resources
                      SET perms = ?
                      WHERE user_id = ? AND resource_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                $perms,
                $user_id,
                $this->id
            ));
            return $statement->rowCount() > 0;
        }

        return FALSE;
    }
    
    function restore($id='')
    {
        if (func_num_args() == 0) {
            $id = $this->id;
        }

        $query = "SELECT ro.*, rc.name AS category_name, rc.iconnr
                  FROM resources_objects AS ro
                  LEFT JOIN resources_categories AS rc USING (category_id)
                  WHERE resource_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($id));
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            return false;
        }

        $this->id = $id;
        $this->name            = $row['name'];
        $this->description     = $row['description'];
        $this->owner_id        = $row['owner_id'];
        $this->institut_id     = $row['institut_id'];
        $this->category_id     = $row['category_id'];
        $this->category_name   = $row['category_name'];
        $this->category_iconnr = $row['iconnr'];
        $this->parent_id       = $row['parent_id'];
        $this->lockable        = $row['lockable'];
        $this->multiple_assign = $row['multiple_assign'];
        $this->root_id         = $row['root_id'];
        $this->mkdate          = $row['mkdate'];
        $this->chdate          = $row['chdate'];
        $this->parent_bind     = !empty($row['parent_bind']);

        return true;
    }

    function store($create=''){
        // Natuerlich nur Speichern, wenn sich was gaendert hat oder das Object neu angelegt wird
        if ($this->chng_flag || $create) {
            $chdate = time();
            $mkdate = time();
            
            if ($create) {
                //create level value
                if (!$this->parent_id) {
                    $level = 0;
                } else {
                    $query = "SELECT level FROM resources_objects WHERE resource_id = ?";
                    $statement = DBManager::get()->prepare($query);
                    $statement->execute(array($this->parent_id));
                    $level = $statement->fetchColumn();
                }

                $query = "INSERT INTO resources_objects
                            (resource_id, root_id, parent_id, category_id,
                             owner_id, institut_id, level, name, description, 
                             lockable, multiple_assign, mkdate, chdate)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                                  UNIX_TIMESTAMP(), UNIX_TIMESTAMP())";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array(
                    $this->id,
                    $this->root_id,
                    $this->parent_id,
                    $this->category_id,
                    $this->owner_id,
                    $this->institut_id,
                    $level,
                    $this->name,
                    $this->description,
                    $this->lockable,
                    $this->multiple_assign 
                ));
                $affected_rows = $statement->rowCount();
            } else {
                $query = "UPDATE resources_objects
                          SET root_id = ?, parent_id = ?, category_id = ?,
                              owner_id = ?, institut_id = ?, name = ?,
                              description = ?, lockable = ?, multiple_assign = ?
                          WHERE resource_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array(
                    $this->root_id,
                    $this->parent_id,
                    $this->category_id,
                    $this->owner_id,
                    $this->institut_id,
                    $this->name,
                    $this->description,
                    $this->lockable,
                    $this->multiple_assign,
                    $this->id
                ));
                $affected_rows = $statement->rowCount();
                
                if ($affected_rows) {
                    $query = "UPDATE resources_objects
                              SET chdate = UNIX_TIMESTAMP()
                              WHERE resource_id = ?";
                    $statement = DBManager::get()->prepare($query);
                    $statement->execute(array($this->id));
                }
            }

            return $affected_rows > 0;
        }
        return FALSE;
    }

    function delete()
    {
        $this->deleteResourceRecursive ($this->id);
    }
    
    //delete section, very privat :)
    
    //private
    function deleteAllAssigns($id='')
    {
        if (!$id) {
            $id = $this->id;
        }

        $query = "SELECT assign_id FROM resources_assign WHERE resource_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($id));
        while ($assign_id = $statement->fetchColumn()) {
            AssignObject::Factory($assign_id)->delete();
        }
    }

    /**
     * update all assigns for this resource
     * 
     * @throws Exception 
     */
    function updateAllAssigns() {
        if (!$this->id) {
            throw new Exception('Missing resource-ID!');
        }

        $query = "SELECT assign_id FROM resources_assign WHERE resource_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->id));
        
        while ($assign_id = $statement->fetchColumn()) {
            AssignObject::Factory($assign_id)->updateResourcesTemporaryEvents();
        }
    }

    //private
    function deleteAllPerms($id='')
    {
        if (!$id) {
            $id = $this->id;
        }
        $query = "DELETE FROM resources_user_resources
                  WHERE resource_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($id));
    }

    function deleteResourceRecursive($id)
    {
        //subcurse to subordinated resource-levels
        $query = "SELECT resource_id FROM resources_objects WHERE parent_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($id));

        while ($resource_id = $statement->fetchColumn()) {
            $this->deleteResourceRecursive($resource_id, $recursive);
        }

        $this->deleteAllAssigns($id);
        $this->deleteAllPerms($id);
        $this->flushProperties($id);

        $query = "DELETE FROM resources_objects WHERE resource_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($id));
    }
    
    function getPathArray($include_self = false)
    {
        $result_arr = array();

        $id = $this->getId();
        if ($include_self) {
            $result_arr[$id] = $this->getName();
        }

        $query = "SELECT name, parent_id, resource_id
                  FROM resources_objects
                  WHERE resource_id = ?";
        $statement = DBManager::get()->prepare($query);

        while ($id) {
            $statement->execute(array($id));
            $temp = $statement->fetch(PDO::FETCH_ASSOC);
            $statement->closeCursor();

            if (!$temp) {
                break;
            }
            
            $id = $temp['parent_id'];
            $result_arr[$temp['resource_id']] = $temp['name'];
        }
        return $result_arr;
    }
    
    function getPathToString($include_self = false, $delimeter = '/')
    {
        return join($delimeter, array_reverse(array_values($this->getPathArray($include_self))));
    }
}
