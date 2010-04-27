<?php
class Step00191ModulesEnable extends Migration
{
    function description()
    {
        return 'renaming, adding and reorganisation of all studip core-modules into the database.';
    }

    function up()
    {
        $db = DBManager::get();
        // existing
        $db->exec("UPDATE `config` SET `section` =  'modules', `chdate` = '".time()."' WHERE `config_id` = '06cdb765fb8f0853e3ebe08f51c3596e'");
        $db->exec("UPDATE `config` SET `section` =  'modules', `type` = 'boolean', `range` = 'global', `chdate` = '".time()."' WHERE `config_id` = 'd8c172c03ed7835f357325588d6ad047'");

        //moving
        $db->exec("INSERT IGNORE INTO `config` ( `config_id` , `parent_id` , `field` , `value` , `is_default` , `type` , `range` , `section` , `position` , `mkdate` , `chdate` , `description` , `comment` , `message_template` ) VALUES ( MD5('CHAT_ENABLE'), '', 'CHAT_ENABLE', '1', '1', 'boolean', 'global', 'modules', '0', '".time()."', '".time()."', 'Schaltet ein oder aus, ob der Chat global verfügbar ist.', '', '')");
        $db->exec("INSERT IGNORE INTO `config` ( `config_id` , `parent_id` , `field` , `value` , `is_default` , `type` , `range` , `section` , `position` , `mkdate` , `chdate` , `description` , `comment` , `message_template` ) VALUES ( MD5('CALENDAR_ENABLE'), '', 'CALENDAR_ENABLE', '1', '1', 'boolean', 'global', 'modules', '0', '".time()."', '".time()."', 'Schaltet ein oder aus, ob der Kalender global verfügbar ist.', '', '')");
        $db->exec("INSERT IGNORE INTO `config` ( `config_id` , `parent_id` , `field` , `value` , `is_default` , `type` , `range` , `section` , `position` , `mkdate` , `chdate` , `description` , `comment` , `message_template` ) VALUES ( MD5('EXPORT_ENABLE'), '', 'EXPORT_ENABLE', '1', '1', 'boolean', 'global', 'modules', '0', '".time()."', '".time()."', 'Schaltet ein oder aus, ob der Export global verfügbar ist.', '', '')");
        $db->exec("INSERT IGNORE INTO `config` ( `config_id` , `parent_id` , `field` , `value` , `is_default` , `type` , `range` , `section` , `position` , `mkdate` , `chdate` , `description` , `comment` , `message_template` ) VALUES ( MD5('EXTERN_ENABLE'), '', 'EXTERN_ENABLE', '1', '1', 'boolean', 'global', 'modules', '0', '".time()."', '".time()."', 'Schaltet ein oder aus, ob die externen Seiten global verfügbar sind.', '', '')");
        $db->exec("INSERT IGNORE INTO `config` ( `config_id` , `parent_id` , `field` , `value` , `is_default` , `type` , `range` , `section` , `position` , `mkdate` , `chdate` , `description` , `comment` , `message_template` ) VALUES ( MD5('VOTE_ENABLE'), '', 'VOTE_ENABLE', '1', '1', 'boolean', 'global', 'modules', '0', '".time()."', '".time()."', 'Schaltet ein oder aus, ob die Umfragen global verfügbar sind.', '', '')");
        $db->exec("INSERT IGNORE INTO `config` ( `config_id` , `parent_id` , `field` , `value` , `is_default` , `type` , `range` , `section` , `position` , `mkdate` , `chdate` , `description` , `comment` , `message_template` ) VALUES ( MD5('ELEARNING_INTERFACE_ENABLE'), '', 'ELEARNING_INTERFACE_ENABLE', '0', '0', 'boolean', 'global', 'modules', '0', '".time()."', '".time()."', 'Schaltet ein oder aus, ob die Lernmodule global verfügbar sind.', '', '')");
        $db->exec("INSERT IGNORE INTO `config` ( `config_id` , `parent_id` , `field` , `value` , `is_default` , `type` , `range` , `section` , `position` , `mkdate` , `chdate` , `description` , `comment` , `message_template` ) VALUES ( MD5('STM_ENABLE'), '', 'STM_ENABLE', '0', '1', 'boolean', 'global', 'modules', '0', '".time()."', '".time()."', 'Schaltet ein oder aus, ob die Studienmodule global verfügbar sind.', '', '')");
        $db->exec("INSERT IGNORE INTO `config` ( `config_id` , `parent_id` , `field` , `value` , `is_default` , `type` , `range` , `section` , `position` , `mkdate` , `chdate` , `description` , `comment` , `message_template` ) VALUES ( MD5('WIKI_ENABLE'), '', 'WIKI_ENABLE', '1', '1', 'boolean', 'global', 'modules', '0', '".time()."', '".time()."', 'Schaltet ein oder aus, ob das Wiki global verfügbar ist.', '', '')");
        $db->exec("INSERT IGNORE INTO `config` ( `config_id` , `parent_id` , `field` , `value` , `is_default` , `type` , `range` , `section` , `position` , `mkdate` , `chdate` , `description` , `comment` , `message_template` ) VALUES ( MD5('SMILEYADMIN_ENABLE'), '', 'SMILEYADMIN_ENABLE', '1', '1', 'boolean', 'global', 'modules', '0', '".time()."', '".time()."', 'Schaltet ein oder aus, ob die Admiinistration der Smileys verfügbar ist.', '', '')");
        $db->exec("INSERT IGNORE INTO `config` ( `config_id` , `parent_id` , `field` , `value` , `is_default` , `type` , `range` , `section` , `position` , `mkdate` , `chdate` , `description` , `comment` , `message_template` ) VALUES ( MD5('LOG_ENABLE'), '', 'LOG_ENABLE', '1', '1', 'boolean', 'global', 'modules', '0', '".time()."', '".time()."', 'Schaltet ein oder aus, ob das Log global verfügbar ist.', '', '')");
        $db->exec("INSERT IGNORE INTO `config` ( `config_id` , `parent_id` , `field` , `value` , `is_default` , `type` , `range` , `section` , `position` , `mkdate` , `chdate` , `description` , `comment` , `message_template` ) VALUES ( MD5('SCM_ENABLE'), '', 'SCM_ENABLE', '1', '1', 'boolean', 'global', 'modules', '0', '".time()."', '".time()."', 'Schaltet ein oder aus, ob freie Informationsseiten global verfügbar sind.', '', '')");
        $db->exec("INSERT IGNORE INTO `config` ( `config_id` , `parent_id` , `field` , `value` , `is_default` , `type` , `range` , `section` , `position` , `mkdate` , `chdate` , `description` , `comment` , `message_template` ) VALUES ( MD5('BANNER_ADS_ENABLE'), '', 'BANNER_ADS_ENABLE', '0', '1', 'boolean', 'global', 'modules', '0', '".time()."', '".time()."', 'Schaltet ein oder aus, ob die Bannerwerbung global verfügbar ist. You need an additional folder in the pictures folder named banner. The Webserver needs write accees for this folder.', '', '')");

        // new
        $db->exec("INSERT IGNORE INTO `config` ( `config_id` , `parent_id` , `field` , `value` , `is_default` , `type` , `range` , `section` , `position` , `mkdate` , `chdate` , `description` , `comment` , `message_template` ) VALUES ( MD5('LITERATURE_ENABLE'), '', 'LITERATURE_ENABLE', '1', '1', 'boolean', 'global', 'modules', '0', '".time()."', '".time()."', 'Schaltet ein oder aus, ob die Literaturverwaltung global verfügbar ist.', '', '')");
    }

