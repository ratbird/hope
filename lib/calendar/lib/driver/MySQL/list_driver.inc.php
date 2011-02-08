<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

function list_restore(&$ttthis){
    $db = new DB_Seminar();
    $end = $ttthis->getEnd();
    $start = $ttthis->getStart();
    $time_offset = (date('G') > 11)? 43200 : 0; // workaround BIEST00065
    
    $query = "SELECT * FROM calendar_events WHERE range_id='" . $ttthis->range_id . "' AND ";
    if (!$ttthis->show_private)
        $query .= "class = 'PUBLIC' AND ";
    $query .= "(((start BETWEEN $start AND $end) OR (end BETWEEN $start AND $end)) "
                    . "OR (start <= $end AND expire > $start AND rtype != 'SINGLE')) "
                    . "ORDER BY start ASC";

    $db->query($query);
    
    $year = date("Y", $start);
    $month = date("n", $start);
    
    while ($db->next_record()) {
        
        $rep = array(
                'ts'        => $db->f('ts'),
                'linterval' => $db->f('linterval'),
                'sinterval' => $db->f('sinterval'),
                'wdays'     => $db->f('wdays'),
                'month'     => $db->f('month'),
                'day'       => $db->f('day'),
                'rtype'     => $db->f('rtype'),
                'duration'  => $db->f('duration'),
                'count'     => $db->f('count'));
        
        $expire = $db->f('expire');
        
        switch ($rep["rtype"]) {
            // Einzeltermin (die hat die Datenbank schon gefiltert)
            case "SINGLE" :
                newListEvent($ttthis, $db, $rep["ts"]);
                break;
            
            // tägliche Wiederholung
            case "DAILY" :
                if ($rep["ts"] < $start) {
                    // brauche den ersten Tag nach $start an dem dieser Termin wiederholt wird
                    $adate = $ttthis->ts + (($rep["linterval"] - (($start - $rep["ts"])
                             / 86400) % $rep["linterval"] - 1) * 86400);
                }
                else
                    $adate = $rep['ts'];
                
                while ($adate <= $expire && $adate <= $end) {
                    newListEvent($ttthis, $db, $adate);
                    $adate += 86400 * $rep["linterval"];
                }
                break;
            
            // wöchentliche Wiederholung
            case "WEEKLY" :
                if ($db->f("start") >= $start) {
                    $adate = mktime(12, 0, 0, date("n",$db->f("start")), date("j",$db->f("start")), date("Y",$db->f("start")), 0);
                    if ($rep["ts"] != $adate)
                        newListEvent($ttthis, $db, $adate);
                    $aday = strftime("%u", $adate) - 1;
                    for ($i = 0; $i < strlen($rep["wdays"]); $i++) {
                        $awday = (int) substr($rep["wdays"], $i, 1) - 1;
                        if ($awday > $aday) {
                            $wdate = $adate + ($awday - $aday) * 86400;
                            if ($wdate > $expire)
                                break 2;
                            newListEvent($ttthis, $db, $wdate);
                        }
                    }
                }
                if ($rep["ts"] < $start) {
                    // Brauche den Montag der angefangenen Woche
                    $adate = $ttthis->ts - (strftime("%u", $ttthis->ts) - 1) * 86400;
                    $adate += (($rep["linterval"] - (($adate - $rep["ts"]) / 604800)
                            % $rep["linterval"]) % $rep["linterval"]) * 604800;
                }
                else
                    $adate = $rep["ts"];
                
                while ($adate <= $expire && $adate <= $end) {
                    // Termin kann innerhalb der Woche an verschiedenen Wochentagen wiederholt werden
                    for ($i = 0; $i < strlen($rep["wdays"]); $i++) {
                        $awday = (int) substr($rep["wdays"], $i, 1) - 1;
                        $wdate = $adate + $awday * 86400;
                        if ($wdate > $end || $wdate > $expire)
                            break 2;
                        if ($wdate + $time_offset < $start)
                            continue;
                        newListEvent($ttthis, $db, $wdate);
                    }
                    $adate += 604800 * $rep["linterval"];
                }
                break;
            
            // monatliche Wiederholung
            case "MONTHLY" :
                if ($db->f("start") > $start) {
                    $adate = mktime(12, 0, 0, date("n", $db->f("start")), date("j", $db->f("start")),
                            date("Y", $db->f("start")), 0);
                    if ($rep["ts"] != $adate)
                        newListEvent($ttthis, $db, $adate);
                }
                
                if ($rep["sinterval"] == 5)
                    $cor = 0;
                else
                    $cor = 1;
                
                if ($rep["ts"] < $end) {
                    // brauche ersten Monat nach $start in dem der Termin wiederholt wird
                    $amonth = $month + (abs($month - date("n", $rep["ts"])) % $rep["linterval"]);
                    // ist Wiederholung am X. Wochentag des X. Monats...
                    if (!$rep["day"]) {
                        $adate = mktime(12,0,0,$amonth,1,$year,0) + ($rep["sinterval"] - $cor) * 604800;
                        $aday = strftime("%u",$adate);
                        $adate -= ($aday - $rep["wdays"]) * 86400;
                        if ($rep["sinterval"] == 5) {
                            if (date("j",$adate) < 10)
                                $adate -= 604800;
                            if (date("n",$adate) == date("n",$adate + 604800))
                                $adate += 604800;
                        }
                        else {
                            if ($aday > $rep["wdays"])
                                $adate += 604800;
                        }
                    }
                    else
                        // oder am X. Tag des Monats ?
                        $adate = mktime(12, 0, 0, $amonth, $rep["day"], $year, 0);
                }
                else{
                    // handelt es sich um "X. Wochentag des X. Monats" kommt nichts hinzu
                    $adate = $rep["ts"] + ($rep["day"] ? ($rep["day"] - 1) * 86400 : 0);
                    $amonth = date("n", $rep["ts"]);
                }
                
                while ($adate <= $expire && $adate <= $end  && $adate + $time_offset >= $start) {
                    // verhindert die Anzeige an Tagen, die außerhalb des Monats liegen (am 29. bis 31.)
                    if (!$rep["wdays"] ? date("j", $adate) == $rep["day"] : TRUE)
                        newListEvent($ttthis, $db, $adate);
                    
                    $amonth += $rep["linterval"];
                    // wenn Termin am X. Wochentag des X. Monats, dann Berechnung hier wiederholen
                    if (!$rep["day"]) {
                        $adate = mktime(12, 0, 0, $amonth, 1, $year, 0) + ($rep["sinterval"] - 1) * 604800;
                        $aday = strftime("%u",$adate);
                        $adate -= ($aday - $rep["wdays"]) * 86400;
                        if ($rep["sinterval"] == 5) {
                            if (date("j",$adate) < 10)
                                $adate -= 604800;
                            if (date("n",$adate) == date("n", $adate + 604800))
                                $adate += 604800;
                        }
                        else {
                            if ($aday > $rep["wdays"])
                                $adate += 604800;
                        }
                    }
                    else
                        $adate = mktime(12, 0, 0, $amonth, $rep["day"], $year, 0);
                }
                break;
            
            // jährliche Wiederholung
            case "YEARLY" :
                if ($db->f("start") > $start) {
                    $wdate = mktime(12, 0, 0, date("n", $db->f("start")), date("j", $db->f("start")),
                            date("Y", $db->f("start")), 0);
                    if ($rep["ts"] != $wdate)
                        newListEvent($ttthis, $db, $wdate);
                }
                
                if ($rep["sinterval"] == 5)
                    $cor = 0;
                else
                    $cor = 1;
                
                if ($rep["ts"] < $start) {
                    if (!$rep["day"]) {
                        $adate = mktime(12, 0, 0, $rep["month"], 1, $year, 0)
                                + ($rep["sinterval"] - $cor) * 604800;
                        $aday = strftime("%u", $adate);
                        $adate -= ($aday - $rep["wdays"]) * 86400;
                        if ($rep["sinterval"] == 5) {
                            if (date("j", $adate) < 10)
                                $adate -= 604800;
                        }
                        else
                            if ($aday > $rep["wdays"])
                                $adate += 604800;
                    }
                    else
                        $adate = mktime(12, 0, 0, $rep["month"], $rep["day"], $year, 0);
                }
                else
                    $adate = $rep["ts"];
                
                if ($rep['duration'] > 1) {
                    if (!$rep["day"]) {
                        $xdate = mktime(12, 0, 0, $rep["month"], 1, $year - 1, 0)
                                + ($rep["sinterval"] - $cor) * 604800;
                        $aday = strftime("%u", $xdate);
                        $xdate -= ($aday - $rep["wdays"]) * 86400;
                        if ($rep["sinterval"] == 5) {
                            if (date("j",$xdate) < 10)
                                $xdate -= 604800;
                        }
                        else
                            if ($aday > $rep["wdays"])
                                $xdate += 604800;
                    }
                    else {
                        $xdate = mktime(12, 0, 0, date("n", $adate), date("j", $adate), date("Y", $adate) - 1, 0)
                                        + ($rep['duration'] - 1) * 86400;
                    }
                    if ($xdate <= $end && $xdate + $time_offset >= $start && $xdate <= $expire)
                        newListEvent($ttthis, $db, $xdate);
                }
                
                if ($adate <= $end && $adate + $time_offset >= $start && $adate <= $expire)
                    newListEvent($ttthis, $db, $adate);
                break;
        }
    }
}

