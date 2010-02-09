<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* admin_semester.inc.php
* 
* create some constants for semester data
* 
* @access		public
* @package		studip_core
* @modulegroup	config
* @module		config_tools_semester.inc.php
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// config_tools_semester.inc.php
// hier werden ein paar Semester-Konstanten errechnet
// Copyright (C) 2003 Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>, 
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

// fill SEMESTER-Array with values, usable only once!!
// deprecated, but may be used in future (import-script)
function semester_makeSemesterArray() {
    $SEMESTER[1]=array("name"=>"WS 2000/01", "beginn"=>mktime(0,0,0,10,1,2000), "ende"=>mktime(23,59,59,3,31,2001), "vorles_beginn"=>mktime(0,0,0,10,14,2000), "vorles_ende"=>mktime(23,59,59,2,17,2001), "past"=>FALSE); 		# Daten ueber das WS 2000/01
    $SEMESTER[2]=array("name"=>"SS 2001", "beginn"=>mktime(0,0,0,4,1,2001), "ende"=>mktime(23,59,59,9,30,2001), "vorles_beginn"=>mktime(0,0,0,4,16,2001), "vorles_ende"=>mktime(23,59,59,7,15,2001), "past"=>FALSE); 			# Daten ueber das SS 2001
    $SEMESTER[3]=array("name"=>"WS 2001/02", "beginn"=>mktime(0,0,0,10,1,2001), "ende"=>mktime(23,59,59,3,31,2002), "vorles_beginn"=>mktime(0,0,0,10,15,2001), "vorles_ende"=>mktime(23,59,59,2,17,2002), "past"=>FALSE); 		# Daten ueber das WS 2001/02
    $SEMESTER[4]=array("name"=>"SS 2002", "beginn"=>mktime(0,0,0,4,1,2002), "ende"=>mktime(23,59,59,9,30,2002), "vorles_beginn"=>mktime(0,0,0,4,8,2002), "vorles_ende"=>mktime(23,59,59,7,7,2002), "past"=>FALSE); 				# Daten ueber das SS 2002
    $SEMESTER[5]=array("name"=>"WS 2002/03", "beginn"=>mktime(0,0,0,10,1,2002), "ende"=>mktime(23,59,59,3,31,2003), "vorles_beginn"=>mktime(0,0,0,10,14,2002), "vorles_ende"=>mktime(23,59,59,2,14,2003), "past"=>FALSE); 		# Daten ueber das WS 2002/03
    $SEMESTER[6]=array("name"=>"SS 2003", "beginn"=>mktime(0,0,0,4,1,2003), "ende"=>mktime(23,59,59,9,30,2003), "vorles_beginn"=>mktime(0,0,0,4,22,2003), "vorles_ende"=>mktime(23,59,59,7,20,2003), "past"=>FALSE); 			# Daten ueber das SS 2003
    $SEMESTER[7]=array("name"=>"WS 2003/04", "beginn"=>mktime(0,0,0,10,1,2003), "ende"=>mktime(23,59,59,3,31,2004), "vorles_beginn"=>mktime(0,0,0,10,20,2003), "vorles_ende"=>mktime(23,59,59,2,8,2004), "past"=>FALSE); 		# Daten ueber das WS 2003/04
    $SEMESTER[8]=array("name"=>"SS 2004", "beginn"=>mktime(0,0,0,4,1,2004), "ende"=>mktime(23,59,59,9,30,2004), "vorles_beginn"=>mktime(0,0,0,4,5,2004), "vorles_ende"=>mktime(23,59,59,7,11,2004), "past"=>FALSE);
    return $SEMESTER;

}

// Script to insert Array-Entries about each term into the database (see above, usable only once)
// deprecated, see above
function semester_insertIntoSemesterdataFromArray ($SEMESTER) {
    if ($db = new DB_Seminar) {
        //print_r($db);   
    }
    //$db->query("use studip");

    for ($i=1; $i <= sizeof($SEMESTER); $i++) {
        $tmp_id=md5(uniqid("lesukfhsdkuh"));
        if (!$db->query("INSERT INTO semester_data ".
                        "(semester_id,name,beginn,ende,vorles_beginn,vorles_ende) ".
                        "VALUES ('".$tmp_id."','".$SEMESTER[$i][name]."','".$SEMESTER[$i][beginn]."','".$SEMESTER[$i][ende]."',".
                        "'".$SEMESTER[$i][vorles_beginn]."','".$SEMESTER[$i][vorles_ende]."')")) {
                            echo "Fehler! Einf&uuml;gen in die DB!!";
                        }

    }
}

function semester_check_form_field($semesterdata) {
    //echo $startDay.$startMonth.$startYear;
    $errorcount = 0;
    if (strlen($semesterdata["name"])==0) {
        $error[$errorcount] .= _("Name");
        $errorcount++;
    }
    if (!(is_numeric($semesterdata["startDay"]) && is_numeric($semesterdata["startMonth"]) && is_numeric($semesterdata["startYear"]) && checkdate($semesterdata["startMonth"], $semesterdata["startDay"], $semesterdata["startYear"]))) {
        $error[$errorcount] .= _("Startdatum");
        $errorcount++;
    }
    if (!(is_numeric($semesterdata["expireDay"]) && is_numeric($semesterdata["expireMonth"]) && is_numeric($semesterdata["expireYear"]) && checkdate($semesterdata["expireMonth"], $semesterdata["expireDay"], $semesterdata["expireYear"]))) {
        $error[$errorcount] .= _("Enddatum");
        $errorcount++;
    }
    if (!(is_numeric($semesterdata["lectureStartDay"]) && is_numeric($semesterdata["lectureStartMonth"]) && is_numeric($semesterdata["lectureStartYear"]) && checkdate($semesterdata["lectureStartMonth"], $semesterdata["lectureStartDay"], $semesterdata["lectureStartYear"]))) {
        $error[$errorcount] .= _("Vorlesungsbeginn");
        $errorcount++;
    }
    if (!(is_numeric($semesterdata["lectureExpireDay"]) && is_numeric($semesterdata["lectureExpireMonth"]) && is_numeric($semesterdata["lectureExpireYear"]) && checkdate($semesterdata["lectureExpireMonth"], $semesterdata["lectureExpireDay"], $semesterdata["lectureExpireYear"]))) {
        $error[$errorcount] .= _("Vorlesungsende");
        $errorcount++;
    }
   
    if ($errorcount) {
        $data = _("Fehler! Folgende Felder sind ungültig:&nbsp;");
        for ($i=0; $i<count($error); $i++) {
            $data .= "$error[$i]";
            if ($i!=(count($error)-1)) {
                $data .= ",&nbsp;";
            } else {
                $data .= "&nbsp;";
            }
        }
        $data .= "!";
        return $data;
    }
    //now compare dates
    if ((mktime(0,0,0,$semesterdata["expireMonth"],$semesterdata["expireDay"],$semesterdata["expireYear"])-mktime(0,0,0,$semesterdata["startMonth"],$semesterdata["startDay"],$semesterdata["startYear"]))<0) {
        return _("Das Datum des Semesterendes muss größer sein als das Datum des Semesteranfangs");
    }
    if ((mktime(0,0,0,$semesterdata["lectureExpireMonth"],$semesterdata["lectureExpireDay"],$semesterdata["lectureExpireYear"])-mktime(0,0,0,$semesterdata["lectureStartMonth"],$semesterdata["lectureStartDay"],$semesterdata["lectureStartYear"]))<0) {
        return _("Das Datum des Vorlesungsendes muss größer sein als das Datum des Vorlesunganfangs");
    }
    if (((mktime(0,0,0,$semesterdata["lectureStartMonth"],$semesterdata["lectureStartDay"],$semesterdata["lectureStartYear"])-mktime(0,0,0,$semesterdata["startMonth"],$semesterdata["startDay"],$semesterdata["startYear"]))<0) || ((mktime(0,0,0,$semesterdata["expireMonth"],$semesterdata["expireDay"],$semesterdata["expireYear"])-mktime(0,0,0,$semesterdata["lectureExpireMonth"],$semesterdata["lectureExpireDay"],$semesterdata["lectureExpireYear"]))<0)) {
        return _("Der Vorlesungszeitraum muss innerhalb des Semesters liegen");
    }
    
    return 1;


}

