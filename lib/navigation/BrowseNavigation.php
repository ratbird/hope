<?php
# Lifter010: TODO
/*
 * BrowseNavigation.php - navigation for my courses / institutes
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Elmar Ludwig
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

class BrowseNavigation extends Navigation
{
    /**
     * Initialize a new Navigation instance.
     */
    public function __construct()
    {
        global $user, $perm;

        // check if logged in
        if (is_object($user) && $user->id != 'nobody') {
            $coursetext = _('Veranstaltungen');
            $courseinfo = _('Meine Veranstaltungen & Einrichtungen');
        } else {
            $coursetext = _('Freie');
            $courseinfo = _('Freie Veranstaltungen');
            $courselink = 'freie.php';
        }

        parent::__construct($coursetext, $courselink);

        if (!$_SESSION['SessionSeminar']) {
            $this->setImage('header/seminar.png', array('title' => $courseinfo, "@2x" => TRUE));
        }
    }

    /**
     * Initialize the subnavigation of this item. This method
     * is called once before the first item is added or removed.
     */
    public function initSubNavigation()
    {
        global $user, $perm;

        parent::initSubNavigation();
        $sem_create_perm = in_array(get_config('SEM_CREATE_PERM'), array('root','admin','dozent')) ? get_config('SEM_CREATE_PERM') : 'dozent';

        // my courses
        if (is_object($user) && $user->id != 'nobody' && !$perm->have_perm('root')) {
            $navigation = new Navigation(_('Meine Veranstaltungen'));
            $navigation->addSubNavigation('list', new Navigation(_('Übersicht'), 'meine_seminare.php'));

            if ($perm->have_perm('admin')) {
                $navigation->addSubNavigation('schedule', new Navigation(_('Veranstaltungs-Stundenplan'), 'dispatch.php/calendar/schedule'));
            } else {
                $navigation->addSubNavigation('group', new Navigation(_('Gruppenzuordnung'), 'gruppe.php'));

                if (get_config('STM_ENABLE') && $perm->have_perm('dozent')) {
                    $navigation->addSubNavigation('modules', new Navigation(_('Meine Studienmodule'), 'my_stm.php'));
                }

                $navigation->addSubNavigation('archive', new Navigation(_('Meine archivierten Veranstaltungen'), 'my_archiv.php'));

                if (get_config('EXPORT_ENABLE')) {
                    $navigation->addSubNavigation('record_of_study', new Navigation(_('Druckansicht'), 'recordofstudy.php'));
                }
            }
            if ($perm->have_perm($sem_create_perm)) {
                $navigation->addSubNavigation('create', new Navigation(_('Neue Veranstaltung anlegen'), 'admin_seminare_assi.php?new_session=TRUE'));
            }
            $this->addSubNavigation('my_courses', $navigation);
        }
    }
}
