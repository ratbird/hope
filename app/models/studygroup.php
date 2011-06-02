<?php
# Lifter010: TODO
/*
 * studygroup.php - Contains the StudygroupModel class
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author     André Klaßen <andre.klassen@elan-ev.de>
 * @copyright  2009 ELAN e.V.
 * @license    http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category   Stud.IP
 *
 */

require_once 'lib/messaging.inc.php';

class StudygroupModel
{
    /**
     * retrieves all installed plugins
     *
     * @return array modules a set of all plugins
     */
    function getInstalledPlugins()
    {
        $modules = array();

        // get standard-plugins (suitable for seminars)
        $plugin_manager = PluginManager::getInstance();
        $plugins = $plugin_manager->getPlugins('StandardPlugin');     // get all globally enabled plugins
        foreach ($plugins as $plugin) {
            $modules[get_class($plugin)] = $plugin->getPluginName();
        }
        return $modules;
    }

    /**
     * retrieves all modules
     *
     * @return array modules
     */
    function getInstalledModules()
    {
        $modules = array();

        // get core modules
        $admin_modules = new AdminModules();

        foreach ($admin_modules->registered_modules as $key => $data) {
            if ($admin_modules->isEnableable($key, '', 'sem')) $modules[$key] = $data['name'];
        }

        return $modules;
    }

    /**
     * gets the availability for a set of given modules
     *
     * @param array modules
     *
     * @return array enabled modules
     */
    function getAvailability($modules)
    {
        $enabled = array();

        // get current activation-settings
        $data = Config::Get()->STUDYGROUP_SETTINGS;
        $data2 = explode(" ",$data);

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

    /**
     * gets all available modules
     *
     * @return array avialable modules
     */
    function getAvailableModules()
    {
        $modules = StudygroupModel::getInstalledModules();
        $enabled = StudygroupModel::getAvailability($modules);

        $ret = array();

        foreach ($enabled as $key => $avail) {
            if ($avail && $modules[$key]) $ret[$key] = $modules[$key];
        }

        return $ret;
    }

    /**
     * gets all available plugins
     *
     * @return array avialable plugins
     */
    function getAvailablePlugins()
    {
        $modules = StudygroupModel::getInstalledPlugins();
        $enabled = StudygroupModel::getAvailability($modules);

        $ret = array();

        foreach ($enabled as $key => $avail) {
            if ($avail && $modules[$key]) $ret[$key] = $modules[$key];
        }

        return $ret;
    }

    /**
     * gets enabled plugins for a given studygroup
     *
     * @param string id of a studygroup
     *
     * @return array enabled plugins
     */
    function getEnabledPlugins($id)
    {
        $enabled = array();

        $plugin_manager = PluginManager::getInstance();
        $plugins = $plugin_manager->getPlugins('StandardPlugin');     // get all globally enabled plugins
        foreach ($plugins as $plugin) {
            $enabled[get_class($plugin)] = $plugin->isActivated($id);
        }
        return $enabled;
    }

    /**
     * retrieves all institues suitbable for an admin wrt global studygroup settings
     *
     * @return array institutes
     */
    function getInstitutes()
    {
        $institues = array();

        // get faculties
        $stmt = DBManager::get()->query("SELECT Name, Institut_id, 1 AS is_fak,'admin' AS inst_perms "
              . "FROM Institute WHERE Institut_id = fakultaets_id ORDER BY Name");
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $institutes[$data['Institut_id']] = array (
                    'name' => $data['Name'],
                    'childs' => array()
                    );
            // institutes for faculties
            $stmt2 = DBManager::get()->query("SELECT a.Institut_id, a.Name FROM Institute a "
                   . "WHERE fakultaets_id='". $data['Institut_id']
                   . "' AND a.Institut_id !='". $data['Institut_id'] . "' ORDER BY Name");

            while ($data2 = $stmt2->fetch(PDO::FETCH_ASSOC)) {
                $institutes[$data['Institut_id']]['childs'][$data2['Institut_id']] = $data2['Name'];
            }
        }

        return $institutes;
    }

