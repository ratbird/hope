<?php
# Lifter010: TODO
/**
* ShowGroupSchedulesDaily.class.php
*
* view schedule/assigns for a ressource group
*
*
* @author       André Noack <noack@data-quest.de>, Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
* @version
* @access       public
* @package      resources
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// ShowGroupSchedulesDaily.class.php
// stellt Assign/graphische Uebersicht der Belegungen dar
// Copyright (C) 2008 André Noack <noack@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
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

use Studip\Button;

require_once ($GLOBALS['RELATIVE_PATH_RESOURCES']."/views/ShowSemSchedules.class.php");
require_once ($GLOBALS['RELATIVE_PATH_RESOURCES']."/views/SemGroupScheduleDayOfWeek.class.php");


class ShowGroupSchedulesDaily extends ShowSemSchedules {

    var $resources_groups;
    var $group_id;

    //Konstruktor
    function ShowGroupSchedulesDaily ($group_id, $start_time, $resources_groups = null) {
        $this->dow = false;
        $this->group_id = $group_id;
        parent::ShowSemSchedules(null, null, null);
        $this->start_time = $start_time ? strtotime('midnight', $start_time) : strtotime('today');
        $this->end_time = strtotime('tomorrow', $this->start_time) - 1;
        if(is_object($resources_groups)) $this->resources_groups = $resources_groups;
        else $this->resources_groups = RoomGroups::GetInstance();
    }

    function navigator ($print_view = false) {
        global $cssSw, $view_mode,$view, $PHP_SELF;
        $start_time = $this->start_time;
        if (!$print_view){
        ?>
        <table border="0" celpadding="2" cellspacing="0" width="99%" align="center">
        <form method="POST" name="schedule_form" action="<?echo $PHP_SELF ?>?navigate=TRUE&quick_view=<?=$view?>&quick_view_mode=<?=$view_mode?>">
            <?= CSRFProtection::tokenTag() ?>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="96%" colspan="3"><font size=-1><b><?=_("Datum:")?></b></font>
                </td>
            </tr>
            <tr>
                <td class="<? echo $cssSw->getClass() ?>" width="4%" rowspan="2">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="40%" valign="top">
                    <input type="text" name="schedule_begin_day" size=2 maxlength=2 value="<?echo date("d",$start_time); ?>">.
                    <input type="text" name="schedule_begin_month" size=2 maxlength=2 value="<?echo date("m",$start_time); ?>">.
                    <input type="text" name="schedule_begin_year" size=4 maxlength=4 value="<?echo date("Y",$start_time); ?>">
                    <?= Button::create(_('Auswählen'), 'jump') ?><br>
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="60%" valign="top">
                    <?= ($view_mode == 'oobj' ? _("Eine Ressourcengruppe ausw&auml;hlen") : _("Eine Raumgruppe ausw&auml;hlen")) ?>:<br>
                    <select name="group_schedule_choose_group" onChange="document.schedule_form.submit()">
                    <? foreach($this->resources_groups->getAvailableGroups() as $gid) :
                        echo '<option value="'.$gid.'" '
                            . ($this->group_id == $gid ? 'selected' : '') . '>'
                            .htmlReady(my_substr($this->resources_groups->getGroupName($gid),0,80))
                            .' ('.$this->resources_groups->getGroupCount($gid).')</option>';
                    endforeach ?>
                    </select>
                    <?= Button::create(_('Auswählen')) ?>
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
        global $RELATIVE_PATH_RESOURCES, $PHP_SELF, $cssSw, $view_mode, $resources_data, $ActualObjectPerms;

        $categories["na"] = 4;
        $categories["sd"] = 4;
        $categories["y"] = 3;
        $categories["m"] = 3;
        $categories["w"] = 0;
        $categories["d"] = 2;

        //an assign for a date corresponding to a (seminar-)metadate
        $categories["meta"] = 1;


         //select view to jump from the schedule
         if ($view_mode == "oobj")
            $view = "openobject_assign";
         else
            $view = "edit_object_assign";

        $start_time = $this->start_time;
        $end_time = $this->end_time;

        if ($resources_data["schedule_time_range"] == -1) {
            $start_hour = 0;
            $end_hour = 12;
        } elseif ($resources_data["schedule_time_range"] == 1) {
            $start_hour = 12;
            $end_hour = 23;
        } else {
            $start_hour = 8;
            $end_hour = 22;
        }

        if ($this->resources_groups->getGroupCount($this->group_id)){

            $schedule=new SemGroupScheduleDayOfWeek($start_hour, $end_hour,$this->resources_groups->getGroupContent($this->group_id), $start_time, false);

            $schedule->add_link = "resources.php?cancel_edit_assign=1&quick_view=$view&quick_view_mode=".$view_mode."&add_ts=";

            $num_rep_events = 0;
            $num_single_events = 0;
            $num = 1;

            foreach ($this->resources_groups->getGroupContent($this->group_id) as $resource_to_show_id => $resource_id){
                //fill the schedule
                $assign_events=new AssignEventList ($start_time, $end_time, $resource_id, '', '', TRUE, $resources_data["show_repeat_mode"]);
                while ($event=$assign_events->nextEvent()) {
                    $repeat_mode = $event->getRepeatMode(TRUE);
                    if(in_array($repeat_mode, array('w','d','m','y'))){
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
                    if (in_array($event->getOwnerType(), array('sem','date'))){
                        $sem_doz_names = array();
                        $c = 0;
                        if ($event->getOwnerType() == 'sem'){
                            $sem_obj = Seminar::GetInstance($event->getAssignUserId());
                        } else {
                            $sem_obj = Seminar::GetInstance(Seminar::GetSemIdByDateId($event->getAssignUserId()));
                        }
                        foreach($sem_obj->getMembers('dozent') as $dozent){
                            $sem_doz_names[] = $dozent['Nachname'];
                            if (++$c > 2) break;
                        }
                        $add_info .= '(' . join(', ' , $sem_doz_names) . ')';
                    }
                    $schedule->addEvent($resource_to_show_id, $event->getName(get_config('RESOURCES_SCHEDULE_EXPLAIN_USER_NAME')), $event->getBegin(), $event->getEnd(),
                        "$PHP_SELF?show_object=$resource_id&cancel_edit_assign=1&quick_view=$view&quick_view_mode=".$view_mode."&edit_assign_object=".$event->getAssignId(), $add_info, $categories[$repeat_mode]);
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
                    <a href="<? echo $PHP_SELF ?>?quick_view=<?=$this->used_view?>&quick_view_mode=<?=$view_mode?>&previous_day=1"><img class="middle" src="<?= Assets::image_path('icons/16/blue/arr_2left.png') ?>" <? echo tooltip (_("Vorherigen Tag anzeigen")) ?>border="0"></a>
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="76%" align="center" style="font-weight:bold">
                <? echo htmlReady(strftime('%A, %x (KW %V)', $start_time));
                ?>
                <br>
                <?php
                $this->showSemWeekNumber($start_time);
                ?>
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="10%" align="center">&nbsp;
                    <a href="<? echo $PHP_SELF ?>?quick_view=<?=$this->used_view?>&quick_view_mode=<?=$view_mode?>&next_day=1"><img class="middle" src="<?= Assets::image_path('icons/16/blue/arr_2right.png') ?>" <? echo tooltip (_("Nächsten Tag anzeigen")) ?>border="0"></a>
                </td>
            </tr>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%" align="center" valign="bottom">&nbsp;
                    <?
                    if ((!$resources_data["schedule_time_range"]) || ($resources_data["schedule_time_range"] == 1))
                        printf ("<a href=\"%s?quick_view=%s&quick_view_mode=%s&time_range=%s\"><img src=\"" . Assets::image_path('icons/16/blue/arr_2up.png') . "\" %s></a>", $PHP_SELF, $this->used_view, $view_mode, ($resources_data["schedule_time_range"]) ? "FALSE" : -1, tooltip (_("Frühere Belegungen anzeigen")));
                    ?>
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="76%" colspan="2">
                    <?

                    echo "&nbsp;<font size=-1>"._("Anzahl der Belegungen in diesem Zeitraum:")." ".$assign_events->numberOfEvents()."</font><br>";
                    ?>
                    &nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="20%" nowrap>
                    <?
                    print "<select style=\"font-size:10px;\" name=\"show_repeat_mode\">";
                    printf ("<option style=\"font-size:10px;\" %s value=\"all\">"._("alle Belegungen")."</option>", ($resources_data["show_repeat_mode"] == "all") ? "selected" : "");
                    printf ("<option %s style=\"font-size:10px;\" value=\"single\">"._("nur Einzeltermine")."</option>", ($resources_data["show_repeat_mode"] == "single") ? "selected" : "");
                    printf ("<option %s style=\"font-size:10px;\" value=\"repeated\">"._("nur Wiederholungstermine")."</option>", ($resources_data["show_repeat_mode"] == "repeated") ? "selected" : "");
                    print "</select>";
                    print "&nbsp;<input type=\"IMAGE\" name=\"send_schedule_repeat_mode\" src=\"" . Assets::image_path('icons/16/blue/arr_2down.png') . "\" ".tooltip(_("Ansicht umschalten")).">";
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
                    if ((!$resources_data["schedule_time_range"]) || ($resources_data["schedule_time_range"] == -1))
                        printf ("<a href=\"%s?quick_view=%s&quick_view_mode=%s&time_range=%s\"><img src=\"" . Assets::image_path('icons/16/blue/arr_2down.png') . "\" %sborder=\"0\"></a>", $PHP_SELF, $this->used_view, $view_mode, ($resources_data["schedule_time_range"]) ? "FALSE" : 1, tooltip (_("Spätere Belegungen anzeigen")));
                    ?>
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="20%" nowrap colspan="3">
                &nbsp;
                </td>
            </tr>
        </table>
        </form>
    <?
        } else {
            ?>
            <table border="0" cellpadding="0" cellspacing="0" width="100%" align="center">
            <tr>
                <td align="center">
                <div style="font-size:150%;font-weight:bold;">
                <?=htmlReady($this->resources_groups->getGroupName($this->group_id))?>
                <br>
                <? echo htmlReady(strftime('%A, %x (KW %V)', $start_time));?>
                <br>
                <?php
                $this->showSemWeekNumber($start_time);
                ?>
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
            </table>
            <?
        }
    }
}
?>
