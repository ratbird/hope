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

        parent::__construct(_('Profil'));
    }
    
    public function initItem()
    {
        global $user;
        parent::initItem();
        $db = DBManager::get();

        $time = $user->cfg->PROFILE_LAST_VISIT ? $user->cfg->PROFILE_LAST_VISIT : $user->cfg->LAST_LOGIN_TIMESTAMP;

        $hp_txt = _('Zu Ihrer Profilseite');
        $hp_link = 'dispatch.php/profile';
        
        $hp_txt .= sprintf(' (%s, %s)', $user->username, $user->perms);
        $this->setURL($hp_link);
        $this->setImage('icons/28/lightblue/person.png', array('title' => $hp_txt, 'class' => $hp_class, "@2x" => TRUE));
    }

    /**
     * Determine whether this navigation item is active.
     */
    public function isActive()
    {
        $active = parent::isActive();

        if ($active) {
            URLHelper::addLinkParam('username', Request::username('username'));
        }

        return $active;
    }

    /**
     * Initialize the subnavigation of this item. This method
     * is called once before the first item is added or removed.
     */
    public function initSubNavigation()
    {
        global $user, $perm;

        parent::initSubNavigation();

        $username = Request::username('username', $user->username);
        $current_user = $username == $user->username ? $user : User::findByUsername($username);

        // profile
        $navigation = new Navigation(_('Profil'), 'dispatch.php/profile/index');
        $this->addSubNavigation('index', $navigation);

        if ($perm->have_profile_perm('user', $current_user->user_id)) {
            // avatar
            $navigation = new Navigation(_('Bild'), 'dispatch.php/settings/avatar');
            $this->addSubNavigation('avatar', $navigation);

            // profile data
            $navigation = new Navigation(_('Nutzerdaten'));
            $navigation->addSubNavigation('profile', new Navigation(_('Grunddaten'), 'dispatch.php/settings/account'));
            if (($perm->get_profile_perm($current_user->user_id) == 'user'
                || ($perm->have_perm('root') && Config::get()->ALLOW_ADMIN_USERACCESS))
                && !StudipAuthAbstract::CheckField('auth_user_md5.password', $current_user->auth_plugin)
                && !LockRules::check($current_user->user_id, 'password')) {
                $navigation->addSubNavigation('password', new Navigation(_('Passwort ändern'), 'dispatch.php/settings/password'));
            }
            $navigation->addSubNavigation('details', new Navigation(_('Weitere Daten'), 'dispatch.php/settings/details'));

            if (!in_array($current_user->perms, words('user admin root'))) {
                $navigation->addSubNavigation('studies', new Navigation(_('Studiendaten'), 'dispatch.php/settings/studies'));
            }

            if ($current_user->perms != 'root') {
                if (count(UserDomain::getUserDomains())) {
                    $navigation->addSubNavigation('userdomains', new Navigation(_('Nutzerdomänen'), 'dispatch.php/settings/userdomains'));
                }

                if ($perm->is_staff_member($current_user->user_id)) {
                    $navigation->addSubNavigation('statusgruppen', new Navigation(_('Einrichtungsdaten'), 'dispatch.php/settings/statusgruppen'));
                }
            }

            $this->addSubNavigation('edit', $navigation);

            // user defined sections
            $navigation = new Navigation(_('Kategorien'), 'dispatch.php/settings/categories');
            $this->addSubNavigation('categories', $navigation);

            // user documents page
            if (Config::get()->PERSONALDOCUMENT_ENABLE) {
                $navigation = new Navigation(_('Meine Dateien'), 'dispatch.php/document/files');
                $this->addSubNavigation('files', $navigation);
            }
        }
    }
}
