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

    public static function findByInstitute($institute_id)
    {
        return self::findBySQL("institut_id=? AND inst_perms <> 'user'", array($institute_id));
    }

    public static function findByUser($user_id)
    {
        return self::findByUser_id($user_id);
    }

    function __construct($id = array())
    {
        $this->db_table = 'user_inst';
        $this->belongs_to = array('user' => array('class_name' => 'User',
                                                    'foreign_key' => 'user_id'),
                                   'institute' => array('class_name' => 'Institute',
                                                    'foreign_key' => 'institut_id')
        );
        $this->has_many = array(
            'datafields' => array(
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
                            })
            );
        $user_getter = function ($record, $field) { return $record->getRelationValue('user', $field);};
        $this->additional_fields['vorname'] = array('get' => $user_getter);
        $this->additional_fields['nachname'] = array('get' => $user_getter);
        $this->additional_fields['username'] = array('get' => $user_getter);
        $this->additional_fields['email'] = array('get' => $user_getter);
        $this->additional_fields['title_front'] = array('get' => $user_getter);
        $this->additional_fields['title_rear'] = array('get' => $user_getter);
        $inst_getter = function ($record, $field) {
            if (strpos($field, 'institute_') !== false) {
                $field = substr($field,10);
            }
            return $record->getRelationValue('institute', $field);
        };
        $this->additional_fields['institute_name'] = array('get' => $inst_getter);
        parent::__construct($id);
    }
}
