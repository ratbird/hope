<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// StudipLitCatElement.class.php
// Class to build search formular and execute search
//
// Copyright (c) 2003 Andr� Noack <noack@data-quest.de>
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

require_once("lib/classes/StudipLitList.class.php");

DbView::addView('literatur');

/**
*
*
*
*
* @access   public
* @author   Andr� Noack <noack@data-quest.de>
* @package
**/
class StudipLitCatElement {

    var $fields = array();
    var $dbv;
    var $form_obj = null;
    var $form_name = "lit_element_form";
    var $init_form;
    var $classname = "StudipLitCatElement";
    var $persistent_slots = array("fields");

    function StudipLitCatElement($catalog_id = false, $with_form = false){
        $this->dbv = new DbView();
        $this->init_form = $with_form;
        $this->initFields();
        if ($catalog_id){
            $this->getElementData($catalog_id);
        }
    }

    function initFields(){
        $this->fields = array(
                        'dc_title'  =>  array(  'caption'   => _("Titel"),
                                                'info'  => _("Titel der Quelle; der vom Verfasser, Urheber oder Verleger vergebene Namen der Ressource"),
                                                'len'   => 255,
                                                'type'  => 'text',
                                                'mandatory' => true),
                        'dc_creator'=>  array(  'caption'   => _("Verfasser oder Urheber"),
                                                'info'  => _("Die Person(en) oder Organisation(en), die den intellektuellen Inhalt verantworten. Z.B. Autoren bei Textdokumenten; K�nstler, Photographen bzw. auch andere Bezeichnungen wie Komponist und Maler bei graphischen Dokumenten."),
                                                'len'   => 255,
                                                'type'  => 'text',
                                                'mandatory' => true),
                        'dc_subject'=>  array(  'caption'   => _("Thema und Stichw�rter"),
                                                'info'  => _("Thema, Schlagwort, Stichwort. Das Thema der Ressource bzw. Stichw�rter oder Phrasen, die das Thema oder den Inhalt beschreiben"),
                                                'len'   => 255,
                                                'type'  => 'text'),
                        'dc_description'=>array('caption'   => _("Inhaltliche Beschreibung"),
                                                'info'  => _("Kurze Inhaltswiedergabe, Beschreibung, Abstract"),
                                                'len'   => 65535,
                                                'type'  => 'text'),
                        'dc_publisher'=>array(  'caption'=> _("Verleger, Herausgeber"),
                                                'info'  => _("Die Einrichtung, die verantwortet, da� diese Ressource in dieser Form zur Verf�gung steht, wie z.B. ein Verleger, ein Herausgeber, eine Universit�t oder eine korporatives Unternehmen."),
                                                'len'   => 255,
                                                'type'  => 'text'),
                        'dc_contributor'=>array('caption'=> _("Weitere beteiligten Personen und K�rperschaften"),
                                                'info'  => _("Sonstige an der Erstellung und Ver�ffentlichung der Ressource beteiligte Personen oder Organisationen"),
                                                'len'   => 255,
                                                'type'  => 'text'),
                       'dc_date' => array( 'caption'=> _("Datum"),
                                                'info'  => _("Das Datum, an dem die Ressource in der gegenw�rtigen Form zug�nglich gemacht wurde."),
                                                'len'   => 11,
                                                'type'  => 'datepicker',
                                                'mandatory' => true),
                        'dc_type'   =>  array(  'caption'=> _("Ressourcenart"),
                                                'info'  => _("Die Art der Ressource, z.B. Homepage, Roman, Gedicht, Arbeitsbericht, technischer Bericht, Essay, W�rterbuch\n(Die Vorgaben entsprechen den EndNote Referenz Typen)"),
                                                'len'   => 255,
                                                'type'  => 'text',
                                                'select_list' => array('','Book','Manuscript','Magazine Article','Conference Proceedings','Report','Computer Program','Audiovisual Material','Artwork','Patent','Bill','Case','Journal Article','Book Section','Edited Book','Newspaper Article','Thesis','Personal Communication','Electronic Source','Film or Broadcast','Map','Hearing','Statute')),
                        'dc_format' =>  array(  'caption'=> _("Format"),
                                                'info'  => _("Hier wird das datentechnische Format der Ressource eingetragen, z.B. Text/HTML, ASCII, Postscript-Datei, ausf�hrbare Anwendung, JPEG-Bilddatei etc. Grunds�tzlich k�nnen Formate auch physische Medieneinheiten wie B�cher, Zeitschriften oder andere nichtelektronische Medien mit einschlie�en."),
                                                'len'   => 255,
                                                'type'  => 'text',
                                                'select_list' => array('','text/html','application/pdf')),
                        'dc_identifier' =>array('caption'=> _("Ressourcen-Identifikation"),
                                                'info'  => _("Weltweit eindeutige Kennzeichnung, z.B. URL oder ISBN"),
                                                'len'   => 255,
                                                'type'  => 'text'),
                        'dc_source' =>  array(  'caption'=> _("Quelle"),
                                                'info'  => _("Bei nichtorigin�ren Beitr�gen die Quelle bzw. das Original"),
                                                'len'   => 255,
                                                'type'  => 'text'),
                        'dc_language'=> array(  'caption'=> _("Sprache"),
                                                'info'  => _("Hier wird die Sprache des intellektuellen Inhalts dieser Ressource vermerkt."),
                                                'len'   => 255,
                                                'type'  => 'text',
                                                'select_list'=> array('','ger','eng','fre','ita','spa')),
                        'dc_relation'=> array(  'caption'=> _("Beziehung zu anderen Ressourcen"),
                                                'info'  => _("Die Angabe in diesem Feld erm�glicht es, Verbindungen unter verschiedenen Ressourcen darzustellen, die einen formalen Bezug zu anderen Ressourcen haben, aber als eigenst�ndige Ressourcen existieren."),
                                                'len'   => 255,
                                                'type'  => 'text'),
                        'dc_coverage'=> array(  'caption'=> _("R�umliche und zeitliche Ma�angaben"),
                                                'info'  => _("Hier werden Angaben zur r�umlichen Bestimmung (z.B. geographische Koordinaten) und zeitlichen G�ltigkeit eingetragen, die die Ressource charakterisieren."),
                                                'len'   => 255,
                                                'type'  => 'text'),
                        'dc_rights' =>  array(  'caption'=> _("Rechtliche Bedingungen"),
                                                'info'  => _("Verweis auf die Nutzungsbedingungen entsprechend dem Urheberrecht"),
                                                'len'   => 255,
                                                'type'  => 'text'),
                        'accession_number'=>array('caption' => _("Zugriffsnummer"),
                                                'info'  => _("Die Zugriffsnummer eines Bibliothekssystems, �ber die diese Quelle identifiziert werden kann. z.B. PICA Prod Nummer oder die Signatur einer Bibliothek."),
                                                'len'   => 100,
                                                'type'  => 'text'),
                        'lit_plugin'=>  array(  'caption'   => _("Verweis auf externes Bibliothekssystem"),
                                                'info'  => _("Der Name des externen Bibliothekssystems, in das �ber einen Weblink verzweigt werden kann."),
                                                'len'   => 100,
                                                'type'  => 'select',
                                                'options'=> StudipLitSearch::GetAvailablePluginsOptions()),
                        'catalog_id' => array(  'type'  => 'text'),
                        'user_id'   =>  array(  'type'  =>  'text'),
                        'mkdate'    =>  array(  'type'  =>  'int'),
                        'chdate'    =>  array(  'type'  =>  'int'),
                        );
    }

