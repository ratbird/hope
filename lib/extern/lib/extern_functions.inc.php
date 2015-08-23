<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: test
# Lifter010: TODO
/**
* extern_functions.inc.php
* 
* 
* 
*
* @author       Peter Thienel <pthienel@web.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup  extern
* @module       extern_functions
* @package  studip_extern
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// extern_functions.inc.php
//
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


if (version_compare(PHP_VERSION, '5.2', '<'))
{
  require_once('vendor/phpxmlrpc/xmlrpc.inc');
  require_once('vendor/phpxmlrpc/jsonrpc.inc');
  require_once('vendor/phpxmlrpc/json_extension_api.inc');
}


require_once($GLOBALS["RELATIVE_PATH_EXTERN"]."/extern_config.inc.php");
require_once("lib/classes/DataFieldEntry.class.php");
require_once("lib/extern/lib/ExternConfigDb.class.php");

/**
* Returns all statusgruppen for the given range.
*
* If there is no statusgruppe for the given range, it returns FALSE.
*
* @access   public
* @param    string  $range_id
* @return   array   (structure statusgruppe_id => name)
*/
function get_all_statusgruppen ($range_id) {
    $ret = array();
    $roles = Statusgruppe::getFlattenedRoles(getAllStatusgruppen($range_id));
    
    foreach ($roles as $id => $role) {
        $ret[$id] = $role['name_long'];
    }
    return sizeof($ret) ? $ret : false;
}

/**
* Returns an array containing the ids as key and the name as value
* for every given name of statusgruppe.
*
* If there is no known statusgruppe in the given range and name, 
* it returns FALSE.
*
* @access   public
* @param    string  $range_id
* @param    string  $ids comma separated list of statusgruppe_id for 
* statusgruppe valid for the given range (syntax: 'id1','id2',...)
*
* @return   array       (structure statusgruppe_id => name)
*/
function get_statusgruppen_by_id ($range_id, $ids) {
    $ret = array();
    $groups = get_all_statusgruppen($range_id);

    foreach ($ids as $id) {
        if ($groups[$id]) $ret[$id] = $groups[$id];
    }
    return sizeof($ret) ? $ret : false;
}

function print_footer () {
    echo "\n</td></tr></table>";
    echo "\n</td></tr>\n<tr><td class=\"blank\" colspan=\"2\" width=\"100%\">&nbsp;";
    echo "</td></tr>\n</table>\n</body>\n</html>";
    page_close();
}

function mila_extern ($string, $length) {
    if ($length > 0 && strlen($string) > $length) 
        $string = substr($string, 0, $length) . "... ";
    
    return $string;
}

function get_start_item_id ($object_id) {
   
    $query = "SELECT item_id FROM range_tree WHERE studip_object_id=?";
    $parameters = array($object_id);
    $statement = DBManager::get()->prepare($query);
    $statement->execute($parameters);
    $row = $statement->fetchColumn();
    if ($row) {
       return $row;
    }
    return FALSE;
}

function get_generic_datafields ($object_type) {
//  $datafields_obj = new DataFields();
    $fieldStructs = DataFieldStructure::getDataFieldStructures($object_type);
//  $generic_datafields = $datafields_obj->getFields($object_type);
    
    if (sizeof($fieldStructs)) {
        foreach ($fieldStructs as $struct) {
            $datafields["ids"][] = $struct->getID();
            $datafields["names"][] = $struct->getName();
            $datafields["ids_names"][$struct->getID()] = $struct->getName();
        }       
        
        return $datafields;
    }
    
    return FALSE;
}

function array_condense ($array) {
    foreach ($array as $value)
        $array_ret[] = $value;
    
    return $array_ret;
}

function update_generic_datafields (&$config, &$data_fields, &$field_names, $object_type) {
    // setup the generic data fields if they exist or if there are any changes
    if ($generic_datafields = get_generic_datafields($object_type)) {
        $config_datafields = $config->getValue("Main", "genericdatafields");
        if (!is_array($config_datafields))
            $config_datafields = array();
        
        $visible = (array) $config->getValue("Main", "visible");
        $order = (array) $config->getValue("Main", "order");
        $aliases = (array) $config->getValue("Main", "aliases");
        $store = FALSE;
        
        // data fields deleted
        if ($diff_generic_datafields = array_diff($config_datafields,
                $generic_datafields["ids"])) {
            $swapped_datafields = array_flip($config_datafields);
            $swapped_order = array_flip($order);
            $offset = sizeof($data_fields) - sizeof($config_datafields);
            $deleted = array();
            foreach ($diff_generic_datafields as $datafield) {
                $deleted[] = $offset + $swapped_datafields[$datafield];
                unset($visible[$offset + $swapped_datafields[$datafield]]);
                unset($swapped_order[$offset + $swapped_datafields[$datafield]]);
                unset($aliases[$offset + $swapped_datafields[$datafield]]);
            }
            $visible = array_condense($visible);
            $order = array_condense(array_flip($swapped_order));
            $aliases = array_condense($aliases);
            
            $config_generic_datafields = array_diff($config_datafields,
                    $diff_generic_datafields);
            for ($i = 0; $i < sizeof($order); $i++) {
                foreach ($deleted as $position) {
                    if ($order[$i] >= $position)
                        $order[$i]--;
                }
            }
            $store = TRUE;
        }
        
        if (!$config_generic_datafields)
            $config_generic_datafields = $config_datafields;
        // data fields added
        if ($diff_generic_datafields = array_diff($generic_datafields["ids"],
                $config_generic_datafields)) {
            $config_generic_datafields = array_merge((array)$config_generic_datafields,
                    (array)$diff_generic_datafields);
            foreach ($diff_generic_datafields as $datafield) {
                $visible[] = "0";
                $order[] = sizeof($order);
                $aliases[] = $generic_datafields["ids_names"][$datafield];
            }
            $store = TRUE;
        }

        if ($store) {
            $config->setValue("Main", "visible", $visible);
            $config->setValue("Main", "order", $order);
            $config->setValue("Main", "aliases", $aliases);
            $config->setValue("Main", "genericdatafields", $config_generic_datafields);
            $config->store();
        }

        $config_generic_datafields = $config->getValue("Main", "genericdatafields");
        foreach ($config_generic_datafields as $datafield) {
            $field_names[] = $generic_datafields["ids_names"][$datafield];
        }

        if ($store)
            return TRUE;

        return FALSE;
    }

    return FALSE;
}

