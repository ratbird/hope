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

class CoreSchedule implements StudipModule {
    
    function getIconNavigation($course_id, $last_visit, $user_id) {
        $navigation = new Navigation(_('Ablaufplan'), "seminar_main.php?auswahl=$course_id&redirect_to=dates.php&date_type=all");
        $navigation->setImage('icons/16/grey/schedule.png');
        return $navigation;
    }
    
    function getTabNavigation($course_id) {
        // cmd und open_close_id mit durchziehen, damit ge�ffnete Termine ge�ffnet bleiben
        $req = Request::getInstance();
        $openItem = '';
        if (isset($req['cmd']) && isset($req['open_close_id'])) {
            $openItem = '&cmd='.$req['cmd'].'&open_close_id='.$req['open_close_id'];
        }
        
        $navigation = new Navigation(_('Ablaufplan'));
        $navigation->setImage('icons/16/white/schedule.png');
        $navigation->setActiveImage('icons/16/black/schedule.png');

        $navigation->addSubNavigation('all', new Navigation(_('Alle Termine'), "dates.php?date_type=all".$openItem));
        $navigation->addSubNavigation('type1', new Navigation(_('Sitzungstermine'), "dates.php?date_type=1".$openItem));
        $navigation->addSubNavigation('other', new Navigation(_('Andere Termine'), "dates.php?date_type=other".$openItem));


        if ($GLOBALS['perm']->have_studip_perm('tutor', $course_id)) {
            $navigation->addSubNavigation('edit', new Navigation(_('Ablaufplan bearbeiten'), 'themen.php?seminar_id=' . $course_id.$openItem));
        }

        return array('schedule' => $navigation);
    }

    function getNotificationObjects($course_id, $since, $user_id)
    {
        return null;
    }
}
