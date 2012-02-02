<?php
# Lifter001: DONE
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
admin_statusgruppe.php - Statusgruppen-Verwaltung von Stud.IP.
Copyright (C) 2008 Till Glöggler <tgloeggl@uos.de>

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
use Studip\Button, Studip\LinkButton;

require '../lib/bootstrap.php';
unregister_globals();
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", 'user' => "Seminar_User"));
$auth->login_if($auth->auth["uid"] == "nobody");
$perm->check("tutor");

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

require_once ('config.inc.php');
require_once ('lib/visual.inc.php');
require_once 'lib/functions.php';
require_once ('lib/admission.inc.php');
require_once ('lib/statusgruppe.inc.php');
require_once ('lib/datei.inc.php');
require_once ('lib/classes/Statusgruppe.class.php');
require_once ('lib/classes/LockRules.class.php');
require_once ('lib/classes/SeminarCategories.class.php');
require_once 'lib/admin_search.inc.php';

PageLayout::setHelpKeyword("Basis.VeranstaltungenVerwaltenGruppen");

PageLayout::setTitle(_("Verwaltung von Gruppen und Funktionen"));

if ($perm->have_perm('admin')) {
    Navigation::activateItem('/admin/course/groups');
} else {
    Navigation::activateItem('/course/members/edit_groups');
}

//get ID, if a object is open
if ($SessSemName[1])
  $range_id = $SessSemName[1];
elseif ($_REQUEST['range_id'])
    $range_id = $_REQUEST['range_id'];

URLHelper::bindLinkParam('range_id', $range_id);

//Change header_line if open object
$header_line = getHeaderLine($range_id);
if ($header_line)
  PageLayout::setTitle($header_line." - ".PageLayout::getTitle());

//Output starts here

include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   //hier wird der "Kopf" nachgeladen
include 'lib/include/admin_search_form.inc.php';

// Rechtecheck
$_range_type = get_object_type($range_id);
if ($_range_type != 'sem' || !$perm->have_studip_perm('tutor', $range_id)) {
    echo "</td></tr></table>";
    page_close();
    die;
}

if(LockRules::Check($range_id, 'groups')) {
        $lockdata = LockRules::getObjectRule($range_id);
        $msg = 'error§' . _("Die Gruppen / Funktionen dieser Veranstaltung dürfen nicht verändert werden.").'§';
        if ($lockdata['description']){
            $msg .= "info§" . formatLinks($lockdata['description']).'§';
        }
        ?>
        <table border=0 align="center" cellspacing=0 cellpadding=0 width="100%">
        <tr><td class="blank" colspan=2><br>
        <?
        parse_msg($msg);
        ?>
        </td></tr>
        </table>
        <?
        page_close();
        die();
}

// if we add persons to a statusgroup, we receive a role_id as an array-element
if (is_array($_REQUEST['role_id'])) {
    $_REQUEST['role_id'] = key($_REQUEST['role_id']);
}



/* * * * * * * * * * * * * * * * * *
 * H E L P E R   F U N C T I O N S *
 * * * * * * * * * * * * * * * * * */

/*
 * this function has to stay here for the moment, because in other files someone already uses this function name.
 */
