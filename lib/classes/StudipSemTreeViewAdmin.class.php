<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter005: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// StudipSemTreeViewAdmin.class.php
// Class to print out the seminar tree in administration mode
//
// Copyright (c) 2003 Andr� Noack <noack@data-quest.de>
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

require_once "lib/classes/StudipSemTree.class.php";
require_once "lib/classes/TreeView.class.php";
require_once "lib/functions.php";
require_once "config.inc.php";


/**
* class to print out the seminar tree (admin mode)
*
* This class prints out a html representation of the whole or part of the tree
*
* @access   public
* @author   Andr� Noack <noack@data-quest.de>
* @package
*/
class StudipSemTreeViewAdmin extends TreeView {

    var $admin_ranges = array();
    var $studienmodulmanagement = null;

    /**
    * constructor
    *
    * @access public
    */
    function StudipSemTreeViewAdmin($start_item_id = "root"){
        $this->start_item_id = ($start_item_id) ? $start_item_id : "root";
        $this->root_content = $GLOBALS['UNI_INFO'];
        parent::TreeView("StudipSemTree"); //calling the baseclass constructor
        $this->studienmodulmanagement = PluginEngine::getPlugin('StudienmodulManagement');
        URLHelper::bindLinkParam("_marked_item", $this->marked_item);
        $this->marked_sem =& $_SESSION['_marked_sem'];
        $this->parseCommand();
    }

    /**
    * manages the session variables used for the open/close thing
    *
    * @access   private
    */
    function handleOpenRanges(){

        $this->open_ranges[$this->start_item_id] = true;

        if (Request::option('close_item') || Request::option('open_item')){
            $toggle_item = (Request::option('close_item')) ? Request::option('close_item') : Request::option('open_item');
            if (!$this->open_items[$toggle_item]){
                $this->openItem($toggle_item);
            } else {
                unset($this->open_items[$toggle_item]);
            }
        }

        if (Request::option('item_id')) $this->anchor = Request::option('item_id');

    }

    function openItem($item_id){
        if ($this->tree->hasKids($item_id)){
            $this->start_item_id = $item_id;
            $this->open_ranges = null;
            $this->open_items = null;
            $this->open_items[$item_id] = true;
            $this->open_ranges[$item_id] = true;
        } else {
            $this->open_ranges[$this->tree->tree_data[$item_id]['parent_id']] = true;
            $this->open_items[$item_id] = true;
            $this->start_item_id = $this->tree->tree_data[$item_id]['parent_id'];
        }
        if ($this->start_item_id == "root"){
            $this->open_ranges = null;
            $this->open_ranges[$this->start_item_id] = true;
        }
        $this->anchor = $item_id;
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
        if ($this->mode == "MoveItem" || $this->mode == "CopyItem")
        $this->move_item_id = $this->marked_item;
    }

    function execCommandOrderItem(){
        $direction = Request::quoted('direction');
        $item_id = Request::option('item_id');
        $items_to_order = $this->tree->getKids($this->tree->tree_data[$item_id]['parent_id']);
        if (!$this->isParentAdmin($item_id) || !$items_to_order)
        return false;
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
        $view = new DbView();
        for ($i = 0; $i < count($items_to_order); ++$i){
            $view->params = array($i, $items_to_order[$i]);
            $rs = $view->get_query("view:SEM_TREE_UPD_PRIO");
        }
        $this->mode = "";
        $this->msg[$item_id] = "msg�" . (($direction == "up") ? _("Element wurde eine Position nach oben verschoben.") : _("Element wurde eine Position nach unten verschoben."));
        return true;
    }

    function execCommandNewItem(){
        $item_id = Request::option('item_id');
        if ($this->isItemAdmin($item_id)){
            $new_item_id = DbView::get_uniqid();
            $this->tree->storeItem($new_item_id,$item_id,_("Neuer Eintrag") , $this->tree->getNumKids($item_id) +1);
            $this->openItem($new_item_id);
            $this->edit_item_id = $new_item_id;
            if ($this->mode != "NewItem") $this->msg[$new_item_id] = "info�" . _("Hier k&ouml;nnen Sie die Bezeichnung und die Kurzinformation zu diesem Bereich eingeben.");
            $this->mode = "NewItem";
        }
        return false;
    }

    function execCommandEditItem(){
        $item_id = Request::option('item_id');
        if ($this->isItemAdmin($item_id) || $this->isParentAdmin($item_id)){
            $this->mode = "EditItem";
            $this->anchor = $item_id;
            $this->edit_item_id = $item_id;
            if($this->tree->tree_data[$this->edit_item_id]['studip_object_id']){
                $this->msg[$item_id] = "info�" . _("Hier k&ouml;nnen Sie die Kurzinformation zu diesem Bereich eingeben. Der Name kann nicht ge&auml;ndert werden, da es sich um eine Stud.IP-Einrichtung handelt.");
            } else {
                $this->msg[$item_id] = "info�" . _("Hier k&ouml;nnen Sie die Bezeichnung und die Kurzinformation zu diesem Bereich eingeben");
            }
        }
        return false;
    }

