<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// admin_lit_element.php
//
//
// Copyright (c) 2003 André Noack <noack@data-quest.de>
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
$perm->check("autor");
require_once ('lib/visual.inc.php');
require_once ('lib/classes/StudipLitCatElement.class.php');
require_once ('lib/classes/StudipLitClipBoard.class.php');

include ('lib/seminar_open.php'); // initialise Stud.IP-Session
PageLayout::setTitle(_("Literatureintrag bearbeiten"));
Navigation::activateItem('/tools/literature');

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head

//html attributes for form
$_attributes = array();
$_attributes['text'] = array('style' => 'width:98%');
$_attributes['textarea'] = array('style' => 'width:98%','rows'=>2);
$_attributes['select'] = array();
$_attributes['date'] = array();
$_attributes['combo'] = array('style' => 'width:45%');
$_attributes['lit_select'] = array('style' => 'font-size:8pt;width:98%');


if ($_REQUEST['cmd'] == "new_entry"){
    $_catalog_id = "new_entry";
} else {
    $_catalog_id = isset($_REQUEST['_catalog_id']) ? Request::option('_catalog_id') : "new_entry";
}

//dump data into db if $_catalog_id points to a search result
if ($_catalog_id{0} == "_"){
        $parts = explode("__", $_catalog_id);
        if ( ($fields = $_SESSION[$parts[0]][$parts[1]]) ){
            $cat_element = new StudipLitCatElement();
            $cat_element->setValues($fields);
            $cat_element->setValue("catalog_id", "new_entry");
            $cat_element->setValue("user_id", "studip");
            if ( ($existing_element = $cat_element->checkElement()) ){
                $cat_element->setValue('catalog_id', $existing_element);
            }
            $cat_element->insertData();
            $_catalog_id = $cat_element->getValue("catalog_id");
            $_SESSION[$parts[0]][$parts[1]]['catalog_id'] = $_catalog_id;
            unset($cat_element);
        }
}

if ($_REQUEST['cmd'] == 'clone_entry'){
    $_the_element = StudipLitCatElement::GetClonedElement($_catalog_id);
    if ($_the_element->isNewEntry()){
        $_msg = "msg§" . _("Der Eintrag wurde kopiert, Sie können die Daten jetzt ändern.") . "§";
        $_msg .= "info§" . _("Der kopierte Eintrag wurde noch nicht gespeichert.") . "§";
        //$old_cat_id = $_catalog_id;
        $_catalog_id = $_the_element->getValue('catalog_id');
    } else {
        $_msg = "error§" . _("Der Eintrag konnte nicht kopiert werden!.") . "§";
    }
}

if(!is_object($_the_element)){
    $_the_element = new StudipLitCatElement($_catalog_id, true);
}
$_the_form = $_the_element->getFormObject();
$_the_clipboard = StudipLitClipBoard::GetInstance();
$_the_clip_form = $_the_clipboard->getFormObject();

if (isset($old_cat_id) && $_the_clipboard->isInClipboard($old_cat_id)){
    $_the_clipboard->deleteElement($old_cat_id);
    $_the_clipboard->insertElement($_catalog_id);
}

$_the_clip_form->form_fields['clip_cmd']['options'][] = array('name' => _("In Merkliste eintragen"), 'value' => 'ins');
$_the_clip_form->form_fields['clip_cmd']['options'][] = array('name' => _("Markierten Eintrag bearbeiten"), 'value' => 'edit');


if ($_the_form->IsClicked("reset") || $_REQUEST['cmd'] == "new_entry"){
    $_the_form->doFormReset();
}

