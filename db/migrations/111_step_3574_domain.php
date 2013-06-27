<?php

class Step3574domain extends Migration {

    function description() {
        return 'modify auto_insert_sem according to Step03574';
    }

    function up() {
        $query = "ALTER TABLE `auto_insert_sem` ADD `domain_id` VARCHAR( 45 ) NOT NULL DEFAULT ''";
        DBManager::get()->exec($query);
    }

    function down() {
        $query = "ALTER TABLE `auto_insert_sem` DROP `domain_id`";
        DBManager::get()->exec($query);
    }

}