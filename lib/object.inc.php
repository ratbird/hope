<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* object.inc.php
*
* functions for object operations (Stud.IP-ojects/modules) as get/set viewdate, rates, favourites and more
*
*
* @author		Ralf Stockmann <rstockm@gwdg.de>, Cornelis Kater <kater@data-quest.de>, data-quest GmbH <info@data-quest.de>
* @access		public
* @modulegroup		functions
* @module		object.inc.php
* @package		studip_core
*/

//object.inc.php - Verwaltung von Objektoperationen
//Copyright (C) 2004 Ralf Stockmann <rstockm@gwdg.de>, Cornelis Kater <kater@data-quest.de>, data-quest GmbH <info@data-quest.de>
// This file is part of Stud.IP
// object.inc.php
// Funktionen fuer generische Objekt-Behandlungen (Stud.IP-Objekte/Module)
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


function object_set_visit_module($type){
	global $SessSemName;
	if (object_get_visit($SessSemName[1], $type, false, false) < object_get_visit($SessSemName[1], $SessSemName['class'], false, false)){
		object_set_visit($SessSemName[1], $type);
	}
}

/**
* This function saves the actual time as last visitdate for the given object, user and type
*
* @param	string	the id of the object (i.e. seminar_id, news_id, vote_id)
* @param	string	the type of visited object or module (i.e. news, documents, wiki - see /lib/classes/Modules.class.php for definitions)
* @param	string	the user who visited the object - if not given, the actual user is used
*
*/
function object_set_visit($object_id, $type, $user_id = '') {
	global $user;
	$now = time();
	if (!$user_id)
		$user_id = $user->id;

	$last_visit = object_get_visit($object_id, $type, FALSE, false , $user_id);

	if ($last_visit === false){
		$last_visit = 0;
	}

	$db=new DB_Seminar;
	$query = sprintf ("REPLACE INTO object_user_visits SET object_id = '%s', user_id ='%s', type='%s', visitdate='%s', last_visitdate = '%s'",
						$object_id, $user_id, $type, $now, $last_visit);
	$db->query($query);

	return object_get_visit($object_id, $type, FALSE, false, $user_id, true);
}

/**
* This function gets the (last) visit time for an object or module. If no information is found, the last visit of the open-object can bes used
*
* @param	string	the id of the object (i.e. seminar_id, news_id, vote_id)
* @param	string	the type of visited object or module (i.e. news, documents, wiki - see /lib/classes/Modules.class.php for definitions OR sem/inst, if the visit for the whole seminar was saved)
* @param	string	the return-mode: 'last' for the last visit, other for actual-visit
* @param	string	the user who visited the object - if not given, the actual user is used
* @param	string	the id of an open-object (seminar or inst), to gather information for last visit from the visit of the whole open-object
* @return	int	the timestamp of the last visit or FALSE
*
*/
function object_get_visit($object_id, $type, $mode = "last", $open_object_id = '', $user_id = '', $refresh_cache = false) {
	global $user;
	static $cache;

	if (!$user_id){
		$user_id = $user->id;
	}
	if (!$open_object_id && $open_object_id !== false){
		$open_object_id = $object_id;
	}
	if ($refresh_cache){
		$cache[$object_id][$type][$user_id] = null;
	}

	if ($cache[$object_id][$type][$user_id]) {
		if ($mode == "last")
			return $cache[$object_id][$type][$user_id]["last_visitdate"];
		else
			return $cache[$object_id][$type][$user_id]["visitdate"];
	}

	$db=new DB_Seminar;
	$query = sprintf ("SELECT visitdate, last_visitdate FROM object_user_visits WHERE object_id = '%s' AND user_id = '%s' AND type = '%s'",
			$object_id, $user_id, $type);
	$db->query($query);

	if ($db->next_record()) {
		$cache[$object_id][$type][$user_id] = array("last_visitdate" => $db->f("last_visitdate"), "visitdate" =>$db->f("visitdate"));
		if ($mode == "last")
			return $db->f("last_visitdate");
		else
			return $db->f("visitdate");
	//no visitdate for the object or modul - we have to gather the information from the studip-object (seminar or institute)
	} elseif ($open_object_id) {
		$query = sprintf ("SELECT visitdate, last_visitdate FROM object_user_visits WHERE object_id = '%s' AND user_id = '%s' AND (type = 'sem' OR type = 'inst')",
				$open_object_id, $user_id);
		$db->query($query);
		if ($db->next_record()) {
			if ($mode == "last")
				return $db->f("last_visitdate");
			else
				return $db->f("visitdate");
		} else
			return FALSE;

	} else
		return FALSE;
}

