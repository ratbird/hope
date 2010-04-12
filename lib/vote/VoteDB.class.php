<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
// +--------------------------------------------------------------------------+
// This file is part of Stud.IP
// VoteDB.class.php
//
// blablabla
//
// Copyright (c) 2003 John Patrick Wowra <jpwowra@...>
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


// Including all needed files
require_once ('lib/classes/StudipObject.class.php');

# wozu diese Datei?

//require_once ("lib/phplib_local.inc.php");

require_once 'lib/functions.php'; // <- für getHeaderLine


/**
 * VoteDB.class.php
 *
 * This class is used to ...
 *
 * @author      John Patrick Wowra jpwowra@math.uni-goettingen.de
 * @access      public
 * @package     vote
 * @modulegroup vote_modules
 *
 */
class VoteDB extends StudipObject {

  /**
   * Variable $db
   *
   * @access   public
   */
   var $db;
   
   /**
    * Holds an instance of a vote
    * @access private
    * @var    Vote  $vote
    */
   var $vote;

   /**
    * Constructor
    *
    * @access   public
    */
   function VoteDB () {
      parent::StudipObject ();
      $this->db = new DB_Seminar ();
      // Für eigene Fehlerroutine am Ende auf "no" schalten!...
      $this->db->Halt_On_Error = "yes";
      return;
   }

   function setVote (&$vote) {
      $this->vote = &$vote;
   }

   /**
    * Gets the new votes in the specified rangeID
    *
    * @access  public
    * @param   string   $rangeID   The specified rangeID
    * @returns array    All active voteID's
    */
   function getNewVotes ($rangeID) {
    
    // convert username to userID
    if ($id = get_userID($rangeID))
        $rangeID = $id;
   
      $result = array ();
      $query = "SELECT * FROM vote WHERE range_id='".$rangeID."' AND ".
    "state = 'new' ORDER BY chdate DESC";

      $this->db->query ($query);

      if ($this->db->nf() == 0) {
    $this->throwError (1, _("Es wurden keine aktiven Votes in der Datenbank gefunden."),
               __LINE__, __FILE__); 
      }

      while ($this->db->next_record ()) {
      array_push ($result, array( "type" => $this->db->f("type"), 
                      "voteID" => $this->db->f("vote_id")));
      }
      //array mit type und VoteID zurückgeben...
      return $result;
   }
  
   /**
    * Gets the active votes in the specified rangeID
    *
    * @access  public
    * @param   string   $rangeID   The specified rangeID
    * @returns array    All active voteID's
    */
   function getActiveVotes ($rangeID) {
    
    // convert username to userID
    if ($id = get_userID($rangeID))
        $rangeID = $id;
        
      $result = array ();
      $query = "SELECT * FROM vote WHERE range_id='".$rangeID."' AND ".
    "state = 'active' ORDER BY chdate DESC";

      $this->db->query ($query);

      if ($this->db->nf() == 0) {
    $this->throwError (1, _("Es wurden keine aktiven Votes in der Datenbank gefunden."),
               __LINE__, __FILE__); 
      }

      while ($this->db->next_record ()) {
      array_push ($result, array( "type" => $this->db->f("type"), 
                      "voteID" => $this->db->f("vote_id")));
      }
      //array mit type und VoteID zurückgeben...
      return $result;
   }


   /**
    * Gets stopped votes in the specified rangeID that are visible to authors
    *
    * @access  public
    * @param   string   $rangeID   The specified rangeID
    * @returns array    All active voteID's
    */
   function getStoppedVisibleVotes ($rangeID) {
    
    // convert username to userID
    if ($id = get_userID($rangeID))
        $rangeID = $id;
        
      $result = array ();

      $query = "SELECT * FROM vote WHERE range_id='".$rangeID."' AND ".
    "state = 'stopvis' ORDER BY chdate DESC";
      $this->db->query ($query);
      if ($this->db->nf() == 0) {
    $this->throwError (1, _("Es wurden keine gestoppten sichtbaren Votes in der Datenbank gefunden."),
               __LINE__, __FILE__); 
      }
      while ($this->db->next_record ()) {
#     if ($this->db->f("resultvisibility") != VOTE_RESULTS_NEVER)
      array_push ($result, array("type" => $this->db->f("type"), 
                     "voteID" => $this->db->f("vote_id")));
      }
      return $result;
   }


  /**
    * Gets stopped votes in the specified rangeID 
    *
    * @access  public
    * @param   string   $rangeID   The specified rangeID
    * @returns array    All voteID's of stopped votes
    */
   function getStoppedVotes ($rangeID) {
    
    // convert username to userID
    if ($id = get_userID($rangeID))
        $rangeID = $id;
        
     $result = array ();
     $query = "SELECT * FROM vote WHERE range_id='".$rangeID."' AND ".
       "(state = 'stopvis' OR state = 'stopinvis') ORDER BY chdate DESC";
     $this->db->query ($query);
     if ($this->db->nf() == 0) {
       $this->throwError (1, _("Es wurden keine gestoppten Votes in der Datenbank gefunden."),
              __LINE__, __FILE__); 
     }
     while ($this->db->next_record ()) {
       array_push ($result, array( "type" => $this->db->f("type"), 
                   "voteID" => $this->db->f("vote_id")));
     }
     return $result;
   }
   

