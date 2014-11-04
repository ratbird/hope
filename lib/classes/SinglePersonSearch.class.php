<?php
/**
 * SinglePersonSearch.class.php
 *
 * This class provides a GUI-element for searching a single person.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * he License, or (at your option) any later version.
 *
 * @author      Sebastian Hobert <sebastian.hobert@uni-goettingen.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @link        http://docs.studip.de/develop/Entwickler/SinglePersonSearch
 */
class SinglePersonSearch {

    private $name;
    private $jsFunction = null;
    public static $importsAlreadyAdded = false;
    private $searchObject = null;
    private $autocomplete_disabled = false;

    /**
     * restores a SinglePersonSearch object.
     *
     * @param string name of the object
     *
     * @return SinglePersonSearch
     */
    public static function load($name)
    {
        $sp = new SinglePersonSearch($name);
        $sp->setSearchObject($_SESSION['singlepersonsearch'][$name]['searchObject']);
        return $sp;
    }

    /**
     * returns a SinglePersonSearch object.
     *
     * @param string name of the object
     *
     * @return SinglePersonSearch
     */
    public static function get($name)
    {
        $sp = new SinglePersonSearch($name);
        return $sp;
    }

    /**
     * contsructs a new SinglePersonSearch object.
     *
     * @param string name of the object and html ids
     */
    public function SinglePersonSearch($name)
    {
       $this->name = $name;
       $_SESSION['singlepersonsearch'][$this->name]['lastUse'] = time();
       $this->collectGarbage();
       $this->loadAssets();

    }

    /**
     * renders a link to open the singlepersonsearch dialog.
     */
    public function render() {
        $template = $GLOBALS['template_factory']->open('singlepersonsearch/form.php');
        $template->set_attribute('jsFunction', $this->jsFunction);
        $template->set_attribute('name', $this->name);
        $template->set_attribute('autocomplete', !$this->autocomplete_disabled);
        return $template->render();
    }
    
    /**
     * returns the last selected user.
     */
    public function getSelectedUser() {
        if (Request::get($this->name . '_selected')) {
            return Request::get($this->name . '_selected');
        }
        if (Request::get($this->name . '_search_term')) {
            return Request::get($this->name . '_search_term');
        }
        
        return false;
    }

    /**
     * sets a JavaScript-function to be fired when the user has pressed the submit-button.
     * Arguments are:
     * function fireme(id_of_item, text_of_item)
     * example setting: MPS->setJSFunctionOnSubmit('fireme');
     *
     * @param string $function_name the name of the javascript function
     *
     * @return SinglePersonSearch
     */
    public function setJSFunctionOnSubmit($function_name)
    {
        $this->jsFunction = $function_name;
        return $this;
    }
    
    public function disableAutocomplete() {
        $this->autocomplete_disabled = true;
        return $this;
    }
    
    /**
     * returns a JavaScript-function which should be fired when the user has pressed the submit button.
     * 
     * @return string function name
     */
    public function getJSFunctionOnSubmit()
    {
        return $this->jsFunction;
    }

    /**
     * sets the search object.
     *
     * @param SearchType object of type SearchType (e.g. SQLSearch.class.php)
     *
     * @return SinglePersonSearch
     */
    public function setSearchObject($searchType) {
        $this->searchObject = $searchType;
        $_SESSION['singlepersonsearch'][$this->name]['searchObject'] = $searchType;
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
    
    /**
     * clears the session data.
     */
    public function clearSession() {
        unset($_SESSION['singlepersonsearch'][$this->name]);
    }

    /**
     * imports stylesheet and javascript files, if not already done.
     */
    private function loadAssets() {
         if (!self::$importsAlreadyAdded) {
            PageLayout::addScript('single_person_search.js');
            self::$importsAlreadyAdded = true;
        }
    }
    
    /**
     * clear unused sessions.
     */
    private function collectGarbage() {
        $maxLifeTime = 30 * 60; // seconds
        foreach ($_SESSION['singlepersonsearch'] as $key=>$value) {
            if (time() - $value['lastUse'] > $maxLifeTime) {
                unset($_SESSION['singlepersonsearch'][$key]);
            }
        }
    }

}
