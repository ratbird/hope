<?php
set_time_limit(0);

## convert globalmessages to message and message_user ##

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", user => "Seminar_User"));
$perm->check("root");

require_once $ABSOLUTE_PATH_STUDIP . "/functions.php";
require_once("$ABSOLUTE_PATH_STUDIP/messaging.inc.php");


$msging=new messaging;
$db = new DB_Seminar;
$db2 = new DB_Seminar;
$db3 = new DB_Seminar;

// $query = "SELECT * FROM globalmessages WHERE user_id_rec like 'rstockm'";
$query = "SELECT * FROM globalmessages";
$db->query($query);


while ($db->next_record()) {

	if ($db->f("user_id_snd") != "____%system%____") {
		$user_id_snd = get_userid($db->f("user_id_snd"));
		echo $user_id_snd;
	} else {
		$user_id_snd = "____%system%____";
	}
	$user_id_rec = get_userid($db->f("user_id_rec"));

	if ($db->f("user_id_rec")) {
		$db2->query ("INSERT IGNORE INTO message SET message_id='".$db->f("message_id")."', autor_id='".$user_id_snd."', mkdate='".$db->f("mkdate")."', message='".addslashes($db->f("message"))."'");
		$db3->query ("INSERT IGNORE INTO message_user SET message_id='".$db->f("message_id")."', user_id='".$user_id_rec."', snd_rec='rec', readed='1'");
		$db3->query ("INSERT IGNORE INTO message_user SET message_id='".$db->f("message_id")."', user_id='".$user_id_snd."', snd_rec='snd', deleted='1'");
		echo "erfolgreich von ".$db->f("user_id_snd")." an ".$db->f("user_id_rec")." konvertiert <hr>";
	} else
		echo "kein Empf&auml;nger : ".$db->f("user_id_rec")." mehr vorhanden, Nachricht wird nicht konvertiert <hr>";
}

page_close();
?>