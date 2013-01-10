<?php
/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 * 
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

require_once 'lib/classes/SimpleORMap.class.php';
require_once 'lib/forum.inc.php';

class BlubberPosting extends SimpleORMap {

    protected $db_table = "blubber";
    static public $course_hashes = false;
    static public $mention_thread_id = false;

    static public function format($text) {
        StudipFormat::addStudipMarkup("blubberhashtag", "(^|\s)#([\w\d_\.\-]*[\w\d])", "", "BlubberPosting::markupHashtags");
        $output = formatReady($text);
        StudipFormat::removeStudipMarkup("blubberhashtag");
        return $output;
    }
    
    static public function markupHashtags($markup, $matches) {
        if (self::$course_hashes) {
            $url = URLHelper::getLink("plugins.php/Blubber/forum/forum", array('hash' => $matches[2], 'cid' => self::$course_hashes));
        } else {
            $url = URLHelper::getLink("plugins.php/Blubber/forum/globalstream", array('hash' => $matches[2]));
        }
        return $matches[1].'<a href="'.$url.'" class="hashtag">#'.$markup->quote($matches[2]).'</a>';
    }
    
    static public function mention($markup, $matches) {
        $mention = $matches[0];
        $thread_id = self::$mention_thread_id;
        $username = stripslashes(substr($mention, 1));
        if ($username[0] !== '"') {
            $user_id = get_userid($username);
        } else {
            $name = substr($username, 1, strlen($username) -2);
            $db = DBManager::get();
            $user_id = $db->query(
                "SELECT user_id FROM auth_user_md5 WHERE CONCAT(Vorname, ' ', Nachname) = ".$db->quote($name)." " .
            "")->fetch(PDO::FETCH_COLUMN, 0);
        }
        $thread = new BlubberPosting($thread_id);
        if (!$thread->isNew() && $user_id && $user_id !== $GLOBALS['user']->id) {
            $user = new User($user_id);
            $messaging = new messaging();
            $url = $GLOBALS['ABSOLUTE_URI_STUDIP']."plugins.php/blubber/forum/thread/"
                . $thread_id.($thread['context_type'] === "course" ? '?cid='.$thread['Seminar_id'] : "");
            $messaging->insert_message(
                sprintf(
                    _("%s hat Sie in einem Blubber erwähnt. Zum Beantworten klicken auf Sie auf folgenen Link:\n\n%s\n"),
                    get_fullname(), $url
                ),
                get_username($user_id),
                $GLOBALS['user']->id,
                null, null, null, null,
                _("Sie wurden erwähnt.")
            );
            DBManager::get()->exec(
                "INSERT IGNORE INTO blubber_mentions " .
                "SET user_id = ".DBManager::get()->quote($user_id).", " .
                    "topic_id = ".DBManager::get()->quote($thread_id).", " .
                    "mkdate = UNIX_TIMESTAMP() " .
            "");
            return '['.$user['Vorname']." ".$user['Nachname'].']'.$GLOBALS['ABSOLUTE_URI_STUDIP']."about.php?username=".$user['username'].' ';
        } else {
            return stripslashes($mention);
        }
    }

