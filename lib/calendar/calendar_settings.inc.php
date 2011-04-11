<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
 * calendar_settings.inc.php
 *
 * Persoenlicher Terminkalender in Stud.IP.
 *
 * PHP version 5
 *
 * LICENSE
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @author     Peter Thienel <pthienel@web.de>
 * @author     Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @copyright  2003-2009 Stud.IP
 * @license    http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category   Stud.IP
 * @package    calendar
 */

// Imports
require_once 'lib/visual.inc.php';


if ($i_page == "calendar.php")
{
    include('lib/include/html_head.inc.php');
    include('lib/include/header.php');
}


// store user-settings
if($cmd_cal == 'chng_cal_settings')
{
    $calendar_user_control_data = array(
        'view'      => $cal_view,
        'start'     => $cal_start,
        'end'           => $cal_end,
        'step_day'      => $cal_step_day,
        'step_week' => $cal_step_week,
        'type_week' => $cal_type_week,
        'holidays'      => $cal_holidays,
        'sem_data'  => $cal_sem_data,
        'link_edit'     => $cal_link_edit,
        'delete'        => $cal_delete
    );
}

$css_switcher = new cssClassSwitcher();
$css_switcher->switchClass();

// print out form
?>
<table width="100%" cellspacing="0" cellpadding="0" border="0" align="center">
    <tr>
        <td class="blank" width="100%" colspan="2" align="center"><br>
            <p class="info">
                <b><?= _("Hier k&ouml;nnen Sie die Ansicht Ihres pers&ouml;nlichen Terminkalenders anpassen."); ?></b>
            </p>
            <form method="post" action="<? echo $PHP_SELF ?>?cmd_cal=chng_cal_settings">
            <?= CSRFProtection::tokenTag() ?>
            <table width ="70%" align="center" cellspacing="0" cellpadding="8" border="0" id="main_content">
                <tr>
                    <th width="50%" align=center><?=_("Option")?></th>
                    <th align=center><?=_("Auswahl")?></th>
                </tr>
                <tr>
                    <td align="right" class="blank" style="border-bottom:1px dotted black;">
                        <label for="cal_view"><? echo _("Startansicht anpassen:"); ?></label>
                    </td>
                    <td class="<? echo $css_switcher->getClass(); ?>">
                        <select name="cal_view" id="cal_view" size="1">
                            <option value="showweek"<?
                                if($calendar_user_control_data["view"] == "showweek")
                                    echo " selected";
                                echo ">" . _("Wochenansicht") . "</option>"; ?>
                            <option value="showday"<?
                                if($calendar_user_control_data["view"] == "showday")
                                    echo " selected";
                                echo ">" . _("Tagesansicht") . "</option>"; ?>
                            <option value="showmonth"<?
                                if($calendar_user_control_data["view"] == "showmonth")
                                    echo " selected";
                                echo ">" . _("Monatsansicht") . "</option>"; ?>
                            <option value="showyear"<?
                                if($calendar_user_control_data["view"] == "showyear")
                                    echo " selected";
                                echo ">" . _("Jahresansicht") . "</option>"; ?>
                        </select>
                    </td>
                </tr>
                <tr><? $css_switcher->switchClass(); ?>
                <td align="right" class="blank" style="border-bottom:1px dotted black;">
                    <? echo _("Zeitraum der Tages- und Wochenansicht:"); ?>
                </td>
                <td class="<? echo $css_switcher->getClass(); ?>">
                    <?
                    echo '<select name="cal_start" aria-label="' . _("Startzeit der Tages- und Wochenansicht") . '">';
                        for ($i=0; $i<=23; $i++)
                            {
                            if ($i==$calendar_user_control_data["start"])
                                {
                                echo "<option selected value=".$i.">";
                                if ($i<10)  echo "0".$i.":00";
                                else echo $i.":00";
                                echo "</option>";
                                }
                                else
                                    {
                                echo "<option value=".$i.">";
                                if ($i<10)  echo "0".$i.":00";
                                else echo $i.":00";
                                echo "</option>";
                                }
                            }
                        echo"</select>";
                    ?>
                    &nbsp;<? echo _("Uhr bis"); ?>
                    <?
                    echo '<select name="cal_end" aria-label="' . _("Endzeit der Tages- und Wochenansicht") . '">';
                        for ($i=0; $i<=23; $i++)
                            {
                            if ($i==$calendar_user_control_data["end"])
                                {
                                echo "<option selected value=".$i.">";
                                if ($i<10)  echo "0".$i.":00";
                                else echo $i.":00";
                                echo "</option>";
                                }
                                else
                                    {
                                echo "<option value=".$i.">";
                                if ($i<10)  echo "0".$i.":00";
                                else echo $i.":00";
                                echo "</option>";
                                }
                            }
                        echo"</select>";
                    ?>
                    &nbsp;<? echo _("Uhr."); ?>
                    </td>
                </tr>
                <tr><? $css_switcher->switchClass(); ?>
                    <td align="right" class="blank" style="border-bottom:1px dotted black;">
                        <label for="cal_step_day"><? echo _("Zeitintervall der Tagesansicht:"); ?></label>
                    </td>
                    <td class="<? echo $css_switcher->getClass(); ?>">
                        <select name="cal_step_day" id="cal_step_day" size="1">
                            <option value="600"<?
                                if($calendar_user_control_data["step_day"] == 600)
                                    echo " selected";
                                echo ">" . _("10 Minuten") . "</option>"; ?>
                            <option value="900"<?
                                if($calendar_user_control_data["step_day"] == 900)
                                    echo " selected";
                                echo ">" . _("15 Minuten") . "</option>"; ?>
                            <option value="1800"<?
                                if($calendar_user_control_data["step_day"] == 1800)
                                    echo " selected";
                                echo ">" . _("30 Minuten") . "</option>"; ?>
                            <option value="3600"<?
                                if($calendar_user_control_data["step_day"] == 3600)
                                    echo " selected";
                                echo ">" . _("1 Stunde") . "</option>"; ?>
                            <option value="7200"<?
                                if($calendar_user_control_data["step_day"] == 7200)
                                    echo " selected";
                                echo ">" . _("2 Stunden") . "</option>"; ?>
                        </select>
                    </td>
                </tr>
                <tr><? $css_switcher->switchClass(); ?>
                    <td align="right" class="blank" style="border-bottom:1px dotted black;">
                        <label for="cal_step_week"><? echo _("Zeitintervall der Wochenansicht:"); ?></label>
                    </td>
                    <td class="<? echo $css_switcher->getClass(); ?>">
                        <select name="cal_step_week" id="cal_step_week" size="1">
                            <option value="1800"<?
                                if($calendar_user_control_data["step_week"] == 1800)
                                    echo " selected";
                                echo ">" . _("30 Minuten") . "</option>"; ?>
                            <option value="3600"<?
                                if($calendar_user_control_data["step_week"] == 3600)
                                    echo " selected";
                                echo ">" . _("1 Stunde") . "</option>"; ?>
                            <option value="7200"<?
                                if($calendar_user_control_data["step_week"] == 7200)
                                    echo " selected";
                                echo ">" . _("2 Stunden") . "</option>" ?>
                        </select>
                    </td>
                </tr>
                <tr><? $css_switcher->switchClass(); ?>
                    <td align="right" class="blank" style="border-bottom:1px dotted black;">
                        <font size="-1"><? echo _("Wochenansicht anpassen:"); ?></font>
                    </td>
                    <td class="<? echo $css_switcher->getClass(); ?>">
                        <font size="-1">
                        <label><input type="radio" name="cal_type_week" value="LONG"<?
                            if($calendar_user_control_data["type_week"] == "LONG")
                                echo " checked";
                            echo '>&nbsp;' . _("7 Tage-Woche") . "</label><br>"; ?>
                        <label><input type="radio" name="cal_type_week" value="SHORT"<?
                            if($calendar_user_control_data["type_week"] == "SHORT")
                                echo " checked";
                            echo '>&nbsp;' . _("5 Tage-Woche") . '</label>'; ?>
                        </font>
                    </td>
                </tr>
        <?/*
                <tr><? $css_switcher->switchClass(); ?>
                    <td class="<? echo $css_switcher->getClass(); ?>">
                        <div class="indent">
                            <br><b><? echo _("Feiertage/Semesterdaten:"); ?></b>
                        </div>
                    </td>
                    <td class="<? echo $css_switcher->getClass(); ?>"><br>
                        <label><input type="checkbox" name="cal_holidays" value="TRUE"<?
                            if($calendar_user_control_data["holidays"])
                                echo " checked";
                            echo '>&nbsp;' . _("Feiertage anzeigen") . "</label><br>"; ?>
                        <label><input type="checkbox" name="cal_sem_data" value="5"<?
                            if($calendar_user_control_data["sem_data"])
                                echo " checked";
                            echo '>&nbsp;' . _("Semesterdaten anzeigen") . '</label>'; ?>
                    </td>
                </tr>
        */ ?>
                <tr><? $css_switcher->switchClass(); ?>
                    <td align="right" class="blank" style="border-bottom:1px dotted black;">
                        <font size="-1"><? echo _("Extras:"); ?></font>
                    </td>
                    <td class="<? echo $css_switcher->getClass(); ?>">
                        <font size="-1">
                        <label><input type="checkbox" name="cal_link_edit" value="TRUE"<?
                            if($calendar_user_control_data["link_edit"])
                                echo " checked";
                            echo '>&nbsp;' . _("Bearbeiten-Link in Wochenansicht") . '</label>'; ?>
                        </font>
                    </td>
                </tr>
                <tr><? $css_switcher->switchClass(); ?>
                    <td align="right" class="blank" style="border-bottom:1px dotted black;">
                        <label for="cal_delete"><? echo _("L&ouml;schen von Terminen:"); ?></label>
                    </td>
                    <td class="<? echo $css_switcher->getClass(); ?>">
                        <font size="-1">
                        <select name="cal_delete" id="cal_delete" size="1">
                            <option value="12"<?
                                if($calendar_user_control_data["delete"] == 12)
                                    echo " selected";
                                echo ">" . _("12 Monate nach Ablauf") . "</option><br>"; ?>
                            <option value="6"<?
                                if($calendar_user_control_data["delete"] == 6)
                                    echo " selected";
                                echo ">" . _("6 Monate nach Ablauf") . "</option><br>"; ?>
                            <option value="3"<?
                                if($calendar_user_control_data["delete"] == 3)
                                    echo " selected";
                                echo ">" . _("3 Monate nach Ablauf") . "</option><br>"; ?>
                            <option value="0"<?
                                if($calendar_user_control_data["delete"] == 0)
                                    echo " selected";
                                echo ">" . _("nie") . '</option>'; ?>
                        </select>
                        </font>
                    </td>
                </tr>
                <tr><? $css_switcher->switchClass(); ?>
                    <td class="<? echo $css_switcher->getClass(); ?>" colspan=2 align="middle">
                    <?
                        // sorgt fuer Ruecksprung in letzte Ansicht in kalender.php
                        if(substr(strrchr($PHP_SELF, "/"), 1) == "calendar.php" && !empty($calendar_sess_control_data["view_prv"]))
                            echo '<input type="hidden" name="cmd" value="'.$calendar_sess_control_data["view_prv"].'">';
                        if($atime)
                            echo '<input type="hidden" name="atime" value="'.$atime.'">';
                    ?>
                        <input type="hidden" name="view" value="calendar">
                        <? echo makeButton("uebernehmen" , "input", _("Änderungen übernehmen")); ?>
                    </td>
                </tr>
            </table>
            <br>
        </td>
    </tr>
</table>
</form>
