<?php
# Lifter007: TODO
# Lifter003: TEST
/**
* StudipDocument.class.php
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

/**
 * Small class derived from SimpleORMap to give access to the table dokumente
 * @author André Noack
 *
 */
class StudipDocument extends SimpleORMap {

    /**
     * returns new instance for given class and key
     * when found in db, else null
     * @param string primary key
     * @return StudipDocument object|NULL
     */
    static function find($id)
    {
        return SimpleORMap::find(__CLASS__,$id);
    }

    /**
     * returns array of instances of given class filtered by given sql
     * @param string sql clause to use on the right side of WHERE
     * @return array of StudipDocument
     */
    static function findBySql($where)
    {
        return SimpleORMap::findBySql(__CLASS__,$where);
    }

    /**
     * returns array of document-objects of given course id
     * @param string sql clause to use on the right side of WHERE
     * @return array of StudipDocument
     */
    static function findByCourseId($cid)
    {
        return self::findBySql("seminar_id = " . DBManager::get()->quote($cid));
    }

    /**
     * returns array of document-objects of given folder id
     * @param string sql clause to use on the right side of WHERE
     * @return array of StudipDocument
     */
    static function findByFolderId($folder_id)
    {
         return self::findBySql("folder_id = " . DBManager::get()->quote($folder_id));
    }

    /**
     * deletes table rows specified by given class and sql clause
     * @param string sql clause to use on the right side of WHERE
     * @return number
     */
    static function deleteBySql($where)
    {
        return SimpleORMap::deleteBySql(__CLASS__, $where);
    }

    /**
     * constructor
     * @param string $id primary key of table
     */
    function __construct($id = null)
    {
        $this->db_table = 'dokumente';
        parent::__construct($id);
    }
}
