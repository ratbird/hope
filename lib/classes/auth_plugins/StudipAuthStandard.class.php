<?php
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// StudipAuthStandard.class.php
// Basic Stud.IP authentication, using the Stud.IP database
//
// Copyright (c) 2003 Andr� Noack <noack@data-quest.de>
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

require_once "lib/classes/auth_plugins/StudipAuthAbstract.class.php";

/**
* Basic Stud.IP authentication, using the Stud.IP database
*
* Basic Stud.IP authentication, using the Stud.IP database
*
* @access   public
* @author   Andr� Noack <noack@data-quest.de>
* @package
*/
class StudipAuthStandard extends StudipAuthAbstract
{

    var $bad_char_regex =  false;

    /**
     * Constructor
     *
     *
     * @access public
     *        
     */
    function __construct()
    {
        parent::__construct();
    }

    /**
    *
    *
    *
    * @access public
    *
    */
    function isAuthenticated($username, $password)
    {
        $user = User::findByUsername($username);
        if (!$user || strlen($password) > 72) {
            $this->error_msg= _("Ung�ltige Benutzername/Passwort-Kombination!") ;
            return false;
        } elseif ($user->username != $username) {
            $this->error_msg = _("Bitte achten Sie auf korrekte Gro&szlig;-Kleinschreibung beim Username!");
            return false;
        } elseif (!is_null($user->auth_plugin) && $user->auth_plugin != "standard") {
            $this->error_msg = sprintf(_("Dieser Benutzername wird bereits �ber %s authentifiziert!"),$db->f("auth_plugin")) ;
            return false;
        } else {
            $pass = $user->password;   // Password is stored as a md5 hash
        }
        $hasher = UserManagement::getPwdHasher();
        if (!($hasher->CheckPassword(md5($password), $pass) || $hasher->CheckPassword($password, $pass))) {
            $this->error_msg= _("Das Passwort ist falsch!");
            return false;
        } else {
            return true;
        }
    }

    function isUsedUsername($username)
    {
        return User::findByUsername($username) ? true : false;
    }

}
?>
