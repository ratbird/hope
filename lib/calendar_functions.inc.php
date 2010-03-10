<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* calendar_functions.inc.php
*
* basic calendar functions
*
* @author       Peter Thienel <pthienel@web.de>
*   @access     public
* @package      studip_core
* @modulegroup      library
* @module       calendar_functions
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// calendar_functions.inc.php
//
// Copyright (C) 2001 Peter Thienel <pthienel@web.de>
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


// Erzeugt aus einem Unix-Timestamp den Wochentag
// Name entweder in Lang- ("LONG") oder Kurzform ("SHORT")

function wday ($tmstamp = "", $mode = "LONG", $day_german = "") {
    global $_language;

    // translate german weekdays with strftime()
    if (!$tmstamp) {
        // timestamps of known weekdays
        $tmstamps = array(
            "MO" => 39092400,
            "DI" => 39178800,
            "MI" => 39265200,
            "DO" => 39351600,
            "FR" => 39438000,
            "SA" => 39524400,
            "SO" => 39610800
        );
        $tmstamp = $tmstamps[$day_german];
    }

    // If the setlocale is set to "de_DE" the short form of day names is a bit
    // strange ;-), so it's better to use these:
    if ($_language == "de_DE") {
        $dayname_long = array("Sonntag", "Montag", "Dienstag", "Mittwoch",
                                                "Donnerstag", "Freitag", "Samstag");
        $dayname_short = array("So", "Mo", "Di", "Mi", "Do", "Fr", "Sa");

        $dow = date("w", $tmstamp);


        if($mode == "SHORT")
            return $dayname_short[$dow];
        return $dayname_long[$dow];
    }

    // For the rest of the world strftime() should be OK ;-)
    if ($mode == "SHORT")
        return htmlentities(strftime("%a", $tmstamp), ENT_QUOTES);
    return htmlentities(strftime("%A", $tmstamp), ENT_QUOTES);

}


function ldate ($tmstamp) {
    return wday($tmstamp) . ", " . date("j. ",$tmstamp)
            . htmlentities(strftime("%B %Y", $tmstamp), ENT_QUOTES);
}


// Hier jezt die ultimative Feiertags-"Berechnung"
// Zurueckgegeben wird ein Array mit Namen des Feiertages ("name") und
// Faerbungsgrad ("col", 0 bis 2).

function holiday ($tmstamp, $mod = "") {
    // erstmal brauchen wir den Ostersonntag fuer die meisten kirchlichen Feiertage
//  $easterday = easter_date(date("Y", $tmstamp)); // geht leider nicht
    // Berechnung nach Carters Algorithmus (gueltig von 1900 - 2099)
    $tmstamp = mktime(0,0,0,date("n",$tmstamp),date("j",$tmstamp),date("Y",$tmstamp));
    $year = date("Y", $tmstamp);
    $b = 225 - 11 * ($year % 19);
    $d = (($b - 21) % 30) + 21;
    if ($d > 48)
        $d--;
    $e = ($year + abs($year / 4) + $d + 1) % 7;
    $q = $d + 7 - $e;
    if ($q < 32)
        $easterday = date("z", mktime(0, 0, 0, 3, $q, $year)) + 1;
    else
        $easterday = date("z", mktime(0, 0, 0, 4, $q - 31, $year)) + 1;

    // Differenz in Tagen zu Ostertag berechnen
    $doy = date("z", $tmstamp) + 1;
    $dif = $doy - $easterday;
    switch ($dif) {
        case -48: $name = "Rosenmontag"; $col = 1; break;
        case -47: $name = "Fastnacht"; $col = 1; break;
        case -46: $name = "Aschermittwoch"; $col = 1; break;
    //  case -8: $name = "Palmsonntag"; $col = 1; break;
        case  -2: $name = "Karfreitag"; $col = 3; break;
        case   0: $name = "Ostersonntag"; $col = 3; break;
        case   1: $name = "Ostermontag"; $col = 3; break;
        case  39: $name = "Christi Himmelfahrt"; $col = 3; break;
        case  49: $name = "Pfingstsonntag"; $col = 3; break;
        case  50: $name = "Pfingstmontag"; $col = 3; break;
        case  60: $name = "Fronleichnam"; $col = 1; break;
    }

    // die unveraenderlichen Feiertage
    switch ($doy) {
        case   1: $name = "Neujahr"; $col = 3; break;
        case   6: $name = "Hl. Drei K&ouml;nige"; $col = 1; break;
    }

    // Schaltjahre nicht vergessen
    if (date("L", $tmstamp))
        $doy--;
    switch ($doy) {
        case  79: $name = "Fr&uuml;hlingsanfang"; $col = 1; break;
        case 121: $name = "Maifeiertag"; $col = 3; break;
//      case 125: $name = "Europatag"; $col = 1; break;
        case 172: $name = "Sommeranfang"; $col = 1; break;
        case 266: $name = "Herbstanfang"; $col = 1; break;
        case 276: $name = "Tag der deutschen Einheit"; $col = 3; break;
        case 304: $name = "Reformationstag"; $col = 2; break;
        case 305: $name = "Allerheiligen"; $col = 1; break;
        case 315: $name = "Martinstag"; $col = 1; break;
        case 340: $name = "Nikolaus"; $col = 1; break;
        case 355: $name = "Winteranfang"; $col = 1; break;
        case 358: $name = "Hl. Abend"; $col = 1; break;
        case 359: $name = "1. Weihnachtstag"; $col = 3; break;
        case 360: $name = "2. Weihnachtstag"; $col = 3; break;
        case 365: $name = "Sylvester"; $col = 1; break;
    }

    // Die Sonntagsfeiertage
    if (date("w", $tmstamp) == 0) {
        if ($doy > 127 && $doy < 135) {
            $name = "Muttertag";
            $col = 1;
        }
        else if ($doy > 266 && $doy < 274) {
            $name = "Erntedank";
            $col = 1;
        }
        else if ($doy > 316 && $doy < 324) {
            $name = "Volkstrauertag";
            $col = 2;
        }
        else if ($doy > 323 && $doy < 331) {
            $name = "Totensonntag";
            $col = 1;
        }
        else if ($doy > 330 && $doy < 338) {
            $name = "1. Advent";
            $col = 2;
        }
        else if ($doy > 337 && $doy < 345) {
            $name = "2. Advent";
            $col = 2;
        }
        else if ($doy > 344 && $doy < 352) {
            $name = "3. Advent";
            $col = 2;
        }
        else if ($doy > 351 && $doy < 359) {
            $name = "4. Advent";
            $col = 2;
        }
    }

    if ($name)
        return array("name" => _($name), "col" => $col);

    return FALSE;
}

