<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* resources.php
* 
* The startscript for the resources module
*
* @author		Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access		public
* @package		resources
* @modulegroup	resources_modules
* @module		resources.php
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// resources.php
// Steuerung fuer Ressourcenverwaltung von Stud.IP
// Copyright (C) 2002 Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>, 
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

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$auth->login_if($auth->auth['uid'] == 'nobody');

$perm->check("autor");

include ('lib/seminar_open.php'); // initialise Stud.IP-Session
$HELP_KEYWORD="Basis.Ressourcen";// META:in resourcesControl.inc.php verlagern,wenn detaillierter vorhanden

if ($RESOURCES_ENABLE) {
	//Steuerung der Ressourcenverwaltung einbinden
	include ("$RELATIVE_PATH_RESOURCES/resourcesControl.inc.php");
} else {
	// Start of Output
	include ('lib/include/html_head.inc.php'); // Output of html head
	include ('lib/include/header.php');   // Output of Stud.IP head
	require_once ('lib/msg.inc.php');
	parse_window ("error§" . _("Die Ressurcenverwaltung ist nicht eingebunden. Bitte aktivieren Sie sie in den Systemeinstellungen oder wenden Sie sich an die Systemadministratoren."), "§",
				_("Ressourcenverwaltung nicht eingebunden"));
}
?>
