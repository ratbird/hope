<?php
# Lifter010: TODO
/**
 * UserLookup.class.php
 * provides an easy to look up user ids by certain filter criteria
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       2.1
*/

final class UserLookup
{
    // At the moment, the cache is only used for the GetValuesForType method
    const USE_CACHE = true;
    const CACHE_DURATION = 3600; // 1 hour

    const FLAG_SORT_NAME = 1;
    const FLAG_RETURN_FULL_INFO = 2;

    /**
     * Predefined array of filter criteria
     *
     * @var array
     */
    protected static $types = array(
        'abschluss' => array(
            'filter' => 'UserLookup::AbschlussFilter',
            'values' => 'UserLookup::AbschlussValues',
        ),
        'fach' => array(
            'filter' => 'UserLookup::FachFilter',
            'values' => 'UserLookup::FachValues',
        ),
        'fachsemester' => array(
            'filter' => 'UserLookup::FachsemesterFilter',
            'values' => 'UserLookup::FachsemesterValues',
        ),
        'institut' => array(
            'filter' => 'UserLookup::InstitutFilter',
            'values' => 'UserLookup::InstitutValues',
        ),
        'status' => array(
            'filter' => 'UserLookup::StatusFilter',
            'values' => 'UserLookup::StatusValues',
        ),
    );

    /**
     * Contains the resulting filter set
     * @var array
     */
    private $filters = array();

    /**
     * Adds another type filter to the set of current filters.
     *
     * Multiple filters for the same filter type result in an AND filter
     * within this type while multiple filters across filter types result
     * in an OR filter across these types.
     *
     * @param  string $type
     * @param  string $value
     * @return UserLookup
     */
    public function set_filter($type, $value)
    {
        if (!array_key_exists($type, self::$types)) {
            throw new Exception('[UserLookup] Cannot set filter for unknown type "'.$type.'"');
        }

        if (!isset($this->filters[$type])) {
            $this->filters[$type] = array();
        }

        $this->filters[$type] = array_merge($this->filters[$type], (array)$value);

        return $this;
    }

    /**
     * Executes the actual lookup by executing all individual filter types
     * and returning the intersection of all according result sets.
     *
     * Possible flags:
     *  - FLAG_SORT_NAME         Sorts the user ids in the result by the
     *                           actual user names
     *  - FLAG_RETURN_FULL_INFO  Returns rudimental user info instead of just
     *                           the ids (as an array with the user id as key
     *                           and an array containting the info as value)
     *
     * @param  int $flags optional set of flags
     * @return array
     */
    public function execute($flags = null)
    {
        if (count($this->filters) === 0) {
            throw new Exception('[UserLookup] Cannot execute empty filter set');
        }

        $result = null;
        foreach ($this->filters as $type => $values) {
            $temp_result = call_user_func(self::$types[$type]['filter'], $values);

            if ($result === null) {
                $result = $temp_result;
            } else {
                $result = array_intersect($result, $temp_result);
            }
        }

        if (($flags & self::FLAG_SORT_NAME) and !($flags & self::FLAG_RETURN_FULL_INFO)) {
            $temp_result = self::ArrayQuery("SELECT user_id FROM auth_user_md5 WHERE user_id IN (??) ORDER BY Nachname ASC, Vorname ASC", $result);
            $result = $temp_result->fetchAll(PDO::FETCH_COLUMN);
        }

        if (!empty($result) and ($flags & self::FLAG_RETURN_FULL_INFO)) {
            $query = "SELECT user_id, username, Vorname, Nachname, Email, perms FROM auth_user_md5 WHERE user_id IN (??)";
            if ($flags & self::FLAG_SORT_NAME) {
                $query .= " ORDER BY Nachname ASC, Vorname ASC";
            }

            $temp_result = self::ArrayQuery($query, $result);
            $result = $temp_result->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
            $result = array_map('reset', $result);
        }

        return $result;
    }

    /**
     * Clears all defined filters.
     *
     * @return UserLookup
     */
    public function clear_filters()
    {
        $this->filters = array();
        return $this;
    }

    /**
     * Adds or updates a filter criterion the global set of criteria.
     *
     * @param string   $name            Name of the criterion type
     * @param callback $values_callback Callback for the type's values
     * @param callback $filter_callback Actual filter callback for a defined
     *                                  set of needles
     */
    public static function AddType($name, $values_callback, $filter_callback)
    {
        if (!is_callable($values_callback)) {
            throw new Exception('[UserLookup] Values callback for type "'.$name.'" is not callable');
        }
        if (!is_callable($filter_callback)) {
            throw new Exception('[UserLookup] Filter callback for type "'.$name.'" is not callable');
        }

        self::$types[$name] = array(
            'filter' => $filter_callback,
            'values' => $values_callback,
        );
    }

