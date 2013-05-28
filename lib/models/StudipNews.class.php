<?php
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
//
// Copyright (C) 2005 André Noack <noack@data-quest>,
// Suchi & Berg GmbH <info@data-quest.de>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+
require_once 'lib/object.inc.php';

/**
 * StudipNews.class.php
 *
 *
 *
 *
 * @author   André Noack <noack@data-quest>, Suchi & Berg GmbH <info@data-quest.de>
 * @access   public
 *
 * @property string news_id database column
 * @property string id alias column for news_id
 * @property string topic database column
 * @property string body database column
 * @property string author database column
 * @property string date database column
 * @property string user_id database column
 * @property string expire database column
 * @property string allow_comments database column
 * @property string chdate database column
 * @property string chdate_uid database column
 * @property string mkdate database column
 * @property SimpleORMapCollection news_ranges has_many NewsRange
 * @property SimpleORMapCollection comments has_many StudipComment
 * @property User owner belongs_to User
 */
class StudipNews extends SimpleORMap {

    public static function GetNewsByRange($range_id, $only_visible = false, $as_objects = false)
    {
        if ($only_visible){
            $clause = " AND date < UNIX_TIMESTAMP() AND (date+expire) > UNIX_TIMESTAMP() ";
        }
        $query = "SELECT news_id AS idx, news.*
                  FROM news_range
                  INNER JOIN news USING (news_id)
                  WHERE range_id = ? {$clause}
                  ORDER BY date DESC, chdate DESC, topic ASC";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($range_id));
        $ret = $statement->fetchGrouped(PDO::FETCH_ASSOC);

        return ($as_objects ? StudipNews::GetNewsObjects($ret) : $ret);
    }

    public static function GetNewsByAuthor($user_id, $as_objects = false)
    {
        $ret = array();
        $query = "SELECT news_id AS idx, news.*
                  FROM news
                  WHERE user_id = ?
                  ORDER BY date DESC, chdate DESC";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user_id));
        $ret = $statement->fetchGrouped(PDO::FETCH_ASSOC);

        return ($as_objects ? StudipNews::GetNewsObjects($ret) : $ret);
    }

    public static function GetNewsByRSSId($rss_id, $as_objects = false){
        if ($user_id = StudipNews::GetUserIDFromRssID($rss_id)){
            return StudipNews::GetNewsByRange($user_id, true, $as_objects);
        } else {
            return array();
        }
    }

    public static function GetNewsObjects($news_result){
        $objects = array();
        if (is_array($news_result)){
            foreach($news_result as $id => $result){
                $objects[$id] = new StudipNews();
                $objects[$id]->setData($result, true);
                $objects[$id]->setNew(false);
            }
        }
        return $objects;
    }

    public static function GetUserIdFromRssID($rss_id){
        $ret = StudipNews::GetRangeIdFromRssID($rss_id);
        return $ret['range_id'];
    }

    public static function GetRssIdFromUserId($user_id){
        return StudipNews::GetRssIdFromRangeId($user_id);
    }

