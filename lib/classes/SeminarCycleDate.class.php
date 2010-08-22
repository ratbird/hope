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
 * @copyright   2010 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
*/

require_once 'SimpleORMap.class.php';

class SeminarCycleDate extends SimpleORMap
{

    static function find($id)
    {
        return SimpleORMap::find(__CLASS__, $id);
    }

    static function findBySql($where)
    {
        return SimpleORMap::findBySql(__CLASS__, $where);
    }

    static function findBySeminar($seminar_id)
    {
        return self::findBySql("seminar_id=" . DbManager::get()->quote($seminar_id) . " ORDER BY sorter ASC, weekday ASC, start_time ASC");
    }

    static function findByTermin($termin_id)
    {
        $found = self::findBySql("metadate_id=(SELECT metadate_id FROM termine WHERE temin_id=" . DbManager::get()->quote($termin_id) . "
                                  UNION SELECT metadate_id FROM ex_termine WHERE temin_id=" . DbManager::get()->quote($termin_id) . ")");
        return is_array($found) ? $found[0] : null;
    }

    static function deleteBySql($where)
    {
        return SimpleORMap::deleteBySql(__CLASS__, $where);
    }

    /**
     *
     * @param string $id primary key of table
     */
    function __construct($id = null)
    {
        $this->db_table = 'seminar_cycle_dates';
        parent::__construct($id);
    }

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

    function setValue($field, $value)
    {
        if ($field == 'start_hour') {
            $this->start_time = $value . ':' . $this->start_minute;
            return $this->start_hour;
        }
        if ($field == 'start_minute') {
            $this->start_time = $this->start_hour . ':' . $value ;
            return $this->start_minute;
        }
        if ($field == 'end_hour') {
            $this->end_time = $value . ':' . $this->end_minute;
            return $this->end_hour;
        }
        if ($field == 'end_minute') {
            $this->end_time = $this->end_hour . ':' . $value ;
            return $this->end_minute;
        }
        if ($field == 'sws') {
            return parent::setValue($field, round(str_replace(',','.', $value),1));
        }
        return parent::setValue($field, $value);
    }

    function setData($data, $reset = false)
    {
        $count = parent::setData($data, $reset);
        $this->sws = $this->content['sws'];
        return $count;
    }

    function toArray()
    {
        $ret = parent::toArray();
        foreach(array('start_hour', 'start_minute', 'end_hour', 'end_minute') as $additional) {
            $ret[$additional] = $this->$additional;
        }
        return $ret;
    }

    function toString($format = 'short')
    {
        $template['short'] = '%s. %02s:%02s - %02s:%02s';
        $template['long'] = '%s: %02s:%02s - %02s:%02s';
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