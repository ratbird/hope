<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* evaluate_values.php
*
* handles all values, which are sent from the resources-management
*
*
* @author       Cornelis Kater <ckater@gwdg.de>
* @access       public
* @package      resources
* @modulegroup  resources
* @module       ScheduleWeek.class.php
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// evaluate_values.php
// Auswerten der Werte aus der Ressourcenverwaltung
// Copyright (C) 2003 Cornelis Kater <ckater@gwdg.de>, data-quest GmbH <info@data-quest.de>
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

require_once ($RELATIVE_PATH_RESOURCES.'/lib/AssignObject.class.php');
require_once ($RELATIVE_PATH_RESOURCES.'/lib/AssignObjectPerms.class.php');
require_once ($RELATIVE_PATH_RESOURCES.'/lib/ResourceObject.class.php');
require_once ($RELATIVE_PATH_RESOURCES.'/lib/ResourceObjectPerms.class.php');
require_once ($RELATIVE_PATH_RESOURCES.'/lib/RoomGroups.class.php');
require_once ($RELATIVE_PATH_RESOURCES.'/lib/RoomRequest.class.php');
require_once ('lib/dates.inc.php');
require_once ("lib/classes/SemesterData.class.php");

//a temp session-variable...
$sess->register("new_assign_object");

/*****************************************************************************
Functions...
/*****************************************************************************/

//a small helper function to close all the kids
function closeStructure ($resource_id) {
    global $resources_data;
    $db = new DB_Seminar;

    unset($resources_data["structure_opens"][$resource_id]);
    $query = sprintf ("SELECT resource_id FROM resources_objects WHERE parent_id = '%s' ", $resource_id);
    $db->query($query);
    while ($db->next_record()) {
        closeStructure ($db->f("resource_id"));
    }
}
/*****************************************************************************
Initialization
/*****************************************************************************/
$GLOBALS['messageForUsers'] = '';
foreach (words('view view_mode quick_view quick_view_mode') as $parameter_name) {
    $$parameter_name = Request::option($parameter_name);
}

//a small helper function to update some data of the tree-structure (after move something)
function updateStructure ($resource_id, $root_id, $level) {
    $db = new DB_Seminar;
    $query = sprintf ("UPDATE resources_objects SET root_id = '%s', level='%s' WHERE resource_id = '%s' ", $root_id, $level, $resource_id);
    $db->query($query);

    $query = sprintf ("SELECT resource_id FROM resources_objects WHERE parent_id = '%s' ", $resource_id);
    $db->query($query);
    while ($db->next_record()) {
        closeStructure ($db->f("resource_id"), $root_id, $level+1);
    }
}

/*****************************************************************************
handle the view-logic
/*****************************************************************************/

//got a fresh session?
if ((!$view && !$quick_view && !isset($resources_data['view']))
    || ( sizeof($_POST) == 0 && sizeof($_GET) == 0
    && (!isset($resources_data['view']) || $resources_data['view_mode'] == 'oobj' || $resources_data['view_mode'] == 'search') ) ) {
    $resources_data='';
    $resources_data["view"]="search";
    $resources_data["view_mode"]=FALSE;
    closeObject();
}

//get views/view_modes
if ($view)
    $resources_data["view"]=$view;
else //or we take back the persistant view from $resources_data
    $view = $resources_data["view"];

if ($view_mode)
    $resources_data["view_mode"]=$view_mode;
else //or... see above ;)
    $view_mode = $resources_data["view_mode"];

if (strpos($view, "openobject") !== FALSE) {
    $resources_data["view_mode"] = "oobj";
    $view_mode = "oobj";
}

//if quick_view, we take this view (only one page long, until the next view is given!)
if ($quick_view)
    $view = $quick_view;

//we do so for the view_mode too
if ($quick_view_mode)
    $view_mode = $quick_view_mode;
else
    $quick_view_mode = $resources_data["view_mode"];

//reset edit the assign... (Zugegeben, immer noch krank: Hier wird ein "sauberer" Seitenaufruf anhand der Anzahl der Parameter ermittelt... )
if (((sizeof($_POST) + sizeof($_GET)) == 2) && (($view == "edit_object_assign") || ($view == "openobject_assign"))) {
    $new_assign_object=FALSE;
}
if (((sizeof($_POST) + sizeof($_GET)) == 3) && ($edit_assign_object) && (($view == "edit_object_assign") || ($view == "openobject_assign"))) {
    $new_assign_object=FALSE;
}
if ($cancel_edit_assign) {
    $new_assign_object=FALSE;
    $resources_data["actual_assign"]=FALSE;
}

//send the user to index, if he want to use studip-object based modul but has no object set!
if (($view=="openobject_main") || ($view=="openobject_details") || ($view=="openobject_assign") || ($view=="openobject_schedule")){
    if (!$SessSemName[1]) {
        $resources_data = null;
        $resources_data["view"] = $view = "search";
        $resources_data["view_mode"] = $view_mode = FALSE;
    }
}
//we take a search as long with us, as no other overview modul is used
if (($view=="openobject_main") || ($view=="lists") || ($view=="resources"))
    $resources_data["search_array"]='';



//Open a level/resource
if ($structure_open) {
    $resources_data["structure_opens"][$structure_open] =TRUE;
    $resources_data["actual_object"]=$structure_open;
}

if ($edit_object)
    $resources_data["actual_object"]=$edit_object;


//Select an object to work with
if ($actual_object) {
    $resources_data["actual_object"]=$actual_object;
}

//Close a level/resource
if ($structure_close)
    closeStructure ($structure_close);

//switch to move mode
if ($pre_move_object) {
    $resources_data["move_object"]=$pre_move_object;
}

//cancel move mode
if ($cancel_move) {
    $resources_data["move_object"]='';
}

//Listenstartpunkt festlegen
if ($open_list) {
    $resources_data["list_open"]=$open_list;
    $resources_data["view"]="lists";
    $view = $resources_data["view"];
    }

if ($recurse_list)
    $resources_data["list_recurse"]=TRUE;

if ($nrecurse_list)
    $resources_data["list_recurse"]=FALSE;

//Create ClipBoard-Class, if needed
if (($view == "search") || ($view == "edit_request")) {
    require_once ("lib/classes/ClipBoard.class.php");

    $clipObj =  ClipBoard::GetInstance("search");
    $clipFormObj =& $clipObj->getFormObject();
    if ($view == "edit_request") {
        array_unshift ($clipFormObj->form_fields['clip_cmd']['options'], array('name' => _("In aktueller Anfrage mit berücksichtigen"), 'value' => 'add'));
        $clipFormObj->form_buttons['clip_reload'] = array('type' => 'aktualisieren', 'info' => _("neu laden"));


        if (($clipFormObj->getFormFieldValue("clip_cmd") == "add") && ($clipFormObj->IsClicked("clip_ok"))){
            $marked_clip_ids = $clipFormObj->getFormFieldValue("clip_content");
            $msg->addMsg(32);
        }
    }

    if ($clip_in)
        $clipObj->insertElement($clip_in, "res");
    if ($clip_out)
        $clipObj->deleteElement($clip_out);
    if (!$clipFormObj->IsClicked("clip_reload"))
        $clipObj->doClipCmd();
}


//Neue Hierachieebene oder Unterebene anlegen
if ($view == "create_hierarchie" || $create_hierachie_level) {
    if ($view == "create_hierarchie") {
        $newHiearchie = ResourceObject::Factory("Neue Hierachie", "Dieses Objekt kennzeichnet eine Hierachie und kann jederzeit in eine Ressource umgewandelt werden"
                        , '', '', '', '', $user->id);
    } elseif ($create_hierachie_level) {
        $parent_Object = ResourceObject::Factory($create_hierachie_level);
        $newHiearchie = ResourceObject::Factory("Neue Hierachieebene", "Dieses Objekt kennzeichnet eine neue Hierachieebene und kann jederzeit in eine Ressource umgewandelt werden"
                        , '', $parent_Object->getRootId(), $create_hierachie_level, '', $user->id);
    }
    $newHiearchie->create();
    $edit_structure_object=$newHiearchie->id;
    $resources_data["structure_opens"][$newHiearchie->id] =TRUE;
    $resources_data["actual_object"]=$newHiearchie->getId();
    $resources_data["view"]="resources";
    $view = $resources_data["view"];
    }

//Neues Objekt anlegen
if ($create_object) {
    $parent_Object = ResourceObject::Factory($create_object);
    $new_Object= ResourceObject::Factory("Neues Objekt", "Dieses Objekt wurde neu erstellt. Es wurden noch keine Eigenschaften zugewiesen."
                    , FALSE, $parent_Object->getRootId(), $create_object, "0", $user->id);
    $new_Object->create();
    $resources_data["view"]="edit_object_properties";
    $view = $resources_data["view"];
    $resources_data["actual_object"]=$new_Object->getId();
    }


//Object loeschen
if ($kill_object) {
    $ObjectPerms = ResourceObjectPerms::Factory($kill_object);
    if ($ObjectPerms->getUserPerm () == "admin") {
        $killObject = ResourceObject::Factory($kill_object);
        if ($killObject->delete())
            $msg -> addMsg(7);
        $resources_data["view"]="resources";
        $view = $resources_data["view"];
        unset($resources_data['actual_object']);
    } else {
        $msg->addMsg(1);
    }
}

//cancel a just created object
if ($cancel_edit) {
    $ObjectPerms = ResourceObjectPerms::Factory($cancel_edit);
    if ($ObjectPerms->getUserPerm () == "admin") {
        $cancel_edit = ResourceObject::Factory($cancel_edit);
        $cancel_edit->delete();
        $resources_data["view"]="resources";
        $view = $resources_data["view"];
        unset($resources_data['actual_object']);
    } else {
        $msg->addMsg(1);
    }
}


//move an object
if ($target_object) {
    $ObjectPerms = ResourceObjectPerms::Factory($target_object);
    if ($ObjectPerms->getUserPerm () == "admin") {
        if ($target_object != $resources_data["move_object"]) {
            //we want to move an object, so we have first to check if we want to move a object in a subordinated object
            $db->query ("SELECT parent_id FROM resources_objects WHERE resource_id = '$target_object'");
            while ($db->next_record()) {
                if ($db->f("parent_id") == $resources_data["move_object"])
                    $target_is_child=TRUE;
                $db->query ("SELECT parent_id FROM resources_objects WHERE resource_id = '".$db->f("parent_id")."' ");
            }
            if (!$target_is_child) {
                $db->query ("UPDATE resources_objects SET parent_id='$target_object' WHERE resource_id = '".$resources_data["move_object"]."' ");
                $db->query ("SELECT root_id, level FROM resources_objects WHERE resource_id = '$target_object'");
                $db->next_record();
                //set the correct root_id's and levels
                updateStructure($resources_data["move_object"], $db->f("root_id"), $db->f("level")+1);
                $resources_data["structure_opens"][$resources_data["move_object"]] =TRUE;
                $resources_data["structure_opens"][$target_object] =TRUE;
                if ($db->nf()) {
                    $msg -> addMsg(9);
                }
            }
        }
        unset($resources_data["move_object"]);
    } else {
        $msg->addMsg(1);
    }
}

