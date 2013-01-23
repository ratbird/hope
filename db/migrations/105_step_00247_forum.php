<?php

class Step00247Forum extends Migration
{
    function description()
    {
        return 'Add forum as core-plugin';
    }

    function up()
    {
        DBManager::get()->exec("UPDATE schema_version
            SET domain = 'Forum' WHERE domain = 'ForumPP'");
        
        // plugin meta data
        $plugin_manager = PluginManager::getInstance();
        $plugin_id = $plugin_manager->registerPlugin('Forum', 'CoreForum', 'core/Forum');
        $plugin_manager->setPluginEnabled($plugin_id, true);
        
        $plugin_manager->setDefaultActivations($plugin_id,
            DBManager::get()->query("SELECT Institut_id FROM Institute")->fetchAll(PDO::FETCH_COLUMN));
        
        $plugindir = get_config('PLUGINS_PATH') .'/core/Forum';

        if (is_dir($plugindir.'/migrations')) {
            $schema_version = new DBSchemaVersion('Forum');
            $migrator = new Migrator($plugindir.'/migrations', $schema_version);
            $migrator->migrate_to(null);
        }
        
        // remove old ForumPP-plugin
        $old_forum = DBManager::get()->query("SELECT * FROM plugins 
            WHERE pluginclassname = 'ForumPP'")->fetch(PDO::FETCH_ASSOC);
    
        if ($old_forum && $old_forum['pluginpath'] !== "core/Forum") {
            // @rmdirr($GLOBALS['PLUGINS_PATH'] . '/' . $old_forum['pluginpath']);
        }
        
        if ($old_forum) {
            DBManager::get()->exec("DELETE FROM plugins 
                WHERE pluginclassname = 'ForumPP'");
            DBManager::get()->exec("DELETE FROM plugins_activated
                WHERE pluginid = " . $old_forum['pluginid']);
            DBManager::get()->exec("DELETE FROM plugins_default_activations 
                WHERE pluginid = " . $old_forum['pluginid']);
            DBManager::get()->exec("DELETE FROM roles_plugins
                WHERE pluginid = " . $old_forum['pluginid']);
        }

        // remove old forum settings
        UserConfig::get($this->user->user_id)->delete('FORUM_SETTINGS');        
    }

    function down()
    {
    }
}
