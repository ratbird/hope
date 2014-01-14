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

    static function getInstitutes($filter = array())
    {
        global $perm, $user;
        
        $parameters = array(1);
        $query = "SELECT COUNT(DISTINCT ci.set_id) FROM courseset_institute ci 
        LEFT JOIN coursesets c ON c.set_id = ci.set_id
        LEFT JOIN courseset_rule cr ON c.set_id = cr.set_id
        LEFT JOIN seminar_courseset sc ON c.set_id = sc.set_id
        LEFT JOIN seminare s ON s.seminar_id = sc.seminar_id
        WHERE ci.institute_id = ?";
        if ($filter['course_set_name']) {
            $query .= " AND c.name LIKE ?";
            $parameters[] = $filter['course_set_name'] . '%';
        }
        if (is_array($filter['rule_types']) && count($filter['rule_types'])) {
            $query .= " AND cr.type IN (?)";
            $parameters[] = $filter['rule_types'];
        }
        if ($filter['semester_id']) {
            $query .= " AND s.start_time = ?";
            $parameters[] = Semester::find($filter['semester_id'])->beginn;
        }
        $cs_count_statement = DBManager::get()->prepare($query);
        $query = str_replace('ci.institute_id', '1', $query);
        $cs_count_all_statement = DBManager::get()->prepare($query);

        if ($perm->have_perm('root')) {
            $cs_count_all_statement->execute($parameters);
            $num_sets = $cs_count_all_statement->fetchColumn();

            $my_inst['all'] = array(
                    'name'    => _('alle'),
                    'num_sets' => $num_sets
            );
            $top_insts = Institute::findBySQL('Institut_id = fakultaets_id ORDER BY Name');
        } else {
            $top_insts = Institute::findMany(User::find($user->id)->institute_memberships->findBy('inst_perms', words('admin dozent'))->pluck('institut_id'),'ORDER BY institut_id=fakultaets_id,name');
        }
        foreach ($top_insts as $inst) {
            $my_inst[$inst->id] = $inst->toArray('name is_fak');
            $parameters[0] = $inst->id;
            $cs_count_statement->execute($parameters);
            $my_inst[$inst->id]['num_sets'] = $cs_count_statement->fetchColumn();
            if ($inst->is_fak && ($perm->have_perm('root') || $inst->members->findBy('user_id', $user->id)->val('inst_perms') == 'admin')) {
                $alle = $inst->sub_institutes;
                if (count($alle)) {
                    $my_inst[$inst->id . '_all'] = array(
                            'name'    => sprintf(_('[Alle unter %s]'), $inst->name),
                            'is_fak'  => 'all'
                    );
    
                    $num_inst = 0;
                    $num_sets_alle = $my_inst[$inst->id]['num_sets'];
    
                    foreach ($alle as $institute) {
                       $num_inst += 1;
                       $my_inst[$institute->id] = $institute->toArray('name is_fak');
                       $parameters[0] = $institute->id;
                       $cs_count_statement->execute($parameters);
                       $my_inst[$institute->id]['num_sets'] = $cs_count_statement->fetchColumn();
                       $num_sets_alle += $my_inst[$institute->id]['num_sets'];
                    }
                    $my_inst[$inst->id . '_all']['num_inst'] = $num_inst;
                    $my_inst[$inst->id . '_all']['num_sets']  = $num_sets_alle;
                }
            }
        }
        return $my_inst;
    }
}

?>