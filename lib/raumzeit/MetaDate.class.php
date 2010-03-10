<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
// +--------------------------------------------------------------------------+
// This file is part of Stud.IP
// MetaDate.class.php
//
// Repräsentiert die Zeit- und Turnusdaten einer Veranstaltung
//
// +--------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +--------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +--------------------------------------------------------------------------+


/**
 * MetaDate.class.php
 *
 *
 * @author      Till Glöggler <tgloeggl@uos.de>
 * @version     28. Juni 2007
 * @access      protected
 * @package     raumzeit
 */

require_once('lib/raumzeit/MetaDateDB.class.php');
require_once('lib/raumzeit/CycleData.class.php');

class MetaDate {
    var $seminar_id = '';
    var $seminarStartTime = 0;
    var $seminarDurationTime = 0;
    var $art = 1;
    var $start_woche = 0;
    var $start_termin = 0;
    var $turnus = 0;
    var $cycles = Array();

    function MetaDate($seminar_id = '') {
        if ($seminar_id != '') {
            $this->seminar_id = $seminar_id;
            $this->restore();
        }

    }

    function getArt() {
        return $this->art;
    }

    function setArt($art) {
        $this->art = $art;
    }

    function getStartWoche() {
        return $this->start_woche;
    }

    function setStartWoche($start_woche) {
        $this->start_woche = $start_woche;
    }

    function getStartTermin() {
        return $this->start_termin;
    }

    function setStartTermin($start_termin) {
        $this->start_termin = $start_termin;
    }

    function getTurnus() {
        return $this->turnus;
    }

    function setTurnus($turnus) {
        if ($turnus != $this->turnus) {
            $this->turnus = $turnus;
        }
        return TRUE;
    }

    function setSeminarStartTime($start) {
        $this->seminarStartTime = $start;
    }

    function setSeminarDurationTime($duration) {
        $this->seminarDurationTime = $duration;
    }

    function getSeminarID() {
        return $this->seminar_id;
    }

    function setCycleData($data = array(), &$cycle) {
        if ($cycle->getDescription() != $data['description']) {
            $cycle->setDescription($data['description']);
        }

        if (isset($data['day']) && isset($data['start_stunde']) && isset($data['start_minute']) && isset($data['end_stunde']) && isset($data['end_minute'])) {

            if (
                ($data['start_stunde'] > 23) || ($data['start_stunde'] < 0) || 
                ($data['end_stunde'] > 23)   || ($data['end_stunde']   < 0) ||
                ($data['start_minute'] > 59)   || ($data['start_minute']   < 0) ||
                ($data['end_minute'] > 59)   || ($data['end_minute']   < 0)
            ) {
                return FALSE;
            }

            if (mktime((int)$data['start_stunde'], (int)$data['start_minute']) < mktime((int)$data['end_stunde'], (int)$data['end_minute'])) {          
                $cycle->setDay($data['day']);
                $cycle->setStart($data['start_stunde'], $data['start_minute']);
                $cycle->setEnd($data['end_stunde'], $data['end_minute']);
                return TRUE;
            }
        }
        
        return FALSE;
    }

    /*
     * adds a regular time entry
     */
    function addCycle($data = array()) {
        $data['day'] = (int)$data['day'];
        $data['start_stunde'] = (int)$data['start_stunde'];
        $data['start_minute'] = (int)$data['start_minute'];
        $data['end_stunde'] = (int)$data['end_stunde'];
        $data['end_minute'] = (int)$data['end_minute'];

        $cycle = new CycleData();
        if ($this->setCycleData($data, $cycle)) {
            $this->cycles[$cycle->getMetadateID()] =& $cycle;
            $this->createSingleDates($cycle->getMetadateID());
            return $cycle->getMetadateID();
        }
        return FALSE;
    }

