<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/**
 * The page to create/edit votes ... vote_edit.inc.php
 *
 * @author      Michael Cohrs <michael A7 cohrs D07 de>
 * @copyright   2003 Stud.IP-Project
 * @access      public
 * @module      vote_edit
 * @package     vote
 * @modulegroup vote_modules
 *
 */

// +--------------------------------------------------------------------------+
// This file is part of Stud.IP
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

/* -------------------------------------------------------- */
define( "MODE_CREATE", 0 );
define( "MODE_MODIFY", 1 );
define( "MODE_RESTRICTED", 2 );

define( "TITLE_HELPTEXT",
    _("Geben Sie hier einen Titel ein (optional)") );
define( "QUESTION_HELPTEXT",
    _("Geben Sie hier Ihre Frage ein") );
/* -------------------------------------------------------- */


include_once("lib/vote/vote.config.php");
include_once ("lib/vote/Vote.class.php");
include_once ("lib/vote/TestVote.class.php");
include_once ("lib/vote/view/vote_edit.lib.php");
include_once ("lib/vote/view/visual.inc.php");

global $auth, $perm;

/* If there is no rights to edit ------------------------------------------- */
$voteID = Request::option('voteID');
if (isset($voteID)) {
   $vote = new Vote ($voteID);
   $rangeID = $vote->getRangeID ();
}
if( !$rangeID ) $rangeID = Request::option("rangeID");

if ( ! ( $perm->have_studip_perm( "tutor", $rangeID ) || 
        $auth->auth["uname"] == $rangeID || (isDeputyEditAboutActivated() && 
        isDeputy($auth->auth["uid"], get_userid($rangeID), true)) ) ) {
    $reason = ( ! is_object($vote)
        ? _("Es macht wenig Sinn, die Editierseite aufzurufen, ohne die zu editierende Umfrage anzugeben...")
        : ( ! $vote->voteDB->isExistant($voteID)
            ? _("Angegebene Umfrage existiert nicht (mehr?) ...")
            : ($vote->x_instanceof() == INSTANCEOF_TEST
               ? sprintf(_("Sie haben keine Berechtigung den Test '%s' zu editieren."), $vote->getTitle())
               : sprintf(_("Sie haben keine Berechtigung die Umfrage '%s' zu editieren."), $vote->getTitle())
               )
            )
        );

    echo "<br>";
    parse_window( "error§" .
          _("Zugriff verweigert.").
          "<br><font size=-1 color=black>".
          $reason.
          "</font>",
          "§", _("Zugriff auf Editierseite verweigert"),
          "<br>&nbsp;"
          );

    page_close ();
    exit;
}
/* ------------------------------------------------------------------------- */

/*******************************************************************/
/******************** initialization *******************************/

// get and memorize the url, where we came from
$referer =  Request::get('referer');
if( ! $referer ) {
    //$referer = $_SERVER['HTTP_REFERER'];
    $referer = $_SESSION['vote_HTTP_REFERER_2']; // workaround for BIEST00082
    $referer = removeArgFromURL( $referer, "voteaction" );
    $referer = removeArgFromURL( $referer, "voteID" );
    $referer = removeArgFromURL( $referer, "showrangeID" );
    if( Request::option('rangeID') )
    $referer .= "&showrangeID=". Request::option('rangeID');
    elseif(  Request::option('showrangeID') )
    $referer .= "&showrangeID=".$showrangeID;
}

$voteID = Request::option('voteID');
$rangeID = Request::option('rangeID');
$type = Request::option('type');
if( empty($type) ) $type = "vote";
$makeACopy = Request::option('makecopy');

if ($type=="test") { $vote = new TestVote( $voteID ); }
else               { $vote = new Vote    ( $voteID ); }

if( $voteID && !$makeACopy ) {
    if( $vote->isInUse( $voteID ) )  // && ! $perm->have_perm ("root") )
    $pageMode = MODE_RESTRICTED;
    else
    $pageMode = MODE_MODIFY;
} else {
    $pageMode = MODE_CREATE;
}

$debug.="referer: $referer\n";
$debug.="pagemode: $pageMode\n";

$vote->finalize(); // reset ErrorHandler

