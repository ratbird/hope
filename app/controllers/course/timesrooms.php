<?php

/**
 * @author  David Siegfried <david.siegfried@uni-vechta.de>
 * @license GPL2 or any later version
 * @since   3.4
 */
class Course_TimesroomsController extends AuthenticatedController
{
    protected $utf8decode_xhr = true;

    /**
     * Common actions before any other action
     *
     * @param String $action Action to be executed
     * @param Array $args Arguments passed to the action
     * @throws Trails_Exception when either no course was found or the user
     *                          may not access this area
     */
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        // Try to find a valid course
        if (Course::findCurrent()) {
            $course_id = Course::findCurrent()->id;
        } else {
            throw new Trails_Exception(404, _('Es wurde keine Veranstaltung ausgewählt!'));
        }

        if (!$GLOBALS['perm']->have_studip_perm('tutor', $course_id)) {
            throw new Trails_Exception(400);
        }

        // Get seminar instance
        $this->course = Seminar::getInstance($course_id);

        if (Navigation::hasItem('course/admin/dates')) {
            Navigation::activateItem('course/admin/dates');
        }
        $this->show = array(
            'regular'     => true,
            'irregular'   => true,
            'roomRequest' => true,
        );

        PageLayout::setHelpKeyword('Basis.Veranstaltungen');
        PageLayout::addSqueezePackage('raumzeit');

        $title = _('Verwaltung von Zeiten und Räumen');
        $title = $this->course->getFullname() . ' - ' . $title;

        PageLayout::setTitle($title);

        $_SESSION['raumzeitFilter'] = Request::get('newFilter');

        // bind linkParams for chosen semester and opened dates
        URLHelper::bindLinkParam('raumzeitFilter', $_SESSION['raumzeitFilter']);

        $this->checkFilter();

        $this->selection = $this->getSemestersForCourse($this->course, $_SESSION['raumzeitFilter']);

