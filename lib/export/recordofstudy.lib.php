<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/**

 * Creates a record of study and exports the data to pdf (html-outpu)

 *

 * @author      Christian Bauer <alfredhitchcock@gmx.net>


 * @copyright   2003 Stud.IP-Project

 * @access      public

 * @module      recordofstudy

 */

/**
 * displays the site title
 *
 * @access  private
 * @param   string $semester    the current semester (edit-mode) (optional)
 *
 */

use Studip\Button, Studip\LinkButton;

function printSiteTitle($semester = NULL){
    $html = "<table border=0 class=blank align=center cellspacing=0 cellpadding=0 width=\"100%\">\n"
          . "   <tr valign=top align=center>\n"
          . "    <td class=table_header_bold align=left colspan=\"2\">\n"
          . "     " . Assets::img('icons/16/white/seminar.png', array('class' => 'text-top', 'title' =>_('Veranstaltungs�bersicht erstellen'))) . "\n"
          . "     &nbsp;<b>"._("Veranstaltungs�bersicht erstellen:")."</b>\n"
          . "     <font size=\"-1\">" . htmlReady($semester) . "</font>\n"
          . "    </td>\n"
          . "   </tr>\n"
          . "</table>\n";
    echo $html;
}

/**
 * displays the semester selection page
 *
 * @access  private
 * @param   array $infobox      the infobox for this site
 * @param   array $semestersAR  the array with the semesters to select
 *
 */
function printSelectSemester($infobox,$semestersAR){
    global $record_of_study_templates;
    $html = "<table border=\"0\" class=\"blank\" cellspacing=\"0\" cellpadding=\"0\" width=\"100%\">\n"
          . " <tr valign=\"top\">\n"
          . "  <td class=\"blank\" id=\"main_content\">\n"
          . "   <table align=\"center\" class=\"blank\" border=\"0\" cellpadding=\"2\" cellspacing=3>\n"
          . "    <tr>"
          . "     <td><font size=\"-1\">\n"
          . MessageBox::info($GLOBALS['FDF_USAGE_HINT'])
          . _("Bitte w�hlen Sie ein Semester aus:")."\n"
          . "      <form action=\"".$_SERVER['PHP_SELF']."\" method=post>\n"
          . CSRFProtection::tokenTag()
          . "       &nbsp;<select name=\"semesterid\" style=\"vertical-align:middle;\">\n";
    // the semester
    foreach ($semestersAR as $semester){
        $html .= "        <option value=\"".$semester["id"]."\">".htmlReady($semester["name"])."</option>\n";
    }
    $html .="       </select>\n"
          . Button::create(_('Ausw�hlen'), 'semester_selected', array('title' => _("Semester und Kriterium ausw�hlen.")))
          . "       <br><br>&nbsp;<select name=\"onlyseminars\" style=\"vertical-align:middle;\">\n"
          . "        <option value=\"1\" selected>"._("nur Lehrveranstaltungen")."</option>\n"
          . "        <option value=\"0\">"._("alle Veranstaltungen")."</option>\n"
          . "       </select>\n";
    if(sizeof($record_of_study_templates)>1){
        $html .="       <br><br>&nbsp;". _("Vorlage").": <select name=\"template\" style=\"vertical-align:middle;\">\n";
        for ($i=1;$i<=sizeof($record_of_study_templates);$i++){
            $html .="        <option value=\"".$i."\">".htmlReady($record_of_study_templates[$i]["title"])."</option>\n";
        }
        $html .="       </select>\n";
    } else {
        $html .=" <input type=\"hidden\" name=\"template\" value=\"1\">\n";
    }
    $html .="      </form>\n"
          . "     </font></td>\n"
          . "    </tr>\n"
          . "   </table>\n"
          . "  </td>\n"
          . "     <td align=\"right\" width=\"270\" valign=\"top\">\n";
    echo $html;
    print_infobox($infobox, "infobox/folders.jpg");
    $html = "     <br></td>\n"
          . " </tr>\n"
          . "</table>\n";
    echo $html;
}

/**
 * displays the edit page
 *
 * @access  private
 * @param   array  $infobox     the infobox for this site
 * @param   array  $basicdata   the basic data for the form
 * @param   array  $seminare    the seminars for the form
 * @param   string $notice      a notice for the user (optional)
 *
 */
