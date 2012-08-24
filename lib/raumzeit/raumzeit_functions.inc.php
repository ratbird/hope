<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
raumzeit_functions.inc.php
Helper functions for the "RaumZeit"-pages
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

function getTemplateDataForSingleDate($val, $cycle_id = '') {
    global $every2nd, $choosen, $id, $showSpecialDays, $rz_switcher;

    if (!isset($rz_switcher)) {
        $rz_switcher = 1;
    }

    if (!isset($showSpecialDays)) $showSpecialDays = TRUE;
    $every2nd = 1 - $every2nd;

    $tpl['cycle_id'] = $cycle_id;                           // CycleData-ID (entspricht einer einzelnen regelmäßigen Veranstaltungszeit
    $tpl['date'] = $val->toString();    // Text-String für Datum
    $tpl['class'] = 'content_title_green';                           // Standardklasse
    $tpl['sd_id'] = $val->getSingleDateID();    // Die ID des aktuellen Einzeltermins (kann an CycleData oder Seminar hängen)
    $tpl['type'] = $val->getDateType();
    $tpl['art'] = $val->getTypeName();
    $tpl['freeRoomText'] = htmlReady($val->getFreeRoomText());
    $tpl['comment'] = htmlReady($val->getComment());
    $tpl['start_time'] = $val->getStartTime();

    /* css-Klasse und deleted-Status für das Template festlegen,
   * je nachdem ob es sich um einen gelöschten Termin handelt oder nicht */
    if ($val->isExTermin()) {
        $tpl['deleted'] = true;
        $tpl['class'] = 'content_title_red';
    } else {
        $tpl['deleted'] = false;
        $tpl['class'] = 'content_title_green';
    }

    /* Aging */
    $timecolor = '#BBBBBB';
    if ( $val->getChdate() >  (time() - 86400)) {
        $timecolor = '#FF0000';
    } else if ($val->getChDate() > 0) {
        $timediff = (int) log((time() - $val->getChDate()) / 86400 + 1) * 15;
        if ($timediff >= 68)
            $timediff = 68;
        $red = dechex(255 - $timediff);
        $other = dechex(119 + $timediff);
        $timecolor= "#" . $red . $other . $other;
    }

    $tpl['aging_color'] = $timecolor;

    /* entscheidet, ob der aktuelle Termin ausgewählt ist oder nicht,
   * je nachdem, welche Auswahlart aktiviert wurde */
    $tpl['checked'] = '';

    if (Request::option('cycle_id') == $cycle_id) {
        switch (Request::option('checkboxActionCmd')) {
            case 'chooseAll':
                $tpl['checked'] = 'checked';
                break;
            case 'chooseNone':
                $tpl['checked'] = '';
                break;
            case 'invert':
                if ($choosen[$val->getTerminID()]) {
                    $tpl['checked'] = '';
                } else {
                    $tpl['checked'] = 'checked';
                }
                break;
            case 'deleteChoosen':
                break;
            case 'deleteAll':
                break;
            case 'chooseEvery2nd':
                if ($every2nd) {
                    $tpl['checked'] = 'checked';
                } else {
                    $tpl['checked'] = '';
                }
                break;
        }
    } else if ($cycle_id != '') {
        if ($val->getStartTime() >= time()) {
            $tpl['checked'] = 'checked';
        }
    }

    /* css-Klasse auswählen, sowie Template-Feld für den Raum mit Text füllen */
    if ($GLOBALS['RESOURCES_ENABLE']) {
        if ($val->getResourceID()) {
            $resObj = ResourceObject::Factory($val->getResourceID());
            $tpl['room'] = _("Raum: ");
            $tpl['room'] .= $resObj->getFormattedLink(TRUE, TRUE, TRUE);
            $tpl['class'] = 'content_title_green';
        } else {
            if ($GLOBALS['RESOURCES_SHOW_ROOM_NOT_BOOKED_HINT']) {
                $tpl['room'] = '('._("kein gebuchter Raum").')';
            } else {
                $tpl['room'] = _("keine Raumangabe");
            }
            if ($val->isExTermin()) {
                if ($name = $val->isHoliday()) {
                    $tpl['room'] = '('._($name).')';
                } else {
                    $tpl['room'] = '('._("wurde gel&ouml;scht").')';
                }
            } else {
                if ($val->getFreeRoomText()) {
                    $tpl['room'] = '('.htmlReady($val->getFreeRoomText()).')';
                }
                if (($name = $val->isHoliday()) && $showSpecialDays) {
                    $tpl['room'] .= '&nbsp;('._($name).')';
                }
            }
            $tpl['class'] = 'content_title_red';
        }
    } else {
        $tpl['room'] = '';
        $tpl['class'] = 'printhead';
        if ($val->getFreeRoomText()) {
            $tpl['room'] = '('.htmlReady($val->getFreeRoomText()).')';
        }
        if (($name = $val->isHoliday()) && $showSpecialDays) {
            $tpl['room'] .= '&nbsp;('._($name).')';
        }

    }

    if (!$GLOBALS['RESOURCES_ENABLE_BOOKINGSTATUS_COLORING']) {
        $tpl['class'] = 'printhead';
    }

    /* Füllt die Variablen für Edit-Felder */
    $tpl['day'] = date('d',$val->getStartTime());
    $tpl['month'] = date('m',$val->getStartTime());
    $tpl['year'] = date('Y',$val->getStartTime());
    $tpl['start_stunde'] = date('H',$val->getStartTime());
    $tpl['start_minute'] = date('i',$val->getStartTime());
    $tpl['end_stunde'] = date('H',$val->getEndTime());
    $tpl['end_minute'] = date('i',$val->getEndTime());
    $tpl['related_persons'] = $val->getRelatedPersons();

    if ($request = RoomRequest::findByDate($val->getSingleDateID())) {
        $tpl['room_request'] = $request;
        $tpl['ausruf']  = _("Für diesen Termin existiert eine Raumanfrage:");
        $tpl['ausruf'] .= "\n\n" . $request->getInfo();
        $request_status = $request->getStatus();
        if ($request_status == 'declined') {
            $tpl['symbol'] = 'icons/16/red/exclaim.png';
        } elseif ($request_status == 'closed') {
            $tpl['symbol'] = 'icons/16/grey/accept.png';
        } else {
            $tpl['symbol'] = 'icons/16/grey/pause/date.png';
        }
    } else {
        $tpl['room_request'] = false;
    }

    $tpl['seminar_id'] = $id;

    // fertiges Template-Array zurückgeben
    return $tpl;
}

