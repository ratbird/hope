<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
 * Beschreibung
 *
 * @author      Alexander Willner <mail@AlexanderWillner.de>
 * @copyright   2004 Stud.IP-Project
 * @access      public
 * @package     evaluation
 * @modulegroup evaluation_modules
 *
 */

// +--------------------------------------------------------------------------+
// This file is part of Stud.IP
// Copyright (C) 2001-2004 Stud.IP
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

# Include all required files ================================================ #
require_once("lib/evaluation/evaluation.config.php");
require_once(EVAL_FILE_OBJECTDB);
# ====================================================== end: including files #

# Define all required constants ============================================= #
/**
 * @const INSTANCEOF_EVALANSWERDB Instance of an evaluationanswerDB object
 * @access public
 */
define ("INSTANCEOF_EVALANSWERDB", "EvalANSWERDB");
# =========================================================================== #


class EvaluationAnswerDB extends EvaluationObjectDB {


# Define all required variables ============================================= #

# ============================================================ end: variables #

# Define constructor and destructor ========================================= #
  /**
   * Constructor
   * @access   public
   */
  function EvaluationAnswerDB () {

    /* Set default values ------------------------------------------------ */
    parent::EvaluationObjectDB ();
    $this->instanceof = INSTANCEOF_EVALANSWERDB;
    /* ------------------------------------------------------------------- */
  }
# =========================================== end: constructor and destructor #


# Define public functions =================================================== #
  /**
   * Loads answers of a group from the DB
   * @access  public
   * @param   EvaluationAnswer   &&$answerObject   The answer object
   */
  function load (&$answerObject) {
    $db = DBManager::get();

    /* load answer --------------------------------------------------------- */
    $query =
      "SELECT".
      " * ".
      "FROM".
      " evalanswer ".
      "WHERE".
      " evalanswer_id = '".$answerObject->getObjectID ()."'".
      "ORDER BY".
      " position ";
    $result = $db->query($query);

    if (($row = $result->fetch()) === FALSE)
      return $this->throwError (2,
            _("Keine Antwort mit dieser ID gefunden."));

    $answerObject->setObjectID ($row['evalanswer_id']);
    $answerObject->setParentID ($row['parent_id']);
    $answerObject->setPosition ($row['position']);
    $answerObject->setText     ($row['text']);
    $answerObject->setValue    ($row['value']);
    $answerObject->setRows     ($row['rows']);
    $answerObject->setResidual ($row['residual']);
    /* --------------------------------------------------------------------- */

  } //loaded


  /**
   * Loads the votes from the users for this answer
   * @access   public
   * @param    EvaluationAnswer   &$answerObject   The answer object
   */
   function loadVotes (&$answerObject) {
    $db = DBManager::get();

      /* load users -------------------------------------------------------- */
      $sql =
         "SELECT".
         " user_id ".
         "FROM".
         " evalanswer_user ".
         "WHERE".
         " evalanswer_id = '".$answerObject->getObjectID ()."'";
      $result = $db->query($sql);

      foreach ($result as $row) {
         $answerObject->addUserID ($row['user_id'], NO);
      }
   }
   /* ----------------------------------------------------------- end: users */

  /**
   * Writes answers into the DB
   * @access    public
   * @param     EvaluationAnswer   &$answerObject       The answerobject
   * @throws    error
   */
  function save (&$answerObject) {
    $db = DBManager::get();

    if (EVAL_DEBUGLEVEL >= 1)
      echo "DB: Speichere Antwortobjekt<br>\n";
    /* save answers -------------------------------------------------------- */
    if ($this->exists ($answerObject->getObjectID ())) {
      $sql =
   "UPDATE".
   " evalanswer ".
   "SET".
   " parent_id       = '".$answerObject->getParentID()."',".
   " position        = '".$answerObject->getPosition()."',".
   " text            = '".$answerObject->getText(YES)."',".
   " value           = '".$answerObject->getValue()."',".
   " rows            = '".$answerObject->getRows()."', ".
   " residual        = '".$answerObject->isResidual()."' ".
   "WHERE".
   " evalanswer_id   = '".$answerObject->getObjectID()."'";
    } else {
      $sql =
   "INSERT INTO".
   " evalanswer ".
   "SET".
   " evalanswer_id   = '".$answerObject->getObjectID()."',".
   " parent_id       = '".$answerObject->getParentID()."',".
   " position        = '".$answerObject->getPosition()."',".
   " text            = '".$answerObject->getText(YES)."',".
   " value           = '".$answerObject->getValue()."',".
   " rows            = '".$answerObject->getRows()."',".
   " residual        = '".$answerObject->isResidual()."' ";
#   " counter         = '".$answerObject->getCounter()."'";
    }
    $db->exec($sql);
    /* ----------------------------------------------------- end: answersave */

    /* connect answer to users --------------------------------------------- */
    while ($userID = $answerObject->getNextUserID ()) {
      $sql =
          "INSERT INTO".
          " evalanswer_user ".
          "SET".
          " evalanswer_id  = '".$answerObject->getObjectID ()."',".
          " user_id = '".$userID."'";
       $db->exec($sql);
    }
    /* ----------------------------------------------------- end: connecting */

    /* connect user with evaluation ---------------------------------------- */
    # Disabled this because of performance problems. Do it with
    # $eval->connectWithUser ($evalID, $userID)
    #$answerID = $answerObject->getObjectID ();
    #$userID = $answerObject->getCurrentUser ();
    #if (!empty ($userID)) {
    #  $evalID = EvaluationObjectDB::getEvalID ($answerID);
    #  EvaluationDB::connectWithUser ($evalID, $userID);
    #}
    /* ----------------------------------------------------- end: connecting */

  } // saved

