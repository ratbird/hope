<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
about.php - Anzeige der persoenlichen Userseiten von Stud.IP
Copyright (C) 2000 Ralf Stockmann <rstockm@gwdg.de>, Stefan Suchi <suchi@gmx.de>, Niclas Nohlen <nnohlen@gwdg.de>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/


require '../lib/bootstrap.php';

unregister_globals();

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Default_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$auth->login_if(Request::quoted('again') && ($auth->auth["uid"] == "nobody"));
$perm->check("user");



include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- hier muessen Seiten-Initialisierungen passieren --

require_once 'lib/functions.php';
require_once('config.inc.php');
require_once('lib/dates.inc.php');
require_once('lib/messaging.inc.php');
require_once('lib/msg.inc.php');
require_once('lib/statusgruppe.inc.php');
require_once('lib/showNews.inc.php');
require_once('lib/show_dates.inc.php');
require_once('lib/classes/DbView.class.php');
require_once('lib/classes/DbSnapshot.class.php');
require_once('lib/classes/DataFieldEntry.class.php');
require_once('lib/classes/guestbook.class.php');
require_once('lib/object.inc.php');
require_once('lib/classes/score.class.php');
require_once('lib/classes/SemesterData.class.php');
require_once('lib/user_visible.inc.php');
require_once('lib/classes/StudipLitList.class.php');
require_once('lib/classes/Avatar.class.php');
require_once('lib/classes/StudipKing.class.php');


header('Location:'.URLHelper::getLink('dispatch.php/profile', array('username' => Request::get('username'))));
