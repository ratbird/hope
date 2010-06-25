<?php
/*
 * CommunityNavigation.php - navigation for community page
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

class CommunityNavigation extends Navigation
{
    public function __construct()
    {
        global $my_messaging_settings;

        parent::__construct(_('Community'), 'online.php');

        $onlineimage = 'header/header_nutzer';
        $onlinetip = _('Nur Sie sind online');
        $active_time = $my_messaging_settings['active_time'];
        $user_count = get_users_online_count($active_time ? $active_time : 5);

        if ($user_count) {
            $onlineimage = 'header/header_nutzeronline';

            if ($user_count == 1) {
                $onlinetip = _('Außer Ihnen ist eine Person online');
            } else {
                $onlinetip = sprintf(_('Es sind außer Ihnen %d Personen online'), $user_count);
            }
        }

        $this->setImage($onlineimage, array('title' => $onlinetip));
    }

    /**
     * Initialize the subnavigation of this item. This method
     * is called once before the first item is added or removed.
     */
    public function initSubNavigation()
    {
        global $perm;

        parent::initSubNavigation();

        // online list
        $this->addSubNavigation('who', new Navigation(_('Wer ist online?'), 'online.php'));

        // address book
        $navigation = new Navigation(_('Kontakte'));
        $navigation->addSubNavigation('alpha', new Navigation(_('Meine Kontakte'), 'contact.php', array('view' => 'alpha')));
        $navigation->addSubNavigation('gruppen', new Navigation(_('Meine Gruppen'), 'contact.php', array('view' => 'gruppen')));
        $navigation->addSubNavigation('admin_groups', new Navigation(_('Gruppenverwaltung'), 'contact_statusgruppen.php'));
        $navigation->addSubNavigation('export', new Navigation(_('vCard-Export'), 'contact_export.php'));
        $this->addSubNavigation('address_book', $navigation);

        // chat
        if (get_config('CHAT_ENABLE')) {
            $navigation = new Navigation(_('Chat'), 'chat_online.php');
            $this->addSubNavigation('chat', $navigation);
        }

        // study groups
        if (get_config('STUDYGROUPS_ENABLE')) {
            $navigation = new Navigation(_('Studiengruppen'));
            $navigation->addSubNavigation('all', new Navigation(_('Alle Studiengruppen'), 'dispatch.php/studygroup/browse/1/founded_asc'));
            $navigation->addSubNavigation('new', new Navigation(_('Neue Studiengruppe anlegen'), 'dispatch.php/course/studygroup/new'));
            $this->addSubNavigation('studygroups', $navigation);
        }

        // stud.ip highscore
        $this->addSubNavigation('score', new Navigation(_('Rangliste'), 'score.php'));
    }
}