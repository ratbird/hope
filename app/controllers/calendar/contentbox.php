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
    function display_action($range_id) {
        $events = new DbCalendarEventList(new SingleCalendar($range_id, Calendar::PERMISSION_READABLE), $date_start, $date_end, TRUE, null, ($GLOBALS['user']->id == $range_id ? array() : array('CLASS' => 'PUBLIC')));

        // Prepare termine
        $this->termine = array();
        
        while ($termin = $events->nextEvent()) {
            
            // Adjust title
            if (date("Ymd", $termin->getStart()) == date("Ymd", time()))
                $termin->titel .= _("Heute") . date(", H:i", $termin->getStart());
            else {
                $termin->titel = substr(strftime("%a", $termin->getStart()), 0, 2);
                $termin->titel .= date(". d.m.Y, H:i", $termin->getStart());
            }

            if ($termin->getStart() < $termin->getEnd()) {
                if (date("Ymd", $termin->getStart()) < date("Ymd", $termin->getEnd())) {
                    $termin->titel .= " - " . substr(strftime("%a", $termin->getEnd()), 0, 2);
                    $termin->titel .= date(". d.m.Y, H:i", $termin->getEnd());
                } else {
                    $termin->titel .= " - " . date("H:i", $termin->getEnd());
                }
            }

            if ($termin->getTitle()) {
                $tmp_titel = htmlReady(mila($termin->getTitle())); //Beschneiden des Titels
                $termin->titel .= ", " . $tmp_titel;
            }
            
            // Store for view
            $this->termine[] = $termin;
            
        }
        
        // Check permission to edit
        $this->admin = $GLOBALS['perm']->have_perm('root') || $range_id == $GLOBALS['user']->id;
    }

}
