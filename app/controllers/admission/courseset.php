<?php

require_once('app/controllers/authenticated_controller.php');
require_once('app/models/courseset.php');
require_once('app/models/rule_administration.php');
require_once('lib/classes/admission/CourseSet.class.php');
require_once('lib/classes/admission/AdmissionUserList.class.php');
require_once('lib/classes/admission/RandomAlgorithm.class.php');

class Admission_CoursesetController extends AuthenticatedController {

    /**
     * Things to do before every page load.
     */
    public function before_filter(&$action, &$args) {
        parent::before_filter($action, $args);
        // AJAX request, so no page layout.
        if (Request::isXhr()) {
            $this->via_ajax = true;
            $this->set_layout(null);
            $request = Request::getInstance();
            foreach ($request as $key => $value) {
                $request[$key] = studip_utf8decode($value);
            }
        // Open base layout for normal 
        } else {
            $layout = $GLOBALS['template_factory']->open('layouts/base');
            $this->set_layout($layout);
            PageLayout::setTitle(_('Anmeldesets'));
            // Get only own courses if user doesn't have permission to edit institute-wide coursesets.
            $this->onlyOwnCourses = true;
            if ($GLOBALS['perm']->have_perm('admin') || ($GLOBALS['perm']->have_perm('dozent') && get_config('ALLOW_DOZENT_COURSESET_ADMIN'))) {
                // We have access to institute-wide course sets, so all courses may be assigned.
                $this->onlyOwnCourses = false;
                Navigation::activateItem('/tools/coursesets/sets');
            } else {
                throw new AccessDeniedException();
            }
        }
        PageLayout::addSqueezePackage('admission');
        $this->set_content_type('text/html;charset=windows-1252');
    }

    /**
     * Show all coursesets the current user has access to.
     */
    public function index_action() {
        // Check for correct permissions.
        //$allowed = (get_config('ALLOW_DOZENT_COURSESET_ADMIN') ? 'dozent' : 'admin');
        //$GLOBALS['perm']->check($allowed);
        $this->ruleTypes = RuleAdministrationModel::getAdmissionRuleTypes();
        $this->coursesets = array();
        if (Request::submitted('choose_institut')) {
            $this->current_institut_id = Request::option('choose_institut_id');
            $this->current_rule_types = Request::getArray('choose_rule_type');
            $this->set_name_prefix = trim(Request::get('set_name_prefix'));
            $this->current_semester_id = Request::option('select_semester_id');
        }
        if ($this->current_semester_id === null) {
            $this->current_semester_id = $_SESSION['_default_sem'];
        } else if ($this->current_semester_id !== '0') {
            $_SESSION['_default_sem'] = $this->current_semester_id;
        }
        if (!count($this->current_rule_types) && !Request::submitted('choose_institut')) {
            $this->current_rule_types['ParticipantRestrictedAdmission'] = true;
        }
        $filter['course_set_name'] = $this->set_name_prefix;
        $filter['semester_id'] = $this->current_semester_id != 'all' ? $this->current_semester_id : null;
        $filter['rule_types'] = array_keys($this->current_rule_types);
        $this->myInstitutes = CoursesetModel::getInstitutes($filter);
        if (!$this->current_institut_id) {
            if ($this->myInstitutes['all']['num_sets'] < 100) {
                $this->current_institut_id = 'all';
            } else {
                next($this->myInstitutes);
                $this->current_institut_id = key($this->myInstitutes);
                reset($this->myInstitutes);
            }
        }
        list($institut_id, $all) = explode('_', $this->current_institut_id);
        if ($institut_id == 'all') {
            $institutes = array_keys($this->myInstitutes);
        } else if ($all == 'all') {
            $institutes[] = $institut_id;
            $institutes = array_merge($institutes, Institute::find($institut_id)->sub_institutes->pluck('institut_id'));
        } else {
            $institutes = array($institut_id);
        }

        foreach ($institutes as $one) {
            if ($this->myInstitutes[$one]['num_sets']) {
                $sets = CourseSet::getCoursesetsByInstituteId($one, $filter);
                foreach ($sets as $set) {
                    $courseset = new CourseSet($set['set_id']);
                    $this->coursesets[$set['set_id']] = $courseset;
                }
            }
        }
        uasort($this->coursesets, function($a,$b) {
            return strnatcasecmp($a->getName(), $b->getName());
        });
    }

