<?php
# Lifter002: DONE - not applicable
# Lifter003: TEST
# Lifter007: TEST
# Lifter010: DONE - not applicable

/**
 * Creates a record of study and exports the data to pdf (database)
 *
 * @author      Christian Bauer <alfredhitchcock@gmx.net>
 * @version     $Exp
 * @copyright   2003 Stud.IP-Project
 * @access      public
 * @module      recordofstudy
 */
require_once 'lib/dates.inc.php';
require_once 'config.inc.php';
require_once 'lib/classes/SemesterData.class.php';

/**
 * collect the current seminars and concerning semesters from the archiv    
 *
 * @access  private
 * @return  array the semesters
 *
 */
function getSemesters()
{
    global $user;

    $semester_in_db = array();

    // creating the list of avaible semester
    foreach (SemesterData::GetSemesterArray() as $key => $value) {
        $semestersAR[$key] = array(
            'id'     => $key,
            'idname' => $value['name'],
            'name'   => convertSemester($value['name']),
            'beginn' => $value['beginn'],
        );
        $semester_in_db[] = $value['name'];
    }

    unset($semestersAR[0]);
    unset($semester_in_db[0]);

    $i = $key + 1;

    // adding the semester from avaible archiv-items
    $query = "SELECT start_time, semester
              FROM archiv_user
              LEFT JOIN archiv USING (seminar_id)
              WHERE user_id = ?
              ORDER BY start_time DESC";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user->id));
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        if (in_array($row['semester'], $semester_in_db)){
            continue;
        }
        $semestersAR[$i] = array(
            'id'         => $i,
            'idname'     => $row['semester'],
            'name'       => convertSemester($row['semester']),
            'beginn'     => $row['start_time'],
            'onlyarchiv' => 1,
        );
        $i += 1;
    }
    
    uasort ($semestersAR, function ($a, $b) {
        return $a['beginn'] - $b['beginn'];
    });
    return $semestersAR;
}

/**
 * collects the basic data from the db
 *
 * @access  private
 * @return  array   the basic data
 */
function getBasicData()
{
    return array(
        'fieldofstudy'  => getFieldOfStudy(),
        'studentname'   => $GLOBALS['user']->getFullName(),
    );
}

/**
 * gets the field of study of the current user from the db
 *
 * @access  private
 * @return  string  the field of study 
 */
function getFieldOfStudy()
{
    $query = "SELECT GROUP_CONCAT(studiengaenge.name SEPARATOR ' ')
              FROM user_studiengang
              LEFT JOIN studiengaenge USING (studiengang_id)
              WHERE user_id = ?
              ORDER BY studiengang_id";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($GLOBALS['user']->id));
    return $statement->fetchColumn() . ' ';
}
 
/**
 * gets the complete name of the student
 *
 * @access  private
 * @return string  the complete name
 */
function getStudentname()
{
    return $GLOBALS['user']->getFullName();
}

/**
 * gets the seminars of the currents user from the db
 *
 * @access  private
 * @param   string  $semesterid     the selected semester id
 * @param   boolean $onlyseminars   could reduce the assortment
 * @return  array   the seminars
 *
 */
function getSeminare($semesterid,$onlyseminars)
{
    global $user,$semestersAR,$SEM_CLASS,$SEM_TYPE,$_fullname_sql;

    $i = 0;
    // if its not an archiv-only-semester, get the current ones
    if (!$semestersAR[$semesterid]['onlyarchiv']) {

        // the status the user should have in the seminar
        $status = 'autor';

        // some stolen code from a.noack :)
        foreach (SemesterData::GetSemesterArray() as $key => $value) {
            if (!empty($value['beginn'])) {
                $sem_start_times[] = $value['beginn'];
            }
        }
        foreach ($SEM_CLASS as $key => $value) {
            if ($value['bereiche']) {
                foreach($SEM_TYPE as $type_key => $type_value) {
                    if ($type_value['class'] == $key) {
                        $allowed_sem_status[] = $type_key;
                    }
                }
            }
        }

        // Prepare tutor statement
        $query = "SELECT GROUP_CONCAT({$_fullname_sql['full']}, '; ')
                  FROM seminar_user
                  LEFT JOIN auth_user_md5 USING (user_id)
                  LEFT JOIN user_info USING (user_id)
                  WHERE seminar_user.Seminar_id = ? AND status = 'dozent'
                  ORDER BY position, Nachname";
        $tutor_statement = DBManager::get()->prepare($query);

        // Prepare and execute statement that obtains all seminars for the current user
        $query = "SELECT b.Seminar_id, b.Name, b.Untertitel, b.VeranstaltungsNummer,
                          INTERVAL(start_time, :sem_start) AS sem_number , 
                          IF(duration_time = -1, -1, INTERVAL(start_time + duration_time, :sem_start)) AS sem_number_end
                   FROM seminar_user AS a
                   LEFT JOIN seminare b USING (Seminar_id)
                   WHERE (:allowed_status IS NULL OR b.status IN (:allowed_status))
                     AND a.user_id = :user_id AND a.status = :status
                   HAVING (sem_number <= :sem_number AND (sem_number_end = -1 OR sem_number_end >= :sem_number))";
        $statement = DBManager::get()->prepare($query);
        $statement->bindValue(':sem_start', $sem_start_times);
        $statement->bindValue(':allowed_status', $onlyseminars ? ($allowed_sem_status ?: null) : null);
        $statement->bindValue(':user_id', $user->id);
        $statement->bindValue(':status', $status);
        $statement->bindValue(':sem_number', $semestersAR[$semesterid]['id']);
        $statement->execute();

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $seminarid        = $row['Seminar_id'];
            $name             = $row['Name'];
            $seminarnumber    = $row['VeranstaltungsNummer'];

            if ($row['Untertitel']) {
                $name .= ': ' . $row['Untertitel'];
            }

            $tutor_statement->execute(array($seminarid));
            $tutor = $tutor_statement->fetchColumn() ?: '';
            $tutor_statement->closeCursor();

            $seminare[$i] = array(
                'id'            => $i,
                'seminarid'     => $seminarid,
                'seminarnumber' => $seminarnumber,
                'tutor'         => $tutor,
                'sws'           => '',
                'description'   => $name 
            );
            $i += 1;
        }
    }

     //archiv seminars
     $query = "SELECT archiv.name, archiv.seminar_id,
                      archiv.VeranstaltungsNummer,
                      archiv.untertitel, archiv.studienbereiche, archiv.dozenten
               FROM archiv_user
               LEFT JOIN archiv USING (seminar_id)
               WHERE archiv_user.user_id = ? AND archiv.semester = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array(
        $user->id,
        $semestersAR[$semesterid]['idname'],
    ));
    while ($row = $statement->fetchColumn()) {
        $seminarid     = $row['seminar_id'];
        $name          = $row['name'];
        $seminarnumber = $row['VeranstaltungsNummer'];
        $tutor         = $row['dozenten'];

        if ($row['untertitel']) {
            $name .= ': ' . $row['untertitel'];
        }

        if (!$onlyseminars || $row['studienbereiche']) {
            $seminare[$i] = array(
                'id'            => $i,
                'seminarid'     => $seminarid,
                'seminarnumber' => $seminarnumber,
                'tutor'         => $tutor,
                'description'   => $name 
            );
            $i += 1;
        }
    }
    return $seminare;
}
