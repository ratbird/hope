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

class CoreDocuments implements StudipModule {
    
    function getIconNavigation($course_id, $last_visit) {
        $navigation = new Navigation(_('Dateibereich'), "seminar_main.php?auswahl=$course_id&redirect_to=forum.php&view=reset&sort=age");
        $navigation->setImage('icons/16/grey/forum.png');

        return $navigation;
    }
    
    function getTabNavigation($course_id) {
        $navigation = new Navigation(_('Dateien'));
        $navigation->setImage('icons/16/white/files.png');
        $navigation->setActiveImage('icons/16/black/files.png');

        $navigation->addSubNavigation('tree', new Navigation(_('Ordneransicht'), "folder.php?cmd=tree"));
        $navigation->addSubNavigation('all', new Navigation(_('Alle Dateien'), "folder.php?cmd=all"));
        return array('files' => $navigation);
    }
    
}
