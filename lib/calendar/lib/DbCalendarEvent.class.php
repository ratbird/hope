<?
# Lifter002: TODO
# Lifter007: TODO

/**
 * DbCalendarEvent.class.php
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Peter Thienel <thienel@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     calendar
 */
require_once($RELATIVE_PATH_CALENDAR . '/lib/CalendarEvent.class.php');

class DbCalendarEvent extends CalendarEvent
{

    var $driver;

    function DbCalendarEvent(&$calendar, $event_id = '', $properties = NULL)
    {
        $this->driver = CalendarDriver::getInstance($calendar->getUserId(), $calendar->getPermission());
        if ($event_id != '' && is_null($properties)) {
            $this->restore($event_id);
            parent::CalendarEvent($this->properties, $event_id, $calendar->getUserId(), $calendar->getPermission());
        } else {
            parent::CalendarEvent($properties, NULL, $calendar->getUserId(), $calendar->getPermission());
            $this->chng_flag = true;
        }
    }

    // Store event in database
    // public
    function save()
    {
        if (!$this->havePermission(CALENDAR_EVENT_PERM_WRITABLE)) {
            return false;
        }

        if ($this->isModified()) {
            $this->setChangeDate();
            return $this->driver->writeObjectsIntoDatabase($this);
        }
    }

    // delete event in database
    // public
    function delete()
    {
        if ($this->havePermission(CALENDAR_EVENT_PERM_WRITABLE)) {
            return $this->driver->deleteObjectsFromDatabase($this);
        }

        return false;
    }

    // get event out of database
    // public
    function restore($event_id)
    {

        $this->driver->openDatabaseGetSingleObject($event_id);
        $this->properties = $this->driver->nextProperties();
        $this->id = $event_id;
    }

    function update($new_event)
    {
        if ($this->havePermission(CALENDAR_EVENT_PERM_WRITABLE)) {
            return false;
        }

        $properties = $new_event->getProperty();
        // never update the uid, the make date and the author!
        $uid = $this->getProperty('UID');
        $mkdate = $this->getMakeDate();
        $author = $this->getProperty('STUDIP_AUTHOR_ID');
        foreach ($properties as $name => $value) {
            $this->setProperty($name, $value);
        }
        $this->setProperty('STUDIP_AUTHOR_ID', $author);
        $this->setProperty('UID', $uid);
        $this->setMakeDate($mkdate);
        if ($this->isDayEvent())
            $new_event->setDayEvent();
        $this->chng_flag = true;

        return true;
    }

}
