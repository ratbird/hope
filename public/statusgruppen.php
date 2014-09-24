<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
statusgruppen.php - Statusgruppen-Anzeige von Stud.IP.
Copyright (C) 2002 Ralf Stockmann <rstockm@gwdg.de>

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
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", 'user' => "Seminar_User"));

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page

require_once ('lib/visual.inc.php');
require_once ('lib/statusgruppe.inc.php');
require_once 'lib/functions.php';

checkObject();
checkObjectModule("participants");

PageLayout::setHelpKeyword("Basis.InVeranstaltungGruppen");
PageLayout::setTitle($SessSemName["header_line"]. " - " . _("Funktionen / Gruppen"));
Navigation::activateItem('/course/members/view_groups');

ob_start();

// Hilfsfunktionen

// groupmail:
// create mailto:-Link fpr
// - groups (filter-argument ignored)
// - seminars (filter=empty or =all: Mail to all accepted participants)
//            (filter=prelim: Mail to all preliminarily accepted partic.)
//            (filter=waiting: Mail to all waiting or claiming partic.)
function groupmail($range_id, $filter = '')
{
    $type = get_object_type($range_id);

    if ($type == 'group') {
        $query = "SELECT GROUP_CONCAT(Email SEPARATOR ';')
                  FROM statusgruppe_user
                  LEFT JOIN auth_user_md5 USING(user_id)
                  WHERE statusgruppe_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($range_id));
        return $statement->fetchColumn();
    }

    if ($type == 'sem') {
        if (!$filter || $filter == 'all') {
            $query = "SELECT GROUP_CONCAT(Email SEPARATOR ';')
                      FROM seminar_user
                      LEFT JOIN auth_user_md5 USING (user_id)
                      WHERE Seminar_id = ?";
        } else if ($filter == 'prelim') {
            $query = "SELECT GROUP_CONCAT(Email SEPARATOR ';')
                      FROM admission_seminar_user
                      LEFT JOIN auth_user_md5 USING (user_id)
                      WHERE seminar_id = ? AND status = 'accepted'";
        } else if ($filter == 'waiting') {
            $query = "SELECT GROUP_CONCAT(Email SEPARATOR ';')
                      FROM admission_seminar_user
                      LEFT JOIN auth_user_md5 USING (user_id)
                      WHERE seminar_id = ? AND status IN ('awaiting', 'claiming')";
        } else {
            throw new InvalidArgumentException('ERROR: unknown filter: ' . $filter);
        }
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($range_id));
        return $statement->fetchColumn();
    }
}


function PrintAktualStatusgruppen ($roles) {
    global $_fullname_sql,$SessSemName, $rechte, $user, $opened_groups;

    if (!is_array($roles)) {
        return;
    }

    // Prepare visibilities query
    $query = "SELECT user_id, visible = 'yes' FROM seminar_user WHERE Seminar_id = ?";
    $visibilities = DBManager::get()->prepare($query);

    // Prepare group members query
    $query = "SELECT user_id, {$_fullname_sql['full']} AS fullname, username,
                     seminar_user.visible = 'yes' AS visible
              FROM statusgruppe_user
              INNER JOIN seminar_user USING (user_id)
              LEFT JOIN auth_user_md5 USING (user_id)
              LEFT JOIN user_info USING (user_id)
              WHERE statusgruppe_id = ? AND seminar_user.seminar_id = ?
              ORDER BY statusgruppe_user.position, Nachname";
    $statement = DBManager::get()->prepare($query);

    foreach ($roles as $role_id => $row) {
        $limit = GetStatusgruppeLimit($role_id);

        $data = $visio = array();
        if (isset($opened_groups[$role_id])) {
            if (!$rechte) {
                $visibilities->execute(array($SessSemName[1]));
                $visio = $visibilities->fetchGrouped(PDO::FETCH_COLUMN);
                $visibilities->closeCursor();
            }

            $statement->execute(array($role_id, $SessSemName[1]));
            $data = $statement->fetchAll(PDO::FETCH_ASSOC);
            $statement->closeCursor();
        }

        $template = $GLOBALS['template_factory']->open('statusgruppen/members');
        $template->active      = Request::option('toggle_group') == $role_id;
        $template->data        = $data;
        $template->folder_id   = ($rechte || CheckUserStatusgruppe($role_id, $user->id)) ? CheckStatusgruppeFolder($role_id) : false;
        $template->limit       = $limit;
        $template->limitted    = $limit && in_array($row['role']->getSelfassign(), array(1, 2));
        $template->may_assign  = CheckAssignRights($role_id, $user->id, $SessSemName[1]);
        $template->may_mail    = $rechte || CheckUserStatusgruppe($role_id, $user->id);
        $template->members     = CountMembersPerStatusgruppe($role_id);
        $template->open        = isset($opened_groups[$role_id]);
        $template->group_id    = $role_id;
        $template->rechte      = $rechte;
        $template->self_assign = in_array($row['role']->getSelfAssign(), array(1, 2));
        $template->subject     = $SessSemName[0];
        $template->size        = $row['size'];
        $template->title       = $row['role']->getName();
        $template->visio       = $visio;
        echo $template->render();
    }
}

