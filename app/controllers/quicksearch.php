<?php
/*
 * quicksearch.php
 *
 * Copyright (c) 2010  Rasmus Fuhse
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'lib/classes/searchtypes/SearchType.class.php';

require_once "lib/classes/Avatar.class.php";
require_once "lib/classes/CourseAvatar.class.php";
require_once "lib/classes/InstituteAvatar.class.php";
require_once 'lib/trails/AuthenticatedController.php';

/**
 * Controller for the ajax-response of the QuickSearch class found in 
 * lib/classes/QuickSearch.class.php 
 */
class QuicksearchController extends AuthenticatedController {

    private $specialSQL;

    /**
     * the one action which is called by the QuickSearch-form when typed in
     * by user.
     * @param query_id string: first argument of url -> id of query in session
     */
    public function response_action($query_id) {
        $this->extraInclude($query_id);
        $this->cleanUp();
        $_SESSION['QuickSearches'][$query_id]['time'] = time();
        $this->search = $this->getSearch($query_id);
        $this->specialSQL = $_SESSION['QuickSearches'][$query_id]['query'];
        $this->form_data = $this->utf8_array_decode(Request::getArray("form_data"));
        $this->searchresults = $this->getResults(utf8_decode(Request::get('request')));
        $this->render_template('quicksearch/response.php');
    }

    /**
     * instantiates the search-object (or string)
     * @param query_id string: id of the query in session
     * @return object or string: ready search-object or string
     */
    private function getSearch($query_id) {
        if (isset($_SESSION['QuickSearches'][$query_id])) {
            $search_query = $_SESSION['QuickSearches'][$query_id]['query'];
            $search_object = $_SESSION['QuickSearches'][$query_id]['object'];
            if ($search_object) {
                //search with an object:
                return unserialize($search_object);
            } elseif (!in_array($search_query,
                    array("username", "user_id", "Seminar_id",
                         "Institut_id", "Arbeitsgruppe_id"))) {
                //search with a special SQL-query:
                $this->specialSQL = $search_query;
                return "special";
            } else {
                //search for username, Seminar_id and so on:
                return $search_query;
            }
        } else {
            return "";
        }
    }

    /**
     * includes the class of the search-object so we can re-instantiate this object
     * later
     * @param query_id string: id of the query in session
     * @return void
     */
    private function extraInclude($query_id) {
        if ($_SESSION['QuickSearches'][$query_id]['includePath']) {
            include_once($_SESSION['QuickSearches'][$query_id]['includePath']);
        }
    }

    /**
     * formats the results so that the searchword is marked bold
     * @param results array: array of searchresults
     * @return array: array of searchresults formatted
     */
    private function extraResultFormat($results) {
        $input = utf8_decode(Request::get('request'));
        foreach ($results as $key => $result) {
            $results[$key][1] = preg_replace("/(".$input.")/i", "<b>$1</b>", $result[1]);
        }
        return $results;
    }

    /**
     * private method to get a result-array in the way of array(array(item_id, item-name)).
     * @param request:    the request from the searchfield typed by the user.
     * @return:    array(array(item_id, item-name), ...) mostly limited to 5.
     */
    private function getResults($request) {
        if ($this->search instanceof SearchType) {
            try {
                $results = $this->search->getResults($request, $this->form_data);
            } catch (Exception $exception) {
                //Der Programmierer will ja seine Fehler sehen:
                return array(array("", $exception->getMessage()));
            }
            return $this->extraResultFormat($results);
        }
        $result = array(array("", _("Session abgelaufen oder unbekannter Suchtyp")));
        return $result;
    }

    /**
     * deletes all older requests, that have not been used since half an hour
     * @return void
     */
    private function cleanUp() {
        $count = 0;
        $lifetime = $GLOBALS['AUTH_LIFETIME'] ? $GLOBALS['AUTH_LIFETIME'] : 30;
        foreach($_SESSION['QuickSearches'] as $query_id => $query) {
            if (time() - $query['time'] > $lifetime * 60) {
                unset($_SESSION['QuickSearches'][$query_id]);
                $count++;
            }
        }
        return $count;
    }
    
    /**
     * method to recursively convert an array from uft8 to iso-1 
     */
    private function utf8_array_decode($input) {
        $return = array();
        foreach ($input as $key => $val) {
            if( is_array($val) ) {
                $return[$key] = $this->utf8_array_decode($val);
            } else {
                $return[$key] = utf8_decode($val);
            }
        }
        return $return;
    }
}
