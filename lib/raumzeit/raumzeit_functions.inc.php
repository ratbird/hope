<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
raumzeit_functions.inc.php
Helper functions for the "RaumZeit"-pages
Copyright (C) 2005-2007 Till Gl�ggler <tgloeggl@uos.de>

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

    $tpl['cycle_id'] = $cycle_id;                           // CycleData-ID (entspricht einer einzelnen regelm��igen Veranstaltungszeit
    $tpl['date'] = $val->toString();    // Text-String f�r Datum
    $tpl['class'] = 'content_title_green';                           // Standardklasse
    $tpl['sd_id'] = $val->getSingleDateID();    // Die ID des aktuellen Einzeltermins (kann an CycleData oder Seminar h�ngen)
    $tpl['type'] = $val->getDateType();
    $tpl['art'] = $val->getTypeName();
    $tpl['freeRoomText'] = htmlReady($val->getFreeRoomText());
    $tpl['comment'] = $val->getComment();
    $tpl['start_time'] = $val->getStartTime();

    /* css-Klasse und deleted-Status f�r das Template festlegen,
   * je nachdem ob es sich um einen gel�schten Termin handelt oder nicht */
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

    // entscheidet, ob der aktuelle Termin ausgew�hlt ist oder nicht
    if (Request::option('cycle_id') == $cycle_id) {
        $tpl['checked'] = in_array($val->getSingleDateId(), Request::optionArray('singledate'));
    } else if ($cycle_id != '') {
        if ($val->getStartTime() >= time()) {
            $tpl['checked'] = true;
        }
    }

    /* css-Klasse ausw�hlen, sowie Template-Feld f�r den Raum mit Text f�llen */
    if ($GLOBALS['RESOURCES_ENABLE']) {
        if ($val->getResourceID()) {
            $resObj = ResourceObject::Factory($val->getResourceID());
            $tpl['room']        = _("Raum: ");
            $tpl['room']       .= $resObj->getFormattedLink(TRUE, TRUE, TRUE, 'view_schedule', 'no_nav', $val->getStartTime());
            $tpl['class']       = 'content_title_green';
            $tpl['resource_id'] = $val->getResourceID();
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
                    $tpl['room'] = '('._('f�llt aus').')';
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

    /* F�llt die Variablen f�r Edit-Felder */
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
        $tpl['ausruf']  = _("F�r diesen Termin existiert eine Raumanfrage:");
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

    // fertiges Template-Array zur�ckgeben
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
    $turnus = $sem->getFormattedTurnusDates();

    $termine = array();
    foreach ($sem->metadate->cycles as $metadate_id => $val) {
        $termine = array_merge($termine, $sem->getSingleDatesForCycle($metadate_id));
    }

    $termine = array_merge($termine, $sem->getSingleDates(true, false, true));
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

        $zw = explode('�', $msg);

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

/**
 * @param string $comment
 * @param array $dates SingleDate
 */
function raumzeit_send_cancel_message($comment, $dates)
{
    if (!is_array($dates)) {
        $dates = array($dates);
    }
    $course = Course::find($dates[0]->range_id);
    if ($course) {
        $subject = sprintf(_("[%s] Terminausfall"), $course->name);
        $recipients = $course->members->pluck('username');
        $lecturers = $course->members->findBy('status', 'dozent')->pluck('nachname');
        $message = sprintf(_("In der Veranstaltung %s f�llt der/die folgende(n) Termine aus:"),
                 $course->name . ' ('. join(',', $lecturers) .') ' . $course->start_semester->name);
        $message .= "\n\n- ";
        $message .= join("\n- " , array_map(function($a) {return $a->toString();}, $dates));
        if ($comment) {
            $message .= "\n\n" . $comment;
        }
        $msg = new messaging();
        return $msg->insert_message($message, $recipients, '____%system%____', '', '', '', '', $subject, true);
    }
    
}
