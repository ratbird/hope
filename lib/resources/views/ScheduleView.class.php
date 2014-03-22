<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* ScheduleView.class.php
*
* creates a grafical schedule view for different purposes, ie. a personal timetable
* or a timetable for a ressource like a room, a device or a building
*
*
* @author       Cornelis Kater <ckater@gwdg.de>
* @access       public
* @package      resources
* @modulegroup  resources_modules
* @module       ScheduleWeek.class.php
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// ScheduleWeek.class.php
// Modul zum Erstellen grafischer Belegungspl&auml;ne
// Copyright (C) 2002 Cornelis Kater <ckater@gwdg.de>
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

class ScheduleView {
    var $events = array();              //the events that will be shown
    var $cell_allocations;          //internal Array
    var $start_hour;            //First hour to display from
    var $end_hour;              //Last hout to display to
    var $show_columns;              //Arrays of the days that should be shown
    var $add_link;
    var $start_date;            //the timestamp of the first day (monday) of the viewed week
    var $categories;

    function ScheduleView ($start_hour = 8, $end_hour = 20, $show_columns = false,  $start_date = false) {

        $this->start_hour=$start_hour;
        $this->end_hour=$end_hour;

        if (!$show_columns) {
            for($i = 1; $i < 8; ++$i) $this->show_columns[$i] = true;
        } else
            $this->show_columns = $show_columns;

        if ($start_date)
            $this->start_date = $start_date;
        else
            $this->start_date = time();

        //the base_date have to be 0:00
        $this->base_date = mktime(0, 0, 0, date("n", $this->start_date), date("j",$this->start_date),  date("Y",$this->start_date));

        //the categories configuration (color's and bg-image)
        $this->categories = array(
            "0" => array("bg-picture"   => $GLOBALS['ASSETS_URL']."images/calendar/category3_small.jpg",
                         "border-color" => "#5C2D64"),  // is now obsolete
            "1" => array("bg-picture"   => $GLOBALS['ASSETS_URL']."images/calendar/category5_small.jpg",
                         "border-color" => "#505064"),
            "2" => array("bg-picture"   => $GLOBALS['ASSETS_URL']."images/calendar/category9_small.jpg",
                         "border-color" => "#957C29"),
            "3" => array("bg-picture"   => $GLOBALS['ASSETS_URL']."images/calendar/category11_small.jpg",
                         "border-color" => "#66954F"),
            "4" => array("bg-picture"   => $GLOBALS['ASSETS_URL']."images/calendar/category13_small.jpg",
                         "border-color" => "#951408"));
    }

    function addEvent($column, $name, $start_time, $end_time, $link='', $add_info='', $category=0) {

        // if the date ends before the starting hour of the schedule, do not add it or the schedule will break
        if (date('G', $end_time) < $this->start_hour
            || (date('G', $end_time) == $this->start_hour &&  date('i', $end_time) == 0)) return;


        if (date ("G", $end_time) > $this->end_hour) {
            $rows = ((($this->end_hour - date("G", $start_time))+1) *4);
            $rows = $rows - (int)(date("i", $start_time) / 15);
        } else
            $rows = ceil(((date("G", $end_time) - date("G", $start_time)) * 4) + ((date("i", $end_time) - date("i", $start_time)) / 15));

        if (date ("G", $start_time) < $this->start_hour) {
            $rows = $rows - (($this->start_hour - date ("G", $start_time)) *4);
            $rows = $rows + (int)(date ("i", $start_time)/ 15);
            $idx_corr_h = $this->start_hour - date ("G", $start_time);
            $idx_corr_m = (0 - date ("i", $start_time)) ;
        } else {
            $idx_corr_h = 0;
            $idx_corr_m = 0;
        }
        $sort_index = date("G", $start_time)+$idx_corr_h . '-' . (int)((date("i", $start_time)+$idx_corr_m) / 15) .'-'. $column;

        $id = md5(uniqid("rss",1));
        if( ($collision_id = $this->checkCollision($sort_index,$category)) ){
            $this->events[$collision_id]['collisions'][] = array('name' => $name, 'link' => $link,'add_info' => $add_info);
        } else {
            $this->events[$id]=array (
                        "sort_index" => $sort_index,
                        "id" =>$id,
                        "rows" => $rows,
                        "name" => $name,
                        "start_time" => $start_time,
                        "end_time" => $end_time,
                        "link" => $link,
                        "add_info" => $add_info,
                        "category" => $category
                        );
        }
    }

