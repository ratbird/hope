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
require_once 'lib/classes/SemesterHoliday.class.php';

/**
 * old model class for table semester_holiday
 * use SemesterHoliday instead
 * @deprecated
 *
 */
class HolidayData
{

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
        return SemesterHoliday::getAll();
    }

    function HolidayData() {
    }

    function getAllHolidays() {
        return SemesterHoliday::getAll();
    }

    function getHolidaysInPeriod($start,$end) {
        return SemesterHoliday::findByTimestampRange($start, $end);
    }

    function getHolidayData($holiday_id) {
        return SemesterHoliday::find($holiday_id);
    }
}
