<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// institut_browse.php
//
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

use Studip\Button, Studip\LinkButton;

require '../lib/bootstrap.php';

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check('user');

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

require_once ('lib/visual.inc.php');
require_once ('lib/classes/StudipRangeTreeView.class.php');

PageLayout::setHelpKeyword("Basis.SuchenEinrichtungen");
PageLayout::setTitle(_("Einrichtungssuche"));
Navigation::activateItem('/search/institutes');

// Start of Output
ob_start();

$view = new DbView();
$the_tree = new StudipRangeTreeView();
$the_tree->open_ranges['root'] = true;
if (Request::option('cmd')=="suche"){
    if (Request::get('search_name') && strlen(Request::get('search_name')) > 1){
        $view->params[0] = "%" . Request::quoted('search_name') . "%";
        $rs = $view->get_query("view:TREE_SEARCH_ITEM");
        while($rs->next_record()){
            $found_items[] = htmlReady($the_tree->tree->getItemPath($rs->f("item_id")));
            $the_tree->openItem($rs->f("item_id"));
        }
    }
    if (Request::get('search_user') && strlen(Request::get('search_user')) > 1){
        $view->params[0] = "%" . Request::quoted('search_user') . "%";
        $rs = $view->get_query("view:TREE_SEARCH_USER");
        while($rs->next_record()){
            $found_items[] = htmlReady($the_tree->tree->getItemPath($rs->f("item_id")));
            $the_tree->openItem($rs->f("item_id"));
        }
    }
    if (Request::get('search_sem') && strlen(Request::get('search_sem')) > 1){
        $view->params[0] = "%" . Request::quoted('search_sem') . "%";
        $rs = $view->get_query("view:TREE_SEARCH_SEM");
        while($rs->next_record()){
            $found_items[] = htmlReady($the_tree->tree->getItemPath($rs->f("item_id")));
            $the_tree->openItem($rs->f("item_id"));
        }
    }
    if (count($found_items)){
        $message = MessageBox::info(_('Gefundene Einrichtungen:'), $found_items);
    } else {
        $message = MessageBox::info(_('Es konnte keine Einrichtung gefunden werden, die Ihrer Suchanfrage entspricht.'));
    }
    PageLayout::postMessage($message);
}
?>
<h1><?= _('Suche nach Einrichtungen') ?></h1>
<?

if ($msg)   {
    echo "\n<table width=\"99%\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">";
    parse_msg ($msg,"§","blank",1,false);
    echo "\n</table>";
}
$the_tree->showTree();

$sidebar = Sidebar::get();
$sidebar->setImage('sidebar/institute-sidebar.png');

$search = new SearchWidget('?cmd=suche');
$search->addNeedle(_('Name der Einrichtung'), 'search_name');
$search->addNeedle(_('Einrichtung dieses Mitarbeiters'), 'search_user');
$search->addNeedle(_('Einrichtung dieser Veranstaltung'), 'search_sem');
$sidebar->addWidget($search);

$template = $GLOBALS['template_factory']->open('layouts/base.php');
$template->content_for_layout = ob_get_clean();
echo $template->render();

page_close();
