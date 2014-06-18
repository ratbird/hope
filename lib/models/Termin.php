<?php

/**
 * Termin.php
 * model class for table termine
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Florian Bieringer <florian.bieringer@uni-passau.de>
 * @copyright   2014 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       3.1
 */
class Termin extends SimpleORMap {

    protected static function configure($config = array()) {
        $config['db_table'] = 'termine';
        $config['additional_fields']['info'] = true;
        $config['additional_fields']['title'] = true;
        $config['has_and_belongs_to_many']['themen'] = array(
            'class_name' => 'Thema',
            'thru_table' => 'themen_termine'
        );
        $config['has_and_belongs_to_many']['groups'] = array(
            'class_name' => 'Statusgruppen',
            'thru_table' => 'statusgruppen_termine',
            'thru_assoc_key' => 'assign_id'
        );
        $config['belongs_to']['course'] = array(
            'class_name' => 'Course',
            'foreign_key' => 'range_id'
        );
        parent::configure($config);
    }

    public function __construct($id = null) {
        parent::__construct($id);
    }

    /**
     * Finds current dates by a range id
     * 
     * @param String $range the range id for the dates
     * @param int $timespan defines the timespan (default 1 week)
     * @param int $start defines the start for the timespan (default now)
     * @return Array All found dates
     */
    public static function findCurrent($range, $timespan = 604800, $start = null) {
        $start = $start ? : time();
        $end = $start + $timespan;
        return self::findBySQL('range_id = :range AND ((date BETWEEN :start AND :end) OR (end_time BETWEEN :start AND :end)) ORDER BY date', array(':range' => $range, ':start' => $start, ':end' => $end));
    }

    /**
     * Fetches all information about a date
     * 
     * @return array all required information
     */
    public function getInfo() {
        $info = array();

        // Find lecturers
        if ($this->course) {
            $dozenten = SimpleORMapCollection::createFromArray($this->course->members->findBy('status', 'dozent')->pluck('user'));
            $info[_('Durchführende Dozenten')] = join(', ', $dozenten->getFullname());
        }

        return $info;
    }

    /**
     * Builds the full title of a date
     * 
     * @return string title of the date
     */
    public function getTitle() {
        if (date("Ymd", $this->date) == date("Ymd"))
            $titel .= _("Heute") . date(", H:i", $this->date);
        else {
            $titel = strftime("%a. %d.%m.%Y, %H:%M", $this->date);
        }

        if ($this->date < $this->end_time) {
            if (date("Ymd", $this->date) < date("Ymd", $this->end_time)) {
                $titel .= " - " . substr(strftime("%a", $this->end_time), 0, 2);
                $titel .= date(". d.m.Y, H:i", $this->end_time);
            } else {
                $titel .= " - " . date("H:i", $this->end_time);
            }
        }

        // Check if we got topics
        if ($this->themen[0]) {
            $titel .= ', ' . join(', ', $this->themen->getValue('title'));
        }

        return $titel;
    }

}
