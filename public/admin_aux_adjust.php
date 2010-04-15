<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* admin_aux_lock.php - Sichtbarkeits-Administration von Stud.IP.
* Copyright (C) 2006 Till Glöggler <tgloeggl@inspace.de>
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

require '../lib/bootstrap.php';

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", user => "Seminar_User"));
$auth->login_if($auth->auth["uid"] == "nobody");
$perm->check($AUX_RULE_ADMIN_PERM ? $AUX_RULE_ADMIN_PERM : 'admin');
include ("lib/seminar_open.php"); // initialise Stud.IP-Session

require_once('lib/classes/Table.class.php');
require_once('lib/classes/ZebraTable.class.php');
require_once('lib/classes/AuxLockRules.class.php');
require_once('lib/classes/DataFieldEntry.class.php');

//Output starts here

include ('lib/include/html_head.inc.php'); // Output of html head
$CURRENT_PAGE = _("Verwaltung der Regeln für Zusatzangaben");
Navigation::activateItem('/admin/config/aux_data');

//get ID from a open Seminar
if ($SessSemName[1])
    $header_object_id = $SessSemName[1];

//Change header_line if open object
$header_line = getHeaderLine($header_object_id);
if ($header_object_id)
    $CURRENT_PAGE = $header_line." - ".$CURRENT_PAGE;

include ('lib/include/header.php');   //hier wird der "Kopf" nachgeladen

function mainView() {
    global $zt;

  $link = htmlspecialchars($GLOBALS['PHP_SELF']);

    echo $zt->openRow();
    echo $zt->cell('&nbsp;', array('class' => 'blank'));
    echo $zt->cell('<a href="'.$link.'?cmd=new_rule">'. _("Neue Regel anlegen") .'</a><br><br>', array('colspan' => '20', 'class' => 'blank'));
    echo $zt->closeRow();

    echo $zt->openHeaderRow();
    echo $zt->cell('&nbsp;', array('class' => 'blank'));
    echo $zt->cell('&nbsp;<b>Name</b>');
    echo $zt->cell('&nbsp;<b>Beschreibung</b>');
    echo $zt->cell('&nbsp;');
    echo $zt->cell('&nbsp;', array('class' => 'blank'));
    echo $zt->closeRow();

    $rules = AuxLockRules::getAllLockRules();

    foreach ((array)$rules as $id => $data) {
        echo $zt->openRow();
        echo $zt->cell('&nbsp;', array('class' => 'blank'));
        echo $zt->cell('&nbsp;'.$data['name']);
        echo $zt->cell('&nbsp;'.$data['description']);
        echo $zt->cell('<a href="'.$link.'?cmd=edit&id='.$id.'">'. makebutton('bearbeiten').'</a>&nbsp;&nbsp;&nbsp;&nbsp;'.
                       '<a href="'.$link.'?cmd=delete&id='.$id.'">'. makebutton('loeschen').'</a>', array('width' => '30%', 'align' => 'center'));
        echo $zt->cell('&nbsp;', array('class' => 'blank'));
        echo $zt->closeRow();
    }

    echo $zt->openRow();
    echo $zt->cell('<br>', array('colspan' => '20', 'class' => 'blank'));
    echo $zt->close();
}