//Name und Beschreibung aendern
if ($change_structure_object) {
    $ObjectPerms = ResourceObjectPerms::Factory($change_structure_object);
    if ($ObjectPerms->getUserPerm () == "admin") {
        $changeObject = ResourceObject::Factory($change_structure_object);
        $changeObject->setName($change_name);
        $changeObject->setDescription($change_description);
        if ($changeObject->store())
            $msg -> addMsg(6);
    } else {
        $msg->addMsg(1);
    }
    $resources_data["view"]="resources";
    $view = $resources_data["view"];
    $resources_data["actual_object"]=$change_structure_object;
}

/*****************************************************************************
edit/add assigns
/*****************************************************************************/

//Objektbelegung erstellen/aendern
if ($change_object_schedules) {
    require_once ('lib/calendar_functions.inc.php'); //needed for extended checkdate
    require_once ($RELATIVE_PATH_RESOURCES."/lib/VeranstaltungResourcesAssign.class.php");
    require_once ('lib/classes/SemesterData.class.php');

    // check, if the submit-button has been pressed. Otherwise do not store the assign.
    $storeAssign = false;
    if ($_REQUEST['submit_x']) {
        $storeAssign = true;
    }

    $semester = new SemesterData;
    $all_semester = $semester->getAllSemesterData();
    //load the object perms
    $ObjectPerms = ResourceObjectPerms::Factory($change_schedule_resource_id);

    //in some case, we load the perms from the assign object, if it has an owner
    if (($ObjectPerms->getUserPerm() != "admin") && ($change_object_schedules != "NEW") && (!$new_assign_object)) {
        //load the assign-object perms of a saved object
        $SavedStateAssignObject = AssignObject::Factory($change_object_schedules);
        if ($SavedStateAssignObject->getAssignUserId()){
            unset($ObjectPerms);
            $ObjectPerms = new AssignObjectPerms($change_object_schedules);
        }
    }

    if (($ObjectPerms->havePerm("tutor")) && ($change_meta_to_single_assigns_x)) {
        $assObj = AssignObject::Factory($change_object_schedules);
        if ($assObj->getOwnerType() != 'sem'){
            $events = $assObj->getEvents();
            if (is_array($events)){
                $create_assign = new AssignObject(false);
                $create_assign->setResourceId($assObj->getResourceId());
                $create_assign->setAssignUserId($assObj->getAssignUserId());
                $create_assign->setUserFreeName($assObj->getUserFreeName());
                $assObj->delete();
                foreach($events as $one_event){
                    $create_assign->setBegin($one_event->begin);
                    $create_assign->setEnd($one_event->end);
                    $create_assign->id = $one_event->id;
                    $create_assign->store(true);
                }
                $return_schedule = TRUE;
                $change_object_schedules = $events[0]->id;
                header (sprintf("Location:resources.php?quick_view=%s&quick_view_mode=%s&show_msg=37", ($view_mode == "oobj") ? "openobject_sem_schedule" : "view_sem_schedule", $view_mode));
            }
        }
    }

    if ($ObjectPerms->havePerm("admin") && isset($_POST['send_change_resource_x']) && isset($_POST['select_change_resource'])) {
        if(!is_array($_POST['select_change_resource'])){
        $ChangeObjectPerms = ResourceObjectPerms::Factory($_POST['select_change_resource']);
        if ($ChangeObjectPerms->havePerm("tutor")) {
                $changeAssign = AssignObject::Factory($_REQUEST['change_object_schedules']);
                $changeAssign->setResourceId($_POST['select_change_resource']);
            $overlaps = $changeAssign->checkOverlap();
            if ($overlaps) {
                $msg->addMsg(11);
            } else {
                $changeAssign->store();
                $return_schedule = TRUE;
                header (sprintf("Location:resources.php?quick_view=%s&quick_view_mode=%s&show_msg=38&msg_resource_id=%s", ($view_mode == "oobj") ? "openobject_schedule" : "view_schedule", $view_mode, $select_change_resource));
            }
        } else
            $msg->addMsg(2);
        } else {
            $original_assign = AssignObject::Factory($_REQUEST['change_object_schedules']);
            foreach($_POST['select_change_resource'] as $copy_to_resource_id){
                $ChangeObjectPerms = ResourceObjectPerms::Factory($copy_to_resource_id);
                if ($ChangeObjectPerms->havePerm("tutor")) {
                    $new_assign = $original_assign->getCopyForResource($copy_to_resource_id);
                    $overlaps = $new_assign->checkOverlap();
                    if ($overlaps) {
                        $bad_msg = _("Nicht buchbare Belegungszeiten:");
                        foreach($overlaps as $overlap){
                            $bad_msg .= "<br>".date("d.m.Y, H:i",$overlap["begin"])." - ".date("d.m.Y, H:i",$overlap["end"]);
                        }
                        $msg->addMsg(48, array(htmlReady(ResourceObject::Factory($copy_to_resource_id)->getName()), $bad_msg));
                    } else {
                        $new_assign->store(true);
                        $msg->addMsg(47, array(htmlReady(ResourceObject::Factory($copy_to_resource_id)->getName())));
                    }
                }
            }
    }
    }

    if ($ObjectPerms->havePerm("autor")) {
        if ($kill_assign_x) {
            $killAssign = AssignObject::Factory($change_object_schedules);
            $killAssign->delete();
            $new_assign_object='';
            $msg->addMsg(5);
            $change_schedule_id = $change_object_schedules = $resources_data['actual_assign'] = FALSE;
        } elseif (!$return_schedule && !isset($search_room_x) && !isset($reset_room_search_x)) {
            if ($change_object_schedules == "NEW")
                $change_schedule_id=FALSE;
            else
                $change_schedule_id=$change_object_schedules;

            if ($reset_search_user_x)
                $search_string_search_user=FALSE;

            if (($send_search_user_x) && ($submit_search_user !="FALSE") && (!$reset_search_user_x)) {
                //Check if this user is able to reach the resource (and this assign), to provide, that the owner of the resources foists assigns to others
                $ForeignObjectPerms = ResourceObjectPerms::Factory($change_schedule_resource_id, $submit_search_user);
                if ($ForeignObjectPerms->havePerm("autor"))
                    $change_schedule_assign_user_id=$submit_search_user;
                else
                    $msg->addMsg(2);
            }

            //the user send infinity repeat (until date) as empty field, but it's -1 in the db
            if (($change_schedule_repeat_quantity_infinity) && (!$change_schedule_repeat_quantity))
                $change_schedule_repeat_quantity=-1;

            //check dates
            $illegal_dates=FALSE;
            if ((!check_date($change_schedule_month, $change_schedule_day, $change_schedule_year, $change_schedule_start_hour, $change_schedule_start_minute)) ||
                (!check_date($change_schedule_month, $change_schedule_day, $change_schedule_year, $change_schedule_end_hour, $change_schedule_end_minute))) {
                $illegal_dates=TRUE;
                $msg -> addMsg(17);
            }

            //create timestamps
            if (!$illegal_dates) {
                $change_schedule_begin=mktime($change_schedule_start_hour, $change_schedule_start_minute, 0, $change_schedule_month, $change_schedule_day, $change_schedule_year);
                $change_schedule_end=mktime($change_schedule_end_hour, $change_schedule_end_minute, 0, $change_schedule_month, $change_schedule_day, $change_schedule_year);
                if ($change_schedule_begin > $change_schedule_end) {
                    if (($change_schedule_repeat_mode != "sd") && (!$change_schedule_repeat_severaldays_x)) {
                        $illegal_dates=TRUE;
                        $msg -> addMsg(20);
                    }
                }
            }

            if (check_date($change_schedule_repeat_end_month, $change_schedule_repeat_end_day, $change_schedule_repeat_end_year))
                if ($change_schedule_repeat_mode == "sd")
                    $change_schedule_repeat_end=mktime(date("G", $change_schedule_end), date("i", $change_schedule_end), 0, $change_schedule_repeat_end_month, $change_schedule_repeat_end_day, $change_schedule_repeat_end_year);
                else
                    $change_schedule_repeat_end=mktime(23, 59, 59, $change_schedule_repeat_end_month, $change_schedule_repeat_end_day, $change_schedule_repeat_end_year);

            if ($change_schedule_repeat_sem_end)
                foreach ($all_semester as $a)
                    if (($change_schedule_begin >= $a["beginn"]) && ($change_schedule_begin <= $a["ende"]))
                        $change_schedule_repeat_end=$a["vorles_ende"];

            //create repeatdata

            //repeat = none
            if ($change_schedule_repeat_none_x) {
                $change_schedule_repeat_end='';
                $change_schedule_repeat_month_of_year='';
                $change_schedule_repeat_day_of_month='';
                $change_schedule_repeat_week_of_month='';
                $change_schedule_repeat_day_of_week='';
                $change_schedule_repeat_quantity='';
                $change_schedule_repeat_interval='';
                if (($change_schedule_begin > $change_schedule_end) && (!$illegal_dates)) { //do this check again, if the user select's sevral days and give a repeat end < begin, so we have no repeatation
                    $illegal_dates=TRUE;
                    $msg -> addMsg(20);
                }
            }


            //repeat = several days
            if ($change_schedule_repeat_severaldays_x) {
                $change_schedule_repeat_end = mktime(date("G", $change_schedule_end), date("i", $change_schedule_end), 0, date("n", $change_schedule_begin), date("j", $change_schedule_begin)+1, date("Y", $change_schedule_begin));
                $change_schedule_repeat_month_of_year='';
                $change_schedule_repeat_day_of_month='';
                $change_schedule_repeat_week_of_month='';
                $change_schedule_repeat_day_of_week='';
                $change_schedule_repeat_quantity='';
                $change_schedule_repeat_interval='';
            }

            //repeat = year
            if ($change_schedule_repeat_year_x) {
                $change_schedule_repeat_month_of_year=date("n", $change_schedule_begin);
                $change_schedule_repeat_day_of_month=date("j", $change_schedule_begin);
                $change_schedule_repeat_week_of_month='';
                $change_schedule_repeat_day_of_week='';
                if (!$change_schedule_repeat_quantity   )
                    $change_schedule_repeat_quantity=-1;
                if (!$change_schedule_repeat_interval)
                    $change_schedule_repeat_interval=1;
            }

            //repeat = month
            if ($change_schedule_repeat_month_x)
                if (!$change_schedule_repeat_week_of_month) {
                    $change_schedule_repeat_month_of_year='';
                    $change_schedule_repeat_day_of_month=date("j", $change_schedule_begin);
                    $change_schedule_repeat_week_of_month='';
                    $change_schedule_repeat_day_of_week='';
                    if (!$change_schedule_repeat_quantity   )
                        $change_schedule_repeat_quantity=-1;
                    if (!$change_schedule_repeat_interval)
                        $change_schedule_repeat_interval=1;
                }

            //repeat = week
            if ($change_schedule_repeat_week_x) {
                $change_schedule_repeat_month_of_year='';
                $change_schedule_repeat_day_of_month='';
                $change_schedule_repeat_week_of_month='';
                $change_schedule_repeat_quantity='';
                if (!$change_schedule_repeat_day_of_week)
                    $change_schedule_repeat_day_of_week=1;
                if (!$change_schedule_repeat_quantity   )
                    $change_schedule_repeat_quantity=-1;
                if (!$change_schedule_repeat_interval)
                    $change_schedule_repeat_interval=1;
            }

            //repeat = day
            if ($change_schedule_repeat_day_x) {
                $change_schedule_repeat_month_of_year='';
                $change_schedule_repeat_day_of_month='';
                $change_schedule_repeat_week_of_month='';
                $change_schedule_repeat_quantity='';
                $change_schedule_repeat_day_of_week='';
                if (!$change_schedule_repeat_quantity   )
                    $change_schedule_repeat_quantity=-1;
                if (!$change_schedule_repeat_interval)
                    $change_schedule_repeat_interval=1;
            }

            //repeat days, only if week
            if ($change_schedule_repeat_day1_x)
                $change_schedule_repeat_day_of_week=1;
            if ($change_schedule_repeat_day2_x)
                $change_schedule_repeat_day_of_week=2;
            if ($change_schedule_repeat_day3_x)
                $change_schedule_repeat_day_of_week=3;
            if ($change_schedule_repeat_day4_x)
                $change_schedule_repeat_day_of_week=4;
            if ($change_schedule_repeat_day5_x)
                $change_schedule_repeat_day_of_week=5;
            if ($change_schedule_repeat_day6_x)
                $change_schedule_repeat_day_of_week=6;
            if ($change_schedule_repeat_day7_x)
                $change_schedule_repeat_day_of_week=7;

            //give data to the assignobject
            if (!$change_schedule_id){
                $changeAssign = AssignObject::Factory(
                    $change_schedule_id,
                    $change_schedule_resource_id,
                    $change_schedule_assign_user_id,
                    $change_schedule_user_free_name,
                    $change_schedule_begin,
                    $change_schedule_end,
                    $change_schedule_repeat_end,
                    $change_schedule_repeat_quantity,
                    $change_schedule_repeat_interval,
                    $change_schedule_repeat_month_of_year,
                    $change_schedule_repeat_day_of_month,
                    $change_schedule_repeat_week_of_month,
                    $change_schedule_repeat_day_of_week);
            } else {
                $changeAssign = AssignObject::Factory($change_schedule_id);
                $changeAssign->setResourceId($change_schedule_resource_id);
                $changeAssign->setUserFreeName($change_schedule_user_free_name);
                $changeAssign->setAssignUserId($change_schedule_assign_user_id);
                $changeAssign->setBegin($change_schedule_begin);
                $changeAssign->setEnd($change_schedule_end);
                $changeAssign->setRepeatEnd($change_schedule_repeat_end);
                $changeAssign->setRepeatQuantity($change_schedule_repeat_quantity);
                $changeAssign->setRepeatInterval($change_schedule_repeat_interval);
                $changeAssign->setRepeatMonthOfYear($change_schedule_repeat_month_of_year);
                $changeAssign->setRepeatDayOfMonth($change_schedule_repeat_day_of_month);
                $changeAssign->setRepeatWeekOfMonth($change_schedule_repeat_week_of_month);
                $changeAssign->setRepeatDayOfWeek($change_schedule_repeat_day_of_week);
            }

            //if isset quantity, we calculate the correct end date
            if ($changeAssign->getRepeatQuantity() >0)
                $changeAssign->setRepeatEnd($changeAssign->getRepeatEndByQuantity());

            //check repeat_end
            if (($changeAssign->getRepeatMode() != "na") && ($change_schedule_repeat_end_month) && ($change_schedule_repeat_end_day) && ($change_schedule_repeat_end_year)) {
                if (!check_date($change_schedule_repeat_end_month, $change_schedule_repeat_end_day, $change_schedule_repeat_end_year)) {
                    $illegal_dates=TRUE;
                    $msg -> addMsg(18);
                }
                //repeat end schould not be bevor the begin
                if (!$illegal_dates) {
                    if ($changeAssign->getEnd() > $changeAssign->getRepeatEnd()) {
                        $changeAssign->setRepeatEnd($changeAssign->getBegin());
                    }
                }
                //limit recurrences
                if (!$illegal_dates) {
                    switch ($changeAssign->getRepeatMode()) {
                        case "y" : if ((date("Y",$changeAssign->getRepeatEnd()) - date("Y", $changeAssign->getBegin())) > 10) {
                                    $illegal_dates=TRUE;
                                    $msg -> addMsg(21);
                                }
                        break;
                        case "m" : if ((date("Y",$changeAssign->getRepeatEnd()) - date("Y", $changeAssign->getBegin())) > 10) {
                                    $illegal_dates=TRUE;
                                    $msg -> addMsg(22);
                                }
                        break;
                        case "w" : if ((($changeAssign->getRepeatEnd() - $changeAssign->getBegin()) / (60 * 60 * 24 *7) / $changeAssign->getRepeatInterval()) > 50) {
                                    $illegal_dates=TRUE;
                                    $msg -> addMsg(23);
                                }
                        break;
                        case "d" : if ((int)(($changeAssign->getRepeatEnd() - $changeAssign->getBegin()) / (60 * 60 * 24) / $changeAssign->getRepeatInterval()) > 100) {
                                    $illegal_dates=TRUE;
                                    $msg -> addMsg(24);
                                }
                        break;
                    }
                }
            }

            if ($illegal_dates) {
                if ($changeAssign->isNew()){
                    $new_assign_object=serialize($changeAssign);
                } else {
                    $changeAssign->restore();
                }
            }

            // create a new assign
            elseif ( ($change_object_schedules == "NEW" || $new_assign_object)){

                if (($change_schedule_assign_user_id) || ($change_schedule_user_free_name)) {
                    $overlaps = $changeAssign->checkOverlap(FALSE);
                    $locks = $changeAssign->checkLock();
                }
                // show hint, that either a user or a free text must be provided
                else if ($storeAssign) {
                    $msg->addMsg(46);
                }

                if ((!$overlaps) && (!$locks)) {
                    if ($storeAssign && $changeAssign->create()) {
                        $resources_data["actual_assign"]=$changeAssign->getId();
                        $msg->addMsg(3);
                        $new_assign_object='';
                    } else {
                        $new_assign_object=serialize($changeAssign);  // store the submitted form-data

                        if ( $storeAssign && !$do_search_user_x && !$reset_search_user_x
                            && !$change_schedule_assign_user_id && $change_schedule_user_free_name) {
                                $msg->addMsg(10);
                        }
                    }
                } else {
                    if ($storeAssign) {
                        if ($overlaps) {  // add error message an store the submitted form-data
                            $msg->addMsg(11);
                            $new_assign_object=serialize($changeAssign);
                        }

                        if ($locks) {
                            foreach ($locks as $val)
                                $locks_txt.=date("d.m.Y, H:i",$val["lock_begin"])." - ".date("d.m.Y, H:i",$val["lock_end"])."<br>";
                            $msg->addMsg(44, array($locks_txt));
                        }
                    } else {  // store the submitted form-data
                        $new_assign_object=serialize($changeAssign);
                    }
                }
            }

            // change an existing assign
            else {
                if (($change_schedule_assign_user_id) || ($change_schedule_user_free_name)) {
                    $overlaps = $changeAssign->checkOverlap(FALSE);
                    $locks = $changeAssign->checkLock();
                }

                if ((!$overlaps) && (!$locks)) {
                    $changeAssign->chng_flag=TRUE;
                    if ($changeAssign->store()) {
                        $msg->addMsg(4);
                        $new_assign_object='';
                    }
                    $resources_data["actual_assign"]=$changeAssign->getId();
                } else {
                    if ($overlaps)
                        $msg->addMsg(11);
                    if ($locks) {
                        foreach ($locks as $val)
                            $locks_txt.=date("d.m.Y, H:i",$val["lock_begin"])." - ".date("d.m.Y, H:i",$val["lock_end"])."<br>";
                        $msg->addMsg(44, array($locks_txt));
                    }
                    $changeAssign->restore();
                }

            }
        }
    } else {
        $msg->addMsg(1);
    }
}