    public static function GetRangeFromRssID($rss_id){
        if ($rss_id){
            $query = "SELECT range_id ,range_type
                      FROM news_rss_range
                      WHERE rss_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($rss_id));
            $ret = $statement->fetch(PDO::FETCH_ASSOC);

            if (count($ret)) return $ret;
        }
        return false;
    }

    public static function GetRangeIdFromRssID($rss_id){
        $ret = StudipNews::GetRangeFromRssID($rss_id);
        return $ret['range_id'];
    }

    public static function GetRssIdFromRangeId($range_id)
    {
        $query = "SELECT rss_id FROM news_rss_range WHERE range_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($range_id));
        return $statement->fetchColumn();
    }

    public static function SetRssId($range_id, $type = false)
    {
        if (!$type){
            $type = get_object_type($range_id);
            if ($type == 'fak') $type = 'inst';
        }
        $rss_id = md5('StudipRss'.$range_id);

        $query = "REPLACE INTO news_rss_range (range_id,rss_id,range_type)
                  VALUES (?, ?, ?)";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $range_id,
            $rss_id,
            $type
        ));
        return $statement->rowCount();
    }

    public static function UnsetRssId($range_id)
    {
        $query = "DELETE FROM news_rss_range WHERE range_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($range_id));
        return $statement->rowCount();
    }

    public static function GetAdminMsg($user_id, $date){
        return sprintf(_("Zuletzt aktualisiert von %s (%s) am %s"),get_fullname($user_id) ,get_username($user_id) ,date("d.m.y",$date));
    }

    public static function DoGarbageCollect(){
        $db = DBManager::get();
        if (!Config::GetInstance()->getValue('NEWS_DISABLE_GARBAGE_COLLECT')){
            $result = $db->query(
                                "SELECT news.news_id FROM news where (date+expire)<UNIX_TIMESTAMP()
                                UNION DISTINCT
                                SELECT news_range.news_id FROM news_range LEFT JOIN news USING (news_id) WHERE ISNULL(news.news_id)
                                UNION DISTINCT
                                SELECT news.news_id FROM news LEFT JOIN news_range USING (news_id) WHERE range_id IS NULL"
                                )->fetchAll(PDO::FETCH_COLUMN, 0);

            if (count($result) > 0) {
                $query = "DELETE FROM news WHERE news_id IN (?)";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($result));
                $killed = $statement->rowCount();

                $query = "DELETE FROM news_range WHERE news_id IN (?)";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($result));

                object_kill_visits(null, $result);
                object_kill_views($result);
                StudipComment::DeleteCommentsByObject($result);
            }
            return $killed;
        }
    }

    public static function TouchNews($news_id, $touch_stamp = null){
        $ret = false;
        if(!$touch_stamp) $touch_stamp = time();
        $news = new StudipNews($news_id);
        if (!$news->isNew()) {
            $news->setValue('date', mktime(0,0,0,strftime("%m",$touch_stamp),strftime("%d",$touch_stamp),strftime("%y",$touch_stamp)));
            if (!$news->store()) {
                $news->triggerChdate();
            }
        }
        return $ret;
    }

    public static function DeleteNewsRanges($range_id){
        $ret = DBManager::get()->exec("DELETE FROM news_range WHERE range_id='$range_id'");
        StudipNews::DoGarbageCollect();
        return $ret;
    }

    public static function DeleteNewsByAuthor($user_id){
        foreach (StudipNews::GetNewsByAuthor($user_id, true) as $news){
            $deleted += $news->delete();
        }
        return $deleted;
    }

    /**
     *
     * @param string $id primary key of table
     */
    function __construct($id = null)
    {
        $this->db_table = 'news';
        $this->has_many['news_ranges'] = array('class_name' => 'NewsRange',
                                                            'assoc_foreign_key' => 'news_id',
                                                            'on_delete' => 'delete',
                                                            'on_store' => 'store'
                                                           );
        $this->has_many['comments'] = array('class_name' => 'StudipComment',
                                            'assoc_foreign_key' => 'object_id',
                                            'on_delete' => 'delete',
                                            'on_store' => 'store');
        $this->belongs_to['owner'] = array('class_name' => 'User',
                                            'foreign_key' => 'user_id');
        parent::__construct($id);
    }

    function restoreRanges() {
        $this->resetRelation('news_ranges');
        return count($this->news_ranges);
    }

    function getRanges() {
        $ranges = $this->news_ranges->pluck('range_id');
        return $ranges;
    }

    function issetRange($range_id) {
        return array_search($range_id, $this->getRanges()) !== false;
    }

    function addRange($range_id) {
        if (!$this->issetRange($range_id)) {
            $range = new NewsRange(array($this->getId(), $range_id));
            if ($range->isNew()) {
                $range->range_id = $range_id;
                $range->news_id = $this->getId();
            }
            $this->news_ranges[] = $range;
            return true;
        } else {
            return false;
        }
    }

    function deleteRange($range_id) {
        if ($this->issetRange($range_id)) {
            return $this->news_ranges->unsetBy('range_id', $range_id);
        } else {
            return false;
        }
    }

    function storeRanges()
    {
        $this->storeRelations();
    }

    function delete() {
        object_kill_visits(null, $this->getId());
        object_kill_views($this->getId());
        return parent::delete();
    }
}