    function checkCollision($index,$category){
        $first_id = false;
        foreach ($this->events as $id => $event){
            if ($index == $event['sort_index']
                && $category == $event['category']){
                if (!$first_id) $first_id = $id;
            }
        }
        return $first_id;
    }

    //private
    function createCellAllocation() {
        if (is_array($this->events)) {
            foreach ($this->events as $ms) {
                $m=1;
                $idx_tmp = $ms["sort_index"];
                if ($ms["rows"]>0) {
                    for ($m; $m<=$ms["rows"]; $m++) {
                        if ($m==1)
                            $start_cell=TRUE;
                        else
                            $start_cell=FALSE;
                    $this->cell_allocations[$idx_tmp][$ms["id"]] = $start_cell;
                    list($hour,$row,$col) = explode('-', $idx_tmp);
                    ++$row;
                    if ($row == 4){
                        $row = 0;
                        ++$hour;
                    }
                    $idx_tmp = $hour . '-' . $row . '-' . $col;
                    }
                } else
                    $this->cell_allocations[$idx_tmp][$ms["id"]] = TRUE;
            }
        }
    }



    //private
    function handleOverlaps() {

        foreach($this->show_columns as $i => $foo) {
            for ($n = $this->start_hour; $n<$this->end_hour+1; $n++) {
                for ($l=0; $l<4; $l++) {
                    $idx = $n . '-' . $l . '-' . $i;
                    if ($this->cell_allocations[$idx])
                        if (sizeof($this->cell_allocations[$idx])>0) {
                            $rows=0;
                            $start_idx = $idx;
                            while ($cs = each ($this->cell_allocations [$idx]))
                                if ($cs[1])
                                    if ($this->events[$cs[0]]["rows"]>$rows) $rows=$this->events[$cs[0]]["rows"];
                            reset ($this->cell_allocations[$idx]);
                            if ($rows>1) {
                                $s=2;
                                for ($s; $s<=$rows; $s++) {
                                    $l++;
                                    if ($l>=4) {
                                        $l=0;
                                        $n++;
                                    }
                                    $idx = $n . '-' . $l . '-' . $i;
                                    //workaround
                                    if (is_array($this->cell_allocations[$idx])){
                                        while ($cs = each ($this->cell_allocations[$idx]))
                                            if ($cs[1]) {
                                                $this->cell_allocations[$idx][$cs[0]]=FALSE;
                                                $this->cell_allocations[$start_idx][$cs[0]]=TRUE;
                                                if ($this->events[$cs[0]]["rows"] > $rows -$s +1)
                                                    $rows=$rows+($this->events[$cs[0]]["rows"]-($rows-$s +1));
                                            }
                                        reset ($this->cell_allocations[$idx]);
                                    }
                                }
                            }
                            $cs = each (array_slice ($this->cell_allocations[$start_idx], 0));
                            reset ($this->cell_allocations[$start_idx]);
                            $this->events[$cs[0]]["rows"] = $rows;
                        }
                }
            }
        }
    }

    function getColumnName($id, $print_view = false){
        return htmlReady($id . " Column");
    }

