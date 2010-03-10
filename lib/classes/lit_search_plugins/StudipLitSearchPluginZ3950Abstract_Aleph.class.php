<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
// Universität Trier  -  Jörg Röpke  -  <roepke@uni-trier.de>
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// StudipLitSearchPluginZ3950Abstract_Aleph.class.php
// 
// 
// Copyright (c) 2005 Jörg Röpke  -  <roepke@uni-trier.de>
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
require_once ("lib/classes/lit_search_plugins/StudipLitSearchPluginZ3950Abstract.class.php");


class StudipLitSearchPluginZ3950Abstract_Aleph extends StudipLitSearchPluginZ3950Abstract {
	
	var $convert_umlaute = true;
	
	var $superTitle = "";
	var $superAutor = "";
	var $superCity = "";
	var $superPublisher = "";
	
	function StudipLitSearchPluginZ3950Abstract_Aleph() { 
		parent::StudipLitSearchPluginZ3950Abstract();
										
		// USMARC mapping								
		$this->mapping['USMARC'] = array('001' => array('field' => 'accession_number', 'callback' => 'idMap', 'cb_args' => ''),
										 '010' => array('field' => 'dc_title', 'callback' => 'search_superbook', 'cb_args' => FALSE),
										 '100' => array('field' => 'dc_creator', 'callback' => 'simpleMap', 'cb_args' => '$a'),
										 '104' => array('field' => 'dc_creator', 'callback' => 'notEmptyMap', 'cb_args' => array('$a','dc_contributor','$a;')),
										 '108' => array('field' => 'dc_creator', 'callback' => 'notEmptyMap', 'cb_args' => array('$a$b','dc_contributor','$a$b;')),
										 '112' => array('field' => 'dc_creator', 'callback' => 'notEmptyMap', 'cb_args' => array('$a','dc_contributor','$a;')),
										 '331' => array('field' => 'dc_title', 'callback' => 'titleMap', 'cb_args' => FALSE),
 										 '335' => array('field' => 'dc_title', 'callback' => 'simpleMap', 'cb_args' => ' - $a'),
										 //'403' => array('field' => 'dc_identifier', 'callback' => 'simpleMap', 'cb_args' => 'ISBN: $a'),
										 '433' => array('field' => 'dc_format', 'callback' => 'simpleMap', 'cb_args' => '$a'),
										 '410' => array('field' => 'dc_publisher', 'callback' => 'simpleMap', 'cb_args' => '$a'),
										 '412' => array('field' => 'dc_publisher', 'callback' => 'simpleMap', 'cb_args' => '$a'),
 										 '425' => array('field' => 'dc_date', 'callback' => 'simpleMap', 'cb_args' => '$a-01-01'),
										 '540' => array('field' => 'dc_identifier', 'callback' => 'simpleMap', 'cb_args' => 'ISBN: $a'),
										 //'902' => array('field' => 'dc_subject', 'callback' => 'simpleMap', 'cb_args' => '$s;'),
										 '907' => array('field' => 'dc_subject', 'callback' => 'simpleMap', 'cb_args' => '$s;')
										);
	}
	
	
	
	
	// suche übergeortnetem Band
	function search_superbook(&$cat_element, $data, $field, $args)
	{
		$result = $data['a'];
		if(!$zid = $this->doZConnect()){
			return false;
		}
		$ok = $this->doZsearch($zid, "@attr 1=12 \"".$result."\"", 1, 1);
		if($ok){
			$super = $this->getZRecord($zid, 1);
			$cat_element->setValue('dc_title', $super['dc_title'] . " (...)");
			$cat_element->setValue('dc_creator', $super['dc_creator']);
			$cat_element->setValue('dc_publisher', $super['dc_publisher']);
			$cat_element->super_book = $super;
		}
	}
	
	
	// ID Mapping für Hyperlink zum Bibliothekskatalog
	function idMap(&$cat_element, $data, $field, $args)
	{
		
		$cat_element->setValue($field, "IDN=".substr($data,3));
		
		return;
	}
		

	// Titel
	function titleMap(&$cat_element, $data, $field, $args)
	{
		$result = $data['a'];
		
		$result = str_replace(array('<','>'),'',$result);
		$result = trim($result);
		
		// Untergeordneter Band -> Supertitel hinzufügen
		if($cat_element->super_book['dc_title'] != "")
			$cat_element->setValue($field, $cat_element->super_book['dc_title']." - ".$result);
		// Haupt- bzw. Übergeordneter Band
		else
			$cat_element->setValue($field, $result);
		return;
	}
	
	function simpleMap(&$cat_element, $data, $field, $args){
		foreach($data as $key => $value){
			$data1[$key] = str_replace(array('<','>'),'',$value);
		}
		parent::simpleMap($cat_element, $data1, $field, $args);
	}
}
?>
