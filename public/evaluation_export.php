<?
# Lifter002: TODO
# Lifter005: TODO - Popup: "Bitte haben Sie etwas Geduld"
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* Export-mainfile. Calls the submodules.
*
*
*
* @author
* @access       public
* @modulegroup  export_modules
* @module       export
* @package      Export
*/
// +--------------------------------------------------------------------------+
// This file is part of Stud.IP
// export.php
//
// Copyright (c) 2002 Arne Schroeder <schroeder@data-quest.de>
// Suchi & Berg GmbH <info@data-quest.de>
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


require '../lib/bootstrap.php';

unregister_globals();
$o_mode = Request::option('o_mode');
$xml_file_id = Request::option('xml_file_id',"");
$xslt_filename = Request::quoted('xslt_filename');
$page = Request::option('page');
$filter = Request::option('filter');
$ex_type = Request::quoted('ex_type');
$format = Request::option('format');
$choose = Request::quoted('choose');
$range_id = Request::option('range_id');

if (($o_mode != "direct") AND ($o_mode != "passthrough")) {
  page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
  $perm->check("tutor");
  include ('lib/seminar_open.php'); // initialise Stud.IP-Session
}

//$i_page = "meine_seminare.php";
//$EXPORT_ENABLE = TRUE;
//$GLOBALS['PATH_EXPORT'] = "export";
// -- here you have to put initialisations for the current page

require_once ('lib/visual.inc.php');
require_once 'lib/functions.php';
require_once ('lib/msg.inc.php');
require_once ('config.inc.php');

# Include all required files ================================================ #
require_once('lib/evaluation/evaluation.config.php');
require_once('lib/evaluation/classes/EvaluationQuestion.class.php');
require_once('lib/evaluation/classes/db/EvaluationQuestionDB.class.php');
require_once('lib/evaluation/classes/EvaluationAnswer.class.php');
require_once('lib/evaluation/classes/db/EvaluationAnswerDB.class.php');

require_once ('lib/seminar_open.php');
require_once ('lib/include/html_head.inc.php');
require_once ('lib/include/header.php');
require_once (EVAL_LIB_COMMON);
require_once (EVAL_LIB_OVERVIEW);
require_once (EVAL_FILE_EVAL);
require_once (EVAL_FILE_EVALDB);
# ====================================================== end: including files #



if (get_config('EXPORT_ENABLE'))
{
    // Zurueckbutton benutzt?
    if (Request::submitted('back'))
    {
        if ($o_mode == "choose")
        {
            if ($page == 4)
            {
                if (get_config('skip_page_3'))
                    $page = 1;
                else
                    $page = 2;
            }
            elseif ($page>1)
                $page = $page-2;
            else
            {
                unset($xml_file_id);
                unset($page);
                $o_mode= "start";
            }
        }
    }
    if (($o_mode != "passthrough") AND ($o_mode != "direct"))
    {
        ?><script LANGUAGE="JavaScript">
        <!-- Begin

        var exportproc=false;

        function export_end()
        {
            if (exportproc)
            {
                msg_window.close();
            }
            return;
        }

        function export_start()
        {
            msg_window=window.open("","messagewindow","height=250,width=200,left=20,top=20,scrollbars=no,resizable=no,toolbar=no");
            msg_window.document.write("<html><head><title><? echo _("Daten-Export");?></title></head>");
            msg_window.document.write("<body bgcolor='#ffffff'><center><p><img src='pictures/alienupload.gif' width='165' height='125'></p>");
            msg_window.document.write("<p><font face='arial, helvetica, sans-serif'><b>&nbsp;<? printf(_("Die Daten werden exportiert. %sBitte haben Sie etwas Geduld!"),"<br>&nbsp;");?><br></font></p></body></html>");
            exportproc=true;
            return true;
        }
        // End -->
        </script>
        <body onUnLoad="export_end()">
        <?
    }

    if ((empty($range_id) AND empty($xml_file_id) AND empty($o_mode) AND empty($ex_type)) OR ($o_mode == "start"))
    {
        include($GLOBALS['PATH_EXPORT'] . "/export_start.inc.php");
        $start_done = true;
    }
    if (($page==2) AND get_config('XSLT_ENABLE') AND get_config('skip_page_3'))
        $page=3;
    

    //Exportmodul einbinden
    if (($page != 3) AND ($o_mode == "choose") AND ($export_error_num < 1))
    {
        include($GLOBALS['PATH_EXPORT'] . "/export_choose_xslt.inc.php");
        if ($export_error_num < 1)
            $xslt_choose_done = true;
    }

    if ( ($range_id != "") AND ($xml_file_id == "") AND ($o_mode != "start") AND (($o_mode != "choose") OR ($page == 3)))
    {
        include($GLOBALS['PATH_EXPORT'] . "/export_xml.inc.php");
        if ($export_error_num < 1)
            $xml_output_done = true;
    }

    if ( ($choose != "") AND ($format != "") AND ($format != "xml") AND ($XSLT_ENABLE) AND ($export_error_num==0) AND
        ( ($o_mode == "processor") OR ($o_mode == "passthrough") OR ($page == 3) ) )
    {
        include($GLOBALS['PATH_EXPORT'] . "/export_run_xslt.inc.php");
        if ($export_error_num < 1)
            $xslt_process_done = true;
    }

    if (($export_error_num < 1) AND ($xslt_process_done) AND ($format == "fo"))
        include($GLOBALS['PATH_EXPORT'] . "/export_run_fop.inc.php");

    if (($export_error_num < 1) AND (!$start_done) AND ((!$xml_output_done) OR ($o_mode != "file")) AND (!$xslt_choose_done) AND (!$xslt_process_done))
    {
        $export_pagename = "Exportmodul - Fehler!";
        $export_error = _("Fehlerhafter Seitenaufruf");
        $infobox = array(
        array ("kategorie"  => _("Information:"),
            "eintrag" => array  (
                            array ( "icon" => "icons/16/black/info.png",
                                    "text"  => _("Die Parameter, mit denen diese Seite aufgerufen wurde, sind fehlerhaft.")
                                 )
                            )
            )
        );
    }

    include($GLOBALS['PATH_EXPORT'] . "/export_view.inc.php");
}
else
{
    // Start of Output
    include ('lib/include/html_head.inc.php'); // Output of html head
    include ('lib/include/header.php');   // Output of Stud.IP head
    parse_window ("error§" . _("Das Exportmodul ist nicht eingebunden. Damit Daten im XML-Format exportiert werden k&ouml;nnen, muss das Exportmodul in den Systemeinstellungen freigeschaltet werden. Wenden Sie sich bitte an die Administratoren."), "§",
                _("Exportmodul nicht eingebunden"));
}


