<?php
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO
/**
* StudipNews.class.php
*
*
*
*
* @author   André Noack <noack@data-quest>, Suchi & Berg GmbH <info@data-quest.de>
* @access   public
*/

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

require_once 'lib/classes/SimpleORMap.class.php';
require_once 'lib/classes/StudipComments.class.php';
require_once 'lib/classes/Config.class.php';
require_once 'lib/object.inc.php';

class StudipNews extends SimpleORMap {

    public $ranges = array();

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
        if ($rss_id) {
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

            if (is_array($result)) {
                $query = "DELETE FROM news WHERE news_id IN (?)";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($result));
                $killed = $statement->rowCount();

                $query = "DELETE FROM news_range WHERE news_id IN (?)";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($result));

                object_kill_visits(null, $result);
                object_kill_views($result);
                StudipComments::DeleteCommentsByObject($result);
            }
            return $killed;
        }
    }

    public static function TouchNews($news_id, $touch_stamp = null){
        $ret = false;
        if(!$touch_stamp) $touch_stamp = time();
        $news = new StudipNews($news_id);
        if(!$news->isNew()){
            $news->setValue('date', mktime(0,0,0,strftime("%m",$touch_stamp),strftime("%d",$touch_stamp),strftime("%y",$touch_stamp)));
            $ret = $news->store();
            $news->triggerChdate();
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

    static function find($id)
    {
        return SimpleORMap::find(__CLASS__, $id);
    }

    static function findBySql($where)
    {
        return SimpleORMap::findBySql(__CLASS__, $where);
    }

    static function deleteBySql($where)
    {
        return SimpleORMap::deleteBySql(__CLASS__, $where);
    }

    /**
     *
     * @param string $id primary key of table
     */
    function __construct($id = null)
    {
        $this->db_table = 'news';
        parent::__construct($id);
    }

    function restore(){
        $ret = parent::restore();
        $this->restoreRanges();
        return $ret;
    }

    function restoreRanges(){
        $this->ranges = array();
        if (!$this->isNew()){
            $query = "SELECT range_id FROM :table WHERE news_id = :news_id";
            $statement = DBManager::get()->prepare($query);
            $statement->bindValue(':table', $this->db_table . '_range', StudipPDO::PARAM_COLUMN);
            $statement->bindValue(':news_id', $this->getId());
            $statement->execute();
            $ranges = $statement->fetchAll(PDO::FETCH_COLUMN);
            $this->ranges = array_flip($ranges);
        }
        return count($this->ranges);
    }

    function store(){
        $ret = parent::store();
        $this->storeRanges();
        return $ret;
    }

    function storeRanges(){
        $db = DBManager::get();
        if (!$this->isNew()){
            $where_query = $this->getWhereQuery();
            if ($where_query){
                $query = "DELETE FROM :table WHERE news_id = :news_id";
                $statement = DBManager::get()->prepare($query);
                $statement->bindValue(':table', $this->db_table . '_range', StudipPDO::PARAM_COLUMN);
                $statement->bindValue(':news_id', $this->getId());
                $statement->execute();

                if (count($this->ranges)){
                    $query = "INSERT INTO :table (range_id, news_id)
                              VALUES (:range_id, :news_id)";
                    $statement = DBManager::get()->prepare($query);
                    $statement->bindValue(':table', $this->db_table . '_range', StudipPDO::PARAM_COLUMN);
                    $statement->bindValue(':news_id', $this->getId());

                    foreach ($this->getRanges() as $range_id) {
                        $statement->bindValue(':range_id', $range_id);
                        $statement->execute();
                    }
                }
                return count($this->ranges);
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    function getRanges(){
        return array_keys($this->ranges);
    }

    function issetRange($range_id){
        return isset($this->ranges[$range_id]);
    }

    function addRange($range_id){
        if (!$this->issetRange($range_id)){
            return ($this->ranges[$range_id] = true);
        } else {
            return false;
        }
    }

    function deleteRange($range_id){
        if ($this->issetRange($range_id)){
            unset($this->ranges[$range_id]);
            return true;
        } else {
            return false;
        }
    }

    function setData($data, $reset = false){
        $count = parent::setData($data, $reset);
        if ($reset){
            $this->restoreRanges();
        }
        return $count;
    }

    function delete() {
        $this->ranges = array();
        $this->storeRanges();
        object_kill_visits(null, $this->getId());
        object_kill_views($this->getId());
        StudipComments::DeleteCommentsByObject($this->getId());
        parent::delete();
        return true;
    }
}
/*
$test =& StudipNews::GetNewsByRange('1c4aacc51b8feea444d85d7183bff9fe');
echo "<pre>";
print_r($test);
$test =& StudipNews::GetNewsByRange('1c4aacc51b8feea444d85d7183bff9fe', true);
print_r($test);
*/
?>
