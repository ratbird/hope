<?php
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
require_once $GLOBALS['RELATIVE_PATH_CHAT']."/chat_config.php";
require_once $GLOBALS['RELATIVE_PATH_CHAT']."/ChatFileServer.class.php";
require_once $GLOBALS['RELATIVE_PATH_CHAT']."/ChatShmServer.class.php";
require_once $GLOBALS['RELATIVE_PATH_CHAT']."/ChatMysqlServer.class.php";
require_once $GLOBALS['RELATIVE_PATH_CHAT']."/ChatPDOServer.class.php";
require_once 'lib/visual.inc.php';
/**
*  Chat Server Klasse
*
*
* @access	public
* @author	André Noack <andre.noack@gmx.net>
* @package	Chat
*/
class ChatServer {

	var $that; // Container Objekt
	var $chatUser = array();
	var $chatDetail = array();
	var $caching = FALSE;

	function &GetInstance($class_name){
		static $object_instance;
		if (!is_object($object_instance[$class_name])){
			$object_instance[$class_name] = new $class_name();
		}
		return $object_instance[$class_name];
	}

	function ChatServer(){
		$this->restore();
	}

	function restore(){
		if ($this->caching) return;
		$this->that->restore($this->chatDetail,CHAT_DETAIL_KEY);
		if (!is_array($this->chatDetail))
			$this->chatDetail=array();
	}

	function store(){
		$this->that->store($this->chatDetail,CHAT_DETAIL_KEY);
	}

	function addChat($rangeid, $chatname = "Stud.IP Global Chat",$password = false){
		if ($this->isActiveChat($rangeid)){
			return false;
		}
		$this->chatDetail[$rangeid]["name"] = $chatname;
		$this->chatDetail[$rangeid]["messages"] = array();
		$this->chatDetail[$rangeid]["password"] = $password;
		$this->chatDetail[$rangeid]["users"] = array();
		$this->chatDetail[$rangeid]["id"] = md5(uniqid("chat",1));
		$this->chatDetail[$rangeid]["log"] = array();
		$this->store();
		return true;
	}

	function removeChat($rangeid){
		unset($this->chatDetail[$rangeid]);
		$this->store();
		return true;
	}

	function isActiveChat($rangeid){
		$this->restore();
		if (!$this->chatDetail[$rangeid]){
			return false;
		}
		$anzahl = $this->getActiveUsers($rangeid);
		if (!$anzahl){
			$this->removeChat($rangeid);
		}
		return $anzahl;
	}

	function getActiveUsers($rangeid){
		$chat_users = $this->getUsers($rangeid);
		$a_time = time();
		$anzahl = 0;
		foreach ($chat_users as $userid => $detail){
			if ((!$detail["perm"] && ($a_time-$detail["action"]) > CHAT_IDLE_TIMEOUT) ||
				($detail["perm"] && ($a_time-$detail["action"]) > CHAT_ADMIN_IDLE_TIMEOUT) ||
				( ($a_time - $detail['heartbeat']) > 30)){
				$this->removeUser($userid,$rangeid);
			}
			else
				++$anzahl;
		}
		return $anzahl;
	}

	function getUsers($rangeid){
		$this->restore();
		return (is_array($this->chatDetail[$rangeid]['users'])) ? $this->chatDetail[$rangeid]['users'] : array();
	}

	function getIdFromNick($rangeid,$nick){
		$this->restore();
		$chat_users = $this->getUsers($rangeid);
		foreach($chat_users as $userid => $detail){
			if ($detail["nick"] == $nick)
				return $userid;
		}
		return false;
	}

	function addUser($userid,$rangeid,$nick,$fullname,$chatperm,$color = "black"){
		if ($this->isActiveUser($userid,$rangeid))
			return false;
		$this->chatDetail[$rangeid]["users"][$userid]["action"] = time();
		$this->chatDetail[$rangeid]["users"][$userid]["heartbeat"] = time();
		$this->chatDetail[$rangeid]["users"][$userid]["nick"] = $nick;
		$this->chatDetail[$rangeid]["users"][$userid]["fullname"] = $fullname;
		$this->chatDetail[$rangeid]["users"][$userid]["perm"] = $chatperm;
		$this->chatDetail[$rangeid]["users"][$userid]["log"] = false;
		if (!$this->chatDetail[$rangeid]["users"][$userid]["color"])
			$this->chatDetail[$rangeid]["users"][$userid]["color"] = $color;
		$this->addMsg("system",$rangeid, sprintf(_("%s hat den Chat betreten!"),htmlReady($fullname." (".$nick.")")));
		$this->store();
		return true;
	}

	function getFullname($userid,$rangeid){
		return $this->chatDetail[$rangeid]["users"][$userid]["fullname"];
	}

	function getNick($userid,$rangeid){
		return $this->chatDetail[$rangeid]["users"][$userid]["nick"];
	}

