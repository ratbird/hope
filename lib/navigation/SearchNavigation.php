<?php
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
 * @copyright   2010 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

class SearchNavigation extends Navigation
{
    /**
     * Initialize a new Navigation instance.
     */
    public function __construct()
    {
        parent::__construct(_('Suche'), 'sem_portal.php');

        //TODO: anpassen
        $image = 'header/header_meinesem';
        $tip = _('Suche');

        $this->setImage($image, array('title' => $tip));
    }

    /**
     * Initialize the subnavigation of this item. This method
     * is called once before the first item is added or removed.
     */
    public function initSubNavigation()
    {
        parent::initSubNavigation();

        // Overview (überflüssig)
        // $this->addSubNavigation('all', new Navigation(_('Übersicht'), 'auswahl_suche.php'));

        // Courses
        $navigation = new Navigation(_('Veranstaltungen'), 'sem_portal.php');
        $navigation->addSubNavigation('all', new Navigation(_('Alle'), 'sem_portal.php?reset_all=TRUE', array('view' => 'all')));
        foreach ($GLOBALS['SEM_CLASS'] as $key => $val) {
            if (!$val['studygroup_mode']) {
                $navigation->addSubNavigation($key, new Navigation($val['name'], 'sem_portal.php?reset_all=TRUE&cmd=qs', array('view' => $key)));
            }
        }
        if (get_config('STM_ENABLE')) {
            $navigation->addSubNavigation('mod', new Navigation(_('Studienmodule'), 'sem_portal.php?reset_all=TRUE', array('view' => 'mod')));
        }
        $this->addSubNavigation('courses', $navigation);

        // Archiv
        $this->addSubNavigation('archiv', new Navigation(_('Archiv'), 'archiv.php'));

        // study groups
        if (get_config('STUDYGROUPS_ENABLE')) {
            $this->addSubNavigation('studygroups', new Navigation(_('Studiengruppen'), 'dispatch.php/studygroup/search'));
        }

        // Personen
        $this->addSubNavigation('persons', new Navigation(_('Personen'), 'browse.php'));

        // Institutes
        $this->addSubNavigation('institutes', new Navigation(_('Einrichtungen'), 'institut_browse.php'));

        // Literatur
        if (get_config('LITERATURE_ENABLE')) {
            $this->addSubNavigation('literatures', new Navigation(_('Literatur'), 'lit_search.php'));
        }

        // Resources
        if (get_config('RESOURCES_ENABLE')) {
            $this->addSubNavigation('resources', new Navigation(_('Ressourcen'), 'resources.php', array('view' => 'search', 'view_mode' => 'search', 'new_search' => 'true')));
        }
    }
}
