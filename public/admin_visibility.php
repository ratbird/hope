<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* admin_visibility.php - Sichtbarkeits-Administration von Stud.IP.
* Copyright (C) 2002 Cornelis Kater <ckater@gwdg.de>, data-quest <info@data-quest.de>, (C) 2003 Tobias Thelen <tthelen@uni-osnabrueck.de>
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
require_once('lib/dates.inc.php'); // Funktionen zum Loeschen von Terminen
require_once('lib/datei.inc.php'); // Funktionen zum Loeschen von Dokumenten
require_once 'lib/functions.php';
require_once('lib/visual.inc.php');
require_once('lib/log_events.inc.php');
require_once('lib/classes/Table.class.php');
require_once('lib/classes/ZebraTable.class.php');

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", 'user' => "Seminar_User"));
$auth->login_if($auth->auth["uid"] == "nobody");

$needed_perm = (get_config('ALLOW_DOZENT_VISIBILITY') ? 'dozent' : 'admin');

$perm->check($needed_perm);

include ('lib/seminar_open.php'); // initialise Stud.IP-Session
require_once 'lib/admin_search.inc.php';

PageLayout::setTitle(_("Verwaltung der Sichtbarkeit von Veranstaltungen"));

if ($perm->have_perm('admin')) {
    Navigation::activateItem('/admin/course/visibility');
} else {
    Navigation::activateItem('/course/admin/main/visibility');
}

//get ID from a open Seminar
if ($SessSemName[1])
    $header_object_id = $SessSemName[1];
else
    $header_object_id = $admin_admission_data["sem_id"];

//Change header_line if open object
$header_line = getHeaderLine($header_object_id);
if ($header_object_id)
    PageLayout::setTitle($header_line." - ".PageLayout::getTitle());

//Output starts here

include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   //hier wird der "Kopf" nachgeladen
include 'lib/include/admin_search_form.inc.php';

function visibility_change_message($old_vis, $new_vis) {
    if ($old_vis) {
        if ($new_vis) {
            return _("ist weiterhin sichtbar");
        } else {
            return _("wurde versteckt");
        }
    } else {
        if ($new_vis) {
            return _("wurde sichtbar gemacht");
        } else {
            return _("ist weiterhin versteckt");
        }
    }
}

$sems=array();
// single delete (a Veranstaltung is open)
if ($SessSemName[1] && (!Request::int('change_visible'))) {
    $visibility_sem[] = "_id_" . $SessSemName[1];
    $visibility_sem[] = "on";
    $single=true;
} 

// Handlings....
// A list was sent
$visibility_sem = Request::optionArray('visibility_sem');
if (Request::optionArray('visibility_sem')) {
    foreach($visibility_sem as $key => $val) {
        if ((substr($val, 0, 4) == "_id_") && (substr($visibility_sem[$key + 1], 0, 4) != "_id_"))
                if ($visibility_sem[$key + 1] == "on") {
                    $sems[] = array("id" => substr($val, 4, strlen($val)), "visible" => 1);
                } else { 
                    $sems[] = array("id" => substr($val, 4, strlen($val)), "visible" => 0);
            } 
        } 
}

// Prepare visibility statement
$visibility_query     = "SELECT VeranstaltungsNummer, Name, visible FROM seminare WHERE Seminar_id = ?";
$visibility_statement = DBManager::get()->prepare($visibility_query);

//echo "<body>";
$containerTable=new ContainerTable();
echo $containerTable->openRow();
echo $containerTable->openCell(array("colspan"=>"2"));

$contentTable=new ContentTable();
echo $contentTable->openCell();
$zt=new ZebraTable(array("width"=>"100%", "padding"=>"5"));
//echo $zt->open();
echo $zt->openHeaderRow();
echo $zt->cell("<b>"._("Nr.")."</b>",array("width"=>"5%"));
echo $zt->cell("<b>"._("Name")."</b>",array("width"=>"75%"));
echo $zt->cell("<b>"._("Sichtbarkeit")."</b>",array("width"=>"20%"));
echo $zt->closeRow();

