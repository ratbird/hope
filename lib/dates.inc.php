<?php
# Lifter002: TODO
# Lifter003: TEST
# Lifter007: TODO
# Lifter010: DONE - no forms

/*
dates.inc.php - basale Routinen zur Terminveraltung.
Copyright (C) 2001 Stefan Suchi <suchi@gmx.de>, André Noack <anoack@mcis.de>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

require_once 'lib/datei.inc.php';  // benötigt zum Löschen von Dokumenten
require_once 'config.inc.php';  //Daten
require_once 'lib/functions.php';  //Daten
require_once 'lib/classes/SemesterData.class.php';  //Daten
require_once 'lib/classes/Seminar.class.php';  //Daten
require_once 'lib/calendar_functions.inc.php';
require_once "lib/raumzeit/CycleDataDB.class.php";          // Turnus-Daten
require_once "lib/raumzeit/SingleDate.class.php";           // Einzeltermin
require_once "lib/raumzeit/raumzeit_functions.inc.php"; // Helper-Funktionen

/*
 * getWeekday liefert einen String mit einem Tagesnamen.
 *
 * day_num  integer PHP-konformer Tag (0-6)
 * short    boolean Wenn gesetzt wird der Tag verkürzt zurückgegeben.
 */
function getWeekday($day_num, $short = TRUE) {
    switch ($day_num) {
        case 0:
            $short ? $day = _("So") : $day = _("Sonntag");
            break;
        case 1:
            $short ? $day = _("Mo") : $day = _("Montag");
            break;
        case 2:
            $short ? $day = _("Di") : $day = _("Dienstag");
            break;
        case 3:
            $short ? $day = _("Mi") : $day = _("Mittwoch");
            break;
        case 4:
            $short ? $day = _("Do") : $day = _("Donnerstag");
            break;
        case 5:
            $short ? $day = _("Fr") : $day = _("Freitag");
            break;
        case 6:
            $short ? $day = _("Sa") : $day = _("Samstag");
            break;
    }

    // return i18n of day
    return _($day);
}


function leadingZero($num) {
    if ($num == '') return '00';
    if (strlen($num) < 2) {
        return '0'.$num;
    } else {
        return $num;
    }
}

/* veranstaltung_beginn liefert den tatsächlichen ersten Termin einer Veranstaltung */
function veranstaltung_beginn($seminar_id = '', $return_mode = '') {
    if ($seminar_id == '') return 'dates.inc.php:veranstaltung_beginn - Fehlerhafter Aufruf!';
    $sem = new Seminar($seminar_id);
    return $sem->getFirstDate($return_mode);
}

/*
Die Funktion veranstaltung_beginn_from_metadata errechnet den ersten Seminartermin aus dem Turnus Daten.
Zurueckgegeben wird ausschließlich ein Timestamp
Diese Funktion arbeitet im 'ad hoc' Modus und erwartet die einzelnen Variabeln des Metadaten-Arrays als Uebergabe.
Konkrete Termine werde dabei NICHT mit beruecksichtigt!
*/
function veranstaltung_beginn_from_metadata($reg_irreg, $sem_begin, $start_woche, $start_termin,$turnus_data, $return_mode='int') {
    $ret_time = 0;
    if( $return_mode != 'int'){
        echo "<br>Fehler in dates.inc.php: veranstaltung_beginn_from_metadata() unterstuetzt nur den return mode 'int'.";
        die();
    }
    $semester = SemesterData::GetInstance()->getSemesterDataByDate($sem_begin);
    $dow = date("w", $semester['vorles_beginn']);
    if ($dow <= 5)
        $corr = ($dow -1) * -1;     
    elseif ($dow == 6)
        $corr = 2;
    elseif ($dow == 0)
        $corr = 1;
    else
    $corr = 0;
    
    if(is_array($turnus_data)){
        foreach ($turnus_data as $key => $val) {
            $start_time = mktime ((int)$val['start_stunde'], (int)$val['start_minute'], 0, date("n", $semester['vorles_beginn']), (date("j", $semester['vorles_beginn'])+$corr) + ($val['day'] -1) + ($start_woche * 7), date("Y", $semester['vorles_beginn']));
            if (($start_time < $ret_time) || ($ret_time == 0)) {
                $ret_time = $start_time;
            }
        }
    }
    
    return $ret_time;
}


