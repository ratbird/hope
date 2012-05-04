<?
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO
/**
* admin_aux.php - Zusatzangaben-Administration von Stud.IP.
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

use Studip\Button, Studip\LinkButton; 

require '../lib/bootstrap.php';

unregister_globals();
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", 'user' => "Seminar_User"));
$auth->login_if($auth->auth["uid"] == "nobody");
$perm->check("dozent");
include ("lib/seminar_open.php"); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page
require_once("lib/dates.inc.php"); // Funktionen zum Loeschen von Terminen
require_once("lib/datei.inc.php"); // Funktionen zum Loeschen von Dokumenten
require_once("lib/functions.php");
require_once("lib/visual.inc.php");
require_once("lib/classes/Table.class.php");
require_once("lib/classes/ZebraTable.class.php");
require_once("lib/classes/AuxLockRules.class.php");
require_once 'lib/admin_search.inc.php';

if (Request::submitted('aux_rule')) {
    $list=TRUE;
    $message = 'info§' . _("Diese Daten sind noch nicht gespeichert.");
}

PageLayout::setTitle(_("Verwaltung der Zusatzangaben von Veranstaltungen"));

if ($perm->have_perm('admin')) {
    Navigation::activateItem('/admin/course/aux_data');
} else {
    Navigation::activateItem('/course/admin/aux_data');
}

//get ID from a open Seminar
if ($SessSemName[1])
    $header_object_id = $SessSemName[1];

//Change header_line if open object
$header_line = getHeaderLine($header_object_id);
if ($header_object_id)
    PageLayout::setTitle($header_line." - ".PageLayout::getTitle());

//Output starts here

include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   //hier wird der "Kopf" nachgeladen
include 'lib/include/admin_search_form.inc.php';

// Prepare aux statement
$aux_query     = "SELECT aux_lock_rule, Name, Veranstaltungsnummer FROM seminare WHERE Seminar_id = ?";
$aux_statement = DBManager::get()->prepare($aux_query);

if (isset($SessSemName[1]) && (!Request::option('make_aux'))) {
    $aux_statement->execute(array($SessSemName[1]));
    $seminar_data = $aux_statement->fetch(PDO::FETCH_ASSOC);
    $aux_statement->closeCursor();

    $aux_sem[$SessSemName[1]] = $seminar_data['aux_lock_rule'];
    $selected = 1;
    //echo $db7->f("aux_lock_rule");
}

// Get a database connection
$rules = AuxLockRules::getAllLockRules();
//echo "<body>";
$containerTable=new ContainerTable();
echo $containerTable->openRow();
echo $containerTable->openCell(array("colspan"=>"2"));

$contentTable=new ContentTable();
echo $contentTable->openCell();
$zt=new ZebraTable(array("width"=>"100%", "padding"=>"5"));
echo $zt->openHeaderRow();
echo $zt->cell("<b>"._("Nr.")."</b>",array("width"=>"5%"));
echo $zt->cell("<b>"._("Name")."</b>",array("width"=>"75%"));
echo $zt->cell("<b>"._("Template")."</b>",array("width"=>"20%"));
echo $zt->closeRow();

// a Seminar is selected!
if (isset($SessSemName[1]) && isset($selected)) {
    $form    =  "<form name=\"\" action=\"". URLHelper::getLink() ."\" method=\"post\">";
    $form   .= CSRFProtection::tokenTag();
    $form   .=  "<input type=\"hidden\" name=\"make_aux\" value=1>";
    $form .=    "<select name=aux_sem[".$SessSemName[1]."]>";
    $form .= "<option value=\"null\">-- ". _("keine Zusatzangaben"). " --</option>";
    if(is_array($rules)){
        foreach ($rules as $id => $rule) {
            $form .= '<option value="'.$id.'"';
            if ($id == $seminar_data['aux_lock_rule']) {
                $form .= " selected ";
            }
            $form .= ">".htmlReady($rule["name"])."</option>";
        }
    }
    $form   .=  "</select>";
    $form   .=  "<input type=\"hidden\" name=\"aux_all\" value=\"-1\">";
    $form   .=  Button::create(_('Zuweisen'));
    $form   .=  "</form>";
    echo $zt->row(array(htmlReady($seminar_data['Veranstaltungsnummer']), htmlReady($seminar_data['Name']), $form));

}

if (!Request::submitted('aux_rule') && Request::optionArray('aux_sem') && (!$selected)) {
    $update_query     = "UPDATE seminare SET aux_lock_rule = ? WHERE Seminar_id = ?";
    $update_statement = DBManager::get()->prepare($update_query);
    
    foreach (Request::optionArray('aux_sem') as $key => $val) {
        $aux_statement->execute(array($key));
        $aux_data = $aux_statement->fetch(PDO::FETCH_ASSOC);
        $aux_statement->closeCursor();
        
        if ($aux_data) {
            $rule = AuxLockRules::getLockRuleById($val);
            if (!$rule['name']) {
                $rule['name'] = '-- ' . _('keine Zusatzangaben') . ' --';
            }
            echo $zt->row(array(htmlReady($aux_data['Veranstaltungsnummer']), htmlReady($aux_data['Name']), htmlReady($rule["name"])));
            if (Request::option('make_aux')) {
                $update_statement->execute(array($val == 'null' ? null : $val, $key));
            }
        } else {
            echo $zt->row(array("&nbsp;", $db->f("Name"), "<font color=red>". _("Änderung fehlgeschlagen") . "</font>"));
        }
    }
}
echo $zt->close();
echo $contentTable->close();

echo $containerTable->blankRow();
echo $containerTable->close();

    include 'lib/include/html_end.inc.php';
    page_close();
?>
