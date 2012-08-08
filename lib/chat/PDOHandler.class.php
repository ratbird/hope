<?php
# Lifter003: TEST
# Lifter007: TEST
# Lifter010: DONE - not applicable
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// PDOHandler.class.php
// simple wrapper class for persistent storage of php variables in Mysl db
// Copyright (c) 2002 André Noack <andre.noack@gmx.net>
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


/**
* Simple wrapper class for mysql based storage using PDO
*
* @access   public
* @author   André Noack <andre.noack@gmx.net>
* @package  Chat
*/

class PDOHandler
{
    /**
    * name of db table
    *
    * @access   private
    * @var      string
    */
    private $table_name;

    /**
    * constructor
    *
    * @access   public
    * @param    string  $db_name
    * @param    string  $table_name
    */
    public function __construct($table_name = "chat_data")
    {
        $this->table_name = $table_name;
    }

    /**
    * stores a variable in shared memory
    *
    * @access   public
    * @param    mixed   &$what  variable to store (call by reference)
    * @param    integer $key    the key under which to store
    */
    public function store(&$what, $key)
    {
        $query = "REPLACE INTO :table (id, data) VALUES (:key, :content)";
        $statement = DBManager::get()->prepare($query);
        $statement->bindValue(':table', $this->table_name, StudipPDO::PARAM_COLUMN);
        $statement->bindValue(':key', $key);
        $statement->bindValue(':content', serialize($what));
        $statement->execute();

        return true;
    }

    /**
    * restores a variable from shared memory
    *
    * @access   public
    * @param    mixed   &$what  variable to restore (call by reference)
    * @param    integer $key    the key from which to store
    */
    public function restore(&$what,$key)
    {
        $query = "SELECT data FROM :table WHERE id = :key";
        $statement = DBManager::get()->prepare($query);
        $statement->bindValue(':table', $this->table_name, StudipPDO::PARAM_COLUMN);
        $statement->bindValue(':key', $key);
        $statement->execute();

        if ($row = $staement->fetch()) {
            $what = unserialize($row['data']);
        }
        return true;
    }
}