function PrintNonMembers ($range_id)
{
    global $_fullname_sql, $rechte, $opened_groups;

    $bereitszugeordnet = GetAllSelected($range_id);

    $query = "SELECT user_id, username, {$_fullname_sql['full']} AS fullname,
                     perms, seminar_user.visible = 'yes' AS visible
              FROM seminar_user
              LEFT JOIN auth_user_md5 USING (user_id)
              LEFT JOIN user_info USING (user_id)
              WHERE Seminar_id = ? AND user_id NOT IN (?)
              ORDER BY Nachname";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array(
        $range_id,
        $bereitszugeordnet ?: ''
    ));
    $data = $statement->fetchAll(PDO::FETCH_ASSOC);

    $non_members = count($data);

    if ($non_members > 0) { // there are non-grouped members
        $template = $GLOBALS['template_factory']->open('statusgruppen/non-members');
        $template->active           = Request::option('toggle_group') == 'non_members';
        $template->open             = $opened_groups['non_members'];
        $template->non_members      = $non_members;
        $template->data             = $data;
        $template->rechte           = $rechte;
        echo $template->render();
    }

    if (count($data) > 1) {
        $Memberstatus = 1;
    } else {
        $Memberstatus = 2;
    }

    if (!sizeof($bereitszugeordnet)) {
        $Memberstatus = 0;
    }
    return $Memberstatus;
}

// Command-Parsing
URLHelper::bindLinkParam('opened_groups', $opened_groups);

if ($assign = Request::option('assign')) {
    if (GetRangeOfStatusgruppe($assign)==$SessSemName[1] && CheckAssignRights($assign, $user->id, $SessSemName[1])) {
        InsertPersonStatusgruppe($user->id, $assign);
        $opened_groups[$assign] = true;
    }
}

if ($delete_id = Request::option('delete_id')) {
    if (GetRangeOfStatusgruppe($delete_id)==$SessSemName[1] && CheckUserStatusgruppe($delete_id, $user->id)){
        RemovePersonStatusgruppe($user->username, $delete_id);
    }
}

if (Request::option('toggle_group')) {
    if (isset($opened_groups[Request::option('toggle_group')])) {
        unset($opened_groups[Request::option('toggle_group')]);
    } else {
        $opened_groups[Request::option('toggle_group')] = true;
    }
}
// Beginn Darstellungsteil

if ($_SESSION['sms_msg']) {
    parse_msg ($sms_msg);
    unset($_SESSION['sms_msg']) ;
}

PrintAktualStatusgruppen(GetAllStatusgruppen($SessSemName[1], $user->id));
$anzahltext = PrintNonMembers($SessSemName[1]);

