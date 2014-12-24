<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class GroupCalendar
{
    public $calendars;
    
    public $group;
    
    public function __construct($group_id)
    {
        $this->group = Statusgruppen::find($group_id);
        if (!$this->group) {
            //throw
        }
        
        
    }
    
    public function getGroupName()
    {
        $this->group->name;
    }
    
    
}