    static public function getMyBlubberCourses() {
        $db = DBManager::get();
        return $db->query(
            "SELECT seminar_user.Seminar_id " .
            "FROM seminar_user " .
                "INNER JOIN seminare ON (seminare.Seminar_id = seminar_user.Seminar_id) " .
                "INNER JOIN plugins_activated ON (plugins_activated.poiid = CONCAT('sem', seminar_user.Seminar_id)) " .
                "INNER JOIN plugins ON (plugins_activated.pluginid = plugins.pluginid) " .
            "WHERE seminar_user.user_id = ".$db->quote($GLOBALS['user']->id)." " .
                "AND plugins_activated.state = 'on' " .
                "AND plugins.pluginclassname = 'Blubber' " .
            "ORDER BY seminare.start_time ASC, seminare.name ASC " .
        "")->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    static public function getMyBlubberBuddys() {
        $db = DBManager::get();
        $contact_ids = $db->query(
            "SELECT contact.user_id " .
            "FROM contact " .
            "WHERE owner_id = ".$db->quote($GLOBALS['user']->id)." " .
                "AND buddy = '1' " .
        "")->fetchAll(PDO::FETCH_COLUMN, 0);
        $contact_ids[] = $GLOBALS['user']->id;
        return $contact_ids;
    }

    static public function getMyExternalContacts() {
        $db = DBManager::get();
        $contact_ids = $db->query(
            "SELECT blubber_follower.external_contact_id " .
            "FROM blubber_follower " .
            "WHERE studip_user_id = ".$db->quote($GLOBALS['user']->id)." " .
                "AND left_follows_right = '1' " .
        "")->fetchAll(PDO::FETCH_COLUMN, 0);
        return $contact_ids;
    }

    static public function getThreads($parameter = array()) {
        $defaults = array(
            'seminar_id' => null,
            'user_id' => null,
            'search' => null,
            'stream_time' => false,
            'offset' => 0,
            'limit' => null
        );
        $parameter = array_merge($defaults, $parameter);
        $db = DBManager::get();
        
        $joins = $where_and = $where_or = array();
        $limit = "";
        
        if ($parameter['seminar_id']) {
            $where_and[] = "AND blubber.Seminar_id = ".$db->quote($parameter['seminar_id']);
            if ($parameter['search']) {
                $where_and[] = "AND MATCH (blubber.description) AGAINST (".$db->quote($parameter['search'])." IN BOOLEAN MODE) ";
            }
        }
        if ($parameter['user_id']) {
            $where_and[] = "AND blubber.Seminar_id = ".$db->quote($parameter['user_id']);
            $where_and[] = "AND blubber.context_type = 'public' ";
        }
        if ($parameter['stream_time']) {
            $where_and[] = "AND blubber.mkdate <= ".$db->quote($parameter['stream_time']);
        }
        if ($parameter['limit'] > 0) {
            $limit = "LIMIT ".((int) $parameter['offset']).", ".((int) $parameter['limit']);
        }
        if ($parameter['search'] && !is_array($parameter['search']) && !$parameter['seminar_id']) {
            $where_and[] = "AND MATCH (blubber.description) AGAINST (".$db->quote($parameter['search'])." IN BOOLEAN MODE) ";
        }
        if (!$parameter['seminar_id'] && !$parameter['user_id']) {
            //Globaler Stream:
            $seminar_ids = self::getMyBlubberCourses();
            $where_or[] = "OR (blubber.Seminar_id IS NULL " .
                            (count($seminar_ids) ? "OR blubber.Seminar_id IN (".$db->quote($seminar_ids).") " : "") .
                       ") ";
            $user_ids = self::getMyBlubberBuddys();
            if (count($user_ids)) {
                $where_or[] = "OR (blubber.context_type = 'public' AND blubber.Seminar_id IN (".$db->quote($user_ids).") AND blubber.external_contact = '0') ";
            }
            $user_ids = self::getMyExternalContacts();
            if (count($user_ids)) {
                $where_or[] = "OR (blubber.context_type = 'public' AND blubber.Seminar_id IN (".$db->quote($user_ids).") AND blubber.external_contact = '1') ";
            }
            
            //private Blubber
            $joins[] = "LEFT JOIN blubber_mentions ON (blubber_mentions.topic_id = blubber.root_id) ";
            $where_or[] = "OR (blubber.context_type != 'course' AND blubber_mentions.user_id = ".$db->quote($GLOBALS['user']->id).") ";
            
            if ($parameter['search'] && is_array($parameter['search'])) {
                foreach ((array) $parameter['search'] as $searchword) {
                    $where_or[] = "OR (blubber.Seminar_id = blubber.user_id AND MATCH (blubber.description) AGAINST (".$db->quote($searchword)." IN BOOLEAN MODE) ) ";
                }
            }
        }
        $thread_ids = $db->query(
            "SELECT blubber.root_id " .
            "FROM blubber " .
                implode(" ", $joins) . " " .
            "WHERE 1=1 " .
                implode(" ", $where_and) . " " .
                (count($where_or) ? "AND ( 1=2 " . implode(" ", $where_or) . " ) " : "") .
            "GROUP BY blubber.root_id " .
            "ORDER BY MAX(blubber.mkdate) DESC " .
            $limit." " .
        "")->fetchAll(PDO::FETCH_COLUMN, 0);
        $threads = array();
        foreach ($thread_ids as $thread_id) {
            $threads[] = new BlubberPosting($thread_id);
        }
        return $threads;
    }

    static public function getPostings($parameter = array()) {
        $defaults = array(
            'seminar_id' => null,
            'user_id' => null,
            'thread' => null,
            'search' => array(),
            'since' => null
        );
        $parameter = array_merge($defaults, $parameter);
        $db = DBManager::get();

        $joins = $where = $where_filter = array();
        $limit = "";

        if ($parameter['since'] > 0) {
            $where_and[] = "AND blubber.chdate >= ".$db->quote($parameter['since']);
        }
        if ($parameter['seminar_id']) {
            $where_and[] = "AND blubber.Seminar_id = ".$db->quote($parameter['seminar_id']);
            if ($parameter['search']) {
                $where_and[] = "AND MATCH (blubber.description) AGAINST (".$db->quote($parameter['search'])." IN BOOLEAN MODE) ";
            }
        }
        if ($parameter['user_id']) {
            $where_and[] = "AND blubber.Seminar_id = ".$db->quote($parameter['user_id']);
            $where_and[] = "AND blubber.context_type = 'public' ";
        }
        if ($parameter['thread']) {
            $where_and[] = "AND blubber.root_id = ".$db->quote($parameter['thread']);
        }
        if ($parameter['search'] && !is_array($parameter['search']) && !$parameter['seminar_id']) {
            $where_and[] = "AND MATCH (blubber.description) AGAINST (".$db->quote($parameter['search'])." IN BOOLEAN MODE) ";
        }
        if (!$parameter['seminar_id'] && !$parameter['user_id'] && !$parameter['thread']) {
            //Globaler Stream:
            $seminar_ids = self::getMyBlubberCourses();
            if (count($seminar_ids)) {
                $where_or[] = "OR blubber.Seminar_id IN (".$db->quote($seminar_ids).") ";
            }
            $user_ids = self::getMyBlubberBuddys();
            if (count($user_ids)) {
                //$joins[] = "INNER JOIN blubber AS thread ON (thread.topic_id = blubber.root_id) ";
                $where_or[] = "OR (blubber.context_type = 'public' AND blubber.Seminar_id IN (".$db->quote($user_ids).") AND blubber.external_contact = '0') ";
            }
            $user_ids = self::getMyExternalContacts();
            if (count($user_ids)) {
                //$joins[] = "INNER JOIN blubber AS thread ON (thread.topic_id = blubber.root_id) ";
                $where_or[] = "OR (blubber.context_type = 'public' AND blubber.Seminar_id IN (".$db->quote($user_ids).") AND blubber.external_contact = '1' ) ";
            }
            
            
            //private Blubber
            $where_or[] = "OR (blubber.context_type != 'course' AND blubber_mentions.user_id = ".$db->quote($GLOBALS['user']->id).") ";
            $joins[] = "LEFT JOIN blubber_mentions ON (blubber_mentions.topic_id = blubber.root_id) ";
            
            if ($parameter['search'] && is_array($parameter['search'])) {
                foreach ($parameter['search'] as $searchword) {
                    $where_or[] = "OR (blubber.Seminar_id = blubber.user_id AND MATCH (blubber.description) AGAINST (".$db->quote($searchword)." IN BOOLEAN MODE) ) ";
                }
            }
        }

        $thread_ids = $db->query(
            "SELECT blubber.topic_id " .
            "FROM blubber " .
                implode(" ", $joins) . " " .
            "WHERE 1=1 " .
                implode(" ", $where_and) . " " .
                (count($where_or) ? "AND ( 1=2 " . implode(" ", $where_or) . " ) " : "") .
            "ORDER BY blubber.mkdate ASC " .
        "")->fetchAll(PDO::FETCH_COLUMN, 0);
        $threads = array();
        foreach ($thread_ids as $thread_id) {
            $threads[] = new BlubberPosting($thread_id);
        }
        return $threads;
    }
    
    public function __construct($id = null) {
        $this->additional_fields['discussion_time']['get'] = function($posting) {
            if ($posting['topic_id'] === $posting['root_id']) {
                $db = DBManager::get();
                return $db->query(
                    "SELECT mkdate " .
                    "FROM blubber " .
                    "WHERE root_id = ".$db->quote($posting->getId())." " .
                    "ORDER BY mkdate DESC " .
                    "LIMIT 1 " .
                "")->fetch(PDO::FETCH_COLUMN, 0);
            } else {
                return $posting['mkdate'];
            }
        };
        parent::__construct($id);
    }
    
    public function isThread() {
        return $this['parent_id'] === "0";
    }
	
    public function getChildren() {
        if ($this->isThread()) {
            $db = DBManager::get();
            return self::findBySQL("root_id = ".$db->quote($this->getId())." AND parent_id != '0' ORDER BY mkdate ASC");
        } else {
            return false;
        }
    }
    
    public function delete() {
        $id = $this->getId();
        NotificationCenter::postNotification("PostingWillDelete", $this);
        foreach ((array) self::findBySQL(__class__, "parent_id = ".DBManager::get()->quote($this->getId())) as $child_posting) {
            $child_posting->delete();
        }
        $success = parent::delete();
        if ($success) {
            NotificationCenter::postNotification("PostingHasDeleted", $this);
        }
        DBManager::get()->exec(
            "INSERT INTO blubber_events_queue " .
            "SET event_type = 'delete', " .
                "item_id = ".DBManager::get()->quote($id).", " .
                "mkdate = UNIX_TIMESTAMP() " .
        "");
        return $success;
    }
    
    public function store() {
        NotificationCenter::postNotification("PostingWillSave", $this);
        $success = parent::store();
        if ($success) {
            NotificationCenter::postNotification("PostingHasSaved", $this);
        }
        return $success;
    }
    
    public function isRelated($user_id = null) {
        $user_id or $user_id = $GLOBALS['user']->id;
        $db = DBManager::get();
        return (bool) $db->query(
            "SELECT 1 " .
            "FROM blubber_mentions " .
            "WHERE user_id = ".$db->quote($user_id)." " .
                "AND topic_id = ".$db->quote($this['root_id'])." " .
        "")->fetch(PDO::FETCH_COLUMN, 0);
    }

    public function getRelatedUsers() {
        $db = DBManager::get();
        return (array) $db->query(
            "SELECT blubber_mentions.user_id " .
            "FROM blubber_mentions " .
                "INNER JOIN auth_user_md5 ON (blubber_mentions.user_id = auth_user_md5.user_id) " .
            "WHERE topic_id = ".$db->quote($this['root_id'])." " .
            "ORDER BY auth_user_md5.Nachname ASC, auth_user_md5.Vorname ASC " .
        "")->fetchAll(PDO::FETCH_COLUMN, 0);
    }
    
    /**
     * Returns an object of the author of this posting. This object may be BlubberUser
     * or BlubberExternalContact or any other object that implemenst the 
     * BlubberContact-interface (see there).
     * @return \BlubberContact
     */
    public function getUser() {
        if ($this['external_contact']) {
            $statement = DBManager::get()->prepare(
                "SELECT * FROM blubber_external_contact WHERE external_contact_id = ? " .
            "");
            $statement->execute(array($this['user_id']));
            $data = $statement->fetch(PDO::FETCH_ASSOC);
            if (class_exists($data['contact_type'])) {
                $user = new $data['contact_type']();
                if (is_a($user, "BlubberContact")) {
                    $user->setData($data);
                    return $user;
                }
            }
            $user = new BlubberExternalContact();
            $user->setData($data);
            return $user;
        } else {
            return new BlubberUser($this['user_id']);
        }
    }

}