if ($_the_form->IsClicked("delete") && $_catalog_id != "new_entry" && $_the_element->isChangeable()){
    if ($_the_element->reference_count){
        $_msg = "info§" . sprintf(_("Sie k&ouml;nnen diesen Eintrag nicht l&ouml;schen, da er noch in %s Literaturlisten referenziert wird."),$_the_element->reference_count) ."§";
    } else {
        $_msg = "info§" . _("Wollen Sie diesen Eintrag wirklich l&ouml;schen?") . "<br>"
                .LinkButton::createAccept(_('Ja'), $PHP_SELF . "?cmd=delete_element&_catalog_id=" . $_catalog_id, array('title' =>  _('löschen')))
                . "&nbsp;"
                .LinkButton::createCancel(_('Abbrechen'), $PHP_SELF . "?_catalog_id=" . $_catalog_id, array('title' =>  _('abbrechen')))
                . "§";
    }
}

if ($_REQUEST['cmd'] == "delete_element" && $_the_element->isChangeable() && !$_the_element->reference_count){
    $_the_element->deleteElement();
}

if ($_REQUEST['cmd'] == "in_clipboard" && $_catalog_id != "new_entry"){
        $_the_clipboard->insertElement($_catalog_id);
}

if ($_REQUEST['cmd'] == "check_entry"){
    $lit_plugin_value = $_the_element->getValue('lit_plugin');
    $content = "<div style=\"font-size:70%\"<b>" ._("Verf&uuml;gbarkeit in externen Katalogen:") . "</b><br>";
    foreach (StudipLitSearch::CheckZ3950($_the_element->getValue('accession_number')) as $plugin_name => $ret){
        $content .= "<b>&nbsp;" . htmlReady(StudipLitSearch::GetPluginDisplayName($plugin_name))."&nbsp;</b>";
        if ($ret['found']){
            $content .= _("gefunden") . "&nbsp;";
            $_the_element->setValue('lit_plugin', $plugin_name);
            if (($link = $_the_element->getValue("external_link"))){
                $content.= formatReady(" [" . $_the_element->getValue("lit_plugin_display_name"). "]" . $link);
            } else {
                $content .= _("(Kein Link zum Katalog vorhanden.)");
            }
        } elseif (count($ret['error'])){
            $content .= '<span style="color:red;">' . htmlReady($ret['error'][0]['msg']) . '</span>';
        } else {
            $content .= _("<u>nicht</u> gefunden") . "&nbsp;";
        }
        $content .= "<br>";
    }
    $content .= "</div>";
    $_the_element->setValue('lit_plugin', $lit_plugin_value);
    $_msg = "info§" . $content . "§";
}

if ($_the_form->IsClicked("send")){
    $_the_element->setValuesFromForm();
    if ($_the_element->checkValues()){
        $_the_element->insertData();
    }
}

if ($_the_clip_form->isClicked("clip_ok")){
    if ($_the_clip_form->getFormFieldValue("clip_cmd") == "ins" && $_catalog_id != "new_entry"){
        $_the_clipboard->insertElement($_catalog_id);
    }
    if ($_the_clip_form->getFormFieldValue("clip_cmd") == "edit"){
        $marked = $_the_clip_form->getFormFieldValue("clip_content");
        if (count($marked) && $marked[0]){
            $_the_element->getElementData($marked[0]);
        }
    }
    $_the_clipboard->doClipCmd();
}

$_catalog_id = $_the_element->getValue("catalog_id");

$_msg .= $_the_element->msg;
$_msg .= $_the_clipboard->msg;

echo $_the_form->getFormStart("$PHP_SELF?_catalog_id=$_catalog_id");
?>
<table width="100%" border="0" cellpadding="2" cellspacing="0">
    <tr>
    <td class="blank" valign="top">

    <?
if ($_msg)  {
    echo "\n<table width=\"99%\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">";
    parse_msg ($_msg,"§","blank",1,false);
    echo "\n</table>";
} else {
    echo "<br><br>";
}
?>
<table width="100%" border="0" cellpadding="2" cellspacing="0">
<?
$class_changer = new CssClassSwitcher();

echo "<tr><td " . $class_changer->getFullClass() . " align=\"left\" width=\"40%\" style=\"font-size:10pt;\">"
    . sprintf(_("Anzahl an Referenzen für diesen Eintrag: %s"), (int)$_the_element->reference_count) ."</td>";
