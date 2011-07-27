<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/*
DbCalendarDay.class.php - 0.8.20020709
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

global $RELATIVE_PATH_CALENDAR, $CALENDAR_DRIVER;

require_once("config.inc.php");
require_once($RELATIVE_PATH_CALENDAR . "/lib/CalendarDay.class.php");
require_once($RELATIVE_PATH_CALENDAR . "/lib/CalendarEvent.class.php");
require_once($RELATIVE_PATH_CALENDAR . "/lib/SeminarEvent.class.php");
require_once($RELATIVE_PATH_CALENDAR . "/lib/calendar_misc_func.inc.php");
require_once($RELATIVE_PATH_CALENDAR . "/lib/driver/$CALENDAR_DRIVER/day_driver.inc.php");

class DbCalendarDay extends CalendarDay {

    var $events;            // Termine (Object[])
    var $events_delete;   // Termine, die geloescht werden (Object[])
    var $arr_pntr;        // "private" function getTermin
    var $user_id;         // User-ID aus PphLib (String)
    
    // Konstruktor
    function DbCalendarDay ($tmstamp) {
        global $user;
        $this->user_id = $user->id;
        CalendarDay::CalendarDay($tmstamp);
        $this->restore();
        $this->sort();
        $this->arr_pntr = 0;
    }
    
    // Anzahl von Terminen innerhalb eines bestimmten Zeitabschnitts
    // default one day
    // public
    function numberOfEvents ($start = 0, $end = 86400) {
        $i = 0;
        $count = 0;
        while ($aterm = $this->events[$i]) {
            if ($aterm->getStart() >= $this->getStart() + $start && $aterm->getStart() <= $this->getStart() + $end)
                $count++;
            $i++;
        }
        return $count - 1;
    }
    
    // public
    function numberOfSimultaneousApps ($term) {
        $i = 0;
        $count = 0;
        while ($aterm = $this->events[$i]) {
            if ($aterm->getStart() >= $term->getStart() && $aterm->getStart() < $term->getEnd())
                $count++;
            $i++;
        }
        return ($count);
    }
    
    // Termin hinzufuegen
    // Der Termin wird gleich richtig einsortiert
    // public
    function addEvent ($term) {
        $this->events[] = $term;
        $this->sort();
    //  return TRUE;
    }
    
    // Termin loeschen
    // public
    function delEvent ($id) {
        for ($i = 0;$i < sizeof($this->events);$i++) {
            if ($id != $this->events[$i]->getId())
                $app_bck[] = $this->events[$i];
            else
                $this->events_delete[] = $this->events[$i];
        }
                
        if (sizeof($app_bck) == sizeof($this->events))
            return FALSE;
        
        $this->events = $app_bck;
        return TRUE;
    }
    
    // ersetzt vorhandenen Termin mit uebergebenen Termin, wenn ID gleich
    // public
    function replaceEvent ($term) {
        for ($i = 0;$i < sizeof($this->events);$i++) {
            if ($this->events[$i]->getId() == $term->getId()) {
                $this->events[$i] = $term;
                $this->sort();
                return TRUE;
            }
        }
        
        return FALSE;
    }
    
    // Abrufen der Termine innerhalb eines best. Zeitraums
    // default 1 hour
    // public
    function nextEvent ($start = -1, $step = 3600) {
        if($start < 0)
            $start = $this->start;
        while ($this->arr_pntr < sizeof($this->events)) {
            if ($this->events[$this->arr_pntr]->getStart() >= $start && $this->events[$this->arr_pntr]->getStart() < $start + $step)
                return $this->events[$this->arr_pntr++];
            $this->arr_pntr++;
        }
        $this->arr_pntr = 0;
        
        return FALSE;
    }
    
    // Termine in Datenbank speichern.
    // public
    function save () {
        
        day_save($this);
    }
    
    // public
    function existEvent () {
        if (sizeof($this->events) > 0)
            return TRUE;
        return FALSE;
    }

    // Wiederholungstermine, die in der Vergangenheit angelegt wurden belegen in
    // events[] die ersten Positionen und werden hier in den "Tagesablauf" einsortiert
    // Termine, die sich ueber die Tagesgrenzen erstrecken, muessen anhand ihrer
    // "absoluten" Anfangszeit einsortiert werden.
    // private
    function sort () {
        if (sizeof($this->events))
            usort($this->events, "cmp_list");
    }                   

    // Termine aus Datenbank holen
    // private
    function restore () {
        day_restore($this);
    }
    
    // public
    function bindSeminarEvents ($sem_id = "") {
        global $TERMIN_TYP;

        if ($sem_id == "")
            $query = sprintf("SELECT t.*, th.title, th.description as details, s.Name "
                . "FROM termine t LEFT JOIN themen_termine tt ON tt.termin_id = t.termin_id "
                        . "LEFT JOIN themen th ON th.issue_id = tt.issue_id "
                        . "LEFT JOIN seminar_user su ON su.Seminar_id=t.range_id "
                        . "LEFT JOIN seminare s ON s.Seminar_id=t.range_id WHERE "
                   . "user_id = '%s' AND date_typ!=-1 AND date_typ!=-2 AND date BETWEEN %s AND %s"
                         , $this->user_id, $this->getStart(), $this->getEnd());
        else if ($sem_id != "") {
            if (is_array($sem_id))
                $sem_id = implode("','", $sem_id);
            $query = sprintf("SELECT t.*, th.title, th.description as details, s.Name, su.status "
                . "FROM termine t LEFT JOIN themen_termine tt ON tt.termin_id = t.termin_id "
                        . "LEFT JOIN themen th ON th.issue_id = tt.issue_id "
                        . "LEFT JOIN seminar_user su ON su.Seminar_id=t.range_id "
                        . "LEFT JOIN seminare s ON su.Seminar_id = s.Seminar_id WHERE "
                . "user_id = '%s' AND range_id IN ('%s') AND date_typ!=-1 "
                . "AND date_typ!=-2 AND date BETWEEN %s AND %s"
                , $this->user_id, $sem_id, $this->getStart(), $this->getEnd());
        }
        else
            return FALSE;
            
        $db = new DB_Seminar;  
        $db->query($query);
        
        if ($db->num_rows() != 0) {
            while ($db->next_record()) {
                if ($db->f('status') === 'dozent') {
                    //wenn ich Dozent bin, zeige den Termin nur, wenn ich durchführender Dozent bin:
                    $termin = new SingleDate($db->f('termin_id'));
                    if (!in_array($this->user_id, $termin->getRelatedPersons())) {
                        continue;
                    }
                }
                $app = new SeminarEvent($db->f('termin_id'), array(
                        'DTSTART'            => $db->f('date'),
                        'DTEND'              => $db->f('end_time'),
                        'SUMMARY'            => $db->f('title'),
                        'DESCRIPTION'        => $db->f('details'),
                        'STUDIP_CATEGORY'    => $db->f('date_typ'),
                        'SEMNAME'            => $db->f('Name'),
                        'LOCATION'           => $db->f('raum'),
                        'CREATED'            => $db->f('mkdate'),
                        'LAST-MODIFIED'      => $db->f('chdate'),
                        'DTSTAMP'            => time()),
                        $db->f('range_id'));
                $this->events[] = $app;
            }
            $this->sort();
            return TRUE;
        }
        return FALSE;
    }
    
    function getUserId () {
    
        return $this->user_id;
    }
    
}

// class Day
