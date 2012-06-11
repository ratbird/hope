<?php
# Lifter002: TODO
# Lifter005: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/**
 * The page to create/edit votes (HTML generation) ... vote_edit.lib.php
 *
 * @author      Michael Cohrs <michael A7 cohrs D07 de>
 * @copyright   2003 Stud.IP-Project
 * @access      public
 * @module      vote_edit_lib
 * @package     vote
 * @modulegroup vote_modules
 *
 */
 

use Studip\Button, Studip\LinkButton;

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

/************************************************************************/

/**
 * is either "vote" or "test"
 * @access   private
 * @var      int $type
 */
global $type;

/**
 * the vote object
 * @access   private
 * @var      Vote $vote
 */
global $vote;

/**
 * the edit page's operational mode
 * @access   private
 * @var      MODE_CREATE, MODE_MODIFY or MODE_RESTRICTED
 */
global $pageMode;

/************************************************************************/
/**************    functions for printing html code     *****************/
/************************************************************************/

/**
 * prints needed javascript functions...
 *
 * @access public
 *
 */

function printJSfunctions ( ) {
    global $pageMode;

    $js = "<script type=\"text/javascript\" language=\"JavaScript\"><!--\n";

    if( $pageMode != MODE_RESTRICTED )
    $js .= "function toggleChangeable() {\n"
        . "  if (document.voteform.anonymous[1].checked) {\n"
        . "    document.voteform.changeable[0].disabled = false;\n"
        . "    document.voteform.changeable[1].disabled = false;\n"
        . "    document.voteform.namesVisibility[0].disabled = false;\n"
        . "    document.voteform.namesVisibility[1].disabled = false;\n"
        . "  }\n"
        . "  else {\n"
        . "    document.voteform.changeable[0].disabled = true;\n"
        . "    document.voteform.changeable[1].disabled = true;\n"
        . "    document.voteform.namesVisibility[0].disabled = true;\n"
        . "    document.voteform.namesVisibility[1].disabled = true;\n"
        . "  }\n"
        . "}\n"

        . "function markAllAnswers( answers ) {\n"
        . "  markAll = false;\n"
        . "  for( i=0; i<answers.length; i++ ) {\n"
        . "    if( !answers[i].checked ) {\n"
        . "      markAll = true;\n"
        . "      break;\n"
        . "    }\n"
        . "  }\n"
        . "  for( i=0; i<answers.length; i++ ) {\n"
        . "    if( markAll )\n"
        . "      answers[i].checked = true;\n"
        . "    else\n"
        . "      answers[i].checked = false;\n"
        . "  }\n"
        . "}\n";

    $js .= "function deleteField( field, initialValue ) {\n"
    . "  if( field.value == initialValue) {\n"
    . "    field.value = \"\";\n"
    . "  }\n"
    . "}\n"

    . "function restoreField( field, initialValue ) {\n"
    . "  if( field.value == \"\") {\n"
    . "    field.value = initialValue;\n"
    . "  }\n"
    . "}\n"
    ;

//  . "function finishInputs() {\n"
//  . "  document.voteform.title.focus();\n"
//  . "  currentTitle = document.voteform.title.value;\n"
//  . "  document.voteform.question.focus();\n"
//  . "  currentQuestion = document.voteform.question.value;\n"
//  . "  if( currentTitle == \"\"  &&  currentQuestion != \"\") {\n"
//  . "    addDots = \"\";\n"
//  . "    if( currentQuestion.length > 50 ) addDots = \"...\";\n"
//  . "    document.voteform.title.value = currentQuestion.substr( 0, 64 ) + addDots;\n"
//  . "  }\n"
//  . "  document.voteform.question.focus(); /* this triggers deleteField */ \n"
//  . "}\n";

    $js .= "//--></script>\n";

    echo $js;
}

/**
 * prints the start of the edit form and errors, if any
 * ... opens <table><tr><td> <table><tr><td>
 *
 * @access public
 * @param  int $voteID      the md5 ID of the vote to be created/modified
 * @param  int $rangeID     the rangeID of the vote to be created/modified
 * @param  string $referer  the original http referer to be memorized...
 *
 */

