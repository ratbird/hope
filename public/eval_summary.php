<?php
# Lifter002: TODO
# Lifter007: TEST
# Lifter003: TODO
# Lifter010: TODO
/**
 * eval_summary.php - Hauptseite fuer Eval-Auswertungen
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Jan Kulmann <jankul@zmml.uni-bremen.de>
 * @copyright   2007-2010 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */


require '../lib/bootstrap.php';

unregister_globals();

require_once 'lib/visual.inc.php';
require_once "vendor/phplot/phplot.php";
require_once 'lib/msg.inc.php';
require_once 'lib/visual.inc.php';
require_once 'config.inc.php';
require_once 'lib/functions.php';
require_once 'lib/datei.inc.php';
require_once 'lib/evaluation/evaluation.config.php';
require_once EVAL_FILE_EVAL;
require_once EVAL_FILE_OBJECTDB;
require_once 'lib/export/export_tmp_gc.inc.php';
require_once 'lib/classes/Institute.class.php';

ob_start();
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

$eval_id = Request::option('eval_id');
/*
    1 = normale HTML-Ansicht in Stud.IP
    2 = Druckansicht, ohne HTML-Elemente
*/
$ausgabeformat = Request::int('ausgabeformat', 1);
$cmd = Request::option('cmd');
$evalgroup_id = Request::option('evalgroup_id');
$group_type = Request::option('group_type');

// Überprüfen, ob die Evaluation existiert oder der Benutzer genügend Rechte hat
$eval = new Evaluation($eval_id);
$eval->check();
if (EvaluationObjectDB::getEvalUserRangesWithNoPermission($eval) == YES || count($eval->errorArray) > 0) {
    throw new Exception(_("Diese Evaluation ist nicht vorhanden oder Sie haben nicht ausreichend Rechte!"));
}

// Gehoert die benutzende Person zum Seminar-Stab (Dozenten, Tutoren) oder ist es ein ROOT?
$staff_member = $perm->have_studip_perm("tutor", $SessSemName[1]);

// Template vorhanden?
$has_template = 0;
$db_template = new DB_Seminar();
$question_type = "";

$tmp_path_export = $GLOBALS['TMP_PATH']. '/export/';
export_tmp_gc();


if (isset($cmd)) {
    if ($cmd=="change_group_type" && isset($evalgroup_id) && isset($group_type)) {
        $db = new DB_Seminar();
        $db->query(sprintf("SELECT * FROM eval_group_template WHERE evalgroup_id='%s'",$evalgroup_id));
        if ($db->next_record()) { // Datensatz schon vorhanden --> UPDATE
            if ($group_type=="normal") {
                $db->query(sprintf("DELETE FROM eval_group_template WHERE group_type='table' AND evalgroup_id='%s'",$evalgroup_id));
                $db->next_record();
            } else
                $db->query(sprintf("UPDATE eval_group_template SET group_type='%s' WHERE evalgroup_id='%s' AND user_id='%s'",$group_type,$evalgroup_id,$auth->auth["uid"]));
        } else { // Datensatz nicht vorhanden --> INSERT
            // Ist der Benutzer auch wirklich der Eigentuemer der Eval?
            $db->query(sprintf("SELECT * FROM eval WHERE eval_id='%s'",$eval_id));
            if ($db->next_record() && ($db->f("author_id")==$auth->auth["uid"] || $staff_member))
                $db->query(sprintf("INSERT INTO eval_group_template (evalgroup_id, user_id, group_type) VALUES ('%s','%s','%s')",$evalgroup_id,$auth->auth["uid"],$group_type));
        }
    }
}


function do_template($column)
{
    global $has_template, $db_template;

    if ($has_template==0 || ($has_template==1 && $db_template->f($column)))
        return true;
    else
        return false;
}


/**
 * returning the type of the graph
 *
 * @return string
 */
function do_graph_template()
{
    global $db_template, $has_template, $question_type;

    if ($has_template==1) {
        if ($question_type=="likertskala") {
            return $db_template->f("likertscale_gfx_type");
        }
        if ($question_type=="multiplechoice") {
            return $db_template->f("mchoice_scale_gfx_type");
        }
        if ($question_type=="polskala") {
            return $db_template->f("polscale_gfx_type");
        }
    } else {
        return "bars";
    }
}

