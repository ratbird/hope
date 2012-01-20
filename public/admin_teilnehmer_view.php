<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
teilnehmer.php - Anzeige der Teilnehmer eines Seminares
Copyright (C) 2000 Stefan Suchi <suchi@gmx.de>, Ralf Stockmann <rstockm@gwdg.de>

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

use Studip\Button, Studip\LinkButton;

require '../lib/bootstrap.php';

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));

include "lib/seminar_open.php"; //hier werden die sessions initialisiert

require_once ("lib/msg.inc.php");
require_once ("lib/visual.inc.php");
require_once ("lib/functions.php");
require_once ("lib/admission.inc.php"); //Funktionen der Teilnehmerbegrenzung
require_once ("lib/statusgruppe.inc.php");  //Funktionen der Statusgruppen
require_once ("lib/messaging.inc.php"); //Funktionen des Nachrichtensystems
require_once ("config/config.inc.php");     //We need the config for some parameters of the class of the Veranstaltung
require_once ("lib/classes/Table.class.php");
require_once ("lib/classes/ZebraTable.class.php");

PageLayout::setTitle(_("Teilnehmeransicht konfigurieren"));
Navigation::activateItem('/admin/config/member_view');

// Start  of Output
include ("lib/include/html_head.inc.php"); // Output of html head
include ("lib/include/header.php");   //hier wird der "Kopf" nachgeladen

$cssSw=new cssClassSwitcher;

// Aenderungen nur in dem Seminar, in dem ich gerade bin...

$db=new DB_Seminar;

$id = "";
if (!($auth->auth["perm"] == "root")) die;

if ($cmd == "change") {

    foreach ($_REQUEST as $key => $val) {
        if ($key[0] == "#") {
            $zw = substr($key, 1, strlen($key));
            $zw2 = explode("##", $zw);
            if ($val == 1) {
                $db->query("REPLACE INTO teilnehmer_view (datafield_id, seminar_id) VALUES ('$zw2[0]', '$zw2[1]')");
            } else {
                $db->query("DELETE FROM teilnehmer_view WHERE datafield_id = '$zw2[0]' AND seminar_id = '$zw2[1]'");
            }
        }
    }
}

$tbl_blank = array("class" => "blank", "colspan" => "2");

$table = new ContainerTable(array("cellspacing" => 0, "border" => "0", "width" => "100%", "cellpadding" => "0"));
$tbl2 = new ZebraTable(array("width" => "99%", "align" => "center"));

// Titelleiste und Leere Zeile
echo $table->headerRow("&nbsp;<b>". _("Teilnehmeransicht konfigurieren")."</b>", array("colspan" => "3"));
echo $table->openRow();
//echo $table->cell("&nbsp;", array("class" => "blank"));
echo $table->openCell();

// Daten
echo $tbl2->open();

$query = "SELECT * FROM teilnehmer_view WHERE ";

for ($i = 1; $i <= sizeof($SEM_CLASS); $i++) {
    if ($i != 1) $query .= "OR ";
    $query .= "seminar_id = '$i' ";
}

$db->query($query);

$active = array();
while ($db->next_record()) {
    $active[$db->f("seminar_id")][$db->f("datafield_id")] = TRUE;
}
echo "<form action=\"". URLHelper::getLink() ."\" method=\"post\">";
echo CSRFProtection::tokenTag();
foreach ($SEM_CLASS as $key => $val) {
    echo $tbl2->headerRow(array("&nbsp;<b>". $val["name"]."</b>", "<b>Status</b>", "<b>Anzeige</b>"));
    echo $tbl2->closeRow();
    foreach ($TEILNEHMER_VIEW as $data) {
        echo $tbl2->openRow();
        echo $tbl2->cell($data["name"], array("width" => "50%"));
        echo $tbl2->cell(($active[$key][$data["field"]] ? "<font color=\"green\">". _("Anzeigen erlaubt"). "</font>" : "<font color=\"red\">". _("Anzeigen nicht erlaubt"). "</font>"), array("width" => "25%"));
        echo $tbl2->cell(sprintf("<input type=\"radio\" name=\"#".$data["field"]."##".$key."\" value=\"1\" %s>". _("erlaubt")."<input type=\"radio\" name=\"#".$data["field"]."##".$key."\" value=\"0\" %s>". _("nicht erlaubt"), ($active[$key][$data["field"]]) ? "checked" : "",($active[$key][$data["field"]]) ? "" : "checked"));
        echo $tbl2->closeRow();
    }
    echo "<input type=\"hidden\" name=\"cmd\" value=\"change\">";
    echo $table->openRow();
    echo $table->cell("&nbsp;",array("colspan" => "2"));
    echo $table->cell(Button::create(_('zuweisen')));
    echo $table->closeRow();
    echo $table->blankRow(array("colspan" => "3"));
}
echo "</form>";
echo $tbl2->close();

// Abschluss für unten (Leere Zeile)
echo $table->closeCell();
echo $table->cell("&nbsp;", array("class" => "blank"));
echo $table->closeRow();
echo $table->blankCell($tbl_blank);
// Alles schließen
echo $table->close();

// Save data back to database.
page_close();
?>
</body>
</html>
