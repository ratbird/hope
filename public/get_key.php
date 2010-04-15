<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO

require '../lib/bootstrap.php';

ob_start();
$auto_challenge = md5(uniqid('bfuhpdsiufbpiasu',1));
$auto_id = md5(uniqid('asiqw34fhofw9ffes',1));
$fp = fopen($TMP_PATH.'/auto_key_'.$auto_id,'a');
fputs($fp,$auto_challenge);
fclose($fp);
$data  = 'var auto_key = "' . $auto_challenge . '";' . "\n";
$data .= 'var auto_id = "'.$auto_id . '";' . "\n";
chmod("$TMP_PATH/auto_key_$auto_id", 0600);
header("Content-type: application/x-javascript");
header("Pragma: no-cache");
header("Expires: Mon, 12 Dec 2001 08:00:00 GMT");
header("cache-control: no-cache");
header("Content-Length: " . strlen($data));
echo $data;
ob_end_flush();
die();
?>