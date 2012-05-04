<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
themen_ablaufplan.php: GUI for default-view of the theme managment
Copyright (C) 2005-2007 Till Glöggler <tgloeggl@uos.de>

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

use Studip\Button, Studip\LinkButton;

// -- here you have to put initialisations for the current page
define('SELECTED', ' checked');
define('NOT_SELECTED', '');

//$sess->register('issue_open');
//$sess->register('raumzeitFilter');

$_SESSION['issue_open'] = array();

require_once ('lib/classes/Seminar.class.php');
require_once ('lib/classes/Modules.class.php');
require_once ('lib/raumzeit/raumzeit_functions.inc.php');
require_once ('lib/raumzeit/themen_ablaufplan.inc.php');
require_once 'lib/admin_search.inc.php';

if (get_config('RESOURCES_ENABLE')) {
    include_once ($GLOBALS['RELATIVE_PATH_RESOURCES']."/lib/ResourceObject.class.php");
    include_once ($GLOBALS['RELATIVE_PATH_RESOURCES']."/lib/ResourcesUserRoomsList.class.php");
    include_once ($GLOBALS['RELATIVE_PATH_RESOURCES']."/lib/VeranstaltungResourcesAssign.class.php");
    include_once ($GLOBALS['RELATIVE_PATH_RESOURCES']."/lib/ResourceObjectPerms.class.php");
    $resList = new ResourcesUserRoomsList($user->id, TRUE, FALSE, TRUE);
}

$moduleClass = new Modules();
$modules = $moduleClass->getLocalModules($id);

//Output starts here

include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   //hier wird der "Kopf" nachgeladen
include 'lib/include/admin_search_form.inc.php';

if (!$perm->have_studip_perm('tutor', $id)) {
    die;
}

$powerFeatures = true;
if (isset($_REQUEST['cmd'])) {
    $cmd = $_REQUEST['cmd'];
}
$sem = new Seminar($id);
$sem->checkFilter();
$themen =& $sem->getIssues();

// if all entries are opened, we parse the submitted results into appropriate arrays
foreach ($_REQUEST as $key => $val) {
    if ($_REQUEST['allOpen']) {
        if (strstr($key, 'theme_title')) {
            $keys = explode('§', $key);
            $changeTitle[$keys[1]] = $val;
        }
        if (strstr($key, 'theme_description')) {
            $keys = explode('§', $key);
            $changeDescription[$keys[1]] = $val;
        }
        if (strstr($key, 'forumFolder')) {
            $keys = explode('§', $key);
            $changeForum[$keys[1]] = $val;
        }
        if (strstr($key, 'fileFolder')) {
            $keys = explode('§', $key);
            $changeFile[$keys[1]] = $val;
        }
    }
}

$sem->registerCommand('open', 'themen_open');
$sem->registerCommand('close', 'themen_close');
$sem->registerCommand('openAll', 'themen_openAll');
$sem->registerCommand('closeAll', 'themen_closeAll');
$sem->registerCommand('editAll', 'themen_saveAll');
$sem->registerCommand('editIssue', 'themen_changeIssue');
$sem->registerCommand('addIssue', 'themen_doAddIssue');
$sem->processCommands();

// add status-message if there are dates which are not covered by the choosable semesters
if ($sem->hasDatesOutOfDuration()) {
    $tpl['forceShowAll'] = TRUE;
    if ($_SESSION['raumzeitFilter'] != 'all') {
        $sem->createInfo(_("Es gibt weitere Termine, die au&szlig;erhalb der regul&auml;ren Laufzeit der Veranstaltung liegen.<br> Um diese anzuzeigen w&auml;hlen Sie bitte \"Alle Semester\"!"));
    }
} else {
    $tpl['forceShowAll'] = FALSE;
}

// fill values for infobox
/* * * * * * * * * * * * * * *
 *       I N F O B O X       *
 * * * * * * * * * * * * * * */

