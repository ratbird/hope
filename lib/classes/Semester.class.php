<?php
# Lifter010: TODO
/*
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
*/
require_once "lib/classes/SimpleORMap.class.php";

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
     * returns array of Semester objects
     * @param string $where sql clause
     * @return array
     */
    static function findBySql($where)
    {
        return SimpleORMap::findBySql(__CLASS__, $where);
    }

    /**
     * delete Semester in db
     * @param string $where sql clause
     * @return number
     */
    static function deleteBySQL($where)
    {
        return SimpleORMap::deleteBySQL(__CLASS__, $where);
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

    /**
     * returns user object for given id or null
     * the param could be a string, an assoc array containing primary key field
     * or an already matching object. In all these cases an object is returned
     *
     * @param mixed $id_or_object id as string, object or assoc array
     * @return Semester
     */
    public static function toObject($id_or_object)
    {
        return SimpleORMap::toObject(__CLASS__, $id_or_object);
    }
    /**
     *
     * @param string $id primary key of table
     */
    function __construct($id = null)
    {
        $this->db_table = 'semester_data';
        parent::__construct($id);
    }

    /* (non-PHPdoc)
     * @see lib/classes/SimpleORMap::setData()
     */
    function setData($data, $reset = false)
    {
        parent::setData($data, $reset);
        if(!empty($data)){
            $this->content['past'] = $data['ende'] < time();
            $this->content['first_sem_week'] = (int)strftime('%W', $data['vorles_beginn']);
            $this->content['last_sem_week'] = (int)strftime('%W', $data['vorles_ende']);
        }
        return $this->haveData();
    }

    /* (non-PHPdoc)
     * @see lib/classes/SimpleORMap::toArray()
     */
    function toArray()
    {
        $ret = parent::toArray();
        foreach(array('past', 'first_sem_week', 'last_sem_week') as $additional) {
            $ret[$additional] = $this->$additional;
        }
        return $ret;
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
        $semesters = SemesterData::getInstance()->getAllSemesterData();
        $seminars = DBManager::get()->query("SELECT start_time FROM seminare WHERE duration_time = -1")->fetchAll(PDO::FETCH_COLUMN);
        $continuous_seminars = array();
        foreach ($semesters as $semester) {
            $continuous_seminars[$semester['semester_id']] = 0;
        }

        foreach ($seminars as $seminar) {
            foreach ($semesters as $i => $semester) {
                if (($seminar >= $semester["beginn"]) && ($seminar < $semester["ende"])) {
                    for ($j=$i; $j < count($semesters); $j++) {
                        $continuous_seminars[$semesters[$j]["semester_id"]]++;
                    }
                }
            }
        }
        return $continuous_seminars[$id];
    }

    /**
     * This method was adopted from the old version.
     *
     * @param md5 $id of the semester
     * @return int count of seminars
     */
    public static function countDurationSeminars($id)
    {
        $semesters = SemesterData::getInstance()->getAllSemesterData();
        $duration_seminars = array();
        foreach ($semesters as $semester) {
            $duration_seminars[$semester['semester_id']] = 0;
        }

        $sql =  "SELECT start_time, duration_time FROM seminare WHERE ".
                "duration_time != 0 AND duration_time != -1";
        $seminars = DBManager::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($seminars as $seminar) {
            $endtime = $seminar["start_time"] + $seminar["duration_time"];
            foreach ($semesters as $i => $semester) {
                if ($seminar["start_time"] >= $semester["beginn"] && $seminar["start_time"] < $semester["ende"]) {
                    for ($j=$i; $j<count($semesters); $j++) {
                        if ($endtime >= $semesters[$j]["beginn"]) {
                            $duration_seminars[$semesters[$j]["semester_id"]]++;
                        }
                    }
                }
            }
        }

        return (int)$duration_seminars[$id];
    }

    /**
     * Returns the number of absolute seminars in a semester
     *
     * @param md5 $id of the semester
     * return int count of seminars
     */
    public static function countAbsolutSeminars($id)
    {
        $semesterdata = SemesterData::getInstance()->getSemesterData($id);

        $sql = "SELECT COUNT(*) FROM seminare WHERE "
              ."start_time >= '".$semesterdata["beginn"]."' "
              ."AND start_time <= '".$semesterdata["ende"]."' "
              ."AND duration_time = '0'";
        return DBManager::get()->query($sql)->fetchColumn();
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
