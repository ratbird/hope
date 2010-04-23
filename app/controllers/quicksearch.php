<?php

require_once 'lib/classes/searchtypes/SearchType.class.php';

require_once 'app/controllers/authenticated_controller.php';
require_once("lib/classes/Avatar.class.php");
require_once("lib/classes/CourseAvatar.class.php");
require_once("lib/classes/InstituteAvatar.class.php");


class QuicksearchController extends AuthenticatedController {
    
	private $search;
	private $specialSQL;
	
    public function response_action($query_id) {
    	$this->extraInclude($query_id);
    	//$this->cleanUp();
    	$this->search = $this->getSearch($query_id);
    	$this->specialSQL = $_SESSION['QuickSearches'][$query_id]['query'];
    	$this->form_data = json_decode(Request::get("form_data"), true);
    	$this->searchresults = $this->getResults(Request::get('searchkey'));
    	$this->render_template('quicksearch/response.php');
    }
    
    private function getSearch($query_id) {
    	if (isset($_SESSION['QuickSearches'][$query_id])) {
    		$search_query = $_SESSION['QuickSearches'][$query_id]['query'];
    		$search_object = $_SESSION['QuickSearches'][$query_id]['object'];
    		if ($search_object) {
    			//search with an object:
    		    return unserialize($search_object);
    		} elseif (!in_array($search_query, 
                    array("username", "user_id", "Seminar_id", 
                         "Institut_id", "Arbeitsgruppe_id"))) {
                //search with a special SQL-query:
                $this->specialSQL = $search_query;
    			return "special";
    		} else {
    			//search for username, Seminar_id and so on:
    			return $search_query;
    		}
    	} else {
    		return "";
    	}
    }
    
    private function extraInclude($query_id) {
    	if ($_SESSION['QuickSearches'][$query_id]['includePath']) {
    		include_once($_SESSION['QuickSearches'][$query_id]['includePath']);
    	}
    }
    
