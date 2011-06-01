<?php
# Lifter010: TODO

/*
 * This class is the model for the institute-calendar for seminars
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Till Glöggler <tgloeggl@uos.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

require_once('lib/calendar/CalendarColumn.class.php');
require_once('app/models/calendar/schedule.php');
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
     * @param string  $seminar_id  the ID of the course
     * @param string  $user_id     the ID of the user
     * @param string  $cycle_id    optional; if given, specifies the ID of the entry
     * @return array  an array containing the properties of the entry
     */
    static function getSeminarEntry($seminar_id, $user_id, $cycle_id = false)
    {
        $ret = array();

        $sem = Seminar::getInstance($seminar_id);
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
                $entry['onClick'] = "function(id) { STUDIP.Instschedule.showSeminarDetails('$seminar_id', '"
                                  . $cycle->getMetaDateId() ."'); }";

                $entry['title']   = '';
                $ret[] = $entry;
            }
        }

        return $ret;
    }


    /**
     * Returns an array of CalendarColumn's, containing the seminar-entries
     * for the passed user (in the passed semester belonging to the passed institute)
     * The start- and end-hour are used to constrain the returned
     * entries to the passed time-period. The passed days constrain the entries
     * to these.
     *
     * @param string  $user_id       the ID of the user
     * @param array   $semester      an array containing the "beginn" of the semester
     * @param int     $start_hour    the start hour
     * @param int     $end_hour      the end hour
     * @param string  $institute_id  the ID of the institute
     * @param array   $days          the days to be displayed
     * @return array  an array containing the entries
     */
    static function getInstituteEntries($user_id, $semester, $start_hour, $end_hour, $institute_id, $days)
    {
        $day_names  = array(_("Montag"),_("Dienstag"),_("Mittwoch"),_("Donnerstag"),_("Freitag"),_("Samstag"),_("Sonntag"));

        // fetch seminar-entries, show invisible seminars if the user has enough perms
        $visibility_perms = $GLOBALS['perm']->have_perm(get_config('SEM_VISIBILITY_PERM'));
        $stmt = DBManager::get()->prepare("SELECT * FROM seminare
            WHERE Institut_id = :institute AND (start_time = :begin
                OR (start_time < :begin AND duration_time = -1)
                OR (start_time + duration_time >= :begin AND start_time <= :begin)) "
                . (!$visibility_perms ? " AND visible='1'" : ""));

        $stmt->bindParam(':begin', $semester['beginn']);
        $stmt->bindParam(':institute', $institute_id);
        $stmt->execute();

        while ($entry = $stmt->fetch()) {
            $seminars[$entry['Seminar_id']] = $entry;
        }
        
        if (is_array($seminars)) foreach ($seminars as $data) {
            $entries = self::getSeminarEntry($data['Seminar_id'], $user_id);

            foreach ($entries as $entry) {
                unset($entry['url']);
                $entry['onClick'] = 'function(id) { STUDIP.Instschedule.showInstituteDetails(id); }';
                $entry['visible'] = 1;

                if (($entry['start'] >= $start_hour * 100 && $entry['start'] <= $end_hour * 100
                    || $entry['end'] >= $start_hour * 100 && $entry['end'] <= $end_hour * 100)) {

                    $entry['color'] = DEFAULT_COLOR_SEM;

                    $day_number = ($entry['day']-1) % 7;
                    if (!isset($ret[$day_number])) {
                        $ret[$day_number] = CalendarColumn::create($day_number);
                    }
                    $ret[$day_number]->addEntry($entry);
                }
            }
        }

        return CalendarScheduleModel::addDayChooser($ret, $days, 'instschedule');
    }
}
