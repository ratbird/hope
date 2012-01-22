<?php
require_once 'lib/classes/SmileyFormat.php';

/**
 * smiley.php - model class for a smiley
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @category    Stud.IP
 * @package     admin
 * @since       2.3
 *
 * @uses        DBManager
 * @uses        SmileyFavorites
 * @uses        SmileyFormat
 */
class Smiley
{
    const FETCH_ALL = 0;
    const FETCH_ID  = 1;

    public $id          = null;
    public $name        = '';
    public $width       = 0;
    public $height      = 0;
    public $short       = '';
    public $count       = 0;
    public $short_count = 0;
    public $fav_count   = 0;
    public $mkdate      = null;
    public $chdate      = null;

    /**
     * Returns the absolute filename of a smiley.
     * 
     * @param  mixed  $name Smiley name, defaults to current smiley's name
     * @return String Absolute filename
     */
    function getFilename($name = null)
    {
        return sprintf('%s/smile/%s.gif', realpath($GLOBALS['DYNAMIC_CONTENT_PATH']), $name ?: $this->name);
    }

    /**
     * Returns the url of a smiley.
     * 
     * @param  mixed  $name Smiley name, defaults to current smiley's name
     * @return String URL
     */
    function getURL($name = null)
    {
        return sprintf('%s/smile/%s.gif', $GLOBALS['DYNAMIC_CONTENT_URL'], urlencode($name ?: $this->name));
    }

    /**
     * Returns the HTML image tag of the smiley
     * 
     * @param  mixed  $tooltip Tooltip to display for this smiley, defaults to
     *                         smiley's name
     * @return String HTML image tag
     */
    function getImageTag($tooltip = null)
    {
        return sprintf('<img src="%s" alt="%s" title="%s" width="%u" height="%u">',
                       $this->getURL(), htmlReady($this->name), htmlReady($tooltip ?: $this->name),
                       $this->width, $this->height);
    }

    /**
     * Returns the smiley object with the given id. If no such object is
     * available, an empty object is returned.
     * 
     * @param  int    $id Id of the smiley to load
     * @return Smiley Smiley object
     */
    static function getById($id)
    {
        $result = self::getByIds($id);
        return reset($result) ?: new self;
    }

