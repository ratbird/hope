<?php
/*
 *  Copyright (c) 2013  Rasmus Fuhse <fuhse@data-quest.de>
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

require_once dirname(__file__)."/StreamAvatar.class.php";

/**
 * A class to fetch blubber-postings from the database and return an array of
 * BlubberPosting-objects.
 * 
 * Such a stream is defined by some pool-variables and some filter-variables.
 * The pool at first defines the set of overall blubber-postings that should
 * be taken and the filter filters this set by it's own parameters.
 * 
 * For example a profile-stream of a user takes all blubber from the user in the
 * pool and the filter is defined in a way to reduce this set to all public postings.
 * 
 */
class BlubberStream extends SimpleORMap {

    public $filter_threads = array();
    
    static public function create($pool = array(), $filter = array()) {
        $stream = new BlubberStream();
        foreach ($pool as $key => $pool_variable) {
            $stream['pool_'.$key] = $pool_variable;
        }
        foreach ($filter as $key => $filter_variable) {
            $stream['filter_'.$key] = $filter_variable;
        }
        return $stream;
    }
    
    static public function findMine($user_id = null) {
        $user_id OR $user_id = $GLOBALS['user']->id;
        return self::findBySQL(
            "user_id = ? ORDER BY name ASC ",
            array($user_id)
        );
    }
    
    static public function getGlobalStream() {
        $stream = new BlubberStream();
        $stream['pool_courses'] = array("all");
        $stream['pool_groups'] = array("all");
        $stream['sort'] = "activity";
        return $stream;
    }
    
    static public function getCourseStream($course_id) {
        $stream = new BlubberStream();
        $stream['pool_courses'] = array($course_id);
        $stream['sort'] = "activity";
        return $stream;
    }

    static public function getProfileStream($user_id) {
        $stream = new BlubberStream();
        $stream['filter_users'] = array($user_id);
        $stream['filter_type'] = array("public");
        $stream['sort'] = "age";
        return $stream;
    }

    static public function getThreadStream($topic_id) {
        $stream = new BlubberStream();
        $stream->filter_threads = array($topic_id);
        return $stream;
    }

    public function __construct($id = null) {
        $this->db_table = "blubber_streams";
        $this->registerCallback('before_store', 'serializeData');
        $this->registerCallback('after_store after_initialize', 'unserializeData');
        parent::__construct($id);
    }
    
    /**
     * Serializes $this->data so it is saves as string in the database.
     * @return boolean
     */
    protected function serializeData()
    {
        $this->content['pool_courses'] = implode(",", (array) $this->content['pool_courses']);
        $this->content['pool_groups'] = implode(",", (array) $this->content['pool_groups']);
        $this->content['pool_hashtags'] = implode(" ", (array) $this->content['pool_hashtags']);
        $this->content['filter_type'] = implode(",", (array) $this->content['filter_type']);
        $this->content['filter_courses'] = implode(",", (array) $this->content['filter_courses']);
        $this->content['filter_groups'] = implode(",", (array) $this->content['filter_groups']);
        $this->content['filter_users'] = implode(",", (array) $this->content['filter_users']);
        $this->content['filter_hashtags'] = implode(" ", (array) $this->content['filter_hashtags']);
        $this->content['filter_nohashtags'] = implode(" ", (array) $this->content['filter_nohashtags']);
        return true;
    }

