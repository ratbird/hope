<?php

class InitPlugin extends DBMigration {

    function up() {
        $db = DBManager::get();
        $stmt = $db->prepare("
        CREATE TABLE IF NOT EXISTS `media_links` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `course_id` varchar(32)  NOT NULL,
            `name` varchar(255) NOT NULL,
            `url` varchar(255)  NOT NULL,
            `description` TEXT  NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=MyISAM ");
        $stmt->execute();
    }

}