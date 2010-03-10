<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// StudipLitListAdmin.class.php
//
//
// Copyright (c) 2003 André Noack <noack@data-quest.de>
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

require_once('lib/classes/TreeView.class.php');
require_once('lib/classes/StudipLitList.class.php');
require_once('lib/classes/StudipLitClipBoard.class.php');
require_once('lib/datei.inc.php');
require_once('lib/classes/lit_search_plugins/StudipLitSearchPluginZ3950Abstract.class.php');

/**
*
*
*
* @access   public
* @author   André Noack <noack@data-quest.de>
* @package
*/
class StudipLitListViewAdmin extends TreeView{



    var $mode;

    var $edit_item_id;

    var $msg;

    var $clip_board;

    var $format_info;


    /**
    * constructor
    *
    * calls the base class constructor
    * @access public
    */
    function StudipLitListViewAdmin($range_id){
        $this->use_aging = true;
        $this->format_info = _("Felder müssen in geschweiften Klammern (z.B. {dc_title}) angegeben werden.\n")
                            . _("Felder und Text, der zwischen senkrechten Strichen steht, wird nur angezeigt, wenn das angegebene Feld nicht leer ist. (z.B. |Anmerkung: {note}|)\n")
                            . _("Folgende Felder können angezeigt werden:\n")
                            . _("Titel - dc_title\n")
                            . _("Verfasser oder Urheber - dc_creator\n")
                            . _("Thema und Stichwörter - dc_subject\n")
                            . _("Inhaltliche Beschreibung - dc_description\n")
                            . _("Verleger, Herausgeber - dc_publisher\n")
                            . _("Weitere beteiligten Personen und Körperschaften - dc_contributor\n")
                            . _("Datum - dc_date\n")
                            . _("Ressourcenart - dc_type\n")
                            . _("Format - dc_format\n")
                            . _("Ressourcen-Identifikation - dc_identifier\n")
                            . _("Quelle - dc_source\n")
                            . _("Sprache - dc_language\n")
                            . _("Beziehung zu anderen Ressourcen - dc_relation\n")
                            . _("Räumliche und zeitliche Maßangaben - dc_coverage\n")
                            . _("Rechtliche Bedingungen - dc_rights\n")
                            . _("Zugriffsnummer - accession_number\n")
                            . _("Jahr - year\n")
                            . _("alle Autoren - authors\n")
                            . _("Herausgeber mit Jahr - published\n")
                            . _("Anmerkung - note\n")
                            . _("link in externes Bibliothekssystem - external_link\n");

        parent::TreeView("StudipLitList", $range_id); //calling the baseclass constructor
        $this->clip_board =& StudipLitClipBoard::GetInstance();
    }

    function parseCommand(){
        if ($_REQUEST['mode'])
            $this->mode = $_REQUEST['mode'];
        if ($_REQUEST['cmd']){
            $exec_func = "execCommand" . $_REQUEST['cmd'];
            if (method_exists($this,$exec_func)){
                if ($this->$exec_func()){
                    $this->tree->init();
                }
            }
        }
    }


    function execCommandEditItem(){
        $item_id = $_REQUEST['item_id'];
        $this->mode = "EditItem";
        $this->anchor = $item_id;
        $this->edit_item_id = $item_id;
        return false;
    }

    function execCommandInClipboard(){
        $item_id = $_REQUEST['item_id'];
        if (is_object($this->clip_board)){
            if ($this->tree->isElement($item_id)){
                $this->clip_board->insertElement($this->tree->tree_data[$item_id]['catalog_id']);
                $this->msg[$item_id] = $this->clip_board->msg;
            } else {
                if ($this->tree->getNumKids($item_id)){
                    $kids = $this->tree->getKids($item_id);
                    for ($i = 0; $i < $this->tree->getNumKids($item_id); ++$i){
                        $cat_ids[] = $this->tree->tree_data[$kids[$i]]['catalog_id'];
                    }
                    $this->clip_board->insertElement($cat_ids);
                    $this->msg[$item_id] = $this->clip_board->msg;
                }
            }
        }
        return false;
    }

