<?php
require_once 'app/controllers/authenticated_controller.php';
require_once 'lib/classes/RSSFeed.class.php';

/**
 * rss_feeds.php - controller class for the rss feed administration
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author   Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @category Stud.IP
 * @package  admin
 * @since    2.4
 */
class Admin_RssFeedsController extends AuthenticatedController
{
    /**
     * Common tasks for all actions.
     */
    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        PageLayout::setHelpKeyword('Basis.MyStudIPRSS');
        PageLayout::setTitle(_('Einstellungen der RSS-Anzeige anpassen'));
        Navigation::activateItem('/tools/rss');
        SkipLinks::addIndex(_('Einstellungen der RSS-Anzeige anpassen'), 'rss-feeds', 100);

        $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));
    }

    /**
     * Overview of all of the current user's feeds
     */
    public function index_action()
    {
        $this->feeds = RSSFeed::loadByUserId($GLOBALS['user']->id);

        $this->setInfoboxImage('infobox/administration.png');

        // Infobox: "Add feed"
        $add = sprintf('<a href="%s">%s</a>',
                       $this->url_for('admin/rss_feeds/create'),
                       _('RSS-Feed neu anlegen'));
        $this->addToInfobox(_('Aktionen'), $add, 'icons/16/black/plus');

        // Infobox: "Check feeds"
        $add = sprintf('<a href="%s">%s</a>',
                       $this->url_for('admin/rss_feeds/check'),
                       _('RSS-Feeds prüfen'));
        $this->addToInfobox(_('Aktionen'), $add, 'icons/16/black/refresh');

        // Infobox: "Configuration"
        $factory = new Flexi_TemplateFactory('../app/views/admin/rss_feeds');
        $config = $factory->render('config', array(
                      'controller' => $this,
                      'limit'      => RSSFeed::getLimit(),
                  ));
        $this->addToInfobox(_('Konfiguration'), $config, 'icons/16/black/admin');
    }

    /**
     * Create a new feed
     */
    public function create_action()
    {
        $feed_id = md5(uniqid('blablubburegds4', true));

        RSSFeed::increasePriorities();

        $feed = new RSSFeed();
        $feed->name   = _('neuer Feed');
        $feed->url    = _('URL');
        $feed->hidden = false;

        $message = $feed->store()
                 ? Messagebox::success(_('Feed angelegt'))
                 : Messagebox::error(_('Anlegen fehlgeschlagen'));

        PageLayout::postmessage($message);
        $this->redirect('admin/rss_feeds');
    }

    /**
     * Checks whether all active are still reachable and updates priorities on the fly
     */
    public function check_action()
    {
        $feeds = RSSFeed::loadByUserId($GLOBALS['user']->id);

        $hidden = 0;
        foreach ($feeds as $index => $feed) {
            if (!$feed->hidden && !RSSFeed::fetch($feed->url, true)) {
                $feed->hidden = 1;
                $hidden += 1;
            }
            $feed->priority = $index;
            $feed->store();
        }

        $message = Messagebox::info(sprintf(_('Es wurden %u ungültige Feeds deaktiviert.'), $hidden));
        PageLayout::postMessage($message);
        $this->redirect('admin/rss_feeds');
    }

    /**
     * Updates (edits) the current user's feeds
     */
    public function update_action()
    {
        $success = $errors = array();

        $postFeeds = Request::getArray('feeds');
        foreach ($postFeeds as $postFeed) {
            $postFeed = array_map('trim', $postFeed);

            if ($postFeed['url'] && ($postFeed['fetch_title'] || $postFeed['name'])) {
                // Try to connect to feed
                $temp = RSSFeed::fetch($postFeed['url'], true);
                if ($temp && $temp->feed_type) {
                    if ($postFeed['fetch_title'] && $temp->channel['title']) {
                        $postFeed['name'] = $temp->channel['title'];
                    }
                    $success[] = sprintf(_('Feed: <b>%s</b> (Typ: %s) erreicht.'),
                                         htmlReady($postFeed['url']),
                                         htmlReady($temp->feed_type));
                } else {
                    $postFeed['active'] = 0;
                    $errors[] = sprintf(_('Feed: <b>%s</b> nicht erreicht, oder Typ nicht erkannt.'),
                                        htmlReady($postFeed['url']));
                }

                $feed = new RSSFeed($postFeed['id']);
                $feed->name        = $postFeed['name'];
                $feed->url         = $postFeed['url'];
                $feed->hidden      = (int)!$postFeed['active'];
                $feed->fetch_title = (int)$postFeed['fetch_title'];
                $feed->store();
            }
        }

        if (!empty($success)) {
            $message = Messagebox::success(_('RSS-Feeds geändert!'), $success, true);
            PageLayout::postMessage($message);
        }

        if (!empty($errors)) {
            $message = Messagebox::error(_('Folgende Fehler sind aufgetreten:'), $errors);
            PageLayout::postMessage($message);
        }

        $this->redirect('admin/rss_feeds');
    }

    /**
     * Moves a feed up- or downwards in the list of the current user's feeds
     */
    public function move_action($id, $direction)
    {
        $feed = new RSSFeed($id);

        if ($direction === 'up') {
            $feed->moveUp();
        } elseif ($direction === 'down') {
            $feed->moveDown();
        } else {
            throw new InvalidArgumentException('Invalid direction passed');
        }

        $message = Messagebox::success(_('RSS-Feeds wurden neu geordnet'));
        PageLayout::postMessage($message);
        $this->redirect('admin/rss_feeds');
    }

    /**
     * Deletes a feed
     */
    public function delete_action($id)
    {
        $feed    = new RSSFeed($id);
        $deleted = $feed->delete();

        $message = $deleted
                 ? Messagebox::success(_('RSS-Feed gelöscht!'))
                 : Messagebox::error(_('Löschen fehlgeschlagen!'));
        PageLayout::postMessage($message);
        $this->redirect('admin/rss_feeds');
    }

    /**
     * Stores the config for rss feed display
     */
    public function config_action()
    {
        RSSFeed::setLimit(Request::int('limit', RSSFeed::DEFAULT_LIMIT));

        $message = Messagebox::success(_('Die Einstellung wurde gespeichert'));
        PageLayout::postMessage($message);
        $this->redirect('admin/rss_feeds');
    }
}