//Objekteigenschaften aendern
if ($change_object_properties) {
    $ObjectPerms = ResourceObjectPerms::Factory($change_object_properties);
    if ($ObjectPerms->getUserPerm () == "admin") {
        $changeObject = ResourceObject::Factory($change_object_properties);
        $changeObject->setName($change_name);
        $changeObject->setDescription($change_description);
        $changeObject->setCategoryId($change_category_id);
        $changeObject->setParentBind($change_parent_bind);
        $changeObject->setInstitutId($change_institut_id);

        if (getGlobalPerms($user->id) == "admin") {
            $changeObject->setMultipleAssign($change_multiple_assign);
        }

        //Properties loeschen
        $changeObject->flushProperties();

        //Eigenschaften neu schreiben
        $props_changed=FALSE;
        if (is_array($change_property_val))
            foreach ($change_property_val as $key=>$val) {
                if ((substr($val, 0, 4) == "_id_") && (substr($change_property_val[$key+1], 0, 4) != "_id_"))
                    if ($changeObject->storeProperty(substr($val, 4, strlen($val)), $change_property_val[$key+1]))
                        $props_changed=TRUE;
            }

        //Object speichern
        if (($changeObject->store()) || ($props_changed))
            $msg -> addMsg(6);
    } else {
        $msg->addMsg(1);
    }

    $resources_data["view"]="edit_object_properties";
    $view = $resources_data["view"];
}

//Objektberechtigungen aendern
if ($change_object_perms) {
    $ObjectPerms = ResourceObjectPerms::Factory($change_object_perms);
    if ($ObjectPerms->getUserPerm () == "admin") {
        $changeObject = ResourceObject::Factory($change_object_perms);

        if (is_array($change_user_id))
            foreach ($change_user_id as $key=>$val) {
                if ($changeObject->storePerms($val, $change_user_perms[$key]))
                    $perms_changed=TRUE;
            }

        if ($delete_user_perms)
            if ($changeObject->deletePerms($delete_user_perms))
                $perms_changed=TRUE;

        if ($reset_search_owner_x)
            $search_string_search_owner=FALSE;

        if ($reset_search_perm_user_x)
            $search_string_search_perm_user=FALSE;

        if (($send_search_owner_x) && ($submit_search_owner !="FALSE") && (!$reset_search_owner_x))
            $changeObject->setOwnerId($submit_search_owner);

        if (($send_search_perm_user_x) && ($submit_search_perm_user !="FALSE") && (!$reset_search_perm_user_x))
            if ($changeObject->storePerms($submit_search_perm_user))
                $perms_changed=TRUE;

        if ((getGlobalPerms($user->id) == "admin") && ($changeObject->isRoom())) {
            if ($changeObject->isParent()) {
                if (($change_lockable) && (!$changeObject->isLockable()))
                    $msg->addMsg(29, array($PHP_SELF, $changeObject->getId(), $PHP_SELF));
                elseif ((!$change_lockable) && ($changeObject->isLockable()))
                    $msg->addMsg(30, array($PHP_SELF, $changeObject->getId(), $PHP_SELF));
            }
            $changeObject->setLockable($change_lockable);
        }

        //Object speichern
        if (($changeObject->store()) || ($perms_changed))
            $msg->addMsg(8);
    } else {
        $msg->addMsg(1);
    }
    $resources_data["view"]="edit_object_perms";
    $view = $resources_data["view"];
}

