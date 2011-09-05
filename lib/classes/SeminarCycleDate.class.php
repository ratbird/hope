<?php
# Lifter010: TODO
/**
 * SeminarCycleDate.class.php
 * model class for table seminar_cycle_dates
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack <noack@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       2.0
*/

require_once 'SimpleORMap.class.php';
require_once 'lib/resources/lib/RoomRequest.class.php';

class SeminarCycleDate extends SimpleORMap
{

    /**
     * returns new instance of type SeminarCycleDate with metadate_id = id
     * when found in db, or null if no SeminarCycleDate matches that id.
     * @param string id: primary key of table seminar_cycle_dates
     * @return object of type SeminarCycleDate for the given id of a SeminarCycleDate
     * or null if no SeminarCycleDate matches
     */
    static function find($id)
    {
        return SimpleORMap::find(__CLASS__, $id);
    }

    /**
     * returns array of instances of SeminarCycleDates filtered by given sql where-clause
     * @param string where: clause to use on the right side of WHERE
     * @return array of instances of SeminarCycleDates filtered by given sql
     * where-clause or an empty array if nothing matches the clause
     */
    static function findBySql($where)
    {
        return SimpleORMap::findBySql(__CLASS__, $where);
    }

    /**
     * returns array of instances of SeminarCycleDates of the given seminar_id
     * @param string seminar_id: selected seminar to search for SeminarCycleDates
     * @return array of instances of SeminarCycleDates of the given seminar_id or
     * an empty array
     */
    static function findBySeminar($seminar_id)
    {
        return self::findBySql("seminar_id=" . DbManager::get()->quote($seminar_id) . " ORDER BY sorter ASC, weekday ASC, start_time ASC");
    }

    /**
     * return instance of SeminarCycleDates of given termin_id
     * @param string termin_id: selected seminar to search for SeminarCycleDates
     * @return array
     */
    static function findByTermin($termin_id)
    {
        $found = self::findBySql("metadate_id=(SELECT metadate_id FROM termine WHERE termin_id=" . DbManager::get()->quote($termin_id) . "
                                  UNION SELECT metadate_id FROM ex_termine WHERE termin_id=" . DbManager::get()->quote($termin_id) . ")");
        return is_array($found) ? $found[0] : null;
    }

    /**
     * deletes SeminarCycleDates specified by given sql where-clause
     * @param string where clause to use on the right side of WHERE to delete all
     * SeminarCycleDate matching that clause
     * @return number of deleted SeminarCycleDates matching the given where-clause
     */
    static function deleteBySql($where)
    {
        $ret = SimpleORMap::deleteBySql(__CLASS__, $where);
        return $ret;
    }

    /**
     * constructor
     * @param string $id primary key of table seminar_cycle_dates
     * @return null
     */
    function __construct($id = null)
    {
        $this->db_table = 'seminar_cycle_dates';
        parent::__construct($id);
    }

    /**
     * returns value of a column or for field start_hour, end_hour, start_minute
     * or end_minute the correct timestamp
     * @param string field: name of the field or start_hour,
     * end_hour, start_minute or end_minute
     * @return string for the attribute, or int (timestamp) if field is start_hour,
     * end_hour, start_minute or end_minute or null, if field does not exists
     */
    function getValue($field)
    {
        if (in_array($field, array('start_hour', 'start_minute'))) {
            list($start_hour, $start_minute) = explode(':', $this->start_time);
            return (int)$$field;
        }
        if (in_array($field, array('end_hour', 'end_minute'))) {
            list($end_hour, $end_minute) = explode(':', $this->end_time);
            return (int)$$field;
        }
        return parent::getValue($field);
    }

    /**
     * sets value of a column|start_hour|start_minute|end_hour|end_minute|sws
     * @param string $field
     * @param string $value
     * @return string
     */
    function setValue($field, $value)
    {
        if ($field == 'start_hour') {
            $this->start_time = sprintf('%02u:%02u:00', $value, $this->start_minute);
            return $this->start_hour;
        }
        if ($field == 'start_minute') {
            $this->start_time = sprintf('%02u:%02u:00', $this->start_hour, $value);
            return $this->start_minute;
        }
        if ($field == 'end_hour') {
            $this->end_time = sprintf('%02u:%02u:00', $value , $this->end_minute);
            return $this->end_hour;
        }
        if ($field == 'end_minute') {
            $this->end_time = sprintf('%02u:%02u:00', $this->end_hour, $value);
            return $this->end_minute;
        }
        if ($field == 'sws') {
            return parent::setValue($field, round(str_replace(',','.', $value),1));
        }
        return parent::setValue($field, $value);
    }

    /**
     * set multiple column values
     * if second param is set, existing data in object will be
     * discarded, else new data overrides old data
     * @param array $data assoc array
     * @param boolean $reset
     * @return number of columns changed
     */
    function setData($data, $reset = false)
    {
        $count = parent::setData($data, $reset);
        $this->sws = $this->content['sws'];
        $this->content_db['sws'] = $this->sws;
        return $count;
    }

    /**
     * returns data of table row as assoc array inclusivly start_hour, end_hour etc.
     * @return associative array in the scheme attribute => value
     */
    function toArray()
    {
        $ret = parent::toArray();
        foreach(array('start_hour', 'start_minute', 'end_hour', 'end_minute') as $additional) {
            $ret[$additional] = $this->$additional;
        }
        return $ret;
    }

    /**
     * returns a string for a date like '3. 9:00s - 10:45' (short and long)
     * or '3. 9:00s - 10:45, , ab der 7. Semesterwoche, (Vorlesung)' with the week of the semester
     * @param format string: "short"|"long"|"full"
     * @return formatted string
     */
    function toString($format = 'short')
    {
        $template['short'] = '%s. %02s:%02s - %02s:%02s';
        $template['long'] = '%s: %02s:%02s - %02s:%02s, %s';
        $template['full'] = '%s: %02s:%02s - %02s:%02s, ' . _("%s, ab der %s. Semesterwoche") . '%s';
        $cycles = array(_("wöchentlich"), _("zweiwöchentlich"), _("dreiwöchentlich"));
        $day = getWeekDay($this->weekday, $format == 'short');
        return sprintf($template[$format],
                       $day,
                       $this->start_hour,
                       $this->start_minute,
                       $this->end_hour,
                       $this->end_minute,
                       $cycles[(int)$this->cycle],
                       $this->week_offset + 1,
                       $this->description ? ' ('.$this->description.')' : '');
    }

    /**
     * @see SimpleORMap::delete()
     */
    function delete()
    {
        if ($rr = RoomRequest::existsByCycle($this->getId())) {
            RoomRequest::find($rr)->delete();
        }
        return parent::delete();
    }
}
