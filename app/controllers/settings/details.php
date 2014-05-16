<?php
/**
 * SettingsController - Administration of all user details related
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
class Settings_DetailsController extends Settings_SettingsController
{
    /**
     * Set up this controller and define the infobox.
     *
     * @param String $action Name of the action to be invoked
     * @param Array  $args   Arguments to be passed to the action method
     */
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        PageLayout::setHelpKeyword('Basis.HomepageLebenslauf');
        PageLayout::setTitle($this->user->perms == 'dozent'
                             ? _('Lebenslauf, Arbeitsschwerpunkte und Publikationen bearbeiten')
                             : _('Lebenslauf bearbeiten'));
        Navigation::activateItem('/profile/edit/details');
        SkipLinks::addIndex(_('Private Daten bearbeiten'), 'layout_content');

        Helpbar::get()
            ->addPlainText(_('Informationen'),
                           array(
                               _('Hier können Sie Angaben über Ihre privaten Kontaktdaten '
                               .'sowie Lebenslauf und Hobbys machen.'),
                               _('Alle Angaben die Sie hier machen sind freiwillig!')
                           ),
                           'icons/16/white/info.png');
    }

    /**
     * Display a user's details.
     */
    public function index_action()
    {
        //add the free administrable datafields
        $userEntries = DataFieldEntry::getDataFieldEntries($this->user->user_id);
        $userEntries = array_filter($userEntries, function ($entry) { return $entry->isVisible(); });

        $this->locked_info     = LockRules::CheckLockRulePermission($this->user->user_id)
                               ? LockRules::getObjectRule($this->user->user_id)->description
                               : false;
        $this->is_dozent       = $this->user->perms == 'dozent';
        $this->user_entries    = $userEntries;
        $this->invalid_entries = $invalidEntries;
    }

    /**
     * Stores a user's details.
     */
    public function store_action()
    {
        $this->check_ticket();

        $changed = false;

        if ($GLOBALS['ENABLE_SKYPE_INFO']) {
            $new_skype_name = Request::get('skype_name');
            if ($new_skype_name != $this->config->SKYPE_NAME) {
                $this->config->store('SKYPE_NAME', $new_skype_name);
                Visibility::updatePrivacySettingWithTest(Request::get('skype_name'), _("Skype Name"), "skype_name", 'privatedata', 1, $this->user->user_id);
                $changed = true;
            }

            if (Request::int('skype_online_status') != $this->config->SKYPE_ONLINE_STATUS) {
                $this->config->store('SKYPE_ONLINE_STATUS', Request::int('skype_online_status'));
                Visibility::updatePrivacySettingWithTest(Request::int('skype_online_status'), _("Skype Online Status"), "skype_online_status", 'skype_name', 1, $this->user->user_id);
                $changed = true;
            }
        }

        $mapping = array(
            'telefon'    => 'privatnr',
            'cell'       => 'privatcell',
            'anschrift'  => 'privadr',
            'home'       => 'Home',
            'motto'      => 'motto',
            'hobby'      => 'hobby',
            'lebenslauf' => 'lebenslauf',
            'schwerp'    => 'schwerp',
            'publi'      => 'publi',
        );
        
        // Visibilitymapping Remove in Stud.IP 3.0 with a migration
        $vis_mapping = array(
            'telefon'    => 'private_phone',
            'cell'       => 'private_cell',
            'anschrift'  => 'privadr',
            'home'       => 'homepage',
            'motto'      => 'motto',
            'hobby'      => 'hobby',
            'lebenslauf' => 'lebenslauf',
            'schwerp'    => 'schwerp',
            'publi'      => 'publi',
        );
        
        $settingsname = array(
            'telefon'    => _('Private Telefonnummer'),
            'cell'       => _('Private Handynummer'),
            'anschrift'  => _('Private Adresse'),
            'home'       => _('Homepage-Adresse'),
            'motto'      => _('Motto'),
            'hobby'      => _('Hobbies'),
            'lebenslauf' => _('Lebenslauf'),
            'schwerp'    => _('Arbeitsschwerpunkte'),
            'publi'      => _('Publikationen'),
        );

        foreach ($mapping as $key => $column) {
            $value = Request::get($key);
            if ($this->user->$column != $value && $this->shallChange('user_info.' . $column, $column, $value)) {
                $this->user->$column = $value;
                Visibility::updatePrivacySettingWithTest($value, $settingsname[$key], $vis_mapping[$key], 'privatedata', 1, $this->user->user_id);
                $changed = true;
            }
        }

        $datafields_changed = false;
        $errors = array();

        $datafields = DataFieldEntry::getDataFieldEntries($this->user->user_id, 'user');
        $data       = Request::getArray('datafields');
        foreach ($datafields as $id => $entry) {
            if (isset($data[$id]) && $data[$id] != $entry->getValue()) {
                
                // i really dont know if this is correct but it works
                $visibility = $datafields[$id]->structure->data;
                Visibility::updatePrivacySettingWithTest($data[$id], $visibility['name'], $visibility['datafield_id'], 'additionaldata', 1, $this->user->user_id);
                $entry->setValueFromSubmit($data[$id]);
                if ($entry->isValid()) {
                    if ($entry->store()) {
                        $datafields_changed = true;
                    }
                } else {
                    $errors[] = sprintf(_('Fehlerhafter Eintrag im Feld <em>%s</em>: %s (Eintrag wurde nicht gespeichert)'), 
                                        $entry->getName(), $entry->getDisplayValue());
                }
            }
        }

        if (count($errors) > 0) {
            $this->reportErrorWithDetails(_('Bitte überprüfen Sie Ihre Eingaben.'), $errors);
        } else if ($this->user->store() || $changed || $datafields_changed) {
            $this->reportSuccess(_('Daten im Lebenslauf u.a. wurden geändert.'));

            setTempLanguage($this->user->user_id);
            $this->postPrivateMessage(_('Daten im Lebenslauf u.a. wurden geändert.'));
            restoreLanguage();
        }
        $this->redirect('settings/details');
    }
}
