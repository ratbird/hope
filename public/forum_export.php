<?
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
forum_export.php - Druck-Anzeige des Forensystems
Copyright (C) 2002 Ralf Stockmann <rstockm@gwdg.de>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/


require '../lib/bootstrap.php';

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

if (!isset($SessSemName[0]) || $SessSemName[0] == "") {
    header("Location: index.php");
   die;
}

// -- here you have to put initialisations for the current page
if (!isset($htmlversion)) {
    PageLayout::removeStylesheet('style.css');
    PageLayout::addStylesheet('print.css'); // use special stylesheet for printing
}

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head

echo "<body bgcolor=\"white\">";

require_once('lib/visual.inc.php');
require_once('lib/archiv.inc.php');

if ($htmlversion)
    echo "<a href=\"".URLHelper::getLink()."\">" . _("zur Druck-Ansicht") . "</a>";
else
    echo "<a href=\"".URLHelper::getLink("?htmlversion=true")."\">" . _("zur HTML-Ansicht") . "</a>";

echo "<h1>" . _("Forum:") . "&nbsp; ".htmlReady($SessSemName[0])."</h1>";
echo Export_Topic($SessSemName[1]);

echo "<table width=100% border=0 cellpadding=2 cellspacing=0>";
echo "<tr><td><i><font size=-1>" . _("Stand:") . " ".date("d.m.y, G:i",time())."</font></i></td><td align=\"right\"><font size=-2><img src=\"".$GLOBALS['ASSETS_URL']."images/logos/logo2b.gif\"><br>&copy; ".date("Y", time())." v.$SOFTWARE_VERSION&nbsp; &nbsp; </font></td></tr>";
echo "</table>\n";
include ('lib/include/html_end.inc.php');
  // Save data back to database.
  page_close()
 ?>
