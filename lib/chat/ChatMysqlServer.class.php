<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// ChatMysqlServer.class.php
// class definfition for the chat server
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
require_once $GLOBALS['RELATIVE_PATH_CHAT']."/ChatServer.class.php";
require_once $GLOBALS['RELATIVE_PATH_CHAT']."/MysqlHandler.class.php";

/**
*  Chat Server class (Mysql based)
*
*
* @access   public
* @author   André Noack <andre.noack@gmx.net>
* @package  Chat
*/
class ChatMysqlServer extends ChatServer {

    function ChatMysqlServer(){
        $this->that =& new MysqlHandler($db_host = CHAT_DB_HOST, $db_user = CHAT_DB_USER, $db_pass = CHAT_DB_PASS, $db_name = CHAT_DB_NAME , $table_name = CHAT_DB_TABLE_NAME);
        parent::ChatServer();
    }

}
?>
