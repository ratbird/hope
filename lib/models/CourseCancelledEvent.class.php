<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Peter Thienel <thienel@data-quest.de>
 * @copyright   2014 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * 
 */

class CourseCancelledEvent extends CourseEvent
{
    
    protected static function configure($config= array())
    {
        $config['db_table'] = 'ex_termine';
        parent::configure($config);
    }
    
    /**
     * Returns all CalendarEvents in the given time range for the given range_id.
     * 
     * @param string $user_id Id of Stud.IP object from type user, course, inst
     * @param DateTime $start The start date time.
     * @param DateTime $end The end date time.
     * @return SimpleORMapCollection Collection of found CalendarEvents.
     */
    public static function getEventsByInterval($user_id, DateTime $start, dateTime $end)
    {
        $stmt = DBManager::get()->prepare('SELECT * FROM seminar_user '
                . 'INNER JOIN ex_termine ON seminar_id = range_id '
                . 'WHERE user_id = :user_id '
                . 'AND bind_calendar = 1 '
                . 'AND date BETWEEN :start AND :end '
                . 'ORDER BY date ASC');
        $stmt->execute(array(
            ':user_id' => $user_id,
            ':start'   => $start->getTimestamp(),
            ':end'     => $end->getTimestamp()
        ));
        $i = 0;
        $event_collection = new SimpleORMapCollection();
        $event_collection->setClassName('Event');
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $event_collection[$i] = new CourseCancelledEvent();
            $event_collection[$i]->setData($row);
            $event_collection[$i]->setNew(false);
        }
        return $event_collection;
    }
    
    
    
}