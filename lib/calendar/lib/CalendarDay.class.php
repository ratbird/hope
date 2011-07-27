<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/*
CalendarDay.class.php - 0.8.20020409a
Klassen fuer Persoenlichen Terminkalender in Stud.IP.
Copyright (C) 2001 Peter Thienel <pthien@gmx.de>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

//****************************************************************************

require_once("config.inc.php");
require_once($RELATIVE_PATH_CALENDAR . "/lib/CalendarMonth.class.php");

class CalendarDay extends CalendarMonth{

    var $dow;               // Wochentag (int)
    var $dom;               // Tag des Monats (int)

    // Konstruktor
    function CalendarDay($tmstamp){
        $date = getdate($tmstamp);
        $this->dow = strftime("%u", $tmstamp);
        $this->dom = $date["mday"];
        $this->year = $date["year"];
        $this->mon = $date["mon"];
        $this->ts = mktime(12,0,0,$this->mon,$this->dom,$this->year,0);
    }
    
    // public
    function getStart(){
        return mktime(0,0,0,$this->mon,$this->dom,$this->year);
    }
    
    function getEnd(){
        return mktime(23,59,59,$this->mon,$this->dom,$this->year);
    }
    
    // public
    function toString($mod = "SHORT"){
        return wday($this->ts, $mod);
    }
    
    // public
    function getValue(){
        return $this->dom;
    }
    
    // public
    function getDate($mod = "LONG"){
        if($mod == "SHORT"){
            if(strlen($this->dom) == 1)
                $date = "0" . $this->dom . ".";
            else
                $date = $this->dom . ".";
            if(strlen($this->mon) == 1)
                $date .= "0" . $this->mon . ".";
            else
                $date .= $this->mon . ".";
            return $date . $this->year;
        }
        else
            return $this->dom . htmlentities(strftime(". %B ", $this->ts), ENT_QUOTES) . $this->year;
    }
    
    // public
    function isHoliday(){
        return holiday($this->ts);
    }
    
} // class Day

?>
