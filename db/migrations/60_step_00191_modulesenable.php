<?php
class Step00191ModulesEnable extends Migration
{
    function description()
    {
        return 'renaming, adding and reorganisation of all studip core-modules into the database.';
    }

    function getModules()
    {
        return array(
            array('field' => 'CHAT_ENABLE',
                  'value' => (get_config('CHAT_ENABLE'))?'1':'0',
                  'comment' => 'Schaltet ein oder aus, ob der Chat global verfügbar ist.',
            ),
            array('field' => 'CALENDAR_ENABLE',
                  'value' => (get_config('CALENDAR_ENABLE'))?'1':'0',
                  'comment' => 'Schaltet ein oder aus, ob der Kalender global verfügbar ist.',
            ),
            array('field' => 'EXPORT_ENABLE',
                  'value' => (get_config('EXPORT_ENABLE'))?'1':'0',
                  'comment' => 'Schaltet ein oder aus, ob der Export global verfügbar ist.',
            ),
            array('field' => 'EXTERN_ENABLE',
                  'value' => (get_config('EXTERN_ENABLE'))?'1':'0',
                  'comment' => 'Schaltet ein oder aus, ob die externen Seiten global verfügbar sind.',
            ),
            array('field' => 'VOTE_ENABLE',
                  'value' => (get_config('VOTE_ENABLE'))?'1':'0',
                  'comment' => 'Schaltet ein oder aus, ob die Umfragen global verfügbar sind.',
            ),
            array('field' => 'ELEARNING_INTERFACE_ENABLE',
                  'value' => (get_config('ELEARNING_INTERFACE_ENABLE'))?'1':'0',
                  'comment' => 'Schaltet ein oder aus, ob die Lernmodule global verfügbar sind.',
            ),
            array('field' => 'STM_ENABLE',
                  'value' => (get_config('STM_ENABLE'))?'1':'0',
                  'comment' => 'Schaltet ein oder aus, ob die Studienmodule global verfügbar sind.',
            ),
            array('field' => 'WIKI_ENABLE',
                  'value' => (get_config('WIKI_ENABLE'))?'1':'0',
                  'comment' => 'Schaltet ein oder aus, ob das Wiki global verfügbar ist.',
            ),
            array('field' => 'SMILEYADMIN_ENABLE',
                  'value' => (get_config('SMILEYADMIN_ENABLE'))?'1':'0',
                  'comment' => 'Schaltet ein oder aus, ob die Administration der Smileys verfügbar ist.',
            ),
            array('field' => 'LOG_ENABLE',
                  'value' => (get_config('LOG_ENABLE'))?'1':'0',
                  'comment' => 'Schaltet ein oder aus, ob das Log global verfügbar ist.',
            ),
            array('field' => 'SCM_ENABLE',
                  'value' => (get_config('SCM_ENABLE'))?'1':'0',
                  'comment' => 'chaltet ein oder aus, ob freie Informationsseiten global verfügbar sind.',
            ),
            array('field' => 'BANNER_ADS_ENABLE',
                  'value' => (get_config('BANNER_ADS_ENABLE'))?'1':'0',
                  'comment' => 'Schaltet ein oder aus, ob die Bannerwerbung global verfügbar ist.',
            )
        );
    }

    function up()
    {
        $db = DBManager::get();

        // existing
        $db->exec("UPDATE `config` SET `section` =  'modules', `chdate` = '".time()."' WHERE `field` = 'RESOURCES_ENABLE'");
        $db->exec("UPDATE `config` SET `section` =  'modules', `type` = 'boolean', `range` = 'global', `chdate` = '".time()."' WHERE `field` = 'STUDYGROUPS_ENABLE'");

            //moving
        foreach ($this->modules as $module) {
            $db->exec("INSERT IGNORE INTO `config` ( `config_id` , `parent_id` , `field` , `value` , `is_default` , `type` , `range` , `section` , `position` , `mkdate` , `chdate` , `description` , `comment` , `message_template` ) VALUES ( MD5('". $module['field'] . "'), '', '". $module['field'] . "', '". $module['value'] . "', '1', 'boolean', 'global', 'modules', '0', '".time()."', '".time()."', '". $module['comment'] . "', '', '')");
        }

        // new
        $db->exec("INSERT IGNORE INTO `config` ( `config_id` , `parent_id` , `field` , `value` , `is_default` , `type` , `range` , `section` , `position` , `mkdate` , `chdate` , `description` , `comment` , `message_template` ) VALUES ( MD5('LITERATURE_ENABLE'), '', 'LITERATURE_ENABLE', '1', '1', 'boolean', 'global', 'modules', '0', '".time()."', '".time()."', 'Schaltet ein oder aus, ob die Literaturverwaltung global verfügbar ist.', '', '')");
    }

    function down()
    {
        $db = DBManager::get();

        //existing
        $db->exec("UPDATE `config` SET `section` =  '', `chdate` = '".time()."' WHERE `field` = 'RESOURCES_ENABLE'");
        $db->exec("UPDATE `config` SET `section` =  '', `chdate` = '".time()."' WHERE `field` = 'STUDYGROUPS_ENABLE'");

        //moving
        foreach ($this->modules as $module) {
            $db->exec("DELETE FROM `config` WHERE `field` = '" . $module['field'] . "'");
        }

        //new
        $db->exec("DELETE FROM `config` WHERE `field` = 'LITERATURE_ENABLE'");
    }
}