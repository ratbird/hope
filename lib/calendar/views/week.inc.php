<?
/**
* week.inc.php
* 
* 
*
* @author		Peter Thienel <pthienel@web.de>
* @version		$Id: week.inc.php,v 1.4 2009/10/07 20:10:42 thienel Exp $
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

include 'lib/include/html_head.inc.php';

if ($forum["jshover"] == 1 AND $auth->auth["jscript"]) { // JS an und erwuenscht?
	echo "<script language=\"JavaScript\">";
	echo "var ol_textfont = \"Arial\"";
	echo "</script>";
	echo "<DIV ID=\"overDiv\" STYLE=\"position:absolute; visibility:hidden; z-index:1000;\"></DIV>";
	echo "<SCRIPT LANGUAGE=\"JavaScript\" SRC=\"".$GLOBALS['ASSETS_URL']."javascripts/overlib.js\"></SCRIPT>";
}

include_once $RELATIVE_PATH_CALENDAR . '/lib/DbCalendarWeek.class.php';
$view = $_calendar->toStringWeek($atime, $st, $et, $_REQUEST['cal_restrict'], Calendar::getBindSeminare($_calendar->getUserId()));
$CURRENT_PAGE = $_calendar->getHeadline();
include 'lib/include/header.php';
include $RELATIVE_PATH_CALENDAR . '/views/navigation.inc.php';

$print_jump_to = false;
echo "<table width=\"100%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\" align=\"center\">\n";
if ($auth->auth['jscript'] && $auth->auth["xres"] > 1024) {
	if ($GLOBALS['CALENDAR_GROUP_ENABLE']) {
		echo "<tr><td class=\"blank\" width=\"25%\" nowrap=\"nowrap\">\n";
		echo jumpTo($jmp_month, $jmp_day, $jmp_year);
		echo "</td><td class=\"blank\" width=\"25%\" nowrap=\"nowrap\">\n";
		echo restrict_category($cal_restrict['studip_category'], $cmd, $atime);
		echo "</td><td class=\"blank\" width=\"50%\">";
		echo calendar_select($_calendar->getId());
		echo "</td></tr>\n";
	} else {
		echo "<tr><td class=\"blank\" nowrap=\"nowrap\" colspan=\"2\">\n";
		echo jumpTo($jmp_month, $jmp_day, $jmp_year);
		echo "</td><td class=\"blank\">";
		echo restrict_category($cal_restrict['studip_category'], $cmd, $atime);
		echo "</td></tr>\n";
	}
} else {
	if ($GLOBALS['CALENDAR_GROUP_ENABLE']) {
		echo "<tr><td class=\"blank\" nowrap=\"nowrap\" colspan=\"2\">\n";
		echo calendar_select($_calendar->getId());
		echo "</td><td class=\"blank\" width=\"50%\" nowrap=\"nowrap\">\n";
		echo restrict_category($cal_restrict['studip_category'], $cmd, $atime);
		echo "</td></tr>\n";
		$print_jump_to = TRUE;
	} else {
		echo "<tr><td class=\"blank\" nowrap=\"nowrap\" colspan=\"2\">\n";
		echo jumpTo($jmp_month, $jmp_day, $jmp_year);
		echo "</td><td class=\"blank\" width=\"50%\" nowrap=\"nowrap\">\n";
		echo restrict_category($cal_restrict['studip_category'], $cmd, $atime);
		echo "</td></tr>\n";
	}
}

echo "</table>";
echo '<table class="blank" border="0" cellpadding="0" cellspacing="0" style="width:100%; table-layout: fixed;"><tr><td style="width:100%; overflow:hidden; padding:0 1%;"><div style="overflow:auto; width:100%;">';

echo $view;

echo '</div></td></tr></table>';

echo '<table width="100%" border="0" cellpadding="0" cellspacing="0"><tr><td align="center" class="blank">';
if ($print_jump_to) {
	echo jumpTo($jmp_month, $jmp_day, $jmp_year);
} else {
	echo "<br />&nbsp;";
}
?>