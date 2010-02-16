<?php
/*
 * StudipNavigation.php - Stud.IP root navigation class
 *
 * Copyright (c) 2009  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'AdminNavigation.php';
require_once 'BrowseNavigation.php';
require_once 'CalendarNavigation.php';
require_once 'ChatNavigation.php';
require_once 'CourseNavigation.php';
require_once 'HomepageNavigation.php';
require_once 'LoginNavigation.php';
require_once 'MessagingNavigation.php';
require_once 'OnlineNavigation.php';
require_once 'StartNavigation.php';

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

            if (get_config('CHAT_ENABLE')) {
                $this->addSubNavigation('chat', new ChatNavigation());
            }

            $this->addSubNavigation('online', new OnlineNavigation());
        }

        if (is_object($user) && $perm->have_perm('autor')) {
            $this->addSubNavigation('homepage', new HomepageNavigation());
        }

        if (is_object($user) && $user->id != 'nobody') {
            $this->addSubNavigation('calendar', new CalendarNavigation());
        }

        if (is_object($user) && $perm->have_perm('tutor')) {
            $this->addSubNavigation('admin', new AdminNavigation());
        }

        // quick links
        $links = new Navigation('Links');

        if (is_object($user) && $user->id != 'nobody') {
            $links->addSubNavigation('search', new Navigation(_('Suche'), 'auswahl_suche.php'));
        }

        $links->addSubNavigation('imprint', new Navigation(_('Impressum'), 'dispatch.php/siteinfo/show'));

        if (get_config('EXTERNAL_HELP')) {
            $links->addSubNavigation('help', new Navigation(_('Hilfe'), format_help_url($GLOBALS['HELP_KEYWORD'])));
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

        // admin plugins
        if (is_object($user) && $perm->have_perm('admin')) {
            PluginEngine::getPlugins('AdministrationPlugin');
        }

        // system plugins
        foreach (PluginEngine::getPlugins('SystemPlugin') as $plugin) {
            if ($plugin instanceof AbstractStudIPSystemPlugin) {
                if ($plugin->hasBackgroundTasks()) {
                    $plugin->doBackgroundTasks();
                }
            }
        }
    }
}
