<?php

class Tic1887PluginDatafields extends Migration
{
    function description ()
    {
        return 'add a new icon to the seminar overview - participants';
    }

    function up ()
    {
        DBManager::get()->exec(
            "ALTER TABLE `datafields` " .
            "CHANGE `object_type` `object_type` ENUM( 'sem', 'inst', 'user', 'userinstrole', 'usersemdata', 'roleinstdata', 'plugin' ) NULL DEFAULT NULL ");
    }

    function down ()
    {
        DBManager::get()->exec(
            "ALTER TABLE `datafields` " .
            "CHANGE `object_type` `object_type` ENUM( 'sem', 'inst', 'user', 'userinstrole', 'usersemdata', 'roleinstdata' ) NULL DEFAULT NULL ");
    }
}
