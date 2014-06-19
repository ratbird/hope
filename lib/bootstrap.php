<?php
# Lifter010: TODO
/*
 * Copyright (c) 2009  Stud.IP CoreGroup
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

global $PHP_SELF, $STUDIP_BASE_PATH;

$PHP_SELF = $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
$STUDIP_BASE_PATH = realpath(dirname(__FILE__) . '/..');

set_include_path(
    $STUDIP_BASE_PATH
    . PATH_SEPARATOR . $STUDIP_BASE_PATH . DIRECTORY_SEPARATOR . 'config'
    . PATH_SEPARATOR . get_include_path()
);
!ini_get('register_globals') OR require 'templates/register_globals_on.php';

require 'lib/classes/StudipAutoloader.php';
StudipAutoloader::register();
StudipAutoloader::addAutoloadPath($STUDIP_BASE_PATH . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'models');
StudipAutoloader::addAutoloadPath($STUDIP_BASE_PATH . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'classes');
StudipAutoloader::addAutoloadPath($STUDIP_BASE_PATH . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'exceptions');
StudipAutoloader::addAutoloadPath($STUDIP_BASE_PATH . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'sidebar');
StudipAutoloader::addAutoloadPath($STUDIP_BASE_PATH . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'helpbar');

// sample the request time every tenth time
register_shutdown_function(function ($timer) { $timer('core.request_time', 0.1); }, Metrics::startTimer());

require 'lib/phplib/db_mysql_studip_pdo.inc';
require 'lib/phplib/ct_sql_studip_pdo.inc';
require 'lib/phplib/ct_cache_studip.inc';
require 'lib/phplib/session4_custom.inc';
require 'lib/phplib/auth4.inc';
require 'lib/phplib/perm.inc';

require 'lib/phplib/email_validation.inc';
require 'config_local.inc.php';
require 'lib/phplib_local.inc.php';
require 'lib/phplib/page4.inc';

// Development: Use own directory for file related classes
require_once 'lib/files/FileStorage.php';
require_once 'lib/files/DirectoryEntry.php';
require_once 'lib/files/DiskFileStorage.php';
require_once 'lib/files/File.php';
require_once 'lib/files/StudipDirectory.php';
require_once 'lib/files/RootDirectory.php';
require_once 'lib/files/FileHelper.php';

