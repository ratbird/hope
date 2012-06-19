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

class CoreOverview implements StudipModule {
    
    function getIconNavigation($course_id, $last_visit) {
        return null;
    }
    
    function getTabNavigation($course_id) {
        $statement = DBManager::get()->prepare("SELECT status FROM seminare WHERE Seminar_id = :seminar_id");
        $statement->execute(array('seminar_id' => $course_id));
        $sem_type = $statement->fetch(PDO::FETCH_COLUMN, 0);
        if ($sem_type) {
            $sem_class = $GLOBALS['SEM_CLASS'][$GLOBALS['SEM_TYPE'][$sem_type]['class']];
            $studygroup_mode = $sem_class['studygroup_mode'];
        }
        $result = DBManager::get()->query("SELECT admission_binding FROM seminare WHERE seminar_id = ".DBManager::get()->quote($_SESSION['SessionSeminar'])."");
        $admission_binding = $result->fetchColumn();
        
        $navigation = new Navigation(_('Übersicht'));
        $navigation->setImage('icons/16/white/seminar.png');
        $navigation->setActiveImage('icons/16/black/seminar.png');
        $navigation->addSubNavigation('info', new Navigation(_('Kurzinfo'), 'seminar_main.php'));

        if (!$studygroup_mode) {
            $navigation->addSubNavigation('details', new Navigation(_('Details'), 'details.php'));
            $navigation->addSubNavigation('print', new Navigation(_('Druckansicht'), 'print_seminar.php'));
        }

        if ($GLOBALS['perm']->have_studip_perm('admin', $course_id) 
                && !$studygroup_mode 
                && ($sem_class->getSlotModule("admin") || $GLOBALS['perm']->have_perm('root'))) {
            $navigation->addSubNavigation('admin', new Navigation(_('Administration dieser Veranstaltung'), 'adminarea_start.php?new_sem=TRUE'));
        }

        if (!$admission_binding && !$GLOBALS['perm']->have_studip_perm('tutor', $_SESSION['SessionSeminar']) && $user->id != 'nobody') {
            $navigation->addSubNavigation('leave', new Navigation(_('Austragen aus der Veranstaltung'), 'meine_seminare.php?auswahl='.$course_id.'&cmd=suppose_to_kill'));
        }
        return array('main' => $navigation);
    }
    
}
