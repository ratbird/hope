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


/**
 * CylceData.class.php
 *
 *
 * @author      Till Glöggler <tgloeggl@uos.de>
 * @version     19. Oktober 2005
 * @access      protected
 * @package     raumzeit
 */

require_once('lib/raumzeit/CycleDataDB.class.php');
require_once('lib/raumzeit/SingleDate.class.php');
require_once('lib/raumzeit/IssueDB.class.php');

class CycleData {
	var $metadate_id = '';
	var $description = NULL;
	var $idx = 0;
	var $start_stunde = 0;
	var $start_minute = 0;
	var $end_stunde = 0;
	var $end_minute = 0;
	var $day = 0;
	var $semester = 0;
	var $resource_id = NULL;
	var $room = NULL;
	var $termine = NULL; // Array

	function CycleData($cycle_data = FALSE) {
		if ($cycle_data) {
			if ($cycle_data['metadate_id']) {
				$this->metadate_id = $cycle_data['metadate_id'];
			} else {
				$this->metadate_id = md5(uniqid('metadate_id'));
			}
			$this->setIdx($cycle_data['idx']);
			$this->setStart($cycle_data['start_stunde'], $cycle_data['start_minute']);
			$this->setEnd($cycle_data['end_stunde'], $cycle_data['end_minute']);
			$this->setDay($cycle_data['day']);
			$this->setDescription($cycle_data['desc']);
			$this->resource_id = $cycle_data['resource_id'];
			$this->room = $cycle_data['room'];
		} else {
			$sems = new SemesterData();
			$semester = $sems->getCurrentSemesterData();
			$this->semester = $semester['beginn'];
			$this->metadate_id = md5(uniqid('MetaDate'));
		}
	}

	function getDescription() {
		return $this->description;
	}

	function setDescription($description) {
		$this->description = $description;
	}

	function setStart($start_stunde, $start_minute) {
		$this->start_stunde = (int)$start_stunde;
		$this->start_minute = (int)$start_minute;
	}

	function setEnd($end_stunde, $end_minute) {
		$this->end_stunde = (int)$end_stunde;
		$this->end_minute = (int)$end_minute;
	}

	function getStartStunde () {
		return $this->start_stunde;
	}

	function getStartMinute () {
		return $this->start_minute;
	}

	function getEndStunde () {
		return $this->end_stunde;
	}

	function getEndMinute () {
		return $this->end_minute;
	}

	function getMetaDateID() {
		return $this->metadate_id;
	}

	function getIdx() {
		return $this->idx;
	}

	function setIdx($idx) {
		$this->idx = $idx;
	}

	function getDay() {
		return $this->day;
	}

	function setDay($day) {
		$this->day = $day;
	}

	function setSemester($semester) {
		$this->semester = $semester;
	}

	function getSemester() {
		return $this->semester;
	}

	function store() {
		foreach ($this->termine as $val) {
			$val->store();
		}

		return TRUE;
	}

	function restore() {
		foreach ($this->termine as $key => $val) {
			$new_termine[$key] = $val->restore();
		}
		$this->termine =& $new_termine;
		return TRUE;
	}

	function delete($removeSingles = TRUE) {
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
		return TRUE;
	}

	function deleteSingleDate($date_id, $filterStart, $filterEnd) {
		if (!$this->termine) {
			$this->readSingleDates($filterStart, $filterEnd);
		}

		$this->termine[$date_id]->setExTermin(true);
		$this->termine[$date_id]->store();
	}
	
	function unDeleteSingleDate($date_id, $filterStart, $filterEnd) {
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

	function readSingleDates($start = 0, $end = 0) {
		$this->termine = array();
		$termin_data = CycleDataDB::getTermine($this->metadate_id, $start, $end);
		if ($termin_data) {
			foreach ($termin_data as $val) {
				unset($termin);
				$termin = new SingleDate();
				$termin->fillValuesFromArray($val);
				$termin->setExTermin($val['ex_termin']);
				$this->termine[$val['termin_id']] =& $termin;
			}
			return TRUE;
		}

		return FALSE;
	}

	function getSingleDates() {
		if (!$this->termine) {
			$this->readSingleDates();
		}
		return $this->termine;
	}

	function getFreeTextPredominantRoom($filterStart = 0, $filterEnd = 0) {
		if ($room = CycleDataDB::getFreeTextPredominantRoomDB($this->metadate_id, $filterStart, $filterEnd)) {
			return $room;
		}
	}

	function getPredominantRoom($filterStart = 0, $filterEnd = 0) {
		if ($rooms = CycleDataDB::getPredominantRoomDB($this->metadate_id, $filterStart, $filterEnd)) {
			return $rooms;
		}

		return FALSE;
	}

	function toString($short = FALSE) {
		return getWeekDay($this->day, $short).($short ? '. ' : ' ').leadingZero($this->start_stunde).':'.leadingZero($this->start_minute).' bis '.leadingZero($this->end_stunde).':'.leadingZero($this->end_minute);
	}


	function autoAssignIssues($themen, $filterStart, $filterEnd) {
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

	function removeRequest($singledate_id, $filterStart, $filterEnd) {
		$this->readSingleDates($filterStart, $filterEnd);
		$this->termine[$singledate_id]->removeRequest();
	}
}
