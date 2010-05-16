<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* mein_stundenplan.php
*
* view of personal timetable
*
*
* @author       Cornelis Kater <ckater@gwdg.de> Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @package      studip_core
* @modulegroup  views
* @module       mein_stundenplan.php
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// mein_stundenplan.php - Persoenliche Stundenplanansicht in Stud.IP.
// Copyright (C) 2001-2002 Cornelis Kater <ckater@gwdg.de> Suchi & Berg GmbH <info@data-quest.de>
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

page_open(array("sess" => "Seminar_Session",
                "auth" => "Seminar_Auth",
                "perm" => "Seminar_Perm",
                "user" => "Seminar_User"));

ob_start(); //Outputbuffering for max performance


include('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page
$view = 'standard';
if (($_REQUEST['view'] == 'print') || ($_REQUEST['view'] == 'edit')) {
    $view = $_REQUEST['view'];
}

if ($view == 'print') {
    $_include_stylesheet = "style_print.css"; // use special stylesheet for printing
}

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head

require_once 'config.inc.php'; //Daten laden
require_once 'config_tools_semester.inc.php';
require_once 'lib/include/ms_stundenplan.inc.php';
require_once 'lib/visual.inc.php';
require_once 'lib/classes/SemesterData.class.php';
require_once 'lib/raumzeit/CycleDataDB.class.php';

if ($RESOURCES_ENABLE)
    require_once ($RELATIVE_PATH_RESOURCES.'/resourcesFunc.inc.php');

$DAY_I2S = array(1 => 'mo',
                 2 => 'di',
                 3 => 'mi',
                 4 => 'do',
                 5 => 'fr',
                 6 => 'sa',
                 7 => 'so');

//eingebundene Daten auf Konsitenz testen (Semesterwechsel? nicht mehr Admin im gespeicherten Institut?)
check_schedule_settings();

if (!$inst_id) {
    $HELP_KEYWORD="Basis.MyStudIPStundenplan";
    $CURRENT_PAGE = _("Mein Stundenplan");
} else {
    $HELP_KEYWORD="Basis.TerminkalenderStundenplan";
    $CURRENT_PAGE = $SessSemName["header_line"]." - "._("Veranstaltungs-Timetable");
}

if ($view != 'print') {
    if ($inst_id) //Links if we show in the instiute-object-view
        Navigation::activateItem('/course/main/schedule');
    else if (!$perm->have_perm("admin")) //if not in the adminview, it's the user view!
        Navigation::activateItem('/calendar/schedule');
    else
        Navigation::activateItem('/browse/my_courses/schedule');

    include 'lib/include/header.php';   //hier wird der "Kopf" nachgeladen
}

$db = new DB_Seminar;
$db2 = new DB_Seminar;
$semester = new SemesterData;
$hash_secret = "machomania";

$all_semester = $semester->getAllSemesterData();
//Wert fuer colspan Ausrechnen
$glb_colspan = 0;

if($view != 'edit' && !$_REQUEST['inst_id']) {
    foreach($my_schedule_settings["glb_days"] as $tmp) {
        if ($tmp){
            $glb_colspan++;
        }
    }
}else {
    $glb_colspan = 7;
}

// Hat man sich inzwischen fest eingetragen, Eintrag aus dem virtuellen Stundenplan löschen
$db->query("SELECT * FROM seminar_user_schedule a, seminar_user b WHERE a.range_id = b.Seminar_id AND a.user_id = b.user_id AND a.user_id = '".$auth->auth['uid']."'");
while ($db->next_record()) {
    $db2->query("DELETE FROM seminar_user_schedule WHERE range_id = '".$db->f('Seminar_id')."' AND user_id = '".$db->f('user_id')."'");
}

// Virtuellen Stundenplaneintrag erstellen
if ($cmd == "add_entry") {
    $db->query("INSERT INTO seminar_user_schedule SET range_id = '$semid', user_id = '".$auth->auth['uid']."'");
}

// Virtuellen Stundenplaneintrag löschen
if ($cmd == "delete_entry") {
    // seminar id auf 32 zeichen kürzen
    $sem_id = substr($_REQUEST['sem_id'], 0, 32);
    $db->query("DELETE FROM seminar_user_schedule WHERE range_id = '$sem_id' AND user_id = '".$auth->auth['uid']."'");
}

// persoenlichen Eintrag wegloeschen
if ($cmd == "delete") {
    unset($my_personal_sems[$sem_id]);
}

// hide entry
if ($cmd == "hide") {
    if(!$my_schedule_settings['hidden']) {
        $my_schedule_settings['hidden'] = array();
    }

    $my_schedule_settings['hidden'][$sem_id] = True;
}

// show previously hidden entry
if ($cmd == "show") {
    if($my_schedule_settings['hidden'][$sem_id]){
        unset($my_schedule_settings['hidden'][$sem_id]);
    }
}

//ein weiterer persoenlicher Eintrag wurde uebermittelt
if ($cmd=="insert") {
    switch ($tag) {
        // nicht wundern, wir nehmen hier irgendwelche Tage, von denen wir
        // wissen, was das fuer ein Wochentag war, um den Wochentag zu fixieren
        // (dieser Programmteil entstand 03/2001... *G)
        case 1: {
            $start_time = mktime($start_stunde,$start_minute,0,3,26,2001);
            $ende_time = mktime($ende_stunde,$ende_minute,0,3,26,2001);
            break;
            }
        case 2: {
            $start_time = mktime($start_stunde,$start_minute,0,3,27,2001);
            $ende_time = mktime($ende_stunde,$ende_minute,0,3,27,2001);
            break;
            }
        case 3: {
            $start_time = mktime($start_stunde,$start_minute,0,3,28,2001);
            $ende_time = mktime($ende_stunde,$ende_minute,0,3,28,2001);
            break;
            }
        case 4: {
            $start_time = mktime($start_stunde,$start_minute,0,3,29,2001);
            $ende_time = mktime($ende_stunde,$ende_minute,0,3,29,2001);
            break;
            }
        case 5: {
            $start_time = mktime($start_stunde,$start_minute,0,3,30,2001);
            $ende_time = mktime($ende_stunde,$ende_minute,0,3,30,2001);
            break;
            }
        case 6: {
            $start_time = mktime($start_stunde,$start_minute,0,3,31,2001);
            $ende_time = mktime($ende_stunde,$ende_minute,0,3,31,2001);
            break;
            }
        case 7: {
            $start_time = mktime($start_stunde,$start_minute,0,4,1,2001);
            $ende_time = mktime($ende_stunde,$ende_minute,0,4,1,2001);
            break;
            }
        }

    $id=md5(uniqid($hash_secret));
    $my_personal_sems[$id]=array("start_time"=>$start_time, "ende_time"=>$ende_time, "beschreibung"=>$beschreibung, "room" =>$room, "doz" =>$dozent, "seminar_id"=>$id);
    //die;
}

// meine Seminare einlesen
if ($inst_id) {
    // institute-admins are allowed to see hidden seminars
    if ($perm->have_studip_perm('admin', $inst_id)) {
        $db->query("SELECT seminare.Seminar_id, Name, VeranstaltungsNummer, start_time, duration_time,  metadata_dates
            FROM seminare WHERE Institut_id = '$inst_id'");
    }

    // others are not allowed to see hidden seminars
    else {
        $db->query("SELECT seminare.Seminar_id, Name, VeranstaltungsNummer, start_time, duration_time,  metadata_dates
            FROM seminare WHERE Institut_id = '$inst_id' AND visible='1'");
    }

} else {
    $user_id=$user->id;
    if ($perm->have_perm("admin")) {
        $db->query("SELECT seminare.Seminar_id, Name, VeranstaltungsNummer, start_time, duration_time,  metadata_dates FROM seminare WHERE Institut_id = '".$my_schedule_settings ["glb_inst_id"]."' ");
    } else {
        $db->query("SELECT seminare.Seminar_id, Name, VeranstaltungsNummer, start_time, duration_time,  metadata_dates FROM  seminar_user LEFT JOIN seminare USING (seminar_id) WHERE user_id = '$user_id'");
    }
}

// select right semester
if ($_REQUEST['inst_id']) {
    $tmp_sem_nr = $_REQUEST['instview_sem'];
} else {
    $k = 0;
    foreach ($all_semester as $a) {
        if ($sem_name) {
            if (rawurldecode($sem_name) == $my_schedule_settings["glb_sem"])
                $tmp_sem_nr = $k;
        } else {
            if ($a["name"] == $my_schedule_settings["glb_sem"])
                $tmp_sem_nr = $k;
            $k++;
        }
    }
}

if (!$tmp_sem_nr) {
    if (time() < $VORLES_ENDE) {
        $tmp_sem_beginn = $SEM_BEGINN;
        $tmp_sem_ende = $SEM_ENDE;
        $tmp_sem_nr = $SEM_ID;
    } else {
        $tmp_sem_beginn=$SEM_BEGINN_NEXT;
        $tmp_sem_ende=$SEM_ENDE_NEXT;
        $tmp_sem_nr=$SEM_ID_NEXT;
    }
} else {
    $tmp_sem_beginn=$all_semester[$tmp_sem_nr]["beginn"];
    $tmp_sem_ende=$all_semester[$tmp_sem_nr]["ende"];
}

// Set the view (begin hour and and hour)
if ($_REQUEST['inst_id']) {
    $global_start_time=8;
    $global_end_time=20;
} else {
    $global_start_time=$my_schedule_settings["glb_start_time"];
    $global_end_time=$my_schedule_settings["glb_end_time"];
}

$something_hidden = False;
// Array der Seminare erzeugen
for ($seminar_user_schedule = 1; $seminar_user_schedule <= 2; $seminar_user_schedule++) {
    if ($seminar_user_schedule == 2) {
        if (!$_REQUEST['inst_id']) {
            // Das gleiche nochmal mit den virtuellen Veranstaltungseintragungen
            $db->query($query = "SELECT b.* FROM seminar_user_schedule a, seminare b WHERE a.range_id = b.Seminar_id AND a.user_id = '".$auth->auth['uid']."'");
        }
    }

    while ($db->next_record()) {
    // Bestimmen, ob die Veranstaltung in dem Semester liegt, was angezeigt werden soll
    $use_this = FALSE;
    $term_data = unserialize($db->f("metadata_dates"));

    if (($db->f("start_time") <=$tmp_sem_beginn) && ($tmp_sem_beginn <= ($db->f("start_time") + $db->f("duration_time")))) {
        $use_this = TRUE;
    }
    if (($use_this) && (is_array($term_data["turnus_data"]) && count($term_data["turnus_data"]))) {
        //Zusammenbasteln Dozentenfeld
        $db2->query("SELECT Nachname, username, position FROM seminar_user LEFT JOIN auth_user_md5 USING (user_id) WHERE status='dozent' AND Seminar_id ='".$db->f("Seminar_id")."' ORDER BY position ");
        $dozenten='';
        $i=1;
        while ($db2->next_record())
            {
            if ($i>1)
                $dozenten.=", ";
            if ($view != 'print')
                $dozenten.= '<a href ="'. URLHelper::getLink('about.php?username='.$db2->f("username")). '">'.htmlReady($db2->f("Nachname"))."</a>";
            else
                $dozenten.= htmlReady($db2->f("Nachname"));
            $i++;
            }

        $i=0;
        foreach ($term_data["turnus_data"] as $data)
            if ($data["end_stunde"] >= $global_start_time) {
                //generate the room

                if ($RESOURCES_ENABLE) {
                    $roomIDsArray = CycleDataDB::getPredominantRoomDB($data["metadate_id"]);
                    if( $roomIDsArray) {
                        $tmp_room = getResourceObjectName($roomIDsArray[0]);
                    } else {
                        $tmp_room = _("n. A.");
                    }
                } else {
                    $roomName = CycleDataDB::getFreeTextPredominantRoomDB($data["metadate_id"]);
                    if( $roomName) {
                        $tmp_room = $roomName;
                    } else {
                        $tmp_room = _("n. A.");
                    }
                }

                //Patch fuer Problem mit alten Versionwn <=0.7 (Typ war falsch gesetzt), wird nur fuer rueckwaerts-Kompatibilitaet benoetigt
                settype ($data["start_stunde"], "integer");
                settype ($data["end_stunde"], "integer");
                settype ($data["start_minute"], "integer");
                settype ($data["end_minute"], "integer");

                //Check, ob die Endzeit ueber den sichtbaren Bereich des Stundenplans hinauslaeuft, wenn ja wird row_span entsprechend angepasst
                if ($data["end_stunde"] >$global_end_time) {
                    $tmp_row_span = ((($global_end_time - $data["start_stunde"])+1) *4);
                    $tmp_row_span = $tmp_row_span - (int)($data["start_minute"] / 15);
                } else
                    $tmp_row_span = ceil((($data["end_stunde"] - $data["start_stunde"]) * 4) + (($data["end_minute"] - $data['start_minute'] ) / 15));

                //Check, ob die Startzeit ueber den Sichtbaren Bereich hinauslaeuft, wenn ja wird row_span und der index entsprechend frisiert
                if ($data["start_stunde"] < $global_start_time) {
                    $tmp_row_span = $tmp_row_span - (($global_start_time - $data["start_stunde"]) *4);
                    $tmp_row_span = $tmp_row_span + (int)($data["start_minute"] / 15);
                    $idx_corr_h = $global_start_time - $data["start_stunde"];
                    $idx_corr_m = (0 - $data["start_minute"]) ;
                } else {
                    $idx_corr_h = 0;
                    $idx_corr_m = 0;
                }

                //Dummy-Timestamps erzeugen. Der 5.8.2001 (ein Sonntag) wird als Grundlage verwendet.
                $start_time=mktime($data["start_stunde"], $data["start_minute"], 0, 8, (5+$data["day"]), 2001);
                $end_time=mktime($data["end_stunde"], $data["end_minute"], 0, 8, (5+$data["day"]), 2001);

                $i++; //<pfusch>$i (fuer alle einzelnen Objekte eines Seminars) wird hier zur Kennzeichnung der einzelen Termine eines Seminars untereinander verwendet. Unten wird die letzte Stelle jeweils weggelassen. </pfusch>

            // virtual dates can't be hidden
            if ($my_schedule_settings['hidden'][$db->f("Seminar_id").$i] && $seminar_user_schedule == 2) {
                unset($my_schedule_settings['hidden'][$db->f("Seminar_id").$i]);
            }

            if ($view == 'edit' || !$my_schedule_settings['hidden'][$db->f("Seminar_id").$i]) {
                $my_sems[$db->f("Seminar_id").$i] = array(
                    "start_time_idx"=>$data["start_stunde"]+$idx_corr_h.(int)(($data["start_minute"]+$idx_corr_m) / 15).$data["day"],
                    "start_time"=>$start_time,
                    "end_time"=>$end_time, "name"=>$db->f("Name"), "nummer"=>$db->f("VeranstaltungsNummer"),
                    "seminar_id"=>$db->f("Seminar_id").$i,
                    "ort"=>$tmp_room,
                    "row_span"=>$tmp_row_span,
                    "dozenten"=>$dozenten,
                    "personal_sem"=>FALSE,
                    'desc' => $data['desc'],
                    "virtual" => ($seminar_user_schedule == 2) ? true : false);
            } else {
                if ($my_schedule_settings["glb_days"][$DAY_I2S[$data['day']]])
                    $something_hidden = True;
            }}
        }
    }
}

// Daten aus der Sessionvariable hinzufuegen
if ((is_array($my_personal_sems)) && (!$inst_id)){
    foreach ($my_personal_sems as $key => $mps){
        if(!$mps["ende_time"] || !$mps['start_time']){
            unset($my_personal_sems[$key]);
            continue;
        }
        if (date("G", $mps["ende_time"]) >= $global_start_time) {
            //auch hier nochmal der Check
            if (date("G", $mps["ende_time"]) > $global_end_time) {
                $tmp_end_time = mktime($global_end_time+1, 00, 00, date ("n", $mps["start_time"]), date ("j", $mps["start_time"]), date ("Y", $mps["start_time"]));
                $tmp_row_span = (int)(($tmp_end_time - $mps["start_time"]) /15/60);
            } else
                $tmp_row_span = (int)(($mps["ende_time"] - $mps["start_time"])/15/60);

            // und der andere
            if (date("G", $mps["start_time"]) < $global_start_time) {
                $tmp_start_time = mktime($global_start_time, 00, 00, date ("n", $mps["start_time"]), date ("j", $mps["start_time"]), date ("Y", $mps["start_time"]));
                $tmp_row_span = (int)(($tmp_end_time - $tmp_start_time) /15/60);
                $idx_corr_h = $global_start_time - date("G", $mps["start_time"]);
                $idx_corr_m = (0 - date("i", $mps["start_time"]));
            } else {
                $idx_corr_h = 0;
                $idx_corr_m = 0;
            }

            // aus Sonntag=0 wird Sonntag=7, damit laesst's sich besser arbeiten *g
            $tmp_day = date("w", $mps["start_time"]);
            if ($tmp_day==0)
                $tmp_day = 7;

            // start_time_idx is encoded here as:  <hour of day> * 100 + <quarter of hour> * 10 + <day of week>
            $my_sems[$mps["seminar_id"]]=array("start_time_idx"=>date("G", $mps["start_time"]+$idx_corr_h) * 100 + (date("i", $mps["start_time"]+$idx_corr_m) / 15) * 10 + $tmp_day, "start_time"=>$mps["start_time"], "end_time"=>$mps["ende_time"], "name"=>$mps["beschreibung"], "seminar_id"=>$mps["seminar_id"],  "ort"=>$mps["room"], "row_span"=>$tmp_row_span, "dozenten"=>htmlReady($mps["doz"]), "personal_sem"=>TRUE);
        }
    }
}

// Array der Zellenbelegungen erzeugen
if (is_array($my_sems)) {
    foreach ($my_sems as $ms) {
        $m = 1;
        $idx_tmp = $ms["start_time_idx"];
        if ($ms["row_span"]>0) {
            for ($m; $m<=$ms["row_span"]; $m++) {
                if ($m==1)
                    $start_cell=TRUE;
                else
                    $start_cell=FALSE;

                $cell_sem[$idx_tmp][$ms["seminar_id"]] = $start_cell;

                // extract quarter of hour from $idx_tmp and skip to next quarter
                if (($idx_tmp % 100) - date("w",$ms["start_time"]) == 30) {
                    $idx_tmp=$idx_tmp+70;
                } else {
                    $idx_tmp=$idx_tmp+10;
                }
            }
        } else {
            $cell_sem[$idx_tmp][$ms["seminar_id"]] = TRUE;
        }
    }
}

// Alle Seminare, die sich ueberschneiden, zusammenfassen
for ($i = 1; $i<7; $i++) {
    for ($n=$global_start_time; $n<$global_end_time+1; $n++) {
        for ($l=0; $l<4; $l++) {
            $idx=($n*100)+($l*10)+$i;
            if ($cell_sem[$idx]) {
                if (sizeof($cell_sem[$idx])>0) {
                    $rows=0;
                    $start_idx=$idx;
                    while ($cs = each ($cell_sem [$idx]))
                        if ($cs[1])
                            if ($my_sems[$cs[0]]["row_span"]>$rows) $rows=$my_sems[$cs[0]]["row_span"];
                    reset ($cell_sem[$idx]);
                    if ($rows>1) {
                        $s=2;
                        for ($s; $s<=$rows; $s++) {
                            $l++;
                            if ($l>=4) {
                                $l = 0;
                                $n++;
                                }
                            $idx=($n*100)+($l*10)+$i;
                            while ($cs = each ($cell_sem [$idx]))
                                if ($cs[1]) {
                                    $cell_sem[$idx][$cs[0]]=FALSE;
                                    $cell_sem[$start_idx][$cs[0]]=TRUE;
                                    if ($my_sems[$cs[0]]["row_span"] > $rows -$s +1)
                                        $rows=$rows+($my_sems[$cs[0]]["row_span"]-($rows-$s +1));
                                    }
                                reset ($cell_sem[$idx]);
                            }
                        }
                    $cs = each (array_slice ($cell_sem[$start_idx], 0));
                    reset ($cell_sem[$start_idx]);
                    $my_sems[$cs[0]]["row_span"] = $rows;
                }
            }
        }
    }
}

?>
<table style="background-color: #fff; width: 100%;"><tr><td><? /* hack to get a white background */ ?>

<div style="margin: 20px;">
<p><?php


if ($_REQUEST['inst_id'] && $view != 'print') { ?>
<?=_("Im Veranstaltungs-Timetable sehen Sie alle Veranstaltungen eines Semesters an der gew&auml;hlten Einrichtung.")?><br />
<form action="<? echo URLHelper::getLink(''); ?>" method="POST">
<br /><font size=-1><?=_("Angezeigtes Semester:")?>&nbsp;
    <select name="instview_sem" style="vertical-align:middle">
    <?
        foreach ($all_semester as $key=>$val) {
            printf ("<option %s value=\"%s\">%s</option>\n", ($tmp_sem_nr == $key) ? "selected" : "", $key, $val["name"]);
        }
    ?>
    </select>&nbsp;
    <input type="IMAGE" value="change_instview_sem" <? echo makeButton("uebernehmen", "src") ?> border=0 align="middle" value="<?=_("&uuml;bernehmen")?>" />&nbsp;
    <input type="HIDDEN" name="inst_id" value="<? echo $inst_id ?>" /></form><br>

 <br><font size=-1><a target="_blank" href="<?= URLHelper::getLink("?view=print&inst_id=$inst_id&instview_sem=$instview_sem") ?>">
<?= _("Druckansicht dieser Seite (wird in einem neuen Browserfenster ge&ouml;ffnet).") ?></a></font>
<?php
}


echo '</p></div>';

if($view == 'edit') {
?>

<form method="POST" action="<? echo URLHelper::getLink('?schedule_cmd=change_view_insert'); ?>">

<?=_("angezeigtes Semester"); ?>: <select name="sem">
<?
if (!$my_schedule_settings ["glb_sem"]) {
    if (time() > $VORLES_ENDE) {
        echo '<option selected value="'. $SEM_NAME_NEXT .'">'._("aktuelles Semester")." ($SEM_NAME_NEXT)</option>";
        $tmp_name = $SEM_NAME_NEXT;
        } else {
        echo '<option selected value="'. $SEM_NAME .'">'._("aktuelles Semester")." ($SEM_NAME)</option>";
        $tmp_name = $SEM_NAME;
        }
}

foreach ($all_semester as $a) {
    if ((time() < $a["vorles_ende"]) && ($a["name"] != $tmp_name)){
        if ($my_schedule_settings ["glb_sem"] == $a["name"]) {
            echo '<option value="'. $a["name"] .'" selected>'. $a["name"] ."</option>";
        } else {
            echo '<option value="'. $a["name"] .'">'. $a["name"]."</option>";
        }
    }
}
echo "</select>";
}
ob_end_flush(); //Clear buffer for ouput the headers
ob_start();

?>
<table width="100%">
<tr><td valign="top">

<table class="steel1" width="100%" <? if ($view == 'print') { ?> bgcolor="#eeeeee" <? } ?> align="center" cellspacing=1 cellpadding=0 border=0>
<tr>
    <td width="10%" align="center" class="rahmen_steelgraulight" ><?=_("Zeit")?>
    </td>
    <? if ($my_schedule_settings["glb_days"]["mo"] || $view == 'edit' || $_REQUEST['inst_id']) {?>
    <td width="<?echo round (90/$glb_colspan)."%"?>" align="center" class="rahmen_steelgraulight" >
        <?php

        if($my_schedule_settings["glb_days"]["mo"]) {
            $checked = 'checked';
        } else {
            $checked = '';
        }
        if($view == 'edit') {
            echo '<input type="checkbox" name="mo" value="true" '. $checked .'>&nbsp;';
        }
        echo _("Montag");

        ?>
    </td><?}
    if ($my_schedule_settings["glb_days"]["di"] || $view == 'edit' || $_REQUEST['inst_id']) {?>
    <td width="<?echo round (90/$glb_colspan)."%"?>" align="center" class="rahmen_steelgraulight">
        <?php

        if($my_schedule_settings["glb_days"]["di"]) {
            $checked = 'checked';
        } else {
            $checked = '';
        }
        if($view == 'edit') {
            echo '<input type="checkbox" name="di" value="true" '. $checked .'>&nbsp;';
        }

        echo _("Dienstag");

        ?>
    </td><?}
    if ($my_schedule_settings["glb_days"]["mi"] || $view == 'edit' || $_REQUEST['inst_id']) {?>
    <td width="<?echo round (90/$glb_colspan)."%"?>" align="center" class="rahmen_steelgraulight">
        <?php

        if($my_schedule_settings["glb_days"]["mi"]) {
            $checked = 'checked';
        } else {
            $checked = '';
        }
        if($view == 'edit') {
            echo '<input type="checkbox" name="mi" value="true" '. $checked .'>&nbsp;';
        }

        echo _("Mittwoch");

        ?>
    </td><?}
    if ($my_schedule_settings["glb_days"]["do"] || $view == 'edit' || $_REQUEST['inst_id']) {?>
    <td width="<?echo round (90/$glb_colspan)."%"?>" align="center" class="rahmen_steelgraulight">
    <?php

        if($my_schedule_settings["glb_days"]["do"]) {
            $checked = 'checked';
        } else {
            $checked = '';
        }
        if($view == 'edit') {
            echo '<input type="checkbox" name="do" value="true" '. $checked .'>&nbsp;';
        }
        echo _("Donnerstag");

    ?>
    </td><?}
    if ($my_schedule_settings["glb_days"]["fr"] || $view == 'edit' || $_REQUEST['inst_id']) {?>
    <td width="<?echo round (90/$glb_colspan)."%"?>" align="center" class="rahmen_steelgraulight">
    <?php

        if($my_schedule_settings["glb_days"]["fr"] || $view == 'edit') {
            $checked = 'checked';
        } else {
            $checked = '';
        }
        if($view == 'edit') {
            echo '<input type="checkbox" name="fr" value="true" '. $checked .'>&nbsp;';
        }
        echo _("Freitag");

        ?>
    </td><?}
    if ($my_schedule_settings["glb_days"]["sa"] || $view == 'edit' || $_REQUEST['inst_id']) {?>
    <td width="<?echo round (90/$glb_colspan)."%"?>" align="center" class="rahmen_steelgraulight">
    <?php

        if($my_schedule_settings["glb_days"]["sa"]) {
            $checked = 'checked';
        } else {
            $checked = '';
        }
        if($view == 'edit') {
            echo '<input type="checkbox" name="sa" value="true" '. $checked .'>&nbsp;';
        }
        echo _("Samstag");

        ?>
    </td><?}

    if ($my_schedule_settings["glb_days"]["so"] || $view == 'edit' || $_REQUEST['inst_id']) {?>
    <td width="<?echo round (90/$glb_colspan)."%"?>" align="center" class="rahmen_steelgraulight">
    <?php

        if($my_schedule_settings["glb_days"]["so"]) {
            $checked = 'checked';
        } else {
            $checked = '';
        }
        if($view == 'edit') {
            echo '<input type="checkbox" name="so" value="true" '. $checked .'>&nbsp;';
        }
        echo _("Sonntag");

        ?>
    </td><?}?>
</tr>
<?
// Aufbauen der eigentlichen Tabelle
for ($i = $global_start_time; $i < $global_end_time+1; $i++) {
    for ($k = 0; $k<4; $k++) {
        if ($k==0) {
            echo "<tr><td align=\"center\" class=\"rahmen_steelgraulight\" rowspan=4>";
            if(($i == $global_start_time  || $i == $global_end_time )&& $view == 'edit') {
                if($i == $global_start_time ) {
                    echo _("Anfangszeit:") . '<br/><select name="beginn_zeit">';
                    $time = $global_start_time;
                } else {
                    echo _("Endzeit:") . '<br/><select name="ende_zeit">';
                    $time = $global_end_time;
                }

                for ($j=0; $j<=23; $j++) {
                    $selected = '';
                    if ($j == $time) {
                        $selected = 'selected';
                    }

                    echo "<option $selected value=".$j.">";
                    if ($j<10) {
                        echo "0";
                    }
                    echo $j.":00</option>";
                }
                echo "</select>";
            } else {

                if ($i<10) echo "0";
                echo $i, ":00 "._("Uhr")."</td>";
            }
        }
        else echo "<tr>";
        $l = 1;
        for ($l; $l<8; $l++) {
            //ausgeblendete Tage skippen
            if($view != 'edit' && !$_REQUEST['inst_id']) {
                if (($l==1) && (!$my_schedule_settings["glb_days"]["mo"] )) continue;
                if (($l==2) && (!$my_schedule_settings["glb_days"]["di"] )) continue;
                if (($l==3) && (!$my_schedule_settings["glb_days"]["mi"] )) continue;
                if (($l==4) && (!$my_schedule_settings["glb_days"]["do"] )) continue;
                if (($l==5) && (!$my_schedule_settings["glb_days"]["fr"] )) continue;
                if (($l==6) && (!$my_schedule_settings["glb_days"]["sa"] )) continue;
                if (($l==7) && (!$my_schedule_settings["glb_days"]["so"] )) continue;
            }
            //if ($l <>8)
            {
            $idx = ($i*100)+($k*10)+$l;
            unset($cell_content);
            $m = 0;
            if ($cell_sem[$idx]) {
                while ($cs = each ($cell_sem[$idx])) {
                        $cell_content[] = array("seminar_id"=>$cs[0], "start_cell"=>$cs[1]);
                }
            }

            if ((!$cell_sem[$idx]) || ($cell_content[0]["start_cell"])) echo "<td ";
            $u = 0;

            if (($cell_sem[$idx]) && ($cell_content[0]["start_cell"])) {
                $r = 0;
                foreach ($cell_content as $cc) {
                    if($my_schedule_settings['hidden'][$cc['seminar_id']]
                      && $view != 'edit'
                      && !$_REQUEST['inst_id']) {
                        if ($r==0) {
                            echo "><table><tr><td>";
                        }
                        $something_hidden = True;
                        continue;
                    }

                    if ($r==0) {
                        echo 'class="rahmen_white" valign="top" rowspan='.$my_sems[$cell_content[0]["seminar_id"]]["row_span"].">";
                        echo '<table width="100%" cellspacing=0 cellpadding=2 border=0>';
                        if($my_schedule_settings['hidden'][$cc['seminar_id']]) {
                            echo '<tr><td style="background-color: #aaa;">';
                        } else {
                            echo '<tr><td class="topic">';
                        }
                    } else {
                        if($my_schedule_settings['hidden'][$cc['seminar_id']] && $view != 'print') {
                            echo "</td></tr><tr><td style=\"background-color: #aaa;\">";
                        } else {
                            echo "</td></tr><tr><td class=\"topic\">";
                        }
                    }

                    if (($view == 'print') && ($r!=0))
                        echo "<hr src=\"".$GLOBALS['ASSETS_URL']."images/border.jpg\" width=\"100%\">";
                    $r++;
                    echo "<font size=-1 ";
                    if ($view != 'print')
                        echo "color=\"#FFFFFF\"";
                    echo ">";

                    //seminar id auf 32 zeichen kürzen
                    $id = substr($my_sems[$cc["seminar_id"]]["seminar_id"], 0, 32);

                    if ($view == 'edit') {
                        if ($my_sems[$cc["seminar_id"]]["personal_sem"]) {
                            $link_img = 'trash.gif" ';
                            $link_cmd = 'delete';
                            $link_tp = tooltip(_("Diesen Termin löschen"));
                        } else if($my_sems[$cc['seminar_id']]['virtual']){
                            $link_img = 'trash.gif" ';
                            $link_cmd = 'delete_entry';
                            $link_tp = tooltip(_("Diesen Termin löschen"));
                        } else {
                            if($my_schedule_settings['hidden'][$cc['seminar_id']]) {
                                $link_img = 'unhide.gif" ';
                                $link_cmd = 'show';
                                $link_tp = tooltip(_("Diesen Termin wieder einblenden"));
                            } else {
                                $link_img = 'hide.gif" ';
                                $link_cmd = 'hide';
                                $link_tp = tooltip(_("Diesen Termin ausblenden"));
                            }
                        }
                        echo '<a style="float: right;" href="'. URLHelper::getLink('?view=edit&cmd='. $link_cmd .'&sem_id='.$cc["seminar_id"]).'">';
                        echo '<img border=0 src="'. $GLOBALS['ASSETS_URL']. 'images/' .$link_img . $link_tp .'></a>';
                    }

                    if ($_REQUEST['inst_id'] && $view == 'standard' && $my_schedule_settings['hidden'][$cc['seminar_id']]) {
                        echo '<img style="float: right;" src="'.$GLOBALS['ASSETS_URL'].'images/info.gif" '. tooltip(_('Dieser Termin ist in Ihrem Studenplan versteckt.')) .' />';

                    }


                    echo date ("H:i",  $my_sems[$cc["seminar_id"]]["start_time"]);
                    if  ($my_sems[$cc["seminar_id"]]["start_time"] <> $my_sems[$cc["seminar_id"]]["end_time"])
                        echo " - ",  date ("H:i",  $my_sems[$cc["seminar_id"]]["end_time"]);
                    if (!$my_sems[$cc['seminar_id']]['virtual']) {
                        if ($my_sems[$cc["seminar_id"]]['desc']) echo ' ('.htmlReady($my_sems[$cc["seminar_id"]]['desc']).')';
                    }

                    if ($my_sems[$cc['seminar_id']]['ort']) echo ", ", htmlReady($my_sems[$cc["seminar_id"]]["ort"]);
                    echo '</font></td></tr><tr><td class="blank">';
                    if ((!$my_sems[$cc["seminar_id"]]["personal_sem"]) && $view != 'print') {
                        if ($my_sems[$cc['seminar_id']]['virtual']) {
                            echo "<a href=\"". URLHelper::getLink('details.php?sem_id='.$id)."\">";
                            echo "<FONT size=\"-1\" color=\"green\">";
                        } else {
                            if ($_REQUEST['inst_id']) {
                                echo '<a href="'. URLHelper::getLink('details.php?sem_id='.$id) .'">';
                            } else {
                                echo '<a href="'. URLHelper::getLink('seminar_main.php?auswahl='.$id) .'">';
                            }
                            echo '<font size=-1>';
                        }
                        if ($my_sems[$cc["seminar_id"]]["nummer"]) {
                            echo htmlReady($my_sems[$cc["seminar_id"]]["nummer"]) . "&nbsp;";
                        }
                        echo htmlReady(substr($my_sems[$cc["seminar_id"]]["name"], 0,50));
                        if (strlen($my_sems[$cc["seminar_id"]]["name"])>50)
                            echo "...";
                        echo"</font></a>";
                    } else {
                        echo "<font size=-1>";
                        if ($my_sems[$cc["seminar_id"]]["nummer"]) {
                            echo htmlReady($my_sems[$cc["seminar_id"]]["nummer"]) . "&nbsp;";
                        }
                        echo htmlReady(substr($my_sems[$cc["seminar_id"]]["name"], 0,50));
                        if (strlen($my_sems[$cc["seminar_id"]]["name"])>50)
                            echo "...";
                        echo "</font>";
                        }
                    if ($my_sems[$cc["seminar_id"]]["dozenten"])
                        echo "<br><div align=\"right\"><font size=-1>", $my_sems[$cc["seminar_id"]]["dozenten"], "</font></div>";

                    }
                echo "</td></tr></table></td>";
                }
            if (!$cell_sem[$idx])  echo "></td>";
            }
            }
            echo "</tr>\n";
        }
    }

    if ($view == 'print') {
        printf  ("<tr><td colspan=%s><i><font size=-1>&nbsp; "._("Erstellt am %s um %s  Uhr.")."</font></i></td><td align=\"right\"><font size=-2><img src=\"".$GLOBALS['ASSETS_URL']."images/logo2b.gif\"><br />&copy; %s v.%s&nbsp; &nbsp; </font></td></tr></tr>", $glb_colspan, date("d.m.y", time()), date("G:i", time()), date("Y", time()), $SOFTWARE_VERSION);
        }
    else {
        }

echo "</table>";


if($view == 'edit') {
    echo '<input style="float: right;" type="IMAGE" '. makeButton("uebernehmen", "src") .' border=0 value="'. _("&Auml;nderungen &uuml;bernehmen") .'"></form><br><br>';
}

// Info-Box
if($view != 'print' && !$_REQUEST['inst_id']) {
echo '</td><td class="blank" width="270" align="right" valign="top">';
// -- Information --
$i = 0;
$infobox_info = array();

if($view == 'standard') {
    $infobox_info[$i] = array ("icon" => 'info.gif',
                               "text"  => _("Der Stundenplan zeigt Ihnen alle regelm&auml;&szlig;igen Veranstaltungen eines Semesters."));
    $i++;

    if ($CALENDAR_ENABLE) {
        $infobox_info[$i] = array ("icon" => "info.gif",
                                   "text"  => sprintf(_("Ihre pers&ouml;nlichen Termine finden Sie im %sTerminkalender%s."), '<a href="'. URLHelper::getLink('calendar.php') . '">', "</a>"));
        $i++;
    }
} else { // view == edit
    $infobox_info[$i] = array ("icon" => 'info.gif',
                               "text"  => _("Hier k&ouml;nnen Sie sie Ansicht ihres pers&ouml;nlichen Stundenplans nach Ihren Vorstellungen anpassen."));
    $i++;
}

// viewed semester != current semester
$current_sem = '';
if (time() > $VORLES_ENDE) {
    $current_sem = $SEM_NAME_NEXT;
} else {
    $current_sem = $SEM_NAME;
}

if ($_REQUEST['inst_id']) {
    $selected_sem = $all_semester[$tmp_sem_nr]['name'];
} else {
    $selected_sem = $my_schedule_settings['glb_sem'];
}

if($something_hidden && $view == 'standard') {
    $infobox_info[$i] = array("icon" => "ausruf_small.gif",
                              "text" => sprintf(_('Ein oder mehrere Termine wurden nicht angezigt. Um die Sichtbarkeit von Terminen anzupassen k&ouml;nnen Sie den %sStudenplan anpassen%s.'), '<a href="'. URLHelper::getLink('?view=edit') .'">', '</a>'));
    $i++;
}

if($my_schedule_settings['glb_sem'] != '' && $my_schedule_settings['glb_sem'] != $current_sem) {
    $infobox_info[$i] = array("icon" => "ausruf_small.gif",
                              "text" => _('Das angezeigte Semester ist nicht das aktuelle.'));
    $i++;
}

// -- View --
$infobox_view = array();
$i = 0;

$icon = 'cont_res1.gif';
if($view == 'standard')
    $icon = 'forumrot_indikator.gif';

$infobox_view[$i] = array("icon" => $icon,
                             "text" => sprintf(_('%sStandard%s'), '<a href="'. URLHelper::getLink('mein_stundenplan.php') .'">', '</a>'));
$i++;

$infobox_view[$i] = array("icon" => "icon-cont.gif",
                             "text" => sprintf(_('%sDruckansicht%s'), '<a href="'. URLHelper::getLink('mein_stundenplan.php?view=print') .'" target="_blank">', '</a>'));
$i++;

$icon = 'eigene2.gif';
if($view == 'edit')
    $icon = 'forumrot_indikator.gif';
$infobox_view[$i] = array("icon" => $icon,
                             "text" => sprintf(_('%sStundenplan anpassen%s<br /> Hier k&ouml;nnen Sie unter anderem eigene Termine nachtragen, Termine ausblenden oder den Zeitraum, den der Stundenplan umfasst, &auml;ndern.'),
                                                    '<a href="'.  URLHelper::getLink('?view=edit') .'">', '</a>'));
$i++;


// -- Actions --
$infobox_actions = array();
$i = 0;
$infobox_actions[$i] = array("icon" => "suche2.gif",
            "text"  => sprintf(_("Wenn Sie weitere Veranstaltungen aus Stud.IP in ihren Stundenplan aufnehmen m&ouml;chten, nutzen Sie bitte die %sVeranstaltungssuche%s."),
                                    '<a href = "'. URLHelper::getLink('sem_portal.php') .'">', "</a>"));


$infobox = array(
                array("kategorie"  => _("Information:"),
                        "eintrag" => $infobox_info),
                array  ("kategorie" => _("Ansichten:"),
                        "eintrag" => $infobox_view),
                array  ("kategorie" => _("Aktionen:"),
                        "eintrag" => $infobox_actions)
            );


print_infobox($infobox, "infoboxes/schedules.jpg");
}

echo '</td></tr></table>';


if($view == 'edit') {
?>
<div class="steelgraulight" style="margin-top: 30px; padding: 10px;">
    <b>&nbsp;<?=_("Eigene Veranstaltung eintragen:")?></b><br>
    <div style="margin-left: 15px; padding: 10px">

        <font size=-1>&nbsp;(<?=_("Hier k&ouml;nnen sie Veranstaltungen, die nicht im Stud.IP System existieren oder andere, eigene Ereignisse eintragen")?>)</font><br>
        <form method="POST" action="<?=URLHelper::getLink('?cmd=insert') ?>">
            &nbsp;<?_("Wochentag:")?>
            <select name="tag">
                <option value="1"><?=_("Montag")?></option>
                <option value="2"><?=_("Dienstag")?></option>
                <option value="3"><?=_("Mittwoch")?></option>
                <option value="4"><?=_("Donnerstag")?></option>
                <option value="5"><?=_("Freitag")?></option>
                <option value="6"><?=_("Samstag")?></option>
                <option value="7"><?=_("Sonntag")?></option>
            </select>&nbsp; &nbsp;
            <?=_("Beginn:")?>
            <?
            echo"<select name=\"start_stunde\">";
            for ($i=$global_start_time; $i<=$global_end_time; $i++)
                {
                if ($i==9) echo "<option selected value=".$i.">".$i."</option>";
                    else echo "<option value=".$i.">".$i."</option>";
                }
                echo"</select>";
                echo"<select name=\"start_minute\">";
                for ($i=0; $i<=45; $i=$i+15)
                {
                if ($i==0) echo "<option selected value=".$i.">0".$i."</option>";
                    else echo "<option value=".$i.">".$i."</option>";
                }
                echo"</select> "._("Uhr")."&nbsp; &nbsp; ";
                ?>
            <?=_("Ende:")?>
            <?
            echo"<select name=\"ende_stunde\">";
            for ($i=$global_start_time; $i<=$global_end_time; $i++)
                {
                if ($i==9) echo "<option selected value=".$i.">".$i."</option>";
                    else echo "<option value=".$i.">".$i."</option>";
                }
                echo"</select>";
                echo"<select name=\"ende_minute\">";
                for ($i=0; $i<=45; $i=$i+15)
                {
                if ($i==0) echo "<option value=".$i.">0".$i."</option>";
                elseif ($i==45) echo "<option selected value=".$i.">".$i."</option>";
                    else echo "<option value=".$i.">".$i."</option>";
                }
                echo"</select> "._("Uhr");
                echo "<br />&nbsp; "._("Beschreibung:");
                ?>
                <input name="beschreibung" type="text" size=40 maxlength=255>&nbsp; &nbsp;
                <?=_("Raum:")?>
                <input name="room" type="text" size=20 maxlength=255>&nbsp; &nbsp;
                <?=_("DozentIn:")?>
                <input name="dozent" type="text" size=20 maxlength=255><br />&nbsp;
                <input name="send" type="IMAGE" <?=makeButton("eintragen", "src")?> value="<?=("Eintragen")?>">
                </form>
</div></div>

<?php

if(count($my_schedule_settings['hidden']) > 0) {
?>
    <div class="steelgraulight" style="margin: 0px; padding: 10px;">
    <b>&nbsp;<?=_("Ausgeblendete Termine:")?></b><br>
    <div style="margin-left: 15px; padding: 10px">

<?php
$first = True;
foreach($my_schedule_settings['hidden'] as $id => $value) {
    if(!$first){
        echo ', ';
    }
    $first = False;

    echo '<a href="'. URLHelper::getLink('?view=edit&cmd=show&sem_id='. $id) .'" title="'. _("Diesen Termin wieder einblenden") .'">';
    echo htmlReady($my_sems[$id]['name']);
    echo strftime(' (%A %H:%M)', $my_sems[$id]["start_time"]);
    echo '</a>';
}
} // view == edit

echo '</div></div>';
}



echo '</td></tr></table>';

ob_end_flush(); //end outputbuffering
// Save data back to database.

include ('lib/include/html_end.inc.php');
page_close();
?>
