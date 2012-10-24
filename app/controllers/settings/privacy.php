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

class Settings_PrivacyController extends Settings_SettingsController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        PageLayout::setHelpKeyword('Basis.MyStudIPPrivacy');
        PageLayout::setTitle(_('Privatsphäre'));
        PageLayout::setTabNavigation('/links/settings');

        Navigation::activateItem('/links/settings/privacy');

        SkipLinks::addIndex(_('Privatsphäre'), 'layout_content', 100);
    }
    
    /**
     */
    public function index_action()
    {
        // Get visibility settings from database.
        $this->global_visibility = get_global_visibility_by_id($this->user->user_id);
        $this->online_visibility = get_local_visibility_by_id($this->user->user_id, 'online');
        $this->chat_visibility   = get_local_visibility_by_id($this->user->user_id, 'chat');
        $this->search_visibility = get_local_visibility_by_id($this->user->user_id, 'search');
        $this->email_visibility  = get_local_visibility_by_id($this->user->user_id, 'email');

        // Get default visibility for homepage elements.
        $this->default_homepage_visibility = get_default_homepage_visibility($this->user->user_id);

        // Now get elements of user's homepage.
        $homepage_elements_unsorted = $this->about->get_homepage_elements();

        // Group elements by category.
        $homepage_elements = array();
        foreach ($homepage_elements_unsorted as $key => $element) {
            $homepage_elements[$element['category']][$key] = $element;
        }
        $this->homepage_elements = $homepage_elements;

        $this->NOT_HIDEABLE_FIELDS = $GLOBALS['NOT_HIDEABLE_FIELDS'];
        $this->user_perm           = $GLOBALS['perm']->get_perm($this->user->user_id);
        $this->user_domains        = UserDomain::getUserDomains();
        $this->FOAF_ENABLE         = $GLOBALS['FOAF_ENABLE'];
    }
    
    public function global_action()
    {
        $this->check_ticket();

        $visibility = Request::option('global_visibility');

        // Globally visible or unknown -> set local visibilities accordingly.
        if ($visibility != 'no') {
            $online = Request::int('online') ?: 0;
            $chat   = Request::int('chat') ?: 0;
            $search = Request::int('search') ?: 0;
            $email  = Request::int('email') ?: 0;
            $foaf_show_identity = Request::int('foaf_show_identity') ?: 0;
        // Globally invisible -> set all local fields to invisible.
        } else {
            $online  = $chat = $search = $foaf_show_identity = 0;
            $email   = get_config('DOZENT_ALLOW_HIDE_EMAIL') ? 0 : 1;
            $success = $this->about->change_all_homepage_visibility(VISIBILITY_ME);
        }

        $this->config->store('FOAF_SHOW_IDENTITY', $foaf_show_identity);
        
        $this->user->visible = $visibility;
        $this->user->store();

        $query = "INSERT INTO user_visibility
                    (user_id, online, chat, search, email, mkdate)
                  VALUES (?, ?, ?, ?, ?, UNIX_TIMESTAMP())
                  ON DUPLICATE KEY
                    UPDATE online = VALUES(online), chat = VALUES(chat),
                           search = VALUES(search), email = VALUES(email)";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $this->user->user_id,
            $online, $chat, $search, $email
        ));

        if ($success || ($statement->rowCount() > 0)) {
            $this->reportSuccess(_('Ihre Sichtbarkeitseinstellungen wurden gespeichert.'));
        } else {
            $this->reportError(_('Ihre Sichtbarkeitseinstellungen wurden nicht gespeichert!'));
        }

        $this->redirect('settings/privacy');
    }
    
    public function homepage_action()
    {
        $this->check_ticket();

        $data = array();
        foreach(array_keys($this->about->get_homepage_elements()) as $key) {
            if (Request::int($key) !== null) {
                $data[$key] = Request::int($key);
            }
        }

        if ($this->about->change_homepage_visibility($data)) {
            $this->reportSuccess(_('Ihre Sichtbarkeitseinstellungen wurden gespeichert.'));
        } else {
            $this->reportError(_('Ihre Sichtbarkeitseinstellungen wurden nicht gespeichert!'));
        }

        $this->redirect('settings/privacy');
    }
    
    public function bulk_action()
    {
        $this->check_ticket();

        if (Request::submitted('store_default')) {
            if (!$default_visibility = Request::int('default')) {
                $this->reportError(_('Bitte wählen Sie eine Standardsichtbarkeit für Ihre Profilelemente!'));
            } else if ($this->about->set_default_homepage_visibility($default_visibility)) {
                $this->reportSuccess(_('Die Standardsichtbarkeit der Profilelemente wurde gespeichert.'));
            } else {
                $this->reportError(_('Die Standardsichtbarkeit der Profilelemente wurde nicht gespeichert!'));
            }
        }

        if (Request::submitted('store_all')) {
            if (!$visiblity = Request::int('all')) {
                $this->reportError(_('Bitte wählen Sie eine Sichtbarkeitsstufe für Ihre Profilelemente!'));
            } else if ($this->about->change_all_homepage_visibility($visiblity)) {
                $this->reportSuccess(_('Die Sichtbarkeit der Profilelemente wurde gespeichert.'));
            } else {
                $this->reportError(_('Die Sichtbarkeitseinstellungen der Profilelemente wurden nicht gespeichert!'));
            }
        }
        $this->redirect('settings/privacy');
    }
}
