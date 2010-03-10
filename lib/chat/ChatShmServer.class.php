<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// ChatShmServer.class.php
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
require_once $GLOBALS['RELATIVE_PATH_CHAT']."/ShmHandler.class.php";

/**
*  Chat Server class (shared memory)
*
*
* @access   public
* @author   André Noack <andre.noack@gmx.net>
* @package  Chat
*/
class ChatShmServer extends ChatServer{

    function ChatShmServer(){
        $this->that = new ShmHandler($key=CHAT_SHM_KEY,$size=CHAT_SHM_SIZE*1024);
        parent::ChatServer();
    }

}
?>