function MovePersonStatusgruppe ($range_id, $role_id, $type, $persons, $workgroup_mode=FALSE) {
    global $perm;

    $mkdate = time();

    $db=new DB_Seminar;
    $db2=new DB_Seminar;

    if ($type == 'direct') {
        for ($i  = 0; $i < sizeof($persons); $i++) {
            $user_id = get_userid($persons[$i]);
            InsertPersonStatusgruppe ($user_id, $role_id);
        }
    } else if ($type == 'indirect') {
        for ($i = 0; $i < sizeof($persons); $i++) {
            $user_id = get_userid($persons[$i]);
            $writedone = InsertPersonStatusgruppe ($user_id, $role_id);
            if ($writedone) {
                if ($workgroup_mode == TRUE) {
                    $globalperms = get_global_perm($user_id);
                    if ($globalperms == "tutor" || $globalperms == "dozent") {
                        insert_seminar_user($range_id, $user_id, "tutor");
                    } else {
                        insert_seminar_user($range_id, $user_id, "autor");
                    }
                } else {
                    insert_seminar_user($range_id, $user_id, "autor");
                }
            }
            checkExternDefaultForUser($user_id);
        }
    } else if ($type == 'search') {
        if ($persons != "") {
            for ($i  = 0; $i < sizeof($persons); $i++) {
                $user_id = get_userid($persons[$i]);
                $writedone = InsertPersonStatusgruppe ($user_id, $role_id);
                if ($writedone) {
                    if ($workgroup_mode == TRUE) {
                        $globalperms = get_global_perm($user_id);
                        if ($globalperms == "tutor" || $globalperms == "dozent") {
                            insert_seminar_user($range_id, $user_id, "tutor");
                        } else {
                            insert_seminar_user($range_id, $user_id, "autor");
                        }
                    } else {
                        insert_seminar_user($range_id, $user_id, "autor");
                    }

                }
            }
        }
    }
}

/* * * * * * * * * * * * * * * *
 * * * C O N T R O L L E R * * *
 * * * * * * * * * * * * * * * */

// initialize array for possible messages. Important, array_merge won't work otherwise!
$msgs = array();

// activation and deactvation of options for the statusgroups
if ($_REQUEST['cmd'] == 'activateSelfAssignAll') {
    SetSelfAssignAll($range_id, true);
    $msgs['msg'][] = _("Selbsteintrag in allen Gruppen wurde eingeschaltet!");
}

if ($_REQUEST['cmd'] == 'deactivateSelfAssignAll') {
    SetSelfAssignAll($range_id, false);
    $msgs['msg'][] = _("Selbsteintrag in allen Gruppen wurde ausgeschaltet!");
}

if ($_REQUEST['cmd'] == 'deactivateSelfAssignExclusive') {
    SetSelfAssignExclusive($range_id, false);
    $msgs['msg'][] = _("Selbsteintrag in nur einer Gruppe erlauben wurde ausgeschaltet!");
}

if ($_REQUEST['cmd'] == 'activateSelfAssignExclusive') {
    SetSelfAssignExclusive($range_id, true);

    $check_multiple = CheckStatusgruppeMultipleAssigns($range_id);
    if (count($check_multiple)) {
        $multis = '<ul>';
        foreach ($check_multiple as $one) {
            $multis .= '<li>' . htmlReady(get_fullname($one['user_id']) . ' ('. $one['gruppen'] . ')').'</li>';
        }
        $multis .= '</ul>';
        $msgs['error'][] = _("Achtung, folgende Teilnehmer sind bereits in mehr als einer Gruppe eingetragen. Sie müssen die Eintragungen manuell korrigieren, um den exklusiven Selbsteintrag einzuschalten.")
            . '<br>'. $multis;
        SetSelfAssignExclusive($range_id, false);
    } else {
        $msgs['msg'][] = _("Selbsteintrag in nur einer Gruppe erlauben wurde eingeschaltet!");
    }
}

// change the position of two adjacent statusgroups
if ($_REQUEST['cmd'] == 'swapRoles') {
    resortStatusgruppeByRangeId($range_id);
    SwapStatusgruppe($_REQUEST['role_id']);
}

// change sort-order of a person in a statsgroup
if ($_REQUEST['cmd'] == 'move_up') {
    MovePersonPosition ($_REQUEST['username'], $_REQUEST['role_id'], "up");
}

if ($_REQUEST['cmd'] == 'move_down') {
    MovePersonPosition ($_REQUEST['username'], $_REQUEST['role_id'], "down");
}

// sort the persons of a statusgroup by their family name
if ($_REQUEST['cmd'] == 'sortByName') {
    sortStatusgruppeByName($_REQUEST['role_id']);
}

