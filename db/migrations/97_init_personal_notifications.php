<?php

class InitPersonalNotifications extends Migration
{

    function description()
    {
        return 'inserts two tables for personal notifications';
    }

    function up()
    {
        $db = DBManager::get();
        $db->exec("
            CREATE TABLE IF NOT EXISTS `personal_notifications` (
                `personal_notification_id` varchar(32) NOT NULL,
                `url` text NOT NULL,
                `text` text NOT NULL,
                `avatar` varchar(256) NULL,
                `html_id` varchar(64) NULL,
                `mkdate` int(11) NOT NULL
        ) ENGINE=MyISAM;");
        $db->exec("
            CREATE TABLE IF NOT EXISTS `personal_notifications_user` (
            `personal_notification_id` varchar(32) NOT NULL,
            `user_id` varchar(32) NOT NULL,
            `seen` int(1) DEFAULT '0' NOT NULL
        ) ENGINE=MyISAM");
    }

    function down()
    {
        $db = DBManager::get();
        $db->exec("DROP TABLE IF EXISTS `personal_notifications` ");
        $db->exec("DROP TABLE IF EXISTS `personal_notifications_user` ");
    }
}