function semester_check_overlap_semester($semesterdata) {
	$semester = new SemesterData;
	$allSemesters = $semester->getAllSemesterData();
	$semesterdata = semester_make_single_data_to_timestamp($semesterdata);
	for ($i=0;$i<count($allSemesters);$i++) {
		if (($semesterdata["beginn"]<$allSemesters[$i]["beginn"]) && ($semesterdata["ende"]>$allSemesters[$i]["ende"])) {
			if ($semesterdata["semester_id"]==$allSemesters[$i]["semester_id"]) {
			;
			} else {
				$error = 1;
			}
		}
	}
	return $error;

}


function semester_make_time_from_fields($day,$month,$year) {
	return mktime(0,0,0,$month,$day,$year);
}

function semester_make_new_semester_button($link) {
    $button = "<tr><td class=\"blank\" colspan=2><font size=2><b><a href=\"".$link."?new=1\">&nbsp;"._("Neues Semester anlegen")."</a><b></font><br><br></td></tr>";
    return $button;
}

function semester_make_timestamp_data_to_single_data($semesterdata) {
	$semesterdata["startDay"] = date("d",$semesterdata["beginn"]);	
	$semesterdata["startMonth"] = date("n",$semesterdata["beginn"]);
	$semesterdata["startYear"] = date("Y",$semesterdata["beginn"]);
	$semesterdata["expireDay"] = date("d",$semesterdata["ende"]);
	$semesterdata["expireMonth"] = date("n",$semesterdata["ende"]);
	$semesterdata["expireYear"] = date("Y",$semesterdata["ende"]);
	$semesterdata["lectureStartDay"] = date("d",$semesterdata["vorles_beginn"]);
	$semesterdata["lectureStartMonth"] = date("n",$semesterdata["vorles_beginn"]);
	$semesterdata["lectureStartYear"] = date("Y",$semesterdata["vorles_beginn"]);
	$semesterdata["lectureExpireDay"] = date("d",$semesterdata["vorles_ende"]);
	$semesterdata["lectureExpireMonth"] = date("n",$semesterdata["vorles_ende"]);
	$semesterdata["lectureExpireYear"] = date("Y",$semesterdata["vorles_ende"]);
	return $semesterdata;
}

function semester_make_single_data_to_timestamp($semesterdata) {
	$semesterdata["beginn"] = mktime(0,0,0,$semesterdata["startMonth"],$semesterdata["startDay"],$semesterdata["startYear"]);
	$semesterdata["ende"] = mktime(23,59,59,$semesterdata["expireMonth"],$semesterdata["expireDay"],$semesterdata["expireYear"]);
	$semesterdata["vorles_beginn"] = mktime(0,0,0,$semesterdata["lectureStartMonth"],$semesterdata["lectureStartDay"],$semesterdata["lectureStartYear"]);
	$semesterdata["vorles_ende"] = mktime(23,59,59,$semesterdata["lectureExpireMonth"],$semesterdata["lectureExpireDay"],$semesterdata["lectureExpireYear"]);
	return $semesterdata;
}

