<?php
/**
 * InstituteMember
 * model class for table user_inst
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack <noack@data-quest.de>
 * @copyright   2012 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 *
 * @property string user_id database column
 * @property string institut_id database column
 * @property string inst_perms database column
 * @property string sprechzeiten database column
 * @property string raum database column
 * @property string telefon database column
 * @property string fax database column
 * @property string externdefault database column
 * @property string priority database column
 * @property string visible database column
 * @property string vorname computed column
 * @property string nachname computed column
 * @property string username computed column
 * @property string email computed column
 * @property string title_front computed column
 * @property string title_rear computed column
 * @property string institute_name computed column
 * @property string id computed column read/write
 * @property SimpleORMapCollection datafields has_many DatafieldEntryModel
 * @property User user belongs_to User
 * @property Institute institute belongs_to Institute
 */
class InstituteMember extends SimpleORMap
{

    protected static function configure($config = array())
    {
        $config['db_table'] = 'user_inst';
        $config['belongs_to']['user'] = array(
            'class_name' => 'User',
            'foreign_key' => 'user_id',
        );
        $config['belongs_to']['institute'] = array(
            'class_name' => 'Institute',
            'foreign_key' => 'institut_id',
        );
        $config['has_many']['datafields'] = array(
            'class_name' => 'DatafieldEntryModel',
            'assoc_foreign_key' =>
                function($model, $params) {
                    $model->setValue('range_id', $params[0]->user_id);
                    $model->setValue('sec_range_id', $params[0]->institut_id);
                },
            'assoc_func' => 'findByModel',
            'on_delete' => 'delete',
            'on_store' => 'store',
            'foreign_key' =>
                function($institute_member) {
                    return array($institute_member);
                }
        );
        $config['additional_fields']['vorname'] = array('user', 'vorname');
        $config['additional_fields']['nachname'] = array('user', 'nachname');
        $config['additional_fields']['username'] = array('user', 'username');
        $config['additional_fields']['email'] = array('user', 'email');
        $config['additional_fields']['title_front'] = array('user', 'title_front');
        $config['additional_fields']['title_rear'] = array('user', 'title_rear');
        $config['additional_fields']['institute_name'] = array();
        parent::configure($config);
    }

    public static function findByInstitute($institute_id)
    {
        $db = DbManager::get();
        return $db->fetchAll("SELECT user_inst.*, aum.vorname,aum.nachname,aum.email,
                             aum.username,ui.title_front,ui.title_rear
                             FROM user_inst
                             LEFT JOIN auth_user_md5 aum USING (user_id)
                             LEFT JOIN user_info ui USING (user_id)
                             WHERE institut_id = ? AND inst_perms <> 'user' ORDER BY inst_perms,nachname",
                             array($institute_id),
                             __CLASS__ . '::buildExisting');
    }

    public static function findByInstituteAndStatus($institute_id, $status)
    {
        $db = DbManager::get();
        return $db->fetchAll("SELECT user_inst.*, aum.vorname,aum.nachname,aum.email,
                             aum.username,ui.title_front,ui.title_rear
                             FROM user_inst
                             LEFT JOIN auth_user_md5 aum USING (user_id)
                             LEFT JOIN user_info ui USING (user_id)
                             WHERE institut_id = ? AND seminar_user.status IN(?) ORDER BY inst_perms,nachname",
                             array($institute_id, is_array($status) ? $status : words($status)),
                             __CLASS__ . '::buildExisting');
    }

    public static function findByUser($user_id)
    {
        $db = DbManager::get();
        return $db->fetchAll("SELECT user_inst.*, Institute.Name as institute_name
                             FROM user_inst
                             LEFT JOIN Institute USING (institut_id)
                             WHERE user_id = ? ORDER BY priority,Institute.Name",
                             array($user_id),
                             __CLASS__ . '::buildExisting');
    }

        function getUserFullname($format = "full")
    {
        return User::build(array_merge(array('motto' => ''), $this->toArray('vorname nachname username title_front title_rear')))->getFullname($format);
    }
}
