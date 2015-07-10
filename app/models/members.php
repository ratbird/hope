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
        $messaging = new messaging;
        foreach ($users as $user_id) {
            // delete member from seminar
            if ($sem->deleteMember($user_id)) {
                $temp_user = UserModel::getUser($user_id);
                setTempLanguage($user_id);
                $message = sprintf(_("Ihr Abonnement der Veranstaltung **%s** wurde von einem/einer VeranstaltungsleiterIn (%s) oder AdministratorIn aufgehoben."), $this->course_title, get_title_for_status('dozent', 1));
                restoreLanguage();
                $messaging->insert_message($message, $temp_user['username'],
                                '____%system%____', FALSE, FALSE, '1', FALSE, sprintf('%s %s', _('Systemnachricht:'),
                                        _("Abonnement aufgehoben")), TRUE);
                $msgs[] = $temp_user['Vorname'] . ' ' . $temp_user['Nachname'];
            }
        }

        return $msgs;
    }

    public function cancelAdmissionSubscription($users, $status)
    {
        $messaging = new messaging;
        $query = "DELETE FROM admission_seminar_user WHERE seminar_id = ? AND user_id = ? AND status = ?";
        $db = DBManager::get()->prepare($query);
        $cs = Seminar::GetInstance($this->course_id)->getCourseSet();
        foreach ($users as $user_id) {
            $temp_user = UserModel::getUser($user_id);
            if ($cs) {
                $prio_delete = AdmissionPriority::unsetPriority($cs->getId(), $user_id, $this->course_id);
            }
            $db->execute(array($this->course_id, $user_id, $status));
            if ($db->rowCount() > 0 || $prio_delete) {
                setTempLanguage($user_id);
                if ($status !== 'accepted') {
                    $message = sprintf(_("Sie wurden von einem/einer VeranstaltungsleiterIn (%s) oder AdministratorIn von der Warteliste der Veranstaltung **%s** gestrichen und sind damit __nicht__ zugelassen worden."), get_title_for_status('dozent', 1),  $this->course_title);
                } else {
                    $message = sprintf(_("Sie wurden von einem/einer VeranstaltungsleiterIn (%s) oder AdministratorIn aus der Veranstaltung **%s** gestrichen und sind damit __nicht__ zugelassen worden."), get_title_for_status('dozent', 1), $this->course_title);
                }
                restoreLanguage();
                $messaging->insert_message($message, $temp_user['username'],
                                '____%system%____', FALSE, FALSE, '1', FALSE, sprintf('%s %s', _('Systemnachricht:'),
                                        _("nicht zugelassen in Veranstaltung")), TRUE);
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
                                in die Veranstaltung **%s** eingetragen.'), get_title_for_status('dozent', 1), $this->course_title);
                        } else {
                            if (!$accepted) {
                                $message = sprintf(_('Sie wurden vom einem/einer %s oder AdministratorIn
                                    aus der Warteliste in die Veranstaltung **%s** aufgenommen und sind damit zugelassen.'),
                                        get_title_for_status('dozent', 1), $this->course_title);
                            } else {
                                $message = sprintf(_('Sie wurden von einem/einer %s oder AdministratorIn
                                    vom Status **vorl�ufig akzeptiert** zum/r TeilnehmerIn der Veranstaltung **%s**
                                    hochgestuft und sind damit zugelassen.'), get_title_for_status('dozent', 1), $this->course_title);
                            }
                        }

                        $messaging->insert_message($message, $temp_user['username'],
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

        $status = 'autor';

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
                        **vorl�ufig akzeptiert** zum/r TeilnehmerIn der Veranstaltung **%s**
                        hochgestuft und sind damit zugelassen.'), get_title_for_status('dozent', 1), $this->course_title);
                }
            }
            restoreLanguage();
            $messaging->insert_message($message, $user['username'],
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
                        endg�ltig akzeptiert und damit in die Veranstaltung aufgenommen.'), $fullname, $status));
                }
            }
        } else if ($consider_contingent) {
            $msg = MessageBox::error(_('Es stehen keine weiteren Pl�tze mehr im Teilnehmerkontingent zur Verf�gung.'));
        } else {
            $msg = MessageBox::error(_('Beim Eintragen ist ein Fehler aufgetreten.
                Bitte versuchen Sie es erneut oder wenden Sie sich an einen Systemadministrator'));
        }

        return $msg;
    }

    /**
     * Adds the given user to the waitlist of the current course and sends a
     * corresponding message.
     *
     * @param String $user_id The user to add
     * @return bool Successful operation?
     */
    public function addToWaitlist($user_id)
    {
        $messaging = new messaging;
        if (!CourseMember::find(array($this->course_id, $user_id)) && !AdmissionApplication::find($user_id, $this->course_id)) {
            // Find waitlist length -> user will be added last.
            $maxpos = DBManager::get()->fetchColumn("SELECT MAX(`position`) AS maxpos
                FROM `admission_seminar_user`
                WHERE `seminar_id`=?
                    AND `status`='awaiting'",
                array($this->course_id));

            // Fetch the object for the given user ID...
            $temp_user = UserModel::getUser($user_id);

            // .. and create a new waitlist entry.
            $a = new AdmissionApplication();
            $a->user_id = $user_id;
            $a->seminar_id = $this->course_id;
            $a->position = $maxpos + 1;
            $a->status = 'awaiting';

            // Insert user in waitlist at current position.
            if ($a->store()) {
                setTempLanguage($user_id);
                $message = sprintf(_('Sie wurden von einem/einer Veranstaltungsleiter/-in (%s) ' .
                    'oder einem/einer Administrator/-in auf die Warteliste der Veranstaltung **%s** gesetzt.'),
                    get_title_for_status('dozent', 1), $this->course_title);
                restoreLanguage();
                $messaging->insert_message($message, $temp_user['username'],
                    '____%system%____', FALSE, FALSE, '1', FALSE, sprintf('%s %s', _('Systemnachricht:'),
                        _('Auf Warteliste gesetzt')), TRUE);
                return true;
            }
        }
        return false;
    }

    /**
     * Adds the given users to the target course.
     * @param array $users users to add
     * @param string $target_course which course to add users to
     * @param bool $move move users (=delete in source course) or just add to target course?
     * @return array success and failure statuses
     */
    public function sendToCourse($users, $target_course, $move = false)
    {
        $msg = array();
        foreach ($users as $user) {
            if (!CourseMember::exists($target_course, $user)) {
                $m = new CourseMember();
                $m->seminar_id = $target_course;
                $m->user_id = $user;
                $m->status = 'autor';
                if ($m->store()) {
                    if ($move) {
                        CourseMember::find(array($this->course_id, $user))->delete();
                    }
                    $msg['success'][] = $user;
                } else {
                    $msg['failed'][] = $user;
                }
            } else {
                $msg['existing'][] = $user;
            }
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
                 AND a.visible <> 'never'
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
                 AND a.visible <> 'never'
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
                 AND a.visible <> 'never'
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
    function getMembers($sort_status = 'autor', $order_by = 'nachname asc')
    {
        $query = "SELECT su.user_id,username,vorname,nachname,email,status,position,su.mkdate,su.visible,su.comment,
                " . $GLOBALS['_fullname_sql']['full_rev'] . " as fullname
                FROM seminar_user su INNER JOIN auth_user_md5 USING(user_id)
                INNER JOIN user_info USING (user_id)
                WHERE seminar_id = ? ORDER BY position, nachname ASC";
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
        $cs = CourseSet::getSetForCourse($this->course_id);
        $claiming = array();
        if (is_object($cs) && !$cs->hasAlgorithmRun()) {
            foreach (AdmissionPriority::getPrioritiesByCourse($cs->getId(), $this->course_id) as $user_id => $p) {
                $user = User::find($user_id);
                $data = $user->toArray('user_id username vorname nachname email');
                $data['fullname'] = $user->getFullname('full_rev');
                $data['position'] = $p;
                $data['visible'] = 'unknown';
                $data['status'] = 'claiming';
                $claiming[] = $data;
            }
        }

        $query = "SELECT asu.user_id,username,vorname,nachname,email,status,position,asu.mkdate,asu.visible, asu.comment,
                " . $GLOBALS['_fullname_sql']['full_rev'] . " as fullname
                FROM admission_seminar_user asu INNER JOIN auth_user_md5 USING(user_id)
                INNER JOIN user_info USING(user_id)
                WHERE seminar_id = ? ORDER BY position, nachname ASC";
        $st = DBManager::get()->prepare($query);
        $st->execute(array(
            $this->course_id
        ));
        $application_members = SimpleCollection::createFromArray(array_merge($claiming, $st->fetchAll(PDO::FETCH_ASSOC)));
        $filtered_members = array();
        foreach (words('awaiting accepted claiming') as $status) {
            $filtered_members[$status] = $application_members->findBy('status', $status);
            if ($status == $sort_status) {
                $filtered_members[$status]->orderBy($order_by, (strpos($order_by, 'nachname') === false ? SORT_NUMERIC : SORT_LOCALE_STRING));
            }
        }
        return $filtered_members;
    }

    /**
     * Adds given users to the course waitlist, either at list beginning or end.
     * System messages are sent to affected users.
     *
     * @param mixed $users array of user ids to add
     * @param String $which_end 'last' or 'first': which list end to append to
     * @return mixed Array of messages (stating success and/or errors)
     */
    public function moveToWaitlist($users, $which_end)
    {
        $messaging = new messaging;
        // Calculate target waitlist index according to desired appending spot.
        switch ($which_end) {
            // Append users to waitlist end.
            case 'last':
                $maxpos = DBManager::get()->fetchColumn("SELECT MAX(`position`)
                    FROM `admission_seminar_user`
                    WHERE `seminar_id`=?
                        AND `status`='awaiting'", array($this->course_id));
                $waitpos = $maxpos+1;
                break;
            // Prepend users to waitlist start.
            case 'first':
            default:
                // Move all others on the waitlist up by the number of people to add.
                DBManager::get()->execute("UPDATE `admission_seminar_user`
                        SET `position`=`position`+?
                        WHERE `seminar_id`=?
                            AND `status`='awaiting'", array(count($users), $this->course_id));
                $waitpos = 1;
        }

        $curpos = $waitpos;
        foreach ($users as $user_id) {
            $temp_user = UserModel::getUser($user_id);
            // Create new waitlist entry.
            $a = new AdmissionApplication();
            $a->user_id = $user_id;
            $a->seminar_id = $this->course_id;
            $a->position = $curpos;
            $a->status = 'awaiting';
            // Insert user in waitlist at current position.
            if ($a->store()) {
                // Delete member from seminar
                if (CourseMember::find(array($this->course_id, $user_id))->delete()) {
                    setTempLanguage($user_id);
                    $message = sprintf(_('Ihr Abonnement der Veranstaltung **%s** wurde von '.
                        'einem/einer Veranstaltungsleiter/-in (%s) oder Administrator/-in aufgehoben, '.
                        'Sie wurden auf die Warteliste dieser Veranstaltung gesetzt.'),
                        $this->course_title, get_title_for_status('dozent', 1));
                    restoreLanguage();
                    $messaging->insert_message($message, $temp_user['username'],
                        '____%system%____', FALSE, FALSE, '1', FALSE, sprintf('%s %s', _('Systemnachricht:'),
                            _('Abonnement aufgehoben, auf Warteliste gesetzt')), TRUE);
                    $msgs['success'][] = $temp_user['Vorname'] . ' ' . $temp_user['Nachname'];
                    $curpos++;
                // Something went wrong on removing the user from course.
                } else {
                    $a->delete();
                    $msgs['error'][] = $temp_user['Vorname'] . ' ' . $temp_user['Nachname'];
                }
            // Something went wrong on inserting the user in waitlist.
            } else {
                $msgs['error'][] = $temp_user['Vorname'] . ' ' . $temp_user['Nachname'];
            }
        }

        return $msgs;
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

    /*
    * set the user_visibility of all unkowns to their global visibility
    * set tutor and dozent to visible=yes
    */
    function checkUserVisibility()
    {
        $st = DBManager::get()->prepare("SELECT COUNT(*) FROM seminar_user WHERE visible = 'unknown' AND Seminar_id = ?");
        $st->execute(array($this->course_id));
        if ($st->fetchColumn()) {
            $st = DBManager::get()->prepare("UPDATE seminar_user SET visible = 'yes' WHERE status IN ('tutor', 'dozent') AND Seminar_id = ?");
            $st->execute(array($this->course_id));

            $st = DBManager::get()->prepare("UPDATE seminar_user su INNER JOIN auth_user_md5 aum USING(user_id)
                SET su.visible=IF(aum.visible IN('no','never') OR (aum.visible='unknown' AND " . (int)!Config::get()->USER_VISIBILITY_UNKNOWN . "), 'no','yes')
                WHERE Seminar_id = ? AND su.visible='unknown'");
            $st->execute(array($this->course_id));
        }
    }

}
