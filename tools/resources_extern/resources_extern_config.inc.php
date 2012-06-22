<?php
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// resources_extern_config.inc.php
// 
// Copyright (c) 2005 André Noack <noack@data-quest.de> 
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

//uncomment if the used directory is outside of studip-htdocs
//require_once "/<pfad zur phplib>/prepend.php";

//ID of the viewable property, change this to match your database entry
$VIEWABLE_PROPERTY_ID = '539dd9e5bea93208b7e6b5415a01f661';

setLocale(LC_ALL, 'de_DE','german');
$_SESSION['resources_data']["show_repeat_mode"] = 'all';
?>
