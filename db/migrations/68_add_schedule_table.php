<?php

class AddScheduleTable extends Migration
{
    function description ()
    {
        return 'add schedule db-table for new schedule';
    }

    function up ()
    {
        // create new multi-purpose schedule table
        DBManager::get()->exec("
            CREATE TABLE `schedule` (
                `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                `start` INT NOT NULL COMMENT 'start hour and minutes',
                `end` INT NOT NULL COMMENT 'end hour and minutes',
                `day` INT NOT NULL COMMENT 'day of week, 0-6',
                `title` VARCHAR( 255 ) NOT NULL ,
                `content` VARCHAR( 255 ) NOT NULL ,
                `color` VARCHAR( 7 ) NOT NULL COMMENT 'color, rgb in hex' ,
                `user_id` VARCHAR( 32 ) NOT NULL
            )
        ");

        DBManager::get()->exec("
            CREATE TABLE `schedule_seminare` (
                `user_id` VARCHAR( 32 ) NOT NULL ,
                `seminar_id` VARCHAR( 32 ) NOT NULL ,
                `metadate_id` VARCHAR( 32 ) NOT NULL ,
                `visible` BOOLEAN NOT NULL DEFAULT '1' ,
                `color` VARCHAR( 7 ) NULL COMMENT 'color, rgb in hex',
                PRIMARY KEY ( `user_id` , `seminar_id`, `metadate_id` )
            ) ENGINE = MYISAM ;
        ");

        // move old "virtual" entries to new table
        $db = DBManager::get()->query("SELECT sus.* FROM seminar_user_schedule as sus
            LEFT JOIN seminare as s ON (s.Seminar_id = sus.range_id)
            WHERE s.Seminar_id IS NOT NULL");

        $stmt = DBManager::get()->prepare("INSERT IGNORE INTO schedule_seminare
            (user_id, seminar_id, metadate_id) VALUES(?, ?, ?)");

        while ($data = $db->fetch()) {
            $sem = new Seminar($data['range_id']);
            foreach ($sem->getCycles() as $cycle) {
                $stmt->execute(array($data['user_id'], $data['range_id'], $cycle->getMetaDateID()));
            }
            unset($sem);
        }

    }

    function down ()
    {
        DBManager::get()->query("DROP TABLE schedule");
        DBManager::get()->query("DROP TABLE schedule_seminare");
    }
}
