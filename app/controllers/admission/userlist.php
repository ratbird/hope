<?php

require_once('app/controllers/authenticated_controller.php');
require_once('lib/classes/admission/AdmissionUserList.class.php');

class Admission_UserListController extends AuthenticatedController {

    public function before_filter(&$action, &$args) {
        parent::before_filter($action, $args);
        if (Request::isXhr()) {
            $this->via_ajax = true;
            $this->set_layout(null);
        } else {
            $layout = $GLOBALS['template_factory']->open('layouts/base');
            $this->set_layout($layout);
            PageLayout::setTitle(_('Nutzerlisten'));
            Navigation::activateItem('/tools/coursesets/userlists');
        }
        PageLayout::addSqueezePackage('admission');
		PageLayout::addStylesheet('form.css');
    }

    public function index_action() {
        $this->userlists = array();
        foreach (AdmissionUserList::getUserLists($GLOBALS['user']->id) as $list) {
            $this->userlists[$list->getId()] = $list;
        }
    }

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
    }

    public function save_action($userlistId='') {
        if (Request::submitted('submit')) {
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
            }
            $this->redirect($this->url_for('admission/userlist/configure', $userlistId));
        }
    }

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