//make new Semester-Entry-Form
function semester_show_new_semester_form($link, $cssSw, $semesterdata, $modus="") {

	$data =     "<form method=\"POST\" name=\"newSemester\" action=\"".$link."\">";
	$data .=    "<tr><td class=\"";
	$cssSw->switchClass(); 
	$data .=    "".$cssSw->getClass()."\"><font size=2><b>"._("Name des Semesters:")."</b></font></td><td class=".$cssSw->getClass()."><input type=\"text\" name=\"semesterdata[name]\" value=\"".$semesterdata["name"]."\"size=60 maxlength=254></td></tr>";
	$data .=    "<tr><td class=\"".$cssSw->getClass()."\"><font size=2><b>"._("Beschreibung:")."</b></font></td><td class=\"".$cssSw->getClass()."\"><textarea cols=50 ROWS=4 name=\"semesterdata[description]\">".$semesterdata["description"]."</textarea></td></tr>";
	$cssSw->switchClass();
	$data .= "<tr><td height=50 colspan=2 class=\"".$cssSw->getClass()."\"><font size=2><b>"._("Bitte geben Sie den zeitlichen Rahmen des Semester ein")."</b></font>";
	if ($modus=="change") {
		$data .= "<font size=2 color=\"red\">&nbsp;&nbsp;&nbsp;"._("Das Startdatum kann nur bei Semestern ge&auml;ndert werden, in denen keine Veranstaltungen liegen!")."</font>";
	}
	$data .= "</td></tr>";
	$cssSw->switchClass(); 
	// hier if-Abfrage, da Start des Semesters nur geändert werden darf, wenn das Semester leer ist
	// Grund: Studip-Code ;)
	if (isset($semesterdata["startDay"]) && isset($semesterdata["startMonth"]) && isset($semesterdata["startYear"]) && (strlen($semesterdata["semester_id"])!=0)) {
		if (semester_count_absolut_seminars_in_semester($semesterdata["semester_id"])) {
			$data .=    "<tr><td class=\"".$cssSw->getClass()."\"><table cellspacing=0 cellpadding=0 border=0><tr><td width=\"40%\"><font size=2>"._("Anfang:")."</font></td><td class=\"".$cssSw->getClass()."\"><td width=\"\">".$semesterdata["startDay"];
			$data .=    ".</td>";
			//$data .=    "<td width=\"\" class=\"".$cssSw->getClass()."\"><input type=\"text\" name=\"StartMonth\" value=\"".$startMonth."\" size=\"2\" maxlength=\"2\">";
			$data .=    "<td width=\"\" class=\"".$cssSw->getClass()."\">";
			$semesterdata["startMonth"] == 1 ? $data .= ""._("Januar")." " : $data .= "";
			$semesterdata["startMonth"] == 2 ? $data .= ""._("Februar")." " : $data .= "";
			$semesterdata["startMonth"] == 3 ? $data .= ""._("M&auml;rz")." " : $data .= "";
			$semesterdata["startMonth"] == 4 ? $data .= ""._("April")." " : $data .= "";
			$semesterdata["startMonth"] == 5 ? $data .= ""._("Mai")." " : $data .= "";
			$semesterdata["startMonth"] == 6 ? $data .= ""._("Juni")." " : $data .= "";
			$semesterdata["startMonth"] == 7 ? $data .= ""._("Juli")." " : $data .= "";
			$semesterdata["startMonth"] == 8 ? $data .= ""._("August")." " : $data .= "";
			$semesterdata["startMonth"] == 9 ? $data .= ""._("September")." " : $data .= "";
			$semesterdata["startMonth"] == 10 ? $data .= ""._("Oktober")." " : $data .= "";
			$semesterdata["startMonth"] == 11 ? $data .= ""._("November")." " : $data .= "";
			$semesterdata["startMonth"] == 12 ? $data .= ""._("Dezember")." " : $data .= "";
			$data .=    "&nbsp;</td>";
			$data .=    "<td width=\"\" class=\"".$cssSw->getClass()."\">".$semesterdata["startYear"];
			$data .=    "</td></tr></table></td>";
			$data .= 	"<input type=\"hidden\" name=\"semesterdata[startDay]\" value=\"".$semesterdata["startDay"]."\">";
			$data .= 	"<input type=\"hidden\" name=\"semesterdata[startMonth]\" value=\"".$semesterdata["startMonth"]."\">";
			$data .= 	"<input type=\"hidden\" name=\"semesterdata[startYear]\" value=\"".$semesterdata["startYear"]."\">";
		} else {
			$data .=    "<tr><td class=\"".$cssSw->getClass()."\"><table cellspacing=0 cellpadding=0 border=0><tr><td width=\"40%\"><font size=2>"._("Anfang:")."</font></td><td class=\"".$cssSw->getClass()."\"><td width=\"\"><input type=\"text\" name=\"semesterdata[startDay]\" value=\"".$semesterdata["startDay"]."\" size=\"2\" maxlength=\"2\">";
			$data .=    ".</td>";
			//$data .=    "<td width=\"\" class=\"".$cssSw->getClass()."\"><input type=\"text\" name=\"StartMonth\" value=\"".$startMonth."\" size=\"2\" maxlength=\"2\">";
			$data .=    "<td width=\"\" class=\"".$cssSw->getClass()."\"><select name=\"semesterdata[startMonth]\" size=\"1\">";
			$data .= 	"<option ";
			$semesterdata["startMonth"] == 1 ? $data .= "selected " : $data .= "";
			$data .=	"value=\"1\">"._("Januar")."</option>";
			$data .= 	"<option ";
			$semesterdata["startMonth"] == 2 ? $data .= "selected " : $data .= "";
			$data .=	"value=\"2\">"._("Februar")."</option>";
			$data .= 	"<option ";
			$semesterdata["startMonth"] == 3 ? $data .= "selected " : $data .= "";
			$data .= 	"value=\"3\">"._("M&auml;rz")."</option>";
			$data .= 	"<option ";
			$semesterdata["startMonth"] == 4 ? $data .= "selected " : $data .= "";
			$data .=	"value=\"4\">"._("April")."</option>";
			$data .= 	"<option ";
			$semesterdata["startMonth"] == 5 ? $data .= "selected " : $data .= "";
			$data .=	"value=\"5\">"._("Mai")."</option>";
			$data .= 	"<option ";
			$semesterdata["startMonth"] == 6 ? $data .= "selected " : $data .= "";
			$data .=	"value=\"6\">"._("Juni")."</option>";
			$data .= 	"<option ";
			$semesterdata["startMonth"] == 7 ? $data .= "selected " : $data .= "";
			$data .=	"value=\"7\">"._("Juli")."</option>";
			$data .= 	"<option ";
			$semesterdata["startMonth"] == 8 ? $data .= "selected " : $data .= "";
			$data .=	"value=\"8\">"._("August")."</option>";
			$data .= 	"<option ";
			$semesterdata["startMonth"] == 9 ? $data .= "selected " : $data .= "";
			$data .= 	"value=\"9\">"._("September")."</option>";
			$data .= 	"<option "; 
			$semesterdata["startMonth"] == 10 ? $data .= "selected " : $data .= "";
			$data .= 	"value=\"10\">"._("Oktober")."</option>";
			$data .= 	"<option ";
			$semesterdata["startMonth"] == 11 ? $data .= "selected " : $data .= "";
			$data .= 	"value=\"11\">"._("November")."</option>";
			$data .= 	"<option ";
			$semesterdata["startMonth"] == 12 ? $data .= "selected " : $data .= "";
			$data .=	"value=\"12\">"._("Dezember")."</option>";
			$data .= 	"</select>";
			$data .=    "</td>";
			$data .=    "<td width=\"\" class=\"".$cssSw->getClass()."\"><input type=\"text\" name=\"semesterdata[startYear]\" value=\"".$semesterdata["startYear"]."\" size=\"4\" maxlength=\"4\">";
			$data .=    "</td></tr></table></td>";
		}
	} else {
		$data .=    "<tr><td class=\"".$cssSw->getClass()."\"><table cellspacing=0 cellpadding=0 border=0><tr><td width=\"40%\"><font size=2>"._("Anfang:")."</font></td><td class=\"".$cssSw->getClass()."\"><td width=\"\"><input type=\"text\" name=\"semesterdata[startDay]\" value=\"".$semesterdata["startDay"]."\" size=\"2\" maxlength=\"2\">";
		$data .=    ".</td>";
		//$data .=    "<td width=\"\" class=\"".$cssSw->getClass()."\"><input type=\"text\" name=\"StartMonth\" value=\"".$startMonth."\" size=\"2\" maxlength=\"2\">";
		$data .=    "<td width=\"\" class=\"".$cssSw->getClass()."\"><select name=\"semesterdata[startMonth]\" size=\"1\">";
		$data .= 	"<option ";
		$semesterdata["startMonth"] == 1 ? $data .= "selected " : $data .= "";
		$data .=	"value=\"1\">"._("Januar")."</option>";
		$data .= 	"<option ";
		$semesterdata["startMonth"] == 2 ? $data .= "selected " : $data .= "";
		$data .=	"value=\"2\">"._("Februar")."</option>";
		$data .= 	"<option ";
		$semesterdata["startMonth"] == 3 ? $data .= "selected " : $data .= "";
		$data .= 	"value=\"3\">"._("M&auml;rz")."</option>";
		$data .= 	"<option ";
		$semesterdata["startMonth"] == 4 ? $data .= "selected " : $data .= "";
		$data .=	"value=\"4\">"._("April")."</option>";
		$data .= 	"<option ";
		$semesterdata["startMonth"] == 5 ? $data .= "selected " : $data .= "";
		$data .=	"value=\"5\">"._("Mai")."</option>";
		$data .= 	"<option ";
		$semesterdata["startMonth"] == 6 ? $data .= "selected " : $data .= "";
		$data .=	"value=\"6\">"._("Juni")."</option>";
		$data .= 	"<option ";
		$semesterdata["startMonth"] == 7 ? $data .= "selected " : $data .= "";
		$data .=	"value=\"7\">"._("Juli")."</option>";
		$data .= 	"<option ";
		$semesterdata["startMonth"] == 8 ? $data .= "selected " : $data .= "";
		$data .=	"value=\"8\">"._("August")."</option>";
		$data .= 	"<option ";
		$semesterdata["startMonth"] == 9 ? $data .= "selected " : $data .= "";
		$data .= 	"value=\"9\">"._("September")."</option>";
		$data .= 	"<option "; 
		$semesterdata["startMonth"] == 10 ? $data .= "selected " : $data .= "";
		$data .= 	"value=\"10\">"._("Oktober")."</option>";
		$data .= 	"<option ";
		$semesterdata["startMonth"] == 11 ? $data .= "selected " : $data .= "";
		$data .= 	"value=\"11\">"._("November")."</option>";
		$data .= 	"<option ";
		$semesterdata["startMonth"] == 12 ? $data .= "selected " : $data .= "";
		$data .=	"value=\"12\">"._("Dezember")."</option>";
		$data .= 	"</select>";

		$data .=    "</td>";
		$data .=    "<td width=\"\" class=\"".$cssSw->getClass()."\"><input type=\"text\" name=\"semesterdata[startYear]\" value=\"".$semesterdata["startYear"]."\" size=\"4\" maxlength=\"4\">";
		$data .=    "</td></tr></table></td>";
	}
	$data .=    "<td class=\"".$cssSw->getClass()."\"><table cellspacing=0 cellpadding=0 border=0><tr><td width=\"40%\"><font size=2>"._("Ende:")."</font></td><td class=\"".$cssSw->getClass()."\"><td width=\"\"><input type=\"text\" name=\"semesterdata[expireDay]\" value=\"".$semesterdata["expireDay"]."\" size=\"2\" maxlength=\"2\">";
	$data .=    ".</td>";

	//$data .=    "<td width=\"\" class=\"".$cssSw->getClass()."\"><input type=\"text\" name=\"ExpireMonth\" value=\"".$expireMonth."\" size=\"2\" maxlength=\"2\">";
	$data .=    "<td width=\"\" class=\"".$cssSw->getClass()."\"><select name=\"semesterdata[expireMonth]\" size=\"1\">";
	$data .= 	"<option ";
	$semesterdata["expireMonth"] == 1 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"1\">"._("Januar")."</option>";
	$data .= 	"<option ";
	$semesterdata["expireMonth"] == 2 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"2\">"._("Februar")."</option>";
	$data .= 	"<option ";
	$semesterdata["expireMonth"] == 3 ? $data .= "selected " : $data .= "";
	$data .= 	"value=\"3\">"._("M&auml;rz")."</option>";
	$data .= 	"<option ";
	$semesterdata["expireMonth"] == 4 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"4\">"._("April")."</option>";
	$data .= 	"<option ";
	$semesterdata["expireMonth"] == 5 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"5\">"._("Mai")."</option>";
	$data .= 	"<option ";
	$semesterdata["expireMonth"] == 6 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"6\">"._("Juni")."</option>";
	$data .= 	"<option ";
	$semesterdata["expireMonth"] == 7 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"7\">"._("Juli")."</option>";
	$data .= 	"<option ";
	$semesterdata["expireMonth"] == 8 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"8\">"._("August")."</option>";
	$data .= 	"<option ";
	$semesterdata["expireMonth"] == 9 ? $data .= "selected " : $data .= "";
	$data .= 	"value=\"9\">"._("September")."</option>";
	$data .= 	"<option "; 
	$semesterdata["expireMonth"] == 10 ? $data .= "selected " : $data .= "";
	$data .= 	"value=\"10\">"._("Oktober")."</option>";
	$data .= 	"<option ";
	$semesterdata["expireMonth"] == 11 ? $data .= "selected " : $data .= "";
	$data .= 	"value=\"11\">"._("November")."</option>";
	$data .= 	"<option ";
	$semesterdata["expireMonth"] == 12 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"12\">"._("Dezember")."</option>";
	$data .= 	"</select>";

	$data .=    "</td>";
	$data .=    "<td width=\"\" class=\"".$cssSw->getClass()."\"><input type=\"text\" name=\"semesterdata[expireYear]\" value=\"".$semesterdata["expireYear"]."\" size=\"4\" maxlength=\"4\">";
	$data .=    "</td></tr></table></td></tr>";
	$cssSw->switchClass();
	$data .= "<tr><td height=50 colspan=2 class=\"".$cssSw->getClass()."\"><font size=2><b>Bitte geben Sie den Vorlesungszeitraum ein</b></font></td></tr>";
	$cssSw->switchClass();
	$data .=    "<tr><td class=\"".$cssSw->getClass()."\"><table cellspacing=0 cellpadding=0 border=0><tr><td width=\"40%\"><font size=2>"._("Anfang:")."</font></td><td class=\"".$cssSw->getClass()."\"><input type=\"text\" name=\"semesterdata[lectureStartDay]\" value=\"".$semesterdata["lectureStartDay"]."\" size=\"2\" maxlength=\"2\">";
	$data .=   	".</td>";

	$data .=    "<td width=\"\" class=\"".$cssSw->getClass()."\"><select name=\"semesterdata[lectureStartMonth]\" size=\"1\">";
	$data .= 	"<option ";
	$semesterdata["lectureStartMonth"] == 1 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"1\">"._("Januar")."</option>";
	$data .= 	"<option ";
	$semesterdata["lectureStartMonth"] == 2 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"2\">"._("Februar")."</option>";
	$data .= 	"<option ";
	$semesterdata["lectureStartMonth"] == 3 ? $data .= "selected " : $data .= "";
	$data .= 	"value=\"3\">"._("M&auml;rz")."</option>";
	$data .= 	"<option ";
	$semesterdata["lectureStartMonth"] == 4 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"4\">"._("April")."</option>";
	$data .= 	"<option ";
	$semesterdata["lectureStartMonth"] == 5 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"5\">"._("Mai")."</option>";
	$data .= 	"<option ";
	$semesterdata["lectureStartMonth"] == 6 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"6\">"._("Juni")."</option>";
	$data .= 	"<option ";
	$semesterdata["lectureStartMonth"] == 7 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"7\">"._("Juli")."</option>";
	$data .= 	"<option ";
	$semesterdata["lectureStartMonth"] == 8 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"8\">"._("August")."</option>";
	$data .= 	"<option ";
	$semesterdata["lectureStartMonth"] == 9 ? $data .= "selected " : $data .= "";
	$data .= 	"value=\"9\">"._("September")."</option>";
	$data .= 	"<option "; 
	$semesterdata["lectureStartMonth"] == 10 ? $data .= "selected " : $data .= "";
	$data .= 	"value=\"10\">"._("Oktober")."</option>";
	$data .= 	"<option ";
	$semesterdata["lectureStartMonth"] == 11 ? $data .= "selected " : $data .= "";
	$data .= 	"value=\"11\">"._("November")."</option>";
	$data .= 	"<option ";
	$semesterdata["lectureStartMonth"] == 12 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"12\">"._("Dezember")."</option>";
	$data .= 	"</select>";

	$data .=    "</td>";


	$data .=    "<td width=\"\" class=\"".$cssSw->getClass()."\"><input type=\"text\" name=\"semesterdata[lectureStartYear]\" value=\"".$semesterdata["lectureStartYear"]."\" size=\"4\" maxlength=\"4\">";
	$data .=    "</td></tr></table></td>";
	$data .=    "<td class=\"".$cssSw->getClass()."\"><table cellspacing=0 cellpadding=0 border=0><tr><td width=\"40%\"><font size=2>"._("Ende:")."</font></td><td class=\"".$cssSw->getClass()."\"><input type=\"text\" name=\"semesterdata[lectureExpireDay]\" value=\"".$semesterdata["lectureExpireDay"]."\" size=\"2\" maxlength=\"2\">";
	$data .=    ".</td>";


	//$data .=    "<td width=\"\" class=\"".$cssSw->getClass()."\"><input type=\"text\" name=\"LectureExpireMonth\" value=\"".$lectureExpireMonth."\" size=\"2\" maxlength=\"2\">";

	$data .=    "<td width=\"\" class=\"".$cssSw->getClass()."\"><select name=\"semesterdata[lectureExpireMonth]\" size=\"1\">";
	$data .= 	"<option ";
	$semesterdata["lectureExpireMonth"] == 1 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"1\">"._("Januar")."</option>";
	$data .= 	"<option ";
	$semesterdata["lectureExpireMonth"] == 2 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"2\">"._("Februar")."</option>";
	$data .= 	"<option ";
	$semesterdata["lectureExpireMonth"] == 3 ? $data .= "selected " : $data .= "";
	$data .= 	"value=\"3\">"._("M&auml;rz")."</option>";
	$data .= 	"<option ";
	$semesterdata["lectureExpireMonth"] == 4 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"4\">"._("April")."</option>";
	$data .= 	"<option ";
	$semesterdata["lectureExpireMonth"] == 5 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"5\">"._("Mai")."</option>";
	$data .= 	"<option ";
	$semesterdata["lectureExpireMonth"] == 6 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"6\">"._("Juni")."</option>";
	$data .= 	"<option ";
	$semesterdata["lectureExpireMonth"] == 7 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"7\">"._("Juli")."</option>";
	$data .= 	"<option ";
	$semesterdata["lectureExpireMonth"] == 8 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"8\">"._("August")."</option>";
	$data .= 	"<option ";
	$semesterdata["lectureExpireMonth"] == 9 ? $data .= "selected " : $data .= "";
	$data .= 	"value=\"9\">"._("September")."</option>";
	$data .= 	"<option "; 
	$semesterdata["lectureExpireMonth"] == 10 ? $data .= "selected " : $data .= "";
	$data .= 	"value=\"10\">"._("Oktober")."</option>";
	$data .= 	"<option ";
	$semesterdata["lectureExpireMonth"] == 11 ? $data .= "selected " : $data .= "";
	$data .= 	"value=\"11\">"._("November")."</option>";
	$data .= 	"<option ";
	$semesterdata["lectureExpireMonth"] == 12 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"12\">"._("Dezember")."</option>";
	$data .= 	"</select>";
	$data .=    "</td>";



	$data .=    "<td width=\"\" class=\"".$cssSw->getClass()."\"><input type=\"text\" name=\"semesterdata[lectureExpireYear]\" value=\"".$semesterdata["lectureExpireYear"]."\" size=\"4\" maxlength=\"4\">";
	$data .=    "</td></tr></table></td></tr>";
	$cssSw->switchClass();
	$data .=    "<tr><td class=\"".$cssSw->getClass()."\">";
	$data .=    "</td><td class=\"".$cssSw->getClass()."\"><br><br>";
	if ($modus=="change") {
		$data.= "<input type=\"IMAGE\" name=\"create\" value=\"Bearbeiten\" ".makeButton("uebernehmen", "src").">&nbsp;&nbsp;";
		$data.=	"<input type=\"hidden\" name=\"create\" value=\"Bearbeiten\">";
	} else {    
		$data .=    "<input type=\"hidden\" name=\"newEntry\" value=\"1\">";
		$data .=    "<input type=\"IMAGE\" name=\"create\" value=\"Anlegen\" ".makeButton("anlegen", "src").">&nbsp;&nbsp;";
		$data .=	"<input type=\"hidden\" name=\"create\" value=\"Anlegen\">";
	}
	$data .=    "<input type=\"hidden\" name=\"semesterdata[semester_id]\" value=\"".$semesterdata["semester_id"]."\">";
	$data .=    "<a href=\"admin_semester.php\"><img ".makeButton("abbrechen", "src")." border=0></a>";
	//$data .=    "<input type=\"IMAGE\" name=\"cancel\" value=\""._("abbrechen")."\"".makeButton("abbrechen", "src").">";
	$data .=    "</td></tr>";
	$data .=    "</form>";
	return $data;

}

