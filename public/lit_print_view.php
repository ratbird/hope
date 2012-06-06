<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/*
literatur.php - Literaturanzeige von Stud.IP
Copyright (C) 2000 André Noack <anoack@mcis.de>, Cornelis Kater <ckater@gwdg.de>, Stefan Suchi <suchi@gmx.de>

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
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

require_once('lib/classes/StudipLitList.class.php');

PageLayout::removeStylesheet('style.css');
PageLayout::addStylesheet('print.css'); // use special stylesheet for printing
// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
$_range_id = Request::option('_range_id');
if ($_range_id != $user->id && !$perm->have_studip_perm('user',$_range_id)){
    page_close();
    die;
}
$_the_tree = TreeAbstract::GetInstance("StudipLitList", $_range_id);
?>
<h1>
<?=htmlReady(sprintf(_("Literatur %s"), $_the_tree->root_name))?>
</h1>
<?
if ( ($list = StudipLitList::GetFormattedListsByRange($_range_id, false, false)) ){
    echo $list;
} else {
    echo _("Es wurde noch keine Literatur erfasst");
}
echo "<table width=100% border=0 cellpadding=2 cellspacing=0>";
echo "<tr><td><i><font size=-1>" . _("Stand:") . " ".date("d.m.y, G:i",time())."</font></i></td><td align=\"right\"><font size=-2><img src=\"".$GLOBALS['ASSETS_URL']."images/logos/logo2b.gif\"><br>&copy; ".date("Y", time())." v.$SOFTWARE_VERSION&nbsp; &nbsp; </font></td></tr>";
echo "</table>\n";
include ('lib/include/html_end.inc.php');

// Save data back to database.
page_close()

?>
