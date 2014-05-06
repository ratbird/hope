<?php

/**
 * Vote.php
 * model class for table vote
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Florian Bieringer <florian.bieringer@uni-passau.de>
 * @copyright   2013 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       3.1
 */
class StudipVote extends SimpleORMap {

    public function __construct($id = null) {
        $this->db_table = 'vote';
        $this->has_many['answers'] = array(
            'class_name' => 'VoteAnswer'
        );
        $this->has_many['anonymous_users'] = array(
            'class_name' => 'VoteUser'
        );
        $this->has_one['author'] = array(
            'class_name' => 'User',
            'foreign_key' => 'author_id',
            'assoc_func' => 'findByUser_id'
        );
        $this->additional_fields['users'] = true;
        $this->additional_fields['count'] = true;
        $this->additional_fields['maxvotes'] = true;
        $this->additional_fields['countinfo'] = true;
        $this->additional_fields['anonymousinfo'] = true;
        $this->additional_fields['endinfo'] = true;
        parent::__construct($id);
    }

    public function getUsers() {
        if ($this->anonymous) {
            return $this->anonymous_users;
        } else {
            $result = array();
            foreach ($this->answers as $answer) {
                foreach ($answer->users as $user) {
                    $result[] = $user;
                }
            }
            return SimpleORMapCollection::createFromArray($result);
        }
    }

    public function getCount() {
        foreach ($this->answers as $answer) {
            $result += $answer->count;
        }
        return $result;
    }

    public function getCountinfo() {
        switch ($this->count) {
            case 1:
                if ($this->userVoted()) {
                    return _('Sie waren bisher der/die einzige TeilnehmerIn.');
                }
                return _('Es hat bisher') . " " . $this->count . " " . _('Person teilgenommen.');
            default:
                return _('Es haben bisher') . " " . $this->count . " " . _('Personen teilgenommen.');
        }
    }

    public function getAnonymousinfo() {
        if ($this->isRunning()) {
            if ($this->anonymous) {
                return _('Die Teilnahme war anonym.');
            }
            return _('Die Teilnahme war nicht anonym.');
        }
        if ($this->anonymous) {
            return _('Die Teilnahme ist anonym.');
        }
        return _('Die Teilnahme ist nicht anonym.');
    }

    public function getEndinfo() {
        return _('Der Endzeitpunkt dieser Umfrage steht noch nicht fest.');
    }

    public function userVoted() {
        return $this->users->findOneBy('user_id', $GLOBALS['user']->id);
    }

    public function getMaxvotes() {
        return max($this->answers->pluck("count"));
    }

    public function isRunning() {
        return $this->hasStarted() && !$this->hasStopped();
    }

    public function hasStarted() {
        return $this->startdate < time();
    }

    public function hasStopped() {

        // if we have a direct stop time check if it is reached
        if ($this->stopdate) {
            return $this->stopdate < time();
        }

        // if we have a timespan check if it has already run out
        if ($this->timespan) {
            return $this->startdate + $this->timespan < time();
        }

        // otherwise the vote is unlimited so it will never run out
        return false;
    }

    public function insertVote($answers, $user_id) {
        
        if (!$answers) {
            throw new Exception(_('Sie haben keine Antwort ausgewählt.'));
        }
        $answers = $this->checkValidAnswers($answers);
        if (!count($answers)) {
            throw new Exception(_('Sie haben keine Antwort ausgewählt. (Keine Validen antoweren)'));
        }
        if (!$this->multiplechoice && count($answers) != 1) {
            throw new Exception(_('Sie haben zu viele Antwort ausgewählt.'));
        }
        
        /*
         * If we have a changerequest here make sure u delete all the given answers
         */
        if ($this->changeable) {
            foreach ($this->answers as $answer) {
                $answer->deleteUser($user_id);
            }
        }

        /*
         *  If the vote is anonymous save that the user has voted in the vote_user table
         * otherwise save the vote in the answer table
         */
        if ($this->anonymous) {
            VoteUser::create(array(
                'vote_id' => $this->id,
                'user_id' => $GLOBALS['user']->id,
                'votedate' => time()
            ));
        } else {
            foreach ($answers as $answer) {
                VoteAnswerUser::create(array(
                    'answer_id' => $answer->id,
                    'user_id' => $GLOBALS['user']->id,
                    'votedate' => time()
                ));
            }
        }

        // in every case increase the answer counter
        foreach ($answers as $answer) {
            $answer->counter++;
            $answer->store();
        }
    }

    private function checkValidAnswers($answers) {
        foreach ($answers as $answer) {
            $result[] = $this->answers->findOneBy('position', $answer);
        }
        return $result;
    }

    /**
     * Checks if the user may see the result
     */
    public function showResult() {
        if (Request::submitted('change') && $this->changeable) {
            return false;
        }
        return $this->userVoted() || Request::submitted('preview');
    }
    
}
