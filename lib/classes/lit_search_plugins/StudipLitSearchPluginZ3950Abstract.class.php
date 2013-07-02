<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// StudipLitSearchPluginZ3950Abstract.class.php
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
class StudipLitSearchPluginZ3950Abstract extends StudipLitSearchPluginAbstract{

    var $z_host;
    var $z_options = array(); // ('user' => 'dummy', 'password' => 'bla', 'persistent' => true, 'piggyback' => true);
    var $z_id;
    var $z_syntax;
    var $z_start_range = 1;
    var $z_hits = 0;
    var $z_profile = array(); // [attribute] => [name]
    var $z_timeout = 10;
    var $convert_umlaute = false;
    var $z_accession_bib = "";
    var $z_accession_re = false; // RegEx to check for valid accession number
    var $z_record_encoding = 'latin1';
    var $z_sort = '';

    function StudipLitSearchPluginZ3950Abstract(){
        parent::StudipLitSearchPluginAbstract();
        $this->z_hits =& $this->search_result['z_hits'];
        // UNIMARC mapping
        $this->mapping['UNIMARC'] = array('001' => array('field' => 'accession_number', 'callback' => 'simpleMap', 'cb_args' => ''),
                                '010' => array('field' => 'dc_identifier', 'callback' => 'simpleMap', 'cb_args' => 'ISBN: $a'),
                                '010' => array('field' => 'dc_identifier', 'callback' => 'simpleMap', 'cb_args' => 'ISSN: $a'),
                                '101' => array('field' => 'dc_language', 'callback' => 'simpleMap', 'cb_args' => '$a'),
                                '200' => array('field' => 'dc_title', 'callback' => 'simpleMap', 'cb_args' => '$a $e' . chr(10) . '$f'),
                                '210' => array( array('field' => 'dc_date', 'callback' => 'simpleMap', 'cb_args' => '$d-01-01'),
                                                array('field' => 'dc_publisher', 'callback' => 'simpleMap', 'cb_args' => '$c, $a')),
                                '215' => array('field' => 'dc_format', 'callback' => 'simpleMap', 'cb_args' => '$a, $c'),
                                '225' => array('field' => 'dc_relation', 'callback' => 'simpleMap', 'cb_args' => '$a, $v'),
                                '300' => array('field' => 'dc_description', 'callback' => 'simpleMap', 'cb_args' => 'Abstract: $a' . chr(10)),
                                '328' => array('field' => 'dc_description', 'callback' => 'simpleMap', 'cb_args' => 'Dissertation note:$a' . chr(10)),
                                '463' => array('field' => 'dc_publisher', 'callback' => 'simpleMap', 'cb_args' => '$t, $v'),
                                '606' => array('field' => 'dc_subject', 'callback' => 'simpleMap', 'cb_args' => ' $a '),
                                '700' => array('field' => 'dc_creator', 'callback' => 'simpleMap', 'cb_args' => '$a, $b'),
                                '701' => array('field' => 'dc_creator', 'callback' => 'notEmptyMap', 'cb_args' => array('$a, $b','dc_contributor','$a, $b;')),
                                '702' => array('field' => 'dc_creator', 'callback' => 'notEmptyMap', 'cb_args' => array('$a, $b','dc_contributor','$a, $b;')),
                                '710' => array('field' => 'dc_creator', 'callback' => 'simpleMap', 'cb_args' => '$a, $b'),
                                '711' => array('field' => 'dc_creator', 'callback' => 'notEmptyMap', 'cb_args' => array('$a, $b','dc_contributor','$a, $b;')),
                                '712' => array('field' => 'dc_creator', 'callback' => 'notEmptyMap', 'cb_args' => array('$a, $b','dc_contributor','$a, $b;')),
                                '856' => array('field' => 'dc_identifier', 'callback' => 'simpleMap', 'cb_args' => 'URL: $u '),
                                );

        //MARC mapping
        $this->mapping['USMARC'] = array('001' => array('field' => 'accession_number', 'callback' => 'simpleMap', 'cb_args' => ''),
                                        '008' => array( array('field' => 'dc_language', 'callback' => 'simpleFixFieldMap', 'cb_args' => array('start'=>34,'length'=>3)),
                                                array('field' => 'dc_date', 'callback' => 'simpleFixFieldMap', 'cb_args' => array('start'=>7,'length'=>4,'template'=>'{result}-01-01'))),
                                        '020' => array('field' => 'dc_identifier', 'callback' => 'simpleMap', 'cb_args' => 'ISBN: $a'),
                                        '245' => array('field' => 'dc_title', 'callback' => 'simpleMap', 'cb_args' => '$a $b $h'),
                                        '260' => array('field' => 'dc_publisher', 'callback' => 'simpleMap', 'cb_args' => '$a $b'),
                                        '256' => array('field' => 'dc_description', 'callback' => 'simpleMap', 'cb_args' => '$a' . chr(10)),
                                        '300' => array('field' => 'dc_format', 'callback' => 'simpleMap', 'cb_args' => '$a $b $c $e'),
                                        '440' => array('field' => 'dc_relation', 'callback' => 'simpleMap', 'cb_args' => '$a $v'),
                                        '500' => array('field' => 'dc_description', 'callback' => 'simpleMap', 'cb_args' => '$a' . chr(10)),
                                        '502' => array('field' => 'dc_description', 'callback' => 'simpleMap', 'cb_args' => 'Dissertation note:$a' . chr(10)),
                                        '518' => array('field' => 'dc_description', 'callback' => 'simpleMap', 'cb_args' => '$a' . chr(10)),
                                        '520' => array('field' => 'dc_subject', 'callback' => 'simpleMap', 'cb_args' => '$a' . chr(10)),
                                        '533' => array('field' => 'dc_description', 'callback' => 'simpleMap', 'cb_args' => '$n' . chr(10)),
                                        '600' => array('field' => 'dc_subject', 'callback' => 'simpleListMap', 'cb_args' => false),
                                        '610' => array('field' => 'dc_subject', 'callback' => 'simpleListMap', 'cb_args' => false),
                                        '611' => array('field' => 'dc_subject', 'callback' => 'simpleListMap', 'cb_args' => false),
                                        '630' => array('field' => 'dc_subject', 'callback' => 'simpleListMap', 'cb_args' => false),
                                        '650' => array('field' => 'dc_subject', 'callback' => 'simpleListMap', 'cb_args' => false),
                                        '651' => array('field' => 'dc_subject', 'callback' => 'simpleListMap', 'cb_args' => false),
                                        '652' => array('field' => 'dc_subject', 'callback' => 'simpleListMap', 'cb_args' => false),
                                        '653' => array('field' => 'dc_subject', 'callback' => 'simpleListMap', 'cb_args' => false),
                                        '773' => array('field' => 'dc_publisher', 'callback' => 'simpleMap', 'cb_args' => '$t, $g, $d'),
                                        '100' => array('field' => 'dc_creator', 'callback' => 'simpleMap', 'cb_args' => '$a'),
                                        '700' => array('field' => 'dc_creator', 'callback' => 'notEmptyMap', 'cb_args' => array('$a','dc_contributor','$a;')),
                                        '110' => array('field' => 'dc_creator', 'callback' => 'simpleMap', 'cb_args' => '$a, $b'),
                                        '111' => array('field' => 'dc_creator', 'callback' => 'notEmptyMap', 'cb_args' => array('$a, $b','dc_contributor','$a, $b;')),
                                        '710' => array('field' => 'dc_creator', 'callback' => 'notEmptyMap', 'cb_args' => array('$a, $b','dc_contributor','$a, $b;')),
                                        '711' => array('field' => 'dc_creator', 'callback' => 'notEmptyMap', 'cb_args' => array('$a, $b','dc_contributor','$a, $b;')),
                                        '856' => array('field' => 'dc_identifier', 'callback' => 'simpleMap', 'cb_args' => 'URL: $u '),
                                        );
    }

