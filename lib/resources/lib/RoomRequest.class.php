<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* RoomRequest.class.php
* 
* class for room requests and room-property requests
* 
*
* @author		Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access		public
* @modulegroup		resources
* @module		AssignObject.class.php
* @package		resources
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// RoomRequest.class.php
// zentrale Klasse Raumwuensche und Raumeigenschaftswuensche
// Copyright (C) 2004 Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
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

require_once('lib/log_events.inc.php');
require_once($GLOBALS['RELATIVE_PATH_RESOURCES'] .'/lib/ResourcesUserRoomsList.class.php');

/**
* RoomRequest, class for room-requests and room-property-requests
*
* @access	public	
* @author	Cornelis Kater <kater@data-quest.de>
* @package	resources
**/
class RoomRequest {
	var $id;					//request-id
	var $seminar_id;				//seminar_id from the assigned seminar
	var $properties = array();			//the assigned property-requests
	var $last_search_result_count;			//the number of found rooms from last executed search
	var $reply_comment;
	var $category_id;
	var $chng_flag;
	var $user_id;
	var $isNewObject;
	var $resource_id;
	var $termin_id;
	var $comment;
	var $closed;
	var $default_seats;
	var $mkdate;
	var $chdate;

	//Konstruktor
	function RoomRequest($id='') {
		global $RELATIVE_PATH_RESOURCES, $user;
		
		$this->user_id = $user->id;

		if($id) {
			$this->id =$id;
			if (!$this->restore($this->id)) 
				$this->isNewObject = TRUE;
		} else {
			if (!$this->id)
				$this->id=$this->createId();
			$this->isNewObject =TRUE;
		} 	
	}

	function createId() {
		return md5(uniqid("wintergoe",1));
	}
	
	/* TEST: brauchen wir das wirklich irgendwo?
	function create() {
		$query = sprintf("SELECT assign_id FROM resources_assign WHERE assign_id ='%s' ", $this->id);
		$this->db->query($query);
		if ($this->db->nf()) {
			$this->chng_flag=TRUE;
			return $this->store();
		} else
			return $this->store(TRUE);
	}
	*/
	function getId() {
		return $this->id;
	}
	
	function getResourceId() {
		return $this->resource_id;
	}
	
	function getSeminarId() {
		return $this->seminar_id;
	}

	function getTerminId() {
		return $this->termin_id;
	}
	
	function getUserId() {
		return $this->user_id;
	}
	
	function getCategoryId() {
		return $this->category_id;
	}
	
	function getComment() {
		return $this->comment;
	}

	function getReplyComment() {
		return $this->reply_comment;
	}

	function getClosed() {
		return $this->closed;
	}
	
	function getPropertyState($property_id) {
		return $this->properties[$property_id]["state"];
	}

	function getProperties() {
		return $this->properties;
	}
	
	function getAvailableProperties() {
		if ($this->category_id) {
			$query = sprintf("SELECT b.name, b.type, b.system, b.property_id FROM resources_categories_properties a LEFT JOIN resources_properties b USING (property_id) WHERE requestable ='1' AND category_id = '%s' ", $this->category_id);
			$db = DBManager::get();
			foreach( $db->query( $query ) as $row ) {
				$available_properties[$row["property_id"] ] = array("name"=>$row["name"], "type"=>$row["type"], "system"=>$row["system"] );
			}
			return $available_properties;
		} else 
			return FALSE;
	}
	
	function getSettedPropertiesCount() {
		$count = 0;
		foreach ($this->properties as $val) {
			if ($val)
				$count++;
		}
		return $count;
	}
	
	function getSeats() {
		foreach ($this->properties as $val) {
			if ($val["system"] == 2)
				return $val["state"];
		}
		return FALSE;
	}
	
	function isNew() {
		return $this->isNewObject;
	}
	
	function setResourceId($value) {
		$this->resource_id=$value;
		$this->chng_flag=TRUE;
	}

	function setUserId($value) {
		$this->user_id=$value;
		$this->chng_flag=TRUE;
	}
	
	function setSeminarId($value) {
		$this->seminar_id=$value;
		$this->chng_flag=TRUE;
	}
	