   /**
    * Gets new votes from a specified user
    *
    * @access  public
    * @param   string   $userID    The ID of the demanding user
    * @returns array    All active voteID's
    */ 
   function getNewUserVotes ($authorID, $rangeID = NULL) {
    
    // convert username to userID
    if ($id = get_userID($rangeID))
        $rangeID = $id;
        
      $result = array ();
      if($rangeID != NULL)
    $query = "SELECT * FROM vote WHERE author_id='".$authorID."' AND ".
      "state = 'new' AND range_id='".$rangeID."' ORDER BY chdate DESC";
      else
    $query = "SELECT * FROM vote WHERE author_id='".$authorID."' AND ".
      "state = 'new' ORDER BY chdate DESC";
      $this->db->query ($query);

      if ($this->db->nf() == 0) {

    $this->throwError (1, _("Es wurden keine passenden Votes in der Datenbank gefunden."),
               __LINE__, __FILE__); 
      }
      while ($this->db->next_record ()) {
    array_push ($result, array( "type" => $this->db->f("type"), 
                   "voteID" => $this->db->f("vote_id")));
      }
      return $result;
   }


   /**
    * Gets active votes from a specified user
    *
    * @access  public
    * @param   string   $userID    The ID of the demanding user
    * @returns array    All active voteID's
    */
   function getActiveUserVotes ($authorID, $rangeID = NULL) {
    
    // convert username to userID
    if ($id = get_userID($rangeID))
        $rangeID = $id;
        
      $result = array ();
      if($rangeID == NULL)
    $query = "SELECT * FROM vote WHERE author_id='".$authorID."' AND ".
      "state = 'active' ORDER BY chdate DESC";
      else
    $query = "SELECT * FROM vote WHERE author_id='".$authorID."' AND ".
      "state = 'active' AND range_id = '".$rangeID."' ORDER BY chdate DESC";
      $this->db->query ($query);
      if ($this->db->nf() == 0) {
    $this->throwError (1, _("Es wurden keine passenden Votes in der Datenbank gefunden."),
               __LINE__, __FILE__); 
      }
      while ($this->db->next_record ()) {
    array_push ($result, array( "type" => $this->db->f("type"), 
                   "voteID" => $this->db->f("vote_id")));

      }
      return $result;
   }


   /**
    * Gets stopped  votes from a specified user 
    *
    * @access  public
    * @param   string   $userID    The ID of the demanding user
    * @returns array    All active voteID's
    */
   function getStoppedUserVotes ($authorID, $rangeID = NULL) {
    
    // convert username to userID
    if ($id = get_userID($rangeID))
        $rangeID = $id;
        
      $result = array ();
      if($rangeID == NULL)
      $query = "SELECT * FROM vote WHERE author_id='".$authorID."' AND ".
    "(state = 'stopvis' or state = 'stopinvis') ORDER BY chdate DESC";
      else
      $query = "SELECT * FROM vote WHERE author_id='".$authorID."' AND ".
        "(state = 'stopvis' or state = 'stopinvis') AND ".
        "range_id ='".$rangeID."' ORDER BY chdate DESC";
      $this->db->query ($query);
      if ($this->db->nf() == 0) {
    $this->throwError (1, _("Es wurden keine passenden Votes in der Datenbank gefunden."),
               __LINE__, __FILE__); 
      }
      while ($this->db->next_record ()) {
    array_push ($result, array( "type" => $this->db->f("type"), 
                   "voteID" => $this->db->f("vote_id")));
      }
      return $result;
   }


   /**
    * Deletes a vote from the DB
    *
    * @access  public
    * @param   string   $voteID   The specified voteID
    */
   function removeVote($voteID){
     $result = array();
      $query="SELECT answer_id FROM voteanswers WHERE vote_id='".$voteID."'";
      $this->db->query ($query);
      while ($this->db->next_record ()) {
#    array_push ($result, $this->db->f("vote_id"));
     array_push ($result, $this->db->f("answer_id"));
      } 
      foreach ($result as $answerID){
         $query="DELETE FROM voteanswers_user WHERE ".
                "answer_id='".$answerID."'";
     $this->db->query ($query);
      }
      $query="DELETE FROM vote_user WHERE vote_id='".$voteID."'";
      $this->db->query ($query);
      $query="DELETE FROM voteanswers WHERE vote_id='".$voteID."'";
      $this->db->query ($query);
      $query="DELETE FROM vote WHERE vote_id='".$voteID."'";
      $this->db->query ($query);
   }


