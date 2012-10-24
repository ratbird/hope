<?php
/*
 * SettingsController - Administration of all user profile related
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

/**
 */
class Settings_PasswordController extends Settings_SettingsController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        if (!$this->shallChange('auth_user_md5.password', 'password')) {
            throw new AccessDeniedException(_('Sie haben keinen Zutritt zu diesem Bereich.'));
        }

        PageLayout::setHelpKeyword('Basis.HomepagePersönlicheDaten');
        PageLayout::setTitle(_('Passwort ändern'));
        Navigation::activateItem('/profile/edit/password');
        SkipLinks::addIndex(_('Passwort ändern'), 'layout_content');
    }

    /**
     *
     */
    public function index_action()
    {
    }

    public function store_action()
    {
        $this->check_ticket();

        $errors = array();

        if ($this->user['password'] != md5(Request::get('password'))) {
            $errors[] = _('Das aktuelle Passwort wurde nicht korrekt eingegeben.');
        }

        $password  = Request::get('new_password');
        $confirm   = Request::get('new_password_confirm');

        if (!$this->validator->ValidatePassword($password)) {
            $errors[] = _('Das Passwort ist zu kurz - es sollte mindestens 4 Zeichen lang sein.');
        } else if ($password !== $confirm) {
            $errors[] = _('Die Wiederholung Ihres Passworts stimmt nicht mit Ihrer Eingabe überein.');
        } else if ($password == $this->user['username']) {
            $errors[] = _('Das Passwort darf nicht mit dem Nutzernamen übereinstimmen.');
        } else if (str_replace(array('.', ' '), '', strtolower($password)) == 'studip') {
            $errors[] = _('Aus Sicherheitsgründen darf das Passwort nicht "Stud.IP" oder eine Abwandlung davon sein.');
        }

        if (count($errors) > 0) {
            $this->reportErrorWithDetails(_('Bitte überprüfen Sie Ihre Eingabe:'), $errors);
        } else {
            $this->user->password = md5($password);
            if ($this->user->store()) {
                $this->reportSuccess(_('Das Passwort wurde erfolgreich geändert.'));
            }
        }
        $this->redirect('settings/password');
    }
}
