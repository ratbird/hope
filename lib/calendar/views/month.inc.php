<?
# Lifter002: TODO
# Lifter005: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/**
 * month.inc.php
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
require_once $RELATIVE_PATH_CALENDAR . '/lib/DbCalendarMonth.class.php';
include 'lib/include/html_head.inc.php';

$view = $_calendar->toStringMonth($atime, $step, Request::int('cal_restrict'), Calendar::getBindSeminare($_calendar->getUserId()));

include 'lib/include/header.php';

// add skip link
SkipLinks::addIndex(_("Monatsansicht"), 'main_content', 100);

echo "<table id=\"main_content\" width=\"100%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\" align=\"center\">\n";
if ($GLOBALS['CALENDAR_GROUP_ENABLE']) {
    echo "<tr><td class=\"blank\" width=\"25%\" nowrap=\"nowrap\">\n";
    echo $GLOBALS['template_factory']->render('calendar/_jump_to', compact('atime', 'cmd'));
    echo "</td><td class=\"blank\" width=\"25%\" nowrap=\"nowrap\">\n";
    echo $GLOBALS['template_factory']->render('calendar/_select_category', compact('atime', 'cmd'));
    echo "</td><td class=\"blank\" width=\"50%\">";
    echo $GLOBALS['template_factory']->render('calendar/_select_calendar', compact('_calendar', 'atime', 'cmd'));
    echo "</td></tr>\n";
} else {
    echo "<tr><td class=\"blank\" nowrap=\"nowrap\" colspan=\"2\">\n";
    echo $GLOBALS['template_factory']->render('calendar/_jump_to', compact('atime', 'cmd'));
    echo "</td><td class=\"blank\">";
    echo $GLOBALS['template_factory']->render('calendar/_select_category', compact('atime', 'cmd'));
    echo "</td></tr>\n";
}
echo "<tr><td class=\"blank\" colspan=\"3\" width=\"100%\" align=\"center\">\n";
echo $view;
echo "</td></tr><tr><td  colspan=\"3\" align=\"center\" class=\"blank\">\n";
echo "<br />&nbsp;";