    /**
     * Unserializes $this->data so it can be used as an array or something else.
     * @return boolean
     */
    protected function unserializeData()
    {
        $this->content['pool_courses'] = $this->content['pool_courses']
                ? explode(",", $this->content['pool_courses'])
                : array();
        $this->content['pool_groups'] = $this->content['pool_groups'] 
                ? explode(",", $this->content['pool_groups'])
                : array();
        $this->content['pool_hashtags'] = $this->content['pool_hashtags'] 
                ? explode(" ", $this->content['pool_hashtags'])
                : array();
        
        $this->content['filter_type'] = $this->content['filter_type']
                ? explode(",", $this->content['filter_type'])
                : array();
        $this->content['filter_courses'] = $this->content['filter_courses']
                ? explode(",", $this->content['filter_courses'])
                : array();
        $this->content['filter_groups'] = $this->content['filter_groups'] 
                ? explode(",", $this->content['filter_groups'])
                : array();
        $this->content['filter_users'] = $this->content['filter_users'] 
                ? explode(",", $this->content['filter_users'])
                : array();
        $this->content['filter_hashtags'] = $this->content['filter_hashtags'] 
                ? explode(" ", $this->content['filter_hashtags'])
                : array();
        $this->content['filter_nohashtags'] = $this->content['filter_nohashtags'] 
                ? explode(" ", $this->content['filter_nohashtags'])
                : array();
        return true;
    }
    
    public function fetchThreads($offset = 0, $limit = null, $stream_time = null) {
        list($sql, $parameters) = $this->getThreadsSql($offset, $limit, $stream_time);
        $statement = DBManager::get()->prepare($sql);
        $statement->execute($parameters);
        $posting_data = $statement->fetchAll(PDO::FETCH_ASSOC);
        $postings = array();
        foreach ($posting_data as $data) {
            $posting = new BlubberPosting();
            $posting->setData($data);
            $posting->setNew(false);
            $postings[] = $posting;
        }
        return $postings;
    }
    
    public function fetchNumberOfThreads() {
        list($sql, $parameters) = $this->getThreadsSql();
        $statement = DBManager::get()->prepare(
            "SELECT COUNT(*) " .
            "FROM (".$sql.") AS threads " .
        "");
        $statement->execute($parameters);
        return $statement->fetch(PDO::FETCH_COLUMN, 0);
    }
    
    public function fetchNewPostings($since) {
        list($sql, $parameters) = $this->getNewPostingsSql($since);
        $statement = DBManager::get()->prepare($sql);
        $statement->execute($parameters);
        $posting_data = $statement->fetchAll(PDO::FETCH_ASSOC);
        $postings = array();
        foreach ($posting_data as $data) {
            $posting = new BlubberPosting();
            $posting->setData($data);
            $posting->setNew(false);
            $postings[] = $posting;
        }
        return $postings;
    }
    
    protected function getThreadsSql($offset = 0, $limit = null, $stream_time = null) {
        list($pool_sql, $filter_sql, $parameters) = $this->getSqlParts();
        if ($stream_time !== null) {
            $filter_sql[] = "blubber.mkdate <= :stream_time";
            $parameters['stream_time'] = $stream_time;
        }
        
        $sql = 
            "SELECT blubber.* " .
            "FROM blubber " .
                "INNER JOIN blubber AS comment ON (comment.root_id = blubber.topic_id) " .
                "LEFT JOIN blubber_mentions ON (blubber_mentions.topic_id = blubber.topic_id) " .
                "LEFT JOIN blubber_tags ON (blubber_tags.topic_id = blubber.topic_id) " .
            "WHERE " .
                //pool
                (count($pool_sql) ? "(1 != 1 OR ".implode(" OR ", $pool_sql).") " : "") .
                (count($pool_sql) && count($filter_sql) ? " AND " : "") .
                //filter
                (count($filter_sql) ? implode(" AND ", $filter_sql)." " : "") .
            "GROUP BY blubber.topic_id " .
            "ORDER BY " . ($this['sort'] === "activity" ? "MAX(comment.mkdate) DESC" : "blubber.mkdate DESC")." " .
            (($offset or $limit) ? "LIMIT ".(int) $offset.", ".(int) $limit." " : " ") .
        "";
        return array($sql, $parameters);
    }
    
