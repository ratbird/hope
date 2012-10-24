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

class Settings_GeneralController extends Settings_SettingsController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        PageLayout::setTitle(_('Allgemeine Einstellungen anpassen'));
        PageLayout::setTabNavigation('/links/settings');
        Navigation::activateItem('/links/settings/general');
        SkipLinks::addIndex(_('Allgemeine Einstellungen anpassen'), 'layout_content', 100);
    }
    
    /**
     */
    public function index_action()
    {
        $this->user_language = $_SESSION['_language'] ?: $GLOBALS['DEFAULT_LANGUAGE'];
    }
    
    public function store_action()
    {
        $this->check_ticket();

        $language = Request::get('forced_language');
        if (array_key_exists($language, $GLOBALS['INSTALLED_LANGUAGES'])) {
            $this->user->preferred_language = $_SESSION['_language'] = $language;
            $this->user->store();
        }

        $this->config->store('startpage_redirect', Request::int('personal_startpage'));
        $this->config->store('ACCESSKEY_ENABLE', Request::int('accesskey_enable'));
        $this->config->store('SHOWSEM_ENABLE', Request::int('showsem_enable'));
        $this->config->store('SKIPLINKS_ENABLE', Request::int('skiplinks_enable'));

        if (Request::int('personal_notifications_activated')) {
            PersonalNotifications::activate();
        } else {
            PersonalNotifications::deactivate();
        }
        if (Request::int('personal_notifications_audio_activated')) {
            PersonalNotifications::activateAudioFeedback();
        } else {
            PersonalNotifications::deactivateAudioFeedback();
        }

        $this->reportSuccess(_('Die Einstellungen wurden gespeichert.'));
        $this->redirect('settings/general');
    }
}