    /**
     * Returns a collection smiley objects with the given ids.
     * 
     * @param  mixed $ids Ids of the smileys to load, also accepts an atomic id
     * @return Array Array of Smiley objects
     */
    static function getByIds($ids)
    {
        if (empty($ids)) {
            return array();
        }
        $query = "SELECT smiley_id AS id, smiley_name AS name, smiley_width AS width, smiley_height AS height, "
               . " short_name AS short, smiley_counter AS `count`, short_counter AS short_count, "
               . " fav_counter AS fav_count, mkdate, chdate "
               . " FROM smiley WHERE smiley_id IN (?)";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($ids));
        return $statement->fetchAll(PDO::FETCH_CLASS, 'Smiley');
    }

    /**
     * Returns the smiley object with the given name. If no such object is
     * available, an empty object is returned 
     * 
     * @param  String $name Name of the smiley to load
     * @return Smiley Smiley object
     */
    static function getByName($name)
    {
        $query = "SELECT smiley_id AS id, smiley_name AS name, smiley_width AS width, smiley_height AS height, "
               . " short_name AS short, smiley_counter AS `count`, short_counter AS short_count, "
               . " fav_counter AS fav_count, mkdate, chdate "
               . " FROM smiley WHERE smiley_name = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($name));
        return $statement->fetchObject('Smiley') ?: new self;
    }

    /**
     * Returns the smiley object with the given short notation. If no such
     * object is available, an empty object is returned 
     * 
     * @param  String $short Short notation of the smiley to load
     * @return Smiley Smiley object
     */
    static function getByShort($short)
    {
        $query = "SELECT smiley_id AS id, smiley_name AS name, smiley_width AS width, smiley_height AS height, "
               . " short_name AS short, smiley_counter AS `count`, short_counter AS short_count, "
               . " fav_counter AS fav_count, mkdate, chdate "
               . " FROM smiley WHERE short_name = ? AND short_name != ''";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($short));
        return $statement->fetchObject('Smiley') ?: new self;
    }

    /**
     * Removes a smiley or a collection of smileys from the database.
     * 
     * @param  mixed  $id Id(s) to delete, accepts either an atomic id or an 
     *                    array of ids
     */
    static function Remove($id)
    {
        if (!empty($id)) {
            foreach (self::getByIds($id) as $smiley) {
                unlink($smiley->getFilename());
            }
            DBManager::get()
                ->prepare("DELETE FROM smiley WHERE smiley_id IN (?)")
                ->execute(array($id));
        }
    }

    /**
     * Stores the current smiley to database. 
     */
    function store()
    {
        $query = "INSERT INTO smiley "
               . "(smiley_id, smiley_name, smiley_width, smiley_height, short_name, "
               . " smiley_counter, short_counter, fav_counter, mkdate, chdate) "
               . "VALUES (?, ?, ?, ?, ?, ?, ?, ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()) "
               . "ON DUPLICATE KEY "
               . "UPDATE smiley_name = VALUES(smiley_name), smiley_width = VALUES(smiley_width), "
               . " smiley_height = VALUES(smiley_height), short_name = VALUES(short_name), "
               . " smiley_counter = VALUES(smiley_counter), short_counter = VALUES(short_counter), "
               . " fav_counter = VALUES(fav_counter), chdate = VALUES(chdate)";
        DBManager::get()
            ->prepare($query)
            ->execute(array(
               $this->id, $this->name, $this->width, $this->height, $this->short,
               $this->count, $this->short_count, $this->fav_count
            ));
        if (empty($this->id)) {
            $this->id = DBManager::get()->lastInsertId();
        }
    }

    /**
     * Renames the smiley to the given new name.
     *
     * @param String $new_name New name of the smiley
     * @return bool  true if smiley was renamed successfully, false otherwise
     */
    function rename($new_name)
    {
        $old_file = $this->getFilename();
        $new_file = $this->getFilename($new_name);

        if (!rename($old_file, $new_file)) {
            return false;
        }

        $this->name = $new_name;
        $this->store();

        return true;
    }

    /**
     * Deletes the smiley.
     */
    function delete()
    {
        if ($this->id) {
            self::Remove($this->id);

            $this->id          = null;
            $this->name        = '';
            $this->width       = 0;
            $this->height      = 0;
            $this->short       = '';
            $this->count       = 0;
            $this->short_count = 0;
            $this->fav_count   = 0;
            $this->mkdate      = null;
            $this->chdate      = null;
        }
    }

    /**
     * Generates the neccessary sql query to load the given group's items.
     *
     * @param  String $group Group to load
     * @return String SQL query to load the given group's items
     */
    private static function groupQuery($group)
    {
        $query = "SELECT smiley_id AS id, smiley_name AS name, smiley_width AS width, smiley_height AS height, "
               . " short_name AS short, smiley_counter AS `count`, short_counter AS short_count, "
               . " fav_counter AS fav_count, mkdate, chdate FROM smiley ";
        switch ($group) {
            case 'all':
                $query .= "ORDER BY smiley_name";
                break;
            case 'top20':
                $query .= "WHERE smiley_counter > 0 OR short_counter > 0 "
                       .  "ORDER BY smiley_counter + short_counter DESC, smiley_name ASC "
                       .  "LIMIT 20";
                break;
            case 'used':
                $query .= "WHERE smiley_counter > 0 OR short_counter > 0 "
                       .  "ORDER BY smiley_counter + short_counter DESC, smiley_name ASC";
                break;
            case 'none':
                $query .= "WHERE smiley_counter=0 AND short_counter=0 ORDER BY smiley_name";
                break;
            case 'short':
                $query .= "WHERE short_name != '' ORDER BY smiley_name";
                break;
            default:
                $query .= sprintf("WHERE smiley_name LIKE CONCAT(%s, '%%') ORDER BY smiley_name",
                                  DBManager::get()->quote($group));
                break;
        }
        return $query;
    }

    /**
     * Loads a given group from the database.
     *
     * @param  String $group Group to load, defaults to 'all'
     * @param  int    $mode  Fetch mode
     *                      - FETCH_ALL to return actual Smiley objects
     *                      - FETCH_ID  to return the group's items' ids
     * @return Array Either the objects or the ids of the group's items
     */
    static function getGrouped($group = 'all', $mode = self::FETCH_ALL)
    {
        $result = DBManager::get()
            ->query(self::groupQuery($group))
            ->fetchAll(PDO::FETCH_CLASS, 'Smiley');

        if ($mode & self::FETCH_ID) {
            $result = array_map(function ($item) { return $item->id; }, $result);
        }
        return $result;
    }

    /**
     * Returns an ordered unique list of the first characters of all smileys.
     *
     * @return Array Ordered list of all first characters
     */
    static function getFirstUsedCharacter()
    {
        return DBManager::get()
            ->query("SELECT LEFT(smiley_name, 1) FROM smiley ORDER BY smiley_name")
            ->fetchColumn();
    }

    /**
     * Returns an associative list of the first characters of all smileys
     * and their according quantity.
     *
     * @return Array Associative list with character as key and quantity as
     *               value
     */
    static function getUsedCharacters()
    {
        $query = "SELECT LEFT(smiley_name, 1), COUNT(smiley_name) "
               . "FROM smiley GROUP BY LEFT(smiley_name, 1)";
        // TODO workaround since query does not return a StudipPDOStatement
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array());
        return $statement->fetchGrouped(PDO::FETCH_COLUMN);
    }

    /**
     * Returns a list of all available short notations.
     *
     * @return Array Associative list with short notation as key and smiley
     *               name as value
     */
    static function getShort()
    {
        $query = "SELECT short_name, smiley_name FROM smiley WHERE short_name != ''";
        // TODO workaround since query does not return a StudipPDOStatement
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array());
        return $statement->fetchGrouped(PDO::FETCH_COLUMN);
    }

    /**
     * Returns some statistics about the smiley database.
     *
     * @return Array 4 numbers: available, used, occurences and last change
     */
    static function getStatistics()
    {
        $query = "SELECT COUNT(*) AS count_all, "
               . " SUM(smiley_counter + short_counter > 0) AS count_used, "
               . " SUM(smiley_counter + short_counter) AS `sum`, "
               . " MAX(chdate) AS last_change "
               . "FROM smiley";
        return DBManager::get()
            ->query($query)
            ->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Searches the database for occurences of smileys and returns a list
     * of how often each smiley was used.
     * If smiley favorites are activated, the list will include the number
     * how often a smiley was favored.
     *
     * @return Array Associative array with smiley name as key and according
     *               usage numbers as value
     */
    static function getUsage()
    {
        // Tabellen, die nach Smileys durchsucht werden sollen
        // Format: array( array (Tabelle, Feld), array (Tabelle, Feld), ... )
        $table_data = array(
            array('guestbook', 'content'),
            array('datafields_entries','content'),
            array('kategorien', 'content'),
            array('message', 'message'),
            array('news', 'body'),
            array('scm', 'content'),
            array('user_info', 'hobby'),
            array('user_info', 'lebenslauf'),
            array('user_info', 'publi'),
            array('user_info', 'schwerp'),
            array('px_topics', 'description'),
            array('wiki', 'body')
        );

        // search in all tables
        $usage = array();
        foreach ($table_data as $table) {
            $query = "SELECT ? AS txt FROM ?"; // $table1, $table0
            if ($table[0] == 'wiki') {  // only the actual wiki page ...
                $sqltxt = "SELECT MAX(CONCAT(LPAD(version, 5, '0'),' ', ?)) AS txt FROM ? GROUP BY range_id, keyword";
            }
            $statement = DBManager::get()->prepare($query);
            $statement->bindParam(1, $table[1], StudipPDO::PARAM_COLUMN);
            $statement->bindParam(2, $table[0], StudipPDO::PARAM_COLUMN);
            $statement->execute(array());
            // and all entrys
            while ($txt = $statement->fetchColumn()) {
                // extract all smileys
                if (preg_match_all(SmileyFormat::REGEXP, $txt, $matches)) {
                    for ($k = 0; $k < count($matches[1]); $k++) {
                        $name = $matches[1][$k];
                        if (!isset($usage[$name])) {
                            $usage[$name] = array('count' => 0, 'short_count' => 0, 'favorites' => 0);
                        }
                        $usage[$name]['count'] += 1;
                    }
                }
                // and now the short-notation
                foreach (self::getShort() as $code => $name) {
                    $regexp = '/(\>|^|\s)' . preg_quote($code) . '(?=$|\<|\s)/';
                    if ($count = preg_match_all($regexp, $txt, $matches)) {
                        if (!isset($usage[$name])) {
                            $usage[$name] = array('count' => 0, 'short_count' => 0, 'favorites' => 0);
                        }
                        $usage[$name]['short_count'] += $count;
                    }
                }
            }
        }

        // favorites
        if (SmileyFavorites::isEnabled()) {
            $favorite_usage = SmileyFavorites::getUsage();
            foreach ($favorite_usage as $name => $count) {
                if (!isset($usage[$name])) {
                    $usage[$name] = array('count' => 0, 'short_count' => 0, 'favorites' => 0);
                }
                $usage[$name]['favorites'] = $count;
            }
        }

        return $usage;
    }

    /**
     * Refreshes the database with current usage numbers.
     *
     * @return int Number of changed objects
     */
    static function updateUsage()
    {
        $usage = self::getUsage();
        $smileys = self::getGrouped('all');
        $changed = 0;

        foreach ($smileys as $smiley) {
            $updated = $usage[$smiley->name];
            if (!isset($updated) 
                && $smiley->count + $smiley->short_count + $smiley->fav_count > 0)
            {
                $smiley->count       = 0;
                $smiley->short_count = 0;
                $smiley->fav_count   = 0;
            } else if ($smiley->count + $smiley->short_count + $smiley->fav_count
                       != $updated['count'] + $updated['short_count'] + $updated['favorites']) {
                $smiley->count       = $updated['count'];
                $smiley->short_count = $updated['short_count'];
                $smiley->fav_count   = $updated['favorites'];
            } else {
                continue;
            }
            $smiley->store();
            $changed++;
        }

        return $changed;
    }

    /**
     * Synchronizes the smileys' file system or an atomic file with the
     * database.
     * The smiley directory is scanned for new, changed or missing files.
     * Any difference will change the database's record.
     *
     * This method is also used for uploading new smileys. Provide an
     * absolute filename of a smiley and it will either be imported into
     * the database or the database will be adjusted to the current file's
     * dimensions.
     *
     * @param mixed $smiley_file If no filename is provided, the whole file
     *                           system is refreshed
     * @return Array Numbers: inserted, updated, removed (, favorites adjusted)
     */
    static function refresh($smiley_file = null)
    {
        $counts = array(
            'insert'    => 0,
            'update'    => 0
        );

        if ($filename === null) {
            $files = glob(self::getFilename('*'));
        } else {
            $files = array($smiley_file);
        }

        foreach ($files as $file) {
            $image_info = getimagesize($file);
            if ($image_info[2] !== IMAGETYPE_GIF) {
                continue;
            }

            $name = basename($file, '.gif');
            $smiley = Smiley::getByName($name);

            $update = false;
            if (!$smiley->id) {
                $smiley->name  = $name;
                $smiley->short = array_search($name, $GLOBALS['SMILE_SHORT']) ?: '';
                $smiley->width  = $image_info[0];
                $smiley->height = $image_info[1];

                $update = true;
                $counts['insert'] += 1;
            } else if ($smiley->width + $smiley->height != $image_info[0] + $image_info[1]) {
                $smiley->width  = $image_info[0];
                $smiley->height = $image_info[1];

                $update = true;
                $counts['update'] += 1;
            }

            if ($update) {
                $smiley->store();
            }

            $ids[] = $smiley->id;
        }

        $db_ids = self::getGrouped('all', self::FETCH_ID);
        $missing = array_diff($db_ids, $ids);
        self::Remove($missing);
        $counts['delete'] = count($missing);

        if (SmileyFavorites::isEnabled()) {
            $counts['favorites'] = SmileyFavorites::gc();
        }

        return $counts;
    }
}


