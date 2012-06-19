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
    
    function getIconNavigation($course_id, $last_visit) {
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
    
}
