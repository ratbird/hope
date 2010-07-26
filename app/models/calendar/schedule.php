<?php

/*
 * Copyright (C) 2009-2010 - Till Glöggler <tgloeggl@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once('app/models/calendar/calendar.php');
define('DEFAULT_COLOR_SEM', $GLOBALS['PERS_TERMIN_KAT'][2]['color']);
define('DEFAULT_COLOR_NEW', $GLOBALS['PERS_TERMIN_KAT'][3]['color']);
define('DEFAULT_COLOR_VIRTUAL', $GLOBALS['PERS_TERMIN_KAT'][1]['color']);

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
     */
    static function deleteEntry($id)
    {
        $stmt = DBManager::get()->prepare("DELETE FROM schedule
            WHERE id = ? AND user_id = ?");
        $stmt->execute(array($id, $GLOBALS['user']->id));
    }

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
            $entry['title']   = htmlReady($entry['title']);
            $entry['content'] = htmlReady($entry['content']);
            $entry['start_hour']   = sprintf("%02d", floor($entry['start'] / 100));
            $entry['start_minute'] = sprintf("%02d", $entry['start'] % 100);
            $entry['end_hour']     = sprintf("%02d", floor($entry['end'] / 100));
            $entry['end_minute']   = sprintf("%02d", $entry['end'] % 100);
            $entry['onClick']      = "STUDIP.Schedule.showScheduleDetails('". $entry['id'] ."'); return false;";

            $ret[$entry['day']][] = $entry;
        }

        return $ret;
    }

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
                $entry['content'] = htmlReady($sem->getNumber()) .' '. htmlReady($sem->getName());
                $entry['url']     = UrlHelper::getLink('dispatch.php/calendar/schedule/entry/' . $seminar_id
                                  . '/' . $cycle->getMetaDateId());
                $entry['onClick'] = "STUDIP.Schedule.showSeminarDetails('$seminar_id', '"
                                  . $cycle->getMetaDateId() ."'); return false;";

                $entry['title']   = '';

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
                    $entry['url'] .= '/?show_hidden=true';

                    $bind_url = UrlHelper::getLink('dispatch.php/calendar/schedule/bind/' 
                              . $seminar_id . '/' . $cycle->getMetaDateId() . '/?show_hidden=true');

                    $entry['icons'][] = array(
                        'url'   => $bind_url,
                        'image' => 'unhide',
                        'onClick' => 'STUDIP.Calendar.noNewEntry = true;',
                        'title' => _("Diesen Eintrag wieder einblenden")
                    );
                }
                
                // show a hide-icon if the entry is not virtual
                else if ($entry['type'] != 'virtual') {
                    $unbind_url = UrlHelper::getLink('dispatch.php/calendar/schedule/unbind/' 
                                . $seminar_id . '/' . $cycle->getMetaDateId());
                    $entry['icons'][] = array(
                        'url'     => $unbind_url,
                        'image'   => 'hide',
                        'onClick' => "STUDIP.Schedule.hideEntry(this, '$seminar_id', '". $cycle->getMetaDateId() ."'); return false;", 
                        'title'   => _("Dieses Eintrag ausblenden")
                    );

                }

                $ret[] = $entry;
            }
        }

        return $ret;
    }

    static function getSeminarEntries($user_id, $semester, $start_hour, $end_hour, $show_hidden = false, $seminar_id = false, $cycle_id = false)
    {
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
        $stmt = DBManager::get()->prepare("SELECT * FROM seminar_user as su
            LEFT JOIN seminare as s USING (Seminar_id)
            WHERE su.user_id = ?  AND (s.start_time = ?
                OR (s.start_time < ? AND s.duration_time = -1)
                OR (s.start_time + s.duration_time >= ?))");
        $stmt->execute(array($user_id, $semester['beginn'], $semester['beginn'], $semester['beginn']));

        while ($entry = $stmt->fetch()) {
            $seminars[$entry['Seminar_id']] = $entry;
        }
        
        if (is_array($seminars)) foreach ($seminars as $data) {
            $entries = self::getSeminarEntry($data['Seminar_id'], $user_id);

            foreach ($entries as $entry) {
                if (($entry['start'] >= $start_hour * 100 && $entry['start'] <= $end_hour * 100
                    || $entry['end'] >= $start_hour * 100 && $entry['end'] <= $end_hour * 100)
                    && ($show_hidden || (!$show_hidden && $entry['visible']))) {
                    $ret[$entry['day']][] = $entry;
                }
            }
        }

        return $ret;

    }


    static function getSeminarEntriesForInstitute($user_id, $semester, $start_hour, $end_hour, $institute_id,
                                                  $show_hidden = false, $seminar_id = false, $cycle_id = false)
    {
        // fetch seminar-entries 
        $stmt = DBManager::get()->prepare("SELECT * FROM seminare as s
            WHERE Institut_id = ? AND (start_time = ?
                OR (start_time < ? AND duration_time = -1)
                OR (start_time + duration_time >= ?))");
        $stmt->execute(array($institute_id, $semester['beginn'], $semester['beginn'], $semester['beginn']));

        while ($entry = $stmt->fetch()) {
            $seminars[$entry['Seminar_id']] = $entry;
        }
        
        if (is_array($seminars)) foreach ($seminars as $data) {
            $entries = self::getSeminarEntry($data['Seminar_id'], $user_id);

            foreach ($entries as $entry) {
                unset($entry['url']);
                $entry['onClick'] = 'STUDIP.Schedule.showInstituteDetails(this); return false;';

                if (($entry['start'] >= $start_hour * 100 && $entry['start'] <= $end_hour * 100
                    || $entry['end'] >= $start_hour * 100 && $entry['end'] <= $end_hour * 100)) {

                    $entry['color'] = DEFAULT_COLOR_SEM;

                    $ret[$entry['day']][] = $entry;
                }
            }
        }

        return $ret;
    }


    static function getSeminarCycleId(Seminar $seminar, $start, $end) {
        foreach ($seminar->getCycles() as $cycle) {
            if (leadingZero($cycle->getStartStunde()) . leadingZero($cycle->getStartMinute()) == $start
                && leadingZero($cycle->getEndStunde()) . leadingZero($cycle->getEndMinute()) == $end) {
                return $cycle->getMetadateId();
            }
        }
    }

    static function isSeminarVisible($seminar_id, $cycle_id) {
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

    static function getInstituteEntries($user_id, $semester, $start_hour, $end_hour, $institute_id, $show_hidden = false)
    {
        // merge the schedule and seminar-entries
        $entries = self::getScheduleEntries($user_id, $start_hour, $end_hour);
        $seminar = self::getSeminarEntriesForInstitute($user_id, $semester, $start_hour, $end_hour, $institute_id, $show_hidden);
        if (is_array($seminar)) foreach($seminar as $day => $sem_entries) {
            foreach ($sem_entries as $entry) {
                $entries[$day][] = $entry;
            }
        }

        return $entries;
    }

    /**
     * return an array of entries in the schedule (personal ones and regular seminar-dates)
     *
     * @param  string  $user_id
     * @param  mixed   $semester  the data for the semester to be displayed
     */
    static function getEntries($user_id, $semester, $start_hour, $end_hour, $show_hidden = false)
    {
        // merge the schedule and seminar-entries
        $entries = self::getScheduleEntries($user_id, $start_hour, $end_hour);
        $seminar = self::getSeminarEntries($user_id, $semester, $start_hour, $end_hour, $show_hidden);
        if (is_array($seminar)) foreach($seminar as $day => $sem_entries) {
            foreach ($sem_entries as $entry) {
                $entries[$day][] = $entry;
            }
        }

        return $entries;
    }

    static function adminBind($seminar_id, $cycle_id, $visible = true) {
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

        var_dump('set visibility to: '. $visible);
        $stmt->execute(array($visible ? '1' : '0', $seminar_id, $GLOBALS['user']->id, $cycle_id));
       
    }

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

    static function bind($seminar_id, $cycle_id)
    {
        $stmt = DBManager::get()->prepare("UPDATE schedule_seminare
            SET visible = 1
            WHERE seminar_id = ? AND user_id = ? AND metadate_id = ?");

        $stmt->execute(array($seminar_id, $GLOBALS['user']->id, $cycle_id));
    }
}
