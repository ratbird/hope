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
        $this->belongs_to = array('user' => array('class_name' => 'User',
                                                    'foreign_key' => 'user_id'),
                                   'course' => array('class_name' => 'Course',
                                                    'foreign_key' => 'seminar_id')
        );
        $user_getter = function ($record, $field) { return $record->getRelationValue('user', $field);};
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