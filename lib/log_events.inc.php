<?
# Lifter007: TEST
# Lifter003: TEST
/**
* log_events.inc.php
*
* Functions to create log events
*
* @author      Tobias Thelen <tthelen@uni-osnabrueck.de>
* @author      Jan-Hendrik Willms <tleilax+studip@gmail.com>
* @author      Peter Thienel <thienel@data-quest.de>
* @access      public
* @module      log_events.inc.php
* @modulegroup library
* @package     studip_core
*/

/**
 * Logs an event to the database after a certain action took place along with
 * the ids of the range object the action possibly affected. You can provide
 * additional info as well as debug information.
 * 
 * @deprecated use EventLog::log() instead
 * @param String $action     Name of the action that took place 
 * @param mixed  $affected   Range id that was affected by the action, if any
 * @param mixed  $coaffected Range id that was possibly affected as well
 * @param mixed  $info       Information to add to the event
 * @param mixed  $dbg_info   Debug information to add to the event
 * @param mixed  $user_id    Provide null for the current user id
 **/
function log_event($action, $affected = null, $coaffected = null, $info = null,
        $dbg_info = null, $user_id = null) {
    
    return StudipLog::log($action, $affected, $coaffected, $info,
            $dbg_info, $user_id);
}
