<?
# Lifter002: TODO
# Lifter005: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
 * month.inc.php
 *
 * Shows the month calendar
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

// Begin of output
include('lib/include/html_head.inc.php');

include('lib/include/header.php');

// add skip link
SkipLinks::addIndex(_("Monatsansicht"), 'main_content', 100);

echo "<table width=\"100%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\">\n";
echo "<tr><td class=\"blank\" width=\"100%\" id=\"main_content\"><br>\n";
echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">\n";
echo "<tr><td>\n";
echo "<table width=\"100%\" class=\"steelgroup0\" border=\"0\" cellspacing=\"1\" cellpadding=\"0\" align=\"center\">\n";
echo "<tr><td>\n";

echo "<table width=\"100%\" border=\"0\" cellspacing=\"1\" cellpadding=\"1\">\n";
echo "<tr>\n<td align=\"center\">";
printf("&nbsp;<a href=\"%s?cmd=showmonth&atime=%s\">",
    $PHP_SELF, mktime(12, 0, 0, $amonth->getMonth(),
            date('j', $amonth->getStart()), date('Y', $amonth->getStart()) - 1));
$tooltip = tooltip(_("ein Jahr zurück"));
echo "<img border=\"0\" src=\"".Assets::image_path('icons/16/blue/arr_eol-left.png')."\"$tooltip></a>";
printf("&nbsp; &nbsp; &nbsp; &nbsp;<a href=\"%s?cmd=showmonth&atime=%s\">",
    $PHP_SELF, $amonth->getStart() - 1);
$tooltip = tooltip(_("einen Monat zurück"));
echo "<img border=\"0\" src=\"".Assets::image_path('icons/16/blue/arr_2left.png')."\"$tooltip></a>&nbsp;</td>\n";
printf("<td colspan=%s class=\"calhead\">\n", $mod == "nokw" ? "5" : "6");
echo "<font size=\"+2\">";
echo htmlentities(strftime("%B ", $amonth->getStart()), ENT_QUOTES) . $amonth->getYear();
echo "</font></td>\n";
printf("<td align=\"center\">&nbsp;<a href=\"%s?cmd=showmonth&atime=%s\">",
    $PHP_SELF, $amonth->getEnd() + 1);
$tooltip = tooltip(_("einen Monat vor"));
echo "<img border=\"0\" src=\"".Assets::image_path('icons/16/blue/arr_2right.png')."\"$tooltip></a>";
printf("&nbsp; &nbsp; &nbsp; &nbsp;<a href=\"%s?cmd=showmonth&atime=%s\">",
    $PHP_SELF, mktime(12, 0, 0, $amonth->getMonth(),
            date('j', $amonth->getStart()), date('Y', $amonth->getEnd()) + 1));
$tooltip = tooltip(_("ein Jahr vor"));
echo "<img border=\"0\" src=\"".Assets::image_path('icons/16/blue/arr_eol-right.png')."\"$tooltip></a></td>\n";
echo "</tr>\n<tr>\n";

$weekdays_german = array("MO", "DI", "MI", "DO", "FR", "SA", "SO");
foreach ($weekdays_german as $weekday_german)
    echo "<td class=\"precol1w\" width=\"$width\">" . wday("", "SHORT", $weekday_german) . "</td>";

if($mod != "nokw")
    echo "<td align=\"center\" class=\"precol1w\" width=\"$width\">" . _("Woche") . "</td>\n";
echo "</tr></table>\n</td></tr>\n";

echo "<tr><td class=\"blank\">\n";
echo "<table width=\"100%\" class=\"steelgroup0\" border=\"0\" cellspacing=\"1\" cellpadding=\"1\">\n";

// Im Kalenderblatt ist links oben immer Montag. Das muss natuerlich nicht der
// Monatserste sein. Es muessen evtl. noch Tage des vorangegangenen Monats
// am Anfang und des folgenden Monats am Ende angefuegt werden.

$adow = strftime("%u", $amonth->getStart()) - 1;

