<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// StudipSemTreeView.class.php
// Class to print out the seminar tree
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
require_once("lib/classes/StudipSemTree.class.php");
require_once("lib/classes/TreeView.class.php");
require_once("config.inc.php");


/**
* class to print out the seminar tree
*
* This class prints out a html representation of the whole or part of the tree
*
* @access   public
* @author   André Noack <noack@data-quest.de>
* @package
*/
class StudipSemTreeView extends TreeView {

    /**
    * constructor
    *
    * @access public
    */
    function StudipSemTreeView($start_item_id = "root", $sem_number = null){
        $this->start_item_id = ($start_item_id) ? $start_item_id : "root";
        $this->root_content = $GLOBALS['UNI_INFO'];
        $args = null;
        if ($sem_number){
            $args = array('sem_number' => $sem_number);
        }
        parent::TreeView("StudipSemTree", $args); //calling the baseclass constructor
    }

    /**
    * manages the session variables used for the open/close thing
    *
    * @access   private
    */
    function handleOpenRanges(){
        global $_REQUEST;

        $this->open_ranges[$this->start_item_id] = true;
        if (Request::option('close_item') || Request::option('open_item')){
            $toggle_item = (Request::option('close_item')) ? Request::option('close_item') : Request::option('open_item');
            if (!$this->open_items[$toggle_item]){
                $this->open_items[$toggle_item] = true;
            } else {
                unset($this->open_items[$toggle_item]);
            }

            if($this->tree->hasKids(Request::option('open_item'))){
                $this->start_item_id = Request::option('open_item');
                $this->open_ranges = null;
                $this->open_items = null;
                $this->open_items[Request::option('open_item')] = true;
                $this->open_ranges[Request::option('open_item')] = true;
            }

            $this->anchor = $toggle_item;
        }

        if ($this->start_item_id == "root"){
            $this->open_ranges = null;
            $this->open_ranges[$this->start_item_id] = true;
        }
    }

