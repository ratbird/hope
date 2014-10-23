<?php
/**
 * Adds admission rule CourseMemberAdmission
 */
class Tic5117CourseMemberAdmission extends Migration
{
    function description()
    {
        return 'Adds admission rule CourseMemberAdmission';
    }

    function up()
    {
        DBManager::get()->exec("
            CREATE TABLE IF NOT EXISTS `coursememberadmissions` (
              `rule_id` varchar(32),
              `message` text NOT NULL,
              `start_time` int(11) NOT NULL DEFAULT 0,
              `end_time` int(11) NOT NULL DEFAULT 0,
              `course_id` varchar(32) NOT NULL DEFAULT '',
              `mkdate` int(11) NOT NULL DEFAULT 0,
              `chdate` int(11) NOT NULL DEFAULT 0,
              PRIMARY KEY (`rule_id`)
            ) ENGINE=MyISAM
        ");
        DBManager::get()->exec("INSERT IGNORE INTO `admissionrules` (`id`, `ruletype`, `active`, `mkdate`)
                                VALUES (NULL, 'CourseMemberAdmission', '1', UNIX_TIMESTAMP())");
    }

    function down()
    {
        DBManager::get()->exec("DROP TABLE `coursememberadmissions`");
        DBManager::get()->exec("DELETE FROM `admissionrules` WHERE `ruletype` = 'CourseMemberAdmission'");
    }
}
