<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO
/*
teilnehmer_aux.php - Anzeige der Teilnehmer eines Seminares
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
unregister_globals();
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));

include "lib/seminar_open.php"; //hier werden die sessions initialisiert

require_once('lib/classes/DataFieldEntry.class.php');
require_once('lib/classes/ZebraTable.class.php');
require_once('lib/classes/AuxLockRules.class.php');
require_once('lib/dates.inc.php');

checkObject();
checkObjectModule("participants");

PageLayout::setTitle($SessSemName["header_line"]. " - " . _("Zusatzangaben"));
Navigation::activateItem('/course/members/aux_data');

if (!$_REQUEST['display_type']) {
    // Start of Output
    include ("lib/include/html_head.inc.php"); // Output of html head
    include ("lib/include/header.php");   //hier wird der "Kopf" nachgeladen
}

$sem_id = $SessSemName[1];
$sem_type = $SessSemName["art_num"];
$user_id = $user->id;
$rule = AuxLockRules::getLockRuleBySemId($sem_id);

function filterDatafields($entries) {
    global $rule;

    $new_entries = array();
    if (isset($rule)) {
        foreach ($entries as $key => $val) {
            if ($rule['attributes'][$key] == 1) {
                $new_entries[$key] = $val;
            }
        }
    }

    return $new_entries;
}

function get_aux_data() {
    global $sem_id, $user, $sem_type, $rule;

    $entries[0] = filterDatafields(DataFieldStructure::getDataFieldStructures('usersemdata'));
    $entries[1] = filterDatafields(DataFieldStructure::getDataFieldStructures('user'));

    $entry_data = array();
    for ($i = 0; $i <= 1; $i++) {
        foreach ($entries[$i] as $id => $entry) {
            $header[$id] = $entry->getName();
            $entry_data[$id] = '';
        }
    }

    $semFields = filterDataFields(AuxLockRules::getSemFields());
    foreach ($semFields as $id => $name) {
        $header[$id] = $name;
        $entry_data[$id] = '';
    }

    $data = array();

    $query    = "SELECT GROUP_CONCAT({$GLOBALS['_fullname_sql']['full']} SEPARATOR ', ')
                 FROM seminar_user
                 LEFT JOIN auth_user_md5 USING (user_id)
                 LEFT JOIN user_info USING (user_id)
                 WHERE seminar_user.status = 'dozent' AND seminar_user.Seminar_id = ?";
    $teachers = DBManager::get()->prepare($query);

    $query = "SELECT *, seminare.VeranstaltungsNummer AS vanr, seminare.Name AS vatitle
              FROM seminar_user
              LEFT JOIN auth_user_md5 USING (user_id)
              LEFT JOIN seminare USING (Seminar_id)
              WHERE Seminar_id = ? AND seminar_user.status IN ('autor', 'user')";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($sem_id));
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $data[$row['user_id']]['entry']    = $entry_data;
        $data[$row['user_id']]['fullname'] = $row['Vorname'].' '.$row['Nachname'];
        $data[$row['user_id']]['username'] = $row['username'];

        $entries[0] = filterDatafields(DataFieldEntry::getDataFieldEntries(array($row['user_id'], $sem_id), 'usersemdata'));
        $entries[1] = filterDatafields(DataFieldEntry::getDataFieldEntries($row['user_id'], 'user'));

        for ($i = 0; $i <= 1; $i++) {
            foreach ($entries[$i] as $id => $entry) {
                $data[$row['user_id']]['entry'][$id] = $entry->getDisplayValue(false);
            }
        }

        foreach ($semFields as $key => $name) {
            if ($key == 'vadozent') {
                if (!isset($vadozent)) {
                    $teachers->execute(array($sem_id));
                    $vadozent = $teachers->fetchColumn();
                    $teachers->closeCursor();
                }

                $data[$row['user_id']]['entry'][$key] = $vadozent;
            } else if ($key == 'vasemester') {
                if (!isset($vasemester)) {
                    $vasemester = get_semester($sem_id);
                }
                $data[$row['user_id']]['entry'][$key] = $vasemester;
            } else {
                $data[$row['user_id']]['entry'][$key] = $row[$key];
            }
        }
    }

    $order = $rule['order'];
    asort($order, SORT_NUMERIC);

    $new_header = array();
    foreach ($order as $key => $dontcare) {
        if (isset($header[$key])) {
            $new_header[$key] = $header[$key];
        }
    }

    return array('aux' => $data, 'header' => $new_header);
}

function aux_csv() {
    $sepp = ';';
    $aux_data = get_aux_data();

    $max = count($aux_data['header']);
    $max++;

    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=export.csv");

    $data = '"Name"'.$sepp;

    foreach ($aux_data['header'] as $id => $name) {
        $data .= '"'.$name.'"'.$sepp;
    }
    $data .= "\n";

    foreach ($aux_data['aux'] as $uid => $cur_user) {
        $data .= '"'.$cur_user['fullname'].'"'.$sepp;
        foreach ($aux_data['header'] as $showkey => $dontcare) {
            $data .= '"'.$cur_user['entry'][$showkey].'"'.$sepp;
        }

        $data .= "\n";
    }

    echo $data;
}

function aux_rtf() {
    $aux_data = get_aux_data();

    $max = count($aux_data['header']) + 1;
    $step = floor(8305 / $max);
    $cellx = '\cellx'.join('\cellx', range($step, $max * $step, $step))."\n";

    header("Content-Type: application/rtf");
    header("Content-Disposition: attachment; filename=export.rtf");


    ?>
{\rtf1\ansi\ansicpg1252\deff0\deflang1031{\fonttbl{\f0\fnil\fcharset0 Times New Roman;}}
{\pard
\trowd<?= $cellx ?>
\pard\intbl Name\cell
<? foreach ($aux_data['header'] as $name) : ?>
\pard\intbl <?= $name ?>\cell
<? endforeach ?>
\row

<? foreach ($aux_data['aux'] as $cur_user) : ?>
\trowd<?= $cellx ?>
\pard\intbl <?= $cur_user['fullname'] ?>\cell
<? foreach ($aux_data['header'] as $showkey => $dontcare) : ?>
\pard\intbl <?= $cur_user['entry'][$showkey] ?>\cell
<? endforeach ?>
\row

<? endforeach ?>
}
}
<?
}

function aux_html() {
    global $zt;

    $data = get_aux_data();

    echo $zt->openRow();
    $cell = '<form action="'.URLHelper::getLink().'" method="post">';
    $cell .= CSRFProtection::tokenTag();
    $cell .= '<select name="display_type"><option value="rtf">RTF</option><option value="csv">Excel kompatibel</option></select>';
    $cell .= '&nbsp;&nbsp;&nbsp;' . Button::create(_('Export')) . '</form>';
    echo $zt->cell($cell, array('colspan' => '20', 'class' => 'blank'));
    echo $zt->closeRow();

    echo $zt->openHeaderRow();
    echo $zt->cell('<b>Name</b>', array('align' => 'left', 'valign' => 'top'));
    foreach ($data['header'] as $id => $name) {
        echo $zt->cell('<b>'.htmlReady($name).'</b>', array('align' => 'left', 'valign' => 'top'));
    }
    echo $zt->closeRow();

    // einzelne Nutzerdaten ausgeben
    foreach ($data['aux'] as $uid => $cur_user) {
        echo $zt->openRow();
        echo $zt->cell(' <a href="'.URLHelper::getLink('about.php?username='.$cur_user['username']).'">'.htmlReady($cur_user['fullname']).'</a>');
        foreach ($data['header'] as $showkey => $dontcare) {
            echo $zt->cell(htmlReady($cur_user['entry'][$showkey]), array('align' => 'left'));
        }
        echo $zt->closeRow();
    }

    echo $zt->close();
}

function aux_sort_entries($entries, $rule) {
    $order = $rule['order'];
    asort($order, SORT_NUMERIC);

    $new_entries = array();
    foreach ($order as $key => $pos) {
        if ($entries[$key]) {
            $new_entries[$key] = $entries[$key];
        }
    }

    return $new_entries;
}

function aux_enter_data() {
    global $user_id, $sem_id, $user, $sem_type, $rule, $zt, $perm, $ct;
    global $datafield_id, $datafield_type, $datafield_sec_range_id, $datafield_content;

    unset($msgs);

    if (is_array($_REQUEST['datafields'])) {
        $invalidEntries = array();
        foreach (filterDatafields(DataFieldEntry::getDataFieldEntries(array($user_id, $sem_id), 'usersemdata')) as $id => $entry){
            if(isset($_REQUEST['datafields'][$entry->getId()])){
                $entry->setValueFromSubmit($_REQUEST['datafields'][$entry->getId()]);
                if ($entry->isValid()) {
                    $entry->store();
                } else {
                    $invalidEntries[$entry->getID()] = $entry;
                }
            }
        }
        /*// change visibility of role data
            foreach ($group_id as $groupID)
            setOptionsOfStGroup($groupID, $u_id, ($visible[$groupID] == '0') ? '0' : '1');*/
        if (count($invalidEntries))
            $msgs[] = 'error§<b>'. _("Sie haben fehlerhafte Eingaben gemacht (siehe unten). Ihre anderen Angaben wurden jedoch gespeichert.") .'</b>';
        else
            $msgs[] = 'msg§'. _("Die Daten wurden gespeichert!");
    }

    echo $ct->cell('&nbsp;', array('class' => 'blank', 'colspan' => '2'));

    if (is_array($msgs)) {
        foreach ($msgs as $msg) {
            parse_msg($msg,'§', "blank", 4, true);
        }
    }

    my_info( _("Bitte füllen Sie die unten aufgeführten Felder - soweit möglich und zutreffend - aus.").'<br>'
        ._("Sie können Ihre Daten noch nachträglich ändern, bis die Liste geschlossen wird."), 'blank', '3', true);
    echo $ct->closeCell();
    echo $ct->closeRow();
    echo $ct->openRow();
    echo $ct->cell('&nbsp;', array('class' => 'blank'));
    echo $ct->openCell();

    $entries = filterDatafields(DataFieldEntry::getDataFieldEntries(array($user_id, $sem_id), 'usersemdata'));

    $entries = aux_sort_entries($entries, $rule);

    echo '<form action="'.URLHelper::getLink().'" method="post">';
    echo CSRFProtection::tokenTag();
    foreach ($entries as $id => $entry) {
        if ($entry->structure->accessAllowed($perm)) {
            $color = 'black';
            if (isset($invalidEntries[$id])) {
                $color = 'red';
                $entry = $invalidEntries[$id];  // keep wrong entry to show it in corresponding form field
            }
            echo $zt->openRow();
            $data = "<font color='$color'>&nbsp;" . htmlReady($entry->getName()) . "</font></b>";
            echo $zt->cell($data);

            $data = $entry->getHTML("datafields");
            echo $zt->cell($data);
            echo $zt->closeRow();
        }
    }

    echo $zt->openRow();
    echo $zt->cell('<br>' . Button::create(_('Übernehmen')) . '<br><br>', array('colspan' => '20', 'align' => 'center'));
    echo $zt->close();
    echo '</form>';
}

