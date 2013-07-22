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
    
    function getIconNavigation($course_id, $last_visit, $user_id) {
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
            $temp = StudipScmEntry::findByRange_id($course_id, 'ORDER BY position ASC');
            $scms = SimpleORMapCollection::createFromArray($temp);

            $navigation = new Navigation($scms->first()->tab_name ?: _('Informationen'));
            $navigation->setImage('icons/16/white/infopage.png');
            $navigation->setActiveImage('icons/16/black/infopage.png');

            foreach ($scms as $scm) {
                $scm_link = URLHelper::getLink('dispatch.php/course/scm/' . $scm->id);
                $nav = new Navigation($scm['tab_name'], $scm_link);
                $navigation->addSubNavigation($scm->id, $nav);
            }

            if ($GLOBALS['perm']->have_studip_perm('tutor', $course_id)) {
                $scm_link = URLHelper::getLink('dispatch.php/course/scm/create');
                $nav = new Navigation(_('Neuen Eintrag anlegen'), $scm_link);
                $navigation->addSubNavigation('new_entry', $nav);
            }

            return array('scm' => $navigation);
        } else {
            return null;
        }
    }
 
    function getNotificationObjects($course_id, $since, $user_id)
    {
        $items = array();
        $type = get_object_type($course_id, array('sem', 'inst', 'fak'));
        
        if ($type == 'sem') {
            $query = 'SELECT scm.*, seminare.Name, '. $GLOBALS['_fullname_sql']['full'] .' as fullname
                FROM scm
                JOIN auth_user_md5 USING (user_id)
                JOIN user_info USING (user_id)
                JOIN seminar_user ON (range_id = Seminar_id)
                JOIN seminare USING (Seminar_id)
                WHERE seminar_user.user_id = ? AND Seminar_id = ? 
                    AND scm.chdate > ?';
        } else {
            $query = 'SELECT scm.*, Institute.Name, '. $GLOBALS['_fullname_sql']['full'] .' as fullname
                FROM scm
                JOIN auth_user_md5 USING (user_id)
                JOIN user_info USING (user_id)
                JOIN user_inst ON (range_id = Institut_id)
                JOIN Institute USING (Institut_id)
                WHERE user_inst.user_id = ? AND Institut_id = ? 
                    AND scm.chdate > ?';
        }
        
        $stmt = DBManager::get()->prepare($query);
        $stmt->execute(array($user_id, $course_id, $since));
        
        while ($row = $stmt->fetch()) {
            // use correct text depending on type of object
            if ($type == 'sem') {
                $summary = sprintf('%s hat in der Veranstaltung "%s" die Informationsseite "%s" ge�ndert.',
                    $row['fullname'], $row['Name'], $row['tab_name']);
            } else {
                $summary = sprintf('%s hat in der Einreichtung "%s" die Informationsseite "%s" ge�ndert.',
                    $row['fullname'], $row['Name'], $row['tab_name']);
            }

            $link = URLHelper::getLink('dispatch.php/course/scm/' . $row['scm_id'],
                                       array('cid' => $row['range_id']));

            $items[] = new ContentElement(
                'Info: ' . $row['tab_name'],
                $summary,
                $row['content'] ?: '',
                $row['user_id'],
                $row['fullname'],
                $link,
                $row['chdate']
            );
        }

        return $items;
    }
}
