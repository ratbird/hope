<?php

/**
 * VoteAnswer.php
 * model class for table VoteAnswer
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
 * @since       3.0
 */
class VoteAnswer extends SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'voteanswers';
        $config['has_many']['users'] = array(
            'class_name' => 'VoteAnswerUser'
        );
        $config['belongs_to']['vote'] = array(
            'class_name' => 'StudipVote',
            'foreign_key' => 'vote_id'
        );
        $config['additional_fields']['count'] = true;
        $config['additional_fields']['prepare'] = true;
        $config['additional_fields']['usernames'] = true;
        parent::configure($config);
    }

    /**
     * Returns the number of votes for an answer
     *
     * @return int Number of votes
     */
    public function getCount() {

        return $this->counter;
    }

    /**
     * Deletes a uservote if he voted for this answer
     */
    public function deleteUser($user_id) {

        // Search the user to check if we have to decrease the counter
        if ($user = $this->users->findOneBy('user_id', $user_id)) {
            $user->delete();
            $this->counter--;
            $this->store();
        }
    }

    /**
     * Fetches all user that voted for this anser
     *
     * @return array alls users
     */
    public function getUsernames() {
        $result = array();
        foreach ($this->users as $user) {
            $result[] = $user->user->getFullName();
        }
        return $result;
    }

}
