<?php
require_once "lib/classes/auth_plugins/StudipAuthAbstract.class.php";

/*
 * StudipAuthIP.class.php - Stud.IP authentication with user ip
 * Copyright (c) 2014  Florian Bieringer, Uni Passau
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */
class StudipAuthIP extends StudipAuthAbstract {
    
    /**
     * {@inheritdoc}
     */
    function isAuthenticated($username, $password) {
        return $GLOBALS['STUDIP_AUTH_CONFIG_IP'][$username] && in_array($_SERVER['REMOTE_ADDR'], $GLOBALS['STUDIP_AUTH_CONFIG_IP'][$username]);
    }
}
