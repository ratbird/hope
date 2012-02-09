<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// StudipSemRangeTreeViewSimple.class.php
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
require_once "lib/classes/StudipRangeTree.class.php";
require_once "lib/classes/RangeTreeObject.class.php";
require_once "config.inc.php";

/**
* class to print out the range tree
*
* This class prints out a html representation a part of the tree
*
* @access   public
* @author   André Noack <noack@data-quest.de>
* @package
*/
class StudipSemRangeTreeViewSimple {


    var $tree;
    var $show_entries;

    /**
    * constructor
    *
    * @access public
    */
    function StudipSemRangeTreeViewSimple($start_item_id = "root", $sem_number = false, $sem_status = false, $visible_only = false){
        $this->start_item_id = ($start_item_id) ? $start_item_id : "root";
        $this->root_content = $GLOBALS['UNI_INFO'];
        $args = null;
        if ($sem_number !== false){
            $args['sem_number'] = $sem_number;
        }
        if ($sem_status !== false){
            $args['sem_status'] =  $sem_status;
        }
        $args['visible_only'] = $visible_only;
        $this->tree = TreeAbstract::GetInstance("StudipRangeTree",$args);
        if (!$this->tree->tree_data[$this->start_item_id]){
            $this->start_item_id = "root";
        }
    }

    function showSemRangeTree(){
        echo "\n<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
        echo "\n<tr><td class=\"steelgraulight\" align=\"left\" valign=\"top\" style=\"font-size:10pt;\">"
            . "<div style=\"font-size:10pt;margin-left:10px\"><b>" . _("Einrichtungen:"). "</b><br>". $this->getSemPath();
        echo "</div></td>";
        echo "<td nowrap class=\"steelgraulight\" align=\"right\" valign=\"bottom\" style=\"font-size:10pt;\">";
        if ($this->start_item_id != "root"){
            echo "\n<a href=\"" .URLHelper::getLink($this->getSelf("start_item_id={$this->tree->tree_data[$this->start_item_id]['parent_id']}", false)) . "\">".
            Assets::img('icons/16/blue/arr_2left.png', array('class' => 'text-top', 'title' =>_('eine Ebene zur&uuml;ck'))). "</a>";
        } else {
            echo "&nbsp;";
        }
        echo "</td></tr>";
        echo "\n<tr><td class=\"steel1\" colspan=\"2\" align=\"center\" valign=\"center\">";
        $this->showKids($this->start_item_id);
        echo "\n</td><tr><td class=\"steelgraulight\" colspan=\"2\" align=\"left\" valign=\"center\">";
        $this->showContent($this->start_item_id);
        echo "\n</td></tr></table>";
    }

    function showKids($item_id){
        $num_kids = $this->tree->getNumKids($item_id);
        $kids = $this->tree->getKids($item_id);
        echo "\n<table width=\"95%\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\"><tr>\n<td class=\"steel1\" width=\"50%\" align=\"left\" valign=\"top\"><ul class=\"semtree\">";
        for ($i = 0; $i < $num_kids; ++$i){
            $num_entries = $this->tree->getNumEntries($kids[$i],true);
            echo "<li><a " . tooltip(sprintf(_("%s Einträge in allen Unterebenen vorhanden"), $num_entries)) . " href=\"" .URLHelper::getLink($this->getSelf("start_item_id={$kids[$i]}", false)) . "\">";
            echo htmlReady($this->tree->tree_data[$kids[$i]]['name']);
            echo " ($num_entries)";
            echo "</a></li>";
            if ($i == ceil($num_kids / 2)-1){
                echo "</ul></td>\n<td class=\"steel1\" align=\"left\" valign=\"top\"><ul class=\"semtree\">";
            }
        }
        if (!$num_kids){
            echo "<li>";
            echo _("Auf dieser Ebene existieren keine weiteren Unterebenen.");
            echo "</li>";
        }
        echo "\n</ul></td></tr></table>";
    }

