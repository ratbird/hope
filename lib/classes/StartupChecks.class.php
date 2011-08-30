<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* StartupChecks.class.php
*
* checks to determine if the system is ready to create Veranstaltungen
*
*
* @author       Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup      core
* @module       StartupChecks.class.php
* @package      studip_core
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// StartupChecks.class.php
// Checks zum ersten Anlegen einer Veranstaltung
// Copyright (C) 2003 Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+

require_once 'lib/functions.php';
require_once ("config.inc.php");
require_once('lib/classes/SemesterData.class.php');

class StartupChecks {
    var $registered_checks = array (
        "institutes" => array("perm" => "root"),
        "institutesRange" => array("perm" => "admin"),
        "myInstituteRange" => array("perm" => "dozent"),
        "myAdminInstitute" => array("perm" => "admin"),
        "dozent" => array("perm" => "admin"),
        "institutesDozent" => array("perm" => "admin"),
        "myInstitutesDozent" => array("perm" => "admin"),
        "myInstitutes" => array("perm" => "dozent"),
        "semester" => array("perm" => "root"),
        "semesterAdmin" => array("perm" => "admin"),
        "semesterDozent" => array("perm" => "dozent")
    );

    function StartupChecks() {
        $this->registered_checks["institutes"]["msg"] = _("Sie ben&ouml;tigen mindestens eine Einrichtung, an der Veranstaltungen angeboten werden k&ouml;nnen. Legen Sie bitte zun&auml;chst eine Einrichtung an.");
        $this->registered_checks["institutes"]["link"] = "admin_institut.php?i_view=new";
        $this->registered_checks["institutes"]["link_name"] = _("neue Einrichtung anlegen");

        $this->registered_checks["institutesRange"]["msg_fak_admin"] = _("Es existieren keine Studienbereiche in der Veranstaltungshierarchie. Jede Fakult&auml;t sollte &uuml;ber mindestens einen Studienbereich verf&uuml;gen, damit Veranstaltungen angelegt werden k&ouml;nnen. Nutzen Sie daf&uuml;r die Veranstaltunghierarchie, um Studienbereiche zu verwalten.");
        $this->registered_checks["institutesRange"]["msg"] = _("Es existieren keine Studienbereiche in der Veranstaltungshierarchie. Jede Fakult&auml;t sollte &uuml;ber mindestens einen Studienbereich verf&uuml;gen, damit Veranstaltungen angelegt werden k&ouml;nnen. Bitte wenden Sie sich an einen der Administratoren des Systems.");
        $this->registered_checks["institutesRange"]["link_fak_admin"] = "admin_sem_tree.php";
        $this->registered_checks["institutesRange"]["link"] = "dispatch.php/siteinfo/show";
        $this->registered_checks["institutesRange"]["link_name_fak_admin"] = _("Studienbereiche verwalten");
        $this->registered_checks["institutesRange"]["link_name"] = _("Kontakt zu den Administratoren");

        $this->registered_checks["myInstituteRange"]["msg"] = _("Das Anlegen einer Veranstaltung ist nicht m&ouml;glich, da keine Studienbereiche an Ihrer Fakult&auml;t existieren. Bitte wenden Sie sich an einen der Administratoren des Systems.");
        $this->registered_checks["myInstituteRange"]["link"] = "dispatch.php/siteinfo/show";
        $this->registered_checks["myInstituteRange"]["link_name"] = _("Kontakt zu den Administratoren");

        $this->registered_checks["myAdminInstitute"]["msg"] = _("Um eine Veranstaltung anlegen zu k&ouml;nnen, muss Ihr Account mit einer Einrichtung verkn&uuml;pft werden.");
        $this->registered_checks["myAdminInstitute"]["link"] = "dispatch.php/siteinfo/show";
        $this->registered_checks["myAdminInstitute"]["link_name"] = _("Kontakt zu den Administratoren");

        $this->registered_checks["dozent"]["msg"] = _("Sie ben&ouml;tigen mindestens einen Dozentenaccount, um Veranstaltungen anlegen zu k&ouml;nnen. Nutzen Sie die globale Nutzerverwaltung, um einen neuen Nutzer mit dem Status Dozent anzulegen oder den Status eines bestehenden Nutzers auf &raquo;dozent&laquo; zu setzen.");
        $this->registered_checks["dozent"]["link"] = "dispatch.php/admin/user/";
        $this->registered_checks["dozent"]["link_name"] = _("Dozentenaccount anlegen oder anderen Account hochstufen");

        $this->registered_checks["institutesDozent"]["msg"] = _("Um Veranstaltungen anlegen zu k&ouml;nnen, muss der Einrichtung, f&uuml;r die Sie eine Veranstaltung anlegen m&ouml;chten, mindestens ein Dozentenaccount zugeordnet werden. Nutzen Sie daf&uuml;r die Mitarbeiterverwaltung f&uuml;r Einrichtungen.");
        $this->registered_checks["institutesDozent"]["link"] = "inst_admin.php?list=TRUE";
        $this->registered_checks["institutesDozent"]["link_name"] = _("Dozent der Einrichtung zuordnen");

        $this->registered_checks["myInstitutesDozent"]["msg"] = _("Um Veranstaltungen anlegen zu k&ouml;nnen, muss der Einrichtung, f&uuml;r die Sie eine Veranstaltung anlegen m&ouml;chten, mindestens ein Dozentenaccount zugeordnet werden. Nutzen Sie daf&uuml;r die Mitarbeiterverwaltung f&uuml;r Einrichtungen.");
        $this->registered_checks["myInstitutesDozent"]["link"] = "inst_admin.php?list=TRUE";
        $this->registered_checks["myInstitutesDozent"]["link_name"] = _("Dozent der Einrichtung zuordnen");

        $this->registered_checks["myInstitutes"]["msg"] = _("Um Veranstaltungen anlegen zu k&ouml;nnen, muss Ihr Account der Einrichtung, f&uuml;r die Sie eine Veranstaltung anlegen m&ouml;chten, zugeordnet werden. Bitte wenden Sie sich an einen der Administratoren des Systems.");
        $this->registered_checks["myInstitutes"]["link"] = "dispatch.php/siteinfo/show";
        $this->registered_checks["myInstitutes"]["link_name"] = _("Kontakt zu den Administratoren");

        $this->registered_checks["semester"]["msg"] = _("Um Veranstaltungen anlegen zu können muss mindestens ein Semester existieren, welches den jetzigen Zeitpunkt beinhaltet. Bitte legen Sie ein passendes Semester an.");
        $this->registered_checks["semester"]["link"] = "dispatch.php/admin/semester/edit_semester";
        $this->registered_checks["semester"]["link_name"] = _("Neues Semester anlegen");

        $this->registered_checks["semesterAdmin"]["msg"] = _("Um Veranstaltungen anlegen zu können muss mindestens ein Semester existieren, welches den jetzigen Zeitpunkt beinhaltet. Um ein neues Semester anzulegen werden root-Rechte benötigt. Bitte wenden Sie sich an jemanden mit den nötigen Rechten.");
        $this->registered_checks["semesterAdmin"]["link"] = "dispatch.php/siteinfo/show";
        $this->registered_checks["semesterAdmin"]["link_name"] = _("Kontakt zu den Administratoren");

        $this->registered_checks["semesterDozent"]["msg"] = _("Um Veranstaltungen anlegen zu können muss mindestens ein Semester existieren, welches den jetzigen Zeitpunkt beinhaltet. Bitte wenden Sie sich an einen der Administratoren des Systems.");
        $this->registered_checks["semesterDozent"]["link"] = "dispatch.php/siteinfo/show";
        $this->registered_checks["semesterDozent"]["link_name"] = _("Kontakt zu den Administratoren");
    }

