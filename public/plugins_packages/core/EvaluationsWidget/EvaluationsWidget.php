<?php
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/*
 * termine.php - Termine controller
 * @todo create templates for changed functions of vote_show.inc.php
 *
*/
global $RELATIVE_PATH_CALENDAR, $template_factory;

include_once("lib/vote/vote.config.php");
include_once ("lib/vote/Vote.class.php");
include_once ("lib/vote/TestVote.class.php");

require_once ("lib/vote/view/visual.inc.php");
require_once ("lib/vote/view/vote_show.lib.php");
require_once ("lib/vote/VoteDB.class.php");
require_once ("lib/vote/Vote.class.php");
require_once ("lib/vote/TestVote.class.php");
require_once ("lib/evaluation/evaluation.config.php");
require_once (EVAL_FILE_OBJECTDB);
require_once (EVAL_FILE_EVAL);
require_once (EVAL_LIB_SHOW);
use Studip\Button, Studip\LinkButton;
class EvaluationsWidget extends StudIPPlugin implements PortalPlugin {



    function __construct() {
        parent::__construct();
        PageLayout::addScript($this->getPluginUrl() . '/js/evaluation.js');
    }

    public function getPortalTemplate() {
        global $perm, $auth;
        //if (get_config('VOTE_ENABLE')) {
        $factory = new Flexi_TemplateFactory(dirname(__FILE__).'/templates');
        $template = $factory->open('eval_all-list');

        $template->evalItemArray = $this->show_evals('studip', $auth->auth['uid'], $perm);
        $template->voteItemArray = $this->show_votes('studip', $auth->auth['uid'], $perm);
        $stoppedVoteItemArray = $this->show_stopped_votes('studip', $perm);
        $stoppedEvalItemArray = $this->show_stopped_evals('studip', $perm);
        if(is_array($stoppedVoteItemArray) || is_array($stoppedEvalItemArray))
            $template->stopped = true;
        $template->plugin = $this;

        $template->title = _('Umfragen');
        $template->icon_url = 'icons/16/white/vote.png';
        $template->admin_url = URLHelper::getURL('admin_vote.php', array(
            'page' => 'overview',
            'cid' => 'studip',
            'new_inst' => 'TRUE',
            'view' => 'vote_inst'
        ));


        $template->admin_title = _('Umfragen bearbeiten');

        return $template;
        // }
    }

    function getHeaderOptions() {
        global $perm;
        if (($perm->have_studip_perm ("tutor", $rangeID) && $perm->have_perm('autor')) OR   // allow creation of evaluations for global autors as well
                get_username($userID) == $rangeID ||
                ($isHomepage && isDeputyEditAboutActivated() && isDeputy($userID, get_userid($rangeID), true))) {
            $options = array();
            $options[] = array('url' => VOTE_FILE_ADMIN."?page=overview&cid=".$rangeID.
                            (get_username($userID) == $rangeID ? '' :
                            ($GLOBALS['SessSemName']["class"]=="sem"
                            ? "&new_sem=TRUE&view=vote_sem"
                            : "&new_inst=TRUE&view=vote_inst")),
                    'img' => 'icons/16/blue/admin.png',
                    'tooltip' =>_('Umfragen bearbeiten'));
            return $options;
        }else {
            $options = array();
            return $options;
        }
    }
    function getURL() {
    }

    function getRange() {
        global $user;
        return $user->id;
    }

