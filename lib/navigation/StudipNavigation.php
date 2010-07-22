<?php
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
 * @copyright   2010 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
*/

//Imports
require_once 'StartNavigation.php';
require_once 'BrowseNavigation.php';
require_once 'CourseNavigation.php';
require_once 'MessagingNavigation.php';
require_once 'CommunityNavigation.php';
require_once 'ProfileNavigation.php';
require_once 'CalendarNavigation.php';
require_once 'SearchNavigation.php';
require_once 'ToolsNavigation.php';
require_once 'AdminNavigation.php';
require_once 'AccountNavigation.php';
require_once 'LoginNavigation.php';
require_once 'HelpNavigation.php';

/**
 * This is the class for the main navigation (toolbar) at the top of the page
 * It's includes all subnavigation depending on the permissions of the user.
 *
 */
class StudipNavigation extends Navigation
{
    /**
     * Initialize the subnavigation of this item. This method
     * is called once before the first item is added or removed.
     */
    public function initSubNavigation()
    {
        global $user, $perm, $user;

        parent::initSubNavigation();

        // top navigation (toolbar)
        $this->addSubNavigation('start', new StartNavigation());

        // if the user is not logged in, he will see the free courses, otherwise
        // the my seminars page will be shown.
        if (is_object($user) && $user->id != 'nobody' || get_config('ENABLE_FREE_ACCESS')) {
            $this->addSubNavigation('browse', new BrowseNavigation());
        }

        // if a course is selected, the navigation for it will be loaded, but
        // it will not be shown in the main toolbar
        if ($_SESSION['SessionSeminar']) {
            $this->addSubNavigation('course', new CourseNavigation());
        }

        // the internal message system
        if (is_object($user) && $user->id != 'nobody') {
            $this->addSubNavigation('messaging', new MessagingNavigation());
        }

        // the new community page
        if (is_object($user) && $user->id != 'nobody') {
            $this->addSubNavigation('community', new CommunityNavigation());
        }

        // the user profile page. to see this navigation, the user has to be at
        //least an "autor"
        if (is_object($user) && $perm->have_perm('autor')) {
            $this->addSubNavigation('profil', new ProfileNavigation());
        }

        // the calendar, schedule page
        if (is_object($user) && $user->id != 'nobody') {
            $this->addSubNavigation('calendar', new CalendarNavigation());
        }

        // the new search page
        if (is_object($user) && $user->id != 'nobody') {
            $this->addSubNavigation('search', new SearchNavigation());
        }

        // the new tools page. to see this navigation, the user has to be at
        //least an "autor"
        if (is_object($user) && $perm->have_perm('autor')) {
            $this->addSubNavigation('tools', new ToolsNavigation());
        }

        //the admin page is only for tutors or higher permissions
        if (is_object($user) && $perm->have_perm('tutor')) {
            $this->addSubNavigation('admin', new AdminNavigation());
        }

        // the resourcemanagment, if it is enabled. only available for admins or higher
        if (is_object($user) && $perm->have_perm('admin') && get_config('RESOURCES_ENABLE')) {
            //TODO: suboptimal, es sollte eine ResourcesNavigation geben
            $resources_nav = new Navigation(_('Ressourcen'), 'resources.php');
            $resources_nav->setImage('header/header_admin', array('title' => _('Zur Ressourcenverwaltung')));
            $this->addSubNavigation('resources', $resources_nav);
        }

        // quick links
        //TODO: besser separat oder so?
        $links = new Navigation('Links');

        //settings
        if (is_object($user) && $user->id != 'nobody') {
            //TODO: suboptimal
            $this->addSubNavigation('account', new AccountNavigation());
            $links->addSubNavigation('account', new Navigation(_('Einstellungen'), 'edit_about.php', array('view' => 'allgemein')));
        }

        //sitemap
        if (is_object($user) && $user->id != 'nobody') {
            //TODO: suboptimal, in die hauptnavi, damit sitemaps eigene reiter bekommt, in die $links, damit es in der 2. zeile angezeigt wird.
            $this->addSubNavigation('sitemap', new Navigation(_('Sitemap'), 'dispatch.php/sitemap/'));
            $links->addSubNavigation('sitemap', new Navigation(_('Sitemap'), 'dispatch.php/sitemap/'));
        }

        //imprint
        $links->addSubNavigation('imprint', new Navigation(_('Impressum'), 'dispatch.php/siteinfo/show'));

        //help
        if (get_config('EXTERNAL_HELP')) {
            $links->addSubNavigation('help', new HelpNavigation(_('Hilfe')));
        }

        //login or logout
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

        //adding the quick links to the main navigation
        $this->addSubNavigation('links', $links);

        // login page
        $this->addSubNavigation('login', new LoginNavigation(_('Login')));
    }
}
