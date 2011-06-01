<?
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
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


require '../lib/bootstrap.php';

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

if ($RESOURCES_ENABLE) {
    include_once ($RELATIVE_PATH_RESOURCES . "/lib/DeleteResourcesUser.class.php");
}
// # Get a database connection
$db = new DB_Seminar;
$db2 = new DB_Seminar;
$db3 = new DB_Seminar;
$db4 = new DB_Seminar;

$sess->register("archiv_assi_data");
$cssSw = new cssClassSwitcher;

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
// Handlings....
// Kill current list and stuff

if ($new_session)
    $archiv_assi_data = array();

// A list was sent
if (is_array($archiv_sem)) {
    $archiv_assi_data['sems'] = array();
    $archiv_assi_data['sem_check'] = array();
    $archiv_assi_data['pos'] = 0;
    foreach($archiv_sem as $key => $val) {
        if ((substr($val, 0, 4) == "_id_") && (substr($$archiv_sem[$key + 1], 0, 4) != "_id_"))
                if ($archiv_sem[$key + 1] == "on") {
                    $archiv_assi_data["sems"][] = array("id" => substr($val, 4, strlen($val)), "succesful_archived" => FALSE);
                    $archiv_assi_data["sem_check"][substr($val, 4, strlen($val))] = TRUE;
                }
    }
}
// inc if we have lectures left in the upper
if ($inc)
    if ($archiv_assi_data["pos"] < sizeof($archiv_assi_data["sems"])-1) {
        $i = 1;
        while ((!$archiv_assi_data["sem_check"][$archiv_assi_data["sems"][$archiv_assi_data["pos"] + $i]["id"]]) && ($archiv_assi_data["pos"] + $i < sizeof($archiv_assi_data["sems"])-1))
        $i++;
        if ((sizeof($archiv_assi_data["sem_check"]) > 1) && ($archiv_assi_data["sem_check"][$archiv_assi_data["sems"][$archiv_assi_data["pos"] + $i]["id"]]))
            $archiv_assi_data["pos"] = $archiv_assi_data["pos"] + $i;
    }

// dec if we have lectures left in the lower
if ($dec)
    if ($archiv_assi_data["pos"] > 0) {
        $d = -1;
        while ((!$archiv_assi_data["sem_check"][$archiv_assi_data["sems"][$archiv_assi_data["pos"] + $d]["id"]]) && ($archiv_assi_data["pos"] + $d > 0))
        $d--;
        if ((sizeof($archiv_assi_data["sem_check"]) > 1) && ($archiv_assi_data["sem_check"][$archiv_assi_data["sems"][$archiv_assi_data["pos"] + $d]["id"]]))
            $archiv_assi_data["pos"] = $archiv_assi_data["pos"] + $d;
    }


if(LockRules::Check($archiv_assi_data["sems"][$archiv_assi_data["pos"]]["id"], 'seminar_archive')) {
        $lockdata = LockRules::getObjectRule($archiv_assi_data["sems"][$archiv_assi_data["pos"]]["id"]);
        if ($lockdata['description']){
            $details = fixlinks($lockdata['description']);
        } else {
            $details = _("Die Veranstaltung kann nicht archiviert werden.");
        }
        throw new AccessDeniedException($details);
}

// Delete (and archive) the lecture
if ($archive_kill) {
    $run = TRUE;
    $s_id = $archiv_assi_data["sems"][$archiv_assi_data["pos"]]["id"];
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
        $sem = Seminar::getInstance($s_id);
        // Delete that Seminar.

        $sem->delete();

        $messages = $sem->getStackedMessages();
        unset($sem);

        // Successful archived, if we are here
        $msg .= "msg§" . sprintf(_("Die Veranstaltung %s wurde erfolgreich archiviert und aus der Liste der aktiven Veranstaltungen gel&ouml;scht. Sie steht nun im Archiv zur Verf&uuml;gung."), "<b>" . htmlReady(stripslashes($tmp_name)) . "</b>") . "§";

        // unset the checker, lecture is now killed!
        unset($archiv_assi_data["sem_check"][$s_id]);

        // redirect non-admin users to overview page, since the course is gone now
        if (!$perm->have_perm('admin')) {
            $_SESSION['archive_message'] = $msg;
            header('Location: ' . URLHelper::getURL('my_archiv.php'));
            page_close();
            die();
        }

        // if there are lectures left....
        if (is_array($archiv_assi_data["sem_check"])) {
            if ($archiv_assi_data["pos"] < sizeof($archiv_assi_data["sems"])-1) { // ...inc the counter if possible..
                $i = 1;
                while ((! $archiv_assi_data["sem_check"][$archiv_assi_data["sems"][$archiv_assi_data["pos"] + $i]["id"]]) && ($archiv_assi_data["pos"] + $i < sizeof($archiv_assi_data["sems"])-1))
                $i++;
                $archiv_assi_data["pos"] = $archiv_assi_data["pos"] + $i;
            } else { // ...else dec the counter to find a unarchived lecture
                if ($archiv_assi_data["pos"] > 0)
                    $d = -1;
                while ((!$archiv_assi_data["sem_check"][$archiv_assi_data["sems"][$archiv_assi_data["pos"] + $d]["id"]]) && ($archiv_assi_data["pos"] + $d > 0))
                $d--;
                $archiv_assi_data["pos"] = $archiv_assi_data["pos"] + $d;
            }
        }
    }
}

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php'); // Output of Stud.IP head
include 'lib/include/admin_search_form.inc.php';