/*
 * used by Seminar.class.php
 *
 * user defined sort function for issues
 */
function myIssueSort($a, $b) {
    if ($a->getPriority() == $b->getPriority()) {
        return 0;
    }
    return ($a->getPriority() < $b->getPriority()) ? -1 : 1;
}

function sort_termine($a, $b) {
    if ($a->getStartTime() == $b->getStartTime()) return 0;
    if ($a->getStartTime() > $b->getStartTime()) {
        return 1;
    } else {
        return -1;
    }
}

function getAllSortedSingleDates(&$sem) {
    define('FILTER', 'TRUE');
    define('NO_FILTER', 'FALSE');

    $turnus = $sem->getFormattedTurnusDates();

    $termine = array();
    foreach ($sem->metadate->cycles as $metadate_id => $val) {
        $termine = array_merge($termine, $sem->getSingleDatesForCycle($metadate_id));
    }

    $termine = array_merge($termine, $sem->getSingleDates(FILTER));
    uasort ($termine, 'sort_termine');

    return $termine;
}

function getFilterForSemester($semester_id) {
    $semester = new SemesterData();
    if ($val = $semester->getSemesterData($semester_id)) {
        return array('filterStart' => $val['beginn'], 'filterEnd' => $val['ende']);
    } else {
        return FALSE;
    }
}

function raumzeit_parse_messages($msgs) {
    $first = true;
    foreach ($msgs as $msg) {
        if ($first) {
            $meldungen['kategorie'] = _("Statusmeldungen:");
        }

        $zw = explode('§', $msg);

        switch ($zw[0]) {
            case 'info':
                $pic = 'icons/16/grey/exclaim.png';
                $color = '#000000';
                break;

            case 'error':
                $pic = 'icons/16/red/decline.png';
                $color = '#FF2020';
                break;

            case 'msg':
                $pic = 'icons/16/green/accept.png';
                $color = '#008000';
                break;
        }

        $meldungen['eintrag'][] = array (
                'icon' => $pic,
                'text' => '<font color="'.$color.'">'.$zw[1].'</font>'
                );

        $first = false;
    }

    return $meldungen;
}

function raumzeit_get_semesters(&$sem, &$semester, $filter) {
    // this function works like raumzeit_get_semester_chooser() but it
    // returns a data structure for a selectionlist template instead of html code

    $all_semester = $semester->getAllSemesterData();
    $passed = false;
    $semester_chooser['all'] = _("Alle Semester");
    foreach ($all_semester as $val) {
        if ($sem->getStartSemester() <= $val['vorles_beginn']) $passed = true;
        if ($passed && ($sem->getEndSemesterVorlesEnde() >= $val['vorles_ende'])) {
            $semester_chooser[$val['beginn']] = $val['name'];
        }
    }

    if (sizeof($semester_chooser) == 2 && !$sem->hasDatesOutOfDuration(true)) {
        unset($semester_chooser['all']);
        foreach ($semester_chooser as $beginn => $trash) {
            $_SESSION['raumzeitFilter'] = $beginn;
            $filter = $beginn;
            $selected = $beginn;
            break;
        }
    }

    $selected = $filter;

    $i = 0;
    foreach ($semester_chooser as $key => $val) {

        // add text and link for each semester
        $selectionlist[$i]["url"]  = '?cmd=applyFilter&newFilter='.$key;
        $selectionlist[$i]["value"]  = $key;
        $selectionlist[$i]["linktext"] = $val;

        // set "selected" status
        if ($selected == $key) {
            $selectionlist[$i]["is_selected"] = true;
        } else {
            $selectionlist[$i]["is_selected"] = false;
        }
        $i++;
    }

    return $selectionlist;
}
