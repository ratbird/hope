<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/*
SeminarEvent.class.php
Klassen fuer Persoenlichen Terminkalender in Stud.IP.
Copyright (C) 2001 Peter Thienel <pthienel@web.de>

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

require_once("config.inc.php");
require_once($GLOBALS["RELATIVE_PATH_CALENDAR"]
        . "/lib/Event.class.php");
require_once($GLOBALS["RELATIVE_PATH_CALENDAR"]
        . "/lib/driver/MySQL/CalendarDriver.class.php");

class SeminarEvent extends Event {

    var $sem_id = "";
    var $sem_write_perm = FALSE;

    function SeminarEvent ($id = "", $properties = NULL, $sem_id = "") {

        if ($id && $properties == NULL) {
            $this->id = $id;
            // get event out of database...
            $this->restore();
        }
        elseif ($properties) {
            parent::Event($properties);
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
    function setCategory ($category) {
        global $TERMIN_TYP;

        if(is_array($TERMIN_TYP[$category])){
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
    function toStringCategories () {
        global $TERMIN_TYP;

        return $TERMIN_TYP[$this->getProperty('STUDIP_CATEGORY')]['name'];
    }

    function getTitle () {
        if ($this->getProperty('SUMMARY') == '') {
            return _("Kein Titel");
        }
        return $this->getProperty('SUMMARY');
    }

    // public
    function getSeminarId () {
        return $this->sem_id;
    }

    // public
    function getRepeat ($index = "") {
        $ts = mktime(12, 0, 0, date('n', $this->getStart()), date('j', $this->getStart()),
                date('Y', $this->getStart()), 0);
        $rep = array('ts' => $this->date, 'linterval' => 0, 'sinterval' => 0, 'wdays' => '',
                'month' => 0, 'day' => 0, 'rtype' => 'SINGLE', 'duration' => 1);
        return $index ? $rep[$index] : $rep;
    }

    // public
    function setSeminarId ($id) {
        $this->sem_id = $id;
    }

    function restore ($id = '') {
        global $user;

        if ($id == "")
            $id = $this->id;
        else
            $this->id = $id;
        $db = new DB_Seminar();
        $query = "SELECT t.*, su.*, s.Seminar_id, s.Name, th.title, th.description "                        
                        . "FROM termine t "
                        . "LEFT JOIN themen_termine tt ON tt.termin_id = t.termin_id "
                        . "LEFT JOIN themen th ON th.issue_id = tt.issue_id "
                        . "LEFT JOIN seminar_user su ON (t.range_id=su.Seminar_id) "
                        . "LEFT JOIN seminare s ON su.Seminar_id=s.Seminar_id WHERE t.termin_id='{$this->id}' "
                        . "AND su.user_id='{$user->id}'";

        $db->query($query);
        if ($db->num_rows() == 1 && $db->next_record()) {
            $this->setProperty('SUMMARY',         $db->f('title'));
            $this->setProperty('DTSTART',         $db->f('date'));
            $this->setProperty('DTEND',           $db->f('end_time'));
            $this->setProperty('DESCRIPTION',     $db->f('description'));
            $this->setProperty('CLASS',           'PRIVATE');
            $this->setProperty('SEMNAME',         $db->f('Name'));
            $this->setProperty('UID',             $this->getUid($this->id));
            $this->setProperty('CREATED',         $db->f('mkdate'));
            $this->setProperty('LAST-MODIFIED',   $db->f('chdate'));
            $this->setProperty('RRULE',           $this->getRepeat());
            $this->setProperty('STUDIP_CATEGORY', $db->f('date_typ'));
            $this->sem_id   = $db->f('Seminar_id');
            if ($db->f('status') == 'tutor' || $db->f('status') == 'dozent')
                $this->setWritePermission(TRUE);

            // get room name
            $db2 = new DB_Seminar();
            $db2->query("SELECT ro.name FROM resources_assign as ra LEFT JOIN resources_objects as ro USING (resource_id) WHERE ra.assign_user_id = '".$db->f('termin_id')."'");
            if ($db2->next_record()) {
                $this->setProperty('LOCATION', $db2->f('name'));
            } elseif( $db->f('raum') ) {
                $this->setProperty('LOCATION', $db->f('raum'));
            } else {
                $this->setProperty('LOCATION', _("Keine Raumangabe"));
            }

            return TRUE;
        }

        return FALSE;
    }

    function getSemName () {
        return $this->properties["SEMNAME"];
    }

    function setSemName ($name) {
        $this->properties["SEMNAME"] = $name;
    }

    function getType () {
        return 1;
    }

    function setWritePermission ($perm) {
        $this->sem_write_perm = $perm;
    }

    function haveWritePermission () {
        return $this->sem_write_perm;
    }

    function getUid () {

        return "Stud.IP-SEM-{$this->id}@{$_SERVER['SERVER_NAME']}";
    }

    function getCategoryStyle ($image_size = 'small') {
        global $TERMIN_TYP;

        $index = $this->getProperty('STUDIP_CATEGORY');
        if($index > 7) $index = 7;
        return array('image' => $image_size == 'small' ?
                    $GLOBALS['ASSETS_URL'].'images/calendar/category_sem'.$index.'_small.jpg' :
                    $GLOBALS['ASSETS_URL'].'images/calendar/category_sem'.$index.'.jpg',
                    'color' => $TERMIN_TYP[$index]['color']);
    }
    
    function &getClone () {
        $clone = new SeminarEvent($this->id, $this->properties, $this->sem_id);
        return $clone;
    }
    
} // class SeminarEvent