/**
 * SmileyFavorites
 * 
 * This model provides access to the favored smileys of an user.
 *
 * @author      Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @category    Stud.IP
 * @package     smiley
 * @since       2.3
 *
 * @uses        DBManager
 * @uses        Smiley
 */
class SmileyFavorites
{
    private $user_id;
    private $favorites = array();
    
    /**
     * Initializes an user's favorites
     *
     * @param String $user_id Id of the user
     */
    function __construct($user_id)
    {
        $this->user_id = $user_id;

        $query = "SELECT smiley_favorite FROM user_info WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->user_id));
        $favorite_string = $statement->fetchColumn() ?: '';
        $this->favorites = explode(',', $favorite_string);
        $this->favorites = array_filter($this->favorites);
    }

    /**
     * Returns the user's favored smileys' ids.
     *
     * @return Array Ids of the smileys the user has vaored
     */
    function get()
    {
        return $this->favorites;
    }

    /**
     * Updates the user's favored smileys.
     *
     * @param Array $favorites Ids of the user's favored smileys
     */
    function set($favorites = array())
    {
        $this->favorites = $favorites;
        
        $favorite_string = implode(',', $favorites);
        DBManager::get()
            ->prepare("UPDATE user_info SET smiley_favorite = ? WHERE user_id = ?")
            ->execute(array($favorite_string, $this->user_id));
    }

