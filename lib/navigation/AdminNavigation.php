<?php
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
 * @copyright   2010 Stud.IP Core-Group
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

        $this->setImage('header/header_admin', array('title' => _('Zu Ihrer Administrationsseite')));
    }

    /**
     * Initialize the subnavigation of this item. This method
     * is called once before the first item is added or removed.
     */
    public function initSubNavigation()
    {
        global $SessSemName, $archive_kill, $links_admin_data, $perm;

        parent::initSubNavigation();

        if ($SessSemName['class'] == 'inst') {
            if ($links_admin_data['referred_from'] == 'inst') {
                $back_jump= _('zurück zur ausgewählten Einrichtung');
            } else {
                $back_jump= _('zur ausgewählten Einrichtung');
            }
        } else if ($SessSemName['class'] == 'sem') {
            if ($links_admin_data['referred_from'] == 'sem' && !$archive_kill && !$links_admin_data['assi']) {
                $back_jump= _('zurück zur ausgewählten Veranstaltung');
            } else if ($links_admin_data['referred_from'] == 'assi' && !$archive_kill) {
                $back_jump= _('zur neu angelegten Veranstaltung');
            } else if (!$links_admin_data['assi']) {
                $back_jump= _('zur ausgewählten Veranstaltung');
            }
        }

        $sem_create_perm = in_array(get_config('SEM_CREATE_PERM'), array('root','admin','dozent')) ? get_config('SEM_CREATE_PERM') : 'dozent';

        // course administration
        $navigation = new Navigation(_('Veranstaltungen'), 'adminarea_start.php?list=TRUE');
        $navigation->addSubNavigation('details', new Navigation(_('Grunddaten'), 'admin_seminare1.php?list=TRUE'));
        $navigation->addSubNavigation('study_areas', new Navigation(_('Studienbereiche'),
                                      'dispatch.php/course/study_areas/show/' . $_SESSION['SessionSeminar'], array('list' => 'TRUE')));
        $navigation->addSubNavigation('dates', new Navigation(_('Zeiten / Räume'), 'raumzeit.php?list=TRUE'));
        $navigation->addSubNavigation('schedule', new Navigation(_('Ablaufplan'), 'themen.php?list=TRUE'));
        $navigation->addSubNavigation('news', new Navigation(_('News'), 'admin_news.php?list=TRUE&view=news_sem'));

        if (get_config('VOTE_ENABLE')) {
            $navigation->addSubNavigation('vote', new Navigation(_('Umfragen und Tests'), 'admin_vote.php?view=vote_sem'));
            $navigation->addSubNavigation('evaluation', new Navigation(_('Evaluationen'), 'admin_evaluation.php?view=eval_sem'));
        }

        if (get_config('LITERATURE_ENABLE')) {
            $navigation->addSubNavigation('literature', new Navigation(_('Literatur'), 'admin_lit_list.php?list=TRUE&view=literatur_sem'));
        }
        $navigation->addSubNavigation('admission', new Navigation(_('Zugangsberechtigungen'), 'admin_admission.php?list=TRUE'));
        $navigation->addSubNavigation('groups', new Navigation(_('Gruppen / Funktionen'), 'admin_statusgruppe.php?list=TRUE'));
        $navigation->addSubNavigation('modules', new Navigation(_('Inhaltselemente'), 'admin_modules.php?list=TRUE&view=modules_sem'));

        if ($perm->have_perm($sem_create_perm)) {
            $navigation->addSubNavigation('copy', new Navigation(_('Veranstaltung kopieren'), 'copy_assi.php?list=TRUE&new_session=TRUE'));
            $navigation->addSubNavigation('create', new Navigation(_('Neue Veranstaltung anlegen'), 'admin_seminare_assi.php?new_session=TRUE'));

            if (get_config('ALLOW_DOZENT_ARCHIV') || $perm->have_perm('admin')) {
                $navigation->addSubNavigation('archive', new Navigation(_('Archivieren'), 'archiv_assi.php?list=TRUE&new_session=TRUE'));
            }

            if (get_config('ALLOW_DOZENT_VISIBILITY') || $perm->have_perm('admin')) {
                $navigation->addSubNavigation('visibility', new Navigation(_('Sichtbarkeit'), 'admin_visibility.php?list=TRUE'));
            }
        }

        if (get_config('SEMINAR_LOCK_ENABLE') && $perm->have_perm('admin')) {
            $navigation->addSubNavigation('lock_rules', new Navigation(_('Sperren'), 'admin_lock.php?list=TRUE'));
        }

        $navigation->addSubNavigation('aux_data', new Navigation(_('Zusatzangaben'), 'admin_aux.php?list=TRUE'));
        $this->addSubNavigation('course', $navigation);

        // institute administration
        $navigation = new Navigation(_('Einrichtungen'));
        $navigation->setURL('admin_news.php?list=TRUE&view=news_inst');

        if ($perm->have_perm('admin')) {
            $navigation->setURL('admin_institut.php?list=TRUE&quit=1');
            $navigation->addSubNavigation('details', new Navigation(_('Grunddaten'), 'admin_institut.php?list=TRUE'));
            $navigation->addSubNavigation('faculty', new Navigation(_('Mitarbeiter'), 'inst_admin.php?list=TRUE'));
            $navigation->addSubNavigation('groups', new Navigation(_('Gruppen / Funktionen'), 'admin_roles.php?list=TRUE'));
        }

        $navigation->addSubNavigation('news', new Navigation(_('News'), 'admin_news.php?list=TRUE&view=news_inst'));

        if (get_config('VOTE_ENABLE')) {
            $navigation->addSubNavigation('vote', new Navigation(_('Umfragen und Tests'), 'admin_vote.php?view=vote_inst'));
            $navigation->addSubNavigation('evaluation', new Navigation(_('Evaluationen'), 'admin_evaluation.php?view=eval_inst'));
        }

        if (get_config('LITERATURE_ENABLE')) {
            $navigation->addSubNavigation('literature', new Navigation(_('Literatur'), 'admin_lit_list.php?list=TRUE&view=literatur_inst'));
        }

        if ($perm->have_perm('admin'))
            $navigation->addSubNavigation('modules', new Navigation(_('Inhaltselemente'), 'admin_modules.php?list=TRUE&view=modules_inst'));

        if (get_config('EXTERN_ENABLE') && $perm->have_perm('admin')) {
            $navigation->addSubNavigation('external', new Navigation(_('Externe Seiten'), 'admin_extern.php?list=TRUE&view=extern_inst'));
        }

        if ($perm->have_perm("root") || ($perm->is_fak_admin() && get_config('INST_FAK_ADMIN_PERMS') != 'none')) {
            $navigation->addSubNavigation('create', new Navigation(_('Neue Einrichtung anlegen'), 'admin_institut.php?i_view=new'));
        }

        $this->addSubNavigation('institute', $navigation);

        // global config / user administration
        if ($perm->have_perm('admin')) {
            $navigation = new Navigation(_('Globale Einstellungen'));

            if (!get_config('RESTRICTED_USER_MANAGEMENT') || $perm->have_perm('root')) {
                $navigation->addSubNavigation('new_user', new Navigation(_('Benutzer'), 'new_user_md5.php'));
            }

            if ($perm->have_perm(get_config('RANGE_TREE_ADMIN_PERM') ? get_config('RANGE_TREE_ADMIN_PERM') : 'admin')) {
                $navigation->addSubNavigation('range_tree', new Navigation(_('Einrichtungshierarchie'), 'admin_range_tree.php'));
            }

            if ($perm->have_perm(get_config('SEM_TREE_ADMIN_PERM') ? get_config('SEM_TREE_ADMIN_PERM') : 'admin') && $perm->is_fak_admin()) {
                $navigation->addSubNavigation('sem_tree', new Navigation(_('Veranstaltungshierarchie'), 'admin_sem_tree.php'));
            }

            if ($perm->have_perm(get_config('AUX_RULE_ADMIN_PERM') ? get_config('AUX_RULE_ADMIN_PERM') : 'admin')) {
                $navigation->addSubNavigation('aux_data', new Navigation(_('Zusatzangaben definieren'), 'admin_aux_adjust.php'));
            }

            if ($perm->have_perm(get_config('LOCK_RULE_ADMIN_PERM') ? get_config('LOCK_RULE_ADMIN_PERM') : 'admin') && get_config('SEMINAR_LOCK_ENABLE')) {
                $navigation->addSubNavigation('lock_rules', new Navigation(_('Sperrebenen anpassen'), 'admin_lock_adjust.php'));
            }

            if ($perm->have_perm('root')) {
                $navigation->addSubNavigation('study_programs', new Navigation(_('Studiengänge'), 'admin_studiengang.php'));
                $navigation->addSubNavigation('user_domains', new Navigation(_('Nutzerdomänen'), 'dispatch.php/domain_admin/show'));
                $navigation->addSubNavigation('data_fields', new Navigation(_('Datenfelder'), 'admin_datafields.php'));
                $navigation->addSubNavigation('settings', new Navigation(_('Konfiguration'), 'admin_config.php'));

                if (get_config('BANNER_ADS_ENABLE'))  {
                    $navigation->addSubNavigation('banner_ads', new Navigation(_('Werbebanner'), 'admin_banner_ads.php'));
                }

                if (get_config('SMILEYADMIN_ENABLE')) {
                    $navigation->addSubNavigation('smileys', new Navigation(_('Smileys'), 'admin_smileys.php'));
                }

                if (get_config('SEMESTER_ADMINISTRATION_ENABLE')) {
                    $navigation->addSubNavigation('semester', new Navigation(_('Semester'), 'admin_semester.php'));
                }

                $navigation->addSubNavigation('member_view', new Navigation(_('Teilnehmeransicht'), 'admin_teilnehmer_view.php'));

                if (get_config('EXTERN_ENABLE')) {
                    $navigation->addSubNavigation('external', new Navigation(_('Externe Seiten'), 'admin_extern.php?list=TRUE&view=extern_global'));
                }

                if (get_config('STUDYGROUPS_ENABLE')) {
                    $navigation->addSubNavigation('studygroup', new Navigation(_('Studiengruppen'), 'dispatch.php/course/studygroup/globalmodules'));
                }
            }

            $this->addSubNavigation('config', $navigation);
        }

        // log view
        if ($perm->have_perm('root') && get_config('LOG_ENABLE')) {
            $navigation = new Navigation(_('Log'));
            $navigation->addSubNavigation('show', new Navigation(_('Log'), 'dispatch.php/event_log/show'));
            $navigation->addSubNavigation('admin', new Navigation(_('Einstellungen'), 'dispatch.php/event_log/admin'));
            $this->addSubNavigation('log', $navigation);
        }

        // admin tools
        $navigation = new Navigation(_('Tools'));

        // plugin and role administration
        if ($perm->have_perm('root')) {
            $navigation->addSubNavigation('plugins', new Navigation(_('Pluginverwaltung'), 'dispatch.php/plugin_admin'));
            $navigation->addSubNavigation('roles', new Navigation(_('Rollenverwaltung'), 'dispatch.php/role_admin'));
        }

        if (get_config('EXPORT_ENABLE')) {
            $navigation->addSubNavigation('export', new Navigation(_('Export'), 'export.php'));
        }

        if ($perm->have_perm('admin')) {
            $navigation->addSubNavigation('show_admission', new Navigation(_('Laufende Anmeldeverfahren'), 'show_admission.php'));
            $navigation->addSubNavigation('literature', new Navigation(_('Literaturübersicht'), 'admin_literatur_overview.php'));
        }

        if ($perm->have_perm('dozent') && get_config('STM_ENABLE')) {
            $navigation->addSubNavigation('modules', new Navigation(_('Konkrete Studienmodule'), 'stm_instance_assi.php'));
        }

        if ($perm->have_perm('root')) {
            if (get_config('STM_ENABLE')) {
                $navigation->addSubNavigation('abstract_modules', new Navigation(_('Allgemeine Studienmodule'), 'stm_abstract_assi.php'));
            }

            if (get_config('ELEARNING_INTERFACE_ENABLE')) {
                $navigation->addSubNavigation('elearning', new Navigation(_('Lernmodul-Schnittstelle'), 'admin_elearning_interface.php'));
            }

            $navigation->addSubNavigation('db_integrity', new Navigation(_('DB Integrität'), 'admin_db_integrity.php'));
        }

        $this->addSubNavigation('tools', $navigation);

        // link to course
        if ($SessSemName['class'] == 'inst') {
            $navigation = new Navigation($back_jump, 'institut_main.php?auswahl='.$SessSemName[1]);
            $this->addSubNavigation('back_jump', $navigation);
        } else if ($SessSemName['class'] == 'sem' && !$archive_kill && !$links_admin_data['assi']) {
            $navigation = new Navigation($back_jump, 'seminar_main.php?auswahl='.$SessSemName[1]);
            $this->addSubNavigation('back_jump', $navigation);
        }

        // admin plugins
        if ($perm->have_perm('admin')) {
            $navigation = new Navigation(_('Admin-Plugins'));
            $this->addSubNavigation('plugins', $navigation);
        }
    }
}
