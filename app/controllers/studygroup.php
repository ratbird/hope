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
     * common tasks for all actions
     */
    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        // allow only "word" characters in arguments
        $this->validate_args($args);
    }

    /**
     * Displays a pageable and sortable overview of all studygoups combined with
     * a search form to query for specific studygroup 
     * 
     * @param $page
     * @param $sort
     */
    function browse_action($page = 1, $sort = "founded_asc")
    {
        PageLayout::setTitle(_('Studiengruppen suchen'));
        Navigation::activateItem('/community/studygroups/browse');
        PageLayout::setHelpKeyword('Basis.SuchenStudiengruppen');
        $this->sort = preg_replace('/\\W/', '', $sort);
        $this->page = intval($page);
        $this->userid = $GLOBALS['auth']->auth['uid'];
        $this->search = Request::get("searchtext");
        $reset = false;
        if (Request::get('action') == 'deny') {
            unset($this->flash['searchterm']);
            unset($this->flash['info']);
            $this->page = 1;
            $this->sort = "founded_asc";
            $reset = true;
        }

        $this->lower_bound = ($this->page - 1) * ELEMENTS_PER_PAGE;
        list ($this->sort_type, $this->sort_order) = explode('_', $this->sort);
        
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
        if (empty($groups)) {
            if (!$reset) {
                if (Request::submitted('searchtext') && empty($this->search)) {
                    $this->flash['info'] = _("Der Suchbegriff ist zu kurz.");
                    unset($this->flash['searchterm']);
                } elseif (isset($this->flash['searchterm'])) {
                    $this->flash['info'] = _("Es wurden keine Studiengruppen für den Suchbegriff gefunden");
                }
            }
            $this->anzahl = StudygroupModel::countGroups();
            $this->groups = StudygroupModel::getAllGroups($this->sort, $this->lower_bound, ELEMENTS_PER_PAGE);
        } elseif (!$check || $this->groups) {
            unset($this->flash['info']);
            if($this->page < 1 || $this->page > ceil($this->anzahl/ELEMENTS_PER_PAGE)) $this->page = 1;
        }
    }
}
