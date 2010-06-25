<?php

/*
 * Copyright (C) 2010 - Rasmus Fuhse <fuhse@data-quest.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once "lib/classes/searchtypes/SearchType.class.php";
require_once "lib/functions.php";

/**
 * Class of type SearchType used for searches with QuickSearch 
 * (lib/classes/QuickSearch.class.php). You can search with a sql-syntax in the 
 * database. You just need to give in a query like for a PDB-prepare statement 
 * and at least the variable ":input" in the query (the :input will be replaced 
 * with the input of the QuickSearch userinput.
 *  [code]
 *  $search = new SQLSearch("SELECT username, Nachname "
 *      "FROM auth_user_md5 " .
 *      "WHERE Nachname LIKE :input ", _("Nachname suchen"), "username");
 *  [/code]  
 * 
 * @author Rasmus Fuhse
 *
 */

class SQLSearch extends SearchType {
    
    private $SQL;
    private $avatarLike;
    
    /**
     * 
     * @param string $query: SQL with at least ":input" as parameter 
     * @param array $presets: variables from the same form that should be used 
     * in this search. array("input_name" => "placeholder_in_sql_query")
     * @return void
     */
    public function __construct($query, $title = "", $avatarLike = "", $presets = array()) {
        $this->SQL = $query;
        $this->presets = $presets;
        $this->title = $title;
        $this->avatarLike = $avatarLike;
    }
    
    /**
     * returns an object of type SQLSearch with parameters to constructor
     */
    static public function get($query, $title = "", $avatarLike = "", $presets = array()) {
        return new SQLSearch($query, $title, $avatarLike, $presets);
    }
    /**
     * returns the title/description of the searchfield
     * @return string: title/description
     */
    public function getTitle() {
        return $this->title;
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
                return Avatar::getAvatar(NULL, get_userid($id))->getURL(Avatar::SMALL);
            case "user_id":
                return Avatar::getAvatar(NULL, $id)->getURL(Avatar::SMALL);
            case "Seminar_id":
            case "Arbeitsgruppe_id":
                return CourseAvatar::getAvatar(NULL, $id)->getURL(Avatar::SMALL);
            case "Institut_id":
                return InstituteAvatar::getAvatar(NULL, $id)->getURL(Avatar::SMALL);
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
                return Avatar::getAvatar(get_userid($id))->getImageTag($size);
            case "user_id":
                return Avatar::getAvatar($id)->getImageTag($size);
            case "Seminar_id":
            case "Arbeitsgruppe_id":
                return CourseAvatar::getAvatar(NULL, $id)->getImageTag($size);
            case "Institut_id":
                return InstituteAvatar::getAvatar(NULL, $id)->getImageTag($size);
        }
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
        $statement = $db->prepare($this->SQL, array(PDO::FETCH_NUM));
        $data = array();
        if (is_array($contextual_data)) {
            foreach ($contextual_data as $name => $value) {
               if (($name !== "input") && (strpos($this->SQL, ":".$name) !== FALSE)) {
                  $data[":".$name] = $value;
               }
            }
        }
        $data[":input"] = "%".$input."%";
        $statement->execute($data);
        $results = $statement->fetchAll();
        return $results;
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