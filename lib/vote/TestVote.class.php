<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
// +--------------------------------------------------------------------------+
// This file is part of Stud.IP
// TestVote.class.php
//
// Copyright (c) 2003 Alexander Willner <mail@AlexanderWillner.de>
// +--------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +--------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +--------------------------------------------------------------------------+


/* Include all required files ---------------------------------------------- */
#require_once ("Vote.class.php");
/* ------------------------------------------------------------------------- */


/**
 * TestVote.class.php
 *
 * This extends a vote in order to use right and wrong answers
 *
 * @author      Alexander Willner <mail@alexanderwillner.de>,
 *              Michael Cohrs <michael A7 cohrs D07 de>
 * @access      public
 * @package     vote
 * @modulegroup vote_modules
 */

class TestVote extends Vote {
   /**
    * Whether the correctness shall be revealed to the user right after voting
    * @access   private
    * @var      bool $co_visibility
    */
   var $co_visibility;

   /**
    * Constructor
    *
    * @param    String $oldTestID The ID of an old test
    * @access   public
    */
   function TestVote ($oldTestID = "") {
      $this->co_visibility = NO;
      parent::Vote ($oldTestID);
      $this->instanceof = INSTANCEOF_TEST;
   }

   /**
    * Sets the answers
    * @access  public
    * @param   $answerArray array The answers. E.g. : 0 => array (
    *          "answer_id" => "35b9dfed54c3740edcf96ece787994f3", 
    *          "text" => "Monday")
    * @throws  error
    */
   function setAnswers ($answerArray) {
      for ($i = 0, $ok = NO; $i < count($answerArray); $i++) {
     if ( $answerArray[$i]['correct'] ) {
        $ok = YES;
        break;
     }
      }

      if ($ok == NO)
     return $this->throwError (1, _("Der Test besitzt keine als richtig deklarierte Antwort."));

      parent::setAnswers ($answerArray);
   }

   /**
    * Sets the way how to show the correct answers
    * @access  public
    * @param   bool  $co_visibility  TRUE if the correctness of answers shall 
    *                                be revealed to the user right after voting
    */
   function setCo_Visibility ($co_visibility) {
       $this->co_visibility = ($co_visibility == NO) ? NO : YES;
   }

   /**
    * Gets the way how to show the correct answers
    * @access  public
    * @returns bool   true if the correctness of answers shall 
    *                 be revealed to the user right after voting
    */
   function getCo_Visibility () {
      return ($this->co_visibility == YES) ? YES : NO;
   }

   /**
    * Gets number of correct answers
    * @access  public
    * @param   array  $userData  the answers the user guessed
    * @returns int    IF param given: the number of answers the user guessed
    *                 right
    *                 ELSE: the total number of correct answers of the test
    */
   function getNumberOfCorrectAnswers ($userData = NULL) {
      $result = 0;
      $answers = $this->getAnswers();

      for ($i = 0; $i < count($answers); $i++) {
     if (is_array ($userData)) {
        if ($answers[$i]["correct"] && in_array($i, $userData))
           $result++;
     } else {
        if ($answers[$i]["correct"])
           $result++;
     }
      }

      return $result;
   }

   /**
    * Loads an old vote (internal!)
    * @access  private
    * @throws  error
    */
   function readVote () {
      $result = $this->voteDB->getVote ($this->objectID);
      $this->setCo_Visibility ($result["co_visibility"]);
      parent::readVote();
   }

   /**
    * Writes the test into the database (!)
    * @access  public
    * @throws  error
    */
   function executeWrite () {
      $this->checkConsistency ();
      if ($this->isError ()) return;

      $this->voteDB->writeVote (
                $this->getVoteID (),
                $this->getAuthorID (),
                $this->getRangeID (),
                $this->getTitle (),
                $this->getQuestion (),
                $this->getState (),
                $this->getStartdate (),
                $this->getStopdate (),
                $this->getTimespan (),
                $this->getCreationdate (),
                $this->getChangedate (),
                $this->getResultvisibility (),
                $this->getNamesvisibility (),
                $this->isMultiplechoice (),
                $this->isAnonymous (),
                $this->getAnswers (),
                $this->isChangeable (),
                $this->getCo_Visibility(),
                $this->x_instanceof ()
                );
      if ($this->voteDB->isError ())
      $this->throwErrorFromClass ($this->voteDB);
   }
   
   /**
    * Checks the consostency of the test (internal!)
    * @access  private
    * @throw   error
    */
   function checkConsistency () {
      /* Check the normal variables ---------------------------------------- */
      if ($this->co_visibility === NULL)
     $this->throwError (1, _("Test hat keine Ergebnissichtbarkeit"));
      /* ------------------------------------------------------------------- */
   }
}
