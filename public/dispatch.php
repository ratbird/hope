<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO

/*
 * index.php - <short-description>
 *
 * Copyright (C) 2006 - Marcus Lunzenauer <mlunzena@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


require '../lib/bootstrap.php';

require_once 'lib/functions.php';
require_once 'lib/trails/TrailsController.php';
require_once 'lib/trails/TrailsDispatcher.php';
require_once 'lib/trails/TrailsFlash.php';
require_once 'lib/trails/TrailsInflector.php';
require_once 'lib/trails/TrailsResponse.php';
require_once 'lib/exceptions/TrailsException.php';

# define root
$trails_root = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'app';

$trails_uri = rtrim($ABSOLUTE_URI_STUDIP, '/') . '/dispatch.php';

# set base url for URLHelper class
URLHelper::setBaseUrl($ABSOLUTE_URI_STUDIP);

# disable register_globals if set
unregister_globals();

# dispatch
$request_uri = $_SERVER['REQUEST_URI'] === $_SERVER['PHP_SELF']
               ? '/'
               : substr($_SERVER['REQUEST_URI'], strlen($_SERVER['PHP_SELF']));

$default_controller = 'default';

$dispatcher = new TrailsDispatcher($trails_root, $trails_uri, $default_controller);
$dispatcher->dispatch($request_uri);
