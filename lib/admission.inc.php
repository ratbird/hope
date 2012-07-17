<?
# Lifter002: TODO
# Lifter003: TEST
# Lifter007: TODO
# Lifter010: TODO
/**
* admission.inc.php
*
* the basic library for the admisson system
*
*
* @author       Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup      admission
* @module       admission.inc.php
* @package      studip_core
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// admission.inc.php
// Funktionen die zur Teilnehmerbeschraenkung benoetigt werden
// Copyright (C) 2002 Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+


require_once 'lib/messaging.inc.php';
require_once 'lib/functions.php';
require_once 'lib/language.inc.php';
require_once 'lib/dates.inc.php';
require_once 'lib/classes/StudipAdmissionGroup.class.php';
require_once 'app/models/calendar/schedule.php';


/**
 * Insert a user into a seminar with optional log-message and contingent
 *
 * @param string   $seminar_id
 * @param string   $user_id
 * @param string   $status       status of user in the seminar (user, autor, tutor, dozent)
 * @param boolean  $copy_studycourse  if true, the studycourse is copied from admission_seminar_user
 *                                    to seminar_user. Overrides the $contingent-parameter
 * @param string   $contingent   optional studiengang_id, if no id is given, no contingent is considered
 * @param string   $log_message  optional log-message. if no log-message is given a default one is used
 * @return void
 */
