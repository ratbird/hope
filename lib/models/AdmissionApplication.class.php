<?php
/**
 * AdmissionApplication.class.php
 * model class for table admission_seminar_user
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Andr� Noack <noack@data-quest.de>
 * @copyright   2012 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 *
 * @property string user_id database column
 * @property string seminar_id database column
 * @property string status database column
 * @property string mkdate database column
 * @property string position database column
 * @property string comment database column
 * @property string visible database column
 * @property string vorname computed column
 * @property string nachname computed column
 * @property string username computed column
 * @property string email computed column
 * @property string course_name computed column
 * @property string id computed column read/write
 * @property User user belongs_to User
 * @property Course course belongs_to Course
 */
class AdmissionApplication extends SimpleORMap
{


    public static function findByCourse($course_id)
    {
        $db = DbManager::get();
        return $db->fetchAll("SELECT admission_seminar_user.*, aum.vorname,aum.nachname,aum.email,
                             aum.username,ui.title_front,ui.title_rear
                             FROM admission_seminar_user
                             LEFT JOIN auth_user_md5 aum USING (user_id)
                             LEFT JOIN user_info ui USING (user_id)
                             WHERE seminar_id = ? ORDER BY position",
                             array($course_id),
                             __CLASS__ . '::buildExisting');
    }

    public static function findByUser($user_id)
    {
        $db = DbManager::get();
        return $db->fetchAll("SELECT admission_seminar_user.*, seminare.Name as course_name
                             FROM admission_seminar_user
                             LEFT JOIN seminare USING (seminar_id)
                             WHERE user_id = ? ORDER BY seminare.Name",
                             array($user_id),
                             __CLASS__ . '::buildExisting');
    }

    protected static function configure($config = array())
    {
        $config['db_table'] = 'admission_seminar_user';
        $config['belongs_to']['user'] = array(
            'class_name' => 'User',
            'foreign_key' => 'user_id',
        );
        $config['belongs_to']['course'] = array(
            'class_name' => 'Course',
            'foreign_key' => 'seminar_id',
        );
        $config['additional_fields']['vorname'] = array('user', 'vorname');
        $config['additional_fields']['nachname'] = array('user', 'nachname');
        $config['additional_fields']['username'] = array('user', 'username');
        $config['additional_fields']['email'] = array('user', 'email');
        $config['additional_fields']['title_front'] = array('user', 'title_front');
        $config['additional_fields']['title_rear'] = array('user', 'title_rear');
        $config['additional_fields']['course_name'] = array();
        parent::configure($config);
    }

    function getUserFullname($format = "full")
    {
        return User::build(array_merge(array('motto' => ''), $this->toArray('vorname nachname username title_front title_rear')))->getFullname($format);
    }
}
