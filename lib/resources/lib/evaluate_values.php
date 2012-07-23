<?
# Lifter002: TODO
# Lifter003: TEST
# Lifter007: TODO
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


/*****************************************************************************
Functions...
/*****************************************************************************/

//a small helper function to close all the kids
function closeStructure ($resource_id)
{
    unset($_SESSION['resources_data']['structure_opens'][$resource_id]);
    
    $query = "SELECT resource_id FROM resources_objects WHERE parent_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($resource_id));
    while ($resource_id = $statement->fetchColumn()) {
        closeStructure ($resource_id);
    }
}
/*****************************************************************************
Initialization
/*****************************************************************************/
$GLOBALS['messageForUsers'] = '';
foreach (words('view view_mode quick_view quick_view_mode') as $parameter_name) {
    $$parameter_name = Request::option($parameter_name);
}
$change_schedule_repeat_quantity = Request::option('change_schedule_repeat_quantity');
//a small helper function to update some data of the tree-structure (after move something)
function updateStructure ($resource_id, $root_id, $level)
{
    $query = "UPDATE resources_objects
              SET root_id = ?, level = ?
              WHERE resource_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array(
        $root_id,
        $level,
        $resource_id
    ));

    $query = "SELECT resource_id FROM resources_objects WHERE parent_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($resource_id));
    while ($resource_id = $statement->fetchColumn()) {
        closeStructure ($resource_id, $root_id, $level + 1);
    }
}

/*****************************************************************************
handle the view-logic
/*****************************************************************************/

//got a fresh session?
if ((!$view && !$quick_view && !isset($_SESSION['resources_data']['view']))
    || ( sizeof($_POST) == 0 && sizeof($_GET) == 0
    && (!isset($_SESSION['resources_data']['view']) || $_SESSION['resources_data']['view_mode'] == 'oobj' || $_SESSION['resources_data']['view_mode'] == 'search') ) ) {
    $_SESSION['resources_data']='';
    $_SESSION['resources_data']["view"]="search";
    $_SESSION['resources_data']["view_mode"]=FALSE;
    closeObject();
}

//get views/view_modes
if ($view)
    $_SESSION['resources_data']["view"]=$view;
else //or we take back the persistant view from $_SESSION['resources_data']
    $view = $_SESSION['resources_data']["view"];

if ($view_mode)
    $_SESSION['resources_data']["view_mode"]=$view_mode;
else //or... see above ;)
    $view_mode = $_SESSION['resources_data']["view_mode"];

if (strpos($view, "openobject") !== FALSE) {
    $_SESSION['resources_data']["view_mode"] = "oobj";
    $view_mode = "oobj";
}

//if quick_view, we take this view (only one page long, until the next view is given!)
if ($quick_view)
    $view = $quick_view;

//we do so for the view_mode too
if ($quick_view_mode)
    $view_mode = $quick_view_mode;
else
    $quick_view_mode = $_SESSION['resources_data']["view_mode"];

//reset edit the assign... (Zugegeben, immer noch krank: Hier wird ein "sauberer" Seitenaufruf anhand der Anzahl der Parameter ermittelt... )
if (((sizeof($_POST) + sizeof($_GET)) == 2) && (($view == "edit_object_assign") || ($view == "openobject_assign"))) {
    $_SESSION['new_assign_object']=FALSE;
}
if (((sizeof($_POST) + sizeof($_GET)) == 3) && ($edit_assign_object) && (($view == "edit_object_assign") || ($view == "openobject_assign"))) {
    $_SESSION['new_assign_object']=FALSE;
}
if (Request::option('cancel_edit_assign')) {
    $_SESSION['new_assign_object']=FALSE;
    $_SESSION['resources_data']["actual_assign"]=FALSE;
}

//send the user to index, if he want to use studip-object based modul but has no object set!
if (($view=="openobject_main") || ($view=="openobject_details") || ($view=="openobject_assign") || ($view=="openobject_schedule")){
    if (!$SessSemName[1]) {
        $_SESSION['resources_data'] = null;
        $_SESSION['resources_data']["view"] = $view = "search";
        $_SESSION['resources_data']["view_mode"] = $view_mode = FALSE;
    }
}
//we take a search as long with us, as no other overview modul is used
if (($view=="openobject_main") || ($view=="lists") || ($view=="resources"))
    $_SESSION['resources_data']["search_array"]='';



//Open a level/resource
$structure_open = Request::option('structure_open');
if ($structure_open) {
    $_SESSION['resources_data']["structure_opens"][$structure_open] =TRUE;
    $_SESSION['resources_data']["actual_object"]=$structure_open;
}

if (Request::option('edit_object'))
    $_SESSION['resources_data']["actual_object"]=Request::option('edit_object');


//Select an object to work with
if (Request::option('actual_object')) {
    $_SESSION['resources_data']["actual_object"] = Request::option('actual_object');
}

//Close a level/resource
$structure_close = Request::option('structure_close');
if ($structure_close)
    closeStructure ($structure_close);

//switch to move mode
$pre_move_object = Request::option('pre_move_object');
if ($pre_move_object) {
    $_SESSION['resources_data']["move_object"]=$pre_move_object;
}

//cancel move mode
if (Request::option('cancel_move')) {
    $_SESSION['resources_data']["move_object"]='';
}

//Listenstartpunkt festlegen
if (Request::option('open_list')) {
    $_SESSION['resources_data']["list_open"]=Request::option('open_list');
    $_SESSION['resources_data']["view"]="lists";
    $view = $_SESSION['resources_data']["view"];
    }

if (Request::option('recurse_list'))
    $_SESSION['resources_data']["list_recurse"]=TRUE;

if (Request::option('nrecurse_list'))
    $_SESSION['resources_data']["list_recurse"]=FALSE;

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
    $clip_in = Request::option('clip_in');
    $clip_out = Request::option('clip_out');
    if ($clip_in)
        $clipObj->insertElement($clip_in, "res");
    if ($clip_out)
        $clipObj->deleteElement($clip_out);
    if (!$clipFormObj->IsClicked("clip_reload"))
        $clipObj->doClipCmd();
}


//Neue Hierachieebene oder Unterebene anlegen
$create_hierachie_level = Request::option('create_hierachie_level');
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
    $_SESSION['resources_data']["structure_opens"][$newHiearchie->id] =TRUE;
    $_SESSION['resources_data']["actual_object"]=$newHiearchie->getId();
    $_SESSION['resources_data']["view"]="resources";
    $view = $_SESSION['resources_data']["view"];
    }

//Neues Objekt anlegen
$create_object = Request::option('create_object');
if ($create_object) {
    $parent_Object = ResourceObject::Factory($create_object);
    $new_Object= ResourceObject::Factory("Neues Objekt", "Dieses Objekt wurde neu erstellt. Es wurden noch keine Eigenschaften zugewiesen."
                    , FALSE, $parent_Object->getRootId(), $create_object, "0", $user->id);
    $new_Object->create();
    $_SESSION['resources_data']["view"]="edit_object_properties";
    $view = $_SESSION['resources_data']["view"];
    $_SESSION['resources_data']["actual_object"]=$new_Object->getId();
    }


//Object loeschen
$kill_object = Request::option('kill_object');
if ($kill_object) {
    $ObjectPerms = ResourceObjectPerms::Factory($kill_object);
    if ($ObjectPerms->getUserPerm () == "admin") {
        $killObject = ResourceObject::Factory($kill_object);
        if ($killObject->delete())
            $msg -> addMsg(7);
        $_SESSION['resources_data']["view"]="resources";
        $view = $_SESSION['resources_data']["view"];
        unset($_SESSION['resources_data']['actual_object']);
    } else {
        $msg->addMsg(1);
    }
}

//cancel a just created object
$cancel_edit = Request::option('cancel_edit');
if ($cancel_edit) {
    $ObjectPerms = ResourceObjectPerms::Factory($cancel_edit);
    if ($ObjectPerms->getUserPerm () == "admin") {
        $cancel_edit = ResourceObject::Factory($cancel_edit);
        $cancel_edit->delete();
        $_SESSION['resources_data']["view"]="resources";
        $view = $_SESSION['resources_data']["view"];
        unset($_SESSION['resources_data']['actual_object']);
    } else {
        $msg->addMsg(1);
    }
}


//move an object
$target_object = Request::option('target_object');
if ($target_object) {
    $ObjectPerms = ResourceObjectPerms::Factory($target_object);
    if ($ObjectPerms->getUserPerm () == "admin") {
        if ($target_object != $_SESSION['resources_data']["move_object"]) {
            //we want to move an object, so we have first to check if we want to move a object in a subordinated object
            $query = "SELECT parent_id FROM resources_objects WHERE resource_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($target_object));
            while ($parent_id = $statement->fetchColumn()) {
                if ($parent_id == $_SESSION['resources_data']['move_object']) {
                    $target_is_child = true;
                }
                
                $statement->closeCursor();
                $statement->execute(array($parent_id));
            }
            if (!$target_is_child) {
                $query = "UPDATE resources_objects SET parent_id = ? WHERE resource_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array(
                    $target_object,
                    $_SESSION['resources_data']['move_object']
                ));

                $query = "SELECT root_id, level FROM resources_objects WHERE resource_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($target_object));
                $temp = $statement->fetch(PDO::FETCH_ASSOC);

                //set the correct root_id's and levels
                updateStructure($_SESSION['resources_data']["move_object"], $temp['root_id'], $temp['level'] + 1);
                $_SESSION['resources_data']['structure_opens'][$_SESSION['resources_data']['move_object']] =TRUE;
                $_SESSION['resources_data']['structure_opens'][$target_object] =TRUE;
                if ($temp) {
                    $msg -> addMsg(9);
                }
            }
        }
        unset($_SESSION['resources_data']["move_object"]);
    } else {
        $msg->addMsg(1);
    }
}

