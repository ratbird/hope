<?
# Lifter002: TODO
# Lifter007: TODO

/**
 * SeminarEvent.class.php
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

require_once($RELATIVE_PATH_CALENDAR . '/lib/Event.class.php');

class SeminarEvent extends Event
{

    var $sem_id = '';
    var $sem_write_perm = FALSE;
    var $driver;

    function SeminarEvent($id = '', $properties = NULL, $sem_id = '', $permission = NULL)
    {
        global $auth;

        if ($id && is_null($properties)) {
            $this->id = $id;
            $this->driver = CalendarDriver::getInstance($auth->auth['uid']);
            // get event out of database...
            $this->restore();
        } elseif (!is_null($properties)) {
            parent::Event($properties, $permission);
            $this->id = $id;
            $this->sem_id = $sem_id;
        }
        $this->properties['RRULE']['rtype'] = 'SINGLE';

        $this->properties['UID'] = $this->getUid();
    }

    /**
     * Changes the category of this event.
     *
     * See config.inc.php for further information and values.<br>
     * <br>
     * After calling this method, the method isModified() returns TRUE.
     *
     * @access public
     * @param int $category a valid integer representation of a category (see
     * config.inc.php)
     * @return boolean TRUE if the value of $category is valid, otherwise FALSE
     */
    function setCategory($category)
    {
        global $TERMIN_TYP;

        if (is_array($TERMIN_TYP[$category])) {
            $this->properties['STUDIP_CATEGORY'] = $category;
            $this->chng_flag = TRUE;
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Returns the name of the category.
     *
     * @access public
     * @return String the name of the category
     */
    function toStringCategories()
    {
        global $TERMIN_TYP;

        if ($this->havePermission(Event::PERMISSION_READABLE)) {
            return $TERMIN_TYP[$this->getProperty('STUDIP_CATEGORY')]['name'];
        }
        return '';
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
    function createRepeat()
    {
        $rep = array('ts' => 0, 'linterval' => 0, 'sinterval' => 0, 'wdays' => '',
            'month' => 0, 'day' => 0, 'rtype' => 'SINGLE', 'duration' => 1);
        return $rep;
    }

    function getRepeat($index = '')
    {
        if (!is_array($this->properties['RRULE']))
            $this->properties['UID'] = SeminarEvent::createRepeat();

        return $index ? $this->properties['RRULE'][$index] : $this->properties['RRULE'];
    }

    // public
    function setSeminarId($id)
    {
        $this->sem_id = $id;
    }

    function restore($id = '')
    {
        global $auth;

        if ($id == '') {
            $id = $this->id;
        } else {
            $this->id = $id;
        }

        if (!is_object($this->driver)) {
            $this->driver = CalendarDriver::getInstance($sem_id);
        }

        $this->driver->openDatabaseGetSingleObject($id, 'SEMINAR_EVENTS');
        if (!$properties = $this->driver->nextProperties()) {
            return FALSE;
        }
        $this->properties = $properties;
        $this->id = $properties['STUDIP_ID'];
        $this->sem_id = $properties['SEM_ID'];
        $this->sem_write_perm = $GLOBALS['perm']->have_studip_perm('tutor', $this->sem_id);
        if ($this->haveWritePermission()) {
            $this->permission = Event::PERMISSION_WRITABLE;
        } else {
            $this->permission = $this->properties['CLASS'] == 'CONFIDENTIAL' ? Event::PERMISSION_CONFIDENTIAL :
                Event::PERMISSION_READABLE;
        }

        return TRUE;
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

    function getTitle()
    {
        if (!$this->havePermission(Event::PERMISSION_READABLE)) {
            return _("Keine Berechtigung.");
        }
        if ($this->properties['SUMMARY'] == '' || $this->getProperty('SUMMARY') == _('Ohne Titel')) {
            return $this->getSemName();
        }

        return $this->properties['SUMMARY'];
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
        if ($this->properties['UID'] == '') {
            $this->properties['UID'] = SeminarEvent::createUid($this->id);
        }
        return $this->properties['UID'];
    }

    function createUid($id)
    {
        return "Stud.IP-SEM-$id@{$_SERVER['SERVER_NAME']}";
    }

    function getCategory()
    {
        if ($this->havePermission(Event::PERMISSION_READABLE)) {
            return $this->properties['STUDIP_CATEGORY'];
        }
        return 255;
    }

    function getCategoryStyle($image_size = 'small')
    {
        global $TERMIN_TYP, $PERS_TERMIN_KAT;

        $index = $this->getCategory();
        if ($index == 255) {
            return array('image' => $image_size == 'small' ?
                Assets::image_path('calendar/category' . $index . '_small.jpg') :
                Assets::image_path('calendar/category' . $index . '.jpg'),
                'color' => $PERS_TERMIN_KAT[$index]['color']);
        }

        return array('image' => $image_size == 'small' ?
            Assets::image_path('calendar/category_sem' . ($index) . '_small.jpg') :
            Assets::image_path('calendar/category_sem' . ($index) . '.jpg'),
            'color' => $TERMIN_TYP[$index]['color']);
    }

    function getEditorId()
    {
        return null;
    }

    function isDayEvent()
    {
        return (($this->getEnd() - $this->getStart()) / 60 / 60) > 23;
    }
    
    /**
     * Returns all related groups.
     * 
     * @return array An array of statusgruppen objects or an empty array, 
     */
    function getRelatedGroups()
    {
        $groups = array();
        foreach (SingleDate::getInstance($this->getId())->getRelatedGroups()
                as $group_id) {
            $groups[$group_id] = new Statusgruppen($group_id);
        }
        return $groups;
    }
}
