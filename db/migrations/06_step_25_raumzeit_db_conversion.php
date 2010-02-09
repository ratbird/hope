<?
class Step25RaumzeitDbConversion extends DBMigration
{
    function description ()
    {
        return 'convert dates for StEP00025; see logfile in $TMP_PATH';
    }

    function up ()
    {
        // open log file
        $logfile_handle = fopen( $GLOBALS["TMP_PATH"] ."/Stud.IP_date_conversion.log", "ab");
        if(!$logfile_handle) {
            throw new Exception ("Can't open logfile ".$GLOBALS["TMP_PATH"]."/Stud.IP_date_conversion.log");
        }
        
        $this->write( get_class($this).": Starting data conversion... - this may take a very long time");

        // create secret password for subroutine authentication
        $secret_password = md5(uniqid("ditnuc6532ktn"));

        // signal start of conversion and set 'secret' 
        $this->db->query("
            REPLACE INTO `config` ( `config_id` , `parent_id` , `field` , `value` , `is_default` , `type` , `range` , `section` , `position` , `mkdate` , `chdate` , `description` , `comment` , `message_template` )
            VALUES (
            'migration5' , '', 'MIGRATION_5_TEMPORARY_SECRET', '".$secret_password."', '0', 'string', 'global', '', '0', '0', '0', 'Temporary secret string for migragtion 5', 'Temporary entry of migration 5', ''
            );
        ");
        
        $this->convert_data( $logfile_handle, $secret_password);       

        // remove signal
        $this->db->query("
            DELETE FROM `config` WHERE config_id = 'migration5';
        ");

        $this->write( get_class($this).": Finished with data conversion...");
        
        // close logfile
        fclose($logfile_handle);        
    }
    
    
    function convert_data( $logfile_handle, $secret_password){
        
        // data conversion code:
                
        // we need enough memory
        ini_set( "memory_limit", "256M");
               
        // set URL of subroutine file
        // (needed because of PHP memory problems, if the conversion would be done in one step)
        $CONVERSION_SUBROUTINE_URL = $GLOBALS["ABSOLUTE_URI_STUDIP"] ."raumzeit_conversion_subroutine.php";
        
        // define step size (number of rows) for subroutine proccessing
        $STEP_SIZE= 300;
        
                
        // include business logic
        require_once('lib/classes/Seminar.class.php');
        require_once('lib/resources/lib/VeranstaltungResourcesAssign.class.php');

        
        
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
        
        // set number of record to start with
        $start_at = 0;
        
        do {
            // call the conversion subroutine with number of rows that should get processed           

            $subroutine_url = $CONVERSION_SUBROUTINE_URL ."?step_size=".$STEP_SIZE."&start_at=".$start_at."&secret=".$secret_password; 

// curl:
            // create cURL-Handle
            $ch = curl_init();

            // set url and other option
            curl_setopt($ch, CURLOPT_URL, $subroutine_url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);

            // make the call to the url
            $response = curl_exec ($ch);

            // close cURL-Handle und gebe die Systemresourcen frei
            curl_close($ch);

// file_get_contents (fopen wrappers)
//   removed file_get_contents access, in favor of curl because file_get_contents could not access the desired URL with 
//   every data we had for testing; it was not possible to reproduce this error e.g. by calling file_get_contents 
//   directly from the shell
//            // open URL via fopen = "call" subroutine
//            $response = file_get_contents( $subroutine_url );

            // success ?
            if( $response == FALSE ){
                $this->write( get_class($this)." - Error while executing subroutine. Can't open URL. Stopping.\n");
                fwrite($logfile_handle, "Error while executing subroutine. Can't open URL '$subroutine_url'. Stopping.\n");
                throw new Exception("Error while executing subroutine.");
            }            
            

            // some not quite nice error handling:
            if( substr($response,0,5) == "ERROR" ){
                // write output to logfile
                $this->write( get_class($this)." - Error while executing subroutine. Please see logfile for details. Stopping.\n");
                fwrite( $logfile_handle, $response);
                fwrite($logfile_handle, "Error while executing subroutine. Stopping.\n");
                throw new Exception("Error while executing subroutine.". $response);
            }

            // get last line (holds the number of converted rows)
            $begin_of_last_line = strrpos( $response, "\n")+1;
            $numberOfConvertedRows = substr($response, $begin_of_last_line, strlen($response)-$begin_of_last_line);

            // check, if $numberOfConvertedRows is really a number
            if( !is_numeric($numberOfConvertedRows) ){
                $this->write( get_class($this)." - Error while executing subroutine. Please see logfile for details. Stopping.\n");
                // write output to logfile
                fwrite( $logfile_handle, $response."\n");
                fwrite($logfile_handle, "Error while executing subroutine. Invalid number of converted lines found. Stopping.\n");
                throw new Exception("Error while executing subroutine.\n ". $response);
            }

            // cutoff last line
            $response = substr($response, 0, $begin_of_last_line);
            
            // write output to logfile
            fwrite( $logfile_handle, $response);
        
            // count total amount of converted seminars
            $counter += $numberOfConvertedRows;

            // step to next record package            
            $start_at += $STEP_SIZE;
            
        } while( $numberOfConvertedRows != 0);
        
        fwrite($logfile_handle, "(". date("Y-m-d H:i:s T") .") Finished Step 2. Converted $counter seminars.\n");
        
        fwrite($logfile_handle, "(". date("Y-m-d H:i:s T") .") Conversion finished.");

        $this->write( get_class($this).": Converted $counter seminars.");        
    }
}
?>
