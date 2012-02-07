<?php
# Lifter001: DONE
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
 * All needed HTML-code to visualize a vote or a test
 *
 * @author      Alexander Willner <mail@AlexanderWillner.de>,
 *              Michael Cohrs <michael A7 cohrs D07 de>
 * @copyright   2003 Stud.IP-Project (GNU General Public License)
 * @access      public
 * @module      vote_show_lib
 * @package     vote
 * @modulegroup vote_modules
 */

use Studip\Button, Studip\LinkButton;

# Include all required files ================================================ #
require_once ("lib/vote/view/visual.inc.php");
require_once ("lib/vote/vote.config.php");
# ====================================================== end: including files #



# Define public functions =================================================== #
/**
 * Create a HTML-image-link for the result
 * @param    String   $voteID   The unique vote id
 * @returns  String   The HTML-image-link
 */
function createVoteLink ($voteID)
{
   $html = "<img src=\"".VOTE_FILE_SHOW."?voteID=".$voteID."\">\n";
   return $html;
}

/**
 * Creates a HTML-form-header for a vote
 * @param    Object   $vote   The vote object
 * @returns  String   The HTML-form-header
 */
function createFormHeader(&$vote)
{
   $html = "";

   $html .=
      "<form action=\"";

   $unamelink="";
   if (!empty ($GLOBALS["username"]))
      $unamelink = "?username=".$GLOBALS["username"];

   $html .= URLHelper::getLink($unamelink."#openvote")."\" method=post>\n".
      " <input type=\"hidden\" name=\"voteformID\" ".
      "value=\"".$vote->getObjectID (). "\">\n".
      " <input type=\"hidden\" name=\"voteopenID\" ".
      "value=\"".$vote->getObjectID (). "\">\n".
      " <input type=\"hidden\" name=\"answerChanged\" ".
      "value=\"".(Request::submitted('changeAnswerButton') ||
          (isset($_POST["answerChanged"]) && !isset($_POST["answer"]))
          ? YES : NO). "\">\n";
   $html .= CSRFProtection::tokenTag();
   return $html;
}

/**
 * Creates a HTML-form-footer and the administrationbuttons
 * @param    Object   $vote    The vote object
 * @param    String   $userID  The unique user id
 * @param    String   $perm    The perm of the user
 * @param    String   $rangeID The rangeID
 * @returns  String   The HTML-form-footer
 */
