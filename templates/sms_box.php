<?php
if ($msg) { // if info ($msg) for user
    print ("<table cellpadding=\"5\" cellspacing=\"0\" border=\"0\" width=\"99%\"><tr><td valign=\"top\">");
    parse_msg($msg);
    print ("</td></tr></table>");
} ?>
<table cellpadding="3" cellspacing="0" border="0" width="100%">
    <tr>
        <td class="blank" align="right" valign="bottom">
        <? if ($cmd != "admin_folder" && !$sms_data['tmp']['move_to_folder']): ?>
            <?= \Studip\LinkButton::create(_('Neuer Ordner'), URLHelper::getURL("?cmd=admin_folder&cmd_2=new")) ?>
        <? endif; ?>
        </td>
    </tr>
</table> <?

// rename or make folder
if ($cmd == "admin_folder") {
    // we would like to make a new folder
    if (Request::option('cmd_2') == "new") {
        $tmp[0] = "new_folder";
        $tmp[1] = _("einen neuen Ordner anlegen");
        $tmp[2] = "new_folder_button";
        $tmp[3] = "";
        $tmp[4] = "";
    }
    // we would like to rename a folder
    if (Request::get('ren_folder')) {
        $tmp[0] = "new_foldername";
        $tmp[1] = _("einen bestehenden Ordner umbennen");
        $tmp[2] = "ren_folder_button";
        $tmp[3] = " value=\"".htmlready(stripslashes(return_val_from_key($my_messaging_settings["folder"][$sms_data["view"]], Request::get('ren_folder'))))."\"";
        $tmp[4] = "<input type=\"hidden\" name=\"orig_folder_name\" value=\"".htmlready(Request::get('ren_folder'))."\">";
    }
    $titel = "  <input type=\"text\" name=\"".$tmp[0]."\"".$tmp[3]." style=\"font-size: 8pt\">";
    echo "\n<form action=\"".URLHelper::getURL()."\" method=\"post\"><table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"99%\" align=\"center\"><tr>";
    echo CSRFProtection::tokenTag();
    printhead(0, 0, FALSE, "open", FALSE, ' ' . Assets::img('icons/16/blue/add/folder-empty.png', array('class' => 'text-top')) . ' ', $titel, FALSE);
    echo "</tr></table> ";
    $content_content = $tmp[1]."<div align=\"center\">".$tmp[4];
    $content_content .= \Studip\Button::create(_('Übernehmen'), $tmp[2], array('align' => 'absmiddle'));
    $content_content .= \Studip\Button::createCancel(_('Abbrechen'), 'a', array('align' => 'absmiddle'));
    $content_content .= " <div>";

    echo "\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"99%\" align=\"center\"><tr>";
    printcontent("99%",0, $content_content, FALSE);
    echo "</form></tr></table>";
}

// show standard folder
$count = count_messages_from_user($sms_data['view'], "AND folder=''");
$count_timefilter = count_x_messages_from_user($sms_data['view'], "all", $query_time_sort." AND folder=''");
$open = folder_openclose($sms_show['folder'][$sms_data['view']], "all");
if ($sms_data['tmp']['move_to_folder'] && $open == "close") {
    $picture = 'icons/16/yellow/arr_2right.png';
    $link = URLHelper::getLink("?move_folder=free");
} else {
    $picture = showfoldericon("all", $count);
}
if (!$sms_data['tmp']['move_to_folder']) {
    $link = folder_makelink("all");
    $link_add = "&cmd_show=openall";
}
$titel = "<a href=\"".$link."\" class=\"tree\" >".$info_text_002."</a>";
$symbol = "<a href=\"".$link.$link_add."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/".$picture."\"></a>";
echo "\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"99%\" align=\"center\"><tr>";
$zusatz = show_nachrichtencount($count, $count_timefilter);
printhead(0, 0, $link, $open, FALSE, "<a href=\"".$link.$link_add."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/".$picture."\"></a>", $titel, $zusatz);
echo "</tr></table>";
if (!$move_to_folder) {
    $content_content = "<div align=\"center\">
        <form action=\"".URLHelper::getURL()."\" method=\"post\" style=\"display: inline\">" .
        CSRFProtection::tokenTag() .
        "<div class=\"button-group\"><input type=\"hidden\" name=\"cmd\" value=\"select_all\">"
        . \Studip\Button::create(_('Alle auswählen'), 'select', array('align' => 'absmiddle')) .
        "</form>
        <form action=\"".URLHelper::getURL()."\" method=\"post\" style=\"display: inline\">".
        CSRFProtection::tokenTag() .
        \Studip\Button::create(_('Löschen'), 'delete_selected_button', array('align' => 'absmiddle'));
        if (have_msgfolder($sms_data['view']) == TRUE) {
            $content_content .= \Studip\Button::create(_('Verschieben'), 'move_selected_button', array('align' => 'absmiddle'));
        }
        $content_content .= "</div><br></div>";
    if (folder_openclose($sms_show['folder'][$sms_data['view']], "all") == "open") {
        echo "\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"99%\" align=\"center\"><tr>";
        if ($count_timefilter != "0") {
            echo "<td class=\"blank\" background=\"".$GLOBALS['ASSETS_URL']."images/forumstrichgrau.gif\"><img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" height=\"100%\" width=\"10px\"></td>";
        }
        printcontent("99%",0, $content_content, FALSE);
        echo "</tr></table> ";
    }
}
if (folder_openclose($sms_show['folder'][$sms_data['view']], "all") == "open") print_messages();

