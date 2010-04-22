<?php
/*
 * SitemapController - Shows a global sitemap for all available pages
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @copyright   2010 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since		Stud.IP version 1.12
 */

require_once 'app/controllers/authenticated_controller.php';

class SitemapController extends AuthenticatedController {

    public function index_action()
    {
        $GLOBALS['CURRENT_PAGE'] =  _('Sitemap');
        Navigation::activateItem('/sitemap');

        //change navigation
        $this->navigation = new StudipNavigation();
        $this->navigation->removeSubNavigation('course');
        $this->navigation->removeSubNavigation('links');
        $this->navigation->removeSubNavigation('login');
        $this->navigation->removeSubNavigation('account');
        $this->navigation->removeSubNavigation('start');
        $this->navigation->removeSubNavigation('sitemap');
        $this->navigation->insertSubNavigation('account', new Navigation(_('Start'), 'index.php'), 'browse');

        //getting quicklinks
        $subnavigation = new StudipNavigation();
        foreach ($subnavigation as $key => $nav) {
            if ($key == 'links') {
                $this->subnavigation = $nav;
            }
        }
        $this->subnavigation->removeSubNavigation('account');
        $this->subnavigation->insertSubNavigation('account', new AccountNavigation(), 'sitemap');

        //infobox
        $infobox_content = array(
            array(
                'kategorie' => _('Hinweise:'),
                'eintrag'   => array(
                    array(
                        'icon' => 'info.gif',
                        'text' => _('Auf dieser Seite finden Sie eine Übersicht über alle verfügbaren Seiten.')
                    )
                )
            )
        );
        $this->infobox = array('picture' => 'verwalten.jpg', 'content' => $infobox_content);
    }
}