<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/*
CalendarYear.class.php - 0.8.20020628
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

class CalendarYear{

    var $year;            // Jahr (int)
    var $ts;              // "genormter" timestamp (s.o.)
    
    // Konstruktor
    function CalendarYear($tmstamp){
        $this->year = date("Y", $tmstamp);
        $this->ts = mktime(12,0,0,1,1,$this->year,0);
    }
    
    // public
    function getYear(){
        return $this->year;
    }
    
    function toString(){
        return (String) $this->year;
    }
    
    // public
    function getStart(){
        return mktime(0,0,0,1,1,$this->year);
    }
    
    // public
    function getEnd(){
        $end = mktime(0,0,0,1,1,$this->year + 1) - 1;
        return $end;
    }
    
    // public
    function getTs(){
        return $this->ts;
    }
    
    // public
    function serialisiere(){
        return serialize($this);
    }
    
} // class CalendarYear

?>
