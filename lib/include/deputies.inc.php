<?php
# Lifter010: TODO
/**
* Default deputies
*
* Helper functions for handling default deputies and their permissions
*
*
* @author       Thomas Hackl <thomas.hackl@uni-passau.de>
* @access       public
* @modulegroup  library
* @module       deputies.inc
* @package      studip_core
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// deputies.inc.php
// helper functions for handling default deputies and their permissions
// Copyright (c) 2010 Thomas Hackl <thomas.hackl@uni-passau.de>
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

$template = $GLOBALS['template_factory']->open('settings/deputies');

if ($perm->have_perm("root"))
    $user_id = get_userid($username);
else
    $user_id = $auth->auth["uid"];

$args['permission'] = getValidDeputyPerms();
$args['edit_about_enabled'] = get_config('DEPUTIES_EDIT_ABOUT_ENABLE');
$args['deputies'] = getDeputies($user_id, true);

if ($_SESSION['deputy_id_parameter']) {
    Request::set('deputy_id_parameter', $deputy_id_parameter);
}

$template->clear_attributes();
echo $template->render($args);
?>