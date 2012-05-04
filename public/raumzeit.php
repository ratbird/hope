<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter005: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
GUI for Seminar.class.php und all aggregated classes
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

require '../lib/bootstrap.php';
unregister_globals();

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

PageLayout::setHelpKeyword("Basis.VeranstaltungenVerwaltenAendernVonZeitenUndTerminen");

// -- here you have to put initialisations for the current page
$list = Request::option('list');
$seminar_id = Request::option('seminar_id');
if ($list) {
    URLHelper::removeLinkParam('seminar_id');
    unset($seminar_id);
}

if (isset($_REQUEST['seminar_id'])) {
    URLHelper::bindLinkParam('seminar_id', $seminar_id);
}

$id = Request::option('seminar_id', $SessSemName[1]);

require_once ('lib/classes/Seminar.class.php');
require_once ('lib/raumzeit/raumzeit_functions.inc.php');
require_once ('lib/dates.inc.php');
require_once 'lib/admin_search.inc.php';
require_once 'lib/classes/LockRules.class.php';
require_once 'lib/classes/AdminList.class.php';


if (get_config('RESOURCES_ENABLE')) {
    include_once ($GLOBALS['RELATIVE_PATH_RESOURCES'] ."/lib/ResourceObject.class.php");
    include_once ($GLOBALS['RELATIVE_PATH_RESOURCES'] ."/lib/ResourcesUserRoomsList.class.php");
    include_once ($GLOBALS['RELATIVE_PATH_RESOURCES'] ."/lib/VeranstaltungResourcesAssign.class.php");
    include_once ($GLOBALS['RELATIVE_PATH_RESOURCES'] ."/lib/ResourceObjectPerms.class.php");
    $resList = new ResourcesUserRoomsList($user->id, TRUE, FALSE, TRUE);
}

PageLayout::addSqueezePackage('raumzeit');
PageLayout::setTitle(_("Verwaltung von Zeiten und Raumangaben"));

if ($perm->have_perm('admin')) {
    Navigation::activateItem('/admin/course/dates');
} else {
    Navigation::activateItem('/course/admin/dates');
}
$sd_open = Request::optionArray('sd_open');
$_SESSION['raumzeitFilter'] = Request::get('newFilter');
// bind linkParams for chosen semester and opened dates
URLHelper::bindLinkParam('raumzeitFilter', $_SESSION['raumzeitFilter']);
URLHelper::bindLinkParam('sd_open', $sd_open);

//Change header_line if open object
$header_line = getHeaderLine($id);
if ($header_line)
    PageLayout::setTitle($header_line." - ".PageLayout::getTitle());

//save messages from
$pmessages = PageLayout::getMessages();

//Output starts here

include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   //hier wird der "Kopf" nachgeladen
include 'lib/include/admin_search_form.inc.php';

if (!$perm->have_studip_perm('tutor', $id)) {
    die;
}
$cmd = Request::option('cmd');
unQuoteAll();

$sem = Seminar::GetInstance($id);
$sem->checkFilter();

$semester = new SemesterData();
$_LOCKED = FALSE;
if (LockRules::Check($id, 'room_time')) {
    $_LOCKED = TRUE;
    $data = LockRules::getObjectRule($id);
    $sem->createInfo(_("Diese Seite ist für die Bearbeitung gesperrt. Sie können die Daten einsehen, jedoch nicht verändern.")
    . ($data['description'] ? '<br>'.formatLinks($data['description']) : ''));
}


// what to do with the text-field
if ($GLOBALS['RESOURCES_ENABLE'] && $resList->numberOfRooms()) {
    if ( ($_REQUEST['freeRoomText'] != '' && !in_array($_REQUEST['room'], words('nothing nochange'))) || ($_REQUEST['freeRoomText_sd'] != '' && !in_array($_REQUEST['room_sd'], words('nothing nochange'))) ) {
        $sem->createError("Sie k&ouml;nnen nur eine freie Raumangabe machen, wenn Sie \"keine Buchung, nur Textangabe\" ausw&auml;hlen!");
        unset($_REQUEST['freeRoomText']);
        unset($_REQUEST['room']);
        unset($_REQUEST['freeRoomText_sd']);
        unset($_REQUEST['room_sd']);
        unset($cmd);
        $open_close_id = $_REQUEST['singleDateID'];
        $cmd = 'open';
    }
}