/*
Die Funktion view_turnus zeigt in einer kompakten Ansicht den Turnus eines Seminars an.
Angezeigt werden bei unregelmaessigen Veranstaltungen gruppierte Termine,
wenn mehrere gleiche Termine an aufeinanderfolgenden Tagen liegen.
Der Parameter short verkuerzt die Ansicht nochmals (fuer besonders platzsparende Ausgabe).
Bei regelmaessigen Veranstaltungen werden die einzelen Zeiten ausgegeben, bei zweiwoechentlichem
Turnus mit dem enstprechenden Zusatz. Short verkuerzt die Ansicht nochmals.
*/
function view_turnus ($seminar_id, $short = FALSE, $meta_data = false, $start_time = false) {

  return Seminar::GetInstance($seminar_id)->getFormattedTurnus($short);
}

/*
 * The function shrink_dates expects an array of dates where the start_time and the end_time is noted
 * and creates a compressed version (spanning f.e. multiple dates).
 *
 * Returns an array, where each element is one condensed entry. (f.e. 10.6 - 14.6 8:00 - 12:00,)
 */
function shrink_dates($dates) {
    $ret = array();

    // check which dates are follow-ups
    for ($i=1; $i<sizeof($dates); $i++) {
        if (((date("G", $dates[$i-1]["start_time"])) == date("G", $dates[$i]["start_time"])) && ((date("i", $dates[$i-1]["start_time"])) == date("i", $dates[$i]["start_time"])) && ((date("G", $dates[$i-1]["end_time"])) == date("G", $dates[$i]["end_time"])) && ((date("i", $dates[$i-1]["end_time"])) == date("i", $dates[$i]["end_time"])))
            $dates[$i]["time_match"]=TRUE;

        if (((date ("z", $dates[$i]["start_time"])-1) == date ("z", $dates[$i-1]["start_time"])) || ((date ("z", $dates[$i]["start_time"]) == 0) && (date ("j", $dates[$i-1]["start_time"]) == 0)))
            if ($dates[$i]["time_match"])
                $dates[$i]["conjuncted"]=TRUE;
    }

    $return_string = '';
    // create text-output
    for ($i=0; $i<sizeof($dates); $i++) {
        if (!$dates[$i]["conjuncted"])
            $conjuncted=FALSE;

        if ((!$dates[$i]["conjuncted"]) || (!$dates[$i+1]["conjuncted"])) {
            // if the current date is a conjunction, add the year
            // to receive an output of the format "dd.mm - dd.mm.yyyy"
            $return_string .= ' ' . getWeekday(date('w', $dates[$i]['start_time'])) .'.';
            if ($dates[$i]['conjuncted']) {
                $return_string .= date (" d.m.Y", $dates[$i]["start_time"]);
            } else {
                $return_string .= date (" d.m.", $dates[$i]["start_time"]);
            }
        }

        if ((!$conjuncted) && ($dates[$i+1]["conjuncted"])) {
            $return_string .= ' -';
            $conjuncted=TRUE;
        } else if ((!$dates[$i+1]["conjuncted"]) && ($dates[$i+1]["time_match"])) {
            $return_string .= ',';
        }

        if (!$dates[$i+1]["time_match"]) {
            $return_string .= ' ' . date("H:i", $dates[$i]["start_time"]);
            if (date("H:i", $dates[$i]["start_time"]) != date("H:i", $dates[$i]["end_time"])) {
                $return_string .= ' - ' . date("H:i", $dates[$i]["end_time"]);
            }
        }
        
        if ($return_string != '' && !$dates[$i+1]['conjuncted'] && !$dates[$i+1]['time_match']) {
            $ret[] = $return_string;
            $return_string = '';
        }
    }

    return $ret;
}

