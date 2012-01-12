<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO
/**
* blockveranstaltungs_assistent.inc.php - Terminverwaltung von Stud.IP
*
* @author       Michael Riehemann <michael.riehemann@uni-oldenburg.de>
* @access       public
* @module       blockveranstaltungs_assistent.inc.php
* @package      studip_core
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// Copyright (C) 2001-2007 Stud.IP
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


/*  Diese funktion bekommt als eingabe die seminar_id und die Daten, die aus der
        Form kommen. Das sind:
                                    'start_day'
                                    'start_month'
                                    'start_year'
                                    'end_day'
                                    'end_month'
                                    'end_year'
                                    'start_hour'
                                    'start_minute'
                                    'end_hour'
                                    'end_minute'
                                    'create_topic'
                                    'create_folder'

        Wenn Verzeichnisse oder Themen im Forum angelegt werden sollen, wird das mit den
        beiden anderen Parametern gesteuert.

        Funktion liefert messages oder errors...
*/

function day_checked($day) 
{
    global $_POST;
    if (isset($_POST['days'])) {
        foreach ($_POST['days'] as $cur_day) {
            if ($cur_day == $day) return ' checked=checked';
        }
    }
    return '';
}

function create_block_schedule_dates($seminar_id, $form_data)
{

    $messages =  Array('seminar_id'=> _("Kein Seminar gewählt!"),
                                     'start_day' => _("Startdatum: Sie haben keinen Tag angegeben."),
                                     'start_month'=> _("Startdatum: Sie haben keinen Monat angegeben."),
                                     'start_year' => _("Startdatum: Sie haben kein Jahr angegeben."),
                                     'end_day' => _("Enddatum: Sie haben keinen Tag angegeben."),
                                     'end_month'=> _("Enddatum: Sie haben keinen Monat angegeben."),
                                     'end_year' => _("Enddatum: Sie haben kein Jahr angegeben."),
                                    'start_hour' => _("Startzeitpunkt: Sie haben keine Stunde angegeben."),
                                    'start_minute'=> _("Startzeitpunkt: Sie haben keine Minuten angegeben.") ,
                                    'end_hour'=> _("Endzeitpunkt: Sie haben keine Stunde angegeben."),
                                    'end_minute'=> _("Endzeitpunkt: Sie haben keine Minuten angegeben."),
                                    'no_days_in_timeslot' => _("Keiner der ausgewählten Tage liegt in dem angegebenen Zeitraum!"),
                                    'art' => _("Geben Sie eine Terminart an!"));

    // do checks
    $k = 0;
    $errors = array();

    foreach($form_data as $key=>$value)
    {
        // check if form was filled
        if(in_array($key, array_keys($messages)))   {
            if($key=='seminar_id' || $key=='end_minute' || $key=='start_minute'){
                if ($value == null) {
                    $errors[] = $messages[$key];
                }
            } else {
                if (!$value||!is_numeric($value) ){
                    $errors[] = $messages[$key];
                }   
            }
            
            $k++;
        }
    }

    if ($k == sizeof($errors)) {
        $errors = array();
        $errors[] = _("Sie haben keine Daten angegeben!");
    }

    // done checks, if $error is filled, an error occurred
    if ($errors != null) {
        return array('ready' => false, 'errors' => $errors);
    }

    if ($form_data["block_submit_x"] && $errors==null)
    { /// create the schedule dates
        $start_time = mktime($form_data["start_hour"],
                $form_data["start_minute"],
                0,
                $form_data["start_month"],
                $form_data["start_day"],
                $form_data["start_year"]);

        $end_time = mktime($form_data["end_hour"],
                $form_data["end_minute"],
                0,
                $form_data["start_month"],
                $form_data["start_day"],
                $form_data["start_year"]);

        if ($start_time == -1 || $end_time ==-1) {
            $errors[] = "Startdatum: fehlerhafte Zeitangabe";
        } else {
            if ($start_time==$end_time) {
                $errors[] = "Start- und Endzeitpunkt sind gleich!";
            } else if ($start_time>$end_time)   {
                $errors[] = "Startzeitpunkt liegt nach Endzeitpunkt!";
            }
        }

        $absolute_end_time = mktime($form_data["end_hour"],
                $form_data["end_minute"],
                0,
                $form_data["end_month"],
                $form_data["end_day"],
                $form_data["end_year"]);

        if ($start_time > $absolute_end_time)
        {
            $errors[] = "Startdatum liegt nach Enddatum!";
        }


        if ($end_time == -1)
        {
            $errors[] = "Enddatum: fehlerhafte Zeitangabe";
        }

        if (sizeof($errors)==0)
        {
            $delta_time = $end_time - $start_time;

            $tmp_start_day_nr = date("w", $start_time);

            $tmp_start_time = $start_time;

            /// reset day index
            $day_counter = 0;

            // real starting time
            $start_time = strtotime("+".$day_counter." days",$start_time);
            $tmp_start_time = $start_time;
            // real end time
            $tmp_end_time = $tmp_start_time+$delta_time;

            if (!is_array($form_data['days'])) {
                $form_data['days'] = array();
            }

            $inserted = 0;
            // generate the schedule dates
            while($tmp_end_time <= $absolute_end_time)
            {
                if(in_array(date("l",$tmp_start_time),$form_data["days"]) || $form_data["every_day"]=='1')
                {
                    $schedule_dates[] = Array("start_time"=> $tmp_start_time, "end_time"=>$tmp_end_time,"astext"=> date("d.m.Y ",$tmp_start_time));
                    $inserted ++;
                }
                $tmp_start_time = strtotime("+1 day "." ".$form_data["start_hour"].":".$form_data["start_minute"], $tmp_start_time);
                $tmp_end_time = strtotime("+1 day ".$form_data["end_hour"].":".$form_data["end_minute"], $tmp_end_time);
            }
            if (!isset($schedule_dates)) {
                    $errors[] = $messages['no_days_in_timeslot'];
            }
        }
    }



    //echo "<pre>".print_r($GLOBALS,true)."<pre>";;
    if (!isset($form_data["block_submit_x"]) || $errors != null)
    { // show the form
        return array('ready' => false, 'errors' => $errors);
    } else {
        $query = "INSERT INTO termine "
               . "(termin_id, range_id, autor_id, content, description, date, "
               . " end_time, mkdate, chdate, date_typ, topic_id, raum) "
               . "VALUES "
               . "(?, ?, ?, 'Kein Titel', '', ?, ?, UNIX_TIMESTAMP(), "
               . " UNIX_TIMESTAMP(), ?, NULL, NULL)";
        $statement = DBManager::get()->prepare($query);

        // step through dates and insert into db
        $status = array();
        foreach ($schedule_dates as $date)
        {
            $statement->execute(array(
                md5(uniqid(rand(),true)), // $date_id
                $seminar_id,
                $GLOBALS['user']->id,
                $date['start_time'],
                $date['end_time'],
                $form_data['art']
            ));
            // status messages
            $status[] = $date['astext'];
        }
        //echo "message".print_r($messages,true)."<br>";
        return array('ready' => true, 'status' => $status);
    }
}
