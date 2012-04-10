<?php
# Lifter002: DONE - no html
# Lifter003: TEST
# Lifter007: TEST - This class uses weird (and apparently undefined) constants 
#                   named YES and NO. Is this _really_ neccesssary since we
#                   have TRUE and FALSE?
# Lifter010: DONE - no html

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
require_once 'lib/classes/StudipObject.class.php';
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
class VoteDB extends StudipObject
{
    /**
     * Holds an instance of a vote
     * @access public
     * @var    Vote  $vote
     */
    public $vote;

    /**
     * Set the vote object to be used
     *
     * @param $mixed $vote Vote object
     */
    function setVote(&$vote)
    {
        $this->vote = &$vote;
    }

    public function __construct()
    {
        parent::StudipObject();
    }

    /**
     * Reads vote in specified rangeID with specified state
     *
     * @access private
     * @param  string  $rangeID    The specified rangeID
     * @param  mixed   $state      The specified state
     * @param  mixed   $author_id  An optional author id
     * @return array   All voteIDs with the specified state
     */
    private function getVotes($rangeID, $state, $author_id = null)
    {
        // convert username to userID
        if ($id = get_userID($rangeID)) {
            $rangeID = $id;
        }

        $additional = '';
        $parameters = array($state);

        if ($rangeID !== null) {
            $additional .= ' AND range_id = ?';
            $parameters[] = $rangeID;
        }
        if ($author_id !== null) {
            $additional .= ' AND author_id = ?';
            $parameters[] = $author_id;
        }

        $query = "SELECT type, vote_id AS voteID
                  FROM vote
                  WHERE state IN (?) {$additional}
                  ORDER BY chdate DESC";

        $statement = DBManager::get()->prepare($query);
        $statement->execute($parameters);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gets the new votes in the specified rangeID
     *
     * @access public
     * @param  string   $rangeID   The specified rangeID
     * @return array    All new voteID's
     */
    function getNewVotes ($rangeID)
    {
        $votes = $this->getVotes($rangeID, 'new');

        if (empty($votes)) {
            $this->throwError(1, _('Es wurden keine neuen Umfragen in der Datenbank gefunden'), __LINE__, __FILE__);
        }

        return $votes;
    }

    /**
     * Gets the active votes in the specified rangeID
     *
     * @access public
     * @param  string   $rangeID   The specified rangeID
     * @return array    All active voteID's
     */
    function getActiveVotes ($rangeID)
    {
        $votes = $this->getVotes($rangeID, 'active');

        if (empty($votes)) {
            $this->throwError(1, _('Es wurden keine aktiven Umfragen in der Datenbank gefunden'), __LINE__, __FILE__);
        }

        return $votes;
    }

    /**
     * Gets stopped votes in the specified rangeID that are visible to authors
     *
     * @access public
     * @param  string   $rangeID   The specified rangeID
     * @return array    All stopped and visible voteID's
     */
    function getStoppedVisibleVotes ($rangeID)
    {
        $votes = $this->getVotes($rangeID, 'stopvis');

        if (empty($votes)) {
            $this->throwError(1, _('Es wurden keine gestoppten sichtbaren Umfragen in der Datenbank gefunden'), __LINE__, __FILE__);
        }

        return $votes;
    }

    /**
     * Gets stopped votes in the specified rangeID
     *
     * @access public
     * @param  string   $rangeID   The specified rangeID
     * @return array    All voteID's of stopped votes
     */
    function getStoppedVotes ($rangeID)
    {
        $votes = $this->getVotes($rangeID, words('stopvis stopinvis'));

        if (empty($votes)) {
            $this->throwError(1, _('Es wurden keine gestoppten Umfragen in der Datenbank gefunden'), __LINE__, __FILE__);
        }

        return $votes;
    }

    /**
     * Gets new votes from a specified user
     *
     * @access  public
     * @param   string   $userID    The ID of the demanding user
     * @returns array    All new voteID's
     */
    function getNewUserVotes ($authorID, $rangeID = NULL)
    {
        $votes = $this->getVotes($rangeID, 'new', $authorID);

        if (empty($votes)) {
            $this->throwError(1, _('Es wurden keine passenden Umfragen in der Datenbank gefunden.'), __LINE__, __FILE__);
        }

        return $votes;
    }

    /**
     * Gets active votes from a specified user
     *
     * @access  public
     * @param   string   $userID    The ID of the demanding user
     * @returns array    All active voteID's
     */
    function getActiveUserVotes ($authorID, $rangeID = NULL)
    {
        $votes = $this->getVotes($rangeID, 'active', $authorID);

        if (empty($votes)) {
            $this->throwError(1, _('Es wurden keine passenden Umfragen in der Datenbank gefunden.'), __LINE__, __FILE__);
        }

        return $votes;
    }

    /**
     * Gets stopped votes from a specified user
     *
     * @access  public
     * @param   string   $userID    The ID of the demanding user
     * @returns array    All stopped voteID's
     */
    function getStoppedUserVotes ($authorID, $rangeID = NULL)
    {
        $votes = $this->getVotes($rangeID, words('stopvis stopinvis'), $authorID);

        if (empty($votes)) {
            $this->throwError(1, _('Es wurden keine passenden Umfragen in der Datenbank gefunden.'), __LINE__, __FILE__);
        }

        return $votes;
    }

    /**
     * Deletes a vote from the DB
     *
     * @access  public
     * @param   string   $voteID   The specified voteID
     */
    function removeVote($voteID)
    {
        $query = "SELECT answer_id FROM voteanswers WHERE vote_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($voteID));
        $result = $statement->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($result)) {
            $query = "DELETE FROM voteanswers_user WHERE answer_id IN (?)";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($result));
        }