    function show_votes ($rangeID, $userID, $perm, $isHomepage = NO) {
        global $perm;
        /* Set variables -------------------------------------------------------- */
        $voteDB  = new VoteDB ();
        if ($perm->have_studip_perm ("tutor", $rangeID) ||
                get_username($userID) == $rangeID ||
                (isDeputyEditAboutActivated() &&
                        isDeputy($userID, get_userid($rangeID), true)))
            $haveFullPerm = true;
        else
            $haveFullPerm = false;
        /* ---------------------------------------------------------------------- */

        /* Start waiting votes -------------------------------------------------- */
        $voteDB->startWaitingVotes ($rangeID);
        $activeVotes  = $voteDB->getActiveVotes ($rangeID);
        $stoppedVotes = $voteDB->getStoppedVisibleVotes ($rangeID);
        /* ---------------------------------------------------------------------- */
        if (!($rangeID2 = get_userid($rangeID)))
            $rangeID2 = $rangeID;
        if (empty ($activeVotes) &&
                empty ($stoppedVotes) &&
                !($perm->have_studip_perm ("tutor", $rangeID) ||
                        get_username($userID) == $rangeID ||
                        (isHomepage && isDeputyEditAboutActivated() && isDeputy($userID, get_userid($rangeID), true)))
        ) {
            $voteDB->finalize ();
        }

        /* Show all active Votes ------------------------------------------------ */
        $activeVoteItemArray = array();
        foreach ($activeVotes as $tmpVote) {
            $voteID = $tmpVote["voteID"];
            if ($tmpVote["type"] == INSTANCEOF_TEST)
                $vote = new TestVote ($voteID);
            else
                $vote = new Vote ($voteID);

            $haveFullPerm = $perm->have_studip_perm ("tutor", $vote->getRangeID()) ||
                    $userID == $vote->getAuthorID();
            $previewResults = ($vote->getResultvisibility() == VOTE_RESULTS_ALWAYS || $haveFullPerm);

            //list template
            $maxTitleLength = ($isHomepage)
                    ? VOTE_SHOW_MAXTITLELENGTH
                    : VOTE_SHOW_MAXTITLELENGTH - 10;

            $title          = htmlReady (my_substr ($vote->getTitle (), 0, $maxTitleLength));
            $date           = $vote->getChangedate ();
            $authorName     = get_fullname ($vote->getAuthorID ());
            $authorUsername = get_username ($vote->getAuthorID ());
            $number       = $vote->voteDB->getNumberUserVoted ();

            $isNew = (($date >= object_get_visit($vote->getObjectID(),($vote->x_instanceof() == INSTANCEOF_EVAL ? 'eval' : 'vote'),false,false)) && ($vote->getAuthorID() != $GLOBALS['auth']->auth["uid"]));
            $icon = ($vote->x_instanceof () == INSTANCEOF_TEST) ? VOTE_ICON_TEST : VOTE_ICON_VOTE;
            if ($vote->x_instanceof () == INSTANCEOF_EVAL)
                $icon = EVAL_PIC_ICON;
            $icon = Assets::img($icon, array('class' => 'text-bottom'));
            $voteInfo = $number." / <a href=\"".URLHelper::getLink("dispatch.php/profile?username=".$authorUsername)."\">\n"
                    . "  <font size=\"-1\" color=\"#333399\">".htmlReady($authorName)."</font>\n"
                    . " </a>\n"
                    . " <font size=\"-1\">&nbsp;".date ("d.m.Y", $date)."</font>&nbsp;";

            $title = "<a href=\"#\" class=\"tree\" onclick=\"EVALUATIONSWIDGET.openclose('".$voteID."','vote'); return false; \">".$title."</a>";

            $voteItemArray[$voteID]['title'] = $title;
            $voteItemArray[$voteID]['id'] = $voteID;
            $voteItemArray[$voteID]['voteInfo'] = $voteInfo;
            $voteItemArray[$voteID]['icon'] = $icon;
            $voteItemArray[$voteID]['date'] = $date;
            $voteItemArray[$voteID]['vote'] = $vote;

            unset($vote->voteDB->vote);
            unset($vote);
        }
        $voteDB->finalize ();
        return $voteItemArray;
    }



    function createFormHeader($vote) {
        $html = "";
        $html .="<form onsubmit= \"EVALUATIONSWIDGET.comsubmitvote('".$vote->getObjectID ()."','vote','".PluginEngine::getURL($this, array(),"comsubmit")."');return false;\" id=\"comsubmit\" >\n";
        $html .= " <input type=\"hidden\" name=\"voteopenID\" ".
                "value=\"".$vote->getObjectID (). "\">\n";

        $html .= CSRFProtection::tokenTag();
        return $html;
    }


    /**@todo create templates for changed functions of vote_show.inc.php
     * Creates a HTML-form-footer and the administrationbuttons
     * @param    Object   $vote    The vote object
     * @param    String   $userID  The unique user id
     * @param    String   $perm    The perm of the user
     * @param    String   $rangeID The rangeID
     * @returns  String   The HTML-form-footer
     */
    function createFormFooter ($vote, $userID, $perm, $rangeID, $isAssociated,$isPreview,$revealNames,$sortAnswers,$changeAnswer,$changeAnswerButton ) {
        $html = "";
        $haveFullPerm = $perm->have_studip_perm ("tutor", $vote->getRangeID()) ||
                $userID == $vote->getAuthorID ();
        $voteDB  = new VoteDB ();
        $isStopped = $vote->isStopped();
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
            $html .= Button::create(_('Ergebnisse'), 'previewButton', array('title' => _('Hier können Sie sich die Ergebnisse im Voraus ansehen.'), 'onclick'=>"EVALUATIONSWIDGET.createopenvotepreview('".$vote->getObjectID ()."','".PluginEngine::getURL($this, array(),"create_open_vote_preview")."','vote');return false;"));
        }
        /* ---------------------------------------------------------------------- */

