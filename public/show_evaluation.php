<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
 * the evaluation participation page :)
 *
 * @author      mcohrs <michael A7 cohrs D07 de>
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @copyright   2004 Stud.IP-Project
 * @access      public
 * @package     evaluation
 * @modulegroup evaluation_modules
 *
 */

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// Copyright (C) 2001-2004 Stud.IP
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+


//TODO: auf TemplateFactory umstellen

# PHP-LIB: open session ===================================================== #

require '../lib/bootstrap.php';

page_open (array ("sess" => "Seminar_Session",
          "auth" => "Seminar_Auth",
          "perm" => "Seminar_Perm",
          "user" => "Seminar_User"));
$auth->login_if ($auth->auth["uid"] == "nobody");
$perm->check ("autor");
# ============================================================== end: PHP-LIB #

# Include all required files ================================================ #
require_once ('lib/evaluation/evaluation.config.php');
require_once ('lib/seminar_open.php');
require_once ('lib/visual.inc.php');

require_once( EVAL_FILE_EVAL );
require_once( EVAL_FILE_EVALDB );
require_once( EVAL_FILE_SHOW_TREEVIEW );
require_once( EVAL_FILE_EVALTREE );

require_once( EVAL_LIB_COMMON );
require_once( EVAL_LIB_SHOW );
# ====================================================== end: including files #


/* Create objects ---------------------------------------------------------- */
$db  = new EvaluationDB();
$lib = new EvalShow();
/* ------------------------------------------------------------ end: objects */

#error_reporting( E_ALL & ~E_NOTICE );

/* Set variables ----------------------------------------------------------- */
$rangeID = ($rangeID) ? $rangeID : $SessSemName[1];
if (empty ($rangeID)) {
    $rangeID = $user->id; }

$evalID = $_GET["evalID"];

$tree = new EvaluationTreeShowUser( $evalID );

#$eval = new Evaluation( $evalID, NULL, EVAL_LOAD_ALL_CHILDREN );
$eval = $tree->tree->eval;
$evalDB = new EvaluationDB();

#$isPreview = $_REQUEST["isPreview"] ? YES : (($eval->getStartdate() == NULL ? YES : NO));
$isPreview = $_REQUEST["isPreview"] ? YES : NO;

$votedEarlier = $eval->hasVoted( $auth->auth["uid"] ) && $isPreview == NO;
$votedNow = Request::submitted('voteButton') && $votedEarlier == NO;

if ( $eval->isAnonymous() )
   $userID = StudipObject::createNewID ();
else
   $userID = $auth->auth["uid"];
/* ---------------------------------------------------------- end: variables */

$br = new HTMpty( "br" );

/* Surrounding Form -------------------------------------------------------- */
$form = new HTM( "form" );
$form->attr( "action", $PHP_SELF."?evalID=".$evalID );
$form->attr( "method", "post" );
$form->html(CSRFProtection::tokenTag());

$titlebar = EvalCommon::createTitle( _("Stud.IP Online-Evaluation"), Assets::image_path('icons/16/white/test.png') );
$form->cont( $titlebar );

/* Surrounding Table ------------------------------------------------------- */
$table = new HTM( "table" );
$table->attr( "border","0" );
$table->attr( "align", "center" );
$table->attr( "cellspacing", "0" );
$table->attr( "cellpadding", "3" );
$table->attr( "width", "100%" );
$table->attr( "class", "steel1" );

/* count mandatory items */
$mandatories = checkMandatoryItems( $eval );

/* ------------------------------------------------------------------------- */
if( $votedNow ) {
    if( ! ( is_array($_POST["answers"]) ||
        /* clicked no answer */
        (is_array($_POST["freetexts"]) && implode("", $_POST["freetexts"]) != "")
        /* typed no freetext */
        )
    ) {

    $eval->throwError( 1, _("Sie haben keine Antworten gewählt.") );
    $votedNow = NO;

    }

    /* check if mandatory answers are missing */
    if( count($mandatories) > 0 ) {
    $eval->throwError( 1, sprintf(_("Sie haben %s erforderliche Fragen nicht beantwortet. Diese wurden gesondert markiert."),
                      count($mandatories)) );
    $votedNow = NO;
    }
}

