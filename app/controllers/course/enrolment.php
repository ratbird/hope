<?php
/**
* enrolment.php - enrolment in courses
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

/**
* @addtogroup notifications
*
* Enrolling in a course triggers a CourseDidEnroll
* notification. The course's ID is transmitted as
* subject of the notification.
*/
class Course_EnrolmentController extends AuthenticatedController {

    /**
    * common tasks for all actions
    */
    function before_filter(&$action, &$args) {
        parent::before_filter($action, $args);
        $this->course_id = $args[0];

        if (!in_array($action, words('apply claim delete order_down order_up'))) {
            $this->redirect($this->url_for('/apply/' . $action));
            return false;
        }
        if (!get_object_type($this->course_id, array('sem'))) {
            throw new Trails_Exception(400);
        }
        $course = Seminar::GetInstance($this->course_id);
        $enrolment_info = $course->getEnrolmentInfo($GLOBALS['user']->id);
        //Ist bereits Teilnehmer/Admin/freier Zugriff -> gleich weiter
        if ($enrolment_info['enrolment_allowed'] && in_array($enrolment_info['cause'], words('root courseadmin member free_access'))) {
            $redirect_url = UrlHelper::getUrl('seminar_main.php', array('auswahl' => $this->course_id));
            if (Request::isXhr()) {
                $this->response->add_header('X-Location', $redirect_url);
                $this->render_nothing();
            } else {
                $this->redirect($redirect_url);
            }
            return false;
        }
        //Grundsätzlich verboten
        if (!$enrolment_info['enrolment_allowed']) {
            throw new AccessDeniedException($enrolment_info['description']);
        }
        PageLayout::setTitle($course->getFullname() . " - " . _("Veranstaltungsanmeldung"));
        PageLayout::addSqueezePackage('enrolment');
        if (Request::isXhr()) {
            $this->set_layout(null);
            $this->response->add_header('X-No-Buttons', 1);
            $this->response->add_header('X-Title', PageLayout::getTitle());
            $request = Request::getInstance();
            foreach ($request as $key => $value) {
                $request[$key] = studip_utf8decode($value);
            }
        } else {
            $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));
        }
        $this->set_content_type('text/html;charset=windows-1252');
        if (Request::submitted('cancel')) {
            $this->redirect(URLHelper::getURL('dispatch.php/course/details/', array('sem_id' => $this->course_id)));
        }
    }

    /**
     * A person applies for a course.
     */
    function apply_action() {
        $user_id = $GLOBALS['user']->id;
        $courseset = CourseSet::getSetForCourse($this->course_id);
        $this->course_name = PageLayout::getTitle();
        if ($courseset) {
            $errors = $courseset->checkAdmission($user_id, $this->course_id);
            if (count($errors)) {
                $this->courseset_message = $courseset->toString(true);
                $this->admission_error = MessageBox::error(_("Die Anmeldung war nicht erfolgreich."), $errors);
                foreach ($courseset->getAdmissionRules() as $rule) {
                    $admission_form .= $rule->getInput();
                }
                if ($admission_form) {
                    $this->admission_form = $admission_form;
                }
            } else {
                if ($courseset->isSeatDistributionEnabled()) {
                    if ($courseset->hasAlgorithmRun()) {
                        if ($courseset->getSeatDistributionTime()) {
                            $msg = _("Die Plätze in dieser Veranstaltung wurden automatisch verteilt.");
                        }
                        if (StudipLock::get('enrolment' . $this->course_id)) {
                            $course = Course::find($this->course_id);
                            if ($course->getFreeSeats() && !$course->getNumWaiting()) {
                                $enrol_user = true;
                            } else {
                                if ($course->isWaitlistAvailable()) {
                                    $maxpos = $course->admission_applicants->findBy('status', 'awaiting')->orderBy('position desc')->val('position');
                                    $new_admission_member = new AdmissionApplication();
                                    $new_admission_member->user_id = $user_id;
                                    $new_admission_member->position = ++$maxpos;
                                    $new_admission_member->status = 'awaiting';
                                    $course->admission_applicants[] = $new_admission_member;
                                    if ($new_admission_member->store()) {
                                        $msg_details[] = sprintf(_("Alle Plätze sind belegt, Sie wurden daher auf Platz %s der Warteliste gesetzt."), $maxpos);
                                        StudipLog::log('SEM_USER_ADD', $course->id, $user_id, 'awaiting', 'Hat sich auf Warteliste eingetragen, Position: ' . $maxpos);
                                    }
                                } else {
                                    $this->admission_error = MessageBox::error(_("Die Anmeldung war nicht erfolgreich. Alle Plätze sind belegt und es steht keine Warteliste zur Verfügung."));
                                }
                            }
                        } else {
                            $this->admission_error = MessageBox::error(_("Die Anmeldung war wegen technischer Probleme nicht erfolgreich. Bitte versuchen Sie es später noch einmal."));
                        }
                    } else {
                        $msg = _("Die Plätze in dieser Veranstaltung werden automatisch verteilt.");
                        if ($limit = $courseset->getAdmissionRule('LimitedAdmission')) {
                            $msg_details[] = sprintf(_("Diese Veranstaltung gehört zu einem Anmeldeset mit %s Veranstaltungen. Sie können maximal %s davon belegen. Bei der Verteilung werden die von Ihnen gewünschten Prioritäten berücksichtigt."), count($courseset->getCourses()), $limit->getMaxNumber());
                            $this->user_max_limit = $limit->getMaxNumberForUser($user_id);
                            if (get_config('IMPORTANT_SEMNUMBER')) {
                                $order = "ORDER BY VeranstaltungsNummer, Name";
                            } else {
                                $order = "ORDER BY Name";
                            }
                            $this->priocourses = Course::findMany($courseset->getCourses(), $order);
                            $this->user_prio = AdmissionPriority::getPrioritiesByUser($courseset->getId(), $user_id);
                            $this->max_limit = $limit->getMaxNumber();
                            $this->prio_stats = AdmissionPriority::getPrioritiesStats($courseset->getId());
                            $this->already_claimed = count($this->user_prio);
                        } else {
                            $this->priocourses = Course::find($this->course_id);
                            $this->already_claimed = array_key_exists($this->course_id, AdmissionPriority::getPrioritiesByUser($courseset->getId(), $user_id));
                        }
                        $msg_details[] = _("Zeitpunkt der automatischen Verteilung: ") . strftime("%x %X", $courseset->getSeatDistributionTime());
                        $this->num_claiming = count(AdmissionPriority::getPrioritiesByCourse($courseset->getId(), $this->course_id));
                        if ($this->already_claimed) {
                            $msg_details[] = _("Sie sind bereits für die Verteilung angemeldet.");
                        }
                    }
                    $this->courseset_message = MessageBox::info($msg, $msg_details);
                } else {
                    $enrol_user = true;
                }
            }
        } else {
            $enrol_user = true;
        }

        if ($enrol_user) {
            $course = Seminar::GetInstance($this->course_id);
            if ($course->admission_prelim) {
                if (!$course->isStudygroup() && $course->admission_prelim_txt && !Request::submitted('apply')) {
                    $this->admission_prelim_txt = $course->admission_prelim_txt;
                    $this->admission_prelim_comment = Config::get()->ADMISSION_PRELIM_COMMENT_ENABLE;
                    $this->admission_form = $this->render_template_as_string('course/enrolment/prelim');
                } else {
                    if (Request::get('admission_comment')) {
                        $admission_comment = get_fullname() . ': ' . Request::get('admission_comment');
                    } else {
                        $admission_comment = '';
                    }
                    if ($course->addPreliminaryMember($user_id, $admission_comment)) {
                        if ($course->isStudygroup()) {
                            if (StudygroupModel::isInvited($user_id, $this->course_id)) {
                                // an invitation exists, so accept the join request automatically
                                $status = 'autor';
                                StudygroupModel::accept_user(get_username($user_id),$this->course_id);
                                StudygroupModel::cancelInvitation(get_username($user_id),$this->course_id);
                                $success = sprintf(_("Sie wurden in die Veranstaltung %s als %s eingetragen."), $course->getName(), get_title_for_status($status, 1));
                                PageLayout::postMessage(MessageBox::success($success));
                            } else {
                                $success = sprintf(_("Sie wurden auf die Anmeldeliste der Studiengruppe %s eingetragen. Die Moderatoren der Studiengruppe können Sie jetzt freischalten."), $course->getName());
                                PageLayout::postMessage(MessageBox::success($success));
                            }
                        } else {
                            $success = sprintf(_("Sie wurden in die Veranstaltung %s vorläufig eingetragen."), $course->getName());
                            PageLayout::postMessage(MessageBox::success($success));
                        }
                    }
                }
            } else {
                $status = 'autor';
                if ($course->addMember($user_id, $status)) {
                    $success = sprintf(_("Sie wurden in die Veranstaltung %s als %s eingetragen."), $course->getName(), get_title_for_status($status, 1));
                    PageLayout::postMessage(MessageBox::success($success));
                    $this->enrol_user = true;

                    if (StudygroupModel::isInvited($user_id, $this->course_id)) {
                        // delete an existing invitation
                        StudygroupModel::cancelInvitation(get_username($user_id),$this->course_id);
                    }
                }
            }
            unset($this->courseset_message);
        }
        StudipLock::release();
    }

    /**
     * Prioritize courses.
     */
    function claim_action() {
        CSRFProtection::verifyUnsafeRequest();
        $user_id = $GLOBALS['user']->id;
        $courseset = CourseSet::getSetForCourse($this->course_id);
        if ($courseset->isSeatDistributionEnabled() && !count($courseset->checkAdmission($user_id, $this->course_id))) {
            if ($limit = $courseset->getAdmissionRule('LimitedAdmission')) {
                $admission_user_limit = Request::int('admission_user_limit');
                if ($admission_user_limit && $admission_user_limit <= $limit->getMaxNumber()) {
                    $limit->setCustomMaxNumber($user_id, $admission_user_limit);
                }
                $admission_prio = Request::getArray('admission_prio');
                $max_prio = max($admission_prio);
                $admission_prio = array_map(function($a) use (&$max_prio) {return $a > 0  ? $a : ++$max_prio;}, $admission_prio);
                if (count(array_unique($admission_prio)) != count(Request::getArray('admission_prio'))) {
                    PageLayout::postMessage(MessageBox::info(_("Sie dürfen jede Priorität nur einmal auswählen. Überprüfen Sie bitte Ihre Auswahl!")));
                }
                $old_prio_count = AdmissionPriority::unsetAllPrioritiesForUser($courseset->getId(), $user_id);
                if ($order_up = key(Request::getArray('admission_prio_order_up'))) {
                    $prio_to_move = $admission_prio[$order_up];
                    $change_with = array_search($prio_to_move - 1, $admission_prio);
                    $admission_prio[$order_up] = $prio_to_move - 1;
                    $admission_prio[$change_with] = $prio_to_move;
                }
                if ($order_down = key(Request::getArray('admission_prio_order_down'))) {
                    $prio_to_move = $admission_prio[$order_down];
                    $change_with = array_search($prio_to_move + 1, $admission_prio);
                    $admission_prio[$order_down] = $prio_to_move + 1;
                    $admission_prio[$change_with] = $prio_to_move;
                }
                if ($delete = key(Request::getArray('admission_prio_delete'))) {
                    unset($admission_prio[$delete]);
                    $changed = 1;
                    $admission_prio = array_map(function($a) {static $c = 1; return $c++;}, $admission_prio);
                }
                foreach ($admission_prio as $course_id => $p) {
                    $changed += AdmissionPriority::setPriority($courseset->getId(), $user_id, $course_id, $p);
                }
                if ($changed || ($old_prio_count && !count($admission_prio))) {
                    if (count(AdmissionPriority::getPrioritiesByUser($courseset->getId(), $user_id))) {
                        PageLayout::postMessage(MessageBox::success(_("Ihre Priorisierung wurde gespeichert.")));
                    } else {
                        PageLayout::postMessage(MessageBox::success(_("Ihre Anmeldung zur Platzvergabe wurde zurückgezogen.")));
                    }
                }
            } else {
                if (Request::int('courseset_claimed')) {
                    if (AdmissionPriority::setPriority($courseset->getId(), $user_id, $this->course_id, 1)) {
                        PageLayout::postMessage(MessageBox::success(_("Ihre Anmeldung zur Platzvergabe wurde gespeichert.")));
                    }
                } else {
                    if (AdmissionPriority::unsetPriority($courseset->getId(), $user_id, $this->course_id)) {
                        PageLayout::postMessage(MessageBox::success(_("Ihre Anmeldung zur Platzvergabe wurde zurückgezogen.")));
                    }
                }
            }
        }
        $this->redirect($this->url_for('/apply/' . $this->course_id));
    }
}
