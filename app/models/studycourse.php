<?php
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
 * @since       Stud.IP version 1.12
 */

/**
 * @uses        StudipCacheFactory
 * @uses        DBManager
 *
 */
class StudycourseModel
{
    const STUDYCOURSE_CACHE_KEY = "/admin/studycourse/";
    const STUDYDEGREE_CACHE_KEY = "/admin/studydegree/";

    /**
     * Get all course of study (profession with all degrees) (if $sci == null)
     * Get one course of study (one profession with all degrees) (if $sci != null)
     *
     * @param   md5 $sci id of a studiengang
     * @return  array() list of courses
     */
    public static function getStudyCourses($sci = NULL)
    {
        $cache = StudipCacheFactory::getCache();
        $studycourses = unserialize($cache->read(self::STUDYCOURSE_CACHE_KEY.$sci));

        if (empty($studycourses)) {
            if (! is_null($sci)) {
                //one profession with id and count studys
                $query = "SELECT s.studiengang_id, s.name,s.beschreibung, "
                       . "count(user_studiengang.studiengang_id) AS count_user, "
                       . "count(admission_seminar_studiengang.seminar_id) AS count_sem "
                       . "FROM studiengaenge s "
                       . "LEFT JOIN user_studiengang USING(studiengang_id) "
                       . "LEFT JOIN admission_seminar_studiengang USING(studiengang_id)"
                       . "WHERE s.studiengang_id='{$sci}' "
                       . "GROUP BY studiengang_id ORDER BY name";
            } else {
                // all profession
                $query = "SELECT s.studiengang_id,s.name,s.beschreibung, "
                       . "count(user_studiengang.studiengang_id) AS count_user, "
                       . "count(admission_seminar_studiengang.seminar_id) AS count_sem "
                       . "FROM studiengaenge s "
                       . "LEFT JOIN user_studiengang USING(studiengang_id) "
                       . "LEFT JOIN admission_seminar_studiengang USING(studiengang_id) "
                       . "GROUP BY studiengang_id ORDER BY name";
            }
            $studycourses = DBManager::get()->query($query)->fetchAll(PDO::FETCH_ASSOC);
            foreach ($studycourses as $index => $row) {
                // get one profession with all degrees
                $query = "SELECT DISTINCT abschluss.name, abschluss.abschluss_id, "
                       . "count(user_studiengang.abschluss_id) AS count_user "
                       . "FROM abschluss LEFT JOIN user_studiengang USING(abschluss_id) "
                       . "WHERE user_studiengang.studiengang_id='{$row['studiengang_id']}' "
                       . "GROUP BY abschluss_id ORDER BY name";
                $studycourses[$index]['degree'] = DBManager::get()->query($query)->fetchAll(PDO::FETCH_ASSOC);
            }
            $cache->write(self::STUDYCOURSE_CACHE_KEY.$sci, serialize($studycourses));
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
        $cache = StudipCacheFactory::getCache();
        $studydegrees = unserialize($cache->read(self::STUDYDEGREE_CACHE_KEY.$sdi));

        if (empty($studydegrees)) {
            if (isset($sdi)) {
                // one degree with count user
                $query = "SELECT a.abschluss_id, a.name, count(us.abschluss_id) AS count_user "
                       . "FROM abschluss a LEFT JOIN user_studiengang us USING (abschluss_id) "
                       . "WHERE a.abschluss_id = '{$sdi}' "
                       . "GROUP BY a.abschluss_id ORDER BY a.name";
            } else {
                // get  all degrees
                $query = "SELECT a.abschluss_id, a.name, count(us.abschluss_id) AS count_user "
                       . "FROM abschluss a LEFT JOIN user_studiengang us USING (abschluss_id) "
                       . "GROUP BY a.abschluss_id ORDER BY a.name";
            }
            $studydegrees = DBManager::get()->query($query)->fetchAll(PDO::FETCH_ASSOC);
            foreach ($studydegrees as $index => $row) {
                // one degree with all professions
                $query = "SELECT DISTINCT studiengaenge.name, studiengaenge.studiengang_id, "
                       . "count(user_studiengang.studiengang_id) AS count_user "
                       . "FROM studiengaenge LEFT JOIN user_studiengang USING(studiengang_id) "
                       . "WHERE user_studiengang.abschluss_id='{$row['abschluss_id']}' "
                       . "GROUP BY studiengang_id ORDER BY name";
                $studydegrees[$index]['profession'] = DBManager::get()->query($query)->fetchAll(PDO::FETCH_ASSOC);
            }
            $cache->write(self::STUDYDEGREE_CACHE_KEY.$sdi, serialize($studydegrees));
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
        $cache = StudipCacheFactory::getCache();
        $cache->expire(self::STUDYCOURSE_CACHE_KEY.$prof_id);
        $cache->expire(self::STUDYCOURSE_CACHE_KEY);

        return DBManager::get()->exec("DELETE FROM studiengaenge WHERE studiengang_id = '{$prof_id}'");
    }

    /**
     * Delete a degree
     * @param   md5 $deg_id
     * @return  int the number of deletet rows
     */
    public static function deleteStudyDegree($deg_id)
    {
        $cache = StudipCacheFactory::getCache();
        $cache->expire(self::STUDYDEGREE_CACHE_KEY.$deg_id);
        $cache->expire(self::STUDYDEGREE_CACHE_KEY);

        return DBManager::get()->exec("DELETE FROM abschluss WHERE abschluss_id = '{$deg_id}'");
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
            $query = "SELECT s.studiengang_id, s.name,s.beschreibung "
                   . "FROM studiengaenge s "
                   . "WHERE s.studiengang_id = '{$prof_id}'";
            return DBManager::get()->query($query)->fetch(PDO::FETCH_ASSOC);
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
            $query = "SELECT a.abschluss_id, a.name,a.beschreibung "
                   . "FROM abschluss a "
                   . "WHERE a.abschluss_id = '{$deg_id}'";
            return DBManager::get()->query($query)->fetch(PDO::FETCH_ASSOC);
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
        $cache = StudipCacheFactory::getCache();
        $cache->expire(self::STUDYCOURSE_CACHE_KEY);

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
        $cache = StudipCacheFactory::getCache();
        $cache->expire(self::STUDYDEGREE_CACHE_KEY);

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
        $cache = StudipCacheFactory::getCache();
        $cache->expire(self::STUDYCOURSE_CACHE_KEY.$prof_id);
        $cache->expire(self::STUDYCOURSE_CACHE_KEY);

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
        $cache = StudipCacheFactory::getCache();
        $cache->expire(self::STUDYDEGREE_CACHE_KEY.$deg_id);
        $cache->expire(self::STUDYDEGREE_CACHE_KEY);

        $stmt = DBManager::get()->prepare("UPDATE abschluss SET name=?, beschreibung=?, chdate=? WHERE abschluss_id=?");
        $stmt->execute(array($deg_name , $deg_desc , time() , $deg_id));
    }
}
