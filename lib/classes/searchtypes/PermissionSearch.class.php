<?php

/*
 * Copyright (C) 2010 - Thomas Hackl <thomas.hackl@uni-passau.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once "lib/classes/searchtypes/SQLSearch.class.php";
require_once "lib/functions.php";

/**
 * Class of type SearchType used for searches with QuickSearch
 * (lib/classes/QuickSearch.class.php). You can search for people with a given
 * Stud.IP permission level, either globally or at an institute.
 *
 * @author Thomas Hackl
 *
 */

class PermissionSearch extends SQLSearch {

    private $search;
    private $avatarLike;
    private $presets;
    private $title;

    /**
     * 
     * @param string $query: SQL with at least ":input" as parameter 
     * @param array $presets: variables from the same form that should be used 
     * in this search. array("input_name" => "placeholder_in_sql_query")
     * @return void
     */
    public function __construct($search, $title = "", $avatarLike = "", $presets = array()) {
        $this->search = $search;
        $this->presets = $presets;
        $this->title = $title;
        $this->avatarLike = $avatarLike;
    }

    /**
     * returns an object of type SQLSearch with parameters to constructor
     */
    static public function get($search, $title = "", $avatarLike = "", $presets = array()) {
        return new PermissionSearch($search, $title, $avatarLike, $presets);
    }

    /**
     * returns the title/description of the searchfield
     * @return string: title/description
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * returns the results of a search
     * Use the contextual_data variable to send more variables than just the input
     * to the SQL. QuickSearch for example sends all other variables of the same
     * <form>-tag here.
     * @param input string: the search-word(s)
     * @param contextual_data array: an associative array with more variables
     * @return array: array(array(), ...)
     */
    public function getResults($input, $contextual_data = array()) {
        $db = DBManager::get();
        $sql = $this->getSQL();
        if (is_array($this->presets)) {
            foreach ($this->presets as $name => $value) {
                if (($name !== 'input' && strpos($sql, ':'.$name) !== false)) {
                    if (is_array($value)) {
                        $sql = str_replace(':'.$name, "'".implode("', '", $value)."'", $sql);
                    } else {
                        $sql = str_replace(':'.$name, "'".$value."'");
                    }
                }
            }
        }
        if (is_array($contextual_data)) {
            foreach ($contextual_data as $name => $value) {
                if (is_array($value)) {
                    if (($name !== "input") && (strpos($sql, ":".$name) !== FALSE)) {
                        $sql = str_replace(":".$name, "'".implode("', '", $value)."'", $sql);
                    }
                } else {
                    if (($name !== "input") && (strpos($sql, ":".$name) !== FALSE)) {
                        $sql = str_replace(":".$name, "'".$value."'", $sql);
                    }
                }
            }
        }
        $statement = $db->prepare($sql, array(PDO::FETCH_NUM));
        $data = array();
        $data[":input"] = "%".$input."%";
        $statement->execute($data);
        $results = $statement->fetchAll();
        return $results;
    }

    private function getSQL() {
        switch ($this->search) {
            case "username":
                return "SELECT DISTINCT auth_user_md5.username, ".$GLOBALS['_fullname_sql']['full_rev']." AS fullname " .
                        "FROM auth_user_md5 " .
                        " LEFT JOIN user_info USING(user_id) " .
                        "WHERE ( CONCAT(auth_user_md5.Vorname, \" \", auth_user_md5.Nachname) LIKE :input " .
                            "OR auth_user_md5.username LIKE :input ) " .
                            "AND auth_user_md5.perms IN (:permission) ".
                        "ORDER BY auth_user_md5.Nachname ASC " .
                        "LIMIT 10";
            case "username_inst":
                return "SELECT DISTINCT auth_user_md5.username, ".$GLOBALS['_fullname_sql']['full_rev']." AS fullname " .
                        "FROM auth_user_md5 LEFT JOIN user_inst ON (user_inst.user_id = auth_user_md5.user_id) " .
                        " LEFT JOIN user_info USING(user_id) " .
                        "WHERE ( CONCAT(auth_user_md5.Vorname, \" \", auth_user_md5.Nachname) LIKE :input " .
                            "OR auth_user_md5.username LIKE :input )" .
                            "AND user_inst.Institut_id IN (:institute) " .
                            "AND user_inst.inst_perms IN (:permission) ".
                        "ORDER BY auth_user_md5.Nachname ASC " .
                        "LIMIT 10";
            case "user_id":
                return "SELECT DISTINCT auth_user_md5.user_id, ".$GLOBALS['_fullname_sql']['full_rev']." AS fullname " .
                        "FROM auth_user_md5 " .
                        " LEFT JOIN user_info USING(user_id) " .
                        "WHERE ( CONCAT(auth_user_md5.Vorname, \" \", auth_user_md5.Nachname) LIKE :input " .
                            "OR auth_user_md5.username LIKE :input ) " .
                            "AND auth_user_md5.perms IN (:permission) ".
                        "ORDER BY auth_user_md5.Nachname ASC " .
                        "LIMIT 10";
            case "user_id_inst":
                return "SELECT DISTINCT auth_user_md5.user_id, ".$GLOBALS['_fullname_sql']['full_rev']." AS fullname " .
                        "FROM auth_user_md5 " .
                        " LEFT JOIN user_info USING(user_id) " .
                        "WHERE ( CONCAT(auth_user_md5.Vorname, \" \", auth_user_md5.Nachname) LIKE :input " .
                            "OR auth_user_md5.username LIKE :input ) " .
                            "AND user_inst.Institut_id IN (:institute) " .
                            "AND user_inst.inst_perms IN (:permission) ".
                        "ORDER BY auth_user_md5.Nachname ASC " .
                        "LIMIT 10";
        }
    }

    /**
     * returns an adress of the avatar of the searched item (if avatar enabled)
     * @param id string: id of the item which can be username, user_id, Seminar_id or Institut_id
     * @param size enum(NORMAL, SMALL, MEDIUM): size of the avatar-image
     * @return string: adress of an image
     */
    public function getAvatar($id) {
        switch ($this->avatarLike) {
            case "username":
            case "username_inst":
                return Avatar::getAvatar(NULL, get_userid($id))->getURL(Avatar::SMALL);
            case "user_id":
            case "user_id_inst":
                return Avatar::getAvatar(NULL, $id)->getURL(Avatar::SMALL);
        }
    }

    /**
     * returns an html tag of the image of the searched item (if avatar enabled)
     * @param id string: id of the item which can be username, user_id, Seminar_id or Institut_id
     * @param size enum(NORMAL, SMALL, MEDIUM): size of the avatar
     * @return string: like "<img src="...avatar.jpg" ... >"
     */
    public function getAvatarImageTag($id, $size = Avatar::SMALL) {
        switch ($this->avatarLike) {
            case "username":
            case "username_inst":
                return Avatar::getAvatar(get_userid($id))->getImageTag($size);
            case "user_id":
            case "user_id_inst":
                return Avatar::getAvatar($id)->getImageTag($size);
        }
    }

    /**
     * A very simple overwrite of the same method from SearchType class.
     * returns the absolute path to this class for autoincluding this class.
     * @return: path to this class
     */
    public function includePath() {
        return __file__;
    }
}