<?php
# Lifter002: TODO
# Lifter005: TODO
# Lifter007: TODO
# Lifter003: TODO
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
Navigation::activateItem('/homepage/tools/evaluation');

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
        $info1 =  array ("icon" => "eval-icon.gif",
        		"text" => _("Auf dieser Seite k&ouml;nnen Sie die Auswertung Ihrer Evaluation konfigurieren.")); 
	$info2 = array ("icon" => "i.gif",
			"text" => _("W&auml;hlen Sie Ihre Einstellungen und dr&uuml;cken Sie auf \"Template speichern\". Anschlie&szlig;end kommen Sie mit dem Button unten links zur&uuml;ck zu Ihrer Evaluation."));
	
	$infobox = array (array ("kategorie" => _("Information:"),
			"eintrag"   => array ($info1, $info2)));
	/* ------------------------------------------------------- end: infobox */
	return print_infobox ($infobox, $imgLogo, YES);
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

	echo "<TABLE BORDER=\"0\" WIDTH=\"100%\" CELLSPACING=\"0\" CELLPADDING=\"0\">";
	echo "<tr><td class=\"topic\" COLSPAN=\"4\" align=\"left\"><IMG SRC=\"{$GLOBALS['ASSETS_URL']}images/eval-icon.gif\" BORDER=\"0\"><FONT SIZE=\"-1\"><B>"._("Auswertungskonfiguration")."</B></FONT></td></tr>\n";
	echo "  <TR>";
	echo "    <TD COLSPAN=\"4\" CLASS=\"blank\">&nbsp;</TD>\n";
	echo "  </TR>";
	echo "  <TR>";
	echo "    <TD CLASS=\"blank\" WIDTH=\"1%\">&nbsp;</TD>\n";
	echo "<FORM NAME=\"temp\" ACTION=\"".$PHP_SELF."\" METHOD=\"POST\">\n";
	echo "    <TD CLASS=\"blank\">\n";
	echo "  <INPUT TYPE=\"hidden\" NAME=\"cmd\" VALUE=\"\">\n";
	echo "  <INPUT TYPE=\"hidden\" NAME=\"template_id\" VALUE=\"".$db_template->f("template_id")."\">\n";
	echo "  <INPUT TYPE=\"hidden\" NAME=\"eval_id\" VALUE=\"".$eval_id."\">\n";
	echo "<TABLE WIDTH=\"95%\" ALIGN=\"LEFT\" BORDER=\"0\" CELLSPACING=\"0\" CELLPADDING=\"0\">\n";
	echo "    <TD CLASS=\"blank\">&nbsp;</TD>\n";
	parse_msg($msg);
	echo "  <TR>\n";
	echo "    <TD CLASS=\"steel1\" WIDTH=\"40%\"><FONT COLOR=\"-1\"><B>"._("Optionen")."</B></FONT></TD>\n";
	echo "    <TD CLASS=\"steel1\" WIDTH=\"10%\" ALIGN=\"CENTER\"><FONT COLOR=\"-1\"><B>"._("Ja")."</B></FONT></TD>\n";
	echo "    <TD CLASS=\"steel1\" WIDTH=\"10%\" ALIGN=\"CENTER\"><FONT COLOR=\"-1\"><B>"._("Nein")."</B></FONT></TD>\n";
	echo "    <TD CLASS=\"steel1\">&nbsp;</TD>\n";
	echo "  </TR>\n";
	echo "  <TR>\n";
	echo "    <TD CLASS=\"steel1kante\" COLSPAN=\"4\">&nbsp;</TD>\n";
	echo "  </TR>\n";
	echo "  <TR>\n";
	echo "    <TD CLASS=\"".$cssSw->getClass()."\"><FONT COLOR=\"-1\">"._("Zeige Gesamtstatistik an").":</FONT></TD>\n";
	echo "    <TD CLASS=\"".$cssSw->getClass()."\" ALIGN=\"CENTER\"><FONT COLOR=\"-1\"><INPUT TYPE=\"radio\" NAME=\"show_total_stats\" VALUE=\"1\" "; if ($db_template->f("show_total_stats")=="1" || !($has_template)) echo "CHECKED"; print "></FONT></TD>\n";
	echo "    <TD CLASS=\"".$cssSw->getClass()."\" ALIGN=\"CENTER\"><FONT COLOR=\"-1\"><INPUT TYPE=\"radio\" NAME=\"show_total_stats\" VALUE=\"0\" "; if ($db_template->f("show_total_stats")=="0") echo "CHECKED"; print "></FONT></TD>\n";
	echo "    <TD CLASS=\"".$cssSw->getClass()."\">&nbsp;</TD>\n";
	echo "  </TR>\n";
	
	$cssSw->switchClass();

	echo "  <TR>\n";
	echo "    <TD CLASS=\"".$cssSw->getClass()."\"><FONT COLOR=\"-1\">"._("Zeige Grafiken an").":</FONT></TD>\n";
	echo "    <TD CLASS=\"".$cssSw->getClass()."\" ALIGN=\"CENTER\"><FONT COLOR=\"-1\"><INPUT TYPE=\"radio\" NAME=\"show_graphics\" VALUE=\"1\" "; if ($db_template->f("show_graphics")=="1" || !($has_template)) echo "CHECKED"; print "></FONT></TD>\n";
	echo "    <TD CLASS=\"".$cssSw->getClass()."\" ALIGN=\"CENTER\"><FONT COLOR=\"-1\"><INPUT TYPE=\"radio\" NAME=\"show_graphics\" VALUE=\"0\" "; if ($db_template->f("show_graphics")=="0") echo "CHECKED"; print "></FONT></TD>\n";
	echo "    <TD CLASS=\"".$cssSw->getClass()."\">&nbsp;</TD>\n";
	echo "  </TR>\n";
	
	$cssSw->switchClass();

	echo "  <TR>\n";
	echo "    <TD CLASS=\"".$cssSw->getClass()."\"><FONT COLOR=\"-1\">"._("Zeige Fragen an").":</FONT></TD>\n";
	echo "    <TD CLASS=\"".$cssSw->getClass()."\" ALIGN=\"CENTER\"><FONT COLOR=\"-1\"><INPUT TYPE=\"radio\" NAME=\"show_questions\" VALUE=\"1\" "; if ($db_template->f("show_questions")=="1" || !($has_template)) echo "CHECKED"; print "></FONT></TD>\n";
	echo "    <TD CLASS=\"".$cssSw->getClass()."\" ALIGN=\"CENTER\"><FONT COLOR=\"-1\"><INPUT TYPE=\"radio\" NAME=\"show_questions\" VALUE=\"0\" "; if ($db_template->f("show_questions")=="0") echo "CHECKED"; print "></FONT></TD>\n";
	echo "    <TD CLASS=\"".$cssSw->getClass()."\">&nbsp;</TD>\n";
	echo "  </TR>\n";
	
	$cssSw->switchClass();

	echo "  <TR>\n";
	echo "    <TD CLASS=\"".$cssSw->getClass()."\"><FONT COLOR=\"-1\">"._("Zeige Gruppen&uuml;berschriften an").":</FONT></TD>\n";
	echo "    <TD CLASS=\"".$cssSw->getClass()."\" ALIGN=\"CENTER\"><FONT COLOR=\"-1\"><INPUT TYPE=\"radio\" NAME=\"show_group_headline\" VALUE=\"1\" "; if ($db_template->f("show_group_headline")=="1" || !($has_template)) echo "CHECKED"; print "></FONT></TD>\n";
	echo "    <TD CLASS=\"".$cssSw->getClass()."\" ALIGN=\"CENTER\"><FONT COLOR=\"-1\"><INPUT TYPE=\"radio\" NAME=\"show_group_headline\" VALUE=\"0\" "; if ($db_template->f("show_group_headline")=="0") echo "CHECKED"; print "></FONT></TD>\n";
	echo "    <TD CLASS=\"".$cssSw->getClass()."\">&nbsp;</TD>\n";
	echo "  </TR>\n";
	
	$cssSw->switchClass();

	echo "  <TR>\n";
	echo "    <TD CLASS=\"".$cssSw->getClass()."\"><FONT COLOR=\"-1\">"._("Zeige Fragenblock&uuml;berschriften an").":</FONT></TD>\n";
	echo "    <TD CLASS=\"".$cssSw->getClass()."\" ALIGN=\"CENTER\"><FONT COLOR=\"-1\"><INPUT TYPE=\"radio\" NAME=\"show_questionblock_headline\" VALUE=\"1\" "; if ($db_template->f("show_questionblock_headline")=="1" || !($has_template)) echo "CHECKED"; print "></FONT></TD>\n";
	echo "    <TD CLASS=\"".$cssSw->getClass()."\" ALIGN=\"CENTER\"><FONT COLOR=\"-1\"><INPUT TYPE=\"radio\" NAME=\"show_questionblock_headline\" VALUE=\"0\" "; if ($db_template->f("show_questionblock_headline")=="0") echo "CHECKED"; print "></FONT></TD>\n";
	echo "    <TD CLASS=\"".$cssSw->getClass()."\">&nbsp;</TD>\n";
	echo "  </TR>\n";
	
	$cssSw->switchClass();

	echo "  <TR>\n";
	echo "    <TD CLASS=\"".$cssSw->getClass()."\"><FONT COLOR=\"-1\">"._("Grafiktyp f&uuml;r Polskalen").":</FONT></TD>\n";
	echo "    <TD CLASS=\"".$cssSw->getClass()."\" ALIGN=\"CENTER\" COLSPAN=\"2\">\n";
	echo "      <SELECT NAME=\"polscale_gfx_type\" SIZE=\"1\" STYLE=\"width:120px\">\n";
	foreach ($graphtypes_polscale as $k=>$v) {
		echo "        <OPTION VALUE=\"".$k."\""; if ($db_template->f("polscale_gfx_type")==$k) print " SELECTED"; print ">".$v."\n";
	}
	echo "      </SELECT>\n";
	echo "    </TD>\n";
	echo "    <TD CLASS=\"".$cssSw->getClass()."\">&nbsp;</TD>\n";
	echo "  </TR>\n";
	
	$cssSw->switchClass();

	echo "  <TR>\n";
	echo "    <TD CLASS=\"".$cssSw->getClass()."\"><FONT COLOR=\"-1\">"._("Grafiktyp f&uuml;r Likertskalen").":</FONT></TD>\n";
	echo "    <TD CLASS=\"".$cssSw->getClass()."\" ALIGN=\"CENTER\" COLSPAN=\"2\">\n";
	echo "      <SELECT NAME=\"likertscale_gfx_type\" SIZE=\"1\" STYLE=\"width:120px\">\n";
	foreach ($graphtypes_likertscale as $k=>$v) {
		echo "        <OPTION VALUE=\"".$k."\""; if ($db_template->f("likertscale_gfx_type")==$k) print " SELECTED"; print ">".$v."\n";
	}
	echo "      </SELECT>\n";
	echo "    </TD>\n";
	echo "    <TD CLASS=\"".$cssSw->getClass()."\">&nbsp;</TD>\n";
	echo "  </TR>\n";
	
	$cssSw->switchClass();

	echo "  <TR>\n";
	echo "    <TD CLASS=\"".$cssSw->getClass()."\"><FONT COLOR=\"-1\">"._("Grafiktyp f&uuml;r Multiplechoice").":</FONT></TD>\n";
	echo "    <TD CLASS=\"".$cssSw->getClass()."\" ALIGN=\"CENTER\" COLSPAN=\"2\">\n";
	echo "      <SELECT NAME=\"mchoice_scale_gfx_type\" SIZE=\"1\" STYLE=\"width:120px\">\n";
	foreach ($graphtypes_mchoice as $k=>$v) {
		echo "        <OPTION VALUE=\"".$k."\""; if ($db_template->f("mchoice_scale_gfx_type")==$k) print " SELECTED"; print ">".$v."\n";
	}
	echo "      </SELECT>\n";
	echo "    </TD>\n";
	echo "    <TD CLASS=\"".$cssSw->getClass()."\">&nbsp;</TD>\n";
	echo "  </TR>\n";
	
	$cssSw->switchClass();

	echo "<script type=\"text/javascript\" language=\"javascript\">\n";
	echo "  function save() {\n";
	echo "    document.temp.cmd.value='save';\n";
	echo "    document.temp.submit();\n";
	echo "  }\n";
	echo "</script>\n";

	echo "  <TR>\n";
	echo "    <TD CLASS=\"steel1\" COLSPAN=\"4\">&nbsp;</TD>\n";
	echo "  </TR>\n";
	echo "  <TR>\n";
	echo "    <TD CLASS=\"steel1\" COLSPAN=\"2\" ALIGN=\"LEFT\"><A HREF=\"eval_summary.php?eval_id=".$eval_id."\">".makeButton("zurueck", "img")."</A></TD>\n";
	echo "    <TD CLASS=\"steel1\" COLSPAN=\"2\" ALIGN=\"RIGHT\"><A HREF=\"javascript:save();\">".makeButton("speichern", "img")."</A>&nbsp;<A HREF=\"javascript:document.temp.reset();\">".makeButton("zuruecksetzen", "img")."</A></TD>\n";
	echo "  </TR>\n";
	echo "  <TR>\n";
	echo "    <TD CLASS=\"blank\" COLSPAN=\"4\">&nbsp;</TD>\n";
	echo "  </TR>\n";
	echo "</TABLE>\n";
	echo "</FORM>\n";
	echo "    </TD>\n";
	echo "    <TD CLASS=\"blank\" WIDTH=\"2%\" ALIGN=\"CENTER\" VALIGN=\"TOP\">".createInfoBox("evaluation.jpg")."</TD>";
	echo "    <TD CLASS=\"blank\" WIDTH=\"1%\">&nbsp;</TD>\n";
	echo "  </TR>\n";
	echo "  <TR>";
	echo "    <TD COLSPAN=\"4\" CLASS=\"blank\">&nbsp;</TD>\n";
	echo "  </TR>";
	echo "</TABLE>\n";
}


  // Save data back to database.
include ('lib/include/html_end.inc.php');
  page_close();
?>
