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
 * @property string studiengang_id database column
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
 * @property string studycourse_name computed column
 * @property string id computed column read/write
 * @property User user belongs_to User
 * @property Course course belongs_to Course
 * @property StudyCourse studycourse belongs_to StudyCourse
 */
class AdmissionApplication extends SimpleORMap
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
        $this->db_table = 'admission_seminar_user';
        $this->belongs_to = array('user' => array('class_name' => 'User',
                                                    'foreign_key' => 'user_id'),
                                   'course' => array('class_name' => 'Course',
                                                    'foreign_key' => 'seminar_id'),
                                   'studycourse' => array('class_name' => 'StudyCourse',
                                                    'foreign_key' => 'studiengang_id')
        );
        $user_getter = function ($record, $field) { return $record->getRelationValue('user', $field);};
        $this->additional_fields['vorname'] = array('get' => $user_getter);
        $this->additional_fields['nachname'] = array('get' => $user_getter);
        $this->additional_fields['username'] = array('get' => $user_getter);
        $this->additional_fields['email'] = array('get' => $user_getter);
        $this->additional_fields['course_name'] = array('get' => 
                                                        function ($record, $field) {
                                                            return $record->getRelationValue('course', 'name');
                                                        });
        $this->additional_fields['studycourse_name'] = array('get' => 
                                                        function ($record, $field) {
                                                            return $record->studiengang_id == 'all' ? _("Alle Studieng�nge") : $record->getRelationValue('studycourse', 'name');
                                                        });
        parent::__construct($id);
    }
}
