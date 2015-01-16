<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
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
        
        $time = time();
        $config['default_values']['start'] = $time;
        $config['default_values']['end'] = $time + 3600;
        $config['default_values']['category_intern'] = 0;
        $config['default_values']['class'] = 'PRIVATE';
        $config['default_values']['rtype'] = 'SINGLE';
        $config['default_values']['linterval'] = 0;
        $config['default_values']['sinterval'] = 0;
        $config['default_values']['ts'] = mktime(12, 0, 0, date('n', $time),
                date('j', $time), date('Y', $time));
        $config['default_values']['uid'] = function($event) {
            return 'Stud.IP-' . $event->event_id . '@' . $_SERVER['SERVER_NAME'];
        };
        
        parent::configure($config);
        
    }
    
    public function delete()
    {
        if (sizeof($this->calendars) > 1) {
            return false;
        }
        $calendars = $this->calendars;
        if (parent::delete()) {
            $calendars->each(function($c) { $c->delete(); });
        }
        
    }
    
    public static function garbageCollect()
    {
        DBManager::get()->query('DELETE event_data '
                . 'FROM calendar_event LEFT JOIN event_data USING(event_id)'
                . 'WHERE range_id IS NULL');
    }
    
}