// do we have any personal folders? if, show them here
if (have_msgfolder($sms_data['view']) == TRUE) {
    // walk throw personal folders
    for($x="0";$x<sizeof($my_messaging_settings["folder"][$sms_data['view']]);$x++) {
        if (htmlready(stripslashes(return_val_from_key($my_messaging_settings["folder"][$sms_data["view"]], $x))) != "dummy") {
            // how many items are in the folder
            $count = count_messages_from_user($sms_data['view'], "AND folder='".$x."'");
            // how many items match the timefilter?
            $count_timefilter = count_x_messages_from_user($sms_data['view'], $x, $query_time_sort);
            // this folder is open?
            $open = folder_openclose($sms_show['folder'][$sms_data['view']], $x);
            if ($sms_data['tmp']['move_to_folder'] && $open == "close") {
                $picture = 'icons/16/yellow/arr_2right.png';
                $link = URLHelper::getLink("?move_folder=".$x);
            } else {
                $link = URLHelper::getLink("?cmd=");
                $picture = showfoldericon($x, $count);
            }
            if (!$sms_data['tmp']['move_to_folder']) {
                 $link = folder_makelink($x);
                 $link_add = "&cmd_show=openall";

            }
            // titel
            $titel = "<a href=\"".$link."\" class=\"tree\" >".htmlready(stripslashes($my_messaging_settings["folder"][$sms_data['view']][$x]))."</a>";
            // titel suffix
            $zusatz = show_nachrichtencount($count, $count_timefilter);
            // display titel
            echo "\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"99%\" align=\"center\"><tr>";
            printhead(0, 0, $link, $open, FALSE, "<a href=\"".$link.$link_add."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/".$picture."\"></a>", $titel, $zusatz);
            echo "</tr></table> ";
            // do we move messages?
            if (!$move_to_folder) {
                $content_content = _("Ordner:")."&nbsp;".$sms_show['folder'][$sms_data['view']]."<br>";
                if ($open == "open") {
                    $content_content = "<div align=\"center\">"._("Ordneroptionen:")."
                        <form action=\"".URLHelper::getURL()."\" method=\"post\" style=\"display: inline\">".
                            CSRFProtection::tokenTag() .
                            "<input type=\"hidden\" name=\"delete_folder\" value=\"".$x."\">"
                              . \Studip\Button::create(_('Löschen'), 'delete_folder_button', array('align' => 'absmiddle')) .
                        "</form>
                        <form action=\"".URLHelper::getURL()."\" method=\"post\" style=\"display: inline\">".
                            CSRFProtection::tokenTag() .
                            "<input type=\"hidden\" name=\"cmd\" value=\"admin_folder\">
                            <input type=\"hidden\" name=\"ren_folder\" value=\"".$x."\">"
                            . \Studip\Button::create(_('Umbenennen'), 'x', array('align' => 'absmiddle')) .
                        "</form>";
                    if ($count_timefilter != "0") {
                        $content_content .= "
                            <br><img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" height=\"5\"><br>"._("markierte Nachrichten:")."
                            <form action=\"".URLHelper::getURL()."\" method=\"post\" style=\"display: inline\">".
                                CSRFProtection::tokenTag() .
                                "<input type=\"hidden\" name=\"cmd\" value=\"select_all\">"
                                . \Studip\Button::create(_('Alle auswählen'), 'select', array('align' => 'absmiddle')) .
                                "</form>
                                <form action=\"".URLHelper::getURL()."\" method=\"post\" style=\"display: inline\">".
                                CSRFProtection::tokenTag()
                                . \Studip\Button::create(_('Löschen'), 'delete_selected_button', array('align' => 'absmiddle'))
                                . \Studip\Button::create(_('Verschieben'), 'move_selected_button', array('align' => 'absmiddle'))
                                . "<br>";
                    }
                    $content_content .= "</div>";
                    echo "\n<table cellpadding=\"0\" cellspacing=\"0\" width=\"99%\" align=\"center\">\n\t<tr>";
                    if ($count_timefilter != "0") {
                        echo "\n\t<td class=\"blank\" background=\"".$GLOBALS['ASSETS_URL']."images/forumstrichgrau.gif\"><img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" height=\"100%\" width=\"10px\"></td>\n";
                    }
                    printcontent("99%",0, $content_content, FALSE);
                    echo "</tr></table> ";
                }
            }
            // if folder is open show some messages
            if (folder_openclose($sms_show['folder'][$sms_data['view']], $x) == "open") print_messages();
        }
    }
}
print("</form>");

