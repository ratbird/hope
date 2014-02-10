<?php

class RemoveScheduleUserTable extends Migration
{
    function description ()
    {
        return 'remove obsolete seminar_user_schedule-table from the db';
    }

    function up ()
    {
        // create new multi-purpose schedule table
        DBManager::get()->exec("DROP TABLE seminar_user_schedule");
    }
}
