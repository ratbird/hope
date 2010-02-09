<?php
/*
 * CalendarNavigation.php - navigation for calendar
 *
 * Copyright (c) 2009  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
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
}