    function execCommandInsertItem(){
        $item_id = Request::option('item_id');
        $parent_id = Request::option('parent_id');
        $item_name = Request::quoted('edit_name');
        $item_info = Request::quoted('edit_info');
        $item_type = Request::int('edit_type');
        if ($this->mode == "NewItem" && $item_id){
            if ($this->isItemAdmin($parent_id)){
                $priority = count($this->tree->getKids($parent_id));
                if ($this->tree->InsertItem($item_id,$parent_id,$item_name,$item_info,$priority,null,$item_type)){
                    $this->mode = "";
                    $this->tree->init();
                    $this->openItem($item_id);
                    $this->msg[$item_id] = "msg�" . _("Dieser Bereich wurde neu eingef&uuml;gt.");
                }
            }
        }
        if ($this->mode == "EditItem"){
            if ($this->isParentAdmin($item_id)){
                if ($this->tree->UpdateItem($item_id, $item_name, $item_info, $item_type)){
                    $this->msg[$item_id] = "msg�" . _("Bereich wurde ge&auml;ndert.");
                } else {
                    $this->msg[$item_id] = "info�" . _("Keine Ver&auml;nderungen vorgenommen.");
                }
                $this->mode = "";
                $this->tree->init();
                $this->openItem($item_id);
            }
        }
        return false;
    }

    function execCommandAssertDeleteItem(){
        $item_id = Request::option('item_id');
        if ($this->isParentAdmin($item_id)){
            $this->mode = "AssertDeleteItem";
            $this->open_items[$item_id] = true;
            $this->msg[$item_id] = "info�" ._("Sie beabsichtigen diesen Bereich inklusive aller Unterbereiche zu l&ouml;schen. ")
            . sprintf(_("Es werden insgesamt %s Bereiche gel&ouml;scht!"),count($this->tree->getKidsKids($item_id))+1)
            . "<br>" . _("Wollen Sie diese Bereiche wirklich l&ouml;schen?") . "<br>"
            . LinkButton::createAccept(_('JA!'), 
                    URLHelper::getURL($this->getSelf('cmd=DeleteItem&item_id='.$item_id)),
                    array('title' => _('l�schen')))
            . "&nbsp;"
            . LinkButton::createCancel(_('NEIN!'), 
                    URLHelper::getURL($this->getSelf('cmd=Cancel&item_id='. $item_id)),
                    array('title' => _('abbrechen')));
        }
        return false;
    }

    function execCommandDeleteItem(){
        $item_id = Request::option('item_id');
        $item_name = $this->tree->tree_data[$item_id]['name'];
        if ($this->isParentAdmin($item_id) && $this->mode == "AssertDeleteItem"){
            $this->openItem($this->tree->tree_data[$item_id]['parent_id']);
            $items_to_delete = $this->tree->getKidsKids($item_id);
            $items_to_delete[] = $item_id;
            $deleted = $this->tree->DeleteItems($items_to_delete);
            if ($deleted['items']){
                $this->msg[$this->anchor] = "msg�" . sprintf(_("Der Bereich <b>%s</b> und alle Unterbereiche (insgesamt %s) wurden gel&ouml;scht. "),htmlReady($item_name),$deleted['items']);
            } else {
                $this->msg[$this->anchor] = "error�" . _("Fehler, es konnten keine Bereiche gel&ouml;scht werden !");
            }
            if ($deleted['entries']){
                $this->msg[$this->anchor] .= sprintf(_("<br>Es wurden %s Veranstaltungszuordnungen gel&ouml;scht. "),$deleted['entries']);
            }
            $this->mode = "";
        }
        return true;
    }

    function execCommandMoveItem(){
        $item_id = Request::option('item_id');
        $this->anchor = $item_id;
        $this->marked_item = $item_id;
        $this->mode = "MoveItem";
        return false;
    }

    function execCommandCopyItem(){
        $item_id = Request::option('item_id');
        $this->anchor = $item_id;
        $this->marked_item = $item_id;
        $this->mode = "CopyItem";
        return false;
    }

    function execCommandDoMoveItem(){
        $item_id = Request::option('item_id');
        $item_to_move = $this->marked_item;
        if ($this->mode == "MoveItem" && ($this->isItemAdmin($item_id) || $this->isParentAdmin($item_id))
        && ($item_to_move != $item_id) && ($this->tree->tree_data[$item_to_move]['parent_id'] != $item_id)
        && !$this->tree->isChildOf($item_to_move,$item_id)){
            $view = new DbView();
            $view->params = array($item_id, count($this->tree->getKids($item_id)), $item_to_move);
            $rs = $view->get_query("view:SEM_TREE_MOVE_ITEM");
            if ($rs->affected_rows()){
                $this->msg[$item_to_move] = "msg�" . _("Bereich wurde verschoben.");
            } else {
                $this->msg[$item_to_move] = "error�" . _("Keine Verschiebung durchgef�hrt.");
            }
        }
        $this->tree->init();
        $this->openItem($item_to_move);
        $this->mode = "";
        return false;
    }

