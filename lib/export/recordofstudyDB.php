<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO

/**

 * Creates a record of study and exports the data to pdf (database)

 *

 * @author      Christian Bauer <alfredhitchcock@gmx.net>

 * @version     $Exp

 * @copyright   2003 Stud.IP-Project

 * @access      public

 * @module      recordofstudy

 */
require_once('lib/dates.inc.php');
require_once('config.inc.php');
require_once('lib/classes/SemesterData.class.php');

/**
 * collect the current seminars and concerning semesters from the archiv	
 *
 * @access  private
 * @returns array the semesters
 *
 */
function getSemesters(){
	global $user;
	$ret = $sorter = $semester_in_db = array();
	// creating the list of avaible semester
	foreach (SemesterData::GetSemesterArray() as $key => $value){
		$semestersAR[$key]["beginn"] = $value["beginn"];
		$semestersAR[$key]["id"] = $key;
		$semestersAR[$key]["idname"] = $value["name"];
		$semestersAR[$key]["name"] = convertSemester($value["name"]);
		$semester_in_db[] = $value["name"];
		$sorter[$key] = $value["beginn"];
	}
	unset($semestersAR[0]);
	unset($sorter[0]);
	unset($semester_in_db[0]);
	$i = $key + 1;
	// adding the semester from avaible archiv-items
	$db = &new DB_Seminar ();
	$db->query ("SELECT archiv.start_time, archiv.semester, archiv.start_time "
		. "FROM archiv_user LEFT "
		. "JOIN archiv  USING (seminar_id) "
		. "WHERE archiv_user.user_id = '".$user->id."' "
		. "GROUP BY archiv.semester ORDER BY start_time DESC");
	while ($db->next_record()) {
		if (in_array($db->f("semester"), $semester_in_db)){
			continue;
		}
		$semestersAR[$i]["beginn"] = $db->f("start_time");
		$semestersAR[$i]["id"] = $i;
		$semestersAR[$i]["idname"] = $db->f("semester");
		$semestersAR[$i]["name"] = convertSemester($db->f("semester"));
		$semestersAR[$i]["onlyarchiv"] = 1;
		$sorter[$i] = $db->f("start_time");
		$i++;
	}
	asort($sorter);
	foreach($sorter as $key => $value){
		$ret[$key] = $semestersAR[$key];
	}
	return $ret;
}

/**
 * collects the basic data from the db
 *
 * @access  private
 * @returns array 	the basic data
 *
 */
function getBasicData(){
global $user;
	$db = &new DB_Seminar ();

	// get field of study
	$db->query("SELECT user_studiengang.*,studiengaenge.name "
		. "FROM user_studiengang LEFT JOIN studiengaenge USING (studiengang_id) "
		. "WHERE user_id = '".$user->id."' "
		. "ORDER BY studiengang_id");

	while ($db->next_record()) {
		$fieldofstudy .= $db->f("name")." ";
	}

	//get fullname
	$db->query("SELECT user_info.title_front as tv"
		.", user_info.title_rear as tr"
		.", auth_user_md5.Vorname vn"
		.", auth_user_md5.Nachname as nn"
		." FROM auth_user_md5 LEFT JOIN user_info USING (user_id)"
		." WHERE auth_user_md5.user_id = '".$user->id."'");
	$db->next_record();
	$fullname = htmlReady($db->f("tv")." ".$db->f("tr")." ".$db->f("vn")." ".$db->f("nn"));

	return array(
		"fieldofstudy"	=> $fieldofstudy,
		"studentname"	=> $fullname,
	);
}

/**
 * gets the field of study of the current user from the db
 *
 * @access  private
 * @returns string 	the field of study 
 *
 */
function getFieldOfStudy(){
	global $user;
	
	$db = &new DB_Seminar ();
	

	// get field of study
	$db->query("SELECT user_studiengang.*,studiengaenge.name FROM user_studiengang LEFT JOIN studiengaenge USING (studiengang_id) WHERE user_id = '".$user->id."' ORDER BY studiengang_id");

	while ($db->next_record()) {
		$fieldofstudy .= $db->f("name")." ";
	}
	return $fieldofstudy;
}
 
/**
 * gets the complete name of the student
 *
 * @access  private
 * @returns string 	the complete name
 *
 */
function getStudentname(){
	global $user;
	
	$db = &new DB_Seminar ();
	
	//get fullname
	$db->query("SELECT user_info.title_front as tv"
		.", user_info.title_rear as tr"
		.", auth_user_md5.Vorname vn"
		.", auth_user_md5.Nachname as nn"
		." FROM auth_user_md5 LEFT JOIN user_info USING (user_id)"
		." WHERE auth_user_md5.user_id = '".$user->id."'");
	$db->next_record();
	$fullname = htmlReady($db->f("tv")." ".$db->f("tr")." ".$db->f("vn")." ".$db->f("nn"));

	return $fullname;
}