if ($sem->metadates->art == 0) {
    $times_info .= '<b>'._("Typ").':</b> '._("regelm&auml;&szlig;ige Veranstaltung").'<br>';
    $z = 0;
    if (is_array($turnus = $sem->getFormattedTurnusDates())) {
        foreach ($turnus as $val) {
            if ($z != 0) { $times_info .= '<br>'; } $z = 1;
            $times_info .= $val;
        }
    }
} else {
    $times_info .= '<b>'._("Typ").':</b> '._("unregelm&auml;&szlig;ige Veranstaltung").'<br>';
}

// infobox end

unset($themen);
$themen =& $sem->getIssues(true);   // read again, so we have the actual sort order and so on

$semester = new SemesterData();
$all_semester = $semester->getAllSemesterData();
$grenze = 0;

$termine = getAllSortedSingleDates($sem);

?>
<form action="<?= URLHelper::getLink() ?>" method="post">
<?= CSRFProtection::tokenTag() ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
        <td class="blank" valign="top">
        <?php
            // show messages
            if ($messages = $sem->getStackedMessages()) :
                foreach ($messages as $type => $message_data) :
                    echo MessageBox::$type( $message_data['title'], $message_data['details'] );
                endforeach;
            endif;
        ?>
            <table width="99%" cellspacing="0" cellpadding="0" border="0">
                <? if (is_array($termine) && sizeof($termine) > 0) : ?>
                <tr>
                    <td class="steelgraulight" colspan="6" height="24" align="center">
                        <a href="<?= URLHelper::getLink("?cmd=".(($openAll) ? 'close' : 'open')."All") ?>">
                            <IMG src="<?=$GLOBALS['ASSETS_URL']?>images/<?=($openAll) ? 'close' : 'open'?>_all.png" border="0" <?=tooltip(sprintf("Alle Termine %sklappen", ($openAll) ? 'zu' : 'auf'))?>>
                        </a>
                    </td>
                </tr>
                <? else : ?>
                <tr>
                    <td align="center">
                        <br>
                        <?= _("Im ausgewählten Zeitraum sind keine Termine vorhanden."); ?>
                    </td>
                </tr>
                <? endif; ?>
                <tr>
                    <td class="blank" colspan="6" height="2"></td>
                </tr>
            <? if ($openAll) { ?>
                <tr>
                    <td class="steelgraulight" colspan="6" align="center" height="30" valign="middle">
                        <input type="hidden" name="allOpen" value="TRUE">
                        <?= Button::create(_('Alles übernehmen'), 'editAll') ?>
                        &nbsp;&nbsp;&nbsp;
                        <?= LinkButton::createCancel(_('Abbrechen'), URLHelper::getURL('', array('cmd' => 'closeAll'))) ?>
                    </td>
                </tr>

                <tr>
                    <td class="steel1" colspan="4"></td>
                    <td class="steel1" colspan="2" align="left" nowrap="nowrap">
                        <font size="-1">
                            <? if ($modules['forum']) : ?>
                            <input type="checkbox" name="createAllForumFolders"> <?=_("Für alle Termine einen Forumsordner anlegen")?>
                            <br>
                            <? endif;   if ($modules['documents']) : ?>
                            <input type="checkbox" name="createAllFileFolders"> <?=_("Für alle Termine einen Dateiordner anlegen")?>
                            <? endif; ?>
                        </font>
                    </td>
                </tr>
            <? } ?>
                <tr>
                    <td class="blank" colspan="6" height="2"></td>
                </tr>
                <?

                foreach ($termine as $singledate_id => $singledate) {

                    if ( ($grenze == 0) || ($grenze < $singledate->getStartTime()) ) {
                        foreach ($all_semester as $zwsem) {
                            if ( ($zwsem['beginn'] < $singledate->getStartTime()) && ($zwsem['ende'] > $singledate->getStartTime()) ) {
                                $grenze = $zwsem['ende'];
                                ?>
                                <tr>
                                    <td class="steelgraulight" align="center" colspan="9">
                                        <font size="-1"><b><?=$zwsem['name']?></b></font>
                                    </td>
                                </tr>
                                <?
                            }
                        }
                    }

                    // Template fuer einzelnes Datum
                    $showSpecialDays = FALSE;
                    $tpl = getTemplateDataForSingleDate($singledate, $metadate_id);
                    if (!$tpl['deleted']) {
                        $tpl['class'] = 'printhead';
                        $tpl['cycle_id'] = $metadate_id;
                        $tpl['art'] = $TERMIN_TYP[$tpl['type']]['name'];

                    // calendar jump
                    $tpl['calendar'] = "&nbsp;<a href=\"".URLHelper::getLink("calendar.php?caluser=self&cmd=showweek&atime=". $singledate->getStartTime());
                    $tpl['calendar'] .= "\"><img class=\"text-top\" src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/schedule.png\" ";
                    $tpl['calendar'] .= tooltip(sprintf(_("Zum %s in den persönlichen Terminkalender springen"), date("d.m", $singledate->getStartTime())));
                    $tpl['calendar'] .= '></a>';

                        // activated modules
                        $tpl['modules'] = $modules;

                        $issue_id = '';
                        if (is_array($tmp_ids = $singledate->getIssueIDs())) {
                            foreach ($tmp_ids as $val) {
                                $issue_id = $val;
                                break;
                            }
                        }
                        if ($issue_id == '') {
                            $tpl['submit_name'] = 'addIssue';
                        } else {
                            $tpl['submit_name'] = 'editIssue';
                            $tpl['issue_id'] = $issue_id;
                            if ($themen[$issue_id]) {
                                $thema =& $themen[$issue_id];
                                $tpl['theme_title'] = $thema->getTitle();
                                $tpl['theme_description'] = $thema->getDescription();
                                $tpl['forumEntry'] = ($thema->hasForum()) ? SELECTED : NOT_SELECTED;
                                $tpl['fileEntry'] = ($thema->hasFile()) ? SELECTED : NOT_SELECTED;
                            } else {
                                $tpl['theme_title'] = '';
                                $tpl['theme_description'] = '';
                            }
                        }

                        include('lib/raumzeit/templates/singledate_ablaufplan.tpl');
                    }
                }

            if ($openAll) {
                ?>
                <tr>
                    <td class="steelgraulight" colspan="6" align="center" height="30" valign="middle">
                        <input type="hidden" name="allOpen" value="TRUE">
                        <?= Button::create(_('Alles übernehmen'), 'editAll') ?>
                        &nbsp;&nbsp;&nbsp;
                        <?= LinkButton::createCancel(_('Abbrechen'), URLHelper::getURL('', array('cmd' => 'closeAll'))) ?>
                    </td>
                </tr>
            <? } ?>
            </table>
        </td>
        <td width="270" align="right" class="blank" valign="top">
        <?

            // print info box:
            // get template
            $infobox_template = $GLOBALS['template_factory']->open('infobox/infobox_topic_admin');

            // get a list of semesters (as display options)
            $semester_selectionlist = raumzeit_get_semesters($sem, $semester, $_SESSION['raumzeitFilter']);

            // fill attributes
            $infobox_template->set_attribute('picture', 'infobox/schedules.jpg');
            $infobox_template->set_attribute("selectionlist_title", "Semesterauswahl");
            $infobox_template->set_attribute('selectionlist', $semester_selectionlist);
            $infobox_template->set_attribute('times_info', $times_info);

            // render template
            echo $infobox_template->render();

        ?>
        </td>
    </tr>
    <tr>
        <td class="blank" colspan="5">
            &nbsp;
        </td>
    </tr>
</table>
</form>
<?
    $sem->store();
    include 'lib/include/html_end.inc.php';
    page_close();