function printRecordOfStudies($infobox, $basicdata, $seminare, $notice = NULL){
    global $semesterid;
    $html = "<table border=\"0\" class=\"blank\" cellspacing=\"0\" cellpadding=\"0\" width=\"100%\">\n"
        . " <form action=\"{$_SERVER['PHP_SELF']}\" method=post>\n"
        . CSRFProtection::tokenTag()
        . " <input type=\"hidden\" name=\"semesterid\" value=\"".$semesterid."\">\n"
        . " <tr valign=\"top\">\n"
        . "  <td width=\"99%\" class=\"blank\">&nbsp;\n"
        . "   <table align=\"center\" width=\"99%\" class=\"blank\" border=\"0\" cellpadding=\"0\" cellspacing=0>\n"
        . "  <tr>"
        . "   <td valign=\"top\" id=\"main_content\">"
        . "    <table align=\"center\" width=\"100%\" class=\"blank\" border=\"0\" cellpadding=\"0\" cellspacing=0>\n";

    // displays some infos for the user
    if ($notice)
    {
        $html .="       <tr>\n"
              . "        <td colspan=\"4\">\n";
        if ($notice == "empty")
            $html .= MessageBox::info(_("Keine Veranstaltungen zum Anzeigen vorhanden."), array(_("Bitte f�gen Sie Veranstaltungen mit Hilfe des Buttons \"hinzuf�gen\" ein oder �ndern Sie Ihre Auswahl.")));
        elseif ($notice == "above_limit")
            $html .= MessageBox::info(_("Sie haben mehr als 10 Veranstaltungen in diesem Semester ausgew�hlt."), array(_("Es werden automatisch mehrere Veranstaltungs�bersichtseiten erstellt.")));
        $html .="        <br></td>\n"
              . "       </tr>\n";
    }


    $html .=createInputBox(_("Hochschule: "), $basicdata["university"], "university", "table_row_odd",     "60")
          . createInputBox(_("Studienfach: "), $basicdata["fieldofstudy"],   "fieldofstudy",  "table_row_odd",     "60")
          . createInputBox(_("Name (Vor- und Zuname): "), $basicdata["studentname"],     "studentname",   "table_row_odd",     "60")
          . createInputBox(_("Semester: "), $basicdata["semester"],      "semester",      "content_seperator",        "30")
          . createInputBox(_("Fachsemester: "), $basicdata["semesternumber"],"semesternumber","table_row_even",             "2", "tes Fachsemester");

    $html .="       <tr>\n"
          . "        <td colspan=\"4\"><font size=\"-1\"><b><br>\n"
          . _("Veranstaltungen:")."\n"
          . "        </b></font></td>\n"
          . "       </tr>\n"
          . "       <tr>\n"
          . createSeminarHeadTD(_("Kenn.-Nr"))
          . createSeminarHeadTD(_("Name des Dozenten"))
          . createSeminarHeadTD(_("Wochenstundenzahl"), "center")
          . createSeminarHeadTD(_("l�schen"), "center")
          . "       </tr>\n";

  if (!empty($seminare)){
    for($i=0;$i+1<=sizeof($seminare);$i++){
        if (($i % 2) == 0)  $displayclass = "table_row_even";
        else                $displayclass = "table_row_odd";
    $html .="       <tr>\n"
          . "        <td class=\"$displayclass\" height=\"40\"><font size=\"-1\">\n"
          . "         &nbsp;<input name=\"seminarnumber$i\" type=\"text\" size=\"6\" maxlength=\"6\" value=\"".htmlReady($seminare[$i]["seminarnumber"])."\">\n"
          . "        </td>\n"
          . "        <td class=\"$displayclass\"><font size=\"-1\">\n"
          . "         &nbsp;<input name=\"tutor$i\" type=\"text\" size=\"70\" maxlength=\"70\" value=\"".$seminare[$i]["tutor"]."\">\n"
          . "         \n"
          . "        </td>\n"
          . "        <td class=\"$displayclass\" align=\"center\"><font size=\"-1\">\n"
          . "         &nbsp;<input name=\"sws$i\" type=\"text\" size=\"2\" maxlength=\"2\" value=\"".htmlReady($seminare[$i]["sws"])."\">"._("SWS")."\n"
          . "        </td>\n"
          . "        <td class=\"$displayclass\" rowspan=\"2\" align=\"center\">\n"
          . "         &nbsp;<input type=\"checkbox\" name=\"delete$i\" value=\"1\">\n"
          . "        </td>\n"
          . "       </tr>\n"
          . "       <tr>\n"
          . "        <td class=\"$displayclass\" colspan=\"3\"><font size=\"-1\" align=\"top\">\n"
          . "         &nbsp;<b>"._("Genaue Bezeichnung:")."</b><br>&nbsp;<textarea name=\"description$i\" cols=\"60\" rows=\"2\">".htmlReady($seminare[$i]["description"])."</textarea>\n"
          . "        &nbsp;<br><br></td>\n"
          . "       </tr>\n";
    }
    // delivers the seminar_max
    $seminare_max = $i;
    $html.="         <input type=\"hidden\" name=\"seminare_max\" value=\"".$seminare_max."\">\n";

    }

    $html .="       <tr>\n"
          . "        <td colspan=\"4\"><font size=\"-1\"><br><table width=\"100%\"><tr><td align=\"left\">\n"
          . Button::create(_('Hinzuf�gen'), 'add_seminars', array('title' => _('Neue Veranstaltung hinzuf�gen.')))
          . "         <select style=\"vertical-align:middle;\" name=\"newseminarfields\" size=1>\n";
    for( $i=1; $i<=10; $i++ )
        $html .= "        <option>$i</option>\n";
    $html .="         </select>\n"
          . "        </font></td>\n"
          . "        <td align=right><font size=\"-1\" style=\"vertical-align:middle;\">\n";

    // only show delete-button if there are any seminars
    if(!empty($seminare))
        $html .= _("Markierte Veranstaltung(en) l�schen")."\n" . Button::create(_('L�schen'), 'delete_seminars', array('title' => _("Markierte Veranstaltung(en) l�schen.")));
    $html .="        </font></td></tr></table>\n"
          . "       </tr>\n"
          . "      </table>\n"
          . "     </td>\n";

    // the right site of the page
    $html .="     <td class=\"blank\" width=\"270\" valign=\"top\" align=\"center\">\n";
    echo $html;
    print_infobox($infobox, "infobox/folders.jpg");
    $html = "      <br>\n"
          . Button::create('<< '._('Zur�ck'), 'select_new_semester', array('title' => _("Abbrechen und ein anderes Semester ausw�hlen.")))
          . Button::create(_('Weiter').' >>', 'collect_information', array('title' => _("Weiter zum Download Ihrer Veranstaltungs�bersicht.")))
          . "     <br><br></td>\n"
          . "    </tr>\n"
          . "   </table>\n"
          . "  </td>\n"
          . " </tr>\n"
          . " </form>\n"
          . "</table>\n";
    echo $html;
}

