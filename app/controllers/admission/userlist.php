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

require_once('app/controllers/authenticated_controller.php');
require_once('lib/classes/admission/AdmissionUserList.class.php');

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
            PageLayout::setTitle(_('Nutzerlisten'));
            Navigation::activateItem('/tools/coursesets/userlists');
        }
        PageLayout::addSqueezePackage('admission');
        $this->set_content_type('text/html;charset=windows-1252');

        $views = new ViewsWidget();
        $views->setTitle(_('Aktionen'));
        $views->addLink(_('Nutzerliste anlegen'),$this->url_for('admission/userlist/configure'))->setActive($action == 'configure');
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
        }
        if ($this->flash['name'] || $this->flash['factor'] || $this->flash['users']) {
            if (!$userlistId) {
                $this->userlist = new AdmissionUserList();
            }
            if ($this->flash['name']) {
                $this->userlist->setName($this->flash['name']);
            }
            if ($this->flash['factor']) {
                $this->userlist->setFactor($this->flash['factor']);
            }
            if ($this->flash['users']) {
                $this->userlist->setUsers($this->flash['users']);
            }
        }
        Request::set('user_id_parameter', $this->flash['user_id_parameter']);
        $userSearch = new StandardSearch('user_id');
        $this->search = QuickSearch::get('user_id', $userSearch)
                                    ->withButton()
                                    ->render();
        if ($this->flash['error']) {
            $this->error = MessageBox::error($this->flash['error']);
        }
    }

    /**
     * Saves the given user list to database.
     * 
     * @param String $userlistId user list to save
     */
    public function save_action($userlistId='') {
        if (Request::submitted('submit') && Request::get('name')) {
            $userlist = new AdmissionUserList($userlistId);
            $userlist->setName(Request::get('name'))
                ->setFactor(Request::float('factor'))
                ->setUsers(Request::getArray('users'))
                ->setOwnerId($GLOBALS['user']->id);
            $userlist->store();
            $this->redirect('admission/userlist');
        } else {
            $this->flash['name'] = Request::get('name');
            $this->flash['factor'] = Request::float('factor');
            $this->flash['users'] = Request::getArray('users');
            if (Request::submitted('add_user')) {
                $this->flash['users'] = array_merge($this->flash['users'], array(Request::get('user_id')));
            } else {
                $this->flash['user_id'] = Request::get('user_id');
                $this->flash['user_id_parameter'] = Request::get('user_id_parameter');
                if (!Request::get('name')) {
                    $this->flash['error'] = _('Bitte geben Sie einen Namen für die Nutzerliste an.');
                }
            }
            $this->redirect($this->url_for('admission/userlist/configure', $userlistId));
        }
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

}

?>