<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
auswahl_suche.php - Uebersicht ueber die Suchfunktion von Stud.IP
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

include ("lib/seminar_open.php"); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page

PageLayout::setTitle(_("Studienmodule bearbeiten"));
// Start of Output
include ("lib/include/html_head.inc.php"); // Output of html head
include ("lib/include/header.php");   // Output of Stud.IP head

?>
<table width="70%" border=0 cellpadding=0 cellspacing=0 align="center">
<tr>
    <td class="table_header_bold" colspan="2"><?= Assets::img('icons/16/white/seminar.png')?> <b><?=_("Module in Stud.IP")?></b></td>
</tr>
<tr>
    <td class="blank">
        <?php if ($perm->have_perm('root') && get_config('STM_ENABLE') ) {?>
        <br><a href="stm_abstract_assi.php"><b><?=_("Allgemeine Module anlegen und bearbeiten")?></b></a><br>
        <font size=-1><?=_("Hier k&ouml;nnen Sie Allgemeine Module anlegen und bearbeiten.")?></font>
        <br>
        <?php } ?>

        <?php if ($perm->have_perm('dozent') && get_config('STM_ENABLE') ) {?>
        <br><a href="stm_instance_assi.php"><b><?=_("Konkrete Module anlegen und bearbeiten")?></b></a><br>
        <font size=-1><?=_("Hier k&ouml;nnen Administratoren Konkrete Module anlegen und Modulverantwortliche Ihre Konkreten Module bearbeiten.")?></font>
        <br>
        <?php }?>
        <? if (!get_config('STM_ENABLE')) {
            echo MessageBox::error(_('Diese Funktion ist deaktiviert. Bitte wenden Sie sich an Ihren Systemadministor'));
        } ?>
    </td>
    <td class="blank" align="right" valign="top"><img src="<?=$GLOBALS['ASSETS_URL']?>images/infobox/archiv.jpg" border="0"></td>
</tr>
</table>

<?  // Save data back to database.
    include ("lib/include/html_end.inc.php");
    page_close()
 ?>
