<?php

/*
 * MembersConrtoller
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      David Siegfried <david.siegfried@uni-oldenburg.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       2.5
 */

require_once 'app/controllers/authenticated_controller.php';
require_once 'app/models/members.php';
require_once 'app/models/user.php';
require_once 'lib/messaging.inc.php'; //Funktionen des Nachrichtensystems

require_once 'lib/admission.inc.php'; //Funktionen der Teilnehmerbegrenzung
require_once 'lib/functions.php'; //Funktionen der Teilnehmerbegrenzung
require_once 'lib/language.inc.php'; //Funktionen der Teilnehmerbegrenzung
require_once 'lib/export/export_studipdata_func.inc.php'; // Funktionne f�r den Export

class Course_MembersController extends AuthenticatedController {

    function before_filter(&$action, &$args) {
        parent::before_filter($action, $args);

        global $perm;

        if ($GLOBALS['rechte']) {
            PageLayout::setHelpKeyword("Basis.VeranstaltungenVerwaltenTeilnehmer");
        } else {
            PageLayout::setHelpKeyword("Basis.InVeranstaltungTeilnehmer");
        }

        // Get the global rights
        $this->rechte = $GLOBALS['rechte'];
        $this->course_id = $_SESSION['SessSemName'][1];
        $this->course_title = $_SESSION['SessSemName'][0];
        $this->header_line = $_SESSION['SessSemName']['header_line'];
        $this->user_id = $GLOBALS['auth']->auth['uid'];

        // Check lock rules
        $this->dozent_is_locked = LockRules::Check($this->course_id, 'dozent');
        $this->tutor_is_locked = LockRules::Check($this->course_id, 'tutor');

        // Layoutsettings
        PageLayout::setTitle($this->header_line . " - " . _("TeilnehmerInnen"));

        SkipLinks::addIndex(Navigation::getItem('/course/members')->getTitle(), 'main_content', 100);

        Navigation::activateItem('/course/members');
        Navigation::activateItem('/course/members/view');
        if (Request::isXhr()) {
            $this->set_layout(null);
        } else {
            $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));
        }

        $this->last_visitdate = object_get_visit($this->course_id, 'participants');

        // Check perms and set the last visit date
        if (!$perm->have_studip_perm('tutor', $this->course_id)) {
            $this->last_visitdate = time() + 10;
        }


        // Get the max-page-value for the pagination
        $this->max_per_page = Config::get()->ENTRIES_PER_PAGE;
        $this->status_groups = array(
            'dozent' => get_title_for_status('dozent', 2),
            'tutor' => get_title_for_status('tutor', 2),
            'autor' => get_title_for_status('autor', 2),
            'user' => get_title_for_status('user', 2),
            'accepted' => get_title_for_status('accepted', 2)
        );

        // StatusGroups for the view
        $this->decoratedStatusGroups = array(
            'dozent' => get_title_for_status('dozent', 1),
            'autor' => get_title_for_status('autor', 1),
            'tutor' => get_title_for_status('tutor', 1),
            'user' => get_title_for_status('user', 1)
        );

        // Create new MembersModel, to get additionanl informations to a given Seminar
        $this->members = new MembersModel($this->course_id, $this->course_title);
    }

    function index_action($page = 1) {
        global $perm, $rechte, $PATH_EXPORT;
        $sem = Seminar::getInstance($this->course_id);

        $this->course = new Course($this->course_id);

        #echo "<pre>"; var_dump($this->course); echo "</pre>";
        // if export enable, include export function an create export links
        if (get_config('EXPORT_ENABLE') AND $perm->have_studip_perm("tutor", $this->course_id)) {
            include_once($PATH_EXPORT . "/export_linking_func.inc.php");

            // create csv-export link
            $this->csvExport = export_link($this->course_id, "person", 
                    sprintf('%s %s', htmlReady($this->status_groups['autor']), 
                            htmlReady($this->course_title)), 'csv', 'csv-teiln', '', 
                    Assets::img('icons/16/blue/file-xls.png', array(
                        'alt' => sprintf(_('%s exportieren als csv Dokument'), 
                                htmlReady($this->decoratedStatusGroups['autor'])),
                        'title' => sprintf(_('%s exportieren als csv Dokument'), 
                                htmlReady($this->status_groups['autor'])))), 
                    'passthrough');

            // create csv-export link
            $this->rtfExport = export_link($this->course_id, "person", 
                    sprintf('%s %s', htmlReady($this->status_groups['autor']), 
                            htmlReady($this->course_title)), 'rtf', 'rtf-teiln', '',
                    Assets::img('icons/16/blue/file-text.png', array(
                        'alt' => sprintf(_('%s exportieren als rtf Dokument'), 
                                htmlReady($this->decoratedStatusGroups['autor'])),
                        'title' => sprintf(_('%s exportieren als rtf Dokument'), 
                                htmlReady($this->decoratedStatusGroups['autor'])))), 
                    'passthrough');
        }

        // old message style
        if ($_SESSION['sms_msg']) {
            $this->msg = $_SESSION['sms_msg'];
            unset($_SESSION['sms_msg']);
        }

        // if user have no perms
        if (!$rechte) {
            $this->set_layout($GLOBALS['template_factory']->open('layouts/base_without_infobox'));
        }

        // Check dozent-perms
        if ($perm->have_studip_perm('dozent', $this->course_id)) {
            $this->is_dozent = true;
        }

        // Check tutor-perms
        if ($perm->have_studip_perm('tutor', $this->course_id)) {
            $this->is_tutor = true;
        }

        // Check autor-perms
        if ($this->is_autor || $perm->have_studip_perm('user', $this->course_id) && !$this->is_dozent) {
            SkipLinks::addIndex(_("Sichtbarkeit �ndern"), 'change_visibility');
            $this->is_autor = true;
            $this->invisibles = $this->getInvisibleCount();

            $this->my_visibilty = $this->members->getUserVisibility($this->user_id, $this->course_id);
        }

        // Check Seminar
        if ($rechte && $sem->isAdmissionEnabled()) {
            $this->semAdmissionEnabled = true;
            $this->count = $this->members->getCountedMembers();
        }

        $this->sort_by = Request::option('sortby', 'nachname');
        $this->order = Request::option('order', 'asc');
        $this->sort_status = Request::get('sort_status');
        $this->page = $page;

        if (Request::int('toggle')) {
            $this->order = $this->order == 'desc' ? 'asc' : 'desc';
        }

        // Set the count variable (autoren)
        if ($page > 1) {
            $this->autor_nr = $this->max_per_page * ($page - 1);
        } else {
            $this->autor_nr = 0;
        }

        // get member informations
        $this->dozenten     = $this->getMembers('dozent');
        $this->tutoren      = $this->getMembers('tutor');
        $this->autoren      = $this->getAutors();
        $this->users        = $this->getMembers('user');
        $this->awaiting     = $this->getMembers('awaiting');
        $this->accepted     = $this->getMembers('accepted');
        $this->studipticket = Seminar_Session::get_ticket();
        $this->subject      = $this->members->getSubject();
        $this->groups       = $this->status_groups;
        $this->rechte       = $rechte;
        $this->waitingTitle = $this->getTitleForAwaiting();

        // Set the infobox
        if ($rechte) {
            $this->setInfoBoxImage('infobox/groups.jpg');

            $link = sprintf('<a href="%s">%s</a>', URLHelper::getLink('sms_send.php', 
                    array('sms_source_page' => 'dispatch.php/cource/members',
                        'course_id' => $this->course_id,
                        'subject' => $this->subject,
                        'filter' => 'all',
                        'emailrequest' => 1)), _('Nachricht an alle (Rundmail)'));
            $this->addToInfobox(_('Aktionen'), $link, 'icons/16/blue/inbox.png');
        }
    }

    /**
     * Get all members by status of a seminar
     * @return SimpleOrMapCollection
     */
    private function getMembers($status) {
        $course = new Course($this->course_id);
        // get members
        if ($status == 'awaiting' || $status == 'accepted') {
            $res = $course->admission_applicants->findBy('status', $status);

            if ($status == $this->sort_status) {
                $res->orderBy(sprintf('%s %s', $this->sort_by, $this->order), 
                        ($this->sort_by == 'position') ? SORT_NUMERIC : SORT_NATURAL);
            } else {
                $res->orderBy('position asc', SORT_NUMERIC);
            }

        } else {
            $res = $course->members->findBy('status', $status);

            if ($status == $this->sort_status) {
                $res->orderBy(sprintf('%s %s', $this->sort_by, $this->order));
            } else {
                $res->orderBy('nachname asc');
            }
        }

        return $res;
    }
    
    
    /**
     * Get all authors of a seminar
     * @global Object $perm
     * @return SimpleOrMapCollection
     */
    private function getAutors() {
        global $perm;
        
        $course = new Course($this->course_id);
        $members = $course->members->findBy('status', 'autor');

        // filter invisible user if not dozent
        if (!$perm->have_studip_perm('dozent', $this->course_id)) {
            $user_id = $this->user_id;
            $members = $members->filter(function($user)use($user_id) {
                        return ($user['visible'] != 'no' || $user['user_id'] == $user_id);
            });
        }
        
        // Count total member for pagination
        $this->total = count($members);

        // Sorting
        if ($this->sort_status == 'autor') {
            $members->orderBy(sprintf('%s %s', $this->sort_by, $this->order));
        } else {
            $members->orderBy('nachname asc');
        }

        if ($this->page != 0) {
            $offset = ($this->page - 1) * $this->max_per_page;

            $results = $members->limit($offset, $this->max_per_page);
        } else {
            $results = $members;
        }
        
        return $results;
    }

    /*
     * Returns an array with emails of members
     */
    public static function getEmailLinkByStatus($course_id, $status)
    {
        $course = new Course($course_id);
        
        if($status == 'accepted' || $status == 'awaiting') {
            $members = $course->admission_applicants->findBy('status', $status);
        } else {
            $members = $course->members->findBy('status', $status); 
        }
        
        $results = $members->pluck('email');
        
        if(!empty($results)) {
            return sprintf('<a href="mailto:%s">%s</a>', 
                    htmlReady(join(',', $results)),
                    Assets::img('icons/16/blue/move_right/mail.png', tooltip2('Email an alle NutzerInnen senden')));
        } else {
            return null;
        }
        
    }
    
    /**
     * Get the count of invisible members
     *
     * @return int
     */
    private function getInvisibleCount() {
        $course = new Course($this->course_id);
        $user_id = $this->user_id;
        return $course->members->findBy('status', 'autor')->findBy('visible', 'no')
                        ->filter(function($user)use($user_id) {
                                    return $user['user_id'] != $user_id;
                                })
                        ->count();
    }


    /**
     * New dozent action.
     * @global Object $perm
     * @global Boolean $rechte
     * @throws AccessDeniedException
     */
    function add_dozent_action() {
        global $perm, $rechte;
        // Security Check
        if ((!$rechte || !$perm->have_studip_perm('dozent', $this->course_id)) && $this->dozent_is_locked) {
            throw new AccessDeniedException('Sie haben keine ausreichende Berechtigung, 
                um auf diesen Teil des Systems zuzugreifen');
        }

        $sem = Seminar::GetInstance($this->course_id);
        Request::set('new_dozent_parameter', $this->flash['new_dozent_parameter']);

        $sem_institutes = $sem->getInstitutes();

        if (SeminarCategories::getByTypeId($sem->status)->only_inst_user) {
            $search_template = "user_inst_not_already_in_sem";
        } else {
            $search_template = "user_not_already_in_sem";
        }

        // create new search for dozent
        $this->search = new PermissionSearch(
                $search_template, sprintf(_("%s suchen"), get_title_for_status('dozent', 1, $sem->status)), 
                "user_id", array('permission' => 'dozent',
            'seminar_id' => $this->course_id,
            'sem_perm' => 'dozent',
            'institute' => $sem_institutes
                )
        );
    }

    /**
     * New tutor action
     * @global Object $perm
     * @global Boolean $rechte
     * @throws AccessDeniedException
     */
    function add_tutor_action() {
        global $perm, $rechte;

        if (!$rechte || !$perm->have_studip_perm('tutor', $this->course_id) || $this->is_tutor_locked) {
            throw new AccessDeniedException('Sie haben keine ausreichende Berechtigung, 
                um auf diesen Teil des Systems zuzugreifen');
        }
        $sem = Seminar::GetInstance($this->course_id);
        Request::set('new_tutor_parameter', $this->flash['new_tutor_parameter']);

        $sem_institutes = $sem->getInstitutes();

        if (SeminarCategories::getByTypeId($sem->status)->only_inst_user) {
            $search_template = "user_inst_not_already_in_sem";
        } else {
            $search_template = "user_not_already_in_sem";
        }

        $this->search = new PermissionSearch(
                $search_template, sprintf(_("%s suchen"), get_title_for_status('tutor', 1, $sem->status)), 
                "user_id", array('permission' => array('dozent', 'tutor'),
            'seminar_id' => $this->course_id,
            'sem_perm' => array('dozent', 'tutor'),
            'institute' => $sem_institutes
                )
        );
    }
    
    /**
     * New author action
     * @global Object $perm
     * @global Boolean $rechte
     * @throws AccessDeniedException
     */
    function add_member_action() {
        global $perm, $rechte;
        $this->set_layout($GLOBALS['template_factory']->open('layouts/base_without_infobox'));
        // get the seminar object
        $sem = Seminar::GetInstance($this->course_id);
        $sem->restoreAdmissionStudiengang();

        // Security Check
        if (!$rechte || !$perm->have_studip_perm('tutor', $this->course_id)) {
            throw new AccessDeniedException('Sie haben keine ausreichende Berechtigung, 
                um auf diesen Teil des Systems zuzugreifen');
        }

        // Check Seminar
        if ($rechte && $sem->isAdmissionEnabled()) {
            $this->semAdmissionEnabled = true;

            if (!empty($sem->admission_studiengang)) {
                $admission_studiengang = $sem->admission_studiengang;

                foreach (array_keys($admission_studiengang) as $studiengang) {
                    $admission_studiengang[$studiengang]['freeSeats'] = $sem->getFreeAdmissionSeats($studiengang);
                }
                $this->admission_studiengang = $admission_studiengang;
            }
        }
        // Damit die QuickSearch funktioniert
        Request::set('new_autor', $this->flash['new_autor']);
        Request::set('new_autor', $this->flash['new_autor_1']);
        Request::set('new_autor_parameter', $this->flash['new_autor_parameter']);
        Request::set('seminar_id', $this->course_id);
        Request::set('consider_contingent', $this->flash['consider_contingent']);

        // new user-search for given status
        $this->search = new SQLSearch("SELECT auth_user_md5.user_id, CONCAT(auth_user_md5.Nachname, \", 
                \", auth_user_md5.Vorname, \" (\", auth_user_md5.username, \")\") " .
                "FROM auth_user_md5 " .
                "LEFT JOIN user_info ON (user_info.user_id = auth_user_md5.user_id) " .
                "WHERE (CONCAT(auth_user_md5.Vorname, \" \", auth_user_md5.Nachname) LIKE :input " .
                "OR auth_user_md5.username LIKE :input) " .
                "AND auth_user_md5.perms IN ('autor', 'tutor', 'dozent') " .
                "AND auth_user_md5.user_id NOT IN (SELECT user_id FROM seminar_user WHERE Seminar_id = :cid ) " .
                "ORDER BY Vorname, Nachname", _("Teilnehmer suchen"), "username");

// TODO
//        foreach (DataFieldStructure::getDataFieldStructures('user', (1|2|4|8), true) as $df) {
//            if ($df->accessAllowed($perm) && in_array($df->getId(), $GLOBALS['TEILNEHMER_IMPORT_DATAFIELDS'])) {
//                $accessible_df[] = $df;
//            }
//        }
    }
    
    
    /**
     * Add a member to a seminar
     * @global Object $perm
     * @global Boolean $rechte
     * @throws AccessDeniedException
     */
    function set_action() {
        global $perm, $rechte;

        // Security Check
        if (!$rechte) {
            throw new AccessDeniedException('Sie haben keine ausreichende Berechtigung, 
                um auf diesen Teil des Systems zuzugreifen');
        }

        $sem = Seminar::GetInstance($this->course_id);
        // insert new dozent in a seminar
        if (Request::get('new_dozent') && (Request::submitted('add_dozent') 
                || Request::submitted('add_dozent_x')) && $perm->have_studip_perm("dozent", $this->course_id)) {

            $deputies_enabled = get_config('DEPUTIES_ENABLE');

            if ($sem->addMember(Request::option('new_dozent'), "dozent")) {
                // Only applicable when globally enabled and user deputies enabled too
                if ($deputies_enabled) {
                    // Check whether chosen person is set as deputy
                    // -> delete deputy entry.
                    if (isDeputy(Request::option('new_dozent'), $this->course_id)) {
                        deleteDeputy(Request::option('new_dozent'), $this->course_id);
                    }
                    // Add default deputies of the chosen lecturer...
                    if (get_config('DEPUTIES_DEFAULTENTRY_ENABLE')) {
                        $deputies = getDeputies(Request::option('new_dozent'));
                        $lecturers = $sem->getMembers('dozent');
                        foreach ($deputies as $deputy) {
                            // ..but only if not already set as lecturer or deputy.
                            if (!isset($lecturers[$deputy['user_id']]) &&
                                    !isDeputy($deputy['user_id'], $this->course_id)) {
                                addDeputy($deputy['user_id'], $this->course_id);
                            }
                        }
                    }
                }
                // new dozent was successfully insert
                PageLayout::postMessage(MessageBox::success(printf(_("%s wurde hinzugef�gt."), 
                        get_title_for_status('dozent', 1, $sem->status))));
            } else {
                // sorry that was a fail
                PageLayout::postMessage(MessageBox::error(_('Die gew�nsche Operation konnte nicht ausgef�hrt werden')));
            }
            // go back
            $this->redirect('course/members/?cid=' . Request::get('cid'));
        }

        // empty dozent formular
        if (Request::submitted('search_dozent') && Request::submitted('search_dozent_x')) {
            $this->flash['new_dozent_parameter'] = Request::get('new_dozent_parameter');
            $this->redirect('course/members/add_dozent?cid=' . Request::get('cid'));
        }

        //insert new tutor
        if (Request::option('new_tutor') && (Request::submitted('add_tutor_x') 
                || Request::submitted('add_tutor')) && $perm->have_studip_perm("tutor", $this->course_id)) {

            if ($sem->addMember(Request::option('new_tutor'), "tutor")) {
                PageLayout::postMessage(MessageBox::success(sprintf(_("%s wurde hinzugef�gt."), 
                        get_title_for_status('tutor', 1, $sem->status))));
            } else {
                // sorry that was a fail
                PageLayout::postMessage(MessageBox::error(_('Die gew�nsche Operation konnte nicht ausgef�hrt werden')));
            }

            // go back
            $this->redirect('course/members/?cid=' . Request::get('cid'));
        }

        // empty tutor formular
        if (Request::submitted('search_tutor') && Request::submitted('search_tutor_x')) {
            $this->flash['new_tutor_parameter'] = Request::get('new_tutor_parameter');
            $this->redirect('course/members/add_tutor?cid=' . Request::get('cid'));
        }

        if (Request::submitted('reset_dozent') && Request::submitted('reset_dozent_x')) {
            $this->redirect('course/members/add_dozent?cid=' . Request::get('cid'));
        }

        if (Request::submitted('reset_tutor') && Request::submitted('reset_tutor_x')) {
            $this->redirect('course/members/add_tutor?cid=' . Request::get('cid'));
        }
    }
    
    /**
     * Add a author to a seminar 
     * @global Object $perm
     * @global Boolean $rechte
     * @throws AccessDeniedException
     */
    function set_autor_action() {
        global $perm, $rechte;

        // Security Check
        if (!$rechte || !$perm->have_studip_perm('tutor', $this->course_id)) {
            throw new AccessDeniedException('Sie haben keine ausreichende Berechtigung, 
                um auf diesen Teil des Systems zuzugreifen');
        }

        // empty autor formular
        if (Request::submitted('search_autor') && Request::submitted('search_autor_x')) {
            $this->flash['new_autor'] = Request::get('new_autor');
            $this->flash['new_autor_1'] = Request::get('new_autor_1');
            $this->flash['new_autor_parameter'] = Request::get('new_autor_parameter');
            $this->flash['consider_contingent'] = Request::get('consider_contingent');

            $this->redirect('course/members/add_member?cid=' . Request::get('cid'));
        }

        //insert new autor
        if (Request::option('new_autor') && (Request::submitted('add_autor_x') 
                || Request::submitted('add_autor')) && $perm->have_studip_perm("tutor", $this->course_id)) {

            $msg = $this->members->addMember(Request::get('new_autor'), 'autor', Request::get('consider_contingent'));

            PageLayout::postMessage($msg);

            $this->redirect('course/members/?cid=' . Request::get('cid'));
        }

        if (Request::submitted('reset_autor') && Request::submitted('reset_autor_x')) {
            $this->redirect('course/members/add_member?cid=' . Request::get('cid'));
        }
    }

    /**
     * Old version of CSV import (copy and paste from teilnehmer.php
     * @global Boolean $rechte
     * @global Object $perm
     * @global type $TEILNEHMER_IMPORT_DATAFIELDS
     * @return type
     * @throws AccessDeniedException
     */
    function set_autor_csv_action() {
        global $rechte, $perm, $TEILNEHMER_IMPORT_DATAFIELDS;
        // Security Check
        if (!$rechte) {
            throw new AccessDeniedException('Sie haben keine ausreichende Berechtigung, 
                um auf diesen Teil des Systems zuzugreifen');
        }

        // prepare CSV-Lines
        $messaging = new messaging();
        $csv_request = preg_split('/(\n\r|\r\n|\n|\r)/', trim(Request::get('csv_import')));
        $csv_mult_founds = array();
        $csv_count_insert = 0;
        $csv_count_multiple = 0;
        $datafield_id = null;

        if (Request::get('csv_import_format') && !in_array(Request::get('csv_import_format'), 
                words('realname username'))) {
            foreach (DataFieldStructure::getDataFieldStructures('user', (1 | 2 | 4 | 8), true) as $df) {
                if ($df->accessAllowed($perm) && in_array($df->getId(), 
                        $TEILNEHMER_IMPORT_DATAFIELDS) && $df->getId() == Request::quoted('csv_import_format')) {
                    $datafield_id = $df->getId();
                    break;
                }
            }
        }
        
        
        if (Request::get('csv_import')) {
            // remove duplicate users from csv-import
            $csv_lines = array_unique($csv_request);
            $csv_count_contingent_full = 0;
            
            foreach ($csv_lines as $csv_line) {
                $csv_name = preg_split('/[,\t]/', substr($csv_line, 0, 100), -1, PREG_SPLIT_NO_EMPTY);
                $csv_nachname = trim($csv_name[0]);
                $csv_vorname = trim($csv_name[1]);
                
                if ($csv_nachname) {
                    if (Request::quoted('csv_import_format') == 'realname') {
                        $csv_users = $this->members->getMemberByIdentification($csv_nachname, $csv_vorname);
                    } elseif (Request::quoted('csv_import_format') == 'username') {
                        $csv_users = $this->members->getMemberByUsername($csv_nachname);
                    } else {
                        $csv_users = $this->members->getMemberByDatafield($csv_nachname, $datafield_id);
                    }
                }

                // if found more then one result to given name
                if (count($csv_users) > 1) {

                    // if user have two accounts
                    $csv_count_present = 0;
                    foreach ($csv_users as $row) {

                        if ($row['is_present']) {
                            $csv_count_present++;
                        } else {
                            $csv_mult_founds[$csv_line][] = $row;
                        }
                    }

                    if (is_array($csv_mult_founds[$csv_line])) {
                        $csv_count_multiple++;
                    }
                } elseif (count($csv_users) > 0) {
                    $row = reset($csv_users);
                    if (!$row['is_present']) {
                        $consider_contingent = Request::option('consider_contingent_csv');
                        
                        if (insert_seminar_user($this->course_id, $row['user_id'], 'autor', isset($consider_contingent), $consider_contingent)) {
                            $csv_count_insert++;
                            setTempLanguage($this->user_id);

                            if ($GLOBALS['SEM_CLASS'][$GLOBALS['SEM_TYPE'][$_SESSION['SessSemName']['art_num']]['class']]['workgroup_mode']) {
                                $message = sprintf(_('Sie wurden von einem/r LeiterIn oder AdministratorIn als 
                                    TeilnehmerIn in die Veranstaltung **%s** eingetragen.'), $this->course_title);
                            } else {
                                $message = sprintf(_('Sie wurden von einem/r DozentIn oder AdministratorIn als 
                                    TeilnehmerIn in die Veranstaltung **%s** eingetragen.'), $this->course_title);
                            }

                            restoreLanguage();
                            $messaging->insert_message(mysql_escape_string($message), 
                                    $row['username'], '____%system%____', FALSE, FALSE, '1', FALSE,
                                    sprintf('%s %s',_('Systemnachricht:'),_('Eintragung in Veranstaltung')), TRUE);
                        } elseif (isset($consider_contingent)) {
                            $csv_count_contingent_full++;
                        }
                    } else {
                        $csv_count_present++;
                    }
                } else {
                    // not found
                    $csv_not_found[] = stripslashes($csv_nachname) . ($csv_vorname ? ', ' . stripslashes($csv_vorname) : '');
                }
            }
        }
        $selected_users = Request::getArray('selected_users');
        
        if (!empty($selected_users) && count($selected_users) > 0) {
            foreach ($selected_users as $selected_user) {
                if ($selected_user) {
                    if (insert_seminar_user($this->course_id, get_userid($selected_user), 'autor', 
                            isset($consider_contingent), $consider_contingent)) {
                        $csv_count_insert++;
                        setTempLanguage($this->user_id);
                        if ($GLOBALS['SEM_CLASS'][$GLOBALS['SEM_TYPE'][$_SESSION['SessSemName']['art_num']]['class']]['workgroup_mode']) {
                            $message = sprintf(_('Sie wurden von einem/r LeiterIn oder AdministratorIn als 
                                TeilnehmerIn in die Veranstaltung **%s** eingetragen.'), $this->course_title);
                        } else {
                            $message = sprintf(_('Sie wurden vom einem/r DozentIn oder AdministratorIn als 
                                TeilnehmerIn in die Veranstaltung **%s** eingetragen.'), $this->course_title);
                        }
                        restoreLanguage();
                        $messaging->insert_message(mysql_escape_string($message), 
                                $selected_user, '____%system%____', FALSE, FALSE, '1', FALSE, 
                                sprintf('%s %s',_('Systemnachricht:'),_('Eintragung in Veranstaltung')), TRUE);
                    } elseif (isset($consider_contingent)) {
                        $csv_count_contingent_full++;
                    }
                }
            }
        }

        // no results
        if (!sizeof($csv_lines) && !sizeof($selected_users)) {
            PageLayout::postMessage(MessageBox::error(_("Keine NutzerIn gefunden!")));
        }

        if ($csv_count_insert) {
            PageLayout::postMessage(MessageBox::success(sprintf(_('%s NutzerInnen als AutorIn in die Veranstaltung 
                eingetragen!'), $csv_count_insert)));
        }

        if ($csv_count_present) {
            PageLayout::postMessage(MessageBox::info(sprintf(_('%s NutzerInnen waren bereits in der Veranstaltung 
                eingetragen!'), $csv_count_present)));
        }

        // redirect to manual assignment
        if ($csv_mult_founds) {
            PageLayout::postMessage(MessageBox::info(sprintf(_('%s NutzerInnen konnten <b>nicht eindeutig</b> 
                zugeordnet werden! Nehmen Sie die Zuordnung manuell vor.'), $csv_count_multiple)));
            $this->flash['csv_mult_founds'] = $csv_mult_founds;
            $this->redirect('course/members/csv_manual_assignment');
            return;
        }
        if (count($csv_not_found) > 0) {
            PageLayout::postMessage(MessageBox::error(sprintf(_('%s konnten <b>nicht</b> zugeordnet werden!'), 
                    htmlReady(join(',', $csv_not_found)))));
        }

        if ($csv_count_contingent_full) {
            PageLayout::postMessage(MessageBox::error(sprintf(_('%s NutzerInnen konnten <b>nicht</b> zugeordnet werden,
                da das ausgew�hlte Kontingent keine freien Pl�tze hat.'), $csv_count_contingent_full)));
        }

        $this->redirect('course/members?' . Request::get('cid'));
    }
    
    /**
     * Select manual the assignment of a given user or of a group of users
     * @global Object $perm
     * @throws AccessDeniedException
     */
    function csv_manual_assignment_action() {
        global $perm;
        // Security. If user not autor, then redirect to index
        if (!$perm->have_studip_perm('tutor', $this->course_id)) {
            throw new AccessDeniedException('Sie haben keine ausreichende Berechtigung, um auf diesen Teil 
                des Systems zuzugreifen');
        }

        if (empty($this->flash['csv_mult_founds'])) {
            $this->redirect('course/members?' . Request::get('cid'));
        }
    }

    /**
     * Change the visibilty of an autor
     * @return Boolean
     */
    function change_visibility_action() {
        global $perm;
        // Security. If user not autor, then redirect to index
        if ($perm->have_studip_perm('tutor', $this->course_id)) {
            throw new AccessDeniedException('Sie haben keine ausreichende Berechtigung, 
                um auf diesen Teil des Systems zuzugreifen');
        }

        // Check for visibile mode
        if (Request::get('cmd') == 'make_visible') {
            $cmd = 'yes';
        } else {
            $cmd = 'no';
        }

        if (Request::option('mode') == 'awaiting') {
            $result = $this->members->setAdmissionVisibility($this->user_id, $cmd);
        } else {
            $result = $this->members->setVisibilty($this->user_id, $cmd);
        }

        if ($result) {
            PageLayout::postMessage(MessageBox::success(_('Ihre Sichtbarkeit ist erfolgreich ge�ndert worden')));
        } else {
            PageLayout::postMessage(MessageBox::error(_('Leider ist beim �ndern der 
                Sichtbarkeit ein Fehler aufgetreten')));
        }
        $this->redirect('course/members?cid=' . Request::get('cid'));
    }
    
    /** 
     * Helper function to select the action
     * @param int $page
     * @throws AccessDeniedException
     */
    function edit_tutor_action($page) {
        if (!$this->rechte) {
            throw new AccessDeniedException('Sie haben keine ausreichende Berechtigung, 
                um auf diesen Teil des Systems zuzugreifen');
        }

        $this->flash['users'] = Request::getArray('tutor');

        // select the additional method
        switch (Request::get('action_tutor')) {
            case '':
                $this->redirect(sprintf('course/members/index/%s', $page));
                break;
            case 'downgrade':
                $this->redirect(sprintf('course/members/downgrade_user/tutor/autor/%s', $page));
                break;
            case 'remove':
                $this->redirect(sprintf('course/members/cancel_subscription/collection/tutor/%s', $page));
                break;
            default:
                $this->redirect('course/members/index');
                break;
        }
    }
    
    /** 
     * Helper function to select the action
     * @param int $page
     * @throws AccessDeniedException
     */
    function edit_autor_action($page) {
        if (!$this->rechte) {
            throw new AccessDeniedException('Sie haben keine ausreichende Berechtigung, 
                um auf diesen Teil des Systems zuzugreifen');
        }

        $this->flash['users'] = Request::getArray('autor');

        switch (Request::get('action_autor')) {
            case '':
                $this->redirect(sprintf('course/members/index/%s', $page));
                break;
            case 'upgrade':
                $this->redirect(sprintf('course/members/upgrade_user/autor/tutor/%s', $page));
                break;
            case 'downgrade':
                $this->redirect(sprintf('course/members/downgrade_user/autor/user/%s', $page));
                break;
            case 'to_admission':
                // TODO Warteliste setzen
                break;
            case 'remove':
                $this->redirect(sprintf('course/members/cancel_subscription/collection/autor/%s', $page));
                break;
            default:
                $this->redirect('course/members/index');
                break;
        }
    }
    
    /** 
     * Helper function to select the action
     * @param int $page
     * @throws AccessDeniedException
     */
    function edit_user_action($page) {
        if (!$this->rechte) {
            throw new AccessDeniedException('Sie haben keine ausreichende Berechtigung, 
                um auf diesen Teil des Systems zuzugreifen');
        }

        $this->flash['users'] = Request::getArray('user');
        $this->flash['consider_contingent'] = Request::get('consider_contingent');

        // select the additional method
        switch (Request::get('action_user')) {
            case '':
                $this->redirect(sprintf('course/members/index/%s', $page));
                break;
            case 'upgrade':
                $this->redirect(sprintf('course/members/upgrade_user/user/autor/%s', $page));
                break;
            case 'remove':
                $this->redirect(sprintf('course/members/cancel_subscription/collection/user/%s', $page));
                break;
            default:
                $this->redirect('course/members/index');
                break;
        }
    }
    
    /** 
     * Helper function to select the action
     * @param int $page
     * @throws AccessDeniedException
     */
    function edit_awaiting_action($page) {
        if (!$this->rechte) {
            throw new AccessDeniedException('Sie haben keine ausreichende Berechtigung, 
                um auf diesen Teil des Systems zuzugreifen');
        }

        $this->flash['users'] = Request::getArray('awaiting');
        $this->flash['consider_contingent'] = Request::get('consider_contingent');

        // select the additional method
        switch (Request::get('action_awaiting')) {
            case '':
                $this->redirect(sprintf('course/members/index/%s', $page));
                break;
            case 'upgrade':
                $this->redirect(sprintf('course/members/insert_admission/awaiting/%s', $page));
                break;
            case 'remove':
                $this->redirect(sprintf('course/members/cancel_subscription/collection/user/%s', $page));
                break;
            default:
                $this->redirect('course/members/index');
                break;
        }
    }

    /** 
     * Helper function to select the action
     * @param int $page
     * @throws AccessDeniedException
     */
    function edit_accepted_action($page) {
        if (!$this->rechte) {
            throw new AccessDeniedException('Sie haben keine ausreichende Berechtigung, 
                um auf diesen Teil des Systems zuzugreifen');
        }

        $this->flash['users'] = Request::getArray('accepted');
        $this->flash['consider_contingent'] = Request::get('consider_contingent');


        // select the additional method
        switch (Request::get('action_accepted')) {
            case '':
                $this->redirect(sprintf('course/members/index/%s', $page));
                break;
            case 'upgrade':
                $this->redirect(sprintf('course/members/insert_admission/accepted/%s', $page));
                break;
            case 'remove':
                $this->redirect(sprintf('course/members/cancel_subscription/collection/accepted/%s', $page));
                break;
            default:
                $this->redirect('course/members/index');
                break;
        }
    }
    
    /**
     * Insert a user to a given seminar or a group of users
     * @param String $status
     * @param String $cmd
     * @param String $user_id
     * @return String
     * @throws AccessDeniedException
     */
    function insert_admission_action($status, $cmd, $user_id) {
        if (!$this->rechte) {
            throw new AccessDeniedException('Sie haben keine ausreichende Berechtigung, 
                um auf diesen Teil des Systems zuzugreifen');
        }

        if (isset($this->flash['consider_contingent'])) {
            Request::set('consider_contingent', $this->flash['consider_contingent']);
        }

        if ($cmd == "singleuser") {
            $users = array($user_id);
        } else {
            // create a usable array
            $users = array_filter($this->flash['users'], function ($user) {
                return $user;
            });
        }


        if ($users) {
            $msgs = $this->members->insertAdmissionMember($users, 'autor', Request::get('consider_contingent'));

            if ($msgs) {
                if ($cmd == 'add_user') {
                    $message = sprintf(_('NutzerIn %s wurde in die Veranstaltung mit dem Status <b>%s</b> eingetragen.'), 
                            htmlReady(join(',', $msgs)), $this->decoratedStatusGroups['autor']);
                } else {
                    if ($status == 'awaiting') {
                        $message = sprintf(_('NutzerIn %s wurde aus der Anmelde bzw. Warteliste mit dem Status 
                            <b>%s</b> in die Veranstaltung eingetragen.'), 
                                htmlReady(join(', ', $msgs)), $this->decoratedStatusGroups['autor']);
                    } else {
                        $message = sprintf(_('NutzerIn %s wurde mit dem Status <b>%s</b> endg�ltig akzeptiert 
                            und damit in die Veranstaltung aufgenommen.'),
                                htmlReady(join(', ', $msgs)), $this->decoratedStatusGroups['autor']);
                    }
                }

                PageLayout::postMessage(MessageBox::success($message));
            } else {
                $message = _("Es stehen keine weiteren Pl�tze mehr im Teilnehmerkontingent zur Verf�gung.");
                PageLayout::postMessage(MessageBox::error($message));
            }
        } else {
            PageLayout::postMessage(MessageBox::error(_('Sie haben keine Nutzer zum Bef�rdern ausgew�hlt')));
        }
        
        $this->redirect('course/members/index');
    }

    /**
     * Cancel the subscription of a selected user or group of users
     * @global Boolean $rechte
     * @param String $cmd
     * @param String $status
     * @param String $page
     * @param String $user_id
     * @throws AccessDeniedException
     */
    function cancel_subscription_action($cmd, $status, $page, $user_id = null) {
        global $rechte;
        if (!$rechte) {
            throw new AccessDeniedException('Sie haben keine ausreichende Berechtigung, 
                um auf diesen Teil des Systems zuzugreifen');
        }

        if ($cmd == "singleuser") {
            $users = array($user_id);
        } else {
            // create a usable array
            foreach ($this->flash['users'] as $user => $val) {
                if ($val) {
                    $users[] = $user;
                }
            }
        }

        if (!empty($users)) {
            $msgs = $this->members->cancelSubscription($users);
            // deleted authors
            if (!empty($msgs)) {
                if (count($msgs) <= 5) {
                    PageLayout::postMessage(MessageBox::success(sprintf(_("%s %s wurde aus der Veranstaltung entfernt."), 
                            htmlReady($this->status_groups[$status]), htmlReady(join(', ', $msgs)))));
                } else {
                    PageLayout::postMessage(MessageBox::success(sprintf(_("%u %s wurden aus der Veranstaltung entfernt."), count($msgs), 
                            htmlReady($this->status_groups[$status]))));
                }
            }
        } else {
            PageLayout::postMessage(MessageBox::error(sprintf(_('Sie haben keine %s zum austragen ausgew�hlt')), 
                    $this->status_groups[$status]));
        }

        $this->redirect(sprintf('course/members/index/%s', $page));
    }

    /**
     * Upgrade a user to a selected status
     * @param type $status
     * @param type $next_status
     * @param type $username
     * @param type $cmd
     * @throws AccessDeniedException
     */
    function upgrade_user_action($status, $next_status, $page) {
        global $perm, $rechte;

        // Security Check
        if (!$rechte) {
            throw new AccessDeniedException('Sie haben keine ausreichende Berechtigung, 
                um auf diesen Teil des Systems zuzugreifen');
        }

        if ($rechte && $perm->have_studip_perm('tutor', $this->course_id) && $next_status != 'autor' && !$perm->have_studip_perm('dozent', $this->course_id)) {
            throw new AccessDeniedException('Sie haben keine ausreichende Berechtigung, 
                um auf diesen Teil des Systems zuzugreifen');
        }

        // create a usable array
        // TODO: arrayFilter
        foreach ($this->flash['users'] as $user => $val) {
            if ($val) {
                $users[] = $user;
            }
        }

        if (!empty($users)) {
            // insert admission user to autorlist
            $msgs = $this->members->setMemberStatus($users, $status, $next_status, 'upgrade');

            if ($msgs) {
                PageLayout::postMessage(MessageBox::success(sprintf(_('Bef&ouml;rderung auf den Status  %s von %s 
                    wurde erfolgreich durchgef&uuml;hrt'), htmlReady($this->decoratedStatusGroups[$next_status]), 
                        htmlReady(join(', ', $msgs)))));
            }
        } else {
            PageLayout::postMessage(MessageBox::error(sprintf(_('Sie haben keine %s zum Bef�rdern ausgew�hlt'), 
                    htmlReady($this->status_groups[$status]))));
        }

        $this->redirect(sprintf('course/members/index/%s', $page));
    }

    /**
     * Downgrade a user to a selected status
     * @param type $status
     * @param type $next_status
     * @param type $username
     * @param type $cmd
     * @throws AccessDeniedException
     */
    function downgrade_user_action($status, $next_status, $page) {
        global $perm;
        // Security Check
        if (!$this->rechte) {
            throw new AccessDeniedException('Sie haben keine ausreichende Berechtigung, 
                um auf diesen Teil des Systems zuzugreifen');
        }

        if ($this->rechte && $perm->have_studip_perm('tutor', $this->course_id) 
                && $next_status != 'user' && !$perm->have_studip_perm('dozent', $this->course_id)) {
            throw new AccessDeniedException('Sie haben keine ausreichende Berechtigung, 
                um auf diesen Teil des Systems zuzugreifen');
        }


        // create a usable array
        // TODO: arrayFilter
        foreach ($this->flash['users'] as $user => $val) {
            if ($val) {
                $users[] = $user;
            }
        }

        if (!empty($users)) {
            $msgs = $this->members->setMemberStatus($users, $status, $next_status, 'downgrade');

            if ($msgs) {
                PageLayout::postMessage(MessageBox::success(sprintf(_('Der/die %s %s wurde auf den 
                    Status %s zur&uuml;ckgestuft'), htmlReady($this->decoratedStatusGroups[$status]), 
                        htmlReady(join(', ', $msgs)), $this->decoratedStatusGroups[$next_status])));
            }
        } else {
            PageLayout::postMessage(MessageBox::error(sprintf(_('Sie haben keine %s zum Herabstufen ausgew�hlt'), 
                    htmlReady($this->status_groups[$status]))));
        }

        $this->redirect(sprintf('course/members/index/%s', $page));
    }

    
    /**
     * Creates a String for the waitinglist
     * @return String
     */
    private function getTitleForAwaiting() {
        $sem = Seminar::GetInstance($this->course_id);
        return ($sem->admission_type == 2 || $sem->admission_selection_take_place == 1) ? 
        _("Warteliste") : _("Anmeldeliste");
    }
}