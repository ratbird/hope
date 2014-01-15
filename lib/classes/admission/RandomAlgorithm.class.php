<?php

require_once('lib/classes/admission/AdmissionAlgorithm.class.php');

class RandomAlgorithm extends AdmissionAlgorithm {

    public function run($courseSet) {
        if ($courseSet->hasAdmissionRule('LimitedAdmission')) {
            return $this->distributeByPriorities($courseSet);
        } else {
            return $this->distributeByCourses($courseSet);
        }
    }

    private function distributeByCourses($courseSet)
    {
        Log::DEBUG('start seat distribution for course set: ' . $courseSet->getId());
        foreach ($courseSet->getCourses() as $course_id) {
            $course = Course::find($course_id);
            $free_seats = $course->getFreeSeats();
            $claiming_users = AdmissionPriority::getPrioritiesByCourse($courseSet->getId(), $course->id);
            $factored_users = $courseSet->getUserFactorList();
            //apply bonus/malus to users, exclude participants
            foreach(array_keys($claiming_users) as $user_id) {
                if (!$course->getParticipantStatus($user_id)) {
                    $claiming_users[$user_id] = 1;
                    if (isset($factored_users[$user_id])) {
                        $claiming_users[$user_id] *= $factored_users[$user_id];
                    }
                    Log::DEBUG(sprintf('user %s gets factor %s', $user_id, $claiming_users[$user_id]));
                } else {
                    unset($claiming_users[$user_id]);
                    Log::DEBUG(sprintf('user %s is already %s, ignoring', $user_id, $course->getParticipantStatus($user_id)));
                }
            }
            Log::DEBUG(sprintf('distribute %s seats on %s claiming in course %s', $free_seats, count($claiming_users), $course->id));
            $claiming_users = $this->rollTheDice($claiming_users);
            Log::DEBUG('the die is cast: ' . print_r($claiming_users,1));
            $chosen_ones = array_slice(array_keys($claiming_users),0 , $free_seats);
            Log::DEBUG('chosen ones: ' . print_r($chosen_ones,1));
            $this->addUsersToCourse($chosen_ones, $course);
            if ($free_seats < count($claiming_users)) {
                if (!$course->admission_disable_waitlist) {
                    $free_seats_waitlist = $course->admission_waitlist_max ?: count($claiming_users) - $free_seats;
                    $waiting_list_ones = array_slice(array_keys($claiming_users),$free_seats , $free_seats_waitlist);
                    Log::DEBUG('waiting list ones: ' . print_r($waiting_list_ones, 1));
                    $this->addUsersToWaitlist($waiting_list_ones, $course);
                } else {
                    $free_seats_waitlist = 0;
                }
                if (($free_seats_waitlist + $free_seats) < count($claiming_users)) {
                    $remaining_ones = array_slice(array_keys($claiming_users),$free_seats_waitlist + $free_seats);
                    Log::DEBUG('remaining ones: ' . print_r($remaining_ones, 1));
                    $this->notifyRemainingUsers($remaining_ones, $course);
                }
            }
        }
    }


