<?
# Lifter002: TODO
# Lifter007: TODO

/**
 * Event.class.php
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

require_once('lib/calendar_functions.inc.php');

define('CALENDAR_EVENT_PERM_CONFIDENTIAL', 1);
define('CALENDAR_EVENT_PERM_READABLE', 2);
define('CALENDAR_EVENT_PERM_WRITABLE', 4);
//define('CALENDAR_EVENT_PERM_PUBLIC', 2);
define('CALENDAR_EVENT_PERM_FOBIDDEN', 0);

class Event
{

    var $id;
    var $properties = array();
    var $chng_flag = false;   // true if event was changed
    var $permission = CALENDAR_EVENT_PERM_WRITABLE;

    function Event($properties, $permission = NULL)
    {
        $this->properties = $properties;
        if (!is_null($permission)) {
            if ($permission != CALENDAR_PERMISSION_OWN) {
                if ($this->properties['CLASS'] == 'CONFIDENTIAL') {
                    if ($this->properties['STUDIP_AUTHOR_ID'] == $GLOBALS['auth']->auth['uid']) {
                        $this->permission = CALENDAR_EVENT_PERM_WRITABLE;
                    } else {
                        $this->permission = CALENDAR_EVENT_PERM_CONFIDENTIAL;
                    }
                } elseif ($this->properties['CLASS'] == 'PRIVATE') {
                    if ($permission == CALENDAR_PERMISSION_WRITABLE) {
                        $this->permission = CALENDAR_EVENT_PERM_WRITABLE;
                    } else {
                        $this->permission = CALENDAR_EVENT_PERM_READABLE;
                    }
                } elseif ($this->properties['CLASS'] == 'PUBLIC') {
                    $this->permission = CALENDAR_EVENT_PERM_READABLE;
                }
            } else {
                $this->permission = CALENDAR_EVENT_PERM_WRITABLE;
            }
        }

        $this->chng_flag = false;
        if (!$this->properties['CREATED']) {
            $this->setMakeDate();
            $this->chng_flag = TRUE;
        }
        if (!$this->properties['LAST-MODIFIED']) {
            $this->setChangeDate($this->getMakeDate());
            $this->chng_flag = TRUE;
        }
        $this->properties['DTSTAMP'] = time();
    }

    function getPermission()
    {
        return $this->permission;
    }

    function setPermission($perm)
    {
        $this->permission = $perm;
    }

    function havePermission($perm)
    {
        return ($this->permission >= $perm);
    }

    function getProperty($property_name = "")
    {

        return $property_name ? $this->properties[$property_name] : $this->properties;
    }

    function setProperty($property_name, $value)
    {

        $this->properties[$property_name] = $value;
        $this->chng_flag = TRUE;
    }

    // public
    function setId($id)
    {
        $this->id = $id;
        $this->chng_flag = TRUE;
    }

    // public
    function getId()
    {
        return $this->id;
    }

    function getAccessibility()
    {

        if ($this->properties['CLASS'])
            return $this->properties['CLASS'];

        return 'CONFIDENTIAL';
    }

    /**
     * Returns the title of this event.
     *
     * @access public
     * @return String the title of this event
     */
    function getTitle()
    {
        if ($this->permission == CALENDAR_EVENT_PERM_CONFIDENTIAL)
            return _("Keine Berechtigung.");
        if ($this->properties['SUMMARY'] == '')
            return _("Kein Titel");

        return $this->properties['SUMMARY'];
    }

    /**
     * Returns the starttime of this event.
     *
     * @access public
     * @return int the starttime of this event as a unix timestamp
     */
    function getStart()
    {
        return $this->properties['DTSTART'];
    }

    /**
     * Returns the endtime of this event.
     *
     * @access public
     * @return int the endtime of this event as a unix timestamp
     */
    function getEnd()
    {
        return $this->properties['DTEND'];
    }

    /**
     * Returns the categories.
     *
     * @access public
     * @return String the categories
     */
    function getCategory()
    {
        if ($this->permission == CALENDAR_EVENT_PERM_CONFIDENTIAL)
            return '';

        return $this->properties['CATEGORIES'];
    }

    /**
     * Returns the Stud.IP build in category as integer value.
     * See config.inc.php $PERS_TERMIN_KAT.
     *
     * @access public
     * @return int the categories
     */
    function getStudipCategory()
    {
        if ($this->permission == CALENDAR_EVENT_PERM_CONFIDENTIAL)
            return 255;

        return $this->properties['STUDIP_CATEGORY'];
    }

    /**
     * Returns the description.
     *
     * If the description is not set it returns false.
     *
     * @access public
     * @return String the description
     */
    function getDescription()
    {
        if ($this->permission == CALENDAR_EVENT_PERM_CONFIDENTIAL)
            return '';

        if (!$this->properties['DESCRIPTION'])
            return false;
        return $this->properties['DESCRIPTION'];
    }

    /**
     * Returns the duration of this event in seconds
     *
     * @access public
     * @return int the duration of this event in seconds
     */
    function getDuration()
    {
        return $this->getEnd() - $this->getStart();
    }

    /**
     * Returns the location. If the location is not set, it returns false.
     *
     * @access public
     * @return String the location
     */
    function getLocation()
    {
        if ($this->permission == CALENDAR_EVENT_PERM_CONFIDENTIAL)
            return '';

        if ($this->properties['LOCATION'] == '')
            return false;
        return $this->properties['LOCATION'];
    }

    /**
     * Returns the unix timestamp of creating
     *
     * @access public
     */
    function getMakeDate()
    {

        return $this->getProperty('CREATED');
    }

    function getImportDate()
    {
        return $this->getMakeDate();
    }

    /**
     * Sets the unix timestamp of the creation date
     *
     * Access to this method is useful only from the container classes
     * DbCalendarEventList, DbCalendarDay, DbCalendarMonth. Normally the
     * constructor sets this timestamp.
     *
     * @access public
     * @param int $timestamp a valid unix timestamp
     */
    function setMakeDate($timestamp = "")
    {
        if ($timestamp === "")
            $this->properties['CREATED'] = time();
        else
            $this->properties['CREATED'] = $timestamp;
        if ($this->properties['LAST-MODIFIED'] < $this->properties['CREATED'])
            $this->properties['LAST-MODIFIED'] = $this->properties['CREATED'];
        $this->chng_flag = true;
    }

    /**
     * Returns the unix timestamp of the last change
     *
     * @access public
     */
    function getChangeDate()
    {

        return $this->getProperty('LAST-MODIFIED');
    }

    /**
     * Sets the unix timestamp of the last change
     *
     * Access to this method is useful only from the container classes
     * DbCalendarEventList, DbCalendarDay, DbCalendarMonth. Normally every
     * modification of this object sets this value automatically.
     * Nevertheless it is a public function.
     *
     * @access public
     * @param int $timestamp a valid unix timestamp
     */
    function setChangeDate($timestamp = "")
    {
        if ($timestamp === "")
            $this->properties['LAST-MODIFIED'] = time();
        else
            $this->properties['LAST-MODIFIED'] = $timestamp;
        if ($this->properties['CREATED'] > $this->properties['LAST-MODIFIED'])
            $this->properties['LAST-MODIFIED'] = $this->properties['CREATED'];
    }

    /**
     * Returns true if this event has been modified after creation
     *
     * @access public
     * @return boolean
     */
    function isModified()
    {
        return $this->chng_flag;
    }

    /**
     * Changes the description.
     *
     * After calling this method, isModified() returns true.
     *
     * @access public
     * @param String $description the description
     */
    function setDescription($description)
    {
        $this->properties['DESCRIPTION'] = $description;
        $this->chng_flag = true;
    }

    /**
     * Changes the location.
     *
     * After calling this method, isModified() returns true.
     *
     * @access public
     * @param String $location the location
     */
    function setLocation($location)
    {
        $this->properties['LOCATION'] = $location;
        $this->chng_flag = true;
    }

    /**
     * Changes the starttime of this event.
     *
     * After calling this method, isModified() returns true.
     *
     * @access public
     * @param int $start a valid unix timestamp
     */
    function setStart($start)
    {
        if ($this->properties['DTEND'] && $this->properties['DTEND'] < $start)
            return false;
        $this->properties['DTSTART'] = $start;
        $this->chng_flag = true;
        return true;
    }

    /**
     * Changes the endtime of this event.
     *
     * After calling this method, isModified() returns true.
     *
     * @access public
     * @param int $end a valid unix timestamp
     */
    function setEnd($end)
    {
        if ($this->properties['DTSTART'] && $this->properties['DTSTART'] > $end)
            return false;
        $this->properties['DTEND'] = $end;
        $this->chng_flag = true;
        return true;
    }

    function isDayEvent()
    {
        return (date('His', $this->getStart()) == '000000' &&
        (date('His', $this->getEnd()) == '235959'
        || date('His', $this->getEnd() - 1) == '235959'));
    }

    function setDayEvent()
    {
        $this->setStart(mktime(0, 0, 0, date('n', $this->getStart()), date('j', $this->getStart()), date('Y', $this->getStart())));
        /*  if (date('Ymd', $this->getStart()) == date('Ymd', $this->getEnd())) {
          $this->setEnd(mktime(23, 59,59, date('n', $this->getEnd()),
          date('j', $this->getEnd()) + 1, date('Y', $this->getEnd())));
          }
          else { */
        $this->setEnd(mktime(23, 59, 59, date('n', $this->getEnd()), date('j', $this->getEnd()), date('Y', $this->getEnd())));
        //}
    }

    /**
     * Changes the category of this event.
     * Only skeleton, overwrite this function in child classes.
     *
     * See config.inc.php for further information and values 
     * of categories.<br>
     * <br>
     * After calling this method, isModified() returns true.
     *
     * @access public
     * @param int $category a valid integer representation of a category (see 
     * config.inc.php)
     * @return boolean true if the value of $category is valid, otherwise false
     */
    function setCategory($category)
    {

    }

    /**
     * Changes the title of this event.
     *
     * If no title is set it returns 'Kein Titel'.<br>
     * <br>
     * After calling this method, isModified() returns true.
     *
     * @access public
     * @param String $title title of this event
     */
    function setTitle($title = '')
    {
        $this->properties['SUMMARY'] = $title;
        $this->chng_flag = true;
    }

    /**
     * Returns the string representation of start- and end-time
     *
     * Without parameters it returns the short version:<br>
     * 12:30 - 09:45<br>
     * If $mod = 'LONG' it returns the long version:<br>
     * Monday, 20.03.2004 12:30 - Tuesday, 21.03.2004 09:45<br>
     * If $mod = 'SHORT_DAY' it returns the long version with
     * short day names<br>
     * Mo. 20.03.2004 12:30 - Tu. 21.03.2004 09:45
     *
     * @access public
     * @param String $mod 'LONG', 'SHORT' or 'SHORT_DAY'
     */
    function toStringDate($mod = 'SHORT')
    {

        if ($mod == 'SHORT')
            return strftime('%H:%M - ', $this->getStart()) . strftime('%H:%M', $this->getEnd());

        if ($mod == 'LONG') {
            $string = wday($this->getStart())
                    . strftime(' %x, %H:%M - ', $this->getStart());
            if (date('zY', $this->getStart()) != date('zY', $this->getEnd())) {
                $string .= wday($this->getEnd())
                        . strftime(' %x, %H:%M', $this->getEnd());
            }
            else
                $string .= strftime('%H:%M', $this->getEnd());
        }
        else {
            $string = wday($this->getStart(), 'SHORT')
                    . strftime('. %x, %H:%M - ', $this->getStart());
            if (date('zY', $this->getStart()) != date('zY', $this->getEnd())) {
                $string .= wday($this->getEnd(), 'SHORT')
                        . strftime('. %x, %H:%M', $this->getEnd());
            }
            else
                $string .= strftime('%H:%M', $this->getEnd());
        }

        return $string;
    }

    function getExceptions()
    {
        return array();
    }

    function getSeminarId()
    {
        return NULL;
    }

}