function insert_seminar_user($seminar_id, $user_id, $status, $copy_studycourse = false, $contingent = false, $log_message = false) {
    // get the seminar-object
    $sem = Seminar::GetInstance($seminar_id);

    $admission_status = '';
    $admission_comment = '';
    $mkdate = time();

    $stmt = DBManager::get()->prepare("SELECT * FROM admission_seminar_user
            WHERE seminar_id = ? AND user_id = ?");
    $stmt->execute(array($seminar_id, $user_id));
    if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // copy the studycourse from admission_seminar_user
        if ($copy_studycourse && $data['studiengang_id']) {
            $contingent = $data['studiengang_id'];
        }
        $admission_status = $data['status'];
        $admission_comment = ($data['comment'] == NULL) ? '' : $data['comment'];
        $mkdate = $data['mkdate'];
    }

    // check if there are places left in the submitted contingent (if any)
    //ignore if preliminary
    if ($admission_status != 'accepted' && $contingent && $sem->isAdmissionEnabled() && !$sem->getFreeAdmissionSeats($contingent)) {
        return false;
    }

    // get coloured group as used on meine_seminare
    $colour_group = $sem->getDefaultGroup();

    // LOGGING
    // if no log message is submitted use a default one
    if (!$log_message) {
        $log_message = 'Wurde in die Veranstaltung eingetragen, admission_status: '. $admission_status . ' Kontingent: ' . $contingent;
    }
    log_event('SEM_USER_ADD', $seminar_id, $user_id, $status, $log_message);

    // actually insert the user into the seminar
    $stmt = DBManager::get()->prepare('INSERT INTO seminar_user
        (Seminar_id, user_id, status, admission_studiengang_id, comment, gruppe, mkdate)
        VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute(array($seminar_id, $user_id, $status, ($contingent ? $contingent : ''), $admission_comment, $colour_group, $mkdate));

    if ($admission_status) {
        // delete the entries, user is now in the seminar
        $stmt = DBManager::get()->prepare('DELETE FROM admission_seminar_user
            WHERE user_id = ? AND seminar_id = ?');
        $stmt->execute(array($user_id, $seminar_id));

        //renumber the waiting/accepted/lot list, a user was deleted from it
        renumber_admission($seminar_id);
    }

    removeScheduleEntriesMarkedAsVirtual($user_id, $seminar_id);

    // reload the seminar, the contingents have changed
    $sem->restore();

    return true;
}

/**
 * Removes entries marked in the schedule as virtual.
 * This function serves the following scenario:
 * If a user first added the dates of one seminar to his or her schedule and later did participate in the seminar
 * then the previously as 'virtual' added dates should be removed with this function.
 *
 * @param $user_id the id of the user the schedule belongs to
 * @param $seminar_id the id of the seminar the schedule belongs to
 */
function removeScheduleEntriesMarkedAsVirtual($user_id, $seminar_id)
{
    CalendarScheduleModel::deleteSeminarEntries($user_id, $seminar_id);
}

/**
* This function calculate the remaining places for the "alle"-allocation
*
* The function calculate the remaining places for the "alle"-allocation. It considers
* the places in the other allocations to avoid rounding errors
*
* @param        string  seminar_id  the seminar_id of the seminar to calculate
* @return       integer
*
*/

function get_all_quota($seminar_id) {
    return Seminar::GetInstance($seminar_id)->getFreeAdmissionSeats('all');
}

/**
* This function calculate the remaining places for the complete seminar
*
* This function calculate the remaining places for the complete seminar. It considers all the allocations
* and it avoids rounding errors
*
* @param        string  seminar_id  the seminar_id of the seminar to calculate
* @return       integer
*
*/

function get_free_admission ($seminar_id) {
    return Seminar::GetInstance($seminar_id)->getFreeAdmissionSeats();
}

/**
* This function numbers a waiting list
*
* Use this functions, if a person was moved from the waiting list or there were other changes
* to the waiting list. The User gets a message, if the parameter is set and the position
* on the waiting  list has changed.
*
* @param        string  seminar_id      the seminar_id of the seminar to calculate
* @param        boolean send_message        should a system-message be send?
*
*/

function renumber_admission ($seminar_id, $send_message = TRUE)
{
    $messaging = new messaging;

    //Daten holen / Abfrage ob ueberhaupt begrenzt
    $query = "SELECT Seminar_id, Name
              FROM seminare
              WHERE Seminar_id = ?
                AND ((admission_type = 1 AND admission_selection_take_place = 1) OR
                     (admission_type = 2))";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($seminar_id));
    $temp = $statement->fetch(PDO::FETCH_ASSOC);

    if ($temp) {
        //Liste einlesen
        $query = "SELECT user_id
                  FROM admission_seminar_user
                  WHERE seminar_id = ? AND status = 'awaiting'
                  ORDER BY position";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($temp['Seminar_id']));
        $user_ids = $statement->fetchAll(PDO::FETCH_COLUMN);

        // Prepare statement that updates the position
        $query = "UPDATE admission_seminar_user
                  SET position = ?
                  WHERE user_id = ? AND seminar_id = ?";
        $update_statement = DBManager::get()->prepare($query);

        $position = 1;
        //Liste neu numerieren
        foreach ($user_ids as $user_id) {
            $update_statement->execute(array($position, $user_id, $temp['Seminar_id']));

            //User benachrichten
            if ($update_statement->rowCount() && $send_message) {
                //Usernamen auslesen
                $username = get_username($user_id);

                setTempLanguage($user_id);
                $message = sprintf(_('Sie sind in der Warteliste der Veranstaltung **%s (%s)** hochgestuft worden. Sie stehen zur Zeit auf Position %s.'),
                                   $temp['Name'],
                                   view_turnus($temp['Seminar_id']),
                                   $position);
                restoreLanguage();

                $messaging->insert_message(addslashes($message), $username, '____%system%____', FALSE, FALSE, '1', FALSE, sprintf(_('Ihre Position auf der Warteliste der Veranstaltung %s wurde verändert'), $temp['Name'])); 
            }
            $position += 1;
        }
    }
}



/*
 * Helper-Functions for grouped admissions
 * Grouped seminars MUST HAVE chronologically admission-procedure activated!
 */
function check_group($user_id, $username, $grouped_sems, $cur_name, $cur_id)
{
    global $send_message;

    $messaging = new messaging;

    $query = "SELECT Seminar_id
              FROM seminar_user
              WHERE user_id = ? AND Seminar_id IN (?)";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user_id, $grouped_sems ?: ''));
    $seminar_ids = $statement->fetchAll(PDO::FETCH_COLUMN);

    if (count($seminar_ids) > 0) {
        $seminar_id = reset($seminar_ids);

        $query = "DELETE FROM seminar_user WHERE user_id = ? AND Seminar_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($seminar_id));
        if ($statement->rowCount() > 0) {
            $query = "SELECT Name FROM seminare WHERE Seminar_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($seminar_id));
            $name = $statement->fetchColumn();

            setTempLanguage($user_id);
            $message = sprintf (_('Ihr Abonnement der Veranstaltung **%s (%s)** wurde aufgehoben, da Sie in der Veranstaltung **%s (%s)** von der Warteliste nachgerückt sind. Bei diesen Veranstaltungen handelt sich um gruppierte Veranstaltungen, der Wartelisteneintrag wurde somit bevorzugt behandelt.'), $name, view_turnus($seminar_id), $cur_name, view_turnus($cur_id));
            restoreLanguage();

            $messaging->insert_message(addslashes($message), $username, '____%system%____', FALSE, FALSE, '1', FALSE, _('Ihre abbonierten Veranstaltungen wurden geändert')); 
            update_admission($seminar_id, $send_message);
        }
    }
}

function group_update_admission($seminar_id, $send_message = TRUE)
{
    $messaging=new messaging;

    //get date / check if there is any admission
    $seminar = Seminar::GetInstance($seminar_id);
    $seminar->restoreAdmissionStudiengang();

    //Groups exist only for chronological admissions
    if ($seminar->admission_type != 2) {
        return;
    }

    //check if seminar ist grouped
    $group = StudipAdmissionGroup::GetAdmissionGroupBySeminarId($seminar_id);
    if (is_object($group) && $group->getValue('status') == 0) {
        $grouped_seminars = array_flip($group->getMemberIds());
        unset($grouped_seminars[$seminar_id]);
        $grouped_seminars = array_keys($grouped_seminars);
        //if no more contingents, just fill up
        if (!$seminar->isAdmissionQuotaChecked()) {
            $count = (int)$seminar->getFreeAdmissionSeats();
            //Studis auswaehlen, die jetzt aufsteigen koennen
            $query = "SELECT user_id, username
                      FROM admission_seminar_user
                      LEFT JOIN auth_user_md5 USING (user_id)
                      WHERE seminar_id = ? AND status != 'accepted'
                      ORDER BY position
                      LIMIT " . (int)$count;
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($seminar->getId()));
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                //First we check to parse the grouped seminars
                check_group($row['user_id'], $row['username'], $grouped_seminars, $seminar->getName(), $seminar->getId());
            }
        } else {
            $query = "SELECT user_id, username
                      FROM admission_seminar_user
                      LEFT JOIN auth_user_md5 USING (user_id)
                      WHERE seminar_id = ? AND studiengang_id = ? AND status != 'accepted'
                      ORDER BY position
                      LIMIT " . (int)$free_quota;
            $statement = DBManager::get()->prepare($query);

            //Alle zugelassenen Studiengaenge einzeln bearbeiten
            foreach($seminar->admission_studiengang as $studiengang_id => $studiengang){
                $free_quota = (int) $seminar->getFreeAdmissionSeats($studiengang_id);

                //Studis auswaehlen, die jetzt aufsteigen koennen
                $statement->execute(array(
                    $seminar_id->getId(),
                    $studiengang_id
                ));
                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                    check_group($row['user_id'], $row['username'], $grouped_seminars, $seminar->getName(), $seminar->getId());
                }
            }
        }
    }
}

