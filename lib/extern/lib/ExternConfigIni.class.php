<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* ExternConfigIni.class.php
* 
* This class stores configurations in INI-files.
* 
*
* @author       Peter Thienel <pthienel@web.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup  extern
* @module       ExternConfig
* @package  studip_extern
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// ExternElement.class.php
// This is a wrapper class for configuration files.
// Copyright (C) 2003 Peter Thienel <pthienel@web.de>,
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

class ExternConfigIni extends ExternConfig {

    var $file_name;
    
    /**
    *
    */
    function ExternConfigIni ($range_id, $module_name, $config_id = '') {
    
        parent::ExternConfig ($range_id, $module_name, $config_id);
        if ($this->id) {
            $this->file_name = $this->id . '.cfg';
        }
    }
    
    /**
    *
    */
    function store () {
        parent::store();
        if (!$this->file_name) {
            if ($this->id) {
                $this->file_name = $this->id . '.cfg';
            } else {
                return FALSE;
            }
        }
        $file_content = "; Configuration file for the extern module"
                . " $this->module_name in Stud.IP\n"
                . "; (range_id: $this->range_id)\n"
                . "; DO NOT EDIT !!!\n";
        
        foreach ($this->config as $element => $attributes) {
            $file_content .= "\n[" . $element . "]\n";
            foreach ($attributes as $attribute => $value) {
                if (is_array($value)) {
                    $value = '|' . implode('|', $value);
                }
                $file_content .= $attribute . " = \"" . $value . "\"\n";
            }
        }

        if ($file = @fopen($GLOBALS['EXTERN_CONFIG_FILE_PATH'] . $this->file_name, 'w')) {
            fputs($file, $file_content);
            fclose($file);
            return ($this->updateConfiguration());
        } else {
            ExternModule::printError();
            return FALSE;
        }
        
    }
    
    /**
    *
    */
    function parse () {
        if (!$this->file_name) {
            if ($this->id) {
                $this->file_name = $this->id . '.cfg';
            } else {
                // error handling
            }
        }
        
        $file_name = $GLOBALS['EXTERN_CONFIG_FILE_PATH'] . $this->file_name;
        if (file_exists($file_name)) {
            $config = @parse_ini_file($file_name, TRUE);
            foreach ($config as $element => $attributes) {
                foreach ($attributes as $attribute => $value) {
                    if ($value{0} == '|') {
                        $this->config[$element][$attribute] = explode('|', substr($value, 1));
                    } else {
                        $this->config[$element][$attribute] = $value;
                    }
                }
            }
        } else {
            // error handling
        }
    }
    
    function insertConfiguration () {
        if (!parent::insertConfiguration()) {
            return false;
        }
    
        $time = time();
        $query = "INSERT INTO extern_config SET config_id='{$this->id}', range_id='{$this->range_id}', config_type={$this->module_type}, ";
        $query .= "name='{$this->config_name}', is_standard=0, mkdate=$time, chdate=$time";
        $db->query($query);
    
        if ($db->affected_rows() != 1) {
            return FALSE;
        }
    
        return TRUE;
    }
    
    function deleteConfiguration () {
        if (parent::deleteConfiguration()) {
            if (!@unlink($GLOBALS['EXTERN_CONFIG_FILE_PATH'] . $this->file_name)) {
                return FALSE;
            }
        } else {
            return FALSE;
        }
        return TRUE;
    }
    
}

?>
