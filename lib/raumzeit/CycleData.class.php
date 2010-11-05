<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
// +--------------------------------------------------------------------------+
// This file is part of Stud.IP
// CycleData.class.php
//
// Repräsentiert ein Turnusdatum eines MetaDates
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

require_once 'lib/raumzeit/CycleDataDB.class.php';
require_once 'lib/raumzeit/SingleDate.class.php';
require_once 'lib/raumzeit/IssueDB.class.php';
require_once 'lib/classes/SeminarCycleDate.class.php';

/**
 * CylceData.class.php
 *
 *
 * @author      Till Glöggler <tgloeggl@uos.de>
 * @version     19. Oktober 2005
 * @access      protected
 * @package     raumzeit
 */
class CycleData
{
    /**
     * Enter description here ...
     * @var unknown_type
     */
    private $alias = array( 'start_stunde' => 'start_hour',
                            'end_stunde' => 'end_hour',
                            'day' => 'weekday',
                            'desc' => 'description');

    /**
     * Enter description here ...
     * @var unknown_type
     */
    public $termine = NULL; // Array

    /**
     * Enter description here ...
     * @var unknown_type
     */
    private $cycle_date = null;

    /**
     * Enter description here ...
     * @param unknown_type $cycle_data
     */
    function __construct($cycle_data = FALSE)
    {
        if ($cycle_data instanceof SeminarCycleDate) {
            $this->cycle_date = $cycle_data;
        } else {
            if ($cycle_data['metadate_id']) {
                $metadate_id = $cycle_data['metadate_id'];
            } else {
                $metadate_id = md5(uniqid('metadate_id'));
            }
            $this->cycle_date = new SeminarCycleDate($metadate_id);
            $this->setStart($cycle_data['start_stunde'], $cycle_data['start_minute']);
            $this->setEnd($cycle_data['end_stunde'], $cycle_data['end_minute']);
            $this->setDay($cycle_data['day']);
            $this->setDescription($cycle_data['desc']);
        }
    }

    function getDescription()
    {
        return $this->cycle_date->description;
    }

    function setDescription($description)
    {
        $this->cycle_date->description = $description;
    }

    function setStart($start_stunde, $start_minute)
    {
        $this->cycle_date->start_hour = (int)$start_stunde;
        $this->cycle_date->start_minute = (int)$start_minute;
    }

    function setEnd($end_stunde, $end_minute)
    {
        $this->cycle_date->end_hour = (int)$end_stunde;
        $this->cycle_date->end_minute = (int)$end_minute;
    }

    function getStartStunde ()
    {
        return $this->cycle_date->start_hour;
    }

    function getStartMinute ()
    {
        return $this->cycle_date->start_minute;
    }

    function getEndStunde ()
    {
        return $this->cycle_date->end_hour;
    }

    function getEndMinute ()
    {
        return $this->cycle_date->end_minute;
    }

    function getMetaDateID()
    {
        return $this->cycle_date->getId();
    }

    function getDay()
    {
        return $this->cycle_date->weekday;
    }

    function setDay($day)
    {
        $this->cycle_date->weekday = $day;
    }

    function __get($field)
    {
        if(isset($this->alias[$field])) {
            $field = $this->alias[$field];
        }
        return $this->cycle_date->$field;
    }

    function __set($field, $value)
    {
        if(isset($this->alias[$field])) {
            $field = $this->alias[$field];
        }
        return $this->cycle_date->$field = $value;
    }

    function __isset($field)
    {
        if(isset($this->alias[$field])) {
            $field = $this->alias[$field];
        }
        return isset($this->cycle_date->$field);
    }

    function storeCycleDate()
    {
        return $this->cycle_date->store();
    }

    function store()
    {
        foreach ($this->termine as $val) {
            $val->store();
        }

        return TRUE;
    }

    function restore()
    {
        foreach ($this->termine as $key => $val) {
            $new_termine[$key] = $val->restore();
        }
        $this->termine =& $new_termine;
        return TRUE;
    }

