<?php
/*
 * AdminList.class.php - contains AdminList
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Rasmus Fuhse <fuhse@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       2.2
 */


/**
 * Singleton class for the admin search list. This is a singleton-class because
 * the result set is dependend on the session.
 *
 * Get the results dependend on the current session with
 *   AdminList::getInstance()->getSearchResults();
 * It also returns templates for a select-box or navigation-arrows with
 *   AdminList::getInstance()->getSelectTemplate()
 * or
 *   AdminList::getInstance()->getTopLinkTemplate()
 *
 * @author Rasmus Fuhse <fuhse@data-quest.de>
 */
class AdminList {
    static protected $instance = null;

    protected $results = array();

    /**
     * returns an AdminList singleton object
     * @return AdminList
     */
    static public function getInstance() {
        if (!self::$instance) {
            self::$instance = new AdminList();
        }
        return self::$instance;
    }

    /**
     * constructor that starts the first search for this instance
     */
    public function __construct() {
        $GLOBALS['view_mode'] = "sem";
        $this->search();
    }

    /**
     * Saves a search-result-set of seminars depending on the parameters of the session
     * to the AdminList object.
     */
    public function search()
    {
        global $perm, $user;
        //the search parameters are completely saved in the following session variable
        $links_admin_data = $_SESSION['links_admin_data'];
        if (isset($links_admin_data["srch_on"]) && $links_admin_data["srch_on"]) {
            $db = DBManager::get();
            if (!$perm->have_perm("root")) {
                foreach (Institute::getMyInstitutes() as $institute) {
                    $my_inst[] = $institute['Institut_id'];
                }
            }
            
            $forbidden_sem_types = studygroup_sem_types();
            if (!$GLOBALS['perm']->have_perm("root")) {
                foreach ($GLOBALS['SEM_TYPE'] as $id => $sem_type) {
                    if (!$GLOBALS['SEM_CLASS'][$sem_type['class']]->getSlotModule("admin")) {
                        //Die Verwaltungsseite ist ausgeschaltet, 
                        //also darf nicht einmal Admin das Seminar bearbeiten.
                        $forbidden_sem_types[] = $id;
                    }
                }
            }

            $params = array();
            $query =
            "SELECT DISTINCT sem.Seminar_id,sem.Name,VeranstaltungsNummer,sem.visible,sem.status, inst.Name AS Institut, sd1.name AS startsem,IF(duration_time=-1, '"._("unbegrenzt")."', sd2.name) AS endsem " .
            "FROM seminar_user AS su " .
                "LEFT JOIN seminare AS sem USING (seminar_id) " .
                "LEFT JOIN Institute AS inst USING (institut_id) " .
                "LEFT JOIN auth_user_md5 AS u ON (su.user_id = u.user_id) " .
                "LEFT JOIN semester_data AS sd1 ON ( start_time BETWEEN sd1.beginn AND sd1.ende) " .
                "LEFT JOIN semester_data AS sd2 ON ((start_time + duration_time) BETWEEN sd2.beginn AND sd2.ende) " .
            "WHERE su.status = 'dozent' " .
                "AND sem.status NOT IN ('".implode("', '", $forbidden_sem_types)."') ";
            //$params = array('studygroup_sem_types' => studygroup_sem_types());

            if ($links_admin_data["srch_sem"]) {
                $one_semester = Semester::find($links_admin_data["srch_sem"]);
                $query.="AND sem.start_time <= :semester_begin AND (:semester_begin <= (sem.start_time + sem.duration_time) OR sem.duration_time = -1) ";
                $params['semester_begin'] = $one_semester["beginn"];
            }

            if (is_array($my_inst) && !$perm->have_perm("root")) {
                $query.="AND inst.Institut_id IN ('".implode("', '", $my_inst)."') ";
                //$params['my_inst'] = $my_inst;
            }

            if ($links_admin_data["srch_inst"]) {
                $query.="AND inst.Institut_id = :special_institute ";
                $params['special_institute'] = $links_admin_data["srch_inst"];
            }

            if ($links_admin_data["srch_fak"]) {
                $query.="AND fakultaets_id = :special_faculty ";
                $params['special_faculty'] = $links_admin_data["srch_fak"];
            }

            if ($links_admin_data["srch_doz"]) {
                $query.="AND su.user_id = :dozent ";
                $params['dozent'] = $links_admin_data["srch_doz"];
            }

            if ($links_admin_data["srch_exp"]) {
                $query.="AND (sem.Name LIKE :search_expression OR sem.VeranstaltungsNummer LIKE :search_expression OR sem.Untertitel LIKE :search_expression OR sem.Beschreibung LIKE :search_expression OR u.Nachname LIKE :search_expression) ";
                $params['search_expression'] = "%".$links_admin_data["srch_exp"]."%";
            }

            $query.=" ORDER BY `" . $links_admin_data["sortby"] . "` ";
            if ($links_admin_data["sortby"] === 'start_time') {
                $query .= ' DESC';
            }

            $statement = DBManager::get()->prepare($query);
            $statement->execute($params);
            $this->results = $statement->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    /**
     * returns the search results of this instance
     * @param bool $new_search: if true a new search will be forced, default to false
     * @return array of associative arrays (seminare.*, Institute.Name, startsem, endsem)
     */
    public function getSearchResults($new_search = false) {
        if ($new_search) {
            $this->search();
        }
        return $this->results;
    }

    /**
     * returns a template for a selectbox with the current list of the adminsearch
     * @param string $course_id : Seminar_id of the course that should be selected in the select-box
     * @return Flexi_Template
     */
    public function getSelectTemplate($course_id)
    {
        if (count($this->results)) {
            $adminList = $GLOBALS['template_factory']->open('admin/adminList.php');
            $adminList->set_attribute('adminList', $this->results);
            $adminList->set_attribute('course_id', $course_id);
            return $adminList;
        }
    }

    /**
     * returns a template for "back" and "forward" links on top of admin-pages
     * @param string $course_id
     * @return Flexi_Template
     */
    public function getTopLinkTemplate($course_id)
    {
        if (count($this->results)) {
            $adminTopLinks = $GLOBALS['template_factory']->open("admin/topLinks.php");
            $adminTopLinks->set_attribute('adminList', $this->results);
            $adminTopLinks->set_attribute('course_id', $course_id);
            return $adminTopLinks;
        }
    }


}