    function editCycle($data = array()) {
        $cycle =& $this->cycles[$data['cycle_id']];
        $new_start = mktime((int)$data['start_stunde'], (int)$data['start_minute']);
        $new_end = mktime((int)$data['end_stunde'], (int)$data['end_minute']);
        $old_start = mktime($cycle->getStartStunde(),$cycle->getStartMinute());
        $old_end = mktime($cycle->getEndStunde(), $cycle->getEndMinute());

        if (($new_start >= $old_start) && ($new_end <= $old_end) && ($data['day'] == $this->cycles[$data['cycle_id']]->day)) {
            // Zeitraum wurde verkuerzt, Raumbuchungen bleiben erhalten...
            if ($this->setCycleData($data, $cycle)) {
                $termine = $cycle->getSingleDates();
                foreach ($termine as $key => $val) {
                    $tos = $val->getStartTime();
                    $toe = $val->getEndTime();
                    if ($toe > time()) {
                        $t_start = mktime((int)$data['start_stunde'], (int)$data['start_minute'], 0, date('m', $tos), date('d', $tos), date('Y', $tos));
                        $t_end = mktime((int)$data['end_stunde'], (int)$data['end_minute'], 0, date('m', $toe), date('d', $toe), date('Y', $toe));
                        $termine[$key]->setTime($t_start, $t_end);
                        $termine[$key]->store();
                    } else {
                        unset($cycle->termine[$key]);
                    }
                }
            }
            return sizeof($termine);
        } else {
            if ($this->setCycleData($data, $cycle)) {

                // collect all existing themes (issues) for this cycle:

                $issues = array();
                $issue_objects = array();
                $singledate_count = 0;

                // loop through the single dates and look for themes (issues)
                $termine = $cycle->getSingleDates();
                foreach ($termine as $key => $termin) {
                    // get all isues of this date ( zero, one, or more, if the expert view is activated)
                    // and store them at the relative position of this single date 
                    $issues[$singledate_count] = $termin->getIssueIDs();
                    $singledate_count++;
                }
                // remove all SingleDates in the future for this CycleData
                $count = CycleDataDB::deleteNewerSingleDates($data['cycle_id'], time(), true);

                // create new SingleDates
                $this->createSingleDates(array('metadate_id' => $cycle->getMetaDateId(), 'startAfterTimeStamp' => time()));

                // clear all loaded SingleDates so no odd ones remain. The Seminar-Class will load them fresh when needed
                $cycle->termine = NULL;

                // read all new single dates
                $termine = $cycle->getSingleDates();

                // new dates counter
                $new_singledate_count = 0;               

                // loop through the single dates and add the themes (issues)
                foreach ($termine as $key => $termin) {
                    // check, if there are issues for this single date 
                    if( $issues[$new_singledate_count] != NULL ) {
                        // add all issues:
                        foreach( $issues[$new_singledate_count] as $issue_key => $issue_id){
                            $termin->addIssueID($issue_id);
                            $termin->store();
                        }
                    }
                    unset($issues[$new_singledate_count]);                  
                    $new_singledate_count++;
                }

                // delete issues, that are not assigned to a single date because of to few dates
                // (only if the schedule expert view is off)           
                if(!$GLOBALS["RESOURCES_ENABLES_EXPERT_SCHEDULE_VIEW"]){
                    if( $new_singledate_count < $singledate_count) {
                        for($i = $new_singledate_count; $i < $singledate_count; $i++){
                            if( $issues[$i] != NULL) {
                                foreach( $issues[$i] as $issue_id){
                                    // delete this issue
                                    IssueDB::deleteIssue($issue_id);
                                }
                            }
                        }
                    }
                }

                return $count;
            }
        }
        return FALSE;
    }

    function deleteCycle($cycle_id) {
        $this->cycles[$cycle_id]->delete();
        unset ($this->cycles[$cycle_id]);
        return TRUE;
    }

