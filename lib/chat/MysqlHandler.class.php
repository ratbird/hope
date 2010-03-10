<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// MysqlHandler.class.php
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
* Simple wrapper class for mysql based sotrage
*
* 
*
* @access   public  
* @author   André Noack <andre.noack@gmx.net>
* @package  Chat
*/

class MysqlHandler {
    /**
    * name of database
    *
    * 
    * @access   private
    * @var      string
    */
    var $db_name;         
    
    /**
    * name of database host
    *
    * 
    * @access   private
    * @var      string
    */
    var $db_host;
    
    /**
    * name of database user
    *
    * 
    * @access   private
    * @var      string
    */
    var $db_user;
    
    /**
    * database password
    *
    * 
    * @access   private
    * @var      string
    */
    var $db_pass;
    
    /**
    * name of db table
    *
    * @access   private
    * @var      string
    */
    var $table_name;         
    
    /**
    * turn debug mode on/off
    *
    * @access   public
    * @var  boolean
    */
    var $debug = true;
    
    /**
    * database connection
    *
    * @access   public
    * @var  resource
    */
    
    var $connection;
    
        
    /**
    * constructor
    *
    * 
    *
    * @access   public
    * @param    string  $db_name
    * @param    string  $table_name
    */
    function MysqlHandler($db_host, $db_user, $db_pass, $db_name = "chat" ,$table_name = "chat_data") {
        $this->db_host = $db_host;
        $this->db_user = $db_user;
        $this->db_pass = $db_pass;
        $this->db_name = $db_name;
        $this->table_name = $table_name;
        $this->connectDB();
    }

    function connectDB(){
        $this->connection = @mysql_pconnect($this->db_host, $this->db_user, $this->db_pass);
        if (!$this->connection){
            $this->halt("Keine Verbindung zur Datenbank möglich: " . mysql_error());
        } else {
            if (!@mysql_select_db($this->db_name, $this->connection)){
                $this->halt("Öffnen der Datenbank fehlgeschlagen: " . mysql_error());
            }
        }
    
    }
    
    /**
    * stores a variable in shared memory
    *
    * @access   public  
    * @param    mixed   &$what  variable to store (call by reference)
    * @param    integer $key    the key under which to store
    */
    function store(&$what,$key) {
        $contents = mysql_escape_string(serialize($what));
        if (!$this->connection){
            $this->connectDB();
        }
        $con = mysql_query("REPLACE INTO {$this->table_name} (id, data) VALUES ($key, '$contents')");
        if (!$con){
            $this->halt("Fehler beim Schreiben von $key");
        }
        return true;
    }

    /**
    * restores a variable from shared memory
    *
    * @access   public  
    * @param    mixed   &$what  variable to restore (call by reference)
    * @param    integer $key    the key from which to store
    */
    function restore(&$what,$key) {
        if (!$this->connection){
            $this->connectDB();
        }
        $con = mysql_query("SELECT data FROM {$this->table_name} WHERE id=$key");
        $result = mysql_fetch_row($con);
        if ($contents = $result[0]){
                $what = unserialize($contents);
                mysql_free_result($con);
        }
        return true;
    }

    

    /**
    * print error message and exit script
    *
    * @access   private
    * @param    string  $msg    the message to print
    */
    function halt($msg){
        echo $msg."<br>";
        die;
    }
}
?>
