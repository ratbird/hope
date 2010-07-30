<?
/**
* navigation.inc.php
* 
* 
*
* @author		Peter Thienel <pthienel@web.de>
* @version		$Id: navigation.inc.php,v 1.2 2009/10/07 20:10:42 thienel Exp $
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
// navigation.inc.php
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
/*
if ($_calendar->getRange() == CALENDAR_RANGE_SEM || $_calendar->getRange() == CALENDAR_RANGE_INST) {
	include 'lib/include/links_openobject.inc.php';
} else {
    require 'lib/include/links_sms.inc.php';
}
*/
/*
if ($cmd != "changeview") {
	echo "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">\n";
	echo "<tr>\n";
	echo "<td class=\"topic\">&nbsp;<img src=\"{$GLOBALS['ASSETS_URL']}images/meinetermine.gif\" ";
	$tooltip = tooltip(_("Termine"));
	echo "border=\"0\" align=\"absmiddle\" $tooltip><b>&nbsp;";
	echo $_calendar->getHeadline() . "</b></td></tr>\n";
	echo "<tr><td class=\"blank\" width=\"100%\">&nbsp;</td></tr>\n";
	echo "</table>\n";
}
*/
?>