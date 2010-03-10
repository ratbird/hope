<?php
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", user => "Seminar_User"));
$perm->check("root");
include_once "functions.php";
set_time_limit(0); //bis zum bitteren Ende...

function forum_kill_edit ($description) {
    $postmp = strpos($description,"%%[editiert von");
    $description = substr_replace($description,"",$postmp);
    return $description;
}

function forum_append_edit ($description, $name, $time) {
    $edit = "<admin_msg autor=\"".$name."\" chdate=\"".$time."\">";
    $description = forum_kill_edit($description).$edit;
    return $description;
}

$db=new DB_Seminar;
$db2=new DB_Seminar;
$c = 0;
$db->query("select * from px_topics");
while ($db->next_record()) {
    $desc = $db->f("description");
    if (ereg("%%\[editiert von",$desc)) { // wurde schon mal editiert
        $id = $db->f("topic_id");
        $postmp = strpos($desc,"%%[editiert von");
        $edittmp = substr($desc,$postmp+16);
        $tmp = explode(" am ",$edittmp);
        $name = $tmp[0];
        $tmp2 = explode(" - ",$tmp[1]);
        $time = mktime(substr($tmp2[1],0,2),substr($tmp2[1],3,2),"00",substr($tmp[1],3,2),substr($tmp[1],0,2),substr($tmp[1],6,4));
        $desc = addslashes(forum_append_edit($desc, $name, $time));
        
        $db2->query("UPDATE px_topics set description='$desc' WHERE topic_id = '$id'");
        if ($db2->affected_rows()) {
            echo $c." - ".$tmp[1]." umgewandelt<hr>";
        }
        $c++;
    }   
}

echo "uff, geschafft, es wurden $c Datensätze umgewandelt!";

page_close();
?>
