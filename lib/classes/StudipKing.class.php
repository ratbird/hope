<?php
# Lifter010: TODO

/*
 * Copyright (C) 2004 - Tobias Thelen <tthelen@uos.de>
 * Copyright (C) 2004 - Till Glöggler <tgloeggl@uos.de>
 * Copyright (C) 2009 - Marcus Lunzenauer <mlunzena@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/**
 * This class awards honours (crowns) to users posting a lot in the bulletin
 * boards, writing a lot of wiki pages and so on.
 *
 * @author    mlunzena
 */

class StudipKing {

    /**
     * How many of each type should be awarded?
     */
    const NUM_KINGS = 1;

    /**
     * key to use for caching
     */
    const CACHE_KEY = 'core/kings';

    /**
    * store kings also in memory
    */
    private static $kings;

    /**
     * Returns the awards of a user as an associative array consisting of
     * "award type" => "amount of posts, wiki pages etc." pairs that belong to
     * this user. If the 2nd parameter is set to true, the values are
     * descriptional strings instead of the raw numbers.
     *
     * @param  string     a string containing the MD5ish ID of the user
     * @param  bool       TRUE to return descriptional text, FALSE to return
     *                    raw numbers, which is the default
     *
     * @return array      an associative array mapping the awards to an amount
     */
    static function is_king($user_id, $textual = FALSE)
    {
        $kings = self::get_kings();
        $result = isset($kings[$user_id]) ? $kings[$user_id] : array();
        if ($textual) {
            foreach ($result as $type => $amount) {
                $result[$type] = self::textual_representation($type, $amount);
            }
        }
        return $result;
    }

    private static function get_kings()
    {
        if (self::$kings === null) {
            $cache = StudipCacheFactory::getCache();

            # read cache (unserializing a cache miss - FALSE - does not matter)
            $kings = unserialize($cache->read(self::CACHE_KEY));

            # cache miss, retrieve from database
            if ($kings === FALSE) {
                $kings = self::get_kings_uncached();
                # write to cache with an expiry time of 24 hours
                $cache->write(self::CACHE_KEY, serialize($kings), 86400);
            }
            self::$kings = $kings;
        }
        return self::$kings;
    }

    private static function get_kings_uncached()
    {
        $types = words('files forum guestbook_entry guestbook_writer news voter votes wiki');
        $kings = array();
        foreach ($types as $type) {
            $method = "{$type}_kings";
            foreach (self::$method() as $user_id => $amount) {
                if (!isset($kings[$user_id])) {
                    $kings[$user_id] = array();
                }
                $kings[$user_id][$type] = $amount;
            }
        }
        return $kings;
    }

    private static function select_kings($sql)
    {
        $result = array();
        $stmt = DBManager::get()->query($sql . " ORDER BY num DESC LIMIT 0," . self::NUM_KINGS);
        foreach ($stmt as $row) {
            $result[$row["id"]] = $row["num"];
        }
        return $result;
    }

    private static function wiki_kings()
    {
        return self::select_kings("SELECT user_id AS id, COUNT(*) AS num FROM wiki GROUP BY user_id");
    }

    private static function forum_kings()
    {
        return self::select_kings("SELECT user_id AS id, COUNT(*) AS num FROM px_topics WHERE parent_id != '0' GROUP BY user_id");
    }

    private static function files_kings()
    {
        return self::select_kings("SELECT user_id AS id, COUNT(*) AS num FROM dokumente GROUP BY user_id");
    }

    private static function guestbook_entry_kings()
    {
        return self::select_kings("SELECT range_id AS id, COUNT(*) AS num FROM guestbook GROUP BY range_id");
    }

    private static function guestbook_writer_kings()
    {
        return self::select_kings("SELECT user_id AS id, COUNT(*) AS num FROM guestbook GROUP BY user_id");
    }

    private static function votes_kings()
    {
        return self::select_kings("SELECT author_id AS id, COUNT(*) AS num FROM vote ".
            "LEFT JOIN vote_user ON (vote.vote_id = vote_user.vote_id) ".
            "GROUP BY author_id");
    }

    private static function voter_kings()
    {
        return self::select_kings("SELECT user_id AS id, COUNT(*) AS num FROM vote_user GROUP BY user_id");
    }

    private static function news_kings()
    {
        return self::select_kings("SELECT user_id AS id, COUNT(*) AS num FROM news GROUP BY user_id");
    }

    private static function textual_representation($type, $amount)
    {
        $alt_text = array(
            'files'            => "%d hochgeladene Dateien",
            'forum'            => "%d Forums-Beiträge",
            'wiki'             => "%d Wiki-Beiträge",
            'guestbook_writer' => "%d geschriebene Gästebucheinträge",
            'guestbook_entry'  => "%d bekommene Gästebucheinträge",
            'voter'            => "%d abgegebene Stimmen",
            'votes'            => "%d bekommene Stimmen",
            'news'             => "%d eingestellte Ankündigungen"
        );
        return sprintf(_($alt_text[$type]), $amount);
    }
}