/*
 * This function is a kind of wrapper, so that no nasty loops between the updaters occur
 *
 **/
function update_admission ($seminar_id, $send_message = TRUE) {
    if(Seminar::GetInstance($seminar_id, TRUE)->isAdmissionEnabled()){
        $group = StudipAdmissionGroup::GetAdmissionGroupBySeminarId($seminar_id);
        if(is_object($group) && $group->getValue('status') == 0){
            group_update_admission($seminar_id, $send_message);
        }
        normal_update_admission($seminar_id, $send_message);
    }
}

/**
* This function updates an admission procedure
*
* The function checks, if user could be insert to the seminar.
* The User gets a message, if he is inserted to the seminar
*
* @param        string  seminar_id      the seminar_id of the seminar to calculate
* @param        boolean send_message        should a system-message be send?
*
*/
function normal_update_admission($seminar_id, $send_message = TRUE)
{
    $messaging=new messaging;

    //Daten holen / Abfrage ob ueberhaupt begrenzt
    $seminar = Seminar::GetInstance($seminar_id);

    if($seminar->isAdmissionEnabled()){

        $seminar->restoreAdmissionStudiengang();

        $sem_preliminary = ($seminar->admission_prelim == 1);

        //Veranstaltung einfach auffuellen (nach Lostermin und Ende der Kontingentierung)
        if (!$seminar->isAdmissionQuotaChecked()) {
            //anzahl der freien Plaetze holen
            $count = (int)$seminar->getFreeAdmissionSeats();

            //Studis auswaehlen, die jetzt aufsteigen koennen
            $query = "SELECT user_id, username, studiengang_id
                      FROM admission_seminar_user
                      LEFT JOIN auth_user_md5 USING (user_id)
                      WHERE seminar_id = ? AND status = 'awaiting'
                      ORDER BY position
                      LIMIT " . (int)$count;
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($seminar_id->getId()));
            $temp = $statement->fetchAll(PDO::FETCH_ASSOC);

            foreach ($temp as $row) {
                //ok, here ist the "colored-group" meant (for grouping on meine_seminare), not the grouped seminars as above!
                $group = select_group($seminar->getSemesterStartTime(), $row['user_id']); 

                if (!$sem_preliminary) {
                    $query = "INSERT INTO seminar_user
                                (user_id, Seminar_id, status, gruppe, admission_studiengang_id, mkdate)
                              VALUES (?, ?, 'autor', ?, ?, UNIX_TIMESTAMP())";
                    $statement = DBManager::get()->prepare($query);
                    $statement->execute(array(
                        $row['user_id'],
                        $seminar->getId(),
                        $group,
                        $row['studiengang_id']
                    ));
                    $affected = $statement->rowCount();
                } else {
                    $query = "UPDATE admission_seminar_user
                              SET status = 'accepted'
                              WHERE user_id = ? AND seminar_id = ?";
                    $statement = DBManager::get()->prepare($query);
                    $statement->execute(array(
                        $row['user_id'],
                        $seminar->getId()
                    ));
                    $affected = $statement->rowCount();
                }
                if ($affected > 0) {
                    if (!$sem_preliminary) {
                        $query = "DELETE FROM admission_seminar_user
                                  WHERE user_id = ? AND seminar_id = ?";
                        $statement = DBManager::get()->prepare($query);
                        $statement->execute(array(
                            $row['user_id'],
                            $seminar->getId()
                        ));
                        $affected = $statement->rowCount();
                    } else {
                        $affected = 0;
                    }
                    //User benachrichtigen
                    if (($sem_preliminary || $affected > 0) && $send_message) {
                        setTempLanguage($row['user_id']);
                        if (!$sem_preliminary) {
                            $message = sprintf (_('Sie sind als TeilnehmerIn der Veranstaltung **%s (%s)** eingetragen worden, da für Sie ein Platz frei geworden ist. Ab sofort finden Sie die Veranstaltung in der Übersicht Ihrer Veranstaltungen. Damit sind Sie auch als TeilnehmerIn der Präsenzveranstaltung zugelassen.'), $seminar->getName(), $seminar->getFormattedTurnus(true));
                        } else {
                            $message = sprintf (_('Sie haben den Status vorläufig akzeptiert in der Veranstaltung **%s (%s)** erhalten, da für Sie ein Platz freigeworden ist.'), $seminar->getName(), $seminar->getFormattedTurnus(true));
                        }
                        $subject = sprintf(_("Teilnahme an der Veranstaltung %s"),$seminar->getName());
                        restoreLanguage();

                        $messaging->insert_message(addslashes($message), $row['username'], '____%system%____', FALSE, FALSE, '1', FALSE, addslashes($subject), true);
                    }
                }
            }
            //Warteposition der restlichen User neu eintragen
            renumber_admission($seminar_id, FALSE);

        //Nachruecken in einzelnen Kontingenten veranlassen (nur bei chronologischer Anmeldung)
        } elseif ($seminar->admission_type == 2) {
            // Prepare statement that obtains all valid students
            $query = "SELECT user_id, username, studiengang_id
                      FROM admission_seminar_user
                      LEFT JOIN auth_user_md5 USING (user_id)
                      WHERE seminar_id = :seminar_id AND studiengang_id = :studiengang_id AND status = 'awaiting'
                      ORDER BY position
                      LIMIT :limit";
            $student_statement = DBManager::get()->prepare($query);
            $student_statement->bindValue(':seminar_id', $seminar->getId());

            // Prepare statement that inserts into seminar_user
            $query = "INSERT INTO seminar_user
                        (user_id, Seminar_id, status, gruppe, admission_studiengang_id, mkdate)
                      VALUES (:user_id, :seminar_id, 'autor', :group, :studiengang_id, UNIX_TIMESTAMP())";
            $insert_statement = DBManager::get()->prepare($query);
            $insert_statement->bindValue(':seminar_id', $seminar->getId());
            
            // Prepare statement that updates admission_seminar_user
            $query = "UPDATE admission_seminar_user
                      SET status = 'accepted'
                      WHERE user_id = :user_id AND seminar_id = :seminar_id";
            $update_statement = DBManager::get()->prepare($query);
            $update_statement->bindValue(':seminar_id', $seminar->getId());

            // Prepare statement that deletes from admission_seminar_user
            $query = "DELETE FROM admission_seminar_user
                      WHERE user_id = :user_id AND seminar_id = :seminar_id";
            $delete_statement = DBManager::get()->prepare($query);
            $delete_statement->bindValue(':seminar_id', $seminar->getId());

            //Alle zugelassenen Studiengaenge einzeln bearbeiten
            foreach($seminar->admission_studiengang as $studiengang_id => $studiengang){
                $free_quota = (int) $seminar->getFreeAdmissionSeats($studiengang_id);
                //Studis auswaehlen, die jetzt aufsteigen koennen
                $student_statement->bindValue(':studiengang_id', $studiengang_id);
                $student_statement->bindValue(':limit', $free_quota, PDO::PARAM_INT);
                $student_statement->execute();
                $temp = $student_statement->fetchAll(PDO::FETCH_ASSOC);
                $student_statement->closeCursor();

                foreach ($temp as $row) {
                    //ok, here ist the "colored-group" meant (for grouping on meine_seminare), not the grouped seminars as above!
                    $group = select_group ($seminar->getSemesterStartTime(), $row['user_id']);

                    if (!$sem_preliminary) {
                        $insert_statement->bindValue(':user_id', $row['user_id']);
                        $insert_statement->bindValue(':group', $group);
                        $insert_statement->bindValue(':studiengang_id', $studiengang_id);
                        $insert_statement->execute();
                        $affected = $insert_statement->rowCount();
                    } else {
                        $update_statement->bindValue(':user_id', $row['user_id']);
                        $update_statement->execute();
                        $affected = $update_statement->rowCount();
                    }

                    if ($affected > 0) {
                        if (!$sem_preliminary) {
                            $delete_statement->bindValue(':user_id', $row['user_id']);
                            $delete_statement->execute();
                            $affected = $delete_statement->rowCount();
                        } else {
                            $affected = 0;
                        }
                        //User benachrichtigen
                        if (($sem_preliminary || $affected > 0) && $send_message) {
                            setTempLanguage($row['user_id']);
                            if (!$sem_preliminary) {
                                $message = sprintf (_('Sie sind als TeilnehmerIn der Veranstaltung **%s (%s)** eingetragen worden, da für Sie ein Platz frei geworden ist. Ab sofort finden Sie die Veranstaltung in der Übersicht Ihrer Veranstaltungen. Damit sind Sie auch als TeilnehmerIn der Präsenzveranstaltung zugelassen.'), $seminar->getName(), $seminar->getFormattedTurnus(true));
                            } else {
                                $message = sprintf (_('Sie haben den Status vorläufig akzeptiert in der Veranstaltung **%s (%s)** erhalten,  da für Sie ein Platz freigeworden ist.'), $seminar->getName(), $seminar->getFormattedTurnus(true));
                            }
                            $subject = sprintf(_('Teilnahme an der Veranstaltung %s'), $seminar->getName());
                            restoreLanguage();

                            $messaging->insert_message(addslashes($message), $row['username'], '____%system%____', FALSE, FALSE, '1', FALSE, addslashes($subject), true);
                        }
                    }
                }
            }
            //Warteposition der restlichen User neu eintragen
            renumber_admission($seminar_id, $send_message);
        }
        $seminar->restore();
    }
}


