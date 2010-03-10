<?php
/**
* Helping functions for different purposes.
*
* @author       Florian Hansen <f1701h@gmx.net>
* @version      0.1
* @access       public
* @modulegroup  wap_modules
* @module       wap_hlp.inc.php
* @package      WAP
*/
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// wap_hlp.inc.php
// Helping functions
// Copyright (c) 2003 Florian Hansen <f1701h@gmx.net>
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

    /**
    * Gets the language-string of the user-preferred language.
    *
    * @author           Florian Hansen <f1701h@gmx.net>
    * @version          0.1
    * @access           public
    * @param    string  A user-id
    * @return   string  The user-preferred language if found,
    *                   the systems default-language otherwise.
    */
    function wap_hlp_get_language($user_id)
    {
        global $INSTALLED_LANGUAGES, $DEFAULT_LANGUAGE;

        if (!$user_id)
            return $DEFAULT_LANGUAGE;

        // try to get preferred language from user
        $db = new DB_Seminar;
        $db-> query("SELECT preferred_language FROM user_info WHERE user_id='$user_id'");

        if ($db->next_record())
        {
            if ($db->f("preferred_language") != NULL && $db->f("preferred_language") != "")
            {
                // we found a stored setting for preferred language
                $language = $db->f("preferred_language");
                return $language;
            }
        }
        // no preferred language, use system default
        return $DEFAULT_LANGUAGE;
    }

    /**
    * Makes the specified global user-vars available.
    *
    * @author           Florian Hansen <f1701h@gmx.net>
    * @version          0.1
    * @access           public
    * @param    string  A user-id
    * @param    string  The name or part of the name of the
    *                       required global variable
    * @return   boolean FALSE, if the user could not be found
    */
    
    function wap_hlp_get_global_user_var_old($user_id, $var_string)
    {
        $q_string  = "SELECT val ";
        $q_string .= "FROM active_sessions ";
        $q_string .= "WHERE sid = '" . $user_id . "' ";
        $q_string .= "AND name = 'Seminar_User'";

        $db = new DB_Seminar;
        $db-> query($q_string);

        if (!$db-> next_record())
            return FALSE;

        $values = base64_decode($db-> f("val"));
        $values = str_replace("Seminar_User:", "", $values);

        $value_array = explode(";", $values);
        foreach ($value_array as $value)
        {
            if (   !(strpos($value, "\$GLOBALS") === FALSE)
                && !(strpos($value, $var_string) === FALSE))
            {
                eval("$value;");
            }
        }
    }
    
    function wap_hlp_get_global_user_var_new($user_id, $var_string){
        $user =& new Seminar_User($user_id);
        $GLOBALS[$var_string] = $user->user_vars[$var_string];
        return true;
    }
    
    function wap_hlp_get_global_user_var($user_id, $var_string){
        return ('active_sessions' == PHPLIB_USERDATA_TABLE ? wap_hlp_get_global_user_var_old($user_id, $var_string) : wap_hlp_get_global_user_var_new($user_id, $var_string));
    }

?>
