<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO
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



require '../lib/bootstrap.php';

unregister_globals();
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check("user");

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page
require_once ('lib/visual.inc.php');        // htmlReady fuer die Veranstaltungsnamen
require_once ('lib/dates.inc.php');     // Semester-Namen fuer Admins
require_once ('lib/datei.inc.php');

$cssSw = new cssClassSwitcher;                          // Klasse für Zebra-Design
$cssSw->enableHover();

// we are defintely not in an lecture or institute
closeObject();
$_SESSION['links_admin_data']='';    //Auch im Adminbereich gesetzte Veranstaltungen muessen geloescht werden.
PageLayout::setHelpKeyword("Basis.MeinArchiv");
PageLayout::setTitle(_("Meine archivierten Veranstaltungen"));

if (!$perm->have_perm('root')) {
    Navigation::activateItem('/browse/my_courses/archive');
}

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head

// would use Trails_Flash here, but this is not trails
if (isset($_SESSION['archive_message'])) {
    $meldung = $_SESSION['archive_message'];
    unset($_SESSION['archive_message']);
}

// add skip link
SkipLinks::addIndex(_("Hauptinhalt"), 'main_content', 100);

echo "\n" . $cssSw->GetHoverJSFunction() . "\n";

$sortby = Request::option('sortby', 'name');
$view = Request::option('view');
if ($sortby == 'count') {
    $sortby = 'count DESC';
}

$query = "SELECT COUNT(*) FROM archiv_user WHERE user_id = ?";
$statement = DBManager::get()->prepare($query);
$statement->execute(array($user->id));
$count = $statement->fetchColumn();

$query = "SELECT name, seminar_id, status, semester, archiv_file_id, forumdump, wikidump
          FROM archiv_user
          LEFT JOIN archiv USING (seminar_id)
          WHERE user_id = :user_id
          GROUP BY seminar_id
          ORDER BY start_time DESC, :sortby";
$statement = DBManager::get()->prepare($query);
$statement->bindValue(':user_id', $user->id);
$statement->bindValue(':sortby', $sortby, StudipPDO::PARAM_COLUMN);
$statement->execute();

if (!$count)
    $meldung.= "info§" . _("Es befinden sich zur Zeit keine Veranstaltungen im Archiv, an denen Sie teilgenommen haben.");

 ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr valign="top">
    <td class="blank" align="center"><br>
<? if ($count): ?>
            <table cellpadding="1" cellspacing="0" width="98%" id="main_content">
                <? if ($meldung) : ?>
                    <? parse_msg($meldung) ?>
                <? endif ?>
                <tr>
                    <th width="1%"></th>
                    <th width="82%" align="left"><a href="<?= URLHelper::getLink("?sortby=name&view=". $view) ?>"><? echo(_("Name")) ?></a></th>
                    <th width="7%"><b><? echo(_("Inhalt")) ?></b></th>
                    <th width="10%"><a href="<?= URLHelper::getLink("?sortby=status&view=". $view) ?>"><? echo(_("Status")) ?></a></th>
                </tr>
    <?
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $cssSw->switchClass();
        if ($last_sem != $row['semester']) {
            $cssSw->resetClass();
            $cssSw->switchClass();
            print "<tr><td class=\"steelkante\" colspan=\"4\"> <b>".$row['semester']."</b></td></tr>";
        }
        echo "<tr ".$cssSw->getHover()." >";
        echo "<td class=\"".$cssSw->getClass()."\"></td>";
        // name-field
        echo "<td class=\"".$cssSw->getClass()."\" ><a href=\"". URLHelper::getLink("archiv.php?dump_id=".$row['seminar_id']) ."\" target=\"_blank\">";
        echo htmlReady($row['name']);
        print ("</a></td>");
        // content-field
        echo "<td class=\"".$cssSw->getClass()."\" nowrap>";
        echo '&nbsp; ';
        // postings-field
        if ($row['forumdump'])
            echo "<a href=\"". URLHelper::getLink("archiv.php?forum_dump_id=".$row['seminar_id']) ."\" target=\"blank\">". Assets::img('icons/16/blue/forum.png', array('title' => 'Beiträge des Forums der Veranstaltung')) ."</a>";
        else
            echo Assets::img('blank.gif', array('size' => '16'));
        echo '&nbsp; ';
        // documents-field
        $file_name = _("Dateisammlung") . '-' . substr($row['name'],0,200) . '.zip';
        if ($row['archiv_file_id']) {
            echo "<a href=\"". URLHelper::getLink(GetDownloadLink($row['archiv_file_id'], $file_name, 1)) ."\">". Assets::img('icons/16/blue/download.png', array('title' => 'Dateisammlung der Veranstaltung herunterladen')) ."</a>";
        } else {
            echo Assets::img('blank.gif', array('size' => '16'));
        }
        echo '&nbsp; ';
        // wiki-field
        if ($row['wikidump'])
            echo "<a href=\"". URLHelper::getLink("archiv.php?wiki_dump_id=".$row['seminar_id']) ."\" target=\"blank\">". Assets::img('icons/16/blue/wiki.png', array('title' => 'Beiträge des Wikis der Veranstaltung')) ."</a>";
        else
            echo Assets::img('blank.gif', array('size' => '16'));
        echo '</td>';
        //status-field
        echo "<td class=\"".$cssSw->getClass()."\" align=\"center\">". $row['status']."</td>";
        $last_sem = $row['semester'];
    }
    echo "</table><br><br>";

else:  // es sind keine Veranstaltungen abboniert

 ?>
        <table border="0" cellpadding="0" cellspacing="0" width="100%" align="center" class="blank" id="main_content">
    <?
    if ($meldung)   {
        parse_msg($meldung);
    }?>
        </table>
<? endif; 

//Info-field on the right side
?>

</td>
<td class="blank" width="270" align="right" valign="top">
<?

// Berechnung der uebrigen Seminare

$count = DBManager::get()->query("SELECT COUNT(*) FROM archiv")->fetchColumn();
$anzahltext = sprintf(_("Es befinden sich zur Zeit %s Veranstaltungen im Archiv."), $count);


// View for Teachers
$infobox = array    (
    array  ("kategorie"  => _("Information:"),
        "eintrag" => array  (
            array ( 'icon' => 'icons/16/black/info.png',
                    "text" => $anzahltext
            )
        )
    ),
    array  ("kategorie" => _("Aktionen:"),
        "eintrag" => array  (
            array ( 'icon' => 'icons/16/black/search.png',
                    "text" => sprintf(_("Um Informationen &uuml;ber andere archivierte Veranstaltungen anzuzeigen nutzen Sie die %sSuche im Archiv%s"), '<a href="'. URLHelper::getLink("archiv.php") .'">', '</a>')
            )
        )
    )
);

// print the info_box

print_infobox($infobox, "infobox/archiv.jpg");

?>

        </td>
    </tr>
    <tr>
        <td class="blank" colspan="2">&nbsp;</td>
    </tr>
</table>
<?php
include ('lib/include/html_end.inc.php');
page_close();
