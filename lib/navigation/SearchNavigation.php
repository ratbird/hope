<?php
# Lifter010: TODO
/*
 * SearchNavigation.php - navigation for search page
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Elmar Ludwig
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       2.0
 */

/**
 * This navigation includes all search pages depending on the
 * activated modules.
 */
class SearchNavigation extends Navigation
{
    /**
     * Initialize a new Navigation instance.
     */
    public function __construct()
    {
        parent::__construct(_('Suche'));

        $this->setImage('header/search.png', array('title' => _('Suche'), "@2x" => TRUE));
    }

    /**
     * Initialize the subnavigation of this item. This method
     * is called once before the first item is added or removed.
     */
    public function initSubNavigation()
    {
        parent::initSubNavigation();

        // browse courses
        $navigation = new Navigation(_('Veranstaltungen'), 'sem_portal.php');
        $navigation->addSubNavigation('all', new Navigation(_('Alle'), 'sem_portal.php?reset_all=TRUE', array('view' => 'all')));

        foreach ($GLOBALS['SEM_CLASS'] as $key => $val) {
            $navigation->addSubNavigation($key, new Navigation($val['name'], 'sem_portal.php?reset_all=TRUE&cmd=qs', array('view' => $key)));
        }

        // browse modules
        if (get_config('STM_ENABLE')) {
            $navigation->addSubNavigation('mod', new Navigation(_('Studienmodule'), 'sem_portal.php?reset_all=TRUE', array('view' => 'mod')));
        }

        $this->addSubNavigation('courses', $navigation);

        // search archive
        $navigation = new Navigation(_('Archiv'), 'archiv.php');
        $this->addSubNavigation('archive', $navigation);

        // search users
        $navigation = new Navigation(_('Personen'), 'browse.php');
        $this->addSubNavigation('users', $navigation);

        // browse institutes
        $navigation = new Navigation(_('Einrichtungen'), 'institut_browse.php');
        $this->addSubNavigation('institutes', $navigation);

        // search literature
        if (get_config('LITERATURE_ENABLE')) {
            $navigation = new Navigation(_('Literatur'), 'lit_search.php');
            $this->addSubNavigation('literature', $navigation);
        }

        // browse resources
        if (get_config('RESOURCES_ENABLE')) {
            $navigation = new Navigation(_('Ressourcen'), 'resources.php', array('view' => 'search', 'reset' => 'TRUE'));
            $this->addSubNavigation('resources', $navigation);
        }
    }
}
