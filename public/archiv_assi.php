<?
# Lifter001: TEST
# Lifter002: TODO
# Lifter003: TEST
# Lifter007: TODO
# Lifter010: TODO
/**
* archiv_Assi.php - Archivierungs-Assistent von Stud.IP.
* Copyright (C) 2002 Cornelis Kater <ckater@gwdg.de>, data-quest <info@data-quest.de>
*
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; either version 2
* of the License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

use Studip\Button, Studip\LinkButton;

require '../lib/bootstrap.php';

unregister_globals();
require_once('lib/dates.inc.php'); // Funktionen zum Loeschen von Terminen
require_once('lib/datei.inc.php'); // Funktionen zum Loeschen von Dokumenten
require_once('lib/archiv.inc.php');
require_once 'lib/functions.php';
require_once('lib/visual.inc.php');
require_once('lib/statusgruppe.inc.php'); //Enthaelt Funktionen fuer Statusgruppen
require_once('lib/log_events.inc.php'); // Logging
require_once('lib/classes/DataFieldEntry.class.php'); //Enthaelt Funktionen fuer Statusgruppen
require_once('lib/classes/StudipLitList.class.php');
require_once('lib/classes/StudipNews.class.php');
require_once ($RELATIVE_PATH_ELEARNING_INTERFACE . "/ObjectConnections.class.php");
require_once ($RELATIVE_PATH_ELEARNING_INTERFACE . "/ELearningUtils.class.php");
require_once ('lib/classes/LockRules.class.php');
require_once 'lib/classes/Seminar.class.php';


page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", 'user' => "Seminar_User"));
$auth->login_if($auth->auth["uid"] == "nobody");

$check_perm = (get_config('ALLOW_DOZENT_ARCHIV') ? 'dozent' : 'admin');

$perm->check($check_perm);

include ('lib/seminar_open.php'); // initialise Stud.IP-Session
require_once 'lib/admin_search.inc.php';

// -- here you have to put initialisations for the current page

if (get_config('RESOURCES_ENABLE')) {
    include_once ($GLOBALS['RELATIVE_PATH_RESOURCES'] . "/lib/DeleteResourcesUser.class.php");
}

if ($perm->have_perm('admin')) {
    Navigation::activateItem('/admin/course/archive');
} else {
    Navigation::activateItem('/course/admin/main/archive');
}

PageLayout::setTitle(_("Archivieren von Veranstaltungen"));

//Change header_line if open object
if ($SessSemName[1]) {
    PageLayout::setTitle(getHeaderLine($SessSemName[1]) . " - " . PageLayout::getTitle());
}
// single delete (a Veranstaltung is open)
if ($SessSemName[1]) {
    $archiv_sem[] = "_id_" . $SessSemName[1];
    $archiv_sem[] = "on";
}
if(!is_array($archiv_sem)){
    $archiv_sem = Request::quotedArray('archiv_sem');
}
// Handlings....
// Kill current list and stuff

if (Request::option('new_session'))
    $_SESSION['archiv_assi_data'] = array();

// A list was sent
if (is_array($archiv_sem) && !Request::option('archive_kill') && !Request::option('inc') && !Request::option('dec') ) {
    $_SESSION['archiv_assi_data']['sems'] = array();
    $_SESSION['archiv_assi_data']['sem_check'] = array();
    $_SESSION['archiv_assi_data']['pos'] = 0;
    foreach($archiv_sem as $key => $val) {
        if ((substr($val, 0, 4) == "_id_") && (substr($$archiv_sem[$key + 1], 0, 4) != "_id_"))
                if ($archiv_sem[$key + 1] == "on") {
                    $_SESSION['archiv_assi_data']["sems"][] = array("id" => substr($val, 4, strlen($val)), "succesful_archived" => FALSE);
                    $_SESSION['archiv_assi_data']["sem_check"][substr($val, 4, strlen($val))] = TRUE;
                }
    }
}
// inc if we have lectures left in the upper
if (Request::option('inc'))
    if ($_SESSION['archiv_assi_data']["pos"] < sizeof($_SESSION['archiv_assi_data']["sems"])-1) {
        $i = 1;
        while ((!$_SESSION['archiv_assi_data']["sem_check"][$_SESSION['archiv_assi_data']["sems"][$_SESSION['archiv_assi_data']["pos"] + $i]["id"]]) && ($_SESSION['archiv_assi_data']["pos"] + $i < sizeof($_SESSION['archiv_assi_data']["sems"])-1))
        $i++;
        if ((sizeof($_SESSION['archiv_assi_data']["sem_check"]) > 1) && ($_SESSION['archiv_assi_data']["sem_check"][$_SESSION['archiv_assi_data']["sems"][$_SESSION['archiv_assi_data']["pos"] + $i]["id"]]))
            $_SESSION['archiv_assi_data']["pos"] = $_SESSION['archiv_assi_data']["pos"] + $i;
    }

// dec if we have lectures left in the lower
if (Request::option('dec'))
    if ($_SESSION['archiv_assi_data']["pos"] > 0) {
        $d = -1;
        while ((!$_SESSION['archiv_assi_data']["sem_check"][$_SESSION['archiv_assi_data']["sems"][$_SESSION['archiv_assi_data']["pos"] + $d]["id"]]) && ($_SESSION['archiv_assi_data']["pos"] + $d > 0))
        $d--;
        if ((sizeof($_SESSION['archiv_assi_data']["sem_check"]) > 1) && ($_SESSION['archiv_assi_data']["sem_check"][$_SESSION['archiv_assi_data']["sems"][$_SESSION['archiv_assi_data']["pos"] + $d]["id"]]))
            $_SESSION['archiv_assi_data']["pos"] = $_SESSION['archiv_assi_data']["pos"] + $d;
    }


if(LockRules::Check($_SESSION['archiv_assi_data']["sems"][$_SESSION['archiv_assi_data']["pos"]]["id"], 'seminar_archive')) {
        $lockdata = LockRules::getObjectRule($_SESSION['archiv_assi_data']["sems"][$_SESSION['archiv_assi_data']["pos"]]["id"]);
        if ($lockdata['description']){
            $details = formatLinks($lockdata['description']);
        } else {
            $details = _("Die Veranstaltung kann nicht archiviert werden.");
        }
        throw new AccessDeniedException($details);
}

// Delete (and archive) the lecture
if (Request::option('archive_kill')) {
    $run = TRUE;
    $s_id = $_SESSION['archiv_assi_data']["sems"][$_SESSION['archiv_assi_data']["pos"]]["id"];
    // # Do we have permission to do so?

    if (!$perm->have_perm($check_perm)) {
        $msg .= "error§" . _("Sie haben keine Berechtigung zum archivieren von Veranstaltungen.") . "§";
        $run = FALSE;
    }
    // Trotzdem nochmal nachsehen
    if (!$perm->have_studip_perm($check_perm , $s_id)) {
        $msg .= "error§" . _("Sie haben keine Berechtigung diese Veranstaltung zu archivieren.") . "§";
        $run = FALSE;
    }

    if ($run) {
        // Bevor es wirklich weg ist. kommt das Seminar doch noch schnell ins Archiv
        in_archiv($s_id);
        $sem = new Seminar($s_id);
        // Delete that Seminar.

        $sem->delete();

        $messages = $sem->getStackedMessages();
        unset($sem);

        // Successful archived, if we are here
        $msg .= "msg§" . sprintf(_("Die Veranstaltung %s wurde erfolgreich archiviert und aus der Liste der aktiven Veranstaltungen gel&ouml;scht. Sie steht nun im Archiv zur Verf&uuml;gung."), "<b>" . htmlReady(stripslashes($tmp_name)) . "</b>") . "§";

        // unset the checker, lecture is now killed!
        unset($_SESSION['archiv_assi_data']["sem_check"][$s_id]);

        // redirect non-admin users to overview page, since the course is gone now
        if (!$perm->have_perm('admin')) {
            $_SESSION['archive_message'] = $msg;
            header('Location: ' . URLHelper::getURL('my_archiv.php'));
            page_close();
            die();
        }

        // if there are lectures left....
        if (is_array($_SESSION['archiv_assi_data']["sem_check"])) {
            if ($_SESSION['archiv_assi_data']["pos"] < sizeof($_SESSION['archiv_assi_data']["sems"])-1) { // ...inc the counter if possible..
                $i = 1;
                while ((! $_SESSION['archiv_assi_data']["sem_check"][$_SESSION['archiv_assi_data']["sems"][$_SESSION['archiv_assi_data']["pos"] + $i]["id"]]) && ($_SESSION['archiv_assi_data']["pos"] + $i < sizeof($_SESSION['archiv_assi_data']["sems"])-1))
                $i++;
                $_SESSION['archiv_assi_data']["pos"] = $_SESSION['archiv_assi_data']["pos"] + $i;
            } else { // ...else dec the counter to find a unarchived lecture
                if ($_SESSION['archiv_assi_data']["pos"] > 0)
                    $d = -1;
                while ((!$_SESSION['archiv_assi_data']["sem_check"][$_SESSION['archiv_assi_data']["sems"][$_SESSION['archiv_assi_data']["pos"] + $d]["id"]]) && ($_SESSION['archiv_assi_data']["pos"] + $d > 0))
                $d--;
                $_SESSION['archiv_assi_data']["pos"] = $_SESSION['archiv_assi_data']["pos"] + $d;
            }
        }
    }
}

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php'); // Output of Stud.IP head
include 'lib/include/admin_search_form.inc.php';

// Outputs...
if (($_SESSION['archiv_assi_data']["sems"]) && (sizeof($_SESSION['archiv_assi_data']["sem_check"]) > 0)) {
    $query = "SELECT Name, Untertitel, status, Beschreibung, VeranstaltungsNummer,
                     duration_time, start_time, art, Institut_id
              FROM seminare
              WHERE Seminar_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($_SESSION['archiv_assi_data']['sems'][$_SESSION['archiv_assi_data']['pos']]['id']));
    $seminar = $statement->fetch(PDO::FETCH_ASSOC);

    $msg .= "info§<font color=\"black\">" . _("Sie sind im Begriff, die untenstehende  Veranstaltung zu archivieren. Dieser Schritt kann nicht r&uuml;ckg&auml;ngig gemacht werden!") . "§";
    // check is Veranstaltung running
    if ($seminar['duration_time'] == -1) {
        $msg .= "info§" . _("Das Archivieren k&ouml;nnte unter Umst&auml;nden nicht sinnvoll sein, da es sich um eine dauerhafte Veranstaltung handelt.") . "§";
    } elseif (time() < $seminar['start_time'] + $seminar['duration_time']) {
        $msg .= "info§" . _("Das Archivieren k&ouml;nnte unter Umst&auml;nden nicht sinnvoll sein, da das oder die Semester, in denen die Veranstaltung stattfindet, noch nicht verstrichen sind.") . "§";
    }
    if($ELEARNING_INTERFACE_ENABLE){
        $cms_types = ObjectConnections::GetConnectedSystems($_SESSION['archiv_assi_data']["sems"][$_SESSION['archiv_assi_data']["pos"]]["id"]);
        if(count($cms_types)){
            $msg .= "info§" . sprintf(_("Die Veranstaltung besitzt verknüpfte Inhalte in %s externen Systemen (%s). Diese verknüpften Inhalte werden durch die Archivierung gelöscht!"), count($cms_types), join(',',$cms_types)) . "§";
        }
    }
?>
<body>

<table width="100%" border=0 cellpadding=0 cellspacing=0>
    <? if($perm->have_perm('admin')) : ?>
    <tr>
        <td class="topic" colspan=2><b>&nbsp;
        <?
        echo $SEM_TYPE[$seminar['status']]["name"], ": ", htmlReady(substr($seminar['Name'], 0, 60));
        if (strlen($seminar['Name']) > 60)
            echo "... ";
        echo " -  " . _("Archivieren der Veranstaltung");
        ?></b>
        </td>
    </tr>
    <? endif ?>
    <tr>
        <td class="blank" colspan=2>
        <? if ($messages) : ?>
            <? foreach ($messages as $type => $message_data) : ?>
                <?= MessageBox::$type($message_data['title'], $message_data['details']) ?>
            <? endforeach ?>
        <? endif ?>
        <table class="zebra" align="center" width="99%" border=0 cellpadding=2 cellspacing=0>
            <?
            parse_msg($msg, "§", "blank", 3);
            ?>
            <tr>
                <td width="4%">&nbsp;</td>
                <td valign="top" colspan=3 valign="top" width="96%">
                <?
                    // Grunddaten des Seminars
                    printf ("<b>%s</b>", htmlReady($seminar['Name']));
                    // last activity
                    $last_activity = lastActivity($_SESSION['archiv_assi_data']["sems"][$_SESSION['archiv_assi_data']["pos"]]["id"]);
                    if ((time() - $last_activity) < (60 * 60 * 24 * 7 * 12))
                        $activity_warning = TRUE;
                    printf ("<br><font size=\"-1\" >" . _("letzte Ver&auml;nderung am:") . " %s%s%s </font>", ($activity_warning) ? "<font color=\"red\" >" : "", date("d.m.Y, G:i", $last_activity), ($activity_warning) ? "</font>" : "");
                    ?>
                </td>
            </tr>
            <? if ($seminar['Untertitel'] != "") {

                        ?>
            <tr>
                <td width="4%">&nbsp;
                </td>
                <td valign="top" colspan=2 valign="top" width="96%">
                <?
                // Grunddaten des Seminars
                printf ("<font size=-1><b>" . _("Untertitel:") . "</b></font><br><font size=-1>%s</font>", htmlReady($seminar['Untertitel']));
                ?>
                </td>
            </tr>
            <? }
                    ?>
            <tr>
                <td width="4%">&nbsp;</td>
                <td valign="top" width="48%">
                <?
                printf ("<font size=-1><b>" . _("Zeit:") . "</b></font><br><font size=-1>%s</font>", htmlReady(view_turnus($_SESSION['archiv_assi_data']['sems'][$_SESSION['archiv_assi_data']['pos']]['id'], FALSE)));
                ?>
                </td>
                <td valign="top" width="48%">
                <?
                printf ("<font size=-1><b>" . _("Semester:") . "</b></font><br><font size=-1>%s</font>", get_semester($_SESSION['archiv_assi_data']['sems'][$_SESSION['archiv_assi_data']['pos']]['id']));
                ?>
                </td>
            </tr>
            <tr>
                <td width="4%">&nbsp;</td>
                <td valign="top" width="48%">
                <?
                printf ("<font size=-1><b>" . _("Erster Termin:") . "</b></font><br><font size=-1>%s</font>", veranstaltung_beginn($_SESSION['archiv_assi_data']["sems"][$_SESSION['archiv_assi_data']["pos"]]["id"]));
                ?>
                </td>
                <td valign="top" width="48%">
                <?
                printf ("<font size=-1><b>" . _("Vorbesprechung:") . "</b></font><br><font size=-1>%s</font>", (vorbesprechung($_SESSION['archiv_assi_data']["sems"][$_SESSION['archiv_assi_data']["pos"]]["id"])) ? htmlReady(vorbesprechung($_SESSION['archiv_assi_data']["sems"][$_SESSION['archiv_assi_data']["pos"]]["id"])) : _("keine"));
                ?>
                </td>
            </tr>
            <tr>
                <td width="4%">&nbsp;</td>
                <td width="48%" valign="top">
                <?
                $sem = Seminar::getInstance($_SESSION['archiv_assi_data']['sems'][$_SESSION['archiv_assi_data']['pos']]['id']);
                printf ("<font size=-1><b>" . _("Veranstaltungsort:") . "</b></font><br><font size=-1>%s</font>", 
                    htmlReady($sem->getDatesTemplate('dates/seminar_export_location')));
                ?>
                </td>
                <td width="48%" valign="top">
                <?
                if ($seminar['VeranstaltungsNummer'])
                    printf ("<font size=-1><b>" . _("Veranstaltungsnummer:") . "</b></font><br><font size=-1>%s</font>", htmlReady($seminar['VeranstaltungsNummer']));
                else
                    print "&nbsp; ";
                ?>
                </td>
            </tr>
            <tr>
                <td width="4%">&nbsp;</td>
                <td width="48%" valign="top">
                <?
                // wer macht den Dozenten?
                $query = "SELECT {$_fullname_sql['full']} AS fullname, username
                          FROM seminar_user
                          LEFT JOIN auth_user_md5 USING (user_id)
                          LEFT JOIN user_info USING (user_id)
                          WHERE Seminar_id = ? AND status = ? ORDER BY position, Nachname";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array(
                    $_SESSION['archiv_assi_data']['sems'][$_SESSION['archiv_assi_data']['pos']]['id'],
                    'dozent'
                ));
                $teachers = $statement->fetchAll(PDO::FETCH_ASSOC);
                $statement->closeCursor();

                printf("<font size=-1><b>" . get_title_for_status('dozent', count($teachers), $seminar['status']) . "</b></font><br>");

                if (count($teachers) === 1) {
                    $teacher = reset($teachers);
                    printf('<font size=-1><a href="%s">%s</a></font>',
                           URLHelper::getLink('about.php?username=' . $teacher['username']),
                           htmlReady($teacher['fullname']));
                } else {
                    echo '<ul style="margin:0;">';
                    foreach ($teachers as $teacher) {
                        echo '<li>';
                        printf('<font size=-1><a href="%s">%s</a></font>',
                               URLHelper::getLink('about.php?username=' . $teacher['username']),
                               htmlReady($teacher['fullname']));
                        echo '</li>';
                    }
                    echo '</ul>';
                }

                ?>
                </td>
                <td width="48%" valign="top">
                <?
                // und wer ist Tutor?
                $statement->execute(array(
                    $_SESSION['archiv_assi_data']['sems'][$_SESSION['archiv_assi_data']['pos']]['id'],
                    'tutor'
                ));
                $tutors = $statement->fetchAll(PDO::FETCH_ASSOC);
                $statement->closeCursor();

                printf("<font size=-1><b>" . get_title_for_status('tutor', count($tutors), $seminar['status']) . "</b></font><br>");
                if (count($tutors) === 0) {
                    echo '<font size=-1>' . _('keine') . '</font>';
                } else if (count($tutors) === 1) {
                    $tutor = reset($tutors);
                    printf('<font size=-1><a href="%s">%s</a></font>',
                           URLHelper::getLink('about.php?username=' . $tutor['username']),
                           htmlReady($tutor['fullname']));
                } else {
                    echo '<ul style="margin:0;">';
                    foreach ($tutors as $tutor) {
                        echo '<li>';
                        printf('<font size=-1><a href="%s">%s</a></font>',
                               URLHelper::getLink('about.php?username=' . $tutor['username']),
                               htmlReady($tutor['fullname']));
                        echo '</li>';
                    }
                    echo '</ul>';
                }

                ?>
                </td>
            </tr>
            <tr>
                <td width="4%">&nbsp;</td>
                <td width="48%" valign="top">
                <?
                printf ("<font size=-1><b>" . _("Veranstaltungstyp:") . "</b></font><br><font size=-1>%s in der Kategorie %s</font>", $SEM_TYPE[$seminar['status']]["name"], $SEM_CLASS[$SEM_TYPE[$seminar['status']]["class"]]["name"]);
                ?>
                </td>
                <td width="48%" valign="top">
                <?
                if ($seminar['art'])
                    printf ("<font size=-1><b>" . _("Art/Form:") . "</b></font><br><font size=-1>%s</font>", htmlReady($seminar['art']));
                else
                    print "&nbsp; ";
                ?>
                </td>
            </tr>
            <? if ($seminar['Beschreibung'] != "") {

                        ?>
            <tr>
                <td width="4%">&nbsp;</td>
                <td colspan="2" width="96%" valign="top">
                <?
                printf ("<font size=-1><b>" . _("Kommentar/Beschreibung:") . "</b></font><br><font size=-1>%s</font>", htmlReady($seminar['Beschreibung'], TRUE, TRUE));
                ?>
                </td>
            </tr>
            <?
            }
            ?>
            <tr>
                <td width="4%">&nbsp;</td>
                <td width="48%" valign="top">
                <?
                $query = "SELECT Name FROM Institute WHERE Institut_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($seminar['Institut_id']));
                $institute_name = $statement->fetchColumn();

                if ($institute_name) {
                    printf("<font size=-1><b>" . _('Heimat-Einrichtung:') . "</b></font><br><font size=-1><a href=\"%s\">%s</a></font>",
                           URLHelper::getLink('institut_main.php?auswahl=' . $seminar['Institut_id']),
                           htmlReady($institute_name));
                }

                ?>
                </td>
                <td width="48%" valign="top">
                <?
                $query = "SELECT Name, Institut_id
                          FROM Institute
                          LEFT JOIN seminar_inst USING (institut_id)
                          WHERE seminar_id = ? AND Institute.Institut_id != ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array(
                    $_SESSION['archiv_assi_data']['sems'][$_SESSION['archiv_assi_data']['pos']]['id'],
                    $seminar['Institut_id']
                ));
                $institutes = $statement->fetchAll(PDO::FETCH_ASSOC);

                if (count($institutes) === 1) {
                    print("<font size=-1><b>" . _("Beteiligte Einrichtung:") . "</b></font><br>");
                    $institute = reset($institutes);
                    printf('<font size=-1><a href="%s">%s</a></font><br>',
                           URLHelper::getLink('institut_main.php?auswahl=' . $institute['Institut_id']),
                           htmlReady($institute['Name']));
                } else if (count($institutes) >= 2) {
                    print("<font size=-1><b>" . _("Beteiligte Einrichtungen:") . "</b></font><br>");

                    echo '<ul style="margin:0;">';
                    foreach ($institutes as $institute) {
                        echo '<li>';
                        printf('<font size=-1><a href="%s">%s</a></font><br>',
                               URLHelper::getLink('institut_main.php?auswahl=' . $institute['Institut_id']),
                               htmlReady($institute['Name']));
                        echo '</li>';
                    }
                    echo '</ul>';
                }
                ?>
                </td>
            </tr>
            <tr>
                <td width="4%">&nbsp;</td>
                <td colspan="2" width="96%" valign="top" align="center">
                <?
                // can we dec?
                if ($_SESSION['archiv_assi_data']["pos"] > 0) {
                    $d = -1;
                    while ((!$_SESSION['archiv_assi_data']["sem_check"][$_SESSION['archiv_assi_data']["sems"][$_SESSION['archiv_assi_data']["pos"] + $d]["id"]]) && ($_SESSION['archiv_assi_data']["pos"] + $d > 0))
                    $d--;
                    if ((sizeof($_SESSION['archiv_assi_data']["sem_check"]) > 1) && ($_SESSION['archiv_assi_data']["sem_check"][$_SESSION['archiv_assi_data']["sems"][$_SESSION['archiv_assi_data']["pos"] + $d]["id"]]))
                        $inc_possible = TRUE;
                }
                if ($inc_possible) {
                    echo LinkButton::create(_('<< Vorherige'), URLHelper::getURL("?dec=TRUE"));
                }
                echo LinkButton::create(_('Archivieren'), URLHelper::getURL("?archive_kill=TRUE"));
                if (!$_SESSION['links_admin_data']["sem_id"]) {

                    if ($perm->have_perm('admin')) {
                        $cancel_url = URLHelper::getURL((($SessSemName[1])
                            ? 'dispatch.php/course/basicdata/view/'. $SessSemName[1] .'?list=TRUE'
                            : '?list=TRUE&new_session=TRUE'));
                    } else {
                        $cancel_url = URLHelper::getURL('dispatch.php/course/management');
                    }

                    echo LinkButton::createCancel(_('Abbrechen'), $cancel_url);
                }
                // can we inc?
                if ($_SESSION['archiv_assi_data']["pos"] < sizeof($_SESSION['archiv_assi_data']["sems"])-1) {
                    $i = 1;
                    while ((!$_SESSION['archiv_assi_data']["sem_check"][$_SESSION['archiv_assi_data']["sems"][$_SESSION['archiv_assi_data']["pos"] + $i]["id"]]) && ($_SESSION['archiv_assi_data']["pos"] + $i < sizeof($_SESSION['archiv_assi_data']["sems"])-1))
                    $i++;
                    if ((sizeof($_SESSION['archiv_assi_data']["sem_check"]) > 1) && ($_SESSION['archiv_assi_data']["sem_check"][$_SESSION['archiv_assi_data']["sems"][$_SESSION['archiv_assi_data']["pos"] + $i]["id"]]))
                        $dec_possible = TRUE;
                }
                if ($dec_possible) {
                    echo LinkButton::create(_('Nächster >>'), URLHelper::getURL("?inc=TRUE"));
                }
                if (sizeof($_SESSION['archiv_assi_data']["sems"]) > 1)
                    printf ("<br><font size=\"-1\">" . _("noch <b>%s</b> von <b>%s</b> Veranstaltungen zum Archivieren ausgew&auml;hlt.") . "</font>", sizeof($_SESSION['archiv_assi_data']["sem_check"]), sizeof($_SESSION['archiv_assi_data']["sems"]));
                ?>
                </td>
            </tr>
        </table>
        <br>
    </td>
    </tr>
    </table>

    <?
    } elseif (($_SESSION['archiv_assi_data']["sems"]) && (sizeof($_SESSION['archiv_assi_data']["sem_check"]) == 0)) {
    ?>

    <table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td class="topic" colspan=2> <b><?=_("Die Veranstaltung wurde archiviert.")?></b>
        </td>
    </tr>
    <tr>
        <td class="blank" colspan=2>
        <b><? parse_msg($msg . "info§" . _("Sie haben alle ausgew&auml;hlten Veranstaltungen archiviert!")); ?></b>
        </td>
    </tr>
    </table>
    <?
    if ($_SESSION['links_admin_data']["sem_id"] == $_SESSION['archiv_assi_data']["sems"][$_SESSION['archiv_assi_data']["pos"]]["id"])
        reset_all_data();
    } elseif (!$list) {
    if ($_SESSION['links_admin_data']["sem_id"] == $_SESSION['archiv_assi_data']["sems"][$_SESSION['archiv_assi_data']["pos"]]["id"])
        reset_all_data();
    ?>
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td class="topic" colspan=2><b><?=_("Keine Veranstaltung zum Archivieren gew&auml;hlt")?></b>
        </td>
    </tr>
    <tr>
        <td class="blank" colspan=2><b>
        <?
        if (!$_SESSION['links_admin_data']["sem_id"])
            parse_msg("info§" . _("Sie haben keine Veranstaltung zum Archivieren gew&auml;hlt."));
        ?></b>
        </td>
    </tr>
    </table>
<?php
    }
    include ('lib/include/html_end.inc.php');
    page_close();
?>
