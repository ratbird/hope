<?php
# Lifter007: TODO
# Lifter003: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// StudipAuthLdapReader.class.php
// Stud.IP authentication against LDAP Server using read-only account
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
class StudipAuthLdapReader extends StudipAuthLdap {
    
    var $anonymous_bind = false;
    
    var $user_password_attribute;
    var $reader_dn ;
    var $reader_password;
    
    
    var $conn = null;
    var $user_data = null;
    
    /**
    * Constructor
    *
    * 
    * @access public
    * 
    */
    function StudipAuthLdapReader() {
        //calling the baseclass constructor
        parent::StudipAuthLdap();
    }
    
                
    function doLdapBind($username){
        if (!$this->doLdapConnect()){
            return false;
        }
        if (!($user_dn = $this->getUserDn($username))){
            return false;
        }
        if (!($r = @ldap_bind($this->conn, $this->reader_dn, $this->reader_password))){
            $this->error_msg = sprintf(_("Anmeldung von %s fehlgeschlagen."),$this->reader_dn) . $this->getLdapError();
            return false;
        }
        if (!($result = @ldap_search($this->conn, $this->base_dn, $this->getLdapFilter($username)))){
            $this->error_msg = _("Abholen der User Attribute fehlgeschlagen.") .$this->getLdapError();
            return false;
        }
        if (@ldap_count_entries($this->conn, $result)){
            if (!($info = @ldap_get_entries($this->conn, $result))){
                $this->error_msg = $this->getLdapError();
                return false;
            }
        } else {
            $this->error_msg = _("Der Username wurde nicht gefunden.");
            return false;
        }
        $this->user_data = $info[0];
        return true;
    }
        
    /**
    * 
    *
    * 
    * @access public
    * 
    */
    function isAuthenticated($username, $password, $jscript){
        
        if (!$this->doLdapBind($username)){
            ldap_unbind($this->conn);
            return false;
        }
        //userPassword in LDAP is base64 encoded, PHP md5() gives base16 !
        $pass = bin2hex(base64_decode(substr(trim($this->user_data[$this->user_password_attribute][0]),strlen("{MD5}"))));
        if (md5($password) != $pass) {
            $this->error_msg= _("Das Passwort ist falsch!");
            ldap_unbind($this->conn);
            return false;
        } else {
            ldap_unbind($this->conn);
            return true;
        }
    }
    
    
    function isUsedUsername($username){
        return $this->doLdapBind($username);
    }
}

?>
