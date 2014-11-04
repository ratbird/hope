<?php

require_once 'lib/functions.php';
require_once 'studip_controller.php';
require_once 'authenticated_controller.php';

/**
 * This class is used by singlepersonsearch objects to perform search actions via ajax.
 */
 
class SinglePersonSearchController extends AuthenticatedController
{

    /**
     * Entry point of the controller that displays the start page of Stud.IP
     *
     * @param string $action
     * @param string $widgetId
     *
     * @return void
     */
    function index_action($action = false, $widgetId = null)
    {
        
    }
    
    /**
     * Ajax action used for searching persons.
     *
     * @param $name string name of SinglePersonSearch object
     * @param $searchterm string searchterm
     */
    public function ajax_search_action($name) {
        $searchterm = studip_utf8decode(Request::get("s"));
        $searchterm = str_replace(",", " ", $searchterm);
        $searchterm = preg_replace('/\s+/', ' ', $searchterm);

        // execute searchobject if searchterm is at least 3 chars long
        if (strlen($searchterm) >= 3) {
            $sp = SinglePersonSearch::load($name);
            $searchObject = $sp->getSearchObject();
            $result = array_map(function($r) {return $r['user_id'];}, $searchObject->getResults($searchterm, array(), 50));
            $this->result = User::findMany($result, 'ORDER BY nachname asc, vorname asc');
        }
        $this->render_template('single_person_search/ajax.php');
    }

}
