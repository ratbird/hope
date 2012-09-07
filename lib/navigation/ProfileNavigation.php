<?php
# Lifter010: TODO
/*
 * ProfilNavigation.php - navigation for user profile page
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

require_once 'lib/edit_about.inc.php';

/**
 * Navigation for the user's profile page. This page includes all
 * information about a user and allows editing this data.
 */
class ProfileNavigation extends Navigation
{
    /**
     * Initialize a new Navigation instance.
     */
    public function __construct()
    {
        global $user, $auth, $homepage_cache_own, $LastLogin;

        parent::__construct(_('Profil'));

        $db = DBManager::get();
        $time = $homepage_cache_own ? $homepage_cache_own : $LastLogin;

        $result = $db->query("SELECT COUNT(post_id) AS count FROM guestbook
                                WHERE range_id = '".$user->id."'
                                AND user_id != '".$user->id."'
                                AND mkdate > '".$time."'");

        $count = $result->fetchColumn();
        $this->setBadgeNumber($count);

        if ($count > 0) {
            $hp_txt = _('Zu Ihrer Profilseite') . ', ' .
                sprintf(ngettext('Sie haben %d neuen Eintrag im Gästebuch.',
                                 'Sie haben %d neue Einträge im Gästebuch.', $count), $count);
            $hp_class = 'new';
            $hp_link = 'about.php?guestbook=open#guest';
        } else {
            $hp_txt = _('Zu Ihrer Profilseite');
            $hp_link = 'about.php';
        }

        $hp_txt .= sprintf(' (%s, %s)', $auth->auth['uname'], $auth->auth['perm']);
        $this->setURL($hp_link);
        $this->setImage('header/profile.png', array('title' => $hp_txt, 'class' => $hp_class, "@2x" => TRUE));
    }

    /**
     * Determine whether this navigation item is active.
     */
    public function isActive()
    {
        $active = parent::isActive();

        if ($active) {
            URLHelper::addLinkParam('username', Request::get('username'));
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

        parent::initSubNavigation();

        $username = Request::get('username', $auth->auth['uname']);

        // this really should not be here
        $username = preg_replace('/[^\w@.-]/', '', $username);

        $my_about = new about($username, NULL);
        $my_about->get_user_details();

        // profile
        $navigation = new Navigation(_('Profil'), 'about.php');
        $this->addSubNavigation('view', $navigation);

        if ($perm->have_profile_perm('user', $my_about->auth_user['user_id'])) {
            // avatar
            $navigation = new Navigation(_('Bild'), 'edit_about.php', array('view' => 'Bild'));
            $this->addSubNavigation('avatar', $navigation);

            // profile data
            $navigation = new Navigation(_('Nutzerdaten'));
            $navigation->addSubNavigation('profile', new Navigation(_('Grunddaten'), 'edit_about.php', array('view' => 'Daten')));
            $navigation->addSubNavigation('private', new Navigation(_('Weitere Daten'), 'edit_about.php', array('view' => 'Lebenslauf')));

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
            $navigation = new Navigation(_('Kategorien'), 'edit_about.php', array('view' => 'Sonstiges'));
            $this->addSubNavigation('sections', $navigation);
        }
    }
}
