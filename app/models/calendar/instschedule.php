<?php
# Lifter010: TODO

/*
 * Copyright (C) 2009-2010 - Till Glöggler <tgloeggl@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once('lib/calendar/CalendarColumn.class.php');
define('DEFAULT_COLOR_SEM', $GLOBALS['PERS_TERMIN_KAT'][2]['color']);
define('DEFAULT_COLOR_NEW', $GLOBALS['PERS_TERMIN_KAT'][3]['color']);
define('DEFAULT_COLOR_VIRTUAL', $GLOBALS['PERS_TERMIN_KAT'][1]['color']);

/**
 * Pseudo-namespace containing helper methods for the calendar of institutes.
 *
 * @since      2.0
 */
class CalendarInstscheduleModel
{

    /**
     * Returns an schedule entry of a course
     *
     * @param string  the ID of the course
     * @param string  the ID of the user
     * @param string  optional; if given, specifies the ID of the entry
     * @return array  an array containing the properties of the entry
     */
    static function getSeminarEntry($seminar_id, $user_id, $cycle_id = false)
    {
        $ret = array();

        $sem = new Seminar($seminar_id);
        foreach ($sem->getCycles() as $cycle) {
            if (!$cycle_id || $cycle->getMetaDateID() == $cycle_id) {
                $entry = array();

                $entry['id'] = $seminar_id;
                $entry['cycle_id'] = $cycle->getMetaDateId();
                $entry['start_formatted'] = sprintf("%02d", $cycle->getStartStunde()) .':'
                    . sprintf("%02d", $cycle->getStartMinute());
                $entry['end_formatted'] = sprintf("%02d", $cycle->getEndStunde()) .':'
                    . sprintf("%02d", $cycle->getEndMinute());

                $entry['start']   = ((int)$cycle->getStartStunde() * 100) + ($cycle->getStartMinute());
                $entry['end']     = ((int)$cycle->getEndStunde() * 100) + ($cycle->getEndMinute());
                $entry['day']     = $cycle->getDay();
                $entry['content'] = $sem->getNumber() . ' ' . $sem->getName();
                $entry['url']     = UrlHelper::getLink('dispatch.php/calendar/instschedule/entry/' . $seminar_id
                                  . '/' . $cycle->getMetaDateId());
                $entry['onClick'] = "STUDIP.Instschedule.showSeminarDetails('$seminar_id', '"
                                  . $cycle->getMetaDateId() ."'); return false;";

                $entry['title']   = '';
                $ret[] = $entry;
            }
        }

        return $ret;
    }


    /**
     * Returns the schedule entries of the specified institute
     *
     * @param string  the ID of the user
     * @param array   an array containing the "beginn" of the semester
     * @param int     the start hour
     * @param int     the end hour
     * @param string  the ID of the institute
     * @return array  an array containing the entries
     */
    static function getInstituteEntries($controller, $user_id, $semester, $start_hour, $end_hour, $institute_id)
    {
        $day_names  = array(_("Montag"),_("Dienstag"),_("Mittwoch"),_("Donnerstag"),_("Freitag"),_("Samstag"),_("Sonntag"));

        // fetch seminar-entries, show invisible seminars if the user has enough perms
        $visibility_perms = $GLOBALS['perm']->have_perm(get_config('SEM_VISIBILITY_PERM'));
        $stmt = DBManager::get()->prepare("SELECT * FROM seminare as s
            WHERE Institut_id = ? AND (start_time = ?
                OR (start_time < ? AND duration_time = -1)
                OR (start_time + duration_time >= ?)) "
                . (!$visibility_perms ? " AND s.visible='1'" : ""));

        $stmt->execute(array($institute_id, $semester['beginn'], $semester['beginn'], $semester['beginn']));

        while ($entry = $stmt->fetch()) {
            $seminars[$entry['Seminar_id']] = $entry;
        }
        
        if (is_array($seminars)) foreach ($seminars as $data) {
            $entries = self::getSeminarEntry($data['Seminar_id'], $user_id);

            foreach ($entries as $entry) {
                unset($entry['url']);
                $entry['onClick'] = 'STUDIP.Instschedule.showInstituteDetails(this); return false;';
                $entry['visible'] = 1;

                if (($entry['start'] >= $start_hour * 100 && $entry['start'] <= $end_hour * 100
                    || $entry['end'] >= $start_hour * 100 && $entry['end'] <= $end_hour * 100)) {

                    $entry['color'] = DEFAULT_COLOR_SEM;

                    $day_number = ($entry['day']-1) % 7;
                    if (!isset($ret[$day_number])) {
                        $ret[$day_number] = CalendarColumn::create($day_number);
                        $ret[$day_number]->setTitle($day_names[$day_number]);
                        $ret[$day_number]->setURL($controller->url_for('calendar/instschedule/index/'. $day_number));
                    }
                    $ret[$day_number]->addEntry($entry);
                }
            }
        }

        return $ret;
    }
}
