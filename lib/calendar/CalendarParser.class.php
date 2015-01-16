<?
# Lifter002: TODO
# Lifter007: TODO

/**
 * CalendarParser.class.php
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Peter Thienel <thienel@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     calendar
 */

class CalendarParser
{

    private $events = array();
    private $components;
    private $type;
    private $number_of_events;
    private $public_to_private = false;
    private $client_identifier;
    private $time;

    function CalendarParser()
    {
        $this->client_identifier = '';
    }

    function parse($data, $ignore = null)
    {
        foreach ($data as $properties) {
            if ($this->public_to_private && $properties['CLASS'] == 'PUBLIC') {
                $properties['CLASS'] = 'PRIVATE';
            }
            $properties['CATEGORIES'] = implode(', ', $properties['CATEGORIES']);
            $this->components[] = $properties;
        }
    }

    public function getCount($data)
    {
        return 0;
    }

    public function parseIntoDatabase($range_id, $data, $ignore)
    {
        if ($this->parseIntoObjects($data, $ignore)) {
            $database->writeObjectsIntoDatabase($this->events, 'REPLACE');
            return true;
        }

        return false;
    }

    public function parseIntoObjects($range_id, $data, $ignore)
    {
        $this->time = time();
        if ($this->parse($data, $ignore)) {
            if (is_array($this->components)) {
                foreach ($this->components as $props) {
                    $event = CalendarEvent::findByUid($props['UID'], $range_id);
                    if ($event) {
                        $this->events[] = $this->setProperties($event->event, $props);
                    } else {
                        $event = new CalendarEvent();
                        $this->events[] = $this->setProperties($event->event, $props);
                    }
                }
            }
            return true;
        }
        $message = _('Die Import-Daten konnten nicht verarbeitet werden!');

        return false;
    }
    
    private function setProperties($event, $props)
    {
        $event->uid = $props['UID'];
        $event->start = $props[''];
        $event->end = $props[''];
        $event->summary = $props[''];
        $event->description = $props[''];
        $event->class = $props[''];
        $event->categories = $props[''];
        $event->category_intern = $props[''];
        $event->priority = $props[''];
        $event->location = $props[''];
        $event->duration = $props[''];
        $event->count = $props[''];
        $event->expire = $props[''];
        $event->exceptions = $props[''];
        $event->importdate = $this->time;
        
    }

    function getType()
    {
        return $this->type;
    }

    function &getObjects()
    {
        return $objects =& $this->events;
    }

    function changePublicToPrivate($value = true)
    {
        $this->public_to_private = $value;
    }

    function getClientIdentifier($data = null)
    {
        return $this->client_identifier;
    }

}

