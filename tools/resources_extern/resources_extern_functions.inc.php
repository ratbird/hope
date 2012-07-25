<?php
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// resources_extern_functions.inc.php
//
// Copyright (c) 2005 André Noack <noack@data-quest.de>
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
require_once 'ResourcesExternTree.class.php';
require_once 'lib/functions.php';
require_once $GLOBALS['RELATIVE_PATH_RESOURCES'] . "/views/ShowSemSchedules.class.php";
require_once $GLOBALS['RELATIVE_PATH_RESOURCES']."/lib/list_assign.inc.php";


class MockObjectPerms {
    function havePerm($foo){
        return false;
    }
}

function show_tree($rid, $level){
    $rtree = TreeAbstract::GetInstance('ResourcesExternTree', $GLOBALS['VIEWABLE_PROPERTY_ID']);
    if ($rtree->getNumKids($rid)){
        foreach ($rtree->getKids($rid) as $rrid){
            echo chr(10).'<div class="tree" style="margin-left:'.($level*20).'px;">';
            if($rtree->tree_data[$rrid]['viewable']){
                echo "\n<a href=\"".URLHelper::getLink('?view=sem_plan&semester_id='.$_SESSION['_semester_id'].'&timespan='.$_SESSION['_timespan'].'&rid='.$rrid)."\">";
            }
            echo htmlReady($rtree->tree_data[$rrid]['name']);
            if($rtree->tree_data[$rrid]['viewable']) echo '</a>';
            echo '</div>';
            show_tree($rrid, $level + 1);
        }
    }
}

function show_sem_plan($rid, $semester_id, $timespan = 'sem_time'){
    $rtree = TreeAbstract::GetInstance('ResourcesExternTree', $GLOBALS['VIEWABLE_PROPERTY_ID']);
    if ($rtree->tree_data[$rid]['viewable']){
        $GLOBALS['ActualObjectPerms'] = new MockObjectPerms();
        $ViewSchedules = new ShowSemSchedules($rid, $semester_id, $timespan);
        $ViewSchedules->showScheduleGraphical(1);
    }
}

function show_sem_chooser($semester_id, $timespan){
    $semester = SemesterData::GetSemesterArray();
    unset($semester[0]);
    echo chr(10) . '<form method="POST" name="schedule_form" action="'.URLHelper::getLink('?view='.Request::option('_view').'&rid='.Request::option('rid')).'">';
    echo CSRFProtection::tokenTag();
    echo chr(10) . '<div class="sem_chooser">' . _("Semester:");
    echo chr(10) . '&nbsp;&nbsp;<select name="semester_id" onChange="document.schedule_form.submit()">';
    foreach($semester as $one_sem){
        echo "\n<option value=\"{$one_sem['semester_id']}\" "
            . ($one_sem['semester_id'] == $semester_id ? "selected" : "")
            . ">" . htmlReady($one_sem['name']) . "</option>";
    }
     echo chr(10) . '</select>&nbsp;&nbsp;<input type="submit" name="jump" value="auswählen">';
     echo chr(10) . '<br>';
     echo chr(10) . '<input type="radio" onChange="document.schedule_form.submit()" style="vertical-align:bottom" '
                  . ($timespan == 'course_time' ? 'checked' : '').' name="timespan" value="course_time">'
                  . _("Vorlesungszeit")
                  . '&nbsp;&nbsp;<input type="radio" onChange="document.schedule_form.submit()" style="vertical-align:bottom" '
                  . ($timespan == 'sem_time' ? 'checked' : '') .' name="timespan" value="sem_time">'
                  . _("vorlesungsfreie Zeit")
                  . '</div>';
    echo chr(10) . '</form>';
}
?>