/**
 * drawing the graph for a evaluation question
 *
 * @param array() $data
 * @param string $evalquestion_id
 */
function do_graph($data, $evalquestion_id)
{
    global $tmp_path_export, $auth, $PATH_EXPORT;

    $type = do_graph_template();

    //Define the object
    if ($type == "pie") {
        // Beim pie muss die Zeichenflaeche etwas groesser gewaehlt werden...
        $graph = new PHPlot(500,300);
    } else {
        $graph = new PHPlot(300,250);
    }

    if ($type == "pie") {
        // Beim pie muss das Array umgeformt werden. Bug in PHPlot?
        $tmp = array();
        $tmp2 = array();
        $legend = array();
        array_push($tmp,"Test");
        foreach($data as $k=>$d) {
            array_push($tmp, $d[1]);
            array_push($legend, $d[0]);
        }
        array_push($tmp2, $tmp);
        $data = $tmp2;
        $graph->SetLegend($legend);
    }

    //png sieht besser aus, mriehe
    if (!isset($GLOBALS['EVAL_AUSWERTUNG_GRAPH_FORMAT'])) {
        $GLOBALS['EVAL_AUSWERTUNG_GRAPH_FORMAT'] = 'png';
    }

    //Data Colors
    $graph->SetDataColors(
        array("blue", "green", "yellow", "red", "PeachPuff", "orange", "pink", "lavender",
            "navy", "peru", "salmon", "maroon", "magenta", "orchid", "ivory"),
        array("black") //Border Colors
    );

    $graph->SetPlotAreaWorld(NULL, 0); // y-achse bei 0 starten
    $graph->SetPrecisionY(0); //anzahl kommastellen y-achse

    $graph->SetPlotBgColor(array(222,222,222));
    $graph->SetDataType("text-data");
    $graph->SetFileFormat($GLOBALS['EVAL_AUSWERTUNG_GRAPH_FORMAT']);
    $graph->SetOutputFile($tmp_path_export."/evalsum".$evalquestion_id.$auth->auth["uid"].".".$GLOBALS['EVAL_AUSWERTUNG_GRAPH_FORMAT']);
    $graph->SetIsInline(true);
    $graph->SetDataValues($data);
    $graph->SetPlotType($type);
    $graph->SetXLabelAngle(0);
    //$graph->SetShading(0); // kein 3D

    $graph->SetLineWidth(1);
    $graph->SetDrawXDataLabels(true);
    //Draw it
    $graph->DrawGraph();
}

function freetype_answers($parent_id, $anz_nutzer)
{
    global $ausgabeformat;

    $db_answers = new DB_Seminar();
    $db_answers->query(sprintf("SELECT ea.* FROM evalanswer ea, evalanswer_user eau WHERE ea.parent_id='%s' AND ea.text!='' AND eau.evalanswer_id=ea.evalanswer_id ORDER BY ea.position",$parent_id));
    echo "  <tr>\n";
    echo "    <td colspan=\"2\">\n";
    echo "      <table border=\"0\" width=\"100%\">\n";
    echo "        <tr><td colspan=\"2\" class=\"blank\"><font size=\"-1\"><b>"._("Antworten")."</b></font></td></tr>\n";
    $counter = 1;
    while ($db_answers->next_record()) {
        echo "      <tr>\n";
        echo "        <td width=\"1%\" valign=\"TOP\"><font size=\"-1\"><b>".$counter.".</b></font></td><td><font size=\"-1\">".formatReady($db_answers->f("text"))."</font></td>\n";
        echo "      </tr>\n";
        $counter++;
    }
    echo "      </table>\n";
    echo "    </td>\n";
    echo "  </tr>\n";
    echo "  <tr><td colspan=\"2\"><font size=\"-1\">"._("Anzahl der Teilnehmer").": ".$anz_nutzer."</font></td></tr>\n";
}

function user_answers_residual($parent_id)
{
    $db_user_answers = new DB_Seminar();
    $db_user_answers->query(sprintf("SELECT eau.* FROM evalanswer_user eau, evalanswer ea WHERE ea.parent_id='%s' AND ea.residual=1 AND eau.evalanswer_id=ea.evalanswer_id",$parent_id));
    $db_user_answers->next_record();
    return $db_user_answers->num_rows();
}