  /**
   * Deletes all votes from the users for this answers
   * @access   public
   * @param    EvaluationAnswer   &$answerObject   The answer object
   */
  function resetVotes (&$answerObject) {
    $db = DBManager::get();

   /* delete userconnects ------------------------------------------------- */
    $sql =
      "DELETE FROM".
      " evalanswer_user ".
      "WHERE".
      " evalanswer_id = '".$answerObject->getObjectID ()."'";
    $db->exec($sql);
    /* ------------------------------------------------------- end: deleting */
  }

  /**
   * Deletes a answer
   * @access public
   * @param  EvaluationAnswer   &$answerObject   The answer to delete
   * @throws  error
   */
  function delete (&$answerObject) {
    $db = DBManager::get();

    /* delete answer ----------------------------------------------------- */
    $sql =
      "DELETE FROM".
      " evalanswer ".
      "WHERE".
      " evalanswer_id = '".$answerObject->getObjectID ()."'";
    $db->exec($sql);
    /* ------------------------------------------------------- end: deleting */

    $this->resetVotes ($answerObject);
  } // deleted


  /**
   * Checks if answer with this ID exists
   * @access  public
   * @param   string   $answerID   The answerID
   * @return  bool     YES if exists
   */
  function exists ($answerID) {
    $db = DBManager::get();

    $sql =
      "SELECT".
      " 1 ".
      "FROM".
      " evalanswer ".
      "WHERE".
      " evalanswer_id = '".$answerID."'";
    $result = $db->query($sql);

    return $result->rowCount() > 0;
  }


  /**
   * Adds the children to a parent object
   * @access  public
   * @param   EvaluationObject  &$parentObject  The parent object
   */
  function addChildren (&$parentObject) {
    $db = DBManager::get();

    $sql =
      "SELECT".
      " evalanswer_id ".
      "FROM".
      " evalanswer ".
      "WHERE".
      " parent_id = '".$parentObject->getObjectID ()."' ".
      "ORDER BY".
      " position";
    $result = $db->query($sql);

    $loadChildren = $parentObject->loadChildren == EVAL_LOAD_ALL_CHILDREN
         ? EVAL_LOAD_ALL_CHILDREN
         : EVAL_LOAD_NO_CHILDREN;

    foreach ($result as $row) {
      $parentObject->addChild (new EvaluationAnswer
                ($row['evalanswer_id'],
                $parentObject, $loadChildren));
    }
  }

  /**
   * Returns the type of an objectID
   * @access public
   * @param  string  $objectID  The objectID
   * @return string  INSTANCEOF_x, else NO
   */
  function getType ($objectID) {
    if ($this->exists ($objectID)) {
      return INSTANCEOF_EVALANSWER;
    } else {
      return NO;
    }
  }

  /**
   * Returns the id from the parent object
   * @access public
   * @param  string  $objectID  The object id
   * @return string  The id from the parent object
   */
  function getParentID ($objectID) {
    $db = DBManager::get();

    $sql =
      "SELECT".
      " parent_id ".
      "FROM".
      " evalanswer ".
      "WHERE".
      " evalanswer_id = '".$objectID."'";
    $result = $db->query($sql);
    $row = $result->fetch();

    return $row['parent_id'];
  }

   /**
    * Give all textanswers for a user and question for the export
    * @access  public
    * @param   string   $questionID   The question id
    * @param   string   $userID       The user id
    */
   function getUserAnwerIDs ($questionID, $userID) {
    $db = DBManager::get();

      $answer_ids = array ();

      /* ask database ------------------------------------------------------- */
      $sql =
            "SELECT".
            " a.evalanswer_id as ttt ".
            "FROM".
            " evalanswer a, evalanswer_user b ".
            "WHERE".
            " a.parent_id = '".$questionID."'".
            " AND".
            " a.evalanswer_id = b.evalanswer_id";
      if (!empty ($userID)) {
         $sql .=
            " AND".
            " b.user_id = '".$userID."'";

      }

      $result = $db->query($sql);
      /* -------------------------------------------------------- end: asking */

      /* Fill up the array with the result ---------------------------------- */
       foreach ($result as $row) {
         array_push ($answer_ids, $row['ttt']);
       }
      /* ------------------------------------------------------- end: filling */

       return $answer_ids;
  }

  /**
   * Checks whether a user has voted for an answer
   * @access   public
   * @param    string   $answerID   The answer id
   * @param    string   $userID     The user id
   * @return   boolean  YES if user has voted for the answer
   */
  function hasVoted ($answerID, $userID) {
    $db = DBManager::get();

   $sql =
      "SELECT".
      " 1 ".
      "FROM".
      " evalanswer_user ".
      "WHERE".
      " evalanswer_id = '".$answerID."'".
      " AND".
      " user_id = '".$userID."'";
    $result = $db->query($sql);

    return $result->rowCount() > 0;
  }
  
  function getAllAnswers ($question_id, $userID, $only_user_answered = false) {
    $db = DBManager::get();

   $sql =
      "SELECT".
      " evalanswer.*, COUNT(IF(user_id='$userID',1,NULL)) AS has_voted ".
      "FROM".
      " evalanswer LEFT JOIN " .
	  " evalanswer_user USING(evalanswer_id) ".
      "WHERE".
      " parent_id = '".$question_id."'".
      ($only_user_answered ?  " AND user_id = '".$userID."' " : "") .
	  " GROUP BY evalanswer.evalanswer_id ORDER BY position";
    $result = $db->query($sql);
    return $result->fetchAll();
  }
}
?>