function printFormStart ( $voteID, $rangeID, $referer ) {
    global $pageMode, $type, $vote;

    if( $pageMode != MODE_CREATE ) {
    $task_string = ($type=="test")
        ? _("Gespeicherten Test bearbeiten")
        : _("Gespeicherte Umfrage bearbeiten");
    }
    else {
    $task_string = ($type=="test")
        ? _("Neuen Test erstellen")
        : _("Neue Umfrage erstellen");
    $voteID = "";
    }

    $html = createBoxHeader (FALSE, ' style="width: 100%;"', $task_string,
                 Assets::image_path($type."-icon.gif"),"","","","",
                 "blank" );
    $html .= "<form action=\"".$GLOBALS['PHP_SELF']."?page=edit&type=".$type."\" name=\"voteform\" method=post>"
    . CSRFProtection::tokenTag()
    . "<input type=hidden name=\"voteID\" value=\"".$voteID."\">"
    . "<input type=hidden name=\"rangeID\" value=\"".$rangeID."\">"
    . "<input type=hidden name=\"referer\" value=\"".$referer."\">"
    . "<table width=\"100%\" cellpadding=5 cellspacing=3 border=0>\n"
    . "<tr><td valign=\"top\">\n";

    if( $vote->isError() )
    $html .= createErrorReport( $vote );

    echo $html;
#    echo $debug;
}

/**
 * prints the title field for current vote
 *
 * @access public
 * @param  string $title    the vote's title, if existing
 *
 */

function printTitleField ( $title = "" ) {
    global $pageMode, $vote;

    if( $title == "" || $title == TITLE_HELPTEXT ) {
    $title = TITLE_HELPTEXT;
    $js = "onFocus='deleteField( this, \"".$title."\" );'";
    $js .= " onBlur='restoreField( this, \"".$title."\" );'";
    }

    if( strpos($_SERVER["HTTP_REFERER"], "page=edit") )
       $title = stripslashes($title);

    $html = "<font size=-1><b>" . _("Titel:")   . "</b></font><br>"
    . "<input type=text size=50 maxlength=100 name=\"title\" value=\"".htmlReady($title)."\" ".$js." tabindex=1>";
    $html .= Assets::img('icons/16/grey/info-circle.png', tooltip(_("Wenn Sie keinen Titel angeben, wird dieser automatisch aus der Fragestellung übernommen."), FALSE, TRUE));
    $html .= "<br><br>\n";

    echo $html;
}

/**
 * prints the question field for current vote
 *
 * @access  public
 * @param   string $question    the vote's question, if existing
 *
 */

function printQuestionField ( $question = "" ) {
    global $pageMode;

    if( $question == "" || $question == QUESTION_HELPTEXT ) {
    $question = QUESTION_HELPTEXT;
    $js = "onFocus='deleteField( this, \"".$question."\" );'";
    $js .= " onBlur='restoreField( this, \"".$question."\" );'";
    }

    if( strpos($_SERVER["HTTP_REFERER"], "page=edit") && $pageMode != MODE_RESTRICTED )
       $question = stripslashes($question);

    $html = "<font size=-1><b>" . _("Frage:")   . "</b></font><br>";

    if( $pageMode != MODE_RESTRICTED ) {
    $html .= "<textarea class=\"add_toolbar\" cols=50 rows=2 style=\"width:100%;\" name=\"question\" ".$js." tabindex=2>".htmlReady($question)."</textarea>";
    } else {
       $html .= "<div class=steelgraulight style=\"padding:2px;\">"
      . "<font size=-1>".formatReady($question)."</font>"
      . "</div>";
    }

    $html .= "<br><br>\n";
    echo $html;
}

/**
 * prints the answer fields table for current vote
 *
 * @access  public
 * @param   array $answers    the vote's answers, if existing,
 *                            e.g.: array( array( "answer_id" => "35b9dfed54c3740edcf96ece787994f3",
 *                                                "text"      => "This is my answer",
 *                                                "counter"   => 7,
 *                                                "correct"   => YES ),
 *                                         array( "answer_id" => ... , .. )
 *                                        )
 */