    function doSearch($search_values = false){
        $rpn =& $this->search_result['rpn'];
        if ($search_values){
            $this->search_values = $search_values;
            $this->search_result = null;
            if ( !($rpn = $this->parseSearchValues()) ){
                return false;
            }
            $this->search_result['rpn'] = $rpn;
        }
        if(!$this->z_id){
            $this->z_id = $this->doZConnect();
        }
        if($this->z_id){
            $hits = $this->doZSearch($this->z_id, $rpn, $this->z_start_range, 5);
            if($hits !== false){
                $this->z_hits = $hits;
                $this->search_result['z_hits'] = $this->z_hits;
                $fetched_records = 0;
                $end_range = (($this->z_start_range + 5) > $this->z_hits) ? $this->z_hits : $this->z_start_range + 5;
                for ($i = $this->z_start_range; $i <= $end_range; ++$i){
                    if($this->search_result[$i] = $this->getZRecord($this->z_id, $i)){
                        $fetched_records++;
                    }
                }
                return $fetched_records;
            } else {
                $this->doResetSearch();
                return false;
            }
        }
    }

    function doZConnect(){
        $zid = yaz_connect($this->z_host,$this->z_options);
        if (!$zid){
            $this->addError("error", sprintf(_("Verbindung zu %s kann nicht aufgebaut werden!"), $this->z_host));
            return false;
        }
        return $zid;
    }