        /* Changebutton --------------------------------------------------------- */
        if ($vote->isChangeable() &&
                $isAssociated &&
                ! $vote->isStopped() &&
                ! $changeAnswerButton &&
                ! $vote->isError()
        ) {
            $html .= Button::create(_('Antwort ändern'), 'changeAnswerButton', array('title' =>_('Hier können Sie Ihre Antwort nochmal ändern.'),'onclick'=>"EVALUATIONSWIDGET.createopenvotechanged('".$vote->getObjectID ()."','".PluginEngine::getURL($this, array(),
                    /*"create_open_vote_changed"*/ "create_open_vote_content"
                    )."');return false;"));
#      $html_extra = "<br>";
        }
        /* ---------------------------------------------------------------------- */

        /* Backbutton ----------------------------------------------------------- */
        elseif ($isPreview) {
            $html .= Button::create('<< ' . _('Zurück'), 'escapePreviewButton', array('title' => _('Zurück zum Abstimmen.'),'onclick'=>"EVALUATIONSWIDGET.back('".$vote->getObjectID ()."','".PluginEngine::getURL($this, array(),"back")."','vote');return false;"));
#      $html_extra = "<br>";
        }
        /* ---------------------------------------------------------------------- */

        /* Sortbutton ----------------------------------------------------------- */
        if ( count($vote->getAnswers()) > 2 &&
                ($isAssociated || $isPreview || $isStopped) &&
                $vote->isInUse() &&
                ! (($vote->getResultVisibility() == VOTE_RESULTS_AFTER_END ||
                                $vote->getResultVisibility() == VOTE_RESULTS_NEVER) &&
                        $vote->isActive()) ) {
            $onclick = ($sortAnswers ? "EVALUATIONSWIDGET.createopenvotesortunsort('".$vote->getObjectID ()."','".PluginEngine::getURL($this, array(),"create_open_vote_unsort")."','".$isPreview."');return false;" : "EVALUATIONSWIDGET.createopenvotesortunsort('".$vote->getObjectID ()."','".PluginEngine::getURL($this, array(),"create_open_vote_sort")."','".$isPreview."');return false;" ) ;
            $html .= Button::create(($sortAnswers ? _('Nicht sortieren') : _('Sortieren')), 'sortButton', array('title' => ($sortAnswers
                    ? _('Antworten wieder in Ihrer ursprünglichen Reihenfolge darstellen.')
                    : _('Antworten nach Stimmenanzahl sortieren.')), 'onclick'=>$onclick));


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
        ) {

            if($revealNames)
                $html .= LinkButton::create(_('Normale Ansicht'),'revealNames', array('title' => _('Zurück zur normalen Ansicht.'),'onclick'=>"EVALUATIONSWIDGET.back('".$vote->getObjectID ()."','".PluginEngine::getURL($this, array(),"back")."', 'vote');return false;"));
            else
                $html .= LinkButton::create(_('Namen zeigen'),'revealNames', array('title' => _('Zeigen, wer welche Antwort gewählt hat.'),'onclick'=>"EVALUATIONSWIDGET.showNames('".$vote->getObjectID ()."','".PluginEngine::getURL($this, array(),"show_Names")."');return false;"));
        }
        /* ---------------------------------------------------------------------- */

        /* Adminbuttons --------------------------------------------------------- */