    private function distributeByPriorities($courseSet)
    {
        Log::DEBUG('start seat distribution for course set: ' . $courseSet->getId());
        $limited_admission = $courseSet->getAdmissionRule('LimitedAdmission');
        //all users with their priorities
        $claiming_users = AdmissionPriority::getPriorities($courseSet->getId());

        //all users which have bonus/malus
        $factored_users = $courseSet->getUserFactorList();

        //all users with their max number of courses
        $max_seats_users = array_combine(array_keys($claiming_users),
                                         array_map(function($u) use ($limited_admission) {return $limited_admission->getMaxNumberForUser($u);},
                                                   array_keys($claiming_users)
                                                   )
                                         );
        //unlucky users get a bonus for the next round
        $bonus_users = array();

        //users / courses f�r later waitlist distribution
        $waiting_users = array();

        //number of already distributed seats for users
        $distributed_users = array();

        $prio_mapper = function ($users, $course_id) use ($claiming_users) {
            $mapper = function ($u) use ($course_id) {
                return isset($u[$course_id]) ? $u[$course_id] : null;
            };
            return array_filter(array_map($mapper, array_intersect_key($claiming_users, array_flip($users))));
        };
        //sort courses by highest count of prio 1 applicants
        $stats = AdmissionPriority::getPrioritiesStats($courseSet->getId());
        $courses = array_map(function ($a) {return $a['h'];},$stats);
        arsort($courses, SORT_NUMERIC);
        $max_prio = AdmissionPriority::getPrioritiesMax($courseSet->getId());
        //count already manually distributed places
        $distributed_users = $this->countParticipatingUsers(array_keys($courses), array_keys($claiming_users));
        Log::DEBUG('already distributed users: ' . print_r($distributed_users,1));
        //walk through all prios with all courses
        foreach(range(1, $max_prio) as $current_prio) {
            foreach (array_keys($courses) as $course_id) {
                $current_claiming = array();
                $course = Course::find($course_id);
                $free_seats = $course->getFreeSeats();
                //find users with current prio for this course, if they still need a place
                foreach ($claiming_users as $user_id => $prio_courses) {
                    if ($prio_courses[$course_id] == $current_prio
                        && $distributed_users[$user_id] < $max_seats_users[$user_id]) {
                        //exclude participants
                        if (!$course->getParticipantStatus($user_id)) {
                            $current_claiming[$user_id] = 1;
                            if (isset($factored_users[$user_id])) {
                                $current_claiming[$user_id] *= $factored_users[$user_id];
                            }
                        } else {
                            Log::DEBUG(sprintf('user %s is already %s in course %s, ignoring', $user_id, $course->getParticipantStatus($user_id), $course->id));
                        }
                    }
                }
                //give maximum bonus to users which were unlucky before
                foreach (array_keys($current_claiming) as $user_id) {
                    if ($bonus_users[$user_id] > 0) {
                        $current_claiming[$user_id] = $bonus_users[$user_id] * count($current_claiming) + 1;
                        $bonus_users[$user_id]--;
                    }
                }
                Log::DEBUG(sprintf('distribute %s seats on %s claiming with prio %s in course %s', $free_seats, count($current_claiming),$current_prio, $course->id));
                Log::DEBUG('users to distribute: ' . print_r($current_claiming,1));
                $current_claiming = $this->rollTheDice($current_claiming);
                Log::DEBUG('the die is cast: ' . print_r($current_claiming,1));
                $chosen_ones = array_slice(array_keys($current_claiming),0 , $free_seats);
                Log::DEBUG('chosen ones: ' . print_r($chosen_ones,1));
                $this->addUsersToCourse($chosen_ones, $course, $prio_mapper($chosen_ones, $course->id));
                foreach ($chosen_ones as $one) {
                    $distributed_users[$one]++;
                }
                if ($free_seats < count($current_claiming)) {
                    $remaining_ones = array_slice(array_keys($current_claiming), $free_seats);
                    foreach ($remaining_ones as $one) {
                        $bonus_users[$one]++;
                        $waiting_users[$course_id][] = $one;
                    }
                }
            }
        }
        //distribute to waitlists if applicable
        //Log::DEBUG('waiting list: ' . print_r($waiting_users, 1));
        foreach ($waiting_users as $course_id => $users) {
            $users = array_filter($users, function($user_id) use ($distributed_users, $max_seats_users) {
                return $distributed_users[$user_id] < $max_seats_users[$user_id];});
            $course = Course::find($course_id);
            Log::DEBUG(sprintf('distribute waitlist of %s in course %s', count($users), $course->id));
            if (!$course->admission_disable_waitlist) {
                $free_seats_waitlist = $course->admission_waitlist_max ?: count($users);
                $waiting_list_ones = array_slice($users, $free_seats , $free_seats_waitlist);
                Log::DEBUG('waiting list ones: ' . print_r($waiting_list_ones, 1));
                $this->addUsersToWaitlist($waiting_list_ones, $course, $prio_mapper($waiting_list_ones, $course->id));
                foreach ($waiting_list_ones as $one) {
                    $distributed_users[$one]++;
                }
            } else {
                $free_seats_waitlist = 0;
            }
            if ($free_seats_waitlist < count($users)) {
                $remaining_ones = array_slice($users, $free_seats_waitlist);
                Log::DEBUG('remaining ones: ' . print_r($remaining_ones, 1));
                $this->notifyRemainingUsers($remaining_ones, $course, $prio_mapper($remaining_ones, $course->id));
            }
        }
    }

