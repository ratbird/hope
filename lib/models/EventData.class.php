<?php
/**
 * EventData.class.php - Model class for calendar events.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Peter Thienel <thienel@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       3.2
 */

class EventData extends SimpleORMap
{

    protected static function configure($config = array())
    {
        $config['db_table'] = 'event_data';

        $config['belongs_to']['author'] = array(
            'class_name' => 'User',
            'foreign_key' => 'author_id',
        );
        $config['belongs_to']['editor'] = array(
            'class_name' => 'User',
            'foreign_key' => 'editor_id',
        );
        $config['has_many']['calendars'] = array(
            'class_name' => 'CalendarEvent',
            'foreign_key' => 'event_id'
        );

        $config['default_values']['linterval'] = 0;
        $config['default_values']['sinterval'] = 0;
        
        parent::configure($config);

    }

    function __construct($id = null)
    {
        parent::__construct($id);
        $this->registerCallback('before_create', 'cbDefaultValues');
    }

    public function delete()
    {
        // do not delete until one calendar is left
        if (sizeof($this->calendars) > 1) {
            return false;
        }
        $calendars = $this->calendars;
        $ret = parent::delete();
        // only one calendar is left
        if ($ret) {
            $calendars->each(function($c) { $c->delete(); });
        }
        return $ret;
    }

    public static function garbageCollect()
    {
        DBManager::get()->query('DELETE event_data '
                . 'FROM calendar_event LEFT JOIN event_data USING(event_id)'
                . 'WHERE range_id IS NULL');
    }

    public function getDefaultValue($field)
    {
        if ($field == 'start') {
            return time();
        }
        if ($field == 'end' && $this->content['start']) {
            return $this->content['start'] + 3600;
        }
        if ($field == 'ts' && $this->content['start']) {
            return mktime(12, 0, 0, date('n', $this->content['start']),
                date('j', $this->content['start']), date('Y', $this->content['start']));
        }
        return parent::getDefaultValue($field);
    }

    protected function cbDefaultValues()
    {
        if (empty($this->content['uid'])) {
            $this->content['uid'] = 'Stud.IP-' . $this->event_id . '@' . $_SERVER['SERVER_NAME'];
        }
    }
}
