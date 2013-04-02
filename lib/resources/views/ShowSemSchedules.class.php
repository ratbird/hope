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
* @author       Andr� Noack <noack@data-quest.de>, Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
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

require_once ($GLOBALS['RELATIVE_PATH_RESOURCES']."/views/ShowSchedules.class.php");
require_once ($GLOBALS['RELATIVE_PATH_RESOURCES']."/views/SemScheduleWeek.class.php");




/*****************************************************************************
ShowSchedules - schedule view
/*****************************************************************************/

class ShowSemSchedules extends ShowSchedules {

    var $semester = null;
    var $only_course_time = true;

    //Konstruktor
    function ShowSemSchedules ($resource_id, $semester_id = null, $timespan = 'sem_time') {
        $sem = new SemesterData();
        if (!$semester_id){
            $this->semester = $sem->getCurrentSemesterData();
        } else {
            $this->semester = $sem->getSemesterData($semester_id);
        }
        $this->timespan = $timespan;
        if  ($this->timespan == 'sem_time'){
            $next_sem = $sem->getNextSemesterData($this->semester['vorles_ende']);
            $this->start_time = $this->semester['vorles_ende'];
            $this->end_time = is_array($next_sem) ? $next_sem['vorles_beginn'] : $this->semester['ende'];
        } else {
            $this->start_time = $this->semester['vorles_beginn'];
            $this->end_time = $this->semester['vorles_ende'];
        }
        parent::ShowSchedules($resource_id);
    }

