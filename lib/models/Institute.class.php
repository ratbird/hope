<?php
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
 *
 * @property string institut_id database column
 * @property string id alias column for institut_id
 * @property string name database column
 * @property string fakultaets_id database column
 * @property string strasse database column
 * @property string plz database column
 * @property string url database column
 * @property string telefon database column
 * @property string email database column
 * @property string fax database column
 * @property string type database column
 * @property string modules database column
 * @property string mkdate database column
 * @property string chdate database column
 * @property string lit_plugin_name database column
 * @property string srienabled database column
 * @property string lock_rule database column
 * @property string is_fak computed column
 * @property SimpleORMapCollection members has_many InstituteMember
 * @property SimpleORMapCollection home_courses has_many Course
 * @property SimpleORMapCollection sub_institutes has_many Institute
 * @property Institute faculty belongs_to Institute
 * @property SimpleORMapCollection courses has_and_belongs_to_many Course
 */

class Institute extends SimpleORMap
{

    /**
     * returns array of instances of Institutes belonging to given faculty
     * @param string $fakultaets_id
     * @return array
     */
    static function findByFaculty($fakultaets_id)
    {
        return self::findBySQL("fakultaets_id=? AND fakultaets_id <> institut_id ORDER BY Name ASC", array($fakultaets_id));
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
     */
    protected static function configure()
    {
        $config['db_table'] = 'Institute';
        $config['additional_fields']['is_fak']['get'] = 'isFaculty';
        $config['has_many'] = array(
                'members' => array(
                        'class_name' => 'InstituteMember',
                        'assoc_func' => 'findByInstitute',
                        'on_delete' => 'delete',
                        'on_store' => 'store'),
                'home_courses' => array(
                        'class_name' => 'Course',
                        'on_delete' => 'delete',
                        'on_store' => 'store'),
                'sub_institutes' => array(
                        'class_name' => 'Institute',
                        'assoc_foreign_key' => 'fakultaets_id',
                        'assoc_func' => 'findByFaculty',
                        'on_delete' => 'delete',
                        'on_store' => 'store'),
                );
        $config['belongs_to'] = array(
                'faculty' => array(
                        'class_name' => 'Institute',
                        'foreign_key' => 'fakultaets_id',
                        )
                );
        $config['has_and_belongs_to_many'] = array(
                'courses' => array(
                        'class_name' => 'Course',
                        'thru_table' => 'seminar_inst',
                        'on_delete' => 'delete',
                        'on_store' => 'store'));
        parent::configure($config);
    }

    function isFaculty()
    {
        return $this->fakultaets_id == $this->institut_id;
    }

}
