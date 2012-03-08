<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* eval_summary_export.php
*
* PDF-Export fuer Eval-Auswertungen
*
*
* @author               Jan Kulmann <jankul@zmml.uni-bremen.de>
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// eval_summary_export.php
// Copyright (C) 2007 Jan Kulmann <jankul@zmml.uni-bremen.de>
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


require '../lib/bootstrap.php';
unregister_globals();

if (!isset($EVAL_AUSWERTUNG_GRAPH_FORMAT)) $EVAL_AUSWERTUNG_GRAPH_FORMAT = 'jpg';

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));

$perm->check('user');

include ('lib/seminar_open.php');             // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page
require_once('lib/msg.inc.php');
require_once('lib/visual.inc.php');
require_once('config.inc.php');
require_once 'lib/functions.php';
require_once('lib/datei.inc.php');
require_once('lib/evaluation/evaluation.config.php');
require_once(EVAL_FILE_EVAL);
require_once(EVAL_FILE_OBJECTDB);
require_once('lib/export/export_tmp_gc.inc.php');
require_once 'lib/classes/Institute.class.php';

// Start of Output
$eval_id = Request::option('eval_id');

$no_permission = YES;
$eval = new Evaluation($eval_id);
$no_permissons = EvaluationObjectDB::getEvalUserRangesWithNoPermission ($eval);
if ($no_permissons == YES) {
  // Evaluation existiert nicht...
  echo "&nbsp;"._("Evaluation NICHT vorhanden oder keine Rechte vorhanden!");
  die();
}

// Gehoert die benutzende Person zum Seminar-Stab (Dozenten, Tutoren) oder ist es ein ROOT?
$staff_member = $perm->have_studip_perm("tutor",$SessSemName[1]);
$db5 = new DB_Seminar;

$tmp_path_export = $GLOBALS['TMP_PATH']. '/export/';
export_tmp_gc();

// Template vorhanden?
$has_template = 0;
$db_template = new DB_Seminar();

$pattern = array("'<img[\s]+[^>]*?src[\s]?=[\s\"\']+(.*?)[\"\']+.*?>'si");
$replace = array("<fo:external-graphic src=\"url(\\1)\"/>");


function do_template($column) {
        global $has_template, $db_template;
        if ($has_template==0 || ($has_template==1 && $db_template->f($column)))
                return true;
        else
                return false;
}


function freetype_answers ($parent_id, $anz_nutzer) {
    global $ausgabeformat, $fo_file, $pattern, $replace;
    $db_answers = new DB_Seminar();
        $db_answers->query(sprintf("SELECT ea.* FROM evalanswer ea, evalanswer_user eau WHERE ea.parent_id='%s' AND ea.text!='' AND eau.evalanswer_id=ea.evalanswer_id ORDER BY ea.position",$parent_id));
    $counter = 1;
    while ($db_answers->next_record()) {
        fputs($fo_file,"                <fo:table-row>\n");
        // fputs($fo_file,"                  <fo:table-cell ><fo:block font-size=\"8pt\">".$counter.". ".htmlspecialchars($db_answers->f("text"))."</fo:block></fo:table-cell>\n");
        fputs($fo_file,"                  <fo:table-cell ><fo:block font-size=\"8pt\">".$counter.". ".preg_replace($pattern,$replace,smile(htmlspecialchars($db_answers->f("text")),TRUE))."</fo:block></fo:table-cell>\n");
        fputs($fo_file,"                </fo:table-row>\n");
        $counter++;
    }
    fputs($fo_file,"                <fo:table-row>\n");
    fputs($fo_file,"                  <fo:table-cell ><fo:block font-size=\"8pt\">"._("Anzahl der Teilnehmer").": ".$anz_nutzer."</fo:block></fo:table-cell>\n");
    fputs($fo_file,"                </fo:table-row>\n");
}

function user_answers_residual ($parent_id) {
        $db_user_answers = new DB_Seminar();
        $db_user_answers->query(sprintf("SELECT eau.* FROM evalanswer_user eau, evalanswer ea WHERE ea.parent_id='%s' AND ea.residual=1 AND eau.evalanswer_id=ea.evalanswer_id",$parent_id));
        $db_user_answers->next_record();
        return $db_user_answers->num_rows();
}


