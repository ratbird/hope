<?
// Wrapper class for driver functions in calendar/lib/driver/

require_once($RELATIVE_PATH_CALENDAR . '/lib/CalendarEvent.class.php');

class DbCalendarEvent extends CalendarEvent {
	
	var $driver;
	
	function DbCalendarEvent (&$calendar, $event_id = '', $properties = NULL) {
		$this->driver =& CalendarDriver::getInstance($calendar->getUserId(),
				$calendar->getPermission());
		if ($event_id != '' && is_null($properties)) {
			$this->restore($event_id);
			parent::CalendarEvent($this->properties, $event_id, $calendar->getUserId(),
					$calendar->getPermission());
		}
		else {
			parent::CalendarEvent($properties, NULL, $calendar->getUserId(),
					$calendar->getPermission());
			$this->chng_flag = TRUE;
		}
		
	}
	
	// Store event in database
	// public
	function save () {
		if (!$this->havePermission(CALENDAR_EVENT_PERM_WRITABLE)) {
			return FALSE;
		}
		
		if ($this->isModified()) {
			$this->setChangeDate();
			return $this->driver->writeObjectsIntoDatabase($this);
		}
	}
	
	// delete event in database
	// public
	function delete () {
		if ($this->havePermission(CALENDAR_EVENT_PERM_WRITABLE)) {
			return $this->driver->deleteObjectsFromDatabase($this);
		}
		
		return FALSE;
	}
	
	// get event out of database
	// public
	function restore ($event_id) {
		
		$this->driver->openDatabaseGetSingleObject($event_id);
		$this->properties = $this->driver->nextProperties();
		$this->id = $event_id;
	}
	
	function update ($new_event) {
		if ($this->havePermission(CALENDAR_EVENT_PERM_WRITABLE)) {
			return FALSE;
		}
		
		$properties = $new_event->getProperty();
		// never update the uid, the make date and the author!
		$uid = $this->getProperty('UID');
		$mkdate = $this->getMakeDate();
		$author = $this->getProperty('STUDIP_AUTHOR_ID');
		foreach ($properties as $name => $value) {
			$this->setProperty($name, $value);
		}
		$this->setProperty('STUDIP_AUTHOR_ID', $author);
		$this->setProperty('UID', $uid);
		$this->setMakeDate($mkdate);
		if ($this->isDayEvent())
			$new_event->setDayEvent();
		$this->chng_flag = TRUE;
		
		return TRUE;
	}
		
}

?>
