<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: test
# Lifter010: TODO
/**
* ExternConfig.class.php
* 
* Abstract class for storing configurations.
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
require_once($GLOBALS["RELATIVE_PATH_EXTERN"]."/lib/ExternModule.class.php");
require_once($GLOBALS["RELATIVE_PATH_EXTERN"]."/lib/ExternConfigDb.class.php");
require_once($GLOBALS["RELATIVE_PATH_EXTERN"]."/lib/ExternConfigIni.class.php");

class ExternConfig {

    var $id = NULL;
    var $config = array();
    var $global_id = NULL;
    var $module_type;
    var $module_name;
    var $config_name;
    var $range_id;

    function GetInstance ($range_id, $module_name, $config_id = '') {
        
    //  require_once($GLOBALS["RELATIVE_PATH_EXTERN"] . '/lib/ExternConfig' . ucfirst(strtolower($GLOBALS['EXTERN_CONFIG_STORAGE_CONTAINER'])) . '.class.php');
        
        $class_name = 'ExternConfig' . ucfirst(strtolower($GLOBALS['EXTERN_CONFIG_STORAGE_CONTAINER']));
        $instance = new $class_name($range_id, $module_name, $config_id);
        return $instance;
    }
    
    /**
    *
    */
    function ExternConfig ($range_id, $module_name, $config_id = '') {
        
        if ($config_id != '') {
            if ($configuration = ExternConfig::GetConfigurationMetaData($range_id, $config_id)) {
                $this->id = $config_id;
                $this->module_type = $configuration['type'];
                $this->module_name = $configuration['module_name'];
                $this->config_name = $configuration['name'];
                $this->range_id = $range_id;
                $this->parse();
            } else {
                ExternModule::printError();
            }
        } else {
            foreach ($GLOBALS['EXTERN_MODULE_TYPES'] as $type => $module) {
                if ($module['module'] == $module_name) {
                    $this->module_name = $module_name;
                    $this->module_type = $type;
                    $this->range_id = $range_id;
                    break;
                }
            }
        }
        
    }
    
    /**
    *
    */
    function getName () {
        return $this->module_name;
    }
    
    /**
    *
    */
    function getConfigName () {
        return $this->config_name;
    }

    /**
    *
    */
    function getType () {
        global $EXTERN_MODULE_TYPES;
        foreach ($EXTERN_MODULE_TYPES as $key => $known_module) {
            if ($known_module['name'] == $this->module_type)
                return $key;
        }
        
        return FALSE;
    }

    /**
    *
    */
    function getTypeName () {
        return $this->module_type;
    }

    /**
    *
    */
    function &getConfiguration () {
        return $this->config;
    }
    
    function setConfiguration ($config) {
        $this->config = $config;
    }
    
    function setDefaultConfiguration ($config) {
        foreach ($config as $element_name => $element) {
            if (is_array($element)) foreach ($element as $attribute => $value) {
                if ((string)$value{0} == '|') {
                    $new_config[$element_name][$attribute] = explode('|', substr($value, 1));
                } else {
                    $new_config[$element_name][$attribute] = $value;
                }
            }
        }

        $this->id = $this->makeId();
        $this->config_name = $this->createConfigName($this->range_id);
        
        // take the new configuration, write the name in the configuration
        // insert it into the database and store it (method of storaging deepends on
        // object type)
        $this->config = $new_config;
        $this->setValue('Main', 'name', $this->config_name);
        if ($this->insertConfiguration()) {
            $this->store();
        } else {
            echo MessageBox::error(_("Sie haben die maximale Anzahl an Konfigurationen f�r dieses Modul erreicht! Kopieren fehlgeschlagen!"));
            ExternModule::printError();
        }
    }
    
    /**
    *
    */
    function getParameterNames () {}

    /**
    *
    */
    
    function getAllParameterNames () {}

    /**
    *
    */
    function getValue ($element_name, $attribute) {
        
        return $this->config[$element_name][$attribute];
    }

    /**
    *
    */
    function setValue ($element_name, $attribute, $value) {
        if (is_array($value)) {
            ksort($value, SORT_NUMERIC);
        }
        $this->config[$element_name][$attribute] = $value;
    }
    
    /**
    *
    */
    function getAttributes ($element_name, $tag, $second_set = FALSE) {
        if (!is_array($this->config[$element_name])) {
            return '';
        }
            
        $attributes = '';
        
        reset($this->config);
        if ($second_set) {
            foreach ($this->config[$element_name] as $tag_attribute_name => $value) {
                if ($value != '') {
                    $tag_attribute = explode('_', $tag_attribute_name);
                    if ($tag_attribute[0] == $tag && !isset($tag_attribute[2])) {
                        if ($this->config[$element_name]["{$tag_attribute_name}2_"] == '') {
                            $attributes .= " {$tag_attribute[1]}=\"$value\"";
                        } else {
                            $attributes .= " {$tag_attribute[1]}=\""
                                    . $this->config[$element_name]["{$tag_attribute_name}2_"] . "\"";
                        }
                    }
                }
            }
        }
        else {
            foreach ($this->config[$element_name] as $tag_attribute_name => $value) {
                if ($value != '') {
                    $tag_attribute = explode('_', $tag_attribute_name);
                    if ($tag_attribute[0] == $tag && !isset($tag_attribute[2])) {
                        $attributes .= " {$tag_attribute[1]}=\"$value\"";
                    }
                }
            }
        }
        
        return $attributes;
    }
    
    // Returns a complete HTML-tag with attributes
    function getTag ($element_name, $tag, $second_set = FALSE) {
        return "<$tag" . $this->getAttributes($element_name, $tag, $second_set) . ">";
    }
    
    /**
    * Restores a configuration with all registered elements and their attributes.
    * The restored configuration contains only the attributes of the current
    * registered elements.
    *
    * @access       public
    * @param        object   $module        The module whose configuration will be restored
    * @param        string   $element_name  The name of the element
    * @param        string[]     $values        These values overwrites the values in current configuration
    */
    function restore ($module, $element_name = '', $values = '') {
        if ($values != '' && $module) {
            if ($element_name) {
                $module_elements[$element_name] = $module->elements[$element_name];
            } else {
                $module_elements = $module->elements;
            }
        
            foreach ($module_elements as $element_name => $element_obj) {
                if ($element_obj->isEditable()) {
                    $attributes = $element_obj->getAttributes();
                    foreach ($attributes as $attribute) {
                        $form_name = $element_name . '_' . $attribute;
                        if (isset($values[$form_name])) {
                            if (is_array($values[$form_name])) {
                                $form_value = array_map('stripslashes', $values[$form_name]);
                            } else {
                                $form_value = stripslashes($values[$form_name]);
                            }
                            $this->setValue($element_name, $attribute, $form_value);
                        }
                    }
                }
            }
        }
    }
    
    /**
    *
    */
    function store () {
        $this->permCheck();
    }
    
    /**
    *
    */
    function parse () {
    
    }
    
    /**
    *
    */
    function makeId () {
        mt_srand((double) microtime() * 1000000);
        
        return md5(uniqid(mt_rand(), 1));
    }
    
    /**
    *
    */
    function getId () {
        return $this->id;
    }
    
    /**
    *
    */
    function createConfigName ($range_id) {
        $configurations = ExternConfig::GetAllConfigurations($range_id, $this->module_type);
        
        $config_name_prefix = _("Konfiguration") . ' ';
        $config_name_suffix = 1;
        $config_name = $config_name_prefix . $config_name_suffix;
        $all_config_names = "";
        
        if (sizeof($configurations[$this->module_name])) {
            foreach ($configurations[$this->module_name] as $configuration)
                $all_config_names .= $configuration['name'];
        }
        
        while(stristr($all_config_names, $config_name)) {
            $config_name = $config_name_prefix . $config_name_suffix;
            $config_name_suffix++;
        }
        
        return $config_name;
    }
    
    /**
    *
    */
    function setGlobalConfig ($global_config, $registered_elements) {
        $this->global_id = $global_config->getId();
        
        // the name of the global configuration has to be overwritten by the
        // the name of the main configuration
        $global_config->config['Main']['name'] = $this->config['Main']['name'];
        
        // The Main-element is not a registered element, because it is part of every
        // module. So register it now.
        $registered_elements[] = 'Main';
        
        foreach ($registered_elements as $name => $element) {
            if ((is_int($name) || !$name) && $this->config[$element]) {
                foreach ($this->config[$element] as $attribute => $value) {
                    if ($value === '') {
                        $this->config[$element][$attribute] = $global_config->config[$element][$attribute];
                    }
                }
            }
            else if ($this->config[$name]) {
                foreach ($this->config[$name] as $attribute => $value) {
                    if ($value === '') {
                        $this->config[$name][$attribute] = $global_config->config[$name][$attribute];
                    }
                }
            }
        }
    }
    
    protected function updateConfiguration () {
        $stmt = DBManager::get()->prepare("UPDATE extern_config SET chdate = ?
            WHERE config_id = ? AND range_id = ?");
        return $stmt->execute(array(time(), $this->id, $this->range_id));
    }
    
    function insertConfiguration () {
        $this->permCheck();        
        $query = "SELECT COUNT(config_id) AS count FROM extern_config WHERE ";
        $query .= "range_id = ? AND config_type = ?";
        $parameters = array($this->range_id, $this->module_type);
        $statement = DBManager::get()->prepare($query);
        $statement->execute($parameters);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if ($row === false && $row['count'] > $GLOBALS['EXTERN_MAX_CONFIGURATIONS']) {
            return FALSE;
        }
        
        return true;
    }
    
    function deleteConfiguration () {
        $query = "SELECT config_id FROM extern_config WHERE config_id = ? ";
        $query .= "AND range_id = ?";
        $parameters = array($this->id ,$this->range_id);
        $statement = DBManager::get()->prepare($query);
        $statement->execute($parameters);
        $row = $statement->fetchColumn();
        if ($row !== false) {
            $query = "DELETE FROM extern_config WHERE config_id = ? ";
            $query .= "AND range_id = ?";
            $parameters = array($this->id ,$this->range_id);
            $statement = DBManager::get()->prepare($query);
            $statement->execute($parameters);
            return TRUE;
        }
        return FALSE;
    }
    
    function copy ($range_id) {
        $copy_config = ExternConfig::GetInstance($range_id, $this->module_name);
        $copy_config->setDefaultConfiguration($this->getConfiguration());
        
        return $copy_config;
    }
    
    /**
    * Returns an array of meta data for all configurations of an institute
    *
    * @access   public
    * @param    string  $range_id
    * @param    string  $type optional parameter to check the right type of
    * the range_id (the right type of "Einrichtung" sem or fak)
    *
    * @return   array       ("name" the name of the configuration, "id" the config_id,
    * "is_default" TRUE if it is the default configuration)
    */
    function GetAllConfigurations ($range_id, $type = NULL) {
        $all_configs = array();
        $query = "SELECT * FROM extern_config WHERE range_id = ? ";
        $parameters = array($range_id);
        if ($type) {
            $query .= "AND config_type = ? ";
            $parameters[] = $type;
        }
        
        $query .= 'ORDER BY name ASC';
        $statement = DBManager::get()->prepare($query);
        $statement->execute($parameters);
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            // return registered modules only!
            $module = $GLOBALS['EXTERN_MODULE_TYPES'][$row['config_type']]['module'];
            if ($module) {
                $all_configs[$module][$row['config_id']] = array('name' => $row['name'],
                        'id' => $row['config_id'], 'is_default' => $row['is_standard']);
            }
        }

        return $all_configs;
    }

    function GetConfigurationMetaData ($range_id, $config_id) {
        $query = "SELECT * FROM extern_config WHERE config_id = ? ";
        $query .= "AND range_id = ? ";
        $parameters = array($config_id, $range_id);
        $statement = DBManager::get()->prepare($query);
        $statement->execute($parameters);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $module_name = $GLOBALS['EXTERN_MODULE_TYPES'][$row['config_type']]['module'];
            if ($module_name) {
                $config = array('name' => $row['name'], 'module_name' => $module_name,
                        'id' => $row['config_id'], 'is_default' => $row['is_standard'],
                        'type' => $row['config_type']);
            }
        } else {
            return FALSE;
        }
    
        return $config;
    }
    
    function ExistConfiguration ($range_id, $config_id)
    {
        $query = "SELECT config_id
                  FROM extern_config
                  WHERE config_id = ? AND range_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($config_id, $range_id));
        return $statement->fetchColumn() > 0;
    }
    
    function SetStandardConfiguration ($range_id, $config_id) {
        $query = "SELECT config_type, is_standard FROM extern_config WHERE config_id = ? ";
        $query .= "AND range_id = ? ";
        $parameters = array($config_id, $range_id);
        $statement = DBManager::get()->prepare($query);
        $statement->execute($parameters);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if ($row !== false) {
            if ($row['is_standard'] == 0) {
                $query = "SELECT config_id FROM extern_config WHERE range_id = ? ";
                $query .= "AND is_standard=1 AND config_type=" . $row['config_type'];
    
                $params = array($range_id);
                $state = DBManager::get()->prepare($query);
                $state->execute($params);
                $res = $state->fetch(PDO::FETCH_ASSOC);
                if ($res) {
                    $query = "UPDATE extern_config SET is_standard=0 WHERE config_id='";
                    $query .= $res['config_id'] . "'";
        
                    $state = DBManager::get()->prepare($query);
                    $state->execute();
                    if ($state->rowCount() != 1) {
                        return FALSE;
                    }
                }
            } else {
                $query = "UPDATE extern_config SET is_standard=0 WHERE config_id = ? ";
                $params = array($config_id);
                $state = DBManager::get()->prepare($query);
                $state->execute($params);
                if ($state->rowCount() != 1) {
                    return FALSE;
                }
            
                return TRUE;
            }
        
            $query = "UPDATE extern_config SET is_standard=1 WHERE config_id = ? ";
            $params = array($config_id);
            $state = DBManager::get()->prepare($query);
            $state->execute($params);
            if ($state->rowCount() != 1) {
                return FALSE;
            }
        } else {
            return FALSE;
        }
        
        return TRUE;
    }
    
    function DeleteAllConfigurations ($range_id) {
        $query = "SELECT config_id FROM extern_config WHERE range_id = ?";
        $params = array($range_id);
        $state = DBManager::get()->prepare($query);
        $state->execute($params);
        $i = 0;
        while($res = $state->fetch(PDO::FETCH_ASSOC))
        {
            $config = ExternConfig::getInstance($range_id, '', $res['config_id']);
            if ($config->deleteConfiguration()) {
                $i++;
            }
        }    
        return $i;
    }

    
    function GetInfo ($range_id, $config_id) {
        $query = "SELECT * FROM extern_config WHERE config_id = ? ";
        $query .= " AND range_id = ? ";
        $params = array($config_id, $range_id);
        $state = DBManager::get()->prepare($query);
        $state->execute($params);
        $res = $state->fetch(PDO::FETCH_ASSOC);
        if ($res) {
            $global_config = ExternConfig::GetGlobalConfiguration($range_id);
            $module_type = $res['config_type'];
            $module = $GLOBALS["EXTERN_MODULE_TYPES"][$res['config_type']]["module"];
            $level = $GLOBALS["EXTERN_MODULE_TYPES"][$res['config_type']]["level"];
            $make = strftime("%x", $res['mkdate']);
            $change = strftime("%x", $res['chdate']);
            $sri = "&lt;studip_remote_include&gt;\n\t&lt;module name=\"$module\" /&gt;";
            $sri .= "\n\t&lt;config id=\"$config_id\" /&gt;\n\t";
            if ($global_config) {
                $sri .= "&lt;global id=\"$global_config\" /&gt;\n\t";
            }
            $sri .= "&lt;range id=\"$range_id\" /&gt;";
            $sri .= "\n&lt;/studip_remote_include&gt;";
            $link_sri = $GLOBALS["EXTERN_SERVER_NAME"] . 'extern.php?page_url=' . _("URL_DER_INCLUDE_SEITE");
        
            if ($level) {
                $link = $GLOBALS["EXTERN_SERVER_NAME"] . "extern.php?module=$module";
                if ($global_config) {
                    $link .= "&config_id=$config_id&global_id=$global_config&range_id=$range_id";
                } else {
                    $link .= "&config_id=$config_id&range_id=$range_id";
                }
                $link_structure = $link . "&view=tree";
                $sri_structure = "&lt;studip_remote_include&gt;\n\tmodule = $module\n\t";
                $sri_structure = "config_id = $config_id\n\t";
                if ($global_config) {
                    $sri_structure .= "global_id = $global_config\n\t";
                }
                $sri_structure .= "range_id=$range_id";
                $sri_structure .= "\n\tview = tree\n&lt;/studip_remote_include&gt;";
                $link_br = $GLOBALS["EXTERN_SERVER_NAME"] . "extern.php?module=$module<br>";
                if ($global_config) {
                    $link_br .= "&config_id=$config_id<br>&global_id=$global_config<br>&range_id=$range_id";
                } else {
                    $link_br .= "&config_id=$config_id<br>&range_id=$range_id";
                }
            
                $info = array("module_type" => $module_type, "module_name" => $module,
                    "name" => $res['name'], "make_date" => $make,
                    "change_date" => $change, "link" => $link, "link_stucture" => $link_structure,
                    "sri" => $sri, "sri_structure" => $sri_structure, "link_sri" => $link_sri,
                    "level" => $level, "link_br" => $link_br);
            } else {
                $info = array("module_type" => $module_type, "module_name" => $module_name,
                    "name" =>$res['name'], "make_date" => $make,
                    "change_date" => $change,   "sri" => $sri, "link_sri" => $link_sri,
                    "level" => $level);
            }
        
            return $info;
        }
    
        return FALSE;   
    }

    function GetGlobalConfiguration ($range_id) {
        $query = "SELECT config_id FROM extern_config WHERE range_id = ? ";
        $query .= "AND config_type = 0 AND is_standard = 1";
        $params = array($range_id);
        $state = DBManager::get()->prepare($query);
        $state->execute($params);
        $res = $state->fetchColumn();
        if ($res) {
            return ($res);
        }
    
        return FALSE;
    }

    function ChangeName ($range_id, $module_type, $config_id, $old_name, $new_name)
    {
        $query = "SELECT 1
                  FROM extern_config
                  WHERE range_id = ? AND config_type = ? AND name = ? ";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($range_id, $module_type, $new_name));
        if ($statement->fetchColumn()) {
            return FALSE;
        }
    
        $query = "UPDATE extern_config
                  SET name = ?, chdate = UNIX_TIMESTAMP() 
                  WHERE config_id = ? AND range_id = ? ";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($new_name, $config_id, $range_id));
        return $statement->rowCount() > 0;
    }

    function GetConfigurationByName ($range_id, $module_type, $name) {
        $query = "SELECT config_id FROM extern_config WHERE range_id = ? AND ";
        $query .= "config_type = ? AND name = ? ";
        $params = array($range_id, $module_type, $name);
        $state = DBManager::get()->prepare($query);
        $state->execute($params);
        $res = $state->fetchColumn();
        if ($res) {
            return $res;
        }
    
        return FALSE;
    }
    
    function GetStandardConfiguration ($range_id, $type) {
        $query = "SELECT config_id FROM extern_config WHERE range_id = ? AND ";
        $query .= "config_type = ? AND is_standard = 1";
        $params = array($range_id, $type);
        $state = DBManager::get()->prepare($query);
        $state->execute($params);
        $res = $state->fetchColumn();
        if ($res) {
            return $res;
        }
        return FALSE;
    }
    
    function GetInstitutesWithConfigurations ($check_view = null) {
        $inst_array = array();
        $c_types = array();
        foreach ($GLOBALS['EXTERN_MODULE_TYPES'] as $id => $conf_type) {
            if (is_null($check_view) || in_array($check_view, $conf_type['view'])) {
                $c_types[] = $id;
            }
        }

        $query = "SELECT i.Institut_id, i.Name, fakultaets_id
                  FROM Institute AS i
                  LEFT JOIN extern_config AS ec ON i.Institut_id = ec.range_id
                  WHERE i.Institut_id = ec.range_id AND ec.config_type IN (?)
                  ORDER BY Name";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($c_types ?: ''));

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $inst_array[$row['Institut_id']] = array('institut_id' => $row['Institut_id'], 'fakultaets_id' => $row['fakultaets_id'], 'name' => $row['Name']);
        }
        return $inst_array;
    }
    
    private function permCheck () {
        // check for sufficient rights
        if ($this->range_id == 'studip' && $GLOBALS['perm']->have_perm('root')) {
            return true;
        }
        if ($GLOBALS['perm']->have_studip_perm('admin', $this->range_id)) {
            return true;
        }
        
        throw new Exception(_("Sie verf�gen nicht �ber ausreichend Rechte f�r diese Aktion."));
    }
    
}
