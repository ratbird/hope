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

/**
 * Model class for blubber-postings derived from SimpleORMap.
 * Also provides a lot of static helper methods for finding and formating postings.
 */
class BlubberPosting extends SimpleORMap {

    //One-time variable that is set right before markup
    static public $course_hashes = false;
    //One-time variable that is set right before markup
    static public $mention_posting_id = false;

    /**
     * Special format-function that adds hashtags to the common formatReady-markup.
     * @param string $text : original text with studip-markup plus hashtags
     * @return string : formatted text
     */
    static public function format($text) {
        StudipFormat::addStudipMarkup("blubberhashtag", "(^|\s)#([\w\d_\.\-]*[\w\d])", null, "BlubberPosting::markupHashtags");
        $output = formatReady($text);
        StudipFormat::removeStudipMarkup("blubberhashtag");
        return $output;
    }

    /**
     * Markup-rule for hashtags. Inserts links to blubber-globalstream for each tag.
     * @param StudipFormat $markup
     * @param array $matches
     * @return string : marked-up text
     */
    static public function markupHashtags($markup, $matches) {
        if (self::$course_hashes) {
            $url = URLHelper::getLink("plugins.php/Blubber/streams/forum", array('hash' => $matches[2], 'cid' => self::$course_hashes));
        } else {
            $url = URLHelper::getLink("plugins.php/Blubber/streams/global", array('hash' => $matches[2]));
        }
        return $matches[1].'<a href="'.$url.'" class="hashtag">#'.$markup->quote($matches[2]).'</a>';
    }

    /**
     * Pre-Markup rule. Recognizes mentions in blubber as @username or @"Firstname lastname"
     * and turns them into usual studip-links. The mentioned person is notified by
     * sending a message to him/her as a side-effect.
     * @param StudipTransformFormat $markup
     * @param array $matches
     * @return string
     */
    static public function mention($markup, $matches) {
        $mention = $matches[0];
        $posting = new BlubberPosting(self::$mention_posting_id);
        $username = stripslashes(substr($mention, 1));
        if ($username[0] !== '"') {
            $user = new BlubberUser(get_userid($username));
        } else {
            $name = substr($username, 1, strlen($username) -2);
            $statement = DBManager::get()->prepare(
                "SELECT user_id FROM auth_user_md5 WHERE CONCAT(Vorname, ' ', Nachname) = :name " .
            "");
            $statement->execute(array('name' => $name));
            $user_id = $statement->fetch(PDO::FETCH_COLUMN, 0);
            if ($user_id) {
                $user = new BlubberUser($user_id);
            } else {
                $statement = DBManager::get()->prepare(
                    "SELECT external_contact_id FROM blubber_external_contact WHERE name = ? " .
                "");
                $statement->execute(array($name));
                $user_id = $statement->fetch(PDO::FETCH_COLUMN, 0);
                $user = BlubberExternalContact::find($user_id);
            }
        }
        if (!$posting->isNew() && $user && $user->getId() !== $GLOBALS['user']->id) {
            $user->mention($posting);
            $statement = DBManager::get()->prepare(
                "INSERT IGNORE INTO blubber_mentions " .
                "SET user_id = :user_id, " .
                    "topic_id = :topic_id, " .
                    "external_contact = :extern, " .
                    "mkdate = UNIX_TIMESTAMP() " .
            "");
            $statement->execute(array(
                'user_id' => $user->getId(),
                'topic_id' => $posting['root_id'],
                'extern' => is_a($user, "BlubberExternalContact") ? 1 : 0
            ));
            return '['.$user->getName().']'.$user->getURL().' ';
        } else {
            return $markup->quote($matches[0]);
        }
    }