/*
Die Funktion Vorbesprechung ueberpueft, ob es eine Vorbesprechung gibt und gibt in diesem
Falle den entsprechenden Timestamp zurueck. Ansonsten wird FALSE zurueckgegeben.
*/

function vorbesprechung ($seminar_id, $type = 'standard')
{
    $query = "SELECT termin_id
              FROM termine
              WHERE range_id = ? AND date_typ = '2'
              ORDER BY date";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($seminar_id));
    $termin_id = $statement->fetchColumn();

    if (!$termin_id) {
        return false;
    }

    $termin = new SingleDate($termin_id);
    $ret = $termin->toString();
    if ($termin->getResourceID()) {
        $ret .= ', '._("Ort:").' ';
        switch ($type) {
            case 'export':
                $resObj = ResourceObject::Factory($termin->getResourceID());
                $ret .= $resObj->getName();
                break;

            case 'standard':
            default:
                $resObj = ResourceObject::Factory($termin->getResourceID());
                $ret .= $resObj->getFormattedLink(TRUE, TRUE, TRUE);
                break;
        }
    }
    return $ret;
}

/*
Die Funktion get_sem_name gibt den Namen eines Semester, in dem ein uebergebener Timestamp liegt, zurueck
*/

function get_sem_name ($time) {
    $semester = SemesterData::GetInstance()->getSemesterDataByDate($time);
    return $semester["name"];
}

/*
Die Funktion get_sem_num gibt die Nummer eines Semester, in dem ein uebergebener Timestamp liegt, zurueck
*/

function get_sem_num ($time) {
    $all_semester = SemesterData::GetInstance()->getAllSemesterData();
    foreach ($all_semester as $key=>$val)
        if (($time >= $val["beginn"]) AND ($time <= $val["ende"]))
            return $key;

}

function get_sem_num_sem_browse () {
    $all_semester = SemesterData::GetInstance()->getAllSemesterData();
    $time = time();
    $ret = false;
    foreach ($all_semester as $key=>$val){
        if ($ret && ($val["vorles_ende"] >= $time)){
            $ret = $key;
            break;
        }
        if ($time >= $val["vorles_ende"]){
            $ret = true;
        }
    }
    return $ret;
}

/*
Die Funktion get_semester gibt den oder die Semester einer speziellen Veranstaltung aus.
*/

function get_semester($seminar_id, $start_sem_only=FALSE)
{
    $query = "SELECT start_time, duration_time FROM seminare WHERE seminar_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($seminar_id));
    $temp = $statement->fetch(PDO::FETCH_ASSOC);

    $return_string = get_sem_name($temp['start_time']);
    if (!$start_sem_only) {
        if ($temp['duration_time'] > 0) {
            $return_string .= ' - ' . get_sem_name($temp['start_time'] + $temp['duration_time']);
        }
        if ($temp['duration_time'] == -1) {
            $return_string .= ' ' . _('bis unbegrenzt');
        }
    }
    return $return_string;
}


function getCorrectedSemesterVorlesBegin ($semester_num) {
    $all_semester = SemesterData::GetInstance()->getAllSemesterData();

    $vorles_beginn=$all_semester[$semester_num]["vorles_beginn"];

    //correct the vorles_beginn to match monday, if necessary
    $dow = date("w", $vorles_beginn);

    if ($dow <= 5)
        $corr = ($dow -1) * -1;
    elseif ($dow == 6)
        $corr = 2;
    elseif ($dow == 0)
        $corr = 1;
    else
        $corr = 0;

    if ($corr) {
        $vorles_beginn_uncorrected = $vorles_beginn;
        $vorles_beginn = mktime(date("G",$vorles_beginn), date("i",$vorles_beginn), 0, date("n",$vorles_beginn), date("j",$vorles_beginn)+$corr,  date("Y",$vorles_beginn));
    }

    return $vorles_beginn;
}

/*
Die Funktion delete_topic löscht rekursiv alle Postings ab der übergebenen topic_id, der zweite Parameter
muss(!) eine Variable sein, diese wird für jedes gelöschte Posting um eins erhöht
*/

