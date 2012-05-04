<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/*
adminarea_start.php - Dummy zum Einstieg in Adminbereich
Copyright (C) 2001 Cornelis Kater <ckater@gwdg.de>

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
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check("tutor");

if (Request::option('select_sem_id')) {
    Request::set('cid', Request::option('select_sem_id'));
}

include ('lib/seminar_open.php'); // initialise Stud.IP-Session
require_once 'lib/admin_search.inc.php';

// -- here you have to put initialisations for the current page

PageLayout::setHelpKeyword("Basis.VeranstaltungenVerwalten");
PageLayout::setTitle(_('Verwaltung von Veranstaltungen'));
Navigation::activateItem('/admin/course');

// Start of Output
include 'lib/include/html_head.inc.php'; // Output of html head
include 'lib/include/header.php';   // Output of Stud.IP head
include 'lib/include/admin_search_form.inc.php';

require_once 'lib/visual.inc.php';

if ($SessSemName[1]) {
    ?>
    <table cellspacing="0" cellpadding="0" border="0" width="100%">
    <tr><td class="blank" colspan=2>&nbsp;</td></tr>
    <tr><td class="blank" colspan=2>
    <p class="info">
    <?
    if ($_SESSION['links_admin_data']['referred_from'] == "sem") {
        printf(_("Hier k&ouml;nnen Sie die Daten der Veranstaltung <b>%s</b> direkt bearbeiten.") . "<br>", htmlReady($SessSemName[0]));
        print(_("Wenn Sie eine andere Veranstaltung bearbeiten wollen, klicken Sie bitte auf <b>Veranstaltungen</b> um zum Auswahlmenü zurückzukehren.") . "<br>&nbsp;");
    } else {
        printf(_("Sie haben die Veranstaltung <b>%s</b> vorgew&auml;hlt. Sie k&ouml;nnen nun direkt die einzelnen Bereiche dieser Veranstaltung bearbeiten, indem Sie die entsprechenden Men&uuml;punkte w&auml;hlen.") . "<br>", htmlReady($SessSemName[0]));
        print(_("Wenn Sie eine andere Veranstaltung bearbeiten wollen, klicken Sie bitte auf <b>Veranstaltungen</b> um zum Auswahlmenü zurückzukehren.") . "<br>&nbsp;");
    }
    ?>
    </p>
    </td></tr>
    </table>
<?
}
include ('lib/include/html_end.inc.php');
page_close();
?>