function object_kill_visits($user_id, $object_ids = false){
	if ($user_id || $object_ids){
		if ($user_id){
			$sql = " user_id='$user_id' ";
		} else {
			$sql = " 1 ";
		}
		if ($object_ids){
			if (!is_array($object_ids)){
				$object_ids = array($object_ids);
			}
			$sql .= "AND object_id IN('" . join("','", $object_ids) . "')";
		}
		$db = new DB_Seminar("DELETE FROM object_user_visits WHERE " . $sql);
		return $db->affected_rows();
	} else {
		return false;
	}
}

function object_add_view ($object_id) {
	$now = time();
	$db=new DB_Seminar;
	$db->query("SELECT * FROM object_views WHERE object_id = '$object_id'");
	if ($db->next_record()) { // wurde schon mal angeschaut, also hochzählen
		if (!in_array($object_id, $_SESSION['object_cache'])) {
			$views = $db->f("views")+1;
			$query = "UPDATE object_views SET chdate='$now', views='$views' WHERE object_id='$object_id'";
			$_SESSION['object_cache'][] = $object_id;
		}
	} else { // wird zum ersten mal angesehen, also counter anlegen
		$views = 1;
		$query = "INSERT INTO object_views (object_id,views,chdate) values ('$object_id', '$views', '$now')";
		$_SESSION['object_cache'][] = $object_id;
	}
	$db->query($query);
	return $views;
}

function object_kill_views($object_id){
	if (!is_array($object_id)) $cond = " object_id='$object_id'";
	else $cond = "object_id IN('".join("','", $object_id)."')";
	$db = new DB_Seminar("DELETE FROM object_views WHERE $cond ");
	return $db->affected_rows();
}

function object_switch_fav ($object_id) {
	global $user;
	$now = time();
	$db=new DB_Seminar;
	if (object_check_user($object_id,"fav") == "TRUE") { // gibt einen Eintrag, also aus Favoriten löschen...
		$db->query("DELETE FROM object_user WHERE object_id='$object_id' AND user_id = '$user->id' AND flag = 'fav'");
		$tmp = FALSE;
	} else { // in die Favoriten aufgenommen...
		$db->query("INSERT INTO object_user (object_id, user_id, flag, mkdate) values ('$object_id', '$user->id', 'fav', '$now')");
		$tmp = TRUE;
	}
	return $tmp;
}

function object_check_user ($object_id, $flag) {
	global $user;
	$db=new DB_Seminar;
	$db->query("SELECT * FROM object_user WHERE object_id = '$object_id' AND user_id = '$user->id' AND flag = '$flag'");
	if ($db->next_record())  // Der Nutzer hat hier einen Eintrag
		$tmp = TRUE;
	else
		$tmp = FALSE;
	return $tmp;
}

function object_add_rate ($object_id, $rate) {
	global $user;
	$rate = (int)$rate;
	if (object_check_user($object_id, "rate") == FALSE) {
		$now = time();
		$db=new DB_Seminar;
		$db->query("INSERT INTO object_user (object_id, user_id, flag, mkdate) values ('$object_id', '$user->id', 'rate', '$now')");
		$db->query("INSERT INTO object_rate (object_id, rate, mkdate) values ('$object_id', '$rate', '$now')");
		$txt = _("Sie haben das Objekt mit \"$rate\"  bewertet.");
	} else {
		$txt = _("Sie haben dieses Objekt bereits bewertet.");
	}
	return $txt;
}

function object_print_rate ($object_id) {
	$db=new DB_Seminar;
	$db->query("SELECT ROUND(avg(rate),1) as mittelwert FROM object_rate WHERE object_id = '$object_id'");
	if ($db->next_record()) {
		$tmp = $db->f("mittelwert");
		if ($tmp == 0)
			$tmp = "?";
		}
	return $tmp;
}



function object_print_rates_detail ($object_id) {
	$db=new DB_Seminar;
	for ($i = 1;$i<6;$i++)
		$tmp[$i] = 0;
	$db->query("SELECT DISTINCT count(rate) as count, rate FROM object_rate WHERE object_id = '$object_id' GROUP BY rate");
	while ($db->next_record())
		$tmp[$db->f("rate")] = $db->f("count");
	return $tmp;
}

function object_return_views ($object_id) {
	$db=new DB_Seminar;
	$db->query("SELECT views FROM object_views WHERE object_id = '$object_id'");
	if ($db->next_record())
		$views = $db->f("views");
	else
		$views = 0;
	return $views;
}

function object_return_ratecount ($object_id) {
	$db=new DB_Seminar;
	$db->query("SELECT count(rate) as count FROM object_rate WHERE object_id = '$object_id'");
	if ($db->next_record())
		$ratecount = $db->f("count");
	else
		$ratecount = 0;
	return $ratecount;
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
?>
