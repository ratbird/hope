<?
# Lifter002: TODO
# Lifter007: TODO

/**
 * CalendarImport.class.php
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

global $RELATIVE_PATH_CALENDAR;

require_once("$RELATIVE_PATH_CALENDAR/lib/ErrorHandler.class.php");

define('IGNORE_ERRORS', 1);

class CalendarImport {

	var $_parser;
	var $data;
	var $public_to_private = FALSE;
	
	function CalendarImport (&$parser, $data = NULL) {
		
		// initialize error handler
		init_error_handler('_calendar_error');
		
		$this->_parser = $parser;
		$this->data = $data;
	}
	
/*	function setParser (&$parser) {
	
		$this->_parser = $parser;
	}
*/
	function getContent () {
		return $this->data;
	}
	
	function importIntoDatabase ($range_id, $ignore = 'IGNORE_ERRORS') {
		$this->_parser->changePublicToPrivate($this->public_to_private);
		if ($this->_parser->parseIntoDatabase($range_id, $this->getContent(), $ignore))
			return TRUE;
		
		return FALSE;
	}
	
	function importIntoObjects ($ignore = 'IGNORE_ERRORS') {
		$this->_parser->changePublicToPrivate($this->public_to_private);
		if ($this->_parser->parseIntoObjects($this->getContent(), $ignore))
			return TRUE;
		
		return FALSE;
	}
	
	function getObjects () {
		
		return $objects =& $this->_parser->getObjects();
	}
	
	function getCount () {

		return $this->_parser->getCount($this->getContent());
	}
	
	function changePublicToPrivate ($value = TRUE) {
		$this->public_to_private = $value;
	}
	
	function getClientIdentifier () {
		if (!$client_identifier = $this->_parser->getClientIdentifier()) {
			return $this->_parser->getClientIdentifier($this->getContent());
		}
		return $client_identifier;
	}
	
	function setImportSem ($do_import) {
		if ($do_import) {
			$this->_parser->import_sem = TRUE;
		} else {
			$this->_parser->import_sem = FALSE;
		}
	}	
}
