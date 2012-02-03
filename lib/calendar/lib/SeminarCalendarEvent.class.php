<?
# Lifter002: TODO
# Lifter007: TODO

/**
 * SeminarCalendarEvent.class.php
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

class SeminarCalendarEvent extends CalendarEvent
{

    var $sem_id = '';
    var $sem_write_perm = false;
    var $driver;

    function SeminarCalendarEvent($properties = NULL, $id = '', $sem_id = '', $permission = NULL)
    {
        global $user;

        if ($id && is_null($properties)) {
            $this->id = $id;
            $this->driver = CalendarDriver::getInstance($user->id);
            // get event out of database...
            $this->restore();
        } elseif (!is_null($properties)) {
            parent::CalendarEvent($properties, $id, '', $permission);
            // $this->id = $id;
            $this->sem_id = $sem_id;
        }

        $this->properties['UID'] = $this->getUid();
    }

    // public
    function getSeminarId()
    {
        if ($this->havePermission(Event::PERMISSION_READABLE)) {
            return $this->sem_id;
        }
        return null;
    }

    // public
    function setSeminarId($id)
    {
        $this->sem_id = $id;
    }

    function restore($id = '')
    {
        global $user;

        if ($id == '')
            $id = $this->id;
        else
            $this->id = $id;

        if (!is_object($this->driver)) {
            $this->driver = CalendarDriver::getInstance($user->id);
        }

        $this->driver->openDatabaseGetSingleObject($id, 'SEMINAR_CALENDAR_EVENTS');

        if (!$event = $this->driver->nextObject()) {
            return false;
        }

        $this->properties = $event->properties;
        $this->id = $event->id;
        $this->sem_id = $event->sem_id;
        $this->sem_write_perm = $event->sem_write_perm;

        return true;
    }

    function getSemName()
    {
        if ($this->havePermission(Event::PERMISSION_READABLE)) {
            return $this->properties["SEMNAME"];
        }
        return '';
    }

    function setSemName($name)
    {
        $this->properties["SEMNAME"] = $name;
    }

    function getType()
    {
        return 1;
    }

    function getPermission()
    {
        global $perm;

        if (is_object($perm)) {
            switch ($perm->get_studip_perm($this->sem_id)) {
                case 'user' :
                case 'autor' :
                    return Event::PERMISSION_READABLE;
                case 'tutor' :
                case 'dozent' :
                case 'admin' :
                case 'root' :
                    return Event::PERMISSION_WRITABLE;
                default :
                    return Event::PERMISSION_FORBIDDEN;
            }
        }
        return Event::PERMISSION_FORBIDDEN;
    }

    function havePermission($permission)
    {
        return ($this->getPermission() >= $permission);
    }

    function setWritePermission($perm)
    {
        $this->sem_write_perm = $perm;
    }

    function haveWritePermission()
    {
        return $this->sem_write_perm;
    }

    function getUid()
    {
        if ($this->properties['UID'] == '')
            $this->properties['UID'] = SeminarCalendarEvent::createUid($this->id);

        return $this->properties['UID'];
    }

    // static
    function createUid($id)
    {
        return "Stud.IP-SEMCAL-$id@{$_SERVER['SERVER_NAME']}";
    }

}