function semester_count_continuos_seminars() {
	$db = new DB_Seminar;
	$semester = new SemesterData;
	$continuos_seminars_in_sem = array();
	$allSemesters = $semester->getAllSemesterData();
	for ($i=0;$i<count($allSemesters);$i++) {
		$continuos_seminars_in_sem[$allSemesters[$i]["semester_id"]]=0;	
	}
	$sql = 	"SELECT start_time FROM seminare WHERE ".
			"duration_time = -1";
	if (!$db->query($sql)) {
		echo "Error! ";
		return 0;
	}
	while ($db->next_record()) {
		for ($i=0;$i<count($allSemesters);$i++) {
			if (($db->f("start_time")>=$allSemesters[$i]["beginn"]) && ($db->f("start_time")<$allSemesters[$i]["ende"])) {
				for ($j=$i;$j<count($allSemesters);$j++) {
					$continuos_seminars_in_sem[$allSemesters[$j]["semester_id"]]++;
				}
			}	
		}	
	}
	return $continuos_seminars_in_sem;
}

function semester_count_duration_seminars() {
	$db = new DB_Seminar;
	$semester = new SemesterData;
	$duration_seminars_in_sem = array();
	$allSemesters = $semester->getAllSemesterData();
	for ($i=0;$i<count($allSemesters);$i++) {
		$duration_seminars_in_sem[$allSemesters[$i]["semester_id"]]=0;	
	}
	$sql = 	"SELECT start_time, duration_time FROM seminare WHERE ".
			"duration_time!=0 AND duration_time!=-1";
	if (!$db->query($sql)) {
		echo "Error! ";
		return 0;
	}
	while ($db->next_record()) {
		$endtime = $db->f("start_time") + $db->f("duration_time");
		for ($i=0;$i<count($allSemesters);$i++) {
			if ((($db->f("start_time")>=$allSemesters[$i]["beginn"])) && ($db->f("start_time")<$allSemesters[$i]["ende"])) {
				for ($j=$i;$j<count($allSemesters);$j++) {
					if ($endtime>=$allSemesters[$j]["beginn"]) {
						$duration_seminars_in_sem[$allSemesters[$j]["semester_id"]]++;
					} else {
						;
					}
				}
			}
		}	
	}
	return $duration_seminars_in_sem;
}

