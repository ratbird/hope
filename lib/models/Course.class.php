<?php
/**
 * Course.class.php
 * model class for table seminare
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

class Course extends SimpleORMap
{
    function __construct($id = null)
    {
        $this->db_table = 'seminare';
        $this->has_many = array(
                'members' => array(
                        'class_name' => 'CourseMember',
                        'on_delete' => 'delete',
                        'on_store' => 'store'),
                'admission_applicants' => array(
                        'class_name' => 'AdmissionApplication',
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
                            function($course) {
                                return array($course);
                            })
        );
        $this->belongs_to = array(
                'start_semester' => array(
                        'class_name' => 'Semester',
                        'foreign_key' => 'start_time',
                        'assoc_func' => 'findByTimestamp',
                        'assoc_foreign_key' => 'beginn'),
                'end_semester' => array(
                        'class_name' => 'Semester',
                        'foreign_key' => 'end_time',
                        'assoc_func' => 'findByTimestamp',
                        'assoc_foreign_key' => 'beginn'),
                'home_institut' => array(
                        'class_name' => 'Institute',
                        'foreign_key' => 'institut_id',
                        'assoc_func' => 'find')
        );
        $this->has_and_belongs_to_many = array(
                'study_areas' => array(
                        'class_name' => 'StudipStudyArea',
                        'thru_table' => 'seminar_sem_tree',
                        'on_delete' => 'delete', 'on_store' => 'store'),
                'institutes' => array(
                        'class_name' => 'Institute',
                        'thru_table' => 'seminar_inst',
                        'on_delete' => 'delete', 'on_store' => 'store'));
        $this->default_values['beschreibung'] = '';
        $this->default_values['lesezugriff'] = 1;
        $this->default_values['schreibzugriff'] = 1;
        $this->default_values['duration_time'] = 0;
        $this->default_values['admission_endtime'] = -1;

        $this->additional_fields['end_time']['get'] = function($course) {
            return $course->duration_time == -1 ? -1 : $course->start_time + $course->duration_time;
        };
        $this->additional_fields['end_time']['set'] = function($course, $field, $value) {
            if ($value == -1) {
                $course->duration_time = -1;
            } else if (($course->start_time > 0)  && ($value > $course->start_time)) {
                $course->duration_time = $value - $course->start_time;
            } else {
                $course->duration_time = 0;
            }
        };

        parent::__construct($id);
    }

}