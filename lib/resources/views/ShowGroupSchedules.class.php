<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* ShowSemSchedules.class.php
*
* view schedule/assigns for a ressource-object
*
*
* @author       André Noack <noack@data-quest.de>, Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @package      resources
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// ShowSchedule.class.php
// stellt Assign/graphische Uebersicht der Belegungen dar
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

use Studip\Button,
    Studip\LinkButton;

require_once ($GLOBALS['RELATIVE_PATH_RESOURCES']."/views/ShowSemSchedules.class.php");
require_once ($GLOBALS['RELATIVE_PATH_RESOURCES']."/views/SemGroupScheduleDayOfWeek.class.php");

require_once ("lib/classes/SemesterData.class.php");



/*****************************************************************************
ShowSchedules - schedule view
/*****************************************************************************/

class ShowGroupSchedules extends ShowSemSchedules {

    //Konstruktor
    function ShowGroupSchedules ($group_id, $semester_id = null, $timespan = 'sem_time', $dow = 1) {
        $this->dow = $dow;
        $this->group_id = $group_id;
        parent::ShowSemSchedules(null, $semester_id, $timespan);
    }

    function navigator ($print_view = false) {
        global $cssSw, $view_mode;
        if (!$print_view){
        ?>
        <table border="0" celpadding="2" cellspacing="0" width="99%" align="center">
        <form method="POST" name="schedule_form" action="<?URLHelper::getLink('?navigate=TRUE&quick_view=view_group_schedule&quick_view_mode='.$view_mode) ?>">
            <?= CSRFProtection::tokenTag() ?>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="96%" colspan="3"><font size=-1><b><?=_("Semester:")?></b></font>
                </td>
            </tr>
            <tr>
                <td class="<? echo $cssSw->getClass() ?>" width="4%" rowspan="2">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="40%" valign="top">
                    <?= SemesterData::GetSemesterSelector(array('name' => 'sem_schedule_choose', 'onChange' => 'document.schedule_form.submit()'), $this->semester['semester_id'],'semester_id',false)?>
                    <?= Button::create(_('Auswählen'), 'jump') ?><br>
                    <input type="radio" onChange="document.schedule_form.submit()" style="vertical-align:bottom" <?=($this->timespan == 'course_time' ? 'checked' : '')?> name="sem_time_choose" value="course_time">
                    <?=_("Vorlesungszeit")?>
                    <input type="radio" onChange="document.schedule_form.submit()" style="vertical-align:bottom" <?=($this->timespan == 'sem_time' ? 'checked' : '')?> name="sem_time_choose" value="sem_time">
                    <?=_("vorlesungsfreie Zeit")?>
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="60%" valign="top">
                    <?=_("Eine Raumgruppe ausw&auml;hlen")?>:<br>
                    <select name="group_schedule_choose_group" onChange="document.schedule_form.submit()">
                    <?
                    $room_group = RoomGroups::GetInstance();
                    foreach($room_group->getAvailableGroups() as $gid){
                        echo '<option value="'.$gid.'" '
                            . ($this->group_id == $gid ? 'selected' : '') . '>'
                            .htmlReady(my_substr($room_group->getGroupName($gid),0,85))
                            .' ('.$room_group->getGroupCount($gid).')</option>';
                    }
                    ?>
                    </select>
                    <?= Button::create(_('Auswählen')) ?>
                </font>
                </td>
                <td class="<? echo $cssSw->getClass() ?>" valign="middle">
                    
                </td>                    
            </tr>
            <tr>
                <td class="<? echo $cssSw->getClass() ?>" colspan="4"><font size="-1">&nbsp;</font>
                </td>
            </tr>
        </table>
    <?
        }
    }

