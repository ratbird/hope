<?php
/**
 * LogEvent
 * model class for table log_events
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Peter Thienel <thienel@data-quest.de>
 * @copyright   2013 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       3.0
 * @property string event_id database column
 * @property string id alias column for event_id
 * @property string user_id database column
 * @property string action_id database column
 * @property string affected_range_id database column
 * @property string coaffected_range_id database column
 * @property string info database column
 * @property string dbg_info database column
 * @property string mkdate database column
 * @property LogAction action belongs_to LogAction
 * @property User user belongs_to User
 */


class LogEvent extends SimpleORMap
{

    protected $formatted_text = '';

    protected static function configure($config = array())
    {
        $config['db_table'] = 'log_events';
        $config['belongs_to']['action'] = array(
            'class_name' => 'LogAction',
            'foreign_key' => 'action_id',
        );
        $config['belongs_to']['user'] = array(
            'class_name' => 'User',
            'foreign_key' => 'user_id',
        );
        $config['notification_map']['after_create'] = 'LogEventDidCreate';
        $config['notification_map']['before_create'] = 'LogEventWillCreate';
        parent::configure($config);
    }

    /**
     * Returns the number of log events counted by actions as an array where
     * the ey is the action id and the value is the number of events for
     * this action.
     *
     * @return array Number of loge events for all actions
     */
    public static function countByActions()
    {
        $query = "SELECT action_id, COUNT(*) FROM log_events GROUP BY action_id";
        $statement = DBManager::get()->query($query);
        return $statement->fetchGrouped(PDO::FETCH_COLUMN);
    }

    /**
     * Deletes all expired events.
     *
     * @return int Number of deleted events.
     */
    public static function deleteExpired()
    {
        $db = DBManager::get();
        $sql = 'DELETE log_events FROM log_events JOIN log_actions USING(action_id)
            WHERE expires > 0 AND mkdate + expires < UNIX_TIMESTAMP()';
        return $db->exec($sql);
    }

    /**
     * Returns the formatted log event. Fills the action template with data
     * of this event.
     *
     * @return string The formatted log event.
     */
    public function formatEvent()
    {
        $text = $this->formatObject();
        $patterns = array(
            '/%sem\(%affected\)/e',
            '/%sem\(%coaffected\)/e',
            '/%studyarea\(%affected\)/e',
            '/%studyarea\(%coaffected\)/e',
            '/%res\(%affected\)/e',
            '/%res\(%coaffected\)/e',
            '/%inst\(%affected\)/e',
            '/%inst\(%coaffected\)/e',
            '/%user\(%affected\)/e',
            '/%user\(%coaffected\)/e',
            '/%user/e',
            '/%singledate\(%affected\)/e',
            '/%semester\(%coaffected\)/e',
            '/%plugin\(%coaffected\)/e',
            '/%affected/',
            '/%coaffected/',
            '/%info/',
            '/%dbg_info/'
        );
        $replacements = array(
            "self::formatSeminar('affected_range_id')",
            "self::formatSeminar('coaffected_range_id')",
            "self::formatStudyarea('affected_range_id')",
            "self::formatStudyarea('coaffected_range_id')",
            "self::formatResource('affected_range_id')",
            "self::formatResource('coaffected_range_id')",
            "self::formatInstitute('affected_range_id')",
            "self::formatInstitute('coaffected_range_id')",
            "self::formatUsername('affected_range_id')",
            "self::formatUsername('coaffected_range_id')",
            "self::formatUsername('user_id')",
            "self::formatSingledate('affected_range_id')",
            "self::formatSemester('coaffected_range_id')",
            "self::formatPlugin('coaffected_range_id')",
            $this->affected_range_id,
            $this->coaffected_range_id,
            htmlReady($this->info),
            htmlReady($this->dbg_info)
        );
        return preg_replace($patterns, $replacements, $text);
    }

    /**
     * Returns the name of the resource for the resource id found in the
     * given field or the resource id if the resource is unknown.
     *
     * @param string $field The name of the table field.
     * @return string The name of the resource or resource id.
     */
    protected function formatResource($field) {
        $resObj = ResourceObject::Factory($this->$field);
        if ($resObj->getName()) {
            return $resObj->getFormattedLink();
        }
        return $this->$field;
    }

