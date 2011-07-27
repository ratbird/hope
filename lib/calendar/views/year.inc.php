<?
# Lifter002: TODO
# Lifter005: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
 * year.inc.php
 *
 * Shows the year calendar
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

// add skip links
SkipLinks::addIndex(_("Jahresansicht"), 'main_content', 100);

echo "<table width=\"100%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\">\n";
echo "<tr><td class=\"blank\" width=\"100%\" id=\"main_content\"><br>\n";
echo "<table class=\"blank\" border=\"0\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\">\n";
echo "<tr><td class=\"blank\">\n";
echo "<table class=\"steelgroup0\" width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
echo "<tr><td align=\"center\" width=\"10%\">\n";
echo "<a href=\"$PHP_SELF?cmd=showyear&atime=" . ($ayear->getStart() - 1) . "\">";
echo "<img border=\"0\" src=\"".Assets::image_path('icons/16/blue/arr_2left.png')."\"";
echo tooltip(_("zurück")) . ">&nbsp;</a></td>\n";
echo "<td class=\"calhead\" align=\"center\" width=\"80%\">\n";
echo "<font size=\"+2\"><b>" . $ayear->getYear() . "</b></font></td>\n";
echo "<td align=\"center\" width=\"10%\"><a href=\"$PHP_SELF?cmd=showyear&atime=";
echo ($ayear->getEnd() + 1) . "\">\n";
echo "<img border=\"0\" src=\"".Assets::image_path('icons/16/blue/arr_2right.png')."\"";
echo tooltip(_("vorwärts")) . ">&nbsp;</a></td>\n";
echo "</tr></table>\n</td></tr>\n";
echo "<tr><td class=\"blank\">";
echo "<table class=\"steelgroup0\" width=\"100%\" border=\"0\" cellpadding=\"2\" cellspacing=\"1\">\n";

$days_per_month = array(31, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
if (date("L", $ayear->getStart()))
    $days_per_month[2] = 29;

echo "<tr>";
for ($i = 1; $i < 13; $i++) {
    $ts_month += ($days_per_month[$i] - 1) * 86400;
    echo "<td align=\"center\" width=\"8%\">";
    echo "<a class=\"calhead\" href=\"" . $PHP_SELF;
    echo "?cmd=showmonth&atime=" . ($ayear->getStart() + $ts_month) . "\">";
    echo "<font size=\"-1\"><b>";
    echo htmlentities(strftime("%B", $ts_month), ENT_QUOTES);
    echo "</b></font></a></td>\n";
}
echo "</tr>\n";

$now = date('Ymd');
for ($i = 1; $i < 32; $i++)
{
    echo "<tr>";
    for ($month = 1; $month < 13; $month++)
    {
        $aday = mktime(12, 0, 0, $month, $i, $ayear->getYear());

        if($i <= $days_per_month[$month])
        {
            $wday = date('w', $aday);
            // emphesize current day
            if (date('Ymd', $aday) == $now)
                $day_class = ' class="celltoday"';
            else if ($wday == 0 || $wday == 6)
                $day_class = ' class="weekend"';
            else
                $day_class = ' class="weekday"';

            if ($month == 1)
                echo "<td$day_class height=\"25\">";
            else
                echo "<td$day_class>";

            if($apps = $ayear->numberOfEvents($aday)) {
                echo "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\"><tr>";
                echo "<td$day_class>";
            }

            $weekday = "<font size=\"2\">" . wday($aday, "SHORT") . "</font>";

            // noch wird nicht nach Wichtigkeit bestimmter Feiertage unterschieden
            $hday = holiday($aday);
            switch ($hday["col"]) {

                case "1":
                    if (date("w", $aday) == "0") {
                        $style_day = 'sday';
                    }
                    else
                        $style_day = 'day';
                    break;
                case "2":
                case "3":
                    if (date("w", $aday) == "0") {
                        $style_day = 'sday';
                    }
                    else
                        $style_day = 'hday';
                    break;
                default:
                    if (date("w", $aday) == "0") {
                        $style_day = 'sday';
                    }
                    else
                        $style_day = 'day';
            }
            echo "<a class=\"$style_day\" href=\"$PHP_SELF?cmd=showday&atime=$aday\" ";
            echo tooltip($hday['name']) . "><b>$i</b></a> " . $weekday;

            if  ($apps) {
                if  ($apps > 1) {
                    echo "</td><td$day_class align=\"right\">";
                    echo "<img src=\"".Assets::image_path('icons/16/blue/date.png')."\" ";
                    echo tooltip(sprintf(_("%s Termine"), $apps)) . " border=\"0\">";
                    echo "</td></tr></table>\n";
                }
                else {
                    echo "</td><td$day_class align=\"right\">";
                    echo "<img src=\"".Assets::image_path('icons/16/blue/date.png')."\" ";
                    echo tooltip(_("1 Termin")) . " border=\"0\">";
                    echo "</td></tr></table>";
                }
            }
            echo "</td>";
        }
        else
            echo "<td class=\"weekday\">&nbsp;</td>";
    }
    echo "</tr>\n";
}
echo "<tr>";
$ts_month = 0;
for ($i = 1; $i < 13; $i++)
{
    $ts_month += ($days_per_month[$i] - 1) * 86400;
    echo "<td align=\"center\" width=\"8%%\">";
    echo "<a class=\"calhead\" href=\"" . $PHP_SELF;
    echo "?cmd=showmonth&atime=" . ($ayear->getStart() + $ts_month) . "\">";
    echo "<font size=\"-1\"><b>";
    echo htmlentities(strftime("%B", $ts_month), ENT_QUOTES);
    echo "</b></font></a></td>\n";
}
echo "</tr></table>\n</td></tr>\n";
jumpTo($jmp_m, $jmp_d, $jmp_y);
echo "\n</table>\n</td></tr></table>\n";
?>