function user_answers ($evalanswer_id) {
    $db_user_answers = new DB_Seminar();
    $db_user_answers->query(sprintf("SELECT * FROM evalanswer_user WHERE evalanswer_id='%s'",$evalanswer_id));
    $db_user_answers->next_record();
    return $db_user_answers->num_rows();
}

function answers ($parent_id, $anz_nutzer, $question_type) {
    global $graph_switch, $auth, $ausgabeformat, $fo_file, $has_template, $pattern, $replace;

     // Rueckgabearray, damit die Daten noch aufzutrennen sind...
        $ret_array = array("id"=>$parent_id,                         // Question-ID
                           "txt"=>"",                                // HTML-Ausgabe
                           "antwort_texte"=>array(),                 // Antwort-Texte
                           "frage"=>"",                              // Frage-Text
                           "has_residual"=>0,                        // Enthaltungen?
                           "antwort_durchschnitt"=>"",               // Antwort-Durchschnitt
                           "summe_antworten"=>"",                    // Summe der Antworten
                           "anzahl_teilnehmer"=>$anz_nutzer,         // Anzahl der Teilnehmer dieser Frage
                           "auswertung"=>array()                     // 1. Anzahl der Antworten zu einer Antwort
                                                                     // 2. Prozente einer Antwort
                                                                     // 3. Prozente einer Antwort ohne Enthaltungen
                          );

    $summary = array ();

    $db_answers_sum = new DB_Seminar();
    $db_answers_sum->query(sprintf("SELECT COUNT(*) anz FROM evalanswer AS ea LEFT JOIN evalanswer_user AS eau USING (evalanswer_id) WHERE ea.parent_id='%s' AND eau.evalanswer_id=ea.evalanswer_id",$parent_id));
    $db_answers_sum->next_record();

    $db_answers = new DB_Seminar();
    $db_answers->query(sprintf("SELECT * FROM evalanswer WHERE parent_id='%s' ORDER BY position",$parent_id));
    $antwort_nummer = 0;
    $gesamte_antworten = 0;
    $edit = "";
    $txt = "";
    $antwort_durchschnitt = 0;
    $has_residual = user_answers_residual($parent_id);
    while ($db_answers->next_record()) {
        $antwort_nummer++;
        $answer_counter = user_answers($db_answers->f("evalanswer_id"));
        if ($db_answers->f("residual")==0) {
            $gesamte_antworten += $answer_counter;
            $antwort_durchschnitt += $answer_counter * $antwort_nummer;
        }
        $prozente = 0;
        if ($db_answers_sum->f("anz")>0) $prozente = ROUND($answer_counter*100/$anz_nutzer);
        $prozente_wo_residual = 0;
        if ($has_residual && ($db_answers_sum->f("anz")-$has_residual)>0) $prozente_wo_residual = ROUND($answer_counter*100/($anz_nutzer-$has_residual));
        $edit .= "                <fo:table-row>\n";
        $edit .= "                  <fo:table-cell ><fo:block font-size=\"8pt\">".$antwort_nummer.". ".preg_replace($pattern,$replace,smile(htmlspecialchars(($db_answers->f("text")!="" ? $db_answers->f("text") : $db_answers->f("value"))),TRUE))."</fo:block></fo:table-cell>\n";

        if ($has_residual) $edit .= "                  <fo:table-cell ><fo:block font-size=\"8pt\">".$answer_counter." (".$prozente."%) ".($db_answers->f("residual")==0 ? "(".$prozente_wo_residual."%)*" : "" )."</fo:block></fo:table-cell>\n";
        else $edit .= "                  <fo:table-cell ><fo:block font-size=\"8pt\">".$answer_counter." (".$prozente."%)</fo:block></fo:table-cell>\n";
        $edit .= "                </fo:table-row>\n";

        array_push($ret_array["antwort_texte"], ($db_answers->f("text")!="" ? $db_answers->f("text") : $db_answers->f("value")));
                array_push($ret_array["auswertung"], array($answer_counter, $prozente, ($db_answers->f("residual")==0 ? $prozente_wo_residual : null)));
                if ($has_residual) $ret_array["has_residual"] = 1;

    }
    if ($gesamte_antworten > 0 && $antwort_durchschnitt > 0) $antwort_durchschnitt = ROUND($antwort_durchschnitt / $gesamte_antworten, 3);

    $ret_array["antwort_durchschnitt"] = $antwort_durchschnitt;
        $ret_array["summe_antworten"] = $gesamte_antworten;

    $txt .= $edit;

    if ($question_type=="multiplechoice") {
        $txt .= "                <fo:table-row>\n";
        $txt .= "                  <fo:table-cell ><fo:block space-before.optimum=\"5pt\" font-size=\"8pt\">"._("Anzahl der Teilnehmer").": ".$anz_nutzer."</fo:block></fo:table-cell>\n";
        $txt .= "                  <fo:table-cell ><fo:block space-before.optimum=\"5pt\" font-size=\"8pt\"></fo:block></fo:table-cell>\n";
        $txt .= "                </fo:table-row>\n";

        $txt .= "                <fo:table-row>\n";
        $txt .= "                  <fo:table-cell ><fo:block space-before.optimum=\"5pt\" font-size=\"8pt\"><fo:inline font-family=\"Symbol\">&#x2211;</fo:inline> ".$gesamte_antworten." "._("Antworten").".</fo:block></fo:table-cell>\n";
        $txt .= "                  <fo:table-cell ><fo:block space-before.optimum=\"5pt\" font-size=\"8pt\"></fo:block></fo:table-cell>\n";
        $txt .= "                </fo:table-row>\n";
    } else {
        $txt .= "                <fo:table-row>\n";
        $txt .= "                  <fo:table-cell ><fo:block space-before.optimum=\"5pt\" font-size=\"8pt\">"._("Anzahl der Teilnehmer").": ".$anz_nutzer."</fo:block></fo:table-cell>\n";
        $txt .= "                  <fo:table-cell ><fo:block space-before.optimum=\"5pt\" font-size=\"8pt\"></fo:block></fo:table-cell>\n";
        $txt .= "                </fo:table-row>\n";

        $txt .= "                <fo:table-row>\n";
        $txt .= "                  <fo:table-cell ><fo:block space-before.optimum=\"5pt\" font-size=\"8pt\"><fo:inline font-family=\"Symbol\">&#x2205;</fo:inline>-"._("Antwort").": ".$antwort_durchschnitt.($has_residual==0 ? "" : "*")."</fo:block></fo:table-cell>\n";
        $txt .= "                  <fo:table-cell ><fo:block space-before.optimum=\"5pt\" font-size=\"8pt\"><fo:inline font-family=\"Symbol\">&#x2211;</fo:inline> ".$gesamte_antworten." "._("Antworten").".</fo:block></fo:table-cell>\n";
        $txt .= "                </fo:table-row>\n";

    }

    if ($has_residual) {
        $txt .= "                <fo:table-row>\n";
        $txt .= "                  <fo:table-cell ><fo:block space-before.optimum=\"5pt\" font-size=\"8pt\">*"._("Werte ohne Enthaltungen").".</fo:block></fo:table-cell>\n";
        $txt .= "                  <fo:table-cell ><fo:block space-before.optimum=\"5pt\" font-size=\"8pt\"></fo:block></fo:table-cell>\n";
        $txt .= "                </fo:table-row>\n";
    }

    $ret_array["txt"] = $txt;

        return $ret_array;

}

