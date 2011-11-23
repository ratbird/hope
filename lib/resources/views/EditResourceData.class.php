<?
# Lifter002: TODO
# Lifter001: TEST
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* EditResourceData.class.php
*
* shows the forms to edit the object
*
*
* @author       Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup      resources
* @module       EditResourceData.class.php
* @package      resources
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// EditResourceData.class.php
// stellt die forms zur Bearbeitung eines Ressourcen-Objekts zur Verfuegung
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

require_once ($RELATIVE_PATH_RESOURCES.'/lib/ResourceObject.class.php');
require_once ($RELATIVE_PATH_RESOURCES.'/lib/ResourceObjectPerms.class.php');
require_once ($RELATIVE_PATH_RESOURCES.'/lib/ResourcesUserRoomsList.class.php');
require_once ($RELATIVE_PATH_RESOURCES.'/lib/AssignObject.class.php');
require_once ($RELATIVE_PATH_RESOURCES.'/lib/AssignObjectPerms.class.php');
require_once ($RELATIVE_PATH_RESOURCES.'/lib/RoomRequest.class.php');

require_once ('lib/classes/cssClassSwitcher.inc.php');

$cssSw = new cssClassSwitcher;


/*****************************************************************************
EditResourceData, Darstellung der unterschiedlichen Forms zur
Bearbeitung eines Objects
/*****************************************************************************/
class EditResourceData {
    var $resObject;     //Das Oject an dem gearbeitet wird
    var $used_view;     //the used view

    //Konstruktor
    function EditResourceData ($resource_id) {
        $this->db=new DB_Seminar;
        $this->db2=new DB_Seminar;
        $this->resObject = ResourceObject::Factory($resource_id);
    }

    function setUsedView ($value) {
        $this->used_view = $value;
    }

    //private
    function selectCategories($select_rooms = TRUE) {
        if (!$select_rooms)
            $this->db->query("SELECT * FROM resources_categories WHERE is_room = 0 ORDER BY name");
        else
            $this->db->query("SELECT * FROM resources_categories ORDER BY name");
    }

    //private
    function selectProperties() {
        $this->db->query ("SELECT resources_properties.name, resources_properties.description, resources_properties.type, resources_properties.options, resources_properties.system, resources_properties.property_id  FROM resources_properties LEFT JOIN resources_categories_properties USING (property_id) LEFT JOIN resources_objects USING (category_id) WHERE resources_objects.resource_id = '".$this->resObject->getId()."' ");
        if (!$this->db->affected_rows())
            return FALSE;
        else
            return TRUE;
    }

    //private
    function selectFacultys($only_fak = TRUE) {
        $this->db->query ("SELECT Name, Institut_id, fakultaets_id  FROM Institute WHERE fakultaets_id = Institut_id ORDER BY name");
        if (!$this->db->affected_rows())
            return FALSE;
        else
            return TRUE;
    }

    //private
    function selectInstitutes($fak_id) {
        $this->db2->query ( "SELECT Name, Institut_id FROM Institute WHERE fakultaets_id = '$fak_id' AND  fakultaets_id != Institut_id ORDER BY name");
        if (!$this->db2->affected_rows())
            return FALSE;
        else
            return TRUE;
    }


    //private
    function selectPerms() {
        $this->db->query ("SELECT *  FROM resources_user_resources WHERE resource_id = '".$this->resObject->getId()."' ");
        if (!$this->db->affected_rows())
            return FALSE;
        else
            return TRUE;
    }

