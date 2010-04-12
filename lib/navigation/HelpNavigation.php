<?php
/**
 * HelpNavigation.php - navigation for help item
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Elmar Ludwig
 * @copyright   2010 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
*/

class HelpNavigation extends Navigation
{
    /**
     * Return the current URL associated with this navigation item.
     */
    public function getURL()
    {
        return format_help_url($GLOBALS['HELP_KEYWORD']);
    }
}