$answers           =  Request::getArray('answers');
$title             = Request::get('title') != TITLE_HELPTEXT ? Request::get('title') : NULL;
$question          = Request::get('question') != QUESTION_HELPTEXT ? Request::get('question') : NULL;
$startMode         = Request::get('startMode');
$startDay          = Request::get('startDay');
$startMonth        = Request::get('startMonth');
$startYear         = Request::get('startYear');
$startHour         = Request::get('startHour');
$startMinute       = Request::get('startMinute');
if( $startDay )    $startDate = $vote->date2timestamp( $startDay, $startMonth, $startYear, $startHour, $startMinute );
$stopMode          = Request::get('stopMode');
$stopDay           = Request::get('stopDay');
$stopMonth         = Request::get('stopMonth');
$stopYear          = Request::get('stopYear');
$stopHour          = Request::get('stopHour');
$stopMinute        = Request::get('stopMinute');
if( $stopDay )     $stopDate = $vote->date2timestamp( $stopDay, $stopMonth, $stopYear, $stopHour, $stopMinute );
$timeSpan          = Request::get('timeSpan');
$multipleChoice    = Request::get('multipleChoice');
$resultVisibility  = Request::get('resultVisibility');
$co_visibility     = Request::get('co_visibility');
$anonymous         = Request::get('anonymous');
$namesVisibility   = Request::get('namesVisibility');
$changeable        = Request::get('changeable');

// undo damage done by magic quotes
if (isset($title)) {
    $title = stripslashes($title);
}
if (isset($question)) {
    $question = stripslashes($question);
}
if (is_array($answers)) {
    for ($index = 0; $index < count($answers); ++$index) {
        $answers[$index]['text'] = stripslashes($answers[$index]['text']);
    }
}

if( empty ($changeable) && !empty($title) )
     $changeable = NO;
if( empty ($namesVisibility) && !empty($title) )
     $namesVisibility = NO;

if( empty( $answers ) ) {
    $answers = $vote->getAnswers();
    if( $makeACopy ) {
    for( $i=0; $i<count($answers); $i++ ) {
        $answers[$i]['answer_id'] = md5(uniqid(rand()));
        $answers[$i]['counter']   = 0;
    }
    }
}

if( empty( $answers ) ) {
    if( !Request::submittedSome('addAnswersButton', 'saveButton', 'deleteAnswersButton')) {
    for( $i=0; $i<5; $i++ )
        $answers[$i] = makeNewAnswer();
    } else
    $answers = array();
}

if(!isset( $title ) )           { $title = $vote->getTitle(); if( $makeACopy ) $title .= _(" (Kopie)"); }
if(!isset( $question ) )          $question = $vote->getQuestion();
if(!isset( $startDay ) )          $startDate = $vote->getStartDate();
if(!isset( $stopDay ) )           $stopDate = $vote->getStopDate();
if(!isset( $timeSpan ) )          $timeSpan = $vote->getTimeSpan();
if(!isset( $multipleChoice ) )    $multipleChoice = $vote->isMultipleChoice();
if(!isset( $resultVisibility ) )  $resultVisibility = $vote->getResultVisibility();
if(!isset( $anonymous ) )         $anonymous = $vote->isAnonymous();
if(!isset( $namesVisibility ) )   $namesVisibility = $vote->getNamesVisibility();
if(!isset( $changeable ) )        $changeable = $vote->isChangeable();
if( $type == "test" ) {
    if( empty( $co_visibility ) ) $co_visibility = $vote->getCo_Visibility();
}
if( empty( $startMode ) ) {
    if( $startDate && $pageMode != MODE_CREATE )
    $startMode = "timeBased";
    elseif( $pageMode != MODE_CREATE )
    $startMode = "manual";
}
if( empty( $stopMode ) ) {
    if( $stopDate )
    $stopMode = "timeBased";
    elseif ( $timeSpan )
    $stopMode = "timeSpanBased";
    else
    $stopMode = "manual";
}
if( empty( $voteID ) )   $voteID = $vote->getVoteID();
if( empty( $rangeID ) )  $rangeID = $vote->getRangeID();

/*******************************************************************/
/******************** page commands ********************************/

if( $pageMode != MODE_RESTRICTED ) {
    $move_up = Request::optionArray('move_up');
    $move_down = Request::optionArray('move_down');
    $deleteAnswers = Request::optionArray('deleteAnswers');
    $newAnswerFields = Request::int('newAnswerFields');
    /**** Command: add Answers ****/
    if(Request::submitted('addAnswersButton')) {
    for( $i=0; $i<$newAnswerFields; $i++ )
        array_push( $answers, makeNewAnswer() );
    }

    /**** Command: move Answers ****/
    if( !empty( $move_up ) ) {
    for( $i=0; $i<count($answers); $i++ )
        if( isset( $move_up[$i] ) )
        moveAnswerUp( $answers, $i );
    }
    if( !empty( $move_down ) ) {
    for( $i=0; $i<count($answers); $i++ )
        if( isset( $move_down[$i] ) )
        moveAnswerDown( $answers, $i );
    }

    /**** Command: delete Answers ****/
    if(Request::submitted('deleteAnswersButton')) {
    for( $i=0; $i<count($answers); $i++ ) {
        if( $deleteAnswers[$i] == "on" ) {
        deleteAnswer( $i, $answers, $deleteAnswers );
        $i--;
        }
    }
    }
}


