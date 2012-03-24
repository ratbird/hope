<?php
# Lifter002: TODO
# Lifter005: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* eval_config.php
*
* Konfiurationsseite fuer Eval-Auswertungen
*
*
* @author               Jan Kulmann <jankul@tzi.de>
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// eval_config.php
// Copyright (C) 2005 Jan Kulmann <jankul@tzi.de>
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

use Studip\Button, Studip\LinkButton;

require '../lib/bootstrap.php';

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));

$perm->check('user');

include ('lib/seminar_open.php');             // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page
require_once('lib/msg.inc.php');
require_once('lib/visual.inc.php');
require_once('config.inc.php');
require_once 'lib/functions.php';
require_once('lib/evaluation/evaluation.config.php');
require_once(EVAL_FILE_EVAL);
require_once(EVAL_FILE_OBJECTDB);

// Start of Output
PageLayout::setTitle(_('Evaluations-Auswertung'));
PageLayout::setHelpKeyword('Basis.Evaluationen');
Navigation::activateItem('/tools/evaluation');

$eval = new Evaluation($eval_id);
$no_permissons = EvaluationObjectDB::getEvalUserRangesWithNoPermission ($eval);

// Gehoert die benutzende Person zum Seminar-Stab (Dozenten, Tutoren) oder ist es ein ROOT?
$staff_member = $perm->have_studip_perm("tutor",$SessSemName[1]);;

include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');    //hier wird der "Kopf" nachgeladen


$graphtypes_mchoice = array("bars"=>"Balken",
            "points"=>"Punkte",
            "thinbarline"=>"Linienbalken"
            );

$graphtypes_polscale = array("bars"=>"Balken",
            "pie"=>"Tortenstücke",
            "lines"=>"Linien",
            "linepoints"=>"Linienpunkte",
            "area"=>"Bereich",
            "points"=>"Punkte",
            "thinbarline"=>"Linienbalken"
            );

$graphtypes_likertscale = array("bars"=>"Balken",
            "pie"=>"Tortenstücke",
            "lines"=>"Linien",
            "linepoints"=>"Linienpunkte",
            "area"=>"Bereich",
            "points"=>"Punkte",
            "thinbarline"=>"Linienbalken"
            );

$db = new DB_Seminar();
$can_change = FALSE;
// Pruefen, ob die Person wirklich berechtigt ist, hier etwas zu aendern...
if ($staff_member)
        $db->query(sprintf("SELECT * FROM eval WHERE eval_id='%s'",$eval_id));
else
        $db->query(sprintf("SELECT * FROM eval WHERE eval_id='%s' AND author_id='%s'",$eval_id,$auth->auth["uid"]));

if ($db->next_record()) $can_change = TRUE; // Person darf etwas aendern....

/**
 * Creates an infobox with image
 * @access public
 * @param  string  $imgLogo  The big logo at the top
 */
function createInfoBox ($imgLogo) {
        /* Define infobox text ------------------------------------------------ */
        $info1 =  array ("icon" => "icons/16/black/vote.png",
                "text" => _("Auf dieser Seite k&ouml;nnen Sie die Auswertung Ihrer Evaluation konfigurieren."));
    $info2 = array ("icon" => "icons/16/black/info.png",
            "text" => _("W&auml;hlen Sie Ihre Einstellungen und dr&uuml;cken Sie auf \"Template speichern\". Anschlie&szlig;end kommen Sie mit dem Button unten links zur&uuml;ck zu Ihrer Evaluation."));

    $infobox = array (array ("kategorie" => _("Information:"),
            "eintrag"   => array ($info1, $info2)));
    /* ------------------------------------------------------- end: infobox */
    return print_infobox ($infobox, 'infobox/'.$imgLogo, YES);
}