	function getPerm($userid,$rangeid){
		return $this->chatDetail[$rangeid]["users"][$userid]["perm"];
	}

	function getAction($userid,$rangeid){
		return $this->chatDetail[$rangeid]["users"][$userid]["action"];
	}

	function getHeartbeat($userid,$rangeid){
		return $this->chatDetail[$rangeid]["users"][$userid]["heartbeat"];
	}

	function setHeartbeat($userid,$rangeid){
		if (isset($this->chatDetail[$rangeid]["users"][$userid])){
			$this->chatDetail[$rangeid]["users"][$userid]["heartbeat"] = time();
			$this->store();
		}
	}

	function removeUser($userid,$rangeid){
		if (!$this->isActiveUser($userid,$rangeid))
			return false;
		$this->removeCmdMsg($userid,$rangeid);
		$this->addMsg("system",$rangeid,sprintf(_("%s hat den Chat verlassen!"),htmlReady($this->getFullname($userid,$rangeid) ." (" . $this->getNick($userid,$rangeid) .")")));
		unset($this->chatDetail[$rangeid]["log"][$userid]);
		unset($this->chatDetail[$rangeid]["users"][$userid]);
		$this->store();
		return true;
	}

	function isActiveUser($userid,$rangeid){
		$this->restore();
		return $this->getHeartbeat($userid,$rangeid);
	}

	function addMsg($userid,$rangeid,$msg){
		$this->restore();
		$anzahl = count($this->chatDetail[$rangeid]["messages"]);
		if ($anzahl > CHAT_MAX_MSG) {
			array_shift($this->chatDetail[$rangeid]["messages"]);
			--$anzahl;
		}
		$this->chatDetail[$rangeid]["messages"][$anzahl] = array($userid,$msg,$this->getMsTime());
		if (substr($userid,0,6)!="system"){
			$this->chatDetail[$rangeid]["users"][$userid]["action"] = time();
		}
		$this->store();
	}

	function getMsg($rangeid,$msStamp = null){
		$this->restore();
		if (is_array($this->chatDetail[$rangeid]["messages"])){
			if (is_array($msStamp)) {
				$anzahl = count($this->chatDetail[$rangeid]["messages"]);
				for ($i = 0; $i < $anzahl; ++$i){
					if ($this->msTimeToFloat($this->chatDetail[$rangeid]["messages"][$i][2]) > $this->msTimeToFloat($msStamp))
					break;
				}
			} else {
				$i = 0;
			}
			if ($i == $anzahl && $i != 0){
				return false;
			} else {
				return array_slice($this->chatDetail[$rangeid]["messages"],$i);
			}
		}
		return false;
	}

	function removeCmdMsg($userid,$rangeid){
		$this->restore();
		$anzahl = count($this->chatDetail[$rangeid]["messages"]);
		for ($i = 0;$i < $anzahl; ++$i){
			if ($this->chatDetail[$rangeid]["messages"][$i][0] == $userid){
				if (substr($this->chatDetail[$rangeid]["messages"][$i][1],0,1) == "/") {
					$this->chatDetail[$rangeid]["messages"][$i][0] = "system:system";
				}
			}
		}
		$this->store();
	}

	function logoutUser($userid){
		if(is_array($this->chatDetail)){
			foreach($this->chatDetail as $chatid => $detail){
				$name = htmlReady($this->getFullname($userid,$chatid) . " (".$this->getNick($userid,$chatid).")");
				if ($this->removeUser($userid,$chatid)){
					$this->addMsg("system",$chatid,sprintf(_("%s hat sich aus Stud.IP ausgeloggt!"),$name));
				}
			}
		}
		return true;
	}

	function getAllChatUsers(){
		$this->restore();
		if (!$this->caching){
			$this->chatUser = array();
		}
		if (count($this->chatDetail) && !count($this->chatUser)){
			foreach($this->chatDetail as $chatid => $detail){
				if ($this->isActiveChat($chatid)){
					$users = $this->getUsers($chatid);
					foreach ($users as $user_id => $data){
						++$this->chatUser[$user_id];
					}
				}
			}
		}
		return count($this->chatUser);
	}

	function getAdminChats($user_id){
		$this->restore();
		$ret = false;
		if(count($this->chatDetail)){
			foreach($this->chatDetail as $chatid => $detail){
				if ($detail['users'][$user_id]['perm']){
					$ret[$chatid] = $detail['name'];
				}
			}
		}
		return $ret;
	}

	function getMsTime(){
		list($usec, $sec) = explode(" ",microtime());
		return array((int)($usec*1000) ,(int)$sec);
	}

	function msTimeToFloat($arg = null){
		if (!$arg){
			$arg = $this->getMsTime();
		}
		return ((float)($arg[0]/1000) + (float)($arg[1]));
	}

}
?>