require_once('lib/raumzeit.inc.php');
$sem->registerCommand('open', 'raumzeit_open');
$sem->registerCommand('close', 'raumzeit_close');
$sem->registerCommand('delete_singledate', 'raumzeit_delete_singledate');
$sem->registerCommand('undelete_singledate', 'raumzeit_undelete_singledate');
$sem->registerCommand('checkboxAction', 'raumzeit_checkboxAction');
$sem->registerCommand('bookRoom', 'raumzeit_bookRoom');
$sem->registerCommand('selectSemester', 'raumzeit_selectSemester');
$sem->registerCommand('addCycle', 'raumzeit_addCycle');
$sem->registerCommand('doAddCycle', 'raumzeit_doAddCycle');
$sem->registerCommand('editCycle', 'raumzeit_editCycle');
$sem->registerCommand('deleteCycle', 'raumzeit_deleteCycle');
$sem->registerCommand('doDeleteCycle', 'raumzeit_doDeleteCycle');
$sem->registerCommand('doAddSingleDate', 'raumzeit_doAddSingleDate');
$sem->registerCommand('editSingleDate_button', 'raumzeit_editSingleDate');
$sem->registerCommand('editSingleDate', 'raumzeit_editSingleDate');
$sem->registerCommand('editDeletedSingleDate', 'raumzeit_editDeletedSingleDate');
$sem->registerCommand('freeText', 'raumzeit_freeText');
$sem->registerCommand('removeRequest', 'raumzeit_removeRequest');
$sem->registerCommand('removeSeminarRequest', 'raumzeit_removeSeminarRequest');
$sem->registerCommand('removeMetadateRequest', 'raumzeit_removeMetadateRequest');
$sem->registerCommand('moveCycle', 'raumzeit_moveCycle');
$sem->registerCommand('related_persons_action_do', 'raumzeit_related_persons_action_do');
$sem->processCommands();

// get possible start-weeks
$start_weeks = array();

$semester_index = get_sem_num($sem->getStartSemester());
$tmp_first_date = getCorrectedSemesterVorlesBegin($semester_index);
$all_semester = $semester->getAllSemesterData();
$end_date = $all_semester[$semester_index]['vorles_ende'];
$date = getdate($tmp_first_date);

$i = 0;
while ($tmp_first_date < $end_date) {
    $start_weeks[$i]['text'] = ($i+1) .'. '. _("Semesterwoche") .' ('. _("ab") .' '. date("d.m.Y",$tmp_first_date).')';
    $start_weeks[$i]['selected'] = ($sem->getStartWeek() == $i);

    $i++;
    $tmp_first_date = mktime($date['hours'], $date['minutes'], $date['seconds'], $date['mon'], $date['mday'] + 7 * $i, $date['year']);
}
$dozenten = $sem->getMembers('dozent');

if ($perm->have_studip_perm("admin",$sem->getId())) {
    $adminList = AdminList::getInstance()->getSelectTemplate($sem->getId());
}

