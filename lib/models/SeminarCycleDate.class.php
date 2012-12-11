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
        $found = self::findBySql("metadate_id=(SELECT metadate_id FROM termine WHERE termin_id= ? "
                                  . "UNION SELECT metadate_id FROM ex_termine WHERE termin_id = ? )", array($termin_id, $termin_id));
        return is_array($found) ? $found[0] : null;
    }

    /**
     * constructor
     * @param string $id primary key of table seminar_cycle_dates
     * @return null
     */
    function __construct($id = null)
    {
        $this->db_table = 'seminar_cycle_dates';
        $this->additional_fields['start_hour'] = array('get' => 'getTimeFraction', 'set' => 'setTimeFraction');
        $this->additional_fields['start_minute'] = array('get' => 'getTimeFraction', 'set' => 'setTimeFraction');
        $this->additional_fields['end_hour'] = array('get' => 'getTimeFraction', 'set' => 'setTimeFraction');
        $this->additional_fields['end_minute'] = array('get' => 'getTimeFraction', 'set' => 'setTimeFraction');
        parent::__construct($id);
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
