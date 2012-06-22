<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* ResourcesBrowse.class.php
*
* search egine for resources
*
*
* @author       Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup      resources
* @module       ResourcesBrowse.class.php
* @package      resources
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// ResourcesBrowse.class.php
// die Suchmaschine fuer Ressourcen
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

use Studip\Button,
    Studip\LinkButton;

require_once ($RELATIVE_PATH_RESOURCES."/lib/ResourcesUserRoots.class.php");
require_once ($RELATIVE_PATH_RESOURCES."/views/ShowList.class.php");


/*****************************************************************************
ResourcesBrowse, the search engine
/*****************************************************************************/

class ResourcesBrowse {
    var $start_object;      //where to start
    var $open_object;       //where we stay
    var $mode;          //the search mode
    var $searchArray;       //the array of search expressions (free search & properties)
    var $db;
    var $db2;
    var $db3;
    var $cssSw;         //the cssClassSwitcher

    function ResourcesBrowse() {
        $this->db = new DB_Seminar();
        $this->db2 = new DB_Seminar();
        $this->db3 = new DB_Seminar();
        $this->cssSw = new cssClassSwitcher();
        $this->list = new ShowList;

        $this->list->setRecurseLevels(0);
        $this->list->setViewHiearchyLevels(FALSE);
    }

    function setStartLevel($resource_id) {
        $this->start_object = $resource_id;
    }

    function setOpenLevel($resource_id) {
        $this->open_object = $resource_id;
    }

    function setMode($mode="browse") {
        $this->mode=$mode;
        if (!$this->mode)
            $this->mode="browse";
    }

    function setCheckAssigns($value) {
        $this->check_assigns=$value;
    }

    function setSearchOnlyRooms($value){
        $this->search_only_rooms = $this->list->show_only_rooms = $value;
    }

    function setSearchArray($array) {
        $this->searchArray = $array;
    }

    //private
    function searchForm() {
        ?>
        <tr>
            <td <? $this->cssSw->switchClass(); echo $this->cssSw->getFullClass() ?> align="center" <? echo ($this->mode == "browse") ? "colspan=\"2\"" : "" ?>>
                <?=_("freie Suche")?>:&nbsp;
                    <select name="resources_search_range" style="vertical-align:middle">
                    <option value="0" selected><?=htmlReady($GLOBALS['UNI_NAME_CLEAN'])?></option>
                    <?if ($this->open_object){
                        $res = ResourceObject::Factory($this->open_object);
                        ?>
                        <option value="<?=$this->open_object?>" selected><?=htmlReady($res->getName())?></option>
                    <?}?>
                    </select>
                <input name="search_exp" type="text" style="vertical-align: middle;" size=35 maxlength=255 value="<? echo htmlReady(stripslashes($this->searchArray["search_exp"])); ?>">
                <?= Button::create(_('Suche starten'), 'start_search') ?>
                <?= LinkButton::create(_('Neue Suche'), URLHelper::getURL('?view=search&quick_view_mode=' . Request::option('view_mode') . '&reset=TRUE')) ?>
            </td>
        </tr>
        <?
    }

    //private
    function getHistory($id) {
        global $UNI_URL, $UNI_NAME_CLEAN;
        $top=FALSE;
        $k=0;
        while ((!$top) && ($id)) {
            $k++;
            $query = sprintf ("SELECT name, parent_id, resource_id, owner_id FROM resources_objects WHERE resource_id = '%s' ", $id);
            $this->db2->query($query);
            $this->db2->next_record();

            $result_arr[] = array("id" => $this->db2->f("resource_id"), "name" => $this->db2->f("name"), 'owner_id' => $this->db2->f('owner_id'));
            $id=$this->db2->f("parent_id");

            if ($this->db2->f("parent_id") == "0") {
                $top = TRUE;
            }
        }
        if (is_array($result_arr))
            switch (ResourceObject::getOwnerType($result_arr[count($result_arr)-1]["owner_id"])) {
                case "global":
                    $top_level_name = $UNI_NAME_CLEAN;
                break;
                case "sem":
                    $top_level_name = _("Veranstaltungsressourcen");
                break;
                case "inst":
                    $top_level_name = _("Einrichtungsressourcen");
                break;
                case "fak":
                    $top_level_name = _("Fakult&auml;tsressourcen");
                break;
                case "user":
                    $top_level_name = _("pers&ouml;nliche Ressourcen");
                break;
            }

            if (Request::option('view') == 'search') {
                $result  = '<a href="'. URLHelper::getLink('?view=search&quick_view_mode='. Request::option('view_mode') .'&reset=TRUE') .'">';
                $result .=  $top_level_name;
                $result .= '</a>';
            }
                
            for ($i = sizeof($result_arr)-1; $i>=0; $i--) {
                if (Request::option('view')) {
                    $result .= '> <a href="'.URLHelper::getLink(sprintf('?quick_view='.Request::option('view').'&quick_view_mode='.Request::option('view_mode').'&%s='.$result_arr[$i]["id"],(Request::option('view')=='search') ? "open_level" : "actual_object" ) );
                        
                    $result .= '">'. htmlReady($result_arr[$i]["name"]) .'</a>';
                } else {
                    $result.= sprintf (" > %s", htmlReady($result_arr[$i]["name"]));
                }
            }
        return $result;
    }

