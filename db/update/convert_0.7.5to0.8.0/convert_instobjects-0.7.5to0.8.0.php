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
<tr><td class="blank" colspan=2>&nbsp;</td></tr>
<tr valign=top align=middle>
    <td class="table_header_bold"colspan=2 align="left"><b>&nbsp;Nachtragen von Diskussionsordner und Dateiordner f&uuml;r Institute</b></td>
</tr>
<tr><td class="blank" colspan=2>&nbsp;</td></tr>

<?

$db=new DB_Seminar;
$db1=new DB_Seminar;
$db2=new DB_Seminar;
$db3=new DB_Seminar;


$i = 0;
$db->query("select Institut_id from Institute");
while ($db->next_record()) {
    ## Create default folder and discussion
    CreateTopic('Allgemeine Diskussionen', " ", 'Hier ist Raum für allgemeine Diskussionen', 0, 0, $db->f("Institut_id"), 0);
    $db2->query("INSERT INTO folder SET folder_id='".md5(uniqid(rand()))."', range_id='".$db->f("Institut_id")."',name='Allgemeiner Dateiordner', description='Ablage für allgemeine Ordner und Dokumente der Einrichtung', mkdate='".time()."', chdate='".time()."'");
    ## Delete old date entries for institutes, once upon a time, it wsas possibly to create dates for inst :)
    $db2->query("DELETE FROM termine WHERE range_id = '".$db->f("Institut_id")."' ");
    ## Delete old date entries for institutes, once upon a time, it wsas possibly to create dates for inst :)
    $db2->query("UPDATE Institute SET type='1' WHERE Institut_id = '".$db->f("Institut_id")."' ");
    $i++;
    }
echo $i, "Datei- und Diskussionsordner angelegt.";

?>
</body>
</html>
