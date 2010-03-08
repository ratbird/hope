<?php
/*
 * HomepageNavigation.php - navigation for home page
 *
 * Copyright (c) 2009  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

class HomepageNavigation extends Navigation
{
    /**
     * Initialize a new Navigation instance.
     */
    public function __construct()
    {
        global $user, $auth, $homepage_cache_own, $LastLogin;

        parent::__construct(_('Homepage'));

        $db = DBManager::get();
        $time = $homepage_cache_own ? $homepage_cache_own : $LastLogin;

        $result = $db->query("SELECT COUNT(post_id) AS count FROM guestbook
                                WHERE range_id = '".$user->id."'
                                AND user_id != '".$user->id."'
                                AND mkdate > '".$time."'");

        $count = $result->fetchColumn();

        if ($count > 0) {
            $hp_txt = _('Zu Ihrer Einstellungsseite') . ', ' .
                sprintf(ngettext('Sie haben %d neuen Eintrag im Gästebuch.',
                                 'Sie haben %d neue Einträge im Gästebuch.', $count), $count);
            $picture = 'header_einst2';
            $hp_link = 'about.php?guestbook=open#guest';
        } else {
            $hp_txt = _('Zu Ihrer Einstellungsseite');
            $picture = 'header_einst';
            $hp_link = 'about.php';
        }

        $hp_txt .= sprintf(' (%s, %s)', $auth->auth['uname'], $auth->auth['perm']);
        $this->setURL($hp_link);
        $this->setImage($picture, array('title' => $hp_txt));
    }

    /**
     * Determine whether this navigation item is active.
     */
    public function isActive()
    {
        $active = parent::isActive();

        if ($active) {
            URLHelper::addLinkParam('username', $GLOBALS['username']);
        }

        return $active;
    }

    /**
     * Initialize the subnavigation of this item. This method
     * is called once before the first item is added or removed.
     */
    public function initSubNavigation()
    {
        global $auth, $perm;
        global $my_about, $username;

        parent::initSubNavigation();

        if (Request::get('usr_name')) {
                $username = Request::get('usr_name');
        } else if (Request::get('username')) {
                $username = Request::get('username');
        } else {
                $username = $auth->auth['uname'];
        }

        // this really should not be here
        $username = preg_replace('/[^\w@.-]/', '', $username);

        // homepage
        $navigation = new Navigation(_('Alle'));
        $navigation->addSubNavigation('all', new Navigation(_('Persönliche Homepage'), 'about.php'));
        $this->addSubNavigation('view', $navigation);

        // avatar
        $navigation = new Navigation(_('Bild'));
        $navigation->addSubNavigation('upload', new Navigation(_('Hochladen des persönlichen Bildes'), 'edit_about.php', array('view' => 'Bild')));
        $this->addSubNavigation('avatar', $navigation);

        // profile data
        $navigation = new Navigation(_('Nutzerdaten'));
        $navigation->addSubNavigation('profile', new Navigation(_('Allgemein'), 'edit_about.php', array('view' => 'Daten')));
        $navigation->addSubNavigation('private', new Navigation(_('Privat'), 'edit_about.php', array('view' => 'Lebenslauf')));

        if ($my_about->auth_user['perms'] != 'admin' && $my_about->auth_user['perms'] != 'root') {
            $navigation->addSubNavigation('study_data', new Navigation(_('Studiendaten'), 'edit_about.php', array('view' => 'Studium')));
        }

        if ($my_about->auth_user['perms'] != 'root') {
            if (count(UserDomain::getUserDomains())) {
                $navigation->addSubNavigation('user_domains', new Navigation(_('Nutzerdomänen'), 'edit_about.php', array('view' => 'userdomains')));
            }

            if ($my_about->special_user) {
                $navigation->addSubNavigation('inst_data', new Navigation(_('Einrichtungsdaten'), 'edit_about.php', array('view' => 'Karriere')));
            }
        }

        $this->addSubNavigation('edit', $navigation);

        // user defined sections
        $navigation = new Navigation(_('eigene Kategorien'));
        $navigation->addSubNavigation('edit', new Navigation(_('Eigene Kategorien bearbeiten'), 'edit_about.php', array('view' => 'Sonstiges')));
        $this->addSubNavigation('sections', $navigation);

        // tools
        $navigation = new Navigation(_('Tools'));
        $navigation->addSubNavigation('news', new Navigation(_('News'), 'admin_news.php', array('range_id' => 'self')));
        $navigation->addSubNavigation('literature', new Navigation(_('Literatur'), 'admin_lit_list.php', array('_range_id' => 'self')));
        $navigation->addSubNavigation('vote', new Navigation(_('Votings und Tests'), 'admin_vote.php?page=overview', array('showrangeID' => $username)));
        $navigation->addSubNavigation('evaluation', new Navigation(_('Evaluationen'), 'admin_evaluation.php', array('rangeID' => $username)));

        if ($perm->have_perm('autor') && get_config('ELEARNING_INTERFACE_ENABLE')) {
            $navigation->addSubNavigation('elearning', new Navigation(_('Meine Lernmodule'), 'my_elearning.php'));
        }

        $this->addSubNavigation('tools', $navigation);

        // settings
        if ($username == $auth->auth['uname']) {
            $navigation = new Navigation(_('Einstellungen'));
            $navigation->addSubNavigation('general', new Navigation(_('Allgemeines'), 'edit_about.php', array('view' => 'allgemein')));
            $navigation->addSubNavigation('forum', new Navigation(_('Forum'), 'edit_about.php', array('view' => 'Forum')));

            if (!$perm->have_perm('admin')) {
                if (get_config('CALENDAR_ENABLE')) {
                    $navigation->addSubNavigation('calendar', new Navigation(_('Terminkalender'), 'edit_about.php', array('view' => 'calendar')));
                }

                $navigation->addSubNavigation('schedule', new Navigation(_('Stundenplan'), 'edit_about.php', array('view' => 'Stundenplan')));
            }

            $navigation->addSubNavigation('messaging', new Navigation(_('Messaging'), 'edit_about.php', array('view' => 'Messaging')));
            $navigation->addSubNavigation('rss', new Navigation(_('RSS-Feeds'), 'edit_about.php', array('view' => 'rss')));

            if (!$perm->have_perm('admin')) {
                if (get_config('MAIL_NOTIFICATION_ENABLE')) {
                    $navigation->addSubNavigation('notification', new Navigation(_('Benachrichtigung'), 'edit_about.php', array('view' => 'notification')));
                }

                $navigation->addSubNavigation('login', new Navigation(_('Login'), 'edit_about.php', array('view' => 'Login')));
            }

            $this->addSubNavigation('settings', $navigation);
        }

        // activated plugins
        PluginEngine::getPlugins('HomepagePlugin');
    }
}