    /**
     * Configure a new or existing course set.
     */
    public function configure_action($coursesetId='') {
        if ($GLOBALS['perm']->have_perm('root')) {
            if ($coursesetId) {
                // Load course set data.
                $this->courseset = new CourseSet($coursesetId);
                $this->myInstitutes = array();
                $selectedInstitutes = $this->courseset->getInstituteIds();
                foreach ($selectedInstitutes as $id => $selected) {
                    $this->myInstitutes[$id] = new Institute($id); 
                }
                $this->selectedInstitutes = $this->myInstitutes;
                $allCourses = CoursesetModel::getInstCourses(array_keys($this->selectedInstitutes), $coursesetId, array(), $this->courseset->getSemester());
                $selectedCourses = $this->courseset->getCourses();
                $this->selectedSemester = $this->courseset->getSemester();
            } else {
                $this->myInstitutes = array();
                $this->selectedInstitutes = array();
                $allCourses = array();
                $selectedCourses = array();
                $this->selectedSemester = Semester::findCurrent()->semester_id;
            }
            $this->instSearch = QuickSearch::get("institute_id", new StandardSearch("Institut_id"))
                ->withButton()
                ->render();
        } else {
            $this->myInstitutes = array();
            $myInstitutes = Institute::getMyInstitutes();
            foreach ($myInstitutes as $institute) {
                $this->myInstitutes[$institute['Institut_id']] = $institute;
            }
            if ($coursesetId) {
                // Load course set data.
                $this->courseset = new CourseSet($coursesetId);
                $selectedInstitutes = $this->courseset->getInstituteIds();
                foreach ($selectedInstitutes as $id => $selected) {
                    $this->selectedInstitutes[$id] = new Institute($id); 
                }
                $allCourses = CoursesetModel::getInstCourses(array_keys($this->selectedInstitutes), $coursesetId, array(), $this->courseset->getSemester(), $this->onlyOwnCourses);
                $selectedCourses = $this->courseset->getCourses();
                $this->selectedSemester = $this->courseset->getSemester();
            } else {
                $this->selectedSemester = Semester::findCurrent()->semester_id;
                $this->selectedInstitutes = $this->myInstitutes;
                $allCourses = CoursesetModel::getInstCourses(array_keys($this->myInstitutes), $coursesetId, array(), $this->selectedSemester, $this->onlyOwnCourses);
                $selectedCourses = array();
            }
        }
        // If an institute search has been conducted, we need to consider parameters from flash.
        if ($this->flash['name'] || $this->flash['institutes'] || $this->flash['courses'] ||
                $this->flash['rules'] || $this->flash['userlists'] || $this->flash['infotext'] ||
                $this->flash['semester']) {
            if (!$this->courseset) {
                $this->courseset = new CourseSet($coursesetId);
            }
            if ($this->flash['name']) {
                $this->courseset->setName($this->flash['name']);
            }
            if ($this->flash['institutes']) {
                $institutes = $this->flash['institutes'];
                $this->courseset->setInstitutes($institutes);
                if ($GLOBALS['perm']->have_perm('root')) {
                    $this->myInstitutes = array();
                    foreach ($institutes as $id) {
                        $this->myInstitutes[$id] = new Institute($id); 
                        $this->selectedInstitutes[$id] = true;
                    }
                }
                $allCourses = CoursesetModel::getInstCourses(array_flip($institutes), $coursesetId);
                $selectedCourses = $this->courseset->getCourses();
            }
            if ($this->flash['courses']) {
                $courses = $this->flash['courses'];
                $this->courseset->setCourses($courses);
                $selectedCourses = $courses;
            }
            if ($this->flash['rules']) {
                $this->courseset->setAdmissionRules($this->flash['rules']);
            }
            if ($this->flash['userlists']) {
                $this->courseset->setUserlists($this->flash['userlists']);
            }
            if ($this->flash['infotext']) {
                $this->courseset->setInfoText($this->flash['infotext']);
            }
            if ($this->flash['privatet']) {
                $this->courseset->setPrivate($this->flash['private']);
            }
            if ($this->flash['semester']) {
                $this->courseset->setSemester($this->flash['semester']);
            }
        }
        $fac = $this->get_template_factory();
        $tpl = $fac->open('admission/courseset/instcourses');
        $tpl->set_attribute('allCourses', $allCourses);
        $tpl->set_attribute('selectedCourses', $selectedCourses);
        $this->coursesTpl = $tpl->render();
        $tpl = $fac->open('admission/courseset/institutes');
        if ($coursesetId) {
            $tpl->set_attribute('courseset', $this->courseset);
        }
        $tpl->set_attribute('instSearch', $this->instSearch);
        $tpl->set_attribute('selectedInstitutes', $this->selectedInstitutes);
        $tpl->set_attribute('myInstitutes', $this->myInstitutes);
        $tpl->set_attribute('controller', $this);
        if ($GLOBALS['perm']->have_perm('admin') || ($GLOBALS['perm']->have_perm('dozent') && get_config('ALLOW_DOZENT_COURSESET_ADMIN'))) {
            $tpl->set_attribute('rights', true);
        } else {
            $tpl->set_attribute('rights', false);
        }
        $this->instTpl = $tpl->render();
    }

