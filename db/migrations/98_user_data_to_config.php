<?php
require_once 'lib/classes/UserConfig.class.php';
require_once 'lib/classes/Config.class.php';
class UserDataToConfig extends Migration
{
    
    function description()
    {
        return 'migrates user forum settings from user_data table to user_config table';
    }

    function up()
    {
    
        $conf = Config::get();

        $conf->create('my_messaging_settings',array('range'=>'user'));   
        $conf->create('forum',array('range'=>'user'));
        $conf->create('my_schedule_settings',array('range'=>'user'));   
        $conf->create('homepage_cache_own',array('range'=>'user'));
        $conf->create('my_studip_settings',array('range'=>'user'));
        $conf->create('CurrentLogin',array('range'=>'user'));    
        $conf->create('LastLogin',array('range'=>'user'));

       //for all users:
        $string = "SELECT user_id FROM auth_user_md5";
        $statement = DBManager::get()->prepare($string);
        $statement->execute();
        while ($val = $statement->fetch(PDO::FETCH_ASSOC)) {
         	$user = new Seminar_User();
         	$user->fake_user = true;
         	$user->start($val['user_id']);
        	UserConfig::get($val['user_id'])->store("forum",json_encode($user->user_vars["forum"]));
        	UserConfig::get($val['user_id'])->store("my_messaging_settings",json_encode($user->user_vars["my_messaging_settings"])); 
        	UserConfig::get($val['user_id'])->store("my_schedule_settings",json_encode($user->user_vars["my_schedule_settings"])); 
        	UserConfig::get($val['user_id'])->store("homepage_cache_own",($user->user_vars["homepage_cache_own"])); 
        	UserConfig::get($val['user_id'])->store("my_studip_settings",($user->user_vars["my_studip_settings"])); 
        	UserConfig::get($val['user_id'])->store("LastLogin",($user->user_vars["LastLogin"])); 
        	UserConfig::get($val['user_id'])->store("CurrentLogin",($user->user_vars["CurrentLogin"])); 
       }
    }

    function down()
    {
        
        $conf = Config::get();
        $conf->delete('my_messaging_settings');   
        $conf->delete('forum');
        $conf->delete('my_schedule_settings');   
        $conf->delete('homepage_cache_own');
        $conf->delete('my_studip_settings');
        $conf->delete('CurrentLogin');   
        $conf->delete('LastLogin');
    }
}
