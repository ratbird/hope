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

class CoreParticipants implements StudipModule {
    
    function getIconNavigation($course_id, $last_visit, $user_id) {
        $navigation = new Navigation(_('TeilnehmerInnen'), "seminar_main.php?auswahl=".$course_id."&redirect_to=teilnehmer.php");
        $navigation->setImage('icons/16/grey/persons.png');
        return $navigation;
    }
    
    function getTabNavigation($course_id) {
        $rule = AuxLockRules::getLockRuleBySemId($course_id);
        $navigation = new Navigation(_('TeilnehmerInnen'));
        $navigation->setImage('icons/16/white/persons.png');
        $navigation->setActiveImage('icons/16/black/persons.png');

        $navigation->addSubNavigation('view', new Navigation(_('TeilnehmerInnen'), "teilnehmer.php"));

        if (is_array($rule['attributes']) && in_array(1, $rule['attributes'])) {
            $navigation->addSubNavigation('aux_data', new Navigation(_('Zusatzangaben'), 'teilnehmer_aux.php'));
        }

        $navigation->addSubNavigation('view_groups', new Navigation(_('Funktionen / Gruppen'), 'statusgruppen.php?view=statusgruppe_sem'));

        if ($GLOBALS['perm']->have_studip_perm('tutor', $course_id) && !LockRules::check($course_id, 'groups')) {
            $navigation->addSubNavigation('edit_groups', new Navigation(_('Funktionen / Gruppen verwalten'), 'admin_statusgruppe.php?new_sem=TRUE&range_id=' .$course_id));
        }

        return array('members' => $navigation);
    }

    function getNotificationObjects($course_id, $since, $user_id)
    {
        $items = array();
        $type = get_object_type($course_id, array('sem', 'inst', 'fak'));
        
        // only show new participants for seminars, not for institutes
        if ($type != 'sem') return $items;

        $stmt = DBManager::get()->prepare('SELECT seminar_user.*, seminare.Name, seminare.status,
            '. $GLOBALS['_fullname_sql']['full'] .' as fullname
            FROM seminar_user
            JOIN auth_user_md5 USING (user_id)
            JOIN user_info USING (user_id)
            JOIN seminare USING (Seminar_id)
            WHERE Seminar_id = ? 
                AND seminar_user.mkdate > ?');
        
        $stmt->execute(array($course_id, $since));
        
        while ($row = $stmt->fetch()) {
            $summary = sprintf('%s ist der Veranstaltung "%s" beigetreten.',
                $row['fullname'], $row['Name']);

            $items[] = new ContentElement(
                'Studiengruppe: Neue/r Teilnehmer/in', $summary, '', $row['user_id'], $row['fullname'],
                URLHelper::getLink('seminar_main.php?auswahl='. $row['Seminar_id'] .'&redirect_to=teilnehmer.php'),
                $row['mkdate']
            );
        }
        
        return $items;
    }
}