/**
* This function checks, if an admission procedure has to start
*
* The function will start a fortune procedure and ends the allocations. It will check ALL
* seminars in the admission system, but it do not much if there are no seminars to handle.
*
* @param        boolean send_message        should a system-message be send?
*
*/
function check_admission ($send_message=TRUE)
{
    $messaging = new messaging;

    //Daten holen / Abfrage ob ueberhaupt begrenzt
    $query = "SELECT Seminar_id
              FROM seminare
              WHERE admission_endtime != -1 AND admission_endtime <= UNIX_TIMESTAMP()
                AND admission_type IN (1,2)
                AND (admission_selection_take_place IS NULL OR admission_selection_take_place = 0)
                AND visible = 1";
    $statement = DBManager::get()->query($query);
    $seminar_ids = $statement->fetchAll(PDO::FETCH_COLUMN);

    if (count($seminar_ids) === 0) {
        return;
    }

    // Prepare user statement
    $query = "SELECT user_id, username, studiengang_id
              FROM admission_seminar_user
              LEFT JOIN auth_user_md5 USING (user_id)
              WHERE status != 'accepted' AND seminar_id = :seminar_id 
                AND IFNULL(:studiengang_id, studiengang_id) = studiengang_id
              ORDER BY RAND()
              LIMIT :limit";
    $users_statement = DBManager::get()->prepare($query);
    
    // Prepare user statement without limit
    $query = "SELECT user_id
              FROM admission_seminar_user
              LEFT JOIN auth_user_md5 USING (user_id)
              WHERE seminar_id = ? AND status != 'accepted'
              ORDER BY RAND()";
    $users2_statement = DBManager::get()->prepare($query);

    // Prepare user statement (no random order)
    $query = "SELECT user_id, username, position
              FROM admission_seminar_user
              LEFT JOIN auth_user_md5 USING (user_id)
              WHERE seminar_id = ? AND status != 'accepted'
              ORDER BY position";
    $users3_statement = DBManager::get()->prepare($query);

    // Prepare promote statement
    $query = "UPDATE admission_seminar_user
              SET status = 'accepted'
              WHERE Seminar_id = ? AND user_id = ?";
    $promote_statement = DBManager::get()->prepare($query);

    // Prepare insert statement
    $query = "INSERT INTO seminar_user (Seminar_id, user_id, status, gruppe, admission_studiengang_id, mkdate)
              VALUES (?, ?, 'autor', ?, ?, UNIX_TIMESTAMP())";
    $insert_statement = DBManager::get()->prepare($query);

    // Prepare delete statement
    $query = "DELETE FROM admission_seminar_user
              WHERE user_id = ? AND seminar_id = ?";
    $delete_statement = DBManager::get()->prepare($query);

    // Prepare position statement
    $query = "UPDATE admission_seminar_user
              SET position = ?, status = 'awaiting'
              WHERE user_id = ? AND seminar_id = ?";
    $position_statement = DBManager::get()->prepare($query);

    ignore_user_abort(true);
    if (!ini_get('safe_mode')) {
        set_time_limit(0);
    }
    foreach ($seminar_ids as $seminar_id) {
        $seminar = Seminar::GetInstance($seminar_id);
        $seminar->restore();
        $sem_preliminary = ($seminar->admission_prelim == 1);

        if ($seminar->admission_type == 1) { //nur Losveranstaltungen losen
            //Check, if locked
            if ($seminar->admission_selection_take_place != 0) {
                break; //Someone has locked or checked the Veranstaltung in the meanwhile
            }

            //Veranstaltung locken
            $seminar->admission_selection_take_place = -1;
            $seminar->store(false);
            $seminar->restoreAdmissionStudiengang();
            $users_to_move = array();
            if ($seminar->isAdmissionQuotaEnabled()) {
                //Alle zugelassenen Studiengaenge einzeln auslosen
                foreach ($seminar->admission_studiengang as $studiengang_id => $quota_info) {
                    $tmp_admission_quota = (int)$seminar->getFreeAdmissionSeats($studiengang_id);
                    if ($tmp_admission_quota > 0) {
                        //Losfunktion
                        $users_statement->bindValue(':seminar_id', $seminar->getId());
                        $users_statement->bindValue(':studiengang_id', $studiengang_id);
                        $users_statement->bindValue(':limit', $tmp_admission_quota, PDO::PARAM_INT);
                        $users_statement->execute();
                        $temp = $users_statement->fetchAll(PDO::FETCH_ASSOC);
                        $users_statement->closeCursor();

                        //User aus admission_Seminar_user in seminar_user verschieben oder in Status "vorläufig akzeptiert" setzen
                        $users_to_move = array_merge($users_to_move, $temp);
                    }
                }
            } else {
                $tmp_admission_quota = (int)$seminar->getFreeAdmissionSeats();
                if ($tmp_admission_quota > 0) {
                    //Losfunktion
                    $users_statement->bindValue(':seminar_id', $seminar->getId());
                    $users_statement->bindValue(':studiengang_id', NULL);
                    $users_statement->bindValue(':limit', $tmp_admission_quota, PDO::PARAM_INT);
                    $users_statement->execute();
                    $temp = $users_statement->fetchAll(PDO::FETCH_ASSOC);
                    $users_statement->closeCursor();

                    //User aus admission_Seminar_user in seminar_user verschieben oder in Status "vorläufig akzeptiert" setzen
                    $users_to_move = array_merge($users_to_move, $temp);
                }
            }

            foreach ($users_to_move as $winner) {
                if ($sem_preliminary) {
                    // Bei Seminaren mit vorläufiger Akzeptierung wird nicht in die Teilnehmerliste
                    // gelost, sondern der Status wird auf "accepted" gesetzt
                    $promote_statement->execute(array(
                        $seminar->getId(),
                        $winner['user_id']
                    ));
                    if ($send_message) {
                        setTempLanguage($winner['user_id']);
                        $message = sprintf (_('Sie wurden als TeilnehmerIn der Veranstaltung **%s** ausgelost. Die endgültige Zulassung zu der Veranstaltung ist noch von weiteren Bedingungen abhängig, die Sie bitte der Veranstaltungsbeschreibung entnehmen.'), 
                                            $seminar->getName());
                        restoreLanguage();
                        $messaging->insert_message(addslashes($message), $winner['username'], '____%system%____', FALSE, FALSE, '1', FALSE, sprintf(_('Teilnahme an der Veranstaltung %s'), $seminar->getName())); 
                    }
                } else {
                    $group = select_group($seminar->getSemesterStartTime(), $winner['user_id']);
                    $insert_statement->execute(array(
                        $seminar->getId(),
                        $winner['user_id'],
                        $group,
                        $winner['studiengang_id']
                    ));
                    if ($insert_statement->rowCount()) {
                        $delete_statement->execute(array(
                            $winner['user_id'],
                            $seminar->getId()
                        ));

                        //User benachrichten
                        if ($delete_statement->rowCount() && $send_message) {
                            setTempLanguage($winner['user_id']);
                            $message = sprintf (_("Sie wurden als TeilnehmerIn der Veranstaltung **%s** ausgelost. Ab sofort finden Sie die Veranstaltung in der Übersicht Ihrer Veranstaltungen. Damit sind Sie auch als TeilnehmerIn der Präsenzveranstaltung zugelassen."),
                                                $seminar->getName());
                            restoreLanguage();
                            $messaging->insert_message(addslashes($message), $winner['username'], '____%system%____', FALSE, FALSE, '1', FALSE, sprintf(_('Teilnahme an der Veranstaltung %s'),$seminar->getName())); 
                        }
                    }
                }
            }

            //Alle anderen Teilnehmer in der Warteliste losen
            $users2_statement->execute(array($seminar->getId()));
            $user_ids = $users2_statement->fetchAll(PDO::FETCH_COLUMN);
            $users2_statement->closeCursor();

            //Warteposition ablegen
            $position = 1;
            foreach ($user_ids as $user_id) {
                $position_statement-execute(array(
                    $position,
                    $user_id,
                    $seminar->getId()
                ));

                $position += 1;
            }
        }

        //Veranstaltung lock aufheben und erfolgreichen Losvorgang eintragen bzw. verstreichen der Kontingentierungsfrist notieren
        $seminar->admission_selection_take_place = 1;
        $seminar->store(false);

        //evtl. verbliebene Plaetze auffuellen
        normal_update_admission($seminar->getId(), $send_message);

        //User benachrichten (nur bei Losverfahren, da Warteliste erst waehrend des Losens generiert wurde)
        //verbleibende Warteliste löschen, wenn keine Warteliste vorgesehen
        if ($send_message && ($seminar->admission_type == 1)) {
            $users3_statement->execute(array($seminar->getId()));

            while ($row = $users3_statement->fetch(PDO::FETCH_ASSOC)) {
                setTempLanguage($row['user_id']);
                if (!$seminar->admission_disable_waitlist) {
                    $message = sprintf(_('Sie wurden leider im Losverfahren der Veranstaltung **%s** __nicht__ ausgelost. Sie wurden jedoch auf Position %s auf die Warteliste gesetzt. Das System wird Sie automatisch eintragen und benachrichtigen, sobald ein Platz für Sie frei wird.'),
                                       $seminar->getName(),
                                       $row['position']);
                } else {
                    $message = sprintf(_('Sie wurden leider im Losverfahren der Veranstaltung **%s** __nicht__ ausgelost. Für diese Veranstaltung wurde keine Warteliste vorgesehen.'),
                                       $seminar->getName());
                    $delete_statement->execute(array(
                        $row['user_id'],
                        $seminar->getId()
                    ));
                }
                $messaging->insert_message(addslashes($message), $row['username'], '____%system%____', FALSE, FALSE, '1');
                restoreLanguage();
            }
            
            $users3_statement->closeCursor();
        }
    }
}