function createFormFooter (&$vote, $userID, $perm, $rangeID) {
   $html = "";

   $haveFullPerm = $perm->have_studip_perm ("tutor", $vote->getRangeID()) ||
       $userID == $vote->getAuthorID ();

   $isPreview = ($_GET["previewResults"] || Request::submitted('previewButton')) &&
       ($vote->getResultvisibility() == VOTE_RESULTS_ALWAYS || $haveFullPerm);

   $isAssociated = $vote->voteDB->isAssociated ($vote->getObjectID(), $userID);
   $isStopped = $vote->isStopped();

   $revealNames = $_GET["revealNames"] && $vote->getObjectID() == $_GET["voteopenID"]
       && ! $vote->isAnonymous();

   $sortAnswers = $_GET["sortAnswers"] && $vote->getObjectID() == $_GET["voteopenID"];

   $changeAnswer = Request::submitted('changeAnswerButton') ||
       ($_POST["answerChanged"] && !isset($_POST["answer"]));

   $link = "?voteopenID=".$vote->getObjectID();

   $link .= ($_GET["openAllVotes"]) ? "&openAllVotes=".YES : "";

   $link .= ($_GET["openStoppedVotes"]) ? "&openStoppedVotes=".YES : "";
   $link .= ($_GET["showrangeID"]) ? "&showrangeID=".$_GET["showrangeID"] : "";
   $link .= ($isPreview) ? "&previewResults=".YES : "";
   $link .= ($GLOBALS["username"]) ? "&username=".$GLOBALS["username"] : "";

   /* Meta-information about the vote -------------------------------------- */
   $html .= createVoteInfo ($vote, $isAssociated);
   /* ---------------------------------------------------------------------- */

   $html .= "<br>\n";
   $html .= "<div align=\"center\">\n";

   /* Submitbutton --------------------------------------------------------- */
   if ( ! ($isAssociated || $isPreview || $isStopped)
    || ($changeAnswer && !$isPreview)) {
      $html .= Button::createAccept(_('Abschicken'),'voteButton', array('title' => _('Geben Sie hier Ihre Stimme ab!')));
   }
   /* ---------------------------------------------------------------------- */

   /* Viewbutton ----------------------------------------------------------- */
   if ( (! ($isAssociated || $isPreview || $isStopped)
     || ($changeAnswer && !$isPreview)) &&
    ($vote->getResultvisibility() == VOTE_RESULTS_ALWAYS || $haveFullPerm)
    ) {
      $html .= Button::create(_('Ergebnisse'), 'previewButton', array('title' => _('Hier können Sie sich die Ergebnisse im Voraus ansehen.')));
   }
   /* ---------------------------------------------------------------------- */

   /* Changebutton --------------------------------------------------------- */
   if ($vote->isChangeable() &&
       $isAssociated &&
       ! $vote->isStopped() &&
       ! Request::submitted('changeAnswerButton') &&
       ! $vote->isError()
       ) {
      $html .= Button::create(_('Antwort ändern'), 'changeAnswerButton', array('title' => _('Hier können Sie Ihre Antwort nochmal ändern.')));       
#      $html_extra = "<br>";
   }
   /* ---------------------------------------------------------------------- */

   /* Backbutton ----------------------------------------------------------- */
   elseif ($isPreview) {
      $html .= Button::create('<< ' . _('Zurück'), 'escapePreviewButton', array('title' => _('Zurück zum Abstimmen.'))); 
#      $html_extra = "<br>";
   }
   /* ---------------------------------------------------------------------- */

   /* Sortbutton ----------------------------------------------------------- */
   if ( count($vote->getAnswers()) > 2 &&
    ($isAssociated || $isPreview || $isStopped) &&
    $vote->isInUse() &&
    ! (($vote->getResultVisibility() == VOTE_RESULTS_AFTER_END ||
        $vote->getResultVisibility() == VOTE_RESULTS_NEVER) &&
       $vote->isActive()) )
       {
      $link_sort = $link."&sortAnswers=".($sortAnswers ? NO : YES);
      $link_sort .= "&revealNames=".($revealNames ? YES : NO);

      $link_sort .= ($vote->isStopped()) ? "#stoppedvotes" : "#openvote";

      $html .= LinkButton::create(($sortAnswers ? _('Nicht sortieren') : _('Sortieren')), URLHelper::getLink($link_sort), array('title' => ($sortAnswers
              ? _('Antworten wieder in Ihrer ursprünglichen Reihenfolge darstellen.')
              : _('Antworten nach Stimmenanzahl sortieren.'))));
   }
   /* ---------------------------------------------------------------------- */

   /* 'Show names'-button -------------------------------------------------- */
   if ( ! $vote->isAnonymous()
    && ($isAssociated || $isPreview || $isStopped)
    && $vote->isInUse()
    && ! (($vote->getResultVisibility() == VOTE_RESULTS_AFTER_END
           || $vote->getResultVisibility() == VOTE_RESULTS_NEVER)
          && $vote->isActive() && !$haveFullPerm)
    && ($haveFullPerm || $vote->getNamesVisibility())
    && (! $changeAnswer || $isPreview)
    )
       {
       $link_reveal = $link."&sortAnswers=".($_GET["sortAnswers"] ? YES : NO);

       if ($GLOBALS["voteopenID"] != $vote->getObjectID ())
      $link_reveal .= "&revealNames=".YES;
       else
      $link_reveal .= "&revealNames=".($_GET["revealNames"] ? NO : YES);

       $link_reveal .= ($vote->isStopped()) ? "#stoppedvotes" : "#openvote";

       if( $_GET["revealNames"] &&
       $GLOBALS["voteopenID"] == $vote->getObjectID ())
       $html .= LinkButton::create(_('Normale Ansicht'),URLHelper::getLink($link_reveal), array('title' => _('Zurück zur normalen Ansicht.')));
       else
       $html .= LinkButton::create(_('Namen zeigen'),URLHelper::getLink($link_reveal), array('title' => _('Zeigen, wer welche Antwort gewählt hat.')));
   }
   /* ---------------------------------------------------------------------- */

   /* Adminbuttons --------------------------------------------------------- */
   /*   if ($userID == $vote->getAuthorID () OR
       $perm->have_studip_perm ("root", $rangeID) OR
       $perm->have_studip_perm ("admin", $rangeID) OR
       $userID == $rangeID) {
   */
   if ($haveFullPerm) {
      if (!$vote->isStopped())
     $html .= LinkButton::create(_('Bearbeiten'), URLHelper::getLink(VOTE_FILE_ADMIN
                                    . "?page=edit&type=" . $vote->x_instanceof() 
                                    . "&voteID=".$vote->getObjectID()), 
                                    array('title' => ( $vote->x_instanceof() == INSTANCEOF_TEST
                                            ? _('Diesen Test bearbeiten.')
                                            : _('Diese Umfrage bearbeiten.'))));
      if (!$vote->isStopped())
      $html .= LinkButton::createCancel(_('Stop'), URLHelper::getLink(VOTE_FILE_ADMIN
                                    . "?page=overview&voteID=" . $vote->getObjectID ()
                                    . "&voteaction=stop&referer=1&showrangeID=".$vote->getRangeID()),
                                    array('title' => ($vote->x_instanceof() == INSTANCEOF_TEST
                                            ? _("Diesen Test stoppen.")
                                            : _("Diese Umfrage stoppen."))));

      $html .= LinkButton::create(_('Löschen'), URLHelper::getLink(VOTE_FILE_ADMIN."?page=overview&voteID="
                                    . $vote->getObjectID () . "&voteaction=delete_request&referer=1&showrangeID="
                                    . $vote->getRangeID()),
                                    array('title' => ($vote->x_instanceof() == INSTANCEOF_TEST 
                                            ? _('Diesen Test löschen.') 
                                            : _('Diese Umfrage löschen.'))));    
      $html .= "<br>";
   }
   /* ---------------------------------------------------------------------- */

   $html .= $html_extra;
   $html .= "</div>\n";
   $html .= "</form>\n";

   return $html;
}

