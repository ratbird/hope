<?php
/**
* Text buttons for navigation etc.
*
* These functions create link-descriptions for anchors.
* They each return the appropriate description-string.
*
* @author       Florian Hansen <f1701h@gmx.net>
* @version      0.13    16.09.2003  19:26:39
* @access       public
* @modulegroup  wap_modules
* @module       wap_buttons.inc.php
* @package      WAP
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// wap_buttons.inc.php
// Text buttons
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

    include_once("wap_txt.inc.php");

    /**
    * page-forward-button.
    *
    * Creates a page-forward-button with page numbers.
    *
    * @author           Florian Hansen <f1701h@gmx.net>
    * @version          0.11    16.09.2003  19:26:00
    * @access           public
    * @param    int     The current page
    * @param    int     The total number of pages
    * @return   string  The text-button
    */
     function wap_buttons_forward_page($page, $num_pages)
     {
        global $_language_path;

        $e = wap_txt_encode_to_wml(_("Seite"));
        return "&#187; $e " . ($page + 1) . " / $num_pages";
     }

    /**
    * page-backward-button.
    *
    * Creates a page-back-button with page numbers.
    *
    * @author           Florian Hansen <f1701h@gmx.net>
    * @version          0.11    16.09.2003  19:26:05
    * @access           public
    * @param    int     The current page
    * @param    int     The total number of pages
    * @return   string  The text-button
    */
     function wap_buttons_back_page($page, $num_pages)
     {
        global $_language_path;

        $e = wap_txt_encode_to_wml(_("Seite"));
        return "&#171; $e " . ($page + 1) . " / $num_pages";
     }

    /**
    * part-forward-button.
    *
    * Creates a part-forward-button with part numbers.
    *
    * @author           Florian Hansen <f1701h@gmx.net>
    * @version          0.12    16.09.2003  19:26:13
    * @access           public
    * @param    int     The current part
    * @param    int     The total number of parts
    * @return   string  The text-button
    */
     function wap_buttons_forward_part($part, $num_parts)
     {
        global $_language_path;

        $e = wap_txt_encode_to_wml(_("Teil"));
        return "&#187; $e " . ($part + 1) . " / $num_parts";
     }

    /**
    * part-backward-button.
    *
    * Creates a part-backward-button with part numbers.
    *
    * @author           Florian Hansen <f1701h@gmx.net>
    * @version          0.12    16.09.2003  19:26:23
    * @access           public
    * @param    int     The current part
    * @param    int     The total number of parts
    * @return   string  The text-button
    */
     function wap_buttons_back_part($part, $num_parts)
     {
        global $_language_path;

        $e = wap_txt_encode_to_wml(_("Teil"));
        return "&#171; $e " . ($part + 1) . " / $num_parts";
     }

    /**
    * back-button.
    *
    * Creates a simple button to indicate that there is a possibility to
    * return the the previous page.
    *
    * @author           Florian Hansen <f1701h@gmx.net>
    * @version          0.1
    * @access           public
    * @return   string  The text-button
    */
     function wap_buttons_back()
     {
        global $_language_path;

        return "&#171; " . wap_txt_encode_to_wml(_("zurück"));
     }

    /**
    * new-search-button.
    *
    * Creates a simple button for starting a new search.
    *
    * @author           Florian Hansen <f1701h@gmx.net>
    * @version          0.1
    * @access           public
    * @return   string  The text-button
    */
     function wap_buttons_new_search()
     {
        global $_language_path;

        return "&#177; " . wap_txt_encode_to_wml(_("Suche"));
     }

    /**
    * change-time-button.
    *
    * Creates a button changing the a period of time.
    *
    * @author           Florian Hansen <f1701h@gmx.net>
    * @version          0.1
    * @access           public
    * @return   string  The text-button
    */
     function wap_buttons_time()
     {
        global $_language_path;

        return "&#177; " . wap_txt_encode_to_wml(_("Zeitraum"));
     }

    /**
    * login-button.
    *
    * @author           Florian Hansen <f1701h@gmx.net>
    * @version          0.1
    * @access           public
    * @return   string  The text-button
    */
     function wap_buttons_login()
     {
        global $_language_path;

        return "&#187; " . wap_txt_encode_to_wml(_("login"));
     }

    /**
    * logout-button.
    *
    * @author           Florian Hansen <f1701h@gmx.net>
    * @version          0.1
    * @access           public
    * @return   string  The text-button
    */
     function wap_buttons_logout()
     {
        global $_language_path;

        return "&#248; " . wap_txt_encode_to_wml(_("logout"));
     }

    /**
    * show-button.
    *
    * For displaying the desired content.
    *
    * @author           Florian Hansen <f1701h@gmx.net>
    * @version          0.1
    * @access           public
    * @return   string  The text-button
    */
     function wap_buttons_show()
     {
        global $_language_path;

        return "&#187; " . wap_txt_encode_to_wml(_("anzeigen"));
     }

    /**
    * search-button.
    *
    * For starting a (new) search.
    *
    * @author           Florian Hansen <f1701h@gmx.net>
    * @version          0.1
    * @access           public
    * @return   string  The text-button
    */
     function wap_buttons_search()
     {
        global $_language_path;

        return "&#187; " . wap_txt_encode_to_wml(_("suchen"));
     }

    /**
    * menu-button.
    *
    * Creates a complete hyperlink.
    * If a session-id is given, the link will point to the index showed
    * after login, otherwise to the main-index.
    * This function has to be called within a paragraph.
    *
    * @author           Florian Hansen <f1701h@gmx.net>
    * @version          0.1
    * @access           public
    * @param    string  A session_id (allowed to be FALSE)
    */
    function wap_buttons_menu_link($session_id)
    {
        global $_language_path;

        echo "<anchor>&#215; " . wap_txt_encode_to_wml(_("Menü")) . "\n";
        if ($session_id)
        {
            echo "    <go method=\"post\" href=\"login_index.php\">\n";
            echo "        <postfield name=\"session_id\" value=\"$session_id\"/>\n";
            echo "    </go>\n";
        }
        else
        {
            echo "    <go href=\"index.php\"/>\n";
        }
        echo "</anchor>\n";
    }
?>