//set/unset lockable for a comlete hierarchy
if (($set_lockable_recursiv) || ($unset_lockable_recursiv)) {
    if (getGlobalPerms($user->id) == "admin") {
        changeLockableRecursiv($lock_resource_id, ($set_lockable_recursiv) ? TRUE : FALSE);
    } else {
        $msg->addMsg(1);
    }
    $resources_data["view"]="edit_object_perms";
    $view = $resources_data["view"];
}

//Typen bearbeiten
if (($add_type) || ($delete_type) || ($delete_type_property_id) || ($change_categories)) {
    if (getGlobalPerms ($user->id) == "admin") { //check for resources root or global root
        if ($delete_type) {
            $db->query("DELETE FROM resources_categories WHERE category_id ='$delete_type'");
        }

        if (($add_type) && ($_add_type_x)) {
            $id=md5(uniqid("Sommer2002",1));
            if ($resource_is_room)
                $resource_is_room = 1;
            $db->query("INSERT INTO resources_categories SET category_id='$id', name='$add_type', description='$insert_type_description', is_room='$resource_is_room' ");
            if ($db->affected_rows())
                $created_category_id=$id;
        }

        if ($delete_type_property_id) {
            $db->query("DELETE FROM resources_categories_properties WHERE category_id='$delete_type_category_id' AND property_id='$delete_type_property_id' ");
        }

        if (is_array($change_category_name)) foreach ($change_category_name as $key=>$val) {
            $query = sprintf ("UPDATE  resources_categories SET name='%s', iconnr='%s' WHERE category_id = '%s'", $change_category_name[$key], $change_category_iconnr[$key], $key);
            $db->query($query);

            if (${"change_category_add_property".$key."_x"}) {
                $db->query("INSERT INTO resources_categories_properties SET category_id='$key', property_id='$add_type_property_id[$key]' ");
            }
        }

        if (is_array($requestable)) {
            foreach ($requestable as $key=>$val) {
                if ((strpos($requestable[$key-1], "id1_")) &&  (strpos($requestable[$key], "id2_"))) {
                    if ($requestable[$key+1] == "on")
                        $req_num = 1;
                    else
                        $req_num = 0;
                    $query = sprintf ("UPDATE resources_categories_properties SET requestable ='%s' WHERE category_id = '%s' AND property_id = '%s' ", $req_num, substr($requestable[$key-1], 5, strlen($requestable[$key-1])), substr($requestable[$key], 5, strlen($requestable[$key])));
                    $db->query($query);
                }
            }
        }
    } else {
        $msg->addMsg(25);
    }
}

//Eigenschaften bearbeiten
if (($add_property) || ($delete_property) || ($change_properties)) {
    if ($globalPerm == "admin") { //check for resources root or global root
        if ($delete_property) {
            $db->query("DELETE FROM resources_properties WHERE property_id ='$delete_property' ");
        }

        if ($add_property) {
            if ($add_property_type=="bool")
                $options="vorhanden";
            if ($add_property_type=="select")
                $options="Option 1;Option 2;Option 3";
            $id=md5(uniqid("Regen2002",1));
            $db->query("INSERT INTO resources_properties SET options='$options', property_id='$id', name='$add_property', description='$insert_property_description', type='$add_property_type' ");
        }

        if (is_array($change_property_name)) foreach ($change_property_name as $key=>$val) {
            if ($send_property_type[$key] == "select") {
                $tmp_options=explode (";",$send_property_select_opt[$key]);
                $options='';
                $i=0;
                if (is_array($tmp_options))
                    foreach ($tmp_options as $a) {
                        if ($i)
                            $options.=";";
                        $options.=trim($a);
                        $i++;
                    }
            } elseif ($send_property_type[$key] == "bool") {
                $options=$send_property_bool_desc[$key];
            }
            else
                $options='';

            $db->query("UPDATE resources_properties SET name='$change_property_name[$key]', options='$options', type='$send_property_type[$key]' WHERE property_id='$key' ");
        }
    } else {
        $msg->addMsg(25);
    }
}

//Globale Perms bearbeiten
if (($add_root_user) || ($delete_root_user_id)){
    if ($globalPerm == "admin") { //check for resources root or global root
        if ($reset_search_root_user_x)
            $search_string_search_root_user=FALSE;

        if (($send_search_root_user_x) && ($submit_search_root_user !="FALSE") && (!$reset_search_root_user_x))
            $db->query("INSERT resources_user_resources SET user_id='$submit_search_root_user', resource_id='all', perms='admin' ");

        if ($delete_root_user_id)
            $db->query("DELETE FROM resources_user_resources WHERE user_id='$delete_root_user_id' AND resource_id='all' ");
    } else {
        $msg->addMsg(25);
    }
}

/*****************************************************************************
change settings
/*****************************************************************************/

//change settings
if ($change_global_settings) {
    if ($globalPerm == "admin") { //check for resources root or global root
        write_config("RESOURCES_LOCKING_ACTIVE", $locking_active);
        write_config("RESOURCES_ASSIGN_LOCKING_ACTIVE", $assign_locking_active);
        write_config("RESOURCES_ALLOW_ROOM_REQUESTS", $allow_requests);
        write_config("RESOURCES_ALLOW_CREATE_ROOMS", $allow_create_resources);
        write_config("RESOURCES_INHERITANCE_PERMS_ROOMS", $inheritance_rooms);
        write_config("RESOURCES_INHERITANCE_PERMS", $inheritance);
        write_config("RESOURCES_ENABLE_ORGA_CLASSIFY", $enable_orga_classify);
        write_config("RESOURCES_ENABLE_ORGA_ADMIN_NOTICE", $enable_orga_admin_notice);
        write_config("RESOURCES_ALLOW_SINGLE_ASSIGN_PERCENTAGE", $allow_single_assign_percentage);
        write_config("RESOURCES_ALLOW_SINGLE_DATE_GROUPING", $allow_single_date_grouping);
    } else {
        $msg->addMsg(25);
    }
}

//create a lock
if ($create_lock) {
    if ($globalPerm == "admin") { //check for resources root or global root
        $id = md5(uniqid("locks",1));
        $query = sprintf("INSERT INTO resources_locks SET lock_begin = '%s', lock_end = '%s', lock_id = '%s', type= '%s' ", 0, 0, $id, $create_lock);
        $db->query($query);

        $resources_data["lock_edits"][$id] = TRUE;
    } else {
        $msg->addMsg(25);
    }
}

//edit a lock
if ($edit_lock) {
    if ($globalPerm == "admin") { //check for resources root or global root
        $resources_data["lock_edits"][$edit_lock] = TRUE;
    } else {
        $msg->addMsg(25);
    }
}

//edit locks
if (($lock_sent_x)) {
    if ($globalPerm == "admin") { //check for resources root or global root
        require_once ('lib/calendar_functions.inc.php'); //needed for extended checkdate

        foreach ($lock_id as $key=>$id) {
            $illegal_begin = FALSE;
            $illegal_end = FALSE;

            //checkdates
            if (!check_date($lock_begin_month[$key], $lock_begin_day[$key], $lock_begin_year[$key], $lock_begin_hour[$key], $lock_begin_min[$key])) {
                //$msg->addMsg(2);
                $illegal_begin=TRUE;
            } else
                $lock_begin = mktime($lock_begin_hour[$key],$lock_begin_min[$key],0,$lock_begin_month[$key], $lock_begin_day[$key], $lock_begin_year[$key]);

            if (!check_date($lock_end_month[$key], $lock_end_day[$key], $lock_end_year[$key], $lock_end_hour[$key], $lock_end_min[$key])) {
                //$msg -> addMsg(3);
                $illegal_end=TRUE;
            } else
                $lock_end = mktime($lock_end_hour[$key],$lock_end_min[$key],0,$lock_end_month[$key], $lock_end_day[$key], $lock_end_year[$key]);

            if ((!$illegal_begin) && (!$illegal_end) && ($lock_begin < $lock_end)) {
                $query = sprintf("UPDATE resources_locks SET lock_begin = '%s', lock_end = '%s' WHERE lock_id = '%s' ", $lock_begin, $lock_end, $id);
                $db->query($query);

                if ($db->affected_rows()) {
                    $msg->addMsg(27);
                    unset($resources_data["lock_edits"][$id]);
                }
            } else
                $msg->addMsg(26);
        }
    } else {
        $msg->addMsg(25);
    }
}

//kill a lock-time
if (($kill_lock)) {
    if ($globalPerm == "admin") { //check for resources root or global root
        $query = sprintf("DELETE FROM resources_locks WHERE lock_id = '%s' ", $kill_lock);
        $db->query($query);
        if ($db->affected_rows()) {
            $msg->addMsg(28);
            unset($resources_data["lock_edits"][$kill_lock]);
        }
    } else {
        $msg->addMsg(25);
    }
}

/*****************************************************************************
evaluate the commands from schedule navigator
/*****************************************************************************/

// fixed BIEST0210; ermoeglicht sofortiges zurueckblaettern im Belegungsplan;
if ($resources_data['schedule_week_offset']==null)
{
  $resources_data['schedule_week_offset']=0;
}

if ($view == "view_schedule" || $view == "openobject_schedule") {
    if ($next_week)
        $resources_data["schedule_week_offset"]++;
    if ($previous_week)
        $resources_data["schedule_week_offset"]--;
    if ($start_time) {
        $resources_data["schedule_start_time"] = $start_time;
        $resources_data["schedule_end_time"] = $resources_data["schedule_start_time"] + (7 * 24 * 60 * 60) + 59;
        $resources_data["schedule_mode"] = "graphical";
        $resources_data["schedule_week_offset"] = 0;
    }
    elseif ($navigate) {
        $resources_data["schedule_length_factor"] = $schedule_length_factor;
        $resources_data["schedule_length_unit"] = $schedule_length_unit;
        $resources_data["schedule_week_offset"] = 0;
        $resources_data["schedule_start_time"] = mktime (0,0,0,$schedule_begin_month, $schedule_begin_day, $schedule_begin_year);
        if (($start_list_x) || (($jump_x) && ($resources_data["schedule_mode"] == "list"))){
            $resources_data["schedule_mode"] = "list";
            if ($resources_data["schedule_start_time"] < 1)
                $resources_data["schedule_start_time"] = mktime (0, 0, 0, date("n", time()), date("j", time()), date("Y", time()));
            switch ($resources_data["schedule_length_unit"]) {
                case "y" :
                    $resources_data["schedule_end_time"] =mktime(23,59,59,date("n",$resources_data["schedule_start_time"]), date("j", $resources_data["schedule_start_time"]), date("Y",$resources_data["schedule_start_time"])+$resources_data["schedule_length_factor"]);
                break;
                case "m" :
                    $resources_data["schedule_end_time"] =mktime(23,59,59,date("n",$resources_data["schedule_start_time"])+$resources_data["schedule_length_factor"], date("j", $resources_data["schedule_start_time"]), date("Y",$resources_data["schedule_start_time"]));
                break;
                case "w" :
                    $resources_data["schedule_end_time"] =mktime(23,59,59,date("n",$resources_data["schedule_start_time"]), date("j", $resources_data["schedule_start_time"])+($resources_data["schedule_length_factor"] * 7), date("Y",$resources_data["schedule_start_time"]));
                break;
                case "d" :
                    $resources_data["schedule_end_time"] =mktime(23,59,59,date("n",$resources_data["schedule_start_time"]), date("j", $resources_data["schedule_start_time"])+$resources_data["schedule_length_factor"]-1, date("Y",$resources_data["schedule_start_time"]));
                break;
            }
            if ($resources_data["schedule_end_time"]  < 1)
                $resources_data["schedule_end_time"] = mktime (23, 59, 59, date("n", time()), date("j", time())+1, date("Y", time()));
        } elseif (($start_graphical_x) || (!$resources_data["schedule_mode"]) || (($jump_x) && ($resources_data["schedule_mode"] == "graphical"))) {
            $resources_data["schedule_end_time"] = $resources_data["schedule_start_time"] + (7 * 24 * 60 * 60) + 59;
            $resources_data["schedule_mode"] = "graphical";
        }
    } else {
        if (!$resources_data["schedule_start_time"])
            $resources_data["schedule_start_time"] = mktime (0, 0, 0, date("n", time()), date("j", time()), date("Y", time()));
        if (!$resources_data["schedule_end_time"])
            $resources_data["schedule_end_time"] = mktime (23, 59, 59, date("n", time()), date("j", time())+7, date("Y", time()));
        if (!$resources_data["schedule_mode"])
            $resources_data["schedule_mode"] = "graphical";
    }
}

