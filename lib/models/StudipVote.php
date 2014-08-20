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
class StudipVote extends SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'vote';
        $config['has_many']['answers'] = array(
            'class_name' => 'VoteAnswer'
        );
        $config['has_many']['anonymous_users'] = array(
            'class_name' => 'VoteUser'
        );
        $config['belongs_to']['author'] = array(
            'class_name' => 'User',
            'foreign_key' => 'author_id'
        );
        $config['additional_fields']['users'] = true;
        $config['additional_fields']['count'] = true;
        $config['additional_fields']['maxvotes'] = true;
        $config['additional_fields']['countinfo'] = true;
        $config['additional_fields']['anonymousinfo'] = true;
        $config['additional_fields']['endinfo'] = true;

        parent::configure($config);
    }

    public function getUsers()
    {
        if ($this->anonymous) {
            return $this->anonymous_users;
        }
        
        $result = array();
        foreach ($this->answers as $answer) {
            foreach ($answer->users as $user) {
                $result[$user->user_id] = $user;
            }
        }
        return SimpleCollection::createFromArray(array_values($result));
    }

    public function getCount()
    {
        return count($this->getUsers());
    }

    public function getCountinfo()
    {
        if ($this->count === 1 && $this->userVoted()) {
            return _('Sie waren bisher der/die einzige TeilnehmerIn.');
        }

        $template = $this->count === 1
                  ? _('Es hat bisher %u Person teilgenommen')
                  : _('Es haben bisher %u Personen teilgenommen.');
        return sprintf($template, $this->count);
    }

    public function getAnonymousinfo()
    {
        if ($this->isRunning()) {
            return $this->anonymous
                ? _('Die Teilnahme ist anonym.')
                : _('Die Teilnahme ist nicht anonym.');
        }

        return $this->anonymous
            ? _('Die Teilnahme war anonym.')
            : _('Die Teilnahme war nicht anonym.');
    }

    public function getEndinfo()
    {
        $stopdate = $this->stopdate ?: ($this->timespan ? $this->startdate + $this->timespan : 0);

        if ($stopdate) {
            if ($stopdate < time()) {
                $format = _('Die Umfrage wurde beendet am %x um %R Uhr.');
            } else if ($this->userVoted()) {
                if ($this->changeable) {
                    $format = _('Sie können Ihre Antwort ändern bis zum %x um %R Uhr.');
                } else {
                    $format = _('Die Umfrage wird voraussichtlich beendet am %x um %R Uhr.');
                }
            } else {
                $format = _('Sie können abstimmen bis zum %x um %R Uhr.');
            }
            return strftime($format, $stopdate);
        }
        return _('Der Endzeitpunkt dieser Umfrage steht noch nicht fest.');
    }

    public function userVoted($user_id = null)
    {
        $user_id = $user_id ?: $GLOBALS['user']->id;
        if ($this->anonymous) {
            $query = "SELECT 1 FROM vote_user WHERE user_id = ? AND vote_id = ?";
        } else {
            $query = "SELECT 1
                      FROM voteanswers_user AS a
                      JOIN voteanswers AS b USING(answer_id)
                      WHERE a.user_id = ? AND b.vote_id = ?";
        }
        return DBManager::get()->fetchColumn($query, array($user_id, $this->id));
    }

    public function getMaxvotes()
    {
        return max($this->answers->pluck('count'));
    }

    public function isRunning()
    {
        return $this->hasStarted() && !$this->hasStopped();
    }

    public function hasStarted()
    {
        return $this->startdate < time();
    }

    public function hasStopped()
    {
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

    public function insertVote($answers, $user_id)
    {

        if (!$answers) {
            throw new Exception(_('Sie haben keine Antwort ausgewählt.'));
        }

        $answers = $this->checkValidAnswers($answers);
        if (!count($answers)) {
            throw new Exception(_('Sie haben keine gültige Antwort ausgewählt.'));
        }

        if (!$this->multiplechoice && count($answers) != 1) {
            throw new Exception(_('Sie haben zu viele Antworten ausgewählt.'));
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

    private function checkValidAnswers($answers)
    {
        $result = array();
        foreach ($answers as $answer) {
            $result[] = $this->answers->findOneBy('position', $answer);
        }
        return $result;
    }

    /**
     * Checks if the user may see the result
     */
    public function showResult()
    {
        if (Request::submitted('change') && $this->changeable) {
            return false;
        }
        return $this->userVoted() || Request::submitted('preview');
    }
}
