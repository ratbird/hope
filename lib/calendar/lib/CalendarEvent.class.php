<?
# Lifter002: TODO
# Lifter007: TODO

/**
 * CalendarEvent.class.php
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

require_once($RELATIVE_PATH_CALENDAR . '/lib/Event.class.php');

class CalendarEvent extends Event
{

    var $ts;          // der "genormte" Timestamp
    var $user_id;
    var $import_date = -1;
    var $editor = '';

    function CalendarEvent($properties, $id = '', $user_id = '', $permission = NULL)
    {
        global $user;

        parent::Event($properties, $permission);

        if (!$user_id)
            $this->user_id = $user->id;
        else
            $this->user_id = $user_id;

        if (!$id) {
            $id = $this->createUniqueId();
        }

        $this->id = $id;
        if (!$this->properties['UID'])
            $this->properties['UID'] = $this->getUid();
        // privater Termin als Standard
        if ($this->properties['CLASS'] === '')
            $this->properties['CLASS'] = 'PRIVATE';
    }

    // public
    function getExpire()
    {
        return $this->properties['RRULE']['expire'];
    }

    function setImportDate($import_date = NULL)
    {
        if ($this->import_date < 0) {
            if (is_null($import_date)) {
                $this->import_date = time();
            } else {
                $this->import_date = $import_date;
            }
        }
    }

    function getImportDate()
    {
        if ($this->import_date < 0) {
            $this->import_date = time();
        }
        return $this->import_date;
    }

    /**
     * Returns the names of the categories.
     *
     * @access public
     * @return String the name of the category
     */
    function toStringCategories()
    {
        global $PERS_TERMIN_KAT;

        if ($this->permission == CALENDAR_EVENT_PERM_CONFIDENTIAL) {
            return '';
        }

        $categories = array();
        if ($this->properties['STUDIP_CATEGORY']) {
            $categories[] = $PERS_TERMIN_KAT[$this->getStudipCategory()]['name'];
        }

        if ($this->properties['CATEGORIES']) {
            $ext_categories = explode(',', parent::getCategory());
            foreach ($ext_categories as $ext_category) {
                $categories[] = trim($ext_category);
            }
        }
        if (sizeof($categories)) {
            return implode(', ', $categories);
        }

        return '';
    }

    // public
    function getTs()
    {

        return $this->properties['RRULE']['ts'];
    }

    function getUserId()
    {
        return $this->user_id ? $this->user_id : false;
    }

    // public
    function getRepeat($index = '')
    {
        if (is_array($this->properties['RRULE']))
            return $index ? $this->properties['RRULE'][$index] : $this->properties['RRULE'];

        return false;
    }

    // public
    function getType()
    {
        return $this->properties['CLASS'];
    }

    // public
    function setType($type)
    {
        $this->properties['CLASS'] = $type;
        $this->chng_flag = true;
    }

    // public
    function getPriority()
    {
        return $this->properties['PRIORITY'];
    }

    function setPriority($priority)
    {
        $this->properties['PRIORITY'] = $priority;
        $this->chng_flag = true;
    }

    function toStringPriority()
    {
        if ($this->permission == CALENDAR_EVENT_PERM_CONFIDENTIAL) {
            return '';
        }
        switch ($this->properties['PRIORITY']) {
            case 1:
                return _("hoch");
            case 2:
                return _("mittel");
            case 3:
                return _("niedrig");
            default:
                return _("keine Angabe");
        }
    }

    function toStringAccessibility()
    {

        switch ($this->properties['CLASS']) {
            case 'PUBLIC':
                return _("öffentlich");
            case 'CONFIDENTIAL':
                return _("vertraulich");
            default:
                return _("privat");
        }
    }

    function setRepeat($r_rule)
    {

        $this->properties['RRULE'] = $this->createRepeat($r_rule, $this->properties['DTSTART'], $this->properties['DTEND']);
        $this->ts = $this->properties['RRULE']['ts'];
        $this->chng_flag = true;
    }

    /**
     * Sets the recurrence rule of this event
     *
     * The possible repetitions are:
     * SINGLE, DAILY, WEEKLY, MONTHLY, YEARLY
     *
     */
    function createRepeat($r_rule, $start, $end)
    {
        $duration = (int) ((mktime(12, 0, 0, date('n', $end), date('j', $end), date('Y', $end), 0)
                - mktime(12, 0, 0, date('n', $start), date('j', $start), date('Y', $start), 0))
                / 86400);
        if (!isset($r_rule['count']))
            $r_rule['count'] = 0;
        // Hier wird auch der 'genormte Timestamp' (immer 12.00 Uhr, ohne Sommerzeit) ts berechnet.
        switch ($r_rule['rtype']) {

            // ts ist hier der Tag des Termins 12:00:00 Uhr
            case 'SINGLE':
                $ts = mktime(12, 0, 0, date('n', $start), date('j', $start), date('Y', $start), 0);
                $rrule = array($ts, 0, 0, '', 0, 0, 'SINGLE', $duration);
                break;

            case 'DAILY':
                // ts ist hier der Tag des ersten Wiederholungstermins 12:00:00 Uhr
                $ts = mktime(12, 0, 0, date('n', $start), date('j', $start) + $r_rule['linterval'], date('Y', $start), 0);
                if ($r_rule['count']) {
                    $r_rule['expire'] = mktime(23, 59, 59, date('n', $start), date('j', $start)
                            + ($r_rule['count'] - 1) * $r_rule['linterval'], date('Y', $start), 0);
                }
                if (!$r_rule['linterval'])
                    $rrule = array($ts, 1, 0, '', 0, 0, 'DAILY', $duration);
                else
                    $rrule = array($ts, $r_rule['linterval'], 0, '', 0, 0, 'DAILY', $duration);
                break;

            case 'WEEKLY':
                // ts ist hier der Montag der ersten Wiederholungswoche 12:00:00 Uhr
                if (!$r_rule['linterval'] && !$r_rule['wdays']) {
                    $ts = mktime(12, 0, 0, date('n', $start), date('j', $start) +
                            (7 - (strftime('%u', $start) - 1)), date('Y', $start), 0);
                    if ($r_rule['count']) {
                        $r_rule['expire'] = mktime(23, 59, 59, date('n', $start), date('j', $start) +
                                (7 * ($r_rule['count'] - 1)), date('Y', $start));
                    }
                    $rrule = array($ts, 1, 0, strftime('%u', $start), 0, 0, 'WEEKLY', $duration);
                } else if (!$r_rule['wdays']) {
                    $ts = mktime(12, 0, 0, date('n', $start), date('j', $start) +
                            ($r_rule['linterval'] * 7 - (strftime('%u', $start) - 1)), date('Y', $start), 0);
                    if ($r_rule['count']) {
                        $r_rule['expire'] = mktime(23, 59, 59, date('n', $start), date('j', $start) +
                                ($r_rule['linterval'] * 7 * ($r_rule['count'] - 1)), date('Y', $start));
                    }
                    $rrule = array($ts, $r_rule['linterval'], 0, strftime('%u', $start), 0, 0, 'WEEKLY', $duration);
                } else {
                    $ts = mktime(12, 0, 0, date('n', $start), date('j', $start) + (7 - (strftime('%u', $start) - 1))
                            - ((strftime('%u', $start) <= substr($r_rule['wdays'], -1)) ? 7 : 0), date('Y', $start), 0);

                    if ($r_rule['count']) {
                        // last week day of the recurrence set
                        $set_start_wday = false;
                        $wdays = array(0);
                        for ($i = 0; $i < strlen($r_rule['wdays']); $i++) {
                            $wdays[] = $r_rule['wdays']{$i};
                            if (!$set_start_wday && intval($r_rule['wdays']{$i}) >= intval(strftime('%u', $start))) {
                                $start_wday = $r_rule['wdays']{$i};
                                $set_start_wday = true;
                            }
                        }
                        if (intval(strftime('%u', $start)) > intval(substr($r_rule['wdays'], -1))) {
                            $start_wday = $r_rule['wdays']{0};
                        }
                        $expire_ts = $ts + ((($r_rule['count'] % (count($wdays) - 1)) >= 1) ? (($start_wday - 1) * 86400) : 0)
                                + floor($r_rule['count'] / (count($wdays) - 1)) * 604800 * $r_rule['linterval'];

                        $r_rule['expire'] = mktime(23, 59, 59, date('n', $expire_ts), date('j', $expire_ts), date('Y', $expire_ts));
                    }
                    $rrule = array($ts, $r_rule['linterval'], 0, $r_rule['wdays'], 0, 0, 'WEEKLY', $duration);
                }
                break;

            case 'MONTHLY':
                if ($r_rule['month'])
                    return false;
                if (!$r_rule['linterval'] && !$r_rule['day'] && !$r_rule['sinterval'] && !$r_rule['wdays']) {
                    $ts = mktime(12, 0, 0, date('n', $start) + 1, date('j', $start), date('Y', $start), 0);
                    $rrule = array($ts, 1, 0, '', 0, date('j', $start), 'MONTHLY', $duration);
                } else if (!$r_rule['day'] && !$r_rule['sinterval'] && !$r_rule['wdays']) {
                    $amonth = date('n', $start) + $r_rule['linterval'];
                    $ts = mktime(12, 0, 0, $amonth, date('j', $start), date('Y', $start), 0);
                    $rrule = array($ts, $r_rule['linterval'], 0, '', 0, date('j', $start), 'MONTHLY', $duration);
                } else if (!$r_rule['sinterval'] && !$r_rule['wdays']) {
                    // Ist erste Wiederholung schon im gleichen Monat?
                    if ($r_rule['day'] < date('j', $start))
                        $amonth = date('n', $start) + $r_rule['linterval'];
                    else
                        $amonth = date('n', $start);
                    $ts = mktime(12, 0, 0, $amonth, $r_rule['day'], date('Y', $start), 0);
                    $rrule = array($ts, $r_rule['linterval'], 0, '', 0, $r_rule['day'], 'MONTHLY', $duration);
                } elseif (!$r_rule['day']) {
                    // hier ist ts der erste Wiederholungstermin
                    $amonth = date('n', $start);
                    $adate = mktime(12, 0, 0, $amonth, 1, date('Y', $start), 0) + ($r_rule['sinterval'] - 1) * 604800;
                    $awday = strftime('%u', $adate);
                    $adate -= ( $awday - $r_rule['wdays']) * 86400;
                    if ($r_rule['sinterval'] == 5) {
                        if (date('j', $adate) < 10)
                            $adate -= 604800;
                        if (date('n', $adate) == date('n', $adate + 604800))
                            $adate += 604800;
                    } elseif ($awday > $r_rule['wdays']) {
                        $adate += 604800;
                    }
                    // Ist erste Wiederholung schon im gleichen Monat?
                    if (date('Ymd', $adate) < date('Ymd', $start)) {
                        //Dann muss hier die Berechnung ohne interval wiederholt werden
                        $amonth = date('n', $start) + $r_rule['linterval'];
                        $adate = mktime(12, 0, 0, $amonth, 1, date('Y', $start), 0) + ($r_rule['sinterval'] - 1) * 604800;
                        $awday = strftime('%u', $adate);
                        $adate -= ( $awday - $r_rule['wdays']) * 86400;
                        if ($r_rule['sinterval'] == 5) {
                            if (date('j', $adate) < 10)
                                $adate -= 604800;
                            if (date('n', $adate) == date('n', $adate + 604800))
                                $adate += 604800;
                        }
                        else if ($awday > $r_rule['wdays'])
                            $adate += 604800;
                    }
                    $ts = $adate;
                    $rrule = array($ts, $r_rule['linterval'], $r_rule['sinterval'], $r_rule['wdays'], 0, 0, 'MONTHLY', $duration);
                }

                if ($r_rule['count']) {
                    $r_rule['expire'] = mktime(23, 59, 59, date('n', $ts) + $r_rule['linterval']
                            * ($r_rule['count'] - 1), date('j', $ts), date('Y', $ts));
                }
                break;

            case 'YEARLY':
                // ts ist hier der erste Wiederholungstermin 12:00:00 Uhr
                if (!$r_rule['month'] && !$r_rule['day'] && !$r_rule['sinterval'] && !$r_rule['wdays']) {
                    $ts = mktime(12, 0, 0, date('n', $start), date('j', $start), date('Y', $start) + 1, 0);
                    $rrule = array($ts, 1, 0, '', date('n', $start), date('j', $start), 'YEARLY', $duration);
                } else if (!$r_rule['sinterval'] && !$r_rule['wdays']) {
                    if (!$r_rule['day'])
                        $r_rule['day'] = date('j', $start);
                    $ts = mktime(12, 0, 0, $r_rule['month'], $r_rule['day'], date('Y', $start), 0);
                    if ($ts <= mktime(12, 0, 0, date('n', $start), date('j', $start), date('Y', $start), 0))
                        $ts = mktime(12, 0, 0, $r_rule['month'], $r_rule['day'], date('Y', $start) + 1, 0);
                    $rrule = array($ts, 1, 0, '', $r_rule['month'], $r_rule['day'], 'YEARLY', $duration);
                }
                else if (!$r_rule['day']) {
                    $ayear = date('Y', $start);
                    do {
                        $adate = mktime(12, 0, 0, $r_rule['month'], 1 + ($r_rule['sinterval'] - 1) * 7, $ayear, 0);
                        $aday = strftime('%u', $adate);
                        $adate -= ( $aday - $r_rule['wdays']) * 86400;
                        if ($r_rule['sinterval'] == 5) {
                            if (date('j', $adate) < 10)
                                $adate -= 604800;
                            if (date('n', $adate) == date('n', $adate + 604800))
                                $adate += 604800;
                        }
                        else if ($aday > $r_rule['wdays'])
                            $adate += 604800;
                        $ts = $adate;
                        $ayear++;
                    } while ($ts <= mktime(12, 0, 0, date('n', $start), date('j', $start), date('Y', $start), 0));
                    $rrule = array($ts, 1, $r_rule['sinterval'], $r_rule['wdays'], $r_rule['month'], 0, 'YEARLY', $duration);
                }

                if ($r_rule['count']) {
                    $r_rule['expire'] = mktime(23, 59, 59, date('n', $ts), date('j', $ts), date('Y', $ts) + $r_rule['count'] - 1);
                }
                break;

            default :
                $ts = mktime(12, 0, 0, date('n', $start), date('j', $start), date('Y', $start), 0);
                $rrule = array($ts, 0, 0, '', 0, 0, 'SINGLE', $duration);
                $r_rule['count'] = 0;
        }

        if (!$r_rule['expire'] || $r_rule['expire'] > CALENDAR_END)
            $r_rule['expire'] = CALENDAR_END;

        return array(
            'ts' => $rrule[0],
            'linterval' => $rrule[1],
            'sinterval' => $rrule[2],
            'wdays' => $rrule[3],
            'month' => $rrule[4],
            'day' => $rrule[5],
            'rtype' => $rrule[6],
            'duration' => $rrule[7],
            'count' => $r_rule['count'],
            'expire' => $r_rule['expire']);
    }

    function getUid()
    {

        return "Stud.IP-{$this->id}@{$_SERVER['SERVER_NAME']}";
    }

    /**
     * Returns the index of a category.
     * See config.inc.php $PERS_TERMIN_KAT.
     *
     * @access public
     * @return int the index of the category
     */
    function getCategory()
    {
        global $PERS_TERMIN_KAT;

        if ($this->permission == CALENDAR_EVENT_PERM_CONFIDENTIAL)
            return 255;

        if ($this->properties['STUDIP_CATEGORY'])
            return $this->properties['STUDIP_CATEGORY'];

        $categories = array();
        foreach ($PERS_TERMIN_KAT as $category)
            $categories[] = strtolower($category['name']);

        $cat_event = explode(',', $this->properties['CATEGORIES']);
        foreach ($cat_event as $category) {
            if ($index = array_search(strtolower(trim($category)), $categories))
                return++$index;
        }

        return 0;
    }

    /**
     * Returns an array with the path to a background image (index 'image')
     * and the color (index 'color') of a category. If $image_size is 'small'
     * returns th path to the smaller version of the image.
     * See config.inc.php $PERS_TERMIN_KAT.
     *
     * @access public
     * @param $image_size the size of the image ('small' or 'big')
     * @return array image path and color
     */
    function getCategoryStyle($image_size = 'small')
    {
        global $PERS_TERMIN_KAT, $CANONICAL_RELATIVE_PATH_STUDIP, $RELATIVE_PATH_CALENDAR;

        $index = $this->getCategory();
        if ($index) {
            return array('image' => $image_size == 'small' ?
                        "{$GLOBALS['ASSETS_URL']}images/calendar/category{$index}_small.jpg" :
                        "{$GLOBALS['ASSETS_URL']}images/calendar/category{$index}.jpg",
                'color' => $PERS_TERMIN_KAT[$index]['color']);
        }

        return array('image' => $image_size == 'small' ?
                    "{$GLOBALS['ASSETS_URL']}images/calendar/category1_small.jpg" :
                    "{$GLOBALS['ASSETS_URL']}images/calendar/category1.jpg",
            'color' => $PERS_TERMIN_KAT[1]['color']);
    }

    /**
     * Returns a unique ID
     *
     * @access public
     * @return String the unique ID
     */
    function createUniqueId()
    {

        return md5(uniqid(rand() . "Stud.IP Calendar"));
    }

    /**
     * Returns a string representation of the recurrence rule
     *
     * @access public
     * @return String the recurrence rule - human readable
     */
    function toStringRecurrence()
    {

        $replace = array(_("Montag") . ', ', _("Dienstag") . ', ', _("Mittwoch") . ', ',
            _("Donnerstag") . ', ', _("Freitag") . ', ', _("Samstag") . ', ', _("Sonntag") . ', ');
        $search = array('1', '2', '3', '4', '5', '6', '7');
        $wdays = str_replace($search, $replace, $this->properties['RRULE']['wdays']);
        $wdays = substr($wdays, 0, -2);

        switch ($this->properties['RRULE']['rtype']) {
            case 'DAILY':
                if ($this->properties['RRULE']['linterval'] > 1) {
                    $text = sprintf(_("Der Termin wird alle %s Tage wiederholt."), $this->properties['RRULE']['linterval']);
                }
                else
                    $text = _("Der Termin wird täglich wiederholt");
                break;

            case 'WEEKLY':
                if ($this->properties['RRULE']['linterval'] > 1) {
                    $text = sprintf(_("Der Termin wird alle %s Wochen am %s wiederholt."), $this->properties['RRULE']['linterval'], $wdays);
                }
                else
                    $text = sprintf(_("Der Termin wird jeden %s wiederholt."), $wdays);
                break;

            case 'MONTHLY':
                if ($this->properties['RRULE']['linterval'] > 1) {
                    if ($this->properties['RRULE']['day']) {
                        $text = sprintf(_("Der Termin wird am %s. alle %s Monate wiederholt."), $this->properties['RRULE']['day'], $this->properties['RRULE']['linterval']);
                    } else {
                        if ($this->properties['RRULE']['sinterval'] != '5') {
                            $text = sprintf(_("Der Termin wird jeden %s. %s alle %s Monate wiederholt."), $this->properties['RRULE']['sinterval'], $wdays, $this->properties['RRULE']['linterval']);
                        } else {
                            $text = sprintf(_("Der Termin wird jeden letzten %s alle %s Monate wiederholt."), $wdays, $this->properties['RRULE']['linterval']);
                        }
                    }
                } else {
                    if ($this->properties['RRULE']['day']) {
                        $text = sprintf(_("Der Termin wird am %s. jeden Monat wiederholt."), $this->properties['RRULE']['day'], $this->properties['RRULE']['linterval']);
                    } else {
                        if ($this->properties['RRULE']['sinterval'] != '5') {
                            $text = sprintf(_("Der Termin wird am %s. %s jeden Monat wiederholt."), $this->properties['RRULE']['sinterval'], $wdays, $this->properties['RRULE']['linterval']);
                        } else {
                            $text = sprintf(_("Der Termin wird jeden letzten %s alle %s Monate wiederholt."), $wdays, $this->properties['RRULE']['linterval']);
                        }
                    }
                }
                break;

            case 'YEARLY':
                $month_names = array(_("Januar"), _("Februar"), _("März"), _("April"), _("Mai"),
                    _("Juni"), _("Juli"), _("August"), _("September"), _("Oktober"),
                    _("November"), _("Dezember"));
                if ($this->properties['RRULE']['day']) {
                    $text = sprintf(_("Der Termin wird jeden %s. %s wiederholt."), $this->properties['RRULE']['day'], $month_names[$this->properties['RRULE']['month'] - 1]);
                } else {
                    if ($this->properties['RRULE']['sinterval'] != '5') {
                        $text = sprintf(_("Der Termin wird jeden %s. %s im %s wiederholt."), $this->properties['RRULE']['sinterval'], $wdays, $month_names[$this->properties['RRULE']['month'] - 1]);
                    } else {
                        $text = sprintf(_("Der Termin wird jeden letzten %s im %s wiederholt."), $wdays, $month_names[$this->properties['RRULE']['month'] - 1]);
                    }
                }
                break;

            default:
                $text = _("Der Termin wird nicht wiederholt.");
        }

        return $text;
    }

    function getExceptions()
    {

        if ($this->properties['EXDATE'] != '') {
            $exceptions = explode(',', $this->properties['EXDATE']);
            if (is_array($exceptions)) {
                sort($exceptions, SORT_NUMERIC);
                return $exceptions;
            }
        }

        return array();
    }

    function setExceptions($exceptions)
    {

        if (is_array($exceptions) && !($this->getRepeat('rtype') == ''
                || $this->getRepeat('rtype') == 'SINGLE')) {
            sort(array_unique($exceptions), SORT_NUMERIC);
            $this->properties['EXDATE'] = implode(',', $exceptions);
        }
        else
            $this->properties['EXDATE'] = '';
    }

    function toStringDate($mod = 'SHORT')
    {

        if ($this->isDayEvent()) {
            if ($mod == 'SHORT')
                return _("ganztägig");

            if (date('Ymd', $this->getStart()) != date('Ymd', $this->getEnd()) - 1) {
                if ($mod == 'LONG') {
                    $string = wday($this->getStart())
                            . strftime(', %x - ', $this->getStart());
                    $string .= wday($this->getEnd())
                            . strftime(', %x (', $this->getEnd() - 1) . _("ganztägig") . ')';
                } else {
                    $string = wday($this->getStart(), 'SHORT')
                            . strftime('. %x - ', $this->getStart());
                    $string .= wday($this->getEnd(), 'SHORT')
                            . strftime('. %x (', $this->getEnd() - 1) . _("ganztägig") . ')';
                }
            } else {
                if ($mod == 'LONG') {
                    $tring = wday($this->getStart())
                            . strftime(', %x (', $this->getStart()) . _("ganztägig") . ')';
                } else {
                    $string = wday($this->getStart(), 'SHORT')
                            . strftime('. %x (', $this->getStart()) . _("ganztägig") . ')';
                }
            }

            return $string;
        }

        return parent::toStringDate($mod);
    }

    function getEditorId()
    {
        if ($this->editor != '' && $this->editor != $this->properties['STUDIP_AUTHOR_ID']) {
            return $this->editor;
        }
        return false;
    }

}
