<?php

/**
 * userlist.php - Controller for user list administration. User lists
 * are lists of persons who get different chances at course seat distribution
 * than "normal" applicants.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Thomas Hackl <thomas.hackl@uni-passau.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

class Admission_UserListController extends AuthenticatedController {

    /**
     * @see AuthenticatedController::before_filter
     */
    public function before_filter(&$action, &$args) {
        parent::before_filter($action, $args);
        if (Request::isXhr()) {
            $this->via_ajax = true;
            $this->set_layout(null);
            $request = Request::getInstance();
            foreach ($request as $key => $value) {
                $request[$key] = studip_utf8decode($value);
            }
        } else {
            $layout = $GLOBALS['template_factory']->open('layouts/base');
            $this->set_layout($layout);
            PageLayout::setTitle(_('Personenlisten'));
            Navigation::activateItem('/tools/coursesets/userlists');
        }
        PageLayout::addSqueezePackage('admission');
        $this->set_content_type('text/html;charset=windows-1252');

        $views = new ViewsWidget();
        $views->setTitle(_('Aktionen'));
        $views->addLink(_('Personenliste anlegen'),$this->url_for('admission/userlist/configure'))->setActive($action == 'configure');
        Sidebar::Get()->addWidget($views);
    }

    /**
     * Show the user lists the current user has access to.
     */
    public function index_action() {
        $this->userlists = array();
        foreach (AdmissionUserList::getUserLists($GLOBALS['user']->id) as $list) {
            $this->userlists[$list->getId()] = $list;
        }
    }

    /**
     * Show a configuration form for the given user list.
     * 
     * @param String $userlistId user list to load settings from (or empty
     * if it is a new user list)
     */
    public function configure_action($userlistId='') {
        if ($userlistId) {
            $this->userlist = new AdmissionUserList($userlistId);
            $this->userlist_id = $userlistId;
            PageLayout::setTitle(_('Personenliste bearbeiten'));

        } else {
            PageLayout::setTitle(_('Personenliste anlegen'));

            $this->userlist = new AdmissionUserList();
            $this->userlist_id = '';
        }
        $this->users = User::findMany(array_keys($this->userlist->getUsers()));
        if ($this->flash['name'] || $this->flash['factor'] || $this->flash['users'] || $this->flash['deleted_member']) {
            if ($this->flash['name']) {
                $this->userlist->setName($this->flash['name']);
            }
            if ($this->flash['factor']) {
                $this->userlist->setFactor($this->flash['factor']);
            }
            if ($this->flash['users'] || $this->flash['deleted_member']) {
                $this->users = User::findMany($this->flash['cleared_users'] ?: $this->flash['users'] ?: array());
            }
        }
        usort($this->users, function($a, $b) {
            if ($a->nachname == $b->nachname) {
                if ($a->vorname == $b->vorname) {
                    return strnatcasecmp($a->username, $b->username);
                } else {
                    return strnatcasecmp($a->vorname, $b->vorname);
                }
            } else {
                return strnatcasecmp($a->nachname, $b->nachname);
            }
        });
        $uids = array_map(function($u) { return $u->id; }, $this->users);
        $this->userSearch = new PermissionSearch('user', 'Person hinzuf�gen', 'user_id',
            array(
                'permission' => array('user', 'autor', 'tutor', 'dozent'),
                'exclude_user' => $uids
            ));

        $this->flash['listusers'] = $uids;
    }

    /**
     * Saves the given user list to database.
     * 
     * @param String $userlistId user list to save
     */
    public function save_action($userlistId='') {
        CSRFProtection::verifyUnsafeRequest();
        $userlist = new AdmissionUserList($userlistId);
        $userlist->setName(Request::get('name'))
            ->setFactor(Request::float('factor'))
            ->setUsers(Request::getArray('users'))
            ->setOwnerId($GLOBALS['user']->id);
        if ($userlist->store()) {
            PageLayout::postSuccess(_('Die Personenliste wurde gespeichert.'));
        } else {
            PageLayout::postError(_('Die Personenliste konnte nicht gespeichert werden.'));
        }
        $this->redirect('admission/userlist');
    }

    /**
     * Deletes the given user list.
     * 
     * @param String $userlistId the user list to delete
     */
    public function delete_action($userlistId) {
        $this->userlist = new AdmissionUserList($userlistId);
        if (Request::int('really')) {
            $this->userlist->delete();
            $this->redirect($this->url_for('admission/userlist'));
        }
        if (Request::int('cancel')) {
            $this->redirect($this->url_for('admission/userlist'));
        }
    }

    /**
     * Deletes the given user from the given user list.
     * @param $userlistId
     * @param $userId
     */
    public function delete_member_action($userlistId, $userId)
    {
        $newusers = array_filter($this->flash['listusers'], function($u) use ($userId) { return $u != $userId; });
        $this->flash['cleared_users'] = $newusers;
        $this->flash['deleted_member'] = true;

        PageLayout::postInfo(
            sprintf(_('%s wurde von der Liste entfernt, die Liste ist aber noch nicht gespeichert.'),
            User::find($userId)->getFullname()));

        $this->redirect($this->url_for('admission/userlist/configure', $userlistId));
    }

    /**
     * Landing page for mulitpersonsearch, adds the selected users to the
     * user list.
     * @param $userlistId string ID of the userlist to edit
     */
    public function add_members_action($userlistId)
    {
        $mp = MultiPersonSearch::load("add_userlist_member_" . $userlistId);

        $users = $mp->getDefaultSelectedUsersIDs();

        $oldsize = count($users);
        foreach ($mp->getAddedUsers() as $u) {
            $users[] = $u;
        }
        $newsize = count($users);

        $this->flash['users'] = $users;

        PageLayout::postInfo(
            sprintf(ngettext('Eine Person wurde der Liste hinzugef�gt.',
                '%u Personen wurden der Liste hinzugef�gt, die Liste ist aber noch nicht gespeichert.',
                $newsize - $oldsize), $newsize - $oldsize));

        $this->redirect($this->url_for('admission/userlist/configure', $userlistId));
    }

}

?>
