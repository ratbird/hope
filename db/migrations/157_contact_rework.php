<?php

class ContactRework extends Migration {

    function description() {
        return 'Make the usage of contacts more simple';
    }

    function up() {
        DBManager::get()->exec("DROP TABLE IF EXISTS contact_userinfo");
        DBManager::get()->exec("ALTER IGNORE TABLE contact DROP buddy, DROP contact_id, DROP INDEX owner_id, ADD PRIMARY KEY(owner_id, user_id);");
        Config::get()->delete("FOAF_ENABLE");
        Config::get()->delete("FOAF_SHOW_IDENTITY");
        Contact::expireTableScheme();
    }

    function down() {
        DBManager::get()->exec("CREATE TABLE IF NOT EXISTS `contact_userinfo` (
        `userinfo_id` varchar(32) NOT NULL DEFAULT '',
        `contact_id` varchar(32) NOT NULL DEFAULT '',
        `name` varchar(255) NOT NULL DEFAULT '',
        `content` text NOT NULL,
        `priority` int(11) NOT NULL DEFAULT '0',
        PRIMARY KEY (`userinfo_id`),
        KEY `contact_id` (`contact_id`),
        KEY `priority` (`priority`)
      ) ENGINE=MyISAM;");
        DBManager::get()->exec("ALTER TABLE contact ADD COLUMN buddy tinyint(4) NOT NULL DEFAULT '1'");
    }

}
