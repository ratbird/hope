<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require_once 'lib/classes/SemesterData.class.php';

/**
 * singleton class for the admin search list
 *
 * @author Rasmus Fuhse <fuhse@data-quest.de>
 */
class AdminList {
    static protected $instance = null;

    protected $results = array();

    static public function getInstance() {
        if (!self::$instance) {
            include_once 'lib/admin_search.inc.php';
            self::$instance = new AdminList();
        }
        return self::$instance;
    }

    public function __construct() {
        $GLOBALS['view_mode'] = "sem";
        $this->search();
    }

    public function search() {
        global $links_admin_data, $perm, $user;
        $semester=new SemesterData;
        $db = DBManager::get();
        if (!$perm->have_perm("root")) {
            $my_inst = $db->query(
                "SELECT a.Institut_id,Name, IF(b.Institut_id=b.fakultaets_id,1,0) AS is_fak " .
                "FROM user_inst a " .
                    "LEFT JOIN Institute b USING (Institut_id) " .
                "WHERE a.user_id='$user->id' " .
                    "AND a.inst_perms='admin' " .
                "ORDER BY is_fak,Name" .
            "")->fetchAll(PDO::FETCH_COLUMN, 0);
        }

        $query="SELECT DISTINCT seminare.*, Institute.Name AS Institut,
                sd1.name AS startsem,IF(duration_time=-1, '"._("unbegrenzt")."', sd2.name) AS endsem
                FROM seminar_user LEFT JOIN seminare USING (seminar_id)
                LEFT JOIN Institute USING (institut_id)
                LEFT JOIN auth_user_md5 ON (seminar_user.user_id = auth_user_md5.user_id)
                LEFT JOIN semester_data sd1 ON ( start_time BETWEEN sd1.beginn AND sd1.ende)
                LEFT JOIN semester_data sd2 ON ((start_time + duration_time) BETWEEN sd2.beginn AND sd2.ende)
                WHERE seminar_user.status = 'dozent' AND seminare.status NOT IN('". implode("','", studygroup_sem_types())."') ";
        $conditions=0;

        if ($links_admin_data["srch_sem"]) {
            $one_semester = $semester->getSemesterData($links_admin_data["srch_sem"]);
            $query.="AND seminare.start_time <=".$one_semester["beginn"]." AND (".$one_semester["beginn"]." <= (seminare.start_time + seminare.duration_time) OR seminare.duration_time = -1) ";
            $conditions++;
        }

        if (is_array($my_inst) && !$perm->have_perm("root")) {
            $query.="AND Institute.Institut_id IN ('".join("','",$my_inst)."') ";
        }

        if ($links_admin_data["srch_inst"]) {
            $query.="AND Institute.Institut_id ='".$links_admin_data["srch_inst"]."' ";
        }

        if ($links_admin_data["srch_fak"]) {
            $query.="AND fakultaets_id ='".$links_admin_data["srch_fak"]."' ";
        }

        if ($links_admin_data["srch_doz"]) {
            $query.="AND seminar_user.user_id ='".$links_admin_data["srch_doz"]."' ";
        }

        if ($links_admin_data["srch_exp"]) {
            $query.="AND (seminare.Name LIKE '%".$links_admin_data["srch_exp"]."%' OR seminare.VeranstaltungsNummer LIKE '%".$links_admin_data["srch_exp"]."%' OR seminare.Untertitel LIKE '%".$links_admin_data["srch_exp"]."%' OR seminare.Beschreibung LIKE '%".$links_admin_data["srch_exp"]."%' OR auth_user_md5.Nachname LIKE '%".$links_admin_data["srch_exp"]."%') ";
            $conditions++;
        }

        $db = DBManager::get();
        $this->results = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSelectTemplate($course_id) {
        $adminList = $GLOBALS['template_factory']->open('admin/adminList.php');
        $adminList->set_attribute('adminList', $this->results);
        $adminList->set_attribute('course_id', $course_id);
        return $adminList;
    }

    public function getTopLinkTemplate($course_id) {
        $adminTopLinks = $GLOBALS['template_factory']->open("admin/topLinks.php");
        $adminTopLinks->set_attribute('adminList', $this->results);
        $adminTopLinks->set_attribute('course_id', $course_id);
        return $adminTopLinks;
    }

    
}

