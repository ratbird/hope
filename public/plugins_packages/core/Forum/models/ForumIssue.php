<?php

/**
 * filename - Short description for file
 *
 * Long description for file (if any)...
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 3 of
 * the License, or (at your option) any later version.
 *
 * @author      Till Glöggler <tgloeggl@uos.de>
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GPL version 3
 * @category    Stud.IP
 */

class ForumIssue
{
    static function getThreadIdForIssue($issue_id)
    {
        $stmt = DBManager::get()->prepare("SELECT topic_id FROM forum_entries_issues
            WHERE issue_id = ?");
        $stmt->execute(array($issue_id));
        
        return ($stmt->fetchColumn());
    }
    
    static function setThreadForIssue($seminar_id, $issue_id, $title, $content)
    {
        if ($topic_id = self::getThreadIdForIssue($issue_id)) {   // update
            ForumEntry::update($topic_id, $title ?: _('Kein Titel'), $content);

        } else {                                                  // create
            $topic_id = md5(uniqid(rand()));

            ForumEntry::insert(array(
                'topic_id'    => $topic_id,
                'seminar_id'  => $seminar_id,
                'user_id'     => $GLOBALS['user']->id,
                'name'        => $title ?: _('Kein Titel'),
                'content'     => $content,
                'author'      => get_fullname($GLOBALS['user']->id),
                'author_host' => getenv('REMOTE_ADDR')
            ), $seminar_id);
            
            $stmt = DBManager::get()->prepare("INSERT INTO forum_entries_issues
                (issue_id, topic_id) VALUES (?, ?)");
            $stmt->execute(array($issue_id, $topic_id));
        }
    }
    
    static function unlinkIssue($notification, $topic_id)
    {
        $stmt = DBManager::get()->prepare("DELETE FROM forum_entries_issues
            WHERE topic_id = ?");
        $stmt->execute(array($topic_id));
    }
}