/**
 * Creates a input HTML-form for the user who wants to vote
 * @param    object   $vote     The vote
 * @param    string   $userID   The unique user id
 * @returns  String   The HTML-text
 */
function createVoteForm (&$vote, $userID) {
   $html     = "";
   $answers  = $vote->getAnswers();
   $type     = $vote->isMultiplechoice() ? "checkbox" : "radio";

   /* Header --------------------------------------------------------------- */
   $html .= " <b>\n  <font size=\"-1\">\n   ";
   $html .= formatReady($vote->getQuestion());
   $html .= "\n  </font>\n </b>\n<br><br>\n";
   /* ---------------------------------------------------------------------- */

   /* Questions ------------------------------------------------------------ */
   $i = 0;
   $html .= "<table border=\"0\" cellspacing=\"0\" cellpadding=\"3\">\n";
   foreach ($answers as $key => $value) {
       $id = "vote_" . $vote->getVoteID() . "_answer_" . $key;
       $html .= " <tr valign=\"middle\">\n";
       $html .= "  <td>\n";
       $html .= "   <input ".
      "type=\"".$type."\" ".
      "name=\"answer[".$i."]\" ".
      "id=\"{$id}\" ".
      "value=\"".$key."\">\n";
       $html .= "  </td>\n";
       $html .= "  <td>\n";
       $html .= "   <label for=\"{$id}\">\n";
       $html .= "    <font size=-1>".formatReady($value["text"])."</font>\n";
       $html .= "   </label>\n";
       $html .= "  </td>\n";
       $html .= " </tr>\n";

       if ($vote->isMultipleChoice ())
       $i++;
   }
   $html .= "</table>\n";
   /* ---------------------------------------------------------------------- */
   $html .= "<br>";
   return $html;
}

/**
 * creates a wonderful arrow for opening/closing all votes at once :)
 *
 * @returns  string    the HTML-text
 */
function createOpeningOrClosingArrow($eval = FALSE)
{
   $html .= "<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\"".
      " width=\"100%\">\n";
   $html .= " <tr>\n";
   $html .= "  <td colspan=\"4\" class=\"steel1\" align=\"center\">\n";
   $html .= "   <a href=\"";

   /* If we are on a homepage ---------------------------------------------- */
   $arrowlink="";
   if (!empty ($GLOBALS["username"])) {
      $arrowlink = "?username=".$GLOBALS["username"];
      $isHomepage = YES;
   }
   /* ---------------------------------------------------------------------- */

// if called from evaluation
if($eval) {
    /* Show openAll-button -------------------------------------------------- */
   if (empty ($GLOBALS["openAllEvals"])) {
      $arrowlink .= ($isHomepage) ? "&" : "?";
      $arrowlink .= "openAllEvals=1&openStoppedEvals=1#votetop";
      $html.=URLHelper::getLink($arrowlink)."\">\n";
      $html .= Assets::img('icons/16/grey/arr_1down.png', tooltip(_("Alle Evaluationen öffnen!")));
   }
   /* ---------------------------------------------------------------------- */

   /* Show closeAll-buton -------------------------------------------------- */
   else {
      $html .= URLHelper::getLink($arrowlink)."\">\n";
      $html .= Assets::img('icons/16/grey/arr_1up.png', tooltip(_("Alle Evaluationen schließen!")));
   }

} else {
   /* Show openAll-button -------------------------------------------------- */
   if (empty ($GLOBALS["openAllVotes"])) {
      $arrowlink .= ($isHomepage) ? "&" : "?";
      $arrowlink .= "openAllVotes=1&openStoppedVotes=1#votetop";
      $html.=URLHelper::getLink($arrowlink)."\">\n";
      $html .= Assets::img('icons/16/grey/arr_1down.png', tooltip(_("Alle Umfragen und Tests öffnen!")));
   }
   /* ---------------------------------------------------------------------- */

   /* Show closeAll-buton -------------------------------------------------- */
   else {
      $html .= URLHelper::getLink($arrowlink)."\">\n";
      $html .= Assets::img('icons/16/grey/arr_1down.png', tooltip(_("Alle Umfragen und Tests schließen!")));
   }
   /* ---------------------------------------------------------------------- */
}
   $html .= "   </a>\n";
   $html .= "  </td>\n";
   $html .= " </tr>\n";
   $html .= "</table>\n";

   return $html;
}

