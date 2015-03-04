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
    
    function getIconNavigation($course_id, $last_visit, $user_id) {
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
            return array('wiki' => $navigation);
        } else {
            return null;
        }
    }
    
    function getNotificationObjects($course_id, $since, $user_id)
    {
        $items = array();
        $type = get_object_type($course_id, array('sem', 'inst', 'fak'));
        
        if ($type == 'sem') {
            $query = 'SELECT wiki.*, seminare.Name, '. $GLOBALS['_fullname_sql']['full'] .' as fullname
                FROM wiki
                JOIN auth_user_md5 USING (user_id)
                JOIN user_info USING (user_id)
                JOIN seminar_user ON (range_id = Seminar_id)
                JOIN seminare USING (Seminar_id)
                WHERE seminar_user.user_id = ? AND Seminar_id = ? 
                    AND wiki.chdate > ?';
        } else {
            $query = 'SELECT wiki.*, Institute.Name, '. $GLOBALS['_fullname_sql']['full'] .' as fullname
                FROM wiki
                JOIN auth_user_md5 USING (user_id)
                JOIN user_info USING (user_id)
                JOIN user_inst ON (range_id = Institut_id)
                JOIN Institute USING (Institut_id)
                WHERE user_inst.user_id = ? AND Institut_id = ? 
                    AND wiki.chdate > ?';
        }
        
        $wikipage_stmt = DBManager::get()->prepare("SELECT * FROM wiki
            WHERE keyword = ? AND range_id = ?
                AND version = ?");
        
        $stmt = DBManager::get()->prepare($query);
        $stmt->execute(array($user_id, $course_id, $since));
        
        while ($row = $stmt->fetch()) {
            // use correct text depending on type of object
            if ($type == 'sem') {
                if ($row['version'] > 1) {
                    $summary = sprintf('%s hat im Wiki der Veranstaltung "%s" die Seite "%s" geändert.',
                        $row['fullname'], $row['Name'], $row['keyword']);
                } else {
                    $summary = sprintf('%s hat im Wiki der Veranstaltung "%s" die Seite "%s" erstellt.',
                        $row['fullname'], $row['Name'], $row['keyword']);
                }
            } else {
                if ($row['version'] > 1) {
                    $summary =  sprintf('%s hat im Wiki der Einreichtung "%s" die Seite "%s" geändert.',
                        $row['fullname'], $row['Name'], $row['keyword']);
                } else {
                    $summary =  sprintf('%s hat im Wiki der Einreichtung "%s" die Seite "%s" erstellt.',
                        $row['fullname'], $row['Name'], $row['keyword']);
                }
            }

            $content = '';
            if ($row['version'] > 1) {
                $wikipage_stmt->execute(array($row['keyword'], $row['range_id'], $row['version'] - 1));
                $old_page = $wikipage_stmt->fetch(PDO::FETCH_ASSOC);
                $content = '<table>' . do_diff($old_page['body'], $row['body']) .'</table>';
            } else {
                $content = wikiReady($row['body']);
            }
            
            $items[] = new ContentElement(
                'Wiki: ' . $row['keyword'], $summary, $content, $row['user_id'], $row['fullname'],
                URLHelper::getLink('wiki.php',
                    array('cid' => $row['range_id'], 'keyword' => $row['keyword'])),
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
            'summary' => _('Gemeinsames asynchrones Erstellen und Bearbeiten von Texten'),
            'description' => _('Im Wiki-Web oder kurz "Wiki" können '.
                'verschiedene AutorInnen gemeinsam Texte, Konzepte und andere '.
                'schriftliche Arbeiten erstellen und gestalten, dies '.
                'allerdings nicht gleichzeitig. Texte können individuell '.
                'bearbeitet und die Änderungen gespeichert werden. Das '.
                'Besondere im Wiki ist, dass Studierende und Lehrende '.
                'annähernd die gleichen Rechte (schreiben, lesen, ändern, '.
                'löschen) haben, was sich nicht einschränken lässt. Das '.
                'System erstellt eine Versionshistorie, mit der Änderungen '.
                'nachvollziehbar werden. Einzelne Versionen können zudem '.
                'auch gelöscht werden (nur Lehrende). Ein Export als '.
                'pdf-Datei ist integriert.'),
            
            'displayname' => _('Wiki-Web'),
        	'keywords' => _('Individuelle Bearbeitung von Texten;
							Versionshistorie;
							Druckansicht und PDF-Export;
							Löschfunktion für die aktuellste Seiten-Version;
							Keine gleichzeitige Bearbeitung desselben Textes möglich, nur nacheinander'),
            'descriptionshort' => _('Gemeinsames asynchrones Erstellen und Bearbeiten von Texten'),
            'descriptionlong' => _('Im Wiki-Web oder kurz "Wiki" können verschiedene AutorInnen gemeinsam Texte, '.
            						'Konzepte und andere schriftliche Arbeiten erstellen und gestalten. Dies '.
            						'allerdings nicht gleichzeitig. Texte können individuell bearbeitet und '.
            						'gespeichert werden. Das Besondere im Wiki ist, dass Studierende und Lehrende '.
            						'annähernd die gleichen Rechte (schreiben, lesen, ändern, löschen) haben, was '.
            						'gegenseitiges Vertrauen voraussetzt. Das System erstellt eine Versionshistorie, '.
            						'mit der Änderungen nachvollziehbar werden. Einzelne Versionen können zudem auch '.
            						'gelöscht werden (nur Lehrende). Eine Druckansicht und eine Exportmöglichkeit als '.
            						'PDF-Datei ist integriert.'),
			'category' => _('Kommunikation und Zusammenarbeit'),
			'icon' => 'icons/16/black/wiki.png',
        	'screenshot' => 'plus/screenshots/Wiki-Web/Gemeinsam_erstellte_Texte.jpg'                
        );
    }
}
