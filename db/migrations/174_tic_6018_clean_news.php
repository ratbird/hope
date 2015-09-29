<?php

class Tic6018CleanNews extends Migration
{
    function description()
    {
        return 'adds config option to cleaner news display';
    }

    function up()
    {
        Config::get()->create('NEWS_DISPLAY', array(
            'value'       => '2',
            'is_default'  => '2',
            'type'        => 'integer',
            'range'       => 'global',
            'section'     => 'view',
            'description' => 'Legt fest, wie sich News für Anwender präsentieren. (2 zeigt sowohl Autor als auch Zugriffszahlen an. 1 zeigt nur den Autor an. 0 blendet beides für Benutzer aus.',
        ));
    }

    function down()
    {
        Config::get()->delete('NEWS_DISPLAY');
    }
}