function printAnswerFields ( $answers ) {
    global $type, $pageMode, $auth;

    $html = "<table cellspacing=0 cellpadding=0 border=0 width=\"100%\"><tr>\n"
    . "<td align=left><b>"
    . "<font size=-1>" . _("Antwortm&ouml;glichkeiten:") . "</font>"
    . "</b></td>"
    . "</tr></table>\n";

    $html .= "<table border=0 cellpadding=2 cellspacing=0 width=\"100%\">";

    // the table's header bar
    $html .= "<tr><th align=center width=15>#</th>";
    if( $type == "test" )
    $html .= "<th>" . _("Richtig") . "</th>";

    $html .= "<th align=left width=100%>" . _("Antwort") . "</th>";

    if( $pageMode != MODE_RESTRICTED ) {
    if( count($answers) > 1 )
        $html .= "<th>" . _("Position") . "</th>";
    $html .= "<th>" . _("L&ouml;schen") . "</th>";
    }
    $html .= "</tr>";

    if( count($answers) == 0 )
    // we need a fake answers field :/
    $html .= "<tr><td colspan=4 align=center>"
        . _("keine Antworten vorhanden")
        . "<input type=hidden name=answers value=\"\"></td></tr>";

    // now print one row for each existing answer
    for( $i=0; $i < count($answers); $i++ ) {

    if( $i%2 == 1 )
        $html .= "<tr class=steelgraulight>";
    else
        $html .= "<tr>";

    // the answer's number
    $html .= "<td align=right><font size=-1>" . ($i+1) . ".&nbsp;</font></td>";

    // the column for the checkbox "correct"
    if( $type == "test" ) {
        if( $pageMode != MODE_RESTRICTED )
        $html .= "<td align=center><input type=checkbox name=\"answers[$i][correct]\" "
            . ( $answers[$i]['correct'] ? "checked" : "" )."></td>";
        else
        $html .= "<td align=center>". image_if_true( $answers[$i]['correct'] ) . "</td>";
    }

    // textfield for the answer

#   if( $auth->auth["jscript"] )
#       $inputSize = round( $auth->auth["xres"] / 16 );
#   else
#       $inputSize = 60 ; // default
#   if( $type == "vote" )
#       $inputSize += 6;  // we have no column for the checkbox 'correct'

    if( strlen($answers[$i]['text']) > 80 )
        $inputSize = "size=".($type=="test" ? 60 : 65)." "; // prevent IE from breaking the page layout
    else
        $inputSize = "style=\"width:100%;\" ";

    $html .= "<td align=left>";
    if( $pageMode != MODE_RESTRICTED ) {

        if( strpos($_SERVER["HTTP_REFERER"], "page=edit") )
        $answers[$i]['text'] = stripslashes($answers[$i]['text']);

        $html .= "<input type=text " . $inputSize
        . "name=\"answers[$i][text]\" value=\"" . htmlReady($answers[$i]['text'])."\" tabindex=".(3+$i).">"
        . "<input type=hidden name=\"answers[$i][answer_id]\" value=\"".$answers[$i]['answer_id']."\">"
        . "<input type=hidden name=\"answers[$i][counter]\" value=\"".$answers[$i]['counter']."\">";
    }
    else
        $html .= "<font size=-1>" . formatReady($answers[$i]['text']) . "</font>";

    $html .= "</td>\n";

    // the "position" and "delete" columns
    if( $pageMode != MODE_RESTRICTED ) {
        if( count($answers) > 1 ) {
        $html .= "<td align=center>";
        $html .= "<input type=image name=\"move_up[$i]\" "
            . "src=\"" . Assets::image_path('icons/16/yellow/arr_2up.png') . "\" "
            . tooltip(_("Antwort hochschieben")) . " align=bottom>\n"
            . "<input type=image name=\"move_down[$i]\" "
            . "src=\"" . Assets::image_path('icons/16/yellow/arr_2down.png') . "\" "
            . tooltip(_("Antwort runterschieben")) . " align=bottom>\n";
        $html .= "</td>";
        }
        $html .= "<td align=center><input type=checkbox id=deleteCheckboxes name=\"deleteAnswers[$i]\"></td>";
    }
    $html .= "</tr>";
    }
    $html .= "</table>\n";

    // buttons for adding/deleting answers
    if( $pageMode != MODE_RESTRICTED ) {
    $html .= "<table border=0 cellpadding=0 cellspacing=0 width=\"100%\">";
    $html .= "<tr><td align=left>";   
     $html .= "<select style=\"vertical-align:middle;\" name=\"newAnswerFields\" size=1>";
    for( $i=1; $i<=10; $i++ )
        $html .= "<option value=\"$i\">$i</option>";

    $html .= "</select>";

    $html .= Button::create(_('Hinzufügen'), 'addAnswersButton' ,array('title' => _('Antwortfelder hinzufügen'))).'</td><td align=right>';
   
    if( count($answers) >= 10 && $auth->auth["jscript"])
        $html .= "(<a href=\"#markAll\" onClick=\"markAllAnswers(document.voteform.deleteCheckboxes);\" title=\""
        . _("Alle Antworten zum L&ouml;schen (de)markieren")."\">". _("Alle markieren") . "</a>)&nbsp;";

    $html .= Button::create(_('Löschen'), 'deleteAnswersButton', array('title' => _('Markierte Antworten löschen')))
        ."\n";

    $html .= "</td>\n";
    $html .= "</tr></table>\n";
    }
    
    $html .= '<div style="text-align:center"><div class="button-group">';
    $html .= Button::createAccept(_('Speichern'), 'saveButton', array('title' => _('Alle Änderungen speichern und zurück!')));
    $html .= Button::createCancel(_('Abbrechen'), 'cancelButton', array('title' => _('Alle Änderungen verwerfen und zurück!')));
    $html .= '</div></div>';
    $html .= "</td>";

    echo $html;

}

/**
 * prints the right-hand-side region of the page, with a nice infobox and save/cancel buttons
 *
 * @access  public
 *
 */

