<?php
/**
 * UserStudyCourse.class.php
 * model class for table user_studiengang
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack <noack@data-quest.de>
 * @copyright   2013 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * 
 */
class UserStudyCourse extends SimpleORMap
{
    public static function findByUser($user_id)
    {
        return self::findByUser_id($user_id, SimpleORMap::FETCH_ADDITONAL);
    }

    public static function findByStudyCourseAndDegree($study_course_id, $degree_id)
    {
        return self::findBySql("studiengang_id = ? AND abschluss_id = ?", array($study_course_id, $degree_id));
    }

    function __construct($id = array())
    {
        $this->db_table = 'user_studiengang';
        $this->belongs_to = array(
                'user' => array('class_name' => 'User',
                                'foreign_key' => 'user_id'),
                'degree' => array('class_name' => 'Degree',
                                'foreign_key' => 'abschluss_id'),
                'studycourse' => array('class_name' => 'StudyCourse',
                                'foreign_key' => 'studiengang_id')
        );
        $this->additional_fields['vorname'] = 'user.vorname';
        $this->additional_fields['nachname'] = 'user.nachname';
        $this->additional_fields['username'] = 'user.username';
        $this->additional_fields['degree_name'] = 'degree.name';
        $this->additional_fields['studycourse_name'] = 'studycourse.name';
        parent::__construct($id);
    }
}