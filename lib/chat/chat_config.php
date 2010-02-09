<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* Config file for package: Chat
* 
*
* @author		André Noack <andre.noack@gmx.net>
* @access		public
* @modulegroup		chat_modules
* @module		chat_config
* @package		Chat
*/
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// chat_nicklist.php
// Shows the nicklist
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
* Shared Memory Key, must be unique  (used only with ChatShmServer)
* @const CHAT_SHM_KEY
*/
if (function_exists('ftok')){
	define("CHAT_SHM_KEY", ftok(__FILE__, 'S')); //works with PHP > 4.2.0
} else {
	define("CHAT_SHM_KEY",98374);    //muss eindeutig sein!!!
}

/**
* Shared Memory Size, in Kbytes (used only with ChatShmServer)
* @const CHAT_SHM_SIZE
*/
define("CHAT_SHM_SIZE",512);     //in Kbyte
/**
* Name of file used for data storage (used only with ChatFileServer)
* @const CHAT_FILE_NAME
*/
define("CHAT_FILE_NAME", "chat_data");
/**
* path used for data storage (used only with ChatFileServer)
* @const CHAT_FILE_NAME
*/
define("CHAT_FILE_PATH", $GLOBALS['TMP_PATH']);
/**
* database host (used only with ChatMysqlServer)
* @const CHAT_DB_HOST
*/
define("CHAT_DB_HOST", $GLOBALS['DB_STUDIP_HOST']);
/**
* database user (used only with ChatMysqlServer)
* @const CHAT_DB_USER
*/
define("CHAT_DB_USER", $GLOBALS['DB_STUDIP_USER']);
/**
* database password (used only with ChatMysqlServer)
* @const CHAT_DB_PASS
*/
define("CHAT_DB_PASS", $GLOBALS['DB_STUDIP_PASSWORD']);
/**
* database name (used only with ChatMysqlServer)
* @const CHAT_DB_NAME
*/
define("CHAT_DB_NAME", $GLOBALS['DB_STUDIP_DATABASE']);
/**
* database tablename (used only with ChatMysqlServer)
* @const CHAT_DB_TABLE_NAME
*/
define("CHAT_DB_TABLE_NAME", "chat_data");
/**
* Used for shm access, do not alter
* @const CHAT_USER_KEY
*/
define("CHAT_USER_KEY",1);       //am besten nicht ändern
/**
* Used for shm access, do not alter
* @const CHAT_DETAIL_KEY
*/
define("CHAT_DETAIL_KEY",2);     //dito
/**
* max Number of entries in one chat room
* @const CHAT_MAX_MSG
*/
define("CHAT_MAX_MSG",100);
/**
* Time in seconds before chat user gets kicked
* @const CHAT_IDLE_TIMEOUT
*/
define("CHAT_IDLE_TIMEOUT",1200);       //in Sekunden
/**
* Time in seconds before chat admin gets kicked
* @const CHAT_ADMIN_IDLE_TIMEOUT
*/
define("CHAT_ADMIN_IDLE_TIMEOUT",7200);       //in Sekunden
/**
* Time in microseconds for client to sleep
*
* A higher number means lower CPU usage on the server, but slower response times for the clients
* @const CHAT_SLEEP_TIME
*/
define("CHAT_SLEEP_TIME",500000);       //in usleep(micro s)
/**
* Time seconds to 'ping' the clients
*
* used to prevent browser timeouts 
* @const CHAT_TO_PREV_TIME
*/
define("CHAT_TO_PREV_TIME",2.5);       //in Sekunden
/**
* Show username or fullname in front of messages
*
* 
* @const CHAT_NICKNAME
*/
define("CHAT_NICKNAME", "fullname");       //"username" or "fullname"
/**
* Global array, contains pre-defined colors (use HTML compliant names)
* @var array $chatColors
*/
$chatColors = array("black","blue","green","orange","indigo","darkred","red","darkblue","maroon","pink");
/**
* Global array, contains chat commands with according help text
* @var array $chatCmd
*/
$chatCmd = array("quit" => _(" [msg] - Sie verlassen den Chat mit der Botschaft [msg]"),
			"color" => _(" [colorcode] - Ihre Schriftfarbe wird auf [colorcode] gesetzt"),
			"me" => _(" [msg] - Ihr Name wird zusammen mit [msg] vom Chatbot ausgegeben"),
			"private" => _(" [username][msg] - Die Botschaft [msg] wird geheim an [username] übermittelt"),
			"help" => _(" - Zeigt diesen Hilfetext"),
			"kick" => _(" [username] - Wirft [username] aus dem Chat wenn sie Chat-Admin sind, mit /kick all werfen sie alle anderen Nutzer aus dem Chat"),
			"sms" => _(" [username][msg] - Verschickt eine systeminterne SMS [msg] an [username]"),
			"invite" => _(" [username][msg] - Verschickt eine Chat-Einladung an [username] mit optionaler Nachricht [msg]"),
			"lock" => _(" - Setzt ein zufälliges Paßwort und wirft alle NutzerInnen aus dem Chat, die nicht Chat-Admins sind."),
			"unlock" => _(" - Ein eventuell gesetztes Passwort wird gelöscht, der Chat wird damit wieder frei zugänglich."),
			"password" => _(" [password] - Setzt das Passwort für den Chat, wenn [password] leer ist wird ein eventuell vorhandenes Passwort gelöscht"),
			"log" => _(" [start | stop | send] - Startet, beendet oder versendet eine Aufzeichnung, wenn sie Chat-Admin sind"));

?>