function printRightRegion ( ) {
    global $type, $pageMode, $vote;

    switch( $pageMode ) {

    case MODE_RESTRICTED:
    /* -------------------------------------------------------- */
    $info_text = ( $type == "test" )
        ? _("Es hat bereits jemand an diesem Test teilgenommen!")
        : _("Es hat bereits jemand an dieser Umfrage teilgenommen!");

    $action_text1 = ($vote->isAnonymous())
        ? _("Sie k&ouml;nnen daher nur noch den Titel, den Endzeitpunkt und die Ergebnissichtbarkeit ver&auml;ndern.")
        : _("Sie k&ouml;nnen daher nur noch den Titel, den Endzeitpunkt, die Ergebnissichtbarkeit und die Revidierbarkeit ver&auml;ndern.");

    break;

    case MODE_MODIFY:
    /* -------------------------------------------------------- */
    $info_text = ( $type == "test" )
        ? _("Sie k&ouml;nnen diesen Test noch ver&auml;ndern, solange niemand abstimmt.")
        : _("Sie k&ouml;nnen diese Umfrage noch ver&auml;ndern, solange niemand abstimmt.");

    $action_text1 = ( $type == "test" )
        ? _("Ver&auml;ndern Sie links die Frage und die Antworten Ihres Tests. ")
        : _("Ver&auml;ndern Sie links die Frage und die Antworten Ihrer Umfrage. ");

    break;

    case MODE_CREATE:
    /* -------------------------------------------------------- */
    $info_text = ( $type == "test" )
        ? _("Auf dieser Seite k&ouml;nnen Sie einen neuen Test anlegen.")
        : _("Auf dieser Seite k&ouml;nnen Sie eine neue Umfrage anlegen.");

    $action_text1 = ( $type == "test" )
        ? _("Geben Sie links die Frage und die m&ouml;glichen Antworten Ihres Tests ein. ")
        : _("Geben Sie links die Frage und die m&ouml;glichen Antworten Ihrer Umfrage ein. ");

    break;
    }

    $action_text2 = _("Im unteren Bereich k&ouml;nnen Sie weitere spezielle Einstellungen vornehmen.");
    $action_text3 = _("Wenn Sie zufrieden sind, klicken Sie auf 'speichern'.");

    /* -------------------------------------------------------- */
    $action_array[] = array ( "icon" => "icons/16/black/info.png",
                  "text" => $action_text1 );

    if( $type == "test" && $pageMode != MODE_RESTRICTED ) {
        $action_text1_extra =
        _("Deklarieren Sie mindestens eine Antwort als richtig, indem Sie einen Haken in die entsprechende Box setzen.");
        $action_array[] = array( "icon" => "icons/16/black/checkbox-checked.png",
                     "text" => $action_text1_extra );
    }

    if( $pageMode != MODE_RESTRICTED )
    $action_array[] = array ( "icon" => "icons/16/black/visibility-visible.png",
                  "text" => $action_text2 );

    $action_array[] = array( "icon" => "icons/16/black/download.png",
                 "text" => $action_text3 );

    $info_array[] = array( "icon" => "icons/16/black/info.png",
               "text" => $info_text );

    $infobox = array( array( "kategorie" => _("Information:" ),
                 "eintrag" => $info_array
                 ),
              array( "kategorie" => _("Aktionen:" ),
                 "eintrag" => $action_array
                 )
              );

    echo "<td width=\"270\" align=\"center\" valign=\"top\">\n";
    print_infobox($infobox, "infobox/voting.jpg");

    echo "</td></tr>";
    echo "</table>";
}

/**
 * prints the tables for the runtime settings (start date, stop date...)
 *
 * @access  public
 * @param   string  $startMode    should be "manual" or "timeBased"
 * @param   string  $stopMode     should be "manual" or "timeBased" or "timeSpanBased"
 * @param   int     $startDate    a unix timestamp for the vote's start date
 * @param   int     $stopDate     a unix timestamp for the vote's stop date
 * @param   int     $timeSpan     a timespan for the vote _in seconds_
 *
 */