function ruleView($rule_id = false) {
    global $zt;

    if ($rule_id) {
        $rule = AuxLockRules::getLockRuleByID($rule_id);
        $title = sprintf(_("Regel %s ändern"), $rule['name']);
    } else {
        $rule = array();
        $title = _("Neue Regel definieren");
    }

    if (Request::get('name'))        $rule['name']        = Request::get('name');
    if (Request::get('description')) $rule['description'] = Request::get('description');
    if (Request::getArray('fields')) $rule['attributes']  = Request::getArray('fields');

    echo '<form action="'.$PHP_SELF.'" method="post">';

    echo $zt->openRow();
    echo $zt->cell('&nbsp;', array('class' => 'blank'));
    echo $zt->cell('<b>'.$title.'</b>', array('colspan' => '20', 'class' => 'blank'));
    echo $zt->closeRow();

    echo $zt->openRow();
    echo $zt->cell('&nbsp;', array('class' => 'blank'));
    echo $zt->cell('&nbsp;'. _("Name:"), array('width' => '80%'));
    echo $zt->cell('<input type="text" name="name" value="'. $rule['name'] .'">', array('colspan' => '3'));
    echo $zt->cell('&nbsp;', array('class' => 'blank'));
    echo $zt->closeRow();

    echo $zt->openRow();
    echo $zt->cell('&nbsp;', array('class' => 'blank'));
    echo $zt->cell('&nbsp;'. _("Beschreibung:"));
    echo $zt->cell('<textarea name="description" cols="40" rows="4">'. $rule['description'] .'</textarea>', array('colspan' => '3'));
    echo $zt->cell('&nbsp;', array('class' => 'blank'));
    echo $zt->closeRow();

    echo $zt->openHeaderRow();
    echo $zt->cell('&nbsp;', array('class' => 'blank'));
    echo $zt->cell('&nbsp;<b>'. _("Feld") .'</b>');
    echo $zt->cell('<b>'. _("Sortierung") .'</b>', array('width' => '10%'));
    echo $zt->cell('&nbsp;&nbsp;<b>'. _("nicht aktivieren") .'</b>', array('width' => '10%'));
    echo $zt->cell('&nbsp;&nbsp;<b>'. _("aktivieren") .'</b>', array('width' => '10%'));
    echo $zt->cell('&nbsp;', array('class' => 'blank'));
    echo $zt->closeRow();

    echo $zt->openHeaderRow();
    echo $zt->cell('&nbsp;', array('class' => 'blank'));
    echo $zt->cell('&nbsp;<b>'. _("Veranstaltungsinformationen") .'</b>', array('colspan' => '4', 'class' => 'steelgraudunkel'));
    echo $zt->cell('&nbsp;', array('class' => 'blank'));
    echo $zt->closeRow();

    $semFields = AuxLockRules::getSemFields();
    $center = array('align' => 'center');

    foreach ($semFields as $id => $name) {
        echo $zt->openRow();
        echo $zt->cell('&nbsp;', array('class' => 'blank'));
        echo $zt->cell('&nbsp;'. $name);
        $checked = '';
        echo $zt->cell('<input type="text" max="3" size="3" name="order['.$id.']" value="'.(($z = $rule['order'][$id]) ? $z : '0').'">', $center);
        echo $zt->cell('<input type="radio" name="fields['.$id.']" value="0"'. (($rule['attributes'][$id]) ? '' : 'checked="checked"') .'>', $center);
        echo $zt->cell('<input type="radio" name="fields['.$id.']" value="1"'. (($rule['attributes'][$id]) ? 'checked="checked"' : '') .'>', $center);
        echo $zt->cell('&nbsp;', array('class' => 'blank'));
        echo $zt->closeRow();
    }

    $fset = array(
        'user' => _("Personenbezogene Informationen"),
        'usersemdata' => _("Zusatzinformationen")
    );

    foreach ($fset as $field => $title) {
        echo $zt->openHeaderRow();
        echo $zt->cell('&nbsp;', array('class' => 'blank'));
        echo $zt->cell('&nbsp;<b>'.$title.'</b>', array('colspan' => '4', 'class' => 'steelgraudunkel'));
        echo $zt->cell('&nbsp;', array('class' => 'blank'));
        echo $zt->closeRow();

        $entries = DataFieldStructure::getDataFieldStructures($field);

        foreach ($entries as $id => $entry) {
            echo $zt->openRow();
            echo $zt->cell('&nbsp;', array('class' => 'blank'));
            echo $zt->cell('&nbsp;'. $entry->getName());
            $checked = '';
            echo $zt->cell('<input type="text" max="3" size="3" name="order['.$id.']" value="'.(($z = $rule['order'][$id]) ? $z : '0').'">', $center);
            echo $zt->cell('<input type="radio" name="fields['.$id.']" value="0"'.(($rule['attributes'][$id]) ? '' : 'checked="checked"').'>', $center);
            echo $zt->cell('<input type="radio" name="fields['.$id.']" value="1"'.(($rule['attributes'][$id]) ? 'checked="checked"' : '').'>', $center);
            echo $zt->cell('&nbsp;', array('class' => 'blank'));
            echo $zt->closeRow();
        }
    }

    echo $zt->openRow();
    echo $zt->cell('<br>', array('colspan' => '20', 'align' => 'center', 'class' => 'blank'));
    echo $zt->closeRow();

    echo $zt->openRow();
    echo $zt->cell('<input type="image" '.makebutton('uebernehmen', 'src').'>', array('colspan' => '20', 'align' => 'center', 'class' => 'blank'));
    echo $zt->closeRow();

    echo $zt->openRow();
    echo $zt->cell('<br>', array('colspan' => '20', 'class' => 'blank'));
    echo $zt->close();

    if ($rule_id) {
        echo '<input type="hidden" name="id" value="'.$rule['lock_id'].'">', "\n";
        echo '<input type="hidden" name="cmd" value="doEdit">', "\n";
    } else {
        echo '<input type="hidden" name="cmd" value="doAdd">', "\n";
    }

    echo '</form>';
}

