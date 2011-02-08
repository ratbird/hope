<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
// Wrapper class for driver functions in calendar/lib/driver/

require_once($RELATIVE_PATH_CALENDAR
        . "/lib/CalendarEvent.class.php");
require_once($RELATIVE_PATH_CALENDAR
        . "/lib/driver/$CALENDAR_DRIVER/event_driver.inc.php");

class DbCalendarEvent extends CalendarEvent {
    
    function DbCalendarEvent ($id = '', $properties = NULL) {
        global $user, $PERS_TERMIN_KAT, $TERMIN_TYP;
        
        $this->user_id = $user->id;
                
        if ($id != '' && !$properties) {
            $this->restore($id);
        }
        else {
            parent::CalendarEvent($properties);
        }
    }
    
    // public
    function getDescription () {
    
        if(isset($this->properties['DESCRIPTION']))
            return $this->properties['DESCRIPTION'];
        elseif ($description = event_get_description($this->id)) {
            $this->properties['DESCRIPTION'] = $description;
            return $this->properties['DESCRIPTION'];
        } else {
            return $this->properties['DESCRIPTION'] = '';
        }
    }
    
    // Store event in database
    // public
    function save () {
    
        event_save($this);
    }
    
    // delete event in database
    // public
    function delete () {
    
        return event_delete($this->id, $this->user_id);
    }
    
    // get event out of database
    // public
    function restore ($id) {
    
        if(!event_restore($id, $this))
            die("Unable to restore this event (ID='$id')!");
    }
    
    function update ($new_event) {
    
        $properties = $new_event->getProperty();
        // never update the uid and the make date!
        $uid = $this->getProperty('UID');
        $mkdate = $this->getMakeDate();
        foreach ($properties as $name => $value)
            $this->setProperty($name, $value);
        $this->setProperty('UID', $uid);
        
        $this->setMakeDate($mkdate);
        $this->setDayEvent($new_event->isDayEvent());
        $this->chng_flag = TRUE;
    }
        
}

?>
