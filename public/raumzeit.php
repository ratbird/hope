<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter005: TODO
# Lifter007: TODO
# Lifter003: TODO
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

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

$HELP_KEYWORD="Basis.VeranstaltungenVerwaltenAendernVonZeitenUndTerminen";

// -- here you have to put initialisations for the current page

if ($list) {
    URLHelper::removeLinkParam('seminar_id');
    unset($seminar_id);
}

if (isset($_REQUEST['seminar_id'])) {
    URLHelper::bindLinkParam('seminar_id', $seminar_id);
}

if (isset($seminar_id)) {
    $id = $seminar_id;
} else {
    $id = $SessSemName[1];
}

require_once ('lib/classes/Seminar.class.php');
require_once ('lib/raumzeit/raumzeit_functions.inc.php');
require_once ('lib/dates.inc.php');
require_once 'lib/admin_search.inc.php';

if ($RESOURCES_ENABLE) {
    include_once ($RELATIVE_PATH_RESOURCES."/lib/ResourceObject.class.php");
    include_once ($RELATIVE_PATH_RESOURCES."/lib/ResourcesUserRoomsList.class.php");
    include_once ($RELATIVE_PATH_RESOURCES."/lib/VeranstaltungResourcesAssign.class.php");
    include_once ($RELATIVE_PATH_RESOURCES."/lib/ResourceObjectPerms.class.php");
    $resList = new ResourcesUserRoomsList($user->id, TRUE, FALSE, TRUE);
}

$CURRENT_PAGE = _("Verwaltung von Zeiten und Raumangaben");
if (Request::get('section') == 'dates') {
    UrlHelper::bindLinkParam('section', $section);
    Navigation::activateItem('/course/admin/dates');
} else {
    Navigation::activateItem('/admin/course/dates');
}

// bind linkParams for chosen semester and opened dates
URLHelper::bindLinkParam('raumzeitFilter', $raumzeitFilter);
URLHelper::bindLinkParam('sd_open', $sd_open);

//Change header_line if open object
$header_line = getHeaderLine($id);
if ($header_line)
    $CURRENT_PAGE = $header_line." - ".$CURRENT_PAGE;

//Output starts here

include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   //hier wird der "Kopf" nachgeladen
include 'lib/include/admin_search_form.inc.php';

if (!$perm->have_studip_perm('tutor', $id)) {
    die;
}

unQuoteAll();

$sem = Seminar::GetInstance($id);
$sem->checkFilter();

$semester = new SemesterData();
$_LOCKED = FALSE;
if ($SEMINAR_LOCK_ENABLE) {
    require_once ('lib/classes/LockRules.class.php');
    $lockRule = new LockRules();
    $data = $lockRule->getSemLockRule($id);
    if (LockRules::Check($id, 'room_time')) {
        $_LOCKED = TRUE;
        $sem->createInfo(_("Diese Seite ist für die Bearbeitung gesperrt. Sie können die Daten einsehen, jedoch nicht verändern.")
            . ($data['description'] ? '<br>'.fixLinks($data['description']) : ''));
    }
}

// Workaround for multiple submit buttons
foreach ($_REQUEST as $key => $val) {
    if ( ($key[strlen($key)-2] == '_') && ($key[strlen($key)-1] == 'x') ) {
        $cmd = substr($key, 0, (strlen($key) - 2));
    }
}