    public function notifyRemainingUsers($user_list, $course, $prio = null)
    {
        foreach ($user_list as $chosen_one) {
            setTempLanguage($chosen_one);
            $message_title = sprintf(_('Teilnahme an der Veranstaltung %s'), $course->name);
            $message_body = sprintf(_('Sie wurden leider im Losverfahren der Veranstaltung **%s** __nicht__ ausgelost. F�r diese Veranstaltung wurde keine Warteliste vorgesehen.'),
                                       $course->name);
            if ($prio) {
                $message_body .= "\n" . sprintf(_("Sie hatten f�r diese Veranstaltung die Priorit�t %s gew�hlt."), $prio[$chosen_one]);
            }
            messaging::sendSystemMessage($chosen_one, $message_title, $message_body);
            restoreLanguage();
        }
    }

    private function addUsersToWaitlist($user_list, $course, $prio = null)
    {
        $maxpos = $course->admission_applicants->findBy('status', 'awaiting')->orderBy('position desc')->val('position');
        foreach ($user_list as $chosen_one) {
            $maxpos++;
            $new_admission_member = new AdmissionApplication();
            $new_admission_member->user_id = $chosen_one;
            $new_admission_member->position = $maxpos;
            $new_admission_member->status = 'awaiting';
            $course->admission_applicants[] = $new_admission_member;
            if ($new_admission_member->store()) {
                setTempLanguage($chosen_one);
                $message_title = sprintf(_('Teilnahme an der Veranstaltung %s'), $course->name);
                $message_body = sprintf(_('Sie wurden leider im Losverfahren der Veranstaltung **%s** __nicht__ ausgelost. Sie wurden jedoch auf Position %s auf die Warteliste gesetzt. Das System wird Sie automatisch eintragen und benachrichtigen, sobald ein Platz f�r Sie frei wird.'),
                                           $course->name,
                                           $maxpos);
                if ($prio) {
                    $message_body .= "\n" . sprintf(_("Sie hatten f�r diese Veranstaltung die Priorit�t %s gew�hlt."), $prio[$chosen_one]);
                }
                messaging::sendSystemMessage($chosen_one, $message_title, $message_body);
                restoreLanguage();
            }
        }
    }

    private function addUsersToCourse($user_list, $course, $prio = null)
    {
        $seminar = new Seminar($course->id);
        foreach ($user_list as $chosen_one) {
            setTempLanguage($chosen_one);
            $message_title = sprintf(_('Teilnahme an der Veranstaltung %s'), $seminar->getName());
            if ($seminar->admission_prelim) {
                if ($seminar->addPreliminaryMember($chosen_one)) {
                    $message_body = sprintf (_('Sie wurden als TeilnehmerIn der Veranstaltung **%s** ausgelost. Die endg�ltige Zulassung zu der Veranstaltung ist noch von weiteren Bedingungen abh�ngig, die Sie bitte der Veranstaltungsbeschreibung entnehmen.'),
                            $seminar->getName());
                }
            } else {
                if ($seminar->addMember($chosen_one, 'autor')) {
                    $message_body = sprintf (_("Sie wurden als TeilnehmerIn der Veranstaltung **%s** ausgelost. Ab sofort finden Sie die Veranstaltung in der �bersicht Ihrer Veranstaltungen. Damit sind Sie auch als TeilnehmerIn der Pr�senzveranstaltung zugelassen."),
                            $seminar->getName());
                }
            }
            if ($prio) {
                $message_body .= "\n" . sprintf(_("Sie hatten f�r diese Veranstaltung die Priorit�t %s gew�hlt."), $prio[$chosen_one]);
            }
            messaging::sendSystemMessage($chosen_one, $message_title, $message_body);
            restoreLanguage();
        }
    }

    /**
     * Caedite eos. Novit enim Dominus qui sunt eius.
     *
     * @param array $user_list
     */
    private function rollTheDice($user_list)
    {
        $max = count($user_list);
        foreach($user_list as $user_id => $factor) {
            $user_list[$user_id] = $factor * mt_rand(1, $max);
        }
        arsort($user_list, SORT_NUMERIC);
        return $user_list;
    }

    public function countParticipatingUsers($course_ids, $user_ids)
    {
        $distributed_users = array();
        $sum = function($r) use (&$distributed_users) {
            $distributed_users[$r['user_id']] += $r['c'];
        };
        $db = DbManager::get();
        $db->fetchAll("SELECT user_id, COUNT(*) as c FROM seminar_user
            WHERE seminar_id IN(?) AND user_id IN(?) GROUP BY user_id", array($course_ids, $user_ids), $sum);
        $db->fetchAll("SELECT user_id, COUNT(*) as c FROM admission_seminar_user
            WHERE seminar_id IN(?) AND user_id IN(?) GROUP BY user_id", array($course_ids, $user_ids), $sum);
        return $distributed_users;
    }

}

?>