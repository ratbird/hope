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
            'foreign_key' => 'autor_id',
          //  'assoc_foreign_key' => 'user_id'
        );
        $config['belongs_to']['editor'] = array(
            'class_name' => 'User',
            'foreign_key' => 'editor_id',
          //  'assoc_foreign_key' => 'user_id'
        );
        $config['has_many']['owner'] = array(
            'class_name' => 'EventRange',
            'foreign_key' => 'event_id',
          //  'assoc_foreign_key' => 'user_id',
            'on_delete' => 'delete'
        );
        
        $config['has_many']['group_events'] = array(
            'class_name' => 'CalendarEvent',
            'foreign_key' => 'event_id',
            'assoc_foreign_key' => 'event_id'
        );
        
        parent::configure($config);
        
    }
    
}