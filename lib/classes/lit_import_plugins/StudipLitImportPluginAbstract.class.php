<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// StudipLitImportPluginAbstract.class.php
//
//
// Copyright (c) 2006 Jan Kulmann <jankul@zmml.uni-bremen.de>
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

require_once ("lib/classes/StudipLitList.class.php");
require_once ("lib/classes/StudipLitCatElement.class.php");

/**
*
*
*
*
* @access   public
* @author   Jan Kulmann <jankul@zmml.uni-bremen.de>
* @package
**/
class StudipLitImportPluginAbstract {

    var $class_name;
    var $error_msg = array();
        var $data; // Data
    var $num_entries;
    var $xmlfile;
    var $xmlfile_size;
    var $xmlfile_name;

    function StudipLitImportPluginAbstract(){
        $this->class_name = strtolower(get_class($this));
        $this->data = FALSE;
        $this->num_entries = 0;
    }

    // Zum Starten der Plugins immer nur diese Methode aufrufen mit folgendem Kommando:
    // StudipLitImportPluginAbstract::use_lit_import_plugins($xmlfile, $xmlfile_size, $xmlfile_name, $plugin_name);
    function use_lit_import_plugins($xmlfile, $xmlfile_size, $xmlfile_name, $plugin_name = "EndNote", $range_id = false) {
        global $_msg, $LIT_IMPORT_PLUGINS;

        if ($plugin_name){
            foreach ($LIT_IMPORT_PLUGINS as $plugin) {
                if ($plugin["name"] == $plugin_name) {
                    require_once ("lib/classes/lit_import_plugins/StudipLitImportPlugin".$plugin["name"].".class.php");
                    $p = "StudipLitImportPlugin".$plugin["name"];
                    $object = new $p;
                    $object->xmlfile = $xmlfile;
                    $object->xmlfile_size = $xmlfile_size;
                    $object->xmlfile_name = $xmlfile_name;
                    $data = $object->upload_file();
                    $dom = $object->parse($data);
                    if ($dom) {
                        $fields_arr = $object->import($dom);
                        if ($fields_arr){
                            if(!$range_id){
                                $range_id = $GLOBALS['user']->id;
                            }
                            $object->importEntries($fields_arr, $range_id);
                        }
                    }
                    $msg = &$_msg;
                    $msg .= $object->getError("irgendwas");
                    break;
                }
            }
        }
    }

    // Kann bei Bedarf ueberschrieben werden
    // function upload_file($xmlfile, $xmlfile_size, $xmlfile_name) {
    function upload_file() {
        global $TMP_PATH, $auth;


                if (!$this->xmlfile_name) {
                        $this->addError("error","Error 1: " . _("Sie haben keine Datei zum Hochladen ausgewählt!"));
                        return FALSE;
                }
                if ($this->xmlfile_size == 0) {
                        $this->addError("error","Error 2: " . _("Sie haben eine leere Datei zum Hochladen ausgewählt!"));
                        return FALSE;
                }


                //na dann kopieren wir mal...
                $newfile = $TMP_PATH."/".$this->xmlfile_name;
                if(!@move_uploaded_file($this->xmlfile,$newfile)) {
                        @unlink($newfile);
                        $this->addError("error","Error 3: " . sprintf(_("Es ist ein Fehler beim Kopieren der Datei %s aufgetreten. Die Datei wurde nicht hochgeladen!"),$this->xmlfile));
                        return FALSE;
                } else {
                        // na dann lesen wir mal...
                        if (!($fp = fopen($newfile, "r"))) {
                                $this->addError("error","Error 4: "._("Importdatei konnte nicht geöffnet werden"));
                                @unlink($newfile);
                                return FALSE;
                        }

                        if($fp) {
                                 while (!feof($fp))
                                         $this->data .= fread($fp, 8192);
                        }
                        fclose($fp);
                        @unlink($newfile);

                        return $this->data;
                }

    }

    // Muss implementiert werden
    function parse(){
        return FALSE;
    }

    // Muss implementiert werden
    function import(){
        return FALSE;
    }

    // Sollte nicht ueberschrieben werden
    function importEntries($field_arr, $range_id){
        if (is_array($field_arr)) {
            $catalog_ids = array();
            foreach ($field_arr as $fields) {
                if ($fields["dc_title"]!="") {
                    $litCatElement = new StudipLitCatElement();
                    $litCatElement->setValues($fields);
                    if ($litCatElement->insertData() > 0 ) {
                        $cat_element_id = $litCatElement->fields['catalog_id']['value'];
                        array_push($catalog_ids, $cat_element_id);
                    }
                }
            }
            if (count($catalog_ids)>0) {
                $lit_list = TreeAbstract::GetInstance("StudipLitList", $range_id);
                $lit_list_id = md5(uniqid("sdlfhaldfhuizhsdhg",1));
                $fields = array();
                $fields["list_id"]  = $lit_list_id;
                $fields["name"]     = _("Neue importierte Liste vom")." ".strftime("%x %X");
                $fields["user_id"]  = $GLOBALS['user']->id;
                if ($lit_list->insertList($fields)) {
                    $num_elements = $lit_list->insertElementBulk($catalog_ids, $lit_list_id);
                    if ($num_elements > 0 ) {
                        $lit_list->init();
                        $this->num_entries = $num_elements;
                        $this->addError("msg",sprintf(_("Neue Liste mit %s neuen Element(en) erzeugt"),$num_elements));
                        return TRUE;
                    } else {
                        $this->addError("error",_("Konnte keine Elemente anlegen"));
                        return FALSE;
                    }
                } else {
                    $this->addError("error",_("Konnte Liste nicht erzeugen"));
                    return FALSE;
                }
            } else {
                $this->addError("error",_("Keine Listeneintr&auml;ge gefunden"));
                return FALSE;
            }
        }
        return FALSE;
    }

    function getNumEntries(){
        return $this->num_entries;
    }

    function getError($format = "clear"){
        if ($format == "clear"){
            return $this->error_msg;
        } else {
            for ($i = 0; $i < count($this->error_msg); ++$i){
                $ret .= $this->error_msg[$i]['type'] . "§" . htmlReady($this->error_msg[$i]['msg']) . "§";
            }
            return $ret;
        }
    }

    function getNumError(){
        return count($this->error_msg);
    }

    function addError($type, $msg){
        $this->error_msg[] = array('type' => $type, 'msg' => $msg);
        return true;
    }

    function getPluginName(){
        global $LIT_IMPORT_PLUGINS;
        $ret = false;
        for ($i = 0; $i < count($LIT_IMPORT_PLUGINS); ++$i){
            if (substr(strtolower($this->class_name),21) == strtolower($LIT_IMPORT_PLUGINS[$i]['name'])){
                $ret = $LIT_IMPORT_PLUGINS[$i]['name'];
                break;
            }
        }
        return $ret;
    }
}
?>