    public function save_action($coursesetId='') {
        if (Request::submitted('submit')) {
            $courseset = new CourseSet($coursesetId);
            if (!$courseset->getUserId()) {
                $courseset->setUserId($GLOBALS['user']->id);
            }
            $courseset->setName(Request::get('name'));
            if (Request::submitted('institutes')) {
                $courseset->setInstitutes(Request::getArray('institutes'));
            }
            if (Request::submitted('semester')) {
                $courseset->setSemester(Request::option('semester'));
                $courseset->setCourses(Request::getArray('courses'));
            }
            if (Request::submitted('userlists')) {
                $courseset->setUserLists(Request::getArray('userlists'));
            }
            if (!$this->instant_course_set_view && $courseset->getUserId() == $GLOBALS['user']->id) {
                $courseset->setPrivate((bool) Request::get('private'));
            }
            if (Request::submitted('infotext')) {
                $courseset->setInfoText(Request::get('infotext'));
            }
            $courseset->clearAdmissionRules();
            foreach (Request::getArray('rules') as $serialized) {
                $rule = unserialize($serialized);
                $courseset->addAdmissionRule($rule);
            }
            $courseset->store();
            PageLayout::postMessage(MessageBox::success(sprintf(_("Das Anmeldeset: %s wurde gespeichert"), htmlReady($courseset->getName()))));
            if ($this->instant_course_set_view) {
                $this->redirect($this->url_for('course/admission'));
            } else {
                $this->redirect($this->url_for('admission/courseset'));
            }
        } else {
            $this->flash['name'] = Request::get('name');
            $this->flash['institutes'] = Request::getArray('institutes');
            $this->flash['courses'] = Request::getArray('courses');
            $this->flash['rules'] = Request::getArray('rules');
            $this->flash['userlists'] = Request::getArray('userlists');
            $this->flash['infotext'] = Request::get('infotext');
            $this->flash['private'] = (bool) Request::get('private');
            if (Request::submitted('add_institute')) {
                $this->flash['institutes'] = array_merge($this->flash['institutes'], array(Request::option('institute_id')));
            } else {
                $this->flash['institute_id'] = Request::get('institute_id');
                $this->flash['institute_id_parameter'] = Request::get('institute_id_parameter');
            }
            if ($this->instant_course_set_view) {
                $this->redirect($this->url_for('course/admission/edit_courseset/' . $coursesetId));
            } else {
                $this->redirect($this->url_for('admission/courseset/configure', $coursesetId));
            }
        }
    }

    public function delete_action($coursesetId) {
        $this->courseset = new CourseSet($coursesetId);
        if (Request::int('really')) {
            $this->courseset->delete();
            $this->redirect($this->url_for('admission/courseset'));
        }
        if (Request::int('cancel')) {
            $this->redirect($this->url_for('admission/courseset'));
        }
    }

    public function instcourses_action($coursesetId='') {
        //CSRFProtection::verifyUnsafeRequest();
        $this->selectedCourses = array();
        if ($coursesetId && !Request::getArray('courses')) {
            $courseset = new CourseSet($coursesetId);
            $this->selectedCourses = $courseset->getCourses();
        } else if (Request::getArray('courses')) {
            $this->selectedCourses = Request::getArray('courses');
        }
        $this->allCourses = CoursesetModel::getInstCourses(Request::getArray('institutes'), 
            $coursesetId, $this->selectedCourses, Request::option('semester'), $this->onlyOwnCourses);
    }

