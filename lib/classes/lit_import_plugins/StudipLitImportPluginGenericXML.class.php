<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter010: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// StudipLitImportPluginGenericXML.class.php
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

require_once ("lib/classes/lit_import_plugins/StudipLitImportPluginAbstract.class.php");
if (version_compare(PHP_VERSION,'5','>=')) require_once('vendor/php4-to-php5/domxml-php4-to-php5.php');

/**
*
*
* 
*
* @access   public  
* @author   Jan Kulmann <jankul@zmml.uni-bremen.de> 
* @package  
**/
class StudipLitImportPluginGenericXML extends StudipLitImportPluginAbstract {
    
    function StudipLitImportPluginGenericXML(){
        // immer erst den parent-contructor aufrufen!
        parent::StudipLitImportPluginAbstract();
    }
    
    function parse($data){
        $domTree = @domxml_open_mem($data);
        if (!is_object($domTree)) {
            $this->addError("error","Error 5: while parsing the document");
            return FALSE;
        }
        return $domTree;
    }
    
    function import($domTree) {
        global $auth, $_msg;
        $msg = &$_msg;
        if ($domTree) {
            $records = $domTree->get_elements_by_tagname("eintrag");
            if (count($records)==0) $records = $domTree->get_elements_by_tagname("EINTRAG");
            
            $fields_arr = array();
            
            foreach ($records as $record) {
                $fields = array();
                $fields["catalog_id"] = "new_entry";
                $fields["user_id"] = $auth->auth["uid"];
                $dates = "";
                
                $child = $record;
                
                $titles = $child->get_elements_by_tagname("titel");
                if (count($titles)==0) $titles = $child->get_elements_by_tagname("TITEL");
                foreach ($titles as $t)
                    $fields["dc_title"] .= $t->get_content().",";
                
                $authors = $child->get_elements_by_tagname("autor");
                if (count($authors)==0) $authors = $child->get_elements_by_tagname("AUTOR");
                foreach ($authors as $a)
                    $fields["dc_creator"] .= $a->get_content().";";
                
                $description = $child->get_elements_by_tagname("beschreibung");
                if (count($description)==0) $description = $child->get_elements_by_tagname("BESCHREIBUNG");
                foreach ($description as $d)
                    $fields["dc_subject"] .= $d->get_content().",";
                
                $publisher = $child->get_elements_by_tagname("herausgeber");
                if (count($publisher)==0) $publisher = $child->get_elements_by_tagname("HERAUSGEBER");
                foreach ($publisher as $p)
                    $fields["dc_publisher"] .= $p->get_content().",";
                
                $pub_loc = $child->get_elements_by_tagname("ort");
                if (count($pub_loc)==0) $pub_loc = $child->get_elements_by_tagname("ORT");
                foreach ($pub_loc as $p)
                    $fields["dc_publisher"] .= " ".$p->get_content().",";
                
                $isbn = $child->get_elements_by_tagname("isbn");
                if (count($isbn)==0) $isbn = $child->get_elements_by_tagname("ISBN");
                foreach ($isbn as $i)
                    $fields["dc_identifier"] .= " ISBN: ".$i->get_content().",";
                
                $years = $child->get_elements_by_tagname("jahr");
                if (count($years)==0) $years = $child->get_elements_by_tagname("JAHR");
                foreach ($years as $y) {
                    $fields["dc_date"] = $y->get_content()."-01-01";
                    $dates .= $y->get_content().",";
                }
                
                if ($fields["dc_identifier"]) $fields["dc_identifier"] = utf8_decode(substr($fields["dc_identifier"],0,-1));
                if ($fields["dc_publisher"]) $fields["dc_publisher"] = utf8_decode(substr($fields["dc_publisher"],0,-1));
                if ($fields["dc_title"]) $fields["dc_title"] = utf8_decode(substr($fields["dc_title"],0,-1));
                if ($fields["dc_creator"]) $fields["dc_creator"] = utf8_decode(substr($fields["dc_creator"],0,-1));
                if ($fields["dc_subject"]) $fields["dc_subject"] = utf8_decode(substr($fields["dc_subject"],0,-1));
                
                if (!trim($fields["dc_creator"])) $fields["dc_creator"] = "Unbekannt";
                if (!trim($fields["dc_title"])) $fields["dc_title"] = "";
                
                if ( $fields["dc_title"] != "") array_push($fields_arr, $fields);
                
            }
            
            return (count($fields_arr)>0 ? $fields_arr : FALSE);
        }
        
    }
}
?>
