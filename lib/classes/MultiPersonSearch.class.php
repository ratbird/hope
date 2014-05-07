<?php
/**
 * MultiPersonSearch.class.php
 * 
 * This class provides a GUI-element for searching, adding and removing 
 * multiple persons. If JavaScript is enabled the GUI-element is shown
 * as a dialog on the current page. Otherwise the GUI-element is shown
 * on a separate page.
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * he License, or (at your option) any later version.
 * 
 * @author      Sebastian Hobert <sebastian.hobert@uni-goettingen.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @link        http://docs.studip.de/develop/Entwickler/...
 */
class MultiPersonSearch {
    
    private $name;
    private $linkIconPath = "";
    private $linkText = "";
    private $title = "";
    private $description = "";
    private $executeURL;
    private $defaultSelectableUsers = array();
    private $defaultSelectedUsers = array();
    private $quickfilter = array();
    private $quickfilterIds = array();
    private $defaultSelectableUsersIDs = array();
    private $defaultSelectedUsersIDs = array();
    public static $importsAlreadyAdded = false;
    private $searchObject = null;
    private $additionalHMTL = "";
    
    /**
     * restores a MultiPersonSearch object.
     * 
     * @param string name of the object
     * 
     * @return MultiPersonSearch
     */
    public static function load($name)
    {
        $mp = new MultiPersonSearch($name);
        $mp->restoreFromSession();
        return $mp;
    }
    
    /**
     * returns a MultiPersonSearch object.
     * 
     * @param string name of the object
     * 
     * @return MultiPersonSearch
     */
    public static function get($name)
    {
        $mp = new MultiPersonSearch($name);
        return $mp;
    }
    
    /**
     * contsructs a new MultiPersonSearch object.
     * 
     * @param string name of the object and html ids
     */
    public function MultiPersonSearch($name)
    {
       $this->name = $name;
       
       $this->loadAssets();
       $this->setDefaultValues();
        
    }
    
    /**
     * returns the newly added persons. The array will contain all
     * persons which are selected (on the right side of the dialog) but
     * without the defaultSelectedUsers.
     * 
     * @return array containing all new persons
     */
    public function getAddedUsers() {
        return $_SESSION['multipersonsearch_' . $this->name . '_added'];
    }
    
    /**
     * saves the added persons to $_SESSION.
     */
    public function saveAddedUsersToSession() {
        $addedUsers = array();
        foreach (Request::optionArray($this->name . '_selectbox') as $selected) {
            if (!in_array($selected, $_SESSION['multipersonsearch_' . $this->name . '_defaultSelectedUsersIDs'])) {
                $addedUsers[] = $selected;
            }
        }
        $_SESSION['multipersonsearch_' . $this->name . '_added'] = $addedUsers;
        $_SESSION['multipersonsearch_' . $this->name . '_additional'] = Request::optionArray('additional');
    }
    
    /**
     * returns the removed persons. The array will contain all
     * persons which were selected by default (on the right side of the
     * dialog) and then removed by the user.
     * 
     * @return array containing all removed persons
     */
    public function getRemovedUsers() {
        return $_SESSION['multipersonsearch_' . $this->name . '_removed'];
    }
    
    /**
     * saves the removed persons to $_SESSION.
     */
    public function saveRemovedUsersToSession() {
        $removedUsers = array();
        foreach ($this->defaultSelectedUsersIDs as $default) {
            if (!in_array($default, Request::optionArray($this->name . '_selectbox'))) {
                $removedUsers[] = $default;
            }
        }
        $_SESSION['multipersonsearch_' . $this->name . '_removed'] =  $removedUsers;
    }
    
    /**
     * renders a link to open the multipersonsearch dialog.
     */
    public function render() {
        $template = $GLOBALS['template_factory']->open('multipersonsearch/link.php');
        
        $template->set_attribute('linkIconPath', $this->linkIconPath);
        $template->set_attribute('linkText', $this->linkText);
        $template->set_attribute('title', $this->title);
        $template->set_attribute('name', $this->name);
        $template->set_attribute('description', $this->description);
        $template->set_attribute('quickfilter', $this->quickfilter);
        $template->set_attribute('executeURL', $this->executeURL);
        
        $template->set_attribute('defaultSelectableUsers', $this->defaultSelectableUsers);
        $template->set_attribute('defaultSelectedUsers', $this->defaultSelectedUsers);
        
        $this->storeToSession();
        return $template->render();
    }
    
    /**
     * sets the icon of the link to open the dialog. To hide the icon an
     * empty string can be set.
     * 
     * @param string path ot the icon
     * 
     * @return MultiPersonSearch
     */
    public function setLinkIconPath($path) {
        $this->linkIconPath = $path;
        
        return $this;
    }
    
