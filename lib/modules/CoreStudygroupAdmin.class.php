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

class CoreStudygroupAdmin implements StudipModule {
    
    function getIconNavigation($course_id, $last_visit) {
        $navigation = new Navigation(_('Verwaltung'), 'dispatch.php/course/studygroup/edit/'.$course_id);
        $navigation->setImage('icons/16/grey/admin.png');
        return $navigation;
    }
    
    function getTabNavigation($course_id) {
        
        if ($GLOBALS['perm']->have_studip_perm('dozent', $course_id)) {
            $navigation = new Navigation(_('Verwaltung'));
            $navigation->setImage('icons/16/white/admin.png');
            $navigation->setActiveImage('icons/16/black/admin.png');

            $navigation->addSubNavigation('main', new Navigation(_('Verwaltung'), 'dispatch.php/course/studygroup/edit/'.$course_id));
            return array('admin' => $navigation);
        } else {
            return array();
        }
    }
    
}
