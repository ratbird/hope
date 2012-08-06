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

class CoreLiterature implements StudipModule {
    
    function getIconNavigation($course_id, $last_visit) {
        if (get_config('LITERATURE_ENABLE')) {
            $navigation = new Navigation(_('TeilnehmerInnen'), "seminar_main.php?auswahl=".$course_id."&redirect_to=teilnehmer.php");
            $navigation->setImage('icons/16/grey/persons.png');
            return $navigation;
        } else {
            return null;
        }
    }
    
    function getTabNavigation($course_id) {
        if (get_config('LITERATURE_ENABLE')) {
            $object_type = get_object_type($course_id);
            $navigation = new Navigation(_('Literatur'));
            $navigation->setImage('icons/16/white/literature.png');
            $navigation->setActiveImage('icons/16/black/literature.png');

            $navigation->addSubNavigation('view', new Navigation(_('Literatur'), "literatur.php?view=literatur_".$object_type));
            $navigation->addSubNavigation('print', new Navigation(_('Druckansicht'), 'lit_print_view.php?_range_id=' . $course_id));

            if ($GLOBALS['perm']->have_studip_perm('tutor', $course_id)) {
                $navigation->addSubNavigation('edit', new Navigation(_('Literatur bearbeiten'), 'admin_lit_list.php?view=literatur_'.$object_type.'&new_'.$object_type.'=TRUE&_range_id='. $course_id));
            }

            return array('literature' => $navigation);
        } else {
            return null;
        }
    }
    
}
