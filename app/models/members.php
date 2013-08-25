<?php

require_once 'lib/classes/Seminar.class.php';
require_once 'lib/functions.php';

class MembersModel
{

    protected $course_id;
    protected $course_title;

    public function __construct($course_id, $course_title)
    {
        $this->course_id = $course_id;
        $this->course_title = $course_title;
    }

    public function getCountedMembers()
    {
        $count = array();

        $query1 = "SELECT COUNT(user_id) AS members, SUM(admission_studiengang_id != '') AS members_contingent
                   FROM seminar_user
                   WHERE Seminar_id = ? AND status IN ('user', 'autor')";

        $stm = DBManager::get()->prepare($query1);
        $stm->execute(array($this->course_id));

        $temp = $stm->fetch(PDO::FETCH_ASSOC);

        $count['members'] = $temp['members'];
        $count['members_contingent'] = $temp['members_contingent'];

        $query2 = "SELECT COUNT(user_id) AS members, SUM(studiengang_id != '') AS members_contingent
                   FROM admission_seminar_user
                   WHERE seminar_id = ? AND status = 'accepted'";

        $stm2 = DBManager::get()->prepare($query2);
        $stm2->execute(array($this->course_id));
        $temp2 = $stm->fetch(PDO::FETCH_ASSOC);

        $count['members'] += $temp2['members'];
        $count['members_contingent'] += $temp2['members_contingent'];


        return $count;
    }

    public function setAdmissionVisibility($user_id, $status)
    {
        $query = "UPDATE admission_seminar_user SET visible = '?' WHERE user_id = ? AND seminar_id = ?";
        $statement = DBManager::get()->prepare($query);

        return $statement->execute(array($status, $user_id, $this->course_id));
    }

    public function setVisibilty($user_id, $status)
    {

        $query = "UPDATE seminar_user SET visible = ? WHERE user_id = ? AND Seminar_id = ?";
        $statement = DBManager::get()->prepare($query);

        $statement->execute(array($status, $user_id, $this->course_id));

        return $statement->rowCount();
    }

    public function setMemberStatus($members, $status, $next_status, $direction)
    {
        $msgs = array();
        $query = 'UPDATE seminar_user SET status = ?, position = ? WHERE Seminar_id = ? AND user_id = ? AND status = ?';
        $pleasure_statement = DBManager::get()->prepare($query);

        foreach ($members as $user_id) {
            $temp_user = User::find($user_id);
            if ($next_status == 'tutor' && !$GLOBALS['perm']->have_perm('tutor', $user_id)) {
                $msgs['no_tutor'][$user_id] = $temp_user->getFullName();
            } else {
                if ($temp_user) {
                    // get the next position of the user
                    switch ($next_status) {
                        case 'user':
                            // get the current position of the user
                            $next_pos = $this->getPosition($user_id);
                            break;
                        case 'autor':
                            // get the current position of the user
                            $next_pos = $this->getPosition($user_id);
                            break;
                        // set the status to tutor
                        case 'tutor':

                            // get the next position of the user
                            $next_pos = get_next_position($next_status, $this->course_id);
                            // resort the tutors
                            re_sort_tutoren($this->course_id, $this->getPosition($user_id));
                            break;
                    }

                    log_event('SEM_CHANGED_RIGHTS', $this->course_id, $user_id, $next_status, 
                            $this->getLogLevel($direction, $next_status));

                    if (is_null($next_pos)) {
                        $next_pos = 0;
                    }

                    $pleasure_statement->execute(array($next_status, $next_pos, $this->course_id, $user_id, $status));

                    if ($pleasure_statement->rowCount()) {
                        if ($next_status == 'autor') {
                            re_sort_tutoren($this->course_id, $next_pos);
                        }
                        $msgs['success'][$user_id] = $temp_user->getFullName();
                    }
                }
            }
        }

        if (!empty($msgs)) {
            return $msgs;
        } else {
            return false;
        }
    }

    public function cancelSubscription($users)
    {
        $sem = Seminar::GetInstance($this->course_id);
        foreach ($users as $user_id) {
            // delete member from seminar
            if ($sem->deleteMember($user_id)) {
                $temp_user = UserModel::getUser($user_id);
                setTempLanguage($user_id);
                restoreLanguage();
                RemovePersonStatusgruppeComplete($temp_user['username'], $this->course_id);
                // logging
                log_event('SEM_USER_DEL', $this->course_id, $user_id, 'Wurde aus der Veranstaltung rausgeworfen');
                $msgs[] = $temp_user['Vorname'] . ' ' . $temp_user['Nachname'];
            }
        }

        return $msgs;
    }