function delete_topic($topic_id, &$deleted)  //rekursives löschen von topics VORSICHT!
{
    if (!$topic_id){ // if topic_id is 0, ALL postings would be deleted !
        return;
    }

    $query = "SELECT topic_id FROM px_topics WHERE parent_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($topic_id));

    while ($next_topic = $statement->fetchColumn()) {
        delete_topic($next_topic, $deleted);
    }

    NotificationCenter::postNotification('PostingWillDelete', $topic_id);

    $query = "DELETE FROM px_topics WHERE topic_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($topic_id));

    NotificationCenter::postNotification('PostingDidDelete', $topic_id);
    $deleted++;

    // gehoerte dieses Posting zu einem Termin?
    // dann Verknuepfung loesen...
    $query = "UPDATE termine SET topic_id = '' WHERE topic_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($topic_id));
}

/*
Die function delete_date löscht einen Termin und verschiebt daran haegende
Ordner in den allgemeinen Ordner.
Der erste Parameter ist die termin_id des zu löschenden Termins.
Der zweite Parameter topic_id gibt an, ob auch die zu diesem Termin gehoerenden
Postings im Forensystem geloescht werden sollen.
0 bzw. FALSE : keine Topics loeschen
> 0 : rekursives loeschen von topic_id
Der dritte Parameter gibt analog an, ob auch die zu diesem Terminen gehoerenden
Folder im Ordnersystem geloescht werden sollen.
Der Rückgabewert der Funktion ist die Anzahl der insgesamt gelöschten Items.
-1 bedeutet einen Fehler beim Loeschen des Termins.
Ausgabe wird keine produziert.
Es erfolgt keine Überprüfung der Berechtigung innerhalb der Funktion,
dies muss das aufrufende Script sicherstellen.
*/

function delete_date($termin_id, $topic_delete = TRUE, $folder_move = TRUE, $sem_id=0) {
    global $RESOURCES_ENABLE, $RELATIVE_PATH_RESOURCES;

    if ($RESOURCES_ENABLE) {
        include_once ($RELATIVE_PATH_RESOURCES."/lib/VeranstaltungResourcesAssign.class.php");
    }

    // Eventuell rekursiv Postings loeschen
    /*if ($topic_delete) { //deprecated at the moment because of bad usabilty (delete date kill whole topic in forum without a notice, that's bad...)
        $query = "SELECT topic_id FROM termine WHERE termin_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($termin_id));
        $topic_id = $statement->fetchColumn();

        if ($topic_id) {
            delete_topic($topic_id, $count);
        }
    }*/

    if (!$folder_move) {
        ## Dateiordner muessen weg!
        recursiv_folder_delete ($termin_id);
    } else {
        ## Dateiordner werden verschoben, wenn Ordner nicht leer, ansonsten auch weg
        if (!doc_count($termin_id))
            recursiv_folder_delete($termin_id);
        else {
            $query = "SELECT folder_id FROM folder WHERE range_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($termin_id));
            $folder_id = $statement->fetchColumn();

            move_item($folder_id, $sem_id, true);

            $query = "UPDATE folder SET name = ?, description = ? WHERE folder_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                _('Dateiordner zu gelöschtem Termin'),
                _('Dieser Ordner enthält Dokumente und Termine eines gelöschten Termins'),
                $folder_id,
            ));
        }
    }

    ## Und den Termin selbst loeschen
    $query = "DELETE FROM termine WHERE termin_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($termin_id));

    if ($statement->rowCount() && $RESOURCES_ENABLE) {
        $insertAssign = new VeranstaltungResourcesAssign($sem_id);
        $insertAssign->killDateAssign($termin_id);
    }
}