        if (!Request::isXhr()) {
            $this->setSidebar();
        } elseif (Request::isXhr() && $this->flash['update-times']) {
            $semester_id = $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE;
            if ($semester_id === 'all') {
                $semester_id = '';
            }
            $this->response->add_header('X-Raumzeit-Update-Times', json_encode(studip_utf8encode(array(
                'course_id' => $this->course->id,
                'html'      => Seminar::GetInstance($this->course->id)->getDatesHTML(array(
                    'semester_id' => $semester_id,
                    'show_room'   => true,
                )) ?: _('nicht angegeben'),
            ))));
        }
    }

    /**
     * Displays the times and rooms of a course
     *
     * @param mixed $course_id Id of the course (optional, defaults to
     *                         globally selected)
     */
    public function index_action()
    {
        Helpbar::get()->addPlainText(_('Rot'), _('Kein Termin hat eine Raumbuchung.'));
        Helpbar::get()->addPlainText(_('Gelb'), _('Mindestens ein Termin hat keine Raumbuchung.'));
        Helpbar::get()->addPlainText(_('Grün'), _('Alle Termine haben eine Raumbuchung.'));

        $editParams = array(
            'fromDialog' => Request::isXhr() ? 'true' : 'false',
        );

        $linkAttributes = array();

        $semesterFormParams = array(
            'formaction' => $this->url_for('course/timesrooms/setSemester/' . $this->course->id),
        );

        if (Request::isXhr()) {
            $this->show                        = array(
                'regular'     => true,
                'irregular'   => true,
                'roomRequest' => false,
            );
            $semesterFormParams['data-dialog'] = 'size=big';
            $editParams['asDialog']            = true;
            $linkAttributes['data-dialog']     = 'size=big';
        }

        $this->semester         = array_reverse(Semester::getAll());
        $this->current_semester = Semester::findCurrent();

        // Get Cycles
        $this->cycle_dates = array();
        foreach ($this->course->cycles as $cycle) {
            foreach ($cycle->getAllDates() as $val) {
                foreach ($this->semester as $sem) {
                    if ($_SESSION['raumzeitFilter'] === $sem->id
                        || ($sem->beginn != $_SESSION['raumzeitFilter'] && $_SESSION['raumzeitFilter'] !== 'all')
                    ) {
                        continue;
                    }

                    if ($sem->beginn <= $val->date && $sem->ende >= $val->date) {
                        if (!isset($this->cycle_dates[$cycle->metadate_id])) {
                            $this->cycle_dates[$cycle->metadate_id] = array(
                                'cycle'        => $cycle,
                                'dates'        => array(),
                                'room_request' => array(),
                            );
                        }
                        if (!isset($this->cycle_dates[$cycle->metadate_id]['dates'][$sem->id])) {
                            $this->cycle_dates[$cycle->metadate_id]['dates'][$sem->id] = array();
                        }
                        $this->cycle_dates[$cycle->metadate_id]['dates'][$sem->id][] = $val;
                        if ($val->getRoom()) {
                            $this->cycle_dates[$cycle->metadate_id]['room_request'][] = $val->getRoom();
                        }
                    }
                }
            }
        }

        // Get Single Dates
        $single_dates = array();

        foreach ($this->course->getDatesWithExdates() as $id => $val) {
            foreach ($this->semester as $sem) {
                if ($_SESSION['raumzeitFilter'] == $sem->id
                    || ($sem->beginn != $_SESSION['raumzeitFilter'] && $_SESSION['raumzeitFilter'] !== 'all')
                ) {
                    continue;
                }

                if ($sem->beginn > $val->date || $sem->ende < $val->date || isset($val->metadate_id)) {
                    continue;
                }

                if (!isset($single_dates[$sem->id])) {
                    $single_dates[$sem->id] = new SimpleCollection();
                }
                $single_dates[$sem->id]->append($val);
            }
        }

        $this->single_dates       = $single_dates;
        $this->semesterFormParams = $semesterFormParams;
        $this->editParams         = $editParams;
        $this->linkAttributes     = $linkAttributes;
    }

    /**
     * Edit the start-semester of a course
     * @throws Trails_DoubleRenderError
     */
    public function editSemester_action()
    {
        if (!Request::isXhr()) {
            $this->redirect('course/timesrooms/index');

            return;
        }
        $this->params           = array('origin' => Request::get('origin', 'course_timesrooms'));
        $this->semester         = array_reverse(Semester::getAll());
        $this->current_semester = Semester::findCurrent();
    }

    /**
     * Primary function to edit date-informations
     * @param      $termin_id
     * @param null $metadate_id
     */
    public function editDate_action($termin_id)
    {
        $this->date       = CourseDate::find($termin_id) ?: CourseExDate::find($termin_id);
        $this->attributes = array();

        if ($request = RoomRequest::findByDate($this->date->id)) {
            $this->params = array('request_id' => $request->getId());
        } else {
            $this->params = array('new_room_request_type' => 'date_' . $this->date->id);
        }

        $this->params['fromDialog'] = Request::get('fromDialog');

        if (Request::get('fromDialog') == 'true') {
            $this->attributes['data-dialog'] = 'size=big';
        } else {
            $this->attributes['fromDialog'] = 'false';
        }

        $this->resList = ResourcesUserRoomsList::getInstance($GLOBALS['user']->id, true, false, true);

        //UMSTELLEN AUF COURSE
        $this->dozenten = $this->course->getMembers('dozent');
        $this->gruppen  = Statusgruppen::findBySeminar_id($this->course->id);

        $this->related_persons = array();
        foreach (User::findDozentenByTermin_id($this->date->id) as $user) {
            $this->related_persons[] = $user->user_id;
        }

        $this->related_groups = array();
        foreach (Statusgruppen::findByTermin_id($this->date->id) as $group) {
            $this->related_groups[] = $group->statusgruppe_id;
        }
    }


    /**
     * Save date-information
     * @param $termin_id
     * @throws Trails_DoubleRenderError
     */
    public function saveDate_action($termin_id)
    {
        $termin = CourseDate::find($termin_id);

        $date     = strtotime(sprintf('%s %s:00', Request::get('date'), Request::get('start_time')));
        $end_time = strtotime(sprintf('%s %s:00', Request::get('date'), Request::get('end_time')));

        //time changed for regular date. create normal singledate and cancel the regular date
        if (($termin->metadate_id != '' || isset($termin->metadate_id))
            && ($date != $termin->date || $end_time != $termin->end_time)
        ) {
            $termin_values = $termin->toArray();
            $termin_info   = $termin->getFullname();
            $termin->cancelDate();
            PageLayout::postInfo(sprintf(_('Der Termin %s wurde aus der Liste der regelmäßigen Termine'
                                           . ' gelöscht und als unregelmäßiger Termin eingetragen, da Sie die Zeiten des Termins verändert haben,'
                                           . ' so dass dieser Termin nun nicht mehr regelmäßig ist.'), $termin_info));

            $termin = new CourseDate();
            unset($termin_values['termin_id']);
            unset($termin_values['metadate_id']);
            $termin->setData($termin_values);
        }
        $termin->date     = $date;
        $termin->end_time = $end_time;
        $termin->date_typ = Request::get('course_type');

        $related_groups        = Request::get('related_statusgruppen');
        $termin->statusgruppen = array();
        if (!empty($related_groups)) {
            $related_groups        = explode(',', $related_groups);
            $termin->statusgruppen = Statusgruppen::findMany($related_groups);
        }

        $related_users    = Request::get('related_teachers');
        $termin->dozenten = array();
        if (!empty($related_users)) {
            $related_users    = explode(',', $related_users);
            $termin->dozenten = User::findMany($related_users);
        }

        // Set Room
        if (Request::option('room') == 'room') {
            $room_id = Request::option('room_sd', '0');

            if ($room_id != '0' && $room_id != $termin->room_assignment->resource_id) {
                ResourceAssignment::deleteBySQL('assign_user_id = :termin',
                    array(':termin' => $termin->termin_id));
                $resObj                  = new ResourceObject($room_id);
                $termin->raum            = '';
                $room                    = new ResourceAssignment();
                $room->assign_user_id    = $termin->termin_id;
                $room->resource_id       = Request::get('room_sd');
                $room->begin             = $termin->date;
                $room->end               = $termin->end_time;
                $room->repeat_end        = $termin->end_time;
                $room->store();

                $this->course->createMessage(sprintf(_('Der Termin %s wurde geändert und der Raum %s gebucht, etwaige freie Ortsangaben wurden entfernt.'),
                    $termin->getFullname(), $resObj->getName()));

            } elseif ($room_id == '0') {
                $this->course->createError(sprintf(_('Der angegebene Raum konnte für den Termin %s nicht gebucht werden!'), $termin->getFullname()));
            }
        } elseif (Request::option('room') == 'noroom') {
            $termin->raum = '';
            ResourceAssignment::deleteBySQL('assign_user_id = :termin',
                array(':termin' => $termin->termin_id));
            $this->course->createMessage(sprintf(_('Der Termin %s wurde geändert, etwaige freie Ortsangaben und Raumbuchungen wurden entfernt.'), '<b>' . $termin->getFullname() . '</b>'));
        } elseif (Request::option('room') == 'freetext') {
            $termin->raum = Request::get('freeRoomText_sd');
            ResourceAssignment::deleteBySQL('assign_user_id = :termin',
                array(':termin' => $termin->termin_id));
            $this->course->createMessage(sprintf(_('Der Termin %s wurde geändert, etwaige Raumbuchungen wurden entfernt und stattdessen der angegebene Freitext eingetragen!'), '<b>' . $termin->getFullname() . '</b>'));
        }

        if ($termin->store()) {
            NotificationCenter::postNotification('CourseDidChangeSchedule', $this->course);
            $this->displayMessages();
        }
        $this->redirect($this->url_for('course/timesrooms/index#' . $termin->metadate_id,
            array('contentbox_open' => $termin->metadate_id)));
    }


    /**
     * Create Single Date
     */
    public function createSingleDate_action()
    {
        $this->restoreRequest(words('date start_time end_time room related_teachers related_statusgruppen freeRoomText dateType fromDialog'));

        $this->editParams = array('fromDialog' => Request::get('fromDialog'));
        $this->resList    = ResourcesUserRoomsList::getInstance($GLOBALS['user']->id, true, false, true);
        $this->teachers   = $this->course->getMembers('dozent');
        $this->groups     = Statusgruppen::findBySeminar_id($this->course->id);
    }

    /**
     * Save Single Date
     * @throws Trails_DoubleRenderError
     */
    public function saveSingleDate_action()
    {
        CSRFProtection::verifyRequest();

        $start_time = strtotime(sprintf('%s %s:00', Request::get('date'), Request::get('start_time')));
        $end_time   = strtotime(sprintf('%s %s:00', Request::get('date'), Request::get('end_time')));

        if ($start_time > $end_time) {
            $this->storeRequest();

            PageLayout::postMessage(MessageBox::error(_('Die Zeitangaben sind nicht korrekt. Bitte überprüfen Sie diese!')));
            $this->redirect('course/timesrooms/createSingleDate');
            return;
        }

        $termin            = new CourseDate();
        $termin->termin_id = $termin->getNewId();
        $termin->range_id  = $this->course->id;
        $termin->date      = $start_time;
        $termin->end_time  = $end_time;
        $termin->autor_id  = $GLOBALS['user']->id;
        $termin->date_typ  = Request::get('dateType');

        $teachers = $this->course->getMembers('dozent');
        foreach (Request::getArray('related_teachers') as $dozent_id) {
            if (in_array($dozent_id, array_keys($teachers))) {
                $related_persons[] = User::find($dozent_id);
            }
        }
        if (isset($related_persons)) {
            $termin->dozenten = $related_persons;
        }

        foreach (Request::getArray('related_statusgruppen') as $statusgruppe_id) {
            $related_groups[] = Statusgruppen::find($statusgruppe_id);
        }
        if (isset($related_groups)) {
            $termin->statusgruppen = $related_groups;
        }

        if (!Request::get('room') || Request::get('room') === 'nothing') {
            $termin->raum = Request::get('freeRoomText');
            $termin->store();
        } else {
            $termin->store();

            $room                 = new ResourceAssignment();
            $room->assign_user_id = $termin->termin_id;
            $room->resource_id    = Request::get('room');
            $room->begin          = $termin->date;
            $room->end            = $termin->end_time;
            $room->repeat_end     = $termin->end_time;

            if (!$room->store()) {
                $termin->delete();
            }
        }

        if ($start_time < $this->course->filterStart || $end_time > $this->course->filterEnd) {
            $this->course->setFilter('all');
        }

        $this->course->createMessage(sprintf(_('Der Termin %s wurde hinzugefügt!'), $termin->getFullname()));
        $this->course->store();
        $this->displayMessages();

        if (Request::get('fromDialog') == 'true') {
            $this->redirect('course/timesrooms/index');
        } else {
            $this->relocate('course/timesrooms/index');
        }
    }

    /**
     * Removes a single date
     *
     * @param String $termin_id Id of the date
     * @param String $sub_cmd Sub command to be executed
     */
    public function deleteSingle_action($termin_id, $sub_cmd = 'delete')
    {
        $cycle_id = Request::option('cycle_id');
        if ($cycle_id) {
            $sub_cmd = 'cancel';
        }
        $this->deleteDate($termin_id, $sub_cmd, $cycle_id);
        $this->displayMessages();

        $params = array();
        if ($cycle_id) {
            $params['contentbox_open'] = $cycle_id;
        }
        $this->redirect($this->url_for('course/timesrooms/index' . (Request::option('cycle_id') ? '#' . $cycle_id : ''), $params));
    }

    /**
     * Restores a previously removed date.
     *
     * @param String $termin_id Id of the previously removed date
     */
    public function undeleteSingle_action($termin_id)
    {
        $ex_termin = CourseExDate::find($termin_id);
        $termin    = $ex_termin->unCancelDate();
        if ($termin) {
            $this->course->createMessage(sprintf(_('Der Termin %s wurde wiederhergestellt!'), $termin->getFullname()));
            $this->displayMessages();
        }

        $params = array();
        if ($termin->metadate_id != '') {
            $params['contentbox_open'] = $termin->metadate_id;
        }
        $this->redirect($this->url_for('course/timesrooms/index' . ($termin->metadate_id ? '#' . $termin->metadate_id : ''), $params));
    }

    /**
     * Performs a stack action defined by url parameter method.
     *
     * @param String $cycle_id Id of the cycle the action should be performed
     *                         upon
     */
    public function stack_action($cycle_id = '')
    {
        $_SESSION['_checked_dates'] = Request::getArray('single_dates');
        if (empty($_SESSION['_checked_dates']) && isset($_SESSION['_checked_dates'])) {
            PageLayout::postMessage(MessageBox::error(_('Sie haben keine Termine ausgewählt!')));
            if (Request::get('fromDialog') == 'true') {
                $this->redirect($this->url_for('course/timesrooms/index#' . $cycle_id,
                    array('contentbox_open' => $cycle_id)));
            } else {
                $this->relocate('course/timesrooms/index#' . $cycle_id,
                    array('contentbox_open' => $cycle_id));
            }
            return;
        }

        switch (Request::get('method')) {
            case 'edit':
                $this->editStack($cycle_id);
                break;
            case 'preparecancel':
                $this->prepareCancel($cycle_id);
                break;
            case 'delete':
                $this->deleteStack($cycle_id);
                break;
            case 'undelete':
                $this->unDeleteStack($cycle_id);
        }
    }

    /**
     * Edits a stack/cycle.
     *
     * @param String $cycle_id Id of the cycle to be edited.
     */
    private function editStack($cycle_id)
    {
        $this->cycle_id   = $cycle_id;
        $this->teachers   = $this->course->getMembers('dozent');
        $this->gruppen    = Statusgruppen::findBySeminar_id($this->course->id);
        $this->resList    = ResourcesUserRoomsList::getInstance($GLOBALS['user']->id, true, false, true);
        $this->editParams = array('fromDialog' => Request::get('fromDialog'));
        $this->render_template('course/timesrooms/editStack');
    }

    /**
     * Prepares a stack/cycle to be canceled.
     *
     * @param String $cycle_id Id of the cycle to be canceled.
     */
    private function prepareCancel($cycle_id)
    {
        $this->cycle_id   = $cycle_id;
        $this->editParams = array('fromDialog' => Request::get('fromDialog'));
        $this->render_template('course/timesrooms/cancelStack');
    }

    /**
     * Deletes a stack/cycle.
     *
     * @param String $cycle_id Id of the cycle to be deleted.
     */
    private function deleteStack($cycle_id = '')
    {
        foreach ($_SESSION['_checked_dates'] as $id) {
            $termin = CourseDate::find($id);
            if ($termin === null) {
                $termin = CourseExDate::find($id);
            }
            if ($termin->metadate_id && $termin instanceof CourseDate) {
                $this->deleteDate($id, 'cancel', $cycle_id);
            } elseif ($termin->metadate_id === null || $termin->metadate_id === '') {
                $this->deleteDate($id, 'delete', $cycle_id);
            } elseif ($termin->metadate_id && $termin instanceof CoursExDate) {
                //$this->deleteDate($id, 'delete', $cycle_id);
            }
        }
        $this->displayMessages();

        unset($_SESSION['_checked_dates']);

        if (Request::get('fromDialog') == 'true') {
            $this->redirect($this->url_for('course/timesrooms/index#' . $cycle_id,
                array('contentbox_open' => $cycle_id)));
        } else {
            $this->relocate('course/timesrooms/index#' . $cycle_id,
                array('contentbox_open' => $cycle_id));
        }
    }

    /**
     * Restores a previously deleted stack/cycle.
     *
     * @param String $cycle_id Id of the cycle to be restored.
     */
    private function unDeleteStack($cycle_id = '')
    {
        foreach ($_SESSION['_checked_dates'] as $id) {
            $ex_termin = CourseExDate::find($id);
            if ($ex_termin === null) {
                continue;
            }
            $ex_termin->content = '';
            $termin             = $ex_termin->unCancelDate();
            if ($termin !== null) {
                $this->course->createMessage(sprintf(_('Der Termin %s wurde wiederhergestellt!'), $termin->getFullname()));
            }
        }
        $this->displayMessages();
        unset($_SESSION['_checked_dates']);

        if (Request::get('fromDialog') == 'true') {
            $this->redirect($this->url_for('course/timesrooms/index#' . $cycle_id,
                array('contentbox_open' => $cycle_id)));
        } else {
            $this->relocate('course/timesrooms/index#' . $cycle_id,
                array('contentbox_open' => $cycle_id));
        }
    }

    /**
     * Saves a stack/cycle.
     *
     * @param String $cycle_id Id of the cycle to be saved.
     */
    public function saveStack_action($cycle_id = '')
    {
        switch (Request::get('method')) {
            case 'edit':
                $this->saveEditedStack($cycle_id);
                break;
            case 'preparecancel':
                $this->saveCanceledStack($cycle_id);
                break;
        }

        $this->displayMessages();

        unset($_SESSION['_checked_dates']);

        if (Request::get('fromDialog') == 'true') {
            $this->redirect($this->url_for('course/timesrooms/index#' . $cycle_id,
                array('contentbox_open' => $cycle_id)));
        } else {
            $this->relocate('course/timesrooms/index#' . $cycle_id,
                array('contentbox_open' => $cycle_id));
        }
    }

    /**
     * Saves a canceled stack/cycle.
     *
     * @param String $cycle_id Id of the canceled cycle to be saved.
     */
    private function saveCanceledStack($cycle_id = '')
    {
        $msg           = _('Folgende Termine wurden gelöscht') . '<ul>';
        $deleted_dates = array();

        foreach ($_SESSION['_checked_dates'] as $val) {
            $termin = CourseDate::find($val);
            if ($termin === null) {
                continue;
            }
            $termin->content = trim(Request::get('cancel_comment', ''));
            $new_ex_termin   = $termin->cancelDate();
            if ($new_ex_termin !== null) {
                $msg .= sprintf('<li>%s</li>', $new_ex_termin->getFullname());
            }
        }
        $msg .= '</ul>';
        $this->course->createMessage($msg);

        if (Request::int('cancel_send_message') && count($deleted_dates) > 0) {
            $snd_messages = raumzeit_send_cancel_message(Request::get('cancel_comment'), $deleted_dates);
            if ($snd_messages) {
                $this->course->createMessage(sprintf(_('Es wurden %u Benachrichtigungen gesendet.'), $snd_messages));
            }
        }
    }

    /**
     * Saves an edited stack/cycle.
     *
     * @param String $cycle_id Id of the edited cycle to be saved.
     */
    private function saveEditedStack($cycle_id = '')
    {
        $persons      = Request::getArray('related_persons');
        $action       = Request::get('related_persons_action');
        $groups       = Request::getArray('related_groups');
        $group_action = Request::get('related_groups_action');

        $teacher_changed = false;
        $groups_changed  = false;

        foreach ($_SESSION['_checked_dates'] as $singledate_id) {
            $singledate = CourseDate::find($singledate_id);
            if (!isset($singledate)) {
                $singledate = CourseExDate::find($singledate_id);
            }
            $singledates[] = $singledate;
        }

        // Update related persons
        if (in_array($action, words('add delete'))) {
            foreach ($singledates as $key => $singledate) {
                $dozenten     = User::findDozentenByTermin_id($singledate->termin_id);
                $dozenten_new = $dozenten;
                if ($singledate->range_id === $this->course->id) {
                    foreach ($persons as $user_id) {
                        $is_in_list = false;
                        foreach ($dozenten as $user_key => $user) {
                            if ($user->user_id == $user_id) {
                                $is_in_list = $user_key;
                            }
                        }

                        if ($is_in_list === false && $action === 'add') {
                            $dozenten_new[]  = User::find($user_id);
                            $teacher_changed = true;
                        } else if ($is_in_list !== false && $action === 'delete') {
                            unset($dozenten_new[$is_in_list]);
                            $teacher_changed = true;
                        }
                    }
                }
                $singledates[$key]->dozenten = $dozenten_new;
            }
        }

        if ($teacher_changed) {
            $this->course->createMessage(_('Zuständige Personen für die Termine wurden geändert.'));
        }

        if (in_array($group_action, words('add delete'))) {
            foreach ($singledates as $key => $singledate) {
                $groups_db  = Statusgruppen::findByTermin_id($singledate->termin_id);
                $groups_new = $groups_db;
                if ($singledate->range_id === $this->course->id) {
                    foreach ($groups as $statusgruppe_id) {
                        $is_in_list = false;
                        foreach ($groups_db as $group_key => $group) {
                            if ($statusgruppe_id == $group->statusgruppe_id) {
                                $is_in_list = $group_key;
                            }
                        }

                        if ($is_in_list === false && $group_action === 'add') {
                            $groups_new[]   = Statusgruppen::find($statusgruppe_id);
                            $groups_changed = true;
                        } elseif ($is_in_list !== false && $group_action === 'delete') {
                            unset($groups_new[$is_in_list]);
                            $groups_changed = true;
                        }
                    }
                }
                $singledates[$key]->statusgruppen = $groups_new;
            }
        }

        if ($groups_changed) {
            $this->course->createMessage(_('Zugewiesene Gruppen für die Termine wurden geändert.'));
        }

        foreach ($singledates as $key => $singledate) {
            if (Request::option('action') == 'room') {
                $singledate->raum = '';
                ResourceAssignment::deleteBySQL('assign_user_id = :termin',
                    array(':termin' => $singledate->termin_id));
                $resObj                             = new ResourceObject($room_id);
                $room                               = new ResourceAssignment();
                $room->assign_user_id               = $singledate->termin_id;
                $room->resource_id                  = Request::get('room');
                $room->begin                        = $singledate->date;
                $room->end                          = $singledate->end_time;
                $room->repeat_end                   = $singledate->end_time;
                $room->store();
            } elseif (Request::option('action') == 'freetext') {
                ResourceAssignment::deleteBySQL('assign_user_id = :termin',
                    array(':termin' => $singledate->termin_id));
                $singledates[$key]->raum = Request::get('freeRoomText');
                $this->course->createMessage(sprintf(_('Der Termin %s wurde geändert, etwaige '
                                                       . 'Raumbuchungen wurden entfernt und stattdessen der angegebene Freitext'
                                                       . ' eingetragen!'),
                    '<b>' . $singledate->getFullname() . '</b>'));
            } elseif (Request::option('action') == 'noroom') {
                ResourceAssignment::deleteBySQL('assign_user_id = :termin',
                    array(':termin' => $singledate->termin_id));
                $singledates[$key]->raum = '';
            }
        }

        foreach ($singledates as $singledate) {
            $singledate->store();
        }
    }

    /**
     * Creates a cycle.
     *
     * @param String $cycle_id Id of the cycle to be created (optional)
     */
    public function createCycle_action($cycle_id = null)
    {
        $this->restoreRequest(words('day start_time end_time description cycle startWeek teacher_sws fromDialog'));

        $this->editParams = array('fromDialog' => Request::get('fromDialog'));

        $this->cycle = new SeminarCycleDate($cycle_id);

        if ($this->cycle->isNew()) {
            $this->has_bookings = false;
        } else {
            $ids = $this->cycle->dates->pluck('termin_id');

            $count = ResourceAssignment::countBySQL('assign_user_id IN (?)', array($ids ?: ''));
            $this->has_bookings = $count > 0;
        }

        $duration = $this->course->duration_time;
        $this->start_weeks = $this->course->start_semester->getStartWeeks($duration);
        array_walk($this->start_weeks, function (&$value, $key) {
            $value = array(
                'text'     => $value,
                'selected' => $this->course->getStartWeek() == $key,
            );
        });
    }

    /**
     * Saves a cycle
     */
    public function saveCycle_action()
    {
        CSRFProtection::verifyRequest();

        $start = strtotime(Request::get('start_time'));
        $end   = strtotime(Request::get('end_time'));

        if (date('H', $start) > date('H', $end)) {
            $this->storeRequest();

            PageLayout::postMessage(MessageBox::error(_('Die Zeitangaben sind nicht korrekt. Bitte überprüfen Sie diese!')));
            $this->redirect('course/timesrooms/createCycle');
            return;
        }

        $cycle              = new SeminarCycleDate();
        $cycle->seminar_id  = $this->course->id;
        $cycle->weekday     = Request::int('day');
        $cycle->description = Request::get('description');
        $cycle->sws         = round(Request::float('teacher_sws'), 1);
        $cycle->cycle       = Request::int('cycle');
        $cycle->week_offset = Request::int('startWeek');
        $cycle->end_offset  = Request::int('endWeek') ?: null;
        $cycle->start_time  = date('H:i:00', $start);
        $cycle->end_time    = date('H:i:00', $end);

        if ($cycle->store()) {
            $cycle_info = $cycle->toString();
            NotificationCenter::postNotification('CourseDidChangeSchedule', $this);

            $this->course->createMessage(sprintf(_('Die regelmäßige Veranstaltungszeit %s wurde hinzugefügt!'), $cycle_info));
            $this->displayMessages();

            if (Request::get('fromDialog') == 'true') {
                $this->redirect('course/timesrooms/index');
            } else {
                $this->relocate('course/timesrooms/index');
            }
        } else {
            $this->storeRequest();

            $this->course->createError(_('Die regelmäßige Veranstaltungszeit konnte nicht hinzugefügt werden! Bitte überprüfen Sie Ihre Eingabe.'));
            $this->displayMessages();
            $this->redirect('course/timesrooms/createCycle');
        }
    }

    /**
     * Edits a cycle
     *
     * @param String $cycle_id Id of the cycle to be edited
     */
    public function editCycle_action($cycle_id)
    {
        $cycle = SeminarCycleDate::find($cycle_id);

        // Prepare Request for saving Request
        $cycle->start_time  = date('H:i:00', strtotime(Request::get('start_time')));
        $cycle->end_time    = date('H:i:00', strtotime(Request::get('end_time')));
        $cycle->weekday     = Request::int('day');
        $cycle->description = Request::get('description');
        $cycle->sws         = Request::get('teacher_sws');
        $cycle->cycle       = Request::get('cycle');
        $cycle->week_offset = Request::get('startWeek');
        $cycle->end_offset  = Request::int('endWeek') ?: null;

        if ($cycle->isDirty()) {
            $cycle->chdate = time();
            $cycle->store();
        } else {
            PageLayout::postInfo(_('Es wurden keine Änderungen vorgenommen'));
        }

        $this->redirect('course/timesrooms/index');
    }

    /**
     * Deletes a cycle
     *
     * @param String $cycle_id Id of the cycle to be deleted
     */
    public function deleteCycle_action($cycle_id)
    {
        CSRFProtection::verifyRequest();
        $cycle = SeminarCycleDate::find($cycle_id);
        if ($cycle !== null && $cycle->delete()) {
            $this->course->createMessage(sprintf(_('Der regelmäßige Eintrag "%s" wurde gelöscht.'), '<b>' . $cycle->toString() . '</b>'));
        }
        $this->displayMessages();

        $this->redirect('course/timesrooms/index');
    }

    /**
     * Add information to canceled / holiday date
     *
     * @param String $termin_id Id of the date
     */
    public function cancel_action($termin_id)
    {
        if (Request::get('asDialog')) {
            $this->asDialog = true;
        }
        $this->termin = CourseDate::find($termin_id) ?: CourseExDate::find($termin_id);
    }

    /**
     * Saves a comment for a given date.
     *
     * @param String $termin_id Id of the date
     */
    public function saveComment_action($termin_id)
    {
        $termin = CourseExDate::find($termin_id);
        if (Request::get('cancel_comment') != $termin->content) {
            $termin->content = Request::get('cancel_comment');
            if ($termin->store()) {
                $this->course->createMessage(sprintf(_('Der Kommtentar des gelöschten Termins %s wurde geändert.'), $termin->getFullname()));
            } else {
                $this->course->createInfo(sprintf(_('Der gelöschte Termin %s wurde nicht verändert.'), $termin->getFullname()));
            }
        } else {
            $this->course->createInfo(sprintf(_('Der gelöschte Termin %s wurde nicht verändert.'), $termin->getFullname()));
        }
        if (Request::int('cancel_send_message')) {
            $snd_messages = raumzeit_send_cancel_message(Request::get('cancel_comment'), $termin);
            if ($snd_messages) {
                $this->course->createInfo(sprintf(_('Es wurden %s Benachrichtigungen gesendet.'), $snd_messages));
            }
        }
        $this->displayMessages();
        $this->redirect($this->url_for('course/timesrooms/index#' . $termin->metadate_id, array('contentbox_open' => $termin->metadate_id)));
    }

    /**
     * Creates the sidebar
     */
    private function setSidebar()
    {
        $actions = new ActionsWidget();
        $actions->addLink(_('Startsemester ändern'), $this->url_for('course/timesrooms/editSemester'), Icon::create('date', 'clickable'))->asDialog('size=400');
        Sidebar::Get()->addWidget($actions);
        $widget = new SelectWidget(_('Semesterfilter'), $this->url_for('course/timesrooms/index', array('cmd' => 'applyFilter')), 'newFilter');
        foreach ($this->selection as $item) {
            $element = new SelectElement($item['value'],
                $item['linktext'],
                $item['is_selected']);
            $widget->addElement($element);
        }
        Sidebar::Get()->addWidget($widget);

        if ($GLOBALS['perm']->have_perm('admin')) {
            $list = new SelectorWidget();
            $list->setUrl($this->url_for('/index'));
            $list->setSelectParameterName('cid');
            foreach (AdminCourseFilter::get()->getCourses(false) as $seminar) {
                $element = new SelectElement($seminar['Seminar_id'],
                    $seminar['Name']);
                $list->addElement($element, 'select-' . $seminar['Seminar_id']);
            }
            $list->setSelection($this->course->id);
            Sidebar::Get()->addWidget($list);
        }
    }

    /**
     * Sets the start semester for the given course.
     *
     * @param String $course_id Id of the course
     */
    public function setSemester_action($course_id)
    {
        $current_semester = Semester::findCurrent();
        $start_semester   = Semester::find(Request::get('startSemester'));
        if (Request::int('endSemester') != -1) {
            $end_semester = Semester::find(Request::get('endSemester'));
        } else {
            $end_semester = -1;
        }

        $course = Seminar::GetInstance($course_id);
        if ($start_semester == $end_semester) {
            $end_semester = 0;
        }

        if ($end_semester != 0 && $end_semester != -1 && $start_semester->beginn >= $end_semester->beginn) {
            PageLayout::postMessage(MessageBox::error(_('Das Startsemester liegt nach dem Endsemester!')));
        } else {

            $course->setStartSemester($start_semester->beginn);
            if ($end_semester != -1) {
                $course->setEndSemester($end_semester->beginn);
            } else {
                $course->setEndSemester($end_semester);
            }
            //$course->removeAndUpdateSingleDates();

            // If the new duration includes the current semester, we set the semester-chooser to the current semester
            if ($current_semester->beginn >= $course->getStartSemester() && $current_semester->beginn <= $course->getEndSemesterVorlesEnde()) {
                $course->setFilter($current_semester->beginn);
            } else {
                // otherwise we set it to the first semester
                $course->setFilter($course->getStartSemester());
            }
        }

        $course->store();

        SeminarCycleDate::removeOutRangedSingleDates($course->getStartSemester(), $course->getEndSemesterVorlesEnde(), $course->id);
        $cycles = SeminarCycleDate::findBySeminar_id($course->seminar_id);
        foreach ($cycles as $cycle) {
            $new_dates = $cycle->createTerminSlots($start_semester->beginn);
            foreach ($new_dates as $semester_id => $dates) {
                foreach ($dates['dates'] as $date) {
                    $date->store();
                }
            }
        }

        $messages = $course->getStackedMessages();
        foreach ($messages as $type => $msg) {
            PageLayout::postMessage(MessageBox::$type($msg['title'], $msg['details']));
        }

        if (Request::submitted('save_close')) {
            $this->relocate(str_replace('_', '/', Request::get('origin')), array('cid' => $course_id));
        } else {
            $this->redirect($this->url_for('course/timesrooms/index', array('cid' => $course_id)));
        }
    }

    /**
     * Displays messages.
     *
     * @param Array $messages Messages to display (optional, defaults to
     *                        potential stored messages on course object)
     */
    private function displayMessages(array $messages = array())
    {
        $messages = $messages ?: $this->course->getStackedMessages();
        foreach ((array)$messages as $type => $msg) {
            PageLayout::postMessage(MessageBox::$type($msg['title'], $msg['details']));
        }
    }

    /**
     * Deletes a date.
     *
     * @param String $termin_id Id of the date
     * @param String $sub_cmd Sub command to be executed
     * @param String $cycle_id Id of the associated cycle
     */
    private function deleteDate($termin_id, $sub_cmd, $cycle_id)
    {
        //cancel cycledate entry
        if ($sub_cmd === 'cancel') {
            $termin     = CourseDate::find($termin_id);
            $seminar_id = $termin->range_id;
            $room       = $termin->getRoom();
            $termin->cancelDate();
            log_event('SEM_DELETE_SINGLEDATE', $termin_id, $seminar_id, 'Cycle_id: ' . $cycle_id);
            //delete singledate entry
        } else if ($sub_cmd === 'delete') {
            $termin      = CourseDate::find($termin_id) ?: CourseExDate::find($termin_id);
            $seminar_id  = $termin->range_id;
            $termin_room = $termin->getRoom();
            $termin_date = $termin->getFullname();
            if ($termin->delete()) {
                log_event("SEM_DELETE_SINGLEDATE", $termin_id, $seminar_id, 'appointment cancelled');
                if (Request::get('approveDelete')) {
                    if (Config::get()->RESOURCES_ENABLE_EXPERT_SCHEDULE_VIEW) {
                        $this->course->createMessage(sprintf(_('Sie haben den Termin %s gelöscht, dem ein Thema zugeorndet war.'
                                                               . ' Sie können das Thema in der %sExpertenansicht des Ablaufplans%s einem anderen Termin (z.B. einem Ausweichtermin) zuordnen.'),
                            $termin_date, '<a href="' . URLHelper::getLink('themen.php?cmd=changeViewMode&newFilter=expert') . '">', '</a>'));
                    } elseif ($room) {
                        $this->course->createMessage(sprintf(_('Der Termin %s wurde gelöscht! Die Buchung für den Raum %s wurde gelöscht.'),
                            $termin_date, $termin_room));
                    } else {
                        $this->course->createMessage(sprintf(_('Der Termin %s wurde gelöscht!'), $termin_date));
                    }
                } else {
                    // no approval needed, delete unquestioned
                    $this->course->createMessage(sprintf(_('Der Termin %s wurde gelöscht!'), $termin_date));
                }
            }
        }
    }

    /**
     * Checks and adjusts defined filters
     */
    private function checkFilter()
    {
        if (Request::option('cmd') == 'applyFilter') {
            $_SESSION['raumzeitFilter'] = Request::get('newFilter');
        }

        if ($this->course->getEndSemester() == 0 && !$this->course->hasDatesOutOfDuration()) {
            $_SESSION['raumzeitFilter'] = $this->course->getStartSemester();
        }

        // Zeitfilter anwenden
        if ($_SESSION['raumzeitFilter'] == '') {
            $_SESSION['raumzeitFilter'] = 'all';
        }

        if ($_SESSION['raumzeitFilter'] != 'all') {
            if (($_SESSION['raumzeitFilter'] < $this->course->getStartSemester()) || ($_SESSION['raumzeitFilter'] > $this->course->getEndSemesterVorlesEnde())) {
                $_SESSION['raumzeitFilter'] = $this->course->getStartSemester();
            }
            $semester       = new SemesterData();
            $filterSemester = $semester->getSemesterDataByDate($_SESSION['raumzeitFilter']);
            $this->course->applyTimeFilter($filterSemester['beginn'], $filterSemester['ende']);
        }
    }

    /**
     * Get all semesters that a course spans over.
     *
     * @param Seminar $course   The course as a Seminar object
     * @param String  $selected Selected semester (can be updated)
     */
    private function getSemestersForCourse(Seminar $course, &$selected)
    {
        // Step 1: Get all matching semesters
        $semesters = array_filter(Semester::getAll(), function (Semester $semester) use ($course) {
            return $course->getStartSemester() <= $semester->vorles_beginn
                && $course->getEndSemesterVorlesEnde() >= $semester->vorles_ende;
        });

        // Step 2: Add option 'all' if more than one semester is found or if
        // there is any date outside of the semester range. Otherwise, adjust
        // the $selected variable
        $temp = array();
        if (count($semesters) > 1 || $course->hasDatesOutOfDuration(true)) {
            $temp['all'] = _('Alle Semester');
        } elseif (count($semesters) === 1) {
            $semester = reset($semesters);
            $selected = $semester->beginn;
        }

        // Step 3: Normalize semesters array (with option 'all' this needs
        // to be in a pretty simple format)
        $result = array();
        foreach (array_reverse($semesters) as $semester) {
            $temp[$semester->beginn] = $semester->name;
        }

        // Step 4: Create required result array from normalized array
        $result = array();
        foreach ($temp as $key => $val) {
            $result[] = array(
                'url'         => '?cmd=applyFilter&newFilter=' . $key,
                'value'       => $key,
                'linktext'    => $val,
                'is_selected' => $selected == $key,
            );
        }

        return $result;
    }

    /**
     * Redirects to another location.
     *
     * @param String $to New location
     */
    public function redirect($to)
    {
        $arguments = func_get_args();

        if (Request::isXhr()) {
            $url = call_user_func_array('parent::url_for', $arguments);

            $url_chunk = Trails_Inflector::underscore(substr(get_class($this), 0, -10));
            $index_url = $url_chunk . '/index';

            if (strpos($url, $index_url) !== false) {
                $this->flash['update-times'] = $this->course->id;
            }
        }

        return call_user_func_array('parent::redirect', $arguments);
    }

    /**
     * Stores a request into trails' flash object
     */
    private function storeRequest()
    {
        $this->flash['request'] = Request::getInstance();
    }

    /**
     * Restores a previously stored request from trails' flash object
     */
    private function restoreRequest(array $fields)
    {
        $request = $this->flash['request'];

        if ($request) {
            foreach ($fields as $field) {
                Request::set($field, $request[$field]);
            }
        }
    }
}
