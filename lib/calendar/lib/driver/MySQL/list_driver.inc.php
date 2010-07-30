<?

function list_restore(&$this){

	$this->driver->openDatabaseGetView($this);
	
	$year = date("Y", $this->getStart());
	$month = date("n", $this->getStart());
	
	while ($this->driver->nextProperties()) {
		
		$rep = $properties['RRULE'];
		
		$expire = $rep['expire'];
		
		switch ($rep['rtype']) {
			// Einzeltermin (die hat die Datenbank schon gefiltert)
			case 'SINGLE' :
				$this->createEvent($properties, $rep['ts']);
				break;
			
			// tägliche Wiederholung
			case 'DAILY' :
				if ($rep['ts'] < $start) {
					// brauche den ersten Tag nach $start an dem dieser Termin wiederholt wird
					$adate = $this->ts + (($rep['linterval'] - (($start - $rep['ts'])
							 / 86400) % $rep['linterval'] - 1) * 86400);
				}
				else
					$adate = $rep['ts'];
				
				while ($adate <= $expire && $adate <= $end) {
					$this->createEvent($properties, $adate);
					$adate += 86400 * $rep['linterval'];
				}
				break;
			
			// wöchentliche Wiederholung
			case 'WEEKLY' :
				if ($properties['DTSTART'] >= $start) {
					$adate = mktime(12, 0, 0, date('n', $properties['DTSTART']),
							date('j', $properties['DTSTART']), date('Y',$properties['DTSTART']), 0);
					if ($rep['ts'] != $adate)
						$this->createEvent($properties, $adate);
						
					$aday = strftime('%u', $adate) - 1;
					for ($i = 0; $i < strlen($rep['wdays']); $i++) {
						$awday = (int) substr($rep['wdays'], $i, 1) - 1;
						if ($awday > $aday) {
							$wdate = $adate + ($awday - $aday) * 86400;
							if ($wdate > $expire)
								break 2;
							$this->createEvent($properties, $wdate);
						}
					}
				}
				if ($rep['ts'] < $start) {
					// Brauche den Montag der angefangenen Woche
					$adate = $this->ts - (strftime('%u', $this->ts) - 1) * 86400;
					$adate += (($rep['linterval'] - (($adate - $rep['ts']) / 604800)
							% $rep['linterval']) % $rep['linterval']) * 604800;
				}
				else
					$adate = $rep['ts'];
				
				while ($adate <= $expire && $adate <= $end) {
					// Termin kann innerhalb der Woche an verschiedenen Wochentagen wiederholt werden
					for ($i = 0; $i < strlen($rep['wdays']); $i++) {
						$awday = (int) substr($rep['wdays'], $i, 1) - 1;
						$wdate = $adate + $awday * 86400;
						if ($wdate > $end || $wdate > $expire)
							break 2;
						if ($wdate < $start)
							continue;
						$this->createEvent($properties, $wdate);
					}
					$adate += 604800 * $rep['linterval'];
				}
				break;
			
			// monatliche Wiederholung
			case 'MONTHLY' :
				if ($properties['DTSTART'] > $start) {
					$adate = mktime(12, 0, 0, date('n', $properties['DTSTART']),
							date('j', $properties['DTSTART']), date('Y', $properties['DTSTART']), 0);
					if ($rep['ts'] != $adate)
						$this->createEvent($properties, $adate);
				}
				
				if ($rep['sinterval'] == 5)
					$cor = 0;
				else
					$cor = 1;
				
				if ($rep['ts'] < $end) {
					// brauche ersten Monat nach $start in dem der Termin wiederholt wird
					$amonth = $month + (abs($month - date('n', $rep['ts'])) % $rep['linterval']);
					// ist Wiederholung am X. Wochentag des X. Monats...
					if (!$rep['day']) {
						$adate = mktime(12,0,0,$amonth,1,$year,0) + ($rep['sinterval'] - $cor) * 604800;
						$aday = strftime('%u',$adate);
						$adate -= ($aday - $rep['wdays']) * 86400;
						if ($rep['sinterval'] == 5) {
							if (date('j',$adate) < 10)
								$adate -= 604800;
							if (date('n',$adate) == date('n',$adate + 604800))
								$adate += 604800;
						}
						else {
							if ($aday > $rep['wdays'])
								$adate += 604800;
						}
					}
					else
						// oder am X. Tag des Monats ?
						$adate = mktime(12, 0, 0, $amonth, $rep['day'], $year, 0);
				}
				else{
					// handelt es sich um 'X. Wochentag des X. Monats' kommt nichts hinzu
					$adate = $rep['ts'] + ($rep['day'] ? ($rep['day'] - 1) * 86400 : 0);
					$amonth = date('n', $rep['ts']);
				}
					
				while ($adate <= $expire && $adate <= $end && $adate >= $start) {
					// verhindert die Anzeige an Tagen, die außerhalb des Monats liegen (am 29. bis 31.)
					if (!$rep['wdays'] ? date('j', $adate) == $rep['day'] : TRUE)
						$this->createEvent($properties, $adate);
					
					$amonth += $rep['linterval'];
					// wenn Termin am X. Wochentag des X. Monats, dann Berechnung hier wiederholen
					if (!$rep['day']) {
						$adate = mktime(12, 0, 0, $amonth, 1, $year, 0) + ($rep['sinterval'] - 1) * 604800;
						$aday = strftime('%u',$adate);
						$adate -= ($aday - $rep['wdays']) * 86400;
						if ($rep['sinterval'] == 5) {
							if (date('j',$adate) < 10)
								$adate -= 604800;
							if (date('n',$adate) == date('n', $adate + 604800))
								$adate += 604800;
						}
						else {
							if ($aday > $rep['wdays'])
								$adate += 604800;
						}
					}
					else
						$adate = mktime(12, 0, 0, $amonth, $rep['day'], $year, 0);
				}
				break;
			
			// jährliche Wiederholung
			case 'YEARLY' :
				if ($properties['DTSTART'] > $start) {
					$wdate = mktime(12, 0, 0, date('n', $properties['DTSTART']),
							date('j', $properties['DTSTART']), date('Y', $properties['DTSTART']), 0);
					if ($rep['ts'] != $wdate)
						$this->createEvent($properties, $wdate);
				}
				
				if ($rep['sinterval'] == 5)
					$cor = 0;
				else
					$cor = 1;
				
				if ($rep['ts'] < $start) {
					if (!$rep['day']) {
						$adate = mktime(12, 0, 0, $rep['month'], 1, $year, 0)
								+ ($rep['sinterval'] - $cor) * 604800;
						$aday = strftime('%u', $adate);
						$adate -= ($aday - $rep['wdays']) * 86400;
						if ($rep['sinterval'] == 5) {
							if (date('j', $adate) < 10)
								$adate -= 604800;
						}
						else
							if ($aday > $rep['wdays'])
								$adate += 604800;
					}
					else
						$adate = mktime(12, 0, 0, $rep['month'], $rep['day'], $year, 0);
				}
				else
					$adate = $rep['ts'];
				
				if ($rep['duration'] > 1) {
					if (!$rep['day']) {
						$xdate = mktime(12, 0, 0, $rep['month'], 1, $year - 1, 0)
								+ ($rep['sinterval'] - $cor) * 604800;
						$aday = strftime('%u', $xdate);
						$xdate -= ($aday - $rep['wdays']) * 86400;
						if ($rep['sinterval'] == 5) {
							if (date('j',$xdate) < 10)
								$xdate -= 604800;
						}
						else
							if ($aday > $rep['wdays'])
								$xdate += 604800;
					}
					else {
						$xdate = mktime(12, 0, 0, date('n', $adate), date('j', $adate), date('Y', $adate) - 1, 0)
										+ ($rep['duration'] - 1) * 86400;
					}
					if ($xdate <= $end && $xdate >= $start && $xdate <= $expire)
						$this->createEvent($properties, $xdate);
				}
				
				if ($adate <= $end && $adate >= $start && $adate <= $expire)
					$this->createEvent($properties, $adate);
				break;
		}
	}
}

function createEvent ($properties, $date) {
	// if this date is in the exceptions return FALSE
	if (in_array($date, explode(',', $properties['EXDATE'])))
		return FALSE;
	
	$date = mktime(date("G", $properties['DTSTART']), date("i", $properties['DTSTART']), 0,
			date("n", $date), date("j", $date), date("Y", $date));
		
	$this->events[] = new CalendarEvent($properties, $properties['STUDIP_ID']);
	
	return TRUE;
}
	
?>