$first_day = $amonth->getStart() - $adow * 86400 + 43200;
// Ist erforderlich, um den Maerz richtig darzustellen
// Ursache ist die Sommer-/Winterzeit-Umstellung
$cor = 0;
if ($amonth->getMonth() == 3)
    $cor = 1;

$last_day = ((42 - ($adow + date("t",$amonth->getStart()))) % 7 + $cor) * 86400
            + $amonth->getEnd() - 43199;

for ($i = $first_day, $j = 0; $i <= $last_day; $i += 86400, $j++)
{
    $aday = date("j", $i);
    // Tage des vorangehenden und des nachfolgenden Monats erhalten andere
    // style-sheets
    $class_day = '';
    if (($aday - $j - 1 > 0) || ($j - $aday  > 6)) {
        $class_cell = 'lightmonth';
        $class_day = 'light';
    }
    // emphesize current day
    else if (date('Ymd', $i) == date('Ymd'))
        $class_cell = 'celltoday';
    else
        $class_cell = 'month';

    // Feiertagsueberpruefung
    if ($mod != "compact" && $mod != "nokw")
        $hday = holiday($i);

    // wenn Feiertag dann nur 4 Termine pro Tag ausgeben, sonst wirds zu eng
    if ($hday["col"] > 0)
        $max_apps = 4;
    else
        $max_apps = 5;

    // week column
    if ($j % 7 == 0)
        echo "<tr>\n";
    echo "<td class=\"$class_cell\" valign=\"top\" width=\"$width\" height=\"$height\">&nbsp;";

    // sunday column
    if (($j + 1) % 7 == 0) {
        echo "<a class=\"{$class_day}sday\" href=\"$PHP_SELF?cmd=showday&atime=$i\">$aday</a>";
        month_up_down($amonth, $i, $step, $max_apps);

        if ($hday["name"] != "")
            echo "<br><font class=\"inday\">{$hday['name']}</font>";

        print_month_events($amonth, $max_apps, $i);

        echo "</td>\n";

        if ($mod != "nokw") {
            echo "<td class=\"lightmonth\" align=\"center\" width=\"$width\" height=\"$height\">";
            printf("<a class=\"calhead\" href=\"%s?cmd=showweek&atime=%s\"><b>%s</b></a></td>\n",
                $PHP_SELF, $i, strftime("%V", $i));
        }
        echo "</tr>\n";
    }
    else{
        // other days columns
        // unterschiedliche Darstellung je nach Art des Tages (Rang des Feiertages)
        switch ($hday["col"]) {
            case 1:
                echo "<a class=\"{$class_day}day\" href=\"$PHP_SELF?cmd=showday&atime=$i\">$aday</a>";
                month_up_down($amonth, $i, $step, $max_apps);
                echo "<br><font class=\"inday\">{$hday['name']}</font>";
                break;
            case 2:
                echo "<a class=\{$class_day}shday\" href=\"$PHP_SELF?cmd=showday&atime=$i\">$aday</a>";
                month_up_down($amonth, $i, $step, $max_apps);
                echo "<br><font class=\"inday\">{$hday['name']}</font>";
                break;
            case 3:
                echo "<a class=\"{$class_day}hday\" href=\"$PHP_SELF?cmd=showday&atime=$i\">$aday</a>";
                month_up_down($amonth, $i, $step, $max_apps);
                echo "<br><font class=\"inday\">{$hday['name']}</font>";
                break;
            default:
                echo "<a class=\"{$class_day}day\" href=\"$PHP_SELF?cmd=showday&atime=$i\">$aday</a>";
                month_up_down($amonth, $i, $step, $max_apps);
        }

        print_month_events($amonth, $max_apps, $i);

        echo "</td>\n";

    }
}

echo "</td></tr></table>\n</td></tr>\n";
echo "</table></td></tr></table>\n";
echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" align=\"center\">\n";
jumpTo($jmp_m, $jmp_d, $jmp_y);
echo "</table>\n";
echo "</td></tr>\n</table>\n";

