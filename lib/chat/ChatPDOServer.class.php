<?php
# Lifter007: TODO
# Lifter003: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// ChatPDOServer.class.php
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
require_once 'ChatServer.class.php';
require_once 'PDOHandler.class.php';

/**
*  Chat Server class (PDO based)
*
*
* @access	public
* @author	André Noack <andre.noack@gmx.net>
* @package	Chat
*/
class ChatPDOServer extends ChatServer {

	function ChatPDOServer(){
		$this->that =& new PDOHandler($table_name = CHAT_DB_TABLE_NAME);
		parent::ChatServer();
	}

}
?>
