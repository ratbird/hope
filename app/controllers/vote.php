<?php

# Lifter010: TODO
/**
 * vote.php - Votecontroller controller
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */
require_once 'app/controllers/authenticated_controller.php';

class VoteController extends AuthenticatedController {

    public function display_action($range_id) {


        /*
         * Insert vote
         */
        if ($vote = Request::get('vote')) {
            $vote = new StudipVote($vote);
            if ($vote && $vote->isRunning() && (!$vote->userVoted() || $vote->changeable)) {
                try {
                    $vote->insertVote(Request::getArray('vote_answers'), $GLOBALS['user']->id);
                } catch (Exception $exc) {
                    $GLOBALS['vote_message'][$vote->id] = MessageBox::error($exc->getMessage());
                }
            }
        }

        $votes = StudipVote::findByRange_id($range_id);
        $this->votes = SimpleORMapCollection::createFromArray($votes)->orderBy('mkdate desc');
    }

    /**
     * Determines if a vote should show its result
     * 
     * @param StudipVote $vote the vote to check
     * @return boolean true if result should be shown
     */
    public function showResult($vote) {
        if (Request::submitted('change') && $vote->changeable) {
            return false;
        }
        return $vote->userVoted() || Request::submitted('preview');
    }

}
