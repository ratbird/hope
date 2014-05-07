<?php
/**
 * CourseMember.class.php
 * model class for table seminar_user
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
 * @property string seminar_id database column
 * @property string user_id database column
 * @property string status database column
 * @property string position database column
 * @property string gruppe database column
 * @property string notification database column
 * @property string mkdate database column
 * @property string comment database column
 * @property string visible database column
 * @property string label database column
 * @property string bind_calendar database column
 * @property string vorname computed column
 * @property string nachname computed column
 * @property string username computed column
 * @property string email computed column
 * @property string title_front computed column
 * @property string title_rear computed column
 * @property string course_name computed column
 * @property string id computed column read/write
 * @property SimpleORMapCollection datafields has_many DatafieldEntryModel
 * @property User user belongs_to User
 * @property Course course belongs_to Course
 */
class CourseMember extends SimpleORMap
{

    public static function findByCourse($course_id)
    {
        return self::findBySeminar_id($course_id, 'ORDER BY position');
    }

    public static function findByUser($user_id)
    {
        return self::findByUser_id($user_id);
    }

    function __construct($id = array())
    {
        $this->db_table = 'seminar_user';
        $this->belongs_to = array(
                'user' => array(
                        'class_name' => 'User',
                        'foreign_key' => 'user_id'),
                'course' => array(
                        'class_name' => 'Course',
                        'foreign_key' => 'seminar_id'),
        );
        $this->has_many = array(
            'datafields' => array(
                        'class_name' => 'DatafieldEntryModel',
                        'assoc_foreign_key' =>
                            function($model, $params) {
                                list($sec_range_id, $range_id) = (array)$params[0]->getId();
                                $model->setValue('range_id', $range_id);
                                $model->setValue('sec_range_id', $sec_range_id);
                            },
                        'assoc_func' => 'findByModel',
                        'on_delete' => 'delete',
                        'on_store' => 'store',
                        'foreign_key' =>
                            function($course_member) {
                                return array($course_member);
                            })
            );
        $user_getter = function ($record, $field) {
            return $record->getRelationValue('user', $field);
        };
        $this->additional_fields['vorname'] = array('get' => $user_getter);
        $this->additional_fields['nachname'] = array('get' => $user_getter);
        $this->additional_fields['username'] = array('get' => $user_getter);
        $this->additional_fields['email'] = array('get' => $user_getter);
        $this->additional_fields['title_front'] = array('get' => $user_getter);
        $this->additional_fields['title_rear'] = array('get' => $user_getter);
        $course_getter = function ($record, $field) {
            if (strpos($field, 'course_') !== false) {
                $field = substr($field,7);
            }
            return $record->getRelationValue('course', $field);
        };
        $this->additional_fields['course_name'] = array('get' => $course_getter);
        parent::__construct($id);
    }
}