/*
Die function delete_range_of_dates löscht Termine mit allen daran haengenden Items.
Der erste Parameter ist die range_id der zu löschenden Termine.
Es koennen also mit einem Aufruf alle Termine eines Seminares,
eines Institutes oder persoenliche Termine eines Benutzers aus der Datenbank entfernt werden.
Dokumente und Literatur an diesen Terminen werden auf jeden Fall gelöscht.
Der zweite Parameter topics gibt an, ob auch die zu diesen Terminen gehoerenden
Postings im Forensystem geloescht werden sollen.
0 bzw. FALSE : keine Topics loeschen
1 bzw. TURE : rekursives Loeschen der Postings
Der Rückgabewert der Funktion ist die Anzahl der gelöschten Termine.
Ausgabe wird keine produziert.
Es erfolgt keine Überprüfung der Berechtigung innerhalb der Funktion,
dies muss das aufrufende Script sicherstellen.
*/

function delete_range_of_dates($range_id, $topics = FALSE)
{
    $count = 0;

    ## Termine finden...
    $query = "SELECT termin_id FROM termine WHERE range_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($range_id));

    while ($termin_id = $statement->fetchColumn()) {       // ...und nacheinander...
        delete_date($termin_id, $topics, true, $range_id);
        $count++;
    }

    return $count;
}


//Checkt, ob Ablaufplantermine zu gespeicherten Metadaten vorliegen
function isSchedule ($sem_id, $presence_dates_only = TRUE, $clearcache = FALSE)
{
    $query = "SELECT COUNT(*)
              FROM termine
              WHERE range_id = ? AND metdate_id != '' AND metadate_id IS NOT NULL";
    if ($presence_dates_only) {
        $query .= "AND date_typ IN " . getPresenceTypeClause();
    }

    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($sem_id));
    
    return $statement->fetchColumn();
}


//Checkt, ob bereits angelegte Termine ueber mehrere Semester laufen
function isDatesMultiSem ($sem_id)
{
    //we load the first date
    $query = "SELECT date FROM termine WHERE range_id = ? ORDER BY date LIMIT 1";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($sem_id));
    $first = $statement->fetchColumn();

    //we load the last date
    $query = "SELECT date FROM termine WHERE range_id = ? ORDER BY date DESC LIMIT 1";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($sem_id));
    $last = $statement->fetchColumn();

    //then we check, if they are in the same semester
    return get_sem_name($first) != get_sem_name($last);
}

/**
* this functions extracts all the dates, which are corresponding to a metadate
*
* @param        string  seminar_id
* @return       array   ["metadate_numer"]["termin_id"]
*               "metadate_number" the numerber of the corresponding metadate. first metadate (in chronological order) is always 0
*               "termin_id" the termin_id that are corresponding to the given metdat_number
*
*/
function getMetadateCorrespondingDates ($sem_id, $presence_dates_only) {

    $sem = Seminar::GetInstance($sem_id);
    $types = getPresenceTypes();
    foreach ($sem->getMetaDates() as $key=>$val) {
        $termine = $sem->getSingleDatesForCycle($key);
        foreach ($termine as $val) {
            if ($presence_dates_only) {
                foreach ($types as $tp) {
                    if ($val->getDateType() == $tp) {
                        $zw[$val->getSingleDateID()] = TRUE;
                        break;
                    }
                }
            } else {
                $zw[$val->getSingleDateID()] = TRUE;
            }
        }
        $result[] = $zw;
        $zw = '';
    }
    return $result;
}

/**
* this functions checks, if a date corresponds with a metadate
*
* @param        string  termin_id
* @return       boolean TRUE, if the date corresponds to a metadate
*
*/
function isMetadateCorrespondingDate ($termin_id, $begin = '', $end = '', $seminar_id='')
{
    $termin = new SingleDate($termin_id);
    if ($termin->getMetaDateID()) {
        return $termin->getRangeId();
    }

    return false;
}

// get_csv_raumbelegung.php
function getCorrespondingMetadates ($termin_id, $begin = '', $end = '', $seminar_id='')
{
    $termin = new SingleDate($termin_id);
    if (!$termin->getMetaDateID()) return false;

    if (!$seminar_id) {
        $seminar_id = $termin->getRangeID();
    }

    $sem = new Seminar($seminar_id);
    $turnus = $sem->getFormattedTurnusDates();
    return $turnus[$termin->getMetaDateID()];
}