   /**
    * Reads a specified vote from the DB and returns an array of it's params. 
    *
    * @access  public
    *
    * @param   string   $voteID         specified voteID
    * @returns array    $votearray      Parameters of the Vote
    */
   function getVote ($voteID) {
     // auf anonymous eingehen....
      $this->db->query ("SELECT * FROM vote WHERE vote_id='".$voteID."'");
      if ($this->db->nf() == 0) {
     $this->throwError (1, _("Es wurden keine Votes mit der angegebenen ID gefunden."),
                __LINE__, __FILE__);  
      } else {
     $this->db->next_record();
     $votearray=array('vote_id'       => $this->db->f("vote_id"),
              'author_id'     => $this->db->f("author_id"),
              'range_id'      => $this->db->f("range_id"),
              'type'          => $this->db->f("type"), 
              'title'         => $this->db->f("title"),
              'question'      => $this->db->f("question"),
              'state'         => $this->db->f("state"),
              'startdate'     => $this->db->f("startdate"),
              'stopdate'      => $this->db->f("stopdate"),
              'timespan'      => $this->db->f("timespan"),
              'mkdate'        => $this->db->f("mkdate"),
              'chdate'        => $this->db->f("chdate"),
              'resultvisibility'=>$this->db->f("resultvisibility"),
              'namesvisibility'=>$this->db->f("namesvisibility"),
              'multiplechoice'=> $this->db->f("multiplechoice"), 
              'anonymous'     => $this->db->f("anonymous"), 
              'changeable'    => $this->db->f("changeable"), 
              'co_visibility' => $this->db->f("co_visibility"), 
              'answerArray'   => array (),
              'isAssociated'  => $this->hasanyoneparticipated($voteID,$this->db->f("anonymous")));

    // convert userID to username
    if ($name = get_username($votearray['range_id']))
       $votearray['range_id'] = $name;
                                       
#Entsprechende Antworten aus der DB laden und in einem abgefahrenen 
#3d Array speichern!

     $this->db->query ("SELECT * FROM voteanswers WHERE ".
               "vote_id='".$voteID."' ORDER BY position");
       for ($count = 0; $this->db->next_record (); $count ++) { 
       if($votearray["anonymous"])  
         $votearray['answerArray'][$count] =    
           array (    
              'answer_id' => $this->db->f("answer_id"),  
              'text' => $this->db->f("answer"),
              'counter'=> $this->db->f("counter"),
              'correct'   => $this->db->f("correct")
              );
       else
         // kacke ich kann hier ja $this->db->query"";
         // nich einfach ein query einfügen
         $votearray['answerArray'][$count] = 
        array (    
            'answer_id' => $this->db->f("answer_id"),  
            'text' => $this->db->f("answer"),
            'counter'=> $this->db->f("counter"),
            'correct'   => $this->db->f("correct")
            );
       }
       return $votearray;
      }
   }


   /**
    * Checks wheather a vote with a specified ID already exists
    * @access  public
    * @param   specified Vote ID
    * @returns TRUE or FALSE
    */
   function isExistant ($voteID) {
      $this->db->query("SELECT * FROM vote WHERE vote_id='".$voteID."'");
      if ($this->db->nf() == 0)
     return FALSE;
      else
     return TRUE;
   }   
   

   /**
    * Checks whether a user has participated in a specified vote
    * @access  public
    * @param   string  $userID  The unique user ID
    * @return  boolean True if user had already used his/her vote
    */
   function isAssociated ($voteID, $userID) {
      $query="SELECT * FROM vote WHERE vote_id='".$voteID."'";
      $this->db->query ($query);
      $this->db->next_record();
         if($this->db->f("anonymous") == 1) {
       $sql="SELECT * FROM vote_user WHERE ".
         "vote_id='".$voteID."' AND ".
         "user_id='".$userID."'";
     $this->db->query ($sql);
     //ändert nix      $this->db->next_record();
         if ($this->db->nf() == 0){
        return FALSE;
     } else {
        return TRUE;
     }
      } else {
     $sql = 
        "SELECT".
        " a.answer_id, b.answer_id ".
        "FROM".
        " voteanswers_user a, voteanswers b ".
        "WHERE".
        " a.user_id = '".$userID."'".
        " AND".
        " b.answer_id = a.answer_id".
        " AND".
        " b.vote_id = '".$voteID."'";
     $this->db->query ($sql);
     if ($this->db->nf() == 0)
        return FALSE;
     else
        return TRUE;
      }
   }   


   /**
    * Checks whether any user has participated in a specified vote
    * @access  public
    * @return  boolean True if user had already used his/her vote
    */
   function hasanyoneparticipated ($voteID, $anonymous = NO) {
    /*  $query = "SELECT * FROM vote WHERE vote_id='".$voteID."'";
      $this->db->query ($query);
      $this->db->next_record();
      */
      /* If vote is anonymous ---------------------------------------------- */
      if ($anonymous == YES) {
     $sql="SELECT vote_id FROM vote_user WHERE vote_id = '".$voteID."' LIMIT 1";
     $this->db->query ($sql);
     $this->db->next_record();
     return ($this->db->f(0)) ? YES : NO;
      }
      /* If vote is not anonymous ------------------------------------------ */
      else {
     $sql = 
        "SELECT".
        " b.vote_id ".
        " FROM ".
        "  voteanswers b INNER JOIN voteanswers_user a USING(answer_id)".
        "WHERE".
        " b.vote_id = '".$voteID."' LIMIT 1";
     $this->db->query ($sql);
     $this->db->next_record();
     return ($this->db->f(0)) ? YES : NO;
      }
      /* ------------------------------------------------------------------- */
   }

