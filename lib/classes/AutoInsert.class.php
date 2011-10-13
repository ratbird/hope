<?php
/*
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Nico Müller <nico.mueller@uni-oldenburg.de>
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @author      Jan Hendrik Willms <jan.hendrik.willms@uni-oldenburg.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       2.1
 */

require_once 'lib/functions.php';

/**
 * AutoInsert.class.php
 * Provides functions required by StEP00216:
 * - Assign seminars for automatic registration of certain user types
 * - Maintenance of registration rules
 *
 * Example of use:
 * @code
 *
 *   # show all auto insert seminars
 *   $auto_sems = AutoInsert::getAllSeminars();
 *
 *   # Save a new auto insert seminar with the user status
 *   AutoInsert::saveSeminar($sem_id, $rechte);
 *
 * @endcode
 */
class AutoInsert
{
    /**
     * Check if at least one seminar is used by the autoinsert functions
     * @return bool Indicating whether at least one seminar is used
     */
    public static function existSeminars()
    {
        $statement = DBManager::get()->query("SELECT COUNT(*) FROM auto_insert_sem");
        $result = $statement->fetchColumn();

        return $result > 0;
    }

    /**
     * Tests whether a user is already enregistered for a seminar
     * @param  string $user_id    Id of the user
     * @param  string $seminar_id Id of the seminar
     * @return bool   Indicating whether the user is enregistered
     */
    private static function checkUser($user_id, $seminar_id)
    {
        $statement = DBManager::get()->prepare("SELECT COUNT(*) FROM seminar_user WHERE user_id = ? AND seminar_id = ?");
        $statement->execute(array($user_id, $seminar_id));
        $result = $statement->fetchColumn();

        return $result > 0;
    }

    /**
     * Returns the status of a user in a certain seminar
     * @param  string $user_id    Id of the user
     * @param  string $seminar_id Id of the seminar
     * @return mixed  The user's status as a string or false if no record exists
     */
    private static function checkUserStatus($user_id,$seminar_id)
    {
        $statement = DBManager::get()->prepare("SELECT status FROM seminar_user WHERE user_id = ? AND seminar_id = ?");
        $statement->execute(array($user_id, $seminar_id));
        $status = $statement->fetchColumn();

        return $status;
    }

    /**
     * Updates a user's status in a certain seminar
     * @param string $user_id    Id of the user
     * @param string $seminar_id Id of the seminar
     * @param string $status     New status for the user in the seminar
     */
    private static function updateUserStatus($user_id, $seminar_id, $status = 'autor')
    {
        $query = "UPDATE seminar_user SET status = ? WHERE Seminar_id = ? AND user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($status, $seminar_id, $user_id));
    }

