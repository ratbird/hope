<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
 *  Base functionality for accessing data in the Stud.IP database,
 *  used by the plugin engine and plugins.
 *  @author  Dennis Reil, <Dennis.Reil@offis.de>
 *  @package pluginengine
 *  @subpackage core
 */
class StudIPCore {


    /**
     * Returns all Institutes, which are registered in the studip database
     */
    static function getInstitutes() {

        $db = DBManager::get();

        # get institutes from database
        $institutes = array();
        $sql = "SELECT LPAD(Institut_id,32,'0') as inst_id, ".
               "Institut_id, Name, fakultaets_id FROM Institute ".
               "ORDER BY fakultaets_id, inst_id, Name";
        foreach ($db->query($sql) as $row) {

            $instid   = $row["Institut_id"];
            $parentid = $row["fakultaets_id"];
            $name     = $row["Name"];

            // a new parent element
            if ($instid == $parentid) {

                $institute = new StudIPInstitute();
                $institute->setId($instid);
                $institute->setName($name);

                // institute already created
                if (is_object($institutes[$instid])) {
                    $childs = $institutes[$instid]->getAllChildInstitutes();
                    foreach ($childs as $child){
                        $institute->addChild($child);
                    }
                }
                $institutes[$instid] = $institute;
            }

            // a child institute
            else {
                $child = new StudIPInstitute();
                $child->setId($instid);
                $child->setName($name);
                $institute = $institutes[$parentid];
                if (!is_object($institute)) {
                    $institute = new StudIPInstitute();
                }
                $institute->addChild($child);
                $institutes[$parentid] = $institute;
            }
        }

        return $institutes;
    }


    /**
     *
     */
    static function getInstituteByFacultyId($facultyid) {

        $db = DBManager::get();

        $stmt = $db->prepare("SELECT Institut_id, Name, fakultaets_id ".
                             "FROM Institute ".
                             "WHERE fakultaets_id=? ".
                             "AND Institut_id <> fakultaets_id");

        $stmt->execute(array($facultyid));

        $institutes = array();
        while ($row = $stmt->fetch()) {
            $instid = $row["Institut_id"];
            $name = $row["Name"];

            $institute = new StudIPInstitute();
            $institute->setId($instid);
            $institute->setName($name);

            $institutes[] = $institute;
        }

        return $institutes;
    }


    /**
     *
     */
    static function getInstitute($instituteid) {

        $db = DBManager::get();

        $stmt = $db->prepare("SELECT Institut_id, Name, fakultaets_id ".
                             "FROM Institute ".
                             "WHERE Institut_id=?");

        $stmt->execute(array($instituteid));

        $institute = array();
        if ($row = $stmt->fetch()) {
            $instid   = $row["Institut_id"];
            $parentid = $row["fakultaets_id"];
            $name     = $row["Name"];

            $institute = new StudIPInstitute();
            $institute->setId($instid);
            $institute->setName($name);

            // Childs abfragen und einfügen
            if ($parentid == $instid) {
                foreach ($this->getInstituteByFacultyId($parentid) as $child) {
                    $institute->addChild($child);
                }
            }
        }

        return $institute;
    }


    /**
     * Returns all semester registered in the stud.ip database
     *
     * @return unknown
     */
    static function getSemester() {

        $db = DBManager::get();

        $stmt = $db->prepare("SELECT * FROM semester_data ORDER BY beginn DESC");

        $stmt->execute();

        $semester = array();
        $current = time();
        while ($row = $stmt->fetch()) {

            if ($current >= $row["beginn"] &&
                $current <= $row["ende"]) {
                $semester[] = array("id"              => $row["semester_id"],
                                    "name"            => $row["name"],
                                    "currentsemester" => true);
            }
            else {
                $semester[] = array("id"              => $row["semester_id"],
                                    "name"            => $row["name"],
                                    "currentsemester" => false);
            }

        }

        return $semester;
    }


    /**
     * Returns all semester registered in the stud.ip database
     *
     * @return unknown
     */
    static function getSeminarsForInstitute($instituteid, $semesterid) {

        $db = DBManager::get();

        $stmt = $db->prepare("SELECT se.* FROM seminar_inst s ".
                             "JOIN seminare se ON (s.seminar_id=se.seminar_id) ".
                             "WHERE s.institut_id=? AND se.start_time = (".
                             "SELECT beginn FROM semester_data ".
                             "WHERE semester_id=?) ".
                             "UNION SELECT * FROM seminare sem ".
                             "WHERE sem.institut_id=? AND sem.start_time=(".
                             "SELECT beginn FROM semester_data ".
                             "WHERE semester_id=?)");
        $stmt->execute(array($instituteid, $semesterid,
                             $instituteid, $semesterid));

        $courses = array();
        while ($row = $stmt->fetch()) {
            $courses[] = array("id"    => $row["Seminar_id"],
                               "titel" => $row["Name"]);
        }
        return $courses;
    }
}