function printRuntimeSettings ( $startMode = "manual",
                $stopMode  = "manual",
                $startDate,
                $stopDate,
                $timeSpan ) {
    global $type, $pageMode;

    $checkManualStart = ""; $checkTimeStart = "";
    $checkManualStop = ""; $checkTimeStop = ""; $checkTimeSpanStop = "";

    switch( $startMode ) {
    case "manual":
    $checkManualStart = " checked"; break;
    case "timeBased":
    $checkTimeStart = " checked"; break;
    default:
    $checkImmediateStart = " checked"; break;
    }

    switch( $stopMode ) {
    case "manual":
    $checkManualStop = " checked"; break;
    case "timeBased":
    $checkTimeStop = " checked"; break;
    case "timeSpanBased":
    $checkTimeSpanStop = " checked"; break;
    default:
    $checkManualStop = " checked"; break;
    }

    if( ! $startDate || $startDate == -1 )
    $startDate = time();

    $startDay = date("d", $startDate);
    $startMonth = date("m", $startDate);
    $startYear = date("Y", $startDate);
    $startHour = date("H", $startDate);
    $startMinute = date("i", $startDate);

    if( ! $stopDate || $stopDate == -1 )
    $stopDate = mktime( 0, 0, 0, date("m") + 1, date("d"), date("Y") );

    $stopDay = date("d", $stopDate);
    $stopMonth = date("m", $stopDate);
    $stopYear = date("Y", $stopDate);
    $stopHour = date("H", $stopDate);
    $stopMinute = date("i", $stopDate);

    if( ! $timeSpan )
    $timeSpan = 1209600; // default: 2 weeks

    $html = "<table border=0 align=center cellspacing=3 cellpadding=2 width=\"100%\">\n";
    // some space
    $html .= "<tr><td colspan=2><font size=-3>&nbsp;</font></td></tr>\n";

    $html .= "<tr><td colspan=2 style=\"padding-bottom:0;\">\n";
    $html .= "<font size=-1><b>" . _("Einstellungen zur Laufzeit:") . "</b></font>";
    $html .= "<img class=\"text-top\" src=\"".Assets::image_path('icons/16/grey/info-circle.png')."\" "
    . tooltip( ($type=="test"
            ? _("Legen Sie hier fest, von wann bis wann der Test in Stud.IP öffentlich sichtbar sein soll.")
            : _("Legen Sie hier fest, von wann bis wann die Umfrage in Stud.IP öffentlich sichtbar sein soll.")),
           FALSE, TRUE )
    . ">";
    $html .= "</td></tr>";

    $html .= "<tr><td class=steel1 width=\"50%\" valign=top>"
    . "<table width=\"100%\" cellpadding=2 cellspacing=0 border=0>\n"
    . "<tr><th>" . _("Anfang") . "</th></tr>";

    if( $pageMode != MODE_RESTRICTED ) {
    $html .= "<tr><td><input type=radio name=startMode value=manual".$checkManualStart.">&nbsp;";
    $html .= "<font size=-1>" . _("sp&auml;ter manuell starten") . "</font>";
    $html .= "</td></tr>";

    $html .=  "<tr><td class=steelgraulight>";
    $html .= "<input type=radio name=startMode value=timeBased".$checkTimeStart.">&nbsp;";
    $html .= "<font size=-1>" . _("Startzeitpunkt:") . "</font>";
    $html .= "&nbsp;&nbsp;<input type=text name=startDay size=3 maxlength=2 value=\"".$startDay."\">&nbsp;.&nbsp;"
        . "<input type=text name=startMonth size=3 maxlength=2 value=\"".$startMonth."\">&nbsp;.&nbsp;"
        . "<input type=text name=startYear size=5 maxlength=4 value=\"".$startYear."\">&nbsp;"
        . sprintf( "<font size=-1>" . _("um %s Uhr") . "</font>",
               "&nbsp;<input type=text name=startHour size=3 maxlength=2 value=\"".$startHour."\">&nbsp;:".
               "&nbsp;<input type=text name=startMinute size=3 maxlength=2 value=\"".$startMinute."\">&nbsp;" );
    $html .= "</td></tr>";

    $html .= "<tr><td valign=middle>";
    $html .= "<input type=radio name=startMode value=immediate".$checkImmediateStart.">&nbsp;";
    $html .= "<font size=-1>" . _("sofort") . "</font>";
    $html .= "</td></tr>";
    }

    // restricted mode
    else {
    $html .= "<tr><td><font size=\"+2\">&nbsp;</font></td></tr>";
    $html .= "<tr><td valign=middle align=center><font size=-1>";
    $html .= sprintf( _("Startzeitpunkt war der <b>%s</b> um <b>%s</b> Uhr."),
              date("d.m.Y", $startDate ), date("H:i", $startDate) );
    $html .= "</font></td></tr>";
    }

    $html .= "</table></td>";

    $html .= "<td class=steel1 width=\"50%\">"

    . "<table width=\"100%\" cellpadding=2 cellspacing=0 border=0>\n"
    . "<tr><th>" . _("Ende") . "</th></tr>"
    . "<tr><td><input type=radio name=stopMode value=manual".$checkManualStop.">&nbsp;"
    . "<font size=-1>" . _("manuell beenden") . "</font>"
    . "</td></tr>"
    . "<tr><td class=steelgraulight><input type=radio name=stopMode value=timeBased".$checkTimeStop.">&nbsp;"
    . "<font size=-1>" . _("Endzeitpunkt:") . "</font>";

    $html .= "&nbsp;&nbsp;<input type=text name=stopDay size=3 maxlength=2 value=\"".$stopDay."\">&nbsp;.&nbsp;"
    . "<input type=text name=stopMonth size=3 maxlength=2 value=\"".$stopMonth."\">&nbsp;.&nbsp;"
    . "<input type=text name=stopYear size=5 maxlength=4 value=\"".$stopYear."\">&nbsp;"
    . sprintf( "<font size=-1>"._("um %s Uhr")."</font>",
           "&nbsp;<input type=text name=stopHour size=3 maxlength=2 value=\"".$stopHour."\">&nbsp;:".
           "&nbsp;<input type=text name=stopMinute size=3 maxlength=2 value=\"".$stopMinute."\">&nbsp;" );
    $html .= " <input type=hidden name=stopDate value=\"".$stopDate."\">"
    . "</td></tr><tr><td valign=middle><input type=radio name=stopMode value=timeSpanBased".$checkTimeSpanStop
    . " onClick=\"document.voteform.submit()\">&nbsp;"
    . "<font size=-1>" . _("Zeitspanne") . "</font>"
    . "&nbsp;&nbsp; <select name=timeSpan style=\"vertical-align:middle\" size=1 onChange=\"document.voteform.submit()\">";

    for ( $i=1; $i<=12; $i++ ) {
    $secs = $i * 604800;  // == weeks * seconds per week

    $html .= "\n<option value=\"" . $secs . "\" ";
    if( $timeSpan == $secs )
        $html .= "selected";
    $html .= ">";
    $html .= sprintf( $i==1 ? _("%s Woche") : _("%s Wochen"), $i );
    $html .= "</option>";
    }
    $html .= "</select>";

//  . " <input name=computedStopTime type=text size=15 value=\"("
//  . strftime( "%d.%m.%y, %H:%m", $startDate + $timeSpan )
//  . ")\" readonly> "

    if( $stopMode == "timeSpanBased" && $startMode != "manual" ) {

    $startDate = ($startMode=="immediate") ? time() : $startDate;

    $html .= " <input class=\"middle\" type=\"image\" name=\"updatetimespanbutton\" src=\""
         . Assets::image_path('icons/16/blue/refresh.png') ."\" ". tooltip(_("Endzeitpunkt neu berechnen.")) . ">";
    $html .= sprintf( _(" (<b>%s</b> um <b>%s</b> Uhr)"),
              strftime( "%d.%m.%Y", $startDate + $timeSpan ),
              strftime( "%H:%M", $startDate + $timeSpan ) );
    }

    $html .= "</td></tr></table>";
    $html .= "</td></tr></table>";

    echo $html;
}

