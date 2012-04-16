<?
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: DONE - not applicable
/**
* show_log.inc.php
*
* Stud.IP event log display functions.
*
*
* @author               Tobias Thelen <tobias.thelen@uni-osnabrueck.de>
* @access               public
* @package              studip_core
* @modulegroup          library
* @module               logging
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// functions.php
// Stud.IP Kernfunktionen
// Copyright (C) 2002 Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>,
// Ralf Stockmann <rstockm@gwdg.de>, André Noack André Noack <andre.noack@gmx.net>
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

require_once $GLOBALS['RELATIVE_PATH_RESOURCES'] . '/lib/ResourceObject.class.php';


function get_log_action($action_id)
{
    static $actions = array();

    if ($actions[$action_id]) {
        return $actions[$action_id];
    }

    $query = "SELECT name, info_template FROM log_actions WHERE action_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($action_id));
    $temp = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$temp) {
        return array(
            'name'          => 'unknown',
            'info_template' => 'Error: unknown action'
        );
    }

    return $actions[$action_id] = $temp;
}

function showlog_format_resource($res_id) {
    $ret="";
    $resObj = ResourceObject::Factory($res_id);
    if ($resObj->getName())
        $ret .= $resObj->getFormattedLink();
    else
        $ret .= $res_id;
    return $ret;
}

function showlog_format_username($uid)
{
    return '<a href="'.URLHelper::getLink('dispatch.php/admin/user/edit/' . $uid) . '">'.htmlReady(get_fullname($uid)).'</a>';
}

function showlog_format_sem($sem_id, $maxlen=100)
{
    $query = "SELECT seminare.Name AS title, VeranstaltungsNummer, semester_data.name AS semester
              FROM seminare
              LEFT JOIN semester_data ON (seminare.start_time = semester_data.beginn)
              WHERE Seminar_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($sem_id));
    $temp = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$temp) {
        return $sem_id;
    }

    return sprintf('<a href="%s">%s %s (%s)</a>',
                   URLHelper::getLink('adminarea_start.php', array('select_sem_id' => $sem_id)),
                   htmlReady($temp['VeranstaltungsNummer']),
                   htmlReady(my_substr($temp['title'], 0, $maxlen)),
                   htmlReady($temp['semester']));
}

function showlog_format_institute($inst_id, $maxlen=100)
{
    $query = "SELECT Name FROM Institute WHERE Institut_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($inst_id));
    $name = $statement->fetchColumn();

    if (!$name) {
        return $inst_id;
    }

    return sprintf('<a href="%s">%s</a>',
                   URLHelper::getLink('institut_main.php', array('auswahl' => $inst_id)),
                   htmlReady(my_substr($name, 0, $maxlen)));
}

function showlog_format_studyarea($area_id)
{
    $query = "SELECT parent_id, sem_tree.name AS name, Institute.Name AS iname
              FROM sem_tree
              LEFT JOIN Institute ON (sem_tree.studip_object_id = Institute.Institut_id)
              WHERE sem_tree_id = ?";
    $statement = DBManager::get()->prepare($query);

    $statement->execute(array($area_id));
    $temp = $statement->fetch(PDO::FETCH_ASSOC);
    
    if (!$temp) {
        return $area_id;
    }

    $path = array($temp['name']);
    while ($temp['parent_id'] != 'root') {
        $statement->execute(array($temp['parent_id']));
        $temp = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$temp) {
            break;
        }

        array_unshift($path, $temp['name'] ?: $temp['iname']);
    }

    return '<em>' . implode(' &gt; ', $path) . '</em>';
}

function showlog_format_singledate($sd_id) {
    require_once('lib/raumzeit/SingleDate.class.php');
    $termin = new SingleDate($sd_id);
    return '<em>'.$termin->toString().'</em>';
}

function showlog_format_plugin($plugin_id) {
    $plugin_manager = PluginManager::getInstance();
    $plugin_info = $plugin_manager->getPluginInfoById($plugin_id);

    return $plugin_info ? '<em>'.$plugin_info['name'].'</em>' : $plugin_id;
}

function showlog_format_semester($sem_start_time) {
    require_once('lib/classes/SemesterData.class.php');
    $semester = new SemesterData();
    $all_semester = $semester->getAllSemesterData();
    foreach ($all_semester as $val) {
        if ($val['beginn'] == $sem_start_time) {
            return '<em>'.$val['name'].'</em>';
        }
    }
    return $sem_start_time;
}

function showlog_format_infotemplate($action, $user_id, $affected, $coaffected, $info, $dbg_info) {
    $info = htmlReady($info);
    $dbg_info = htmlReady($dbg_info);

    $text=$action['info_template'];
    $text=preg_replace('/%sem\(%affected\)/e','showlog_format_sem($affected)',$text);
    $text=preg_replace('/%sem\(%coaffected\)/e','showlog_format_sem($coaffected)',$text);
    $text=preg_replace('/%studyarea\(%affected\)/e','showlog_format_studyarea($affected)',$text);
    $text=preg_replace('/%studyarea\(%coaffected\)/e','showlog_format_studyarea($coaffected)',$text);
    $text=preg_replace('/%res\(%affected\)/e','showlog_format_resource($affected)',$text);
    $text=preg_replace('/%res\(%coaffected\)/e','showlog_format_resource($coaffected)',$text);
    $text=preg_replace('/%inst\(%affected\)/e','showlog_format_institute($affected)',$text);
    $text=preg_replace('/%inst\(%coaffected\)/e','showlog_format_institute($coaffected)',$text);
    $text=preg_replace('/%user\(%affected\)/e','showlog_format_username($affected)',$text);
    $text=preg_replace('/%user\(%coaffected\)/e','showlog_format_username($coaffected)',$text);
    $text=preg_replace('/%user/e','showlog_format_username($user_id)',$text);
    $text=preg_replace('/%singledate\(%affected\)/e','showlog_format_singledate($affected)',$text);
    $text=preg_replace('/%semester\(%coaffected\)/e','showlog_format_semester($coaffected)',$text);
    $text=preg_replace('/%plugin\(%coaffected\)/e','showlog_format_plugin($coaffected)',$text);
    $text=preg_replace('/%affected/',$affected,$text);
    $text=preg_replace('/%coaffected/',$coaffected,$text);
    $text=preg_replace('/%info/',$info,$text);
    $text=preg_replace('/%dbg_info/',$dbg_info,$text);
    return $text;
}

function showlog_search_seminar($needle)
{
    $result = array();

    // search for active seminars
    $query = "SELECT Seminar_id, seminare.Name, semester_data.name AS semester, VeranstaltungsNummer
              FROM seminare
              LEFT JOIN semester_data ON (seminare.start_time = semester_data.beginn)
              WHERE VeranstaltungsNummer LIKE CONCAT('%', :needle, '%')
                 OR seminare.Name LIKE CONCAT('%', :needle, '%')";
    $statement = DBManager::get()->prepare($query);
    $statement->bindParam(':needle', $needle);
    $statement->execute();

    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $title = sprintf('%s %s (%s)',
                         $row['VeranstaltungsNummer'],
                         my_substr($row['Name'], 0, 40),
                         $row['semester']);

        $result[] = array($row['Seminar_id'], $title);
    }

    // search deleted seminars
    // SemName and Number is part of info field, old id (still in DB) is in affected column
    $query = "SELECT affected_range_id, info
              FROM log_events
              LEFT JOIN log_actions USING (action_id)
              WHERE info LIKE CONCAT('%', ?, '%')
                AND log_actions.name IN ('SEM_ARCHIVE', 'SEM_DELETE_FROM_ARCHIVE')";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($needle));

    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $title = sprintf('%s (%s)', my_substr($row['info'], 0, 40), _('gelöscht'));
        $result[] = array($row['affected_range_id'], $title);
    }

    return $result;
}

function showlog_search_inst($needle)
{
    $result = array();

    $query = "SELECT Institut_id, Name FROM Institute WHERE Name LIKE CONCAT('%', ?, '%')";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($needle));

    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $result[] = array($row['Institut_id'], my_substr($row['Name'], 0, 28));
    }

    // search for deleted seminars
    // InstName is part of info field, old id (still in DB) is in affected column
    $query = "SELECT affected_range_id, info
              FROM log_events
              LEFT JOIN log_actions USING (action_id)
              WHERE info LIKE CONCAT('%', ?, '%') AND log_actions.name = 'INST_DEL'";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($needle));

    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $title = sprintf('%s (%s)', $row['info'], _('gelöscht'));
        $result[] = array($row['affected_range_id'], $title);
    }

    return $result;
}

function showlog_search_user($needle)
{
    global $_fullname_sql;

    $result = array();

    $query = "SELECT {$_fullname_sql['full']} AS fullname, a.user_id, a.username
              FROM auth_user_md5 AS a
              LEFT JOIN user_info USING (user_id)
              WHERE Nachname LIKE CONCAT('%', :needle, '%')
                 OR Vorname LIKE CONCAT('%', :needle, '%')
                 OR username LIKE CONCAT('%', :needle, '%')";
    $statement = DBManager::get()->prepare($query);
    $statement->bindParam(':needle', $needle);
    $statement->execute();
    
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $name = sprintf('%s (%s)', my_substr($row['fullname'], 0, 20), $row['username']);
        $result[] = array($row['user_id'], $name);
    }

    // search for deleted users
    // InstName is part of info field, old id (still in DB) is in affected column
    $query = "SELECT affected_range_id, info
              FROM log_events
              LEFT JOIN log_actions USING (action_id)
              WHERE info LIKE CONCAT('%', ?, '%') AND log_actions.name = 'USER_DEL'";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($needle));

    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $name = sprintf('%s (%s)', $row['info'], _('gelöscht'));
        $result[] = array($row['affected_range_id'], $name);
    }

    return $result;
}

function showlog_search_resource($needle)
{
    $result = array();

    $query = "SELECT resource_id, name FROM resources_objects WHERE name LIKE CONCAT('%', ?, '%')";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($needle));

    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $result[] = array($row['resource_id'], my_substr($row['name'], 0, 30));
    }

    return $result;
}
