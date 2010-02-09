<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* Export xml-functions.
*
* In this file there are several functions to generate xml-tags.
*
* @author		Arne Schroeder <schroeder@data.quest.de>
* @access		public
* @modulegroup		export_modules
* @module		export_xml_functions
* @package		Export
*/
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// export_xml_func.inc.php
//
// Copyright (c) 2002 Arne Schroeder <schroeder@data-quest.de>
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

require_once("lib/classes/SemesterData.class.php");

/**
* create xml_header
*
* This function creates a xml-header for output.
* Its contents are Name of University, Stud.IP-Version, Range of Export (e.g. "root"), and temporal range.
*
* @access	public
* @return		string	xml-header
*/
function xml_header()
{
global $UNI_NAME_CLEAN, $SEM_ID, $SOFTWARE_VERSION, $ex_type, $ex_sem, $range_name, $range_id;
	$semester = new SemesterData;
	$all_semester = $semester->getAllSemesterData();
	$xml_tag_string = "<" . "?xml version=\"1.0\"?>\n";
	//encoding=\"ISO-8859-1\" encoding=\"UTF-8\";
//	$xml_tag_string .= "<!DOCTYPE StudIP SYSTEM \"http://goettingen.studip.de/studip.dtd\">\n";
	$xml_tag_string .= "<studip version=\"" . htmlspecialchars ($SOFTWARE_VERSION) . "\" logo=\"". htmlspecialchars ($GLOBALS['ASSETS_URL']."images/logo2b.gif") . "\"";
	if ($range_id == "root") $xml_tag_string .= " range=\"" . _("Alle Einrichtungen") . "\"";
	elseif ($range_name != "") $xml_tag_string .= " range=\"" . htmlspecialchars ($range_name) . "\"";
	if ($UNI_NAME_CLEAN != "") $xml_tag_string .= " uni=\"" . htmlspecialchars ($UNI_NAME_CLEAN) . "\"";
	if ($ex_type !="veranstaltung")
		$xml_tag_string .= " zeitraum=\"" . htmlspecialchars ($all_semester[$SEM_ID]["name"]) . "\" semester_id=\"" . htmlspecialchars ($SEM_ID) . "\"";
	elseif ($all_semester[$ex_sem]["name"] != "") $xml_tag_string .= " zeitraum=\"" . htmlspecialchars ($all_semester[$ex_sem]["name"]) . "\" semester_id=\"" . htmlspecialchars ($all_semester[$ex_sem]["semester_id"]) . "\"";
	$xml_tag_string .= ">\n";
	return $xml_tag_string;
}

/**
* create opening xml-tag
*
* This function creates an open xml-tag.
* The tag-name is defined by the given parameter $tag_name.
* An optional parameter allows to set an attribute named "key".
*
* @access	public
* @param		string	tag name
* @param		string	value for optional attribute "key"
* @return		string	xml open tag
*/
function xml_open_tag($tag_name, $tag_key = "")
{
	if ($tag_key != "")
		$xml_tag_string .= " key=\"" . htmlspecialchars ($tag_key ) ."\"" ;
	$xml_tag_string = "<" . $tag_name . $xml_tag_string .  ">\n";
	return $xml_tag_string;
}

/**
* create closing xml-tag
*
* This function creates a closed xml-tag.
* The tag-name is defined by the given parameter $tag_name.
*
* @access	public
* @param		string	tag name
* @return		string	xml close tag
*/
function xml_close_tag($tag_name)
{
	$xml_tag_string = "</" . $tag_name .  ">\n";
	return $xml_tag_string;
}

/**
* create xml-tag
*
* This function creates a xml-tag. 
* The tag-name is defined by the given parameter $tag_name.
* The given parameter tag_content is put between open tag and close tag.
*
* @access	public        
* @param		string	tag name
* @param		string	content for xml-tag
* @param		array	array of tag attributes
* @return		string	xml tag
*/
function xml_tag($tag_name, $tag_content, $tag_attributes = null)
{
	if (is_array($tag_attributes)){
		foreach($tag_attributes as $key => $value){
			$xml_tag_string .= " $key=\"".htmlspecialchars($value)."\" ";
		}
	}
	$xml_tag_string = "<" . $tag_name . $xml_tag_string .  ">" 
		. htmlspecialchars ( $tag_content )
		. "</" . $tag_name .  ">\n";
	return $xml_tag_string;
}

/**
* create xml-footer
*
* This function creates the footer for xml output,
* which is a closing "studip"-tag.
*
* @access	public
* @return		string	xml footer
*/
function xml_footer()
{
	$xml_tag_string = "</studip>";
	return $xml_tag_string;
}

?>
