<?
/**
 * @author Jan-Hendrik Willms <tleilax+studip@gmail.com>
 */

// +---------------------------------------------------------------------------+
// Copyright (C) 2012 Jan-Hendrik Willms <tleilax+studip@gmail.com>
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

// Uses jQuery miniColors colorpicker by Cory LaViska <https://github.com/claviska/jquery-miniColors/>
require './bootstrap.php';

$uri = sprintf('http%s://%s%s%s',
               @$_SERVER['HTTPS'] ? 's' : '',
               $_SERVER['SERVER_NAME'],
               $_SERVER['SERVER_PORT'] != 80 ? ':' . $_SERVER['SERVER_PORT'] : '',
               $_SERVER['SCRIPT_NAME']);
$dispatcher = new Trails_Dispatcher('./app', $uri, 'svg2png');
$dispatcher->dispatch($_SERVER['PATH_INFO'] ?: '/');