// ueberprueft eine Datumsangabe, die in einen Timestamp gewandelt werden soll
// gibt bei Erfolg den timestamp zurück mit DST
function check_date ($month, $day, $year, $hour = 0, $min = 0) {
    if (!preg_match("/^\d{1,2}$/", $day) || !preg_match("/^\d{1,2}$/", $month)
            || !preg_match("/^\d{1,2}$/", $hour) || !preg_match("/^\d{1,2}$/", $min)
            || !preg_match("/^\d{4}$/", $year)) {
        return FALSE;
    }
    if ($year < 1970 || $year > 2036)
        return FALSE;
    if (!checkdate($month, $day, $year))
        return FALSE;
    if ($hour > 23 || $hour < 0 || $min > 59 || $min < 0)
        return FALSE;

    return mktime($hour, $min, 0, $month, $day, $year);
}

// ermittelt die Anzahl von Tagen zwischen zwei timestamps (plus Schalttage)
function day_diff ($ts_1, $ts_2) {
    $days = (int)(abs($ts_1 - $ts_2) / 86400);
    $days_1 = (int)(date("Y", $ts_1) / 4);
    $days_2 = (int)(date("Y", $ts_2) / 4);
    if (date("n", $ts_1) > 3 && date("L", $ts_1))
        $days_1--;
    if (date("n", $ts_2) > 3 && date("L", $ts_2))
        $days_2--;

    return $days - abs($days_1 - $days_2);
}

/**
 * Useful function to return the name of the n-th day. Note that the first
 * day's is 1 not 0. Thus "monday" is "1" and "sunday" is "7"!
 *
 *
 * @param int     the index of the day
 *
 * @return string the name of the day
 *
 */
function get_day_name($day) {

  $days = array(_("Montag"),
                _("Dienstag"),
                _("Mittwoch"),
                _("Donnerstag"),
                _("Freitag"),
                _("Samstag"),
                _("Sonntag"));

  if (!isset($days[$day - 1])) {
    trigger_error(sprintf('Argument(%s) has to be between 1 and 7', $day),
                  E_USER_ERROR);
    exit;
  }


    return $days[$day - 1];
}

/**
 * checks values that shall become a single date with start- and endtime
 *
 * @param string $day
 * @param string $month
 * @param string $year
 * @param string $start_hour
 * @param string $start_minute
 * @param string $end_hour
 * @param string $end_minute
 *
 * @return bool true if date is valid, false otherwise
 */
function check_singledate( $day, $month, $year, $start_hour, $start_minute, $end_hour, $end_minute ) {

    // check start-date
    $start = check_date($month, $day, $year, $start_hour, $start_minute);
    if (!$start) return false;

    // check end-date
    $end = check_date($month, $day, $year, $end_hour, $end_minute);
    if (!$end) return false;

    // check, that end-date is not before start_date
    return ($end > $start);
}