    function execCommandDoCopyItem(){
        $item_id = Request::option('item_id');
        $item_to_copy = $this->marked_item;
        if ($this->mode == "CopyItem" && ($this->isItemAdmin($item_id) || $this->isParentAdmin($item_id))
        && ($item_to_copy != $item_id) && ($this->tree->tree_data[$item_to_copy]['parent_id'] != $item_id)
        && !$this->tree->isChildOf($item_to_copy,$item_id)){
            $items_to_copy = $this->tree->getKidsKids($item_to_copy);
            $seed = DbView::get_uniqid();
            $new_item_id = md5($item_to_copy . $seed);
            $parent_id = $item_id;
            $num_copy = $this->tree->InsertItem($new_item_id,$parent_id,
            mysql_escape_string($this->tree->tree_data[$item_to_copy]['name']),
            mysql_escape_string($this->tree->tree_data[$item_to_copy]['info']),
            $this->tree->getMaxPriority($parent_id)+1,
            ($this->tree->tree_data[$item_to_copy]['studip_object_id'] ? $this->tree->tree_data[$item_to_copy]['studip_object_id'] : null),
            $this->tree->tree_data[$item_to_copy]['type']);
            if($num_copy){
                if ($items_to_copy){
                    for ($i = 0; $i < count($items_to_copy); ++$i){
                        $num_copy += $this->tree->InsertItem(md5($items_to_copy[$i] . $seed),
                        md5($this->tree->tree_data[$items_to_copy[$i]]['parent_id'] . $seed),
                        mysql_escape_string($this->tree->tree_data[$items_to_copy[$i]]['name']),
                        mysql_escape_string($this->tree->tree_data[$items_to_copy[$i]]['info']),
                        $this->tree->tree_data[$items_to_copy[$i]]['priority'],
                        ($this->tree->tree_data[$items_to_copy[$i]]['studip_object_id'] ? $this->tree->tree_data[$items_to_copy[$i]]['studip_object_id'] : null),
                        $this->tree->tree_data[$item_to_copy]['type']);
                    }
                }
                $items_to_copy[] = $item_to_copy;
                for ($i = 0; $i < count($items_to_copy); ++$i){
                    $sem_entries = $this->tree->getSemIds($items_to_copy[$i], false);
                    if ($sem_entries){
                        for ($j = 0; $j < count($sem_entries); ++$j){
                            $num_entries += $this->tree->InsertSemEntry(md5($items_to_copy[$i] . $seed) , $sem_entries[$j]);
                        }
                    }
                }
            }

            if ($num_copy){
                $this->msg[$new_item_id] = "msg�" . sprintf(_("%s Bereich(e) wurde(n) kopiert."), $num_copy) . "<br>"
                . sprintf(_("%s Veranstaltungszuordnungen wurden kopiert"), $num_entries);
            } else {
                $this->msg[$new_item_id] = "error�" . _("Keine Kopie durchgef�hrt.");
            }
            $this->tree->init();
            $this->openItem($new_item_id);
        }
        $this->mode = "";
        return false;
    }

    function execCommandInsertFak(){
        if($this->isItemAdmin("root") && Request::quoted('insert_fak')){
            $view = new DbView();
            $item_id = $view->get_uniqid();
            $view->params = array($item_id,'root','',$this->tree->getNumKids('root')+1,'',Request::quoted('insert_fak'),0);
            $rs = $view->get_query("view:SEM_TREE_INS_ITEM");
            if ($rs->affected_rows()){
                $this->tree->init();
                $this->openItem($item_id);
                $this->msg[$item_id] = "msg�" . _("Dieser Bereich wurde neu eingef&uuml;gt.");
                return false;
            }
        }
        return false;
    }

    function execCommandMarkSem(){
        $item_id = Request::option('item_id');
        $marked_sem_array =  Request::quotedArray('marked_sem');
        $marked_sem = array_values(array_unique($marked_sem_array));
        $sem_aktion = explode("_",Request::quoted('sem_aktion'));
        if (($sem_aktion[0] == 'mark' || $sem_aktion[1] == 'mark') && count($marked_sem)){
            $count_mark = 0;
            for ($i = 0; $i < count($marked_sem); ++$i){
                if (!isset($this->marked_sem[$marked_sem[$i]])){
                    ++$count_mark;
                    $this->marked_sem[$marked_sem[$i]] = true;
                }
            }
            if ($count_mark){
                $this->msg[$item_id] = "msg�" . sprintf(_("Es wurde(n) %s Veranstaltung(en) der Merkliste hinzugef&uuml;gt."),$count_mark);
            }
        }
        if ($this->isItemAdmin($item_id)){
            if (($sem_aktion[0] == 'del' || $sem_aktion[1] == 'del') && count($marked_sem)){
                $not_deleted = array();
                foreach($marked_sem as $key => $seminar_id){
                    $seminar = new Seminar($seminar_id);
                    if(count($seminar->getStudyAreas()) == 1){
                        $not_deleted[] = $seminar->getName();
                        unset($marked_sem[$key]);
                    }
                }
                if ($this->msg[$item_id]){
                    $this->msg[$item_id] .= "<br>";
                } else {
                    $this->msg[$item_id] = "msg�";
                }
                if(count($marked_sem)){
                    $count_del = $this->tree->DeleteSemEntries($item_id, $marked_sem);
                    $this->msg[$item_id] .= sprintf(_("%s Veranstaltungszuordnung(en) wurde(n) aufgehoben."),$count_del);
                }
                if(count($not_deleted)){
                    $this->msg[$item_id] .= '<br>'
                                         . sprintf(_("F�r folgende Veranstaltungen wurde die Zuordnung nicht aufgehoben, da es die einzige Zuordnung ist: %s")
                                         , '<br>'.htmlready(join(', ', $not_deleted)));
                }
            }
            $this->anchor = $item_id;
            $this->open_items[$item_id] = true;
            return true;
        }
        return false;
    }

