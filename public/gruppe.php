<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
gruppe.php - Zuordnung der abonierten Seminare zu Gruppen
Copyright (C) 2000 Ralf Stockmann <rstockm@gwdg.de>, Stefan Suchi <suchi@gmx.de>

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


require '../lib/bootstrap.php';

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));

$perm->check('user');

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page
require_once 'lib/functions.php';
require_once('lib/visual.inc.php');
require_once ('lib/meine_seminare_func.inc.php');

PageLayout::setHelpKeyword("Basis.VeranstaltungenOrdnen");

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head

$db=new DB_Seminar;

if (isset($_REQUEST['open_my_sem'])) $_my_sem_open[$_REQUEST['open_my_sem']] = true;

if (isset($_REQUEST['close_my_sem'])) unset($_my_sem_open[$_REQUEST['close_my_sem']]);

$forced_grouping = get_config('MY_COURSES_FORCE_GROUPING');
$no_grouping_allowed = ($forced_grouping == 'not_grouped' || !in_array($forced_grouping, getValidGroupingFields()));

//es wird eine Tabelle aufgebaut, in der die Gruppenzugehoerigkeit festgelegt wird.

if ($auth->is_authenticated() && $user->id != "nobody" && !$perm->have_perm("admin")) {
?>
<table class="index_box">
    <tr>
        <td class="topic">
            <img src="<?= Assets::image_path('icons/16/blue/group.png')?>" <?= tooltip(_("Gruppe ändern")) ?>>
            <b><?=_("Gruppenzuordnung")?></b>
        </td>
    </tr>
    <tr>
        <td class="blank">
            <p style="margin:20px;">
            <?=_("Hier k&ouml;nnen Sie Ihre Veranstaltungen in Farbgruppen einordnen und eine Gliederung nach Kategorien festlegen. <br>Die Darstellung unter <b>meine Veranstaltungen</b> wird entsprechend den Gruppen sortiert bzw. entsprechend der gew&auml;hlten Kategorie gegliedert.")?>
            </p>
        </td>
    </tr>
    <tr>
        <td class="blank">
    <form method="post" action="meine_seminare.php">
    <?= CSRFProtection::tokenTag() ?>
    <table border="0" cellpadding="0" cellspacing="0" width="90%" align="center">
    <tr>
        <td class="blank" align="right">
        <?=_("Kategorie zur Gliederung:")?>
        <select name="select_group_field">
            <?php if ($no_grouping_allowed) { ?>
            <option value="not_grouped" <?=($_my_sem_group_field == 'not_grouped' ? 'selected' : '')?>><?=_("keine Gliederung")?></option>
            <?php } ?>
            <option value="sem_number" <?=($_my_sem_group_field == 'sem_number' ? 'selected' : '')?>><?=_("Semester")?></option>
            <option value="sem_tree_id" <?=($_my_sem_group_field == 'sem_tree_id' ? 'selected' : '')?>><?=_("Studienbereich")?></option>
            <option value="sem_status" <?=($_my_sem_group_field == 'sem_status' ? 'selected' : '')?>><?=_("Typ")?></option>
            <option value="gruppe" <?=($_my_sem_group_field == 'gruppe' ? 'selected' : '')?>><?=_("Farbgruppen")?></option>
            <option value="dozent_id" <?=($_my_sem_group_field == 'dozent_id' ? 'selected' : '')?>><?=_("Dozenten")?></option>
        </select>
        </td>
        <td class="blank" align="center" colspan="8">
        <input type="image" <?=makeButton("absenden", "src") ?> border="0" value="absenden">
        </td>
    </tr>
    <tr><td class="blank" align="right" colspan="9">
    &nbsp;
    </td></tr>
    <tr valign="top" align="center">
    <th width="90%"><?=_("Veranstaltung")?></th>

<?
FOR ($i=0; $i<9; $i++)
    ECHO "<th class=\"gruppe".$i."\" >&nbsp;<img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" height=\"15px\" width=\"20px\"></th>";
    ECHO "</tr>";
    $group_field = $_my_sem_group_field;
    $groups = array();
    $add_fields = '';
    $add_query = '';

    if($group_field == 'sem_tree_id'){
        $add_fields = ',sem_tree_id';
        $add_query = "LEFT JOIN seminar_sem_tree sst ON (sst.seminar_id=seminare.Seminar_id)";
    }

    if($group_field == 'dozent_id'){
        $add_fields = ', su1.user_id as dozent_id';
        $add_query = "LEFT JOIN seminar_user as su1 ON (su1.seminar_id=seminare.Seminar_id AND su1.status='dozent')";
    }

    $dbv = new DbView();

    $query = "SELECT seminare.VeranstaltungsNummer AS sem_nr, seminare.Name, seminare.Seminar_id, seminare.status as sem_status, seminar_user.gruppe, seminare.visible,
                {$dbv->sem_number_sql} as sem_number, {$dbv->sem_number_end_sql} as sem_number_end $add_fields
                FROM seminar_user LEFT JOIN seminare USING (Seminar_id)
                $add_query
                WHERE seminar_user.user_id = '$user->id'";
    if (get_config('DEPUTIES_ENABLE')) {
        $query .= " UNION ".getMyDeputySeminarsQuery('gruppe', $dbv->sem_number_sql, $dbv->sem_number_end_sql, $add_fields, $add_query);
    }
    $query .= " ORDER BY sem_nr ASC";
    $db->query($query);
    while ($db->next_record()){
        $my_sem[$db->f("Seminar_id")] = array("obj_type" => "sem", "name" => $db->f("Name"), "visible" => $db->f("visible"), "gruppe" => $db->f("gruppe"),
        "sem_status" => $db->f("sem_status"),"sem_number" => $db->f("sem_number"),"sem_number_end" => $db->f("sem_number_end") );
        if ($group_field){
            fill_groups($groups, $db->f($group_field), array('seminar_id' => $db->f('Seminar_id'), 'name' => $db->f("Name"), 'gruppe' => $db->f('gruppe')));
        }
    }

    if ($group_field == 'sem_number') {
        correct_group_sem_number($groups, $my_sem);
    } else {
        add_sem_name($my_sem);
    }


    $c=0;
    sort_groups($group_field, $groups);
    $group_names = get_group_names($group_field, $groups);

    foreach ($groups as $group_id => $group_members){
        if ($group_field != 'not_grouped'){
            echo '<tr><td class="blank" colspan="9"><img src="'.$GLOBALS['ASSETS_URL'].'images/blank.gif" width="1px" height="5px"></td></tr>';
            echo '<tr><td class="blue_gradient" valign="middle" height="20" colspan="9">';
            if (isset($_my_sem_open[$group_id])){
                echo '<a class="tree" style="font-weight:bold" name="' . $group_id . '" href="' . $PHP_SELF . '?close_my_sem=' . $group_id . '#' .$group_id . '" ' . tooltip(_("Gruppierung schließen"), true) . '>';
                echo '<img src="'.$GLOBALS['ASSETS_URL'].'images/icons/16/blue/arr_1down.png"   hspace="3" border="0">';
            } else {
                echo '<a class="tree"  name="' . $group_id . '" href="' . $PHP_SELF . '?open_my_sem=' . $group_id . '#' .$group_id . '" ' . tooltip(_("Gruppierung öffnen"), true) . '>';
                echo '<img src="'.$GLOBALS['ASSETS_URL'].'images/icons/16/blue/arr_1right.png"  hspace="3" border="0">';
            }
            if (is_array($group_names[$group_id])){
                $group_name = $group_names[$group_id][1] . " > " . $group_names[$group_id][0];
            } else {
                $group_name = $group_names[$group_id];
            }
            echo htmlReady(my_substr($group_name,0,70));

            echo '</a></td></tr>';
        }
        if (isset($_my_sem_open[$group_id])){

            foreach ($group_members as $member){
                $values = $my_sem[$member['seminar_id']];
                if ($c % 2)
                $class="steel1";
                else
                $class="steelgraulight";

                printf("<tr><td class=\"$class\"><font size=\"-1\">&nbsp;<a href=\"seminar_main.php?auswahl=%s\">%s</a>%s</font></td>",
                $member['seminar_id'] ,htmlReady(my_substr($values["name"],0,70)),
                (!$values["visible"] ? "&nbsp;" . _("(versteckt)")  : ""));
                FOR ($i=0; $i<9; $i++)
                {
                    ECHO "<td class=\"gruppe".$i."\"><input type=radio name=gruppe[".$member['seminar_id']."] value=".$i;
                    IF ($values["gruppe"]==$i) ECHO " checked";
                    ECHO "></td>";
                }
                ECHO "</tr>";
                $c++;
            }
        }
    }
    ECHO "<tr><td class=\"blank\">&nbsp; </td><td class=\"blank\" align=center colspan=8><br><input type=\"IMAGE\" " . makeButton("absenden", "src") . " border=0 value=absenden><input type=hidden name=gruppesent value=1><br>&nbsp; </td></tr>";
    echo "</table></form></td></tr></table>";
}

include 'lib/include/html_end.inc.php';
page_close();
