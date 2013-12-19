<?php
/**
 * ForumEntry.php - Allows the retrieval and handling of forum-entrys
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 3 of
 * the License, or (at your option) any later version.
 *
 * @author      Till Gl�ggler <tgloeggl@uos.de>
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GPL version 3
 * @category    Stud.IP
 */

require_once 'lib/visual.inc.php';

class ForumEntry {
    const WITH_CHILDS = true;
    const WITHOUT_CHILDS = false;
    const THREAD_PREVIEW_LENGTH = 100;
    const POSTINGS_PER_PAGE = 10;
    const FEED_POSTINGS = 100;


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * H   E   L   P   E   R   -   F   U   N   C   T   I   O   N   S *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * is used for posting-preview. replaces all newlines with spaces
     * 
     * @param string $text the text to work on
     * @returns string
     */
    static function br2space($text)
    {
        return str_replace("\n", ' ', str_replace("\r", '', $text));
    }

    /**
     * remove the edit-html from a posting
     *
     * @param string $description the posting-content
     * @return string the content stripped by the edit-mark
     */
    static function killEdit($description)
    {
        // wurde schon mal editiert
        if (preg_match('/^(.*)(<admin_msg.*?)$/s', $description, $match)) {
            return $match[1];
        }
        return $description;
    }

    /**
     * add the edit-html to a posting
     * 
     * @param string $description the posting-content
     * @return string the content with the edit-mark
     */
    static function appendEdit($description)
    {
        $edit = "<admin_msg autor=\"" . addslashes(get_fullname()) . "\" chdate=\"" . time() . "\">";
        return $description . $edit;
    }

    /**
     * convert the edit-html to raw text
     * 
     * @param string $description the posting-content
     * @return string the content with the raw text version of the edit-mark
     */
    static function parseEdit($description, $anonymous = false)
    {
        // wurde schon mal editiert
        if (preg_match('/^.*(<admin_msg.*?)$/s', $description, $match)) {
            $tmp = explode('"', $match[1]);
            $append = "\n\n%%[" . _("Zuletzt editiert von") . ' ' . ($anonymous && !$GLOBALS['perm']->have_perm('root') ? _('Anonym') : $tmp[1]) . " - " . date("d.m.y - H:i", $tmp[3]) . "]%%";
            $description = ForumEntry::killEdit($description) . $append;
        }
        return $description;
    }

    /**
     * remove the [quote]-tags from the passed posting
     * 
     * @param string $description the posting-content
     * @return string the posting without [quote]-tags
     */
    static function killQuotes($description)
    {
        return str_replace('[/quote]', '', preg_replace("/\[quote=.*\]/U", "", $description));
    }


    /**
     * calls Stud.IP's kill_format and additionally removes any found smiley-tag
     * 
     * @param string $text the text to parse
     * @return string the text without format-tags and without smileys
     */
    static function killFormat($text)
    {
        
        $text = kill_format($text);
        
        // find stuff which is enclosed between to colons
        preg_match('/:.*:/U', $text, $matches);
        
        // remove the match if it is a smiley
        foreach ($matches as $match) {
            if (Smiley::getByName($match) || Smiley::getByShort($match)) {
                $text = str_replace($match, '', $text);
            }
        }
        
        return $text;
    }

