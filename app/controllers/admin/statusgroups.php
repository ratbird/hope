<?php

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


        // The logic to select an institute should somehow be moved somewhere else
        if ($set = Request::get('admin_inst_id')) {
            $_SESSION['SessionSeminar'] = $set;
            $this->redirect('admin/statusgroups/index');
        }
        if ($action != 'selectInstitute')
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
        $this->setInfobox();
        $this->setAjaxPaths();

        // Collect all groups
        $this->loadGroups();

        // Check if the viewing user should get the admin interface
        $this->tutor = $this->type['edit']($this->user_id);
    }

    /**
     * Interface to edit a group or create a new one
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
    
    public function memberAdd_action($group_id = null) {
        // load selected group
        $this->group = new Statusgruppen($group_id);
        
        // set infobox
        $this->setInfoBoxImage('infobox/groups.jpg');
        $this->addToInfobox(_('Aktionen'), "<a href='" . $this->url_for('admin/statusgroups') . "'>" . _('Zurück') . "</a>", 'icons/16/black/arr_1left.png');
        
        // load current group members on first call
        $this->selectedPersons = array();
        if (!Request::get('not_first_call')) {
            $this->currentGroupMembers = array();
            foreach ($this->group->members as $member) {
                $user = new User($member->user_id);
                $this->selectedPersons[] = $user;
            }
        } else {
            // Load selected persons
            $this->selectedPersonsHidden = unserialize(studip_utf8decode(Request::get('search_persons_selected_hidden')));
            foreach ($this->selectedPersonsHidden as $user_id) {
                $this->selectedPersons[] = new User($user_id);
            }
        }
        
        // Search
        $this->search = Request::isXHR() ? utf8_decode(Request::get('freesearch')) : Request::get('freesearch');
        $lastSearch = Request::isXHR() ? utf8_decode(Request::get('last_search_hidden')) : Request::get('last_search_hidden');
        $this->searchPreset = Request::get('search_preset');
        $lastSearchPreset= Request::isXHR() ? utf8_decode(Request::get('last_search_preset')) : Request::get('last_search_preset');
        if (($this->searchPreset == "inst" && $lastSearchPreset != "inst")|| !Request::get('not_first_call')) { // ugly
            // search with preset
            foreach ($this->type['groups'] as $group) {
                $this->selectablePersons = array();
                foreach ($group['user']() as $user) {
                    $this->selectablePersons[] = $user->user;
                }
            }
            // reset search input, because a preset is used
            $this->search = "";
        } elseif ($this->search != $lastSearch || Request::submitted('submit_search')) {
            // search with free text input
            $this->selectablePersons = User::search($this->search, 0);
            // reset preset
            $this->searchPreset = "";
        } else {
            // otherwise restore selectable persons
            $this->selectablePersonsHidden = unserialize(studip_utf8decode(Request::get('search_persons_selectable_hidden')));
            foreach ($this->selectablePersonsHidden as $user_id) {
                $this->selectablePersons[] = new User($user_id);
            }
        }
        
        // select person
        if (Request::submitted('search_persons_add')) {
            foreach (Request::optionArray('search_persons_selectable') as $user_id) {
                $this->selectedPersons[] = new User($user_id);
            }
        }
        
        // deselect person
        if (Request::submitted('search_persons_remove')) {
            foreach (Request::optionArray('search_persons_selected') as $user_id) {
                foreach ($this->selectedPersons as $key=>$value) {
                    if ($value->id == $user_id) {
                        unset($this->selectedPersons[$key]);
                    }
                }
                $this->selectablePersons[] = new User($user_id);
            }
        }
        
        // remove already selected persons from selectable
        foreach ($this->selectedPersons as $user) {
            foreach ($this->selectablePersons as $key=>$value) {
                if ($value->id == $user->id) {
                    // delete from selectable persons
                    unset($this->selectablePersons[$key]);
                }
            }
        }
        
        // save changes
        if (Request::submitted('save')) {
            
            $this->countRemoved = 0;
            CSRFProtection::verifyUnsafeRequest();
            
            // delete users from group if removed
            $currentMembers = array();
            foreach ($this->group->members as $member) {
                $isRemoved = true;
                foreach ($this->selectedPersons as $user) {
                    if ($member->user_id == $user->id) {
                        $isRemoved = false;
                    }
                }
                
                if ($isRemoved) {
                    //exit("DELETED");
                    $this->group->removeUser($member->user_id);
                    $this->type['after_user_delete']($member->user_id);
                    //$this->afterFilter();
                    $this->countRemoved++;
                }
            }
            
            // add new users
            $this->countNew = 0;
            
            foreach ($this->selectedPersons as $user) {
                if (!$this->group->isMember($user->id)) {
                    //exit("ADDED");
                    $new_user = new StatusgruppeUser(array($this->group->id, $user->id));
                    $new_user->store();
                    $this->type['after_user_add']($user_id);
                    $this->countNew++;
                    
                }
            }
            
            $this->selectedPersons = array();
            $this->selectablePersons = array();
            
            // reload current group members
            $this->group = new Statusgruppen($group_id);
            $this->currentGroupMembers = array();
            foreach ($this->group->members as $member) {
                $user = new User($member->user_id);
                $this->selectedPersons[] = $user;
            }
            PageLayout::postMessage(MessageBox::success(_('Die Mitglieder wurden gespeichert.')));
            $this->redirect('admin/statusgroups/index#group-'.$group_id);
        }
        
        
        // abort changes
        if (Request::submitted('abort')) {
            $this->redirect('admin/statusgroups/index');
        }
        
        // generate hidden form data to remember current state
        $this->selectablePersonsHidden = array();
        foreach ($this->selectablePersons as $user) {
            $this->selectablePersonsHidden[] = $user->id;
        }
        $this->selectedPersonsHidden = array();
        foreach ($this->selectedPersons as $user) {
            $this->selectedPersonsHidden[] = $user->id;
        }
        
        // set layout
        if (Request::isXhr()) {
            $this->set_layout(null);
        } else {
            $this->title = _('Mitglieder verwalten');
        }
        
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
     * Ajaxaction to look for people
     */
    public function search_action() {
        $searchString = utf8_decode(Request::get('query'));
        $limit = Request::get('limit') ? : 10;
        $users = User::search($searchString, $limit);
        foreach ($users as $user) {
            $new['name'] = utf8_encode($user->getFullName());
            $new['id'] = $user->id;
            $json[] = $new;
        }
        echo json_encode($json);
        $this->render_nothing();
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

    /*     * ********************************
     * ***** PRIVATE HELP FUNCTIONS ****
     * ******************************** */
    
    private function loadGroups() {
        $this->groups = Statusgruppen::findBySQL('range_id = ? ORDER BY position', array($_SESSION['SessionSeminar']));
    }

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

    private function afterFilter() {
        if (Request::isXhr()) {
            $this->render_action('_members');
        } else {
            $this->redirect('admin/statusgroups');
        }
    }

    private function setAjaxPaths() {
        $this->path['ajax_move'] = $this->url_for('admin/statusgroups/move');
        $this->path['ajax_add'] = $this->url_for('admin/statusgroups/add');
        $this->path['ajax_search'] = $this->url_for('admin/statusgroups/search');
    }

    /**
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

    private function setInfoBox() {
        $this->setInfoBoxImage('infobox/groups.jpg');

        //Infobox people Search
        $infobox_search = "<input type = 'text' id = 'ppl_search' style = 'width: 200px;'>";

        foreach ($this->type['groups'] as $group) {
            $infobox_search .= "<h4 class='category' style='margin-bottom: 2px;'>{$group['name']}</h4>";
            foreach ($group['user']() as $user) {
                $infobox_search .= "<p class='person pre' id='{$user->user->id}' style='margin: 0px;'>{$user->user->getFullName('full_rev')}</p>";
            }
        }

        $infobox_search .= "<h4 class='category' id='free_search' style='margin-bottom: 2px; display:none;'>"
                . _('Freie Suche') . "</h4><div id='search_result'></div>";

        $this->addToInfobox(_('Aktionen'), "<a title='" . _('Neue Gruppe anlegen') . "' class='modal' href='" . $this->url_for("admin/statusgroups/editGroup") . "'>" . _('Neue Gruppe anlegen') . "</a>", 'icons/16/black/add/group3.png');
        $this->addToInfobox(_('Aktionen'), "<a title='" . _('Gruppenreihenfolge ändern') . "' class='modal' href='" . $this->url_for("admin/statusgroups/sortGroups") . "'>" . _('Gruppenreihenfolge ändern') . "</a>", 'icons/16/black/arr_2down.png');
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

    /**
     * Checks if the current user has the specific $rights
     */
    private function check($rights) {
        if (!$this->type[$rights]($this->user_id)) {
            die;
        }
    }

    /**
     * This sets the type of statusgroup. By now it only supports
     * Inst statusgroup but could be extended
     */
    private function setType() {
        if (get_object_type($_SESSION['SessionSeminar'], array('inst', 'fak'))) {
            $type = 'inst';
        }
        $types = $this->types();
        if (!$type || Request::submitted('type') && $type != Request::get('type')) {
            $this->redirect($types[Request::get('type')]['redirect']);
        } else {
            $this->type = $types[$type];
        }
    }

    /**
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
            }
            $newInstUser->store();
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
            return $GLOBALS['perm']->have_studip_perm('admin', $_SESSION['SessionSeminar']);
        },
                'redirect' => 'admin/statusgroups/selectInstitute',
                'groups' => array(
                    'members' => array(
                        'name' => _('Mitglieder'),
                        'user' => function() {
                    $inst = new Institute($_SESSION['SessionSeminar']);
                    return $inst->members->findBy('user', null, '<>')->orderBy('nachname');
                }))
            )
        );
    }

}
