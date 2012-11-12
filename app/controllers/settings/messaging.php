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

class Settings_MessagingController extends Settings_SettingsController
{
    public function before_filter(&$action, &$args)
    {
        if ($action === 'verify') {
            $action = 'index';
        }

        parent::before_filter($action, $args);

        PageLayout::setHelpKeyword('Basis.MyStudIPMessaging');
        PageLayout::setTitle(_('Einstellungen des Nachrichtensystems anpassen'));
        PageLayout::setTabNavigation('/links/settings');
        Navigation::activateItem('/links/settings/messaging');
        SkipLinks::addIndex(_('Einstellungen des Nachrichtensystems anpassen'), 'layout_content', 100);

        $this->settings = $this->config->MESSAGING_SETTINGS;
    }

    public function index_action($verify_action = null)
    {
        if (Request::submitted('store')) {
            $this->check_ticket();

            if (Request::get('new_smsforward_rec')) {
                $this->user->smsforward_rec  = get_userid(Request::get('new_smsforward_rec'));
                $this->user->smsforward_copy = 1;
            } else if (Request::int('smsforward_copy') && !$this->user->smsforward_copy) {
                $this->user->smsforward_copy = 1;
            } else if (!Request::int('smsforward_copy') && $this->user->smsforward_copy) {
                $this->user->smsforward_copy = 0;
            }

            $this->user->email_forward = Request::int('send_as_email');
            $this->user->store();

            // write to user config table
            $this->config->store('ONLINE_NAME_FORMAT', Request::option('online_format'));
            $this->config->store('MAIL_AS_HTML', Request::int('mail_format'));

            $settings = $this->settings;

            $settings['delete_messages_after_logout'] = Request::int('delete_messages_after_logout');
            $settings['sms_sig']              = Request::get('sms_sig');
            $settings['timefilter']           = Request::option('timefilter');
            $settings['openall']              = Request::int('openall');
            $settings['opennew']              = Request::int('opennew', 2);
            $settings['logout_markreaded']    = Request::int('logout_markreaded');
            $settings['addsignature']         = Request::int('addsignature');
            $settings['confirm_reading']      = Request::int('confirm_reading');
            $settings['save_snd']             = Request::int('save_snd', 2);
            $settings['request_mail_forward'] = Request::int('request_mail_forward', 0);

            $this->config->store('MESSAGING_SETTINGS', $settings);

            $_SESSION['sms_data']['sig']  = $settings['addsignature'];
            $_SESSION['sms_data']['time'] = $settings['timefilter'];

            $this->reportSuccess(_('Ihre Einstellungen wurden erfolgreich gespeichert.'));
            $this->redirect('settings/messaging');
        }

        if (!$this->user->smsforward_rec && Request::submitted('gosearch')) {
            $vis_query = get_vis_query('auth_user_md5');
            $query = "SELECT user_id, username, {$GLOBALS['_fullname_sql']['full_rev']} AS fullname, perms
                      FROM auth_user_md5
                      LEFT JOIN user_info USING (user_id)
                      WHERE (username LIKE CONCAT('%', :needle, '%') OR
                             Vorname LIKE CONCAT('%', :needle, '%') OR
                             Nachname LIKE CONCAT('%', :needle, '%'))
                        AND user_id != :user_id AND {$vis_query}
                      ORDER BY Nachname ASC";
            $statement = DBManager::get()->prepare($query);
            $statement->bindValue(':needle', Request::get('search_exp'));
            $statement->bindValue(':user_id', $this->user->user_id);
            $statement->execute();
            $matches = $statement->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $matches = false;
        }

        $this->matches       = $matches;
        $this->verify_action = $verify_action;
    }

    public function reset_action($action = 'reset', $verified = false)
    {
        if ($verified) {
            $this->check_ticket();

            if ($action === 'reset') {
                $this->user->smsforward_rec  = '';
                $this->user->smsforward_copy = 0;
                $this->user->store();

                $query = "UPDATE message_user SET folder = 0 WHERE user_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($this->user->user_id));

                $this->config->delete('MESSAGING_SETTINGS');

                unset($_SESSION['sms_data']['sig']);
                unset($_SESSION['sms_data']['time']);

                $this->reportSuccess(_('Ihre Einstellungen wurden erfolgreich zurückgesetzt.'));
            } else if ($action === 'forward_receiver') {
                $this->user->smsforward_rec  = '';
                $this->user->smsforward_copy = 0;
                $this->user->store();

                $this->reportSuccess(_('Empfänger und Weiterleitung wurden erfolgreich gelöscht'));
            }
        }
        $this->redirect('settings/messaging');
    }
}
