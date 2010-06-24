<?php

/*
 * Copyright (C) 2010 - Rasmus Fuhse <fuhse@data-quest.de>
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
 * (lib/classes/QuickSearch.class.php). You can search with a sql-syntax in the
 * database. You just need to give in a query like for a PDB-prepare statement
 * and at least the variable ":input" in the query (the :input will be replaced
 * with the input of the QuickSearch userinput.
 *  [code]
 *  $search = new SQLSearch("username");
 *  [/code]
 *
 * @author Rasmus Fuhse
 *
 */

class StandardSearch extends SQLSearch {

    private $search;
    private $avatarLike;

    /**
     *
     * @param string $query: SQL with at least ":input" as parameter
     * @param array $presets: variables from the same form that should be used
     * in this search. array("input_name" => "placeholder_in_sql_query")
     * @return void
     */
    public function __construct($search) {
        $this->avatarLike = $this->search = $search;
    }

    /**
     * returns an object of type SQLSearch with parameters to constructor
     */
    static public function get($search) {
        return new SQLSearch($search);
    }
    /**
     * returns the title/description of the searchfield
     * @return string: title/description
     */
    public function getTitle() {
        switch ($this->search) {
            case "username":
            case "user_id":
                return _("Nutzer suchen");
            case "Seminar_id":
                return _("Veranstaltung suchen");
            case "Arbeitsgruppe_id":
                return _("Arbeitsgruppe suchen");
            case "Institut_id":
                return _("Einrichtung suchen");
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
        $statement = $db->prepare($this->getSQL(), array(PDO::FETCH_NUM));
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

    private function getSQL() {
        switch ($this->search) {
            case "username":
                return "SELECT DISTINCT auth_user_md5.user_id, CONCAT(auth_user_md5.Vorname, \" \", auth_user_md5.Nachname) " .
                        "FROM auth_user_md5 LEFT JOIN user_info ON (user_info.user_id = auth_user_md5.user_id) " .
                        "WHERE CONCAT(auth_user_md5.Vorname, \" \", auth_user_md5.Nachname) LIKE :input " .
                            "OR auth_user_md5.username LIKE :input " .
                        "ORDER BY user_info.score DESC " .
                        "LIMIT 5";
            case "user_id":
                return "SELECT DISTINCT auth_user_md5.user_id, CONCAT(auth_user_md5.Vorname, \" \", auth_user_md5.Nachname, \" <em>(\", auth_user_md5.username,\")</em>\") " .
                        "FROM auth_user_md5 LEFT JOIN user_info ON (user_info.user_id = auth_user_md5.user_id) " .
                        "WHERE CONCAT(auth_user_md5.Vorname, \" \", auth_user_md5.Nachname) LIKE :input " .
                            "OR auth_user_md5.username LIKE :input " .
                        "ORDER BY user_info.score DESC " .
                        "LIMIT 5";
            case "Seminar_id":
                return "SELECT DISTINCT seminare.Seminar_id, seminare.Name " .
                        "FROM seminare " .
                            "LEFT JOIN seminar_user ON (seminar_user.Seminar_id = seminare.Seminar_id AND seminar_user.status = 'dozent') " .
                            "LEFT JOIN auth_user_md5 ON (auth_user_md5.user_id = seminar_user.user_id) " .
                        "WHERE (seminare.Name LIKE :input " .
                            "OR CONCAT(auth_user_md5.Vorname, ' ', auth_user_md5.Nachname) LIKE :input " .
                            "OR seminare.VeranstaltungsNummer LIKE :input " .
                            "OR seminare.Untertitel LIKE :input " .
                            "OR seminare.Beschreibung LIKE :input " .
                            "OR seminare.Ort LIKE :input " .
                            "OR seminare.Sonstiges LIKE :input) " .
                            "AND seminare.visible = 1 " .
                            "AND seminare.status NOT IN ('".implode("', '", studygroup_sem_types())."') " .
                        "LIMIT 5";
            case "Arbeitsgruppe_id":
                return "SELECT DISTINCT seminare.Seminar_id, seminare.Name " .
                        "FROM seminare " .
                            "LEFT JOIN seminar_user ON (seminar_user.Seminar_id = seminare.Seminar_id AND seminar_user.status = 'dozent') " .
                            "LEFT JOIN auth_user_md5 ON (auth_user_md5.user_id = seminar_user.user_id) " .
                        "WHERE (seminare.Name LIKE :input " .
                            "OR CONCAT(auth_user_md5.Vorname, ' ', auth_user_md5.Nachname) LIKE :input " .
                            "OR seminare.VeranstaltungsNummer LIKE :input " .
                            "OR seminare.Untertitel LIKE :input " .
                            "OR seminare.Beschreibung LIKE :input " .
                            "OR seminare.Ort LIKE :input " .
                            "OR seminare.Sonstiges LIKE :input) " .
                            "AND seminare.visible = 1 " .
                            "AND seminare.status IN ('".implode("', '", studygroup_sem_types())."') " .
                        "LIMIT 5";
            case "Institut_id":
                return "SELECT DISTINCT Institute.Institut_id, Institute.Name " .
                        "FROM Institute " .
                            "LEFT JOIN range_tree ON (range_tree.item_id = Institute.Institut_id) " .
                        "WHERE Institute.Name LIKE :input " .
                            "OR Institute.Strasse LIKE :input " .
                            "OR Institute.email LIKE :input " .
                            "OR range_tree.name LIKE :input " .
                        "ORDER BY Institute.Name " .
                        "LIMIT 5";
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