    function execCommandInsertItem(){
        $item_id = $_REQUEST['item_id'];
        $parent_id = $_REQUEST['parent_id'];
        $user_id = $GLOBALS['auth']->auth['uid'];
        if ($this->mode != "NewItem"){
            if (isset($_REQUEST['edit_note'])){
                $affected_rows = $this->tree->updateElement(array('list_element_id' => $item_id, 'note' => $_REQUEST['edit_note'], 'user_id' => $user_id));
                if ($affected_rows){
                    $this->msg[$item_id] = "msg§" . _("Anmerkung wurde ge&auml;ndert.");
                } else {
                    $this->msg[$item_id] = "info§" . _("Keine Ver&auml;nderungen vorgenommen.");
                }
            } else if (isset($_REQUEST['edit_format'])){
                $affected_rows = $this->tree->updateList(array('list_id' => $item_id,'format' => $_REQUEST['edit_format'],'name' => $_REQUEST['edit_name'],'visibility' => $_REQUEST['edit_visibility'], 'user_id' => $user_id));
                if ($affected_rows){
                    $this->msg[$item_id] = "msg§" . _("Listeneigenschaften wurden ge&auml;ndert.");
                } else {
                    $this->msg[$item_id] = "info§" . _("Keine Ver&auml;nderungen vorgenommen.");
                }
            }
        } else {
            $priority = $this->tree->getMaxPriority($parent_id) + 1;
            $affected_rows = $this->tree->insertList(array('list_id' => $item_id,'priority' => $priority, 'format' => $_REQUEST['edit_format'],'visibility' => $_REQUEST['edit_visibility'], 'name' => $_REQUEST['edit_name'],'user_id' => $user_id));
            if ($affected_rows){
                $this->mode = "";
                $this->anchor = $item_id;
                $this->open_items[$item_id] = true;
                $this->msg[$item_id] = "msg§" . _("Diese Liste wurde neu eingef&uuml;gt.");
            }
        }
        $this->mode = "";
        $this->anchor = $item_id;
        $this->open_items[$item_id] = true;
        return true;
    }

    function execCommandCopyList(){
        $item_id = $_REQUEST['item_id'];
        if ($new_list_id = $this->tree->copyList($item_id)){
            $this->anchor = $new_list_id;
            $this->open_ranges[$new_list_id] = true;
            $this->open_items[$new_list_id] = true;
            $this->msg[$new_list_id] = "msg§" . _("Diese Liste wurde kopiert.");
        } else {
            $this->anchor = $item_id;
            $this->msg[$item_id] = "error§" . _("Die Liste konnte nicht kopiert werden.");
        }
        return true;
    }

    function execCommandCopyUserList(){
        $list_id = $_REQUEST['user_list'];
        if ($new_list_id = $this->tree->copyList($list_id)){
            $this->anchor = $new_list_id;
            $this->open_ranges[$new_list_id] = true;
            $this->open_items[$new_list_id] = true;
            $this->msg[$new_list_id] = "msg§" . _("Diese Liste wurde kopiert.");
        } else {
            $this->anchor = 'root';
            $this->msg['root'] = "error§" . _("Die Liste konnte nicht kopiert werden.");
        }
        return true;
    }

    function execCommandToggleVisibility(){
        $item_id = $_REQUEST['item_id'];
        $user_id = $GLOBALS['auth']->auth['uid'];
        $visibility = ($this->tree->tree_data[$item_id]['visibility']) ? 0 : 1;
        if ($this->tree->updateList(array('list_id' => $item_id, 'visibility' => $visibility, 'user_id' => $user_id))){
            $this->msg[$item_id] = "msg§" . _("Die Sichtbarkeit der Liste wurde ge&auml;ndert.");
        } else {
            $this->msg[$item_id] = "error§" . _("Die Sichtbarkeit konnte nicht ge&auml;ndert werden.");
        }
        $this->anchor = $item_id;
        return true;
    }

