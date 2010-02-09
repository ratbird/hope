<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
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
require_once(EVAL_FILE_GROUPDB);
# ====================================================== end: including files #


# Define all required constants ============================================= #
/**
 * @const INSTANCEOF_EVALDB Is instance of an evaluationDB object
 * @access public
 */
define ("INSTANCEOF_EVALDB", "EvalDB");

/**
 * @const EVAL_STATE_NEW Beschreibung
 * @access public
 */
define ("EVAL_STATE_NEW", "new");

/**
 * @const EVAL_STATE_ACTIVE Beschreibung
 * @access public
 */
define ("EVAL_STATE_ACTIVE", "active");

/**
 * @const EVAL_STATE_STOPPED Beschreibung
 * @access public
 */
define ("EVAL_STATE_STOPPED", "stopped");
# =========================================================================== #


/**
 * Databaseclass for all evaluations
 *
 * @author  Alexander Willner <mail@AlexanderWillner.de>
 *
 * @copyright   2004 Stud.IP-Project
 * @access      public
 * @package     evaluation
 *
 */
class EvaluationDB extends EvaluationObjectDB {

# Define all required variables ============================================= #

# ============================================================ end: variables #


# Define constructor and destructor ========================================= #
  /**
   * Constructor
   * @access   public
   */
  function EvaluationDB () {
    /* Set default values -------------------------------------------------- */
    parent::EvaluationObjectDB ();
    $this->instanceof = INSTANCEOF_EVALDBOBJECT;
    /* --------------------------------------------------------------------- */
  }
# =========================================== end: constructor and destructor #


# Define public functions =================================================== #
  /**
   * Loads an evaluation from DB into an object
   *
   * @access public
   * @param  object EvaluationObject &$evalObject  The evaluation to load
   * @throws error
   */
  function load (&$evalObject) {
    $db = DBManager::get();

    /* load evaluation basics ---------------------------------------------- */
    $sql =
      "SELECT".
      " * ".
      "FROM".
      " eval ".
      "WHERE".
      " eval_id = '".$evalObject->getObjectID ()."'";

    $result = $db->query($sql);

    if (($row = $result->fetch()) === FALSE)
      return $this->throwError (1,
            _("Keine Evaluation mit dieser ID gefunden."));

    $evalObject->setAuthorID     ($row['author_id']);
    $evalObject->setTitle        ($row['title']);
    $evalObject->setText         ($row['text']);
    $evalObject->setStartdate    ($row['startdate']);
    $evalObject->setStopdate     ($row['stopdate']);
    $evalObject->setTimespan     ($row['timespan']);
    $evalObject->setCreationdate ($row['mkdate']);
    $evalObject->setChangedate   ($row['chdate']);
    $evalObject->setAnonymous    ($row['anonymous']);
    $evalObject->setVisible      ($row['visible']);
    $evalObject->setShared       ($row['shared']);
    /* --------------------------------------------------------- end: values */


    /* load ranges --------------------------------------------------------- */
    $sql =
      "SELECT".
      " range_id ".
      "FROM".
      " eval_range ".
      "WHERE".
      " eval_id = '".$evalObject->getObjectID ()."'";

    $result = $db->query($sql);

    foreach ($result as $row) {
      $evalObject->addRangeID ($row['range_id']);
    }
    /* --------------------------------------------------------- end: ranges */


    /* load groups --------------------------------------------------------- */
    if ($evalObject->loadChildren != EVAL_LOAD_NO_CHILDREN) {
        EvaluationGroupDB::addChildren ($evalObject);
     }
    /* ---------------------------------------------------------- end: group */

  } // loaded


