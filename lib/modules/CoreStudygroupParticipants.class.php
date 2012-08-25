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

class CoreStudygroupParticipants implements StudipModule {
    
    function getIconNavigation($course_id, $last_visit, $user_id) {
        $navigation = new Navigation(_('TeilnehmerInnen'), "seminar_main.php?auswahl=".$course_id."&redirect_to=teilnehmer.php");
        $navigation->setImage('icons/16/grey/persons.png');
        return $navigation;
    }
    
    function getTabNavigation($course_id) {
        $navigation = new Navigation(_('TeilnehmerInnen'), "dispatch.php/course/studygroup/members/".$course_id);
        $navigation->setImage('icons/16/white/persons.png');
        $navigation->setActiveImage('icons/16/black/persons.png');
        return array('members' => $navigation);
    }

    function getNotificationObjects($course_id, $since, $user_id)
    {
        $items = array();

        $stmt = DBManager::get()->prepare('SELECT seminar_user.*, seminare.Name,
            '. $GLOBALS['_fullname_sql']['full'] .' as fullname
            FROM seminar_user
            JOIN auth_user_md5 USING (user_id)
            JOIN user_info USING (user_id)
            JOIN seminare USING (Seminar_id)
            WHERE Seminar_id = ? 
                AND seminar_user.mkdate > ?');
        
        $stmt->execute(array($course_id, $since));
        
        while ($row = $stmt->fetch()) {
            $summary = sprintf('%s ist der Studiengruppe "%s" beigetreten.',
                $row['fullname'], $row['Name']);

            $items[] = new ContentElement(
                'Studiengruppe: Neue/r Teilnehmer/in', $summary, '', $row['user_id'], $row['fullname'],
                URLHelper::getLink('dispatch.php/course/studygroup/members/' . $row['Seminar_id'],
                    array('cid' => $row['Seminar_id'])),
                $row['mkdate']
            );
        }
        
        return $items;
    }
}
