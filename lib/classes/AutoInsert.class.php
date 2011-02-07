<?php
/*
 * AutoInsert.class.php - administrate seminars for automatical logins
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Jan Hendrik Willms <jan.hendrik.willms@uni-oldenburg.de>
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       2.1
 */

require_once 'lib/functions.php';

class AutoInsert
{
    /**
     * Check if exist at least 1 seminar for the auto insert function
     */
    public static function existSeminars()
    {
        $stmt = DBManager::get()->query("SELECT count(*) FROM auto_insert_sem");
        $check = $stmt->fetchColumn();

        return ($check > 0);
    }

    /**
     * Is there already an user entry
     * @param $user_id
     * @param $seminar_id
     */
    private static function checkUser($user_id, $seminar_id)
    {
        $stmt = DBManager::get()->prepare("SELECT count(*) FROM seminar_user WHERE user_id = ? and seminar_id = ?");
        $stmt->execute(array($user_id, $seminar_id));
        $check = $stmt->fetchColumn();

        return ($check > 0);
    }

    /**
     * Check user status
     * @param $user_id
     * @param $seminar_id
     */
    private static function checkUserStatus($user_id,$seminar_id)
    {
        $query = "SELECT status FROM seminar_user WHERE user_id = '{$user_id}' and seminar_id = '{$seminar_id}'";
        return DBManager::get()->query($query)->fetchColumn();
    }

    /**
     * If exist user, update the status (relevant to the status "user")
     * @param $user_id
     * @param $seminar_id
     */
    private static function updateUserStatus($user_id,$seminar_id)
    {
        $stmt = DBManager::get()->prepare("UPDATE seminar_user SET status = 'autor' "
                                         ."WHERE Seminar_id = ? "
                                         ."AND user_id = ?");
        $stmt->execute(array($seminar_id, $user_id));
    }


    /**
     * Inserts a user into all matching seminars
     * @param $user_id
     */
    public static function engage($user_id)
    {

    }

    /**
     * Insert a user in the seminar_user tabelle
     * @param $user_status (current user status )
     * @param $user_id
     * @param $sem (status which a user must have to be registered)
     */
    private static function saveUser($user_status, $user_id, $sem)
    {
        if (in_array($user_status, $sem['status']) && ($sem['Schreibzugriff']) < 2){
            // insert the user in the seminar_user table
            $stmt = DBManager::get()->prepare("INSERT IGNORE INTO seminar_user (Seminar_id, user_id, status, gruppe) "
                                              ."VALUES(?, ?, ?, ?)");
            $stmt->execute(array($sem['seminar_id'], $user_id, 'autor', select_group($sem['start_time'])));
            return true;
        }
        return false;
    }

    /**
     * Check of no duplicate entries
     * @param $seminar_id
     */
    public static function checkSeminar($seminar_id)
    {
        $stmt = DBManager::get()->prepare("SELECT count(*) FROM auto_insert_sem WHERE seminar_id=?");
        $stmt->execute(array($seminar_id));
        $check = $stmt->fetchColumn();

        return ($check > 0);
    }

    /**
     * Insert a new seminar, for the auto insert function
     * @param $seminar_id
     * @param $status (array wiht string autor, tutor or dozent)
     */
    public static function saveSeminar($seminar_id, $status)
    {
        $stmt = DBManager::get()->prepare("INSERT INTO auto_insert_sem (seminar_id,status)  VALUES(?, ?)");
        foreach ($status as $s)
        {
            $stmt->execute(array($seminar_id, $s));
        }
    }

    /**
     *
     * @param md5 $seminar_id
     * @param string $status
     * @param int $change
     */
    public static function updateSeminar($seminar_id, $status, $remove)
    {
        if ($remove == 1) {
            $stmt = DBManager::Get()->prepare("DELETE FROM auto_insert_sem WHERE seminar_id = ? AND status = ?");
            $stmt->execute(array($seminar_id, $status));
        } else {
            $stmt = DBManager::Get()->prepare("INSERT IGNORE INTO auto_insert_sem (seminar_id, status) VALUES (?, ?)");
            $stmt->execute(array($seminar_id, $status));
        }
    }

