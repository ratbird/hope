<?php
# Lifter002: TODO
# Lifter005: TODO
# Lifter007: TODO
# Lifter003: TEST
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

unregister_globals();
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
$eval_id = Request::option('eval_id');
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

// Pruefen, ob die Person wirklich berechtigt ist, hier etwas zu aendern...
$query = "SELECT 1 FROM eval WHERE eval_id = ? AND author_id = IFNULL(?, author_id)";
$statement = DBManager::get()->prepare($query);
$statement->execute(array(
    $eval_id,
    $staff_member ? null : $GLOBALS['user']->id
));
$can_change = $statement->fetchColumn();

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


$cmd = Request::option('cmd');
$template_id = Request::option('template_id');
if (isset($cmd) && $can_change && isset($eval_id)) {
    if ($cmd=="save") {
        $show_questions = Request::option('show_questions');
        $show_total_stats = Request::option('show_total_stats');
        $show_graphics= Request::option('show_graphics');
        $show_questionblock_headline= Request::option('show_questionblock_headline');
        $show_group_headline= Request::option('show_group_headline');
        $polscale_gfx_type= Request::option('polscale_gfx_type');
        $likertscale_gfx_type= Request::option('likertscale_gfx_type');
        $mchoice_scale_gfx_type= Request::option('mchoice_scale_gfx_type');
        if (!isset($template_id) || $template_id=="") {
            // Neues Template einfuegen
            $template_id=DbView::get_uniqid();
            
            $query = "INSERT INTO eval_templates
                          (template_id, user_id, name, show_questions, show_total_stats, show_graphics,
                           show_questionblock_headline, show_group_headline, polscale_gfx_type,
                           likertscale_gfx_type, mchoice_scale_gfx_type)
                      VALUES (?, ?, 'nix', ?, ?, ?, ?, ?, ?, ?, ?)";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                $template_id, $GLOBALS['user']->id, $show_questions, $show_total_stats,
                $show_graphics, $show_questionblock_headline, $show_group_headline, $polscale_gfx_type,
                $likertscale_gfx_type, $mchoice_scale_gfx_type
            ));

            $query = "INSERT INTO eval_templates_eval (eval_id, template_id)
                      VALUES (?, ?)";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($eval_id, $template_id));

            $msg .= "msg§"._("Template wurde neu erzeugt.");
        } else {
            // Bestehendes Template updaten
            $query = "UPDATE eval_templates
                      SET show_questions = ?, show_total_stats = ?, show_graphics = ?,
                          show_questionblock_headline = ?, show_group_headline = ?,
                          polscale_gfx_type = ?, likertscale_gfx_type = ?,
                          mchoice_scale_gfx_type = ?
                      WHERE template_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                $show_questions, $show_total_stats, $show_graphics,
                $show_questionblock_headline, $show_group_headline,
                $polscale_gfx_type, $likertscale_gfx_type,
                $mchoice_scale_gfx_type, $template_id
            ));

            $msg .= "msg§".("Template wurde ver&auml;ndert.");
        }
    }
}


$cssSw=new cssClassSwitcher;

