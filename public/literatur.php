<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/*
literatur.php - Literaturanzeige von Stud.IP
Copyright (C) 2000 André Noack <anoack@mcis.de>, Cornelis Kater <ckater@gwdg.de>, Stefan Suchi <suchi@gmx.de>

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

unregister_globals();
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Default_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$auth->login_if($again && ($auth->auth["uid"] == "nobody"));

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

require_once('lib/classes/StudipLitList.class.php');
// -- here you have to put initialisations for the current page

checkObject(); // do we have an open object?
checkObjectModule("literature");
object_set_visit_module("literature");

PageLayout::setHelpKeyword("Basis.Literatur");
PageLayout::setTitle($SessSemName["header_line"]. " - " . _("Literatur"));
Navigation::activateItem('/course/literature/view');

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head

?>
<table width="100%" border="0" cellpadding="2" cellspacing="0">
    <tr>
    <td class="blank" align="left" valign="top">
    <table width="100%" border="0" cellpadding="20" cellspacing="0">
        <tr><td align="left" class="blank">
<?
if ( ($list = StudipLitList::GetFormattedListsByRange($SessSemName[1], object_get_visit($SessSemName[1], "literature"))) ){
    echo $list;
} else {
    echo _("Es wurde noch keine Literatur erfasst");
}
?>
        </td></tr>
    </table>
</td>
<td class="blank" width="270" align="right" valign="top">
<?
$infobox[0] = array(
    "kategorie" => _("Information:"),
    "eintrag" => array(array(
        "icon" => "icons/16/black/literature.png",
        "text" => _("Hier sehen Sie Literaturlisten.")
    ))
);
$infobox[1] = array (
    "kategorie" => _("Aktionen:"),
    "eintrag" => array(array(
        "icon" => "icons/16/black/add/literature.png",
        "text" => _("Sie k&ouml;nnen jede dieser Listen in Ihren pers&ouml;nlichen Literaturbereich kopieren, um erweiterte Informationen über die Eintr&auml;ge zu erhalten.")
    ))
);

print_infobox ($infobox, "infobox/literaturelist.jpg");
?>
        <br>
        </td>
    </tr>
</table>
<?
include ('lib/include/html_end.inc.php');
page_close();