    function institutes() {
        $db = DBManager::get();

        $query = "SELECT Institut_id FROM Institute";
        $result = $db->query($query);

        return $result->rowCount() == 0;
    }

    function institutesRange() {
        global $user, $perm;

        $db = DBManager::get();

        if (!$perm->have_perm ("root")) {
            $query = sprintf ("SELECT a.Institut_id, IF(a.Institut_id=fakultaets_id,1,0) AS is_fak,inst_perms FROM user_inst  a LEFT JOIN Institute USING (institut_id) WHERE (user_id = '%s' AND inst_perms = 'admin')", $user->id);

            $result = $db->query($query);
            foreach ($result as $row) {
                $tmp_inst_ids[]=$row['Institut_id'];
                if ($row['is_fak']) {
                    $query2 = sprintf ("SELECT a.Institut_id FROM Institute a WHERE fakultaets_id='%s' AND a.Institut_id!='%s' ", $row['Institut_id'], $row['Institut_id']);
                    $result2 = $db->query($query2);
                    foreach ($result2 as $row2) {
                        $tmp_inst_ids[]=$row2['Institut_id'];
                    }
                }
            }

            if (is_array($tmp_inst_ids))
                $clause = implode("', '", $tmp_inst_ids);

            $query = sprintf ("SELECT studip_object_id FROM user_inst LEFT JOIN Institute USING (Institut_id) LEFT JOIN sem_tree ON (Institute.fakultaets_id = sem_tree.studip_object_id) WHERE user_inst.Institut_id IN ('%s') AND studip_object_id IS NOT NULL ", $clause);
        } else {
            $query = "SELECT studip_object_id FROM sem_tree LEFT JOIN Institute ON (Institute.fakultaets_id = sem_tree.studip_object_id)";
        }

        $result = $db->query($query);

        return $result->rowCount() == 0;
    }

