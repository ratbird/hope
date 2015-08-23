<?php

/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

require_once 'lib/modules/StudipModule.class.php';

class CoreAdmin implements StudipModule {

    function getIconNavigation($course_id, $last_visit, $user_id) {
        $navigation = new Navigation(_('Verwaltung'), 'dispatch.php/course/management');
        $navigation->setImage('icons/16/grey/admin.png', tooltip2(_('Verwaltung')));
        return $navigation;
    }

    function getTabNavigation($course_id) {

        $sem_create_perm = in_array(get_config('SEM_CREATE_PERM'), array('root','admin','dozent')) ? get_config('SEM_CREATE_PERM') : 'dozent';

        if ($GLOBALS['perm']->have_studip_perm('tutor', $course_id)) {
            $navigation = new Navigation(_('Verwaltung'));
            $navigation->setImage('icons/16/white/admin.png');
            $navigation->setActiveImage('icons/16/black/admin.png');

            $main = new Navigation(_('Verwaltung'), 'dispatch.php/course/management');
            $navigation->addSubNavigation('main', $main);

            if ($GLOBALS['SessSemName']['class'] !== "inst") {
                $item = new Navigation(_('Grunddaten'), 'dispatch.php/course/basicdata/view/' . $course_id);
                $item->setDescription(_('Prüfen und bearbeiten Sie in diesem Verwaltungsbereich die Grundeinstellungen dieser Veranstaltung.'));
                $navigation->addSubNavigation('details', $item);

                $item = new Navigation(_('Infobild'), 'dispatch.php/course/avatar/update/' . $course_id);
                $item->setDescription(_('Bearbeiten oder löschen Sie das Infobild zu dieser Veranstaltung.'));
                $navigation->addSubNavigation('avatar', $item);

                $item = new Navigation(_('Studienbereiche'), 'dispatch.php/course/study_areas/show/' . $course_id);
                $item->setDescription(_('Legen Sie hier fest, in welchen Studienbereichen diese Veranstaltung im Verzeichnis aller Veranstaltungen erscheint.'));
                $navigation->addSubNavigation('study_areas', $item);

                $item = new Navigation(_('Zeiten/Räume'), 'raumzeit.php');
                $item->setDescription(_('Verändern Sie hier Angaben über regelmäßige Veranstaltungszeiten, Einzeltermine und Ortsangaben.'));
                $navigation->addSubNavigation('dates', $item);

                if (get_config('RESOURCES_ENABLE') && get_config('RESOURCES_ALLOW_ROOM_REQUESTS')) {
                    $item = new Navigation(_('Raumanfragen'), 'dispatch.php/course/room_requests/index/' . $course_id);
                    $item->setDescription(_('Verwalten Sie hier Raumanfragen zu Veranstaltungszeiten.'));
                    $navigation->addSubNavigation('room_requests', $item);
                }

                $item = new Navigation(_('Zugangsberechtigungen'), 'dispatch.php/course/admission');
                $item->setDescription(_('Richten Sie hier verschiedene Zugangsbeschränkungen, Anmeldeverfahren oder einen Passwortschutz für Ihre Veranstaltung ein.'));
                $navigation->addSubNavigation('admission', $item);

                $item = new AutoNavigation(_('Zusatzangaben'), 'dispatch.php/admin/additional');
                $item->setDescription(_('Hier können Sie Vorlagen zur Erhebung weiterer Angaben von Ihren TeilnehmerInnen auswählen.'));
                $navigation->addSubNavigation('additional_data', $item);

                if ($GLOBALS['perm']->have_perm($sem_create_perm)) {
                    if (!LockRules::check($course_id, 'seminar_copy')) {
                        $item = new Navigation(_('Veranstaltung kopieren'), 'dispatch.php/course/wizard/copy/'.$course_id);
                        $item->setImage('icons/16/black/add/seminar.png');
                        $main->addSubNavigation('copy', $item);
                    }

                    if (get_config('ALLOW_DOZENT_ARCHIV')) {
                        $item = new Navigation(_('Veranstaltung archivieren'), 'archiv_assi.php');
                        $item->setImage('icons/16/black/remove/seminar.png');
                        $main->addSubNavigation('archive', $item);
                    }

                    if (get_config('ALLOW_DOZENT_VISIBILITY') && !LockRules::Check($course_id, 'seminar_visibility')) {
                        $is_visible = Course::findCurrent()->visible;
                        $item = new Navigation(_('Sichtbarkeit ändern') . ' (' .  ($is_visible ? _('sichtbar') : _('unsichtbar')) . ')', 'dispatch.php/course/management/change_visibility');
                        $item->setImage('icons/16/black/visibility-' . ($is_visible ? 'visible' : 'invisible' ). '.png');
                        $main->addSubNavigation('visibility', $item);
                    }
                }

                // show entry for simulated participant view
                if (in_array($GLOBALS['perm']->get_studip_perm($course_id), words('tutor dozent'))) {
                    $item = new Navigation('Studierendenansicht simulieren', 'dispatch.php/course/change_view?cid='.Request::option('cid'));
                    $item->setDescription(_('Hier können Sie sich die Veranstaltung so ansehen, wie sie für Ihre TeilnehmerInnen aussieht.'));
                    $item->setImage('icons/16/black/visibility-invisible.png');
                    $main->addSubNavigation('change_view', $item);
                }
            }  // endif modules only seminars

            if ($GLOBALS['perm']->have_studip_perm('tutor', $course_id)) {
                if (get_config('VOTE_ENABLE')) {
                    $item = new Navigation(_('Umfragen und Tests'), 'admin_vote.php?view=vote_sem');
                    $item->setDescription(_('Erstellen und bearbeiten Sie einfache Umfragen und Tests.'));
                    $navigation->addSubNavigation('vote', $item);

                    $item = new Navigation(_('Evaluationen'), 'admin_evaluation.php?view=eval_sem');
                    $item->setDescription(_('Richten Sie fragebogenbasierte Umfragen und Lehrevaluationen ein.'));
                    $navigation->addSubNavigation('evaluation', $item);
                }
            }

            return array('admin' => $navigation);
        } else {
            return array();
        }
    }

    function getNotificationObjects($course_id, $since, $user_id)
    {
        return null;
    }

    /**
     * @see StudipModule::getMetadata()
     */
    function getMetadata()
    {
        return array();
    }
}
