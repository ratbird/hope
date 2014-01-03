<?php
/**
 * @author  André Klaßen <andre.klassen@elan-ev.de>
 * @license GPL 2 or later
 *
 * @condition course_id ^[a-f0-9]{32}$
 * @condition user_id ^[a-f0-9]{32}$
 * @condition semester_id ^[a-f0-9]{32}$
 */

namespace API;
use DBManager,PDO, StudipPDO, User, Calendar, DbCalendarEventList, SingleCalendar, SingleDate, 
    Seminar, Issue, CalendarExportFile, CalendarWriterICalendar, SemesterData, CalendarScheduleModel, UserConfig;

require_once $GLOBALS['RELATIVE_PATH_CALENDAR'] . '/lib/sync/CalendarExportFile.class.php';
require_once $GLOBALS['RELATIVE_PATH_CALENDAR'] . '/lib/sync/CalendarWriterICalendar.class.php';

class EventsRoute extends RouteMap
{
    /**
     * returns all upcoming events within the next two weeks for a given user
     *
     * @get /user/:user_id/events
     *
     * @return Collection
     */
    public function getEvents($user_id)
    {
        
        $start = time();
        $end   = strtotime('+2 weeks');
        $list = @new DbCalendarEventList(new SingleCalendar($user_id, Calendar::PERMISSION_OWN), $start, $end, true, Calendar::getBindSeminare());

        $events = array();
        
        while ($termin = $list->nextEvent()) {
            $singledate = new SingleDate($termin->id);

            $events[] = array(
                'event_id'    => $termin->id,
                'course_id'   => (strtolower(get_class($termin)) === 'seminarevent') ? $termin->getSeminarId() : '',
                'start'       => $termin->getStart(),
                'end'         => $termin->getEnd(),
                'title'       => $termin->getTitle(),
                'description' => $termin->getDescription() ?: '',
                'categories'  => $termin->toStringCategories() ?: '',
                'room'        => html_entity_decode(strip_tags($singledate->getRoom() ?: $singledate->getFreeRoomText() ?: '')),
            );
        }


        $this->paginate('/events/' . $user_id . '?offset=%u&limit=%u', count($events));

        $result = array_slice($events, $this->offset, $this->limit);
        
        return $this->collect($result);
        
    }
    
    /**
     *  returns an iCAL Export of all events for a given user
     * 
     * @get /user/:user_id/events/ical
     * 
     * @return .ics file
     */
    public function getEventsICAL($user_id)
    {
        global $TMP_PATH;
        $extype = 'ALL_EVENTS';
        $export = new CalendarExportFile(new CalendarWriterICalendar());
        $export->exportFromDatabase($user_id, 0, 2114377200, 'ALL_EVENTS', Calendar::getBindSeminare($GLOBALS['user']->id));

        if ($GLOBALS['_calendar_error']->getMaxStatus(ERROR_CRITICAL)) {
            $this->halt(500);
        }
        
        $filename = "$TMP_PATH/export/". $export->getTempFileName();
        
        
        $headers = array(
            'Content-Type'        => 'text/calendar; windows-1252',
            'Content-Disposition' => 'attachment; filename="studip.ics"',
            'Content-Transfer-Encoding' => 'binary',
            'Pragma' => 'public',
            'Cache-Control' => 'private'
        );
        
        $this->halt(200, $headers, function () use ($filename) { readfile($filename); });
    }
    
    
    /**
     * returns schedule for a given user and semester 
     *
     * @get /user/:user_id/schedule/:semester_id
     *
     * @return Collection  
     */
    
    public function getSchedule($user_id, $semester_id = '')
    {
        $semdata = new SemesterData();
        $current_semester = $semdata->getSemesterData($semester_id);
        
        if (!$current_semester) {
            $current_semester = $semdata->getCurrentSemesterData();
        }
        
        $schedule_settings = UserConfig::get($user_id)->SCHEDULE_SETTINGS;
        $days = $schedule_settings['glb_days'];
        
        foreach ($days as $key => $day_number) {
            $days[$key] = ($day_number + 6) % 7;
        }

       $result = CalendarScheduleModel::getEntries($user_id, $current_semester, 
                  $schedule_settings['glb_start_time'], $schedule_settings['glb_end_time'], $days);
       
       return $this->collect($result);
   }
    
    /**
     * returns events for a given course
     *
     * @get /course/:course_id/events
     *
     * @return Collection
     */
    public function getEventsForCourse($course_id)
    {
        $seminar = new Seminar($course_id);
        $dates = getAllSortedSingleDates($seminar);

        $events = array();
        
        foreach ($dates as $date) {

            $issues = $date->getIssueIDs();
            $issue_titles = array();
            $description = '';
            if(is_array($issues)) {
                foreach($issues as $is) {
                    $issue = new Issue(array('issue_id' => $is));
                    $issue_titles[] = $issue->getTitle();
                }
            }
            
            $description = implode(', ', $issue_titles);
            $temp = getTemplateDataForSingleDate($date);
            $events[] = array(
                'event_id'    => $date->getSingleDateID(),
                'course_id'   => $course_id,
                'start'       => $date->getStartTime(),
                'end'         => $date->getEndTime(),
                'title'       => $temp['date'],
                'description' => $description,
                'categories'  => $temp['art'] ?: '',
                'room'        => html_entity_decode(strip_tags($temp['room'] ?: '')),
            );
            
        }
        
        $this->paginate('/course/' . $course_id . '/events?offset=%u&limit=%u', count($events));

        $result = array_slice($events, $this->offset, $this->limit);
        
        return $this->collect($result);
    }
}
