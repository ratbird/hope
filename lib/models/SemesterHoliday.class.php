<?php
/**
 * SemesterHoliday.class.php
 * model class for table semester_holiday
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack <noack@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 *
 * @property string holiday_id database column
 * @property string id alias column for holiday_id
 * @property string semester_id database column
 * @property string name database column
 * @property string description database column
 * @property string beginn database column
 * @property string ende database column
 */

class SemesterHoliday extends SimpleORMap
{
    /**
     * cache
     */
    private static $holiday_cache;

    /**
     * returns SemesterHoliday object for given id or null
     * @param string $id
     * @return NULL|SemesterHoliday
     */
    static function find($id)
    {
        $holiday_cache = self::getAll();
        return isset($holiday_cache[$id]) ? $holiday_cache[$id] : null;
    }

     /**
     * returns all SemesterHoliday between given timestamps (starting AND ending within given timestamps)
     * @param integer $timestamp_start
     * @param integer $timestamp_end
     * @return array of SemesterHoliday
     */
    static function findByTimestampRange($timestamp_start, $timestamp_end)
    {
        $ret = array();
        if ($timestamp_start < $timestamp_end) {
            foreach(self::getAll() as $holiday) {
               if ($holiday->beginn >= $timestamp_start && $holiday->ende <= $timestamp_end) {
                    $ret[] = $holiday;
                }
            }
        }
        return $ret;
    }

    /**
     * returns all SemesterHolidays for given semester
     *
     * @param mixed semester object or id as string or assoc array
     * @return array of SemesterHoliday
     */
    static function findBySemester($semester)
    {
        $semester = Semester::toObject($semester);
        return self::findByTimestampRange($semester->beginn, $semester->ende);
    }

    /**
     * returns array of all existing SemesterHoliday objects
     * orderd by begin
     * @param boolean $force_reload
     * @return array
     */
    static function getAll($force_reload = false)
    {
        if (!is_array(self::$holiday_cache) || $force_reload) {
            self::$holiday_cache = array();
            foreach(self::findBySql('1 ORDER BY beginn') as $holiday){
                self::$holiday_cache[$holiday->getId()] = $holiday;
            }
        }
        return self::$holiday_cache;
    }

    protected static function configure()
    {
        $config['db_table'] = 'semester_holiday';
        $config['default_values']['description'] = '';
        parent::configure($config);
    }
}