$ct = new ContainerTable(array('width' => '100%', 'class' => 'blank', 'role' => 'main'));
$zt = new ZebraTable(array('width' => '100%', 'padding' => '2', 'id' => 'main_content'));

switch ($_REQUEST['display_type']) {
    case 'rtf':
        aux_rtf();
        page_close(NULL);
        break;

    case 'csv':
        aux_csv();
        page_close(NULL);
        break;

    default:

        echo $ct->openRow(array('class' => 'blank'));
        echo $ct->cell('<br>', array('colspan' => '20'));
        echo $ct->closeRow();

        echo $ct->openRow();
        echo $ct->cell('&nbsp;', array('class' => 'blank'));
        echo $ct->openCell();
        if ($rechte) {
            // add skip links
            SkipLinks::addIndex(_("Zusatzangaben"), 'main_content', 100);
            aux_html();
        } else {
            // add skip links
            SkipLinks::addIndex(_("Zusatzangaben eingeben"), 'main_content', 100);
            aux_enter_data();
        }
        echo $ct->closeCell();
        echo $ct->cell('&nbsp;', array('class' => 'blank'));
        echo $ct->closeRow();

        echo $ct->openRow(array('class' => 'blank'));
        echo $ct->cell('<br>', array('colspan' => '20'));
        echo $ct->closeRow();

        echo $ct->close();
        include 'lib/include/html_end.inc.php';
        page_close();
        break;
}

?>
