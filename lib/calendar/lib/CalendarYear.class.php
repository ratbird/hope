<?
# Lifter007: TODO

/**
 * CalendarYear.class.php
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

class CalendarYear
{

    var $year;            // Jahr (int)
    var $ts;              // "genormter" timestamp (s.o.)

    // Konstruktor

    function CalendarYear($tmstamp)
    {
        $this->year = date('Y', $tmstamp);
        $this->ts = mktime(12, 0, 0, 1, 1, $this->year, 0);
    }

    // public
    function getYear()
    {
        return $this->year;
    }

    function toString()
    {
        return (String) $this->year;
    }

    // public
    function getStart()
    {
        return mktime(0, 0, 0, 1, 1, $this->year);
    }

    // public
    function getEnd()
    {
        $end = mktime(0, 0, 0, 1, 1, $this->year + 1) - 1;
        return $end;
    }

    // public
    function getTs()
    {
        return $this->ts;
    }

    // public
    function serialisiere()
    {
        return serialize($this);
    }

}
