<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
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

use Studip\Button, Studip\LinkButton;

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
class StudipLitListViewAdmin extends TreeView
{
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
        $this->clip_board = StudipLitClipBoard::GetInstance();
    }

    function parseCommand(){
        if (Request::quoted('mode'))
            $this->mode = Request::quoted('mode');
        if (Request::option('cmd')){
            $exec_func = "execCommand" . Request::option('cmd');
            if (method_exists($this,$exec_func)){
                if ($this->$exec_func()){
                    $this->tree->init();
                }
            }
        }
    }


    function execCommandEditItem(){
        $item_id = Request::option('item_id');
        $this->mode = "EditItem";
        $this->anchor = $item_id;
        $this->edit_item_id = $item_id;
        return false;
    }

    function execCommandInClipboard(){
        $item_id = Request::option('item_id');
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
        $item_id = Request::option('item_id');
        $parent_id = Request::option('parent_id');
        $user_id = $GLOBALS['auth']->auth['uid'];
        if ($this->mode != "NewItem"){
            if (Request::get('edit_note')){
                $affected_rows = $this->tree->updateElement(array('list_element_id' => $item_id, 'note' => Request::quoted('edit_note'), 'user_id' => $user_id));
                if ($affected_rows){
                    $this->msg[$item_id] = "msg§" . _("Anmerkung wurde ge&auml;ndert.");
                } else {
                    $this->msg[$item_id] = "info§" . _("Keine Ver&auml;nderungen vorgenommen.");
                }
             } else if ( Request::get('edit_format') ) {

                $affected_rows = $this->tree->updateList(array('list_id' => $item_id,'format' => Request::quoted('edit_format'),'name' => Request::quoted('edit_name'),'visibility' => Request::quoted('edit_visibility'), 'user_id' => $user_id));
                if ($affected_rows){
                    $this->msg[$item_id] = "msg§" . _("Listeneigenschaften wurden ge&auml;ndert.");
                } else {
                    $this->msg[$item_id] = "info§" . _("Keine Ver&auml;nderungen vorgenommen.");
                }
            }
        } else {
            $priority = $this->tree->getMaxPriority($parent_id) + 1;
            $affected_rows = $this->tree->insertList(array('list_id' => $item_id,'priority' => $priority, 'format' => Request::quoted('edit_format'),'visibility' => Request::quoted('edit_visibility'), 'name' => Request::quoted('edit_name'),'user_id' => $user_id));
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
        $item_id = Request::option('item_id');
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
        $list_id = Request::quoted('user_list');
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
        $item_id = Request::option('item_id');
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
        $direction = Request::quoted('direction');
        $item_id = Request::option('item_id');
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
        $item_id = Request::option('item_id');
        $kids = $this->tree->getKids($item_id);
        usort($kids, create_function('$a,$b',
                '$the_tree = TreeAbstract::GetInstance("StudipLitList", "'.$this->tree->range_id.'");
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
        $item_id = Request::option('item_id');
        $this->mode = "AssertDeleteItem";

        $template = $GLOBALS['template_factory']->open('shared/question');
        $question = _("Sie beabsichtigen, diese Liste inklusive aller Einträge zu löschen. ")
                    . sprintf(_("Es werden insgesamt %s Einträge gelöscht!"), count($this->tree->getKidsKids($item_id)))
                    . "\n" . _("Wollen Sie diese Liste wirklich löschen?");

        $template->set_attribute('approvalLink', URLHelper::getUrl($this->getSelf("cmd=DeleteItem&item_id=$item_id")));
        $template->set_attribute('disapprovalLink', URLHelper::getUrl($this->getSelf("cmd=Cancel&item_id=$item_id")));
        $template->set_attribute('question', $question);

        echo $template->render();

        return false;
    }

    function execCommandDeleteItem(){
        $item_id = Request::option('item_id');
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
        $item_id = Request::option('item_id');
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
        $item_id = Request::option('item_id');
        $this->mode = "";
        $this->anchor = $item_id;
        return false;
    }

    function getItemContent($item_id) {
        $edit_content = false;

        $content .= "\n<table width=\"90%\" cellpadding=\"2\" cellspacing=\"0\" align=\"center\" style=\"font-size:10pt\">";
        $content .= $this->getItemMessage($item_id);

        if ($item_id == $this->edit_item_id) {
            $edit_content = $this->getEditItemContent();
            $content .= "\n<tr><td class=\"table_row_even\" align=\"left\">$edit_content</td></tr>";
        }
        else {
            if ($item_id == "root" && $this->tree->range_type != 'user') {
                $content .= $this->getTableRowForRootInLiteratur();
            }

            if ($this->tree->isElement($item_id)) {
                $content .= $this->getTopRowForTableBox(_("Vorschau:"));
                $content .= $this->getLiteratureEntryRowForTableBox($item_id);
                $content .= $this->getBottomRowForTableBox($item_id);
            } elseif ($item_id != 'root') {
                $content .= $this->getTopRowForTableBox(_("Formatierung:"));
                $content .= $this->getFormatRowForTableBox($item_id);
                $content .= $this->getSubTitleRowForTableBox(_("Sichtbarkeit:"));
                $content .= $this->getVisibilityStatusRowForTableBox($item_id);
                $content .= $this->getBottomRowForTableBox($item_id);
            }
        }

        $content .= "</table>";


        if (!$edit_content) {
            $content .= "\n<table width=\"90%\" cellpadding=\"2\" cellspacing=\"2\" align=\"center\" style=\"font-size:10pt\">";
            $content .= "\n<tr><td align=\"center\">&nbsp;</td></tr>";
            $content .= "\n<tr><td align=\"center\">";

            if ($item_id == "root") {
                $content .= $this->getNewLiteratureButton($item_id);
            }
            elseif ($this->mode != "NewItem") {
                if ($this->tree->isElement($item_id)) {
                    $content .= $this->getEditLiteratureEntryButton($item_id);
                    $content .= $this->getDetailsButton($item_id);
                    $content .= $this->getDeleteButton($item_id, "DeleteItem");
                } else {
                    $content .= $this->getEditFormatingButton($item_id);
                    $content .= $this->getCopyListButton($item_id);
                    $content .= $this->getSortButton($item_id);
                    $content .= $this->getExportButton($item_id);
                    $content .= $this->getDeleteButton($item_id, "AssertDeleteItem");
                }

                if ($this->tree->isElement($item_id)) {
                    if (!$this->isInClipboard($item_id)) {
                        $content .= $this->getToClipboardButton($item_id);
                    }
                }
            }

            $content .= "</form></td></tr></table>";
        }

        return $content;
    }


    function getTableRowForRootInLiteratur() {
        $user_lists = $this->tree->GetListsByRange($GLOBALS['auth']->auth['uid']);

        $content .= "\n<tr><td class=\"table_row_even\" align=\"left\">";
        $content .= "\n<form name=\"userlist_form\" action=\"" . URLHelper::getLink($this->getSelf("cmd=CopyUserList")) . "\" method=\"POST\">";
        $content .= CSRFProtection::tokenTag();
        $content .= "<b>" . _("Pers&ouml;nliche Literaturlisten:")
                . "</b><br><br>\n<select name=\"user_list\" style=\"vertical-align:middle;width:70%;\">";
        if (is_array($user_lists)) {
            foreach ($user_lists as $list_id => $list_name) {
                $content .= "\n<option value=\"$list_id\">" . htmlReady($list_name) . "</option>";
            }
        }
        $content .= "\n</select>&nbsp;&nbsp;" .
                Button::create(_('Kopie erstellen'), array('title' => _('Eine Kopie der ausgewähkten Liste erstellen'))) .
                "</form></td></tr>";

        return $content;
    }


    function getTopRowForTableBox($title){
        $content .= "\n<tr><td class=\"table_row_odd\" align=\"left\" style=\"border-top: 1px solid black;border-left: 1px solid black;border-right: 1px solid black;\">";
        $content .= $title;
        $content .= " </td></tr>";

        return $content;
    }


    function getLiteratureEntryRowForTableBox($item_id){
        $content .= "\n<tr><td class=\"table_row_even\" align=\"left\" style=\"border-left: 1px solid black;border-right: 1px solid black;\">";
        $content .= formatReady($this->tree->getFormattedEntry($item_id), false, true);
        $content .= " </td></tr>";

        return $content;
    }


    function getFormatRowForTableBox($item_id){
        $content .= "\n<tr><td class=\"table_row_even\" align=\"left\" style=\"border-left: 1px solid black;border-right: 1px solid black;\">";
        $content .= htmlReady($this->tree->tree_data[$item_id]['format'], false, true);
        $content .= " &nbsp;</td></tr>";

        return $content;
    }

    function getVisibilityStatusRowForTableBox($item_id){
        $content .= "\n<tr><td class=\"table_row_even\" align=\"left\" style=\"border-left: 1px solid black;border-right: 1px solid black;\">";

        if ($this->tree->tree_data[$item_id]['visibility']){
            $content .= "<img src=\"" . $GLOBALS['ASSETS_URL'] . "images/icons/16/black/visibility-visible.png\" border=\"0\" style=\"vertical-align:bottom;\">&nbsp;" . _("Sichtbar");
        }
        else{
            $content .="<img src=\"" . $GLOBALS['ASSETS_URL'] . "images/icons/16/black/visibility-invisible.png\" border=\"0\" style=\"vertical-align:bottom;\">&nbsp;" . _("Unsichtbar");
        }

        $content .=  " </td></tr>";

        return $content;
    }


    function getSubTitleRowForTableBox($title){
        $content .= "\n<tr><td class=\"table_row_odd\" align=\"left\" style=\"border-left: 1px solid black;border-right: 1px solid black;\">";
        $content .= $title;
        $content .= "</td></tr>";

        return $content;
    }


    function getBottomRowForTableBox($item_id){
        $content .= "\n<tr><td class=\"table_row_odd\" align=\"right\" style=\"border-bottom: 1px solid black;border-left: 1px solid black;border-right: 1px solid black;\">";
        $content .= _("Letzte &Auml;nderung:");
        $content .= strftime(" %d.%m.%Y ", $this->tree->tree_data[$item_id]['chdate']);
        $content .= "(<a href=\"about.php?username=";
        $content .= $this->tree->tree_data[$item_id]['username'];
        $content .= "\">" . htmlReady($this->tree->tree_data[$item_id]['fullname']) . "</a>) </td></tr>";

        return $content;
    }

    function getNewLiteratureButton($item_id){
        $content = LinkButton::create(_('neue Literaturliste'),
                    URLHelper::getURL($this->getSelf('cmd=NewItem&item_id='.$item_id)),
                    array('title' => _('Eine neue Literaturliste anlegen')));
        $content .= "&nbsp;";

        return $content;
    }

    function getEditFormatingButton($item_id){
        $content = LinkButton::create(_('bearbeiten'),
                    URLHelper::getURL($this->getSelf('cmd=EditItem&item_id='.$item_id)),
                    array('title' => _("Dieses Element bearbeiten")));
        $content .= "&nbsp;";

        return $content;
    }

    function getEditLiteratureEntryButton($item_id){
        $content = LinkButton::create(_('Anmerkung'),
                    URLHelper::getURL($this->getSelf('cmd=EditItem&item_id='. $item_id)),
                    array('title' => _('Dieses Element bearbeiten')));
        $content .= "&nbsp;";

        return $content;
    }

    function getDetailsButton($item_id){
        $content = LinkButton::create(_('Details'),
                    'admin_lit_element.php?_catalog_id='.$this->tree->tree_data[$item_id]['catalog_id'],
                    array('title' => _('Detailansicht dieses Eintrages ansehen.')));
        $content .= "&nbsp;";

        return $content;
    }

    function getCopyListButton($item_id){
        $content = LinkButton::create(_('Kopie erstellen'),
                    URLHelper::getURL($this->getSelf('cmd=CopyList&item_id='.$item_id)),
                    array('title' => _('Eine Kopie dieser Liste erstellen')));
        $content .= "&nbsp;";

        return $content;
    }

    function getSortButton($item_id){
        $content = LinkButton::create(_('sortieren'),
                    URLHelper::getURL($this->getSelf('cmd=SortKids&item_id='.$item_id)),
                    array('title' => _('Elemente dieser Liste alphabetisch sortieren')));
        $content .= "&nbsp;";

        return $content;
    }

    function getExportButton($item_id){
        $content = LinkButton::create(_('Export'),
                    GetDownloadLink('', $this->tree->tree_data[$item_id]['name'] . '.txt', 5, 'force', $this->tree->range_id, $item_id),
                    array('title' => _('Export der Liste in EndNote kompatiblem Forma')));
        $content .= '&nbsp;';

        return $content;
    }

    function getDeleteButton($item_id, $cmd){
        $content = LinkButton::create(_('Löschen'),
                    URLHelper::getURL($this->getSelf('cmd='.$cmd.'&item_id='.$item_id)),
                    array('title' => _('Dieses Element löschen')));
        $content .= '&nbsp;';

        return $content;
    }

    function getToClipboardButton($item_id){
         $content = LinkButton::create(_('Merkliste'),
                    URLHelper::getURL($this->getSelf('cmd=InClipboard&item_id='.$item_id)),
                    array('title' => _('Eintrag in Merkliste aufnehmen')));
        $content .= '&nbsp;';

        return $content;
    }

    function isInClipboard($item_id){
        return $this->clip_board->isInClipboard($this->tree->tree_data[$item_id]["catalog_id"]);
    }

    function getItemHead($item_id)
    {
        $head = "";
        $head .= parent::getItemHead($item_id);
        if ($this->tree->tree_data[$item_id]['parent_id'] == $this->start_item_id){
            $anzahl = " (" . $this->tree->getNumKids($item_id) . ")";
            $head .= ($this->open_items[$item_id]) ? "<b>" . $anzahl . "</b>" : $anzahl;
        }
        if ($item_id != $this->start_item_id && $item_id != $this->edit_item_id){
            $head .= "</td><td align=\"right\" valign=\"bottom\" nowrap class=\"printhead\">";
            if (!$this->tree->isFirstKid($item_id)){
                $head .= " <a href=\"". URLHelper::getLink($this->getSelf("cmd=OrderItem&direction=up&item_id=$item_id")) .
                "\"><img src=\"" . Assets::image_path('icons/16/yellow/arr_2up.png') . "\" " .
                tooltip(_("Element nach oben verschieben")) ."></a>";
            }
            if (!$this->tree->isLastKid($item_id)){
                $head .= " <a href=\"". URLHelper::getLink($this->getSelf("cmd=OrderItem&direction=down&item_id=$item_id")) .
                "\"><img src=\"" . Assets::image_path('icons/16/yellow/arr_2down.png') . "\" " .
                tooltip(_("Element nach unten verschieben")) . "></a>";
            }
            if ($this->tree->isElement($item_id)){
                $head .= ($this->clip_board->isInClipboard($this->tree->tree_data[$item_id]["catalog_id"]))
                        ? "<img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/red/exclaim.png\" " .
                        tooltip(_("Dieser Eintrag ist bereits in Ihrer Merkliste")) . ">"
                        :"<a href=\"". URLHelper::getLink($this->getSelf("cmd=InClipboard&item_id=$item_id")) .
                        "\"><img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/exclaim.png\" " .
                        tooltip(_("Eintrag in Merkliste aufnehmen")) . "></a>";
            } else {
                $head .= " <a href=\"". URLHelper::getLink($this->getSelf("cmd=InClipboard&item_id=$item_id")) .
                "\"><img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/exclaim.png\" " .
                tooltip(_("Komplette Liste in Merkliste aufnehmen")) . "></a>";
            }
            $head .= "";
        }
        return $head;
    }

    function getItemHeadPics($item_id)
    {
        $head = $this->getItemHeadFrontPic($item_id);
        $head .= "\n<td  class=\"printhead\" nowrap  align=\"left\" valign=\"bottom\">";
        if (!$this->tree->isElement($item_id)){
            if ($this->tree->hasKids($item_id)){
                $head .= "<a href=\"";
                $head .= ($this->open_ranges[$item_id]) ? URLHelper::getLink($this->getSelf("close_range={$item_id}")) : URLHelper::getLink($this->getSelf("open_range={$item_id}"));
                $head .= "\"> <img src=\"".$GLOBALS['ASSETS_URL']."images/";
                $head .= ($this->open_ranges[$item_id]) ? "icons/16/blue/folder-full.png" : "icons/16/blue/folder-full.png";
                $head .= "\" ";
                $head .= (!$this->open_ranges[$item_id])? tooltip(_("Alle Unterelemente öffnen")) : tooltip(_("Alle Unterelemente schließen"));
                $head .= "></a>";
            } else {
                $head .= " <img src=\"".$GLOBALS['ASSETS_URL']."images/";
                $head .= ($this->open_items[$item_id]) ? "icons/16/blue/folder-full.png" : "icons/16/blue/folder-full.png";
                $head .= "\" " . tooltip(_("Dieses Element hat keine Unterelemente")) . ">";
            }
            if ($item_id != "root"){
                $head .= " <a href=\"" . URLHelper::getLink($this->getSelf("cmd=ToggleVisibility&item_id={$item_id}")) . "\"><img src=\"".$GLOBALS['ASSETS_URL']."images/";
                $head .= ($this->tree->tree_data[$item_id]['visibility']) ? "icons/16/blue/visibility-visible.png" : "icons/16/blue/visibility-invisible.png";
                $head .= "\" " . tooltip(_("Sichtbarkeit ändern")) . "></a>";
            }
        } else {
            $head .= Assets::img('icons/16/blue/literature.png');
        }
    return $head . "</td>";
    }

    function getEditItemContent(){
        $content .= "\n<form name=\"item_form\" action=\"" . URLHelper::getLink($this->getSelf("cmd=InsertItem&item_id={$this->edit_item_id}")) . "\" method=\"POST\">";
        $content .= CSRFProtection::tokenTag();
        $content .= "\n<input type=\"HIDDEN\" name=\"parent_id\" value=\"{$this->tree->tree_data[$this->edit_item_id]['parent_id']}\">";
        if ($this->tree->isElement($this->edit_item_id)){
            $content .= "\n<tr><td class=\"table_row_odd\"style=\"border-top: 1px solid black;border-left: 1px solid black;border-right: 1px solid black;\" ><b>". _("Anmerkung zu einem Eintrag bearbeiten:") . "</b></td></tr>";
            $edit_name = "note";
            $rows = 5;
            $content .= "<tr><td class=\"table_row_even\" style=\"border-bottom: 1px solid black;border-left: 1px solid black;border-right: 1px solid black;\"><textarea name=\"edit_{$edit_name}\" style=\"width:99%\" rows=\"$rows\">" . htmlReady($this->tree->tree_data[$this->edit_item_id][$edit_name])
                . "</textarea></td></tr>";
        } else {
            $content .= "\n<tr><td class=\"table_row_odd\" style=\"border-top: 1px solid black;border-left: 1px solid black;border-right: 1px solid black;\" ><b>". _("Name der Liste bearbeiten:") . "</b></td></tr>";
            $content .= "<tr><td class=\"table_row_even\" align=\"center\" style=\"border-left: 1px solid black;border-right: 1px solid black;\"><input type=\"text\" name=\"edit_name\" style=\"width:99%\" value=\"" . htmlReady($this->tree->tree_data[$this->edit_item_id]['name'])
                . "\"></td></tr>";

            $edit_name = "format";
            $rows = 2;
            $content .= "\n<tr><td class=\"table_row_odd\" style=\"border-left: 1px solid black;border-right: 1px solid black;\" ><b>". _("Formatierung der Liste bearbeiten:") . "</b>"
                    . "&nbsp;<img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/grey/info-circle.png\""
                    . tooltip($this->format_info, TRUE, TRUE) . " class=\"text-top\"></td></tr>";
            $content .= "<tr><td class=\"table_row_even\" align=\"center\" style=\"border-left: 1px solid black;border-right: 1px solid black;\"><textarea name=\"edit_{$edit_name}\" style=\"width:99%\" rows=\"$rows\">" . htmlReady($this->tree->tree_data[$this->edit_item_id][$edit_name])
                . "</textarea></td></tr>";
            $content .= "\n<tr><td class=\"table_row_odd\" style=\"border-bottom: 1px solid black;;border-left: 1px solid black;border-right: 1px solid black;\" >
            <b>". _("Sichtbarkeit der Liste:") . "</b>&nbsp;&nbsp;&nbsp;
            <input type=\"radio\" name=\"edit_visibility\" value=\"1\" style=\"vertical-align:bottom\" "
            . (($this->tree->tree_data[$this->edit_item_id]['visibility']) ? "checked" : "") . ">" . _("Ja")
            . "&nbsp;<input type=\"radio\" name=\"edit_visibility\" value=\"0\" style=\"vertical-align:bottom\" "
            . ((!$this->tree->tree_data[$this->edit_item_id]['visibility']) ? "checked" : "") . ">" . _("Nein") . "</td></tr>";

        }
        $content .= "<tr><td class=\"table_row_even\">&nbsp;</td></tr><tr><td class=\"table_row_even\" align=\"center\">" .
                Button::createAccept(_('Speichern'),
                        array('title' => _("Einstellungen speichern"))) .
                "&nbsp;" .
                LinkButton::createCancel(_('Abbrechen'),
                        URLHelper::getURL($this->getSelf("cmd=Cancel&item_id=".$this->edit_item_id)),
                        array('Aktion abbrechen' => _('Aktion abbrechen'))) .
                '</td></tr>';
        $content .= "\n</form>";

        return $content;
    }

    function getItemMessage($item_id,$colspan = 1){
        $content = "";
        if ($this->msg[$item_id]){
            $msg = explode("§",$this->msg[$item_id]);
            $pics = array('error' => 'icons/16/red/decline.png', 'info' => 'icons/16/black/info.png', 'msg' => 'icons/16/green/accept.png');
            $content = "\n<tr><td colspan=\"{$colspan}\"><table border=\"0\" cellspacing=\"0\" cellpadding=\"2\" width=\"100%\" style=\"font-size:10pt\">
                        <tr><td align=\"center\" width=\"25\"><img src=\"".$GLOBALS['ASSETS_URL']."images/" . $pics[$msg[0]] . "\" ></td>
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
