<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
 * details.php - Detail-Uebersicht und Statistik fuer ein Seminar
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Cornelis Kater <ckater@gwdg.de>
 * @author      Stefan Suchi <suchi@gmx.de>
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

require '../lib/bootstrap.php';

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Default_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$auth->login_if(Request::get('again') && ($auth->auth["uid"] == "nobody"));

require_once 'lib/functions.php';
require_once 'lib/msg.inc.php';
require_once 'lib/dates.inc.php'; //Funktionen zum Anzeigen der Terminstruktur
require_once 'config.inc.php';
require_once 'lib/visual.inc.php'; // wir brauchen htmlReady
require_once 'lib/admission.inc.php';
require_once 'lib/functions.php';
require_once 'lib/classes/StudipSemTree.class.php';
require_once 'lib/classes/DataFieldEntry.class.php';
require_once 'lib/deputies_functions.inc.php';
require_once 'lib/classes/admission/CourseSet.class.php';

include 'lib/seminar_open.php'; // initialise Stud.IP-Session

$sem_id = Request::option('sem_id');
//wenn kein Seminar gesetzt und auch kein externer Aufruf raus....
if (empty($sem_id)) {
    checkObject(); //wirft Exception, wenn $SessionSeminar leer ist
    $sem_id = $SessionSeminar;
}
//Inits
$info_msg = $abo_msg = $delete_msg = $back_msg = '';
$send_from_search = Request::get('send_from_search') !== null;
$send_from_search_page = Request::get('send_from_search_page');
if (!preg_match('/^('.preg_quote($CANONICAL_RELATIVE_PATH_STUDIP,'/').')?([a-zA-Z0-9_-]+\.php)([a-zA-Z0-9_?&=-]*)$/', $send_from_search_page)) $send_from_search_page = '';

$sem = new Seminar($sem_id);
$modules = new Modules();
$deputies_enabled = get_config('DEPUTIES_ENABLE');

if ($SessionSeminar != $sem_id && !$sem->isVisible() && !$perm->have_perm(get_config('SEM_VISIBILITY_PERM'))) {
    throw new AccessDeniedException(_('Diese Veranstaltung ist versteckt. Hier gibt es nichts zu sehen.'));
}
// redirect, if sem is a studygroup
if ( $sem->isStudygroup() ) {
    if ($perm->have_studip_perm('autor', $sem_id)) {    // participants may see seminar_main
        $link = URLHelper::getUrl('seminar_main.php?auswahl='. $sem_id);
    } else {   // all other get a special details-page
        $link = URLHelper::getUrl('dispatch.php/course/studygroup/details/'. $sem_id, array('send_from_search_page' => $send_from_search_page));
    }
    header('Location: '. $link);
    die;
}
PageLayout::setHelpKeyword("Basis.InVeranstaltungDetails");

PageLayout::setTitle($sem->getFullname(). " - " . _("Details"));

if ($SessionSeminar == $sem_id) {
    Navigation::activateItem('/course/main/details');
    // add skip link
    SkipLinks::addIndex(Navigation::getItem('/course/main/details')->getTitle(), 'main_content', 100);
}

PageLayout::addSqueezePackage('admission');
PageLayout::addSqueezePackage('enrolment');

ob_start();
// Start of Output
#include ('lib/include/html_head.inc.php'); // Output of html head
#include ('lib/include/header.php');  // Output of Stud.IP head
#include ('lib/include/deprecated_tabs_layout.php');

//load all the data
$query = "SELECT * FROM seminare WHERE Seminar_id = ?";
$statement = DBManager::get()->prepare($query);
$statement->execute(array($sem_id));
$seminar = $statement->fetch(PDO::FETCH_ASSOC);

$same_domain = true;
$user_domains = UserDomain::getUserDomainsForUser($auth->auth['uid']);
$seminar_domains = UserDomain::getUserDomainsForSeminar($sem_id);

if (count($user_domains) > 0) {
    $same_domain = count(array_intersect($seminar_domains, $user_domains)) > 0;
}


 //calculate a "quarter" year, to avoid showing dates that are older than a quarter year (only for irregular dates)
