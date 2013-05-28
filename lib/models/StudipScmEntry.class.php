<?php
# Lifter003: DONE - not applicable
# Lifter007: TEST
# Lifter010: DONE - not applicable

/**
 * StudipScmEntry.class.php
 *
 * @author Andr� Noack <noack@data-quest>, Suchi & Berg GmbH <info@data-quest.de>
 * @author Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @access public
 */

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
//
// Copyright (C) 2006 Andr� Noack, Suchi & Berg GmbH <info@data-quest.de>
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
    /**
     * @param mixed $id primary key of table
     */
    function __construct($id = null)
    {
        $this->db_table = 'scm';

        $this->belongs_to = array(
            'user' => array(
                'class_name'  => 'User',
                'foreign_key' => 'user_id',
            ),
            'course' => array(
                'class_name'  => 'Course',
                'foreign_key' => 'range_id',
            ),
        );

        parent::__construct($id);
    }
}