    public function cancelAdmissionSubscription($users, $status)
    {
        $query = "DELETE FROM admission_seminar_user WHERE seminar_id = ? AND user_id = ? AND status = ?";
        $db = DBManager::get()->prepare($query);
        foreach ($users as $user_id) {
            $temp_user = UserModel::getUser($user_id);
            $db->execute(array($this->course_id, $user_id, $status));

            if ($db->rowCount() > 0) {
                log_event('SEM_USER_DEL', $this->course_id, $user_id, 'Wurde aus der Veranstaltung rausgeworfen');
                $msgs[] = $temp_user['Vorname'] . ' ' . $temp_user['Nachname'];
            }
        }
        return $msgs;
    }

    public function insertAdmissionMember($users, $next_status, $consider_contingent, $accepted = null, $cmd = 'add_user')
    {
        $messaging = new messaging;
        foreach ($users as $user_id => $value) {
            if ($value) {
                $temp_user = UserModel::getUser($user_id);
                if ($temp_user) {
                    $admission_user = insert_seminar_user($this->course_id, $user_id, $next_status, 
                            ($accepted || $consider_contingent ? TRUE : FALSE), $consider_contingent);

                    // only if user was on the waiting list
                    if ($admission_user) {
                        setTempLanguage($user_id);
                        restoreLanguage();

                        if ($cmd == "add_user") {
                            $message = sprintf(_('Sie wurden vom einem/einer %s oder AdministratorIn als TeilnehmerIn 
                                in die Veranstaltung **%s** eingetragen.'), get_title_for_status('dozent', 1), $this->course_id);
                        } else {
                            if (!$accepted) {
                                $message = sprintf(_('Sie wurden vom einem/einer %s oder AdministratorIn 
                                    aus der Warteliste in die Veranstaltung **%s** aufgenommen und sind damit zugelassen.'), 
                                        get_title_for_status('dozent', 1), $this->course_id);
                            } else {
                                $message = sprintf(_('Sie wurden von einem/einer %s oder AdministratorIn 
                                    vom Status **vorläufig akzeptiert** zum/r TeilnehmerIn der Veranstaltung **%s** 
                                    hochgestuft und sind damit zugelassen.'), get_title_for_status('dozent', 1), $this->course_id);
                            }
                        }

                        $messaging->insert_message(mysql_escape_string($message), $temp_user['username'], 
                                '____%system%____', FALSE, FALSE, '1', FALSE, sprintf('%s %s', _('Systemnachricht:'), 
                                        _('Eintragung in Veranstaltung')), TRUE);
                        $msgs[] = $temp_user['Vorname'] . ' ' . $temp_user['Nachname'];
                    }
                }
            }
        }

        // resort admissionlist
        renumber_admission($this->course_id);

        return $msgs;
    }

    public function addMember($user_id, $accepted = null, $consider_contingent = null, $cmd = 'add_user')
    {
        global $perm, $SEM_CLASS, $SEM_TYPE;

        $user = UserModel::getUser($user_id);
        $messaging = new messaging;

        $query = "SELECT DISTINCT user_id
                  FROM seminar_inst
                  LEFT JOIN user_inst USING (Institut_id)
                  WHERE user_id = ? AND seminar_id = ? AND inst_perms NOT IN ('user', 'autor')";
        $db = DBManager::get()->prepare($query);

        if ($SEM_CLASS[$SEM_TYPE[$_SESSION['SessSemName']['art_num']]['class']]['workgroup_mode'] 
                && $perm->have_studip_perm('dozent', $this->course_id) 
                && ($user['perms'] == 'tutor' || $user['perms'] == 'dozent')) {

            if (!$SEM_CLASS[$SEM_TYPE[$_SESSION['SessSemName']['art_num']]['class']]['only_inst_user']) {
                $status = 'tutor';
            } else {
                $db->execute(array($user_id, $this->course_id));
                $status = $db->fetchColumn() ? 'tutor' : 'autor';
                $db->closeCursor();
            }
        } else {
            $status = 'autor';
        }

        // insert
        $copy_course = ($accepted || $consider_contingent) ? TRUE : FALSE;
        $admission_user = insert_seminar_user($this->course_id, $user_id, $status, $copy_course, $consider_contingent, true);

        // create fullname of user of given user informations
        $fullname = $user['Vorname'] . ' ' . $user['Nachname'];

        if ($admission_user) {
            setTempLanguage($user_id);
            if ($cmd == 'add_user') {
                $message = sprintf(_('Sie wurden vom einem/einer %s oder AdministratorIn als TeilnehmerIn 
                    in die Veranstaltung **%s** eingetragen.'), get_title_for_status('dozent', 1), $this->course_title);
            } else {
                if (!$accepted) {
                    $message = sprintf(_('Sie wurden vom einem/einer %s oder AdministratorIn 
                        aus der Warteliste in die Veranstaltung **%s** aufgenommen und sind damit zugelassen.'), 
                            get_title_for_status('dozent', 1), $this->course_title);
                } else {
                    $message = sprintf(_('Sie wurden von einem/einer %s oder AdministratorIn vom Status 
                        **vorläufig akzeptiert** zum/r TeilnehmerIn der Veranstaltung **%s** 
                        hochgestuft und sind damit zugelassen.'), get_title_for_status('dozent', 1), $this->course_title);
                }
            }
            restoreLanguage();
            $messaging->insert_message(mysql_escape_string($message), $user['username'], 
                    '____%system%____', FALSE, FALSE, '1', FALSE, sprintf('%s %s', _('Systemnachricht:'), 
                            _('Eintragung in Veranstaltung')), TRUE);
        }

        //Warteliste neu sortieren
        renumber_admission($this->course_id);

        if ($admission_user) {
            if ($cmd == "add_user") {
                $msg = MessageBox::success(sprintf(_('NutzerIn %s wurde in die Veranstaltung mit dem Status 
                    <b>%s</b> eingetragen.'), $fullname, $status));
            } else {
                if (!$accepted) {
                    $msg = MessageBox::success(sprintf(_('NutzerIn %s wurde aus der Anmelde bzw. Warteliste 
                        mit dem Status <b>%s</b> in die Veranstaltung eingetragen.'), $fullname, $status));
                } else {
                    $msg = MessageBox::success(sprintf(_('NutzerIn %s wurde mit dem Status <b>%s</b> 
                        endgültig akzeptiert und damit in die Veranstaltung aufgenommen.'), $fullname, $status));
                }
            }
        } else if ($consider_contingent) {
            $msg = MessageBox::error(_('Es stehen keine weiteren Plätze mehr im Teilnehmerkontingent zur Verfügung.'));
        } else {
            $msg = MessageBox::error(_('Beim Eintragen ist ein Fehler aufgetreten. 
                Bitte versuchen Sie es erneut oder wenden Sie sich an einen Systemadministrator'));
        }

        return $msg;
    }

    /**
     * Get user informations by first and last name for csv-import
     * @param String $vorname
     * @param String $nachname
     * @return Array
     */
    public function getMemberByIdentification($nachname, $vorname = null)
    {
        // TODO Fullname
        $query = "SELECT a.user_id, username, perms, b.Seminar_id AS is_present
                 FROM auth_user_md5 AS a
                 LEFT JOIN user_info USING (user_id)
                 LEFT JOIN seminar_user AS b ON (b.user_id = a.user_id AND b.Seminar_id = ?)
                 WHERE perms IN ('autor', 'tutor', 'dozent')
                   AND Nachname LIKE ? AND (? IS NULL OR Vorname LIKE ?)
                 ORDER BY Nachname";
        $db = DBManager::get()->prepare($query);

        $db->execute(array($this->course_id, $nachname, $vorname, $vorname));

        return $db->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get user informations by username for csv-import
     * @param String $username
     * @return Array
     */
    public function getMemberByUsername($username)
    {
        // TODO Fullname
        $query = "SELECT a.user_id, username,
                        perms, b.Seminar_id AS is_present
                 FROM auth_user_md5 AS a
                 LEFT JOIN user_info USING (user_id)
                 LEFT JOIN seminar_user AS b ON (b.user_id = a.user_id AND b.Seminar_id = ?)
                 WHERE perms IN ('autor', 'tutor', 'dozent')
                   AND username LIKE ?
                 ORDER BY Nachname";
        $db = DBManager::get()->prepare($query);
        $db->execute(array($this->course_id, $username));

        return $db->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get user informations by generic datafields for csv-import
     * @param String $nachname
     * @param String $datafield_id
     * @return Array
     */
    public function getMemberByDatafield($nachname, $datafield_id)
    {
        // TODO Fullname
        $query = "SELECT a.user_id, username, b.Seminar_id AS is_present
                 FROM datafields_entries AS de
                 LEFT JOIN auth_user_md5 AS a ON (a.user_id = de.range_id)
                 LEFT JOIN user_info USING (user_id)
                 LEFT JOIN seminar_user AS b ON (b.user_id = a.user_id AND b.Seminar_id = ?)
                 WHERE perms IN ('autor', 'tutor', 'dozent')
                   AND de.datafield_id = ? AND de.content = ?
                 ORDER BY Nachname";
        $db = DBManager::get()->prepare($query);
        $db->execute(array($this->course_id, $datafield_id, $nachname));
        return $db->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param string $sort_status
     * @param string $order_by
     * @param string $exclude_invisibles
     * @return SimpleCollection
     */
    function getMembers($sort_status = 'autor', $order_by = 'nachname asc', $exclude_invisibles = null)
    {
        $query = "SELECT su.user_id,username,vorname,nachname,email,status,position,su.mkdate,su.visible," . $GLOBALS['_fullname_sql']['full_rev'] . " as fullname
                FROM seminar_user su INNER JOIN auth_user_md5 USING(user_id)
                INNER JOIN user_info USING(user_id) WHERE seminar_id = ? ORDER BY position, nachname ASC";
        $st = DBManager::get()->prepare($query);
        $st->execute(array(
            $this->course_id
        ));
        $members = SimpleCollection::createFromArray($st->fetchAll(PDO::FETCH_ASSOC));
        $filtered_members = array();
        foreach (words('user autor tutor dozent') as $status) {
            $filtered_members[$status] = $members->findBy('status', $status);
            if ($status == $sort_status) {
                $filtered_members[$status]->orderBy($order_by, (strpos($order_by, 'nachname') === false ? SORT_NUMERIC : SORT_LOCALE_STRING));
            } else {
                $filtered_members[$status]->orderBy(in_array($status, words('tutor dozent')) ? 'position,nachname' : 'nachname asc');
            }
            // filter invisible user
            if ($exclude_invisibles) {
                $filtered_members[$status] = $filtered_members[$status]->filter(function ($user) use($exclude_invisibles)
                {
                    return ($user['visible'] != 'no' || $user['user_id'] == $exclude_invisibles);
                });
            }
        }
        return $filtered_members;
    }

    /**
     * @param string $sort_status
     * @param string $order_by
     * @return SimpleCollection
     */
    function getAdmissionMembers($sort_status = 'autor', $order_by = 'nachname asc')
    {
        $query = "SELECT asu.user_id,username,vorname,nachname,email,status,position,asu.mkdate,asu.visible,studiengang_id," . $GLOBALS['_fullname_sql']['full_rev'] . " as fullname
                FROM admission_seminar_user asu INNER JOIN auth_user_md5 USING(user_id)
                INNER JOIN user_info USING(user_id) WHERE seminar_id = ? ORDER BY position, nachname ASC";
        $st = DBManager::get()->prepare($query);
        $st->execute(array(
            $this->course_id
        ));
        $application_members = SimpleCollection::createFromArray($st->fetchAll(PDO::FETCH_ASSOC));
        $filtered_members = array();
        foreach (words('awaiting accepted') as $status) {
            $filtered_members[$status] = $application_members->findBy('status', $status);
            if ($status == $sort_status) {
                $filtered_members[$status]->orderBy($order_by, (strpos($order_by, 'nachname') === false ? SORT_NUMERIC : SORT_LOCALE_STRING));
            } else {
                $filtered_members[$status]->orderBy('nachname asc', SORT_LOCALE_STRING);
            }
        }
        return $filtered_members;
    }
    
    /**
     * Get the positon out of the database
     * @param String $user_id
     * @return String
     */
    private function getPosition($user_id)
    {
        $query = "SELECT position FROM seminar_user WHERE user_id = ?";
        $position_statement = DBManager::get()->prepare($query);

        $position_statement->execute(array($user_id));
        $pos = $position_statement->fetchColumn();
        $position_statement->closeCursor();

        if ($pos) {
            return $pos;
        } else {
            return null;
        }
    }

    private function getLogLevel($direction, $status)
    {
        if ($direction == 'upgrade') {
            $directionString = 'Hochgestuft';
        } else {
            $directionString = 'Runtergestuft';
        }

        switch ($status) {
            case 'tutor': $log_level = 'zum Tutor';
                break;
            case 'autor': $log_level = 'zum Autor';
                break;
            case 'dozent': $log_level = 'zum Dozenten';
                break;
        }

        return sprintf('%s %s', $directionString, $log_level);
    }

}