  /**
   * Saves an evaluation
   * @access public
   * @param  object   Evaluation  &$evalObject  The evaluation to save
   * @throws  error
   */
  function save (&$evalObject) {
    $db = DBManager::get();

    if (EVAL_DEBUGLEVEL >= 1)
      echo "DB: Speichere Evaluationsobjekt<br>\n";

    $startdate = $evalObject->getStartdate() == NULL
   ? "NULL"
   : $evalObject->getStartdate();

    $stopdate = $evalObject->getStopdate() == NULL
   ? "NULL"
   : $evalObject->getStopdate();

    $timespan = $evalObject->getTimespan() == NULL
   ? "NULL"
   : $evalObject->getTimespan();

    /* save evaluation ----------------------------------------------------- */
    if ($this->exists ($evalObject->getObjectID ())) {
      $sql =
   "UPDATE".
   " eval ".
   "SET".
   " title     = '".$evalObject->getTitle (YES)."',".
   " text      = '".$evalObject->getText (YES)."',".
   " startdate =  ".$startdate.",".
   " stopdate  =  ".$stopdate.",".
   " timespan  =  ".$timespan.",".
   " mkdate    = '".$evalObject->getCreationdate ()."',".
   " chdate    = '".$evalObject->getChangedate ()."',".
   " anonymous = '".$evalObject->isAnonymous ()."',".
   " visible   = '".$evalObject->isVisible ()."',".
   " shared    = '".$evalObject->isShared ()."'".
   "WHERE".
   " eval_id   = '".$evalObject->getObjectID ()."'";
    } else {
      $sql =
   "INSERT INTO".
   " eval ".
   "SET".
   " eval_id   = '".$evalObject->getObjectID ()."',".
   " author_id = '".$evalObject->getAuthorID ()."',".
   " title     = '".$evalObject->getTitle (YES)."',".
   " text      = '".$evalObject->getText (YES)."',".
   " startdate =  ".$startdate.",".
   " stopdate  =  ".$stopdate.",".
   " timespan  =  ".$timespan.",".
   " mkdate    = '".$evalObject->getCreationdate ()."',".
   " chdate    = '".$evalObject->getChangedate ()."',".
   " anonymous = '".$evalObject->isAnonymous ()."',".
   " visible   = '".$evalObject->isVisible ()."',".
   " shared    = '".$evalObject->isShared ()."'";
    }

    $db->exec($sql);
    /* ------------------------------------------------------- end: evalsave */

    /* connect to ranges --------------------------------------------------- */
      $sql =
         "DELETE FROM".
         " eval_range ".
         "WHERE".
         " eval_id  = '".$evalObject->getObjectID ()."'";
      $db->exec($sql);

      while ($rangeID = $evalObject->getNextRangeID ()) {
         $sql =
            "INSERT INTO".
            " eval_range ".
            "SET".
            " eval_id  = '".$evalObject->getObjectID ()."',".
            " range_id = '".$rangeID."'";
         $db->exec($sql);
      }
    /* ----------------------------------------------------- end: connecting */
  } //...saved


  /**
   * Deletes an evaluation
   * @access public
   * @param  object   Evaluation  &$evalObject  The evaluation to delete
   * @throws  error
   */
  function delete (&$evalObject) {
    $db = DBManager::get();

    /* delete evaluation --------------------------------------------------- */
    $sql =
      "DELETE FROM eval WHERE eval_id = '".$evalObject->getObjectID ()."'";
    $db->exec($sql);
    /* ------------------------------------------------------- end: deleting */

    /* delete rangeconnects ------------------------------------------------ */
    $sql =
      "DELETE FROM".
      " eval_range ".
      "WHERE".
      " eval_id = '".$evalObject->getObjectID ()."'";
    $db->exec($sql);
    /* ------------------------------------------------------- end: deleting */

    /* delete userconnects ------------------------------------------------- */
    $sql =
      "DELETE FROM".
      " eval_user ".
      "WHERE".
      " eval_id = '".$evalObject->getObjectID ()."'";
    $db->exec($sql);
    /* ------------------------------------------------------- end: deleting */

  } // deleted




  /**
   * Checks if evaluation with this ID exists
   * @access  public
   * @param   string   $evalID   The evalID
   * @return  bool     YES if exists
   */
  function exists ($evalID) {
    $db = DBManager::get();

    $sql =
      "SELECT".
      " 1 ".
      "FROM".
      " eval ".
      "WHERE".
      " eval_id = '".$evalID."'";
    $result = $db->query($sql);

    return $result->rowCount() > 0;
  }

