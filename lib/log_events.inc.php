<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* log_events.inc.php
*
* Functions to create log events
*
* @author               Tobias Thelen <tthelen@uni-osnabrueck.de>
* @access               public
* @module               log_events.inc.php
* @modulegroup      library
* @package              studip_core
*/

function get_log_action_id($db, $action) {
    $db->query("SELECT action_id, active FROM log_actions WHERE name='$action'");
    if ($db->next_record()) {
        if (!$db->f("active")) return -1; // inactive
        return $db->f("action_id");
    } elseif ($action=="LOG_ERROR") { // prevent from inf. looping if LOG_ERROR is unknown
        return 99999;
    }
    return 0;
}

function log_event($action, $affected=NULL, $coaffected=NULL, $info=NULL, $dbg_info=NULL, $user=NULL) {
    global $auth, $LOG_ENABLE;
    //print "logging... $action $affected $coaffected $info $dbg_info $user <p>";
    if (!$LOG_ENABLE) return; // don't log if logging is disabled
    $db=new DB_Seminar;
    $action_id=get_log_action_id($db,$action);
    if ($action_id==-1) return; // inactive action
    $timestamp=time();
    if (!$user) { // automagically set current user as agent
        $user=$auth->auth['uid'];
    }
    if (!$action_id) { // Action doesn't exist -> LOG_ERROR
        log_event("LOG_ERROR",NULL,NULL,NULL,"log_event($action,$affected,$coaffected,$info,$dbg_info) for user $user");
        return;
    }
    $eventid=md5(uniqid("Ay!Captain!",1));
    $q="INSERT INTO log_events SET event_id='$eventid', action_id='$action_id', user_id='$user', affected_range_id='$affected', coaffected_range_id='$coaffected', info='".addslashes($info)."', dbg_info='".addslashes($dbg_info)."', mkdate='$timestamp'";
    $db->query($q);
    return;
}

?>
