<?
/**
* CalendarSoapServer.class.php
* 
* 
* 
*
* @author		Peter Thienel <thienel@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
* @version	$Id: CalendarSoapServer.class.php,v 1.1 2008/12/23 09:54:04 thienel Exp $
* @access		public
* @modulegroup	calendar_modules
* @module		calendar
* @package	Calendar
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// CalendarSoapServer.class.php
// 
// Copyright (C) 2006 Peter Thienel <thienel@data-quest.de>,
// Suchi & Berg GmbH <info@data-quest.de>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+

require_once("$ABSOLUTE_PATH_STUDIP$RELATIVE_PATH_CALENDAR/lib/Calendar.class.php");
require_once("$ABSOLUTE_PATH_STUDIP$RELATIVE_PATH_CALENDAR/lib/sync/CalendarExport.class.php");
require_once("$ABSOLUTE_PATH_STUDIP$RELATIVE_PATH_CALENDAR/lib/sync/CalendarWriterICalendar.class.php");
require_once("$ABSOLUTE_PATH_STUDIP$RELATIVE_PATH_CALENDAR/lib/sync/CalendarWriterRaw.class.php");
require_once("$ABSOLUTE_PATH_STUDIP$RELATIVE_PATH_CALENDAR/lib/sync/CalendarImport.class.php");
require_once("$ABSOLUTE_PATH_STUDIP$RELATIVE_PATH_CALENDAR/lib/sync/CalendarParserICalendar.class.php");
require_once("$ABSOLUTE_PATH_STUDIP$RELATIVE_PATH_CALENDAR/lib/sync/CalendarParserRaw.class.php");
require_once("$ABSOLUTE_PATH_STUDIP$RELATIVE_PATH_CALENDAR/lib/sync/CalendarSynchronizer.class.php");
require_once("$ABSOLUTE_PATH_STUDIP$RELATIVE_PATH_CALENDAR/lib/sync/CalendarSynchronizerOutlook.class.php");
	
require_once("$ABSOLUTE_PATH_STUDIP/soap/StudipSoapServer.class.php");

class CalendarSoapServer extends StudipSoapServer {
	
	function CalendarSoapServer ($wsdl = TRUE) {
	
		StudipSoapServer::StudipSoapServer($wsdl);
		$this->_registerSessionMethods();
		$this->_registerMethods();
	}
	
	function _defineService () {
		$this->service_namespace = 'urn:http://www.studip.de/calendar/services';
		$this->service_name = 'StudIPCalendarServices';
		$this->services_use = 'encoded';
		$this->services_style = 'rpc';
	}
	
	function _registerMethods () {
		
		// Add useful complex types. E.g. array("a","b") or array(1,2)
		$this->server->wsdl->addComplexType('intArray',
				'complexType',
				'array',
				'',
				'SOAP-ENC:Array',
				array(),
				array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'xsd:int[]')),
				'xsd:int');


		$this->server->wsdl->addComplexType('stringArray',
				'complexType',
				'array',
				'',
				'SOAP-ENC:Array',
				array(),
				array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'xsd:string[]')),
				'xsd:string');
		
		$this->server->wsdl->addComplexType('dateTime',
				'complexType',
				'struct',
				'all',
				'',
				array('year' => array('name' => 'year', 'type' => 'xsd:int'),
						'month' => array('name' => 'month', 'type' => 'xsd:int'),
						'day' => array('name' => 'day', 'type' => 'xsd:int'),
						'hour' => array('name' => 'hour', 'type' => 'xsd:int'),
						'minute' => array('name' => 'minute', 'type' => 'xsd:int'),
						'second' => array('name' => 'second', 'type' => 'xsd:int')));
		
		$this->server->wsdl->addComplexType('dateTimeArray',
				'complexType',
				'array',
				'',
				'SOAP-ENC:Array',
				array(),
				array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:dateTime[]')),
				'tns:dateTime');
		
		$this->server->wsdl->addComplexType('recurrenceRule',
				'complexType',
				'struct',
				'all',
				'',
				array('rtype'         => array('name' => 'rtype', 'type' => 'xsd:string'),
							'linterval'     => array('name' => 'linterval', 'type' => 'xsd:int'),
							'sinterval'     => array('name' => 'sinterval', 'type' => 'xsd:int'),
							'wdays'         => array('name' => 'wdays', 'type' => 'xsd:string'),
							'month'         => array('name' => 'month', 'type' => 'xsd:int'),
							'day'           => array('name' => 'day', 'type' => 'xsd:int'),
							'expire'        => array('name' => 'expire', 'type' => 'tns:dateTime'),
							'count'         => array('name' => 'count', 'type' => 'xsd:int')));
		
		$this->server->wsdl->addComplexType('rawData',
				'complexType',
				'struct',
				'all',
				'',
				array('DTSTART'       => array('name' => 'DTSTART', 'type' => 'tns:dateTime'),
						'DTEND'           => array('name' => 'DTEND', 'type' => 'tns:dateTime'),
						'SUMMARY'         => array('name' => 'SUMMARY', 'type' => 'xsd:string'),
						'DESCRIPTION'     => array('name' => 'DESCRIPTION', 'type' => 'xsd:string'),
						'UID'             => array('name' => 'UID', 'type' => 'xsd:string'),
						'CLASS'           => array('name' => 'CLASS', 'type' => 'xsd:string'),
						'CATEGORIES'      => array('name' => 'CATEGORIES', 'type' => 'tns:stringArray'),
						'PRIORITY'        => array('name' => 'PRIORITY', 'type' => 'xsd:int'),
						'LOCATION'        => array('name' => 'LOCATION', 'type' => 'xsd:string'),
						'RRULE'           => array('name' => 'RRULE', 'type' => 'tns:recurrenceRule'),
						'EXDATE'          => array('name' => 'EXDATE', 'type' => 'tns:dateTimeArray'),
						'CREATED'         => array('name' => 'CREATED', 'type' => 'tns:dateTime'),
						'LASTMODIFIED'    => array('name' => 'LASTMODIFIED', 'type' => 'tns:dateTime'),
						'DTSTAMP'         => array('name' => 'DTSTAMP', 'type' => 'tns:dateTime')));
		
		$this->server->wsdl->addComplexType('rawDataArray',
				'complexType',
				'array',
				'',
				'SOAP-ENC:Array',
				array(),
				array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:rawData[]')),
				'tns:rawData');
		
		$this->server->wsdl->addComplexType('dataResponse',
				'complexType',
				'struct',
				'all',
				'',
				array('success' => array('name' => 'success', 'type' => 'xsd:boolean'),
						'data' => array('name' => 'data', 'type' => 'xsd:string'),
						'messages' => array('name' => 'messages', 'type' => 'tns:stringArray')));
		
		$this->server->wsdl->addComplexType('rawResponse',
				'complexType',
				'struct',
				'all',
				'',
				array('success' => array('name' => 'success', 'type' => 'xsd:boolean'),
						'data' => array('name' => 'data', 'type' => 'tns:rawDataArray'),
						'messages' => array('name' => 'messages', 'type' => 'tns:stringArray')));
		
		$this->server->register('iCalendarExport',
				array('sid' => 'xsd:string',
						'startDate' => 'tns:dateTime',
						'endDate' => 'tns:dateTime',
						'eventTypes' => 'xsd:string'),
				array('export' => 'tns:dataResponse'),
				$this->service_namespace,
				$this->service_namespace.'#synchronize',
				$this->service_style,
				$this->service_use,
				'Stud.IP-Calendar export as iCalendar');
		
		$this->server->register('iCalendarSynchronize',
				array('sid' => 'xsd:string',
						'iCalData' => 'xsd:string'),
				array('synchronized' => 'tns:dataResponse'),
				$this->service_namespace,
				$this->service_namespace.'#synchronize',
				$this->service_style,
				$this->service_use,
				'Stud.IP-Calendar iCalendar synchronise function');
		
		$this->server->register('rawCalendarExport',
				array('sid' => 'xsd:string',
						'startDate' => 'tns:dateTime',
						'endDate' => 'tns:dateTime',
						'eventTypes' => 'xsd:string'),
				array('export' => 'tns:rawResponse'),
				$this->service_namespace,
				$this->service_namespace.'#synchronize',
				$this->service_style,
				$this->service_use,
				'Stud.IP-Calendar export (raw data format)');
		
		$this->server->register('rawCalendarSynchronize',
				array('sid' => 'xsd:string',
						'clientId' => 'xsd:string',
						'rawData' => 'tns:rawDataArray'),
				array('synchronized' => 'tns:rawResponse'),
				$this->service_namespace,
				$this->service_namespace.'#synchronize',
				$this->service_style,
				$this->service_use,
				'Stud.IP-Calendar synchronise function (raw data format)');
		
		$this->server->register('getLastSync',
				array('sid' => 'xsd:string',
						'prodid' => 'xsd:string'),
				array('lastsync' => 'tns:dateTime'),
				$this->service_namespace,
				$this->service_namespace.'#synchronize',
				$this->service_style,
				$this->service_use,
				'Stud.IP-Calendar get last date of synchronisation.');
		
		$this->server->register('setLastSync',
				array('sid' => 'xsd:string',
						'prodid' => 'xsd:string'),
				array('lastsync' => 'xsd:boolean'),
				$this->service_namespace,
				$this->service_namespace.'#synchronize',
				$this->service_style,
				$this->service_use,
				'Stud.IP-Calendar set last date of synchronisation.');
		
		$this->server->register('countEvents',
				array('sid' => 'xsd:string'),
				array('numberOfEvents' => 'xsd:int'),
				$this->service_namespace,
				$this->service_namespace.'#synchronize',
				$this->service_style,
				$this->service_use,
				'Stud.IP-Calendar get number of events');
		
	}
	
}

function iCalendarExport ($sid, $startDate, $endDate, $eventTypes) {
	
	if (!StudipSoapServer::ValidateSession($sid)) {
		exit;
	}
	global $_calendar_error, $user;

	$start = mktime($startDate['hour'], $startDate['minute'], $startDate['second'],
			$startDate['month'], $startDate['day'], $startDate['year']);
	$end = mktime($endDate['hour'], $endDate['minute'], $endDate['second'],
			$endDate['month'], $endDate['day'], $endDate['year']);
	
	$export =& new CalendarExport(new CalendarWriterICalendar());
	$export->exportFromDatabase($user->id, $start, $end, $eventTypes,
			Calendar::GetBindSeminare($user->id));
	
	$ret['data'] = $export->getExport();
	$ret['messages'] = array();
	$ret['success'] = !$_calendar_error->getMaxStatus(ERROR_CRITICAL);
	while ($error = $_calendar_error->nextError(ERROR_MESSAGE)) {
		$ret['messages'][] = $error->getMessage();
	}
	while ($error = $_calendar_error->nextError(ERROR_WARNING)) {
		$ret['messages'][] = $error->getMessage();
	}
	while ($error = $_calendar_error->nextError(ERROR_CRITICAL)) {
		$ret['messages'][] = $error->getMessage();
	}
	
	return $ret;
}

function iCalendarSynchronize ($sid, $iCalData) {
	
	if (!StudipSoapServer::ValidateSession($sid)) {
		exit;
	}
	global $user, $_calendar_error;
	
	$import =& new CalendarImport(new CalendarParserICalendar(), $iCalData);
	$export =& new CalendarExport(new CalendarWriterICalendar());
	$synchronizer =& new CalendarSynchronizer($import, $export);
	
//	$synchronizer->setMaxEvents($CALENDAR_MAX_EVENTS - $count_events);
	$synchronizer->synchronize($user->id);
	
	$ret['data'] = $export->getExport();
	$ret['messages'] = array();
	$ret['success'] = !$_calendar_error->getMaxStatus(ERROR_CRITICAL);
	while ($error = $_calendar_error->nextError(ERROR_MESSAGE)) {
		$ret['messages'][] = $error->getMessage();
	}
	while ($error = $_calendar_error->nextError(ERROR_WARNING)) {
		$ret['messages'][] = $error->getMessage();
	}
	while ($error = $_calendar_error->nextError(ERROR_CRITICAL)) {
		$ret['messages'][] = $error->getMessage();
	}
	
	return $ret;
}

function rawCalendarExport ($sid, $username, $startDate, $endDate, $eventTypes) {
	
	if (!StudipSoapServer::ValidateSession($sid)) {
		exit;
	}
	global $_calendar_error, $user;

	$start = mktime($startDate['hour'], $startDate['minute'], $startDate['second'],
			$startDate['month'], $startDate['day'], $startDate['year']);
	$end = mktime($endDate['hour'], $endDate['minute'], $endDate['second'],
			$endDate['month'], $endDate['day'], $endDate['year']);
	
	// The usage of username to export calendars of other users is not yet implemented!
	
	$export =& new CalendarExport(new CalendarWriterRaw());
	$export->exportFromDatabase($user->id, $start, $end, $eventTypes,
			Calendar::GetBindSeminare($user->id));
	echo "<br><br>";
	echo serialize($export->getExport());
	echo "<br><br>";
	$ret['data'] = $export->getExport();
	$ret['messages'] = array();
	$ret['success'] = !$_calendar_error->getMaxStatus(ERROR_CRITICAL);
	while ($error = $_calendar_error->nextError(ERROR_MESSAGE)) {
		$ret['messages'][] = $error->getMessage();
	}
	while ($error = $_calendar_error->nextError(ERROR_WARNING)) {
		$ret['messages'][] = $error->getMessage();
	}
	while ($error = $_calendar_error->nextError(ERROR_CRITICAL)) {
		$ret['messages'][] = $error->getMessage();
	}
	
	return $ret;
}
	
function rawCalendarSynchronize ($sid, $clientId, $rawData) {
	
	if (!StudipSoapServer::ValidateSession($sid)) {
		exit;
	}
	global $_calendar_error, $user, $CALENDAR_MAX_EVENTS;
	
	$import =& new CalendarImport(new CalendarParserRaw($clientId), $rawData);
	$export =& new CalendarExport(new CalendarWriterRaw());
	$synchronizer =& new CalendarSynchronizerOutlook($import, $export);
	
	$db =& CalendarDriver::getInstance($user->id);
	$db->openDatabase('COUNT', 'CALENDAR_EVENTS');
	
	$synchronizer->setMaxEvents($CALENDAR_MAX_EVENTS - $db->getCountEvents());
	$synchronizer->synchronize($user->id);
	
	$ret['data'] = array();
	if ($export->getCount()) {
		$ret['data'] = $export->getExport();
	}
	$ret['messages'] = array();
	$ret['success'] = !$_calendar_error->getMaxStatus(ERROR_CRITICAL);
	while ($error = $_calendar_error->nextError(ERROR_MESSAGE)) {
		$ret['messages'][] = decodeHTML($error->getMessage());
	}
	while ($error = $_calendar_error->nextError(ERROR_WARNING)) {
		$ret['messages'][] = decodeHTML($error->getMessage());
	}
	while ($error = $_calendar_error->nextError(ERROR_CRITICAL)) {
		$ret['messages'][] = decodeHTML($error->getMessage());
	}
	
	return $ret;
}

function countEvents ($sid) {
	
	if (!StudipSoapServer::ValidateSession($sid)) {
		exit;
	}
	global $user, $_calendar_error;
	
	$db =& CalendarDriver::getInstance($user->id);
	$db->openDatabase('COUNT', 'CALENDAR_EVENTS');
	
	return $db->getCountEvents();
}

function getLastSync ($sid, $clientId) {

	if (!StudipSoapServer::ValidateSession($sid)) {
		exit;
	}
	global $user, $_calendar_error;
	
	return toDateTime(CalendarSynchronizer::GetLastSync($clientId, $user->id));
}

function setLastSync ($sid, $clientId) {

	if (!StudipSoapServer::ValidateSession($sid)) {
		exit;
	}
	global $user, $_calendar_error;
	
	return CalendarSynchronizer::SetLastSync($clientId, $user->id);
}

function toDateTime ($timestamp) {
	return array(
			'year' => intval(date('Y', $timestamp)),
			'month' => intval(date('n', $timestamp)),
			'day' => intval(date('j', $timestamp)),
			'hour' => intval(date('G', $timestamp)),
			'minute' => intval(date('i', $timestamp)),
			'second' => intval(date('s', $timestamp)));
}
?>