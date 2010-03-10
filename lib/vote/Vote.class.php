<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Include all required files ================================================ #
require_once ("lib/classes/StudipObject.class.php");
require_once ("lib/vote/VoteDB.class.php");
# ====================================================== end: including files #


/**
 * This class is used to implement a vote in Stud.IP
 *
 * @author      Alexander Willner <mail@alexanderwillner.de>
 * @copyright   2003 Stud.IP-Project
 * @access      public
 * @package     vote
 * @modulegroup vote_modules
 *
 */
class Vote extends StudipObject {

# Define all required variables ============================================= #
   /**
    * The title of the vote.
    * @access   private
    * @var      string   $title
    */
   var $title;

   /**
    * The question of the vote.
    * @access   private
    * @var      string   $question
    */
   var $question;

   /**
    * The possible anwers of the vote.
    * @access   private
    * @var      array    $answerArray
    */
   var $answerArray;

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
    * timespan
    * @access   private
    * @var      integer $timespan
    */
   var $timespan;

   /**
    * Time of creation. Is set automatically.
    * @access   private
    * @var      integer $creationdate
    */
   var $creationdate;

   /**
    * Time of last change. Is set automatically.
    * @access   private
    * @var      integer $changedate
    */
   var $changedate;

   /**
    * Defines whether multiple choice is allowed.
    * @access   private
    * @var      boolean $multiplechoice
    */
   var $multiplechoice;

   /**
    * Defines whether the vote is to be linked with a user.
    * @access   private
    * @var      boolean $anonymous
    */
   var $anonymous;

   /**
    * Defines whether it is allowed to change the answers
    * @access   private
    * @var      boolean $changeable
    */
   var $changeable;

   /**
    * Defines whether the vote is visible or not
    * @access   private
    * @var      integer $visible
    */
   var $visible;

   /**
    * Defines how to show the result. See const VOTE_RESULTS_*
    * @access   private
    * @var      integer    $resultvisibility
    */
   var $resultvisibility;

   /**
    * Defines whether a user can see the participants of the vote.
    * @access   private
    * @var      integer    $namesvisibility
    */
   var $namesvisibility;

   /**
    * Defines whether a user had already used this vote
    * @access   private
    * @var      boolean   $isInUse
    */
   var $isInUse;

   /**
    * Holds the DB-state the state of the vote. See constants VOTE_*
    * @access   private
    * @var      string    $state
    */
   var $state;

   /**
    * The unique ID of the vote
    * @access   private
    * @var      integer $objectID
    */
   var $objectID;

   /**
    * The unique range ID of the vote
    * @access   private
    * @var      string $rangeID
    */
   var $rangeID;

   /**
    * The unique userID of the author of the vote
    * @access   private
    * @var      string $authorID
    */
   var $authorID;

   /**
    * Holds the VoteDB object
    * @access   public
    * @var      object VoteDB $voteDB
    */
   var $voteDB;
# ============================================================ end: variables #



# Define constructor and destructor ========================================= #
   /**
    * Constructor
    * @access   public
    * @param    string   $oldVoteID The ID of an existing vote
    * @throws   error
    */
   function Vote ($oldVoteID = "") {

      /* For good OOP: Call constructor and set destruktor ----------------- */
      parent::StudipObject ();
      $this->instanceof = INSTANCEOF_VOTE;
      /* ------------------------------------------------------------------- */

      /* Set default values ------------------------------------------------ */
      $this->setAuthorEmail ("mail@AlexanderWillner.de");
      $this->setAuthorName ("Alexander Willner");


      $this->voteDB           = NULL;
      $this->errorArray       = array ();

      $this->objectID           = "";
      $this->rangeID          = "";
      $this->authorID         = "";
      $this->title            = "";
      $this->question         = "";
      $this->answerArray      = array ();
      $this->startdate        = NULL;
      $this->stopdate         = NULL;
      $this->timespan         = NULL;
      $this->creationdate     = time ();
      $this->changedate       = time ();
      $this->multiplechoice   = NO;
      $this->anonymous        = YES;
      $this->changeable       = NO;
      $this->visible          = NO;
      $this->isInUse          = NO;
      $this->resultvisibility = VOTE_RESULTS_AFTER_VOTE;
      $this->namesvisibility  = NO;
      $this->state            = VOTE_NEW;
      /* ------------------------------------------------------------------- */

      /* Connect to database ----------------------------------------------- */
      $this->voteDB = &new VoteDB ();
      if ($this->voteDB->isError ())
     return $this->throwErrorFromClass ($this->voteDB);
      /* ------------------------------------------------------------------- */

      /* Load an old Vote or create a new one ------------------------------ */
      if (empty ($oldVoteID)) {
         $this->objectID = md5 (uniqid (rand()));
      } else {
         if (! $this->voteDB->isExistant ($oldVoteID))
            return $this->throwError (1, _("Die angegebene ID existiert nicht."));
         $this->objectID = $oldVoteID;
         $this->readVote ();
      }

      $this->voteDB->setVote ($this);
      /* ------------------------------------------------------------------- */
   }