echo "<td " . $class_changer->getFullClass() . " align=\"center\">";
if ($_the_element->isChangeable()){
    echo $_the_form->getFormButton("send") .  $_the_form->getFormButton("delete") . $_the_form->getFormButton("reset");
    echo LinkButton::create(_('Kopie erstellen'), $PHP_SELF.'?cmd=clone_entry&_catalog_id='.$_catalog_id, array('title' => _('Eine Kopie dieses Eintrages anlegen')));
}
echo "<img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\"  height=\"28\" width=\"15\" border=\"0\">";
echo LinkButton::create(_('Neu anlegen'), $PHP_SELF.'?cmd=new_entry', array('title' => _("Neuen Eintrag anlegen")));
if ($_catalog_id != "new_entry"){
    echo "<img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\"  height=\"28\" width=\"15\" border=\"0\">";
    echo LinkButton::create(_('Verfügbarkeit'), $PHP_SELF.'?cmd=check_entry&_catalog_id='.$_catalog_id, array('title' => _("Verfügbarkeit überprüfen")));
}
if ($_catalog_id != "new_entry" && !$_the_clipboard->isInClipboard($_catalog_id)){
    echo "<img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\"  height=\"28\" width=\"15\" border=\"0\">";
    echo LinkButton::create(_('Merkliste'), $PHP_SELF.'?cmd=in_clipboard&_catalog_id='.$_catalog_id, array('title' => _("Eintrag in Merkliste aufnehmen")));
}
echo "</td></tr>";
echo '<p style="font-size:-1">';
printf(_('Alle mit einem Sternchen %s markierten Felder müssen ausgefüllt werden.'),'<span style="font-size:1.5em;color:red;font-weigth:bold;">*</span>');
echo '</p>';
foreach ($_the_element->fields as $field_name => $field_detail){
    if ($field_detail['caption']){
        echo "<tr><td " . $class_changer->getFullClass() . ">";
        echo $_the_form->getFormFieldCaption($field_name,array('style'=>'font-weight:bold;font-size:10pt;'));
        echo $_the_form->getFormFieldInfo($field_name);
        if ($field_detail['mandatory']) {
            echo '<span style="font-size:1.5em;color:red;font-weight:bold;">*</span>';
        }
        echo "</td><td " . $class_changer->getFullClass() . ">";
        $attributes = $_attributes[$_the_form->form_fields[$field_name]['type']];
        if (!$_the_element->isChangeable()){
            $attributes['readonly'] = 'readonly';
            $attributes['disabled'] = 'disabled';
        }
        echo $_the_form->getFormField($field_name, $attributes);
        if ($field_name == "lit_plugin"){
            echo "&nbsp;&nbsp;<span style=\"font-size:10pt;\">";
            if (($link = $_the_element->getValue("external_link"))){
                echo formatReady("=) [Link zum Katalog]" . $link);
            } else {
                echo _("(Kein Link zum Katalog vorhanden.)");
            }
            echo "</span>";
        }
        echo "</td></tr>";
    }
    $class_changer->switchClass();
}
$class_changer->switchClass();
echo "<tr><td " . $class_changer->getFullClass() . " align=\"left\" width=\"40%\" style=\"font-size:10pt;\">"
    . sprintf(_("Anzahl an Referenzen für diesen Eintrag: %s"), (int)$_the_element->reference_count) ."</td>";
