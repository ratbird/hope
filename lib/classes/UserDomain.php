<?php
# Lifter007: TODO
# Lifter003: TODO
/*
 * UserDomain.php - class representing a user domain in Stud.IP
 *
 * Copyright (c) 2008  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/**
 * Simple class representing a user domain in Stud.IP.
 */
class UserDomain
{
    /**
     * ID of this user domain
     */
    private $id;

    /**
     * name of this user domain
     */
    private $name;

    /**
     * Initialize a new UserDomain instance.
     */
    public function __construct ($id, $name = NULL)
    {
        $db = DBManager::get();

        if (preg_match('/[^\\w.-]/', $id)) {
            throw new Exception(_('Ungültige ID für Nutzerdomäne').': '.$id);
        }

        $this->id = $id;

        if (isset($name)) {
            $this->name = $name;
        } else {
            $sql = "SELECT name FROM userdomains WHERE userdomain_id = '".$id."'";
            $result = $db->query($sql);

            if (($row = $result->fetch())) {
                $this->name = $row['name'];
            }
        }
    }

    /**
     * Get a string representation of this user domain.
     */
    public function __toString ()
    {
        return $this->id;
    }

    /**
     * Get the ID of this user domain.
     */
    public function getID ()
    {
        return $this->id;
    }

    /**
     * Get the display name of this user domain.
     */
    public function getName ()
    {
        return $this->name;
    }

    /**
     * Set the display name of this user domain.
     */
    public function setName ($name)
    {
        $this->name = $name;
    }

    /**
     * Store changes to the user domain to the database.
     * This currently only saves the display name.
     */
    public function store ()
    {
        $db = DBManager::get();

        $sql = "SELECT * FROM userdomains WHERE userdomain_id = '".$this->id."'";
        $result = $db->query($sql);

        if (($row = $result->fetch())) {
            $db->exec("UPDATE userdomains SET name = ".$db->quote($this->name)."
                       WHERE userdomain_id = '".$this->id."'");
        } else {
            $db->exec("INSERT INTO userdomains (userdomain_id, name)
                       VALUES ('".$this->id."', ".$db->quote($this->name).")");
        }
    }

    /**
     * Delete this user domain from the database.
     */
    public function delete ()
    {
        $db = DBManager::get();

        $db->exec("DELETE FROM userdomains WHERE userdomain_id = '".$this->id."'");
    }

    /**
     * Get an array of all defined user domains.
     * Returns an array of UserDomain objects.
     */
    public static function getUserDomains ()
    {
        $db = DBManager::get();
        $domains = array();

        $result = $db->query("SELECT * FROM userdomains ORDER BY name");

        foreach ($result as $row) {
            $domains[] = new UserDomain($row['userdomain_id'], $row['name']);
        }

        return $domains;
    }

    /**
     * Add a user to this user domain.
     */
    public function addUser ($user_id)
    {
        $db = DBManager::get();

        $db->exec("INSERT IGNORE INTO user_userdomains (user_id, userdomain_id)
                   VALUES ('".$user_id."', '".$this->id."')");
    }

    /**
     * Remove a user from this user domain.
     */
    public function removeUser ($user_id)
    {
        $db = DBManager::get();

        $db->exec("DELETE FROM user_userdomains
                   WHERE user_id = '".$user_id."' AND
                         userdomain_id = '".$this->id."'");
    }

    /**
     * Get an array of all users in this user domain.
     * Returns an array of user IDs.
     */
    public function getUsers ()
    {
        $db = DBManager::get();
        $users = array();

        $sql = "SELECT user_id FROM user_userdomains WHERE userdomain_id = '".$this->id."'";
        $result = $db->query($sql);

        foreach ($result as $row) {
            $users[] = $row['user_id'];
        }

        return $users;
    }

    /**
     * Get an array of all user domains for a specific user.
     * Returns an array of UserDomain objects.
     */
    public static function getUserDomainsForUser ($user_id)
    {
        $db = DBManager::get();
        $domains = array();

        $sql = "SELECT * FROM userdomains JOIN user_userdomains USING (userdomain_id)
                WHERE user_userdomains.user_id = '".$user_id."' ORDER BY name";
        $result = $db->query($sql);

        foreach ($result as $row) {
            $domains[] = new UserDomain($row['userdomain_id'], $row['name']);
        }

        return $domains;
    }

    /**
     * Remove all user domains for a specific user.
     */
    public static function removeUserDomainsForUser ($user_id)
    {
        $db = DBManager::get();

        $db->exec("DELETE FROM user_userdomains WHERE user_id = '".$user_id."'");
    }

    /**
     * Add a seminar to this user domain.
     */
    public function addSeminar ($seminar_id)
    {
        $db = DBManager::get();

        $db->exec("INSERT IGNORE INTO seminar_userdomains (seminar_id, userdomain_id)
                   VALUES ('".$seminar_id."', '".$this->id."')");
    }

    /**
     * Remove a seminar from this user domain.
     */
    public function removeSeminar ($seminar_id)
    {
        $db = DBManager::get();

        $db->exec("DELETE FROM seminar_userdomains
                   WHERE seminar_id = '".$seminar_id."' AND
                         userdomain_id = '".$this->id."'");
    }

    /**
     * Get an array of all seminars in this user domain.
     * Returns an array of seminar IDs.
     */
    public function getSeminars ()
    {
        $db = DBManager::get();
        $seminars = array();

        $sql = "SELECT seminar_id FROM seminar_userdomains WHERE userdomain_id = '".$this->id."'";
        $result = $db->query($sql);

        foreach ($result as $row) {
            $seminars[] = $row['seminar_id'];
        }

        return $seminars;
    }

    /**
     * Get an array of all user domains for a specific seminar.
     * Returns an array of UserDomain objects.
     */
    public static function getUserDomainsForSeminar ($seminar_id)
    {
        $db = DBManager::get();
        $domains = array();

        $sql = "SELECT * FROM userdomains JOIN seminar_userdomains USING (userdomain_id)
                WHERE seminar_userdomains.seminar_id = '".$seminar_id."' ORDER BY name";
        $result = $db->query($sql);

        foreach ($result as $row) {
            $domains[] = new UserDomain($row['userdomain_id'], $row['name']);
        }

        return $domains;
    }

    /**
     * Remove all user domains for a specific seminar.
     */
    public static function removeUserDomainsForSeminar ($seminar_id)
    {
        $db = DBManager::get();

        $db->exec("DELETE FROM seminar_userdomains WHERE seminar_id = '".$seminar_id."'");
    }
}
