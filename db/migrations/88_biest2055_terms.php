<?php

class Biest2055Terms extends Migration
{
    function description ()
    {
        return 'add TERMS_ACCEPTED user config';
    }

    function up ()
    {
        $sql = "INSERT INTO `user_config` ".
            "(`userconfig_id`, `parent_id`, `user_id`, `field`, `value`, `mkdate`, `chdate`, `comment`) ".
            "VALUES " . join(",", $this->getValues());
        DBManager::get()->exec($sql);
    }

    function down ()
    {
        $sql = 'DELETE FROM `user_config` WHERE `field` = "TERMS_ACCEPTED"';
        DBManager::get()->exec($sql);
    }

    function getValues()
    {
        return array_map(array($this, 'getValue'), $this->getUserIds());
    }

    function getValue($id)
    {
        $now = time();
        $field = 'TERMS_ACCEPTED';
        return sprintf("('%s', NULL, '%s', '%s', '1', %d, %d, '')",
                       md5($id . "TERMS_ACCEPTED"),
                       $id, $field, $now, $now);

    }

    function getUserIds()
    {
        return DBManager::get()->query("SELECT user_id FROM auth_user_md5")->fetchAll(PDO::FETCH_COLUMN);
    }
}