   /**
    * Destructor
    * @access   public
    */
   function finalize () {
      parent::finalize ();
   }
# =========================================== end: constructor and destructor #


# Define public functions =================================================== #
   /**
    * Sets the title of a vote
    * @access  public
    * @param   string  $title  The title of a vote
    * @throws  error
    */
   function setTitle ($title) {
      if (empty ($title))
     return $this->throwError (1, _("Der Titel ist leer."));

      $this->title = $title;
   }

   /**
    * Gets the title of a vote
    * @access  public
    * @return  string  The title of a vote
    */
   function getTitle () {
      return $this->title;
   }

   /**
    * Sets the question of a vote
    * @access  public
    * @param   string  $question  The question of a vote
    * @throws  error
    */
   function setQuestion ($question) {
      if ($this->isInUse ())
     return $this->throwError (2, _("Es ist nicht erlaubt die Frage im Nachhinein zu ändern."));

      if (empty ($question))
     return $this->throwError (1, _("Sie haben keine Fragestellung angegeben."));

      $this->question = $question;
   }

   /**
    * Gets the question of a vote
    * @access  public
    * @return  string  The question of a vote
    */
   function getQuestion () {
      return $this->question;
   }

   /**
    * Sets the answers
    * @access  public
    * @param   array $answerArray The answer(s)
    * @throws  error
    */
   function setAnswers ($answerArray) {
      if ($this->isInUse ())
     return $this->throwError (1,
                   _("Es ist nicht erlaubt die Antworten im Nachhinein zu ändern!"));

      if (!is_array ($answerArray) || empty ($answerArray))
     return $this->throwError (2, _("Es wurden keine Antworten zugeordnet."));

      $this->answerArray = $answerArray;
   }

   /**
    * make mysql happy
    */
   function addSlashesToText () {
       $this->question = addslashes ($this->question);
       for ($i=0; $i<count($this->getAnswers()); $i++ ) {
       $this->answerArray[$i]["text"] = addslashes( $this->answerArray[$i]["text"] );
       }
   }

   /**
    * Gets the answers
    * @access  public
    * @return  array   The answers.
    */
   function getAnswers () {
      if (!is_array ($this->answerArray))
     $this->answerArray = array ();
      return $this->answerArray;
   }

   /**
    * Sets the start date
    * @access  public
    * @param   integer $timestamp  The UNIX-timestamp from the start day
    * @throws  error
    */
   function setStartdate ($timestamp) {
      if ($this->isInUse ())
     return $this->throwError (1,
                   _("Es ist nicht erlaubt das Startdatum im Nachhinein zu ändern."));
      if ($timestamp != NULL & $timestamp <= 0)
     return $this->throwError (2, _("Das Startdatum ungültig."));

      if (!empty ($this->stopdate) && $timespan > $this->stopdate)
     return $this->throwError (3,
                   _("Startdatum ist größer als Enddatum."));


      $this->startdate = $timestamp;
      if (!$this->isNew ())
     $this->state = VOTE_STATE_NEW;
   }