function get_default_generic_datafields (&$default_config, $object_type) {
    // extend $default_config if generic data fields exist
    if ($generic_datafields = get_generic_datafields($object_type)) {
        foreach ($generic_datafields["ids"] as $datafield) {
            $default_config["genericdatafields"] .= "|" . $datafield;
            $default_config["visible"] .= "|0";
            $default_config["order"] .= "|" . substr_count($default_config["order"], "|");
            $default_config["aliases"] .= "|" . $generic_datafields["ids_names"][$datafield];
        }

        return TRUE;
    }

    return FALSE;
}

function enable_sri ($i_id, $enable) {

    if ($enable) {
        $query = "UPDATE Institute SET srienabled = 1 WHERE Institut_id = ?";
    } else {
        $query = "UPDATE Institute SET srienabled = 0 WHERE Institut_id = ?";
    }
     $statement = DBManager::get()->prepare($query);
     $statement->execute(array( $i_id ));

}

function sri_is_enabled ($i_id) {
    if ($GLOBALS['EXTERN_SRI_ENABLE']) {
        if (!$GLOBALS['EXTERN_SRI_ENABLE_BY_ROOT']) {
            return 1;
        }
        $query = "SELECT srienabled FROM Institute WHERE Institut_id = ? AND srienabled = 1";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array( $i_id ));
        $row = $statement->fetchColumn();
        if ($row) {
            return 1;
        }
        
    }
    return 0;
}

/*
 * Download an external configuration.
 *
 * @param string $range_id the range_id
 * @param string $config_id the id of the config to download
 * @param string $module the config-type
 */
function download_config($range_id, $config_id, $module) {
    $extern = new ExternConfigDB($range_id, '',$config_id);
    
    // check, if we have an external configuration with the given ids
    $stmt = DBManager::get()->prepare("SELECT COUNT(*) as c FROM extern_config 
        WHERE config_id = ? AND range_id = ?");
    $stmt->execute(array($config_id, $range_id));
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // show download-content
    if ($result['c'] == 1) {
        $file_name = $GLOBALS["EXTERN_CONFIG_FILE_PATH"] . $config_id . ".cfg";
        header("Content-Type: text/plain");
        header("Content-Disposition: attachment; filename=$config_id.cfg");
        $extern->parse();
        $extern->config['config_type'] = $module;
        
        // utf8-encode all values
        $config = $extern->config;
        array_walk_recursive($config, 'utf8Encode');

        // create json, working only with utf8-encoded data
        $extern_attributes = json_encode($config);

        echo indentJson($extern_attributes);

    }

    return TRUE;
}

/*
 * Store an (uploaded) external configuration.
 *
 * @param string $range_id the range_id
 * @param string $config_id the id of the config to overwrite
 * @param string $jsonconfig the json-ified configuration
 *
 * @return boolean returns true on success, false otherwise
 */
function store_config($range_id, $config_id, $jsonconfig)
{
    $extern = new ExternConfigDB($range_id, '', $config_id);
    $extern->config = $jsonconfig;
    return ($extern->store()) ? true : false;
}

/*
 * Some checks trying to validate someone is uploadin the correct type of config.
 *
 * @param string $data the content of the new (uploaded) config
 * @param string $type the type it should have
 *
 * @return boolean true if the types match
 */
function check_config($data, $type) {
    if ($data['config_type'] == $type) {
        return true;
    } else {
        return false;
    }
}

/*
 * Create correct indention for json-string.
 *
 * @param string $str the json to indent
 *
 * @return string teh indented json
 */
function indentJson($str) {
    $strOut = '';
    $identPos = 0;
    for($loop = 0;$loop<= strlen($str) ;$loop++){
        $_char = substr($str,$loop,1);
        //part 1
        if($_char == '}' || $_char == ']'){
            $strOut .= "\n";
            $identPos --;
            for($ident = 0;$ident < $identPos;$ident++){
                $strOut .= "\t";
            }
        }
        //part 2
        $strOut .= $_char;
        //part 3
        if($_char == ',' || $_char == '{' || $_char == '['){
            $strOut .= "\n";
            if($_char == '{' || $_char == '[')
                $identPos ++;
            for($ident = 0;$ident < $identPos;$ident++){
                $strOut .= "\t";
            }
        }
    }
    return $strOut;
}

/**
 * for use with functions like array_walk. utf8_encode's the passed parameters
 *
 * @param  mixed  $value  the array-value
 * @param  mixed  $key    the array-key
 */
function utf8Encode(&$value, &$key) {
    $value = utf8_encode($value);
    $key   = utf8_encode($key);
}

/**
 * for use with functions like array_walk. utf8_decode's the passed parameters
 * 
 * @param  mixed  $value  the array-value
 * @param  mixed  $key    the array-key
 */
function utf8Decode(&$value, &$key) {
    $value = utf8_decode($value);
    $key   = utf8_decode($key);
}
