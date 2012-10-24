<?php
/*
 * Settings/StudiesController
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
class Settings_StudiesController extends Settings_SettingsController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        
        if (!in_array($this->user->perms, words('autor tutor dozent'))) {
            throw new AccessDeniedException(_('Sie haben keinen Zugriff auf diesen Bereich.'));
        }

        PageLayout::setHelpKeyword('Basis.HomepageUniversitäreDaten');
        PageLayout::setTitle(_('Studiengang bearbeiten'));
        Navigation::activateItem('/profile/edit/studies');
        SkipLinks::addIndex(_('Fächer und Abschlüsse auswählen'), 'select_fach_abschluss');
        SkipLinks::addIndex(_('Zu Einrichtungen zuordnen'), 'select_institute');

        $this->allow_change = array(
            'sg' => !StudipAuthAbstract::CheckField('studiengang_id', $this->user->auth_plugin)
                    && ($GLOBALS['ALLOW_SELFASSIGN_STUDYCOURSE'] || $GLOBALS['perm']->have_perm('admin')),
            'in' => $GLOBALS['ALLOW_SELFASSIGN_INSTITUTE'] || $GLOBALS['perm']->have_perm('admin'),
        );
    }
    
    public function index_action()
    {
        $infobox_message = _('Hier können Sie Angaben &uuml;ber Ihre Studienkarriere machen.');
        $this->setInfoBoxImage('infobox/groups.jpg');
        $this->addToInfobox(_('Informationen'), $infobox_message, 'icons/16/black/info.png');
    }
    
    public function store_sg_action()
    {
        $this->check_ticket();

        $any_change = false;

        $fach_abschluss_delete = Request::getArray('fach_abschluss_delete');
        if (count($fach_abschluss_delete) > 0) {
            $query = "DELETE FROM user_studiengang
                      WHERE user_id = ? AND studiengang_id = ? AND abschluss_id IN (?)";
            $statement = DBManager::get()->prepare($query);

            foreach ($fach_abschluss_delete as $studiengang_id => $abschluesse) {
                $statement->execute(array(
                    $this->user->user_id,
                    $studiengang_id,
                    $abschluesse
                ));
                if ($statement->rowCount() > 0) {
                    $any_change = true;
                }
            }
        }

        if (!$any_change) {
            $query = "UPDATE IGNORE user_studiengang
                      SET semester = ?
                      WHERE user_id = ? AND studiengang_id = ? AND abschluss_id = ?";
            $statement = DBManager::get()->prepare($query);
            
            $change_fachsem = Request::getArray('change_fachsem');
            foreach ($change_fachsem as $studiengang_id => $abschluesse) {
                foreach ($abschluesse as $abschluss_id => $semester) {
                    $statement->execute(array(
                        $semester,
                        $this->user->user_id,
                        $studiengang_id,
                        $abschluss_id
                    ));
                    if ($statement->rowCount() > 0) {
                        $any_change = true;
                    }
                }
            }

            $new_studiengang = Request::option('new_studiengang');
            if ($new_studiengang && $new_studiengang != 'none') {
                $query = "INSERT IGNORE INTO user_studiengang
                            (user_id, studiengang_id, abschluss_id, semester)
                          VALUES (?, ?, ?, ?)";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array(
                    $this->user->user_id,
                    $new_studiengang,
                    Request::option('new_abschluss'),
                    Request::int('fachsem')
                ));
                if ($statement->rowCount() > 0) {
                    $any_change = true;
                }
            }
        }

        if ($any_change) {
            $this->reportSuccess(_('Die Zuordnung zu Studiengängen wurde ge&auml;ndert.'));

            setTempLanguage($this->user->user_id);
            $this->postPrivateMessage(_("Die Zuordnung zu Studiengängen wurde geändert!\n"));
            restoreLanguage();
        }

        $this->redirect('settings/studies');
    }
    
    public function store_in_action()
    {
        $this->check_ticket();

        $inst_delete = Request::optionArray('inst_delete');
        if (count($inst_delete) > 0) {
            $query = "DELETE FROM user_inst WHERE user_id = ? AND Institut_id = ?";
            $statement = DBManager::get()->prepare($query);
            
            foreach ($inst_delete as $institute_id) {
                $statement->execute(array(
                    $this->user->user_id,
                    $institute_id
                ));
                if ($statement->rowCount() > 0) {
                    log_event('INST_USER_DEL', $institute_id, $this->user->user_id);
                    $delete = true;
                }
            }
        }

        $new_inst = Request::option('new_inst');
        if ($new_inst) {
            $query = "INSERT IGNORE INTO user_inst
                        (user_id, Institut_id, inst_perms)
                      VALUES (?, ?, 'user')";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                $this->user->user_id,
                $new_inst
            ));
            if ($statement->rowCount() > 0) {
                log_event('INST_USER_ADD', $new_inst , $this->user->user_id, 'user');
                $new = true;
            }
        }

        if ($delete || $new) {
            $this->reportSuccess(_('Die Zuordnung zu Einrichtungen wurde ge&auml;ndert.'));

            setTempLanguage($this->user->user_id);
            $this->postPrivateMessage(_("Die Zuordnung zu Einrichtungen wurde geändert!\n"));
            restoreLanguage();
        }

        $this->redirect('settings/studies');
    }
}