if ($SessSemName[1] && (!Request::int('change_visible'))) {
    $visibility_statement->execute(array($SessSemName[1]));
    $temp = $visibility_statement->fetch(PDO::FETCH_ASSOC);
    $visibility_statement->closeCursor();
    
    if ($temp) {
        if(!LockRules::Check($SessSemName[1], 'seminar_visibility')) {
            $form   =   "<form name=\"asd\" action=\"". URLHelper::getLink() ."\" method=\"POST\">";
            $form   .= CSRFProtection::tokenTag();
            $form   .=  "<input type=\"checkbox\" name=\"visibility_sem[".$SessSemName[1]."]\"";
            if ($temp['visible']) {
                $form .= " checked ";
            }
            $form   .=  ">";
            $form   .=  "<input type=\"hidden\" name=\"all_sem[]\" value=".$SessSemName[1].">";
            $form   .=  "<input type=\"hidden\" name=\"change_visible\" value=\"1\">";
            $form   .=  Button::create(_('Zuweisen'));
            $form   .=  "</form>";
        } else {
            $form = $temp['visible'] ? _("sichtbar") : _("versteckt");
        }
        echo $zt->row(array(htmlready($temp['VeranstaltungsNummer']), htmlready($temp['Name']), $form));
    }

} else {
    $all_sem = Request::optionArray('all_sem');
    $update_query     = "UPDATE seminare SET visible = ? WHERE Seminar_id = ?";
    $update_statement = DBManager::get()->prepare($update_query);

    for ($i=0;$i<count($all_sem);$i++) {
        $visible=false;
        
        $visibility_statement->execute(array($all_sem[$i]));
        $temp = $visibility_statement->fetch(PDO::FETCH_ASSOC);
        $visibility_statement->closeCursor();
        
        if ($temp) {
            if (is_array($visibility_sem)) {
                reset($visibility_sem);
                while (list($key, $val)=each($visibility_sem)) {
                    if (($all_sem[$i]==$key) && $val=="on") {
                        $visible = true;
                    }
                }
            }
            if(!LockRules::Check($all_sem[$i], 'seminar_visibility')) {
                if ($visible && $temp['visible'] != 1) {
                    echo $zt->row(array(htmlready($temp['VeranstaltungsNummer']), htmlready($temp['Name']), visibility_change_message($temp['visible'], 1)));
                    $update_statement->execute(array(1, $all_sem[$i]));
                    log_event("SEM_VISIBLE",$all_sem[$i]);
                } else if ($visible && $temp['visible'] == 1) {
                    echo $zt->row(array(htmlready($temp['VeranstaltungsNummer']), htmlready($temp['Name']), visibility_change_message($temp['visible'], 1)));
                } else if (!$visible && $temp['visible'] != 0) {
                    $update_statement->execute(array(0, $all_sem[$i]));
                    log_event("SEM_INVISIBLE",$all_sem[$i]);
                     echo $zt->row(array(htmlready($temp['VeranstaltungsNummer']), htmlready($temp['Name']), visibility_change_message($temp['visible'], 0)));
                } else {
                    echo $zt->row(array(htmlready($temp['VeranstaltungsNummer']), htmlready($temp['Name']), visibility_change_message($temp['visible'], 0)));
                }
                $visible = false;
            }
        } else {
            // TODO This will not have the expected output since $temp['Name'] will always be undefined
            echo $zt->row(array("&nbsp;", $temp['Name'], "<font color=red>". _("Änderung fehlgeschlagen") . "</font>"));
        }
    }
}

echo $zt->close();

echo $contentTable->close();

echo $containerTable->blankRow();
echo $containerTable->close();
include ('lib/include/html_end.inc.php');
page_close();