if (($show_repeat_mode) && ($send_schedule_repeat_mode_x)) {
    $resources_data["show_repeat_mode"] = $show_repeat_mode;
}

if ($time_range) {
    if ($time_range == "FALSE")
        $resources_data["schedule_time_range"] = '';
    else
        $resources_data["schedule_time_range"] = $time_range;
}

/*****************************************************************************
handle commands from the search 'n' browse module
/*****************************************************************************/
if ($view == "search") {

    if(!isset($resources_data['search_only_rooms'])){
        $resources_data['search_only_rooms'] = 1;
    }

    if ($open_level)
         $resources_data["browse_open_level"]=$open_level;

    if ($mode == "properties")
        $resources_data["search_mode"]="properties";

    if ($mode == "browse")
        $resources_data["search_mode"]="browse";

    if ($check_assigns == "TRUE")
        $resources_data["check_assigns"]=TRUE;

    if ($check_assigns == "FALSE")
        $resources_data["check_assigns"]=FALSE;
    if (isset($_REQUEST['search_only_rooms']))
        $resources_data["search_only_rooms"] = $_REQUEST['search_only_rooms'];

    if ((isset($start_search_x)) || ($search_send)) {
        unset($resources_data["search_array"]);
        $resources_data["search_array"]["search_exp"] = Request::quoted('search_exp');

        $resources_data["search_array"]["resources_search_range"]=$resources_data["browse_open_level"]=$_REQUEST['resources_search_range'];
        if (is_array($search_property_val))
            foreach ($search_property_val as $key=>$val) {
                if ((substr($val, 0, 4) == "_id_") && (substr($search_property_val[$key+1], 0, 4) != "_id_") && ($search_property_val[$key+1]))
                    $resources_data["search_array"]["properties"][substr($val, 4, strlen($val))]=$search_property_val[$key+1];
        }

        //handle dates for searching resources that are free for this times
        /// >> changed for advanced room searches
        if ($search_day_of_week!=-1) // a day is selected. this indicates the user searches a room for the whole term
        {
            /// search whole term
            $semesterData = new SemesterData();
            $sel_semester = $semesterData->getSemesterData($search_semester);
            $date =  (int)$sel_semester["vorles_beginn"];

            $days = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');

            $beginn_day = date('w', $date);

            $target_day = array_search($search_day_of_week, $days);

            $diff = $target_day - $beginn_day;

            // wrap around
            if ($diff < 0)
            {
                $diff = 7 + $diff;
            }

            //$date = strtotime($str = "this $search_day_of_week 12:00:00", $date);
            $date= strtotime($str = "+ $diff days", $date);

            check_and_set_date (date("d",$date), date("m",$date), date("Y",$date), $search_begin_hour_2, $search_begin_minute_2, $resources_data["search_array"], "search_assign_begin");
            check_and_set_date (date("d",$date), date("m",$date), date("Y",$date), $search_end_hour_2, $search_end_minute_2, $resources_data["search_array"], "search_assign_end");
            $resources_data["search_array"]["search_repeating"] = '1';
        } else
        {
            check_and_set_date ($search_day, $search_month, $search_year, $search_begin_hour, $search_begin_minute, $resources_data["search_array"], "search_assign_begin");
            check_and_set_date ($search_day, $search_month, $search_year, $search_end_hour, $search_end_minute, $resources_data["search_array"], "search_assign_end");
            $resources_data["search_array"]["search_repeating"] = $search_repeating;
        }
                $resources_data["search_array"]["search_day_of_week"] = $search_day_of_week;
                $resources_data["search_array"]["search_semester"] = $search_semester;
        /// << changed for advanced room searches
        if (($resources_data["search_array"]["search_assign_begin"] == -1) || ($resources_data["search_array"]["search_assign_end"] == -1)) {
            $resources_data["search_array"]["search_assign_begin"] = 0;
            $resources_data["search_array"]["search_assign_end"] = 0;
        }
    }

    if ($reset) {
        unset($resources_data["browse_open_level"]);
        unset($resources_data["search_array"]);
    }
}

/*****************************************************************************
the room-planning module
/*****************************************************************************/
if(isset($_REQUEST['tools_requests_sem_choose_button_x']) || isset($_REQUEST['tools_requests_sem_choose'])){
    $resources_data["sem_schedule_semester_id"] = $_REQUEST['tools_requests_sem_choose'];
    $resources_data["resolve_requests_no_time"] = (bool)$_REQUEST['resolve_requests_no_time'];
    unset($resources_data["requests_working_on"]);
    unset($resources_data["requests_open"]);
    $resources_data["view"] = "requests_start";
    $view = "requests_start";
}

if ($view == "view_requests_schedule") {
    if ($_REQUEST['next_week'])
        $resources_data["schedule_week_offset"]++;
    elseif ($_REQUEST['previous_week'])
        $resources_data["schedule_week_offset"]--;
    elseif($_REQUEST["show_repeat_mode_requests"])
        $resources_data["show_repeat_mode_requests"] = $_REQUEST["show_repeat_mode_requests"];
    elseif ($_REQUEST['start_time']) {
        $resources_data["schedule_start_time"] = $_REQUEST['start_time'];
        $resources_data["schedule_end_time"] = $resources_data["schedule_start_time"] + (7 * 24 * 60 * 60) + 59;
        $resources_data["schedule_mode"] = "graphical";
        $resources_data["schedule_week_offset"] = 0;
    }
    elseif ($_REQUEST['navigate']) {
        $resources_data["schedule_week_offset"] = 0;
        $resources_data["schedule_start_time"] = mktime (0,0,0,(int)$_REQUEST['schedule_begin_month'], (int)$_REQUEST['schedule_begin_day'], (int)$_REQUEST['schedule_begin_year']);
    } else {
        if($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]['first_event']){
            $resources_data["schedule_start_time"] = $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]['first_event'];
        } else {
            $semester_data = SemesterData::GetInstance()->getSemesterData($resources_data["sem_schedule_semester_id"]);
            $resources_data["schedule_start_time"] = $semester_data['vorles_beginn'];
        }
        $resources_data["schedule_mode"] = "graphical";
        $resources_data["show_repeat_mode_requests"] = 'all';
        $resources_data["schedule_week_offset"] = 0;
        $resources_data['sem_schedule_timespan'] = 'course_time';
    }
}
switch ($skip_closed_requests) {
    case "FALSE" : $resources_data["skip_closed_requests"] = FALSE; break;
    case "TRUE" : $resources_data["skip_closed_requests"] = TRUE; break;
}

//cancel an edit request session
if ($cancel_edit_request_x) {
    if (sizeof($resources_data["requests_open"]) < sizeof ($resources_data["requests_working_on"])) {
        foreach ($resources_data["requests_working_on"] as $val) {
            $request_ids[] = $val["request_id"];
            $request_data[$val["request_id"]] = $val;
        }
        $in="('".join("','",$request_ids)."')";

        $query = sprintf ("SELECT request_id FROM resources_requests WHERE closed='1' AND request_id IN %s", $in);
        $db->query($query);

        if ($db->nf()) {
            $msg->addMsg(40, array($PHP_SELF, $PHP_SELF));
            $save_state_x = FALSE;
        }
    }
    $resources_data["view"] = "requests_start";
    $view = "requests_start";
}