   /**
    * Gets the start date
    * @access  public
    * @return  integer Start date as a UNIX timestamp
    */
   function getStartdate () {
      return $this->startdate;
   }

   /**
    * Sets the stop date
    * @access  public
    * @param   integer $timestamp  The UNIX-timestamp from the end day
    * @throws  error
    */
   function setStopdate ($timestamp) {
      if ($timestamp != NULL && $timestamp <= 0)
     return $this->throwError (1,
                   _("Das Stoppdatum ist leider ungültig."));
      if ($timestamp != NULL && !empty ($this->timespan))
     return $this->throwError (2, _("Es ist nur eine Zeitspanne oder ein Enddatum erlaubt."));
      if ($timestamp != NULL && $timestamp < $this->startdate)
     return $this->throwError (3,
                   _("Das Enddatum ist vor dem Startdatum."));

      $this->stopdate = $timestamp;
   }

   /**
    * Gets the stop date
    * @access  public
    * @return  integer End date as a UNIX timestamp.
    */
   function getStopdate () {
      return $this->stopdate;
   }

   /**
    * Gets the real stop date as a UNIX-timestamp (e.g. startdate + timespan)
    * @access  public
    * @return  integer The UNIX-timestamp with the real stopdate
    */
   function getRealStopdate () {
      $stopdate = $this->getStopdate ();

      if ($this->getTimespan () != NULL)
     $stopdate = $this->getStartdate () + $this->getTimespan ();

      return $stopdate;
   }

   /**
    * Gets the creation date
    * @access  public
    * @return  integer Creation date as a UNIX timestamp.
    */
   function getCreationdate () {
      return $this->creationdate;
   }

   /**
    * Gets the change date
    * @access  public
    * @return  integer Change date as a UNIX timestamp.
    */
   function getChangedate () {
      return $this->changedate;
   }

   /**
    * Sets the timespan
    * @access  public
    * @param   integer $seconds  The timespan in seconds
    * @throws  error
    */
   function setTimespan ($seconds) {
      if ($seconds != NULL && $seconds <= 0)
     return $this->throwError (1, _("Die Zeitspanne ist ungültig."));
      if ($seconds != NULL && !empty ($this->stopdate))
     return $this->throwError (2, _("Es ist nur eine Zeitspanne oder ein Enddatum erlaubt."));

      $this->timespan = $seconds;
   }

   /**
    * Gets the timespan
    * @access  public
    * @return  integer The timespan in days
    */
   function getTimespan () {
      return $this->timespan;
   }

   /**
    * Defines whether multiple choice is allowed
    * @access  public
    * @param   boolean  $multiplechoice  YES if multiple choice is allowed
    * @throws  error
    */
   function setMultiplechoice ($multiplechoice) {
      if ($this->isInUse ())
     return $this->throwError (2,
                   _("Es ist nicht erlaubt 'Multiple Choice' im Nachhinein zu ändern."));

      $this->multiplechoice = ($multiplechoice == NO) ? NO : YES;
   }

   /**
    * Checks whether multiple choice is allowed
    * @access  public
    * @return  boolean  If multiple choice is allowed -> YES
    */
   function isMultiplechoice () {
      return ($this->multiplechoice == YES) ? YES : NO;
   }

   /**
    * Sets the way how to show the results
    * @access  public
    * @param   integer  $mode  See const VOTE_RESULTS_*
    * @throws  error
    */
   function setResultvisibility ($mode) {
      if ($mode != VOTE_RESULTS_AFTER_VOTE &&
      $mode != VOTE_RESULTS_AFTER_END &&
      $mode != VOTE_RESULTS_ALWAYS &&
      $mode != VOTE_RESULTS_NEVER)
     return $this->throwError (1, _("Ungültige Ergebnissichtbarkeit. Siehe Konstanten VOTE_RESULTS_*."));

      $this->resultvisibility = $mode;
   }

