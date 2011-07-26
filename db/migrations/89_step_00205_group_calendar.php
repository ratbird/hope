<?php

class Step00205GroupCalendar extends Migration
{
    /**
     * new config options to install
     */
    private $options_new = array(
        array(
            'name' => 'CALENDAR_GROUP_ENABLE',
            'description' => 'Schaltet die Gruppenterminkalender-Funktionen ein.',
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
        return 'extends the calendar by group calendar functions';
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
     * get binded seminars out of user settings and set flag in seminar_user
     */
    function transferBoundSeminars()
    {
        $stmt = DBManager::get()->prepare('SELECT sid, val FROM user_data');
        $stmt->execute(array($user_id));
        $stmt2 = DBManager::get()->prepare('UPDATE seminar_user SET bind_calendar = 1 WHERE Seminar_id = ? AND user_id = ?');
        while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $user_data = @unserialize($result['val']);
            if (is_array($user_data['calendar_user_control_data']['bind_seminare'])) {
                foreach (array_keys($user_data['calendar_user_control_data']['bind_seminare']) as $seminar_id) {
                    $stmt2->execute(array($seminar_id, $result['sid']));
                }
            }
        }
    }

    /**
     * perform this migration
     */
    function up()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `seminar_user` ADD `bind_calendar` TINYINT( 1 ) NOT NULL DEFAULT '1'");
        $db->exec("ALTER TABLE `calendar_events` ADD `editor_id` VARCHAR( 32 ) NOT NULL AFTER `autor_id`");
        $db->exec("ALTER TABLE `calendar_events` ADD `importdate` INT( 11 ) NOT NULL DEFAULT '0'");
        $db->exec("ALTER TABLE `contact` ADD `calpermission` TINYINT( 2 ) UNSIGNED NOT NULL DEFAULT '1'");
        $db->exec("ALTER TABLE `statusgruppen` ADD `calpermission` INT( 2 ) UNSIGNED NOT NULL DEFAULT '1'");
        $this->insertConfig($this->options_new);
        $this->transferBoundSeminars();
    }

    /**
     * revert this migration
     */
    function down()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `seminar_user` DROP `bind_calendar`");
        $db->exec("ALTER TABLE `calendar_events` DROP `editor_id`");
        $db->exec("ALTER TABLE `calendar_events` DROP `importdate`");
        $db->exec("ALTER TABLE `contact` DROP `calpermission`");
        $db->exec("ALTER TABLE `statusgruppen` DROP `calpermission`");
        $this->deleteConfig($this->options_new);
    }
}