    function getTooltip($item_id){
        if ($item_id == "root"){
            $ret = ($this->root_content) ? $this->root_content : _("Keine weitere Info vorhanden");
        } else {
            $range_object = RangeTreeObject::GetInstance($item_id);
            if (is_array($range_object->item_data_mapping)){
                foreach ($range_object->item_data_mapping as $key => $value){
                    if ($range_object->item_data[$key]){
                        $info .= $value . ": ";
                        $info .= $range_object->item_data[$key].  " ";
                    }
                }
            }
            $ret = ($info) ? $info :  _("Keine weitere Info vorhanden");
        }
        return $ret;
    }

    function showContent($item_id){
        echo "\n<div align=\"center\" style=\"margin-left:10px;margin-top:10px;margin-bottom:10px;font-size:10pt\">";
        if ($item_id != "root"){
            if ($this->tree->hasKids($item_id) && ($num_entries = $this->tree->getNumEntries($this->start_item_id,true))){
                if ($this->show_entries != "sublevels"){
                    echo "<a " . tooltip(_("alle Einträge in allen Unterebenen anzeigen")) ." href=\"" . URLHelper::getLink($this->getSelf("cmd=show_sem_range_tree&item_id={$this->start_item_id}_withkids")) ."\">";
                    echo "<img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/arr_1right.png\" border=\"0\">&nbsp;";
                } else {
                    echo "<img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/arr_1down.png\" border=\"0\">&nbsp;";
                }
                printf(_("<b>%s</b> Eintr&auml;ge in allen Unterebenen vorhanden"), $num_entries);
                if ($this->show_entries != "sublevels"){
                    echo "</a>";
                }
                echo "&nbsp;&nbsp;|&nbsp;&nbsp;";
            }
            if ($num_entries = $this->tree->getNumEntries($item_id)){
                if ($this->show_entries != "level"){
                    echo "<a " . tooltip(_("alle Einträge auf dieser Ebene anzeigen")) ." href=\"" . URLHelper::getLink($this->getSelf("cmd=show_sem_range_tree&item_id=$item_id")) ."\">";
                    echo "<img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/red/arr_1right.png\" border=\"0\">&nbsp;";
                } else {
                    echo "<img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/red/arr_1down.png\" border=\"0\">&nbsp;";
                }
                printf(_("<b>%s</b> Eintr&auml;ge auf dieser Ebene.&nbsp;"),$num_entries);
                if ($this->show_entries != "level"){
                    echo "</a>";
                }
            } else {
                    echo _("Keine Eintr&auml;ge auf dieser Ebene vorhanden!");
            }
        }
        echo "\n</div>";
    }

    function getSemPath(){

        if ($parents = $this->tree->getParents($this->start_item_id)){
            for($i = count($parents)-1; $i >= 0; --$i){
                $ret .= "&nbsp;&gt;&nbsp;<a href=\"" . URLHelper::getLink($this->getSelf("start_item_id={$parents[$i]}",false))
                    . "\">" .htmlReady($this->tree->tree_data[$parents[$i]]["name"]) . "</a>";
            }
        }
        if ($this->start_item_id == "root") {
            $ret = "&nbsp;&gt;&nbsp;<a href=\"" . URLHelper::getLink($this->getSelf("start_item_id=root",false)) . "\">" .htmlReady($this->tree->root_name) . "</a>";
        } else {
            $ret .= "&nbsp;&gt;&nbsp;<a href=\"" . URLHelper::getLink($this->getSelf("start_item_id={$this->start_item_id}",false)) . "\">" . htmlReady($this->tree->tree_data[$this->start_item_id]["name"]) . "</a>";

        }
        $ret .= "&nbsp;<a href=\"#\" " . tooltip(kill_format($this->getTooltip($this->start_item_id)),false,true) . "><img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/grey/info-circle.png\" border=\"0\" align=\"absmiddle\"></a>";
        return $ret;
    }



    function getSelf($param = "", $with_start_item = true){
        $url_params = (($with_start_item) ? "start_item_id=" . $this->start_item_id . "&" : "") . $param ;
        return URLHelper::getLink('?' . $url_params);
    }
}
?>