        //private
    function createHtmlOutput($print_view=false) {
        $glb_colspan = count($this->show_columns);
        ?>
        <table width ="100%" align="center" cellspacing=1 cellpadding=1 border=0>
            <tr>
                <td width="5%" align="center" class="rahmen_table_row_odd" ><?=_("Zeit");?>
                </td>
                <?php
                foreach($this->show_columns as $column_id => $column_value){
                    ?>
                    <td width="<?echo round (95/$glb_colspan)."%"?>" align="center" class="rahmen_table_row_odd">
                    <?=$this->getColumnName($column_id, $print_view);?>
                    </td>
                    <?
                }
                ?>
            </tr>
        <?

        //Aufbauen der eigentlichen Tabelle
        $i = $this->start_hour;

        for ($i; $i < ($this->end_hour+1); $i++) {
            $k=0;
            for ($k; $k<4; $k++) {
                if ($k==0)  {
                    echo "<tr><td align=\"center\" class=\"rahmen_table_row_odd\" rowspan=4>";
                    if ($i<10) echo "0";
                    echo $i, ":00 </td>";
                }
                else echo "<tr>";

                foreach($this->show_columns as $l => $bla){

                    $idx = $i . '-' . $k . '-' . $l;

                    unset($cell_content);
                    $m=0;
                    if ($this->cell_allocations[$idx])
                        while ($cs = each ($this->cell_allocations [$idx]))
                            $cell_content[]=array("id"=>$cs[0], "start_cell"=>$cs[1]);
                    if ((!$this->cell_allocations[$idx]) || ($cell_content[0]["start_cell"]))
                        echo "<td ";
                    $u=0;
                    if (($this->cell_allocations[$idx]) && ($cell_content[0]["start_cell"])) {
                        $r=0;
                        foreach ($cell_content as $cc) {
                            if (!$print_view){
                                $font_color = '#FFFFFF';
                                $cc_border_color = $this->categories[$this->events[$cc["id"]]["category"]]["border-color"];
                                $cc_bg_picture = $this->categories[$this->events[$cc["id"]]["category"]]["bg-picture"];
                                $cc0_border_color = $this->categories[$this->events[$cell_content[0]["id"]]["category"]]["border-color"];
                                $cc0_bg_picture = $this->categories[$this->events[$cell_content[0]["id"]]["category"]]["bg-picture"];
                            } else {
                                $font_color = '#000000';
                                $cc_border_color = $cc0_border_color = '#FFFFFF';
                                $cc_bg_picture = $cc0_bg_picture = '';
                            }

                            if ($r==0) {
                                printf ("style=\"vertical-align:top; font-size:10px; color:$font_color; %s valign=\"top\" rowspan=\"%s\" >",
                                    $print_view ? "background-color:#FFFFFF;border-style:solid; border-width:1px; border-color:#FFFFFF" : "background-image:url($cc0_bg_picture); border-style:solid; border-width:1px; border-color:$cc0_border_color;"
                                    , $this->events[$cell_content[0]["id"]]["rows"]);
                                echo "<table width=\"100%\" cellspacing=0 cellpadding=0 border=0><tr>";
                            } else
                                echo "</td></tr><tr>";
                            printf ("<td style=\"vertical-align:top; font-size:10px; height:15px; color:$font_color; %s\" >",
                                $print_view ? "background-color:#FFFFFF;" : "background-image:url($cc0_bg_picture); border-style:solid; border-width:1px; border-color:$cc0_border_color;");
                            if (($print_view) && ($r!=0))
                                echo "<hr width=\"100%\">";
                            $r++;
                            printf ("<div style=\"font-size:10px; height:15px; color:$font_color; background-color:%s; ",
                                $cc_border_color);
                            echo " \">".date ("H:i",  $this->events[$cc["id"]]["start_time"]);
                            if  ($this->events[$cc["id"]]["start_time"] <> $this->events[$cc["id"]]["end_time"])
                                echo " - ",  date ("H:i",  $this->events[$cc["id"]]["end_time"]);
                            echo "</div>";
                            echo "</td></tr><tr>";
                            printf("<td style=\"vertical-align:top; font-size:10px; color:$font_color; background-image:url(%s); \">",
                                $cc_bg_picture);
                                echo $this->getEventName($cc["id"], $font_color, $print_view);
                        }
                        echo "</td></tr></table></td>";
                    }
                    if (!$this->cell_allocations[$idx]) {
                        if (($k == 3) && ($this->add_link) && !$print_view) {
                            echo $this->getAddLink($l,$i);
                        } else
                            echo "class=\"table_row_even\" align=\"right\"></td>";
                    }
                }
                echo "</tr>\n";
            }
        }

        if ($print_view) {
            echo "<tr><td colspan=$glb_colspan><i>"._("Erstellt am")." ",date("d.m.y", time())," um ", date("G:i", time())," Uhr.</i></td>
            <td align=\"right\"><img src=\"".$GLOBALS['ASSETS_URL']."images/logos/logo2b.png\"></td></tr></tr>";
        } else {;
            //print view bottom
        }
        ?>
            </td>
        </tr>
    </table>
    <?
    }