    /**
     * returns the icon of the link to open the dialog.
     * 
     * @return string path ot the icon.
     */
    public function getLinkIconPath() {
        return $this->linkIconPath;
    }
    
    /**
     * sets the link text of the link to open the dialog. To hide the
     * text an empty string can be set.
     * 
     * @param string text of the link
     * 
     * @return MultiPersonSearch
     * 
     */
    public function setLinkText($text = "") {
        $this->linkText = $text;
        
        return $this;
    }
    
    /**
     * returns the link text of the link.
     * 
     * @return string text of the link.
     */
    public function getLinkText() {
        return $this->linkText;
    }
    
    /**
     * sets the action which will handle the added and removed persons after saving the dialog.
     * 
     * @param string action
     * 
     * @return MultiPersonSearch
     */
    public function setExecuteURL($action) {
        $this->executeURL = $action;
        
        return $this;
    }
    
    /**
     * returns the action which will handle the added and removed persons after saving the dialog.
     * 
     * @return string action which will handle the form data.
     */
    public function getExecuteURL() {
        return $this->executeURL;
    }
    
    /**
     * sets the search object.
     * 
     * @param SearchType object of type SearchType (e.g. SQLSearch.class.php)
     * 
     * @return MultiPersonSearch
     */
    public function setSearchObject($searchType) {
        $this->searchObject = $searchType;
        
        return $this;
    }
    
    /**
     * returns the search object.
     * 
     * @return SearchType
     */
    public function getSearchObject() {
        return $this->searchObject;
    }
    
    public function setAdditionalHTML($html) {
        $this->additionalHMTL = $html;
        
        return $this;
    }
    
    public function getAdditionHTML() {
        return $this->additionalHMTL;
    }
    
    public function getAdditionalOptionArray() {
        return $_SESSION['multipersonsearch_' . $this->name . '_additional'];
    }
    
    /**
     * sets the persons which will be shown as selectable by default on
     * the left side of the dialoag.
     * 
     * @param array array containing user-ids
     */
    public function setDefaultSelectableUser($userArray) {
        $userArray = array_unique($userArray);
        $this->defaultSelectableUsers = array();
        $this->defaultSelectableUsersIDs = array();
        if (is_array($userArray)) {
            foreach ($userArray as $userId) {
                $this->defaultSelectableUsers[]  = new User($userId);
                $this->defaultSelectableUsersIDs[] = $userId;
            }
        }
        return $this;
    }
    /**
     * returns the ids of defaultselectable users.
     * 
     * @return array
     */
    public function getDefaultSelectableUsersIDs() {
        return $this->defaultSelectableUsersIDs;
    }
    
    /**
     * sets the persons which will be shown as selected by default on
     * the right side of the dialoag.
     * 
     * @param array array containing user-ids
     */
    public function setDefaultSelectedUser($userArray) {
        $userArray = array_unique($userArray);
        $this->defaultSelectedUsers = array();
        $this->defaultSelectedUsersIDs = array();
        if (is_array($userArray)) {
            foreach ($userArray as $userId) {
                $this->defaultSelectedUsers[]  = new User($userId);
                $this->defaultSelectedUsersIDs[]  = $userId;
            }
        }
        return $this;
    }
    
