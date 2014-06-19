<?php
# Lifter010: TODO

/**
 * CalendarWeekView.class.php - a specialized calendar view for displaying weeks
 *
 * This class takes and checks all necessary parameters to display a calendar/schedule/time-table.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Till Gl�ggler <tgloeggl@uos.de> & Rasmus Fuhse <fuhse@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

require_once 'lib/calendar/CalendarView.class.php';

/**
 * Kind of bean class for the calendar view.
 *
 * @since      2.0
 */

class WidgetCalendarWeekView extends CalendarView
{
    protected $read_only  = false; //irgendwann mal ersetzen durch insertFunction

    protected $days       = array(1,2,3,4,5);
    protected static $day_names  = array("Montag","Dienstag","Mittwoch","Donnerstag","Freitag","Samstag","Sonntag");



    /**
     * You need to pass an instance of this class to the template. The constructor
     * expects an array of entries of the following type:
     *  array(
     *   $day_number => array(array (
     *    'color' => the color in hex (css-like, without the #)
     *    'start' => the (start hour * 100) + (start minute)
     *    'end'   => the (end hour * 100) + (end minute)
     *    //'day'   => day of week (0 = Sunday, ... , 6 = Saturday)
     *    'title' => the entry`s title
     *    'content' => whatever shall be the content of the entry as a string
     *   ) ...) ...
     *  )
     *
     * @param  mixed  $entries     an array of entries (see above)
     * @param  string $controller  the name of the controller. Used to create links.
     */
    public function __construct($entries, $controller)
    {
        parent::__construct($entries);
        $this->context = $controller;
    }

    /**
     * Call this function th enable/disable the grouping of entries with the same start and end.
     *
     * @param  bool  $group  optional, defaults to true
     */
    public function groupEntries($grouped = true)
    {
        $this->grouped = $grouped;
        foreach($this->entries as $entry_column) {
            $entry_column->groupEntries();
        }
    }

    /**
     * @param bool  $readonly  true to make it read only, false otherwise
     * @return void
     */
    public function setReadOnly($readonly = true)
    {
        $this->read_only = $readonly;
    }


    /* * * * * * * * * * * * * * *
     * * *   G E T T E R S   * * *
     * * * * * * * * * * * * * * */

    /**
     * @return mixed the context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return mixed the days
     */
    public function getDays()
    {
        return $this->days;
    }

    /**
     * returns the previously set javasscript insert-function only
     * if read_only is not set.
     *
     * @return  string  name of js-function or anonymous js-function
     */
    public function getInsertFunction() {
        if (!$this->read_only) {
            return parent::getInsertFunction();
        }

        return false;
    }

    /**
     * returns all columns of the calendar-view nad removes the url if
     * read_only is set
     *
     * @return  array  of CalendarColumn
     */
    public function getColumns() {
        if ($this->read_only) {
            foreach ($this->entries as $column) {
                $column->setURL(false);
                foreach ($column->entries as $key => $entry) {
                    unset($column->entries[$key]['url']);
                    unset($column->entries[$key]['onClick']);
                    unset($column->entries[$key]['icons']);
                }
            }
        }

        return parent::getColumns();
    }

    /**
     * outputs the CalendarView with all (grouped) dates in columns.
     *
     * @param  array  $params  you can pass some additional variables to the templates
     *
     * @return string
     */
    public function render($params = array())
    {
        $style_parameters = array(
            'whole_height' => $this->getOverallHeight(),
            'entry_height' => $this->getHeight()
        );
        $factory =  new Flexi_TemplateFactory(dirname(__FILE__));

        PageLayout::addStyle($factory->render('stylesheet', $style_parameters));

        $template = $factory->open("calendar_view.php");
        $template->set_attribute("calendar_view", $this);
        $template->set_attribute("view_id", $this->view_id);
        $ret = $template->render($params);
        //$ret .= $template->render_partial('_entry.php');
        //$ret .= $template->render_partial('_entry_details');
        return $ret;
    }
}