function semester_count_absolut_seminars_in_semester($semester_id) {
	$db = new DB_Seminar;
	$semester = new SemesterData;
	$semesterdata = $semester->getSemesterData($semester_id);
	$sql = 	"SELECT COUNT(*) as count FROM seminare WHERE ".
			"start_time >= ".$semesterdata["beginn"]." AND start_time <= ".$semesterdata["ende"]." ".
			"AND duration_time=0";
	if (!$db->query($sql)) {
		echo "Error! Db-Query in semester_count";
		return -1;
	}
	$db->next_record();
	return $db->f("count");
}

function semester_show_semester_header(){
    $data =     "<tr><td class=\"blank\" colspan=2>";
    $data .=    "<table align=center bg=\"#ffffff\" width=\"100%\" border=0 cellpadding=2 cellspacing=0>";
    $data .=    "<tr valign=top align=middle>";
    $data .=    "<th align=left width=\"15%\"><font size=2>"._("Name")."</font></th>";
    $data .=    "<th align=left width=\"12%\"><font size=2>"._("Beginn")."</font></th>";
    $data .=    "<th align=left width=\"12%\"><font size=2>"._("Ende")."</font></th>";
    $data .=    "<th align=left width=\"12%\"><font size=2>"._("Vorlesungsbeginn")."</font></th>";
    $data .=    "<th align=left width=\"12%\"><font size=2>"._("Vorlesungsende")."</font></th>";
	$data .=	"<th align=left width=\"12%\"><font size=2>"._("Anzahl Veranstaltungen")."</font></th>";
	$data .= 	"<th align=left width=\"20%\"></th>";
	$data .= 	"<th align=left width=\"20%\"></th>";
    $data .=    "</tr></table></td></tr>";
    return $data;
}

