<?php

class Step00234HomepagePluginActivation extends Migration
{

    function description()
    {
        return 'configuration entry for default homepage plugin activation';
    }

    function up()
    {
        $db = DBManager::get();
        $query = $db->prepare("INSERT INTO `config` (`config_id`, `parent_id`, `field`, `value`, `is_default`, `type`, `range`, `section`, `position`, `mkdate`, `chdate`, `description`, `comment`, `message_template`) VALUES (MD5(?), '', ?, ?, '1', ?, 'global', 'privacy', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), ?, '', '')");

        // insert new configuration entry
        $query->execute(array('HOMEPAGEPLUGIN_DEFAULT_ACTIVATION', 'HOMEPAGEPLUGIN_DEFAULT_ACTIVATION', 1, 'boolean', 'Sollen neu installierte Homepageplugins automatisch für Benutzer aktiviert sein?'));

    }

    function down()
    {
        $db = DBManager::get();
        $query = $db->prepare("DELETE FROM `config` WHERE `field` = ?");

        $query->execute(array('HOMEPAGEPLUGIN_DEFAULT_ACTIVATION'));

    }
}
?>