/**** Command: SAVE VOTE ****/
/* -------------------------------------------------------- */
if(Request::submitted('saveButton')) {
    $vote->errorArray = array();

    // special case: creator wants to modify things in a running vote,
    // but in the meantime the first user has voted...
    if( $pageMode == MODE_RESTRICTED && !empty( $_POST["question"]) )
    $vote->throwError(666, _("Inzwischen hat jemand abgestimmt! Sie k&ouml;nnen daher die meisten &Auml;nderungen nicht mehr vornehmen."), __LINE__, __FILE__);

    if( $title == NULL )
    if( $question != NULL )
        $title = my_substr( $question, 0, 50 );

    $vote->setTitle( $title );
    /* -------------------------------------------------------- */

    if( $pageMode != MODE_RESTRICTED ) {
    /* -------------------------------------------------------- */
    $vote->setQuestion ( $question );

    // remove any empty answers
    for( $i=0; $i<count($answers); $i++ ) {
        if( empty( $answers[$i]['text'] ) ) {
        deleteAnswer( $i, $answers, $deleteAnswers );
        $i--;
        }
    }
    /* -------------------------------------------------------- */
    $vote->setAnswers( $answers );
    /* -------------------------------------------------------- */
    switch( $startMode ) {
    case "manual":
        $vote->setStartDate( NULL );
        break;
    case "timeBased":
        $vote->setStartDate( $startDate );
        break;
    case "immediate":
        $vote->setStartDate( time()-1 );
        break;
    }
    /* -------------------------------------------------------- */
    $vote->setMultipleChoice( $multipleChoice );
    $vote->setAnonymous( $anonymous );
    if( $type == "test" ) $vote->setCo_Visibility( $co_visibility );
    /* -------------------------------------------------------- */
    if( $pageMode == MODE_CREATE ) {
        $vote->setRangeID( $rangeID );
        $vote->setAuthorID( $auth->auth["uid"] );
    }
    /* -------------------------------------------------------- */

    }

    // other values to be written in ANY pageMode...
    /* -------------------------------------------------------- */
    switch( $stopMode  ) {
    case "manual":
    $vote->setStopDate( NULL );
    $vote->setTimeSpan( NULL );
    break;
    case "timeBased":
    $vote->setTimeSpan( NULL );
    $vote->setStopDate( $stopDate );
    break;
    case "timeSpanBased":
    $vote->setStopDate( NULL );
    $vote->setTimeSpan( $timeSpan );
    break;
    }
    /* -------------------------------------------------------- */
    $vote->setResultVisibility( $resultVisibility );
    $vote->setNamesVisibility( $namesVisibility );
    if( isset($changeable) ) $vote->setChangeable( $changeable );
    /* -------------------------------------------------------- */
    // now all values are set...

    if( $pageMode != MODE_CREATE ) {
    if( $vote->getAuthorID() != $auth->auth["uid"] ) {
        // user's vote has been modified by admin/root
        // --> send notification sms
        $sms = new messaging();
            setTempLanguage($vote->getAuthorID());
        $smsText = sprintf( _("An Ihrer Umfrage '%s' wurden von dem/der Administrator/in %s Änderungen vorgenommen."),
                $vote->getTitle(),
                $vote->voteDB->getAuthorRealname($auth->auth["uid"]) );
            $sms->insert_message(mysql_escape_string( $smsText ), $vote->voteDB->getAuthorUsername($vote->getAuthorID()), "____%system%____", FALSE, FALSE, "1", FALSE, _("Systemnachricht:")." "._("Umfrage/Test geändert"));
            restoreLanguage();
    }
    }

    if( ! $vote->isError() ) {
    // this is now always done (see above)
    // if ($pageMode == MODE_RESTRICTED)
    //    $vote->addSlashesToText(); // prevent mysql from crashing...

    // save vote to database!
    $vote->executeWrite();

    if( ! $vote->isError() ) {

        // clear outbut buffer, as we are leaving the edit page
        ob_end_clean();
        $referer .= ( ! strpos( $referer, "?" ) ) ? "?" : "&";
        $referer .= "voteaction=".($pageMode == MODE_CREATE ? "created" : "saved");
        $referer .= "&voteID=".$vote->getVoteID();
        header( "Location: ". URLHelper::getUrl($referer) );
    }
    }
    else {
    // Errors occured!
    // They will be automatically printed by 'printFormStart'
    // and the form will be displayed again...
    }
}