/**
 * displays the site in which the user can download the pdf
 *
 * @access  private
 * @param   array  $infobox     the infobox for this site
 * @param   array  $seminars    the seminars to export
 *
 */
function printPdfAssortment($infobox,$seminars){
    global $record_of_study_templates, $template;
    $html = "<table border=\"0\" class=\"blank\" cellspacing=\"0\" cellpadding=\"0\" width=\"100%\">\n"
          . " <tr valign=\"top\">\n"
          . "  <td class=\"blank\">&nbsp;\n"
          . "   <table align=\"center\" width=\"99%\" class=\"blank\" border=\"0\" cellpadding=\"0\" cellspacing=0>\n"
          . "    <tr>\n"
          . "     <td align=\"left\" valign=\"top\" id=\"main_content\"><font size=\"-1\">\n"
          . sprintf(_("Sie haben %s Eintr�ge f�r Ihre Veranstaltungs�bersicht ausgew�hlt."),$seminars["numberofseminars"]);
    $html .= ($seminars["numberofpages"]>1)
          ? sprintf(_("Deshalb werden Ihre Eintr�ge auf %s Seiten verteilt."),$seminars["numberofpages"])."\n"
          : sprintf(_("Ihre Eintr�ge k�nnen auf einer Seite untergebracht werden."),$seminars["numberofseminars"])."\n";
    $html .="     <br><br>\n"
          . _("Ihre Studiendaten:")."<br>\n"
          . "&nbsp;" . _("Hochschule: ") . htmlReady($seminars["university"]) . "<br>\n"
          . "&nbsp;" . _("Studienfach: ") . htmlReady($seminars["fieldofstudy"]) . "<br>\n"
          . "&nbsp;" . _("Name (Vor- und Zuname): ") . htmlReady($seminars["studentname"]) . "<br>\n"
          . "&nbsp;" . _("Semester: ") . htmlReady($seminars["semester"]) . "<br>\n"
          . "&nbsp;" . _("Fachsemester: ") . htmlReady($seminars["semesternumber"]) . "<br>\n"
          . "<br>\n"
          . _("Vorlage:") ." ". htmlReady($record_of_study_templates[$template]["title"]) . "\n"
          . "<br><br>\n";

    $html .= ($seminars["numberofpages"]>1)
          ? _("Klicken Sie nun auf die einzelnen Links, um Ihre Veranstaltungs�bersicht zu erstellen.")."\n"
          : _("Klicken Sie nun auf den Link, um Ihre Veranstaltungs�bersicht zu erstellen.")."\n";

    $html .="     <br>\n";
    if ($seminars["numberofpages"]>1)
        $html .= _("Veranstaltungs�bersicht: ");
    for($i=1;$i<=$seminars["numberofpages"];$i++){
        $html .="     <a href=\"recordofstudy.php?create_pdf=1&page=$i\" target=\"_blank\">\n";
        $html .= ($seminars["numberofpages"]>1)
              ? sprintf(_("Seite %s"),$i)
              : _("Veranstaltungs�bersicht");
        $html .=" </a>";
    }

    $html .="     </font></td>\n"
          . "     <td align=\"right\" width=\"270\" valign=\"top\">\n";
    echo $html;
    print_infobox($infobox, "infobox/folders.jpg");
//  $html = "     <form action=\"$PHP_SELF\" method=post>"
//        . "     <center>\n"
//        . "       <a href=\"recordofstudy.php\">\n"
//        . "        "._("Zur�ck zur Semesterauswahl")."\n"
//        . "       </a>\n"
//        . createButton("speichern",_("Erstellt Sie mit diesem Button ein PDF, wenn Sie die ben�tigten Daten eingegeben haben."),"create_pdf")
//        . createButton("zurueck",_("Abbrechen und eine Studienbuchseite f�r ein anderes Semester erstellen."),"select_new_semester")
//        . "     <br><br></center></form></td>\n"

    $html = "     </td>\n"
          . "    </tr>\n"
          . "   </table>\n"
          . "  <br></td>\n"
          . " </tr>\n"
          . "</table>\n";
    echo $html;
}