function user_answers($evalanswer_id)
{
    $db_user_answers = new DB_Seminar();
    $db_user_answers->query(sprintf("SELECT * FROM evalanswer_user WHERE evalanswer_id='%s'",$evalanswer_id));
    $db_user_answers->next_record();
    return $db_user_answers->num_rows();
}

function answers($parent_id, $anz_nutzer, $question_type)
{
    global $graph_switch, $auth, $ausgabeformat, $has_template;

    // Rueckgabearray, damit die Daten noch aufzutrennen sind...
    $ret_array = array("id"=>$parent_id,                         // Question-ID
               "txt"=>"",                                // HTML-Ausgabe
               "antwort_texte"=>array(),                 // Antwort-Texte
               "frage"=>"",                              // Frage-Text
               "has_residual"=>0,                // Enthaltungen?
               "antwort_durchschnitt"=>"",               // Antwort-Durchschnitt
               "summe_antworten"=>"",                    // Summe der Antworten
               "anzahl_teilnehmer"=>$anz_nutzer,         // Anzahl der Teilnehmer dieser Frage
               "auswertung"=>array()                     // 1. Anzahl der Antworten zu einer Antwort
                                                 // 2. Prozente einer Antwort
                                                 // 3. Prozente einer Antwort ohne Enthaltungen
              );

    $summary = array ();

    $css=new cssClassSwitcher;

    $db_answers_sum = new DB_Seminar();
    $db_answers_sum->query(sprintf("SELECT COUNT(*) anz FROM evalanswer AS ea LEFT JOIN evalanswer_user AS eau USING (evalanswer_id) WHERE ea.parent_id='%s' AND eau.evalanswer_id=ea.evalanswer_id",$parent_id));
    $db_answers_sum->next_record();

    $db_answers = new DB_Seminar();
    $db_answers->query(sprintf("SELECT * FROM evalanswer WHERE parent_id='%s' ORDER BY position",$parent_id));
    $antwort_nummer = 0;
    $edit = "";
    $txt = "";
    $gesamte_antworten = 0;
    $antwort_durchschnitt = 0;
    $has_residual = user_answers_residual($parent_id);
    $i = 1;
    $edit .= "<tr class=\"steel1\"><td width=\"1%\">&nbsp;</td><td width=\"70%\"><font size=\"-1\"><b>"._("Antworten")."</b></font></td><td width=\"29%\"><font size=\"-1\"><b>"._("Auswertung")."</b></font></td></tr>\n";
    while ($db_answers->next_record()) {
        $css->switchClass();
        $antwort_nummer++;
        $answer_counter = user_answers($db_answers->f("evalanswer_id"));
        if ($db_answers->f("residual")==0) {
            $gesamte_antworten += $answer_counter;
            $antwort_durchschnitt += $answer_counter * $antwort_nummer;
        }
        $prozente_wo_residual = 0;
        if ($has_residual && ($db_answers_sum->f("anz")-$has_residual)>0) $prozente_wo_residual = ROUND($answer_counter*100/($anz_nutzer-$has_residual));
        $prozente = 0;
        if ($db_answers_sum->f("anz")>0) $prozente = ROUND($answer_counter*100/$anz_nutzer);
        $edit .= "<tr class=\"".($i==1?"steel1kante":$css->getClass())."\"><td width=\"1%\"><font size=\"-1\"><b>".$antwort_nummer.".&nbsp;</b></font></td><td width=\"70%\"><font size=\"-1\">".($db_answers->f("text")!="" ? formatReady($db_answers->f("text")) : $db_answers->f("value"))."</font></td>";
        if ($has_residual) $edit .= "<td width=\"29%\"><font size=\"-1\">".$answer_counter." (".$prozente."%) ".($db_answers->f("residual")==0 ? "(".$prozente_wo_residual."%)<b>*</b>" : "" )."</font></td></tr>\n";
        else $edit .= "<td width=\"29%\"><font size=\"-1\">".$answer_counter." (".$prozente."%)</font></td></tr>\n";
        array_push($summary, array($antwort_nummer."(".$prozente."%)",$answer_counter));

        array_push($ret_array["antwort_texte"], ($db_answers->f("text")!="" ? formatReady($db_answers->f("text")) : $db_answers->f("value")));
        array_push($ret_array["auswertung"], array($answer_counter, $prozente, ($db_answers->f("residual")==0 ? $prozente_wo_residual : null)));
        if ($has_residual) $ret_array["has_residual"] = 1;

        $i = 0;
    }
    do_graph($summary, $parent_id);

    if ($gesamte_antworten > 0 && $antwort_durchschnitt > 0) $antwort_durchschnitt = ROUND($antwort_durchschnitt / $gesamte_antworten, 3);

    $ret_array["antwort_durchschnitt"] = $antwort_durchschnitt;
    $ret_array["summe_antworten"] = $gesamte_antworten;

    $txt .= "  <tr>\n";
    $txt .= "    <td width=\"70%\" valign=\"TOP\">\n";
    $txt .= "      <table width=\"98%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">";
    $txt .= $edit."\n";
    $txt .= "        <tr class=\"blank\"><td colspan=\"3\"><font size=\"-1\">&nbsp;</font></td></tr>";
    $txt .= "        <tr class=\"blank\"><td colspan=\"3\"><font size=\"-1\"><b>&#x2211;</b>=".$gesamte_antworten." "._("Antworten")."</font></td></tr>";

    $txt .= "        <tr class=\"blank\">";
    if ($question_type=="multiplechoice") {
        $txt .= "        <td colspan=\"3\">";
    } else {
        $txt .= "<td colspan=\"2\"><font size=\"-1\"><b>&#x2205;</b>-"._("Antwort").": ".$antwort_durchschnitt.($has_residual==0 ? "" : "<b>*</b>")."</font></td><td>";
    }
    $txt .= "          <font size=\"-1\">"._("Anzahl der Teilnehmer").": ".$anz_nutzer."</font></td></tr>";

    if ($has_residual) $txt .= "        <tr class=\"blank\"><td colspan=\"3\"><font size=\"-1\"><b>*</b>"._("Werte ohne Enthaltungen").".</font></td></tr>";
    $txt .= "      </table>";
    $txt .= "    </td>\n";
    $txt .= "    <td width=\"30%\" valign=\"TOP\" align=\"RIGHT\">\n";
    if (do_template("show_graphics")) {
        $txt .= '<IMG SRC="' . GetDownloadLink('evalsum'.$parent_id.$auth->auth['uid'].'.'.$GLOBALS['EVAL_AUSWERTUNG_GRAPH_FORMAT'], 'evalsum'.$parent_id.$auth->auth['uid'].'.'.$GLOBALS['EVAL_AUSWERTUNG_GRAPH_FORMAT'], 2) .'">'."\n";
    } else $txt .= "&nbsp;\n";
    $txt .= "    </td>\n";
    $txt .= "  </tr>\n";

    $ret_array['txt'] = $txt;

    return $ret_array;

}