    /**
     * Returns an array of all course-IDs of the courses I (the user) participate
     * in and in which blubber-plugin is activated.
     * @return array of Seminar_ids
     */
    static public function getMyBlubberCourses() {
        $statement = DBManager::get()->prepare(
            "SELECT seminar_user.Seminar_id " .
            "FROM seminar_user " .
                "INNER JOIN seminare ON (seminare.Seminar_id = seminar_user.Seminar_id) " .
                "INNER JOIN plugins_activated ON (plugins_activated.poiid = CONCAT('sem', seminar_user.Seminar_id)) " .
                "INNER JOIN plugins ON (plugins_activated.pluginid = plugins.pluginid) " .
            "WHERE seminar_user.user_id = :user_id " .
                "AND plugins_activated.state = 'on' " .
                "AND plugins.pluginclassname = 'Blubber' " .
            "ORDER BY seminare.start_time ASC, seminare.name ASC " .
        "");
        $statement->execute(array('user_id' => $GLOBALS['user']->id));
        return $statement->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /**
     * Returns an array of user_ids of of all buddys I have including myself.
     * @return array of user_ids
     */
    static public function getMyBlubberBuddys() {
        $statement = DBManager::get()->prepare(
            "SELECT contact.user_id " .
            "FROM contact " .
            "WHERE owner_id = :user_id " .
                "AND buddy = '1' " .
        "");
        $statement->execute(array('user_id' => $GLOBALS['user']->id));
        $contact_ids = (array) $statement->fetchAll(PDO::FETCH_COLUMN, 0);
        $contact_ids[] = $GLOBALS['user']->id;
        return $contact_ids;
    }

    /**
     * Returns an array of all user_ids of external contacts I am following.
     * @return array of external_contact_ids
     */
    static public function getMyExternalContacts() {
        $statement = DBManager::get()->prepare(
            "SELECT blubber_follower.external_contact_id " .
            "FROM blubber_follower " .
            "WHERE studip_user_id = :user_id " .
                "AND left_follows_right = '1' " .
        "");
        $statement->execute(array('user_id' => $GLOBALS['user']->id));
        return $statement->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /**
     * Returns some blubber-threads (as BlubberPosting-objects). The parameter
     * "parameter" defines the search as follows:
     * $parameter = array(
     *      'seminar_id' => null|string, //search only in one course?
     *      'user_id' => null|string, //search only for one user, who write the thread-posting
     *      'search' => null|string, //a search-word that must appear in the thread or a comment to that thread
     *      'stream_time' => false|int, //only postings older than the stream_time (as unix-timestamp); this is mostly used for ordering-purposes
     *      'offset' => int, //throw away the first n threads, used for paginating, default 0
     *      'limit' => null //maximum number of threads, so you don't get all 100000 threads at once.
     *  );
     * All parameter are optional. The order of the threads is the date of their
     * latest comment or their own mkdate.
     * @param array $parameter : see above
     * @return array of \BlubberPosting
     */
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
        $sql_params = array();
        
        $joins = $where_and = $where_or = array();
        $limit = "";
        
        if ($parameter['seminar_id']) {
            $where_and[] = "AND blubber.Seminar_id = :range_id ";
            $sql_params['range_id'] = $parameter['seminar_id'];
            if ($parameter['search']) {
                $where_and[] = "AND MATCH (blubber.description) AGAINST (:search IN BOOLEAN MODE) ";
                $sql_params['search'] = $parameter['search'];
            }
        }
        if ($parameter['user_id']) {
            $where_and[] = "AND blubber.Seminar_id = :range_id ";
            $where_and[] = "AND blubber.context_type = 'public' ";
            $sql_params['range_id'] = $parameter['user_id'];
        }
        if ($parameter['stream_time']) {
            $where_and[] = "AND blubber.mkdate <= :stream_time ";
            $sql_params['stream_time'] = $parameter['stream_time'];
        }
        if ($parameter['limit'] > 0) {
            $limit = "LIMIT ".((int) $parameter['offset']).", ".((int) $parameter['limit']);
        }
        if ($parameter['search'] && !is_array($parameter['search']) && !$parameter['seminar_id']) {
            $where_and[] = "AND MATCH (blubber.description) AGAINST (:search IN BOOLEAN MODE) ";
            $sql_params['search'] = $parameter['search'];
        }
        if (!$parameter['seminar_id'] && !$parameter['user_id']) {
            //Globaler Stream:
            $seminar_ids = self::getMyBlubberCourses();
            $where_or[] = "OR (blubber.Seminar_id IS NULL " .
                            (count($seminar_ids) ? "OR blubber.Seminar_id IN (:seminar_ids) " : "") .
                       ") ";
            $sql_params['seminar_ids'] = $seminar_ids;
            $user_ids = self::getMyBlubberBuddys();
            if (count($user_ids)) {
                $where_or[] = "OR (blubber.context_type = 'public' AND blubber.Seminar_id IN (:internal_user_ids) AND blubber.external_contact = '0') ";
                $sql_params['internal_user_ids'] = $user_ids;
            }
            $user_ids = self::getMyExternalContacts();
            if (count($user_ids)) {
                $where_or[] = "OR (blubber.context_type = 'public' AND blubber.Seminar_id IN (:external_user_ids) AND blubber.external_contact = '1') ";
                $sql_params['external_user_ids'] = $user_ids;
            }
            
            //private Blubber
            $joins[] = "LEFT JOIN blubber_mentions ON (blubber_mentions.topic_id = blubber.root_id) ";
            $where_or[] = "OR (blubber.context_type != 'course' AND blubber_mentions.user_id = :me) ";
            $sql_params['me'] = $GLOBALS['user']->id;

            if ($parameter['search'] && is_array($parameter['search'])) {
                foreach ((array) $parameter['search'] as $key => $searchword) {
                    $where_or[] = "OR (blubber.Seminar_id = blubber.user_id AND MATCH (blubber.description) AGAINST (:searchword".($key + 1)." IN BOOLEAN MODE) ) ";
                    $sql_params['searchword'.($key + 1)] = $searchword;
                }
            }
        }
        
        $sql = "SELECT blubber.root_id " .
            "FROM blubber " .
                implode(" ", $joins) . " " .
            "WHERE 1=1 " .
                implode(" ", $where_and) . " " .
                (count($where_or) ? "AND ( 1=2 " . implode(" ", $where_or) . " ) " : "") .
            "GROUP BY blubber.root_id " .
            "ORDER BY MAX(blubber.mkdate) DESC " .
            $limit." ";
        $statement = DBManager::get()->prepare($sql);
        $statement->execute($sql_params);
        $thread_ids = $statement->fetchAll(PDO::FETCH_COLUMN, 0);
        
        $threads = array();
        foreach ($thread_ids as $thread_id) {
            $threads[] = new BlubberPosting($thread_id);
        }
        return $threads;
    }

    /**
     * Returns BlubberPostings for the given search, defined by the
     * parameter "parameter" as follows:
     * $parameter = array(
     *      'seminar_id' => null|string, //search only in one course?
     *      'user_id' => null|string, //search only for one user, who write the thread-posting
     *      'thread' => null|string, //only with root_id = thread
     *      'search' => array/string, //a search-word that must appear in the thread or a comment to that thread
     *      'since' => null|int //only postings newer than the since (as unix-timestamp)
     *  );
     * All parameters are optional. The postings are ordered by their mkdate.
     * @param array $parameter : see above
     * @return array of \BlubberPosting
     */
    static public function getPostings($parameter = array()) {
        $defaults = array(
            'seminar_id' => null,
            'user_id' => null,
            'thread' => null,
            'search' => array(),
            'since' => null
        );
        $parameter = array_merge($defaults, $parameter);
        $sql_params = array();

        $joins = $where = $where_filter = array();
        $limit = "";

        if ($parameter['since'] > 0) {
            $where_and[] = "AND blubber.chdate >= :since ";
            $sql_params['since'] = $parameter['since'];
        }
        if ($parameter['seminar_id']) {
            $where_and[] = "AND blubber.Seminar_id = :seminar_id ";
            $sql_params['seminar_id'] = $parameter['seminar_id'];
            if ($parameter['search']) {
                $where_and[] = "AND MATCH (blubber.description) AGAINST (:search IN BOOLEAN MODE) ";
                $sql_params['search'] = $parameter['search'];
            }
        }
        if ($parameter['user_id']) {
            $where_and[] = "AND blubber.Seminar_id = :user_id ";
            $where_and[] = "AND blubber.context_type = 'public' ";
            $sql_params['user_id'] = $parameter['user_id'];
        }
        if ($parameter['thread']) {
            $where_and[] = "AND blubber.root_id = :thread ";
            $sql_params['thread'] = $parameter['thread'];
        }
        if ($parameter['search'] && !is_array($parameter['search']) && !$parameter['seminar_id']) {
            $where_and[] = "AND MATCH (blubber.description) AGAINST (:search IN BOOLEAN MODE) ";
            $sql_params['search'] = $parameter['search'];
        }
        if (!$parameter['seminar_id'] && !$parameter['user_id'] && !$parameter['thread']) {
            //Globaler Stream:
            $seminar_ids = self::getMyBlubberCourses();
            if (count($seminar_ids)) {
                $where_or[] = "OR blubber.Seminar_id IN (:seminar_ids) ";
                $sql_params['seminar_ids'] = $seminar_ids;
            }
            $user_ids = self::getMyBlubberBuddys();
            if (count($user_ids)) {
                //$joins[] = "INNER JOIN blubber AS thread ON (thread.topic_id = blubber.root_id) ";
                $where_or[] = "OR (blubber.context_type = 'public' AND blubber.Seminar_id IN (:internal_user_ids) AND blubber.external_contact = '0') ";
                $sql_params['internal_user_ids'] = $user_ids;
            }
            $user_ids = self::getMyExternalContacts();
            if (count($user_ids)) {
                $where_or[] = "OR (blubber.context_type = 'public' AND blubber.Seminar_id IN (:external_user_ids) AND blubber.external_contact = '1' ) ";
                $sql_params['external_user_ids'] = $user_ids;
            }
            
            //private Blubber
            $where_or[] = "OR (blubber.context_type != 'course' AND blubber_mentions.user_id = :me) ";
            $joins[] = "LEFT JOIN blubber_mentions ON (blubber_mentions.topic_id = blubber.root_id) ";
            $sql_params['me'] = $GLOBALS['user']->id;
            
            if ($parameter['search'] && is_array($parameter['search'])) {
                foreach ($parameter['search'] as $key => $searchword) {
                    $where_or[] = "OR (blubber.Seminar_id = blubber.user_id AND MATCH (blubber.description) AGAINST (:searchword".($key + 1)." IN BOOLEAN MODE) ) ";
                    $sql_params['searchword'.($key + 1)] = $searchword;
                }
            }
        }

        $sql = "SELECT blubber.topic_id " .
            "FROM blubber " .
                implode(" ", $joins) . " " .
            "WHERE 1=1 " .
                implode(" ", $where_and) . " " .
                (count($where_or) ? "AND ( 1=2 " . implode(" ", $where_or) . " ) " : "") .
            "ORDER BY blubber.mkdate ASC ";
        $statement = DBManager::get()->prepare($sql);
        $statement->execute($sql_params);
        $thread_ids = $statement->fetchAll(PDO::FETCH_COLUMN, 0);

        $threads = array();
        foreach ($thread_ids as $thread_id) {
            $threads[] = new BlubberPosting($thread_id);
        }
        return $threads;
    }

    /**
     * Overrides the contructor of SimpleORMap and is used in the exact same way.
     * Adds a virtual field to the posting giving the time of the newest comment
     * to the thread (or the thread.mkdate if no comment exists).
     * @param null|string $id
     */
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
        $this->db_table = "blubber";
        parent::__construct($id);
    }