/**
 * creates a complete <tr> with a label and an input-box
 *
 * @access  private
 * @param   string $text    the label
 * @param   string $value   the input box value
 * @param   string $name    the input box name
 * @param   string $class   the <td> class
 * @param   string $size    the $size of the input box
 * @param   string $additionaltext  an additonal text (optional)
 * @returns string          the button
 */
function createInputBox($text, $value, $name, $class, $size, $additionaltext = NULL){
    $html = "    <tr>\n"
          . "     <td class=\"".$class."\" colspan=\"4\" width=\"99%\"><font size=\"-1\">\n"
          . "      &nbsp;".$text."<br><input name=\"".$name."\" type=\"text\" size=\"".$size."\" maxlength=\"".$size."\" value=\"".htmlReady($value)."\">".htmlReady($additionaltext)."\n"
          . "     </font></td>\n"
          . "    </tr>\n";

    return $html;
}

/**
 * creates a <td> with a label
 *
 * @access  private
 * @param   string $text    the label
 * @param   string $align   the align (optional)
 * @returns string          the <td> head
 */
function createSeminarHeadTD($text, $align = "left"){
    $html = "        <td class=\"table_header\" height=\"26\" align=\"".$align."\" style=\"vertical-align:bottom;\" ><font size=\"-1\"><b>\n"
          . "         &nbsp;".htmlReady($text)."\n"
          . "        </font></b></td>\n";
    return $html;
}

?>
