<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// admin_sem_tree.php
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

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check($SEM_TREE_ADMIN_PERM ? $SEM_TREE_ADMIN_PERM : 'admin');
if (!$perm->is_fak_admin()){
    $perm->perm_invalid(0,0);
    page_close();
    die;
}

PageLayout::setTitle($UNI_NAME_CLEAN . " - " . _("Veranstaltungshierachie bearbeiten"));
Navigation::activateItem('/admin/config/sem_tree');

require_once ('lib/visual.inc.php');
require_once ('lib/classes/StudipSemTreeViewAdmin.class.php');
require_once ('lib/classes/StudipSemSearch.class.php');

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head

$view = new DbView();
$the_tree = new StudipSemTreeViewAdmin($_REQUEST['start_item_id']);
$search_obj = new StudipSemSearch();

$_open_items =& $the_tree->open_items;
$_open_ranges =& $the_tree->open_ranges;
$_possible_open_items = array();

if(!Config::GetInstance()->getValue('SEM_TREE_ALLOW_BRANCH_ASSIGN')){
    if(is_array($_open_items)){
        foreach($_open_items as $item_id => $value){
            if(!$the_tree->tree->getNumKids($item_id)) $_possible_open_items[$item_id] = $value;
        }
    }
} else {
    $_possible_open_items = $_open_items;
}

// allow add only for items where user has admin permission
if (is_array($_possible_open_items)) {
    foreach ($_possible_open_items as $item_id => $value) {
        if (!$the_tree->isItemAdmin($item_id)) {
            unset($_possible_open_items[$item_id]);
        }
    }
}

if ($search_obj->search_done){
    if ($search_obj->search_result->numRows > 50){
        $_msg = "error§" . _("Es wurden mehr als 50 Veranstaltungen gefunden! Bitte schr&auml;nken Sie Ihre Suche weiter ein.");
    } elseif ($search_obj->search_result->numRows > 0){
        $_msg = "msg§" .sprintf(_("Es wurden %s Veranstaltungen gefunden, und in Ihre Merkliste eingef&uuml;gt"),$search_obj->search_result->numRows);
        if (is_array($_SESSION['_marked_sem']) && count($_SESSION['_marked_sem'])){
            $_SESSION['_marked_sem'] = array_merge((array)$_SESSION['_marked_sem'], (array)$search_obj->search_result->getDistinctRows("seminar_id"));
        } else {
            $_SESSION['_marked_sem'] = $search_obj->search_result->getDistinctRows("seminar_id");
        }
    } else {
        $_msg = "info§" . _("Es wurden keine Veranstaltungen gefunden, auf die Ihre Suchkriterien zutreffen.");
    }
}