    function getElementData($catalog_id = false){
        if (!$catalog_id){
            $catalog_id = $this->fields['catalog_id']['value'];
        }
        if ($catalog_id != 'new_entry'){
            $this->dbv->params[0] = $catalog_id;
            $rs = $this->dbv->get_query("view:LIT_GET_ELEMENT");
            if ($rs->next_record()){
                foreach ($this->fields as $field_name => $field_detail){
                    $this->fields[$field_name]['value'] = $rs->f($field_name);
                }
                $this->dbv->params[0] = $catalog_id;
                $rs = $this->dbv->get_query("view:LIT_GET_REFERENCE_COUNT");
                $rs->next_record();
                $this->reference_count = $rs->f("anzahl");
            } else {
                $catalog_id = "new_entry";
            }
        }
        if ($catalog_id == "new_entry"){
            $this->fields['catalog_id']['value'] = "new_entry";
            $this->fields['mkdate']['value'] = time();
            $this->fields['chdate']['value'] = time();
            $this->fields['user_id']['value'] = $GLOBALS['auth']->auth['uid'];
            $this->fields['lit_plugin']['value'] = 'Studip';
            $this->reference_count = 0;
        }
        if ($this->init_form){
            $this->setFormObject();
        }
        return ($catalog_id != "new_entry");
    }

    function &getFormObject(){
        if (!is_object($this->form_obj)){
            $this->setFormObject();    
        }
        return $this->form_obj;
        
    }

