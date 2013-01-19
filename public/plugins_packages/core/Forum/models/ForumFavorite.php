<?php

class ForumFavorite {
    static function set($topic_id) {
        $stmt = DBManager::get()->prepare("REPLACE INTO
            forum_favorites (topic_id, user_id)
            VALUES (?, ?)");
        $stmt->execute(array($topic_id, $GLOBALS['user']->id));
    }
    
    static function remove($topic_id) {
        $stmt = DBManager::get()->prepare("DELETE FROM forum_favorites
            WHERE topic_id = ? AND user_id = ?");
        $stmt->execute(array($topic_id, $GLOBALS['user']->id));        
    }
}