    /**
     * Delete a auto insert seminar
     * @param $seminar_id
     */
    public static function deleteSeminar($seminar_id)
    {
        return DBManager::get()->exec("DELETE FROM auto_insert_sem WHERE seminar_id = '{$seminar_id}'");
    }

    /**
     * Show me all auto insert seminars
     */
    public static function getAllSeminars($only_sem_id = false)
    {
        $query = "SELECT a.seminar_id, GROUP_CONCAT(a.status) AS status, seminare.Name, seminare.Schreibzugriff,seminare.start_time "
                . "FROM auto_insert_sem a "
                . "LEFT JOIN seminare USING (Seminar_id) "
                . "GROUP BY seminare.seminar_id "
                . "ORDER BY seminare.Name";
        if ($only_sem_id) {
            $results = DBManager::get()->query($query)->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $results = DBManager::get()->query($query)->fetchAll(PDO::FETCH_ASSOC);
            foreach ($results as $index=>$result ){
                $results[$index]['status'] = explode(',',$result['status']);
            }
        }
        return $results;
    }

    /**
     * Show me one auto inser seminar
     * @param  $seminar_id
     */
    public static function getSeminar($seminar_id)
    {
        $query = "SELECT a.seminar_id, GROUP_CONCAT(a.status) AS status, seminare.Name "
                . "FROM auto_insert_sem a "
                . "LEFT JOIN seminare USING (Seminar_id)"
                . "WHERE a.seminar_id = '{$seminar_id}'"
                . "GROUP BY seminare.seminar_id ";
        $result = DBManager::get()->query($query)->fetch(PDO::FETCH_ASSOC);
        $result['status'] = explode(',',$result['status']);
        return $result;
    }

    /**
     * Insert the user, so you know in which courses he was already entered.
     * @param $user_id
     * @param $seminar_id
     */
    public static function saveAutoInsertUser($seminar_id,$user_id)
    {
        // insert the user in the auto_insert_user table,
        // if the user exist in this table, the user would not registred again in the seminar_user table
        $stmt = DBManager::get()->prepare("INSERT INTO auto_insert_user (Seminar_id, user_id, mkdate) "
                                          ."VALUES(?, ?, NOW())");
        $stmt->execute(array($seminar_id, $user_id));
        return true;
    }

    /**
     * Check of user exist
     * @param $seminar_id
     * @param $user_id
     */
    public static function checkAutoInsertUser($seminar_id, $user_id)
    {
        // if the user exist in this table, the user would not registred again in the seminar_user table
        $query = "SELECT IF(COUNT(*)>0,1,0) FROM auto_insert_user "
                ."WHERE seminar_id = '$seminar_id' AND user_id = '$user_id'";
        return DBManager::get()->query($query)->fetchColumn();
    }

    /**
     * Check of the right status from the new user
     *
     * @param  $status (current user status)
     * @param  $new_user_id (new user id)
     */
    public static function checkNewUser($status, $new_user_id)
    {
        $get_seminars = self::getAllSeminars();
        $seminars_added = array();

        foreach($get_seminars as $sem) {
            if (!self::checkUser($new_user_id, $sem['seminar_id'])) {
                if (self::saveUser($status, $new_user_id, $sem)){
                   array_push($seminars_added, $sem['Name']);
                }
            }
        }
        return $seminars_added;
    }

    /**
     * If changing the status from a exist user
     * Exist a relevant seminar?
     * @param $old_status
     * @param $new_status
     * @param $user_id
     */
    public static function checkOldUser($old_status, $new_status, $user_id)
    {
        if ($old_status == $new_status or !self::existSeminars()) {
            return array();
        }

        $get_seminars = self::getAllSeminars();
        $seminars_added = array();

        foreach($get_seminars as $sem) {
            if (!self::checkUser($user_id, $sem['seminar_id'])) {
                if(self::saveUser($new_status, $user_id, $sem)) {
                    array_push($seminars_added, $sem['Name']);
                }
            } elseif(AutoInsert::checkUserStatus($user_id, $sem['seminar_id']) == 'user'){
                AutoInsert::updateUserStatus($user_id, $sem['seminar_id']);
                array_push($seminars_added, $sem['Name']);
            }
        }

        return $seminars_added;
    }
}
