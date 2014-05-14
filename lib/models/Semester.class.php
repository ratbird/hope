<?php
# Lifter010: TODO
/**
 * Semester.class.php
 * model class for table semester_data
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
 * @property string semester_id database column
 * @property string id alias column for semester_id
 * @property string name database column
 * @property string description database column
 * @property string semester_token database column
 * @property string beginn database column
 * @property string ende database column
 * @property string vorles_beginn database column
 * @property string vorles_ende database column
 * @property string first_sem_week computed column
 * @property string last_sem_week computed column
 * @property string past computed column
 */
class Semester extends SimpleORMap
{
    /**
     * cache
     */
    private static $semester_cache;
    private static $current_semester;

    /**
     * returns semester object for given id or null
     * @param string $id
     * @return NULL|Semester
     */
    static function find($id)
    {
        $semester_cache = self::getAll();
        return isset($semester_cache[$id]) ? $semester_cache[$id] : null;
    }

    /**
     * returns Semester for given timestamp
     * @param integer $timestamp
     * @return null|Semester
     */
    static function findByTimestamp($timestamp)
    {
        foreach(self::getAll() as $semester) {
            if ($timestamp >= $semester->beginn && $timestamp <= $semester->ende) {
                $found = $semester;
                break;
            }
        }
        return $found;
    }

    /**
     * returns following Semester for given timestamp
     * @param integer $timestamp
     * @return null|Semester
     */
    static function findNext($timestamp = null)
    {
        $timestamp = $timestamp OR $timestamp = time();
        $semester = self::findByTimestamp($timestamp);
        if ($semester) {
            return self::findByTimestamp($semester->ende + 1);
        } else {
            return null;
        }
    }

    /**
     * returns current Semester
     */
    static function findCurrent()
    {
        self::getAll();
        return self::$current_semester;
    }

    /**
     * returns array of all existing semester objects
     * orderd by begin
     * @param boolean $force_reload
     * @return array
     */
    static function getAll($force_reload = false)
    {
        if (!is_array(self::$semester_cache) || $force_reload) {
            self::$semester_cache = array();
            foreach(self::findBySql('1 ORDER BY beginn') as $semester){
                self::$semester_cache[$semester->getId()] = $semester;
                if (time() >= $semester->beginn && time() < $semester->ende) {
                    self::$current_semester = $semester;
                }
            }
        }
        return self::$semester_cache;
    }

    protected static function configure()
    {
        $config['db_table'] = 'semester_data';
        $config['default_values']['description'] = '';
        $config['additional_fields']['first_sem_week'] = true;
        $config['additional_fields']['last_sem_week'] = true;
        $config['additional_fields']['past'] = true;
        parent::configure($config);
    }

    function getfirst_sem_week()
    {
        return (int)strftime('%W', $this['vorles_beginn']);
    }

    function getlast_sem_week()
    {
        return (int)strftime('%W', $this['vorles_ende']);
    }

    function getpast()
    {
        return $this['ende'] < time();
    }

    /**
     * returns "Semesterwoche" for a given timestamp
     * @param integer $timestamp
     * @return number|boolean
     */
    function getSemWeekNumber($timestamp)
    {
        $current_sem_week = (int)strftime('%W', $timestamp);
        if(strftime('%Y', $timestamp) > strftime('%Y', $this->vorles_beginn)){
            $current_sem_week += 52;
        }
        if($this->last_sem_week < $this->first_sem_week){
            $last_sem_week = $this->last_sem_week + 52;
        } else {
            $last_sem_week = $this->last_sem_week;
        }
        if($current_sem_week >= $this->first_sem_week && $current_sem_week <= $last_sem_week){
            return $current_sem_week - $this->first_sem_week + 1;
        } else {
            return false;
        }
    }

    /**
     * Return the number of continuous seminars in a semester
     *
     * @param md5 $id of the semester
     * @return int count of seminars
     */
    public static function countContinuousSeminars($id)
    {
        $semesterdata = self::find($id);

        $query = "SELECT COUNT(*)
                  FROM seminare
                  WHERE duration_time = -1 AND start_time < ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $semesterdata['beginn'],
        ));
        return $statement->fetchColumn();
    }

    /**
     * This method was adopted from the old version.
     *
     * @param md5 $id of the semester
     * @return int count of seminars
     */
    public static function countDurationSeminars($id)
    {
        $semesterdata = self::find($id);

        $query = "SELECT COUNT(*)
                  FROM seminare
                  WHERE duration_time > 0 AND start_time < ? AND
                    start_time+duration_time >= ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $semesterdata['beginn'],
            $semesterdata['ende']
        ));
        return $statement->fetchColumn();
    }

    /**
     * Returns the number of absolute seminars in a semester
     *
     * @param md5 $id of the semester
     * return int count of seminars
     */
    public static function countAbsolutSeminars($id)
    {
        $semesterdata = self::find($id);

        $query = "SELECT COUNT(*)
                  FROM seminare
                  WHERE start_time = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $semesterdata['beginn'],
        ));
        return $statement->fetchColumn();
    }

    /**
     * This method was adopted from the old version.
     *
     * @param md5 $id of the semester
     * return int count of seminars
     */
    public static function getAbsolutAndDurationSeminars($id)
    {
        return (int)self::countAbsolutSeminars($id) + self::countDurationSeminars($id);
    }
}
