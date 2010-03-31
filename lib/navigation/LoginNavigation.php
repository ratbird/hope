<?php
/*
 * LoginNavigation.php - navigation for login page
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Elmar Ludwig
 * @copyright   2010 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
*/

class LoginNavigation extends Navigation
{
    /**
     * Initialize the subnavigation of this item. This method
     * is called once before the first item is added or removed.
     */
    public function initSubNavigation()
    {
        parent::initSubNavigation();

        $navigation = new Navigation(_('Login - für registrierte NutzerInnen'), 'index.php?again=yes');
        $this->addSubNavigation('login', $navigation);

        if (in_array('CAS', $GLOBALS['STUDIP_AUTH_PLUGIN'])) {
            $navigation = new Navigation(_('Login - für Single Sign On mit CAS'), 'index.php?again=yes&sso=cas');
            $this->addSubNavigation('login_cas', $navigation);
        }

        if (in_array('Shib', $GLOBALS['STUDIP_AUTH_PLUGIN'])) {
            $navigation = new Navigation(_('Shibboleth Login - für Single Sign On mit Shibboleth'), 'index.php?again=yes&sso=shib');
            $this->addSubNavigation('login_shib', $navigation);
        }

        if (get_config('ENABLE_SELF_REGISTRATION')) {
            $navigation = new Navigation(_('Registrieren - um NutzerIn zu werden'), 'register1.php');
            $this->addSubNavigation('register', $navigation);
        }

        if (get_config('ENABLE_FREE_ACCESS')) {
            $navigation = new Navigation(_('Freier Zugang - ohne Registrierung'), 'freie.php');
            $this->addSubNavigation('browse', $navigation);
        }

        if (get_config('EXTERNAL_HELP')) {
            $navigation = new Navigation(_('Hilfe - zu Bedienung und Funktionsumfang'), format_help_url('Basis.Allgemeines'));
            $this->addSubNavigation('help', $navigation);
        }
    }
}
