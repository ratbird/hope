<?php
/*
 * Settings/UserdomainsController
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       2.4
 */

require_once 'settings.php';

class Settings_UserdomainsController extends Settings_SettingsController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        require_once 'lib/classes/UserDomain.php';

        PageLayout::setHelpKeyword('Basis.HomepageNutzerdomänen');
        PageLayout::setTitle(_('Nutzerdomänen bearbeiten'));
        Navigation::activateItem('/profile/edit/userdomains');
        SkipLinks::addIndex(_('Zugeordnete Nutzerdomänen'), 'assigned_userdomains');
        SkipLinks::addIndex(_('Nutzerdomäne auswählen'), 'select_userdomains');
    }

    public function index_action()
    {
        $this->allow_change = !StudipAuthAbstract::CheckField("userdomain_id", $this->user->auth_plugin)
                              && $GLOBALS['perm']->have_perm('admin');

        $infobox_message = _('Hier können Sie die Liste Ihrer Nutzerdomänen einsehen.');
        $this->setInfoBoxImage('infobox/groups.jpg');
        $this->addToInfobox(_('Informationen'), $infobox_message, 'icons/16/black/info.png');
    }

    public function store_action()
    {
        $this->check_ticket();

        $any_change = false;

        $userdomain_delete = Request::optionArray('userdomain_delete');
        if (count($userdomain_delete) > 0) {
            foreach ($userdomain_delete as $id) {
                $domain = new UserDomain($id);
                $domain->removeUser($this->user->user_id);
            }

            $any_change = true;
        }

        $new_userdomain = Request::option('new_userdomain');
        if ($new_userdomain && $new_userdomain != 'none') {
            $domain = new UserDomain($new_userdomain);
            $domain->addUser($this->user->user_id);

            $any_change = true;
        }

        if ($any_change) {
            $this->reportSuccess(_('Die Zuordnung zu Nutzerdomänen wurde ge&auml;ndert.'));

            setTempLanguage($this->user->user_id);
            $this->postPrivateMessage(_("Die Zuordnung zu Nutzerdomänen wurde geändert!\n"));
            restoreLanguage();
        }

        $this->redirect('settings/userdomains');
    }
}
