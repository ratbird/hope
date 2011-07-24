<?php

class Step228Raumanfragen extends Migration
{
    function description ()
    {
        return 'adds field metadate_id to resources_requests';
    }

    function up ()
    {
        $db = DBManager::get();
        $db->exec(
            "ALTER TABLE `resources_requests` ADD `metadate_id` VARCHAR( 32 ) NOT NULL DEFAULT '' AFTER `termin_id`");
        SimpleORMap::expireTableScheme();
    }

    function down ()
    {
        $db = DBManager::get();
        $db->exec(
            "ALTER TABLE `resources_requests` " .
            "DROP COLUMN `metadate_id`");
    }
}