// add a person to a statusgroup
$personsAdded = false;

// the person is participant (if we administrate a seminar), or the person is member (if we administrate an institute)
if (is_array($_REQUEST['seminarPersons'])) {
    MovePersonStatusgruppe ($range_id, $_REQUEST['role_id'], 'direct', $_REQUEST['seminarPersons'], $workgroup_mode);
    $personsAdded = true;
}

// only for seminars - the person is member of the institute the seminar is in
if (is_array($_REQUEST['institutePersons'])) {
    MovePersonStatusgruppe ($range_id, $_REQUEST['role_id'], 'indirect', $_REQUEST['institutePersons'], $workgroup_mode);
    $personsAdded = true;
}

// the person shall be added via the free search
if (isset($_REQUEST['searchPersons'])) {
    MovePersonStatusgruppe ($range_id, $_REQUEST['role_id'], 'search', $_REQUEST['searchPersons'], $workgroup_mode);
    $personsAdded = true;
}

if ($personsAdded) {
    $msgs['msg'][] = _("Die Personen wurden der Gruppe hinzugefügt.");
}

// delete a person from a statusgroup
if ($_REQUEST['cmd'] == 'removePerson') {
    $msgs['msg'][] = _("Die Person wurde aus der Gruppe entfernt!");
    RemovePersonStatusgruppe ($_REQUEST['username'], $_REQUEST['role_id']);
}

// edit the data of a role
if ($_REQUEST['cmd'] == 'doEditRole') {
    $statusgruppe = new Statusgruppe($_REQUEST['role_id']);
    $name = htmlReady($statusgruppe->getName());
    if ($statusgruppe->checkData()) {
        $msgs['info'][] = sprintf(_("Die Daten der Gruppe %s wurden geändert!"), '<b>'. $name .'</b>');
    }
    $statusgruppe->store();
    $msgs = $statusgruppe->getMessages($msgs);
}

// ask, if the user really intends to delete the role
if ($_REQUEST['cmd'] == 'deleteRole') {
    $statusgruppe = new Statusgruppe($_REQUEST['role_id']);
    if ($_REQUEST['really']) {
        $msgs['msg'][] = sprintf(_("Die Gruppe %s wurde gelöscht!"), htmlReady($statusgruppe->getName()));
        $statusgruppe->delete();
    } else {
        $msgs['info'][] = sprintf(_("Sind Sie sicher, dass Sie die Gruppe %s löschen möchten?"), '<b>'. htmlReady($statusgruppe->getName()) .'</b>')
            . '<br>'
            . LinkButton::createAccept(_('JA!'), URLHelper::getURL('', array('cmd' => 'deleteRole', 'really' => 'true', 'role_id' => Request::get('role_id'))))
            . '&nbsp;&nbsp;&nbsp;&nbsp;'
            . LinkButton::createCancel(_('NEIN!'), URLHelper::getURL(''));
    }
}

// adding a new role
if ($_REQUEST['cmd'] == 'addRole' && !Request::submitted('choosePreset')) {
    // to prevent url-hacking for changing the data of an existing role
    $role_id = md5(uniqid(rand()));
    if (!Statusgruppe::roleExists($role_id)) {
        $new_role = new Statusgruppe();

        // this is necessary, because it could be the second try to add after the user has corrected errors
        $new_role->setStatusgruppe_Id($role_id);
        $new_role->setRange_Id($range_id);

        if ($new_role->checkData()) {
            // show a hint if a role with the same name already exists
            if (Statusgruppe::countByName($new_role->getName(), $new_role->getRange_Id()) > 0) {
                $msgs['info'][] = sprintf(_("Die Gruppe %s wurde hinzugefügt, es gibt jedoch bereits ein Gruppe mit demselben Namen!"), '<b>'. htmlReady($new_role->getName()) .'</b>');
            } else {
                $msgs['msg'][] = sprintf(_("Die Gruppe %s wurde hinzugefügt!"), '<b>'. htmlReady($new_role->getName()) .'</b>');
            }

            $new_role->store();
        }

        $msgs = $new_role->getMessages($msgs);
    }
}


