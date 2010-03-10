<?php
# Lifter007: TODO
# Lifter003: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// StudipAuthStandardExtern.class.php
// Stud.IP authentication, using an external Stud.IP database, e.g. an alternative installation
// 
// Copyright (c) 2003 André Noack <noack@data-quest.de> 
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

require_once ("lib/classes/auth_plugins/StudipAuthStandard.class.php");
require_once ("lib/dbviews/core.view.php");

/**
* Stud.IP authentication, using an external Stud.IP database, e.g. an alternative installation
*
* Stud.IP authentication, using an external Stud.IP database, e.g. an alternative installation
*
* @access   public
* @author   André Noack <noack@data-quest.de>
* @package  
*/
class StudipAuthStandardExtern extends StudipAuthStandard {
    
    /**
    * indicates whether login form should use md5 challenge response auth
    *
    * this should only be true, if password is stored and accessible as md5 hash !
    *
    * @access   public
    * @var      bool
    */
    var $md5_challenge_response = true;
    
    var $bad_char_regex = false;

    var $db_host;
    var $db_name;
    var $db_username;
    var $db_password;
    
    var $user_data;
    
    /**
    * Constructor
    *
    * 
    * @access private
    * 
    */
    function StudipAuthStandardExtern() {
        //calling the baseclass constructor
        parent::StudipAuthAbstract();
        $db = new DB_Seminar();
        $db->Host = $this->db_host;
        $db->Database = $this->db_name;
        $db->User = $this->db_username;
        $db->Password = $this->db_password;
        $this->dbv_auth = new DbView($db);
    }
    
    /**
    * 
    *
    * 
    * @access public
    * 
    */
    function isAuthenticated($username, $password, $jscript){
        $is_authenticated = parent::isAuthenticated($username, $password, $jscript);
        if ($is_authenticated && is_array($this->user_data_mapping)){
            $this->dbv_auth->params[] = join(",",array_keys($this->user_data_mapping));
            $this->dbv_auth->params[] = $username;
            $db = $this->dbv_auth->get_query("view:USER_DATA_UNAME");
            $db->next_record();
            $this->user_data = $db->Record;
        }
        return $is_authenticated;
    }
    
    /**
    * 
    *
    * 
    * @access private
    * 
    */
    function doExternMap($map_params){
        $ret = "";
        if ($this->user_data[$map_params]){
            $ret = $this->user_data[$map_params];
        }
        return $ret;
    }
    
    /**
    * 
    *
    * 
    * @access private
    * 
    */
    function doExternMapPerms($map_params){
        $ret = "";
        if ($this->user_data[$map_params] != "root" || $this->user_data[$map_params] != "admin"){
            $ret = $this->user_data[$map_params];
        } else {
            $ret = "autor";
        }
        return $ret;
    }
}
?>