    function showScheduleGraphical($print_view = false) {
        global $RELATIVE_PATH_RESOURCES, $cssSw, $view_mode, $ActualObjectPerms;

        $categories["na"] = 4;
        $categories["sd"] = 4;
        $categories["y"] = 3;
        $categories["m"] = 3;
        $categories["w"] = 0;
        $categories["d"] = 2;

        //an assign for a date corresponding to a (seminar-)metadate
        $categories["meta"] = 1;


         //select view to jump from the schedule
         if ($this->used_view == "openobject_schedule")
            $view = "openobject_assign";
         else
            $view = "edit_object_assign";

        $start_time = $this->start_time;
        $end_time = $this->end_time;

        if ($_SESSION['resources_data']["schedule_time_range"] == -1) {
            $start_hour = 0;
            $end_hour = 12;
        } elseif ($_SESSION['resources_data']["schedule_time_range"] == 1) {
            $start_hour = 12;
            $end_hour = 23;
        } else {
            $start_hour = 8;
            $end_hour = 22;
        }

        $room_group = RoomGroups::GetInstance();
        if ($room_group->getGroupCount($this->group_id)){

            $schedule=new SemGroupScheduleDayOfWeek($start_hour, $end_hour,$room_group->getGroupContent($this->group_id), $start_time, $this->dow);

            $schedule->add_link = "resources.php?cancel_edit_assign=1&quick_view=$view&quick_view_mode=".$view_mode."&add_ts=";

            $num_rep_events = 0;
            $num_single_events = 0;
            $num = 1;

            foreach ($room_group->getGroupContent($this->group_id) as $room_to_show_id => $room_id){

                if ($_SESSION['resources_data']["show_repeat_mode"] == 'repeated' || $_SESSION['resources_data']["show_repeat_mode"] == 'all'){
                    $events = createNormalizedAssigns($room_id, $start_time, $end_time,get_config('RESOURCES_SCHEDULE_EXPLAIN_USER_NAME'), $this->dow);
                    foreach($events as $id => $event){
                        $repeat_mode = $event['repeat_mode'];
                        $add_info = ($event['sem_doz_names'] ? '('.$event['sem_doz_names'].') ' : '');
                        $add_info .= ($repeat_mode == 'w' && $event['repeat_interval'] == 1 ? '('._("wöchentlich").')' : '');
                        $add_info .= ($repeat_mode == 'w' && $event['repeat_interval'] > 1 ? '('.$event['repeat_interval'].'-'._("wöchentlich").')' : '');
                        $name = $event['name'];
                        $schedule->addEvent($room_to_show_id, $name, $event['begin'], $event['end'],
                                    URLHelper::getLink('?show_object='.$room_id.'&cancel_edit_assign=1&quick_view='.$view.'&quick_view_mode='.$view_mode.'&edit_assign_object='.$event['assign_id']), $add_info, $categories[$repeat_mode]);
                        ++$num_rep_events;
                    }
                }
                //nur zukünftige Einzelbelegungen, print_view braucht noch Sonderbehandlung <!!!>
                if ( ($end_time > time()) && ($_SESSION['resources_data']["show_repeat_mode"] == 'single' || $_SESSION['resources_data']["show_repeat_mode"] == 'all')){
                    $a_start_time = ($start_time > time() ? $start_time : time());
                    if ($print_view && ($start_time < time())){
                        $a_start_time = $this->getNextMonday($a_start_time);
                    }
                    $a_end_time = ($print_view ? $a_start_time + 86400 * 14 : $end_time);
                    $assign_events = new AssignEventList ($a_start_time, $a_end_time, $room_id, '', '', TRUE, 'semschedulesingle', $this->dow);
                    while ($event = $assign_events->nextEvent()) {
                        //mehrtägige nur am passenden Tag anzeigen
                        if ($event->repeat_mode == 'sd' && date('N', $event->begin) != $this->dow) continue;
                        if(in_array($event->repeat_mode, array('w','d','m','y'))){
                            if(strftime('%u', $event->getBegin()) != $this->dow) continue;
                            $assign = AssignObject::Factory($event->getAssignId());
                            switch($event->repeat_mode){
                                case 'd':
                                $add_info = '('.sprintf(_("täglich, %s bis %s"), strftime('%x',$assign->getBegin()), strftime('%x',$assign->getRepeatEnd())).')';
                                break;
                                case 'w':
                                if($assign->getRepeatInterval() == 1) $add_info = '('._("wöchentlich").')';
                                else  $add_info = '('.$assign->getRepeatInterval().'-'._("wöchentlich").')';
                                break;
                                case 'm':
                                if($assign->getRepeatInterval() == 1) $add_info = '('._("monatlich").')';
                                else  $add_info = '('.$assign->getRepeatInterval().'-'._("monatlich").')';
                                break;
                                case 'y':
                                if($assign->getRepeatInterval() == 1) $add_info = '('._("jährlich").')';
                                else  $add_info = '('.$assign->getRepeatInterval().'-'._("jährlich").')';
                                break;
                            }
                        } else {
                            $add_info = '';
                        }
                        $schedule->addEvent($room_to_show_id, 'EB'.$num++.':' . $event->getName(get_config('RESOURCES_SCHEDULE_EXPLAIN_USER_NAME')), $event->getBegin(), $event->getEnd(),
                        URLHelper::getLink('?show_object='.$room_id.'&cancel_edit_assign=1&quick_view='.$view.'&quick_view_mode='.$view_mode.'&edit_assign_object='.$event->getAssignId()), $add_info, $categories[$event->repeat_mode]);
                        ++$num_single_events;
                        $single_assigns[] = $event;
                    }
                }
            }
        } else {
            return;
        }
        if(!$print_view){
        ?>
        <table border=0 celpadding=2 cellspacing=0 width="99%" align="center">
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">
                    <img src="<?= $GLOBALS['ASSETS_URL'] ?>images/blank.gif" height="35" border="0">
                </td>
                <td class="<? echo $cssSw->getClass() ?>"  width="10%" align="left">&nbsp;
                    <a href="<? URLHelper::getLink('?quick_view='.$this->used_view.'&quick_view_mode='.$view_mode.'&previous_day=1') ?>"><img class="middle" src="<?= Assets::image_path('icons/16/blue/arr_2left.png') ?>" <? echo tooltip (_("Vorherigen Tag anzeigen")) ?>></a>
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="76%" align="center">
                <b>
                <? printf(_("Wochentag: %s"), htmlReady(strftime('%A', $schedule->base_date)));

                echo '<br>' . htmlReady($this->semester['name']) . ' - ' . date ("d.m.Y", $start_time), " - ", date ("d.m.Y", $end_time);
                ?>
                </b>
                <br>
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="10%" align="center">&nbsp;
                    <a href="<? URLHelper::getLink('?quick_view='.$this->used_view.'&quick_view_mode='.$view_mode.'&next_day=1') ?>"><img  class="middle"  src="<?= Assets::image_path('icons/16/blue/arr_2right.png') ?>" <? echo tooltip (_("Nächsten Tag anzeigen")) ?>></a>
                </td>
            </tr>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%" align="center" valign="bottom">&nbsp;
                    <?
                    if ((!$_SESSION['resources_data']["schedule_time_range"]) || ($_SESSION['resources_data']["schedule_time_range"] == 1))
                        printf ("<a href=\"".URLHelper::getLink('?quick_view=%s&quick_view_mode=%s&time_range=%s')."\"><img src=\"" . Assets::image_path('icons/16/blue/arr_2up.png') . "\" %sborder=\"0\"></a>", $this->used_view, $view_mode, ($_SESSION['resources_data']["schedule_time_range"]) ? "FALSE" : -1, tooltip (_("Frühere Belegungen anzeigen")));
                    ?>
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="76%" colspan="2">
                    <?

                    if ($_SESSION['resources_data']["show_repeat_mode"] == 'repeated' || $_SESSION['resources_data']["show_repeat_mode"] == 'all'){
                        echo "&nbsp;<font size=-1>"._("Anzahl der regelmäßigen Belegungen in diesem Zeitraum:")." ".$num_rep_events."</font><br>";
                    }
                    if ($_SESSION['resources_data']["show_repeat_mode"] == 'single' || $_SESSION['resources_data']["show_repeat_mode"] == 'all'){
                        echo "&nbsp;<font size=-1>"._("Anzahl der Einzelbelegungen in diesem Zeitraum:")." ".$num_single_events."</font><br>";
                    }
                    ?>
                    &nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="20%" nowrap>
                    <?
                    print "<select style=\"font-size:10px;\" name=\"show_repeat_mode\">";
                    printf ("<option style=\"font-size:10px;\" %s value=\"all\">"._("alle Belegungen")."</option>", ($_SESSION['resources_data']["show_repeat_mode"] == "all") ? "selected" : "");
                    printf ("<option %s style=\"font-size:10px;\" value=\"single\">"._("nur Einzeltermine")."</option>", ($_SESSION['resources_data']["show_repeat_mode"] == "single") ? "selected" : "");
                    printf ("<option %s style=\"font-size:10px;\" value=\"repeated\">"._("nur Wiederholungstermine")."</option>", ($_SESSION['resources_data']["show_repeat_mode"] == "repeated") ? "selected" : "");
                    print "</select>";
                    print "&nbsp;<input type=\"IMAGE\" name=\"send_schedule_repeat_mode\" src=\"" . Assets::image_path('icons/16/green/accept.png') . "\" ".tooltip(_("Ansicht umschalten")).">";
                    ?>
                </td>
            </tr>
            <tr>
                <td class="<? echo $cssSw->getClass() ?>" width="4%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="96%" colspan="3">
                    <?
                    $schedule->showSchedule("html");
                    ?>
                </td>
            </tr>
            <tr>
                <td class="<? echo $cssSw->getClass() ?>" width="4%" align="center" valign="bottom">&nbsp;
                    <?
                    if ((!$_SESSION['resources_data']["schedule_time_range"]) || ($_SESSION['resources_data']["schedule_time_range"] == -1))
                        printf ("<a href=\"".URLHelper::getLink('?quick_view=%s&quick_view_mode=%s&time_range=%s')."\"><img src=\"" . Assets::image_path('icons/16/blue/arr_2down.png') . "\" %sborder=\"0\"></a>", $this->used_view, $view_mode, ($_SESSION['resources_data']["schedule_time_range"]) ? "FALSE" : 1, tooltip (_("Spätere Belegungen anzeigen")));
                    ?>
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="20%" nowrap colspan="3">
                &nbsp;
                </td>
            </tr>
            <?php
            if (($_SESSION['resources_data']["show_repeat_mode"] == 'single' || $_SESSION['resources_data']["show_repeat_mode"] == 'all') && $num_single_events ){
                ?>
                <tr>
                <td class="<? echo $cssSw->getClass() ?>" width="4%" align="center" valign="bottom">&nbsp;</td>
                <td class="<? echo $cssSw->getClass() ?>" colspan="3">
                <strong><?=_("Einzelbelegungen:")?></strong>
                <br>
                <?php
                $num = 1;
                foreach($single_assigns as $event) {
                    echo LinkButton::create(_('Eigenschaften'), URLHelper::getURL('?show_object='
                        . $event->getResourceId() . '&quick_view=' . $view . '&quick_view_mode=' 
                        . $quick_view_mode . '&edit_assign_object=' . $event->getAssignId()));
                    printf ("&nbsp; <font size=-1>"._("%s ist von <b>%s</b> bis <b>%s</b>, belegt von <b>%s</b>")."</font><br>",'EB'.$num++.': ' . htmlReady(getResourceObjectName($event->getResourceId())), strftime("%A, %d.%m.%Y %H:%M", $event->getBegin()), strftime("%A, %d.%m.%Y %H:%M", $event->getEnd()), $event->getName());
                }
                ?>
                </tr>
                <?php
            }
            ?>
        </table>
        </form>
    <?
        } else {
            ?>
            <table border="0" cellpadding="0" cellspacing="0" width="100%" align="center">
            <tr>
                <td align="center">
                <div style="font-size:150%;font-weight:bold;">
                <?=htmlReady($room_group->getGroupName($this->group_id) . ' - ' . $this->semester['name'])?>
                <br>
                <? printf(_("Wochentag: %s"), htmlReady(strftime('%A', $schedule->base_date))); ?>
                </div>
                <div style="font-size:100%;font-weight:bold;margin-bottom:10px;">
                <?=date ("d.m.Y", $start_time). " - ". date ("d.m.Y", $end_time)?>
                &nbsp;(<?=($this->timespan == 'course_time' ? _("Vorlesungszeit") : _("vorlesungsfreie Zeit"))?>)
                </div>
                </td>
            </tr>
            <tr>
                <td>
                <?
                $schedule->showSchedule("html", true);
                ?>
                </td>
            </tr>
            <?
            if (($_SESSION['resources_data']["show_repeat_mode"] == 'single' || $_SESSION['resources_data']["show_repeat_mode"] == 'all')  && $num_single_events ){
            ?>
            <tr>
                <td>
                <strong>
                <?=_("Einzelbelegungen:")?>
                &nbsp;(<?=strftime("%d.%m.%Y",$a_start_time) . ' - ' . strftime("%d.%m.%Y",$a_end_time)?>)
                </strong>
                <br>
                <?
                $num = 1;
                foreach($single_assigns as $event) {
                    printf ("<font size=-1>"._("%s ist von <b>%s</b> bis <b>%s</b>, belegt von <b>%s</b>")."</font><br>",'EB'.$num++.': ' . htmlReady(getResourceObjectName($event->getResourceId())), strftime("%A, %d.%m.%Y %H:%M", $event->getBegin()), strftime("%A, %d.%m.%Y %H:%M", $event->getEnd()), htmlready($event->getName()));
                }
                ?>
                </td>
            </tr>
            <?}?>
            </table>
            <?
        }
    }
}
?>