if( $votedNow ) {
    /* the vote was OK */

    /* process the user's selected answers --------------------------------- */
    if( is_array($_POST["answers"]) ) {
    foreach( $_POST["answers"] as $question_id => $answer ) {
        if( is_array($answer) )
        /* multiple choice question */
        foreach( $answer as $nr => $answer_id )
            voteFor( $answer_id );
        else
        /* answer = answer_id */
        voteFor( $answer );
    }
    }

    /* process the user's typed-in answers --------------------------------- */
    if( is_array($_POST["freetexts"]) ) {
    foreach( $_POST["freetexts"] as $question_id => $text ) {
        if( trim($text) != '' ) {
        $question = new EvaluationQuestion( $question_id );
        $answer = new EvaluationAnswer();
        $answer->setText( $text );
        $answer->setRows( 1 );
        $answer->vote( $GLOBALS["userID"] );
        $question->addChild( $answer );
        $question->save();
        $debug .= "added answer text <b>".$answer->getText().
            "</b> for question <b>".$question->getText()."</b>\n";
        }
    }
    }

    /* connect user with eval */
    $evalDB->connectWithUser( $evalID, $auth->auth["uid"] );

    /* header ------ */
    $table->cont( $lib->createEvaluationHeader( $eval, $votedNow, $votedEarlier ) );

} elseif( $votedEarlier ) {
    /* header ------ */
    $table->cont( $lib->createEvaluationHeader( $eval, $votedNow, $votedEarlier ) );

} else {
    /* header ------ */
    $table->cont( $lib->createEvaluationHeader( $eval, $votedNow, $votedEarlier ) );

    /* the whole evaluation ------ */
    $table->cont( $lib->createEvaluation( $tree ) );
}

/* footer ------ */
$table->cont( $lib->createEvaluationFooter( $eval, $votedNow || $votedEarlier, $isPreview ) );

$form->cont( $table );

PageLayout::disableHeader();

/* Ausgabe erzeugen---------------------------------------------------------- */
//Header
include ('lib/include/html_head.inc.php');
include ('lib/include/header.php');

//Content (TODO: besser mit TemplateFactory)
echo $form->createContent();

//Footer
include ('lib/include/html_end.inc.php');
page_close();


 /**
  * checkMandatoryItems:
  * put IDs of mandatory questions into global array $mandatories
  *  (or, if the user has voted, the IDs of the mandatory questions, which he did not answer to)
  *
  * @param object  the Evaluation object (when called externally).
  */
 function checkMandatoryItems( $item )
 {
     global $mandatories;

     if( $children = $item->getChildren() )
     {
        foreach( $children as $child )
        {
         checkMandatoryItems( $child );
        }
     }

     if( $item->x_instanceof() == INSTANCEOF_EVALQUESTION )
     {
        $group = $item->getParentObject();

        if( $group->isMandatory() &&
         ( ! is_array($_POST["answers"]) ||
           ( is_array($_POST["answers"]) &&
         ! in_array($item->getObjectID(), array_keys($_POST["answers"])) )
           ) &&
         trim($_POST["freetexts"][$item->getObjectID()]) == ''
         )
         {
             $mandatories[] = $item->getObjectID();
         }
     }
     return $mandatories;

 }


 /**
  * vote for an answer of given ID
  * @param string  the ID.
  */
 function voteFor( $answer_id )
 {
    global $debug;
    global $userID;

    $answer = new EvaluationAnswer( $answer_id );
    $answer->vote($userID);

    $answer->save();

    $debug .= "voted for answer <b>".$answer->getText()."</b> (".
    $answer->getObjectID().")\n";
}

?>
