<?php
# Lifter010: TODO
/**
 * studycourse.php - model class for the studycourses
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Nico Müller <nico.mueller@uni-oldenburg.de>
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @copyright   2010 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     studycourses
 * @since       2.0
 */

/**
 * @uses        DBManager
 *
 */
class StudycourseModel
{

    /**
     * Get all course of study (profession with all degrees) (if $sci == null)
     * Get one course of study (one profession with all degrees) (if $sci != null)
     *
     * @param   md5 $sci id of a studiengang
     * @return  array() list of courses
     */
    public static function getStudyCourses($sci = NULL)
    {
        if (!is_null($sci)) {
            //one profession with id and count studys
            $query = "SELECT s.studiengang_id, s.name, s.beschreibung, 
                             COUNT(user_studiengang.studiengang_id) AS count_user, 
                             COUNT(admission_seminar_studiengang.seminar_id) AS count_sem
                      FROM studiengaenge AS s
                      LEFT JOIN user_studiengang USING (studiengang_id)
                      LEFT JOIN admission_seminar_studiengang USING (studiengang_id)
                      WHERE s.studiengang_id = ?
                      GROUP BY studiengang_id
                      ORDER BY name";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($sci));
            $studycourses = $statement->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // all profession
              $query1 = "SELECT studiengang_id, name, beschreibung "
                    . "FROM studiengaenge ORDER BY name";
              $query2 = "SELECT studiengang_id, count(user_id) AS count_user "
                    . "FROM user_studiengang GROUP BY studiengang_id";
              $query3 = "SELECT studiengang_id, count(seminar_id) AS count_sem "
                    . "FROM admission_seminar_studiengang GROUP BY studiengang_id";
            $studycourses = DBManager::get()->query($query1)->fetchAll(PDO::FETCH_ASSOC);
            $users = DBManager::get()->query($query2)->fetchGrouped(PDO::FETCH_COLUMN);
            $seminars = DBManager::get()->query($query3)->fetchGrouped(PDO::FETCH_COLUMN);
            foreach ($studycourses as $index => $course) {
                $studycourses[$index]['count_user'] = $users[$course['studiengang_id']][0];
                $studycourses[$index]['count_sem'] = $seminars[$course['studiengang_id']][0];
            }
        }

        $query = "SELECT DISTINCT abschluss.name, abschluss.abschluss_id,
                         COUNT(user_studiengang.abschluss_id) AS count_user
                  FROM abschluss
                  LEFT JOIN user_studiengang USING (abschluss_id)
                  WHERE user_studiengang.studiengang_id = ?
                  GROUP BY abschluss_id
                  ORDER BY name";
        $statement = DBManager::get()->prepare($query);

        foreach ($studycourses as $index => $row) {
            // get one profession with all degrees
            $statement->execute(array($row['studiengang_id']));
            $studycourses[$index]['degree'] = $statement->fetchAll(PDO::FETCH_ASSOC);
            $statement->closeCursor();
        }

        return $studycourses;
    }

    /**
     * Get all degrees (degrees with all profession) (if $sdi == null)
     * Get one degree (one degree with all profession) (if $sdi != null)
     *
     * @param   md5 $sdi
     * @return  array() list of studydegrees
     */
    public static function getStudyDegrees($sdi = NULL)
    {
        if (isset($sdi)) {
            // one degree with count user
            $query = "SELECT a.abschluss_id, a.name,
                             COUNT(us.abschluss_id) AS count_user
                      FROM abschluss AS a
                      LEFT JOIN user_studiengang AS us USING (abschluss_id)
                      WHERE a.abschluss_id = ?
                      GROUP BY a.abschluss_id
                      ORDER BY a.name";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($sdi));
            $studydegrees = $statement->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // get  all degrees
            $query1 = "SELECT abschluss_id, name FROM abschluss ORDER BY name";
            $query2 = "SELECT abschluss_id, count(abschluss_id) AS count_user "
                    . "FROM user_studiengang GROUP BY abschluss_id";

            $studydegrees = DBManager::get()->query($query1)->fetchAll(PDO::FETCH_ASSOC);
            $users = DBManager::get()->query($query2)->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_GROUP);

            foreach ($studydegrees as $index => $degree) {
                $studydegrees[$index]['count_user'] = $users[$degree['abschluss_id']][0];
            }
        }

        $query = "SELECT DISTINCT studiengaenge.name, studiengaenge.studiengang_id,
                         COUNT(user_studiengang.studiengang_id) AS count_user
                  FROM studiengaenge
                  LEFT JOIN user_studiengang USING (studiengang_id)
                  WHERE user_studiengang.abschluss_id = ?
                  GROUP BY studiengang_id
                  ORDER BY name";
        foreach ($studydegrees as $index => $row) {
            // one degree with all professions
            $statement->execute(array($row['abschluss_id']));
            $studydegrees[$index]['profession'] = $statement->fetchAll(PDO::FETCH_ASSOC);
            $statement->closeCursor();
        }

