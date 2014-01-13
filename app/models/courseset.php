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

    static function getInstitutes()
    {
        global $perm, $user;
    
        // Prepare institute statement
        $query = "SELECT a.Institut_id, a.Name, COUNT(set_id) AS num_sets
        FROM Institute AS a
        LEFT JOIN courseset_institute ON (courseset_institute.institute_id = a.Institut_id)
        WHERE fakultaets_id = ? AND a.Institut_id != fakultaets_id
        GROUP BY a.Institut_id
        ORDER BY a.Name, num_sets DESC";
        $institute_statement = DBManager::get()->prepare($query);
    
        $parameters = array();
        if ($perm->have_perm('root')) {
            $query = "SELECT COUNT(DISTINCT set_id) FROM courseset_institute";
            $statement = DBManager::get()->query($query);
            $num_sets = $statement->fetchColumn();
    
            $_my_inst['all'] = array(
                    'name'    => _('alle'),
                    'num_sets' => $num_sets
            );
            $query = "SELECT a.Institut_id, a.Name, 1 AS is_fak, COUNT(set_id) AS num_sets
            FROM Institute AS a
            LEFT JOIN courseset_institute ON (courseset_institute.institute_id = a.Institut_id)
            WHERE a.Institut_id = fakultaets_id
            GROUP BY a.Institut_id
            ORDER BY is_fak, Name, num_sets DESC";
        } else {
            $query = "SELECT s.inst_perms,b.Institut_id, b.Name, b.Institut_id = b.fakultaets_id AS is_fak, COUNT(set_id) AS num_sets
            FROM user_inst AS s
            LEFT JOIN Institute AS b USING ( Institut_id )
            LEFT JOIN courseset_institute ON (courseset_institute.institute_id = a.Institut_id)
            WHERE s.user_id = ? AND s.inst_perms IN('admin','dozent')
            GROUP BY b.Institut_id
            ORDER BY is_fak, Name, num_sets DESC";
            $parameters[] = $user->id;
        }
        $statement = DBManager::get()->prepare($query);
        $statement->execute($parameters);
        $temp = $statement->fetchAll(PDO::FETCH_ASSOC);
    
        foreach ($temp as $row) {
            $_my_inst[$row['Institut_id']] = array(
                    'name'    => $row['Name'],
                    'is_fak'  => $row['is_fak'],
                    'num_sets' => $row['num_sets']
            );
            if ($row["is_fak"] && $row["inst_perms"] != 'dozent') {
                $institute_statement->execute(array($row['Institut_id']));
                $alle = $institute_statement->fetchAll();
                if (count($alle)) {
                    $_my_inst[$row['Institut_id'] . '_all'] = array(
                            'name'    => sprintf(_('[Alle unter %s]'), $row['Name']),
                            'is_fak'  => 'all',
                            'num_sets' => $row['num_sets']
                    );
    
                    $num_inst = 0;
                    $num_sets_alle = $row['num_sets'];
    
                    foreach ($alle as $institute) {
                        if (!$_my_inst[$institute['Institut_id']]) {
                            $num_inst += 1;
                            $num_sets_alle += $institute['num_sets'];
                        }
                        $_my_inst[$institute['Institut_id']] = array(
                                'name'    => $institute['Name'],
                                'is_fak'  => 0,
                                'num_sets' => $institute["num_sets"]
                        );
                    }
                    $_my_inst[$row['Institut_id']]['num_inst']          = $num_inst;
                    $_my_inst[$row['Institut_id'] . '_all']['num_inst'] = $num_inst;
                    $_my_inst[$row['Institut_id'] . '_all']['num_sets']  = $num_sets_alle;
                }
            }
        }
        return $_my_inst;
    }
}

?>