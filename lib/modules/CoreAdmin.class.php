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
        $navigation->setImage('icons/16/grey/admin.png');
        return $navigation;
    }

    function getTabNavigation($course_id) {

        $sem_create_perm = in_array(get_config('SEM_CREATE_PERM'), array('root','admin','dozent')) ? get_config('SEM_CREATE_PERM') : 'dozent';

        if ($GLOBALS['perm']->have_studip_perm('tutor', $course_id) && !$GLOBALS['perm']->have_perm('admin')) {
            $navigation = new Navigation(_('Verwaltung'));
            $navigation->setImage('icons/16/white/admin.png');
            $navigation->setActiveImage('icons/16/black/admin.png');

            $main = new Navigation(_('Verwaltung'), 'dispatch.php/course/management');
            $navigation->addSubNavigation('main', $main);

            if ($GLOBALS['SessSemName']['class'] !== "inst") {
                $item = new Navigation(_('Grunddaten'), 'dispatch.php/course/basicdata/view/' . $course_id);
                $item->setDescription(_('Pr�fen und Bearbeiten Sie in diesem Verwaltungsbereich die Grundeinstellungen dieser Veranstaltung.'));
                $navigation->addSubNavigation('details', $item);

                $item = new Navigation(_('Avatar'), 'dispatch.php/course/avatar/update/' . $course_id);
                $item->setDescription(_('Bearbeiten oder L�schen Sie den Veranstaltungsavatar, das Infobild zu dieser Veranstaltung.'));
                $navigation->addSubNavigation('avatar', $item);

                $item = new Navigation(_('Studienbereiche'), 'dispatch.php/course/study_areas/show/' . $course_id);
                $item->setDescription(_('Legen Sie hier fest, in welchen Studienbereichen diese Veranstaltung im Verzeichnis aller Veranstaltungen erscheint.'));
                $navigation->addSubNavigation('study_areas', $item);

                $item = new Navigation(_('Zeiten/R�ume'), 'raumzeit.php');
                $item->setDescription(_('Ver�ndern Sie hier Angaben �ber regelm��ige Veranstaltungszeiten, Einzeltermine und Ortsangaben.'));
                $navigation->addSubNavigation('dates', $item);

                if (get_config('RESOURCES_ENABLE') && get_config('RESOURCES_ALLOW_ROOM_REQUESTS')) {
                    $item = new Navigation(_('Raumanfragen'), 'dispatch.php/course/room_requests/index/' . $course_id);
                    $item->setDescription(_('Verwalten Sie hier Raumanfragen zu Veranstaltungszeiten.'));
                    $navigation->addSubNavigation('room_requests', $item);
                }

                $item = new Navigation(_('Zugangsberechtigungen'), 'dispatch.php/course/admission');
                $item->setDescription(_('Richten Sie hier verschiedene Zugangsbeschr�nkungen, Anmeldeverfahren oder einen Passwortschutz f�r Ihre Veranstaltung ein.'));
                $navigation->addSubNavigation('admission', $item);

                $item = new AutoNavigation(_('Zusatzangaben'), 'dispatch.php/admin/additional');
                $item->setDescription(_('Hier k�nnen Sie Vorlagen zur Erhebung weiter Angaben von Ihren Teilnehmern ausw�hlen.'));
                $navigation->addSubNavigation('additional_data', $item);

                if ($GLOBALS['perm']->have_perm($sem_create_perm)) {
                    if (!LockRules::check($course_id, 'seminar_copy')) {
                        $item = new Navigation(_('Veranstaltung kopieren'), 'admin_seminare_assi.php?cmd=do_copy&cp_id='.$course_id.'&start_level=TRUE&class=1');
                        $item->setImage('icons/16/black/add/seminar.png');
                        $main->addSubNavigation('copy', $item);
                    }

                    if (get_config('ALLOW_DOZENT_ARCHIV')) {
                        $item = new Navigation(_('Veranstaltung archivieren'), 'archiv_assi.php');
                        $item->setImage('icons/16/black/remove/seminar.png');
                        $main->addSubNavigation('archive', $item);
                    }

                    if (get_config('ALLOW_DOZENT_VISIBILITY')) {
                        $item = new Navigation(_('Sichtbarkeit �ndern'), 'admin_visibility.php');
                        $item->setImage('icons/16/black/visibility-invisible.png');
                        $main->addSubNavigation('visibility', $item);
                    }
                }

                // show entry for simulated participant view
                $item = new Navigation('Studierendenansicht simulieren', 'dispatch.php/course/change_view?cid='.Request::option('cid'));
                $item->setDescription(_('Hier k�nnen Sie sich die Veranstaltung so ansehen, wie sie f�r Ihre TeilnehmerInnen aussieht.'));
                $item->setImage('icons/16/black/visibility-invisible.png');
                $main->addSubNavigation('change_view', $item);
            }  // endif modules only seminars

            if ($GLOBALS['perm']->have_studip_perm('tutor', $course_id) && !$GLOBALS['perm']->have_perm('admin')) {
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