    /**
     * returns if this posting is a thread
     * @return bool : true if posting is a thread, false if it's a comment.
     */
    public function isThread() {
        return $this['parent_id'] === "0";
    }

    /**
     * Returns an array of BlubberPostings that are children of this thread, or
     * false if this posting is a comment.
     * @return array|boolean : array of BlubberPosting or false if posting is a comment.
     */
    public function getChildren() {
        if ($this->isThread()) {
            return self::findBySQL("root_id = ? AND parent_id != '0' ORDER BY mkdate ASC", array($this->getId()));
        } else {
            return false;
        }
    }

    /**
     * Deletes this posting and all regarding information.
     * @return integer: 1 if posting successfully deleted, else 0.
     */
    public function delete() {
        $id = $this->getId();
        NotificationCenter::postNotification("PostingWillDelete", $this);
        foreach ((array) self::findBySQL("parent_id = ? ", array($id)) as $child_posting) {
            $child_posting->delete();
        }
        $success = parent::delete();
        if ($success) {
            NotificationCenter::postNotification("PostingHasDeleted", $this);
        }
        //insert into event-queue so it disappears from people's live-stream
        $delete_stmt = DBManager::get()->prepare(
            "INSERT INTO blubber_events_queue " .
            "SET event_type = 'delete', " .
                "item_id = :item_id, " .
                "mkdate = UNIX_TIMESTAMP() " .
        "");
        $delete_stmt->execute(array('item_id' => $id));
        return $success;
    }

