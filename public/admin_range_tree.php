<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* Frontend
* 
* 
*
* @author       André Noack <andre.noack@data.quest.de>
* @access       public
* @modulegroup  admin_modules
* @module       admin_range_tree
* @package      Admin
*/
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// admin_range_tree.php
//
// Copyright (c) 2002 André Noack <noack@data-quest.de> 
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


require '../lib/bootstrap.php';

unregister_globals();

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check($RANGE_TREE_ADMIN_PERM ? $RANGE_TREE_ADMIN_PERM  : 'admin');
        
require_once('lib/visual.inc.php');
require_once('lib/classes/StudipRangeTreeViewAdmin.class.php');

include('lib/seminar_open.php'); //hier werden die sessions initialisiert

PageLayout::setTitle($UNI_NAME_CLEAN . " - " . _("Einrichtungshierarchie bearbeiten"));
Navigation::activateItem('/admin/config/range_tree');

include('lib/include/html_head.inc.php');
include('lib/include/header.php');   //hier wird der "Kopf" nachgeladen 

?>
<table class="blank" cellspacing="0" cellpadding="2" border="0" width="100%">
    <tr><td  align="center" class="blank"><br>
    <table class="blank" cellspacing="0" cellpadding="0" border="0" width="99%">
    <tr>
    <td align="center" class="blank">
<?
$the_tree = new StudipRangeTreeViewAdmin();
$the_tree->open_ranges['root'] = true;

$the_tree->showTree();

echo '</td></tr></table><br></td></tr></table>';
include ('lib/include/html_end.inc.php');
page_close();

?>
