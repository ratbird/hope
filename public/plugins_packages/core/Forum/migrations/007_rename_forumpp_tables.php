<?php

class RenameForumPPTables extends DBMigration {
    function up() {
        DBManager::get()->exec("RENAME TABLE 
            forumpp_abo_users           TO forum_abo_users,
            forumpp_categories          TO forum_categories,
            forumpp_categories_entries  TO forum_categories_entries,
            forumpp_entries             TO forum_entries,
            forumpp_favorites           TO forum_favorites,
            forumpp_likes               TO forum_likes,
            forumpp_visits              TO forum_visits");
    }
    
    function down() {
    }
}