    function deleteSingleDate($cycle_id, $date_id, $filterStart, $filterEnd) {
        $this->cycles[$cycle_id]->deleteSingleDate($date_id, $filterStart, $filterEnd);
    }

    function unDeleteSingleDate($cycle_id, $date_id, $filterStart, $filterEnd) {
        return $this->cycles[$cycle_id]->unDeleteSingleDate($date_id, $filterStart, $filterEnd);
    }

    function store() {
        return MetaDateDB::storeMetaData($this);
    }

    function createMetaDateFromArray($data) {
        // art == 0: regelmaessige Veranstaltung
        // art == 1: unregelmaessige Veranstaltung
        //$this->setArt($data['art']);
        // All seminars need to be irregular due to the new handling of regular dates
        $this->setArt(1);
        $this->setStartWoche($data['start_woche']);
        $this->setStartTermin($data['start_termin']);
        $this->setTurnus($data['turnus']);
        if (is_array($data['turnus_data'])) {
            foreach ($data['turnus_data'] as $val) {
                unset($cycle);
                $cycle = new CycleData($val);
                $this->cycles[$cycle->getMetaDateID()] =& $cycle;
            }
        }
        return TRUE;
    }

    function restore() {
        $data = MetaDateDB::restoreMetaData($this->seminar_id);
        $data = unserialize($data);
        return $this->createMetaDateFromArray($data);
    }

    function delete ($removeSingleDates = TRUE) {
        //TODO: Löschen eines MetaDate-Eintrages (CycleData);
    }

    function getSerializedMetaData() {
        $data = Array();
        $data['art'] = $this->getArt();
        $data['start_termin'] = $this->getStartTermin();
        $data['start_woche'] = $this->getStartWoche();
        $data['turnus'] = $this->getTurnus();

        $cycle_data = Array();

        foreach ($this->cycles as $val) {
            $cycle_data[] = Array('idx' => $val->getIdx(),
                                                        'day' => $val->getDay(),
                                                        'start_stunde' => leadingZero($val->getStartStunde()),
                                                        'start_minute' => leadingZero($val->getStartMinute()),
                                                        'end_stunde'   => leadingZero($val->getEndStunde()),
                                                        'end_minute'   => leadingZero($val->getEndMinute()),
                                                        'desc'  => $val->getDescription(),
                                                        'room'  => $val->room,
                                                        'metadate_id'  => $val->getMetaDateID());
        }

        $data['turnus_data'] =& $cycle_data;

        return serialize($data);
    }
    
    function sortCycleData($a, $b) {
        if ($a['day'] == $b['day']) {
            if ($a['start_hour'] == $b['start_hour']) {
                return 0;   
            } 
            return ($a['start_hour'] < $b['start_hour']) ? -1 : 1;  
        } 
        return ($a['day'] < $b['day']) ? -1 : 1;
    }
        
    function getCycleData() {
        $ret = array();
        foreach ($this->cycles as $val) {
            $ret[$val->getMetaDateID()] = array(
                'metadate_id' => $val->metadate_id, 
                'idx' => $val->idx, 
                'day' => $val->day, 
                'start_hour' => $val->start_stunde, 
                'start_minute' => $val->start_minute, 
                'end_hour' => $val->end_stunde, 
                'end_minute' => $val->end_minute, 
                'desc' => $val->description, 
                'room' => $val->room, 
                'resource_id' => $val->resource_id,
                'assigned_rooms' => $val->getPredominantRoom(),
                'freetext_rooms' => $val->getFreetextPredominantRoom()
            );
        }
        uasort($ret,array('MetaDate','sortCycleData'));

        return $ret;
    }

    function getMetaDataAsArray() {
        $ret['turnus_data'] = $this->getCycleData();
        $ret['art'] = $this->getArt();
        $ret['start_termin'] = $this->getStartTermin();
        $ret['start_woche'] = $this->getStartWoche();
        $ret['turnus'] = $this->getTurnus();
        return $ret;
    }

