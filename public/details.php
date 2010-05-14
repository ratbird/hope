<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO

/**
 * details.php
 *
 * Detail-Uebersicht und Statistik fuer ein Seminar
 *
 * PHP version 5
 *
 * @author      Cornelis Kater <ckater@gwdg.de>
 * @author      Stefan Suchi <suchi@gmx.de>
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @copyright   2000-2009 Stud.IP
 * @license     http://www.gnu.org/licenses/gpl.html GPL Licence 3
 * @package     studip_core
 * @access      public
 */



require '../lib/bootstrap.php';

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Default_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$auth->login_if($again && ($auth->auth["uid"] == "nobody"));

require_once 'lib/functions.php';
require_once 'lib/msg.inc.php';
require_once 'lib/dates.inc.php'; //Funktionen zum Anzeigen der Terminstruktur
require_once 'config.inc.php';
require_once 'lib/visual.inc.php'; // wir brauchen htmlReady
require_once 'lib/admission.inc.php';
require_once 'lib/functions.php';
require_once 'lib/classes/StudipSemTree.class.php';
require_once 'lib/classes/DataFieldEntry.class.php';
require_once 'lib/classes/StudipStmInstance.class.php';
require_once 'lib/classes/StudipAdmissionGroup.class.php';
require_once 'lib/classes/StudipStudyArea.class.php';
require_once 'lib/classes/UserDomain.php';
require_once "lib/classes/CourseAvatar.class.php";

include 'lib/seminar_open.php'; // initialise Stud.IP-Session

//wenn kein Seminar gesetzt und auch kein externer Aufruf raus....
if (!isset($sem_id)) {
    checkObject();
}

$HELP_KEYWORD="Basis.InVeranstaltungDetails";
if ($SessSemName[1] && !isset($sem_id)) $header_object_id = $SessSemName[1];
else $header_object_id = $sem_id;
$CURRENT_PAGE = getHeaderLine($header_object_id). " - " . _("Details");

if (($SessSemName[1] != "") && (!isset($sem_id) || $SessSemName[1] == $sem_id)) {
    Navigation::activateItem('/course/main/details');
}

ob_start();
// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');  // Output of Stud.IP head

//Inits
$cssSw=new cssClassSwitcher;
$db=new DB_Seminar;
$db2=new DB_Seminar;
$db3=new DB_Seminar;
$db4=new DB_Seminar;
$info_msg = $abo_msg = $delete_msg = $back_msg = '';
$send_from_search = (int)isset($send_from_search);
if (!preg_match('/^('.preg_quote($CANONICAL_RELATIVE_PATH_STUDIP,'/').')?([a-zA-Z0-9_-]+\.php)([a-zA-Z0-9_?&=-]*)$/', $send_from_search_page)) $send_from_search_page = '';

//wenn Seminar gesetzt und kein externer Aufruf uebernahme der SessionVariable
if (($SessSemName[1] != "") && (!isset($sem_id) || $SessSemName[1] == $sem_id)) {
    $sem_id = $SessSemName[1];
}

$sem = new Seminar($sem_id);
#$DataFields = new DataFields($sem_id);

// redirect, if sem is a studygroup
if ( $SEM_CLASS[$SEM_TYPE[$sem->status]["class"]]["studygroup_mode"] ) {
    ob_end_clean();
    if ($perm->have_studip_perm('autor', $sem_id)) {    // participants may see seminar_main
        $link = UrlHelper::getUrl('seminar_main.php?auswahl='. $sem_id);
    } else {   // all other get a special details-page
        $link = UrlHelper::getUrl('dispatch.php/course/studygroup/details/'. $sem_id, array('send_from_search_page' => $send_from_search_page));
    }
    header('Location: '. $link);
    die;
}

//load all the data
$db2->query("SELECT * FROM seminare WHERE Seminar_id = '$sem_id'");
$db2->next_record();

$same_domain = true;
$user_domains = UserDomain::getUserDomainsForUser($auth->auth['uid']);
$seminar_domains = UserDomain::getUserDomainsForSeminar($sem_id);

if (count($user_domains) > 0) {
    $same_domain = count(array_intersect($seminar_domains, $user_domains)) > 0;
}

