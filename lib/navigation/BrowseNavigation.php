<?php
/*
 * BrowseNavigation.php - navigation for my courses / institutes
 *
 * Copyright (c) 2009  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

class BrowseNavigation extends Navigation
{
    /**
     * Initialize a new Navigation instance.
     */
    public function __construct()
    {
        global $user, $perm;

        if (is_object($user) && $user->id != 'nobody') {
            $coursetext = _('Veranstaltungen');
            $courseinfo = _('Meine Veranstaltungen & Einrichtungen');
        } else {
            $coursetext = _('Freie');
            $courseinfo = _('Freie Veranstaltungen');
            $courselink = 'freie.php';
        }

        parent::__construct($coursetext, $courselink);

        $this->setImage('header_meinesem', array('title' => $courseinfo));
    }

    /**
     * Initialize the subnavigation of this item. This method
     * is called once before the first item is added or removed.
     */
    public function initSubNavigation()
    {
        global $perm;

        parent::initSubNavigation();

        // my courses
        if (!$perm->have_perm('root')) {
            $navigation = new Navigation(_('Meine Veranstaltungen'));
            $navigation->addSubNavigation('list', new Navigation(_('Übersicht'), 'meine_seminare.php'));

            if ($perm->have_perm('admin')) {
                $navigation->addSubNavigation('schedule', new Navigation(_('Veranstaltungs-Timetable'), 'mein_stundenplan.php'));
            } else {
                if (get_config('STM_ENABLE') && $perm->have_perm('dozent')) {
                    $navigation->addSubNavigation('modules', new Navigation(_('meine Studienmodule'), 'my_stm.php'));
                }

                $navigation->addSubNavigation('archive', new Navigation(_('meine archivierten Veranstaltungen'), 'my_archiv.php'));

                if (get_config('EXPORT_ENABLE')) {
                    $navigation->addSubNavigation('record_of_study', new Navigation(_('Druckansicht'), 'recordofstudy.php'));
                }
            }

            $this->addSubNavigation('my_courses', $navigation);
        }

        // browse courses
        $navigation = new Navigation(_('Veranstaltungen suchen / hinzufügen'), 'sem_portal.php');

        if ($perm->have_perm('admin')) {
            $navigation->setTitle(_('Veranstaltungen suchen'));
        }

        $navigation->addSubNavigation('all', new Navigation(_('Alle'), 'sem_portal.php?reset_all=TRUE', array('view' => 'all')));

        foreach ($GLOBALS['SEM_CLASS'] as $key => $val)  {
            if (!$val['studygroup_mode']) {
                $navigation->addSubNavigation($key, new Navigation($val['name'], 'sem_portal.php?reset_all=TRUE&cmd=qs', array('view' => $key)));
            }
        }

        if (get_config('STM_ENABLE')) {
            $navigation->addSubNavigation('mod', new Navigation(_('Studienmodule'), 'sem_portal.php?reset_all=TRUE', array('view' => 'mod')));
        }

        $this->addSubNavigation('courses', $navigation);

        // browse study groups
        if (get_config('STUDYGROUPS_ENABLE')) {
            $navigation = new Navigation(_('Studiengruppen'));
            $navigation->addSubNavigation('search', new Navigation(_('Studiengruppen suchen'), 'dispatch.php/studygroup/search/1/founded_asc'));
            $navigation->addSubNavigation('browse', new Navigation(_('Studiengruppen anzeigen'), 'dispatch.php/studygroup/browse/1/founded_asc'));
            $this->addSubNavigation('studygroups', $navigation);
        }
    }
}