// what to do with the text-field
if ($GLOBALS['RESOURCES_ENABLE'] && $resList->numberOfRooms()) {
    if ( (($_REQUEST['freeRoomText'] != '') && ($_REQUEST['room'] != 'nothing')) || (($_REQUEST['freeRoomText_sd'] != '') && ($_REQUEST['room_sd'] != 'nothing'))) {
        $sem->createError("Sie k&ouml;nnen nur eine freie Raumangabe machen, wenn sie \"keine Buchung, nur Textangabe\" ausw&auml;hlen!");
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
$sem->registerCommand('editSingleDate', 'raumzeit_editSingleDate');
$sem->registerCommand('editDeletedSingleDate', 'raumzeit_editDeletedSingleDate');
$sem->registerCommand('freeText', 'raumzeit_freeText');
$sem->registerCommand('removeRequest', 'raumzeit_removeRequest');
$sem->registerCommand('removeSeminarRequest', 'raumzeit_removeSeminarRequest');
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

// template-like output
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td class="blank" width="100%" valign="top">
            <table width="99%" border="0" cellpadding="2" cellspacing="0">

            <?php 
                // show messages
                if ($messages = $sem->getStackedMessages()) :
            ?>
            <tr>
                <td colspan="9">
            <?php
                foreach ($messages as $type => $message_data) :
                    echo MessageBox::$type( $message_data['title'], $message_data['details'] );
                endforeach;
            ?>
                </td>
            </tr>
            <? endif; ?>

            <tr>
                <td colspan="9" class="blue_gradient">
                    &nbsp;<B><?=_("Regelmäßige Zeiten")?></B>
                </td>
            </tr>
            <tr>
                <td colspan="9" class="blank">
                    <? if (!$_LOCKED) { ?>
                        <form action="<?= URLHelper::getLink() ?>" method="post">
                        <? } ?>
                        <FONT size="-1">
                        &nbsp;<?=_("Startsemester")?>:&nbsp;
                        <?
                            if ($perm->have_perm('tutor') && !$_LOCKED) {
                                echo "<SELECT name=\"startSemester\">\n";
                                $all_semester = $semester->getAllSemesterData();
                                foreach ($all_semester as $val) {
                                    echo '<OPTION value="'.$val['beginn'].'"';
                                    if ($sem->getStartSemester() == $val['beginn']) {
                                        echo ' selected';
                                    }
                                    echo '>'.$val['name']."</OPTION>\n";
                                }
                                echo "</SELECT>\n";
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
                        <SELECT name="endSemester">
                            <OPTION value="0"<?=($sem->getEndSemester() == 0) ? ' selected' : ''?>>1 <?=_("Semester")?></OPTION>
                            <?
                            //if ($perm->have_perm("admin")) {      // admins or higher may do everything
                                foreach ($all_semester as $val) {
                                    if ($val['beginn'] > $sem->getStartSemester()) {        // can be removed, if we always need all Semesters
                                        echo '<OPTION value="'.$val['beginn'].'"';
                                        if ($sem->getEndSemester() == $val['beginn']) {
                                            echo ' selected';
                                        }
                                        echo '>'.$val['name'].'</OPTION>';
                                    }
                                }
                                ?>
                                <OPTION value="-1"<?=($sem->getEndSemester() == -1) ? 'selected' : ''?>><?=_("unbegrenzt")?></OPTION>
                                <?
                            /*} else {      // dozent or tutor may only selecte a duration of one or two semesters or what admin has choosen
                                $sem2 = '';
                                foreach ($all_semester as $val) {
                                    if (($sem2 == '') && ($val['beginn'] > $sem->getStartSemester())) {
                                        echo '<OPTION value="'.$val['beginn'].'"'.(($sem->getEndSemester() == $val['beginn']) ? ' selected' : '').'>2 '._("Semester").'</OPTION>';
                                        $sem2 = $val['beginn'];
                                    }
                                    if ( ($val['beginn'] == $sem->getEndSemester() && ($sem2 != $val['beginn']))) {
                                        echo '<OPTION value="'.$val['beginn'].'"'.(($sem->getEndSemester() == $val['beginn']) ? ' selected' : '').'>'.$val['name'].'</OPTION>';
                                    }
                                }
                                if ($sem->getEndSemester() == -1) {
                                    ?>
                                    <OPTION value="-1" selected>unbegrenzt</OPTION>
                                    <?
                                }
                            }*/
                            ?>
                        </SELECT>
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
                        } ?>
                        &nbsp;&nbsp;
                        <br />
                        <?=_("Turnus")?>:
                        <? if (!$_LOCKED) { ?>
                        <SELECT name="turnus">
                            <OPTION value="0"<?=$sem->getTurnus() ? '' : 'selected'?>><?=_("w&ouml;chentlich");?></OPTION>
                            <OPTION value="1"<?=$sem->getTurnus() ? 'selected' : ''?>><?=_("zweiw&ouml;chentlich")?></OPTION>
                        </SELECT>
                        <? } else {
                            echo (!$sem->getTurnus()) ? _("w&ouml;chentlich") : _("zweiw&ouml;chentlich");
                        } ?>
                        &nbsp;&nbsp;
                        <?=_("beginnt in der")?>:
                        <? if (!$_LOCKED) { ?>
                        <select name="startWeek">
                        <?
                            foreach ($start_weeks as $value => $data) :

                                echo '<option value="'.$value.'"';
                                if ($data['selected']) echo ' selected="selected"';
                                echo '>'.$data['text'].'</option>', "\n";

                            endforeach;
                        ?>
                        </SELECT>
                        </FONT>
                        &nbsp;&nbsp;
                        <input type="image" <?=makebutton('uebernehmen', 'src')?> align="absmiddle">
                        <input type="hidden" name="cmd" value="selectSemester">
                        </form>
                        <? } else {
                            echo ($sem->getStartWeek() + 1) .'. '. _("Semesterwoche");
                        } ?>
                    </TD>
                </TR>
                <?
                $turnus = $sem->getFormattedTurnusDates();      // string representation of all CycleData-objects is retrieved as an associative array: key: CycleDataID, val: string
                    //TODO: string representation should not be collected by a big array, but with the toString method of the CycleData-object
                    foreach ($sem->metadate->getCycleData() as $metadate_id => $cycle_element) {        // cycle trough all CycleData objects
                        if (!$tpl['room'] = $sem->getFormattedPredominantRooms($metadate_id)) {     // getPredominantRoom returns the predominant booked room
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
                        $tpl['date'] = $turnus[$metadate_id];
                        $tpl['mdDayNumber'] = $cycle_element['day'];
                        $tpl['mdStartHour'] = $cycle_element['start_hour'];
                        $tpl['mdEndHour'] = $cycle_element['end_hour'];
                        $tpl['mdStartMinute'] = $cycle_element['start_minute'];
                        $tpl['mdEndMinute'] = $cycle_element['end_minute'];
                        $tpl['mdDescription'] = htmlReady($cycle_element['desc']);

                        include('lib/raumzeit/templates/metadate.tpl');

                        if ($sd_open[$metadate_id]) {
                            $termine =& $sem->getSingleDatesForCycle($metadate_id);
                            ?>
                            <FORM action="<?= URLHelper::getLink() ?>" method="post" name="Formular">
                            <INPUT type="hidden" name="cycle_id" value="<?=$metadate_id?>">
                <TR>
                    <TD align="center" colspan="9" class="steel1">
                        <TABLE cellpadding="1" cellspacing="0" border="0" width="90%">
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
                                            <TR>
                                                <TD class="steelgraulight" align="center" colspan="9">
                                                    <B><?=$zwsem['name']?></B>
                                                </TD>
                                            </TR>
                                            <?
                                        }
                                    }
                                }
                                // Template fuer einzelnes Datum
                                $tpl['checked'] = '';
                                $tpl = getTemplateDataForSingleDate($val, $metadate_id);
                                $tpl['cycle_sd'] = TRUE;

                                if ($sd_open[$singledate_id] && ($open_close_id == $singledate_id)) {
                                    include('lib/raumzeit/templates/openedsingledate.tpl');
                                } else {
                                    unset($sd_open[$singledate_id]);
                                    include('lib/raumzeit/templates/singledate.tpl');
                                }
                                // Ende Template einzelnes Datum
                            }
                            ?>
                        </TABLE>
                    </TD>
                </TR>
                <? if (sizeof($termine) > 0) : ?>
                <TR>
                    <TD class="steel1" colspan="9" align="center">
                        <?
                            $tpl['width'] = '90%';
                            $tpl['cycle_id'] = $metadate_id;
                            include('lib/raumzeit/templates/actions.tpl');
                        ?>
                    </TD>
                </TR>
                <?
                endif;
                        }
                        echo "</form>";
                    }

                if ($newCycle) {
            ?>
                <TR>
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
                </TR>
            <?
                }
            ?>
                <? if (!$_LOCKED) { ?>
                <TR>
                    <TD class="blank" colspan="9">
                        <br />
                        <font size="-1">
                            &nbsp;&nbsp;
                            <?=_("Regelmäßigen Zeiteintrag")?>
                            <a href="<?= URLHelper::getLink('?cmd=addCycle#newCycle') ?>">
                                <img <?=makebutton('hinzufuegen', 'src')?> border="0" align="absmiddle">
                            </a>
                        </font>
                    </TD>
                </TR>
                <? } ?>
                <TR>
                    <TD colspan="9" class="blank">&nbsp;</TD>
                </TR>
                <TR>
                    <TD colspan="9" class="blue_gradient">
                        <a name="irregular_dates"></a>
                        &nbsp;<B><?=_("Unregelm&auml;&szlig;ige Termine/Blocktermine")?></B>
                    </TD>
                </TR>
                <? if ($termine =& $sem->getSingleDates(true, true)) { ?>
                <TR>
                    <TD align="left" colspan="9" class="steel1">
                        <form action="<?= URLHelper::getLink() ?>" method="post" name="Formular">
                        <TABLE cellpadding="1" cellspacing="0" border="0" width="100%">
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
                                            <TR>
                                                <TD class="steelgraulight" align="center" colspan="9">
                                                    <B><?=$zwsem['name']?></B>
                                                </TD>
                                            </TR>
                                            <?
                                        }
                                    }
                                }

                                if ($sd_open[$val->getSingleDateID()] && ($open_close_id == $val->getSingleDateID())) {
                                    include('lib/raumzeit/templates/openedsingledate.tpl');
                                } else {
                                    unset($sd_open[$val->getSingleDateID()]);
                                    include('lib/raumzeit/templates/singledate.tpl');
                                }
                                $count++;
                            }
                            ?>
                        </TABLE>
                <? } ?>
                <? if ($count) { ?>
                        <?
                            $tpl['width'] = '100%';
                            include('lib/raumzeit/templates/actions.tpl');
                        ?>
                        </form>
                    </TD>
                </TR>
                <? } ?>


                <tr>
                    <td colspan="9" class="blank">&nbsp;</td>
                </tr>

                <? if (!$_LOCKED) { ?>
                <TR>
                    <TD>
                    <SCRIPT type ="text/javascript">
                    function block_fenster () {
                        f1 = window.open("blockveranstaltungs_assistent.php?seminar_id=<?=$id?>", "Zweitfenster", "width=550,height=600,toolbar=no, menubar=no, scrollbars=yes");
                        f1.focus();
                    }
                    </SCRIPT>
                        <FONT size="-1">
                            &nbsp;<?=_("Blockveranstaltungstermine")?>
                        </FONT>
                         <a href="javascript:window.block_fenster()"><?=makebutton("anlegen")?></a>
                    </TD>
                </TR>
                <? if (isset($cmd) && ($cmd == 'createNewSingleDate')) {
                    if ($GLOBALS['RESOURCES_ENABLE_BOOKINGSTATUS_COLORING']) {
                        $tpl['class'] = 'steelred';
                    } else {
                        $tpl['class'] = 'printhead';
                    }

                    include('lib/raumzeit/templates/addsingledate.tpl');
                } else { ?>
                <TR>
                    <TD colspan="9" class="blank">
                        <FONT size="-1">
                            &nbsp;einen neuen Termin
                            <a href="<?= URLHelper::getLink('?cmd=createNewSingleDate#newSingleDate') ?>">
                                <IMG <?=makebutton('erstellen', 'src')?> align="absmiddle" border="0">
                            </A>
                        </FONT>
                    </TD>
                </TR>
                <? } ?>
                <tr>
                    <td class="blank" colspan="9">&nbsp;</td>
                </tr>
                <?
                }

                if (!$_LOCKED && $RESOURCES_ENABLE && $RESOURCES_ALLOW_ROOM_REQUESTS) { ?>
                <tr>
                    <td colspan="9" class="blue_gradient">
                        <a name="irregular_dates"/></a>
                        &nbsp;<b><?=_("Raum anfordern")?></b>
                    </td>
                </tr>
                <tr>
                    <td class="blank" colspan="9" style="padding-left: 6px">
                        <font size="-1">
                            <?=_("Hier können Sie für die gesamte Veranstaltung, also für alle regelmäßigen und unregelmäßigen Termine, eine Raumanfrage erstellen. Um für einen einzelnen Termin eine Raumanfrage zu erstellen, klappen Sie diesen auf und wählen dort \"Raumanfrage erstellen\"");?>
                        </font>
                    </td>
                </tr>
                <tr>
                    <td class="blank" colspan="9">
                        &nbsp;
                    </td>
                </tr>
                <tr>
                    <td class="blank" colspan="9">
                        <?
                        $request_status = $sem->getRoomRequestStatus();
                        if ($request_status && ($request_status == 'open' || $request_status == 'pending')) :
                            $req_info = $sem->getRoomRequestInfo();
                        ?>
                        <!-- the room-request has not yet been resolved -->
                        <?= MessageBox::info(_("Für diese Veranstaltung liegt eine noch offene Raumanfrage vor."), array(nl2br($req_info))) ?>
                        </div>
                        <br />

                        <? elseif ($request_status && $request_status == 'declined') :
                            $req_info = $sem->getRoomRequestInfo();
                        ?>
                        <!-- the room-request has been declined -->
                        <?= MessageBox::info( _("Die Raumanfrage für diese Veranstaltung wurde abgelehnt!"), array(nl2br($req_info))) ?>
                        <br />
                        <? endif; ?>

                        <font size="-1">
                            &nbsp;Raumanfrage
                            <a href="<?= URLHelper::getLink('admin_room_requests.php?seminar_id='. $id) ?>">
                                <? if ($request_status && $request_status == 'open') {
                                ?>
                                    <img <?=makebutton('bearbeiten', 'src')?> align="absmiddle" border="0">
                                <?
                                } else {
                                ?>
                                    <img <?=makebutton('erstellen', 'src')?> align="absmiddle" border="0">
                                <?
                                } ?>
                            </A>
                            <? if ($request_status && $request_status == 'open') { ?>
                            &nbsp;oder&nbsp;
                            <a href="<?= URLHelper::getLink('?cmd=removeSeminarRequest') ?>">
                                <img <?=makebutton('zurueckziehen', 'src')?> align="absmiddle" border="0">
                            </A>
                        </FONT>
                        <? } ?>
                    </TD>
                </TR>
                <TR>
                    <TD colspan="9" class="blank">&nbsp;</TD>
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
                    $semester_selectionlist = raumzeit_get_semesters($sem, $semester, $raumzeitFilter);

                    // fill attributes
                    $infobox_template->set_attribute('picture', 'schedules.jpg');
                    $infobox_template->set_attribute("selectionlist_title", "Semesterauswahl"); 
                    $infobox_template->set_attribute('selectionlist', $semester_selectionlist);    

                    // render template
                    echo $infobox_template->render();

                ?>
            </td>
        </tr>
</table>
<?
if ($_REQUEST['open_close_id']) {
    echo "\n", '<script language="javascript">new Effect.Highlight(\''.$_REQUEST['open_close_id'].'\', {duration:3})</script>', "\n";
}
$sem->store();
include 'lib/include/html_end.inc.php';
page_close();
