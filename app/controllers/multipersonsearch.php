<?php
/**
 * multipersonsearch.php - trails-controller for MultiPersonSearch
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * he License, or (at your option) any later version.
 * 
 * @author      Sebastian Hobert <sebastian.hobert@uni-goettingen.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */
require_once 'app/controllers/authenticated_controller.php';

class MultipersonsearchController extends AuthenticatedController {
    
    /**
     * Ajax action used for searching persons.
     * 
     * @param $name string name of MultiPersonSearch object
     * @param $searchterm string searchterm
     */
    public function ajax_search_action($name) {
        $searchterm = studip_utf8decode(Request::get("s"));
        $searchterm = str_replace(",", " ", $searchterm);
        $searchterm = preg_replace('/\s+/', ' ', $searchterm);
        
        // execute searchobject if searchterm is at least 3 chars long
        if (strlen($searchterm) >= 3) {
            $mp = MultiPersonSearch::load($name);
            $searchObject = $mp->getSearchObject();
            $result = $searchObject->getResults($searchterm, array("cid" => Request::get('cid')));
            $this->result = new SimpleCollection(User::findMany($result));
            $this->result = $this->result->limit(50);
            $this->result->orderBy("nachname asc, vorname asc");
            $this->alreadyMember = $mp->getDefaultSelectedUsersIDs();
        }
        $this->render_template('multipersonsearch/ajax.php');
    }
    
    /**
     * Action which handles dialog form inputs.
     * 
     * This action checks for CSRF and redirects to the action which
     * handles adding/removing users.
     */
    public function js_form_exec_action() {
        CSRFProtection::verifyUnsafeRequest();
        $this->name = Request::get("name");
        $mp = MultiPersonSearch::load($this->name);
        $mp->saveAddedUsersToSession();
        if (strpos($mp->getExecuteURL(), '.php') === false) {
            $this->redirect(URLHelper::getLink('dispatch.php/' . $mp->getExecuteURL()));
        } else {
            $this->redirect(URLHelper::getLink($mp->getExecuteURL()));
        }
    }
    
    
    /**
     * Action which shows a js-enabled dialog form.
     */
    public function js_form_action($name) {
        $mp = MultiPersonSearch::load($name);
        $this->name = $name;
        $this->description = $mp->getDescription();
        $this->quickfilter = $mp->getQuickfilterIds();
        foreach ($this->quickfilter as $title => $users) {
            $tmp = new SimpleCollection(User::findMany($users));
            $tmp->orderBy("nachname asc, vorname asc");
            $this->quickfilter[$title] = $tmp;
        }
        $this->executeURL = $mp->getExecuteURL();
        
        $tmp = new SimpleCollection(User::findMany($mp->getDefaultSelectableUsersIDs()));
        $tmp->orderBy("nachname asc, vorname asc");
        $this->defaultSelectableUsers = $tmp;
        $tmp = new SimpleCollection(User::findMany($mp->getDefaultSelectedUsersIDs()));
        $tmp->orderBy("nachname asc, vorname asc");
        $this->defaultSelectedUsers = $tmp;
        
        if (Request::isXhr()) {
            $this->set_layout(null);
        } else {
            $this->title = $mp->getTitle();
        }
    }
    