    /**
     * Returns whether the smiley with the given id is favored by the user.
     *
     * @param  int  $smiley_id Id of the smiley
     * @return bool True if the smiley is favored by the user, false otherwise
     */
    function contain($smiley_id)
    {
        return in_array($smiley_id, $this->favorites);
    }

    /**
     * Toggles whether a smiley is favored by the user. You can either provide
     * an acutal state or omit the state to toggle the current state.
     *
     * @param  int   $smiley_id  Id of the smiley to favor/disfavor
     * @param  mixed $favorite   Either a boolean state or null to toggle current state
     * @return bool  True if the smiley is favored by the user, false otherwise
     */
    function toggle($smiley_id, $favorite = null)
    {
        if ($favorite === null) {
            $favorite = !$this->contain($smiley_id);
        }
        $favorites = $this->favorites;
        
        if ($favorite) {
            $favorites[] = $smiley_id;
        } else {
            $favorites = array_diff($favorites, array($smiley_id));
        }
        $this->set($favorites);

        return $this->contain($smiley_id);
    }
    
    
    /**
     * Returns whether the ability to favor smiley is enabled.
     * 
     * @return bool
     */
    static function isEnabled()
    {
        $state = null;
        if ($state === null) {
            $state = DBManager::get()
                ->query("SHOW COLUMNS FROM user_info LIKE 'smiley_favorite%'")
                ->fetchColumn();
        }
        return $state;
    }

