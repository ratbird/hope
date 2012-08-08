<?
# Lifter002: TODO
# Lifter003: TODO
# Lifter007: TODO
# Lifter010: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// StudipDocumentTree.class.php
// Class to handle structure of the document folders
// 
// Copyright (c) 2006 André Noack <noack@data-quest.de> 
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
require_once("lib/classes/Modules.class.php");
require_once 'lib/functions.php';
require_once 'lib/statusgruppe.inc.php';

DbView::addView('core');

/**
* class to handle structure of the document folders
*
* This class provides an interface to the structure of the document folders of one
* Stud.IP entity
*
* @access   public
* @author   André Noack <noack@data-quest.de>
* @package  
*/
class StudipDocumentTree extends TreeAbstract {
    
    var $range_id;
    var $entity_type;
    var $must_have_perm;
    var $perms = array('x' => 1, 'w' => 2, 'r' => 4, 'f' => 8);
    var $default_perm = 7;
    var $permissions_activated = false;
    var $group_folders = array();
    
    
    function ExistsGroupFolders($seminar_id)
    {
        $query = "SELECT 1
                  FROM statusgruppen 
                  INNER JOIN folder ON (statusgruppe_id = folder.range_id)
                  WHERE statusgruppen.range_id = ?
                  LIMIT 1";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($seminar_id));
        return $statement->fetchColumn() > 0;
    }
    
    /**
    * constructor
    *
    * do not use directly, call TreeAbstract::GetInstance("StudipDocumentTree")
    * @access private
    */ 
    function StudipDocumentTree($args)
    {
        $this->range_id = $args['range_id'];
        $this->entity_type = $args['entity_type'] ?: get_object_type($this->range_id);
        if ($args['get_root_name']) {
            list($name,) = array_values(get_object_name($this->range_id, $this->entity_type));
        }
        $this->root_name = $name;
        $this->must_have_perm = $this->entity_type == 'sem' ? 'tutor' : 'autor';
        $modules = new Modules();
        $this->permissions_activated = $modules->getStatus('documents_folder_permissions', $this->range_id, $this->entity_type);
        parent::TreeAbstract(); //calling the baseclass constructor 
        $this->tree_data['root']['permission'] = $this->default_perm;
    }

    /**
    * initializes the tree
    *
    * stores all folders in array $tree_data
    * @access public
    */
    function init()
    {
        parent::init();
        $p = 0;
        $top_folders['allgemein'] = array($this->range_id,'FOLDER_GET_DATA_BY_RANGE');
        $top_folders['top'] = array(md5($this->range_id . 'top_folder'),'FOLDER_GET_DATA_BY_RANGE');
        if ($this->entity_type == 'sem') {
            $top_folders['termin'] = array($this->range_id,'FOLDER_GET_DATA_BY_THEMA');
            $top_folders['gruppe'] = array($this->range_id,'FOLDER_GET_DATA_BY_GRUPPE');
        }
        
        foreach ($top_folders as $type => $folder){
            $this->view->params[0] = $folder[0];
            $db = $this->view->get_query("view:" . $folder[1]);
            while ($db->next_record()){
                $this->storeItem($db->f('range_id'), 'root' , 'virtual' , $p++);
                $this->tree_data[$db->f("range_id")]["permission"] = $this->default_perm;
                $this->tree_data[$db->f("folder_id")]["entries"] = 0;
                $this->tree_data[$db->f("folder_id")]["permission"] = $db->f('permission');
                $this->storeItem($db->f("folder_id"), $db->f('range_id'), $db->f('name'), $p++);
                if($type == 'gruppe') $this->group_folders[$db->f("folder_id")] = array();
                $this->initSubfolders($db->f("folder_id"));
            }
        }
        if (is_array($this->tree_childs['root'])){
            $this->tree_childs['root'] = array_unique($this->tree_childs['root']);
        }
    }
    
    function initSubfolders($parent_id){
        $view = new DbView();
        $view->params[0] = $parent_id;
        $db = $view->get_query("view:FOLDER_GET_DATA_BY_RANGE");
        $p = 0;
        while($db->next_record()){
            //$this->tree_data[$db->f("folder_id")] = $db->Record;
            $this->tree_data[$db->f("folder_id")]["entries"] = 0;
            $this->tree_data[$db->f("folder_id")]["permission"] = $db->f('permission');
            $this->storeItem($db->f("folder_id"), $parent_id, $db->f('name'), $p++);
            $this->initSubfolders($db->f("folder_id"));
        }
    }
    
