<?php

class Step216AutomatisiertesEintragen extends Migration
{
    function description ()
    {
        return 'add new root view: automatic user insert';
    }

    function up ()
    {
        // create new auto_insert_sem table
        // seminar_id : Seminar_id
        // status : Stud.IP user status (root,admin,dozent etc.)
        DBManager::get()->exec("
            CREATE TABLE IF NOT EXISTS `auto_insert_sem` (
                `seminar_id` char(32) NOT NULL,
                `status` varchar(32) NOT NULL,
                PRIMARY KEY  (`seminar_id`,`status`)
                ) ENGINE=MyISAM ;
        ");

        DBManager::get()->exec("
           CREATE TABLE IF NOT EXISTS `auto_insert_user` (
                `seminar_id` char(32) NOT NULL,
                `user_id` char(32) NOT NULL,
                `mkdate` DATETIME,
                PRIMARY KEY  (`seminar_id`,`user_id`)
                ) ENGINE=MyISAM ;
        ");

        $options[] =
            array(
            'name'        => 'AUTO_INSERT_SEM_PARTICIPANTS_VIEW_PERM',
            'type'        => 'boolean',
            'value'       => 0,
            'section'     => 'global',
            'description' => 'Sollen automatisch eingetragene Nutzer in Veranstaltungen auch den Teilnehmerreiter sehen? (TRUE =sichtbar, FALSE= versteckt)'
            );

        $stmt = DBManager::get()->prepare("
                INSERT IGNORE INTO config
                    (config_id, field, value, is_default, type, section, mkdate, chdate, description)
                VALUES
                    (MD5(:name), :name, :value, 1, :type, :section, UNIX_TIMESTAMP(),  UNIX_TIMESTAMP(), :description)
                ");

        foreach ($options as $option) {
            $stmt->execute($option);
        }

    }

    function down ()
    {
        DBManager::get()->query("DROP TABLE auto_insert_sem");
        DBManager::get()->query("DROP TABLE auto_insert_user");
    }
}
