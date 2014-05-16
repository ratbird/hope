<?
//This is the old ruin of sms_send.php. Go to the all new trails controller instead.
//This redirect is only here so that plugins can still use the old links. But this
//URL is definitely DEPRECATED!

require '../lib/bootstrap.php';

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check("user");
header("Location: ".URLHelper::getURL("dispatch.php/messages/write", $_REQUEST));