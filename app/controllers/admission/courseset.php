<?php

require_once('app/controllers/authenticated_controller.php');
require_once('app/models/courseset.php');
require_once('lib/classes/Institute.class.php');
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
        // Open base layout for normal 
        } else {
            $layout = $GLOBALS['template_factory']->open('layouts/base');
            $this->set_layout($layout);
            PageLayout::setTitle(_('Anmeldesets'));
            Navigation::activateItem('/tools/coursesets/sets');
        }
        PageLayout::addSqueezePackage('admission');
		PageLayout::addStylesheet('form.css');
    }

    /**
     * Show all coursesets the current user has access to.
     */
    public function index_action() {
        // Fetch the institutes that current user is assigned to...
        $institutes = Institute::getMyInstitutes();
        $this->myInstitutes = array();
        // ... with at least the permission "dozent".
        foreach ($institutes as $institute) {
            if (in_array($institute['inst_perms'], array('dozent', 'admin'))) {
                $this->myInstitutes[$institute['Institut_id']] = $institute;
            }
        }
        $this->coursesets = array();
        foreach ($this->myInstitutes as $institute) {
            $sets = CourseSet::getCoursesetsByInstituteId($institute['Institut_id']);
            foreach ($sets as $set) {
                $courseset = new CourseSet($set['set_id']);
                $this->coursesets[$set['set_id']] = $courseset;
            }
        }
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
				$allCourses = CoursesetModel::getInstCourses(array_keys($this->selectedInstitutes), $coursesetId);
				$selectedCourses = $this->courseset->getCourses();
			} else {
	    		$this->myInstitutes = array();
				$this->selectedInstitutes = array();
				$allCourses = array();
				$selectedCourses = array();
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
                $allCourses = CoursesetModel::getInstCourses(array_keys($this->selectedInstitutes), $coursesetId);
                $selectedCourses = $this->courseset->getCourses();
            } else {
                $this->selectedInstitutes = array();
                $allCourses = CoursesetModel::getInstCourses(array_keys($this->myInstitutes), $coursesetId);
                $selectedCourses = array();
            }
		}
		// If an institute search has been conducted, we need to consider parameters from flash.
        if ($this->flash['name'] || $this->flash['institutes'] || $this->flash['courses'] ||
				$this->flash['rules'] || $this->flash['userlists'] || $this->flash['infotext']) {
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
        $this->instTpl = $tpl->render();
    }

    public function save_action($coursesetId='') {
        if (Request::submitted('submit')) {
            $courseset = new CourseSet($coursesetId);
            $courseset->setName(Request::get('name'))
                ->setInstitutes(Request::getArray('institutes'))
                ->setCourses(Request::getArray('courses'))
                ->setUserLists(Request::getArray('userlists'))
                ->setPrivate((bool) Request::get('private'))
                ->clearAdmissionRules();
            foreach (Request::getArray('rules') as $serialized) {
                $rule = unserialize($serialized);
                $courseset->addAdmissionRule($rule);
            }
            $courseset->store();
	        $this->redirect('admission/courseset');
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
            $this->redirect($this->url_for('admission/courseset/configure', $coursesetId));
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
        CSRFProtection::verifyUnsafeRequest();
        $this->selectedCourses = array();
        if ($coursesetId && !Request::getArray('courses')) {
            $courseset = new CourseSet($coursesetId);
            $this->selectedCourses = $courseset->getCourses();
        } else if (Request::getArray('courses')) {
            $this->selectedCourses = Request::getArray('courses');
        }
        $this->allCourses = CoursesetModel::getInstCourses(Request::getArray('institutes'), 
        	$coursesetId, $this->selectedCourses);
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
}

?>