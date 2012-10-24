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
class Settings_DetailsController extends Settings_SettingsController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        PageLayout::setHelpKeyword('Basis.HomepageLebenslauf');
        PageLayout::setTitle($this->user->perms == 'dozent'
                             ? _('Lebenslauf, Arbeitsschwerpunkte und Publikationen bearbeiten')
                             : _('Lebenslauf bearbeiten'));
        Navigation::activateItem('/profile/edit/details');
        SkipLinks::addIndex(_('Private Daten bearbeiten'), 'layout_content');

        $infobox_message = _('Hier können Sie Angaben &uuml;ber Ihre privaten Kontaktdaten '
                            .'sowie Lebenslauf und Hobbys machen.') . '<br>'
                            ._('Alle Angaben die Sie hier machen sind freiwillig!');
        $this->setInfoBoxImage('infobox/groups.jpg');
        $this->addToInfobox(_('Informationen'), $infobox_message, 'icons/16/black/info.png');
    }

    /**
     *
     */
    public function index_action()
    {
        //add the free administrable datafields
        $userEntries = DataFieldEntry::getDataFieldEntries($this->user->user_id);
        array_filter($userEntries, function ($entry) { return $entry->isVisible(); });

        $this->locked_info     = LockRules::CheckLockRulePermission($this->user->user_id)
                               ? LockRules::getObjectRule($this->user->user_id)->description
                               : false;
        $this->is_dozent       = $this->user->perms == 'dozent';
        $this->user_entries    = $userEntries;
        $this->invalid_entries = $invalidEntries;
    }

    /**
     *
     */
    public function store_action()
    {
        $this->check_ticket();

        if ($GLOBALS['ENABLE_SKYPE_INFO']) {
            $this->config->store('SKYPE_NAME', preg_replace('/[^a-z0-9.,_-]/i', '', Request::get('skype_name')));
            $this->config->store('SKYPE_ONLINE_STATUS', Request::int('skype_online_status'));
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

        foreach ($mapping as $key => $column) {
            $value = Request::get($key);
            if ($this->shallChange('user_info.' . $column, $column, $value)) {
                $this->user->$column = $value;
            }
        }

        $datafields_changed = false;
        $errors = array();

        $datafields = DataFieldEntry::getDataFieldEntries($this->user->user_id, 'user');
        $data       = Request::getArray('datafields');
        foreach ($datafields as $id => $entry) {
            if (isset($data[$id]) && $data[$id] != $entry->getValue()) {
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
        } else if ($this->user->store() || $datafields_changed) {
            $this->reportSuccess(_('Daten im Lebenslauf u.a. wurden geändert.'));

            setTempLanguage($this->user->user_id);
            $this->postPrivateMessage(_('Daten im Lebenslauf u.a. wurden geändert.'));
            restoreLanguage();
        }
        $this->redirect('settings/details');
    }
}
