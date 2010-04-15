<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO

require '../lib/bootstrap.php';

if (isset($_REQUEST['target'])) @include $RELATIVE_PATH_CHAT . '/' . basename($_REQUEST['target']);
?>

