#!/usr/bin/php -q
<?php
# Lifter007: TODO
# Lifter003: TODO

/* This script converts dates from the traditional metadate-style to the new style */


// run untill really everything is done...
set_time_limit(0); 

// we need enough memory
ini_set( "memory_limit", "256M");


// set name of subroutine file
// (neede because of PHP memory problems, if the conversion would be done in one step)
$CONVERSION_SUBROUTINE_FILE = dirname(__FILE__) ."/convert_dates_for_imported_seminars-SUBROUTINE.php";

// define step size (number of rows) for subroutine proccessing
$STEP_SIZE= 300;


// create root user environment:
require_once dirname(__FILE__).'/studip_cli_env.inc.php';

// include business logic
require_once('lib/classes/Seminar.class.php');
require_once('lib/resources/lib/VeranstaltungResourcesAssign.class.php');

// open log file
$logfile_handle = fopen( $GLOBALS["TMP_PATH"] ."/Stud.IP_date_conversion.log", "ab");
if(!$logfile_handle) {
    throw new Exception ("Can't open logfile ".$GLOBALS["TMP_PATH"]."/Stud.IP_date_conversion.log");
}

// lets go...
fwrite($logfile_handle, "(". date("Y-m-d H:i:s T") .") Starting conversion of imported seminar dates.\n");


// STEP 1:
//      convert the title of dates (="content") to real themes
//      converts all dates, that don't have content==''
fwrite($logfile_handle, "(". date("Y-m-d H:i:s T") .") Step 1: Converting the title of dates to real themes:\n");

// create database connectors
$db  = new DB_Seminar();
$db2 = new DB_Seminar();

// get all dates (=termine) with content!=''
$db->query("SELECT termine.* FROM seminare LEFT JOIN termine ON (seminare.Seminar_id = termine.range_id) WHERE (content != '' OR description != '')");

$counter = 0;

// create new theme for each date
while ($db->next_record()) {
    $counter++;
    $new_issue_id = md5(uniqid("Issue"));
        $db2->query("INSERT INTO themen_termine (issue_id, termin_id) VALUES ('$new_issue_id', '".$db->f('termin_id')."')");
        $db2->query("INSERT INTO themen (issue_id, seminar_id, author_id, title, description, mkdate, chdate) VALUES ('$new_issue_id', '".$db->f('range_id')."', '".$db->f('author_id')."', '".mysql_escape_string($db->f('content'))."', '".mysql_escape_string($db->f('description'))."', '".$db->f('mkdate')."', '".$db->f('chdate')."')");
        $db2->query("UPDATE termine SET content = '', description = '' WHERE termin_id = '".$db->f('termin_id')."'");
        $db2->query("UPDATE folder SET range_id = '$new_issue_id' WHERE range_id = '".$db->f('termin_id')."'"); 
        if($db->f('topic_id')){ 
            $db2->query("UPDATE px_topics SET topic_id = '$new_issue_id' WHERE topic_id = '".$db->f('topic_id')."'"); 
            $db2->query("UPDATE px_topics SET root_id = '$new_issue_id'  WHERE root_id = '".$db->f('topic_id')."'"); 
            $db2->query("UPDATE px_topics SET parent_id = '$new_issue_id'  WHERE parent_id = '".$db->f('topic_id')."'"); 
        } 
    fwrite($logfile_handle, "(". date("Y-m-d H:i:s T") .") converting termin_id='".$db->f('termin_id')."', added theme_id='".$new_issue_id."'\n");
    flush();
}

fwrite($logfile_handle, "(". date("Y-m-d H:i:s T") .") Finished Step 1. Converted $counter dates.\n");

// END OF STEP 1


// STEP 2:
//      create single dates for all regular dates (turnus_data in metadata_dates)
fwrite($logfile_handle, "(". date("Y-m-d H:i:s T") .") Step 2: Creating single dates for all regular dates:\n");

// reset counter
$counter = 0;
$output = array();

do {
    // call conversion subroutine with number of rows that should get processed
    $numberOfConvertedRows = exec( $CONVERSION_SUBROUTINE_FILE." ".$STEP_SIZE, $output ,$exitStatus);
    if( $exitStatus == FALSE ){
        fwrite($logfile_handle, "There were errors in the subroutine script. Stopping.\n");
        throw new Exception ("There were errors in the subroutine script.\n");
    }

    // remove last line
    array_pop( $output);
    
    // write output to logfile    
    fwrite( $logfile_handle, implode("\n", $output));

    // count total amount of converted seminars
    $counter += $numberOfConvertedRows;
} while( $numberOfConvertedRows != 0);

fwrite($logfile_handle, "(". date("Y-m-d H:i:s T") .") Finished Step 2. Converted $counter seminars.\n");

fwrite($logfile_handle, "(". date("Y-m-d H:i:s T") .") Conversion finished.");

// close logfile
fclose($logfile_handle);

// return true
exit(1);
