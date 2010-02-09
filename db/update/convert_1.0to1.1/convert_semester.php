<?php
/*
SemesterScript.php - Insertscript von Stud.IP 
by Mark Sievers <kursmanager@uos.de>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/


require_once ("$ABSOLUTE_PATH_STUDIP/lib/classes/SemesterData.class.php");
require_once ("$ABSOLUTE_PATH_STUDIP/lib/classes/HolidayData.class.php");
require_once ("$ABSOLUTE_PATH_STUDIP/config.inc.php");

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check("root");
// Semester and Holiday Insert Script. To insert the data from the global Variables SEMESTER
// and HOLIDAY, simply call this script. 
// Be careful when inserting the data, because the script should only be used, when the db-tables
// semester_data, semester_holiday are empty

if (semester_insert_into_semester_from_array($SEMESTER)) {
	echo "Folgende Semester wurden eingef&uuml;gt<br><br>";
	echo "<table border=1><tr>";
	echo "<th></th><th>Name</th><th>Beginn</th><th>Ende</th>";
	echo "</tr>";
	for ($i=1;$i<count($SEMESTER);$i++) {
		echo "<tr><td><b>".$i."</b></td><td>".$SEMESTER[$i]["name"]."</td><td>".date("d-m-Y",$SEMESTER[$i]["beginn"])."</td><td>".date("d-m-Y",$SEMESTER[$i]["ende"])."</td></tr>";
	}
	echo "</table>";
}
if (holiday_insert_into_semester_from_array($HOLIDAY)) {
	echo "Folgende Ferien wurden eingef&uuml;gt:<br><br>";
	echo "<table border=1><tr>";
	echo "<th></th><th>Name</th><th>Beginn</th><th>Ende</th>";
	echo "</tr>";
	for ($i=1;$i<count($HOLIDAY);$i++) {
		echo "<tr><td><b>".$i."</b></td><td>".$HOLIDAY[$i]["name"]."</td><td>".date("d-m-Y",$HOLIDAY[$i]["beginn"])."</td><td>".date("d-m-Y",$HOLIDAY[$i]["ende"])."</td></tr>";
	}
	echo "</table>";
}



function semester_insert_into_semester_from_array ($SEMESTER) {
	$semester = new SemesterData;
	$error = 0;
    //$db->query("use studip");
    for ($i=1; $i <= sizeof($SEMESTER); $i++) {
        $tmp_id=md5(uniqid("lesukfhsdkuh"));
		if (!$semester->insertNewSemester($SEMESTER[$i])) {
			$error++;
		}	
    }
	if ($error) {
		return 0;
	}
	return 1;
}


function holiday_insert_into_semester_from_array ($HOLIDAY) {
	$holiday = new HolidayData;
	$error = 0;
    //$db->query("use studip");
    for ($i=1; $i <= sizeof($HOLIDAY); $i++) {
        $tmp_id=md5(uniqid("lesukfhsdkuh"));
		if (!$holiday->insertNewHoliday($HOLIDAY[$i])) {
			$error++;
		}	
    }
	if ($error) {
		return 0;
	}
	return 1;
}



page_close();
?>


