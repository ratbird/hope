<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* export.inc.php
*
*
*
* @author       Peter Thienel <pthienel@web.de>
* @access       public
* @package      caldender
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// export.inc.php
//
// Copyright (c) 2003 Peter Tienel <pthienel@web.de>
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

//Imports
require_once($RELATIVE_PATH_CALENDAR.'/lib/sync/CalendarImportFile.class.php');
require_once($RELATIVE_PATH_CALENDAR.'/lib/sync/CalendarParserICalendar.class.php');
require_once($RELATIVE_PATH_CALENDAR.'/lib/sync/CalendarExportFile.class.php');
require_once($RELATIVE_PATH_CALENDAR.'/lib/sync/CalendarWriterICalendar.class.php');
require_once($RELATIVE_PATH_CALENDAR.'/lib/sync/CalendarSynchronizer.class.php');
require_once('lib/msg.inc.php');

if ($experiod != 'period') {
    unset($exstartmonth);
    unset($exstartday);
    unset($exstartyear);
    unset($exendtmonth);
    unset($exendday);
    unset($exendyear);
}

if (!isset($calendar_sess_export))
    $sess->register('calendar_sess_export');

// direct "one-button-export(tm)" of an event-object
if ($expmod == 'exp_direct' && $termin_id) {
    $export = new CalendarExportFile(new CalendarWriterICalendar());

    if ($evtype == 'sem')
        $exp_event = array(new SeminarEvent($termin_id));
    else
        $exp_event = array(new DbCalendarEvent($termin_id));

    $export->exportFromObjects($exp_event);
    $export->sendFile();

    page_close();
    exit;
}

$err = array();
if ($experiod == 'period') {
    if (!$exstart = check_date($exstartmonth, $exstartday, $exstartyear, 0, 0))
        $err['exstart'] = TRUE;
    if (!$exend = check_date($exendmonth, $exendday, $exendyear, 23, 59))
        $err['exend'] = TRUE;
    if ($exstart >= $exend)
        $err['exend'] = TRUE;
}

if (($expmod != 'exp' && $expmod != 'imp' && $expmod != 'sync') || ($expmod == 'exp' && !empty($err)))
{
    include('lib/include/html_head.inc.php');

    //TODO: 2mal body?, ob das so geht?
    print_js_import();
    echo "\n<body onUnLoad=\"upload_end()\">";

    include('lib/include/header.php');
}