    /**
     * Action which is used for handling all submits for no-JavaScript
     * users:
     * * searching,
     * * adding a person,
     * * removing a person,
     * * selcting a quickfilter,
     * * aborting,
     * * saving.
     * 
     * This needs to be done in one single action to provider a similar
     * usability for no-JavaScript users as for JavaScript users.
     */
    public function no_js_form_action() {
        if (!empty($_POST)) {
            CSRFProtection::verifyUnsafeRequest();
        }
        
        $this->name = Request::get("name");
        $mp = MultiPersonSearch::load($this->name);
        
        $this->selectableUsers = array();
        $this->selectedUsers = array();
        $this->search = Request::get("freesearch");
        
        $previousSelectableUsers = unserialize(studip_utf8decode(Request::get('search_persons_selectable_hidden')));
        $previousSelectedUsers = unserialize(studip_utf8decode(Request::get('search_persons_selected_hidden')));
        
        // restore quickfilter
        $this->quickfilterIDs = $mp->getQuickfilterIds();
        foreach($this->quickfilterIDs as $title=>$array) {
            $this->quickfilter[] = $title;
        }
        
        // abort
        if (Request::submitted('abort')) {
            $this->redirect($_SESSION['multipersonsearch_' . $this->name . '_pageURL']);
        }
        // search
        elseif (Request::submitted('submit_search')) {
            // evaluate search
            $this->selectedUsers = User::findMany($previousSelectedUsers);
            $searchterm = Request::get('freesearch');
            $searchObject = $mp->getSearchObject();
            $result = $searchObject->getResults($searchterm, array("cid" => Request::get('cid')));
            
            $this->selectableUsers = User::findMany($result); 
            
            // remove already selected users
            foreach ($this->selectableUsers as $key=>$user) {
                if (in_array($user->id, $previousSelectedUsers) || in_array($user->id, $mp->getDefaultSelectedUsersIDs())) {
                    unset($this->selectableUsers[$key]);
                    $this->alreadyMemberUsers[$key] = $user;
                }
            }
        }
        // quickfilter
        elseif (Request::submitted('submit_search_preset')) {
            $this->selectedUsers = User::findMany($previousSelectedUsers);
            $this->selectableUsers = User::findMany($this->quickfilterIDs[Request::get('search_preset')]);
            // remove already selected users
            foreach ($this->selectableUsers as $key=>$user) {
                if (in_array($user->id, $previousSelectedUsers) || in_array($user->id, $mp->getDefaultSelectedUsersIDs()) ) {
                    unset($this->selectableUsers[$key]);
                }
            }
        }
        // add user
        elseif (Request::submitted('search_persons_add')) {
            // add users
            foreach (Request::optionArray('search_persons_selectable') as $userID) {
                if (($key = array_search($userID, $previousSelectableUsers)) !== false) {
                    unset($previousSelectableUsers[$key]);
                }
                $previousSelectedUsers[] = $userID;
            }
            
            $this->selectedUsers = User::findMany($previousSelectedUsers);
            $this->selectableUsers = User::findMany($previousSelectableUsers);
        }
        // remove user
        elseif (Request::submitted('search_persons_remove')) {
            // remove users
            foreach (Request::optionArray('search_persons_selected') as $userID) {
                if (($key = array_search($userID, $previousSelectedUsers)) !== false) {
                    unset($previousSelectedUsers[$key]);
                }
                $previousSelectableUsers[] = $userID;
            }
            
            $this->selectedUsers = User::findMany($previousSelectedUsers);
            $this->selectableUsers = User::findMany($previousSelectableUsers);
        }
        // save
        elseif (Request::submitted('save')) {
            //$_SESSION['multipersonsearch_' . $this->name . '_status'] = 'save';
            // find added users
            $addedUsers = array();
            $defaultSelectedUsersIDs = $searchObject = $mp->getDefaultSelectedUsersIDs();
            foreach ($previousSelectedUsers as $selected) {
                if (!in_array($selected, $defaultSelectedUsersIDs)) {
                    $addedUsers[] = $selected;
                }
            }
            // find removed users
            $removedUsers = array();
            foreach ($defaultSelectedUsersIDs as $default) {
                if (!in_array($default, $previousSelectedUsers)) {
                    $removedUsers[] = $default;

                }
            }
            $_SESSION['multipersonsearch_' . $this->name . '_selected'] = $previousSelectedUsers;
            $_SESSION['multipersonsearch_' . $this->name . '_added'] = $addedUsers;
            $_SESSION['multipersonsearch_' . $this->name . '_removed'] = $removedUsers;
            // redirect to action which handles the form data
            if (strpos($mp->getExecuteURL(), '.php') === false) {
                $this->redirect(URLHelper::getLink('dispatch.php/' . $mp->getExecuteURL()));
            } else {
                $this->redirect(URLHelper::getLink($mp->getExecuteURL()));
            }
        }
        // default
        else {
            // get selected and selectable users from SESSION
            $this->defaultSelectableUsersIDs = $mp->getDefaultSelectableUsersIDs();
            $this->defaultSelectedUsersIDs = $mp->getDefaultSelectedUsersIDs();
            $this->selectableUsers = User::findMany($this->defaultSelectableUsersIDs);
            $this->selectedUsers = array();
        }
        
        // save selected/selectable users in hidden form fields
        $this->selectableUsers = new SimpleCollection($this->selectableUsers);
        $this->selectableUsers->orderBy("nachname asc, vorname asc");
        $this->selectableUsersHidden =  $this->selectableUsers->pluck('id'); 
        $this->selectedUsers = new SimpleCollection($this->selectedUsers);
        $this->selectedUsers->orderBy("nachname asc, vorname asc");
        $this->selectedUsersHidden =  $this->selectedUsers->pluck('id'); 
        $this->selectableUsers->orderBy('nachname, vorname');
        $this->selectedUsers->orderBy('nachname, vorname'); 
        
        // set layout data
        $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));
        $this->title = $mp->getTitle();
        $this->description = $mp->getDescription();
        $this->pageURL = $mp->getPageURL();
        
    }
    
}