    function getAddLink($l,$i){
        $add_link_timestamp = $this->base_date + (($l-1) * 24 * 60 * 60) + ($i * 60 * 60);
        return sprintf ("class=\"table_row_even\" align=\"right\" valign=\"bottom\"><a href=\"%s%s\"><img src=\"".$GLOBALS['ASSETS_URL']."images/calplus.gif\" %s border=\"0\"></a></td>",
        $this->add_link, $add_link_timestamp, tooltip(sprintf(_("Eine neue Belegung von %s bis %s Uhr anlegen"), date ("H:i", $add_link_timestamp), date ("H:i", $add_link_timestamp + (2 * 60 * 60)))));

    }

    function getEventName($id,$font_color,$print_view){
        $out = "\n<div style=\"margin-left:2px;margin-right:2px;\"><font size=\"-1\">";
        if (!is_array($this->events[$id]['collisions'])){
            if (!$print_view) $out.= "\n<a style=\"color:$font_color;\" href=\"".$this->events[$id]["link"]."\">";
            $out .= $this->getShortName($this->events[$id]["name"], $print_view);
            if ($this->events[$id]["add_info"]) $out.= "\n<br>" . $this->events[$id]["add_info"];
            if (!$print_view) $out.= "\n</a>";
        } else {
            if(count($this->events[$id]['collisions']) < 3){
                if (!$print_view) $out.= "\n<a style=\"color:$font_color;\" href=\"".$this->events[$id]["link"]."\">";
                $out .= $this->getShortName($this->events[$id]["name"], $print_view);
                if ($this->events[$id]["add_info"]) $out.= "\n<br>" . $this->events[$id]["add_info"];
                if (!$print_view) $out.= "\n</a>";
                foreach($this->events[$id]['collisions'] as $event){
                    if (!$print_view) $out.= "\n<a style=\"color:$font_color;\" href=\"".$event["link"]."\">";
                    $out .= "\n<br>" . $this->getShortName($event["name"], $print_view);
                    if ($event["add_info"]) $out.= "<br>" . $event["add_info"];
                    if (!$print_view) $out.= "\n</a>";
                }
            } else {
                if (!$print_view) $out.= "<a style=\"color:$font_color;\" href=\"".$this->events[$id]["link"]."\"
                                            title=\"".htmlReady($this->events[$id]["name"])."\">";
                $out .= htmlReady(substr($this->events[$id]["name"], 0, strpos($this->events[$id]["name"],':')));
                if (!$print_view) $out.= "</a>";
                foreach($this->events[$id]['collisions'] as $event){
                    if (!$print_view) $out.= "<a style=\"color:$font_color;\" href=\"".$event["link"]."\"
                                            title=\"".htmlReady($event["name"])."\">, ";
                    $out .= htmlReady(substr($event["name"], 0, strpos($event["name"],':')));
                    if (!$print_view) $out.= "</a>";
                }
            }
        }
        return $out . "</font></div>";
    }

    function getShortName($name, $print_view){
        $out = htmlReady(substr($name, 0,50));
        if (strlen($name)>50) $out.= "...";
        if ($print_view){
            $out = preg_replace('/EB[0-9]+/', '<b>\0</b>', htmlready($name,false,false));
        }
        return nl2br($out);
    }

    function showSchedule($mode="html", $print_view=false) {
        $this->createCellAllocation();
        $this->handleOverlaps();
        switch ($mode) {
            case "html":
            default:
                $this->createHtmlOutput($print_view);
        }
    }
}
?>
