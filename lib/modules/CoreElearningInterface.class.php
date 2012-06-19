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

class CoreElearningInterface implements StudipModule {
    
    function getIconNavigation($course_id, $last_visit) {
        if (get_config('ELEARNING_INTERFACE_ENABLE')) {
            $navigation = new Navigation(_('Lernmodule'), "seminar_main.php?auswahl=".$course_id."&redirect_to=elearning_interface.php&view=show");
            $navigation->setImage('icons/16/grey/wiki.png');

            return $navigation;
        } else {
            return null;
        }
    }
    
    function getTabNavigation($course_id) {
        if (get_config('ELEARNING_INTERFACE_ENABLE')) {
            $navigation = new Navigation(_('Lernmodule'));
            $navigation->setImage('icons/16/white/learnmodule.png');
            $navigation->setActiveImage('icons/16/black/learnmodule.png');

            if (ObjectConnections::isConnected($course_id)) {
                $elearning_nav = new Navigation(_('Lernmodule dieser Veranstaltung'), 'elearning_interface.php?view=show&seminar_id=' . $course_id);

                if ($sem_class == 'inst') {
                    $elearning_nav->setTitle(_('Lernmodule dieser Einrichtung'));
                }

                $navigation->addSubNavigation('show', $elearning_nav);
            }

            if ($GLOBALS['perm']->have_studip_perm('tutor', $_SESSION['SessionSeminar'])) {
                $navigation->addSubNavigation('edit', new Navigation(_('Lernmodule hinzufügen / entfernen'), 'elearning_interface.php?view=edit&seminar_id=' . $course_id));
            }

            return array('elearning' => $navigation);
        } else {
            return null;
        }
    }
    
}