if (isset($cmd) && $can_change && isset($eval_id)) {
    if ($cmd=="save") {
        $db = new DB_Seminar();
        if (!isset($template_id) || $template_id=="") {
            // Neues Template einfuegen
            $template_id=DbView::get_uniqid();
            $db->query(sprintf("INSERT INTO eval_templates (template_id,user_id,name,show_questions,show_total_stats,show_graphics,show_questionblock_headline,show_group_headline,polscale_gfx_type,likertscale_gfx_type,mchoice_scale_gfx_type) VALUES ('%s','%s','nix',%d,%d,%d,%d,%d,'%s','%s','%s')",$template_id,$auth->auth["uid"],$show_questions,$show_total_stats,$show_graphics,$show_questionblock_headline,$show_group_headline,$polscale_gfx_type,$likertscale_gfx_type,$mchoice_scale_gfx_type));
            $db->query(sprintf("INSERT INTO eval_templates_eval (eval_id, template_id) VALUES ('%s', '%s')", $eval_id, $template_id));
            $msg .= "msg§"._("Template wurde neu erzeugt.");
        } else {
            // Bestehendes Template updaten
            $db->query(sprintf("UPDATE eval_templates SET show_questions=%d,show_total_stats=%d,show_graphics=%d,show_questionblock_headline=%d,show_group_headline=%d,polscale_gfx_type='%s',likertscale_gfx_type='%s',mchoice_scale_gfx_type='%s' WHERE template_id='%s'",$show_questions,$show_total_stats,$show_graphics,$show_questionblock_headline,$show_group_headline,$polscale_gfx_type,$likertscale_gfx_type,$mchoice_scale_gfx_type,$template_id));
            $msg .= "msg§".("Template wurde ver&auml;ndert.");
        }
    }
}


$cssSw=new cssClassSwitcher;

