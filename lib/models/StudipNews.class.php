<?php
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
//
// Copyright (C) 2005 Andr� Noack <noack@data-quest>,
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
 * @author   Andr� Noack <noack@data-quest>, Suchi & Berg GmbH <info@data-quest.de>
 * @author   Arne Schr�der <schroeder@data-quest>
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

    public static function GetNewsRangesByFilter($user_id, $area = '', $term = '', $startdate = 0, $enddate = 0, $as_objects = false, $limit = 100)
    {
        $news_result = array();
        if ($limit <= 0)
            return $news_result;
        $where_querypart = 'news.user_id = ?';
        $query_vars = array($user_id);
        if ($startdate) {
            $where_querypart .= " AND (date+expire) > ?";
            $query_vars[] = $startdate;
        }
        if ($enddate) {
            $where_querypart .= " AND date < ?";
            $query_vars[] = $enddate;
        }
        if ($term) {
            $where_querypart .= " AND topic LIKE CONCAT('%', ?, '%')";
            $query_vars[] = $term;
        }
        switch ($area) {
            case 'global': 
                $select_querypart = 'CONCAT(news_id, "_studip") AS idx, range_id, news.* ';
                $from_querypart = 'news_range INNER JOIN news USING(news_id)';
                $where_querypart .= ' AND range_id = ?';
                $order_querypart = 'news.date DESC, news.chdate DESC';
                $query_vars[] = 'studip';
                break;
            case 'sem': 
                $select_querypart = 'CONCAT(news_id, "_", range_id) AS idx, range_id, seminare.Name AS title, ' 
                    .'seminare.start_time AS start, news.*, seminare.start_time, sd1.name AS startsem, ' 
                    .'IF(seminare.duration_time=-1, "'._("unbegrenzt").'", sd2.name) AS endsem ';
                $from_querypart = 'news INNER JOIN news_range USING(news_id) INNER JOIN seminare ON Seminar_id = range_id '
                    .'LEFT JOIN semester_data sd1 ON (start_time BETWEEN sd1.beginn AND sd1.ende) '
                    .'LEFT JOIN semester_data sd2 ON (start_time + duration_time BETWEEN sd2.beginn AND sd2.ende)';
                $order_querypart = 'seminare.Name, news.date DESC, news.chdate DESC';
                //$semester = new SemesterData();
                break;
            case 'inst': 
                $select_querypart = 'CONCAT(news_id, "_", range_id) AS idx, range_id, Institute.Name AS title, news.* ';
                $from_querypart = 'Institute INNER JOIN news_range ON Institut_id = range_id INNER JOIN news USING(news_id)';
                $order_querypart = 'Institute.Name, news.date DESC, news.chdate DESC';
                break;
            case 'user': 
                $select_querypart = 'CONCAT(news_id, "_", auth_user_md5.user_id) AS idx, range_id, auth_user_md5.user_id AS userid, news.* ';
                $from_querypart = 'auth_user_md5 INNER JOIN news_range ON auth_user_md5.user_id = range_id INNER JOIN news USING(news_id)';
                $order_querypart = 'auth_user_md5.Nachname, news.date DESC, news.chdate DESC';
                break;
            default: 
                foreach (array('global', 'inst', 'sem', 'user') as $type) {
                    $add_news = StudipNews::GetNewsRangesByFilter($user_id, $type, $term, $startdate, $enddate, $as_objects, $limit);
                    if (is_array($add_news)) {
                        $limit = $limit - count($add_news[$type]);
                        $news_result = array_merge($news_result, $add_news);
                    }
                }
                return $news_result;
        }
        $query = "SELECT $select_querypart
                  FROM $from_querypart
                  WHERE $where_querypart
                  ORDER BY $order_querypart LIMIT 0, ?";
        $query_vars[] = $limit;
        $statement = DBManager::get()->prepare($query);
        $statement->execute($query_vars);
        $news_result = $statement->fetchGrouped(PDO::FETCH_ASSOC);
        if (is_array($news_result)) {
            foreach($news_result as $id => $result) {
                //if (StudipNews::haveRangePermission($result['range_id'], 'edit')) {
                    $objects[$area][$id]['range_id'] = $result['range_id'];
                    $objects[$area][$id]['title'] = $result['title'];
                    if ($area == 'sem') {
                        $objects[$area][$id]['semester'] .= sprintf('(%s%s)',
                        $result['startsem'],
                        $result['startsem'] != $result['endsem'] ? ' - ' . $result['endsem'] : '');
                    } elseif ($area == 'user') {
                        if ($GLOBALS['auth']->auth['uid'] == $result['userid'])
                            $objects[$area][$id]['title'] = _('Ank�ndigungen auf Ihrer Profilseite');
                        else
                            $objects[$area][$id]['title'] = sprintf(_('Ank�ndigungen auf der Profilseite von %s'), get_fullname($result['userid']));
                    } elseif ($area == 'global') {
                        $objects[$area][$id]['title'] = _('Ank�ndigungen auf der Stud.IP Startseite');
                    }
                    if ($as_objects) {
                        $objects[$area][$id]['object'] = new StudipNews();
                        $objects[$area][$id]['object']->setData($result, true);
                        $objects[$area][$id]['object']->setNew(false);
                    }
                //}
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

    /**
     * DEPRECATED
     */
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

    public static function haveRangePermission($operation, $range_id, $user_id = '') {
        static $news_range_perm_cache;
        if (isset($news_range_perm_cache[$user_id.$range_id.$operation]))
            return $news_range_perm_cache[$user_id.$range_id.$operation];
        if (!$user_id)
            $user_id = $GLOBALS['auth']->auth['uid'];
        if ($GLOBALS['perm']->have_perm('root', $user_id))
            return $news_range_perm_cache[$user_id.$range_id.$operation] = true;
        $type = get_object_type($range_id, array('global', 'sem', 'inst', 'fak', 'user'));
        switch($type) {
            case 'global': 
                if ($operation == 'view') 
                    return $news_range_perm_cache[$user_id.$range_id.$operation] = true;
                break;
            case 'fak': 
            case 'inst':
            case 'sem':
                if (($operation == 'view') AND ($_SESSION['SessionSeminar'] == $range_id))
                    return $news_range_perm_cache[$user_id.$range_id.$operation] = true;
                if (($operation == 'edit') OR ($operation == 'copy')) {
                    if ($GLOBALS['perm']->have_studip_perm('tutor', $range_id))
                        return $news_range_perm_cache[$user_id.$range_id.$operation] = true;
                }
                break; 
            case 'user':
                if ($operation == 'view') {
                    if (($range_id = $user_id) OR get_visibility_by_id($range_id))
                        return $news_range_perm_cache[$user_id.$range_id.$operation] = true;
                }
                elseif (($operation == 'edit') OR ($operation == 'copy')) {
                    if ($GLOBALS['perm']->have_profile_perm('user', $range_id))
                        return $news_range_perm_cache[$user_id.$range_id.$operation] = true;
                }
                break; 
        }
        return $news_range_perm_cache[$user_id.$range_id.$operation] = false;
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

    /**
     * checks, if user has permission to perform given operation on news object
     * 
     * @param string delete, unassign, edit, copy, or view
     * @param string specified range-id, used only for unassign-operation
     * @return boolean true or false
     */
    function havePermission($operation, $check_range_id = '') {
        if (!$user_id)
            $user_id = $GLOBALS['auth']->auth['uid'];
        if (!in_array($operation, array('delete', 'unassign', 'edit', 'copy', 'view')))
            return false;
        // in order to unassign, there must be more than one range assigned; $check_range_id must be specified.
        if (($operation == 'unassign') AND (count($this->getRanges()) < 2))
            return false;
        // root, owner, and owner's deputy have full permission
        if ($GLOBALS['perm']->have_perm('root', $user_id) 
              OR (($user_id == $this->user_id) AND $GLOBALS['perm']->have_perm('autor')) 
              OR (isDeputyEditAboutActivated() AND isDeputy($user_id, $this->user_id, true)))
            return true;
        // check news' ranges for edit, copy or view permission
        if (($operation == 'unassign') OR ($operation == 'delete'))
            $range_operation = 'edit';
        else
            $range_operation = $operation;
        foreach ($this->getRanges() as $range_id) {
            if (StudipNews::haveRangePermission($range_operation, $range_id, $user_id)) {
                // in order to view, edit, copy, or unassign, access to one of the ranges is sufficient
                if (($operation == 'view') OR ($operation == 'edit') OR ($operation == 'copy')) {
                    return true;
                // in order to unassign, access to the specified range is needed
                } elseif (($operation == 'unassign') AND ($range_id == $check_range_id)) {
                    return true;
                }
                // in order to delete, access to all ranges is necessary
                $permission_ranges++;
            } elseif ($operation == 'delete')
                return false;
        }
        if (($operation == 'delete') AND (count($this->getRanges()) == $permission_ranges))
            return true;
        return false;
    }
    
    function validate() {
        if (!$this->user_id AND $this->isNew()) {
            $this->user_id = $GLOBALS['auth']->auth['uid'];
            $this->author = get_fullname(false, 'full', false);
        }
        if (!$this->user_id OR !$this->author) {
            PageLayout::postMessage(MessageBox::error(_('Fehler: Personenangabe unvollst�ndig.')));
            return false;
        }
        if (!$this->topic) {
            PageLayout::postMessage(MessageBox::error(_('Bitte geben Sie einen Titel f�r die Ank�ndigung ein.')));
            return false;
        }
        if (!$this->body) {
            PageLayout::postMessage(MessageBox::error(_('Bitte geben Sie einen Inhalt f�r die Ank�ndigung ein.')));
            return false;
        }
        if (!count($this->getRanges())) {
            PageLayout::postMessage(MessageBox::error(_('Die Ank�ndigung muss mindestens einem Bereich zugeordnet sein.')));
            return false;
        }
        if ((int)$this->date < 1) {
            PageLayout::postMessage(MessageBox::error(_('Ung�ltiges Einstelldatum.')));
            return false;
        }
        if ((int)$this->expire < 1) {
            PageLayout::postMessage(MessageBox::error(_('Ung�ltiges Ablaufdatum.')));
            return false;
        }
        return true;
    }
}
