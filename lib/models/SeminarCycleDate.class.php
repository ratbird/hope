<?php
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
 * @property string metadate_id database column
 * @property string id alias column for metadate_id
 * @property string seminar_id database column
 * @property string start_time database column
 * @property string end_time database column
 * @property string weekday database column
 * @property string description database column
 * @property string sws database column
 * @property string cycle database column
 * @property string week_offset database column
 * @property string sorter database column
 * @property string mkdate database column
 * @property string chdate database column
 * @property string start_hour computed column read/write
 * @property string start_minute computed column read/write
 * @property string end_hour computed column read/write
 * @property string end_minute computed column read/write
 */

class SeminarCycleDate extends SimpleORMap
{

    /**
     * returns array of instances of SeminarCycleDates of the given seminar_id
     * @param string seminar_id: selected seminar to search for SeminarCycleDates
     * @return array of instances of SeminarCycleDates of the given seminar_id or
     * an empty array
     */
    static function findBySeminar($seminar_id)
    {
        return self::findBySeminar_id($seminar_id, "ORDER BY sorter ASC, weekday ASC, start_time ASC");
    }

    /**
     * return instance of SeminarCycleDates of given termin_id
     * @param string termin_id: selected seminar to search for SeminarCycleDates
     * @return array
     */
    static function findByTermin($termin_id)
    {
        return self::findOneBySql("metadate_id=(SELECT metadate_id FROM termine WHERE termin_id= ? "
                                  . "UNION SELECT metadate_id FROM ex_termine WHERE termin_id = ? )", array($termin_id, $termin_id));
    }


    protected static function configure($config = array())
    {
        $config['db_table'] = 'seminar_cycle_dates';
        $config['belongs_to']['course'] = array('class_name' => 'Course');
        $config['has_one']['room_request'] = array(
            'class_name' => 'RoomRequest',
            'on_store' => 'store',
            'on_delete' => 'delete',
        );
        $config['has_many']['dates'] = array(
            'class_name' => 'CourseDate',
            'on_delete' => 'delete',
            'on_store' => 'store',
        );
        $config['additional_fields']['start_hour'] = array('get' => 'getTimeFraction', 'set' => 'setTimeFraction');
        $config['additional_fields']['start_minute'] = array('get' => 'getTimeFraction', 'set' => 'setTimeFraction');
        $config['additional_fields']['end_hour'] = array('get' => 'getTimeFraction', 'set' => 'setTimeFraction');
        $config['additional_fields']['end_minute'] = array('get' => 'getTimeFraction', 'set' => 'setTimeFraction');
        parent::configure($config);
    }

    protected function getTimeFraction($field)
    {
        if (in_array($field, array('start_hour', 'start_minute'))) {
            list($start_hour, $start_minute) = explode(':', $this->start_time);
            return (int)$$field;
        }
        if (in_array($field, array('end_hour', 'end_minute'))) {
            list($end_hour, $end_minute) = explode(':', $this->end_time);
            return (int)$$field;
        }
    }

    protected function setTimeFraction($field, $value)
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
    }

    /**
     * SWS needs special setter to always store a decimal
     *
     * @param number $value
     */
    protected function setSws($value)
    {
        $this->content['sws'] =  round(str_replace(',','.', $value),1);
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
}
