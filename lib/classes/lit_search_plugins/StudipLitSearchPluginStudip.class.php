<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// StudipLitSearchPluginAbstract.class.php
// 
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

require_once ("lib/classes/StudipLitCatElement.class.php");
require_once ("lib/classes/lit_search_plugins/StudipLitSearchPluginAbstract.class.php");

/**
*
*
* 
*
* @access   public  
* @author   Andr� Noack <noack@data-quest.de>
* @package  
**/
class StudipLitSearchPluginStudip extends StudipLitSearchPluginAbstract{
    
    
    function StudipLitSearchPluginStudip(){
        parent::StudipLitSearchPluginAbstract();
        $this->dbv = new DbView();
        $rs = $this->dbv->get_query("view:LIT_GET_CATALOG_COUNT");
        $rs->next_record();
        $this->description = sprintf(_("Stud.IP Literaturkatalog. Inhalt des Kataloges: %s Eintr�ge."), $rs->f(0));
    }
    
    function doSearch($search_values){
        $this->search_values = $search_values;
        if ( !($sql = $this->parseSearchValues()) ){
            return false;
        }
        $this->dbv->params[] = $sql;
        $rs = $this->dbv->get_query("view:LIT_SEARCH_CATALOG");
        $this->search_result = array();
        while ($rs->next_record()){
            $this->search_result[] = $rs->f('catalog_id');
        }
        return $rs->num_rows();
    }
    
    function parseSearchValues(){
        $sql = '1';
        $search_values = $this->search_values;
        if (is_array($search_values)){
            for ($i = 0 ; $i < count($search_values); ++$i){
                $term = mysql_escape_string($search_values[$i]['search_term']);
                if (strlen($term)){
                    if ($search_values[$i]['search_truncate'] == "left"){
                        $term = $term . "";
                    } else if ($search_values[$i]['search_truncate'] == "right"){
                        $term = $term . '*';
                    } else {
                        $term = $term;
                    }
                    $field = $search_values[$i]['search_field'];
                    $operator = 'AND';
                    if ($i > 0){
                        $operator = $search_values[$i]['search_operator'];
                        if ($operator == "NOT"){
                            $operator = "AND NOT";
                        }
                    } 
                    $sql .= " $operator MATCH(" . $field . ") AGAINST ('" . $term . "' IN BOOLEAN MODE)  ";
                } else if ($i == 0) {
                    $this->addError("error", _("Der erste Suchbegriff fehlt."));
                    return false;
                }
            }
        }
        return $sql;
    }
    
    function getSearchFields(){
        return array(array('name' => _("Titel,Autor,Schlagwort"), 'value' => "dc_title,dc_creator,dc_contributor,dc_subject"),
                    array('name' => _("Titel"), 'value' => "dc_title"),
                    array('name' => _("Autor"), 'value' => "dc_creator,dc_contributor"),
                    array('name' => _("Schlagwort"), 'value' => "dc_subject"),
                    array('name' => _("Inhalt"), 'value' => "dc_description"),
                    array('name' => _("Verlagsort, Verlag"), 'value' => "dc_publisher"),
                    array('name' => _("Identifikation"), 'value' => "dc_identifier")
                );
        
    }
    
    function getSearchResult($num_hit){
        if (!isset($this->search_result[$num_hit-1])){
            $this->addError("error",_("Suchergebnis existiert nicht."));
            return false;
        } else {
            $cat_element = new StudipLitCatElement($this->search_result[$num_hit-1]);
            if ($cat_element->isNewEntry()){
                array_splice($this->search_result, $num_hit-1,1);
                return false;
            } else {
                return $cat_element;
            }
        }
    }
}
?>
