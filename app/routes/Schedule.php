<?php
namespace RESTAPI\Routes;

/**
 * @author  Andr� Kla�en <andre.klassen@elan-ev.de>
 * @author  <mlunzena@uos.de>
 * @license GPL 2 or later
 *
 * @condition user_id ^[a-f0-9]{32}$
 * @condition semester_id ^[a-f0-9]{32}$
 */
class Schedule extends \RESTAPI\RouteMap
{

    /**
     * returns schedule for a given user and semester
     *
     * @get /user/:user_id/schedule/:semester_id
     * @get /user/:user_id/schedule
     */

    public function getSchedule($user_id, $semester_id = NULL)
    {
        if ($user_id !== $GLOBALS['user']->id) {
            $this->error(401);
        }

        $semdata = new \SemesterData();

        $current_semester = isset($semester_id)
            ? $semdata->getSemesterData($semester_id)
            : $semdata->getCurrentSemesterData();

        if (!$current_semester) {
            $this->notFound('No such semester.');
        }

        $schedule_settings = \UserConfig::get($user_id)->SCHEDULE_SETTINGS;
        $days = $schedule_settings['glb_days'];

        $entries = \CalendarScheduleModel::getEntries(
            $user_id, $current_semester,
            $schedule_settings['glb_start_time'], $schedule_settings['glb_end_time'],
            $days,
            $visible = false);

       $json = array();
       foreach ($entries as $number_of_day => $schedule_of_day) {
           $entries = array();
           foreach ($schedule_of_day->entries as $entry) {
               $entries[$entry['id']] = self::entryToJson($entry);
           }
           $json[$number_of_day + 1] = $entries;
       }

       return $json;
    }


    private static function entryToJson($entry)
    {
        $json = array();

        foreach (words("start end content title color type") as $key) {
            $json[$key] = $entry[$key];
        }

        return $json;
    }
}
