<?
/**
* header.inc.php
* 
* 
*
* @author		Peter Thienel <pthienel@web.de>
* @version		$Id: header.inc.php,v 1.1 2008/12/23 09:53:52 thienel Exp $
* @access		public
* @modulegroup	calendar
* @module		calendar
* @package	calendar
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
// header.inc.php
//
// Copyright (c) 2003 Peter Tienel <pthienel@web.de> 
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


echo "<html>\n<head>\n<title>Stud.IP</title>\n";
echo "<link rel=\"stylesheet\" href=\"style.css\" type=\"text/css\">";
if (isset($FAVICON))
		printf("<link rel=\"SHORTCUT ICON\" href=\"%s\">", $FAVICON);
echo "</head>\n<body bgcolor=\"#FFFFFF\">\n";

if($cmd == 'showmonth'){
	echo '<div ID="overDiv" STYLE="position:absolute; visibility:hidden;z-index:1000;"></div>';
	echo "<script language=\"JavaScript\" src=\"overlib.js\"></script>\n";
}
	
require("header.php");

?>