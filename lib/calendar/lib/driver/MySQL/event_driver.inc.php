<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

function event_get_description ($id) {
        
    $db = new DB_Seminar;
    $query = sprintf("SELECT event_id, description FROM calendar_events WHERE event_id='%s'"
                . " AND range_id='%s'", $id, $this->getUserId());
    $db->query($query);
    if($db->next_record())
        return $db->f('description');
    return FALSE;
}

function event_save (&$ttthis) {
    // Natuerlich nur Speichern, wenn sich was geaendert hat
    // und es sich um einen persoenlichen Termin handelt
    if($ttthis->isModified()){
        $db = new DB_Seminar();
        
        $query = "REPLACE calendar_events (event_id,range_id,autor_id,uid,start,end,"
                  . "summary,description,class,categories,category_intern,priority,location,ts,linterval,"
                        . "sinterval,wdays,month,day,rtype,duration,count,expire,exceptions,mkdate,chdate) VALUES ";
        
        $query .= sprintf("('%s','%s','%s','%s',%s,%s,'%s','%s','%s','%s',%s,%s,'%s',%s,%s,%s,
                '%s',%s,%s,'%s',%s,%s,%s,'%s',%s,%s)",
                $ttthis->getId(), $ttthis->getUserId(), $ttthis->getUserId(),
                $ttthis->properties['UID'],
                $ttthis->properties['DTSTART'],
                $ttthis->properties['DTEND'],
                $ttthis->properties['SUMMARY'],
                $ttthis->properties['DESCRIPTION'],
                $ttthis->properties['CLASS'],
                $ttthis->properties['CATEGORIES'],
                $ttthis->properties['STUDIP_CATEGORY'],
                $ttthis->properties['PRIORITY'],
                $ttthis->properties['LOCATION'],
                $ttthis->properties['RRULE']['ts'],
                $ttthis->properties['RRULE']['linterval'],
                $ttthis->properties['RRULE']['sinterval'],
                $ttthis->properties['RRULE']['wdays'],
                $ttthis->properties['RRULE']['month'],
                $ttthis->properties['RRULE']['day'],
                $ttthis->properties['RRULE']['rtype'],
                $ttthis->properties['RRULE']['duration'],
                $ttthis->properties['RRULE']['count'],
                $ttthis->properties['RRULE']['expire'],
                $ttthis->properties['EXDATE'],
                $ttthis->getMakeDate(), $ttthis->getChangeDate());
        
        if($db->query($query)){
            $ttthis->chng_flag = FALSE;
            return TRUE;
        }
        return FALSE;
    }
    return FALSE;
}

function event_delete ($event_id, $user_id) {
    $db = new DB_Seminar;
    $query = sprintf("DELETE FROM calendar_events WHERE event_id='%s' AND range_id='%s'", $event_id, $user_id);
    if($db->query($query))
        return TRUE;
    return FALSE;
}

function event_restore ($id, &$ttthis) {
    $db = new DB_Seminar();

    $query = sprintf("SELECT * FROM calendar_events "
                                    . "WHERE range_id='%s' AND event_id='%s'"
                                    , $ttthis->getUserId(), $id);
    $db->query($query);
    
    if ($db->next_record()) {
        $ttthis->setId($id);
        $ttthis->setProperty('UID',             $db->f('uid'));
        $ttthis->setProperty('SUMMARY',         $db->f('summary'));
        $ttthis->setProperty('DTSTART',         $db->f('start'));
        $ttthis->setProperty('CLASS',           $db->f('class'));
        $ttthis->setProperty('DTEND',           $db->f('end'));
        $ttthis->setProperty('CATEGORIES',      $db->f('categories'));
        $ttthis->setProperty('STUDIP_CATEGORY', $db->f('category_intern'));
        $ttthis->setProperty('DESCRIPTION',     $db->f('description'));
        $ttthis->setProperty('PRIORITY',        $db->f('priority'));
        $ttthis->setProperty('LOCATION',        $db->f('location'));
        $ttthis->setProperty('EXDATE',          $db->f('exceptions'));
        $ttthis->setProperty('RRULE', array(
                'ts'        => $db->f('ts'),
                'linterval' => $db->f('linterval'),
                'sinterval' => $db->f('sinterval'),
                'wdays'     => $db->f('wdays'),
                'month'     => $db->f('month'),
                'day'       => $db->f('day'),
                'rtype'     => $db->f('rtype'),
                'duration'  => $db->f('duration'),
                'count'     => $db->f('count'),
                'expire'    => $db->f('expire')));
        $ttthis->setMakeDate($db->f('mkdate'));
        $ttthis->setChangeDate($db->f('chdate'));
        $ttthis->chng_flag = FALSE;
        
        return TRUE;
    }
    return FALSE;
}