    function doZSearch($zid, $rpn, $start, $number){

        yaz_range($zid, (int)$start, (int)$number);
        yaz_syntax($zid, $this->z_syntax);
        if($this->z_sort) yaz_sort($zid, $this->z_sort);
        yaz_element($zid, 'F');
        yaz_search($zid,"rpn", $rpn);
        yaz_wait(($options = array('timeout' => $this->z_timeout)));
        if (yaz_errno($zid)){
            $this->addError("error", sprintf(_("Fehler bei der Suche: %s"), yaz_error($this->z_id)));
            return false;
        } else {
            return yaz_hits($zid);
        }
    }

    function doCheckAccession($accession_number){
        if (!$this->z_accession_bib){
            $this->addError("error", sprintf(_("Attribut f�r Zugriffsnummer fehlt! (%s)"), strtolower(get_class($this))));
            return false;
        }
        if (!$accession_number){
            $this->addError("error", sprintf(_("Zugriffsnummer fehlt!")));
            return false;
        }
        if (!$this->checkAccessionNumber($accession_number)){
            $this->addError("error", sprintf(_("Zugriffsnummer hat falsches Format f�r diesen Katalog!")));
            return false;
        }
        if(!$this->z_id){
            $this->z_id = $this->doZConnect();
        }
        if ($this->z_hits){
            $save_result = $this->search_result;
            $save_z_hits = $this->z_hits;
            $this->search_result = array();
            $restore_result = true;
        }
        $rpn = '@attr 1=' . $this->z_accession_bib . ' ' . $accession_number ;
        $ret = $this->doZSearch($this->z_id, $rpn, 1, 1);
        if ($restore_result){
                $this->search_result = $save_result;
                $this->z_hits = $save_z_hits;
        } else {
            $this->search_result = array();
        }
        return $ret;
    }

    function checkAccessionNumber($accession_number){
        if (!$this->z_accession_re){
            return true;
        } else {
            return preg_match($this->z_accession_re, $accession_number);
        }
    }

    function parseSearchValues(){
        $rpn = false;
        $search_values = $this->search_values;
        if (is_array($search_values)){
            $rpn_front = "";
            $rpn_end = "";
            for ($i = 0 ; $i < count($search_values); ++$i){
                $term = $search_values[$i]['search_term'];
                if (strlen($term)){
                    if ($this->convert_umlaute){
                        $term = $this->ConvertUmlaute($term);
                    }
                    $rpn_end .= " @attr 1=" . $search_values[$i]['search_field'] . " ";
                    switch ($search_values[$i]['search_truncate']){
                        case "left":
                        $truncate = "2";
                        break;
                        case "right":
                        $truncate = "1";
                        break;
                        case "none":
                        $truncate = "100";
                        break;
                    }
                    $rpn_end .= " @attr 5=$truncate ";
                    $rpn_end .= " \"" . $term . "\" ";
                    if ($i > 0){
                        switch ($search_values[$i]['search_operator']){
                            case "AND":
                            $rpn_front = " @and " . $rpn_front;
                            break;
                            case "OR":
                            $rpn_front = " @or " . $rpn_front;
                            break;
                            case "NOT":
                            $rpn_front = " @not " . $rpn_front;
                            break;
                        }
                    }
                } else if ($i == 0) {
            $this->addError("error", _("Der erste Suchbegriff fehlt."));
            return false;
                }
            }
        }
        $rpn = $rpn_front . $rpn_end;
        return (strlen($rpn)) ? $rpn : false;
    }

    function getZRecord($zid, $rn){
        $syntax = 'xml';
        if($this->z_record_encoding != 'utf-8') $syntax .= ";charset={$this->z_record_encoding},utf-8";
        $record = yaz_record($zid,(int)$rn,$syntax);
        //echo "<pre>" .htmlReady( print_R($record,1)). '</pre>';
        $plugin_mapping = $this->mapping[$this->z_syntax];
        if ($record){
            $cat_element = new StudipLitCatElement();
            $cat_element->setValue("user_id", $GLOBALS['auth']->auth['uid']);
            $cat_element->setValue("catalog_id", $this->sess_var_name . "__" . $rn );
            $cat_element->setValue("lit_plugin", $this->getPluginName());
            $xmlrecord = new SimpleXMLElement($record);
            foreach($xmlrecord->controlfield as $field){
                $code = (string)$field['tag'];
                $data = studip_utf8decode((string)$field);
                if (isset($plugin_mapping[$code])){
                    $mapping = (is_array($plugin_mapping[$code][0])) ? $plugin_mapping[$code] : array($plugin_mapping[$code]);
                    for ($j = 0; $j < count($mapping); ++$j){
                        $map_method = $mapping[$j]['callback'];
                        $this->$map_method($cat_element, $data, $mapping[$j]['field'], $mapping[$j]['cb_args']);
                    }
                }
            }
            foreach($xmlrecord->datafield as $field){
                $code = (string)$field['tag'];
                $data = array();
                foreach($field->subfield as $subfield){
                    $subcode = (string)$subfield['code'];
                    if($subcode && !isset($data[$subcode])){
                        $data[$subcode] =  studip_utf8decode((string)$subfield);
                    }
                }
                if (isset($plugin_mapping[$code])){
                    $mapping = (is_array($plugin_mapping[$code][0])) ? $plugin_mapping[$code] : array($plugin_mapping[$code]);
                    for ($j = 0; $j < count($mapping); ++$j){
                        $map_method = $mapping[$j]['callback'];
                        $this->$map_method($cat_element, $data, $mapping[$j]['field'], $mapping[$j]['cb_args']);
                    }
                }
            }
            return $cat_element->getValues();
        } else {
            $this->addError("error",sprintf(_("Datensatz Nummer %s konnte nicht abgerufen werden."), $rn));
            return 0;
        }

    }