function groups ($parent_id) {
    global $cssSw, $ausgabeformat, $fo_file, $auth, $global_counter, $local_counter, $tmp_path_export, $pattern, $replace;
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
            fputs($fo_file,"    <!-- Groupblock -->\n");
            fputs($fo_file,"    <fo:block font-variant=\"small-caps\" font-weight=\"bold\" text-align=\"start\" space-after.optimum=\"2pt\" background-color=\"lightblue\" space-before.optimum=\"10pt\">\n");
            if (do_template("show_group_headline"))
                fputs($fo_file,"      ".$global_counter.". ".preg_replace($pattern,$replace,smile(htmlspecialchars($db_groups->f("title")),TRUE))."\n");
            fputs($fo_file,"    </fo:block>\n");
        } else {
            $local_counter += 1;

                        $group_type = "normal";

            $db_group_type = new DB_Seminar();
                        $db_group_type->query(sprintf("SELECT * FROM eval_group_template WHERE evalgroup_id='%s'",$db_groups->f("evalgroup_id")));
                        if ($db_group_type->next_record()) $group_type = $db_group_type->f("group_type");

            fputs($fo_file,"    <!-- Questionblock -->\n");
            fputs($fo_file,"    <fo:block font-variant=\"small-caps\" font-weight=\"bold\" text-align=\"start\" background-color=\"grey\" color=\"white\" space-after.optimum=\"10pt\">\n");
            if (do_template("show_questionblock_headline"))
                fputs($fo_file,"      ".$global_counter.".".$local_counter.". ".preg_replace($pattern,$replace,smile(htmlspecialchars($db_groups->f("title")),TRUE))."\n");
            fputs($fo_file,"    </fo:block>\n");
        }

        if ($db_groups->f("child_type")=="EvaluationQuestion") {

            $db_questions = new DB_Seminar();
            $db_questions->query(sprintf("SELECT * FROM evalquestion WHERE parent_id='%s' ORDER BY position",$db_groups->f("evalgroup_id")));

            $local_question_counter = 0;
            $answer_arr = array();

            while ($db_questions->next_record()) {
                $db_questions_user = new DB_Seminar();
                                $db_questions_user->query(sprintf("SELECT COUNT(DISTINCT eau.user_id) anz FROM evalanswer ea, evalanswer_user eau WHERE ea.parent_id='%s' AND eau.evalanswer_id=ea.evalanswer_id",$db_questions->f("evalquestion_id")));
                $db_questions_user->next_record();

                if ($group_type=="normal") {

                $local_question_counter += 1;
                fputs($fo_file,"    <!-- Question -->\n");
                fputs($fo_file,"    <fo:block text-align=\"start\" font-weight=\"bold\" space-before.optimum=\"10pt\" space-after.optimum=\"10pt\">\n");
                if (do_template("show_questions")) {
                    fputs($fo_file,"      ".$global_counter.".".$local_counter.".".$local_question_counter.". ".preg_replace($pattern,$replace,smile(htmlspecialchars($db_questions->f("text")),TRUE))."\n");
                }
                fputs($fo_file,"    </fo:block>\n");
                fputs($fo_file,"    <!-- table start -->\n");
                fputs($fo_file,"    <fo:table table-layout=\"fixed\" border-width=\".1mm\" space-after.optimum=\"10pt\">\n");
                if (!($freetype)) {
                    fputs($fo_file,"      <fo:table-column column-width=\"100mm\"/>\n");
                    fputs($fo_file,"      <fo:table-column column-width=\"60mm\"/>\n");
                } else {
                    fputs($fo_file,"      <fo:table-column column-width=\"160mm\"/>\n");
                }
                fputs($fo_file,"      <fo:table-body>\n");
                fputs($fo_file,"        <fo:table-row >\n");
                fputs($fo_file,"          <fo:table-cell ><fo:block start-indent=\"3mm\" end-indent=\"3mm\" padding-left=\"3mm\" padding-right=\"3mm\" padding-top=\"4mm\" padding-bottom=\"4mm\">\n");
                fputs($fo_file,"            <!-- table start -->\n");
                fputs($fo_file,"            <fo:table table-layout=\"fixed\">\n");
                if (!($freetype)) {
                    fputs($fo_file,"              <fo:table-column column-width=\"60mm\"/>\n");
                    fputs($fo_file,"              <fo:table-column column-width=\"40mm\"/>\n");
                } else {
                    fputs($fo_file,"              <fo:table-column column-width=\"160mm\"/>\n");
                }
                fputs($fo_file,"              <fo:table-body>\n");


                } // ($group_type=="normal")

                if (!($freetype)) {
                    // Keine Freitext-Eingabe
                    $ret = answers($db_questions->f("evalquestion_id"), $db_questions_user->f("anz"), $db_questions->f("type"));
                    $ret["frage"] = $db_questions->f("text");
                                        array_push($answer_arr, $ret);
                                        if ($group_type=="normal") fputs($fo_file, $ret["txt"]);
                } else {
                    // Freitext
                    freetype_answers($db_questions->f("evalquestion_id"), $db_questions_user->f("anz"));
                }


                if ($group_type=="normal") {

                    fputs($fo_file,"              </fo:table-body>\n");
                    fputs($fo_file,"            </fo:table>\n");
                    fputs($fo_file,"            <!-- table end -->\n");
                    fputs($fo_file,"          </fo:block></fo:table-cell>\n");
                    if (!($freetype)) {
                        fputs($fo_file,"          <fo:table-cell ><fo:block start-indent=\"3mm\" end-indent=\"3mm\" padding-left=\"3mm\" padding-right=\"3mm\" padding-top=\"4mm\" padding-bottom=\"4mm\">\n");
                        if (do_template("show_graphics")) {
                            fputs($fo_file,"            <fo:external-graphic content-width=\"70mm\" content-height=\"60mm\" src=\"url('file:///".$tmp_path_export."/evalsum".$db_questions->f("evalquestion_id").$auth->auth["uid"].".".$GLOBALS['EVAL_AUSWERTUNG_GRAPH_FORMAT']."')\"/>\n");
                        }
                        fputs($fo_file,"          </fo:block></fo:table-cell>\n");
                    }
                    fputs($fo_file,"        </fo:table-row>\n");
                    fputs($fo_file,"      </fo:table-body>\n");
                    fputs($fo_file,"    </fo:table>\n");
                    fputs($fo_file,"  <!-- table end -->\n");

                } // ($group_type=="normal")



            }

            if (!($freetype) && $group_type=="table") {

                $antworten_angezeigt = FALSE;
                                $i = 0;
                                $has_residual = 0;
                $col_count = count($answer_arr[0]["antwort_texte"]);

                fputs($fo_file,"    <!-- table start -->\n");
                                fputs($fo_file,"    <fo:table table-layout=\"fixed\" border-width=\".1mm\" border-style=\"solid\" space-after.optimum=\"10pt\">\n");
                fputs($fo_file,"              <fo:table-column/>\n");
                for ($a=1; $a<=$col_count; $a++)
                    fputs($fo_file,"              <fo:table-column  column-width=\"15mm\"/>\n");
                fputs($fo_file,"              <fo:table-column  column-width=\"8mm\"/>\n");
                fputs($fo_file,"              <fo:table-column  column-width=\"8mm\"/>\n");
                fputs($fo_file,"              <fo:table-column  column-width=\"15mm\"/>\n");


                fputs($fo_file,"      <fo:table-body>\n");

                                foreach ($answer_arr as $k1=>$questions) { // Oberste Ebene, hier sind die Questions abgelegt
                    if (!($antworten_angezeigt)) {
                                            $i = 1;
                        fputs($fo_file,"        <fo:table-row >\n");
                        fputs($fo_file,"          <fo:table-cell ><fo:block space-before.optimum=\"10pt\">\n");
                        fputs($fo_file,"          </fo:block></fo:table-cell >");
                        foreach ($questions["antwort_texte"] as $k2=>$v2) { // 1. Unterebene, hier sind die Antworttexte abgelegt
                            fputs($fo_file,"          <fo:table-cell ><fo:block space-before.optimum=\"10pt\" font-size=\"7pt\">\n");
                            fputs($fo_file, preg_replace($pattern,$replace,smile(htmlspecialchars($v2),TRUE)));
                            fputs($fo_file,"          </fo:block></fo:table-cell >");
                        }

                        fputs($fo_file,"          <fo:table-cell ><fo:block text-align=\"center\" space-before.optimum=\"10pt\" font-size=\"7pt\" font-family=\"Symbol\">\n");
                        fputs($fo_file, "&#x2211;");
                        fputs($fo_file,"          </fo:block></fo:table-cell >");
                        fputs($fo_file,"          <fo:table-cell ><fo:block text-align=\"center\" space-before.optimum=\"10pt\" font-size=\"7pt\" font-family=\"Symbol\">\n");
                        fputs($fo_file, "&#x2205;");
                        fputs($fo_file,"          </fo:block></fo:table-cell >");
                        fputs($fo_file,"          <fo:table-cell ><fo:block text-align=\"center\" space-before.optimum=\"10pt\" font-size=\"7pt\">\n");
                        fputs($fo_file, _("Teilnehmer"));
                        fputs($fo_file,"          </fo:block></fo:table-cell >");

                        fputs($fo_file,"        </fo:table-row>\n");
                        $antworten_angezeigt = TRUE;
                    }

                    fputs($fo_file,"        <fo:table-row >\n");

                    fputs($fo_file,"          <fo:table-cell ><fo:block font-size=\"6pt\" start-indent=\"3mm\">\n");
                    fputs($fo_file, $questions["frage"]);
                    fputs($fo_file,"          </fo:block></fo:table-cell >");

                    foreach ($questions["auswertung"] as $k3=>$v3) {
                        fputs($fo_file,"          <fo:table-cell ><fo:block font-size=\"7pt\">\n");
                        fputs($fo_file, $v3[0]." (".$v3[1]."%)"); // 2. Unterebene, hier sind die Zahlen abgelegt
                        if ($v3[2]) fputs($fo_file, " (".$v3[2]."%)*");
                        fputs($fo_file,"          </fo:block></fo:table-cell >");
                    }

                    $i=0;
                    if ($questions["has_residual"]) $has_residual = 1;

                    fputs($fo_file,"          <fo:table-cell ><fo:block text-align=\"center\" font-size=\"7pt\">\n");
                    fputs($fo_file, $questions["summe_antworten"]);
                    fputs($fo_file,"          </fo:block></fo:table-cell >");

                    fputs($fo_file,"          <fo:table-cell ><fo:block text-align=\"center\" font-size=\"7pt\">\n");
                    fputs($fo_file, $questions["antwort_durchschnitt"].($questions["has_residual"]?"*":""));
                    fputs($fo_file,"          </fo:block></fo:table-cell >");

                    fputs($fo_file,"          <fo:table-cell ><fo:block text-align=\"center\" font-size=\"7pt\">\n");
                    fputs($fo_file, $questions["anzahl_teilnehmer"]);
                    fputs($fo_file,"          </fo:block></fo:table-cell >");

                    fputs($fo_file,"        </fo:table-row>\n");

                }

                fputs($fo_file,"        <fo:table-row >\n");
                fputs($fo_file,"          <fo:table-cell ><fo:block start-indent=\"3mm\" space-after.optimum=\"10pt\" font-size=\"7pt\">\n");
                if ($has_residual) fputs($fo_file, "* "._("Werte ohne Enthaltungen").".");
                fputs($fo_file,"          </fo:block></fo:table-cell >");
                fputs($fo_file,"        </fo:table-row >\n");

                fputs($fo_file,"      </fo:table-body>\n");
                fputs($fo_file,"    </fo:table>\n");
                                fputs($fo_file,"  <!-- table end -->\n");

            }
        }

        groups($db_groups->f("evalgroup_id"));

    }

}


