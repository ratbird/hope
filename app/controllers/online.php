<?php
/**
 * OnlineController
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       2.5
 */

require_once 'app/controllers/authenticated_controller.php';
require_once 'lib/contact.inc.php';

class OnlineController extends AuthenticatedController
{
    /**
     * Sets up the controller
     *
     * @param String $action Which action shall be invoked
     * @param Array $args Arguments passed to the action method
     */
    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        PageLayout::setHelpKeyword('Basis.InteraktionWhosOnline');
        PageLayout::setTitle(_('Wer ist online?'));
        Navigation::activateItem('/community/online');
        SkipLinks::addIndex(_('Wer ist online?'), 'layout_content', 100);

        $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));

        $this->buddy_count = GetNumberOfBuddies();
        $this->settings    = $GLOBALS['user']->cfg->MESSAGING_SETTINGS;

        // If "show_groups" setting is not set, default it to whether the
        // user has organized his buddies in groups
        if (!isset($this->settings['show_groups'])) {
            $query = "SELECT 1 FROM statusgruppen WHERE range_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($GLOBALS['user']->id));
            $has_contact_groups = $statement->fetchColumn();

            $this->settings['show_groups'] = $has_contact_groups;
        }

    }

    /**
     * Displays the online list.
     **/
    public function index_action()
    {
        $this->contact_count = GetSizeOfBook(); // Total number of contacts

        $this->users           = $this->getOnlineUsers($this->settings['show_groups']);
        $this->showOnlyBuddies = $this->settings['show_only_buddys'];
        $this->showGroups      = $this->settings['show_groups'];

        $this->limit = Config::getInstance()->ENTRIES_PER_PAGE;
        $max_page    = ceil(count($this->users['users']) / $this->limit);
        $this->page  = min(Request::int('page', 1), $max_page);

        // Infobox
        $this->setInfoboxImage('infobox/online.jpg');
        $this->addToInfobox(_('Information:'),
                            _('Hier k�nnen Sie sehen, wer au�er Ihnen im Moment online ist.'),
                            'icons/16/black/info.png');
        $this->addToInfobox(_('Information:'),
                            _('Sie k�nnen diesen NutzerInnen eine Nachricht schicken.'),
                            'icons/16/black/mail.png');
        $this->addToInfobox(_('Information:'),
                            _('Wenn Sie auf den Namen klicken, kommen Sie zur Homepage des Benutzers.'),
                            'icons/16/black/person.png');

        // Add buddy configuration option to infobox only if the user actually
        // has buddies
        if ($this->buddy_count > 0) {
            $template = $this->get_template_factory()->open('online/config');
            $template->show_only_buddys = $this->settings['show_only_buddys'];
            $template->show_groups      = $this->settings['show_groups'];
            $template->controller       = $this;
            $this->addToInfobox(_('Einstellung:'),
                                $template->render(),
                                'icons/16/black/admin.png');
        }
    }

    /**
     * Controller for all buddy related action.
     *
     * The following actions are supported:
     * - "add" to add a user to the current user's buddy list
     * - "remove" to remove a user from the current user's buddy list
     *
     * @param String $action The action to be executed
     */
    public function buddy_action($action = 'add')
    {
        $username = Request::username('username');

        $messaging = new messaging;
        if ($action === 'add' && $username !== null) {
            $messaging->add_buddy($username);
            PageLayout::postMessage(MessageBox::success(_('Der Benutzer wurde zu Ihren Buddies hinzugef�gt.')));
        } elseif ($action === 'remove' && $username !== null) {
            $messaging->delete_buddy($username);
            PageLayout::postMessage(MessageBox::success(_('Der Benutzer geh�rt nicht mehr zu Ihren Buddies.')));
        }
        $this->redirect('online');
    }

    /**
     * Changes a specific setting by toggling it's state.
     *
     * @param String $settings The settings to be changed
     * @param String $ticket   Neccessary studip ticket to execute the change
     */
    public function config_action($setting, $ticket)
    {
        if (!in_array($setting, words('show_buddies show_groups')) || !check_ticket($ticket)) {
            $message = MessageBox::error(_('Es ist ein Fehler aufgetreten. Bitte versuchen Sie die Aktion erneut.'));
        } else {
            if ($setting === 'show_buddies') {
                $this->settings['show_only_buddys'] = (int)!$this->settings['show_only_buddys'];
            } else {
                $this->settings['show_groups'] = (int)!$this->settings['show_groups'];
            }
            $GLOBALS['user']->cfg->store('MESSAGING_SETTINGS', $this->settings);
            
            $message = MessageBox::success(_('Ihre Einstellungen wurden gespeichert.'));
        }
        
        PageLayout::postMessage($message);
        $this->redirect('online');
    }

    /**
     * Creates a list of online users - optionally including the according
     * contact groups.
     * The created list is an array with four elemens:
     * - "total" is the _number_ of all currently online users.
     * - "buddies" is an _array_ containing the data of all the user's buddies
     *   that are currently online.
     * - "users" is an _array_ containing the data of all users that are
     *   currently online and are not a buddy of the current user and are
     *   either globally visible or visible in the current user's domains.
     * - "others" is the number of all other and accordingly invisible users.
     *
     * @param bool $show_buddy_groups Defines whether the list of buddies
     *                                should include the according contact
     *                                groups or not
     * @return Array List of online users as an array (see above)
     */
    private function getOnlineUsers($show_buddy_groups = false)
    {
        $temp  = get_users_online(10, $GLOBALS['user']->cfg->ONLINE_NAME_FORMAT);
        $total = count($temp);

        // Filter invisible users
        $visible    = array();
        $my_domains = UserDomain::getUserDomainsForUser($GLOBALS['user']->id);

        foreach ($temp as $username => $user) {
            if ($user['is_visible']) {
                continue;
            }
            $global_visibility = get_global_visibility_by_id($user['user_id']);
            $domains           = UserDomain::getUserDomainsForUser($user['user_id']);
            $same_domains      = array_intersect($domains, $my_domains);

            if ($global_visibility !== 'yes' || !count($same_domains)) {
                unset($temp[$username]);
            }
        }

        // Split list into buddies and other users
        $buddies = array_filter($temp, function ($user) { return $user['is_buddy']; });
        $users   = array_filter($temp, function ($user) { return !$user['is_buddy']; });

        if ($show_buddy_groups) {
            // Add groups to buddies
            $buddy_ids = array_map(function ($user) { return $user['user_id']; }, $buddies);

            $name_format = $GLOBALS['user']->cfg->ONLINE_NAME_FORMAT;
            if (!isset($GLOBALS['_fullname_sql'][$name_format])) {
                $name_format = reset(array_keys($GLOBALS['_fullname_sql']));
            }

            $query = "SELECT user_id, statusgruppen.position, name, statusgruppen.statusgruppe_id
                      FROM statusgruppen
                        JOIN statusgruppe_user USING (statusgruppe_id)
                        JOIN auth_user_md5 USING (user_id)
                      WHERE range_id = :user_id AND user_id IN (:buddy_ids)
                      ORDER BY statusgruppen.position ASC";
            $statement = DBManager::get()->prepare($query);
            $statement->bindValue(':user_id', $GLOBALS['user']->id);
            $statement->bindValue(':buddy_ids', $buddy_ids ?: array(''), StudipPDO::PARAM_ARRAY);
            $statement->execute();
            $grouped = $statement->fetchGrouped();

            foreach ($buddies as $username => $buddy) {
                if (isset($grouped[$buddy['user_id']])) {
                    $group = $grouped[$buddy['user_id']];
                    $buddies[$username]['group']          = $group['name'];
                    $buddies[$username]['group_id']       = $group['statusgruppe_id'];
                    $buddies[$username]['group_position'] = $group['position'];
                } else {
                    $buddies[$username]['group']          = _('Buddies ohne Gruppenzuordnung');
                    $buddies[$username]['group_id']       = 'all';
                    $buddies[$username]['group_position'] = 100000;
                }
            }
            usort($buddies, function ($a, $b) {
                return ($a['group_position'] - $b['group_position']) ?: strcmp($a['name'] - $b['name']);
            });
        }

        $others = $total - count($buddies) - count($users);
        return compact('buddies', 'users', 'total', 'others');
    }
}
