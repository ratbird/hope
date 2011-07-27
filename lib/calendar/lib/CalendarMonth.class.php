<?
# Lifter002: TODO
# Lifter007: TODO

/**
 * CalendarMonth.class.php
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

require_once($RELATIVE_PATH_CALENDAR . '/lib/CalendarYear.class.php');

class CalendarMonth extends CalendarYear
{

    var $mon;      // Monat (int)

    // Konstruktor

    function CalendarMonth($tmstamp)
    {
        $this->year = date("Y", $tmstamp);
        $this->mon = date("n", $tmstamp);
        $this->ts = mktime(12, 0, 0, $this->mon, 1, $this->year, 0);
    }

    // public
    function getValue()
    {
        return $this->mon;
    }

    // public
    function toString()
    {
        return htmlentities(strftime("%B", $this->ts), ENT_QUOTES);
    }

    // public
    function getStart()
    {
        return mktime(0, 0, 0, $this->mon, 1, $this->year);
    }

    // public
    function getEnd()
    {
        $next_mon = $this->mon + 1;
        return mktime(0, 0, 0, $next_mon, 1, $this->year) - 1;
    }

}
