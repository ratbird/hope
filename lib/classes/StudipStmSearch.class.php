<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// StudipStmSearch.class.php
// 
// Copyright (c) 2006 André Noack <noack@data-quest.de>
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

require_once("lib/classes/StudipStmInstancesTree.class.php");
require_once("lib/classes/StudipForm.class.php");
require_once("lib/visual.inc.php");
require_once("lib/functions.php");


/**
* Class to build search formular and execute search
*
* 
*
* @access   public  
* @author   André Noack <noack@data-quest.de>
* @package  DBTools
**/
class StudipStmSearch {
    
    var $db;
    
    var $search_result;
    
    var $form_name;
    
    var $num_sem;
    
    var $stm_tree;
    
    var $search_done = false;
    
    var $found_rows = false;
    
    var $search_button_clicked = false;
    
    var $new_search_button_clicked = false;
    
    var $attributes_default = array('style' => 'width:100%;');
    
    var $search_scopes = array();
    
    function StudipStmSearch($form_name = "search_stm", $auto_search = true){
        $this->db = new DB_Seminar();
        $this->form_name = $form_name;
        $this->search_fields = array(   'quick_search' => array('type' => 'text'),
                                        'scope_choose' => array('type' => 'select','default_value' => 'root','options_callback' => array($this,'getScopeChooseOptions') , 'size' => 45),
                                        'qs_choose' => array('type' => 'select', 'default_value' => 'all', 'options' => array(
                                                                                                            array('name' =>_("alles"),'value' => 'all'),
                                                                                                            array('name' =>_("Titel"),'value' => 'title'),
                                                                                                            array('name' =>_("Nummer"),'value' => 'id_number'),
                                                                                                            array('name' => _("Dozent"),'value' => 'doz_name')))
        );
        $this->search_buttons = array(  'do_search' => array('caption' => 'Suchen', 'info' => _("Suche starten")),
                                        'new_search' => array('caption' => 'Neue Suche', 'info' => _("Neue Suche starten"))
        );
        $this->form = new StudipForm($this->search_fields, $this->search_buttons, $form_name, false);
        $this->form->field_attributes_default = $this->attributes_default;
        if($this->form->isClicked("do_search") || $this->form->isSended()){
            $this->search_button_clicked = true;
            if ($auto_search){
                $this->doSearch();
                $this->search_done = true;
            }
        }
        
        if($this->form->isClicked("new_search")){
            $this->new_search_button_clicked = true;
        }
    }
    
    function getSearchField($name,$attributes = false,$default = false){
        return $this->form->getFormField($name,$attributes,$default);
    }
    
    function getScopeChooseOptions($caller, $name){
        $options = array();
        if(!is_object($this->stm_tree)){
            $this->stm_tree = TreeAbstract::GetInstance("StudipStmInstancesTree");
        }
        $options = array(array('name' => my_substr($this->stm_tree->root_name,0,$this->search_fields['scope_choose']['size']), 'value' => 'root'));
        for($i = 0; $i < count($this->search_scopes); ++$i){
            $options[] = array('name' => my_substr($this->stm_tree->tree_data[$this->search_scopes[$i]]['name'],0,$this->search_fields['scope_choose']['size']), 'value' => $this->search_scopes[$i]);
        }
        return $options;
    }
    
    function getFormStart($action = ""){
        return $this->form->getFormStart($action);
    }
    
    function getFormEnd(){
        return $this->form->getFormEnd();
    }
    
    function getHiddenField($name, $value = false){
        return $this->form->getHiddenField($name, $value); 
    }
    
    function getSearchButton($attributes = false, $tooltip = false){
        return $this->form->getFormButton('do_search', $attributes);
    }
    
    function getNewSearchButton($attributes = false, $tooltip = false){
        return $this->form->getFormButton('new_search', $attributes);
    }
    
    function doSearch(){
        $clause = "AND stm.complete=1 ";
        $and_clause = "";
        $this->search_result = new DbSnapshot();
        $combination = "AND";
        
        if ($quick_search = $this->form->getFormFieldValue("quick_search")){
            if (strlen($quick_search) < 2){
                return false;
            }
            if ($this->form->getFormFieldValue("qs_choose") == 'all'){
                foreach ($this->search_fields['qs_choose']['options'] as $opt){
                    if ($opt['value'] != 'all') ${$opt['value']} = $quick_search;
                }
                $combination = "OR";
            } else {
                ${$this->form->getFormFieldValue("qs_choose")} = $quick_search;
            }
        }
        
        if ($this->form->getFormFieldValue("scope_choose") && $this->form->getFormFieldValue("scope_choose") != 'root'){
            if(!is_object($this->stm_tree)){
                $this->stm_tree = TreeAbstract::GetInstance("StudipStmInstancesTree");
            }
            $stm_ids = $this->stm_tree->getStmIds($this->form->getFormFieldValue("scope_choose"),true);
            if (is_array($stm_ids)){
                $clause = " AND stm.stm_instance_id IN('" . join("','", $stm_ids) ."')" . $clause;
            } else {
                return true;
            }
        }
        
        if (isset($doz_name) && strlen($doz_name) > 2){
            $doz_name = "%".mysql_escape_string(trim($doz_name))."%";
            $sql = "SELECT DISTINCT stm.stm_instance_id FROM auth_user_md5 a 
                    INNER JOIN seminar_user b ON(a.user_id=b.user_id AND b.status='dozent') 
                    INNER JOIN stm_instances_elements stme ON sem_id=seminar_id 
                    INNER JOIN stm_instances stm ON stm.stm_instance_id = stme.stm_instance_id WHERE 
                     (a.username LIKE '$doz_name' OR a.Vorname LIKE '$doz_name' OR a.Nachname LIKE '$doz_name') $clause";
            $snap = new DbSnapshot(new DB_Seminar($sql));
            $this->search_result = $snap;
            $this->found_rows = $this->search_result->numRows;
        }

        
        if ($combination == "AND" && $this->search_result->numRows){
            $and_clause = " AND stm.stm_instance_id IN('" . join("','",$this->search_result->getRows("stm_instance_id")) ."')";
        }
        
        if ((isset($title) && strlen($title) > 2) ||
            (isset($id_number) && strlen($id_number) > 2) ){
                $sql = "SELECT DISTINCT stm.stm_instance_id FROM stm_instances stm
                        INNER JOIN stm_abstract ON stm.stm_abstr_id = stm_abstract.stm_abstr_id
                        INNER JOIN stm_instances_text ON stm.stm_instance_id =stm_instances_text.stm_instance_id AND stm_instances_text.lang_id='".LANGUAGE_ID."'
                        WHERE ";
                        
            $sql .= ($title) ? " title LIKE '%".mysql_escape_string(trim($title))."%' " : " ";
            $sql.= ($title && $id_number) ? $combination : " ";
            $sql .= ($id_number) ? " id_number LIKE '".mysql_escape_string(trim($id_number))."' " : " ";
            $sql .=  $and_clause . $clause;
            $snap = new DbSnapshot(new DB_Seminar($sql));
            if ($this->found_rows === false){
                $this->search_result = $snap;
            } else {
                $this->search_result->mergeSnapshot($snap,"stm_instance_id",$combination);
            }
            $this->found_rows = $this->search_result->numRows;
        }
        
        return;
    }
}
?>
