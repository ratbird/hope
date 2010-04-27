<?php

require_once("lib/classes/searchtypes/SearchType.class.php");

class SQLSearch extends SearchType {
	
	private $SQL;
	
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
		$this->avatarLike = $avararLike;
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
    public function getAvatar($id) {
    	switch ($this->avatarLike) {
    	    case "username":
            case "user_id":
                return Avatar::getAvatar(NULL, $id)->getURL(Avatar::SMALL);
            case "Seminar_id":
            case "Arbeitsgruppe_id":
                return CourseAvatar::getAvatar(NULL, $id)->getURL(Avatar::SMALL);
            case "Institut_id":
                return InstituteAvatar::getAvatar(NULL, $id)->getURL(Avatar::SMALL);
    	}
    }
    public function getAvatarImageTag($id, $size = Avatar::SMALL) {
        switch ($this->avatarLike) {
            case "username":
            case "user_id":
                return Avatar::getAvatar(NULL, $id)->getImageTag($size);
            case "Seminar_id":
            case "Arbeitsgruppe_id":
                return CourseAvatar::getAvatar(NULL, $id)->getImageTag($size);
            case "Institut_id":
                return InstituteAvatar::getAvatar(NULL, $id)->getImageTag($size);
        }
    }
    public function getResults($input, $contextual_data = array()) {
        $db = DBManager::get();
        $statement = $db->prepare($this->SQL, array(PDO::FETCH_NUM));
        $data = array();
        //var_dump($contextual_data);
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
        //$results[] =  array("", count($data));
        return $results;
    }
    public function includePath() {
    	return __file__;
    }
}