<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO

/*
 * Token.class.php - Token class
 *
 * Copyright (C) 2006 - Marco Diedrich (mdiedric@uos.de)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

class Token
{
    function Token($user_id, $duration_validity = 30)
    {
        $this->user_id = $user_id;
        $this->duration_validity = $duration_validity;
        $this->expiration_time = $this->calculate_expiration_time(time());
        $this->token = $this->generate_token();
        $this->save();
    }

    function generate_token()
    {
        return md5(uniqid(rand(), true));
    }

    function get_token()
    {
        return $this->token;
    }

    function get_string()
    {
        return $this->get_token();
    }

    function save()
    {
        $db = new DB_Seminar();

        $db->query("INSERT INTO user_token 
            (user_id, token, expiration)
            VALUES ('{$this->user_id}', '{$this->token}', '{$this->expiration_time}');");
    }

    function calculate_expiration_time()
    {
        return strtotime(sprintf("+ %u seconds", $this->duration_validity), time());
    }

    function generate($user_id)
    {
        $token = Token::generate_token();
        $expiration_time = Token::calculate_expiration_time();

        $db = new DB_Seminar();

        $db->query("INSERT INTO user_token 
            (user_id, token, expiration)
            VALUES ('{$user_id}', '{$token}', '{$expiration_time}');");
    }

    function remove_expired()
    {
        $db = new DB_Seminar();

        $db->query(sprintf("DELETE FROM user_token 
            WHERE expiration < %u;", time()));
    }

    function remove($token)
    {
        $db = new DB_Seminar();

        $db->query("DELETE FROM user_token 
            WHERE token='{$token}';");
    }

    function time_expired($expiration)
    {
        return time() > $expiration;
    }

    function is_valid($token)
    {
        $db = new DB_Seminar();

        $db->query("SELECT * FROM user_token 
            WHERE token='{$token}';");

        if ($db->next_record())
        {
            if (! Token::time_expired($db->f("expiration")))
            {
                $user_id = $db->f('user_id');
                Token::remove($token);
                return $user_id;
            } else
            {
                Token::remove_expired();
                return null;
            }
        } else
        {
            return null;
        }
    }

}

# $token = &new Token('38f32d5c0b1d16450408bb11c4089930', 1);
# var_dump($token);
# $token->save();
# var_dump($token->is_valid($token->get_string()));


