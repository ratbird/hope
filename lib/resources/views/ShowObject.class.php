<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* ShowObject.class.php
* 
* shows an object
* 
*
* @author       Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup      resources
* @module       EditResourceData.class.php
* @package      resources
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// ShowObject.class.php
// stellt ein Ressourcen Objekt da
// Copyright (C) 2003 Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
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

require_once ($RELATIVE_PATH_RESOURCES."/lib/ResourceObject.class.php");
require_once ($RELATIVE_PATH_RESOURCES."/views/ShowList.class.php");
require_once ($RELATIVE_PATH_RESOURCES."/views/ResourcesBrowse.class.php");

/*****************************************************************************
showObject, Darstellung der Eigenschaften eines Objekts
/*****************************************************************************/

class ShowObject {
    var $resObject;     //Das Oject an dem gearbeitet wird
    
    //Konstruktor
    function ShowObject($resource_id) {
        $this->db = new DB_Seminar;
        $this->db2 = new DB_Seminar;
        $this->resObject = ResourceObject::Factory($resource_id);
        $this->cssSw = new cssClassSwitcher;

        $this->list = new ShowList;
        $this->list->setRecurseLevels(0);
        $this->list->setViewHiearchyLevels(TRUE);
        $this->list->setSimpleList(TRUE);
    }

    //privat
    function selectProperties() {
        $this->db->query ("SELECT resources_properties.name, resources_properties.description, resources_properties.type, resources_properties.options, resources_properties.system, resources_properties.property_id  FROM resources_properties LEFT JOIN resources_categories_properties USING (property_id) LEFT JOIN resources_objects USING (category_id) WHERE resources_objects.resource_id = '".$this->resObject->getId()."' ORDER BY resources_properties.name");
        if (!$this->db->affected_rows())
            return FALSE;
        else
            return TRUE;
    }
    
    function showProperties() {
        global $PHP_SELF, $view_mode;

        ?>
        <table border=0 celpadding=2 cellspacing=0 width="99%" align="center">
        <form method="POST" action="<?echo $PHP_SELF ?>?change_object_properties=<? echo $this->resObject->getId() ?>">
            <?= CSRFProtection::tokenTag() ?>
            <input type="hidden" name="view" value="edit_object_properties">
            <tr>
                <td class="<? echo $this->cssSw->getClass() ?>" width="4%">&nbsp; 
                </td>
                <td class="<? echo $this->cssSw->getClass() ?>"><font size=-1><b><?=_("Name:")?></b></font><br>
                <font size=-1><? echo htmlReady($this->resObject->getName())." (".(($this->resObject->getCategoryName()) ? htmlReady($this->resObject->getCategoryName()) : _("Hierachieebene")).")" ?>
                </td>
                <td class="<? echo $this->cssSw->getClass() ?>" width="60%" valign="top"><font size=-1><b><?=_("verantwortlich:")?></b></font><br>
                <font size=-1>
                <? 
                if ($view_mode == "no_nav")
                    print htmlReady($this->resObject->getOwnerName(TRUE));
                else
                    print "<a href=\"".$this->resObject->getOwnerLink()."\">".htmlReady($this->resObject->getOwnerName(TRUE))."</a>";
                ?>
                </font>
                </td>
            </tr>
            <tr>
                <td class="<? $this->cssSw->switchClass(); echo $this->cssSw->getClass() ?>" width="4%">&nbsp; 
                </td>
                <td class="<? echo $this->cssSw->getClass() ?>" valign="top" colspan=2><font size=-1><b><?=_("Beschreibung:")?></b></font><br>
                <font size=-1><? echo htmlReady($this->resObject->getDescription()) ?></font>
            </tr>
            <?
            if ($this->resObject->isParent())
                $childs = TRUE;
            ?>
            <tr>
                <td class="<? $this->cssSw->switchClass(); echo $this->cssSw->getClass() ?>" width="4%">&nbsp; 
                </td>
                <td class="<? echo $this->cssSw->getClass() ?>" valign="top" <?=($childs) ? "" : "colspan=\"2\"" ?>><font size=-1><b><?=_("Einordnung:")?></b></font><br>
                <font size=-1><? echo ResourcesBrowse::getHistory($this->resObject->getId(), TRUE) ?></font>
                </td>
                <?
                if ($childs) {
                ?>
                <td class="<? echo $this->cssSw->getClass() ?>" valign="top" ><font size=-1><b><?=_("Untergeordnete Objekte:")?></b></font><br>
                <font size=-1><? $this->list->showListObjects($this->resObject->getId()) ?></font>
                <?
                }
                ?>
            </tr>
            
            <? 
            if ($this->resObject->getCategoryId()) {
            ?>
            <tr>
                <td class="<? $this->cssSw->switchClass(); echo $this->cssSw->getClass() ?>" width="4%">&nbsp; 
                </td>
                <td class="<? echo $this->cssSw->getClass() ?>" colspan=2><font size=-1><b><?=_("Eigenschaften:")?></b></font>
                </td>
            </tr>
            <?
                $this->selectProperties();
                while ($this->db->next_record()) {
                    ?>
            <tr>
                <td class="<?   $this->cssSw->switchClass(); echo $this->cssSw->getClass() ?>" width="4%">&nbsp; 
                </td>
                <td class="<? echo $this->cssSw->getClass() ?>">
                    &nbsp; &nbsp; <font size=-1>&bull;&nbsp;<? echo htmlReady($this->db->f("name")); ?></font>
                </td>
                <td class="<? echo $this->cssSw->getClass() ?>" width="40%">
                <font size=-1>
                <?
                    $this->db2->query("SELECT * FROM resources_objects_properties WHERE resource_id = '".$this->resObject->getId()."' AND property_id = '".$this->db->f("property_id")."' ");
                    $this->db2->next_record();
                    switch ($this->db->f("type")) {
                        case "bool":
                            printf ("%s", ($this->db2->f("state")) ?  htmlReady($this->db->f("options")) : " - ");
                        break;
                        case "num":
                        case "text";
                            print htmlReady($this->db2->f("state"));
                        break;
                        case "select";
                            $options=explode (";",$this->db->f("options"));
                            foreach ($options as $a) {
                                if ($this->db2->f("state") == $a) 
                                    print htmlReady($a);
                            }
                        break;
                    }
                ?></td>
            </tr><?
                }
            }  ?>
        </table>
        <?
    }   
}
