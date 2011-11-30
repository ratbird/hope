<?php
# Lifter010: TODO
/**
 * StudipNavigation.php - Stud.IP root navigation class
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
 */

require_once 'AdminNavigation.php';
require_once 'BrowseNavigation.php';
require_once 'CalendarNavigation.php';
require_once 'CommunityNavigation.php';
require_once 'CourseNavigation.php';
require_once 'FooterNavigation.php';
require_once 'HelpNavigation.php';
require_once 'LoginNavigation.php';
require_once 'MessagingNavigation.php';
require_once 'ProfileNavigation.php';
require_once 'SearchNavigation.php';
require_once 'StartNavigation.php';
require_once 'ToolsNavigation.php';

/**
 * This is the class for the top navigation (toolbar) in the page header.
 */
class StudipNavigation extends Navigation
{
    /**
     * Initialize the subnavigation of this item. This method
     * is called once before the first item is added or removed.
     */
    public function initSubNavigation()
    {
        global $perm, $user;

        parent::initSubNavigation();

        // top navigation (toolbar)
        $this->addSubNavigation('start', new StartNavigation());

        // if the user is not logged in, he will see the free courses, otherwise
        // the my courses page will be shown.
        if (is_object($user) && $user->id != 'nobody' || get_config('ENABLE_FREE_ACCESS')) {
            $this->addSubNavigation('browse', new BrowseNavigation());
        }

        // if a course is selected, the navigation for it will be loaded, but
        // it will not be shown in the main toolbar
        if ($_SESSION['SessionSeminar']) {
            $this->addSubNavigation('course', new CourseNavigation());
        }

        if (is_object($user) && $user->id != 'nobody') {
            // internal message system
            $this->addSubNavigation('messaging', new MessagingNavigation());

            // community page
            $this->addSubNavigation('community', new CommunityNavigation());

            // user profile page
            $this->addSubNavigation('profile', new ProfileNavigation());

            // calendar and schedule page
            $this->addSubNavigation('calendar', new CalendarNavigation());

            // search page
            $this->addSubNavigation('search', new SearchNavigation());
        }

        // tools page
        if (is_object($user) && $perm->have_perm('autor')) {
            $this->addSubNavigation('tools', new ToolsNavigation());
        }

        // admin page
        if (is_object($user) && $perm->have_perm('admin')) {
            $this->addSubNavigation('admin', new AdminNavigation());
        }

        // resource managment, if it is enabled
        if (get_config('RESOURCES_ENABLE')) {
            //TODO: suboptimal, es sollte eine ResourcesNavigation geben
            $navigation = new Navigation(_('Ressourcen'), 'resources.php', array('view' => 'resources'));

            if (is_object($user) && $perm->have_perm('admin')) {
                $navigation->setImage('header/resources.png', array('title' => _('Zur Ressourcenverwaltung')));
            }

            $this->addSubNavigation('resources', $navigation);
        }

        // quick links
        $links = new Navigation('Links');

        // settings
        if (is_object($user) && $perm->have_perm('autor')) {
            $navigation = new Navigation(_('Einstellungen'));
            $navigation->addSubNavigation('general', new Navigation(_('Allgemeines'), 'edit_about.php', array('view' => 'allgemein')));
            $navigation->addSubNavigation('privacy', new Navigation(_('Privatsphäre'), 'edit_about.php', array('view' => 'privacy')));
            $navigation->addSubNavigation('messaging', new Navigation(_('Nachrichten'), 'edit_about.php', array('view' => 'Messaging')));
            $navigation->addSubNavigation('forum', new Navigation(_('Forum'), 'edit_about.php', array('view' => 'Forum')));

            if (get_config('CALENDAR_ENABLE')) {
                $navigation->addSubNavigation('calendar', new Navigation(_('Terminkalender'), 'edit_about.php', array('view' => 'calendar')));
            }
            
            if (!$perm->have_perm('admin')) {
                if (get_config('MAIL_NOTIFICATION_ENABLE')) {
                    $navigation->addSubNavigation('notification', new Navigation(_('Benachrichtigung'), 'sem_notification.php'));
                }
            }

            if (isDefaultDeputyActivated() && $perm->get_perm() == 'dozent') {
                $navigation->addSubNavigation('deputies', new Navigation(_('Standardvertretung'), 'edit_about.php', array('view' => 'deputies')));
            }

            $links->addSubNavigation('settings', $navigation);
        }

        // help
        $links->addSubNavigation('help', new HelpNavigation(_('Hilfe')));

        // login / logout
        if (is_object($user) && $user->id != 'nobody') {
            $links->addSubNavigation('logout', new Navigation(_('Logout'), 'logout.php'));
        } else {
            if (in_array('CAS', $GLOBALS['STUDIP_AUTH_PLUGIN'])) {
                $links->addSubNavigation('login_cas', new Navigation(_('Login CAS'), 'index.php?again=yes&sso=cas'));
            }

            if (in_array('Shib', $GLOBALS['STUDIP_AUTH_PLUGIN'])) {
                $links->addSubNavigation('login_shib', new Navigation(_('Login Shibboleth'), 'index.php?again=yes&sso=shib'));
            }

            $links->addSubNavigation('login', new Navigation(_('Login'), 'index.php?again=yes'));
        }

        $this->addSubNavigation('links', $links);

        // footer links
        $this->addSubNavigation('footer', new FooterNavigation(_('Footer')));

        // login page
        $this->addSubNavigation('login', new LoginNavigation(_('Login')));
    }
}