    /**
     * allows an user to access a "closed" studygroup
     *
     * @param string username
     * @param string id of a studygroup
     *
     * @return void
     */
    function accept_user($username, $sem_id)
    {
        $stmt = DBManager::get()->query("SELECT asu.user_id FROM admission_seminar_user asu "
              . "LEFT JOIN auth_user_md5 au ON (au.user_id=asu.user_id) "
              . "WHERE au.username='$username' AND asu.seminar_id='". $sem_id ."'");
        if ($data = $stmt->fetch()) {
            $accept_user_id = $data['user_id'];

            DBManager::get()->query("INSERT INTO seminar_user SET user_id='".$accept_user_id."', seminar_id='".$sem_id."',
                    status='autor', position=0, gruppe=8, admission_studiengang_id=0, notification=0, mkdate=NOW(), comment='', visible='yes'");

            DBManager::get()->query("DELETE FROM admission_seminar_user WHERE user_id='".$accept_user_id."' AND seminar_id='".$sem_id."'");
        }
    }

    /**
     * denies access to a "closed" studygroup for an user
     *
     * @param string username
     * @param string id of a studygroup
     *
     * @return void
     */
    function deny_user($username, $sem_id)
    {
        DBManager::get()->query("DELETE FROM admission_seminar_user WHERE user_id='". get_userid($username) ."' AND seminar_id='".$sem_id."'");
    }

    /**
     * promotes an user in a studygroup wrt to a given perm
     *
     * @param string username
     * @param string id of a studygroup
     * @param string perm
     *
     * @return void
     */
    function promote_user($username, $sem_id, $perm)
    {
        DBManager::get()->query( "UPDATE seminar_user SET status = '$perm' WHERE Seminar_id = '$sem_id' AND user_id = '". get_userid($username) ."'");
    }

    /**
     * removes a user of a studygroup
     *
     * @param string username
     * @param string id of a studygroup
     *
     * @return void
     */
    function remove_user($username, $sem_id)
    {
        DBManager::get()->query("DELETE FROM seminar_user WHERE Seminar_id = '$sem_id' AND user_id = '" . get_userid($username) . "'");
    }

    /**
     * retrieves the count of all studygroups
     *
     *  param string search a filter term
     *
     * @return string count
     */
    function countGroups($search = null)
    {
        $status = studygroup_sem_types();

        $sql = "SELECT COUNT(*) as c FROM seminare WHERE status IN ('" . implode("','", $status) . "')";

        if (isset($search)) {
            $search = DBManager::get()->quote('%' . $search . '%');
            $sql .= " AND seminare.Name LIKE {$search}";
        }

        return DBManager::get()->query($sql)->fetchColumn();
    }

    /**
     * get all studygroups in a paged manner wrt a stort criteria and a search term
     *
     * @param string sort criteria
     * @param int lower bound of the resultset
     * @param int elements per page, if null get the global configuration value
     * @param string search term
     *
     * @return array studygroups
     */
    function getAllGroups($sort = '', $lower_bound = 1, $elements_per_page = NULL, $search = null)
    {
        if (is_null($elements_per_page)) {
            $elements_per_page = get_config('ENTRIES_PER_PAGE');
        }

        $status = studygroup_sem_types();
        $sql = "SELECT * FROM seminare WHERE status IN('" . implode("','", $status) . "')";
        if (isset($search)) {
            $search = DBManager::get()->quote('%' . $search . '%');
            $sql .= " AND seminare.Name LIKE {$search}";
        }
        $sort_order = (substr($sort, strlen($sort) - 3, 3) == 'asc') ? 'asc' : 'desc';

        // add here the sortings
        if ($sort == 'name_asc') {
            $sql .= " ORDER BY Name ASC";
        }
        elseif ($sort == 'name_desc') {
            $sql .= " ORDER BY Name DESC";
        }
        elseif ($sort == 'founded_asc') {
            $sql .= " ORDER BY mkdate ASC";
        }
        elseif ($sort == 'founded_desc') {
            $sql .= " ORDER BY mkdate DESC";
        }
        elseif ($sort == 'member_asc' || $sort == 'member_desc') {
            $sql = "SELECT s.*, (SELECT COUNT(*) FROM seminar_user as su "
                 . "WHERE s.Seminar_id = su.Seminar_id) as countsems "
                 . "FROM seminare as s "
                 . "WHERE s.status IN ('". implode("','", $status)."') ";
            if(!empty($search)) $sql.= "AND s.Name LIKE {$search} ";
            $sql .= "ORDER BY countsems $sort_order";
        }
        elseif ($sort == 'founder_asc' || $sort == 'founder_desc') {
            $sql = "SELECT s.* FROM seminare as s "
                 . "LEFT JOIN seminar_user as su ON s.Seminar_id = su.Seminar_id AND su.status = 'dozent' "
                 . "LEFT JOIN auth_user_md5 as aum ON su.user_id = aum.user_id "
                 . "WHERE s.status IN ('". implode("','", $status)."') ";
            if(!empty($search)) $sql.= "AND s.Name LIKE {$search} ";
            $sql     .= "ORDER BY aum.Nachname ". $sort_order;
        }
        elseif ($sort == 'ismember_asc' || $sort == 'ismember_desc') {
            $sql = "SELECT s.*, "
                 . "( SELECT su.user_id FROM seminar_user AS su WHERE su.user_id = '".$GLOBALS['user']->id."' AND su.Seminar_id = s.Seminar_id ) "
                 . "AS ismember FROM seminare AS s  "
                 . "WHERE s.status IN ('". implode("','", $status)."') ";
           if(!empty($search)) $sql.= "AND s.Name LIKE {$search} ";
           $sql  .= "ORDER BY `ismember`". $sort_order;

        }
        elseif ($sort == 'access_asc') {
            $sql .= " ORDER BY admission_prelim ASC";
        }
        elseif ($sort == 'access_desc') {
            $sql .= " ORDER BY admission_prelim DESC";
        }

        $sql .= ', name ASC LIMIT '. $lower_bound .','. $elements_per_page;

        $stmt = DBManager::get()->query($sql);
        $groups = $stmt->fetchAll();

        return $groups;
    }

    /**
     * returns the count of members for a given studygroup
     *
     * @param string id of a studygroup
     *
     * @return int count
     */
    function countMembers($semid)
    {
        $sql = "SELECT COUNT(user_id) FROM `seminar_user` WHERE Seminar_id = '{$semid}'";

        $stmt = DBManager::get()->query($sql);
        $count= $stmt->fetch();

        return intval($count[0]);
    }

    /**
     * get founder for a given studgroup
     *
     * @param string id of a studygroup
     *
     * @return array founder
     *
     */
    function getFounder($semid)
    {
        $sql  = "SELECT user_id FROM `seminar_user` WHERE Seminar_id = '{$semid}' AND status = 'dozent'";
        $stmt = DBManager::get()->query($sql);
        while ($user = $stmt->fetch()) {
            $founder[] = array('user_id' => $user['user_id'], 'fullname' => get_fullname($user['user_id']), 'uname' => get_username($user['user_id']));
        }

        return $founder;
    }

    /**
     * checks whether a user is a member of a studygroup
     *
     * @param string id of a user
     * @param string id of a studygroup
     *
     * @return boolean membership
     */
    function isMember($userid, $semid)
    {
        $sql  = "SELECT * FROM `seminar_user` WHERE Seminar_id = '{$semid}' AND user_id = '{$userid}'";

        $stmt = DBManager::get()->query($sql);
        $res  = $stmt->fetch();

        return is_array($res);
    }

    /**
     * adds a founder to a given studygroup
     *
     * @param string username
     * @param string id of a studygroup
     *
     * @return void
     */
    function addFounder($username, $sem_id)
    {
        $stmt = DBManager::get()->prepare("INSERT IGNORE INTO seminar_user "
              . "(Seminar_id, user_id, status) VALUES (?, ?, 'dozent')");
        $stmt->execute(array($sem_id, get_userid($username)));
    }

    /**
     * removes a founder from a given studygroup
     *
     * @param string username
     * @param string id of a studygroup
     *
     * @return void
     */
    function removeFounder($username, $sem_id)
    {
        $stmt = DBManager::get()->prepare("DELETE FROM seminar_user "
              . "WHERE Seminar_id = ? AND user_id = ?");
        $stmt->execute(array($sem_id, get_userid($username)));
    }

    /**
     * get founders of a given studygroup
     *
     * @param string id of a studygroup
     *
     * @return array founders
     */
    function getFounders($sem_id)
    {
        $query = "SELECT username, perms, ". $GLOBALS['_fullname_sql']['full_rev'] ." as fullname FROM seminar_user "
               . "LEFT JOIN auth_user_md5 USING (user_id) "
               . "LEFT JOIN user_info USING (user_id) "
               . "WHERE Seminar_id = ? AND status = 'dozent'";

        $stmt = DBManager::get()->prepare($query);
        $stmt->execute(array($sem_id));

        return $stmt->fetchAll();
    }

    /**
     * retrieves all members of a given studygroup in a paged manner
     *
     * @param string id of a studygroup
     * @param int lower bound of the resultset
     * @param int elements per page, if null get the global configuration value
     *
     * @return array members
     */
    function getMembers($sem_id, $lower_bound = 1, $elements_per_page = NULL)
    {
        if (is_null($elements_per_page)) {
            $elements_per_page = get_config('ENTRIES_PER_PAGE');
        }

        $query = "SELECT username,user_id ,perms, seminar_user.status, ". $GLOBALS['_fullname_sql']['full_rev']
               . " as fullname, seminar_user.mkdate FROM seminar_user "
               . "LEFT JOIN auth_user_md5 USING (user_id) "
               . "LEFT JOIN user_info USING (user_id) "
               . "WHERE Seminar_id = ? "
               . "ORDER BY seminar_user.mkdate ASC, seminar_user.status ASC  LIMIT ". $lower_bound .",". $elements_per_page;

        $stmt = DBManager::get()->prepare($query);
        $stmt->execute( array($sem_id) );

        return $stmt->fetchAll();
    }

    /**
     * callback function - used to compare sequences of studygroup statuses
     *
     * @param array status a
     * @param array status b
     *
     * return int ordering
     */
    function compare_status($a, $b)
    {
        if ($a['status'] == $b['status']) return strnatcmp($a['fullname'], $b['fullname']);
        elseif ($a['status'] == 'dozent') {
            if ($b['status'] == 'tutor') return -1;
            elseif ($b['status'] == 'autor') return -1;
        }
        elseif ($a['status'] == 'tutor') {
            if ($b['status'] == 'dozent') return +1;
            elseif ($b['status'] == 'autor') return -1;
        }
        elseif ($a['status'] == 'autor') {
            if ($b['status'] == 'tutor') return +1;
            elseif ($b['status'] == 'dozent') return +1;
        }
    }

    /**
     * Checks for a given seminar_id whether a course is a studygroup
     *
     * @param   string id of a seminar
     *
     * @return  array studygroup
     */
    function isStudygroup($sem_id)
    {
        $stmt = DBManager::get()->query("SELECT * FROM seminare WHERE Seminar_id = '$sem_id' AND status IN ('". implode("','", studygroup_sem_types())."')");
        return $stmt->fetch();
    }

    /**
     * If a new user applies, an application note to all moderators and founders
     * of a studygroup will be automatically sent while calling this function.
     * The note contains the user's name and a direct link to the member page of the studygroup.
     *
     * @param string    $sem_id    id of a seminar / studygroup
     * @param strimg    $user_id   id of the applicant
     *
     * @return int                 number of recipients
     */
    function applicationNotice($sem_id, $user_id)
    {
        $sem        = new Seminar($sem_id);
        $dozenten   = $sem->getMembers();
        $tutors     = $sem->getMembers('tutor');
        $recipients = array();
        $msging     = new Messaging();

        foreach(array_merge($dozenten, $tutors) as $uid => $user) {
            $recipients[] = $user['username'];
        }

        if (studip_strlen($sem->getName()) > 32) //cut subject if to long
            $subject = sprintf(_("[Studiengruppe: %s...]"),studip_substr($sem->getName(), 0, 30));
        else
            $subject = sprintf(_("[Studiengruppe: %s]"),$sem->getName());

        $subject .= " " ._("Neuer Mitgliedsantrag");
        $message  = sprintf(_("%s möchte der Studiengruppe %s beitreten. Klicken Sie auf den untenstehenden Link, um direkt zur Studiengruppe zu gelangen.\n\n [Direkt zur Studiengruppe]%s"),
                get_fullname($user_id) ,$sem->getName(),URLHelper::getlink($GLOBALS['ABSOLUTE_URI_STUDIP']."dispatch.php/course/studygroup/members/" . $sem->id, array('cid' => $sem->id)));

        return $msging->insert_message(addslashes($message), $recipients,"____%system%____", '', '', '', '', addslashes($subject));

    }
}