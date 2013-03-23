<?php
# Lifter010: TODO

/*
 *  This class is the module for the seminar-schedules in Stud.IP
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Till Gl�ggler <tgloeggl@uos.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

require_once('lib/calendar/CalendarColumn.class.php');
define('DEFAULT_COLOR_SEM', $GLOBALS['PERS_TERMIN_KAT'][2]['color']);
define('DEFAULT_COLOR_NEW', $GLOBALS['PERS_TERMIN_KAT'][3]['color']);
define('DEFAULT_COLOR_VIRTUAL', $GLOBALS['PERS_TERMIN_KAT'][1]['color']);

/**
 * Pseudo-namespace containing helper methods for the schedule.
 *
 * @since      2.0
 */
class CalendarScheduleModel
{

    /**
     * update an existing entry or -if $data['id'] is not set- create a new entry
     *
     * @param  mixed  $data
     */
    static function storeEntry($data)
    {
        if ($data['id']) {     // update
            $stmt = DBManager::get()->prepare("UPDATE schedule
                SET start = ?, end = ?, day = ?, title = ?, content = ?, color = ?, user_id = ?
                WHERE id = ?");
            $stmt->execute(array($data['start'], $data['end'], $data['day'], $data['title'],
                $data['content'], $data['color'], $data['user_id'], $data['id']));
        } else {
            $stmt = DBManager::get()->prepare("INSERT INTO schedule
                (start, end, day, title, content, color, user_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute(array($data['start'], $data['end'], $data['day'], $data['title'],
                $data['content'], $data['color'], $data['user_id']));
        }
    }

    /**
     * Update an existing entry of a course or create a new entry if $data['id'] is not set
     *
     * @param mixed  $data  the data to store
     * @return void
     */
    static function storeSeminarEntry($data)
    {
        $stmt = DBManager::get()->prepare("REPLACE INTO schedule_seminare
            (seminar_id, user_id, metadate_id, color) VALUES(?, ? ,?, ?)");

        $stmt->execute(array($data['id'], $GLOBALS['user']->id, $data['cycle_id'], $data['color']));
    }

    /**
     * delete the entry with the submitted id, belonging to the current user
     *
     * @param  string  $id
     * @return void
     */
    static function deleteEntry($id)
    {
        $stmt = DBManager::get()->prepare("DELETE FROM schedule
            WHERE id = ? AND user_id = ?");
        $stmt->execute(array($id, $GLOBALS['user']->id));
    }


    /**
     * Returns an array of CalendarColumn's containing the
     * schedule entries (optionally of a given id only).
     * The start- and end-hour are used to constrain the returned
     * entries to the passed time-period.
     * If you pass an id, there will be only the single entry with that id in
     * the CalendarColumn
     *
     * @param string  $user_id the  ID of the user
     * @param int     $start_hour   the start hour
     * @param int     $end_hour     the end hour
     * @param string  $id           optional; the ID of the schedule-entry
     * @return array  an array containing the entries
     */
    static function getScheduleEntries($user_id, $start_hour, $end_hour, $id = false)
    {
        $ret = array();

        // fetch user-generated entries
        if (!$id) {
            $stmt = DBManager::get()->prepare("SELECT * FROM schedule
                WHERE user_id = ? AND (
                    (start >= ? AND end <= ?)
                    OR (start <= ? AND end >= ?)
                    OR (start <= ? AND end >= ?)
                )");
            $start = $start_hour * 100;
            $end   = $end_hour   * 100;
            $stmt->execute(array($user_id, $start, $end, $start, $start, $end, $end));
        } else {
            $stmt = DBManager::get()->prepare("SELECT * FROM schedule
                WHERE user_id = ? AND id = ?");
            $stmt->execute(array($user_id, $id));
        }

        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($entries as $entry) {
            $entry['start_formatted'] = sprintf("%02d", floor($entry['start'] / 100)) .':'. sprintf("%02d", floor($entry['start'] % 100));
            $entry['end_formatted'] = sprintf("%02d", floor($entry['end'] / 100)) .':'. sprintf("%02d", floor($entry['end'] % 100));
            $entry['title']        = $entry['title'];
            $entry['content']      = $entry['content'];
            $entry['start_hour']   = sprintf("%02d", floor($entry['start'] / 100));
            $entry['start_minute'] = sprintf("%02d", $entry['start'] % 100);
            $entry['end_hour']     = sprintf("%02d", floor($entry['end'] / 100));
            $entry['end_minute']   = sprintf("%02d", $entry['end'] % 100);
            $entry['onClick']      = "function (id) { STUDIP.Schedule.showScheduleDetails('". $entry['id'] ."'); }";
            $entry['visible']      = true;

            $day_number = ($entry['day']-1) % 7;
            if (!isset($ret[$day_number])) {
                $ret[$day_number] = CalendarColumn::create($day_number);
            }
            $ret[$day_number]->addEntry($entry);
        }

        return $ret;
    }

    /**
     * Return an entry for the specified course.
     *
     * @param string  $seminar_id  the ID of the course
     * @param string  $user_id     the ID of the user
     * @param mixed   $cycle_id    either false or the ID of the cycle
     * @param mixed   $semester    filter for this semester
     *
     * @return array  the course's entry
     */
    static function getSeminarEntry($seminar_id, $user_id, $cycle_id = false, $semester = false)
    {
        $ret = array();
        $filterStart = 0;
        $filterEnd   = 0;

        // filter dates (and their rooms) if semester is passed
        if ($semester) {
            $filterStart = $semester['vorles_beginn'];
            $filterEnd   = $semester['vorles_ende'];
        }

        $sem = new Seminar($seminar_id);
        foreach ($sem->getCycles() as $cycle) {
            if (!$cycle_id || $cycle->getMetaDateID() == $cycle_id) {
                $entry = array();

                $entry['id'] = $seminar_id .'-'. $cycle->getMetaDateId();
                $entry['cycle_id'] = $cycle->getMetaDateId();
                $entry['start_formatted'] = sprintf("%02d", $cycle->getStartStunde()) .':'
                    . sprintf("%02d", $cycle->getStartMinute());
                $entry['end_formatted'] = sprintf("%02d", $cycle->getEndStunde()) .':'
                    . sprintf("%02d", $cycle->getEndMinute());

                $entry['start']   = ((int)$cycle->getStartStunde() * 100) + ($cycle->getStartMinute());
                $entry['end']     = ((int)$cycle->getEndStunde() * 100) + ($cycle->getEndMinute());
                $entry['day']     = $cycle->getDay();
                $entry['content'] = $sem->getNumber() . ' ' . $sem->getName();

                $entry['title']   = $cycle->getDescription();

                // check, if the date is assigned to a room
                if ($rooms = $cycle->getPredominantRoom($filterStart, $filterEnd)) {
                    $entry['title'] .= implode('', getPlainRooms(array_slice($rooms, 0, 1)))
                                    . (sizeof($rooms) > 1 ? ', u.a.' : '');
                } else if ($rooms = $cycle->getFreeTextPredominantRoom($filterStart, $filterEnd)) {
                    unset($rooms['']);
                    if (!empty($rooms)) {
                        $entry['title'] .= '('. implode('), (', array_slice(array_keys($rooms), 0, 3)) .')';
                    }
                }

                // add the lecturer
                $lecturers = array();
                $members = $sem->getMembers('dozent');

                foreach ($members as $member) {
                    $lecturers[] = $member['Nachname'];
                }
                $entry['content'] .= " (". implode(', ', array_slice($lecturers, 0, 3))
                                  . (sizeof($members) > 3 ? ' et al.' : '').')';


                $entry['url']     = URLHelper::getLink('dispatch.php/calendar/schedule/entry/' . $seminar_id
                                  . '/' . $cycle->getMetaDateId());
                $entry['onClick'] = "function (id) {
                    var ids = id.split('-');
                    STUDIP.Schedule.showSeminarDetails(ids[0], ids[1]);
                }";


                // check the settings for this entry
                $stmt2 = DBManager::get()->prepare("SELECT sc.*, IF(su.user_id IS NULL, 'virtual', 'sem') as type
                    FROM schedule_seminare as sc
                    LEFT JOIN seminar_user as su ON (su.user_id = sc.user_id AND su.Seminar_id = sc.seminar_id)
                    WHERE sc.seminar_id = ? AND sc.user_id = ? AND sc.metadate_id = ?");
                $stmt2->execute(array($sem->getId(), $user_id, $cycle->getMetaDateId()));

                if ($details = $stmt2->fetch()) {
                    if ($details['type'] == 'virtual') {
                        $entry['color'] = $details['color'] ? $details['color'] : DEFAULT_COLOR_VIRTUAL;
                        $entry['icons'][] = array(
                            'image' => 'virtual.png',
                            'title' => _("Dies ist eine vorgemerkte Veranstaltung")
                        );
                    } else {
                        $entry['color'] = $details['color'] ? $details['color'] : DEFAULT_COLOR_SEM;
                    }
                    $entry['type']    = $details['type'];
                    $entry['visible'] = $details['visible'];
                } else {
                    $entry['type']    = 'sem';
                    $entry['color']   = DEFAULT_COLOR_SEM;
                    $entry['visible'] = 1;
                }

                // show an unhide icon if entry is invisible
                if (!$entry['visible']) {
                    $entry['url'] .= '/?show_hidden=1';

                    $bind_url = URLHelper::getLink('dispatch.php/calendar/schedule/bind/'
                              . $seminar_id . '/' . $cycle->getMetaDateId() . '/?show_hidden=1');

                    $entry['icons'][] = array(
                        'url'   => $bind_url,
                        'image' => Assets::image_path('icons/16/white/visibility-invisible.png'),
                        'onClick' => "function(id) { window.location = '". $bind_url ."'; }",
                        'title' => _("Diesen Eintrag wieder einblenden"),
                    );
                }

                // show a hide-icon if the entry is not virtual
                else if ($entry['type'] != 'virtual') {
                    $unbind_url = URLHelper::getLink('dispatch.php/calendar/schedule/unbind/'
                                . $seminar_id . '/' . $cycle->getMetaDateId());
                    $entry['icons'][] = array(
                        'url'     => $unbind_url,
                        'image'   => Assets::image_path('icons/16/white/visibility-visible.png'),
                        'onClick' => "function(id) { window.location = '". $unbind_url ."'; }",
                        'title'   => _("Diesen Eintrag ausblenden"),
                    );

                }

                $ret[] = $entry;
            }
        }

        return $ret;
    }

    /**
     * Deletes the schedule entries of one user for one seminar.
     *
     * @param string  $user_id     the user of the schedule
     * @param string  $seminar_id  the seminar which entries should be deleted
     */
    static function deleteSeminarEntries($user_id, $seminar_id)
    {
        $stmt = DBManager::get()->prepare($query = "DELETE FROM schedule_seminare
            WHERE user_id = ? AND seminar_id = ?");
        $stmt->execute(array($user_id, $seminar_id));
    }

    /**
     * Returns an array of CalendarColumn's, containing the seminar-entries
     * for the passed user in the passed semester.
     * The start- and end-hour are used to constrain the returned
     * entries to the passed time-period.
     * Seminar-entries can be hidden, so you can opt-in to fetch the hidden
     * ones as well.
     *
     * @param string  $user_id      the ID of the user
     * @param string  $semester     an array containing the "beginn" of the semester
     * @param int     $start_hour   the start hour
     * @param int     $end_hour     the end hour
     * @param string  $show_hidden  optional; true to show hidden, false otherwise
     * @return array  an array containing the properties of the entry
     */
    static function getSeminarEntries($user_id, $semester, $start_hour, $end_hour, $show_hidden = false)
    {
        $seminars = array();
        
        // get all virtually added seminars
        $stmt = DBManager::get()->prepare("SELECT * FROM schedule_seminare as c
            LEFT JOIN seminare as s ON (s.Seminar_id = c.Seminar_id)
            WHERE c.user_id = ? AND s.start_time = ?");
        $stmt->execute(array($user_id, $semester['beginn']));

        while ($entry = $stmt->fetch()) {
            $seminars[$entry['seminar_id']] = array(
                'Seminar_id' => $entry['seminar_id']
            );
        }

        // fetch seminar-entries
        $stmt = DBManager::get()->prepare("SELECT s.Seminar_id FROM seminar_user as su
            LEFT JOIN seminare as s USING (Seminar_id)
            WHERE su.user_id = :userid AND (s.start_time = :begin
                OR (s.start_time <= :begin AND s.duration_time = -1)
                OR (s.start_time + s.duration_time >= :begin
                    AND s.start_time <= :begin))");
        $stmt->bindParam(':begin', $semester['beginn']);
        $stmt->bindParam(':userid', $user_id);
        $stmt->execute();

        while ($entry = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $seminars[$entry['Seminar_id']] = array(
                'Seminar_id' => $entry['Seminar_id']
            );
        }

        $ret = array();
        foreach ($seminars as $data) {
            $entries = self::getSeminarEntry($data['Seminar_id'], $user_id, false, $semester);

            foreach ($entries as $entry) {
                if (($entry['start'] >= $start_hour * 100 && $entry['start'] <= $end_hour * 100
                    || $entry['end'] >= $start_hour * 100 && $entry['end'] <= $end_hour * 100)
                    && ($show_hidden || (!$show_hidden && $entry['visible']))) {
                    $day_number = ($entry['day']-1) % 7;
                    if (!isset($ret[$day_number])) {
                        $ret[$day_number] = new CalendarColumn();
                    }

                    $ret[$day_number]->addEntry($entry);
                }
            }
        }

        return $ret;

    }


    /**
     * Returns an array of CalendarColumn's, containing the seminar-entries
     * for the passed user in the passed semester belonging to the passed institute.
     * The start- and end-hour are used to constrain the returned
     * entries to the passed time-period.
     *
     * @param string  $user_id       the ID of the user
     * @param array   $semester      an array containing the "beginn" of the semester
     * @param int     $start_hour    the start hour
     * @param int     $end_hour      the end hour
     * @param string  $institute_id  the ID of the institute
     * @return array  an array containing the entries
     */
    static function getSeminarEntriesForInstitute($user_id, $semester, $start_hour, $end_hour, $institute_id)
    {
        $ret = array();

        // fetch seminar-entries
        $visibility_perms = $GLOBALS['perm']->have_perm(get_config('SEM_VISIBILITY_PERM'));
        $stmt = DBManager::get()->prepare("SELECT * FROM seminare
            WHERE Institut_id = :institute AND (start_time = :begin
                OR (start_time < :begin AND duration_time = -1)
                OR (start_time + duration_time >= :begin AND start_time <= :begin)) "
                . (!$visibility_perms ? " AND visible='1'" : ""));

        $stmt->bindParam(':begin', $semester['beginn']);
        $stmt->bindParam(':institute', $institute_id);
        $stmt->execute();

        $seminars = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($seminars as $data) {
            $entries = self::getSeminarEntry($data['Seminar_id'], $user_id, false, $semester);

            foreach ($entries as $entry) {
                unset($entry['url']);
                $entry['onClick'] = 'function(id) { STUDIP.Schedule.showInstituteDetails(id); }';

                if (($entry['start'] >= $start_hour * 100 && $entry['start'] <= $end_hour * 100
                    || $entry['end'] >= $start_hour * 100 && $entry['end'] <= $end_hour * 100)) {

                    $entry['color'] = DEFAULT_COLOR_SEM;

                    $day_number = ($entry['day']-1) % 7;
                    if (!isset($ret[$day_number])) {
                        $ret[$day_number] = CalendarColumn::create($entry['day']);
                    }

                    $ret[$day_number]->addEntry($entry);
                }
            }
        }

        return $ret;
    }


    /**
     * Returns the ID of the cycle of a course specified by start and end.
     *
     * @param  Seminar  an instance of a Seminar
     * @param  string   the start of the cycle
     * @param  string   the end of the cycle
     * @return string   the ID of the cycle
     */
    static function getSeminarCycleId(Seminar $seminar, $start, $end)
    {
        foreach ($seminar->getCycles() as $cycle) {
            if (leadingZero($cycle->getStartStunde()) . leadingZero($cycle->getStartMinute()) == $start
                && leadingZero($cycle->getEndStunde()) . leadingZero($cycle->getEndMinute()) == $end) {
                return $cycle->getMetadateId();
            }
        }
    }

    /**
     * check if the passed cycle of the passed id is visible
     * for the currently logged in user int the schedule
     *
     * @param  string the ID of the course
     * @param  string the ID of the cycle
     * @return bool true if visible, false otherwise
     */
    static function isSeminarVisible($seminar_id, $cycle_id)
    {
        $stmt = DBManager::get()->prepare("SELECT visible
            FROM schedule_seminare
            WHERE seminar_id = ? AND user_id = ? AND metadate_id = ?");
        $stmt->execute(array($seminar_id, $GLOBALS['user']->id, $cycle_id));
        if (!$data = $stmt->fetch()) {
            return true;
        } else {
            return $data['visible'] ? true : false;
        }
    }

    /**
     * Returns an array of CalendarColumn's, containing the seminar-entries
     * for the passed user (in the passed semester belonging to the passed institute)
     * and the user-defined schedule-entries.
     * The start- and end-hour are used to constrain the returned
     * entries to the passed time-period. The passed days constrain the entries
     * to these.
     * Seminar-entries can be hidden, so you can opt-in to fetch the hidden
     * ones as well.
     *
     * @param string  $user_id       the user's ID
     * @param string  $semester      the data for the semester to be displayed
     * @param int     $start_hour    the start hour of the entries
     * @param int     $end_hour      the end hour of the entries
     * @param string  $institute_id  the institute's ID
     * @param array   $days          days to be displayed
     * @param bool    $show_hidden   filters hidden entries
     * @return array  an array of entries
     */
    static function getInstituteEntries($user_id, $semester, $start_hour, $end_hour, $institute_id, $days, $show_hidden = false)
    {
        // merge the schedule and seminar-entries
        $entries = self::getScheduleEntries($user_id, $start_hour, $end_hour, false);
        $seminar = self::getSeminarEntriesForInstitute($user_id, $semester, $start_hour, $end_hour, $institute_id, $show_hidden);

        foreach($seminar as $day => $entry_column) {
            foreach ($entry_column->getEntries() as $entry) {
                if (!isset($entries[$day])) {
                    $entries[$day] = CalendarColumn::create($day);
                }
                $entries[$day]->addEntry($entry);
            }
        }

        return self::addDayChooser($entries, $days);
    }

    /**
     * 
     *
     * @param  string  $user_id
     * @param  mixed   $semester  the data for the semester to be displayed
     */

    /**
     * Returns an array of CalendarColumn's, containing the seminar-entries
     * for the passed user (in the passed semester) and the user-defined schedule-entries.
     * The start- and end-hour are used to constrain the returned
     * entries to the passed time-period. The passed days constrain the entries
     * to these.
     * Seminar-entries can be hidden, so you can opt-in to fetch the hidden
     * ones as well.
     *
     * @param string  $user_id       the user's ID
     * @param string  $semester      the data for the semester to be displayed
     * @param int     $start_hour    the start hour of the entries
     * @param int     $end_hour      the end hour of the entries
     * @param array   $days          days to be displayed
     * @param bool    $show_hidden   filters hidden entries
     * @return array
     */
    static function getEntries($user_id, $semester, $start_hour, $end_hour, $days, $show_hidden = false)
    {
        // merge the schedule and seminar-entries
        $entries = self::getScheduleEntries($user_id, $start_hour, $end_hour, false);
        $seminar = self::getSeminarEntries($user_id, $semester, $start_hour, $end_hour, $show_hidden);
        foreach($seminar as $day => $entry_column) {
            foreach ($entry_column->getEntries() as $entry) {
                if (!isset($entries[$day])) {
                    $entries[$day] = CalendarColumn::create($day);
                }
                $entries[$day]->addEntry($entry);
            }
        }

        return self::addDayChooser($entries, $days);
    }

    /**
     * adds title and link to CalendarColumn-objects and sorts the objects to be
     * displayed correctly in the calendar-view
     *
     * @param array $entries  an array of CalendarColumn-objects
     * @param array $days     an array of int's, denoting the days to be displayed
     * @return array 
     */
    static function addDayChooser($entries, $days, $controller = 'schedule') {
        $day_names  = array(_("Montag"),_("Dienstag"),_("Mittwoch"),
            _("Donnerstag"),_("Freitag"),_("Samstag"),_("Sonntag"));

        $ret = array();

        foreach ($days as $day) {
            if (!isset($entries[$day])) {
                $ret[$day] = CalendarColumn::create($day);
            } else {
                $ret[$day] = $entries[$day];
            }

            if (sizeof($days) == 1) {
                $ret[$day]->setTitle($day_names[$day] .' ('. _('zur�ck zur Wochenansicht') .')')
                    ->setURL('dispatch.php/calendar/'. $controller .'/index');
            } else {
                $ret[$day]->setTitle($day_names[$day])
                    ->setURL('dispatch.php/calendar/'. $controller .'/index/'. $day);
            }
        }

        return $ret;
    }

    /**
     * Toggle entries' visibility
     *
     * @param  string  $seminar_id  the course's ID
     * @param  string  $cycle_id    the cycle's ID
     * @param  bool    $visible     the value to switch to
     * @return void
     */
    static function adminBind($seminar_id, $cycle_id, $visible = true)
    {
        $stmt = DBManager::get()->prepare("SELECT * FROM schedule_seminare
            WHERE seminar_id = ? AND user_id = ? AND metadate_id = ?");
        $stmt->execute(array($seminar_id, $GLOBALS['user']->id, $cycle_id));

        if ($stmt->fetch()) {
            $stmt = DBManager::get()->prepare("UPDATE schedule_seminare
                SET visible = ?
                WHERE seminar_id = ? AND user_id = ? AND metadate_id = ?");
        } else {
            $stmt = DBManager::get()->prepare("INSERT INTO schedule_seminare
                (visible, seminar_id, user_id, metadate_id)
                VALUES(?, ?, ?, ?)");
        }

        $stmt->execute(array($visible ? '1' : '0', $seminar_id, $GLOBALS['user']->id, $cycle_id));

    }

    /**
     * Switch a seminars' cycle to invisible.
     *
     * @param  string  $seminar_id  the course's ID
     * @param  string  $cycle_id    the cycle's ID
     * @return void
     */
    static function unbind($seminar_id, $cycle_id = false)
    {
        $stmt = DBManager::get()->prepare("SELECT su.*, sc.seminar_id as present
            FROM seminar_user as su
            LEFT JOIN schedule_seminare as sc ON (su.Seminar_id = sc.seminar_id
                AND sc.user_id = su.user_id AND sc.metadate_id = ?)
            WHERE su.Seminar_id = ? AND su.user_id = ?");
        $stmt->execute(array($cycle_id, $seminar_id, $GLOBALS['user']->id));

        // if we are participant of the seminar, just hide the entry
        if ($data = $stmt->fetch()) {
            if ($data['present']) {
                $stmt = DBManager::get()->prepare("UPDATE schedule_seminare
                    SET visible = 0
                    WHERE seminar_id = ? AND user_id = ? AND metadate_id = ?");
            } else {
                $stmt = DBManager::get()->prepare("INSERT INTO schedule_seminare
                    (seminar_id, user_id, metadate_id, visible)
                    VALUES(?, ?, ?, 0)");
            }
            $stmt->execute(array($seminar_id, $GLOBALS['user']->id, $cycle_id));
        }

        // otherwise delete the entry
        else {
            $stmt = DBManager::get()->prepare("DELETE FROM schedule_seminare
                WHERE seminar_id = ? AND user_id = ?");
            $stmt->execute(array($seminar_id, $GLOBALS['user']->id));
        }
    }

    /**
     * Switch a seminars' cycle to visible.
     *
     * @param  string  $seminar_id  the course's ID
     * @param  string  $cycle_id    the cycle's ID
     * @return void
     */
    static function bind($seminar_id, $cycle_id)
    {
        $stmt = DBManager::get()->prepare("UPDATE schedule_seminare
            SET visible = 1
            WHERE seminar_id = ? AND user_id = ? AND metadate_id = ?");

        $stmt->execute(array($seminar_id, $GLOBALS['user']->id, $cycle_id));
    }
}
