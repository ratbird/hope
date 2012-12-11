<?php
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO
/**
* StudipScmEntry.class.php
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
// Copyright (C) 2006 André Noack, Suchi & Berg GmbH <info@data-quest.de>
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

class StudipScmEntry extends SimpleORMap
{

    public static function GetSCMEntriesForRange($range_id, $as_objects = false){
        return SimpleORMapCollection::createFromArray(self::findByRange_id($range_id))->toGroupedArray();
    }

    public static function GetNumSCMEntriesForRange($range_id)
    {
        return self::countBySql("range_id = ?", array($range_id));
    }

    public static function DeleteSCMEntriesForRange($range_ids)
    {
        if (!is_array($range_ids)) {
            $range_ids = array($range_ids);
        }
        $where = "range_id IN (?)";
        return self::deleteBySQL($where, array($range_ids));
    }

    /**
     *
     * @param string $id primary key of table
     */
    function __construct($id = null)
    {
        $this->db_table = 'scm';
        parent::__construct($id);
    }

}

?>
