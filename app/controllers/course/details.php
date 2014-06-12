<?php
/*
 * details.php - realises a redirector for administrative pages
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      David Siegfried <david.siegfried@uni-oldenburg.de>
 * @copyright   2014
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       3.1
 */

require_once 'app/controllers/authenticated_controller.php';
require_once 'lib/dates.inc.php'; //Funktionen zum Anzeigen der Terminstruktur
require_once 'app/models/my_realm.php';

class Course_DetailsController extends AuthenticatedController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        $this->sidebarlink = false;
        if (empty($args[0])) {
            if (Request::option('sem_id')) {
                $this->sidebarlink = true;
                $args[0]           = Request::option('sem_id');
            } elseif (Request::option('cid')) {
                $this->sidebarlink = true;
                $args[0]           = Request::option('cid');
            } elseif (isset($GLOBALS['SessionSeminar'])) {
                checkObject();
                $args[0] = $GLOBALS['SessionSeminar'];
            } else {
                throw new AccessDeniedException(_('Sie haben keine Veranstaltung ausgewählt!'));
            }
        }

        $this->course                = Course::find($args[0]);
        $this->user                  = User::find($GLOBALS['user']->id);
        $this->is_studygroup         = $GLOBALS['SEM_CLASS'][$GLOBALS['SEM_TYPE'][$this->course->status]["class"]]["studygroup_mode"];
        $this->send_from_search      = Request::get('send_from_search') !== null;
        $this->send_from_search_page = Request::get('send_from_search_page');

        if ($GLOBALS['SessionSeminar'] != $args[0] && !(int)$this->course->visible && !$GLOBALS['perm']->have_perm(Config::get()->SEM_VISIBILITY_PERM)) {
            throw new AccessDeniedException(_('Diese Veranstaltung ist versteckt. Hier gibt es nichts zu sehen.'));
        }

        if (!preg_match('/^(' . preg_quote($GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP'], '/') . ')?([a-zA-Z0-9_-]+\.php)([a-zA-Z0-9_?&=-]*)$/', $this->send_from_search_page)) {
            $this->send_from_search_page = '';
        }

        if ($this->is_stuygroup) {
            if ($GLOBALS['perm']->have_studip_perm('autor', $args[0])) { // participants may see seminar_main
                $link = URLHelper::getUrl('seminar_main.php', array('auswahl' => $args[0]));
            } else {
                $link = URLHelper::getUrl('dispatch.php/course/studygroup/details/' . $args[0], array('send_from_search_page' => $this->send_from_search_page));
            }
            $this->redirect($link);
            return;
        }

        $this->setEnrolmentInfo($args[0]);
        $this->setStatistics($args[0]);
        $this->modules          = new Modules();
        $this->deputies_enabled = Config::get()->DEPUTIES_ENABLE;
    }

    public function index_action($course_id)
    {
        $this->cycle_dates = SeminarCycleDate::findBySeminar($this->course->id);

        $this->prelim_discussion = vorbesprechung($this->course->id);
        $this->teachers          = $this->course->members->findBy('status', 'dozent')->toArray('user_id username vorname nachname email');
        $this->tutors            = $this->course->members->findBy('status', 'tutor')->toArray('user_id username vorname nachname email');
        $this->study_path        = MyRealmModel::getSemTree($course_id);
        $this->institut          = Institute::find($this->course->institut_id);
        $this->title             = $this->course->getFullname('number-type-name');
        $this->course_domains    = UserDomain::getUserDomainsForSeminar($course_id);
        $this->user_domains      = UserDomain::getUserDomainsForUser($GLOBALS['user']->id);
        $this->quarter_year      = 60 * 60 * 24 * 90;
        $this->courseset         = CourseSet::getSetForCourse($course_id);

        if (count($this->user_domains) > 0) {
            $this->same_domains = count(array_intersect($this->course_domains, $this->user_domains)) > 0;
        }
        if ((int)$this->admission_prelim == 1) {
            $this->admission_participation = MyRealmModel::checkAdmissionParticipation($course_id);
        }

        if (Request::isXhr()) {
            $this->set_layout(null);
            $this->response->add_header('Content-Type', 'text/html;charset=Windows-1252');
            header('X-Title: ' . $this->title);
        } else {
            PageLayout::setHelpKeyword("Basis.InVeranstaltungDetails");
            PageLayout::setTitle($this->title . " - " . _("Details"));
            PageLayout::addSqueezePackage('admission');
            PageLayout::addSqueezePackage('enrolment');
            if ($GLOBALS['SessionSeminar'] == $course_id) {
                Navigation::activateItem('/course/main/details');
                SkipLinks::addIndex(Navigation::getItem('/course/main/details')->getTitle(), 'main_content', 100);
            }
        }

        $this->setSidebar();
    }

    private function setSidebar()
    {
        $sidebar = Sidebar::Get();
        $sidebar->setTitle(_('Details'));
        $links = new LinksWidget();
        $links->setTitle(_('Aktionen'));

        if ($this->enrolment_allowed && $this->sidebarlink) {
            if (in_array($this->cause, words('member root courseadmin'))) {
                $abo_msg = _("direkt zur Veranstaltung");
            } elseif (enrolment_allowed) {
                $abo_msg = _("Zugang zur Veranstaltung");
            }
            $links->addLink($abo_msg, URLHelper::getScriptLink("dispatch.php/course/enrolment/apply/" . $this->course->id), 'icons/16/black/door-enter.png');
        }

        if (Config::get()->SCHEDULE_ENABLE
            && !$GLOBALS['perm']->have_studip_perm("user", $this->course->id)
            && !$GLOBALS['perm']->have_perm('admin')
            && Seminar::getInstance($this->course->id)->getMetaDateCount()
        ) {
            $query     = "SELECT COUNT(*) FROM schedule_seminare WHERE seminar_id = ? AND user_id = ?";
            $statement = DBManager::Get()->prepare($query);
            $statement->execute(array($this->course->id,
                $GLOBALS['user']->id));
            $sem_user_schedule = $statement->fetchColumn();
            if (!$sem_user_schedule) {
                $links->addLink(_("Nur im Stundenplan vormerken"), URLHelper::getLink("dispatch.php/calendar/schedule/addvirtual/" . $this->course->id), 'icons/16/black/info.png');
            }
        }

        if ($this->send_from_search) {
            $links->addLink(_("Zurück zur letzten Auswahl"), URLHelper::getLink($this->send_from_search_page), 'icons/16/black/link-intern.png');
        }

        if ($links->hasElements()) {
            $sidebar->addWidget($links);
        }
    }

    private function setEnrolmentInfo($course_id)
    {
        $user                    = User::find($GLOBALS['user']->id);
        $this->enrolment_allowed = false;

        if ((int)$this->course->lesezugriff == 0 && Config::get()->ENABLE_FREE_ACCESS) {
            $this->enrolment_allowed = true;
            $this->cause             = 'free_access';
            if (!Request::isXhr()) {
                PageLayout::postMessage(MessageBox::success(_("Für die Veranstaltung ist keine Anmeldung erforderlich.")));
            }
            return;
        }

        if ($GLOBALS['perm']->have_perm('root', $user->id)) {
            $this->enrolment_allowed = true;
            $this->cause             = 'root';
            PageLayout::postMessage(MessageBox::info(_("Sie sind AdministratorIn und k&ouml;nnen deshalb die Veranstaltung nicht abonnieren.")));
            return;
        }

        if ($GLOBALS['perm']->have_perm('admin', $user->id)) {
            $this->enrolment_allowed = true;
            $this->cause             = 'admin';
            if (!Request::isXhr()) {
                PageLayout::postMessage(MessageBox::info(_("Als Administrator_in können Sie sich nicht für eine Veranstaltung anmelden.")));
            }
            return;
        }

        if ($GLOBALS['perm']->have_studip_perm('admin', $this->course->id, $user->id)) {
            $this->enrolment_allowed = true;
            $this->cause             = 'courseadmin';
            if (!Request::isXhr()) {
                PageLayout::postMessage(MessageBox::success(_("Sie sind Administrator_in der Veranstaltung.")));
            }
            return;
        }

        //Ist bereits Teilnehmer
        if ($GLOBALS['perm']->have_studip_perm('user', $this->course->id, $user->id)) {
            $this->enrolment_allowed = true;
            $this->cause             = 'member';
            $perms                   = $GLOBALS['perm']->get_studip_perm($this->course->id);
            $status                  = (!$perms ? 'TeilnehmerIn' : get_title_for_status($perms, 1));

            if (!Request::isXhr()) {
                PageLayout::postMessage(MessageBox::success(sprintf(_("Sie sind als %s der Veranstaltung eingetragen"), $status)));
            }
            return;
        }

        $admission_status = $user->admission_applications->findBy('seminar_id', $this->course->id)->val('status');
        if ($admission_status == 'accepted') {
            $this->enrolment_allowed = false;
            if (!Request::isXhr()) {
                PageLayout::postMessage(MessageBox::info(_("Sie wurden für diese Veranstaltung vorläufig akzeptiert.")));
            }
            return;
        }

        if ($admission_status == 'awaiting') {
            $this->enrolment_allowed = false;
            if (!Request::isXhr()) {
                PageLayout::postMessage(MessageBox::info(_("Sie stehen auf der Warteliste für diese Veranstaltung.")));
            }
            return;
        }

        //falsche Nutzerdomäne
        $same_domain  = true;
        $user_domains = UserDomain::getUserDomainsForUser($user->id);
        if (count($user_domains) > 0) {
            $seminar_domains = UserDomain::getUserDomainsForSeminar($this->course->id);
            $same_domain     = count(array_intersect($seminar_domains, $user_domains)) > 0;
        }


        if (!$same_domain && !$this->is_studygroup) {
            $this->enrolment_allowed = false;
            $errors[]                = _("Sie sind nicht in einer zugelassenenen Nutzerdomäne, Sie können sich nicht eintragen!");
        }

        //Teilnehmerverwaltung mit Sperregel belegt
        if (LockRules::Check($this->course->id, 'participants')) {
            $lockdata                = LockRules::getObjectRule($this->course->id);
            $this->enrolment_allowed = false;
            if (!empty($lockdata['description'])) {
                $errors[] = formatLinks($lockdata['description']);
            } else {
                $errors[] = _('Die Teilnehmerverwaltung ist mit einer Sperregel belegt!');
            }

        }
        //Veranstaltung unsichtbar für aktuellen Nutzer
        if (!$this->course->visible && !$this->is_studygroup && !$GLOBALS['perm']->have_perm(Config::get()->SEM_VISIBILITY_PERM, $user->id)) {
            $this->enrolment_allowed = false;
            $errors[]                = _("Die Veranstaltung ist gesperrt!");
        }

        if ($courseset = CourseSet::getSetForCourse($this->course->id)) {
            $this->enrolment_allowed = !empty($errors) ? false : true;
            $this->cause             = 'courseset';
            $infos[]                 = _("Die Anmeldung zu dieser Veranstaltung folgt speziellen Regeln. Lesen Sie den Hinweistext.");
        }


        if (!Request::isXhr()) {
            if (empty($errors) && !empty($infos)) {
                PageLayout::postMessage(MessageBox::info(_("Sie sind als TeilnehmerIn der Veranstaltung eingetragen."), $infos));
                return;
            }

            if (!empty($errors)) {
                PageLayout::postMessage(MessageBox::error(_('Sie können sich nicht eintragen'), $errors));
                return;
            }

            if (empty($errors) && empty($infos)) {
                $this->enrolment_allowed = true;
                PageLayout::postMessage(MessageBox::info(_("Sie sind nicht als TeilnehmerIn der Veranstaltung eingetragen.")));
                return;
            }
        }

    }


    public function setStatistics()
    {
        //Statistikfunktionen
        $query     = "SELECT COUNT(*) AS count, SUM(status = 'dozent') AS anz_dozent,
                                 SUM(status = 'tutor') AS anz_tutor, SUM(status = 'autor') AS anz_autor,
                                 SUM(status = 'user') AS anz_user
                          FROM seminar_user
                          WHERE Seminar_id = ?
                          GROUP BY Seminar_id";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->course->id));
        $this->statistics = $statement->fetch(PDO::FETCH_ASSOC);


        $query     = "SELECT COUNT(*) FROM admission_seminar_user WHERE seminar_id = ? AND status = 'accepted'";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->course->id));
        $this->statistics['admission_count'] = $statement->fetchColumn();

        if ($this->statistics['admission_count']) {
            $this->statistics['count'] += $this->statistics['admission_count'];
        }

        $count   = 0;
        $plugins = PluginEngine::getPlugins('ForumModule');
        foreach ($plugins as $plugin) {
            $count += $plugin->getNumberOfPostingsForSeminar($this->course->id);
        }

        $this->statistics['forumPosts'] = $count;

        $query     = "SELECT COUNT(*) FROM dokumente WHERE Seminar_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->course->id));
        $this->statistics['documents'] = $statement->fetchColumn();
    }
}