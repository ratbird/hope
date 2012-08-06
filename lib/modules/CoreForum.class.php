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

class CoreForum implements StudipModule {
    
    function getIconNavigation($course_id, $last_visit) {
        $navigation = new Navigation(_('Forum'), "seminar_main.php?auswahl=$course_id&redirect_to=forum.php&view=reset&sort=age");
        $navigation->setImage('icons/16/grey/forum.png');

        return $navigation;
    }
    
    function getTabNavigation($course_id) {
        global $forum;
        $sem_class = $GLOBALS['SEM_CLASS'][$GLOBALS['SEM_TYPE'][$GLOBALS['SessSemName']['art_num']]['class']];
        $navigation = new Navigation(_('Forum'), "forum.php?view=reset");
        $navigation->setImage('icons/16/white/forum.png');
        $navigation->setActiveImage('icons/16/black/forum.png');

        $navigation->addSubNavigation('view', new Navigation(_('Themenansicht'), 'forum.php?view='.$forum['themeview']));

        if ($user->id != 'nobody') {
            $navigation->addSubNavigation('unread', new Navigation(_('Neue Beiträge'), 'forum.php?view=neue&sort=age'));
        }

        $navigation->addSubNavigation('recent', new Navigation(_('Letzte Beiträge'), 'forum.php?view=flat&sort=age'));
        $navigation->addSubNavigation('search', new Navigation(_('Suchen'), 'forum.php?view=search&reset=1'));
        $navigation->addSubNavigation('export', new Navigation(_('Druckansicht'), 'forum_export.php'));

        if ($GLOBALS['perm']->have_studip_perm('tutor', $_SESSION['SessionSeminar']) || $sem_class['topic_create_autor']) {
            $navigation->addSubNavigation('create_topic', new Navigation(_('Neues Thema anlegen'), 'forum.php?view='.$forum['themeview'].'&neuesthema=TRUE#anker'));
        }
        return array('forum' => $navigation);
    }
    
}