/**
 * Creates the opened/closed headline for a vote
 * @param    object   $vote   The vote
 * @param    boolean  $open   whether the whole vote will be displayed or
 *                            the headline only
 * @returns  String   The HTML-text
 */
function createVoteHeadline(&$vote, $open, $openID, $evalDB = "", $isHomepage = NO)
{

   $maxTitleLength = ($isHomepage)
      ? VOTE_SHOW_MAXTITLELENGTH
      : VOTE_SHOW_MAXTITLELENGTH - 10;

   $title          = htmlReady (my_substr ($vote->getTitle (), 0, $maxTitleLength));
   $date           = $vote->getChangedate ();
   $authorName     = get_fullname ($vote->getAuthorID ());
   $authorUsername = get_username ($vote->getAuthorID ());
   if (empty ($evalDB))
     $number       = $vote->voteDB->getNumberUserVoted ();
   else
     $number       = $evalDB->getNumberOfVotes ($vote->getObjectID ());

   $openStr = ($open) ? "open" : "close";
   $isNew = (($date >= object_get_visit($vote->getObjectID(),($vote->x_instanceof() == INSTANCEOF_EVAL ? 'eval' : 'vote'),false,false)) && ($vote->getAuthorID() != $GLOBALS['auth']->auth["uid"]));
   $icon = ($vote->x_instanceof () == INSTANCEOF_TEST) ? VOTE_ICON_TEST :
      VOTE_ICON_VOTE;
   if ($vote->x_instanceof () == INSTANCEOF_EVAL)
     $icon = EVAL_PIC_ICON;
   $icon = Assets::img($icon, array('class' => 'text-bottom'));
   $voteInfo = $number." / <a href=\"".URLHelper::getLink("about.php?username=".$authorUsername)."\">\n"
      . "  <font size=\"-1\" color=\"#333399\">".htmlReady($authorName)."</font>\n"
      . " </a>\n"
      . " <font size=\"-1\">&nbsp;".date ("d.m.Y", $date)."</font>&nbsp;";

   if ($open) {
      $link = "?closeVotes=1";
      if (!empty ($GLOBALS["username"]))
     $link .= "&username=".$GLOBALS["username"];
      $link .= "#votetop";
   } else {
      $link = "?voteopenID=".$vote->getObjectID();
      if (!empty ($GLOBALS["username"]))
     $link .= "&username=".$GLOBALS["username"];
      $link .= "#openvote";
   }
   $link=URLHelper::getLink($link);

   $title = "<a href=\"$link\" class=\"tree\" >".$title."</a>";
   if ($vote->getObjectID() == $openID)
      $title .= "<a name=\"openvote\">&nbsp;</a>";

   return "<tr>"
      . printhead (0, 0, $link, $openStr, $isNew, $icon,
           $title, $voteInfo, $date, FALSE)
      . "</tr>";
}


/**
 * Creates the opened/closed headline for a old votes
 * @param    array    $stoppedVotes   The stopped vote
 * @returns  String   The HTML-text
 */
function createStoppedVotesHeadline($stoppedVotes, $openStoppedVotes, $stoppedEvals = NULL)
{
   $link = "?openStoppedVotes=" .
       ($openStoppedVotes ? NO : YES);
   if (!empty ($GLOBALS["username"]))
       $link .= "&username=".$GLOBALS["username"];
   $link .= "#stoppedvotes";
   $link=URLHelper::getLink($link);

   return "<tr>"
       . printhead (0, 0, $link, ($openStoppedVotes) ? "open" : "close",
            FALSE, Assets::img(VOTE_ICON_STOPPED, array('class' => 'text-bottom')),
            "<a href=\"".$link."\" class=\"tree\">" .
            _("Abgelaufene Umfragen") . "</a>".
            "<a name=\"stoppedvotes\">&nbsp;</a>",
            "<font size=\"-1\">(".(count( $stoppedVotes ) +
                       count ($stoppedEvals)).
            ")</font>", 0, FALSE)
       . "</tr>";
}


/**
 * Creates the line containing general information for a stopped vote.
 * @param    object   $vote       The vote
 * @returns  String   The HTML-text
 */
