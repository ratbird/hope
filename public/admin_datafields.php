<?
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* admin_datafields.php
*
* administrate the generic datafields
*
*
* @author       Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @module       admin_datafields.php
* @modulegroup      admin
* @package      studip_core
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// admin_datafields.php
// Administration fuer generische Datenfelder
// Copyright (C) 2003 Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
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

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));

$perm->check("root");

include ('lib/seminar_open.php'); // initialise Stud.IP-Session
require_once('lib/msg.inc.php');    //Ausgaben
require_once('config.inc.php'); //Settings....
require_once 'lib/functions.php';   //basale Funktionen
require_once('lib/visual.inc.php'); //Darstellungsfunktionen
require_once('lib/classes/DataFieldStructure.class.php');
require_once('lib/classes/DataFieldEntry.class.php');


$db=new DB_Seminar;
$cssSw=new cssClassSwitcher;
$sess->register("admin_datafields_data");


// handles output of column "Feldtyp"
function printDataFieldType ($targetID, $currStruct)
{
    echo '<font size="-1">';
    if ($currStruct == 0 || $targetID == $currStruct->getID()) {
        print "<select name=\"datafield_type\">";
        foreach (DataFieldEntry::getSupportedTypes() as $type) {
            $sel = ($currStruct != 0 && $currStruct->getType() == $type) ? 'selected' : '';
            print "<option value=\"$type\" $sel>$type</option>";
        }
        print "</select>";
    }
    else {
        print $currStruct->getType();
        if ($_GET['edit_typeparam'] == $currStruct->getID()) {  // edit button clicked?
            print '<br><a name="a">' . $currStruct->getHTMLEditor('typeparam') . "</a>";
            printf('<input type="hidden" name="datafield_id" value="%s">', $currStruct->getID());
            print ' <input type="image" name="save" src="'.$GLOBALS['ASSETS_URL'].'images/haken_transparent.gif" border="0" align="middle" title="&Auml;nderungen speichern">';
            print ' <input type="image" name="preview" src="'.$GLOBALS['ASSETS_URL'].'images/preview.gif" border="0" align="middle" title="Vorschau">';
            printf(' <a href="%s"><img src="'.$GLOBALS['ASSETS_URL'].'images/x_transparent.gif" border="0" align="middle" title="Bearbeitung abbrechen"></a>', 
                    URLHelper::getLink("?cancel=TRUE"));
        }
        elseif ($_POST['preview_x'] && $_POST['datafield_id'] == $currStruct->getID()) { // preview button clicked?
            print '<br>';
            $currStruct->setTypeParam($_POST['typeparam']);
            $sbox = DataFieldEntry::createDataFieldEntry($currStruct);
            print '<a name="a">' . $sbox->getHTML('') . '</a>';
            printf(' <a href="%s"><img src="'.$GLOBALS['ASSETS_URL'].'images/haken_transparent.gif" border="0" align="middle" title="Fertig"></a>', 
                     URLHelper::getLink("?cancel=TRUE"));
            printf(' <a href="%s"><img src="'.$GLOBALS['ASSETS_URL'].'images/edit_transparent.gif" border="0" align="middle" title="Eintr&auml;ge bearbeiten"></a>',
                     URLHelper::getLink("?edit_typeparam=".$_POST['datafield_id']."#a"));
        }
        else
            if (in_array($currStruct->getType(), array('selectbox', 'radio', 'combo'))) {
                printf(" <a href=\"%s\">", URLHelper::getLink("?edit_typeparam=".$currStruct->getID()."#a"));
                print  '<img src="'.$GLOBALS['ASSETS_URL'].'images/edit_transparent.gif" border="0" align="middle" title="Eintr&auml;ge bearbeiten"></a>';
            }
    }
    echo '</font>';
}

$CURRENT_PAGE = _("Verwaltung generischer Datenfelder");
Navigation::activateItem('/admin/config/data_fields');

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head


if ($change_datafield)
{
    $admin_datafields_data["change_datafield"] = $change_datafield;
    $admin_datafields_data["create_datafield"] = FALSE;
}

if ($create_new)
{
    $admin_datafields_data["create_datafield"] = $create_new;
    $admin_datafields_data["change_datafield"] = FALSE;
}

if ($cancel || $_GET['edit_typeparam'])
{
    $admin_datafields_data["create_datafield"] = FALSE;
    $admin_datafields_data["change_datafield"] = FALSE;
}



if ($_POST['save_x'] || $_POST['preview_x']) {  // do we edit or preview a (selectbox) type parameter?
    $struct = new DataFieldStructure(array('datafield_id' => $_POST['datafield_id']));
    $struct->setTypeParam($_POST['typeparam']);
    $struct->store();
}
elseif ($send && ($admin_datafields_data["change_datafield"]
|| $admin_datafields_data["create_datafield"])) {




    function datafield_check_array($datafield) {    // we do not want duplicated code (guter Scherz :-)
        $class = $datafield;
        if (is_array($datafield)) {
            $class = 0;
            foreach ($datafield as $val) {
                if ($val == "FALSE")
                    $class = "NULL";
                else
                    $class |= DataFieldStructure::permMask($val);
            }
        }
        elseif ($datafield == "FALSE")
            $class = "NULL";

        return $class;
    }

    $datafield_class = datafield_check_array($datafield_class);

    $fieldStruct = new DataFieldStructure(array('datafield_id' => $admin_datafields_data['change_datafield']));
    $fieldStruct->setName($datafield_name);
    $fieldStruct->setType($datafield_type);
    $fieldStruct->setObjectClass($datafield_class);
    $fieldStruct->setObjectType($admin_datafields_data["create_datafield"]);
    $fieldStruct->setPriority($datafield_priority);
    $fieldStruct->setEditPerms($datafield_edit_perms);
    $fieldStruct->setViewPerms($datafield_view_perms);

    $fieldStruct->store();
    if ($admin_datafields_data["change_datafield"]) {
        $admin_datafields_data["change_datafield"] = FALSE;
        $msg = "msg§"._("Die &Auml;nderungen am Datenfeld wurden &uuml;bernommen.");
    } else {
        $admin_datafields_data["create_datafield"] = FALSE;
        $msg = "msg§"._("Das Datenfeld wurde angelegt.");
    }
}

if ($kill_datafield) { // contains a datafield_id
    DataFieldStructure::remove($kill_datafield);
    $msg = "msg§"._("Das Datenfeld wurde gel&ouml;scht.");
}

