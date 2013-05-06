<?php
/**
 * User.class.php
 * model class for combined auth_user_md5/user_info record
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack <noack@data-quest.de>
 * @copyright   2011 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
*/

/**
 * this class represents one user, the attributes from tables
 * auth_user_md5 and user_info were merged.
 *
 * @code
 * $a_user = User::find($id);
 * $another_users_email = User::findByUsername($username)->email;
 * $a_user->email = $another_users_email;
 * $a_user->store();
 * @endcode
 *
 */
class User extends AuthUserMd5
{
    /**
     * return user object for given username
     *
     * @param string $username a username
     * @return User
     */
    public static function findByUsername($username)
    {
        $found = parent::findByUsername($username);
        return isset($found[0]) ? $found[0] : null;
    }

    /**
     * returns an array of User-objects that have the given value in the
     * given datafield.
     * @param string $datafield_id
     * @param array of User
     */
    public static function findByDatafield($datafield_id, $value)
    {
        $search = DBManager::get()->prepare(
            "SELECT range_id " .
            "FROM datafields_entries " .
            "WHERE datafield_id = :datafield_id " .
                "AND content = :value " .
        "");
        $search->execute(compact("datafield_id", "value"));
        $users = array();
        foreach ($search->fetchAll(PDO::FETCH_COLUMN, 0) as $user_id) {
            $users[] = new User($user_id);
        }
        return $users;
    }

    /**
     *
     * @param string $id a user id
     */
    function __construct($id = null)
    {
        $this->has_many = array(
                'course_memberships' => array(
                        'class_name' => 'CourseMember',
                        'on_delete' => 'delete',
                        'on_store' => 'store'),
                'institute_memberships' => array(
                        'class_name' => 'InstituteMember',
                        'on_delete' => 'delete',
                        'on_store' => 'store'),
                'admission_applications' => array(
                        'class_name' => 'AdmissionApplication',
                        'on_delete' => 'delete',
                        'on_store' => 'store'),
                'archived_course_memberships' => array(
                        'class_name' => 'ArchivedCourseMember',
                        'on_delete' => 'delete',
                        'on_store' => 'store'),
                'datafields' => array(
                        'class_name' => 'DatafieldEntryModel',
                        'assoc_foreign_key' =>
                        function($model,$params) {
                            $model->setValue('range_id', $params[0]->id);
                        },
                'assoc_func' => 'findByModel',
                'on_delete' => 'delete',
                'on_store' => 'store',
                'foreign_key' =>
                    function($model) {
                        return array($model);
                    })
        );
        $this->has_one['info'] = array(
                'class_name' => 'UserInfo',
                'on_delete' => 'delete',
                'on_store' => 'store');
        $info_getter = function ($record, $field) { return $record->info->getValue($field);};
        $info_setter = function ($record, $field, $value) { return $record->info->setValue($field, $value);};
        $info = new UserInfo();
        $info_meta = $info->getTableMetadata();
        foreach ($info_meta['fields'] as $field => $meta) {
            if ($field !== $info_meta['pk'][0]) {
                $this->additional_fields[$field] = array('get' => $info_getter, 'set' => $info_setter);
            }
        }
        parent::__construct($id);
    }

    /**
     * @see SimpleORMap::store()
     */
    function store()
    {
        if ($this->isDirty() && !$this->info->isFieldDirty('chdate')) {
            $this->info->setValue('chdate', time());
        }
        return parent::store();
    }

    /**
     * @see SimpleORMap::triggerChdate()
     */
    function triggerChdate()
    {
       return $this->info->triggerChdate();
    }

    /**
     * returns the name in specified format
     * (formats defined in $GLOBALS['_fullname_sql'])
     *
     * @param string one of full,full_rev,no_title,no_title_rev,no_title_short,no_title_motto,full_rev_username
     * @return string guess what - the fullname
     */
    function getFullName($format = "full")
    {
        $sql = $GLOBALS['_fullname_sql'][$format];
        $db = DBManager::get();
        if (!$sql) {
            return $this->vorname . ' ' . $this->nachname;
        }
        $data = array_map(array($db,'quote'), $this->toArray());
        return $db->query("SELECT " . strtr(strtolower($sql), $data))->fetchColumn();
    }

    function toArrayRecursive($only_these_fields = null)
    {
        $ret = parent::toArrayRecursive($only_these_fields);
        unset($ret['info']);
        return  $ret;
    }

}