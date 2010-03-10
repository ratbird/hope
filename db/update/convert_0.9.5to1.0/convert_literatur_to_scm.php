<?php
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// 
// 
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

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check("root");

require_once("$ABSOLUTE_PATH_STUDIP/lib/classes/Modules.class.php");

$modules = new Modules();

echo "<h1>Konvertierung Literatur / Links -> Simple Content Module</h1>";
$db = new DB_Seminar("SELECT range_id, IF (s.Name IS NULL , CONCAT( 'Einrichtung: ', i.Name ) , CONCAT( 'Veranstaltung: ', s.Name )) AS name
                    FROM literatur LEFT JOIN seminare s ON ( range_id = Seminar_id )  LEFT JOIN Institute i ON ( range_id = i.Institut_id ) ");
while($db->next_record()){
    if ($modules->writeStatus("scm", $db->f("range_id"), 1)){
        $modules->writeStatus("literature", $db->f("range_id"), 0);
        echo $db->f("name") . " <b>SCM eingeschaltet, Literaturverwaltung ausgeschaltet</b><br>";
    } else {
        echo $db->f("name") . " <b>SCM ist schon eingeschaltet</b><br>";
    }
}

$db->query("INSERT IGNORE INTO scm (scm_id, range_id, user_id, tab_name, content, mkdate, chdate)
 SELECT literatur_id,range_id,user_id,'Literatur/Links',
 CONCAT_WS('\n','__**Literatur:**__',literatur,'\n__**Links:**__',links) ,mkdate,chdate FROM literatur
");
echo $db->affected_rows() . " Einträge in SCM geschrieben.";
echo "<br>Hugh, ich habe gesprochen.";
page_close();
?>