/**
 * prints the table for the vote's properties
 *
 * @access  public
 * @param   bool $multipleChoice     whether multiple answers are allowed
 * @param   int  $resultVisibility   see VOTE_RESULTS_*
 * @param   bool $co_visibility      whether the user sees the correct answers right after voting (only if test)
 * @param   bool $anonymous          whether the vote is being treated anonymously or not
 * @param   bool $changeable         whether the user is allowed to change a given answer (only if not anonymous)
 *
 */

function printProperties ( $multipleChoice,
               $resultVisibility,
               $co_visibility,
               $anonymous,
               $namesVisibility,
               $changeable ) {
    global $type, $pageMode;

    // some space
    $html = "<table border=0 align=center cellspacing=3 cellpadding=2 width=\"100%\">\n"
    . "<tr><td colspan=2><font size=-3>&nbsp;</font></td></tr>\n"
    . "<tr><td colspan=2 style=\"padding-bottom:0;\">\n";

    $html .= "<b><font size=-1>" . _("Weitere Eigenschaften:") . "</font></b>"
    . "</td></tr>";

    $html .= "<tr><td class=steel1 width=\"100%\" valign=top>"
    . "<table width=\"100%\" cellpadding=2 cellspacing=0 border=0>\n"
    . "<tr><th width=\"50%\" align=center>" . _("Option") . "&nbsp; </th>"
    . "<th align=center> &nbsp;" . _("Auswahl") . "</th></tr>";

    // -------------------------------------------
    // multiple choice
    $html .= "<tr><td align=right class=blank style=\"border-bottom:1px dotted black;\">"
    . "<font size=-1>"
    . _("Die Auswahl mehrerer Antworten ist erlaubt <i>(Multiple Choice)</i>:")
    . "</font>"
    . "&nbsp;&nbsp;</td><td align=left><font size=-1>";
    if( $pageMode != MODE_RESTRICTED ) {
    $line1 = "<input type=radio value=\"".NO."\" name=multipleChoice ".
        ( (!$multipleChoice) ? "checked" : "" ) . "> ";
    $line2 = "<input type=radio value=\"".YES."\" name=multipleChoice ".
        ( ($multipleChoice) ? "checked" : "" ) . "> ";
    }
    else {
    $line1 = image_if_true( ! $multipleChoice );
    $line2 = image_if_true( $multipleChoice );
    }
    $html .= $line1 .  _("nein") . "<br>";
    $html .= $line2 .  _("ja");
    $html .= "</font></td></tr>";

    // -------------------------------------------
    // result visibility
    $html .= "<tr><td align=right class=blank style=\"border-bottom:1px dotted black;\">";
    if( $type == "test" ) {
    $html .= "<img class=\"text-top\" src=\"".Assets::image_path('icons/16/grey/info-circle.png')."\" "
        . tooltip(_("Bedenken Sie, dass die Einstellung 'immer', also eine Voransicht des Zwischenstands, bei einem Test nicht unbedingt sinnvoll ist."),
              FALSE, TRUE)
        . "> ";
    }

    $html .= "<font size=-1>";
    $html .= _("Der Teilnehmer sieht die (Zwischen-)Ergebnisse:");
    $html .= "</font>";
    $html .= "&nbsp;&nbsp;</td><td align=left class=steelgraulight><font size=-1>";

#    if( $pageMode != MODE_RESTRICTED ) {
    $line1 = "<input type=radio value=" . VOTE_RESULTS_ALWAYS . " name=resultVisibility ".
        ( ($resultVisibility == VOTE_RESULTS_ALWAYS) ? "checked" : "" ) . "> ";
    $line2 = "<input type=radio value=" . VOTE_RESULTS_AFTER_VOTE . " name=resultVisibility ".
        ( ($resultVisibility == VOTE_RESULTS_AFTER_VOTE) ? "checked" : "" ) . "> ";
    $line3 = "<input type=radio value=" . VOTE_RESULTS_AFTER_END . " name=resultVisibility ".
        ( ($resultVisibility == VOTE_RESULTS_AFTER_END) ? "checked" : "" ) . "> ";
    $line4 = "<input type=radio value=" . VOTE_RESULTS_NEVER . " name=resultVisibility ".
        ( ($resultVisibility == VOTE_RESULTS_NEVER) ? "checked" : "" ) . "> ";
#    }
#    else {
#   $line1 = image_if_true( $resultVisibility == VOTE_RESULTS_ALWAYS );
#   $line2 = image_if_true( $resultVisibility == VOTE_RESULTS_AFTER_VOTE );
#   $line3 = image_if_true( $resultVisibility == VOTE_RESULTS_AFTER_END );
#   $line4 = image_if_true( $resultVisibility == VOTE_RESULTS_NEVER );
#    }

    $html .= $line1 . _("immer") . "<br>";
    $html .= $line2 . _("erst nachdem er seine Stimme(n) abgegeben hat") . "<br>";
    $html .= $line3 . (($type=="test") ? _("erst nach Ablauf des Tests") : _("erst nach Ablauf der Umfrage")) . "<br>";
    $html .= $line4 . _("nie");
    $html .= "</font></td></tr>";

    // -------------------------------------------
    // correct answers visibility
    if( $type == "test" ) {
    $html .= "<tr><td align=right class=blank style=\"border-bottom:1px dotted black;\">"
        . "<font size=-1>"
        . _("Der Teilnehmer sieht, ob seine Antwort(en) richtig war(en):")
        . "</font>"
        . "&nbsp;&nbsp;</td><td align=left class=steel1>"
        . "<font size=-1>";

    if( $pageMode != MODE_RESTRICTED ) {
        $line1 = "<input type=radio value=\"".YES."\" name=co_visibility ".
        ( ($co_visibility) ? "checked" : "" ) . "> ";
        $line2 = "<input type=radio value=\"".NO."\" name=co_visibility ".
        ( (!$co_visibility) ? "checked" : "" ) . "> ";
    }
    else {
        $line1 = image_if_true( $co_visibility );
        $line2 = image_if_true( ! $co_visibility );
    }

    $html .= $line1 . _("sofort") . "<br>";
    $html .= $line2 . _("erst nach Ablauf des Tests");

    $html .= "<br>";
    $html .= "</font></td></tr>";
    }

    // -------------------------------------------
    // anonymity
    $html .= "<tr><td align=right class=blank style=\"border-bottom:1px dotted black;\">";
    $html .= "<img class=\"text-top\" src=\"".Assets::image_path('icons/16/grey/info-circle.png')."\" "
    . tooltip(_("'Anonym' bedeutet, dass niemandem angezeigt und nirgends gespeichert wird, welche Antwort ein Teilnehmer wählt. \n\n'Personalisiert' bedeutet, dass Sie sehen können, wer wofür stimmt."), FALSE, TRUE)
    . "> ";

    $html .= "<font size=-1>";
    $html .= ($type=="test")
    ? _("Die Auswertung des Tests l&auml;uft:") . "</font>&nbsp;&nbsp;</td><td align=left class=steelgraulight>"
    : _("Die Auswertung der Umfrage l&auml;uft:") . "</font>&nbsp;&nbsp;</td><td align=left class=steel1>";
    $html .= "<font size=-1>";

    if( $pageMode != MODE_RESTRICTED ) {
    $line1 = "<input type=radio value=\"".YES."\" name=anonymous ".
        ( ($anonymous) ? "checked" : "" ) . " onClick=\"toggleChangeable();\"> ";
    $line2 = "<input type=radio value=\"".NO."\" name=anonymous ".
        ( (!$anonymous) ? "checked" : "" ) . " onClick=\"toggleChangeable();\"> ";
    }
    else {
    $line1 = image_if_true( $anonymous );
    $line2 = image_if_true( ! $anonymous );
    }
    $html .= $line1 . _("anonym"). "<br>";
    $html .= $line2 . _("personalisiert");
    $html .= "</font></td></tr>";

    // -------------------------------------------
    // names visibility
    $html .= "<tr><td align=right class=blank style=\"border-bottom:1px dotted black;\">";
    $html .= "<img class=\"text-top\" src=\"".Assets::image_path('icons/16/grey/info-circle.png')."\" "
    . tooltip(_("Diese Option ist nur möglich, wenn Sie die Auswertung auf 'personalisiert' schalten, und wenn die Ergebnissichtbarkeit nicht auf 'nie' steht. "),
          FALSE, TRUE)
    . "> ";

    $html .= "<font size=-1>";
    $html .= _("Die Namen der Teilnehmer werden &ouml;ffentlich sichtbar gemacht:") . "</font>&nbsp;&nbsp;";
    $html .= ($type == "test")
    ? "</td><td align=left class=steel1>"
    : "</td><td align=left class=steelgraulight>";
    $html .= "<font size=-1>";

    $line1 = "<input type=radio value=\"".YES."\" name=namesVisibility ".( $namesVisibility ? "checked" : "" )."> ";
    $line2 = "<input type=radio value=\"".NO."\" name=namesVisibility ".( !$namesVisibility ? "checked" : "" )."> ";

    $html .= $line1 . _("ja"). "<br>";
    $html .= $line2 . _("nein");
    $html .= "</font></td></tr>";

    // -------------------------------------------
    // changeable?
    if( ! ($anonymous && $pageMode == MODE_RESTRICTED ) ) {
    $html .= "<tr><td align=right class=blank>";
    $html .= "<img class=\"text-top\" src=\"".Assets::image_path('icons/16/grey/info-circle.png')."\" "
        . tooltip(_("Diese Option ist nur möglich, wenn Sie die Auswertung auf 'personalisiert' schalten. ").
              ( ($type=="test")
            ? _("\n\nBeachten Sie außerdem, dass das Einschalten dieser Option in Kombination mit 'Richtigkeits-Anzeige: sofort' keinen Sinn macht.")
            : "" ),
              FALSE, TRUE)
        . "> ";

    $html .= "<font size=-1>";
    $html .= _("Der Teilnehmer darf seine gegebene(n) Antwort(en) beliebig oft revidieren:");
    $html .= "</font>&nbsp;&nbsp;";

    $html .= ($type == "test")
        ? "</td><td align=left class=steelgraulight>"
        : "</td><td align=left class=steel1>";
    $html .= "<font size=-1>";

    $line1 = "<input type=radio value=\"".NO."\" name=changeable ".
        ( (!$changeable) ? "checked" : "" ) . "> ";
    $line2 = "<input type=radio value=\"".YES."\" name=changeable ".
        ( ($changeable) ? "checked" : "" ) . "> ";

    $html .= $line1 . _("nein") ."<br>";
    $html .= $line2 . _("ja");

    $html .= "</font></td></tr>";
    }

    $html .= "</table>";

    $html .= "</td></tr>";
    $html .= "</table>";

    echo $html;
}


/**
 * prints the end of the edit form
 * ... closes <td><tr><table>
 *
 * @access  public
 *
 */
function printFormEnd ( ) {
    global $pageMode;

    $html = "</form>" . createBoxFooter();

    if( $pageMode != MODE_RESTRICTED )
    $html .= "<script type=\"text/javascript\" language=\"JavaScript\"><!--\n"
        . "toggleChangeable();\n"
        . "//--></script>\n";

    $html .= "</body></html>";


    echo $html;
}



/**
 * prints a cross image or a blank image
 *
 * @access  public
 * @param   bool $option    the condition
 * @returns string          the HTML <img.. tag
 */
function image_if_true($option)
{
    if ($option)
        return Assets::img('icons/16/grey/decline.png');
    else
        return " <img width=16 height=16 src=\"".Assets::image_path('blank.gif')."\"> ";
}

?>

