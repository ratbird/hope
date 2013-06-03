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
    //regexp for hashtags
    static public $hashtags_regexp = "(^|\s)#([\w\d_\.\-\?!\+=%]*[\w\d])";

    /**
     * Special format-function that adds hashtags to the common formatReady-markup.
     * @param string $text : original text with studip-markup plus hashtags
     * @return string : formatted text
     */
    static public function format($text) {
        StudipFormat::addStudipMarkup("blubberhashtag", BlubberPosting::$hashtags_regexp, null, "BlubberPosting::markupHashtags");
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
            $user_id = get_userid($username);
            if ($user_id) {
                $user = new BlubberUser($user_id);
            } else {
                $user = BlubberExternalContact::findByEmail($username);
            }
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
        if (!$posting->isNew() && $user->getId() && $user->getId() !== $GLOBALS['user']->id) {
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
        $this->registerCallback('after_store', 'synchronizeHashtags');
        parent::__construct($id);
    }
    
    protected function synchronizeHashtags() {
        if (!$this['root_id'] && !$this['parent_id']) {
            $this['root_id'] = $this->getId();
        }
        $get_old_hashtags = DBManager::get()->prepare(
            "SELECT DISTINCT tag " .
            "FROM blubber_tags " .
            "WHERE blubber_tags.topic_id = :topic_id " .
        "");
        $get_old_hashtags->execute(array('topic_id' => $this['root_id']));
        $old_hashtags = $get_old_hashtags->fetchAll(PDO::FETCH_COLUMN, 0);
        
        $get_current_hashtags = DBManager::get()->prepare(
            "SELECT description " .
            "FROM blubber " .
            "WHERE blubber.root_id = :topic_id " .
        "");
        $get_current_hashtags->execute(array('topic_id' => $this['root_id']));
        $entries = $get_current_hashtags->fetchAll(PDO::FETCH_COLUMN, 0);
        $current_tags = array();
        foreach ($entries as $entry) {
            preg_match_all("/".BlubberPosting::$hashtags_regexp."/", $entry, $hashtags);
            $hashtags = $hashtags[2];
            $current_tags = array_merge($current_tags, $hashtags);
        }
        $delete_tag_statement = DBManager::get()->prepare(
            "DELETE FROM blubber_tags " .
            "WHERE topic_id = :topic_id " .
                "AND tag = :tag " .
        "");
        foreach (array_diff($old_hashtags, $current_tags) as $delete_tag) {
            $delete_tag_statement->execute(array(
                'topic_id' => $this['root_id'],
                'tag' => $delete_tag
            ));
        }
        $insert_statement = DBManager::get()->prepare(
            "INSERT IGNORE INTO blubber_tags " .
            "SET topic_id = :topic_id, " .
                "tag = :tag " .
        "");
        foreach (array_diff($current_tags, $old_hashtags) as $insert_tag) {
            $insert_statement->execute(array(
                'topic_id' => $this['root_id'],
                'tag' => $insert_tag
            ));
        }
            
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
        $root_id = $this['root_id'];
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
        if ($id !== $root_id) {
            $thread = new BlubberPosting($root_id);
            $thread['chdate'] = time();
            $thread->store();
        } else {
            $delete_hashtags = DBManager::get()->prepare(
                "DELETE FROM blubber_tags " .
                "WHERE topic_id = :topic_id " .
            "");
            $delete_hashtags->execute(array('topic_id' => $id));
        }
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
     * Create new unique pk as md5 hash and puts it to field root_id if this is 
     * not a comment.
     * if pk consists of multiple columns, false is returned
     * @return boolean|string
     */
    function getNewId() {
        $id = parent::getNewId();
        if (!$this['root_id']) {
            $this['root_id'] = $id;
        }
        return $id;
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