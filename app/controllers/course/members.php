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
require_once 'lib/messaging.inc.php'; //Funktionen des Nachrichtensystems

require_once 'lib/admission.inc.php'; //Funktionen der Teilnehmerbegrenzung
require_once 'lib/functions.php'; //Funktionen der Teilnehmerbegrenzung
require_once 'lib/language.inc.php'; //Funktionen der Teilnehmerbegrenzung
require_once 'lib/export/export_studipdata_func.inc.php'; // Funktionne f�r den Export

class Course_MembersController extends AuthenticatedController
{

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        global $perm;

        $this->course_id = $_SESSION['SessSemName'][1];
        $this->course_title = $_SESSION['SessSemName'][0];
        $this->header_line = $_SESSION['SessSemName']['header_line'];
        $this->user_id = $GLOBALS['auth']->auth['uid'];


        // Check dozent-perms
        if ($perm->have_studip_perm('dozent', $this->course_id)) {
            $this->is_dozent = true;
        }

        // Check tutor-perms
        if ($perm->have_studip_perm('tutor', $this->course_id)) {
            $this->is_tutor = true;
        }

        // Check autor-perms
        if ($perm->have_studip_perm('autor', $this->course_id)) {
            $this->is_autor = true;
        }


        if ($this->is_tutor) {
            PageLayout::setHelpKeyword("Basis.VeranstaltungenVerwaltenTeilnehmer");
        } else {
            PageLayout::setHelpKeyword("Basis.InVeranstaltungTeilnehmer");
        }

        // Check lock rules
        $this->dozent_is_locked = LockRules::Check($this->course_id, 'dozent');
        $this->tutor_is_locked = LockRules::Check($this->course_id, 'tutor');
        $this->is_locked = LockRules::Check($this->course_id, 'participants');


        // Layoutsettings
        PageLayout::setTitle(sprintf('%s - %s', $this->header_line, _("TeilnehmerInnen")));

        SkipLinks::addIndex(Navigation::getItem('/course/members')->getTitle(), 'main_content', 100);

        Navigation::activateItem('/course/members');
        Navigation::activateItem('/course/members/view');