    function showScheduleForms($assign_id='') {
        global $PHP_SELF, $perm, $user, $resources_data, $new_assign_object, $search_user, $search_string_search_user,
            $CANONICAl_RELATIVE_PATH_STUDIP, $RELATIVE_PATH_RESOURCES, $cssSw, $view_mode,$quick_view, $add_ts,
            $search_exp_room, $search_room_x, $search_properties_x;

        $resReq = new RoomRequest();

        $killButton = TRUE;
        if ($new_assign_object)
            $resAssign = unserialize($new_assign_object);
        else
            $resAssign = AssignObject::Factory($assign_id);

        //workaround anoack: AssignObject::resource_id  must match the actual resource object
        if($resAssign->getResourceId() != $resources_data['actual_object']) {
            $resAssign = AssignObject::Factory(false);
        }
        //workaround anoack: new AssignObjects need a resource_id !
        if ($resAssign->isNew()){
            $resAssign->setResourceId($resources_data['actual_object']);
        }

        if (($add_ts) && ($resAssign->isNew())) {
            $resAssign->setBegin($add_ts);
            $resAssign->setEnd($add_ts + (2 * 60 * 60));
        }


        $owner_type = $resAssign->getOwnerType();

        //it is not allowed to edit or kill assigns for rooms here
        if (($owner_type == "sem") || ($owner_type == "date")) {
            $resObject = ResourceObject::Factory($resAssign->getResourceId());
            if ($resObject->isRoom()) {
                $lockedAssign=TRUE;
                $killButton = FALSE;
            }
        }


        //load the object perms
        $ResourceObjectPerms = ResourceObjectPerms::Factory($resAssign->getResourceId());

        //in some case, we load the perms from the assign object, if it has an owner
        if (($ResourceObjectPerms->getUserPerm() != "admin") && (!$resAssign->isNew()) && (!$new_assign_object)) {
            //load the assign-object perms of a saved object
            $SavedStateAssignObject = AssignObject::Factory($resAssign->getId());
            if ($SavedStateAssignObject->getAssignUserId()){
                unset($ObjectPerms);
                $ObjectPerms = new AssignObjectPerms($resAssign->getId());
            }
        }
        if (!isset($ObjectPerms)){
            $ObjectPerms =& $ResourceObjectPerms;
        }

        if ((!$ObjectPerms->havePerm("autor"))){ // && (!$resAssign->isNew()) && (!$new_assign_object)) {
            $killButton = FALSE;
            $lockedAssign = TRUE;
        }

        if ($resAssign->isNew()){
            $killButton = FALSE;
            if($ObjectPerms->getUserPerm() == 'autor' && !$resAssign->getAssignUserId()) {
                $resAssign->setAssignUserId($user->id);
            }
        }

        if ($resAssign->isNew() && $lockedAssign){
            echo MessageBox::info(_("Sie haben nicht die Berechtigung, für diese Resource eine Belegung zu erstellen."));
            return;
        }

        if ($lockedAssign) {
            if ($owner_type == "sem") {
                $seminarName = Seminar::GetInstance($resAssign->getAssignUserId())->getName();
                $seminarID = $resAssign->getAssignUserId();
            } elseif ($owner_type == "date") {
                $seminarID = Seminar::GetSemIdByDateId($resAssign->getAssignUserId());
                $seminarName = Seminar::GetInstance($seminarID)->getName();
            }
        }

        /* * * * * * * * * * * * * * * *
         * * * * T E M P L A T E * * * *
         * * * * * * * * * * * * * * * */
        $template = $GLOBALS['template_factory']->open('resources/show_schedule_forms.php');
        $template->set_attribute('used_view', $this->used_view);
        $template->set_attribute('db', $this->db);
        $change_schedule_move_or_copy = $_POST['change_schedule_move_or_copy'];
        echo $template->render(compact( 'resAssign', 'resources_data', 'view_mode', 'cssSw', 'lockedAssign', 'killButton', 
            'owner_type', 'perm', 'search_string_search_user', 'ResourceObjectPerms', 'search_exp_room', 'search_room_x',
            'search_properties_x', 'resReq', 'seminarName', 'seminarID','change_schedule_move_or_copy'));
    }


    function showPropertiesForms() {
        global $PHP_SELF, $cssSw, $user;

        $ObjectPerms = ResourceObjectPerms::Factory($this->resObject->getId());

        /* * * * * * * * * * * * * * * *
         * * * * T E M P L A T E * * * *
         * * * * * * * * * * * * * * * */
        $template = $GLOBALS['template_factory']->open('resources/show_properties_forms.php');
        $template->set_attribute('resObject', $this->resObject);
        $template->set_attribute('db', $this->db);
        $template->set_attribute('db2', $this->db2);
        $template->set_attribute('EditResourceData', $this);

        echo $template->render(compact( 'ObjectPerms', 'cssSw', 'user' ));
    }

    function showPermsForms() {
        global $PHP_SELF, $search_owner, $search_perm_user, $search_string_search_perm_user, $search_string_search_owner,
            $cssSw, $user;

        $ObjectPerms = ResourceObjectPerms::Factory($this->resObject->getId());

        $owner_perms = checkObjektAdministrablePerms ($this->resObject->getOwnerId());

        if ($owner_perms)
            $admin_perms = TRUE;
        else
            $admin_perms = ($ObjectPerms->havePerm("admin")) ? TRUE : FALSE;

        $selectPerms = $this->selectPerms();

        /* * * * * * * * * * * * * * * *
         * * * * T E M P L A T E * * * *
         * * * * * * * * * * * * * * * */
        $template = $GLOBALS['template_factory']->open('resources/show_perms_forms.php');
        $template->set_attribute('db', $this->db);
        $template->set_attribute('resObject', $this->resObject);

        echo $template->render(compact( 'search_owner', 'search_perm_user', 'search_string_search_perm_user', 'search_string_search_owner',
            'cssSw', 'user', 'admin_perms', 'owner_perms', 'ObjectPerms', 'selectPerms' ));
    }
}
