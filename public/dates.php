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

$issue_open = array();
$raumzeitFilter = Request::get('raumzeit_filter');

URLHelper::bindLinkParam('date_type', Request::get('date_type'));
URLHelper::bindLinkParam('raumzeit_filter', $raumzeitFilter);
URLHelper::bindLinkParam('rzSeminar', $rzSeminar);

include ("lib/seminar_open.php"); // initialise Stud.IP-Session

require_once ('lib/classes/Seminar.class.php');
require_once ('lib/datei.inc.php');
require_once ('lib/forum.inc.php');
require_once ('lib/raumzeit/raumzeit_functions.inc.php');

if ($RESOURCES_ENABLE) {
    include_once ($RELATIVE_PATH_RESOURCES."/lib/ResourceObject.class.php");
    include_once ($RELATIVE_PATH_RESOURCES."/lib/ResourcesUserRoomsList.class.php");
    include_once ($RELATIVE_PATH_RESOURCES."/lib/VeranstaltungResourcesAssign.class.php");
    include_once ($RELATIVE_PATH_RESOURCES."/lib/ResourceObjectPerms.class.php");
}

$sem = new Seminar($SessionSeminar);

checkObject();
checkObjectModule("schedule");
object_set_visit_module("schedule");

PageLayout::setTitle($SessSemName["header_line"].' - '._("Ablaufplan"));

if (Request::get('date_type') == '1') {
    Navigation::activateItem('/course/schedule/type1');
    UrlHelper::bindLinkParam('type', $type);
} else if (Request::get('date_type') == 'other') {
    Navigation::activateItem('/course/schedule/other');
    UrlHelper::bindLinkParam('type', $type);
} else {
    Navigation::activateItem('/course/schedule/all');
}

$semester = new SemesterData();
$data = $semester->getCurrentSemesterData();
if (!$raumzeitFilter || ($rzSeminar != $SessSemName[1])) {
    $raumzeitFilter = $data['beginn'];
    $rzSeminar = $SessSemName[1];
}
$sem->checkFilter();
$themen =& $sem->getIssues();

function dates_open() {
    global $issue_open;

    $issue_open[$_REQUEST['open_close_id']] = true;
}

function dates_close() {
    global $issue_open;

    $issue_open[$_REQUEST['open_close_id']] = false;
    unset ($issue_open[$_REQUEST['open_close_id']]);
}

$sem->registerCommand('open', 'dates_open');
$sem->registerCommand('close', 'dates_close');
$sem->processCommands();

$termine = getAllSortedSingleDates($sem);

// Export the dates
if (Request::get('export') && $rechte) {
    header("Content-type: application/vnd.ms-word");
    header("Content-Disposition: attachment; filename=ablaufplan.doc");
    header("Expires: 0");
    header("Cache-Control: private");
    header("Pragma: cache");

    $dates = array();

    if (is_array($termine) && sizeof($termine) > 0) {
        foreach ($termine as $singledate_id => $singledate) {
            if (!$singledate->isExTermin()) {
                $tmp_ids = $singledate->getIssueIDs();
                $title = '';
                if (is_array($tmp_ids)) {
                    $title = $themen[array_pop($tmp_ids)]->getTitle();
                }

                $dates[] = array(
                    'date'  => $singledate->toString(),
                    'title' => $title,
                    'start' => $singledate->getStartTime(),
                    'related_persons' => $singledate->getRelatedPersons()
                );
            }
        }
    }

    echo $GLOBALS['template_factory']->open('dates_export')->render(compact('dates'));
} else {
    // Start of Output
    include ('lib/include/html_head.inc.php'); // Output of html head
    include ('lib/include/header.php');   // Output of Stud.IP head

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

            if (Request::get('date_type')) {
                switch (Request::get('date_type')) {
                    case 'all':
                        break;

                    case 'other':
                        if ($tpl['type'] == 1) {
                            $tpl['deleted'] = true;
                        }
                        break;

                    default:
                        if ($tpl['type'] != Request::get('date_type')) {
                            $tpl['deleted'] = true;
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
                    $tpl['forumEntry'] = $thema->hasForum();
                    $tpl['fileEntry'] = $thema->hasFile();
                    if($tpl['forumEntry']) {
                        $tpl['forumCount'] = forum_count($thema->getIssueId(), $sem->getId());
                    } else {
                        $tpl['forumCount'] = 0;
                    }
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
    echo $template->render(compact('dates', 'sem', 'rechte', 'openAll', 'issue_open', 'raumzeitFilter'));

}
include ('lib/include/html_end.inc.php');
page_close();
