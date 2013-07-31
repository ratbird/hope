<?php
/**
 * Settings_AccountController - Administration of all user account related
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

class Settings_AccountController extends Settings_SettingsController
{
    /**
     * Set up this controller and define the infobox
     *
     * @param String $action Name of the action to be invoked
     * @param Array  $args   Arguments to be passed to the action method
     */
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        PageLayout::setHelpKeyword('Basis.HomepagePers�nlicheDaten');
        PageLayout::setTitle(_('Benutzerkonto bearbeiten'));
        Navigation::activateItem('/profile/edit/profile');
        SkipLinks::addIndex(_('Benutzerkonto bearbeiten'), 'layout_content');

        $infobox_message = _('Hier k&ouml;nnen Sie Ihre Benutzerdaten ver&auml;ndern.') . '<br>'
                         . sprintf(_('Alle mit einem Sternchen %s markierten Felder m&uuml;ssen ausgef&uuml;llt werden.'),
                                   '<span style="color: red; font-size: 1.5em; font-weight: bold;">*</span>');
        $this->setInfoBoxImage('infobox/groups.jpg');
        $this->addToInfobox(_('Informationen'), $infobox_message, 'icons/16/black/info.png');
    }

    /**
     * Display the account information of a user
     */
    public function index_action()
    {
        $this->locked_info = LockRules::CheckLockRulePermission($this->user['user_id'])
                           ? LockRules::getObjectRule($this->user['user_id'])->description
                           : false;

        $auth = StudipAuthAbstract::GetInstance($this->user->auth_plugin ?: 'standard');
        $this->is_sso = $auth instanceOf StudipAuthSSO;
    }

    /**
     * Stores the account informations of a user
     */
    public function store_action()
    {
        $this->check_ticket();

        $errors = $info = $success = array();
        $logout = false;

        //erstmal die "unwichtigen" Daten
        $geschlecht = Request::int('geschlecht');
        if ($this->shallChange('user_info.geschlecht', 'gender', $geschlecht)) {
            $this->user->geschlecht = $geschlecht;
        }

        $title_front = Request::get('title_front') ?: Request::get('title_front_chooser');
        if ($this->shallChange('user_info.title_front', 'title', $title_front)) {
            $this->user->title_front = $title_front;
        }

        $title_rear = Request::get('title_rear') ?: Request::get('title_rear_chooser');
        if ($this->shallChange('user_info.title_rear', 'title', $title_rear)) {
            $this->user->title_rear = $title_rear;
        }

        if ($this->user->store()) {
            $success[] = _('Ihre pers�nlichen Daten wurden ge�ndert.');

            // Inform the user about this change
            setTempLanguage($this->user->user_id);
            $this->postPrivateMessage(_("Ihre pers�nlichen Daten wurden ge�ndert.\n"));
            restoreLanguage();
        }

        //nur n�tig wenn der user selbst seine daten �ndert
        if (!$this->restricted) {
            // Vorname ver�ndert ?
            $vorname = trim(Request::get('vorname'));
            if ($this->shallChange('auth_user_md5.Vorname', 'name', $vorname)) {
                // Vorname nicht korrekt oder fehlend
                if (!$this->validator->ValidateName($vorname)) {
                    $errors[] = _('Der Vorname fehlt oder ist unsinnig!');
                } else {
                    $this->user->Vorname = $vorname;
                    $success[] = _('Ihr Vorname wurde ge&auml;ndert!');
                }
            }

            // Nachname ver�ndert ?
            $nachname = trim(Request::get('nachname'));
            if ($this->shallChange('auth_user_md5.Nachname', 'name', $nachname)) {
                // Nachname nicht korrekt oder fehlend
                if (!$this->validator->ValidateName($nachname)) {
                    $errors[] = _('Der Nachname fehlt oder ist unsinnig!');
                } else {
                    $this->user->Nachname = $nachname;
                    $success[] = _('Ihr Nachname wurde ge&auml;ndert!');
                }
            }

            // Username
            $new_username = trim(Request::get('new_username'));
            if ($this->shallChange('auth_user_md5.username', 'username', $new_username)) {
                if (!$this->validator->ValidateUsername($new_username)) {
                    $errors[] = _('Der gew�hlte Benutzername ist nicht lang genug!');
                } else if ($check_uname = StudipAuthAbstract::CheckUsername($new_username) && $check_uname['found']) {
                    $errors[] =  _('Der Benutzername wird bereits von einem anderen Benutzer verwendet. Bitte w�hlen Sie einen anderen Usernamen!');
                } else {
                    $this->user->username = $new_username;
                    $success[] = _('Ihr Benutzername wurde ge&auml;ndert!');

                    $logout = true;
                }
            }

            // Email
            $email1 = trim(Request::get('email1'));
            $email2 = trim(Request::get('email2'));
            if ($this->shallChange('auth_user_md5.Email', 'email', $email1)) {
                $auth   = StudipAuthAbstract::GetInstance($this->user->auth_plugin ?: 'standard');
                $is_sso = $auth instanceOf StudipAuthSSO;

                if (!$is_sso && $auth->isAuthenticated($this->user->username, Request::get('password'))) {
                    $errors[] = _('Das aktuelle Passwort wurde nicht korrekt eingegeben.');
                } else if ($email1 !== $email2) {
                    $errors[] = _('Die Wiederholung der E-Mail-Adresse stimmt nicht mit Ihrer Eingabe �berein.');
                } else {
                    $result   = edit_email($this->user, $email1);
                    $messages = explode('�', $result[1]);

                    if ($result[0]) {
                        $this->user->Email = $email1;

                        if (count($messages) < 2) {
                            $success[] = _('Ihre E-Mail-Adresse wurde ge&auml;ndert!');
                        }
                    }

                    for ($i = 0; $i < count($messages); $i += 2) {
                        $type = $messages[$i];
                        if ($type === 'msg') {
                            $type = 'success';
                        } else if ($type === 'error') {
                            $type = 'errors';
                        }
                        ${$type}[] = $messages[$i + 1];
                    }
                }
            }
        }

        if (count($errors) > 0) {
            $this->reportErrorWithDetails(_('Bitte �berpr�fen Sie Ihre Eingaben:'), $errors);
        } else if ($this->user->store()) {
            $this->reportSuccessWithDetails(_('Ihre Nutzerdaten wurden ge�ndert.'), $success);
            if (count($info) > 0) {
                $this->reportInfoWithDetails(_('Bitte beachten Sie:'), $info);
            }
        }

        if ($logout) {
            $this->redirect('settings/account/logout');
        } else {
            $this->redirect('settings/account');
        }
    }

    /**
     * Display an information page that the user has been logged out
     */
    public function logout_action()
    {
        $this->username = Request::username('username', $GLOBALS['user']->username);
    }
}