    public function institutes_action() {
        CSRFProtection::verifyUnsafeRequest();
        $this->myInstitutes = Institute::getMyInstitutes();
        $this->selectedInstitutes = array();
        foreach(Request::getArray('institutes') as $institute) {
            $this->selectedInstitutes[$institute] = new Institute($institute);
        }
        $this->instSearch = QuickSearch::get("institute_id", new StandardSearch("Institut_id"))
            ->withButton()
            ->render();
    }
    
    public function configure_courses_action($set_id, $csv = null)
    {
        if (Request::isXhr()) {
            $this->response->add_header('X-Title', _('Ausgewählte Veranstaltungen konfigurieren'));
            $this->response->add_header('X-No-Buttons', 1);
        }
        $courseset = new CourseSet($set_id);
        $this->set_id = $courseset->getId();
        $this->courses = Course::findMany($courseset->getCourses(), "ORDER BY Name");
        $this->applications = AdmissionPriority::getPrioritiesStats($courseset->getId());
        if ($csv) {
            $captions = array(_("Nummer"), _("Name"), _("Dozenten"), _("max. Teilnehmer"), _("Teilnehmer aktuell"), _("Anzahl Anmeldungen"),_("Anzahl Anmeldungen Prio 1"), _("Warteliste"), _("max. Anzahl Warteliste"));
            $data = array();
            foreach ($this->courses as $course) {
                $row = array();
                $row[] = $course->veranstaltungsnummer;
                $row[] = $course->name;
                $row[] = join(', ', $course->members->findBy('status','dozent')->orderBy('position')->pluck('Nachname'));
                $row[] = $course->admission_turnout;
                $row[] = count($course->members->findBy('status', words('user autor')));
                $row[] = $this->applications[$course->id]['c'];
                $row[] = $this->applications[$course->id]['h'];
                $row[] = $course->admission_disable_waitlist ? _("ja") : _("nein");
                $row[] = $course->admission_waitlist_max > 0 ? $course->admission_waitlist_max : '';
                $data[] = $row;
            }
            $tmpname = md5(uniqid('tmp'));
            if (array_to_csv($data, $GLOBALS['TMP_PATH'].'/'.$tmpname, $captions)) {
                $this->redirect(GetDownloadLink($tmpname, 'Veranstaltungen_' . $courseset->getName() . '.csv', 4, 'force'));
                return;
            }
        }
        if (Request::submitted('configure_courses_save')) {
            CSRFProtection::verifyUnsafeRequest();
            $admission_turnouts = Request::intArray('configure_courses_turnout');
            $admission_waitlists = Request::intArray('configure_courses_disable_waitlist');
            $admission_waitlists_max = Request::intArray('configure_courses_waitlist_max');
            $ok = 0;
            foreach($this->courses as $course) {
                $course->admission_turnout = $admission_turnouts[$course->id];
                $course->admission_disable_waitlist = isset($admission_waitlists[$course->id]) ? 0 : 1;
                $course->admission_waitlist_max = $course->admission_disable_waitlist ? 0 : $admission_waitlists_max[$course->id];
                $ok += $course->store();
            }
            if ($ok) {
                PageLayout::postMessage(MessageBox::success(_("Die zugeordneten Veranstaltungen wurden konfiguriert.")));
            }
            $this->redirect($this->url_for('admission/courseset/configure/' . $courseset->getId()));
            return;
        }
        $this->set_content_type('text/html; charset=windows-1252');
    }
    
    public function factored_users_action($set_id)
    {
        $this->set_content_type('text/html; charset=windows-1252');
        if (Request::isXhr()) {
            $this->response->add_header('X-Title', _('Liste der Nutzer'));
        }
        $courseset = new CourseSet($set_id);
        $factored_users = $courseset->getUserFactorList();
        $applicants = AdmissionPriority::getPriorities($set_id);
        $this->users = User::findAndMapMany(function($u) use ($factored_users, $applicants) {
                                          return array_merge($u->toArray('username vorname nachname'), array('applicant' => isset($applicants[$u->id]), 'factor' => $factored_users[$u->id]));                      
                                       }, array_keys($factored_users), 'ORDER BY Nachname');
    }
    
