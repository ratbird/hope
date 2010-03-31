<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// admin_lit_list.php
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

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check("autor");
require_once ('lib/visual.inc.php');
require_once ('lib/classes/StudipLitListViewAdmin.class.php');
require_once ('lib/classes/StudipLitClipBoard.class.php');
include_once('lib/lit_import.inc.php');

include ('lib/seminar_open.php'); // initialise Stud.IP-Session
require_once 'lib/admin_search.inc.php';

$_attributes['lit_select'] = array('style' => 'font-size:8pt;width:100%');

$HELP_KEYWORD = "Basis.LiteraturListen";
$CURRENT_PAGE = _("Verwaltung von Literaturlisten");

if (!$sess->is_registered('_lit_range')){
    $sess->register('_lit_range');
}

if ($_REQUEST['_range_id'] == "self"){
    $_range_id = $auth->auth['uid'];
    unset($view_mode);
} else if (isset($_REQUEST['_range_id'])){
    $_range_id = $_REQUEST['_range_id'];
} else {
    $_range_id = $_lit_range;
}
if (!$_range_id){
    $_range_id = $auth->auth['uid'];
}

if ($list  || $view || $view_mode || $_range_id != $auth->auth['uid']){
    if ($links_admin_data['topkat'] == 'sem') {
        Navigation::activateItem('/admin/course/literature');
    } else {
        Navigation::activateItem('/admin/institute/literature');
    }
    $_range_id = ($SessSemName[1]) ? $SessSemName[1] : $_range_id;
} else {
    Navigation::activateItem('/tools/literature');
}

$_lit_range = $_range_id;

$_the_treeview =& new StudipLitListViewAdmin($_range_id);
$_the_tree =& $_the_treeview->tree;

$CURRENT_PAGE = $_the_tree->root_name . " - " . $CURRENT_PAGE;

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head
include 'lib/include/admin_search_form.inc.php';

//checking rights
if (($_the_tree->range_type == "sem" && !$perm->have_studip_perm("tutor", $_range_id)) ||
    (($_the_tree->range_type == "inst" || $_the_tree->range_type == "fak") && !$perm->have_studip_perm("autor", $_range_id))){
        $perm->perm_invalid(0,0);
        page_close();
        die;
}

//Literaturlisten-Import
do_lit_import();

$_the_treeview->parseCommand();

//always show existing lists
$_the_treeview->open_ranges['root'] = true;
//if there are no lists always open root element
if (!$_the_tree->hasKids('root')){
    $_the_treeview->open_items['root'] = true;
}
$_the_clipboard =& StudipLitClipBoard::GetInstance();
$_the_clip_form =& $_the_clipboard->getFormObject();


if ($_the_clip_form->isClicked("clip_ok")){
    $clip_cmd = explode("_",$_the_clip_form->getFormFieldValue("clip_cmd"));
    if ($clip_cmd[0] == "ins"){
        if (is_array($_the_clip_form->getFormFieldValue("clip_content"))){
            $inserted = $_the_tree->insertElementBulk($_the_clip_form->getFormFieldValue("clip_content"), $clip_cmd[1]);
            if ($inserted){
                $_the_tree->init();
                $_the_treeview->open_ranges[$clip_cmd[1]] = true;
                $_msg .= "msg§" . sprintf(_("%s Eintr&auml;ge aus ihrer Merkliste wurden in <b>%s</b> eingetragen."),
                $inserted, htmlReady($_the_tree->tree_data[$clip_cmd[1]]['name'])) . "§";
            }
        } else {
            $_msg .= "info§" . _("Sie haben keinen Eintrag in ihrer Merkliste ausgew&auml;hlt!") . "§";
        }
    }
    $_the_clipboard->doClipCmd();
}

if ( ($lists = $_the_tree->getListIds()) && $_the_clipboard->getNumElements()){
    for ($i = 0; $i < count($lists); ++$i){
        $_the_clip_form->form_fields['clip_cmd']['options'][]
        = array('name' => my_substr(sprintf(_("In \"%s\" eintragen"), $_the_tree->tree_data[$lists[$i]]['name']),0,50),
        'value' => 'ins_' . $lists[$i]);
    }
}