    function execCommandCancel(){
        $item_id = Request::option('item_id');
        $this->mode = "";
        $this->anchor = $item_id;
        return false;
    }

    function showSemTree(){
        ?>
        <script type="text/javascript">
        function invert_selection(the_form){
            my_elements = document.forms[the_form].elements['marked_sem[]'];
            if(!my_elements.length){
                if(my_elements.checked)
                my_elements.checked = false;
                else
                my_elements.checked = true;
            } else {
                for(i = 0; i < my_elements.length; ++i){
                    if(my_elements[i].checked)
                    my_elements[i].checked = false;
                    else
                    my_elements[i].checked = true;
                }
            }
        }
        </script>
        <?
        echo "\n<table width=\"99%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
        if ($this->start_item_id != 'root'){
            echo "\n<tr><td class=\"table_row_odd\" align=\"left\" valign=\"top\"><div style=\"font-size:10pt;margin-left:10px\"><b>"
            . _("Studienbereiche:") . "</b><br>" .  $this->getSemPath()
            . "</div></td></tr>";
        }
        echo "\n<tr><td class=\"blank\"  align=\"left\" valign=\"top\">";
        $this->showTree($this->start_item_id);
        echo "\n</td></tr></table>";
    }

    function getSemPath(){
        //$ret = "<a href=\"" . parent::getSelf("start_item_id=root") . "\">" .htmlReady($this->tree->root_name) . "</a>";
        if ($parents = $this->tree->getParents($this->start_item_id)){
            for($i = count($parents)-1; $i >= 0; --$i){
                $ret .= " &gt; <a class=\"tree\" href=\"" . URLHelper::getLink($this->getSelf("start_item_id={$parents[$i]}&open_item={$parents[$i]}",false))
                . "\">" .htmlReady($this->tree->tree_data[$parents[$i]]["name"]) . "</a>";
            }
        }
        return $ret;
    }

    /**
    * returns html for the icons in front of the name of the item
    *
    * @access   private
    * @param    string  $item_id
    * @return   string
    */
    function getItemHeadPics($item_id){
        $head = $this->getItemHeadFrontPic($item_id);
        $head .= "\n<td  class=\"printhead\" nowrap  align=\"left\" valign=\"bottom\">";
        if ($this->tree->hasKids($item_id)){
            $head .= "<img class=\"text-top\" src=\"".$GLOBALS['ASSETS_URL']."images/";
            $head .= ($this->open_ranges[$item_id]) ? "icons/16/blue/folder-full.png" : "icons/16/blue/folder-full.png";
            $head .= "\" ";
            $head .= (!$this->open_ranges[$item_id])? tooltip(_("Alle Unterelement �ffnen")) : tooltip(_("Alle Unterelemente schliessen"));
            $head .= ">";
        } else {
            $head .= "<img class=\"text-top\" src=\"".$GLOBALS['ASSETS_URL']."images/";
            $head .= ($this->open_items[$item_id]) ? "icons/16/blue/folder-empty.png" : "icons/16/blue/folder-empty.png";
            $head .= "\" " . tooltip(_("Dieses Element hat keine Unterelemente")) . " >";
        }
        return $head . "</td>";
    }

