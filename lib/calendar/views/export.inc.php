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

use Studip\Button, Studip\LinkButton;

require_once($RELATIVE_PATH_CALENDAR . '/lib/sync/CalendarImportFile.class.php');
require_once($RELATIVE_PATH_CALENDAR . '/lib/sync/CalendarParserICalendar.class.php');
require_once($RELATIVE_PATH_CALENDAR . '/lib/sync/CalendarExportFile.class.php');
require_once($RELATIVE_PATH_CALENDAR . '/lib/sync/CalendarWriterICalendar.class.php');
require_once($RELATIVE_PATH_CALENDAR . '/lib/sync/CalendarSynchronizer.class.php');
require_once('app/models/ical_export.php');
require_once('lib/msg.inc.php');
$experiod = Request::option('experiod');
$expmod = Request::option('expmod');
$extype = Request::option('extype');
$exendday = Request::option('exendday');
$exendmonth = Request::option('exendmonth');
$exendyear = Request::option('exendyear');
$exstartday = Request::option('exstartday');
$exstartmonth = Request::option('exstartmonth');
$exstartyear = Request::option('exstartyear');
if ($experiod != 'period') {
    unset($exstartmonth);
    unset($exstartday);
    unset($exstartyear);
    unset($exendtmonth);
    unset($exendday);
    unset($exendyear);
}