    public function applications_list_action($set_id, $csv = null)
    {
        $this->set_content_type('text/html; charset=windows-1252');
        if (Request::isXhr()) {
            $this->response->add_header('X-Title', _('Liste der Anmeldungen'));
        }
        $courseset = new CourseSet($set_id);
        $applicants = AdmissionPriority::getPriorities($set_id);
        $users = User::findMany(array_keys($applicants), 'ORDER BY Nachname');
        $courses = SimpleCollection::createFromArray(Course::findMany($courseset->getCourses()));
        $captions = array(_("Nachname"), _("Vorname"), _("Nutzername"), _("Veranstaltung"), _("Nummer"), _("Priorität"));
        $data = array();
        foreach ($users as $user) {
            $row = array();
            $app_courses = $applicants[$user->id];
            asort($app_courses);
            foreach ($app_courses as $course_id => $prio) {
                $row = array();
                $row[] = $user->nachname;
                $row[] = $user->vorname;
                $row[] = $user->username;
                $row[] = $courses->findOneBy('id', $course_id)->name;
                $row[] = $courses->findOneBy('id', $course_id)->veranstaltungsnummer;
                $row[] = $prio;
                $data[] = $row;
            }
        }
        if ($csv) {
            $tmpname = md5(uniqid('tmp'));
            if (array_to_csv($data, $GLOBALS['TMP_PATH'].'/'.$tmpname, $captions)) {
                $this->redirect(GetDownloadLink($tmpname, 'Anmeldungen_' . $courseset->getName() . '.csv', 4, 'force'));
                return;
            }
        }
        $this->captions = $captions;
        $this->data = $data;
        $this->set_id = $courseset->getId();
    }
    
    function get_courses($seminare_condition)
    {
        global $perm, $user;

        list($institut_id, $all) = explode('_', $this->current_institut_id);
        // Prepare count statements
        $query = "SELECT count(*)
                FROM seminar_user
                WHERE seminar_id = ? AND status IN ('user', 'autor')";
        $count0_statement = DBManager::get()->prepare($query);

        $query = "SELECT SUM(status = 'accepted') AS count2,
                SUM(status = 'awaiting') AS count3
                FROM admission_seminar_user
                WHERE seminar_id = ?
                GROUP BY seminar_id";
        $count1_statement = DBManager::get()->prepare($query);

        $parameters = array();

        $sql = "SELECT seminare.seminar_id,seminare.Name as course_name,seminare.VeranstaltungsNummer as course_number,
                admission_prelim, admission_turnout,seminar_courseset.set_id
                FROM seminar_courseset
                INNER JOIN courseset_rule csr ON csr.set_id=seminar_courseset.set_id AND csr.type='ParticipantRestrictedAdmission'
                INNER JOIN seminare ON seminar_courseset.seminar_id=seminare.seminar_id
                ";
        if ($institut_id == 'all'  && $perm->have_perm('root')) {
            $sql .= "WHERE 1 {$seminare_condition} ";
        } elseif ($all == 'all') {
            $sql .= "INNER JOIN Institute USING (Institut_id)
            WHERE Institute.fakultaets_id = ? {$seminare_condition}
            ";
            $parameters[] = $institut_id;
        } else {
            $sql .= "WHERE seminare.Institut_id = ? {$seminare_condition}
            ";
            $parameters[] = $institut_id;
        }
        $sql .= "GROUP BY seminare.Seminar_id ORDER BY seminar_courseset.set_id, seminare.Name";

        $statement = DBManager::get()->prepare($sql);
        $statement->execute($parameters);

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $seminar_id = $row['seminar_id'];
            $ret[$seminar_id] = $row;

            $count0_statement->execute(array($seminar_id));
            $count = $count0_statement->fetchColumn();

            $ret[$seminar_id]['count_teilnehmer']     = $count;

            $count1_statement->execute(array($seminar_id));
            $counts = $count1_statement->fetch(PDO::FETCH_ASSOC);

            $ret[$seminar_id]['count_prelim'] = (int)$counts['count2'];
            $ret[$seminar_id]['count_waiting']  = (int)$counts['count3'];
            $cs = new CourseSet($row['set_id']);
            $ret[$seminar_id]['cs_name'] = $cs->getName();
            $ret[$seminar_id]['distribution_time'] = $cs->getSeatDistributionTime();
            if ($ta = $cs->getAdmissionRule('TimedAdmission')) {
                $ret[$seminar_id]['start_time'] = $ta->getStartTime();
                $ret[$seminar_id]['end_time'] = $ta->getEndTime();
            }
            if (!$cs->hasAlgorithmRun()) {
                $ret[$seminar_id]['count_claiming'] = $cs->getNumApplicants();
            }
        }
        return $ret;
    }

}

?>