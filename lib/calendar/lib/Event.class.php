<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/*
Event.class.php
Klassen fuer Persoenlichen Terminkalender in Stud.IP.
Copyright (C) 2001 Peter Thienel <pthien@gmx.de>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

//****************************************************************************

require_once('config.inc.php');
require_once('lib/calendar_functions.inc.php');

class Event {
    
    var $id;
    var $properties = array();
    var $chng_flag = FALSE;   // Termin geaendert ? (boolean)

    function Event ($properties) {
        
        $this->properties = $properties;
        
        $this->chng_flag = FALSE;
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
    
    function getProperty ($property_name = "") {
        
        return $property_name ? $this->properties[$property_name] : $this->properties;
    }
    
    function setProperty ($property_name, $value) {
    
        $this->properties[$property_name] = $value;
        $this->chng_flag = TRUE;
    }
    
    // public
    function setId ($id) {
        $this->id = $id;
        $this->chng_flag = TRUE;
    }
    
    // public
    function getId () {
        return $this->id;
    }
    
    /**
    * Returns the title of this event.
    *
    * @access public
    * @return String the title of this event
    */
    function getTitle () {
        if ($this->properties['SUMMARY'] == '')
            return _("Keine Titel");
        
        return $this->properties['SUMMARY'];
    }
    
    /**
    * Returns the starttime of this event.
    *
    * @access public
    * @return int the starttime of this event as a unix timestamp
    */
    function getStart () {
        return $this->properties['DTSTART'];
    }
    
    /**
    * Returns the endtime of this event.
    *
    * @access public
    * @return int the endtime of this event as a unix timestamp
    */
    function getEnd () {
        return $this->properties['DTEND'];
    }
    
    /**
    * Returns the categories.
    *
    * @access public
    * @return String the categories
    */
    function getCategory () {
        return $this->properties['CATEGORIES'];
    }
    
    /**
    * Returns the Stud.IP build in category as integer value.
    * See config.inc.php $PERS_TERMIN_KAT.
    *
    * @access public
    * @return int the categories
    */
    function getStudipCategory () {
        return $this->properties['STUDIP_CATEGORY'];
    }
    
    /**
    * Returns the description.
    *
    * If the description is not set it returns FALSE.
    *
    * @access public
    * @return String the description
    */
    function getDescription () {
        if(!$this->properties['DESCRIPTION'])
            return FALSE;
        return $this->properties['DESCRIPTION'];
    }
    
    /**
    * Returns the duration of this event in seconds
    *
    * @access public
    * @return int the duration of this event in seconds
    */
    function getDuration () {
        return $this->properties['DTEND'] - $this->properties['DTSTART'] -
            ((date('I', $this->properties['DTSTART']) - date('I', $this->properties['DTEND'])) * 3600);
    }
    
    /**
    * Returns the location. If the location is not set, it returns FALSE.
    *
    * @access public
    * @return String the location
    */
    function getLocation () {
        if($this->properties['LOCATION'] == '')
            return FALSE;
        return $this->properties['LOCATION'];
    }
    
    /**
    * Returns the unix timestamp of creating
    *
    * @access public
    */
    function getMakeDate () {
    
        return $this->getProperty('CREATED');
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
    function setMakeDate ($timestamp = "") {
        if($timestamp === "")
            $this->properties['CREATED'] = time();
        else
            $this->properties['CREATED'] = $timestamp;
        if ($this->properties['LAST-MODIFIED'] < $this->properties['CREATED'])
            $this->properties['LAST-MODIFIED'] = $this->properties['CREATED'];
        $this->chng_flag = TRUE;
    }
    
    /**
    * Returns the unix timestamp of the last change
    *
    * @access public
    */
    function getChangeDate () {
    
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
    function setChangeDate ($timestamp = "") {
        if($timestamp === "")
            $this->properties['LAST-MODIFIED'] = time();
        else
            $this->properties['LAST-MODIFIED'] = $timestamp;
        if($this->properties['CREATED'] > $this->properties['LAST-MODIFIED'])
            $this->properties['LAST-MODIFIED'] = $this->properties['CREATED'];
    }
    
    /**
    * Returns TRUE if this event has been modified after creation
    *
    * @access public
    * @return boolean
    */
    function isModified () {
        return $this->chng_flag;
    }
    
    /**
    * Changes the description.
    *
    * After calling this method, the method isModified() returns TRUE.
    *
    * @access public
    * @param String $description the description
    */
    function setDescription ($description) {
        $this->properties['DESCRIPTION'] = $description;
        $this->chng_flag = TRUE;
    }
    
    /**
    * Changes the location.
    *
    *   After calling this method, the method isModified() returns TRUE.
    *
    * @access public
    * @param String $location the location
    */
    function setLocation ($location) {
        $this->properties['LOCATION'] = $location;
        $this->chng_flag = TRUE;
    }
    
    /**
    * Changes the starttime of this event.
    *
    *   After calling this method, the method isModified() returns TRUE.
    *
    * @access public
    * @param int $start a valid unix timestamp
    */ 
    function setStart ($start) {
        if($this->properties['DTEND'] < $start)
            return FALSE;
        $this->properties['DTSTART'] = $start;
        $this->chng_flag = TRUE;
        return TRUE;
    }
    
    /**
    * Changes the endtime of this event.
    *
    *   After calling this method, the method isModified() returns TRUE.
    *
    * @access public
    * @param int $end a valid unix timestamp
    */
    function setEnd ($end) {
        if($this->properties['DTSTART'] != 0 && $this->properties['DTSTART'] > $end)
            return FALSE;
        $this->properties['DTEND'] = $end;
        $this->chng_flag = TRUE;
        return TRUE;
    }
    
    /**
    * Changes the category of this event.
    * Only skeleton, overwrite this function in child classes.
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
    function setCategory ($category) {
    
    }
    
    /**
    * Changes the title of this event.
    *
    * If no title is set it returns 'Kein Titel'.<br>
    * <br>
    * After calling this method, the method isModified() returns TRUE.
    *
    * @access public
    * @param String $title title of this event
    */
    function setTitle ($title = '') {
        $this->properties['SUMMARY'] = $title;
        $this->chng_flag = TRUE;
    }
    
    function isDayEvent () {
    
        return FALSE;
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
    function toStringDate ($mod = 'SHORT') {
        
        if ($mod == 'SHORT')
            return strftime('%H:%M - ', $this->getStart()) . strftime('%H:%M', $this->getEnd());
        
        if ($mod == 'LONG') {
            $string = wday($this->getStart())
                    . strftime(', %x %H:%M - ', $this->getStart());
            if (date('zY', $this->getStart()) != date('zY', $this->getEnd())) {
                $string .= wday($this->getEnd())
                        . strftime(', %x %H%M', $this->getEnd());
            }
            else
                $string .= strftime('%H:%M', $this->getEnd());
        }
        else {
            $string = wday($this->getStart(), 'SHORT')
                    . strftime('. %x %H:%M - ', $this->getStart());
            if (date('zY', $this->getStart()) != date('zY', $this->getEnd())) {
                $string .= wday($this->getEnd(), 'SHORT')
                        . strftime('. %x %H:%M', $this->getEnd());
            }
            else
                $string .= strftime('%H:%M', $this->getEnd());
        }
        
        return $string;
    }
    
}
    
