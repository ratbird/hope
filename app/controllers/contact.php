<?php

require_once 'app/controllers/authenticated_controller.php';

/**
 * ContactController
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Florian Bieringer <florian.bieringer@uni-passau.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       3.2
 */
class ContactController extends AuthenticatedController {

    public function before_filter(&$action, &$args) {
        parent::before_filter($action, $args);
        if (Request::isXhr() || $action == 'vcard') {
            $this->set_content_type('text/html;Charset=windows-1252');
            $this->set_layout(null);
        } else {
            $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));
        }

        // Load statusgroups
        $this->groups = SimpleORMapCollection::createFromArray(Statusgruppen::findByRange_id(User::findCurrent()->id));
        
        // Load requested group
        if ($args[0]) {
            $this->group = $this->groups->find($args[0]);
            
            //Check for cheaters
            if ($this->group->range_id != User::findCurrent()->id) {
                throw new AccessDeniedException;
            }
        }
        
    }

    /**
     * Main action to display contacts
     */
    function index_action($filter = null) {
        
        // Check if we need to add contacts
        $mps = MultiPersonSearch::load('contacts');
        foreach ($mps->getAddedUsers() as $userId) {
            $user = new User($userId);
            Contact::add($user->username, $filter);
        }
        $mps->clearSession();

        // write filter to local
        $this->filter = $filter;

        // Deal with navigation
        Navigation::activateItem('community/contacts/view');

        // Edit CSS for quicknavigation
        PageLayout::addStyle('div.letterlist span {color: #c3c8cc;}');

        if ($filter) {
            $selected = $this->groups->find($filter);
            $contacts = SimpleORMapCollection::createFromArray($selected->members->pluck('user'));
        } else {
            $contacts = User::findCurrent()->contacts;
        }
        $this->allContacts = $contacts;

        // Retrive first letter and store in that contactgroup
        $this->contacts = array();
        foreach ($contacts as $contact) {
            $this->contacts[$contact->nachname[0]][] = $contact;
        }

        // Humans are a lot better with sorted results
        ksort($this->contacts);
        foreach ($this->contacts as &$contactgroup) {
            usort($contactgroup, array(__CLASS__, 'compareContacts'));
        }

        // Init sidebar
        $this->initSidebar($filter);

        // Init person search
        $mps = MultiPersonSearch::get('contacts')
                ->setTitle(_('Kontakte hinzufügen'))
                ->setDefaultSelectedUser($this->allContacts->pluck('user_id'))
                ->setExecuteURL($this->url_for('contact/index/'.$filter))
                ->setSearchObject(new StandardSearch('user_id'));
        
        // Set default title
        $this->title = _('Alle Kontakte');
        
        // If we have a group
        if ($selected) {
            
            // Set title of Table
            $this->title = $selected->name;
            
            // Set title of multipersonsearch
            $mps->setTitle(sprintf(_('Kontakte zu %s hinzufügen'), $selected->name));
            $mps->addQuickfilter(_('Kontakte'), User::findCurrent()->contacts->pluck('user_id'));
        }
        
        // Render multiperson search
        $this->multiPerson = $mps->render();
        
    }
    
    function remove_action($group = null) {
        Contact::remove(Request::get('user'), $group);
        $this->redirect('contact/index');
    }

    function editGroup_action() {

        // If we got a group load it
        if (!$this->group) {
            $this->group = new Statusgruppen();
            $this->group->range_id = User::findCurrent()->id;
        }

        if (Request::submitted('store')) {
            CSRFProtection::verifyRequest();
            $this->group->name = Request::get('name');
            $this->group->store();
            $this->redirect('contact/index/'.$this->group->id);
        }
    }

    function deleteGroup_action() {
        if (Request::submitted('delete')) {
            CSRFProtection::verifyRequest();
            $this->group->delete();
            $this->redirect('contact/index');
        }
    }

    function vcard_action($group = null) {

        $charset = 'windows-1252';
        $filename = _('Kontakte');

        // If we got an array of user
        if (Request::submitted('user')) {
            foreach (Request::getArray('user') as $username) {
                $user[] = User::findByUsername($username);
            }
        }

        // If we got a group
        if ($group) {
            $user = Statusgruppen::find($group)->members->pluck('user');
        }

        // Fallback to all contacts if we got nothing

        if (!$user) {
            User::findCurrent()->contacs;
        }

        header("Content-type: text/x-vCard;charset=" . $charset); //application/octet-stream MIME
        header("Content-disposition: attachment; filename=" . $filename . ".vcf");
        header("Pragma: private");

        $this->vCard = vCard::export($user);
    }

    private static function compareContacts($a, $b) {
        return strcmp($a->nachname, $b->nachname);
    }

    private function initSidebar($active_id = null) {
        $sidebar = Sidebar::Get();

        $letterlist = new SidebarWidget();
        foreach (range('A', 'Z') as $letter) {
            if ($this->contacts[$letter]) {
                $html .= "<a href='#letter_$letter'>$letter</a>";
            } else {
                $html .= "<span>$letter</span>";
            }
        }
        $letterlist->setTitle(_('Schnellzugriff'));
        $letterlist->addElement(new WidgetElement("<div class='letterlist'>$html</div>"));
        $sidebar->addWidget($letterlist);

        // Groups
        $actions = new ActionsWidget();
        $actions->addLink(_('Neue Gruppe anlegen'), $this->url_for('contact/editGroup'), 'icons/blue/16/add/group3.svg')->asDialog('size=auto');
        $actions->addLink(_('Nachricht an alle'), $this->url_for('messages/write', array('rec_uname' => $this->allContacts->pluck('username'))), 'icons/blue/16/mail.svg')->asDialog();
        $actions->addLink(_('E-Mail an alle'), URLHelper::getLink('mailto:' . join(',', $this->allContacts->pluck('email'))), 'icons/blue/16/mail.svg');
        $actions->addLink(_('Alle vCards herunterladen'), $this->url_for('contact/vcard/' . $this->filter), 'icons/blue/16/vcard.svg');
        $sidebar->addWidget($actions);

        // Groups navigation
        $groupsWidget = new ViewsWidget();
        $groupsWidget->setTitle(_('Gruppen'));
        $groupsWidget->addLink(_('Alle Kontakte'), URLHelper::getLink('dispatch.php/contact/index'))->setActive(!$active_id);
        foreach ($this->groups as $group) {
            $groupsWidget->addLink(htmlReady($group->name), URLHelper::getLink('dispatch.php/contact/index/' . $group->id))->setActive($group->id == $active_id);
        }
        $sidebar->addWidget($groupsWidget);
    }

}
