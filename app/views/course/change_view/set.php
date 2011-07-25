<?php
/*
 * set.php - contains redirection after successful view change
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Thomas Hackl <thomas.hackl@uni-passau.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       2.1
 */
// Set simulated view, redirect to overview page.
if ($_SESSION['seminar_change_view_'.Request::option('cid')]) {
    $location = URLHelper::getURL('seminar_main.php', array('cid' => Request::option('cid')));
// Reset simulated view, redirect to administration page.
} else {
    $location = URLHelper::getURL('dispatch.php/course/management', array('cid' => Request::option('cid')));
}
header('Location: '.$location);
?>