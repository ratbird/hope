<?php
/**
* Administrative functions for session management
*
* @author       Florian Hansen <f1701h@gmx.net>
* @version      0.11    10.09.2003  21:26:09
* @access       public
* @modulegroup  wap_modules
* @module       wap_adm.inc.php
* @package      WAP
*/
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// wap_adm.inc.php
// Administrative functions
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

require_once("lib/classes/auth_plugins/StudipAuthAbstract.class.php");

    /**
    * Gets the ID of a user
    *
    * Gets the user-id corresponding to the given session-id.
    *
    * @author           Florian Hansen <f1701h@gmx.net>
    * @version          0.1
    * @access           public
    * @param    string  The current session-id of the user
    * @return   string  The matching user-id if found, otherwise FALSE
    */
    function wap_adm_get_user_id($session_id)
    {
        if (!$session_id)
            return FALSE;

        $q_string  = "SELECT user_id FROM wap_sessions ";
        $q_string .= "WHERE session_id = '" . $session_id . "'";

        $db = new DB_Seminar;
        $db-> query ($q_string);

        if ($db-> next_record())
            return $db-> f("user_id");
        else
            return FALSE;
    }

    /**
    * Gets the name of a user
    *
    * Gets the user-name corresponding to the given user-id.
    *
    * @author           Florian Hansen <f1701h@gmx.net>
    * @version          0.1
    * @access           public
    * @param    string  User-id of the wanted user
    * @return   string  The user-name if found, otherwise FALSE.
    */
    function wap_adm_get_user_name($user_id)
    {
        if (!$user_id)
            return FALSE;

        $q_string  = "SELECT username FROM auth_user_md5 ";
        $q_string .= "WHERE user_id = '" . $user_id . "'";

        $db = new DB_Seminar;
        $db-> query ($q_string);

        if ($db-> next_record())
            return $db-> f("username");
        else
            return FALSE;
    }

    /**
    * Removes a session from the database.
    *
    * @author           Florian Hansen <f1701h@gmx.net>
    * @version          0.1
    * @access           public
    * @param    string  The current session-id of the user
    * @return   boolean FALSE if no session-id was given
    */
    function wap_adm_remove_session($session_id)
    {
        if (!$session_id)
            return FALSE;

        $user_id = wap_adm_get_user_id($session_id);

        $q_string  = "DELETE FROM wap_sessions ";
        $q_string .= "WHERE user_id = '" . $user_id . "' ";
        $q_string .= "AND session_id = '" . $session_id ."'";

        $db = new DB_Seminar;
        $db-> query($q_string);
    }

    /**
    * Checks the login-data.
    *
    * Verifies the correctness of the given login data.
    *
    * @author           Florian Hansen <f1701h@gmx.net>
    * @version          0.11    05.09.2003  16:30:21
    * @access           public
    * @param    string  user-name
    * @param    string  password
    * @return   string  A new session-id if login data was correct,
    *                       otherwise FALSE
    */
    function wap_adm_check_user($user_name, $user_pass)
    {
        include_once("wap_buttons.inc.php");
        include_once("wap_hlp.inc.php");

        $db = new DB_Seminar;

        /*
        $encoded_pass = md5($user_pass);

        $q_string  = "SELECT user_id ";
        $q_string .= "FROM auth_user_md5 ";
        $q_string .= "WHERE username = '" . $user_name . "' ";
        $q_string .= "AND password = '" . $encoded_pass . "'";

        $db-> query ($q_string);
        */
        $check_auth = StudipAuthAbstract::CheckAuthentication($user_name,$user_pass,false);
        if ($check_auth['uid']){
            $user_id = $check_auth['uid'];
            $new_session_id = md5(uniqid("WapSession", TRUE));
            $q_string  = "INSERT INTO wap_sessions ";
            $q_string .= "(user_id, session_id, creation_time) ";
            $q_string .= "VALUES ('$user_id', '$new_session_id', NOW())";
            $db-> query ($q_string);

            return $new_session_id;
        }
        else
        {
            return FALSE;
        }
    }

    /**
    * Starts a new wml deck with one card.
    *
    * Checks if the current session expired, sets the proper language settings
    * and prints the wml-head.
    *
    * @author           Florian Hansen <f1701h@gmx.net>
    * @version          0.1
    * @access           public
    * @param    string  session-id
    * @return   string  The user-id if a valid session-id was given,
    *                       otherwise FALSE
    */
    function wap_adm_start_card($session_id = FALSE)
    {
        define("SESSION_TIME_OUT", 1800);

        global $_language;
        global $_language_path;
        global $session_expired;

        include_once("wap_buttons.inc.php");
        include_once("wap_hlp.inc.php");
        include_once('lib/language.inc.php');
        include_once('lib/functions.php');

        $session_expired = FALSE;

        header("Content-Type: text/vnd.wap.wml;charset=iso-8859-1");
        echo '<?xml version="1.0"?>' . "\n";
        echo '<!DOCTYPE wml PUBLIC "-//WAPFORUM//DTD WML 1.1//EN" ';
        echo '"http://www.wapforum.org/DTD/wml_1.1.xml">' . "\n";
        echo "<wml>\n";
        echo "<head>\n";
        echo "    <meta http-equiv=\"Cache-Control\" content=\"no-store\" forua=\"true\"/>\n";
        echo "</head>\n";
        echo "<card newcontext=\"true\">\n\n";
        
        if(!get_config('WAP_ENABLE')){
            echo "<p>";
            echo _("Der WAP Zugriff ist in dieser Installation ausgeschaltet.") . "<br/>";
            echo "</p>\n";
            wap_adm_end_card();
            die();
        }
        
        if (!$session_id)
        {
            $_language      = wap_hlp_get_language(FALSE);
            $_language_path = init_i18n($_language);
            return FALSE;
        }

        $q_string  = "SELECT NOW() - creation_time AS session_duration ";
        $q_string .= "FROM wap_sessions ";
        $q_string .= "WHERE session_id = '" . $session_id . "'";

        $db = new DB_Seminar;
        $db-> query($q_string);

        if (!$db-> next_record())
        {
            $_language      = wap_hlp_get_language(FALSE);
            $_language_path = init_i18n($_language);
            return FALSE;
        }

        $user_id          = wap_adm_get_user_id($session_id);
        $session_duration = $db-> f("session_duration");

        $_language      = wap_hlp_get_language($user_id);
        $_language_path = init_i18n($_language);

        if ($session_duration > SESSION_TIME_OUT)
        {
            wap_adm_remove_session($session_id);
            $user_name = wap_adm_get_user_name($user_id);

            echo "<p>";
            echo _("Ihre Sitzung ist abgelaufen. Bitte erneut anmelden.") . "<br/>";
            echo "</p>\n";

            echo "<p align=\"right\">\n";
            echo "<anchor>" . wap_buttons_login() . "\n";
            echo "    <go method=\"post\" href=\"login_form.php\">\n";
            echo "        <postfield name=\"user_name\" value=\"$user_name\"/>\n";
            echo "    </go>\n";
            echo "</anchor><br/>\n";

            wap_buttons_menu_link(FALSE);
            echo "</p>\n";

            $session_expired = TRUE;
            return FALSE;
        }
        else
        {
            $q_string  = "UPDATE wap_sessions ";
            $q_string .= "SET creation_time = NOW() ";
            $q_string .= "WHERE session_id = '" . $session_id . "'";
            $db-> query($q_string);

            return $user_id;
        }
    }

    /**
    * Closes the current card and deck.
    *
    * Prints the required close-tags.
    *
    * @author           Florian Hansen <f1701h@gmx.net>
    * @version          0.1
    * @access           public
    */
    function wap_adm_end_card()
    {
        echo "\n";
        echo "</card>\n";
        echo "</wml>\n";
    }
?>
