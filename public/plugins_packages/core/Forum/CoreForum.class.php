<?php
/*
 * Forum.class.php - Forum
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Till Glöggler <till.gloeggler@elan-ev.de>
 * @copyright   2011 ELAN e.V. <http://www.elan-ev.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

require_once 'app/models/smiley.php';
require_once 'vendor/trails/trails.php';
require_once 'lib/plugins/core/ForumModule.class.php';
require_once 'controllers/forum_controller.php';

// Notifications
NotificationCenter::addObserver('CoreForum', 'overviewDidClear', "OverviewDidClear");

class CoreForum extends StudipPlugin implements ForumModule
{
    /**
     * This method dispatches all actions.
     *
     * @param string $unconsumed_path  part of the dispatch path that was not consumed
     */    
    public function perform($unconsumed_path) {
        $this->setupAutoload();
        
        // Add JS and StyleSheet to header
        PageLayout::addScript($this->getPluginURL() . '/javascript/forum.js');
        PageLayout::addStylesheet($this->getPluginURL() . '/stylesheets/forum.css');

        parent::perform($unconsumed_path);
    }

    private function setupAutoload() {
        if (class_exists("StudipAutoloader")) {
            StudipAutoloader::addAutoloadPath(__DIR__ . '/models');
        } else {
            spl_autoload_register(function ($class) {
                include_once __DIR__ . $class . '.php';
            });
        }
    }
    
    /* interface method */
    public function getTabNavigation($course_id)
    {
        if (!$this->isActivated($course_id)) {
            return;
        }
        
        $this->setupAutoload();

        $navigation = new Navigation(_('Forum'), PluginEngine::getLink($this, array(), 'index'));
        $navigation->setImage('icons/16/white/forum.png');

        // add main third-level navigation-item
        $navigation->addSubNavigation('index', new Navigation(_('Übersicht'), PluginEngine::getLink($this, array(), 'index')));

        if (ForumPerm::has('fav_entry', $course_id)) {
            $navigation->addSubNavigation('newest', new Navigation(_("Neue Beiträge"), PluginEngine::getLink($this, array(), 'index/newest')));
            $navigation->addSubNavigation('latest', new Navigation(_("Letzte Beiträge"), PluginEngine::getLink($this, array(), 'index/latest')));
            $navigation->addSubNavigation('favorites', new Navigation(_('Gemerkte Beiträge'), PluginEngine::getLink($this, array(), 'index/favorites')));

            // mass-administrate the forum
            if (ForumPerm::has('admin', $course_id)) {
                $navigation->addSubNavigation('admin', new Navigation(_('Administration'), PluginEngine::getLink($this, array(), 'admin')));
            }
        }

        return array('forum2' => $navigation);
    }

    /* interface method */
    function getIconNavigation($course_id, $last_visit, $user_id = null)
    {
        if (!$this->isActivated($course_id)) {
            return;
        }

        $this->setupAutoload();
        
        if ($GLOBALS['perm']->have_studip_perm('user', $course_id)) {
            $num_entries = ForumVisit::getCount($course_id, ForumVisit::getVisit($course_id));
            $text = ForumHelpers::getVisitText($num_entries, $course_id);
        } else {
            $num_entries = 0;
            $text = 'Forum';
        }

        $navigation = new Navigation('forum', PluginEngine::getLink($this, array(), 'index/enter_seminar'));
        $navigation->setBadgeNumber($num_entries);

        if ($num_entries > 0) {
            $navigation->setImage('icons/20/red/new/forum.png', array('title' => $text));
        } else {
            $navigation->setImage('icons/20/grey/forum.png', array('title' => $text));
        }

        return $navigation;
    }

    /* interface method */
    function getNotificationObjects($course_id, $since, $user_id)
    {
        $this->setupAutoload();
        
        if (ForumPerm::has('view', $course_id, $user_id)) {
            $postings = ForumEntry::getLatestSince($course_id, $since);
            
            $contents = array();
            foreach ($postings as $post) {
                $obj = get_object_name($course_id, 'sem');

                $summary = sprintf(_('%s hat im Forum der Veranstaltung "%s" einen Forenbeitrag verfasst.'), 
                    get_fullname($post['user_id']),
                    $obj['name']
                ); 
 
                $contents[] = new ContentElement(
                    _('Forum: ') . $obj['name'],
                    $summary,
                    formatReady($post['content']),
                    $post['user_id'],
                    $post['author'],
                    PluginEngine::getURL($this, array(), 'index/index/' . $post['topic_id'] 
                            .'?cid='. $course_id
                            .'#'. $post['topic_id']),
                    $post['mkdate']
                );
            }
        }
        
        return $contents;
    }


    /**
     * This method is called, whenever an user clicked to clear the visit timestamps
     * and set everything as visited
     *
     * @param object $notification
     * @param string $user_id
     */
    function overviewDidClear($notification, $user_id)
    {
        $stmt = DBManager::get()->prepare("UPDATE forum_visits
            SET visitdate = UNIX_TIMESTAMP(), last_visitdate = UNIX_TIMESTAMP()
            WHERE user_id = ?");
        $stmt->execute(array($user_id));
    }

    function getInfoTemplate($course_id)
    {
        return null;
    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    /* * IMPLEMENTATION OF METHODS FROM FORUMMODULE-INTERFACE  * */
    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    function getLinkToThread($issue_id)
    {
        $this->setupAutoload();
        
        if ($topic_id = ForumIssue::getThreadIdForIssue($issue_id)) {
            return PluginEngine::getLink($this, array(), '/index/index/' . $topic_id);
        }

        return false;
    }

    function setThreadForIssue($issue_id, $title, $content)
    {
        $this->setupAutoload();
        
        ForumIssue::setThreadForIssue($GLOBALS['SessSemName'][1], $issue_id, $title, $content);
    }

    function getNumberOfPostingsForUser($user_id, $seminar_id = null)
    {
        $this->setupAutoload();
        
        return ForumEntry::countUserEntries($user_id, $seminar_id);
    }

    function getNumberOfPostingsForIssue($issue_id)
    {
        $this->setupAutoload();
        
        $topic_id = ForumIssue::getThreadIdForIssue($issue_id);

        return $topic_id ? ForumEntry::countEntries($topic_id) : 0;
    }

    function getNumberOfPostingsForSeminar($seminar_id)
    {
        $this->setupAutoload();
        
        return floor(ForumEntry::countEntries($seminar_id));
    }

    function getNumberOfPostings()
    {
        $this->setupAutoload();
        
        return ForumEntry::countAllEntries();
    }

    function getEntryTableInfo()
    {
        return array(
            'table'      => 'forum_entries',
            'content'    => 'content',
            'chdate'     => 'chdate',
            'seminar_id' => 'seminar_id',
            'user_id'    => 'user_id'
        );
    }

    function getTopTenSeminars()
    {
        $this->setupAutoload();
        
        return ForumEntry::getTopTenSeminars();
    }

    function migrateUser($user_from, $user_to)
    {
        $this->setupAutoload();
        
        return ForumEntry::migrateUser($user_from, $user_to);
    }

    function deleteContents($seminar_id)
    {
        $this->setupAutoload();
        
        return ForumEntry::delete($seminar_id);
    }

    function getDump($seminar_id)
    {
        $this->setupAutoload();
        
        return ForumEntry::getDump($seminar_id);
    }

    static function getDescription() {
        return _('Textbasierte und zeit- und ortsunabhängige '.
            'Diskursmöglichkeit. Lehrende können parallel zu '.
            'Veranstaltungsthemen Fragen stellen, die von den Studierenden '.
            'per Meinungsaustausch besprochen werden.');
    }
}
