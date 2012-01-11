#! /usr/bin/env php
<?php
require_once 'studip_cli_env.inc.php';

require_once dirname(__FILE__) . '/../lib/classes/squeeze/squeeze.php';

\Studip\Squeeze\packageAll();
