<?php
/*
 * StudygroupController
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Klaßen <aklassen@uos.de>
 * @copyright   2009-2010 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     studygroup
 */

require_once 'app/models/studygroup.php';
require_once 'lib/trails/AuthenticatedController.php';

if (!defined('ELEMENTS_PER_PAGE')) define("ELEMENTS_PER_PAGE", 20);


class StudygroupController extends AuthenticatedController {

    /**
     *
     * @param $page
     * @param $sort
     */
    function browse_action($page = 1, $sort = "founded_asc")
    {
        $this->sort = $sort;
        $this->page = $page;

        $anzahl = StudygroupModel::countGroups();

        // lets calculate borders
        if($this->page < 1 || $this->page > ceil($anzahl/ELEMENTS_PER_PAGE)) $this->page = 1;
        $this->lower_bound = ($this->page - 1) * ELEMENTS_PER_PAGE;


        $groups = StudygroupModel::getAllGroups($this->sort, $this->lower_bound, ELEMENTS_PER_PAGE);


        $GLOBALS['CURRENT_PAGE'] =  _('Studiengruppen anzeigen');
        Navigation::activateItem('/community/studygroups/all');
        $this->groups = $groups;
        $this->anzahl = $anzahl;
        $this->userid = $GLOBALS['auth']->auth['uid'];
      }

    /**
     *
     * @param $page
     * @param $sort
     */
    function search_action($page = 1, $sort = "founded_asc")
    {
        //TODO: alle anzeigen vs. suchseite -> navi anpassen
        $GLOBALS['CURRENT_PAGE'] =  _('Studiengruppen suchen');
        Navigation::activateItem('/search/studygroups');
        $this->sort = $sort;
        $this->page = $page;
        $search = Request::get("searchtext");
        if (Request::get('action') == 'deny') {
            unset($this->flash['searchterm']);
        }
        if (empty($search)) {
            if(isset($this->flash['searchterm'])){
                $search = $this->flash['searchterm'];
            }
        }
        if (!empty($search)) {
            $this->lower_bound = ($this->page - 1) * ELEMENTS_PER_PAGE;

            $groups = StudygroupModel::getAllGroups($this->sort, $this->lower_bound, ELEMENTS_PER_PAGE, $search);
            $this->flash['searchterm'] = $search;
            $this->flash->keep('searchterm');
            $this->anzahl = count($groups);
            // lets calculate borders
           if($this->page < 1 || $this->page > ceil($anzahl/ELEMENTS_PER_PAGE)) $this->page = 1;

           $this->groups = $groups;
           $this->userid = $GLOBALS['auth']->auth['uid'];
        }
        // let the user know that there is no studygroup for the searchterm
        if (empty($groups) && isset($this->flash['searchterm'])) {
            $this->flash['info'] = _("Es wurden keine Studiengruppen für den Suchbegriff gefunden");
        } else {
            unset($this->flash['info']);
        }
    }
}
