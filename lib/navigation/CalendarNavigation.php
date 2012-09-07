<?php
# Lifter010: TODO
/*
 * CalendarNavigation.php - navigation for calendar
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

class CalendarNavigation extends Navigation
{
    /**
     * Initialize a new Navigation instance.
     */
    public function __construct()
    {
        parent::__construct(_('Planer'));

        if (get_config('CALENDAR_ENABLE')) {
            $planerinfo = _('Termine');
        } else {
            $planerinfo = _('Stundenplan');
        }

        $this->setImage('header/schedule.png', array('title' => $planerinfo, "@2x" => TRUE));
    }

    /**
     * Initialize the subnavigation of this item. This method
     * is called once before the first item is added or removed.
     */
    public function initSubNavigation()
    {
        global $perm, $atime;

        parent::initSubNavigation();

        // calendar
        $atime = $atime ? intval($atime) : Request::int($atime);
        if (get_config('CALENDAR_ENABLE')) {
            $navigation = new Navigation(_('Terminkalender'), 'calendar.php', array('caluser' => 'self'));
            $navigation->addSubNavigation('day', new Navigation(_('Tag'), 'calendar.php', array('cmd' => 'showday', 'atime' => $atime)));
            $navigation->addSubNavigation('week', new Navigation(_('Woche'), 'calendar.php', array('cmd' => 'showweek', 'atime' => $atime)));
            $navigation->addSubNavigation('month', new Navigation(_('Monat'), 'calendar.php', array('cmd' => 'showmonth', 'atime' => $atime)));
            $navigation->addSubNavigation('year', new Navigation(_('Jahr'), 'calendar.php', array('cmd' => 'showyear', 'atime' => $atime)));
            $navigation->addSubNavigation('edit', new Navigation(_('Termin anlegen/bearbeiten'), 'calendar.php', array('cmd' => 'edit', 'atime' => $atime)));
            $navigation->addSubNavigation('course', new Navigation(_('Veranstaltungstermine'), 'calendar.php', array('cmd' => 'bind', 'atime' => $atime)));
            $navigation->addSubNavigation('export', new Navigation(_('Export/Sync'), 'calendar.php', array('cmd' => 'export', 'atime' => $atime)));
            if (get_config('CALENDAR_GROUP_ENABLE')) {
                $navigation->addSubNavigation('admin_groups', new Navigation(_('Kalendergruppen'), 'contact_statusgruppen.php', array('nav' => 'calendar')));
            }
            $this->addSubNavigation('calendar', $navigation);
        }

        // schedule
        if (!$perm->have_perm('admin') && get_config('SCHEDULE_ENABLE')) {
            $navigation = new Navigation(_('Stundenplan'), 'dispatch.php/calendar/schedule');
            $this->addSubNavigation('schedule', $navigation);
        }
    }
}