/**
* sets a user on a waiting list for a registration procedure
*
* if applicable ($status == 'awaiting') returns the position
*
* @param        string  user_id
* @param        string  seminar_id
* @param        string  status              'claiming','awaiting','accepted'
* @param        string  studiengang_id
* @param        string  comment
* @return       integer position on waiting list
*
*/
function admission_seminar_user_insert($user_id, $seminar_id, $status, $studiengang_id = '', $comment = '')
{
    if($status == 'claiming' || $status == 'accepted') {
        $query = "INSERT INTO admission_seminar_user
                    (user_id, seminar_id, status, studiengang_id, mkdate, comment)
                  VALUES (?, ?, ?, ?, UNIX_TIMESTAMP(), ?)";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $user_id,
            $seminar_id,
            $status,
            $studiengang_id,
            $comment
        ));
    } elseif ($status == 'awaiting'){
        $query = "INSERT INTO admission_seminar_user
                    (user_id, seminar_id, studiengang_id, status, mkdate, comment, position)
                  SELECT ?, ?, ?, 'awaiting', UNIX_TIMESTAMP(), ?, IFNULL(MAX(position), 0) + 1
                  FROM admission_seminar_user
                  WHERE seminar_id = ? AND status != 'accepted'";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $user_id,
            $seminar_id,
            $studiengang_id,
            $comment,
            $seminar_id
        ));
    }
    return admission_seminar_user_get_position($user_id, $seminar_id);
}

