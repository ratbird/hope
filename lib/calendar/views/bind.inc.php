<?

# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/**
 * bind.inc.php
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
//TODO: templates
include('lib/include/html_head.inc.php');
include('lib/include/header.php');

// add skip link
SkipLinks::addIndex(_("Veranstaltungstermine"), 'main_content', 100);
// Semesterauswahl
$semester_data = new SemesterData();
$current_semester = $semester_data->getCurrentSemesterData();

if (Request::submitted('sem_auswahl')) {
    $selected_sem = Request::get('sem_auswahl');
} else {
    $selected_sem = $current_semester['semester_id'];
}
// alle vom user abonnierten Seminare
$db = DBManager::get();
$sortby = Request::option('sortby', 'seminar_user.gruppe, seminare.Name');
$conds = array($user->id, $user->id);
if ($order == 'ASC') {
    $order = 'DESC';
} else {
    $order = 'ASC';
}
$query = "SELECT bind_calendar, visitdate, seminare.Name, seminare.Seminar_id, seminar_user.status, seminar_user.gruppe, count(termin_id) as count,
    sd1.name AS startsem,IF(duration_time=-1, '" . _("unbegrenzt") . "', sd2.name) AS endsem
    FROM seminar_user LEFT JOIN seminare ON seminare.Seminar_id=seminar_user.seminar_id
    LEFT JOIN object_user_visits  ouv ON ouv.object_id = seminare.Seminar_id AND ouv.user_id = ? AND ouv.type = 'sem'
    LEFT JOIN semester_data sd1 ON ( start_time BETWEEN sd1.beginn AND sd1.ende)
    LEFT JOIN semester_data sd2 ON ((start_time + duration_time) BETWEEN sd2.beginn AND sd2.ende)
    LEFT JOIN termine ON range_id=seminare.Seminar_id WHERE seminar_user.user_id = ? ";


if ($selected_sem != "0") {
    $conds[] = $selected_sem;
    $query .= "AND sd1.semester_id = ? ";
} else {
    
}


$query .= "GROUP BY Seminar_id ORDER BY " . $sortby . " " . $order;
$db = DBManager::get()->prepare($query);
$db->execute($conds);

$result = $db->fetchAll(PDO::FETCH_ASSOC);
echo "<table width=\"100%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\">\n";
echo "<tr><td class=\"blank\" valign=\"top\">\n";


