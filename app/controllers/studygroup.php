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

require_once 'app/controllers/authenticated_controller.php';
require_once 'app/models/studygroup.php';

if (!defined('ELEMENTS_PER_PAGE')) define("ELEMENTS_PER_PAGE", 20);


class StudygroupController extends AuthenticatedController {

    /**
     * Displays a pageable and sortable overview of all studygoups
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

        PageLayout::setTitle(_('Studiengruppen anzeigen'));
        Navigation::activateItem('/community/studygroups/browse');
        $this->groups = $groups;
        $this->anzahl = $anzahl;
        $this->userid = $GLOBALS['auth']->auth['uid'];
        list ($this->sort_type, $this->sort_order) = split('[_]', $this->sort);
      }

    /**
     * Displays a search form to query for specific studygroup
     * 
     * @param $page
     * @param $sort
     */
    function search_action($page = 1, $sort = "founded_asc")
    {
        PageLayout::setTitle(_('Studiengruppen suchen'));
        Navigation::activateItem('/search/studygroups');
        PageLayout::setHelpKeyword('Basis.SuchenStudiengruppen');
        $this->sort = preg_replace('/\\W/', '', $sort);
        $this->page = intval($page);
        $this->userid = $GLOBALS['auth']->auth['uid'];
        $this->search = Request::get("searchtext");
        $reset = false;
        if (Request::get('action') == 'deny') {
            unset($this->flash['searchterm']);
            $this->page = 1;
            $this->sort = "founded_asc";
            $reset = true;
        }

        $this->lower_bound = ($this->page - 1) * ELEMENTS_PER_PAGE;

        if (empty($this->search) && isset($this->flash['searchterm']))  {
            $this->search = $this->flash['searchterm'];
        }
        if (!empty($this->search)) {
            $groups = StudygroupModel::getAllGroups($this->sort, $this->lower_bound, ELEMENTS_PER_PAGE, $this->search);
            $this->flash['searchterm'] = $this->search;
            $this->flash->keep('searchterm');
            $this->anzahl = StudygroupModel::countGroups($this->search);
            $this->groups = $groups;
        }
        // let the user know that there is no studygroup for the searchterm
        if (empty($groups) && !$reset) {
            if( Request::submitted('searchtext')) {
            	$this->flash['info'] = _("Der Suchbegriff ist zu kurz.");
            	unset($this->flash['searchterm']);
            } elseif (isset($this->flash['searchterm'])) {
            	$this->flash['info'] = _("Es wurden keine Studiengruppen für den Suchbegriff gefunden");
            }
        } elseif (!$check) {
            unset($this->flash['info']);
            if($this->page < 1 || $this->page > ceil($this->anzahl/ELEMENTS_PER_PAGE)) $this->page = 1;
            list ($this->sort_type, $this->sort_order) = split('[_]', $this->sort);
        }
    }
}