    function getItemContent($item_id){
        if ($item_id == $this->edit_item_id ) return $this->getEditItemContent();
        if(!$GLOBALS['SEM_TREE_TYPES'][$this->tree->getValue($item_id, 'type')]['editable']){
            $is_not_editable = true;
            $this->msg[$item_id] = "info�" . sprintf(_("Der Typ dieses Elementes verbietet eine Bearbeitung."));
        }
        if ($item_id == $this->move_item_id){
            $this->msg[$item_id] = "info�" . sprintf(_("Dieses Element wurde zum Verschieben / Kopieren markiert. Bitte w&auml;hlen Sie ein Einf�gesymbol %s aus, um das Element zu verschieben / kopieren."), Assets::img("icons/16/yellow/arr_2right", array('alt' => "Einf�gesymbol", 'title' => "Einf�gesymbol")));
        }
        $content = "\n<table width=\"90%\" cellpadding=\"2\" cellspacing=\"2\" align=\"center\" style=\"font-size:10pt;\">";
        $content .= $this->getItemMessage($item_id);
        $content .= "\n<tr><td style=\"font-size:10pt;\" align=\"center\">";
        if(!$is_not_editable){
        if ($this->isItemAdmin($item_id) ){
            $content .= LinkButton::create(_('Neues Objekt'), 
                    URLHelper::getURL($this->getSelf('cmd=NewItem&item_id='.$item_id)), 
                    array('title' => _('Innerhalb dieser Ebene ein neues Element einf�gen'))) . '&nbsp;';
        }
        if ($this->isParentAdmin($item_id) && $item_id != "root"){
            $content .= LinkButton::create(_('Bearbeiten'), 
                    URLHelper::getURL($this->getSelf('cmd=EditItem&item_id=' . $item_id)), 
                    array('title' => 'Dieses Element bearbeiten')) . '&nbsp;';

            $content .= LinkButton::create(_('L�schen'), 
                    URLHelper::getURL($this->getSelf('cmd=AssertDeleteItem&item_id=' . $item_id)),
                    array('title' => _('Dieses Element l�schen'))) . '&nbsp;';
            
            if ($this->move_item_id == $item_id && ($this->mode == "MoveItem" || $this->mode == "CopyItem")){
                $content .= LinkButton::create(_('Abbrechen'), 
                        URLHelper::getURL($this->getSelf('cmd=Cancel&item_id=' . $item_id)),
                        array('title' => _('Verschieben / Kopieren abbrechen'))) . '&nbsp;';
            } else {
                $content .= LinkButton::create(_('Verschieben'), 
                        URLHelper::getURL($this->getSelf('cmd=MoveItem&item_id='.$item_id)),
                        array('title' => _('Dieses Element in eine andere Ebene verschieben'))) . '&nbsp;';
                $content .= LinkButton::create(_('Kopieren'),
                        URLHelper::getURL($this->getSelf('cmd=CopyItem&item_id='.$item_id)),
                        array('title' => _('Dieses Element in eine andere Ebene kopieren')));
            }
        }
        }
        if ($item_id == 'root' && $this->isItemAdmin($item_id)){
            $view = new DbView();
            $rs = $view->get_query("view:SEM_TREE_GET_LONELY_FAK");
            $content .= "\n<p><form action=\"" . URLHelper::getLink($this->getSelf("cmd=InsertFak")) . "\" method=\"post\">"
                . CSRFProtection::tokenTag()
                . _("Stud.IP Fakult&auml;t einf&uuml;gen:")
                . "&nbsp;\n<select style=\"width:200px;vertical-align:middle;\" name=\"insert_fak\">";
            while($rs->next_record()){
                $content .= "\n<option value=\"" . $rs->f("Institut_id") . "\">" . htmlReady(my_substr($rs->f("Name"),0,50)) . "</option>";
            }
            $content .= "</select>&nbsp;" . Button::create(_('Eintragen'), array('title' => _("Fakult�t einf�gen"))) . "</form></p>";
        }
        $content .= "</td></tr></table>";

        $content .= "\n<table border=\"0\" width=\"90%\" cellpadding=\"2\" cellspacing=\"0\" align=\"center\" style=\"font-size:10pt\">";
        if ($item_id == "root"){
            $content .= "\n<tr><td  class=\"table_header_bold\" align=\"left\" style=\"font-size:10pt;\">" . htmlReady($this->tree->root_name) ." </td></tr>";
            $content .= "\n<tr><td  class=\"table_row_even\" align=\"left\" style=\"font-size:10pt;\">" . htmlReady($this->root_content) ." </td></tr>";
            $content .= "\n</table>";
            return $content;
        }
        if ($this->tree->tree_data[$item_id]['info']){
            $content .= "\n<tr><td style=\"font-size:10pt;\" class=\"table_row_even\" align=\"left\" colspan=\"3\">";
            $content .= formatReady($this->tree->tree_data[$item_id]['info']) . "</td></tr>";
        }
        if(is_object($this->studienmodulmanagement) && $this->tree->isModuleItem($item_id)){
            $content .= "\n<tr><td class=\"blank\" align=\"left\" colspan=\"3\">";
            $content .=  _("Modulbeschreibung:") . '</td></tr>';
            $content .= "\n<tr><td class=\"blank\" align=\"left\" colspan=\"3\">";
            $content .= htmlReady($this->studienmodulmanagement->getModuleDescription($item_id));
            $content .= "</td></tr>";
        }
        $content .= "<tr><td style=\"font-size:10pt;\"colspan=\"3\">&nbsp;</td></tr>";
        if ($this->tree->getNumEntries($item_id) - $this->tree->tree_data[$item_id]['lonely_sem']){
            $content .= "<tr><td class=\"table_row_even\" style=\"font-size:10pt;\" align=\"left\" colspan=\"3\"><b>" . _("Eintr&auml;ge auf dieser Ebene:");
            $content .= "</b>\n</td></tr>";
            $entries = $this->tree->getSemData($item_id);
            $content .= $this->getSemDetails($entries,$item_id);
        } else {
            $content .= "\n<tr><td class=\"table_row_even\" style=\"font-size:10pt;\" colspan=\"3\">" . _("Keine Eintr&auml;ge auf dieser Ebene vorhanden!") . "</td></tr>";
        }
        if ($this->tree->tree_data[$item_id]['lonely_sem']){
            $content .= "<tr><td class=\"table_row_even\" align=\"left\" style=\"font-size:10pt;\" colspan=\"3\"><b>" . _("Nicht zugeordnete Veranstaltungen auf dieser Ebene:");
            $content .= "</b>\n</td></tr>";
            $entries = $this->tree->getLonelySemData($item_id);
            $content .= $this->getSemDetails($entries,$item_id,true);
        }
        $content .= "</table>";
        return $content;
    }

