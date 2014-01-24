<?php
/**
 * Settings_AvatarController - Administration of all user avatar related
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

class Settings_AvatarController extends Settings_SettingsController
{
    /**
     * Set up this controller
     *
     * @param String $action Name of the action to be invoked
     * @param Array  $args   Arguments to be passed to the action method
     */
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        PageLayout::setHelpKeyword('Basis.HomepageBild');
        PageLayout::setTitle(_('Hochladen eines pers�nlichen Bildes'));
        Navigation::activateItem('/profile/avatar');
        SkipLinks::addIndex(_('Hochladen eines pers�nlichen Bildes'), 'edit_avatar');

        $this->customized = Avatar::getAvatar($this->user->user_id)->is_customized();
        if ($this->customized) {
            SkipLinks::addIndex(_('Eigenes Bild l�schen'), 'delete_picture');
        }
    }

    /**
     * Display the avatar information of a user
     */
    public function index_action()
    {
    }

    /**
     * Upload a new avatar.
     * Sends an information email to the user if the action was not invoked
     * by himself.
     */
    public function upload_action()
    {
        $this->check_ticket();

        if (Request::submitted('upload')) {
            try {
                Avatar::getAvatar($this->user->user_id)->createFromUpload('imgfile');

                NotificationCenter::postNotification('AvatarDidUpload', $this->user->user_id);

                $message = _('Die Bilddatei wurde erfolgreich hochgeladen. '
                            .'Eventuell sehen Sie das neue Bild erst, nachdem Sie diese Seite '
                            .'neu geladen haben (in den meisten Browsern F5 dr�cken).');
                $this->reportSuccess($message);

                setTempLanguage($this->user->user_id);
                $this->postPrivateMessage(_("Ein neues Bild wurde hochgeladen.\n"));
                restoreLanguage();
                Visibility::addPrivacySetting(_('Avatar'), 'picture', 'privatedata', 1, $this->user->user_id);
            } catch (Exception $e) {
                $this->reportError($e->getMessage());
            }
        }
        $this->redirect('settings/avatar');
    }

    /**
     * Resets/removes a user's avatar.
     */
    public function reset_action()
    {
        $this->check_ticket();

        if (Request::submitted('reset')) {
            Avatar::getAvatar($this->user->user_id)->reset();
            Visibility::removePrivacySetting('picture');
            $this->reportSuccess(_('Bild gel&ouml;scht.'));
        }
        $this->redirect('settings/avatar');
    }
}