<?php


class StudipLog
{
    /**
     * Magic log, intercepts all undefined static method calls
     * called method name must be the name of a log action
     *
     * @param string $name
     * @param array $arguments
     * @return boolean True if event was written or false if logging is disabled
     */
    public static function __callStatic($name, $arguments)
    {
        $log_action_name = strtoupper($name);
        $log_action = LogAction::findByName($log_action_name);
        if ($log_action) {
            return call_user_func_array('EventLog::log', $arguments);
        }
        throw new BadMethodCallException('Unknown method called: '
                . $log_action_name);
    }
    
    /**
    * Logs an event to the database after a certain action took place along with
    * the ids of the range object the action possibly affected. You can provide
    * additional info as well as debug information.
    * 
    * @param String $action_name     Name of the action that took place 
    * @param mixed  $affected   Range id that was affected by the action, if any
    * @param mixed  $coaffected Range id that was possibly affected as well
    * @param mixed  $info       Information to add to the event
    * @param mixed  $dbg_info   Debug information to add to the event
    * @param mixed  $user_id    Provide null for the current user id
    **/
    public static function log($action_name, $affected = null,
            $coaffected = null, $info = null, $dbg_info = null, $user_id = null)
    {
        $log_action = SimpleORMapCollection::createFromArray(
                LogAction::findByName($action_name))->first();
        if (!$log_action) {
            // Action doesn't exist -> LOG_ERROR
            $debug = sprintf('EventLog::log(%s,%s,%s,%s,%s) for user %s',
                    $log_action->name, $affected, $coaffected, $info,
                    $dbg_info, $user_id);
            self::log('LOG_ERROR', null, null, null, $debug);
            return false;
        }
        if ($log_action->isActive()) {
            // automagically set current user as agent
            if (!$user_id) {
                $user_id = $GLOBALS['auth']->auth['uid'];
            }
            $log_event = new LogEvent();
            $log_event->user_id = $user_id;
            $log_event->action_id = $log_action->getId();
            $log_event->affected_range_id = $affected;
            $log_event->coaffected_range_id = $coaffected;
            $log_event->info = $info;
            $log_event->dbg_info = $dbg_info;
            $log_event->store();
            return true;
        }
        return false;
    }
    
    public static function registerAction($name, $description, $info_template,
            $class)
    {
        $action = new LogAction();
        $action->name = $name;
        $action->description = $description;
        $action->info_template = $info_template;
        $action->class = $class;
        $action->type = 'core';
        $action->store();
    }
    
    public static function registerActionPlugin($name, $description,
            $info_template, $plugin_name)
    {
        $action = new LogAction();
        $action->name = $name;
        $action->description = $description;
        $action->info_template = $info_template;
        $action->class = plugin_name;
        $action->type = 'plugin';
        $action->store();
    }
    
    public static function registerActionFile($name, $description,
            $info_template, $filename, $class)
    {
        $action = new LogAction();
        $action->name = $name;
        $action->description = $description;
        $action->info_template = $info_template;
        $action->filename = $filename;
        $action->class = $class;
        $action->type = 'file';
        $action->store();
    }
    
    public function unregisterAction($name)
    {
        $action = LogAction::findByName($name)->first();
        if ($action) {
            return $action->delete();
        }
        return false;
    }
    
    /**
     * Finds all seminars by given search string. Searches for the name of
     * existing or already deleted seminars.
     * 
     * @param string $needle The needle to search for.
     * @return array
     */
    public static function searchSeminar($needle)
    {
        $result = array();

        // search for active seminars
        $courses = Course::findBySQL("VeranstaltungsNummer LIKE CONCAT('%', :needle, '%')
                     OR seminare.Name LIKE CONCAT('%', :needle, '%')",
                array(':needle' => $needle));

        foreach ($courses as $course) {
            $title = sprintf('%s %s (%s)',
                             $course->VeranstaltungsNummer,
                             my_substr($course->name, 0, 40),
                             $course->start_semester->name);
                $result[] = array($course->getId(), $title);
        }

        // search deleted seminars
        // SemName and Number is part of info field, old id (still in DB) is in affected column
        $log_action_ids_archived_seminar = SimpleORMapCollection::createFromArray(
                LogAction::findBySQL(
                    "name IN ('SEM_ARCHIVE', 'SEM_DELETE_FROM_ARCHIVE')"))
                ->pluck('action_id');
        $log_events_archived_seminar = LogEvent::findBySQL("info LIKE CONCAT('%', ?, '%')
                AND action_id IN (?) ",
                array($needle, $log_action_ids_archived_seminar));
        foreach ($log_events_archived_seminar as $log_event) {
            $title = sprintf('%s (%s)', my_substr($log_event->info, 0, 40), _('gelöscht'));
            $result[] = array($log_event->affected_range_id, $title);
        }

        return $result;
    }

