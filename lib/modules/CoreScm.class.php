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

class CoreScm implements StudipModule {
    
    function getIconNavigation($course_id, $last_visit) {
        if (get_config('SCM_ENABLE')) {
            $navigation = new Navigation(_('Ablaufplan'), "seminar_main.php?auswahl=$course_id&redirect_to=dates.php&date_type=all");
            $navigation->setImage('icons/16/grey/schedule.png');
            return $navigation;
        } else {
            return null;
        }
    }
    
    function getTabNavigation($course_id) {
        if (get_config('SCM_ENABLE')) {
            $scms = array_values(StudipScmEntry::GetSCMEntriesForRange($course_id));

            $navigation = new Navigation($scms[0]['tab_name']);
            $navigation->setImage('icons/16/white/infopage.png');
            $navigation->setActiveImage('icons/16/black/infopage.png');

            foreach ($scms as $scm) {
                $navigation->addSubNavigation($scm['scm_id'], new Navigation($scm['tab_name'] , "scm.php?show_scm=".$scm['scm_id']));
            }

            if ($GLOBALS['perm']->have_studip_perm('tutor', $course_id)) {
                $navigation->addSubNavigation('new_entry', new Navigation(_('Neuen Eintrag anlegen'), "scm.php?show_scm=new_entry&i_view=edit"));
            }

            return array('scm' => $navigation);
        } else {
            return null;
        }
    }
    
}
