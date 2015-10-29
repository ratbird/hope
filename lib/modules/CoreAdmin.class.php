<?php

/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

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
                $item->setImage('icons/blue/edit.svg');
                $item->setDescription(_('Bearbeiten der Grundeinstellungen dieser Veranstaltung.'));
                $navigation->addSubNavigation('details', $item);

                $item = new Navigation(_('Infobild'), 'dispatch.php/course/avatar/update/' . $course_id);
                $item->setImage('icons/blue/file-pic.svg');
                $item->setDescription(_('Infobild dieser Veranstaltung bearbeiten oder l<öschen.'));
                $navigation->addSubNavigation('avatar', $item);

                $item = new Navigation(_('Studienbereiche'), 'dispatch.php/course/study_areas/show/' . $course_id);
                $item->setImage('icons/blue/module.svg');
                $item->setDescription(_('Zuordnung dieser Veranstaltung zu Studienbereichen für die Darstellung im Verzeichnis aller Veranstaltungen.'));
                $navigation->addSubNavigation('study_areas', $item);

                $item = new Navigation(_('Zeiten/Räume'), 'raumzeit.php');
                $item->setImage('icons/blue/date.svg');
                $item->setDescription(_('Regelmäßige Veranstaltungszeiten, Einzeltermine und Ortsangaben ändern.'));
                $navigation->addSubNavigation('dates', $item);

                if (get_config('RESOURCES_ENABLE') && get_config('RESOURCES_ALLOW_ROOM_REQUESTS')) {
                    $item = new Navigation(_('Raumanfragen'), 'dispatch.php/course/room_requests/index/' . $course_id);
                    $item->setImage('icons/blue/resources.svg');
                    $item->setDescription(_('Raumanfragen zu Veranstaltungszeiten verwalten.'));
                    $navigation->addSubNavigation('room_requests', $item);
                }

                $item = new Navigation(_('Zugangsberechtigungen'), 'dispatch.php/course/admission');
                $item->setImage('icons/blue/lock-locked.svg');
                $item->setDescription(_('Zugangsbeschränkungen, Anmeldeverfahren oder einen Passwortschutz für diese Veranstaltung einrichten.'));
                $navigation->addSubNavigation('admission', $item);

                $item = new AutoNavigation(_('Zusatzangaben'), 'dispatch.php/admin/additional');
                $item->setImage('icons/blue/add.svg');
                $item->setDescription(_('Vorlagen zur Erhebung weiterer Angaben von Teilnehmerinnen und Teilnehmern auswählen.'));
                $navigation->addSubNavigation('additional_data', $item);

                if ($GLOBALS['perm']->have_perm($sem_create_perm)) {
                    if (!LockRules::check($course_id, 'seminar_copy')) {
                        $item = new Navigation(_('Veranstaltung kopieren'), 'dispatch.php/course/wizard/copy/'.$course_id);
                        $item->setImage('icons/16/blue/add/seminar.png');
                        $main->addSubNavigation('copy', $item);
                    }

                    if (get_config('ALLOW_DOZENT_ARCHIV')) {
                        $item = new Navigation(_('Veranstaltung archivieren'), 'archiv_assi.php');
                        $item->setImage('icons/16/blue/remove/seminar.png');
                        $main->addSubNavigation('archive', $item);
                    }

                    if (get_config('ALLOW_DOZENT_VISIBILITY') && !LockRules::Check($course_id, 'seminar_visibility')) {
                        $is_visible = Course::findCurrent()->visible;
                        $item = new Navigation(_('Sichtbarkeit ändern') . ' (' .  ($is_visible ? _('sichtbar') : _('unsichtbar')) . ')', 'dispatch.php/course/management/change_visibility');
                        $item->setImage('icons/16/blue/visibility-' . ($is_visible ? 'visible' : 'invisible' ). '.png');
                        $main->addSubNavigation('visibility', $item);
                    }
                }

                // show entry for simulated participant view
                if (in_array($GLOBALS['perm']->get_studip_perm($course_id), words('tutor dozent'))) {
                    $item = new Navigation('Studierendenansicht simulieren', 'dispatch.php/course/change_view?cid='.Request::option('cid'));
                    $item->setDescription(_('Hier können Sie sich die Veranstaltung aus der Sicht von Studierenden sehen.'));
                    $item->setImage('icons/16/blue/visibility-invisible.png');
                    $main->addSubNavigation('change_view', $item);
                }
            }  // endif modules only seminars

            if ($GLOBALS['perm']->have_studip_perm('tutor', $course_id)) {
                if (get_config('VOTE_ENABLE')) {
                    $item = new Navigation(_('Umfragen und Tests'), 'admin_vote.php?view=vote_sem');
                    $item->setImage('icons/blue/vote.svg');
                    $item->setDescription(_('Erstellen und bearbeiten von einfachen Umfragen und Tests.'));
                    $navigation->addSubNavigation('vote', $item);

                    $item = new Navigation(_('Evaluationen'), 'admin_evaluation.php?view=eval_sem');
                    $item->setImage('icons/blue/evaluation.svg');
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
