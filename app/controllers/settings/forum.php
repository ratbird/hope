<?php
/*
 * SettingsController - Controller for all setting related pages (formerly edit_about)
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

class Settings_ForumController extends Settings_SettingsController
{
    public function before_filter(&$action, &$args)
    {
        if ($action === 'verify') {
            $action = 'index';
        }

        parent::before_filter($action, $args);

        PageLayout::setHelpKeyword('Basis.MyStudIPForum');
        PageLayout::setTitle(_('Einstellungen des Forums anpassen'));
        PageLayout::setTabNavigation('/links/settings');
        Navigation::activateItem('/links/settings/forum');

        SkipLinks::addIndex(_('Einstellungen des Forums anpassen'), 'layout_content', 100);

        $settings = json_decode($this->config->forum, true) ?: array();

        $this->defaults = array(
            'sortthemes' => 'asc',
            'themeview'  => 'tree',
            'presetview' => 'tree',
        );
        foreach ($this->defaults as $key => $value) {
            if (!isset($settings[$key])) {
                $settings[$key] = $value;
            }
        }

        $this->settings = $settings;
    }

    public function index_action($verify_action = null)
    {
        $this->verify_action = $verify_action;
    }

    public function store_action()
    {
        $this->check_ticket();

        $presetview = Request::option('presetview');
        if ($presetview == 'theme') {
            $presetview = Request::option('themeview');
        }

        $forum = array(
            'neuauf'      => Request::int('neuauf'),
            'rateallopen' => Request::option('rateallopen'),
            'showimages'  => Request::option('showimages'),
            'sortthemes'  => Request::option('sortthemes'),
            'themeview'   => Request::option('themeview'),
            'presetview'  => $presetview,
            'shrink'      => Request::int('shrink') * 7 * 24 * 60 * 60, // = 1 Woche
            'changed'     => 'TRUE',
        );

        $this->config->store('forum', json_encode($forum));
        $this->reportSuccess(_('Ihre Einstellungen wurden gespeichert.'));
        $this->redirect('settings/forum');
    }

    public function reset_action($verified = true)
    {
        if ($verified) {
            $this->check_ticket();

            $this->config->store('forum', json_encode($this->defaults));

            $this->reportSuccess(_('Ihre Einstellungen wurden zurückgesetzt.'));
        }

        $this->redirect('settings/forum');

    }
}
