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
require_once ('lib/classes/SeminarCategories.class.php');
require_once 'lib/admin_search.inc.php';

PageLayout::setHelpKeyword("Basis.VeranstaltungenVerwaltenGruppen");

PageLayout::setTitle(_("Verwaltung von Funktionen und Gruppen"));

if ($perm->have_perm('admin')) {
    Navigation::activateItem('/admin/course/groups');
} else {
    Navigation::activateItem('/course/members/edit_groups');
}

//get ID, if a object is open
if ($SessSemName[1])
  $range_id = $SessSemName[1];
elseif (Request::option('range_id'))
    $range_id = Request::option('range_id');

URLHelper::bindLinkParam('range_id', $range_id);
URLHelper::setBaseURL($GLOBALS['ABSOLUTE_URI_STUDIP']);

//Change header_line if open object
$header_line = getHeaderLine($range_id);
if ($header_line)
  PageLayout::setTitle($header_line." - ".PageLayout::getTitle());

//Output starts here

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


/* * * * * * * * * * * * * * * * * *
 * H E L P E R   F U N C T I O N S *
 * * * * * * * * * * * * * * * * * */

/*
 * this function has to stay here for the moment, because in other files someone already uses this function name.
 */
function MovePersonStatusgruppe ($range_id, $role_id, $type, $persons, $workgroup_mode=FALSE) {
    global $perm;

    $mkdate = time();

    if ($type == 'direct') {
        for ($i  = 0; $i < sizeof($persons); $i++) {
            $user_id = get_userid($persons[$i]);
            InsertPersonStatusgruppe ($user_id, $role_id, false);
        }
    } else if ($type == 'indirect') {
        for ($i = 0; $i < sizeof($persons); $i++) {
            $user_id = get_userid($persons[$i]);
            $writedone = InsertPersonStatusgruppe ($user_id, $role_id, false);
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
                $writedone = InsertPersonStatusgruppe ($user_id, $role_id, false);
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

/*
 * Add persons to a statusgroup. This function is used by the multi 
 * person search dialog.
 */
function addToStatusgroup($range_id, $statusgruppe_id, $workgroup_mode) {
    $mp = MultiPersonSearch::load("contacts_statusgroup_" . $statusgruppe_id);
    if (count($mp->getAddedUsers()) !== 0) {
        
        foreach ($mp->getAddedUsers() as $m) {
            $quickfilters = $mp->getQuickfilterIds();
            if (in_array($m, $quickfilters[_("VeranstaltungsteilnehmerInnen")])) {
                InsertPersonStatusgruppe ($m, $statusgruppe_id, false);
            } elseif (in_array($m, $quickfilters[_("MitarbeiterInnen")])) {
                $writedone = InsertPersonStatusgruppe ($m, $statusgruppe_id, false);
                if ($writedone) {
                    if ($workgroup_mode == TRUE) {
                        $globalperms = get_global_perm($m);
                        if ($globalperms == "tutor" || $globalperms == "dozent") {
                            insert_seminar_user($range_id, $m, "tutor");
                        } else {
                            insert_seminar_user($range_id, $m, "autor");
                        }
                    } else {
                        insert_seminar_user($range_id, $m, "autor");
                    }
                }
                checkExternDefaultForUser($m);
            } else {
                $writedone = InsertPersonStatusgruppe ($m, $statusgruppe_id, false);
                if ($writedone) {
                    if ($workgroup_mode == TRUE) {
                        $globalperms = get_global_perm($m);
                        if ($globalperms == "tutor" || $globalperms == "dozent") {
                            insert_seminar_user($range_id, $m, "tutor");
                        } else {
                            insert_seminar_user($range_id, $m, "autor");
                        }
                    } else {
                        insert_seminar_user($range_id, $m, "autor");
                    }
                }
            }
        }
    }
    $mp->clearSession();
}

/* * * * * * * * * * * * * * * *
 * * * C O N T R O L L E R * * *
 * * * * * * * * * * * * * * * */

// initialize array for possible messages. Important, array_merge won't work otherwise!
$msgs = array();

// activation and deactvation of options for the statusgroups
if (Request::option('cmd') == 'activateSelfAssignAll') {
    SetSelfAssignAll($range_id, true);
    $message = _('Selbsteintrag in allen Gruppen wurde eingeschaltet!');
    PageLayout::postMessage(MessageBox::success($message));
}

if (Request::option('cmd') == 'deactivateSelfAssignAll') {
    SetSelfAssignAll($range_id, false);
    $message = _('Selbsteintrag in allen Gruppen wurde ausgeschaltet!');
    PageLayout::postMessage(MessageBox::success($message));
}

if (Request::option('cmd') == 'deactivateSelfAssignExclusive') {
    SetSelfAssignExclusive($range_id, false);
    $message = _('Selbsteintrag in nur einer Gruppe wurde ausgeschaltet!');
    PageLayout::postMessage(MessageBox::success($message));
}

if (Request::option('cmd') == 'activateSelfAssignExclusive') {
    SetSelfAssignExclusive($range_id, true);

    $check_multiple = CheckStatusgruppeMultipleAssigns($range_id);
    if (count($check_multiple)) {
        $multis = '<ul>';
        foreach ($check_multiple as $one) {
            $multis .= '<li>' . htmlReady(get_fullname($one['user_id']) . ' ('. $one['gruppen'] . ')').'</li>';
        }
        $multis .= '</ul>';
        SetSelfAssignExclusive($range_id, false);

        $message = _('Achtung, folgende Teilnehmer sind bereits in mehr als einer Gruppe eingetragen. Sie müssen die Eintragungen manuell korrigieren, um den exklusiven Selbsteintrag einzuschalten.');
        $message .= '<br>' . $multis;
        PageLayout::postMessage(MessageBox::error($message));

    } else {
        $message = _('Selbsteintrag in nur einer Gruppe erlauben wurde eingeschaltet!');
        PageLayout::postMessage(MessageBox::success($message));
    }
}

// change the position of two adjacent statusgroups
if (Request::option('cmd') == 'swapRoles') {
    resortStatusgruppeByRangeId($range_id);
    SwapStatusgruppe(Request::option('role_id'));
}

// change sort-order of a person in a statsgroup
if (Request::option('cmd') == 'move_up') {
    MovePersonPosition (Request::quoted('username'), Request::option('role_id'), "up");
}

if (Request::option('cmd') == 'move_down') {
    MovePersonPosition (Request::quoted('username'), Request::option('role_id'), "down");
}

// sort the persons of a statusgroup by their family name
if (Request::option('cmd') == 'sortByName') {
    sortStatusgruppeByName(Request::option('role_id'));
}

// add persons to a statusgroup
foreach (GetAllStatusgruppen($range_id) as $id => $role) {
    addToStatusgroup($range_id, $id, $workgroup_mode);
}

// delete a person from a statusgroup
if (Request::option('cmd') == 'removePerson') {
    RemovePersonStatusgruppe (Request::quoted('username'), Request::option('role_id'));

    $message = _('Die Person wurde aus der Gruppe entfernt!');
    PageLayout::postMessage(MessageBox::success($message));
}

// edit the data of a role
if (Request::option('cmd') == 'doEditRole') {
    $statusgruppe = new Statusgruppe(Request::option('role_id'));
    $name = htmlReady($statusgruppe->getName());
    if ($statusgruppe->checkData()) {
        $message = sprintf(_('Die Daten der Gruppe %s wurden geändert!'),
                           '<b>'. htmlReady($name) .'</b>');
        PageLayout::postMessage(MessageBox::info($message));
    }
    $statusgruppe->store();
    $statusgruppe->getMessages(array());
}

// ask, if the user really intends to delete the role
if (Request::option('cmd') == 'deleteRole') {
    $statusgruppe = new Statusgruppe(Request::option('role_id'));
    if (Request::get('really')) {
        $statusgruppe->delete();
        $message = sprintf(_('Die Gruppe %s wurde gelöscht!'),
                           htmlReady($statusgruppe->getName()));
        PageLayout::postMessage(MessageBox::success($message));
    } else {
        $message  = sprintf(_('Sind Sie sicher, dass Sie die Gruppe %s löschen möchten?'),
                            '<b>'. htmlReady($statusgruppe->getName()) .'</b>');
        $message .= '<br>';
        $message .= LinkButton::createAccept(_('JA!'), URLHelper::getURL('', array('cmd' => 'deleteRole', 'really' => 'true', 'role_id' => Request::option('role_id'))));
        $message .= '&nbsp;&nbsp;&nbsp;&nbsp;';
        $message .= LinkButton::createCancel(_('NEIN!'), URLHelper::getURL(''));
        
        PageLayout::postMessage(MessageBox::info($message));
    }
}

// adding a new role
if (Request::option('cmd') == 'addRole' && !Request::submitted('choosePreset')) {
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
                $message = sprintf(_('Die Gruppe %s wurde hinzugefügt, es gibt jedoch bereits eine Gruppe mit demselben Namen!'),
                                   '<b>'. htmlReady($new_role->getName()) .'</b>');
                PageLayout::postMessage(MessageBox::info($message));
            } else {
                $message = sprintf(_('Die Gruppe %s wurde hinzugefügt!'),
                                   '<b>'. htmlReady($new_role->getName()) .'</b>');
                PageLayout::postMessage(MessageBox::success($message));
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
        
        $message  = _('Achtung, der exklusive Selbsteintrag wurde ausgeschaltet, da folgende Teilnehmer in mehr als einer Gruppe eingetragen sind. Sie müssen die Eintragungen manuell korrigieren, um den exklusiven Selbsteintrag wieder einzuschalten.');
        $message .= '<br>' . $multis;
        PageLayout::postMessage(MessageBox::error($message));

        SetSelfAssignExclusive($range_id, false);
    }
}


/* * * * * * * * * * * * * * * *
 * * * *     V I E W     * * * *
 * * * * * * * * * * * * * * * */

Helpbar::get()->load('statusgruppen/admin');

list($self_assign_all, $self_assign_exclusive) = CheckSelfAssignAll($range_id);

$sidebar = Sidebar::get();
$sidebar->setImage('sidebar/group-sidebar.png');

// get statusgroups, to check if there are any
$statusgruppen = GetAllStatusgruppen($range_id);

// do we have some roles already?
if ($statusgruppen && sizeof($statusgruppen) > 0) {
    $actions = new ActionsWidget();

    $actions->addLink(_('Selbsteintragung in allen Gruppen aktiviert?'),
                      URLHelper::getLink($self_assign_all ? '?cmd=deactivateSelfAssignAll' : '?cmd=activateSelfAssignAll'),
                      $self_assign_all ? 'icons/16/black/checkbox-checked.png' : 'icons/16/black/checkbox-unchecked.png');

    $actions->addLink(_('Selbsteintragung in nur einer Gruppe aktiviert?'),
                      URLHelper::getLink($self_assign_exclusive ? '?cmd=deactivateSelfAssignExclusive' : '?cmd=activateSelfAssignExclusive'),
                      $self_assign_exclusive ? 'icons/16/black/checkbox-checked.png' : 'icons/16/black/checkbox-unchecked.png');

    $sidebar->addWidget($actions);

    // open the template for tree-view of roles
    $template = $GLOBALS['template_factory']->open('statusgruppen/sem_content');
    $template->set_layout('layouts/base.php');

    $template->set_attribute('range_id', $range_id);

    // the persons of the institute who can be added directly
    $template->set_attribute('seminar_persons', getPersons($range_id, 'sem'));
    $template->set_attribute('inst_persons', getPersons($range_id, 'inst'));

    // all statusgroups in a tree-structured array
    $template->set_attribute('roles', $statusgruppen);

    // set the options for the box
    list($self_assign_all, $self_assign_exclusive) = CheckSelfAssignAll($range_id);
    $template->set_attribute('self_assign_all', $self_assign_all);
    $template->set_attribute('self_assign_exclusive', $self_assign_exclusive);

    $template->set_attribute('seminar_class', SeminarCategories::GetBySeminarId($range_id)->id);

    if (Request::option('cmd') == 'editRole') {
        $role = new Statusgruppe(Request::option('role_id'));
        $template->set_attribute('role_data', $role->getData());
        $template->set_attribute('edit_role', $role->getId());
    } else if (Request::submitted('choosePreset')) {
        $template->set_attribute('role_data', array('name' => Request::quoted('presetName')));
    }
    $template->set_attribute('show_search_and_members_form', !LockRules::Check($range_id, 'participants'));
    
    // quickfilters
    foreach (getPersons($range_id, 'sem') as $k=>$v) {
        $quickfilter_sem[] = $k;
    }
    $template->set_attribute('quickfilter_sem', $quickfilter_sem);
    foreach (getPersons($range_id, 'inst') as $k=>$v) {
        $quickfilter_inst[] = $k;
    }
    $template->set_attribute('quickfilter_inst', $quickfilter_inst);
    // search
    $search_obj = new SQLSearch("SELECT auth_user_md5.user_id, {$GLOBALS['_fullname_sql']['full_rev']} as fullname, username, perms "
                            . "FROM auth_user_md5 "
                            . "LEFT JOIN user_info ON (auth_user_md5.user_id = user_info.user_id) "
                            . "WHERE "
                            . "username LIKE :input OR Vorname LIKE :input "
                            . "OR CONCAT(Vorname,' ',Nachname) LIKE :input "
                            . "OR CONCAT(Nachname,' ',Vorname) LIKE :input "
                            . "OR Nachname LIKE :input OR {$GLOBALS['_fullname_sql']['full_rev']} LIKE :input "
                            . " ORDER BY fullname ASC",
                            _("Nutzer suchen"), "user_id");
    $template->set_attribute('search_obj', $search_obj);
    // show the tree-view of the statusgroups
    echo $template->render();


}

// there are no roles yet, so we show some informational text
else {
    $template = $GLOBALS['template_factory']->open('statusgruppen/sem_no_statusgroups');
    $template->set_layout('layouts/base.php');

    $template->set_attribute('range_id', $range_id);
    $template->set_attribute('seminar_class', SeminarCategories::GetBySeminarId($range_id)->id);

    if (Request::submitted('choosePreset')) {
        $template->set_attribute('role_data', array('name' => Request::quoted('presetName')));
    }

    // no parameters necessary, just display a static page
    echo $template->render();
}

page_close();
