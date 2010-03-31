<?php
/*
 * CalendarNavigation.php - navigation for calendar
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

class CalendarNavigation extends Navigation
{
    /**
     * Initialize a new Navigation instance.
     */
    public function __construct()
    {
        parent::__construct(_('Planer'));

        if (get_config('CALENDAR_ENABLE')) {
            $planerurl  = 'calendar.php';
            $planerinfo = _('Termine und Kontakte');
        } else {
            $planerurl  = 'mein_stundenplan.php';
            $planerinfo = _('Stundenplan und Kontakte');
        }

        $this->setURL($planerurl);
        $this->setImage('header_planer', array('title' => $planerinfo));
    }

    /**
     * Initialize the subnavigation of this item. This method
     * is called once before the first item is added or removed.
     */
    public function initSubNavigation()
    {
        global $perm;

        parent::initSubNavigation();

        // calendar
        if (get_config('CALENDAR_ENABLE')) {
            $navigation = new Navigation(_('Terminkalender'), 'calendar.php');
            $navigation->addSubNavigation('day', new Navigation(_('Tag'), 'calendar.php', array('cmd' => 'showday')));
            $navigation->addSubNavigation('week', new Navigation(_('Woche'), 'calendar.php', array('cmd' => 'showweek')));
            $navigation->addSubNavigation('month', new Navigation(_('Monat'), 'calendar.php', array('cmd' => 'showmonth')));
            $navigation->addSubNavigation('year', new Navigation(_('Jahr'), 'calendar.php', array('cmd' => 'showyear')));
            $navigation->addSubNavigation('edit', new Navigation(_('Termin anlegen/bearbeiten'), 'calendar.php', array('cmd' => 'edit')));
            $navigation->addSubNavigation('course', new Navigation(_('Veranstaltungstermine'), 'calendar.php', array('cmd' => 'bind')));
            $navigation->addSubNavigation('export', new Navigation(_('Export/Sync'), 'calendar.php', array('cmd' => 'export')));
            $this->addSubNavigation('calendar', $navigation);
        }

        // schedule
        if (!$perm->have_perm('admin')) {
            $this->addSubNavigation('schedule', new Navigation(_('Stundenplan'), 'mein_stundenplan.php'));
        }
    }
}