    function getSemDetails($snap, $item_id, $lonely_sem = false){
        $form_name = DbView::get_uniqid();
        $content = "<form name=\"$form_name\" action=\"" . URLHelper::getLink($this->getSelf("cmd=MarkSem")) ."\" method=\"post\">
        <input type=\"hidden\" name=\"item_id\" value=\"$item_id\">";
        $content .= CSRFProtection::tokenTag();
        $group_by_data = $snap->getGroupedResult("sem_number", "seminar_id");
        $sem_data = $snap->getGroupedResult("seminar_id");
        $group_by_duration = $snap->getGroupedResult("sem_number_end", array("sem_number","seminar_id"));
        foreach ($group_by_duration as $sem_number_end => $detail){
            if ($sem_number_end != -1 && ($detail['sem_number'][$sem_number_end] && count($detail['sem_number']) == 1)){
                continue;
            } else {
                foreach ($detail['seminar_id'] as $seminar_id => $foo){
                    $start_sem = key($sem_data[$seminar_id]["sem_number"]);
                    if ($sem_number_end == -1){
                        $sem_number_end = count($this->tree->sem_dates)-1;
                    }
                    for ($i = $start_sem; $i <= $sem_number_end; ++$i){
                        if ($group_by_data[$i] && !$tmp_group_by_data[$i]){
                            foreach($group_by_data[$i]['seminar_id'] as $id => $bar){
                                $tmp_group_by_data[$i]['seminar_id'][$id] = key($sem_data[$id]["Name"]);
                            }
                        }
                        $tmp_group_by_data[$i]['seminar_id'][$seminar_id] = key($sem_data[$seminar_id]["Name"]);
                    }
                }
            }
        }
        if (is_array($tmp_group_by_data)){
            foreach ($tmp_group_by_data as $start_sem => $detail){
                $group_by_data[$start_sem] = $detail;
            }
        }

        foreach ($group_by_data as $group_field => $sem_ids){
            foreach ($sem_ids['seminar_id'] as $seminar_id => $foo){
                $name = strtolower(key($sem_data[$seminar_id]["Name"]));
                $name = str_replace("�","ae",$name);
                $name = str_replace("�","oe",$name);
                $name = str_replace("�","ue",$name);
                $group_by_data[$group_field]['seminar_id'][$seminar_id] = $name;
            }
            uasort($group_by_data[$group_field]['seminar_id'], 'strnatcmp');
        }

        krsort($group_by_data, SORT_NUMERIC);

        foreach ($group_by_data as $sem_number => $sem_ids){
            $content .= "\n<tr><td class=\"content_seperator\" colspan=\"3\" style=\"font-size:10pt;\" >" . $this->tree->sem_dates[$sem_number]['name'] . "</td></tr>";
            if (is_array($sem_ids['seminar_id'])){
                while(list($seminar_id,) = each($sem_ids['seminar_id'])){
                    $sem_name = key($sem_data[$seminar_id]["Name"]);
                    $sem_number_start = key($sem_data[$seminar_id]["sem_number"]);
                    $sem_number_end = key($sem_data[$seminar_id]["sem_number_end"]);
                    if ($sem_number_start != $sem_number_end){
                        $sem_name .= " (" . $this->tree->sem_dates[$sem_number_start]['name'] . " - ";
                        $sem_name .= (($sem_number_end == -1) ? _("unbegrenzt") : $this->tree->sem_dates[$sem_number_end]['name']) . ")";
                    }
                    $content .= "<tr><td class=\"table_row_even\" width=\"1%\"><input type=\"checkbox\" name=\"marked_sem[]\" value=\"$seminar_id\" style=\"vertical-align:middle\">
                    </td><td class=\"table_row_even\" style=\"font-size:10pt;\"><a href=\"details.php?sem_id=". $seminar_id
                    ."&send_from_search=true&send_from_search_page=" . rawurlencode(URLHelper::getLink($this->getSelf())) . "\">" . htmlReady($sem_name) . "</a>
                    </td><td class=\"table_row_even\" align=\"right\" style=\"font-size:10pt;\">(";
                    $doz_name = array_keys($sem_data[$seminar_id]['doz_name']);
                    $doz_uname = array_keys($sem_data[$seminar_id]['doz_uname']);
                    if (is_array($doz_name)){
                        uasort($doz_name, 'strnatcasecmp');
                        $i = 0;
                        foreach ($doz_name as $index => $value){
                            if ($i == 4){
                                $content .= "... <a href=\"details.php?sem_id=". $seminar_id
                                ."&send_from_search=true&send_from_search_page=" . rawurlencode(URLHelper::getLink($this->getSelf())) . "\">("._("mehr").")</a>";
                                break;
                            }
                            $content .= "<a href=\"dispatch.php/profile?username=" . $doz_uname[$index] ."\">" . htmlReady($value) . "</a>";
                            if($i != count($doz_name)-1){
                                $content .= ", ";
                            }
                            ++$i;
                        }
                    }
                    $content .= ") </td></tr>";
                }
            }
        }
        $content .= "<tr><td class=\"table_row_even\" colspan=\"2\">"
            . LinkButton::create(_('Ausw�hlen'), array('title' => _('Auswahl umkeheren'), 'onClick' => 'invert_selection(\'$form_name\');return false;'))
            . "</td><td class=\"table_row_even\" align=\"right\">
        <select name=\"sem_aktion\" style=\"font-size:8pt;vertical-align:bottom;\" " . tooltip(_("Aktion ausw�hlen"),true) . ">
        <option value=\"mark\">" . _("in Merkliste &uuml;bernehmen") . "</option>";
        if (!$lonely_sem && $this->isItemAdmin($item_id)){
            $content .= "<option value=\"del_mark\">" . _("l&ouml;schen und in Merkliste &uuml;bernehmen") . "</option>
            <option value=\"del\">" . _("l&ouml;schen") . "</option>";
        }
        $content .= "</select>" . Button::createAccept(_('OK'), array('title' => _("Gew�hlte Aktion starten"))) 
                 . "</td></tr> </form>";
        return $content;
    }

