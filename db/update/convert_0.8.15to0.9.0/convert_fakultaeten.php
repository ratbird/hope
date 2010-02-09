<?php
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", user => "Seminar_User"));
$perm->check("root");

require_once $ABSOLUTE_PATH_STUDIP . "/lib/classes/DbView.class.php";
require_once $ABSOLUTE_PATH_STUDIP . "/forum.inc.php";
require_once $ABSOLUTE_PATH_STUDIP . "/visual.inc.php";

$view = new DbView();
$view2 = new DbView();

echo "<h1>convert_fakultaeten.php</h1>";

$rs = $view->get_query("INSERT INTO Institute (Institut_id,fakultaets_id,Name,mkdate,chdate,type)
						SELECT Fakultaets_id,Fakultaets_id,Name,mkdate,chdate,7 FROM Fakultaeten");

$rs = $view->get_query("SELECT Name,fakultaets_id FROM Institute WHERE Institut_id=fakultaets_id ORDER BY Name");
while ($rs->next_record()){
	CreateTopic('Allgemeine Diskussionen', " ", 'Hier ist Raum für allgemeine Diskussionen', 0, 0, $rs->f("fakultaets_id"), 0);
	$rs2 = $view2->get_query("INSERT INTO folder SET folder_id='".$view->get_uniqid()."', range_id='" . $rs->f("fakultaets_id") . "',
					name='Allgemeiner Dateiordner', description='Ablage für allgemeine Ordner und Dokumente der Einrichtung',
					mkdate='".time()."', chdate='".time()."'");
	echo "<b>Datei- und Forenordner für die Einrichtung \"".htmlReady($rs->f("Name"))."\" wurden angelegt.</b><br>";
}

page_close();
?>
