<?php
require '../lib/bootstrap.php';

header('Location: ' . URLHelper::getUrl('dispatch.php/profile', array('username' => Request::username('username'))));