    function execCommandOrderItem(){
        $direction = $_REQUEST['direction'];
        $item_id = $_REQUEST['item_id'];
        $items_to_order = $this->tree->getKids($this->tree->tree_data[$item_id]['parent_id']);
        if (!$items_to_order){
            return false;
        }
        for ($i = 0; $i < count($items_to_order); ++$i){
            if ($item_id == $items_to_order[$i])
                break;
        }
        if ($direction == "up" && isset($items_to_order[$i-1])){
            $items_to_order[$i] = $items_to_order[$i-1];
            $items_to_order[$i-1] = $item_id;
        } elseif (isset($items_to_order[$i+1])){
            $items_to_order[$i] = $items_to_order[$i+1];
            $items_to_order[$i+1] = $item_id;
        }
        for ($i = 0; $i < count($items_to_order); ++$i){
            if ($this->tree->isElement($item_id)){
                $this->tree->updateElement(array('priority' => $i, 'list_element_id' => $items_to_order[$i]));
            } else {
                $this->tree->updateList(array('priority' => $i, 'list_id' => $items_to_order[$i]));
            }
        }
        $this->mode = "";
        $this->msg[$item_id] = "msg§" . (($direction == "up") ? _("Element wurde um eine Position nach oben verschoben.") : _("Element wurde um eine Position nach unten verschoben."));
        return true;
    }
    
