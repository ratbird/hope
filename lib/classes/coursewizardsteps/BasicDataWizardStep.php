<?php
/**
 * BasicDataWizardStep.php
 * Course wizard step for getting the basic course data.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Thomas Hackl <thomas.hackl@uni-passau.de>
 * @copyright   2015 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

class BasicDataWizardStep implements CourseWizardStep
{
    /**
     * Returns the Flexi template for entering the necessary values
     * for this step.
     *
     * @param Array $values Pre-set values
     * @param int $stepnumber which number has the current step in the wizard?
     * @param String $temp_id temporary ID for wizard workflow
     * @return String a Flexi template for getting needed data.
     */
    public function getStepTemplate($values, $stepnumber, $temp_id)
    {
        // We only need our own stored values here.
        $values = $values[__CLASS__];
        // Load template from step template directory.
        $factory = new Flexi_TemplateFactory($GLOBALS['STUDIP_BASE_PATH'].'/app/views/course/wizard/steps');
        if ($values['studygroup']) {
            $tpl = $factory->open('basicdata/index_studygroup');
            $values['lecturers'][$GLOBALS['user']->id] = 1;
        } else {
            $tpl = $factory->open('basicdata/index');
        }

        // Get all available course types and their categories.
        $typestruct = array();
        foreach (SemType::getTypes() as $type)
        {
            $class = $type->getClass();
            // Creates a studygroup.
            if ($values['studygroup']) {
                // Get all studygroup types.
                if ($class['studygroup_mode']) {
                    $typestruct[$class['name']][] = $type;
                }
                // Pre-set institute for studygroup assignment.
                $values['institute'] = Config::get()->STUDYGROUP_DEFAULT_INST;
            // Normal course.
            } else {
                if (!$class['course_creation_forbidden']) {
                    $typestruct[$class['name']][] = $type;
                }
            }
        }
        $tpl->set_attribute('types', $typestruct);
        // Select a default type if none is given.
        if (!$values['coursetype']) {
            if ($GLOBALS['user']->cfg->MY_COURSES_TYPE_FILTER && Request::isXhr()) {
                $values['coursetype'] = $GLOBALS['user']->cfg->MY_COURSES_TYPE_FILTER;
            } else {
                $values['coursetype'] = 1;
            }
        }

        // Semester selection.
        $semesters = array();
        $now = mktime();
        // Allow only current or future semesters for selection.
        foreach (Semester::getAll() as $s) {
            if ($s->ende >= $now) {
                if ($s->id == $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE &&
                        !$values['start_time'] && Request::isXhr()) {
                    $values['start_time'] = $s->beginn;
                }
                $semesters[] = $s;
            }
        }
        $tpl->set_attribute('semesters', array_reverse($semesters));
        // If no semester is set, use current as selected default.
        if (!$values['start_time']) {
            $values['start_time'] = Semester::findCurrent()->beginn;
        }

        // Get all allowed home institutes (my own).
        $institutes = Institute::getMyInstitutes();
        $tpl->set_attribute('institutes', $institutes);
        if (!$values['institute']) {
            if ($GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT && Request::isXhr()) {
                $values['institute'] = $GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT;
            } else {
                $values['institute'] = $institutes[0]['Institut_id'];
            }
        }

        // QuickSearch for participating institutes.
        // No JS: Keep search value and results for displaying in search select box.
        if ($values['part_inst_id']) {
            Request::getInstance()->offsetSet('part_inst_id', $values['part_inst_id']);
        }
        if ($values['part_inst_id_parameter']) {
            Request::getInstance()->offsetSet('part_inst_id_parameter', $values['part_inst_id_parameter']);
        }
        $instsearch = new StandardSearch('Institut_id',
            _('Beteiligte Einrichtung hinzufügen'),
            'part_inst_id'
        );
        $tpl->set_attribute('instsearch', QuickSearch::get('part_inst_id', $instsearch)
            ->withButton(array('search_button_name' => 'search_part_inst', 'reset_button_name' => 'reset_instsearch'))
            ->fireJSFunctionOnSelect('STUDIP.CourseWizard.addParticipatingInst')
            ->render());
        if (!$values['participating']) {
            $values['participating'] = array();
        }

        // Quicksearch for lecturers.
        // No JS: Keep search value and results for displaying in search select box.
        if ($values['lecturer_id']) {
            Request::getInstance()->offsetSet('lecturer_id', $values['lecturer_id']);
        }
        if ($values['lecturer_id_parameter']) {
            Request::getInstance()->offsetSet('lecturer_id_parameter', $values['lecturer_id_parameter']);
        }

        // Check for deputies.
        $deputies = Config::get()->DEPUTIES_ENABLE;
        /*
         * No lecturers set, add yourself so that at least one lecturer is
         * present. But this can only be done if your own permission level
         * is 'dozent'.
         */
        if (!$values['lecturers'] && $GLOBALS['perm']->have_perm('dozent') && !$GLOBALS['perm']->have_perm('admin')) {
            $values['lecturers'][$GLOBALS['user']->id] = true;
            // Remove from deputies if set.
            if ($deputies && $values['deputies'][$GLOBALS['user']->id]) {
                unset($values['deputies'][$GLOBALS['user']->id]);
            }
            // Add your own default deputies if applicable.
            if ($deputies && Config::get()->DEPUTIES_DEFAULTENTRY_ENABLE) {
                $values['deputies'] = array_merge($values['deputies'] ?: array(),
                    array_keys(getDeputies($GLOBALS['user']->id)));
            }
        }
        // Add lecturer from my courses filter.
        if ($GLOBALS['user']->cfg->ADMIN_COURSES_TEACHERFILTER && !$values['lecturers'] && Request::isXhr()) {
            $values['lecturers'][$GLOBALS['user']->cfg->ADMIN_COURSES_TEACHERFILTER] = true;
            // Add this lecturer's default deputies if applicable.
            if ($deputies && Config::get()->DEPUTIES_DEFAULTENTRY_ENABLE) {
                $values['deputies'] = array_merge($values['deputies'] ?: array(),
                    array_keys(getDeputies($GLOBALS['user']->cfg->ADMIN_COURSES_TEACHERFILTER)));
            }
        }
        if (!$values['lecturers']) {
            $values['lecturers'] = array();
        }
        if ($deputies && !$values['deputies']) {
            $values['deputies'] = array();
        }

        // Quicksearch for lecturers.
        $tpl->set_attribute('lsearch', $this->getSearch($values['coursetype'],
            $values['institute'], array_keys($values['lecturers'])));

        // Quicksearch for deputies if applicable.
        if ($deputies) {
            // No JS: Keep search value and results for displaying in search select box.
            if ($values['deputy_id']) {
                Request::getInstance()->offsetSet('deputy_id', $values['deputy_id']);
            }
            if ($values['deputy_id_parameter']) {
                Request::getInstance()->offsetSet('deputy_id_parameter', $values['deputy_id_parameter']);
            }
            $deputysearch = new PermissionSearch('user',
                _('Vertretung hinzufügen'),
                'user_id',
                array('permission' => 'dozent',
                    'exclude_user' => array_keys($values['deputies']))
            );
            $tpl->set_attribute('dsearch', QuickSearch::get('deputy_id', $deputysearch)
                ->withButton(array('search_button_name' => 'search_deputy', 'reset_button_name' => 'reset_dsearch'))
                ->fireJSFunctionOnSelect('STUDIP.CourseWizard.addDeputy')
                ->render());
        }
        $tpl->set_attribute('values', $values);
        return $tpl->render();
    }

    /**
     * The function only needs to handle person adding and removing
     * as other actions are handled by normal request processing.
     * @param Array $values currently set values for the wizard.
     * @return bool
     */
    public function alterValues($values)
    {
        // We only need our own stored values here.
        $values = $values[__CLASS__];
        // Add a participating institute.
        if (Request::submitted('add_part_inst') && Request::option('part_inst_id')) {
            $values['participating'][Request::option('part_inst_id')] = true;
            unset($values['part_inst_id']);
            unset($values['part_inst_id_parameter']);
        }
        // Add a lecturer.
        if (Request::submitted('add_lecturer') && Request::option('lecturer_id')) {
            $values['lecturers'][Request::option('lecturer_id')] = true;
            unset($values['lecturer_id']);
            unset($values['lecturer_id_parameter']);
        }
        // Remove a lecturer.
        if ($remove = array_keys(Request::getArray('remove_lecturer'))) {
            $remove = $remove[0];
            unset($values['lecturers'][$remove]);
        }
        // Add a deputy.
        if (Request::submitted('add_deputy')) {
            $values['deputies'][Request::option('deputy_id')] = true;
            unset($values['deputy_id']);
            unset($values['deputy_id_parameter']);
        }
        // Remove a deputy.
        if ($remove = array_keys(Request::getArray('remove_deputy'))) {
            $remove = $remove[0];
            unset($values['deputies'][$remove]);
        }
        return $values;
    }

    /**
     * Validates if given values are sufficient for completing the current
     * course wizard step and switch to another one. If not, all errors are
     * collected and shown via PageLayout::postMessage.
     *
     * @param mixed $values Array of stored values
     * @return bool Everything ok?
     */
    public function validate($values)
    {
        // We only need our own stored values here.
        $values = $values[__CLASS__];
        $ok = true;
        $errors = array();
        if (!trim($values['name'])) {
            $errors[] = _('Bitte geben Sie den Namen der Veranstaltung an.');
        }
        if (!$values['lecturers']) {
            $errors[] = sprintf(_('Bitte tragen Sie mindestens eine Person als %s ein.'),
                get_title_for_status('dozent', 1, $values['coursetype']));
        }
        if (!$values['lecturers'][$GLOBALS['user']->id] && !$GLOBALS['perm']->have_perm('admin')) {
            if (Config::get()->DEPUTIES_ENABLE) {
                if (!$values['deputies'][$GLOBALS['user']->id]) {
                    $errors[] = sprintf(_('Sie selbst müssen entweder als %s oder als Vertretung eingetragen sein.'),
                        get_title_for_status('dozent', 1, $values['coursetype']));
                }
            } else {
                $errors[] = sprintf(_('Sie müssen selbst als %s eingetragen sein.'),
                    get_title_for_status('dozent', 1, $values['coursetype']));
            }
        }
        if (in_array($values['coursetype'], studygroup_sem_types())) {
            if (!$values['accept']) {
                $errors[] = _('Sie müssen die Nutzungsbedingungen akzeptieren.');
            }
        }
        if ($errors) {
            $ok = false;
            PageLayout::postMessage(MessageBox::error(
                _('Bitte beheben Sie erst folgende Fehler, bevor Sie fortfahren:'), $errors));
        }
        return $ok;
    }

    /**
     * Stores the given values to the given course.
     *
     * @param Course $course the course to store values for
     * @param Array $values values to set
     * @return Course The course object with updated values.
     */
    public function storeValues($course, $values)
    {
        // We only need our own stored values here.
        $values = $values[__CLASS__];
        $course->status = $values['coursetype'];
        $course->start_time = $values['start_time'];
        $course->duration_time = 0;
        $course->name = $values['name'];
        $course->veranstaltungsnummer = $values['number'];
        $course->institut_id = $values['institute'];
        $course->visible = 0;
        $lecturers = array_map(function($l) use ($course)
        {
            return CourseMember::create(array(
                'Seminar_id' => $course->id,
                'user_id' => $l,
                'status' => 'dozent',
                'position' => 0,
                'gruppe' => 0,
                'notification' => 0,
                'comment' => '',
                'visible' => 'yes',
                'bind_calendar' => 1
            ));
        }, array_keys($values['lecturers']));
        $course->members = SimpleORMapCollection::createFromArray($lecturers);
        if (Config::get()->DEPUTIES_ENABLE && $values['deputies']) {
            foreach ($values['deputies'] as $d => $assigned) {
                addDeputy($d, $course->id);
            }
        }
        // Studygroups: access and description.
        if (in_array($values['coursetype'], studygroup_sem_types())) {
            $course->visible = 1;
            switch ($values['access']) {
                case 'all':
                    $course->admission_prelim = 0;
                    break;
                case 'invisible':
                    if (!Config::get()->STUDYGROUPS_INVISIBLE_ALLOWED) {
                        $course->visible = 0;
                    }
                case 'invite':
                    $course->admission_prelim = 1;
                    $course->admission_prelim_txt = Config::get()->STUDYGROUP_ACCEPTANCE_TEXT;
                    break;
            }
            $course->beschreibung = $values['description'];
        }
        if ($course->store()) {
            return $course;
        } else {
            return false;
        }
    }

    /**
     * Checks if the current step needs to be executed according
     * to already given values. A good example are study areas which
     * are only needed for certain sem_classes.
     *
     * @param Array $values values specified from previous steps
     * @return bool Is the current step required for a new course?
     */
    public function isRequired($values)
    {
        return true;
    }

    /**
     * Copy values for basic data wizard step from given course.
     * @param Course $course
     * @param Array $values
     */
    public function copy($course, $values)
    {
        $data = array(
            'coursetype' => $course->status,
            'start_time' => $course->start_time,
            'name' => $course->name,
            'number' => $course->veranstaltungsnummer,
            'institute' => $course->institut_id
        );
        $lecturers = array_map(function($l) {
                return $l->user_id;
            },
            $course->getMembersWithStatus('dozent'));
        $data['lecturers'] = array_flip($lecturers);
        if (Config::get()->DEPUTIES_ENABLE) {
            $deputies = getDeputies($course->id);
            $data['deputies'] = array_keys($deputies);
        }
        $values[__CLASS__] = $data;
        return $values;
    }

    public function getSearch($course_type, $institute_id, $exclude_users = array())
    {
        if (SeminarCategories::getByTypeId($course_type)->only_inst_user){
            $search = 'user_inst';
        } else {
            $search = 'user';
        }
        $psearch = new PermissionSearch($search,
            sprintf(_("%s hinzufügen"), get_title_for_status('dozent', 1, $course_type)),
            'user_id',
            array('permission' => 'dozent',
                'exclude_user' => $exclude_users ?: array(),
                'institute' => $institute_id
            )
        );
        $qsearch = QuickSearch::get('lecturer_id', $psearch)
            ->withButton(array('search_button_name' => 'search_lecturer', 'reset_button_name' => 'reset_lsearch'))
            ->fireJSFunctionOnSelect('STUDIP.CourseWizard.addLecturer')
            ->render();
        return $qsearch;
    }

}