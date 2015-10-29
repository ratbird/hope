<?php

class Step00198Deputies extends Migration
{
    static $config_entries = array(
        // Do users with status "dozent" always have to be visible?
        array(
            'name'        => 'DEPUTIES_ENABLE',
            'type'        => 'boolean',
            'value'       => 0,
            'description' => 'Legt fest, ob die Funktion Vertretung aktiviert ist.'
        ),
        array(
            'name'        => 'DEPUTIES_DEFAULTENTRY_ENABLE',
            'type'        => 'boolean',
            'value'       => 0,
            'description' => 'D�rfen Lehrende Standardvertretungen festlegen? Diese werden automatisch bei Hinzuf�gen der Lehrenden als Vertretung in Veranstaltungen eingetragen.'
        ),
        array(
            'name'        => 'DEPUTIES_EDIT_ABOUT_ENABLE',
            'type'        => 'boolean',
            'value'       => 1,
            'description' => 'D�rfen Lehrende ihren Standardvertretungen erlauben, ihr Profil zu bearbeiten?'
        )
    );

    function description()
    {
        return 'deputies';
    }

    function up()
    {
        $db = DBManager::get();

        $query = $db->prepare("INSERT IGNORE INTO `config` ".
            "(`config_id`, `parent_id`, `field`, `value`, `is_default`, ".
                "`type`, `range`, `section`, `position`, `mkdate`, `chdate`, ".
                "`description`, `comment`, `message_template`)
            VALUES (MD5(?), '', ?, ?, '1', ?, 'global', 'deputies', '0', ".
                "UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), ?, '', '')");

        // insert new configuration entries
        foreach (self::$config_entries as $entry) {
            $query->execute(array($entry['name'], $entry['name'], $entry['value'], $entry['type'], $entry['description']));
        }

        $db->exec("CREATE TABLE `deputies` ( ".
            "`range_id` VARCHAR(32) NOT NULL, ".
            "`user_id` VARCHAR(32) NOT NULL, ".
            "`gruppe` TINYINT(4) NOT NULL DEFAULT 0, ".
            "`notification` INT(10) NOT NULL DEFAULT 0, ".
            "`edit_about` TINYINT(1) NOT NULL DEFAULT 0, ".
            "PRIMARY KEY (`range_id`, `user_id`)) ENGINE=MyISAM");
    }

    function down()
    {
        $db = DBManager::get();
        $db->exec("DROP TABLE `deputies`");
    }
}
