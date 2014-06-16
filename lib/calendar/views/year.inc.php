<?
# Lifter002: TODO
# Lifter005: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/**
 * year.inc.php
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
require_once $RELATIVE_PATH_CALENDAR . '/lib/DbCalendarYear.class.php';

ob_start();

$view = $_calendar->toStringYear($atime, Request::int('cal_restrict'), Calendar::getBindSeminare($_calendar->getUserId()));

// add skip links
SkipLinks::addIndex(_("Jahresansicht"), 'main_content', 100);
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
echo '</table>';
echo $view;

$template = $GLOBALS['template_factory']->open('layouts/base.php');
$template->content_for_layout = ob_get_clean();
echo $template->render();