    function execCommandSortKids(){
        $item_id = $_REQUEST['item_id'];
        $kids = $this->tree->getKids($item_id);
        usort($kids, create_function('$a,$b',
                '$the_tree =& TreeAbstract::GetInstance("StudipLitList", "'.$this->tree->range_id.'");
                return strnatcasecmp(StudipLitSearchPluginZ3950Abstract::ConvertUmlaute($the_tree->getValue($a, "name")),StudipLitSearchPluginZ3950Abstract::ConvertUmlaute($the_tree->getValue($b, "name")));
                '));
        foreach($kids as $pos => $kid_id){
            if ($this->tree->isElement($kid_id)){
                $this->tree->updateElement(array('priority' => $pos, 'list_element_id' => $kid_id));
            } else {
                $this->tree->updateList(array('priority' => $pos, 'list_id' => $kid_id));
            }
        }
        $this->mode = "";
        $this->msg[$item_id] = "msg§" . _("Die Unterelemente wurden alphabetisch sortiert.") . '§';
        return true;
    }
    
    function execCommandAssertDeleteItem(){
        $item_id = $_REQUEST['item_id'];
        $this->mode = "AssertDeleteItem";
        $this->msg[$item_id] = "info§" ._("Sie beabsichtigen diese Liste inklusive aller Eintr&auml;ge, zu l&ouml;schen. ")
                        . sprintf(_("Es werden insgesamt %s Eintr&auml;ge gel&ouml;scht!"),count($this->tree->getKidsKids($item_id)))
                        . "<br>" . _("Wollen Sie diese Liste wirklich l&ouml;schen?") . "<br>"
                        . "<a href=\"" . $this->getSelf("cmd=DeleteItem&item_id=$item_id") . "\">"
                        . "<img " .makeButton("ja2","src") . tooltip(_("löschen"))
                        . " border=\"0\"></a>&nbsp;"
                        . "<a href=\"" . $this->getSelf("cmd=Cancel&item_id=$item_id") . "\">"
                        . "<img " .makeButton("nein","src") . tooltip(_("abbrechen"))
                        . " border=\"0\"></a>";
        return false;
    }

    function execCommandDeleteItem(){
        $item_id = $_REQUEST['item_id'];
        $deleted = 0;
        $item_name = $this->tree->tree_data[$item_id]['name'];
        $this->anchor = $this->tree->tree_data[$item_id]['parent_id'];
        if (!$this->tree->isElement($item_id) && $this->mode == "AssertDeleteItem"){
            $deleted = $this->tree->deleteList($item_id);
            if ($deleted){
                $this->msg[$this->anchor] = "msg§" . sprintf(_("Die Liste <b>%s</b> und alle Eintr&auml;ge (insgesamt %s) wurden gel&ouml;scht. "),htmlReady($item_name),$deleted-1);
            } else {
                $this->msg[$this->anchor] = "error§" . _("Fehler, die Liste konnte nicht gel&ouml;scht werden!");
            }
        } else {
            $deleted = $this->tree->deleteElement($item_id);
            if ($deleted){
                $this->msg[$this->anchor] = "msg§" . sprintf(_("Der Eintrag <b>%s</b> wurde gel&ouml;scht. "),htmlReady($item_name));
            } else {
                $this->msg[$this->anchor] = "error§" . _("Fehler, der Eintrag konnte nicht gel&ouml;scht werden!");
            }
        }
        $this->mode = "";
        $this->open_items[$this->anchor] = true;
        return true;
    }

    function execCommandNewItem(){
        $item_id = $_REQUEST['item_id'];
        $new_item_id = md5(uniqid("listblubb",1));
        $this->tree->tree_data[$new_item_id] = array(
            'chdate' => time(),
            'format'=> $this->tree->format_default,
            'user_id' => $GLOBALS['auth']->auth['uid'],
            'username' => $GLOBALS['auth']->auth['uname'],
            'fullname' => get_fullname($GLOBALS['auth']->auth['uid'],'no_title_short'),
            'visibility' => 0
            );
        $this->tree->storeItem($new_item_id, $item_id, _("Neue Liste"),$this->tree->getMaxPriority($item_id) + 1);
        $this->anchor = $new_item_id;
        $this->edit_item_id = $new_item_id;
        $this->open_ranges[$item_id] = true;
        $this->open_items[$new_item_id] = true;
        $this->msg[$new_item_id] = "info§" . _("Diese neue Liste wurde noch nicht gespeichert.");
        $this->mode = "NewItem";
        return false;
    }

    function execCommandCancel(){
        $item_id = $_REQUEST['item_id'];
        $this->mode = "";
        $this->anchor = $item_id;
        return false;
    }

    function getItemContent($item_id){
        $edit_content = false;
        if ($item_id == $this->edit_item_id){
            $edit_content = $this->getEditItemContent();
        }
        $content .= "\n<table width=\"90%\" cellpadding=\"2\" cellspacing=\"0\" align=\"center\" style=\"font-size:10pt\">";
        $content .= $this->getItemMessage($item_id);
        if (!$edit_content){
            if ($item_id == "root" && $this->tree->range_type != 'user'){
                $content .= "\n<form name=\"userlist_form\" action=\"" . $this->getSelf("cmd=CopyUserList") . "\" method=\"POST\">";
                $user_lists = $this->tree->GetListsByRange($GLOBALS['auth']->auth['uid']);
                $content .= "\n<tr><td class=\"steel1\" align=\"left\"><b>" . _("Pers&ouml;nliche Literaturlisten:")
                ."</b><br><br>\n<select name=\"user_list\" style=\"vertical-align:middle;width:70%;\">";
                if (is_array($user_lists)){
                    foreach ($user_lists as $list_id => $list_name){
                        $content .= "\n<option value=\"$list_id\">" . htmlReady($list_name) . "</option>";
                    }
                }
                $content .= "\n</select>&nbsp;&nbsp;<input type=\"image\" " . makeButton("kopieerstellen","src")
                . tooltip(_("Eine Kopie der ausgewählten Liste erstellen")) . " style=\"vertical-align:middle;\" border=\"0\"></td></tr></form>";
            }
            if ($this->tree->isElement($item_id)) {
                //$content .= "\n<tr><td class=\"steelkante\" align=\"left\" style=\"font-size:10pt\">" . _("Anmerkung:") ." </td></tr>";
                //$content .= "\n<tr><td class=\"steel1\" align=\"left\" style=\"font-size:10pt\">" . formatReady($this->tree->tree_data[$item_id]['note']) ." &nbsp;</td></tr>";
                $content .= "\n<tr><td class=\"steelgraulight\" align=\"left\" style=\"font-size:10pt;border-top: 1px solid black;border-left: 1px solid black;border-right: 1px solid black;\">" . _("Vorschau:") ."<br>";
                $content .= "\n<tr><td class=\"steel1\" align=\"left\" style=\"font-size:10pt;border-left: 1px solid black;border-right: 1px solid black;\">" . formatReady($this->tree->getFormattedEntry($item_id), false, true) ." </td></tr>";
            } elseif ($item_id != 'root') {
                $content .= "\n<tr><td class=\"steelgraulight\" align=\"left\" style=\"font-size:10pt;border-top: 1px solid black;border-left: 1px solid black;border-right: 1px solid black;\">" . _("Formatierung:") ." </td></tr>";
                $content .= "\n<tr><td class=\"steel1\" align=\"left\" style=\"font-size:10pt;border-left: 1px solid black;border-right: 1px solid black;\">" . htmlReady($this->tree->tree_data[$item_id]['format'],false,true) ." &nbsp;</td></tr>";
                $content .= "\n<tr><td class=\"steelgraulight\" align=\"left\" style=\"font-size:10pt;border-left: 1px solid black;border-right: 1px solid black;\">" . _("Sichtbarkeit:") . "</td></tr>";
                $content .= "\n<tr><td class=\"steel1\" align=\"left\" style=\"font-size:10pt;border-left: 1px solid black;border-right: 1px solid black;\">"
                . ($this->tree->tree_data[$item_id]['visibility']
                ? "<img src=\"".$GLOBALS['ASSETS_URL']."images/vote-icon-visible.gif\" border=\"0\" style=\"vertical-align:bottom;\">&nbsp;" . _("Sichtbar")
                : "<img src=\"".$GLOBALS['ASSETS_URL']."images/vote-icon-invisible.gif\" border=\"0\" style=\"vertical-align:bottom;\">&nbsp;" . _("Unsichtbar")) . " </td></tr>";
            }
        } else {
            $content .= "\n<tr><td class=\"steel1\" align=\"left\">$edit_content</td></tr>";
        }
        if (!$edit_content && $item_id != 'root'){
            $content .= "\n<tr><td class=\"steelgraulight\" align=\"right\" style=\"font-size:10pt;border-bottom: 1px solid black;border-left: 1px solid black;border-right: 1px solid black;\">" . _("Letzte &Auml;nderung:") . strftime(" %d.%m.%Y ", $this->tree->tree_data[$item_id]['chdate'])
                            . "(<a href=\"about.php?username=" . $this->tree->tree_data[$item_id]['username'] . "\">" . htmlReady($this->tree->tree_data[$item_id]['fullname']) . "</a>) </td></tr>";
        }
        $content .= "</table>";
        if (!$edit_content){
            $content .= "\n<table width=\"90%\" cellpadding=\"2\" cellspacing=\"2\" align=\"center\" style=\"font-size:10pt\">";
            $content .= "\n<tr><td align=\"center\">&nbsp;</td></tr>";
            $content .= "\n<tr><td align=\"center\">";
            if ($item_id == "root"){
                $content .= "<a href=\"" . $this->getSelf("cmd=NewItem&item_id=$item_id") . "\">"
                . "<img " .makeButton("neueliteraturliste","src") . tooltip(_("Eine neue Literaturliste anlegen."))
                . " border=\"0\"></a>&nbsp;";
            }
            if ($this->mode != "NewItem"){
                if ($item_id != "root"){
                    $content .= "<a href=\"" . $this->getSelf("cmd=EditItem&item_id=$item_id") . "\">"
                    . "<img " .makeButton("bearbeiten","src") . tooltip(_("Dieses Element bearbeiten"))
                    . " border=\"0\"></a>&nbsp;";
                    if ($this->tree->isElement($item_id)){
                        $cmd = "DeleteItem";
                        $content .= "<a href=\"admin_lit_element.php?_catalog_id={$this->tree->tree_data[$item_id]['catalog_id']}\">"
                        . "<img " .makeButton("details","src") . tooltip(_("Detailansicht dieses Eintrages ansehen."))
                        . " border=\"0\"></a>&nbsp;";
                    } else {
                        $cmd = "AssertDeleteItem";
                        $content .= "<a href=\"" . $this->getSelf("cmd=CopyList&item_id=$item_id") . "\">"
                        . "<img " .makeButton("kopieerstellen","src") . tooltip(_("Eine Kopie dieser Liste erstellen"))
                        . " border=\"0\"></a>&nbsp;";
                        $content .= "<a href=\"" . $this->getSelf("cmd=SortKids&item_id=$item_id") . "\">"
                        . "<img " .makeButton("sortieren","src") . tooltip(_("Elemente dieser Liste alphabetisch sortieren"))
                        . " border=\"0\"></a>&nbsp;";
                        $content .= '<a href="' . GetDownloadLink('', $this->tree->tree_data[$item_id]['name'] . '.txt', 5, 'force', $this->tree->range_id, $item_id) . '">'
                        . "<img " .makeButton("export","src") . tooltip(_("Export der Liste in EndNote kompatiblem Format"))
                        . " border=\"0\"></a>&nbsp;";
                    }
                    $content .= "<a href=\"" . $this->getSelf("cmd=$cmd&item_id=$item_id") . "\">"
                    . "<img " .makeButton("loeschen","src") . tooltip(_("Dieses Element löschen"))
                    . " border=\"0\"></a>&nbsp;";
                    if ($this->tree->isElement($item_id)){
                        if (!$this->clip_board->isInClipboard($this->tree->tree_data[$item_id]["catalog_id"])){
                            $content .= "<a href=\"". $this->getSelf("cmd=InClipboard&item_id=$item_id") . "\">"
                                        . "<img " . makeButton("merkliste","src") . " border=\"0\" " .
                                        tooltip(_("Eintrag in Merkliste aufnehmen")) . "></a>";
                        }
                    }
                }
            }
            $content .= "</td></tr></table>";
        }
        return $content;
    }

    function getItemHead($item_id){
        $head = "";
        $head .= parent::getItemHead($item_id);
        if ($this->tree->tree_data[$item_id]['parent_id'] == $this->start_item_id){
            $anzahl = " (" . $this->tree->getNumKids($item_id) . ")";
            $head .= ($this->open_items[$item_id]) ? "<b>" . $anzahl . "</b>" : $anzahl;
        }
        if ($item_id != $this->start_item_id && $item_id != $this->edit_item_id){
            $head .= "</td><td align=\"right\" valign=\"bottom\" nowrap class=\"printhead\">";
            if (!$this->tree->isFirstKid($item_id)){
                $head .= "<a href=\"". $this->getSelf("cmd=OrderItem&direction=up&item_id=$item_id") .
                "\"><img src=\"".$GLOBALS['ASSETS_URL']."images/move_up.gif\" hspace=\"4\" width=\"13\" height=\"11\" border=\"0\" " .
                tooltip(_("Element nach oben verschieben")) ."></a>";
            }
            if (!$this->tree->isLastKid($item_id)){
                $head .= "<a href=\"". $this->getSelf("cmd=OrderItem&direction=down&item_id=$item_id") .
                "\"><img src=\"".$GLOBALS['ASSETS_URL']."images/move_down.gif\" hspace=\"4\" width=\"13\" height=\"11\" border=\"0\" " .
                tooltip(_("Element nach unten verschieben")) . "></a>";
            }
            if ($this->tree->isElement($item_id)){
                $head .= ($this->clip_board->isInClipboard($this->tree->tree_data[$item_id]["catalog_id"]))
                        ? "<img src=\"".$GLOBALS['ASSETS_URL']."images/forum_fav.gif\" hspace=\"4\" width=\"12\" height=\"11\" border=\"0\" " .
                        tooltip(_("Dieser Eintrag ist bereits in ihrer Merkliste")) . ">"
                        :"<a href=\"". $this->getSelf("cmd=InClipboard&item_id=$item_id") .
                        "\"><img src=\"".$GLOBALS['ASSETS_URL']."images/forum_fav2.gif\" hspace=\"4\" width=\"12\" height=\"11\" border=\"0\" " .
                        tooltip(_("Eintrag in Merkliste aufnehmen")) . "></a>";
            } else {
                $head .= "<a href=\"". $this->getSelf("cmd=InClipboard&item_id=$item_id") .
                "\"><img src=\"".$GLOBALS['ASSETS_URL']."images/forum_fav2.gif\" hspace=\"4\" width=\"12\" height=\"11\" border=\"0\" " .
                tooltip(_("Komplette Liste in Merkliste aufnehmen")) . "></a>";
            }
            $head .= "&nbsp;";
        }
        return $head;
    }

    function getItemHeadPics($item_id){
        $head = $this->getItemHeadFrontPic($item_id);
        $head .= "\n<td  class=\"printhead\" nowrap  align=\"left\" valign=\"bottom\">";
        if (!$this->tree->isElement($item_id)){
            if ($this->tree->hasKids($item_id)){
                $head .= "<a href=\"";
                $head .= ($this->open_ranges[$item_id]) ? $this->getSelf("close_range={$item_id}") : $this->getSelf("open_range={$item_id}");
                $head .= "\"><img border=\"0\"  src=\"".$GLOBALS['ASSETS_URL']."images/";
                $head .= ($this->open_ranges[$item_id]) ? "cont_folder3.gif" : "cont_folder.gif";
                $head .= "\" ";
                $head .= (!$this->open_ranges[$item_id])? tooltip(_("Alle Unterelemente öffnen")) : tooltip(_("Alle Unterelemente schließen"));
                $head .= "></a>";
            } else {
                $head .= "<img src=\"".$GLOBALS['ASSETS_URL']."images/";
                $head .= ($this->open_items[$item_id]) ? "cont_folder4.gif" : "cont_folder2.gif";
                $head .= "\" " . tooltip(_("Dieses Element hat keine Unterelemente")) . "border=\"0\">";
            }
            if ($item_id != "root"){
                $head .= "<a href=\"" . $this->getSelf("cmd=ToggleVisibility&item_id={$item_id}") . "\"><img src=\"".$GLOBALS['ASSETS_URL']."images/";
                $head .= ($this->tree->tree_data[$item_id]['visibility']) ? "vote-icon-visible.gif" : "vote-icon-invisible.gif";
                $head .= "\" border=\"0\" " . tooltip(_("Sichtbarkeit ändern")) . "></a>";
            }
        } else {
            $head .= "<img src=\"".$GLOBALS['ASSETS_URL']."images/cont_lit.gif";
            $head .= "\" border=\"0\">";
        }
    return $head . "</td>";
    }

    function getEditItemContent(){
        $content .= "\n<form name=\"item_form\" action=\"" . $this->getSelf("cmd=InsertItem&item_id={$this->edit_item_id}") . "\" method=\"POST\">";
        $content .= "\n<input type=\"HIDDEN\" name=\"parent_id\" value=\"{$this->tree->tree_data[$this->edit_item_id]['parent_id']}\">";
        if ($this->tree->isElement($this->edit_item_id)){
            $content .= "\n<tr><td class=\"steelgraulight\"style=\"font-size:10pt;border-top: 1px solid black;border-left: 1px solid black;border-right: 1px solid black;\" ><b>". _("Anmerkung zu einem Eintrag bearbeiten:") . "</b></td></tr>";
            $edit_name = "note";
            $rows = 5;
            $content .= "<tr><td class=\"steel1\" align=\"center\" style=\"font-size:10pt;border-bottom: 1px solid black;border-left: 1px solid black;border-right: 1px solid black;\"><textarea name=\"edit_{$edit_name}\" style=\"width:100%\" rows=\"$rows\">" . $this->tree->tree_data[$this->edit_item_id][$edit_name]
                . "</textarea></td></tr>";
        } else {
            $content .= "\n<tr><td class=\"steelgraulight\" style=\"font-size:10pt;border-top: 1px solid black;border-left: 1px solid black;border-right: 1px solid black;\" ><b>". _("Name der Liste bearbeiten:") . "</b></td></tr>";
            $content .= "<tr><td class=\"steel1\" align=\"center\" style=\"font-size:10pt;border-left: 1px solid black;border-right: 1px solid black;\"><input type=\"text\" name=\"edit_name\" style=\"width:100%\" value=\"" . $this->tree->tree_data[$this->edit_item_id]['name']
                . "\"></td></tr>";

            $edit_name = "format";
            $rows = 2;
            $content .= "\n<tr><td class=\"steelgraulight\" style=\"font-size:10pt;border-left: 1px solid black;border-right: 1px solid black;\" ><b>". _("Formatierung der Liste bearbeiten:") . "</b>"
                    . "&nbsp;<img src=\"".$GLOBALS['ASSETS_URL']."images/info.gif\""
                    . tooltip($this->format_info, TRUE, TRUE) . " align=\"absmiddle\"></td></tr>";
            $content .= "<tr><td class=\"steel1\" align=\"center\" style=\"font-size:10pt;border-left: 1px solid black;border-right: 1px solid black;\"><textarea name=\"edit_{$edit_name}\" style=\"width:100%\" rows=\"$rows\">" . $this->tree->tree_data[$this->edit_item_id][$edit_name]
                . "</textarea></td></tr>";
            $content .= "\n<tr><td class=\"steelgraulight\" style=\"font-size:10pt;border-bottom: 1px solid black;;border-left: 1px solid black;border-right: 1px solid black;\" >
            <b>". _("Sichtbarkeit der Liste:") . "</b>&nbsp;&nbsp;&nbsp;
            <input type=\"radio\" name=\"edit_visibility\" value=\"1\" style=\"vertical-align:bottom\" "
            . (($this->tree->tree_data[$this->edit_item_id]['visibility']) ? "checked" : "") . ">" . _("Ja")
            . "&nbsp;<input type=\"radio\" name=\"edit_visibility\" value=\"0\" style=\"vertical-align:bottom\" "
            . ((!$this->tree->tree_data[$this->edit_item_id]['visibility']) ? "checked" : "") . ">" . _("Nein") . "</td></tr>";

        }
        $content .= "<tr><td class=\"steel1\">&nbsp;</td></tr><tr><td class=\"steel1\" align=\"center\"><input type=\"image\" "
                . makeButton("speichern","src") . tooltip("Einstellungen speichern") . " border=\"0\">"
                . "&nbsp;<a href=\"" . $this->getSelf("cmd=Cancel&item_id="
                . $this->edit_item_id) . "\">"
                . "<img " .makeButton("abbrechen","src") . tooltip(_("Aktion abbrechen"))
                . " border=\"0\"></a></td></tr>";
        $content .= "\n</form>";

        return $content;
    }

    function getItemMessage($item_id,$colspan = 1){
        $content = "";
        if ($this->msg[$item_id]){
            $msg = split("§",$this->msg[$item_id]);
            $pics = array('error' => 'x_small2.gif', 'info' => 'ausruf_small2.gif', 'msg' => 'ok_small2.gif');
            $content = "\n<tr><td colspan=\"{$colspan}\"><table border=\"0\" cellspacing=\"0\" cellpadding=\"2\" width=\"100%\" style=\"font-size:10pt\">
                        <tr><td align=\"center\" width=\"25\"><img width=\"22\" height=\"20\" src=\"".$GLOBALS['ASSETS_URL']."images/" . $pics[$msg[0]] . "\" ></td>
                        <td align=\"left\">" . $msg[1] . "</td></tr>
                        </table></td></tr><tr>";
        }
        return $content;
    }

    function getSelf($param = false){
        $url_params = "foo=" . DbView::get_uniqid();
        if ($this->mode) $url_params .= "&mode=" . $this->mode;
        if ($param) $url_params .= '&' . $param;
        return parent::getSelf($url_params);
    }
}
?>