?>
<form method="POST" name="modules" action="<?=  URLHelper::getLink("?send=TRUE#a") ?>">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td class="blank" valign="top">
            <? if (isset($msg)) { ?>
                <table width="100%">
                <? parse_msg($msg, "§", "blank", 1, FALSE); ?>
                </table>
            <? } ?>
        </td>
    </tr>
    <tr>
        <td class="blank">
        <b><font size="-1"><?=_("Datenfelder f&uuml;r Veranstaltungen")?></font></b>
        <table width="100%" border="0" cellpadding="2" cellspacing="0">
            <tr>
                <td class="steel" width="20%" align="left" valign="bottom">
                    <font size="-1">
                    <b><?=_("Name")?></b>
                    </font>
                </td>
                <td class="steel" width="12%" align="center" valign="bottom">
                    <font size="-1"><b><?=_("Feldtyp")?></b></font>
                </td>
                <td class="steel" width="20%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Veranstaltungskategorie")?></b>
                    </font>
                </td>
                <td class="steel" width="12%" align="center" valign="bottom">
                    <font size="-1"><b><?=_("ben&ouml;tigter Status")?></b></font>
                </td>
                <td class="steel" width="12%" align="center" valign="bottom">
                    <font size="-1"><b><?=_("Sichtbarkeit")?></b></font>
                </td>
                <td class="steel" width="7%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Reihenfolge")?></b>
                    </font>
                </td>
                <td class="steel" width="10%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Eintr&auml;ge")?></b>
                    </font>
                </td>
                <td class="steel" width="7%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Aktion")?></b>
                    </font>
                </td>
            </tr>
            <?
            $datafields_list = DataFieldStructure::getDataFieldStructures("sem");
            foreach ($datafields_list as $key=>$val) {
                $cssSw->switchClass();
                ?>
            <tr>
                <td class="<?=$cssSw->getClass()?>" align="left">
                    <font size="-1">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        print "<a name=\"a\"></a>";
                        printf ("<input type=\"TEXT\" maxlength=\"255\" size=\"25\" style=\"{font-size:8 pt; width: 90%%;}\" value=\"%s\" name=\"datafield_name\">", $val->getName());
                    }
                    else
                        print $val->getName();
                    ?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                <?
                    printDataFieldType($admin_datafields_data['change_datafield'], $val);
                ?>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        print "<select name=\"datafield_class\" style=\"{font-size:8 pt;}\">";
                        echo "<option value=\"FALSE\">". _("alle") ."</option>";
                        foreach ($SEM_CLASS as $key2=>$val2)
                            printf ("<option %s value=\"%s\">%s</option>", ($val->getObjectClass() == $key2) ? "selected" : "", $key2, $val2["name"]);
                        print "</select>";
                        }
                    else
                        print ($val->getObjectClass()) ? $SEM_CLASS[$val->getObjectClass()]["name"] : _("alle")
                    ?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        print "<select name=\"datafield_edit_perms\" style=\"{font-size:8 pt;}\">";
                        printf ("<option %s value=\"user\">user</option>", ($val->getEditPerms() == "user") ? "selected" : "");
                        printf ("<option %s value=\"autor\">autor</option>", ($val->getEditPerms() == "autor") ? "selected" : "");
                        printf ("<option %s value=\"tutor\">tutor</option>", ($val->getEditPerms() == "tutor") ? "selected" : "");
                        printf ("<option %s value=\"dozent\">dozent</option>", ($val->getEditPerms() == "dozent") ? "selected" : "");
                        printf ("<option %s value=\"admin\">admin</option>", ($val->getEditPerms() == "admin") ? "selected" : "");
                        printf ("<option %s value=\"root\">root</option>", ($val->getEditPerms() == "root") ? "selected" : "");
                        print "</select>";
                    } else
                        print $val->getEditPerms()
                    ?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        print "<select name=\"datafield_view_perms\" style=\"{font-size:8 pt;}\">";
                        printf ("<option %s value=\"all\">%s</option>", ($val->getViewPerms() == "all") ? "selected" : "", _("alle"));
                        printf ("<option %s value=\"user\">user</option>", ($val->getViewPerms() == "user") ? "selected" : "");
                        printf ("<option %s value=\"autor\">autor</option>", ($val->getViewPerms() == "autor") ? "selected" : "");
                        printf ("<option %s value=\"tutor\">tutor</option>", ($val->getViewPerms() == "tutor") ? "selected" : "");
                        printf ("<option %s value=\"dozent\">dozent</option>", ($val->getViewPerms() == "dozent") ? "selected" : "");
                        printf ("<option %s value=\"admin\">admin</option>", ($val->getViewPerms() == "admin") ? "selected" : "");
                        printf ("<option %s value=\"root\">root</option>", ($val->getViewPerms() == "root") ? "selected" : "");
                        print "</select>";
                    }
                    elseif ($val->getViewPerms() == "all")
                        print _("alle");
                    else
                        print $val->getViewPerms();
                    ?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID())
                        printf ("<input type=\"TEXT\" maxlength=\"10\" size=\"5\" style=\"{font-size:8 pt; width: 30%%; text-align: center;}\" value=\"%s\" name=\"datafield_priority\">", $val->getPriority());
                    else
                        print $val->getPriority()
                    ?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                    <?=$val->getCachedNumEntries()?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        print  ' <input type="IMAGE" name="send_datafield" src="'.$GLOBALS['ASSETS_URL'].'images/haken_transparent.gif" border="0" title="Änderungen übernehmen">';
                        printf ('<a href="%s"><img src="'.$GLOBALS['ASSETS_URL'].'images/x_transparent.gif" border="0" title="Bearbeitung abbrechen"></a>', 
                                URLHelper::getLink("?cancel=TRUE"));
                    }
                    else
                        printf (" <a href=\"%s\"><img src=\"".$GLOBALS['ASSETS_URL']."images/edit_transparent.gif\" border=\"0\" %s></a>", 
                                URLHelper::getLink("?change_datafield=".$val->getID()."#a"), tooltip(_("Datenfeld ändern")));
                    printf (" <a href=\"%s\"><img src=\"".$GLOBALS['ASSETS_URL']."images/trash.gif\" border=\"0\" %s></a>", 
                            URLHelper::getLink("?kill_datafield=".$val->getID()), tooltip(_("Datenfeld löschen (wird von keiner Veranstaltung verwendet)")));
                    ?>
                </td>
            </tr>
            <?
            }  // foreach
            if ($admin_datafields_data["create_datafield"] == "sem") {
                $cssSw->switchClass()
            ?>
            <tr>
                <td class="<?=$cssSw->getClass()?>" align="left">
                    <a name="a"></a>
                    <font size="-1">
                        <input type="TEXT" maxlength="255" size="25" style="{font-size:8 pt; width: 90%;}" name="datafield_name">
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                <?
                    printDataFieldType(0, 0);
                ?>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                        <select name="datafield_class" style="{font-size:8 pt;}">";
                        <option value="FALSE"><?=_("alle")?></option>
                        <?
                        foreach ($SEM_CLASS as $key=>$val)
                            printf ("<option value=\"%s\">%s</option>", $key, $val['name']);
                        ?>
                        </select>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                        <select name="datafield_edit_perms" style="{font-size:8 pt;}">
                        <option value="user">user</option>
                        <option value="autor">autor</option>
                        <option value="tutor">tutor</option>
                        <option value="dozent">dozent</option>
                        <option value="admin">admin</option>
                        <option value="root">root</option>
                        </select>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                <? //New possibility: Set rights for visibility ?>
                    <font size="-1">
                        <select name="datafield_view_perms" style="{font-size:8 pt;}">
                        <option value="all"><?=_("alle")?></option>
                        <option value="user">user</option>
                        <option value="autor">autor</option>
                        <option value="tutor">tutor</option>
                        <option value="dozent">dozent</option>
                        <option value="admin">admin</option>
                        <option value="root">root</option>
                        </select>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                        <input type="TEXT" maxlength="10" size="5" style="{font-size:8 pt; width: 30%; text-align: center;}" name="datafield_priority">
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    &nbsp;
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center" nowrap>
                    <input type="IMAGE" name="send" src="<?= $GLOBALS['ASSETS_URL'] ?>images/haken_transparent.gif" border="0" <?=tooltip(_("Datenfeld speichern"))?>>
                    <a href="<?= URLHelper::getLink("?cancel=TRUE") ?>"><img src="<?= $GLOBALS['ASSETS_URL'] ?>images/x_transparent.gif" border="0" <?=tooltip(_("Anlegen abbrechen"))?>>
                </td>
            </tr>
            <?
            }
            ?>
        </table>
        <?
        if ($admin_datafields_data["create_datafield"] != "sem") {
            ?><a href="<?= URLHelper::getLink("?create_new=sem#a") ?>"><img src="<?= $GLOBALS['ASSETS_URL'] ?>images/add_right.gif" border="0" <?=tooltip(_("Neues Datenfeld für Veranstaltungen anlegen"))?>></a><?
        }
        ?>
        <br><br>
        </td>
    </tr>
    <tr>
        <td class="blank" colspan=2>
        <b><font size="-1"><?=_("Datenfelder f&uuml;r Einrichtungen")?></font></b>
        <table width = "100%" border="0" cellpadding="2" cellspacing="0" align="center">
            <tr>
                <td class="steel" width="20%" align="left" valign="bottom">

                    <font size="-1">
                    <b><?=_("Name")?></b>
                    </font>
                </td>
                <td class="steel" width="12%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Feldtyp")?></b>
                    </font>
                </td>
                <td class="steel" width="20%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Einrichtungstyp")?></b>
                    </font>
                </td>
                <td class="steel" width="12%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("ben&ouml;tigter Status")?></b>
                    </font>
                </td>
                <td class="steel" width="12%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Sichtbarkeit")?></b>
                    </font>
                </td>
                <td class="steel" width="7%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Reihenfolge")?></b>
                    </font>
                </td>
                <td class="steel" width="10%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Eintr&auml;ge")?></b>
                    </font>
                </td>
                <td class="steel" width="7%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Aktion")?></b>
                    </font>
                </td>
            </tr>
            <?
            $datafields_list = DataFieldStructure::getDataFieldStructures("inst");
            $cssSw->resetClass();
            foreach ($datafields_list as $key=>$val) {
                $cssSw->switchClass()
                ?>
            <tr>
                <td class="<?=$cssSw->getClass()?>" align="left">
                    <font size="-1">
                    <?
                          if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        print "<a name=\"a\"></a>";
                              print "<input type=\"TEXT\" maxlength=\"255\" size=\"25\" style=\"{font-size:8 pt; width: 90%%;}\" ";
                               print "value=\"".$val->getName()."\" name=\"datafield_name\">";
                          }
                          else
                              print $val->getName()
                    ?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                <?
                    printDataFieldType($admin_datafields_data['change_datafield'], $val);
                ?>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        print "<select name=\"datafield_class\" style=\"{font-size:8 pt}\">";
                        echo "<option value=\"FALSE\">". _("alle") ."</option>";
                        foreach ($INST_TYPE as $key2=>$val2)
                            printf ("<option %s value=\"%s\">%s</option>", ($val->getObjectClass() == $key2) ? "selected" : "", $key2, $val2["name"]);
                        print "</select>";
                        }
                    else
                        print ($val->getObjectClass()) ? $INST_TYPE[$val->getObjectClass()]["name"] : _("alle")
                    ?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        print "<select name=\"datafield_edit_perms\" style=\"{font-size:8 pt;}\">";
                        printf ("<option %s value=\"user\">user</option>", ($val->getEditPerms() == "user") ? "selected" : "");
                        printf ("<option %s value=\"autor\">autor</option>", ($val->getEditPerms() == "autor") ? "selected" : "");
                        printf ("<option %s value=\"tutor\">tutor</option>", ($val->getEditPerms() == "tutor") ? "selected" : "");
                        printf ("<option %s value=\"dozent\">dozent</option>", ($val->getEditPerms() == "dozent") ? "selected" : "");
                        printf ("<option %s value=\"admin\">admin</option>", ($val->getEditPerms() == "admin") ? "selected" : "");
                        printf ("<option %s value=\"root\">root</option>", ($val->getEditPerms() == "root") ? "selected" : "");
                        print "</select>";
                    } else
                        print $val->getEditPerms()
                    ?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        print "<select name=\"datafield_view_perms\" style=\"{font-size:8 pt;}\">";
                        printf ("<option %s value=\"all\">%s</option>", ($val->getViewPerms() == "all") ? "selected" : "", _("alle"));
                        printf ("<option %s value=\"user\">user</option>", ($val->getViewPerms() == "user") ? "selected" : "");
                        printf ("<option %s value=\"autor\">autor</option>", ($val->getViewPerms() == "autor") ? "selected" : "");
                        printf ("<option %s value=\"tutor\">tutor</option>", ($val->getViewPerms() == "tutor") ? "selected" : "");
                        printf ("<option %s value=\"dozent\">dozent</option>", ($val->getViewPerms() == "dozent") ? "selected" : "");
                        printf ("<option %s value=\"admin\">admin</option>", ($val->getViewPerms() == "admin") ? "selected" : "");
                        printf ("<option %s value=\"root\">root</option>", ($val->getViewPerms() == "root") ? "selected" : "");
                        print "</select>";
                    } else
                        if ($val->getViewPerms() == "all") {
                            print _("alle");
                        }
                        else {
                            print $val->getViewPerms();
                        }
                    ?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        printf ("<input type=\"TEXT\" maxlength=\"10\" size=\"5\" style=\"{font-size:8 pt; width: 30%%; text-align: center;}\" value=\"%s\" name=\"datafield_priority\">", $val->getPriority());
                    } else
                        print $val->getPriority()
                    ?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                    <?=$val->getCachedNumEntries()?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        print  ' <input type="IMAGE" name="send_datafield" src="'.$GLOBALS['ASSETS_URL'].'images/haken_transparent.gif" border="0" title="Änderungen übernehmen">';
                        printf ('<a href="%s"><img src="'.$GLOBALS['ASSETS_URL'].'images/x_transparent.gif" border="0" title="Bearbeitung abbrechen"></a>', 
                                URLHelper::getLink("?cancel=TRUE"));
                    }
                    else
                        printf (" <a href=\"%s\"><img src=\"".$GLOBALS['ASSETS_URL']."images/edit_transparent.gif\" border=\"0\" %s></a>", 
                                URLHelper::getLink("?change_datafield=".$val->getID()."#a"), tooltip(_("Datenfeld ändern")));
                    printf (" <a href=\"%s\"><img src=\"".$GLOBALS['ASSETS_URL']."images/trash.gif\" border=\"0\" %s></a>", 
                            URLHelper::getLink("?kill_datafield=".$val->getID()), tooltip(_("Datenfeld löschen (wird von keiner Veranstaltung verwendet)")));
                    ?>
                </td>
            </tr>
                <?
            }
            if ($admin_datafields_data["create_datafield"] == "inst") {
                $cssSw->switchClass()
            ?>
            <tr>
                <td class="<?=$cssSw->getClass()?>" align="left">
                    <a name="a"></a>
                    <font size="-1">
                        <input type="TEXT" maxlength="255" size="25" style="{font-size:8 pt; width: 90%}" name="datafield_name">
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                <?
                    printDataFieldType(0, 0);
                ?>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                        <select name="datafield_class" style="{font-size:8 pt;}">";
                        <option value="FALSE"><?=_("alle")?></option>
                        <?
                        foreach ($INST_TYPE as $key=>$val) {
                            printf ("<option value=\"%s\">%s</option>", $key, $val['name']);
                        }
                        ?>
                        </select>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                        <select name="datafield_edit_perms" style="{font-size:8 pt;}">";
                        <option value="user">user</option>
                        <option value="autor">autor</option>
                        <option value="tutor">tutor</option>
                        <option value="dozent">dozent</option>
                        <option value="admin">admin</option>
                        <option value="root">root</option>
                        </select>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                        <select name="datafield_view_perms" style="{font-size:8 pt;}">";
                        <option value="all"><?=_("alle")?></option>
                        <option value="user">user</option>
                        <option value="autor">autor</option>
                        <option value="tutor">tutor</option>
                        <option value="dozent">dozent</option>
                        <option value="admin">admin</option>
                        <option value="root">root</option>
                        </select>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                        <input type="TEXT" maxlength="10" size="5" style="{font-size:8 pt; width: 30%; text-align: center;}" name="datafield_priority">
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    &nbsp;
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center" nowrap>
                    <input type="IMAGE" name="send" src="<?= $GLOBALS['ASSETS_URL'] ?>images/haken_transparent.gif" border="0" <?=tooltip(_("Datenfeld speichern"))?>>
                    <a href="<?= URLHelper::getLink("?cancel=TRUE") ?>"><img src="<?= $GLOBALS['ASSETS_URL'] ?>images/x_transparent.gif" border="0" <?=tooltip(_("Anlegen abbrechen"))?>>
                </td>
            </tr>
            <?
            }
            ?>
        </table>
        <?
        if ($admin_datafields_data["create_datafield"] != "inst") {
            ?><a href="<?= URLHelper::getLink("?create_new=inst#a") ?>"><img src="<?= $GLOBALS['ASSETS_URL'] ?>images/add_right.gif" border="0" <?=tooltip(_("Neues Datenfeld für Einrichtungen anlegen"))?>></a><?
        }
        ?>
        <br><br>
        </td>
    </tr>
    <tr>
        <td class="blank" colspan=2>
        <b><font size="-1"><?=_("Datenfelder f&uuml;r Nutzer")?></font></b>
        <table width = "100%" border="0" cellpadding="2" cellspacing="0" align="center">
            <tr>
                <td class="steel" width="20%" align="left" valign="bottom">

                    <font size="-1">
                    <b><?=_("Name")?></b>
                    </font>
                </td>
                <td class="steel" width="12%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Feldtyp")?></b>
                    </font>
                </td>
                <td class="steel" width="20%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Nutzerstatus")?></b>
                    </font>
                </td>
                <td class="steel" width="12%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("ben&ouml;tigter Status")?></b>
                    </font>
                </td>
                <td class="steel" width="12%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Sichtbarkeit")?></b>
                    </font>
                </td>
                <td class="steel" width="7%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Reihenfolge")?></b>
                    </font>
                </td>
                <td class="steel" width="10%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Eintr&auml;ge")?></b>
                    </font>
                </td>
                <td class="steel" width="7%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Aktion")?></b>
                    </font>
                </td>
            </tr>
            <?
            $datafields_list = DataFieldStructure::getDataFieldStructures("user");
            $cssSw->resetClass();
            foreach ($datafields_list as $key=>$val) {
                $cssSw->switchClass()
                ?>
            <tr>
                <td class="<?=$cssSw->getClass()?>" align="left">
                    <font size="-1">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        print "<a name=\"a\"></a>";
                        printf ("<input type=\"TEXT\" maxlength=\"255\" size=\"25\" style=\"{font-size:8 pt; width: 90%%;}\" value=\"%s\" name=\"datafield_name\">", $val->getName());
                    } else
                        print $val->getName()
                    ?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                <?
                    printDataFieldType($admin_datafields_data['change_datafield'], $val);
                ?>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        print "<select name=\"datafield_class[]\"  multiple size=\"7\" style=\"{font-size:8 pt;}\">";
                        printf ("<option %s value=\"FALSE\">"._("alle")."</option>", (!$val->getObjectClass()) ? "selected" : "");
                        printf ("<option %s value=\"user\">user</option>", ($val->getObjectClass() & DataFieldStructure::permMask("user")) ? "selected" : "");
                        printf ("<option %s value=\"autor\">autor</option>", ($val->getObjectClass() & DataFieldStructure::permMask("autor")) ? "selected" : "");
                        printf ("<option %s value=\"tutor\">tutor</option>", ($val->getObjectClass() & DataFieldStructure::permMask("tutor")) ? "selected" : "");
                        printf ("<option %s value=\"dozent\">dozent</option>", ($val->getObjectClass() & DataFieldStructure::permMask("dozent")) ? "selected" : "");
                        printf ("<option %s value=\"admin\">admin</option>", ($val->getObjectClass() & DataFieldStructure::permMask("admin")) ? "selected" : "");
                        printf ("<option %s value=\"root\">root</option>", ($val->getObjectClass() & DataFieldStructure::permMask("root")) ? "selected" : "");
                        print "</select>";
                    }
                    else
                        print ($val->getObjectClass()) ? DataFieldStructure::getReadableUserClass($val->getObjectClass()) : _("alle");
                    ?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        print "<select name=\"datafield_edit_perms\" style=\"{font-size:8 pt;}\">";
                        printf ("<option %s value=\"user\">user</option>", ($val->getEditPerms() == "user") ? "selected" : "");
                        printf ("<option %s value=\"autor\">autor</option>", ($val->getEditPerms() == "autor") ? "selected" : "");
                        printf ("<option %s value=\"tutor\">tutor</option>", ($val->getEditPerms() == "tutor") ? "selected" : "");
                        printf ("<option %s value=\"dozent\">dozent</option>", ($val->getEditPerms() == "dozent") ? "selected" : "");
                        printf ("<option %s value=\"admin\">admin</option>", ($val->getEditPerms() == "admin") ? "selected" : "");
                        printf ("<option %s value=\"root\">root</option>", ($val->getEditPerms() == "root") ? "selected" : "");
                        print "</select>";
                    } else
                        print $val->getEditPerms()
                    ?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        print "<select name=\"datafield_view_perms\" style=\"{font-size:8 pt;}\">";
                        printf ("<option %s value=\"all\">%s</option>", ($val->getViewPerms() == "user") ? "selected" : "", _("alle"));
                        printf ("<option %s value=\"user\">user</option>", ($val->getViewPerms() == "user") ? "selected" : "");
                        printf ("<option %s value=\"autor\">autor</option>", ($val->getViewPerms() == "autor") ? "selected" : "");
                        printf ("<option %s value=\"tutor\">tutor</option>", ($val->getViewPerms() == "tutor") ? "selected" : "");
                        printf ("<option %s value=\"dozent\">dozent</option>", ($val->getViewPerms() == "dozent") ? "selected" : "");
                        printf ("<option %s value=\"admin\">admin</option>", ($val->getViewPerms() == "admin") ? "selected" : "");
                        printf ("<option %s value=\"root\">root</option>", ($val->getViewPerms() == "root") ? "selected" : "");
                        print "</select>";
                    } else
                        if ($val->getViewPerms() == "all") {
                            print _("alle");
                        }
                        else {
                            print $val->getViewPerms();
                        }
                    ?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        printf ("<input type=\"TEXT\" maxlength=\"10\" size=\"5\" style=\"{font-size:8 pt; width: 30%%; text-align: center;}\" value=\"%s\" name=\"datafield_priority\">", $val->getPriority());
                    } else
                        print $val->getPriority()
                    ?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                    <?=$val->getCachedNumEntries()?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        print  ' <input type="IMAGE" name="send_datafield" src="'.$GLOBALS['ASSETS_URL'].'images/haken_transparent.gif" border="0" title="Änderungen übernehmen">';
                        printf ('<a href="%s"><img src="'.$GLOBALS['ASSETS_URL'].'images/x_transparent.gif" border="0" title="Bearbeitung abbrechen"></a>', 
                                URLHelper::getLink("?cancel=TRUE"));
                    }
                    else
                        printf (" <a href=\"%s\"><img src=\"".$GLOBALS['ASSETS_URL']."images/edit_transparent.gif\" border=\"0\" %s></a>", 
                                URLHelper::getLink("?change_datafield=".$val->getID()."#a"), tooltip(_("Datenfeld ändern")));
                    printf (" <a href=\"%s\"><img src=\"".$GLOBALS['ASSETS_URL']."images/trash.gif\" border=\"0\" %s></a>", 
                            URLHelper::getLink("?kill_datafield=".$val->getID()), tooltip(_("Datenfeld löschen (wird von keiner Veranstaltung verwendet)")));
                    ?>
                </td>
            </tr>
                <?
            }
            if ($admin_datafields_data["create_datafield"] == "user") {
                $cssSw->switchClass()
            ?>
            <tr>
                <td class="<?=$cssSw->getClass()?>" align="left">
                    <a name="a"></a>
                    <font size="-1">
                        <input type="TEXT" maxlength="255" size="25" style="{font-size:8 pt; width: 90%;}" name="datafield_name">
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                <?
                    printDataFieldType(0, 0);
                ?>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                        <select name="datafield_class[]"  multiple size="7" style="{font-size:8 pt;}">";
                        <option value="FALSE"><?=_("alle")?></option>
                        <option value="user">user</option>
                        <option value="autor">autor</option>
                        <option value="tutor">tutor</option>
                        <option value="dozent">dozent</option>
                        <option value="admin">admin</option>
                        <option value="root">root</option>
                        </select>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                        <select name="datafield_edit_perms" style="{font-size:8 pt;}">";
                        <option value="user">user</option>
                        <option value="autor">autor</option>
                        <option value="tutor">tutor</option>
                        <option value="dozent">dozent</option>
                        <option value="admin">admin</option>
                        <option value="root">root</option>
                        </select>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                        <select name="datafield_view_perms" style="{font-size:8 pt;}">";
                        <option value="all"><?=_("alle")?></option>
                        <option value="user">user</option>
                        <option value="autor">autor</option>
                        <option value="tutor">tutor</option>
                        <option value="dozent">dozent</option>
                        <option value="admin">admin</option>
                        <option value="root">root</option>
                        </select>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                        <input type="TEXT" maxlength="10" size="5" style="{font-size:8 pt; width: 30%; text-align: center;}" name="datafield_priority">
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    &nbsp;
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center" nowrap>
                    <input type="IMAGE" name="send" src="<?= $GLOBALS['ASSETS_URL'] ?>images/haken_transparent.gif" border="0" <?=tooltip(_("Datenfeld speichern"))?> >
                    <a href="<?= URLHelper::getLink("?cancel=TRUE") ?>"><img src="<?= $GLOBALS['ASSETS_URL'] ?>images/x_transparent.gif" border="0" <?=tooltip(_("Anlegen abbrechen"))?> ></a>
                </td>
            </tr>
            <?
            }
            ?>
        </table>
        <?
        if ($admin_datafields_data["create_datafield"] != "user") {
            ?><a href="<?= URLHelper::getLink("?create_new=user#a") ?>"><img src="<?= $GLOBALS['ASSETS_URL'] ?>images/add_right.gif" border="0" <?=tooltip(_("Neues Datenfeld für Nutzer anlegen"))?>></a><?
        }
        ?>
        <br><br>
        </td>
    </tr>
    <tr>
        <td class="blank" colspan=2>
        <b><font size="-1"><?=_("Datenfelder f&uuml;r Nutzerrollen in Einrichtungen")?></font></b>
        <table width = "100%" border="0" cellpadding="2" cellspacing="0" align="center">
            <tr>
                <td class="steel" width="20%" align="left" valign="bottom">

                    <font size="-1">
                    <b><?=_("Name")?></b>
                    </font>
                </td>
                <td class="steel" width="12%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Feldtyp")?></b>
                    </font>
                </td>
                <td class="steel" width="20%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Nutzerstatus")?></b>
                    </font>
                </td>
                <td class="steel" width="12%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("ben&ouml;tigter Status")?></b>
                    </font>
                </td>
                <td class="steel" width="12%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Sichtbarkeit")?></b>
                    </font>
                </td>
                <td class="steel" width="7%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Reihenfolge")?></b>
                    </font>
                </td>
                <td class="steel" width="10%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Eintr&auml;ge")?></b>
                    </font>
                </td>
                <td class="steel" width="7%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Aktion")?></b>
                    </font>
                </td>
            </tr>
            <?
            $datafields_list = DataFieldStructure::getDataFieldStructures("userinstrole");
            $cssSw->resetClass();
            foreach ($datafields_list as $key=>$val) {
                $cssSw->switchClass()
                ?>
            <tr>
                <td class="<?=$cssSw->getClass()?>" align="left">
                    <font size="-1">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        print "<a name=\"a\"></a>";
                        printf ("<input type=\"TEXT\" maxlength=\"255\" size=\"25\" style=\"{font-size:8 pt; width: 90%%;}\" value=\"%s\" name=\"datafield_name\">", $val->getName());
                    } else
                        print $val->getName()
                    ?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                <?
                    printDataFieldType($admin_datafields_data['change_datafield'], $val);
                ?>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        print "<select name=\"datafield_class[]\"  multiple size=\"7\" style=\"{font-size:8 pt;}\">";
                        printf("<option %s value=\"FALSE\">"._("alle")."</option>", (!$val->getObjectClass()) ? "selected" : "");
                        printf("<option %s value=\"user\">user</option>", ($val->getObjectClass() & DataFieldStructure::permMask("user")) ? "selected" : "");
                        printf("<option %s value=\"autor\">autor</option>", ($val->getObjectClass() & DataFieldStructure::permMask("autor")) ? "selected" : "");
                        printf("<option %s value=\"tutor\">tutor</option>", ($val->getObjectClass() & DataFieldStructure::permMask("tutor")) ? "selected" : "");
                        printf("<option %s value=\"dozent\">dozent</option>", ($val->getObjectClass() & DataFieldStructure::permMask("dozent")) ? "selected" : "");
                        printf("<option %s value=\"admin\">admin</option>", ($val->getObjectClass() & DataFieldStructure::permMask("admin")) ? "selected" : "");
                        printf("<option %s value=\"root\">root</option>", ($val->getObjectClass() & DataFieldStructure::permMask("root")) ? "selected" : "");
                        print "</select>";
                    }
                    else
                        print ($val->getObjectClass()) ? DataFieldStructure::getReadableUserClass($val->getObjectClass()) : _("alle");
                    ?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        print "<select name=\"datafield_edit_perms\" style=\"{font-size:8 pt;}\">";
                        printf("<option %s value=\"user\">user</option>", ($val->getEditPerms() == "user") ? "selected" : "");
                        printf("<option %s value=\"autor\">autor</option>", ($val->getEditPerms() == "autor") ? "selected" : "");
                        printf("<option %s value=\"tutor\">tutor</option>", ($val->getEditPerms() == "tutor") ? "selected" : "");
                        printf("<option %s value=\"dozent\">dozent</option>", ($val->getEditPerms() == "dozent") ? "selected" : "");
                        printf("<option %s value=\"admin\">admin</option>", ($val->getEditPerms() == "admin") ? "selected" : "");
                        printf("<option %s value=\"root\">root</option>", ($val->getEditPerms() == "root") ? "selected" : "");
                        print "</select>";
                    } else
                        print $val->getEditPerms()
                    ?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        print "<select name=\"datafield_view_perms\" style=\"{font-size:8 pt;}\">";
                        printf ("<option %s value=\"all\">%s</option>", ($val->getViewPerms() == "user") ? "selected" : "", _("alle"));
                        printf ("<option %s value=\"user\">user</option>", ($val->getViewPerms() == "user") ? "selected" : "");
                        printf ("<option %s value=\"autor\">autor</option>", ($val->getViewPerms() == "autor") ? "selected" : "");
                        printf ("<option %s value=\"tutor\">tutor</option>", ($val->getViewPerms() == "tutor") ? "selected" : "");
                        printf ("<option %s value=\"dozent\">dozent</option>", ($val->getViewPerms() == "dozent") ? "selected" : "");
                        printf ("<option %s value=\"admin\">admin</option>", ($val->getViewPerms() == "admin") ? "selected" : "");
                        printf ("<option %s value=\"root\">root</option>", ($val->getViewPerms() == "root") ? "selected" : "");
                        print "</select>";
                    }
                    elseif ($val->getViewPerms() == "all")
                        print _("alle");
                    else
                        print $val->getViewPerms();
                    ?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        printf ("<input type=\"TEXT\" maxlength=\"10\" size=\"5\" style=\"{font-size:8 pt; width: 30%%; text-align: center;}\" value=\"%s\" name=\"datafield_priority\">", $val->getPriority());
                    } else
                        print $val->getPriority()
                    ?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                    <?=$val->getCachedNumEntries()?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        print  ' <input type="IMAGE" name="send_datafield" src="'.$GLOBALS['ASSETS_URL'].'images/haken_transparent.gif" border="0" title="Änderungen übernehmen">';
                        printf ('<a href="%s"><img src="'.$GLOBALS['ASSETS_URL'].'images/x_transparent.gif" border="0" title="Bearbeitung abbrechen"></a>', 
                                URLHelper::getLink("?cancel=TRUE"));
                    }
                    else
                        printf (" <a href=\"%s\"><img src=\"".$GLOBALS['ASSETS_URL']."images/edit_transparent.gif\" border=\"0\" %s></a>", 
                                URLHelper::getLink("?change_datafield=".$val->getID()."#a"), tooltip(_("Datenfeld ändern")));
                    printf (" <a href=\"%s\"><img src=\"".$GLOBALS['ASSETS_URL']."images/trash.gif\" border=\"0\" %s></a>", 
                            URLHelper::getLink("?kill_datafield=".$val->getID()), tooltip(_("Datenfeld löschen (wird von keiner Veranstaltung verwendet)")));
                    ?>
                </td>
            </tr>
                <?
            }
            if ($admin_datafields_data["create_datafield"] == "userinstrole") {
                $cssSw->switchClass()
            ?>
            <tr>
                <td class="<?=$cssSw->getClass()?>" align="left">
                    <a name="a"></a>
                    <font size="-1">
                        <input type="TEXT" maxlength="255" size="25" style="{font-size:8 pt; width: 90%;}" name="datafield_name">
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                <?
                    printDataFieldType(0, 0);
                ?>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                        <select name="datafield_class[]"  multiple size="7" style="{font-size:8 pt;}">";
                        <option value="FALSE"><?=_("alle")?></option>
                        <option value="user">user</option>
                        <option value="autor">autor</option>
                        <option value="tutor">tutor</option>
                        <option value="dozent">dozent</option>
                        <option value="admin">admin</option>
                        <option value="root">root</option>
                        </select>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                        <select name="datafield_edit_perms" style="{font-size:8 pt;}">";
                        <option value="user">user</option>
                        <option value="autor">autor</option>
                        <option value="tutor">tutor</option>
                        <option value="dozent">dozent</option>
                        <option value="admin">admin</option>
                        <option value="root">root</option>
                        </select>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                        <select name="datafield_view_perms" style="{font-size:8 pt;}">";
                        <option value="all"><?=_("alle")?></option>
                        <option value="user">user</option>
                        <option value="autor">autor</option>
                        <option value="tutor">tutor</option>
                        <option value="dozent">dozent</option>
                        <option value="admin">admin</option>
                        <option value="root">root</option>
                        </select>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                        <input type="TEXT" maxlength="10" size="5" style="{font-size:8 pt; width: 30%; text-align: center;}" name="datafield_priority">
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    &nbsp;
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center" nowrap>
                    <input type="IMAGE" name="send" src="<?= $GLOBALS['ASSETS_URL'] ?>images/haken_transparent.gif" border="0" <?=tooltip(_("Datenfeld speichern"))?> >
                    <a href="<?= URLHelper::getLink("?cancel=TRUE") ?>"><img src="<?= $GLOBALS['ASSETS_URL'] ?>images/x_transparent.gif" border="0" <?=tooltip(_("Anlegen abbrechen"))?> ></a>
                </td>
            </tr>
            <?
            }
            ?>
        </table>
        <?
        if ($admin_datafields_data["create_datafield"] != "userinstrole") {
            ?><a href="<?= URLHelper::getLink("?create_new=userinstrole#a") ?>"><img src="<?= $GLOBALS['ASSETS_URL'] ?>images/add_right.gif" border="0" <?=tooltip(_("Neues Datenfeld für Nutzer anlegen"))?>></a><?
        }
        ?>
        <br><br>
        </td>
    </tr>


    <?
     /*
      * Datenfelder für Nutzer-Zusatzangaben in Veranstaltungen
        */
    ?>
    <tr>
        <td class="blank" colspan=2>
        <b><font size="-1"><?=_("Datenfelder f&uuml;r Nutzer-Zusatzangaben in Veranstaltungen")?></font></b>
        <table width = "100%" border="0" cellpadding="2" cellspacing="0" align="center">
            <tr>
                <td class="steel" width="20%" align="left" valign="bottom">

                    <font size="-1">
                    <b><?=_("Name")?></b>
                    </font>
                </td>
                <td class="steel" width="12%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Feldtyp")?></b>
                    </font>
                </td>
                <td class="steel" width="20%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Nutzerstatus")?></b>
                    </font>
                </td>
                <td class="steel" width="12%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("ben&ouml;tigter Status")?></b>
                    </font>
                </td>
                <td class="steel" width="12%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Sichtbarkeit")?></b>
                    </font>
                </td>
                <td class="steel" width="7%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Reihenfolge")?></b>
                    </font>
                </td>
                <td class="steel" width="10%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Eintr&auml;ge")?></b>
                    </font>
                </td>
                <td class="steel" width="7%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Aktion")?></b>
                    </font>
                </td>
            </tr>
            <?
            $datafields_list = DataFieldStructure::getDataFieldStructures("usersemdata");
            $cssSw->resetClass();
            foreach ($datafields_list as $key=>$val) {
                $cssSw->switchClass()
                ?>
            <tr>
                <td class="<?=$cssSw->getClass()?>" align="left">
                    <font size="-1">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        print "<a name=\"a\"></a>";
                        printf ("<input type=\"TEXT\" maxlength=\"255\" size=\"25\" style=\"{font-size:8 pt; width: 90%%;}\" value=\"%s\" name=\"datafield_name\">", $val->getName());
                    } else
                        print $val->getName()
                    ?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                <?
                    printDataFieldType($admin_datafields_data['change_datafield'], $val);
                ?>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        print "<select name=\"datafield_class[]\"  multiple size=\"7\" style=\"{font-size:8 pt;}\">";
                        printf("<option %s value=\"FALSE\">"._("alle")."</option>", (!$val->getObjectClass()) ? "selected" : "");
                        printf("<option %s value=\"user\">user</option>", ($val->getObjectClass() & DataFieldStructure::permMask("user")) ? "selected" : "");
                        printf("<option %s value=\"autor\">autor</option>", ($val->getObjectClass() & DataFieldStructure::permMask("autor")) ? "selected" : "");
                        printf("<option %s value=\"tutor\">tutor</option>", ($val->getObjectClass() & DataFieldStructure::permMask("tutor")) ? "selected" : "");
                        printf("<option %s value=\"dozent\">dozent</option>", ($val->getObjectClass() & DataFieldStructure::permMask("dozent")) ? "selected" : "");
                        printf("<option %s value=\"admin\">admin</option>", ($val->getObjectClass() & DataFieldStructure::permMask("admin")) ? "selected" : "");
                        printf("<option %s value=\"root\">root</option>", ($val->getObjectClass() & DataFieldStructure::permMask("root")) ? "selected" : "");
                        print "</select>";
                    }
                    else
                        print ($val->getObjectClass()) ? DataFieldStructure::getReadableUserClass($val->getObjectClass()) : _("alle");
                    ?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        print "<select name=\"datafield_edit_perms\" style=\"{font-size:8 pt;}\">";
                        printf("<option %s value=\"user\">user</option>", ($val->getEditPerms() == "user") ? "selected" : "");
                        printf("<option %s value=\"autor\">autor</option>", ($val->getEditPerms() == "autor") ? "selected" : "");
                        printf("<option %s value=\"tutor\">tutor</option>", ($val->getEditPerms() == "tutor") ? "selected" : "");
                        printf("<option %s value=\"dozent\">dozent</option>", ($val->getEditPerms() == "dozent") ? "selected" : "");
                        printf("<option %s value=\"admin\">admin</option>", ($val->getEditPerms() == "admin") ? "selected" : "");
                        printf("<option %s value=\"root\">root</option>", ($val->getEditPerms() == "root") ? "selected" : "");
                        print "</select>";
                    } else
                        print $val->getEditPerms()
                    ?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        print "<select name=\"datafield_view_perms\" style=\"{font-size:8 pt;}\">";
                        printf ("<option %s value=\"all\">%s</option>", ($val->getViewPerms() == "user") ? "selected" : "", _("alle"));
                        printf ("<option %s value=\"user\">user</option>", ($val->getViewPerms() == "user") ? "selected" : "");
                        printf ("<option %s value=\"autor\">autor</option>", ($val->getViewPerms() == "autor") ? "selected" : "");
                        printf ("<option %s value=\"tutor\">tutor</option>", ($val->getViewPerms() == "tutor") ? "selected" : "");
                        printf ("<option %s value=\"dozent\">dozent</option>", ($val->getViewPerms() == "dozent") ? "selected" : "");
                        printf ("<option %s value=\"admin\">admin</option>", ($val->getViewPerms() == "admin") ? "selected" : "");
                        printf ("<option %s value=\"root\">root</option>", ($val->getViewPerms() == "root") ? "selected" : "");
                        print "</select>";
                    }
                    elseif ($val->getViewPerms() == "all")
                        print _("alle");
                    else
                        print $val->getViewPerms();
                    ?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        printf ("<input type=\"TEXT\" maxlength=\"10\" size=\"5\" style=\"{font-size:8 pt; width: 30%%; text-align: center;}\" value=\"%s\" name=\"datafield_priority\">", $val->getPriority());
                    } else
                        print $val->getPriority()
                    ?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                    <?=$val->getCachedNumEntries()?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        print  ' <input type="IMAGE" name="send_datafield" src="'.$GLOBALS['ASSETS_URL'].'images/haken_transparent.gif" border="0" title="Änderungen übernehmen">';
                        printf ('<a href="%s"><img src="'.$GLOBALS['ASSETS_URL'].'images/x_transparent.gif" border="0" title="Bearbeitung abbrechen"></a>', 
                                URLHelper::getLink("?cancel=TRUE"));
                    }
                    else
                        printf (" <a href=\"%s\"><img src=\"".$GLOBALS['ASSETS_URL']."images/edit_transparent.gif\" border=\"0\" %s></a>", 
                                URLHelper::getLink("?change_datafield=".$val->getID()."#a"), tooltip(_("Datenfeld ändern")));
                    printf (" <a href=\"%s\"><img src=\"".$GLOBALS['ASSETS_URL']."images/trash.gif\" border=\"0\" %s></a>", 
                            URLHelper::getLink("?kill_datafield=".$val->getID()), tooltip(_("Datenfeld löschen (wird von keiner Veranstaltung verwendet)")));
                    ?>
                </td>
            </tr>
                <?
            }
            if ($admin_datafields_data["create_datafield"] == "usersemdata") {
                $cssSw->switchClass()
            ?>
            <tr>
                <td class="<?=$cssSw->getClass()?>" align="left">
                    <a name="a"></a>
                    <font size="-1">
                        <input type="TEXT" maxlength="255" size="25" style="{font-size:8 pt; width: 90%;}" name="datafield_name">
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                <?
                    printDataFieldType(0, 0);
                ?>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                        <select name="datafield_class[]"  multiple size="7" style="{font-size:8 pt;}">";
                        <option value="FALSE"><?=_("alle")?></option>
                        <option value="user">user</option>
                        <option value="autor">autor</option>
                        <option value="tutor">tutor</option>
                        <option value="dozent">dozent</option>
                        <option value="admin">admin</option>
                        <option value="root">root</option>
                        </select>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                        <select name="datafield_edit_perms" style="{font-size:8 pt;}">";
                        <option value="user">user</option>
                        <option value="autor">autor</option>
                        <option value="tutor">tutor</option>
                        <option value="dozent">dozent</option>
                        <option value="admin">admin</option>
                        <option value="root">root</option>
                        </select>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                        <select name="datafield_view_perms" style="{font-size:8 pt;}">";
                        <option value="all"><?=_("alle")?></option>
                        <option value="user">user</option>
                        <option value="autor">autor</option>
                        <option value="tutor">tutor</option>
                        <option value="dozent">dozent</option>
                        <option value="admin">admin</option>
                        <option value="root">root</option>
                        </select>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                        <input type="TEXT" maxlength="10" size="5" style="{font-size:8 pt; width: 30%; text-align: center;}" name="datafield_priority">
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    &nbsp;
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center" nowrap>
                    <input type="IMAGE" name="send" src="<?= $GLOBALS['ASSETS_URL'] ?>images/haken_transparent.gif" border="0" <?=tooltip(_("Datenfeld speichern"))?> >
                    <a href="<?= URLHelper::getLink("?cancel=TRUE") ?>"><img src="<?= $GLOBALS['ASSETS_URL'] ?>images/x_transparent.gif" border="0" <?=tooltip(_("Anlegen abbrechen"))?> ></a>
                </td>
            </tr>
            <?
            }
            ?>
        </table>
        <?
        if ($admin_datafields_data["create_datafield"] != "usersemdata") {
            ?><a href="<?= URLHelper::getLink("?create_new=usersemdata#a") ?>"><img src="<?= $GLOBALS['ASSETS_URL'] ?>images/add_right.gif" border="0" <?=tooltip(_("Neues Datenfeld für Nutzer-Zusatzangaben anlegen"))?>></a><?
        }
        ?>
        <br><br>
        </td>
    </tr>

    <?
     /*
      * Datenfelder für Rollen in Einrichtungen
        */
    ?>
    <tr>
        <td class="blank" colspan=2>
        <b><font size="-1"><?=_("Datenfelder f&uuml;r Rollen in Einrichtungen")?></font></b>
        <table width = "100%" border="0" cellpadding="2" cellspacing="0" align="center">
            <tr>
                <td class="steel" width="20%" align="left" valign="bottom">
                    <font size="-1">
                    <b><?=_("Name")?></b>
                    </font>
                </td>
                <td class="steel" width="12%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Feldtyp")?></b>
                    </font>
                </td>
                <td class="steel" width="20%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Nutzerstatus")?></b>
                    </font>
                </td>
                <td class="steel" width="12%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("ben&ouml;tigter Status")?></b>
                    </font>
                </td>
                <td class="steel" width="12%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Sichtbarkeit")?></b>
                    </font>
                </td>
                <td class="steel" width="7%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Reihenfolge")?></b>
                    </font>
                </td>
                <td class="steel" width="10%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Eintr&auml;ge")?></b>
                    </font>
                </td>
                <td class="steel" width="7%" align="center" valign="bottom">
                    <font size="-1">
                    <b><?=_("Aktion")?></b>
                    </font>
                </td>
            </tr>
            <?
            $datafields_list = DataFieldStructure::getDataFieldStructures("roleinstdata");
            $cssSw->resetClass();
            foreach ($datafields_list as $key=>$val) {
                $cssSw->switchClass()
                ?>
            <tr>
                <td class="<?=$cssSw->getClass()?>" align="left">
                    <font size="-1">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        print "<a name=\"a\"></a>";
                        printf ("<input type=\"TEXT\" maxlength=\"255\" size=\"25\" style=\"{font-size:8 pt; width: 90%%;}\" value=\"%s\" name=\"datafield_name\">", $val->getName());
                    } else
                        print $val->getName()
                    ?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                <?
                    printDataFieldType($admin_datafields_data['change_datafield'], $val);
                ?>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        print "<select name=\"datafield_class[]\"  multiple size=\"7\" style=\"{font-size:8 pt;}\">";
                        printf("<option %s value=\"FALSE\">"._("alle")."</option>", (!$val->getObjectClass()) ? "selected" : "");
                        printf("<option %s value=\"user\">user</option>", ($val->getObjectClass() & DataFieldStructure::permMask("user")) ? "selected" : "");
                        printf("<option %s value=\"autor\">autor</option>", ($val->getObjectClass() & DataFieldStructure::permMask("autor")) ? "selected" : "");
                        printf("<option %s value=\"tutor\">tutor</option>", ($val->getObjectClass() & DataFieldStructure::permMask("tutor")) ? "selected" : "");
                        printf("<option %s value=\"dozent\">dozent</option>", ($val->getObjectClass() & DataFieldStructure::permMask("dozent")) ? "selected" : "");
                        printf("<option %s value=\"admin\">admin</option>", ($val->getObjectClass() & DataFieldStructure::permMask("admin")) ? "selected" : "");
                        printf("<option %s value=\"root\">root</option>", ($val->getObjectClass() & DataFieldStructure::permMask("root")) ? "selected" : "");
                        print "</select>";
                    }
                    else
                        print ($val->getObjectClass()) ? DataFieldStructure::getReadableUserClass($val->getObjectClass()) : _("alle");
                    ?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        print "<select name=\"datafield_edit_perms\" style=\"{font-size:8 pt;}\">";
                        printf("<option %s value=\"user\">user</option>", ($val->getEditPerms() == "user") ? "selected" : "");
                        printf("<option %s value=\"autor\">autor</option>", ($val->getEditPerms() == "autor") ? "selected" : "");
                        printf("<option %s value=\"tutor\">tutor</option>", ($val->getEditPerms() == "tutor") ? "selected" : "");
                        printf("<option %s value=\"dozent\">dozent</option>", ($val->getEditPerms() == "dozent") ? "selected" : "");
                        printf("<option %s value=\"admin\">admin</option>", ($val->getEditPerms() == "admin") ? "selected" : "");
                        printf("<option %s value=\"root\">root</option>", ($val->getEditPerms() == "root") ? "selected" : "");
                        print "</select>";
                    } else
                        print $val->getEditPerms()
                    ?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        print "<select name=\"datafield_view_perms\" style=\"{font-size:8 pt;}\">";
                        printf ("<option %s value=\"all\">%s</option>", ($val->getViewPerms() == "user") ? "selected" : "", _("alle"));
                        printf ("<option %s value=\"user\">user</option>", ($val->getViewPerms() == "user") ? "selected" : "");
                        printf ("<option %s value=\"autor\">autor</option>", ($val->getViewPerms() == "autor") ? "selected" : "");
                        printf ("<option %s value=\"tutor\">tutor</option>", ($val->getViewPerms() == "tutor") ? "selected" : "");
                        printf ("<option %s value=\"dozent\">dozent</option>", ($val->getViewPerms() == "dozent") ? "selected" : "");
                        printf ("<option %s value=\"admin\">admin</option>", ($val->getViewPerms() == "admin") ? "selected" : "");
                        printf ("<option %s value=\"root\">root</option>", ($val->getViewPerms() == "root") ? "selected" : "");
                        print "</select>";
                    }
                    elseif ($val->getViewPerms() == "all")
                        print _("alle");
                    else
                        print $val->getViewPerms();
                    ?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        printf ("<input type=\"TEXT\" maxlength=\"10\" size=\"5\" style=\"{font-size:8 pt; width: 30%%; text-align: center;}\" value=\"%s\" name=\"datafield_priority\">", $val->getPriority());
                    } else
                        print $val->getPriority()
                    ?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                    <?=$val->getCachedNumEntries()?>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <?
                    if ($admin_datafields_data["change_datafield"] == $val->getID()) {
                        print  ' <input type="IMAGE" name="send_datafield" src="'.$GLOBALS['ASSETS_URL'].'images/haken_transparent.gif" border="0" title="Änderungen übernehmen">';
                        printf ('<a href="%s"><img src="'.$GLOBALS['ASSETS_URL'].'images/x_transparent.gif" border="0" title="Bearbeitung abbrechen"></a>', 
                                URLHelper::getLink("?cancel=TRUE"));
                    }
                    else
                        printf (" <a href=\"%s\"><img src=\"".$GLOBALS['ASSETS_URL']."images/edit_transparent.gif\" border=\"0\" %s></a>", 
                                URLHelper::getLink("?change_datafield=".$val->getID()."#a"), tooltip(_("Datenfeld ändern")));
                    printf (" <a href=\"%s\"><img src=\"".$GLOBALS['ASSETS_URL']."images/trash.gif\" border=\"0\" %s></a>", 
                            URLHelper::getLink("?kill_datafield=".$val->getID()), tooltip(_("Datenfeld löschen (wird von keiner Veranstaltung verwendet)")));
                    ?>
                </td>
            </tr>
                <?
            }
            if ($admin_datafields_data["create_datafield"] == "roleinstdata") {
                $cssSw->switchClass()
            ?>
            <tr>
                <td class="<?=$cssSw->getClass()?>" align="left">
                    <a name="a"></a>
                    <font size="-1">
                        <input type="text" maxlength="255" size="25" style="{font-size:8 pt; width: 90%;}" name="datafield_name">
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                <?
                    printDataFieldType(0, 0);
                ?>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                        <select name="datafield_class[]"  multiple size="7" style="{font-size:8 pt;}">";
                        <option value="FALSE"><?=_("alle")?></option>
                        <option value="user">user</option>
                        <option value="autor">autor</option>
                        <option value="tutor">tutor</option>
                        <option value="dozent">dozent</option>
                        <option value="admin">admin</option>
                        <option value="root">root</option>
                        </select>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                        <select name="datafield_edit_perms" style="{font-size:8 pt;}">";
                        <option value="user">user</option>
                        <option value="autor">autor</option>
                        <option value="tutor">tutor</option>
                        <option value="dozent">dozent</option>
                        <option value="admin">admin</option>
                        <option value="root">root</option>
                        </select>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                        <select name="datafield_view_perms" style="{font-size:8 pt;}">";
                        <option value="all"><?=_("alle")?></option>
                        <option value="user">user</option>
                        <option value="autor">autor</option>
                        <option value="tutor">tutor</option>
                        <option value="dozent">dozent</option>
                        <option value="admin">admin</option>
                        <option value="root">root</option>
                        </select>
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    <font size="-1">
                        <input type="TEXT" maxlength="10" size="5" style="{font-size:8 pt; width: 30%; text-align: center;}" name="datafield_priority">
                    </font>
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center">
                    &nbsp;
                </td>
                <td class="<?=$cssSw->getClass()?>" align="center" nowrap>
                    <input type="IMAGE" name="send" src="<?= $GLOBALS['ASSETS_URL'] ?>images/haken_transparent.gif" border="0" <?=tooltip(_("Datenfeld speichern"))?> >
                    <a href="<?= URLHelper::getLink("?cancel=TRUE") ?>"><img src="<?= $GLOBALS['ASSETS_URL'] ?>images/x_transparent.gif" border="0" <?=tooltip(_("Anlegen abbrechen"))?> ></a>
                </td>
            </tr>
            <?
            }
            ?>
        </table>
        <?
        if ($admin_datafields_data["create_datafield"] != "roleinstdata") {
            ?><a href="<?= URLHelper::getLink("?create_new=roleinstdata#a") ?>"><img src="<?= $GLOBALS['ASSETS_URL'] ?>images/add_right.gif" border="0" <?=tooltip(_("Neues Datenfeld für Nutzer-Zusatzangaben anlegen"))?>></a><?
        }
        ?>
        <br><br>
        </td>
    </tr>
</table>
</form>
<?
    include ('lib/include/html_end.inc.php');
    page_close();
?>