   /**
    * Gets the number of users which used the vote
    * @access public
    * @return integer The number of users which used the vote
    */
   function getNumberUserVoted () {
      /* If vote is anonymous ---------------------------------------------- */
      if ($this->vote->isAnonymous ()) {
     $sql = 
        "SELECT".
        " count(DISTINCT user_id) ".
        "AS".
        " number ".
        "FROM".
        " vote_user ".
        "WHERE".
        " vote_id = '".$this->vote->getVoteID ()."'";
      }
      /* If vote is not anonymous ------------------------------------------ */
      else {
     $sql = 
        "SELECT".
        " count(DISTINCT a.user_id) ".
        "AS".
        " number ".
        "FROM".
        " voteanswers_user a, voteanswers b ".
        "WHERE".
        " b.answer_id = a.answer_id".
        " AND".
        " b.vote_id = '".$this->vote->getVoteID ()."'";
      }
      /* ------------------------------------------------------------------- */
      $this->db->query ($sql);
      $this->db->next_record ();
      return $this->db->f ("number");
   }

   /**
    * Gets the name of the range from the vote
    * @access  public
    * @return  string The name of the range
    */
   function getRangename ($rangeID) {
     $sql="SELECT username FROM auth_user_md5 WHERE ".
       "user_id = '".$rangeID."' OR ".
       "username = '".$rangeID."'";
     $this->db->query ($sql);
     if ($this->db->nf() == 0){
       if ($rangeID == "studip")
     $rangename = _("Systemweite Votings/Tests");
       else
     $rangename = getHeaderLine($rangeID);
       return $rangename;
     }
     else{
       $this->db->next_record ();
       $username = "Homepage: ".$this->db->f ("username")."";
       return $username;
     }
   }
   
   /**
    * Gets the username from the owner of the vote
    * @access  public
    * @return  string The username
    */
   function getAuthorUsername ($authorID) {
      $username = "nixgefunden";
      $this->db->query("SELECT username FROM auth_user_md5 WHERE ".
               "user_id='".$authorID."'");
      if ($this->db->nf() == 0) {
     $this->throwError (1, _("Keinen User mit der ID gefunden"),
                __LINE__, __FILE__);
      } else {
     $this->db->next_record ();
     $username = $this->db->f ("username");
      }
      return $username;
   }


   /**
    * Gets the real name from the owner of the vote
    * @access  public
    * @return  string The real name ("name surname")
    */
   function getAuthorRealname ($authorID) {
      $realname = "Nix";
      $this->db->query("SELECT Vorname, Nachname FROM auth_user_md5 WHERE ".
               "user_id='".$authorID."'");
      if ($this->db->nf() == 0) {
     return $realname;
      } else {
     $this->db->next_record();
     $realname= $this->db->f("Vorname")." ".$this->db->f("Nachname");
     return $realname;
      }
      return $realname;
   }


   /**
    * Gets the real name from the owner of the vote
    * @access  public
    * @param   voteID     VoteID of the specified vote
    * @param   $state     New state of the vote
    * @param   $startdate New date, when the vote starts
    * @param   $stopdate  New date, when the vote stops
    * @param   $timespan  A useless parameter
    */
   function startVote ($voteID, $state, $startdate, $stopdate, $timespan) {
      if ($startdate == NULL) $startdate = "NULL";
      if ($stopdate == NULL) $stopdate = "NULL";
      if ($timespan == NULL) $timespan = "NULL";

      $sql = "UPDATE vote SET ".
    "state = '".$state."', ".
    "chdate='".time()."' , ".
    "startdate = ".$startdate.", ".
    "stopdate = ".$stopdate.", ".
    "timespan = ".$timespan." ".
    "WHERE vote_id = '".$voteID."'";
      $this->db->query ($sql);

      if (!$this->db->affected_rows())
     $this->throwError (1, _("Vote konnte nicht gestartet werden"), 
                __LINE__, __FILE__);
   }


   /**
    * Stopps an active vote by setting its state to 'stopped'.
    * @access  public
    * @param   voteID     VoteID of the specified vote
    * @param   $state     New state of the vote
    * @param   $stopdate  New date, when the vote stops
    */
   function stopVote ($voteID, $state, $stopdate) {
      $sql = 
     "UPDATE".
     " vote ".
     "SET".
     " state     = '".$state."',".
#    " chdate    = '".time()."', ".
#    " startdate = ".$startdate.",".
     " stopdate  = '".$stopdate."', ".
     " timespan  = NULL ".
     " WHERE".
     " vote_id = '".$voteID."'";
      $this->db->query ($sql);
      if (!$this->db->affected_rows())
    $this->throwError (1, _("Vote konnte nicht gestartet werden"), 
               __LINE__, __FILE__);
   }


  /**
    * Continues a stopped vote by setting it's state to active
    * @access  public
    * @param   voteID     VoteID of the specified vote
    * @param   $startdate New date, when the vote starts
    * @param   $stopdate  New date, when the vote stops
    */
   function continueVote ($voteID, $startdate, $stopdate) {
      if ($startdate == NULL) $startdate = "NULL";
      if ($stopdate == NULL) $stopdate = "NULL";
     
      $sql = 
     "UPDATE".
     " vote ".
     "SET".
     " state     = 'active',".
     " chdate    = '".time()."',".
     " startdate = ".$startdate.",".
     " stopdate  = ".$stopdate." ".
     "WHERE".
     " vote_id   = '".$voteID."'";

      $this->db->query ($sql);
      if (!$this->db->affected_rows())
     $this->throwError (1, _("Vote konnte nicht fortgesetzt werden"), 
                __LINE__, __FILE__);
   }