    function getEditItemContent(){
        $content = "\n<form name=\"item_form\" action=\"" . URLHelper::getLink($this->getSelf("cmd=InsertItem&item_id={$this->edit_item_id}")) . "\" method=\"POST\">";
        $content .= CSRFProtection::tokenTag();
        $content .= "\n<input type=\"HIDDEN\" name=\"parent_id\" value=\"{$this->tree->tree_data[$this->edit_item_id]['parent_id']}\">";
        $content .= "\n<table width=\"90%\" border =\"0\" style=\"border-style: solid; border-color: #000000;  border-width: 1px;font-size: 10pt;\" cellpadding=\"2\" cellspacing=\"2\" align=\"center\">";
        $content .=  $this->getItemMessage($this->edit_item_id,2);
        $content .= "\n<tr><td colspan=\"2\" class=\"content_seperator\" ><b>". _("Bereich editieren") . "</b></td></tr>";
        $content .= "\n<tr><td class=\"table_row_even\" width=\"1%\">". _("Name des Elements:") . "</td><td class=\"table_row_even\" width=\"99%\">";
        if($this->tree->tree_data[$this->edit_item_id]['studip_object_id']){
            $content .= htmlReady($this->tree->tree_data[$this->edit_item_id]['name']);
        } else {
            $content .= "<input type=\"TEXT\" name=\"edit_name\" size=\"50\" style=\"width:100%\" value=\"" . htmlReady($this->tree->tree_data[$this->edit_item_id]['name']) . "\">";
        }
        $content .= "</td></tr>";
        if (count($GLOBALS['SEM_TREE_TYPES']) > 1) {
            $content .= "<tr><td class=\"table_row_even\"  width=\"1%\">" . _("Typ des Elements:") . "</td><td class=\"table_row_even\">"
            . "<select name=\"edit_type\">";
            foreach($GLOBALS['SEM_TREE_TYPES'] as $sem_tree_type_key => $sem_tree_type){
                if($sem_tree_type['editable']){
                    $selected = $sem_tree_type_key == $this->tree->getValue($this->edit_item_id, 'type') ? 'selected' : '';
                    $content .= '<option value="'.htmlReady($sem_tree_type_key).'"'.$selected.'>';
                    $content .= htmlReady($sem_tree_type['name'] ? $sem_tree_type['name'] : $sem_tree_type_key);
                    $content .= '</option>';
                }
            }
            $content .= "</select></td></tr>";
        } else { # Auswahl ausblenden, wenn nur ein Typ vorhanden
            $content .= "<input type='hidden' name='edit_type' value='0'>";
        }
        $buttonlink_id = ($this->mode == "NewItem") ? $this->tree->tree_data[$this->edit_item_id]['parent_id'] : $this->edit_item_id;
        $content .= "<tr><td class=\"table_row_even\"  width=\"1%\">" . _("Infotext:") . "</td><td class=\"table_row_even\">"
        . "<textarea style=\"width:100%\" rows=\"5\" name=\"edit_info\" wrap=\"virtual\">" .htmlReady($this->tree->tree_data[$this->edit_item_id]['info']) . "</textarea>"
        . "</td></tr><tr><td class=\"table_row_even\" align=\"right\" valign=\"top\" colspan=\"2\">"
        . Button::createAccept(_('Absenden'), array('title' => _('Einstellungen �bernehmen')))       
        . "&nbsp;"
        . LinkButton::createCancel(_('Abbrechen'), 
                URLHelper::getURL($this->getSelf('cmd=Cancel&item_id='.$buttonlink_id)) , 
                array('title' => _('Aktion abbrechen')))
        . "</td></tr>";

        $content .= "\n</table></form>";

        return $content;
    }