    /**
     * private method to get a result-array in the way of array(array(item_id, item-name)).
     * @param request:    the request from the searchfield typed by the user.
     * @return:    array(array(item_id, item-name), ...) mostly limited to 5.
     */
    private function getResults($request) {
    	if ($this->search instanceof SearchType) {
            try {
            	$form_data = json_decode(Request::get("form_data"), true);
            	//var_dump(Request::get("form_data"));
                $results = $this->search->getResults($request, $form_data);
            } catch (Exception $exception) {
                //Der Programmierer will ja seine Fehler sehen:
                return array(array("", $exception->getMessage()));
            }
            return $results;
        } else {
            $db = DBManager::get();

            if ($this->search == "username") {
            	$statement = $db->prepare("SELECT DISTINCT auth_user_md5.username, CONCAT(auth_user_md5.Vorname, \" \", auth_user_md5.Nachname) " .
                    "FROM auth_user_md5 LEFT JOIN user_info ON (user_info.user_id = auth_user_md5.user_id) " .
                    "WHERE CONCAT(auth_user_md5.Vorname, \" \", auth_user_md5.Nachname) LIKE :input " .
                        "OR auth_user_md5.username LIKE :input ORDER BY user_info.score DESC LIMIT 5", array(PDO::FETCH_NUM));
                $statement->execute(array(':input' => "%".$request."%"));
                $result = $statement->fetchAll();
                return $result;
            }
            if ($this->search == "user_id") {
                $statement = $db->prepare("SELECT DISTINCT auth_user_md5.user_id, CONCAT(auth_user_md5.Vorname, \" \", auth_user_md5.Nachname) " .
                    "FROM auth_user_md5 LEFT JOIN user_info ON (user_info.user_id = auth_user_md5.user_id) " .
                    "WHERE CONCAT(auth_user_md5.Vorname, \" \", auth_user_md5.Nachname)) LIKE :input " .
                        "OR auth_user_md5.username LIKE :input ORDER BY user_info.score DESC LIMIT 5", array(PDO::FETCH_NUM));
                $statement->execute(array(':input' => "%".$request."%"));
                $result = $statement->fetchAll();
                return $result;
            }
            if ($this->search == "Institut_id") {
                $statement = $db->prepare("SELECT DISTINCT Institute.Institut_id, Institute.Name " .
                    "FROM Institute " .
                        "LEFT JOIN range_tree ON (range_tree.item_id = Institute.Institut_id) " .
                    "WHERE Institute.Name LIKE :input " .
                        "OR Institute.Strasse LIKE :input " .
                        "OR Institute.email LIKE :input " .
                        "OR range_tree.name LIKE :input " .
                    "ORDER BY Institute.Name LIMIT 5", array(PDO::FETCH_NUM));
                $statement->execute(array(':input' => "%".$request."%"));
                $result = $statement->fetchAll();
                return $result;
            }
            if ($this->search == "Seminar_id") {
                $statement = $db->prepare("SELECT DISTINCT seminare.Seminar_id, seminare.Name " .
                    "FROM seminare " .
                        "LEFT JOIN seminar_user ON (seminare.Seminar_id = seminar_user.Seminar_id AND seminar_user.status = 'dozent') " .
                        "LEFT JOIN auth_user_md5 ON (seminar_user.user_id = auth_user_md5.user_id) " .
                    "WHERE (seminare.Name LIKE :input " .
                            "OR seminare.Untertitel LIKE :input " .
                            "OR seminare.Ort LIKE :input " .
                            "OR seminare.Sonstiges LIKE :input " .
                            "OR seminare.Beschreibung LIKE :input) " .
                        "AND seminare.visible = 1 " .
                        "AND seminare.status != '99' " .
                //Suche nach Dozent hat noch nicht funktioniert
                    "ORDER BY seminare.Name LIMIT 5", array(PDO::FETCH_NUM));
                $statement->execute(array(':input' => "%".$request."%"));
                $result = $statement->fetchAll();
                return $result;
            }
            if ($this->search == "Arbeitsgruppe_id") {
                $statement = $db->prepare("SELECT DISTINCT seminare.Seminar_id, seminare.Name " .
                    "FROM seminare " .
                        "LEFT JOIN seminar_user ON (seminare.Seminar_id = seminar_user.Seminar_id AND seminar_user.status = 'dozent') " .
                        "LEFT JOIN auth_user_md5 ON (seminar_user.user_id = auth_user_md5.user_id) " .
                    "WHERE (seminare.Name LIKE :input " .
                            "OR seminare.Untertitel LIKE :input " .
                            "OR seminare.Ort LIKE :input " .
                            "OR seminare.Sonstiges LIKE :input " .
                            "OR seminare.Beschreibung LIKE :input) " .
                        "AND seminare.visible = 1 " .
                        "AND seminare.status = '99' " .
                    //Suche nach Dozent hat noch nicht funktioniert
                    "ORDER BY seminare.Name LIMIT 5", array(PDO::FETCH_NUM));
                $statement->execute(array(':input' => "%".$request."%"));
                $result = $statement->fetchAll();
                return $result;
            }
            if ($this->search == "special") {
                $statement = $db->prepare($this->specialSQL, array(PDO::FETCH_NUM));
                try {
                    $statement->execute(array(':input' => "%".$request."%"));
                    $result = $statement->fetchAll();
                } catch (Exception $exception) {
                    return array(array("", $exception->getMessage()), array("", $this->specialSQL));
                }
                return $result;
            }
        }
        $result = array(array("", _("Session abgelaufen oder unbekannter Suchtyp")));
        return $result;
    }
    
    private function cleanUp() {
    	$count = 0;
    	foreach($_SESSION['QuickSearches'] as $query_id => $query) {
    		if (time() - $query['time'] > $GLOBALS['AUTH_LIFETIME'] * 60) {
    			unset($_SESSION['QuickSearches'][$query_id]);
    			$count++;
    		}
    	}
    	return $count;
    }
}
