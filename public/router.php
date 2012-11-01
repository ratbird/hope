<?
/*
 * Copyright (C) 2012 - Rasmus Fuhse <fuhse@data-quest.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require '../lib/bootstrap.php';

unregister_globals();

require_once 'lib/functions.php';
require_once 'lib/exceptions/AccessDeniedException.php';
require_once 'vendor/trails/trails.php';

# set base url for URLHelper class
URLHelper::setBaseUrl($CANONICAL_RELATIVE_PATH_STUDIP);

# initialize Stud.IP-Session
page_open(array('sess' => 'Seminar_Session',
                'auth' => 'Seminar_Default_Auth',
                'perm' => 'Seminar_Perm',
                'user' => 'Seminar_User'));

$path = substr($_SERVER['REQUEST_URI'], strlen($CANONICAL_RELATIVE_PATH_STUDIP));
if (stripos($path, "?")) {
    $path = substr($path, 0, stripos($path, "?"));
}
$path = studip_utf8decode(urldecode($path));

//Ab hier soll noch ein wirkungsvollerer Router wie Slim eingebaut werden:
ob_start();

NotificationCenter::postNotification("PathIsRewritten", $path);

$output = ob_get_contents();

if ($output) {
    echo $output;
} else {
    throw new Exception("404: Page not found");
}