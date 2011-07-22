<?
# Lifter002: TODO
# Lifter005: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* ShowToolsRequests.class.php
*
* room-management tool for room-admins
*
*
* @author       Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup      resources
* @module       ToolsRequestResolve.class.php
* @package      resources
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// ShowToolsRequests.class.php
// die Suchmaschine fuer Ressourcen
// Copyright (C) 2004 Cornelis Kater <ckater@gwdg.de>, data-quest GmbH <info@data-quest.de>
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

require_once ('lib/classes/cssClassSwitcher.inc.php');
require_once ($RELATIVE_PATH_RESOURCES.'/lib/RoomRequest.class.php');
require_once ($RELATIVE_PATH_RESOURCES.'/lib/RoomGroups.class.php');
require_once ('lib/classes/Seminar.class.php');


$cssSw = new cssClassSwitcher;

/**
* ShowToolsRequests, room-management tool for room-admin
*
* @access   public
* @author   Cornelis Kater <kater@data-quest.de>
* @package  resources
**/
class ShowToolsRequests
{
    var $db;
    var $db2;
    var $cssSw;         //the cssClassSwitcher
    var $requests;          //the requests i'am responsibel for
    var $semester_id;
    var $show_requests_no_time = false;

    function ShowToolsRequests($semester_id, $resolve_requests_no_time = null)
    {
        $this->db = new DB_Seminar;
        $this->db2 = new DB_Seminar;
        if (!$semester_id){
            $this->semester_id = SemesterData::GetSemesterIdByDate(time());
        } else {
            $this->semester_id = $semester_id;
        }
        if(!is_null($resolve_requests_no_time)){
            $this->show_requests_no_time = !$resolve_requests_no_time;
        }
    }

    function getMyOpenSemRequests() {
        $this->restoreOpenRequests();
        return (int)$this->requests_stats_open['my_sem'];
    }

    function getMyOpenNoTimeRequests() {
        $this->restoreOpenRequests();
        return (int)$this->requests_stats_open['no_time'];
    }

    function getMyOpenResRequests() {
        $this->restoreOpenRequests();
        return (int)$this->requests_stats_open['my_res'];
    }

    function getMyOpenRequests() {
        $this->restoreOpenRequests();
        return (int)$this->requests_stats_open['sum'];
    }

    function restoreOpenRequests(){
        if (is_null($this->requests)){
            $this->requests = (array)getMyRoomRequests($GLOBALS['user']->id, $this->semester_id, true);
            foreach ($this->requests as $val) {
                $this->requests_stats_open['sum'] += !$val["closed"] && ($val["have_times"] || $this->show_requests_no_time);
                $this->requests_stats_open['my_res'] += !$val["closed"] && $val["my_res"] && ($val["have_times"] || $this->show_requests_no_time);
                $this->requests_stats_open['my_sem'] += !$val["closed"] && $val["my_sem"] && ($val["have_times"] || $this->show_requests_no_time);
                $this->requests_stats_open['no_time'] += !$val["closed"] && !$val["have_times"];
            }
        }
    }

