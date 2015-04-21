<?php
/**
 * courses.php - Controller for admin and seminar related
 * pages under "Meine Veranstaltungen"
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * @author      David Siegfried <david@ds-labs.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2 or later
 * @category    Stud.IP
 * @since       3.1
 */
require_once 'app/models/my_realm.php';
require_once 'app/controllers/authenticated_controller.php';
require_once 'lib/meine_seminare_func.inc.php';
require_once 'lib/object.inc.php';

class Admin_CoursesController extends AuthenticatedController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        if (!$GLOBALS['perm']->have_perm('admin')) {
            throw new AccessDeniedException(_('Sie haben nicht die nötigen Rechte, um diese Seite zu betreten.'));
        }

        Navigation::activateItem('/browse/my_courses/list');

        // we are defintely not in an lecture or institute
        closeObject();
        $_SESSION['links_admin_data'] = ''; // TODO: Still neccessary?.

        //delete all temporary permission changes
        if (is_array($_SESSION)) {
            foreach (array_keys($_SESSION) as $key) {
                if (strpos($key, 'seminar_change_view_') !== false) {
                    unset($_SESSION[$key]);
                }
            }
        }

        $this->insts      = Institute::getMyInstitutes($GLOBALS['user']->id);
        $selected_inst_id = $GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT;
        if (!$selected_inst_id) {
            $GLOBALS['user']->cfg->store('MY_INSTITUTES_DEFAULT', $this->insts[0]['Institut_id']);
        }
        // Look for Inst-Perms
        $this->selected_inst_id = ($selected_inst_id == '' || !$GLOBALS['perm']->have_studip_perm('admin', $selected_inst_id))
            ? $this->insts[0]['Institut_id'] : $selected_inst_id;
        $this->selected_inst    = Institute::find(($this->selected_inst_id == '' ?: $this->selected_inst_id));

        // Semester selection
        $config_sem = $GLOBALS['user']->cfg->getValue('MY_COURSES_SELECTED_CYCLE');
        if ($config_sem != '' && $config_sem != '0') {
            $this->semester = Semester::find($config_sem);
        } else {
            $this->semester = Semester::findCurrent();
        }

        PageLayout::setHelpKeyword("Basis.Veranstaltungen");
        PageLayout::setTitle(_("Verwaltung von Veranstaltungen und Einrichtungen"));

    }

    /**
     * Show all courses with more options
     */
    public function index_action()
    {
        $this->sem_create_perm = in_array(Config::get()->SEM_CREATE_PERM, array('root', 'admin', 'dozent'))
            ? Config::get()->SEM_CREATE_PERM : 'dozent';


        // get courses only if institutes available
        if (!empty($this->insts)) {
            $this->actions                = self::getActions();
            $teachers                     = array();
            $config_my_course_type_filter = $GLOBALS['user']->cfg->getValue('MY_COURSES_TYPE_FILTER');


            // Get the view filter
            $config_view_filter = $GLOBALS['user']->cfg->getValue('MY_COURSES_ADMIN_VIEW_FILTER_ARGS');
            $this->view_filter  = isset($config_view_filter) ? unserialize($config_view_filter) : array();
            if (!$this->view_filter) {
                $this->view_filter = $this->getViewFilters();
                $GLOBALS['user']->cfg->store('MY_COURSES_ADMIN_VIEW_FILTER_ARGS', serialize($this->view_filter));
            }

            // filter by dozent
            if (Request::submitted('filterByTeacher')) {
                Request::set('teacher_filter', Request::option('teacher_filter'));
            }

            // Get the sort flag
            if (Request::option('sortFlag')) {
                $sortFlag = Request::get('sortFlag');
            }

            $sortby   = $GLOBALS['user']->cfg->getValue('MEINE_SEMINARE_SORT');
            $sortFlag = ($sortFlag == 'asc') ? 'DESC' : 'ASC';

            if (Request::option('sortby') && Request::get('sortby') != $sortby) {
                $sortby = Request::option('sortby');
                $GLOBALS['user']->cfg->store('MEINE_SEMINARE_SORT', $sortby);
            }

            $this->selected_action = $GLOBALS['user']->cfg->MY_COURSES_ACTION_AREA;
            if (is_null($this->selected_action)) {
                $this->selected_action = 1;
            }

            $this->sortby        = $sortby;
            $this->sortFlag      = $sortFlag;
            $this->courses       = $this->getCourses($GLOBALS['user']->id,
                array('sortby'      => $sortby,
                      'sortFlag'    => $sortFlag,
                      'view_filter' => $this->view_filter,
                      'typeFilter'  => $config_my_course_type_filter));
            $this->count_courses = count($this->courses);
            // get all available teacher for infobox-filter
            // filter by selected teacher
            if (!empty($this->courses)) {
                $teachers = $this->filterTeacher($this->courses);
            }
            $_SESSION['MY_COURSES_LIST'] = array_map(function ($c, $id) {
                return array('Name'       => $c['Name'],
                             'Seminar_id' => $id);
            }, array_values($this->courses), array_keys($this->courses));
        }
        $this->all_lock_rules = array_merge(array(array('name'    => '--' . _("keine Sperrebene") . '--',
                                                        'lock_id' => 'none')),
            LockRule::findAllByType('sem'));
        $this->aux_lock_rules = array_merge(array(array('name'    => '--' . _("keine Zusatzangaben") . '--',
                                                        'lock_id' => 'none')),
            AuxLockRules::getAllLockRules());
        $sidebar              = Sidebar::get();
        $sidebar->setImage("sidebar/seminar-sidebar.png");

        if ($this->sem_create_perm) {
            $actions = new ActionsWidget();
            $actions->addLink(_('Neue Veranstaltung anlegen'),
                URLHelper::getLink('admin_seminare_assi.php',
                    array('new_session' => 'TRUE')), 'icons/16/blue/add/seminar.png');
            $sidebar->addWidget($actions, 'links');
        }
        $this->setSearchWiget();
        $this->set_inst_selector();
        $this->set_semester_selector();
        $this->setTeacherWidget($teachers);
        $this->setCourseTypeWidget($config_my_course_type_filter);
        $this->setActionsWidget($this->selected_action);
        $this->setViewWidget($this->view_filter);

        if ($this->sem_create_perm) {
            $export = new ExportWidget();
            $export->addLink(_('Als Excel exportieren'),
                URLHelper::getLink('dispatch.php/admin/courses/export_csv'),
                'icons/16/blue/file-excel.png');
            $sidebar->addWidget($export);
        }
    }

    /**
     * Export action
     */
    public function export_csv_action()
    {
        $config_view_filter = $GLOBALS['user']->cfg->getValue('MY_COURSES_ADMIN_VIEW_FILTER_ARGS');
        $view_filter  = isset($config_view_filter) ? unserialize($config_view_filter) : array();
        if (!$view_filter) {
            $view_filter = $this->getViewFilters();
            $GLOBALS['user']->cfg->store('MY_COURSES_ADMIN_VIEW_FILTER_ARGS', serialize($view_filter));
        }

        if($pos = array_search('Inhalt', $view_filter)) {
            unset($view_filter[$pos]);
        }
        $sortby                       = $GLOBALS['user']->cfg->getValue('MEINE_SEMINARE_SORT');
        $config_my_course_type_filter = $GLOBALS['user']->cfg->getValue('MY_COURSES_TYPE_FILTER');

        $courses = $this->getCourses($GLOBALS['user']->id,
            array('sortby'     => $sortby,
                  'sortFlag'   => 'asc',
                  'typeFilter' => $config_my_course_type_filter,
                  'view_filter' => $view_filter)
        );

        $captions = array();

        if(empty($view_filter)) {
            return;
        }

        $captions = array_values($view_filter);


        foreach ($courses as $course_id => $course) {
            $sem      = new Seminar($course_id);

            if(in_array('Nr.', $captions)) {
                $data[$course_id][array_search('Nr.', $captions)] = $course['VeranstaltungsNummer'];
            }

            if(in_array('Name', $captions)) {
                $data[$course_id][array_search('Name', $captions)] = $course['Name'];
            }

            if(in_array('Veranstaltungstyp', $captions)) {
                $data[$course_id][array_search('Veranstaltungstyp', $captions)]
                    = $course['sem_class_name'] . ': ' . $GLOBALS['SEM_TYPE'][$course['status']]['name'];
            }

            if(in_array('Raum/Zeit', $captions)) {
                $_room    = $sem->getDatesExport(array(
                    'semester_id' => $this->semester->id,
                    'show_room'   => true
                ));
                $_room    = $_room ?: _('nicht angegeben');
                $data[$course_id][array_search('Raum/Zeit', $captions)] = $_room;
            }

            if(in_array('DozentIn', $captions)) {
                $dozenten = array();
                array_walk($course['dozenten'], function ($a) use (&$dozenten) {
                    $user = User::findByUsername($a['username']);
                    $dozenten[] = $user->getFullName();
                });
                $data[$course_id][array_search('DozentIn', $captions)] = !empty($dozenten) ? implode(', ', $dozenten) : '';
            }

            if(in_array('TeilnehmerInnen', $captions)) {
                $data[$course_id][array_search('TeilnehmerInnen', $captions)] = $course['teilnehmer'];
            }

            if(in_array('TeilnehmerInnen auf Warteliste', $captions)) {
                $data[$course_id][array_search('TeilnehmerInnen auf Warteliste', $captions)] = $course['waiting'];
            }

            if(in_array('Vorläufige Anmeldungen', $captions)) {
                $data[$course_id][array_search('Vorläufige Anmeldungen', $captions)] = $course['prelim'];
            }
        }

        $tmpname = md5(uniqid('Veranstaltungsexport'));
        if (array_to_csv($data, $GLOBALS['TMP_PATH'] . '/' . $tmpname, $captions)) {
            $this->redirect(GetDownloadLink($tmpname, 'Veranstaltungen_Export.csv', 4, 'force'));
            return;
        }
    }

    /**
     * Set the selected institute or semester
     */
    public function set_selection_action()
    {
        if (Request::option('institute')) {
            $GLOBALS['user']->cfg->store('MY_INSTITUTES_DEFAULT', Request::option('institute'));
            PageLayout::postMessage(MessageBox::success('Die gewünschte Einrichtung wurde ausgewählt!'));
        }

        if (Request::option('sem_select')) {
            $GLOBALS['user']->cfg->store('MY_COURSES_SELECTED_CYCLE', Request::option('sem_select'));
            PageLayout::postMessage(MessageBox::success(sprintf(_('Das %s wurde ausgewählt'), Semester::find(Request::option('sem_select'))->name)));
        }

        $this->redirect('admin/courses/index');
    }


    /**
     * Set the lockrules of courses
     */
    public function set_lockrule_action()
    {
        $result  = false;
        $courses = Request::getArray('lock_sem');

        if (!empty($courses)) {
            foreach ($courses as $course_id => $value) {
                // force to pre selection
                if (Request::get('lock_sem_all') && Request::submitted('all')) {
                    $value = Request::get('lock_sem_all');
                }

                $course = Course::find($course_id);
                if ($value == 'none') {
                    $value = null;
                }

                if ($course->lock_rule == $value) {
                    continue;
                }

                $course->setValue('lock_rule', $value);
                if (!$course->store()) {
                    PageLayout::postMessage(MessageBox::error(sprintf(_('Bei den folgenden Veranstaltungen ist ein
                    Fehler aufgetreten')), $course->name));
                    $this->redirect('admin/courses/index');

                    return;
                }
                $result = true;
            }

            if ($result) {
                PageLayout::postMessage(MessageBox::success(sprintf(_('Die gewünschten Änderungen wurden erfolgreich durchgeführt!'))));
            }
        }
        $this->redirect('admin/courses/index');
    }

    /**
     * Set the visibility of a course
     */
    public function set_visibility_action()
    {
        $result      = false;
        $visibilites = Request::getArray('visibility');
        $all_courses = Request::getArray('all_sem');

        if (!empty($all_courses)) {
            foreach ($all_courses as $course_id) {
                $course = Course::find($course_id);

                $visibility = isset($visibilites[$course_id]) ? 1 : 0;

                if ((int)$course->visible == $visibility) {
                    continue;
                }

                $course->setValue('visible', $visibility);
                if (!$course->store()) {
                    PageLayout::postMessage(MessageBox::error(sprintf(_('Bei den folgenden Veranstaltungen ist ein
                    Fehler aufgetreten')), $course->name));
                    $this->redirect('admin/courses/index');

                    return;
                }
                $result = true;
            }

            if ($result) {
                PageLayout::postMessage(MessageBox::success(sprintf(_('Die Sichtbarkeit wurde bei den gewünschten Veranstatungen erfolgreich geändert!'))));
            }
        }
        $this->redirect('admin/courses/index');
    }


    /**
     * Set the additional course informations
     */
    public function set_aux_lockrule_action()
    {
        $result  = false;
        $courses = Request::getArray('lock_sem');

        if (!empty($courses)) {
            foreach ($courses as $course_id => $value) {
                // force to pre selection
                if (Request::get('lock_sem_all') && Request::submitted('all')) {
                    $value = Request::get('lock_sem_all');
                }

                $course = Course::find($course_id);

                if ($value == 'none') {
                    $value = null;
                }

                if ($course->aux_lock_rule == $value) {
                    continue;
                }

                $course->setValue('aux_lock_rule', $value);
                if (!$course->store()) {
                    PageLayout::postMessage(MessageBox::error(sprintf(_('Bei den folgenden Veranstaltungen ist ein
                    Fehler aufgetreten')), $course->name));
                    $this->redirect('admin/courses/index');

                    return;
                }
                $result = true;
            }

            if ($result) {
                PageLayout::postMessage(MessageBox::success(sprintf(_('Die gewünschten Änderungen wurden erfolgreich durchgeführt!'))));
            }
        }
        $this->redirect('admin/courses/index');
    }


    /**
     * Set the selected view filter and store the selection in configuration
     */
    public function set_view_filter_action($filter = null, $state = true)
    {
        // store view filter in configuration
        if (!is_null($filter)) {
            $db_filter = unserialize($GLOBALS['user']->cfg->MY_COURSES_ADMIN_VIEW_FILTER_ARGS);
            $or_filter = $filters = $this->getViewFilters();
            $selected  = $or_filter[$filter];

            if ($state) {
                $db_filter = array_filter($db_filter, function ($a) use ($selected) {
                    return $a != $selected;
                });

            } else {
                array_push($db_filter, $selected);
            }

            if (empty($db_filter)) {
                $GLOBALS['user']->cfg->store('MY_COURSES_ADMIN_VIEW_FILTER_ARGS', serialize(array()));
            } else {
                $GLOBALS['user']->cfg->store('MY_COURSES_ADMIN_VIEW_FILTER_ARGS', serialize($db_filter));
            }
        }

        $this->redirect('admin/courses/index');
    }

    /**
     * Set the selected action type and store the selection in configuration
     */
    public function set_action_type_action()
    {
        // select the action area
        if (Request::option('action_area')) {
            $GLOBALS['user']->cfg->store('MY_COURSES_ACTION_AREA', Request::option('action_area'));
            PageLayout::postMessage(MessageBox::success(_('Der Aktionsbereich wurde erfolgreich übernommen!')));
        }

        $this->redirect('admin/courses/index');
    }

    /**
     * Set the selected course type filter and store the selection in configuration
     */
    public function set_course_type_action()
    {
        if (Request::option('course_type')) {
            $GLOBALS['user']->cfg->store('MY_COURSES_TYPE_FILTER', Request::option('course_type'));
            PageLayout::postMessage(MessageBox::success(_('Der gewünschte Veranstaltungstyp wurde übernommen!')));
        }
        $this->redirect('admin/courses/index');
    }



    /**
     * Return a specifically action oder all available actions
     * @param null $selected
     * @return array
     */
    private static function getActions($selected = null)
    {
        // array for the avaiable modules
        $actions = array(
            1  => array('name'        => 'Grunddaten',
                        'title' => 'Grunddaten',
                        'url'         => 'dispatch.php/course/basicdata/view?cid=%s',
                        'attributes'  => array(
                            'data-dialog' => 'size=50%'
                        )),
            2  => array('name'        => 'Studienbereiche',
                        'title' => 'Studienbereiche',
                        'url'         => 'dispatch.php/course/study_areas/show?cid=%s',
                        'attributes'  => array(
                            'data-dialog' => 'size=50%'
                        )),
            3  => array('name'        => 'Zeiten / Räume',
                        'title' => 'Zeiten / Räume',
                        'url'         => 'raumzeit.php?cid=%s'),
            8  => array('name'        => 'Sperrebene',
                        'title' => 'Sperrebenen',
                        'url'         => 'dispatch.php/admin/courses/set_lockrule',
                        'multimode'   => true),
            9  => array('name'        => 'Sichtbarkeit',
                        'title' => 'Sichtbarkeit',
                        'url'         => 'dispatch.php/admin/courses/set_visibility',
                        'multimode'   => true),
            10 => array('name'        => 'Zusatzangaben',
                        'title' => 'Zusatzangaben',
                        'url'         => 'dispatch.php/admin/courses/set_aux_lockrule',
                        'multimode'   => true),
            11 => array('name'        => 'Veranstaltung kopieren',
                        'title' => 'Kopieren',
                        'url'         => 'admin_seminare_assi.php?cmd=do_copy&start_level=1&class=1&cp_id=%s'),
            14 => array('name'        => 'Zugangsberechtigungen',
                        'title' => 'Zugangsberechtigungen',
                        'url'         => 'dispatch.php/course/admission?cid=%s',
                        'attributes'  => array(
                            'data-dialog' => 'size=50%'
                        )),
            16 => array('name'        => 'Archivieren',
                        'title' => 'Archivieren',
                        'url'         => 'archiv_assi.php',
                        'multimode'   => true)
        );
        if (get_config('RESOURCES_ALLOW_ROOM_REQUESTS')) {
            $actions[4] = array('name'        => 'Raumanfragen',
                                'title' => 'Raumanfragen',
                                'url'         => 'dispatch.php/course/room_requests/index?cid=%s');
        }
        foreach (PluginManager::getInstance()->getPlugins("AdminCourseAction") as $plugin) {
            $actions[get_class($plugin)] = array(
                'name'        => $plugin->getPluginName(),
                'title' => $plugin->getPluginName(),
                'url'         => $plugin->getAdminActionURL(),
                'multimode'   => $plugin->useMultimode()
            );
        }

        if (is_null($selected)) {
            return $actions;
        }

        return $actions[$selected];
    }


    /**
     * Set and return all needed view filters
     * @return array
     */
    private function getViewFilters()
    {
        return array(_('Nr.'),
                     _('Name'),
                     _('Veranstaltungstyp'),
                     _('Raum/Zeit'),
                     _('DozentIn'),
                     _('TeilnehmerInnen'),
                     _('TeilnehmerInnen auf Warteliste'),
                     _('Vorläufige Anmeldungen'),
                     _('Inhalt'));
    }

    /**
     * Search for teachers for the given course selection
     * @param $seminars
     * @return array
     */
    private function filterTeacher(&$seminars)
    {

        $teachers = array();
        if (!empty($seminars)) {
            foreach ($seminars as $index => $course) {
                if (Request::option('teacher_filter') && strcmp('all', Request::get('teacher_filter')) !== 0) {
                    if (!isset($course['dozenten'][Request::option('teacher_filter')])) {
                        unset($seminars[$index]);
                    }
                }
                $teachers = array_merge($teachers, $course['dozenten']);
            }
        }
        $teachers = SimpleCollection::createFromArray($teachers)
            ->orderBy('fullname asc')
            ->getArrayCopy();

        return $teachers;
    }

    /**
     * TODO: SORM
     * Get Courses (maybe migration to SORM)
     * @param       $user_id
     * @param       $inst_id
     * @param array $params
     * @return mixed
     */
    private function getCourses($user_id, $params = array())
    {
        // Init
        $sortby        = $params['sortby'];
        $sortFlag      = $params['sortFlag'];
        $typeFilter    = $params['typeFilter'];
        $pluginsFilter = in_array('Inhalt', $params['view_filter']);


        if (isset($sortby) && in_array($sortby, words('VeranstaltungsNummer Name status teilnehmer waiting prelim'))) {
            if ($sortby == "status") {
                $sortby = sprintf('sc.name %s, st.name %s, VeranstaltungsNummer %s, Name %s', $sortFlag, $sortFlag, $sortFlag, $sortFlag);
            } else {
                $sortby = sprintf('%s %s', $sortby, $sortFlag);
            }
        } else {
            $sortby = sprintf('VeranstaltungsNummer %s, Name %s', $sortFlag, $sortFlag);
        }

        $where = '';

        if (!is_null($typeFilter) && strcmp($typeFilter, "all") !== 0) {
            $where .= ' AND seminare.status = :typeFilter ';
        }

        if (is_object($this->semester)) {
            $sem_condition = "AND seminare.start_time <=" . $this->semester->beginn . " AND (" . $this->semester->beginn . " <= (seminare.start_time + seminare.duration_time)
                OR seminare.duration_time = -1) ";
        } else {
            $sem_condition = '';
        }

        // Prepare and execute seminar statement
        $query = "SELECT DISTINCT seminare.Seminar_id, Institute.Name AS Institut, seminare.VeranstaltungsNummer,
                         seminare.Name, seminare.status, seminare.chdate,
                         seminare.start_time, seminare.admission_binding, seminare.visible,
                         seminare.modules, COUNT(seminar_user.user_id) AS teilnehmer,
                         seminare.lock_rule, seminare.aux_lock_rule,
                         (SELECT COUNT(seminar_id)
                          FROM admission_seminar_user
                          WHERE seminar_id = seminare.Seminar_id AND status = 'accepted') AS prelim,
                          (SELECT COUNT(seminar_id)
                          FROM admission_seminar_user
                          WHERE seminar_id = seminare.Seminar_id AND status = 'awaiting') AS waiting
                  FROM Institute
                  INNER JOIN seminare ON (seminare.Institut_id = Institute.Institut_id {$sem_condition})
                  LEFT JOIN seminar_user on (seminare.seminar_id=seminar_user.seminar_id AND seminar_user.status != 'dozent' and seminar_user.status != 'tutor')
                  LEFT JOIN sem_types as st ON st.id = seminare.status
                  WHERE Institute.Institut_id = :institute_id
                  {$where}
                  GROUP BY seminare.Seminar_id
                  ORDER BY {$sortby}";

        $statement = DBManager::get()->prepare($query);
        //$statement->bindValue(':unlimited', _('unbegrenzt'));
        $statement->bindValue('institute_id', $this->selected_inst_id);
        if (!is_null($typeFilter) && strcmp($typeFilter, "all") !== 0) {
            $statement->bindValue(':typeFilter', $typeFilter);
        }
        $statement->execute();
        $seminars = array_map('reset', $statement->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC));
        $statement->closeCursor();
        if (!empty($seminars)) {
            foreach ($seminars as $seminar_id => $seminar) {
                $dozenten                          = $this->getTeacher($seminar_id);
                $seminars[$seminar_id]['dozenten'] = $dozenten;

                if ($pluginsFilter) {
                    $seminars[$seminar_id]['navigations'] = MyRealmModel::getPluginNavigationForSeminar($seminar_id, object_get_visit($seminar_id, 'sem', ''));
                }
            }
        }

        if (Request::get('search')) {
            $search_result = array_filter($seminars, function ($a) {
                if (stripos($a['VeranstaltungsNummer'], Request::get('search')) !== false) {
                    return $a;
                }

                if (stripos($a['Name'], Request::get('search')) !== false) {
                    return $a;
                }

                foreach ($a['dozenten'] as $teacher) {
                    if (stripos($teacher['fullname'], Request::get('search')) !== false) {
                        return $a;
                    }
                }
            });

            if (!empty($search_result)) {
                return $search_result;
            }

            return array();
        }


        return $seminars;
    }

    /**
     * Return the amount of courses in a institute and given type
     * @param $id
     * @return mixed
     */
    private function getCourseAmountForStatus(&$id)
    {
        $sql = "
            SELECT COUNT(seminar_id) FROM seminare
            WHERE Institut_id = :institut_id
                AND status = :status
                AND seminare.start_time <= :semester_beginn
                AND (:semester_beginn <= (seminare.start_time + seminare.duration_time)
                    OR seminare.duration_time = -1)";
        $statement = DBManager::get()->prepare($sql);
        $statement->execute(array(
            'institut_id' => $this->selected_inst_id,
            'status' => $id,
            'semester_beginn' => $this->semester->beginn
        ));
        $count = $statement->fetch(PDO::FETCH_COLUMN);

        return $count;
    }

    /**
     * TODO: SORM
     * Returns the teacher for a given cours
     * @param $course_id
     * @return array
     */
    private function getTeacher($course_id)
    {
        $query = "SELECT DISTINCT user_id, username, Nachname, CONCAT(Nachname, ', ', Vorname) as fullname
                  FROM seminar_user
                  LEFT JOIN auth_user_md5 USING (user_id)
                  WHERE Seminar_id = ? AND status='dozent'
                  ORDER BY position, Nachname ASC";

        $teacher_statement = DBManager::get()->prepare($query);
        $teacher_statement->execute(array($course_id));

        $dozenten = array_map('reset', $teacher_statement->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC));
        $teacher_statement->closeCursor();

        return $dozenten;
    }


    /**
     * Adds view filter to the sidebar
     * @param array $configs
     */
    private function setViewWidget($configs = array())
    {
        $configs         = $configs ?: array();
        $sidebar         = Sidebar::Get();
        $filters         = $this->getViewFilters();
        $checkbox_widget = new OptionsWidget();
        $checkbox_widget->setTitle(_('Darstellungs-Filter'));
        $size = count($filters);

        for ($i = 0; $i < $size; $i++) {
            $state = in_array($filters[$i], $configs);
            $checkbox_widget->addCheckbox($filters[$i], $state, $this->url_for('admin/courses/set_view_filter/' . $i . '/' . $state));
        }
        $sidebar->addWidget($checkbox_widget);
    }

    /**
     * Adds the institutes selector to the sidebar
     */
    private function set_inst_selector()
    {
        $sidebar = Sidebar::Get();
        $list    = new SelectWidget(_('Einrichtung'), $this->url_for('admin/courses/set_selection'), 'institute');
        foreach ($this->insts as $institut) {
            $list->addElement(
                new SelectElement(
                    $institut['Institut_id'],
                    (!$institut['is_fak'] ? "  ": "").$institut['Name'],
                    $this->selected_inst_id == $institut['Institut_id']
                ),
                'select-' . $institut['Name']
            );
        }
        $sidebar->addWidget($list);
    }

    /**
     * Adds the semester selector to the sidebar
     */
    private function set_semester_selector()
    {
        $semesters = array_reverse(Semester::getAll());
        $sidebar   = Sidebar::Get();
        $list      = new SelectWidget(_('Semester'), $this->url_for('admin/courses/set_selection'), 'sem_select');
        foreach ($semesters as $semester) {
            $list->addElement(new SelectElement($semester->id, $semester->name, $semester->id == $this->semester->id), 'sem_select-' . $semester->id);
        }

        $sidebar->addWidget($list);
    }


    /**
     * Adds HTML-Selector to the sidebar
     * @param null $selected_action
     * @return string
     */
    private function setActionsWidget($selected_action = null)
    {
        $actions = self::getActions();
        $sidebar = Sidebar::Get();
        $list    = new SelectWidget(_('Aktionsbereich-Auswahl'), $this->url_for('admin/courses/set_action_type'), 'action_area');

        foreach ($actions as $index => $action) {
            $list->addElement(new SelectElement($index, $action['name'], $selected_action == $index), 'action-aria-' . $index);
        }
        $sidebar->addWidget($list, 'actions');
    }


    /**
     * Returns a course type widthet depending on all available courses and theirs types
     * @param string $selected
     * @param array  $params
     * @return ActionsWidget
     */
    private function setCourseTypeWidget($selected = 'all')
    {
        $sidebar        = Sidebar::get();
        $this->url      = $this->url_for('admin/courses/set_course_type');
        $result         = array();
        $this->types    = array();
        $semCats        = SeminarCategories::GetAll();
        $this->selected = $selected;
        if (!empty($semCats)) {
            foreach ($semCats as $cat) {
                $types = $cat->getTypes();
                if (!empty($types)) {
                    if (count($types) > 1) {
                        asort($types, SORT_LOCALE_STRING);
                    }
                    $result[$cat->name] = $types;
                }
            }
        }

        foreach ($result as $cat => $types) {
            foreach ($types as $id => $name) {
                $amount = $this->getCourseAmountForStatus($id);
                if ($amount > 0) {
                    $this->types[$cat][$id]['name']   = $name;
                    $this->types[$cat][$id]['amount'] = $amount;
                }
            }
        }

        $this->render_template('admin/courses/filters/course_type_filter.php', null);
        $html = $this->response->body;
        $this->erase_response();
        $widget = new SidebarWidget();
        $widget->setTitle(_('Veranstaltungstyp-Filter'));
        $widget->addElement(new WidgetElement($html));
        $sidebar->addWidget($widget, 'courses');
    }

    /**
     * Returns a widget to selected a specific teacher
     * @param array $teachers
     * @return ActionsWidget|null
     */
    private function setTeacherWidget($teachers = array())
    {
        if (empty($teachers)) {
            return null;
        }
        $sidebar = Sidebar::Get();
        $list    = new SelectWidget(_('Dozenten-Filter'), $this->url_for('admin/courses/index'), 'teacher_filter');
        $list->addElement(new SelectElement('all', _('alle'), Request::get('teacher_filter') == 'all'), 'teacher_filter-all');

        foreach ($teachers as $user_id => $teacher) {
            $list->addElement(new SelectElement($user_id, $teacher['fullname'], Request::get('teacher_filter') == $user_id), 'teacher_filter-' . $user_id);
        }

        $sidebar->addWidget($list, 'teachers');
    }


    private function setSearchWiget()
    {
        $sidebar = Sidebar::Get();
        $search  = new SearchWidget(URLHelper::getLink('dispatch.php/admin/courses'));
        $search->addNeedle(_('Freie Suche'), 'search', true);
        $sidebar->addWidget($search);
    }
}