    //private
    function showTimeRange() {
        $colspan = $this->mode == 'browse' ? ' colspan="2" ' : '';
        ?>
        <tr>
            <td <? $this->cssSw->switchClass(); echo $this->cssSw->getFullClass() ?> >
                <?=_("gefundene Ressourcen sollen zu folgender Zeit <u>nicht</u> belegt sein:")?>
            <br>
            </td>
        </tr>
        <tr>
            <td <? $this->cssSw->switchClass(); echo $this->cssSw->getFullClass() ?> >
            &nbsp;<br>
                <table cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td width="120">
                            <b><?= _('Einzeltermin:') ?></b>
                        </td>
                        <td>
                            <?=_("Beginn")?>:
                            &nbsp;<input type="text" style="font-size:8pt;" name="search_begin_hour" size="2" maxlength="2" value="<?=($this->searchArray["search_assign_begin"]) ? date("H", $this->searchArray["search_assign_begin"]) : _("ss")?>">
                            <input type="text" style="font-size:8pt;" name="search_begin_minute" size="2" maxlength="2" value="<?=($this->searchArray["search_assign_begin"]) ? date("i", $this->searchArray["search_assign_begin"]) : _("mm")?>">&nbsp;<?=_("Uhr")?>
                            &nbsp;&nbsp;<?=_("Ende")?>:
                            &nbsp;<input type="text" style="font-size:8pt;" name="search_end_hour" size="2" maxlength="2" value="<?=($this->searchArray["search_assign_end"]) ? date("H", $this->searchArray["search_assign_end"]) : _("ss")?>">
                            <input type="text" style="font-size:8pt;" name="search_end_minute" size="2" maxlength="2" value="<?=($this->searchArray["search_assign_end"]) ? date("i", $this->searchArray["search_assign_end"]) : _("mm")?>">&nbsp;<?=_("Uhr")?>
                <br>
                            <?=_("Datum")?>: &nbsp;
                            <input type="text" style="font-size:8pt;" name="search_day" size="2" maxlength="2" value="<?=($this->searchArray["search_assign_begin"]) ? date("d", $this->searchArray["search_assign_begin"]) : _("tt")?>">
                            .<input type="text" style="font-size:8pt;" name="search_month" size="2" maxlength="2" value="<?=($this->searchArray["search_assign_begin"]) ? date("m", $this->searchArray["search_assign_begin"]) : _("mm")?>">
                            .<input type="text" style="font-size:8pt;" name="search_year" size="4" maxlength="4" value="<?=($this->searchArray["search_assign_begin"]) ? date("Y", $this->searchArray["search_assign_begin"]) : _("jjjj")?>">
                            &nbsp;&nbsp;&nbsp;&nbsp;    <input type="checkbox" style="font-size:8pt;" name="search_repeating" value="1" <?=($this->searchArray["search_repeating"]==1) ? "checked=checked" : ""?>> für restliches Semester prüfen &nbsp; <br>
                            <br>
                        </td>
                    </tr>
                </table>
                </td>
                </tr>
                <tr>
                <td <? $this->cssSw->switchClass(); echo $this->cssSw->getFullClass() ?> >
                    <table cellspacing="0" cellpadding="0" border="0" width="100%">
                        <tr>
                            <td width="120">
                                <b><?= _('Semestertermin:') ?></b>
                            </td>
                            <td>
                    <br>
                <?=_("Beginn")?>:
                    &nbsp;<input type="text" style="font-size:8pt;" name="search_begin_hour_2" size="2" maxlength="2" value="<?=($this->searchArray["search_assign_begin"]) ? date("H", $this->searchArray["search_assign_begin"]) : _("ss")?>">
                    <input type="text" style="font-size:8pt;" name="search_begin_minute_2" size="2" maxlength="2" value="<?=($this->searchArray["search_assign_begin"]) ? date("i", $this->searchArray["search_assign_begin"]) : _("mm")?>">&nbsp;<?=_("Uhr")?>
                &nbsp;&nbsp;<?=_("Ende")?>:
                    &nbsp;<input type="text" style="font-size:8pt;" name="search_end_hour_2" size="2" maxlength="2" value="<?=($this->searchArray["search_assign_end"]) ? date("H", $this->searchArray["search_assign_end"]) : _("ss")?>">
                    <input type="text" style="font-size:8pt;" name="search_end_minute_2" size="2" maxlength="2" value="<?=($this->searchArray["search_assign_end"]) ? date("i", $this->searchArray["search_assign_end"]) : _("mm")?>">&nbsp;<?=_("Uhr")?>
                <br>
                <?=_("Tag der Woche")?>:
                <select name = 'search_day_of_week'>
                <option value=-1 <?=$this->searchArray["search_day_of_week"]==-1? "selected=selected":""?>><?=_("--")?> </option>
                <option value='Monday' <?=$this->searchArray["search_day_of_week"]=='Monday'? "selected=selected":""?>><?=_("Montag")?> </option>
                <option value='Tuesday' <?=$this->searchArray["search_day_of_week"]=='Tuesday'? "selected=selected":""?>><?=_("Dienstag")?> </option>
                <option value='Wednesday' <?=$this->searchArray["search_day_of_week"]=='Wednesday'?  "selected=selected":""?>><?=_("Mittwoch")?> </option>
                <option value='Thursday' <?=$this->searchArray["search_day_of_week"]=='Thursday'?  "selected=selected":""?>><?=_("Donnerstag")?> </option>
                <option value='Friday' <?=$this->searchArray["search_day_of_week"]=='Friday'?  "selected=selected":""?>><?=_("Freitag")?> </option>
                <option value='Saturday' <?=$this->searchArray["search_day_of_week"]=='Saturday'?  "selected=selected":""?>><?=_("Samstag")?> </option>
                <option value='Sunday' <?=$this->searchArray["search_day_of_week"]=='Sunday'?  "selected=selected":""?>><?=_("Sonntag")?> </option>
                </select> &nbsp;
                <?=_("Semester")?>:
                <select name = 'search_semester'>
                <?
                    $semesterData = new SemesterData();
                    $all_semester = $semesterData->getAllSemesterData();
                if (!$this->searchArray["search_semester"])
                {
                    $current_semester = $semesterData->getCurrentSemesterData();
                    $selected_semester = $semesterData->getSemesterDataByDate(strtotime("+1 Day",$current_semester["ende"]));
                } else
                {
                    $selected_semester["semester_id"] = $this->searchArray["search_semester"];
                }
                    $this_sem = false;
                    foreach($all_semester as $semester)
                    {
                        if ($selected_semester["semester_id"]==$semester["semester_id"])
                        {
                            $this_sem = true;
                        } else
                        {
                            $this_sem = false;
                        }

                        echo "<option value='".$semester["semester_id"]."' ".($this_sem?" selected=selected ":"").">".$semester["name"]."</option>";

                    }
                ?>
                </select> &nbsp;
                    </td>
                </tr>
            </table>
                <br>
            </td>
        </tr>


        <?
    }