    function getMyRequestedRooms(){
        $db = DBManager::get();
        $ret = array();
        $res_requests = array_filter($this->requests, create_function('$val', 'return (!$val["closed"] && $val["my_res"] && ($val["have_times"] || ' . (int)$this->show_requests_no_time . '));'));
        if(count($res_requests)){
            $ret = $db->query("SELECT ro.resource_id, ro.name, COUNT(ro.resource_id) as anzahl
                            FROM resources_requests rr
                            INNER JOIN resources_objects ro
                            USING ( resource_id )
                            WHERE rr.request_id
                            IN (
                            " . join(',', array_map(array($db, 'quote'), array_keys($res_requests))) . "
                            ) GROUP BY ro.resource_id ORDER BY ro.name")->fetchAll(PDO::FETCH_ASSOC);
        }
        return $ret;
    }

    function selectSemInstituteNames($inst_id) {
        $query = sprintf("SELECT a.Name AS inst_name, b.Name AS fak_name FROM Institute a LEFT JOIN Institute b ON (a.fakultaets_id = b.Institut_id) WHERE a.Institut_id = '%s' ", $inst_id);
        $this->db->query($query);
        $this->db->next_record();
        return;
    }

    function selectDates($seminar_id, $termin_id = '') {
        if (!$termin_id) {
            if ($GLOBALS['RESOURCES_HIDE_PAST_SINGLE_DATES']) {
                $query = sprintf("SELECT *, resource_id FROM termine LEFT JOIN resources_assign ra ON (ra.assign_user_id = termine.termin_id) WHERE date >= ".(time()-3600)." AND range_id = '%s' ORDER BY date, content", $seminar_id);
            } else {
                $query = sprintf("SELECT *, resource_id FROM termine LEFT JOIN resources_assign ra ON (ra.assign_user_id = termine.termin_id) WHERE range_id = '%s' ORDER BY date, content", $seminar_id);
            }
        } else {
            $query = sprintf("SELECT *, resource_id FROM termine LEFT JOIN resources_assign ra ON (ra.assign_user_id = termine.termin_id) WHERE range_id = '%s' %s ORDER BY date, content", $seminar_id, "AND termin_id = '".$termin_id."'");
        }

        $this->db->query($query);
        return;
    }

    function showToolStart() {
        global $PHP_SELF, $cssSw;

        $open_requests = $this->getMyOpenRequests();

        ?>
        <table border=0 celpadding=2 cellspacing=0 width="99%" align="center">
        <form method="POST" name="tools_requests_form" action="<?echo $PHP_SELF ?>?tools_requests_start=1">
            <?= CSRFProtection::tokenTag() ?>
            <input type="hidden" name="view" value="edit_request">
            <tr>
                <td class="<? echo $cssSw->getClass() ?>" width="4%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>">
                <table border="0" cellpadding="2" cellspacing="2">
                <tr>
                <td>
                <?=SemesterData::GetSemesterSelector(array('name' => 'tools_requests_sem_choose', 'onChange' => 'document.tools_requests_form.submit()'), $this->semester_id, 'semester_id',false)?>
                <?=makeButton("auswaehlen",'input',_("Semester auswählen"),'tools_requests_sem_choose_button')?>
                </td>
                <td class="<? echo $cssSw->getClass() ?>" style="padding-left:10px">
                <b><?=_("Status:")?></b>
                <br>
                    <?
                    if ($open_requests){
                        printf (_("Es liegen insgesamt <b>%s</b> nicht aufgel&ouml;ste Anfragen vor - <br>davon <b>%s</b> von Veranstaltungen und <b>%s</b> auf Ressourcen, auf die Sie Zugriff haben."), $open_requests, (int)$this->getMyOpenSemRequests(), (int)$this->getMyOpenResRequests());
                    } else {
                        printf (_("Es liegen im Augenblick keine unaufgel&ouml;sten Anfragen vor."));
                    }
                    if (($no_time = $this->getMyOpenNoTimeRequests())){
                        if(!$this->show_requests_no_time){
                            printf("<br>" . _("(<b>%s</b> weitere Anfragen haben keine Zeiten eingetragen, oder beziehen sich auf vergangene Termine.)"), $no_time);
                        } else {
                            printf("<br>" . _("(<b>%s</b> der Anfragen haben keine Zeiten eingetragen, oder beziehen sich auf vergangene Termine.)"), $no_time);
                        }
                    }
                    ?>
                </td>
                </tr>
                <tr>
                <td colspan="2">
                    <input onChange="document.tools_requests_form.submit()" name="resolve_requests_no_time" id="resolve_requests_no_time_check" type="checkbox" <?=(!$this->show_requests_no_time ? 'checked' : '')?> value="1">
                    &nbsp;<label for="resolve_requests_no_time_check"><?=_("Anfragen ohne eingetragene Zeiten oder auf vergangene Termine ausblenden")?></label>
                </td>
                </tr>
                </table>
                </td>
            </tr>
            <? $cssSw->switchClass();
            if ($open_requests) {
            ?>
            <tr>
                <td class="<? echo $cssSw->getClass() ?>" width="4%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>"><font size=-1><b><?=_("Optionen beim Aufl&ouml;sen")?></b><br>
                    <?
                    print _("Sie k&ouml;nnen die vorliegenden Anfragen mit folgenden Optionen aufl&ouml;sen:");
                    ?>
                    <br><br></font>
                    <table border="0" cellpadding="2" cellspacing="0">
                        <tr>
                            <td width="48%" valign="top">
                                <font size="-1">
                                <?
                                print _("Art der Anfragen:");
                                print "<br><br><input type=\"RADIO\" name=\"resolve_requests_mode\" value=\"all\" checked>&nbsp;"._("alle Anfragen");
                                print "<br><input type=\"RADIO\" name=\"resolve_requests_mode\" value=\"sem\">&nbsp;"._("nur Anfragen von meinen Veranstaltungen");
                                print "<br><input type=\"RADIO\" name=\"resolve_requests_mode\" value=\"res\">&nbsp;"._("nur Anfragen auf meine R&auml;ume");
                                print "<br><input type=\"RADIO\" id=\"resolve_requests_one_res_check\" name=\"resolve_requests_mode\" value=\"one_res\">&nbsp;"._("nur Anfragen auf einen Raum:");
                                print "<br><span style=\"padding-left:20px;\"><select onchange=\"$('#resolve_requests_one_res_check').attr('checked', true);\"name=\"resolve_requests_one_res\">";
                                foreach(array_merge(array(array('resource_id' => '', 'name' => _(" -keine Auswahl - "))), $this->getMyRequestedRooms()) as $one){
                                    echo '<option value="'.$one['resource_id'].'">'.htmlready($one['name'] . ($one['anzahl'] ? ' (' . $one['anzahl']. ')' : '')).'</option>';
                                }
                                print "</select></span>";

                                ?>
                                </font>
                            </td>
                            <td width="4%">
                            &nbsp;
                            </td>
                            <td width="48%">
                                <font size="-1">
                                <?
                                print _("Sortierung der Anfragen:");
                                print "<br><br><input type=\"RADIO\" name=\"resolve_requests_order\" value=\"complex\" checked>&nbsp;"._("komplexere zuerst (Raumgr&ouml;&szlig;e und  gew&uuml;nschte Eigenschaften)");
                                print "<br><input type=\"RADIO\" name=\"resolve_requests_order\" value=\"oldest\">&nbsp;"._("&auml;ltere zuerst");
                                print "<br><input type=\"RADIO\" name=\"resolve_requests_order\" value=\"newest\">&nbsp;"._("neue zuerst");
                                print "<br><input type=\"RADIO\" name=\"resolve_requests_order\" value=\"urgent\">&nbsp;"._("dringendere zuerst");
                                ?>
                                </font>
                            </td>
                        </tr>

                    </table>
                </td>
            </tr>
            <? $cssSw->switchClass(); ?>
            <tr>
                <td class="<? echo $cssSw->getClass() ?>" width="4%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" align="center">
                    <?
                    print "<input type=\"IMAGE\" name=\"start_multiple_mode\" ".makeButton("starten", "src").">";
                    ?>
            </td>
            </tr>
            <?
            }
            ?>
            </form>
        </table>
        <br><br>
        <?
    }

    function showRequestList() {
        global $resources_data, $_fullname_sql, $CANONICAL_RELATIVE_PATH_STUDIP;
        require_once("lib/classes/ZebraTable.class.php");

        $license_to_kill = (get_config('RESOURCES_ALLOW_DELETE_REQUESTS') && getGlobalPerms($GLOBALS['user']->id) == 'admin');
        if ($license_to_kill){
            echo chr(10) . '<script type="text/javascript">';
            echo chr(10) . '
            function auswahl_umkehr(){
                my_elements = document.forms[\'list_requests_form\'].elements[\'requests_marked_to_kill[]\'];
                if(!my_elements.length){
                    if(my_elements.checked)
                        my_elements.checked = false;
                    else
                        my_elements.checked = true;
                } else {
                    for(i = 0; i < my_elements.length; ++i){
                        if(my_elements[i].checked)
                        my_elements[i].checked = false;
                        else
                        my_elements[i].checked = true;
                    }
                }
            }';
            echo chr(10) . '</script>';
            echo chr(10) . '<form name="list_requests_form" method="post" action="'.$GLOBALS['PHP_SELF'].'">';
            echo CSRFProtection::tokenTag();
            echo chr(10) . '<div align="right">
                <a href="#" onClick="auswahl_umkehr();return false;">'
                . makeButton('auswahlumkehr', 'img', _("Auswahl umkehren"))
                . '</a>&nbsp;&nbsp;'
                . makeButton('loeschen', 'input', _("Ausgewählte Anfragen löschen"), 'do_delete_requests')
                . '&nbsp;</div><br>';
        }
        $i = 0;
        $zt = new ZebraTable(array('width' => '99%', 'padding' => '1', 'align' => 'center'));
        $zt->switchClass();
        echo $zt->openRow();
        echo $zt->cell("&nbsp;", array("class" => "steelkante"));
        echo $zt->cell("<font size=\"-1\"><b>" . _("Z&auml;hler") . "</b></font>", array("class" => "steelkante", 'colspan' => '3'));
        echo $zt->cell("<font size=\"-1\"><b>" . _("V.-Nummer") . "</b></font>", array("class" => "steelkante"));
        echo $zt->cell("<font size=\"-1\"><b>" . _("Titel") . "</b></font>", array("class" => "steelkante"));
        echo $zt->cell("<font size=\"-1\"><b>" . _("Dozenten") . "</b></font>", array("class" => "steelkante"));
        echo $zt->cell("<font size=\"-1\"><b>" . _("Anfrager") . "</b></font>", array("class" => "steelkante"));
        echo $zt->cell("<font size=\"-1\"><b>" . _("Start-Semester") . "<b></font>", array("class" => "steelkante"));
        if ($license_to_kill){
            echo $zt->cell("<font size=\"-1\"><b>" . _("l&ouml;schen") . "<b></font>", array("class" => "steelkante", 'width' => '5%'));
        }
        echo $zt->closeRow();
        ?>
        <?
        foreach ($resources_data['requests_working_on'] as $key => $val) {
            $i++;
            if ($resources_data['requests_open'][$val['request_id']] || !$resources_data['skip_closed_requests']) {
                $reqObj = new RoomRequest($val['request_id']);
                $semObj = new Seminar($reqObj->getSeminarId());

                if ($semObj->getName() != "") {
                    echo $zt->openRow();
                    //echo "<font size=\"-1\">";
                    echo $zt->cell("&nbsp;");
                    echo $zt->cell("<font size=\"-1\">$i.</font>");
                    echo $zt->cell("<a href=\"resources.php?view=edit_request&edit=".$val['request_id']."\">".Assets::img('icons/16/blue/edit.png', tooltip(_("Anfrage bearbeiten")))."</a>");
                    echo $zt->cell((($resources_data['requests_open'][$val['request_id']]) ? '' : Assets::img('icons/16/green/accept.png'))."</font>");
                    echo $zt->cell("<font size=\"-1\">".htmlReady($semObj->seminar_number)."</font>");
                    echo $zt->cell("<font size=\"-1\"><a href=\"details.php?sem_id=".$semObj->getId()."&send_from_search=true&send_from_search_page=".urlencode($CANONICAL_RELATIVE_PATH_STUDIP."resources.php?view=list_requests")."\">".my_substr(htmlReady($semObj->getName()),0,50)."</a><br></font>");
                    echo $zt->openCell();
                    echo "<font size=\"-1\">";
                    $k = false;
                    foreach ($semObj->getMembers('dozent') as $doz) {
                        if ($k) echo ", ";
                        echo "<a href=\"about.php?username={$doz['username']}\">".HtmlReady($doz['fullname'])."</a>";
                        $k = true;
                    }
                    echo "</font>";
                    $this->selectSemInstituteNames($semObj->getInstitutId());
                    if (!$this->all_semester) {
                        $semester = new SemesterData();
                        $this->all_semester = $semester->getAllSemesterData();
                    }
                    foreach ($this->all_semester as $one_sem) {
                        if ($one_sem['beginn'] == $semObj->semester_start_time) {
                            $cursem = $one_sem['name'];
                        }
                    }

                    echo $zt->closeCell();
                    echo $zt->cell("<font size=\"-1\"><a href=\"about.php?username=".get_username($reqObj->user_id)."\">".get_fullname($reqObj->user_id)."</a></font>");
                    echo $zt->cell("<font size=\"-1\">$cursem</font>");
                    if ($license_to_kill){
                        echo $zt->cell("<font size=\"-1\"><input type=\"checkbox\" name=\"requests_marked_to_kill[]\" value=\"{$val['request_id']}\"></font>", array('align' => 'center'));
                    }
                    echo $zt->closeRow();
                }
            }
        }
        echo $zt->close();
        if ($license_to_kill){
            echo chr(10) . '</form>';
        }
    }

    /**
     *
     * @param $request_id
     */
    function showRequest($request_id)
    {
        global $PHP_SELF, $cssSw, $resources_data, $perm;

        $reqObj = new RoomRequest($request_id);
        $semObj = new Seminar($reqObj->getSeminarId());
        $sem_link = $perm->have_studip_perm('tutor', $semObj->getId()) ?
            "seminar_main.php?auswahl=" . $semObj->getId() :
            "details.php?sem_id=" . $semObj->getId() . "&send_from_search=1&send_from_search_page="
            . UrlHelper::getLink("resources.php?working_on_request=$request_id");
        ?>
        <form method="POST" action="<?echo UrlHelper::getLink('?working_on_request=' . $request_id);?>">
        <?= CSRFProtection::tokenTag() ?>
        <input type="hidden" name="view" value="edit_request">
        <table border=0 celpadding=2 cellspacing=0 width="99%" align="center">
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" colspan="2" width="96%" valign="top">
                    <a href="<?=UrlHelper::getLink($sem_link)?>">
                        <b><?= $semObj->seminar_number ? htmlReady($semObj->seminar_number).':' : '' ?><?=htmlReady($semObj->getName())?></b>
                    </a>
                    <font size="-1">
                        <br>
                        <?
                        $this->selectSemInstituteNames($semObj->getInstitutId());

                        print "&nbsp;&nbsp;&nbsp;&nbsp;"._("Art der Anfrage:")." ".($reqObj->getTerminId() ? _("Einzeltermin einer Veranstaltung") : ($reqObj->getMetadateId() ? _("Regelmäßige Zeit einer Veranstaltung") : _("alle Termine einer Veranstaltung")))."<br>";
                        print "&nbsp;&nbsp;&nbsp;&nbsp;"._("Erstellt von:")." <a href=\"".UrlHelper::getLink('about.php?username='.get_username($reqObj->getUserId()))."\">".htmlReady(get_fullname($reqObj->getUserId()))."</a><br>";
                        print "&nbsp;&nbsp;&nbsp;&nbsp;"._("Lehrende: ");
                        foreach ($semObj->getMembers('dozent') as $doz) {
                            if ($dozent){
                                echo ", ";
                            }
                            echo '<a href ="'. URLHelper::getLink('about.php?username='.$doz['username']). '">'.HtmlReady($doz['fullname'])."</a>";
                            $dozent = true;
                        }
                        print "<br>";
                        print "&nbsp;&nbsp;&nbsp;&nbsp;"._("verantwortliche Einrichtung:")." ".htmlReady($this->db->f("inst_name"))."<br>";
                        print "&nbsp;&nbsp;&nbsp;&nbsp;"._("verantwortliche Fakult&auml;t:")." ".htmlReady($this->db->f("fak_name"))."<br>";
                        print "&nbsp;&nbsp;&nbsp;&nbsp;"._("aktuelle Teilnehmerzahl:")." ".$semObj->getNumberOfParticipants('total').'<br>';
                        ?>
                    </font>
                </td>
            </tr>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="35%" valign="top">
                    <font size="-1"><b><?=_("angeforderte Belegungszeiten:")?></b><br><br>
                    <?
                    $dates = $semObj->getGroupedDates($reqObj->getTerminId(),$reqObj->getMetadateId());
                    if (count($dates)) {
                            $i = 1;
                            foreach ($dates['info'] as $info) {
                                $name = $info['name'];
                                if ($info['weekend']) $name = '<span style="color:red">'. $info['name'] . '</span>';
                                printf ("<font color=\"blue\"><i><b>%s</b></i></font>. %s<br>", $i, $name);
                                $i++;
                            }

                        if ($reqObj->getType() != 'date') {
                            echo _("regelmäßige Buchung ab:")." ".strftime("%x", $dates['first_event']);
                            }
                        } else {
                            print _("nicht angegeben");
                        }
                    ?>
                    </font>
                </td>
                <td style="border-left:1px dotted black; background-image: url('<?= $GLOBALS['ASSETS_URL'] ?>images/steel4.jpg')" width="51%" rowspan="3" valign="top">
                    <table cellpadding="2" cellspacing="0" border="0" width="90%">
                        <tr>
                            <td width="70%">
                                <font size="-1"><b><?=_("angeforderter Raum:")?></b></font>
                            </td>
                            <?
                            unset($resObj);
                            $cols=0;
                            if ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["grouping"]) {
                                if (is_array($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["groups"]))
                                    foreach ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["groups"] as $key => $val) {
                                        $cols++;
                                        print "<td width=\"1%\" align=\"left\"><font size=\"-1\" color=\"blue\"><i><b>".$cols.".</b></i></font></td>";
                                    }
                            } else {
                                if (is_array($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["assign_objects"]))
                                    foreach ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["assign_objects"] as $key => $val) {
                                        $cols++;
                                        print "<td width=\1%\" align=\"left\"><font size=\"-1\" color=\"blue\"><i><b>".$cols.".</b></i></font></td>";
                                    }
                            }
                            ?>
                            <td width="29%" align="right">
                                <!--<font style="font-size:10px;color:blue;"><?//=_("Kapazit&auml;t")?></font>-->
                            </td>
                        </tr>
                        <tr>
                            <td width="70%">
                            <?
                            if ($request_resource_id = $reqObj->getResourceId()) {
                                $resObj = ResourceObject::Factory($request_resource_id);
                                print $resObj->getFormattedLink($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["first_event"]);
                                print ' <img class="text-top" src="' . Assets::image_path(($resObj->getOwnerId() == 'global') ? 'icons/16/red/info-circle.png' : 'icons/16/grey/info-circle.png') . '" ' . tooltip(_("Der ausgewählte Raum bietet folgende der wünschbaren Eigenschaften:")." \n".$resObj->getPlainProperties(TRUE), TRUE, TRUE). '>';
                                if ($resObj->getOwnerId() == 'global') {
                                    print ' [global]';
                                }
                            } else
                                print _("Es wurde kein Raum angefordert.");

