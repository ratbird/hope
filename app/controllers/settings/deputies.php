<?php
/**
 * Settings_DeputiesController - Administration of all user deputy
 * related settings
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

class Settings_DeputiesController extends Settings_SettingsController
{
    /**
     * Set up this controller.
     *
     * @param String $action Name of the action to be invoked
     * @param Array  $args   Arguments to be passed to the action method
     */
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        require_once 'lib/deputies_functions.inc.php';

        PageLayout::setHelpKeyword('Basis.MyStudIPDeputies');
        PageLayout::setTitle(_('Standardvertretung'));
        Navigation::activateItem('/links/settings/deputies');
        PageLayout::setTabNavigation('/links/settings');
        SkipLinks::addIndex(_('Standardvertretung'), 'main_content', 100);

        $this->edit_about_enabled = get_config('DEPUTIES_EDIT_ABOUT_ENABLE');
    }

    /**
     * Displays the deputy information of a user.
     */
    public function index_action()
    {
        if (Request::submitted('add_deputy') && $deputy_id = Request::option('deputy_id')) {
            $this->check_ticket();

            if (isDeputy($deputy_id, $this->user->user_id)) {
                $this->reportError(_('%s ist bereits als Vertretung eingetragen.'), get_fullname($deputy_id, 'full'));
            } else if ($deputy_id == $this->user->user_id) {
                $this->reportError(_('Sie k�nnen sich nicht als Ihre eigene Vertretung eintragen!'));
            } else if (addDeputy($deputy_id, $this->user->user_id)) {
                $this->reportSuccess(_('%s wurde als Vertretung eingetragen.'), get_fullname($deputy_id, 'full'));
            } else {
                $this->reportError(_('Fehler beim Eintragen der Vertretung!'));
            }
            $this->redirect('settings/deputies');
            return;
        }

        $deputies = getDeputies($this->user->user_id, true);

        $exclude_users = array($this->user->user_id);
        if (is_array($deputies)) {
            $exclude_users = array_merge($exclude_users, array_map(function ($d) {
                return $d['user_id'];
            }, $deputies));
        }

        $this->deputies = $deputies;

        $this->search = new PermissionSearch('user', _('Vor-, Nach- oder Benutzername'),
            'user_id', array('permission'   => getValidDeputyPerms(),
                             'exclude_user' => $exclude_users
            ));

        $sidebar = Sidebar::Get();
        $sidebar->setTitle(PageLayout::getTitle());
        $actions = new ActionsWidget();
        // add "add dozent" to infobox
        $mp = MultiPersonSearch::get('settings_add_deputy')
            ->setLinkText(_('Neue Standardvertretung festlegen'))
            ->setDefaultSelectedUser(array_keys($this->deputies))
            ->setLinkIconPath('')
            ->setTitle(_('Neue Standardvertretung festlegen'))
            ->setExecuteURL(URLHelper::getLink('dispatch.php/settings/deputies/add_member'))
            ->setSearchObject($this->search)
            ->setNavigationItem('/links/settings/deputies')
            ->render();
        $element = LinkElement::fromHTML($mp, 'icons/16/blue/add/community.png');
        $actions->addElement($element);
        Sidebar::Get()->addWidget($actions);
    }


    public function add_member_action()
    {
        CSRFProtection::verifyRequest();

        $mp = MultiPersonSearch::load('settings_add_deputy');
        $msg = array();
        foreach ($mp->getAddedUsers() as $_user_id) {
            if (isDeputy($_user_id, $this->user->user_id)) {
                $msg['error'][] = sprintf(_('%s ist bereits als Vertretung eingetragen.'), get_fullname($_user_id, 'full'));
            } else if ($_user_id == $this->user->user_id) {
                $msg['error'][] = _('Sie k�nnen sich nicht als Ihre eigene Vertretung eintragen!');
            } else if (addDeputy($_user_id, $this->user->user_id)) {
                $msg['success'][] = sprintf(_('%s wurde als Vertretung eingetragen.'), get_fullname($_user_id, 'full'));
            } else {
                $msg['error'][] = _('Fehler beim Eintragen der Vertretung!');
            }
        }
        // only show an error messagebox once.
        if (!empty($msg['error'])) {
            PageLayout::postMessage(MessageBox::error(_('Die gew�nschte Operation konnte nicht ausgef�hrt werden.'), $msg['error']));
        } else {
            PageLayout::postMessage(MessageBox::success(_('Die gew�nschten Personen wurden als Ihre Vertretung eingetragen!'), $msg['success']));
        }

        $this->redirect('settings/deputies/index');
    }

    /**
     * Stores the deputy settings of a user.
     */
    public function store_action()
    {
        $this->check_ticket();

        $delete = Request::optionArray('delete');
        if (count($delete) > 0) {
            $deleted = deleteDeputy($delete, $this->user->user_id);
            if ($deleted) {
                $this->reportSuccess($deleted == 1
                    ? _('Die Vertretung wurde entfernt.')
                    : _('Es wurden %s Vertretungen entfernt.'),
                    $deleted);
            } else {
                $this->reportError(_('Fehler beim Entfernen der Vertretung(en).'));
            }
        }

        if ($this->edit_about_enabled) {
            $deputies = getDeputies($this->user->user_id, true);
            $changes = Request::intArray('edit_about');

            $success = true;
            $changed = 0;
            foreach ($changes as $id => $state) {
                if (!in_array($id, $deleted) && $state != $deputies[$id]['edit_about']) {
                    $success = $success and (setDeputyHomepageRights($id, $this->user->user_id, $state) > 0);
                    $changed += 1;
                }
            }

            if ($success && $changed > 0) {
                $this->reportSuccess(_('Die Einstellungen wurden gespeichert.'));
            } else if ($changed > 0) {
                $this->reportError(_('Fehler beim Speichern der Einstellungen.'));
            }
        }

        $this->redirect('settings/deputies');
    }
}