/**
* Print a list of events for each day of month
*
* @access public
* @param object $month_obj instance of DbCalendarMonth
* @param int $max_events the number of events to print
* @param int $day_timestamp unix timestamp of the day
*/
function print_month_events ($month_obj, $max_events, $day_timestamp)
{
    global $PHP_SELF, $auth, $forum;

    $count = 0;
    while (($aterm = $month_obj->nextEvent($day_timestamp)) && $count < $max_events) {
        if (strtolower(get_class($aterm)) == "seminarevent") {
            $html_title = fit_title($aterm->getSemName(), 1, 1, 15);
            $jscript_title = JSReady($aterm->getSemName());
            $ev_type = "&evtype=sem";
        }
        else {
            $html_title = fit_title($aterm->getTitle(), 1, 1, 15);
            $jscript_title = JSReady($aterm->getTitle());
            $ev_type = "";
        }

        printf("<br><a class=\"inday\" href=\"%s?cmd=edit&termin_id=%s&atime=%s%s\"",
                $PHP_SELF, $aterm->getId(), $day_timestamp, $ev_type);

        echo js_hover($aterm) . '>';
        $category_style = $aterm->getCategoryStyle();
        printf("<font color=\"%s\">%s</font></a>", $category_style['color'], $html_title);
        $count++;
    }
}

/**
* Up-/down-navigation if there are more events per day than the given number
*
* @access private
* @param object &$month_obj instance of DbCalendarMonth
* @param int $day_timestamp unix timestamp of this day
* @param int $step the current step
* @param int $max_events the number of events per step
*/
function month_up_down (&$month_obj, $day_timestamp, $step, $max_events)
{
    //TODO: globals
    global $PHP_SELF, $atime, $CANONICAL_RELATIVE_PATH_STUDIP;
    if($atime == $day_timestamp)
    {
        $spacer = TRUE;
        $up = FALSE;
        $a = $month_obj->numberOfEvents($day_timestamp) - $step - $max_events;
        $up = ($month_obj->numberOfEvents($day_timestamp) > $max_events && $step >= $max_events);
        if($a + $max_events > $max_events)
        {
            if($up)
                echo "&nbsp; &nbsp; &nbsp;";
            else
                echo "&nbsp; &nbsp; &nbsp; &nbsp; &nbsp;";
            $tooltip = sprintf(_("noch %s Termine danach"), $a);
            $tooltip = tooltip($tooltip);
            echo "<a href=\"$PHP_SELF?cmd=showmonth&atime=$day_timestamp&step=";
            echo ($step + $max_events) . "\">";
            echo "<img src=\"".Assets::image_path('icons/16/blue/arr_1down.png')."\" ";
            echo $tooltip . " border=\"0\"></a>\n";
            $spacer = FALSE;
        }
        if($up)
        {
            if($spacer)
                echo "&nbsp; &nbsp; &nbsp; &nbsp; &nbsp;";
            $tooltip = sprintf(_("noch %s Termine davor"), $step);
            $tooltip = tooltip($tooltip);
            echo "<a href=\"$PHP_SELF?cmd=showmonth&atime=$day_timestamp&step=";
            echo ($step - $max_events) . "\">";
            echo "<img src=\"".Assets::image_path('icons/16/blue/arr_1up.png')."\" ";
            echo $tooltip . " border=\"0\"></a>\n";
            $month_obj->setPointer($atime, $step);
        }
    }
    else if($month_obj->numberOfEvents($day_timestamp) > $max_events)
    {
        echo "&nbsp; &nbsp; &nbsp; &nbsp; &nbsp;";
        $tooltip = sprintf(_("noch %s Termine danach"),
                $month_obj->numberOfEvents($day_timestamp) - $max_events);
        $tooltip = tooltip($tooltip);
        echo "<a href=\"$PHP_SELF?cmd=showmonth&atime=$day_timestamp&step=";
        echo ($max_events) . "\"><img src=\"".Assets::image_path('icons/16/blue/arr_1down.png')."\" ";
        echo $tooltip . " border=\"0\"></a>\n";
    }
}
?>
