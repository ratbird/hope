<?php
/*
 * OnlineNavigation.php - navigation for online page
 *
 * Copyright (c) 2009  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

class OnlineNavigation extends Navigation
{
    /**
     * Initialize a new Navigation instance.
     */
    public function __construct()
    {
        global $my_messaging_settings;

        parent::__construct(_('Online'), 'online.php');

        $onlineimage = 'header_nutzer';
        $onlinetip = _('Nur Sie sind online');
        $active_time = $my_messaging_settings['active_time'];
        $user_count = get_users_online_count($active_time ? $active_time : 5);

        if ($user_count) {
            $onlineimage = 'header_nutzeronline';

            if ($user_count == 1) {
                $onlinetip = _('Außer Ihnen ist eine Person online');
            } else {
                $onlinetip = sprintf(_('Es sind außer Ihnen %d Personen online'), $user_count);
            }
        }

        $this->setImage($onlineimage, array('title' => $onlinetip));
    }
}
