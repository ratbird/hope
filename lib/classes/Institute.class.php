<?php
/**
 * Institute.class.php
 * model class for table Institute
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Rasmus Fuhse <fuhse@data-quest>, Suchi & Berg GmbH <info@data-quest.de>
 * @copyright   2010 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
*/

require_once 'SimpleORMap.class.php';

class Institute extends SimpleORMap
{
    protected $db_table = 'Institute';

    static function find($id)
    {
        return SimpleORMap::find(__CLASS__, $id);
    }

    static function findBySql($where)
    {
        return SimpleORMap::findBySql(__CLASS__, $where);
    }

    static function findByFaculty($fakultaets_id)
    {
        return self::findBySql("fakultaets_id=" . DbManager::get()->quote($fakultaets_id) . " ORDER BY Name ASC");
    }

    static function deleteBySql($where)
    {
        return SimpleORMap::deleteBySql(__CLASS__, $where);
    }

    static function getInstitutes()
    {
        $db = DBManager::get();
        $result = $db->query("SELECT Institute.Institut_id, Institute.Name, IF(Institute.Institut_id=Institute.fakultaets_id,1,0) AS is_fak " .
                "FROM Institute " .
                    "LEFT JOIN Institute as fakultaet ON (Institute.fakultaets_id = fakultaet.Institut_id) " .
                "ORDER BY fakultaet.Name ASC, is_fak DESC, Institute.Name ASC")->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
    
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
                    "LEFT JOIN Institute as fakultaet ON (Institute.fakultaets_id = Institute.Institut_id) " .
                "WHERE (user_id = ".$db->quote($user_id)." " . 
                    "AND (inst_perms = 'dozent' OR inst_perms = 'tutor')) " . 
                "ORDER BY fakultaet.Name ASC, is_fak DESC, Institute.Name ASC")->fetchAll(PDO::FETCH_ASSOC);
        } else if (!$perm->have_perm("root")) {
            $result = $db->query("SELECT user_inst.Institut_id, Institute.Name, IF(user_inst.Institut_id=Institute.fakultaets_id,1,0) AS is_fak, user_inst.inst_perms " .
                "FROM user_inst " .
                    "LEFT JOIN Institute USING (institut_id) " .
                    "LEFT JOIN Institute as fakultaet ON (Institute.fakultaets_id = Institute.Institut_id) " .
                "WHERE (user_id = ".$db->quote($user_id)." AND inst_perms = 'admin') " .
                "ORDER BY fakultaet.Name ASC, is_fak DESC, Institute.Name ASC")->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $result = $db->query("SELECT Institute.Institut_id, Institute.Name, IF(Institute.Institut_id=Institute.fakultaets_id,1,0) AS is_fak, 'admin' AS inst_perms " .
                "FROM Institute " .
                    "LEFT JOIN Institute as fakultaet ON (Institute.fakultaets_id = fakultaet.Institut_id) " .
                "ORDER BY fakultaet.Name ASC, is_fak DESC, Institute.Name ASC")->fetchAll(PDO::FETCH_ASSOC);
        }
        return $result;
    }

    function getValue($field)
    {
        if ($field == 'is_fak') {
            return $this->fakultaets_id == $this->institut_id;
        }
        return parent::getValue($field);
    }

}
