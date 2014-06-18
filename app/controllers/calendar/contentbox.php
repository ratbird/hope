<?php

/*
 * contentbox.php - Calender Contentbox controller
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Florian Bieringer <florian.bieringer@uni-passau.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     calender
 */

class Calendar_ContentboxController extends StudipController {

    /**
     * Widget controller to produce the formally known show_votes()
     * 
     * @param String $range_id range id of the news to get displayed
     * @return array() Array of votes
     */
    function display_action($range_id, $timespan = 604800, $start = null) {

        // Visit object
        ContentBoxHelper::visitType('schedule');
        
        // Fetch time if needed
        $start = $start ? : time();

        $events = new DbCalendarEventList(new SingleCalendar($range_id, Calendar::PERMISSION_READABLE), $start, $start + $timespan, TRUE, null, ($GLOBALS['user']->id == $range_id ? array() : array('CLASS' => 'PUBLIC')));

        // Prepare termine
        $this->termine = array();

        while ($termin = $events->nextEvent()) {
            // Adjust title
            if (date("Ymd", $termin->getStart()) == date("Ymd", time()))
                $termin->title .= _("Heute") . date(", H:i", $termin->getStart());
            else {
                $termin->title = substr(strftime("%a", $termin->getStart()), 0, 2);
                $termin->title .= date(". d.m.Y, H:i", $termin->getStart());
            }

            if ($termin->getStart() < $termin->getEnd()) {
                if (date("Ymd", $termin->getStart()) < date("Ymd", $termin->getEnd())) {
                    $termin->title .= " - " . substr(strftime("%a", $termin->getEnd()), 0, 2);
                    $termin->title .= date(". d.m.Y, H:i", $termin->getEnd());
                } else {
                    $termin->title .= " - " . date("H:i", $termin->getEnd());
                }
            }

            if ($termin->getTitle()) {
                $tmp_titel = htmlReady(mila($termin->getTitle())); //Beschneiden des Titels
                $termin->title .= ", " . $tmp_titel;
            }

            // Store for view
            $this->termine[] = array(
                'id' => $termin->id,
                'title' => $termin->title,
                'description' => $termin->description,
                'room' => $termin->getLocation(),
                'info' => array(
                    _('Kategorie') => $termin->toStringCategories(),
                    _('Priorität') => $termin->toStringPriority(),
                    _('Sichtbarkeit') => $termin->toStringAccessibility(),
                    $termin->toStringRecurrence())
            );
        }

        // Fetch normal dates
        foreach (CourseDate::findBySQL('range_id = :range AND ((date BETWEEN :start AND :end) OR (end_time BETWEEN :start AND :end)) ORDER BY date', array(':range' => $range_id, ':start' => $start, ':end' => $start + $timespan)) as $courseDate) {

            // Build info
            $info = array();
            if ($courseDate->dozenten[0]) {
                $info[_('Durchführende Dozenten')] = join(', ', $courseDate->dozenten->getFullname());
            }
            if ($courseDate->statusgruppen[0]) {
                $info[_('Beteiligte Gruppen')] = join(', ', $courseDate->statusgruppen->getValue('name'));
            }

            // Store for view
            $this->termine[] = array(
                'id' => $courseDate->id,
                'title' => $courseDate->getFullname() . ($courseDate->topics[0] ? ', '.join(', ', $courseDate->topics->getValue('title') ): ""),
                'description' => $courseDate->description,
                'room' => $courseDate->getRoomName(),
                'info' => $info
            );
        }
        
        // Check permission to edit
        $this->admin = $range_id == $GLOBALS['user']->id || $GLOBALS['perm']->have_studip_perm('tutor', $range_id);

        // Forge title
        if ($this->termine) {
            $this->title = sprintf(_("Termine für die Zeit vom %s bis zum %s"), strftime("%d. %B %Y", $start), strftime("%d. %B %Y", $start + $timespan));
        } else {
            $this->title = _('Termine');
        }

        // Set range_id
        $this->range_id = $range_id;

        // Check out if we are on a profile
        if ($this->admin) {
            $this->isProfile = User::exists($range_id);
        }
    }

}