    /**
     * Enregisters a user in a certain seminar by checking his status and the
     * seminar's permissions
     * @param  string $user_status Current user status
     * @param  string $user_id     Id of the user
     * @param  Array  $seminar     Array representation of the seminar
     * @return boole  Indicating whether the user was actually inserted
     */
    private static function saveUser($user_status, $user_id, $seminar)
    {
        if (!in_array($user_status, $seminar['status']) or $seminar['Schreibzugriff'] >= 2)
            return false;

        // insert the user in the seminar_user table
        $query  = "INSERT IGNORE INTO seminar_user (Seminar_id, user_id, status, gruppe, mkdate)";
        $query .= " VALUES (?, ?, 'autor', ?, UNIX_TIMESTAMP())";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $seminar['seminar_id'],
            $user_id,
            select_group($seminar['start_time'])
        ));
        return true;
    }

    /**
     * Tests if a seminar already has an autoinsert record
     * @param  string $seminar_id Id of the seminar
     * @return bool   Indicating whether the seminar already has an autoinsert record
     */
    public static function checkSeminar($seminar_id)
    {
        $query = "SELECT 1 FROM auto_insert_sem WHERE seminar_id = ? LIMIT 1";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($seminar_id));
        $result = $statement->fetchColumn();

        return (bool)$result;
    }

    /**
     * Enables a seminar for autoinsertion of users with the given status(ses)
     * @param string $seminar_id Id of the seminar
     * @param mixed  $status     Either a single string or an array of strings
     *                           containing the status(ses) to enable for
     *                           autoinsertion
     */
    public static function saveSeminar($seminar_id, $status)
    {
        $query = "INSERT INTO auto_insert_sem (seminar_id, status) VALUES (?, ?)";
        $statement = DBManager::get()->prepare($query);

        foreach ((array)$status as $s)
        {
            $statement->execute(array($seminar_id, $s));
        }
    }

    /**
     * Updates an autoinsert record for a given seminar, dependent on the
     * parameter $remove it either inserts or removes the record for the given
     * parameters
     *
     * @param string $seminar_id Id of the seminar
     * @param string $status     Status for autoinsertion
     * @param bool   $remove     Whether the record should be added or removed
     */
    public static function updateSeminar($seminar_id, $status, $remove = false)
    {
        $query = $remove
            ? "DELETE FROM auto_insert_sem WHERE seminar_id = ? AND status= ?"
            : "INSERT IGNORE INTO auto_insert_sem (seminar_id, status) VALUES (?, ?)";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($seminar_id, $status));
    }

    /**
     * Removes a seminar from the autoinsertion process.
     * @param string $seminar_id Id of the seminar
     */
    public static function deleteSeminar($seminar_id)
    {
        $query = "DELETE FROM auto_insert_sem WHERE seminar_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($seminar_id));
    }

    /**
     * Returns a list of all seminars enabled for autoinsertion
     * @param  bool  Indicates whether only the seminar ids (true) or the full
     *               dataset shall be returned (false)
     * @return array The list of all enabled seminars (format according to $only_sem_id)
     */
    public static function getAllSeminars($only_sem_id = false)
    {

        if ($only_sem_id) {
            $query  = "SELECT DISTINCT seminar_id FROM auto_insert_sem";
            $statement = DBManager::get()->query($query);
            $results = $statement->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $query  = "SELECT a.seminar_id, GROUP_CONCAT(a.status) AS status, s.Name, s.Schreibzugriff, s.start_time ";
            $query .= "FROM auto_insert_sem a ";
            $query .= "JOIN seminare AS s USING (Seminar_id) ";
            $query .= "GROUP BY s.seminar_id ";
            $query .= "ORDER BY s.Name";
            $statement = DBManager::get()->query($query);
            $results = $statement->fetchAll(PDO::FETCH_ASSOC);
            foreach ($results as $index=>$result ) {
                $results[$index]['status'] = explode(',', $result['status']);
            }
        }
        return $results;
    }

    /**
     * Returns a seminar's info for autoinsertion
     * @param  string $seminar_id Id of the seminar
     * @return array  The seminar's data as an associative array
     */
    public static function getSeminar($seminar_id)
    {
        $query  = "SELECT a.seminar_id, GROUP_CONCAT(a.status) AS status, s.Name ";
        $query .= "FROM auto_insert_sem a ";
        $query .= "JOIN seminare AS s USING (Seminar_id) ";
        $query .= "WHERE a.seminar_id = ? ";
        $query .= "GROUP BY s.seminar_id";

        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($seminar_id));

        $result = $statement->fetch(PDO::FETCH_ASSOC);
        $result['status'] = explode(',', $result['status']);
        return $result;
    }

    /**
     * Store the user's automatic registration in a seminar redundantly to
     * avoid an annoying reregistration although the user explicitely left the
     * according seminar
     * @param string $user_id    Id of the user
     * @param string $seminar_id Id of the seminar
     */
    public static function saveAutoInsertUser($seminar_id, $user_id)
    {
        $query = "INSERT INTO auto_insert_user (Seminar_id, user_id, mkdate)
                  SELECT ?, user_id, UNIX_TIMESTAMP() FROM auth_user_md5 WHERE
                  user_id=? AND perms NOT IN('root','admin')";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($seminar_id, $user_id));
        return $statement->rowCount();
    }

    /**
     * Tests whether a user was already automatically registered for a certain
     * seminar.
     * @param  string $seminar_id Id of the seminar
     * @param  string $user_id    If of the user
     * @return bool   Indicates whether the user was already registered
     */
    public static function checkAutoInsertUser($seminar_id, $user_id)
    {
        $query = "SELECT 1 FROM auto_insert_user WHERE seminar_id = ? AND user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($seminar_id, $user_id));
        $result = $statement->fetchColumn();

        return $result > 0;
    }

    /**
     * Checks whether a user is already automatically registered for all
     * seminars according to his status.
     * PLEASE NOTE: The user <strong>will be registered</strong> in all seminars
     * he's not yet registered in.
     *
     * @param  string $status  Current user status
     * @param  string $user_id Id of the user
     * @return array  List of all seminar names the user was registered in
     */
    public static function checkNewUser($status, $user_id)
    {
        $seminars = self::getAllSeminars();
        $added    = array();

        foreach ($seminars as $seminar)
        {
            if (!self::checkUser($user_id, $seminar['seminar_id'])) {
                if (self::saveUser($status, $user_id, $seminar)) {
                   $added[] = $seminar['Name'];
                }
            }
        }

        return $added;
    }

    /**
     * Checks whether a user is already automatically registered for all
     * seminars according to his _changed_ status.
     * PLEASE NOTE: The user <strong>will be registered</strong> in all seminars
     * he's not yet registered in.
     *
     * @param  string $old_status Old user status
     * @param  string $new_status Current user status
     * @param  string $user_id    Id of the user
     * @return array  List of all seminar names the user was registered in
     */
    public static function checkOldUser($old_status, $new_status, $user_id)
    {
        if ($old_status == $new_status) {
            return array();
        }

        $seminars = self::getAllSeminars();
        $added    = array();

        foreach ($seminars as $seminar)
        {
            if (!self::checkUser($user_id, $seminar['seminar_id'])) {
                if (self::saveUser($new_status, $user_id, $seminar)) {
                    $added[] = $seminar['Name'];
                }
            } elseif (self::checkUserStatus($user_id, $seminar['seminar_id']) == 'user') {
                self::updateUserStatus($user_id, $seminar['seminar_id']);
                $added[] = $seminar['Name'];
            }
        }

        return $added;
    }
}