    function down()
    {
        $db = DBManager::get();
        //existing
        $db->exec("UPDATE `config` SET `section` =  '', `chdate` = '".time()."' WHERE `config_id` = '06cdb765fb8f0853e3ebe08f51c3596e'");
        $db->exec("UPDATE `config` SET `section` =  '', `chdate` = '".time()."' WHERE `config_id` = 'd8c172c03ed7835f357325588d6ad047'");

        //moving
        $db->exec("DELETE FROM `config` WHERE `config_id` = MD5('CHAT_ENABLE')");
        $db->exec("DELETE FROM `config` WHERE `config_id` = MD5('CALENDAR_ENABLE')");
        $db->exec("DELETE FROM `config` WHERE `config_id` = MD5('EXPORT_ENABLE')");
        $db->exec("DELETE FROM `config` WHERE `config_id` = MD5('EXTERN_ENABLE')");
        $db->exec("DELETE FROM `config` WHERE `config_id` = MD5('VOTE_ENABLE')");
        $db->exec("DELETE FROM `config` WHERE `config_id` = MD5('ELEARNING_INTERFACE_ENABLE')");
        $db->exec("DELETE FROM `config` WHERE `config_id` = MD5('STM_ENABLE')");
        $db->exec("DELETE FROM `config` WHERE `config_id` = MD5('WIKI_ENABLE')");
        $db->exec("DELETE FROM `config` WHERE `config_id` = MD5('SMILEYADMIN_ENABLE')");
        $db->exec("DELETE FROM `config` WHERE `config_id` = MD5('LOG_ENABLE')");
        $db->exec("DELETE FROM `config` WHERE `config_id` = MD5('SCM_ENABLE')");
        $db->exec("DELETE FROM `config` WHERE `config_id` = MD5('BANNER_ADS_ENABLE')");

        //new
        $db->exec("DELETE FROM `config` WHERE `config_id` = MD5('LITERATURE_ENABLE')");
    }
}