    protected function getNewPostingsSql($since) {
        list($pool_sql, $filter_sql, $parameters) = $this->getSqlParts();
        $filter_sql[] = "comment.chdate > :since ";
        $parameters['since'] = $since;
        
        $sql = 
            "SELECT comment.* " .
            "FROM blubber " .
                "INNER JOIN blubber AS comment ON (comment.root_id = blubber.topic_id) " .
                "LEFT JOIN blubber_mentions ON (blubber_mentions.topic_id = blubber.topic_id) " .
                "LEFT JOIN blubber_tags ON (blubber_tags.topic_id = blubber.topic_id) " .
            "WHERE " .
                //pool
                (count($pool_sql) ? "(1 != 1 OR ".implode(" OR ", $pool_sql).") " : "") .
                (count($pool_sql) && count($filter_sql) ? " AND " : "") .
                //filter
                (count($filter_sql) ? implode(" AND ", $filter_sql)." " : "") .
            "ORDER BY comment.chdate " .
        "";
        return array($sql, $parameters);
    }
    
    protected function getSqlParts() {
        $pool_sql = array();
        $filter_sql = array();
        $parameters = array();
        
        if (count($this['pool_courses'])) {
            $pool_courses = $this->getCourses($this['pool_courses']);
            if (count($pool_courses)) {
                $pool_sql[] = "(blubber.Seminar_id IN (:pool_courses) AND blubber.context_type = 'course')";
                $parameters['pool_courses'] = $pool_courses;
            }
        }
        if (count($this['pool_groups'])) {
            $pool_users = $this->getUsersByGroups($this['pool_groups']);
            if (count($pool_users)) {
                $pool_users[] = $GLOBALS['user']->id;
                $pool_sql[] = "blubber.user_id IN (:pool_users)";
                $parameters['pool_users'] = $pool_users;
            }
        }
        if (count($this['pool_hashtags']) > 0) {
            $pool_sql[] = "(blubber_tags.tag IN (:pool_hashtags))";
            $parameters['pool_hashtags'] = $this['pool_hashtags'];
        }
        
        // Rights to see the blubber-postings:
        $parameters['seminar_ids'] = $this->getMyCourses();
        $filter_sql[] = "(blubber.context_type = 'public' " .
                                "OR (blubber.context_type = 'private' AND blubber_mentions.user_id = :me) " .
                                (count($parameters['seminar_ids']) ? "OR (blubber.context_type = 'course' AND blubber.Seminar_id IN (:seminar_ids)) " : "") .
                            ")";
        $parameters['me'] = $GLOBALS['user']->id;
        
        if (count($this['filter_type']) > 0) {
            $filter_sql[] = "blubber.context_type IN (:filter_type)";
            $parameters['filter_type'] = $this['filter_type'];
        }
        if (count($this['filter_courses']) > 0) {
            $filter_courses = $this->getCourses($this['filter_courses']);
            if (count($filter_courses)) {
                $filter_sql[] = "(blubber.Seminar_id IN (:filter_courses) AND blubber.context_type = 'course')";
                $parameters['filter_courses'] = $filter_courses;
            } else {
                $filter_sql[] = "1 != 1";
            }
        }
        if (count($this['filter_groups']) > 0 or count($this['filter_users']) > 0) {
            $filter_users = array();
            if (count($this['filter_groups']) > 0) {
                $filter_users = $this->getUsersByGroups($this['filter_groups']);
                $filter_users[] = $GLOBALS['user']->id;
            }
            if (count($this['filter_users']) > 0) {
                $filter_users = $filter_users + $this['filter_users'];
                $filter_users = array_unique($filter_users);
            }
            $filter_sql[] = "blubber.user_id IN (:filter_users)";
            $parameters['filter_users'] = $filter_users;
        }
        if (count($this['filter_hashtags']) > 0) {
            $filter_sql[] = "(blubber_tags.tag IN (:filter_hashtags))";
            $parameters['filter_hashtags'] = $this['filter_hashtags'];
        }
        if (count($this['filter_nohashtags']) > 0) {
            $filter_sql[] = "( 0 = (SELECT COUNT(*) FROM blubber_tags AS tags WHERE tags.topic_id = blubber.topic_id AND tag IN (:filter_nohashtags) ) )";
            $parameters['filter_nohashtags'] = $this['filter_nohashtags'];
        }
        if (count($this->filter_threads) > 0) {
            $filter_sql[] = "blubber.topic_id IN (:filter_threads) ";
            $parameters['filter_threads'] = $this->filter_threads;
        }
        return array($pool_sql, $filter_sql, $parameters);
    }
    
