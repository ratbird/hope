<?php
require_once 'app/models/calendar/Calendar.php';

class Step00283UpdateCalendarSettings extends Migration {

    function description() {
        return 'Registers new calendar settings for each user and adds mkdate.';
    }

    function up() {
        DBManager::get()->exec("ALTER TABLE `event_data` CHANGE `autor_id` `author_id` VARCHAR(32) NOT NULL");
        DBManager::get()->execute("ALTER TABLE `calendar_event` ADD `mkdate` INT NOT NULL AFTER `group_status`");
        DBManager::get()->execute("UPDATE calendar_event ce LEFT JOIN event_data ed USING(event_id) SET ce.mkdate = ed.mkdate");

        $replace = array(
            'showlist' => 'list',
            'showday' => 'day',
            'showweek' => 'week',
            'showmonth' => 'month',
            'showyear' => 'year');
        $res = DBManager::get()->query('SELECT user_id FROM auth_user_md5 WHERE 1');
        $default_settings = Calendar::getDefaultUserSettings();
        foreach ($res as $row) {
            $config = UserConfig::get($row['user_id']);
            $settings = $config->getValue('CALENDAR_SETTINGS');
            if (is_array($settings)) {
                $default_settings['view'] = $replace[$settings['cal_view']];
            }
            $config->store('CALENDAR_SETTINGS', $default_settings);
        }
    }

    function down() {
        DBManager::get()->execute("ALTER TABLE `event_data` CHANGE `author_id` `autor_id` VARCHAR(32) NOT NULL");
        DBManager::get()->execute("ALTER TABLE `calendar_event` DROP `mkdate`");
        
        $replace = array(
            'list' => 'showlist',
            'day' => 'showday',
            'week' => 'showweek',
            'month' => 'showmonth',
            'year' => 'showyear');
        $default_settings = array(
            'view' => 'week',
            'start' => '9',
            'end' => '20',
            'step_day' => '900',
            'step_week' => '1800',
            'type_week' => 'LONG',
            'delete' => '0',
            'step_week_group' => '3600',
            'step_day_group' => '3600'
        );
        $res = DBManager::get()->query('SELECT user_id FROM auth_user_md5 WHERE 1');
        foreach ($res as $row) {
            $config = UserConfig::get($row['user_id']);
            $settings = $config->getValue('CALENDAR_SETTINGS');
            if (is_array($settings)) {
                $default_settings['view'] = $replace[$settings['cal_view']];
            }
            $config->store('CALENDAR_SETTINGS', $default_settings);
        }
    }

}