    /**
     * Stores the posting into database and fires notifications "PostingWillSave" and "PostingHasSaved"
     * @return integer: 1 if posting successfully stored, else 0. 
     */
    public function store() {
        NotificationCenter::postNotification("PostingWillSave", $this);
        $success = parent::store();
        if ($success) {
            NotificationCenter::postNotification("PostingHasSaved", $this);
        }
        return $success;
    }

    /**
     * Returns if the given user (or the active user) is related to the posting's
     * discussion. He/she is related if he/she is mentioned in the thread or any
     * comment of the thread or it's a private message and visible to the user.
     * @param string/null $user_id : if null the active user is taken
     * @return boolean : true if the user is related, else false.
     */
    public function isRelated($user_id = null) {
        $user_id or $user_id = $GLOBALS['user']->id;
        $statement = DBManager::get()->prepare(
            "SELECT 1 " .
            "FROM blubber_mentions " .
            "WHERE user_id = :user_id " .
                "AND topic_id = :topic_id " .
        "");
        $statement->execute(array('user_id' => $user_id, 'topic_id' => $this['root_id']));
        return (bool) $statement->fetch(PDO::FETCH_COLUMN, 0);
    }

    /**
     * Returns all user_ids of related users to the posting - all who are
     * mentioned or to who the private blubber is visible.
     * @return boolean
     */
    public function getRelatedUsers() {
        $statement = DBManager::get()->prepare(
            "SELECT blubber_mentions.user_id " .
            "FROM blubber_mentions " .
                "INNER JOIN auth_user_md5 ON (blubber_mentions.user_id = auth_user_md5.user_id) " .
            "WHERE topic_id = :topic_id " .
            "ORDER BY auth_user_md5.Nachname ASC, auth_user_md5.Vorname ASC " .
        "");
        $statement->execute(array('topic_id' => $this['root_id']));
        return (array) $statement->fetchAll(PDO::FETCH_COLUMN, 0);
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