    function getPermissionString($folder_id){
        $perm = (int)$this->getValue($folder_id,'permission');
        $perms = $this->perms;
        array_pop($perms);
        $r = array_flip($perms);
        foreach($perms as $v => $p) if(!($perm & $p)) $r[$p] = '-';
        return join('', array_reverse($r));
    }
    
    function checkPermission($folder_id, $perm, $user_id = null){
        if ($user_id && is_object($GLOBALS['perm']) && $GLOBALS['perm']->have_studip_perm($this->must_have_perm, $this->range_id, $user_id)){
            return true;
        } 
        if ($user_id && !$this->checkGroupFolder($folder_id, $user_id)){
            return false;
        }
        if ($perm != 'f' && !$this->permissions_activated){
            return true;
        } else {
            return (bool)($this->getValue($folder_id, 'permission') & $this->perms[$perm]);
        }
    }
    
    function checkGroupFolder($folder_id, $user_id){
        if($this->isGroupFolder($folder_id)){
            $statusgruppe_id = $this->getValue($folder_id, 'parent_id');
            if(!isset($this->group_folders[$folder_id][$user_id])){
                $this->group_folders[$folder_id][$user_id] = CheckUserStatusgruppe($statusgruppe_id, $user_id);
            }
            return $this->group_folders[$folder_id][$user_id];
        } else {
            return true;
        }
    }
    
    function setPermission($folder_id, $perm){
        $this->tree_data[$folder_id]['permission'] |= $this->perms[$perm];
        $this->view->params[0] = $this->tree_data[$folder_id]['permission'];
        $this->view->params[1] = $folder_id;
        $db = $this->view->get_query("view:FOLDER_UPDATE_PERMISSION");
        if ($ar = $db->affected_rows()){
            $this->view->params[0] = $folder_id;
            $this->view->get_query("view:FOLDER_UPDATE_CHDATE");
        }
        return $ar;
    }
    
    function unsetPermission($folder_id, $perm){
        $this->tree_data[$folder_id]['permission'] &= ~$this->perms[$perm];
        $this->view->params[0] = $this->tree_data[$folder_id]['permission'];
        $this->view->params[1] = $folder_id;
        $db = $this->view->get_query("view:FOLDER_UPDATE_PERMISSION");
        if ($ar = $db->affected_rows()){
            $this->view->params[0] = $folder_id;
            $this->view->get_query("view:FOLDER_UPDATE_CHDATE");
        }
        return $ar;
    }
    
    function setDefaultPermission($folder_id){
        return $this->setPermission($folder_id, $this->default_perm);
    }
    
    function isWritable($folder_id, $user_id = null){
        return $this->checkPermission($folder_id, 'w', $user_id);
    }
    
    function isReadable($folder_id, $user_id = null){
        return $this->checkPermission($folder_id,'r', $user_id);
    }
    
    function isExecutable($folder_id, $user_id = null){
        return $this->checkPermission($folder_id,'x', $user_id);
    }
    
    function checkCreateFolder($folder_id, $user_id = null){
        return $this->checkPermission($this->getRootFolder($folder_id), 'f', $user_id);
    }
    
    function isGroupFolder($folder_id){
        return isset($this->group_folders[$folder_id]);
    }
        
    function getNextSuperFolder($folder_id){
        $parents = $this->getParents($folder_id);
        if (is_array($parents)){
            array_pop($parents);
            foreach($parents as $folder){
                if (!$this->isReadable($folder) || !$this->isExecutable($folder)) return $folder;
            }
        }
        return false;
    }
    
    function getRootFolder($folder_id){
        $parents = $this->getParents($folder_id);
        if (is_array($parents) && count($parents) > 2){
            array_pop($parents);
            array_pop($parents);
            return array_pop($parents);
        } else {
            return $folder_id;
        }
    }
    
    function isLockedFolder($folder_id, $user_id = null){
        return ((!$this->isReadable($folder_id, $user_id) 
                && !$this->isWritable($folder_id, $user_id))
                || !$this->isExecutable($folder_id, $user_id));
    }
    
