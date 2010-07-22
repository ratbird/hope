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
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       Stud.IP version 2.0
 */

require_once 'app/controllers/authenticated_controller.php';

/**
 * The sitemap is only visible for logged in users, because the sitemap depends
 * on the permissions of the user. It creates two new StudipNavigation Object to
 * display the main navigation (toolbar) and the quick links (subnavigation)
 *
 */
class SitemapController extends AuthenticatedController
{
    //using the StudipCache for the sitemap
    const SITEMAP_CACHE_KEY = '/sitemap/';

    /**
     * The only main method, that loads the navigation object and displays it.
     */
    public function index_action()
    {
        //we need the id of the user
        $userid = $GLOBALS['auth']->auth['uid'];

        //Setting title and activate this item in the navigation
        PageLayout::setTitle(_('Sitemap'));
        Navigation::activateItem('/sitemap');

        //getting the cache
        $cache = StudipCacheFactory::getCache();

        //getting main-navigation from the cache
        $this->navigation = unserialize($cache->read(self::SITEMAP_CACHE_KEY.'main/'.$userid));

        //load the navigation, if the cache is empty
        if (empty($this->navigation)) {
            //remove hidden subnavigations from the main navigation in a new object
            $this->navigation = new StudipNavigation('ignore');
            $this->navigation->removeSubNavigation('course');
            $this->navigation->removeSubNavigation('links');
            $this->navigation->removeSubNavigation('login');
            $this->navigation->removeSubNavigation('account');
            $this->navigation->removeSubNavigation('start');
            $this->navigation->removeSubNavigation('sitemap');
            //adding an entry to the sitemap navigation
            $this->navigation->insertSubNavigation('account', new Navigation(_('Start'), 'index.php'), 'browse');
            //storing this navigation into the cache
            $cache->write(self::SITEMAP_CACHE_KEY.'main/'.$userid, serialize($this->navigation));
        }

        //getting quicklinks (either from the cache or from a new object)
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
        $this->infobox = array('picture' => 'infobox/administration.jpg', 'content' => $infobox_content);
    }
}