if (isset($eval_id) && $can_change) {

    $db_template = new DB_Seminar();

    $db_template->query(sprintf("SELECT t.* FROM eval_templates t, eval_templates_eval te WHERE te.eval_id='%s' AND t.template_id=te.template_id",$eval_id));
    $db_template->next_record();

    echo "<table border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\">";
    echo "<tr><td class=\"topic\" colspan=\"4\" align=\"left\">";
    echo Assets::img('icons/16/white/test.png');
    echo "<b>"._("Auswertungskonfiguration")."</b></td></tr>\n";
    echo "  <tr>";
    echo "    <td colspan=\"4\" class=\"blank\">&nbsp;</td>\n";
    echo "  </tr>";
    echo "  <tr>";
    echo "    <td class=\"blank\" width=\"1%\">&nbsp;</td>\n";
    echo "<form name=\"temp\" action=\"".$PHP_SELF."\" method=\"POST\">\n";
    echo CSRFProtection::tokenTag();
    echo "    <td class=\"blank\">\n";
    echo "  <input type=\"hidden\" name=\"cmd\" value=\"\">\n";
    echo "  <input type=\"hidden\" name=\"template_id\" value=\"".$db_template->f("template_id")."\">\n";
    echo "  <input type=\"hidden\" name=\"eval_id\" value=\"".$eval_id."\">\n";
    echo "<table width=\"95%\" align=\"LEFT\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
    echo "    <td class=\"blank\">&nbsp;</td>\n";
    parse_msg($msg);
    echo "  <tr>\n";
    echo "    <td class=\"steel1\" width=\"40%\"><font color=\"-1\"><b>"._("Optionen")."</b></font></td>\n";
    echo "    <td class=\"steel1\" width=\"10%\" align=\"CENTER\"><font color=\"-1\"><b>"._("Ja")."</b></font></td>\n";
    echo "    <td class=\"steel1\" width=\"10%\" align=\"CENTER\"><font color=\"-1\"><b>"._("Nein")."</b></font></td>\n";
    echo "    <td class=\"steel1\">&nbsp;</td>\n";
    echo "  </tr>\n";
    echo "  <tr>\n";
    echo "    <td class=\"steelkante\" colspan=\"4\">&nbsp;</td>\n";
    echo "  </tr>\n";
    echo "  <tr>\n";
    echo "    <td class=\"".$cssSw->getClass()."\"><font color=\"-1\">"._("Zeige Gesamtstatistik an").":</font></td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\" align=\"CENTER\"><font color=\"-1\"><input type=\"radio\" name=\"show_total_stats\" value=\"1\" "; if ($db_template->f("show_total_stats")=="1" || !($has_template)) echo "CHECKED"; print "></font></td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\" align=\"CENTER\"><font color=\"-1\"><input type=\"radio\" name=\"show_total_stats\" value=\"0\" "; if ($db_template->f("show_total_stats")=="0") echo "CHECKED"; print "></font></td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\">&nbsp;</td>\n";
    echo "  </tr>\n";

    $cssSw->switchClass();

    echo "  <tr>\n";
    echo "    <td class=\"".$cssSw->getClass()."\"><font color=\"-1\">"._("Zeige Grafiken an").":</font></td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\" align=\"CENTER\"><font color=\"-1\"><input type=\"radio\" name=\"show_graphics\" value=\"1\" "; if ($db_template->f("show_graphics")=="1" || !($has_template)) echo "CHECKED"; print "></font></td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\" align=\"CENTER\"><font color=\"-1\"><input type=\"radio\" name=\"show_graphics\" value=\"0\" "; if ($db_template->f("show_graphics")=="0") echo "CHECKED"; print "></font></td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\">&nbsp;</td>\n";
    echo "  </tr>\n";

    $cssSw->switchClass();

    echo "  <tr>\n";
    echo "    <td class=\"".$cssSw->getClass()."\"><font color=\"-1\">"._("Zeige Fragen an").":</font></td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\" align=\"CENTER\"><font color=\"-1\"><input type=\"radio\" name=\"show_questions\" value=\"1\" "; if ($db_template->f("show_questions")=="1" || !($has_template)) echo "CHECKED"; print "></font></td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\" align=\"CENTER\"><font color=\"-1\"><input type=\"radio\" name=\"show_questions\" value=\"0\" "; if ($db_template->f("show_questions")=="0") echo "CHECKED"; print "></font></td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\">&nbsp;</td>\n";
    echo "  </tr>\n";

    $cssSw->switchClass();

    echo "  <tr>\n";
    echo "    <td class=\"".$cssSw->getClass()."\"><font color=\"-1\">"._("Zeige Gruppen&uuml;berschriften an").":</font></td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\" align=\"CENTER\"><font color=\"-1\"><input type=\"radio\" name=\"show_group_headline\" value=\"1\" "; if ($db_template->f("show_group_headline")=="1" || !($has_template)) echo "CHECKED"; print "></font></td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\" align=\"CENTER\"><font color=\"-1\"><input type=\"radio\" name=\"show_group_headline\" value=\"0\" "; if ($db_template->f("show_group_headline")=="0") echo "CHECKED"; print "></font></td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\">&nbsp;</td>\n";
    echo "  </tr>\n";

    $cssSw->switchClass();

    echo "  <tr>\n";
    echo "    <td class=\"".$cssSw->getClass()."\"><font color=\"-1\">"._("Zeige Fragenblock&uuml;berschriften an").":</font></td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\" align=\"CENTER\"><font color=\"-1\"><input type=\"radio\" name=\"show_questionblock_headline\" value=\"1\" "; if ($db_template->f("show_questionblock_headline")=="1" || !($has_template)) echo "CHECKED"; print "></font></td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\" align=\"CENTER\"><font color=\"-1\"><input type=\"radio\" name=\"show_questionblock_headline\" value=\"0\" "; if ($db_template->f("show_questionblock_headline")=="0") echo "CHECKED"; print "></font></td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\">&nbsp;</td>\n";
    echo "  </tr>\n";

    $cssSw->switchClass();

    echo "  <tr>\n";
    echo "    <td class=\"".$cssSw->getClass()."\"><font color=\"-1\">"._("Grafiktyp f&uuml;r Polskalen").":</font></td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\" align=\"CENTER\" colspan=\"2\">\n";
    echo "      <select name=\"polscale_gfx_type\" size=\"1\" style=\"width:120px\">\n";
    foreach ($graphtypes_polscale as $k=>$v) {
        echo "        <option value=\"".$k."\""; if ($db_template->f("polscale_gfx_type")==$k) print " SELECTED"; print ">".$v."\n";
    }
    echo "      </select>\n";
    echo "    </td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\">&nbsp;</td>\n";
    echo "  </tr>\n";

    $cssSw->switchClass();

    echo "  <tr>\n";
    echo "    <td class=\"".$cssSw->getClass()."\"><font color=\"-1\">"._("Grafiktyp f&uuml;r Likertskalen").":</font></td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\" align=\"CENTER\" colspan=\"2\">\n";
    echo "      <select name=\"likertscale_gfx_type\" size=\"1\" style=\"width:120px\">\n";
    foreach ($graphtypes_likertscale as $k=>$v) {
        echo "        <option value=\"".$k."\""; if ($db_template->f("likertscale_gfx_type")==$k) print " SELECTED"; print ">".$v."\n";
    }
    echo "      </select>\n";
    echo "    </td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\">&nbsp;</td>\n";
    echo "  </tr>\n";

    $cssSw->switchClass();

    echo "  <tr>\n";
    echo "    <td class=\"".$cssSw->getClass()."\"><font color=\"-1\">"._("Grafiktyp f&uuml;r Multiplechoice").":</font></td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\" align=\"CENTER\" colspan=\"2\">\n";
    echo "      <select name=\"mchoice_scale_gfx_type\" size=\"1\" style=\"width:120px\">\n";
    foreach ($graphtypes_mchoice as $k=>$v) {
        echo "        <option value=\"".$k."\""; if ($db_template->f("mchoice_scale_gfx_type")==$k) print " SELECTED"; print ">".$v."\n";
    }
    echo "      </select>\n";
    echo "    </td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\">&nbsp;</td>\n";
    echo "  </tr>\n";

    $cssSw->switchClass();

    echo "<script type=\"text/javascript\" language=\"javascript\">\n";
    echo "  function save() {\n";
    echo "    document.temp.cmd.value='save';\n";
    echo "    document.temp.submit();\n";
    echo "  }\n";
    echo "</script>\n";

    echo "  <tr>\n";
    echo "    <td class=\"steel1\" colspan=\"4\">&nbsp;</td>\n";
    echo "  </tr>\n";
    echo "  <tr>\n";
    echo "    <td class=\"steel1\" colspan=\"2\" align=\"LEFT\">".LinkButton::create('<< '._('Zurück'), 'eval_summary.php?eval_id='.$eval_id)."</td>\n";
    echo "    <td class=\"steel1\" colspan=\"2\" align=\"RIGHT\">".LinkButton::create(_('Speichern'), 'javascript:save();')."&nbsp;".LinkButton::create(_('Zurücksetzen'), 'javascript:document.temp.reset();')."</td>\n";
    echo "  </tr>\n";
    echo "  <tr>\n";
    echo "    <td class=\"blank\" colspan=\"4\">&nbsp;</td>\n";
    echo "  </tr>\n";
    echo "</table>\n";
    echo "</form>\n";
    echo "    </td>\n";
    echo "    <td class=\"blank\" width=\"2%\" align=\"CENTER\" valign=\"TOP\">".createInfoBox("evaluation.jpg")."</td>";
    echo "    <td class=\"blank\" width=\"1%\">&nbsp;</td>\n";
    echo "  </tr>\n";
    echo "  <tr>";
    echo "    <td colspan=\"4\" class=\"blank\">&nbsp;</td>\n";
    echo "  </tr>";
    echo "</table>\n";
}


  // Save data back to database.
include ('lib/include/html_end.inc.php');
  page_close();
?>
