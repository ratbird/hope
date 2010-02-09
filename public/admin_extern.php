<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* admin_extern.php
* 
* Extern-admin-pages-mainfile. Calls the submodules.
*
* @author		Peter Thienel <pthienel@data.quest.de>
* @access		public
* @modulegroup	extern_modules
* @module		extern
* @package		Extern
*/
/**
* workaround for PHPDoc
*
* Use this if module contains no elements to document !
* @const PHPDOC_DUMMY
*/
define("PHPDOC_DUMMY",true);
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// admin_extern.php
//
// Copyright (c) 2003 Peter Tienel <pthienel@data-quest.de> 
// Suchi & Berg GmbH <info@data-quest.de>
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


page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth",
		"perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check("admin");

require_once 'lib/functions.php';

$HELP_KEYWORD="Basis.EinrichtungenVerwaltenExterneSeiten";
$CURRENT_PAGE.= _("Verwaltung externer Seiten"); 

if ($EXTERN_ENABLE) {
	include($RELATIVE_PATH_EXTERN . "/admin_extern.inc.php");
} else {
	// Start of Output
	include ('lib/include/html_head.inc.php'); // Output of html head
	include ('lib/include/header.php');   // Output of Stud.IP head
	require_once ('lib/msg.inc.php');
	parse_window ("error§" . _("Die Verwaltung externer Seiten ist nicht eingebunden. Bitte aktivieren Sie diese in den Systemeinstellungen, oder wenden Sie sich an den oder die SystemadministratorIn."), "§",
				_("Modul \"externe Seiten\" nicht eingebunden"));
}
page_close();
?>