echo "<td " . $class_changer->getFullClass() . " align=\"center\">";
if ($_the_element->isChangeable()){
    echo $_the_form->getFormButton("send") .  $_the_form->getFormButton("delete") . $_the_form->getFormButton("reset");
} elseif ($_catalog_id != "new_entry") {
    echo LinkButton::create(_('Kopie erstellen'), $PHP_SELF.'?cmd=clone_entry&_catalog_id='.$_catalog_id, array('title' => _("Eine Kopie dieses Eintrages anlegen")));
}
echo "<img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\"  height=\"28\" width=\"15\" border=\"0\">";
echo LinkButton::create(_('Neu anlegen'), $PHP_SELF.'?cmd=new_entry', array('title' =>  _("Neuen Eintrag anlegen")));
if ($_catalog_id != "new_entry"){
    echo "<img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\"  height=\"28\" width=\"15\" border=\"0\">";
    echo LinkButton::create(_('Verfügbarkeit'), $PHP_SELF.'?cmd=check_entry&_catalog_id='.$_catalog_id, array('title' =>  _("Verfügbarkeit überprüfen")));
}
if ($_catalog_id != "new_entry" && !$_the_clipboard->isInClipboard($_catalog_id)){
    echo "<img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\"  height=\"28\" width=\"15\" border=\"0\">";
    echo LinkButton::create(_('Merkliste'), $PHP_SELF.'?cmd=in_clipboard&_catalog_id='.$_catalog_id, array('title' =>  _("Eintrag in Merkliste aufnehmen")));
}
echo "</td></tr>";
?>
</table>
</td>

<td class="blank" width="270" align="right" valign="top">
<?
$infobox[0] = array ("kategorie" => _("Information:"),
                    "eintrag" =>    array(
                                    array("icon" => "icons/16/black/literature.png","text"  =>  _("Hier können Sie Literatur / Quellen erfassen, oder von Ihnen erfasste Einträge ändern.")),
                                    array("icon" => "blank.gif","text"  =>  ($_the_element->getValue("user_id") == "studip" ? "<b>" . _("Systemeintrag:") . "</b><br>" . _("Dies ist ein vom System generierter Eintrag.") : "<b>" . _("Eingetragen von:") . "</b><br>" . get_fullname($_the_element->getValue("user_id"),'full',true))),
                                    array("icon" => "blank.gif","text"  =>  "<b>" . _("Letzte Änderung am:") . "</b><br>" . strftime("%d.%m.%Y",$_the_element->getValue("chdate")))
                                    )
                    );
if ($_the_element->isNewEntry()){
    $infobox[0]["eintrag"][] = array("icon" => "icons/16/black/info.png","text"  => _("Dies ist ein neuer Eintrag, der noch nicht gespeichert wurde!") );
}
if (!$_the_element->isChangeable()){
    $infobox[0]["eintrag"][] = array("icon" => "icons/16/black/info.png","text"  => _("Sie haben diesen Eintrag nicht selbst vorgenommen, und dürfen ihn daher nicht verändern! Wenn Sie mit diesem Eintrag arbeiten wollen, können Sie sich eine persönliche Kopie erstellen.") );
}
$infobox[1] = array ("kategorie" => _("Aktionen:"));
$infobox[1]["eintrag"][] = array("icon" => "icons/16/black/literature.png","text"  => "<a href=\"admin_lit_list.php\">" . _("Literaturlisten bearbeiten") . "</a>" );
$infobox[1]["eintrag"][] = array("icon" => "icons/16/black/search.png","text"  => "<a href=\"lit_search.php\">" . _("Literatur suchen") . "</a>" );

print_infobox($infobox, "infobox/literaturelist.jpg");

?>
<table width="250" align="center" border="0" cellpadding="0" cellspacing="0">
<tr>
<td class="blank" align="center" valign="top">
    <b><?=_("Merkliste:")?></b>
    <br>
    <?=$_the_clip_form->getFormField("clip_content", array_merge(array('size' => $_the_clipboard->getNumElements()),(array) $_attributes['lit_select']))?>
    <div align="center" style="background-image:url(<?= $GLOBALS['ASSETS_URL'] ?>images/border.jpg); background-repeat:repeat-y; margin:3px; height: 2px;"> </div>
    <?=$_the_clip_form->getFormField("clip_cmd", $_attributes['lit_select'])?>
    <div align="center">
    <?=$_the_clip_form->getFormButton("clip_ok", array('style'=>'vertical-align:middle; margin:3px;'))?>
    </div>
    <?= $_the_clip_form->getHiddenField(md5("is_sended"),1) ?>
    </td>
</tr>
</table>
</td>
</tr>
</table>
<?
echo $_the_form->getFormEnd();

include ('lib/include/html_end.inc.php');
page_close();
?>
