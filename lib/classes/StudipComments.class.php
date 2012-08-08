<?php
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO
/**
* StudipComments.class.php
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
// Copyright (C) 2005 Tobias Thelen ,   <tthelen@uni-osnabrueck.de>
//
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


class StudipComments extends SimpleORMap
{

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

    static function NumCommentsForObject($object_id)
    {
        $query = "SELECT COUNT(*) FROM comments WHERE object_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($object_id));
        return $statement->fetchColumn();
    }

    static function NumCommentsForObjectSinceLastVisit($object_id, $comments_since = 0, $exclude_user_id = null)
    {
        $query = "SELECT COUNT(*) FROM comments WHERE object_id = ? AND chdate > ?";
        $parameters = array($object_id, (int)$comments_since);
        if ($exclude_user_id) {
            $query .= " AND user_id != ?";
            $parameters[] = $exclude_user_id;
        }
        $statement = DBManager::get()->prepare($query);
        $statement->execute($parameters);
        return $statement->fetchColumn();
    }

    static function GetCommentsForObject($object_id)
     {
        global $_fullname_sql;
        $query = "SELECT comments.content, {$_fullname_sql['full']} AS fullname,
                         a.username, comments.mkdate, comments.comment_id
                  FROM comments
                  LEFT JOIN auth_user_md5 AS a USING (user_id)
                  LEFT JOIN user_info USING (user_id)
                  WHERE object_id = ?
                  ORDER BY comments.mkdate";
        $static = DBManager::get()->prepare($query);
        $statement->execute(array($object_id));
        return $statement->fetchAll(PDO::FETCH_BOTH);
    }

    static function DeleteCommentsByObject($object_ids)
    {
        if (!is_array($object_ids)) {
            $object_ids = array($object_ids);
        }
        $object_ids = array_map(array(DBManager::get(), 'quote'), $object_ids);

        $where = "object_id IN ('" . join("','", $object_ids). "')";
        return self::deleteBySQL($where);
    }

    /**
     *
     * @param string $id primary key of table
     */
    function __construct($id = null)
    {
        $this->db_table = 'comments';
        parent::__construct($id);
    }
}
