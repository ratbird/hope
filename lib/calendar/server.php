<?php
/**
* server.class.php
* 
* 
* 
*
* @author		Peter Thienel <thienel@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
* @version	$Id: server.php,v 1.1 2008/12/23 19:28:19 thienel Exp $
* @access		public
* @modulegroup	calendar_modules
* @module		calendar
* @package	Calendar
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// server.class.php
// 
// Copyright (C) 2006 Peter Thienel <thienel@data-quest.de>,
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

include_once("$ABSOLUTE_PATH_STUDIP$RELATIVE_PATH_CALENDAR/webservice/CalendarSoapServer.class.php");
	
$server =& new CalendarSoapServer();
$server->start();
?>