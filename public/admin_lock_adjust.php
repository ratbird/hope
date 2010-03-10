<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO

/**
* admin_lock.php - Sichtbarkeits-Administration von Stud.IP.
* Copyright (C) 2002 Cornelis Kater <ckater@gwdg.de>, data-quest <info@data-quest.de>, (C) 2003 Mark Sievers <mark_sievers2000@yahoo.de>
*
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; either version 2
* of the License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", user => "Seminar_User"));
$auth->login_if($auth->auth["uid"] == "nobody");
$perm->check($LOCK_RULE_ADMIN_PERM ? $LOCK_RULE_ADMIN_PERM : 'admin');


include ("lib/seminar_open.php"); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page
require_once("lib/dates.inc.php"); // Funktionen zum Loeschen von Terminen
require_once("lib/datei.inc.php"); // Funktionen zum Loeschen von Dokumenten
require_once("lib/functions.php");
require_once("lib/include/admin_lock_adjust.inc.php");
require_once("lib/visual.inc.php");
require_once("lib/classes/Table.class.php");
require_once("lib/classes/ZebraTable.class.php");


$CURRENT_PAGE = _("Sperrebenen von Veranstaltungen anpassen");
Navigation::activateItem('/admin/config/lock_rules');

// Start of Output
include ("lib/include/html_head.inc.php"); // Output of html head
include ("lib/include/header.php"); // Output of Stud.IP head

$containerTable = new ContainerTable();
echo $containerTable->openRow();
echo $containerTable->openCell(array("colspan"=>"2"));
$contentTable = new ContentTable();
echo $contentTable->openCell();

if ($action=="new") {
    echo show_lock_rule_form($lockdata);
}

else if ($action=="insert") {
    if (strlen($lockdata["name"])==0) {
        echo $contentTable->closeCell();
        echo $contentTable->closeRow();
        parse_msg("error§"._("Bitte geben Sie einen Namen f&uuml;r die Sperrebene an!"), "§", "blank", 1, FALSE );  // parse_msg macht immer ne neue row auf, daher diese eigenartige Benutzung
        echo $contentTable->openRow();
        echo $contentTable->openCell();
        echo show_lock_rule_form($lockdata);
    } else if (get_lock_rule_by_name($lockdata["name"])) {
        echo $contentTable->closeCell();
        echo $contentTable->closeRow();
        parse_msg("error§"._("Der Name ist schon vergeben!"), "§", "blank", 1, FALSE );
        echo $contentTable->openRow();
        echo $contentTable->openCell();
        echo show_lock_rule_form($lockdata);
    } else {
        $insertdata = parse_lockdata($lockdata);        // delete 0-values
        if (insert_lock_rule(remove_magic_quotes($insertdata))) {
            echo $contentTable->closeCell();
            echo $contentTable->closeRow();
            parse_msg("msg§"._("Einf&uuml;gen erfolgreich"), "§", "blank", 1, FALSE );
            echo $contentTable->openRow();
            echo $contentTable->openCell();
            echo show_content();        //show_content zeigt immer die Startseite an
        }
    }
}
else if ($action=="edit") {                             // Zeige Form?
    $lockdata = get_lock_rule($lock_id);
    echo show_lock_rule_form($lockdata, 1);
}
else if ($action=="confirm_edit") {                     // UPDATE!!
    $updatedata = parse_lockdata($lockdata);        // delete 0-values
    if (!update_existing_rule(remove_magic_quotes($updatedata))) {
        echo $contentTable->closeCell();
        echo $contentTable->closeRow();
        parse_msg("error§"._("Die &Auml;nderung ist fehlgeschlagen"), "§", "blank", 1, FALSE );
        echo $contentTable->openRow();
        echo $contentTable->openCell();
        echo show_content();                //s.o.
    } else {
        echo $contentTable->closeCell();
        echo $contentTable->closeRow();
        parse_msg("msg§"._("Die &Auml;nderung war erfolgreich!"), "§", "blank", 1, FALSE );
        echo $contentTable->openRow();
        echo $contentTable->openCell();
        echo show_content();
    }
}
else if ($action=="delete") {
    if (!delete_lock_rule($lock_id)) {
        echo $contentTable->closeCell();
        echo $contentTable->closeRow();
        parse_msg("error§"._("Die Sperrebene konnte nicht gel&ouml;scht werden!"), "§", "blank", 1, FALSE );
        echo $contentTable->openRow();
        echo $contentTable->openCell();
        echo show_content();
    } else {
        echo $contentTable->closeCell();
        echo $contentTable->closeRow();
        parse_msg("msg§"._("Die Sperrebene wurde gel&ouml;scht!"), "§", "blank", 1, FALSE );
        echo $contentTable->openRow();
        echo $contentTable->openCell();
        echo show_content();
    }

}
else if ($action=="confirm_delete") {
    if (!check_empty_lock_rule($lock_id)) {
        echo $contentTable->closeCell();
        echo $contentTable->closeRow();
        parse_msg("info§", "§", "blank", 1, FALSE );
        echo $contentTable->openRow();
        echo $contentTable->openCell();
        echo "<font size=2>"._("Wollen Sie die Sperrebene l&ouml;schen?")."</font>";
        echo "<br><br>";
        echo "<a href=\"".URLHelper::getLink("?lock_id=".$lock_id."&action=delete")."\">".makeButton("ja2","img")."</a>&nbsp;&nbsp;<a href=\"".URLHelper::getLink()."\">".makeButton("nein","img")."</a>";
        echo show_content();
    } else {
        echo $contentTable->closeCell();
        echo $contentTable->closeRow();
        parse_msg("error§"._("Sperrebenen k&ouml;nnen nur gel&ouml;scht werden, wenn sie nicht benutzt werden!"), "§", "blank", 1, FALSE );
        echo $contentTable->openRow();
        echo $contentTable->openCell();
        echo show_content();
    }
}
else {
    echo show_content(); // Uebersicht
}

echo $contentTable->close();
echo $containerTable->close();
include "lib/include/html_end.inc.php";
page_close();
?>
