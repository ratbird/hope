<?
# Lifter002: TODO
# Lifter003: TEST
# Lifter007: TODO
# Lifter010: TODO

/**
 * edit.inc.php
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Peter Thienel <thienel@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     calendar
 */

//TODO: templates
include('lib/include/html_head.inc.php');
include('lib/include/header.php');

// add skip link
SkipLinks::addIndex(_("Termine anlegen/bearbeiten"), 'main_content', 100);

echo "<table width=\"100%\" class=\"blank\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
echo "<tr><td class=\"blank\" valign=\"top\">\n";
echo "<table class=\"blank\" width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">\n";

if (!empty($err)) {
    $error_sign = "<span style=\"color:#FF0000; font-weight:bold; font-size:1.2em;\">&nbsp;*&nbsp;</span>";
    $error_message = sprintf(_("Bitte korrigieren Sie die mit %s gekennzeichneten Felder.%s"), $error_sign, $err_message);
    my_info($error_message, 'blank', 2);
}

if (!$termin_id && !$_calendar->havePermission(Calendar::PERMISSION_WRITABLE)) {
    if ($_calendar->getRange() == Calendar::RANGE_USER) {
        $error_message = sprintf(_("Der Kalender von %s ist f&uuml;r Sie nur lesbar. Sie haben keine Berechtigung Termine anzulegen."), get_fullname($_calendar->getUserId()));
    } else {
        $error_message = sprintf(_("Der Kalender von %s ist f&uuml;r Sie nur lesbar. Sie haben keine Berechtigung Termine anzulegen."), $SessSemName[1]);
    }
    my_error($error_message, 'blank', 2, TRUE);
    echo "<tr><td class=\"blank\" width=\"15%\">&nbsp;</td>";
    echo '<td class="blank" width="85%"><a href="' . URLHelper::getLink('', array('cmd' => $calendar_sess_control_data['view_prv'], 'atime' => $atime)) . '">';
    echo makeButton("zurueck") . "</a></td></tr>\n";
    echo "</table><br />&nbsp;<br /></td></tr></table>\n";
    page_close();
    exit;
}
echo "<tr><td class=\"blank\" width=\"99%\" valign=\"top\">\n";

if ($evtype == 'semcal' || (isset($_calendar->event) && ($_calendar->event instanceof SeminarEvent || $_calendar->event instanceof SeminarCalendarEvent)
//      || $_calendar->checkPermission(Calendar::PERMISSION_READABLE)
//      || $_calendar->event->getPermission() == Event::PERMISSION_CONFIDENTIAL) {
        || !$_calendar->event->havePermission(Event::PERMISSION_WRITABLE))) {
    // form is not editable
    $disabled = " style=\"color:#000000; background-color:#FFFFFF;\" disabled=\"disabled\"";
} else {
    $disabled = '';
}

echo '<form name="edit_event" action="' . URLHelper::getLink('', array('cmd' => 'edit')) . '" method="post">';
echo CSRFProtection::tokenTag();
echo "<table class=\"blank\" width=\"99%\" border=\"0\" cellspacing=\"0\" cellpadding=\"10\">\n";
echo "<tr><th width=\"100%\" align=\"left\">";
echo $edit_mode_out;
echo "\n</th></tr>\n";

$css_switcher = new cssClassSwitcher();
$css_switcher->switchClass();

########################################################################################

