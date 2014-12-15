<?php
/**
 * Settings_PrivacyController - Administration of all user privacy related
 * settings
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
    /**
     * Set up this controller.
     *
     * @param String $action Name of the action to be invoked
     * @param Array  $args   Arguments to be passed to the action method
     */
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
     * Displays the privacy settings of a user.
     */
    public function index_action()
    {
        // Get visibility settings from database.
        $this->global_visibility = get_global_visibility_by_id($this->user->user_id);
        $this->online_visibility = get_local_visibility_by_id($this->user->user_id, 'online');
        $this->search_visibility = get_local_visibility_by_id($this->user->user_id, 'search');
        $this->email_visibility  = get_local_visibility_by_id($this->user->user_id, 'email');

        // Get default visibility for homepage elements.
        $this->default_homepage_visibility = Visibility::get_default_homepage_visibility();

        $this->NOT_HIDEABLE_FIELDS = $GLOBALS['NOT_HIDEABLE_FIELDS'];
        $this->user_perm           = $GLOBALS['perm']->get_perm($this->user->user_id);
        $this->user_domains        = UserDomain::getUserDomains();
        
        // Calculate colWidth and colCount for different visibilities
        $this->colCount = Visibility::getColCount();
        $this->colWidth = 67 / $this->colCount;
        $this->visibilities = Visibility::getVisibilities();
        $this->homepage_elements = Visibility::getHTMLArgs(); 
        
    }

    /**
     * Stores the privacy settings concerning the appearance of a user inside
     * the system.
     */
    public function global_action()
    {
        $this->check_ticket();

        $visibility = Request::option('global_visibility');

        // Globally visible or unknown -> set local visibilities accordingly.
        if ($visibility != 'no') {
            $online = Request::int('online') ?: 0;
            $search = Request::int('search') ?: 0;
            $email  = Request::int('email') ?: 0;
            $foaf_show_identity = Request::int('foaf_show_identity') ?: 0;
        // Globally invisible -> set all local fields to invisible.
        } else {
            $online  = $search = $foaf_show_identity = 0;
            $email   = get_config('DOZENT_ALLOW_HIDE_EMAIL') ? 0 : 1;
            $success = $this->about->change_all_homepage_visibility(VISIBILITY_ME);
        }

        $this->config->store('FOAF_SHOW_IDENTITY', $foaf_show_identity);
        
        $this->user->visible = $visibility;
        $this->user->store();

        $query = "INSERT INTO user_visibility
                    (user_id, online, search, email, mkdate)
                  VALUES (?, ?, ?, ?, UNIX_TIMESTAMP())
                  ON DUPLICATE KEY
                    UPDATE online = VALUES(online),
                           search = VALUES(search), email = VALUES(email)";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $this->user->user_id,
            $online, $search, $email
        ));

        $this->reportSuccess(_('Ihre Sichtbarkeitseinstellungen wurden gespeichert.'));
        $this->redirect('settings/privacy');
    }

    /**
     * Stores the privacy settings concerning the homepage / profile of a
     * user.
     */
    public function homepage_action() {
        $this->check_ticket();
        
        // If no bulk action is performed set all visibilitysettings seperately
        if (!$this->bulk()) {
            $data = Request::getArray('visibility_update');
            if (Visibility::updateUserFromRequest($data)) {
                $this->reportSuccess(_('Ihre Sichtbarkeitseinstellungen wurden gespeichert.'));
            } else {
                $this->reportError(_('Ihre Sichtbarkeitseinstellungen wurden nicht gespeichert!'));
            }
        }
        $this->redirect('settings/privacy');
    }
    
    /**
     * Performs bulk actions on the privacy settings of a user. This can be
     * either the setting of new default values or the changing of all privacy
     * values at once.
     * 
     * @return boolean Returns <b>true</b> if all visibilities have been set
     */
    public function bulk()
    {
        if ($default_visibility = Request::int('default')) {
            $this->about->set_default_homepage_visibility(Request::int('default'));
        }

        if ($visiblity = Request::int('all')) {
            if (Visibility::setAllSettingsForUser($visiblity)) {
                $this->reportSuccess(_('Die Sichtbarkeit der Profilelemente wurde gespeichert.'));
                return true;
            } else {
                $this->reportError(_('Die Sichtbarkeitseinstellungen der Profilelemente wurden nicht gespeichert!'));
            }
        }
        return false;
    }
}
