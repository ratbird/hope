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
            $navigation = new Navigation(_('Ablaufplan'), URLHelper::getURL("seminar_main.php", array('auswahl' => $course_id, 'redirect_to' => "dispatch.php/course/dates")));
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
                $summary = sprintf('%s hat in der Veranstaltung "%s" die Informationsseite "%s" geändert.',
                    $row['fullname'], $row['Name'], $row['tab_name']);
            } else {
                $summary = sprintf('%s hat in der Einreichtung "%s" die Informationsseite "%s" geändert.',
                    $row['fullname'], $row['Name'], $row['tab_name']);
            }

            $link = URLHelper::getLink('dispatch.php/course/scm/' . $row['scm_id'],
                                       array('cid' => $row['range_id']));

            $items[] = new ContentElement(
                'Freie Informationsseite: ' . $row['tab_name'],
                $summary,
                formatReady($row['content']),
                $row['user_id'],
                $row['fullname'],
                $link,
                $row['chdate']
            );
        }

        return $items;
    }

    /** 
     * @see StudipModule::getMetadata()
     */ 
    function getMetadata()
    {
        return array(
            'summary' => _('Die Lehrenden bestimmen, wie Titel und Inhalt dieser Seite aussehen.'),
            'description' => _('Die Freie Informationsseite ist eine Seite, '.
                'die sich die Lehrenden nach ihren speziellen Anforderungen '.
                'einrichten können. So kann z.B. der Titel im Kartenreiter '.
                'selbst definiert werden. Ferner können beliebig viele '.
                'Einträge im Untermenü vorgenommen werden. Für jeden Eintrag '.
                'öffnet sich eine Seite mit einem Text-Editor, in den '.
                'beliebiger Text eingegeben und formatiert werden kann. Oft '.
                'wird die Seite für die Angabe von Literatur genutzt als '.
                'Alternative zum Plugin Literatur. Sie kann aber auch für '.
                'andere beliebige Zusatzinformationen (Links, Protokolle '.
                'etc.) verwendet werden.'),
            'displayname' => _('Informationen'),
            'category' => _('Lehr- und Lernorganisation'),
        	'keywords' => _('Raum für eigene Informationen;
							Name des Reiters frei definierbar;
							Beliebig erweiterbar durch zusätzliche "neue Einträge"'),
            'descriptionshort' => _('Freie Gestaltung von Reiternamen und Inhalten durch Lehrende.'),
            'descriptionlong' => _('Diese Seite kann von Lehrenden nach ihren speziellen Anforderungen eingerichtet werden. '.
            						'So ist z.B. der Titel im Reiter frei definierbar. Ferner können beliebig viele neue '.
            						'Eintragsseiten eingefügt werden. Für jeden Eintrag öffnet sich eine Seite mit einem '.
            						'Text-Editor, in den beliebiger Text eingefüft, eingegeben und formatiert werden kann. '.
            						'Oft wird die Seite für die Angabe von Literatur genutzt als Alternative zur Funktion '.
            						'Literatur. Sie kann aber auch für andere beliebige Zusatzinformationen (Links, Protokolle '.
            						'etc.) verwendet werden.'),
        	'icon' => 'icons/16/black/infopage.png',
        	'screenshots' => array(
        		'path' => 'plus/screenshots/Freie_Informationsseite',
        		'pictures' => array(
        			0 => array('source' => 'Zwei_Eintraege_mit_Inhalten_zur_Verfuegung_stellen.jpg', 'title' => 'Zwei Einträge mit Inhalten zur Verfügung stellen'),
        			1 => array( 'source' => 'Neue_Informationsseite_anlegen.jpg', 'title' => 'Neue Informationsseite anlegen')
        		)
        	)     	
        );
    }
}
