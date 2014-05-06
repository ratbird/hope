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
class VoteAnswer extends SimpleORMap {

    public function __construct($id = null) {
        $this->db_table = 'voteanswers';
        $this->has_many['users'] = array(
            'class_name' => 'VoteAnswerUser'
        );
        $this->belongs_to['vote'] = array(
            'class_name' => 'StudipVote',
            'foreign_key' => 'vote_id'
        );
        $this->additional_fields['count'] = true;
        $this->additional_fields['prepare'] = true;
        $this->additional_fields['percent'] = true;
        $this->additional_fields['width'] = true;
        $this->additional_fields['usernames'] = true;
        parent::__construct($id);
    }

    /**
     * Returns the number of votes for an answer
     * 
     * @return int Number of votes
     */
    public function getCount() {
        if (count($this->users)) {
            return count($this->users);
        }
        return $this->counter;
    }
    
    /**
     * Returns the percentage
     * 
     * @return int
     */
    public function getPercent() {
        if (!$this->count) {
            return 0;
        }
        return round($this->count * 100 / $this->vote->count);
    }
    
    /**
     * Returns the width of the answerbar
     * 
     * @return int width
     */
    public function getWidth() {
        if (!$this->vote->maxvotes) {
            return 0;
        }
        return $this->count / $this->vote->maxvotes;
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