//Name und Beschreibung aendern
$change_structure_object = Request::option('change_structure_object');
if ($change_structure_object) {
    $ObjectPerms = ResourceObjectPerms::Factory($change_structure_object);
    if ($ObjectPerms->getUserPerm () == "admin") {
        $changeObject = ResourceObject::Factory($change_structure_object);
        $changeObject->setName(Request::quoted('change_name'));
        $changeObject->setDescription(Request::quoted('change_description'));
        if ($changeObject->store())
            $msg -> addMsg(6);
    } else {
        $msg->addMsg(1);
    }
    $_SESSION['resources_data']["view"]="resources";
    $view = $_SESSION['resources_data']["view"];
    $_SESSION['resources_data']["actual_object"]=$change_structure_object;
}

/*****************************************************************************
edit/add assigns
/*****************************************************************************/

//Objektbelegung erstellen/aendern
$change_object_schedules = Request::option('change_object_schedules');
if ($change_object_schedules) {
    require_once ('lib/calendar_functions.inc.php'); //needed for extended checkdate
    require_once ($RELATIVE_PATH_RESOURCES."/lib/VeranstaltungResourcesAssign.class.php");
    require_once ('lib/classes/SemesterData.class.php');

    // check, if the submit-button has been pressed. Otherwise do not store the assign.
    $storeAssign = false;
    if (Request::submitted('submit')) {
        $storeAssign = true;
    }

    $semester = new SemesterData;
    $all_semester = $semester->getAllSemesterData();
    //load the object perms
    $ObjectPerms = ResourceObjectPerms::Factory(Request::option('change_schedule_resource_id'));

    //in some case, we load the perms from the assign object, if it has an owner
    if (($ObjectPerms->getUserPerm() != "admin") && ($change_object_schedules != "NEW") && (!$_SESSION['new_assign_object'])) {
        //load the assign-object perms of a saved object
        $SavedStateAssignObject = AssignObject::Factory($change_object_schedules);
        if ($SavedStateAssignObject->getAssignUserId()){
            unset($ObjectPerms);
            $ObjectPerms = new AssignObjectPerms($change_object_schedules);
        }
    }

    if (($ObjectPerms->havePerm("tutor")) && Request::submitted('change_meta_to_single_assigns')) {
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
    $select_change_resource = Request::quotedArray('select_change_resource');
    if ($ObjectPerms->havePerm("admin") && Request::submitted('send_change_resource') && !empty($select_change_resource)) {
        if(!is_array($select_change_resource)){
        $ChangeObjectPerms = ResourceObjectPerms::Factory($select_change_resource);
        if ($ChangeObjectPerms->havePerm("tutor")) {
                $changeAssign = AssignObject::Factory($change_object_schedules);
                $changeAssign->setResourceId($$select_change_resource);
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
            foreach($select_change_resource as $copy_to_resource_id){
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

    if (($ObjectPerms->havePerm("admin")) && (Request::submitted('change_comment_internal'))) {
        $changeAssign =& AssignObject::Factory($change_object_schedules);
        $changeAssign->setCommentInternal(Request::quoted('comment_internal'));
        $changeAssign->store();
        $msg->addMsg(50);
    }

    if ($ObjectPerms->havePerm("autor")) {
        if (Request::submitted('kill_assign')) {
            $killAssign = AssignObject::Factory($change_object_schedules);
            $killAssign->delete();
            $_SESSION['new_assign_object']='';
            $msg->addMsg(5);
            $change_schedule_id = $change_object_schedules = $_SESSION['resources_data']['actual_assign'] = FALSE;
        } elseif (!$return_schedule && !Request::submitted('search_room') 
            && !Request::submitted('reset_room_search') && !Request::submitted('change_comment_internal')) {
            if ($change_object_schedules == "NEW")
                $change_schedule_id=FALSE;
            else
                $change_schedule_id=$change_object_schedules;

            if (Request::submitted('reset_search_user'))
                Request::set('search_string_search_user',FALSE);

            if ((Request::submitted('send_search_user')) && (!Request::submitted('reset_search_user'))) {
                //Check if this user is able to reach the resource (and this assign), to provide, that the owner of the resources foists assigns to others
                $ForeignObjectPerms = ResourceObjectPerms::Factory(Request::option('change_schedule_resource_id'));
                if ($ForeignObjectPerms->havePerm("autor"))
                    Request::set('change_schedule_assign_user_id',Request::quoted('submit_search_user'));
                else
                    $msg->addMsg(2);
            }

            //the user send infinity repeat (until date) as empty field, but it's -1 in the db
            if ((Request::option('change_schedule_repeat_quantity_infinity')) && (!$change_schedule_repeat_quantity))
                $change_schedule_repeat_quantity=-1;

            //check dates
            $illegal_dates=FALSE;
            if ((!check_date(Request::quoted('change_schedule_month'), Request::quoted('change_schedule_day'), Request::quoted('change_schedule_year'), Request::quoted('change_schedule_start_hour'), Request::quoted('change_schedule_start_minute'))) ||
                (!check_date(Request::quoted('change_schedule_month'), Request::quoted('change_schedule_day'), Request::quoted('change_schedule_year'), Request::quoted('change_schedule_end_hour'), Request::quoted('change_schedule_end_minute')))) {
                $illegal_dates=TRUE;
                $msg -> addMsg(17);
            }

            //create timestamps
            if (!$illegal_dates) {
                $change_schedule_begin=mktime(Request::quoted('change_schedule_start_hour'), Request::quoted('change_schedule_start_minute'), 0, Request::quoted('change_schedule_month'), Request::quoted('change_schedule_day'), Request::quoted('change_schedule_year'));
                $change_schedule_end=mktime(Request::quoted('change_schedule_end_hour'), Request::quoted('change_schedule_end_minute'), 0, Request::quoted('change_schedule_month'), Request::quoted('change_schedule_day'), Request::quoted('change_schedule_year'));
                if ($change_schedule_begin > $change_schedule_end) {
                    if ((Request::option('change_schedule_repeat_mode') != "sd") && (!Request::submitted('change_schedule_repeat_severaldays'))) {
                        $illegal_dates=TRUE;
                        $msg -> addMsg(20);
                    }
                }
            }

            if (check_date(Request::quoted('change_schedule_repeat_end_month'), Request::quoted('change_schedule_repeat_end_day'), Request::quoted('change_schedule_repeat_end_year')))
                if (Request::option('change_schedule_repeat_mode') == "sd")
                    $change_schedule_repeat_end=mktime(date("G", $change_schedule_end), date("i", $change_schedule_end), 0, Request::quoted('change_schedule_repeat_end_month'), Request::quoted('change_schedule_repeat_end_day'), Request::quoted('change_schedule_repeat_end_year'));
                else
                    $change_schedule_repeat_end=mktime(23, 59, 59, Request::quoted('change_schedule_repeat_end_month'), Request::quoted('change_schedule_repeat_end_day'), Request::quoted('change_schedule_repeat_end_year'));

            if (Request::option('change_schedule_repeat_sem_end'))
                foreach ($all_semester as $a)
                    if (($change_schedule_begin >= $a["beginn"]) && ($change_schedule_begin <= $a["ende"]))
                        $change_schedule_repeat_end=$a["vorles_ende"];

            //create repeatdata

            //repeat = none

                $change_schedule_repeat_month_of_year=Request::quoted('change_schedule_repeat_month_of_year');
                $change_schedule_repeat_day_of_month=Request::quoted('change_schedule_repeat_day_of_month');
                $change_schedule_repeat_week_of_month=Request::quoted('change_schedule_repeat_week_of_month');
                $change_schedule_repeat_day_of_week=Request::quoted('change_schedule_repeat_quantity');
                
                $change_schedule_repeat_interval=Request::option('change_schedule_repeat_interval');
            if (Request::submitted('change_schedule_repeat_none')) {
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
            if (Request::submitted('change_schedule_repeat_severaldays')) {
                $change_schedule_repeat_end = mktime(date("G", $change_schedule_end), date("i", $change_schedule_end), 0, date("n", $change_schedule_begin), date("j", $change_schedule_begin)+1, date("Y", $change_schedule_begin));
                $change_schedule_repeat_month_of_year='';
                $change_schedule_repeat_day_of_month='';
                $change_schedule_repeat_week_of_month='';
                $change_schedule_repeat_day_of_week='';
                $change_schedule_repeat_quantity='';
                $change_schedule_repeat_interval='';
            }

            //repeat = year
            if (Request::submitted('change_schedule_repeat_year')) {
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
            if (Request::submitted('change_schedule_repeat_month'))
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
            if (Request::submitted('change_schedule_repeat_week')) {
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
            if (Request::submitted('change_schedule_repeat_day')) {
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

            //give data to the assignobject
            if (!$change_schedule_id){
                $changeAssign = AssignObject::Factory(
                    $change_schedule_id,
                    Request::option('change_schedule_resource_id'),
                    Request::option('change_schedule_assign_user_id'),
                    Request::quoted('change_schedule_user_free_name'),
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
                $changeAssign->setResourceId(Request::option('change_schedule_resource_id'));
                $changeAssign->setUserFreeName(Request::quoted('change_schedule_user_free_name'));
                $changeAssign->setAssignUserId(Request::option('change_schedule_assign_user_id'));
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
            if (($changeAssign->getRepeatMode() != "na") && (Request::quoted('change_schedule_repeat_end_month')) && (Request::quoted('change_schedule_repeat_end_day')) && (Request::quoted('change_schedule_repeat_end_year'))) {
                if (!check_date(Request::quoted('change_schedule_repeat_end_month'), Request::quoted('change_schedule_repeat_end_day'), Request::quoted('change_schedule_repeat_end_year'))) {
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
                    $_SESSION['new_assign_object']=serialize($changeAssign);
                } else {
                    $changeAssign->restore();
                }
            }

            // create a new assign
            elseif ( ($change_object_schedules == "NEW" || $_SESSION['new_assign_object'])){

                if ((Request::option('change_schedule_assign_user_id')) || (Request::quoted('change_schedule_user_free_name'))) {
                    $overlaps = $changeAssign->checkOverlap(FALSE);
                    $locks = $changeAssign->checkLock();
                }
                // show hint, that either a user or a free text must be provided
                else if ($storeAssign) {
                    $msg->addMsg(46);
                }

                if ((!$overlaps) && (!$locks)) {
                    if ($storeAssign && $changeAssign->create()) {
                        $_SESSION['resources_data']["actual_assign"]=$changeAssign->getId();
                        $msg->addMsg(3);
                        $_SESSION['new_assign_object']='';
                    } else {
                        $_SESSION['new_assign_object']=serialize($changeAssign);  // store the submitted form-data

                        if ( $storeAssign && !Request::submitted('do_search_user') && !Request::submitted('reset_search_user')
                            && !Request::option('change_schedule_assign_user_id') && Request::quoted('change_schedule_user_free_name')) {
                                $msg->addMsg(10);
                        }
                    }
                } else {
                    if ($storeAssign) {
                        if ($overlaps) {  // add error message an store the submitted form-data
                            $msg->addMsg(11);
                            $_SESSION['new_assign_object']=serialize($changeAssign);
                        }

                        if ($locks) {
                            foreach ($locks as $val)
                                $locks_txt.=date("d.m.Y, H:i",$val["lock_begin"])." - ".date("d.m.Y, H:i",$val["lock_end"])."<br>";
                            $msg->addMsg(44, array($locks_txt));
                        }
                    } else {  // store the submitted form-data
                        $_SESSION['new_assign_object']=serialize($changeAssign);
                    }
                }
            }

            // change an existing assign
            else {
                if ((Request::option('change_schedule_assign_user_id')) || (Request::quoted('change_schedule_user_free_name'))) {
                    $overlaps = $changeAssign->checkOverlap(FALSE);
                    $locks = $changeAssign->checkLock();
                }

                if ((!$overlaps) && (!$locks)) {
                    $changeAssign->chng_flag=TRUE;
                    if ($changeAssign->store()) {
                        $msg->addMsg(4);
                        $_SESSION['new_assign_object']='';
                    }
                    $_SESSION['resources_data']["actual_assign"]=$changeAssign->getId();
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
$change_object_properties = Request::option('change_object_properties');
if ($change_object_properties) {
    $ObjectPerms = ResourceObjectPerms::Factory($change_object_properties);
    if ($ObjectPerms->getUserPerm () == "admin") {
        $changeObject = ResourceObject::Factory($change_object_properties);
        $changeObject->setName(Request::quoted('change_name'));
        $changeObject->setDescription(Request::quoted('change_description'));
        $changeObject->setCategoryId(Request::option('change_category_id'));
        $changeObject->setInstitutId(Request::option('change_institut_id'));

        if (getGlobalPerms($user->id) == "admin") {
            $changeObject->setMultipleAssign($change_multiple_assign);
        }

        //Properties loeschen
        $changeObject->flushProperties();

        //Eigenschaften neu schreiben
        $props_changed=FALSE;
        $change_property_val = Request::quotedArray('change_property_val');
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

    $_SESSION['resources_data']["view"]="edit_object_properties";
    $view = $_SESSION['resources_data']["view"];
}

//Objektberechtigungen aendern
if (Request::option('change_object_perms')) {
    $ObjectPerms = ResourceObjectPerms::Factory(Request::option('change_object_perms'));
    if ($ObjectPerms->getUserPerm () == "admin") {
        $changeObject = ResourceObject::Factory(Request::option('change_object_perms'));
        $change_user_id = Request::optionArray('change_user_id');
        if (is_array($change_user_id))
            $change_user_perms = Request::optionArray('change_user_perms');
            foreach ($change_user_id as $key=>$val) {
                if ($changeObject->storePerms($val, $change_user_perms[$key]))
                    $perms_changed=TRUE;
            }

        if (Request::option('delete_user_perms'))
            if ($changeObject->deletePerms(Request::option('delete_user_perms')))
                $perms_changed=TRUE;

        if (Request::submitted('reset_search_owner'))
            $search_string_search_owner=FALSE;

        if (Request::submitted('reset_search_perm_user'))
            $search_string_search_perm_user=FALSE;

        if ((Request::submitted('send_search_owner')) && (Request::option('submit_search_owner') !="FALSE") && (!Request::submitted('reset_search_owner')))
            $changeObject->setOwnerId(Request::option('submit_search_owner'));

        if ((Request::submitted('send_search_perm_user')) && (Request::option('submit_search_perm_user') !="FALSE") && (!Request::submitted('reset_search_perm_user')))
            if ($changeObject->storePerms(Request::option('submit_search_perm_user')))
                $perms_changed=TRUE;

        if ((getGlobalPerms($user->id) == "admin") && ($changeObject->isRoom())) {
            if ($changeObject->isParent()) {
                if ((Request::option('change_lockable')) && (!$changeObject->isLockable()))
                    $msg->addMsg(29, array(URLHelper::getURL('?set_lockable_recursiv=1&lock_resource_id='.$changeObject->getId())));
                elseif ((!Request::option('change_lockable')) && ($changeObject->isLockable()))
                    $msg->addMsg(30, array(URLHelper::getURL('?unset_lockable_recursiv=1&lock_resource_id='.$changeObject->getId())));
            }
            $changeObject->setLockable(Request::option('change_lockable'));
        }

        //Object speichern
        if (($changeObject->store()) || ($perms_changed))
            $msg->addMsg(8);
    } else {
        $msg->addMsg(1);
    }
    $_SESSION['resources_data']["view"]="edit_object_perms";
    $view = $_SESSION['resources_data']["view"];
}

//set/unset lockable for a comlete hierarchy
if ((Request::option('set_lockable_recursiv')) || (Request::option('unset_lockable_recursiv'))) {
    if (getGlobalPerms($user->id) == "admin") {
        changeLockableRecursiv(Request::option('lock_resource_id'), (Request::option('set_lockable_recursiv')) ? TRUE : FALSE);
    } else {
        $msg->addMsg(1);
    }
    $_SESSION['resources_data']["view"]="edit_object_perms";
    $view = $_SESSION['resources_data']["view"];
}

//Typen bearbeiten
if ((Request::quoted('add_type')) || (Request::option('delete_type')) || (Request::option('delete_type_property_id')) || (Request::option('change_categories'))) {
    if (getGlobalPerms ($user->id) == "admin") { //check for resources root or global root
        if (Request::option('delete_type')) {
            $query = "DELETE FROM resources_categories WHERE category_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                Request::option('delete_type')
            ));
        }
        $resource_is_room = Request::int('resource_is_room');
        //$insert_type_description = Request::quoted('insert_type_description');
        if (Request::quoted('add_type') && Request::submitted('_add_type')) {
            $id=md5(uniqid("Sommer2002",1));
            if ($resource_is_room) {
                $resource_is_room = 1;
            }
            $query = "INSERT INTO resources_categories
                        (category_id, name, description, is_room)
                      VALUES (?, ?, ?, ?)";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                $id,
                Request::get('add_type'),
                Request::get('insert_type_description'),
                $resource_is_room
            ));
            if ($statement->rowCount()) {
                $created_category_id = $id;
            }
        }

        if (Request::option('delete_type_property_id')) {
            $query = "DELETE FROM resources_categories_properties
                      WHERE category_id = ? AND property_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
               Request::option('delete_type_category_id'),
               Request::option('delete_type_property_id'),
            ));
        }
        $change_category_name = Request::getArray('change_category_name');
        $change_category_iconnr = Request::optionArray('change_category_iconnr');
        $add_type_property_id = Request::optionArray('add_type_property_id');
        if (is_array($change_category_name)) {
            $query = "UPDATE resources_categories
                      SET name = ?, iconnr = ?
                      WHERE category_id = ?";
            $update_statement = DBManager::get()->prepare($query);

            $query = "INSERT INTO resources_categories_properties
                        (category_id, property_id)
                      VALUES (?, ?)";
            $insert_statement = DBManager::get()->prepare($query);

            foreach ($change_category_name as $key => $val) {
                $update_statement->execute(array(
                    $change_category_name[$key],
                    $change_category_iconnr[$key],
                    $key
                ));

                if (Request::submitted('change_category_add_property' . $key)) {
                    $insert_statement->execute(array(
                        $key, $add_type_property_id[$key]
                    ));
                }
            }
        }
        $requestable = Request::optionArray('requestable');
        if (is_array($requestable)) {
            $query = "UPDATE resources_categories_properties
                      SET requestable = ?
                      WHERE category_id = ? AND property_id = ?";
            $statement = DBManager::get()->prepare($query);

            foreach ($requestable as $key=>$val) {
                if ((strpos($requestable[$key-1], "id1_")) &&  (strpos($requestable[$key], "id2_"))) {
                    if ($requestable[$key+1] == "on") {
                        $req_num = 1;
                    } else {
                        $req_num = 0;
                    }
                    $statement->execute(array(
                        $req_num,
                        substr($requestable[$key - 1], 5),
                        substr($requestable[$key], 5)
                    ));
                }
            }
        }
    } else {
        $msg->addMsg(25);
    }
}

//Eigenschaften bearbeiten
if ((Request::option('add_property')) || (Request::option('delete_property')) || (Request::option('change_properties'))) {
    if ($globalPerm == "admin") { //check for resources root or global root
        if (Request::option('delete_property')) {
            $query = "DELETE FROM resources_properties WHERE property_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                Request::option('delete_property')
            ));
        }

        if (Request::quoted('add_property')) {
            if (Request::option('add_property_type')=="bool")
                $options="vorhanden";
            if (Request::option('add_property_type')=="select")
                $options="Option 1;Option 2;Option 3";
            $id=md5(uniqid("Regen2002",1));

            $query = "INSERT INTO resources_properties
                        (options, property_id, name, description, type)
                      VALUES (?, ?, ?, ?, ?)";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                $options,
                $id,
                Request::get('add_property'),
                Request::get('insert_property_description'),
                Request::get('add_property_type')
            ));
        }
        $change_property_name = Request::getArray('change_property_name');
        $send_property_type = Request::optionArray('send_property_type');
        $send_property_select_opt = Request::optionArray('send_property_select_opt');
        $send_property_bool_desc = Request::optionArray('send_property_bool_desc');

        $query = "UPDATE resources_properties
                  SET name = ?, options = ?, type = ?
                  WHERE property_id = ?";
        $statement = DBManager::get()->prepare($query);

        foreach ($change_property_name as $key => $val) {
            if ($send_property_type[$key] == 'select') {
                $tmp_options = explode(';', $send_property_select_opt[$key]);
                $tmp_options = array_map('trim', $tmp_options);
                $options = implode(';', $tmp_options);
            } elseif ($send_property_type[$key] == 'bool') {
                $options = $send_property_bool_desc[$key];
            } else {
                $options='';
            }

            $statement->execute(array(
                $change_property_name[$key],
                $options,
                $send_property_type[$key],
                $key
            ));
        }
    } else {
        $msg->addMsg(25);
    }
}

//Globale Perms bearbeiten
if ((Request::option('add_root_user')) || (Request::option('delete_root_user_id'))){
    if ($globalPerm == "admin") { //check for resources root or global root
        if (Request::submitted('reset_search_root_user')) {
            $search_string_search_root_user=FALSE;
        }

        if ((Request::submitted('send_search_root_user')) && (Request::option('submit_search_root_user') !="FALSE") && (!Request::submitted('reset_search_root_user'))) {
            $query = "INSERT INTO resources_user_resources (user_id, resource_id, perms)
                      VALUES (?, 'all', 'admin')";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                Request::option('submit_search_root_user')
            ));
        }

        if (Request::option('delete_root_user_id')) {
            $query = "DELETE FROM resources_user_resources
                      WHERE user_id = ? AND resource_id = 'all'";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                Request::option('delete_root_user_id')
            ));
        }
    } else {
        $msg->addMsg(25);
    }
}

/*****************************************************************************
change settings
/*****************************************************************************/

//change settings
if (Request::option('change_global_settings')) {
    if ($globalPerm == "admin") { //check for resources root or global root
        write_config("RESOURCES_LOCKING_ACTIVE", Request::option('locking_active',false));
        write_config("RESOURCES_ASSIGN_LOCKING_ACTIVE", Request::option('assign_locking_active',false));
        write_config("RESOURCES_ALLOW_ROOM_REQUESTS", Request::option('allow_requests',false));
        write_config("RESOURCES_ALLOW_CREATE_ROOMS", Request::option('allow_create_resources'));
        write_config("RESOURCES_INHERITANCE_PERMS_ROOMS", Request::option('inheritance_rooms'));
        write_config("RESOURCES_INHERITANCE_PERMS", Request::option('inheritance'));
        write_config("RESOURCES_ENABLE_ORGA_CLASSIFY", Request::option('enable_orga_classify',false));
        write_config("RESOURCES_ENABLE_ORGA_ADMIN_NOTICE", Request::option('enable_orga_admin_notice',false));
        write_config("RESOURCES_ALLOW_SINGLE_ASSIGN_PERCENTAGE", Request::option('allow_single_assign_percentage'));
        write_config("RESOURCES_ALLOW_SINGLE_DATE_GROUPING", Request::option('allow_single_date_grouping'));
    } else {
        $msg->addMsg(25);
    }
}

//create a lock
if (Request::option('create_lock')) {
    if ($globalPerm == "admin") { //check for resources root or global root
        $id = md5(uniqid("locks",1));
        $query = "INSERT INTO resources_locks (lock_id, lock_begin, lock_end, type)
                  VALUES (?, 0, 0, ?)";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $id,
            Request::option('create_lock')
        ));

        $_SESSION['resources_data']["lock_edits"][$id] = TRUE;
    } else {
        $msg->addMsg(25);
    }
}

//edit a lock
$edit_lock = Request::option('edit_lock');
if ($edit_lock) {
    if ($globalPerm == "admin") { //check for resources root or global root
        $_SESSION['resources_data']["lock_edits"][$edit_lock] = TRUE;
    } else {
        $msg->addMsg(25);
    }
}

//edit locks

if ((Request::submitted('lock_sent'))) {
    if ($globalPerm == "admin") { //check for resources root or global root
        require_once 'lib/calendar_functions.inc.php'; //needed for extended checkdate

        $query = "UPDATE resources_locks
                  SET lock_begin = ?, lock_end = ?
                  WHERE lock_id = ?";
        $statement = DBManager::get()->prepare($query);

        $lock_id = Request::optionArray('lock_id');
        foreach ($lock_id as $key=>$id) {
            $illegal_begin = FALSE;
            $illegal_end = FALSE;

            //checkdates
            $lock_begin_year  = Request::optionArray('lock_begin_year');
            $lock_begin_month = Request::optionArray('lock_begin_month');
            $lock_begin_day   = Request::optionArray('lock_begin_day');
            $lock_begin_hour  = Request::optionArray('lock_begin_hour');
            $lock_begin_min   = Request::optionArray('lock_begin_min');

            $lock_end_year  = Request::optionArray('lock_end_year');
            $lock_end_month = Request::optionArray('lock_end_month');
            $lock_end_day   = Request::optionArray('lock_end_day');
            $lock_end_hour  = Request::optionArray('lock_end_hour');
            $lock_end_min   = Request::optionArray('lock_end_min');

            if (!check_date($lock_begin_month[$key], $lock_begin_day[$key], $lock_begin_year[$key], $lock_begin_hour[$key], $lock_begin_min[$key])) {
                //$msg->addMsg(2);
                $illegal_begin=TRUE;
            } else {
                $lock_begin = mktime($lock_begin_hour[$key],$lock_begin_min[$key],0,$lock_begin_month[$key], $lock_begin_day[$key], $lock_begin_year[$key]);
            }

            if (!check_date($lock_end_month[$key], $lock_end_day[$key], $lock_end_year[$key], $lock_end_hour[$key], $lock_end_min[$key])) {
                //$msg -> addMsg(3);
                $illegal_end=TRUE;
            } else {
                $lock_end = mktime($lock_end_hour[$key],$lock_end_min[$key],0,$lock_end_month[$key], $lock_end_day[$key], $lock_end_year[$key]);
            }

            if ((!$illegal_begin) && (!$illegal_end) && ($lock_begin < $lock_end)) {
                $statement->execute(array(
                    $lock_begin,
                    $lock_end,
                    $id
                ));

                if ($statement->rowCount() > 0) {
                    $msg->addMsg(27);
                    unset($_SESSION['resources_data']["lock_edits"][$id]);
                }
            } else
                $msg->addMsg(26);
        }
    } else {
        $msg->addMsg(25);
    }
}

//kill a lock-time
$kill_lock = Request::option('kill_lock');
if ($kill_lock) {
    if ($globalPerm == 'admin') { //check for resources root or global root
        $query = "DELETE FROM resources_locks WHERE lock_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($kill_lock));
        if ($statement->rowCount() > 0) {
            $msg->addMsg(28);
            unset($_SESSION['resources_data']['lock_edits'][$kill_lock]);
        }
    } else {
        $msg->addMsg(25);
    }
}

/*****************************************************************************
evaluate the commands from schedule navigator
/*****************************************************************************/

// fixed BIEST0210; ermoeglicht sofortiges zurueckblaettern im Belegungsplan;
if ($_SESSION['resources_data']['schedule_week_offset']==null)
{
  $_SESSION['resources_data']['schedule_week_offset']=0;
}

if ($view == "view_schedule" || $view == "openobject_schedule") {
    if (Request::option('next_week'))
        $_SESSION['resources_data']["schedule_week_offset"]++;
    if (Request::option('previous_week'))
        $_SESSION['resources_data']["schedule_week_offset"]--;
    if (Request::quoted('start_time')) {
        $_SESSION['resources_data']["schedule_start_time"] = Request::quoted('start_time');
        $_SESSION['resources_data']["schedule_end_time"] = $_SESSION['resources_data']["schedule_start_time"] + (7 * 24 * 60 * 60) + 59;
        $_SESSION['resources_data']["schedule_mode"] = "graphical";
        $_SESSION['resources_data']["schedule_week_offset"] = 0;
    }
    elseif (Request::option('navigate')) {
        $_SESSION['resources_data']["schedule_length_factor"] = Request::quoted('schedule_length_factor');
        $_SESSION['resources_data']["schedule_length_unit"] = Request::quoted('schedule_length_unit');
        $_SESSION['resources_data']["schedule_week_offset"] = 0;
        $_SESSION['resources_data']["schedule_start_time"] = mktime (0,0,0,Request::quoted('schedule_begin_month'), Request::quoted('schedule_begin_day'), Request::quoted('schedule_begin_year'));
        if (Request::submitted('start_list') || (Request::submitted('jump') && ($_SESSION['resources_data']["schedule_mode"] == "list"))) {
            $_SESSION['resources_data']["schedule_mode"] = "list";
            if ($_SESSION['resources_data']["schedule_start_time"] < 1)
                $_SESSION['resources_data']["schedule_start_time"] = mktime (0, 0, 0, date("n", time()), date("j", time()), date("Y", time()));
            switch ($_SESSION['resources_data']["schedule_length_unit"]) {
                case "y" :
                    $_SESSION['resources_data']["schedule_end_time"] =mktime(23,59,59,date("n",$_SESSION['resources_data']["schedule_start_time"]), date("j", $_SESSION['resources_data']["schedule_start_time"]), date("Y",$_SESSION['resources_data']["schedule_start_time"])+$_SESSION['resources_data']["schedule_length_factor"]);
                break;
                case "m" :
                    $_SESSION['resources_data']["schedule_end_time"] =mktime(23,59,59,date("n",$_SESSION['resources_data']["schedule_start_time"])+$_SESSION['resources_data']["schedule_length_factor"], date("j", $_SESSION['resources_data']["schedule_start_time"]), date("Y",$_SESSION['resources_data']["schedule_start_time"]));
                break;
                case "w" :
                    $_SESSION['resources_data']["schedule_end_time"] =mktime(23,59,59,date("n",$_SESSION['resources_data']["schedule_start_time"]), date("j", $_SESSION['resources_data']["schedule_start_time"])+($_SESSION['resources_data']["schedule_length_factor"] * 7), date("Y",$_SESSION['resources_data']["schedule_start_time"]));
                break;
                case "d" :
                    $_SESSION['resources_data']["schedule_end_time"] =mktime(23,59,59,date("n",$_SESSION['resources_data']["schedule_start_time"]), date("j", $_SESSION['resources_data']["schedule_start_time"])+$_SESSION['resources_data']["schedule_length_factor"]-1, date("Y",$_SESSION['resources_data']["schedule_start_time"]));
                break;
            }
            if ($_SESSION['resources_data']["schedule_end_time"]  < 1)
                $_SESSION['resources_data']["schedule_end_time"] = mktime (23, 59, 59, date("n", time()), date("j", time())+1, date("Y", time()));
        } elseif (Request::submitted('start_graphical') || (!$_SESSION['resources_data']["schedule_mode"]) || (Request::submitted('jump') && ($_SESSION['resources_data']["schedule_mode"] == "graphical"))) {
            $_SESSION['resources_data']["schedule_end_time"] = $_SESSION['resources_data']["schedule_start_time"] + (7 * 24 * 60 * 60) + 59;
            $_SESSION['resources_data']["schedule_mode"] = "graphical";
        }
    } else {
        if (!$_SESSION['resources_data']["schedule_start_time"])
            $_SESSION['resources_data']["schedule_start_time"] = mktime (0, 0, 0, date("n", time()), date("j", time()), date("Y", time()));
        if (!$_SESSION['resources_data']["schedule_end_time"])
            $_SESSION['resources_data']["schedule_end_time"] = mktime (23, 59, 59, date("n", time()), date("j", time())+7, date("Y", time()));
        if (!$_SESSION['resources_data']["schedule_mode"])
            $_SESSION['resources_data']["schedule_mode"] = "graphical";
    }
}

if (Request::option('show_repeat_mode') && (Request::submitted('send_schedule_repeat_mode'))) {
    $_SESSION['resources_data']["show_repeat_mode"] = Request::option('show_repeat_mode');
}

if (Request::option('time_range')) {
    if (Request::option('time_range') == "FALSE")
        $_SESSION['resources_data']["schedule_time_range"] = '';
    else
        $_SESSION['resources_data']["schedule_time_range"] = Request::option('time_range');
}

/*****************************************************************************
handle commands from the search 'n' browse module
/*****************************************************************************/
if ($view == "search") {

    if(!isset($_SESSION['resources_data']['search_only_rooms'])){
        $_SESSION['resources_data']['search_only_rooms'] = 1;
    }

    if (Request::option('open_level'))
         $_SESSION['resources_data']["browse_open_level"]=Request::option('open_level');
    $mode = Request::option('mode');
    if ($mode == "properties")
        $_SESSION['resources_data']["search_mode"]="properties";

    if ($mode == "browse")
        $_SESSION['resources_data']["search_mode"]="browse";

    if (Request::option('check_assigns') == "TRUE")
        $_SESSION['resources_data']["check_assigns"]=TRUE;

    if (Request::option('check_assigns') == "FALSE")
        $_SESSION['resources_data']["check_assigns"]=FALSE;
    if (isset($_REQUEST['search_only_rooms']))
        $_SESSION['resources_data']["search_only_rooms"] = $_REQUEST['search_only_rooms'];

    if ((Request::submitted('start_search')) || (Request::option('search_send'))) {
        unset($_SESSION['resources_data']["search_array"]);
        $_SESSION['resources_data']["search_array"]["search_exp"] = Request::quoted('search_exp');

        $_SESSION['resources_data']["search_array"]["resources_search_range"]=$_SESSION['resources_data']["browse_open_level"]=$_REQUEST['resources_search_range'];
        $search_property_val = Request::quotedArray('search_property_val');
        if (is_array($search_property_val))
            foreach ($search_property_val as $key=>$val) {
                if ((substr($val, 0, 4) == "_id_") && (substr($search_property_val[$key+1], 0, 4) != "_id_") && ($search_property_val[$key+1]))
                    $_SESSION['resources_data']["search_array"]["properties"][substr($val, 4, strlen($val))]=$search_property_val[$key+1];
        }

        //handle dates for searching resources that are free for this times
        /// >> changed for advanced room searches
        if (Request::option('search_day_of_week')!=-1) // a day is selected. this indicates the user searches a room for the whole term
        {
            /// search whole term
            $semesterData = new SemesterData();
            $sel_semester = $semesterData->getSemesterData(Request::option('search_semester'));
            $date =  (int)$sel_semester["vorles_beginn"];

            $days = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');

            $beginn_day = date('w', $date);

            $target_day = array_search(Request::option('search_day_of_week'), $days);

            $diff = $target_day - $beginn_day;

            // wrap around
            if ($diff < 0)
            {
                $diff = 7 + $diff;
            }

            //$date = strtotime($str = "this $search_day_of_week 12:00:00", $date);
            $date= strtotime($str = "+ $diff days", $date);

            check_and_set_date (date("d",$date), date("m",$date), date("Y",$date), Request::quoted('search_begin_hour_2'), Request::quoted('search_begin_minute_2'), $_SESSION['resources_data']["search_array"], "search_assign_begin");
            check_and_set_date (date("d",$date), date("m",$date), date("Y",$date), Request::quoted('search_end_hour_2'), Request::quoted('search_end_minute_2'), $_SESSION['resources_data']["search_array"], "search_assign_end");
            $_SESSION['resources_data']["search_array"]["search_repeating"] = '1';
        } else
        {
            check_and_set_date (Request::quoted('search_day'), Request::quoted('search_month'), Request::quoted('search_year'), Request::quoted('search_begin_hour'), Request::quoted('search_begin_minute'), $_SESSION['resources_data']["search_array"], "search_assign_begin");
            check_and_set_date (Request::quoted('search_day'), Request::quoted('search_month'), Request::quoted('search_year'), Request::quoted('search_end_hour'), Request::quoted('search_end_minute'), $_SESSION['resources_data']["search_array"], "search_assign_end");
            $_SESSION['resources_data']["search_array"]["search_repeating"] = Request::int('search_repeating');
        }
                $_SESSION['resources_data']["search_array"]["search_day_of_week"] = Request::option('search_day_of_week');
                $_SESSION['resources_data']["search_array"]["search_semester"] = Request::option('search_semester');
        /// << changed for advanced room searches
        if (($_SESSION['resources_data']["search_array"]["search_assign_begin"] == -1) || ($_SESSION['resources_data']["search_array"]["search_assign_end"] == -1)) {
            $_SESSION['resources_data']["search_array"]["search_assign_begin"] = 0;
            $_SESSION['resources_data']["search_array"]["search_assign_end"] = 0;
        }
    }

    if (Request::option('reset')) {
        unset($_SESSION['resources_data']["browse_open_level"]);
        unset($_SESSION['resources_data']["search_array"]);
    }
}

/*****************************************************************************
the room-planning module
/*****************************************************************************/
if(isset($_REQUEST['tools_requests_sem_choose_button_x']) || isset($_REQUEST['tools_requests_sem_choose'])){
    $_SESSION['resources_data']["sem_schedule_semester_id"] = $_REQUEST['tools_requests_sem_choose'];
    $_SESSION['resources_data']["resolve_requests_no_time"] = (bool)$_REQUEST['resolve_requests_no_time'];
    unset($_SESSION['resources_data']["requests_working_on"]);
    unset($_SESSION['resources_data']["requests_open"]);
    $_SESSION['resources_data']["view"] = "requests_start";
    $view = "requests_start";
}

if ($view == "view_requests_schedule") {
    if ($_REQUEST['next_week'])
        $_SESSION['resources_data']["schedule_week_offset"]++;
    elseif ($_REQUEST['previous_week'])
        $_SESSION['resources_data']["schedule_week_offset"]--;
    elseif($_REQUEST["show_repeat_mode_requests"])
        $_SESSION['resources_data']["show_repeat_mode_requests"] = $_REQUEST["show_repeat_mode_requests"];
    elseif ($_REQUEST['start_time']) {
        $_SESSION['resources_data']["schedule_start_time"] = $_REQUEST['start_time'];
        $_SESSION['resources_data']["schedule_end_time"] = $_SESSION['resources_data']["schedule_start_time"] + (7 * 24 * 60 * 60) + 59;
        $_SESSION['resources_data']["schedule_mode"] = "graphical";
        $_SESSION['resources_data']["schedule_week_offset"] = 0;
    }
    elseif ($_REQUEST['navigate']) {
        $_SESSION['resources_data']["schedule_week_offset"] = 0;
        $_SESSION['resources_data']["schedule_start_time"] = mktime (0,0,0,(int)$_REQUEST['schedule_begin_month'], (int)$_REQUEST['schedule_begin_day'], (int)$_REQUEST['schedule_begin_year']);
    } else {
        if($_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]['first_event']){
            $_SESSION['resources_data']["schedule_start_time"] = $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]['first_event'];
        } else {
            $semester_data = SemesterData::GetInstance()->getSemesterData($_SESSION['resources_data']["sem_schedule_semester_id"]);
            $_SESSION['resources_data']["schedule_start_time"] = $semester_data['vorles_beginn'];
        }
        $_SESSION['resources_data']["schedule_mode"] = "graphical";
        $_SESSION['resources_data']["show_repeat_mode_requests"] = 'all';
        $_SESSION['resources_data']["schedule_week_offset"] = 0;
        $_SESSION['resources_data']['sem_schedule_timespan'] = 'course_time';
    }
}
switch (Request::option('skip_closed_requests')) {
    case "FALSE" : $_SESSION['resources_data']["skip_closed_requests"] = FALSE; break;
    case "TRUE" : $_SESSION['resources_data']["skip_closed_requests"] = TRUE; break;
}

