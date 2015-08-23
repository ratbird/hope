<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

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
require_once 'vendor/trails/trails.php';
require_once 'lib/classes/StudipDispatcher.php';

// prepare environment
URLHelper::setBaseUrl($GLOBALS['ABSOLUTE_URI_STUDIP']);
StudipAutoloader::addAutoloadPath($GLOBALS['STUDIP_BASE_PATH'] . '/app/models');
StudipAutoloader::addAutoloadPath($GLOBALS['STUDIP_BASE_PATH'] . '/app/models', 'Studip');

$request_uri = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '/';

$dispatcher = new StudipDispatcher();
$dispatcher->dispatch($request_uri);
