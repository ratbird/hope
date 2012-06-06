<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/*
copy_assi.php - Dummy zum Einstieg in Veranstaltungskopieren
Copyright (C) 2004 Tobias Thelen <tthelen@uni-osnabrueck.de>

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
$perm->check("dozent");

include ('lib/seminar_open.php'); // initialise Stud.IP-Session
require_once 'lib/functions.php';
require_once ('lib/classes/LockRules.class.php');
require_once 'lib/admin_search.inc.php';

// -- here you have to put initialisations for the current page

PageLayout::setTitle(_("Kopieren der Veranstaltung"));
Navigation::activateItem('/admin/course/copy');

//get ID from a open Institut
if ($SessSemName[1])
    $header_object_id = $SessSemName[1];
else
    $header_object_id = $admin_admission_data["sem_id"];

//Change header_line if open object
$header_line = getHeaderLine($header_object_id);
if ($header_line)
    PageLayout::setTitle($header_line." - ".PageLayout::getTitle());

//Output starts here

include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   //hier wird der "Kopf" nachgeladen
include 'lib/include/admin_search_form.inc.php';

require_once 'lib/visual.inc.php';
if ($SessSemName[1]) {
    if(LockRules::Check($SessSemName[1], 'seminar_copy')) {
        $lockdata = LockRules::getObjectRule($SessSemName[1]);
        $msg = 'error§' . _("Die Veranstaltung kann nicht kopiert werden.").'§';
        if ($lockdata['description']){
            $msg .= "info§" . formatLinks($lockdata['description']).'§';
        }
        ?>
        <table border=0 align="center" cellspacing=0 cellpadding=0 width="100%">
        <tr><td class="blank" colspan=2><br>
        <?
        parse_msg($msg);
        ?>
        </td></tr>
        </table>
        <?
    } else {
    ?>
        <table cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr><td class="blank" colspan=2>&nbsp;</td></tr>
        <tr><td class="blank" colspan=2>
        <blockquote>
        <?
        printf(_("Die Veranstaltung wurde zum Kopieren ausgewählt."). " ");
        printf(_("Um die vorgewählte Veranstaltung zu kopieren klicken Sie %shier%s."),
            '<a href="'.URLHelper::getLink('admin_seminare_assi.php?cmd=do_copy&cp_id='.$SessSemName[1].'&start_level=TRUE&class=1').'">',
            '</a>');
        ?>
        </blockquote>
        <br>
        </td></tr>
        </table>
    <?php
    }
}
include ('lib/include/html_end.inc.php');
page_close();
?>
