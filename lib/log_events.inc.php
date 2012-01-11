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
* @access      public
* @module      log_events.inc.php
* @modulegroup library
* @package     studip_core
*/

/**
 * Retrieves an action's id from the database
 * 
 * @param  DB_Seminar $db     Formerly passed in db object, now obsolete
 * @param  String     $action Name of the action
 * @return int        Id of the action
 */
function get_log_action_id($db, $action) {
    if ($action == 'LOG_ERROR') {
        return '9999'; // prevent from inf. looping if LOG_ERROR is unknown
    }

    $query = "SELECT IF(active=0, -1, action_id) "
           . "FROM log_actions "
           . "WHERE name = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($action));
    return $statement->rowCount() ? $statement->fetchColumn() : 0;
}

/**
 * Logs an event to the database after a certain action took place along with
 * the ids of the range object the action possibly affected. You can provide
 * additional info as well as debug information.
 *
 * @param String $action     Name of the action that took place 
 * @param mixed  $affected   Range id that was affected by the action, if any
 * @param mixed  $coaffected Range id that was possibly affected as well
 * @param mixed  $info       Information to add to the event
 * @param mixed  $dbg_info   Debug information to add to the event
 * @param mixed  $user_id    Provide null for the current user id
 **/
function log_event($action, $affected=null, $coaffected=null, $info=null, $dbg_info=null, $user_id=null) {
    if (!$GLOBALS['LOG_ENABLE']) {
        return; // don't log if logging is disabled
    }
    # echo "logging: $action $affected $coaffected $info $dbg_info $user_id<br>";

    $action_id = get_log_action_id(null, $action);
    if ($action_id == -1) { 
        return; // inactive action
    }
    
    if (!$user_id) { // automagically set current user as agent
        $user_id = $GLOBALS['auth']->auth['uid'];
    }
    if (!$action_id) { // Action doesn't exist -> LOG_ERROR
        $debug = sprintf('log_event(%s,%s,%s,%s,%s) for user %s',
                         $action, $affected, $coaffected, $info, $dbg_info,
                         $user_id);
        log_event('LOG_ERROR', null, null, null, $debug);
        return;
    }

    $query = "INSERT INTO log_events "
           . "(event_id, action_id, user_id, affected_range_id,"
           . " coaffected_range_id, info, dbg_info, mkdate) "
           . "VALUES (?, ?, ?, ?, ?, ?, ?, UNIX_TIMESTAMP())";
    DBManager::get()
        ->prepare($query)
        ->execute(array(
           md5(uniqid('Ay!Captain!', 1)), // $event_id
           $action_id, $user_id, $affected, $coaffected, $info, $dbg_info
        ));
}