/* Initialize Test Process-------------------------------------------------
echo "MOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO! <br> ";
  echo "<font color=red>Beginn der Testphase...</font><br>";
/* Create objects ----------------------------------------------------------
$db  = new EvaluationDB ();
if ($db->isError)
  return EvalCommon::showErrorReport ($db, _("Datenbankfehler"));
$lib = new EvalOverview ($db, $perm, $user);
/* ------------------------------------------------------------ end: objects
/* Set variables -----------------------------------------------------------
$rangeID = ($rangeID) ? $rangeID : $SessSemName[1];
if (empty ($rangeID) || ($rangeID == get_username ($user->id)))
     $rangeID = $user->id;

$debug = 1;
/* ---------------------------------------------------------- end: variables

$evalArray = $db->getEvaluationIDs ($rangeID);
echo "Es wurde(n) ".count($evalArray)." Evaluation(en) gefunden...</font><br>";

$group1 = new EvaluationGroup ();
$group1->setTitle ("Ich bin Gruppe Nummer 1");
$group1->setText ("Ich bin der Text einer Gruppe1");


$group2 = new EvaluationGroup ();
$group2->setTitle ("Ich bin Gruppe Nummer 2");
$group2->setText ("Ich bin der Text einer Gruppe2");
$group2ID = $group2->getObjectID ();

$group21 = new EvaluationGroup ();
$group21->setTitle ("Ich bin Gruppe Nummer 2.1");
$group21->setText ("Ich bin der Text einer Gruppe2.1");
$group2->addChild ($group21);

$eval = new Evaluation ();
$eval->addRangeID ($rangeID);
$eval->setAuthorID ($user->id);
$eval->setTitle ("TestTitel");
$eval->setText ("Eine Textbeschreibung");
$eval->setStartdate (time ());
$eval->setStopdate (time ()*2);
$eval->setTimespan (NULL);
$eval->setAnonymous (YES);
$eval->setVisible (YES);


$question1 =  new EvaluationQuestion ();
$question1->setText("Wie gefaellt Ihnen Frage1?");
$question1->setRangeID($rangeID);

$answer1= new EvaluationAnswer();
$answer1->setText("Antwort1Text");
$question1->addChild($answer1);

$answer2= new EvaluationAnswer();
$answer2->setText("Antwort2Text");
$question1->addChild($answer2);

$group1->addchild($question1);

#echo "Frage1: ".$question1->getText ()."<br>";
#echo "Range_ID:".$question1->getRangeID()."<br>";
#$question1->toString();

$eval->addChild ($group2);
$eval->addChild ($group1);

echo "saveStart<br>";
$eval->save();
echo "saveEnd<br>";

#$question1->save();
#$answer1->save();
#$answer2->save();
echo "save Durchgeführt!!!<br>";

#$question1->changeType();
#$question1->setPosition();
#$question1->setMultiplechoice();
#$question1->setShare();

#$ID = $question->getParentID();
#echo "Startzeit: ".$eval->getStopdate ()."<br>";*/



page_close();
?>
