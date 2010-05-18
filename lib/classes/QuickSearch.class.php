<?php
/**
 * QuickSearch.class.php
 *
 * @author        Rasmus Fuhse <fuhse@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
 * @version        $Id:$
 */
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// QuickSearch.class.php
// Copyright (C) 2009 Rasmus Fuhse <fuhse@data-quest.de>
// Suchi & Berg GmbH <info@data-quest.de>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+

require_once("lib/classes/Avatar.class.php");
require_once("lib/classes/CourseAvatar.class.php");
require_once("lib/classes/InstituteAvatar.class.php");

require_once('lib/classes/searchtypes/SearchType.class.php');


/**
 * This class provides a small and intuitive GUI-element for an instant search of
 * courses, persons, institutes or other items. Mainly the structure to include
 * a QuickSearch-field is the following:
 *  //code-begin
 *  $sf = new QuickSearch("username");
 *    $sf->withButton();
 *  $sf->specialSQL("SELECT username, CONCAT(Vorname, \" \", Nachname) " .
 *     "FROM auth_user_md5 " .
 *     "WHERE CONCAT(Vorname, \" \", Nachname) LIKE :input " .
 *        "AND perms = 'dozent'", _("Dozenten suchen"));
 *  print $sf->render();
 *  //code-end
 * This code should be included into an html <form>-tag. It will provide a nice looking
 * input-field with a button (this is the $sf->withButton() command for), a javascript
 * instant-search for your items (in this case 'Dozenten') and also a non-javascript
 * oldschool version of a searchfield with a select-box in step 2 of the search (first
 * write what you search, click on the button and then select in the select-box what
 * you wanted to have).
 * You can handle the searchfield in your form-tag as if you have written an
 * '<input type="text" name="username">'.
 *
 * For most cases you may only want to search for persons, courses or institutes.
 * Thus a shortcut is implemented in this class. You may write
 *  //code-begin
 *  $sf = new QuickSearch("username", "username");
 *    $sf->withButton();
 *  print $sf->render();
 *  //code-end
 * to receive a searchfield that is automatically searching for users and inserts
 * the selected users's username in the searchfield. The first parameter of the
 * constructor 'new Quicksearch' is the name of the variable in your form and is
 * completely free to name. The (optional) second parameter describes what you are
 * searching for: username, user_id, Seminar_id or Institut_id.
 * 
 * Also you can do method-chaining with this class, so you can press everything
 * you need infront of your semicolon. Watch this example:
 *  //code-begin
 *  print QuickSearch::get("username", "username")->withButton->render();
 *  //code-end
 *  
 * Lastly you can replace the second argument of the constructor (or get-method)
 * by an object whose class extends the SearchType-class. This might be
 * useful to create your own searches and handle them with oop-style or even
 * use a totally different search-engine like lucene-index! All you need to
 * do so is implement your searchclass and follow this example:
 *  //code-begin
 *  class TeacherSearch extends SearchType {
 *    ...
 *  }
 *  $searcher = new TeacherSearch();
 *  print QuickSearch::get("username", $searcher)->withButton->render();
 *  //code-end
 * Watch the SearchType class in lib/classes/searchtypes/SearchType.class.php 
 * for details.
 * Enjoy!
 */
class QuickSearch {
    
    static $count_QS = 0;       //static counter of all instances of this class
    
    private $name;              //name of the input/select field
    private $search;            //may be an object or a string
    private $avatarLike;        //like "user_id", "username", "Seminar_id" or stuff
    private $withButton;        //if true, the field will be displayed with a looking-glass-button to click on
    private $specialBeschriftung;
    private $selectBox = true;
    private $box_width = "233"; //width of the box withButton
    private $box_align = "right";//align of the lookingglass in the withButton-box
    
