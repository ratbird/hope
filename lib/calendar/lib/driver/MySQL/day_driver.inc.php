<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

function day_save (&$events_save, &$events_delete) {
    $db = new DB_Seminar();
    if (sizeof($this->events)) {
        $query = "REPLACE calendar_events (event_id,range_id,autor_id,uid,summary,description,"
                . "start,end,class,categories,priority,location,ts,linterval,sinterval,wdays,"
                        . "month,day,rtype,duration,count,expire,exceptions,mkdate,chdate) VALUES";
        
        $sep = FALSE;
        
        $chdate = time();
        if (!$event->getMakeDate())
            $mkdate = $chdate;
        else
            $mkdate = $event->getMakeDate();
        
        foreach ($events_save as $event) {
            $properties = $event->getProperty();
            if ($sep1)
                $values .= ",";
            $values .= sprintf("('%s','%s','%s','%s','%s','%s',%s,%s,'%s','%s',%s,%s,%s,'%s',%s,%s,%s,
                    '%s',%s,%s,'%s',%s,%s,'%s',%s,%s)",
                    $event->getId(), $event->getUserId(), $event->getUserId(),
                    $properties['UID'],
                    $properties['SUMMARY'],
                    $properties['DESCRIPTION'],
                    $properties['DTSTART'],
                    $properties['DTEND'],
                    $properties['CLASS'],
                    $properties['CATEGORIES'],
                    $properties['STUDIP_CATEGORY'],
                    $properties['PRIORITY'],
                    $properties['LOCATION'],
                    $properties['RRULE']['ts'],
                    $properties['RRULE']['linterval'],
                    $properties['RRULE']['sinterval'],
                    $properties['RRULE']['wdays'],
                    $properties['RRULE']['month'],
                    $properties['RRULE']['day'],
                    $properties['RRULE']['rtype'],
                    $properties['RRULE']['duration'],
                    $properties['RRULE']['count'],
                    $properties['RRULE']['expire'],
                    $properties['EXDATE'],
                    $mkdate, $chdate);
            $sep = TRUE;
        }
        
        if ($values) {
            $query .= $values;
            $db->query($query);
        }
        
    }
    if (sizeof($events_delete)) {
        $query = "DELETE FROM calendar_events WHERE autor_id = '{$user->id}' AND event_id IN (";
        $sep = FALSE;
        foreach ($events_delete as $event) {
            if ($sep)
                $values .= ",";
            $values .= "'" . $event->getId() . "'";
        }
        $query .= $values . ")";
        $db->query($query);
    }
}

