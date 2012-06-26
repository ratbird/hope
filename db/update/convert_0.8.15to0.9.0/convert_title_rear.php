<?php
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check("root");
include("html_head.inc.php");
?>
<script type="text/javascript">
function invert_selection(){
    my_elements = document.forms[0].elements['convertible[]'];
    for(i = 0; i < my_elements.length; ++i){
        if(my_elements[i].checked)
            my_elements[i].checked = false;
        else
            my_elements[i].checked = true;
    }
}
</script>
<?
$db = new DB_Seminar();
$db1 = new DB_Seminar();
echo "<h1>Titel nachgestellt Konvertierung</h1>";
if($_REQUEST['convertible']){
    $conv = $_REQUEST['convertible'];
    $query = "SELECT user_id, SUBSTRING(Nachname,LENGTH(SUBSTRING_INDEX(Nachname,' ',1))+1) AS title_rear,
  IF(RIGHT(SUBSTRING_INDEX(Nachname,' ',1),1)=',',LEFT(SUBSTRING_INDEX(Nachname,' ',1),LENGTH(SUBSTRING_INDEX(Nachname,' ',1))-1),SUBSTRING_INDEX(Nachname,' ',1)) AS new_nachname
  FROM auth_user_md5 WHERE user_id IN('".join("','",$conv)."')";
    $db->query($query);
    while($db->next_record()){
        $db1->query("UPDATE user_info SET title_rear='".trim($db->f("title_rear"))."' WHERE user_id='".$db->f("user_id")."'");
        $db1->query("UPDATE auth_user_md5 SET Nachname='".trim($db->f("new_nachname"))."' WHERE user_id='".$db->f("user_id")."'");
    }
    echo "<p><b>Es wurden ".$db->num_rows()." Datensätze aktualisiert.</b></p>";
}
$query= " SELECT user_id,username, SUBSTRING(Nachname,LENGTH(SUBSTRING_INDEX(Nachname,' ',1))+1) AS title_rear,
 IF(RIGHT(SUBSTRING_INDEX(Nachname,' ',1),1)=',',LEFT(SUBSTRING_INDEX(Nachname,' ',1),LENGTH(SUBSTRING_INDEX(Nachname,' ',1))-1),SUBSTRING_INDEX(Nachname,' ',1)) AS new_nachname,Vorname,Nachname FROM auth_user_md5
 WHERE SUBSTRING(Nachname,LENGTH(SUBSTRING_INDEX(Nachname,' ',1))+1) !='' ORDER BY SUBSTRING(Nachname,LENGTH(SUBSTRING_INDEX(Nachname,' ',1))+1)";
$db->query($query);
echo "<form action=\"".URLHelper::getLink()."\" method=\"post\">";
echo "<table  border=\"0\" cellspacing=\"2\" cellpadding=\"2\"><tr>";
echo "<th>username</th><th>Vorname</th><th>Nachname</th><th>new_nachname</th><th>titel_rear</th><th><a href=\"#\" onClick=\"invert_selection();\" title=\"Auswahl umkehren\">umwandeln ?</a></th></tr>";
while ($db->next_record()){
    echo"<tr><td class=\"blank\" align=\"center\">".$db->f("username")."</td>";
    echo"<td class=\"blank\" align=\"center\">".$db->f("Vorname")."</td>";
    echo"<td class=\"blank\" align=\"center\">".$db->f("Nachname")."</td>";
    echo"<td class=\"blank\" align=\"center\">".$db->f("new_nachname")."</td>";
    echo"<td class=\"blank\" align=\"center\">".$db->f("title_rear")."</td>";
    echo"<td class=\"blank\" align=\"center\"><input name=\"convertible[]\" checked type=\"checkbox\" value=\"" . $db->f("user_id") . "\"></td></tr>";
}
echo "<tr><td class=\"blank\" colspan=\"6\" align=\"right\"><input type=\"submit\" value=\"-OK-\"></td></tr>";
echo "</table></form></body></html>";
page_close();
?>