if (!empty($eval_id) && $can_change) {
    $query = "SELECT t.*
              FROM eval_templates AS t
              JOIN eval_templates_eval AS te USING (template_id)
              WHERE te.eval_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($eval_id));
    $templates = $statement->fetch(PDO::FETCH_ASSOC);

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
    echo "  <input type=\"hidden\" name=\"template_id\" value=\"".$templates['template_id']."\">\n";
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
    echo "    <td class=\"".$cssSw->getClass()."\" align=\"CENTER\"><font color=\"-1\"><input type=\"radio\" name=\"show_total_stats\" value=\"1\" "; if ($templates['show_total_stats']=="1" || !($has_template)) echo "CHECKED"; print "></font></td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\" align=\"CENTER\"><font color=\"-1\"><input type=\"radio\" name=\"show_total_stats\" value=\"0\" "; if ($templates['show_total_stats']=="0") echo "CHECKED"; print "></font></td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\">&nbsp;</td>\n";
    echo "  </tr>\n";

    $cssSw->switchClass();

    echo "  <tr>\n";
    echo "    <td class=\"".$cssSw->getClass()."\"><font color=\"-1\">"._("Zeige Grafiken an").":</font></td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\" align=\"CENTER\"><font color=\"-1\"><input type=\"radio\" name=\"show_graphics\" value=\"1\" "; if ($templates['show_graphics']=="1" || !($has_template)) echo "CHECKED"; print "></font></td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\" align=\"CENTER\"><font color=\"-1\"><input type=\"radio\" name=\"show_graphics\" value=\"0\" "; if ($templates['show_graphics']=="0") echo "CHECKED"; print "></font></td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\">&nbsp;</td>\n";
    echo "  </tr>\n";

    $cssSw->switchClass();

    echo "  <tr>\n";
    echo "    <td class=\"".$cssSw->getClass()."\"><font color=\"-1\">"._("Zeige Fragen an").":</font></td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\" align=\"CENTER\"><font color=\"-1\"><input type=\"radio\" name=\"show_questions\" value=\"1\" "; if ($templates['show_questions']=="1" || !($has_template)) echo "CHECKED"; print "></font></td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\" align=\"CENTER\"><font color=\"-1\"><input type=\"radio\" name=\"show_questions\" value=\"0\" "; if ($templates['show_questions']=="0") echo "CHECKED"; print "></font></td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\">&nbsp;</td>\n";
    echo "  </tr>\n";

    $cssSw->switchClass();

    echo "  <tr>\n";
    echo "    <td class=\"".$cssSw->getClass()."\"><font color=\"-1\">"._("Zeige Gruppen&uuml;berschriften an").":</font></td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\" align=\"CENTER\"><font color=\"-1\"><input type=\"radio\" name=\"show_group_headline\" value=\"1\" "; if ($templates['show_group_headline']=="1" || !($has_template)) echo "CHECKED"; print "></font></td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\" align=\"CENTER\"><font color=\"-1\"><input type=\"radio\" name=\"show_group_headline\" value=\"0\" "; if ($templates['show_group_headline']=="0") echo "CHECKED"; print "></font></td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\">&nbsp;</td>\n";
    echo "  </tr>\n";

    $cssSw->switchClass();

    echo "  <tr>\n";
    echo "    <td class=\"".$cssSw->getClass()."\"><font color=\"-1\">"._("Zeige Fragenblock&uuml;berschriften an").":</font></td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\" align=\"CENTER\"><font color=\"-1\"><input type=\"radio\" name=\"show_questionblock_headline\" value=\"1\" "; if ($templates['show_questionblock_headline']=="1" || !($has_template)) echo "CHECKED"; print "></font></td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\" align=\"CENTER\"><font color=\"-1\"><input type=\"radio\" name=\"show_questionblock_headline\" value=\"0\" "; if ($templates['show_questionblock_headline']=="0") echo "CHECKED"; print "></font></td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\">&nbsp;</td>\n";
    echo "  </tr>\n";

    $cssSw->switchClass();

    echo "  <tr>\n";
    echo "    <td class=\"".$cssSw->getClass()."\"><font color=\"-1\">"._("Grafiktyp f&uuml;r Polskalen").":</font></td>\n";
    echo "    <td class=\"".$cssSw->getClass()."\" align=\"CENTER\" colspan=\"2\">\n";
    echo "      <select name=\"polscale_gfx_type\" size=\"1\" style=\"width:120px\">\n";
    foreach ($graphtypes_polscale as $k=>$v) {
        echo "        <option value=\"".$k."\""; if ($templates['polscale_gfx_type']==$k) print " SELECTED"; print ">".$v."\n";
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
        echo "        <option value=\"".$k."\""; if ($templates['likertscale_gfx_type']==$k) print " SELECTED"; print ">".$v."\n";
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
        echo "        <option value=\"".$k."\""; if ($templates['mchoice_scale_gfx_type']==$k) print " SELECTED"; print ">".$v."\n";
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