function groups($parent_id)
{
    global $ausgabeformat, $global_counter, $local_counter, $question_type, $eval_id, $PHP_SELF, $evalgroup_id;

    $db_groups = new DB_Seminar();
    $db_groups->query(sprintf("SELECT * FROM evalgroup WHERE parent_id='%s' ORDER BY position",$parent_id));

    while ($db_groups->next_record()) {
        // Heraussuchen, ob es sich um ein Freitext-Template handelt...
        $db = new DB_Seminar();
        $db->query(sprintf("SELECT * FROM evalquestion WHERE evalquestion_id='%s'",$db_groups->f("template_id")));
        $freetype = FALSE;
        if ($db->next_record()) {
            if (strstr($db->f("text"),"Freitext"))
                $freetype = TRUE;
        }

        if ($db_groups->f("child_type")=="EvaluationGroup") {
            $global_counter += 1;
            $local_counter   = 0;

            echo "  <tr><td class=\"".($ausgabeformat==1 ? "topic" : "blank")."\" align=\"LEFT\" colspan=\"2\">\n";
            if (do_template("show_group_headline"))
                echo "    <b>".$global_counter.". ".formatReady($db_groups->f("title"))."</b>&nbsp;\n";
            else echo "&nbsp;";
        } else {
            $local_counter += 1;

            $group_type = "normal";

            $db_group_type = new DB_Seminar();
            $db_group_type->query(sprintf("SELECT * FROM eval_group_template WHERE evalgroup_id='%s'",$db_groups->f("evalgroup_id")));
            if ($db_group_type->next_record()) $group_type = $db_group_type->f("group_type");

            echo "  <tr><td class=\"".($ausgabeformat==1 ? "steelgraulight" : "blank")."\" colspan=\"2\">\n";
            if (do_template("show_questionblock_headline")) {
                echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\"><tr><td align=\"left\"><b>".$global_counter.".".$local_counter.". ".formatReady($db_groups->f("title"))."</b></td>";
                echo "<td align=\"RIGHT\">".($ausgabeformat==1 && !($freetype) ? "<a href=\"$PHP_SELF?eval_id=$eval_id&evalgroup_id=".$db_groups->f("evalgroup_id")."&group_type=".($group_type=="normal" ? "table" : "normal")."&cmd=change_group_type#anker\"><IMG SRC=\"".Assets::image_path('icons/16/blue/'.($group_type=='normal' ? 'vote-stopped' : 'vote').'.png')."\" TITLE=\""._("Zum Darstellungstyp")." ".($group_type=="normal"?_("Tabelle"):_("Normal"))." "._("wechseln").".\" border=\"0\"></a>" : "&nbsp;"). "</td>";
                echo "</tr></table>\n";
            }
            if ($evalgroup_id == $db_groups->f("evalgroup_id")) {
                echo "  <a name=\"anker\"></a>\n";
            }
        }

        echo "  </td></tr>";

        if ($db_groups->f("child_type")=="EvaluationQuestion") {
            echo "  <tr><td class=\"blank\" colspan=\"2\">\n";
            $db_questions = new DB_Seminar();
            $db_questions->query(sprintf("SELECT * FROM evalquestion WHERE parent_id='%s' ORDER BY position",$db_groups->f("evalgroup_id")));
            echo "<table border=\"". ($group_type=="normal" || $ausgabeformat==1 ? "0" : "1") ."\" width=\"100%\" cellspacing=\"0\">\n";

            $local_question_counter = 0;
            $answer_arr = array();
            while ($db_questions->next_record()) {

                $question_type = $db_questions->f("type");
                $db_questions_user = new DB_Seminar();
                $db_questions_user->query(sprintf("SELECT COUNT(DISTINCT eau.user_id) anz FROM evalanswer ea, evalanswer_user eau WHERE ea.parent_id='%s' AND eau.evalanswer_id=ea.evalanswer_id",$db_questions->f("evalquestion_id")));
                $db_questions_user->next_record();

                $local_question_counter += 1;

                if (do_template("show_questions") && $group_type=="normal") {
                    echo "    <tr><td class=\"blank\" colspan=\"2\">\n";
                    echo "      <b>".$global_counter.".".$local_counter.".".$local_question_counter.". ".formatReady($db_questions->f("text"))."</b></font>\n";
                    echo "    </td></tr>\n";
                }

                if (!($freetype)) {
                    // Keine Freitext-Eingabe
                    $ret = answers($db_questions->f("evalquestion_id"), $db_questions_user->f("anz"), $db_questions->f("type"));
                    $ret["frage"] = $db_questions->f("text");
                    array_push($answer_arr, $ret);
                    if ($group_type=="normal") echo $ret["txt"];
                } else {
                    // Freitext
                    freetype_answers($db_questions->f("evalquestion_id"), $db_questions_user->f("anz"));
                }

            }

            if (!($freetype) && $group_type=="table") {
                $antworten_angezeigt = FALSE;
                $i = 0;
                $has_residual = 0;
                $css=new cssClassSwitcher;
                foreach ($answer_arr as $k1=>$questions) { // Oberste Ebene, hier sind die Questions abgelegt

                    $css->switchClass();

                    if (!($antworten_angezeigt)) {
                        $i = 1;
                                            echo "  <tr class=\"steel1\"><td><font size=\"-1\">&nbsp;</font></td>";
                                            foreach ($questions["antwort_texte"] as $k2=>$v2) { // 1. Unterebene, hier sind die Antworttexte abgelegt
                                                echo "<td><font size=\"-1\">".$v2."</font></td>";
                                            }
                        echo "<td align=\"center\"><font size=\"-1\"><b>&#x2211;</b></font></td><td align=\"center\"><font size=\"-1\"><b>&#x2205;</b></font></td><td align=\"center\"><font size=\"-1\">"._("Teilnehmer")."</font></td>";
                                            echo "</tr>";
                                            $antworten_angezeigt = TRUE;
                                        }

                    echo "<tr class=\"". ($i==1?"steel1kante":$css->getClass())."\">";
                    echo "  <td><font size=\"-1\">".$questions["frage"]."</font></td>";
                    foreach ($questions["auswertung"] as $k3=>$v3) {
                        echo "<td width=\"10%\" valign=\"TOP\" ".($i!=1?"CLASS=\"".$css->getClass()."\"":"")."><font size=\"-1\">";
                        echo $v3[0]." (".$v3[1]."%)"; // 2. Unterebene, hier sind die Zahlen abgelegt
                        if ($v3[2]) echo " (".$v3[2]."%)<b>*</b>";
                        echo "</font></td>";
                    }

                    $i=0;
                    if ($questions["has_residual"]) $has_residual = 1;

                    echo "<td align=\"center\" width=\"3%\" valign=\"TOP\"><font size=\"-1\">".$questions["summe_antworten"]."</font></td><td align=\"center\" width=\"3%\" valign=\"TOP\"><font size=\"-1\">".$questions["antwort_durchschnitt"].($questions["has_residual"]?"<b>*</b>":"")."</font></td><td align=\"center\" width=\"6%\" valign=\"TOP\"><font size=\"-1\">".$questions["anzahl_teilnehmer"]."</font></td>";

                    echo "</tr>";
                }
                if ($has_residual) echo "<tr><td><font size=\"-1\"><b>*</b>"._("Werte ohne Enthaltungen").".</font></td></tr>";
            }

            echo "</table>\n";
            echo "</td></tr>\n";
        }
        groups($db_groups->f("evalgroup_id"));
    }
}