        return $studydegrees;
    }

    /**
     * Delete a profession
     *
     * @param   md5 $prof_id
     * @return  int the number of deletet rows
     */
    public static function deleteStudyCourse($prof_id)
    {
        $query = "DELETE FROM studiengaenge WHERE studiengang_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($prof_id));
        return $statement->rowCount();
    }

    /**
     * Delete a degree
     * @param   md5 $deg_id
     * @return  int the number of deletet rows
     */
    public static function deleteStudyDegree($deg_id)
    {
        $query = "DELETE FROM abschluss WHERE abschluss_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($deg_id));
        return $statement->rowCount();
    }

    /**
     * Get infos about the profession
     *
     * @param   md5 $prof_id
     * @return  array() profession
     */
    public static function getStudyCourseInfo($prof_id = NULL)
    {
        if (!is_null($prof_id)) {
            $query = "SELECT studiengang_id, name, beschreibung
                      FROM studiengaenge s
                      WHERE studiengang_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($prof_id));
            return $statement->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    /**
     * Get infos about the degree
     *
     * @param   md5 $deg_id
     * @return  array() degree
     */
    public static function getStudyDegreeInfo($deg_id = NULL)
    {
        if (!is_null($deg_id)) {
            $query = "SELECT abschluss_id, name, beschreibung
                      FROM abschluss
                      WHERE abschluss_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($deg_id));
            return $statement->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    /**
     * Create a new profession
     *
     * @param   string $prof_name new name of the profession
     * @param   string $prof_desc new description of the profession [optional]
     */
    public static function saveNewProfession($prof_name, $prof_desc)
    {
        // Create an id
        $prof_id = md5(uniqid($prof_name.$prof_desc));
        $stmt = DBManager::get()->prepare("INSERT INTO studiengaenge (studiengang_id, name, beschreibung, mkdate, chdate) VALUES(?, ?, ?, ?, ?)");
        $stmt->execute(array($prof_id, $prof_name, $prof_desc, time(), time()));
    }

    /**
     * Create a new degree
     *
     * @param   string $deg_name new name of the degree
     * @param   string $deg_desc new description of the degree
     */
    public static function saveNewDegree($deg_name, $deg_desc)
    {
        // Create an id
        $deg_id = md5(uniqid($deg_name.$deg_desc));
        $stmt = DBManager::get()->prepare("INSERT INTO abschluss (abschluss_id, name, beschreibung, mkdate, chdate) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(array($deg_id, $deg_name, $deg_desc, time(), time()));
    }

    /**
     * Check, if the profession already exists
     *
     * @param   string $prof_name
     * @return  bool true, if exists
     */
    public static function checkProfession($prof_name)
    {
        $stmt = DBManager::get()->prepare("SELECT count(name) FROM studiengaenge WHERE name=?");
        $stmt->execute(array($prof_name));
        $check = $stmt->fetchColumn();
        if ($check > 0) {
            return true;
        }
        return false;
    }

    /**
     * Check, if the degree already exists
     *
     * @param   $prof_name
     * @return  bool true, if exists
     */
    public static function checkDegree($deg_name)
    {
        $stmt = DBManager::get()->prepare("SELECT count(name) FROM abschluss WHERE name=?");
        $stmt->execute(array($deg_name));
        $check = $stmt->fetchColumn();
        if ($check > 0) {
            return true;
        }
        return false;
    }

    /**
     * Save changes of the profession
     *
     * @param   md5 $prof_id
     * @param   string $prof_name
     * @param   string $prof_desc
     */
    public static function saveEditProfession($prof_id, $prof_name, $prof_desc)
    {
        $stmt = DBManager::get()->prepare("UPDATE studiengaenge SET name=?, beschreibung=?, chdate=? WHERE studiengang_id=?");
        $stmt->execute(array($prof_name, $prof_desc, time(), $prof_id));
    }

    /**
     * Save changes of the degree
     *
     * @param   md5 $deg_id
     * @param   string $deg_name
     * @param   string $deg_desc
     */
    public static function saveEditDegree($deg_id, $deg_name, $deg_desc)
    {
        $stmt = DBManager::get()->prepare("UPDATE abschluss SET name=?, beschreibung=?, chdate=? WHERE abschluss_id=?");
        $stmt->execute(array($deg_name , $deg_desc , time() , $deg_id));
    }
}