// template-like output
?>
<script>
jQuery(function () {
    STUDIP.RoomRequestDialog.reloadUrlOnClose = '<?= UrlHelper::getUrl()?>';
    STUDIP.BlockAppointmentsDialog.reloadUrlOnClose = '<?= UrlHelper::getUrl()?>';
});
</script>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td class="blank" width="100%" valign="top" style="padding-left: 8px">
            <table width="99%" border="0" cellpadding="2" cellspacing="0">

            <?php
                // show messages
                $messages = $sem->getStackedMessages();
                if ($messages || $pmessages) :
            ?>
            <tr>
                <td colspan="9">
            <?php
                foreach ($messages as $type => $message_data) :
                    echo MessageBox::$type( $message_data['title'], $message_data['details'] );
                endforeach;
                echo join("\n", $pmessages);
            ?>
                </td>
            </tr>
            <? endif; ?>

            <tr>
                <td colspan="9" class="blue_gradient">
                    <b><?=_("Regelmäßige Zeiten")?></b>
                </td>
            </tr>
            <tr>
                <td colspan="9" class="blank">
                    <? if (!$_LOCKED) { ?>
                        <form action="<?= URLHelper::getLink() ?>" method="post">
                        <?= CSRFProtection::tokenTag() ?>
                        <? } ?>
                        <?=_("Startsemester")?>:
                        <?
                            if ($perm->have_perm('tutor') && !$_LOCKED) {
                                echo "<select name=\"startSemester\">\n";
                                $all_semester = $semester->getAllSemesterData();
                                foreach ($all_semester as $val) {
                                    echo '<option value="'.$val['beginn'].'"';
                                    if ($sem->getStartSemester() == $val['beginn']) {
                                        echo ' selected';
                                    }
                                    echo '>'.$val['name']."</option>\n";
                                }
                                echo "</select>\n";
                            } else {
                                $all_semester = $semester->getAllSemesterData();
                                foreach ($all_semester as $val) {
                                    if ($sem->getStartSemester() == $val['beginn']) {
                                        echo $val["name"];
                                    }
                                }
                            }
                        ?>
                        , <?=_("Dauer")?>:
                        <? if (!$_LOCKED) { ?>
                        <select name="endSemester">
                            <option value="0"<?=($sem->getEndSemester() == 0) ? ' selected' : ''?>>1 <?=_("Semester")?></option>
                            <?
                            //if ($perm->have_perm("admin")) {      // admins or higher may do everything
                                foreach ($all_semester as $val) {
                                    if ($val['beginn'] > $sem->getStartSemester()) {        // can be removed, if we always need all Semesters
                                        echo '<option value="'.$val['beginn'].'"';
                                        if ($sem->getEndSemester() == $val['beginn']) {
                                            echo ' selected';
                                        }
                                        echo '>'.$val['name'].'</option>';
                                    }
                                }
                                ?>
                                <option value="-1"<?=($sem->getEndSemester() == -1) ? 'selected' : ''?>><?=_("unbegrenzt")?></option>
                        </select>

                        <?= Button::create(_('Übernehmen'), 'uebernehmen') ?>
                        <input type="hidden" name="cmd" value="selectSemester">
                        <? } else {
                            switch ($sem->getEndSemester()) {
                                case '0':
                                    echo _("1 Semester");
                                    break;

                                case '-1':
                                    echo _("unbegrenzt");
                                    break;

                                default:
                                    foreach ($all_semester as $val) {
                                        if ($val['beginn'] == $sem->getEndSemester()) {
                                            echo $val['beginn'];
                                        }
                                    }
                                    break;
                            }
                        }
                       ?>
                        </form>
                    </td>
                </tr>
                <?
                    //TODO: string representation should not be collected by a big array, but with the toString method of the CycleData-object
                    $cyclecount = $sem->getMetaDateCount();
                    $show_sorter = $cyclecount > 1 && Config::get()->ALLOW_METADATE_SORTING;
                    foreach ($sem->metadate->getCycles() as $metadate_id => $cycle) {        // cycle trough all CycleData objects
                        $tpl = $cycle_element = $cycle->toArray();
                        if (!$tpl['room'] = $sem->getDatesTemplate('dates/seminar_predominant_html', array('cycle_id' => $metadate_id))) {
                            $tpl['room'] = _("keiner");
                        }

                        /* get StatOfNotBookedRooms returns an array:
                         * open:            number of rooms with no booking
                         * all:                 number of singleDates, which can have a booking
                         * open_rooms:  array of singleDates which have no booking
                         */
                        $tpl['ausruf'] = $sem->getBookedRoomsTooltip($metadate_id);
                        $tpl['anfragen'] = $sem->getRequestsInfo($metadate_id);
                        $tpl['class'] = $sem->getCycleColorClass($metadate_id);

                        $tpl['md_id'] = $metadate_id;
                        $tpl['date'] = $cycle->toString('long');
                        $tpl['date_tooltip'] = $cycle->toString('full');
                        $tpl['mdDayNumber'] = $cycle_element['day'];
                        $tpl['mdStartHour'] = $cycle_element['start_hour'];
                        $tpl['mdEndHour'] = $cycle_element['end_hour'];
                        $tpl['mdStartMinute'] = $cycle_element['start_minute'];
                        $tpl['mdEndMinute'] = $cycle_element['end_minute'];
                        $tpl['mdDescription'] = htmlReady($cycle_element['desc']);
                        if ($request_id = RoomRequest::existsByCycle($metadate_id)) {
                            $tpl['room_request'] = RoomRequest::find($request_id);
                            $tpl['room_request_ausruf']  = _("Für diese Zeit existiert eine Raumanfrage:");
                            $tpl['room_request_ausruf'] .= "\n\n" . $tpl['room_request']->getInfo();
                            if ($tpl['room_request']->getStatus() == 'declined') {
                                $tpl['symbol'] = 'icons/16/red/exclaim.png';
                            } else {
                                $tpl['symbol'] = 'icons/16/grey/pause/date.png';
                            }
                        } else {
                            $tpl['room_request'] = false;
                        }
                        include('lib/raumzeit/templates/metadate.tpl');

                        if ($sd_open[$metadate_id]) {
                            $termine =& $sem->getSingleDatesForCycle($metadate_id);
                            ?>
                            <form action="<?= URLHelper::getLink() ?>" method="post" name="Formular">
                            <?= CSRFProtection::tokenTag() ?>
                            <input type="hidden" name="cycle_id" value="<?=$metadate_id?>">
                <tr>
                    <td align="center" colspan="9" class="steel1">
                        <table cellpadding="1" cellspacing="0" border="0" width="90%">
                            <?
                            $every2nd = 1;
                            $all_semester = $semester->getAllSemesterData();
                            $grenze = 0;
                            if (sizeof($termine) == 0) {
                                foreach ($all_semester as $val) {
                                    if ($val['beginn'] == $raumzeitFilter) {
                                        $sem_name = $val['name'];break;
                                    }
                                }
                                parse_msg('error§'.sprintf(_("Für das %s gibt es für diese regelmäßige Zeit keine Termine!"), '<b>'.$sem_name.'</b>').'§', '§', 'steel1');
                            } else foreach ($termine as $singledate_id => $val) {
                                if ( ($grenze == 0) || ($grenze < $val->getStartTime()) ) {
                                    foreach ($all_semester as $zwsem) {
                                        if ( ($zwsem['beginn'] < $val->getStartTime()) && ($zwsem['ende'] > $val->getStartTime()) ) {
                                            $grenze = $zwsem['ende'];
                                            ?>
                                            <tr>
                                                <td class="steelgraulight" align="center" colspan="9">
                                                    <b><?=$zwsem['name']?></b>
                                                </td>
                                            </tr>
                                            <?
                                        }
                                    }
                                }
                                // Template fuer einzelnes Datum
                                $tpl['checked'] = '';
                                $val->restore();
                                $tpl = getTemplateDataForSingleDate($val, $metadate_id);
                                $tpl['cycle_sd'] = TRUE;


                                if ($sd_open[$singledate_id] && (Request::option('open_close_id') == $singledate_id)) {
                                    include('lib/raumzeit/templates/openedsingledate.tpl');
                                } else {
                                    unset($sd_open[$singledate_id]);
                                    include('lib/raumzeit/templates/singledate.tpl');
                                }
                                // Ende Template einzelnes Datum
                            }
                            ?>
                        </table>
                    </td>
                </tr>
                <? if (sizeof($termine) > 0) : ?>
                <tr>
                    <td class="steel1" colspan="9" align="center">
                        <?
                            $tpl['width'] = '90%';
                            $tpl['cycle_id'] = $metadate_id;
                            include('lib/raumzeit/templates/actions.tpl');
                        ?>
                    </td>
                </tr>
                <?
                endif;
                } ?>
                </form>
            <? }

            if ($newCycle) {
            ?>
                <tr>
                    <?
                    if (isset($_REQUEST['day'])) {
                        $tpl['day'] = $_REQUEST['day'];
                    } else {
                        $tpl['day'] = 1;
                    }
                    $tpl['start_stunde'] = $_REQUEST['start_stunde'];
                    $tpl['start_minute'] = $_REQUEST['start_minute'];
                    $tpl['end_stunde'] = $_REQUEST['end_stunde'];
                    $tpl['end_minute'] = $_REQUEST['end_minute'];
                    include('lib/raumzeit/templates/addcycle.tpl')
                    ?>
                </tr>
            <?
                }
            ?>
                <? if (!$_LOCKED) { ?>
                <tr>
                    <td class="blank" colspan="9">
                        <br>
                        <?=_("Regelmäßigen Zeiteintrag")?>
                        <?= LinkButton::create(_('Hinzufügen'), URLHelper::getURL('', array('cmd' => 'addCycle')) . '#newCycle') ?>
                    </td>
                </tr>
                <? } ?>
                <tr>
                    <td colspan="9" class="blank">&nbsp;</td>
                </tr>
                <tr>
                    <td colspan="9" class="blue_gradient">
                        <a name="irregular_dates"></a>
                        <b><?=_("Unregelm&auml;&szlig;ige Termine/Blocktermine")?></b>
                    </td>
                </tr>
                <? if ($termine =& $sem->getSingleDates(true, true)) { ?>
                <tr>
                    <td align="left" colspan="9" class="steel1">
                        <form action="<?= URLHelper::getLink() ?>" method="post" name="Formular">
                        <?= CSRFProtection::tokenTag() ?>
                        <table cellpadding="1" cellspacing="0" border="0" width="100%">
                            <?
                            $count = 0;
                            $every2nd = 1;
                            $grenze = 0;
                            foreach ($termine as $key => $val) {
                                $tpl['checked'] = '';
                                $tpl = getTemplateDataForSingleDate($val);

                                if ( ($grenze == 0) || ($grenze < $val->getStartTime()) ) {
                                    foreach ($all_semester as $zwsem) {
                                        if ( ($zwsem['beginn'] < $val->getStartTime()) && ($zwsem['ende'] > $val->getStartTime()) ) {
                                            $grenze = $zwsem['ende'];
                                            ?>
                                            <tr>
                                                <td class="steelgraulight" align="center" colspan="9">
                                                    <b><?=$zwsem['name']?></b>
                                                </td>
                                            </tr>
                                            <?
                                        }
                                    }
                                }

                                if ($sd_open[$val->getSingleDateID()] && (Request::option('open_close_id') == $val->getSingleDateID())) {
                                    include('lib/raumzeit/templates/openedsingledate.tpl');
                                } else {
                                    unset($sd_open[$val->getSingleDateID()]);
                                    include('lib/raumzeit/templates/singledate.tpl');
                                }
                                $count++;
                            }
                            ?>
                        </table>
                <? } ?>
                <? if ($count) { ?>
                        <?
                            $tpl['width'] = '100%';
                            include('lib/raumzeit/templates/actions.tpl');
                        ?>
                        </form>
                    </td>
                </tr>
                <? } ?>


                <tr>
                    <td colspan="9" class="blank">&nbsp;</td>
                </tr>

                <? if (!$_LOCKED) { ?>
                <? if (isset($cmd) && ($cmd == 'createNewSingleDate')) {
                    if ($GLOBALS['RESOURCES_ENABLE_BOOKINGSTATUS_COLORING']) {
                        $tpl['class'] = 'steelred';
                    } else {
                        $tpl['class'] = 'printhead';
                    }

                    include('lib/raumzeit/templates/addsingledate.tpl');
                } else { ?>
                <tr>
                    <td>

                    </td>
                </tr>
                <tr>
                    <td colspan="9" class="blank">
                                               <?= sprintf (_('Einen neuen Termin %s oder mehrere Termine mit dem Blockveranstaltungsassistenten %s'),
                            LinkButton::create(_('Erstellen'), URLHelper::getURL('', array('cmd' => 'createNewSingleDate')) . '#newSingleDate'),
                            LinkButton::create(_('Anlegen'), 'javascript:STUDIP.BlockAppointmentsDialog.initialize("'.UrlHelper::getURL('dispatch.php/course/block_appointments').'")')); ?>
                    </td>
                </tr>
                <? } ?>
                <tr>
                    <td class="blank" colspan="9">&nbsp;</td>
                </tr>
                <?
                }

                if (!$_LOCKED && $RESOURCES_ENABLE && $RESOURCES_ALLOW_ROOM_REQUESTS) { ?>
                <tr>
                    <td colspan="9" class="blue_gradient">
                        <a name="irregular_dates"></a>
                        <b><?=_("Raum anfordern")?></b>
                    </td>
                </tr>
                <tr>
                    <td class="blank" colspan="9" style="padding-left: 6px">
                        <?=_("Hier können Sie für die gesamte Veranstaltung, also für alle regelmäßigen und unregelmäßigen Termine, eine Raumanfrage erstellen. Um für einen einzelnen Termin eine Raumanfrage zu erstellen, klappen Sie diesen auf und wählen dort \"Raumanfrage erstellen\"");?>
                    </td>
                </tr>
                <tr>
                    <td class="blank" colspan="9">&nbsp;</td>
                </tr>
                <tr>
                    <td class="blank" colspan="9">
                        <?
                        $request_status = $sem->getRoomRequestStatus();
                        if ($request_status && ($request_status == 'open' || $request_status == 'pending')) :
                            $req_info = $sem->getRoomRequestInfo();
                        ?>
                        <!-- the room-request has not yet been resolved -->
                        <?= MessageBox::info(_("Für diese Veranstaltung liegt eine noch offene Raumanfrage vor."), array(nl2br(htmlReady($req_info)))) ?>
                        <br>

                        <? elseif ($request_status && $request_status == 'declined') :
                            $req_info = $sem->getRoomRequestInfo();
                        ?>
                        <!-- the room-request has been declined -->
                        <?= MessageBox::info( _("Die Raumanfrage für diese Veranstaltung wurde abgelehnt!"), array(nl2br($req_info))) ?>
                        <br>
                        <? endif; ?>

                        <?= _("Raumanfrage") ?>
                            <? if ($request_status && $request_status == 'open') {
                            ?>
                                <?= Linkbutton::create(_('Bearbeiten'), URLHelper::getURL('dispatch.php/course/room_requests/edit/' . $sem->getId(), array('request_id' => RoomRequest::existsByCourse($id))), array('onclick' => "STUDIP.RoomRequestDialog.initialize(this.href.replace('edit','edit_dialog'));return false;")) ?>
                            <?
                            } else {
                            ?>
                                <?= Linkbutton::create(_('Erstellen'), URLHelper::getURL('dispatch.php/course/room_requests/edit/' . $sem->getId(), array('new_room_request_type' => 'course')), array('onclick' => "STUDIP.RoomRequestDialog.initialize(this.href.replace('edit','edit_dialog'));return false;")) ?>
                            <?
                            } ?>
                        <? if ($request_status && $request_status == 'open') { ?>
                        <?= _('oder') ?>
                        <?= LinkButton::create(_('Zurückziehen'), URLHelper::getURL('', array('cmd' => 'removeSeminarRequest'))) ?>
                        <? } ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="9" class="blank">&nbsp;</td>
                </tr>
            <? } ?>

            </table>
        </td>
        <td align="left" valign="top" class="blank">
                <?
                    // print info box:
                    // get template
                    $infobox_template = $GLOBALS['template_factory']->open('infobox/infobox_raumzeit');

                    // get a list of semesters (as display options)
                    $semester_selectionlist = raumzeit_get_semesters($sem, $semester, $_SESSION['raumzeitFilter']);

                    // fill attributes
                    $infobox_template->set_attribute('picture', 'infobox/schedules.jpg');
                    $infobox_template->set_attribute("selectionlist_title", "Semesterauswahl");
                    $infobox_template->set_attribute('selectionlist', $semester_selectionlist);
                    $infobox_template->set_attribute("adminList", $adminList);

                    // render template
                    echo $infobox_template->render();

                ?>
            </td>
        </tr>
</table>
<?

$sem->store();
include 'lib/include/html_end.inc.php';
page_close();
