<?php
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", user => "Seminar_User"));
$perm->check("root");
include "functions.php";
set_time_limit(0); //bis zum bitteren Ende...


$db=new DB_Seminar;
$db2=new DB_Seminar;
$db3=new DB_Seminar;
$c = 0;
$d = 0;
$db->query("select * from statusgruppen");
while ($db->next_record()) {
    $i = 1;
    $statusgruppe_id = $db->f("statusgruppe_id");
    $db2->query("select * from statusgruppe_user WHERE statusgruppe_id = '$statusgruppe_id'");
    while ($db2->next_record()) {
        $user_id = $db2->f("user_id");
        $db3->query("UPDATE statusgruppe_user SET position='$i' WHERE user_id = '$user_id' AND statusgruppe_id = '$statusgruppe_id'");
        $i++;
        $c++;
    }
    $d++;
}

echo "uff, geschafft, es wurden $c Datensätze in $d Gruppen umgewandelt!";

page_close();
?>