    function isExerciseFolder($folder_id, $user_id = null){
        return (!$this->isReadable($folder_id, $user_id) 
                && $this->isWritable($folder_id, $user_id)
                && $this->isExecutable($folder_id, $user_id));
    }
    
    function isDownloadFolder($folder_id, $user_id = null){
        if($user_id && is_object($GLOBALS['perm']) && $GLOBALS['perm']->have_studip_perm($this->must_have_perm, $this->range_id, $user_id)){
            return true;
        }
        if (!$this->isExecutable($folder_id, $user_id) || !$this->isReadable($folder_id, $user_id)){
            return false;
        } elseif ( ($s_folder = $this->getNextSuperFolder($folder_id))
        && (!$this->isExecutable($s_folder, $user_id) || !$this->isReadable($s_folder, $user_id) )) {
            return false;
        } else {
            return $this->checkGroupFolder($this->getRootFolder($folder_id), $user_id);
        }
    }
    
    function getReadableFolders($user_id){
        if(is_object($GLOBALS['perm']) && $GLOBALS['perm']->have_studip_perm($this->must_have_perm, $this->range_id, $user_id)){
            return $this->getKidsKids('root');
        } else {
            return $this->getReadableKidsKids('root', $user_id);
        }
    }
    
    function getUnreadableFolders($user_id, $ignore_groups = false){
        if(is_object($GLOBALS['perm']) && $GLOBALS['perm']->have_studip_perm($this->must_have_perm, $this->range_id, $user_id)){
            $ret = array();
        } else {
            if($ignore_groups) {
                $group_folders = $this->group_folders;
                $this->group_folders = array();
            }
            $ret = array_diff($this->getKidsKids('root'), $this->getReadableKidsKids('root', $user_id));
            if($ignore_groups) {
                $this->group_folders = $group_folders;
            }
        }
        return $ret;
    }
    
    function getReadableKidsKids($item_id, $user_id, $in_recursion = false){
        static $kidskids;
        if (!$kidskids || !$in_recursion){
            $kidskids = array();
        }
        if (!$in_recursion && $item_id != 'root'){
            if (!($this->isReadable($item_id, $user_id) && $this->isExecutable($item_id, $user_id))) return $kidskids;
            else {
                $s_folder = $this->getNextSuperFolder($item_id);
                if ($s_folder && !($this->isReadable($s_folder, $user_id) && $this->isExecutable($s_folder, $user_id))) return $kidskids;
            }
        }
        $num_kids = $this->getNumKids($item_id);
        if ($num_kids){
            $kids = array();
            foreach($this->getKids($item_id) as $one){
                if($this->isReadable($one, $user_id) && $this->isExecutable($one, $user_id)) $kids[] = $one;
            }
            $kidskids = array_merge((array)$kidskids, (array)$kids);
            foreach($kids as $kid){
                $this->getReadableKidsKids($kid,$user_id, true);
            }
        }
        return (!$in_recursion) ? $kidskids : null;
    }
    
    function isFolder($item_id){
        return ($item_id != 'root' && isset($this->tree_data[$item_id]));
    }
    
    function getGroupFolders(){
        return array_keys($this->group_folders);
    }
    
}
//test
/*
$f = TreeAbstract::GetInstance('StudipDocumentTree', array('range_id' => '55c88e42f2cbbda0fa55b6c1af6121fc'));
echo "<pre>";
print_r($f->tree_childs);
print_r($f->tree_data);
//echo $f->getPermissionString('823b5c771f17d4103b1828251c29a7cb');
//echo var_dump($f->isWritable('823b5c771f17d4103b1828251c29a7cb'));
//echo "<br>";
//$f->unsetPermission('834499e2b8a2cd71637890e5de31cba3', 'x');
//echo $f->getPermissionString('823b5c771f17d4103b1828251c29a7cb');
//echo var_dump($f->isWritable('823b5c771f17d4103b1828251c29a7cb'));
//echo var_dump($f->getNextSuperFolder('823b5c771f17d4103b1828251c29a7cb'));
echo var_dump($f->getReadableFolders());
//echo var_Dump($f->getKids('823b5c771f17d4103b1828251c29a7cb'));
*/
?>
