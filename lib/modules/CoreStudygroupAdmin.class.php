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
    
    function getIconNavigation($course_id, $last_visit, $user_id) {
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
            $navigation->addSubNavigation('avatar', new Navigation(_('Avatar'), 'dispatch.php/course/avatar/update/'.$course_id));
            
            if (!$GLOBALS['perm']->have_perm('admin')) {
                if (get_config('VOTE_ENABLE')) {
                    $item = new Navigation(_('Umfragen und Tests'), 'admin_vote.php?view=vote_sem');
                    $item->setDescription(_('Erstellen und bearbeiten Sie einfache Umfragen und Tests.'));
                    $navigation->addSubNavigation('vote', $item);

                    $item = new Navigation(_('Evaluationen'), 'admin_evaluation.php?view=eval_sem');
                    $item->setDescription(_('Richten Sie fragebogenbasierte Umfragen und Lehrevaluationen ein.'));
                    $navigation->addSubNavigation('evaluation', $item);
                }
            }
            return array('admin' => $navigation);
        } else {
            return array();
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
