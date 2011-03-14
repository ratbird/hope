<?php

class Step209Teilnehmericon extends Migration
{
    function description ()
    {
        return 'add a new icon to the seminar overview - participants';
    }

    function up ()
    {
        // expand the enum in object_user_visits
        DBManager::get()->exec("ALTER TABLE `object_user_visits`
            CHANGE `type` `type`
            ENUM( 'vote', 'documents', 'forum', 'literature', 'schedule', 'scm',
                'sem', 'wiki', 'news', 'eval', 'inst', 'ilias_connect',
                'elearning_interface', 'participants' ) NOT NULL DEFAULT 'vote'");
    }

    function down ()
    {
        DBManager::get()->exec("ALTER TABLE `object_user_visits`
            CHANGE `type` `type`
            ENUM( 'vote', 'documents', 'forum', 'literature', 'schedule', 'scm',
                'sem', 'wiki', 'news', 'eval', 'inst', 'ilias_connect',
                'elearning_interface') NOT NULL DEFAULT 'vote'");
    }
}