   /**
    * Restarts a vote setting all answercounters to 0 again
    * @access  public
    * @param   voteID     VoteID of the specified vote
    * @param   $startdate New date, when the vote starts
    * @param   $stopdate  New date, when the vote stops
    * @param   $anonymous Is the vote anonymous or not
    */
   function restartVote ($voteID, $startdate, $stopdate, $anonymous) {
     if ($startdate == NULL) $startdate = "NULL";
     if ($stopdate == NULL) $stopdate = "NULL";
     
     $sql= "UPDATE vote SET state = '".VOTE_STATE_NEW."', ".
       "startdate=NULL, stopdate=NULL WHERE vote_id='".$voteID."'";
     
     $this->db->query($sql);
     if (!$this->db->affected_rows())
     $this->throwError (1, _("Vote konnte nicht neu gestartet werden"), 
                __LINE__, __FILE__);
     if(!$anonymous){
     $answers = array(); 
     $sql="SELECT answer_id from voteanswers WHERE ".
         "vote_id='".$voteID."'";
     $this->db->query($sql);
     while($this->db->next_record()){
         array_push ($answers, $this->db->f("answer_id"));
     }
     foreach ($answers as $answerID){
         $sql="DELETE FROM voteanswers_user WHERE ".
         "answer_id='".$answerID."'";
         $this->db->query ($sql);
     }
     }
     else {
         $sql="DELETE FROM vote_user ".
         "WHERE vote_id='".$voteID."'";
         $this->db->query($sql);
     /*
     if (!$this->db->affected_rows())
         $this->throwError (1, _("Antwortverknuepfungen (anonym) konnten nicht ".
                     "geloescht werden."), 
                __LINE__, __FILE__); 
     */
     }

     $sql="UPDATE voteanswers SET ".
       "counter = 0 WHERE vote_id='".$voteID."'";
     $this->db->query($sql);
     
   }
   

   /**
    * Sets the visibility of a vote to the new state
    * @access  public
    * @param   voteID     VoteID of the specified vote
    * @param   $state     New state
    */
   function setVisible ($voteID, $state) {
      $sql = 
    "UPDATE vote SET ".
    "state = '".$state."' WHERE vote_id = '".$voteID."'";
      $this->db->query ($sql);
      if (!$this->db->affected_rows())
    $this->throwError (1, _("DB: Vote nicht gefunden oder ist bereits sichtbar"));
   }

  