// get the option-values for the statusgroup-options
list($self_assign_all, $self_assign_exclusive) = CheckSelfAssignAll($range_id);

// if self_assign_exclusive is activated, check always for inconsistency (this check needs to be after all commands)
if ($self_assign_exclusive) {
    // activating exclusive self-assign is not possible with mutlitple assigned users
    $check_multiple = CheckStatusgruppeMultipleAssigns($range_id);
    if (count($check_multiple)) {
        $multis = '<ul>';
        foreach ($check_multiple as $one) {
            $multis .= '<li>' . htmlReady(get_fullname($one['user_id']) . ' ('. $one['gruppen'] . ')').'</li>';
        }
        $multis .= '</ul>';
        $msgs['error'][] = _("Achtung, der exklusive Selbsteintrag wurde ausgeschaltet, da folgende Teilnehmer in mehr als einer Gruppe eingetragen sind. Sie müssen die Eintragungen manuell korrigieren, um den exklusiven Selbsteintrag wieder einzuschalten.")
            . '<br>'. $multis;
        SetSelfAssignExclusive($range_id, false);
    }
}


/* * * * * * * * * * * * * * * *
 * * * *     V I E W     * * * *
 * * * * * * * * * * * * * * * */

// get statusgroups, to check if there are any
$statusgruppen = GetAllStatusgruppen($range_id);

// do we have some roles already?
if ($statusgruppen && sizeof($statusgruppen) > 0) {
    // open the template for tree-view of roles
    $template = $GLOBALS['template_factory']->open('statusgruppen/sem_content');

    // the layout defines where the infobox is located
    $template->set_layout('statusgruppen/sem_layout.php');

    $template->set_attribute('range_id', $range_id);

    // the persons of the institute who can be added directly
    $template->set_attribute('seminar_persons', getPersons($range_id, 'sem'));
    $template->set_attribute('inst_persons', getPersons($range_id, 'inst'));

    $template->set_attribute('messages', $msgs);

    // all statusgroups in a tree-structured array
    $template->set_attribute('roles', $statusgruppen);

    // set the options for the box
    list($self_assign_all, $self_assign_exclusive) = CheckSelfAssignAll($range_id);
    $template->set_attribute('self_assign_all', $self_assign_all);
    $template->set_attribute('self_assign_exclusive', $self_assign_exclusive);

    $template->set_attribute('seminar_class', SeminarCategories::GetBySeminarId($range_id)->id);

    if ($_REQUEST['cmd'] == 'editRole') {
        $role = new Statusgruppe($_REQUEST['role_id']);
        $template->set_attribute('role_data', $role->getData());
        $template->set_attribute('edit_role', $role->getId());
    } else if (Request::submitted('choosePreset')) {
        $template->set_attribute('role_data', array('name' => $_REQUEST['presetName']));
    }
    $template->set_attribute('show_search_and_members_form', !LockRules::Check($range_id, 'participants'));
    // show the tree-view of the statusgroups
    echo $template->render();


}

// there are no roles yet, so we show some informational text
else {
    $template = $GLOBALS['template_factory']->open('statusgruppen/sem_no_statusgroups');

    // the layout defines where the infobox is located
    $template->set_layout('statusgruppen/sem_layout.php');

    $template->set_attribute('range_id', $range_id);
    $template->set_attribute('seminar_class', SeminarCategories::GetBySeminarId($range_id)->id);

    if (Request::submitted('choosePreset')) {
        $template->set_attribute('role_data', array('name' => $_REQUEST['presetName']));
    }

    // no parameters necessary, just display a static page
    echo $template->render();
}

// Ende Gruppenuebersicht
include ('lib/include/html_end.inc.php');

// Ende Darstellungsteil
page_close();
