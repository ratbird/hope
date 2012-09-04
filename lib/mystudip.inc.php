<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* personal settings
*
* helper functions for handling personal settings
*
*
* @author       Stefan Suchi <suchi@data-quest.de>
* @access       public
* @modulegroup  library
* @module       mystudip.inc
* @package      studip_core
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// mystudip.inc.php
// helper functions for handling personal settings
// Copyright (c) 2003 Stefan Suchi <suchi@data-quest.de>
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
* generates drop-down box for language selection
*
* This function generates a drop-down box for language selection.
* Language could be given as selected default.
*
* @access   public
* @param        string  pre-selected language (in "de_DE" style)
*/

use Studip\Button, Studip\LinkButton;

function select_language($selected_language = "") {
    global $INSTALLED_LANGUAGES, $DEFAULT_LANGUAGE;

    if (!isset($selected_language)) {
        $selected_language = $DEFAULT_LANGUAGE;
    }

    echo "<select name=\"forced_language\" id=\"forced_language\" width=30>";
    foreach ($INSTALLED_LANGUAGES as $temp_language => $temp_language_settings) {
        if ($temp_language == $selected_language) {
            echo "<option selected value=\"$temp_language\">" . $temp_language_settings["name"] . "</option>";
        } else {
            echo "<option value=\"$temp_language\">" . $temp_language_settings["name"] . "</option>";
        }
    }

    echo "</select>";

    return;
}


/**
* generates first page of personal settings
*
* This function generates the first page of personal settings.
*
* @access   public
*/
function change_general_view()
{
    $template = $GLOBALS['template_factory']->open('settings/general');
    $template->my_studip_settings = $GLOBALS['my_studip_settings'];
    echo $template->render();
}