function createStoppedVoteHeader(&$vote, $evalDB = NULL)
{
    $date           = $vote->getChangedate ();
    $authorName     = get_fullname ($vote->getAuthorID ());
    $authorUsername = get_username ($vote->getAuthorID ());
    $title          = htmlReady ($vote->getTitle ());
    if (empty ($evalDB))
      $number       = $vote->voteDB->getNumberUserVoted ();
    else
      $number       = $evalDB->getNumberOfVotes ($vote->getObjectID ());

    $html .= "<table align=center width=\"92%\" cellpadding=1 cellspacing=0><tr>\n";
    $html .= "<td class=toolbar align=\"left\" valign=\"bottom\">\n";
    $html .= "<font size=-1><b>\n";
    $html .= "&nbsp;".$title;
    $html .= "</b></font>";
    $html .= "</td>";
    $html .= "<td class=toolbar align=\"right\" valign=\"bottom\">\n";
    $html .= "<a href=\"".URLHelper::getLink("about.php?username=".$authorUsername)."\">\n";
    $html .= "<font size=\"-1\" color=\"#333333\">".htmlReady($authorName)."</font>";
    $html .= "</a>\n";
    $html .= "<font size=\"-1\">&nbsp;".date ("d.m.Y", $date)."</font>&nbsp;";
    $html .= "</td>\n";
    $html .= "</tr>\n";
    $html .= "<tr>\n";
    $html .= "<td class=steelgraulight style=\"border:1px solid black; padding-top:8px; ".
              "padding-left:6px; padding-right:6px;\" colspan=2>\n";

    return $html;
}


/**
 * Creates the line containing general information for a stopped vote.
 * @param    object   $vote       The vote
 * @returns  String   The HTML-text
 */
function createStoppedVoteFooter()
{
    $html .= "</td>\n";
    $html .= "</tr></table>\n";
    $html .= "<br>\n";

    return $html;
}

/**
 * Creates a successmessage
 * @param    object   $vote       The vote
 * @param    bool     $firstTime  whether the report is created 1st time
 * @returns  String   The HTML-text
 */
function createSuccessReport (&$vote, $firstTime = YES, $changed = NO)
{
   global $perm, $auth;

   $html     = "";
   $stopdate = $vote->getRealStopdate();

   /* Show the results ----------------------------------------------------- */
   $haveFullPerm = $perm->have_studip_perm ("tutor", $vote->getRangeID()) ||
       $auth->auth["uid"] == $vote->getAuthorID ();

   $resVis = $haveFullPerm ? VOTE_RESULTS_ALWAYS : $vote->getResultvisibility();
   // brutal, ich weiss..

   switch ($resVis) {

     case VOTE_RESULTS_AFTER_VOTE:
     case VOTE_RESULTS_ALWAYS:
      $html .= createVoteResult ($vote);
      break;

     case VOTE_RESULTS_AFTER_END:
      $html .= "<font size=\"-1\">";
      if (!empty ($stopdate)) {
     $html .= sprintf(_("Die Ergebnisse werden Ihnen ab dem <b>%s</b>, %s Uhr angezeigt."),
              date ("d.m.Y", $stopdate),
              date ("H:i", $stopdate));
     $html .= "<br><br>\n";
      } else {
     $html .= ($vote->x_instanceof() == INSTANCEOF_TEST)
        ? _("Die Ergebnisse werden Ihnen angezeigt, sobald der Test vom Ersteller beendet worden ist.")
        : _("Die Ergebnisse werden Ihnen angezeigt, sobald die Umfrage vom Ersteller beendet worden ist.");
     $html .= "<br><br>\n";
      }
      $html .= "</font>";
      break;

     case VOTE_RESULTS_NEVER:
      $html .= "<font size=\"-1\">";
      $html .= _("Die Ergebnisse werden Ihnen nicht angezeigt, da dies vom Ersteller nicht erwünscht ist.");
      $html .= "<br><br>";
      $html .= "</font>";
      break;

     default:
      $html .= createReportMessage ("Unbekannter Modus!",
                    VOTE_ICON_ERROR,
                    VOTE_COLOR_ERROR);
   }
   /* ---------------------------------------------------------------------- */


   // show user-depending test-result message
   if ($vote->x_instanceof() == INSTANCEOF_TEST AND ($firstTime OR $changed)) {
      if ($vote->getCo_Visibility()) {
     $nrAll = count($_POST["answer"]);
     $nrCorrect = $vote->getNumberOfCorrectAnswers ($_POST["answer"]);
     $nrFalse = $nrAll - $nrCorrect;

     if ($vote->isMultipleChoice ()) {
        if ($nrCorrect == 0)
           $html .= createReportMessage
          (sprintf(_("Leider haben Sie keine der %s richtigen Antwort(en) gew&auml;hlt."),
               $vote->getNumberOfCorrectAnswers()),
           VOTE_ICON_ERROR, VOTE_COLOR_ERROR );

        else if ($nrFalse == 0 &&
             ($nrCorrect == $vote->getNumberOfCorrectAnswers ()))
           $html .= createReportMessage
          (sprintf(_("Gl&uuml;ckwunsch! Sie haben alle der %s richtigen Antwort(en) gew&auml;hlt."),
               $vote->getNumberOfCorrectAnswers()),
           VOTE_ICON_SUCCESS, VOTE_COLOR_SUCCESS );
        else
           if ($nrFalse > 0)
          $html .= createReportMessage
             (sprintf(_("Sie haben von %s richtigen Antwort(en) %s gefunden und dar&uuml;ber hinaus %s falsche Antwort(en) gegeben."),
                  $vote->getNumberOfCorrectAnswers(),
                  $nrCorrect,
                  $nrFalse),
              VOTE_ICON_ERROR, VOTE_COLOR_ERROR );
           else
          $html .= createReportMessage
             (sprintf(_("Sie haben von %s richtigen Antwort(en) %s gefunden."),
                  $vote->getNumberOfCorrectAnswers(),
                  $nrCorrect),
              VOTE_ICON_ERROR, VOTE_COLOR_ERROR );

     } else {

        if ( $nrCorrect > 0 )
           $html .=
          createReportMessage (_("Gl&uuml;ckwunsch! Ihre Antwort war richtig."),
                       VOTE_ICON_SUCCESS, VOTE_COLOR_SUCCESS );
        else
           $html .=
          createReportMessage (_("Ihre Antwort war leider falsch."),
                       VOTE_ICON_ERROR, VOTE_COLOR_ERROR );
     }
      } else {  // co_visibility disabled
     $html .=
        createReportMessage (_("Ihre Antwort wurde registriert."),
                 VOTE_ICON_SUCCESS, VOTE_COLOR_SUCCESS );
      }
   }
   elseif ($firstTime OR $changed) {
      $html .=
     createReportMessage (_("Ihre Stimme wurde gez&auml;hlt."),
                  VOTE_ICON_SUCCESS, VOTE_COLOR_SUCCESS );
   }

   return $html;
}