    /**
     * returns the ids of defaultselected users.
     * 
     * @return array
     */
    public function getDefaultSelectedUsersIDs() {
        return $this->defaultSelectedUsersIDs;
    }
    
    
    /**
     * sets the title of the dialog.
     * 
     * @param string  $title title of the dialog
     * 
     * @return MultiPersonSearch
     */
    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }
    
    /**
     * returns the title.
     * 
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }
    
    
    /**
     * sets the description of the dialog.
     * 
     * @param string  $desc description of the dialog
     * 
     * @return MultiPersonSearch
     */
    public function setDescription($desc) {
        $this->description = $desc;
        return $this;
    }
    
    /**
     * returns the description.
     * 
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }
    
    
    /**
     * returns the url of the page where the GUI element is added.
     * 
     * @return string
     */
    public function getPageUrl() {
        return $this->pageURL;
    }
    
    /**
     * adds a new quickfilter.
     * 
     * @param string $title title of the new quickfilter
     * @param array $userArray containing all user-ids belonging to the quickfilter
     * 
     * @return MultiPersonSearch
     */
    public function addQuickfilter($title, $userArray) {
        $users = array();
        $usersIds = array();
        if (is_array($userArray)) {
            foreach ($userArray as $userId) {
                $users[]  = new User($userId);
                $usersIds[]  = $userId;
            }
        }
        $this->quickfilter[$title] = $users;
        $this->quickfilterIds[$title] = $usersIds;
        
        return $this;
    }
    
    /**
     * returns the ids of quickfilters.
     * 
     * @return array
     */
    public function getQuickfilterIds() {
        return $this->quickfilterIds;
    }
    
    /**
     * clears all quickfilters.
     * 
     * @return MultiPersonSearch
     */
    public function clearQuickfilters() {
        $this->quickfilter = array();
        $this->quickfilterIds = array();
        
        return $this;
    }
    
    /**
     * stores the internal data to a session.
     */
    public function storeToSession() {
        
        $_SESSION['multipersonsearch_' . $this->name . '_title'] = $this->title;
        $_SESSION['multipersonsearch_' . $this->name . '_description'] = $this->description;
        $_SESSION['multipersonsearch_' . $this->name . '_quickfilter'] = $this->quickfilterIds;
        $_SESSION['multipersonsearch_' . $this->name . '_additionalHMTL'] = $this->additionalHMTL;
        $_SESSION['multipersonsearch_' . $this->name . '_executeURL'] = $this->executeURL;
        $_SESSION['multipersonsearch_' . $this->name . '_pageURL'] = Request::url();
        $_SESSION['multipersonsearch_' . $this->name . '_defaultSelectableUsersIDs'] = $this->defaultSelectableUsersIDs;
        $_SESSION['multipersonsearch_' . $this->name . '_defaultSelectedUsersIDs'] = $this->defaultSelectedUsersIDs;
        $_SESSION['multipersonsearch_' . $this->name . '_quickfilterIds'] = $this->quickfilterIds;
        $_SESSION['multipersonsearch_' . $this->name . '_searchObject'] = serialize($this->searchObject);
        
    }
    
    /**
     * restores the internal data from a session.
     */
    public function restoreFromSession() {
        
        $this->title = $_SESSION['multipersonsearch_' . $this->name . '_title'];
        $this->description = $_SESSION['multipersonsearch_' . $this->name . '_description'];
        $this->quickfilterIds = $_SESSION['multipersonsearch_' . $this->name . '_quickfilterIds'];
        $this->additionalHMTL = $_SESSION['multipersonsearch_' . $this->name . '_additionalHMTL'];
        $this->executeURL = html_entity_decode($_SESSION['multipersonsearch_' . $this->name . '_executeURL']);
        $this->pageURL = $_SESSION['multipersonsearch_' . $this->name . '_pageURL'];
        $this->defaultSelectableUsersIDs = $_SESSION['multipersonsearch_' . $this->name . '_defaultSelectableUsersIDs'];
        $this->defaultSelectedUsersIDs = $_SESSION['multipersonsearch_' . $this->name . '_defaultSelectedUsersIDs'];
        $this->quickfilterIds = $_SESSION['multipersonsearch_' . $this->name . '_quickfilterIds'];
        $this->searchObject = unserialize($_SESSION['multipersonsearch_' . $this->name . '_searchObject']);
        
    }
    
    /**
     * clears the session data.
     */
    public function clearSession() {
        unset($_SESSION['multipersonsearch_' . $this->name . '_title']);
        unset($_SESSION['multipersonsearch_' . $this->name . '_description']);
        unset($_SESSION['multipersonsearch_' . $this->name . '_quickfilterIds']);
        unset($_SESSION['multipersonsearch_' . $this->name . '_additional']);
        unset($_SESSION['multipersonsearch_' . $this->name . '_executeURL']);
        unset($_SESSION['multipersonsearch_' . $this->name . '_pageURL']);
        unset($_SESSION['multipersonsearch_' . $this->name . '_defaultSelectableUsersIDs']);
        unset($_SESSION['multipersonsearch_' . $this->name . '_defaultSelectedUsersIDs']);
        unset($_SESSION['multipersonsearch_' . $this->name . '_quickfilterIds']);
        unset($_SESSION['multipersonsearch_' . $this->name . '_searchObject']);
        unset($_SESSION['multipersonsearch_' . $this->name . '_added']);
        unset($_SESSION['multipersonsearch_' . $this->name . '_removed']);
    }
    
    /**
     * imports stylesheet and javascript files, if not already done.
     */
    private function loadAssets() {
         if (!self::$importsAlreadyAdded) {
            PageLayout::addStylesheet('multi-select.css');
            PageLayout::addScript('jquery/jquery.multi-select.js');
            PageLayout::addScript('multi_person_search.js');
            self::$importsAlreadyAdded = true;
        }
    }
    
    /**
     * sets default values of the internal variables.
     */
    private function setDefaultValues() {
        $this->title = _("Personen hinzuf&uuml;gen");
        $this->description = _("Bitte w&auml;hlen Sie aus, wen Sie hinzuf&uuml;gen m&ouml;chten.");
        $this->linkIconPath = "icons/16/blue/add/community.png";
    }
    
}

?>
