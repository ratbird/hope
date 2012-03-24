<?php
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", user => "Seminar_User"));
$perm->check("root");
include_once "functions.php";
include_once "visual.inc.php";
set_time_limit(0);

$db=new DB_Seminar;
$db2=new DB_Seminar;
$db->Halt_On_Error = "yes";
$db2->Halt_On_Error = "yes";

print "<font size=\"-1\">"._("Durchsuche Archiv-Tabelle auf Veranstalungsnummer")." ...</font><br><br><font size=\"-2\">";

$i = 0;
$db->query("select * from archiv");
while ($db->next_record()) {
    $dump = $db->f("dump");

    $start  = "<tr><td width=\"15%\"><b>"._("Veranstaltungsnummer:")."&nbsp;</b></td><td width=75% align=left>";
    $end    = "</td></tr>";

    $dump1 = explode($start,$dump);

    print "<i>".$db->f("name")."</i>";

    if($dump1[1] == NULL)
        $html = "<font color=\"red\">"._(" ... besitzt keine Veranstaltungsnummer!")."</font><br>";
    else{   
        $dump2 = explode($end,$dump1[1]);
        $semnumber = $dump2[0];
        $html = "<font color=\"green\">";
        $html .= sprintf(_(" ... Veranstalungsnummer (%s) gefunden"),$semnumber);
        $db2->query("UPDATE archiv set VeranstaltungsNummer = '$semnumber' where seminar_id = '".$db->f("seminar_id")."' ");
        $db2->execute;
        $html .=  _(" und in Spalte 'VeranstaltungsNummer' geschrieben.<br>")."</font>";
    }
    echo $html;
    $c++;
}

echo "</font><font size=\"-1\"><br>Konvertierung abgeschlossen. Alle $c Indianerstämme sind ausgerottet worden!</font>";
page_close();
?>
