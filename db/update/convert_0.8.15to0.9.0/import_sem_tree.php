<?php
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", user => "Seminar_User"));
$perm->check("root");

require_once $ABSOLUTE_PATH_STUDIP."/lib/classes/DbView.class.php";
require_once $ABSOLUTE_PATH_STUDIP."/config.inc.php";

$view = new DbView();
$view2 = new DbView();
$view3 = new DbView();
$view4 = new DbView();

$rs = $view->get_query("SELECT Name,fakultaets_id,type FROM Institute WHERE Institut_id=fakultaets_id ORDER BY Name");

echo "<h1>import_sem_tree.php</h1><pre>";
while($rs->next_record()){
    $fak_item_id = $view2->get_uniqid();
    $rs2 = $view2->get_query("INSERT INTO sem_tree (sem_tree_id,parent_id,priority,info,studip_object_id,name) VALUES 
                        ('$fak_item_id','root',1,'".mysql_escape_string("Die " . $INST_TYPE[$rs->f('type')]['name'] . " **"
                        . $rs->f("Name") . "**")."','" . $rs->f("fakultaets_id") . "','"
                        . mysql_escape_string($rs->f("Name")) . "')");
    echo ($rs2->affected_rows()) ? $rs->f("Name")." eingefügt\n" : "Fehler\n";
    $rs2 = $view2->get_query("SELECT c.name,c.beschreibung,c.fach_id FROM Institute a LEFT JOIN fach_inst b USING (Institut_id) LEFT JOIN faecher c USING(fach_id) 
                        WHERE a.fakultaets_id='".$rs->f("fakultaets_id")."' AND NOT ISNULL(c.fach_id) GROUP BY c.fach_id ORDER BY c.name");
    $fach_prio = 1;
    while ($rs2->next_record()){
        $fach_item_id = $view3->get_uniqid();
        $rs3 = $view3->get_query("INSERT INTO sem_tree (sem_tree_id,parent_id,priority,info,name) VALUES
                            ('$fach_item_id','$fak_item_id',". $fach_prio++ . ",'". mysql_escape_string($rs2->f('beschreibung')) .
                            "','".mysql_escape_string($rs2->f("name"))."')");
        echo ($rs3->affected_rows()) ? "\t".$rs2->f("name")." eingefügt\n" : "&nbsp;Fehler\n";
        $rs3 = $view3->get_query("SELECT b.name,b.beschreibung,b.bereich_id FROM bereich_fach a LEFT JOIN bereiche b USING(bereich_id)
                                WHERE a.fach_id ='".$rs2->f('fach_id')."' ORDER BY b.name");
        $bereich_prio = 1;
        while ($rs3->next_record()){
            $bereich_item_id = $view3->get_uniqid();
            $rs4 = $view4->get_query("INSERT INTO sem_tree (sem_tree_id,parent_id,priority,info,name) VALUES
                            ('$bereich_item_id','$fach_item_id',". $bereich_prio++ . ",'". mysql_escape_string($rs3->f('beschreibung')) .
                            "','".mysql_escape_string($rs3->f("name"))."')");
            echo ($rs4->affected_rows()) ? "\t\t".$rs3->f("name")." eingefügt\n" : "&nbsp;Fehler\n";
            $rs4 = $view4->get_query("INSERT INTO seminar_sem_tree (sem_tree_id,seminar_id) SELECT '$bereich_item_id',a.seminar_id FROM 
                                    seminar_bereich a LEFT JOIN seminar_inst b USING(seminar_id) WHERE bereich_id='".$rs3->f('bereich_id')."' 
                                    AND institut_id IN({1})","SELECT institut_id FROM Institute WHERE fakultaets_id='".$rs->f('fakultaets_id')."'");
            echo "\t\t\t".$rs4->affected_rows()." Veranstaltungen eingehängt\n";
        }
            
    }
}
page_close();
?>
