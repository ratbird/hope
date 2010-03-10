<?php

/*
 * Copyright (C) 2009 - André Klaßen <aklassen@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


class StudygroupModel {
    function getInstalledPlugins() {
        $modules = array();

        // get standard-plugins (suitable for seminars)
        $plugin_manager = PluginManager::getInstance();
        $plugins = $plugin_manager->getPluginInfos('StandardPlugin');     // get all globally enabled plugins
        foreach ($plugins as $plugin) {
            if($plugin['enabled']){
                $modules[$plugin['class']] = $plugin['name'];
            }
        }
        return $modules;
    }

    function getInstalledModules() {
        $modules = array();

        // get core modules
        $admin_modules = new AdminModules();

        foreach ($admin_modules->registered_modules as $key => $data) {
            if ($admin_modules->isEnableable($key, '', 'sem')) $modules[$key] = $data['name'];
        }

        return $modules;
    }

    function getAvailability( $modules ) {
        $enabled = array();

        // get current activation-settings
        $data = Config::GetInstance()->getValue('STUDYGROUP_SETTINGS');
        $data2 = explode('|', $data);

        foreach ($data2 as $element) {
            list($key, $value) = explode(':', $element);
            $enabled[$key] = ($value) ? true : false;
        }

        if (!is_array($enabled)) {  // if not settings are there yet, set default
            foreach ($modules as $key => $name) {
                $enabled[$key] = false;
            }
        }

        return $enabled;
    }



    function getAvailableModules() {
        $modules = StudygroupModel::getInstalledModules();
        $enabled = StudygroupModel::getAvailability( $modules );

        $ret = array();

        foreach ($enabled as $key => $avail) {
            if ($avail && $modules[$key]) $ret[$key] = $modules[$key];
        }

        return $ret;
    }   

    function getAvailablePlugins() {
        $modules = StudygroupModel::getInstalledPlugins();
        $enabled = StudygroupModel::getAvailability( $modules );

        $ret = array();

        foreach ($enabled as $key => $avail) {
            if ($avail && $modules[$key]) $ret[$key] = $modules[$key];
        }

        return $ret;
    }   

    function getEnabledPlugins($id) {
        $enabled = array();

        $plugin_manager = PluginManager::getInstance();
        $plugins = $plugin_manager->getPluginInfos('StandardPlugin');     // get all globally enabled plugins
        foreach ($plugins as $plugin ) { 
            $enabled[$plugin['class']] = $plugin_manager->isPluginActivated($plugin['id'], $id);
        }
        return $enabled;
    }   

    function getInstitutes() {
        $institues = array();

        // get faculties
        $stmt = DBManager::get()->query("SELECT Name, Institut_id, 1 AS is_fak,'admin' AS inst_perms
                FROM Institute WHERE Institut_id = fakultaets_id ORDER BY Name");
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $institutes[$data['Institut_id']] = array (
                    'name' => $data['Name'],
                    'childs' => array()
                    );
            // institutes for faculties
            $stmt2 = DBManager::get()->query("SELECT a.Institut_id, a.Name FROM Institute a
                    WHERE fakultaets_id='". $data['Institut_id'] ."' 
                    AND a.Institut_id !='". $data['Institut_id'] . "' ORDER BY Name");
            while ($data2 = $stmt2->fetch(PDO::FETCH_ASSOC)) {
                $institutes[$data['Institut_id']]['childs'][$data2['Institut_id']] = $data2['Name'];
            }
        }   

        return $institutes;
    }

    function accept_user($username, $sem_id) {
        $stmt = DBManager::get()->query("SELECT asu.user_id FROM admission_seminar_user asu 
            LEFT JOIN auth_user_md5 au ON (au.user_id=asu.user_id) 
            WHERE au.username='$username' AND asu.seminar_id='". $sem_id ."'");
        if ($data = $stmt->fetch()) {
            $accept_user_id = $data['user_id'];

            DBManager::get()->query("INSERT INTO seminar_user SET user_id='".$accept_user_id."', seminar_id='".$sem_id."',
                status='autor', position=0, gruppe=8, admission_studiengang_id=0, notification=0, mkdate=NOW(), comment='', visible='yes'");

            DBManager::get()->query("DELETE FROM admission_seminar_user WHERE user_id='".$accept_user_id."' AND seminar_id='".$sem_id."'");
        }
    }

    function deny_user($username, $sem_id) {
        DBManager::get()->query("DELETE FROM admission_seminar_user WHERE user_id='". get_userid($username) ."' AND seminar_id='".$sem_id."'");
    }

    function promote_user($username, $sem_id, $perm) 
    {
        DBManager::get()->query( "UPDATE seminar_user SET status = '$perm' WHERE Seminar_id = '$sem_id' AND user_id = '". get_userid($username) ."'");
    }

    function remove_user($username, $sem_id, $perm) 
    {
        DBManager::get()->query("DELETE FROM seminar_user WHERE Seminar_id = '$sem_id' AND user_id = '". get_userid($username) ."'");
    }
    
        function countGroups() {
        $status = studygroup_sem_types();

                return DBManager::get()->query("SELECT COUNT(*) as c FROM seminare WHERE status IN ('". implode("','", $status)."')")->fetchColumn();
        }

    function getAllGroups($sort = '', $lower_bound = 1, $elements_per_page = 20)
    {
        $status = studygroup_sem_types();
        $sql = "SELECT * FROM seminare WHERE status IN('". implode("','", $status)."')";
        
        $sort_order = (substr($sort, strlen($sort) - 3, 3) == 'asc') ? 'asc' : 'desc';
                
        // add here the sortings
        if($sort == 'name_asc') {
            $sql .= " ORDER BY Name ASC";
        }
        else if($sort == 'name_desc') {
            $sql .= " ORDER BY Name DESC";
        }
        else if($sort == 'founded_asc') {
            $sql .= " ORDER BY mkdate ASC";
        }
        else if($sort == 'founded_desc') {
            $sql .= " ORDER BY mkdate DESC";
        }
        else if($sort == 'member_asc' || $sort == 'member_desc') {
                $sql = "SELECT s.*, (SELECT COUNT(*) FROM seminar_user as su 
                                WHERE s.Seminar_id = su.Seminar_id) as countsems
                        FROM seminare as s
                        WHERE s.status IN ('". implode("','", $status)."')
                        ORDER BY countsems $sort_order";
        }
        else if($sort == 'founder_asc' || $sort == 'founder_desc') {
                $sql = "SELECT s.* FROM seminare as s 
                        LEFT JOIN seminar_user as su ON s.Seminar_id = su.Seminar_id AND su.status = 'dozent' AND su.user_id <> MD5('studygroup_dozent') 
                        LEFT JOIN auth_user_md5 as aum ON su.user_id = aum.user_id 
                        WHERE s.status IN ('". implode("','", $status)."') 
                        ORDER BY aum.Nachname ". $sort_order;
        }
        else if($sort == 'ismember_asc' || $sort == 'ismember_desc') {
                $sql ="SELECT s.*, 
                        ( SELECT su.user_id FROM seminar_user AS su WHERE su.user_id = '".$GLOBALS['user']->id."' AND su.Seminar_id = s.Seminar_id ) 
                        AS ismember FROM seminare AS s  
                        WHERE s.status IN ('". implode("','", $status)."')    
                        ORDER BY `ismember`". $sort_order;
    
        }
        else if($sort == 'access_asc') {
                $sql .= " ORDER BY admission_prelim ASC";
        }
        else if($sort == 'access_desc') {
                 $sql .= " ORDER BY admission_prelim DESC";
        }

        $sql .= ', name ASC LIMIT '. $lower_bound .','. $elements_per_page;
    
        $stmt = DBManager::get()->query($sql);
        $groups = $stmt->fetchAll();
        
        return $groups;
    }
    
    function countMembers ( $semid )
    {
      $sql = "SELECT COUNT(user_id) FROM `seminar_user` WHERE Seminar_id = '{$semid}'";  
      
      $stmt = DBManager::get()->query($sql);
      $count= $stmt->fetch();
      
      // always return one member less than the total count since there is dummy_dozent in each group
      return $count[0]-1;
    }
    
    function getFounder ( $semid )
    {
        $sql  = "SELECT user_id FROM `seminar_user` WHERE Seminar_id = '{$semid}' AND status = 'dozent' AND user_id != MD5('studygroup_dozent')";
        $stmt = DBManager::get()->query($sql);
                while ($user = $stmt->fetch()) {
            $founder[] = array('user_id' => $user['user_id'], 'fullname' => get_fullname($user['user_id']), 'uname' => get_username($user['user_id']));
                }
       
        return $founder; 
    }
    
    
    function isMember ( $userid, $semid)
    {
        $sql = "SELECT * FROM `seminar_user` WHERE Seminar_id = '{$semid}' AND user_id = '{$userid}'";  

        $stmt = DBManager::get()->query($sql);
        $res= $stmt->fetch();
        
        return (is_array($res));
       
    }
    
    function addFounder ( $username, $sem_id ) {
        $stmt = DBManager::get()->prepare("INSERT IGNORE INTO seminar_user
            (Seminar_id, user_id, status) VALUES (?, ?, 'dozent')");
        $stmt->execute( array($sem_id, get_userid($username)) );
    }

    function removeFounder ( $username, $sem_id ) {
        $stmt = DBManager::get()->prepare("DELETE FROM seminar_user
            WHERE Seminar_id = ? AND user_id = ?");
        $stmt->execute( array($sem_id, get_userid($username)) );
    }

    function getFounders ( $sem_id ) {
        $stmt = DBManager::get()->prepare($query = "SELECT username, perms, ". $GLOBALS['_fullname_sql']['full_rev'] ." as fullname FROM seminar_user
            LEFT JOIN auth_user_md5 USING (user_id)
            LEFT JOIN user_info USING (user_id)
            WHERE Seminar_id = ? AND status = 'dozent'
                AND username != 'studygroup_dozent'");
        $stmt->execute( array($sem_id) );

        return $stmt->fetchAll();
    }
    
    function getMembers ( $sem_id, $lower_bound = 1, $elements_per_page = 20 ) {
        $stmt = DBManager::get()->prepare($query = "SELECT username,user_id ,perms, seminar_user.status, ". $GLOBALS['_fullname_sql']['full_rev'] ." as fullname FROM seminar_user
            LEFT JOIN auth_user_md5 USING (user_id)
            LEFT JOIN user_info USING (user_id)
            WHERE Seminar_id = ? AND username != 'studygroup_dozent'
            ORDER BY seminar_user.mkdate ASC, seminar_user.status ASC  LIMIT ". $lower_bound .",". $elements_per_page);

        $stmt->execute( array($sem_id) );

        return $stmt->fetchAll();
    }
    
    function compare_status($a, $b) { 
        if ($a['status'] == $b['status']) return strnatcmp($a['fullname'], $b['fullname']);
        elseif ($a['status'] == 'dozent'){
            if ($b['status'] == 'tutor') return -1;
            elseif ($b['status'] == 'autor') return -1;
        }
        elseif ($a['status'] == 'tutor'){
            if ($b['status'] == 'dozent') return +1;
            else if ($b['status'] == 'autor') return -1;
        }
        elseif ($a['status'] == 'autor'){
            if ($b['status'] == 'tutor') return +1;
            else if ($b['status'] == 'dozent') return +1;
        }
    } 
}
