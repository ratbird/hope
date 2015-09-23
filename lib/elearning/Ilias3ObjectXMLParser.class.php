<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
    +-----------------------------------------------------------------------------+
    | ILIAS open source                                                           |
    +-----------------------------------------------------------------------------+
    | Copyright (c) 1998-2001 ILIAS open source, University of Cologne            |
    |                                                                             |
    | This program is free software; you can redistribute it and/or               |
    | modify it under the terms of the GNU General Public License                 |
    | as published by the Free Software Foundation; either version 2              |
    | of the License, or (at your option) any later version.                      |
    |                                                                             |
    | This program is distributed in the hope that it will be useful,             |
    | but WITHOUT ANY WARRANTY; without even the implied warranty of              |
    | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
    | GNU General Public License for more details.                                |
    |                                                                             |
    | You should have received a copy of the GNU General Public License           |
    | along with this program; if not, write to the Free Software                 |
    | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
    +-----------------------------------------------------------------------------+
*/


/**
* Object XML Parser
*
* @author Stefan Meyer <smeyer@databay.de>
*
* @extends ilSaxParser
* @package common
*/

class Ilias3ObjectXMLParser extends Ilias3SaxParser
{
    var $object_data = array();

    /**
    * Constructor
    *
    * @param    object      $a_content_object   must be of type ilObjContentObject
    *                                           ilObjTest or ilObjQuestionPool
    * @param    string      $a_xml_file         xml data
    * @param    string      $a_subdir           subdirectory in import directory
    * @access   public
    */
    function Ilias3ObjectXMLParser($a_xml_data = '')
    {
        parent::Ilias3SaxParser();
        $this->setXMLContent($a_xml_data);
    }

    function getObjectData()
    {
        return $this->object_data ? $this->object_data : array();
    }

    /**
    * set event handlers
    *
    * @param    resource    reference to the xml parser
    * @access   private
    */
    function setHandlers($a_xml_parser)
    {
        xml_set_object($a_xml_parser,$this);
        xml_set_element_handler($a_xml_parser,'handlerBeginTag','handlerEndTag');
        xml_set_character_data_handler($a_xml_parser,'handlerCharacterData');
    }




    /**
    * handler for begin of element
    *
    * @param    resource    $a_xml_parser       xml parser
    * @param    string      $a_name             element name
    * @param    array       $a_attribs          element attributes array
    */
    function handlerBeginTag($a_xml_parser,$a_name,$a_attribs)
    {

        switch($a_name)
        {
            case 'Objects':
                $this->curr_obj = -1;
                break;

            case 'Object':
                ++$this->curr_obj;
                $this->reference_count = -1;

                $this->__addProperty('type',$a_attribs['type']);
                $this->__addProperty('obj_id',$a_attribs['obj_id']);
                break;

            case 'Title':
                break;

            case 'Description':
                break;

            case 'Owner':
                break;

            case 'CreateDate':
                break;

            case 'LastUpdate':
                break;

            case 'ImportId':
                break;

            case 'References':
                ++$this->reference_count;
                $this->__addReference($a_attribs['ref_id'], $a_attribs['accessInfo']);
                break;

            case 'Operation':
                break;
        }
    }



    /**
    * handler for end of element
    *
    * @param    resource    $a_xml_parser       xml parser
    * @param    string      $a_name             element name
    */
    function handlerEndTag($a_xml_parser,$a_name)
    {
        switch($a_name)
        {
            case 'Objects':
                break;

            case 'Object':
                break;

            case 'Title':
                $this->__addProperty('title',trim($this->cdata));
                break;

            case 'Description':
                $this->__addProperty('description',trim($this->cdata));
                break;

            case 'Owner':
                $this->__addProperty('owner',trim($this->cdata));
                break;

            case 'CreateDate':
                $this->__addProperty('create_date',trim($this->cdata));
                break;

            case 'LastUpdate':
                $this->__addProperty('last_update',trim($this->cdata));
                break;

            case 'ImportId':
                $this->__addProperty('import_id',trim($this->cdata));
                break;

            case 'References':
                $this->__addReference(trim($this->cdata));
                break;

            case 'Operation':
                $this->__addOperation(trim($this->cdata));
                break;
        }

        $this->cdata = '';

        return;
    }

    /**
    * handler for character data
    *
    * @param    resource    $a_xml_parser       xml parser
    * @param    string      $a_data             character data
    */
    function handlerCharacterData($a_xml_parser,$a_data)
    {
        if($a_data != "\n")
        {
            // Replace multiple tabs with one space
            $a_data = preg_replace("/\t+/"," ",$a_data);

            $this->cdata .= $a_data;
        }


    }

    // PRIVATE
    function __addProperty($a_name,$a_value)
    {
            $this->object_data[$this->curr_obj][$a_name] = $a_value;
    /*/
        if (is_array($this->object_data[$this->curr_obj][$a_name]))
            $this->object_data[$this->curr_obj][$a_name][] = $a_value;
        elseif ($this->object_data[$this->curr_obj][$a_name] != "")
        {
            $old_value = $this->object_data[$this->curr_obj][$a_name];
            $this->object_data[$this->curr_obj][$a_name] = array($old_value);
            $this->object_data[$this->curr_obj][$a_name][] = $a_value;
        }
        else
            $this->object_data[$this->curr_obj][$a_name] = $a_value;
        /**/
    }

    function __addReference($a_value, $a_accessinfo = "")
    {
        if($a_value)
        {
            $this->object_data[$this->curr_obj]['references'][$this->reference_count]["ref_id"] = $a_value;
            $this->object_data[$this->curr_obj]['references'][$this->reference_count]["accessInfo"] = $a_accessinfo;
        }
    }

    function __addOperation($a_value)
    {
        if($a_value)
            $this->object_data[$this->curr_obj]['references'][$this->reference_count]["operations"][] = $a_value;
    }

}
?>