<?php

require_once 'lib/classes/searchtypes/SearchType.class.php';

require_once "lib/classes/Avatar.class.php";
require_once "lib/classes/CourseAvatar.class.php";
require_once "lib/classes/InstituteAvatar.class.php";
require_once 'lib/trails/AuthenticatedController.php';

class QuicksearchController extends AuthenticatedController {

    private $specialSQL;

    /**
     * the one action which is called by the QuickSearch-form when typed in
     * by user.
     * @param query_id string: first argument of url -> id of query in session
     */
    public function response_action($query_id) {
        $this->extraInclude($query_id);
        $this->cleanUp();
        $_SESSION['QuickSearches'][$query_id]['time'] = time();
        $this->search = $this->getSearch($query_id);
        $this->specialSQL = $_SESSION['QuickSearches'][$query_id]['query'];
        $this->form_data = Request::getArray("form_data");
        $this->searchresults = $this->getResults(Request::get('request'));
        $this->render_template('quicksearch/response.php');
    }

    /**
     * instantiates the search-object (or string)
     * @param query_id string: id of the query in session
     * @return object or string: ready search-object or string
     */
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

    /**
     * includes the class of the search-object so we can re-instantiate this object
     * later
     * @param query_id string: id of the query in session
     * @return void
     */
    private function extraInclude($query_id) {
        if ($_SESSION['QuickSearches'][$query_id]['includePath']) {
            include_once($_SESSION['QuickSearches'][$query_id]['includePath']);
        }
    }

    /**
     * formats the results so that the searchword is marked bold
     * @param results array: array of searchresults
     * @return array: array of searchresults formatted
     */
    private function extraResultFormat($results) {
    	$input = Request::get('request');
    	foreach ($results as $key => $result) {
    		$results[$key][1] = preg_replace("/(".$input.")/i", "<b>$1</b>", $result[1]);
    	}
    	return $results;
    }

    /**
     * private method to get a result-array in the way of array(array(item_id, item-name)).
     * @param request:    the request from the searchfield typed by the user.
     * @return:    array(array(item_id, item-name), ...) mostly limited to 5.
     */
    private function getResults($request) {
        if ($this->search instanceof SearchType) {
            try {
                $results = $this->search->getResults($request, $this->form_data);
            } catch (Exception $exception) {
                //Der Programmierer will ja seine Fehler sehen:
                return array(array("", $exception->getMessage()));
            }
            return $this->extraResultFormat($results);
        } else {
            $db = DBManager::get();

            if ($this->search == "username") {
                $statement = $db->prepare("SELECT DISTINCT auth_user_md5.username, CONCAT(auth_user_md5.Vorname, \" \", auth_user_md5.Nachname) " .
                    "FROM auth_user_md5 LEFT JOIN user_info ON (user_info.user_id = auth_user_md5.user_id) " .
                    "WHERE CONCAT(auth_user_md5.Vorname, \" \", auth_user_md5.Nachname) LIKE :input " .
                        "OR auth_user_md5.username LIKE :input ORDER BY user_info.score DESC LIMIT 5", array(PDO::FETCH_NUM));
                $statement->execute(array(':input' => "%".$request."%"));
                $result = $statement->fetchAll();
                return $this->extraResultFormat($result);
            }
            if ($this->search == "user_id") {
                $statement = $db->prepare("SELECT DISTINCT auth_user_md5.user_id, CONCAT(auth_user_md5.Vorname, \" \", auth_user_md5.Nachname) " .
                    "FROM auth_user_md5 LEFT JOIN user_info ON (user_info.user_id = auth_user_md5.user_id) " .
                    "WHERE CONCAT(auth_user_md5.Vorname, \" \", auth_user_md5.Nachname)) LIKE :input " .
                        "OR auth_user_md5.username LIKE :input ORDER BY user_info.score DESC LIMIT 5", array(PDO::FETCH_NUM));
                $statement->execute(array(':input' => "%".$request."%"));
                $result = $statement->fetchAll();
                return $this->extraResultFormat($result);
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
                return $this->extraResultFormat($result);
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
                return $this->extraResultFormat($result);
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
                return $this->extraResultFormat($result);
            }
            if ($this->search == "special") {
                $statement = $db->prepare($this->specialSQL, array(PDO::FETCH_NUM));
                try {
                    $statement->execute(array(':input' => "%".$request."%"));
                    $result = $statement->fetchAll();
                } catch (Exception $exception) {
                    return array(array("", $exception->getMessage()), array("", $this->specialSQL));
                }
                return $this->extraResultFormat($result);
            }
        }
        $result = array(array("", _("Session abgelaufen oder unbekannter Suchtyp")));
        return $result;
    }

    /**
     * deletes all older requests, that have not been used since half an hour
     * @return void
     */
    private function cleanUp() {
        $count = 0;
        $lifetime = $GLOBALS['AUTH_LIFETIME'] ? $GLOBALS['AUTH_LIFETIME'] : 30;
        foreach($_SESSION['QuickSearches'] as $query_id => $query) {
            if (time() - $query['time'] > $lifetime * 60) {
                unset($_SESSION['QuickSearches'][$query_id]);
                $count++;
            }
        }
        return $count;
    }
}