//list all Semesters
function semester_show_semesters($all_semesters) {
	$duration_seminar_semester = semester_count_duration_seminars();
	$continuos_seminar_semester = semester_count_continuos_seminars();
    $showSemesters = "<table align=center bg=\"#ffffff\" width=\"100%\" border=0 cellpadding=2 cellspacing=0><tr><td class=\"blank\" colspan=8><br></td></tr>";
    $count = count($all_semesters);
    for ($i=0; $i<$count; $i++) {
		$absolut_seminars = semester_count_absolut_seminars_in_semester($all_semesters[$i]["semester_id"]);
        $showSemesters .= semester_show_semester($all_semesters[$i], $i, $duration_seminar_semester, $absolut_seminars, $continuos_seminar_semester);
    }
    $showSemester.= "</table><br><br></td></tr>";
    return $showSemesters;
}

//list one Semester
function semester_show_semester($semester, $i, $duration_seminar_semester, $absolut_seminars, $continuos_seminar_semester) {
   if (($i % 2) == 0) {
        $style = "steel1";
   } else {
        $style = "steelgraulight";
   }
   $row =   "<tr>";
   $row .=  "<td class=".$style." width=\"15%\"><font size=1>".$semester[name]."</font></td>";
   $row .=  "<td class=".$style." width=\"12%\"><font size=1>".date("d.m.Y", $semester["beginn"])."</font></td>";
   $row .=  "<td class=".$style." width=\"12%\"><font size=1>".date("d.m.Y", $semester["ende"])."</font></td>";
   $row .=  "<td class=".$style." width=\"12%\"><font size=1>".date("d.m.Y", $semester["vorles_beginn"])."</font></td>";
   $row .=  "<td class=".$style." width=\"12%\"><font size=1>".date("d.m.Y", $semester["vorles_ende"])."</font></td>";
   $row .=	"<td class=".$style." width=\"12%\"><font size=1>";
   $row .=	$duration_seminar_semester[$semester["semester_id"]]+$absolut_seminars."&nbsp;(+&nbsp;".$continuos_seminar_semester[$semester["semester_id"]]."&nbsp;"._("implizit").")</font></td>";
   //$row .= 	"<br>".$continuos_seminar_semester[$semester["semester_id"]]."&nbsp;"._("kontinuierlich");
   //$row .=	"<br>".$absolut_seminars."&nbsp;"._("1-Semester")."</font></td>";
   $row .=  "<td width=\"15%\" align=\"RIGHT\" class=".$style."><a href=\"admin_semester.php?change=1&semester_id=".$semester[semester_id]."\">".makeButton("bearbeiten")."</a></td>";
   $row .= 	"<td width=\"15%\" align=\"RIGHT\" class=".$style."><a href=\"admin_semester.php?delete=1&semester_id=".$semester["semester_id"]."\">".makeButton("loeschen"). "</a></td>";
   $row .=  "</tr>";
   return $row;
}

function semester_delete($semester_id) {
	$semester = new SemesterData;
		if ($semester->deleteSemester($semester_id))
			return 1;
		return 0;
}

function semester_confirm_delete($semester_id, $link) {
	$semester = new SemesterData;
	$semesterdata = $semester->getSemesterData($semester_id);
	$duration_seminar_semester = semester_count_duration_seminars();
	$absolut_seminars = semester_count_absolut_seminars_in_semester($semester_id);
	if ($absolut_seminars || $duration_seminar_semester[$semester_id]) {
		$msg = "error§"._("Semester, in denen Veranstaltungen liegen, k&ouml;nnen nicht gel&ouml;scht werden!");
		$data = parse_msg($msg);
		$data .= semester_show_overview($link);
		return $data;
	} else {
		$data = 	"<td class=\"blank\"><font size=\"2\">";
		$data .=	parse_msg("info§", "§", "blank", 1, FALSE )._("Wollen Sie wirklich dieses Semester l&ouml;schen?");
		$data .=	"</font><br><br><a href=\"".$link."?delete=1&confirm=TRUE&semester_id=".$semester_id."\">".makeButton("ja2","img")."</a>&nbsp;&nbsp;<a href=\"".$link."\">".makeButton("nein","img")."</a><br><br>";
		$data .= 	"</td>";
		$data .= semester_show_overview($link);
		return $data;
	}
}

function holiday_show_holiday_header(){
    $data =    	"<tr><td class=\"blank\" colspan=8>";
    $data .=    "<table align=center bg=\"#ffffff\" width=\"100%\" border=0 cellpadding=2 cellspacing=0>";
    $data .=    "<tr valign=top align=middle>";
    $data .=    "<th align=left width=\"35%\"><font size=2>"._("Name")."</font></th>";
    $data .=    "<th align=left width=\"20%\"><font size=2>"._("Beginn")."</font></th>";
    $data .=    "<th align=left width=\"20%\"><font size=2>"._("Ende")."</font></th>";
    $data .=    "<th align=left width=\"15%\"><font size=2></font></th>";
    $data .=    "<th align=left width=\"15%\"><font size=2></font></th>";
	$data .=	"</tr></table></td></tr>";
	return $data;
}


function holiday_make_new_holiday_button($link) {
	$button = 	"<tr><td class=\"blank\" colspan=8><br><br></td></tr>";
    $button .= "<tr><td class=\"blank\" colspan=8><font size=2><b><a href=\"".$link."?newHoliday=1\">&nbsp;"._("Neue Ferien anlegen")."</a><b></font><br><br></td></tr>";
    return $button;
}

