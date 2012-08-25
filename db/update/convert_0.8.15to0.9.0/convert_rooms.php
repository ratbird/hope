<?php
/*
admin_bereich.php - Bereichs-Verwaltung von Stud.IP.
Copyright (C) 2000 Ralf Stockmann <rstockm@gwdg.de>, Cornelis Kater <ckater@gwdg.de>, Stefan Suchi <suchi@gmx.de>

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


## straight from the Seminars...
  page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", user => "Seminar_User"));
  $perm->check("root");


?>
<html>
 <head>
<!--
// here i include my personal meta-tags; one of those might be useful:
// <META HTTP-EQUIV="REFRESH" CONTENT="<?php print $auth->lifetime*60;?>; URL=logout.php">
-->
  <title>Stud.IP</title>
    <link rel="stylesheet" href="style.css" type="text/css">
 </head>

<body>


<?php
    include "seminar_open.php"; //hier werden die sessions initialisiert

// hier muessen Seiten-Initialisierungen passieren

    include "header.php";   //hier wird der "Kopf" nachgeladen 
    include "forum.inc.php";    
    
?>
<body>

<table border=0 bgcolor="#000000" align="center" cellspacing=0 cellpadding=0 width=100%>
<tr valign=top align=middle>
    <td class="table_header_bold"colspan=2 align="left"><b>&nbsp;Umwandeln der Eintr&auml;ge f&uuml;r R&auml;ume in neues Format</b></td>
</tr>

<?

$db=new DB_Seminar;
$db2=new DB_Seminar;

$level = 0;
$db->query("select Seminar_id, Ort, metadata_dates from seminare ");

while ($db->next_record()) {
    $term_data = unserialize ($db->f("metadata_dates"));
    if ($term_data["art"]==0) {
        if (is_array($term_data["turnus_data"]))
            foreach ($term_data["turnus_data"] as $key=>$val) {
                $term_data["turnus_data"][$key]["room"] = $db->f("Ort");
            }
    }
    $metadata = serialize ($term_data);
    $db2->query("update seminare set metadata_dates = '$metadata' where Seminar_id = '".$db->f("Seminar_id")."' ");
}

?>
</body>
</html>
