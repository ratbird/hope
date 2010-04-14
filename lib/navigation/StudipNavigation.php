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

        if (is_object($user) && $user->id != 'nobody' || get_config('ENABLE_FREE_ACCESS')) {
            $this->addSubNavigation('browse', new BrowseNavigation());
        }

        if ($_SESSION['SessionSeminar']) {
            $this->addSubNavigation('course', new CourseNavigation());
        }

        if (is_object($user) && $user->id != 'nobody') {
            $this->addSubNavigation('messaging', new MessagingNavigation());
        }

        if (is_object($user) && $user->id != 'nobody') {
            $this->addSubNavigation('community', new CommunityNavigation());
        }

        if (is_object($user) && $perm->have_perm('autor')) {
            $this->addSubNavigation('profil', new ProfileNavigation());
        }

        if (is_object($user) && $user->id != 'nobody') {
            $this->addSubNavigation('calendar', new CalendarNavigation());
        }

        if (is_object($user) && $user->id != 'nobody') {
            $this->addSubNavigation('search', new SearchNavigation());
        }

        if (is_object($user) && $user->id != 'nobody') {
            $this->addSubNavigation('tools', new ToolsNavigation());
        }

        if (is_object($user) && $perm->have_perm('tutor')) {
            $this->addSubNavigation('admin', new AdminNavigation());
        }

        if (is_object($user) && $perm->have_perm('admin') && get_config('RESOURCES_ENABLE')) {
            //TODO: suboptimal, es sollte eine ResourcesNavigation geben
            $resources_nav = new Navigation(_('Ressourcen'), 'resources.php');
            $resources_nav->setImage('header_admin', array('title' => _('Zur Ressourcenverwaltung')));
            $this->addSubNavigation('resources', $resources_nav);
        }

        // quick links
        $links = new Navigation('Links');

        //settings
        if (is_object($user) && $user->id != 'nobody') {
            //TODO: suboptimal
            $this->addSubNavigation('account', new AccountNavigation());
            $links->addSubNavigation('account', new Navigation(_('Einstellungen'), 'edit_about.php', array('view' => 'allgemein')));
        }

        $links->addSubNavigation('imprint', new Navigation(_('Impressum'), 'dispatch.php/siteinfo/show'));

        if (get_config('EXTERNAL_HELP')) {
            $links->addSubNavigation('help', new HelpNavigation(_('Hilfe')));
        }

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

        // login page
        $this->addSubNavigation('login', new LoginNavigation(_('Login')));
        }
}
