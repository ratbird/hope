<?php
# Lifter007: TODO
# Lifter010: TODO
/**
 * dates.php - schedule for students
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Till Glöggler <tgloeggl@uni-osnabrueck.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

require '../lib/bootstrap.php';

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));

include ("lib/seminar_open.php"); // initialise Stud.IP-Session

URLHelper::bindLinkParam('date_type', $date_type);
URLHelper::bindLinkParam('raumzeit_filter', $raumzeitFilter);
URLHelper::bindLinkParam('rzSeminar', $rzSeminar);

$raumzeitFilter = Request::get('newFilter') ?: Request::get('raumzeit_filter');
$rzSeminar      = Request::option('rzSeminar');
$date_type      = Request::option('date_type');

$_SESSION['issue_open'] = array();
$_SESSION['raumzeitFilter'] = $raumzeitFilter;

require_once ('lib/classes/Seminar.class.php');
require_once ('lib/datei.inc.php');
require_once ('lib/raumzeit/raumzeit_functions.inc.php');

if ($RESOURCES_ENABLE) {
    include_once ($RELATIVE_PATH_RESOURCES."/lib/ResourceObject.class.php");
    include_once ($RELATIVE_PATH_RESOURCES."/lib/ResourcesUserRoomsList.class.php");
    include_once ($RELATIVE_PATH_RESOURCES."/lib/VeranstaltungResourcesAssign.class.php");
    include_once ($RELATIVE_PATH_RESOURCES."/lib/ResourceObjectPerms.class.php");
}
$cmd = Request::option('cmd');
$sem = new Seminar($SessionSeminar);

checkObject();
checkObjectModule("schedule");
object_set_visit_module("schedule");

PageLayout::setTitle($SessSemName["header_line"].' - '._("Ablaufplan"));

if ($date_type == '1') {
    Navigation::activateItem('/course/schedule/type1');
    URLHelper::bindLinkParam('type', Request::option('type'));
} else if ($date_type == 'other') {
    Navigation::activateItem('/course/schedule/other');
    URLHelper::bindLinkParam('type', Request::option('type'));
} else {
    Navigation::activateItem('/course/schedule/all');
}

$semester = new SemesterData();
$data = $semester->getCurrentSemesterData();
if (!$_SESSION['raumzeitFilter'] || ($rzSeminar != $SessSemName[1])) {
    $raumzeitFilter = $data['beginn'];
    $_SESSION['raumzeitFilter'] = $raumzeitFilter;
    $rzSeminar = $SessSemName[1];
}
$sem->checkFilter();
$themen =& $sem->getIssues();

function dates_open() {
    $_SESSION['issue_open'][Request::option('open_close_id')] = true;
}

function dates_close() {
    $_SESSION['issue_open'][Request::option('open_close_id')] = false;
    unset ($_SESSION['issue_open'][Request::option('open_close_id')]);
}

$sem->registerCommand('open', 'dates_open');
$sem->registerCommand('close', 'dates_close');
$sem->processCommands();

$termine = getAllSortedSingleDates($sem);

// Export the dates
if (Request::get('export') && $rechte) {
    $filename = prepareFilename($sem->getName() . '-' . _("Ablaufplan")) . '.doc';
    header("Content-type: application/vnd.ms-word");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Expires: 0");
    header("Cache-Control: private");
    header("Pragma: cache");

    $dates = array();

    if (is_array($termine) && sizeof($termine) > 0) {
        foreach ($termine as $singledate_id => $singledate) {
            if (!$singledate->isExTermin()) {
                $tmp_ids = $singledate->getIssueIDs();
                $title = $description = '';
                if (is_array($tmp_ids)) {
                    $thema_id = array_pop($tmp_ids);
                    $title = $themen[$thema_id]->getTitle();
                    $description = $themen[$thema_id]->getDescription();
                }

                $dates[] = array(
                    'date'  => $singledate->toString(),
                    'title' => $title,
                    'description' => $description,
                    'start' => $singledate->getStartTime(),
                    'related_persons' => $singledate->getRelatedPersons()
                );
            } elseif ($singledate->getComment()) {
                $dates[] = array(
                    'date'  => $singledate->toString(),
                    'title' => _('fällt aus') . ' (' . _('Kommentar:') . ' ' . $singledate->getComment() . ')',
                    'description' => '',
                    'start' => $singledate->getStartTime(),
                    'related_persons' => array()
                );
            }
        }
    }
    $content = $GLOBALS['template_factory']->open('dates_export')->render(compact('dates'));
    echo mb_encode_numericentity($content, array(0x80, 0xffff, 0, 0xffff), 'cp1252');
} else {
    PageLayout::addSqueezePackage('raumzeit');
    PageLayout::addHeadElement('script', array(), "
    jQuery(function () {
        STUDIP.CancelDatesDialog.reloadUrlOnClose = '" . URLHelper::getUrl() ."';
    });");
    if ($cmd == 'openAll') $openAll = true;
    $dates = array();

    if (is_array($termine) && sizeof($termine) > 0) {
        foreach ($termine as $singledate_id => $singledate) {

            $showSpecialDays = FALSE;
            $tpl = null;
            $tpl = getTemplateDataForSingleDate($singledate, $metadate_id);
            // If "Sitzung" shall not be shown, uncomment this
            /*if ($tpl['type'] == 1 || $tpl['type'] == 7) {
                unset($tpl['art']);
            }*/

            //calendar jump
            if ($user->id != 'nobody') {
                $tpl['calendar'] = $GLOBALS['template_factory']->open('raumzeit/calendar_jump')
                        ->render(array('start' => $singledate->getStartTime()));
            }

            if ($date_type) {
                switch ($date_type) {
                    case 'all':
                        break;

                    case 'other':
                        if ($TERMIN_TYP[$tpl['type']]['sitzung'] || $tpl['deleted']) {
                            continue 2;
                        }
                        break;

                    default:
                        if (!$TERMIN_TYP[$tpl['type']]['sitzung'] || $tpl['deleted']) {
                            continue 2;
                        }
                        break;
                }
            }

            if ($openAll) $tpl['openall'] = true;

            if (!$tpl['deleted'] || $tpl['comment'])  {
                $tpl['class'] = 'printhead';
                $tpl['cycle_id'] = $metadate_id;

                $issue_id = '';
                if (is_array($tmp_ids = $singledate->getIssueIDs())) {
                    foreach ($tmp_ids as $val) {
                        if (empty($issue_id)) {
                            if (is_object($themen[$val])) {
                                $issue_id = $val;
                            }
                        } else {
                            if (is_object($themen[$val])) {
                                $tpl['additional_themes'][] = array('title' => $themen[$val]->getTitle(), 'desc' => $themen[$val]->getDescription());
                            }
                        }
                    }
                }

                if (is_object($themen[$issue_id])) {
                    $tpl['issue_id'] = $issue_id;
                    $thema =& $themen[$issue_id];
                    $tpl['theme_title'] = $thema->getTitle();
                    $tpl['theme_description'] = $thema->getDescription();
                    $tpl['folder_id'] = $thema->getFolderID();
                    $tpl['fileEntry'] = $thema->hasFile();
                    if($tpl['fileEntry']){
                        $tpl['fileCountAll'] = doc_count($thema->getFolderId());
                    } else {
                        $tpl['fileCountAll'] = 0;
                    }
                }

                $dates[] = $tpl;
            }
        }
    }


    $template = $GLOBALS['template_factory']->open('dates');
    $infobox = $GLOBALS['template_factory']->open('infobox/infobox_dates');

    $issue_open = $_SESSION['issue_open'];
    $cancelled_dates_locked = LockRules::Check($sem->getId(), 'cancelled_dates');

    $semester_selectionlist = raumzeit_get_semesters($sem, $semester, $raumzeitFilter);
    $picture = 'sidebar/date-sidebar.png';
    $selectionlist_title = _("Semesterauswahl");
    $selectionlist = $semester_selectionlist;
    $layout = $GLOBALS['template_factory']->open('layouts/base.php');
    $layout->infobox = $infobox->render(compact('picture', 'selectionlist_title', 'selectionlist', 'rechte', 'raumzeitFilter'));
    $layout->content_for_layout = $template->render(compact('dates', 'sem', 'rechte', 'openAll', 'issue_open', 'raumzeitFilter', 'cancelled_dates_locked'));

    echo $layout->render();
}
page_close();
