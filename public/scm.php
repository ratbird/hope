<?php
/**
 * Duct tape proxy for new trails app so bookmarks won't break.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author   Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license  http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category Stud.IP
 * @since    2.5
 * @todo     Remove this file after 2.5 has been branched out
 */

require '../lib/bootstrap.php';
page_open(array('sess' => 'Seminar_Session',
                'auth' => 'Seminar_Default_Auth',
                'perm' => 'Seminar_Perm',
                'user' => 'Seminar_User'));
include 'lib/seminar_open.php'; // initialise Stud.IP-Session

$view = $_REQUEST['i_view'];
$id   = $_REQUEST['show_scm'];

if ($view === 'change') {
    $url = URLHelper::getLink('dispatch.php/course/scm/edit/' . $id);
} elseif ($view === 'kill') {
    $url = URLHelper::getLink('dispatch.php/course/scm/delete/' . $id);
} elseif ($view === 'first_position') {
    $url = URLHelper::getLink('dispatch.php/course/scm/move/' . $id);
} elseif ($view === 'edit') {
    $url = URLHelper::getLink('dispatch.php/course/scm/edit/' . $id);
} else {
    $url = URLHelper::getLink('dispatch.php/course/scm/' . $id);
}
header('Location: ' . $url, true, 301);
