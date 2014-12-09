<?php
# Lifter010: TODO
/*
 * AdminNavigation.php - navigation for admin area
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Elmar Ludwig
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

class AdminNavigation extends Navigation
{
    /**
     * Initialize a new Navigation instance.
     */
    public function __construct()
    {
        parent::__construct(_('Admin'));

        $this->setImage('icons/lightblue/admin.svg', array('title' => _('Zu Ihrer Administrationsseite')));
    }

    /**
     * Initialize the subnavigation of this item. This method
     * is called once before the first item is added or removed.
     */
    public function initSubNavigation()
    {
        global $SessionSeminar, $SessSemName, $archive_kill, $perm;

        parent::initSubNavigation();

        if ($SessSemName['class'] == 'inst') {
            if (isset($_SESSION['links_admin_data']['referred_from']) && $_SESSION['links_admin_data']['referred_from'] == 'inst') {
                $back_jump = _('zur�ck zur ausgew�hlten Einrichtung');
            } else {
                $back_jump = _('zur ausgew�hlten Einrichtung');
            }
        } else if ($SessSemName['class'] == 'sem') {
            if (isset($_SESSION['links_admin_data']['referred_from']) && $_SESSION['links_admin_data']['referred_from'] == 'sem' && !$archive_kill && !(isset($_SESSION['links_admin_data']['assi']) && $_SESSION['links_admin_data']['assi'])) {
                $back_jump = _('zur�ck zur ausgew�hlten Veranstaltung');
            } else if (isset($_SESSION['links_admin_data']['referred_from']) && $_SESSION['links_admin_data']['referred_from'] == 'assi' && !$archive_kill) {
                $back_jump = _('zur neu angelegten Veranstaltung');
            } else if (!(isset($_SESSION['links_admin_data']['assi']) && $_SESSION['links_admin_data']['assi'])) {
                $back_jump = _('zur ausgew�hlten Veranstaltung');
            }
        }

        $sem_create_perm = in_array(Config::get()->SEM_CREATE_PERM, array('root', 'admin', 'dozent')) ? Config::get()->SEM_CREATE_PERM : 'dozent';

        // global config / user administration
        if (!Config::get()->RESTRICTED_USER_MANAGEMENT || $perm->have_perm('root')) {
            $navigation = new Navigation(_('Benutzer'));
            $navigation->setURL('dispatch.php/admin/user/');
            $navigation->addSubNavigation('index', new Navigation(_('Benutzer'), 'dispatch.php/admin/user'));

            if ($perm->have_perm('root')) {
                $navigation->addSubNavigation('user_domains', new Navigation(_('Nutzerdom�nen'), 'dispatch.php/admin/domain'));
            }
            $this->addSubNavigation('user', $navigation);
        }


        // course administration
        $navigation = new Navigation(_('Veranstaltungen'), 'adminarea_start.php?list=TRUE');

        if ($SessionSeminar == null) {
            $navigation->addSubNavigation('adminarea_start', new Navigation(_('Veranstaltung ausw�hlen'),
                'adminarea_start.php', array('list' => 'TRUE')));
        }

        $navigation->addSubNavigation('details', new Navigation(_('Grunddaten'),
            'dispatch.php/course/basicdata/view/', array('list' => 'TRUE')));
        $navigation->addSubNavigation('study_areas', new Navigation(_('Studienbereiche'),
            'dispatch.php/course/study_areas/show/', array('list' => 'TRUE')));
        $navigation->addSubNavigation('dates', new Navigation(_('Zeiten / R�ume'), 'raumzeit.php?list=TRUE'));
        if (Config::get()->RESOURCES_ENABLE && Config::get()->RESOURCES_ALLOW_ROOM_REQUESTS) {
            $navigation->addSubNavigation('room_requests', new Navigation(_('Raumanfragen'), 'dispatch.php/course/room_requests', array('list' => 'TRUE')));
        }

        if (Config::get()->VOTE_ENABLE) {
            $navigation->addSubNavigation('vote', new Navigation(_('Umfragen und Tests'), 'admin_vote.php?view=vote_sem&list=TRUE'));
            $navigation->addSubNavigation('evaluation', new Navigation(_('Evaluationen'), 'admin_evaluation.php?view=eval_sem&list=TRUE'));
        }

        $navigation->addSubNavigation('admission', new Navigation(_('Zugangsberechtigungen'), 'dispatch.php/course/admission', array('list' => 'TRUE')));
        $navigation->addSubNavigation('groups', new Navigation(_('Funktionen / Gruppen'), 'admin_statusgruppe.php?list=TRUE'));
        $navigation->addSubNavigation('modules', new Navigation(_('Inhaltselemente'), 'dispatch.php/course/plus/index?list=TRUE'));

        if ($perm->have_perm($sem_create_perm)) {
            $navigation->addSubNavigation('copy', new Navigation(_('Veranstaltung kopieren'), 'copy_assi.php?list=TRUE&new_session=TRUE'));
            $navigation->addSubNavigation('create', new Navigation(_('Neue Veranstaltung anlegen'), 'admin_seminare_assi.php?new_session=TRUE'));
            $navigation->addSubNavigation('archive', new Navigation(_('Archivieren'), 'archiv_assi.php?list=TRUE&new_session=TRUE'));
            $navigation->addSubNavigation('visibility', new Navigation(_('Sichtbarkeit'), 'admin_visibility.php?list=TRUE'));
        }

        $navigation->addSubNavigation('lock_rules', new Navigation(_('Sperren'), 'admin_lock.php?list=TRUE'));

        $navigation->addSubNavigation('aux_data', new Navigation(_('Zusatzangaben'), 'admin_aux.php?list=TRUE'));
        $this->addSubNavigation('course', $navigation);

        // institute administration
        $navigation = new Navigation(_('Einrichtungen'));

        $navigation->setURL('admin_institut.php?list=TRUE&quit=1');
        $navigation->addSubNavigation('details', new Navigation(_('Grunddaten'), 'admin_institut.php?list=TRUE'));
        $navigation->addSubNavigation('faculty', new Navigation(_('Mitarbeiter'), 'dispatch.php/institute/members?list=TRUE&admin_view=1'));
        $navigation->addSubNavigation('groups', new Navigation(_('Funktionen / Gruppen'), 'dispatch.php/admin/statusgroups?type=inst&list=TRUE'));

        if (Config::get()->VOTE_ENABLE) {
            $navigation->addSubNavigation('vote', new Navigation(_('Umfragen und Tests'), 'admin_vote.php?view=vote_inst&list=TRUE'));
            $navigation->addSubNavigation('evaluation', new Navigation(_('Evaluationen'), 'admin_evaluation.php?view=eval_inst'));
        }

        $navigation->addSubNavigation('modules', new Navigation(_('Inhaltselemente'), 'dispatch.php/course/plus/index?list=TRUE'));

        if (Config::get()->EXTERN_ENABLE) {
            $navigation->addSubNavigation('external', new Navigation(_('Externe Seiten'), 'admin_extern.php?list=TRUE&view=extern_inst'));
        }

        if ($perm->have_perm("root") || ($perm->is_fak_admin() && Config::get()->INST_FAK_ADMIN_PERMS != 'none')) {
            $navigation->addSubNavigation('create', new Navigation(_('Neue Einrichtung anlegen'), 'admin_institut.php?i_view=new'));
        }

        $this->addSubNavigation('institute', $navigation);
        $navigation = new Navigation(_('Standort'));

        if ($perm->have_perm(Config::get()->RANGE_TREE_ADMIN_PERM ? Config::get()->RANGE_TREE_ADMIN_PERM : 'admin')) {
            $navigation->addSubNavigation('range_tree', new Navigation(_('Einrichtungshierarchie'), 'admin_range_tree.php'));
        }

        if ($perm->have_perm(Config::get()->SEM_TREE_ADMIN_PERM ? Config::get()->SEM_TREE_ADMIN_PERM : 'admin') && $perm->is_fak_admin()) {
            $navigation->addSubNavigation('sem_tree', new Navigation(_('Veranstaltungshierarchie'), 'admin_sem_tree.php'));
        }

        if ($perm->have_perm(Config::get()->LOCK_RULE_ADMIN_PERM ? Config::get()->LOCK_RULE_ADMIN_PERM : 'admin')) {
            $navigation->addSubNavigation('lock_rules', new Navigation(_('Sperrebenen'), 'dispatch.php/admin/lockrules'));
        }


        if ($perm->have_perm('root')) {
            $navigation->addSubNavigation('auto_insert', new Navigation(_('Automatisiertes Eintragen'), 'dispatch.php/admin/autoinsert'));

            if (Config::get()->SEMESTER_ADMINISTRATION_ENABLE) {
                $navigation->addSubNavigation('semester', new Navigation(_('Semester'), 'dispatch.php/admin/semester'));
                $navigation->addSubNavigation('holidays', new Navigation(_('Ferien'), 'dispatch.php/admin/holidays'));
            }

            if (Config::get()->EXTERN_ENABLE) {
                $navigation->addSubNavigation('external', new Navigation(_('Externe Seiten'), 'admin_extern.php?list=TRUE&view=extern_global'));
            }

            $navigation->addSubNavigation('studycourse', new Navigation(_('Studieng�nge'), 'dispatch.php/admin/studycourse/profession'));
            $navigation->addSubNavigation('sem_classes', new Navigation(_('Veranstaltungskategorien'), 'dispatch.php/admin/sem_classes/overview'));
        }
        $this->addSubNavigation('locations', $navigation);

        // global config / user administration
        $navigation = new Navigation(_('System'));


        if ($perm->have_perm('root')) {
            $navigation->addSubNavigation('plugins', new Navigation(_('Plugins'), 'dispatch.php/admin/plugin'));
            $navigation->addSubNavigation('roles', new Navigation(_('Rollen'), 'dispatch.php/admin/role'));
            $navigation->addSubNavigation('datafields', new Navigation(_('Datenfelder'), 'dispatch.php/admin/datafields'));
            $navigation->addSubNavigation('configuration', new Navigation(_('Konfiguration'), 'dispatch.php/admin/configuration/configuration'));

            if (Config::get()->BANNER_ADS_ENABLE) {
                $navigation->addSubNavigation('banner', new Navigation(_('Werbebanner'), 'dispatch.php/admin/banner'));
            }

            $navigation->addSubNavigation('studygroup', new Navigation(_('Studiengruppen'), 'dispatch.php/course/studygroup/globalmodules'));

            if (Config::get()->SMILEYADMIN_ENABLE) {
                $navigation->addSubNavigation('smileys', new Navigation(_('Smileys'), 'dispatch.php/admin/smileys'));
            }

            if (Config::get()->TOURS_ENABLE) {
                $navigation->addSubNavigation('tour', new Navigation(_('Touren'), 'dispatch.php/tour/admin_overview'));
            }

            if (Config::get()->ELEARNING_INTERFACE_ENABLE) {
                $navigation->addSubNavigation('elearning', new Navigation(_('Lernmodule'), 'admin_elearning_interface.php'));
            }

            if (Config::get()->WEBSERVICES_ENABLE) {
                $navigation->addSubNavigation('webservice_access', new Navigation(_('Webservices'), 'dispatch.php/admin/webservice_access'));
            }

            if (Config::get()->CRONJOBS_ENABLE) {
                $navigation->addSubNavigation('cronjobs', new Navigation(_('Cronjobs'), 'dispatch.php/admin/cronjobs/schedules'));
            }

            if (Config::get()->PERSONALDOCUMENT_ENABLE) {
                $navigation->addSubNavigation('document_area', new Navigation(_('Pers. Dateibereich'), 'dispatch.php/document/administration'));
            }


            $navigation->addSubNavigation('admissionrules', new Navigation(_('Anmelderegeln'), 'dispatch.php/admission/ruleadministration'));

            $navigation->addSubNavigation('api', new Navigation(_('API'), 'dispatch.php/admin/api'));
        }
        if ($perm->have_perm(Config::get()->AUX_RULE_ADMIN_PERM ? Config::get()->AUX_RULE_ADMIN_PERM : 'admin')) {
            $navigation->addSubNavigation('specification', new Navigation(_('Zusatzangaben'), 'dispatch.php/admin/specification'));
        }

        $this->addSubNavigation('config', $navigation);

        // log view
        if ($perm->have_perm('root') && Config::get()->LOG_ENABLE) {
            $navigation = new Navigation(_('Log'));
            $navigation->addSubNavigation('show', new Navigation(_('Log'), 'dispatch.php/event_log/show'));
            $navigation->addSubNavigation('admin', new Navigation(_('Einstellungen'), 'dispatch.php/event_log/admin'));
            $this->addSubNavigation('log', $navigation);
        }

        // link to course
        if ($SessSemName['class'] == 'inst') {
            $navigation = new Navigation($back_jump, 'dispatch.php/institute/overview?auswahl=' . $SessSemName[1]);
            $this->addSubNavigation('back_jump', $navigation);
        } else if ($SessSemName['class'] == 'sem' && !$archive_kill && !(isset($_SESSION['links_admin_data']['assi']) && $_SESSION['links_admin_data']['assi'])) {
            $navigation = new Navigation($back_jump, 'seminar_main.php?auswahl=' . $SessSemName[1]);
            $this->addSubNavigation('back_jump', $navigation);
        }

        // admin plugins
        $navigation = new Navigation(_('Admin-Plugins'));
        $this->addSubNavigation('plugins', $navigation);
    }
}