    function &getSingleDates($metadate_id, $filterStart = 0, $filterEnd = 0) {
        if (!$this->cycles[$metadate_id]->termine) {
            $this->readSingleDates($metadate_id, $filterStart, $filterEnd);
        }

        return $this->cycles[$metadate_id]->termine;
    }

    function readSingleDates($metadate_id, $start = 0, $end = 0) {
        return $this->cycles[$metadate_id]->readSingleDates($start, $end);
    }

    function hasDates($metadate_id, $filterStart = 0, $filterEnd = 0) {
        if (!isset($this->hasDatesTmp[$metadate_id])) {
            $this->hasDatesTmp[$metadate_id] = MetaDateDB::has_dates($metadate_id, $this->getSeminarID(), $filterStart, $filterEnd);
        }

        return $this->hasDatesTmp[$metadate_id];
    }
    /*function isLectureFreeTime($time, $all_semester) {
        foreach ($all_semester as $val) {
            if (($val['beginn'] <= $time) && ($val['ende'] >= $time)) {
                if (($val['vorles_beginn'] <= $time) && ($val['vorles_ende'] >= $time)) {
                    return TRUE;
                }
            }
        }
        return FALSE;
    }*/

    function createSingleDates($data, $irregularSingleDates = NULL) {
        if (is_array($data)) {
            $metadate_id = $data['metadate_id'];
            $startAfterTimeStamp = $data['startAfterTimeStamp'];
        } else {
            $metadate_id = $data;
            $startAfterTimeStamp = 0;
        }

        $semester = new SemesterData;
        $all_semester = $semester->getAllSemesterData();

        // get the starting-point for creating singleDates for the choosen cycleData
        foreach ($all_semester as $val) {
            if (($this->seminarStartTime >= $val["beginn"]) && ($this->seminarStartTime <= $val["ende"])) {
                $sem_begin = mktime(0, 0, 0, date("n",$val["vorles_beginn"]), date("j",$val["vorles_beginn"]),  date("Y",$val["vorles_beginn"]));
            }
        }

        // get the end-point
        if ($this->seminarDurationTime == -1) {
            foreach ($all_semester as $val) {
                $sem_end = $val['vorles_ende'];
            }
        } else {
            $i = 0;
            foreach ($all_semester as $val) {
                $i++;
                $timestamp = $this->seminarDurationTime + $this->seminarStartTime;
                if (($timestamp >= $val['beginn']) &&  ($timestamp <= $val['ende'])) {
                    $sem_end = $val["vorles_ende"];
                }
            }
        }

        $passed = false;
        foreach ($all_semester as $val) {
            if ($sem_begin <= $val['vorles_beginn']) {
                $passed = true;
            }
            if ($passed && ($sem_end >= $val['vorles_ende']) && ($startAfterTimeStamp <= $val['ende'])) {
                // correction calculation, if the semester does not start on monday
                $dow = date("w", $val['vorles_beginn']);
                if ($dow <= 5)
                    $corr = ($dow -1) * -1;
                elseif ($dow == 6)
                    $corr = 2;
                elseif ($dow == 0)
                    $corr = 1;
                else
                    $corr = 0;
                $this->createSingleDatesForSemester($metadate_id, $val['vorles_beginn'], $val['vorles_ende'], $startAfterTimeStamp, $corr, $irregularSingleDates);
            }
        }
    }