// nachfragen, ob das Seminar abonniert werden soll
if ($sem_id) {
    if ($perm->have_studip_perm("admin",$sem_id)) {
        $skip_verify=TRUE;
    } elseif ($perm->have_perm("user") && !$perm->have_perm("admin") && $same_domain) { //Add lecture only if logged in
        $db->query("SELECT status FROM seminar_user WHERE user_id ='$user->id' AND Seminar_id = '$sem_id'");
        $db->next_record();
        if (($db2->f("admission_starttime") > time()) && (($db2->f("admission_endtime_sem") == "-1"))) {
            $abo_msg = sprintf ("</A>"._("Tragen Sie sich hier ab %s um %s ein.")."<A>",date("d.m. Y",$db2->f("admission_starttime")),date("G:i",$db2->f("admission_starttime")));
        } elseif (($db2->f("admission_starttime") > time()) && (($db2->f("admission_endtime_sem") != "-1"))) {
            $abo_msg = sprintf ("</A>"._("Tragen Sie sich hier von %s bis %s ein.")."<A>",date("d.m. Y, G:i",$db2->f("admission_starttime")),date("d.m.Y, G:i",$db2->f("admission_endtime_sem")));
        } elseif (($db2->f("admission_endtime_sem") < time()) && ($db2->f("admission_endtime_sem") != -1)) {
            if (!$db->f("status") == "user") $info_msg = _("Eintragen nicht mehr möglich, der Anmeldezeitraum ist abgelaufen");
        } elseif ($db2->f("admission_type") == 3) {
                        $info_msg = _("Eintragen nicht m&ouml;glich, diese Veranstaltung ist gesperrt.");
        } else {
            if (!$db->num_rows()) {
                $db->query("SELECT status FROM admission_seminar_user WHERE user_id ='$user->id' AND seminar_id = '$sem_id'");
                if (!$db->num_rows()) $abo_msg = _("Tragen Sie sich hier f&uuml;r die Veranstaltung ein");
            } else {
                if ($db->f("status") == "user") $abo_msg = _("Schreibrechte aktivieren");
            }
        }

        $db->query("SELECT * FROM seminar_user_schedule WHERE range_id = '$sem_id' AND user_id = '".$auth->auth['uid']."'");
        $sem_user_schedule = $db->num_rows();

        $db->query("SELECT * FROM seminar_user WHERE Seminar_id = '$sem_id' AND user_id = '".$auth->auth['uid']."'");
        $sem_user = $db->num_rows();

        if (!$sem_user && !$sem_user_schedule) {
            $plan_msg = "<a href=\"".URLHelper::getLink("mein_stundenplan.php?cmd=add_entry&semid=$sem_id")."\">"._("Nur im Stundenplan vormerken")."</a>";
        }

    }

    if ($perm->have_studip_perm("user",$sem_id) && !$perm->have_studip_perm("tutor",$sem_id)) {
        if ($db2->f("admission_binding"))
            $info_msg = sprintf(_("Das Austragen aus der Veranstaltung ist nicht mehr m&ouml;glich, da das Abonnement bindend ist.<br>Bitte wenden Sie sich an den Leiter (%s) der Veranstaltung!"), get_title_for_status('dozent', 1), $db2->f('status'));
        else
            $delete_msg = _("Tragen Sie sich hier aus der Veranstaltung aus");
    }
}

if ($send_from_search)
    $back_msg.=_("Zur&uuml;ck zur letzten Auswahl");


 //calculate a "quarter" year, to avoid showing dates that are older than a quarter year (only for irregular dates)
$quarter_year = 60 * 60 * 24 * 90;


//In dieser Datei nehmen wir die Art direkt, nicht aus Session, da die Datei auch ausserhalb von Seminaren aufgerufen wird
if ($SEM_TYPE[$db2->f("status")]["name"] == $SEM_TYPE_MISC_NAME) //Typ fuer Sonstiges
    $art = _("Veranstaltung");
else
    $art = $SEM_TYPE[$db2->f("status")]["name"];


    ?>

    <?
    if ($SessSemName[1] != $sem_id) {
    ?>
<div class="topic">&nbsp;</div>
    <?
    }
    if ($msg)
    {
        echo "<table>";
        parse_msg($msg);
        echo "</table>";
    }
    ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td class="blank">
        <table align="center" width="100%" border="0" cellpadding="2" cellspacing="0">
        <tr>
            <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="1%">&nbsp; <img src="<?= $GLOBALS['ASSETS_URL'] ?>images/blank.gif" width="25" height="10" border="0">
            </td>
            <td class="<? echo $cssSw->getClass() ?>" valign="top" colspan="2" valign="top">
                <?
                //Titel und Untertitel der Veranstaltung
                printf ("<b>%s</b><br> ",htmlReady($db2->f("Name")));
                printf ("<font size=-1>%s</font>",htmlReady($db2->f("Untertitel")));
                ?>
            </td>
            <td class="blank" width="270" rowspan="7" valign="top" align="right">

            <? // Infobox

            $user_id = $auth->auth["uid"];
            $db3->query("SELECT status FROM seminar_user WHERE Seminar_id = '$sem_id' AND user_id = '$user_id'");
            if ($db3->next_record() ){
                $mein_status = $db3->f("status");
            } else {
                unset ($mein_status);
            }
            //Status als Wartender ermitteln
            $db3->query("SELECT status FROM admission_seminar_user WHERE seminar_id = '$sem_id' AND user_id = '$user_id'");
            if ($db3->next_record() ){
                $admission_status = $db3->f("status");
            } else {
                unset ($admission_status);
            }

            if (($mein_status) || ($admission_status)) {
                $picture_tmp = "haken.gif";
            } else {
                $picture_tmp = "x2.gif";
            }

            if (($mein_status) || ($admission_status)) {
                if ($mein_status) {
                    $tmp_text=_("Sie sind als TeilnehmerIn der Veranstaltung eingetragen");
                    $tmp_text .= $num_text;
                } elseif ($admission_status) {
                    if ($admission_status == "accepted") {
                        $tmp_text = sprintf(_("Sie wurden f&uuml;r diese Veranstaltung vorl&auml;ufig akzeptiert.<br>Lesen Sie den Hinweistext!"));
                        $tmp_text .= $num_text;
                    } else {
                        $tmp_text = sprintf (_("Sie sind in die %s der Veranstaltung eingetragen."), ($admission_status=="claiming")    ? _("Anmeldeliste") : _("Warteliste"));
                        $tmp_text .= $num_text;
                    }
                }
            } elseif ($perm->have_perm("admin")) {
                $tmp_text=_("Sie sind AdministratorIn und k&ouml;nnen deshalb die Veranstaltung nicht abonnieren.");
            } elseif (!$same_domain) {
                $tmp_text=_("Sie sind nicht in einer für die Veranstaltung zugelassenenen Nutzerdomäne.");
            } else {
                $tmp_text=_("Sie sind nicht als TeilnehmerIn der Veranstaltung eingetragen.");
            }
            if ((!$mein_status) && (!$admission_status)) {
                $tmp_text = "<font color = red>".$tmp_text."<font>";
            }


    $db4->query("SELECT admission_prelim FROM seminare WHERE Seminar_id = '$sem_id'");
    $db4->next_record();

    $infobox = array    (
        array   ("kategorie"    => _("Pers&ouml;nlicher Status:"),
            "eintrag" => array  (
                array ( "icon" => $picture_tmp,
                    "text"  => $tmp_text
                )
            )
        ),
        array   ("kategorie" => _("Berechtigungen:"),
            "eintrag" => array  (
                array   (   "icon" => "blank.gif",
                    "text"  => _("Lesen:") . "&nbsp; ".get_ampel_read($mein_status, $admission_status, $db2->f("Lesezugriff"), FALSE, $db2->f("admission_starttime"), $db2->f("admission_endtime_sem"), $db2->f("admission_prelim"))
                ),
                array   (   "icon" => "blank.gif",
                    "text"  => _("Schreiben:") . "&nbsp; ".get_ampel_write($mein_status, $admission_status, $db2->f("Schreibzugriff"), FALSE, $db2->f("admission_starttime"), $db2->f("admission_endtime_sem"), $db2->f("admission_prelim"))
                )
            )
        )
    );

