<?php
# Lifter001: DONE
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO
/*
seminar_main.php - Die Eingangs- und Uebersichtsseite fuer ein Seminar
Copyright (C) 2000 Stefan Suchi <suchi@gmx.de>, Ralf Stockmann <rstockm@gwdg.de>

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

ob_start();
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Default_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$auth->login_if(Request::get('again') && ($auth->auth["uid"] == "nobody"));

if (Request::option('auswahl')) {
    Request::set('cid', Request::option('auswahl'));
}

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page
require_once('lib/dates.inc.php'); //Funktionen zur Anzeige der Terminstruktur
require_once('lib/visual.inc.php');
require_once 'lib/functions.php';

$course_id = $_SESSION['SessionSeminar'];

//set visitdate for course, when coming from meine_seminare
if (Request::get('auswahl')) {
    object_set_visit($course_id, "sem");
}

// gibt es eine Anweisung zur Umleitung?
if (Request::get('redirect_to')) {
    $query_parts = explode('&', stristr(urldecode($_SERVER['QUERY_STRING']), 'redirect_to'));
    list( , $where_to) = explode('=', array_shift($query_parts));
    $new_query = $where_to . '?' . join('&', $query_parts);
    page_close();
    $new_query = preg_replace('/[^0-9a-z+_#?&=.-\/]/i', '', $new_query);
    header('Location: '.URLHelper::getURL($new_query, array('cid' => $course_id)));
    die;
}
$sem = new Seminar($course_id);
$sem_class = $GLOBALS['SEM_CLASS'][$GLOBALS['SEM_TYPE'][$sem->status]['class']];
$sem_class || $sem_class = SemClass::getDefaultSemClass();
if ($sem_class->getSlotModule("overview") !== "CoreOverview") {
    foreach ($sem_class->getNavigationForSlot("overview") as $nav) {
        header('Location: '.URLHelper::getURL($nav->getURL()));
        die;
    }
} else {
    $Modules = new Modules();
    $course_modules = $Modules->getLocalModules($course_id);
    if (!$course_modules['overview'] && !$sem_class->isSlotMandatory("overview")) {
        //Keine �bersichtsseite. Anstatt eines Fehler wird der Nutzer zum ersten
        //Reiter der Veranstaltung weiter geleitet.
        if (Navigation::hasItem("/course")) {
            foreach (Navigation::getItem("/course")->getSubNavigation() as $navigation) {
                header('Location: '.URLHelper::getURL($navigation->getURL()));
                die;
            }
        }
    }
}

if (get_config('NEWS_RSS_EXPORT_ENABLE') && $course_id){
    $rss_id = StudipNews::GetRssIdFromRangeId($course_id);
    if ($rss_id) {
        PageLayout::addHeadElement('link', array('rel'   => 'alternate',
                                                 'type'  => 'application/rss+xml',
                                                 'title' => 'RSS',
                                                 'href'  => 'rss.php?id='.$rss_id));
    }
}

checkObject();

PageLayout::setHelpKeyword("Basis.InVeranstaltungKurzinfo");
PageLayout::setTitle($GLOBALS['SessSemName']["header_line"]. " - " . _("Kurzinfo"));
Navigation::activateItem('/course/main/info');
// add skip link
SkipLinks::addIndex(Navigation::getItem('/course/main/info')->getTitle(), 'main_content', 100);

include 'lib/showNews.inc.php';
include 'lib/show_dates.inc.php';

$studygroup_mode = $SEM_CLASS[$SEM_TYPE[$GLOBALS['SessSemName']["art_num"]]["class"]]["studygroup_mode"];

// list of used modules
$Modules = new Modules;
$modules = $Modules->getLocalModules($course_id);

$sem = Seminar::GetInstance($course_id);

URLHelper::bindLinkParam("sem_data", $smain_data);

//Auf und Zuklappen Termine
if (Request::get('dopen')) {
    $smain_data['dopen'] = Request::option('dopen');
}
if (Request::get('dclose')) {
    unset($smain_data['dopen']);
}
//Auf und Zuklappen News
process_news_commands($smain_data);

//calculate a "quarter" year, to avoid showing dates that are older than a quarter year (only for irregular dates)
$quarter_year = 60 * 60 * 24 * 90;

?>
<table width="100%" border=0 cellpadding=0 cellspacing=0>
    <tr>
        <td class="blank" valign="top" id="main_content">
    <?
    echo "<h3>".htmlReady($GLOBALS['SessSemName']["header_line"]). "</h3>";
    if ($GLOBALS['SessSemName'][3]) {
        echo "<b>" . _("Untertitel:") . " </b>";
        echo htmlReady($GLOBALS['SessSemName'][3]);
        echo "<br>";
    }

    if (!$studygroup_mode) { ?>
        <b><?= _("Zeit / Veranstaltungsort") ?>:</b><br>
        <?
        $show_link = ($perm->have_studip_perm('autor', $course_id) && $modules['schedule']);
        echo $sem->getDatesTemplate('dates/seminar_html', array('link_to_dates' => $show_link, 'show_room' => true));
        ?>

        <br>
        <br>

        <?
        $next_date = $sem->getNextDate();
        if ($next_date) {
            echo '<b>'._("N�chster Termin").':</b><br>';
            echo $next_date . '<br>';
        } else if ($first_date = $sem->getFirstDate()) {
            echo '<b>'._("Erster Termin").':</b><br>';
            echo $first_date . '<br>';
        } else {
            echo '<b>'._("Erster Termin").':</b><br>';
            echo _("Die Zeiten der Veranstaltung stehen nicht fest."). '<br>';
        }

    $dozenten = $sem->getMembers('dozent');
    $num_dozenten = count($dozenten);
    $show_dozenten = array();
    foreach($dozenten as $dozent) {
        $show_dozenten[] = '<a href="'.URLHelper::getLink("dispatch.php/profile?username=".$dozent['username']).'">'
                            . htmlready($num_dozenten > 10 ? get_fullname($dozent['user_id'], 'no_title_short') : $dozent['fullname'])
                            . '</a>';
    }
    printf("<br><b>%s: </b>%s", get_title_for_status('dozent', $num_dozenten), implode(', ', $show_dozenten));

    ?>
        <br>
        <br>
    <?
        // Ticket #68
        if (!$perm->have_studip_perm('dozent', $course_id)) {
            $rule = AuxLockRules::getLockRuleBySemId($course_id);
            if (isset($rule)) {
                $show = false;
                foreach ((array)$rule['attributes'] as $val) {
                    if ($val == 1) {
                        // Es gibt also Zusatzangaben. Nun noch �berpr�fen ob der Nutzer diese Angaben schon gemacht hat...
                        $query = "SELECT 1
                                  FROM datafields
                                  LEFT JOIN datafields_entries USING (datafield_id)
                                  WHERE object_type = 'usersemdata' AND sec_range_id = ? AND range_id = ?";
                        $statement = DBManager::get()->prepare($query);
                        $statement->execute(array($course_id, $user->id));
                        if (!$statement->fetchColumn()) {
                            $show = true;
                        }
                        break;
                    }
                }

                if ($show) {
                    echo MessageBox::info(_("Sie haben noch nicht die f�r diese Veranstaltung ben�tigten Zusatzinformationen eingetragen."), array(
                        _('Um das nachzuholen, gehen Sie unter "TeilnehmerInnen" auf "Zusatzangaben"'),
                        _("oder") . ' <a href="' . URLHelper::getLink("teilnehmer_aux.php") . '"> ' . _("direkt zu den Zusatzangaben") . '</a>'
                    ));
                }
            }
        }
    } else {
        echo '<b>'._('Beschreibung:').' </b><br>'. formatLinks($sem->description) .'<br><br>';
        echo '<b>'._('Moderiert von:') .'</b> ';
        $all_mods = $sem->getMembers('dozent') + $sem->getMembers('tutor');
        $mods = array();
        foreach($all_mods as $mod) {
            $mods[] = '<a href="'.URLHelper::getLink("dispatch.php/profile?username=".$mod['username']).'">'.htmlready($mod['fullname']).'</a>';
        }
        echo implode(', ', $mods);
    }
?>
        </td>
        <td class="blank" align="right" valign="top">
            <? if ($studygroup_mode) : ?>
            <?= StudygroupAvatar::getAvatar($course_id)->getImageTag(Avatar::NORMAL) ?>
            <? else: ?>
            <?= CourseAvatar::getAvatar($course_id)->getImageTag(Avatar::NORMAL) ?>
            <? endif; ?>
        </td>
    </tr>
    </table>

<?php

// Anzeige von News
show_news($course_id, $rechte, 0, $smain_data["nopen"], "100%", object_get_visit($course_id, "sem"), $smain_data);

// Anzeige von Terminen
$start_zeit=time();
$end_zeit=$start_zeit+1210000;
$show_admin = false;
if (!$studygroup_mode) {
    if ($rechte) {
        $show_admin = URLHelper::getLink("admin_dates.php?range_id=".$course_id."&ebene=sem&new_sem=TRUE");
        PageLayout::addSqueezePackage('raumzeit');
        PageLayout::addHeadElement('script', array(), "
        jQuery(function () {
            STUDIP.CancelDatesDialog.reloadUrlOnClose = '" . URLHelper::getUrl() ."';
        });");
    }
    show_dates($start_zeit, $end_zeit, $smain_data["dopen"], $course_id, 0, TRUE, $show_admin);
}

// include and show votes and tests
    $factory = new Flexi_TemplateFactory($STUDIP_BASE_PATH . '/app/views');
    echo $factory->render("vote/index", array('votes' => StudipVote::findByRange_id($course_id)));

// display plugins
$plugins = PluginEngine::getPlugins('StandardPlugin', $course_id);
$layout = $GLOBALS['template_factory']->open('shared/index_box');

foreach ($plugins as $plugin) {
    $template = $plugin->getInfoTemplate($course_id);

    if ($template) {
        echo $template->render(NULL, $layout);
        $layout->clear_attributes();
    }
}

$layout = $GLOBALS['template_factory']->open('layouts/base.php');
$layout->content_for_layout = ob_get_clean();
echo $layout->render();
page_close();
