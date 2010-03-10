<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// IntegrityCheckAbstract.class.php
// Integrity checks for the Stud.IP database
// 
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

require_once "lib/classes/DbView.class.php";
require_once $RELATIVE_PATH_ADMIN_MODULES."/integrity.view.php";

/**
* Abstract base class for integrity check plugins
*
* This class is meant to be abstract, don't use it directly, derive your plugins from it
*
* @access   private 
* @author   André Noack <noack@data-quest.de>
* @package  Admin
*/
class IntegrityCheckAbstract{
    
    /**
    * array of Db Checks
    *
    * structure: array('detail_table'=>{name of detail table},'query'=>{SQL or view to do the check})
    * @access   private
    * @var      array   $checklist
    */
    var $checklist = array();
    /**
    * DbView Object used for queries
    *
    * 
    * @access   private
    * @var      object DbView   $view
    */
    var $view;
    /**
    * name of the master table
    *
    *
    * @access   private
    * @var      string  $master_table
    */
    var $master_table;
    
    function IntegrityCheckAbstract(){
        $this->view = new DbView();
    }
    
    function doCheck($checknumber){
        if(!$this->checklist[$checknumber])
            return false;
        return $this->view->get_query($this->checklist[$checknumber]['query']);
    }
    
    function doCheckDelete($checknumber){
        if(!$this->checklist[$checknumber])
            return false;
        $key = false;
        if(!$key = $this->checklist[$checknumber]['key']){
            $spl = explode(":",$this->checklist[$checknumber]['query']);
            $key = $GLOBALS["_views"][trim($spl[1])]["pk"];
        }
        if(!$key)
            return false;
        $db = $this->view->get_query("DELETE FROM ".$this->checklist[$checknumber]['detail_table']." WHERE $key IN ({1})",
                                    $this->checklist[$checknumber]['query']);
        $a_rows = $db->affected_rows();
        $db->query("OPTIMIZE TABLE ".$this->checklist[$checknumber]['detail_table']);
        return $a_rows;
    }
    function getCheckDetailResult($checknumber){
        if(!$this->checklist[$checknumber])
            return false;
        $key = false;
        if(!$key = $this->checklist[$checknumber]['key']){
            $spl = explode(":",$this->checklist[$checknumber]['query']);
            $key = $GLOBALS["_views"][trim($spl[1])]["pk"];
        }
        if(!$key)
            return false;
        $db = $this->view->get_query("SELECT * FROM ".$this->checklist[$checknumber]['detail_table']." WHERE $key IN ({1})",
                                    $this->checklist[$checknumber]['query']);
        return $db;
    }
    
    function getCheckDetailTable($checknumber){
        if(!$this->checklist[$checknumber])
            return false;
        return $this->checklist[$checknumber]['detail_table'];
    }
    
    function getCheckMasterTable(){
        return $this->master_table;
    }
    
    function getCheckCount(){
        return count($this->checklist);
    }
    
    function getCheckDetailList(){
        $ret = array();
        for($i=0; $i < count($this->checklist); ++$i){
            $ret[] = $this->getCheckDetailTable($i);
        }
        return $ret;
    }
}
?>
