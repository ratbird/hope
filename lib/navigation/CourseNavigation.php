<?php
# Lifter010: TODO
/*
 * CourseNavigation.php - navigation for course / institute area
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Elmar Ludwig
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

require_once 'lib/functions.php';
require_once 'lib/classes/Modules.class.php';
require_once 'lib/classes/StudipScmEntry.class.php';
require_once 'lib/classes/LockRules.class.php';
require_once 'lib/classes/AuxLockRules.class.php';
require_once 'lib/classes/AutoInsert.class.php';

if (get_config('ELEARNING_INTERFACE_ENABLE')) {
    require_once $GLOBALS['RELATIVE_PATH_ELEARNING_INTERFACE'].'/ObjectConnections.class.php';
}

if (get_config('RESOURCES_ENABLE')) {
    require_once $GLOBALS['RELATIVE_PATH_RESOURCES'].'/resourcesFunc.inc.php';
}

class CourseNavigation extends Navigation
{
    /**
     * Initialize a new Navigation instance.
     */
    public function __construct()
    {
        global $user, $perm;

        // check if logged in
        if (is_object($user) && $user->id != 'nobody') {
            $coursetext = _('Veranstaltungen');
            $courseinfo = _('Meine Veranstaltungen & Einrichtungen');
            $courselink = 'meine_seminare.php';
        } else {
            $coursetext = _('Freie');
            $courseinfo = _('Freie Veranstaltungen');
            $courselink = 'freie.php';
        }

        parent::__construct($coursetext, $courselink);

        if (is_object($user) && !$perm->have_perm('root')) {
            $this->setImage('header/seminar.png', array('title' => $courseinfo));
        }
    }

    /**
     * Initialize the subnavigation of this item. This method
     * is called once before the first item is added or removed.
     */
    public function initSubNavigation()
    {
        global $SEM_CLASS, $SEM_TYPE;
        global $SessSemName, $forum, $perm, $user;

        parent::initSubNavigation();

        // list of used modules
        $Modules = new Modules;
        $modules = $Modules->getLocalModules($SessSemName[1]);
        $studygroup_mode = $SEM_CLASS[$SEM_TYPE[$SessSemName['art_num']]['class']]['studygroup_mode'];

        $db = DBManager::get();
        $result = $db->query("SELECT admission_binding FROM seminare WHERE seminar_id = '$SessSemName[1]'");
        $admission_binding = $result->fetchColumn();

        $rule = AuxLockRules::getLockRuleBySemId($SessSemName[1]);
        $sem_class = $SessSemName['class'];

        if ($modules['scm']) {
            $scms = array_values(StudipScmEntry::GetSCMEntriesForRange($SessSemName[1]));
        }

        $sem_create_perm = in_array(get_config('SEM_CREATE_PERM'), array('root','admin','dozent')) ? get_config('SEM_CREATE_PERM') : 'dozent';

        if ($sem_class == 'sem' && $perm->have_studip_perm('tutor', $SessSemName[1]) && !$studygroup_mode) {
            $navigation = new Navigation('', 'dispatch.php/course/change_view?cid='.$SessSemName[1]);
            $navigation->setDescription(_('Ansicht simulieren'));
            $navigation->setImage('icons/16/%COLOR%/tools.png');
            $this->addSubNavigation('change_view', $navigation);
        }

        // general information
        $navigation = new Navigation(_('Übersicht'));
        $navigation->setImage('icons/16/%COLOR%/seminar.png');

        if ($sem_class == 'sem') {
            $navigation->addSubNavigation('info', new Navigation(_('Kurzinfo'), 'seminar_main.php'));

            if (!$studygroup_mode) {
                $navigation->addSubNavigation('details', new Navigation(_('Details'), 'details.php'));
                $navigation->addSubNavigation('print', new Navigation(_('Druckansicht'), 'print_seminar.php'));
            }

            if ($perm->have_studip_perm('admin', $SessSemName[1]) && !$studygroup_mode) {
                $navigation->addSubNavigation('admin', new Navigation(_('Administration dieser Veranstaltung'), 'adminarea_start.php?new_sem=TRUE'));
            }

            if (!$admission_binding && !$perm->have_studip_perm('tutor', $SessSemName[1]) && $user->id != 'nobody') {
                $navigation->addSubNavigation('leave', new Navigation(_('Austragen aus der Veranstaltung'), 'meine_seminare.php?auswahl='.$SessSemName[1].'&cmd=suppose_to_kill'));
            }
        } else {
            $navigation->addSubNavigation('info', new Navigation(_('Info'), 'institut_main.php'));
            $navigation->addSubNavigation('courses', new Navigation(_('Veranstaltungen'), 'show_bereich.php?level=s&id='.$SessSemName[1]));
            $navigation->addSubNavigation('schedule', new Navigation(_('Veranstaltungs-Stundenplan'), 'dispatch.php/calendar/instschedule?cid='.$SessSemName[1]));

            if ($perm->have_studip_perm('tutor', $SessSemName[1]) && $perm->have_perm('admin')) {
                $navigation->addSubNavigation('admin', new Navigation(_('Administration der Einrichtung'), 'admin_institut.php?new_inst=TRUE'));
            }
        }

        $this->addSubNavigation('main', $navigation);

        // admin area
        $navigation = new Navigation(_('Verwaltung'));
        $navigation->setImage('icons/16/%COLOR%/admin.png');

        if ($studygroup_mode) {
            if ($perm->have_studip_perm('dozent', $SessSemName[1])) {
                $navigation->addSubNavigation('main', new Navigation(_('Verwaltung'), 'dispatch.php/course/studygroup/edit/'.$SessSemName[1]));
            }
        } else if ($perm->have_studip_perm('tutor', $SessSemName[1]) && !$perm->have_perm('admin')) {
            $main = new Navigation(_('Verwaltung'), 'dispatch.php/course/management');
            $navigation->addSubNavigation('main', $main);

            if ($sem_class == 'sem') {
                $item = new Navigation(_('Grunddaten'), 'dispatch.php/course/basicdata/view/' . $_SESSION['SessionSeminar']);
                $item->setDescription(_('Prüfen und Bearbeiten Sie in diesem Verwaltungsbereich die Grundeinstellungen dieser Veranstaltung.'));
                $navigation->addSubNavigation('details', $item);

                $item = new Navigation(_('Studienbereiche'), 'dispatch.php/course/study_areas/show/' . $_SESSION['SessionSeminar']);
                $item->setDescription(_('Legen Sie hier fest, in welchen Studienbereichen diese Veranstaltung im Verzeichnis aller Veranstaltungen erscheint.'));
                $navigation->addSubNavigation('study_areas', $item);

                $item = new Navigation(_('Zeiten/Räume'), 'raumzeit.php');
                $item->setDescription(_('Verändern Sie hier Angaben über regelmäßige Veranstaltungszeiten, Einzeltermine und Ortsangaben.'));
                $navigation->addSubNavigation('dates', $item);

                $item = new Navigation(_('Zugangsberechtigungen'), 'admin_admission.php');
                $item->setDescription(_('Richten Sie hier verschiedene Zugangsbeschränkungen, Anmeldeverfahren oder einen Passwortschutz für Ihre Veranstaltung ein.'));
                $navigation->addSubNavigation('admission', $item);

                $item = new Navigation(_('Zusatzangaben'), 'admin_aux.php');
                $item->setDescription(_('Hier können Sie Vorlagen zur Erhebung weiter Angaben von Ihren Teilnehmern auswählen.'));
                $navigation->addSubNavigation('aux_data', $item);

                if ($perm->have_perm($sem_create_perm)) {
                    if (!LockRules::check($SessSemName[1], 'seminar_copy')) {
                        $item = new Navigation(_('Veranstaltung kopieren'), 'admin_seminare_assi.php?cmd=do_copy&cp_id='.$SessSemName[1].'&start_level=TRUE&class=1');
                        $item->setImage('icons/16/black/add/seminar.png');
                        $main->addSubNavigation('copy', $item);
                    }

                    if (get_config('ALLOW_DOZENT_ARCHIV')) {
                        $item = new Navigation(_('Veranstaltung archivieren'), 'archiv_assi.php');
                        $item->setImage('icons/16/black/remove/seminar.png');
                        $main->addSubNavigation('archive', $item);
                    }

                    if (get_config('ALLOW_DOZENT_VISIBILITY')) {
                        $item = new Navigation(_('Sichtbarkeit ändern'), 'admin_visibility.php');
                        $item->setImage('icons/16/black/visibility-invisible.png');
                        $main->addSubNavigation('visibility', $item);
                    }
                }
            }
        }

        if ($perm->have_studip_perm('tutor', $SessSemName[1]) && !$perm->have_perm('admin')) {
            $item = new Navigation(_('Ankündigungen'), 'admin_news.php?view=news_' . $sem_class);
            $item->setDescription(_('Erstellen Sie Ankündigungen und bearbeiten Sie laufende Ankündigungen.'));
            $navigation->addSubNavigation('news', $item);

            if (get_config('VOTE_ENABLE')) {
                $item = new Navigation(_('Umfragen und Tests'), 'admin_vote.php?view=vote_' . $sem_class);
                $item->setDescription(_('Erstellen und bearbeiten Sie einfache Umfragen und Tests.'));
                $navigation->addSubNavigation('vote', $item);

                $item = new Navigation(_('Evaluationen'), 'admin_evaluation.php?view=eval_' . $sem_class);
                $item->setDescription(_('Richten Sie fragebogenbasierte Umfragen und Lehrevaluationen ein.'));
                $navigation->addSubNavigation('evaluation', $item);
            }
        }

        $this->addSubNavigation('admin', $navigation);

        // forum
        if ($modules['forum']) {
            $navigation = new Navigation(_('Forum'), 'forum.php?view=reset');
            $navigation->setImage('icons/16/%COLOR%/forum.png');

            $navigation->addSubNavigation('view', new Navigation(_('Themenansicht'), 'forum.php?view='.$forum['themeview']));

            if ($user->id != 'nobody') {
                $navigation->addSubNavigation('unread', new Navigation(_('Neue Beiträge'), 'forum.php?view=neue&sort=age'));
            }

            $navigation->addSubNavigation('recent', new Navigation(_('Letzte Beiträge'), 'forum.php?view=flat&sort=age'));
            $navigation->addSubNavigation('search', new Navigation(_('Suchen'), 'forum.php?view=search&reset=1'));
            $navigation->addSubNavigation('export', new Navigation(_('Druckansicht'), 'forum_export.php'));

            if ($perm->have_studip_perm('tutor', $SessSemName[1]) || $SEM_CLASS[$SEM_TYPE[$SessSemName['art_num']]['class']]['topic_create_autor']) {
                $navigation->addSubNavigation('create_topic', new Navigation(_('Neues Thema anlegen'), 'forum.php?view='.$forum['themeview'].'&neuesthema=TRUE#anker'));
            }

            $this->addSubNavigation('forum', $navigation);
        }

        // participants
        if ($user->id != 'nobody') {
            if ($modules['participants']) {
                $navigation = new Navigation(_('TeilnehmerInnen'));
                $navigation->setImage('icons/16/%COLOR%/persons.png');

                if ($studygroup_mode) {
                    $navigation->setURL('dispatch.php/course/studygroup/members/'.$SessSemName[1]);
                    $this->addSubNavigation('members', $navigation);
                } else if ($perm->have_studip_perm((AutoInsert::checkSeminar($SessSemName[1]) ? Config::get()->AUTO_INSERT_SEM_PARTICIPANTS_VIEW_PERM : 'user') , $SessSemName[1])) {
                    $navigation->addSubNavigation('view', new Navigation(_('TeilnehmerInnen'), 'teilnehmer.php'));

                    if (is_array($rule['attributes']) && in_array(1, $rule['attributes'])) {
                        $navigation->addSubNavigation('aux_data', new Navigation(_('Zusatzangaben'), 'teilnehmer_aux.php'));
                    }

                    $navigation->addSubNavigation('view_groups', new Navigation(_('Funktionen / Gruppen'), 'statusgruppen.php?view=statusgruppe_sem'));

                    if ($perm->have_studip_perm('tutor', $SessSemName[1]) && !LockRules::check($SessSemName[1], 'groups')) {
                        $navigation->addSubNavigation('edit_groups', new Navigation(_('Funktionen / Gruppen verwalten'), 'admin_statusgruppe.php?new_sem=TRUE&range_id=' .$SessSemName[1]));
                    }

                    $this->addSubNavigation('members', $navigation);
                }
            } else if ($modules['personal']) {
                $navigation = new Navigation(_('Personal'));
                $navigation->setImage('icons/16/%COLOR%/persons.png');
                $navigation->addSubNavigation('view', new Navigation(_('MitarbeiterInnen'), 'institut_members.php'));

                if ($perm->have_studip_perm('tutor', $SessSemName[1]) && $perm->have_perm('admin')) {
                    $navigation->addSubNavigation('edit_groups', new Navigation(_('Funktionen / Gruppen verwalten'), 'admin_roles.php?new_sem=TRUE&range_id='. $SessSemName[1]));
                }

                $this->addSubNavigation('faculty', $navigation);
            }
        }

        // files
        if ($modules['documents']) {
            $navigation = new Navigation(_('Dateien'));
            $navigation->setImage('icons/16/%COLOR%/files.png');

            $navigation->addSubNavigation('tree', new Navigation(_('Ordneransicht'), 'folder.php?cmd=tree'));
            $navigation->addSubNavigation('all', new Navigation(_('Alle Dateien'), 'folder.php?cmd=all'));
            $this->addSubNavigation('files', $navigation);
        }

        // schedule
        if ($modules['schedule']) {
            $navigation = new Navigation(_('Ablaufplan'));
            $navigation->setImage('icons/16/%COLOR%/schedule.png');

            $navigation->addSubNavigation('all', new Navigation(_('Alle Termine'), 'dates.php?date_type=all'));
            $navigation->addSubNavigation('type1', new Navigation(_('Sitzungstermine'), 'dates.php?date_type=1'));
            $navigation->addSubNavigation('other', new Navigation(_('Andere Termine'), 'dates.php?date_type=other'));


            if ($perm->have_studip_perm('tutor', $SessSemName[1])) {
                $navigation->addSubNavigation('edit', new Navigation(_('Ablaufplan bearbeiten'), 'themen.php?seminar_id=' . $SessSemName[1]));
            }

            $this->addSubNavigation('schedule', $navigation);
        }

        // information page
        if (get_config('SCM_ENABLE') && $modules['scm']) {
            $navigation = new Navigation($scms[0]['tab_name']);
            $navigation->setImage('icons/16/%COLOR%/infopage.png');

            foreach ($scms as $scm) {
                $navigation->addSubNavigation($scm['scm_id'], new Navigation($scm['tab_name'] , 'scm.php?show_scm=' . $scm['scm_id']));
            }

            if ($perm->have_studip_perm('tutor', $SessSemName[1])) {
                $navigation->addSubNavigation('new_entry', new Navigation(_('Neuen Eintrag anlegen'), 'scm.php?show_scm=new_entry&i_view=edit'));
            }

            $this->addSubNavigation('scm', $navigation);
        }

        // literature
        if (get_config('LITERATURE_ENABLE') && $modules['literature']) {
            $navigation = new Navigation(_('Literatur'));
            $navigation->setImage('icons/16/%COLOR%/literature.png');


            $navigation->addSubNavigation('view', new Navigation(_('Literatur'), 'literatur.php?view=literatur_'.$sem_class));

            $navigation->addSubNavigation('print', new Navigation(_('Druckansicht'), 'lit_print_view.php?_range_id=' . $SessSemName[1]));

            if ($perm->have_studip_perm('tutor', $SessSemName[1])) {
                $navigation->addSubNavigation('edit', new Navigation(_('Literatur bearbeiten'), 'admin_lit_list.php?view=literatur_'.$sem_class.'&new_'.$sem_class.'=TRUE&_range_id='. $SessSemName[1]));
            }

            $this->addSubNavigation('literature', $navigation);
        }

        // wiki
        if (get_config('WIKI_ENABLE') && $modules['wiki']) {
            $navigation = new Navigation(_('Wiki'));
            $navigation->setImage('icons/16/%COLOR%/wiki.png');

            $navigation->addSubNavigation('show', new Navigation(_('WikiWikiWeb'), 'wiki.php?view=show'));
            $navigation->addSubNavigation('listnew', new Navigation(_('Neue Seiten'), 'wiki.php?view=listnew'));
            $navigation->addSubNavigation('listall', new Navigation(_('Alle Seiten'), 'wiki.php?view=listall'));
            $navigation->addSubNavigation('export', new Navigation(_('Export'), 'wiki.php?view=export'));
            $this->addSubNavigation('wiki', $navigation);
        }

        // resources
        if (get_config('RESOURCES_ENABLE')) {
            if (checkAvailableResources($SessSemName[1])) {
                $navigation = new Navigation(_('Ressourcen'), 'resources.php?view=openobject_main&view_mode=oobj');
                $navigation->setImage('icons/16/%COLOR%/resources.png');

                $navigation->addSubNavigation('overview', new Navigation(_('Übersicht'), 'resources.php?view=openobject_main'));
                $navigation->addSubNavigation('group_schedule', new Navigation(_('Übersicht Belegung'), 'resources.php?view=openobject_group_schedule'));
                $navigation->addSubNavigation('view_details', new Navigation(_('Details'), 'resources.php?view=openobject_details'));
                $navigation->addSubNavigation('view_schedule', new Navigation(_('Belegung'), 'resources.php?view=openobject_schedule'));
                $navigation->addSubNavigation('edit_assign', new Navigation(_('Belegungen bearbeiten'), 'resources.php?view=openobject_assign'));
                $this->addSubNavigation('resources', $navigation);
            }
        }

        // content modules
        if (get_config('ELEARNING_INTERFACE_ENABLE') && $modules['elearning_interface'] && $user->id != 'nobody') {
            $navigation = new Navigation(_('Lernmodule'));
            $navigation->setImage('icons/16/%COLOR%/learnmodule.png');

            if (ObjectConnections::isConnected($SessSemName[1])) {
                $elearning_nav = new Navigation(_('Lernmodule dieser Veranstaltung'), 'elearning_interface.php?view=show&seminar_id=' . $SessSemName[1]);

                if ($sem_class == 'inst') {
                    $elearning_nav->setTitle(_('Lernmodule dieser Einrichtung'));
                }

                $navigation->addSubNavigation('show', $elearning_nav);
            }

            if ($perm->have_studip_perm('tutor', $SessSemName[1])) {
                $navigation->addSubNavigation('edit', new Navigation(_('Lernmodule hinzufügen / entfernen'), 'elearning_interface.php?view=edit&seminar_id=' . $SessSemName[1]));
            }

            $this->addSubNavigation('elearning', $navigation);
        }
    }
}