/**
* a small helper funktion to get the type query for "Sitzungstermine"
* (this dates are important to get the regularly, presence dates
* for a seminar
*
* @return   string  the SQL-clause to select only the "Sitzungstermine"
*
*/
function getPresenceTypeClause() {
    global $TERMIN_TYP;

    $i=0;
    $typ_clause = "(";
    foreach ($TERMIN_TYP as $key=>$val) {
        if ($val["sitzung"]) {
            if ($i)
                $typ_clause .= ", ";
            $typ_clause .= "'".$key."' ";
            $i++;
        }
    }
    $typ_clause .= ")";

    return $typ_clause;
}

function getPresenceTypes() {
    global $TERMIN_TYP;

    foreach ($TERMIN_TYP as $key=>$val) {
        if ($val["sitzung"]) {
            $types[] = $key;
        }
    }

    return $types;
}

/**
* TerminEingabeHilfe
*
* Liefert HTML-Code für Grafik und popup window für Kalender
*
* @param    int Werte von 1 bis 7, bestimmt welche Formularfeldnamen verwendet werden
* @param    int counter wenn mehrere TerminFelder auf einer Seite
* @param    string  ursprüngliche StartStunde
* @param    string  ursprüngliche StartMinute
* @param    string  ursprüngliche EndStunde
* @param    string  ursprüngliche EndMinute
* @return   string  html-code für popup window
*
*/
function Termin_Eingabe_javascript ($t = 0, $n = 0, $atime=0, $ss = '', $sm = '', $es = '', $em = '', $bla = '') {
    global $auth, $CANONICAL_RELATIVE_PATH_STUDIP, $RELATIVE_PATH_CALENDAR;

    if (!$auth->auth["jscript"]) return '';

    $km = ($auth->auth["xres"] > 650)? 8 : 6;
    $kx = ($auth->auth["xres"] > 650)? 780 : 600;
    $ky = ($auth->auth["yres"] > 490)? 500 : 480;
    $sb = ($auth->auth["yres"] > 490)? '' : ',scrollbars=yes ';
    $txt = '&nbsp;';
    $at = ($atime)? '&atime='.$atime:'';
    $q = ($ss !== '')? "&ss={$ss}&sm={$sm}&es={$es}&em={$em}":'';
    $txt .= "<a href=\"javascript:window.open('";
    $txt .= "termin_eingabe_dispatch.php?mcount={$km}&element_switch={$t}&c={$n}{$at}{$q}{$bla}', 'kalender', 'dependent=yes $sb, width=$kx, height=$ky');void(0);";
    $txt .= '"><img src="' . Assets::image_path('popupcalendar.png') . '" class="text-top" ';
    $txt .= tooltip(_("Für eine Eingabehilfe zur einfacheren Terminwahl bitte hier klicken."),TRUE,FALSE);
    $txt .= '></a>';

    return  $txt;
}                                                                                               

/**
 * Return an array of room snippets, possibly linked
 *
 * @param array $rooms  an associative array of rooms
 * @param bool  $html   true if you want links, otherwise false
 *
 * @return array  an array of (formatted) room snippets
 */
function getFormattedRooms($rooms, $link = false)
{
    $room_list = array();

    if (is_array($rooms)) {
        foreach ($rooms as $room_id => $count) {
            if ($room_id) {
                $resObj =& ResourceObject::Factory($room_id);
                if ($link) {
                    $room_list[] = $resObj->getFormattedLink(TRUE, TRUE, TRUE,
                        'view_schedule', 'no_nav', false, htmlReady($resObj->getName()));
                } else {
                    $room_list[] = htmlReady($resObj->getName());
                }
            }
        }
    }

    return $room_list;
}

/**
 * Return an array of room snippets without any formatting
 *
 * @param array $rooms  an associative array of rooms
 *
 * @return array  an array of room snippets
 */
function getPlainRooms($rooms) {
    $room_list = array();

    if (is_array($rooms)) {
        foreach ($rooms as $room_id => $count) {
            if ($room_id) {
                $resObj =& ResourceObject::Factory($room_id);
                $room_list[] = $resObj->getName();
            }
        }
    }

    return $room_list;
}
