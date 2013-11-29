<?php

/**
 * start.php - Startpage controller
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */
require_once 'app/controllers/authenticated_controller.php';

class StartController extends AuthenticatedController {

    public function before_filter(&$action, &$args) {
        parent::before_filter($action, $args);

//set layout for portalplugins
        $this->content_layout = $GLOBALS['template_factory']->open('shared/index_box');
    }

    public function index_action() {
        global $user, $perm, $auth;

        // Fetch headline
        switch ($perm->get_perm()) {
            case 'root':
                $this->headline = _("Startseite für Root bei Stud.IP");
                break;
            case 'admin':
                $this->headline = _("Startseite für AdministratorInnen bei Stud.IP");
                break;
            case 'dozent':
                $this->headline = _("Startseite für DozentInnen bei Stud.IP");
                break;
            default:
                $this->headline = _("Ihre persönliche Startseite bei Stud.IP");
                break;
        }

        // Display banner ad
        if (get_config('BANNER_ADS_ENABLE')) {
            $this->banner = Banner::getRandomBanner()->toHTML();
        }

        // Prüfen, ob PortalPlugins vorhanden sind.
        $portalplugins = PluginEngine::getPlugins('PortalPlugin');

        foreach ($portalplugins as $portalplugin) {
            $template = $portalplugin->getPortalTemplate();

            if ($template) {
                $this->portalplugins .= $template->render(NULL, $this->content_layout);
                $this->content_layout->clear_attributes();
            }
        }

        // Load RSS Feeds
        $this->rss = RSSFeed::loadByUserId($user->id);

        // Populate infobox
        $this->addInfobox();

        // Fetch News
        require_once 'lib/showNews.inc.php';
        process_news_commands($index_data);
        ob_start();
        show_news('studip', $perm->have_perm('root'), 0, $index_data['nopen'], "", null, $index_data);
        $this->news = ob_get_contents();
        ob_end_clean();

        // Fetch Calendar
        if (!$perm->have_perm('admin')) { // only dozent, tutor, autor, user
            require_once 'lib/show_dates.inc.php';

            //open and close
            if (Request::get('dopen')) {
                $index_data['dopen'] = Request::option('dopen');
            }
            if (Request::get('dclose')) {
                unset($index_data['dopen']);
            }

            // display dates
            $start = time();
            $end = $start + 60 * 60 * 24 * 7;
            ob_start();
            if (get_config('CALENDAR_ENABLE')) {
                show_all_dates($start, $end, TRUE, FALSE, $index_data['dopen']);
            } else {
                show_dates($start, $end, $index_data['dopen']);
            }
            $this->calendar = ob_get_contents();
            ob_end_clean();
        }

        // Fetch Votes
        if (get_config('VOTE_ENABLE')) {
            require_once 'lib/vote/vote_show.inc.php';
            ob_start();
            show_votes('studip', $auth->auth['uid'], $perm);
            $this->votes = ob_get_contents();
            ob_end_clean();
        }
    }

    /**
     * Prepare the infobox for the startpage
     * currently there is only the image :(
     */
    private function addInfobox() {
        $this->setInfoboxImage('indexpage.jpg');

        /*
         * I leave this code here in case someone wants to start adding info right away
         * 
          $this->addToInfobox(_('Aktionen'), "something something ... darkside", 'icons/16/black/add.png');
         */
    }

}