    function setFormObject(){
        $form_fields = array();
        $form_name = $this->form_name;
        if($this->isNewEntry()){
            $this->fields['default_lit_list'] = array('caption' => _("Eintrag in diese Literaturliste"),
                                                'info'  => _("W�hlen Sie hier eine pers�nliche Literaturliste aus, in die der neue Eintrag aufgenommen werden soll."),
                                                'len'   => 255,
                                                'type'  => 'select',
                                                'options'=> array_merge(array('---'), (array)StudipLitList::GetListsByRange($GLOBALS['user']->id, 'form_options')));
        }
        foreach ($this->fields as $field_name => $field_detail){
            
            if ($field_detail['caption']){
                if ($field_detail['select_list']){
                    $form_fields[$field_name . "_select"] = array('type' => 'select','options' => $field_detail['select_list']);
                    $form_fields[$field_name . "_text"] = array('type' => 'text');
                    $form_fields[$field_name] = array('type' => 'combo', 'text' => $field_name . "_text", 'select' => $field_name . "_select", 'separator' => '&nbsp;');
                } else {
                    $form_fields[$field_name]['type'] = $field_detail['type'];
                    if ($field_detail['type'] == 'text' && $field_detail['len'] > 100){
                        $form_fields[$field_name]['type'] = 'textarea';
                    }
                    if ($field_detail['type'] == 'select'){
                        $form_fields[$field_name]['options'] = $field_detail['options'];
                    }
                }
                $form_fields[$field_name]['caption'] = $field_detail['caption'];
                $form_fields[$field_name]['info'] = $field_detail['info'];
                $form_fields[$field_name]['default_value'] = $field_detail['value'];
            }
        }
        $form_fields['catalog_id'] = array('type' => 'hidden', 'default_value' => $this->fields['catalog_id']['value']);
        $form_buttons = array('send' => array('type' => 'accept', 'caption' => _('speichern'), 'info' => _("�nderungen speichern")),
                            'reset' => array('caption' => _('zur�cksetzen'), 'info' => _("�nderungen zur�cksetzen")),
                            'delete' => array('caption' => _('l�schen'), 'info' => _("Eintrag l�schen"))
                    );
        if (!is_object($this->form_obj)){
            $this->form_obj = new StudipForm($form_fields, $form_buttons, $form_name);
        } else {
            $this->form_obj->form_fields = $form_fields;
        }
        if ($this->form_obj->getFormFieldValue("catalog_id") != $this->getValue("catalog_id")){
            $this->form_obj->doFormReset();
        }
        return true;
    }

    function setValuesFromForm(){
        $this->getFormObject();
        if (is_array($this->form_obj->form_values)){
            foreach($this->form_obj->form_values as $name => $value){
                if ($this->fields[$name] && $value['type'] != 'hidden'){
                    $this->fields[$name]['value'] = $value;
                }
            }
        }
    }

    function getValues(){
        $ret = array();
        foreach ($this->fields as $name => $value){
            $ret[$name] = $value['value'];
        }
        return $ret;
    }

    function setValues($fields){
        if (is_array($fields)){
            foreach ($fields as $name => $value){
                
                if ($this->fields[$name]) $this->fields[$name]['value'] = $value;
            }
            return true;
        } else {
            return false;
        }
    }

    function insertData(){
        if ($this->isNewEntry()){
            $this->fields['catalog_id']['value'] = md5(uniqid("litblablubb",1));
            $this->fields['chdate']['value'] = $this->fields['mkdate']['value'] = time();
            $default_list_entry = $this->fields['default_lit_list']['value'];
            unset($this->fields['default_lit_list']);
            foreach($this->fields as $name => $detail){
                $field_names[] = $name;
                $field_values[] = mysql_escape_string(trim($detail['value']));
            }
            $sql = "INSERT INTO lit_catalog(" . join(",", $field_names) . ") VALUES ('" . join("','", $field_values) . "')";
            $msg = "msg�" . _("Ein neuer Datensatz wurde eingef�gt.") . "�";
        } else {
            $this->fields['chdate']['value'] = time();
            foreach($this->fields as $name => $detail){
                $field_upd[] = $name . "='" . mysql_escape_string(trim($detail['value'])) . "'";
            }
            $sql = "UPDATE lit_catalog SET " . join(",", $field_upd) . " WHERE catalog_id='" . $this->fields['catalog_id']['value'] . "'";
            $msg = "msg�" . _("Die ge�nderten Daten wurden gespeichert.") ."�";
        }
        $rs = $this->dbv->get_query($sql);
        if ($this->init_form){
            $this->form_obj->doFormReset();
        }
        $this->getElementData();
        if ($rs->affected_rows()){
            if($default_list_entry && $default_list_entry != '---'){
                $list = TreeAbstract::GetInstance("StudipLitList", $GLOBALS['user']->id);
                $list->insertElement(array('catalog_id' => $this->getValue('catalog_id'), 'list_id' => $default_list_entry,
                                            'list_element_id' => $list->getNewListElementId(),
                                            'user_id' => $GLOBALS['user']->id,
                                            'note' => '', 'priority' => ($list->getMaxPriority($default_list_entry) + 1) ));
            }
            $this->msg .= $msg;
        }
        return $rs->affected_rows();
    }

