<?php

class Step00205GtkGroupsSemCal extends Migration
{
    /**
     * new config options to install
     */
    private $options_new = array(
        array(
            'name' => 'COURSE_CALENDAR_ENABLE',
            'description' => 'Kalender als Inhaltselement in Veranstaltungen.',
            'section' => 'modules',
            'type' => 'boolean',
            'value' => '0'
        )
    );

    /**
     * short description of this migration
     */
    function description()
    {
        return 'global switch for activating course calendars, checkbox for calendar groups';
    }

    /**
     * insert list of options into config table
     */
    function insertConfig($options)
    {
        $db = DBManager::get();
        $time = time();

        $stmt = $db->prepare("
                INSERT INTO config
                    (config_id, field, value, is_default, type, section, mkdate, chdate, description)
                VALUES
                    (MD5(:name), :name, :value, 1, :type, :section, $time, $time, :description)
                ");

        foreach ($options as $option) {
            $stmt->execute($option);
        }
    }

    /**
     * remove list of options from config table
     */
    function deleteConfig($options)
    {
        $db = DBManager::get();

        $stmt = $db->prepare("DELETE FROM config WHERE field = :name");

        foreach ($options as $option) {
            $stmt->execute(array('name' => $option['name']));
        }
    }

    /**
     * perform this migration
     */
    function up()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `statusgruppen` CHANGE `calpermission` `calendar_group` TINYINT( 2 ) UNSIGNED NOT NULL DEFAULT '0'");
        $db->exec("UPDATE `statusgruppen` SET `calendar_group` = 0 WHERE 1");
        $this->insertConfig($this->options_new);
    }

    /**
     * revert this migration
     */
    function down()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `statusgruppen` CHANGE `calendar_group` `calpermission` INT( 2 ) UNSIGNED NOT NULL DEFAULT '1'");
        $db->exec("UPDATE `statusgruppen` SET `calpermission` = 1 WHERE 1");
        $this->deleteConfig($this->options_new);
    }
}