/**** Command: cancel ****/
elseif(Request::submitted('cancelButton')) {

    // clear outbut buffer, as we are leaving the edit page.
    ob_end_clean();
    $referer .= ( ! strpos( $referer, "?" ) ) ? "?" : "&";
    $referer .= "voteID=".$vote->getVoteID();
    header( "Location: " . URLHelper::getUrl($referer) );
}


// end output buffering, we are still on the edit page...
ob_end_flush();

/*******************************************************************/
/************************ output calls *****************************/

printJSfunctions();

printFormStart( $voteID, $rangeID, $referer );

printTitleField( $title );

printQuestionField( $question );

printAnswerFields( $answers );

printRightRegion();

printRuntimeSettings( $startMode, $stopMode, $startDate, $stopDate, $timeSpan );

printProperties( $multipleChoice, $resultVisibility, $co_visibility, $anonymous, $namesVisibility, $changeable );

printFormEnd();


/*******************************************************************/
/******************** internal functions ***************************/

/**
 * creates a new answer
 *
 * @access  private
 * @return  array    the created answer as an array with keys 'answer_id' => new md5 id,
 *                                                            'text' => "",
 *                                                            'counter' => 0,
 *                                                            'correct' => NO
 */

function makeNewAnswer( ) {

    return array( 'answer_id' => md5(uniqid(rand())),
          'text'      => "",
          'counter'   => 0,
          'correct'   => NO
          );
}

/**
 * moves the answer at position 'pos' from the array 'answers' one field up
 *
 * @access  private
 * @param   array  &$answers    the answerarray
 * @param   int    $pos         the position of the answer to be moved
 *
 */

function moveAnswerUp( &$answers, $pos ) {

    if( $pos == 0 ) {
    $temp = $answers[0];
    unset( $answers[0] );

    // move all other answers a field up
    for( $i=0; $i<count($answers); $i++ ) {
        $answers[$i] = $answers[$i+1];
        unset( $answers[$i+1] );
    }
    $answers[count($answers)] = $temp;
    }

    else {
    $temp = $answers[$pos-1];
    $answers[$pos-1] = $answers[$pos];
    $answers[$pos] = $temp;
    }
    return;
}

/**
 * moves the answer at position 'pos' from the array 'answers' one field down
 *
 * @access  private
 * @param   array  &$answers    the answerarray
 * @param   int    $pos         the position of the answer to be moved
 *
 */

function moveAnswerDown( &$answers, $pos ) {

    $last = count($answers)-1;
    if( $pos == $last ) {
    $temp = $answers[$last];
    unset( $answers[$last] );

    // move all other answers a field down
    for( $i=$last; $i>0; $i-- ) {
        $answers[$i] = $answers[$i-1];
        unset( $answers[$i-1] );
    }
    $answers[0] = $temp;
    }

    else {
    $temp = $answers[$pos+1];
    $answers[$pos+1] = $answers[$pos];
    $answers[$pos] = $temp;
    }
    return;
}

/**
 * deletes the answer at position 'pos' from the array 'answers'
 * and modifies the array 'deleteAnswers' respectively
 *
 * @access  public
 * @param   array  &$answers        the answerarray
 * @param   array  &$deleteAnswers  the array containing the deleteCheckbox-bool-value for each answer
 * @param   int    $pos             the position of the answer to be deleted
 *
 */

function deleteAnswer( $pos, &$answers, &$deleteAnswers ) {

    unset( $answers[$pos] );
    if( is_array( $deleteAnswers ) )
    unset( $deleteAnswers[$pos] );

    for( $i=$pos; $i<count($answers); $i++ ) {

    if( empty( $answers[$i] ) ) {
        $answers[$i] = $answers[$i+1];
        unset( $answers[$i+1] );
        if( is_array( $deleteAnswers ) ) {
        $deleteAnswers[$i] = $deleteAnswers[$i+1];
        unset( $deleteAnswers[$i+1] );
        }
    }
    }
    return;
}


/**
 * deletes argument '&arg=value' from URL
 *
 * @access  public
 * @param   string $URL    the URL to be modified
 * @param   string $arg    the name of the argument
 * @returns string         the new URL
 *
 */

function removeArgFromURL( $URL, $arg ) {
    $pos = strpos( $URL, "$arg=" );

    if( $pos ) {
    if( $URL[$pos-1] == "&" ) {
        // If pos-1 is pointing to a '&', knock pos back one, so it is removed.
        $pos--;
    }
    $nMax = strlen( $URL );
    $nEndPos = strpos( $URL, "&", $pos+1 );

    if( $nEndPos === false ) {
        // $arg is on the end of the URL
        $URL = substr( $URL, 0, $pos );
    }
    else {
        // $arg is in the URL
        $URL = str_replace( substr( $URL, $pos, $nEndPos-$pos ), '', $URL );
    }
    }
    return $URL;
}
?>
