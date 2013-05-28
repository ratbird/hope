<?php
/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

require_once "lib/classes/UpdateInformation.class.php";
require_once 'lib/datei.inc.php';
require_once dirname(__file__)."/models/BlubberPosting.class.php";
require_once dirname(__file__)."/models/BlubberExternalContact.class.php";
require_once dirname(__file__)."/models/BlubberStream.class.php";

class Blubber extends StudIPPlugin implements StandardPlugin, SystemPlugin {

    public $config = array();

    /**
     * Constructor of Plugin : adds Navigation and collects information for javascript-update.
     */
    public function __construct() {
        parent::__construct();
        if (UpdateInformation::isCollecting()) {
            $data = Request::getArray("page_info");
            if (stripos(Request::get("page"), "plugins.php/blubber") !== false) {
                $output = array();
                switch ($data['Blubber']['stream']) {
                    case "global":
                        $stream = BlubberStream::getGlobalStream();
                        break;
                    case "course":
                        $stream = BlubberStream::getCourseStream($data['Blubber']['context_id']);
                        break;
                    case "profile":
                        $stream = BlubberStream::getProfileStream($data['Blubber']['context_id']);
                        break;
                    /*case "thread":
                        $stream = BlubberStream::getThreadStream($data['Blubber']['context_id']);
                        break;
                     */
                    case "custom":
                        $stream = new BlubberStream($data['Blubber']['context_id']);
                        break;
                }
                $last_check = $data['Blubber']['last_check'] ? $data['Blubber']['last_check'] : (time() - 5 * 60);

                $new_postings = $stream->fetchNewPostings($last_check);

                $factory = new Flexi_TemplateFactory($this->getPluginPath()."/views");
                foreach ($new_postings as $new_posting) {
                    if ($new_posting['root_id'] === $new_posting['topic_id']) {
                        $thread = $new_posting;
                        $template = $factory->open("streams/thread.php");
                        $template->set_attribute('thread', $new_posting);
                    } else {
                        $thread = new BlubberPosting($new_posting['root_id']);
                        $template = $factory->open("streams/comment.php");
                        $template->set_attribute('posting', $new_posting);
                    }
                    BlubberPosting::$course_hashes = ($thread['user_id'] !== $thread['Seminar_id'] ? $thread['Seminar_id'] : false);
                    $template->set_attribute("course_id", $data['Blubber']['seminar_id']);
                    $output['postings'][] = array(
                        'posting_id' => $new_posting['topic_id'],
                        'mkdate' => $new_posting['mkdate'],
                        'root_id' => $new_posting['root_id'],
                        'content' => $template->render()
                    );
                }
                UpdateInformation::setInformation("Blubber.getNewPosts", $output);

                //Events-Queue:
                $db = DBManager::get();
                $events = $db->query(
                    "SELECT event_type, item_id " .
                    "FROM blubber_events_queue " .
                    "WHERE mkdate >= ".$db->quote($last_check)." " .
                    "ORDER BY mkdate ASC " .
                "")->fetchAll(PDO::FETCH_ASSOC);
                UpdateInformation::setInformation("Blubber.blubberEvents", $events);
                $db->exec(
                    "DELETE FROM blubber_events_queue " .
                    "WHERE mkdate < UNIX_TIMESTAMP() - 60 * 60 * 6 " .
                "");
            }
        }
        if (Navigation::hasItem("/community")) {
            $nav = new Navigation($this->getDisplayTitle(), PluginEngine::getURL($this, array(), "streams/global"));
            $nav->addSubNavigation("global", new AutoNavigation(_("Globaler Stream"), PluginEngine::getURL($this, array(), "streams/global")));
            foreach (BlubberStream::findMine() as $stream) {
                $url = PluginEngine::getURL($this, array(), "streams/custom/".$stream->getId());
                $nav->addSubNavigation($stream->getId(), new AutoNavigation($stream['name'], $url));
                if ($stream['defaultstream']) {
                    $nav->setURL($url);
                }
            }
            $nav->addSubNavigation("add", new AutoNavigation(_("+"), PluginEngine::getURL($this, array(), "streams/edit")));
            Navigation::insertItem("/community/blubber", $nav, "online");
            Navigation::getItem("/community")->setURL($nav->getURL());
        }
        
        if (Navigation::hasItem("/profile") && 
                $this->isActivated(get_userid(Request::username('username', 
                $GLOBALS['auth']->auth['uname'])), 'user')) {
            $nav = new AutoNavigation(_("Blubber"), PluginEngine::getURL($this, 
                array('user_id' => get_userid(Request::get("username"))), 
                "streams/profile"));
            Navigation::addItem("/profile/blubber", $nav);
        }
    }

