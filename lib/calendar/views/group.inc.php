<?
/**
* group.inc.php
* 
* 
*
* @author		Peter Thienel <pthienel@web.de>
* @version		$Id: group.inc.php,v 1.2 2009/09/06 01:33:37 thienel Exp $
* @access		public
* @modulegroup	calendar
* @module		calendar
* @package	calendar
*/
/**
* workaround for PHPDoc
*
* Use this if module contains no elements to document !
* @const PHPDOC_DUMMY
*/
define("PHPDOC_DUMMY",true);
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// week.inc.php
//
// Copyright (c) 2003 Peter Tienel <pthienel@web.de> 
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

require("$ABSOLUTE_PATH_STUDIP/html_head.inc.php");

if ($forum["jshover"] == 1 AND $auth->auth["jscript"]) { // JS an und erwuenscht?
	echo "<script language=\"JavaScript\">";
	echo "var ol_textfont = \"Arial\"";
	echo "</script>";
	echo "<DIV ID=\"overDiv\" STYLE=\"position:absolute; visibility:hidden; z-index:1000;\"></DIV>";
	echo "<SCRIPT LANGUAGE=\"JavaScript\" SRC=\"".$GLOBALS['ASSETS_URL']."javascripts/overlib.js\"></SCRIPT>";
}

require("$ABSOLUTE_PATH_STUDIP/header.php");
require($ABSOLUTE_PATH_STUDIP . $RELATIVE_PATH_CALENDAR . "/views/navigation.inc.php");
include_once($ABSOLUTE_PATH_STUDIP . $RELATIVE_PATH_CALENDAR . "/lib/DbCalendarWeek.class.php");

$tab = create_group_view($_group_calendar, $atime, $st, $et, $calendar_sess_control_data['cal_group']);

echo "<table width=\"100%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\" align=\"center\">\n";
echo calendar_select_group($calendar_sess_control_data['cal_group']);
echo "<tr><td class=\"blank\" width=\"100%\" align=\"center\">\n";
echo "<table border=\"0\" width=\"100%\" cellspacing=\"1\" cellpadding=\"0\" class=\"steelgroup0\">\n";

echo "<td align=\"center\" width=\"10%\" height=\"40\"><a href=\"$PHP_SELF?cmd=group&atime=";
echo $atime - 86400 . "\">\n";
$tooltip = tooltip(_("zurück"));
echo "<img border=\"0\" src=\"{$GLOBALS['ASSETS_URL']}images/calendar_previous.gif\"$tooltip></a></td>\n";
echo "<td class=\"calhead\" width=\"80%\" class=\"cal\"><b>\n";

//echo $_group_calendar->calendars[0]->view->toString("LONG") . ", "
	//	. $_group_calendar->calendars[0]->view->getDate();
	
echo ldate($atime);
	
// event. Feiertagsnamen ausgeben
if ($hday = holiday($atime))
	echo "<br>" . $hday["name"];

echo "</b></td>\n";
echo "<td align=\"center\" width=\"10%\"><a href=\"$PHP_SELF?cmd=group&atime=";
echo $atime + 86400 . "\">\n";
$tooltip = tooltip(_("vor"));
echo "<img border=\"0\" src=\"{$GLOBALS['ASSETS_URL']}images/calendar_next.gif\"$tooltip></a></td>\n";
echo "</tr>\n";
echo "<tr><td colspan=\"3\">\n";
echo "<table width=\"100%\" border=\"0\" cellpadding=\"2\" cellspacing=\"1\" align=\"center\" class=\"steelgroup0\">\n";
echo $tab;
echo "</table>\n</td></tr></table>";
echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" align=\"center\">\n";
jumpTo($jmp_m, $jmp_d, $jmp_y);
echo "</table>\n";
echo "<tr><td class=\"blank\">&nbsp;";
?>