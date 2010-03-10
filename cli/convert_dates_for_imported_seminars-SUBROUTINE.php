#!/usr/bin/php -q
<?php
# Lifter007: TODO
# Lifter003: TODO

// run untill really everything is done...
set_time_limit(0);

// we need enough memory
ini_set( "memory_limit", "256M");

// get command line parameters
$parameter_count = $_SERVER['argc'];
$parameters      = $_SERVER['argv'];

// check command line parameters
if( $parameter_count != 2) {
    echo "(". date("Y-m-d H:i:s T") .") ERROR: Wrong number of command line parameters. [convert_regular_dates_to_single_dates_with_themes-SUBROUTINE.php]\n";
    exit(0);
}


// create root user environment:
require_once dirname(__FILE__).'/studip_cli_env.inc.php';

// include business logic classes
require_once('lib/classes/Seminar.class.php');
require_once('lib/resources/lib/VeranstaltungResourcesAssign.class.php');
//require_once('lib/raumzeit/QueryMeasure.class.php');

// read command line parameters:

// number of records to be converted this time
$step_size = $_SERVER['argv'][1];

// check if parameters are valid integers
if( !is_numeric( $step_size) ){
    echo "(". date("Y-m-d H:i:s T") .") ERROR: One or more invalid parameters. (not numeric) [convert_regular_dates_to_single_dates_with_themes-SUBROUTINE.php]\n";
    exit(0);
}


// set counter for this round...
$seminar_counter = 0;

// prevents the caching of assign objects in AssignObject.class.php (?)
$GLOBALS['FORCE_THROW_AWAY'] = TRUE;

// enable 
// - raumbuchungen, die auf ein metadate gebucht sind werden auf einzeltermine verschoben
// - ressources assign: termine mit raum verknüpft
// creates 
$GLOBALS['CONVERT_SINGLE_DATES'] = TRUE;


$db = new DB_Seminar();
$db2 = new DB_Seminar();

// read a bunch of seminares where the change date is zero 
// (chdate funtions as a marker, it will be modified during conversion)
$db->query("SELECT Seminar_id, Name FROM seminare WHERE chdate = 0 LIMIT 0, $step_size");
    
// get number of rows
$number_of_rows = $db->num_rows();

// initialize counter
$seminar_counter = 0;

// loop through all found seminars
while ($db->next_record()) {

        // get seminar ID
        $seminar_id = $db->f('Seminar_id');
        
        echo "(". date("Y-m-d H:i:s T") .") Converting Seminar ID='$seminar_id', Name '".$db->f('Name')."'\n";
        flush();
        unset($sem);
        
        // create new seminar object
        $sem = new Seminar( $seminar_id);

        // loop through every regular date
        foreach ($sem->metadate->cycles as $key => $val) {

            // assign ressources, if ressources are used
            if ($val->resource_id) {
                $veranst_assign = new VeranstaltungResourcesAssign($sem->getId());
                $veranst_assign->deleteAssignedRooms();
            }
            
            // this method creates corresponding single dates for regular dates, if they are not present 
            $sem->getSingleDatesForCycle($key);
            
            $val->resource_id = '';
        }
        
        // update the seminar object (modifies the chdate)
        $sem->store();

        $query = sprintf("UPDATE seminare SET chdate='%s' WHERE Seminar_id='%s' ", time(), $db->f('Seminar_id'));
        $db2->query($query);
        
        $seminar_counter++;        
}


echo "Number of converted seminars in this substep: $seminar_counter\n"; // return the number of convertet dates (last output is return value)
echo "$seminar_counter\n"; // return the number of convertet dates (last output is return value)
flush();

// return true
exit(1);

?>