   /**
    * Gets the way how to show the results
    * @access  public
    * @return  string  The mode. See VOTE_RESULTS_*
    */
   function getResultvisibility () {
      return $this->resultvisibility;
   }

   /**
    * Defines whether the names of participants will be publicly visible
    * @access  public
    * @param   boolean $namesvisibility YES if names shall be visible
    * @throws  error
    */
   function setNamesvisibility ($namesvisibility) {
      if ($namesvisibility == YES &&
      $this->resultvisibility == VOTE_RESULTS_NEVER)
     return $this->throwError (1, _("Wenn die Namen der Teilnehmer sichtbar gemacht werden sollen, darf die Ergebnissichtbarkeit nicht auf \"nie\" stehen."));
      elseif ($namesvisibility == YES &&
          $this->isAnonymous ())
     return $this->throwError (2, _("Die Namen der Teilnehmer k&ouml;nnen nicht sichtbar gemacht werden, wenn die Auswertung anonym ist."));

      $this->namesvisibility = $namesvisibility;
   }

   /**
    * Gets whether the names of participants will be publicly visible
    * @access  public
    * @return  boolean
    */
   function getNamesvisibility () {
      return ($this->namesvisibility == YES) ? YES : NO;
   }

   /**
    * Defines whether the vote is to be linked with a user
    * @access  public
    * @param   boolean $anonymous  YES if vote is not be linked with a user
    * @throws  error
    */
   function setAnonymous ($anonymous) {
      if ($this->isInUse ())
     return $this->throwError (2, _("Es ist nicht erlaubt die Auswertungsoption im Nachhinein zu ändern!"));

      $this->anonymous = ($anonymous == NO) ? NO : YES;
   }

   /**
    * Checks whether the vote is to be linked with a user
    * @access  public
    * @return  boolean YES if vote is not linked with a user
    */
   function isAnonymous () {
      return ($this->anonymous == YES) ? YES : NO;
   }

   /**
    * Defines whether it´s allowed for a user to change his/her answer
    * @access  public
    * @param   boolean $changeable  YES if it´s allowed to change the answer
    * @throw   error
    */
   function setChangeable ($changeable) {
      if ($this->isAnonymous () && $changeable == YES)
     return $this->throwError (1, _("Antwortaenderung ist mit der Option 'anonym' leider nicht moeglich."));

      $this->changeable = ($changeable == NO) ? NO : YES;
   }

   /**
    * Checks whether the it´s allowed to change the answer
    * @access  public
    * @return  boolean YES if it´s allowed to change the answer
    */
   function isChangeable () {
      return ($this->changeable == YES) ? YES : NO;
   }

   /**
    * Checks whether the vote is visible
    * @access  public
    * @return  boolean YES if vote is visible
    */
   function isVisible () {
      return ($this->visible == YES) ? YES : NO;
   }

   /**
    * Sets the unique ID from the vote
    * @access  public
    * @param   string  $objectID  The unique ID
    */
   function setVoteID ($objectID) {
      $this->objectID = $objectID;
   }

   /**
    * Gets the unique ID from the vote
    * @access  public
    * @return  string The unique ID
    */
   function getVoteID () {
      return $this->objectID;
   }

   /**
    * Sets the unique range ID from the vote
    * @access  public
    * @param   string  $rangeID  The unique range ID
    */
   function setRangeID ($rangeID) {
      $this->rangeID = $rangeID;
   }

   /**
    * Gets the unique range ID from the vote
    * @access  public
    * @return  string The unique range ID
    */
   function getRangeID () {
      return $this->rangeID;
   }

   /**
    * Sets the unique author ID from the vote
    * @access  public
    * @param   string  $authorID  The unique author ID
    */
   function setAuthorID ($authorID) {
      $this->authorID = $authorID;
   }