/**
 * Display the results in HTML
 * @param    object   $vote     The vote
 * @param    bool     $preview  if the user wants to look at the results
 *                              without having voted
 * @returns  String   The HTML-text
 */
function createVoteResult ($vote, $preview = NO)
{
   global $auth, $forum, $perm;

   $haveFullPerm = $perm->have_studip_perm ("tutor", $vote->getRangeID()) ||
       $auth->auth["uid"] == $vote->getAuthorID ();
   $sortAnswers = $_GET["sortAnswers"] &&
       ($vote->getObjectID() == $_GET["voteopenID"]);
   $revealNames = $_GET["revealNames"] &&
       ($vote->getObjectID() == $_GET["voteopenID"]) &&
       ($haveFullPerm || $vote->getNamesVisibility()) &&
       ! $vote->isAnonymous();

   if ($sortAnswers) $vote->sortVoteanswers ();

   $max         = $vote->getMaxAnswer ();
   $totalNumber = $vote->getNumberPersons ();
   $answers     = $vote->getAnswers ();
   $html        = "";
   /* ---------------------------------------------------------------------- */

   if ($revealNames) {
      $extraStyle = " style=\"padding-bottom:4px; padding-top:4px;\"";
      $leftCellWidth = "\"2%\"";
      $rightCellWidth = "\"98%\"";
   }
   else {
      $leftCellWidth = "\"25%\"";
      $rightCellWidth = "\"75%\"";
   }

   $co_visibility =
       ( $vote->x_instanceof() == INSTANCEOF_TEST ) &&
       ( $vote->getCo_Visibility() || $vote->isStopped() ) &&
       ( ! $preview );

   /* Show results only if someone had already used the vote --------------- */
   if ($totalNumber <= 0 || $max <= 0)
      return createReportMessage
      ( ( $vote->x_instanceof() == INSTANCEOF_TEST
          ? _("An diesem Test hat noch niemand teilgenommen.")
          : _("Bei dieser Umfrage hat noch niemand abgestimmt.") ),
        VOTE_ICON_INFO, VOTE_COLOR_INFO).
      "<br>\n";
   /* ---------------------------------------------------------------------- */

   $html .= " <b>\n  <font size=\"-1\">\n   ";
   $html .= formatReady($vote->getQuestion());
   $html .= "\n  </font>\n </b>\n<br><br>\n";
   //$html .= "<b>".formatReady($vote->getQuestion())."</b><br><br>";
   $html .= "<div align=\"center\">\n";
   $html .= " <table align=center border=\"0\" width=\"95%\" ".
      "cellpadding=3 cellspacing=0>\n";
#   $html .= " <tr>\n";

#   if( ! $revealNames ) {
#       $html .= "  <td align=right><font size=-1>";
#       if (count($answers) > 2) {
#      // display link for sorting
#      $html .= "  <a href=\"".$link_sort."\">";
#      $html .= (($sortAnswers) ? _("Sortierung aufheben") : _("Sortieren")) . "</a></font>\n";
#       }
#       $html .= "  </td>\n";
#   }
#   $html .= "  <td width=5>&nbsp;</td>\n";
#   $html .= "  <td colspan=2><b>".formatReady($vote->getQuestion())."</b></td>\n";
#   $html .= " </tr>\n";
#   $html .= " <tr><td height=3 colspan=3></td></tr>\n"; // some space

   /* ---------------------------------------------------------------------- */
   foreach ($answers as $answer) {
      $val = $answer["counter"];
      $percent = round ($val / $totalNumber * 100);
      // Prozentangaben müssen auf 100% gebracht werden. 99% bzw. 101%
      // sind möglich
      $html .= "<tr>\n";
      $html .= "<td".$extraStyle." width=".$leftCellWidth." align=right valign=middle nowrap>";
      $html .= "<b>".$percent."%</b>\n";
      if( ! $revealNames ) {
      // the bars
      if ($percent < 50){
          $bar = VOTE_BAR_MIDDLE . (floor($percent / 5) * 5) . '.png';
      } else {
          $bar = VOTE_BAR_MIDDLE . '50.png';
      }
      $html .= "&nbsp;<img src=\"".VOTE_BAR_LEFT."\" width=1 height=10 class=middle>";
      $html .= "<img src=\"". Assets::image_path($bar) ."\"";
      $html .= " width=\"" . ($val/$max * 100 + 1) . "\" height=10 class=middle>";
      $html .= "<img src=\"".VOTE_BAR_RIGHT."\" width=1 height=10 class=middle>";
      }
      $html .= "&nbsp;</td>\n";

      if ($co_visibility) {
     // show whether the answer is correct or wrong

     $html .= "<td".$extraStyle.">";
     $html .= ($answer["correct"])
        ? "<img src=\"".VOTE_ANSWER_CORRECT."\" ".tooltip(_("Richtige Antwort")).">"
        : "<img src=\"".VOTE_ANSWER_WRONG."\" ".tooltip(_("Falsche Antwort")).">";
     $html .= "</td>\n";
      }

      /* the cell containing the answer text ( + user list) ---------------- */
      $html .= "  <td".$extraStyle." width=".$rightCellWidth.">";
      $html .= "<font size=-1>".formatReady($answer["text"])."</font>\n";

      if( ! $revealNames ) {
      $html .= "  &nbsp;<font size=-2><i>(".$val." ".($val==1 ? _("Stimme") : _("Stimmen")).")</i></font>";
      }
      else {
      // show the list of users who chose current answer

     $html .= "<br>";
     // get userIDs
     $associatedUsers = $vote->voteDB->getAssociatedUsers( $answer["answer_id"] );
     $html .= "<font size=-1>(";
     if ( ! empty($associatedUsers)) {
         usort( $associatedUsers, "sortBySurname" );
         foreach ($associatedUsers as $uid) {
         $html .= "<a href=\"".URLHelper::getLink("about.php?username=".
             $vote->voteDB->getAuthorUsername($uid))."\">".
             $vote->voteDB->getAuthorRealname($uid)."</a>".
             ", ";
         }
         $html = rtrim($html, ", ");
     } else {
         $html .= " -- ";
     }
     $html .= ")</font>";
      }
      $html .= "  </td>\n";
      /* ------------------------------------------------------------------- */

      $html .= " </tr>\n";
   }

   $html .= " </table>\n";
   $html .= "</div>\n";
   $html .= "<br>";

   return $html;
}


