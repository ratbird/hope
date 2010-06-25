<?php
/*
 * AccountNavigation.php - navigation for account page
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

class AccountNavigation extends Navigation
{
    /**
     * Initialize a new Navigation instance.
     */
    public function __construct()
    {
        parent::__construct(_('Einstellungen'), 'edit_about.php', array('view' => 'allgemein'));
    }

    /**
     * Initialize the subnavigation of this item. This method
     * is called once before the first item is added or removed.
     */
    public function initSubNavigation()
    {
        global $perm;

        parent::initSubNavigation();

        // general
        $this->addSubNavigation('general', new Navigation(_('Allgemeines'), 'edit_about.php', array('view' => 'allgemein')));

        $this->addSubNavigation('privacy', new Navigation(_('Privatsphäre'), 'edit_about.php', array('view' => 'privacy')));

        $this->addSubNavigation('messaging', new Navigation(_('Nachrichten'), 'edit_about.php', array('view' => 'Messaging')));

        $this->addSubNavigation('forum', new Navigation(_('Forum'), 'edit_about.php', array('view' => 'Forum')));

        if (get_config('CALENDAR_ENABLE')) {
            $this->addSubNavigation('calendar', new Navigation(_('Terminkalender'), 'edit_about.php', array('view' => 'calendar')));
        }

        if (!$perm->have_perm('admin')) {
            $this->addSubNavigation('schedule', new Navigation(_('Stundenplan'), 'edit_about.php', array('view' => 'Stundenplan')));
        }


        $this->addSubNavigation('rss', new Navigation(_('RSS-Feeds'), 'edit_about.php', array('view' => 'rss')));

        if (!$perm->have_perm('admin')) {
            if (get_config('MAIL_NOTIFICATION_ENABLE')) {
                $this->addSubNavigation('notification', new Navigation(_('Benachrichtigung'), 'sem_notification.php'));
            }

            $this->addSubNavigation('login', new Navigation(_('Login'), 'edit_about.php', array('view' => 'Login')));
        }
    }
}
