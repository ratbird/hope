<?php
require_once 'lib/classes/SeminarCycleDate.class.php';

class Step00202EnhancedSeminarCycle extends Migration
{
    
    function description()
    {
        return 'adds new table seminar_cycle_dates and converts old metadata_dates';
    }

    function up()
    {
        $db = DBManager::get();
        $db->exec("CREATE TABLE IF NOT EXISTS `seminar_cycle_dates` (
                  `metadate_id` varchar(32) NOT NULL,
                  `seminar_id` varchar(32) NOT NULL,
                  `start_time` time NOT NULL,
                  `end_time` time NOT NULL,
                  `weekday` tinyint(3) unsigned NOT NULL,
                  `description` varchar(255) NOT NULL DEFAULT '',
                  `sws` decimal(2,1) NOT NULL DEFAULT '0.0',
                  `cycle` tinyint(3) unsigned NOT NULL DEFAULT '0',
                  `week_offset` tinyint(3) unsigned NOT NULL DEFAULT '0',
                  `sorter` tinyint(3) unsigned NOT NULL DEFAULT '0',
                  `mkdate` int(10) unsigned NOT NULL,
                  `chdate` int(10) unsigned NOT NULL,
                  PRIMARY KEY (`metadate_id`),
                  KEY `seminar_id` (`seminar_id`)
                ) TYPE=MyISAM;");

        foreach ($db->query("SELECT Seminar_id, metadata_dates FROM seminare") as $row) {
            $md = @unserialize($row['metadata_dates']);
            if (is_array($md['turnus_data'])) {
                foreach ($md['turnus_data'] as $c) {
                    if ($c['metadate_id']) {
                        $cd = new SeminarCycleDate();
                        $cd->setId($c['metadate_id']);
                        $cd->weekday = $c['day'];
                        $cd->description = $c['desc'];
                        $cd->start_hour = $c['start_stunde'];
                        $cd->start_minute = $c['start_minute'];
                        $cd->end_hour = $c['end_stunde'];
                        $cd->end_minute = $c['end_minute'];
                        $cd->cycle = $md['turnus'];
                        $cd->week_offset = $md['start_woche'];
                        $cd->seminar_id = $row['Seminar_id'];
                        $cd->store();
                    }
                }
            }
        }
    }

    function down()
    {
        $db = DBManager::get();
        $query = $db->prepare("DROP TABLE `seminar_cycle_dates`");
    }
}
?>