    /**
     * Finds all institutes by given search string. Searches for the name of
     * existing or already deleted institutes.
     * 
     * @param type $needle The needle to search for.
     * @return array
     */
    public static function searchInstitute($needle)
    {
        $result = array();

        $institutes = Institute::findBySQL(
                "name LIKE CONCAT('%', ?, '%')", array($needle));
        foreach ($institutes as $institute) {
            $result[] = array($institute->getId(), my_substr($institute->name, 0, 28));
        }

        // search for deleted institutes
        // Name of deleted institute is part of info field,
        // old id (still in DB) is in affected column
        $log_action_delete_institute = SimpleORMapCollection::createFromArray(
                LogAction::findByName('INST_DEL'))->first();
        $log_events_delete_institute = LogEvents::findBySQL(
                "actions_id = ? AND info LIKE CONCAT('%', ?, '%')",
                array($log_action_delete_institute->getId(), $needle));
        foreach ($log_events_delete_institute as $log_event) {
            $title = sprintf('%s (%s)', $log_event->info, _('gelöscht'));
            $result[] = array($log_event->affected_range_id, $title);
        }

        return $result;
    }

    /**
     * Finds all users by given search string. Searches for the users id,
     * part of the name or the username.
     * 
     * @param type $needle The needle to search for.
     * @return array
     */
    public static function searchUser($needle)
    {

        $result = array();

        $users = User::findBySQL("Nachname LIKE CONCAT('%', :needle, '%')
                     OR Vorname LIKE CONCAT('%', :needle, '%')
                     OR username LIKE CONCAT('%', :needle, '%')",
                array(':needle' => $needle));
        foreach ($users as $user) {
            $name = sprintf('%s (%s)', my_substr($user->getFullname(), 0, 20),
                    $user->username);
            $result[] = array($user->getId(), $name);
        }

        // search for deleted users
        // The name of the user is part of info field,
        // old id (still in DB) is in affected column
        $log_action_deleted_user = SimpleORMapCollection::createFromArray(
                LogAction::findByName('USER_DEL'))->first();
        $log_events_deleted_user = LogEvent::findBySQL(
                "action_id = ? AND info LIKE CONCAT('%', ?, '%')",
                array($log_action_deleted_user->getId(), $needle));
        foreach ($log_events_deleted_user as $log_event) {
            $name = sprintf('%s (%s)', $log_event->info, _('gelöscht'));
            $result[] = array($log_event->affected_range_id, $name);
        }

        return $result;
    }
    
    /**
     * Finds all resources by given search string. The search string can be
     * eather a resource id or part of the name.
     * 
     * @param string $needle The needle to search for.
     * @return array
     */
    public static function searchResource($needle)
    {
        $result = array();

        $query = "SELECT resource_id, name FROM resources_objects WHERE name LIKE CONCAT('%', ?, '%')";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($needle));

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $result[] = array($row['resource_id'], my_substr($row['name'], 0, 30));
        }

        return $result;
    }
    
    public static function searchObjectByAction($needle, $action_id)
    {
        $action = LogAction::find($action_id);
        
        if ($action) {
            switch ($action->type) {
                case 'plugin':
                    $plugin_manager = PluginManager::getInstance();
                    $plugin_info = $plugin_manager->getPluginInfo($action->class);
                    $class_name = $plugin_info['class'];
                    $plugin = $plugin_manager->getPlugin($class_name);
                    if ($plugin instanceof Loggable) {
                        return $class_name::logSearch($needle, $action->name);
                    }
                    break;
                case 'file':
                    if (!file_exists($action->filename)) {
                        require_once($action->filename);
                        $class_name = $action->class;
                        if ($class_name instanceof Loggable) {
                            return $class_name::logSearch($needle, $action->name);
                        }
                    }
                    break;
                case 'core':
                    $class_name = $action->class;
                    if ($class_name instanceof Loggable) {
                        return $class_name::logSearch($needle, $action->name);
                    }
            }
        }
        return array();
    }
}