                            ?>
                            </td>
                            <?
                            $i=0;

                            if ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["grouping"]) {
                                foreach ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["groups"] as $key => $val) {
                                    print "<td width=\"1%\" nowrap><font size=\"-1\">";
                                    if ($request_resource_id) {
                                        if ($request_resource_id == $val["resource_id"]) {
                                            print Assets::img('icons/16/green/accept.png', tooltip(_("Dieser Raum ist augenblicklich gebucht"), TRUE, TRUE));
                                        } else {
                                            $overlap_status = $this->showGroupOverlapStatus($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["detected_overlaps"][$request_resource_id], $val["events_count"], $val["overlap_events_count"][$request_resource_id], $val["termin_ids"]);
                                            print $overlap_status["html"];
                                            printf ("<input type=\"radio\" name=\"selected_resource_id[%s]\" value=\"%s\" %s %s>",
                                            $i, $request_resource_id,
                                            ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["selected_resources"][$i] == $request_resource_id) ? "checked" : "",
                                            ($overlap_status["status"] == 2 || !ResourcesUserRoomsList::CheckUserResource($request_resource_id)) ? "disabled" : "");
                                        }
                                    } else
                                        print "&nbsp;";
                                    print "</font></td>";
                                    $i++;
                                }
                            } elseif (is_array($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["assign_objects"])) {
                                foreach ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["assign_objects"] as $key => $val) {
                                    print "<td width=\"1%\" nowrap><font size=\"-1\">";
                                    if ($request_resource_id) {
                                        if ($request_resource_id == $val["resource_id"]) {
                                            print Assets::img('icons/16/green/accept.png', tooltip(_("Dieser Raum ist augenblicklich gebucht"), TRUE, TRUE));
                                        } else {
                                            $overlap_status = $this->showOverlapStatus($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["detected_overlaps"][$request_resource_id][$key], $val["events_count"], $val["overlap_events_count"][$request_resource_id]);
                                            print $overlap_status["html"];
                                            printf ("<input type=\"radio\" name=\"selected_resource_id[%s]\" value=\"%s\" %s %s>",
                                            ($semObj->getMetaDateType() == 1) ? $val["termin_id"] : $i, $request_resource_id,
                                            ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["selected_resources"][($semObj->getMetaDateType() == 1) ? $val2["termin_id"] : $i] == $request_resource_id) ? "checked" : "",
                                            ($overlap_status["status"] == 2 || !ResourcesUserRoomsList::CheckUserResource($request_resource_id)) ? "disabled" : "");
                                        }
                                    } else
                                        print "&nbsp;";
                                    print "</font></td>";
                                    $i++;
                                }
                            }
                            ?>
                            <td width="29%" align="right">
                                <?
                                if (is_object($resObj)) {
                                    $seats = $resObj->getSeats();
                                    $requested_seats = $reqObj->getSeats();
                                    if ((is_numeric($seats)) && (is_numeric($requested_seats))) {
                                        $percent_diff = (100 / $requested_seats) * $seats;
                                        if ($percent_diff > 0)
                                            $percent_diff = "+".$percent_diff;
                                        if ($percent_diff < 0)
                                            $percent_diff = "-".$percent_diff;
                                        print "<font style=\"font-size:10px;\">".round($percent_diff)."%</font>";
                                    }
                                }
                                ?>
                            </td>
                        </tr>
                        <?
                        if (get_config('RESOURCES_ENABLE_GROUPING')) {
                            $room_group = RoomGroups::GetInstance();
                            $group_id = $resources_data['actual_room_group'];
                            ?>
                        <tr>
                            <td style="border-top:1px solid;" width="100%" colspan="<?=$cols+2?>">
                                <font size="-1"><b><?=_("Raumgruppe berücksichtigen:")?></b></font>
                            </td>
                        </tr>
                        <tr>
                        <td colspan="<?=$cols?>"><font size="-1">
                        <select name="request_tool_choose_group">
                        <option <?=(is_null($group_id) ? 'selected' : '')?> value="-"><?=_("Keine Raumgruppe anzeigen")?></option>
                        <?
                        foreach($room_group->getAvailableGroups() as $gid){
                        echo '<option value="'.$gid.'" '
                            . (!is_null($group_id) && $group_id == $gid ? 'selected' : '') . '>'
                            .htmlReady(my_substr($room_group->getGroupName($gid),0,45))
                            .' ('.$room_group->getGroupCount($gid).')</option>';
                        }
                        ?>
                        </select>
                        </font>
                        </td>
                        <td colspan="2"><font size="-1">
                        <input type="image" name="request_tool_group" align="middle" <?=makeButton("auswaehlen", "src") ?> border=0 ><br>
                        </font>
                        </td>
                        </tr>
                        <?
                        if ($room_group->getGroupCount($group_id)){
                            foreach ($room_group->getGroupContent($group_id) as $key) {
                        ?>
                        <tr>
                            <td width="70%">
                                <?
                                $resObj = ResourceObject::Factory($key);
                                print $resObj->getFormattedLink($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["first_event"]);
                                print ' <img class="text-top" src="' . Assets::image_path(($resObj->getOwnerId() == 'global') ? 'icons/16/red/info-circle.png' : 'icons/16/grey/info-circle.png') . '" ' . tooltip(_("Der ausgewählte Raum bietet folgende der wünschbaren Eigenschaften:")." \n".$resObj->getPlainProperties(TRUE), TRUE, TRUE). '>';
                                if ($resObj->getOwnerId() == 'global') {
                                    print ' [global]';
                                }
                            ?>
                            </td>
                            <?
                            $i=0;
                            if ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["grouping"]) {
                                if (is_array($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["groups"])) {
                                    foreach ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["groups"] as $key2 => $val2) {
                                        print "<td width=\"1%\" nowrap><font size=\"-1\">";
                                        if ($key == $val2["resource_id"]) {
                                            print Assets::img('icons/16/green/accept.png', tooltip(_("Dieser Raum ist augenblicklich gebucht"), TRUE, TRUE));
                                        } else {
                                            $overlap_status = $this->showGroupOverlapStatus($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["detected_overlaps"][$key], $val2["events_count"], $val2["overlap_events_count"][$resObj->getId()], $val2["termin_ids"]);
                                            print $overlap_status["html"];
                                            printf ("<input type=\"radio\" name=\"selected_resource_id[%s]\" value=\"%s\" %s %s>", $i, $key,
                                            ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["selected_resources"][$i] == $key) ? "checked" : "",
                                            ($overlap_status["status"] == 2 || !ResourcesUserRoomsList::CheckUserResource($key)) ? "disabled" : "");
                                        }
                                        print "</font></td>";
                                        $i++;
                                    }
                                }
                            } else {
                                if (is_array($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["assign_objects"])) {
                                    foreach ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["assign_objects"] as $key2 => $val2) {
                                        print "<td width=\"1%\" nowrap><font size=\"-1\">";
                                        if ($key == $val2["resource_id"]) {
                                            print Assets::img('icons/16/green/accept.png', tooltip(_("Dieser Raum ist augenblicklich gebucht"), TRUE, TRUE));
                                        } else {
                                            $overlap_status = $this->showOverlapStatus($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["detected_overlaps"][$key][$key2], $val2["events_count"], $val2["overlap_events_count"][$resObj->getId()]);
                                            print $overlap_status["html"];
                                            printf ("<input type=\"radio\" name=\"selected_resource_id[%s]\" value=\"%s\" %s %s>",
                                            ($semObj->getMetaDateType() == 1) ? $val2["termin_id"] : $i,
                                            $key,
                                            ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["selected_resources"][($semObj->getMetaDateType() == 1) ? $val2["termin_id"] : $i] == $key) ? "checked" : "",
                                            ($overlap_status["status"] == 2 || !ResourcesUserRoomsList::CheckUserResource($key)) ? "disabled" : "");
                                        }
                                        print "</font></td>";
                                        $i++;
                                    }
                                }
                            }
                            ?>
                            <td width="29%" align="right">
                                <?
                                if (is_object($resObj)) {
                                    $seats = $resObj->getSeats();
                                    $requested_seats = $reqObj->getSeats();
                                    if ((is_numeric($seats)) && (is_numeric($requested_seats))) {
                                        $percent_diff = (100 / $requested_seats) * $seats;
                                        if ($percent_diff > 0)
                                            $percent_diff = "+".$percent_diff;
                                        if ($percent_diff < 0)
                                            $percent_diff = "-".$percent_diff;
                                        print "<font style=\"font-size:10px;\">".round($percent_diff)."%</font>";
                                    }
                                }
                                ?>
                            </td>
                        </tr>
                        <?
                                }
                            }
                        }
                        ?>
                        <tr>
                            <td style="border-top:1px solid;" width="100%" colspan="<?=$cols+2?>">
                                <font size="-1"><b><?=_("weitere passende Räume:")?></b>
                                </font>
                            </td>
                        </tr>
                        <?
                        if (is_array($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["considered_resources"]))
                            foreach ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["considered_resources"] as $key=>$val) {
                                if ($val["type"] == "matching")
                                    $matching_rooms[$key] = TRUE;
                                if ($val["type"] == "clipped")
                                    $clipped_rooms[$key] = TRUE;
                                if ($val["type"] == "grouped")
                                    $grouped_rooms[$key] = TRUE;
                            }

                        if (sizeof($matching_rooms)) {
                            foreach ($matching_rooms as $key=>$val) {
                            ?>
                        <tr>
                            <td width="70%">
                                <?
                                $resObj = ResourceObject::Factory($key);
                                print $resObj->getFormattedLink($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["first_event"]);
                                print ' <img class="text-top" src="' . Assets::image_path(($resObj->getOwnerId() == 'global') ? 'icons/16/red/info-circle.png' : 'icons/16/grey/info-circle.png') . '" ' . tooltip(_("Der ausgewählte Raum bietet folgende der wünschbaren Eigenschaften:")." \n".$resObj->getPlainProperties(TRUE), TRUE, TRUE). '>';
                                if ($resObj->getOwnerId() == 'global') {
                                    print ' [global]';
                                }
                            ?>
                            </td>
                            <?
                            $i=0;
                            if ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["grouping"]) {
                                if (is_array($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["groups"])) {
                                    foreach ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["groups"] as $key2 => $val2) {
                                        print "<td width=\"1%\" nowrap><font size=\"-1\">";
                                        if ($key == $val2["resource_id"]) {
                                            print Assets::img('icons/16/green/accept.png', tooltip(_("Dieser Raum ist augenblicklich gebucht"), TRUE, TRUE));
                                        } else {
                                            $overlap_status = $this->showGroupOverlapStatus($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["detected_overlaps"][$key], $val2["events_count"], $val2["overlap_events_count"][$resObj->getId()], $val2["termin_ids"]);
                                            print $overlap_status["html"];
                                            printf ("<input type=\"radio\" name=\"selected_resource_id[%s]\" value=\"%s\" %s %s>",
                                            $i, $key, ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["selected_resources"][$i] == $key) ? "checked" : "",
                                            ($overlap_status["status"] == 2 || !ResourcesUserRoomsList::CheckUserResource($key)) ? "disabled" : "");
                                        }
                                        print "</font></td>";
                                        $i++;
                                    }
                                }
                            } else {
                                if (is_array($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["assign_objects"])) {
                                    foreach ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["assign_objects"] as $key2 => $val2) {
                                        print "<td width=\"1%\" nowrap><font size=\"-1\">";
                                        if ($key == $val2["resource_id"]) {
                                            print Assets::img('icons/16/green/accept.png', tooltip(_("Dieser Raum ist augenblicklich gebucht"), TRUE, TRUE));
                                        } else {
                                            $overlap_status = $this->showOverlapStatus($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["detected_overlaps"][$key][$key2], $val2["events_count"], $val2["overlap_events_count"][$resObj->getId()]);
                                            print $overlap_status["html"];
                                            printf ("<input type=\"radio\" name=\"selected_resource_id[%s]\" value=\"%s\" %s %s>",
                                            $i, $key,
                                            ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["selected_resources"][($semObj->getMetaDateType() == 1) ? $val2["termin_id"] : $i] == $key) ? "checked" : "",
                                            ($overlap_status["status"] == 2 || !ResourcesUserRoomsList::CheckUserResource($key)) ? "disabled" : "");
                                        }
                                        print "</font></td>";
                                        $i++;
                                    }
                                }
                            }
                            ?>
                            <td width="29%" align="right">
                                <?
                                if (is_object($resObj)) {
                                    $seats = $resObj->getSeats();
                                    $requested_seats = $reqObj->getSeats();
                                    if ((is_numeric($seats)) && (is_numeric($requested_seats))) {
                                        $percent_diff = (100 / $requested_seats) * $seats;
                                        if ($percent_diff > 0)
                                            $percent_diff = "+".$percent_diff;
                                        if ($percent_diff < 0)
                                            $percent_diff = "-".$percent_diff;
                                        print "<font style=\"font-size:10px;\">".round($percent_diff)."%</font>";
                                    }
                                }
                                ?>
                            </td>
                        </tr>
                            <?
                            }
                            ?>
                        <tr>
                            <td colspan="<?=$cols+2?>" align="center">
                                <font size="-1">
                                    <?=_("zeige R&auml;ume")?>
                                    <a href="<?=$PHP_SELF?>?dec_limit_low=1">-</a>
                                    <input type="text" name="search_rooms_limit_low" maxlength="2" size="1" style="font-size:8pt" value="<?=($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["search_limit_low"] + 1)?>">
                                    <a href="<?=$PHP_SELF?>?inc_limit_low=1">+</a>

                                    bis
                                    <a href="<?=$PHP_SELF?>?dec_limit_high=1">-</a>
                                    <input type="text" name="search_rooms_limit_high" maxlength="2" size="1" style="font-size:8pt" value="<?=$resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["search_limit_high"]?>">
                                    <a href="<?=$PHP_SELF?>?inc_limit_high=1">+</a>

                                    <input type="image" name="matching_rooms_limit_submit" src="<?= Assets::image_path('icons/16/yellow/arr_2up.png') ?>" <?=tooltip(_("ausgewählten Bereich anzeigen"))?>>
                                </font>
                            </td>
                        </tr>
                            <?
                        } else
                            print "<tr><td width=\"100%\" colspan=\"".($cols+1)."\"><font size=\"-1\">"._("keine gefunden")."</font></td></tr>";

                        //Clipped Rooms
                        if (sizeof($clipped_rooms)) {
                        ?>
                        <tr>
                            <td style="border-top:1px solid;" width="100%" colspan="<?=$cols+2?>">
                                <font size="-1"><b><?=_("R&auml;ume aus der Merkliste:")?></b></font>
                            </td>
                        </tr>
                        <?
                            foreach ($clipped_rooms as $key=>$val) {
                        ?>
                        <tr>
                            <td width="70%">
                                <?
                                $resObj = ResourceObject::Factory($key);
                                print $resObj->getFormattedLink($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["first_event"]);
                                print ' <img class="text-top" src="' . Assets::image_path(($resObj->getOwnerId() == 'global') ? 'icons/16/red/info-circle.png' : 'icons/16/grey/info-circle.png') . '" ' . tooltip(_("Der ausgewählte Raum bietet folgende der wünschbaren Eigenschaften:")." \n".$resObj->getPlainProperties(TRUE), TRUE, TRUE). '>';
                                if ($resObj->getOwnerId() == 'global') {
                                    print ' [global]';
                                }
                            ?>
                            </td>
                            <?
                            $i=0;
                            if ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["grouping"]) {
                                if (is_array($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["groups"])) {
                                    foreach ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["groups"] as $key2 => $val2) {
                                        print "<td width=\"1%\" nowrap><font size=\"-1\">";
                                        if ($key == $val2["resource_id"]) {
                                            print "<img src=\"".Assets::image_path('icons/16/blue/accept.png')." ".tooltip(_("Dieser Raum ist augenblicklich gebucht"), TRUE, TRUE).">";
                                        } else {
                                            $overlap_status = $this->showGroupOverlapStatus($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["detected_overlaps"][$key], $val2["events_count"], $val2["overlap_events_count"][$resObj->getId()], $val2["termin_ids"]);
                                            print $overlap_status["html"];
                                            printf ("<input type=\"radio\" name=\"selected_resource_id[%s]\" value=\"%s\" %s %s>",
                                            $i, $key,
                                            ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["selected_resources"][$i] == $key) ? "checked" : "",
                                            ($overlap_status["status"] == 2 || !ResourcesUserRoomsList::CheckUserResource($key)) ? "disabled" : "");
                                        }
                                        print "</font></td>";
                                        $i++;
                                    }
                                }
                            } else {
                                if (is_array($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["assign_objects"])) {
                                    foreach ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["assign_objects"] as $key2 => $val2) {
                                        print "<td width=\"1%\" nowrap><font size=\"-1\">";
                                        if ($key == $val2["resource_id"]) {
                                            print "<img src=\"".Assets::image_path('icons/16/blue/accept.png')." ".tooltip(_("Dieser Raum ist augenblicklich gebucht"), TRUE, TRUE).">";
                                        } else {
                                            $overlap_status = $this->showOverlapStatus($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["detected_overlaps"][$key][$key2], $val2["events_count"], $val2["overlap_events_count"][$resObj->getId()]);
                                            print $overlap_status["html"];
                                            printf ("<input type=\"radio\" name=\"selected_resource_id[%s]\" value=\"%s\" %s %s>",
                                            ($semObj->getMetaDateType() == 1) ? $val2["termin_id"] : $i,
                                            $key,
                                            ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["selected_resources"][($semObj->getMetaDateType() == 1) ? $val2["termin_id"] : $i] == $key) ? "checked" : "",
                                            ($overlap_status["status"] == 2 || !ResourcesUserRoomsList::CheckUserResource($key)) ? "disabled" : "");
                                        }
                                        print "</font></td>";
                                        $i++;
                                    }
                                }
                            }
                            ?>
                            <td width="29%" align="right">
                                <?
                                if (is_object($resObj)) {
                                    $seats = $resObj->getSeats();
                                    $requested_seats = $reqObj->getSeats();
                                    if ((is_numeric($seats)) && (is_numeric($requested_seats))) {
                                        $percent_diff = (100 / $requested_seats) * $seats;
                                        if ($percent_diff > 0)
                                            $percent_diff = "+".$percent_diff;
                                        if ($percent_diff < 0)
                                            $percent_diff = "-".$percent_diff;
                                        print "<font style=\"font-size:10px;\">".round($percent_diff)."%</font>";
                                    }
                                }
                                ?>
                            </td>
                        </font></td>
                        </tr>
                        <?
                            }
                        }
                        ?>
                    </table>
                </td>
            </tr>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="35%" valign="top">
                    <font size="-1"><b><?=_("gew&uuml;nschte Raumeigenschaften:")?></b><br><br>
                    <?
                    $properties = $reqObj->getProperties();
                    if (sizeof($properties)) {
                    ?>
                        <table width="99%" cellspacing="0" cellpadding="2" border="0">
                        <?

                        foreach ($properties as $key=>$val) {
                            ?>
                            <tr>
                                <td width="70%">
                                    <li><font size="-1"><?=htmlReady($val["name"])?></font></li>
                                </td>
                                <td width="30%"><font size="-1">
                                <?
                                switch ($val["type"]) {
                                    case "bool":
                                        /*printf ("%s", ($val["state"]) ?  htmlReady($val["options"]) : " - ");*/
                                    break;
                                    case "num":
                                    case "text":
                                        print htmlReady($val["state"]);
                                    break;
                                    case "select":
                                        $options=explode (";",$val["options"]);
                                        foreach ($options as $a) {
                                            if ($val["state"] == $a)
                                                print htmlReady($a);
                                        }
                                    break;
                                }
                                ?></font>
                                </td>
                            </tr>
                            <?
                        }
                        ?>
                        </table>
                        <?
                    } else
                        print _("Es wurden keine Raumeigenschaften gew&uuml;nscht.");
                    ?>
                    </font>
                </td>
            </tr>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="35%" valign="top">
                    <font size="-1"><b><?=_("Kommentar des Anfragenden:")?></b><br><br>
                    <?
                    if ($comment = $reqObj->getComment())
                        print $comment;
                    else
                        print _("Es wurde kein Kommentar eingegeben");
                    ?>
                    </font>
                </td>

            </tr>

            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" colspan="2" width="96%" valign="top" align="center">
                <?
                // can we dec?
                if ($resources_data["requests_working_pos"] > 0) {
                    $d = -1;
                    if ($resources_data["skip_closed_requests"])
                        while ((!$resources_data["requests_open"][$resources_data["requests_working_on"][$resources_data["requests_working_pos"] + $d]["request_id"]]) && ($resources_data["requests_working_pos"] + $d > 0))
                            $d--;
                    if ((sizeof($resources_data["requests_open"]) > 1) && (($resources_data["requests_open"][$resources_data["requests_working_on"][$resources_data["requests_working_pos"] + $d]["request_id"]]) || (!$resources_data["skip_closed_requests"])))
                        $inc_possible = TRUE;
                }
                if ($inc_possible) {
                    print("&nbsp;<input type=\"IMAGE\" name=\"dec_request\" ".makeButton("zurueck", "src")." border=\"0\">");
                }
                print("&nbsp;<input type=\"IMAGE\" name=\"cancel_edit_request\" ".makeButton("abbrechen", "src")." border=\"0\">");
                print("&nbsp;<input type=\"IMAGE\" name=\"delete_request\" ".makeButton("loeschen","src")." border=\"0\">");
                if ((($reqObj->getResourceId()) || (sizeof($matching_rooms)) || (sizeof($clipped_rooms)) || (sizeof($grouped_rooms))) &&
                    ((is_array($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["groups"])) || ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["assign_objects"]))) {
                    print("&nbsp;<input type=\"IMAGE\" name=\"save_state\" ".makeButton("speichern", "src")." border=\"0\">");
                    print("&nbsp;<input type=\"IMAGE\" name=\"suppose_decline_request\" ".makeButton("ablehnen", "src")." border=\"0\">");
                }

                // can we inc?
                if ($resources_data["requests_working_pos"] < sizeof($resources_data["requests_working_on"])-1) {
                    $i = 1;
                    if ($resources_data["skip_closed_requests"])
                        while ((!$resources_data["requests_open"][$resources_data["requests_working_on"][$resources_data["requests_working_pos"] + $i]["request_id"]]) && ($resources_data["requests_working_pos"] + $i < sizeof($resources_data["requests_working_on"])-1))
                            $i++;
                    if ((sizeof($resources_data["requests_open"]) > 1) && (($resources_data["requests_open"][$resources_data["requests_working_on"][$resources_data["requests_working_pos"] + $i]["request_id"]]) || (!$resources_data["skip_closed_requests"])))
                        $dec_possible = TRUE;
                }

                if ($dec_possible) {
                    print("&nbsp;<input type=\"IMAGE\" name=\"inc_request\" ".makeButton("weiter", "src")." border=\"0\">");
                }
                if (sizeof($resources_data["requests_open"]) > 1)
                    printf ("<br><font size=\"-1\">" . _("<b>%s</b> von <b>%s</b> Anfragen in der Bearbeitung wurden noch nicht aufgel&ouml;st.") . "</font>", sizeof($resources_data["requests_open"]), sizeof($resources_data["requests_working_on"]));
                    printf ("<br><font size=\"-1\">" . _("Aktueller Request: ")."<b>%s</b></font>", $resources_data["requests_working_pos"]+1);
                ?>
                </td>
            </tr>
        </table>
        </form>
        <br><br>
        <?
    }

    /**
     *
     * @param $overlaps
     * @param $events_count
     * @param $overlap_events_count
     * @param $group_dates
     */
    function showGroupOverlapStatus($overlaps, $events_count, $overlap_events_count, $group_dates)
    {
        if ($overlap_events_count) {
            foreach ($overlaps as $val) {
                if ($val["lock"])
                    $lock_desc.=sprintf(_("%s, %s Uhr bis %s, %s Uhr")."\n", date("d.m.Y", $val["begin"]), date("H:i", $val["begin"]), date("d.m.Y", $val["end"]), date("H:i", $val["end"]));
            }
            if ($lock_desc)
                $lock_desc = _("Sperrzeit(en):\n").$lock_desc;

            if ($overlap_events_count >= round($events_count * ($GLOBALS['RESOURCES_ALLOW_SINGLE_ASSIGN_PERCENTAGE'] / 100))) {
                if ($overlap_events_count == 1)
                    if ($lock_desc)
                        $desc.=sprintf(_("Es besteht eine Belegungssperre zur gewünschten Belegungszeit.")."\n".$lock_desc);
                    else
                        $desc.=sprintf(_("Es existieren Überschneidungen zur gewünschten Belegungszeit.")."\n");
                else
                    $desc.=sprintf(_("Es existieren Überschneidungen oder Belegungssperren zu mehr als %s%% aller gewünschten Belegungszeiten.")."\n".$lock_desc, $GLOBALS['RESOURCES_ALLOW_SINGLE_ASSIGN_PERCENTAGE']);
                $html = "<img src=\"" . Assets::image_path('icons/16/red/decline.png') . "\" ".tooltip($desc, TRUE, TRUE).">";
                $status = 2;
            } else {
                $desc.=sprintf(_("Einige der gewünschten Belegungszeiten überschneiden sich mit eingetragenen Belegungen bzw. Sperrzeiten:\n"));
                foreach ($group_dates as $key=>$val) {
                    if ($overlaps[$key])
                        foreach ($overlaps[$key] as $key2=>$val2)
                            if ($val2["lock"])
                                $desc.=sprintf(_("%s, %s Uhr bis %s, %s Uhr (Sperrzeit)")."\n", date("d.m.Y", $val2["begin"]), date("H:i", $val2["begin"]), date("d.m.Y", $val2["end"]), date("H:i", $val2["end"]));
                            else
                                $desc.=sprintf(_("%s von %s bis %s Uhr")."\n", date("d.m.Y", $val2["begin"]), date("H:i", $val2["begin"]), date("H:i", $val2["end"]));
                }
                $html = "<img src=\"" . Assets::image_path('icons/16/grey/exclaim-circle.png') . "\" ".tooltip($desc, TRUE, TRUE).">";
                $status = 1;
            }
        } else {
            $html = "<img src=\"" . Assets::image_path('icons/16/green/accept.png') . "\" ".tooltip(_("Es existieren keine Überschneidungen"), TRUE, TRUE).">";
            $status = 0;
        }
        return array("html"=>$html, "status"=>$status);
    }


    function showOverlapStatus($overlaps, $events_count, $overlap_events_count) {
        if (is_array($overlaps)) {
            foreach ($overlaps as $val) {
                if ($val["lock"])
                    $lock_desc.=sprintf(_("%s, %s Uhr bis %s, %s Uhr")."\n", date("d.m.Y", $val["begin"]), date("H:i", $val["begin"]), date("d.m.Y", $val["end"]), date("H:i", $val["end"]));
            }
            if ($lock_desc)
                $lock_desc = _("Sperrzeit(en):\n").$lock_desc;

            if ($overlap_events_count >= round($events_count * ($GLOBALS['RESOURCES_ALLOW_SINGLE_ASSIGN_PERCENTAGE'] / 100))) {
                if ($overlap_events_count == 1)
                    if ($overlaps[0]["lock"])
                        $desc.=sprintf(_("Es besteht eine Belegungssperre zur gewünschten Belegungszeit.")."\n".$lock_desc);
                    else
                        $desc.=sprintf(_("Es existieren Überschneidungen zur gewünschten Belegungszeit.")."\n");
                else
                    $desc.=sprintf(_("Es existieren Überschneidungen oder Belegungssperren zu mehr als %s%% aller gewünschten Belegungszeiten.")."\n".$lock_desc, $GLOBALS['RESOURCES_ALLOW_SINGLE_ASSIGN_PERCENTAGE']);
                $html = "<img src=\"" . Assets::image_path('icons/16/red/decline.png') . "\" ".tooltip($desc, TRUE, TRUE).">";
                $status = 2;
            } else {
                $desc.=sprintf(_("Einige der gewünschten Belegungszeiten überschneiden sich mit eingetragenen Belegungen bzw. Sperrzeiten:\n"));
                foreach ($overlaps as $val) {
                    if ($val["lock"])
                        $desc.=sprintf(_("%s, %s Uhr bis %s, %s Uhr (Sperrzeit)")."\n", date("d.m.Y", $val["begin"]), date("H:i", $val["begin"]), date("d.m.Y", $val["end"]), date("H:i", $val["end"]));
                    else
                        $desc.=sprintf(_("%s von %s bis %s Uhr")."\n", date("d.m.Y", $val["begin"]), date("H:i", $val["begin"]), date("H:i", $val["end"]));
                }
                $html = "<img src=\"" . Assets::image_path('icons/16/grey/exclaim-circle.png') . "\" ".tooltip($desc, TRUE, TRUE).">";
                $status = 1;
            }
        } else {
            $html = "<img src=\"" . Assets::image_path('icons/16/green/accept.png') . "\" ".tooltip(_("Es existieren keine Überschneidungen"), TRUE, TRUE).">";
            $status = 0;
        }
        return array("html"=>$html, "status"=>$status);
    }
}
