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

if (isset($_REQUEST['GLOBALS'])) {
  die('Setting the $GLOBALS array is not tolerated!');
}

$PHP_SELF = $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
$STUDIP_BASE_PATH = realpath(dirname(__FILE__) . '/..');

set_include_path(
    $STUDIP_BASE_PATH
    . PATH_SEPARATOR . $STUDIP_BASE_PATH . DIRECTORY_SEPARATOR . 'config'
    . PATH_SEPARATOR . get_include_path()
);

define('PHPLIB_SESSIONDATA_TABLE', 'session_data');
define('PHPLIB_USERDATA_TABLE', 'user_data');

require 'lib/phplib/db_mysql_studip_pdo.inc';
require 'lib/phplib/ct_sql_studip_pdo.inc';
require 'lib/phplib/session4_custom.inc';
require 'lib/phplib/auth4.inc';
require 'lib/phplib/perm.inc';
require 'lib/phplib/user4.inc';


require 'lib/phplib/email_validation.inc';
require 'init_config_arrays.inc.php';
require 'config_local.inc.php';
require_once 'lib/phplib_local.inc.php';

require_once 'lib/classes/CSRFProtection.php';
require 'lib/phplib/page4.inc';
require_once 'lib/classes/SkipLinks.php';
require_once 'lib/classes/PersonalNotifications.class.php';