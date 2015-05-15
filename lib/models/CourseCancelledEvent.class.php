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
        $config['alias_fields']['ex_description'] = 'content';
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
                . 'WHERE ex_termine.content <> \'\' AND user_id = :user_id '
                . 'AND date BETWEEN :start AND :end '
                . "AND (IFNULL(metadate_id, '') = '' "
                . 'OR metadate_id NOT IN ( '
                . 'SELECT metadate_id FROM schedule_seminare '
                . 'WHERE user_id = :user_id AND visible = 0) ) '
                . 'ORDER BY date ASC');
        $stmt->execute(array(
            ':user_id' => $user_id,
            ':start'   => $start->getTimestamp(),
            ':end'     => $end->getTimestamp()
        ));
        $event_collection = new SimpleORMapCollection();
        $event_collection->setClassName('Event');
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $event = new CourseCancelledEvent();
            $event->setData($row);
            $event->setNew(false);
            // related persons (dozenten)
            if (self::checkRelated($event, $user_id)) {
                $event_collection[] = $event;
            }
        }
        return $event_collection;
    }
    
    /**
     * Returns the title of this event.
     * The title of a course event is the name of the course or if a topic is
     * assigned, the title of this topic. If the user has not the permission
     * Event::PERMISSION_READABLE, the title is "Keine Berechtigung.".
     *
     * @return string
     */
    public function getTitle()
    {
        $title = parent::getTitle();
        if ($this->havePermission(Event::PERMISSION_READABLE)) {
            $title .= ' ' . _('(fällt aus)');
        }
        return $title;
    }
    
    /**
     * Returns the index of the category.
     * If the user has no permission, 255 is returned.
     *
     * TODO remove? use getStudipCategory instead?
     *
     * @see config/config.inc.php $TERMIN_TYP
     * @return int The index of the category
     */
    public function getCategory()
    {
        return 255;
    }
    
    function getDescription()
    {
        if ($this->havePermission(Event::PERMISSION_READABLE)) {
            return $this->ex_description;
        }
        return '';
    }

}