        $query="DELETE FROM vote_user WHERE vote_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($voteID));

        $query="DELETE FROM voteanswers WHERE vote_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($voteID));

        $query="DELETE FROM vote WHERE vote_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($voteID));
   }

    /**
     * Reads a specified vote from the DB and returns an array of it's params.
     *
     * @access  public
     * @param   string   $voteID         specified voteID
     * @return  array    $votearray      Parameters of the Vote
     */
    function getVote ($voteID)
    {
        // TODO auf anonymous eingehen?

        $query = "SELECT vote_id, author_id, range_id, type, title, question, state,
                         startdate, stopdate, timespan, mkdate, chdate,
                         resultvisibility, namesvisibility, multiplechoice,
                         anonymous, changeable, co_visibility
                  FROM vote
                  WHERE vote_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($voteID));
        $vote = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$vote) {
            $this->throwError(1, _('Es wurden keine Umfragen mit der angegebenen ID gefunden.'), __LINE__, __FILE__);
            return;
        }

        $vote['isAssociated'] = $this->hasanyoneparticipated($voteID, $vote['anonymous']);

        // convert userID to username
        if ($name = get_username($vote['range_id'])) {
            $vote['range_id'] = $name;
        }

        // Read answers from db
        $query = "SELECT answer_id, answer AS `text`, counter, correct
                  FROM voteanswers
                  WHERE vote_id = ?
                  ORDER BY position";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($voteID));
        $vote['answerArray'] = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $vote;
    }

    /**
     * Checks wheather a vote with a specified ID already exists
     * @access public
     * @param  specified Vote ID
     * @return bool
     */
    function isExistant ($voteID)
    {
        $query = "SELECT 1 FROM vote WHERE vote_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($voteID));
        return $statement->fetchColumn();
    }

    /**
     * Checks whether a user has participated in a specified vote
     * @access  public
     * @param   string  $userID  The unique user ID
     * @return  boolean True if user had already used his/her vote
     */
    function isAssociated ($voteID, $userID)
    {
        $query = "SELECT anonymous FROM vote WHERE vote_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($vote_id));
        $anonymous = $statement->fetchColumn();

        if ($anonymous) {
            $query = "SELECT 1 FROM vote_user WHERE vote_id = ? AND user_id = ?";
        } else {
            $query = "SELECT 1
                      FROM voteanswers_user AS a
                      JOIN voteanswers AS b USING(answer_id)
                      WHERE a.user_id = ? AND b.vote_id = ?";
        }
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($voteID, $userID));
        return $statement->fetchColumn();
    }

    /**
     * Checks whether any user has participated in a specified vote
     * @access  public
     * @return  boolean True if user had already used his/her vote
     */
    function hasanyoneparticipated ($voteID, $anonymous = NO)
    {
        if ($anonymous == YES) {
            $query = "SELECT 1 FROM vote_user WHERE vote_id = ?";
        } else {
            $query = "SELECT 1
                      FROM voteanswers AS b
                      JOIN voteanswers_user AS a USING(answer_id)
                      WHERE b.vote_id = ?";
        }
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($voteID));
        return $statement->fetchColumn() ? YES : NO;
    }

    /**
     * Gets the number of users which used the vote
     * @access public
     * @return integer The number of users which used the vote
     */
    function getNumberUserVoted ()
    {
        if ($this->vote->isAnonymous()) {
            $query = "SELECT COUNT(DISTINCT user_id) FROM vote_user WHERE vote_id = ?";
        } else {
            $query = "SELECT COUNT(DISTINCT a.user_id)
                      FROM voteanswers_user AS a
                      JOIN voteanswers AS b USING(answer_id)
                      WHERE b.vote_id = ?";
        }
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->vote->getVoteID()));
        return $statement->fetchColumn();
    }

    /**
     * Gets the name of the range from the vote
     * @access  public
     * @return  string The name of the range
     */
    function getRangename ($rangeID)
    {
        $query = "SELECT username FROM auth_user_md5 WHERE ? IN (user_id, username)";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($rangeID));
        $rangename = $statement->fetchColumn();

        if (!$rangename) {
            return $rangeID == 'studip'
                 ? _('Systemweite Umfragen und Tests')
                 : getHeaderLine($rangeID);
        }

        return 'Profil: ' . $rangename;
    }

    /**
     * Gets the username from the owner of the vote
     * @access  public
     * @return  string The username
     */
    function getAuthorUsername ($authorID)
    {
        $query = "SELECT username FROM auth_user_md5 WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($authorID));
        $username = $statement->fetchColumn();

        if (!$username) {
            $username = 'nixgefunden';
            $this->throwError(1, _('Es wurde kein Benutzer mit der ID gefunden'), __LINE__, __FILE__);
        }

        return $username;
    }

    /**
     * Gets the real name from the owner of the vote
     * @access  public
     * @return  string The real name ("name surname")
     */
    function getAuthorRealname ($authorID)
    {
        $query = "SELECT CONCAT(Vorname, ' ', Nachname)
                  FROM auth_user_md5
                  WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($authorID));
        $name = $statement->fetchColumn() ?: 'Nix';

        return $name;
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
    function startVote ($voteID, $state, $startdate, $stopdate, $timespan)
    {
        $query = "UPDATE vote
                  SET state = ?, startdate = ?, stopdate = ?, timespan = ?, chdate = UNIX_TIMESTAMP()
                  WHERE vote_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $state, $startdate, $stopdate, $timespan, $voteID
        ));

        if (!$statement->rowCount()) {
            $this->throwError(1, _('Die Umfrage konnte nicht gestartet werden'), __LINE__, __FILE__);
        }
    }

    /**
     * Stopps an active vote by setting its state to 'stopped'.
     * @access  public
     * @param   voteID     VoteID of the specified vote
     * @param   $state     New state of the vote
     * @param   $stopdate  New date, when the vote stops
     */
    function stopVote ($voteID, $state, $stopdate)
    {
        if (empty($stopdate)) {
            $stopdate = null;
        }

        $query = "UPDATE vote
                  SET state = ?, stopdate = ?, timespan = NULL
                  WHERE vote_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($state, $stopdate, $voteID));

        if (!$statement->rowCount()) {
            $this->throwError(1, _('Vote konnte nicht gestartet werden'), __LINE__, __FILE__);
        }
    }

    /**
     * Continues a stopped vote by setting it's state to active
     * @access  public
     * @param   voteID     VoteID of the specified vote
     * @param   $startdate New date, when the vote starts
     * @param   $stopdate  New date, when the vote stops
     */
    function continueVote ($voteID, $startdate, $stopdate)
    {
        if (empty($startdate)) {
            $startdate = null;
        }
        if (empty($stopdate)) {
            $stopdate = null;
        }
        
        $query = "UPDATE vote
                  SET state = 'active', startdate = ?, stopdate = ?, chdate = UNIX_TIMESTAMP()
                  WHERE vote_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($startdate, $stopdate, $voteID));

        if (!$statement->rowCount()) {
            $this->throwError(1, _('Die Umfrage konnte nicht fortgesetzt werden'), __LINE__, __FILE__);
        }
    }

    /**
     * Restarts a vote setting all answercounters to 0 again
     * @access  public
     * @param   voteID     VoteID of the specified vote
     * @param   $startdate New date, when the vote starts
     * @param   $stopdate  New date, when the vote stops
     * @param   $anonymous Is the vote anonymous or not
     */
    function restartVote($voteID, $startdate, $stopdate, $anonymous)
    {
        $query = "UPDATE vote
                  SET state = ?, startdate = NULL, stopdate = NULL
                  WHERE vote_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(VOTE_STATE_NEW, $voteID));

        if (!$statement->rowCount()) {
            $this->throwError(1, _('Die Umfrage konnte nicht neu gestartet werden'), __LINE__, __FILE__);
        }

        if (!$anonymous) {
            $query = "SELECT answer_id FROM voteanswers WHERE vote_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($voteID));
            $answers = $statement->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($answers)) {
                $query = "DELETE FROM voteanswers_user WHERE answer_id IN (?)";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($answers));
            }
        } else {
            $query = "DELETE FROM vote_user WHERE vote_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($voteID));
        }

        $query = "UPDATE voteanswers SET counter = 0 WHERE vote_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($voteID));
    }

    /**
     * Sets the visibility of a vote to the new state
     * @access  public
     * @param   voteID     VoteID of the specified vote
     * @param   $state     New state
     */
    function setVisible ($voteID, $state)
    {
        $query = "UPDATE vote SET state = ? WHERE vote_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($state, $voteID));

        if (!$statement->rowCount()) {
            $this->throwError(1, _('Die Umfrage wurde nicht gefunden oder ist bereits sichtbar'));
        }
    }

    /**
     * Starts all votes in dependency to their startdate
     * @param   string   $rangeID   The specified rangeID
     */
    function startWaitingVotes ($rangeID)
    {
        $query = "UPDATE vote
                  SET state = ?
                  WHERE range_id = ? AND state = ? AND startdate <= UNIX_TIMESTAMP()";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(VOTE_STATE_ACTIVE, $rangeID, VOTE_STATE_NEW));

        $query = "UPDATE vote
                  SET state = IF (resultvisibility = ?, ?, ?)
                  WHERE range_id = ? AND state = ?
                    AND (stopdate < UNIX_TIMESTAMP() OR startdate + timespan < UNIX_TIMESTAMP())";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            VOTE_RESULTS_NEVER, VOTE_STATE_STOPINVIS, VOTE_STATE_STOPVIS,
            $rangeID, VOTE_STATE_ACTIVE
        ));
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
     * @return  boolean  TRUE/FALSE      Success of Insertion
     */
    function writeVote ($voteID, $authorID, $rangeID, $title, $question,
                        $state, $startTime, $endTime, $timespan, $mkdate,
                        $chdate, $resultvisibility, $namesvisibility, $multiplechoice,
                        $anonymous, $answerarray, $changeable,
                        $co_visibility = NULL, $type)
    {
        // convert username to userID
        if ($id = get_userID($rangeID)) {
            $rangeID = $id;
        }

        /* Doubleclick on save? ---------------------------------------------- */
        $query = "SELECT 1 FROM voteanswers WHERE answer_id = ? AND vote_id != ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($answerarray[0]['answer_id'], $voteID));

        if ($statement->rowCount()) {
            return $this->throwError(1, _('Sie haben mehrmals auf \'Speichern\' gedrückt. Die Umfrage bzw. der Test wurde bereits in die Datenbank geschrieben.'));
        }

        $query = "SELECT 1 FROM vote WHERE vote_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($voteID));
        $present = $statement->fetchColumn();

        if (!$present) {
            // Insert vote
            $query = "INSERT INTO vote (vote_id, author_id, range_id, type, title, question, state,
                                        startdate, stopdate, timespan, mkdate, chdate,
                                        resultvisibility, namesvisibility,
                                        multiplechoice, anonymous, changeable, co_visibility)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                $voteID, $authorID, $rangeID, $type, $title, $question, $state,
                $startTime, $endTime, $timespan, $mkdate, $chdate,
                $resultvisibility, $namesvisibility,
                $multiplechoice, $anonymous, $changeable, $co_visibility,
            ));

            if (!$statement->rowCount()) {
                $error = $statement->errorInfo();
                $this->throwError($error[1], $error[2], __LINE__, __FILE__, ERROR_CRITICAL);
                return false;
            }
        } else {
            // Update vote
            $query = "UPDATE vote SET author_id = ?, range_id = ?, type = ?, title = ?, question = ?, state = ?,
                                      startdate = ?, stopdate = ?, timespan = ?, mkdate = ?, chdate = ?,
                                      resultvisibility = ?, namesvisibility = ?,
                                      multiplechoice = ?, anonymous = ?, changeable = ?, co_visibility = ?
                      WHERE vote_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                $authorID, $rangeID, $type, $title, $question, $state,
                $startTime, $endTime, $timespan, $mkdate, $chdate,
                $resultvisibility, $namesvisibility,
                $multiplechoice, $anonymous, $changeable, $co_visibility,
                $voteID,
            ));

            // Erase all answer
            $query = "DELETE FROM voteanswers WHERE vote_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($voteID));
        }

        // Prepare "count answers for vote" statement
        $query = "SELECT COUNT(*) FROM voteanswers WHERE vote_id = ?";
        $select = DBManager::get()->prepare($query);

        // Prepare "insert answer" statement
        $query = "INSERT INTO voteanswers (answer_id, vote_id, answer, position, counter, correct)
                  VALUES (?, ?, ?, ?, ?, ?)";
        $insert = DBManager::get()->prepare($query);

        // Antworten speichern
        foreach ($answerarray as $index => $answer) {
            $select->execute(array($voteID));
            $num_answers = $select->fetchColumn();
            $select->closeCursor();

            if ($num_answers != $index) {
                // TODO I really don't know why this has to be here
                // If it is for error reporting means, this function should
                // actually report an error and not just silently purge all
                // remaining answers after one of them could not be stored.
                //
                // Former comment:
                //
                // "+index ... keine ahnung ob das so bleiben kann,
                //  aber es geht erstmal ;)  (michael)"
                break;
            }

            $insert->execute(array(
                $answer['answer_id'],
                $voteID,
                $answer['text'],
                $index,
                $answer['counter'],
                $answer['correct'] ? 1 : 0,
            ));
        }

        return true;
    }

    /**
     * Store a user's participation
     *
     * @param string   $voteID       Id of the vote
     * @param string   $userID       Id of the participating user
     * @param array    $answerArray  Array containing the answers
     * @param boolean  $isAnonymous  Indicates where participation is anonymous
     * @param boolean  $changeable   Indiciates whether the user may change his answers
     */
    function participate ($voteID, $userID, $answerArray, $isAnonymous, $changeable = NULL)
    {
        $update = 0;

        $query = "SELECT changeable FROM vote WHERE vote_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($voteID));
        $changeable = $statement->fetchColumn();

        //Antwort aendern...
        if ($changeable != 0) {
            $update = 1;

            //lösche alte antworten!!!
            $query = "SELECT answer_id FROM voteanswers WHERE vote_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($voteID));
            $temp = $statement->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($temp)) {
                $query = "SELECT answer_id
                          FROM voteanswers_user
                          WHERE user_id = ? AND answer_id IN (?)";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($userID, $temp));
                $oldanswers = $statement->fetchAll(PDO::FETCH_COLUMN);

                if (!empty($oldanswers)) {
                    $query = "UPDATE voteanswers
                              SET counter = counter - 1
                              WHERE answer_id IN (?)";
                    $statement = DBManager::get()->prepare($query);
                    $statement->execute(array($oldanswers));

                    $query = "DELETE FROM voteanswers_user
                              WHERE user_id = ? AND answer_id IN (?)";
                    $statement = DBManager::get()->prepare($query);
                    $statement->execute(array($userID, $oldanswers));
                }
            }

        }

        //normales Abstimmen
        if ($isAnonymous) {
            $query = "INSERT INTO vote_user (vote_id, user_id, votedate)
                      VALUES (?, ?, UNIX_TIMESTAMP())";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($voteID, $userID));
        } else {
            $query = "SELECT answer_id FROM voteanswers WHERE vote_id = ? AND position = ?";
            $select = DBManager::get()->prepare($query);

            $query = "INSERT INTO voteanswers_user (answer_id, user_id, votedate)
                       VALUES (?, ?, UNIX_TIMESTAMP())";
            $insert = DBManager::get()->prepare($query);

            foreach ($answerArray as $answer) {
                $select->execute(array($voteID, $answer));
                $answer_id = $select->fetchColumn();
                $select->closeCursor();

                if (!$answer_id) {
                    $this->throwError (1, _('DB: Keine gültige Antwort gewählt'), __LINE__, __FILE__);
                    return;
                }

#(m)     if($update!=0){
                $insert->execute(array($answer_id, $userID));
#(m)     }
            }
        }

        // update counter for anonymous votes