    function createSingleDatesForSemester($metadate_id, $sem_begin, $sem_end, $startAfterTimeStamp, $corr, &$irregularSingleDates) {
        global $CONVERT_SINGLE_DATES;

        // loads the singledates of the by metadate_id denoted regular time-entry into the object
        $this->readSingleDates($metadate_id);

        // The currently existing singledates for the by metadate_id denoted  regular time-entry
        $existingSingleDates =& $this->cycles[$metadate_id]->getSingleDates();

        // HolidayData is used to decide wether a date is during a holiday an should be created as an ex_termin.
        // Additionally, it is used to show which type of holiday we've got.
        $holiday = new HolidayData();

        // This variable is used to check if a given singledate shall be created in a bi-weekly seminar.
        if ($this->start_woche == -1) $this->start_woche = 0;
        $odd_or_even = 1 - ($this->start_woche % 2);

        if ($CONVERT_SINGLE_DATES) {
            // calculate the number of matching single dates for this metadate (regular date):
            $single_date_count = 0;

            // loop through every (unconverted) single date
            foreach ($irregularSingleDates as $key => $val) {

                // loop through every week
                $week = 0;
                do {
                    //create timestamps for the singledate to be tested
                    $start_time = mktime ((int)$this->cycles[$metadate_id]->start_stunde, (int)$this->cycles[$metadate_id]->start_minute, 0, date("n", $sem_begin), (date("j", $sem_begin)+$corr) + ($this->cycles[$metadate_id]->day -1) + ($week * 7), date("Y", $sem_begin));
                    $end_time = mktime ((int)$this->cycles[$metadate_id]->end_stunde, (int)$this->cycles[$metadate_id]->end_minute, 0, date("n", $sem_begin), (date("j", $sem_begin)+$corr) + ($this->cycles[$metadate_id]->day -1) + ($week * 7), date("Y", $sem_begin));

                    // compare the calculated "would-be" dates (start_time, end_time) with the existing single dates
                    if (($val->date == $start_time) && ($val->end_time == $end_time)) {
                        // match found: count as single date for this regular date
                        $single_date_count++;
                    }                
                    $week++;
                } while($end_time < $sem_end);            
            } 

            // were there any matching single dates?
            if($single_date_count > 0 ) {

                // check, if array is already created, if not, do so
                if($GLOBALS["TEMP_METADATE_HAS_EXISTING_SCHEDULE_PLAN"] == NULL){
                    $GLOBALS["TEMP_METADATE_HAS_EXISTING_SCHEDULE_PLAN"] = array();
                } 

                // set flag for further calls of this method here, that 
                // for this regular (meta)date there have been matching single dates;
                // this means that there was an "ablaufplan" created;
                // if so, no additional dates should be created for 
                // this regular date, only existing dates should be used                
                $GLOBALS["TEMP_METADATE_HAS_EXISTING_SCHEDULE_PLAN"][$metadate_id] = TRUE;
            }

        }

        $week = 0;

        // loop through all possible singledates for this regular time-entry
        do {

            // if dateExists is true, the singledate will not be created. Default is of course to create the singledate
            $dateExists = false;

            // (TODO: This code an the code below should be perfomance optimized. Many follow up check's are unecessary, if $dateExists gets true.)


            // do not create singledates, if they are earlier then the chosen start-week
            if ($this->start_woche > $week) $dateExists = true;

            // bi-weekly check
            if ($this->turnus > 0) {
                if (($week % 2) == $odd_or_even) {
                    $dateExists = true;
                }
            }

            //create timestamps for the new singledate
            $start_time = mktime ((int)$this->cycles[$metadate_id]->start_stunde, (int)$this->cycles[$metadate_id]->start_minute, 0, date("n", $sem_begin), (date("j", $sem_begin)+$corr) + ($this->cycles[$metadate_id]->day -1) + ($week * 7), date("Y", $sem_begin));

            $end_time = mktime ((int)$this->cycles[$metadate_id]->end_stunde, (int)$this->cycles[$metadate_id]->end_minute, 0, date("n", $sem_begin), (date("j", $sem_begin)+$corr) + ($this->cycles[$metadate_id]->day -1) + ($week * 7), date("Y", $sem_begin));

            // convert the start_time to start_woche, stepwise
            if ($CONVERT_SINGLE_DATES) {
                if ($this->start_termin != -1) {
                    if ($this->start_termin > $start_time) {
                        $this->start_woche++;
                        $dateExists = true;
                    }
                }
            }

            /*
             * We only create dates, which do not already exist, so we do not overwrite existing dates.
             *
             * Additionally, we delete singledates which are not needed any more (bi-weekly, changed start-week, etc.)
             */
            foreach ($existingSingleDates as $key => $val) {
                // take only the singledate into account, that maps the current timepoint
                if (($val->date == $start_time) && ($val->end_time == $end_time)) {

                    // bi-weekly check
                    if ($this->turnus > 0) {
                        if (($week % 2) == $odd_or_even) {
                            $val->delete();
                        }
                    }

                    // delete singledates if they are earlier than the chosen start-week
                    if ($this->start_woche > $week) {
                        $val->delete();
                    }
                    $dateExists = true;
                }
            }

            /*
             * for converting existing dates, which belong to specific metadates
             */
            if ($CONVERT_SINGLE_DATES) {
                foreach ($irregularSingleDates as $key => $val) {
                    if (($val->date == $start_time) && ($val->end_time == $end_time)) {
                        $irregularSingleDates[$key]->setMetaDateID($metadate_id);
                        $irregularSingleDates[$key]->range_id = $this->seminar_id;
                        $irregularSingleDates[$key]->update = TRUE;
                        $irregularSingleDates[$key]->store();
                        if ($irregularSingleDates[$key]->room) {
                            $irregularSingleDates[$key]->setFreeRoomText('');
                        }
                        $dateExists = TRUE;
                    }
                }
            }

            // conversion end

            if (!($end_time < $sem_end)) {
                $dateExists = true;
            }

            if ($start_time < $startAfterTimeStamp) {
                $dateExists = true;
            }

            /*
            if (!$this->isLectureFreeTime($start_time, $all_semester)) {
                $dateExists = TRUE;
            }
            */

            if (!$dateExists) {
                unset($termin);
                $termin = new SingleDate(array('seminar_id' => $this->seminar_id));

                // check, if this metadate has been marked
                if($GLOBALS["TEMP_METADATE_HAS_EXISTING_SCHEDULE_PLAN"] != NULL && $GLOBALS["TEMP_METADATE_HAS_EXISTING_SCHEDULE_PLAN"][$metadate_id] != NULL) {
                    if ($GLOBALS["TEMP_METADATE_HAS_EXISTING_SCHEDULE_PLAN"][$metadate_id] == TRUE) {
                        $termin->setExTermin(true);
                    }
                }

                $all_holiday = $holiday->getAllHolidays(); // fetch all Holidays
                foreach ($all_holiday as $val2) {
                    if (($val2["beginn"] <= $start_time) && ($start_time <=$val2["ende"])) {
                        $termin->setExTermin(true);
                    }
                }

                //check for calculatable holidays
                if (!$termin->isExTermin()) {
                    $holy_type = holiday($start_time);
                    if ($holy_type["col"] == 3) {
                        $termin->setExTermin(true);
                    }
                }

                // fill the singleDate-Object with data
                $termin->setMetaDateID($metadate_id);
                $termin->setTime($start_time, $end_time);
                $termin->setDateType($date_typ);
                if ($CONVERT_SINGLE_DATES && !$termin->isExTermin()) {
                    if ($this->cycles[$metadate_id]->resource_id) {
                        $termin->bookRoom($this->cycles[$metadate_id]->resource_id);
                    } else {
                        if ($this->cycles[$metadate_id]->room) {
                            $termin->setFreeRoomText($this->cycles[$metadate_id]->room);
                        }
                    }
                    /*if (sizeof($irregularSingleDates) > 0) {
                        $termin->setExTermin(true);
                        }*/
                }

                // store the singleDate to database
                $termin->store();
            }

            //inc the week
            $week++;

        } while ($end_time < $sem_end);

        // store all the other stuff
        $this->store();

    }
}