switch ($_REQUEST['cmd']) {
    case 'new_rule':
        $view = 'add';
        break;

    case 'doAdd':
        if (!Request::get('name')) {
            $msg['error'][] = sprintf(_("Bitte geben sie der Regel einen Namen!"));
            $view = 'add';
        } 

        if (!AuxLockRules::checkLockRule(Request::getArray('fields'))) {
            $msg['error'][] = sprintf(_("Bitte wählen Sie mindestens ein Feld aus der Kategorie \"Zusatzinformationen\" aus!"));
            $view = 'add';
        }
        
        if (!$view) {
            AuxLockRules::createLockRule(Request::get('name'), Request::get('description'), Request::getArray('fields'), Request::getArray('order'));
            $msg['success'][] = sprintf(_("Die Regel %s wurde angelegt!"), $_REQUEST['name']);
            $view = 'main';
        }
        break;

    case 'edit':
        $edit_id = $_REQUEST['id'];
        $view = 'edit';
        break;

    case 'doEdit':
        $edit_id = Request::get('id');
        if (!Request::get('name')) {
            $msg['error'][] = sprintf(_("Bitte geben sie der Regel einen Namen!"));
            $view = 'edit';
        }

        if (!AuxLockRules::checkLockRule(Request::getArray('fields'))) {
            $msg['error'][] = sprintf(_("Bitte wählen Sie mindestens ein Feld aus der Kategorie \"Zusatzinformationen\" aus!"));
            $view = 'edit';
        } 
        
        if (!$view) {
            AuxLockRules::updateLockRule(Request::get('id'), Request::get('name'), Request::get('description'), Request::getArray('fields'), Request::getArray('order'));
            $msg['success'][] = sprintf(_("Die Regel %s wurde geändert!"), $_REQUEST['name']);
            $view = 'main';
        }
        break;

    case 'delete':
        if (AuxLockRules::deleteLockRule($_REQUEST['id'])) {
            $msg['success'][] = _("Die Regel wurde gelöscht!");
        } else {
            $msg['error'][] = _("Es können nur nicht verwendete Regeln gelöscht werden!");
        }
        break;

    default:
        $view = 'main';
        break;
}

$containerTable = new ContainerTable();
echo $containerTable->openRow();
echo $containerTable->openCell(array("colspan"=>"2"));
if (is_array($msg)) {
    foreach ($msg as $type => $messages) {
        foreach ($messages as $message) {
            echo MessageBox::$type($message);
        }
    }
}

// do stuff

echo $containerTable->close();

$zt = new ZebraTable(array('width' => '100%'));

switch ($view) {
    case 'add':
        ruleView();
        break;

    case 'edit':
        ruleView($edit_id);
        break;

    default:
        mainView();
        break;
}

echo $containerTable->close();

include 'lib/include/html_end.inc.php';
page_close();
