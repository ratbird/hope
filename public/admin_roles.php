<?php
# Lifter001: DONE
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TEST
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
require_once 'lib/admin_search.inc.php';

/*
 * no admin help yet (cf. http://develop.studip.de/trac/ticket/475 )
 * PageLayout::setHelpKeyword("Basis.EinrichtungenVerwaltenGruppen");
 */
PageLayout::setHelpKeyword("Basis.Allgemeines");
PageLayout::setTitle(_("Verwaltung von Funktionen und Gruppen"));

Navigation::activateItem('/admin/institute/groups');

//get ID, if a object is open
if ($SessSemName[1])
  $range_id = $SessSemName[1];
elseif (Request::option('range_id'))
  $range_id = Request::option('range_id');

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
if (!$perm->have_studip_perm("admin", $range_id) || ($_range_type != 'inst' && $_range_type != 'fak')) {
    echo "</td></tr></table>";
    page_close();
    die;
}




/* * * * * * * * * * * * * * * * * *
 * H E L P E R   F U N C T I O N S *
 * * * * * * * * * * * * * * * * * */

/*
 * this function has to stay here for the moment, because in other files someone already uses this function name.
 */
function MovePersonStatusgruppe ($range_id, $role_id, $type, $persons, $workgroup_mode=FALSE) {
    global $perm;

    if ($type == 'direct') {
        for ($i  = 0; $i < sizeof($persons); $i++) {
            $user_id = get_userid($persons[$i]);
            InsertPersonStatusgruppe ($user_id, $role_id);
        }
    } else if ($type == 'indirect') {
        foreach ($persons as $name) {
            $user_id = get_userid($name);
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
                    $globalperms = get_global_perm($user_id);

                    log_event('INST_USER_ADD', $range_id ,$user_id, $globalperms);

                    if ($perm->get_studip_perm($range_id, $user_id) == FALSE) {
                        $query = "INSERT INTO user_inst (Institut_id, user_id, inst_perms) VALUES (?, ?, ?)";
                        $statement = DBManager::get()->prepare($query);
                        $statement->execute(array($range_id, $user_id, $globalperms));
                    }
                    if ($perm->get_studip_perm($range_id, $user_id) =="user") {
                        $query = "UPDATE user_inst SET inst_perms = ? WHERE user_id = ? AND Institut_id = ?";
                        $statement = DBManager::get()->prepare($query);
                        $statement->execute(array($globalperms, $user_id, $range_id));
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

$open = false;

$lockrule = LockRules::getObjectRule($range_id);
if ($lockrule->description && LockRules::Check($range_id, 'groups')) {
    $msgs['info'][] = formatLinks($lockrule->description);
}

// a role_id has been submitted, so open that role automatically
if (Request::option('role_id')) {
    $open = Request::option('role_id');
}

// move a role up or down
if (Request::option('cmd') == 'moveUp') {
    $role = new Statusgruppe(Request::option('role_id'));
    resortStatusgruppeByRangeId($role->getRange_Id());
    moveStatusgruppe(Request::option('role_id'), 'up');
}

if (Request::option('cmd') == 'moveDown') {
    $role = new Statusgruppe(Request::option('role_id'));
    resortStatusgruppeByRangeId($role->getRange_Id());
    moveStatusgruppe(Request::option('role_id'), 'down');
}


// change sort-order of a person in a statsgroup
if (Request::option('cmd') == 'move_up') {
    MovePersonPosition (Request::quoted('username'), Request::option('role_id'), "up");
}

if (Request::option('cmd') == 'move_down') {
    MovePersonPosition (Request::quoted('username'), Request::option('role_id'), "down");
}

// a chosen person is sorted in after a second chosen person - allows to sort arbitrary over long distances
if (Request::option('cmd') == 'sort_person') {
    $sort_person = Request::quotedArray('sort_person');
    if (empty($sort_person)) {
        $msgs['error'][] = _("Bitte wählen Sie die Person aus, die einsortiert werden soll!");
    } else {
        $do_person_sort = Request::quotedArray('do_person_sort');
        if (!empty ($do_person_sort)) {
            // do_person_sort is an array and we need the key of the first element
            foreach ($do_person_sort as $zw_uid => $trash) {
                // the username of the user after which is inserted
                $u_insert_after = $zw_uid;
                break;
            }

            // the username of the user to be inserted
            $u_insert = array_shift($sort_person);

            SortPersonInAfter(get_userid($u_insert), get_userid($u_insert_after), Request::option('role_id'));
        }
    }

}

// sort the persons of a statusgroup by their family name
if (Request::option('cmd') == 'sortByName') {
    sortStatusgruppeByName(Request::option('role_id'));
}

// add a person to a statusgroup
// the person is participant (if we administrate a seminar), or the person is member (if we administrate an institute)
if (Request::option('cmd') == 'addPersonsToRoleDirect' ) {
    $msgs['msg'][]  = _("Die Personen wurden der Gruppe hinzugefügt.");
    $msgs['info'][] = _("Beachten Sie, dass für die Personen die Standarddaten der Einrichtung übernommen wurden!");

    MovePersonStatusgruppe ($range_id, Request::option('role_id'), 'direct', Request::quotedArray('persons_to_add'), $workgroup_mode);
}

// only for seminars - the person is member of the institute the seminar is in
if (Request::option('cmd') == 'addPersonsToRoleIndirect' ) {
    $msgs['msg'][]  = _("Die Personen wurden der Gruppe hinzugefügt.");
    $msgs['info'][] = _("Beachten Sie, dass für die Personen die Standarddaten der Einrichtung übernommen wurden!");

    MovePersonStatusgruppe ($range_id, Request::option('role_id'), 'indirect', Request::quotedArray('persons_to_add'), $workgroup_mode);
}

// the person shall be added via the free search
if (Request::option('cmd') == 'addPersonsToRoleSearch' ) {
    $msgs['msg'][]  = _("Die Personen wurden der Gruppe hinzugefügt.");
    $msgs['info'][] = _("Beachten Sie, dass für die Personen die Standarddaten der Einrichtung übernommen wurden!");

    MovePersonStatusgruppe ($range_id, Request::option('role_id'), 'search', Request::quotedArray('persons_to_add'), $workgroup_mode);
}

// delete a person from a statusgroup
if (Request::option('cmd') == 'removePerson') {
    $msgs['msg'][] = _("Die Person wurde aus der Gruppe entfernt!");
    RemovePersonStatusgruppe (Request::quoted('username'), Request::option('role_id'));
}

// edit the data of a role
if (Request::option('cmd') == 'editRole') {
    $statusgruppe = new Statusgruppe(Request::option('role_id'));
    $name = htmlReady($statusgruppe->getName());
    if ($statusgruppe->checkData()) {
        $msgs['info'][] = sprintf(_("Die Daten der Gruppe %s wurden geändert!"), '<b>'. $name .'</b>');
    }
    $statusgruppe->store();
    $msgs = $statusgruppe->getMessages($msgs);
}

// ask, if the user really intends to delete the role
if (Request::option('cmd') == 'deleteRole') {
    $statusgruppe = new Statusgruppe(Request::option('role_id'));
    if (Request::quoted('really')) {
        $msgs['msg'][] = sprintf(_("Die Gruppe %s wurde gelöscht!"), htmlReady($statusgruppe->getName()));
        $statusgruppe->delete();
    } else {
        echo createQuestion(sprintf(_("Sind Sie sicher, dass Sie die Gruppe **%s** löschen möchten?"), $statusgruppe->getName() ),
            array('cmd' => 'deleteRole', 'really' => 'true', 'role_id' => Request::option('role_id')),
            array('role_id' => Request::option('role_id'))
        );
    }
}

// adding a new role
$displayNewRole = false;

if (Request::option('cmd') == 'newRole') {
    $displayNewRole = true;
    $new_role = new Statusgruppe();
    $new_role->setRange_Id($range_id);
}

if (Request::option('cmd') == 'addRole') {
    // to prevent url-hacking for changing the data of an existing role
    if (!Statusgruppe::roleExists(Request::option('role_id'))) {
        $new_role = new Statusgruppe();

        // this is necessary, because it could be the second try to add after the user has corrected errors
        $new_role->setStatusgruppe_Id(Request::option('role_id'));
        $new_role->setRange_Id($range_id);

        if ($new_role->checkData()) {
            $new_role->store();
            $open = $new_role->getId();
            $msgs['msg'][] = sprintf(_("Die Gruppe %s wurde hinzugefügt!"), '<b>'. htmlReady($new_role->getName()) .'</b>');
        } else {
            $displayNewRole = true;
        }

        $msgs = $new_role->getMessages($msgs);
    }
}



/* * * * * * * * * * * * * * * *
 * * * *     V I E W     * * * *
 * * * * * * * * * * * * * * * */

// get statusgroups, to check if there are any
$statusgruppen = GetAllStatusgruppen($range_id);

// are we in the newRole-mode?
if ($displayNewRole) {
    // open the template for inserting a new statusgroup
    $template = $GLOBALS['template_factory']->open('statusgruppen/new_role');

    $template->set_attribute('range_id', $range_id);

    // the layout defines where the infobox is located
    $template->set_layout('statusgruppen/layout.php');

    // pass the messages to the infobox
    $template->set_attribute('messages', $msgs);

    // the role, emtpy and fresh or prefilled with posted data
    $template->set_attribute('role_data', $new_role->getData());
    $template->set_attribute('role', $new_role);

    // all statusgroups in a tree-structured array
    $template->set_attribute('all_roles', $statusgruppen);

    // show the formula for entering a new statusgroup
    echo $template->render();


}

// do we have some roles already?
else if ($statusgruppen && sizeof($statusgruppen) > 0) {
    // open the template for tree-view of roles
    $template = $GLOBALS['template_factory']->open('statusgruppen/roles');

    // the layout defines where the infobox is located
    $template->set_layout('statusgruppen/layout.php');

    // the ids of the currently opened statusgroups
    $template->set_attribute('open', $open);

    $template->set_attribute('range_id', $range_id);

    // the persons of the institute who can be added directly
    $template->set_attribute('inst_persons', getPersons($range_id));

    if (Request::option('view') == 'editRole') {
        $template->set_attribute('editRole', Request::option('role_id'));
    }

    if (Request::option('view') == 'startMove') {
        $template->set_attribute('move', true);
        $template->set_attribute('move_id', Request::option('role_id'));
    }

    if (Request::option('view') == 'sort') {
        $template->set_attribute('sort', true);
    }

    $template->set_attribute('messages', $msgs);

    // all statusgroups in a tree-structured array
    $template->set_attribute('roles', $statusgruppen);

    // show the tree-view of the statusgroups
    echo $template->render();


}

// there are no roles yet, so we show some informational text
else {
    $template = $GLOBALS['template_factory']->open('statusgruppen/no_statusgroups');

    // the layout defines where the infobox is located
    $template->set_layout('statusgruppen/layout.php');

    $template->set_attribute('range_id', $range_id);

    // no parameters necessary, just display a static page
    echo $template->render();
}

// Ende Gruppenuebersicht
include ('lib/include/html_end.inc.php');

// Ende Darstellungsteil
page_close();