//        if ($isAnonymous) {
        if (!empty($answerArray)) { // Otherwise IN (?) will break for empty arrays
            $query = "UPDATE voteanswers
                      SET counter = counter + 1
                      WHERE vote_id = ? AND position IN (?)";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($voteID, $answerArray));
        }
//        }
   }

    /**
     * Returns the users having voted with a specific answer
     * @access  public
     * @param   answer_id The id of the specific answer
     */
    function getAssociatedUsers($answer_id)
    {
        $query = "SELECT user_id FROM voteanswers_user WHERE answer_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($answer_id));
        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Returns the type of a specific vote
     * @param  string  $voteID  Id of the vote
     * @return mixed  Either 'vote', 'test' or false if vote id is invalid
     */
    function getType ($voteID)
    {
        $query = "SELECT type FROM vote WHERE vote_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($voteID));
        return $statement->fetchColumn();
    }

    /**
     * Searches a range? (TODO)
     *
     * @param  string $search_str  String to search
     * @return mixed
     */
    function search_range($search_str)
    {
        return search_range($search_str, true);
    }

    /**
    * Checks wheather a vote with a specified ID already exists (alex)
    * @access  public
    * @return  Either YES or NO
    */
    function isExistant2 ()
    {
        return $this->isExistant($this->vote->getVoteID()) ? YES : NO;
    }

    /**
     * Checks whether a special user or anyone has participated
     * @access  public
     * @param   string  $userID  The unique user ID
     * @return  boolean True if user had already used his/her vote
     */
    function isAssociated2 ($userID = NULL)
    {
        return $this->isAssociated($this->vote->getVoteID(), $userID) ? YES : NO;
    }

    /**
     * Writes a new vote(not a test) into the database (neue Version von Alex)
     * z.Z. noch unbenutzt (12.Aug.2003), jedoch getestet und funktionstüchtig
     * @access  public
     */
    function writeVote2 ()
    {
        return $this->writeVote(
            $this->vote->getVoteID(),
            $this->vote->getAuthorID(),
            $this->vote->getRangeID (),
            $this->vote->getTitle(),
            $this->vote->getQuestion(),
            $this->vote->getState(),
            $this->vote->getStartdate(),
            $this->vote->getStopdate(),
            $this->vote->getTimespan(),
            $this->vote->getCreationdate(),
            $this->vote->getChangedate(),
            $this->vote->getResultvisibility(),
            $this->vote->getNamesvisibility(),
            $this->vote->isMultiplechoice(),
            $this->vote->isAnonymous(),
            $this->vote->getAnswers(),
            $this->vote->isChangeable(),
            null,
            'vote'
        );
    }
}
