<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* MysqlDriver.class.php
* 
* 
* 
*
* @author       Peter Thienel <pthienel@web.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup  calendar_modules
* @module       calendar_sync
* @package  Calendar
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// MysqlDriver.class.php
// 
// Copyright (C) 2003 Peter Thienel <pthienel@web.de>,
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
 

class MysqlDriver {

    var $db;
    var $count; 
    
    function MysqlDriver () {
    
        $this->count = 0;
    }
    
    function initialize ($db_name) {
    
        if (!is_object($this->db["$db_name"]))
            $this->db["$db_name"] = new DB_Seminar();
    }
    
    function count () {
    
        $this->count++;
    }
    
    function getCount () {
    
        return $this->count;
    }
    
}
    
