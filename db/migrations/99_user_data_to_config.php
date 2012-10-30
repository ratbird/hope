<?php
require_once 'lib/classes/UserConfig.class.php';
require_once 'lib/classes/Config.class.php';
class UserDataToConfig extends Migration
{
    
    function description()
    {
        return 'migrates user settings from user_data table to user_config table';
    }

    function up()
    {
    
        $conf = Config::get();
        $conf->create('calendar_user_control_data',array('range'=>'user'));  
       //for all users:
        $string = "SELECT user_id FROM auth_user_md5";
        $statement = DBManager::get()->prepare($string);
        $statement->execute();
        while ($val = $statement->fetch(PDO::FETCH_ASSOC)) {
         	$user = new Seminar_User();
         	$user->fake_user = true;
         	$user->start($val['user_id']);

        	UserConfig::get($val['user_id'])->store("calendar_user_control_data",json_encode($user->user_vars["calendar_user_control_data"]));
                UserConfig::set($val['user_id'], null);
        	unset($user->cfg);
                unset($user);
       }
    }

    function down()
    {       
        $conf = Config::get();        
        $conf->delete('calendar_user_control_data');
    }
}
