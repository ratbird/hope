<?php
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", user => "Seminar_User"));
$perm->check("root");
set_time_limit(0);

require_once($ABSOLUTE_PATH_STUDIP . "config.inc.php");

$db_read = new DB_Seminar();
$db_write = new DB_Seminar();
$count = 0;
$faults = array();

$query = "SELECT * FROM termine WHERE range_id = autor_id";

$db_read->query($query);
$num_rows = $db_read->num_rows();

echo "<b>$num_rows</b> pers&ouml;nliche Termine gefunden.<br>";

while ($db_read->next_record()) {
    
    if ($db_read->f("chdate") < $db_read->f("mkdate"))
        $chdate = $db_read->f("mkdate");
    else
        $chdate = $db_read->f("chdate");
    
    if ($db_read->f("type") == -1)
        $class = "PUBLIC";
    else
        $class = "PRIVATE";
    
    global $PERS_TERMIN_KAT;
    if (isset($PERS_TERMIN_KAT[$db_read->f("color")]))
        $category_intern = $db_read->f("color");
    else
        $category_intern = 0;
        
    $categories = "";
    
    switch ($db_read->f("priority")) {
        case 0:
            $priority = 0;
            break;
        case 1:
        case 2:
            $priority = 1;
            break;
        case 3:
        case 4:
            $priority = 2;
            break;
        default:
            $priority = 3;
    }
    
    if (!$expire = $db_read->f("expire"))
        $expire = 0;
    
    $repeat = explode(",", $db_read->f("repeat"));
    if (sizeof($repeat) > 1) {
        $rep = array();
        list($rep["ts"], $rep["linterval"], $rep["sinterval"], $rep["wdays"],
         $rep["month"], $rep["day"], $rep["rtype"], $rep["duration"]) = $repeat;
        if ($rep["duration"] == "#")
            $rep["duration"] = 1;
        if ($rep["rtype"] == "DAYLY")
            $rep["rtype"] = "DAILY";
    }
    else {
        $rep = array(
                "ts"        => mktime(12, 0, 0, date("n", $db_read->f("date")),
                               date("j",$db_read->f("date")),
                               date("Y", $db_read->f("date")), 0),
                "linterval" => 0,
                "sinterval" => 0,
                "wdays"     => "",
                "month"     => 0,
                "day"       => 0,
                "rtype"      => "SINGLE",
                "duration"  => "");
    }
    
    if ($rep["duration"] == "#")
        $rep["duration"] = 1;
    else
        $rep["duration"]  = floor(($db_read->f("end_time") - $db_read->f("date")) / 86400) + 1;
    
    $uid = "Stud.IP-" . $db_read->f("termin_id") . "@{$_SERVER['SERVER_NAME']}";
    
    $query  = sprintf("REPLACE calendar_events (event_id, range_id, autor_id, uid, start, end, summary, description,"
                    . "class, categories, category_intern, priority, location, ts, linterval, sinterval, wdays, month, day, rtype,"
                    . "duration, count, expire, exceptions, mkdate, chdate) VALUES ('%s','%s','%s','%s',%s,%s,'%s',"
                    . "'%s','%s','%s',%s,%s,'%s',%s,%s,%s,'%s',%s,%s,'%s',%s,%s,%s,'%s',%s,%s)",
                    $db_read->f("termin_id"),
                    $db_read->f("range_id"),
                    $db_read->f("autor_id"),
                    $uid,
                    $db_read->f("date"),
                    $db_read->f("end_time"),
                    addslashes($db_read->f("content")),
                    addslashes($db_read->f("description")),
                    $class,
                    $categories,
                    (int) $category_intern,
                    $priority,
                    addslashes($db_read->f("raum")),
                    $rep["ts"],
                    (int) $rep["linterval"],
                    (int) $rep["sinterval"],
                    $rep["wdays"],
                    (int) $rep["month"],
                    (int) $rep["day"],
                    $rep["rtype"],
                    $rep["duration"],
                    0,
                    $expire,
                    "",
                    $db_read->f("mkdate"),
                    $chdate);
    
    $db_write->query($query);
    if ($db_write->affected_rows()) {
        $query = "DELETE FROM termine WHERE termin_id = '" . $db_read->f("termin_id") . "'";
    
        $db_write->query($query);
    
        $count++;
        
        echo "Termin mit termin_id='" . $db_read->f("termin_id");
        echo "' &uuml;bertragen.<br> $count von $num_rows Terminen in Tabelle calendar_events &uuml;bertragen<br>\n";
    }
    else {
        $faults[] = $db_read->f("termin_id");
        echo "<font color=\"#FF0000\"><b>WARNUNG</b> Termin mit termin_id='" . $db_read->f("termin_id");
        echo " fehlerhaft. Der Termin wurde nicht aus der Tabelle termine entfernt!</font><br>\n";
    }
}

if ($count == $num_rows) {
    echo "<br>&Uuml;bertragung der pers&ouml;nlichen Termine aus Tabelle termine in Tabelle events<br>";
    echo "<b>erfolgreich</b> abgeschlossen!<br><br>Insgesamt $count Termine &uuml;bertragen.\n";
}
else {
    echo "<br><b><font color=\"#FF0000\">WARNUNG</b> Es wurden nicht alle Termine erfolgreich";
    echo "&uuml;bertragen!<br>Folgende Termine konnten nicht in der Tabelle calendar_events eingetragen werden:";
    echo "</font><br><br>\n";
    foreach ($faults as $fault) {
        echo "Termin mit termin_id = <b>'$fault'</b> fehlerhaft.<br>\n";
    }
    echo "&Uuml;berpr&uuml;fen Sie diese Termine manuell.";
}

page_close();

?>