/**
 * Displays the informations about a vote. E.g. used in front of the submit
 * buttons.
 * @param    Object $vote          The vote
 * @param    bool   $isAssociated  whether the current user has used the vote
 * @returns  String                The HTML-text
 */
function createVoteInfo (&$vote, $isAssociated = NO)
{
   $html     = "";
   $stopdate = $vote->getRealStopdate ();
   $number   = $vote->getNumberPersons ();

   $html .= "<div align=\"left\">\n";
   $html .= "<hr noshade=\"noshade\" size=\"1\">\n";
   $html .= "<font size=\"-1\">\n";

   /* multiple choice? ----------------------------------------------------- */
   if ($vote->isMultipleChoice()) {
       $html .= ($isAssociated || $vote->isStopped())
       ? _("Sie konnten mehrere Antworten ausw&auml;hlen.")
       : _("Sie k&ouml;nnen mehrere Antworten ausw&auml;hlen.");
       $html .= _(" Die Summe kann daher über 100% liegen.");
       $html .= " \n";
   }
   /* ---------------------------------------------------------------------- */

   /* Get number of votes -------------------------------------------------- */
   if ($vote->isStopped()) {
      if ($number != 1)
     $html .= sprintf (_("Es haben insgesamt <b>%s</b> Personen teilgenommen"), $number);
      else
      $html .= $isAssociated
          ? sprintf (_("Sie waren der/die einzige TeilnehmerIn"))
          : sprintf (_("Es hat insgesamt <b>eine</b> Person teilgenommen"));
   }
   else {
      if ($number != 1)
     $html .= sprintf (_("Es haben bisher <b>%s</b> Personen teilgenommen"), $number);
      else
     $html .= $isAssociated
         ? sprintf (_("Sie waren bisher der/die einzige TeilnehmerIn"))
         : sprintf (_("Es hat bisher insgesamt <b>eine</b> Person teilgenommen"));
   }
   /* ---------------------------------------------------------------------- */

   /* participated? -------------------------------------------------------- */
   // a: habe $number als Überprüfung hinzugefügt, da sonst bei abgelaufenen
   // Votes die Anzeige nicht funktioniert...muss überarbeitet werden!
   // m: lag wohl an parameter-defaultwert isAssociated=true in createFormFooter...
   if ($isAssociated && $number > 1)
       $html .= _(", Sie ebenfalls");
   /* ---------------------------------------------------------------------- */

   $html .= ".";

   $html .= "<br>\n";

   /* stopdate ------------------------------------------------------------- */
   if (!empty ($stopdate)) {
       if ($vote->isStopped()) {
       $html .= ($vote->x_instanceof() == INSTANCEOF_TEST)
           ? sprintf (_("Der Test wurde beendet am <b>%s</b> um <b>%s</b> Uhr."),
              date ("d.m.Y", $stopdate),
              date ("H:i", $stopdate))
           : sprintf (_("Die Umfrage wurde beendet am <b>%s</b> um <b>%s</b> Uhr."),
              date ("d.m.Y", $stopdate),
              date ("H:i", $stopdate));
       }
       else {
       if ($isAssociated) {
           if ($vote->isChangeable()) {
           $html .= sprintf (_("Sie k&ouml;nnen Ihre Antwort &auml;ndern bis zum <b>%s</b> um <b>%s</b> Uhr."),
                     date ("d.m.Y", $stopdate),
                     date ("H:i", $stopdate));
           }
           else {
           $html .= ($vote->x_instanceof() == INSTANCEOF_TEST)
               ? sprintf (_("Der Test wird voraussichtlich beendet am <b>%s</b> um <b>%s</b> Uhr."),
                  date ("d.m.Y", $stopdate),
                  date ("H:i", $stopdate))
               : sprintf (_("Die Umfrage wird voraussichtlich beendet am <b>%s</b> um <b>%s</b> Uhr."),
                  date ("d.m.Y", $stopdate),
                  date ("H:i", $stopdate));
           }
       }
       else {
           $html .= sprintf (_("Sie k&ouml;nnen abstimmen bis zum <b>%s</b> um <b>%s</b> Uhr."),
                 date ("d.m.Y", $stopdate),
                 date ("H:i", $stopdate));
       }
       }
   }
   else {
       $html .= ($vote->x_instanceof() == INSTANCEOF_TEST)
       ? _("Der Endzeitpunkt dieses Tests steht noch nicht fest.")
       : _("Der Endzeitpunkt dieser Umfrage steht noch nicht fest.");
   }
   $html .= " \n";
   /* ---------------------------------------------------------------------- */

   /* Is anonymous --------------------------------------------------------- */
   if ($vote->isStopped() || $isAssociated)
       $html .= ($vote->isAnonymous())
       ? _("Die Teilnahme war anonym.")
       : _("Die Teilnahme war <b>nicht</b> anonym.");
   else
       $html .= ($vote->isAnonymous())
       ? _("Die Teilnahme ist anonym.")
       : _("Die Teilnahme ist <b>nicht</b> anonym.");

   $html .= "<br>\n";
   /* ---------------------------------------------------------------------- */


   $html .= " </font>\n";
   $html .= "</div>\n";

   return $html;
}

# ===================================================== end: public functions #


/* ------------------------------------------------------------------------- */
define ("VOTE_MESSAGE_EMPTY",
    "<p class=\"info\">\n".
    "  "._("Es sind keine aktuellen Umfragen vorhanden. Um neue Umfragen zu erstellen, klicken Sie rechts auf die Zahnräder.")."\n".
    "</p>\n");
/* ------------------------------------------------------------------------- */




function sortBySurname ($a, $b)
{
    return strcmp( strtolower (get_nachname ($a)), strtolower (get_nachname ($b)));
}
