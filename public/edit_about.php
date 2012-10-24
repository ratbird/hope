<?php
# Lifter002: TODO
# Lifter005: TODO - form validation
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// edit_about.php
// administration of personal home page
//
// Copyright (C) 2000 Ralf Stockmann <rstockm@gwdg.de>, Stefan Suchi <suchi@gmx.de>,
// Niklas Nohlen <nnohlen@gwdg.de>, Miro Freitag <mfreita@goe.net>, André Noack <andre.noack@gmx.net>
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
 * @addtogroup notifications
 *
 * Uploading a new avatar triggers a AvatarDidUpload notification. The
 * user's ID is transmitted as subject of the notification.
 */


use Studip\Button, Studip\LinkButton;
require '../lib/bootstrap.php';

unregister_globals();
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Default_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$auth->login_if($auth->auth["uid"] == "nobody");

require_once('config.inc.php');
require_once('lib/msg.inc.php');
require_once('lib/visual.inc.php');
require_once 'lib/functions.php';
require_once('lib/language.inc.php');
require_once('lib/classes/DataFieldEntry.class.php');
require_once('lib/log_events.inc.php');
require_once('lib/edit_about.inc.php');

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

URLHelper::addLinkParam('admin_view', Request::option('admin_view'));
$view = Request::option('view');

checkExternDefaultForUser(get_userid($username));

/* * * * * * * * * * * * * * * *
 * * * * * * V I E W * * * * * *
 * * * * * * * * * * * * * * * */

switch($view) {
    case "calendar":
        PageLayout::setHelpKeyword("Basis.MyStudIPTerminkalender");
        PageLayout::setTitle(_("Einstellungen des Terminkalenders anpassen"));
        Navigation::activateItem('/links/settings/calendar');
        PageLayout::setTabNavigation('/links/settings');
        SkipLinks::addIndex(_("Einstellungen des Terminkalenders anpassen"), 'main_content', 100);
        break;
    default:
        PageLayout::setHelpKeyword("Basis.MyStudIP");
        break;
}

if (!$cmd) {
 // darfst du ändern?? evtl erst ab autor ?
    $perm->check("user");

    // Ab hier die Views der MyStudip-Sektion
    if($view == 'calendar' && get_config('CALENDAR_ENABLE')) {
        ob_start();

        require_once($GLOBALS['RELATIVE_PATH_CALENDAR'].'/calendar_settings.inc.php');

        $template = $GLOBALS['template_factory']->open('layouts/base_without_infobox');
        $template->content_for_layout = ob_get_clean();
        echo $template->render();
    }
}

page_close();