// Outputs...
if (($archiv_assi_data["sems"]) && (sizeof($archiv_assi_data["sem_check"]) > 0)) {
    $db->query("SELECT * FROM seminare WHERE Seminar_id = '" . $archiv_assi_data["sems"][$archiv_assi_data["pos"]]["id"] . "' ");
    $db->next_record();
    $msg .= "info§<font color=\"red\">" . _("Sie sind im Begriff, die untenstehende  Veranstaltung zu archivieren. Dieser Schritt kann nicht r&uuml;ckg&auml;ngig gemacht werden!") . "§";
    // check is Veranstaltung running
    if ($db->f("duration_time") == -1) {
        $msg .= "info§" . _("Das Archivieren k&ouml;nnte unter Umst&auml;nden nicht sinnvoll sein, da es sich um eine dauerhafte Veranstaltung handelt.") . "§";
    } elseif (time() < ($db->f("start_time") + $db->f("duration_time"))) {
        $msg .= "info§" . _("Das Archivieren k&ouml;nnte unter Umst&auml;nden nicht sinnvoll sein, da das oder die Semester, in denen die Veranstaltung stattfindet, noch nicht verstrichen sind.") . "§";
    }
    if($ELEARNING_INTERFACE_ENABLE){
        $cms_types = ObjectConnections::GetConnectedSystems($archiv_assi_data["sems"][$archiv_assi_data["pos"]]["id"]);
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
        echo $SEM_TYPE[$db->f("status")]["name"], ": ", htmlReady(substr($db->f("Name"), 0, 60));
        if (strlen($db->f("Name")) > 60)
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
        <table align="center" width="99%" border=0 cellpadding=2 cellspacing=0>
            <?
            parse_msg($msg, "§", "blank", 3);
            ?>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" valign="top" colspan=3 valign="top" width="96%">
                <?
                    // Grunddaten des Seminars
                    printf ("<b>%s</b>", htmlReady($db->f("Name")));
                    // last activity
                    $last_activity = lastActivity($archiv_assi_data["sems"][$archiv_assi_data["pos"]]["id"]);
                    if ((time() - $last_activity) < (60 * 60 * 24 * 7 * 12))
                        $activity_warning = TRUE;
                    printf ("<br><font size=\"-1\" >" . _("letzte Ver&auml;nderung am:") . " %s%s%s </font>", ($activity_warning) ? "<font color=\"red\" >" : "", date("d.m.Y, G:i", $last_activity), ($activity_warning) ? "</font>" : "");
                    ?>
                </td>
            </tr>
            <? if ($db->f("Untertitel") != "") {

                        ?>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" valign="top" colspan=2 valign="top" width="96%">
                <?
                // Grunddaten des Seminars
                printf ("<font size=-1><b>" . _("Untertitel:") . "</b></font><br><font size=-1>%s</font>", htmlReady($db->f("Untertitel")));
                ?>
                </td>
            </tr>
            <? }
                    ?>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" valign="top" width="48%">
                <?
                printf ("<font size=-1><b>" . _("Zeit:") . "</b></font><br><font size=-1>%s</font>", htmlReady(view_turnus($archiv_assi_data["sems"][$archiv_assi_data["pos"]]["id"], FALSE)));
                ?>
                </td>
                <td class="<? echo $cssSw->getClass() ?>" valign="top" width="48%">
                <?
                printf ("<font size=-1><b>" . _("Semester:") . "</b></font><br><font size=-1>%s</font>", get_semester($archiv_assi_data["sems"][$archiv_assi_data["pos"]]["id"]));
                ?>
                </td>
            </tr>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" valign="top" width="48%">
                <?
                printf ("<font size=-1><b>" . _("Erster Termin:") . "</b></font><br><font size=-1>%s</font>", veranstaltung_beginn($archiv_assi_data["sems"][$archiv_assi_data["pos"]]["id"]));
                ?>
                </td>
                <td class="<? echo $cssSw->getClass() ?>" valign="top" width="48%">
                <?
                printf ("<font size=-1><b>" . _("Vorbesprechung:") . "</b></font><br><font size=-1>%s</font>", (vorbesprechung($archiv_assi_data["sems"][$archiv_assi_data["pos"]]["id"])) ? htmlReady(vorbesprechung($archiv_assi_data["sems"][$archiv_assi_data["pos"]]["id"])) : _("keine"));
                ?>
                </td>
            </tr>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="48%" valign="top">
                <?
                $sem = Seminar::getInstance($archiv_assi_data['sems'][$archiv_assi_data['pos']]['id']);
                printf ("<font size=-1><b>" . _("Veranstaltungsort:") . "</b></font><br><font size=-1>%s</font>", $sem->getDatesTemplate('dates/seminar_export_location'));
                ?>
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="48%" valign="top">
                <?
                if ($db->f("VeranstaltungsNummer"))
                    printf ("<font size=-1><b>" . _("Veranstaltungsnummer:") . "</b></font><br><font size=-1>%s</font>", htmlReady($db->f("VeranstaltungsNummer")));
                else
                    print "&nbsp; ";
                ?>
                </td>
            </tr>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="48%" valign="top">
                <?
                // wer macht den Dozenten?
                $db2->query ("SELECT " . $_fullname_sql['full'] . " AS fullname, seminar_user.user_id, username, status, position FROM seminar_user  LEFT JOIN auth_user_md5 USING (user_id) LEFT JOIN user_info USING (user_id) WHERE seminar_user.Seminar_id = '" . $archiv_assi_data["sems"][$archiv_assi_data["pos"]]["id"] . "' AND status = 'dozent' ORDER BY position, Nachname");
                printf("<font size=-1><b>" . get_title_for_status('dozent', $db2->num_rows(), $db->f('status')) . "</b></font><br>");
                while ($db2->next_record()) {
                    if ($db2->num_rows() > 1)
                        print "<li>";
                    printf("<font size=-1><a href=\"%s\">%s</a></font>", URLHelper::getLink("about.php?username=".$db2->f("username")), htmlReady($db2->f("fullname")));
                    if ($db2->num_rows() > 1)
                        print "</li>";
                }

                ?>
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="48%" valign="top">
                <?
                // und wer ist Tutor?
                $db2->query ("SELECT seminar_user.user_id, " . $_fullname_sql['full'] . " AS fullname, username, status, position FROM seminar_user  LEFT JOIN auth_user_md5 USING (user_id) LEFT JOIN user_info USING (user_id) WHERE seminar_user.Seminar_id = '" . $archiv_assi_data["sems"][$archiv_assi_data["pos"]]["id"] . "' AND status = 'tutor' ORDER BY position, Nachname");
                printf("<font size=-1><b>" . get_title_for_status('tutor', $db2->num_rows(), $db->f('status')) . "</b></font><br>");
                if ($db2->num_rows() == 0) {
                    print("<font size=-1>" . _("keine") . "</font>");
                }
                while ($db2->next_record()) {
                    if ($db2->num_rows() > 1)
                        print "<li>";
                    printf("<font size=-1><a href=\"%s\">%s</a></font>", URLHelper::getLink("about.php?username=".$db2->f("username")), htmlReady($db2->f("fullname")));
                    if ($db2->num_rows() > 1)
                        print "</li>";
                }

                ?>
                </td>
            </tr>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="48%" valign="top">
                <?
                printf ("<font size=-1><b>" . _("Veranstaltungstyp:") . "</b></font><br><font size=-1>%s in der Kategorie %s</font>", $SEM_TYPE[$db->f("status")]["name"], $SEM_CLASS[$SEM_TYPE[$db->f("status")]["class"]]["name"]);
                ?>
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="48%" valign="top">
                <?
                if ($db->f("art"))
                    printf ("<font size=-1><b>" . _("Art/Form:") . "</b></font><br><font size=-1>%s</font>", htmlReady($db->f("art")));
                else
                    print "&nbsp; ";
                ?>
                </td>
            </tr>
            <? if ($db->f("Beschreibung") != "") {

                        ?>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" colspan=2 width="96%" valign="top">
                <?
                printf ("<font size=-1><b>" . _("Kommentar/Beschreibung:") . "</b></font><br><font size=-1>%s</font>", htmlReady($db->f("Beschreibung"), TRUE, TRUE));
                ?>
                </td>
            </tr>
            <?
            }
            ?>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="48%" valign="top">
                <?
                $db2->query("SELECT Name, url, Institut_id FROM Institute WHERE Institut_id = '" . $db->f("Institut_id") . "' ");
                $db2->next_record();
                if ($db2->num_rows()) {
                    printf("<font size=-1><b>" . _("Heimat-Einrichtung:") . "</b></font><br><font size=-1><a href=\"%s\">%s</a></font>", URLHelper::getLink("institut_main.php?auswahl=".$db2->f("Institut_id")), htmlReady($db2->f("Name")));
                }

                ?>
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="48%" valign="top">
                <?
                $db2->query("SELECT Name, url, Institute.Institut_id FROM Institute LEFT JOIN seminar_inst USING (institut_id) WHERE seminar_id = '" . $archiv_assi_data["sems"][$archiv_assi_data["pos"]]["id"] . "' AND Institute.institut_id != '" . $db->f("Institut_id") . "'");
                if ($db2->num_rows() == 1)
                    printf ("<font size=-1><b>" . _("Beteiligte Einrichtung:") . "</b></font><br>");
                elseif ($db2->num_rows() >= 2)
                    printf ("<font size=-1><b>" . _("Beteiligte Einrichtungen:") . "</b></font><br>");
                else
                    print "&nbsp; ";
                while ($db2->next_record()) {
                    if ($db2->num_rows() >= 2)
                        print "<li>";
                    printf("<font size=-1><a href=\"%s\">%s</a></font><br>", URLHelper::getLink("institut_main.php?auswahl=".$db2->f("Institut_id")), htmlReady($db2->f("Name")));
                    if ($db2->num_rows() > 2)
                        print "</li>";
                }

                ?>
                </td>
            </tr>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" colspan=2 width="96%" valign="top" align="center">
                <?
                // can we dec?
                if ($archiv_assi_data["pos"] > 0) {
                    $d = -1;
                    while ((!$archiv_assi_data["sem_check"][$archiv_assi_data["sems"][$archiv_assi_data["pos"] + $d]["id"]]) && ($archiv_assi_data["pos"] + $d > 0))
                    $d--;
                    if ((sizeof($archiv_assi_data["sem_check"]) > 1) && ($archiv_assi_data["sem_check"][$archiv_assi_data["sems"][$archiv_assi_data["pos"] + $d]["id"]]))
                        $inc_possible = TRUE;
                }
                if ($inc_possible) {
                    printf("&nbsp;<a href=\"%s\">%s</a>", URLHelper::getLink("?dec=TRUE"), makeButton("vorherige", "img"));
                }
                printf("&nbsp;<a href=\"%s\">%s</a>", URLHelper::getLink("?archive_kill=TRUE"), makeButton("archivieren", "img"));
                if (!$links_admin_data["sem_id"]) {
                    echo '&nbsp;<a href="';

                    if ($perm->have_perm('admin')) {
                        echo URLHelper::getLink((($SessSemName[1])
                            ? 'dispatch.php/course/basicdata/view/'. $SessSemName[1] .'?list=TRUE'
                            : '?list=TRUE&new_session=TRUE'));
                    } else {
                        echo URLHelper::getLink('dispatch.php/course/management');
                    }

                    echo '">' . makeButton('abbrechen', 'img') . '</a>';
                }
                // can we inc?
                if ($archiv_assi_data["pos"] < sizeof($archiv_assi_data["sems"])-1) {
                    $i = 1;
                    while ((!$archiv_assi_data["sem_check"][$archiv_assi_data["sems"][$archiv_assi_data["pos"] + $i]["id"]]) && ($archiv_assi_data["pos"] + $i < sizeof($archiv_assi_data["sems"])-1))
                    $i++;
                    if ((sizeof($archiv_assi_data["sem_check"]) > 1) && ($archiv_assi_data["sem_check"][$archiv_assi_data["sems"][$archiv_assi_data["pos"] + $i]["id"]]))
                        $dec_possible = TRUE;
                }
                if ($dec_possible) {
                    printf("&nbsp;<a href=\"%s\">%s</a>", URLHelper::getLink("?inc=TRUE"), makeButton("naechster", "img"));
                }
                if (sizeof($archiv_assi_data["sems"]) > 1)
                    printf ("<br><font size=\"-1\">" . _("noch <b>%s</b> von <b>%s</b> Veranstaltungen zum Archivieren ausgew&auml;hlt.") . "</font>", sizeof($archiv_assi_data["sem_check"]), sizeof($archiv_assi_data["sems"]));
                ?>
                </td>
            </tr>
        </table>
        <br>
    </td>
    </tr>
    </table>

    <?
    } elseif (($archiv_assi_data["sems"]) && (sizeof($archiv_assi_data["sem_check"]) == 0)) {
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
    if ($links_admin_data["sem_id"] == $archiv_assi_data["sems"][$archiv_assi_data["pos"]]["id"])
        reset_all_data();
    } elseif (!$list) {
    if ($links_admin_data["sem_id"] == $archiv_assi_data["sems"][$archiv_assi_data["pos"]]["id"])
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
        if (!$links_admin_data["sem_id"])
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
