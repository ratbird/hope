<?php
/**
 * MyCoursesSearch.class.php
 * Search only in own courses.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Thomas Hackl <thomas.hackl@uni-passau.de>
 * @copyright   2015 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
*/

require_once 'lib/classes/searchtypes/SearchType.class.php';

class MyCoursesSearch extends StandardSearch
{
    public $search;

    private $perm_level;
    private $parameters;

    /**
     *
     * @param string $search
     * @param string $perm_level
     *
     * @return void
     */
    public function __construct($search, $perm_level = 'dozent', $parameters = array())
    {
        $this->avatarLike = $this->search = $search;
        $this->perm_level = $perm_level;
        $this->parameters = $parameters;
        $this->sql = $this->getSQL();
    }

    /**
     * returns an object of type SQLSearch with parameters to constructor
     *
     * @param string $search
     * @param string $perm_level
     *
     * @return SQLSearch
     */
    static public function get($search, $perm_level = 'dozent', $parameters = array())
    {
        return new MyCoursesSearch($search, $perm_level, $parameters);
    }

    /**
     * returns the title/description of the searchfield
     *
     * @return string title/description
     */
    public function getTitle()
    {
        return _('Veranstaltung suchen');
    }

    /**
     * returns the results of a search
     * Use the contextual_data variable to send more variables than just the input
     * to the SQL. QuickSearch for example sends all other variables of the same
     * <form>-tag here.
     * @param input string: the search-word(s)
     * @param contextual_data array: an associative array with more variables
     * @param limit int: maximum number of results (default: all)
     * @param offset int: return results starting from this row (default: 0)
     * @return array: array(array(), ...)
     */
    public function getResults($input, $contextual_data = array(), $limit = PHP_INT_MAX, $offset = 0)
    {
        $db = DBManager::get();
        $sql = $this->getSQL();
        if ($offset || $limit != PHP_INT_MAX) {
            $sql .= sprintf(' LIMIT %d, %d', $offset, $limit);
        }
        foreach ($this->parameters + $contextual_data as $name => $value) {
            if ($name !== "input" && strpos($sql, ":".$name) !== false) {
                if (is_array($value)) {
                    if (count($value)) {
                        $sql = str_replace(":".$name, implode(',', array_map(array($db, 'quote'), $value)), $sql);
                    } else {
                        $sql = str_replace(":".$name, "''", $sql);
                    }
                } else {
                    $sql = str_replace(":".$name, $db->quote($value), $sql);
                }
            }
        }
        $statement = $db->prepare($sql, array(PDO::FETCH_NUM));
        $data = array();
        $data[":input"] = "%".$input."%";
        $statement->execute($data);
        $results = $statement->fetchAll();
        return $results;
    }

    /**
     * returns a sql-string appropriate for the searchtype of the current class
     *
     * @return string
     */
    private function getSQL()
    {
        $semnumber = Config::get()->IMPORTANT_SEMNUMBER;
        $name = $semnumber
            ? "CONCAT(TRIM(CONCAT_WS(' ', s.`VeranstaltungsNummer`, s.`Name`)), ' (', sem.`name`, ')')"
            : "CONCAT(s.`Name`, ' (', sem.`name`, ')')";
        switch ($this->perm_level) {
            // Roots see everything, everywhere.
            case 'root':
                $query = "SELECT DISTINCT s.`Seminar_id`, ".$name."
                    FROM `seminare` s
                        JOIN `semester_data` sem ON (
                            s.`start_time` + s.`duration_time` BETWEEN sem.`beginn` AND sem.`ende`
                            OR (s.`duration_time` = -1 AND s.`start_time` <= sem.`ende`))
                    WHERE (s.`VeranstaltungsNummer` LIKE :input
                            OR s.`Name` LIKE :input)
                        AND s.`status` NOT IN (:semtypes)
                        AND s.`Seminar_id` NOT IN (:exclude)";
                if ($semnumber) {
                    $query .= " ORDER BY sem.`beginn` DESC, s.`VeranstaltungsNummer`, s.`Name`";
                } else {
                    $query .= " ORDER BY sem.`beginn` DESC, s.`VeranstaltungsNummer`, s.`Name`";
                }
                return $query;
            // Admins see everything at their assigned institutes.
            case 'admin':
                $query = "SELECT DISTINCT s.`Seminar_id`, ".$name."
                    FROM `seminare` s
                        JOIN `semester_data` sem ON (
                            s.`start_time` + s.`duration_time` BETWEEN sem.`beginn` AND sem.`ende`
                            OR (s.`duration_time` = -1 AND s.`start_time` <= sem.`ende`))
                        JOIN `seminar_inst` si ON (s.`Seminar_id` = si.`seminar_id`)
                    WHERE (s.`VeranstaltungsNummer` LIKE :input
                            OR s.`Name` LIKE :input)
                        AND s.`status` NOT IN (:semtypes)
                        AND si.`institut_id` IN (:institutes)
                        AND s.`Seminar_id` NOT IN (:exclude)";
                if ($semnumber) {
                    $query .= " ORDER BY sem.`beginn` DESC, s.`VeranstaltungsNummer`, s.`Name`";
                } else {
                    $query .= " ORDER BY sem.`beginn` DESC, s.`VeranstaltungsNummer`, s.`Name`";
                }
                return $query;
            // Lecturers see their own courses.
            case 'dozent':
                $query = "SELECT DISTINCT s.`Seminar_id`, ".$name.", sem.`beginn`, s.`VeranstaltungsNummer`, s.`Name`
                    FROM `seminare` s
                        JOIN `seminar_user` su ON (s.`Seminar_id`=su.`Seminar_id`)
                        JOIN `semester_data` sem ON (
                            s.`start_time` + s.`duration_time` BETWEEN sem.`beginn` AND sem.`ende`
                            OR (s.`duration_time` = -1 AND s.`start_time` <= sem.`ende`))
                    WHERE (s.`VeranstaltungsNummer` LIKE :input
                            OR s.`Name` LIKE :input)
                        AND su.`user_id` = :userid
                        AND su.`status` = 'dozent'
                        AND s.`status` NOT IN (:semtypes)
                        AND s.`Seminar_id` NOT IN (:exclude)";
                if (Config::get()->DEPUTIES_ENABLE) {
                    $query .= " UNION
                        SELECT DISTINCT s.`Seminar_id`, ".$name.", sem.`beginn`, s.`VeranstaltungsNummer`, s.`Name`
                        FROM `seminare` s
                            JOIN `deputies` d ON (s.`Seminar_id` = d.`range_id`)
                            JOIN `semester_data` sem ON (
                                s.`start_time` + s.`duration_time` BETWEEN sem.`beginn` AND sem.`ende`
                                OR (s.`duration_time` = -1 AND s.`start_time` <= sem.`ende`))
                        WHERE (s.`VeranstaltungsNummer` LIKE :input
                                OR s.`Name` LIKE :input)
                            AND d.`user_id` = :userid
                            AND s.`Seminar_id` NOT IN (:exclude)";
                }
                if ($semnumber) {
                    $query .= " ORDER BY `beginn` DESC, `VeranstaltungsNummer`, `Name`";
                } else {
                    $query .= " ORDER BY `beginn` DESC, `Name`";
                }
                return $query;
        }
    }

    /**
     * A very simple overwrite of the same method from SearchType class.
     * returns the absolute path to this class for autoincluding this class.
     *
     * @return: path to this class
     */
    public function includePath()
    {
        return __FILE__;
    }
}
