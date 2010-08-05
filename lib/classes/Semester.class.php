<?php
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
 * @copyright   2010 Stud.IP Core-Group
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

    protected $db_table = 'semester_data';

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
}