    function simpleMap($cat_element, $data, $field, $args){
        $trim_chars = " \t\n\r\0/,:.";
        if ($args != "" && is_array($data)){
            foreach($data as $key => $value){
                $search[] = '$' . $key;
                $replace[] = $value;
            }
            $result = str_replace($search, $replace, $args);
            $result = preg_replace('/\$[0-9a-z]\s*/', "", $result);

        } else {
            $result = $data;
        }
        $result = trim($result, $trim_chars);
        $cat_element->setValue($field, $cat_element->getValue($field) . " " . $result);
        return;
    }

    function simpleListMap($cat_element, $data, $field, $args){
        if(is_array($data)){
            $result = join('; ', $data);
        } else {
            $result = $data;
        }
        $result = (($cat_element->getValue($field)) ? $cat_element->getValue($field) . '; ' . $result : $result);
        $cat_element->setValue($field, $result);
        return;
    }

    function simpleFixFieldMap($cat_element, $data, $field, $args){
        if (is_array($args) && $data != "") {
            if ($result = trim(substr($data,$args['start'],$args['length']))) {
                if ($args['template']){
                    $result = str_replace('{result}',$result, $args['template']);
                }
                $cat_element->setValue($field, $cat_element->getValue($field) . " " . $result);
            }
        }
        return;
    }

    function notEmptyMap($cat_element, $data, $field, $args){
        if (!$cat_element->getValue($field)){
            $this->simpleMap($cat_element, $data, $field, $args[0]);
        } else {
            $this->simpleMap($cat_element, $data, $args[1], $args[2]);
        }
        return;
    }

    function getSearchFields(){
        foreach ($this->z_profile as $attribute => $name){
            $ret[] = array('name' => $name, 'value' => $attribute);
        }
        return $ret;
    }


    function getSearchResult($num_hit){
        if (!isset($this->search_result[$num_hit]) && $num_hit <= $this->z_hits){
            $this->z_start_range = (int)floor($num_hit/5)*5 + 1;
            $this->doSearch();
        }
        $catalog_id = ($this->search_result[$num_hit]['catalog_id']{0} != "_") ? $this->search_result[$num_hit]['catalog_id'] : false;
        $cat_element = new StudipLitCatElement($catalog_id);
        if ($cat_element->isNewEntry()){
            $cat_element->setValues($this->search_result[$num_hit]);
            $cat_element->setValue("catalog_id", $this->sess_var_name . "__" . $num_hit);
        }

        if($this->z_id != NULL){
            yaz_close($this->z_id);
            $this->z_id = NULL;
        }

        return $cat_element;
    }

    function doResetSearch(){
        $this->search_result = array();
        $this->z_hits = 0;
    }

    function getNumHits(){
        return $this->z_hits;
    }

    function ConvertUmlaute($text){
        $text = str_replace("�","ae",$text);
        $text = str_replace("�","Ae",$text);
        $text = str_replace("�","oe",$text);
        $text = str_replace("�","Oe",$text);
        $text = str_replace("�","ue",$text);
        $text = str_replace("�","Ue",$text);
        $text = str_replace("�","ss",$text);

        $text = str_replace("�","E",$text);
        $text = str_replace("�","E",$text);
        $text = str_replace("�","E",$text);
        $text = str_replace("�","ae",$text);
        $text = str_replace("�","ae",$text);
        $text = str_replace("�","e",$text);
        $text = str_replace("�","e",$text);
        $text = str_replace("�","i",$text);
        $text = str_replace("�","i",$text);
        $text = str_replace("�","i",$text);
        $text = str_replace("�","c",$text);

        return $text;
    }
}
?>