    function navigator ($print_view = false) {
        global $cssSw, $view_mode;
        $semester = SemesterData::GetSemesterArray();
        unset($semester[0]);
        if (!$print_view){
        ?>
        <table border="0" celpadding="2" cellspacing="0" width="99%" align="center">
        <form method="POST" name="schedule_form" action="<?echo URLHelper::getLink('?navigate=TRUE&quick_view=view_sem_schedule&quick_view_mode='.$view_mode) ?>">
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
                <td class="<? echo $cssSw->getClass() ?>" width="40%" valign="bottom">
                <font size="-1">
                <?=SemesterData::GetSemesterSelector(array('name' => 'sem_schedule_choose', 'onChange' => 'document.schedule_form.submit()'), $this->semester['semester_id'],'semester_id',false)?>
                <?= Button::create(_('Ausw�hlen'), 'jump') ?><br>
                </font>
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="30%" valign="middle">
                <font size="-1">
                <?=_("Ein Semester als Liste ausgeben")?>
                </font>
                </td>
                <td class="<? echo $cssSw->getClass() ?>">
                    <?= Button::create(_('Ausgeben'), 'sem_schedule_start_list') ?><br>
                </td>
            </tr>
            <tr>
            <td class="<? echo $cssSw->getClass() ?>" width="40%" valign="middle">
                <font size="-1">
                <input type="radio" onChange="document.schedule_form.submit()" style="vertical-align:bottom" <?=($this->timespan == 'course_time' ? 'checked' : '')?> name="sem_time_choose" value="course_time">
                <?=_("Vorlesungszeit")?>
                <input type="radio" onChange="document.schedule_form.submit()" style="vertical-align:bottom" <?=($this->timespan == 'sem_time' ? 'checked' : '')?> name="sem_time_choose" value="sem_time">
                <?=_("vorlesungsfreie Zeit")?>
                </font>
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="30%" valign="middle"><font size="-1">
                    <?=_("<i>oder</i> ein Semester grafisch ausgeben")?>
                </font>
                </td>
                <td class="<? echo $cssSw->getClass() ?>">
                    <?= Button::create(_('Ausgeben'), 'sem_schedule_start_graphical') ?><br>
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

        $schedule=new SemScheduleWeek($start_hour, $end_hour ,false , $start_time);
        $num_rep_events = 0;
        $num_single_events = 0;
        if ($ActualObjectPerms->havePerm("autor"))
            $schedule->add_link = "resources.php?cancel_edit_assign=1&quick_view=$view&quick_view_mode=".$view_mode."&add_ts=";
        if ($_SESSION['resources_data']["show_repeat_mode"] == 'repeated' || $_SESSION['resources_data']["show_repeat_mode"] == 'all'){
            $events = createNormalizedAssigns($this->resource_id, $start_time, $end_time, get_config('RESOURCES_SCHEDULE_EXPLAIN_USER_NAME'));
            foreach($events as $id => $event){
                $repeat_mode = $event['repeat_mode'];
                $add_info = ($event['sem_doz_names'] ? '('.$event['sem_doz_names'].') ' : '');
                $add_info .= ($repeat_mode == 'w' && $event['repeat_interval'] == 1 ? '('._("w�chentlich").')' : '');
                $add_info .= ($repeat_mode == 'w' && $event['repeat_interval'] > 1 ? '('.$event['repeat_interval'].'-'._("w�chentlich").')' : '');
                $name = $event['name'];
                $schedule->addEvent($name, $event['begin'], $event['end'],
                            URLHelper::getLink('?cancel_edit_assign=1&quick_view='.$view.'&quick_view_mode='.$view_mode.'&edit_assign_object='.$event['assign_id']), $add_info, $categories[$repeat_mode]);
            }
            $num_rep_events = count($events);
        }
        //nur zuk�nftige Einzelbelegungen, print_view hat Sonderbehandlung <!!!>
        if ( ($end_time > time()) && ($_SESSION['resources_data']["show_repeat_mode"] == 'single' || $_SESSION['resources_data']["show_repeat_mode"] == 'all')){
            $a_start_time = ($start_time > time() ? $start_time : time());
            if ($print_view && ($start_time < time())){
                $a_start_time = $this->getNextMonday($a_start_time);
            }
            $a_end_time = ($print_view ? $a_start_time + 86400 * 14 : $end_time);
            $assign_events = new AssignEventList ($a_start_time, $a_end_time, $this->resource_id, '', '', TRUE, 'semschedulesingle');
            $num = 1;
            while ($event = $assign_events->nextEvent()) {
                if(in_array($event->repeat_mode, array('d','m','y'))){
                    $assign = AssignObject::Factory($event->getAssignId());
                    switch($event->repeat_mode){
                        case 'd':
                        $add_info = '('.sprintf(_("t�glich, %s bis %s"), strftime('%x',$assign->getBegin()), strftime('%x',$assign->getRepeatEnd())).')';
                        break;
                        case 'm':
                        if($assign->getRepeatInterval() == 1) $add_info = '('._("monatlich").')';
                        else  $add_info = '('.$assign->getRepeatInterval().'-'._("monatlich").')';
                        break;
                        case 'y':
                        if($assign->getRepeatInterval() == 1) $add_info = '('._("j�hrlich").')';
                        else  $add_info = '('.$assign->getRepeatInterval().'-'._("j�hrlich").')';
                        break;
                    }
                } else {
                    $add_info = '';
                }
                $schedule->addEvent('EB'.$num++.':' . $event->getName(get_config('RESOURCES_SCHEDULE_EXPLAIN_USER_NAME')), $event->getBegin(), $event->getEnd(),
                        URLHelper::getLink('?cancel_edit_assign=1&quick_view='.$view.'&quick_view_mode='.$view_mode.'&edit_assign_object='.$event->getAssignId()), $add_info, $categories[$event->repeat_mode]);
            }
            $num_single_events = $assign_events->numberOfEvents();
        }
        if(!$print_view){
        ?>
        <table border=0 celpadding=2 cellspacing=0 width="99%" align="center">
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">
                    <img src="<?= $GLOBALS['ASSETS_URL'] ?>images/blank.gif" height="35" border="0">
                </td>
                <td class="<? echo $cssSw->getClass() ?>"  width="10%" align="left">&nbsp;
                    <a href="<? echo URLHelper::getLink('?quick_view='.$this->used_view.'&quick_view_mode='.$view_mode.'&previous_sem=1')?>"><?= Assets::img("icons/16/blue/arr_2left.png", array('alt' => _("Vorheriges Semester anzeigen"), 'title' => _("Vorheriges Semester anzeigen"), 'border' => 0)) ?></a>
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="76%" align="center">
                <b>
                <? printf(_("Anzeige des Semesters: %s"), htmlReady($this->semester['name']));

                echo '<br>' . date ("d.m.Y", $start_time), " - ", date ("d.m.Y", $end_time);
                ?>
                </b>
                <br>
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="10%" align="center">&nbsp;
                    <a href="<? echo URLHelper::getLink('?quick_view='.$this->used_view.'&quick_view_mode='.$view_mode.'&next_sem=1')?>"><?= Assets::img("icons/16/blue/arr_2right.png", array('alt' => _("N�chstes Semester anzeigen"), 'title' => _("N�chstes Semester anzeigen"), 'border' => 0)) ?></a>
                </td>
            </tr>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%" align="center" valign="bottom">&nbsp;
                    <?
                    if ((!$_SESSION['resources_data']["schedule_time_range"]) || ($_SESSION['resources_data']["schedule_time_range"] == 1))
                        printf ("<a href=\"".URLHelper::getLink('?quick_view=%s&quick_view_mode=%s&time_range=%s')."\">" . Assets::img("icons/16/blue/arr_2up.png", array('alt' => _("Fr�here Belegungen anzeigen"), 'title' => _("Fr�here Belegungen anzeigen"), "border" => 0)) . "</a>", $this->used_view, $view_mode, ($_SESSION['resources_data']["schedule_time_range"]) ? "FALSE" : -1);
                    ?>
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="76%" colspan="2">
                    <?
                    if ($_SESSION['resources_data']["show_repeat_mode"] == 'repeated' || $_SESSION['resources_data']["show_repeat_mode"] == 'all'){
                        echo "&nbsp;<font size=-1>"._("Anzahl der regelm��igen Belegungen in diesem Zeitraum:")." ".$num_rep_events."</font><br>";
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
                    print "&nbsp;".Assets::input("icons/16/green/accept.png", array('type' => "image", 'class' => "middle", 'name' => "send_schedule_repeat_mode", 'title' => _('Ansicht umschalten')));
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
                        printf ("<a href=\"".URLHelper::getLink('?quick_view=%s&quick_view_mode=%s&time_range=%s')."\">"    . Assets::img("icons/16/blue/arr_2down.png", array('alt' => _("Sp�tere Belegungen anzeigen"), 'title' => _("Sp�tere Belegungen anzeigen"), 'border' => 0)) . "</a>", $this->used_view, $view_mode, ($_SESSION['resources_data']["schedule_time_range"]) ? "FALSE" : 1);
                    ?>
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="20%" nowrap colspan="3">
                &nbsp;
                </td>
            </tr>
            <?php
            if (($_SESSION['resources_data']["show_repeat_mode"] == 'single' || $_SESSION['resources_data']["show_repeat_mode"] == 'all') && $num_single_events){
                ?>
                <tr>
                <td class="<? echo $cssSw->getClass() ?>" width="4%" align="center" valign="bottom">&nbsp;</td>
                <td class="<? echo $cssSw->getClass() ?>" colspan="3">
                <strong><?=_("Einzelbelegungen:")?></strong>
                <br>
                <?php
                reset($assign_events->events);
                $num = 1;
                while($event = $assign_events->nextEvent()) {
                    echo LinkButton::create(_('Eigenschaften'), URLHelper::getURL('?quick_view=' 
                        . $view . '&quick_view_mode=' . $view_mode . '&edit_assign_object=' . $event->getAssignId()));
                    printf ("&nbsp; <font size=-1>"._("%s ist von <b>%s</b> bis <b>%s</b>, belegt von <b>%s</b>")."</font><br>",'EB'.$num++, strftime("%A, %d.%m.%Y %H:%M", $event->getBegin()), strftime("%A, %d.%m.%Y %H:%M", $event->getEnd()), $event->getName());
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
            $room = ResourceObject::Factory($this->resource_id);
            ?>
            <table border="0" cellpadding="0" cellspacing="0" width="100%" align="center">
            <tr>
                <td align="center">
                <div style="font-size:150%;font-weight:bold;">
                <?=htmlReady($room->getName().' - ' .$this->semester['name'])?>
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
            if (($_SESSION['resources_data']["show_repeat_mode"] == 'single' || $_SESSION['resources_data']["show_repeat_mode"] == 'all') && $num_single_events){
            ?>
            <tr>
                <td>
                <strong>
                <?=_("Einzelbelegungen:")?>
                &nbsp;(<?=strftime("%d.%m.%Y",$a_start_time) . ' - ' . strftime("%d.%m.%Y",$a_end_time)?>)
                </strong>
                <br>
                <?
                reset($assign_events->events);
                $num = 1;
                while($event = $assign_events->nextEvent()) {
                    printf ("<font size=-1>"._("%s ist von <b>%s</b> bis <b>%s</b>, belegt von <b>%s</b>")."</font><br>",'EB'.$num++, strftime("%A, %d.%m.%Y %H:%M", $event->getBegin()), strftime("%A, %d.%m.%Y %H:%M", $event->getEnd()), htmlready($event->getName()));
                }
                ?>
                </td>
            </tr>
            <?}?>
            </table>
            <?
        }
    }

    function getNextMonday($start_time = null){
        $start_time = ($start_time ? $start_time : time());
        $this_monday = date("j", $start_time)  - (date("w", $start_time) - 1);
        if (date("w", $start_time)+1 > 4){
            $this_monday += 7;
        }
        return mktime(2, 0, 1, date("n", $start_time), $this_monday ,  date("Y", $start_time));
    }

}
?>