$db = new DB_Seminar();

if ($staff_member) $db->query(sprintf("SELECT * FROM eval WHERE eval_id='%s'",$eval_id));
else $db->query(sprintf("SELECT * FROM eval WHERE eval_id='%s' AND author_id='%s'",$eval_id,$auth->auth["uid"]));

if ($db->next_record()) {
    // Evaluation existiert auch...

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
    if (file_exists($tmp_path_export."/evalsum".$db->f("eval_id").$auth->auth["uid"].".fo")) unlink($tmp_path_export."/evalsum".$db->f("eval_id").$auth->auth["uid"].".fo");
    if (file_exists($tmp_path_export."/evalsum".$db->f("eval_id").$auth->auth["uid"].".pdf")) unlink($tmp_path_export."/evalsum".$db->f("eval_id").$auth->auth["uid"].".pdf");

    $fo_file = fopen($tmp_path_export."/evalsum".$db->f("eval_id").$auth->auth["uid"].".fo","w");

    // ----- START HEADER -----

    fputs($fo_file,"<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n");
    fputs($fo_file,"<fo:root xmlns:fo=\"http://www.w3.org/1999/XSL/Format\">\n");
    fputs($fo_file,"  <!-- defines the layout master -->\n");
    fputs($fo_file,"  <fo:layout-master-set>\n");
    fputs($fo_file,"    <fo:simple-page-master master-name=\"first\" page-height=\"29.7cm\" page-width=\"21cm\" margin-top=\"1cm\" margin-bottom=\"2cm\" margin-left=\"2.5cm\" margin-right=\"2.5cm\">\n");
    fputs($fo_file,"      <fo:region-body margin-top=\"1cm\" margin-bottom=\"1.5cm\"/>\n");
    fputs($fo_file,"      <fo:region-before extent=\"1cm\"/>\n");
    fputs($fo_file,"      <fo:region-after extent=\"1.5cm\"/>\n");
    fputs($fo_file,"      </fo:simple-page-master>\n");
    fputs($fo_file,"    </fo:layout-master-set>\n");
    fputs($fo_file,"  <!-- starts actual layout -->\n");
    fputs($fo_file,"  <fo:page-sequence master-reference=\"first\">\n");
    fputs($fo_file,"  <fo:static-content flow-name=\"xsl-region-after\">\n");
    fputs($fo_file,"    <fo:block text-align=\"center\" font-size=\"8pt\" font-family=\"serif\" line-height=\"14pt\" >\n");
    fputs($fo_file,"    "._("Erstellt mit Stud.IP")." $SOFTWARE_VERSION - "._("Seite")." <fo:page-number/>\n");
    fputs($fo_file,"    </fo:block>\n");
    fputs($fo_file,"    <fo:block text-align=\"center\" font-size=\"8pt\" font-family=\"serif\" line-height=\"14pt\" >\n");
    fputs($fo_file,"      <fo:basic-link color=\"blue\" external-destination=\"$ABSOLUTE_URI_STUDIP\">$UNI_NAME_CLEAN</fo:basic-link>\n");
    fputs($fo_file,"    </fo:block>\n");
    fputs($fo_file,"  </fo:static-content>\n");
    fputs($fo_file,"  <fo:flow flow-name=\"xsl-region-body\">\n");
    fputs($fo_file,"    <!-- this defines a title level 1-->\n");
    fputs($fo_file,"    <fo:block font-size=\"18pt\" font-variant=\"small-caps\" font-family=\"sans-serif\" line-height=\"24pt\" space-after.optimum=\"15pt\" background-color=\"blue\" color=\"white\" text-align=\"center\" padding-top=\"3pt\">\n");
    fputs($fo_file,"      "._("Stud.IP Evaluationsauswertung")."\n");
    fputs($fo_file,"    </fo:block>\n");
    fputs($fo_file,"    <!-- this defines a title level 2-->\n");

    fputs($fo_file,"    <fo:block font-size=\"16pt\" font-weight=\"bold\" font-family=\"sans-serif\" space-before.optimum=\"10pt\" space-after.optimum=\"15pt\" text-align=\"center\">\n");
    fputs($fo_file,"      ".preg_replace($pattern,$replace,smile(htmlspecialchars($db->f("title")),TRUE))."\n");
    fputs($fo_file,"    </fo:block>\n");
    fputs($fo_file,"    <fo:block text-align=\"start\" line-height=\"10pt\" font-size=\"8pt\">\n");
    fputs($fo_file,    _("Diese Evaluation ist folgenden Bereichen zugeordnet:"));
    fputs($fo_file,"    </fo:block>\n");
    foreach($eval_ranges_names as $n) {
        fputs($fo_file,"    <fo:block text-align=\"start\" margin-left=\"0.5cm\" line-height=\"10pt\" font-size=\"8pt\">\n");
        fputs($fo_file, htmlspecialchars($n));
        fputs($fo_file,"    </fo:block>\n");
    }



    if (do_template("show_total_stats")) {
        fputs($fo_file,"    <fo:block text-align=\"start\" space-before.optimum=\"10pt\" line-height=\"10pt\" font-size=\"8pt\">\n");
        fputs($fo_file,"      ".$db_number_of_votes->f("anz")." "._("Teilnehmer insgesamt").".\n");
        fputs($fo_file,"      "._("Die Teilnahme war")." ". ($db->f("anonymous")==0 ? _("nicht") : "") . " "._("anonym").".\n");
        fputs($fo_file,"      "._("Eigentümer").": ".$db_owner->f("fullname").". "._("Erzeugt am").": ".date("d.m.Y H:i:s")."\n");
        fputs($fo_file,"    </fo:block>\n");
    }

    // ----- ENDE HEADER -----

    groups($db->f("eval_id"));

    // ----- START FOOTER -----

    fputs($fo_file,"    </fo:flow>\n");
    fputs($fo_file,"  </fo:page-sequence>\n");
    fputs($fo_file,"</fo:root>\n");

    // ----- ENDE FOOTER -----

    fclose($fo_file);

    $pdffile = "$tmp_path_export/" . md5($db->f("eval_id").$auth->auth["uid"]);

    $str = $FOP_SH_CALL." $tmp_path_export/evalsum".$db->f("eval_id").$auth->auth["uid"].".fo $pdffile";

    $err = exec($str);

    if (file_exists($pdffile) && filesize($pdffile)) {
        header('Location: ' . getDownloadLink( basename($pdffile), "evaluation.pdf", 2));
        unlink($tmp_path_export."/evalsum".$db->f("eval_id").$auth->auth["uid"].".fo");
    } else {
        echo "Fehler beim PDF-Export!<BR>".$err;
        echo "<BR>\n".$str;
    }
} else {
    // Evaluation existiert nicht...
    echo _("Evaluation NICHT vorhanden oder keine Rechte!");
}
// Save data back to database.
page_close();
?>