if ($anzahltext == 1) {
    $infos = _("Nicht alle Personen sind einer Funktion / Gruppe zugeordnet.");
}
if ($anzahltext == 2) {
    $infos = _("Alle Personen sind mindestens einer Funktion / Gruppe zugeordnet.");
}
if ($anzahltext == 0) {
    $infos = _("Niemand ist einer Funktion / Gruppe zugeordnet.");
}
if (!$perm->have_studip_perm('tutor', $SessSemName[1])) {
    $my_groups = GetGroupsByCourseAndUser($SessSemName[1], $user->id);
    if (count($my_groups)) {
        $infos  = _("Sie selbst sind diesen Gruppen zugeordnet:");
        $infos .= '<div style="font-weight:bold">' . join('</div><div style="font-weight:bold">', array_map('htmlReady', $my_groups)) . '</div';
    } else {
        $infos = _("Sie sind noch keiner Gruppe zugeordnet.");
    }
}

list($self_assign_all, $self_assign_exclusive) = CheckSelfAssignAll($SessSemName[1]);
if($self_assign_exclusive) {
    $infos .= '<br>' . _('In dieser Veranstaltung können Sie sich nur in eine der möglichen Gruppen eintragen.');
}


if ($rechte) {
    $sidebar = Sidebar::get();
    $sidebar->setImage('sidebar/group-sidebar.png');

    $actions = new ActionsWidget();
    if (groupmail($SessSemName[1], 'all')) {
        $actions->addLink(_('Nachricht an alle (Rundmail)'), 
                          URLHelper::getLink('dispatch.php/messages/write?course_id=' . $SessSemName[1] . '&emailrequest=1&default_subject=' . rawurlencode($SessSemName[0]) . '&filter=all'),
                          'icons/16/black/mail.png');
    }
    if (groupmail($SessSemName[1], 'prelim')) {
        $actions->addLink(_('Nachricht an alle Teilnehmer auf der Warteliste'),
                          URLHelper::getLink('dispatch.php/messages/write?course_id=' . $SessSemName[1] . '&emailrequest=1&default_subject=' . rawurlencode($SessSemName[0]) . '&filter=prelim'),
                          'icons/16/black/mail.png');
    }
    if (groupmail($SessSemName[1], 'waiting')) {
        $actions->addLink(_('Nachricht an alle vorläufigen Teilnehmer'),
                          URLHelper::getLink('dispatch.php/messages/write?course_id=' . $SessSemName[1] . '&emailrequest=1&default_subject=' . rawurlencode($SessSemName[0]) . '&filter=waiting'),
                          'icons/16/black/mail.png');
    }
    $sidebar->addWidget($actions);

    if (Config::get()->EXPORT_ENABLE && $perm->have_studip_perm('tutor', $SessSemName[1])) {
        include_once $PATH_EXPORT . '/export_linking_func.inc.php';
        
        $widget = new ExportWidget();

        $tmp = export_link($SessSemName[1], 'person', _('Gruppenliste') . ' ' . $SessSemName[0], 'rtf', 'rtf-gruppen', 'status',  _('Gruppen exportieren als rtf Dokument'), 'passthrough');
        $element = LinkElement::fromHTML($tmp, 'icons/16/black/export/file-text.png');
        $widget->addElement($element);

        $tmp = export_link($SessSemName[1], 'person', _('Gruppenliste') . ' ' . $SessSemName[0], 'csv', 'csv-gruppen', 'status',  _('Gruppen exportieren als csv Dokument'), 'passthrough');
        $element = LinkElement::fromHTML($tmp, 'icons/16/black/export/file-office.png');
        $widget->addElement($element);

        $sidebar->addWidget($widget);
    }
}

$layout = $GLOBALS['template_factory']->open('layouts/base.php');
$layout->content_for_layout = ob_get_clean();
echo $layout->render();

page_close();
