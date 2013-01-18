<?php
/**
 * CronjobLog - Model for the database table "cronjobs_logs"
 *
 * @author      Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       2.4
 */

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// CronjobLog.class.php
//
// Copyright (C) 2013 Jan-Hendrik Willms <tleilax+studip@gmail.com>
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

class CronjobLog extends SimpleORMap
{
    /**
     * Defines the associated database table, relation to the schedule
     * and appropriate callbacks to encode/decode a possible exception.
     *
     * @param mixed $id Id of the log entry in question or null for a new entry
     */ 
    public function __construct($id = null)
    {
        $this->db_table = 'cronjobs_logs';

        $this->belongs_to['schedule'] = array(
            'class_name'  => 'CronjobSchedule',
            'foreign_key' => 'schedule_id',
        );

        $this->registerCallback('before_store', function ($item, $type) {
            $item->exception = serialize($item->exception ?: null);
        });
        $this->registerCallback('after_initialize', function ($item, $type) {
            $item->exception = unserialize($item->exception) ?: null;
        });

        parent::__construct($id);
    }
}