    /**
     * returns an instance of QuickSearch so you can use it as singleton
     * @return object of type QuickSearch
     */
    public static function get($name, $search = NULL) {
    	return new QuickSearch($name, $search);
    }
    
    
    /**
     * constructor which prepares a searchfield for persons, courses, institutes or
     * special items you may want to search for. This is a GUI-class, see
     * QuickSearch.class.php for further documentation.
     * @param name:    the name of the destinated variable in your html-form. Handle it
     * as if it was an '<input type="text" name="yourname">' input.
     * @param search:    if set to user_id, username, Seminar_id, Arbeitsgruppe_id or Institute_id
     * the searchfield will automatically search for persons, courses, workgroups, institutes and
     * you don't need to call the specialSearch-method.
     */
    public function QuickSearch($name, $search = NULL) {
        $this->count_QS++;
        $this->name = $name;
        $this->withButton = false;
        $this->avatarLike = "";
        if ($search instanceof SearchType) {
            $this->search = $search;
        } else {
            $this->search = NULL;
        }
    }
    
    /**
     * if set to true, the searchfield will be a nice-looking grey searchfield with
     * a magnifier-symbol as a submit-button. Set this to false to create your own
     * submit-button and style of the form.
     * @param design:    associative array of params.
     * @return self
     */
    public function withButton($design = array()) {
        $this->withButton = true;
        if (isset($design['width'])) {
            $this->box_width = $design['width'];
        }
        $this->box_align = $design['align'] ? $design['align'] : "right";
        return $this;
    }
    
    /**
     * this will disable a submit button for the searchfield
     * @return self
     */
    public function withoutButton() {
        $this->withButton = false;
        return $this;
    }
    
    /**
     * Here you can set a default-value for the searchfield
     * @param valueID: the default-ID that should be stored
     * @param valueName: the default value, that should be displayed
     * - remember that these may not be the same, they may be
     *   something like "ae2b1fca515949e5d54fb22b8ed95575", "test_dozent"
     * @return self
     */
    public function defaultValue($valueID, $valueName) {
        $this->defaultID = $valueID;
        $this->defaultName = $valueName;
        return $this;
    }
    
    /**
     * defines a css class for the searchfield
     * @param class: any css class name for the "input type=text" tag
     * @return self
     */
    public function setInputClass($class) {
        $this->inputClass = $class;
        return $this;
    }
    
    /**
     * defines css-proporties for searchfield that will be included as 'style="$style"'
     * @param style: one or more css-proporties separated with ";"
     * @return self
     */
    public function setInputStyle($style) {
        $this->inputStyle = $style;
        return $this;
    }
    
    /**
     * sets the color of the description viewable, while the user has 
     * written nothing in the searchfield. Invisible for Non-JS users. 
     * @param color: string like "green" or "#ff0000"  
     * @return self
     */
    public function setDescriptionColor($color) {
        $this->descriptionColor = $color;
        return $this;
    }
    
    /**
     * disables the select-box, which is displayed for non-JS users who will 
     * choose with this box, which item they want to have.
     */
    public function noSelectbox($set = true) {
        $this->selectBox = !$set;
        return $this;
    }
    
    /**
     * set a JavaScript-function to be fired after the user has selected a
     * value in the QuickSearch field. Arguments are: 
     * function fireme(id_of_item, text_of_item)
     * example setting: QS->fireJSFunctionOnSelect('fireme');
     * @param function_name:  string
     * @return self
     */
    public function fireJSFunctionOnSelect($function_name) {
    	$this->JSfunction = $function_name;
    	return $this;
    }
    
    /**
     * assigns special attributes to the html-element of the searchfield
     * @param attr_array: array like array("title" => "hello world")
     * @return self
     */
    public function withAttributes($attr_array) {
    	if (is_array($attr_array)) {
    		$this->withAttributes = $attr_array;
    	}
    	return $this;
    }
    
