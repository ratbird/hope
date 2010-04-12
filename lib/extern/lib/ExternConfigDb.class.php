<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* ExternConfigDb.class.php
* 
* This class is a wrapper class for configuration files.
* 
*
* @author       Peter Thienel <thienel@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup  extern
* @module       ExternConfig
* @package  studip_extern
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// ExternConfigDb.class.php
// This is a wrapper class for configuration data stored in the database.
// Copyright (C) 2007 Peter Thienel <pthienel@web.de>,
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

require_once($GLOBALS["RELATIVE_PATH_EXTERN"]."/lib/extern_functions.inc.php");
require_once($GLOBALS["RELATIVE_PATH_EXTERN"]."/lib/ExternConfig.class.php");
require_once($GLOBALS["RELATIVE_PATH_EXTERN"]."/lib/ExternModule.class.php");


class ExternConfigDb extends ExternConfig {

    var $db;

    /**
    *
    */
    function ExternConfigDb ($range_id, $module_name, $config_id = '') {
        $this->db = new DB_Seminar();
        parent::ExternConfig ($range_id, $module_name, $config_id);
    }

    /**
    *
    */
    function store () {
        parent::store();
        $serialized_config = serialize($this->config);

        if (strlen($serialized_config)) {
            $stmt = DBManager::get()->prepare("UPDATE extern_config 
                SET config = ?, chdate = UNIX_TIMESTAMP()
                WHERE config_id = ? AND range_id = ?");
            $stmt->execute($data = array($serialized_config, $this->id, $this->range_id));

            return($this->updateConfiguration());
        } else {
            ExternModule::printError();
            return FALSE;
        }
        
    }
    
    /**
    *
    */
    function parse () {
        $query = "SELECT * FROM extern_config WHERE config_id = '{$this->id}'";
        if ($this->db->query($query) && $this->db->next_record()) {
            $this->config = unserialize(stripslashes($this->db->f('config')));
        } else {
            ExternModule::printError();
        }
    }
    
    function insertConfiguration () {
        if (!parent::insertConfiguration()) {
            return false;
        }
    
        $serialized_config = serialize($config_obj->config);
        $time = time();
        $query = "INSERT INTO extern_config VALUES (";
        $query .= "'{$this->id}', '{$this->range_id}', {$this->module_type}, ";
        $query .= "'{$this->config_name}', 0, '$serialized_config', $time, $time)";
        $this->db->query($query);
    
        if ($this->db->affected_rows() != 1) {
            return FALSE;
        }
    
        return TRUE;
    }
    
}

?>
