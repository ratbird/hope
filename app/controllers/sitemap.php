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
 * @since       Stud.IP version 1.12
 */

require_once 'lib/trails/AuthenticatedController.php';

class SitemapController extends AuthenticatedController
{
    const SITEMAP_CACHE_KEY = '/sitemap/';

    public function index_action()
    {
        $userid = $GLOBALS['auth']->auth['uid'];;

        $GLOBALS['CURRENT_PAGE'] =  _('Sitemap');
        Navigation::activateItem('/sitemap');

        $cache = StudipCacheFactory::getCache();

        //getting mainnavigation
        $this->navigation = unserialize($cache->read(self::SITEMAP_CACHE_KEY.'main/'.$userid));

        if (empty($this->navigation)) {
            $this->navigation = new StudipNavigation('ignore');
            $this->navigation->removeSubNavigation('course');
            $this->navigation->removeSubNavigation('links');
            $this->navigation->removeSubNavigation('login');
            $this->navigation->removeSubNavigation('account');
            $this->navigation->removeSubNavigation('start');
            $this->navigation->removeSubNavigation('sitemap');
            $this->navigation->insertSubNavigation('account', new Navigation(_('Start'), 'index.php'), 'browse');
            $cache->write(self::SITEMAP_CACHE_KEY.'main/'.$userid, serialize($this->navigation));
        }

        //getting quicklinks
        $this->subnavigation = unserialize($cache->read(self::SITEMAP_CACHE_KEY.'quicklinks/'.$userid));
        if (empty($this->subnavigation)) {
            $subnavigation = new StudipNavigation('ignore');
            foreach ($subnavigation as $key => $nav) {
                if ($key == 'links') {
                    $this->subnavigation = $nav;
                }
            }
            $cache->write(self::SITEMAP_CACHE_KEY.'quicklinks/'.$userid, serialize($this->subnavigation));
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
        $this->infobox = array('picture' => 'infoboxes/administration.jpg', 'content' => $infobox_content);
    }
}
