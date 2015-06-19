<?php
/**
 * statusgroups.php - trails-controller for managing statusgroups
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Florian Bieringer <florian.bieringer@uni-passau.de>
 * @author      Sebastian Hobert <sebastian.hobert@uni-goettingen.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */
require_once 'app/controllers/authenticated_controller.php';

class Admin_StatusgroupsController extends AuthenticatedController {

    /**
     * {@inheritdoc }
     */
    public function before_filter(&$action, &$args) {
        parent::before_filter($action, $args);

        if (Request::submitted('abort')) {
            $this->redirect('admin/statusgroups/index');
        }

        $this->user_id = $GLOBALS['user']->user_id;

        // Set pagelayout
        PageLayout::setHelpKeyword("Basis.Allgemeines");
        PageLayout::setTitle(_("Verwaltung von Funktionen und Gruppen"));
        Navigation::activateItem('/admin/institute/groups');

        $this->setType();

        // encode
        if (Request::isXhr()) {
            $this->set_content_type('text/html;Charset=windows-1252');
            $this->set_layout(null);
            $this->group = new Statusgruppen(Request::get('group'));
        } else {
            $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));
            PageLayout::addScript(Assets::javascript_path('app_admin_statusgroups.js'));
        }
    }

    /**
     * Basic display of the groups
     */
    public function index_action() {
        $this->checkForChangeRequests();

        // Do some basic layouting
        PageLayout::addScript('jquery/jquery.tablednd.js');
        PageLayout::addStylesheet('jquery-nestable.css');
        PageLayout::addScript('jquery/jquery.nestable.js');
        $this->setAjaxPaths();
        // Setup sidebar.
        $sidebar = Sidebar::get();
        $sidebar->setImage('sidebar/group-sidebar.png');
        $widget = new ActionsWidget();
        $widget->addLink(_('Neue Gruppe anlegen'),
            $this->url_for('admin/statusgroups/editGroup'),
            'icons/16/black/add/group3.png')
            ->asDialog('size=auto');
        $widget->addLink(_('Gruppenreihenfolge ändern'),
            $this->url_for('admin/statusgroups/sortGroups'),
            'icons/16/black/arr_2down.png')
            ->asDialog();
        $sidebar->addWidget($widget);
        // Collect all groups
        $this->loadGroups();
        // Check if the viewing user should get the admin interface
        $this->tutor = $this->type['edit']($this->user_id);
        $membersCollection = new SimpleCollection((User::findMany(Institute::find($_SESSION['SessionSeminar'])->members->orderBy('nachname')->pluck('user_id'))));
        $this->membersOfInstitute = $membersCollection->pluck('id');
    }

    /**
     * Interface to edit a group or create a new one.
     *
     * @param string group id
     */
    public function editGroup_action($group_id = null) {
        $this->group = new Statusgruppen($group_id);
        $this->loadGroups();
    }

    /**
     * Interface to sort groups
     */
    public function sortGroups_action() {
        PageLayout::addStylesheet('jquery-nestable.css');
        PageLayout::addScript('jquery/jquery.nestable.js');
        $this->loadGroups();
    }

    /**
     * Action to add multiple members to a group.
     *
     * @param string group id
     */
    public function memberAdd_action($group_id = null) {
        $mp = MultiPersonSearch::load("add_statusgroup" . $group_id);
        $this->group = new Statusgruppen($group_id);
        $countAdded = 0;
        foreach ($mp->getAddedUsers() as $a) {
            if (!$this->group->isMember(new User($a))) {
                $new_user = new StatusgruppeUser(array($group_id, $a));
                $new_user->store();
                $this->type['after_user_add']($a);
                $countAdded++;
            }
        }

        if ($countAdded == 1) {
            PageLayout::postMessage(MessageBox::success(_('Es wurde eine Person hinzugefügt.')));
        } elseif ($countAdded > 1) {
            PageLayout::postMessage(MessageBox::success(sprintf(_('Es wurden %s MitgliederInnen hinzugefügt.'), $countAdded)));
        }

        $this->redirect('admin/statusgroups');
    }

    /**
     * Ajax action to move a user
     */
    public function move_action() {
        $this->check('edit');
        $GLOBALS['perm']->check('tutor');
        $group = Request::get('group');
        $user_id = Request::get('user');
        $pos = Request::get('pos');
        $statusgroup = new statusgruppen($group);
        $statusgroup->moveUser($user_id, $pos);
        $this->type['after_user_move']($user_id);
        $this->users = $statusgroup->members;
        $this->afterFilter();
    }

    /**
     * Ajaxaction to add a user
     */
    public function add_action() {
        $this->check('edit');
        $group = Request::get('group');
        $user_id = Request::get('user');
        $user = new StatusgruppeUser(array($group, $user_id));
        $user->store();
        $statusgroup = new statusgruppen($group);
        $this->users = $statusgroup->members;
        $this->type['after_user_add']($user_id);
        $this->afterFilter();
    }

    /**
     * Ajaxaction to delete a user
     */
    public function delete_action($group_id, $user_id) {
        $this->check('edit');
        $this->group = new Statusgruppen($group_id);
        $this->user = new User($user_id);
        if (Request::submitted('confirm')) {
            $this->group->removeUser($user_id);
            $this->type['after_user_delete']($user_id);
            $this->afterFilter();
        }
    }

    /**
     * Delete a group
     */
    public function deleteGroup_action($group_id) {
        $this->check('edit');
        $this->group = new Statusgruppen($group_id);
        if (Request::submitted('confirm')) {
            CSRFProtection::verifySecurityToken();

            // move all subgroups to the parent
            $children = SimpleORMapCollection::createFromArray($this->group->children);
            $children->setValue('range_id', $this->group->range_id);
            $children->store();

            //remove users
            $this->group->removeAllUsers();

            //goodbye group
            $this->group->delete();
            $this->redirect('admin/statusgroups/index');
        }
    }

    /**
     * Delete a group
     */
    public function sortAlphabetic_action($group_id) {
        $this->check('edit');
        $this->group = new Statusgruppen($group_id);
        if (Request::submitted('confirm')) {
            CSRFProtection::verifySecurityToken();
            $this->group->sortMembersAlphabetic();
            $this->redirect('admin/statusgroups/index');
        }
    }

    /**
     * Action to select institute. This should be put somewhere else since we
     * have to do this on EVERY institute page
     */
    public function selectInstitute_action() {

    }

    /**
     * Action to truncate a group
     * @param type $id the group id
     */
    public function truncate_action($id) {
        $this->check('edit');
        $this->group = new Statusgruppen($id);
        if (Request::submitted('confirm')) {
            CSRFProtection::verifySecurityToken();
            $this->group->removeAllUsers();
            $this->redirect('admin/statusgroups/index');
        }
    }

    /* *********************************
     * ***** PRIVATE HELP FUNCTIONS ****
     * *********************************/

    /*
     * Loads groups from the database.
     */
    private function loadGroups() {
        $this->groups = Statusgruppen::findBySQL('range_id = ? ORDER BY position', array($_SESSION['SessionSeminar']));
    }

    /*
     * Updates groups recursivly.
     */
    private function updateRecoursive($obj, $parent) {
        $i = 0;
        if ($obj) {
            foreach ($obj as $group) {
                $statusgroup = new Statusgruppen($group->id);
                $statusgroup->range_id = $parent;
                $statusgroup->position = $i;
                $statusgroup->store();
                $this->updateRecoursive($group->children, $group->id);
                $i++;
            }
        }
    }

    /*
     * Renders an action (ajax) or redirects to the statusgroup index page (no ajax).
     */
    private function afterFilter() {
        if (Request::isXhr()) {
            $this->render_action('_members');
        } else {
            $this->redirect('admin/statusgroups');
        }
    }

    /*
     * Sets the urls for ajax calls.
     */
    private function setAjaxPaths() {
        $this->path['ajax_move'] = $this->url_for('admin/statusgroups/move');
        $this->path['ajax_add'] = $this->url_for('admin/statusgroups/add');
        $this->path['ajax_search'] = $this->url_for('admin/statusgroups/search');
    }

    /*
     * Since we dont want an ugly tree display but we want numberation we
     * "unfold" the groups tree
     */
    private function unfoldGroup(&$list, $groups) {
        if (is_array($groups)) {
            $groups = SimpleORMapCollection::createFromArray($groups);
        }
        foreach ($groups->orderBy('position') as $group) {
            $list[] = $group;
            $this->unfoldGroup($list, $group->children, $newpre);
        }
    }

    /*
     * Checks if a group should be updated from a request
     */
    private function checkForChangeRequests() {
        if (Request::submitted('save')) {
            $this->check('edit');
            $group = new Statusgruppen(Request::get('id'));
            if ($group->isNew()) {
                $group->range_id = $_SESSION['SessionSeminar'];
            }
            $group->name = Request::get('name');
            $group->name_w = Request::get('name_w');
            $group->name_m = Request::get('name_m');
            $group->size = Request::get('size');
            $group->range_id = Request::get('range_id') ? : $group->range_id;
            $group->position = Request::get('position') ? : $group->position;
            $group->selfassign = Request::get('selfassign') ? 1 : 0;
            $group->store();
            $group->setDatafields(Request::getArray('datafields') ? : array());
        }
        if (Request::submitted('order')) {
            $this->check('edit');
            $newOrder = json_decode(Request::get('ordering'));
            $this->updateRecoursive($newOrder, $_SESSION['SessionSeminar']);
        }
    }

    /*
     * Checks if the current user has the specific $rights
     */
    private function check($rights) {
        if (!$this->type[$rights]($this->user_id)) {
            die;
        }
    }

    /*
     * This sets the type of statusgroup. By now it only supports
     * Inst statusgroup but could be extended
     */
    private function setType() {
        $_SESSION['SessionSeminar'] = Request::option('admin_inst_id') ? : $_SESSION['SessionSeminar'];
        if (get_object_type($_SESSION['SessionSeminar'], array('inst', 'fak'))) {
            $type = 'inst';
        }
        $types = $this->types();

        if (!$type || Request::submitted('type') && $type != Request::get('type')) {
            $types[is_null(Request::get('type')) ? 'inst' : Request::get('type')]['redirect']();
        } else {
            $this->type = $types[$type];
        }
    }

    /*
     * This is the rest of the idea we could use statusgroups on other pages.
     * navigation and redirect to selection page must move here if the
     * statusgroupspage is reused
     *
     * @return type
     */
    private function types() {
        return array(
            'inst' => array(
                'name' => _('Institut'),
                'after_user_add' => function ($user_id) {
            $newInstUser = new InstituteMember(array($user_id, $_SESSION['SessionSeminar']));
            if ($newInstUser->isNew()) {
                $user = new User($user_id);
                $newInstUser->inst_perms = $user->perms;
                if ($newInstUser->store()) {
                    StudipLog::INST_USER_ADD($_SESSION['SessionSeminar'], $user->id, $user->perms);
                }
            }
        },
                'after_user_delete' => function ($user_id) {
            null;
        },
                'after_user_move' => function ($user_id) {
            null;
        },
                'view' => function ($user_id) {
            return true;
        },
                'needs_size' => false,
                'needs_self_assign' => false,
                'edit' => function ($user_id) {
            return $GLOBALS['perm']->have_studip_perm('admin', $_SESSION['SessionSeminar']) && !LockRules::Check($_SESSION['SessionSeminar'], 'groups');
        },
                'redirect' => function () {
            require_once 'lib/admin_search.inc.php';
            die(); //must not return
        },
                'groups' => array(
                    'members' => array(
                        'name' => _('Mitglieder'),


                ))
            )
        );
    }

}
