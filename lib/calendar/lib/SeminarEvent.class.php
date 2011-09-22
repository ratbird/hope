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
            parent::Event($properties, $id, '', $permission);
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

        return $TERMIN_TYP[$this->getProperty('STUDIP_CATEGORY') - 1]['name'];
    }

    // public
    function getSeminarId()
    {
        return $this->sem_id;
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

        return TRUE;
    }

    function getSemName()
    {
        return $this->properties["SEMNAME"];
    }

    function setSemName($name)
    {
        $this->properties["SEMNAME"] = $name;
    }

    function getType()
    {
        return 1;
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
            $this->properties['UID'] = SeminarEvent::createUid($this->id);

        return $this->properties['UID'];
    }

    // static
    function createUid($sem_id)
    {
        return "Stud.IP-SEM-$sem_id-{$this->id}@{$_SERVER['SERVER_NAME']}";
    }

    function getCategory()
    {
        if ($this->permission == Event::PERMISSION_CONFIDENTIAL) {
            return 255;
        }

        return $this->properties['STUDIP_CATEGORY'];
    }

    function getCategoryStyle($image_size = 'small')
    {
        global $TERMIN_TYP, $CANONICAL_RELATIVE_PATH_STUDIP;
        $PERS_TERMIN_KAT;

        $index = $this->getCategory();
        if ($index == 255) {
            return array('image' => $image_size == 'small' ?
                        "{$GLOBALS['ASSETS_URL']}images/calendar/category{$index}_small.jpg" :
                        "{$GLOBALS['ASSETS_URL']}images/calendar/category{$index}.jpg",
                'color' => $PERS_TERMIN_KAT[$index]['color']);
        }

        return array('image' => $image_size == 'small' ?
                    "{$GLOBALS['ASSETS_URL']}images/calendar/category_sem"
                    . ($index - 1) . "_small.jpg" :
                    "{$GLOBALS['ASSETS_URL']}images/calendar/category_sem"
                    . ($index - 1) . ".jpg",
            'color' => $TERMIN_TYP[$index - 1]['color']);
    }

    function getEditorId()
    {
        return null;
    }

}