if ($abo_msg || $back_msg || $delete_msg || $info_msg || $plan_msg || $mein_status || $perm->have_studip_perm("admin",$sem_id) ) {
    $infobox[2]["kategorie"] = _("Aktionen:");
    if (($abo_msg) && (!$skip_verify)) {
        $infobox[2]["eintrag"][] = array (  "icon" => "link_intern.gif" ,
                                    "text"  => "<a href=\"".URLHelper::getLink("sem_verify.php?id=".$sem_id."&send_from_search=$send_from_search&send_from_search_page=$send_from_search_page")."\">".$abo_msg. "</a>"
                                );
    } elseif ($sem_id != $SessSemName[1] && ($perm->have_studip_perm("admin",$sem_id) || ($mein_status && !$admission_status)) ) {
        $infobox[2]["eintrag"][] = array (  "icon" => "link_intern.gif" ,
                                    "text"  => "<a href=\"".URLHelper::getLink("seminar_main.php?auswahl=".$sem_id."")."\">"._("direkt zur Veranstaltung"). "</a>"
                                );
    }
    if ($delete_msg) {
        $infobox[2]["eintrag"][] = array (  "icon" => "link_intern.gif" ,
                                    "text"  => "<a href=\"".URLHelper::getLink("meine_seminare.php?auswahl=".$sem_id."&cmd=suppose_to_kill")."\">".$delete_msg."</a>"
                                );
    }
    if ($back_msg) {
        $infobox[2]["eintrag"][] = array (  "icon" => "link_intern.gif" ,
                                    "text"  => "<a href=\"".URLHelper::getLink($send_from_search_page)."\">".$back_msg. "</a>"
                                );
    }
    if ($info_msg) {
        $infobox[2]["eintrag"][] = array (  "icon" => "ausruf_small.gif" ,
                                    "text"  => $info_msg
                                );
    }
    if ($plan_msg) {
        $infobox[2]["eintrag"][] = array (  "icon" => "link_intern.gif" ,
                                    "text"  => $plan_msg
                                );
    }

}


if ($db2->f("admission_binding")) {
    $infobox[count($infobox)]["kategorie"] = _("Information:");
    $infobox[count($infobox)-1]["eintrag"][] = array (  "icon" => "info.gif" ,
                                "text"  => _("Das Abonnement dieser Veranstaltung ist <u>bindend</u>!")
                            );
}

// print the info_box
echo $template_factory->render(
    'infobox/infobox_avatar',
    array(
        'content' => $infobox,
        'picture' => CourseAvatar::getAvatar($sem_id)->getUrl(Avatar::NORMAL)
));
// ende Infobox