//cancel an edit request session
if (Request::int('cancel_edit_request_x') || Request::submitted('cancel_edit_request') ) {
    if (sizeof($_SESSION['resources_data']["requests_open"]) < sizeof ($_SESSION['resources_data']["requests_working_on"])) {
        foreach ($_SESSION['resources_data']["requests_working_on"] as $val) {
            $request_ids[] = $val["request_id"];
            $request_data[$val["request_id"]] = $val;
        }

        if (count($request_ids) > 0) {
            $query = "SELECT 1 FROM resources_requests WHERE closed = 1 AND request_id IN (?)";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($request_ids));
            if ($statement->fetchColumn()) {
                $msg->addMsg(40, array(URLHelper::getLink(), URLHelper::getLink('?snd_closed_request_sms=1')));
                Request::set('save_state', 1);
            }
        }
    }
    $_SESSION['resources_data']["view"] = "requests_start";
    $view = "requests_start";
}

//we start a new room-planning-session
if (Request::submitted('start_multiple_mode') || (Request::option('single_request'))) {
    unset($_SESSION['resources_data']["requests_working_on"]);
    unset($_SESSION['resources_data']["requests_open"]);

    $requests = (array)getMyRoomRequests($GLOBALS['user']->id, $_SESSION['resources_data']["sem_schedule_semester_id"], true, Request::option('single_request'));

    $_SESSION['resources_data']["requests_working_pos"] = 0;
    $_SESSION['resources_data']["skip_closed_requests"] = TRUE;
    if($_REQUEST['resolve_requests_mode'] == "one_res"){
        $_SESSION['resources_data']['resolve_requests_one_res'] = $_REQUEST['resolve_requests_one_res'];
    } else {
        $_SESSION['resources_data']['resolve_requests_one_res'] = null;
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

    if (Request::option('single_request')) {
        if ($selected_requests[Request::option('single_request')]) {
            $_SESSION['resources_data']["requests_working_on"][] = array("request_id" => Request::option('single_request'), "closed" => FALSE);
            $_SESSION['resources_data']["requests_open"][Request::option('single_request')] = TRUE;
            if($requests[Request::option('single_request')]['resource_id']){
                $_SESSION['resources_data']['resolve_requests_one_res'] = $requests[Request::option('single_request')]['resource_id'];
            }
        }
    } elseif (is_array($selected_requests)) {
        //order requests
        $order = '';
        if (Request::option('resolve_requests_order') == 'complex') {
            $order = "seats DESC, complexity DESC";
        } else  if (Request::option('resolve_requests_order') == 'newest') {
            $order = "a.mkdate DESC";
        } else if (Request::option('resolve_requests_order') == 'oldest') {
            $order = "a.mkdate ASC";
        }

        // for sort-order urgent a simpler query suffices
        if (Request::option('resolve_requests_order') == 'urgent') {
            $query = "SELECT rq.request_id, rq.seminar_id, rq.termin_id
                      FROM resources_requests AS rq,
                           termine AS t
                      WHERE rq.request_id IN (?) AND t.date > UNIX_TIMESTAMP()
                        AND ((t.range_id = rq.seminar_id AND IFNULL(rq.termin_id, '') = '')
                             OR (IFNULL(rq.termin_id, '') != '' AND rq.termin_id = t.termin_id))
                      ORDER BY {$order}";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
               array_keys($selected_requests) ?: ''
            ));
            while ($data = $statement->fetch(PDO::FETCH_ASSOC)) {
                if (!isset($db_requests[$data['request_id']])) {
                    $db_requests[$data['request_id']] = $data;
                }
            }
        } else {
            $query = "SELECT a.seminar_id, a.termin_id, a.request_id #, a.resource_id,
                             # COUNT(b.property_id) AS complexity, MAX(d.state) AS seats
                      FROM resources_requests AS a
                      LEFT JOIN resources_requests_properties AS b USING (request_id)
                      LEFT JOIN resources_properties AS c ON (b.property_id = c.property_id AND c.system = 2)
                      LEFT JOIN resources_requests_properties AS d
                        ON (c.property_id = d.property_id AND a.request_id = d.request_id)
                      WHERE a.request_id IN (?)
                      GROUP BY a.request_id
                      ORDER BY {$order}";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                array_keys($selected_requests) ?: ''
            ));

            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $db_requests[] = $row;
            }

        }

        // insert sorted requests into resources_data-Array
        if (is_array($db_requests)) foreach ($db_requests as $val) {
            $_SESSION['resources_data']["requests_working_on"][] = array("request_id" => $val['request_id'], "closed" => FALSE);
            $_SESSION['resources_data']["requests_open"][$val['request_id']] = TRUE;
        }

    }
    if (is_array($_SESSION['resources_data']['requests_open']) && count($_SESSION['resources_data']['requests_open'])){
        $_SESSION['resources_data']["view"] = "edit_request";
        $view = $_SESSION['resources_data']["view"];
        $new_session_started = TRUE;
    } else {
        $_SESSION['resources_data']["view"] = $view = "requests_start";
        $msg->addMsg(41);
    }
}
$selected_resource_id = Request::optionArray('selected_resource_id');
if (is_array($selected_resource_id)) {
    foreach ($selected_resource_id as $key=>$val) {
        $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["selected_resources"][$key] = $val;
    }
}

