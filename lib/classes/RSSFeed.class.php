<?php
# Lifter002: TEST
# Lifter007: TEST
# Lifter003: TEST
# Lifter010: DONE - not applicable

/**
 * RSSFeed.class.php - Model of rss feeds
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Jan Kulmann <jankul@tzi.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

define('MAGPIE_CACHE_DIR', $TMP_PATH.'/magpie_cache');
define('MAGPIE_OUTPUT_ENCODING', 'UTF-8');

require_once 'vendor/magpierss/rss_fetch.inc';
require_once 'lib/visual.inc.php';

class RSSFeed
{
    const CONFIG_KEY = 'RSS_FEED_DISPLAY_LIMIT';
    const DEFAULT_LIMIT = 10;

    public $id;
    public $url;
    public $name;
    public $fetch_title = 1;
    public $hidden = 1;
    public $priority = 0;

    /**
     * Constructs an rss feed object. Omit the id to create an empty feed.
     *
     * @param mixed $id Id of the requested rss feed
     */
    public function __construct($id = null)
    {
        if (!$id) {
            return;
        }

        // Check whether the requested feed belongs to the current user
        if (!self::checkAccess($id)) {
            throw new AccessDeniedException(_('Sie haben leider nicht die notwendige Berechtigung für diese Aktion.'));
        }

        $this->id = $id;

        $data = self::load($this->id);
        $this->url         = $data['url'];
        $this->name        = $data['name'];
        $this->fetch_title = $data['fetch_title'];
        $this->hidden      = $data['hidden'];
        $this->priority    = $data['priority'];
    }

    /**
     * Stores feed in database.
     *
     * @return boolean Indicates whether feed was actually stored
     */
    public function store()
    {
        if (!$this->id) {
            $this->id = md5(uniqid('rss-feed', true));
        }

        $query = "INSERT INTO rss_feeds (feed_id, name, url, user_id, priority, fetch_title, hidden, mkdate, chdate)
                  VALUES (?, ?, ?, ?, ?, ?, ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
                  ON DUPLICATE KEY
                    UPDATE name = VALUES(name), url = VALUES(url), priority = VALUES(priority),
                           fetch_title = VALUES(fetch_title), hidden = VALUES(hidden),
                           chdate = UNIX_TIMESTAMP()";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $this->id,
            $this->name,
            $this->url,
            $GLOBALS['user']->id,
            $this->priority ?: 0,
            $this->fetch_title,
            $this->hidden,
        ));

        return $statement->rowCount() > 0;
    }

    /**
     * Deletes the feed from the database.
     *
     * @return boolean Indicates whether the feed was actually deleted
     */
    public function delete()
    {
        if (!$this->id) {
            return true;
        }

        $query = "DELETE FROM rss_feeds WHERE feed_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->id));

        return $statement->rowCount() > 0;
    }

    /**
     * Moves the feed upwards in the list of the user's feeds
     *
     * @return booleean Indicates whether the feed was actually moved
     */
    public function moveUp()
    {
        // Get the previous feed by searching for the closest item with
        // a lesser priority than the current feed
        $query = "SELECT feed_id, priority
                  FROM rss_feeds
                  WHERE priority < ? AND user_id = ?
                  ORDER BY priority DESC";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->priority, $GLOBALS['user']->id));
        $previous = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$previous) {
            // No previous feed, probably already on top
            return false;
        }

        return $this->swap($previous['feed_id'], $previous['priority']);
    }

    /**
     * Moves the feed downwards in the list of the user's feeds
     *
     * @return booleean Indicates whether the feed was actually moved
     */
    public function moveDown()
    {
        // Get the next feed by searching for the closest item with
        // a higher priority than the current feed
        $query = "SELECT feed_id, priority
                  FROM rss_feeds
                  WHERE priority > ? AND user_id = ?
                  ORDER BY priority ASC";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->priority, $GLOBALS['user']->id));
        $next = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$next) {
            // No next feed, probably already at the bottom
            return false;
        }

        return $this->swap($next['feed_id'], $next['priority']);
    }

    /**
     * Swaps the position of the current with the specified other feed by
     * exchanging their priorities.
     *
     * @param  string  $other_id  Id of the feed to swap the current feed with
     * @param  string  $other_id  Priority of the feed to swap the current feed with
     * @return boolean Indicates whether the feeds was actually swapped
     */
    private function swap($other_id, $other_priority)
    {
        $sum = $this->priority + $other_priority;

        $query = "UPDATE rss_feeds
                  SET priority = ? - priority, chdate = UNIX_TIMESTAMP()
                  WHERE feed_id IN (?, ?)";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $sum,
            $this->id,
            $other_id,
        ));

        return $statement->rowCount() == 2;
    }

    /**
     * Renders the contents of the feed
     *
     * @param  int    $limit Maximum number of displayed item, -1 to display all
     * @return string Rendered html output
     */
    public function render($limit = -1)
    {
        $url = TransformInternalLinks($this->url);
        $feed = self::fetch($url);

        if (!$feed) {
            return sprintf(_('Zeitüberschreitung beim Laden von %s...'), $this->url);
        }

        // Trim feed items
        $items = array();
        foreach ($feed->items as $item) {
            $title = trim(studip_utf8decode($item['title']));

            // We don't want no empty titles (or do we?)
            if (empty($title)) {
                continue;
            }

            // Adjust content
            $content = $item['description'] ?: $item['summary'];
            $content = studip_utf8decode($content);
            $content = html_entity_decode($content);
            $content = strip_tags($content);

            $result = array(
                'title'   => $title,
                'content' => my_substr($content, 0, 250),
                'url'     => TransformInternalLinks($item['link']),
            );

            if ($item['enclosure_url']) {
                $result['attachment'] = array(
                    'url'    => $item['enclosure_url'],
                    'type'   => $item['enclosure_type'],
                    'length' => $item['enclosure_length'],
                );
            }
            $items[] = $result;
        }

        // Reduce item's according to limit
        $count = count($items);
        if ($limit > 0 && $count > $limit) {
            $items = array_slice($items, 0, $limit);
        }

        // Parse feed url
        $parsed_url = parse_url($url);
        $internal   = strpos($parsed_url['path'], $GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP']) === 0
                    && in_array($_SERVER['HTTP_HOST'], array(
                        $parsed_url['host'],
                        $parsed_url['host'] . ':' . $parsed_url['port']
                    ));

        // Prepare and render template
        $template = $GLOBALS['template_factory']->open('rss_feed');
        $template->id        = $this->id;
        $template->items     = $items;
        $template->domain    = $parsed_url['host'];
        $template->internal  = $internal;
        $template->truncated = $limit > 0 ? max($count - $limit, 0) : 0;
        $template->url       = $feed->channel['link'];
        return $template->render();
    }

    /**
     * Checks whether the specified feed belongs to the specified user
     *
     * @param string   $feed_id Id of the feed in question
     * @param string   $user_id Id of the user in question, false for current user
     * @return boolean Indicates whether the feed belongs to the user
     */
    public static function checkAccess($feed_id, $user_id = false)
    {
        $query = "SELECT 1
                  FROM rss_feeds
                  WHERE feed_id = ? AND user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $feed_id,
            $user_id ?: $GLOBALS['user']->id
        ));
        return $statement->fetchColumn();
    }

    /**
     * Loads a single feed with the specified id if the id is passed as a
     * string or loads a bundle of feeds if the ids are passed as an array.
     * Feeds can be returned as associative arrays containing the feeds' data
     * or as complete RSSFeed objects.
     * Results are always sorted by the feeds' priorities (not applicable
     * if a single feed is requested).
     *
     * @param mixed   $id           Id(s) of the feed(s) in question
     * @param boolean $return_class Indicates whether arrays or objects should
     *                              be returned
     * @return mixed Array of feeds or single feed resp. empty array or false
     *               if no feeds match the id(s)
     */
    public static function load($id, $return_class = false)
    {
        if (empty($id)) {
            return is_array($id) ? array() : false;
        }

        $query = "SELECT feed_id AS id, url, name, fetch_title, hidden, priority
                  FROM rss_feeds
                  WHERE feed_id IN (?)
                  ORDER BY priority";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($id));
        $result = $return_class
                ? $statement->fetchAll(PDO::FETCH_CLASS, __CLASS__)
                : $statement->fetchGrouped(PDO::FETCH_ASSOC);

        return is_array($id) ? $result : reset($result);
    }

    /**
     * Loads all feeds of the requested user (sorted by priority).
     *
     * @param  string $user_id Id of the user in question.
     * @return array List of the user's feeds as RSSFeed objects
     */
    public static function loadByUserId($user_id)
    {
        $query = "SELECT feed_id
                  FROM rss_feeds
                  WHERE user_id = ? AND name != '' AND url != ''";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user_id));
        $ids = $statement->fetchAll(PDO::FETCH_COLUMN);

        return self::load($ids, true);
    }

    /**
     * Increases the priorities of all the current user's feeds by 1.
     */
    public static function increasePriorities()
    {
        $query = "UPDATE rss_feeds SET priority = priority + 1 WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($GLOBALS['user']->id));
    }

    /**
     * Fetches a feed from the given url using Magpie.
     *
     * @param string  $url     URL of the feed to fetch
     * @param boolean $refresh Indicates whether the cache should be refreshed
     * @return mixed Magpie object representation of the feed or false on error
     */
    public static function fetch($url, $refresh = false)
    {
        if (!$refresh) {
            define('MAGPIE_CACHE_AGE', 1);
        }
        try {
            $result = @fetch_rss($url);
        } catch (Exception $e) {
            $result = false;
        }
        return $result;
    }

    /**
     * Returns the current user's limit of displayed feed items.
     *
     * @return int The current user's limit of displayed feed items
     */
    public static function getLimit()
    {
        $config = UserConfig::get($GLOBALS['user']->id);
        return $config[self::CONFIG_KEY] ?: self::DEFAULT_LIMIT;
    }

    /**
     * Sets the current user's limit of displayed feed items.
     *
     * @param int $limit The current user's limit of displayed feed items
     *                   (pass -1 for no limit)
     */
    public function setLimit($limit)
    {
        $config = UserConfig::get($GLOBALS['user']->id);
        $config->store(self::CONFIG_KEY, $limit);
    }
}