//we start a new room-planning-session
if (($start_multiple_mode_x) || ($single_request)) {
    unset($resources_data["requests_working_on"]);
    unset($resources_data["requests_open"]);

    $requests = (array)getMyRoomRequests($GLOBALS['user']->id, $resources_data["sem_schedule_semester_id"], true, $single_request);

    $resources_data["requests_working_pos"] = 0;
    $resources_data["skip_closed_requests"] = TRUE;
    if($_REQUEST['resolve_requests_mode'] == "one_res"){
        $resources_data['resolve_requests_one_res'] = $_REQUEST['resolve_requests_one_res'];
    } else {
        $resources_data['resolve_requests_one_res'] = null;
    }
    $selected_requests = array();
    //filter the requests
    foreach($requests as $key => $val) {
        if (!$val["closed"] && !($resolve_requests_no_time && !$val['have_times'])) {
            if ($resolve_requests_mode == "sem") {
                if ($val["my_sem"])
                    $selected_requests[$key] = TRUE;
            } elseif ($resolve_requests_mode == "res") {
                if ($val["my_res"])
                    $selected_requests[$key] = TRUE;
            } elseif ($_REQUEST['resolve_requests_mode'] == "one_res") {
                if ($val["resource_id"] == $_REQUEST['resolve_requests_one_res'])
                    $selected_requests[$key] = TRUE;
            } else {
                $selected_requests[$key] = TRUE;
            }
        }
    }

    if ($single_request) {
        if ($selected_requests[$single_request]) {
            $resources_data["requests_working_on"][] = array("request_id" => $single_request, "closed" => FALSE);
            $resources_data["requests_open"][$single_request] = TRUE;
            if($requests[$single_request]['resource_id']){
                $resources_data['resolve_requests_one_res'] = $requests[$single_request]['resource_id'];
            }
        }
    } elseif (is_array($selected_requests)) {
        //order requests
        $order = '';
        $in =  "('".join("','",array_keys($selected_requests))."')";
        if ($resolve_requests_order == "complex")
            $order = "seats DESC, complexity DESC";
        if ($resolve_requests_order == "newest")
            $order = "a.mkdate DESC";
        if ($resolve_requests_order == "oldest")
            $order = "a.mkdate ASC";

        // for sort-order urgent a simpler query suffices
        if ($resolve_requests_order == "urgent") {
            $stmt = DBManager::get()->query("SELECT rq.request_id, rq.seminar_id, rq.termin_id FROM resources_requests as rq, termine as t
                WHERE rq.request_id IN $in AND t.date > ". time() ." AND (
                    (t.range_id = rq.seminar_id AND (rq.termin_id IS NULL OR rq.termin_id = ''))
                    OR ( rq.termin_id IS NOT NULL AND rq.termin_id != '' AND rq.termin_id = t.termin_id)
                )
                ORDER BY t.date ASC");
            while ($data = $stmt->fetch()) {
                if (!$db_requests[$data['request_id']]) {
                    $db_requests[$data['request_id']] = array(
                        'request_id' => $data['request_id'],
                        'termin_id'  => $data['termin_id'],
                        'seminar_id' => $data['seminar_id']
                    );
                }
            }
        } else {
            $query = sprintf ("SELECT a.seminar_id, a.termin_id, a.request_id, a.resource_id, COUNT(b.property_id) AS complexity, MAX(d.state) AS seats
                FROM resources_requests a
                LEFT JOIN resources_requests_properties b USING (request_id)
                LEFT JOIN resources_properties c ON (b.property_id = c.property_id AND c.system = 2)
                LEFT JOIN resources_requests_properties d ON (c.property_id = d.property_id AND a.request_id = d.request_id)
                WHERE a.request_id IN %s
                GROUP BY a.request_id
                ORDER BY %s", $in, $order);
            $db->query($query);

            while ($db->next_record()) {
                $db_requests[] = array('request_id' => $db->f('request_id'), 'termin_id' => $db->f('termin_id'), 'seminar_id' => $db->f('seminar_id'));
            }

        }

        // insert sorted requests into resources_data-Array
        if (is_array($db_requests)) foreach ($db_requests as $val) {
            $resources_data["requests_working_on"][] = array("request_id" => $val['request_id'], "closed" => FALSE);
            $resources_data["requests_open"][$val['request_id']] = TRUE;
        }

    }
    if (is_array($resources_data['requests_open']) && count($resources_data['requests_open'])){
        $resources_data["view"] = "edit_request";
        $view = $resources_data["view"];
        $new_session_started = TRUE;
    } else {
        $resources_data["view"] = $view = "requests_start";
        $msg->addMsg(41);
    }
}

if (is_array($selected_resource_id)) {
    foreach ($selected_resource_id as $key=>$val) {
        $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["selected_resources"][$key] = $val;
    }
}

// save the assigments in db
if ($save_state_x) {
    require_once ($RELATIVE_PATH_RESOURCES."/lib/RoomRequest.class.php");
    require_once ($RELATIVE_PATH_RESOURCES."/lib/VeranstaltungResourcesAssign.class.php");
    require_once ("lib/classes/Seminar.class.php");

    $reqObj = new RoomRequest($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["request_id"]);
    $semObj = Seminar::GetInstance($reqObj->getSeminarId());
    $semResAssign = new VeranstaltungResourcesAssign($semObj->getId());

    //if not single date-mode, we have to load all termin_ids from other requests of this seminar, because these dates don't have to be touched (they have an own request!)
    if (!$reqObj->getTerminId()) {
        $query = sprintf ("SELECT rr.termin_id, closed, date, end_time FROM resources_requests rr LEFT JOIN termine USING (termin_id) WHERE seminar_id = '%s' AND rr.termin_id != '' ", $reqObj->getSeminarId());
        $db->query($query);
        while ($db->next_record()) {
            $dates_with_request[$db->f("termin_id")] = array("closed"=>$db->f("closed"), "begin" => $db->f("date"), "end" => $db->f("end_time"));
        }
    }

    //single date mode - just create one assign-object
    if ($reqObj->getTerminId()) {
        $dateRequest = TRUE;
        $assignObjects[] = $semResAssign->getDateAssignObject($reqObj->getTerminId());
    }

    //multiple assign_objects (every date one assign-object or every metadate one assign-object)
    elseif (is_array ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["assign_objects"])) {
        $i=0;
        //check, if one assignment should assigned to a room, which is only particularly free - so we have treat every single date
        if ($reqObj->getType() == 'cycle' ) {
            $assignObjects = $semResAssign->getMetaDateAssignObjects($reqObj->getMetadateId());
        } else if ($reqObj->getType() == 'course' ) {
            $assignObjects = $semResAssign->getDateAssignObjects(TRUE);
        }
    }

    //get the selected resources, save this informations and create the right msgs
    if (is_array($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["selected_resources"])) {
        //check all selected resources for perms
        foreach ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["selected_resources"] as $key=>$val) {
            $resPerms = ResourceObjectPerms::Factory($val);
            if (!$resPerms->havePerm("autor"))
                $no_perm = TRUE;
            $resPerms ='';
        }

        if ($no_perm)
            $msg->addMsg(25);
        else {
            // avoid warnings due to undefined result
            $result = array();

            //single date mode
            if ($reqObj->getTerminId()) {
                reset($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["selected_resources"]);
                list(,$res_id) = each($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["selected_resources"]);
                $result = $semResAssign->changeDateAssign($reqObj->getTerminId(), $res_id);

            //grouped multiple dates mode
            } else {
                foreach ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["selected_resources"] as $key=>$val) {
                    $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["groups"][$key]["resource_id"] = $val;
                    foreach ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["groups"][$key]["termin_ids"] as $key2 => $val2) {
                        if (!$dates_with_request[$key2]) {
                            $result = array_merge((array)$result, (array)$semResAssign->changeDateAssign($key2, $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["selected_resources"][$key]));
                            $result_termin_id[] = $key2;
                        } else
                            $skipped_termin_ids[$key2]=TRUE;
                    }
                }

                // only close request, if number of booked entries matches number of bookable entries
                $booked_entries = sizeof($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["selected_resources"]);
                $bookable_entries = sizeof($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]['groups']);

                if ($booked_entries == $bookable_entries ) {
                    $close_request = TRUE;
                }

                $semObj->store();
            //normal metadate mode
            }

            //---------------------------------------------- second part, msgs and some other operations

            $succesful_assigned = 0;
            //create msgs, single date mode
            if ($reqObj->getTerminId()) {
                $assign_ids = array_keys($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["assign_objects"]);
                $resObj = ResourceObject::Factory($res_id);
                foreach ($result as $key=>$val) {
                    if (!$val["overlap_assigns"]) {
                        $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["assign_objects"][$assign_ids[0]]["resource_id"] = $resObj->getId();
                        $good_msg.="<br>".sprintf(_("%s, Belegungszeit: %s"), $resObj->getFormattedLink( $assignObjects[0]->getBegin() ), $assignObjects[0]->getFormattedShortInfo());
                        $succesful_assigned++;
                    } else {
                        $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["assign_objects"][$assign_ids[0]]["resource_id"] = FALSE;
                        $bad_msg.="<br>".sprintf(_("%s, Belegungszeit: %s"), $resObj->getFormattedLink( $assignObjects[0]->getBegin() ), $assignObjects[0]->getFormattedShortInfo());
                    }
                }

            //create msgs, grouped multi date mode
            } else {
                $i=0;
                foreach ($result as $key=>$val) {
                    $resObj = ResourceObject::Factory($val["resource_id"]);
                    if (!$val["overlap_assigns"]) {
                        $good_msg.="<br>".sprintf(_("%s, Belegungszeit: %s"), $resObj->getFormattedLink( $assignObjects[$result_termin_id[$i]]->getBegin() ), $assignObjects[$result_termin_id[$i]]->getFormattedShortInfo());
                    } else {
                        $req_added_msg.="<br>".sprintf(_("%s, Belegungszeit: %s"), $resObj->getFormattedLink( $assignObjects[$result_termin_id[$i]]->getBegin() ), $assignObjects[$result_termin_id[$i]]->getFormattedShortInfo());
                        $copyReqObj = clone $reqObj;
                        $copyReqObj->copy();
                        $copyReqObj->setTerminId($val["termin_id"]);
                        $copyReqObj->store();
                    }
                    $i++;
                }

            }

            //create additional msgs for skipped dates (this ones have got an own request, so the generel request doesn't affect them)
            $skipped_objects = 0;
            if ($skipped_termin_ids) {
                foreach ($skipped_termin_ids as $key=>$val) {
                    $skipped_msg="<br>"._("Belegungszeit:")."&nbsp;".date("d.m.Y, H:i", $dates_with_request[$key]["begin"]).(($dates_with_request[$key]["end"]) ? " - ".date("H:i", $dates_with_request[$key]["end"]) : "");
                    $skipped_msg.=sprintf("&nbsp;"._("Status:")."&nbsp;<font color=\"%s\">%s</font>", ($dates_with_request[$key]["closed"] == 0) ? "red" : "green", ($dates_with_request[$key]["closed"] == 0) ? _("noch nicht bearbeitet") : _("bereits bearbeitet"));
                    $skipped_objects++;
                }
            }

            //set reload flag for this request (the next time the user skips to the request, we reload all data)
            $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["reload"] = TRUE;

            //create msg's
            if ($good_msg) {
                $GLOBALS['messageForUsers'] = $good_msg;
                $msg->addMsg(33, array($good_msg));
            }
            if ($bad_msg)
                $msg->addMsg(34, array($bad_msg));
            if ($req_added_msg)
                $msg->addMsg(35, array($req_added_msg));
            if ($skipped_msg)
                $msg->addMsg(42, array($skipped_msg));

        }
    }

    //set request to closed, if we have a room for every assign_object
    $assigned_resources = 0;
    foreach ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["assign_objects"] as $val) {
        if ($val["resource_id"])
            $assigned_resources++;
    }

    if ((($assigned_resources + $skipped_objects) == sizeof($assignObjects)) || ($close_request)) {
        $_sendMessage['request_id'] = $reqObj->id;
        $_sendMessage['seminar_id'] = $reqObj->seminar_id;
        $_sendMessage['type'] = 'closed';

        $reqObj->setClosed(1);
        $reqObj->store();
        unset($resources_data["requests_open"][$reqObj->getId()]);
        if (sizeof($resources_data["requests_open"]) == 0) {
            $resources_data["view"] = "requests_start";
            $view = "requests_start";
            //$msg->addMsg(36, array($PHP_SELF, $PHP_SELF));
            $save_state_x = FALSE;
        } else  {
            if ($resources_data["requests_working_pos"] == sizeof($resources_data["requests_working_on"])-1) {
                $auto_dec = TRUE;
            } else {
                $auto_inc = TRUE;
            }
        }
    }
}

if (isset($_REQUEST['do_delete_requests_x']) && get_config('RESOURCES_ALLOW_DELETE_REQUESTS') && getGlobalPerms($GLOBALS['user']->id) == 'admin'){
    if (is_array($_REQUEST['requests_marked_to_kill'])){
        foreach($_REQUEST['requests_marked_to_kill'] as $rid){
            $req_obj = new RoomRequest($rid);
            $count += ($req_obj->delete() != 0);
            unset($resources_data["requests_open"][$rid]);
            foreach($resources_data['requests_working_on'] as $number => $rwo){
                if ($rwo['request_id'] == $rid){
                    unset($resources_data['requests_working_on'][$number]);
                    break;
                }
            }
        }
        $resources_data['requests_working_pos'] = 0;
        $resources_data['requests_working_on'] = array_values($resources_data['requests_working_on']);
        $msg->addMsg(45, array($count));
    }
}

if ($suppose_decline_request_x) {
    $msg->addMsg(43, array($PHP_SELF, $PHP_SELF));
    $view = "edit_request";
}

