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


/**
 * The mainclass for an evaluation for the Stud.IP-project.
 *
 * @author  Alexander Willner <mail@AlexanderWillner.de>
 *
 * @copyright   2004 Stud.IP-Project
 * @access      public
 * @package     evaluation
 *
 */
class Evaluation {
# Define all required variables ============================================= #
  /**
   * The unique ID of the evaluation
   * @access   private
   * @var      integer $evalID
   */
  var $evalID;
   
  /**
   * The unique userID of the author of the evaluation
   * @access   private
   * @var      string  $authorID
   */
  var $authorID;

  /**
   * The unique range IDs of the evaluation
   * @access   private
   * @var      array   $rangeIDs
   */
  var $rangeIDs;

  /**
   * The group objects of the evaluation
   * @access   private
   * @var      array   $groupObjects
   */
  var $groupObjects;

  /**
   * The title of the evaluation.
   * @access   private
   * @var      string   $title
   */
  var $title;

  /**
   * An text for description.
   * @access   private
   * @var      string   $text
   */
  var $text;

  /**
   * Startdate
   * @access   private
   * @var      integer $startdate
   */
  var $startdate;

  /**
   * Stopdate
   * @access   private
   * @var      integer $stopdate
   */
  var $stopdate;

  /**
   * Timespan
   * @access   private
   * @var      integer $timespan
   */
  var $timespan;

  /**
   * Time of creation. Is set automatically.
   * @access   private
   * @var      integer $mkdate
   */
  var $mkdate;

  /**
   * Time of last change. Is set automatically.
   * @access   private
   * @var      integer $chdate
   */
  var $chdate;

  /**
   * Defines how to show the result.
   * @access   private
   * @var      integer    $resultvisibility
   */
  var $resultvisibility;

  /**
   * Defines whether a user had already used this evaluation
   * @access   private
   * @var      boolean   $inUse
   */
  var $inUse;

  /**
   * Defines whether the evaluation is visible
   * @access   private
   * @var      boolean   $visible
   */
  var $visible;

 /**
  * Holds the EvaluationDB object
  * @access   public
  * @var      object EvaluationDB $evalDB
  */
  var $evalDB;
# ============================================================ end: variables #


# Define constructor and destructor ========================================= #
  /**
    * Constructor
    * @access   public
    * @param    string   $evalID   The ID of an existing evaluation
    */
   function Evaluation ($evalID = "") {

     /* Set default values ------------------------------------------------ */
      srand ((double) microtime () * 1000000);

      $this->evalID           = $evalID;
      $this->authorID         = "";
      $this->rangeIDs         = array ();
      $this->groupObjects     = array ();
      $this->title            = "";
      $this->text             = "";
      $this->startdate        = NULL;
      $this->stopdate         = NULL;
      $this->timespan         = NULL;
      $this->mkdate           = time ();
      $this->chdate           = time ();
      $this->resultvisibility = EVAL_RESVIS_EVER;
      $this->inUse            = NO;
      $this->visible          = NO;
      $this->evalDB           = new EvaluationDB;
      /* ------------------------------------------------------------------- */

      /* Connect to database ----------------------------------------------- */
      $this->db = new EvaluationDB ();
      /* ------------------------------------------------------------------- */

      /* Load an evaluation or create a new one ---------------------------- */
      if ($evalID) {
    $this->db->loadEvaluation ($this);
      }
      /* ------------------------------------------------------------------- */
   }
# =========================================== end: constructor and destructor #


# Define public functions =================================================== #
   /**
    * Sets the title
    * @access  public
    * @param   string  $title  The title.
    * @throws  error
    */
   function setTitle ($title) {
      if (empty ($title))
    trigger_error (_("Der Titel darf nicht leer sein."), E_USER_ERROR);

      $this->title = $title;
   }

   /**
    * Gets the title
    * @access  public
    * @return  string  The title of a vote
    */
   function getTitle () {
      return $this->title;
   }
   
   /**
    * Gets the evalID
    * @access  public
    * @return  string  The evalID
    */
   function getEvalID () {
      return $this->evalID;
   }

   /**
    * Gets all the groups in the evaluation
    * @access  public
    * @return  array  An array full of groupObjects
    */
   function getChildren () {
     return $this->groupObjects;
   }
# ===================================================== end: public functions #


# Define private functions ================================================== #

# ==================================================== end: private functions #

}


# Include all required files ================================================ #
/**
 * Common configuration
 */
require_once("lib/evaluation/evaluation.config.php");
# ====================================================== end: including files #


# Define constants ========================================================== #
/**
 * @const EVAL_RESVIS_EVER Beschreibung
 * @access public
 */
define ("EVAL_RESVIS_EVER", "ever");

/**
  * @const EVAL_RESIVS_DELIVERY Beschreibung
 * @access public
 */
define ("EVAL_RESIVS_DELIVERY", "delivery");

/**
 * @const EVAL_RESIVS_END Beschreibung
 * @access public
 */
define ("EVAL_RESIVS_END", "end");

/**
 * @const EVAL_RESIVS_NEVER Beschreibung
 * @access public
 */
define ("EVAL_RESIVS_NEVER", "never");
# ===================================================== end: define constants #

?>
