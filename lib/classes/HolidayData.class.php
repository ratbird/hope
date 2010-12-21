<?php
/**
 * HolidayData.class.php
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Mark Sievers <msievers@uos.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

class HolidayData
{
    var $db;

    /**
     * get an instance of this class
     *
     * @param boolean $refresh_cache
     * @return object HolidayData
     */
    static function GetInstance($refresh_cache = false)
    {
        static $holiday_object;

        if ($refresh_cache) {
            $holiday_object = null;
        }
        if (is_object($holiday_object)) {
            return $holiday_object;
        } else {
            $holiday_object = new HolidayData();
            return $holiday_object;
        }
    }

    function GetAllHolidaysArray(){
        static $all_holiday;
        if (is_null($all_holiday)){
            $holiday = new HolidayData();
            $all_holiday = $holiday->getAllHolidays();
        }
        return $all_holiday;
    }

    function HolidayData() {
        $this->db = new DB_Seminar;
    }

    function getAllHolidays() {
        $i=0;
        $sql = "SELECT * FROM semester_holiday order by beginn";
        if (!$this->db->query($sql)) {
            echo "Error! Query (getAllHolidays) not succeeded";
            die();
        }
        if ($this->db->num_rows()==0) {
            return array();
        }
        while ($this->db->next_record()) {
            $holidaydata[$i] = $this->wrapHolidayData();
            $i++;
        }
        return $holidaydata;
    }

    function getHolidaysInPeriod($start,$end) {
        $i=0;
        $sql = "SELECT * FROM semester_holiday WHERE beginn >= '".$start."' AND ende <= '".$end."'";
        if (!$this->db->query($sql)) {
            echo "Error! Query not succeeded in getHolidayInPeriod!";
            die();
        }
        if ($this->db->num_rows()==0) {
            return array();
        }
        while ($this->db->next_record()) {
            $holidaydata[$i] = $this->wrapHolidayData();
            $i++;
        }
        return $holidaydata;
    }

    function deleteHoliday($holiday_id) {
        $sql = "DELETE FROM semester_holiday WHERE holiday_id = '".$holiday_id."'";
        if (!$this->db->query($sql)) {
            echo "Error! Query (deleteHoliday) not succeeded";
            die();
        }
        return 1;
    }

    function getHolidayData($holiday_id) {
        $sql = "SELECT * FROM semester_holiday WHERE holiday_id='".$holiday_id."'";
        if (!$this->db->query($sql)) {
            echo "Error! Query (getHolidayData) not succeeded";
            die();
        }
        if ($this->db->num_rows()==0) {
            return array();
        }
        $this->db->next_record();
        return $this->wrapHolidayData();
    }

    function insertNewHoliday($holidaydata) {
        $holiday_id = md5(uniqid("Legolas"));
        $sql =  "INSERT INTO semester_holiday (holiday_id,semester_id,name,description,beginn,ende) ".
                "VALUES ('".$holiday_id."','1','".$holidaydata["name"]."','".$holidaydata["description"]."','".$holidaydata["beginn"]."','".$holidaydata["ende"]."')";
        if (!$this->db->query($sql)) {
            echo "Error! Query (insertNewHoliday) not succeeded";
            die();
        }
        return $holiday_id;
    }

    function updateExistingHoliday($holidaydata) {
        $sql =  "UPDATE semester_holiday SET ".
                "name = '".$holidaydata["name"]."', ".
                "description = '".$holidaydata["description"]."', ".
                "beginn = '".$holidaydata["beginn"]."', ".
                "ende = '".$holidaydata["ende"]."' ".
                "WHERE holiday_id='".$holidaydata["holiday_id"]."'";
        if (!$this->db->query($sql)) {
            echo "Error! Query (updateExistingHoliday) not succeeded";
            die();
        }
        return 1;
    }

    function wrapHolidayData() {
        $holidaydata = array();
        $holidaydata["holiday_id"]  = $this->db->f("holiday_id");
        $holidaydata["name"]        = $this->db->f("name");
        $holidaydata["description"] = $this->db->f("description");
        $holidaydata["beginn"]  = $this->db->f("beginn");
        $holidaydata["ende"] = $this->db->f("ende");
        return $holidaydata;
    }
}