    function deleteElement(){
        $this->dbv->params[0] = $this->getValue("catalog_id");
        $rs = $this->dbv->get_query("view:LIT_DEL_ELEMENT");
        if ($rs->affected_rows()){
            $this->msg = "msg�" . _("Der Datensatz wurde gel&ouml;scht.") . "�";
            $this->initFields();
            $this->getElementData("new_entry");
            return true;
        } else {
            $this->msg = "error�" . _("Der Datensatz konnte nicht gel&ouml;scht werden") . "�";
            return false;
        }
    }

    function checkElement(){
        if ($this->getValue('user_id') == 'studip' && $this->getValue('accession_number')){
            $this->dbv->params[0] = $this->getValue('accession_number');
            $rs = $this->dbv->get_query("view:LIT_CHECK_ELEMENT");
            if ($rs->next_record()){
                return $rs->f('catalog_id');
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    function checkValues(){
        $missing_fields = false;
        foreach($this->fields as $name => $detail){
            
            if ($detail['mandatory']){
                if ($detail['type'] == 'date' || $detail['type'] == 'datepicker'){
                    $this->setValue($name, $this->checkDate($detail['value']));
                }
                if (!$this->getValue($name)){
                    $missing_fields[] = $detail['caption'];
                }
            }
        }
        if (is_array($missing_fields)){
            $this->msg .= "error�" . sprintf(_("Bitte f&uuml;llen Sie folgende Felder aus: %s"), "\"" . join("\", \"",$missing_fields)) ."\"�";
        }
        return is_array($missing_fields) ? false : true;
    }

    function checkDate($date){
        $date =  explode('-', $date);
        $date[1] = ($date[1] < 1 || $date[1] > 12) ? 1 : $date[1];
        $date[2] = ($date[2] < 1 || $date[2] > 31) ? 1 : $date[2];
        if ($date[0] < 1 || $date[0] > 3000){
            return false;
        } else {
            return join('-' , $date);
        }
    }

    function getValue($name){
        if ($this->fields[$name]){
            return trim($this->fields[$name]['value']);
        } else {
            switch ($name){
                case "year":
                $year = explode("-", $this->getValue("dc_date"));
                $ret = $year[0];
                break;
                case "authors":
                if ( ($ret = $this->getValue("dc_contributor")) ){
                    $ret = "; " . $ret;
                }
                $ret = $this->getValue("dc_creator") . $ret;
                break;
                case "published":
                $ret = $this->getValue("dc_publisher") . ", " . $this->getValue("year");
                break;
                case "external_link":
                $plugin_name = $this->getValue("lit_plugin");
                $link = StudipLitSearch::GetExternalLink($plugin_name);
                if ($link){
                    $ret = preg_replace('/({[a-z0-9_]+})/e', "\$this->getValue(substr('\\1',1,strlen('\\1')-2))", $link);
                    if ($ret == preg_replace('/({[a-z0-9_]+})/', "", $link)) {
                        $ret = "";
                    }
                }
                break;
                case "lit_plugin_display_name":
                $plugin_name = $this->getValue("lit_plugin");
                $ret = StudipLitSearch::GetPluginDisplayName($plugin_name);
                break;
                default:
                $ret = "unknown tag: $name";
            }
        return $ret;
        }
    }

    function setValue($name, $value){
        if (isset($this->fields[$name])){
            $this->fields[$name]['value'] = $value;
            return true;
        } else {
            return false;
        }
    }

    function isChangeable(){
        return ($GLOBALS['auth']->auth['uid'] == $this->getValue("user_id") || $GLOBALS['auth']->auth['perm'] == "root");
    }

    function isNewEntry(){
        return (!$this->fields['catalog_id']['value'] || $this->fields['catalog_id']['value'] == 'new_entry');
    }

    function getShortName(){
        $autor = preg_split ("/[\s,]+/", $this->getValue("dc_creator"),-1,PREG_SPLIT_NO_EMPTY);
        $autor = $autor[0];
        $year = explode("-", $this->getValue("dc_date"));
        $year = $year[0];
        return $autor . "(" . $year . ")-" . $this->getValue("dc_title");
    }

    function CloneElement($catalog_id){
        $clone = new StudipLitCatElement($catalog_id);
        $clone->getElementData('new_entry');
        $clone->insertData();
        return ($clone->getValue('catalog_id') == $catalog_id ? false : $clone->getValue('catalog_id'));
    }

    function &GetClonedElement($catalog_id){
        $clone = new StudipLitCatElement($catalog_id);
        $clone->getElementData('new_entry');
        return $clone;
    }
}
?>
