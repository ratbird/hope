<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

function year_restore (&$ttthis) {
    $db = new DB_Seminar();
    $end = $ttthis->getEnd();
    $start = $ttthis->getStart();

    $query = sprintf("SELECT * FROM calendar_events "
                 . "WHERE range_id='%s' AND (start BETWEEN %s AND %s"
                 . " OR (start <= %s AND expire > %s AND rtype != 'SINGLE') OR (%s BETWEEN start AND end))"
                 . " ORDER BY start ASC"
                 , $ttthis->user_id, $start, $end, $end, $start, $start);
    $db->query($query);
    
    $year = $ttthis->year;
    $month = 1;
    
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
        
        $duration = $rep['duration'];
        
        switch($rep['rtype']){
            // Einzeltermin
            case 'SINGLE' :
                $adate = $rep['ts'];
                while($duration-- && $adate <= $end){
                    add_event($ttthis, $db, $adate);
                    $adate += 86400;
                }
                break;
            
            // tägliche Wiederholung
            case 'DAILY' :
                if($rep['ts'] < $start){
                    // brauche den ersten Tag nach $start an dem dieser Termin wiederholt wird
                    $adate = $ttthis->ts + ($rep['linterval'] - (($ttthis->ts - $rep['ts']) / 86400) % $rep['linterval']) * 86400;
                    // Wie oft muss ein mehrtägiger Termin eingetragen werden, dessen
                    // Startzeit vor Jahresbeginn liegt?
                    if(($xdate = $adate - ($rep['linterval'] - $duration + 1) * 86400) > $start){
                        $duration_first = ($xdate - $ttthis->ts) / 86400 + 1;
                        $md_date = $ttthis->ts;
                        while($duration_first-- && $md_date <= $end && $md_date <= $db->f('expire')){
                            add_event($ttthis, $db, $md_date);
                            $md_date += 86400;
                        }
                    }
                }
                else
                    $adate = $rep['ts'];
                
                while($duration--){
                    $md_date = $adate;
                    while($md_date <= $db->f('expire') && $md_date <= $end){
                        add_event($ttthis, $db, $md_date);
                        $md_date += 86400 * $rep['linterval'];
                    }
                    $adate += 86400;
                }
                break;
            
            // wöchentliche Wiederholung
            case 'WEEKLY' :
                if($db->f('start') > $start){
                    $adate = mktime(12,0,0,date('n',$db->f('start')),date('j',$db->f('start')),date('Y',$db->f('start')),0);
                    if($rep['ts'] != $adate){
                        $md_date = $adate;
                        $count = $duration;
                        while($count-- && $md_date <= $end && $md_date <= $db->f('expire')){
                            add_event($ttthis, $db, $md_date);
                            $md_date += 86400;
                        }
                    }
                        
                    $aday = strftime('%u', $adate) - 1;
                    for($i = 0;$i < strlen($rep['wdays']);$i++){
                        $awday = (int) substr($rep['wdays'], $i, 1) - 1;
                        if($awday > $aday){
                            $wdate = $adate + ($awday - $aday) * 86400;
                            $count = $duration;
                            while($count--){
                                if($wdate > $end || $wdate > $db->f('expire'))
                                    break 2;
                                add_event($ttthis, $db, $wdate);
                                $wdate += 86400;
                            }
                        }
                    }
                }
                
                if($rep['ts'] < $start){
                    // Brauche Montag der 'angefangenen' Woche
                    $adate = $ttthis->ts - (strftime('%u',$start) - 1) * 86400;
                    $adate += (($rep['linterval'] - (($adate - $rep['ts']) / 604800) % $rep['linterval']) % $rep['linterval']) * 604800;
                }
                else
                    $adate = $rep['ts'];
                    
                while($adate <= $db->f('expire') && $adate <= $end){
                    // Termin kann innerhalb der Woche an verschiedenen Wochentagen wiederholt werden
                    for($i = 0;$i < strlen($rep['wdays']);$i++){
                        $awday = (int) substr($rep['wdays'], $i, 1) - 1;
                        $wdate = $adate + $awday * 86400;
                        $count = $duration;
                        while($count--){
                            if($wdate > $end || $wdate > $db->f('expire'))
                                break 3;
                            add_event($ttthis, $db, $wdate);
                            $wdate += 86400;
                        }
                    }
                    $adate += 604800 * $rep['linterval'];
                }
                break;
            
            // monatliche Wiederholung
            case 'MONTHLY' :
                if($db->f('start') > $start){
                    $adate = mktime(12,0,0,date('n',$db->f('start')),date('j',$db->f('start')),date('Y',$db->f('start')),0);
                    $count = $duration;
                    while($count-- && $adate <= $end && $adate <= $db->f('expire')){
                        add_event($ttthis, $db, $adate);
                        $adate += 86400;
                    }
                }
                
                if($rep['sinterval'] == 5)
                    $cor = 0;
                else
                    $cor = 1;
                
                if($rep['ts'] < $start){
                    // brauche ersten Monat in dem der Termin wiederholt wird
                $amonth = ($rep['linterval'] - ((($year - date('Y',$rep['ts'])) * 12) - date('n',$rep['ts'])) % $rep['linterval']) % $rep['linterval'];
                    // ist Wiederholung am X. Wochentag des X. Monats...
                    if(!$rep['day']){
                        $adate = mktime(12,0,0,$amonth,1,$year,0) + ($rep['sinterval'] - $cor) * 604800;
                        $aday = strftime('%u',$adate);
                        $adate -= ($aday - $rep['wdays']) * 86400;
                        if($rep['sinterval'] == 5){
                            if(date('j',$adate) < 10)
                                $adate -= 604800;
                            if(date('n',$adate) == date('n',$adate + 604800))
                                $adate += 604800;
                        }
                        else{
                            if($aday > $rep['wdays'])
                                $adate += 604800;
                        }
                    }
                    else
                        // oder am X. Tag des Monats ?
                        $adate = mktime(12,0,0,$amonth,$rep['day'],$year,0);
                }
                else{
                    // handelt es sich um 'X. Wochentag des X. Monats' kommt nichts hinzu
                    $adate = $rep['ts'];// + ($rep['day']?($rep['day'] - 1) * 86400:0);
                    $amonth = date('n', $rep['ts']);
                }
                
                // Termine, die die Jahresgrenze überbrücken
                if($duration > 1 && $rep['ts'] < $ttthis->ts){
                    if(!$rep['day']){
                        $xdate = mktime(12,0,0,$amonth - $rep['linterval'],1,$year,0) + ($rep['sinterval'] - $cor) * 604800;
                        $aday = strftime('%u',$xdate);
                        $xdate -= ($aday - $rep['wdays']) * 86400;
                        if($rep['sinterval'] == 5){
                            if(date('j',$xdate) < 10)
                                $xdate -= 604800;
                            if(date('n',$xdate) == date('n',$xdate + 604800))
                                $xdate += 604800;
                        }
                        else
                            if($aday > $rep['wdays'])
                                $xdate += 604800;
                        $xdate += $duration * 86400;
                    }
                    else
                        $xdate = mktime(12,0,0,date('n',$adate) - $rep['linterval'],date('j',$adate) + $duration,date('Y',$adate),0);
                    
                    $xdate++;
                    $md_date = $ttthis->ts;
                    while($md_date < $xdate && $md_date <= $db->f('expire')){
                        add_event($ttthis, $db, $md_date);
                        $md_date += 86400;
                    }
                }
                
                while($adate <= $db->f('expire') && $adate <= $end){
                    $md_date = $adate;
                    $count = $duration;
                    while($count--){
                        // verhindert die Anzeige an Tagen, die außerhalb des Monats liegen (am 29. bis 31.)
                        if(!$rep['wdays'] ? date('j', $adate) == $rep['day'] : TRUE
                            && $md_date <= $db->f('expire') && $md_date <= $end)
                                add_event($ttthis, $db, $md_date);
                        $md_date += 86400;
                    }
                    $amonth += $rep['linterval'];
                    // wenn Termin am X. Wochentag des X. Monats, dann Berechnung hier wiederholen
                    if(!$rep['day']){
                        $adate = mktime(12,0,0,$amonth,1,$year,0) + ($rep['sinterval'] - $cor) * 604800;
                        $aday = strftime('%u',$adate);
                        $adate -= ($aday - $rep['wdays']) * 86400;
                        if($rep['sinterval'] == 5){
                            if(date('j',$adate) < 10)
                                $adate -= 604800;
                            if(date('n',$adate) == date('n',$adate + 604800))
                                $adate += 604800;
                        }
                        else
                            if($aday > $rep['wdays'])
                                $adate += 604800;
                    }
                    else
                        $adate = mktime(12,0,0,$amonth,$rep['day'],$year,0);
                }
                break;
            
            // jährliche Wiederholung
            case 'YEARLY' :
                if($db->f('date') > $start -1 && $db->f('start') < $end + 1){
                    $adate = mktime(12,0,0,date('n',$db->f('start')),date('j',$db->f('start')),date('Y',$db->f('start')),0);
                    if($rep['ts'] != $adate){
                        $count = $duration;
                        while($count-- && $adate <= $end && $adate <= $db->f('expire')){
                            add_event($ttthis, $db, $adate);
                            $adate += 86400;
                        }
                    }
                }
                
                if($rep['ts'] > $start -1 && $rep['ts'] < $end + 1){
                    $adate = $rep['ts'];
                    $count = $duration;
                    while($count-- && $adate <= $end && $adate <= $db->f('expire')){
                        add_event($ttthis, $db, $adate);
                        $adate += 86400;
                    }
                }
                
                if($rep['sinterval'] == 5)
                    $cor = 0;
                else
                    $cor = 1;
                
                if($rep['ts'] < $start){
                    if(!$rep['day']){
                        $adate = mktime(12,0,0,$rep['month'],1,$year,0) + ($rep['sinterval'] - $cor) * 604800;
                        $aday = strftime('%u',$adate);
                        $adate -= ($aday - $rep['wdays']) * 86400;
                        if($rep['sinterval'] == 5){
                            if(date('j',$adate) < 10)
                                $adate -= 604800;
                            if(date('n',$adate) == date('n',$adate + 604800))
                                $adate += 604800;
                        }
                        else
                            if($aday > $rep['wdays'])
                                $adate += 604800;
                    }
                    else
                        $adate = mktime(12,0,0,$rep['month'],$rep['day'],$year,0);
                }
                else
                    $adate = $rep['ts'];
                                
                // Termine, die die Jahresgrenze überbrücken
                if($duration > 1 && $rep['ts'] < $ttthis->ts){
                    if(!$rep['day']){
                        $xdate = mktime(12,0,0,$rep['month'],1,$year - 1,0) + ($rep['sinterval'] - $cor) * 604800;
                        $aday = strftime('%u',$xdate);
                        $xdate -= ($aday - $rep['wdays']) * 86400;
                        if($rep['sinterval'] == 5){
                            if(date('j',$xdate) < 10)
                                $xdate -= 604800;
                            if(date('n',$xdate) == date('n',$xdate + 604800))
                                $xdate += 604800;
                        }
                        else
                            if($aday > $rep['wdays'])
                                $xdate += 604800;
                        $xdate += $duration * 86400;
                    }
                    else
                        $xdate = mktime(12,0,0,date('n',$adate),date('j',$adate) + $duration - 1,date('Y',$adate) - 1,0);
                    
                    $xdate++;
                    $md_date = $ttthis->ts;
                    while($md_date < $xdate && $md_date <= $db->f('expire')){
                        add_event($ttthis, $db, $md_date);
                        $md_date += 86400;
                    }
                }
                
                if($adate > $db->f('start'))
                    while($duration-- && $adate <= $db->f('expire') && $adate <= $end){
                        add_event($ttthis, $db, $adate);
                        $adate += 86400;
                    }
                break;
                
        }
    }
}

function add_event (&$ttthis, &$db, $date) {
    // if this date is in the exceptions return FALSE
    if (in_array($date, explode(',', $db->f('exceptions'))))
        return FALSE;
    
    $ttthis->appdays["$date"]++;
    
    return TRUE;
}

?>