if (!$set_recur_x) {
    if (isset($_calendar->event) && ($_calendar->event instanceof SeminarEvent
            || $_calendar->event instanceof SeminarCalendarEvent)) {
        echo "<tr>\n<td class=\"" . $css_switcher->getClass() . "\" width=\"100%\">\n";
        echo _("Veranstaltung") . ":&nbsp; ";
        echo htmlReady($_calendar->event->getSemName());
        echo "</td>\n</tr>\n";
        $css_switcher->switchClass();
    }

    echo "<tr>\n<td class=\"" . $css_switcher->getClass() . "\" width=\"100%\">\n";
    echo "<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
    echo "<tr>\n<td>";
    echo _("Beginn:") . " </td>\n<td> &nbsp;";
    echo _("Tag");
    echo " <input type=\"text\" name=\"start_day\" size=\"2\" maxlength=\"2\" value=\"";
    echo ((strlen($start_day) < 2) ? '0' . $start_day : $start_day) . "\"$disabled>\n";
    echo " . <input type=\"text\" name=\"start_month\" size=\"2\" maxlength=\"2\" value=\"";
    echo ((strlen($start_month) < 2) ? '0' . $start_month : $start_month) . "\"\"$disabled>\n";
    echo " . <input type=\"text\" name=\"start_year\" size=\"4\" maxlength=\"4\" ";
    echo "value=\"$start_year\"$disabled>\n";
    $atimetxt = ($start_day && $start_month && $start_year) ?
            '&atime=' . mktime(12, 0, 0, $start_month, $start_day, $start_year) : '';
    echo "&nbsp;";
    if (!(is_object($_calendar->event) && (($_calendar->event instanceof SeminarEvent) || !$_calendar->event->havePermission(Event::PERMISSION_WRITABLE)))) {
        /*
          if (!((isset($_calendar->event) && !($_calendar->event instanceof SeminarEvent))
          || !$_calendar->event->havePermission(Event::PERMISSION_WRITABLE))) {
         */
        echo Assets::img('popupcalendar.png', array('onClick' => "window.open('" . UrlHelper::getLink("termin_eingabe_dispatch.php?element_switch=start{$atimetxt}&form_name=edit_event&element_depending=end") . "', 'InsertDate', 'dependent=yes, width=210, height=210, left=500, top=150')", 'style' => 'vertical-align:middle;'));
    }
    echo "&nbsp; &nbsp;";
    echo _("Uhrzeit");
    echo " <select name=\"start_h\" size=\"1\"$disabled>\n";

    for ($i = 0; $i < 24; $i++) {
        echo "<option";
        if ($i == $start_h)
            echo " selected";
        if ($i < 10)
            echo ">0$i";
        else
            echo ">$i";
    }

    echo "</select> : <select name=\"start_m\" size=\"1\"$disabled>\n";

    for ($i = 0; $i < 60; $i += 5) {
        echo "<option";
        if ($i == $start_m)
            echo " selected";
        if ($i < 10)
            echo ">0$i";
        else
            echo ">$i";
    }

    echo "</select>";
    echo ($err["start_time"] ? $error_sign : "");
    echo "&nbsp; &nbsp; <input type=\"checkbox\" name=\"wholeday\"";
    echo "onClick=\"if (document.edit_event.elements['wholeday'].checked == true) ";
    echo "{document.edit_event.elements['start_h'].value = '00'; document.edit_event.elements['start_m'].value = '00'; ";
    echo "document.edit_event.elements['end_h'].value = '23'; document.edit_event.elements['end_m'].value = '55';}\"";
    echo ($wholeday ? ' checked="checked"' : '') . "$disabled> &nbsp;";
    echo _("ganzt&auml;gig");
    $info = _("Als ganztägig markierte Termine beginnen um 00:00 Uhr am angegebenen Starttag und enden um 23.59 am angegeben Endtag.");
    echo '&nbsp;&nbsp;&nbsp;<img src="' . Assets::image_path('icons/16/grey/info-circle.png') . '" ';
    echo tooltip($info, TRUE, TRUE) . ">\n";
    echo "</td>\n</tr>\n";
    echo "<tr><td colspan=\"2\">&nbsp;</td></tr>\n";
    echo '<tr><td>';
    echo _("Ende:") . ' </td><td> &nbsp;';
    echo _("Tag");
    echo " <input type=\"text\" name=\"end_day\" size=\"2\" maxlength=\"2\" value=\"";
    echo ((strlen($end_day) < 2) ? '0' . $end_day : $end_day) . "\"$disabled>\n";
    echo " . <input type=\"text\" name=\"end_month\" size=\"2\" maxlength=\"2\" value=\"";
    echo ((strlen($end_month) < 2) ? '0' . $end_month : $end_month) . "\"$disabled>\n";
    echo " . <input type=\"text\" name=\"end_year\" size=\"4\" maxlength=\"4\" value=\"$end_year\"$disabled>\n";

    $atimetxt = ($end_day && $end_month && $end_year) ?
            '&atime=' . mktime(12, 0, 0, $end_month, $end_day, $end_year) : '';
    echo '&nbsp;';
    if (!(is_object($_calendar->event) && (($_calendar->event instanceof SeminarEvent) || !$_calendar->event->havePermission(Event::PERMISSION_WRITABLE)))) {

        /*
          if (!((isset($_calendar->event) && strtolower(get_class($_calendar->event)) == 'seminarevent')
          || !$_calendar->event->havePermission(Event::PERMISSION_WRITABLE))) {
         */
        echo Assets::img('popupcalendar.png', array('onClick' => "window.open('" . UrlHelper::getLink("termin_eingabe_dispatch.php?element_switch=end{$atimetxt}&form_name=edit_event") . "', 'InsertDate', 'dependent=yes, width=210, height=210, left=500, top=150')", 'style' => 'vertical-align:middle;'));
    }
    echo "&nbsp; &nbsp;";
    echo _("Uhrzeit");
    echo " <select name=\"end_h\" size=\"1\"$disabled>\n";

    for ($i = 0; $i < 24; $i++) {
        echo "<option";
        if ($i == $end_h)
            echo " selected";
        if ($i < 10)
            echo ">0$i";
        else
            echo ">$i";
    }

    echo "</select>&nbsp;:&nbsp;<select name=\"end_m\" size=\"1\"$disabled>\n";

    for ($i = 0; $i < 60; $i += 5) {
        echo "<option";
        if ($i == $end_m)
            echo " selected";
        if ($i < 10)
            echo ">0$i";
        else
            echo ">$i";
    }

    echo "</select>";
    echo ($err["end_time"] ? $error_sign : "");
    echo "</td>\n</tr>\n</table>\n</td>\n</tr>\n";

    if ($_calendar->event->havePermission(Event::PERMISSION_READABLE)) {
        $css_switcher->switchClass();
        echo "<tr><td class=\"" . $css_switcher->getClass() . "\">\n";
        echo "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
        echo '<tr><td>';
        echo _("Zusammenfassung:") . "&nbsp;&nbsp;</td>\n";
        echo "<td>";
        echo '<input type="text" name="txt" size="50" maxlength="255" value="' . htmlReady($txt) . '"' . $disabled . '></input>';
        printf("%s</td>\n", ($err["titel"] ? $error_sign : ""));
        echo "</tr><tr>\n";
        echo "<tr><td colspan=\"2\">&nbsp;</td></tr>\n";
        echo '<td>';
        echo _("Beschreibung:") . "&nbsp;&nbsp;</td>";
        echo "<td><textarea name=\"content\" cols=\"48\" rows=\"5\" wrap=\"virtual\"$disabled>";
        echo htmlReady($content);
        echo "</textarea></td>\n";
        echo "</tr>\n</table>\n</td>\n</tr>\n";

        $css_switcher->switchClass();
        echo "<tr><td class=\"" . $css_switcher->getClass() . "\">\n";
        echo _("Kategorie:") . '&nbsp;&nbsp;';
        echo "<select name=\"cat\" size=\"1\"$disabled>\n";

        if (isset($_calendar->event) && $_calendar->event instanceof SeminarEvent) {
            if (!isset($cat))
                $cat = 1;
            printf("<option value=\"%s\" selected>%s", $cat, htmlReady($TERMIN_TYP[$cat]['name']));
            echo "</select>\n";
        }
        else {
            if (!isset($cat))
                $cat = 0;
            echo "<option value=\"0\"";
            if ($cat == 0)
                echo " selected=\"selected\"";
            echo " style=\"font-weight:bold;\">" . _("keine Auswahl");
            foreach ($PERS_TERMIN_KAT as $key => $value) {
                printf("<option value=\"%s\"", $key);
                if ($cat == $key) {
                    echo " selected=\"selected\"";
                }
                echo " style=\"color:{$value['color']}; font-weight:bold;\"";
                printf(">%s\n", htmlReady($value['name']));
            }
            echo "</select>\n&nbsp; &nbsp;";
            echo '<input type="text" name="cat_text" size="30" maxlength="255" value="' . htmlReady($cat_text) . '"' . $disabled . '>';
            $info = _("Sie können beliebige Kategorien in das Freitextfeld eingeben. Trennen Sie einzelne Kategorien bitte durch ein Komma.");
            echo '&nbsp;&nbsp;&nbsp;<img src="' . Assets::image_path('icons/16/grey/info-circle.png') . '" ';
            echo tooltip($info, TRUE, TRUE) . ">\n";
        }
        echo "</td>\n</tr>\n";

        $css_switcher->switchClass();
        echo "<tr><td class=\"" . $css_switcher->getClass() . "\">\n";
        echo _("Raum:") . "&nbsp;&nbsp;";
        echo '<input type="text" name="loc" size="30" maxlength="255" value="' . htmlReady($loc) . '"' . $disabled . '>';
        echo "</td>\n</tr>\n";
    }

    if (!($_calendar->event instanceof SeminarEvent)) {
        if ($_calendar->event->havePermission(Event::PERMISSION_WRITABLE) && !($_calendar->event instanceof SeminarCalendarEvent)) {
            $css_switcher->switchClass();
            echo "<tr><td class=\"" . $css_switcher->getClass() . "\">\n";
            echo _("Zugriff:") . "&nbsp;&nbsp;\n";
            echo "<select name=\"via\" size=\"1\"$disabled>\n";
            if ($_calendar->checkPermission(Calendar::PERMISSION_OWN)) {
                $info = _("Private und vertrauliche Termine sind nur für Sie sichtbar. Öffentliche Termine werden auf ihrer internen Homepage auch anderen Nutzern bekanntgegeben.");
                $via_names = array(
                    'PUBLIC' => _("&ouml;ffentlich"),
                    'PRIVATE' => _("privat"),
                    'CONFIDENTIAL' => _("vertraulich"));
            } elseif ($_calendar->getRange() == Calendar::RANGE_SEM || $_calendar->getRange() == Calendar::RANGE_INST) {
                $info = _("In Veranstaltungskalendern können nur private Termine angelegt werden");
                $via_names = array(
                    'PRIVATE' => _("privat")
                );
            } else {
                $info = _("Im Kalender eines anderen Nutzers können Sie nur private Termine einstellen.");
                $via_names = array(
                    'PRIVATE' => _("privat"),
                    'CONFIDENTIAL' => _("vertraulich"));
            }
            foreach ($via_names as $key => $via_name) {
                echo "<option value=\"$key\"";
                if ($via == $key)
                    echo " selected";
                echo ">$via_name\n";
            }
            echo "</select>&nbsp;&nbsp;&nbsp;";
            echo '<img src="' . Assets::image_path('icons/16/grey/info-circle.png') . '" ' . tooltip($info, TRUE, TRUE) . ">\n";

            echo "&nbsp;&nbsp;&nbsp;" . _("Priorit&auml;t:");
            echo "&nbsp;&nbsp;<select name=\"priority\" size=\"1\">\n";
            $priority_names = array(
                _("keine Angabe"),
                _("hoch"),
                _("mittel"),
                _("niedrig"));
            for ($i = 0; $i < 4; $i++) {
                echo "<option value=\"$i\"";
                if ($priority == $i)
                    echo " selected";
                echo ">{$priority_names[$i]}\n";
            }
            echo "</select></td>\n</tr>\n";
        }

        if ($_calendar->event instanceof SeminarCalendarEvent) {
            $css_switcher->switchClass();
            echo "<tr><td class=\"" . $css_switcher->getClass() . "\">\n";
            echo _("Priorit&auml;t:") . "&nbsp;&nbsp;<select $disabled name=\"priority\" size=\"1\">\n";
            $priority_names = array(
                _("keine Angabe"),
                _("hoch"),
                _("mittel"),
                _("niedrig"));
            for ($i = 0; $i < 4; $i++) {
                echo "<option value=\"$i\"";
                if ($priority == $i)
                    echo " selected";
                echo " />{$priority_names[$i]}\n";
            }
            echo '</select>';
            echo '<input type="hidden" name="via" value="PRIVATE">';
            echo "\n<input type=\"hidden\" name=\"evtype\" value =\"semcal\">\n";
            echo "</td>\n</tr>\n";
        }

        if ($_calendar instanceof GroupCalendar) {
            $css_switcher->switchClass();
            echo "<tr><td class=\"" . $css_switcher->getClass() . "\" valign=\"baseline\">";
            echo _("Eintragen in Kalender:") . '<br>&nbsp;&nbsp;';
            echo $GLOBALS['template_factory']->render('calendar/select_members', compact('_calendar'));
            echo "</td>\n</tr>\n";
        }

        $css_switcher->switchClass();
        echo "<tr><td class=\"" . $css_switcher->getClass() . "\">";
        if ($_calendar->event)
            echo htmlReady($_calendar->event->toStringRecurrence());
        echo "&nbsp; &nbsp; &nbsp;";

//      if ($_calendar->havePermission(Calendar::PERMISSION_WRITABLE)
        //      && $_calendar->event->getPermission() == CALENDAR_EVENT_PERM_PUBLIC) {
        if ($_calendar->event->havePermission(Event::PERMISSION_WRITABLE) && !($_calendar->event instanceof SeminarCalendarEvent)) {
            echo "<input style=\"vertical-align: middle;\" type=\"image\" ";
            echo makeButton("bearbeiten", "src") . " name=\"set_recur\" border=\"0\">\n";
        }
        //  elseif ($_calendar->event->getRepeat('rtype') != 'SINGLE') {
        else {
            echo "<input style=\"vertical-align: middle;\" type=\"image\" ";
            echo makeButton("details", "src") . " name=\"set_recur\" border=\"0\">\n";
        }

        echo "</td>\n</tr>\n";
    }
}