$db = new DB_Seminar();

if ($staff_member)
    $db->query(sprintf("SELECT * FROM eval WHERE eval_id='%s'",$eval_id));
else
    $db->query(sprintf("SELECT * FROM eval WHERE eval_id='%s' AND author_id='%s'",$eval_id,$auth->auth["uid"]));

if ($db->next_record()) {
  $db_template->query(sprintf("SELECT t.* FROM eval_templates t, eval_templates_eval te WHERE te.eval_id='%s' AND t.template_id=te.template_id",$eval_id));
  if ($db_template->next_record()) $has_template = 1;

  $db_owner = new DB_Seminar();
  $db_owner->query(sprintf("SELECT ".$_fullname_sql['no_title']." AS fullname FROM auth_user_md5 WHERE user_id='%s'", $db->f("author_id")));
  $db_owner->next_record();

  $global_counter = 0;
  $local_counter  = 0;

  $db_number_of_votes = new DB_Seminar();
  $db_number_of_votes->query(sprintf("SELECT COUNT(DISTINCT user_id) anz FROM eval_user WHERE eval_id='%s'", $eval_id));
  $db_number_of_votes->next_record();

  $eval_ranges_names = array();
  $eval_ranges = DbManager::get()
                 ->query("SELECT range_id FROM eval_range WHERE eval_id = " . DbManager::get()->quote($eval_id))
                 ->fetchAll(PDO::FETCH_COLUMN);
  foreach ($eval_ranges as $eval_range) {
      $o_type = get_object_type($eval_range, array('studip','user','sem','inst'));
      switch($o_type) {
      case 'global':
          $name = _("Systemweite Evaluationen");
          break;
      case 'sem':
          $name = _("Veranstaltung:");
          $seminar = Seminar::getInstance($eval_range);
          $name .= ' ' . $seminar->getName();
          $name .= ' (' . Semester::findByTimestamp($seminar->semester_start_time)->name;
          if ($seminar->semester_duration_time == -1) {
              $name .= ' - ' . _("unbegrenzt");
          }
          if ($seminar->semester_duration_time > 0) {
              $name .= ' - ' . Semester::findByTimestamp($seminar->semester_start_time + $seminar->semester_duration_time)->name;
          }
          $name .= ')';
          $dozenten = array_map(function($v){return $v['Nachname'];}, $seminar->getMembers('dozent'));
          $name .= ' (' . join(', ' , $dozenten) . ')';
          break;
      case 'user':
          $name = _("Profil:");
          $name .= ' ' . get_fullname($eval_range);
          break;
      case 'inst':
      case 'fak':
          $name = _("Einrichtung:");
          $name .= ' ' . Institute::find($eval_range)->name;
          break;
      default:
          $name = _("unbekannt");
      }
      $eval_ranges_names[] = $name;
  }
  sort($eval_ranges_names);

  // Evaluation existiert auch...
  echo "<table border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\">\n";
  echo "<tr><td class=\"topic\" align=\"left\"><font color=\"".($ausgabeformat==1 ? "white" : "black")."\">";
  echo ($ausgabeformat==1 ? Assets::img('icons/16/white/test.png') : "" );
  echo "<b>"._("Evaluations-Auswertung")."</b></font></td>\n";
  echo "<td class=\"".($ausgabeformat==1 ? "topic" : "blank" )."\" align=\"RIGHT\">".($ausgabeformat==1 ? "<a href=\"eval_summary_export.php?eval_id=".$eval_id."\" TARGET=\"_blank\"><font color=\"WHITE\">"._("PDF-Export")."</font></a><b>&nbsp;|&nbsp;</b><a href=\"".$PHP_SELF."?eval_id=".$eval_id."&ausgabeformat=2\" TARGET=\"_blank\"><font color=\"WHITE\">"._("Druckansicht")."</font></a>&nbsp;&nbsp;<a href=\"eval_config.php?eval_id=".$eval_id."\"><IMG SRC=\"".Assets::image_path('icons/16/white/arr_2right.png')."\" border=\"0\" ALT=\""._("Auswertung konfigurieren")."\" TITLE=\""._("Auswertung konfigurieren")."\"></a>" : "" ) ."&nbsp;</td>\n";
  echo "</tr>\n";
  echo "<tr><td class=\"blank\" colspan=\"2\" align=\"left\">&nbsp;</td></tr>\n";
  echo "<tr><td class=\"blank\" colspan=\"2\" align=\"left\"><font size=\"+1\"><b>&nbsp;&nbsp;".formatReady($db->f("title"))."</b></font></td>\n";
  echo "<tr><td class=\"blank\" colspan=\"2\" align=\"left\">&nbsp;&nbsp;";
  echo _("Diese Evaluation ist folgenden Bereichen zugeordnet:");
  echo '<ul>';
  echo '<li>' . join('</li><li>', array_map('htmlready', $eval_ranges_names)) . '</li>';
  echo '</ul>';
  echo "</td></tr>\n";

  echo "</tr>\n";

  echo "<tr><td class=\"blank\" colspan=\"2\" align=\"left\">&nbsp;</font></td></tr>\n";

  // Gesamtstatistik
  if (do_template("show_total_stats")) {
    echo "  <tr>\n";
    echo "    <td colspan=\"2\" class=\"blank\"><font size=\"-1\">\n";
    echo "      &nbsp;&nbsp;".$db_number_of_votes->f("anz")." "._("Teilnehmer insgesamt").".&nbsp;";
    echo "      "._("Die Teilnahme war")." ". ($db->f("anonymous")==0 ? _("nicht") : "") . " "._("anonym").".";
    echo "      "._("Eigent&uuml;mer").": ".$db_owner->f("fullname").". ".("Erzeugt am").": ".date('d.m.Y H:i:s');
    echo "    </font></td>\n";
    echo "  </tr>\n";
  }

  echo "  <tr><td colspan=\"2\">\n";
  echo "    <table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"8\">\n";

  groups($db->f("eval_id"));

  echo "    </table>\n";
  echo "  </td></tr>\n";
  echo "</table>\n";
}


PageLayout::setHelpKeyword("Basis.Evaluationen");
Navigation::activateItem('/tools/evaluation');
PageLayout::setTitle(_("Evaluations-Auswertung"));

if ($ausgabeformat == 2) {
    PageLayout::removeStylesheet('style.css');
    PageLayout::addStylesheet('print.css');
}
$layout = $GLOBALS['template_factory']->open('layouts/base_without_infobox');

$layout->content_for_layout = ob_get_clean();

echo $layout->render();
page_close();
