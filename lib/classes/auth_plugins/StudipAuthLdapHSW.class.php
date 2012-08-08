<?php
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// StudipAuthLdapHSW.class.php
// Stud.IP authentication against LDAP Server, modified for HS Wismar
// 
// Copyright (c) 2005 André Noack <noack@data-quest.de> 
// Suchi & Berg GmbH <info@data-quest.de>
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


require_once ("lib/classes/auth_plugins/StudipAuthLdap.class.php");

/**
* Stud.IP authentication against LDAP Server
*
* Stud.IP authentication against LDAP Server
*
* @access   public
* @author   André Noack <noack@data-quest.de>
* @package  
*/
class StudipAuthLdapHSW extends StudipAuthLdap {
    
    var $study_course_attribute = 'hswstudiengang';
    

    function StudipAuthLdapHSW() {
        //calling the baseclass constructor
        parent::StudipAuthLdap();
    }
    
    function doLdapMap($map_params){
        $ret = "";
        if ($this->user_data[$map_params][0]){
            $ret = $this->user_data[$map_params][0];
        }
        return utf8_decode($ret);
    }
    
    function doLdapMapVorname($map_params){
        $ret = "";
        $ldap_field = $this->user_data[$map_params[0]][$map_params[1]];
        if ($ldap_field){
            $pos = strpos($ldap_field,$this->user_data['sn'][0]);
            if ($pos !== false){
                $ret = trim(substr($ldap_field,0,$pos));
            }
        }
        return utf8_decode($ret);
    }
    
    function doDataMapping($uid){
        $this->doLdapMapStudyCourse($uid);
        return parent::doDataMapping($uid);
    }
    
    function isMappedField($name){
        return (parent::isMappedField($name) || $name == 'studiengang_id');
    }
    
    function doLdapMapStudyCourse($uid){
        $db = $this->dbv->db;
        $ret = false;
        //delete all studycourses for this user
        $query = "DELETE FROM user_studiengang WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($uid));

        if ($this->user_data[$this->study_course_attribute]['count']){
            for ($i = 0; $i < $this->user_data[$this->study_course_attribute]['count']; ++$i){
                $s_id = null;
                $shortcut = utf8_decode($this->user_data[$this->study_course_attribute][$i]);
                //get the id of existing study course
                $query = "SELECT studiengang_id FROM studiengaenge WHERE beschreibung LIKE CONCAT('(', ?, ')%')";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($shortcut));
                $temp_id = $statement->fetchColumn();
                if ($tmp_id !== false) {
                    $s_id = $tmp_id;
                } else {
                    //insert a new study course, if none is found
                    $s_id = md5(uniqid($shortcut,1));
                    $query = "INSERT INTO studiengaenge (studiengang_id, name, beschreibung, mkdate, chdate)
                              VALUES(?, ?, ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())";
                    $statement = DBManager::get()->prepare($query);
                    $statement->execute(array(
                        $s_id,
                        $shortcut,
                        '(' . $shortcut . ')',
                    ));
                }
                if ($s_id){
                    //link the found study course id to the user
                    $query = "INSERT INTO user_studiengang (user_id, studiengang_id) VALUES (?, ?)";
                    $statement = DBManager::get()->prepare($query);
                    $statement->execute(array($uid, $s_id));
                    $ret += $statement->rowCount();
                }
            }
        }
        return $ret;
    }
    
}
//test
/*
echo "<pre>";
$testuser = "testuser";
$testpasswort = "testpasswort";
$test = new StudipAuthLdapHSW();
$success = $test->doLdapBind($testuser,$testpasswort);
echo $success ? "Angemeldet" : "nicht Angemeldet";
if (!$success) echo "<br><b>" . $test->error_msg . "</b>";
echo "<br>Inhalt von {$test->study_course_attribute}:<br>";
print_r($test->user_data[$test->study_course_attribute]);
echo (int)$test->doLdapMapStudyCourse($test->getStudipUserid($testuser));
echo " Einträge in user_studiengang vorgenommen.";
*/
?>