    /**
     * Returns all valid values for a certain criterion type.
     *
     * @param  string $type Name of the criterion type
     * @return array  Associative array containing the values as keys and
     *                descriptive names as values
     */
    public static function GetValuesForType($type)
    {
        if (!array_key_exists($type, self::$types)) {
            throw new Exception('[UserLookup] Unknown type "'.$type.'"');
        }

        if (self::USE_CACHE) {
            $cache = StudipCacheFactory::getCache();
            $cache_key = 'UserLookup/'.$type.'/values';
            $cached_values = $cache->read($cache_key);
            if ($cached_values) {
                return unserialize($cached_values);
            }
        }

        $values = call_user_func(self::$types[$type]['values']);

        if (self::USE_CACHE) {
            $cache->write($cache_key, serialize($values), self::CACHE_DURATION);
        }

        return $values;
    }

    /**
     * Convenience method to query over an array via ".. WHERE x IN (<array>)".
     * Any ?? is substituted by the array of values.
     *
     * @param  string $query  The query to execute
     * @param  array  $values Array containing the values to search for
     * @return PDOStatement
     */
    protected static function ArrayQuery($query, $values)
    {
        $values = array_unique($values);
        $values = array_map(array(DBManager::Get(), 'quote'), $values);
        $query = str_replace('??', implode(',', $values), $query);

        return DBManager::Get()->query($query);
    }

    /**
     * Return all user with studiengang_id -$needles-
     * @param $needles  //studiengang_id
     */
    protected static function FachFilter($needles)
    {
        $db_result = self::ArrayQuery("SELECT user_id FROM user_studiengang WHERE studiengang_id IN (??)", $needles);
        return $db_result->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Return all studycourses
     */
    protected static function FachValues()
    {
        $db_result = DBManager::Get()->query("SELECT studiengang_id, name FROM studiengaenge ORDER BY name COLLATE latin1_german1_ci ASC");
        $result = $db_result->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_COLUMN);
        return array_map('reset', $result);
    }

    /**
     * Return all user with abschluss_id -$needles-
     * @param $needles //abschluss_id
     */
    protected static function AbschlussFilter($needles)
    {
        $result = self::ArrayQuery("SELECT user_id FROM user_studiengang WHERE abschluss_id IN (??)", $needles);
        return $result->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Return all studydegrees
     */
    protected static function AbschlussValues()
    {
        $db_result = DBManager::Get()->query("SELECT abschluss_id, name FROM abschluss ORDER BY name COLLATE latin1_german1_ci ASC");
        $result = $db_result->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_COLUMN);
        return array_map('reset', $result);
    }

    /**
     * Return all user_id's in one semester ($needles)
     * @param $needles //semester
     */
    protected static function FachsemesterFilter($needles)
    {
        $result = self::ArrayQuery("SELECT user_id FROM user_studiengang WHERE semester IN (??)", $needles);
        return $result->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Create a array with all possible values for studysemesters
     */
    protected static function FachsemesterValues()
    {
        $db_result = DBManager::Get()->query("SELECT MAX(semester) FROM user_studiengang");
        $max = $db_result->fetchColumn();
        $values = range(1, $max);
        return array_combine($values, $values);
    }

    /**
     * Return all user_id's from this institut
     * @param $needles //institut_id
     */
    protected static function InstitutFilter($needles)
    {
        $result = self::ArrayQuery("SELECT user_id FROM user_inst WHERE Institut_id IN (??)", $needles);
        return $result->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Return all faculty's and instituts
     */
    protected static function InstitutValues()
    {
        $db_result = DBManager::Get()->query("SELECT fakultaets_id, Institut_id, Name, fakultaets_id = Institut_id AS is_fakultaet FROM Institute ORDER BY Institut_id = fakultaets_id DESC, Name COLLATE latin1_german1_ci ASC")->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

        $result = array();
        foreach ($db_result as $fakultaets_id => $items) {
            foreach ($items as $item) {
                if (!isset($result[$fakultaets_id])) {
                    $result[$fakultaets_id] = array(
                        'name'   => $item['Name'],
                        'values' => array(),
                    );
                } else {
                    $result[$fakultaets_id]['values'][$item['Institut_id']] = $item['Name'];
                }
            }
        }
        return $result;
    }

    /**
     * Return all user_id's with the status ($needles)
     * @param $needles //user-status(perms)
     */
    protected static function StatusFilter($needles)
    {
        $result = self::ArrayQuery("SELECT user_id FROM auth_user_md5 WHERE perms IN (??)", $needles);
        return $result->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * In array with all studip-status (perms)
     */
    protected static function StatusValues()
    {
        return array(
            'autor'  => _('Autor'),
            'tutor'  => _('Tutor'),
            'dozent' => _('Dozent'),
            'admin'  => _('Admin'),
            'root'   => _('Root'),
        );
    }
}
