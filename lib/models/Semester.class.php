<?php
/**
 * Semester.class.php
 * model class for table semester_data
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Andr� Noack <noack@data-quest.de>
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
     * Configures this model.
     *
     * @param Array $config
     */
    protected static function configure($config = array())
    {
        $config['db_table'] = 'semester_data';

        $config['default_values']['description']    = '';
        $config['default_values']['semester_token'] = '';

        $config['additional_fields']['first_sem_week'] = true;
        $config['additional_fields']['last_sem_week'] = true;
        $config['additional_fields']['current'] = true;
        $config['additional_fields']['past'] = true;

        $config['additional_fields']['absolute_seminars_count'] = array(
            'get' => 'seminar_counter',
            'set' => false,
        );
        $config['additional_fields']['duration_seminars_count'] = array(
            'get' => 'seminar_counter',
            'set' => false,
        );
        $config['additional_fields']['continuous_seminars_count'] = array(
            'get' => 'seminar_counter',
            'set' => false,
        );

        parent::configure($config);
    }

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
    public static function find($id)
    {
        $semester_cache = self::getAll();
        return $semester_cache[$id] ?: null;
    }

    /**
     * returns Semester for given timestamp
     * @param integer $timestamp
     * @return null|Semester
     */
    public static function findByTimestamp($timestamp)
    {
        foreach(self::getAll() as $semester) {
            if ($timestamp >= $semester->beginn && $timestamp <= $semester->ende) {
                return $semester;
            }
        }
        return null;
    }

    /**
     * returns following Semester for given timestamp
     * @param integer $timestamp
     * @return null|Semester
     */
    public static function findNext($timestamp = null)
    {
        $timestamp = $timestamp OR $timestamp = time();
        $semester = self::findByTimestamp($timestamp);
        if ($semester) {
            return self::findByTimestamp($semester->ende + 1);
        }

        return null;
    }

    /**
     * returns current Semester
     */
    public static function findCurrent()
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
    public static function getAll($force_reload = false)
    {
        if (!is_array(self::$semester_cache) || $force_reload) {
            self::$semester_cache = array();
            foreach(self::findBySql('1 ORDER BY beginn') as $semester){
                self::$semester_cache[$semester->getId()] = $semester;
                if ($semester->current) {
                    self::$current_semester = $semester;
                }
            }
        }
        return self::$semester_cache;
    }

    /**
     * Caches seminar counts
     */
    protected $seminar_counts = null;

    /**
     * Counts the number of different seminar types in this semester.
     * This method caches the result in $seminar_counts so the db
     * will only be queried once per semester.
     *
     * @param String $field Name of the seminar (/additional_fields) type
     * @return int The count of seminars of this type
     */
    protected function seminar_counter($field)
    {
        if ($this->seminar_counts === null) {
            $query = "SELECT SUM(duration_time = -1 AND start_time < :beginn) AS continuous,
                             SUM(duration_time > 0 AND start_time < :beginn AND start_time + duration_time >= :ende) AS duration,
                             SUM(start_time = :beginn) AS absolute
                      FROM seminare
                      WHERE start_time <= :beginn";
            $statement = DBManager::get()->prepare($query);
            $statement->bindValue(':beginn', $this['beginn']);
            $statement->bindValue(':ende', $this['ende']);
            $statement->execute();
            $this->seminar_counts = $statement->fetch(PDO::FETCH_ASSOC);
        }
        
        $index = str_replace('_seminars_count', '', $field);
        return (int)$this->seminar_counts[$index];
    }

    /**
     * Returns the calendar week number of the first week of the lecture
     * period.
     *
     * @return int Calendar week number of the first week of lecture
     */
    public function getfirst_sem_week()
    {
        return (int)strftime('%W', $this['vorles_beginn']);
    }

    /**
     * Returns the calendar week number of the last week of the lecture
     * period.
     *
     * @return int Calendar week number of the last week of lecture
     */
    public function getlast_sem_week()
    {
        return (int)strftime('%W', $this['vorles_ende']);
    }

    /**
     * Return whether this semester is in the past.
     *
     * @return bool Indicating whether this semester is in the past
     */
    public function getpast()
    {
        return $this['ende'] < time();
    }

    /**
     * Returns whether this semester is the current semester.
     *
     * @return bool Indicating if this is the current semester
     */
    public function getcurrent()
    {
        return time() >= $this->beginn && time() < $this->ende;
    }

    /**
     * returns "Semesterwoche" for a given timestamp
     * @param integer $timestamp
     * @return number|boolean
     */
    public function getSemWeekNumber($timestamp)
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
        }

        return false;
    }
}