   /**
    * Starts all votes in dependency to their startdate
    */
# Die Funktion sollte noch umbenannt werden...[a]
   function startWaitingVotes () {
     $this->db->query("UPDATE".
              " vote ".
              "SET".
              " state='".VOTE_STATE_ACTIVE."' ".
              "WHERE".
              " state = '".VOTE_STATE_NEW."' AND".
              " startdate <= '".time()."'");

     $this->db->query("UPDATE".
              " vote ".
              "SET".
              " state='".VOTE_STATE_STOPINVIS."' ".
              "WHERE".
              " state = '".VOTE_STATE_ACTIVE."' AND".
              " stopdate < '".time()."' AND".
              " resultvisibility = '".VOTE_RESULTS_NEVER."'");


     $this->db->query("UPDATE".
              " vote ".
              "SET".
              " state='".VOTE_STATE_STOPVIS."' ".
              "WHERE".
              " state = '".VOTE_STATE_ACTIVE."' AND".
              " stopdate < '".time()."'");
     
     $this->db->query("UPDATE".
              " vote ".
              "SET".
              " state='".VOTE_STATE_STOPVIS."' ".
              "WHERE".
              " state = '".VOTE_STATE_ACTIVE."' AND".
              " (startdate+timespan) < '".time()."'");

     $this->db->query("UPDATE".
              " vote ".
              "SET".
              " state='".VOTE_STATE_STOPINVIS."' ".
              "WHERE".
              " state = '".VOTE_STATE_ACTIVE."' AND".
              " (startdate+timespan) < '".time()."' AND".
              " resultvisibility = '".VOTE_RESULTS_NEVER."'");
   }

   
   /**
    * Writes a new vote into the Database
    *
    * @access  public
    *
    * @param   string   $voteID         specified voteID
    * @param   string   $autorID        ID of the author
    * @param   string   $rangeID        specified rangeID
    * @param   string   $title          Title of the vote   
    * @param   string   $question       Question of the vote
    * @param   int      $state          state of the vote
    * @param   int      $starttime      Starttime of the vote
    * @param   int      $endtime        Endtime of the vote 
    * @param   int      $timeslice
    * @param   int      $creationTime   Creation time of thevote
    * @param   int      $changeTime     Time of last modifications
    * @param   int      $resultview     Visibility of the results
    * @param   int  $namesvisibility Visibility of the participants
    * @param   string   $multianswer    Single or multianswering
    * @param   string   $anonymous      Democratic or totalitarian vote
    * @param   array    $answerArray    The answers
    * @param   string   $co_visibility  correct answers visibility
    * @param   string   $type           
    *
    * @param   boolean  TRUE/FALSE      Success of Insertion
    */
   function writeVote ($voteID, $authorID, $rangeID, $title, $question,
               $state, $startTime, $endTime, $timespan, $mkdate,
               $chdate, $resultvisibility, $namesvisibility, $multiplechoice,
               $anonymous, $answerarray, $changeable,
               $co_visibility = NULL, $type) {
    
      // convert username to userID
      if ($id = get_userID($rangeID))
        $rangeID = $id;
          
      if ($startTime == NULL) $startTime="NULL";
      if ($endTime == NULL) $endTime="NULL";
      if ($timespan == NULL) $timespan="NULL";
      if ($co_visibility === NULL) $co_visibility = "NULL";       
      
      // escape strings for storing in the DB
      $title = addslashes($title);
      $question = addslashes($question);
      for ($index = 0; $index < count($answerarray); ++$index) {
          $answerarray[$index]['text'] = addslashes($answerarray[$index]['text']);
      }

      /* Doubleclick on save? ---------------------------------------------- */
      $sql = 
     "SELECT".
     " 1 ".
     "FROM".
     " voteanswers ".
     "WHERE".
     " answer_id = '".$answerarray[0]["answer_id"]."'".
     " AND".
     " vote_id != '".$voteID."'";

      $this->db->query ($sql);
      if ($this->db->nf ()) 
     return $this->throwError (1, _("Sie haben mehrmals auf 'Speichern' gedr&uuml;ckt. Das Voting bzw. der Test wurde bereits in die Datenbank geschrieben."));
      /* ------------------------------------------------------------------- */
      
       $this->db->query ("SELECT title from vote WHERE ".
             "vote_id='".$voteID."'");
       if ($this->db->nf() == 0) {
     
       $query = "INSERT INTO vote (".
         "vote_id, author_id, range_id,type , title, question, state, ".
         "startdate, stopdate, timespan, mkdate, chdate, ".
         "resultvisibility, namesvisibility, ".
         "multiplechoice, anonymous, changeable, co_visibility) ".
         "VALUES ('".$voteID."','".$authorID."', '".$rangeID."',".
         "'".$type."','".$title."', ".
         "'".$question."', '".$state."', ".$startTime.", ".
         $endTime.", ".$timespan.", '".$mkdate."', ".
         "'".$chdate."', '".$resultvisibility."', '".$namesvisibility."', ".
         "'".$multiplechoice."', '".$anonymous."', ".
         "'".$changeable."', ".$co_visibility.")";

       if (!$this->db->query ($query)) {
           $this->throwError (mysql_errno (), mysql_error (), 
                  __LINE__, __FILE__, ERROR_CRITICAL);
           return false;
       }
       // Antworten speichern

       for($index = 0 ; $index < count($answerarray); $index++){
           $correct = ($answerarray[$index]["correct"]) ? TRUE : FALSE; 
           $this->db->query("SELECT * from voteanswers ".
                "WHERE vote_id='".$voteID."'");
           if ($this->db->nf() == 0 + $index) { 
           // +index ... keine ahnung ob das so bleiben kann, 
           // aber es geht erstmal ;)  (michael)
           $query = "INSERT INTO voteanswers ".
             "(answer_id, vote_id, answer, position, ".
             "counter, correct) VALUES ".
             "( '".$answerarray[$index]["answer_id"]."', ".
             "'".$voteID."', ".
             "'".$answerarray[$index]["text"]."', ".
             "'".$index."', ".
             "'".$answerarray[$index]["counter"]."', ".
             "'".$correct."')";
           //  echo("voteanswers Query:".$query."<br>\n");   
           }
           $this->db->query ($query);
       }
       }
       else {
       // Vote existierte schon
       $query="UPDATE vote SET author_id='".$authorID."' , ".
           "range_id='".$rangeID."' , type ='".$type."' ,  ".
           "title='".$title."' , ".
           "question='".$question."'   , state='".$state."' , ".
           "startdate=".$startTime." , stopdate=".$endTime." , ".
           "timespan=".$timespan."   , mkdate='".$mkdate."', ".
           "chdate='".time()."' , ".
           "resultvisibility='".$resultvisibility."', ".
           "namesvisibility='".$namesvisibility."', ".
           "multiplechoice='".$multiplechoice."', ".
           "anonymous='".$anonymous."', ".
           "changeable='".$changeable."', ".
           "co_visibility=".$co_visibility." ".
           "WHERE vote_id='".$voteID."'";

       $this->db->query($query);
       $query="DELETE FROM voteanswers WHERE ".
         "vote_id='".$voteID."'";
       
       $this->db->query($query);
       for($index = 0 ; $index < count($answerarray); $index++){
         $correct = ($answerarray[$index]["correct"]) ? TRUE : FALSE; 
         $this->db->query("SELECT * from voteanswers ".
                  "WHERE vote_id='".$voteID."'");
         if ($this->db->nf() == 0 + $index) { 
           $query = "INSERT INTO voteanswers ".
         "(answer_id, vote_id, answer, position, ".
         "counter, correct) VALUES ".
         "( '".$answerarray[$index]["answer_id"]."', ".
         "'".$voteID."', ".
         "'".$answerarray[$index]["text"]."', ".
         "'".$index."', ".
         "'".$answerarray[$index]["counter"]."', ".
         "'".$correct."')";
         }
         $this->db->query ($query);
       }
       

       }
        return true;
   } //writeVote




