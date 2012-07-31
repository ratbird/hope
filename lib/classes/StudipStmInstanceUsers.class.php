<?php
# Lifter002: DONE - not applicable
# Lifter003: TEST
# Lifter007: TEST
# Lifter010: DONE - not applicable

/**
* StudipStmInstanceUsers.class.php
* 
* 
* 
*
* @author   André Noack <noack@data-quest.de>
*           Suchi & Berg GmbH <info@data-quest.de>
* @access   public
* @package  
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// StudipStmInstanceUsers.class.php
// 
// Copyright (C) 2006 André Noack <noack@data-quest.de>,
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

require_once 'lib/classes/StudipStmInstance.class.php';
require_once 'lib/classes/Seminar.class.php';

class StudipStmInstanceUsers
{

    function GetInstanceUsers($instance_id)
    {
        $query = "SELECT DISTINCT user_id
                  FROM stm_instances_user
                  WHERE stm_instance_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($instance_id));
        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }
    
    function IsInstanceUser($user_id, $instance_id)
    {
    }
    
    function IsInstanceSemUser($user_id, $seminar_id)
    {
        $query = "SELECT 1
                  FROM stm_instances_user AS siu
                  INNER JOIN stm_instances_elements AS sie ON USING (stm_instance_id, element_id)
                  WHERE user_id = ? AND sem_id = ?
                  LIMIT 1";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user_id, $seminar_id));
        return $statement->fetchColumn() > 0;
    }
}
