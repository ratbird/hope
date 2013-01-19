<?php

class AddForumEntriesIssuesTable extends DBMigration {
    function up() {
        DBManager::get()->exec("CREATE TABLE IF NOT EXISTS `forum_entries_issues` (
            `topic_id` varchar(32) NOT NULL,
            `issue_id` varchar(32) NOT NULL,
            PRIMARY KEY (`topic_id`,`issue_id`)
        )");
    }
    
    function down() {
    }
}