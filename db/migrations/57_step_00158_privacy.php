<?php
// include the visibility functions: we need constants here.
require_once(realpath(dirname(__FILE__).'/../../lib/user_visible.inc.php'));

class Step00158Privacy extends Migration
{
	
    static $config_entries = array(
    	// Do users with status "dozent" always have to be visible?
        array(
            'name'        => 'DOZENT_ALWAYS_VISIBLE',
            'type'        => 'boolean',
            'value'       => 1,
            'description' => 'Legt fest, ob Personen mit Dozentenrechten immer global sichtbar sind und das auch nicht selbst ändern können.'
        // What is the default visibility for unconfigured homepage elements?
        ), array(
            'name'        => 'HOMEPAGE_VISIBILITY_DEFAULT',
            'type'        => 'string',
            'value'       => 'VISIBILITY_STUDIP',
            'description' => 'Standardsichtbarkeit für Homepageelemente, falls der Benutzer nichts anderes eingestellt hat. Gültige Werte sind: VISIBILITY_ME, VISIBILITY_BUDDIES, VISIBILITY_DOMAIN, VISIBILITY_STUDIP, VISIBILITY_EXTERN'
        ), array(
            'name'        => 'FORUM_ANONYMOUS_POSTINGS',
            'type'        => 'boolean',
            'value'       => 0,
            'description' => 'Legt fest, ob Forenbeiträge anonym verfasst werden dürfen (Root sieht aber immer den Urheber).'
        )
    );

    function description()
    {
        return 'add configuration entries and database table for enhanced privacy settings';
    }

    function up()
    {
        $db = DBManager::get();
        $query = $db->prepare("INSERT INTO `config` (`config_id`, `parent_id`, `field`, `value`, `is_default`, `type`, `range`, `section`, `position`, `mkdate`, `chdate`, `description`, `comment`, `message_template`) VALUES (MD5(?), '', ?, ?, '1', ?, 'global', 'privacy', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), ?, '', '')");

        // insert new configuration entries
        foreach (self::$config_entries as $entry) {
            $query->execute(array($entry['name'], $entry['name'], $entry['value'], $entry['type'], $entry['description']));
        }

        // create database table for privacy settings
        $db->exec("CREATE TABLE `user_visibility` (`user_id` VARCHAR(32) NOT NULL PRIMARY KEY, `online` TINYINT(1) NOT NULL DEFAULT 0, `chat` TINYINT(1) NOT NULL DEFAULT 0, `search` TINYINT(1) NOT NULL DEFAULT 1, `email` TINYINT(1) NOT NULL DEFAULT 1, `homepage` TEXT NOT NULL DEFAULT '', mkdate INT(20) NOT NULL DEFAULT 0)");

        // insert entries for all existing users
        $db->exec("INSERT INTO `user_visibility` (SELECT `user_id`, 1, 1, 1, 1, '', ".time()." FROM `auth_user_md5`)");
        
        // transfer hidden categories to privacy settings
        $data = $db->query("SELECT * FROM `kategorien` WHERE hidden=1 GROUP BY `range_id`");
        $categories = array();
        // aggregate all categories by their owner...
        while ($category = $data->fetch()) {
        	$categories[$category['range_id']]['kat_'.$category['kategorie_id']] = VISIBILITY_ME;
        }
        // ... and write settings to user privacy table
        foreach ($categories as $owner_id => $settings) {
        	$db->exec("UPDATE `user_visibility` SET `homepage`='".serialize($settings)."' WHERE `user_id`='".$owner_id."'");
        }
        
        // remove hidden attribute of custom categories (is configured in privacy settings now)
        $db->exec("ALTER TABLE `kategorien` DROP `hidden`");
        
        // add field for anonymous postings in forum
        $db->exec("ALTER TABLE `px_topics` ADD `anonymous` TINYINT(4) NOT NULL DEFAULT 0");
    }

    function down()
    {
        $db = DBManager::get();
        $query = $db->prepare("DELETE FROM `config WHERE `config_id = MD5(?)");

        foreach (self::$config_entries as $entry) {
            $query->execute(array(md5($entry['name'])));
        }
        
        // add "hidden" field to user categories...
        $db->exec("ALTER TABLE `kategorien` ADD `hidden` TINYINT(4) NOT NULL DEFAULT 0 AFTER `content`");
        // ... and set it there according to privacy settings
		$db->query("SELECT `user_id`, `homepage` FROM `user_visibility` WHERE `homepage` LIKE '%kat_%'");
		while ($current = $db->fetch()) {
			$data = unserialize($current['homepage']);
			foreach ($data as $key => $visibility) {
				if (substr($key, 0, 4) == 'kat_' && $visibility == VISIBILITY_ME) {
					$category_id = substr($key, 4);
					DBManager::get()->exec("UPDATE `kategorien` SET `hidden`=1 WHERE `user_id`='".$current['user_id']."'");
				}
			}
		}

        // delete privacy settings from database
        $db->exec("DROP TABLE `user_visibility`");
        
        // delete anonymous flag from forum posts
        $db->exec("ALTER TABLE `px_topics` DROP `anonymous`");
    }
}
?>

?>