$_msg .= $_the_clipboard->msg;
if (is_array($_the_treeview->msg)){
    foreach ($_the_treeview->msg as $t_msg){
        if (!$_msg || ($_msg && (strpos($t_msg, $_msg) === false))){
            $_msg .= $t_msg . "§";
        }
    }
}

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
    echo "<br>";
}
?>
<table width="100%" border="0" cellpadding="2" cellspacing="0">
<tr>
    <td>
<?
$_the_treeview->showTree();
?>
    <br/>
    </td>
</tr>
<tr>
    <td>
<?
// Literaturlisten-Import
print_lit_import_dlg();
?>
    </td>
</tr>
</table>
</td>
<td class="blank" width="270" align="right" valign="top">
<?
$infobox[0] = array ("kategorie" => _("Information:"),
                    "eintrag" =>    array(
                                    array('icon' => "blank.gif","text"  =>  _("Hier können Sie Literaturlisten erstellen / bearbeiten.")),
                                    )
                    );

if (!$_the_tree->getNumKids('root')){
    $infobox[0]["eintrag"][] = array('icon' => "ausruf_small.gif","text"  => _("Sie haben noch keine Listen angelegt!") );
} else {
    $lists = $_the_tree->getKids('root');
    $list_count['visible'] = 0;
    $list_count['visible_entries'] = 0;
    $list_count['invisible'] = 0;
    $list_count['invisible_entries'] = 0;
    for ($i = 0; $i < count($lists); ++$i){
        if ($_the_tree->tree_data[$lists[$i]]['visibility']){
            ++$list_count['visible'];
            $list_count['visible_entries'] += $_the_tree->getNumKids($lists[$i]);
        } else {
            ++$list_count['invisible'];
            $list_count['invisible_entries'] += $_the_tree->getNumKids($lists[$i]);
        }
    }
    $infobox[0]["eintrag"][] = array('icon' => "vote-icon-visible.gif",
                                    "text"  => sprintf(_("%s öffentlich sichtbare Listen, insgesamt %s Eintr&auml;ge"),$list_count['visible'],$list_count['visible_entries']));
    $infobox[0]["eintrag"][] = array('icon' => "vote-icon-invisible.gif",
                                    "text" => sprintf(_("%s unsichtbare Listen, insgesamt %s Eintr&auml;ge"),$list_count['invisible'],$list_count['invisible_entries']) );
}

$infobox[1] = array ("kategorie" => _("Aktionen:"));
$infobox[1]["eintrag"][] = array('icon' => "link_intern.gif","text"  => "<a href=\"lit_search.php\">" . _("Literatur suchen") . "</a>" );
$infobox[1]["eintrag"][] = array('icon' => "link_intern.gif","text"  => "<a href=\"admin_lit_element.php?_range_id=new_entry\">" . _("Neue Literatur anlegen") . "</a>" );

print_infobox ($infobox, "literaturelist.jpg");
?>
<table width="250" align="center">
<?=$_the_clip_form->getFormStart($_the_treeview->getSelf());?>
<tr>
    <td class="blank" align="center" valign="top">
    <b><?=_("Merkliste:")?></b>
    <br>
    <?=$_the_clip_form->getFormField("clip_content", array_merge(array('size' => $_the_clipboard->getNumElements()),(array) $_attributes['lit_select']))?>
    <div align="center" style="background-image:url(<?= $GLOBALS['ASSETS_URL'] ?>images/border.jpg);background-repeat:repeat-y;margin:3px;"><img src="<?= $GLOBALS['ASSETS_URL'] ?>images/blank.gif" height="2" border="0"></div>
    <?=$_the_clip_form->getFormField("clip_cmd", $_attributes['lit_select'])?>
    <div align="center">
    <?=$_the_clip_form->getFormButton("clip_ok",array('style'=>'vertical-align:middle;margin:3px;'))?>
    </div>
    </td>
</tr>
</table>
<?
echo $_the_clip_form->getFormEnd();
?>
</td>
</tr>
</table>
<?
include ('lib/include/html_end.inc.php');
page_close();
?>
