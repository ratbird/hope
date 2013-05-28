<?php
require_once 'lib/log_events.inc.php';
require_once 'lib/resources/lib/ResourcesUserRoomsList.class.php';

/**
 * RoomRequest.class.php - model class for table resources_requests
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Cornelis Kater <ckater@gwdg.de>
 * @author      Till Gl�ggler <tgloeggl@uos.de>
 * @author      Andr� Noack <noack@data-quest.de>
 * @author      Suchi & Berg GmbH <info@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * 
 * @property string request_id database column
 * @property string id alias column for request_id
 * @property string seminar_id database column
 * @property string termin_id database column
 * @property string metadate_id database column
 * @property string user_id database column
 * @property string resource_id database column
 * @property string category_id database column
 * @property string comment database column
 * @property string reply_comment database column
 * @property string closed database column
 * @property string mkdate database column
 * @property string chdate database column
 */
class RoomRequest extends SimpleORMap
{
    private $properties = array();          //the assigned property-requests
    public $last_search_result_count;          //the number of found rooms from last executed search
    private $properties_changed = false;
    private $default_seats;

    static function findByCourse($seminar_id)
    {
        $db = DbManager::get();
        return array_shift(self::findBySql("termin_id = '' AND metadate_id = '' AND seminar_id = " . $db->quote($seminar_id)));
    }

    static function findByDate($termin_id)
    {
        $db = DbManager::get();
        return array_shift(self::findBySql("termin_id = " . $db->quote($termin_id)));
    }

    static function findByCycle($metadate_id)
    {
        $db = DbManager::get();
        return array_shift(self::findBySql("metadate_id = " . $db->quote($metadate_id)));
    }

    static function existsByCourse($seminar_id, $is_open = false)
    {
        $db = DbManager::get();
        $id = self::existsForSQL(($is_open ? "closed = 0 AND " : "") . "termin_id = '' AND metadate_id = '' AND seminar_id = " . $db->quote($seminar_id));
        return $id;
    }

    static function existsByDate($termin_id, $is_open = false)
    {
        $db = DbManager::get();
        $id = self::existsForSQL(($is_open ? "closed = 0 AND " : "") . "termin_id = " . $db->quote($termin_id));
        return $id;
    }

    static function existsByCycle($metadate_id, $is_open = false)
    {
        $db = DbManager::get();
        $id = self::existsForSQL(($is_open ? "closed = 0 AND " : "") . "metadate_id = " . $db->quote($metadate_id));
        return $id;
    }

    public static function existsForSQL($where)
    {
        $db = DBManager::get();
        $sql = "SELECT request_id FROM resources_requests WHERE " . $where;
        return $db->query($sql)->fetchColumn();
    }

    //Konstruktor
    function __construct($id = null)
    {
        $this->db_table = "resources_requests";
        parent::__construct($id);
    }


    function getResourceId()
    {
        return $this->content['resource_id'];
    }

    function getSeminarId()
    {
        return $this->content['seminar_id'];
    }

    function getTerminId()
    {
        return $this->content['termin_id'];
    }

    function getMetadateId()
    {
        return $this->content['metadate_id'];
    }

    function getUserId()
    {
        return $this->content['user_id'];
    }

    function getCategoryId()
    {
        return $this->content['category_id'];
    }

    function getComment()
    {
        return $this->content['comment'];
    }

    function getReplyComment()
    {
        return $this->content['reply_comment'];
    }

    function getClosed()
    {
        return $this->content['closed'];
    }

    function getPropertyState($property_id)
    {
        return $this->properties[$property_id]["state"];
    }

    function getProperties()
    {
        return $this->properties;
    }