######################################################################################
else {

    if ($_calendar->havePermission(Calendar::PERMISSION_READABLE)) {
        if (!isset($_calendar->event) || !($_calendar->event instanceof SeminarEvent) || $evtype != 'semcal') {
            echo "<tr><td align=\"center\" class=\"" . $css_switcher->getClass();
            echo "\" colspan=\"2\" nowrap=\"nowrap\">\n&nbsp;";

            if ($_calendar->event->havePermission(Event::PERMISSION_WRITABLE) && $evtype != 'semcal') {

                if ($mod == "SINGLE")
                    echo "<input type=\"image\" name=\"mod_s\" " . makeButton("keine2", "src") . " border=\"0\">\n";
                else
                    echo "<input type=\"image\" name=\"mod_s\" " . makeButton("keine", "src") . " border=\"0\">\n";
                echo " ";
                if ($mod == "DAILY")
                    echo "<input type=\"image\" name=\"mod_d\" " . makeButton("taeglich2", "src") . " border=\"0\">\n";
                else
                    echo "<input type=\"image\" name=\"mod_d\" " . makeButton("taeglich", "src") . " border=\"0\">\n";
                echo " ";
                if ($mod == "WEEKLY")
                    echo "<input type=\"image\" name=\"mod_w\" " . makeButton("woechentlich2", "src") . " border=\"0\">\n";
                else
                    echo "<input type=\"image\" name=\"mod_w\" " . makeButton("woechentlich", "src") . " border=\"0\">\n";
                echo " ";
                if ($mod == "MONTHLY")
                    echo "<input type=\"image\" name=\"mod_m\" " . makeButton("monatlich2", "src") . " border=\"0\">\n";
                else
                    echo "<input type=\"image\" name=\"mod_m\" " . makeButton("monatlich", "src") . " border=\"0\">\n";
                echo " ";
                if ($mod == "YEARLY")
                    echo "<input type=\"image\" name=\"mod_y\" " . makeButton("jaehrlich2", "src") . " border=\"0\">\n";
                else
                    echo "<input type=\"image\" name=\"mod_y\" " . makeButton("jaehrlich", "src") . " border=\"0\">\n";
            } else {

                if ($mod == "SINGLE")
                    echo "<img " . makeButton("keine2", "src") . " border=\"0\">\n";
                else
                    echo "<img " . makeButton("keine", "src") . " border=\"0\">\n";
                echo " ";
                if ($mod == "DAILY")
                    echo "<img " . makeButton("taeglich2", "src") . " border=\"0\">\n";
                else
                    echo "<img " . makeButton("taeglich", "src") . " border=\"0\">\n";
                echo " ";
                if ($mod == "WEEKLY")
                    echo "<img " . makeButton("woechentlich2", "src") . " border=\"0\">\n";
                else
                    echo "<img " . makeButton("woechentlich", "src") . " border=\"0\">\n";
                echo " ";
                if ($mod == "MONTHLY")
                    echo "<img " . makeButton("monatlich2", "src") . " border=\"0\">\n";
                else
                    echo "<img " . makeButton("monatlich", "src") . " border=\"0\">\n";
                echo " ";
                if ($mod == "YEARLY")
                    echo "<img " . makeButton("jaehrlich2", "src") . " border=\"0\">\n";
                else
                    echo "<img " . makeButton("jaehrlich", "src") . " border=\"0\">\n";
            }

            echo "</td></tr>\n";
        }
    }

    if ($mod == "MONTHLY" || $mod == "YEARLY") {
        $form_week_arr = array(
            "1" => _("ersten"),
            "2" => _("zweiten"),
            "3" => _("dritten"),
            "4" => _("vierten"),
            "5" => _("letzten")
        );

        $form_day_arr = array(
            "1" => _("Montag"),
            "2" => _("Dienstag"),
            "3" => _("Mittwoch"),
            "4" => _("Donnerstag"),
            "5" => _("Freitag"),
            "6" => _("Samstag"),
            "7" => _("Sonntag")
        );

        $form_month_arr = array(
            "1" => _("Januar"),
            "2" => _("Februar"),
            "3" => _("M&auml;rz"),
            "4" => _("April"),
            "5" => _("Mai"),
            "6" => _("Juni"),
            "7" => _("Juli"),
            "8" => _("August"),
            "9" => _("September"),
            "10" => _("Oktober"),
            "11" => _("November"),
            "12" => _("Dezember")
        );
    }

    switch ($mod) {
        case "DAILY":
            $css_switcher->switchClass();
            echo "<tr>\n<td nowrap=\"nowrap\" class=\"" . $css_switcher->getClass() . "\">\n";
            echo "&nbsp; <input type=\"radio\" name=\"type_d\" value=\"daily\"";
            if ($type_d == "daily" || $type_d == "")
                echo " checked";
            echo "$disabled>&nbsp;" . _("Alle") . " &nbsp;";
            echo "<input type=\"text\" name=\"linterval_d\" size=\"3\" maxlength=\"3\" value=\"";
            echo ($linterval_d ? $linterval_d : "1");
            echo "\"$disabled>&nbsp;" . _("Tage");
            echo ($err["linterval_d"] ? $error_sign : "");
            echo "&nbsp; &nbsp; &nbsp; ";
            echo "<input type=\"radio\" name=\"type_d\" value=\"wdaily\"";
            if ($type_d == "wdaily")
                echo " checked";
            echo "$disabled>&nbsp;" . _("Jeden Werktag") . "</td>";
            echo "</td></tr>\n";
            break;

        case "WEEKLY":
            if (!is_array($wdays)) {
                $wdays = array(strftime('%u', mktime(0, 0, 0, $start_month, $start_day, $start_year)));
            }
            $css_switcher->switchClass();
            echo "<tr><td nowrap=\"nowrap\" class=\"" . $css_switcher->getClass() . "\">\n";
            echo "&nbsp; <label>";
            $out_1 = '<input type="text" name="linterval_w" size="3" maxlength="3" value="';
            $out_1 .= ( $linterval_w ? $linterval_w : "1");
            $out_1 .= '">';
            printf(_("Alle %s Wochen %s am:"), $out_1, $err["linterval_w"] ? $error_sign : "");
            echo "</label><table width=\"75%\" border=\"0\" cellpadding=\"3\" cellspacing=\"0\">\n";
            echo "<tr><td width=\"8%\" rowspan=\"2\">&nbsp;</td>\n<td width=\"23%\">";
            echo "<label><input type=\"checkbox\" name=\"wdays[]\" value=\"1\"";
            echo (in_array(1, $wdays) ? ' checked="checked"' : '');
            echo ">&nbsp;" . _("Montag") . "</label></td>\n";
            echo "<td width=\"23%\"><label><input type=\"checkbox\" name=\"wdays[]\" value=\"2\"";
            echo (in_array(2, $wdays) ? ' checked="checked"' : '');
            echo ">&nbsp;" . _("Dienstag") . "</label></td>\n";
            echo "<td width=\"23%\"><label><input type=\"checkbox\" name=\"wdays[]\" value=\"3\"";
            echo (in_array(3, $wdays) ? ' checked="checked"' : '');
            echo ">&nbsp;" . _("Mittwoch") . "</label></td>\n";
            echo "<td nowrap=\"nowrap\" width=\"23%\"><label><input type=\"checkbox\" name=\"wdays[]\" value=\"4\"";
            echo (in_array(4, $wdays) ? ' checked="checked"' : '');
            echo ">&nbsp;" . _("Donnerstag") . "</label></td>\n";
            echo "</tr><tr>\n";
            echo "<td><label><input type=\"checkbox\" name=\"wdays[]\" value=\"5\"";
            echo (in_array(5, $wdays) ? ' checked="checked"' : '');
            echo ">&nbsp;" . _("Freitag") . "</label></td>\n";
            echo "<td><label><input type=\"checkbox\" name=\"wdays[]\" value=\"6\"";
            echo (in_array(6, $wdays) ? ' checked="checked"' : '');
            echo ">&nbsp;" . _("Samstag") . "</label></td>\n";
            echo "<td colspan=\"2\"><label><input type=\"checkbox\" name=\"wdays[]\" value=\"7\"";
            echo (in_array(7, $wdays) ? ' checked="checked"' : '');
            echo ">&nbsp;" . _("Sonntag") . "</label></td>\n";
            echo "</tr>\n</table></td></tr>\n";
            break;

        case "MONTHLY":
            $css_switcher->switchClass();
            echo "<tr><td nowrap=\"nowrap\" class=\"" . $css_switcher->getClass() . "\">\n";
            echo "&nbsp; <input type=\"radio\" name=\"type_m\" value=\"day\"";
            if ($type_m == "day" || $type_m == "")
                echo " checked";
            echo ">&nbsp;";
            $out_1 = "&nbsp;";
            $out_1 .= "<input type=\"text\" name=\"day_m\" size=\"2\" maxlength=\"2\" value=\"";
            $out_1 .= ( $day_m != '' ? "$day_m" : "$start_day");
            $out_1 .= "\">" . ($err['day_m'] ? $error_sign : "") . "&nbsp;.&nbsp; ";
            $out_2 = "&nbsp;";
            $out_2 .= "<input type=\"text\" name=\"linterval_m1\" size=\"3\" maxlength=\"3\" value=\"";
            $out_2 .= ( $linterval_m1 != '' ? "$linterval_m1" : "1");
            $out_2 .= "\">" . ($err["linterval_m1"] ? $error_sign : "") . "&nbsp;";
            printf(_("Wiederholt am %s alle %s Monate"), $out_1, $out_2);
            echo "<br><br>&nbsp; <input type=\"radio\" name=\"type_m\" value=\"wday\"";
            if ($type_m == "wday")
                echo " checked";
            echo ">&nbsp;" . _("Jeden") . "&nbsp;";
            echo "<select name=\"sinterval_m\" size=\"1\">\n";

            foreach ($form_week_arr as $key => $value) {
                echo "<option value=\"$key\"";
                if ($sinterval_m == $key)
                    echo " selected";
                echo ">$value\n";
            }

            echo "</select>\n";
            echo "<select name=\"wday_m\" size=\"1\">\n";

            foreach ($form_day_arr as $key => $value) {
                echo "<option value=\"$key\"";
                if ($wday_m == $key)
                    echo " selected";
                echo ">$value\n";
            }

            echo "</select>\n";
            echo "&nbsp;" . _("alle");
            echo " &nbsp;<input type=\"text\" name=\"linterval_m2\" size=\"3\" maxlength=\"3\" value=\"";
            echo ($linterval_m2 ? $linterval_m2 : "1");
            echo "\">" . ($err["linterval_m2"] ? $error_sign : "");
            echo "&nbsp;" . _("Monate") . "</td></tr>\n";
            break;

        case "YEARLY":
            if (!$month_y1)
                $month_y1 = $start_month;
            if (!$month_y2)
                $month_y2 = $start_month;

            $css_switcher->switchClass();
            echo "<tr><td nowrap=\"nowrap\" class=\"" . $css_switcher->getClass() . "\">\n";
            echo "&nbsp; <input type=\"radio\" name=\"type_y\" value=\"day\"";
            if ($type_y == "day" || $type_y == "")
                echo " checked";
            echo ">&nbsp;" . _("Jeden") . "&nbsp; ";
            echo "<input type=\"text\" name=\"day_y\" size=\"2\" maxlength=\"2\" value=\"";
            echo ($day_y ? $day_y : $start_day);
            echo "\">" . ($err["day_y"] ? $error_sign : "");
            echo "&nbsp;.&nbsp;\n";
            echo "<select name=\"month_y1\" size=\"1\">\n";

            foreach ($form_month_arr as $key => $value) {
                echo "<option value=\"$key\"";
                if ($month_y1 == $key)
                    echo " selected";
                echo ">$value\n";
            }

            echo "</select>\n";
            echo "<br><br>&nbsp; <input type=\"radio\" name=\"type_y\" value=\"wday\"";
            if ($type_y == "wday")
                echo " checked";
            echo ">&nbsp;";
            $out_1 = "&nbsp; ";
            $out_1 .= "<select name=\"sinterval_y\" size=\"1\">\n";

            foreach ($form_week_arr as $key => $value) {
                $out_1 .= "<option value=\"$key\"";
                if ($sinterval_y == $key)
                    $out_1 .= " selected";
                $out_1 .= ">$value\n";
            }

            $out_1 .= "</select>\n<select name=\"wday_y\" size=\"1\">\n";

            foreach ($form_day_arr as $key => $value) {
                $out_1 .= "<option value=\"$key\"";
                if ($wday_y == $key)
                    $out_1 .= " selected";
                $out_1 .= ">$value\n";
            }

            $out_1 .= "</select>&nbsp;";
            printf(_("Jeden %s im"), $out_1);
            echo "&nbsp;<select name=\"month_y2\" size=\"1\">\n";

            foreach ($form_month_arr as $key => $value) {
                echo "<option value=\"$key\"";
                if ($month_y2 == $key)
                    echo " selected";
                echo ">$value\n";
            }
            echo "</select></td></tr>\n";
            break;
    }

    $css_switcher->switchClass();
    echo "<tr><td class=\"" . $css_switcher->getClass() . "\">";

    if ($mod != 'SINGLE') {
        // end of recurrence
        echo '<table border="0" cellspacing="0" cellpadding="0">';
        echo "\n<tr><td>&nbsp; ";
        echo _("Wiederholung endet:") . '</td>';
        echo "<td>&nbsp; ";
        echo "<input type=\"radio\" name=\"exp_c\" value=\"never\"";
        if ($exp_c == "never")
            echo " checked";
        echo "$disabled>" . _("nie");
        echo "<br>&nbsp; <input type=\"radio\" name=\"exp_c\" value=\"date\"";
        if ($exp_c == "date")
            echo " checked";
        echo "$disabled>" . _("am:");
        echo "&nbsp; <input type=\"text\" size=\"2\" maxlength=\"2\" name=\"exp_day\" value=\"";
        echo (($exp_day && $exp_c == "date") ? $exp_day : "TT");
        echo "\"$disabled>&nbsp;.&nbsp;";
        echo "<input type=\"text\" size=\"2\" maxlength=\"2\" name=\"exp_month\" value=\"";
        echo (($exp_month && $exp_c == "date") ? $exp_month : "MM");
        echo "\"$disabled>&nbsp;.&nbsp;";
        echo "<input type=\"text\" size=\"4\" maxlength=\"4\" name=\"exp_year\" value=\"";
        echo (($exp_year && $exp_c == "date") ? $exp_year : "JJJJ");
        echo "\"$disabled>" . ($err["exp_time"] ? $error_sign : "");

        // insert popup calendar
        $atimetxt = ($start_day && $start_month && $start_year) ?
                '&atime=' . mktime(12, 0, 0, $start_month, $start_day, $start_year) : '';
        echo '&nbsp;&nbsp;';

        if (!(is_object($_calendar->event) && (($_calendar->event instanceof SeminarEvent) || !$_calendar->event->havePermission(Event::PERMISSION_WRITABLE)))) {

            /*
              if (!((isset($_calendar->event) && strtolower(get_class($_calendar->event)) == 'seminarevent')
              || !$_calendar->event->havePermission(Event::PERMISSION_WRITABLE))) {
             */

            echo Assets::img('popupcalendar.png', array('onClick' => "window.open('" . UrlHelper::getLink("termin_eingabe_dispatch.php?element_switch=exp{$atimetxt}&form_name=edit_event&mcount=6") . "', 'InsertDate', 'dependent=yes, width=700, height=450, left=250, top=150')", 'style' => 'vertical-align:middle;'));
        }
        echo '<br>&nbsp; <input type="radio" name="exp_c" value="count"';
        if ($exp_c == "count")
            echo " checked";
        echo "$disabled>" . sprintf(_("nach %s Wiederholungen"), '&nbsp; <input type="text" size="3" maxlength="3" name="exp_count" value="'
                . (($exp_count && $exp_c == "count") ? $exp_count : '1') . "\"$disabled>"
                . ($err['exp_count'] ? $error_sign : '') . ' &nbsp;');
        echo "</td></tr>\n</table>\n";
        echo "</td>\n</tr>\n";

        // exceptions
        $css_switcher->switchClass();
        echo "<tr><td class=\"" . $css_switcher->getClass() . "\">";
        echo "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
        echo "<tr><td valign=\"middle\">\n";
        echo "<br>&nbsp; ";
        echo _("Ausnahmen:") . '&nbsp; ';

        if (!(is_object($_calendar->event) && (($_calendar->event instanceof SeminarEvent) || !$_calendar->event->havePermission(Event::PERMISSION_WRITABLE)))) {

            /*
              if (!((isset($_calendar->event) && strtolower(get_class($_calendar->event)) == 'seminarevent')
              || !$_calendar->event->havePermission(Event::PERMISSION_WRITABLE))) {
             */

            echo "<input type=\"text\" size=\"2\" maxlength=\"2\" name=\"exc_day\" value=\"TT\">";
            echo "&nbsp;.&nbsp;";
            echo "<input type=\"text\" size=\"2\" maxlength=\"2\" name=\"exc_month\" value=\"MM\">";
            echo "&nbsp;.&nbsp;";
            echo "<input type=\"text\" size=\"4\" maxlength=\"4\" name=\"exc_year\" value=\"JJJJ\">";
            echo ($err["exc_time"] ? $error_sign : "");
            echo '&nbsp;&nbsp;';

            // insert popup calendar
            echo Assets::img('popupcalendar.png', array('onClick' => "window.open('" . UrlHelper::getLink("termin_eingabe_dispatch.php?element_switch=exc{$atimetxt}&form_name=edit_event&mcount=6") . "', 'InsertDate', 'dependent=yes, width=700, height=450, left=250, top=150')", 'style' => 'vertical-align:middle;'));

            echo '&nbsp;&nbsp;';
            echo '<input type="image" src="' . Assets::image_path('icons/16/yellow/arr_2right.png');
            echo '" name="add_exc"' . tooltip(_("Ausnahme hinzufügen")) . ' align="absmiddle">';
            echo '&nbsp; &nbsp;</td>';
        }
        echo "<td>\n";
        echo "<select name=\"exc_delete[]\" size=\"4\" multiple=\"multiple\" style=\"width:170px; vertical-align:middle;\"$disabled>\n";
        foreach ($exceptions as $exception) {
            echo "<option value=\"$exception\">" . strftime('%A, %x', $exception);
            echo "</option>\n";
        }
        echo "</select>\n</td></tr>\n";
        echo "<tr><td>&nbsp;</td>\n<td>";

        if (!(is_object($_calendar->event) && (($_calendar->event instanceof SeminarEvent) || !$_calendar->event->havePermission(Event::PERMISSION_WRITABLE)))) {

            /*
              if (!((isset($_calendar->event) && strtolower(get_class($_calendar->event)) == 'seminarevent')
              || !$_calendar->event->havePermission(Event::PERMISSION_WRITABLE))) {
             */

            echo "<input style=\"vertical-align:middle;\" type=\"image\" ";
            echo ' src="' . Assets::image_path('icons/16/blue/trash.png') . '" name="del_exc"';
            echo tooltip(_("ausgewählte Ausnahme löschen")) . ">\n";
            echo _("ausgew&auml;hlte l&ouml;schen");
        }
        echo "</td></tr></table>\n</td>\n</tr>\n";
    } else {
        echo '&nbsp; ';
        echo _("Der Termin wird nicht wiederholt.");
        echo "</td>\n</tr>\n";
    }
}