        if (Request::isXhr()) {
            $this->set_layout(null);
        } else {
            $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));
        }

        checkObject();
        checkObjectModule("participants");
        object_set_visit_module('participants');
        $this->last_visitdate = object_get_visit($this->course_id, 'participants');

        // Check perms and set the last visit date
        if (!$this->is_tutor) {
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
        $this->members->checkUserVisibility();
    }

    function index_action()
    {
        global $perm, $PATH_EXPORT;
        $sem = Seminar::getInstance($this->course_id);

        // old message style
        if ($_SESSION['sms_msg']) {
            $this->msg = $_SESSION['sms_msg'];
            unset($_SESSION['sms_msg']);
        }

        $this->sort_by = Request::option('sortby', 'nachname');
        $this->order = Request::option('order', 'desc');
        $this->sort_status = Request::get('sort_status');

        if (Request::int('toggle')) {
            $this->order = $this->order == 'desc' ? 'asc' : 'desc';
        }

        $filtered_members = $this->members->getMembers($this->sort_status, $this->sort_by . ' ' . $this->order, !$this->is_tutor ? $this->user_id : null);

        if ($this->is_tutor) {
            $filtered_members = array_merge($filtered_members, $this->members->getAdmissionMembers($this->sort_status, $this->sort_by . ' ' . $this->order ));
            $this->awaiting = $filtered_members['awaiting']->toArray('user_id username vorname nachname visible kontingent mkdate');
            $this->accepted = $filtered_members['accepted']->toArray('user_id username vorname nachname visible kontingent mkdate');
        }

        // Check autor-perms
        if (!$this->is_tutor) {
            SkipLinks::addIndex(_("Sichtbarkeit �ndern"), 'change_visibility');
            // filter invisible user
            $this->invisibles = count($filtered_members['autor']->findBy('visible', 'no')) + count($filtered_members['user']->findBy('visible', 'no'));
            $current_user_id = $this->user_id;
            $exclude_invisibles =
                function ($user) use ($current_user_id) {
                        return ($user['visible'] != 'no' || $user['user_id'] == $current_user_id);
                    };
            $filtered_members['autor'] = $filtered_members['autor']->filter($exclude_invisibles);
            $filtered_members['user'] = $filtered_members['user']->filter($exclude_invisibles);
            $this->my_visibility = $this->getUserVisibility();
            if (!$this->my_visibility['iam_visible']) {
                $this->invisibles--;
            }
        }

        // get member informations
        $this->dozenten = $filtered_members['dozent']->toArray('user_id username vorname nachname');
        $this->tutoren = $filtered_members['tutor']->toArray('user_id username vorname nachname mkdate');
        $this->autoren = $filtered_members['autor']->toArray('user_id username vorname nachname visible kontingent mkdate');
        $this->users = $filtered_members['user']->toArray('user_id username vorname nachname visible kontingent mkdate');
        $this->studipticket = Seminar_Session::get_ticket();
        $this->subject = $this->getSubject();
        $this->groups = $this->status_groups;
        $this->waitingTitle = $this->getTitleForAwaiting();
        // Check Seminar
        if ($this->is_tutor && $sem->isAdmissionEnabled()) {
            $this->semAdmissionEnabled = true;
            $this->course = $sem;
            $this->count = $this->members->getCountedMembers();
        }
        // Set the infobox
        $this->setInfoBoxImage('infobox/groups.jpg');
        if ($this->is_tutor) {
            if ($this->is_dozent) {
                if (!$this->dozent_is_locked) {
                    $url = sprintf('<a href="%s">%s</a>', $this->url_for('course/members/add_dozent/'), sprintf(_('Neue/n %s eintragen'), $this->status_groups['dozent']));
                    $this->addToInfobox(_('Aktionen'), $url, 'icons/16/black/add/community.png');
                }

                if (!$this->tutor_is_locked) {
                    $url = sprintf('<a href="%s">%s</a>', $this->url_for('course/members/add_tutor/'), sprintf(_('Neue/n %s eintragen'), $this->status_groups['tutor']));
                    $this->addToInfobox(_('Aktionen'), $url, 'icons/16/black/add/community.png');
                }
            }
            if (!$this->is_locked) {
                $url = sprintf('<a href="%s">%s</a>', $this->url_for('course/members/add_member/'), sprintf(_('Neue/n %s eintragen'), $this->status_groups['autor']));
                $this->addToInfobox(_('Aktionen'), $url, 'icons/16/black/add/community.png');
            }
            $link = sprintf('<a href="%s">%s</a>', URLHelper::getLink('sms_send.php', array('sms_source_page' => 'dispatch.php/course/members',
                        'course_id' => $this->course_id,
                        'subject' => $this->subject,
                        'filter' => 'all',
                        'emailrequest' => 1)), _('Nachricht an alle (Rundmail)'));
            $this->addToInfobox(_('Aktionen'), $link, 'icons/16/black/inbox.png');

            if (get_config('EXPORT_ENABLE')) {
                include_once($PATH_EXPORT . "/export_linking_func.inc.php");

                // create csv-export link
                $csvExport = export_link($this->course_id, "person", sprintf('%s %s', htmlReady($this->status_groups['autor']), htmlReady($this->course_title)), 'csv', 'csv-teiln', '', _('TeilnehmerInnen-Liste als csv-Dokument exportieren'), 'passthrough');
                // create csv-export link
                $rtfExport = export_link($this->course_id, "person", sprintf('%s %s', htmlReady($this->status_groups['autor']), htmlReady($this->course_title)), 'rtf', 'rtf-teiln', '', _('TeilnehmerInnen-Liste als rtf-Dokument exportieren'), 'passthrough');
                $this->addToInfobox(_('Aktionen'), $csvExport, 'icons/16/black/export/file-office.png');
                $this->addToInfobox(_('Aktionen'), $rtfExport, 'icons/16/black/export/file-text.png');

                if (count($this->awaiting) > 0) {
                    $awaiting_rtf = export_link($this->course_id, "person", sprintf('%s %s', _("Warteliste"), htmlReady($this->course_title)), "rtf", "rtf-warteliste", "awaiting", _("Warteliste als rtf-Dokument exportieren"), 'passthrough');
                    $awaiting_csv = export_link($this->course_id, "person", sprintf('%s %s', _("Warteliste"), htmlReady($this->course_title)), "csv", "csv-warteliste", "awaiting", _("Warteliste als csv-Dokument exportieren"), 'passthrough');

                    $this->addToInfobox(_('Aktionen'), $awaiting_csv, 'icons/16/black/export/file-office.png');
                    $this->addToInfobox(_('Aktionen'), $awaiting_rtf, 'icons/16/black/export/file-text.png');
                }
            }
        } elseif (!$this->is_tutor) {
            // Visibility preferences
            if (!$this->my_visibility['iam_visible']) {
                $text = _('Sie sind f�r andere TeilnehmerInnen auf der TeilnehmerInnen-Liste nicht sichtbar.');
                $icon = 'icons/16/black/visibility-visible.png';
                $modus = 'make_visible';
                $link_text = _('Klicken Sie hier, um sichtbar zu werden.');
            } else {
                $text = _('Sie sind f�r andere TeilnehmerInnen auf der TeilnehmerInnen-Liste sichtbar.');
                $icon = 'icons/16/black/visibility-invisible.png';
                $modus = 'make_invisible';
                $link_text = _('Klicken Sie hier, um unsichtbar zu werden.');
            }

            $link = sprintf('<a href="%s">%s</a>', $this->url_for(sprintf('course/members/change_visibility/%s/%s', $modus, $this->my_visibility['visible_mode'])), $link_text);
            $this->addToInfobox(_('Sichtbarkeit'), $text, 'icons/16/black/info.png');
            $this->addToInfobox(_('Sichtbarkeit'), $link, $icon);
        }
        if ($this->is_locked && $this->is_tutor) {
            $lockdata = LockRules::getObjectRule($this->course_id);
            if ($lockdata['description']) {
                PageLayout::postMessage(MessageBox::info(formatLinks($lockdata['description'])));
            }
        }
    }

    /*
     * Returns an array with emails of members
     */

    public function getEmailLinkByStatus($status, $members)
    {
        if (!get_config('ENABLE_EMAIL_TO_STATUSGROUP')) {
            return;
        }

        if ($status == 'accepted' || $status == 'awaiting') {
            $textStatus = 'NutzerInnen';
        } else {
            $textStatus = $this->status_groups[$status];
        }

        $results = SimpleCollection::createFromArray($members)->pluck('email');

        if (!empty($results)) {
            return sprintf('<a href="mailto:%s">%s</a>', htmlReady(join(',', $results)), Assets::img('icons/16/blue/move_right/mail.png', tooltip2(sprintf('E-Mail an alle %s versenden', $textStatus))));
        } else {
            return null;
        }
    }

    /**
     * New dozent action.
     * @throws AccessDeniedException
     */
    function add_dozent_action()
    {
        // Security Check
        if (!$this->is_dozent || $this->dozent_is_locked) {
            throw new AccessDeniedException('Sie haben leider keine ausreichende Berechtigung, um auf diesen Bereich von Stud.IP zuzugreifen.');
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
                $search_template, sprintf(_("%s suchen"), get_title_for_status('dozent', 1, $sem->status)), "user_id", array('permission' => 'dozent',
            'seminar_id' => $this->course_id,
            'sem_perm' => 'dozent',
            'institute' => $sem_institutes)
        );
    }

    /**
     * New tutor action
     * @throws AccessDeniedException
     */
    function add_tutor_action()
    {
        if (!$this->is_dozent || $this->is_tutor_locked) {
            throw new AccessDeniedException('Sie haben leider keine ausreichende Berechtigung, um auf diesen Bereich von Stud.IP zuzugreifen.');
        }
        $sem = Seminar::GetInstance($this->course_id);
        Request::set('new_tutor_parameter', $this->flash['new_tutor_parameter']);

        $sem_institutes = $sem->getInstitutes();

        if (SeminarCategories::getByTypeId($sem->status)->only_inst_user) {
            $search_template = 'user_inst_not_already_in_sem';
        } else {
            $search_template = 'user_not_already_in_sem';
        }

        $this->search = new PermissionSearch(
                $search_template, sprintf(_('%s suchen'), get_title_for_status('tutor', 1, $sem->status)), 'user_id', array('permission' => array('dozent', 'tutor'),
            'seminar_id' => $this->course_id,
            'sem_perm' => array('dozent', 'tutor'),
            'institute' => $sem_institutes)
        );
    }

    /**
     * New author action
     * @global Object $perm
     * @throws AccessDeniedException
     */
    function add_member_action()
    {
        global $perm;
        $this->set_layout($GLOBALS['template_factory']->open('layouts/base_without_infobox'));
        // get the seminar object
        $sem = Seminar::GetInstance($this->course_id);
        $sem->restoreAdmissionStudiengang();

        // Security Check
        if (!$this->is_tutor) {
            throw new AccessDeniedException('Sie haben leider keine ausreichende Berechtigung, um auf diesen Bereich von Stud.IP zuzugreifen.');
        }

        // Check Seminar
        if ($this->is_tutor && $sem->isAdmissionEnabled()) {
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
        $this->search = new SQLSearch("SELECT auth_user_md5.user_id, CONCAT(" . $GLOBALS['_fullname_sql']['full'] .
                ", \" (\", auth_user_md5.username, \")\") as fullname " .
                "FROM auth_user_md5 " .
                "LEFT JOIN user_info ON (user_info.user_id = auth_user_md5.user_id) " .
                "WHERE (CONCAT(auth_user_md5.Vorname, \" \", auth_user_md5.Nachname) LIKE :input " .
                "OR auth_user_md5.username LIKE :input) " .
                "AND auth_user_md5.perms IN ('autor', 'tutor', 'dozent') " .
                "AND auth_user_md5.user_id NOT IN (SELECT user_id FROM seminar_user WHERE Seminar_id = :cid ) " .
                "ORDER BY Vorname, Nachname", _("Teilnehmer suchen"), "username");

        $datafields = DataFieldStructure::getDataFieldStructures('user', (1 | 2 | 4 | 8), true);
        foreach ($datafields as $df) {
            if ($df->accessAllowed($perm) && in_array($df->getId(), $GLOBALS['TEILNEHMER_IMPORT_DATAFIELDS'])) {
                $accessible_df[] = $df;
            }
        }
        $this->accessible_df = $accessible_df;
    }

    /**
     * Add a member to a seminar
     * @throws AccessDeniedException
     */
    function set_action()
    {
        // Security Check
        if (!$this->is_tutor) {
            throw new AccessDeniedException('Sie haben leider keine ausreichende Berechtigung, um auf diesen Bereich von Stud.IP zuzugreifen.');
        }
        CSRFProtection::verifyUnsafeRequest();
        $sem = Seminar::GetInstance($this->course_id);
        // insert new dozent in a seminar
        if (Request::submitted('add_dozent') && $this->is_dozent) {

            if (!Request::option('new_dozent')) {
                PageLayout::postMessage(MessageBox::error(_('Es wurde niemand gefunden.')));

                $this->redirect('course/members/add_dozent');
            } else {
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
                    PageLayout::postMessage(MessageBox::success(sprintf(_('%s wurde hinzugef�gt.'), get_title_for_status('dozent', 1, $sem->status))));
                    // go back
                    $this->redirect('course/members/index');
                } else {
                    // sorry that was a fail
                    PageLayout::postMessage(MessageBox::error(_('Die gew�nschte Operation konnte nicht ausgef�hrt werden.')));
                    // go back
                    $this->redirect('course/members/add_dozent');
                }
            }
        }

        // empty dozent formular
        if (Request::submitted('search_dozent') && Request::submitted('search_dozent_x')) {
            $this->flash['new_dozent_parameter'] = Request::get('new_dozent_parameter');
            $this->redirect('course/members/add_dozent');
        }

        //insert new tutor
        if (Request::submitted('add_tutor') && $this->is_dozent) {

            // selection fails
            if (!Request::option('new_tutor')) {
                PageLayout::postMessage(MessageBox::error(_('Sie haben keine Auswahl get�tigt
                    oder der/die gesuchte TeilnehmerIn wurde nicht gefunden')));
                $this->redirect('course/members/add_tutor');
            } else {
                if ($sem->addMember(Request::option('new_tutor'), "tutor")) {
                    PageLayout::postMessage(MessageBox::success(sprintf(_('%s wurde hinzugef�gt.'), get_title_for_status('tutor', 1, $sem->status))));
                    $this->redirect('course/members/index');
                } else {
                    // sorry that was a fail
                    PageLayout::postMessage(MessageBox::error(_('Die gew�nsche Operation konnte nicht ausgef�hrt werden')));

                    $this->redirect('course/members/add_tutor');
                }
            }
        }

        // empty tutor formular
        if (Request::submitted('search_tutor')) {
            $this->flash['new_tutor_parameter'] = Request::get('new_tutor_parameter');
            $this->redirect('course/members/add_tutor');
        }

        if (Request::submitted('reset_dozent')) {
            $this->redirect('course/members/add_dozent');
        }

        if (Request::submitted('reset_tutor')) {
            $this->redirect('course/members/add_tutor');
        }
    }

    /**
     * Add a author to a seminar
     * @throws AccessDeniedException
     */
    function set_autor_action()
    {
        // Security Check
        if (!$this->is_tutor) {
            throw new AccessDeniedException('Sie haben leider keine ausreichende Berechtigung, um auf diesen Bereich von Stud.IP zuzugreifen.');
        }
        CSRFProtection::verifyUnsafeRequest();
        // empty autor formular
        if (Request::submitted('search_autor') && Request::submitted('search_autor_x')) {
            $this->flash['new_autor'] = Request::get('new_autor');
            $this->flash['new_autor_1'] = Request::get('new_autor_1');
            $this->flash['new_autor_parameter'] = Request::get('new_autor_parameter');
            $this->flash['consider_contingent'] = Request::get('consider_contingent');

            $this->redirect('course/members/add_member');
            return;
        }

        if (Request::submitted('reset_autor') && Request::submitted('reset_autor_x')) {
            $this->redirect('course/members/add_member');
            return;
        }
        //insert new autor
        if (Request::option('new_autor') && (Request::submitted('add_autor_x')
                || (Request::submitted('add_autor')) && $this->is_tutor)) {

            $msg = $this->members->addMember(Request::get('new_autor'), 'autor', Request::get('consider_contingent'));

            PageLayout::postMessage($msg);
            $this->redirect('course/members/index');
            return;
        } else {
            PageLayout::postMessage(MessageBox::error(_('Sie haben keine Auswahl get�tigt
                oder der/die gesuchte TeilnehmerIn wurde nicht gefunden')));
            $this->redirect('course/members/add_member');
            return;
        }
    }


    /**
     * Send Stud.IP-Message to selected users
     */
    function send_message_action()
    {
        if (!empty($this->flash['users'])) {
            // create a usable array
            foreach ($this->flash['users'] as $user => $val) {
                if ($val) {
                    $users[] = UserModel::getUser($user, 'username');
                }
            }
            $_SESSION['sms_data']['p_rec'] = $users;
            $this->redirect(URLHelper::getURL('sms_send.php', array('sms_source_page' => 'dispatch.php/course/members/index', 'messagesubject' => $this->getSubject(), 'tmpsavesnd' => 1)));
        } else {
            $this->redirect('course/members/index');
        }
    }

    /**
     * Old version of CSV import (copy and paste from teilnehmer.php
     * @global Object $perm
     * @return type
     * @throws AccessDeniedException
     */
    function set_autor_csv_action()
    {
        global $perm;
        // Security Check
        if (!$this->is_tutor) {
            throw new AccessDeniedException('Sie haben leider keine ausreichende Berechtigung, um auf diesen Bereich von Stud.IP zuzugreifen.');
        }
        CSRFProtection::verifyUnsafeRequest();

        // prepare CSV-Lines
        $messaging = new messaging();
        $csv_request = preg_split('/(\n\r|\r\n|\n|\r)/', trim(Request::get('csv_import')));
        $csv_mult_founds = array();
        $csv_count_insert = 0;
        $csv_count_multiple = 0;
        $datafield_id = null;

        if (Request::get('csv_import_format') && !in_array(Request::get('csv_import_format'), words('realname username'))) {
            foreach (DataFieldStructure::getDataFieldStructures('user', (1 | 2 | 4 | 8), true) as $df) {
                if ($df->accessAllowed($perm) && in_array($df->getId(), $GLOBALS['TEILNEHMER_IMPORT_DATAFIELDS']) && $df->getId() == Request::quoted('csv_import_format')) {
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

                            $message = sprintf(_('Sie wurden als TeilnehmerIn in die Veranstaltung **%s** eingetragen.'), $this->course_title);

                            restoreLanguage();
                            $messaging->insert_message(mysql_escape_string($message), $row['username'], '____%system%____', FALSE, FALSE, '1', FALSE, sprintf('%s %s', _('Systemnachricht:'), _('Eintragung in Veranstaltung')), TRUE);
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
                    if (insert_seminar_user($this->course_id, get_userid($selected_user), 'autor', isset($consider_contingent), $consider_contingent)) {
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
                        $messaging->insert_message(mysql_escape_string($message), $selected_user, '____%system%____', FALSE, FALSE, '1', FALSE, sprintf('%s %s', _('Systemnachricht:'), _('Eintragung in Veranstaltung')), TRUE);
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
            PageLayout::postMessage(MessageBox::error(sprintf(_('%s konnten <b>nicht</b> zugeordnet werden!'), htmlReady(join(',', $csv_not_found)))));
        }

        if ($csv_count_contingent_full) {
            PageLayout::postMessage(MessageBox::error(sprintf(_('%s NutzerInnen konnten <b>nicht</b> zugeordnet werden,
                da das ausgew�hlte Kontingent keine freien Pl�tze hat.'), $csv_count_contingent_full)));
        }

        $this->redirect('course/members/index');
    }

    /**
     * Select manual the assignment of a given user or of a group of users
     * @global Object $perm
     * @throws AccessDeniedException
     */
    function csv_manual_assignment_action()
    {
        global $perm;
        // Security. If user not autor, then redirect to index
        if (!$perm->have_studip_perm('tutor', $this->course_id)) {
            throw new AccessDeniedException('Sie haben leider keine ausreichende Berechtigung, um auf diesen Bereich von Stud.IP zuzugreifen.');
        }

        if (empty($this->flash['csv_mult_founds'])) {
            $this->redirect('course/members/index');
        }
    }

    /**
     * Change the visibilty of an autor
     * @return Boolean
     */
    function change_visibility_action($cmd, $mode)
    {
        global $perm;
        // Security. If user not autor, then redirect to index
        if ($perm->have_studip_perm('tutor', $this->course_id)) {
            throw new AccessDeniedException('Sie haben keine ausreichende Berechtigung,
                um auf diesen Teil des Systems zuzugreifen');
        }

        // Check for visibile mode
        if ($cmd == 'make_visible') {
            $command = 'yes';
        } else {
            $command = 'no';
        }

        if ($mode == 'awaiting') {
            $result = $this->members->setAdmissionVisibility($this->user_id, $command);
        } else {
            $result = $this->members->setVisibilty($this->user_id, $command);
        }

        if ($result > 0) {
            PageLayout::postMessage(MessageBox::success(_('Ihre Sichtbarkeit wurde erfolgreich ge�ndert.')));
        } else {
            PageLayout::postMessage(MessageBox::error(_('Leider ist beim �ndern der Sichtbarkeit ein Fehler aufgetreten. Die Einstellung konnte nicht vorgenommen werden.')));
        }
        $this->redirect('course/members');
    }

    /**
     * Helper function to select the action
     * @throws AccessDeniedException
     */
    function edit_tutor_action()
    {
        if (!$this->is_tutor) {
            throw new AccessDeniedException('Sie haben leider keine ausreichende Berechtigung, um auf diesen Bereich von Stud.IP zuzugreifen.');
        }
        CSRFProtection::verifyUnsafeRequest();

        $this->flash['users'] = Request::getArray('tutor');

        // select the additional method
        switch (Request::get('action_tutor')) {
            case '':
                $this->redirect('course/members/index');
                break;
            case 'downgrade':
                $this->redirect('course/members/downgrade_user/tutor/autor');
                break;
            case 'remove':
                $this->redirect('course/members/cancel_subscription/collection/tutor');
                break;
            case 'message':
                $this->redirect('course/members/send_message');
                break;
            default:
                $this->redirect('course/members/index');
                break;
        }
    }

    /**
     * Helper function to select the action
     * @throws AccessDeniedException
     */
    function edit_autor_action()
    {
        if (!$this->is_tutor) {
            throw new AccessDeniedException('Sie haben leider keine ausreichende Berechtigung, um auf diesen Bereich von Stud.IP zuzugreifen.');
        }
        CSRFProtection::verifyUnsafeRequest();

        $this->flash['users'] = Request::getArray('autor');

        switch (Request::get('action_autor')) {
            case '':
                $this->redirect('course/members/index');
                break;
            case 'upgrade':
                $this->redirect('course/members/upgrade_user/autor/tutor');
                break;
            case 'downgrade':
                $this->redirect('course/members/downgrade_user/autor/user');
                break;
            case 'to_admission':
                // TODO Warteliste setzen
                break;
            case 'remove':
                $this->redirect('course/members/cancel_subscription/collection/autor');
                break;
            case 'message':
                $this->redirect('course/members/send_message');
                break;
            default:
                $this->redirect('course/members/index');
                break;
        }
    }

    /**
     * Helper function to select the action
     * @throws AccessDeniedException
     */
    function edit_user_action()
    {
        if (!$this->is_tutor) {
            throw new AccessDeniedException('Sie haben keine ausreichende Berechtigung,
                um auf diesen Teil des Systems zuzugreifen');
        }
        CSRFProtection::verifyUnsafeRequest();

        $this->flash['users'] = Request::getArray('user');
        $this->flash['consider_contingent'] = Request::get('consider_contingent');

        // select the additional method
        switch (Request::get('action_user')) {
            case '':
                $this->redirect('course/members/index');
                break;
            case 'upgrade':
                $this->redirect('course/members/upgrade_user/user/autor');
                break;
            case 'remove':
                $this->redirect('course/members/cancel_subscription/collection/user');
                break;
            case 'message':
                $this->redirect('course/members/send_message');
                break;
            default:
                $this->redirect('course/members/index');
                break;
        }
    }

    /**
     * Helper function to select the action
     * @throws AccessDeniedException
     */
    function edit_awaiting_action()
    {
        if (!$this->is_tutor) {
            throw new AccessDeniedException('Sie haben leider keine ausreichende Berechtigung, um auf diesen Bereich von Stud.IP zuzugreifen.');
        }
        CSRFProtection::verifyUnsafeRequest();

        $this->flash['users'] = Request::getArray('awaiting');
        $this->flash['consider_contingent'] = Request::get('consider_contingent');

        // select the additional method
        switch (Request::get('action_awaiting')) {
            case '':
                $this->redirect('course/members/index');
                break;
            case 'upgrade':
                $this->redirect('course/members/insert_admission/awaiting/collection');
                break;
            case 'remove':
                $this->redirect('course/members/cancel_subscription/collection/awaiting');
                break;
            case 'message':
                $this->redirect('course/members/send_message');
                break;
            default:
                $this->redirect('course/members/index');
                break;
        }
    }

    /**
     * Helper function to select the action
     * @throws AccessDeniedException
     */
    function edit_accepted_action()
    {
        if (!$this->is_tutor) {
            throw new AccessDeniedException('Sie haben keine ausreichende Berechtigung,
                um auf diesen Teil des Systems zuzugreifen');
        }
        CSRFProtection::verifyUnsafeRequest();

        $this->flash['users'] = Request::getArray('accepted');
        $this->flash['consider_contingent'] = Request::get('consider_contingent');


        // select the additional method
        switch (Request::get('action_accepted')) {
            case '':
                $this->redirect('course/members/index');
                break;
            case 'upgrade':
                $this->redirect('course/members/insert_admission/accepted/collection');
                break;
            case 'remove':
                $this->redirect('course/members/cancel_subscription/collection/accepted');
                break;
            case 'message':
                $this->redirect('course/members/send_message');
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
    function insert_admission_action($status, $cmd)
    {
        if (!$this->is_tutor) {
            throw new AccessDeniedException('Sie haben leider keine ausreichende Berechtigung, um auf diesen Bereich von Stud.IP zuzugreifen.');
        }

        if (isset($this->flash['consider_contingent'])) {
            Request::set('consider_contingent', $this->flash['consider_contingent']);
        }

        // create a usable array
        $users = array_filter($this->flash['users'], function ($user) {
                        return $user;
                    });

        if ($users) {
            $msgs = $this->members->insertAdmissionMember($users, 'autor', Request::get('consider_contingent'), $status == 'accepted');
            if ($msgs) {
                if ($cmd == 'add_user') {
                    $message = sprintf(_('%s wurde in die Veranstaltung mit dem Status <b>%s</b> eingetragen.'), htmlReady(join(',', $msgs)), $this->decoratedStatusGroups['autor']);
                } else {
                    if ($status == 'awaiting') {
                        $message = sprintf(_('%s wurde aus der Anmelde bzw. Warteliste mit dem Status
                            <b>%s</b> in die Veranstaltung eingetragen.'), htmlReady(join(', ', $msgs)), $this->decoratedStatusGroups['autor']);
                    } else {
                        $message = sprintf(_('%s wurde mit dem Status <b>%s</b> endg�ltig akzeptiert
                            und damit in die Veranstaltung aufgenommen.'), htmlReady(join(', ', $msgs)), $this->decoratedStatusGroups['autor']);
                    }
                }

                PageLayout::postMessage(MessageBox::success($message));
            } else {
                $message = _("Es stehen keine weiteren Pl�tze mehr im Teilnehmerkontingent zur Verf�gung.");
                PageLayout::postMessage(MessageBox::error($message));
            }
        } else {
            PageLayout::postMessage(MessageBox::error(_('Sie haben keine Nutzer zum Hochstufen ausgew�hlt.')));
        }

        $this->redirect('course/members/index');
    }

    /**
     * Cancel the subscription of a selected user or group of users
     * @param String $cmd
     * @param String $status
     * @param String $user_id
     * @throws AccessDeniedException
     */
    function cancel_subscription_action($cmd, $status, $user_id = null)
    {
        if (!$this->is_tutor) {
            throw new AccessDeniedException('Sie haben keine ausreichende Berechtigung,
                um auf diesen Teil des Systems zuzugreifen');
        }

        if (!Request::submitted('no')) {

            if (Request::submitted('yes')) {
                CSRFProtection::verifyUnsafeRequest();
                $users = Request::getArray('users');
                if (!empty($users)) {
                    if ($status == 'accepted' || $status == 'awaiting') {
                        $msgs = $this->members->cancelAdmissionSubscription($users, $status);
                    } else {
                        $msgs = $this->members->cancelSubscription($users);
                    }

                    // deleted authors
                    if (!empty($msgs)) {
                        if (count($msgs) <= 5) {
                            PageLayout::postMessage(MessageBox::success(sprintf(_("%s %s wurde aus der Veranstaltung ausgetragen."), htmlReady($this->status_groups[$status]), htmlReady(join(', ', $msgs)))));
                        } else {
                            PageLayout::postMessage(MessageBox::success(sprintf(_("%u %s wurden aus der Veranstaltung entfernt."), count($msgs), htmlReady($this->status_groups[$status]))));
                        }
                    }
                } else {
                    PageLayout::postMessage(MessageBox::error(sprintf(_('Sie haben keine %s zum Austragen ausgew�hlt')), $this->status_groups[$status]));
                }
            } else {
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
                $this->flash['status'] = $status;
                $this->flash['delete'] = $users;
            }
        }



        $this->redirect('course/members/index');
    }

    /**
     * Upgrade a user to a selected status
     * @param type $status
     * @param type $next_status
     * @param type $username
     * @param type $cmd
     * @throws AccessDeniedException
     */
    function upgrade_user_action($status, $next_status)
    {
        global $perm;

        // Security Check
        if (!$this->is_tutor) {
            throw new AccessDeniedException('Sie haben leider keine ausreichende Berechtigung, um auf diesen Bereich von Stud.IP zuzugreifen.');
        }

        if ($this->is_tutor && $perm->have_studip_perm('tutor', $this->course_id) && $next_status != 'autor' && !$perm->have_studip_perm('dozent', $this->course_id)) {
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

            if ($msgs['success']) {
                PageLayout::postMessage(MessageBox::success(sprintf(_('Das Hochstufen auf den Status  %s von %s
                    wurde erfolgreich durchgef&uuml;hrt'), htmlReady($this->decoratedStatusGroups[$next_status]), htmlReady(join(', ', $msgs['success'])))));
            }

            if ($msgs['no_tutor']) {
                PageLayout::postMessage(MessageBox::error(sprintf(_('Das Hochstufen auf den Status  %s von %s
                   konnte wegen fehlender Rechte nicht durchgef&uuml;hrt werden.'), htmlReady($this->decoratedStatusGroups[$next_status]), htmlReady(join(', ', $msgs['no_tutor'])))));
            }
        } else {
            PageLayout::postMessage(MessageBox::error(sprintf(_('Sie haben keine %s zum Hochstufen ausgew�hlt'), htmlReady($this->status_groups[$status]))));
        }

        $this->redirect('course/members/index');
    }

    /**
     * Downgrade a user to a selected status
     * @param type $status
     * @param type $next_status
     * @param type $username
     * @param type $cmd
     * @throws AccessDeniedException
     */
    function downgrade_user_action($status, $next_status)
    {
        // Security Check
        if (!$this->is_tutor) {
            throw new AccessDeniedException('Sie haben keine ausreichende Berechtigung,
                um auf diesen Teil des Systems zuzugreifen');
        }
        // TODO: Check this
        if ($this->is_tutor && $next_status != 'user' && !$this->is_dozent) {
            throw new AccessDeniedException('Sie haben keine ausreichende Berechtigung,
                um auf diesen Teil des Systems zuzugreifen');
        }

        foreach ($this->flash['users'] as $user => $val) {
            if ($val) {
                $users[] = $user;
            }
        }

        if (!empty($users)) {
            $msgs = $this->members->setMemberStatus($users, $status, $next_status, 'downgrade');

            if ($msgs['success']) {
                PageLayout::postMessage(MessageBox::success(sprintf(_('Der/die %s %s wurde auf den
                    Status %s heruntergestuft.'), htmlReady($this->decoratedStatusGroups[$status]), htmlReady(join(', ', $msgs['success'])), $this->decoratedStatusGroups[$next_status])));
            }
        } else {
            PageLayout::postMessage(MessageBox::error(sprintf(_('Sie haben keine %s zum Herunterstufen ausgew�hlt'), htmlReady($this->status_groups[$status]))));
        }

        $this->redirect('course/members/index');
    }

    /**
     * Get the visibility of a user in a seminar
     * @param String $user_id
     * @param String $seminar_id
     * @return Array
     */
    private function getUserVisibility()
    {
        $member = CourseMember::find(array($this->course_id, $this->user_id));

        $visibility = $member->visible;
        $status = $member->status;
        #echo "<pre>"; var_dump($member); echo "</pre>";
        $result['visible_mode'] = false;

        if ($visibility) {
            $result['iam_visible'] = ($visibility == 'yes' || $visibility == 'unknown');

            if ($status == 'user' || $status == 'autor') {
                $result['visible_mode'] = 'participant';
            } else {
                $result['iam_visible'] = true;
                $result['visible_mode'] = false;
            }
        }

        return $result;
    }

    /**
     * Creates a String for the waitinglist
     * @return String
     */
    private function getTitleForAwaiting()
    {
        $sem = Seminar::GetInstance($this->course_id);
        return ($sem->admission_type == 2 || $sem->admission_selection_take_place == 1) ?
                _("Warteliste") : _("Anmeldeliste");
    }

    /**
     * Returns the Subject for the Messaging
     * @return String
     */
    private function getSubject()
    {
        $result = Seminar::GetInstance($this->course_id)->getNumber();

        $subject = ($result == '') ? sprintf('[%s]', $this->course_title) :
                sprintf('[%s] : %s', $result, $this->course_title);

        return $subject;
    }

}