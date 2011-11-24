<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter010: TODO

/**
 * export.inc.php
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

require_once($RELATIVE_PATH_CALENDAR . '/lib/sync/CalendarImportFile.class.php');
require_once($RELATIVE_PATH_CALENDAR . '/lib/sync/CalendarParserICalendar.class.php');
require_once($RELATIVE_PATH_CALENDAR . '/lib/sync/CalendarExportFile.class.php');
require_once($RELATIVE_PATH_CALENDAR . '/lib/sync/CalendarWriterICalendar.class.php');
require_once($RELATIVE_PATH_CALENDAR . '/lib/sync/CalendarSynchronizer.class.php');
require_once('lib/msg.inc.php');

if ($experiod != 'period') {
    unset($exstartmonth);
    unset($exstartday);
    unset($exstartyear);
    unset($exendtmonth);
    unset($exendday);
    unset($exendyear);
}

if (!isset($calendar_sess_export)) {
    $sess->register('calendar_sess_export');
}

// direct "one-button-export(tm)" of an event-object
if ($expmod == 'exp_direct' && $termin_id) {
    $export = new CalendarExportFile(new CalendarWriterICalendar());

    if ($evtype == 'sem') {
        $exp_event = array(new SeminarEvent($termin_id));
    } else {
        $exp_event = array(new DbCalendarEvent($_calendar, $termin_id));
    }

    $export->exportFromObjects($exp_event);
    $export->sendFile();

    page_close();
    exit;
}

$err = array();
if ($experiod == 'period') {
    if (!$exstart = check_date($exstartmonth, $exstartday, $exstartyear, 0, 0)) {
        $err['exstart'] = true;
    }
    if (!$exend = check_date($exendmonth, $exendday, $exendyear, 23, 59)) {
        $err['exend'] = true;
    }
    if ($exstart >= $exend) {
        $err['exend'] = true;
    }
}

if (($expmod != 'exp' && $expmod != 'imp' && $expmod != 'sync') || ($expmod == 'exp' && !empty($err))) {
    include 'lib/include/html_head.inc.php';

    //TODO: 2mal body?, ob das so geht?
    print_js_import();
    echo "\n<body onUnLoad=\"STUDIP.OldUpload.upload_end()\">";

    include('lib/include/header.php');
}

if (($expmod != 'exp' && $expmod != 'imp' && $expmod != 'sync') || ($expmod == 'exp' && !empty($err))) {

    echo "<table border=\"0\" class=\"blank\" cellspacing=\"0\" cellpadding=\"5\" width=\"100%\">\n";

    if (!empty($err)) {
        $error_sign = "<span style=\"color:#FF0000 font-size:1.2em; font-weight:bold;>&nbsp;*&nbsp;</span>";
        $error_message = sprintf(_("Bitte korrigieren Sie die mit %s gekennzeichneten Felder.%s"), $error_sign, $err_message);
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
            $info['sync'] = sprintf(_("Stud.IP hat %s Termine synchronisiert."), $calendar_sess_export['count_synchronized']);
        } else {
            $info['sync'] = _("Ihre iCalendar-Datei enthielt keine Termine, die synchronisiert werden konnten.");
        }

        if ($calendar_sess_export['count_export']) {
            $info['export'] = sprintf(_("Die zum Download zur Verf&uuml;gung stehende Datei enthält %s Termine."), $calendar_sess_export['count_export']);
        } else {
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
            $params['button'] = "<input type=\"image\" " . makeButton("herunterladen", "src") . " border=\"0\">";
        } else {
            $send_sync = URLHelper::getLink('', array('cmd' => 'export', 'atime' => $atime));
            $params['content'] = $info['export'];
            $params['button'] = "<input type=\"image\" " . makeButton("zurueck", "src") . " border=\"0\">";
        }
        $params['form'] = "<form action=\"$send_sync\" method=\"post\">\n";
        $params['form'] .= CSRFProtection::tokenTag();
        $send_file = "";

        echo "<tr><th align=\"left\" width=\"100%\">\n";
        echo _("Herunterladen der synchronisierten Kalenderdaten") . "\n</th></tr>\n";
        print_cell($params);
        echo "</table\n</td>\n";

        echo "<td class=\"blank\" align=\"right\" valign=\"top\" width=\"1%\" valign=\"top\">\n";
        print_infobox($info['all'], "pictures/dates.jpg");
    } else {

        // add skip link
        SkipLinks::addIndex(_("Exportieren Ihrer Kalenderdaten"), 'calendar_export', 100);

        echo "<tr><th align=\"left\" width=\"100%\">\n";
        if ($_calendar->checkPermission(Calendar::PERMISSION_OWN)) {
            echo _("Exportieren Ihrer Kalenderdaten");
        } else if ($_calendar->getRange() == Calendar::RANGE_SEM || $_calendar->getRange() == Calendar::RANGE_INST) {
            echo _("Exportieren der Termine");
        } else {
            printf(_("Exportieren der Termine von %s"), get_fullname($_calendar->getUserId()));
        }
        echo "\n</th></tr>\n";

        $params['form'] = '<form name="Formular" action="' . URLHelper::getLink('', array('cmd' => 'export', 'atime' => $atime)) . "\" method=\"post\" id=\"calendar_export\">\n";
        $params['form'] .= CSRFProtection::tokenTag();

        $params['content'] = '';
        if ($_calendar->checkPermission(Calendar::PERMISSION_OWN)) {
            $tooltip = _("Es werden nur Termine von Veranstaltungen exportiert, die zuvor im Menüpunkt \"Veranstaltungstermine\" ausgewählt wurden.");
            $params['content'] = _("Bitte w&auml;hlen Sie, welche Termine exportiert werden sollen:") . "</div>\n"
                    . "<br>&nbsp; &nbsp; <select name=\"extype\" size=\"1\">\n"
                    . "<option value=\"PERS\"" . ($extype == 'PERS' ? 'selected="selected"' : '')
                    . ">" . _("Nur pers&ouml;nliche Termine") . "</option>\n"
                    . "<option value=\"SEM\"" . ($extype == 'SEM' ? 'selected="selected"' : '')
                    . ">" . _("Nur Veranstaltungstermine") . "</option>\n"
                    . "<option value=\"ALL\"" . ($extype == 'ALL' ? 'selected="selected"' : '')
                    . ">" . _("Alle Termine") . "</option>\n</select>"
                    . '&nbsp;&nbsp;&nbsp;' . Assets::img('icons/16/grey/info-circle.png', tooltip2($tooltip, true, true))
                    . "<br>&nbsp;\n";
        } else {
            if ($_calendar->getRange() == Calendar::RANGE_SEM || $_calendar->getRange() == Calendar::RANGE_INST) {
                $params['content'] = '<input type="hidden" name="extype" value="ALL">';
            } else {
                $params['content'] = _("Bitte w&auml;hlen Sie, welche Termine exportiert werden sollen:") . "</div>\n"
                        . "<br>&nbsp; &nbsp; <select name=\"extype\" size=\"1\">\n"
                        . "<option value=\"PERS\"" . ($extype == 'PERS' ? 'selected="selected"' : '')
                        . ">" . _("Nur pers&ouml;nliche Termine") . "</option>\n"
                        . "<option value=\"SEM\"" . ($extype == 'SEM' ? 'selected="selected"' : '')
                        . ">" . _("Nur Veranstaltungstermine") . "</option>\n"
                        . "<option value=\"ALL\"" . ($extype == 'ALL' ? 'selected="selected"' : '')
                        . ">" . _("Alle Termine") . "</option>\n</select>"
                        . "&nbsp;&nbsp;&nbsp;\n"
                        . "<br>&nbsp;\n";
            }
        }

        $params['content'] .= '<div>'
                . _("Geben Sie an, aus welchem Zeitbereich Termine exportiert werden sollen:")
                . "</div><br>\n&nbsp; &nbsp; <input type=\"radio\" name=\"experiod\" value=\"all\" ";
        if ($experiod != 'period') {
            $params['content'] .= "checked=\"checked\"";
        }
        $params['content'] .= ">\n"
                . "&nbsp;" . _("Alle Termine") . "<br>\n"
                . "&nbsp; &nbsp; <input type=\"radio\" name=\"experiod\" value=\"period\" ";
        if ($experiod == 'period') {
            $params['content'] .= "checked=\"checked\"";
        }

        if (!$exstartday)
            $exstartday = date("d", time());
        if (!$exstartmonth)
            $exstartmonth = date("m", time());
        if (!$exstartyear)
            $exstartyear = date("Y", time());

        // insert popup calendar
        $atimetxt = '&atime=' . mktime(12, 0, 0, $exstartday, $exstartmonth, $exstartyear);
        $text_exstart = " &nbsp <input type=\"text\" name=\"exstartday\" size=\"2\" maxlength=\"2\" value=\""
                . $exstartday . "\">.&nbsp;\n"
                . "<input type=\"text\" name=\"exstartmonth\" size=\"2\" maxlength=\"2\" value=\""
                . $exstartmonth . "\">.&nbsp;\n"
                . "<input type=\"text\" name=\"exstartyear\" size=\"4\" maxlength=\"4\" value=\""
                . $exstartyear . "\">"
                . ($err['exstart'] ? "&nbsp;$error_sign" : '')
                . ' <img align="absmiddle" src="' . Assets::image_path('popupcalendar.png')
                . "\" onClick=\"window.open('" . UrlHelper::getLink("termin_eingabe_dispatch.php?element_switch=54${atimetxt}")
                . "', 'InsertDate', 'dependent=yes, width=210, height=210, left=500, top=150')\">&nbsp;&nbsp;";

        if (!$exendday)
            $exendday = date("d", time());
        if (!$exendmonth)
            $exendmonth = date("m", time());
        if (!$exendyear)
            $exendyear = date("Y", time()) + 1;

        $atimetxt = '&atime=' . mktime(12, 0, 0, $exendday, $exendmonth, $exendyear);
        $text_exend = " &nbsp; <input type=\"text\" name=\"exendday\" size=\"2\" maxlength=\"2\" value=\""
                . $exendday . "\">.&nbsp;\n"
                . "<input type=\"text\" name=\"exendmonth\" size=\"2\" maxlength=\"2\" value=\""
                . $exendmonth . "\">.&nbsp;\n"
                . "<input type=\"text\" name=\"exendyear\" size=\"4\" maxlength=\"4\" value=\""
                . $exendyear . "\">"
                . ($err['exend'] ? "&nbsp;$error_sign" : '')
                . ' <img align="absmiddle" src="' . Assets::image_path('popupcalendar.png')
                . "\" onClick=\"window.open('" . UrlHelper::getLink("termin_eingabe_dispatch.php?element_switch=55${atimetxt}")
                . "', 'InsertDate', 'dependent=yes, width=210, height=210, left=500, top=150')\">";
        $params['content'] .= ">\n&nbsp;" . sprintf(_("Nur Termine vom:%sbis zum:%s"), $text_exstart, $text_exend);
        $params['button'] = "<input type=\"image\" " . makeButton("export", "src") . " border=\"0\">";
        $params['expmod'] = "exp";
        print_cell($params);

        if ($_calendar->checkPermission(Calendar::PERMISSION_OWN)) {

            // add skip link
            SkipLinks::addIndex(_("Importieren Ihrer Kalenderdaten"), 'calendar_import');

            echo "<tr><th colspan=\"2\" align=\"left\" width=\"100%\">\n";
            echo _("Importieren Ihrer Kalenderdaten") . "\n</th></tr>\n";
            $tooltip1 = _("Alle als \"öffentlich\" gekennzeichneten Termine in Ihrer Import-Datei werden in \"private\" Termine umgewandelt. Die Termine sind somit auf Ihrer persönlichen Homepage nicht für andere Nutzer sichtbar.");
            $tooltip2 = _("Unterdrückt den Import von Veranstaltungs-Terminen aus Veranstaltungen, die Sie abonniert haben.");
            $params['form'] = '<form action="' . URLHelper::getLink('', array('cmd' => 'export', 'atime' => $atime)) . "\" method=\"post\" "
                    . "enctype=\"multipart/form-data\" name=\"import_form\" id=\"calendar_import\">\n";
            $params['form'] .= CSRFProtection::tokenTag();
            $params['content'] = _("Sie k&ouml;nnen Termine importieren, die sich in einer iCalendar-Datei befinden.")
                    . "<br><br>&nbsp; &nbsp; <input type=\"checkbox\" name=\"import_as_private_imp\" value=\"1\" checked=\"checked\">\n&nbsp;"
                    . _("Alle &ouml;ffentlichen Termine als \"privat\" importieren.")
                    . '&nbsp;&nbsp;&nbsp;' . Assets::img('icons/16/grey/info-circle.png', tooltip2($tooltip1, true, true))
                    . "<br>&nbsp; &nbsp; <input type=\"checkbox\" name=\"import_sem_imp\" value=\"1\">\n&nbsp;"
                    . _("Termine aus abonnierten Veranstaltungen importieren.")
                    . '&nbsp;&nbsp;&nbsp;' . Assets::img('icons/16/grey/info-circle.png', tooltip2($tooltip2, true, true))
                    . "<br><br>" . _("Klicken Sie auf \"Durchsuchen\", um eine Datei auszuwählen.")
                    . "</div>\n<br>&nbsp; &nbsp; <input type=\"file\" name=\"importfile\" size=\"40\">\n";
            $params['button'] = "<input value=\"Senden\" type=\"image\" " . makeButton('dateihochladen', 'src') . " onClick=\"return STUDIP.OldUpload.upload_start(document.import_form);\" "
                    . "name=\"create\" border=\"0\">\n";
            $params['expmod'] = 'imp';
            print_cell($params);

            // add skip link
            SkipLinks::addIndex(_("Synchronisieren Ihrer Kalenderdaten"), 'calendar_sync');

            echo "<tr><th colspan=\"2\" align=\"left\" width=\"100%\">\n";
            echo _("Synchronisieren Ihrer Kalenderdaten") . "\n</th></tr>\n";

            $params['form'] = '<form action="' . URLHelper::getLink('', array('cmd' => 'export', 'atime' => $atime)) . "\" method=\"post\" "
                    . "enctype=\"multipart/form-data\" name=\"sync_form\" id=\"calendar_sync\">\n";
            $params['form'] .= CSRFProtection::tokenTag();
            $params['content'] = _("Sie k&ouml;nnen Termine synchronisieren, die sich in einer iCalendar-Datei befinden.")
                    . "<br><br>&nbsp; &nbsp; <input type=\"checkbox\" name=\"import_as_private_sync\" value=\"1\" checked=\"checked\">\n&nbsp;"
                    . _("Alle &ouml;ffentlichen Termine als \"privat\" importieren.")
                    . '&nbsp;&nbsp;&nbsp;' . Assets::img('icons/16/grey/info-circle.png', tooltip2($tooltip, true, true))
                    . "<br>&nbsp; &nbsp; <input type=\"checkbox\" name=\"import_sem_sync\" value=\"1\">\n&nbsp;"
                    . _("Termine aus abonnierten Veranstaltungen importieren.")
                    . '&nbsp;&nbsp;&nbsp;' . Assets::img('icons/16/grey/info-circle.png', tooltip2($tooltip2, true, true))
                    . "<br><br>" . _("Klicken Sie auf \"Durchsuchen\", um eine Datei auszuwählen.")
                    . "</div>\n<br>&nbsp; &nbsp; <input type=\"file\" name=\"importfile\" size=\"40\">\n";
            $params['button'] = "<input value=\"Senden\" type=\"image\" " . makeButton("dateihochladen", "src") . " onClick=\"return STUDIP.OldUpload.upload_start(document.sync_form);\" "
                    . "name=\"create\" border=\"0\">\n";
            $params['expmod'] = 'sync';
            print_cell($params);
        }

        if ($expmod == 'impdone') {
            if ($calendar_sess_export['count_import']) {
                $info['import'] = sprintf(_("Es wurden %s Termine importiert."), $calendar_sess_export['count_import']);
                unset($calendar_sess_export['count_import']);
            } else {
                $info['import'] = _("Es wurden keine Termine importiert.");
            }

            $info['all'][0]['kategorie'] = _("Information:");
            $info['all'][0]['eintrag'][] = array("icon" => "icons/16/black/info.png",
                'text' => $info['import']);
        } else {
            $info['all'][0]['kategorie'] = _("Information:");
            $info['all'][0]['eintrag'][] = array("icon" => "icons/16/black/info.png",
                'text' => _("Sie k&ouml;nnen Termindaten importieren, exportieren und synchronisieren."));
        }
        echo "</table>\n</td>\n";

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
} elseif ($expmod == 'exp' && empty($err)) {

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
    if ($experiod != 'period') {
        $export->exportFromDatabase($_calendar->getUserId(), 0, Calendar::CALENDAR_END, $extype, Calendar::getBindSeminare($_calendar->getUserId()));
    } else {
        $export->exportFromDatabase($_calendar->getUserId(), $exstart, $exend, $extype, Calendar::getBindSeminare($_calendar->getUserId()));
    }

    if ($_calendar_error->getMaxStatus(ErrorHandler::ERROR_CRITICAL)) {
        $calendar_sess_export['msg'] = 'error§' . _("Der Export konnte nicht durchgef&uuml;hrt werden!");
        while ($error = $_calendar_error->nextError(ErrorHandler::ERROR_CRITICAL)) {
            $calendar_sess_export['msg'] .= '<br>' . $error->getMessage();
        }
        while ($error = $_calendar_error->nextError(ErrorHandler::ERROR_FATAL)) {
            $calendar_sess_export['msg'] .= '<br>' . $error->getMessage();
        }

        page_close();
        header("Location: $PHP_SELF?cmd=export&atime=$atime&");
        exit;
    }

    $export->sendFile();
} elseif ($expmod == 'imp') {

    $import = new CalendarImportFile(new CalendarParserICalendar(),
                    $_FILES["importfile"]);

    if ($_REQUEST['import_sem_imp']) {
        $import->setImportSem(true);
    }

    if ($_REQUEST['import_as_private_imp']) {
        $import->changePublicToPrivate();
    }

    $import_count = $import->getCount();

    if ($import_count < ($CALENDAR_MAX_EVENTS - $count_events)) {

        $import->importIntoDatabase($_calendar->getUserId());

        if ($_calendar_error->getMaxStatus(ErrorHandler::ERROR_CRITICAL)) {
            $calendar_sess_export['count_import'] = 0;
            $calendar_sess_export['msg'] = 'error§' . _("Der Import konnte nicht durchgef&uuml;hrt werden!");
            while ($error = $_calendar_error->nextError(ErrorHandler::ERROR_CRITICAL))
                $calendar_sess_export['msg'] .= '<br>' . $error->getMessage();
            while ($error = $_calendar_error->nextError(ErrorHandler::ERROR_FATAL))
                $calendar_sess_export['msg'] .= '<br>' . $error->getMessage();
        } else {
            $calendar_sess_export['count_import'] = $import->getCount();
            $calendar_sess_export['msg'] = 'msg§' . _("Der Import wurde erfolgreich durchgef&uuml;hrt!");
            if ($_calendar_error->getMaxStatus(ErrorHandler::ERROR_WARNING)) {
                $warnings = array();
                $calendar_sess_export['msg'] .= '§info§';
                while ($error = $_calendar_error->nextError(ErrorHandler::ERROR_WARNING)) {
                    $warnings[] = $error->getMessage();
                }
                $calendar_sess_export['msg'] .= implode('<br />', $warnings);
            }
        }
    } else {
        $calendar_sess_export['msg'] = 'error§' . _("Der Import konnte nicht durchgef&uuml;hrt werden!");
        $calendar_sess_export['msg'] .= '<br>' . _("Die zu importierende Datei enth&auml;lt zuviele Termine.");
    }

    page_close();
    header("Location: $PHP_SELF?cmd=export&expmod=impdone&atime=$atime");
    exit;
} elseif ($expmod == 'sync') {

    $import = new CalendarImportFile(new CalendarParserICalendar(),
                    $_FILES["importfile"]);

    if ($_REQUEST['import_sem_sync']) {
        $import->setImportSem(true);
    }

    if ($_REQUEST['import_as_private_sync']) {
        $import->changePublicToPrivate();
    }

    $export = new CalendarExportFile(new CalendarWriterICalendar());

    $synchronizer = new CalendarSynchronizer($import, $export);
    $synchronizer->setMaxEvents($CALENDAR_MAX_EVENTS - $count_events);
    $synchronizer->synchronize($_calendar->getUserId());

    if ($_calendar_error->getMaxStatus(ErrorHandler::ERROR_CRITICAL)) {
        unset($calendar_sess_export['count_import']);
        unset($calendar_sess_export['count_export']);
        unset($calendar_sess_export['count_synchronized']);
        $calendar_sess_export['msg'] = 'error§' . _("Die Synchronisation konnte nicht durchgef&uuml;hrt werden!");
        while ($error = $_calendar_error->nextError(ErrorHandler::ERROR_CRITICAL))
            $calendar_sess_export['msg'] .= '<br />' . $error->getMessage();
        while ($error = $_calendar_error->nextError(ErrorHandler::ERROR_FATAL))
            $calendar_sess_export['msg'] .= '<br />' . $error->getMessage();
        $location = "Location: $PHP_SELF?cmd=export&atime=$atime";
    } else {
        $calendar_sess_export['count_import'] = $import->getCount();
        $calendar_sess_export['count_export'] = $export->getCount();
        $calendar_sess_export['count_synchronized'] = $synchronizer->getCount();
        $calendar_sess_export['msg'] = 'msg§' . _("Die Synchronisation wurde erfolgreich durchgef&uuml;hrt!");
        while ($error = $_calendar_error->nextError(ErrorHandler::ERROR_MESSAGE))
            $calendar_sess_export['msg'] .= '<br />' . $error->getMessage();
        $warnings = array();
        while ($error = $_calendar_error->nextError(ErrorHandler::ERROR_WARNING))
            $warnings[] = $error->getMessage();
        if (sizeof($warnings)) {
            $calendar_sess_export['msg'] .= '§info§' . implode('<br />', $warnings);
        }
        $location = "Location: $PHP_SELF?cmd=export&expmod=syncrec&tmpfile="
                . $export->getTempFileName() . "&file=" . $export->getFileName() . "&atime=$atime";
    }

    page_close();
    header($location);
    exit;
}

function print_cell($params)
{

    echo "<tr><td width=\"100%\" class=\"steel1\">\n";
    echo $params['form'];
    echo '<div>';
    echo $params['content'];
    echo "<div style=\"text-align:center; vertical-align:center;\">\n";
    echo "&nbsp;\n";
    echo "<div style=\"text-align:center; vertical-align:center;\">\n";
    echo $params['button'];
    echo "<input type=\"hidden\" name=\"expmod\" value=\"{$params['expmod']}\">\n";
    echo "</div>\n</form>\n</td></tr>\n";
}