/**
* returns the position for a user on a waiting list
*
* if the user is not found false is returned, return true if the user is found but
* no position is available
*
* @param        string  user_id
* @param        string  seminar_id
* @return       integer position in waiting list or false if not found
*
*/
function admission_seminar_user_get_position($user_id, $seminar_id)
{
    $query = "SELECT IFNULL(position, 'na')
              FROM admission_seminar_user
              WHERE user_id = ? AND seminar_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user_id, $seminar_id));
    $position = $statement->fetchColumn();

    return $position == 'na' ? true : $position;
}


/**
 * this function returns a string representation of the differences between two admission_data-arrays
 *
 * @param mixed $aado the array holding the original data
 * @param mixed $aado the array holding the changed data

 * @return mixed an array holding each change in its string representation
 */
function get_readable_admission_difference ($aado, $aad) {
    $message = array();

    $changes = array_diff_assoc($aad, $aado);

    foreach ($changes as $field => $value) {
        switch ($field) {
            case 'admission_type':
                $message[] = 'Anmeldeverfahren: '. get_admission_description($field, $aado[$field])
                    .' -> '. get_admission_description($field, $aad[$field]);
                break;

            case 'sem_admission_start_date':
                if ( $aado[$field] <= 0 ) $before = 'keine'; else $before = date('d.m.Y H:i', $aado[$field]);
                if ( $aad[$field]  <= 0 ) $after  = 'keine'; else $after  = date('d.m.Y H:i', $aad[$field]);
                $message[] = 'Startzeit: '. $before .' -> '. $after;
                break;

            case 'sem_admission_end_date':
                if ( $aado[$field] <= 0 ) $before = 'keine'; else $before = date('d.m.Y H:i', $aado[$field]);
                if ( $aad[$field]  <= 0 ) $after  = 'keine'; else $after  = date('d.m.Y H:i', $aad[$field]);
                $message[] = 'Endzeit: '. $before .' -> '. $after;
                break;

            case 'read_level':
                $message[] = 'Lesezugriff: '. get_admission_description($field, $aado[$field])
                    .' -> '. get_admission_description($field, $aad[$field]);
                break;

            case 'write_level':
                $message[] = 'Schreibzugriff: '. get_admission_description($field, $aado[$field])
                    .' -> '. get_admission_description($field, $aad[$field]);
                break;

            case 'passwort':
                $message[] = 'Passwort: '. $aado[$field] .' -> '. $aad[$field];
                break;

            case 'admission_prelim':
                $message[] = 'Anmeldemodus: '. get_admission_description($field, $aado[$field])
                    .' -> '. get_admission_description($field, $aad[$field]);
                break;

            case 'admission_prelim_txt':
                $message[] = 'Hinweistext Anmeldemodus: '. $aado[$field] ."<br> -> ". $aad[$field];
                break;

            case 'admission_disable_waitlist':
                $message[] = 'Warteliste wurde '. (($aad[$field] == 0) ? 'aktiviert' : 'deaktiviert');
                break;

            case 'admission_turnout':
                $message[] = 'Teilnehmerzahl: '. $aado[$field] .' -> '. $aad[$field];
                break;

            case 'admission_binding':
                $message[] = 'Verbindliche Anmeldung wurde '. (($aad[$field] == 1) ? 'aktiviert' : 'deaktiviert');
                break;

            case 'admission_enable_quota':
                $message[] = 'Die prozentuale Kontingentierung wurde '. (($value == 1) ? 'aktiviert' : 'deaktiviert');
                break;

            case 'admission_endtime':
                $message[] = 'Das Enddatum für die Kontingentierung wurde auf '. date('d.m.Y H:i', $value) .' gesetzt.';
                break;

        }

    }

    // check, if something has been deleted
    foreach (array_diff_assoc((array)$aado['studg'], (array)$aad['studg']) as $id => $data) {
        $message[] = 'Der Studiengang '. $data['name'] 
                   . ' wurde aus der Kontingentierung entfernt.';
    }

    // check, if something has been added
    foreach (array_diff_assoc((array)$aad['studg'], (array)$aado['studg']) as $id => $data) {
        $message[] = 'Der Studiengang '. $data['name']
                   . ($data['ratio'] ? ' ('. $data['ratio'] .'%)' : '')
                   . ' wurde der Kontingentierung hinzugefügt.';
    }

    // check for changed ratios
    if (!empty($aado['studg'])) {
        foreach ($aado['studg'] as $id => $data) {
            if  ($aad['studg'][$id] &&  $aad['studg'][$id]['ratio'] != $data['ratio']) {
                $message[] = 'Der Prozentsatz bei '. $data['name'] .' wurde von '
                           . $data['ratio'] . '% auf '.  $aad['studg'][$id]['ratio'] .'% geändert.';
            }
        }
    }

    return $message;
}


