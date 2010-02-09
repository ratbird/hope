<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// core.view.class.php
// Database views used within Stud.IP core
// 
// Copyright (c) 2003 André Noack <noack@data-quest.de> 
// Suchi & Berg GmbH <info@data-quest.de>
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


$GLOBALS["_views"]["AUTH_USER_UNAME"] = array("pk"=>"user_id","temp_table_type"=>"HEAP",
							"query"=>"SELECT * FROM auth_user_md5 WHERE username=? ");
$GLOBALS["_views"]["AUTH_USER_UID"] = array("pk"=>"user_id","temp_table_type"=>"HEAP",
							"query"=>"SELECT * FROM auth_user_md5 WHERE user_id=? ");
$GLOBALS["_views"]["USER_DATA_UNAME"] = array("pk"=>"user_id","temp_table_type"=>"HEAP",
							"query"=>"SELECT § FROM auth_user_md5 LEFT JOIN user_info USING(user_id) WHERE username=? ");
$GLOBALS["_views"]["GENERIC_UPDATE"] = array("query" => "UPDATE § SET §=? WHERE user_id=?");
$GLOBALS["_views"]["AUTH_USER_INSERT"] = array("query" => "INSERT INTO auth_user_md5 (user_id, username, perms, password, Vorname, Nachname, Email,auth_plugin) VALUES (?,?,?,?,?,?,?,?)");
$GLOBALS["_views"]["USER_INFO_INSERT"] = array("query" => "INSERT INTO user_info (user_id, mkdate, chdate, preferred_language) VALUES (?,?,?,?)");

$GLOBALS["_views"]["SEM_USER_INSERT"] = array("query" => "INSERT INTO seminar_user (Seminar_id, user_id, status, gruppe, mkdate) VALUES (?, ?, ?, §, UNIX_TIMESTAMP())");

$GLOBALS["_views"]["FOLDER_GET_DATA_BY_RANGE"] = array("pk"=>"folder_id","temp_table_type"=>"HEAP",
							"query"=>"SELECT * FROM folder WHERE range_id=? ORDER BY mkdate");
$GLOBALS["_views"]["FOLDER_GET_DATA_BY_THEMA"] = array("pk"=>"folder_id","temp_table_type"=>"HEAP",
							"query"=>"SELECT folder.* FROM themen INNER JOIN folder ON(issue_id=folder.range_id) WHERE themen.seminar_id=? ORDER BY priority");					
$GLOBALS["_views"]["FOLDER_GET_DATA_BY_GRUPPE"] = array("pk"=>"folder_id","temp_table_type"=>"HEAP",
							"query"=>"SELECT folder.* FROM statusgruppen INNER JOIN folder ON(statusgruppe_id=folder.range_id) WHERE statusgruppen.range_id=? ORDER BY position");					

$GLOBALS["_views"]["FOLDER_UPDATE_PERMISSION"] = array("query" => "UPDATE folder SET permission=? WHERE folder_id=?");
$GLOBALS["_views"]["FOLDER_UPDATE_CHDATE"] = array("query" => "UPDATE folder SET chdate=UNIX_TIMESTAMP() WHERE folder_id=?");

?>
