<?php
# Lifter010: TODO
/**
 * Institute.class.php - model class for table Institute
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Rasmus Fuhse <fuhse@data-quest>
 * @author      Suchi & Berg GmbH <info@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       2.0
 */

require_once 'SimpleORMap.class.php';

class Institute extends SimpleORMap
{

    /**
     * returns new instance for given key
     * when found in db, else null
     * @param string $id
     * @return NULL|Institute
     */
    static function find($id)
    {
        return SimpleORMap::find(__CLASS__, $id);
    }

    /**
     * returns array of instances of Institutes filtered by given sql
     * @param string sql clause to use on the right side of WHERE
     * @return array
     */
    static function findBySql($where)
    {
        return SimpleORMap::findBySql(__CLASS__, $where);
    }

    /**
     * returns array of instances of Institutes belonging to given faculty
     * @param string $fakultaets_id
     * @return array
     */
    static function findByFaculty($fakultaets_id)
    {
        return self::findBySql("fakultaets_id=" . DbManager::get()->quote($fakultaets_id) . " ORDER BY Name ASC");
    }

    /**
     * deletes table rows in table Institute specified by given sql clause
     * @param string sql clause to use on the right side of WHERE
     * @return integer
     */
    static function deleteBySql($where)
    {
        return SimpleORMap::deleteBySql(__CLASS__, $where);
    }

    /**
     * returns an array of all institutes ordered by faculties and name
     * @return array
     */
    static function getInstitutes()
    {
        $db = DBManager::get();
        $result = $db->query("SELECT Institute.Institut_id, Institute.Name, IF(Institute.Institut_id=Institute.fakultaets_id,1,0) AS is_fak " .
                "FROM Institute " .
                    "LEFT JOIN Institute as fakultaet ON (Institute.fakultaets_id = fakultaet.Institut_id) " .
                "ORDER BY fakultaet.Name ASC, is_fak DESC, Institute.Name ASC")->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    /**
     * returns an array of all institutes to which the given user belongs,
     * ordered by faculties and name. The user role for each institute is included
     * @param string $user_id if omitted, the current user is used
     * @return array
     */
    static function getMyInstitutes($user_id = NULL)
    {
        global $perm, $user;
        if (!$user_id) {
            $user_id = $user->id;
        }
        $db = DBManager::get();
        if (!$perm->have_perm("admin")) {
            $result = $db->query("SELECT user_inst.Institut_id, Institute.Name, IF(user_inst.Institut_id=Institute.fakultaets_id,1,0) AS is_fak, user_inst.inst_perms " .
                "FROM user_inst " .
                    "LEFT JOIN Institute USING (institut_id) " .
                "WHERE (user_id = ".$db->quote($user_id)." " .
                    "AND (inst_perms = 'dozent' OR inst_perms = 'tutor')) " .
                "ORDER BY Institute.Name ASC")->fetchAll(PDO::FETCH_ASSOC);
        } else if (!$perm->have_perm("root")) {
            $result = $db->query("SELECT user_inst.Institut_id, Institute.Name, IF(user_inst.Institut_id=Institute.fakultaets_id,1,0) AS is_fak, user_inst.inst_perms " .
                "FROM user_inst " .
                    "LEFT JOIN Institute USING (institut_id) " .
                "WHERE (user_id = ".$db->quote($user_id)." AND inst_perms = 'admin') " .
                "ORDER BY Institute.Name ASC")->fetchAll(PDO::FETCH_ASSOC);
            if ($perm->is_fak_admin()) {
                foreach($result as $fak) {
                    $combined_result[] = $fak;
                    $institutes = $db->query("SELECT Institut_id, Name, 0 as is_fak, 'admin' as inst_perms 
                                              FROM Institute WHERE Institut_id <> fakultaets_id AND fakultaets_id = " . $db->quote($fak['Institut_id'])
                                             . " ORDER BY Institute.Name ASC")->fetchAll(PDO::FETCH_ASSOC);
                    $combined_result = array_merge($combined_result, $institutes);
                }
                $result = $combined_result;
            }
            
        } else {
            $result = $db->query("SELECT Institute.Institut_id, Institute.Name, IF(Institute.Institut_id=Institute.fakultaets_id,1,0) AS is_fak, 'admin' AS inst_perms " .
                "FROM Institute " .
                    "LEFT JOIN Institute as fakultaet ON (Institute.fakultaets_id = fakultaet.Institut_id) " .
                "ORDER BY fakultaet.Name ASC, is_fak DESC, Institute.Name ASC")->fetchAll(PDO::FETCH_ASSOC);
        }
        return $result;
    }

    /**
     *
     * @param string $id primary key of table
     */
    function __construct($id = null)
    {
        $this->db_table = 'Institute';
        parent::__construct($id);
    }

    function getValue($field)
    {
        if ($field == 'is_fak') {
            return $this->fakultaets_id == $this->institut_id;
        }
        return parent::getValue($field);
    }

}