function newListEvent (&$ttthis, $db, $date) {
    // if this date is in the exceptions return FALSE
    if (in_array($date, explode(',', $db->f('exceptions'))))
        return FALSE;
    
    $date = mktime(date("G", $db->f("start")), date("i", $db->f("start")), 0,
            date("n", $date), date("j", $date), date("Y", $date));
    
    // BIEST00065
    if ($date < $ttthis->start) {
        return FALSE;
    }
    
    $event = new CalendarEvent(array(
            'DTSTART'         => $date,
            'DTEND'           => $db->f('end') - $db->f('start') + $date,
            'SUMMARY'         => $db->f('summary'),
            'DESCRIPTION'     => $db->f('description'),
            'PRIORITY'        => $db->f('priority'),
            'CLASS'           => $db->f('class'),
            'LOCATION'        => $db->f('location'),
            'CATEGORIES'      => $db->f('categories'),
            'STUDIP_CATEGORY' => $db->f('category_intern'),
            'UID'             => $db->f('uid'),
            'CREATED'         => $db->f('mkdate'),
            'LAST-MODIFIED'   => $db->f('chdate'),
            'EXDATE'          => $db->f('exceptions'),
            'RRULE'           => array(
                 'ts'           => $db->f('ts'),
                 'linterval'    => $db->f('linterval'),
                 'sinterval'    => $db->f('sinterval'),
                 'wdays'        => $db->f('wdays'),
                 'month'        => $db->f('month'),
                 'day'          => $db->f('day'),
                 'rtype'        => $db->f('rtype'),
                 'duration'     => $db->f('duration'),
                 'count'        => $db->f('count'),
                 'expire'       => $db->f('expire'))),
            $db->f('event_id'));
    
    $ttthis->events[] = $event;
    
    return TRUE;
}
    
?>
