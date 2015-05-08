<?php
/**
 * Adds default startpage widgets for all users
 */
class Tic5544SetDefaultStartwidgets extends Migration
{
    function description()
    {
        return 'Adds default startwidgets for all users';
    }
    
    function up()
    {
        // Fill database with defaults
        DBManager::get()->execute("INSERT INTO widget_user SELECT NULL,pluginid,position,user_id,col FROM auth_user_md5 JOIN widget_default ON (perms = perm)  WHERE NOT EXISTS (SELECT 1 FROM widget_user WHERE widget_user.range_id = auth_user_md5.user_id)");
    }
    
    function down()
    {
    }
    
}