    function showSemTree(){
        echo "\n<table width=\"99%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
        if ($this->start_item_id != 'root'){
            echo "\n<tr><td class=\"printhead\" align=\"left\" valign=\"top\">" . $this->getSemPath()
            . "<img src=\"".$GLOBALS['ASSETS_URL']."images/forumleer.gif\"  border=\"0\" height=\"20\" width=\"1\"></td></tr>";
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
        $head = "";
        $head .= "<a href=\"";
        $head .= ($this->open_items[$item_id])? URLHelper::getLink($this->getSelf("close_item={$item_id}")) . "\"" . tooltip(_("Dieses Element schließen"),true) . ">"
                                            : URLHelper::getLink($this->getSelf("open_item={$item_id}")) . "\"" . tooltip(_("Dieses Element öffnen"),true) . ">";
        $head .= "<img class=\"text-top\" src=\"".$GLOBALS['ASSETS_URL']."images/";
        $head .= ($this->open_items[$item_id]) ? "icons/16/blue/arr_1down.png" : "icons/16/blue/arr_1right.png";
        $head .= "\">";
        $head .= (!$this->open_items[$item_id]) ? "<img  src=\"".$GLOBALS['ASSETS_URL']."images/forumleer.gif\" width=\"5\">" : "";
        $head .= "</a>";
        if ($this->tree->hasKids($item_id)){
            $head .= "<img class=\"text-top\"  src=\"".$GLOBALS['ASSETS_URL']."images/";
            $head .= ($this->open_ranges[$item_id]) ? "icons/16/blue/folder-full.png" : "icons/16/blue/folder-full.png";
            $head .= "\" ";
            $head .= (!$this->open_ranges[$item_id])? tooltip(_("Alle Unterelement öffnen")) : tooltip(_("Alle Unterelemente schliessen"));
            $head .= ">";
        } else {
            $head .= "<img class=\"text-top\" src=\"".$GLOBALS['ASSETS_URL']."images/";
            $head .= ($this->open_items[$item_id]) ? "icons/16/blue/folder-empty.png" : "icons/16/blue/folder-empty.png";
            $head .= "\" " . tooltip(_("Dieses Element hat keine Unterelemente")) . ">";
        }
    return $head;
    }

    function getItemContent($item_id){
        $content = "\n<table width=\"90%\" cellpadding=\"2\" cellspacing=\"0\" align=\"center\" style=\"font-size:10pt\">";
        if ($item_id == "root"){
            $content .= "\n<tr><td class=\"topic\" align=\"left\">" . htmlReady($this->tree->root_name) ." </td></tr>";
            $content .= "\n<tr><td class=\"table_row_even\" align=\"left\">" . htmlReady($this->root_content) ." </td></tr>";
            $content .= "\n</table>";
            return $content;
        }
        if ($this->tree->tree_data[$item_id]['info']){
            $content .= "\n<tr><td class=\"table_row_even\" align=\"left\" colspan=\"2\">";
            $content .= formatReady($this->tree->tree_data[$item_id]['info']) . "</td></tr>";
        }
        $content .= "<tr><td colspan=\"2\" class=\"table_row_even\">" . sprintf(_("Alle Veranstaltungen innerhalb dieses Bereiches in der %s&Uuml;bersicht%s"),
                "<a href=\"" . URLHelper::getLink($this->getSelf("cmd=show_sem_range&item_id=$item_id")) ."\">","</a>") . "</td></tr>";
        $content .= "<tr><td colspan=\"2\">&nbsp;</td></tr>";
        if ($this->tree->getNumEntries($item_id) - $this->tree->tree_data[$item_id]['lonely_sem']){
            $content .= "<tr><td class=\"table_row_even\" align=\"left\" colspan=\"2\"><b>" . _("Eintr&auml;ge auf dieser Ebene:");
            $content .= "</b>\n</td></tr>";
            $entries = $this->tree->getSemData($item_id);
            $content .= $this->getSemDetails($entries->getGroupedResult("seminar_id"));
        } else {
            $content .= "\n<tr><td class=\"table_row_even\" colspan=\"2\">" . _("Keine Eintr&auml;ge auf dieser Ebene vorhanden!") . "</td></tr>";
        }
        if ($this->tree->tree_data[$item_id]['lonely_sem']){
            $content .= "<tr><td class=\"table_row_even\" align=\"left\" colspan=\"2\"><b>" . _("Nicht zugeordnete Veranstaltungen auf dieser Ebene:");
            $content .= "</b>\n</td></tr>";
            $entries = $this->tree->getLonelySemData($item_id);
            $content .= $this->getSemDetails($entries->getGroupedResult("seminar_id"));
        }
        $content .= "</table>";
        return $content;
    }

    function getSemDetails($sem_data){
        $content = "";
        $sem_number = -1;
        foreach($sem_data as $seminar_id => $data){
            if (key($data['sem_number']) != $sem_number){
                $sem_number = key($data['sem_number']);
                $content .= "\n<tr><td class=\"content_seperator\" colspan=\"2\">" . $this->tree->sem_dates[$sem_number]['name'] . "</td></tr>";
            }
            $sem_name = key($data["Name"]);
            $sem_number_end = key($data["sem_number_end"]);
            if ($sem_number != $sem_number_end){
                $sem_name .= " (" . $this->tree->sem_dates[$sem_number]['name'] . " - ";
                $sem_name .= (($sem_number_end == -1) ? _("unbegrenzt") : $this->tree->sem_dates[$sem_number_end]['name']) . ")";
            }
            $content .= "<tr><td class=\"table_row_even\"><a href=\"details.php?sem_id=". $seminar_id
            ."&send_from_search=true&send_from_search_page=" . rawurlencode(URLHelper::getLink($this->getSelf())) . "\">" . htmlReady($sem_name) . "</a>
            </td><td class=\"table_row_even\" align=\"right\">(";
            for ($i = 0; $i < count($data["doz_name"]); ++$i){
                $content .= "<a href=\"about.php?username=" . key($data["doz_uname"]) ."\">" . htmlReady(key($data["doz_name"])) . "</a>";
                if($i != count($data["doz_name"])-1){
                    $content .= ", ";
                }
                next($data["doz_name"]);
                next($data["doz_uname"]);
            }
            $content .= ") </td></tr>";
            }
            return $content;
    }

    function getItemHead($item_id){
        $head = "";
        $head .= parent::getItemHead($item_id);
        if ($item_id != "root"){
            $head .= " (" . $this->tree->getNumEntries($item_id,true) . ") " ;
        }
        return $head;
    }

    function getSelf($param = "", $with_start_item = true){
        if ($param)
            $url = (($with_start_item) ? "?start_item_id=" . $this->start_item_id . "&" : "?") . $param . "#anchor";
        else
            $url = (($with_start_item) ? "?start_item_id=" . $this->start_item_id : "") . "#anchor";
        return $url;
    }
}
//test
//page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Default_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
//include 'lib/include/html_head.inc.php';
//include ('lib/seminar_open.php'); // initialise Stud.IP-Session
//$test = new StudipSemTreeView();
//$test->showTree("c2942084b6140fc2635dfecdf65bf20d");
//page_close();
?>
