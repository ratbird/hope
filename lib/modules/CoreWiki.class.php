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

class CoreWiki implements StudipModule {
    
    function getIconNavigation($course_id, $last_visit) {
        if (get_config('WIKI_ENABLE')) {
            $navigation = new Navigation(_('Wiki'), "seminar_main.php?auswahl=".$course_id."&redirect_to=wiki.php");
            $navigation->setImage('icons/16/grey/wiki.png');
            return $navigation;
        } else {
            return null;
        }
    }
    
    function getTabNavigation($course_id) {
        if (get_config('WIKI_ENABLE')) {
            $navigation = new Navigation(_('Wiki'));
            $navigation->setImage('icons/16/white/wiki.png');
            $navigation->setActiveImage('icons/16/black/wiki.png');

            $navigation->addSubNavigation('show', new Navigation(_('WikiWikiWeb'), 'wiki.php?view=show'));
            $navigation->addSubNavigation('listnew', new Navigation(_('Neue Seiten'), 'wiki.php?view=listnew'));
            $navigation->addSubNavigation('listall', new Navigation(_('Alle Seiten'), 'wiki.php?view=listall'));
            $navigation->addSubNavigation('export', new Navigation(_('Export'), 'wiki.php?view=export'));
            return array('wiki' => $navigation);
        } else {
            return null;
        }
    }
    
}
