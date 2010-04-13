<?
# Lifter007: TODO
# Lifter003: TODO
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


function get_log_action($action_id) {
    static $actions=array();
    if ($actions[$action_id]) {
        return $actions[$action_id];
    }
    $db=new DB_Seminar;
    $db->query("SELECT * FROM log_actions WHERE action_id='$action_id'");
    if ($db->next_record()) {
        $res=array("name"=>$db->f('name'),"info_template"=>$db->f('info_template'));
        $actions[$action_id]=$res;
        return $res;
    }
    return array("name"=>"unknown","info_template"=>"Error: unknown action");
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

function showlog_format_username($uid) {
    $uname=get_username($uid);
    if ($uname) {
        return '<a href="'.URLHelper::getLink('new_user_md5.php', array('details' => $uname)).'">'.htmlReady(get_fullname($uid)).'</a>';
    } else {
        return $uid;
    }
}

function showlog_format_sem($sem_id, $maxlen=100) {
    $db=new DB_Seminar();
    $q="SELECT seminare.Name as title, seminare.VeranstaltungsNummer as number, semester_data.name as semester FROM seminare LEFT JOIN semester_data ON (seminare.start_time=semester_data.beginn) WHERE Seminar_id='$sem_id'";
    $db->query($q);
    if ($db->next_record()) {
        $title=htmlReady(my_substr($db->f('title'),0,$maxlen));
        return '<a href="'.URLHelper::getLink('adminarea_start.php', array('select_sem_id' => $sem_id)).'">'.htmlReady($db->f('number')).' '.$title.' ('.htmlReady($db->f('semester')).')</a>';
    } else {
        return $sem_id;
    }
}

function showlog_format_institute($inst_id, $maxlen=100) {
    $db=new DB_Seminar();
    $q="SELECT Institute.Name as title FROM Institute WHERE Institut_id='$inst_id'";
    $db->query($q);
    if ($db->next_record()) {
        $title=htmlReady(my_substr($db->f('title'),0,$maxlen));
        return '<a href="'.URLHelper::getLink('institut_main.php', array('auswahl' => $inst_id)).'">'.$title.'</a>';
    } else {
        return $inst_id;
    }
}

function showlog_format_studyarea($area_id) {
    $db=new DB_Seminar();
    $q="SELECT parent_id, sem_tree.name as name, Institute.Name as iname FROM sem_tree LEFT JOIN Institute ON (sem_tree.studip_object_id=Institute.Institut_id) WHERE sem_tree_id='%s'";
    $db->query(sprintf($q,$area_id));
    if ($db->next_record()) {
        $path=array($db->f('name'));
        while ($db->f('parent_id')!="root") {
            $db->query(sprintf($q,$db->f('parent_id')));
            if ($db->next_record()) {
                if (!$db->f('name')) {
                    $path[]=htmlReady($db->f('iname'));
                } else {
                    $path[]=htmlReady($db->f('name'));
                }
            } else {
                break; // ERROR
            }
        }
        $path=array_reverse($path);
        return "<em>".implode(" &gt; ",$path)."</em>";
    } else {
        return $area_id;
    }
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

function showlog_search_seminar($needle) {
    $db=new DB_Seminar();
    // search for active seminars
    $q="SELECT Seminar_id, seminare.Name, semester_data.name as semester FROM seminare LEFT JOIN semester_data ON (seminare.start_time=semester_data.beginn) WHERE VeranstaltungsNummer like '%$needle%' OR seminare.Name like '%$needle%'";
    $db->query($q);
    $sems=array();
    while ($db->next_record()) {
        $sems[]=array($db->f("Seminar_id"),$db->f('VeranstaltungsNummer').' '.my_substr($db->f("Name"),0,40).' ('.$db->f('semester').')');
    }
    // search deleted seminars
    // SemName and Number is part of info field, old id (still in DB) is in affected column
    $q="SELECT * FROM log_events LEFT JOIN log_actions ON (log_actions.action_id=log_events.action_id) WHERE info LIKE '%$needle%' AND (log_actions.name='SEM_ARCHIVE' OR log_actions.name='SEM_DELETE_FROM_ARCHIVE')";
    $db->query($q);
    while ($db->next_record()) {
        $sems[]=array($db->f("affected_range_id"), my_substr($db->f("info"),0,40)." ("._("gelöscht").")");
    }

    return $sems;
}

function showlog_search_inst($needle) {
    $db=new DB_Seminar();
    $q="SELECT Institut_id, Name FROM Institute WHERE Name like '%$needle%'";
    $db->query($q);
    $sems=array();
    while ($db->next_record()) {
        $sems[]=array($db->f("Institut_id"),my_substr($db->f('Name'),0,28));
    }

    // search for deleted seminars
    // InstName is part of info field, old id (still in DB) is in affected column
    $q="SELECT * FROM log_events LEFT JOIN log_actions ON (log_actions.action_id=log_events.action_id) WHERE info LIKE '%$needle%' AND (log_actions.name='INST_DEL')";
    $db->query($q);
    while ($db->next_record()) {
        $sems[]=array($db->f("affected_range_id"),($db->f("info")." ("._("gelöscht").")"));
    }

    return $sems;
}

function showlog_search_user($needle) {
    global $_fullname_sql;
    $db=new DB_Seminar();
    $q="SELECT " . $_fullname_sql['full'] . " AS fullname, a.* FROM auth_user_md5 a LEFT JOIN user_info USING (user_id) WHERE Nachname LIKE '%$needle%' OR Vorname LIKE '%$needle%' OR username LIKE '%$needle%'";
    $db->query($q);
    $users=array();
    while ($db->next_record()) {
        $users[]=array($db->f("user_id"),my_substr($db->f('fullname'),0,20)." (".$db->f("username").")");
    }

    // search for deleted users
    // InstName is part of info field, old id (still in DB) is in affected column
    $q="SELECT * FROM log_events LEFT JOIN log_actions ON (log_actions.action_id=log_events.action_id) WHERE info LIKE '%$needle%' AND (log_actions.name='USER_DEL')";
    $db->query($q);
    while ($db->next_record()) {
        $users[]=array($db->f("affected_range_id"),($db->f("info")." ("._("gelöscht").")"));
    }

    return $users;
}

function showlog_search_resource($needle) {
    $db=new DB_Seminar();
    $q="SELECT resource_id, name FROM resources_objects WHERE name like '%$needle%'";
    $db->query($q);
    $sems=array();
    while ($db->next_record()) {
        $sems[]=array($db->f("resource_id"),my_substr($db->f("name"),0,30));
    }
    return $sems;
}