   function participate ($voteID, $userID, $answerArray, 
             $isAnonymous, $changeable=NULL) {
     $update = 0;
     $sql="SELECT changeable FROM vote WHERE ".
       "vote_id ='".$voteID."'";
     $this->db->query ($sql);
     $this->db->next_record ();
     $changeable=$this->db->f("changeable");
   
     //Antwort aendern...
     if($changeable!=0){
       $oldanswers = array();
       $temp =array();
       $update=1;
       
       //lösche alte antworten!!!
       $sql="SELECT answer_id from voteanswers WHERE ".
     "vote_id ='".$voteID."'";

       $this->db->query($sql); 
       while ($this->db->next_record ()) {
     array_push ($temp, $this->db->f("answer_id")); 
       }
       foreach($temp as $a_id){
     $sql="SELECT answer_id from voteanswers_user WHERE ".
       "user_id ='".$userID."' AND ".
       "answer_id ='".$a_id."'";

     $this->db->query($sql); 
     while ($this->db->next_record ()) {
       array_push ($oldanswers, $this->db->f("answer_id")); 
     }
       }
       foreach ($oldanswers as $yeoldeanswer){
     $sql = "UPDATE voteanswers SET counter = counter - 1 ".
       "WHERE answer_id = '".$yeoldeanswer."' ";

     $this->db->query ($sql);
     $sql="DELETE FROM voteanswers_user WHERE ".
       "user_id = '".$userID."' AND ".
       "answer_id= '".$yeoldeanswer."'";

     $this->db->query ($sql);
       }
     }
     
     //normales Abstimmen
     if ($isAnonymous) {
       $sql_user = "INSERT INTO ".
     "vote_user ".
     "(vote_id, user_id, votedate) ".
     "VALUES ".
     "('".$voteID."', '".$userID."', '".time ()."')";
       $this->db->query ($sql_user);
     }
     else{
       foreach ($answerArray as $answer) {
     $sql_answer = "SELECT answer_id FROM voteanswers WHERE ".
       "vote_id = '".$voteID."' AND position = '".$answer."'";
     $this->db->query ($sql_answer);
     if (!$this->db->num_rows ()) {
       $this->throwError (1, _("DB: Keine gültige Antwort gewählt"), 
                  __LINE__, __FILE__);
       return;
     }
     $this->db->next_record();
#(m)     if($update!=0){
       $sql_user = "INSERT INTO voteanswers_user ".
         "(answer_id, user_id, votedate) VALUES ".
         "('".$this->db->f ("answer_id")."', '".$userID."', ".
         "'".time ()."')";
       $this->db->query ($sql_user);
#(m)     }       
       }
     }     
     // update counter for anonymous votes
     //    if ($isAnonymous) {
     foreach ($answerArray as $answer) {
       $sql_answer = "UPDATE voteanswers SET counter = counter + 1 ".
     "WHERE vote_id = '".$voteID."' ".
     "AND position = '".$answer."'";
       $this->db->query ($sql_answer);
     }

       // }
   }
   
   /**
    * Returns the users having voted with a specific answer
    * @access  public
    * @param   answer_id The id of the specific answer
    */
   function  getAssociatedUsers($answer_id ){
     $users = array();
     $sql="SELECT user_id from  voteanswers_user ".  
       "WHERE answer_id = '".$answer_id."'";
     $this->db->query ($sql);
     while($this->db->next_record()){
       array_push ($users, $this->db->f("user_id"));
     }
     return $users;
   }

   function getType ($voteID) {
     $sql = "SELECT type FROM vote ".
       "WHERE  vote_id = '".$voteID."'";
     $this->db->query ($sql);
     $this->db->next_record ();
     return $this->db->f ("type");
   }
   
   function search_range($search_str) {
      return search_range($search_str, true);
   }




   /**
    * Checks wheather a vote with a specified ID already exists (alex)
    * @access  public
    * @returns YES or NO
    */
   function isExistant2 () {
      $sql =
     "SELECT".
     " 1 ".
     "FROM".
     " vote ".
     "WHERE".
     " vote_id = '".$this->vote->getVoteID ()."'";
      $this->db->query ($sql);
      return ($this->db->nf ()) ? YES : NO;
   }

   /**
    * Checks whether a special user or anyone has participated
    * @access  public
    * @param   string  $userID  The unique user ID
    * @return  boolean True if user had already used his/her vote
    */
   function isAssociated2 ($userID = NULL) {
      if ($this->vote->isAnonymous ()) {
     $sql = 
        "SELECT".
        " 1 ".
        "FROM".
        " vote_user ".
        "WHERE ".
        " vote_id = '".$this->vote->getVoteID ()."'";
     if ($userID)
        $sql .= " AND user_id = '".$userID."'";
     $this->db->query ($sql);
     return ($this->db->nf()) ? YES : NO;
      } else {
     $sql = 
        "SELECT".
        " 1 ".
        "FROM".
        " voteanswers_user a, voteanswers b ".
        "WHERE".
        " b.answer_id = a.answer_id".
        "  AND".
        " b.vote_id = '".$this->vote->getVoteID ()."'";
     if ($userID)
        $sql .= " AND a.user_id = '".$userID."'";
     $this->db->query ($sql);
     return ($this->db->nf()) ? YES : NO;
      }
   }

