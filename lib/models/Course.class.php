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
 * 
 * @property string seminar_id database column
 * @property string id alias column for seminar_id
 * @property string veranstaltungsnummer database column
 * @property string institut_id database column
 * @property string name database column
 * @property string untertitel database column
 * @property string status database column
 * @property string beschreibung database column
 * @property string ort database column
 * @property string sonstiges database column
 * @property string passwort database column
 * @property string lesezugriff database column
 * @property string schreibzugriff database column
 * @property string start_time database column
 * @property string duration_time database column
 * @property string art database column
 * @property string teilnehmer database column
 * @property string vorrausetzungen database column
 * @property string lernorga database column
 * @property string leistungsnachweis database column
 * @property string metadata_dates database column
 * @property string mkdate database column
 * @property string chdate database column
 * @property string ects database column
 * @property string admission_endtime database column
 * @property string admission_turnout database column
 * @property string admission_binding database column
 * @property string admission_type database column
 * @property string admission_selection_take_place database column
 * @property string admission_group database column
 * @property string admission_prelim database column
 * @property string admission_prelim_txt database column
 * @property string admission_starttime database column
 * @property string admission_endtime_sem database column
 * @property string admission_disable_waitlist database column
 * @property string admission_enable_quota database column
 * @property string visible database column
 * @property string showscore database column
 * @property string modules database column
 * @property string aux_lock_rule database column
 * @property string lock_rule database column
 * @property string newsfeed_token database column
 * @property string end_time computed column read/write
 * @property SimpleORMapCollection members has_many CourseMember
 * @property SimpleORMapCollection admission_applicants has_many AdmissionApplication
 * @property SimpleORMapCollection datafields has_many DatafieldEntryModel
 * @property Semester start_semester belongs_to Semester
 * @property Semester end_semester belongs_to Semester
 * @property Institute home_institut belongs_to Institute
 * @property SimpleORMapCollection study_areas has_and_belongs_to_many StudipStudyArea
 * @property SimpleORMapCollection institutes has_and_belongs_to_many Institute
 */

class Course extends SimpleORMap
{
    /**
     * Returns the currently active course or false if none is active.
     *
     * @return mixed Course object of currently active course, false otherwise
     * @since 3.0
     */
    public static function findCurrent()
    {
        return empty($GLOBALS['SessSemName'][1])
            ? null
            : Course::find($GLOBALS['SessSemName'][1]);
    }

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
    
    public function store()
    {
        parent::store();
        
        NotificationCenter::postNotification("CourseDidCreateOrUpdate", $this->id);
        if ($this->is_new) {
            NotificationCenter::postNotification("CourseDidCreate", $this->id);
        } else {
            NotificationCenter::postNotification("CourseDidUpdate", $this->id);
        }
    }
}
