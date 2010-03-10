<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* my_archiv.php
*
* overview for achived Veranstaltungen
*
*
* @author       Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup  views
* @module       my_archiv.php
* @package      studip_core
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// my_archiv.php
// Anzeigeseite fuer persoenliche, archivierte Veranstaltungen
// Copyright (C) 2003 Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
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


page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check("user");

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page
require_once ('lib/visual.inc.php');        // htmlReady fuer die Veranstaltungsnamen
require_once ('lib/dates.inc.php');     // Semester-Namen fuer Admins
require_once ('lib/datei.inc.php');

$cssSw = new cssClassSwitcher;                          // Klasse für Zebra-Design
$cssSw->enableHover();
$db = new DB_Seminar;

// we are defintely not in an lecture or institute
closeObject();
$links_admin_data='';    //Auch im Adminbereich gesetzte Veranstaltungen muessen geloescht werden.
$HELP_KEYWORD="Basis.MeinArchiv";
$CURRENT_PAGE=_("Meine archivierten Veranstaltungen");

if (!$perm->have_perm('root')) {
    Navigation::activateItem('/browse/my_courses/archive');
}

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head

echo "\n" . $cssSw->GetHoverJSFunction() . "\n";

if (!isset($sortby))
    $sortby="name";
if ($sortby == "count")
    $sortby = "count DESC";

$db->query ("SELECT archiv.name, archiv.seminar_id, archiv_user.status, archiv.semester, archiv.archiv_file_id, archiv.forumdump, archiv.wikidump FROM archiv_user LEFT JOIN archiv  USING (seminar_id) WHERE archiv_user.user_id = '$user->id' GROUP BY seminar_id ORDER BY start_time DESC, $sortby");
$num_my_sem=$db->num_rows();
if (!$num_my_sem)
    $meldung.= "info§" . _("Es befinden sich zur Zeit keine Veranstaltungen im Archiv, an denen Sie teilgenommen haben.");

 ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr valign="top">
    <td class="blank" align="center"><br>
<?

if ($num_my_sem) {
    ?>
            <table cellpadding="1" cellspacing="0" width="98%">
                <tr>
                    <th width="1%"></th>
                    <th width="82%" align="left"><a href="<?= URLHelper::getLink("?sortby=name&view=". $view) ?>"><? echo(_("Name")) ?></a></th>
                    <th width="7%"><b><? echo(_("Inhalt")) ?></b></th>
                    <th width="10%"><a href="<?= URLHelper::getLink("?sortby=status&view=". $view) ?>"><? echo(_("Status")) ?></a></th>
                </tr>
    <?
    while ($db->next_record()) {
        $cssSw->switchClass();
        if ($last_sem != $db->f("semester")) {
            $cssSw->resetClass();
            $cssSw->switchClass();
            print "<tr><td class=\"steelkante\" colspan=\"4\"> <b>".$db->f("semester")."</b></td></tr>";
        }
        echo "<tr ".$cssSw->getHover()." >";
        echo "<td class=\"".$cssSw->getClass()."\"></td>";
        // name-field
        echo "<td class=\"".$cssSw->getClass()."\" ><a href=\"". URLHelper::getLink("archiv.php?dump_id=".$db->f('seminar_id')) ."\" target=\"_blank\">";
        echo htmlReady($db->f("name"));
        print ("</a></td>");
        // content-field
        echo "<td class=\"".$cssSw->getClass()."\" nowrap>";
        echo '&nbsp; ';
        // postings-field
        if ($db->f("forumdump"))
            echo "<a href=\"". URLHelper::getLink("archiv.php?forum_dump_id=".$db->f('seminar_id')) ."\" target=\"blank\">". Assets::img('icon-posting.gif', array('title' => 'Beiträge des Forums der Veranstaltung')) ."</a>";
        else
            echo Assets::img('icon-leer.gif');
        echo '&nbsp; ';
        // documents-field
        $file_name = _("Dateisammlung") . '-' . substr($db->f('name'),0,200) . '.zip';
        if ($db->f('archiv_file_id')) {
            echo "<a href=\"". URLHelper::getLink(GetDownloadLink($db->f('archiv_file_id'), $file_name, 1)) ."\">". Assets::img('icon-disc.gif', array('title' => 'Dateisammlung der Veranstaltung herunterladen')) ."</a>";
        } else {
            echo Assets::img('icon-leer.gif');
        }
        echo '&nbsp; ';
        // wiki-field
        if ($db->f("wikidump"))
            echo "<a href=\"". URLHelper::getLink("archiv.php?wiki_dump_id=".$db->f('seminar_id')) ."\" target=\"blank\">". Assets::img('icon-wiki.gif', array('title' => 'Beiträge des Wikis der Veranstaltung')) ."</a>";
        else
            echo Assets::img('icon-leer.gif');
        echo '</td>';
        //status-field
        echo "<td class=\"".$cssSw->getClass()."\" align=\"center\">". $db->f("status")."</td>";
        $last_sem=$db->f("semester");
    }
    echo "</table><br><br>";

} else {  // es sind keine Veranstaltungen abboniert

 ?>
        <table border="0" cellpadding="0" cellspacing="0" width="100%" align="center" class="blank">
    <?
    if ($meldung)   {
        parse_msg($meldung);
    }?>
        </table>
<?
}

//Info-field on the right side
?>

</td>
<td class="blank" width="270" align="right" valign="top">
<?

// Berechnung der uebrigen Seminare

$db->query("SELECT count(*) as count  FROM archiv");
$db->next_record();
$anzahltext = sprintf(_("Es befinden sich zur Zeit %s Veranstaltungen im Archiv."), ($db->f("count")));


// View for Teachers
$infobox = array    (
    array  ("kategorie"  => _("Information:"),
        "eintrag" => array  (
            array ( 'icon' => "ausruf_small.gif",
                            "text"  => $anzahltext
            )
        )
    ),
    array  ("kategorie" => _("Aktionen:"),
        "eintrag" => array  (
            array    (  'icon' => "suche2.gif",
                                "text"  => sprintf(_("Um Informationen &uuml;ber andere archivierte Veranstaltungen anzuzeigen nutzen Sie die <br />%sSuche im Archiv%s"), '<a href="'. URLHelper::getLink("archiv.php") .'">', '</a>')
            )
        )
    )
);

// print the info_box

print_infobox ($infobox, "archiv.jpg");

?>

        </td>
    </tr>
    <tr>
        <td class="blank" colspan="2">&nbsp;
        </td>
    </tr>
</table>
<?php
include ('lib/include/html_end.inc.php');
  // Save data back to database.
ob_end_flush(); //Outputbuffering beenden
page_close();
?>