	function setCategoryId($value) {
		if ($value != $this->category_id) {
			$this->properties = array();
			$this->category_id=$value;
			$this->chng_flag=TRUE;			
			if ($this->default_seats) {
				foreach ($this->getAvailableProperties() as $key=>$val) {
					if ($val["system"] == 2) {
						$this->setPropertyState($key, $this->default_seats);
					}
				}
			}
		}
	}	

	function setComment($value) {
		$this->comment=$value;
		$this->chng_flag=TRUE;
	}
	
	function setReplyComment($value) {
		$this->reply_comment=$value;
		$this->chng_flag=TRUE;
	}

	/**
	 * this function changes the state of the room-request
	 *
	 * possible states are:
	 *  0 - room-request is open
	 *  1 - room-request has been edited, but no confirmation has been sent
	 *  2 - room-request has been edited and a confirmation has been sent
	 *  3 - room-request has been declined
	 *
	 * @param integer $value one of the states 
	 */
	function setClosed($value) {
		$this->closed=$value;
		$this->chng_flag=TRUE;
	}
	
	function setTerminId($value) {
		$this->termin_id=$value;
		$this->chng_flag=TRUE;
	}

	function setPropertyState($property_id, $value) {
		if ($value)
			$this->properties[$property_id] = array("state" => $value);
		else
			$this->properties[$property_id] = FALSE;
	}
	
	function setDefaultSeats($value) {
		$this->default_seats=($value);
	}
	
	function searchRoomsToRequest($search_exp, $properties = false){
		$permitted_rooms = null;
		if(getGlobalPerms($GLOBALS['user']->id) != 'admin' && !Config::GetInstance()->getValue('RESOURCES_ALLOW_ROOM_REQUESTS_ALL_ROOMS')){
			$my_rooms = new ResourcesUserRoomsList($GLOBALS['user']->id, false, false, true);
			$global_resources = DBManager::get()
								->query("SELECT resource_id FROM resources_objects WHERE owner_id='global'")
								->fetchAll(PDO::FETCH_COLUMN);
			$permitted_rooms = array_unique(array_merge(array_keys($my_rooms->getRooms()), $global_resources));
		}
		return $this->searchRooms($search_exp, $properties, 0, 0, true, $permitted_rooms);
	}
	
	function searchRooms($search_exp, $properties = FALSE, $limit_lower = 0, $limit_upper = 0, $only_rooms = TRUE, $permitted_resources = FALSE) {
		$search_exp = mysql_escape_string($search_exp);
		//create permitted resource clause
		if (is_array($permitted_resources)) {
			$permitted_resources_clause="AND a.resource_id IN ('".join("','",$permitted_resources)."')";
		}

		//create the query
		if ($search_exp && !$properties)
			$query = sprintf ("SELECT a.resource_id, a.name FROM resources_objects a %s WHERE a.name LIKE '%%%s%%' %s ORDER BY a.name", ($only_rooms) ? "INNER JOIN resources_categories b ON (a.category_id=b.category_id AND is_room = 1)" : "", $search_exp, $permitted_resources_clause);

		//create the very complex query for room search AND room propterties search...	
		if ($properties) {
			$availalable_properties = $this->getAvailableProperties();
			$setted_properties = $this->getSettedPropertiesCount();
			$query = sprintf ("SELECT DISTINCT a.resource_id, b.name %s FROM resources_objects_properties a LEFT JOIN resources_objects b USING (resource_id) WHERE %s ", ($setted_properties) ? ", COUNT(a.resource_id) AS resource_id_count" : "", ($permitted_resources_clause) ? "1 ".$permitted_resources_clause." AND " : "");

			$i=0;
			if ($setted_properties) {
				foreach ($this->properties as $key => $val) {
					if ($val) {
						//let's create some possible wildcards
						if (ereg("<=", $val["state"])) {
							$val["state"] = trim(substr($val["state"], strpos($val["state"], "<")+2, strlen($val["state"])));
							$linking = "<=";
						} elseif (ereg(">=", $val["state"])) {
							$val["state"] = trim(substr($val["state"], strpos($val["state"], "<")+2, strlen($val["state"])));
							$linking = ">=";
						} elseif (ereg("<", $val["state"])) {
							$val["state"] = trim(substr($val["state"], strpos($val["state"], "<")+1, strlen($val["state"])));
							$linking = "<";
						} elseif (ereg(">", $val["state"])) {
							$val["state"] = trim(substr($val["state"], strpos($val["state"], "<")+1, strlen($val["state"])));
							$linking = ">";
						} elseif ($availalable_properties[$key]["system"] == "2") {
							$linking = ">=";
						} else $linking = "=";
						
						$query.= sprintf(" %s (property_id = '%s' AND state %s %s%s%s) ", ($i) ? "OR" : "", $key, $linking,  (!is_numeric($val["state"])) ? "'" : "", $val["state"], (!is_numeric($val["state"])) ? "'" : "");
						$i++;
					}
				}
			}
				
			if ($search_exp) 
				$query.= sprintf(" %s (b.name LIKE '%%%s%%' OR b.description LIKE '%%%s%%') ", ($setted_properties) ? "AND" : "", $search_exp, $search_exp);

			$query.= sprintf ("%s b.category_id ='%s' ", ($setted_properties) ? "AND" : "", $this->category_id);

			if ($setted_properties)
				$query.= sprintf (" GROUP BY a.resource_id  HAVING resource_id_count = '%s' ", $i);
			
			$query.= sprintf ("ORDER BY b.name %s", ($limit_upper) ? "LIMIT ".(($limit_lower) ? $limit_lower : 0).",".($limit_upper - $limit_lower) : "");
		}

		$db = DBManager::get();
		$result = $db->query( $query );
		
		$found = array();
		
		foreach( $result as $res ){
			if ($res["name"]) {
				$found [$res["resource_id"]] = $res["name"];
			}
		}

		$this->last_search_result_count = $result->rowCount();
		return $found;
	}