   /**
    * Writes a new vote(not a test) into the database (neue Version von Alex)
    * z.Z. noch unbenutzt (12.Aug.2003), jedoch getestet und funktionstüchtig
    * @access  public
    */
   function writeVote2 () {
      $answerarray = $this->vote->getAnswers ();
      $startdate   = $this->vote->getStartdate ();
      $stopdate    = $this->vote->getSTopdate ();
      $timespan    = $this->vote->getTimespan ();

      if ($startdate === NULL) $startdate = "NULL";
      if ($stopdate  === NULL) $stopdate  = "NULL";
      if ($timespan  === NULL) $timespan  = "NULL";
      
      /* Doubleclick on save? ---------------------------------------------- */
      $sql = 
     "SELECT".
     " 1 ".
     "FROM".
     " voteanswers ".
     "WHERE".
     " answer_id = '".$answerarray[0]["answer_id"]."'".
     " AND".
     " vote_id != '".$voteID."'";

      $this->db->query ($sql);
      if ($this->db->nf ()) 
     return $this->throwError (1, _("Sie haben mehrmals auf 'Speichern' gedr&uuml;ckt. Das Voting bzw. der Test wurde bereits in die Datenbank geschrieben."));
      /* ------------------------------------------------------------------- */
      
      /* If vote does not exists in DB create it --------------------------- */
      if (!$this->isExistant2 ()) {
     $sql =
        "INSERT INTO".
        " vote (".
        "  vote_id,".
        "  author_id,".
        "  range_id,".
        "  title,".
        "  question,".
        "  state,".
        "  startdate,".
        "  stopdate,".
        "  timespan,".
        "  mkdate,".
        "  chdate,".
        "  resultvisibility,".
        "  namesvisibility,".
        "  multiplechoice,".
        "  anonymous,".
        "  changeable".
        " ) ".
        "VALUES (".
        " '".$this->vote->getVoteID ()."',".
        " '".$this->vote->getAuthorID ()."',".
        " '".$this->vote->getRangeID ()."',".
        " '".$this->vote->getTitle ()."',".
        " '".$this->vote->getQuestion ()."',".
        " '".$this->vote->getState ()."',".
        "  ".$startdate.",".
        "  ".$stopdate.",".
        "  ".$timespan.",".
        " '".$this->vote->getCreationdate ()."',".
        " '".$this->vote->getChangedate ()."',".
        " '".$this->vote->getResultvisibility ()."',".
        " '".$this->vote->getNamesvisibility ()."',".
        " '".$this->vote->isMultiplechoice ()."',".
        " '".$this->vote->isAnonymous ()."',".
        " '".$this->vote->isChangeable ()."'".
        ")";
     if (!$this->db->query ($sql))
        return $this->throwError (mysql_errno (), mysql_error (), 
                      __LINE__, __FILE__, ERROR_CRITICAL);
     
     /* Save answers --------------------------------------------------- */
     for ($index = 0 ; $index < count($answerarray); $index++) {
        $sql = 
           "INSERT INTO".
           " voteanswers (".
           "  answer_id,".
           "  vote_id,".
           "  answer,".
           "  position,".
           "  counter".
           " ) ".
           " VALUES (".
           " '".$answerarray[$index]["answer_id"]."',".
           " '".$this->vote->getVoteID ()."',".
           " '".$answerarray[$index]["text"]."', ".
           " '".$index."',".
           " '".$answerarray[$index]["counter"]."'".
           " )";      
     }
     /* ---------------------------------------------- end: save answers */
      }
      /* ------------------------------------------------- end: insert in DB */

      
      /* If vote already exists in DB update it ---------------------------- */
      else {
     $sql =
        "UPDATE".
        " vote ".
        "SET".
        " author_id        = '".$this->vote->getAuthorID ()."',".
        " range_id         = '".$this->vote->getRangeID ()."',".
        " title            = '".$this->vote->getTitle ()."',".
        " question         = '".$this->vote->getQuestion ()."',".
        " state            = '".$this->vote->getState ()."',".
        " startdate        =  ".$startdate.",".
        " stopdate         =  ".$stopdate.",".
        " timespan         =  ".$timespan.",".
        " mkdate           = '".$this->vote->getCreationdate ()."',".
        " chdate           = '".time()."',".
        " resultvisibility = '".$this->vote->getResultvisibility ()."',".
        " namesvisibility = '".$this->vote->getNamesvisibility ()."',".
        " multiplechoice   = '".$this->vote->isMultiplechoice ()."',".
        " anonymous        = '".$this->vote->isAnonymous ()."',".
        " changeable       = '".$this->vote->isChangeable ()."' ".
        "WHERE".
        " vote_id          = '".$this->vote->getVoteID ()."'";
      
     if (!$this->db->query ($sql))
        return $this->throwError (mysql_errno (), mysql_error (), 
                      __LINE__, __FILE__, ERROR_CRITICAL);

     /* Update old answers --------------------------------------------- */
     for ($index = 0 ; $index < count($answerarray); $index++) {
        $sql = 
           "UPDATE".
           " voteanswers ".
           "SET".
           "  vote_id   = '".$this->vote->getVoteID ()."',".
           "  answer    = '".$answerarray[$index]["text"]."', ".
           "  position  = '".$index."',".
           "  counter   = '".$answerarray[$index]["counter"]."'".
           "WHERE".
           "  answer_id = '".$answerarray[$index]["answer_id"]."'";
        
        if (!$this->db->query ($sql))
           return $this->throwError (mysql_errno (), mysql_error (), 
                     __LINE__, __FILE__, ERROR_CRITICAL);
     }
     /* -------------------------------------------- end: update answers */
      }
      /* ---------------------------------------------------- end: update DB */
   }


}
?>