#######################################################################################

if ($editor_id = $_calendar->event->getEditorId()) {
    $css_switcher->switchClass();
    echo "<tr><td class=\"" . $css_switcher->getClass() . "\">";
    echo sprintf(_("Termin geändert am %s von %s"), get_fullname($editor_id), strftime('%c', $_calendar->event->properties['LAST-MODIFIED']));
    echo "</td></tr>";
}


if ($termin_id) {
    if ($_calendar->event instanceof SeminarEvent) {
        $info_box['export_link'] = URLHelper::getLink('', array('cmd' => 'export', 'expmod' => 'exp_direct', 'termin_id' => $_calendar->event->getId(), 'evtype' => 'sem'));
    } else {
        $info_box['export_link'] = URLHelper::getLink('', array('cmd' => 'export', 'expmod' => 'exp_direct', 'termin_id' => $_calendar->event->getId()));
    }
    $info_box['export'] = array('icon' => 'icons/16/black/date.png',
        'text' => sprintf(_("Diesen Termin einzeln %sexportieren%s."), "<a href=\"{$info_box['export_link']}\">", "</a>"));
}

if (isset($_calendar->event) && ($_calendar->event instanceof SeminarEvent || $_calendar->event instanceof SeminarCalendarEvent || $evtype == 'semcal')) {
    $query = "SELECT name FROM seminare WHERE Seminar_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($_calendar->event->getSeminarId()));
    $name = $statement->fetchColumn();

    if ($_calendar->event instanceof SeminarCalendarEvent) {
        $link_to_seminar = '<a href="' . URLHelper::getLink('seminar_main.php', array('auswahl' => $_calendar->event->getSeminarId(), 'redirect_to' => 'calendar.php&cmd=edit&atime=' . $atime . '&termin_id=' . $_calendar->event->getId())) . '">' . htmlReady($name) . '</a>';
    } else {
        $link_to_seminar = '<a href="' . URLHelper::getLink('seminar_main.php', array('auswahl' => $_calendar->event->getSeminarId())) . '">' . htmlReady($name) . '</a>';
    }

    // create infobox entries
    switch ($_calendar->getRange()) {
        case Calendar::RANGE_USER :
            $info_box['sem1'] = sprintf(_("Dieser Termin geh&ouml;rt zur Veranstaltung:<p>%s</p>Veranstaltungstermine k&ouml;nnen nicht im pers&ouml;nlichen Terminkalender bearbeitet werden."), $link_to_seminar);
            break;
        case Calendar::RANGE_SEM :
            $info_box['sem1'] = _("Dieser Termin ist ein Termin aus dem Ablaufplan.");
            break;
        case Calendar::RANGE_INST :
            // events/dates at "Einrichtungen" are not implemented
            break;
    }
    $info_box['sem2'] = sprintf(_("%sWählen%s Sie aus, welche Veranstaltungstermine in Ihrem Terminkalender angezeigt werden sollen."), '<a href="' . URLHelper::getLink('', array('cmd' => 'bind')) . '">', '</a>');
    if ($GLOBALS['perm']->have_studip_perm('tutor', $_calendar->event->getSeminarId())) {
        if ($_calendar->event instanceof SeminarEvent) {
            $link_to_seminar = '<a href="' . URLHelper::getLink('raumzeit.php#' . $_calendar->event->getId(), array('cmd' => 'open', 'open_close_id' => $_calendar->event->getId(), 'cid' => $_calendar->event->getSeminarId())) . '">';
            $info_box['sem3'] = sprintf(_("Um diesen Termin zu bearbeiten, wechseln Sie bitte in die %sTerminverwaltung%s.")
                    , $link_to_seminar, '</a>');
            $info_box['all'][1]['eintrag'][] = array('icon' => 'icons/16/black/admin.png', 'text' => $info_box['sem3']);
        }

        $info_box['all'][0]['kategorie'] = _("Information:");
        $info_box['all'][0]['eintrag'][] = array('icon' => 'icons/16/black/info.png',
            'text' => $info_box['sem1']);
        $info_box['all'][1]['kategorie'] = _("Aktion:");
        $info_box['all'][1]['eintrag'][] = array('icon' => 'icons/16/black/seminar.png',
            'text' => $info_box['sem2']);
        $info_box['all'][1]['eintrag'][] = $info_box['export'];
    } else {
        $info_box['all'][0]['kategorie'] = _("Information:");
        $info_box['all'][0]['eintrag'][] = array('icon' => 'icons/16/black/info.png',
            'text' => $info_box['sem1']);
        $info_box['all'][1]['kategorie'] = _("Aktion:");
        $info_box['all'][1]['eintrag'][] = array('icon' => "icons/16/black/add/date.png",
            'text' => $info_box['sem2']);
        $info_box['all'][1]['eintrag'][] = $info_box['export'];
    }



    $css_switcher->switchClass();
    echo "<tr><td class=\"" . $css_switcher->getClass() . "\" align=\"center\" nowrap=\"nowrap\">\n";
    echo "<input type=\"hidden\" name=\"atime\" value=\"$atime\">\n";
    echo "<input type=\"hidden\" name=\"mod_err\" value=\"$mod_err\">\n";
    echo "<input type=\"hidden\" name=\"mod_prv\" value=\"$mod\">\n";
    echo "<input type=\"hidden\" name=\"mod\" value=\"$mod\">\n";
    echo "<input type=\"hidden\" name=\"termin_id\" value=\"$termin_id\">\n";
    if ($set_recur_x) {
        echo "<input type=\"hidden\" name=\"evtype\" value=\"$evtype\">\n";
        echo "<input type=\"image\" " . makeButton("zurueck", "src") . " name=\"back_recur\" border=\"0\">\n";
        echo "&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; ";
        echo "<input type=\"hidden\" name=\"set_recur_x\" value=\"1\">\n";
        echo '<input type="hidden" name="wholeday" value="' . Request::get('wholeday') . "\">\n";
    }

    echo "<input type=\"image\" " . makeButton("zurueck", "src") . " border=\"0\" name=\"cancel\">\n";
} else {
    $css_switcher->switchClass();
    echo "<tr><td class=\"" . $css_switcher->getClass() . "\" align=\"center\" nowrap=\"nowrap\">\n";
    echo "<input type=\"hidden\" name=\"atime\" value=\"$atime\">\n";
    echo "<input type=\"hidden\" name=\"mod_err\" value=\"$mod_err\">\n";
    echo "<input type=\"hidden\" name=\"mod_prv\" value=\"$mod\">\n";
    echo "<input type=\"hidden\" name=\"mod\" value=\"$mod\">\n";
    echo "<input type=\"hidden\" name=\"termin_id\" value=\"$termin_id\">\n";
    if ($set_recur_x) {
        echo "<input type=\"image\" " . makeButton("zurueck", "src") . " name=\"back_recur\" border=\"0\">\n";
        echo "&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; ";
        echo "<input type=\"hidden\" name=\"set_recur_x\" value=\"1\">\n";
        echo '<input type="hidden" name="wholeday" value="' . Request::get('wholeday') . "\">\n";
    }
//  if ($_calendar->havePermission(Calendar::PERMISSION_WRITABLE)
    //      && $_calendar->event->getPermission() == CALENDAR_EVENT_PERM_PUBLIC) {
    if ($_calendar->event->havePermission(Event::PERMISSION_WRITABLE) && $evtype != 'semcal') {
        if ($atime && strtolower(get_class($_calendar->event)) == 'calendarevent') {
            if ($count_events < $CALENDAR_MAX_EVENTS) {
                echo "<input type=\"image\" " . makeButton("terminspeichern", "src") . " name=\"store\" border=\"0\">\n";
            }
        } else {
            echo "<input type=\"hidden\" name=\"termin_id\" value=\"$termin_id\">\n";
            echo "<input type=\"image\" " . makeButton("terminaendern", "src") . " border=\"0\" name=\"change\">&nbsp; &nbsp;";
            echo "<input type=\"image\" " . makeButton("loeschen", "src") . " border=\"0\" name=\"del\">\n";
        }
        echo "<input type=\"image\" " . makeButton("abbrechen", "src") . " border=\"0\" name=\"cancel\">\n";
    } elseif (!$set_recur_x || $evtype == 'semcal') {
        echo "<input type=\"image\" " . makeButton("zurueck", "src") . " border=\"0\" name=\"cancel\">\n";
    }

    // create infobox entries
    if ($count_events >= $CALENDAR_MAX_EVENTS) {
        // max number of events reached
        $info_box['count'] = _("Sie k&ouml;nnen keine weiteren Termine mehr speichern!")
                . '<br><br>'
                . sprintf(_("L&ouml;schen Sie &auml;ltere Termine, oder w&auml;hlen Sie eine automatische L&ouml;schfunktion in Ihren %sKalenderoptionen%s."), '<a href="' . URLHelper::getLink('edit_about.php', array('view' => 'calendar')) . '">', '</a>');
    } elseif ($count_events >= ($CALENDAR_MAX_EVENTS - $CALENDAR_MAX_EVENTS / 20)) {
        // only 5% of max number of events free
        $info_box['count'] = sprintf(_("Sie k&ouml;nnen noch %s Termine speichern."), $CALENDAR_MAX_EVENTS - $count_events);
        $info_box['count'] .= '<br><br>';
        $info_box['count'] .= sprintf(_("W&auml;hlen Sie eine automatische L&ouml;schfunktion in Ihren %sKalenderoptionen%s, um &auml;ltere Termine zu l&ouml;schen."), '<a href="' . URLHelper::getLink('edit_about.php', array('view' => 'calendar')) . '">', '</a>');
    } else {
        $info_box['count'] = sprintf(_("Sie k&ouml;nnen abgelaufene Termine automatisch l&ouml;schen lassen. W&auml;hlen Sie dazu eine L&ouml;schfunktion in Ihren %sKalenderoptionen%s."), '<a href="' . URLHelper::getLink('edit_about.php', array('view' => 'calendar')) . '">', '</a>');
    }
    $info_box['all'][0]['kategorie'] = _("Information:");
    $info_box['all'][0]['eintrag'][] = array('icon' => 'icons/16/black/info.png',
        'text' => $info_box['count']);
    if ($termin_id) {
        $info_box['all'][1]['kategorie'] = _("Aktion:");
        $info_box['all'][1]['eintrag'][] = $info_box['export'];
    }
}


echo "</td></tr></table></form>\n</td>\n";
echo "<td class=\"blank\" align=\"right\" valign=\"top\" width=\"270\">\n";
print_infobox($info_box['all'], "infobox/dates.jpg");
echo "</td></tr>\n";
echo "</table></td></tr></table><br>\n";
echo "</td></tr></table>\n";