    /**
     * Returns the name of the user with the id found in the given field.
     *
     * @param string $field The name of the table field.
     * @return string The name of the user.
     */
    protected function formatUsername($field)
    {
        return '<a href="' . URLHelper::getLink('dispatch.php/admin/user/edit/'
                . $this->$field) . '">' . htmlReady(get_fullname($this->$field))
                . '</a>';
    }

    /**
     * Returns the name of the seminar for the id found in the given
     * field or the id if the seminar is unknown.
     *
     * @param string $field The name of the table field.
     * @return string The name of seminar or the id.
     */
    protected function formatSeminar($field)
    {
        $course = Course::find($this->$field);

        if (!$course) {
            return $this->$field;
        }
        return sprintf('<a href="%s">%s %s (%s)</a>',
                       URLHelper::getLink('adminarea_start.php',
                               array('select_sem_id' => $course->getId())),
                       htmlReady($course->VeranstaltungsNummer),
                       htmlReady(my_substr($course->name, 0, 100)),
                       htmlReady($course->start_semester->name));
    }

    /**
     * Returns the name of the institute for the id found in the given
     * field or the id if the institute is unknown.
     *
     * @param string $field The name of the table field.
     * @return string The name of institute or the id.
     */
    protected function formatInstitute($field)
    {
        $institute = Institute::find($this->$field);

        if (!$institute) {
            return $this->$field;
        }

        return sprintf('<a href="%s">%s</a>',
                       URLHelper::getLink('institut_main.php',
                               array('auswahl' => $institute->getId())),
                       htmlReady(my_substr($institute->name, 0, 100)));
    }

    /**
     * Returns the name of the study area for the id found in the given
     * field or the id if the study area is unknown.
     *
     * @param string $field The name of the table field.
     * @return string The name of seminar or the id.
     */
    protected function formatStudyarea($field)
    {
        $study_area = StudipStudyArea::find($this->$field);

        if (!$study_area) {
            return $this->$field;
        }

        return '<em>' . $study_area->getPath(' &gt ') . '</em>';
    }

    /**
     * Returns the singledate for the id found in the given field.
     *
     * @param string $field The name of the table field.
     * @return string The singledate.
     */
    protected function formatSingledate($field) {
        require_once('lib/raumzeit/SingleDate.class.php');
        $termin = new SingleDate($this->$field);
        return '<em>' . $termin->toString() . '</em>';
    }

    /**
     * Returns the name of the plugin for the id found in the given
     * field or the id if the plugin is unknown.
     *
     * @param string $field The name of the table field.
     * @return string The name of plugin or the id.
     */
    protected function formatPlugin($field) {
        $plugin_manager = PluginManager::getInstance();
        $plugin_info = $plugin_manager->getPluginInfoById($this->$field);

        return $plugin_info ? '<em>'
                . $plugin_info['name'] . '</em>' : $this->$field;
    }

    /**
     * Returns the name of the semester for the id found in the given
     * field or the id if the seminar is unknown.
     *
     * @param string $field The name of the table field.
     * @return string The name of semester or the id.
     */
    protected function formatSemester($field) {
        $semester = new SemesterData();
        $all_semester = $semester->getAllSemesterData();
        foreach ($all_semester as $val) {
            if ($val['beginn'] == $this->$field) {
                return '<em>' . $val['name'] . '</em>';
            }
        }
        return $this->$field;
    }

    protected function formatObject()
    {
        if ($this->action) {
            switch ($this->action->type) {
                case 'plugin':
                    $plugin_manager = PluginManager::getInstance();
                    $plugin_info = $plugin_manager->getPluginInfo($this->action->class);
                    $class_name = $plugin_info['class'];
                    $plugin = $plugin_manager->getPlugin($class_name);
                    if ($plugin instanceof Loggable) {
                        return $class_name::logFormat($this);
                    }
                    break;
                case 'file':
                    if (!file_exists($action->filename)) {
                        require_once($action->filename);
                        $class_name = $action->class;
                        if ($class_name instanceof Loggable) {
                            return $class_name::logFormat($this);
                        }
                    }
                    break;
                case 'core':
                    $class_name = $action->class;
                    if ($class_name instanceof Loggable) {
                        return $class_name::logFormat($this);
                    }
            }
        }
        return $this->action->info_template;
    }

}