   /**
    * Gets the unique user ID from the owner of the vote
    * @access  public
    * @return  string The unique user ID
    */
   function getAuthorID () {
      return $this->authorID;
   }

   /**
    * Check whether a user had already used this vote
    * @access  public
    * @return  bollean YES if a user had already used the vote
    */
   function isInUse () {
      return ($this->isInUse == NO) ? NO : YES;
   }

   /**
    * Checks whether the vote is new
    * @access  public
    * @return  boolean YES if vote is new
    */
   function isNew () {
      return ($this->state == VOTE_NEW) ? YES : NO;
   }

   /**
    * Checks whether the vote is active
    * @access  public
    * @return  boolean YES if vote is active
    */
   function isActive () {
      return ($this->state == VOTE_ACTIVE) ? YES : NO;
   }

   /**
    * Checks whether the vote is stopped
    * @access  public
    * @return  boolean YES if vote is stopped
    */
   function isStopped () {
      return ($this->state == VOTE_STOPPED_VISIBLE ||
          $this->state == VOTE_STOPPED_INVISIBLE) ? YES : NO;
   }

   /**
    * Sorts the answerarray
    * @access public
    */
   function sortVoteAnswers () {
      usort ($this->answerArray, array ($this, "sortVoteAnswerarray"));
   }

   /**
    * Gets the number of votes from the answer with the most votes
    * @access public
    * @returns the number
    */
   function getMaxAnswer () {
      $max = 0;
      foreach ($this->getAnswers () as $answer)
     if ($answer["counter"] > $max)
        $max = $answer["counter"];
      return $max;
   }

   /**
    * Gets the total number of votes
    * @access public
    * @returns the number
    */
   function getNumberAnswers () {
      $number = 0;
      foreach ($this->getAnswers () as $answer)
     $number += $answer["counter"];
      return $number;
   }

   /**
    * Gets the total number of persons who voted
    * @access public
    * @returns the number
    */
   function getNumberPersons () {
      return $this->voteDB->getNumberUserVoted ();
   }

   /**
    * Associate a user with an answer or vote in the database (!)
    * @access  public
    * @param   string  $userID      The unique user ID
    * @param   array   $answerArray An array with one or more answers
    * @throws  error
    */
   function executeAssociate ($userID, $answerArray) {
      $answerNumber = count ($this->answerArray);

      /* Check for illegal input ------------------------------------------- */
      if (count($answerArray) <= 0)
     return $this->throwError (2,
                   _("Sie haben keine Antwort ausgewählt."));

      if (!is_array ($answerArray))
     return $this->throwError (1, _("Ungültiger Aufruf: Wert ist kein Array."));

      if (!$this->isMultiplechoice () && count($answerArray) != 1)
     return $this->throwError (2,
                   _("Mehrfachantworten sind nicht erlaubt!"));


      foreach ($answerArray as $answer) {
     if ($answer < 0 || $answer > $answerNumber)
        $this->throwError (3, _("Keine gültige Antwort ausgewählt!"));
      }
      if ($this->isError ())
     return;
      /* ------------------------------------------------------------------- */


      /* Write data -------------------------------------------------------- */
      $this->voteDB->participate ($this->objectID, $userID,
                  $answerArray, $this->isAnonymous ());
      if ($this->voteDB->isError ())
     $this->throwErrorFromClass ($this->voteDB);
      /* ------------------------------------------------------------------- */

      /* Load new data ----------------------------------------------------- */
      $this->readVote ();
      /* ------------------------------------------------------------------- */
   }

   /**
    * Starts the vote in the database (!)
    * @access  public
    * @throws  error
    */
   function executeStart () {
      if ($this->getState () != VOTE_STATE_NEW) {
     $this->throwError (1, _("Nur neue Objekte können gestartet werden."));
      }
      if ($this->isError ()) return;

      $this->state = VOTE_STATE_ACTIVE;
      $this->startdate = time ();
      if ($this->stopdate <= $this->startdate)
     $this->stopdate = NULL;
      $this->voteDB->startVote ($this->objectID, $this->state,
                $this->startdate,
                $this->stopdate, $this->timespan);
      if ($this->voteDB->isError ())
     $this->throwErrorFromClass ($this->voteDB);
   }