// direct "one-button-export(tm)" of an event-object
if ($expmod == 'exp_direct' && $termin_id) {
    $export = new CalendarExportFile(new CalendarWriterICalendar());

    if (Request::option('evtype') == 'sem') {
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
    if (!$exstart = check_date(Request::option('exstartmonth'), Request::option('exstartday'), Request::option('exstartyear'), 0, 0)) {
        $err['exstart'] = true;
    }
    if (!$exend = check_date(Request::option('exendmonth'), Request::option('exendday'), Request::option('exendyear'), 23, 59)) {
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
        $error_sign = "<span style=\"color:#FF0000; font-size:1.2em; font-weight:bold;\">&nbsp;*&nbsp;</span>";
        $error_message = sprintf(_("Bitte korrigieren Sie die mit %s gekennzeichneten Felder.%s"), $error_sign, $err_message);
        my_info($error_message, "blank", 2);
    }

    // print messages
    if ($_SESSION['calendar_sess_export']['msg'] != '') {
        parse_msg($_SESSION['calendar_sess_export']['msg']);
        unset($_SESSION['calendar_sess_export']['msg']);
    }

    echo "<tr valign=\"top\">\n";
    echo "<td width=\"99%\" nowrap class=\"blank\">\n";

    if (Request::get('delid') && check_ticket(Request::get('ticket'))) {
        IcalExport::deleteKey($GLOBALS['user']->id);
        echo MessageBox::success(_("Die Adresse, unter der Ihre Termine abrufbar sind, wurde gelöscht!"));
    }
    if (Request::get('newid') && check_ticket(Request::get('ticket'))) {
        $short_id = IcalExport::setKey($GLOBALS['user']->id);
        echo MessageBox::success(_("Eine Adresse, unter der Ihre Termine abrufbar sind, wurde erstellt."));
    } else {
        $short_id = IcalExport::getKeyByUser($GLOBALS['user']->id);
    }

    if (Request::submitted('submit_email')) {
    $email_reg_exp = '/^([-.0-9=?A-Z_a-z{|}~])+@([-.0-9=?A-Z_a-z{|}~])+\.[a-zA-Z]{2,6}$/i';
    if (preg_match($email_reg_exp, Request::get('email')) !== 0) {
        $subject = '[' . get_config('UNI_NAME_CLEAN') . ']' . _("Exportadresse für Ihre Termine");
        $text .= _("Diese Email wurde vom Stud.IP-System verschickt. Sie können
auf diese Nachricht nicht antworten.") . "\n\n";
        $text .= _("Über diese Adresse erreichen Sie den Export für Ihre Termine:") . "\n\n";
        $text .= $GLOBALS['ABSOLUTE_URI_STUDIP'] . 'dispatch.php/ical/index/' . IcalExport::getKeyByUser($GLOBALS['user']->id);
        StudipMail::sendMessage(Request::get('email'), $subject, $text);
        echo MessageBox::success(_("Die Adresse wurde verschickt!"));
    } else {
        echo MessageBox::error(_("Bitte geben Sie eine gültige Email-Adresse an."));
    }

}

    echo "<table align=\"center\" width=\"100%\" class=\"blank\" border=\"0\" cellpadding=\"5\" cellspacing=0>\n";

    if ($expmod == 'syncrec') {

        $info = array();
        if ($_SESSION['calendar_sess_export']['count_synchronized']) {
            $info['sync'] = sprintf(_("Stud.IP hat %s Termine synchronisiert."), $_SESSION['calendar_sess_export']['count_synchronized']);
        } else {
            $info['sync'] = _("Ihre iCalendar-Datei enthielt keine Termine, die synchronisiert werden konnten.");
        }

        if ($_SESSION['calendar_sess_export']['count_export']) {
            $info['export'] = sprintf(_("Die zum Download zur Verf&uuml;gung stehende Datei enthält %s Termine."), $_SESSION['calendar_sess_export']['count_export']);
        } else {
            $info['export'] = _("Ihr Stud.IP-Kalender enthält keine neueren Termine als die hochgeladene iCalendar-Datei. Es wurde keine Ausgabedatei erzeugt.");
        }

        $info['all'][0]['kategorie'] = _("Information:");
        $info['all'][0]['eintrag'][] = array("icon" => "icons/16/black/info.png",
            'text' => $info['sync']);
        $info['all'][0]['eintrag'][] = array('icon' => 'blank.gif',
            'text' => $info['export']);

        if ($_SESSION['calendar_sess_export']['count_export']) {
            $send_sync = GetDownloadLink($tmpfile, $file, 2, 'force');
            $params['content'] = _("Klicken Sie auf den Button, um die Datei mit den synchronisierten Kalenderdaten herunterzuladen.")
                    . _("Die Daten liegen ebenfalls in einer iCalendar-Datei vor, die Sie in Ihren lokalen Terminkalender (z.B. MS Outlook) importieren können.");
            $params['button'] = Button::create(_('herunterladen'));
        } else {
            $send_sync = URLHelper::getLink('', array('cmd' => 'export', 'atime' => $atime));
            $params['content'] = $info['export'];
            $params['button'] = Button::create(_('<< zurück'));
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
        $params['button'] = Button::create(_('export'));
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
            $params['button'] = Button::create(_('Datei hochladen'), 'create', array('onclick' => 'onClick=\"return STUDIP.OldUpload.upload_start(document.import_form);'));
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
            $params['button'] = Button::create(_('Senden'), 'create', array('onclick' => 'onClick=\"return STUDIP.OldUpload.upload_start(document.sync_form);'));
            $params['expmod'] = 'sync';
            print_cell($params);

            // add skip link
            SkipLinks::addIndex(_("Ihre Termine in externen Kalendern anzeigen"), 'calendar_include');

            echo "<tr><th align=\"left\">\n";
            echo _("Einbinden Ihrer Termine in externe Kalender")."</th></tr>\n";
            echo "<tr><td class=\"table_row_even\" id=\"calendar_include\">\n";
            if ($short_id) {
                echo _("Die folgende Adresse können Sie in externe Terminkalenderanwendungen eintragen, um Ihre Termine dort anzuzeigen:");
                $url = URLHelper::getLink($GLOBALS['ABSOLUTE_URI_STUDIP'] . 'dispatch.php/ical/index/' . $short_id);

                echo '<p style="font-weight: bold;"><a href="' . $url . '" target="_blank">' . htmlReady($url) . '</a></p>';
                echo '<p>';
                printf(_("%sNeue Adresse generieren.%s (Achtung: Die alte Adresse wird damit ungültig!)"), '<a href="' . URLHelper::getLink('', array('cmd' => 'export', 'newid' => '1', 'ticket' => get_ticket())) . '">', '</a>');
                echo '</p><p>';
                printf(_("%sAdresse löschen.%s (Ein Zugriff auf Ihre Termine über diese Adresse ist dann nicht mehr möglich!)"), '<a href="' . URLHelper::getLink('', array('cmd' => 'export', 'delid' => '1', 'ticket' => get_ticket())) . '">', '</a>');
                echo '</p>';
                echo '<form action="' . URLHelper::getLink('') . '" method="post">';
                echo CSRFProtection::tokenTag();
                echo '<p>' . _("Verschicken Sie die Export-Andresse als Email:");
                echo ' <input type="email" name="email" value="' . htmlReady($GLOBALS['user']->email) . '" required="required"></input>';
                echo '<input type="hidden" name="cmd" value="export"></input>';
                echo Button::create(_('Abschicken'), 'submit_email', array('title' => _('Abschicken')));
                echo '</p></form>';
            } else {
                echo '<p>';
                echo _("Sie können sich eine Adresse generieren lassen, mit der Sie Termine aus Ihrem Stud.IP-Terminkalender in externen Terminkalendern einbinden können.");
                echo '</p><p>';
                echo '<a href="' . URLHelper::getLink('', array('cmd' => 'export', 'newid' => '1', 'ticket' => get_ticket())) . '">';
                echo _("Adresse generieren!");
                echo '</a></p>';
            }
            echo "</td></tr>\n";
        }

        if ($expmod == 'impdone') {
            if ($_SESSION['calendar_sess_export']['count_import']) {
                $info['import'] = sprintf(_("Es wurden %s Termine importiert."), $_SESSION['calendar_sess_export']['count_import']);
                unset($_SESSION['calendar_sess_export']['count_import']);
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
        $_SESSION['calendar_sess_export']['msg'] = 'error§' . _("Der Export konnte nicht durchgef&uuml;hrt werden!");
        while ($error = $_calendar_error->nextError(ErrorHandler::ERROR_CRITICAL)) {
            $_SESSION['calendar_sess_export']['msg'] .= '<br>' . $error->getMessage();
        }
        while ($error = $_calendar_error->nextError(ErrorHandler::ERROR_FATAL)) {
            $_SESSION['calendar_sess_export']['msg'] .= '<br>' . $error->getMessage();
        }

        page_close();
        header("Location: ".URLHelper::getLink('?cmd=export&atime=$atime&'));
        exit;
    }

    $export->sendFile();
} elseif ($expmod == 'imp') {

    $import = new CalendarImportFile(new CalendarParserICalendar(),
                    $_FILES["importfile"]);

    if (Request::get('import_sem_imp')) {
        $import->setImportSem(true);
    }

    if (Request::get('import_as_private_imp')) {
        $import->changePublicToPrivate();
    }

    $import_count = $import->getCount();

    if ($import_count < ($CALENDAR_MAX_EVENTS - $count_events)) {

        $import->importIntoDatabase($_calendar->getUserId());

        if ($_calendar_error->getMaxStatus(ErrorHandler::ERROR_CRITICAL)) {
            $_SESSION['calendar_sess_export']['count_import'] = 0;
            $_SESSION['calendar_sess_export']['msg'] = 'error§' . _("Der Import konnte nicht durchgef&uuml;hrt werden!");
            while ($error = $_calendar_error->nextError(ErrorHandler::ERROR_CRITICAL))
                $_SESSION['calendar_sess_export']['msg'] .= '<br>' . $error->getMessage();
            while ($error = $_calendar_error->nextError(ErrorHandler::ERROR_FATAL))
                $_SESSION['calendar_sess_export']['msg'] .= '<br>' . $error->getMessage();
        } else {
            $_SESSION['calendar_sess_export']['count_import'] = $import->getCount();
            $_SESSION['calendar_sess_export']['msg'] = 'msg§' . _("Der Import wurde erfolgreich durchgef&uuml;hrt!");
            if ($_calendar_error->getMaxStatus(ErrorHandler::ERROR_WARNING)) {
                $warnings = array();
                $_SESSION['calendar_sess_export']['msg'] .= '§info§';
                while ($error = $_calendar_error->nextError(ErrorHandler::ERROR_WARNING)) {
                    $warnings[] = $error->getMessage();
                }
                $_SESSION['calendar_sess_export']['msg'] .= implode('<br />', $warnings);
            }
        }
    } else {
        $_SESSION['calendar_sess_export']['msg'] = 'error§' . _("Der Import konnte nicht durchgef&uuml;hrt werden!");
        $_SESSION['calendar_sess_export']['msg'] .= '<br>' . _("Die zu importierende Datei enth&auml;lt zuviele Termine.");
    }

    page_close();
    header("Location: ".URLHelper::getLink('?cmd=export&expmod=impdone&atime='.$atime));
    exit;
} elseif ($expmod == 'sync') {

    $import = new CalendarImportFile(new CalendarParserICalendar(),
                    $_FILES["importfile"]);

    if (Request::get('import_sem_sync')) {
        $import->setImportSem(true);
    }

    if (Request::get('import_as_private_sync')) {
        $import->changePublicToPrivate();
    }

    $export = new CalendarExportFile(new CalendarWriterICalendar());

    $synchronizer = new CalendarSynchronizer($import, $export);
    $synchronizer->setMaxEvents($CALENDAR_MAX_EVENTS - $count_events);
    $synchronizer->synchronize($_calendar->getUserId());

    if ($_calendar_error->getMaxStatus(ErrorHandler::ERROR_CRITICAL)) {
        unset($_SESSION['calendar_sess_export']['count_import']);
        unset($_SESSION['calendar_sess_export']['count_export']);
        unset($_SESSION['calendar_sess_export']['count_synchronized']);
        $_SESSION['calendar_sess_export']['msg'] = 'error§' . _("Die Synchronisation konnte nicht durchgef&uuml;hrt werden!");
        while ($error = $_calendar_error->nextError(ErrorHandler::ERROR_CRITICAL))
            $_SESSION['calendar_sess_export']['msg'] .= '<br />' . $error->getMessage();
        while ($error = $_calendar_error->nextError(ErrorHandler::ERROR_FATAL))
            $_SESSION['calendar_sess_export']['msg'] .= '<br />' . $error->getMessage();
        $location = "Location: ".URLHelper::getLink('?cmd=export&atime='.$atime);
    } else {
        $_SESSION['calendar_sess_export']['count_import'] = $import->getCount();
        $_SESSION['calendar_sess_export']['count_export'] = $export->getCount();
        $_SESSION['calendar_sess_export']['count_synchronized'] = $synchronizer->getCount();
        $_SESSION['calendar_sess_export']['msg'] = 'msg§' . _("Die Synchronisation wurde erfolgreich durchgef&uuml;hrt!");
        while ($error = $_calendar_error->nextError(ErrorHandler::ERROR_MESSAGE))
            $_SESSION['calendar_sess_export']['msg'] .= '<br />' . $error->getMessage();
        $warnings = array();
        while ($error = $_calendar_error->nextError(ErrorHandler::ERROR_WARNING))
            $warnings[] = $error->getMessage();
        if (sizeof($warnings)) {
            $_SESSION['calendar_sess_export']['msg'] .= '§info§' . implode('<br />', $warnings);
        }
        $location = "Location: ". URLHelper::getLink('?cmd=export&expmod=syncrec&tmpfile='. $export->getTempFileName() . '&file=' . $export->getFileName() . '&atime='.$atime);
    }

    page_close();
    header($location);
    exit;
}

/**
 * Prints a table row and inserts the values given by the params array.
 *
 * @param Array $params Array with values to insert in the table row.
 */
function print_cell($params)
{

    echo "<tr><td width=\"100%\" class=\"table_row_even\">\n";
    echo $params['form'];
    echo '<div>';
    echo $params['content'];
    echo "<div style=\"text-align:center; vertical-align:middle;\">\n";
    echo "&nbsp;\n";
    echo "<div style=\"text-align:center; vertical-align:middle;\">\n";
    echo $params['button'];
    echo "<input type=\"hidden\" name=\"expmod\" value=\"{$params['expmod']}\">\n";
    echo "</div>\n</form>\n</td></tr>\n";
}
