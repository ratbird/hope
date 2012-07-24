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

unregister_globals();
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check('user');

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

require_once ('lib/visual.inc.php');
require_once ('lib/classes/StudipRangeTreeView.class.php');

PageLayout::setHelpKeyword("Basis.SuchenEinrichtungen");
PageLayout::setTitle(_("Einrichtungssuche"));
Navigation::activateItem('/search/institutes');

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head

$view = new DbView();
$the_tree = new StudipRangeTreeView();
$the_tree->open_ranges['root'] = true;
if (Request::option('cmd')=="suche"){
    if (Request::quoted('search_name') && strlen(Request::quoted('search_name')) > 1){
        $view->params[0] = "%" . Request::quoted('search_name') . "%";
        $rs = $view->get_query("view:TREE_SEARCH_ITEM");
        while($rs->next_record()){
            $found_items[] = htmlReady($the_tree->tree->getItemPath($rs->f("item_id")));
            $the_tree->openItem($rs->f("item_id"));
        }
    }
    if (Request::quoted('search_user') && strlen(Request::quoted('search_user')) > 1){
        $view->params[0] = "%" . Request::quoted('search_user') . "%";
        $rs = $view->get_query("view:TREE_SEARCH_USER");
        while($rs->next_record()){
            $found_items[] = htmlReady($the_tree->tree->getItemPath($rs->f("item_id")));
            $the_tree->openItem($rs->f("item_id"));
        }
    }
    if (Request::quoted('search_sem') && strlen(Request::quoted('search_sem')) > 1){
        $view->params[0] = "%" . Request::quoted('search_sem') . "%";
        $rs = $view->get_query("view:TREE_SEARCH_SEM");
        while($rs->next_record()){
            $found_items[] = htmlReady($the_tree->tree->getItemPath($rs->f("item_id")));
            $the_tree->openItem($rs->f("item_id"));
        }
    }
    if (count($found_items)){
        $msg = "info§" . _("Gefundene Einrichtungen:"). "<div style=\"font-size:10pt;\">" . join("<br>",$found_items) ."</div>§";
    } else {
        $msg = "info§" . _("Es konnte keine Einrichtung gefunden werden, die Ihrer Suchanfrage entspricht."). "§";
    }
}
?>
<table width="100%" border="0" cellpadding="2" cellspacing="0">
    <tr>
    <td class="blank" align="left" valign="top">
    <h1><?= _('Suche nach Einrichtungen') ?></h1>
    <?
if ($msg)   {
    echo "\n<table width=\"99%\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">";
    parse_msg ($msg,"§","blank",1,false);
    echo "\n</table>";
}
$the_tree->showTree();
    ?>
    <br>
    </td>
    <td class="blank" align="right" valign="top" width="270">
    <?
$infobox = array(array("kategorie"  => _("Information:"),
                        "eintrag" => array(array("icon" => "icons/16/black/info.png",
                                                "text"  => _("Sie k&ouml;nnen sich durch den Einrichtungsbaum klicken oder das Suchformular benutzen"))
                                        )
                        )
                );
$such_form = "<form action=\"".URLHelper::getLink("?cmd=suche")."\" method=\"post\">" . _("Bitte geben Sie hier Ihre Suchkriterien ein:") . "<br>"
            . CSRFProtection::tokenTag()
            . _("Name der Einrichtung:") . "<br>"
            . "<input type=\"text\" name=\"search_name\" style=\"width:95%;\"><br>"
            . _("Einrichtung dieses Mitarbeiters:") . "<br>"
            . "<input type=\"text\" name=\"search_user\" style=\"width:95%;\"><br>"
            . _("Einrichtung dieser Veranstaltung:") . "<br>"
            . "<input type=\"text\" name=\"search_sem\" style=\"width:95%;\">"
            . "<div align=\"right\" style=\"width:95%;\">". Button::create(_('Suchen'), array('title' => _("Suche starten")))
            . "</div></form>";
$infobox[1]["kategorie"] = _("Suchen:");
$infobox[1]["eintrag"][] = array (  "icon" => "icons/16/black/search.png" ,
                                    "text" => $such_form
                                );
print_infobox($infobox, "infobox/institutes.jpg");
?>
</td></tr>
</table>
<?
include ('lib/include/html_end.inc.php');
page_close();
?>
