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
        $user = User::findByUsername($username);
        return $user->password == self::getUserIP();
    }

    /**
     * Fetches the ip of the calling user
     * 
     * @return user ip
     */
    private static function getUserIP() {
        $fields = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        foreach ($fields as $field) {
            $ip = filter_input(INPUT_SERVER, $field);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

}