if ($decline_request) {
    require_once ($RELATIVE_PATH_RESOURCES."/lib/RoomRequest.class.php");

    $reqObj = new RoomRequest($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["request_id"]);

    $_sendMessage['request_id'] = $reqObj->id;
    $_sendMessage['seminar_id'] = $reqObj->seminar_id;
    $_sendMessage['type'] = 'declined';

    $reqObj->setClosed(3);
    $reqObj->store();
    unset($resources_data["requests_open"][$reqObj->getId()]);
    if (sizeof($resources_data["requests_open"]) == 0) {
        $resources_data["view"] = "requests_start";
        $view = "requests_start";

    } else  {
        if ($resources_data["requests_working_pos"] == sizeof($resources_data["requests_working_on"])-1) {
            $auto_dec = TRUE;
        } else {
            $auto_inc = TRUE;
        }
    }
}
if ($delete_request_x || $_REQUEST['approveDelete']) {
        if(!$_REQUEST['approveDelete']){
            $approval=array('approveDelete' => TRUE);
            echo createQuestion(_("Wollen Sie diese Raumanfrage wirklich löschen?"), $approval);
        }

    if($_REQUEST['approveDelete']){
            require_once ($RELATIVE_PATH_RESOURCES."/lib/RoomRequest.class.php");
            $reqObj = new RoomRequest($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["request_id"]);//$_REQUEST['request_id']);
            unset($resources_data["requests_open"][$reqObj->getId()]);
            $reqObj->delete();
            $resources_data['requests_working_pos'] = 0;
            $resources_data['requests_working_on'] = array_values($resources_data['requests_working_on']);
            unset($_REQUEST['approveDelete']);
                if (sizeof($resources_data["requests_open"]) == 0) {
                    $resources_data["view"] = "requests_start";
                    $view = "requests_start";
            } else  {
                    if ($resources_data["requests_working_pos"] == sizeof($resources_data["requests_working_on"])-1) {
                            $auto_dec = TRUE;
                    } else {
                            $auto_inc = TRUE;
                    }
            }

        }
}

// inc if we have requests left in the upper
if (($inc_request_x) || ($auto_inc))
    if ($resources_data["requests_working_pos"] < sizeof($resources_data["requests_working_on"])-1) {
        $i = 1;
        if ($resources_data["skip_closed_requests"])
            while ((!$resources_data["requests_open"][$resources_data["requests_working_on"][$resources_data["requests_working_pos"] + $i]["request_id"]]) && ($resources_data["requests_working_pos"] + $i < sizeof($resources_data["requests_open"])-1))
                $i++;
        if ((sizeof($resources_data["requests_open"]) >= 1) && (($resources_data["requests_open"][$resources_data["requests_working_on"][$resources_data["requests_working_pos"] + $i]["request_id"]]) || (!$resources_data["skip_closed_requests"]))){
            $resources_data["requests_working_pos"] = $resources_data["requests_working_pos"] + $i;
        } elseif ($auto_inc)
            $dec_request_x = TRUE; //we cannot inc - so we are at the end and want to find an request below, so try do dec.
    }

// dec if we have requests left in the lower
if (($dec_request_x) || ($auto_dec))
    if ($resources_data["requests_working_pos"] > 0) {
        $d = -1;
        if ($resources_data["skip_closed_requests"])
            while ((!$resources_data["requests_open"][$resources_data["requests_working_on"][$resources_data["requests_working_pos"] + $d]["request_id"]]) && ($resources_data["requests_working_pos"] + $d > 0))
                $d--;
        if ((sizeof($resources_data["requests_open"]) >= 1) && (($resources_data["requests_open"][$resources_data["requests_working_on"][$resources_data["requests_working_pos"] + $d]["request_id"]]) || (!$resources_data["skip_closed_requests"]))) {
            $resources_data["requests_working_pos"] = $resources_data["requests_working_pos"] + $d;
        }
    }

//inc/dec the limits of found rooms
if ($inc_limit_low) {
    $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["search_limit_low"]+=10;
    $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["reload"] = TRUE;
    if ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["search_limit_low"] >= $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["search_limit_high"])
        $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["search_limit_low"] = $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["search_limit_high"] -1;
}
if ($inc_limit_high) {
    $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["search_limit_high"]+=10;
    $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["reload"] = TRUE;
}

if ($dec_limit_low) {
    $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["search_limit_low"]-=10;
    $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["reload"] = TRUE;
    if ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["search_limit_low"] < 0)
        $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["search_limit_low"] = 0;
}
if ($dec_limit_high) {
    $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["search_limit_high"]-=10;
    $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["reload"] = TRUE;
    if ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["search_limit_high"] <= $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["search_limit_low"])
        $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["search_limit_high"] = $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["search_limit_low"] +1;
}
if ($matching_rooms_limit_submit_x) {
    $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["search_limit_low"] = $search_rooms_limit_low - 1;
    $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["search_limit_high"] = $search_rooms_limit_high ;
    $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["reload"] = TRUE;
}

if (isset($_REQUEST['request_tool_group_x'])){
    if ($_REQUEST['request_tool_choose_group'] == '-'){
        $resources_data['actual_room_group'] = null;
    } else {
        $resources_data['actual_room_group'] = $_REQUEST['request_tool_choose_group'];
    }
    $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["reload"] = TRUE;
}


//create the (overlap)data for all resources that should checked for a request
if (($inc_request_x) || ($dec_request_x) || ($new_session_started) || ($marked_clip_ids) || ($save_state_x) || $auto_inc || $auto_dec || $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["reload"]) {
    require_once ($RELATIVE_PATH_RESOURCES."/lib/RoomRequest.class.php");
    require_once ($RELATIVE_PATH_RESOURCES."/lib/CheckMultipleOverlaps.class.php");
    require_once ($RELATIVE_PATH_RESOURCES."/lib/VeranstaltungResourcesAssign.class.php");
    require_once ($RELATIVE_PATH_RESOURCES."/lib/ResourcesUserRoomsList.class.php");
    require_once ("lib/classes/Seminar.class.php");
    require_once ("lib/classes/SemesterData.class.php");

    if ((!is_array($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["detected_overlaps"])) || ($marked_clip_ids) || ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["reload"])) {
        unset ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["reload"]);
        if (!isset($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["search_limit_low"])) {
            $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["search_limit_low"] = 0;
            $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["search_limit_high"] = 10;
        }
        $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["considered_resources"] = array();

        $semester = new SemesterData;
        $all_semester = $semester->getAllSemesterData();

        $reqObj = new RoomRequest($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["request_id"]);
        $semObj = Seminar::GetInstance($reqObj->getSeminarId(), true);
        $multiOverlaps = new CheckMultipleOverlaps;
        $semResAssign = new VeranstaltungResourcesAssign($semObj->getId());

        //add the requested ressource to selection
        if ($reqObj->getResourceId())
            $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["considered_resources"][$reqObj->getResourceId()] = array("type"=>"requested");

        //add resource_ids from room groups
        if (get_config('RESOURCES_ENABLE_GROUPING')){
            $room_group = RoomGroups::GetInstance();
            $group_id = $resources_data['actual_room_group'];
            if ($room_group->getGroupCount($group_id)){
                foreach ($room_group->getGroupContent($group_id) as $val) {
                    $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["considered_resources"][$val] = array("type"=>"grouped");
                }
            }
        }

        //add the matching ressources to selection
        if (getGlobalPerms($user->id) != "admin")
            $resList = new ResourcesUserRoomsList ($user->id, FALSE, FALSE);
        $matching_resources = $reqObj->searchRooms(FALSE, TRUE, $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["search_limit_low"], $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["search_limit_high"], TRUE, (is_object($resList)) ? array_keys($resList->getRooms()) : FALSE);
        if ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["search_limit_high"]  > ($reqObj->last_search_result_count + $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["search_limit_low"]))
            $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["search_limit_high"] = $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["search_limit_low"] + $reqObj->last_search_result_count;

        foreach ($matching_resources as $key => $val) {
            if (!$resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["considered_resources"][$key])
                $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["considered_resources"][$key] = array("type"=>"matching");
        }

        //add resource_ids from clipboard
        if (is_array($marked_clip_ids))
            foreach ($marked_clip_ids as $val)
                if (!$resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["considered_resources"][$val])
                    $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["considered_resources"][$val] = array("type"=>"clipped");


        //create the assign-objects for the seminar (virtual!)
        $assignObjects = array();
        if ($reqObj->getType() == 'date') {
            $assignObjects[] = $semResAssign->getDateAssignObject($reqObj->getTerminId());
        } else if ($reqObj->getType() == 'cycle' ) {
            $assignObjects = $semResAssign->getMetaDateAssignObjects($reqObj->getMetadateId());
        } else if ($reqObj->getType() == 'course' ) {
            $assignObjects = $semResAssign->getDateAssignObjects(TRUE);
        }

        $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["assign_objects"]=array();
        $current_time = (time()-3600);
        if ($GLOBALS['RESOURCES_HIDE_PAST_SINGLE_DATES']) {
            $new_assign_objects = Array();
            if (!$reqObj->getTerminId()) {
                foreach ($assignObjects as $assObj) {
                    if (  $assObj->begin < $current_time
                            && $assObj->end < $current_time )
                    {
                        continue;
                    }
                    $new_assign_objects [] = $assObj;
                }
                $assignObjects = $new_assign_objects;
            }
        }

        if (is_array($assignObjects) && sizeof($assignObjects) > 0) {

            //add already assigned resource_ids to the check-set and remember those assigns
            foreach($assignObjects as $assObj){
                if ($assObj->getResourceId()){
                    if(!$resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["considered_resources"][$assObj->getResourceId()])
                        $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["considered_resources"][$assObj->getResourceId()] = array("type"=>"matching");
                }
                $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["assign_objects"][$assObj->getId()] = array("resource_id" => $assObj->getResourceId());
            }

            //set the time range to check;
                $multiOverlaps->setAutoTimeRange($assignObjects);

            //add the considered resources to the check-set
            if (is_array($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["considered_resources"])) {
                foreach ($resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["considered_resources"] as $key => $val) {
                    $multiOverlaps->addResource($key);
                }
            }


            //do checks
            $result = array();
            $first_event = FALSE;

            /* * * * * * * * * * * * * *
             * * * Group the dates * * *
             * * * * * * * * * * * * * */

            $groupedDates = $semObj->getGroupedDates($reqObj->getTerminId(),$reqObj->getMetadateId());
            $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["groups"] = $groupedDates['groups'];

            //gruppierte Termine durchlaufen
            foreach($groupedDates['groups'] as $group_id => $group) {
                $events = array();
                foreach ($assignObjects as $assObj) {
                    if ($group['termin_ids'][$assObj->getAssignUserId()]) {
                        foreach ($assObj->getEvents() as $evtObj) {

                            $events[$evtObj->getId()] = $evtObj;
                            if (($evtObj->getBegin() < $first_event) || (!$first_event))
                                $first_event = $evtObj->getBegin();
                        }
                    }
                }

                $multiOverlaps->checkOverlap($events, $tmp_result, "assign_user_id");

                $overlaps = array();
                if (is_array($tmp_result)) {
                    foreach ($tmp_result as $room => $data) {
                        $tmp_overlap_count = 0;
                        foreach($data as $ovl_termin_id => $ovl_termine) {
                            $result[$room][$ovl_termin_id] = $ovl_termine;
                            foreach ($ovl_termine as $termin) {
                                foreach($events as $an_event) {
                                    if ( ($termin['begin'] >= $an_event->begin && $termin['begin'] <= $an_event->end)
                                    ||  ($termin['begin'] <= $an_event->begin && $termin['end'] > $an_event->begin) ) {
                                        $tmp_overlap_count++;
                                    }
                                }
                            }
                        }
                        $overlaps[$room] = $tmp_overlap_count;
                    }
                }

                $event_zw =  $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["groups"][$group_id]["termin_ids"];
            $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["groups"][$group_id]["events_count"] = sizeof($event_zw);
            $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["groups"][$group_id]["overlap_events_count"] = $overlaps;

                // Der gebuchte Raum, soweit vorhanden => fuer den gruenen Haken
                if ($groupedDates['info'][$group_id]['raum']) {
                    $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["groups"][$group_id]["resource_id"] = $groupedDates['info'][$group_id]['raum'];
                }

            }  // Ende: gruppierte Termine durchlaufen

        $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["detected_overlaps"] = $result;
        $resources_data["requests_working_on"][$resources_data["requests_working_pos"]]["first_event"] = $first_event;
        }
    }
}