   /**
    * Stops the vote in the database (!)
    * @access  public
    * @throws  error
    */
   function executeStop () {
      if ($this->getState () != VOTE_STATE_ACTIVE) {
     $this->throwError (1,
                _("Nur ein laufendes Objekt kann gestoppt werden"));
      }
      if ($this->isError ()) return;

      if ($this->resultvisibility == VOTE_RESULTS_NEVER)
     $this->state = VOTE_STOPPED_INVISIBLE;
      else
     $this->state = VOTE_STOPPED_VISIBLE;


      $this->stopdate = time ();
      $this->voteDB->stopVote ($this->objectID, $this->state, $this->stopdate);
      if ($this->voteDB->isError ())
     $this->throwErrorFromClass ($this->voteDB);
   }

   /**
    * Removes the vote from the database (!)
    * @access  public
    * @throws  error
    */
   function executeRemove () {
      //if ($this->isError ()) return;

      $this->voteDB->removeVote ($this->objectID);
      if ($this->voteDB->isError ())
     $this->throwErrorFromClass ($this->voteDB);
   }

   /**
    * Restarts the vote in the database (!)
    * @access  public
    * @throws  error
    */
   function executeRestart () {
      if ($this->getState () == VOTE_NEW) {
     $this->throwError (1,
                _("Ein neues Objekt kann nicht neu gestartet werden!"));
      }
      if ($this->isError ()) return;

      $this->startdate = time ();
      if ($this->stopdate <= $this->startdate)
     $this->stopdate = NULL;
      $this->voteDB->restartVote ($this->objectID, $this->startdate,
                  $this->stopdate, $this->isAnonymous ());
      if ($this->voteDB->isError ())
     $this->throwErrorFromClass ($this->voteDB);
   }

   /**
    * Continues the vote in the database (!)
    * @access  public
    * @throws  error
    */
   function executeContinue () {
      if (!$this->isStopped ()) {
     $this->throwError
        (1, _("Nur ein gestopptes Objekt kann fortgesetzt werden!"),
         __LINE__, __FILE__);
      }
      if ($this->isError ()) return;

      $this->startdate = time ();
      if ($this->stopdate <= $this->startdate)
     $this->stopdate = 0;
      $this->voteDB->continueVote ($this->objectID, $this->startdate,
                   $this->stopdate);
      if ($this->voteDB->isError ())
     $this->throwErrorFromClass ($this->voteDB);
   }

   /**
    * Sets the visibility of a stopped vote in the database (!)
    * @access  public
    * @param   boolean $visibility If YES -> Stopped vote gets visible
    * @throws  error
    */
   function executeSetVisible ($visibility) {
      if (!$this->isStopped ())
     $this->throwError (1, _("Es können nur gestoppte Objecte (un)sichtbar geschaltet werden!"));

      if ($this->isError ()) return;


      $this->visible = ($visibility == NO) ? NO : YES;
      if ($this->isVisible ())
     $this->state = VOTE_STOPPED_VISIBLE;
      else
     $this->state = VOTE_STOPPED_INVISIBLE;

      $this->voteDB->setVisible ($this->objectID, $this->state);
      if ($this->voteDB->isError ())
     $this->throwErrorFromClass ($this->voteDB);
   }

   /**
    * Writes the vote into the database (!)
    * @access  public
    * @throws  error
    */
   function executeWrite () {
      $this->checkConsistency ();
      if ($this->isError ()) return;
# Die writeVote2-Funktion ist eine veränderte Version von mir (alex)
#      $this->voteDB->writeVote2 ();
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
                time (), #,$this->getChangedate (),provisorisch
                $this->getResultvisibility (),
                $this->getNamesvisibility (),
                $this->isMultiplechoice (),
                $this->isAnonymous (),
                $this->getAnswers (),
                $this->isChangeable (),
                NULL, # co_visibility
                $this->x_instanceof ()
                );
      if ($this->voteDB->isError ())
     $this->throwErrorFromClass ($this->voteDB);
   }
