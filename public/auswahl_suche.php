<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
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

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));

$perm->check('user');

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page

$HELP_KEYWORD="Basis.Suchen";
$CURRENT_PAGE = _("Suche");

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head

?>
<table width="70%" border="0" cellpadding="0" cellspacing="0" align="center">

<tr>
    <td class="topic" colspan="2"><img src="<?= $GLOBALS['ASSETS_URL'] ?>images/suchen.gif" border="0"> <b><?=_("Suchen in Stud.IP")?></b></td>
</tr>
<tr>
<td class="blank" width="100%">
<blockquote>
    <br>
    <a href="<?=URLHelper::getLink('sem_portal.php')?>">
    <b><?=_("Suchen nach Veranstaltungen")?></b></a><br>
    <font size=-1><?=_("Hier finden Sie alle Veranstaltungen in Stud.IP.")?></font>
    <br>
    <br>
    <a href="<?=URLHelper::getLink('browse.php')?>"><b><?=_("Suchen nach Personen")?></b></a><br>
    <font size=-1><?=_("Hier k&ouml;nnen Sie nach Ihren, in Stud.IP registrierten, KommilitonInnen und Dozierenden suchen.")?></font>
    <br>
    <br><a href="<?=URLHelper::getLink('institut_browse.php')?>"><b><?=_("Suchen nach Einrichtungen")?></b></a><br>
    <font size=-1><?=_("Hier finden Sie alle Einrichtungen in Stud.IP.")?></font>
    <br>
    <?
    if ($RESOURCES_ENABLE) {
    ?>
    <br><a href="<?=URLHelper::getLink('resources.php',array('view'=>'search','view_mode'=>'search','new_search'=>'TRUE'))?>"><b><?=_("Suchen nach Ressourcen")?></b></a><br>
    <font size=-1><?=_("Hier finden Sie Ressourcen wie etwa R&auml;ume, Geb&auml;ude oder Ger&auml;te.")?></font>
    <br>
    <?
    }
    if ($ELEARNING_INTERFACE_ENABLE) {
    ?>
    <br><a href="<?=URLHelper::getLink('browse_elearning.php')?>"><b><?=_("Suchen nach Lernmodulen")?></b></a><br>
    <font size=-1><?=_("Hier finden Sie Lernmodule in angebundenen Systemen.")?></font>
    <br>
    <?
    }
    ?>
    <br><a href="<?=URLHelper::getLink('archiv.php')?>"><b><?=_("Suchen im Archiv")?></b></a><br>
    <font size=-1><?=_("Hier finden Sie alle archivierten Veranstaltungen vergangener Semester.")?></font>
    <br>
    <br><a href="<?=URLHelper::getLink('lit_search.php')?>"><b><?=_("Suchen nach Literatur")?></b></a><br>
    <font size=-1><?=_("Hier k&ouml;nnen Sie in verschiedenen Katalogen nach Literatur suchen.")?></font>
    <br>
    <br>
</blockquote>
</td>
<td class="blank" align="right" valign="top"><br><img src="<?= $GLOBALS['ASSETS_URL'] ?>images/suche.jpg" border="0"></td>
</tr>
</table>
<?php
    include ('lib/include/html_end.inc.php');
    page_close();
 ?>
