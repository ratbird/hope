<?php

class AddIndexToForumEntries extends DBMigration {
    function up() {
        DBManager::get()->exec("ALTER TABLE `forum_entries` ADD INDEX (  `seminar_id` ,  `lft` )");
        DBManager::get()->exec("ALTER TABLE `forum_entries` ADD INDEX (  `seminar_id` ,  `rgt` )");
        DBManager::get()->exec("ALTER TABLE `forum_entries` ADD INDEX (  `user_id` )");

        DBManager::get()->exec("ALTER TABLE `forum_categories` ADD INDEX (  `seminar_id` )");
    }

    function down() {
    }
}