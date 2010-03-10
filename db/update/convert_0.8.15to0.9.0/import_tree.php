<?php
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", user => "Seminar_User"));
$perm->check("root");

require_once $ABSOLUTE_PATH_STUDIP."/lib/classes/DbView.class.php";

$view = new DbView();
$view2 = new DbView();
$rs = $view->get_query("SELECT Name,fakultaets_id FROM Institute WHERE Institut_id=fakultaets_id ORDER BY Name");

echo "<h1>import_tree.php</h1>";
while($rs->next_record()){
    $item_id = $view2->get_uniqid();
    $rs2 = $view2->get_query("INSERT INTO range_tree (item_id,parent_id,priority,studip_object,studip_object_id,name) VALUES 
                        ('$item_id','root',1,'fak','" . $rs->f("fakultaets_id") . "','" . mysql_escape_string($rs->f("Name")) . "')");
    echo ($rs2->affected_rows()) ? $rs->f("Name")." eingefügt<br>" : "Fehler<br>";
    $rs2 = $view2->get_query("SELECT Name,Institut_id FROM Institute WHERE fakultaets_id = '".$rs->f("fakultaets_id")."' AND Institut_id != '".$rs->f("fakultaets_id")."' ORDER BY Name");
    while ($rs2->next_record()){
        $view3 = new DbView();
        $rs3 = $view3->get_query("INSERT INTO range_tree (item_id,parent_id,priority,studip_object,studip_object_id,name) VALUES
                            ('".$view3->get_uniqid()."','$item_id',1,'inst','".$rs2->f("Institut_id")."','".mysql_escape_string($rs2->f("Name"))."')");
        echo ($rs3->affected_rows()) ? "&nbsp;".$rs2->f("Name")." eingefügt<br>" : "&nbsp;Fehler<br>";
    }
}
page_close();
?>