    /**
     * last step: display everything and be happy!
     * comment: the Ajax-Result (for the javascript-instant-search) will be also displayed here,
     * but that does not need to concern you.
     */
    public function render() {
    	if (trim(Request::get($this->name.'_parameter')) 
    	       && (Request::get($this->name.'_parameter') != $this->beschriftung()) 
    	       && !Request::get($this->name)
    	       && $this->selectBox) {
            //No Javascript activated and having searched:
            $searchresults = $this->searchresults(Request::get($this->name.'_parameter'));

    		$template = $GLOBALS['template_factory']->open('quicksearch/selectbox.php');
    		$template->set_attribute('withButton', $this->withButton);
            $template->set_attribute('box_align', $this->box_align);
            $template->set_attribute('box_width', $this->box_width);
            $template->set_attribute('withAttributes', $this->withAttributes);
            $template->set_attribute('searchresults', $searchresults);
    		$template->set_attribute('name', $this->name);
    		$template->set_attribute('inputClass', $this->inputClass);
    		return $template->render();

    	} else {
    		//Abfrage in der Session speichern:
    		$query_id = md5(serialize($this->search));
    		if ($this->specialQuery) {
    			$_SESSION['QuickSearches'][$query_id]['query'] = $this->specialQuery;
    		} elseif ($this->search instanceof SearchType) {
    			$_SESSION['QuickSearches'][$query_id]['object'] = serialize($this->search);
    			if ($this->search instanceof SearchType) {
    				$_SESSION['QuickSearches'][$query_id]['includePath'] = $this->search->includePath();
    			}
    			$_SESSION['QuickSearches'][$query_id]['time'] = time();
    		} else {
    			$_SESSION['QuickSearches'][$query_id]['query'] = $this->search;
    		}
    		$_SESSION['QuickSearches'][$query_id]['time'] = time();
    		//var_dump($_SESSION['QuickSearches'][$query_id]);
    		//Ausgabe:
    		$template = $GLOBALS['template_factory']->open('quicksearch/inputfield.php');
    		$template->set_attribute('withButton', $this->withButton);
            $template->set_attribute('box_align', $this->box_align);
            $template->set_attribute('box_width', $this->box_width);
            $template->set_attribute('inputStyle', $this->inputStyle ? $this->inputStyle : "");
    		$template->set_attribute('beschriftung', $this->beschriftung());
    		$template->set_attribute('name', $this->name);
    		$template->set_attribute('defaultID', $this->defaultID);
    		$template->set_attribute('defaultName', $this->defaultName);
    		$template->set_attribute('inputClass', $this->inputClass);
            $template->set_attribute('withAttributes', $this->withAttributes ? $this->withAttributes : array());
            $template->set_attribute('descriptionColor', $this->descriptionColor ? $this->descriptionColor : "#888888");
            $template->set_attribute('JSfunction', $this->JSfunction);
    		$template->set_attribute('count_QS', $this->count_QS);
    		$template->set_attribute('query_id', $query_id);
    		return $template->render();
    	}
    }
    
    //////////////////////////////////////////////////////////////////////////////
    //                               private-methods                            //
    //////////////////////////////////////////////////////////////////////////////
    
    /**
     * private method to get a result-array in the way of array(array(item_id, item-name)).
     * @param request:    the request from the searchfield typed by the user.
     * @return:    array(array(item_id, item-name), ...) mostly limited to 5.
     */
    private function searchresults($request) {
        if ($this->search instanceof SearchType) {
            try {
                $results = $this->search->getResults($request, $_REQUEST);
            } catch (Exception $exception) {
                //Der Programmierer will ja seine Fehler sehen:
                return array(array("", $exception->getMessage()));
            }
            return $results;
        } else {
            $result = array(array("", _("Kein korrektes Suchobjekt angegeben.")));
            return $result;
        }
    }
    
    /**
     * get the label of the searchfield that is written in javascript and disappears
     * when the user focusses on the searchfield.
     * @return:    localized-string
     */
    private function beschriftung() {
        if ($this->search instanceof SearchType) {
        	return $this->search->getTitle();
        } else {
            return "";
        }
    }
}