	function restore() {
		$query = sprintf("SELECT * FROM resources_requests WHERE request_id='%s' ",$this->id);
		$db = DBManager::get();
		$result = $db->query( $query );
		if( $res = $result->fetch() ) {
			$this->seminar_id = $res[ "seminar_id" ];
			$this->termin_id = $res["termin_id"];
			$this->mkdate = $res["mkdate"];
			$this->resource_id = $res["resource_id"];
			$this->user_id = $res["user_id"];
			$this->category_id = $res["category_id"];
			$this->comment = $res["comment"];
			$this->closed = $res["closed"];
			$this->chdate = $res["chdate"];
			$this->reply_comment = $res['reply_comment'];
			
			$query = sprintf("SELECT a.*, b.type, b.name, b.options, b.system FROM resources_requests_properties a LEFT JOIN resources_properties b USING (property_id) WHERE a.request_id='%s' ", $this->id);
			foreach ( $db->query( $query ) as $res ) {
				$this->properties[$res["property_id"]] = array("state"=>$res["state"], "type"=>$res["type"], "name"=>$res["name"], "options"=>$res["options"], "system"=>$res["system"], "mkdate"=>$res["mkdate"], "chdate"=>$res["chdate"]);
			}
			return TRUE;
		}
		return FALSE;
	}
	
	//private
	function cleanProperties() {
		$db = DBManager::get();
		foreach ($this->properties as $key => $val) {
			if ($val)
				$properties[] = $key;
		}
		if (is_array($properties)) {
			$in="('".join("','",$properties)."')";
		}
		$query = sprintf("DELETE FROM resources_requests_properties WHERE %s request_id = '%s' ", (is_array($properties)) ? "property_id  NOT IN ".$in." AND " : "", $this->id);
		$result = $db->exec( $query );
		return $result > 0 ;
	}
	
	//private
	function storeProperties() {
		$db = DBManager::get();
		foreach ($this->properties as $key=>$val) {
			$query = sprintf ("REPLACE INTO resources_requests_properties SET request_id = '%s', property_id = '%s', state = '%s', mkdate = '%s', chdate = '%s'", $this->id, $key, $val["state"], (!$val["mkdate"]) ? time() : $val["mkdate"], time());
			
			if ($db->exec( $query ))
				$changed = TRUE;
		}
		if ($this->cleanProperties())
			$changed = TRUE;
		
		return $changed;
	}
	