$quarter_year = 60 * 60 * 24 * 90;


//In dieser Datei nehmen wir die Art direkt, nicht aus Session, da die Datei auch ausserhalb von Seminaren aufgerufen wird
if ($SEM_TYPE[$seminar['status']]["name"] == $SEM_TYPE_MISC_NAME) //Typ fuer Sonstiges
    $art = _("Veranstaltung");
else
    $art = $SEM_TYPE[$seminar['status']]["name"];


    ?>

    <?
    if ($msg)
    {
        echo "<table>";
        parse_msg($msg);
        echo "</table>";
    }
    ?>
        <table class="default nohover" id="main_content" align="center" width="100%" border="0" cellpadding="2" cellspacing="0">
        <tr>
            <td width="1%">&nbsp; <img src="<?= $GLOBALS['ASSETS_URL'] ?>images/blank.gif" width="25" height="10" border="0">
            </td>
            <td valign="top" colspan="2" valign="top">
                <?
                //Titel und Untertitel der Veranstaltung
                printf ("<b>%s</b><br> ",htmlReady($seminar['Name']));
                printf ("<font size=-1>%s</font>",htmlReady($seminar['Untertitel']));
                ?>
            </td>
            </tr>
            <tr>
                <td width="1%">&nbsp;</td>
                <td valign="top">
                    <font size="-1">
                    <b><?= _("Zeit:") ?></b><br>
                    <? if (($mein_status || $perm->have_studip_perm("admin", $sem_id)) && $modules->getStatus('schedule', $sem_id)) :
                        $show_link = true;
                    endif ?>
                    <?= $sem->getDatesHTML(array('link_to_dates' => $show_link)) ?>
                    </font>
                </td>
                <td valign="top">
                <?
                printf ("<font size=-1><b>" . _("Semester:") . "</b></font><br><font size=-1>%s</font>",get_semester($sem_id));
                ?>
                </td>
            </tr>
            <tr>
                <td width="1%">&nbsp;</td>
                <td valign="top">
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
                <td valign="top">
                <?
                printf ("<font size=-1><b>" . _("Vorbesprechung:") . "</b></font><br><font size=-1>%s</font>", (vorbesprechung($sem_id)) ? vorbesprechung($sem_id) : _("keine"));
                ?>
                </td>
            </tr>
            <tr>
                <td width="1%">&nbsp;</td>
                <td valign="top">
                    <font size="-1">
                    <b><?= _("Veranstaltungsort:") ?></b>
                    <br>
                    <?= $sem->getDatesTemplate('dates/seminar_html_location', array('ort' => $seminar['Ort'])) ?>
                    </font>
                </td>
                <td valign="top">
                <?
                if ($seminar['VeranstaltungsNummer'])
                    printf ("<font size=-1><b>" . _("Veranstaltungsnummer:") . "</b></font><br><font size=-1>%s</font>",htmlReady($seminar['VeranstaltungsNummer']));
                else
                    print "&nbsp; ";
                ?>
                </td>
            </tr>
            <tr>
                <td width="1%">&nbsp;</td>
                <? foreach (array('dozent', 'tutor') as $status) { ?>
                    <td valign="top">
                    <font size="-1">
                    <?
                    $data = array();
                    if ($users = $sem->getMembers($status)) {
                        // fill data for template
                        foreach ($users as $entry) {
                            $data[] = array(
                                'name' => $entry['fullname'].($entry['label'] ? " (".$entry['label'].")" : ""),
                                'link' => 'dispatch.php/profile?username=' . $entry['username']
                            );
                        }

                        // set config-defined title for this status
                        $title = get_title_for_status($status, sizeof($data), $sem->getStatus()) . ':';

                        // show template
                        $template = $GLOBALS['template_factory']->open('details/list');
                        echo $template->render(compact('title', 'data'));
                    } ?>
                    </font>
                    </td>
                <? } ?>
            </tr>
        </table>
        <table class="default nohover">
            <tr>
                <td width="%">&nbsp;
                <img src="<?= $GLOBALS['ASSETS_URL'] ?>images/blank.gif" width="25" height="10" border="0">
                </td>
                <td colspan=2 width="51%" valign="top">
                <?
                printf ("<font size=-1><b>" . _("Veranstaltungstyp:") . "</b></font><br><font size=-1>" . _("%s in der Kategorie %s") . "</font>",$SEM_TYPE[$seminar['status']]["name"], $SEM_CLASS[$SEM_TYPE[$seminar['status']]["class"]]["name"]);
                ?>
                </td>
                <td colspan=2 width="48%" valign="top">
                <?
                if ($seminar['art'])
                    printf ("<font size=-1><b>" . _("Art/Form:") . "</b></font><br><font size=-1>%s</font>",htmlReady($seminar['art']));
                else
                    print "&nbsp; ";
                ?>
                </td>
            </tr>
            <? if ($seminar['Beschreibung'] !="") {
            ?>
            <tr>
                <td width="1%">&nbsp;</td>
                <td colspan=4 width="99%" valign="top">
                <?
                printf ("<font size=-1><b>" . _("Kommentar/Beschreibung:") . "</b></font><br><font size=-1>%s</font>",formatLinks($seminar['Beschreibung']));
                ?>
                </td>
            </tr>
            <? }
            if ($seminar['teilnehmer'] !="") {
            ?>
            <tr>
                <td width="1%">&nbsp;</td>
                <td colspan=4 width="99%" valign="top">
                <?
                printf ("<font size=-1><b>" . _("Teilnehmende:") . "</b></font><br><font size=-1>%s</font>",htmlReady($seminar['teilnehmer'], TRUE, TRUE));
                ?>
                </td>
            </tr>
            <? }
            if ($seminar['vorrausetzungen'] !="") {
            ?>
            <tr>
                <td width="1%">&nbsp;</td>
                <td colspan=4 width="99%" valign="top">
                <?
                printf ("<font size=-1><b>" . _("Voraussetzungen:") . "</b></font><br><font size=-1>%s</font>",htmlReady($seminar['vorrausetzungen'], TRUE, TRUE));
                ?>
                </td>
            </tr>
            <? }
            if ($seminar['lernorga'] !="") {
            ?>
            <tr>
                <td width="1%">&nbsp;</td>
                <td colspan=4 width="99%" valign="top">
                <?
                printf ("<font size=-1><b>" . _("Lernorganisation:") . "</b></font><br><font size=-1>%s</font>",htmlReady($seminar['lernorga'], TRUE, TRUE));
                ?>
                </td>
            </tr>
            <? }
            if ($seminar['leistungsnachweis'] !="") {
            ?>
            <tr>
                <td width="1%">&nbsp;</td>
                <td colspan=4 width="99%" valign="top">
                <?
                printf ("<font size=-1><b>" . _("Leistungsnachweis:") . "</b></font><br><font size=-1>%s</font>",htmlReady($seminar['leistungsnachweis'], TRUE, TRUE));
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
                     <td width="1%">&nbsp;</td>
                     <td colspan=4 width="99%" valign="top">
                     <?
                     printf ("<font size=-1><b>" . htmlReady($entry->getName()) . ":</b></font><br><font size=-1>%s</font>", $entry->getDisplayValue());
                     ?>
                     </td>
                 </tr>
                 <?
                     }
                }
            }
            if ($seminar['Sonstiges'] !="") {
            ?>
            <tr>
                <td width="1%">&nbsp;</td>
                <td colspan=4 width="99%" valign="top">
                <?
                printf ("<font size=-1><b>" . _("Sonstiges:") . "</b></font><br><font size=-1>%s</font>",formatLinks($seminar['Sonstiges']));
                ?>
                </td>
            </tr>
            <? }
            if ($seminar['ects']) {
            ?>
            <tr>
                <td width="1%">&nbsp;</td>
                <td valign="top">
                <?
                printf ("<font size=-1><b>" . _("ECTS-Punkte:") . "</b></font><br><font size=-1>%s</font>",htmlReady($seminar['ects'], TRUE, TRUE));
                ?>
                </td>
            </tr>
            <? }
            $studienmodule = null;
            if ($studienmodulmanagement = PluginEngine::getPlugin('StudienmodulManagement')){
                $studienmodule = StudipStudyArea::getStudyAreasForCourse($sem_id)->filter(function($area) { return $area->isModule(); });
                if (count($studienmodule)){
                    $semester_id = SemesterData::GetSemesterIdByDate($seminar['start_time']);
                    foreach($studienmodule as $module){
                        $nav = $studienmodulmanagement->getModuleInfoNavigation($module->getId(), $semester_id);
                        $title = $studienmodulmanagement->getModuleTitle($module->getId(), $semester_id);
                        $icon_html = '';
                        if($icon = $nav->getImage()){
                            $icon_html = '<img ';
                            foreach ($icon as $key => $value) $icon_html .= sprintf('%s="%s" ', $key, htmlReady($value));
                            $icon_html .= '>';
                        }
                        $stm_out[$module->getId()] = sprintf('<a class="module-info" href="%s">%s %s<span>%s</span></a>',
                            URLHelper::getLink($nav->getUrl()), htmlReady($title), $icon_html, htmlReady($nav->getTitle()));
                    }
                    ?>
            <tr>
                <td width="1%">&nbsp;</td>
                <td colspan=4 width="99%" valign="top">
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
            if ($SEM_CLASS[$SEM_TYPE[$seminar['status']]["class"]]["bereiche"]) {
                $sem_path = (array)get_sem_tree_path($sem_id);
                if(is_array($studienmodule)){
                    $sem_path = array_diff_key($sem_path, $studienmodule);
                }
            ?>
            <tr>
                <td width="1%">&nbsp;</td>
                <td colspan=4 width="99%" valign="top">
                    <font size="-1">
                <?
                // show the studyareas
                if (is_array($sem_path) && count($sem_path)){
                    // set pluralized title if necessary
                    $title = ngettext('Studienbereich:', 'Studienbereiche:', count($sem_path));

                    // fill data for template
                    $data = array();
                    foreach ($sem_path as $sem_tree_id => $path_name) {
                        $data[] = array(
                            'name' => $path_name,
                            'link' => 'show_bereich.php?level=sbb&id=' . $sem_tree_id
                        );
                    }

                    // show template
                    $template = $GLOBALS['template_factory']->open('details/list');
                    echo $template->render(compact('title', 'data'));
                }
                ?>
                &nbsp;
                    </font>
                </td>
            </tr>
            <? } ?>
            <tr>
                <td width="1%">&nbsp;</td>
                <td colspan=2 width="51%" valign="top">
                <?
                    $query = "SELECT Name, url FROM Institute WHERE Institut_id = ?";
                    $statement = DBManager::get()->prepare($query);
                    $statement->execute(array($seminar['Institut_id']));
                    $temp = $statement->fetch(PDO::FETCH_ASSOC);
                    if (!empty($temp)) {
                        printf("<font size=-1><b>" . _("Heimat-Einrichtung:") . "</b></font><br><font size=-1><a href=\"%s\">%s</a></font>", URLHelper::getLink("dispatch.php/institute/overview?auswahl=".$seminar['Institut_id']), htmlReady($temp['Name']));
                    }
                ?>
                </td>
                <td colspan=2 width="48%" valign="top">
                    <font size="-1">
                <?
                // fetch associated institutes and/or faculties
                $stmt = DBManager::get()->prepare('SELECT Name, url, Institute.Institut_id FROM Institute '
                    . 'LEFT JOIN seminar_inst USING (institut_id) '
                    . 'WHERE seminar_id = ? AND Institute.institut_id != ?');
                $stmt->execute(array($sem_id, $seminar['Institut_id']));

                if ($entries = $stmt->fetchAll(PDO::FETCH_ASSOC)) {
                    $data = array();
                    foreach ($entries as $entry) {
                        $data[] = array(
                            'name' => $entry['Name'],
                            'link' => 'dispatch.php/institute/overview?auswahl=' . $entry['Institut_id']
                        );
                    }

                    // get pluralized title if necessary
                    $title = ngettext('Beteiligte Einrichtung:', 'Beteiligte Einrichtungen:', sizeof($data));

                    // show template
                    $template = $GLOBALS['template_factory']->open('details/list');
                    echo $template->render(compact('title', 'data'));
                }
                ?>
                    </font>
                </td>
    </tr>
            <?
    if ($seminar['admission_prelim'] == 1) {
            ?>
            <tr>
                <td width="1%">&nbsp;</td>
                <td colspan=2 width="51%" valign="top">
                <font size=-1><b><?=_("Anmeldeverfahren:")?></b></font><br>
                <?
        echo "<font size=-1>";
        print(_("Die Auswahl der Teilnehmenden wird nach der Eintragung manuell vorgenommen."));
        echo "<br>";

        $query = "SELECT 1 FROM admission_seminar_user WHERE user_id = ? AND seminar_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user->id, $sem_id));
        $present = $statement->fetchColumn();

        if ($present) {
            echo "<table width=\"100%\">";
            printf ("<tr><td width=\"%s\">&nbsp;</td><td><font size=-1>%s</font><br></td></tr></table>", "2%", formatReady($seminar['admission_prelim_txt']));
        } else {
            if (!$perm->have_perm("admin")) {
                print("<p>"._("Wenn Sie an der Veranstaltung teilnehmen wollen, klicken Sie auf \"Tragen Sie sich hier ein\". Sie erhalten dann nähere Hinweise und kännen sich immer noch gegen eine Teilnahme entscheiden.")."</p>");
            } else {
                print("<p>"._("NutzerInnen, die sich für diese Veranstaltung eintragen möchten, erhalten nähere Hinweise und können sich dann noch gegen eine Teilnahme entscheiden.")."</p>");
            }
        }
        echo "</font>";
        echo "<td colspan=2 width=\"48%\" valign=\"top\"><td>";
        }
        ?>
        <? if (count($seminar_domains)): ?>
            <tr>
                <td width="1%">&nbsp;</td>
                <td colspan="4" valign="top">
                <font size="-1"><b><?= _("Zugelassenene Nutzerdomänen:") ?></b></font><br>
                <? foreach ($seminar_domains as $domain): ?>
                    <font size="-1"><?= htmlReady($domain->getName()) ?></font><br>
                <? endforeach ?>
                </td>
            </tr>
        <? endif ?>
        <?php
        if ($courseset = CourseSet::getSetForCourse($sem_id)) {
        ?>
            <tr>
                <td width="1%">&nbsp;</td>
                <td colspan="4" valign="top">
                <font size="-1">
                    <b>
                        <?= sprintf(_('Diese Veranstaltung gehört zum Anmeldeset "%s".'),
                            htmlReady($courseset->getName())) ?>
                    </b>
                </font>
                <div id="courseset_<?= $courseset->getId() ?>">
                    <?= $courseset->toString(true) ?>
                </div>
                </td>
            </tr>
        <?php } ?>
        <tr>
            <td width="1%">&nbsp;</td>
            <td width="24%" valign="top">
            <?
                //Statistikfunktionen
                $query = "SELECT COUNT(*) AS anzahl, SUM(status = 'dozent') AS anz_dozent,
                                 SUM(status = 'tutor') AS anz_tutor, SUM(status = 'autor') AS anz_autor,
                                 SUM(status = 'user') AS anz_user
                          FROM seminar_user
                          WHERE Seminar_id = ?
                          GROUP BY Seminar_id";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($sem_id));
                $seminar_counts = $statement->fetch(PDO::FETCH_ASSOC);

                $query = "SELECT COUNT(*) FROM admission_seminar_user WHERE seminar_id = ? AND status = 'accepted'";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($sem_id));
                $admission_count = $statement->fetchColumn();

                $count = 0;
                if ($seminar_counts['anzahl']) $count += $seminar_counts['anzahl'];
                if ($admission_count) $count += $admission_count;
                printf("<font size=-1><b>" . _("Anzahl der Teilnehmenden:") . "&nbsp;</b></font><font size=-1>%s </font>", ($count!=0) ? $count : _("keine"));
                printf("<br><font size=-1><b>%s:&nbsp;</b></font><font size=-1>%s </font>", get_title_for_status('dozent', $seminar_counts['anz_dozent'], $seminar['status']), $seminar_counts['anz_dozent'] ?: _('keine'));
                printf("<br><font size=-1><b>%s:&nbsp;</b></font><font size=-1>%s </font>", get_title_for_status('tutor', $seminar_counts['anz_tutor'], $seminar['status']), $seminar_counts['anz_tutor'] ?: _('keine'));
                printf("<br><font size=-1><b>" . _("Sonstige") . ":&nbsp;</b></font><font size=-1>%s </font>", (($seminar_counts['anz_autor'] + $seminar_counts['anz_user'] + $admission_count) ?: _('keine')));
            ?>
            </td>
            <td width="25%" valign="top">
            <?
            if(Seminar::GetInstance($sem_id)->isAdmissionEnabled()) {
                printf ("<font size=-1><b>" . _("max. Teilnehmerzahl:") . "&nbsp;</b></font><font size=-1>%s </font>", $seminar['admission_turnout']);
                printf ("<br><font size=-1><b>" . _("Freie Plätze:") . "&nbsp;</b></font><font size=-1>%s </font>",Seminar::GetInstance($sem_id)->getFreeAdmissionSeats() );
                if (!$seminar['admission_disable_waitlist']) {
                    $query = "SELECT COUNT(*) FROM admission_seminar_user WHERE seminar_id = ? AND status != 'accepted'";
                    $statement = DBManager::get()->prepare($query);
                    $statement->execute(array($sem_id));
                    $count = $statement->fetchColumn();
                    printf ("<br><font size=-1><b>" . _("Wartelisteneintr&auml;ge:") . "&nbsp;</b></font><font size=-1>%s </font>",$count);
                }
            }
            ?>
            </td>
            <td width="25%" valign="top">
            <?
                $count = 0;
                foreach (PluginEngine::getPlugins('ForumModule') as $plugin) {
                    $count += $plugin->getNumberOfPostingsForSeminar($sem_id);
                }
                printf ("<font size=-1><b>" . _("Forenbeiträge:") . "&nbsp;</b></font><font size=-1>%s </font>", $count ?: _('keine'));
            ?>
            </td>
            <td width="25%" valign="top">
            <?
                $query = "SELECT COUNT(*) FROM dokumente WHERE Seminar_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($sem_id));
                $count = $statement->fetchColumn();
                printf ("<font size=-1><b>" . _("Dokumente:") . "&nbsp;</b></font><font size=-1>%s </font>", $count ?: _('keine'));
            ?>
            </td>
            </tr>
        </table>
<?php
#    include ('lib/include/html_end.inc.php');

	$content_for_layout = ob_get_clean();

    // Infobox
    if ($sem_id) {
        $enrolment_info = Seminar::getInstance($sem_id)->getEnrolmentInfo($user->id);
        $info_msg = $enrolment_info['description'];

        if ($enrolment_info['enrolment_allowed'] && in_array($enrolment_info['cause'], words('member root courseadmin'))) {
            $abo_msg = _("direkt zur Veranstaltung");
        } elseif ($enrolment_info['enrolment_allowed']) {
            $abo_msg = _("Zugang zur Veranstaltung");
        }
        if (get_config('SCHEDULE_ENABLE') && !$perm->have_studip_perm("user", $sem_id) && !$perm->have_perm('admin') && Seminar::getInstance($sem_id)->getMetaDateCount()) {
            $query = "SELECT COUNT(*) FROM schedule_seminare WHERE seminar_id = ? AND user_id = ?";
            $statement = DBManager::Get()->prepare($query);
            $statement->execute(array($sem_id, $GLOBALS['user']->id));
            $sem_user_schedule = $statement->fetchColumn();
            if (!$sem_user_schedule) {
                $plan_msg = "<a href=\"".URLHelper::getLink("dispatch.php/calendar/schedule/addvirtual/$sem_id")."\">"._("Nur im Stundenplan vormerken")."</a>";
            }
        }
        if ($perm->have_studip_perm("user",$sem_id) && !$perm->have_studip_perm("tutor",$sem_id)) {
            if ($seminar['admission_binding'])
                $info_msg = sprintf(_("Das Austragen aus der Veranstaltung ist nicht mehr m&ouml;glich, da das Abonnement bindend ist.<br>Bitte wenden Sie sich an den Leiter (%s) der Veranstaltung!"), get_title_for_status('dozent', 1), $seminar['status']);
            else
                $delete_msg = _("Tragen Sie sich hier aus der Veranstaltung aus");
        }
    }

    if ($send_from_search) {
        $back_msg.=_("Zur&uuml;ck zur letzten Auswahl");
    }

    $picture_tmp = 'icons/16/red/decline.png';
    if ($enrolment_info['cause'] == 'member') {
        $mein_status = $perm->get_studip_perm($sem_id);
        $tmp_text = _("Sie sind als TeilnehmerIn der Veranstaltung eingetragen");
        $picture_tmp = 'icons/16/green/accept.png';
    }
    if ($enrolment_info['cause'] == 'accepted') {
        $admission_status = 'accepted';
        $tmp_text = sprintf(_("Sie wurden f&uuml;r diese Veranstaltung vorl&auml;ufig akzeptiert.<br>Lesen Sie den Hinweistext!"));
    }
    if ($enrolment_info['cause'] == 'awaiting') {
        $admission_status = 'awaiting';
        $tmp_text = _("Sie sind in die Warteliste der Veranstaltung eingetragen.");
    }
    if (in_array($enrolment_info['cause'], words('root courseadmin admin'))) {
        $tmp_text = _("Sie sind AdministratorIn und k&ouml;nnen deshalb die Veranstaltung nicht abonnieren.");
    }
    if (!$tmp_text) {
        $tmp_text = _("Sie sind nicht als TeilnehmerIn der Veranstaltung eingetragen.");
    }

    $infobox = array(
        array("kategorie"    => _("Pers&ouml;nlicher Status:"),
            "eintrag" => array(
                array( "icon" => $picture_tmp,
                    "text"  => $tmp_text
                )
            )
        ),
    );

    $infobox[2]["kategorie"] = _("Aktionen:");
    if ($abo_msg && $sem_id != $_SESSION['SessionSeminar']) {
        $infobox[2]["eintrag"][] = array (  "icon" => 'icons/16/black/door-enter.png',
                                    "text"  => "<a rel=\"lightbox\" href=\"".URLHelper::getScriptLink("dispatch.php/course/enrolment/apply/$sem_id")."\">".$abo_msg. "</a>"
                                );
    }
    if ($delete_msg) {
        $infobox[2]["eintrag"][] = array (  "icon" => 'icons/16/black/door-leave.png',
                                    "text"  => "<a href=\"".URLHelper::getLink("my_courses.php?auswahl=".$sem_id."&cmd=suppose_to_kill")."\">".$delete_msg."</a>"
                                );
    }
    if ($back_msg) {
        $infobox[2]["eintrag"][] = array (  "icon" => 'icons/16/black/link-intern.png',
                                    "text"  => "<a href=\"".URLHelper::getLink($send_from_search_page)."\">".$back_msg. "</a>"
                                );
    }
    if ($info_msg) {
        $infobox[2]["eintrag"][] = array (  "icon" => 'icons/16/black/info.png',
                                    "text"  => $info_msg
                                );
    }
    if ($plan_msg) {
        $infobox[2]["eintrag"][] = array (  "icon" => 'icons/16/black/info.png',
                                    "text"  => $plan_msg
                                );
    }


	if ($seminar['admission_binding']) {
	    $infobox[count($infobox)]["kategorie"] = _("Information:");
	    $infobox[count($infobox)-1]["eintrag"][] = array (  "icon" => 'icons/16/black/info.png',
	                                "text"  => _("Das Abonnement dieser Veranstaltung ist <u>bindend</u>!")
	                            );
	}

	$infobox = array(
        'content' => $infobox,
        'picture' => CourseAvatar::getAvatar($sem_id)
	);
	echo $GLOBALS['template_factory']->render('layouts/base.php', compact('content_for_layout', 'infobox'));

    page_close();