?>
                </td>
            </tr>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="1%">&nbsp;</td>
                <td class="<? echo $cssSw->getClass() ?>" valign="top">
                <?
                 printf ("<font size=-1><b>" . _("Zeit:") . "</b></font><br><font size=-1>%s</font>",htmlReady(view_turnus($sem_id, FALSE, FALSE, (time() - $quarter_year))));
                    if (($mein_status || $perm->have_studip_perm("admin",$sem_id)) && $modules['schedule']) {
                        echo '<br><font size="-1"><br>';
                        echo sprintf(_("Details zu allen Terminen im %sAblaufplan%s"), '<a href="'.URLHelper::getLink('seminar_main.php?auswahl='.$sem_id.'&redirect_to=dates.php').'">', '</a>');
                        echo '</font><br>';
                    }
                ?>
                </td>
                <td class="<? echo $cssSw->getClass() ?>" valign="top">
                <?
                printf ("<font size=-1><b>" . _("Semester:") . "</b></font><br><font size=-1>%s</font>",get_semester($sem_id));
                ?>
                </td>
            </tr>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="1%">&nbsp;</td>
                <td class="<? echo $cssSw->getClass() ?>" valign="top">
                    <font size="-1">
                    <?

              $next_date = $sem->getNextDate();
                    if ($next_date) {
                        echo '<b>'._("Nächster Termin").':</b><br>';
                        echo $next_date;
                    } else if ($first_date = $sem->getFirstDate()) {
                        echo '<b>'._("Erster Termin").':</b><br>';
                        echo $first_date;
                    } else {
                        echo '<b>'._("Erster Termin").':</b><br>';
                        echo _("Die Zeiten der Veranstaltung stehen nicht fest.");
                    }

                ?>
                    </font>
                </td>
                <td class="<? echo $cssSw->getClass() ?>" valign="top">
                <?
                printf ("<font size=-1><b>" . _("Vorbesprechung:") . "</b></font><br><font size=-1>%s</font>", (vorbesprechung($sem_id)) ? vorbesprechung($sem_id) : _("keine"));
                ?>
                </td>
            </tr>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="1%">&nbsp;</td>
                <td class="<? echo $cssSw->getClass() ?>" valign="top">
                <?
                    $room = getRoom($sem_id, true, (time() - $quarter_year));
                    if (!$room) {
                        if (!($room = $db2->f('Ort'))) {
                            $room = _("nicht angegeben");
                        }
                    }

                    echo '<font size="-1"><b>' . _("Veranstaltungsort:") . '</b><br>' . $room . '</font>';
                ?>
                </td>
                <td class="<? echo $cssSw->getClass() ?>" valign="top">
                <?
                if ($db2->f("VeranstaltungsNummer"))
                    printf ("<font size=-1><b>" . _("Veranstaltungsnummer:") . "</b></font><br><font size=-1>%s</font>",$db2->f("VeranstaltungsNummer"));
                else
                    print "&nbsp; ";
                ?>
                </td>
            </tr>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="1%">&nbsp;</td>
                <td class="<? echo $cssSw->getClass() ?>" valign="top">
                <?
                //wer macht den Dozenten?
                $db->query ("SELECT " . $_fullname_sql['full'] . " AS fullname, seminar_user.user_id, username, status FROM seminar_user LEFT JOIN auth_user_md5 USING (user_id) LEFT JOIN user_info USING(user_id) WHERE seminar_user.Seminar_id = '$sem_id' AND status = 'dozent' ORDER BY position, Nachname");
                if ($db->num_rows() > 0)
                    printf("<font size=-1><b>%s:</b></font><br>", get_title_for_status('dozent', $db->num_rows(), $db2->f('status')));
                else
                    print "&nbsp; ";
                while ($db->next_record()) {
                    if ($db->num_rows() > 1)
                        print "<li>";
                    printf( "<font size=\"-1\"><a href=\"%s\">%s</a></font>", URLHelper::getLink("about.php?username=".$db->f("username")), htmlReady($db->f("fullname")) );
                    if ($db->num_rows() > 1)
                        print "</li>";
                }
                ?>
                </td>
                <td class="<? echo $cssSw->getClass() ?>" valign="top">
                <?
                //und wer ist Tutor?
                $db->query ("SELECT seminar_user.user_id, " . $_fullname_sql['full'] . " AS fullname, username, status FROM seminar_user LEFT JOIN auth_user_md5 USING (user_id) LEFT JOIN user_info USING(user_id) WHERE seminar_user.Seminar_id = '$sem_id' AND status = 'tutor' ORDER BY position, Nachname");
                if ($db->num_rows() > 0)
                    printf ("<font size=-1><b>%s:</b></font><br>", get_title_for_status('tutor', $db->num_rows(), $db2->f('status')));
                else
                    print "&nbsp; ";
                while ($db->next_record()) {
                    if ($db->num_rows() > 1)
                        print "<li>";
                    printf( "<font size=\"-1\"><a href=\"%s\">%s</a></font>", URLHelper::getLink("about.php?username=".$db->f("username")), htmlReady($db->f("fullname")) );
                    if ($db->num_rows() > 1)
                        print "</li>";
                }
                ?>
                </td>
            </tr>
        </table>
        <table width="100%" border="0" cellpadding="2" cellspacing="0">
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="%">&nbsp;
                <img src="<?= $GLOBALS['ASSETS_URL'] ?>images/blank.gif" width="25" height="10" border="0">
                </td>
                <td class="<? echo $cssSw->getClass() ?>" colspan=2 width="51%" valign="top">
                <?
                printf ("<font size=-1><b>" . _("Veranstaltungstyp:") . "</b></font><br><font size=-1>" . _("%s in der Kategorie %s") . "</font>",$SEM_TYPE[$db2->f("status")]["name"], $SEM_CLASS[$SEM_TYPE[$db2->f("status")]["class"]]["name"]);
                ?>
                </td>
                <td class="<? echo $cssSw->getClass() ?>" colspan=2 width="48%" valign="top">
                <?
                if ($db2->f("art"))
                    printf ("<font size=-1><b>" . _("Art/Form:") . "</b></font><br><font size=-1>%s</font>",htmlReady($db2->f("art")));
                else
                    print "&nbsp; ";
                ?>
                </td>
            </tr>
            <? if ($db2->f("Beschreibung") !="") {
            ?>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="1%">&nbsp;</td>
                <td class="<? echo $cssSw->getClass() ?>" colspan=4 width="99%" valign="top">
                <?
                printf ("<font size=-1><b>" . _("Kommentar/Beschreibung:") . "</b></font><br><font size=-1>%s</font>",FixLinks(htmlReady($db2->f("Beschreibung"), TRUE, TRUE)));
                ?>
                </td>
            </tr>
            <? }
            if ($db2->f("teilnehmer") !="") {
            ?>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="1%">&nbsp;</td>
                <td class="<? echo $cssSw->getClass() ?>" colspan=4 width="99%" valign="top">
                <?
                printf ("<font size=-1><b>" . _("Teilnehmende:") . "</b></font><br><font size=-1>%s</font>",htmlReady($db2->f("teilnehmer"), TRUE, TRUE));
                ?>
                </td>
            </tr>
            <? }
            if ($db2->f("vorrausetzungen") !="") {
            ?>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="1%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" colspan=4 width="99%" valign="top">
                <?
                printf ("<font size=-1><b>" . _("Voraussetzungen:") . "</b></font><br><font size=-1>%s</font>",htmlReady($db2->f("vorrausetzungen"), TRUE, TRUE));
                ?>
                </td>
            </tr>
            <? }
            if ($db2->f("lernorga") !="") {
            ?>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="1%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" colspan=4 width="99%" valign="top">
                <?
                printf ("<font size=-1><b>" . _("Lernorganisation:") . "</b></font><br><font size=-1>%s</font>",htmlReady($db2->f("lernorga"), TRUE, TRUE));
                ?>
                </td>
            </tr>
            <? }
            if ($db2->f("leistungsnachweis") !="") {
            ?>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="1%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" colspan=4 width="99%" valign="top">
                <?
                printf ("<font size=-1><b>" . _("Leistungsnachweis:") . "</b></font><br><font size=-1>%s</font>",htmlReady($db2->f("leistungsnachweis"), TRUE, TRUE));
                ?>
            </td>
            </tr>
            <? }
                //add the free adminstrable datafields
                $localEntries = DataFieldEntry::getDataFieldEntries($sem_id);

                foreach ($localEntries as $entry) {
                if ($entry->structure->accessAllowed($perm)) {
                    if ($entry->getValue()) {
                 ?>
                 <tr>
                     <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="1%">&nbsp;
                     </td>
                     <td class="<? echo $cssSw->getClass() ?>" colspan=4 width="99%" valign="top">
                     <?
                     printf ("<font size=-1><b>" . htmlReady($entry->getName()) . ":</b></font><br><font size=-1>%s</font>", $entry->getDisplayValue());
                     ?>
                     </td>
                 </tr>
                 <?
                     }
                }
            }
            if ($db2->f("Sonstiges") !="") {
            ?>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="1%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" colspan=4 width="99%" valign="top">
                <?
                printf ("<font size=-1><b>" . _("Sonstiges:") . "</b></font><br><font size=-1>%s</font>",FixLinks(htmlReady($db2->f("Sonstiges"), TRUE, TRUE)));
                ?>
                </td>
            </tr>
            <? }
            if ($db2->f("ects")) {
            ?>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="1%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" colspan=4 width="99%" valign="top">
                <?
                printf ("<font size=-1><b>" . _("ECTS-Kreditpunkte:") . "</b></font><br><font size=-1>%s</font>",htmlReady($db2->f("ects"), TRUE, TRUE));
                ?>
                </td>
            </tr>
            <? }
            if (get_config('STM_ENABLE')){
                $stms = StudipStmInstance::GetStmInstancesBySeminar($sem_id);
                if (count($stms)){
                    $stm_out = array();
                    foreach($stms as $stm_id => $stm){
                        if ($stm['complete']){
                            $stm_out[] = '<a href="'.URLHelper::getLink('stm_details.php?stm_instance_id='.$stm_id).'"><img src="'.$GLOBALS['ASSETS_URL'].'images/link_intern.gif" border="0">&nbsp;&nbsp;' . ($stm['id_number'] ? htmlReady($stm['id_number']).': ' : '') . htmlReady($stm['title']) . '</a>';
                        }
                    }
                    ?>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="1%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" colspan=4 width="99%" valign="top">
                <?
                printf ("<font size=-1><b>" . _("Studienmodule:") . "</b></font><br><font size=-1>%s</font>",
                        join("<br>\n", $stm_out));
                ?>
                </td>
            </tr>
            <?
                }
            }
            $studienmodule = null;
            if ($studienmodulmanagement = PluginEngine::getPlugin('StudienmodulManagement')){
                $studienmodule = array_filter(StudipStudyArea::getStudyAreasForCourse($sem_id), create_function('$a', 'return $a->isModule();'));
                if (count($studienmodule)){
                    $semester_id = SemesterData::GetSemesterIdByDate($db2->f("start_time"));
                    foreach($studienmodule as $module){
                        $nav = $studienmodulmanagement->getModuleInfoNavigation($module->getId(), $semester_id);
                        $icon_html = '';
                        if($icon = $nav->getImage()){
                            $icon_html = '<img ';
                            foreach ($icon as $key => $value) $icon_html .= sprintf('%s="%s" ', $key, htmlReady($value));
                            $icon_html .= '>';
                        }
                        $stm_out[$module->getId()] = sprintf('<a href="%s">%s %s</a>',
                            URLHelper::getLink($nav->getUrl()), htmlReady($nav->getTitle()), $icon_html);
                    }
                    ?>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="1%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" colspan=4 width="99%" valign="top">
                <?
                printf ("<font size=-1><b>" . _("Studienmodule:") . "</b></font><br><font size=-1><ul><li>%s</li></ul></font>",
                        join("</li>\n<li>", $stm_out));
                ?>
                </td>
            </tr>
            <?
                }
            }
            // Anzeige der Bereiche
            if ($SEM_CLASS[$SEM_TYPE[$db2->f("status")]["class"]]["bereiche"]) {
                $sem_path = (array)get_sem_tree_path($sem_id);
                if(is_array($studienmodule)){
                    $sem_path = array_diff_key($sem_path, $studienmodule);
                }
            ?>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="1%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" colspan=4 width="99%" valign="top">
                <?
                if (is_array($sem_path) && count($sem_path)){
                    if (count($sem_path) ==1)
                    printf ("<font size=-1><b>" . _("Studienbereich:") . "</b></font><br>");
                    else
                    printf ("<font size=-1><b>" . _("Studienbereiche:") . "</b></font><br>");
                    foreach ($sem_path as $sem_tree_id => $path_name) {
                        if (count($sem_path) >= 2)
                        print "<li>";
                        printf ("<font size=\"-1\"><a href=\"%s\">%s</a></font>", URLHelper::getLink("show_bereich.php?level=sbb&id=".$sem_tree_id),
                        htmlReady($path_name));
                        if (count($sem_path) >= 2)
                        print "</li>";
                    }
                }
                ?>&nbsp;
                </td>
            </tr>
            <? } ?>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="1%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" colspan=2 width="51%" valign="top">
                <?
                $db3->query("SELECT Name, url, Institut_id FROM Institute WHERE Institut_id = '".$db2->f("Institut_id")."' ");
                $db3->next_record();
                if ($db3->num_rows()) {
                printf("<font size=-1><b>" . _("Heimat-Einrichtung:") . "</b></font><br><font size=-1><a href=\"%s\">%s</a></font>", URLHelper::getLink("institut_main.php?auswahl=".$db3->f("Institut_id")), htmlReady($db3->f("Name")));
                }
                ?>
                </td>
                <td class="<? echo $cssSw->getClass() ?>" colspan=2 width="48%" valign="top">
                <?
                $db3->query("SELECT Name, url, Institute.Institut_id FROM Institute LEFT JOIN seminar_inst USING (institut_id) WHERE seminar_id = '$sem_id' AND Institute.institut_id != '".$db2->f("Institut_id")."'");
                if ($db3->num_rows() ==1)
                    printf ("<font size=-1><b>" . _("beteiligte Einrichtung:") . "</b></font><br>");
                elseif ($db3->num_rows() >=2)
                    printf ("<font size=-1><b>" . _("beteiligte Einrichtungen:") . "</b></font><br>");
                else
                    print "&nbsp; ";
                while ($db3->next_record()) {
                    if ($db3->num_rows() >= 2)
                        print "<li>";
                    printf("<font size=-1><a href=\"%s\">%s</a></font><br>", URLHelper::getLink("institut_main.php?auswahl=".$db3->f("Institut_id")), htmlReady($db3->f("Name")));
                    if ($db3->num_rows() > 2)
                        print "</li>";
                }
                ?>
                </td>
    </tr>
            <?
            if ($db2->f("admission_type") | ($db2->f("admission_prelim") == 1) | ($db2->f("admission_starttime") > time()) | ($db2->f("admission_endtime_sem") != -1)) {
            ?>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="1%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" colspan=2 width="51%" valign="top">
                <font size=-1><b><?=_("Anmeldeverfahren:")?></b></font><br>
                <?
    }
    if ($db2->f("admission_prelim") == 1 && $db2->f("admission_type") != 3) {
        echo "<font size=-1>";
        print(_("Die Auswahl der Teilnehmenden wird nach der Eintragung manuell vorgenommen."));
        echo "<br>";
        $db3->query("SELECT * FROM admission_seminar_user WHERE user_id='$user->id' AND seminar_id='$sem_id'");
        if ($db3->next_record()) {
            echo "<table width=\"100%\">";
            printf ("<tr><td width=\"%s\">&nbsp;</td><td><font size=-1>%s</font><br></td></tr></table>", "2%", formatReady($db2->f("admission_prelim_txt")));
        } else {
            if (!$perm->have_perm("admin")) {
                print("<p>"._("Wenn Sie an der Veranstaltung teilnehmen wollen, klicken Sie auf \"Tragen Sie sich hier ein\". Sie erhalten dann nähere Hinweise und können sich immer noch gegen eine Teilnahme entscheiden.")."</p>");
            } else {
                print("<p>"._("NutzerInnen, die sich für diese Veranstaltung eintragen möchten, erhalten nähere Hinweise und können sich dann noch gegen eine Teilnahme entscheiden.")."</p>");
            }
        }
        echo "</font>";
    }
    if ($db2->f("admission_starttime") > time()) {
        echo "<font size=-1>";
        printf ("<br>"._("Das Teilnahmeverfahren für diese Veranstaltung startet am %s um %s."),date("d.m.Y",$db2->f("admission_starttime")), date("G:i",$db2->f("admission_starttime")));
        echo "</font>";
    }
    if (($db2->f("admission_endtime_sem") > time()) && ($db2->f("admission_endtime_sem") != -1)) {
        echo "<font size=-1>";
        printf (" "._("Das Teilnahmeverfahren für diese Veranstaltung endet am %s um %s."),date("d.m.Y",$db2->f("admission_endtime_sem")), date("G:i",$db2->f("admission_endtime_sem")));
        echo "<br>";
        echo "</font>";
    }
    if (($db2->f("admission_endtime_sem") <= time()) && ($db2->f("admission_endtime_sem") != -1)) {
        echo "<font size=-1>";
        printf (_("Das Teilnahmeverfahren für diese Veranstaltung wurde am %s um %s beendet."),date("d.m.Y",$db2->f("admission_endtime_sem")), date("G:i",$db2->f("admission_endtime_sem")));
        echo "<br>";
        echo "</font>";
    }
    if ($db2->f("admission_type") == 3) {
                echo '<font size="-1" color="red">'. _("Diese Veranstaltung ist gesperrt, sie k&ouml;nnen sich nicht selbst eintragen!");
                echo "<td class=\"".$cssSw->getClass()."\" colspan=2 width=\"48%\" valign=\"top\"><td>";
        } elseif ($db2->f("admission_type")) {
        if ($db2->f("admission_selection_take_place") == 1) {
            if ($db2->f("admission_type") == 1) {
                printf ("<font size=-1>" . _("Die Auswahl der Teilnehmenden wurde nach dem Losverfahren am %s Uhr festgelegt.") . "</font>", date("d.m.Y, G:i", $db2->f("admission_endtime")));
                if (!$db2->f('admission_disable_waitlist') && ($db2->f("admission_endtime_sem") > time()) || ($db2->f("admission_endtime_sem") == -1)) {
                    echo "<font size=-1>" . _("Weitere Interessierte k&ouml;nnen per Warteliste einen Platz bekommen.") . "</font>";
                }
                echo "<br>";
            } else {
                if ($db2->f("admission_prelim") == 1) {
                    printf ("<font size=-1>" . _("Die vorläufige Auswahl der Teilnehmenden erfolgte in der Reihenfolge der Anmeldung.")."</font>");
                } else {
                    printf ("<font size=-1>" . _("Die Auswahl der Teilnehmenden erfolgte in der Reihenfolge der Anmeldung.")."</font>");
                }
                if($db2->f("admission_enable_quota")) printf("<font size=-1>" .  _("Die Kontingentierung wurde am %s aufgehoben.") . "</font>", date("d.m.Y, G:i", $db2->f("admission_endtime")));
                if (!$db2->f('admission_disable_waitlist') && ($db2->f("admission_endtime_sem") > time() || $db2->f("admission_endtime_sem") == -1)) {
                    echo "<font size=-1>" . _("Weitere Pl&auml;tze k&ouml;nnen noch &uuml;ber Wartelisten vergeben werden.") . "</font>";
                }
                echo "<br>";
            }
        } else {
            if ($db2->f("admission_type") == 1)
                printf ("<font size=-1>" . _("Die Auswahl der Teilnehmenden erfolgt nach dem Losverfahren am %s Uhr.") . "</font><br>", date("d.m.Y, G:i", $db2->f("admission_endtime")));
            else {
                if ($db2->f("admission_prelim") == 1) {
                    printf ("<font size=-1>" . _("Die vorläufige Auswahl der Teilnehmenden erfolgt in der Reihenfolge der Anmeldung."));
                } else {
                    printf ("<font size=-1>" . _("Die Auswahl der Teilnehmenden erfolgt in der Reihenfolge der Anmeldung."));
                }
                if ($db2->f("admission_enable_quota")) {
                    if ($db2->f("admission_endtime") < time()) {
                        printf ( _("Die Kontingentierung wurde am %s aufgehoben.") . "<br>", date("d.m.Y, G:i", $db2->f("admission_endtime")));
                    } else {
                        printf (_("Die Kontingentierung wird am %s aufgehoben.") . "<br>", date("d.m.Y, G:i", $db2->f("admission_endtime")));
                    }
                }
            }
        }
        //check, if seminar is grouped
        $group_obj = StudipAdmissionGroup::GetAdmissionGroupBySeminarId($sem_id);
        if (is_object($group_obj)) {
                ?>
                <div style="margin-top:5px;">
                <font size="-1">
                    <?=_("Veranstaltungsgruppe:")?>&nbsp;<?=htmlReady($group_obj->getValue('name'))?>
                    <ol>
                    <?foreach($group_obj->getMemberIds() as $m_id){
                        if (!$group_obj->members[$m_id]->isVisible()) continue; // hide invisible courses
                        $target = $perm->have_studip_perm("autor", $m_id) ? 'seminar_main.php?auswahl=' : 'details.php?sem_id=';
                        $target .= $m_id;
                        ?>
                        <li><a href="<?= URLHelper::getLink($target) ?>">
                        <?=htmlReady($group_obj->members[$m_id]->getName())?>
                        </a>
                        &nbsp;
                        (<?=htmlReady($group_obj->members[$m_id]->getFormattedTurnus(true))?>)
                        </li>
                    <?}?>
                    </ol>
                </font>
                </div>
            <?}?>
            </td>
            <td class="<? echo $cssSw->getClass() ?>" colspan=2 width="48%" valign="top">
            <?
                $all_cont_user = false;
                $admission_sem = Seminar::GetInstance($sem_id);
                $free_admission = $admission_sem->getFreeAdmissionSeats();
                if($free_admission !== false){
                ?>
                <div style="margin-top:5px;">
                <font size="-1">
                    <b><?=sprintf(_("Zugelassene Studiengänge (%s freie Plätze):"), $free_admission)?></b>
                    <ul>
                    <?foreach($admission_sem->admission_studiengang as $studiengang){
                        ?>
                        <li><?=htmlReady($studiengang['name'])?>
                        &nbsp;
                        (<?=($admission_sem->isAdmissionQuotaEnabled() ? sprintf(_("%s freie Plätze") . ' / ', $studiengang['num_total']-$studiengang['num_occupied']) : '') . sprintf(_("%s belegt"), $studiengang['num_occupied'])?>)
                        </li>
                    <?
                    $all_cont_user += $studiengang['num_occupied'];
                    }?>
                    </ul>
                </font>
                </div>
            <?}?>
            </td>
        </tr>
        <?
        } elseif (($db2->f("admission_starttime") > time()) || ($db2->f("admission_prelim") == 1) || ($db2->f("admission_endtime_sem") != -1)) {
            echo "<td class=\"".$cssSw->getClass()."\" colspan=2 width=\"48%\" valign=\"top\"><td>";
        }
        ?>
        <? if (count($seminar_domains)): ?>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="1%">&nbsp;</td>
                <td class="<?= $cssSw->getClass() ?>" colspan="4" valign="top">
                <font size="-1"><b><?= _("zugelassenene Nutzerdomänen:") ?></b></font><br>
                <? foreach ($seminar_domains as $domain): ?>
                    <font size="-1"><?= htmlReady($domain->getName()) ?></font><br>
                <? endforeach ?>
                </td>
            </tr>
        <? endif ?>
        <tr>
            <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="1%">&nbsp;</td>
            <td class="<? echo $cssSw->getClass() ?>" width="24%" valign="top">
            <?
                //Statistikfunktionen
                $db3->query("SELECT COUNT(Seminar_id) AS anzahl, COUNT(IF(status='dozent',Seminar_id,NULL)) AS anz_dozent
                        , COUNT(IF(status='tutor',Seminar_id,NULL)) AS anz_tutor, COUNT(IF(status='autor',Seminar_id,NULL)) AS anz_autor
                        , COUNT(IF(status='user',Seminar_id,NULL)) AS anz_user FROM seminar_user
                        WHERE Seminar_id = '$sem_id' GROUP BY Seminar_id");
                $db3->next_record();
                $db4->query("SELECT count(*) as anzahl FROM admission_seminar_user WHERE seminar_id = '$sem_id' AND status = 'accepted'");
                $db4->next_record();
                $count = 0;
                if ($db3->f("anzahl")) $count += $db3->f("anzahl");
                if ($db4->f("anzahl")) $count += $db4->f("anzahl");
                printf("<font size=-1><b>" . _("Anzahl der Teilnehmenden:") . "&nbsp;</b></font><font size=-1>%s </font>", ($count!=0) ? $count : _("keine"));
                printf("<br><font size=-1><b>%s:&nbsp;</b></font><font size=-1>%s </font>", get_title_for_status('dozent', $db3->f("anz_dozent"), $db2->f('status')), $db3->f("anz_dozent") ? $db3->f("anz_dozent") : _("keine"));
                printf("<br><font size=-1><b>%s:&nbsp;</b></font><font size=-1>%s </font>", get_title_for_status('tutor', $db3->f("anz_tutor"), $db2->f('status')), $db3->f("anz_tutor") ? $db3->f("anz_tutor") : _("keine"));
                printf("<br><font size=-1><b>" . _("Sonstige") . ":&nbsp;</b></font><font size=-1>%s </font>", (($db3->f("anz_autor")+ $db3->f("anz_user") + $db4->f("anzahl")) ? $db3->f("anz_autor")+$db3->f("anz_user") +$db4->f("anzahl") : _("keine")));
            ?>
            </td>
            <td class="<? echo $cssSw->getClass() ?>" width="25%" valign="top">
            <?
            if ($db2->f("admission_turnout")){
                    printf ("<font size=-1><b>" . _("%s Teilnehmerzahl:") . "&nbsp;</b></font><font size=-1>%s </font>", ($db2->f("admission_type")) ? _("max.") : _("erw."), $db2->f("admission_turnout"));
                    if (isset($all_cont_user) && $all_cont_user !== false){
                        printf ("<br><font size=-1><b>" . _("Freie Kontingentpl&auml;tze:") . "&nbsp;</b></font><font size=-1>%s </font>",$db2->f("admission_turnout") - $all_cont_user );
                        if (!$db2->f('admission_disable_waitlist') && ($db2->f("admission_turnout") - $all_cont_user) == 0){
                            $db3->query("SELECT COUNT(*) AS wartende FROM admission_seminar_user WHERE seminar_id='$sem_id' AND status !='accepted'");
                            $db3->next_record();
                            printf ("<br><font size=-1><b>" . _("Wartelisteneintr&auml;ge:") . "&nbsp;</b></font><font size=-1>%s </font>",$db3->f("wartende"));
                        }
                    }
            }
            ?>
            </td>
            <td class="<? echo $cssSw->getClass() ?>" width="25%" valign="top">
            <?
                $db3->query("SELECT count(*) as anzahl FROM px_topics WHERE Seminar_id = '$sem_id'");
                $db3->next_record();
                printf ("<font size=-1><b>" . _("Postings:") . "&nbsp;</b></font><font size=-1>%s </font>", ($db3->f("anzahl")) ? $db3->f("anzahl") : _("keine"));
            ?>
            </td>
            <td class="<? echo $cssSw->getClass() ?>" width="25%" valign="top">
            <?
                $db3->query("SELECT count(*) as anzahl FROM dokumente WHERE Seminar_id = '$sem_id'");
                $db3->next_record();
                printf ("<font size=-1><b>" . _("Dokumente:") . "&nbsp;</b></font><font size=-1>%s </font>", ($db3->f("anzahl")) ? $db3->f("anzahl") : _("keine"));
            ?>
            </td>
            </tr>
        </table>
        </td>
    </tr>
</table>
<?php
    include ('lib/include/html_end.inc.php');
    page_close();
?>