# ===================================================== end: public functions #



# Define public static functions ============================================ #
   /**
    * Checks and transforms a date into a UNIX (r)(tm) timestamp
    * @access public
    * @static
    * @param   integer $day    The day
    * @param   integer $month  The month
    * @param   integer $year   The year
    * @param   integer $hour   The hour (optional)
    * @param   integer $minute The minute (optional)
    * @param   integer $second The second (optional)
    * @return  integer If an error occurs -> -1. Otherwise the UNIX-timestamp
    */
   function date2timestamp ($day, $month, $year,
                $hour = 0, $minute = 0, $second = 0) {
      if (!checkdate ((int)$month, (int)$day, (int)$year) ||
      $hour < 0 || $hour > 24 ||
      $minute < 0 || $minute > 59 ||
      $second < 0 || $second > 59) {
     return -1;
      }
      return mktime ($hour, $minute, $second, $month, $day, $year);
   }
# ===================================================== end: static functions #



# Define private functions ================================================== #
   /**
    * Loads an old vote (internal!)
    * @access  private
    * @throws  error
    */
   function readVote () {
      $result = $this->voteDB->getVote ($this->objectID);

      if ($this->voteDB->isError ()) {
     $this->throwErrorFromClass ($this->voteDB);
     return;
      }
      $this->setIsInUse (NO); // because of a re-read of the data after a vote
      $this->setRangeID ($result["range_id"]);
      $this->setAuthorID ($result["author_id"]);
      $this->setTitle ($result["title"]);
      $this->setQuestion ($result["question"]);
      $this->setStartdate ($result["startdate"]);
      $this->setState ($result["state"]);
      $this->setStopdate ($result["stopdate"]);
      $this->setCreationdate ($result["mkdate"]);
      $this->setChangedate ($result["chdate"]);
      $this->setTimespan ($result["timespan"]);
      $this->setMultiplechoice ($result["multiplechoice"]);
      $this->setResultvisibility ($result["resultvisibility"]);
      $this->setAnonymous ($result["anonymous"]);
      $this->setNamesvisibility ($result["namesvisibility"]);
      $this->setChangeable ($result["changeable"]);
      $this->setAnswers ($result["answerArray"]);
      $this->setIsInUse ($result["isAssociated"]);

      $this->checkConsistency ();
   }

   /**
    * Sets isInUse (internal!)
    * @access  private
    * @param   integer $isInUse  YES / NO
    */
   function setIsInUse ($isInUse) {
      $this->isInUse = ($isInUse == YES) ? YES : NO;
   }

   /**
    * Sets the state (internal!)
    * @access  private
    * @param   integer $state  The state of the vote. See VOTE_*
    * @throws  error
    */
   function setState ($state) {
      $this->visible = ($state == VOTE_STOPPED_VISIBLE) ? YES : NO;
      $this->state = $state;
   }

   /**
    * Sets the create date (internal!)
    * @access  private
    * @param   integer $timestamp  The UNIX-timestamp from the create day
    * @throws  error
    */
   function setCreationdate ($timestamp) {
      if ($timestamp <= 0)
     $this->throwError (1, _("Erstellzeit leider ungültig."));
      else
     $this->creationdate = $timestamp;
   }

   /**
    * Sets the change date (internal!)
    * @access  private
    * @param   integer $timestamp  The UNIX-timestamp from the change day
    * @throws  error
    */
   function setChangedate ($timestamp) {
      if ($timestamp <= 0)
     $this->throwError (1,_("Änderungsdatum leider ungültig."));
      else
     $this->changedate = $timestamp;
   }

   /**
    * Gets the internal state of a vote (internal!)
    * @access  private
    * @return  integer  The State. See VOTE_*
    */
   function getState () {
      return $this->state;
   }


   /**
    * Checks the consostency of the vote (internal!)
    * @access  private
    * @throw   error
    */
   function checkConsistency () {
      /* Check the normal variables ---------------------------------------- */
      if (empty ($this->objectID))
     $this->throwError (1, _("Objekt besitzt keine ID!"));

      if (empty ($this->authorID))
     $this->throwError (2, _("Objekt ist keinem User zugeordnet!"));

      if (empty ($this->rangeID))
     $this->throwError (3, _("Objekt ist keinem Bereich zugeordnet!"));

      if (empty ($this->title))
     $this->throwError (4, _("Objekt besitzt keinen Titel."));

      if (empty ($this->question))
     $this->throwError (5, _("Objekt besitzt keine Fragestellung."));

      if (empty ($this->answerArray) || !is_array ($this->answerArray))
     $this->throwError (6, _("Objekt besitzt keine Antworten."));

      if (empty ($this->creationdate) || $this->creationdate > time ())
     $this->throwError(7,
               _("Objekt besitzt kein gültiges Erstellungsdatum!"));

      if (empty ($this->changedate) || $this->changedate > time ())
     $this->throwError (8,
                _("Objekt besitzt kein gültiges Änderungsdatum!"));

      if ($this->resultvisibility != VOTE_RESULTS_AFTER_VOTE &&
      $this->resultvisibility != VOTE_RESULTS_AFTER_END &&
      $this->resultvisibility != VOTE_RESULTS_ALWAYS &&
      $this->resultvisibility != VOTE_RESULTS_NEVER)
     $this->throwError (13, _("Objekt besitzt ungültigen Status für die Ergebnissichtbarkeit!"));

      if ($this->state != VOTE_NEW &&
      $this->state != VOTE_ACTIVE &&
      $this->state != VOTE_STOPPED_VISIBLE &&
      $this->state != VOTE_STOPPED_INVISIBLE)
     $this->throwError (14, _("Objekt besitzt ungültigen Status!"));
      /* ------------------------------------------------------------------- */


      /* It´s not possible to have a stopdate and a timespan --------------- */
      if (!empty ($this->stopdate) && !empty ($this->timespan))
     $this->throwError (15,
                _("Objekt hat ein Enddatum UND eine Zeitspanne!"));
      /* ------------------------------------------------------------------- */


      /* Running Vote without stardate? ------------------------------------ */
      if ($this->state == VOTE_ACTIVE && empty($this->startdate))
     $this->throwError (16, _("Laufender Vote hat kein Startdatum!"));
      /* ------------------------------------------------------------------- */


      /* Stop before start? ------------------------------------------------ */
      if (!empty ($this->stopdate) && ($this->stopdate < $this->startdate))
     $this->throwError (17, _("Startdatum des Votes ist vor Enddatum!"));
      /* ------------------------------------------------------------------- */


      /* Running Vote with invalid stopdate? ------------------------------- */
      /*
musste ich rausnehmen, da man sonst einem laufenden Vote kein Enddatum mehr
geben konnte das in der Vergangenheit liegt
      if ($this->state == VOTE_ACTIVE && (
                      (!empty ($this->stopdate) &&
                       $this->stopdate < time ()) ||
                      (!empty ($this->timespan) &&
                       $this->startdate +
                       $this->timespan < time ())) )
     $this->throwError (18, _("Laufender Vote hätte bereits beendet werden müssen!"));
      */
      /* ------------------------------------------------------------------- */

      /* What about all the dates? ----------------------------------------- */

      /* ------------------------------------------------------------------- */
   }

   /**
    * Sorts the answerarray
    * @param   array $a first answerarray
    * @param   array $b second answerarray
    * @access  private
    */
   function sortVoteAnswerarray ($a, $b) {
      if ($a["counter"] == $b["counter"]) return 0;
      return ($a["counter"] < $b["counter"]) ? 1 : -1;
   }
# ==================================================== end: private functions #

}

?>
