<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
 * calendar.php - Calendar-mainfile. Calls the submodules.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Peter Thienel <pthienel@data.quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     calendar
 */


// Default_Auth

require '../lib/bootstrap.php';

unregister_globals();
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check("user");

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// here you have to put initialisations for the current page
if (get_config('CALENDAR_ENABLE')) {
    //Kalenderfrontend einbinden
    include($GLOBALS['RELATIVE_PATH_CALENDAR'].'/calendar.inc.php');
} else {
    //TODO: use messagebox
    require_once ('lib/msg.inc.php');
    // Start of Output
    include ('lib/include/html_head.inc.php'); // Output of html head
    include ('lib/include/header.php');   // Output of Stud.IP head
    $message = _("Der Terminkalender ist nicht eingebunden. Der Terminkalender wurde in den Systemeinstellungen nicht freigeschaltet. Wenden Sie sich bitte an die zuständigen Administrator.");
    parse_window ("error§$message", "§", _("Terminkalender ist nicht eingebunden!"));
    include ('lib/include/html_end.inc.php');
}