    //private
    function showProperties() {
        ?>
        <tr>
            <td <? $this->cssSw->switchClass(); echo $this->cssSw->getFullClass() ?> >
                <?=_("folgende Eigenschaften soll die Ressource besitzen (leer bedeutet egal):")?>
            <br>
            </td>
        </tr>
        <tr>
            <td <? $this->cssSw->switchClass(); echo $this->cssSw->getFullClass() ?> >
                <table width="90%" cellpadding=5 cellspacing=0 border=0 align="center">
                    <?
                    $query = sprintf("SELECT category_id, name FROM resources_categories ORDER BY name");
                    $this->db->query($query);
                    $k=0;
                    while ($this->db->next_record()) {
                        $query = sprintf("SELECT resources_properties.property_id, name, type, options
                                        FROM resources_categories_properties LEFT JOIN resources_properties
                                        USING (property_id) WHERE category_id = '%s'
                                        %s ORDER BY name ", $this->db->f("category_id"),
                                        (get_config('RESOURCES_SEARCH_ONLY_REQUESTABLE_PROPERTY') ? " AND requestable=1 ": ""));
                        $this->db2->query($query);
                        if ($this->db2->num_rows()){

                            print "<tr>\n";
                            print "<td colspan=\"2\"> \n";
                            if ($k)
                                print "<hr><br>";
                            printf ("<b>%s:</b>", htmlReady($this->db->f("name")));
                            print "</td>\n";
                            print "</tr> \n";
                            print "<tr>\n";
                            print "<td width=\"50%\" valign=\"top\">";
                            if ($this->db2->num_rows() % 2 == 1)
                                $i=0;
                            else
                                $i=1;
                            $switched = FALSE;
                            while ($this->db2->next_record()) {
                                if (($i > ($this->db2->num_rows() /2 )) && (!$switched)) {
                                    print "</td><td width=\"50%\" valign=\"top\">";
                                    $switched = TRUE;
                                }
                                print "<table width=\"100%\" border=\"0\"><tr>";
                                printf ("<td width=\"50%%\">%s</td>", htmlReady($this->db2->f("name")));
                                print "<td width=\"50%\">";
                                printf ("<input type=\"HIDDEN\" name=\"search_property_val[]\" value=\"%s\">", "_id_".$this->db2->f("property_id"));
                                switch ($this->db2->f("type")) {
                                    case "bool":
                                        printf ("<input type=\"CHECKBOX\" name=\"search_property_val[]\" %s>&nbsp;%s", ($value) ? "checked":"", htmlReady($this->db2->f("options")));
                                    break;
                                    case "num":
                                        printf ("<input type=\"TEXT\" name=\"search_property_val[]\" value=\"%s\" size=20 maxlength=255>", htmlReady($value));
                                    break;
                                    case "text";
                                        printf ("<textarea name=\"search_property_val[]\" cols=20 rows=2 >%s</textarea>", htmlReady($value));
                                    break;
                                    case "select";
                                        $options=explode (";",$this->db2->f("options"));
                                        print "<select name=\"search_property_val[]\">";
                                        print   "<option value=\"\">--</option>";
                                        foreach ($options as $a) {
                                            printf ("<option %s value=\"%s\">%s</option>", ($value == $a) ? "selected":"", $a, htmlReady($a));
                                        }
                                        printf ("</select>");
                                    break;
                                }
                                print "</td></tr></table>";
                                $i++;
                            }
                        $k++;
                        }
                    }
                    ?>
                </table>
            </td>
        </tr>
        <?
    }