function day_restore (&$ttthis) {
    
    $db = new DB_Seminar;
    // die Abfrage grenzt das Trefferset weitgehend ein
    $query = sprintf("SELECT * FROM calendar_events WHERE range_id='%s' AND((start BETWEEN %s AND %s "
                    . "OR end BETWEEN %s AND %s) OR (%s BETWEEN start AND end) OR (start <= %s AND expire > %s "
                    . "AND (rtype = 'DAILY' OR (rtype = 'WEEKLY' AND wdays LIKE '%%%s%%') OR (rtype = 'MONTHLY' "
                    . "AND (wdays LIKE '%%%s%%' OR day = %s)) OR (rtype = 'YEARLY' AND (month = %s AND (day = %s "
                    . "OR wdays LIKE '%%%s%%'))) OR duration > 1)))",
                    $ttthis->getUserId(), $ttthis->getStart(), $ttthis->getEnd(), $ttthis->getStart(), $ttthis->getEnd(),
                    $ttthis->getStart(), $ttthis->getEnd(), $ttthis->getStart(), $ttthis->dow, $ttthis->dow, $ttthis->dom,
                    $ttthis->mon, $ttthis->dom, $ttthis->dow);
    
    $db->query($query);
    
    while ($db->next_record()) {
        // if the date of this day is in the exceptions take the next event
        if (in_array($ttthis->ts, explode(',', $db->f('exceptions'))))
            continue;
        
        $rep = array(
                "ts"        => $db->f("ts"),
                "linterval" => $db->f("linterval"),
                "sinterval" => $db->f("sinterval"),
                "wdays"     => $db->f("wdays"),
                "month"     => $db->f("month"),
                "day"       => $db->f("day"),
                "rtype"     => $db->f("rtype"),
                "duration"  => $db->f("duration"));
        
        // der "Ursprungstermin"
        if ($db->f("start") >= $ttthis->getStart() && $db->f("end") <= $ttthis->getEnd()) {
            createEvent($ttthis, $db, 0);
        }
        elseif ($db->f("start") >= $ttthis->getStart() && $db->f("start") <= $ttthis->getEnd()) {
            createEvent($ttthis, $db, 1);
        }
        elseif ($db->f("start") < $ttthis->getStart() && $db->f("end") > $ttthis->getEnd()) {
            createEvent($ttthis, $db, 2);
        }
        elseif ($db->f("end") >= $ttthis->getStart() && $db->f("end") <= $ttthis->getEnd()) {
            createEvent($ttthis, $db, 3);
        }
        else {
            
            switch ($rep["rtype"]) {
                case "DAILY":
                    
        /*  
                    if($rep["linterval"] == 1){
                        createEvent($ttthis, $db, 0);
                        break;
                    }*/
                    
                    $pos = (($ttthis->ts - $rep["ts"]) / 86400) % $rep["linterval"];
                    
                    if ($pos == 0) {
                        if ($rep["duration"] > 1)
                            createEvent($ttthis, $db, 1);
                        else
                            createEvent($ttthis, $db, 0);
                        break;
                    }
                    
                    if ($pos < $rep["duration"]) {
                        if (($pos == $rep["duration"] - 1) || ($rep["duration"] - $rep["linterval"] - 1 == $pos))
                            createEvent($ttthis, $db, 3);
                        else
                            createEvent($ttthis, $db, 2);
                    }
                    break;
                    
                case "WEEKLY":
                    if ($rep["duration"] == 1) {
                        // berechne den Montag in dieser Woche...
                        $adate = $ttthis->ts - ($ttthis->dow - 1) * 86400;
                        if(ceil(($adate - $rep["ts"]) / 604800) % $rep["linterval"] == 0){
                            createEvent($ttthis, $db, 0);
                            break;
                        }
                    }
                    else {
                        $adate = $ttthis->ts - ($ttthis->dow - 1) * 86400;
                        if ($adate + 1 > $rep["ts"] - ($ttthis->dow) * 86400) {
                            for ($i = 0;$i < strlen($rep["wdays"]);$i++) {
                                $pos = (($adate - $rep["ts"]) / 86400 - $rep["wdays"][$i] + $ttthis->dow) % ($rep["linterval"] * 7);
                                if ($pos == 0) {
                                    createEvent($ttthis, $db, 1);
                                    break;
                                }
                                if ($pos < $rep["duration"]) {
                                    if($pos == $rep["duration"] - 1)
                                        createEvent($ttthis, $db, 3);
                                    else
                                        createEvent($ttthis, $db, 2);
                                }
                            }
                        }
                    }
                    break;
                case "MONTHLY":
                    if ($rep["duration"] == 1) {
                        // liegt dieser Tag nach der ersten Wiederholung und gehört der Monat zur Wiederholungsreihe?
                        if ($rep["ts"] < $ttthis->ts + 1 && abs(date("n", $rep["ts"]) - $ttthis->mon) % $rep["linterval"] == 0) {
                            // es ist ein Termin am X. Tag des Monats, den hat die Datenbankabfrage schon richtig erkannt
                            if (!$rep["sinterval"]) {
                                createEvent($ttthis, $db, 0);
                                break;
                            }
                            // Termine an einem bestimmten Wochentag in der X. Woche
                            if (ceil($ttthis->dom / 7) == $rep["sinterval"]) {
                                createEvent($ttthis, $db, 0);
                                break;
                            }
                            if ($rep["sinterval"] == 5 && (($ttthis->dom / 7) > 3))
                                createEvent($ttthis, $db, 0);
                        }
                    }
                    else {
                        $amonth = ($rep["linterval"] - ((($ttthis->year - date("Y",$rep["ts"])) * 12) - (date("n",$rep["ts"]))) % $rep["linterval"]) % $rep["linterval"];
                        if ($rep["day"]) {
                            $lwst = mktime(12, 0, 0, $amonth, $rep["day"], $ttthis->year, 0);
                            $hgst = $lwst + ($rep["duration"] - 1) * 86400;
                            if ($ttthis->ts == $lwst) {
                                createEvent($ttthis, $db, 1);
                                break;
                            }
                    
                            if ($ttthis->ts > $lwst && $ttthis->ts < $hgst) {
                                createEvent($ttthis, $db, 2);
                                break;
                            }
                    
                            if ($ttthis->ts == $hgst) {
                                createEvent($ttthis, $db, 3);
                                break;
                            }
                            
                            $lwst = mktime(12, 0, 0, $amonth - $rep["linterval"], $rep["day"], $ttthis->year, 0);
                            $hgst = $lwst + $rep["duration"] * 86400;
                            
                            if ($ttthis->ts == $lwst) {
                                createEvent($ttthis, $db, 1);
                                break;
                            }
                    
                            if ($ttthis->ts > $lwst && $ttthis->ts < $hgst) {
                                createEvent($ttthis, $db, 2);
                                break;
                            }
                    
                            if ($ttthis->ts == $hgst) {
                                createEvent($ttthis, $db, 3);
                                break;
                            }
                            
                        }
                        if ($rep["sinterval"]) {
                        
                            if ($rep["sinterval"] == 5)
                                $cor = 0;
                            else
                                $cor = 1;
                            
                            $lwst = mktime(12, 0 , 0, $amonth, 1, $ttthis->year, 0) + ($rep["sinterval"] - $cor) * 604800;
                            $aday = strftime("%u", $lwst);
                            $lwst -= ($aday - $rep["wdays"]) * 86400;
                            if ($rep["sinterval"] == 5) {
                                if(date("j", $lwst) < 10)
                                    $lwst -= 604800;
                                if (date("n", $lwst) == date("n", $lwst + 604800))
                                    $lwst += 604800;
                            }
                            else {
                                if($aday > $rep["wdays"])
                                    $lwst += 604800;
                            }
                            
                            $hgst = $lwst + ($rep["duration"] - 1) * 86400;
                            
                            if ($ttthis->ts == $lwst) {
                                createEvent($ttthis, $db, 1);
                                break;
                            }
                            
                            if ($ttthis->ts > $lwst && $ttthis->ts < $hgst) {
                                createEvent($ttthis, $db, 2);
                                break;
                            }
                            
                            if ($ttthis->ts == $hgst) {
                                createEvent($ttthis, $db, 3);
                                break;
                            }
                            
                            $lwst = mktime(12, 0, 0, $amonth - $rep["linterval"], 1, $ttthis->year, 0) + ($rep["sinterval"] - $cor) * 604800;;
                            $aday = strftime("%u", $lwst);
                            $lwst -= ($aday - $rep["wdays"]) * 86400;
                            if ($rep["sinterval"] == 5) {
                                if (date("j", $lwst) < 10)
                                    $lwst -= 604800;
                                if (date("n", $lwst) == date("n", $lwst + 604800))
                                    $lwst += 604800;
                            }
                            else {
                                if ($aday > $rep["wdays"])
                                    $lwst += 604800;
                            }
                            
                            $hgst = $lwst + $rep["duration"] * 86400;
                            $lwst += 86400;
                            
                            if ($ttthis->ts == $lwst) {
                                createEvent($ttthis, $db, 1);
                                break;
                            }
                            
                            if ($ttthis->ts > $lwst && $ttthis->ts < $hgst) {
                                createEvent($ttthis, $db, 2);
                                break;
                            }
                            
                            if($ttthis->ts == $hgst){
                                createEvent($ttthis, $db, 3);
                                break;
                            }
                        }
                        
                    }
                        
                    break;
                case "YEARLY":
                
                    if ($rep["duration"] == 1) {
                        if ($rep["ts"] > $ttthis->getStart() && $rep["ts"] < $ttthis->getEnd()) {
                            createEvent($ttthis, $db, 0);
                            break;
                        }
                            
                        // liegt der Wiederholungstermin überhaupt in diesem Jahr?
                        if ($ttthis->year == date("Y", $rep["ts"]) || ($ttthis->year - date("Y", $rep["ts"])) % $rep["linterval"] == 0) {
                            // siehe "MONTHLY"
                            if (!$rep["sinterval"]) {
                                createEvent($ttthis, $db, 0);
                                break;
                            }
                            if (ceil($ttthis->dom / 7) == $rep["sinterval"]) {
                                createEvent($ttthis, $db, 0);
                                break;
                            }
                            if ($rep["sinterval"] == 5 && (($ttthis->dom / 7) > 3)) {
                                createEvent($ttthis, $db, 0);
                                break;
                            }
                        }
                    }
                    else {
                    
                        // der erste Wiederholungstermin
                        $lwst = $rep["ts"];
                        $hgst = $rep["ts"] + $rep["duration"] * 86400;
                        if ($lwst == $ttthis->ts) {
                            createEvent($ttthis, $db, 1);
                            break;
                        }
                        
                        if ($ttthis->ts > $lwst && $ttthis->ts < $hgst) {
                            createEvent($ttthis, $db, 2);
                            break;
                        }
                    
                        if ($ttthis->ts == $hgst) {
                            createEvent($ttthis, $db, 3);
                            break;
                        }
                        
                        if ($rep["day"]) {
                            $lwst = mktime(12,0,0,$rep["month"],$rep["day"],$ttthis->year,0);
                            $hgst = $lwst + ($rep["duration"] - 1) * 86400;
                            if ($ttthis->ts == $lwst) {
                                createEvent($ttthis, $db, 1);
                                break;
                            }
                    
                            if ($ttthis->ts > $lwst && $ttthis->ts < $hgst) {
                                createEvent($ttthis, $db, 2);
                                break;
                            }
                    
                            if ($ttthis->ts == $hgst) {
                                createEvent($ttthis, $db, 3);
                                break;
                            }
                            
                            $lwst = mktime(12, 0, 0, $rep["month"], $rep["day"] - 1, $ttthis->year - 1, 0);
                            $hgst = $lwst + $rep["duration"] * 86400;
                            
                            if ($ttthis->ts == $lwst) {
                                createEvent($ttthis, $db, 1);
                                break;
                            }
                    
                            if ($ttthis->ts > $lwst && $ttthis->ts < $hgst) {
                                createEvent($ttthis, $db, 2);
                                break;
                            }
                    
                            if ($ttthis->ts == $hgst) {
                                createEvent($ttthis, $db, 3);
                                break;
                            }
                            
                        }
                        
                        if ($rep["sinterval"]) {
                            $lwst = mktime(12, 0, 0, $rep["month"], 1, $ttthis->year, 0) + ($rep["sinterval"] - $cor) * 604800;
                            $aday = strftime("%u",$lwst);
                            $lwst -= ($aday - $rep["wdays"]) * 86400;
                            if ($rep["sinterval"] == 5) {
                                if (date("j",$lwst) < 10)
                                    $lwst -= 604800;
                                if (date("n", $lwst) == date("n", $lwst + 604800))
                                    $lwst += 604800;
                            }
                            else
                                if ($aday > $rep["wdays"])
                                    $lwst += 604800;
                    
                            $hgst = $lwst + ($rep["duration"] - 1) * 86400;
                    
                            if ($ttthis->ts == $lwst) {
                                createEvent($ttthis, $db, 1);
                                break;
                            }
                            
                            if ($ttthis->ts > $lwst && $ttthis->ts < $hgst) {
                                createEvent($ttthis, $db, 2);
                                break;
                            }
                            
                            if ($ttthis->ts == $hgst) {
                                createEvent($ttthis, $db, 3);
                                break;
                            }
                            
                            $lwst = mktime(12, 0, 0, $rep["$month"], 1, $ttthis->year - 1, 0) + ($rep["sinterval"] - $cor) * 604800;
                            $aday = strftime("%u", $lwst);
                            $lwst -= ($aday - $rep["wdays"]) * 86400;
                            if ($rep["sinterval"] == 5) {
                                if (date("j", $lwst) < 10)
                                    $lwst -= 604800;
                                if (date("n", $lwst) == date("n", $lwst + 604800))
                                    $lwst += 604800;
                            }
                            else {
                                if ($aday > $rep["wdays"])
                                    $lwst += 604800;
                            }
                            
                            $hgst = $lwst + $rep["duration"] * 86400;
                            $lwst += 86400;
                            
                            if ($ttthis->ts == $lwst) {
                                createEvent($ttthis, $db, 1);
                                break;
                            }
                            
                            if ($ttthis->ts > $lwst && $ttthis->ts < $hgst) {
                                createEvent($ttthis, $db, 2);
                                break;
                            }
                            
                            if ($ttthis->ts == $hgst) {
                                createEvent($ttthis, $db, 3);
                                break;
                            }
                            
                        }
                    }
            }
        }
    }
}
    
    function createEvent (&$ttthis, &$db, $time_range) {
        switch ($time_range) {
            case 0: // Einzeltermin
                $start = mktime(date('G', $db->f('start')), date('i', $db->f('start')),
                        date('s', $db->f('start')), $ttthis->mon, $ttthis->dom, $ttthis->year);
                $end = mktime(date('G', $db->f('end')), date('i', $db->f('end')),
                        date('s', $db->f('end')), $ttthis->mon, $ttthis->dom, $ttthis->year);
                break;
            case 1: // Start
                $start = mktime(date('G', $db->f('start')), date('i', $db->f('start')),
                        date('s', $db->f('start')), $ttthis->mon, $ttthis->dom, $ttthis->year);
                $end = $start + $db->f('end') - $db->f('start');
                break;
            case 2: // Mitte
                $start = $ttthis->getStart() - $ttthis->getStart() + $db->f('start');
                $end = $start + $db->f('end') - $db->f('start');
                break;
            case 3: // Ende
                $end = mktime(date('G', $db->f('end')), date('i', $db->f('end')),
                        date('s', $db->f('end')), $ttthis->mon, $ttthis->dom, $ttthis->year);
                $start = $end - $db->f('end') + $db->f('start');
        }
        $termin = new CalendarEvent(array(
                'DTSTART'         => $start,
                'DTEND'           => $end,
                'SUMMARY'         => $db->f('summary'),
                'DESCRIPTION'     => $db->f('description'),
                'CLASS'           => $db->f('class'),
                'PRIORITY'        => $db->f('prority'),
                'LOCATION'        => $db->f('location'),
                'CATEGORIES'      => $db->f('categories'),
                'STUDIP_CATEGORY' => $db->f('category_intern'),
                'UID'             => $db->f('uid'),
                'EXDATE'          => $db->f('exceptions'),
                'RRULE'           => array(
                        'ts'          => $db->f('ts'),
                        'linterval'   => $db->f('linterval'),
                        'sinterval'   => $db->f('sinterval'),
                        'wdays'       => $db->f('wdays'),
                        'month'       => $db->f('month'),
                        'day'         => $db->f('day'),
                        'rtype'       => $db->f('rtype'),
                        'duration'    => $db->f('duration'),
                        'count'       => $db->f('count'),
                        'expire'      => $db->f('expire'))),
                $db->f('event_id'), $db->f('mkdate'), $db->f('chdate'));
        $ttthis->events[] = $termin;
    }
    

?>
