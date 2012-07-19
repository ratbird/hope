<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

require '../lib/bootstrap.php';

unregister_globals();
if (Request::get('target')) @include $RELATIVE_PATH_CHAT . '/' . basename($_REQUEST['target']);
?>

