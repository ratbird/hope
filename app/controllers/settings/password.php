<?php
/**
 * Settings_PasswordController - Administration of all user and password related
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

class Settings_PasswordController extends Settings_SettingsController
{
    /**
     * Set up this controller.
     *
     * @param String $action Name of the action to be invoked
     * @param Array  $args   Arguments to be passed to the action method
     * @throws AccessDeniedException if the current user is not allowed to
     *                               change the password
     */
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        if (!$this->shallChange('auth_user_md5.password', 'password')) {
            throw new AccessDeniedException(_('Sie haben keinen Zutritt zu diesem Bereich.'));
        }

        PageLayout::setHelpKeyword('Basis.HomepagePers�nlicheDaten');
        PageLayout::setTitle(_('Passwort �ndern'));
        Navigation::activateItem('/profile/edit/password');
        SkipLinks::addIndex(_('Passwort �ndern'), 'layout_content');
    }

    /**
     * Displays the password change form.
     */
    public function index_action()
    {
    }

    /**
     * Stores a new password for a user.
     */
    public function store_action()
    {
        $this->check_ticket();

        $errors = array();
        $hasher = UserManagement::getPwdHasher();
        

        $password  = Request::get('new_password');
        $confirm   = Request::get('new_password_confirm');
        if (!($hasher->CheckPassword(md5(Request::get('password')), $this->user['password'] ) || $hasher->CheckPassword(Request::get('password'), $this->user['password'] ))) {
            $errors[] = _('Das aktuelle Passwort wurde nicht korrekt eingegeben.');
        }
        if (!$this->validator->ValidatePassword($password)) {
            $errors[] = _('Das Passwort ist zu kurz - es sollte mindestens 4 Zeichen lang sein.');
        } else if ($password !== $confirm) {
            $errors[] = _('Die Wiederholung Ihres Passworts stimmt nicht mit Ihrer Eingabe �berein.');
        } else if ($password == $this->user['username']) {
            $errors[] = _('Das Passwort darf nicht mit dem Nutzernamen �bereinstimmen.');
        } else if (str_replace(array('.', ' '), '', strtolower($password)) == 'studip') {
            $errors[] = _('Aus Sicherheitsgr�nden darf das Passwort nicht "Stud.IP" oder eine Abwandlung davon sein.');
        }

        if (count($errors) > 0) {
            $this->reportErrorWithDetails(_('Bitte �berpr�fen Sie Ihre Eingabe:'), $errors);
        } else {
            $this->user->password = $hasher->HashPassword($password);
            if ($this->user->store()) {
                $this->reportSuccess(_('Das Passwort wurde erfolgreich ge�ndert.'));
            }
        }
        $this->redirect('settings/password');
    }
}