function holiday_show_holiday($holidaydata, $i) {
   if (($i % 2) == 0) {
        $style = "steel1";
   } else {
        $style = "steelgraulight";
   }
   $row =   "<tr>";
   $row .=  "<td class=".$style." width=\"35%\"><font size=1>".$holidaydata["name"]."</font></td>";
   $row .=  "<td class=".$style." width=\"20%\"><font size=1>".date("d.m.Y", $holidaydata["beginn"])."</font></td>";
   $row .=  "<td class=".$style." width=\"20%\"><font size=1>".date("d.m.Y", $holidaydata["ende"])."</font></td>";
   $row .=  "<td width=\"15%\" align=\"RIGHT\" class=".$style."><a href=\"admin_semester.php?holidayChange=1&holiday_id=".$holidaydata["holiday_id"]."\">".makeButton("bearbeiten")."</a></td>";
   $row .= 	"<td width=\"15%\" align=\"RIGHT\" class=".$style."><a href=\"admin_semester.php?delete=1&holiday_id=".$holidaydata["holiday_id"]."\">".makeButton("loeschen")."</a></td>";
   $row .=  "</tr>";
   return $row;
}


function holiday_show_holidays($allHolidays) {
    $showHolidays = "<table align=center bg=\"#ffffff\" width=\"100%\" border=0 cellpadding=2 cellspacing=0><tr><td class=\"blank\" colspan=8><br></td></tr>";
		for ($i=0;$i<count($allHolidays);$i++) {
			$showHolidays .=	holiday_show_holiday($allHolidays[$i], $i);
		}
    $showHolidays .= "</table><br><br>";
	return $showHolidays;
	}


	
function holiday_show_new_holiday_form($link, $cssSw, $holidaydata, $modus="") { 

	$data =     "<form method=\"POST\" name=\"newHoliday\" action=\"".$link."\">";
	$data .=    "<tr><td class=\"";
	$cssSw->switchClass(); 
	$data .=    "".$cssSw->getClass()."\"><font size=2><b>"._("Name der Ferien:")."</b></font></td><td class=".$cssSw->getClass()."><input type=\"text\" name=\"holidaydata[name]\" value=\"".$holidaydata["name"]."\"size=60 maxlength=254></td></tr>";
	$data .=    "<tr><td class=\"".$cssSw->getClass()."\"><font size=2><b>"._("Beschreibung:")."</b></font></td><td class=\"".$cssSw->getClass()."\"><textarea cols=50 ROWS=4 name=\"holidaydata[description]\">".$holidaydata["description"]."</textarea></td></tr>";
	$cssSw->switchClass();
	$data .= "<tr><td height=50 colspan=2 class=\"".$cssSw->getClass()."\"><font size=2><b>Bitte geben Sie den zeitlichen Rahmen der Ferien ein</b></font></td></tr>";
	$cssSw->switchClass(); 
	$data .=    "<tr><td class=\"".$cssSw->getClass()."\"><table cellspacing=0 cellpadding=0 border=0><tr><td width=\"40%\"><font size=2>"._("Anfang:")."</font></td><td class=\"".$cssSw->getClass()."\"><td width=\"\"><input type=\"text\" name=\"holidaydata[startDay]\" value=\"".$holidaydata["startDay"]."\" size=\"2\" maxlength=\"2\">";
	$data .=    ".</td>";
	$data .=    "<td width=\"\" class=\"".$cssSw->getClass()."\"><select name=\"holidaydata[startMonth]\" size=\"1\">";
	$data .= 	"<option ";
	$holidaydata["startMonth"] == 1 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"1\">"._("Januar")."</option>";
	$data .= 	"<option ";
	$holidaydata["startMonth"] == 2 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"2\">"._("Februar")."</option>";
	$data .= 	"<option ";
	$holidaydata["startMonth"] == 3 ? $data .= "selected " : $data .= "";
	$data .= 	"value=\"3\">"._("M&auml;rz")."</option>";
	$data .= 	"<option ";
	$holidaydata["startMonth"] == 4 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"4\">"._("April")."</option>";
	$data .= 	"<option ";
	$holidaydata["startMonth"] == 5 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"5\">"._("Mai")."</option>";
	$data .= 	"<option ";
	$holidaydata["startMonth"] == 6 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"6\">"._("Juni")."</option>";
	$data .= 	"<option ";
	$holidaydata["startMonth"] == 7 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"7\">"._("Juli")."</option>";
	$data .= 	"<option ";
	$holidaydata["startMonth"] == 8 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"8\">"._("August")."</option>";
	$data .= 	"<option ";
	$holidaydata["startMonth"] == 9 ? $data .= "selected " : $data .= "";
	$data .= 	"value=\"9\">"._("September")."</option>";
	$data .= 	"<option "; 
	$holidaydata["startMonth"] == 10 ? $data .= "selected " : $data .= "";
	$data .= 	"value=\"10\">"._("Oktober")."</option>";
	$data .= 	"<option ";
	$holidaydata["startMonth"] == 11 ? $data .= "selected " : $data .= "";
	$data .= 	"value=\"11\">"._("November")."</option>";
	$data .= 	"<option ";
	$holidaydata["startMonth"] == 12 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"12\">"._("Dezember")."</option>";
	$data .= 	"</select>";

	$data .=    "</td>";
	$data .=    "<td width=\"\" class=\"".$cssSw->getClass()."\"><input type=\"text\" name=\"holidaydata[startYear]\" value=\"".$holidaydata["startYear"]."\" size=\"4\" maxlength=\"4\">";
	$data .=    "</td></tr></table></td>";
	$data .=    "<td class=\"".$cssSw->getClass()."\"><table cellspacing=0 cellpadding=0 border=0><tr><td width=\"40%\"><font size=2>"._("Ende:")."</font></td><td class=\"".$cssSw->getClass()."\"><td width=\"\"><input type=\"text\" name=\"holidaydata[expireDay]\" value=\"".$holidaydata["expireDay"]."\" size=\"2\" maxlength=\"2\">";
	$data .=    ".</td>";

	$data .=    "<td width=\"\" class=\"".$cssSw->getClass()."\"><select name=\"holidaydata[expireMonth]\" size=\"1\">";
	$data .= 	"<option ";
	$holidaydata["expireMonth"] == 1 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"1\">"._("Januar")."</option>";
	$data .= 	"<option ";
	$holidaydata["expireMonth"] == 2 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"2\">"._("Februar")."</option>";
	$data .= 	"<option ";
	$holidaydata["expireMonth"] == 3 ? $data .= "selected " : $data .= "";
	$data .= 	"value=\"3\">"._("M&auml;rz")."</option>";
	$data .= 	"<option ";
	$holidaydata["expireMonth"] == 4 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"4\">"._("April")."</option>";
	$data .= 	"<option ";
	$holidaydata["expireMonth"] == 5 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"5\">"._("Mai")."</option>";
	$data .= 	"<option ";
	$holidaydata["expireMonth"] == 6 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"6\">"._("Juni")."</option>";
	$data .= 	"<option ";
	$holidaydata["expireMonth"] == 7 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"7\">"._("Juli")."</option>";
	$data .= 	"<option ";
	$holidaydata["expireMonth"] == 8 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"8\">"._("August")."</option>";
	$data .= 	"<option ";
	$holidaydata["expireMonth"] == 9 ? $data .= "selected " : $data .= "";
	$data .= 	"value=\"9\">"._("September")."</option>";
	$data .= 	"<option "; 
	$holidaydata["expireMonth"] == 10 ? $data .= "selected " : $data .= "";
	$data .= 	"value=\"10\">"._("Oktober")."</option>";
	$data .= 	"<option ";
	$holidaydata["expireMonth"] == 11 ? $data .= "selected " : $data .= "";
	$data .= 	"value=\"11\">"._("November")."</option>";
	$data .= 	"<option ";
	$holidaydata["expireMonth"] == 12 ? $data .= "selected " : $data .= "";
	$data .=	"value=\"12\">"._("Dezember")."</option>";
	$data .= 	"</select>";

	$data .=    "</td>";
	$data .=    "<td width=\"\" class=\"".$cssSw->getClass()."\"><input type=\"text\" name=\"holidaydata[expireYear]\" value=\"".$holidaydata["expireYear"]."\" size=\"4\" maxlength=\"4\">";
	$data .=    "</td></tr></table></td></tr>";
	$cssSw->switchClass();
	$data .=    "<tr><td class=\"".$cssSw->getClass()."\">";
	$data .=    "</td><td class=\"".$cssSw->getClass()."\"><br><br>";
	if ($modus=="change") {
		$data.= "<input type=\"IMAGE\" name=\"create\" value=\"Ferienbearbeiten\" ".makeButton("uebernehmen", "src").">&nbsp;&nbsp;";
		$data.= "<input type=\"hidden\" name=\"create\" value=\"Ferienbearbeiten\">";
	} else {    
		$data .=    "<input type=\"hidden\" name=\"newEntry\" value=\"1\">";
		$data .=    "<input type=\"IMAGE\" name=\"create\" value=\"Ferienanlegen\" ".makeButton("anlegen", "src").">&nbsp;&nbsp;";
		$data.= "<input type=\"hidden\" name=\"create\" value=\"Ferienanlegen\">";
	}
	$data .=    "<input type=\"hidden\" name=\"holidaydata[holiday_id]\" value=\"".$holidaydata["holiday_id"]."\">";
	$data .=    "<a href=\"admin_semester.php\"><img ".makeButton("abbrechen", "src")." border=0></a>";
	//$data .=    "<input type=\"IMAGE\" name=\"cancel\" value=\""._("abbrechen")."\"".makeButton("abbrechen", "src").">";
	$data .=    "</td></tr>";
	$data .=    "</form>";
	return $data;

}
	
