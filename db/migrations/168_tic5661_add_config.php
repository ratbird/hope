<?php
class Tic5661AddConfig extends Migration
{
    public function description()
    {
        return 'Adds the config entry "NEW_INDICATOR_THRESHOLD" that indicates '
             . 'after how many days an item is consired "old" and will no '
             . 'longer be marked as new';
    }

    public function up()
    {
        Config::get()->create('NEW_INDICATOR_THRESHOLD', array(
            'value'       => '180',
            'is_default'  => '1',
            'type'        => 'integer',
            'range'       => 'global',
            'section'     => 'global',
            'description' => 'Gibt an, nach wieviel Tagen ein Eintrag als alt '
                           . 'angesehen und nicht mehr rot markiert werden '
                           . 'soll (0 angeben, um nur das tatsäcliche Alter) '
                           . 'zu betrachten.',
        ));
    }

    public function down()
    {
        Config::get()->delete('NEW_INDICATOR_THRESHOLD');
    }
}
