<?php

require_once 'app/controllers/authenticated_controller.php';

class Admin_StatusgroupsController extends AuthenticatedController {

    /**
     * {@inheritdoc }
     */
    public function before_filter(&$action, &$args) {
        parent::before_filter($action, $args);
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
    }

    /**
     * Basic display of the groups
     */
    public function index_action() {
        $this->checkForChangeRequests();

        // Do some basic layouting
        $this->setHead();
        $this->setInfobox();
        $this->setAjaxPaths();

        // Collect all groups and unfold them for a clear display
        $this->groups = Statusgruppen::findByRange_id($_SESSION['SessionSeminar']);
        $this->unfolded = array();
        $this->unfoldGroup($this->unfolded, $this->groups);

        // Check if the viewing user should get the admin interface
        $this->tutor = $this->type['edit']($this->user_id);
    }

    /**
     * Interface to add multiple users to multiple groups
     */
    public function memberAdd_action() {
        // Collect all groups and unfold them for a clear display
        $this->groups = Statusgruppen::findByRange_id($_SESSION['SessionSeminar']);
        $this->unfolded = array();
        $this->unfoldGroup($this->unfolded, $this->groups);

        $this->setInfoBoxImage('infobox/groups.jpg');
        $this->addToInfobox(_('Aktionen'), "<a href='" . $this->url_for('admin/statusgroups') . "'>" . _('Zur�ck') . "</a>", 'icons/16/black/arr_1left.png');
        
        if ($search = Request::get('freesearch')) {
            $this->freepeople = User::search($search, 0);
        }

        if (!Request::submitted('removeSelection')) {
            $this->selectedGroups = Request::getArray('groups');
            $this->selectedMembers = Request::getArray('members');
        } else {
            $this->selectedGroups = array();
            $this->selectedMembers = array();
        }
        if (Request::submitted('add')) {
            CSRFProtection::verifyUnsafeRequest();
            foreach ($this->selectedGroups as $group) {
                foreach ($this->selectedMembers as $user_id) {
                    $user = new StatusgruppeUser(array($group, $user_id));
                    $user->store();
                    $this->type['after_user_add']($user_id);
                }
            }
                    
        }
    }

    /**
     * Ajax action to move a user
     */
    public function move_action() {
        $this->check('edit');
        $this->set_layout(null);
        $GLOBALS['perm']->check('tutor');
        $group = Request::get('group');
        $user_id = Request::get('user');
        $pos = Request::get('pos');
        $statusgroup = new statusgruppen($group);
        $statusgroup->moveUser($user_id, $pos);
        $this->type['after_user_move']($user_id);
        $this->users = $statusgroup->members;
    }

    /**
     * Ajaxaction to add a user
     */
    public function add_action() {
        $this->check('edit');
        $this->set_layout(null);
        $group = Request::get('group');
        $user_id = Request::get('user');
        $user = new StatusgruppeUser(array($group, $user_id));
        $user->store();
        $statusgroup = new statusgruppen($group);
        $this->users = $statusgroup->members;
        $this->type['after_user_add']($user_id);
        $this->render_action('move');
    }

    /**
     * Ajaxaction to delete a user
     */
    public function delete_action() {
        $this->check('edit');
        $this->set_layout(null);
        $group = Request::get('group');
        $user_id = Request::get('user');
        $user = new StatusgruppeUser(array($group, $user_id));
        $user->delete();
        $statusgroup = new statusgruppen($group);
        $this->users = $statusgroup->members;
        $this->type['after_user_delete']($user_id);
        $this->render_action('move');
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
     * Action to reorder the groups
     */
    public function order_action() {
        $this->check('edit');
        $newOrder = json_decode(Request::get('json'));
        $this->updateRecoursive($newOrder, $_SESSION['SessionSeminar']);
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
        $group = new Statusgruppen($id);
        $group->removeAllUsers();
        $this->redirect('admin/statusgroups/index');
    }

    /**********************************
     ****** PRIVATE HELP FUNCTIONS ****
     **********************************/
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

    private function setHead() {
        $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));
        PageLayout::addStylesheet('jquery-nestable.css');
        PageLayout::addScript('jquery/jquery.tablednd.js');
        PageLayout::addScript('jquery/jquery.nestable.js');
        PageLayout::addScript('statusgroups.js');
    }

    private function setAjaxPaths() {
        $this->path['ajax_move'] = $this->url_for('admin/statusgroups/move');
        $this->path['ajax_add'] = $this->url_for('admin/statusgroups/add');
        $this->path['ajax_search'] = $this->url_for('admin/statusgroups/search');
        $this->path['ajax_delete'] = $this->url_for('admin/statusgroups/delete');
        $this->path['ajax_order'] = $this->url_for('admin/statusgroups/order');
    }

    /**
     * Since we dont want an ugly tree display but we want numberation we
     * "unfold" the groups tree
     */
    private function unfoldGroup(&$list, $groups, $preset = array()) {
        foreach ($groups as $group) {

            // Numberating groups LIKE A BOSS!
            $this->numbers[$group->id] = join(".", $newpre = array_merge($preset, array(++$i)));
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

        $this->addToInfobox(_('Aktionen'), "<a id='new_group' href='javascript: newgroup()'>" . _('Neue Gruppe anlegen') . "</a>", 'icons/16/black/add/group3.png');
        $this->addToInfobox(_('Aktionen'), "<a id='new_group' href='javascript: order()'>" . _('Reihenfolge �ndern') . "</a>", 'icons/16/black/refresh.png');
        $this->addToInfobox(_('Aktionen'), "<a id='new_group' href='" . $this->url_for("admin/statusgroups/memberAdd") . "'>" . _('Mehrere Mitglieder hinzuf�gen') . "</a>", 'icons/16/black/add/community.png');
        $this->addToInfobox('Personensuche', $infobox_search);
    }

    /*
     * Checks if a group should be updated from a request
     */
    private function checkForChangeRequests() {
        $this->check('edit');
        if ($id = Request::get('id')) {
            if ($id == "newgroup") {
                $group = new Statusgruppen();
                $group->range_id = $_SESSION['SessionSeminar'];
            } else {
                $group = new Statusgruppen($id);
            }
            if (Request::get('delete')) {
                $group->delete();
            } else {
                $group->name = Request::get('name');
                $group->name_w = Request::get('name_w');
                $group->name_m = Request::get('name_m');
                $group->size = Request::get('size');
                $group->selfassign = Request::get('selfassign') ? 1 : 0;
                $group->store();
            }
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
        if ($_SESSION['SessionSeminar'] && (Request::get('type') == null || get_object_type($_SESSION['SessionSeminar']) == Request::get('type'))) {
            $types = $this->types();
            $this->type = $types[get_object_type($_SESSION['SessionSeminar'])];
        } else {
            $this->redirect('admin/statusgroups/selectInstitute');
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
                'edit' => function ($user_id) {
                    return $GLOBALS['perm']->have_studip_perm('admin', $_SESSION['SessionSeminar']);
                },
                'groups' => array(
                    'members' => array(
                        'name' => _('Mitglieder'),
                        'user' => function() {
                            $inst = new Institute($_SESSION['SessionSeminar']);
                            return $inst->members->orderBy('nachname');
                        }))
            )
        );
    }

}

