<?php
/**
 * admission.php - administration of admission restrictions
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack <noack@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     admin
 */

require_once 'app/controllers/authenticated_controller.php';
require_once 'lib/classes/admission/CourseSet.class.php';

class Course_AdmissionController extends AuthenticatedController
{
    /**
     * common tasks for all actions
     */
    function before_filter (&$action, &$args)
    {
        global $perm;

        parent::before_filter($action, $args);

        $course_id = $args[0];

        $this->course_id = Request::option('cid', $course_id);

        if ($perm->have_perm('admin')) {
            //Navigation im Admin-Bereich:
            Navigation::activateItem('/admin/course/admission');
        } else {
            //Navigation in der Veranstaltung:
            Navigation::activateItem('/course/admin/admission');
        }

        if (!$this->course_id) {
            PageLayout::setTitle(_("Verwaltung von Zugangsberechtigungen"));
            $GLOBALS['view_mode'] = "sem";

            require_once 'lib/admin_search.inc.php';

            include 'lib/include/admin_search_form.inc.php';  // will not return
            die(); //must not return
        }

        if (!get_object_type($this->course_id, array('sem')) ||
            SeminarCategories::GetBySeminarId($this->course_id)->studygroup_mode ||
            !$perm->have_studip_perm("tutor", $this->course_id)) {
            throw new Trails_Exception(400);
        }

        $this->course = Course::find($this->course_id);
        $this->user_id = $GLOBALS['user']->id;
        PageLayout::setHelpKeyword("Basis.VeranstaltungenVerwaltenZugangsberechtigungen");
        PageLayout::setTitle($this->course->getFullname()." - " ._("Verwaltung von Zugangsberechtigungen"));
        if (Request::isXhr()) {
            $this->set_layout(null);
            $this->response->add_header('X-No-Buttons', 1);
            $request = Request::getInstance();
            foreach ($request as $key => $value) {
                $request[$key] = studip_utf8decode($value);
            }
        } else {
            $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));
        }
        $this->set_content_type('text/html;charset=windows-1252');
        $lockrules = words('admission_turnout admission_type admission_endtime admission_binding passwort read_level write_level admission_prelim admission_prelim_txt admission_starttime admission_endtime_sem admission_disable_waitlist user_domain admission_binding admission_studiengang');
        foreach ($lockrules as $rule) {
            $this->is_locked[$rule] = LockRules::Check($this->course_id, $rule) ? 'disabled readonly' : '';
        }
        if (!SeminarCategories::GetByTypeId($this->course->status)->write_access_nobody) {
            $this->is_locked['write_level'] = 'disabled readonly';
        }
        PageLayout::addSqueezePackage('admission');
    }

    /**
     * Shows the current restrictions for course participation.
     */
    function index_action()
    {
        $this->sidebar = Sidebar::get();
        $this->sidebar->setImage(Assets::image_path("sidebar/seminar-sidebar.png"));
        
        $this->all_domains = UserDomain::getUserDomains();
        $this->seminar_domains = array_map(function($d) {return $d->getId();}, UserDomain::getUserDomainsForSeminar($this->course_id));
        $this->current_courseset = CourseSet::getSetForCourse($this->course_id);
        if (!$this->current_courseset) {
            $this->available_coursesets = array();
            foreach (CourseSet::getCoursesetsByInstituteId($this->course->institut_id) as $cs) {
                $cs = new CourseSet($cs['set_id']);
                if ($cs->isUserAllowedToAssignCourse($this->user_id, $this->course_id)) {
                    $this->available_coursesets[] = $cs;
                }
            }
        }
    }

    /**
     * Change preliminary admission settings.
     */
    function change_admission_prelim_action()
    {
        CSRFProtection::verifyUnsafeRequest();
        $this->response->add_header('X-Title', _('Anmeldemodus ändern'));
        if (Request::submitted('change_admission_prelim')) {
            $request = Request::extract('admission_prelim int, admission_binding submitted, admission_prelim_txt');
            $request = array_diff_key($request, array_filter($this->is_locked));
            $request['change_admission_prelim'] = 1;
            $this->course->setData($request);
            if ($this->course->isFieldDirty('admission_prelim')) {
                if ($this->course->admission_prelim == 1 && $this->course->getNumParticipants()) {
                    $question = _("Sie beabsichtigen den Anmeldemodus auf vorläufiger Eintrag zu ändern. Sollen die bereits in der Veranstaltung eingetragenen Teilnehmer in vorläufige Teilnehmer umgewandelt werden?");
                }
                if ($this->course->admission_prelim == 0 && $this->course->getNumPrelimParticipants()) {
                    $question = _("Sie beabsichtigen den Anmeldemodus auf direkten Eintrag zu ändern. Sollen die vorläufigen Teilnehmer in die Veranstaltung übernommen werden (ansonsten werden die vorläufigen Teilnehmer aus der Veranstaltung entfernt) ?");
                }
            }
            if (Request::submittedSome('change_admission_prelim_no', 'change_admission_prelim_yes') || !$question) {
                if ($this->course->admission_prelim == 1 && $this->course->getNumParticipants() && Request::submitted('change_admission_prelim_yes')) {
                    $num_moved = 0;
                    $seminar = new Seminar($this->course_id);
                    foreach ($this->course->members->findBy('status', array('user','autor'))->pluck('user_id') as $user_id) {
                        $seminar->addPreliminaryMember($user_id);
                        $num_moved += ($seminar->deleteMember($user_id) !== false);
                        setTempLanguage($user_id);
                        $message_body = sprintf(_('Sie wurden in der Veranstaltung **%s** in den Status **vorläufig akzeptiert** befördert, da das Anmeldeverfahren geändert wurde.'), $this->course->name);
                        $message_title = sprintf(_("Statusänderung %s"), $this->course->name);
                        messaging::sendSystemMessage($user_id, $message_title, $message_body);
                        restoreLanguage();
                    }
                    if ($num_moved) {
                        PageLayout::postMessage(MessageBox::success(sprintf(_("%s Teilnehmer wurden auf vorläufigen Eintrag gesetzt."), $num_moved)));
                    }
                }
                if ($this->course->admission_prelim == 0 && $this->course->getNumPrelimParticipants()) {
                    if (Request::submitted('change_admission_prelim_yes')) {
                        $num_moved = 0;
                        $seminar = new Seminar($this->course_id);
                        foreach ($this->course->admission_applicants->findBy('status', 'accepted')->pluck('user_id') as $user_id) {
                            $num_moved += ($seminar->addMember($user_id, 'autor') !== false);
                            setTempLanguage($user_id);
                            $message_body = sprintf(_('Sie wurden in der Veranstaltung **%s** in den Status **Autor** versetzt, da das Anmeldeverfahren geändert wurde.'), $this->course->name);
                            $message_title = sprintf(_("Statusänderung %s"), $this->course->name);
                            messaging::sendSystemMessage($user_id, $message_title, $message_body);
                            restoreLanguage();
                        }
                        if ($num_moved) {
                            PageLayout::postMessage(MessageBox::success(sprintf(_("%s Teilnehmer wurden in die Veranstaltung übernommen."), $num_moved)));
                        }
                    }
                    if (Request::submitted('change_admission_prelim_no')) {
                        $num_moved = 0;
                        foreach ($this->course->admission_applicants->findBy('status', 'accepted') as $applicant) {
                            setTempLanguage($applicant->user_id);
                            $message_body = sprintf(_('Sie wurden aus der Veranstaltung **%s** entfernt, da das Anmeldeverfahren geändert wurde.'), $this->course->name);
                            $message_title = sprintf(_("Statusänderung %s"), $this->course->name);
                            messaging::sendSystemMessage($applicant->user_id, $message_title, $message_body);
                            restoreLanguage();
                            $num_moved += $applicant->delete();
                        }
                        if ($num_moved) {
                            PageLayout::postMessage(MessageBox::success(sprintf(_("%s vorläufige Teilnehmer wurden entfernt."), $num_moved)));
                        }
                    }
                }
                if ($this->course->store()) {
                    PageLayout::postMessage(MessageBox::success(_("Der Anmeldemodus wurde geändert.")));
                }
                unset($question);
            }
        }
        if (!$question) {
            $this->redirect($this->url_for('/index'));
        } else {
            $this->button_yes = 'change_admission_prelim_yes';
            $this->button_no = 'change_admission_prelim_no';
            $this->request = $request;
            PageLayout::postMessage(MessageBox::info($question));
            $this->render_template('course/admission/_change_admission.php');
        }
    }

    /**
     * Change free access settings.
     */
    function change_free_access_action()
    {
        CSRFProtection::verifyUnsafeRequest();
        if (Request::submitted('change_free_access')) {
            $request = Request::extract('read_level submitted, write_level submitted');
            $request = array_diff_key($request, array_filter($this->is_locked));
            if (isset($request['write_level'])) {
                if ($request['write_level'] === true) {
                    $this->course->schreibzugriff = 0;
                    $request['read_level'] = true;
                } else {
                    $this->course->schreibzugriff = 1;
                }
            }
            if (isset($request['read_level'])) {
                if ($request['read_level'] === true) {
                    $this->course->lesezugriff = 0;
                } else {
                    $this->course->lesezugriff = 1;
                    $this->course->schreibzugriff = 1;
                }
            }
            if ($this->course->store()) {
                PageLayout::postMessage(MessageBox::success(_("Freier Zugriff wurde geändert.")));
            }
        }
        $this->redirect($this->url_for('/index'));
    }

    function change_admission_turnout_action()
    {
        CSRFProtection::verifyUnsafeRequest();
        $this->response->add_header('X-Title', _('Teilnehmeranzahl ändern'));
        if (Request::submitted('change_admission_turnout')) {
            $request = Request::extract('admission_turnout int, admission_disable_waitlist submitted, admission_disable_waitlist_move submitted, admission_waitlist_max int');
            $request = array_diff_key($request, array_filter($this->is_locked));
            $request['change_admission_turnout'] = 1;
            if ($request['admission_turnout'] > 1) {
                $this->course->admission_turnout = $request['admission_turnout'];
            }
            if (isset($request['admission_disable_waitlist'])) {
                $this->course->admission_disable_waitlist = $request['admission_disable_waitlist'] ? 0 : 1;
                if ($this->course->admission_disable_waitlist && $this->course->getNumWaiting()) {
                    $question = sprintf(_("Sie beabsichtigen die Warteliste zu deaktivieren. Die bestehende Warteliste mit %s Einträgen wird gelöscht. Sind sie sicher?"), $this->course->getNumWaiting());
                }
            }
            if (isset($request['admission_disable_waitlist_move'])) {
                $this->course->admission_disable_waitlist_move = $request['admission_disable_waitlist_move'] ? 0 : 1;
            }
            if (isset($request['admission_waitlist_max'])) {
                $this->course->admission_waitlist_max = $request['admission_waitlist_max'];
                if ($this->course->admission_waitlist_max > 0 && !$this->admission_disable_waitlist && $this->course->getNumWaiting() > $this->course->admission_waitlist_max) {
                    $question = sprintf(_("Sie beabsichtigen die Anzahl der Wartenden zu begrenzen. Die letzten %s Einträge der Warteliste werden gelöscht. Sind sie sicher?"), $this->course->getNumWaiting()-$this->course->admission_waitlist_max);
                }
            }
            if (Request::submitted('change_admission_turnout_yes') || !$question) {
                if ($this->course->admission_disable_waitlist && $this->course->getNumWaiting()) {
                    $removed_applicants = $this->course->admission_applicants->findBy('status', 'awaiting');
                }
                if ($this->course->admission_waitlist_max > 0 && !$this->admission_disable_waitlist && $this->course->getNumWaiting() > $this->course->admission_waitlist_max) {
                    $limit = $this->course->getNumWaiting() - $this->course->admission_waitlist_max;
                    $removed_applicants = $this->course->admission_applicants->findBy('status', 'awaiting')->orderBy('position desc', SORT_NUMERIC)->limit($limit);
                }
                if ($removed_applicants) {
                    $num_moved = 0;
                    foreach ($removed_applicants as $applicant) {
                        setTempLanguage($applicant->user_id);
                        $message_body = sprintf(_('Die Warteliste der Veranstaltung **%s** wurde von einem/r DozentIn oder AdministratorIn deaktiviert, Sie sind damit __nicht__ zugelassen worden.'),  $this->course->name);
                        $message_title = sprintf(_("Statusänderung %s"), $this->course->name);
                        messaging::sendSystemMessage($applicant->user_id, $message_title, $message_body);
                        restoreLanguage();
                        $num_moved += $applicant->delete();
                    }
                    if ($num_moved) {
                        PageLayout::postMessage(MessageBox::success(sprintf(_("%s Wartende wurden entfernt."), $num_moved)));
                    }
                }

                if ($this->course->store()) {
                    PageLayout::postMessage(MessageBox::success(_("Die Teilnehmeranzahl wurde geändert.")));
                }
                unset($question);
            }
        }
        if (!$question) {
            $this->redirect($this->url_for('/index'));
        } else {
            $this->request = $request;
            $this->button_yes = 'change_admission_turnout_yes';
            PageLayout::postMessage(MessageBox::info($question));
            $this->render_template('course/admission/_change_admission.php');
        }
    }

    function change_domains_action()
    {
        CSRFProtection::verifyUnsafeRequest();
        if (Request::submitted('change_domains') && !LockRules::Check($this->course_id, 'user_domain')) {
            $old_domains = array_map(function($d) {return $d->getId();}, UserDomain::getUserDomainsForSeminar($this->course_id));
            $new_domains = Request::getArray('user_domain');
            $changes = count(array_diff($old_domains, $new_domains)) + count(array_diff($new_domains, $old_domains));
            if ($changes) {
                UserDomain::removeUserDomainsForSeminar($this->course_id);
                foreach ($new_domains as $d) {
                    $domain = new UserDomain($d);
                    $domain->addSeminar($this->course_id);
                }
                PageLayout::postMessage(MessageBox::success(_("Die zugelassenen Nutzerdomänen wurden geändert.")));
            }
        }
        $this->redirect($this->url_for('/index'));
    }

    function change_course_set_action()
    {
        CSRFProtection::verifyUnsafeRequest();
        if (Request::submitted('change_course_set_assign') && Request::get('course_set_assign') && !LockRules::Check($this->course_id, 'admission_type')) {
            $cs = new CourseSet(Request::option('course_set_assign'));
            if ($cs->isUserAllowedToAssignCourse($this->user_id, $this->course_id)) {
                $cs->addCourse($this->course_id);
                $cs->store();
                $cs->load();
                if (in_array($this->course_id, $cs->getCourses())) {
                    PageLayout::postMessage(MessageBox::success(sprintf(_("Die Zuordnung zum Anmeldeset %s wurde durchgeführt."), htmlReady($cs->getName()))));
                }
            }
        }
        if (Request::submitted('change_course_set_unassign') && !LockRules::Check($this->course_id, 'admission_type')) {
            $this->response->add_header('X-Title', _('Anmelderegeln aufheben'));
            if ($this->course->getNumWaiting() && !Request::submitted('change_course_set_unassign_yes')) {
                $question = sprintf(_("In dieser Veranstaltung existiert eine Warteliste. Die bestehende Warteliste mit %s Einträgen wird gelöscht. Sind sie sicher?"), $this->course->getNumWaiting());
            }
            if (!$question && ($cs = CourseSet::getSetForCourse($this->course_id))) {
                $cs->removeCourse($this->course_id);
                $cs->store();
                $cs->load();
                if (!in_array($this->course_id, $cs->getCourses())) {
                    PageLayout::postMessage(MessageBox::success(sprintf(_("Die Zuordnung zum Anmeldeset %s wurde aufgehoben."), htmlReady($cs->getName()))));
                }
                if (!count($cs->getCourses())
                    && $this->user_id == $cs->getUserId()
                    && $cs->getPrivate()) {
                    $cs->delete();
                }
                if ($this->course->getNumWaiting()) {
                    $num_moved = 0;
                    foreach ($this->course->admission_applicants->findBy('status', 'awaiting') as $applicant) {
                        setTempLanguage($applicant->user_id);
                        $message_body = sprintf(_('Die Warteliste der Veranstaltung **%s** wurde von einem/r DozentIn oder AdministratorIn deaktiviert, Sie sind damit __nicht__ zugelassen worden.'),  $this->course->name);
                        $message_title = sprintf(_("Statusänderung %s"), $this->course->name);
                        messaging::sendSystemMessage($applicant->user_id, $message_title, $message_body);
                        restoreLanguage();
                        $num_moved += $applicant->delete();
                    }
                    if ($num_moved) {
                        PageLayout::postMessage(MessageBox::success(sprintf(_("%s Wartende wurden entfernt."), $num_moved)));
                    }
                }
            }
        }
        if (!$question) {
            $this->redirect($this->url_for('/index'));
        } else {
            $this->request = array('change_course_set_unassign' => 1);
            $this->button_yes = 'change_course_set_unassign_yes';
            PageLayout::postMessage(MessageBox::info($question));
            $this->render_template('course/admission/_change_admission.php');
        }
    }

    function explain_course_set_action()
    {
        $cs = new CourseSet(Request::option('set_id'));
        if ($cs->getId()) {
            $template = $GLOBALS['template_factory']->open('shared/tooltip');
            $this->render_text($template->render(array('text' => $cs->toString())));
        } else {
            $this->render_nothing();
        }
    }

    function instant_course_set_action()
    {
        $this->response->add_header('X-Title', _('Neue Anmelderegel'));
        list($type, $another_type) = explode('_', Request::option('type'));
        list($rule_id, $another_rule_id) = explode('_', Request::option('rule_id'));
        $rule_types = AdmissionRule::getAvailableAdmissionRules(true);
        if (isset($rule_types[$type])) {
            $rule = new $type($rule_id);
            if (isset($rule_types[$another_type])) {
                $another_rule = new $another_type($another_rule_id);
            }
            $course_set = CourseSet::getSetForRule($rule_id) ?: new CourseSet();
            if ((Request::isPost() && Request::submitted('save')) || $rule instanceof LockedAdmission) {
                if ($rule instanceof LockedAdmission) {
                     $course_set->setName($rule->getName() . ': ' . $this->course->name);
                } else {
                    CSRFProtection::verifyUnsafeRequest();
                    $rule->setAllData(Request::getInstance());
                    $errors = $rule->validate(Request::getInstance());
                    if ($another_rule) {
                        $another_rule->setAllData(Request::getInstance());
                        $errors = array_merge($errors, $another_rule->validate(Request::getInstance()));
                    }
                    if (!strlen(trim(Request::get('instant_course_set_name')))) {
                        $errors[] = _("Bitte geben Sie einen Namen für die Anmelderegel ein!");
                    } else {
                        $course_set->setName(trim(Request::get('instant_course_set_name')));
                    }
                }
                if (count($errors)) {
                    PageLayout::postMessage(MessageBox::error(_("Speichern fehlgeschlagen"), array_map('htmlready', $errors)));
                } else {
                    $rule->store();
                    $course_set->setPrivate(true);
                    $course_set->addAdmissionRule($rule);
                    $course_set->setAlgorithm(new RandomAlgorithm());//TODO
                    $course_set->setInstitutes(array($this->course->institut_id));
                    $course_set->setCourses(array($this->course_id));
                    if ($another_rule) {
                        $course_set->addAdmissionRule($another_rule);
                    }
                    $course_set->store();
                    PageLayout::postMessage(MessageBox::success(_("Die Anmelderegel wurde erzeugt und der Veranstaltung zugewiesen.")));
                    $this->redirect($this->url_for('/index'));
                    return;
                }
            }
            if (!$course_set->getId()) {
                $course_set->setName($rule->getName() . ': ' . $this->course->name);
            }
            $this->rule_template = $rule->getTemplate();
            $this->type = $type;
            $this->rule_id = $rule_id;
            if ($another_rule) {
                $this->type = $this->type . '_' . $another_type;
                $this->rule_id = $this->rule_id . '_' . $another_rule->getId();
                $this->rule_template = $another_rule->getTemplate() . $this->rule_template;
            }
            $this->course_set_name = $course_set->getName();
        } else {
            throw new Trails_Exception(400);
        }
    }

    function edit_courseset_action($cs_id)
    {
        $cs = new CourseSet($cs_id);
        if ($cs->isUserAllowedToEdit($this->user_id)) {
            $this->instant_course_set_view = true;
            $response = $this->relay('admission/courseset/configure/' . $cs->getId());
            $this->body = $response->body;
            if ($response->headers['Location']) {
                $this->redirect($response->headers['Location']);
            }
        } else {
            throw new Trails_Exception(400);
        }
    }

    function save_courseset_action($cs_id)
    {
        $cs = new CourseSet($cs_id);
        if ($cs->isUserAllowedToEdit($this->user_id)) {
            $this->instant_course_set_view = true;
            $response = $this->relay('admission/courseset/save/' . $cs->getId());
            $this->body = $response->body;
            if ($response->headers['Location']) {
                $this->redirect($response->headers['Location']);
            }
        } else {
            throw new Trails_Exception(400);
        }
    }

    function after_filter($action, $args)
    {
        if (Request::isXhr()) {
            foreach ($this->response->headers as $k => $v) {
                if ($k === 'Location') {
                    $this->response->headers['X-Location'] = $v;
                    unset($this->response->headers['Location']);
                    $this->response->set_status(200);
                    $this->response->body = '';
                }
            }
        }
        parent::after_filter($action, $args);
    }
}