//Infobox

// build infobox_content > viewfilter
$time_by_links = "";
$time_by_links .= "<a href=\"".URLHelper::getLink("?sms_time=all")."\">".Assets::img(show_icon($sms_data["time"], "all"), array('width' => '16', 'class' => 'text-bottom'))." "._("alle Nachrichten")."</a><br>";
$time_by_links .= "<a href=\"".URLHelper::getLink("?sms_time=24h")."\">".Assets::img(show_icon($sms_data["time"], "24h"), array('width' => '16', 'class' => 'text-bottom'))." "._("letzte 24 Stunden")."</a><br>";
$time_by_links .= "<a href=\"".URLHelper::getLink("?sms_time=7d")."\">".Assets::img(show_icon($sms_data["time"], "7d"), array('width' => '16', 'class' => 'text-bottom'))." "._("letzte 7 Tage")."</a><br>";
$time_by_links .= "<a href=\"".URLHelper::getLink("?sms_time=30d")."\">".Assets::img(show_icon($sms_data["time"], "30d"), array('width' => '16', 'class' => 'text-bottom'))." "._("letzte 30 Tage")."</a><br>";
$time_by_links .= "<a href=\"".URLHelper::getLink("?sms_time=older")."\">".Assets::img(show_icon($sms_data["time"], "older"), array('width' => '16', 'class' => 'text-bottom'))." "._("&auml;lter als 30 Tage")."</a>";

$view_by_links = "";
$view_by_links .= "<a href=\"".URLHelper::getLink("?sms_time=new")."\">".Assets::img(show_icon($sms_data["time"], "new"), array('width' => '16', 'class' => 'text-bottom'))." "._("neue Nachrichten")."</a><br>";

// did we came from a ...?
if ($SessSemName[0] && $SessSemName["class"] == "inst") {
    $tmp_array_1 = array("kategorie" => _("Zur&uuml;ck:"),"eintrag" => array(array("icon" => "icons/16/black/info.png", "text" => "<a href=\"institut_main.php\">"._("Zur&uuml;ck zur ausgew&auml;hlten Einrichtung")."</a>")));
} else if ($SessSemName[0]) {
    $tmp_array_1 = array("kategorie" => _("Zur&uuml;ck:"),"eintrag" => array(array("icon" => "icons/16/black/info.png", "text" => "<a href=\"seminar_main.php\">"._("Zur&uuml;ck zur ausgew&auml;hlten Veranstaltung")."</a>")));
}
// how many items do we have?
$neum = count_messages_from_user('in', " AND message_user.readed = 0 ");
$altm = count_messages_from_user('in', " AND message_user.readed = 1 ");
$show_message_count = sprintf(_("Sie haben %s empfangene und %s gesendete Nachrichten."), ($altm+$neum), count_messages_from_user("snd"));
if ($neum == "1") {
    $show_message_count .= "<br>"._("Eine Nachricht ist ungelesen.");
} else if ($neum > "1") {
    $show_message_count .= "<br>".sprintf(_("%s Nachrichten sind ungelesen."), ($neum));
}

$infobox = array($tmp_array_1,
            array("kategorie" => _("Information:"),"eintrag" => array(
                array('icon' => 'icons/16/black/info.png', "text" => $show_message_count))),
            array("kategorie" => _("nach Zeit filtern:"),"eintrag" => array(
                array('icon' => 'icons/16/black/new/mail.png', "text" => $time_by_links))),
            array("kategorie" => _("weitere Ansichten:"),"eintrag" => array(
                array('icon' => 'icons/16/black/new/mail.png', "text" => $view_by_links))),
            array("kategorie" => _("Optionen:"),"eintrag" => array(
                array("icon" => 'icons/16/black/admin.png', "text" => "<a href=\"".URLHelper::getLink("?cmd_show=openall")."\">"._("Alle Nachrichten aufklappen")."</a><br><a href=\"".URLHelper::getLink("?cmd=mark_allsmsreaded")."\">"._("Alle als gelesen speichern")."</a>"),
                array("icon" => 'icons/16/black/add/folder-empty.png', "text" => "<a href=\"".URLHelper::getLink("?cmd=admin_folder&cmd_2=new")."\">"._("Neuen Ordner erstellen")."</a>")
            ))
        );

$infobox = array(
    'picture' => 'infobox/board2.jpg',
    'content' => $infobox
);