function holiday_check_form_field($holidaydata) { // check insert_form
    $errorcount = 0;
    if (strlen($holidaydata["name"])==0) {
        $error[$errorcount] .= _("Name");
        $errorcount++;
    }
    if (!(is_numeric($holidaydata["startDay"]) && is_numeric($holidaydata["startMonth"]) && is_numeric($holidaydata["startYear"]) && checkdate($holidaydata["startMonth"], $holidaydata["startDay"], $holidaydata["startYear"]))) {
        $error[$errorcount] .= _("Startdatum");
        $errorcount++;
    }
    if (!(is_numeric($holidaydata["expireDay"]) && is_numeric($holidaydata["expireMonth"]) && is_numeric($holidaydata["expireYear"]) && checkdate($holidaydata["expireMonth"], $holidaydata["expireDay"], $holidaydata["expireYear"]))) {
        $error[$errorcount] .= _("Enddatum");
        $errorcount++;
    }
   
    if ($errorcount) {
        $data = _("Fehler! Folgende Felder sind ungültig:&nbsp;");
        for ($i=0; $i<count($error); $i++) {
            $data .= "$error[$i]";
            if ($i!=(count($error)-1)) {
                $data .= ",&nbsp;";
            } else {
                $data .= "&nbsp;";
            }
        }
        $data .= "!";
        return $data;
    }
    //now compare dates
    if ((mktime(0,0,0,$holidaydata["expireMonth"],$holidaydata["expireDay"],$holidaydata["expireYear"])-mktime(0,0,0,$holidaydata["startMonth"],$holidaydata["startDay"],$holidaydata["startYear"]))<0) {
        return _("Das Datum des Ferienendes muss größer sein als das Datum des Ferienanfangs");
    }
    return 1;
}

function holiday_make_timestamp_data_to_single_data($holidaydata) { 
	$holidaydata["startDay"] = date("d",$holidaydata["beginn"]);	
	$holidaydata["startMonth"] = date("n",$holidaydata["beginn"]);
	$holidaydata["startYear"] = date("Y",$holidaydata["beginn"]);
	$holidaydata["expireDay"] = date("d",$holidaydata["ende"]);
	$holidaydata["expireMonth"] = date("n",$holidaydata["ende"]);
	$holidaydata["expireYear"] = date("Y",$holidaydata["ende"]);
	return $holidaydata;
}

function holiday_make_single_data_to_timestamp($holidaydata) { // Zeitfelder zusammenführen
	$holidaydata["beginn"] = mktime(0,0,0,$holidaydata["startMonth"],$holidaydata["startDay"],$holidaydata["startYear"]);
	$holidaydata["ende"] = mktime(23,59,59,$holidaydata["expireMonth"],$holidaydata["expireDay"],$holidaydata["expireYear"]);
	return $holidaydata;
}


function holiday_confirm_delete($holiday_id, $link) {	// bestätige Löschen der Ferien
	$data = 	parse_msg("info§", "§", blank, 1, FALSE);
	$data .= 	"<td class=\"blank\"><font size=\"2\">";
	$data .=	_("Wollen Sie wirklich diese Ferien l&ouml;schen?");
	$data .=	"</font><br><br><a href=\"".$link."?delete=1&confirm=TRUE&holiday_id=".$holiday_id."\">".makeButton("ja2","img")."</a>&nbsp;&nbsp;<a href=\"".$link."\">".makeButton("nein","img")."</a><br><br>";
	$data .= 	"</td>";
	$data .= 	semester_show_overview($link);
	return $data;

}

function holiday_delete($holiday_id) {	// loesche Ferien
	$holiday = new HolidayData;
	if ($holiday->deleteHoliday($holiday_id)) {
		return 1;
	}
	return 0;
}

function semester_show_overview($link) {
	// Diese Funktion zeigt die eigentliche Übersicht an
	$semester = new SemesterData();
	$holiday = new HolidayData();
	$newSemesterButton = semester_make_new_semester_button($link);
	$allSemesters =	$semester->getAllSemesterData();
	$header = semester_show_semester_header();
	if ($allSemesters==0) {
		$content = "error§"._("Kein Semester vorhanden!");	
		$content = parse_msg($content);
	} else {
		$content = semester_show_semesters($allSemesters);
	}
	$holidayHeader = holiday_show_holiday_header();
	$holidayButton = holiday_make_new_holiday_button($link);
	$allHolidays = $holiday->getAllHolidays();
	if ($allHolidays==0) {
		$holidayContent = "error§"._("Keine Ferien vorhanden");
		$holidayContent = parse_msg($holidayContent);
	} else {
		$holidayContent = holiday_show_holidays($allHolidays);
	}
	$overview = $newSemesterButton.$header.$content.$holidayButton.$holidayHeader.$holidayContent;
	return $overview;
}

?>
