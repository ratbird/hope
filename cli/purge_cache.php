#!/usr/bin/php -q
<?php
/**
* purge_cache.php
* 
* 
* 
*
* @author		André Noack <noack@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access		public
*/
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// purge_cache.php
// 
// Copyright (C) 2010 André Noack <noack@data-quest.de>
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
require_once 'studip_cli_env.inc.php';
require_once 'lib/classes/StudipFileCache.class.php';

$cache = new StudipFileCache();

$cache->purge($_SERVER['argv'][1] === '-q');

exit(1);
