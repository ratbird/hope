<?
# Lifter002: TODO
# Lifter005: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
 * day.inc.php
 *
 * Shows the day calendar
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
SkipLinks::addIndex(_("Tagesansicht"), 'main_content', 100);

echo "<table width=\"100%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\">\n";
echo "<tr><td class=\"blank\" width=\"60%\" id=\"main_content\"><br>\n";
echo "<table width=\"100%\" class=\"blank\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
echo "<tr><td class=\"blank\" width=\"100%\">\n";
echo "<table class=\"steelgroup0\" width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\"><tr>\n";
echo "<td align=\"center\" width=\"10%\" height=\"40\"><a href=\"$PHP_SELF?cmd=showday&atime=";
echo $atime - 86400 . "\">\n";
$tooltip = tooltip(_("zurück"));
echo "<img border=\"0\" src=\"".Assets::image_path('icons/16/blue/arr_2left.png')."\"$tooltip></a></td>\n";
echo "<td class=\"calhead\" width=\"80%\" class=\"cal\"><b>\n";

echo $aday->toString("LONG") . ", " . $aday->getDate();
// event. Feiertagsnamen ausgeben
if ($hday = holiday($atime))
    echo "<br>" . $hday["name"];

echo "</b></td>\n";
echo "<td align=\"center\" width=\"10%\"><a href=\"$PHP_SELF?cmd=showday&atime=";
echo $atime + 86400 . "\">\n";
$tooltip = tooltip(_("vorwärts"));
echo "<img border=\"0\" src=\"".Assets::image_path('icons/16/blue/arr_2right.png')."\"$tooltip></a></td>\n";
echo "</tr>\n";

if ($st > 0) {
    echo "<tr><td align=\"center\" colspan=\"3\"><a href=\"$PHP_SELF?cmd=showday&atime=";
    echo ($atime - ($at - $st + 1) * 3600) . "\">";
    $tooltip = tooltip(_("zeige davor"));
    echo "<img border=\"0\" src=\"".Assets::image_path('icons/16/blue/arr_2up.png')."\"$tooltip></a></td></tr>\n";
}
echo "</table>\n</td></tr>\n<tr><td class=\"blank\">\n";
echo "<table class=\"steelgroup0\" width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"1\">";

echo $tab["table"];

if ($et < 23) {
    echo "<tr><td align=\"center\" colspan=\"" . $tab["max_columns"] . "\">";
    echo "<a href=\"$PHP_SELF?cmd=showday&atime=";
    echo ($atime + ($et - $at + 1) * 3600) . "\">";
    $tooltip = tooltip(_("zeige danach"));
    echo "<img border=\"0\" src=\"".Assets::image_path('icons/16/blue/arr_2down.png')."\"$tooltip></a></td></tr>\n";
}
else
    echo "<tr><td colspan=\"" . $tab["max_columns"] . "\">&nbsp;</td></tr>\n";

echo "</table>\n</td></tr>\n</table>\n<td width=\"40%\" valign=\"top\" class=\"blank\"><br>\n";
echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
echo "<tr><td>\n";
echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
jumpTo($jmp_m, $jmp_d, $jmp_y);
echo "</table></td></tr>\n";
echo "<tr><td align=\"center\">".includeMonth(Request::int('imt', $atime), '?cmd=showday', '', '', $atime)."</td></tr>\n";
echo "</table>\n";
echo "</td></tr>\n</table>\n";
?>
