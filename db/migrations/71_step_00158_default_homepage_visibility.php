<?php
// include the visibility functions: we need constants here.
require_once(realpath(dirname(__FILE__).'/../../lib/user_visible.inc.php'));

class Step00158DefaultHomepageVisibility extends Migration
{
    
    function description()
    {
        return 'users get the possibility to define a default setting for new homepage elements';
    }

    function up()
    {
        $db = DBManager::get();
        $query = $db->exec("ALTER TABLE `user_visibility` ADD `default_homepage_visibility` INT NOT NULL DEFAULT 0 AFTER `homepage` ");
    }

    function down()
    {
        $db = DBManager::get();
        $query = $db->exec("ALTER TABLE `user_visibility` DROP `default_homepage_visibility`");
    }
}
?>
