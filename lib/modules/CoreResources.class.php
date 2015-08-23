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
require_once 'lib/resources/resourcesFunc.inc.php';

class CoreResources implements StudipModule {
    
    function getIconNavigation($course_id, $last_visit, $user_id) {
        $navigation = new Navigation(_('Ressourcen'), "seminar_main.php?auswahl=".$course_id."&redirect_to=wiki.php");
        $navigation->setImage('icons/16/grey/resources.png');

        return $navigation;
    }
    
    function getTabNavigation($course_id) {
        if (get_config('RESOURCES_ENABLE') && checkAvailableResources($course_id)) {
            $navigation = new Navigation(_('Ressourcen'), 'resources.php?view=openobject_main&view_mode=oobj');
            $navigation->setImage('icons/16/white/resources.png');
            $navigation->setActiveImage('icons/16/black/resources.png');

            $navigation->addSubNavigation('overview', new Navigation(_('�bersicht'), 'resources.php?view=openobject_main'));
            $navigation->addSubNavigation('group_schedule', new Navigation(_('�bersicht Belegung'), 'resources.php?view=openobject_group_schedule'));
            $navigation->addSubNavigation('view_details', new Navigation(_('Details'), 'resources.php?view=openobject_details'));
            $navigation->addSubNavigation('view_schedule', new Navigation(_('Belegung'), 'resources.php?view=openobject_schedule'));
            $navigation->addSubNavigation('edit_assign', new Navigation(_('Belegungen bearbeiten'), 'resources.php?view=openobject_assign'));
            return array('resources' => $navigation);
        } else {
            return null;
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