//inform the owner of the requests
//if ($snd_closed_request_sms) {
if ($_sendMessage) {
    require_once ($GLOBALS['RELATIVE_PATH_RESOURCES'].'/lib/RoomRequest.class.php');
    require_once ('lib/classes/Seminar.class.php');
    require_once ('lib/messaging.inc.php');
    require_once ('lib/language.inc.php');


    $messaging = new messaging;

    foreach ($resources_data["requests_working_on"] as $val) {
        //$request_ids[] = $val["request_id"];
        $request_data[$val["request_id"]] = $val;
    }

    $reqObj = new RoomRequest($_sendMessage['request_id']);
    $semObj = new Seminar($_sendMessage['seminar_id']);

    // first we have to get all users to which the message will be sent
    // -> creator of request

    $users = Array($reqObj->getUserId());

    // the room-request has been declined
    if ($_sendMessage['type'] == 'declined') {
        $decline_message = remove_magic_quotes($_REQUEST['decline_message']);
        if ($semObj->seminar_number) {
            $message = sprintf(_("ABGELEHNTE RAUMANFRAGE: Ihre Raumanfrage zur Veranstaltung %s (%s) wurde abgelehnt.") . "\n\n" .
                _("Nachricht des Raumadministrators:") . "\n" . $decline_message, $semObj->getName(), $semObj->seminar_number);
        } else {
            $message = sprintf(_("ABGELEHNTE RAUMANFRAGE: Ihre Raumanfrage zur Veranstaltung %s wurde abgelehnt.") . "\n\n" .
                _("Nachricht des Raumadministrators:") . "\n" . $decline_message, $semObj->getName());
        }

        if ($reqObj->getTerminId()) {
            $termin = new SingleDate($reqObj->getTerminId());
            $message .= "\n\n". _("Betroffener Termin:") . "\n" . $termin->toString();
        }

        if ($reqObj->getMetadateId()) {
            $cycle = SeminarCycleDate::find($reqObj->getMetadateId());
            $message .= "\n\n". _("Betroffene Zeit:") . "\n" . $cycle->toString('full');
        }
        // fetch the names of the lecutrers to display them in the message
        foreach($semObj->getMembers('dozent') as $dozenten){
            $title[] = $dozenten['Nachname'];
        }
        if($semObj->seminar_number) $title[] = $semObj->seminar_number;
        $title[] = mila($semObj->getName(),30);

        $reqObj->setReplyComment($decline_message);
        $reqObj->store();
        foreach ($users as $userid) {
            setTempLanguage($userid);
            $messaging->insert_message(addslashes($message), get_username($userid), $user->id, FALSE, FALSE, FALSE, FALSE,
                addslashes(_("Raumanfrage abgelehnt:") .' '. implode(', ', $title)), TRUE, 'high');
            restoreLanguage();
        }
    }

    // the room-request has been resolved
    else {

        // create appropriate message
        if ($semObj->seminar_number)
            $message = sprintf (_("Ihre Raumanfrage zur Veranstaltung %s (%s) wurde bearbeitet.")." \n"._("Für folgende Belegungszeiten wurde der jeweils angegebene Raum gebucht:")."\n\n", $semObj->getName(), $semObj->seminar_number);
        else
            $message = sprintf (_("Ihre Raumanfrage zur Veranstaltung %s wurde bearbeitet.")." \n"._("Für folgende Belegungszeiten wurde der jeweils angegebene Raum gebucht:")."\n\n", $semObj->getName());

        //send the message into stud.ip message system
        // only if there are assigned dates
        if ($GLOBALS['messageForUsers'])
        {
            foreach ($users as $userid) {
                setTempLanguage($userid);
                $messaging->insert_message(addslashes($message) . strip_tags(str_ireplace('<br>', "\n", $GLOBALS['messageForUsers'])), get_username($userid), $user->id, FALSE, FALSE, FALSE, FALSE, _("Raumanfrage bearbeitet"), TRUE);
                restoreLanguage();
            }
        }

        //set more closed ;-)
        $reqObj->setClosed(2);
        $reqObj->store();
    }
}

//unset, if all requests are edited and the set of requests should be resetted after
if ($reset_set) {
    unset($resources_data["requests_working_on"]);
    unset($resources_data["requests_open"]);
}

/*****************************************************************************
evaluate the commands from schedule navigator (sem mode)
/*****************************************************************************/
if ($view == "view_sem_schedule" || $view == "view_group_schedule" || $view == "view_group_schedule_daily" || $view == 'openobject_group_schedule') {

    if ($_REQUEST['next_sem']){
        $sem_array = SemesterData::GetSemesterArray();
        foreach ($sem_array as $id => $one_sem){
            if ($one_sem['semester_id'] == $resources_data['sem_schedule_semester_id'] && isset($sem_array[$id+1])){
                $resources_data['sem_schedule_semester_id'] = $sem_array[$id+1]['semester_id'];
                break;
            }
        }
    }
    if ($_REQUEST['previous_sem']){
        $sem_array = SemesterData::GetSemesterArray();
        foreach ($sem_array as $id => $one_sem){
            if ($one_sem['semester_id'] == $resources_data['sem_schedule_semester_id'] && ($id-1) && isset($sem_array[$id-1])){
                $resources_data['sem_schedule_semester_id'] = $sem_array[$id-1]['semester_id'];
                break;
            }
        }
    }
    if($view == "view_group_schedule_daily" || $view == 'openobject_group_schedule'){
        if(isset($_REQUEST['jump_x'])){
            $resources_data["schedule_start_time"] = mktime (0, 0, 0, (int)$_REQUEST['schedule_begin_month'], (int)$_REQUEST['schedule_begin_day'], (int)$_REQUEST['schedule_begin_year']);
        }
        if(!$resources_data["schedule_start_time"]) $resources_data["schedule_start_time"] = strtotime('today');
        if ($_REQUEST['previous_day']){
            $resources_data["schedule_start_time"] = strtotime('yesterday', $resources_data["schedule_start_time"]);
        }
        if ($_REQUEST['next_day']){
            $resources_data["schedule_start_time"] = strtotime('tomorrow', $resources_data["schedule_start_time"]);
        }
    }
    if($view == "view_group_schedule"){
    if ($_REQUEST['previous_day']){
        $resources_data['group_schedule_dow'] = (--$resources_data['group_schedule_dow'] == 0 ? 7 : $resources_data['group_schedule_dow']);
    }
    if ($_REQUEST['next_day']){
        $resources_data['group_schedule_dow'] = (++$resources_data['group_schedule_dow'] == 8 ? 1 : $resources_data['group_schedule_dow']);
    }
    }
    if ($_REQUEST['navigate']) {
        if (isset($_REQUEST['sem_time_choose'])){
            $resources_data['sem_schedule_timespan'] = $_REQUEST['sem_time_choose'];
        }
        if (isset($_REQUEST['sem_schedule_choose'])){
            $resources_data['sem_schedule_semester_id'] = $_REQUEST['sem_schedule_choose'];
        }
        if (($sem_schedule_start_list_x) || (($jump_x) && ($resources_data["schedule_mode"] == "list"))){
            $resources_data["schedule_mode"] = "list";
        } elseif (($sem_schedule_start_graphical_x) || (!$resources_data["schedule_mode"]) || (($jump_x) && ($resources_data["schedule_mode"] == "graphical"))) {
            $resources_data["schedule_mode"] = "graphical";
        }

        if (isset($_REQUEST['group_schedule_choose_group'])){
            $resources_data['actual_room_group'] = (int)$_REQUEST['group_schedule_choose_group'];
        }
    }
    if (!$resources_data['sem_schedule_semester_id']){
        $resources_data['sem_schedule_semester_id'] = $_SESSION['_default_sem'];
        $resources_data['sem_schedule_timespan'] = 'course_time';
        $resources_data["schedule_mode"] = "graphical";
        $resources_data["show_repeat_mode"] = 'all';
    }
    if (!isset($resources_data['actual_room_group'])){
        $resources_data['actual_room_group'] = 0;
        $resources_data["schedule_mode"] = "graphical";
        $resources_data["show_repeat_mode"] = 'all';
        $resources_data['group_schedule_dow'] = 1;
    }
    $_SESSION['_default_sem'] = $resources_data['sem_schedule_semester_id'];
}

if (($show_repeat_mode) && ($send_schedule_repeat_mode_x)) {
    $resources_data["show_repeat_mode"] = $show_repeat_mode;
}

if ($time_range) {
    if ($time_range == "FALSE")
        $resources_data["schedule_time_range"] = '';
    else
        $resources_data["schedule_time_range"] = $time_range;
}

/*****************************************************************************
some other stuff ;-)
/*****************************************************************************/

//display perminvalid window
if ((in_array("1", $msg->codes)) || (in_array("25", $msg->codes))) {
    $forbiddenObject = ResourceObject::Factory($resources_data["actual_object"]);
    if ($forbiddenObject->isLocked()) {
        $lock_ts = getLockPeriod("edit");
        $msg->addMsg(31, array(date("d.m.Y, G:i", $lock_ts[0]), date("d.m.Y, G:i", $lock_ts[1])));
    }
    $msg->displayAllMsg("window");
    die;
}

//show object, this object will be edited or viewed
if ($show_object)
    $resources_data["actual_object"]=$show_object;

if ($show_msg) {
    if ($msg_resource_id)
        $msgResourceObj = ResourceObject::Factory($msg_resource_id);
    $msg->addMsg($show_msg, ($msg_resource_id) ? array(htmlReady($msgResourceObj->getName())) : FALSE);
}

//if ObjectPerms for actual user and actual object are not loaded, load them!

if ($ObjectPerms) {
    if (($ObjectPerms->getId() == $resources_data["actual_object"]) && ($ObjectPerms->getUserId()  == $user->id))
        $ActualObjectPerms = $ObjectPerms;
     else
        $ActualObjectPerms = ResourceObjectPerms::Factory($resources_data["actual_object"]);
} else
    $ActualObjectPerms = ResourceObjectPerms::Factory($resources_data["actual_object"]);

//edit or view object
if ($edit_object) {
    if ($ActualObjectPerms->getUserPerm() == "admin") {
        $resources_data["view"]="edit_object_properties";
        $view = $resources_data["view"];
    } else {
        $resources_data["view"]="view_details";
        $view = $resources_data["view"];
    }
}

?>

