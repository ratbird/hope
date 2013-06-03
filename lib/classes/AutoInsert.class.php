<?php
/*
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Nico M�ller <nico.mueller@uni-oldenburg.de>
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
class AutoInsert {

    private $settings = array();

    public function instance() {
        return new AutoInsert();
    }

    public function __construct() {
        $this->loadSettings();
    }

    private function loadSettings() {
        $query = "SELECT a.seminar_id, GROUP_CONCAT(a.status,IF(LENGTH(a.domain_id)=0,':keine',CONCAT(':',a.domain_id))) AS domain_status, s.Name, s.Schreibzugriff, s.start_time ";
        $query .= "FROM auto_insert_sem a ";
        $query .= "JOIN seminare AS s USING (Seminar_id) ";
        $query .= "GROUP BY s.seminar_id ";
        $query .= "ORDER BY s.Name";
        $statement = DBManager::get()->query($query);
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as $result) {
            if ($result['Schreibzugriff'] < 3)
{
                $domains = explode(',', $result['domain_status']);

                foreach ($domains as $domain) {
                    $array = explode(':', $domain);
                    $key = $array[1] . '.' . $array[0];
                    $this->settings[$key][$result['seminar_id']] = array(
                        'Seminar_id' => $result['seminar_id'],
                        'name' => $result['Name'],
                        'Schreibzugriff' => $result['Schreibzugriff'],
                        'start_time' => $result['start_time']
                    );
                }
            }
        }
    }


    private function getUserSeminars($user_id,$seminare) {
        $statement = DBManager::get()->prepare("SELECT Seminar_id,s.name,s.Schreibzugriff,s.start_time FROM seminar_user su INNER JOIN seminare s USING(Seminar_id) WHERE user_id = ? AND Seminar_id IN('".implode("','",$seminare)."')");
        $statement->execute(array($user_id));
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Tr�gt den Benutzer in den Eingestellten veranstaltungen automatisch ein.
     * @param type $user_id 
     * @param type $status Wenn Status nicht angegeben wird, wird der Status des Users aus user_id genommen
     * @return array 'added' Namen der Seminare in die der User eingetragen wurde
     *         array 'removed' Namen der Seminare aus denen der User ausgetragen wurde
     */
    public function saveUser($user_id, $status = FALSE) {



        $domains = array();
        if (!$status)
        {
            $status = get_global_perm($user_id);
        }
        foreach (UserDomain::getUserDomainsForUser($user_id) as $d) {
            $domains [] = $d->getID(); //Domains des Users
        }


        if (count($domains) == 0)
        {
            $domains [] = 'keine';
        }
        $settings = array();
        $all_seminare = array();
        foreach ($domains as $domain) {

            $key = $domain . '.' . $status;
            if (is_array($this->settings[$key]))
            {
                $id = key($this->settings[$key]);
                foreach ($this->settings[$key] as $id => $value) {
                    $settings[$id] = $value;
                }
            }
            foreach($this->settings as $key){
                foreach($key as $id => $sem){
                    $all_seminare[$id] = $sem;
                }
            }
        }
        
        $seminare = array();
        foreach ($this->getUserSeminars($user_id,array_keys($all_seminare)) as $sem) {
            $seminare[$sem['Seminar_id']] = $sem;
        }
        $toAdd = array_diff_key($settings, $seminare);
        $toRemove = array_diff_key($all_seminare, $toAdd, $settings);
        $added = array();
        $removed = array();
     
        foreach ($toAdd as $id => $seminar) {
            if ($this->addUser($user_id, $seminar))
                $added[] = $seminar['name'];
        }
        foreach ($toRemove as $id => $seminar) {
            if ($this->removeUser($user_id, $seminar))
                $removed[] = $seminar['name'];
        }
         
        return array('added' => $added, 'removed' => $removed);
    }
  
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
    private function checkUser($user_id, $seminar_id) {

        $statement = DBManager::get()->prepare("SELECT COUNT(*) FROM seminar_user WHERE user_id = ? AND seminar_id = ?");
        $statement->execute(array($user_id, $seminar_id));
        $result = $statement->fetchColumn();

        return $result > 0;
    }

    private function addUser($user_id, $seminar) {
    
            $query = "INSERT IGNORE INTO seminar_user (Seminar_id, user_id, status, gruppe, mkdate)";
            $query .= " VALUES (?, ?, 'autor', ?, UNIX_TIMESTAMP())";
            $statement = DBManager::get()->prepare($query);
             $statement->execute(array(
                $seminar['Seminar_id'],
                $user_id,
                select_group($seminar['start_time'])
            ));
             $rows = $statement->rowCount();
             if($rows > 0) return true;
         
            return false;
         
    }

    private function removeUser($user_id, $seminar) {
    
            $query = "DELETE FROM seminar_user "
                    . "WHERE user_id = ? "
                    . "AND Seminar_id = ? ";

            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                $user_id,
                $seminar['Seminar_id']
            ));
                   $rows = $statement->rowCount();
                   
            $query = "DELETE FROM statusgruppe_user "
            ."WHERE user_id = ? "
            ."AND statusgruppe_id IN (SELECT statusgruppe_id FROM statusgruppen WHERE range_id = ?)";
                $statusgruppe_stmt = DBManager::get()->prepare($query);
            $statusgruppe_stmt->execute(array(
                $user_id,
                $seminar['Seminar_id']
            ));
                   $statusgruppe_rows = $statusgruppe_stmt->rowCount();
             if($rows > 0 || $statusgruppe_rows > 0) return true;
         
            return false;
    }
    /**
     * 
     * @param type $user_id
     */
    public function deleteUserSeminare($user_id){
          $db = DBManager::get();
 

            $db->exec("DELETE FROM seminar_user " .
                   "WHERE user_id = ".$db->quote($user_id));
          

  
    }

    /**
     * Returns the status of a user in a certain seminar
     * @param  string $user_id    Id of the user
     * @param  string $seminar_id Id of the seminar
     * @return mixed  The user's status as a string or false if no record exists
     */
    private static function checkUserStatus($user_id, $seminar_id) {
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
    private static function updateUserStatus($user_id, $seminar_id, $status = 'autor') {
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
    private static function _saveUser($user_status, $user_id, $seminar) {
        if (!in_array($user_status, $seminar['status']) or $seminar['Schreibzugriff'] >= 2)
            return false;

        // insert the user in the seminar_user table
        $query = "INSERT IGNORE INTO seminar_user (Seminar_id, user_id, status, gruppe, mkdate)";
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
    public static function checkSeminar($seminar_id, $domain_id = FALSE) {

        if (!$domain_id)
    {
            $query = "SELECT 1 FROM auto_insert_sem WHERE seminar_id = ?  LIMIT 1";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($seminar_id));
        }
        else
        {
            $query = "SELECT 1 FROM auto_insert_sem WHERE seminar_id = ? AND domain_id = ? LIMIT 1";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($seminar_id, $domain_id));
        }


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
    public static function saveSeminar($seminar_id, $status, $domain_id) {
        $query = "INSERT INTO auto_insert_sem (seminar_id, status,domain_id) VALUES (?, ?,?)";
        $statement = DBManager::get()->prepare($query);

        foreach ((array) $status as $s) {
            $statement->execute(array($seminar_id, $s, $domain_id));
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
    public static function updateSeminar($seminar_id, $domain = '', $status, $remove = false) {

        $query = $remove ? "DELETE FROM auto_insert_sem WHERE seminar_id = ? AND status= ? AND domain_id = ?" : "INSERT IGNORE INTO auto_insert_sem (seminar_id, status,domain_id) VALUES (?, ?, ?)";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($seminar_id, $status, $domain));
    }

    /**
     * Removes a seminar from the autoinsertion process.
     * @param string $seminar_id Id of the seminar
     */
    public static function deleteSeminar($seminar_id) {
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
    public static function getAllSeminars($only_sem_id = false) {

        if ($only_sem_id)
    {
            $query = "SELECT DISTINCT seminar_id FROM auto_insert_sem";
            $statement = DBManager::get()->query($query);
            $results = $statement->fetchAll(PDO::FETCH_COLUMN);
        }
        else
        {
            $query = "SELECT a.seminar_id, GROUP_CONCAT(a.status,IF(LENGTH(a.domain_id)=0,':keine',CONCAT(':',a.domain_id))) AS domain_status, s.Name, s.Schreibzugriff, s.start_time ";
            $query .= "FROM auto_insert_sem a ";
            $query .= "JOIN seminare AS s USING (Seminar_id) ";

            $query .= "GROUP BY s.seminar_id ";
            $query .= "ORDER BY s.Name";
            $statement = DBManager::get()->query($query);
            $results = $statement->fetchAll(PDO::FETCH_ASSOC);
            foreach ($results as $index => $result) {
                $domains = explode(',', $result['domain_status']);
                foreach ($domains as $domain) {
                    $array = explode(':', $domain);
                    $results[$index]['status'][$array[1]][] = $array[0];
            }
        }
        }

        return $results;
    }

    /**
     * Returns a seminar's info for autoinsertion
     * @param  string $seminar_id Id of the seminar
     * @return array  The seminar's data as an associative array
     */
    public static function getSeminar($seminar_id) {
        $query = "SELECT a.seminar_id, GROUP_CONCAT(a.status) AS status, s.Name ";
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
    public static function saveAutoInsertUser($seminar_id, $user_id) {
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
    public static function checkAutoInsertUser($seminar_id, $user_id) {
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
    public static function checkNewUser($status, $user_id) {

        $seminars = self::getAllSeminars();
        $added = array();

        foreach ($seminars as $seminar) {

            if (!self::checkUser($user_id, $seminar['seminar_id']))
        {
                if (self::saveUser($status, $user_id, $seminar))
                {
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
    public static function checkOldUser($old_status, $new_status, $user_id) {
        if ($old_status == $new_status)
    {
            return array();
        }

        $seminars = self::getAllSeminars();
        $added = array();

        foreach ($seminars as $seminar) {
            if (!self::checkUser($user_id, $seminar['seminar_id']))
        {
                if (self::saveUser($new_status, $user_id, $seminar))
                {
                    $added[] = $seminar['Name'];
                }
            }
            elseif (self::checkUserStatus($user_id, $seminar['seminar_id']) == 'user')
            {
                self::updateUserStatus($user_id, $seminar['seminar_id']);
                $added[] = $seminar['Name'];
            }
        }

        return $added;
    }

}