  /**
   * Checks if someone used the evaluation
   * @access  public
   * @param   string   $evalID   The eval id
   * @param   string   $userID   The user id
   * @return  bool     YES if evaluation was used
   */
  function hasVoted ($evalID, $userID = "") {
    $db = DBManager::get();

    /* ask database ------------------------------------------------------- */
    $sql =
      "SELECT".
      " 1 ".
      "FROM".
      " eval_user ".
      "WHERE".
      " eval_id = '".$evalID."'";
    if (!empty ($userID))
      $sql .= " AND user_id = '".$userID."'";

    $result = $db->query($sql);
    /* --------------------------------------------------------- end: asking */

    return $result->rowCount() > 0;
  }

  /**
   * Returns the type of an objectID
   * @access public
   * @param  string  $objectID  The objectID
   * @return string  INSTANCEOF_x, else NO
   */
  function getType ($objectID) {
    if ($this->exists ($objectID)) {
      return INSTANCEOF_EVAL;
    } else {
      $dbObject = new EvaluationGroupDB ();
      return $dbObject->getType ($objectID);
    }
  }
# ===================================================== end: public functions #


# Define private functions ================================================== #

# ==================================================== end: private functions #


# Define static functions =================================================== #
  /**
   * Connect a user with an evaluation
   * @access   public
   * @param    string   $evalID   The evaluation id
   * @param    string   $userID   The user id
   */
  function connectWithUser ($evalID, $userID) {
    $db = DBManager::get();

    if (empty ($userID))
      die ("EvaluationDB::connectWithUser: UserID leer!!");

    $sql =
      "INSERT IGNORE INTO".
      " eval_user ".
      "SET".
      " eval_id  = '".$evalID."',".
      " user_id = '".$userID."'";
    $db->exec($sql);
  }

   /**
    * Removes the connection of an evaluation with a user or all users
    * @access   public
    * @param    string   $evalID   The evaluation id
    * @param    string   $userID   The user id
    */
   function removeUser ($evalID, $userID = "") {
    $db = DBManager::get();

      $sql =
        "DELETE FROM".
         " eval_user ".
         "WHERE".
         " eval_id  = '".$evalID."'";

      if (!empty ($userID)) {
         $sql .= " AND user_id = '".$userID."'";
      }

      $db->exec($sql);
  }

  /**
   * Get number of users who participated in the eval
   * @access public
   * @param  string   $evalID  The eval id
   * @return integer  The number of users
   */
   function getNumberOfVotes ($evalID) {
    $db = DBManager::get();

    $sql =
      "SELECT".
      " count(DISTINCT user_id) ".
      "AS".
      " number ".
      "FROM".
      " eval_user ".
      "WHERE".
      " eval_id = '".$evalID."'";
    /* ------------------------------------------------------------------- */
    $result = $db->query($sql);
    $row = $result->fetch();
    return $row['number'];
  }

   /**
   * Get users who participated in the eval
   * @access public
   * @param  string   $evalID     The eval id
   * @param  array    $answerIDs  The answerIDs to get the pseudonym users
   * @return integer  The number of users
   */
   function getUserVoted ($evalID, $answerIDs = array (), $questionIDs = array ()) {
    $db = DBManager::get();

      $user_ids = array ();

      /* ask database ------------------------------------------------------- */
      if (empty ($answerIDs) && empty ($questionIDs)) {
          $sql =
            "SELECT DISTINCT".
            " user_id ".
            "FROM".
            " eval_user ".
            "WHERE".
            " eval_id = '".$evalID."'";
       } elseif (empty ($questionIDs)) {
         $sql =
            "SELECT DISTINCT".
            " user_id ".
            "FROM".
            " evalanswer_user ".
            "WHERE".
            " evalanswer_id IN ('".join("','", $answerIDs)."')";
       } else {
	    $sql =
            "SELECT DISTINCT".
            " user_id ".
            "FROM".
            " evalanswer INNER JOIN evalanswer_user USING(evalanswer_id) ".
            "WHERE".
            " parent_id IN ('".join("','", $questionIDs)."')";
	   }
	   
      $result = $db->query($sql);
       /* ------------------------------------------------ end: asking database */

       /* Fill up the array with IDs ----------------------------------------- */
       foreach ($result as $row) {
         array_push ($user_ids, $row['user_id']);
       }
       /* ------------------------------------------------------- end: filling */

    return $user_ids;
  }


  /**
   *
   * @access public
   * @param  string   $search_str
   * @return array
   */
   function search_range($search_str) {
      return search_range($search_str, true);
   }
# ===================================================== end: static functions #

}

?>