    function myInstituteRange() {
        global $user;

        $db = DBManager::get();

        $query = sprintf ("SELECT studip_object_id FROM user_inst LEFT JOIN Institute USING (Institut_id) LEFT JOIN sem_tree ON (Institute.fakultaets_id = sem_tree.studip_object_id) WHERE user_id = '%s' ", $user->id);
        $result = $db->query($query);

        return $result->rowCount() == 0;
    }

    function myAdminInstitute() {
        global $user, $perm;

        $db = DBManager::get();

        if ($perm->have_perm ("root")) {
            return FALSE;
        }

        $query = sprintf ("SELECT user_id FROM user_inst WHERE user_id = '%s' AND inst_perms = 'admin' ", $user->id);
        $result = $db->query($query);

        return $result->rowCount() == 0;
    }

    function dozent() {
        $db = DBManager::get();

        $query = "SELECT user_id FROM auth_user_md5 WHERE perms = 'dozent'";
        $result = $db->query($query);

        return $result->rowCount() == 0;
    }

    function institutesDozent() {
        $db = DBManager::get();

        $query = "SELECT user_id FROM user_inst WHERE inst_perms = 'dozent'";
        $result = $db->query($query);

        return $result->rowCount() == 0;
    }

    function myInstitutesDozent() {
        global $user, $perm;

        $db = DBManager::get();

        if ($perm->have_perm ("root")) {
            return FALSE;
        } else {
            $query = sprintf ("SELECT a.Institut_id, IF(a.Institut_id=fakultaets_id,1,0) AS is_fak,inst_perms FROM user_inst  a LEFT JOIN Institute USING (institut_id) WHERE (user_id = '%s' AND inst_perms = 'admin')", $user->id);

            $result = $db->query($query);
            foreach ($result as $row) {
                $tmp_inst_ids[]=$row['Institut_id'];
                if ($row['is_fak']) {
                    $query2 = sprintf ("SELECT a.Institut_id FROM Institute a WHERE fakultaets_id='%s' AND a.Institut_id!='%s' ", $row['Institut_id'], $row['Institut_id']);
                    $result2 = $db->query($query2);
                    foreach ($result2 as $row2) {
                        $tmp_inst_ids[]=$row2['Institut_id'];
                    }
                }
            }

            if (is_array($tmp_inst_ids))
                $clause = implode("', '", $tmp_inst_ids);

            $query = sprintf ("SELECT user_id FROM user_inst WHERE inst_perms = 'dozent' AND Institut_id IN ('%s')", $clause);
        }

        $result = $db->query($query);

        return $result->rowCount() == 0;
    }

    function myInstitutes() {
        global $user;

        $db = DBManager::get();

        $query = sprintf ("SELECT user_id FROM user_inst WHERE inst_perms = 'dozent' AND user_id = '%s'", $user->id);
        $result = $db->query($query);

        return $result->rowCount() == 0;
    }

    function semester() {
        $semester = new SemesterData();
        $all_semester = $semester->getAllSemesterData();

        foreach ($all_semester as $key => $semester) {
            if ((!$semester["past"]) && ($semester["ende"] > time())) {
                return false;
            }
        }

        return true;
    }

    function semesterDozent() {
        return $this->semester();
    }

    function semesterAdmin() {
        global $perm;
        if (!$perm->have_perm('root')) {
            return $this->semester();
        }

        return false;
    }

    function getCheckList() {
        global $perm;
        $list = array();

        foreach ($this->registered_checks as $key=>$val) {
            if ((($this->registered_checks[$key]["perm"] == "root") && ($perm->have_perm("root"))) ||
                 (($this->registered_checks[$key]["perm"] == "admin") && ($perm->have_perm("admin"))) ||
                 (($this->registered_checks[$key]["perm"] == "dozent") && ($perm->have_perm("dozent")) && (!$perm->have_perm("root")) && (!$perm->have_perm("admin")))) {

                if (method_exists($this,$key)) {
                    $list[$key] = $this->$key();
                }
            }
        }
        return $list;
    }
}
