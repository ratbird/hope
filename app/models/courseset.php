<?php

class CoursesetModel {

    public function getInstCourses($instituteIds, $coursesetId='', $selectedCourses=array()) {
        $parameters = array();
        $query = "SELECT DISTINCT si.`seminar_id`, s.`VeranstaltungsNummer`, s.`Name`, s.admission_turnout,
                    IF(s.`duration_time`=-1, UNIX_TIMESTAMP(), s.`start_time`+s.`duration_time`) AS start
                  FROM `seminar_inst` si
                  JOIN `seminare` AS s ON (si.`seminar_id` = s.`Seminar_id`)
                  JOIN `semester_data` sd ON (s.`duration_time`=-1 OR s.`start_time`+s.`duration_time` BETWEEN sd.`beginn` AND sd.`ende`)
                  LEFT JOIN `seminar_courseset` AS sc ON (s.`Seminar_id`=sc.`seminar_id`)
                  WHERE (si.`Institut_id` IN ('".
                  implode("', '", array_keys($instituteIds))."')
                  AND (sc.`set_id` IS NULL
                  AND sd.`ende` >= UNIX_TIMESTAMP())";
        if ($coursesetId) {
            $query .= " OR sc.`set_id`=?";
            $parameters[] = $coursesetId;
        }
        if ($selectedCourses) {
            $query .= " OR sc.`seminar_id` IN ('".implode("', '", $selectedCourses)."')";
        }
        $query .= ") ORDER BY start DESC, s.VeranstaltungsNummer ASC, s.Name ASC";
        $stmt = DBManager::get()->prepare($query);
        $stmt->execute($parameters);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $semesters = SemesterData::GetSemesterArray();
        $courses = array();
        foreach ($data as $entry) {
            $semester_id = SemesterData::GetSemesterIdByDate($entry['start']);
            if (!$courses[$semester_id]['name']) {
                foreach ($semesters as $semester) {
                    if ($semester['beginn'] <= $entry['start'] && $semester['ende'] >= $entry['start']) {
                        $courses[$semester_id]['name'] = $semester['name'];
                        break;
                    }
                }
            }
            $courses[$semester_id]['courses'][] = $entry;
        }
        return $courses;
    }

}

?>