    function delete($removeSingles = TRUE)
    {
        if ($removeSingles) {
            if (!$this->termine) {
                $this->readSingleDates();
            }
            foreach ($this->termine as $termin) {
                // delete issues, if the schedule expert view is off
                if(!$GLOBALS["RESOURCES_ENABLES_EXPERT_SCHEDULE_VIEW"]){
                    $issue_ids = $termin->getIssueIDs();
                    if (is_array($issue_ids)) {
                        foreach($issue_ids as $issue_id){
                            // delete this issue
                  $issue = new Issue(array('issue_id' => $issue_id));
                  $issue->delete();
                        }
                    }
                }
                $termin->delete();
            }
        }
        return $this->cycle_date->delete();
    }

    function deleteSingleDate($date_id, $filterStart, $filterEnd)
    {
        if (!$this->termine) {
            $this->readSingleDates($filterStart, $filterEnd);
        }

        $this->termine[$date_id]->setExTermin(true);
        $this->termine[$date_id]->store();
    }

    function unDeleteSingleDate($date_id, $filterStart, $filterEnd)
    {
        if (!$this->termine) {
            $this->readSingleDates($filterStart, $filterEnd);
        }

        if (!$this->termine[$date_id]->isExTermin()) {
            return false;
        }

        $this->termine[$date_id]->setExTermin(false);
        $this->termine[$date_id]->store();
        return true;
    }

    function readSingleDates($start = 0, $end = 0)
    {
        $this->termine = array();
        $termin_data = CycleDataDB::getTermine($this->metadate_id, $start, $end);
        if ($termin_data) {
            foreach ($termin_data as $val) {
                unset($termin);
                $termin = new SingleDate();
                $termin->fillValuesFromArray($val);
                $termin->setExTermin($val['ex_termin']);
                $this->termine[$val['termin_id']] = $termin;
            }
            return TRUE;
        }

        return FALSE;
    }

    function getSingleDates()
    {
        if (!$this->termine) {
            $this->readSingleDates();
        }
        return $this->termine;
    }

    function getFreeTextPredominantRoom($filterStart = 0, $filterEnd = 0)
    {
        if ($room = CycleDataDB::getFreeTextPredominantRoomDB($this->metadate_id, $filterStart, $filterEnd)) {
            return $room;
        }
    }

    function getPredominantRoom($filterStart = 0, $filterEnd = 0)
    {
        if ($rooms = CycleDataDB::getPredominantRoomDB($this->metadate_id, $filterStart, $filterEnd)) {
            return $rooms;
        }

        return FALSE;
    }

    function toString($short = false)
    {
        if($short === false) {
            return $this->cycle_date->toString('long');
        } else if ($short === true) {
            return $this->cycle_date->toString('short');
        } else {
            return $this->cycle_date->toString($short);
        }
    }

    function toArray()
    {
        $ret = $this->cycle_date->toArray();
        foreach($this->alias as $a => $o) {
            $ret[$a] = $this->cycle_date->$o;
        }
        $ret['assigned_rooms'] = $this->getPredominantRoom();
        $ret['freetext_rooms'] = $this->getFreetextPredominantRoom();
        $ret['tostring']       = $this->toString();
        $ret['tostring_short'] = $this->toString(true);

        $ret['start_minute'] = leadingZero($ret['start_minute']);
        $ret['end_minute'] = leadingZero($ret['end_minute']);
        return $ret;
    }

    function autoAssignIssues($themen, $filterStart, $filterEnd)
    {
        $this->readSingleDates($filterStart, $filterEnd);
        $z = 0;
        foreach ($this->termine as $key => $val) {
            if (sizeof($val->getIssueIDs()) == 0) {
                if (!$themen[$z]) break;
                if (!$val->isExTermin()) {
                    $this->termine[$key]->addIssueID($themen[$z++]);
                }
            }
        }
        $this->store();
    }

    function removeRequest($singledate_id, $filterStart, $filterEnd)
    {
        $this->readSingleDates($filterStart, $filterEnd);
        $this->termine[$singledate_id]->removeRequest();
    }
}