if (($expmod != 'exp' && $expmod != 'imp' && $expmod != 'sync') || ($expmod == 'exp' && !empty($err))) {

    echo "<table border=\"0\" class=\"blank\" cellspacing=\"0\" cellpadding=\"5\" width=\"100%\">\n";

    if (!empty($err)) {
        $error_sign = "<font color=\"#FF0000\" size=\"+2\"><b>&nbsp;*&nbsp;</b></font>";
        $error_message = sprintf(_("Bitte korrigieren Sie die mit %s gekennzeichneten Felder.%s"),
            $error_sign, $err_message);
        my_info($error_message, "blank", 2);
    }

    // print messages
    if ($calendar_sess_export['msg'] != '') {
        parse_msg($calendar_sess_export['msg']);
        unset($calendar_sess_export['msg']);
    }

    echo "<tr valign=\"top\">\n";
    echo "<td width=\"99%\" nowrap class=\"blank\">\n";
    echo "<table align=\"center\" width=\"100%\" class=\"blank\" border=\"0\" cellpadding=\"5\" cellspacing=0>\n";

    if ($expmod == 'syncrec') {

        $info = array();
        if ($calendar_sess_export['count_synchronized']) {
            $info['sync'] = sprintf(_("Stud.IP hat %s Termine synchronisiert."),
                                $calendar_sess_export['count_synchronized']);
        }
        else {
            $info['sync'] = _("Ihr iCalendar-Datei enthielt keine Termine, die synchronisiert werden konnten.");
        }

        if ($calendar_sess_export['count_export']) {
            $info['export'] = sprintf(_("Die zum Download zur Verf&uuml;gung stehende Datei enthält %s Termine."),
                                $calendar_sess_export['count_export']);
        }
        else {
            $info['export'] = _("Ihr Stud.IP-Kalender enthält keine neueren Termine als die hochgeladene iCalendar-Datei. Es wurde keine Ausgabedatei erzeugt.");
        }

        $info['all'][0]['kategorie'] = _("Information:");
        $info['all'][0]['eintrag'][] = array("icon" => "icons/16/black/info.png",
                'text' => $info['sync']);
        $info['all'][0]['eintrag'][] = array('icon' => 'blank.gif',
                'text' => $info['export']);

        if ($calendar_sess_export['count_export']) {
            $send_sync = GetDownloadLink($tmpfile, $file, 2, 'force');
            $params['content'] = _("Klicken Sie auf den Button, um die Datei mit den synchronisierten Kalenderdaten herunterzuladen.")
                    . _("Die Daten liegen ebenfalls in einer iCalendar-Datei vor, die Sie in Ihren lokalen Terminkalender (z.B. MS Outlook) importieren können.");
            $params['button'] = "<input type=\"image\" " . makeButton("herunterladen", "src"). " border=\"0\">";
        }
        else {
            $send_sync = "$PHP_SELF?cmd=export&atime=$atime";
            $params['content'] = $info['export'];
            $params['button'] = "<input type=\"image\" " . makeButton("zurueck", "src"). " border=\"0\">";
        }
        $params['form'] = "<form action=\"$send_sync\" method=\"post\">\n"
            . CSRFProtection::tokenTag();
        $send_file = "";

        echo "<tr><th align=\"left\" width=\"100%\">\n<font size=\"-1\">";
        echo _("Herunterladen der synchronisierten Kalenderdaten")."</font>\n</th></tr>\n";
        print_cell($params);
        echo "</table\n</td>\n";

        echo "<td class=\"blank\" align=\"right\" valign=\"top\" width=\"270\">\n";
        print_infobox($info['all'], "infobox/dates.jpg");
    }
    else {

        // if javascript enabled display icon for popup calendar
        if ($auth->auth["jscript"]) {
            $insert_date_start = "&nbsp;"
                . "<img align=\"absmiddle\" src=\"".Assets::image_path('popupcalendar.png')."\" "
                . "onClick=\"window.open('termin_eingabe_dispatch.php?element_switch=9', 'InsertDate', "
                . "'dependent=yes, width=210, height=210, left=500, top=150')\">";
            $insert_date_end = "&nbsp;"
                . "<img align=\"absmiddle\" src=\"".Assets::image_path('popupcalendar.png')."\" "
                . "onClick=\"window.open('termin_eingabe_dispatch.php?element_switch=10', 'InsertDate', "
                . "'dependent=yes, width=210, height=210, left=500, top=150')\">";
        }
        else {
            $insert_date_start = '';
            $insert_date_end = '';
        }

        // add skip link
        SkipLinks::addIndex(_("Exportieren Ihrer Kalenderdaten"), 'calendar_export', 100);

        echo "<tr><th align=\"left\" width=\"100%\">\n<font size=\"-1\">";
        echo _("Exportieren Ihrer Kalenderdaten")."</font>\n</th></tr>\n";

        $tooltip = _("Es werden nur Termine von Veranstaltungen exportiert, die zuvor im Menüpunkt \"Veranstaltungstermine\" ausgewählt wurden.");
        $params['form'] = "<form name=\"Formular\" action=\"$PHP_SELF?cmd=export&atime=$atime\" method=\"post\" id=\"calendar_export\">\n"
            . CSRFProtection::tokenTag();
        $params['content'] = _("Bitte w&auml;hlen Sie, welche Termine exportiert werden sollen:") . "</font></div>\n"
                . "<br>&nbsp; &nbsp; <select name=\"extype\" size=\"1\">\n"
                . "<option value=\"PERS\"" . ($extype == 'PERS' ? 'selected="selected"' : '')
                . ">" . _("Nur meine pers&ouml;nlichen Termine") . "</option>\n"
                . "<option value=\"SEM\"" . ($extype == 'SEM' ? 'selected="selected"' : '')
                . ">" . _("Nur meine Veranstaltungstermine") . "</option>\n"
                . "<option value=\"ALL\"" . ($extype == 'ALL' ? 'selected="selected"' : '')
                . ">" . _("Alle Termine") . "</option>\n</select>"
                . "&nbsp;&nbsp;&nbsp;<img src=\""
                . $GLOBALS['ASSETS_URL']."images/icons/16/grey/info-circle.png\"" . tooltip($tooltip, TRUE, TRUE) . ">\n"
                . "<br>&nbsp;\n<div><font size=\"-1\">"
                . _("Geben Sie an, aus welchem Zeitbereich Termine exportiert werden sollen:")
                . "</div><br>\n&nbsp; &nbsp; <input type=\"radio\" name=\"experiod\" value=\"all\" ";
        if ($experiod != 'period')
            $params['content'] .= "checked=\"checked\"";
        $params['content'] .= ">\n"
                . "&nbsp;" . _("Alle Termine") . "<br>\n"
                . "&nbsp; &nbsp; <input type=\"radio\" name=\"experiod\" value=\"period\" ";
        if ($experiod == 'period')
            $params['content'] .= "checked=\"checked\"";
        $params['content'] .= ">\n"
                . "&nbsp;"
                . sprintf(_("Nur Termine vom:%sbis zum:"),
                            " &nbsp <input type=\"text\" name=\"exstartday\" size=\"2\" maxlength=\"2\" value=\""
                        . ($exstartday ? $exstartday : date("d", time())) . "\">.&nbsp;\n"
                        . "<input type=\"text\" name=\"exstartmonth\" size=\"2\" maxlength=\"2\" value=\""
                        . ($exstartmonth ? $exstartmonth : date("m", time())) . "\">.&nbsp;\n"
                        . "<input type=\"text\" name=\"exstartyear\" size=\"4\" maxlength=\"4\" value=\""
                        . ($exstartyear ? $exstartyear : date("Y", time())) . "\">"
                        . ($err['exstart'] ? $error_sign : '')
                        . $insert_date_start . "&nbsp &nbsp; \n")
                . " &nbsp; <input type=\"text\" name=\"exendday\" size=\"2\" maxlength=\"2\" value=\""
                . ($exendday ? $exendday : date("d", time())) . "\">.&nbsp;\n"
                . "<input type=\"text\" name=\"exendmonth\" size=\"2\" maxlength=\"2\" value=\""
                . ($exendmonth ? $exendmonth : date("m", time())) . "\">.&nbsp;\n"
                . "<input type=\"text\" name=\"exendyear\" size=\"4\" maxlength=\"4\" value=\""
                . ($exendyear ? $exendyear : date("Y", time()) + 1) . "\">\n"
                . ($err['exend'] ? $error_sign : '')
                . $insert_date_end;
        $params['button'] = "<input type=\"image\" " . makeButton("export", "src"). " border=\"0\">";
        $params['expmod'] = "exp";
        print_cell($params);

        // add skip link
        SkipLinks::addIndex(_("Importieren Ihrer Kalenderdaten"), 'calendar_import');

        echo "<tr><th colspan=\"2\" align=\"left\" width=\"100%\">\n<font size=\"-1\">";
        echo _("Importieren Ihrer Kalenderdaten")."</font>\n</th></tr>\n";

        $params['form'] = "<form action=\"$PHP_SELF?cmd=export&atime=$atime\" method=\"post\" "
                . "enctype=\"multipart/form-data\" name=\"import_form\" id=\"calendar_import\">\n"
            . CSRFProtection::tokenTag();
        $params['content'] = _("Sie k&ouml;nnen Termine importieren, die sich in einer iCalendar-Datei befinden.")
                . "<br><br>" . _("Klicken Sie auf \"Durchsuchen\", um eine Datei auszuwählen.")
                . "</div>\n<br>&nbsp; &nbsp; <input type=\"file\" name=\"importfile\" size=\"40\">\n";
        $params['button'] = "<input value=\"Senden\" type=\"image\" " . makeButton("dateihochladen", "src"). " onClick=\"return upload_start(document.import_form);\" "
                . "name=\"create\" border=\"0\">\n";
        $params['expmod'] = 'imp';
        print_cell($params);

        // add skip link
        SkipLinks::addIndex(_("Synchronisieren Ihrer Kalenderdaten"), 'calendar_sync');

        echo "<tr><th colspan=\"2\" align=\"left\" width=\"100%\">\n<font size=\"-1\">";
        echo _("Synchronisieren Ihrer Kalenderdaten")."</font>\n</th></tr>\n";

        $params['form'] = "<form action=\"$PHP_SELF?cmd=export&atime=$atime\" method=\"post\" "
                . "enctype=\"multipart/form-data\" name=\"sync_form\" id=\"calendar_sync\">\n"
            . CSRFProtection::tokenTag();
        $params['content'] = _("Sie k&ouml;nnen Termine synchronisieren, die sich in einer iCalendar-Datei befinden.")
                . "<br><br>" . _("Klicken Sie auf \"Durchsuchen\", um eine Datei auszuwählen.")
                . "</div>\n<br>&nbsp; &nbsp; <input type=\"file\" name=\"importfile\" size=\"40\">\n";
        $params['button'] = "<input value=\"Senden\" type=\"image\" " . makeButton("dateihochladen", "src"). " onClick=\"return upload_start(document.sync_form);\" "
                . "name=\"create\" border=\"0\">\n";
        $params['expmod'] = 'sync';
        print_cell($params);

        if ($expmod == 'impdone') {
            if ($calendar_sess_export['count_import']) {
                $info['import'] = sprintf(_("Es wurden %s Termine importiert."),
                                    $calendar_sess_export['count_import']);
                unset($calendar_sess_export['count_import']);
            }
            else {
                $info['import'] = _("Es wurden keine Termine importiert.");
            }

            $info['all'][0]['kategorie'] = _("Information:");
            $info['all'][0]['eintrag'][] = array("icon" => "icons/16/black/info.png",
                    'text' => $info['import']);
        }
        else {
            $info['all'][0]['kategorie'] = _("Information:");
            $info['all'][0]['eintrag'][] = array("icon" => "icons/16/black/info.png",
                    'text' => _("Sie k&ouml;nnen Termindaten importieren, exportieren und synchronisieren."));
        }
        echo "</table\n</td>\n";

        echo "<td class=\"blank\" align=\"right\" valign=\"top\" width=\"1%\" valign=\"top\">\n";
        print_infobox($info['all'], "infobox/dates.jpg");
    }

    echo "</td>\n";
    echo "</tr>\n";
    echo "<tr><td class=\"blank\" colspan=\"2\">&nbsp;</td></tr>\n";
    echo "</table>\n";
    echo "</td>\n";
    echo "</tr>\n";
    echo "</table>\n";
    echo "</td></tr></table>\n";
}
elseif ($expmod == 'exp' && empty($err)) {

    switch ($extype) {
        case 'ALL':
            $extype = 'ALL_EVENTS';
            break;
        case 'SEM':
            $extype = 'SEMINAR_EVENTS';
            break;
        default:
            $extype = 'CALENDAR_EVENTS';
    }

    $export = new CalendarExportFile(new CalendarWriterICalendar());
    if ($experiod != 'period')
        $export->exportFromDatabase($user->id, 0, 2114377200, $extype, $bind_seminare);
    else
        $export->exportFromDatabase($user->id, $exstart, $exend, $extype, $bind_seminare);

    if ($_calendar_error->getMaxStatus(ERROR_CRITICAL)) {
        $calendar_sess_export['msg'] = 'error§' . _("Der Export konnte nicht durchgef&uuml;hrt werden!");
        while ($error = $_calendar_error->nextError(ERROR_CRITICAL))
            $calendar_sess_export['msg'] .= '<br>' . $error->getMessage();
        while ($error = $_calendar_error->nextError(ERROR_FATAL))
            $calendar_sess_export['msg'] .= '<br>' . $error->getMessage();

        page_close();
        header("Location: $PHP_SELF?cmd=export&atime=$atime&");
        exit;
    }

    $export->sendFile();

}
elseif ($expmod == 'imp') {

    $import = new CalendarImportFile(new CalendarParserICalendar(),
            $_FILES['importfile']);

    $import_count = $import->getCount();

    if ($import_count < ($CALENDAR_MAX_EVENTS - $count_events)) {

        $import->importIntoDatabase();

        if ($_calendar_error->getMaxStatus(ERROR_CRITICAL)) {
            $calendar_sess_export['count_import'] = 0;
            $calendar_sess_export['msg'] = 'error§' . _("Der Import konnte nicht durchgef&uuml;hrt werden!");
            while ($error = $_calendar_error->nextError(ERROR_CRITICAL))
                $calendar_sess_export['msg'] .= '<br>' . $error->getMessage();
            while ($error = $_calendar_error->nextError(ERROR_FATAL))
                $calendar_sess_export['msg'] .= '<br>' . $error->getMessage();
        }
        else {
            $calendar_sess_export['count_import'] = $import->getCount();
            $calendar_sess_export['msg'] = 'msg§' . _("Der Import wurde erfolgreich durchgef&uuml;hrt!");
        }
    }
    else {
        $calendar_sess_export['msg'] = 'error§' . _("Der Import konnte nicht durchgef&uuml;hrt werden!");
        $calendar_sess_export['msg'] .= '<br>' . _("Die zu importierende Datei enth&auml;lt zuviele Termine.");
    }

    page_close();
    header("Location: $PHP_SELF?cmd=export&expmod=impdone&atime=$atime");
    exit;

}
elseif ($expmod == 'sync') {

    $import = new CalendarImportFile(new CalendarParserICalendar(),
            $_FILES['importfile']);

    $export = new CalendarExportFile(new CalendarWriterICalendar());

    $synchronizer = new CalendarSynchronizer($import, $export,
            $CALENDAR_MAX_EVENTS - $count_events);
    $synchronizer->synchronize();

    if ($_calendar_error->getMaxStatus(ERROR_CRITICAL)) {
        unset($calendar_sess_export['count_import']);
        unset($calendar_sess_export['count_export']);
        unset($calendar_sess_export['count_synchronized']);
        $calendar_sess_export['msg'] = 'error§' . _("Die Synchronisation konnte nicht durgef&uuml;hrt werden!");
        while ($error = $_calendar_error->nextError(ERROR_CRITICAL))
            $calendar_sess_export['msg'] .= '<br>' . $error->getMessage();
        while ($error = $_calendar_error->nextError(ERROR_FATAL))
            $calendar_sess_export['msg'] .= '<br>' . $error->getMessage();
        $location = "Location: $PHP_SELF?cmd=export&atime=$atime";
    }
    else {
        $calendar_sess_export['count_import'] = $import->getCount();
        $calendar_sess_export['count_export'] = $export->getCount();
        $calendar_sess_export['count_synchronized'] = $synchronizer->getCount();
        $calendar_sess_export['msg'] = 'msg§' . _("Die Synchronisation wurde erfolgreich durchgef&uuml;hrt!");
        $location = "Location: $PHP_SELF?cmd=export&expmod=syncrec&tmpfile="
            . $export->getTempFileName() . "&file=" . $export->getFileName() . "&atime=$atime";
    }

    page_close();
    header($location);
    exit;

}

/**
 * Enter description here...
 *
 * @param Array $params
 */
function print_cell ($params)
{
    echo "<tr><td width=\"100%\" class=\"steel1\">\n";
    echo $params['form'];
    echo "<div><font size=\"-1\">";
    echo $params['content'];
    echo "<div style=\"text-align:center; vertical-align:middle;\">\n";
    echo "&nbsp;\n";
    echo "<div style=\"text-align:center; vertical-align:middle;\">\n";
    echo $params['button'];
    echo "<input type=\"hidden\" name=\"expmod\" value=\"{$params['expmod']}\">\n";
    echo "</div>\n</form>\n</td></tr>\n";
}

?>