/**
 * gets the seminars of the currents user from the db
 *
 * @access  private
 * @param   string $semesterid		the selected semester id
 * @param   boolean $onlyseminars	could reduce the assortment
 * @returns array 	the seminars
 *
 */
 function getSeminare($semesterid,$onlyseminars){
	 global $user,$semestersAR,$SEM_CLASS,$SEM_TYPE,$_fullname_sql;
	 
	 $db = &new DB_Seminar ();
	 $i = 0;
	 // if its not an archiv-only-semester, get the current ones
	 if(!$semestersAR[$semesterid]["onlyarchiv"]){
		 
		 // the status the user should have in the seminar
		 $status = "autor";
		 
		 // some stolen code from a.noack :)
		 foreach (SemesterData::GetSemesterArray() as $key => $value){
			 if (isset($value['beginn']) && $value['beginn'])
			 $sem_start_times[] = $value['beginn'];
		 }
		 foreach ($SEM_CLASS as $key => $value){
			 if ($value['bereiche']){
				 foreach($SEM_TYPE as $type_key => $type_value){
					 if($type_value['class'] == $key)
					 $allowed_sem_status[] = $type_key;
				 }
			 }
		 }	
		 
		 
		 // new seminars
		 $db2 = &new DB_Seminar ();
		 
		 $query = "SELECT b.Seminar_id,b.Name,b.Untertitel,b.VeranstaltungsNummer, "
		 . "INTERVAL(start_time," . join(",",$sem_start_times) .") AS sem_number , "
		 . "IF(duration_time=-1,-1,INTERVAL(start_time+duration_time," . join(",",$sem_start_times) .")) AS sem_number_end "
		 . "FROM seminar_user a LEFT JOIN seminare b USING(Seminar_id) WHERE ";
		 
		 if($onlyseminars) $query .= ((is_array($allowed_sem_status)) ? " b.status IN('" . join("','",$allowed_sem_status) . "') AND " : "") ." ";
		 
		 $query .= " a.user_id='".$user->id."' AND a.status='".$status."' "
		 . "HAVING (sem_number <= ".$semestersAR[$semesterid]["id"]." AND (sem_number_end >= ".$semestersAR[$semesterid]["id"]." OR sem_number_end = -1))";
		 $db->query($query);	
		 
		 while ($db->next_record()) {
			 $seminarid = $db->f("Seminar_id");
			 $name = $db->f("Name");
			 $seminarnumber = $db->f("VeranstaltungsNummer");
			 $description = $db->f("Untertitel");
			 if ($description)
			 $name .= ": ".$description;
			 $sem_number_start = $db->f("sem_number");
			 $sem_number_end = $db->f("sem_number_end");
			 
			 $db2->query ("SELECT seminar_user.user_id, " . $_fullname_sql['full'] . " AS fullname, username, status, position FROM seminar_user LEFT JOIN auth_user_md5 USING (user_id)  LEFT JOIN user_info USING(user_id) WHERE seminar_user.Seminar_id = '".$seminarid."' AND status = 'dozent' ORDER BY position, Nachname");
			 $tutor = '';
			 while($db2->next_record()){
				 if ($tutor) $tutor .= "; ";
				 $tutor .= $db2->f("fullname");
			 }
			 
			 $seminare[$i] = array(
			 "id" 			=> $i,
			 "seminarid" 	=> $seminarid,
			 "seminarnumber" => $seminarnumber,
			 "tutor" 		=> $tutor,
			 "sws"			=> "",
			 "description" 	=> $name 
			 );
			 $i++;
		 }
	 }
	 //archiv seminars
	 $db->query ("SELECT archiv.name, archiv.seminar_id, archiv_user.status, archiv.VeranstaltungsNummer, archiv.name, archiv.semester, archiv.untertitel, archiv.studienbereiche, archiv.dozenten "
	 . "FROM archiv_user LEFT JOIN archiv  USING (seminar_id) "
	 . "WHERE archiv_user.user_id = '".$user->id."' AND archiv.semester = '".$semestersAR[$semesterid]["idname"]."'");
	 while($db->next_record()){
		 
		 $seminarid = $db->f("seminar_id");
		 $name = $db->f("name");
		 $seminarnumber = $db->f("VeranstaltungsNummer");
		 $description = $db->f("untertitel");
		 if ($description)
		 $name .= ": ".$description;	
		 $tutor = $db->f("dozenten");
		 $semesterDB = $db->f("semester");
		 
		 if( (!$onlyseminars) || 
		 ($onlyseminars && $db->f("studienbereiche")))
		 $seminare[$i] = array(
		 "id" 			=> $i,
		 "seminarid" 	=> $seminarid,
		 "seminarnumber" => $seminarnumber,
		 "tutor" 		=> $tutor,
		 "description" 	=> $name 
		 );
		 $i++;
	 }
 return $seminare;
 }
?>