    function isItemAdmin($item_id){
        global $auth;
        if ($auth->auth['perm'] == "root"){
            return true;
        }
        if (!($admin_id = $this->tree->tree_data[$this->tree->getAdminRange($item_id)]['studip_object_id'])){
            return false;
        }
        if(!isset($this->admin_ranges[$admin_id])){
            $view = new DbView();
            $view->params[0] = $auth->auth['uid'];
            $view->params[1] = $admin_id;
            $rs = $view->get_query("view:SEM_TREE_CHECK_PERM");
            $this->admin_ranges[$admin_id] = ($rs->next_record()) ? true : false;
        }
        if ($this->admin_ranges[$admin_id]){
            return true;
        } else {
            return false;
        }
    }

    function isParentAdmin($item_id){
        return $this->isItemAdmin($this->tree->tree_data[$item_id]['parent_id']);
    }

    function getItemHead($item_id){
        $head = "";
        if (($this->mode == "MoveItem" || $this->mode == "CopyItem") && ($this->isItemAdmin($item_id) || $this->isParentAdmin($item_id))
        && ($this->move_item_id != $item_id) && ($this->tree->tree_data[$this->move_item_id]['parent_id'] != $item_id)
        && !$this->tree->isChildOf($this->move_item_id,$item_id)){
            $head .= "<a href=\"" . URLHelper::getLink($this->getSelf("cmd=Do" . $this->mode . "&item_id=$item_id")) . "\">"
            . Assets::img("icons/16/yellow/arr_2right.png", array('alt' => _("An dieser Stelle einf�gen"), 'title' => _("An dieser Stelle einf�gen")))."</a>&nbsp;";
        }
        $head .= parent::getItemHead($item_id);
        if ($item_id != "root"){
            $head .= " (" . $this->tree->getNumEntries($item_id,true) . ") " ;
        }
        if ($item_id != $this->start_item_id && $this->isParentAdmin($item_id) && $item_id != $this->edit_item_id){
            $head .= "</td><td nowrap align=\"right\" valign=\"bottom\" class=\"printhead\">";
            if (!$this->tree->isFirstKid($item_id)){
                $head .= "<a href=\"". URLHelper::getLink($this->getSelf("cmd=OrderItem&direction=up&item_id=$item_id")) .
                "\">" .  Assets::img('icons/16/yellow/arr_2up.png', array('class' => 'text-top', 'title' => _("Element nach oben"))) .
                "</a>";
            }
            if (!$this->tree->isLastKid($item_id)){
                $head .= "<a href=\"". URLHelper::getLink($this->getSelf("cmd=OrderItem&direction=down&item_id=$item_id")) .
                "\">" . Assets::img('icons/16/yellow/arr_2down.png', array('class' => 'text-top', 'title' => _("Element nach unten"))) .
                "</a>";
            }
            $head .= "&nbsp;";
        }
        return $head;
    }

    function getItemMessage($item_id,$colspan = 1){
        $content = "";
        if ($this->msg[$item_id]){
            $msg = explode("�",$this->msg[$item_id]);
            $pics = array('error' => 'icons/16/red/decline.png', 'info' => 'icons/16/grey/exclaim.png', 'msg' => 'icons/16/green/accept.png');
            $content = "\n<tr><td colspan=\"{$colspan}\"><table border=\"0\" cellspacing=\"0\" cellpadding=\"2\" width=\"100%\" style=\"font-size:10pt\">
                        <tr><td class=\"blank\" align=\"center\" width=\"25\">" .  Assets::img($pics[$msg[0]], array('class' => 'text-top')) . "</td>
            <td class=\"blank\" align=\"left\">" . $msg[1] . "</td></tr>
            </table></td></tr><tr>";
        }
        return $content;
    }

    function getSelf($param = "", $with_start_item = true){
        $url_params = "foo=" . DbView::get_uniqid();
        if ($this->mode) $url_params .= "&mode=" . $this->mode;
        if ($with_start_item) $url_params .= "&start_item_id=" . $this->start_item_id;
        if ($param) $url_params .= '&' . $param;
        return parent::getSelf($url_params);
    }
}
//test
//page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Default_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
//include 'lib/include/html_head.inc.php';
//include ('lib/seminar_open.php'); // initialise Stud.IP-Session
//$test = new StudipSemTreeViewAdmin(Request::quoted('start_item_id'));
//$test->showSemTree();
//echo "<hr><pre>";
//print_r($_open_items);
//page_close();
?>