    //private
    function browseLevels() {

        if ($this->open_object) {
            $query = sprintf ("SELECT a.resource_id, a.name, a.description FROM resources_objects a LEFT JOIN resources_objects b ON (b.parent_id = a.resource_id)  WHERE a.parent_id = '%s' AND (a.category_id IS NULL OR b.resource_id IS NOT NULL) GROUP BY resource_id ORDER BY name", $this->open_object);
            $query2 = sprintf ("SELECT parent_id FROM resources_objects WHERE resource_id = '%s' ", $this->open_object);

            $this->db2->query($query2);
            $this->db2->next_record();
            if ($this->db2->f("parent_id") != "0")
                $way_back=$this->db2->f("parent_id");
        } else {
            $resRoots=new ResourcesUserRoots($range_id);

            $roots=$resRoots->getRoots();
            if (is_array($roots))
                $clause = "AND resource_id  IN('" . join("','",$roots) . "')";
            else
                $clause = "AND 1=2";

            $query = sprintf ("SELECT resource_id, name, description FROM resources_objects WHERE 1 %s ORDER BY name", $clause);
            $way_back=-1;
        }

        $this->db->query($query);

        //check for sublevels in current level
        $sublevels = FALSE;
        while ($this->db->next_record()) {
            $query2 = sprintf ("SELECT resource_id, name, description FROM resources_objects WHERE parent_id = '%s' ORDER BY name", $this->db->f("resource_id"));
            $this->db2->query($query2);
            if ($this->db2->nf() >0)
                $sublevels = TRUE;
        }
        if ($sublevels)
            $this->db->query($query);

        ?>
        <tr>
            <td <? $this->cssSw->switchClass(); echo $this->cssSw->getFullClass() ?>>
                <?
                echo $this->getHistory($this->open_object);
                ?>
            </td>
            <td <? echo $this->cssSw->getFullClass() ?>width="15%" align="right" nowrap valign="top">
                <?
                if ($way_back>=0) : ?>
                <a href="<?= URLHelper::getLink('?view=search&quick_view_mode='. Request::option('view_mode')
                            . '&' . (!$way_back) ? "reset=TRUE" : "open_level=$way_back") ?>">
                    <?= Assets::img('icons/16/blue/arr_2left.png', array(
                        'class' => 'text-top',
                        'title' =>_('eine Ebene zur&uuml;ck'))) ?>
                    </a>
                <? endif ?>
            </td>
        </tr>
        <tr>
            <td <? $this->cssSw->switchClass(); echo $this->cssSw->getFullClass() ?> align="left" colspan="2">
                <?
                if ((!$this->db->num_rows()) || (!$sublevels)) { ?>
                    <?= MessageBox::info(_("Auf dieser Ebene existieren keine weiteren Unterebenen")) ?>
                <? } else {
                ?>
                <table width="90%" cellpadding=5 cellspacing=0 border=0 align="center">
                    <?
                    if ($this->db->num_rows() % 2 == 1)
                        $i=0;
                    else
                        $i=1;
                    print "<td width=\"55%\" valign=\"top\">";
                    while ($this->db->next_record()) {
                        if (($i > ($this->db->num_rows() /2 )) && (!$switched)) {
                            print "</td><td width=\"40%\" valign=\"top\">";
                            $switched = TRUE;
                        } ?>
                        <a href="<?= URLHelper::getLink('?view=search&quick_view_mode='. Request::option('view_mode') .'&open_level=' . $this->db->f("resource_id")) ?>">
                            <b><?= htmlReady($this->db->f("name")) ?></b>
                        </a><br>
                        <? $i++;
                    }
                    print "</table>";
                }
                ?>
            </td>
        </tr>
        <tr>
            <td <? $this->cssSw->switchClass(); echo $this->cssSw->getFullClass() ?> align="left" colspan="2">
                <?=_("Ressourcen auf dieser Ebene:")?>
            </td>
        </tr>
        <?
    }

    //private
    function showList() {
        ?>
        <tr>
            <td <? echo ($this->mode == "browse") ? " colspan=\"2\"" : "" ?>>
                <?$result_count=$this->list->showListObjects($this->open_object);
        if (!$result_count) {
            echo MessageBox::info(_("Es existieren keine Eintr&auml;ge auf dieser Ebene.")); ?>
            </td>
        </tr>
            <?
        }
}

    //private
    function showSearchList($check_assigns = FALSE) {
        ?>
        <tr>
            <td <? echo ($this->mode == "browse") ? " colspan=\"2\"" : "" ?>>
                <?$result_count=$this->list->showSearchList($this->searchArray, $check_assigns);
        if (!$result_count) {
            echo MessageBox::info(_("Es wurden keine Eintr&auml;ge zu Ihren Suchkriterien gefunden.")); ?>
            </td>
        </tr>
            <?
        }
    }

    //private
    function showSearch() {
        ?>
        <form method="post" action="<?= URLHelper::getLink('?search_send=yes&quick_view=search&quick_view_mode='. Request::option('view_mode')) ?>">
            <?= CSRFProtection::tokenTag() ?>
            <table border=0 celpadding=2 cellspacing=0 width="99%" align="center">
                <?
                $this->searchForm();
                if (!$this->searchArray) {
                    if ($this->mode == "browse")
                        $this->browseLevels();
                    if ($this->check_assigns)
                        $this->showTimeRange();
                    if ($this->mode == "properties")
                        $this->showProperties();
                    if ($this->mode == "browse")
                        $this->showList();
                } else {
                    if ($this->check_assigns)
                        $this->showTimeRange();
                    if ($this->mode == "properties")
                        $this->showProperties();
                    $this->showSearchList(($_SESSION['resources_data']["check_assigns"]) ? TRUE : FALSE);

                }
                ?>
            </table>
        </form>
            <br>
        <?
    }
}
