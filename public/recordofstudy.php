<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
 * Creates a record of study and exports the data to pdf
 *
 * @author      Christian Bauer <alfredhitchcock@gmx.net>
 * @copyright   2003 Stud.IP-Project
 * @access      public
 * @module      recordofstudy
 */

/* ************************************************************************** *
/*
/* the structure of the pdf-template:
/*
/*      -- form 'university'        // the name of the university
/*      -- form 'fieldofstudy'      // the field of study
/*      -- form 'studentname'       // the complete name of the student
/*      -- form 'semester'          // the semester
/*      -- form 'semesternumber'    // the semester number
/*
/*         (X := 0 -> last entry)/*
/*      -- form 'seminarnumber.X'   // the number of the seminar
/*      -- form 'tutor.X'           // the complete tutor name
/*      -- form 'sws.X'             // the average hours per semester
/*      -- form 'description.X'     // the name (+ discription) of the seminar
/*                                                                            *
/* ************************************************************************* */

/* ************************************************************************** *
/*                                                                            *
/* initialise Stud.IP-Session                                                 *
/*                                                                            *
/* ************************************************************************* */

require '../lib/bootstrap.php';

page_open (array ("sess" => "Seminar_Session", "auth" => "Seminar_Auth",
          "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check ("autor");

/* ************************************************************************** *
/*                                                                            *
/* including needed files                                                     *
/*                                                                            *
/* ************************************************************************* */

$FDF_USAGE_HINT=sprintf(_("Die Ausgabe wird in einem speziellen Format erzeugt, f�r das Sie den %sAcrobat Reader%s mit Browser- und Formularunterst�tzung ben�tigen."),"<a href='http://get.adobe.com/reader/' target='_blank'>","</a>");

include_once($PATH_EXPORT ."/recordofstudy.lib.php");
include_once($PATH_EXPORT ."/recordofstudyDB.php");
include ('lib/seminar_open.php');
require_once('config.inc.php');
require_once('lib/datei.inc.php');

// needed session-variables
if (empty($_SESSION['seminars'])) {
    $_SESSION['seminars'] = array();
}
if (empty($_SESSION['semestersAR'])) {
    $_SESSION['semestersAR'] = getSemesters();
}
if (empty($_SESSION['template'])) {
    $_SESSION['template'] = "";
}

$seminars    = $_SESSION['seminars'];
$semestersAR = $_SESSION['semestersAR'];
$template    = $_SESSION['template'];

/* **END*of*initialise*Stud.IP-Session*********************************** */

/* ************************************************************************** *
/*                                                                            *
/* identify the current site-mode                                             *
/*                                                                            *
/* ************************************************************************* */
$semester = Request::get('semester');
if ( (Request::submitted('semester_selected')) || (Request::submitted('add_seminars')) ||
    (Request::submitted('delete_seminars')))
    $mode = "edit";
elseif (Request::submitted('collect_information'))
    $mode = "pdf_assortment";
elseif (Request::int('create_pdf') == 1)
    $mode = "create_pdf";
else
    $mode = "new";

/* **END*of*identify*the*current*site-mode*********************************** */


/* ************************************************************************** *
/*                                                                            *
/* collecting the data                                                        *
/*                                                                            *
/* ************************************************************************* */
$infobox = createInfoxboxArray($mode);

if ($mode == "new"){
}
elseif ($mode == "edit"){
    // get the basic data
    if (Request::get('template')){
        $template = $_SESSION['template'] = Request::get('template');
    };

    $university = htmlReady(stripslashes(Request::get('university')));
    if (empty($university)) $university = htmlReady($GLOBALS['UNI_NAME_CLEAN']);
    $fieldofstudy = htmlReady(stripslashes(Request::get('fieldofstudy')));
    if (empty($fieldofstudy)) $fieldofstudy = getFieldOfStudy();
    $studentname = htmlReady(stripslashes(Request::get('studentname')));
    if (empty($studentname)) $studentname = getStudentname();
    $semesterid = htmlReady(stripslashes(Request::get('semesterid')));
    $semester = htmlReady(stripslashes(Request::get('semester')));
    if (empty($semester))
        $semester = $semestersAR[$semesterid]["name"];
    $semesternumber = htmlReady(Request::get('semesternumber'));

    $basicdata = array(
        "university"    => $university,
        "fieldofstudy"  => $fieldofstudy,
        "studentname"   => $studentname,
        "semester"      => $semester,
        "semesternumber"=> $semesternumber
    );

    // get the seminars from the db
    if (Request::submitted('semester_selected')) {
        $seminareAR = getSeminare($semesterid,Request::get('onlyseminars'));
    }
    // get the seminars from post
    else{
        $seminare_max = Request::get('seminare_max');
        $deletenumbers = 0;
        for($i=0;$i+1<=$seminare_max;$i++){

            // delete this entry
            if (($_POST['delete'.$i]) &&
              (!Request::submitted('add_seminars') && ((Request::get('delete'.$i))))){
                $deletenumbers++;
            }
            else{
                // adding this one to the current seminas-array
                $seminarnumber = htmlReady(stripslashes(Request::get('seminarnumber'.$i)));
                $tutor = htmlReady(Request::get('tutor'.$i));
                $sws = htmlReady(stripslashes(Request::get('sws'.$i)));
                $description = htmlReady(stripslashes(Request::get('description'.$i)));

                $seminareAR[$i-$deletenumbers] = array(
                    "id"            => $i,
                    "seminarid"     => $seminarid,
                    "seminarnumber" => $seminarnumber,
                    "tutor"         => $tutor,
                    "sws"           => $sws,
                    "description"   => $description
                );
            }
        }
    }

    // this is the new max of seminar_fields
    $seminars_max = $i;

    // add new ones
    if (Request::submitted('add_seminars') && (!($_POST['delete'.$i]))){
        $numberofnew = Request::get('newseminarfields');
        for($i=1;$i<=$numberofnew;$i++){
            $seminareAR[$i+$seminare_max] = array("id" => $i+$seminars_max);
        }

    }
}
elseif($mode == "pdf_assortment"){

    // the last entry
    $seminare_max = Request::get('seminare_max');

    // the basic data
    $university = stripslashes(Request::get('university'));
    $fieldofstudy = stripslashes(Request::get('fieldofstudy'));
    $studentname = stripslashes(Request::get('studentname'));
    $semester = stripslashes(Request::get('semester'));
    $semesternumber = stripslashes(Request::get('semesternumber'));
    $seminars = array (
        "university" => $university,
        "fieldofstudy" => $fieldofstudy,
        "studentname" => $studentname,
        "semester" => $semester,
        "semesternumber" => $semesternumber
    );
    // creating the seminare-arrays cut into ones with the size of 10
    $runner = 10;
    // $j is the current page
    for($j=0;$j<=$seminare_max/10;$j++){
        // $runner notices the last entry
        if ($j+1>$seminare_max/10)
            $runner = $seminare_max%10;
        // $i is the current page-entry (0-9)
        for($i=0;$i+1<=$runner;$i++){
                // $y is the running nummber from 0 -> last seminar
                $y = $i+($j*10);
                $seminars[$j][$i]["seminarnumber"] = stripslashes(Request::get('seminarnumber'.$y));
                $seminars[$j][$i]["tutor"] = stripslashes(Request::get('tutor'.$y));
                $seminars[$j][$i]["sws"] = stripslashes(Request::get('sws'.$y));
                $seminars[$j][$i]["description"] = stripslashes(Request::get('description'.$y));
        }
    }
    $exemptions = array (10,20,30,40,50,60,70,80,90,100);
    if (in_array($seminare_max,$exemptions))
        $j--;
    $seminars["numberofseminars"] = $seminare_max;
    $seminars["numberofpages"] = $j;
    
    $_SESSION['seminars'] = $seminars;
}
elseif($mode == 'create_pdf'){
    $pdf_file['full_path'] = $ABSOLUTE_URI_STUDIP . sprintf('sendfile.php?type=3&file_id=%1$s&file_name=%1$s', $record_of_study_templates[$template]['template']);
    $fdfAR = createFdfAR($seminars);
};

/* **END*of*collecting*the*data********************************************* */

/* ************************************************************************** *
/*                                                                            *
/* displays the site                                                          *
/*                                                                            *
/* ************************************************************************* */
PageLayout::setTitle(_("Veranstaltungs�bersicht erstellen"));
PageLayout::setHelpKeyword("Basis.Allgemeines");
Navigation::activateItem('/browse/my_courses/record_of_study');
// add skip link
SkipLinks::addIndex(_("Hauptinhalt"), 'main_content', 100);
ob_start();
if ($mode == "new"){
    printSelectSemester($infobox,$semestersAR);
}
elseif ($mode == "edit"){
    PageLayout::setTitle(PageLayout::getTitle() . ': ' . $basicdata["semester"]);

    // display a notice for the user?
    if (sizeof($seminareAR) > 10)
        $notice = "above_limit";
    elseif (sizeof($seminareAR) < 1)
        $notice = "empty";

    printRecordOfStudies($infobox, $basicdata, $seminareAR, $notice);
}
elseif ($mode == "pdf_assortment"){
    PageLayout::setTitle(PageLayout::getTitle() . ': ' . $seminars["semester"]);
    printPdfAssortment($infobox, $seminars);
}
elseif ($mode == "create_pdf"){
    ob_end_clean();
    $out = printPDF($pdf_file ,$fdfAR);
}

// if you wanna create a pdf no html-header should be send to the browser
if ($mode != 'create_pdf') {
    $out = ob_get_clean();
    require_once('lib/include/html_head.inc.php');
    require_once('lib/include/header.php');
}
echo $out;
if ($mode != 'create_pdf') {
    require_once 'lib/include/html_end.inc.php';
}
page_close ();
/* **END*of*displays*the*site*********************************************** */


/* ************************************************************************** *
/*                                                                            *
/* private functions                                                          *
/*                                                                            *
/* ************************************************************************* */

/**
 * creates an array with the data to fill the pdf
 *
 * @access  private
 * @param   string $seminars    the seminars
 * @returns array               an array with the data for the pdf
 *
 */
function createFdfAR($seminars){

    $page = Request::get('page')-1;
    $university = $seminars["university"];
    $fieldofstudy = $seminars["fieldofstudy"];
    $studentname = $seminars["studentname"];
    $semester = $seminars["semester"];
    $semesternumber = $seminars["semesternumber"];

    $fdfAR = array (
        "university" => $university,
        "fieldofstudy" => $fieldofstudy,
        "studentname" => $studentname,
        "semester" => $semester,
        "semesternumber" => $semesternumber
    );

    for($i=0;$i+1<=10;$i++){
            $fdfAR["seminarnumber.".$i] = (string)$seminars[$page][$i]["seminarnumber"];
            $fdfAR["tutor.".$i] = (string)$seminars[$page][$i]["tutor"];
            $fdfAR["sws.".$i] = (string)$seminars[$page][$i]["sws"];
            $fdfAR["description.".$i] = (string)$seminars[$page][$i]["description"];
    }
    return $fdfAR;
}

/**
 * creates a fdf and sends it to the browser
 *
 * @access  private
 * @param   string $pdf_file    the URL of the pdf-template
 * @param   array $pdf_data     the key and values to send
 *
 */
 function printPDF ($pdf_file, $pdf_data) {
     $fdf = "%FDF-1.2\n%����\n";
     $fdf .= "1 0 obj \n<< /FDF ";
     $fdf .= "<< /Fields [\n";
     foreach ($pdf_data as $key => $value){
         $key = addcslashes($key, "\n\r\t\\()");
         $value = addcslashes($value, "\n\r\t\\()");
         $fdf .= "<< /T ($key) /V ($value) >> \n";
     }
     $fdf .= "]\n/F (".$pdf_file["full_path"].") >>";
     $fdf .= ">>\nendobj\ntrailer\n<<\n";
     $fdf .= "/Root 1 0 R \n\n>>\n";
     $fdf .= "%%EOF";
     // Now we display the FDF data which causes Acrobat to start
     header("Expires: Mon, 12 Dec 2001 08:00:00 GMT");
     header("Last-Modified: " . gmdate ("D, d M Y H:i:s") . " GMT");
     header("Pragma: public");
     header("Cache-Control: private");
     header("Content-Type: application/vnd.fdf");
     header("Content-disposition: attachment; filename=\"".md5(uniqid('fdf',1)).".fdf\"");
     header("Content-Length: " . strlen($fdf));
     return $fdf;
 }

/**
 * replaces the semester token
 *
 * @access  public
 * @param   string $semname a semestertitle (exampl: 'SS 2003')
 * @returns string          the full semestertitle
 *
 */
function convertSemester($semname){
    global $SEMESTER;

    if ($semname[0].$semname[1] == "WS")
        return str_replace("WS", _("Wintersemester"),$semname);
    elseif ($semname[0].$semname[1] == "SS")
        return str_replace("SS", _("Sommersemester"),$semname);
    else
        return $semname;
}

/**
 * creates an array which conntains infobox labels
 *
 * @access  private
 * @param   string $mode    the current site-mode
 * @returns array           an array with infobox labels
 *
 */
function createInfoxboxArray($mode){
    if ($mode == "new"){
        $infobox = array    (
            array ("kategorie"  => "Information:",
                "eintrag" => array  (
                        array    (  "icon" => 'icons/16/black/info.png',
                                "text"  => _("Um eine Druckansicht Ihrer Veranstaltungen zu erstellen, w�hlen Sie bitte zun�chst das entsprechende Semester aus und engen gegebenenfalls Ihre Suchabfrage ein.")
                                ),
                        array    (  "icon" => 'icons/16/black/info.png',
                                "text" => $GLOBALS['FDF_USAGE_HINT']
                        ))
            ),
        );
    }
    elseif ($mode == "edit") {
        $infobox = array(
            array  ("kategorie"  => "Information:",
                    "eintrag" =>    array (
                            array ( "icon" => 'icons/16/black/info.png',
                                    "text"  => _("Erstellen Sie Ihre Veranstaltungs�bersicht und bearbeiten Sie fehlende oder falsche Eintr�ge.")
                                    ),
                        array    (  "icon" => 'icons/16/black/info.png',
                                "text" => $GLOBALS['FDF_USAGE_HINT']
                        )
                                    )
            ),
            array  ("kategorie" => "Aktionen:",
                    "eintrag" => array(
                        array ( "icon" => "icons/16/blue/trash.png",
                                "text"  => _("Entfernen Sie nicht ben�tigte Veranstaltungen mit Hilfe der Markierungsboxen und/oder f�gen Sie beliebig viele neue Veranstaltungen hinzu.")
                                ),
                        array ( "icon" => "icons/16/blue/info.png",
                                "text"  => _("Nachdem alle Informationen korrekt angezeigt werden, erstellen Sie Ihre Veranstaltungs�bersicht mit Hilfe des Buttons 'speichern'.")
                                ),
                                )
            ),
        );
    }
    elseif ($mode == "pdf_assortment"){
        $infobox = array(
            array  ("kategorie"  => "Information:",
                    "eintrag" =>    array (
                            array ( "icon" => 'icons/16/black/forum.png',
                                    "text"  => _("�ber den/die Link(s) k�nnen Sie sich Ihre Veranstaltungs�bersicht anzeigen lassen.")
                                    ),
                        array    (  "icon" => 'icons/16/black/info.png',
                                "text" => $GLOBALS['FDF_USAGE_HINT']
                        )
                        )
            )
        );
    };

    return $infobox;
}
?>
