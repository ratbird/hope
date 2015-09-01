<?php

class Tic5961AddConfigDefaultSem extends Migration
{
    function description()
    {
        return 'adds config option for new my courses';
    }

    function up()
    {
        Config::get()->create('MY_COURSES_DEFAULT_CYCLE', array(
            'value'       => 'last',
            'is_default'  => '1',
            'type'        => 'string',
            'range'       => 'global',
            'section'     => 'MeineVeranstaltungen',
            'description' => 'Standardeinstellung für den Semester-Filter, falls noch keine Auswahl getätigt wurde. (all, future, current, last)',
        ));
    }

    function down()
    {
        Config::get()->delete('MY_COURSES_DEFAULT_CYCLE');
    }
}
