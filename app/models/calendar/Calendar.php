<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Calendar
{
    const CALENDAR_END = 0x7FFFFFFF;
    const PERMISSION_OWN = 16;
    const PERMISSION_ADMIN = 8;
    const PERMISSION_WRITABLE = 4;
    const PERMISSION_READABLE = 2;
    const PERMISSION_FORBIDDEN = 1;
    
    /*
     * TODO remove or replace by object types
     */
    const RANGE_USER = 1;
    const RANGE_GROUP = 2;
    const RANGE_SEM = 3;
    const RANGE_INST = 4;
 
    
    /**
     * Returns all ids of seminars the given user wants to include in his
     * calendar. If the parameter all is true, it returns all seminars
     * of the user.
     *
     * @param type $user_id the id of the user
     * @param type $all if true, all users seminars are included
     * @param type $names if true, the names of the seminars are included in the
     * returned array
     * @return mixed
     */
    public static function getBindSeminare($user_id, $all = NULL, $names = false)
    {
        $bind_seminare = array();

        $db = DBManager::get();
        if ($names) {
            $query = "SELECT su.Seminar_id, s.Name FROM seminar_user su LEFT JOIN seminare s USING(Seminar_id) WHERE user_id = ?";
        } else {
            $query = "SELECT Seminar_id FROM seminar_user WHERE user_id = ?";
        }
        if (is_null($all) || $all === false) {
            $query .= " AND bind_calendar = 1";
        }
        if ($names) {
            $query .= ' ORDER BY Name';
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($user_id));
            $result = $statement->fetchAll(PDO::FETCH_ASSOC);
            foreach ($result as $row) {
                $bind_seminare[$row['Seminar_id']] = $row['Name'];
            }
        } else {
            if (isset($GLOBALS['SessSemName'][1])) {
                if ($GLOBALS['perm']->have_studip_perm('user', $GLOBALS['SessSemName'][1])) {
                    array_push($bind_seminare, $GLOBALS['SessSemName'][1]);
                    return $bind_seminare;
                }
                return NULL;
            } else {
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($user_id));
                $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                foreach ($result as $row) {
                    $bind_seminare[] = $row['Seminar_id'];
                }
            }
        }
        if (count($bind_seminare)) {
            return $bind_seminare;
        }

        return NULL;
    }
    
    /**
     * 
     * @param type $user_id
     * @return type
     */
    public static function getUsers($user_id)
    {

        $stmt = DBManager::get()->prepare("SELECT DISTINCT aum.username, CONCAT(aum.Nachname,', ',aum.vorname) as fullname, aum.user_id FROM contact c LEFT JOIN auth_user_md5 aum ON(c.owner_id = aum.user_id) WHERE c.user_id = ? AND c.calpermission > 1 ORDER BY fullname");
        $stmt->execute(array($user_id));

        $users = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $user) {
            $users[] = array('name' => $user['fullname'], 'username' => $user['username'],
                'id' => $user['user_id']);
        }

        return $users;
    }
    
    /**
     * 
     * @param type $user_id
     * @return type
     */
    public static function getGroups($user_id)
    {

        $stmt = DBManager::get()->prepare("SELECT DISTINCT sg.statusgruppe_id, sg.name FROM statusgruppen sg LEFT JOIN statusgruppe_user su USING(statusgruppe_id) LEFT JOIN contact c ON(su.user_id = c.owner_id) WHERE sg.range_id = ? AND sg.calendar_group = 1 AND c.calpermission > 1 ORDER BY sg.name");
        $stmt->execute(array($user_id));

        $groups = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $group) {
            $groups[] = array('name' => $group['name'], 'id' => $group['statusgruppe_id']);
        }

        return $groups;
    }
    
    /**
     * TODO wird das noch benötigt?
     * 
     * @return type
     */
    public static function getAllContactGroups()
    {

        $query = "SELECT aum.user_id, aum.username,  s.statusgruppe_id, s.name ";
        $query .= "FROM statusgruppe_user su ";
        $query .= "LEFT JOIN statusgruppen s USING ( statusgruppe_id ) ";
        $query .= "LEFT JOIN auth_user_md5 aum ON ( range_id = aum2.user_id ) ";
        $query .= "WHERE su.user_id = ? AND s.range_id != aum.user_id ";
        $query .= "AND s.range_id = aum.user_id AND s.cal_enable = 1";

        $stmt = DBManager::get()->prepare($query);
        $stmt->execute(array($GLOBALS['user']->id));
        $contact_groups = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $contact_group) {
            $contact_groups[] = $contact_group;
        }

        return $contact_groups;
    }
    
    
    public static function GetInstituteActivatedCalendar($user_id)
    {
        $stmt = DBManager::get()->prepare("SELECT ui.Institut_id, Name, modules FROM user_inst ui LEFT JOIN Institute i USING(Institut_id)WHERE user_id = ? AND inst_perms IN ('admin','dozent','tutor','autor') ORDER BY Name ASC");
        $modules = new Modules();
        $stmt->execute(array($user_id));
        $active_calendar = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($modules->isBit($row['modules'], $modules->registered_modules['calendar']['id'])) {
                $active_calendar[$row['Institut_id']] = $row['Name'];
            }
        }
        return $active_calendar;
    }
    
    public static function GetSeminarActivatedCalendar($user_id)
    {
        $stmt = DBManager::get()->prepare("SELECT seminar_id, Name, modules FROM seminar_user LEFT JOIN seminare USING(seminar_id) WHERE user_id = ? ORDER BY Name ASC");
        $modules = new Modules();
        $stmt->execute(array($user_id));
        $active_calendar = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($modules->isBit($row['modules'], $modules->registered_modules['calendar']['id'])) {
                $active_calendar[$row['seminar_id']] = $row['Name'];
            }
        }
        return $active_calendar;
    }

    public static function GetLecturers()
    {
        $stmt = DBManager::get()->prepare("SELECT aum.username, CONCAT(aum.Nachname,', ',aum.vorname) as fullname, aum.user_id FROM auth_user_md5 aum WHERE perms = 'dozent' ORDER BY fullname");
        $stmt->execute();
        $lecturers = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($row['user_id'] != $GLOBALS['user']->id) {
                $lecturers[] = array('name' => $row['fullname'], 'username' => $row['username'], 'id' => $row['user_id']);
            }
        }
        return $lecturers;
    }
    
    public static function getDefaultUserSettings($index = NULL)
    {
        $default = array(
            'view' => 'week',
            'start' => '9',
            'end' => '20',
            'step_day' => '900',
            'step_week' => '1800',
            'type_week' => 'LONG',
            'step_week_group' => '3600',
            'step_day_group' => '3600'
        );
        return (is_null($index) ? $default : $default[$index]);
    }
}