if ($_REQUEST['cmd'] == "MarkList"){
    if (is_array($_REQUEST['sem_mark_list'])){
        if ($_REQUEST['mark_list_aktion'] == "del"){
            $count_del = 0;
            for ($i = 0; $i < count($_REQUEST['sem_mark_list']); ++$i){
                if (isset($_SESSION['_marked_sem'][$_REQUEST['sem_mark_list'][$i]])){
                    ++$count_del;
                    unset($_SESSION['_marked_sem'][$_REQUEST['sem_mark_list'][$i]]);
                }
            }
            $_msg .= "msg§" . sprintf(_("%s Veranstaltung(en) wurde(n) aus Ihrer Merkliste entfernt."),$count_del);
        } else {
            $tmp = explode("_",$_REQUEST['mark_list_aktion']);
            $item_ids[0] = $tmp[1];
            if ($item_ids[0] == "all"){
                $item_ids = array();
                foreach ($_possible_open_items as $key => $value){
                    if($key != 'root')
                        $item_ids[] = $key;
                }
            }
            for ($i = 0; $i < count($item_ids); ++$i){
                $count_ins = 0;
                for ($j = 0; $j < count($_REQUEST['sem_mark_list']); ++$j){
                    if ($_REQUEST['sem_mark_list'][$j]){
                        $count_ins += StudipSemTree::InsertSemEntry($item_ids[$i], $_REQUEST['sem_mark_list'][$j]);
                    }
                }
                $_msg .= sprintf(_("%s Veranstaltung(en) in <b>" .htmlReady($the_tree->tree->tree_data[$item_ids[$i]]['name']) . "</b> eingetragen.<br>"), $count_ins);
            }
            if ($_msg)
                $_msg = "msg§" . $_msg;
            $the_tree->tree->init();
        }
    }
}
if ($the_tree->mode == "MoveItem" || $the_tree->mode == "CopyItem"){
    if ($_msg){
        $_msg .= "§";
    }
    if ($the_tree->mode == "MoveItem"){
        $text = _("Der Verschiebemodus ist aktiviert. Bitte w&auml;hlen Sie ein Einfügesymbol %s aus, um das Element <b>%s</b> an diese Stelle zu verschieben.%s");
    } else {
        $text = _("Der Kopiermodus ist aktiviert. Bitte w&auml;hlen Sie ein Einfügesymbol %s aus, um das Element <b>%s</b> an diese Stelle zu kopieren.%s");
    }
    $_msg .= "info§" . sprintf($text ,
                                '<img src="'. Assets::image_path('icons/16/yellow/arr_2right.png') .'" '. tooltip(_('Einfügesymbol')) . '>',
                                htmlReady($the_tree->tree->tree_data[$the_tree->move_item_id]['name']),
                                "<div align=\"right\">"
                                .LinkButton::createCancel(_('Abbrechen'), $the_tree->getSelf("cmd=Cancel&item_id=$the_tree->move_item_id"), array('title' => _("Verschieben / Kopieren abbrechen")))
                                ."</div>");
}


?>

<table width="100%" border="0" cellpadding="2" cellspacing="0">
    <tr>
    <td class="blank" width="75%" align="left" valign="top">
    <?
