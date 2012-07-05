<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* my_stm.php
*
* overview for Studienmodule
*
*
* @author       André Noack <noack@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup  views
* @module       my_stm.php
* @package      studip_core
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// my_stm.php
// Anzeigeseite fuer
// Copyright (C) 2006 André Noack <noack@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
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

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check("dozent");

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page
require_once ('lib/visual.inc.php');        // htmlReady fuer die Veranstaltungsnamen
require_once ('lib/classes/StudipStmInstance.class.php');


$cssSw = new cssClassSwitcher;                          // Klasse für Zebra-Design
$cssSw->enableHover();

// we are defintely not in an lexture or institute
closeObject();
$_SESSION['links_admin_data']='';    //Auch im Adminbereich gesetzte Veranstaltungen muessen geloescht werden.

PageLayout::setTitle(_("Meine Studienmodule"));
if (!$perm->have_perm('root')) {
    Navigation::activateItem('/browse/my_courses/modules');
}

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head

echo "\n" . $cssSw->GetHoverJSFunction() . "\n";

$num_my_mod = 0;
$my_stm = array();
$all_sems = array();

$query = "SELECT su.seminar_id, IF(s.visible = 0, CONCAT(s.Name, ?), s.Name) AS Name,
                 stm_instance_id,
                 sd1.name AS startsem, IF(duration_time = -1, ?, sd2.name) AS endsem
          FROM seminar_user AS su
          INNER JOIN seminare AS s ON (su.seminar_id = s.Seminar_id)
          LEFT JOIN stm_instances_elements AS sie ON (su.seminar_id = sem_id)
          LEFT JOIN semester_data AS sd1 ON (start_time BETWEEN sd1.beginn AND sd1.ende)
          LEFT JOIN semester_data AS sd2 ON (start_time + duration_time BETWEEN sd2.beginn AND sd2.ende)
          WHERE su.user_id = ? AND su.status = 'dozent'";
$statement = DBManager::get()->prepare($query);
$statement->execute(array(_('(versteckt)'), _('unbegrenzt'), $user->id));
while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
    $my_stm[$row['stm_instance_id']][] = $row;
}

$query = "SELECT stm_instance_id FROM stm_instances WHERE responsible = ?";
$statement = DBManager::get()->prepare($query);
$statement->execute(array($user->id));
while ($id = $statement->fetchColumn()) {
    if (!isset($my_stm[$id])) {
        $my_stm[$id][] = array();
    }
}

if (!count($my_stm))
    $meldung.= "info§" . _("Es sind zur Zeit keine Ihrer Veranstaltungen zu Modulen zugeordnet.");

?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<?

if (count($my_stm)) {
    ?>
    <tr valign="top">
        <td class="blank" colspan="2">&nbsp;
        </td>
    </tr>
    <tr valign="top">
        <td valign="top" class="blank" align="center">
            <table border="0" cellpadding="1" cellspacing="0" width="98%" align="center" valign="top" class="blank">
                <tr align="center" valign="top">
                    <th width="1%"></th>
                    <th width="72%" align="left"><? echo(_("Name")) ?></th>
                    <th width="17%" align="left"><? echo(_("Verantwortlich")) ?></th>
                    <th width="10%" align="left"><? echo(_("Status")) ?>&nbsp;</th>
                </tr>
    <?
    foreach($my_stm as $stm_id => $sems) {
        $cssSw->resetClass();
        $cssSw->switchClass();
        $stm = new StudipStmInstance($stm_id);
        if ($stm->getValue("responsible") == $user->id) ++$num_my_mod;
        echo "<tr>";
        echo "<td class=\"steelkante\" colspan=\"2\">&nbsp;<b><a href=\"stm_details.php?stm_instance_id=".$stm->getId()."\" class=\"tree\">".$stm->getValue("displayname")."</a></b></td>";
        echo "<td class=\"steelkante\">&nbsp;<b><a href=\"about.php?username=".get_username($stm->getValue("responsible"))."\" class=\"tree\">".htmlReady(get_fullname($stm->getValue("responsible"), 'no_title_short'))."</a></b></td>";
        echo "<td class=\"steelkante\">&nbsp;<b><font size=-1>".($stm->getValue("complete") ? _("Vollständig") : _("Unvollständig") )."</font></b></td>";
        echo "</tr>";
        foreach($sems as $one_sem){
            if(isset($one_sem['seminar_id'])){
                $all_sems[$one_sem['seminar_id']] = 1;
                $name = $one_sem['Name']
                    . " (".$one_sem['startsem']
                    . ($one_sem['startsem'] != $one_sem['endsem'] ? " - ".$one_sem['endsem'] : "")
                    . ")";
                echo "<tr ".$cssSw->getHover()." >";
                echo "<td class=\"".$cssSw->getClass()."\">&nbsp; </td>";
                // name-field
                echo "<td class=\"".$cssSw->getClass()."\" colspan=\"4\"><a href=\"details.php?sem_id=".$one_sem['seminar_id']."\">";
                echo "<font size=-1>".htmlReady($name)."</font>";
                echo ("</a></td>");
                echo "</tr>";
            } else {
                echo "<tr ".$cssSw->getHover()." >";
                echo "<td class=\"".$cssSw->getClass()."\" colspan=\"4\">&nbsp;</td>";
                echo "</tr>";
            }
        }
    }
    echo "</table><br><br>";

} else {  // es sind keine Veranstaltungen abboniert

 ?>
 <tr>
 <tr>
    <td class="blank" colspan="2">&nbsp;
    </td>
 </tr>
     <td valign="top" class="blank">
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





// View for Teachers
$infobox = array    (
    array  ("kategorie"  => _("Information:"),
        "eintrag" => array  (
            array ( "icon" => "icons/16/black/info.png",
                            "text"  => sprintf(_("Es sind zur Zeit %s Veranstaltungen zu Studienmodulen zugewiesen."), count($all_sems))
            ),
            array    (  "icon" => "blank.gif",
                                "text"  => sprintf(_("Sie sind in %s Modulen als Verantwortlicher eingetragen."), $num_my_mod)
            ),
            array    (  "icon" => "icons/16/black/search.png",
                                "text"  => _("Um mehr Informationen &uuml;ber ein Studienmodul anzuzeigen, klicken Sie bitte aus den Namen des Moduls.")
            )
        )
    ),
    array  ("kategorie" => _("Aktionen:"),
        "eintrag" => array  (
            array    (  "icon" => "icons/16/black/search.png",
                                "text"  => sprintf(_("Um Informationen &uuml;ber alle Studienmodule anzuzeigen nutzen Sie die <br> %sSuche nach Studienmodulen%s"), '<a href="sem_portal.php?view=mod&reset_all=TRUE">', '</a>')
            )
        )
    )
);

// print the info_box

print_infobox ($infobox, "infobox/lectures.jpg");

?>

        </td>
    </tr>
    <tr>
        <td class="blank" colspan="2">&nbsp;
        </td>
    </tr>
</table>
<?
include "lib/include/html_end.inc.php";
// Save data back to database.
page_close();
?>