    protected function getUsersByGroups($groups) {
        if ($groups[0] === "all") {
            $statement = DBManager::get()->prepare(
                "SELECT DISTINCT user_id " .
                "FROM contact " .
                "WHERE owner_id = :me " .
            "");
            $statement->execute(array('me' => $GLOBALS['user']->id));
            return $statement->fetchAll(PDO::FETCH_COLUMN, 0);
        } elseif(count($groups)) {
            $statement = DBManager::get()->prepare(
                "SELECT DISTINCT statusgruppe_user.user_id " .
                "FROM statusgruppe_user " .
                    "INNER JOIN statusgruppen ON (statusgruppen.statusgruppe_id = statusgruppe_user.statusgruppe_id) " .
                "WHERE statusgruppen.range_id = :me " .
                    "AND statusgruppen.statusgruppe_id IN (:groups) " .
            "");
            $statement->execute(array(
                'me' => $GLOBALS['user']->id,
                'groups' => $groups
            ));
            return $statement->fetchAll(PDO::FETCH_COLUMN, 0);
        }
    }
    
    protected function getCourses($courses) {
        $mycourses = $this->getMyCourses();
        if ($courses[0] === "all") {
            return $mycourses;
        } else {
            return array_intersect($courses, $mycourses);
        }
    }
    
    protected function getMyCourses() {
        $mandatory_classes = array();
        $standard_classes = array();
        $forbidden_classes = array();
        $mandatory_types = array();
        $standard_types = array();
        $forbidden_types = array();
        foreach (SemClass::getClasses() as $key => $class) {
            if ($class->isModuleMandatory("Blubber")) {
                $mandatory_classes[] = $key;
            }
            if ($class->isSlotModule("Blubber")) {
                $standard_classes[] = $key;
            }
            if (!$class->isModuleAllowed("Blubber")) {
                $forbidden_classes[] = $key;
            }
        }
        foreach (SemType::getTypes() as $key => $type) {
            if (in_array($type['class'], $mandatory_classes)) {
                $mandatory_types[] = $key;
            }
            if (in_array($type['class'], $standard_classes)) {
                $standard_types[] = $key;
            }
            if (in_array($type['class'], $forbidden_classes)) {
                $forbidden_types[] = $key;
            }
        }
        $statement = DBManager::get()->prepare(
            "SELECT seminare.Seminar_id " .
            "FROM seminar_user " .
                "INNER JOIN seminare ON (seminare.Seminar_id = seminar_user.Seminar_id) " .
                "LEFT JOIN plugins_activated ON (plugins_activated.poiid = CONCAT('sem', seminare.Seminar_id)) " .
            "WHERE user_id = :me " .
                "AND (".
                    "seminare.status IN (:mandatory_types) " .
                    "OR (plugins_activated.state = 'on') " .
                    "OR (seminare.status IN (:standard_types) AND plugins_activated.state IS NULL) " .
                ") " .
                "AND seminare.status NOT IN (:forbidden_types) " .
        "");
        $parameter = array('me' => $GLOBALS['user']->id);
        $parameter['mandatory_types'] = count($mandatory_types) ? $mandatory_types : null;
        $parameter['standard_types'] = count($standard_types) ? $standard_types : null;
        $parameter['forbidden_types'] = count($forbidden_types) ? $forbidden_types : null;
        $statement->execute($parameter);
        return $statement->fetchAll(PDO::FETCH_COLUMN, 0);
    }
    
}