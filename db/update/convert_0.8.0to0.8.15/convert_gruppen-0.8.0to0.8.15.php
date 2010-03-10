<?php
/*
convert_gruppen.php - Convertscript fuer Inst-Funktionen
Copyright (C) 2002 Ralf Stockmann <rstockm@gwdg.de>

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
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check("root");

include ("$ABSOLUTE_PATH_STUDIP/seminar_open.php"); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page

// Start of Output
include ("$ABSOLUTE_PATH_STUDIP/html_head.inc.php"); // Output of html head
include ("$ABSOLUTE_PATH_STUDIP/header.php");   // Output of Stud.IP head

require_once "$ABSOLUTE_PATH_STUDIP/config.inc.php";
require_once "$ABSOLUTE_PATH_STUDIP/statusgruppe.inc.php";

?>
<body>
<?

$db=new DB_Seminar;

?>
                
<table>             
    <tr>
        <td>
    
<?
    $i = 0;
    $j = 0;
    $db->query ("SELECT * FROM user_inst WHERE Funktion != '' AND Funktion != 'Student' AND Funktion != '0' AND Funktion != '1'");
    while ($db->next_record()) {
        $name = $INST_FUNKTION[$db->f("Funktion")][name];
        $statusgruppe_id = CheckStatusgruppe($db->f("Institut_id"),$name);
        if ($statusgruppe_id == FALSE) {
             $statusgruppe_id = AddNewStatusgruppe ($name, $db->f("Institut_id"), 0);
             echo "<br>*Statusgruppe ".$name." - ".$statusgruppe_id." f&uuml;r den Bereich ".$db->f("Institut_id")." angelegt."; 
             $i++;
        } else {
            echo "<br>Statusgruppe ".$statusgruppe_id."exisitiert bereits";
        }
        $write = InsertPersonStatusgruppe ($db->f("user_id"), $statusgruppe_id);
        if ($write == TRUE) {
            $j++;
        }
    }

    echo "<br><br><h1>Es wurden $i neue Gruppen angelegt und $j Personen zugeordnet.</h1>";
?>
        </td>
    </tr>
</table>
</body>
</html>
<?php
    
// Save data back to database.
page_close();
 ?>