/**
 * this function returns a readable representation of the following admission_data:
 *  - admission_type
 *  - read_level
 *  - write_level
 *
 * @param string $type one of the possible fields to get the string representation for
 * @param mixed $value the value for the value
 *
 * @return string string representation of $value
 */
function get_admission_description ($type, $value) {
    switch ($type) {
        case 'admission_type':
            switch ( $value ) {
                case 0:
                    $ergebnis = "Ohne";
                    break;
                case 1:
                    $ergebnis = "Los";
                    break;
                case 2:
                    $ergebnis = "Chronologisch";
                    break;
                case 3:
                    $ergebnis = "Gesperrt";
                    break;
            }
        break; // admission_type

        case 'read_level':
        case 'write_level':
            switch ( $value ) {
                case 0:
                    $ergebnis = 'freier Zugriff';
                    break;

                case 1:
                    $ergebnis = 'in Stud.IP angemeldet';
                    break;

                case 2:
                    $ergebnis = 'nur mit Passwort';
                    break;

                default:
                    $ergebnis = $value;
                    break;
            }
        break;

        case 'admission_prelim':
            switch ( $value ) {
                case 0:
                    $ergebnis = 'Direkter Eintrag';
                    break;

                case 1:
                    $ergebnis = 'Vorläufiger Eintrag';
                    break;
            }
        break;
    }

    return $ergebnis;
}

