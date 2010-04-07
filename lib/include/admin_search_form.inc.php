<?
# Lifter001: TODO - in progress (session variables)
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/*
admin_search_form.inc.php - Suche fuer die Verwaltungsseiten von Stud.IP.
Copyright (C) 2001 Stefan Suchi <suchi@gmx.de>, Ralf Stockmann <rstockm@gwdg.de>, Cornelis Kater <ckater@gwdg.de

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

# necessary if you want to include admin_search_form.inc.php in function/method scope
global  $SEM_BEGINN_NEXT,
        $SEM_CLASS,
        $SEM_TYPE;

global  $auth, $perm, $user;

global  $_fullname_sql,
        $i_page,
        $links_admin_data,
    $list,
        $msg,
        $SessSemName,
        $view_mode;


if ($perm->have_perm("autor")) {    // Navigationsleiste ab status "Autor", autors also need a navigation for studygroups

    require_once 'config.inc.php';
    require_once 'lib/admin_semester.inc.php';
    require_once 'lib/dates.inc.php';
    require_once 'lib/msg.inc.php';
    require_once 'lib/visual.inc.php';
    require_once 'lib/functions.php';
    require_once 'lib/classes/SemesterData.class.php';
    require_once "lib/classes/LockRules.class.php";
    require_once "lib/classes/AuxLockRules.class.php";

    $db=new DB_Seminar;
    $db2=new DB_Seminar;
    $db4=new DB_Seminar;
    $cssSw=new cssClassSwitcher;
    $semester=new SemesterData;
    $lock_rules=new LockRules;
    $all_lock_rules=$lock_rules->getAllLockRules($perm->have_perm('root'));
    $aux_rules=new AuxLockRules();
    $all_aux_rules=$aux_rules->getAllLockRules();

    //Einheitliches Auswahlmenu fuer Einrichtungen
    if (((!$SessSemName[1]) || ($SessSemName["class"] == "sem")) && ($list) && ($view_mode == "inst")) {
        //Save data back to database and start a connection  - so we avoid some problems with large search results and data is writing back to db too late
        page_close();

        ?>
        <table width="100%" cellspacing=0 cellpadding=0 border=0>
        <?
        if ($msg) {
            echo "<tr> <td class=\"blank\" colspan=2><br>";
            parse_msg ($msg);
            echo "</td></tr>";
        }
        ?>
        <tr>
            <td class="blank" colspan=2>&nbsp;
                <form name="links_admin_search" action="<?=URLHelper::getLink()?>" method="POST">
                <table cellpadding="0" cellspacing="0" border="0" width="99%" align="center">
                    <tr>
                        <td class="steel1">
                            <font size=-1><br><b><?=_("Bitte wählen Sie die Einrichtung aus, die Sie bearbeiten wollen:")?></b><br>&nbsp; </font>
                        </td>
                    </tr>
                    <tr>
                        <td class="steel1">
                        <font size=-1><select name="admin_inst_id" size="1" style="vertical-align:middle">
                        <?
                        if ($auth->auth['perm'] == "root"){
                            $db->query("SELECT Institut_id, Name, 1 AS is_fak  FROM Institute WHERE Institut_id=fakultaets_id ORDER BY Name");
                        } elseif ($auth->auth['perm'] == "admin") {
                            $db->query("SELECT a.Institut_id,Name, IF(b.Institut_id=b.fakultaets_id,1,0) AS is_fak FROM user_inst a LEFT JOIN Institute b USING (Institut_id)
                                        WHERE a.user_id='$user->id' AND a.inst_perms='admin' ORDER BY is_fak,Name");
                        } else {
                            $db->query("SELECT a.Institut_id,Name FROM user_inst a LEFT JOIN Institute b USING (Institut_id) WHERE inst_perms IN('tutor','dozent') AND user_id='$user->id' ORDER BY Name");
                        }

                        printf ("<option value=\"NULL\">%s</option>\n", _("-- bitte Einrichtung auswählen --"));
                        while ($db->next_record()){
                            printf ("<option value=\"%s\" style=\"%s\">%s </option>\n", $db->f("Institut_id"),($db->f("is_fak") ? "font-weight:bold;" : ""), htmlReady(substr($db->f("Name"), 0, 70)));
                            if ($db->f("is_fak")){
                                $db2->query("SELECT Institut_id, Name FROM Institute WHERE fakultaets_id='" .$db->f("Institut_id") . "' AND institut_id!='" .$db->f("Institut_id") . "' ORDER BY Name");
                                while ($db2->next_record()){
                                    printf("<option value=\"%s\">&nbsp;&nbsp;&nbsp;&nbsp;%s </option>\n", $db2->f("Institut_id"), htmlReady(substr($db2->f("Name"), 0, 70)));
                                }
                            }
                        }
                        ?>
                    </select></font>&nbsp;
                    <input type="image" <?=makeButton("auswaehlen", "src")?> border=0 align="absmiddle" value="bearbeiten">
                    </td>
                </tr>
                <tr>
                    <td class="steel1">
                        &nbsp;
                    </td>
                </tr>
                <tr>
                    <td class="blank">
                        &nbsp;
                    </td>
                </tr>
            </table>
            </form>
        </tr>
        </td>
        </table>
        <?
        include ('lib/include/html_end.inc.php');
        page_close();
        die;
    }

    //Einheitliches Seminarauswahlmenu, wenn kein Seminar gewaehlt ist
    if (((!$SessSemName[1]) || ($SessSemName["class"] == "inst")) && ($list) && ($view_mode == "sem")) {
        //Save data back to database and start a connection  - so we avoid some problems with large search results and data is writing back to db too late
        page_close();

        ?>
        <table width="100%" cellspacing=0 cellpadding=0 border=0>
        <?
        if ($msg)
            parse_msg ($msg);
        ?>
        <tr>
            <td class="blank" colspan=2>&nbsp;
        <?
        //Umfangreiches Auswahlmenu nur ab Admin, alles darunter sollte eine uberschaubare Anzahl von Seminaren haben
        if ($perm->have_perm("admin")) {
        ?>
            <form name="links_admin_search" action="<?=URLHelper::getLink()?>" method="POST">
                <table cellpadding="0" cellspacing="0" border="0" width="99%" align="center">
                    <tr>
                        <td class="steel1" colspan=5>
                                <font size=-1><br><b><?=_("Sie können die Auswahl der Veranstaltungen eingrenzen:")?></b><br>&nbsp; </font>
                        </td>
                    </tr>
                    <tr>
                        <td class="steel1">
                            <font size=-1><?=_("Semester:")?></font><br>
                            <?=SemesterData::GetSemesterSelector(array('name'=>'srch_sem'), $links_admin_data["srch_sem"])?>
                        </td>

                        <td class="steel1">
                        <?
                        if ($perm->have_perm("root")) {
                            $db->query("SELECT Institut_id, Name FROM Institute WHERE Institut_id!=fakultaets_id ORDER BY Name");
                        } else {
                            $db->query("SELECT a.Institut_id,Name, IF(b.Institut_id=b.fakultaets_id,1,0) AS is_fak FROM user_inst a LEFT JOIN Institute b USING (Institut_id)
                                WHERE a.user_id='$user->id' AND a.inst_perms='admin' ORDER BY is_fak,Name");
                        }
                        ?>
                        <font size=-1><?=_("Einrichtung:")?></font><br>
                        <select name="srch_inst">
                            <option value=0><?=_("alle")?></option>
                            <?
                            while ($db->next_record()) {
                                $my_inst[]=$db->f("Institut_id");
                                if ($links_admin_data["srch_inst"] == $db->f("Institut_id"))
                                    echo"<option selected value=".$db->f("Institut_id").">".substr($db->f("Name"), 0, 30)."</option>";
                                else
                                    echo"<option value=".$db->f("Institut_id").">".substr($db->f("Name"), 0, 30)."</option>";
                                if ($db->f("is_fak")) {
                                    $db2->query("SELECT Institut_id, Name FROM Institute WHERE fakultaets_id='" .$db->f("Institut_id") . "' AND institut_id!='" .$db->f("Institut_id") . "'");
                                    while ($db2->next_record()) {
                                        if ($links_admin_data["srch_inst"] == $db2->f("Institut_id"))
                                            echo"<option selected value=".$db2->f("Institut_id").">&nbsp;&nbsp;&nbsp;".substr($db2->f("Name"), 0, 30)."</option>";
                                        else
                                            echo"<option value=".$db2->f("Institut_id").">&nbsp;&nbsp;&nbsp;".substr($db2->f("Name"), 0, 30)."</option>";
                                        $my_inst[]=$db2->f("Institut_id");
                                    }
                                }
                            }
                            ?>
                        </select>
                        </td>
                        <td class="steel1">
                        <?
                        if (($perm->have_perm("admin")) && (!$perm->have_perm("root"))) {
                            ?>
                            <font size=-1><?=_("DozentIn:")?></font><br>
                            <select name="srch_doz">
                            <option value=0><?=_("alle")?></option>
                            <?
                            if (is_array($my_inst)) {
                                $inst_id_query = "'";
                                $inst_id_query.= join ("', '",$my_inst);
                                $inst_id_query.= "'";

                                $query="SELECT auth_user_md5.user_id, " . $_fullname_sql['full_rev'] ." AS fullname, Institut_id FROM user_inst  LEFT JOIN auth_user_md5 USING(user_id) LEFT JOIN user_info USING(user_id) WHERE inst_perms='dozent' AND institut_id IN ($inst_id_query) GROUP BY auth_user_md5.user_id ORDER BY Nachname ";
                                $db->query($query);
                                if ($db->num_rows()) {
                                    while ($db->next_record()) {
                                        if ($links_admin_data["srch_doz"] == $db->f("user_id"))
                                            echo"<option selected value=".$db->f("user_id").">".htmlReady(my_substr($db->f("fullname"),0,35))."</option>";
                                        else
                                            echo"<option value=".$db->f("user_id").">".htmlReady(my_substr($db->f("fullname"),0,35))."</option>";
                                    }
                                }
                            }
                            ?>
                            </select>
                            <?
                        }

                        if ($perm->have_perm("root")) {
                            $db->query("SELECT Institut_id,Name FROM Institute WHERE Institut_id=fakultaets_id ORDER BY Name");
                            ?>
                            <font size=-1><?=_("Fakultät:")?></font><br>
                            <select name="srch_fak">
                                <option value=0><?=_("alle")?></option>
                                <?
                                while ($db->next_record()) {
                                    if ($links_admin_data["srch_fak"] == $db->f("Institut_id"))
                                        echo"<option selected value=".$db->f("Institut_id").">".substr($db->f("Name"), 0, 30)."</option>";
                                    else
                                        echo"<option value=".$db->f("Institut_id").">".substr($db->f("Name"), 0, 30)."</option>";
                                }
                                ?>
                            </select>
                            <?
                        }
                        ?>&nbsp;
                        </td>
                        <td class="steel1">
                            <font size=-1><?=_("freie Suche:")?></font><br><input type="TEXT" name="srch_exp" maxlength=255 size=20 value="<? echo $links_admin_data["srch_exp"] ?>" />
                            <input type="HIDDEN" name="srch_send" value="TRUE" />
                        </td>
                        <td class="steel1" valign="bottom" width="20%" nowrap="nowrap">
                            <?
                            echo makeButton('anzeigen', 'input', _("Anzeigen"), 'anzeigen');
                            if ($links_admin_data["srch_on"]){
                                echo '&nbsp;' . makeButton('zuruecksetzen','input', _("zurücksetzen"),'links_admin_reset_search');
                            }
                            ?>
                            <input type="HIDDEN" name="view" value="<? echo $links_admin_data["view"]?>" />
                        </td>
                    </tr>
                <tr>
                    <td class="steel1" colspan="5">
                        <br>&nbsp;<font size=-1>
                            <label>
                                <input type="checkbox" name="show_rooms_check" value="on" <?  if ($_REQUEST['show_rooms_check'] == 'on') { echo 'checked="checked"'; } ?> >&nbsp; <?=_("Raumdaten einblenden")?>
                            </label>
                        </font><br>
                    </td>
                </tr>
                    <?
                    //more Options for archiving
                    if ($i_page == "archiv_assi.php") {
                        ?>
                        <tr>
                            <td class="steel1" colspan=6>
                                <br>&nbsp;<font size=-1><input type="CHECKBOX" name="select_old" <? if ($links_admin_data["select_old"]) echo ' checked' ?> />&nbsp;<?=_("keine zukünftigen Veranstaltungen anzeigen - Beginn des (letzten) Veranstaltungssemesters ist verstrichen")?> </font><br>
                                <!-- &nbsp;<font size=-1><input type="CHECKBOX" name="select_inactive" <? if ($links_admin_data["select_inactive"]) echo ' checked' ?> />&nbsp;<?=_("nur inaktive Veranstaltungen auswählen (letzte Aktion vor mehr als sechs Monaten)")?> </font> -->
                            </td>
                        </tr>
                        <?
                    } else {
                        ?>
                        <input type="HIDDEN" name="select_old" value="<? if ($links_admin_data["select_old"]) echo "TRUE" ?> " />
                        <input type="HIDDEN" name="select_inactive" value="<? if ($links_admin_data["select_inactive"]) echo "TRUE" ?>" />
                        <?
                    }
                    ?>
                    <tr>
                        <td class="steel1" colspan=5>
                            &nbsp;
                        </td>
                    </tr>
                    <tr>
                        <td class="blank" colspan=5>
                            &nbsp;
                        </td>
                    </tr>
                    <? if (! empty($message)) : ?>
                    <tr>
                        <td class="blank" colspan=5>
                            <? parse_msg($message); ?>
                        </td>
                    </tr>
                    <? endif; ?>
                </table>
            </form>
            <?
        }

    // display Seminar-List
    if ($links_admin_data["srch_on"] || $auth->auth["perm"] =="tutor" || $auth->auth["perm"] == "dozent") {

        // Creation of Seminar-Query
        if ($links_admin_data["srch_on"]) {
            $query="SELECT DISTINCT seminare.*, Institute.Name AS Institut,
                    sd1.name AS startsem,IF(duration_time=-1, '"._("unbegrenzt")."', sd2.name) AS endsem
                    FROM seminar_user LEFT JOIN seminare USING (seminar_id)
                    LEFT JOIN Institute USING (institut_id)
                    LEFT JOIN auth_user_md5 ON (seminar_user.user_id = auth_user_md5.user_id)
                    LEFT JOIN semester_data sd1 ON ( start_time BETWEEN sd1.beginn AND sd1.ende)
                    LEFT JOIN semester_data sd2 ON ((start_time + duration_time) BETWEEN sd2.beginn AND sd2.ende)
                    WHERE seminar_user.status = 'dozent' ";
            $conditions=0;

            if ($links_admin_data["srch_sem"]) {
                $one_semester = $semester->getSemesterData($links_admin_data["srch_sem"]);
                $query.="AND seminare.start_time <=".$one_semester["beginn"]." AND (".$one_semester["beginn"]." <= (seminare.start_time + seminare.duration_time) OR seminare.duration_time = -1) ";
                $conditions++;
            }

            if (is_array($my_inst) && $auth->auth["perm"] != "root") {
                $query.="AND Institute.Institut_id IN ('".join("','",$my_inst)."') ";
            }

            if ($links_admin_data["srch_inst"]) {
                $query.="AND Institute.Institut_id ='".$links_admin_data["srch_inst"]."' ";
            }


            if ($links_admin_data["srch_fak"]) {
                $query.="AND fakultaets_id ='".$links_admin_data["srch_fak"]."' ";
            }


            if ($links_admin_data["srch_doz"]) {
                $query.="AND seminar_user.user_id ='".$links_admin_data["srch_doz"]."' ";
            }

            if ($links_admin_data["srch_exp"]) {
                $query.="AND (seminare.Name LIKE '%".$links_admin_data["srch_exp"]."%' OR seminare.VeranstaltungsNummer LIKE '%".$links_admin_data["srch_exp"]."%' OR seminare.Untertitel LIKE '%".$links_admin_data["srch_exp"]."%' OR seminare.Beschreibung LIKE '%".$links_admin_data["srch_exp"]."%' OR auth_user_md5.Nachname LIKE '%".$links_admin_data["srch_exp"]."%') ";
                $conditions++;
            }

            //Extension to the query, if we want to show lectures which are archiveable
            if (($i_page== "archiv_assi.php") && ($links_admin_data["select_old"]) && ($SEM_BEGINN_NEXT)) {
                $query.="AND ((seminare.start_time + seminare.duration_time < ".$SEM_BEGINN_NEXT.") AND seminare.duration_time != '-1') ";
                $conditions++;
            }

            // tutors and dozents only have a plain list
            } elseif (($auth->auth["perm"] =="tutor") || ($auth->auth["perm"] == "dozent")) {
                    $query="SELECT  seminare.*, Institute.Name AS Institut ,
                            sd1.name AS startsem,IF(duration_time=-1, '"._("unbegrenzt")."', sd2.name) AS endsem
                        FROM seminar_user LEFT JOIN seminare USING (Seminar_id)
                        LEFT JOIN Institute USING (institut_id)
                        LEFT JOIN semester_data sd1 ON ( start_time BETWEEN sd1.beginn AND sd1.ende)
                        LEFT JOIN semester_data sd2 ON ((start_time + duration_time) BETWEEN sd2.beginn AND sd2.ende)
                        WHERE seminar_user.status IN ('dozent'"
                        .(($i_page != 'archiv_assi.php' && $i_page != 'admin_visibility.php') ? ",'tutor'" : "")
                        . ") AND seminar_user.user_id='$user->id' ";

            // should never be reached
            } else {
                $query = FALSE;
            }

            $query.=" ORDER BY  ".$links_admin_data["sortby"];
            if ($links_admin_data["sortby"] == 'start_time') $query .= ' DESC';
            $db->query($query);

        ?>
        <form name="links_admin_action" action="<?=URLHelper::getLink()?>" method="POST">
        <table border=0  cellspacing=0 cellpadding=2 align=center width="99%">
        <?
        $show_rooms_check_url= ($_REQUEST['show_rooms_check']=="on") ? "&show_rooms_check=on" : null;
        // only show table header in case of hits
        if ($db->num_rows()) {
            ?>
            <tr height=28>
                <td width="%10" class="steel" valign=bottom>
                    <img src="<?= $GLOBALS['ASSETS_URL'] ?>images/blank.gif" width=1 height=20>
                    &nbsp;<a href="<?=URLHelper::getLink('?adminarea_sortby=start_time'. $show_rooms_check_url)?>"><b><?=_("Semester")?></b></a>
                </td>
                <td width="5%" class="steel" valign=bottom>
                    <img src="<?= $GLOBALS['ASSETS_URL'] ?>images/blank.gif" width=1 height=20>
                    &nbsp; <a href="<?=URLHelper::getLink('?adminarea_sortby=VeranstaltungsNummer'. $show_rooms_check_url)?>"><b><?=_("Nr.")?></b></a>
                </td>
                <td width="45%" class="steel" valign=bottom>
                    <img src="<?= $GLOBALS['ASSETS_URL'] ?>images/blank.gif" width=1 height=20>
                    &nbsp; <a href="<?=URLHelper::getLink('?adminarea_sortby=Name'. $show_rooms_check_url)?>"><b><?=_("Name")?></b></a>
                </td>
                <? if ($show_rooms_check_url) : ?>
                <td width="25%" class="steel" valign=bottom>
                    <img src="<?=$GLOBALS['ASSETS_URL']?>images/blank.gif" width=1 height=20>
                    <b><?=_("Raum")?></b></a>
                </td>
                <? endif; ?>
                <td width="15%" align="center" class="steel" valign=bottom>
                    <b><?=_("DozentIn")?></b>
                </td>
                <td width="25%"align="center" class="steel" valign=bottom>
                    <a href="<?=URLHelper::getLink('?adminarea_sortby=status'. $show_rooms_check_url)?>"><b><?=_("Status")?></b></a>
                </td>
                <td width="10%" align="center" class="steel" valign=bottom>
                    <b><?
                        if ($i_page=="archiv_assi.php") {
                            echo _("Archivieren");
                        } elseif ($i_page=="admin_visibility.php") {
                            echo _("Sichtbarkeit");
                        } elseif ($i_page=="admin_lock.php") {
                        echo _("Sperrebene");
                        } else {
                            echo _("Aktion");
                        }
                    ?></b>
                </td>
            </tr>
            <?
            //more Options for archiving
            if ($i_page == "archiv_assi.php") {
                ?>
                <tr class="steel2">
                    <td colspan="3">
                        <font size="-1"><?=_("Alle ausgewählten Veranstaltungen")?></font> <input type="image" <?=makeButton("archivieren", "src")?> border="0"><br>
                        <font size="-1" color="red"><?=_("Achtung: Das Archivieren ist ein Schritt, der <b>nicht</b> rückgängig gemacht werden kann!")?></font>
                    </td>
                    <td colspan="<?=(Request::get('show_rooms_check')=='on')?'4':'3'; ?>" align="right">
                    <?
                        printf("<font size=-1><a href=\"%s\">%s</a></font>", URLHelper::getLink('?select_all=TRUE&list=TRUE&show_rooms_check='.Request::get('show_rooms_check')), makeButton("alleauswaehlen"));
                        printf(" <a href=\"%s\">%s</a></font>", URLHelper::getLink('?select_none=TRUE&list=TRUE&show_rooms_check='.Request::get('show_rooms_check')), makeButton("keineauswaehlen"));
                    ?>
                    </td>
                </tr>
                <?
            }
            //more Options for visibility changing
            if ($i_page == "admin_visibility.php") {
                ?>
                <tr class="steel2">
                    <td colspan="3">
                        <?= _("Änderungen") ?> <?= makeButton('speichern', 'input') ?>
                    </td>
                    <td colspan="<?=(Request::get('show_rooms_check')=='on')?'4':'3'; ?>" align="right">
                    <input type="hidden" name="change_visible" value="1">
                    <?
                        printf("<a href=\"%s\">%s</a></font>", URLHelper::getLink('?select_all=TRUE&list=TRUE'), makeButton("alleauswaehlen"));
                        printf(" <a href=\"%s\">%s</a></font>", URLHelper::getLink('?select_none=TRUE&list=TRUE'), makeButton("keineauswaehlen"));
                    ?>
                    </td>
                </tr>
                <?
            }
        //more Options for lock changing
        if ($i_page == "admin_lock.php") {
            ?>
            <tr class="steel2">
                <td colspan="3">
                    <?= _("Änderungen") ?> <?= makeButton('speichern', 'input') ?>
                </td>
                <td colspan="4" align="right">
                <?
                    printf("<select name=\"lock_all\" size=1>");
                    printf("<option value='-1'>"._("Bitte wählen")."</option>");
                    printf("<option value='none' %s>--"._("keine Sperrebene")."--</option>", $lock_all == 'none' ? 'selected=selected' : '' );
                    for ($i=0;$i<count($all_lock_rules);$i++) {
                        printf("<option value=\"".$all_lock_rules[$i]["lock_id"]."\" ");
                        if (isset($lock_all) && $lock_all==$all_lock_rules[$i]["lock_id"]) {
                            printf(" selected=selected ");
                        }
                        printf(">".htmlReady($all_lock_rules[$i]["name"])."</option>");
                    }
                    // ab hier die verschiedenen Sperrlevel für alle Veranstaltungen
                    echo '</select> ';
                    echo _("als Vorauswahl");
                    echo ' '.makeButton('zuweisen', 'input', false, 'general_lock');
                ?>&nbsp;
                </td>
            </tr>
            <?
        }

        //more Options for aux data
            if ($i_page == "admin_aux.php") {
                ?>
                <tr class="steel2">
                    <td colspan="3" nowrap>
                        <?=_("Änderungen")?> <?= makeButton('speichern', 'input') ?>
                    </td>
                    <td colspan="4" align="right">
                    <?
                        echo '<select name="aux_all" size="1">';
                        echo '<option value="-1">'. _("Bitte auswählen"). '</option>';
                        echo '<option value="null" ' . ($aux_all == 'null' ? 'selected=selected' : '') . '>-- '. _("keine Zusatzangaben") .' --</option>';
                        foreach ((array)$all_aux_rules as $lock_id => $data) {
                            echo '<option value="'.$lock_id.'"';
                            if (isset($aux_all) && $aux_all==$lock_id) {
                                echo ' selected=selected ';
                            }
                            echo '>'.htmlReady($data['name']).'</option>';
                        }
                        // ab hier die verschiedenen Zusatzangaben für alle Veranstaltungen
                        echo '</select> ';
                        echo _("als Vorauswahl");
                        echo ' '.makeButton('zuweisen', 'input', false, 'aux_rule');
                    ?>&nbsp;
                    </td>
                </tr>
                <?
            }


        }

        while ($db->next_record()) {
            $seminar_id = $db->f("Seminar_id");
            $sem=new SemesterData;
            $studygroup_mode = $GLOBALS['SEM_CLASS'][$GLOBALS['SEM_TYPE'][$db->f('status')]["class"]]["studygroup_mode"];

            if (!$semdata=$sem->getSemesterData($links_admin_data['srch_sem'])) {
                $semdata = $sem->getSemesterDataByDate($db->f('start_time'));
            }

            // if "show room-data" is enabled
            if (!$show_rooms_check_url) {
                $_room = "&nbsp;";
            } else {
                $_room = getRoomOverviewUnsteady($seminar_id,$semdata["semester_id"],TRUE);
                if (!$_room) {
                    $semdata = $sem->getSemesterDataByDate($db->f('start_time'));
                    $_room = getRoomOverviewUnsteady($seminar_id, $semdata['semester_id'], TRUE);
                }
                $_room = $_room ? $_room : "nicht angegeben";
            }
            $user_id = $auth->auth["uid"];

            $cssSw->switchClass();
            echo "<tr>";
            echo "<td align=\"center\" class=\"".$cssSw->getClass()."\"><font size=\"-1\">".htmlReady($db->f('startsem'));
            if ($db->f('startsem') != $db->f('endsem')) echo '<br>( - '.htmlReady($db->f('endsem')).')';
            echo "</font></td>";
            echo "<td class=\"".$cssSw->getClass()."\"><font size=\"-1\">".htmlReady($db->f("VeranstaltungsNummer"))."</font></td>";
            echo "<td class=\"".$cssSw->getClass()."\"><font size=\"-1\">".htmlReady(substr($db->f("Name"),0,100));
            if (strlen ($db->f("Name")) > 100)
                echo "(...)";
            if ($db->f("visible")==0) {
                echo "&nbsp;". _("(versteckt)");
            }

            echo "</font></td>";

            // if "show room-data" is enabled, show cell
            if ($show_rooms_check_url) {
                echo "<td valign=\"top\" class=\"".$cssSw->getClass()."\"><font size=-1>".$_room."</font></td>";
            }

            echo "<td align=\"center\" class=\"".$cssSw->getClass()."\"><font size=-1>";
            $db4->query("SELECT ". $_fullname_sql['full'] ." AS fullname, username, position FROM seminar_user
                LEFT JOIN auth_user_md5 USING (user_id)
                LEFT JOIN user_info USING (user_id)
                WHERE Seminar_id = '$seminar_id' and status = 'dozent' AND seminar_user.user_id <> MD5('studygroup_dozent') ORDER BY position ");
            $k=0;
            if (!$db4->num_rows())
                echo "&nbsp; ";
            while ($db4->next_record()) {
                if ($db4->f('username')) {
                    if ($k) echo ', ';
                    echo "<a href=\"".UrlHelper::GetLink("about.php?username=".$db4->f("username"))."\">".htmlReady($db4->f("fullname"))."</a>";
                    $k++;
                }
            }
            echo "</font></td>";
            echo "<td class=\"".$cssSw->getClass()."\" align=\"center\"><font size=-1>".$SEM_TYPE[$db->f("status")]["name"]."<br>" . _("Kategorie:") . " <b>".$SEM_CLASS[$SEM_TYPE[$db->f("status")]["class"]]["name"]."</b><font></td>";
            echo "<td class=\"".$cssSw->getClass()."\" nowrap align=\"center\">";

            if ($studygroup_mode) {
                if($i_page != "admin_visibility.php") {
                    echo _("Studiengruppe") . sprintf("<br><a href=\"%s\">%s</a></font>", URLHelper::getLink('dispatch.php/course/studygroup/edit/' . $seminar_id . "?cid=" . $seminar_id), makeButton("bearbeiten"));
                }
                else echo '<input type="CHECKBOX" DISABLED/>';
            } else {
                //Kommandos fuer die jeweilgen Seiten
                switch ($i_page) {
                    case "adminarea_start.php":
                        printf("<font size=-1>" . _("Veranstaltung") . "<br><a href=\"%s\">%s</a></font>", URLHelper::getLink('?select_sem_id=' . $seminar_id), makeButton("auswaehlen"));
                        break;
                    case "themen.php":
                        printf("<font size=-1>" . _("Ablaufplan") . "<br><a href=\"%s\">%s</a></font>", URLHelper::getLink('?seminar_id=' . $seminar_id), makeButton("bearbeiten"));
                        break;
                    case "raumzeit.php":
                        printf("<font size=-1>" . _("Zeiten / Räume") . "<br><a href=\"%s\">%s</a></font>", URLHelper::getLink('?seminar_id=' . $seminar_id), makeButton("bearbeiten"));
                        break;
                    case "admin_admission.php":
                        printf("<font size=-1>" . _("Zugangsberechtigungen") . "<br><a href=\"%s\">%s</a></font>", URLHelper::getLink('?seminar_id=' . $seminar_id), makeButton("bearbeiten"));
                        break;
                    case "admin_room_requests.php":
                        printf("<font size=-1>" . _("Raumwünsche") . "<br><a href=\"%s\">%s</a></font>", URLHelper::getLink('?seminar_id=' . $seminar_id), makeButton("bearbeiten"));
                        break;
                    case "admin_lit_list.php":
                        printf("<font size=-1>" . _("Literatur") . "<br><a href=\"%s\">%s</a></font>", URLHelper::getLink('?_range_id=' . $seminar_id), makeButton("bearbeiten"));
                        break;
                    case "admin_statusgruppe.php":
                        printf("<font size=-1>" . _("Funktionen / Gruppen") . "<br><a href=\"%s\">%s</a></font>", URLHelper::getLink('?ebene=sem&range_id=' . $seminar_id), makeButton("bearbeiten"));
                        break;
                    case "admin_roles.php":
                        printf("<font size=-1>" . _("Funktionen / Gruppen") . "<br><a href=\"%s\">%s</a></font>", URLHelper::getLink('?ebene=sem&range_id=' . $seminar_id), makeButton("bearbeiten"));
                        break;
                    case "admin_seminare1.php":
                        printf("<font size=-1>" . _("Veranstaltung") . "<br><a href=\"%s\">%s</a></font>", URLHelper::getLink('?s_command=edit&s_id=' . $seminar_id), makeButton("bearbeiten"));
                        break;
                    case "admin_modules.php":
                        printf("<font size=-1>" . _("Module") . "<br><a href=\"%s\">%s</a></font>", URLHelper::getLink('?range_id=' . $seminar_id), makeButton("bearbeiten"));
                        break;
                    case "admin_news.php":
                        printf("<font size=-1>" . _("News") . "<br><a href=\"%s\">%s</a></font>", URLHelper::getLink('?range_id=' . $seminar_id), makeButton("bearbeiten"));
                        break;
                    case "copy_assi.php":
                        printf("<font size=-1>" . _("Veranstaltung") . "<br><a href=\"%s\">%s</a></font>", URLHelper::getLink('admin_seminare_assi.php?cmd=do_copy&start_level=TRUE&class=1&cp_id=' . $seminar_id), makeButton("kopieren"));
                        break;
                    case "admin_lock.php":
                        $lock_rules = new LockRules();
                        $rule = $lock_rules->getSemLockRule($seminar_id);
                        if(!$perm->have_perm('root') && ($rule['permission'] == 'admin' || $rule['permission'] == 'root')){
                            echo '<div style="margin-bottom:3px;font-weight:bold;text-align:left">'._("zugewiesen") . ': ' . htmlReady($rule['name']).'</div>';
                        } else {
                            ?>
                            <input type="hidden" name="make_lock" value=1>
                            <select name=lock_sem[<? echo $seminar_id ?>]>
                            <option value="none">-- <?= _("keine Sperrebene") ?> --</option>
                            <?
                                for ($i=0;$i<count($all_lock_rules);$i++) {
                                    echo "<option value=".$all_lock_rules[$i]["lock_id"]."";
                                    if (isset($lock_all) && $lock_all==$all_lock_rules[$i]["lock_id"]) {
                                        echo " selected ";
                                    } elseif (!isset($lock_all) && ($all_lock_rules[$i]["lock_id"]==$rule["lock_id"])) {
                                        echo " selected ";
                                    }
                                    echo ">".htmlReady($all_lock_rules[$i]["name"])."</option>";
                                }
                            ?>
                            </select>

                        <?
                        }
                    break;
                    case "admin_aux.php":
                        $db5 = new Db_Seminar;
                        $db5->query("SELECT aux_lock_rule from seminare WHERE Seminar_id='$seminar_id'");
                        $db5->next_record();
                        if ($perm->have_perm("dozent")) {
                            ?>
                            <input type="hidden" name="make_aux" value="1">
                            <select name=aux_sem[<? echo $seminar_id ?>]>
                            <option value="null">-- <?=_("keine Zusatzangaben")?> --</option>
                            <?
                                foreach ((array)$all_aux_rules as $lock_id => $data) {
                                    echo '<option value="'.$lock_id.'"';
                                    if (isset($aux_all) && $aux_all==$lock_id) {
                                        echo ' selected ';
                                    } elseif (!isset($aux_all) && ($lock_id == $db5->f("aux_lock_rule"))) {
                                        echo ' selected ';
                                    }
                                    echo '>'.htmlReady($data['name']).'</option>';
                                }
                            ?>
                            </select>
                        <?
                        }
                    break;

                    case "admin_visibility.php":
                        if ($perm->have_perm("admin") || (get_config('ALLOW_DOZENT_VISIBILITY') && $perm->have_perm('dozent'))) {
                            if(!LockRules::check($seminar_id, 'seminar_visibility')){
                                ?>
                                <input type="HIDDEN" name="all_sem[]" value="<? echo $seminar_id ?>" />
                                <input type="CHECKBOX" name="visibility_sem[<? echo $seminar_id ?>]" <? if (!$_REQUEST['select_none'] && ($_REQUEST['select_all'] || $db->f("visible"))) echo ' checked'; ?> />
                                <?
                            } else {
                                echo $db->f('visible') ? _("sichtbar") : _("versteckt");
                            }
                        }
                        break;
                    case "archiv_assi.php":
                        if ($perm->have_perm("admin") || (get_config('ALLOW_DOZENT_ARCHIV') && $perm->have_perm('dozent'))) {
                            if(!LockRules::check($seminar_id, 'seminar_visibility')){
                                ?>
                                <input type="HIDDEN" name="archiv_sem[]" value="_id_<? echo $seminar_id ?>" />
                                <input type="CHECKBOX" name="archiv_sem[]" <? if ($_REQUEST['select_all']) echo ' checked'; ?> />
                                <?
                            } else {
                                echo "&nbsp;";
                            }
                        }
                        break;
                    case "dispatch.php":
                        if ($this instanceof Course_StudyAreasController){
                            printf(_("Studienbereiche") . '<br><a href="%s">%s</a>',
                                $this->url_for('course/study_areas/show/' . $seminar_id),
                                makeButton("bearbeiten"));
                        }
                        break;
                }
            }
            echo "</tr>";
        }
        ?>
        </table>
        </form>
        <?
        //Traurige Meldung wenn nichts gefunden wurde oder sonst irgendwie nichts da ist
        if ($query && !$db->num_rows()) {
            if ($conditions) {
                echo MessageBox::info(_("Leider wurden keine Veranstaltungen entsprechend Ihren Suchkriterien gefunden!"));
            } else {
                echo MessageBox::info(_("Leider wurden keine Veranstaltungen gefunden!"));
            }
        }
    }
    ?>
    <br>
    </td>
    </tr>
    </table>
    <?
        include ('lib/include/html_end.inc.php');
        page_close();
        die;
    }
}

if ($SessSemName["class"] == "sem" && $SessSemName[1] && !$perm->have_studip_perm('tutor', $SessSemName[1])){
    parse_window('error§' . _("Sie haben keine ausreichende Zugriffsberechtigung!"), '§', _("Zugriff verweigert"));
    include ('lib/include/html_end.inc.php');
    page_close();
    die();
}
