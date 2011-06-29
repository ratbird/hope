<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* ShowThread.class.php
*
* creates a threaded view
*
*
* @author       Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup      resources
* @module       ShowThread.class.php
* @package      resources
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// ShowThread.class.php
// erzeugt einen threaded-view
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

require_once ($RELATIVE_PATH_RESOURCES."/views/ShowTreeRow.class.php");
require_once ($RELATIVE_PATH_RESOURCES."/lib/ResourceObject.class.php");
require_once ($RELATIVE_PATH_RESOURCES."/lib/ResourceObjectPerms.class.php");


/*****************************************************************************
ShowThread, stellt Struktur mit Hilfe von printThread dar
/*****************************************************************************/

class ShowThread extends ShowTreeRow {
    var $lines;     //Uebersichtsarray der Struktur;

    function ShowThread() {
        $this->db = new DB_Seminar;
        $this->db2 = new DB_Seminar;
    }

    function showThreadLevel ($root_id, $level=0, $lines='') {
        global $resources_data, $edit_structure_object, $RELATIVE_PATH_RESOURCES, $PHP_SELF, $ActualObjectPerms;

        $db=new DB_Seminar;
        $db2=new DB_Seminar;

        //Daten des Objects holen
        $db->query("SELECT resource_id FROM resources_objects WHERE resource_id = '$root_id' ");

        while ($db->next_record()) {
            //Untergeordnete Objekte laden
            $db2->query("SELECT resource_id FROM resources_objects WHERE parent_id = '".$db->f("resource_id")."' ORDER BY name ");

            //Struktur merken
            $weitere=$db2->affected_rows();
            $this->lines[$level+1] = $weitere;

            //Object erstellen
            $resObject = ResourceObject::Factory($db->f("resource_id"));

            //Daten vorbereiten
            if (!$resObject->getCategoryIconnr())
                $icon = Assets::img('icons/16/grey/folder-full.png', array('class' => 'text-top'));
            else
                $icon="<img src=\"".$GLOBALS['ASSETS_URL']."images/cont_res".$resObject->getCategoryIconnr().".gif\">";

            if ($resources_data["move_object"])
                $icon="&nbsp;<a href=\"$PHP_SELF?target_object=".$resObject->id."#a\"><img src=\"".Assets::image_path('icons/16/yellow/arr_2right.png')."\" alt=\""._("Objekt in diese Ebene verschieben")."\"></a>".$icon;

            if ($resources_data["structure_opens"][$resObject->id]) {
                $link = URLHelper::getLink('?structure_close=' . $resObject->id . '#a');
                $open = 'open';
                if ($resources_data["actual_object"] == $resObject->id)
                    echo '<a name="a"></a>';
            } else {
                $link = URLHelper::getLink('?structure_open=' . $resObject->id . '#a');
                $open = 'close';
            }

            if ($resObject->getCategoryName())
                $titel=$resObject->getCategoryName().": ";
            if ($edit_structure_object==$resObject->id) {
                echo "<a name=\"a\"></a>";
                $titel.="<input style=\"font-size: 8pt; width: 100%;\" type=\"text\" size=20 maxlength=255 name=\"change_name\" value=\"".htmlReady($resObject->getName())."\">";
            } else {
                $titel.=htmlReady($resObject->getName());
            }

            //create a link on the titel, too
            if (($link) && ($edit_structure_object != $resObject->id))
                $titel = "<a href=\"$link\" class=\"tree\" >$titel</a>";

            if ($resObject->getOwnerLink())
                $zusatz=sprintf (_("verantwortlich:") . " <a href=\"%s\"><font color=\"#333399\">%s</font></a>", $resObject->getOwnerLink(), htmlReady($resObject->getOwnerName()));
            else
                $zusatz=sprintf (_("verantwortlich:") . " %s", htmlReady($resObject->getOwnerName()));
            $new=TRUE;
            if ($open=="open") {
                //load the perms
                if (($ActualObjectPerms) && ($ActualObjectPerms->getId() == $resObject->getId())) {
                    $perms = $ActualObjectPerms->getUserPerm();
                } else {
                    $ThisObjectPerms = ResourceObjectPerms::Factory($resObject->getId());
                    $perms = $ThisObjectPerms->getUserPerm();
                }

                if ($edit_structure_object==$resObject->id) {
                    $content.= "<br><textarea name=\"change_description\" rows=3 cols=40>".htmlReady($resObject->getDescription())."</textarea><br>";
                    $content.= "<input type=\"image\" name=\"send\" align=\"absmiddle\" ".makeButton("uebernehmen", "src")." border=0 value=\""._("&Auml;nderungen speichern")."\">";
                    $content.= "&nbsp;<a href=\"$PHP_SELF?cancel_edit=$resObject->id\">".makeButton("abbrechen", "img")."</a>";
                    $content.= "<input type=\"hidden\" name=\"change_structure_object\" value=\"".$resObject->getId()."\">";
                    $open="open";
                } else {
                    $content=htmlReady($resObject->getDescription());
                }
                if ($resources_data["move_object"] == $resObject->id)
                    $content.= sprintf ("<br>"._("Dieses Objekt wurde zum Verschieben markiert. Bitte w&auml;hlen Sie das Einf&uuml;gen-Symbol %s, um es in die gew&uuml;nschte Ebene zu verschieben."), "<img src=\"".Assets::image_path('icons/16/yellow/arr_2right.png')."\" alt=\""._("Klicken Sie auf dieses Symbol, um dieses Objekt in eine andere Ebene zu verschieben")."\">");

                if ($resObject->getCategoryId())
                    $edit.= "<a href=\"$PHP_SELF?show_object=$resObject->id&view=view_schedule\">".makeButton("belegung")."</a>&nbsp;";
                $edit.= "<a href=\"$PHP_SELF?show_object=$resObject->id&view=view_details\">".makeButton("eigenschaften")."</a>";


                if ($perms == "admin") {
                    $edit.= "&nbsp;&nbsp;&nbsp;&nbsp;";
                    $edit.= "<a href=\"$PHP_SELF?create_object=$resObject->id\">".makeButton("neuesobjekt", "img")."</a>";
                    $edit.= "&nbsp;<a href=\"$PHP_SELF?create_hierachie_level=$resObject->id\">".makeButton("neueebene", "img")."</a>";
                }

                $edit.= "&nbsp;&nbsp;&nbsp;&nbsp;";

                if ($weitere)
                    $edit.= "<a href=\"$PHP_SELF?open_list=$resObject->id\">".makeButton("listeoeffnen", "img")."</a>";

                if ($resources_data["move_object"] == $resObject->id)
                    $edit.= "&nbsp;<a href=\"$PHP_SELF?cancel_move=TRUE\">".makeButton("abbrechen", "img")."</a>";
                elseif ($perms == "admin")
                    $edit.= "&nbsp;<a href=\"$PHP_SELF?pre_move_object=$resObject->id\">".makeButton("verschieben", "img")."</a>";

                if ((!$weitere) && ($perms == "admin") && ($resObject->isDeletable()))
                    $edit.= "&nbsp;<a href=\"$PHP_SELF?kill_object=$resObject->id\">".makeButton("loeschen", "img")."</a>";
            }

            //Daten an Ausgabemodul senden (aus resourcesVisual)
            $this->showRow($icon, $link, $titel, $zusatz, $level, $lines, $weitere, $new, $open, $content, $edit);

            //in weitere Ebene abtauchen &nbsp;
            while ($db2->next_record()) {
                if ($resources_data["structure_opens"][$db->f("resource_id")])
                    $this->showThreadLevel($db2->f("resource_id"), $level+1, $lines);
            }
        }
    }
}
