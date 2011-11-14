<?php
# Lifter010: TODO
/**
* Privacy settings
*
* Helper functions for handling privacy settings
*
*
* @author       Thomas Hackl <thomas.hackl@uni-passau.de>
* @access       public
* @modulegroup  library
* @module       privacy.inc
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
global $FOAF_ENABL;
$template = $GLOBALS['template_factory']->open('settings/privacy');

if ($perm->have_perm("root"))
    $user_id = get_userid($username);
else if (get_config('DEPUTIES_ENABLE') && get_config('DEPUTIES_DEFAULTENTRY_ENABLE') && get_config('DEPUTIES_EDIT_ABOUT_ENABLE') && isDeputy($auth->auth['uid'], get_userid($username), true))
    $user_id = get_userid($username);
else
    $user_id = $auth->auth["uid"];

// Get visibility settings from database.
$args['global_visibility'] = get_global_visibility_by_id($user_id);
$args['online_visibility'] = get_local_visibility_by_id($user_id, 'online');
$args['chat_visibility'] = get_local_visibility_by_id($user_id, 'chat');
$args['search_visibility'] = get_local_visibility_by_id($user_id, 'search');
$args['email_visibility'] = get_local_visibility_by_id($user_id, 'email');

// Get default visibility for homepage elements.
$args['default_homepage_visibility'] = get_default_homepage_visibility($user_id);

// Now get elements of user's homepage.
$homepage_elements_unsorted = $my_about->get_homepage_elements();

// Group elements by category.
$args['homepage_elements'] = array();
foreach ($homepage_elements_unsorted as $key => $element) {
    $args['homepage_elements'][$element['category']][$key] = $element;
}

$args['user_id'] = $user_id;
$args['NOT_HIDEABLE_FIELDS'] = $NOT_HIDEABLE_FIELDS;
$args['user_perm'] = $perm->get_perm($user_id);
$args['user_domains'] = UserDomain::getUserDomains();

$args['FOAF_ENABLE'] = $FOAF_ENABLE;
$args['user_cfg'] = UserConfig::get($user_id);

$template->clear_attributes();
echo $template->render($args);

?>