    function getAvailableProperties()
    {
        $available_properties = array();
        if ($this->category_id) {
            $db = DBManager::get();

            $st = $db->prepare("SELECT b.property_id as id, b.*
                                FROM resources_categories_properties a
                                LEFT JOIN resources_properties b USING (property_id)
                                WHERE requestable = 1 AND category_id = ?");
            if ($st->execute(array($this->category_id))) {
                $available_properties = array_map('array_shift', $st->fetchAll(PDO::FETCH_ASSOC|PDO::FETCH_GROUP));
            }
        }
        return $available_properties;
    }

    function getSettedPropertiesCount()
    {
        $count = 0;
        foreach ($this->properties as $val) {
            if ($val) $count++;
        }
        return $count;
    }

    function getSeats()
    {
        $available_properties = $this->getAvailableProperties();
        foreach ($this->properties as $key => $val) {
            if ($available_properties[$key]["system"] == 2) return $val["state"];
        }
        return false;
    }

    function setResourceId($value)
    {
        $this->content['resource_id'] = $value;
    }

    function setUserId($value)
    {
        $this->content['user_id'] = $value;
    }

    function setSeminarId($value)
    {
        $this->content['seminar_id'] = $value;
    }

    function setCategoryId($value)
    {
        $this->content['category_id'] = $value;
        if ($this->isFieldDirty('category_id')) {
            $this->inititalizeProperties();
        }
    }

    private function inititalizeProperties()
    {
        $this->properties = array();
        $this->properties_changed = true;
        if ($this->default_seats) {
            foreach ($this->getAvailableProperties() as $key=>$val) {
                if ($val["system"] == 2) {
                    $this->setPropertyState($key, $this->default_seats);
                }
            }
        }
    }

    function setComment($value)
    {
        $this->content['comment'] = $value;
    }

    function setReplyComment($value)
    {
        $this->content['reply_comment'] = $value;
    }

    /**
     * this function changes the state of the room-request
     *
     * possible states are:
     *  0 - room-request is open
     *  1 - room-request has been edited, but no confirmation has been sent
     *  2 - room-request has been edited and a confirmation has been sent
     *  3 - room-request has been declined
     *
     * @param integer $value one of the states
     */
    function setClosed($value)
    {
        $this->content['closed'] = $value;
    }

    function setTerminId($value)
    {
        $this->content['termin_id'] = $value;
    }

    function setMetadateId($value)
    {
        $this->content['metadate_id'] = $value;
    }

    function setPropertyState($property_id, $value) {
        if ($this->properties[$property_id]['state'] != $value) {
            $this->properties_changed = true;
        }
        if ($value) {
            $this->properties[$property_id] = array("state" => $value);
        } else {
            $this->properties[$property_id] = FALSE;
        }
    }

    function setDefaultSeats($value)
    {
        $this->default_seats = (int)$value;
    }

    function searchRoomsToRequest($search_exp, $properties = false)
    {
        $permitted_rooms = null;
        if(getGlobalPerms($GLOBALS['user']->id) != 'admin' && !Config::GetInstance()->getValue('RESOURCES_ALLOW_ROOM_REQUESTS_ALL_ROOMS')){
            $my_rooms = new ResourcesUserRoomsList($GLOBALS['user']->id, false, false, true);
            $global_resources = DBManager::get()
                                ->query("SELECT resource_id FROM resources_objects WHERE owner_id='global'")
                                ->fetchAll(PDO::FETCH_COLUMN);
            $permitted_rooms = array_unique(array_merge(array_keys($my_rooms->getRooms()), $global_resources));
        }
        return $this->searchRooms($search_exp, $properties, 0, 0, true, $permitted_rooms);
    }

    function searchRooms($search_exp, $properties = FALSE, $limit_lower = 0, $limit_upper = 0, $only_rooms = TRUE, $permitted_resources = FALSE) {
        $search_exp = mysql_escape_string($search_exp);
        //create permitted resource clause
        if (is_array($permitted_resources)) {
            $permitted_resources_clause="AND a.resource_id IN ('".join("','",$permitted_resources)."')";
        }

        //create the query
        if ($search_exp && !$properties)
            $query = sprintf ("SELECT a.resource_id, a.name FROM resources_objects a %s WHERE a.name LIKE '%%%s%%' %s ORDER BY a.name", ($only_rooms) ? "INNER JOIN resources_categories b ON (a.category_id=b.category_id AND is_room = 1)" : "", $search_exp, $permitted_resources_clause);

        //create the very complex query for room search AND room propterties search...
        if ($properties) {
            $setted_properties = $this->getSettedPropertiesCount();
            $query = sprintf ("SELECT DISTINCT a.resource_id, b.name %s FROM resources_objects_properties a LEFT JOIN resources_objects b USING (resource_id) WHERE %s ", ($setted_properties) ? ", COUNT(a.resource_id) AS resource_id_count" : "", ($permitted_resources_clause) ? "1 ".$permitted_resources_clause." AND " : "");

            $i=0;
            if ($setted_properties) {
                $available_properties = $this->getAvailableProperties();
                foreach ($this->properties as $key => $val) {
                    if ($val) {
                        //let's create some possible wildcards
                        if (preg_match("/<=/", $val["state"])) {
                            $val["state"] = trim(substr($val["state"], strpos($val["state"], "<")+2, strlen($val["state"])));
                            $linking = "<=";
                        } elseif (preg_match("/>=/", $val["state"])) {
                            $val["state"] = trim(substr($val["state"], strpos($val["state"], "<")+2, strlen($val["state"])));
                            $linking = ">=";
                        } elseif (preg_match("/</", $val["state"])) {
                            $val["state"] = trim(substr($val["state"], strpos($val["state"], "<")+1, strlen($val["state"])));
                            $linking = "<";
                        } elseif (preg_match("/>/", $val["state"])) {
                            $val["state"] = trim(substr($val["state"], strpos($val["state"], "<")+1, strlen($val["state"])));
                            $linking = ">";
                        } elseif ($available_properties[$key]["system"] == "2") {
                            $linking = ">=";
                        } else $linking = "=";

                        $query.= sprintf(" %s (property_id = '%s' AND state %s %s%s%s) ", ($i) ? "OR" : "", $key, $linking,  (!is_numeric($val["state"])) ? "'" : "", $val["state"], (!is_numeric($val["state"])) ? "'" : "");
                        $i++;
                    }
                }
            }

            if ($search_exp)
                $query.= sprintf(" %s (b.name LIKE '%%%s%%' OR b.description LIKE '%%%s%%') ", ($setted_properties) ? "AND" : "", $search_exp, $search_exp);

            $query.= sprintf ("%s b.category_id ='%s' ", ($setted_properties) ? "AND" : "", $this->category_id);

            if ($setted_properties)
                $query.= sprintf (" GROUP BY a.resource_id  HAVING resource_id_count = '%s' ", $i);

            $query.= sprintf ("ORDER BY b.name %s", ($limit_upper) ? "LIMIT ".(($limit_lower) ? $limit_lower : 0).",".($limit_upper - $limit_lower) : "");
        }

        $db = DBManager::get();
        $result = $db->query( $query );

        $found = array();

        foreach( $result as $res ){
            if ($res["name"]) {
                $found [$res["resource_id"]] = $res["name"];
            }
        }

        $this->last_search_result_count = $result->rowCount();
        return $found;
    }

    function restore()
    {
        $found = parent::restore();
        if ($found) {
            $db = DBManager::get();
            $st = $db->prepare("SELECT a.property_id, state, mkdate, chdate, type, name, options, system
                                FROM resources_requests_properties a
                                LEFT JOIN resources_properties b USING (property_id)
                                WHERE a.request_id=? ");
            if ($st->execute(array($this->getId()))) {
                $this->properties = array_map('array_shift', $st->fetchAll(PDO::FETCH_ASSOC|PDO::FETCH_GROUP));
                $this->properties_changed = false;
            }
        } else {
            $this->inititalizeProperties();
        }
        return $found;
    }

    //private
    private function cleanProperties()
    {
        $db = DBManager::get();
        foreach ($this->properties as $key => $val) {
            if ($val)
                $properties[] = $key;
        }
        if (is_array($properties)) {
            $in="('".join("','",$properties)."')";
        }
        $query = sprintf("DELETE FROM resources_requests_properties WHERE %s request_id = '%s' ", (is_array($properties)) ? "property_id  NOT IN ".$in." AND " : "", $this->getId());
        $result = $db->exec( $query );
        return $result > 0 ;
    }

    //private
    private function storeProperties()
    {
        $db = DBManager::get();
        foreach ($this->properties as $key=>$val) {
            $query = sprintf ("REPLACE INTO resources_requests_properties SET request_id = '%s', property_id = '%s', state = '%s', mkdate = '%s', chdate = '%s'", $this->getId(), $key, $val["state"], (!$val["mkdate"]) ? time() : $val["mkdate"], time());

            if ($db->exec( $query ))
                $changed = TRUE;
        }
        if ($this->cleanProperties())
            $changed = TRUE;

        return $changed;
    }

    function checkOpen($also_change = FALSE)
    {
        $db = DBManager::get();
        $existing_assign = false;
        //a request for a date is easy...
        if ($this->termin_id) {
            $query = sprintf ("SELECT assign_id FROM resources_assign WHERE assign_user_id = %s ", $db->quote($this->termin_id));
            $existing_assign = $db->query( $query )->fetchColumn();
        //metadate request
        } elseif ($this->metadate_id){
            $query = sprintf("SELECT count(termin_id)=count(assign_id) FROM termine LEFT JOIN resources_assign ON(termin_id=assign_user_id)
                    WHERE metadate_id=%s" , $db->quote($this->seminar_id));
        //seminar request
        } else {
            $query = sprintf("SELECT count(termin_id)=count(assign_id) FROM termine LEFT JOIN resources_assign ON(termin_id=assign_user_id)
                    WHERE range_id='%s' AND date_typ IN".getPresenceTypeClause(), $this->seminar_id);
            }
        if ($query) {
            $existing_assign = $db->query( $query )->fetchColumn();
        }

        if($existing_assign && $also_change){
            $this->setClosed(1);
            $this->store();
        }
        return (bool)$existing_assign;
    }


    function copy()
    {
        $this->setId($this->getNewId());
        $this->setNew(true);
        $this->properties_changed = true;
    }

    function store()
    {
        if (!$this->user_id) {
            $this->user_id = $GLOBALS['user']->id;
        }
        $this->closed = (int)$this->closed;
        if ($this->resource_id || $this->getSettedPropertiesCount()) {
            if ($this->isNew() && !$this->getId()) {
                $this->setId($this->getNewId());
            }
            if ($this->properties_changed) {
                $properties_changed = $this->properties_changed;
                $properties_stored = $this->storeProperties();
            }
            $stored = parent::store();
            // LOGGING
            $props="";
            foreach ($this->properties as $key => $val) {
                $props.=$val['name']."=".$val['state']." ";
            }
            if (!$props) {
                $props="--";
            }
            if ($this->isNew()) {
                log_event("RES_REQUEST_NEW",$this->seminar_id,$this->resource_id,"Termin: $this->termin_id, Metadate: $this->metadate_id, Properties: $props, Kommentar: $this->comment",$query);
            } else {
                if($properties_changed && !$stored) {
                    $this->triggerChdate();
                }
                if ($this->closed==1 || $this->closed==2) {
                    log_event("RES_REQUEST_RESOLVE",$this->seminar_id,$this->resource_id,"Termin: {$this->termin_id}, Metadate: $this->metadate_id, Properties: $props, Status: ".$this->closed,$query);
                } else if ($this->closed==3) {
                    log_event("RES_REQUEST_DENY",$this->seminar_id,$this->resource_id,"Termin: {$this->termin_id}, Metadate: $this->metadate_id, Properties: $props, Status: ".$this->closed,$query);
                } else {
                    log_event("RES_REQUEST_UPDATE",$this->seminar_id,$this->resource_id,"Termin: {$this->termin_id}, Metadate: $this->metadate_id, Properties: $props, Status: ".$this->closed,$query);
                }
            }
        }
        return $stored || $properties_changed;
    }

    function delete()
    {
        $db = DBManager::get();
        $query = "DELETE FROM resources_requests_properties WHERE request_id=". $db->quote($this->getId());
        $properties_deleted = $db->exec($query);
        // LOGGING
        log_event("RES_REQUEST_DEL",$this->seminar_id,$this->resource_id,"Termin: $this->termin_id, Metadate: $this->metadate_id","");
        return parent::delete() || $properties_deleted;
    }

    function toArray()
    {
        $ret = parent::toArray();
        $ret['properties'] = $this->getProperties();
        return $ret;
    }

    function getType()
    {
        if ($this->termin_id) return 'date';
        if ($this->metadate_id) return 'cycle';
        if ($this->seminar_id) return 'course';
        return null;
    }

    function getStatus()
    {
        switch ($this->getClosed()) {
            case '0'; return 'open'; break;
            case '1'; return 'pending'; break;
            case '2'; return 'closed'; break;
            case '3'; return 'declined'; break;
        }
    }

    function getInfo()
    {
        if ($this->isNew()) {
            if (!($this->getSettedPropertiesCount() || $this->getResourceId())) {
                $requestData[] = _('Die Raumanfrage ist unvollst�ndig, und kann so nicht dauerhaft gespeichert werden!');
            } else {
                $requestData[] = _('Die Raumanfrage ist neu.');
            }
            $requestData[] = '';
        } else {
            $requestData[] = sprintf(_('Erstellt von: %s'), get_fullname($this->user_id));
            $requestData[] = sprintf(_('Erstellt am: %s'), strftime('%x %H:%M', $this->mkdate));
            $requestData[] = sprintf(_('Letzte �nderung: %s'), strftime('%x %H:%M', $this->chdate));
        }
        if ($this->resource_id) {
            $resObject = ResourceObject::Factory($this->resource_id);
            $requestData[] = sprintf(_('Raum: %s'), $resObject->getName());
            $requestData[] = sprintf(_('verantwortlich: %s'), $resObject->getOwnerName());
        } else {
            $requestData[] = _('Es wurde kein spezifischer Raum gew�nscht');
        }
        $requestData[] = '';

        foreach ($this->getAvailableProperties() as $val) {
            if ($this->getPropertyState($val['property_id']) !== null) {
                $state = $this->getPropertyState($val['property_id']);
                $prop = $val['name'].': ';
                if ($val['type'] == 'bool') {
                    if ($state == 'on') {
                        $prop .= _('vorhanden');
                    } else {
                        $prop .= _('nicht vorhanden');
                    }
                } else {
                    $prop .= $state;
                }
                $requestData[] = $prop;
            }
        }
        $requestData[] = '';

        $requestData[] = sprintf(_('Bearbeitung durch den/die RaumadministratorIn: %s'), $this->getStatusExplained());
        $requestData[] = '';

        // if the room-request has been declined, show the decline-notice placed by the room-administrator
        if ($this->getClosed() == 3) {
            $requestData[] = _('Nachricht RaumadministratorIn:');
            $requestData[] = $this->getReplyComment();
        } else {
            $requestData[] = _('Nachricht an den/die RaumadministratorIn:');
            $requestData[] = $this->getComment();
        }
        return join("\n", $requestData);
    }

    function getTypeExplained()
    {
        $ret = '';
        if ($this->termin_id) {
            $ret = _("Einzeltermin der Veranstaltung");
            if (get_object_type($this->termin_id, array('date'))) {
                $termin = new SingleDate($this->termin_id);
                $ret .= chr(10) . '(' . $termin->toString() . ')';
            }
        } elseif ($this->metadate_id) {
            $ret = _("alle Termine einer regelm��igen Zeit");
            if ($cycle = SeminarCycleDate::find($this->metadate_id)) {
                $ret .= chr(10) . ' (' . $cycle->toString('full') . ')';
            }
        } elseif ($this->seminar_id) {
            $ret =  _("alle regelm��igen und unregelm��igen Termine der Veranstaltung");
            if (get_object_type($this->seminar_id, array('sem'))) {
                $course = new Seminar($this->seminar_id);
                $ret .= chr(10) . ' (' . $course->getDatesExport(array('short' => true, 'shrink' => true)) . ')';
            }
        } else {
            $ret = _("Kein Typ zugewiesen");
        }
        return $ret;
    }

    function getStatusExplained()
    {
        if ($this->getClosed() == 0) {
            $txt = _("Die Anfrage wurde noch nicht bearbeitet.");
        } else if ($this->getClosed() == 3) {
            $txt = _("Die Anfrage wurde bearbeitet und abgelehnt.");
        } else {
            $txt = _("Die Anfrage wurde bearbeitet.");
        }
        return $txt;
    }
}