    /**
     * returns the entry for the passed topic_id
     *
     * @param  string  $topic_id
     * @return array   array('lft' => ..., 'rgt' => ..., seminar_id => ...)
     *
     * @throws Exception
     */
    static function getConstraints($topic_id)
    {
        // look up the range of postings
        $range_stmt = DBManager::get()->prepare("SELECT *
            FROM forum_entries WHERE topic_id = ?");
        $range_stmt->execute(array($topic_id));
        if (!$data = $range_stmt->fetch(PDO::FETCH_ASSOC)) {
            return false;
            // throw new Exception("Could not find entry with id >>$topic_id<< in forum_entries, " . __FILE__ . " on line " . __LINE__);
        }

        if ($data['depth'] == 1) {
            $data['area'] = 1;
        }

        return $data;
    }
    
    /**
     * return the topic_id of the parent element, false if there is none (ie the
     * passed topic_id is already the upper-most node in the tree)
     * 
     * @param string $topic_id the topic_id for which the parent shall be found
     * 
     * @return string the topic_id of the parent element or false
     */
    static function getParentTopicId($topic_id)
    {
        $path = ForumEntry::getPathToPosting($topic_id);
        array_pop($path);
        $data = array_pop($path);
        
        return $data['id'] ?: false;
    }
    
    
    /**
     * get the topic_ids of all childs of the passed topic including itself
     * 
     * @param string $topic_id the topic_id to find the childs for
     * @return array a list if topic_ids
     */
    static function getChildTopicIds($topic_id)
    {
        $constraints = ForumEntry::getConstraints($topic_id);
        
        $stmt = DBManager::get()->prepare("SELECT topic_id
            FROM forum_entries WHERE lft >= ? AND rgt <= ?
                AND seminar_id = ?");
        $stmt->execute(array($constraints['lft'], $constraints['rgt'], $constraints['seminar_id']));
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * D   A   T   A   -   R   E   T   R   I   E   V   A   L *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

     /**
      * get the page the passed posting is on
      *
      * @param  string  $topic_id
      * @return  int
      */
    static function getPostingPage($topic_id, $constraint = null)
    {
        if (!$constraint) {
            $constraint = ForumEntry::getConstraints($topic_id);
        }

        // this calculation only works for postings
        if ($constraint['depth'] <= 2) return ForumHelpers::getPage();

        if ($parent_id = ForumEntry::getParentTopicId($topic_id)) {
            $parent_constraint = ForumEntry::getConstraints($parent_id);

            return ceil((($constraint['lft'] - $parent_constraint['lft'] + 3) / 2) / ForumEntry::POSTINGS_PER_PAGE);
        }

        return 0;
    }

    /**
     * return the id for the oldest unread child-posting for the passed topic.
     * 
     * @param string $parent_id
     * @return string  id of oldest unread posting
     */
    static function getLastUnread($parent_id)
    {
        $constraint = ForumEntry::getConstraints($parent_id);
        
        // take users visitdate into account
        $visitdate = ForumVisit::getLastVisit($constraint['seminar_id']);
        
        // get the first unread entry
        $stmt = DBManager::get()->prepare("SELECT * FROM forum_entries
            WHERE lft > ? AND rgt < ? AND seminar_id = ?
                AND mkdate >= ?
            ORDER BY mkdate ASC LIMIT 1");
        $stmt->execute(array($constraint['lft'], $constraint['rgt'], $constraint['seminar_id'], $visitdate));
        $last_unread = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $last_unread ? $last_unread['topic_id'] : null;
    }

    /**
     * retrieve the the latest posting under $parent_id
     * or false if the postings itself is the latest
     *
     * @param string $parent_id the node to lookup the childs in
     * @return mixed the data for the latest postings or false
     */
    static function getLatestPosting($parent_id)
    {
        $constraint = ForumEntry::getConstraints($parent_id);

        // get last entry
        $stmt = DBManager::get()->prepare("SELECT * FROM forum_entries
            WHERE lft > ? AND rgt < ? AND seminar_id = ?
            ORDER BY mkdate DESC LIMIT 1");
        $stmt->execute(array($constraint['lft'], $constraint['rgt'], $constraint['seminar_id']));

        if (!$data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return false;
        }
        
        return $data;
    }

    /**
     * returns a hashmap with arrays containing id and name with the entries
     * which lead to the passed topic
     * 
     * @param string $topic_id the topic to get the path for
     * 
     * @return array
     */
    static function getPathToPosting($topic_id)
    {
        $data = ForumEntry::getConstraints($topic_id);
        $ret = array();

        $stmt = DBManager::get()->prepare("SELECT * FROM forum_entries
            WHERE lft <= ? AND rgt >= ? AND seminar_id = ? ORDER BY lft ASC");
        $stmt->execute(array($data['lft'], $data['rgt'], $data['seminar_id']));

        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $ret[$data['topic_id']] = array(
                'id'   => $data['topic_id'],
                'name' => $data['name']
            );
        }

        // set the name of the first entry to the name of the category the entry is in
        if (sizeof($ret) > 1) {
            reset($ret);
            $area = array_pop(array_slice($ret, 1, 1));
            $top  = current($ret);
            $ret[$top['id']]['name'] = ForumCat::getCategoryNameForArea($area['id']) ?: _('Allgemein');
        }

        return $ret;
    }
    
    /**
     * returns a hashmap where key is topic_id and value a posting-title from the
     * entries which lead to the passed topic.
     * 
     * WARNING: This function ommits postings with an empty title. For a full
     * list please use ForumEntry::getPathToPosting()!
     * 
     * @param string $topic_id the topic to get the path for
     * 
     * @return array
     */    
    static function getFlatPathToPosting($topic_id)
    {
        // use only the part of the path until the thread, no posting title
        $postings = array_slice(self::getPathToPosting($topic_id), 0, 3);
        
        // var_dump($postings);
        
        foreach ($postings as $post) {
            if ($post['name']) {
                $ret[$post['id']] = $post['name'];
            }
        }
        
        return $ret;
    }

    /**
     * fill the passed postings with additional data
     *
     * @param  array $postings
     * @return array
     */
    static function parseEntries($postings)
    {
        $posting_list = array();

        // retrieve the postings
        foreach ($postings as $data) {
            // we throw away all formatting stuff, tags, etc, leaving the important bit of information
            $desc_short = ForumEntry::br2space(ForumEntry::killFormat(strip_tags($data['content'])));
            if (strlen($desc_short) > (ForumEntry::THREAD_PREVIEW_LENGTH + 2)) {
                $desc_short = substr($desc_short, 0, ForumEntry::THREAD_PREVIEW_LENGTH) . '...';
            } else {
                $desc_short = $desc_short;
            }

            $posting_list[$data['topic_id']] = array(
                'author'          => $data['author'],
                'topic_id'        => $data['topic_id'],
                'name'            => formatReady($data['name']),
                'name_raw'        => $data['name'],
                'content'         => formatReady(ForumEntry::parseEdit($data['content'], $data['anonymous'])),
                'content_raw'     => ForumEntry::killEdit($data['content']),
                'content_short'   => $desc_short,
                'opengraph'       => ($og = OpenGraphURL::find(OpenGraphURL::$tempURLStorage[0])) ? $og->render() : "",
                'chdate'          => $data['chdate'],
                'mkdate'          => $data['mkdate'],
                'user_id'        => $data['user_id'],
                'raw_title'       => $data['name'],
                'raw_description' => ForumEntry::killEdit($data['content']),
                'fav'             => ($data['fav'] == 'fav'),
                'depth'           => $data['depth'],
                'anonymous'       => $data['anonymous'],
                'closed'          => $data['closed'],
                'sticky'          => $data['sticky'],
                'seminar_id'      => $data['seminar_id']
            );
        } // retrieve the postings

        return $posting_list;
    }

    /**
     * Get all entries for the passed parent_id.
     * Returns an array of the following structure:
     * Array (
     *     'list'  => Array (
     *         'author'          => 
     *         'topic_id'        => 
     *         'name'            => formatReady()
     *         'name_raw'        => 
     *         'content'         => formatReady()
     *         'content_raw'     => 
     *         'content_short'   => 
     *         'chdate'          => 
     *         'mkdate'          => 
     *         'user_id'        => 
     *         'raw_title'       => 
     *         'raw_description' => 
     *         'fav'             => 
     *         'depth'           => 
     *         'sticky'          =>
     *         'closed'          =>
     *         'seminar_id'      =>
     *     )
     *     'count' =>
     * )
     * 
     * @param type $parent_id    id of parent-element to get entries for.
     * @param type $with_childs  if true, the whole subtree is fetched
     * @param type $add          for additional constraints in the WHERE-part of the query
     * @param type $sort_order   can be ASC or DESC
     * @param type $start        can be used for pagination, is used for the LIMIT-part of the query
     * @param type $limit        number of entries to fetch, defaults to ForumEntry::POSTINGS_PER_PAGE
     * 
     * @return array
     * 
     * @throws Exception  if the retrieval failed, an Exception is thrown
     */
    static function getEntries($parent_id, $with_childs = false, $add = '',
        $sort_order = 'DESC', $start = 0, $limit = ForumEntry::POSTINGS_PER_PAGE)
    {
        $constraint = ForumEntry::getConstraints($parent_id);
        $seminar_id = $constraint['seminar_id'];
        $depth      = $constraint['depth'] + 1;

        // count the entries and set correct page if necessary
        if ($with_childs) {
            $count_stmt = DBManager::get()->prepare("SELECT COUNT(*) FROM forum_entries
                LEFT JOIN forum_favorites as ou ON (ou.topic_id = forum_entries.topic_id AND ou.user_id = ?)
                WHERE (forum_entries.seminar_id = ?
                    AND forum_entries.seminar_id != forum_entries.topic_id
                    AND lft > ? AND rgt < ?) "
                . ($depth > 2 ? " OR forum_entries.topic_id = ". DBManager::get()->quote($parent_id) : '')
                . $add
                . " ORDER BY forum_entries.mkdate $sort_order");
            $count_stmt->execute(array($GLOBALS['user']->id, $seminar_id, $constraint['lft'], $constraint['rgt']));
            $count = $count_stmt->fetchColumn();
        } else {
            $count_stmt = DBManager::get()->prepare("SELECT COUNT(*) FROM forum_entries
                LEFT JOIN forum_favorites as ou ON (ou.topic_id = forum_entries.topic_id AND ou.user_id = ?)
                WHERE ((depth = ? AND forum_entries.seminar_id = ?
                    AND forum_entries.seminar_id != forum_entries.topic_id
                    AND lft > ? AND rgt < ?) "
                . ($depth > 2 ? " OR forum_entries.topic_id = ". DBManager::get()->quote($parent_id) : '')
                . ') '. $add
                . " ORDER BY forum_entries.mkdate $sort_order");
            $count_stmt->execute(array($GLOBALS['user']->id, $depth, $seminar_id, $constraint['lft'], $constraint['rgt']));
            $count = $count_stmt->fetchColumn();            
        }

        // use the last page if the requested page does not exist
        if ($start > $count) {
            $page = ceil($count / ForumEntry::POSTINGS_PER_PAGE);
            ForumHelpers::setPage($page);
            $start = max(1, $page - 1) * ForumEntry::POSTINGS_PER_PAGE;
        }
        
        if ($with_childs) {
            $stmt = DBManager::get()->prepare("SELECT forum_entries.*, IF(ou.topic_id IS NOT NULL, 'fav', NULL) as fav
                    FROM forum_entries
                LEFT JOIN forum_favorites as ou ON (ou.topic_id = forum_entries.topic_id AND ou.user_id = ?)
                WHERE (forum_entries.seminar_id = ?
                    AND forum_entries.seminar_id != forum_entries.topic_id
                    AND lft > ? AND rgt < ?) "
                . ($depth > 2 ? " OR forum_entries.topic_id = ". DBManager::get()->quote($parent_id) : '')
                . $add
                . " ORDER BY forum_entries.mkdate $sort_order"
                . ($limit ? " LIMIT $start, $limit" : ''));
            $stmt->execute(array($GLOBALS['user']->id, $seminar_id, $constraint['lft'], $constraint['rgt']));
        } else {
            $stmt = DBManager::get()->prepare("SELECT forum_entries.*, IF(ou.topic_id IS NOT NULL, 'fav', NULL) as fav
                    FROM forum_entries
                LEFT JOIN forum_favorites as ou ON (ou.topic_id = forum_entries.topic_id AND ou.user_id = ?)
                WHERE ((depth = ? AND forum_entries.seminar_id = ?
                    AND lft > ? AND rgt < ?) "
                . ($depth > 2 ? " OR forum_entries.topic_id = ". DBManager::get()->quote($parent_id) : '')
                . ') '. $add
                . " ORDER BY forum_entries.mkdate $sort_order"
                . ($limit ? " LIMIT $start, $limit" : ''));
            $stmt->execute(array($GLOBALS['user']->id, $depth, $seminar_id, $constraint['lft'], $constraint['rgt']));
        }

        if (!$stmt) {
            throw new Exception("Error while retrieving postings in " . __FILE__ . " on line " . __LINE__);
        }

        return array('list' => ForumEntry::parseEntries($stmt->fetchAll(PDO::FETCH_ASSOC)), 'count' => $count);
    }


    /**
     * Takes a posting-array like the one generated by ForumEntry::getList()
     * and adds the child-posting with the freshest creation-date to it.
     * 
     * @param array $postings
     * @return array
     */
    function getLastPostings($postings)
    {
        foreach ($postings as $key => $posting) {

            if ($data = ForumEntry::getLatestPosting($posting['topic_id'])) {
                $last_posting['topic_id']      = $data['topic_id'];
                $last_posting['date']          = $data['mkdate'];
                $last_posting['user_id']       = $data['user_id'];
                $last_posting['user_fullname'] = $data['author'];
                $last_posting['username']      = get_username($data['user_id']);
                $last_posting['anonymous']     = $data['anonymous'];

                // we throw away all formatting stuff, tags, etc, so we have just the important bit of information
                $text = strip_tags($data['name']);
                $text = ForumEntry::br2space($text);
                $text = ForumEntry::killFormat(ForumEntry::killQuotes($text));

                if (strlen($text) > 42) {
                    $text = substr($text, 0, 40) . '...';
                }

                $last_posting['text'] = $text;
            }

            $postings[$key]['last_posting'] = $last_posting;            
            if (!$postings[$key]['last_unread']  = ForumEntry::getLastUnread($posting['topic_id'])) {
                $postings[$key]['last_unread'] = $last_posting['topic_id'];
            }
            $postings[$key]['num_postings'] = ForumEntry::countEntries($posting['topic_id']);

            unset($last_posting);
        }

        return $postings;
    }

    /**
     * get a list of postings of a special type
     * 
     * @param string $type one of 'area', 'list', 'postings', 'latest', 'favorites', 'dump', 'flat'
     * @param string $parent_id the are to fetch from
     * @return array array('list' => ..., 'count' => ...);
     */
    static function getList($type, $parent_id)
    {
        $start = (ForumHelpers::getPage() - 1) * ForumEntry::POSTINGS_PER_PAGE;

        switch ($type) {
            case 'area':
                $list = ForumEntry::getEntries($parent_id, ForumEntry::WITHOUT_CHILDS, '', 'DESC', 0, 1000);
                $postings = $list['list'];

                $postings = ForumEntry::getLastPostings($postings);
                return array('list' => $postings, 'count' => $list['count']);

                break;

            case 'list':
                $constraint = ForumEntry::getConstraints($parent_id);

                // purpose of the following query is to retrieve the threads
                // for an area ordered by the mkdate of their latest posting
                $stmt = DBManager::get()->prepare("SELECT SQL_CALC_FOUND_ROWS
                        fe.*, IF(ou.topic_id IS NOT NULL, 'fav', NULL) as fav
                    FROM forum_entries AS fe
                    LEFT JOIN forum_favorites as ou ON (ou.topic_id = fe.topic_id AND ou.user_id = :user_id)
                    WHERE fe.seminar_id = :seminar_id AND fe.lft > :left
                        AND fe.rgt < :right AND fe.depth = 2
                    ORDER BY sticky DESC, latest_chdate DESC
                    LIMIT $start, ". ForumEntry::POSTINGS_PER_PAGE);
                $stmt->bindParam(':seminar_id', $constraint['seminar_id']);
                $stmt->bindParam(':left', $constraint['lft'], PDO::PARAM_INT);
                $stmt->bindParam(':right', $constraint['rgt'], PDO::PARAM_INT);
                $stmt->bindParam(':user_id', $GLOBALS['user']->id);
                $stmt->execute();

                $postings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $count = DBManager::get()->query("SELECT FOUND_ROWS()")->fetchColumn();
                $postings = ForumEntry::parseEntries($postings);
                $postings = ForumEntry::getLastPostings($postings);

                return array('list' => $postings, 'count' => $count);
                break;

            case 'postings':
                return ForumEntry::getEntries($parent_id, ForumEntry::WITH_CHILDS, '', 'ASC', $start);
                break;

            case 'newest':
                $constraint = ForumEntry::getConstraints($parent_id);

                // get postings
                $stmt = DBManager::get()->prepare("SELECT forum_entries.*, IF(ou.topic_id IS NOT NULL, 'fav', NULL) as fav
                    FROM forum_entries
                    LEFT JOIN forum_favorites as ou ON (ou.topic_id = forum_entries.topic_id AND ou.user_id = :user_id)
                    WHERE seminar_id = :seminar_id AND lft > :left
                        AND rgt < :right AND (mkdate >= :mkdate OR chdate >= :mkdate)
                    ORDER BY mkdate ASC
                    LIMIT $start, ". ForumEntry::POSTINGS_PER_PAGE);
                
                $stmt->bindParam(':seminar_id', $constraint['seminar_id']);
                $stmt->bindParam(':left', $constraint['lft']);
                $stmt->bindParam(':right', $constraint['rgt']);
                $stmt->bindParam(':mkdate', ForumVisit::getLastVisit($constraint['seminar_id']));
                $stmt->bindParam(':user_id', $GLOBALS['user']->id);
                $stmt->execute();
                
                $postings = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $postings = ForumEntry::parseEntries($postings);
                // var_dump($postings);

                // count found postings
                $stmt_count = DBManager::get()->prepare("SELECT COUNT(*)
                    FROM forum_entries
                    WHERE seminar_id = :seminar_id AND lft > :left
                        AND rgt < :right AND mkdate >= :mkdate
                    ORDER BY mkdate ASC");
                
                $stmt_count->bindParam(':seminar_id', $constraint['seminar_id']);
                $stmt_count->bindParam(':left', $constraint['lft']);
                $stmt_count->bindParam(':right', $constraint['rgt']);
                $stmt_count->bindParam(':mkdate', ForumVisit::getLastVisit($constraint['seminar_id']));
                $stmt_count->execute();


                // return results
                return array('list' => $postings, 'count' => $stmt_count->fetchColumn());
                break;

            case 'latest':
                return ForumEntry::getEntries($parent_id, ForumEntry::WITH_CHILDS, '', 'DESC', $start);
                break;

            case 'favorites':
                $add = "AND ou.topic_id IS NOT NULL";
                return ForumEntry::getEntries($parent_id, ForumEntry::WITH_CHILDS, $add, 'DESC', $start);
                break;
            
            case 'dump':
                return ForumEntry::getEntries($parent_id, ForumEntry::WITH_CHILDS, '', 'ASC', 0, false);
                break;
            
            case 'flat':
                $constraint = ForumEntry::getConstraints($parent_id);
                
                $stmt = DBManager::get()->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM forum_entries
                    WHERE lft > ? AND rgt < ? AND seminar_id = ? AND depth = ?
                    ORDER BY name ASC");
                $stmt->execute(array($constraint['lft'], $constraint['rgt'], $constraint['seminar_id'], $constraint['depth'] + 1));
                
                $count = DBManager::get()->query("SELECT FOUND_ROWS()")->fetchColumn();

                $posting_list = array();

                // speed up things a bit by leaving out the formatReady fields
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $data) {
                    $posting_list[$data['topic_id']] = array(
                        'author'          => $data['author'],
                        'topic_id'        => $data['topic_id'],
                        'name_raw'        => $data['name'],
                        'content_raw'     => ForumEntry::killEdit($data['content']),
                        'content_short'   => $desc_short,
                        'chdate'          => $data['chdate'],
                        'mkdate'          => $data['mkdate'],
                        'user_id'        => $data['user_id'],
                        'raw_title'       => $data['name'],
                        'raw_description' => ForumEntry::killEdit($data['content']),
                        'fav'             => ($data['fav'] == 'fav'),
                        'depth'           => $data['depth'],
                        'seminar_id'      => $data['seminar_id']
                    );
                }

                return array('list' => $posting_list, 'count' => $count);
                break;
                
            case 'depth_to_large':
                $constraint = ForumEntry::getConstraints($parent_id);
                
                $stmt = DBManager::get()->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM forum_entries
                    WHERE lft > ? AND rgt < ? AND seminar_id = ? AND depth > 3
                    ORDER BY name ASC");
                $stmt->execute(array($constraint['lft'], $constraint['rgt'], $constraint['seminar_id']));
                
                $count = DBManager::get()->query("SELECT FOUND_ROWS()")->fetchColumn();

                return array('list' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'count' => $count);
                break;
        }
    }
    
    /**
     * Get the latest forum entries for the passed entries childs
     * 
     * @param string $parent_id
     * @param int $since  timestamp
     * 
     * @return array list of postings
     */
    function getLatestSince($parent_id, $since)
    {
        $constraint = ForumEntry::getConstraints($parent_id);
                
        $stmt = DBManager::get()->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM forum_entries
            WHERE lft > ? AND rgt < ? AND seminar_id = ?
                AND mkdate >= ?
            ORDER BY name ASC");
        $stmt->execute(array($constraint['lft'], $constraint['rgt'], $constraint['seminar_id'], $since));
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     ** returns a list of postings for the passed search-term
     * 
     * @param string $parent_id the area to search in (can be a whole seminar)
     * @param string $_searchfor the term to search for
     * @param array $options filter-options: search_title, search_content, search_author
     * @return array array('list' => ..., 'count' => ...);
     */
    static function getSearchResults($parent_id, $_searchfor, $options)
    {
        $start = (ForumHelpers::getPage() - 1) * ForumEntry::POSTINGS_PER_PAGE;

        // if there are quoted parts, they should not be separated
        $suchmuster = '/".*"/U';
        preg_match_all($suchmuster, $_searchfor, $treffer);
        array_walk($treffer[0], function(&$value) { $value = trim($value, '"'); });

        // remove the quoted parts from $_searchfor
        $_searchfor = trim(preg_replace($suchmuster, '', $_searchfor));

        // split the searchstring $_searchfor at every space
        $parts = explode(' ', $_searchfor);

        foreach ($parts as $key => $val) {
            if ($val == '') {
                unset($parts[$key]);
            }
        }

        if (!empty($parts)) {
            $_searchfor = array_merge($parts, $treffer[0]);
        } else  {
            $_searchfor = $treffer[0];
        }

        // make an SQL-statement out of the searchstring
        $search_string = array();
        foreach ($_searchfor as $key => $val) {
            if (!$val) {
                unset($_searchfor[$key]);
            } else {
                $search_word = '%'. $val .'%';
                $zw_search_string = array();
                if ($options['search_title']) {
                    $zw_search_string[] .= "name LIKE " . DBManager::get()->quote($search_word);
                }

                if ($options['search_content']) {
                    $zw_search_string[] .= "content LIKE " . DBManager::get()->quote($search_word);
                }

                if ($options['search_author']) {
                    $zw_search_string[] .= "author LIKE " . DBManager::get()->quote($search_word);
                }
                
                $search_string[] = '('. implode(' OR ', $zw_search_string) .')';
            }
        }

        if (!empty($search_string)) {
            $add = "AND (" . implode(' AND ', $search_string) . ")";
            return array_merge(
                array('highlight' => $_searchfor),
                ForumEntry::getEntries($parent_id, ForumEntry::WITH_CHILDS, $add, 'DESC', $start)
            );
        }

        return array('num_postings' => 0, 'list' => array());
    }

    /**
     * returns the entry for the passed topic_id
     * 
     * @param string $topic_id
     * @return array hash-array with the entries fields
     */
    static function getEntry($topic_id)
    {
        return ForumEntry::getConstraints($topic_id);
    }

    /**
     * Count the number of child-elements that the passed entry has and return it.
     * 
     * @param string $parent_id
     * 
     * @return int  the number of child entries for the passed entry
     */
    static function countEntries($parent_id)
    {
        $data = ForumEntry::getConstraints($parent_id);
        return max((($data['rgt'] - $data['lft'] - 1) / 2) + 1, 0);
    }

    /**
     * Count all entries the passed user has ever written and return the result
     * 
     * @staticvar type $entries
     * 
     * @param string $user_id
     * 
     * @return int  number of entries user has ever written
     */
    static function countUserEntries($user_id, $seminar_id = null)
    {
        static $entries;

        if (!$entries[$user_id]) {
            $stmt = DBManager::get()->prepare("SELECT COUNT(*)
                FROM forum_entries
                WHERE user_id = ? AND seminar_id = IFNULL(?, seminar_id)");
            $stmt->execute(array($user_id, $seminar_id));

            $entries[$user_id] = $stmt->fetchColumn();
        }

        return $entries[$user_id];
    }
    
    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *   D   A   T   A   -   C   R   E   A   T   I   O   N   *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * insert a node into the table
     *
     * @param type $data an array containing the following fields:
     *     topic_id     the id of the new topic
     *     seminar_id   the id of the seminar to add the topic to
     *     user_id      the id of the user who created the topic
     *     name         the title of the entry
     *     content      the content of the entry
     *     author       the author's name as a plaintext string
     *     author_host  ip-address of creator
     * @param type $parent_id the node to add the topic to
     *
     * @return void
     */
    static function insert($data, $parent_id) 
    {
        $constraint = ForumEntry::getConstraints($parent_id);

        // #TODO: Zusammenfassen in eine Transaktion!!!
        DBManager::get()->exec('UPDATE forum_entries SET lft = lft + 2
            WHERE lft > '. $constraint['rgt'] ." AND seminar_id = '". $constraint['seminar_id'] ."'");
        DBManager::get()->exec('UPDATE forum_entries SET rgt = rgt + 2
            WHERE rgt >= '. $constraint['rgt'] ." AND seminar_id = '". $constraint['seminar_id'] ."'");

        $stmt = DBManager::get()->prepare("INSERT INTO forum_entries
            (topic_id, seminar_id, user_id, name, content, mkdate, latest_chdate,
                chdate, author, author_host, lft, rgt, depth, anonymous)
            VALUES (? ,?, ?, ?, ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), ?, ?, ?, ?, ?, ?)");
        $stmt->execute(array($data['topic_id'], $data['seminar_id'], $data['user_id'],
            $data['name'], transformBeforeSave($data['content']), $data['author'], $data['author_host'],
            $constraint['rgt'], $constraint['rgt'] + 1, $constraint['depth'] + 1, $data['anonymous'] ? : 0));

        // update "latest_chdate" for easier sorting of actual threads
        DBManager::get()->exec("UPDATE forum_entries SET latest_chdate = UNIX_TIMESTAMP()
            WHERE topic_id = '" . $constraint['topic_id'] . "'");
        
        NotificationCenter::postNotification('ForumAfterInsert', $data['topic_id'], $data);
    }


    /**
     * update the passed topic
     *
     * @param type $topic_id the id of the topic to update
     * @param type $name the new name
     * @param type $content the new content
     *
     * @return void
     */
    static function update($topic_id, $name, $content)
    {
        $topic = ForumEntry::getConstraints($topic_id);

        if (time() - $topic['mkdate'] > 5 * 60) {
            $content = ForumEntry::appendEdit($content);
        }

        $stmt = DBManager::get()->prepare("UPDATE forum_entries
            SET name = ?, content = ?, chdate = UNIX_TIMESTAMP(), latest_chdate = UNIX_TIMESTAMP()
            WHERE topic_id = ?");
        $stmt->execute(array($name, transformBeforeSave($content), $topic_id));

        // update "latest_chdate" for easier sorting of actual threads
        $parent_id = ForumEntry::getParentTopicId($topic_id);
        DBManager::get()->exec("UPDATE forum_entries SET latest_chdate = UNIX_TIMESTAMP()
            WHERE topic_id = '" . $parent_id . "'");
    }

    /**
     * delete an entry and all his descendants from the mptt-table
     *
     * @param type $topic_id the id of the entry to delete
     *
     * @return void
     */
    function delete($topic_id)
    {
        NotificationCenter::postNotification('ForumBeforeDelete', $topic_id);
        
        $constraints = ForumEntry::getConstraints($topic_id);
        $parent      = ForumEntry::getConstraints(ForumEntry::getParentTopicId($topic_id));

        // #TODO: Zusammenfassen in eine Transaktion!!!
        // get all entry-ids to delete them from the category-reference-table
        $stmt = DBManager::get()->prepare("SELECT topic_id FROM forum_entries
            WHERE seminar_id = ? AND lft >= ? AND rgt <= ? AND depth = 1");
        $stmt->execute(array($constraints['seminar_id'], $constraints['lft'], $constraints['rgt']));
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if ($ids != false && !is_array($ids)) $ids = array($ids);

        if (!empty($ids)) {
            $stmt = DBManager::get()->prepare("DELETE FROM forum_categories_entries
                WHERE topic_id IN (:ids)");
            $stmt->bindParam(':ids', $ids, StudipPDO::PARAM_ARRAY);
            $stmt->execute();
        }

        // delete all entries
        $stmt = DBManager::get()->prepare("DELETE FROM forum_entries
            WHERE seminar_id = ? AND lft >= ? AND rgt <= ?");

        $stmt->execute(array($constraints['seminar_id'], $constraints['lft'], $constraints['rgt']));

        // update lft and rgt
        $diff = $constraints['rgt'] - $constraints['lft'] + 1;
        $stmt = DBManager::get()->prepare("UPDATE forum_entries SET lft = lft - $diff
            WHERE lft > ? AND seminar_id = ?");
        $stmt->execute(array($constraints['rgt'], $constraints['seminar_id']));

        $stmt = DBManager::get()->prepare("UPDATE forum_entries SET rgt = rgt - $diff
            WHERE rgt > ? AND seminar_id = ?");
        $stmt->execute(array($constraints['rgt'], $constraints['seminar_id']));


        // set the latest_chdate to the latest child's chdate
        $stmt = DBManager::get()->prepare("SELECT chdate FROM forum_entries
            WHERE lft > ? AND rgt < ? AND seminar_id = ?
            ORDER BY chdate DESC LIMIT 1");
        $stmt->execute(array($parent['lft'], $parent['rgt'], $parent['seminar_id']));
        $chdate = $stmt->fetchColumn();

        $stmt_insert = DBManager::get()->prepare("UPDATE forum_entries
            SET chdate = ? WHERE topic_id = ?");
        if ($chdate) {
            $stmt_insert->execute(array($chdate, $parent['topic_id']));
        } else {
            $stmt_insert->execute(array($parent['chdate'], $parent['topic_id']));
        }
    }

    /**
     * move the passed topic to the passed area
     *
     * @param type $topic_id the topic to move
     * @param type $destination the area_id where the topic is moved to
     *
     * @return void
     */
    function move($topic_id, $destination)
    {
        // #TODO: Zusammenfassen in eine Transaktion!!!
        $constraints = ForumEntry::getConstraints($topic_id);

        // move the affected entries "outside" the tree
        $stmt = DBManager::get()->prepare("UPDATE forum_entries
            SET lft = lft * -1, rgt = (rgt * -1)
            WHERE seminar_id = ? AND lft >= ? AND rgt <= ?");
        $stmt->execute(array($constraints['seminar_id'], $constraints['lft'], $constraints['rgt']));

        // update the lft and rgt values of the parent to reflect the "deletion"
        $diff = $constraints['rgt'] - $constraints['lft'] + 1;
        $stmt = DBManager::get()->prepare("UPDATE forum_entries SET lft = lft - $diff
            WHERE lft > ? AND seminar_id = ?");
        $stmt->execute(array($constraints['rgt'], $constraints['seminar_id']));

        $stmt = DBManager::get()->prepare("UPDATE forum_entries SET rgt = rgt - $diff
            WHERE rgt > ? AND seminar_id = ?");
        $stmt->execute(array($constraints['rgt'], $constraints['seminar_id']));

        // make some space by updating the lft and rgt values of the target node
        $constraints_destination = ForumEntry::getConstraints($destination);
        $size = $constraints['rgt'] - $constraints['lft'] + 1;
        
        DBManager::get()->exec("UPDATE forum_entries SET lft = lft + $size
            WHERE lft > ". $constraints_destination['rgt'] ." AND seminar_id = '". $constraints_destination['seminar_id'] ."'");
        DBManager::get()->exec("UPDATE forum_entries SET rgt = rgt + $size
            WHERE rgt >= ". $constraints_destination['rgt'] ." AND seminar_id = '". $constraints_destination['seminar_id'] . "'");

        //move the entries from "outside" the tree to the target node
        $constraints_destination = ForumEntry::getConstraints($destination);

        
        // update the depth to reflect the new position in the tree
        // determine if we need to add, subtract or even do nothing to/from the depth
        $depth_mod = $constraints_destination['depth'] - $constraints['depth'] + 1;
        
        DBManager::get()->exec("UPDATE forum_entries
            SET depth = depth + (" . $depth_mod .")
            WHERE seminar_id = '". $constraints_destination['seminar_id'] ."'
                AND lft < 0");

        // move the tree to its destination
        $diff = ($constraints_destination['rgt'] - ($constraints['rgt'] - $constraints['lft'])) - 1 - $constraints['lft'];

        DBManager::get()->exec("UPDATe forum_entries
            SET lft = (lft * -1) + $diff, rgt = (rgt * -1) + $diff
            WHERE seminar_id = '". $constraints_destination['seminar_id'] ."'
                AND lft < 0");
    }
    
    /**
     * close the passed topic
     *
     * @param type $topic_id the topic to close
     *
     * @return void
     */
    static function close($topic_id)
    {
        // close all entries belonging to the topic
        $stmt = DBManager::get()->prepare("UPDATE forum_entries
            SET closed = 1
            WHERE topic_id = ?");
        $stmt->execute(array($topic_id));
    }
    
    /**
     * open the passed topic
     *
     * @param type $topic_id the topic to open
     *
     * @return void
     */
    static function open($topic_id)
    {
        // open all entries belonging to the topic
        $stmt = DBManager::get()->prepare("UPDATE forum_entries
            SET closed = 0
            WHERE topic_id = ?");
        $stmt->execute(array($topic_id));
    }
    
    /**
     * make the passed topic sticky
     *
     * @param type $topic_id the topic to make sticky
     *
     * @return void
     */
    static function sticky($topic_id)
    {
        // open all entries belonging to the topic
        $stmt = DBManager::get()->prepare("UPDATE forum_entries
            SET sticky = 1
            WHERE topic_id = ?");
        $stmt->execute(array($topic_id));
    }
    
    /**
     * make the passed topic unsticky
     *
     * @param type $topic_id the topic to make unsticky
     *
     * @return void
     */
    static function unsticky($topic_id)
    {
        // open all entries belonging to the topic
        $stmt = DBManager::get()->prepare("UPDATE forum_entries
            SET sticky = 0
            WHERE topic_id = ?");
        $stmt->execute(array($topic_id));
    }

    /**
     * check, if the default root-node for this seminar exists and make sure
     * the default category exists as well
     *
     * @param type $seminar_id
     *
     * @return void
     */
    function checkRootEntry($seminar_id)
    {
        // check, if the root entry in the topic tree exists
        $stmt = DBManager::get()->prepare("SELECT COUNT(*) FROM forum_entries
            WHERE topic_id = ? AND seminar_id = ?");
        $stmt->execute(array($seminar_id, $seminar_id));
        if ($stmt->fetchColumn() == 0) {
            $stmt = DBManager::get()->prepare("INSERT INTO forum_entries
                (topic_id, seminar_id, name, mkdate, chdate, lft, rgt, depth)
                VALUES (?, ?, '�bersicht', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 1, 0)");
            $stmt->execute(array($seminar_id, $seminar_id));
        }

        // make sure, that the category "Allgemein" exists
        $stmt = DBManager::get()->prepare("REPLACE INTO forum_categories
            (category_id, seminar_id, entry_name) VALUES (?, ?, 'Allgemein')");
        $stmt->execute(array($seminar_id, $seminar_id));
        
        // make sure that the default area "Allgemeine Diskussionen" exists, if there is nothing else present
        $stmt = DBManager::get()->prepare("SELECT COUNT(*) FROM forum_entries 
            WHERE seminar_id = ? AND depth = 1");
        $stmt->execute(array($seminar_id));
        
        // add default area
        if ($stmt->fetchColumn() == 0) {
            $data = array(
                'topic_id'    => md5(uniqid()),
                'seminar_id'  => $seminar_id,
                'user_id'     => '',
                'name'        => 'Allgemeine Diskussion',
                'content'     => 'Hier ist Raum f�r allgemeine Diskussionen',
                'author'      => '',
                'author_host' => ''
            );
            ForumEntry::insert($data, $seminar_id);
        }
    }
    
    /**
     * returns the ten most active seminars
     * 
     * @return array
     */
    static function getTopTenSeminars()
    {
        return DBManager::get()->query("SELECT a.seminar_id, b.name AS display,
            count( a.seminar_id ) AS count FROM forum_entries a
            INNER JOIN seminare b USING ( seminar_id )
            WHERE b.visible = 1
            AND a.mkdate > UNIX_TIMESTAMP( NOW( ) - INTERVAL 2 WEEK )
            GROUP BY a.seminar_id
            ORDER BY count DESC
            LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * count all entries that exists in the whole installation and return it.
     * 
     * @return int
     */
    static function countAllEntries()
    {
        return DBManager::get()->query("SELECT COUNT(*) FROM forum_entries")->fetchColumn();
    }
    
    /**
     * updates the user-entries and replaces the old user-id by the new one
     * 
     * @param string $user_from
     * @param string $user_to
     */
    static function migrateUser($user_from, $user_to)
    {
        $stmt = DBManager::get()->prepare("UPDATE forum_entries SET user_id = ? WHERE user_id = ?");
        $stmt->execute(array($user_to, $user_from));

        $stmt = DBManager::get()->prepare("UPDATE IGNORE forum_favorites SET user_id = ? WHERE user_id = ?");
        $stmt->execute(array($user_to, $user_from));
        
        $stmt = DBManager::get()->prepare("UPDATE IGNORE forum_visits SET user_id = ? WHERE user_id = ?");
        $stmt->execute(array($user_to, $user_from));
        
        $stmt = DBManager::get()->prepare("UPDATE IGNORE forum_likes SET user_id = ? WHERE user_id = ?");
        $stmt->execute(array($user_to, $user_from));        
        
        $stmt = DBManager::get()->prepare("UPDATE IGNORE forum_abo_users SET user_id = ? WHERE user_id = ?");
        $stmt->execute(array($user_to, $user_from));        
    }

    /**
     * returns the complete seminar or only the passed sub-tree as a html-string
     * 
     * @param string $seminar_id
     * 
     * @return string
     */
    static function getDump($seminar_id, $parent_id = null)
    {
        $seminar_name = get_object_name($seminar_id, 'sem');
        $content = '<h1>'. _('Forum') .': '  . $seminar_name['name'] .'</h1>';
        $data = ForumEntry::getList('dump', $parent_id ?: $seminar_id);

        foreach ($data['list'] as $entry) {
            if ($entry['depth'] == 1) {
                $content .= '<h2>'. _('Bereich') .': '. $entry['name'] .'</h2>';
                $content .= $entry['content'] .'<br><br>';
            } else if ($entry['depth'] == 2) {
                $content .= '<h3 style="margin-bottom: 0px;">'. _('Thema') .': '. $entry['name'] .'</h3>';
                $content .= '<i>' . sprintf(_('erstellt von %s am %s'), htmlReady($entry['author']), 
                    strftime('%A %d. %B %Y, %H:%M', (int)$entry['mkdate'])) . '</i><br>';
                $content .= $entry['content'] .'<br><br>';
            } else if ($entry['depth'] == 3) {
                $content .= '<b>'.$entry['name'] .'</b><br>';
                $content .= '<i>' . sprintf(_('erstellt von %s am %s'), htmlReady($entry['author']), 
                    strftime('%A %d. %B %Y, %H:%M', (int)$entry['mkdate'])) . '</i><br>';
                $content .= $entry['content'] .'<hr><br>';
            }
        }

        return $content;
    }
}
