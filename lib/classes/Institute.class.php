<?php

/**
* Seminar.class.php
*
* the seminar main-class
*
*
* @author       Rasmus Fuhse <fuhse@data-quest>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup      core
* @module       Institute.class.php
* @package      raumzeit
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// Institute.class.php
// Copyright (C) 2010 Rasmus Fuhse <fuhse@data-quest>, data-quest GmbH <info@data-quest.de>
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

class Institute {
    var $id;
    
    static function getInstitutes() {
        $db = DBManager::get();
        $result = $db->query("SELECT Institute.Institut_id, Institute.Name, IF(Institute.Institut_id=Institute.fakultaets_id,1,0) AS is_fak " .
                "FROM Institute " .
                    "LEFT JOIN Institute as fakultaet ON (Institute.fakultaets_id = fakultaet.Institut_id) " .
                "ORDER BY fakultaet.Name ASC, is_fak DESC, Institute.Name ASC")->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
    
    static function getMyInstitutes($user_id = NULL) {
        global $perm, $user;
        if (!$user_id) {
            $user_id = $user->id;
        }
        $db = DBManager::get();
        if (!$perm->have_perm("admin")) {
            $result = $db->query("SELECT user_inst.Institut_id, Institute.Name, IF(user_inst.Institut_id=Institute.fakultaets_id,1,0) AS is_fak, user_inst.inst_perms " .
                "FROM user_inst " .
                    "LEFT JOIN Institute USING (institut_id) " .
                    "LEFT JOIN Institute as fakultaet ON (Institute.fakultaets_id = Institute.Institut_id) " .
                "WHERE (user_id = ".$db->quote($user_id)." " . 
                    "AND (inst_perms = 'dozent' OR inst_perms = 'tutor')) " . 
                "ORDER BY fakultaet.Name ASC, is_fak DESC, Institute.Name ASC")->fetchAll(PDO::FETCH_ASSOC);
        } else if (!$perm->have_perm("root")) {
            $result = $db->query("SELECT user_inst.Institut_id, Institute.Name, IF(user_inst.Institut_id=Institute.fakultaets_id,1,0) AS is_fak, user_inst.inst_perms " .
                "FROM user_inst " .
                    "LEFT JOIN Institute USING (institut_id) " .
                    "LEFT JOIN Institute as fakultaet ON (Institute.fakultaets_id = Institute.Institut_id) " .
                "WHERE (user_id = ".$db->quote($user_id)." AND inst_perms = 'admin') " .
                "ORDER BY fakultaet.Name ASC, is_fak DESC, Institute.Name ASC")->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $result = $db->query("SELECT Institute.Institut_id, Institute.Name, IF(Institute.Institut_id=Institute.fakultaets_id,1,0) AS is_fak, 'admin' AS inst_perms " .
                "FROM Institute " .
                    "LEFT JOIN Institute as fakultaet ON (Institute.fakultaets_id = fakultaet.Institut_id) " .
                "ORDER BY fakultaet.Name ASC, is_fak DESC, Institute.Name ASC")->fetchAll(PDO::FETCH_ASSOC);
        }
        return $result;
    }
    
    public function __construct($id) {
        $db = DBManager::get();
        $db->query();
        $this->id = $id;
    }
}
