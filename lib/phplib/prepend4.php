<?php
# Lifter002: TODO
/*
 * Session Management for PHP3
 *
 * Copyright (c) 1998,1999 SH Online Dienst GmbH
 *                    Boris Erdmann, Kristian Koehntopp
 *
 *
 */ 
(!isset($_REQUEST['GLOBALS'])) OR die('Setting the $GLOBALS array is not tolerated!');
$PHP_SELF = $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
$STUDIP_BASE_PATH = realpath( dirname(__FILE__) . '/../..');

$include_path = $STUDIP_BASE_PATH;
$include_path .= PATH_SEPARATOR . $STUDIP_BASE_PATH . DIRECTORY_SEPARATOR . 'config';
$include_path .= PATH_SEPARATOR . get_include_path();
set_include_path($include_path);

define('PHPLIB_SESSIONDATA_TABLE', 'session_data');
define('PHPLIB_USERDATA_TABLE', 'user_data');

require('lib/phplib/db_mysql_studip_pdo.inc');  /* Change this to match your database. */
require('lib/phplib/ct_sql_studip_pdo.inc');    /* Change this to match your data storage container */
require('lib/phplib/session4_custom.inc');   /* Required for everything below.      */
require('lib/phplib/auth4.inc');      /* Disable this, if you are not using authentication. */
require('lib/phplib/perm.inc');      /* Disable this, if you are not using permission checks. */
require('lib/phplib/user4.inc');      /* Disable this, if you are not using per-user variables. */


/* Additional require statements go below this line */

require('lib/phplib/email_validation.inc'); /* Required, contains register-check functions. */

/* Additional require statements go before this line */

require('init_config_arrays.inc.php');     /* Required, initializes your local configuration. */
require('config_local.inc.php');     /* Required, contains your local configuration. */

require('lib/phplib/page4.inc');      /* Required, contains the page management functions. */

?>