    /**
     * Returns a navigation for the tab displayed in the course.
     * @param string $course_id of the course
     * @return \AutoNavigation
     */
    public function getTabNavigation($course_id) {
        $tab = new AutoNavigation($this->getDisplayTitle(), PluginEngine::getLink($this, array(), "streams/forum"));
        $tab->setImage(Assets::image_path("icons/16/white/blubber"));
        return array('blubberforum' => $tab);
    }

    /**
     * Returns a navigation-object with the grey/red icon for displaying in the
     * meine_seminare.php page.
     * @param string  $course_id
     * @param int $last_visit
     * @param string|null  $user_id
     * @return \AutoNavigation
     */
    public function getIconNavigation($course_id, $last_visit, $user_id = null) {
        if (!$user_id) {
            $user_id = $GLOBALS['user']->id;
        }
        $icon = new AutoNavigation($this->getDisplayTitle(), PluginEngine::getLink($this, array(), "streams/forum"));
        $db = DBManager::get();
        $last_own_posting_time = (int) $db->query(
            "SELECT mkdate " .
            "FROM blubber " .
            "WHERE user_id = ".$db->quote($user_id)." " .
                "AND Seminar_id = ".$db->quote($course_id)." " .
                "AND context_type = 'course' " .
            "ORDER BY mkdate DESC " .
        "")->fetch(PDO::FETCH_COLUMN, 0);
        $new_ones = $db->query(
            "SELECT COUNT(*) " .
            "FROM blubber " .
            "WHERE chdate > ".$db->quote(max($last_visit, $last_own_posting_time))." " .
                "AND user_id != ".$db->quote($user_id)." " .
                "AND Seminar_id = ".$db->quote($course_id)." " .
                "AND context_type = 'course' " .
        "")->fetch(PDO::FETCH_COLUMN, 0);
        if ($new_ones) {
            $title = $new_ones > 1 ? sprintf(_("%s neue Blubber"), $new_ones) : _("1 neuer Blubber");
            $icon->setImage(Assets::image_path("icons/16/red/blubber"), array('title' => $title));
            $icon->setTitle($title);
        } else {
            $icon->setImage(Assets::image_path("icons/16/grey/blubber"), array('title' => $this->getDisplayTitle()));
        }
        return $icon;
    }

    /**
     * Needed function to return notification-objects.
     * @param string $course_id
     * @param int $since
     * @param string $user_id
     * @return array of type ContentElement
     */
    public function getNotificationObjects($course_id, $since, $user_id)
    {
        $blubber = BlubberPosting::getPostings(array(
            'seminar_id' => $course_id,
            'since' => $since
        ));
        $contents = array();
        foreach ($blubber as $blubb) {
            $contents[] = new ContentElement(
                $blubb['title'],
                $blubb['title'],
                $blubb['description'],
                $blubb['user_id'],
                get_fullname($blubb['user_id']),
                PluginEngine::getURL($this, array(), 'streams/thread/'.$blubb['root_id']),
                $blubb['mkdate']
            );
        }
        return $contents;
    }

    /**
     * Returns no template, because this plugin doesn't want to insert an
     * info-template in the course-overview.
     * @param string $course_id
     * @return null
     */
    public function getInfoTemplate($course_id)  {
        return null;
    }

    /**
     * Returns localized title of this plugin.
     * @return type
     */
    public function getDisplayTitle() {
        return _("Blubbern");
    }

    /**
    * This method dispatches and displays all actions. It uses the template
    * method design pattern, so you may want to implement the methods #route
    * and/or #display to adapt to your needs.
    *
    * @param  string  the part of the dispatch path, that were not consumed yet
    *
    * @return void
    */
    public function perform($unconsumed_path) {
        if(!$unconsumed_path) {
            header("Location: " . PluginEngine::getUrl($this), 302);
            return false;
        }
        $trails_root = $this->getPluginPath();
        $dispatcher = new Trails_Dispatcher($trails_root, null, 'show');
        $dispatcher->current_plugin = $this;
        $dispatcher->dispatch($unconsumed_path);
    }
}