if (!empty($result)) {

    if (!empty($calendar_sess_control_data["view_prv"])) {
        echo "<form action=\"$PHP_SELF?cmd={$calendar_sess_control_data['view_prv']}\" method=\"post\">";
    } else {
        echo "<form action=\"$PHP_SELF?cmd=showweek\" method=\"post\">";
    }
    echo "<input type=\"hidden\" name=\"selected_sem\" value=\"".$selected_sem."\" />\n";
    echo CSRFProtection::tokenTag();
    echo "<table border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"1\" class=\"blank\" id=\"main_content\">\n";
    echo "\n<tr>\n";
    echo "<th width=\"2%\" nowrap colspan=\"2\" align=\"center\">";
    echo "&nbsp;<a href=\"gruppe.php\">";
    $tooltip = tooltip(_("Gruppe �ndern"));
    echo "<img src=\"" . Assets::image_path('icons/16/blue/group.png') . "\" {$tooltip}>";
    echo "</a></th>\n";
    echo "<th width=\"64%\" align=\"left\">";
    echo "<a href=\"$PHP_SELF?cmd=bind&sortby=Name&order=$order\">" . _("Name") . "</a></th>\n";
    echo "<th width=\"7%\"><a href=\"$PHP_SELF?cmd=bind&sortby=count&order=$order\">";
    echo _("Termine") . "</a></th>\n";
    echo "<th width=\"13%\"><b>" . _("besucht") . "</b></th>\n";
    echo "<th width=\"13%\"><a href=\"$PHP_SELF?cmd=bind&sortby=status&order=$order\">";
    echo _("Status") . "</a></th>\n";
    echo "<th width=\"2%\"><input type=\"checkbox\" id=\"checkAll\" name=\"all\" value=\"1\" 
        checked=\"checked\" aria-label=\"Alle ausw�hlen\" data-proxyfor=\":checkbox[name^=sem]\"/></th>\n</tr>\n";
    // Studip 2.4
    #echo "<th width=\"2%\"><input type=\"checkbox\" name=\"all\" value=\"1\" checked=\"checked\" aria-label=\"Alle ausw�hlen\" data-proxyfor=\":checkbox[name^=sem]\"/></th>\n</tr>\n";

    $css_switcher = new cssClassSwitcher();
    echo $css_switcher->GetHoverJSFunction();
    $css_switcher->enableHover();
    $css_switcher->switchClass();

    foreach ($result as $row) {
        $name = $row['Name'] . " (" . $row['startsem'] . ($row['startsem'] != $row['endsem'] ? " - " . $row['endsem'] : "") . ")";
        $style = $css_switcher->getFullClass();
        echo "<tr" . $css_switcher->getHover() . "><td width=\"1%\" class=\"gruppe" . $row['gruppe'] . "\">";
        echo "<img src=\"" . $GLOBALS['ASSETS_URL'] . "images/blank.gif\" alt=\"Gruppe\" border=\"0\" width=\"7\" height=\"12\"></td>\n";
        echo "<td$style>&nbsp; </td>";
        echo "<td$style><font size=\"-1\">";
        echo "<a href=\"" . $CANONICAL_RELATIVE_PATH_STUDIP;
        echo "seminar_main.php?auswahl=" . $row['Seminar_id'] . "\">";
        echo htmlReady(mila($name));
        echo "</a></font></td>\n";
        echo "<td$style align=\"center\"><font size=\"-1\">";
        echo $row['count'];
        echo "</font></td>\n";
        if ($row['visitdate'] == 0) {
            echo "<td$style align=\"center\"><font size=\"-1\">";
            echo _("nicht besucht") . "</font></td>\n";
        } else {
            echo "<td$style align=\"center\"><font size=\"-1\">";
            echo strftime("%x", $row['visitdate']);
            echo "</font></td>";
        }
        echo "<td$style align=\"center\"><font size=\"-1\">";
        echo $row['status'];
        echo "</font></td>\n";
        if ($row['bind_calendar']) {
            $is_checked = ' checked';
        } else {
            $is_checked = '';
        }
        echo "<td$style>";
        echo "<input type=\"checkbox\" class=\"sem_checkbox\" name=\"sem[" . $row['Seminar_id']
        . "]\" value=\"1\"$is_checked></td></tr>\n",
        $css_switcher->switchClass();
    }

    echo "<tr><td class=\"blank\">&nbsp;</td></tr>\n";
    echo "<tr><td class=\"blank\" colspan=\"6\" align=\"center\">";
    echo "&nbsp;<input type=\"image\" " . makeButton("auswaehlen", "src") . " name=\"auswaehlen\" border=\"0\"></td></tr>\n";

// Dummy-Wert damit $sem auch ohne ausgewaehlte Seminare ausgewertet wird
    echo "\n<input type=\"hidden\" name=\"sem[1]\" value=\"FALSE\">\n";
    echo "<input type=\"hidden\" name=\"atime\" value=\"$atime\">";
    

    echo "</table>";
    echo "\n</form>\n";
} else {
    echo "<table border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"1\" class=\"blank\" id=\"main_content\">\n";
    echo "\n<tr>\n";
    echo "<td valign=\"top\">";
    echo MessageBox::info('In dem ausgew�hlten Semester sind keine Veranstaltungen hinterlegt');
    echo "</td>";
    echo "</tr>";
    echo "</table>";
}
echo "\n</td>\n";
echo "<td class=\"blank\" width=\"270\" valign=\"top\" align=\"right\">\n";


$info_content = array(
    array("kategorie" => _("Semesterauswahl"),
        "eintrag" => array(
            array("icon" => "",
                "text" => '<form method="post" id="sem_auswahl" name="semester" action="' . $PHP_SELF . '?cmd=bind">' .
                $semester_data->GetSemesterSelector(array('name' => 'sem_auswahl', 'onchange' => 'jQuery(\'#sem_auswahl\').submit()'), $selected_sem)
                . '</form>'
            )
        )
    ),
    array("kategorie" => _("Information:"),
        "eintrag" => array(
            array("icon" => "icons/16/black/info.png",
                "text" => _("Termine aus den ausgew&auml;hlten Veranstaltungen werden in Ihren Terminkalender &uuml;bernommen.")
        ))));

print_infobox($info_content, "infobox/dates.jpg");
echo "</td></tr></table>\n";

echo "</tr><tr><td class=\"blank\">&nbsp;";