    /**
     * Returns a list of how often a smiley has been favored.
     *
     * @return Array Associative array with smiley name as key and according
     *               favored numbers as value
     */
    static function getUsage()
    {
        $usage = array();
        
        $query = "SELECT user_id, smiley_favorite FROM user_info WHERE smiley_favorite != ''";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array());
        $temp = $statement->fetchGrouped(PDO::FETCH_COLUMN);

        foreach ($temp as $user_id => $favorite_string) {
            $favorites = explode(',', $favorite_string);
            $smileys = Smiley::getByIds($favorites);
            foreach ($smileys as $smiley) {
                if (!isset($usage[$smiley->name])) {
                    $usage[$smiley->name] = 0;
                }
                $usage[$smiley->name] += 1;

            }
        }
        
        return $usage;
    }

    /**
     * Garbage collector. Removes all smiley ids from the users' favorites
     * that are no longer in the database.
     *
     * @return int Number of changed records
     */
    static function gc()
    {
        $smileys = Smiley::getGrouped('all', Smiley::FETCH_ID);
        
        $query = "SELECT user_id, smiley_favorite FROM user_info WHERE smiley_favorite != ''";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array());
        $temp = $statement->fetchGrouped(PDO::FETCH_COLUMN);

        $changed = 0;
        foreach ($temp as $user_id => $favorite_string) {
            $old_favorites = explode(',', $favorite_string);
            $new_favorites = array_intersect($smileys, $old_favorites);
            if (count($old_favorites) > count($new_favorites)) {
                $favorites = new self($user_id);
                $favorites->set($new_favorites);
                $changed += 1;
            }
        }
        
        return $changed;
    }
}
