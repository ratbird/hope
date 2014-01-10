<?php

class CoursesetModel {

    public function getInstCourses($instituteIds, $coursesetId='', $selectedCourses=array()) {
		// Get semester dates for course sorting.
        $sem = SemesterData::getInstance();
        $semesters = $sem->GetSemesterArray();
		$currentSemester = $sem->getCurrentSemesterData();
		// Construct SQL query.
        $select = "SELECT DISTINCT s.`seminar_id`";
        $from = " FROM `seminare` s JOIN `seminar_inst` si ON (s.`Seminar_id`=si.`seminar_id`)";
        $where = " WHERE (s.`start_time`=-1 OR s.`start_time`+s.`duration_time`>=?) 
        	AND si.`institut_id` IN ('".implode("', '", $instituteIds)."')";
        $order = " ORDER BY s.`start_time` DESC, s.`VeranstaltungsNummer` ASC, s.`Name` ASC";
        $parameters = array($currentSemester['beginn']);
		/*
		 * Course set ID given, we need do include all courses that are already
		 * assigned to the given set.
		 */
        if ($coursesetId) {
        	$from .= " JOIN `seminar_courseset` sc ON (s.`Seminar_id`=sc.`seminar_id`)";
            $where .= " OR sc.`set_id`=?";
            $parameters[] = $coursesetId;
        }
		// Courses that have been selected manually in configuration dialogue.
        if ($selectedCourses) {
            $where .= " OR si.`seminar_id` IN ('".implode("', '", $selectedCourses)."')";
        }
        $query = $select.$from.$where.$order;
        $stmt = DBManager::get()->prepare($query);
        $stmt->execute($parameters);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $courses = array();
        // Get all found courses...
        foreach ($data as $entry) {
        	$course = new Course($entry['seminar_id']);
        	// ... set correct semester for multi-semester courses ...
        	if ($course->duration_time == -1) {
            	$semester_id = $currentSemester['semester_id'];
			} else {
				$semester_id = $sem->GetSemesterIdByDate($course->start_time+$course->duration_time);
			}
			// Check if semester name is already set and set it if necessary.
			if (!$courses[$semester_id]['name']) {
				foreach ($semesters as $semester) {
					if ($semester['semester_id'] == $semester_id) {
						$courses[$semester_id]['name'] = $semester['name'];
						break;
					}
				}
			}
			// ... and sort them in at the right semester.
            $courses[$semester_id]['courses'][$course->Seminar_id] =
            	array(
            		'seminar_id' => $course->Seminar_id,
            		'VeranstaltungsNummer' => $course->VeranstaltungsNummer,
            		'Name' => $course->Name,
            		'admission_turnout' => $course->admission_turnout
				);
        }
        return $courses;
    }

}

?>