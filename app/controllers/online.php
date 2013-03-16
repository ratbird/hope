<?php
/**
 * online.php - Anzeigemodul fuer Personen die Online sind
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author   André Noack <andre.noack@gmx.net>
 * @author   Cornelis Kater <ckater@gwdg.de>
 * @author   Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @author   Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license  http://www.gnu.org/licenses/gpl.html GPL Licence 2
 */

require_once 'app/controllers/authenticated_controller.php';

require_once 'lib/functions.php';
require_once 'lib/msg.inc.php';
require_once 'lib/visual.inc.php';
require_once 'lib/messaging.inc.php';
require_once 'lib/contact.inc.php';
require_once 'lib/user_visible.inc.php';
require_once 'lib/classes/Avatar.class.php';
require_once 'lib/classes/StudipKing.class.php';

class OnlineController extends AuthenticatedController
{
    const PER_PAGE        = 25;
    const ONLINE_DURATION = 10;
    
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        PageLayout::setHelpKeyword('Basis.InteraktionWhosOnline');
        PageLayout::setTitle(_('Wer ist online?'));
        Navigation::activateItem('/community/online_new');
        SkipLinks::addIndex(_('Wer ist Online?'), 'layout_content', 100);

        $this->settings = $GLOBALS['user']->cfg->MESSAGING_SETTINGS;

        $this->buddy_count = GetNumberOfBuddies();
        if ($this->buddy_count > 0) {
            $template = $this->get_template_factory()->open('online/buddy_config');
            $template->show_only_buddys = $this->settings['show_only_buddys'];
            $template->controller       = $this;
            $this->addToInfobox(_('Einstellung:'), $template->render());
        }

        $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));

        $this->setInfoboxImage('infobox/online.jpg');
        $this->addToInfobox(_('Information:'),
                            _('Hier können Sie sehen, wer au&szlig;er Ihnen im Moment online ist.'),
                            'icons/16/black/info.png');
        $this->addToInfobox(_('Information:'),
                            _('Sie können diesen Benutzern eine Nachricht schicken oder sie zum Chatten einladen.'),
                            'icons/16/black/mail.png');
        $this->addToInfobox(_('Information:'),
                            _('Wenn Sie auf den Namen klicken, kommen Sie zur Homepage des Benutzers.'),
                            'icons/16/black/person.png');
    }

    public function index_action($page = 1)
    {
        $online_users = get_users_online(self::ONLINE_DURATION, $GLOBALS['user']->cfg->ONLINE_NAME_FORMAT);
        $total        = count($online_users);

        /*
         * Start to filter
         */

        //Only use visible users
        $visible_users = array();
        $my_domains    = UserDomain::getUserDomainsForUser($GLOBALS['user']->id);

        foreach ($online_users as $key => $value) {
            $value['username'] = $key;

            $global_visibility = get_global_visibility_by_id($value['userid']);
            $domains           = UserDomain::getUserDomainsForUser($value['userid']);
            if (count($domains) && $global_visibility == 'yes') {
                if (array_intersect($my_domains, $domains) && $value['is_visible']) {
                    $visible_users[$key] = $value;
                }
            } else if ($value['is_visible']) {
                $visible_users[$key] = $value;
            }
        }


        //now seperate the buddies from the others
        $filtered_buddies = array();
        $others           = array();

        foreach ($visible_users as $key => $value) {
            if ($value['is_buddy']) {
                $filtered_buddies[$key] = $value;
            } else {
                $others[$key] = $value;
            }
        }

        $user_count = count($others);
        $weitere = $alle - count($filtered_buddies) - $user_count;

        if ($page < 1) {
            $page = 1;
        } else if ($page > ceil($user_count / self::PER_PAGE)) {
            $page = ceil($user_count / self::PER_PAGE);
        }

        //Slice the array to limit data
        $other_users = array_slice($others, ($page - 1) * self::PER_PAGE, self::PER_PAGE);
    }

    public function config_action()
    {
        if (Request::submitted('change_show_only_buddys')) {
            CSRFProtection::verifyUnsafeRequest();
            $this->settings['show_only_buddys'] = Request::int('show_only_buddys', 0);
            $GLOBALS['user']->cfg->store('MESSAGING_SETTINGS', $this->settings);

            PageLayout::postMessage(MessageBox::success(_('Ihre Einstellungen wurden gespeichert.')));
        }
        $this->redirect('online');
    }

    public function add_budy_action($username)
    {
        $messaging = new messaging;
        $messaging->add_buddy($username);

        PageLayout::postMessage(MessageBox::success(_('Der Benutzer wurde zu Ihren Buddies hinzugefügt.')));
        $this->redirect('online');
    }

    public function remove_buddy_action($username)
    {
        $messaging = new messaging;
        $msging->delete_buddy($username);

        PageLayout::postMessage(MessageBox::success(_('Der Benutzer gehört nicht mehr zu Ihren Buddies.')));
        $this->redirect('online');
    }
}
