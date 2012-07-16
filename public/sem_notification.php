<?php
# Lifter002: TODO
# Lifter003: TEST
# Lifter005: TODO
# Lifter007: TODO
# Lifter010: TODO
/**
* sem_notification.php
*
*
*
*
* @author       Peter Thienel <thienel@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup  studip
* @module       studip
* @package  studip_core
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// sem_notification.php
//
// Copyright (C) 2005 Peter Thienel <thienel@data-quest.de>,
// data-quest Suchi & Berg GmbH <info@data-quest.de>
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
$auth->login_if($auth->auth["uid"] == "nobody");

require_once 'lib/functions.php';
require_once('lib/visual.inc.php');
require_once('lib/classes/cssClassSwitcher.inc.php');
require_once('lib/meine_seminare_func.inc.php');
require_once('lib/classes/ModulesNotification.class.php');
require_once('lib/msg.inc.php');

if (!get_config('MAIL_NOTIFICATION_ENABLE')) {
    $message = _("Die Benachrichtigungsfunktion wurde in den Systemeinstellungen nicht freigeschaltet.");
    throw new Exception($message);
}

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

PageLayout::setHelpKeyword("Basis.MyStudIPBenachrichtigung");
PageLayout::setTitle(_("Benachrichtigung über neue Inhalte anpassen"));
Navigation::activateItem('/links/settings/notification');
PageLayout::setTabNavigation('/links/settings');

SkipLinks::addIndex(PageLayout::getTitle(), 'main_content', 100);

include('lib/include/html_head.inc.php'); // Output of html head
include('lib/include/header.php');   // Output of Stud.IP head

function print_module_icons ($m_enabled)
{
    foreach ($m_enabled as $m_name => $m_data) {
        switch ($m_name) {
            case 'news' :
                $m_icon = Assets::image_path('icons/16/white/news.png');
                break;
            case 'forum' :
                $m_icon = Assets::image_path('icons/16/white/forum.png');
                break;
            case 'documents' :
                $m_icon = Assets::image_path('icons/16/white/files.png');
                break;
            case 'schedule' :
                $m_icon = Assets::image_path('icons/16/white/schedule.png');
                break;
            case 'literature' :
                $m_icon = Assets::image_path('icons/16/white/literature.png');
                break;
            case 'elearning_interface' :
                $m_icon = Assets::image_path('icons/16/white/learnmodule.png');
                break;
            case 'wiki' :
                $m_icon = Assets::image_path('icons/16/white/wiki.png');
                break;
            case 'scm' :
                $m_icon = Assets::image_path('icons/16/white/infopage.png');
                break;
            case 'votes' :
                $m_icon = Assets::image_path('icons/16/white/vote.png');
                break;
            case 'basic_data' :
                $m_icon = Assets::image_path('icons/16/white/seminar.png');
                break;
            case 'participants' :
                $m_icon = Assets::image_path('icons/16/white/persons.png');
                break;
            default :
                break;
        }
        echo "<th>" . Assets::img($m_icon, array('class' => 'middle', 'title' => $m_data['name'])) . "</th>";
    }
}


if (isset($_REQUEST['open_my_sem']))
    $_my_sem_open[$_REQUEST['open_my_sem']] = true;
if (isset($_REQUEST['close_my_sem']))
    unset($_my_sem_open[$_REQUEST['close_my_sem']]);

if ($auth->is_authenticated() && $user->id != "nobody" && !$perm->have_perm("admin")) {
    if (isset($_my_sem_group_field)) {
        $group_field = $_my_sem_group_field;
    } else {
        $group_field = 'not_grouped';
    }

    if($group_field == 'sem_tree_id'){
        $add_fields = ',sem_tree_id';
        $add_query = "LEFT JOIN seminar_sem_tree sst ON (sst.seminar_id=seminare.Seminar_id)";
    }

    if($group_field == 'dozent_id'){
        $add_fields = ', su1.user_id as dozent_id';
        $add_query = "LEFT JOIN seminar_user as su1 ON (su1.seminar_id=seminare.Seminar_id AND su1.status='dozent')";
    }

    $dbv = new DbView();

    $query = "SELECT seminare.VeranstaltungsNummer AS sem_nr, seminare.Name, seminare.Seminar_id,
                     seminare.status AS sem_status, seminar_user.gruppe, seminare.visible,
                     {$dbv->sem_number_sql} AS sem_number, {$dbv->sem_number_end_sql} AS sem_number_end
                     {$add_fields}
              FROM seminar_user
              LEFT JOIN seminare  USING (Seminar_id)
              {$add_query}
              WHERE seminar_user.user_id = ?";
    if (get_config('DEPUTIES_ENABLE')) {
        $query .= " UNION ".getMyDeputySeminarsQuery('notification', $dbv->sem_number_sql, $dbv->sem_number_end_sql, $add_fields, $add_query);
    }
    $query .= " ORDER BY sem_nr ASC";
    
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user->id));
    $seminars = $statement->fetchAll(PDO::FETCH_ASSOC);

    if (!count($seminars)) {
        echo "<table class=\"blank\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" id=\"main_content\">\n";
        echo "<tr><td class=\"blank\">&nbsp;</td></tr>";
        parse_msg("info§" . sprintf(_("Sie haben zur Zeit keine Veranstaltungen abonniert, an denen Sie teilnehmen k&ouml;nnen. Bitte nutzen Sie %s<b>Veranstaltung suchen / hinzuf&uuml;gen</b>%s um neue Veranstaltungen aufzunehmen."), "<a href=\"sem_portal.php\">", "</a>"),
                '§', 'blank', 0);
        echo "</table>";
    }

    $modules = new ModulesNotification();
    // Update der Benachrichtigungsfunktion
    if ($_REQUEST['cmd'] == 'set_sem_notification') {
        if (is_array($_REQUEST['m_checked'])) {
            $modules->setModuleNotification($_REQUEST['m_checked'], 'sem');

            echo '<table class="default"><tr><td class="blank">';
            echo MessageBox::success(_('Die Einstellungen wurden gespeichert.'));
            echo '</td></tr></table>';
        }
    }
    $enabled_modules = $modules->getGlobalEnabledNotificationModules('sem');
    $css = new cssClassSwitcher();
    $css->enableHover();
    echo $css->GetHoverJSFunction();
    echo '<table class="blank" cellspacing="0" cellpadding="2" border="0" width="100%">';
    echo "<tr><td class=\"blank\" width=\"100%\">\n";
    echo "\n<table width=\"75%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" align=\"center\" id=\"main_content\">\n";
    echo "<form method=\"post\" action=\"".URLHelper::getLink()."\">\n";
    echo CSRFProtection::tokenTag();
    echo '<tr><td class="blank" colspan="' . (sizeof($enabled_modules) + 3);
    echo "\">&nbsp;</td></tr>\n";
    echo '<tr><td class="blank" align="center" colspan="' . (sizeof($enabled_modules) + 3) . '">';
    ?>
    <p class="info">
        <b><?= _("Stud.IP kann Sie bei Änderungen in den einzelnen Inhaltsbereichen Ihrer Veranstaltungen automatisch per Email informieren.<br>Geben Sie hier an, über welche Änderungen Sie informiert werden wollen.") ?></b>
    </p>
    </td>
    </tr>
    <tr>
        <th colspan="2" width="90%"><?=_("Veranstaltung")?></th>
<?
    print_module_icons($enabled_modules);
    echo '<th align="center" style="font-size:small;">';
    if ($GLOBALS['auth']->auth['jscript']) {
        echo _("Alle");
    }else {
        echo '';
    }
    echo "</th></tr>\n";

    $groups = array();
    $my_sem = array();
    foreach ($seminars as $seminar) {
        $my_sem[$seminar['Seminar_id']] = array(
            'obj_type'       => "sem",
            'name'           => $seminar['Name'],
            'visible'        => $seminar['visible'],
            'gruppe'         => $seminar['gruppe'],
            'sem_status'     => $seminar['sem_status'],
            'sem_number'     => $seminar['sem_number'],
            'sem_number_end' => $seminar['sem_number_end']
        );
        if ($group_field){
            fill_groups($groups, $seminar[$group_field], array(
                'seminar_id' => $seminar['Seminar_id'],
                'name'       => $seminar['Name'],
                'gruppe'     => $seminar['gruppe']
            ));
        }
    }

    $sem_ids_cs = "'" . implode("','", array_keys($my_sem)) . "'";

    if ($group_field == 'sem_number') {
        correct_group_sem_number($groups, $my_sem);
    } else {
        add_sem_name($my_sem);
    }

    sort_groups($group_field, $groups);
    $group_names = get_group_names($group_field, $groups);
    $m_notifications = $modules->getModuleNotification();
    $c_checked = array();
    $s_count = 0;
    $out = '';
    foreach ($groups as $group_id => $group_members){
        if ($group_field != 'not_grouped') {
            $out .= '<tr><td class="blank" colspan="'.(sizeof($enabled_modules) + 3).'"><img src="'.$GLOBALS['ASSETS_URL'].'images/blank.gif" width="1px" height="5px"></td></tr>';
            $out .= '<tr><td class="blue_gradient" valign="top" height="20" colspan="';
            $out .= (sizeof($enabled_modules) + 3) . '">';
            if (isset($_my_sem_open[$group_id])){
                $out .= '<a class="tree" style="font-weight:bold" name="' . $group_id;
                $out .= '" href="' . URLHelper::getLink('?close_my_sem=' . $group_id);
                $out .= '#' .$group_id . '" ' . tooltip(_("Gruppierung schließen"), true) . '>';
                $out .= Assets::img('icons/16/blue/arr_1down.png');
            } else {
                $out .= '<a class="tree"  name="' . $group_id . '" href="' . URLHelper::getLink('?open_my_sem=' . $group_id . '#' .$group_id);
                $out .= '" ' . tooltip(_("Gruppierung öffnen"), true) . '>';
                $out .= Assets::img('icons/16/blue/arr_1right.png');
            }
            if (is_array($group_names[$group_id])){
                $group_name = $group_names[$group_id][1] . " > " . $group_names[$group_id][0];
            } else {
                $group_name = $group_names[$group_id];
            }
            $out .= htmlReady(my_substr($group_name,0,70));
            $out .= "</a></td></tr>\n";
        }

        if (isset($_my_sem_open[$group_id])) {
            $css->resetClass();
            $css->switchClass();
            $s_count++;
            foreach ($group_members as $member){
                $values = $my_sem[$member['seminar_id']];

                $out .= sprintf("<tr%s>\n<td class=\"gruppe%s\"><img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" border=\"0\" width=\"7\" height=\"12\"></td>",
                $css->getHover(), $values['gruppe']);
                $out .= sprintf("<td%s><font size=\"-1\">&nbsp;<a href=\"seminar_main.php?auswahl=%s\">%s</a>%s</font>",
                $css->getFullClass(), $member['seminar_id'],
                htmlReady(my_substr($values["name"],0,70)),
                (!$values["visible"] ? '&nbsp;' . _("(versteckt)")  : ''));
                $out .= "\n<input type=\"hidden\" ";
                $out .= 'name="m_checked[' . $member['seminar_id'] . "][33]\" value=\"0\"></td>\n";
                $m_count = 0;
                $r_checked = 0;
                foreach ($enabled_modules as $m_name => $m_data) {
                    $out .= '<td' . $css->getFullClass() . '>';
                    $out .= '<input type="checkbox" name="m_checked[' . $member['seminar_id'] . "][$m_count]\" ";
                    $out .= "value=\"" . pow(2, $m_data['id']) . '"';
                    if ($modules->isBit($m_notifications[$member['seminar_id']], $m_data['id'])) {
                        $out .= ' checked="checked"';
                        $c_checked[$m_count]++;
                        $r_checked++;
                    }
                    if ($GLOBALS['auth']->auth['jscript']) {
                        $out .= " onClick=\"selectSingle('{$member['seminar_id']}', '$m_count', this)\"";
                        $out .= " id=\"{$member['seminar_id']}_{$m_count}_{$group_id}\"";
                    }
                    $out .= "></td>\n";
                    $m_count++;
                }
                if ($GLOBALS['auth']->auth['jscript']) {
                    $out .= '<td' . $css->getFullClass() . 'nowrap="nowrap">&nbsp;&nbsp;';
                    $out .= "<input type=\"checkbox\" id=\"{$member['seminar_id']}_{$group_id}\"";
                    if ($r_checked == sizeof($enabled_modules)) {
                        $out .= 'checked="checked"';
                    }
                    $out .= " onClick=\"selectRow('{$member['seminar_id']}', this)\">";
                    $out .= '&nbsp;&nbsp;</td>';
                } else {
                    $out .= '<td' . $css->getFullClass() . '>&nbsp</td>';
                }
                $out .= "</tr>\n";
                $css->switchClass();
            }
        }
    }

    ?>
    <script type="text/javascript">
        <!--
            function selectSingle (sem_id, m_id, c_box) {
                var i;
                g_ids = new Array(<? echo "'" . implode("','", array_keys($groups)) . "'"; ?>);
                for (i = 0; i < g_ids.length; i++) {
                    if (document.getElementById(sem_id + '_' + m_id + '_' + g_ids[i])) {
                        document.getElementById(sem_id + '_' + m_id + '_' + g_ids[i]).checked = c_box.checked;
                        checkRow(sem_id, g_ids[i]);
                    }
                }
            }

            function selectRow (sem_id, c_box) {
            var i;
                var n;
                g_ids = new Array(<? echo "'" . implode("','", array_keys($groups)) . "'"; ?>);
                for (n = 0; n < g_ids.length; n++) {
                    if (document.getElementById(sem_id + '_' + g_ids[n])) {
                        document.getElementById(sem_id + '_' + g_ids[n]).checked = c_box.checked;
                    }
                for (i = 0; i < <? echo sizeof($enabled_modules); ?>; i++) {
                        if (document.getElementById(sem_id + '_' + i + '_' + g_ids[n])) {
                    document.getElementById(sem_id + '_' + i + '_' + g_ids[n]).checked = c_box.checked;
                        }
                    }
                }
            }

            function selectColumn (mod_id, c_box) {
                var i;
                sem_ids = new Array(<? echo $sem_ids_cs; ?>);
                for (i = 0; i < sem_ids.length; i++) {
                    selectSingle(sem_ids[i], mod_id, c_box)
                }
            }

            function selectAll (mod_count, c_box) {
                var i;
                var c_checked;
                for (i = 0; i < mod_count; i++) {
                    document.getElementById('mod_row_' + i).checked = c_box.checked;
                    selectColumn(i, document.getElementById('mod_row_' + i));
                }
            }

            function checkRow (sem_id, g_id) {
                var i;
                var n = 0;
                var m_count = <? echo sizeof($enabled_modules); ?>;
                for (i = 0; i < m_count; i++) {
                    if (document.getElementById(sem_id + '_' + i + '_' + g_id).checked) {
                        n++;
                    }
                }
                if (n == m_count) {
                    document.getElementById(sem_id + '_' + g_id).checked = 1;
                } else {
                    document.getElementById(sem_id + '_' + g_id).checked = 0;
                }
            }
    // -->
    </script>
    <?
    echo $out;
    if ($group_field != 'not_grouped') {
        echo '<tr><td class="blank" colspan="'.(sizeof($enabled_modules) + 3).'"><img src="'.$GLOBALS['ASSETS_URL'].'images/blank.gif" width="1px" height="5px"></td></tr>';
    }

    echo '<tr><th colspan="2">&nbsp;</th>';
    print_module_icons($enabled_modules);
    if ($GLOBALS['auth']->auth['jscript']) {
        echo '<th align="center" style="font-size:small;">' . _("Alle") . '</th>';
    } else {
        echo '<th>&nbsp;</th>';
    }
    echo "</tr>\n";
    if ($GLOBALS['auth']->auth['jscript']) {
        echo '<tr class="steel2"><td colspan="2" align="right">';
        echo _("Benachrichtigung für alle aufgelisteten Veranstaltungen:") . '</td>';
        for ($i = 0; $i < sizeof($enabled_modules); $i++) {
            echo "<td><input type=\"checkbox\" id=\"mod_row_$i\" ";
            if ($c_checked[$i] == count($seminars)) {
                echo 'checked="checked"';
            }
            echo "onClick=\"selectColumn($i, this)\"></td>";
        }
        echo '<td><input type="checkbox" onClick="selectAll(';
        echo sizeof($enabled_modules) . ', this)"';
        if (array_sum($c_checked) == count($seminars) * sizeof($enabled_modules)) {
            echo ' checked="checked"';
        }
        echo "></td></tr>\n";
    }
    echo '<tr><td class="blank" align="center" colspan="';
    echo (sizeof($enabled_modules) + 3) . '"><br>';
    echo Button::create(_('Übernehmen'), array('title' => _("Änderungen übernehmen")));
    if ($_REQUEST['view'] != 'notification') {
        echo "&nbsp; <a href=\"".URLHelper::getURL()."\">";
    } else {
        echo "&nbsp; <a href=\"".URLHelper::getLink('?view=notification')."\">";
    }
    echo Button::create(_('Zurücksetzen'), array('title' => _('zurücksetzen')));
    echo '<input type="hidden" name="cmd" value="set_sem_notification"><br>&nbsp; </td></tr></form>';
    echo "</table>\n";
}

if ($_REQUEST['view'] != 'notification') {
    echo "</td></tr></table>\n";

    include ('lib/include/html_end.inc.php');
  // Save data back to database.
  page_close();
}

?>
