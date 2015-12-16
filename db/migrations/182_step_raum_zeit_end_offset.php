<?

class StepRaumZeitEndOffset extends Migration
{
    function description()
    {
        return _('F�gt eine neue Spalte hinzu, die Semesterwochhe f�r das Ende zu speicher!');
    }

    function up()
    {
        DBManager::get()->exec('ALTER TABLE `seminar_cycle_dates` ADD COLUMN `end_offset` TINYINT(3) NULL AFTER `week_offset`');

        SimpleORMap::expireTableScheme();

        //CHANGE route entry in help_content from raumzeit.php to new dispatch.php/course/timesrooms
        $query = 'UPDATE `help_content` SET route = :new WHERE route = :old';
        $stm = DBManager::get()->prepare($query);
        $stm->execute(array(':new' => 'dispatch.php/course/timesrooms', ':old' => 'raumzeit.php'));
    }

    function down()
    {
        DBManager::get()->exec('ALTER TABLE `seminar_cycle_dates` DROP COLUMN `end_offset`');

        SimpleORMap::expireTableScheme();

        //CHANGE route entry in help_content from dispatch.php/course/timesrooms back to raumzeit.php
        $query = 'UPDATE `help_content` SET route = :old WHERE route = :new';
        $stm = DBManager::get()->prepare($query);
        $stm->execute(array(':new' => 'dispatch.php/course/timesrooms', ':old' => 'raumzeit.php'));
    }
}