// save the assigments in db
if (Request::submitted('save_state')) {
    require_once ($RELATIVE_PATH_RESOURCES."/lib/RoomRequest.class.php");
    require_once ($RELATIVE_PATH_RESOURCES."/lib/VeranstaltungResourcesAssign.class.php");
    require_once ("lib/classes/Seminar.class.php");

    $reqObj = new RoomRequest($_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["request_id"]);
    $semObj = Seminar::GetInstance($reqObj->getSeminarId());
    $semResAssign = new VeranstaltungResourcesAssign($semObj->getId());

    //if not single date-mode, we have to load all termin_ids from other requests of this seminar, because these dates don't have to be touched (they have an own request!)
    if (!$reqObj->getTerminId()) {
        $query = "SELECT rr.termin_id, closed, date, end_time
                  FROM resources_requests AS rr
                  LEFT JOIN termine USING (termin_id)
                  WHERE seminar_id = ? AND rr.termin_id != ''";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($reqObj->getSeminarId()));
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $dates_with_request[$row['termin_id']] = array(
                'closed' => $row['closed'],
                'begin'  => $row['date'],
                'end'    => $row['end_time']
            );
        }
    }

    //single date mode - just create one assign-object
    if ($reqObj->getTerminId()) {
        $dateRequest = TRUE;
        $assignObjects[] = $semResAssign->getDateAssignObject($reqObj->getTerminId());
    }

    //multiple assign_objects (every date one assign-object or every metadate one assign-object)
    elseif (is_array ($_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["assign_objects"])) {
        $i=0;
        //check, if one assignment should assigned to a room, which is only particularly free - so we have treat every single date
        if ($reqObj->getType() == 'cycle' ) {
            $assignObjects = $semResAssign->getMetaDateAssignObjects($reqObj->getMetadateId());
        } else if ($reqObj->getType() == 'course' ) {
            $assignObjects = $semResAssign->getDateAssignObjects(TRUE);
        }
    }

    //get the selected resources, save this informations and create the right msgs
    if (is_array($_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["selected_resources"])) {
        //check all selected resources for perms
        foreach ($_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["selected_resources"] as $key=>$val) {
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
                reset($_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["selected_resources"]);
                list(,$res_id) = each($_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["selected_resources"]);
                $result = $semResAssign->changeDateAssign($reqObj->getTerminId(), $res_id);

            //grouped multiple dates mode
            } else {
                foreach ($_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["selected_resources"] as $key=>$val) {
                    $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["groups"][$key]["resource_id"] = $val;
                    foreach ($_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["groups"][$key]["termin_ids"] as $key2 => $val2) {
                        if (!$dates_with_request[$key2]) {
                            $result = array_merge((array)$result, (array)$semResAssign->changeDateAssign($key2, $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["selected_resources"][$key]));
                            $result_termin_id[] = $key2;
                        } else
                            $skipped_termin_ids[$key2]=TRUE;
                    }
                }

                // only close request, if number of booked entries matches number of bookable entries
                $booked_entries = sizeof($_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["selected_resources"]);
                $bookable_entries = sizeof($_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]['groups']);

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
                $assign_ids = array_keys($_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["assign_objects"]);
                $resObj = ResourceObject::Factory($res_id);

                if (!empty($result)){
                    foreach ($result as $key=>$val) {
                        if (!$val["overlap_assigns"]) {
                            $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["assign_objects"][$assign_ids[0]]["resource_id"] = $resObj->getId();
                            $good_msg.="<br>".sprintf(_("%s, Belegungszeit: %s"), $resObj->getFormattedLink( $assignObjects[0]->getBegin() ), $assignObjects[0]->getFormattedShortInfo());
                            $succesful_assigned++;
                        } else {
                            $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["assign_objects"][$assign_ids[0]]["resource_id"] = FALSE;
                            $bad_msg.="<br>".sprintf(_("%s, Belegungszeit: %s"), $resObj->getFormattedLink( $assignObjects[0]->getBegin() ), $assignObjects[0]->getFormattedShortInfo());
                        }
                    }
                }

            //create msgs, grouped multi date mode
            } else { 
                $i=0;
                foreach ($result as $key => $val) {
                    if($val["resource_id"]) {
                        $resObj = ResourceObject::Factory($val["resource_id"]);
                        $zw_msg = '<br>' . sprintf(_("%s, Belegungszeit: %s"), 
                            $resObj->getFormattedLink($assignObjects[$val['termin_id']]->getBegin()),
                            $assignObjects[$val['termin_id']]->getFormattedShortInfo());

                        if (!$val["overlap_assigns"]) {
                            $good_msg .= $zw_msg;
                        } else {
                            $req_added_msg .= $zw_msg;
                            $copyReqObj = clone $reqObj;
                            $copyReqObj->copy();
                            $copyReqObj->setTerminId($val["termin_id"]);
                            $copyReqObj->store();
                        }

                        $i++;
                    }
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
            $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["reload"] = TRUE;

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
    foreach ($_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["assign_objects"] as $val) {
        if ($val["resource_id"])
            $assigned_resources++;
    }

    if ((($assigned_resources + $skipped_objects) == sizeof($assignObjects)) || ($close_request)) {
        $_sendMessage['request_id'] = $reqObj->id;
        $_sendMessage['seminar_id'] = $reqObj->seminar_id;
        $_sendMessage['type'] = 'closed';

        $reqObj->setClosed(1);
        $reqObj->store();
        unset($_SESSION['resources_data']["requests_open"][$reqObj->getId()]);
        if (sizeof($_SESSION['resources_data']["requests_open"]) == 0) {
            $_SESSION['resources_data']["view"] = "requests_start";
            $view = "requests_start";
            Request::set('save_state', 1);
        } else  {
            if ($_SESSION['resources_data']["requests_working_pos"] == sizeof($_SESSION['resources_data']["requests_working_on"])-1) {
                $auto_dec = TRUE;
            } else {
                $auto_inc = TRUE;
            }
        }
    }
}

if (Request::submitted('do_delete_requests') && get_config('RESOURCES_ALLOW_DELETE_REQUESTS') && getGlobalPerms($GLOBALS['user']->id) == 'admin'){
    if (is_array($_REQUEST['requests_marked_to_kill'])){
        foreach($_REQUEST['requests_marked_to_kill'] as $rid){
            $req_obj = new RoomRequest($rid);
            $count += ($req_obj->delete() != 0);
            unset($_SESSION['resources_data']["requests_open"][$rid]);
            foreach($_SESSION['resources_data']['requests_working_on'] as $number => $rwo){
                if ($rwo['request_id'] == $rid){
                    unset($_SESSION['resources_data']['requests_working_on'][$number]);
                    break;
                }
            }
        }
        $_SESSION['resources_data']['requests_working_pos'] = 0;
        $_SESSION['resources_data']['requests_working_on'] = array_values($_SESSION['resources_data']['requests_working_on']);
        $msg->addMsg(45, array($count));
    }
}

if (Request::submitted('suppose_decline_request')) {
    $msg->addMsg(43, array(URLHelper::getLink(), URLHelper::getLink('?decline_request=0')));
    $view = "edit_request";
}

if (Request::int('decline_request')) {
    require_once ($RELATIVE_PATH_RESOURCES."/lib/RoomRequest.class.php");

    $reqObj = new RoomRequest($_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["request_id"]);

    $_sendMessage['request_id'] = $reqObj->id;
    $_sendMessage['seminar_id'] = $reqObj->seminar_id;
    $_sendMessage['type'] = 'declined';

    $reqObj->setClosed(3);
    $reqObj->store();
    unset($_SESSION['resources_data']["requests_open"][$reqObj->getId()]);
    if (sizeof($_SESSION['resources_data']["requests_open"]) == 0) {
        $_SESSION['resources_data']["view"] = "requests_start";
        $view = "requests_start";

    } else  {
        if ($_SESSION['resources_data']["requests_working_pos"] == sizeof($_SESSION['resources_data']["requests_working_on"])-1) {
            $auto_dec = TRUE;
        } else {
            $auto_inc = TRUE;
        }
    }
}
if (Request::submitted('delete_request') || $_REQUEST['approveDelete']) {
        if(!$_REQUEST['approveDelete']){
            $approval=array('approveDelete' => TRUE);
            echo createQuestion(_("Wollen Sie diese Raumanfrage wirklich löschen?"), $approval);
        }

    if($_REQUEST['approveDelete']){
            require_once ($RELATIVE_PATH_RESOURCES."/lib/RoomRequest.class.php");
            $reqObj = new RoomRequest($_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["request_id"]);//$_REQUEST['request_id']);
            unset($_SESSION['resources_data']["requests_open"][$reqObj->getId()]);
            $reqObj->delete();
            $_SESSION['resources_data']['requests_working_pos'] = 0;
            $_SESSION['resources_data']['requests_working_on'] = array_values($_SESSION['resources_data']['requests_working_on']);
            unset($_REQUEST['approveDelete']);
                if (sizeof($_SESSION['resources_data']["requests_open"]) == 0) {
                    $_SESSION['resources_data']["view"] = "requests_start";
                    $view = "requests_start";
            } else  {
                    if ($_SESSION['resources_data']["requests_working_pos"] == sizeof($_SESSION['resources_data']["requests_working_on"])-1) {
                            $auto_dec = TRUE;
                    } else {
                            $auto_inc = TRUE;
                    }
            }

        }
}

// inc if we have requests left in the upper
if (Request::submitted('inc_request') || $auto_inc)
    if ($_SESSION['resources_data']["requests_working_pos"] < sizeof($_SESSION['resources_data']["requests_working_on"])-1) {
        $i = 1;
        if ($_SESSION['resources_data']["skip_closed_requests"])
            while ((!$_SESSION['resources_data']["requests_open"][$_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"] + $i]["request_id"]]) && ($_SESSION['resources_data']["requests_working_pos"] + $i < sizeof($_SESSION['resources_data']["requests_open"])-1))
                $i++;
        if ((sizeof($_SESSION['resources_data']["requests_open"]) >= 1) && (($_SESSION['resources_data']["requests_open"][$_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"] + $i]["request_id"]]) || (!$_SESSION['resources_data']["skip_closed_requests"]))){
            $_SESSION['resources_data']["requests_working_pos"] = $_SESSION['resources_data']["requests_working_pos"] + $i;
        } elseif ($auto_inc)
            Request::set('dec_request', 1); //we cannot inc - so we are at the end and want to find an request below, so try do dec.
    }

// dec if we have requests left in the lower
if ((Request::submitted('dec_request')) || ($auto_dec))
    if ($_SESSION['resources_data']["requests_working_pos"] > 0) {
        $d = -1;
        if ($_SESSION['resources_data']["skip_closed_requests"])
            while ((!$_SESSION['resources_data']["requests_open"][$_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"] + $d]["request_id"]]) && ($_SESSION['resources_data']["requests_working_pos"] + $d > 0))
                $d--;
        if ((sizeof($_SESSION['resources_data']["requests_open"]) >= 1) && (($_SESSION['resources_data']["requests_open"][$_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"] + $d]["request_id"]]) || (!$_SESSION['resources_data']["skip_closed_requests"]))) {
            $_SESSION['resources_data']["requests_working_pos"] = $_SESSION['resources_data']["requests_working_pos"] + $d;
        }
    }

//inc/dec the limits of found rooms
if (Request::int('inc_limit_low')) {
    $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["search_limit_low"]+=10;
    $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["reload"] = TRUE;
    if ($_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["search_limit_low"] >= $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["search_limit_high"])
        $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["search_limit_low"] = $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["search_limit_high"] -1;
}
if (Request::int('inc_limit_high')) {
    $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["search_limit_high"]+=10;
    $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["reload"] = TRUE;
}

if (Request::int('dec_limit_low')) {
    $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["search_limit_low"]-=10;
    $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["reload"] = TRUE;
    if ($_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["search_limit_low"] < 0)
        $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["search_limit_low"] = 0;
}
if (Request::int('dec_limit_high')) {
    $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["search_limit_high"]-=10;
    $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["reload"] = TRUE;
    if ($_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["search_limit_high"] <= $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["search_limit_low"])
        $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["search_limit_high"] = $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["search_limit_low"] +1;
}
if (Request::submitted('matching_rooms_limit_submit')) {
    $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["search_limit_low"] = Request::quoted('search_rooms_limit_low') - 1;
    $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["search_limit_high"] = Request::quoted('search_rooms_limit_high') ;
    $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["reload"] = TRUE;
}

if (Request::submitted('request_tool_group')) {
    if ($_REQUEST['request_tool_choose_group'] == '-'){
        $_SESSION['resources_data']['actual_room_group'] = null;
    } else {
        $_SESSION['resources_data']['actual_room_group'] = $_REQUEST['request_tool_choose_group'];
    }
    $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["reload"] = TRUE;
}


//create the (overlap)data for all resources that should checked for a request
if (Request::submitted('inc_request') || Request::submitted('dec_request') 
    || $new_session_started || $marked_clip_ids || Request::submitted('save_state') || $auto_inc 
    || $auto_dec || $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["reload"]) {
    require_once ($RELATIVE_PATH_RESOURCES."/lib/RoomRequest.class.php");
    require_once ($RELATIVE_PATH_RESOURCES."/lib/CheckMultipleOverlaps.class.php");
    require_once ($RELATIVE_PATH_RESOURCES."/lib/VeranstaltungResourcesAssign.class.php");
    require_once ($RELATIVE_PATH_RESOURCES."/lib/ResourcesUserRoomsList.class.php");
    require_once ("lib/classes/Seminar.class.php");
    require_once ("lib/classes/SemesterData.class.php");

    if ((!is_array($_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["detected_overlaps"]))
        || ($marked_clip_ids) || ($_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["reload"])) {

        unset ($_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["reload"]);
        if (!isset($_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["search_limit_low"])) {
            $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["search_limit_low"] = 0;
            $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["search_limit_high"] = 10;
        }
        $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["considered_resources"] = array();

        $semester = new SemesterData;
        $all_semester = $semester->getAllSemesterData();

        $reqObj = new RoomRequest($_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["request_id"]);
        $semObj = Seminar::GetInstance($reqObj->getSeminarId(), true);
        $multiOverlaps = new CheckMultipleOverlaps();
        $semResAssign = new VeranstaltungResourcesAssign($semObj->getId());

        //add the requested ressource to selection
        if ($reqObj->getResourceId())
            $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["considered_resources"][$reqObj->getResourceId()] = array("type"=>"requested");

        //add resource_ids from room groups
        if (get_config('RESOURCES_ENABLE_GROUPING')){
            $room_group = RoomGroups::GetInstance();
            $group_id = $_SESSION['resources_data']['actual_room_group'];
            if ($room_group->getGroupCount($group_id)){
                foreach ($room_group->getGroupContent($group_id) as $val) {
                    $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["considered_resources"][$val] = array("type"=>"grouped");
                }
            }
        }

        //add the matching ressources to selection
        if (getGlobalPerms($user->id) != "admin")
            $resList = new ResourcesUserRoomsList ($user->id, FALSE, FALSE);
        $matching_resources = $reqObj->searchRooms(FALSE, TRUE, $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["search_limit_low"], $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["search_limit_high"], TRUE, (is_object($resList)) ? array_keys($resList->getRooms()) : FALSE);
        if ($_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["search_limit_high"]  > ($reqObj->last_search_result_count + $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["search_limit_low"]))
            $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["search_limit_high"] = $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["search_limit_low"] + $reqObj->last_search_result_count;

        foreach ($matching_resources as $key => $val) {
            if (!$_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["considered_resources"][$key])
                $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["considered_resources"][$key] = array("type"=>"matching");
        }

        //add resource_ids from clipboard
        if (is_array($marked_clip_ids))
            foreach ($marked_clip_ids as $val)
                if (!$_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["considered_resources"][$val])
                    $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["considered_resources"][$val] = array("type"=>"clipped");


        //create the assign-objects for the seminar (virtual!)
        $assignObjects = array();
        if ($reqObj->getType() == 'date') {
            $assignObjects[] = $semResAssign->getDateAssignObject($reqObj->getTerminId());
        } else if ($reqObj->getType() == 'cycle' ) {
            $assignObjects = $semResAssign->getMetaDateAssignObjects($reqObj->getMetadateId());
        } else if ($reqObj->getType() == 'course' ) {
            $assignObjects = $semResAssign->getDateAssignObjects(TRUE);
        }

        $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["assign_objects"]=array();
        $current_time = (time()-3600);
        if ($GLOBALS['RESOURCES_HIDE_PAST_SINGLE_DATES']) {
            $new_assign_objects = Array();
            if (!$reqObj->getTerminId()) {
                if (is_array($assignObjects) && sizeof($assignObjects) > 0) {
                     foreach ($assignObjects as $assObj) {
                         if (  $assObj->begin < $current_time
                                 && $assObj->end < $current_time )
                         {
                             continue;
                         }
                         $new_assign_objects [] = $assObj;
                     }
                }
                $assignObjects = $new_assign_objects;
            }
        }

        if (is_array($assignObjects) && sizeof($assignObjects) > 0) {

            //add already assigned resource_ids to the check-set and remember those assigns
            foreach($assignObjects as $assObj){
                if ($assObj->getResourceId()){
                    if(!$_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["considered_resources"][$assObj->getResourceId()])
                        $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["considered_resources"][$assObj->getResourceId()] = array("type"=>"matching");
                }
                $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["assign_objects"][$assObj->getId()] = array("resource_id" => $assObj->getResourceId());
            }

            //set the time range to check;
            $multiOverlaps->setAutoTimeRange($assignObjects);

            //add the considered resources to the check-set
            if (is_array($_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["considered_resources"])) {
                foreach ($_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["considered_resources"] as $key => $val) {
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
            $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["groups"] = $groupedDates['groups'];

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

                $event_zw =  $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["groups"][$group_id]["termin_ids"];
                $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["groups"][$group_id]["events_count"] = sizeof($event_zw);
                $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["groups"][$group_id]["overlap_events_count"] = $overlaps;

                // Der gebuchte Raum, soweit vorhanden => fuer den gruenen Haken
                if ($groupedDates['info'][$group_id]['raum']) {
                    $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["groups"][$group_id]["resource_id"] = $groupedDates['info'][$group_id]['raum'];
                }

            }  // Ende: gruppierte Termine durchlaufen

            $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["detected_overlaps"] = $result;
            $_SESSION['resources_data']["requests_working_on"][$_SESSION['resources_data']["requests_working_pos"]]["first_event"] = $first_event;
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

    foreach ($_SESSION['resources_data']["requests_working_on"] as $val) {
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
if (Request::option('reset_set')) {
    unset($_SESSION['resources_data']["requests_working_on"]);
    unset($_SESSION['resources_data']["requests_open"]);
}

/*****************************************************************************
evaluate the commands from schedule navigator (sem mode)
/*****************************************************************************/
if ($view == "view_sem_schedule" || $view == "view_group_schedule" || $view == "view_group_schedule_daily" || $view == 'openobject_group_schedule') {

    if ($_REQUEST['next_sem']){
        $sem_array = SemesterData::GetSemesterArray();
        foreach ($sem_array as $id => $one_sem){
            if ($one_sem['semester_id'] == $_SESSION['resources_data']['sem_schedule_semester_id'] && isset($sem_array[$id+1])){
                $_SESSION['resources_data']['sem_schedule_semester_id'] = $sem_array[$id+1]['semester_id'];
                break;
            }
        }
    }
    if ($_REQUEST['previous_sem']){
        $sem_array = SemesterData::GetSemesterArray();
        foreach ($sem_array as $id => $one_sem){
            if ($one_sem['semester_id'] == $_SESSION['resources_data']['sem_schedule_semester_id'] && ($id-1) && isset($sem_array[$id-1])){
                $_SESSION['resources_data']['sem_schedule_semester_id'] = $sem_array[$id-1]['semester_id'];
                break;
            }
        }
    }
    if($view == "view_group_schedule_daily" || $view == 'openobject_group_schedule'){
        if(Request::submitted('jump')) {
            $_SESSION['resources_data']["schedule_start_time"] = mktime (0, 0, 0, (int)$_REQUEST['schedule_begin_month'], (int)$_REQUEST['schedule_begin_day'], (int)$_REQUEST['schedule_begin_year']);
        }
        if(!$_SESSION['resources_data']["schedule_start_time"]) $_SESSION['resources_data']["schedule_start_time"] = strtotime('today');
        if ($_REQUEST['previous_day']){
            $_SESSION['resources_data']["schedule_start_time"] = strtotime('yesterday', $_SESSION['resources_data']["schedule_start_time"]);
        }
        if ($_REQUEST['next_day']){
            $_SESSION['resources_data']["schedule_start_time"] = strtotime('tomorrow', $_SESSION['resources_data']["schedule_start_time"]);
        }
    }
    if($view == "view_group_schedule"){
    if ($_REQUEST['previous_day']){
        $_SESSION['resources_data']['group_schedule_dow'] = (--$_SESSION['resources_data']['group_schedule_dow'] == 0 ? 7 : $_SESSION['resources_data']['group_schedule_dow']);
    }
    if ($_REQUEST['next_day']){
        $_SESSION['resources_data']['group_schedule_dow'] = (++$_SESSION['resources_data']['group_schedule_dow'] == 8 ? 1 : $_SESSION['resources_data']['group_schedule_dow']);
    }
    }
    if ($_REQUEST['navigate']) {
        if (isset($_REQUEST['sem_time_choose'])){
            $_SESSION['resources_data']['sem_schedule_timespan'] = $_REQUEST['sem_time_choose'];
        }
        if (isset($_REQUEST['sem_schedule_choose'])){
            $_SESSION['resources_data']['sem_schedule_semester_id'] = $_REQUEST['sem_schedule_choose'];
        }
        if (Request::submitted('sem_schedule_start_list') || (Request::submitted('jump') && ($_SESSION['resources_data']["schedule_mode"] == "list"))){
            $_SESSION['resources_data']["schedule_mode"] = "list";
        } elseif (Request::submitted('sem_schedule_start_graphical') || (!$_SESSION['resources_data']["schedule_mode"]) || (Request::submitted('jump') && ($_SESSION['resources_data']["schedule_mode"] == "graphical"))) {
            $_SESSION['resources_data']["schedule_mode"] = "graphical";
        }

        if (isset($_REQUEST['group_schedule_choose_group'])){
            $_SESSION['resources_data']['actual_room_group'] = (int)$_REQUEST['group_schedule_choose_group'];
        }
    }
    if (!$_SESSION['resources_data']['sem_schedule_semester_id']){
        $_SESSION['resources_data']['sem_schedule_semester_id'] = $_SESSION['_default_sem'];
        $_SESSION['resources_data']['sem_schedule_timespan'] = 'course_time';
        $_SESSION['resources_data']["schedule_mode"] = "graphical";
        $_SESSION['resources_data']["show_repeat_mode"] = 'all';
    }
    if (!isset($_SESSION['resources_data']['actual_room_group'])){
        $_SESSION['resources_data']['actual_room_group'] = 0;
        $_SESSION['resources_data']["schedule_mode"] = "graphical";
        $_SESSION['resources_data']["show_repeat_mode"] = 'all';
        $_SESSION['resources_data']['group_schedule_dow'] = 1;
    }
    $_SESSION['_default_sem'] = $_SESSION['resources_data']['sem_schedule_semester_id'];
}

if ((Request::option('show_repeat_mode')) && (Request::submitted('send_schedule_repeat_mode'))) {
    $_SESSION['resources_data']["show_repeat_mode"] = $show_repeat_mode;
}

if (Request::option('time_range')) {
    if (Request::option('time_range') == "FALSE")
        $_SESSION['resources_data']["schedule_time_range"] = '';
    else
        $_SESSION['resources_data']["schedule_time_range"] = Request::option('time_range');
}

/*****************************************************************************
some other stuff ;-)
/*****************************************************************************/

//display perminvalid window
if ((in_array("1", $msg->codes)) || (in_array("25", $msg->codes))) {
    $forbiddenObject = ResourceObject::Factory($_SESSION['resources_data']["actual_object"]);
    if ($forbiddenObject->isLocked()) {
        $lock_ts = getLockPeriod("edit");
        $msg->addMsg(31, array(date("d.m.Y, G:i", $lock_ts[0]), date("d.m.Y, G:i", $lock_ts[1])));
    }
    $msg->displayAllMsg("window");
    die;
}

//show object, this object will be edited or viewed
if (Request::option('show_object'))
    $_SESSION['resources_data']["actual_object"]=Request::option('show_object');

if (Request::option('show_msg')) {
    if (Request::option('msg_resource_id'))
        $msgResourceObj = ResourceObject::Factory($msg_resource_id);
    $msg->addMsg(Request::option('show_msg'), (Request::option('msg_resource_id')) ? array(htmlReady($msgResourceObj->getName())) : FALSE);
}

//if ObjectPerms for actual user and actual object are not loaded, load them!

if ($ObjectPerms) {
    if (($ObjectPerms->getId() == $_SESSION['resources_data']["actual_object"]) && ($ObjectPerms->getUserId()  == $user->id))
        $ActualObjectPerms = $ObjectPerms;
     else
        $ActualObjectPerms = ResourceObjectPerms::Factory($_SESSION['resources_data']["actual_object"]);
} else
    $ActualObjectPerms = ResourceObjectPerms::Factory($_SESSION['resources_data']["actual_object"]);

//edit or view object
if (Request::option('edit_object')) {
    if ($ActualObjectPerms->getUserPerm() == "admin") {
        $_SESSION['resources_data']["view"]="edit_object_properties";
        $view = $_SESSION['resources_data']["view"];
    } else {
        $_SESSION['resources_data']["view"]="view_details";
        $view = $_SESSION['resources_data']["view"];
    }
}

?>