	function checkOpen($also_change = FALSE) {
		$db = DBManager::get();
		$existing_assign = false;
		//a request for a date is easy...
		if ($this->termin_id) {
			$query = sprintf ("SELECT assign_id FROM resources_assign WHERE assign_user_id = '%s' ", $this->termin_id);
			$result = $db->query( $query );
			if ($result->rowCount())
				$existing_assign = TRUE;
		//seminar request
		} else {
			$query = sprintf("SELECT count(termin_id)=count(assign_id) FROM termine LEFT JOIN resources_assign ON(termin_id=assign_user_id)
					WHERE range_id='%s' AND date_typ IN".getPresenceTypeClause(), $this->seminar_id);
			$result = $db->query( $query );
			$res = $result->fetch();
			if ( $res && $res[0] ){
				$existing_assign = TRUE;
			}
		}
		if($existing_assign && $also_change){
			$this->setClosed(1);
			$this->store();
		}
		return $existing_assign;
	}
	
	
	function copy() {
		$this->id = $this->createId();
		$this->isNewObject = TRUE;
		$this->chng_flag = TRUE;
	}

	function store(){
		$db = DBManager::get();
		// save only, if changes were made or the object is new and we have a resource_id or properties
		if ($this->chng_flag || ($this->isNew() && ($this->resource_id || $this->getSettedPropertiesCount())) ) {
			$chdate = time();
			$mkdate = time();

			if ($this->isNew()) {
				$query = sprintf("INSERT INTO resources_requests SET request_id='%s', resource_id='%s', " 
					."user_id='%s', seminar_id= '%s', termin_id = '%s', category_id = '%s', closed='%s', comment='%s', "
					."mkdate='%s' "
							 , $this->id, $this->resource_id, $this->user_id, $this->seminar_id, $this->termin_id, $this->category_id
							 , $this->closed, mysql_escape_string($this->comment), $mkdate);
				$this->isNewObject = FALSE;
				$changed = TRUE;
				// LOGGING
				$props="";
				foreach ($this->properties as $key => $val) {
					$props.=$val['name']."=".$val['state']." ";	
				}
				if (!$props) {
					$props="--";
				}
				log_event("RES_REQUEST_NEW",$this->seminar_id,$this->resource_id,"Termin: $this->termin_id, Properties: $props, Kommentar: $this->comment",$query);
			} else {
				$query = sprintf("UPDATE resources_requests SET resource_id='%s', " 
					."user_id='%s', seminar_id='%s', termin_id = '%s', category_id = '%s', comment='%s', "	
					."closed='%s', reply_comment = '%s' WHERE request_id='%s' "
							 , $this->resource_id, $this->user_id, $this->seminar_id, $this->termin_id, $this->category_id,  mysql_escape_string($this->comment)
							 , $this->closed,  mysql_escape_string($this->reply_comment), $this->id);
				// LOGGING
				$props="";
				foreach ($this->properties as $key => $val) {
					$props.=$val['name']."=".$val['state']." ";	
				}
				if (!$props) {
					$props="--";
				}
				if ($this->closed==1 || $this->closed==2) {
					log_event("RES_REQUEST_RESOLVE",$this->seminar_id,$this->resource_id,"Termin: {$this->termin_id}, Properties: $props, Status: ".$this->closed,$query);
				} else if ($this->closed==3) {
					log_event("RES_REQUEST_DENY",$this->seminar_id,$this->resource_id,"Termin: {$this->termin_id}, Properties: $props, Status: ".$this->closed,$query);
				} else {
					log_event("RES_REQUEST_UPDATE",$this->seminar_id,$this->resource_id,"Termin: {$this->termin_id}, Properties: $props, Status: ".$this->closed,$query);
				}
			}
			$result = $db->exec( $query );
			
			$changed_prop = $this->storeProperties();
			
			if ($result > 0) {
				$query = sprintf("UPDATE resources_requests SET chdate='%s' WHERE request_id='%s' ", $chdate, $this->id);
				$db->query( $query );
				$changed = TRUE;
			}
		}
		
		return ($changed || $changed_prop);
	}

	function delete() {
		$db = DBManager::get();
		$query = sprintf("DELETE FROM resources_requests_properties WHERE request_id='%s'", $this->id);
		
		$db->exec( $query );
		// LOGGING
		log_event("RES_REQUEST_DEL",$this->seminar_id,$this->resource_id,"Termin: $this->termin_id","");
		$query = sprintf("DELETE FROM resources_requests WHERE request_id='%s'", $this->id);
		return $db->exec( $query );
	}
}
?>