if ($_msg)  {
    echo "\n<table width=\"99%\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">";
    parse_msg ($_msg,"§","blank",1,false);
    echo "\n</table>";
} else {
    echo "<br><br>";
}
$the_tree->showSemTree();
    ?>
    </td>
    <td class="blank" align="left" valign="top">
    <div>
    <b><?=_("Veranstaltungssuche:")?></b>
    </div>
    <?
    $search_obj->attributes_default = array('style' => 'width:100%;font-size:10pt;');
    $search_obj->search_fields['type']['size'] = 30 ;
    echo $search_obj->getFormStart(URLHelper::getLink($the_tree->getSelf()));
    ?>
    <table border="0" width="100%" style="font-size:10pt">
    <tr>
    <td ><span style="font-size:10pt"><?=_("Titel:")?></span></td><td style="font-size:10pt"><?=$search_obj->getSearchField("title")?></td>
    </tr>
    <tr>
    <td><span style="font-size:10pt"><?=_("Untertitel:")?></span></td><td style="font-size:10pt"><?=$search_obj->getSearchField("sub_title")?></td>
    </tr>
    <tr>
    <td><span style="font-size:10pt"><?=_("Nummer:")?></span></td><td style="font-size:10pt"><?=$search_obj->getSearchField("number")?></td>
    </tr>
    <tr>
    <td><span style="font-size:10pt"><?=_("Kommentar:")?></span></td><td style="font-size:10pt"><?=$search_obj->getSearchField("comment")?></td>
    </tr>
    <tr>
    <td><span style="font-size:10pt"><?=_("DozentIn:")?></span></td><td style="font-size:10pt"><?=$search_obj->getSearchField("lecturer")?></td>
    </tr>
    <tr>
    <td><span style="font-size:10pt"><?=_("Bereich:")?></span></td><td style="font-size:10pt"><?=$search_obj->getSearchField("scope")?></td>
    </tr>
    <tr>
    <td><span style="font-size:10pt"><?=_("Kombination:")?></span></td><td style="font-size:10pt"><?=$search_obj->getSearchField("combination",array('style' => 'width:*;font-size:10pt;'))?></td>
    </tr>
    <tr>
    <td colspan="2"><hr></td>
    </tr>
    <tr>
    <td><span style="font-size:10pt"><?=_("Typ:")?></span></td><td style="font-size:10pt"><?=$search_obj->getSearchField("type",array('style' => 'width:*;font-size:10pt;'))?></td>
    </tr>
    <tr>
    <td><span style="font-size:10pt"><?=_("Semester:")?></span></td><td style="font-size:10pt"><?=$search_obj->getSearchField("sem",array('style' => 'width:*;font-size:10pt;'))?></td>
    </tr>
    <tr>
    <td align="right" colspan="2"><?=$search_obj->getSearchButton();?>&nbsp;&nbsp;<?=$search_obj->getNewSearchButton();?></td>
    </tr>
    </table>
    <?=$search_obj->getFormEnd();?>
    <p>
    <b><?=_("Merkliste:")?></b>
    </p>
    <form action="<?=URLHelper::getLink($the_tree->getSelf("cmd=MarkList"))?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <select multiple size="20" name="sem_mark_list[]" style="font-size:8pt;width:100%">
    <?
    $cols = 50;
    if (is_array($_SESSION['_marked_sem']) && count($_SESSION['_marked_sem'])){
        $view->params[0] = array_keys($_SESSION['_marked_sem']);
        $entries = new DbSnapshot($view->get_query("view:SEMINAR_GET_SEMDATA"));
        $sem_data = $entries->getGroupedResult("seminar_id");
        $sem_number = -1;
        foreach($sem_data as $seminar_id => $data){
            if (key($data['sem_number']) != $sem_number){
                $sem_number = key($data['sem_number']);
                echo "\n<option value=\"0\" style=\"font-weight:bold;color:red;\">&nbsp;</option>";
                echo "\n<option value=\"0\" style=\"font-weight:bold;color:red;\">" . $the_tree->tree->sem_dates[$sem_number]['name'] . ":</option>";
                echo "\n<option value=\"0\" style=\"font-weight:bold;color:red;\">" . str_repeat("¯",floor($cols * .8)) . "</option>";
            }
            $sem_name = key($data["Name"]);
            $sem_number_end = key($data["sem_number_end"]);
            if ($sem_number != $sem_number_end){
                $sem_name .= " (" . $the_tree->tree->sem_dates[$sem_number]['name'] . " - ";
                $sem_name .= (($sem_number_end == -1) ? _("unbegrenzt") : $the_tree->tree->sem_dates[$sem_number_end]['name']) . ")";
            }
            $line = htmlReady(my_substr($sem_name,0,$cols));
            $tooltip = $sem_name . " (" . join(",",array_keys($data["doz_name"])) . ")";
            echo "\n<option value=\"$seminar_id\" " . tooltip($tooltip,false) . ">$line</option>";
        }
    }
    ?>
    </select><br>&nbsp;<br><select name="mark_list_aktion" style="font-size:8pt;width:100%;">
    <?
    if (is_array($_possible_open_items) && count($_possible_open_items) && !(count($_possible_open_items) == 1 && $_possible_open_items['root'])){
        echo "\n<option  value=\"insert_all\">" . _("In alle ge&ouml;ffneten Bereiche eintragen") . "</option>";
        foreach ($_possible_open_items as $item_id => $value){
            echo "\n<option value=\"insert_{$item_id}\">"
                . sprintf(_("In \"%s\" eintragen"),htmlReady(my_substr($the_tree->tree->tree_data[$item_id]['name'],0,floor($cols * .8)))) . "</option>";
        }
    }
    ?>
    <option value="del"><?=_("Aus Merkliste l&ouml;schen")?></option>
    </select>
    <div align="right">
    <?= Button::create(_('OK'), array('title' => _("Gewählte Aktion starten"))); ?>
    </div>
    </form>
</td></tr>
</table>
<?php
include ('lib/include/html_end.inc.php');
page_close();
?>