        if ($haveFullPerm) {
            if (!$vote->isStopped())
                $html .= LinkButton::create(_('Bearbeiten'), URLHelper::getURL(VOTE_FILE_ADMIN
                        . "?page=edit&type=" . $vote->x_instanceof()
                        . "&voteID=".$vote->getObjectID()),
                        array('title' => ( $vote->x_instanceof() == INSTANCEOF_TEST
                        ? _('Diesen Test bearbeiten.')
                        : _('Diese Umfrage bearbeiten.'))));
            if (!$vote->isStopped())
                $html .= LinkButton::createCancel(_('Stop'), URLHelper::getURL(VOTE_FILE_ADMIN
                        . "?page=overview&voteID=" . $vote->getObjectID ()
                        . "&voteaction=stop&referer=1&showrangeID=".$vote->getRangeID()),
                        array('title' => ($vote->x_instanceof() == INSTANCEOF_TEST
                        ? _("Diesen Test stoppen.")
                        : _("Diese Umfrage stoppen."))));

            $html .= LinkButton::create(_('Löschen'), URLHelper::getURL(VOTE_FILE_ADMIN."?page=overview&voteID="
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

    /**@todo create templates for changed functions of vote_show.inc.php
     * Creates a successmessage
     * @param    object   $vote       The vote
     * @param    bool     $firstTime  whether the report is created 1st time
     * @returns  String   The HTML-text
     */
    function createSuccessReport ($vote,$sortAnswers,$revealNames=NO, $firstTime = YES, $changed = NO) {
        global $perm, $auth;
        $html     = "";
        $stopdate = $vote->getRealStopdate();
        /* Show the results ----------------------------------------------------- */
        $haveFullPerm = $perm->have_studip_perm ("tutor", $vote->getRangeID()) ||
                $auth->auth["uid"] == $vote->getAuthorID ();

        $resVis = $haveFullPerm ? VOTE_RESULTS_ALWAYS : $vote->getResultvisibility();
        switch ($resVis) {

            case VOTE_RESULTS_AFTER_VOTE:
            case VOTE_RESULTS_ALWAYS:
                $html .= $this->createVoteResult ($vote,$sortAnswers,$revealNames);
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
                $nrAll = count(Request::getArray('answer'));
                $nrCorrect = $vote->getNumberOfCorrectAnswers (Request::getArray('answer'));
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


    /**@todo create templates for changed functions of vote_show.inc.php
     * Display the results in HTML
     * @param    object   $vote     The vote
     * @param    bool     $preview  if the user wants to look at the results
     *                              without having voted
     * @returns  String   The HTML-text
     */
    function createVoteResult ($vote,$sortAnswers, $revealNames = NO,$preview = NO) {
        global $auth, $perm;

        $haveFullPerm = $perm->have_studip_perm ("tutor", $vote->getRangeID()) ||
                $auth->auth["uid"] == $vote->getAuthorID ();

        $revealNames = $revealNames &&
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
                if ($percent < 50) {
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
                        $html .= "<a href=\"".URLHelper::getLink("dispatch.php/profile?username=".
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

    function show_stopped_votes($rangeID, $perm) {
        global $perm,$user;
        /* Set variables -------------------------------------------------------- */
        $voteDB  = new VoteDB ();
        if ($voteDB->isError ()) {
            echo createErrorReport ($voteDB, _("Umfrage-Datenbankfehler"));
            return;
        }
        if ($perm->have_studip_perm ("tutor", $rangeID) ||
                get_username($user->id) == $rangeID ||
                (isDeputyEditAboutActivated() &&
                        isDeputy($user->id, get_userid($rangeID), true)))
            $haveFullPerm = true;
        else
            $haveFullPerm = false;
        $stoppedVotes = $voteDB->getStoppedVisibleVotes ($rangeID);
        $voteItemArray = array();
        /* Show all stopped Votes ----------------------------------------------- */
        if (!empty ($stoppedVotes) && $haveFullPerm) {

            foreach ($stoppedVotes as $tmpVote) {
                $voteID = $tmpVote["voteID"];

                if ($tmpVote["type"] == INSTANCEOF_TEST)
                    $vote = new TestVote ($voteID);
                else
                    $vote = new Vote ($voteID);

                $title          = htmlReady (my_substr ($vote->getTitle (), 0, $maxTitleLength));
                $date           = $vote->getChangedate ();
                $authorName     = get_fullname ($vote->getAuthorID ());
                $authorUsername = get_username ($vote->getAuthorID ());
                $number       = $vote->voteDB->getNumberUserVoted ();

                $isNew = (($date >= object_get_visit($vote->getObjectID(),($vote->x_instanceof() == INSTANCEOF_EVAL ? 'eval' : 'vote'),false,false)) && ($vote->getAuthorID() != $GLOBALS['auth']->auth["uid"]));
                $icon = ($vote->x_instanceof () == INSTANCEOF_TEST) ? VOTE_ICON_TEST : VOTE_ICON_VOTE;
                if ($vote->x_instanceof () == INSTANCEOF_EVAL)
                    $icon = EVAL_PIC_ICON;
                $icon = Assets::img($icon, array('class' => 'text-bottom'));
                $voteInfo = $number." / <a href=\"".URLHelper::getLink("dispatch.php/profile?username=".$authorUsername)."\">\n"
                        . "  <font size=\"-1\" color=\"#333399\">".htmlReady($authorName)."</font>\n"
                        . " </a>\n"
                        . " <font size=\"-1\">&nbsp;".date ("d.m.Y", $date)."</font>&nbsp;";

                $title = "<a href=\"#\" class=\"tree\" onclick=\"EVALUATIONSWIDGET.openclose('".$voteID."','vote'); return false; \">".$title."</a>";

                $voteItemArray[$voteID]['title'] = $title;
                $voteItemArray[$voteID]['id'] = $voteID;
                $voteItemArray[$voteID]['voteInfo'] = $voteInfo;
                $voteItemArray[$voteID]['icon'] = $icon;
                $votelItemArray[$voteID]['date'] = $date;
                $voteItemArray[$voteID]['vote'] = $vote;
                unset($vote->voteDB->vote);
                unset($vote);
            }
            return $voteItemArray;
        }
    }

    function show_stopped_evals($rangeID, $perm, $isHomepage = NO) {
        global $perm,$user;
        /* Set variables -------------------------------------------------------- */
        $evalItemArray = array();
        if ($perm->have_studip_perm ("tutor", $rangeID) ||
                get_username($user->id) == $rangeID ||
                (isDeputyEditAboutActivated() &&
                        isDeputy($user->id, get_userid($rangeID), true)))
            $haveFullPerm = true;
        else
            $haveFullPerm = false;
        if (!($rangeID2 = get_userid($rangeID)))
            $rangeID2 = $rangeID;
        $evalDB  = new EvaluationDB ();
        if ($evalDB->isError ()) {
            echo createErrorReport ($evalDB, _("Evaluation-Datenbankfehler"));
            return;
        }
        $stoppedEvals = array ();
        if ($haveFullPerm) {
            $stoppedEvals = $evalDB->getEvaluationIDs ($rangeID2, EVAL_STATE_STOPPED);
            if ($evalDB->isError ()) {
                echo createErrorReport ($evalDB,
                _("Datenbankfehler beim Auslesen der EvaluationsIDs."));
            }
        }

        /* Show all stopped Votes ----------------------------------------------- */
        if ((!empty ($stoppedEvals) && $haveFullPerm)) {
            foreach ($stoppedEvals as $evalID) {

                $vote = new Evaluation ($evalID, NULL, EVAL_LOAD_NO_CHILDREN);
                $haveFullPerm = $haveFullPerm || ($userID == $eval->getAuthorID());
                $maxTitleLength = ($isHomepage)
                        ? VOTE_SHOW_MAXTITLELENGTH
                        : VOTE_SHOW_MAXTITLELENGTH - 10;

                $title          = htmlReady (my_substr ($vote->getTitle (), 0, $maxTitleLength));
                $date           = $vote->getChangedate ();
                $authorName     = get_fullname ($vote->getAuthorID ());
                $authorUsername = get_username ($vote->getAuthorID ());
                if (empty ($evalDB))
                //  $number       = $vote->voteDB->getNumberUserVoted ();
                    $number = "";
                else
                    $number       = $evalDB->getNumberOfVotes ($vote->getObjectID ());

                $isNew = (($date >= object_get_visit($vote->getObjectID(),($vote->x_instanceof() == INSTANCEOF_EVAL ? 'eval' : 'vote'),false,false)) && ($vote->getAuthorID() != $GLOBALS['auth']->auth["uid"]));
                $icon = ($vote->x_instanceof () == INSTANCEOF_TEST) ? VOTE_ICON_TEST : VOTE_ICON_VOTE;
                if ($vote->x_instanceof () == INSTANCEOF_EVAL)
                    $icon = EVAL_PIC_ICON;
                $icon = Assets::img($icon, array('class' => 'text-bottom'));
                $voteInfo = $number." / <a href=\"".URLHelper::getLink("dispatch.php/profile?username=".$authorUsername)."\">\n"
                        . "  <font size=\"-1\" color=\"#333399\">".htmlReady($authorName)."</font>\n"
                        . " </a>\n"
                        . " <font size=\"-1\">&nbsp;".date ("d.m.Y", $date)."</font>&nbsp;";

                $title = "<a href=\"$link\" class=\"tree\" onclick=\"EVALUATIONSWIDGET.openclose('".$evalID."','eval'); return false; \">".$title."</a>";

                $evalItemArray[$evalID]['title'] = $title;
                $evalItemArray[$evalID]['voteInfo'] = $voteInfo;
                $evalItemArray[$evalID]['icon'] = $icon;
                $evalItemArray[$evalID]['date'] = $date;
                $evalItemArray[$evalID]['eval'] = $vote;
                $evalItemArray[$evalID]['id'] = $evalID;
            }
            $evalDB->finalize ();
            return $evalItemArray;

        }
    }

    function show_evals ($rangeID, $userID, $perm, $isHomepage = NO) {

        $evalDB  = new EvaluationDB ();

        if ($evalDB->isError ()) {
            echo createErrorReport ($evalDB, _("Evaluation-Datenbankfehler"));
            return;
        }
        if ($perm->have_studip_perm ("tutor", $rangeID) ||
                get_username($userID) == $rangeID ||
                (isDeputyEditAboutActivated() &&
                        isDeputy($userID, get_userid($rangeID), true)))
            $haveFullPerm = true;
        else
            $haveFullPerm = false;

        $activeEvals  = array ();
        $stoppedEvals = array ();

        if (!($rangeID2 = get_userid($rangeID)))
            $rangeID2 = $rangeID;

        $activeEvals  = $evalDB->getEvaluationIDs ($rangeID2, EVAL_STATE_ACTIVE);
        if ($evalDB->isError ()) {
            echo createErrorReport ($evalDB,
            _("Datenbankfehler beim Auslesen der EvaluationsIDs."));
        }
        if ($haveFullPerm) {
            $stoppedEvals = $evalDB->getEvaluationIDs ($rangeID2, EVAL_STATE_STOPPED);
            if ($evalDB->isError ()) {
                echo createErrorReport ($evalDB,
                _("Datenbankfehler beim Auslesen der EvaluationsIDs."));
            }
        }

        if ( empty ($activeEvals) &&
                empty ($stoppedEvals) &&
                !($perm->have_studip_perm ("tutor", $rangeID) ||
                        get_username($userID) == $rangeID ||
                        (isHomepage && isDeputyEditAboutActivated() && isDeputy($userID, get_userid($rangeID), true)))
        ) {
            $evalDB->finalize ();
        }

        $evalItemArray = array();
        foreach ($activeEvals as $evalID) {
            $eval = new Evaluation ($evalID, NULL, EVAL_LOAD_NO_CHILDREN);
            $haveFullPerm = $haveFullPerm || ($userID == $eval->getAuthorID());
            $maxTitleLength = ($isHomepage)
                    ? VOTE_SHOW_MAXTITLELENGTH
                    : VOTE_SHOW_MAXTITLELENGTH - 10;

            $title          = htmlReady (my_substr ($eval->getTitle (), 0, $maxTitleLength));
            $date           = $eval->getChangedate ();
            $authorName     = get_fullname ($eval->getAuthorID ());
            $authorUsername = get_username ($eval->getAuthorID ());
            if (empty ($evalDB))
                $number = "";
            else
                $number       = $evalDB->getNumberOfVotes ($eval->getObjectID ());

            $isNew = (($date >= object_get_visit($eval->getObjectID(),($eval->x_instanceof() == INSTANCEOF_EVAL ? 'eval' : 'vote'),false,false)) && ($eval->getAuthorID() != $GLOBALS['auth']->auth["uid"]));
            $icon = ($eval->x_instanceof () == INSTANCEOF_TEST) ? VOTE_ICON_TEST : VOTE_ICON_VOTE;
            if ($eval->x_instanceof () == INSTANCEOF_EVAL)
                $icon = EVAL_PIC_ICON;
            $icon = Assets::img($icon, array('class' => 'text-bottom'));
            $voteInfo = $number." / <a href=\"".URLHelper::getLink("dispatch.php/profile?username=".$authorUsername)."\">\n"
                    . "  <font size=\"-1\" color=\"#333399\">".htmlReady($authorName)."</font>\n"
                    . " </a>\n"
                    . " <font size=\"-1\">&nbsp;".date ("d.m.Y", $date)."</font>&nbsp;";

            $title = "<a href=\"$link\" class=\"tree\" onclick=\"EVALUATIONSWIDGET.openclose('".$evalID."','eval'); return false; \">".$title."</a>";

            $evalItemArray[$evalID]['title'] = $title;
            $evalItemArray[$evalID]['voteInfo'] = $voteInfo;
            $evalItemArray[$evalID]['icon'] = $icon;
            $evalItemArray[$evalID]['date'] = $date;
            $evalItemArray[$evalID]['eval'] = $eval;
            $evalItemArray[$evalID]['id'] = $evalID;
        }
        $evalDB->finalize ();
        return $evalItemArray;
    }

    function create_open_vote_sort_action() {
        global $perm, $user;

        $voteID = Request::option('id');
        $prev = Request::option('prev');
        $vote = new Vote ($voteID);
        $voteDB  = new VoteDB ();

        $return = createBoxContentHeader ();
        $return .= $this->createFormHeader ($vote);
        /* User has already used the vote --------------------------------- */
        $sortAnswers = YES;
        $isAssosiated = YES;
        $return .= $this->createSuccessReport ($vote,$sortAnswers, NO);
        $return .= $this->createFormFooter($vote, $userID, $perm, $rangeID, $isAssosiated, $prev, $revealNames, $sortAnswers, $changeAnswer, $changeAnswerButton);

        $return .= createBoxContentFooter ();
        $vote->finalize ();
        $voteDB->finalize ();
        echo studip_utf8encode($return);

    }

    function create_open_vote_unsort_action() {
        global $perm, $user;

        $voteID = Request::option('id');
        $vote = new Vote ($voteID);
        $voteDB  = new VoteDB ();
        $prev = Request::option('prev');
        $return = createBoxContentHeader ();
        $return .= $this->createFormHeader ($vote);
        /* User has already used the vote --------------------------------- */
        $sortAnswers = NO;
        $isAssosiated = YES;
        $return .= $this->createSuccessReport ($vote,$sortAnswers, NO);
        $return .= $this->createFormFooter($vote, $userID, $perm, $rangeID, $isAssosiated, $prev, $revealNames, $sortAnswers, $changeAnswer, $changeAnswerButton);

        $return .= createBoxContentFooter ();
        $vote->finalize ();
        $voteDB->finalize ();
        echo studip_utf8encode($return);

    }

    function show_Names_action() {
        global $perm, $user;

        $voteID = Request::option('id');
        $vote = new Vote ($voteID);
        $voteDB  = new VoteDB ();

        $return = createBoxContentHeader ();
        $return .= $this->createFormHeader ($vote);
        /* User has already used the vote --------------------------------- */
        $revealNames = YES;
        $isAssosiated = YES;
        $return .= $this->createSuccessReport ($vote,$sortAnswers,  $revealNames);
        $return .= $this->createFormFooter($vote, $userID, $perm, $rangeID, $isAssosiated, $isPreview, $revealNames, $sortAnswers, $changeAnswer, $changeAnswerButton);

        $return .= createBoxContentFooter ();
        $vote->finalize ();
        $voteDB->finalize ();
        echo studip_utf8encode($return);

    }

    function open_stopped_content_action() {
        $factory = new Flexi_TemplateFactory(dirname(__FILE__).'/templates');
        $template = $factory->open('stopped_evals_list');
        $template->stoppedVoteItemArray = $this->show_stopped_votes('studip', $perm);
        $template->stoppedEvalItemArray = $this->show_stopped_evals('studip', $perm);
        $template->plugin = $this;
        echo studip_utf8encode($template->render());
    }

     function create_open_vote_content_action() {
        global $perm, $user;

        $voteID = Request::option('id');
        $vote = new Vote ($voteID);
        $voteDB  = new VoteDB ();
        object_set_visit($voteID, "vote"); //set a visittime for this vote
        $return = createBoxContentHeader ();
        $return .= $this->createFormHeader ($vote);
        $changeAnswer = (Request::option('change_answer') !==  null) && $voteDB->isChangeable($voteID);
        /* User has already used the vote --------------------------------- */
        if ( (!$changeAnswer) && $voteDB->isAssociated ($voteID, $user->id) ) {
            $return .= createSuccessReport ($vote, NO);
            $return .= $this->createFormFooter($vote, $user->id, $perm, $rangeID, 1, $isPreview, $revealNames, $sortAnswers, $changeAnswer, $changeAnswerButton);
        }
        /* User has not yet used the vote or wants to change his answer -- */
        else {
            $return .= createVoteForm ($vote, $user->id);
            $return .= $this->createFormFooter($vote, $user->id, $perm, $rangeID, 0, $isPreview, $revealNames, $sortAnswers, $changeAnswer, $changeAnswerButton);
        }
        /* --------------------------------------------------------------- */

        $return .= createBoxContentFooter ();
        $vote->finalize ();
        $voteDB->finalize ();
        echo studip_utf8encode($return);

    }
    function create_open_eval_content_action() {
        global $perm, $user;

        $evalID = Request::option('id');
        $eval = new Evaluation ($evalID, NULL, EVAL_LOAD_NO_CHILDREN);
        $evalDB  = new EvaluationDB();
        object_set_visit($evalID, "eval"); //set a visittime for this eval
        $return="";
        $return.= createBoxContentHeader ();
        $return.= createFormHeader ($eval);

        /* User has already used the vote --------------------------------- */
        $hasVoted = $evalDB->hasVoted ($evalID, $user->id);
        $numberOfVotes = $evalDB->getNumberOfVotes ($evalID);
        $evalNoPermissons = EvaluationObjectDB::getEvalUserRangesWithNoPermission($eval);

        $table = new HTML ("table");
        $table->addAttr("style", "font-size:1.2em;");
        $table->addAttr("width", "100%");
        $table->addAttr("border", "0");
        $tr = new HTML ("tr");
        $td = new HTML ("td");

        $maxTitleLength = ($isHomepage)
                ? VOTE_SHOW_MAXTITLELENGTH
                : VOTE_SHOW_MAXTITLELENGTH - 10;

        if (strlen (formatReady($eval->getTitle ())) > $maxTitleLength) {
            $b = new HTML ("b");
            $b->addHTMLContent(formatReady($eval->getTitle ()));

            $td->addContent($b);
            $td->addContent( new HTMLempty ("br") );
            $td->addContent( new HTMLempty ("br") );
        }

        $td->addAttr("style", "font-size:0.8em;");
        $td->addHTMLContent(formatReady($eval->getText ()));
        $td->addContent(new HTMLempty ("br"));
        $td->addContent(new HTMLempty ("br"));

        if (! $hasVoted ) {
            $div = new HTML ("div");
            $div->addAttr ("align", "center");
            $div->addContent (EvalShow::createVoteButton ($eval));
            $td->addContent ($div);
        }

        $tr->addContent ($td);
        $table->addContent ($tr);
        $table->addContent (EvalShow::createEvalMetaInfo ($eval, $hasVoted));
        $rangeID = 'studip';
        if ($perm->have_studip_perm ("tutor", $rangeID) ||
                get_username($user->id) == $rangeID ||
                (isDeputyEditAboutActivated() &&
                        isDeputy($user->id, get_userid($rangeID), true)))
            $haveFullPerm = true;
        else
            $haveFullPerm = false;

        if ( $haveFullPerm ) {
            if (!($range = get_username($rangeID2)))
                $range = $rangeID2;
            $tr = new HTML ("tr");
            $td = new HTML ("td");
            $td->addAttr ("align", "center");
            $td->addContent (EvalShow::createOverviewButton ($range, $eval->getObjectID ()));

            if ( $evalNoPermissons == 0 ) {
                $td->addContent (EvalShow::createStopButton ($eval));
                $td->addContent (EvalShow::createDeleteButton ($eval));
                $td->addContent (EvalShow::createExportButton ($eval));
                $td->addContent (EvalShow::createReportButton ($eval));
            }

            $tr->addContent ($td);
            $table->addContent ($tr);
        }

        $return.= $table->createContent ();
        //echo createVoteForm ($eval, $userID);
        /* --------------------------------------------------------------- */
        //echo createFormFooter ($eval, $userID, $perm, $rangeID);
        $return.= createBoxContentFooter ();

        echo studip_utf8encode($return);

    }

    function create_open_vote_preview_action() {
        global $perm, $user;
        /* User clicked 'preview' ---------------------------------------- */
        $voteID = Request::option('id');
        $vote = new Vote ($voteID);
        $return ="";
        $return .= $this->createSuccessReport ($vote, NO);
        $return .= $this->createFormFooter($vote, $user->id, $perm, $rangeID, NO, YES, $revealNames, $sortAnswers, $changeAnswer, $changeAnswerButton);
        echo studip_utf8encode($return);

    }


    function create_open_vote_changed_action() {
        global $perm, $user;
        /* User clicked 'preview' ---------------------------------------- */
        $voteID = Request::option('id');
        $vote = new Vote ($voteID);
        echo createReportMessage (_("Die &Auml;nderungen wurden gespeichert"),
                VOTE_ICON_SUCCESS, VOTE_COLOR_SUCCESS).
                "<br>\n";


    }
    function back_action() {
        global $perm, $user;
        /* User clicked 'preview' ---------------------------------------- */
        $voteID = Request::option('id');
        $vote = new Vote ($voteID);
        $this->create_open_vote_content_action();


    }

     function comsubmit_action() {
        global $perm,$user;


        $id = Request::option('voteopenID');
        $vote = new Vote($id);
        $answer = Request::getArray('answer');
        $return ="";
        $vote->executeAssociate ($user->id, $answer);
        if ($vote->isError ()) {
            $return .= createErrorReport ($vote, _("Fehler bei der Abstimmung"));
            $return .= createVoteForm ($vote, $user->id);
        } else {
            if ($answerChanged)
                $return .= createSuccessReport ($vote, NO, YES);
            else
                $return .= createSuccessReport ($vote);
        }
        $return .=  $this->createFormFooter($vote, $user->id, $perm, $rangeID, YES, NO, $revealNames, $sortAnswers, $changeAnswer, $changeAnswerButton);
        $return .= createBoxContentFooter ();
        $vote->finalize ();
        echo studip_utf8encode($return);


    }

    function getPluginName(){
        return _("Umfragen");
    }
}
