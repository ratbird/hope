<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

require_once('config.inc.php');
require_once('lib/classes/SemesterData.class.php');
require_once('lib/classes/DataFieldEntry.class.php');
require_once('lib/classes/Avatar.class.php');
require_once('lib/visual.inc.php');
require_once('lib/statusgruppe.inc.php');
require_once($GLOBALS['RELATIVE_PATH_EXTERN'] . '/lib/extern_functions.inc.php');

if ($GLOBALS["CALENDAR_ENABLE"]) {
    require_once($GLOBALS["RELATIVE_PATH_CALENDAR"] . "/lib/SingleCalendar.class.php");
    require_once($GLOBALS["RELATIVE_PATH_CALENDAR"] . "/lib/DbCalendarEventList.class.php");
}

$instituts_id = $this->config->range_id;
$username = $args["username"];
$sem_id = $args["seminar_id"];

$this->visibilities = get_local_visibility_by_username($username, 'homepage', true);
if ($this->visibilities) {
    $this->owner_perm = $this->visibilities['perms'];
    $this->visibilities = json_decode($this->visibilities['homepage'], true);
} else {
    $this->visibilities = array();
    $this->owner_perm = 'user';
}

$db_inst = new DB_Seminar();
$db = new DB_Seminar();

if (!$nameformat = $this->config->getValue("Main", "nameformat")) {
    $nameformat = "full";
}

$query_user_data = "SELECT i.Institut_id, i.Name, i.Strasse, i.Plz, i.url, ui.*, aum.*, "
                        . $GLOBALS['_fullname_sql'][$nameformat] . " AS fullname,"
                        . "uin.user_id, uin.lebenslauf, uin.publi, uin.schwerp, uin.Home "
                        . "FROM Institute i LEFT JOIN user_inst ui USING(Institut_id) "
              . "LEFT JOIN auth_user_md5 aum USING(user_id) "
              . "LEFT JOIN user_info uin USING (user_id) WHERE ui.inst_perms IN ('autor','tutor','dozent') AND "
              . get_ext_vis_query() . ' AND ';

// Mitarbeiter/in am Institut
$db_inst->query("SELECT i.Institut_id FROM Institute i LEFT JOIN user_inst ui USING(Institut_id) "
              ."LEFT JOIN auth_user_md5 aum USING(user_id) "
              ."WHERE i.Institut_id = '$instituts_id' AND aum.username = '$username' AND ui.inst_perms IN ('autor','tutor','dozent') AND " . get_ext_vis_query());

// Mitarbeiter/in am Heimatinstitut des Seminars
if (!$db_inst->num_rows() && $sem_id) {
    $db_inst->query("SELECT s.Institut_id FROM seminare s LEFT JOIN user_inst ui USING(Institut_id) "
                   ."LEFT JOIN auth_user_md5 aum USING(user_id) WHERE s.Seminar_id = '$sem_id' "
                                 ."AND aum.username = '$username' AND ui.inst_perms = 'dozent' AND " . get_ext_vis_query());
    if($db_inst->num_rows() && $db_inst->next_record())
        $instituts_id = $db_inst->f("Institut_id");
}

// an beteiligtem Institut Dozent(in)
if (!$db_inst->num_rows() && $sem_id) {
    $db_inst->query("SELECT si.institut_id FROM seminare s LEFT JOIN seminar_inst si ON(s.Seminar_id = si.seminar_id) "
                   ."LEFT JOIN user_inst ui ON(si.institut_id = ui.Institut_id) LEFT JOIN auth_user_md5 aum "
                                 ."USING(user_id) WHERE s.Seminar_id = '$sem_id' AND si.institut_id != '$instituts_id' "
                                 ."AND ui.inst_perms = 'dozent' AND aum.username = '$username' AND " . get_ext_vis_query());
    if($db_inst->num_rows() && $db_inst->next_record())
        $instituts_id = $db_inst->f("institut_id");
}

// ist zwar global Dozent, aber an keinem Institut eingetragen
if (!$db_inst->num_rows() && $sem_id) {
    $query = "SELECT aum.*, ";
    $query .= $GLOBALS['_fullname_sql'][$nameformat] . " AS fullname ";
    $query .= "FROM auth_user_md5 aum LEFT JOIN user_info USING(user_id) LEFT JOIN seminar_user su USING(user_id) ";
    $query .= "WHERE username = '$username' AND perms = 'dozent' AND su.seminar_id = '$sem_id' AND su.status = 'dozent'";
    $query .= " AND " . get_ext_vis_query();
    $db->query($query);
}
elseif ($this->config->getValue('Contact', 'defaultadr')) {
    $db->query($query_user_data . " aum.username = '$username' AND ui.externdefault = 1");
    if (!$db->num_rows())
        $db->query($query_user_data . " aum.username = '$username' AND i.Institut_id = '$instituts_id' AND ui.inst_perms IN ('autor','tutor','dozent')");
}
else
    $db->query($query_user_data . " aum.username = '$username' AND i.Institut_id = '$instituts_id' AND ui.inst_perms IN ('autor','tutor','dozent')");

if (!$db->next_record())
    die;

$aliases_content = $this->config->getValue("Main", "aliases");
$visible_content = $this->config->getValue("Main", "visible");

if ($margin = $this->config->getValue("TableParagraphText", "margin")) {
    $text_div = "<div style=\"margin-left:$margin;\">";
    $text_div_end = "</div>";
}
else {
    $text_div = "";
    $text_div_end = "";
}

echo "<table" . $this->config->getAttributes("TableHeader", "table") . ">\n";

$studip_link = $GLOBALS['ABSOLUTE_URI_STUDIP'] . 'edit_about.php';
$studip_link .= "?login=yes&view=Daten&username=$username";
if ($this->config->getValue("Main", "studiplink") == "top") {
    $args = array("width" => "100%", "height" => "40", "link" => $studip_link);
    echo "<tr><td width=\"100%\">\n";
    $this->elements["StudipLink"]->printout($args);
    echo "</td></tr>";
}

// generic data fields
if ($generic_datafields = $this->config->getValue("Main", "genericdatafields")) {
//  $datafields_obj =& new DataFields($db->f("user_id"));
    $fieldEntries = DataFieldEntry::getDataFieldEntries($db->f("user_id"));
//  $datafields = $datafields_obj->getLocalFields($db->f("user_id"));
}

$order = $this->config->getValue("Main", "order");
foreach ($order as $position) {

    $data_field = $this->data_fields["content"][$position];

    if ($visible_content[$position]) {
        switch ($data_field) {
            case "lebenslauf" :
            case "schwerp" :
            case "publi" :
                if ($db->f($data_field) != "" && is_element_visible_externally($db->f("user_id"), $this->owner_perm, $data_field, $this->visibilities[$data_field])) {
                    echo "<tr><td width=\"100%\">\n";
                    echo "<table" . $this->config->getAttributes("TableParagraph", "table") . ">\n";
                    echo "<tr" . $this->config->getAttributes("TableParagraphHeadline", "tr");
                    echo "><td" . $this->config->getAttributes("TableParagraphHeadline", "td");
                    echo "><font" . $this->config->getAttributes("TableParagraphHeadline", "font") . ">\n";
                    echo $aliases_content[$position] . "</font></td></tr>\n";
                    echo "<tr" . $this->config->getAttributes("TableParagraphText", "tr") . ">";
                    echo "<td" . $this->config->getAttributes("TableParagraphText", "td") . ">";
                    echo "$text_div<font" . $this->config->getAttributes("TableParagraphText", "font") . ">\n";
                    echo formatReady($db->f($data_field), TRUE, TRUE);
                    echo "</font>$text_div_end</td></tr>\n</table>\n</td></tr>\n";
                }
                break;
            case "news" :
            case "termine" :
                if (is_element_visible_externally($db->f("user_id"), $this->owner_perm, $data_field, $this->visibilities[$data_field])) {
                    $data_field($this, $db, $aliases_content[$position], $text_div, $text_div_end);
                }
                break;
            case "kategorien" :
            case "lehre" :
            case "head" :
                $data_field($this, $db, $aliases_content[$position], $text_div, $text_div_end);
                break;
            /*
            case 'literature' :
                $literature_content = $this->elements['LitList']->getContent(NULL);
                literature($this, $literature_content, $aliases_content[$position], $text_div, $text_div_end);
                break;
            */
            // generic data fields
            default :
                // include generic datafields
                if (isset($fieldEntries[$data_field]) && is_object($fieldEntries[$data_field]) && $fieldEntries[$data_field]->getDisplayValue()) {
                    echo "<tr><td width=\"100%\">\n";
                    echo "<table" . $this->config->getAttributes("TableParagraph", "table") . ">\n";
                    echo "<tr" . $this->config->getAttributes("TableParagraphHeadline", "tr");
                    echo "><td" . $this->config->getAttributes("TableParagraphHeadline", "td");
                    echo "><font" . $this->config->getAttributes("TableParagraphHeadline", "font") . ">\n";
                    echo $aliases_content[$position] . "</font></td></tr>\n";
                    echo "<tr" . $this->config->getAttributes("TableParagraphText", "tr") . ">";
                    echo "<td" . $this->config->getAttributes("TableParagraphText", "td") . ">";
                    echo "$text_div<font" . $this->config->getAttributes("TableParagraphText", "font") . ">\n";
                    echo $fieldEntries[$data_field]->getDisplayValue();
                    echo "</font>$text_div_end</td></tr>\n</table>\n</td></tr>\n";
                }
        }
    }
}

if ($this->config->getValue("Main", "studiplink") == "bottom") {
    $args = array("width" => "100%", "height" => "40", "link" => $studip_link);
    echo "<tr><td width=\"100%\">\n";
    $this->elements["StudipLink"]->printout($args);
    echo "</td></tr>";
}

echo "</table>\n";

function news (&$module, $db, $alias_content, $text_div, $text_div_end) {
    if (is_element_visible_externally($db->f("user_id"), $module->owner_perm, $data_field, $module->visibilities['news'])) {
        if ($margin = $module->config->getValue("TableParagraphSubHeadline", "margin")) {
            $subheadline_div = "<div style=\"margin-left:$margin;\">";
            $subheadline_div_end = "</div>";
        }
        else {
            $subheadline_div = "";
            $subheadline_div_end = "";
        }

        $db_news = new DB_Seminar();
        $query = "SELECT * FROM news_range nr LEFT JOIN news n USING(news_id) WHERE "
                        . "nr.range_id = '" . $db->f("user_id") . "' AND user_id = '" . $db->f("user_id")
                        . "' AND date <= " . time() . " AND (date + expire) >= " . time();
        $db_news->query($query);
        if ($db_news->num_rows()) {
            echo "<tr><td width=\"100%\">\n";
            echo "<table" . $module->config->getAttributes("TableParagraph", "table") . ">\n";
            echo "<tr" . $module->config->getAttributes("TableParagraphHeadline", "tr") . ">";
            echo "<td" . $module->config->getAttributes("TableParagraphHeadline", "td") . ">";
            echo "<font" . $module->config->getAttributes("TableParagraphHeadline", "font") . ">";
            echo "$alias_content</font></td></tr>\n";

            while ($db_news->next_record()) {
                echo "<tr" . $module->config->getAttributes("TableParagraphSubHeadline", "tr") . ">";
                echo "<td" . $module->config->getAttributes("TableParagraphSubHeadline", "td") . ">";
                echo $subheadline_div;
                echo "<font" . $module->config->getAttributes("TableParagraphSubHeadline", "font") . ">";
                echo htmlReady($db_news->f("topic"));
                echo "</font>$subheadline_div_end</td></tr>\n";
                echo "<tr" . $module->config->getAttributes("TableParagraphText", "tr") . ">";
                list ($content, $admin_msg) = explode("<admin_msg>", $db_news->f("body"));
                echo "<td" . $module->config->getAttributes("TableParagraphText", "td") . ">";
                echo "$text_div<font" . $module->config->getAttributes("TableParagraphText", "font") . ">";
                echo formatReady($content, TRUE, TRUE);
                echo "</font>$text_div_end</td></tr>\n";
            }
            echo "</table>\n</td></tr>\n";
        }
    }
}

function termine (&$module, $db, $alias_content, $text_div, $text_div_end) {
    if ($GLOBALS["CALENDAR_ENABLE"] && is_element_visible_externally($db->f("user_id"), $module->owner_perm, $data_field, $module->visibilities['dates'])) {
        if ($margin = $module->config->getValue("TableParagraphSubHeadline", "margin")) {
            $subheadline_div = "<div style=\"margin-left:$margin;\">";
            $subheadline_div_end = "</div>";
        }
        else {
            $subheadline_div = "";
            $subheadline_div_end = "";
        }

        $event_list = new DbCalendarEventList(new SingleCalendar($db->f("user_id")));
        if ($event_list->existEvent()) {
            echo "<tr><td width=\"100%\">\n";
            echo "<table" . $module->config->getAttributes("TableParagraph", "table") . ">\n";
            echo "<tr" . $module->config->getAttributes("TableParagraphHeadline", "tr") . ">";
            echo "<td" . $module->config->getAttributes("TableParagraphHeadline", "td") . ">";
            echo "<font" . $module->config->getAttributes("TableParagraphHeadline", "font") . ">";
            echo "$alias_content</font></td></tr>\n";

            while ($event = $event_list->nextEvent()) {
                echo "<tr" . $module->config->getAttributes("TableParagraphSubHeadline", "tr") . ">";
                echo "<td" . $module->config->getAttributes("TableParagraphSubHeadline", "td") . ">";
                echo $subheadline_div;
                echo "<font" . $module->config->getAttributes("TableParagraphSubHeadline", "font") . ">";
                echo strftime($module->config->getValue("Main", "dateformat") . " %H:%M", $event->getStart());
                if (date("dmY", $event->getStart()) == date("dmY", $event->getEnd()))
                    echo strftime(" - %H:%M", $event->getEnd());
                else
                    echo strftime(" - " . $module->config->getValue("Main", "dateformat") . " %H:%M", $event->getEnd());
                echo " &nbsp;" . htmlReady($event->getTitle());
                echo "</font>$subheadline_div_end</td></tr>\n";
                if ($event->getDescription()) {
                    echo "<tr" . $module->config->getAttributes("TableParagraphText", "tr") . ">";
                    echo "<td" . $module->config->getAttributes("TableParagraphText", "td") . ">";
                    echo "$text_div<font" . $module->config->getAttributes("TableParagraphText", "font") . ">";
                    echo htmlReady($event->getDescription());
                    echo "</font>$text_div_end</td></tr>\n";
                }
            }
            echo "</table>\n</td></tr>\n";
        }
    }
}

function kategorien (&$module, $db, $alias_content, $text_div, $text_div_end) {
    $db_kategorien = new DB_Seminar();
    $query = "SELECT * FROM auth_user_md5 aum LEFT JOIN kategorien k ON (k.range_id=user_id) "
           ."WHERE username='" . $db->f("username") . "' ORDER BY priority";

    $db_kategorien->query($query);
    while ($db_kategorien->next_record()) {
        if (is_element_visible_externally($db->f("user_id"), $module->owner_perm, 
                'kat_'.$db_kategorien->f('kategorie_id'), 
                $module->visibilities['kat_'.$db_kategorien->f('kategorie_id')])) {
            echo "<tr><td width=\"100%\">\n";
            echo "<table" . $module->config->getAttributes("TableParagraph", "table") . ">\n";
            echo "<tr" . $module->config->getAttributes("TableParagraphHeadline", "tr") . ">";
            echo "<td" . $module->config->getAttributes("TableParagraphHeadline", "td") . ">";
            echo "<font" . $module->config->getAttributes("TableParagraphHeadline", "font") . ">";
            echo htmlReady($db_kategorien->f("name"), TRUE);
            echo "</font></td></tr>\n";
            echo "<tr" . $module->config->getAttributes("TableParagraphText", "tr") . ">";
            echo "<td" . $module->config->getAttributes("TableParagraphText", "td") . ">";
            echo "$text_div<font" . $module->config->getAttributes("TableParagraphText", "font") . ">";
            echo formatReady($db_kategorien->f("content"), TRUE, TRUE);
            echo "</font>$text_div_end</td></tr>\n</table>\n</td></tr>\n";
        }
    }
}

function lehre (&$module, $db, $alias_content, $text_div, $text_div_end) {
    global $attr_text_td, $end, $start;
    $db1 = new DB_Seminar();
    $semester = new SemesterData();
    $all_semester = $semester->getAllSemesterData();
    // old hard coded $SEMESTER-array starts with index 1
    array_unshift($all_semester, 0);

    if ($margin = $module->config->getValue("TableParagraphSubHeadline", "margin")) {
        $subheadline_div = "<div style=\"margin-left:$margin;\">";
        $subheadline_div_end = "</div>";
    }
    else {
        $subheadline_div = "";
        $subheadline_div_end = "";
    }
    if ($margin = $module->config->getValue("List", "margin")) {
        $list_div = "<div style=\"margin-left:$margin;\">";
        $list_div_end = "</div>";
    }
    else {
        $list_div = "";
        $list_div_end = "";
    }

    $types = array();
    $semclass = $module->config->getValue('PersondetailsLectures', 'semclass');
    if (is_null($semclass)) {
        $semclass = array(1);
    }
    foreach ($GLOBALS["SEM_TYPE"] as $key => $type) {
        if (in_array($type["class"], $semclass)) {
            $types[] = $key;
        }
    }
    $types = implode("','", $types);

    $switch_time = mktime(0, 0, 0, date("m"),
            date("d") + 7 * $module->config->getValue("PersondetailsLectures", "semswitch"), date("Y"));
    // get current semester
    $current_sem = get_sem_num($switch_time) + 1;

    switch ($module->config->getValue("PersondetailsLectures", "semstart")) {
        case "previous" :
            if (isset($all_semester[$current_sem - 1]))
                $current_sem--;
            break;
        case "next" :
            if (isset($all_semester[$current_sem + 1]))
                $current_sem++;
            break;
        case "current" :
            break;
        default :
            if (isset($all_semester[$module->config->getValue("PersondetailsLectures", "semstart")]))
                $current_sem = $module->config->getValue("PersondetailsLectures", "semstart");
    }

    $last_sem = $current_sem + $module->config->getValue("PersondetailsLectures", "semrange") - 1;
    if ($last_sem < $current_sem)
        $last_sem = $current_sem;
    if (!isset($all_semester[$last_sem]))
        $last_sem = sizeof($all_semester) - 1;

    $out = "";
    for (;$current_sem <= $last_sem; $last_sem--) {
        $query = "SELECT * FROM seminar_user su LEFT JOIN seminare s USING(seminar_id) "
               ."WHERE user_id='".$db->f("user_id")."' AND "
                   ."su.status LIKE 'dozent' AND ((start_time >= {$all_semester[$last_sem]['beginn']} "
                   ."AND start_time <= {$all_semester[$last_sem]['beginn']}) OR (start_time <= {$all_semester[$last_sem]['ende']} "
                         ."AND duration_time = -1)) AND s.status IN ('$types') AND s.visible = 1 "
                         ."ORDER BY s.mkdate DESC";

        $db1->query($query);

        if ($db1->num_rows()) {
            if (!($module->config->getValue("PersondetailsLectures", "semstart") == "current"
                    && $module->config->getValue("PersondetailsLectures", "semrange") == 1)) {
                $out .= "<tr" . $module->config->getAttributes("TableParagraphSubHeadline", "tr") . ">";
                $out .= "<td" . $module->config->getAttributes("TableParagraphSubHeadline", "td") . ">";
                $out .= $subheadline_div;
                $out .= "<font" . $module->config->getAttributes("TableParagraphSubHeadline", "font") . ">";
                $month = date("n", $all_semester[$last_sem]['beginn']);
                if($month > 9) {
                    $out .= $module->config->getValue("PersondetailsLectures", "aliaswise");
                    $out .= date(" Y/", $all_semester[$last_sem]['beginn']) . date("y", $all_semester[$last_sem]['ende']);
                }
                else if($month > 3 && $month < 10) {
                    $out .= $module->config->getValue("PersondetailsLectures", "aliassose");
                    $out .= date(" Y", $all_semester[$last_sem]['beginn']);
                }
                $out .= "</font>$subheadline_div_end</td></tr>\n";
            }

            $out .= "<tr" . $module->config->getAttributes("TableParagraphText", "tr") . ">";
            $out .= "<td" . $module->config->getAttributes("TableParagraphText", "td") . ">";

            if ($module->config->getValue("PersondetailsLectures", "aslist")) {
                $out .= "$list_div<ul" . $module->config->getAttributes("List", "ul") . ">\n";
                while ($db1->next_record()) {
                    $out .= "<li" . $module->config->getAttributes("List", "li") . ">";
                    $out .= $module->elements["LinkIntern"]->toString(array("module" => "Lecturedetails",
                            "link_args" => "seminar_id=" . $db1->f("Seminar_id"),
                            "content" => htmlReady($db1->f("Name"), TRUE)));
                    if ($db1->f("Untertitel") != "") {
                        $out .= "<font" . $module->config->getAttributes("TableParagraphText", "font") . "><br>";
                        $out .= htmlReady($db1->f("Untertitel"), TRUE) . "</font>\n";
                    }
                }
                $out .= "</ul>$list_div_end";
            }
            else {
                $out .= $text_div;
                $j = 0;
                while ($db1->next_record()) {
                    if ($j) $out .= "<br>";
                    $out .= $module->elements['LinkIntern']->toString(array('module' => 'Lecturedetails',
                            'link_args' => 'seminar_id=' . $db1->f('Seminar_id'),
                            'content' => htmlReady($db1->f('Name'), TRUE)));
                    if($db1->f('Untertitel') != '') {
                        $out .= "<font" . $module->config->getAttributes("TableParagraphText", "font") . ">";
                        $out .= "<br>" . htmlReady($db1->f("Untertitel"), TRUE) . "</font>\n";
                    }
                    $j = 1;
                }
                $out .= $text_div_end;
            }
            $out .= "</td></tr>\n";
        }
    }

    if ($out) {
        $out_title = "<tr><td width=\"100%\">\n";
        $out_title .= "<table" . $module->config->getAttributes("TableParagraph", "table") . ">\n";
        $out_title .= "<tr" . $module->config->getAttributes("TableParagraphHeadline", "tr") . ">";
        $out_title .= "<td" . $module->config->getAttributes("TableParagraphHeadline", "td") . ">";
        $out_title .= "<font" . $module->config->getAttributes("TableParagraphHeadline", "font") . ">";
        $out_title .= $alias_content . "</font></td></tr>\n";
        echo $out_title . $out . "</table>\n</td></tr>\n";
    }
}

function head (&$module, $db, $a) {
    $pic_max_width = $module->config->getValue("PersondetailsHeader", "img_width");
    $pic_max_height = $module->config->getValue("PersondetailsHeader", "img_height");

    // fit size of image
    if ($pic_max_width && $pic_max_height) {
        $pic_size = @getimagesize(Avatar::getAvatar($db->f("user_id"))->getFilename(Avatar::NORMAL));

        if ($pic_size[0] > $pic_max_width || $pic_size[1] > $pic_max_height) {
            $fak_width = $pic_size[0] / $pic_max_width;
            $fak_height = $pic_size[1] / $pic_max_height;
            if ($fak_width > $fak_height) {
                $pic_width = (int) ($pic_size[0] / $fak_width);
                $pic_height = (int) ($pic_size[1] / $fak_width);
            }
            else {
                $pic_height = (int) ($pic_size[1] / $fak_height);
                $pic_width = (int) ($pic_size[0] / $fak_height);
            }
        }
        else {
            $pic_width = $pic_size[0];
            $pic_height = $pic_size[1];
        }
        $pic_max_width = $pic_width;
        $pic_max_height = $pic_height;
    }

    $module->config->config["PersondetailsHeader"]["img_width"] = $pic_max_width;
    $module->config->config["PersondetailsHeader"]["img_height"] = $pic_max_height;

    if ($module->config->getValue("Main", "showcontact")
            && $module->config->getValue("Main", "showimage"))
        $colspan = " colspan=\"2\"";
    else
        $colspan = "";

    echo "<tr><td width=\"100%\">\n";
    echo "<table" . $module->config->getAttributes("PersondetailsHeader", "table") . ">\n";

    // display name as headline
    if (!$module->config->getValue('PersondetailsHeader', 'hidename')) {
        echo "<tr" . $module->config->getAttributes("PersondetailsHeader", "tr") . ">";
        echo "<td$colspan width=\"100%\"";
        echo $module->config->getAttributes("PersondetailsHeader", "headlinetd") . ">";
        echo "<font" . $module->config->getAttributes("PersondetailsHeader", "font") . ">";
        echo htmlReady($db->f("fullname"), TRUE);
        echo "</font></td></tr>\n";
    }

    if ($module->config->getValue("Main", "showimage")
            || $module->config->getValue("Main", "showcontact")) {
        echo "<tr>";
        if ($module->config->getValue("Main", "showcontact")
                && ($module->config->getValue("Main", "showimage") == "right"
                || !$module->config->getValue("Main", "showimage"))) {
                echo "<td" . $module->config->getAttributes("PersondetailsHeader", "contacttd") . ">";
                echo kontakt($module, $db) . "</td>\n";
        }
        if ($module->config->getValue("Main", "showimage")) {
            echo "<td" . $module->config->getAttributes("PersondetailsHeader", "picturetd") . ">";
            $avatar = Avatar::getAvatar($db->f("user_id"));
            if ($avatar->is_customized() && is_element_visible_externally($db->f("user_id"), $module->owner_perm, 'picture', $module->visibilities['picture'])) {
                echo "<img src=\"".$avatar->getURL(Avatar::NORMAL) .
                     "\" alt=\"Foto " . htmlReady(trim($db->f("fullname"))) . "\"";
                echo $module->config->getAttributes("PersondetailsHeader", "img") . "></td>";
            }
            else
                echo "&nbsp;</td>";
        }

        if ($module->config->getValue("Main", "showcontact")
                && $module->config->getValue("Main", "showimage") == "left") {
            echo "<td" . $module->config->getAttributes("PersondetailsHeader", "contacttd") . ">";
            echo kontakt($module, $db) . "</td>\n";
        }

        echo "</tr>\n";
        if ($module->config->getValue('Main', 'showcontact')
                && $module->config->getValue('Contact', 'separatelinks')) {
            echo "<tr><td";
            if ($module->config->getValue('Main', 'showimage'))
                echo ' colspan="2"';
            echo $module->config->getAttributes('PersondetailsHeader', 'contacttd') . ">\n";
            echo kontakt($module, $db, TRUE);
            echo "</td></tr>\n";
        }
    }

    echo  "</table>\n</td></tr>\n";
}

function kontakt ($module, $db, $separate = FALSE) {
    $attr_table = $module->config->getAttributes("Contact", "table");
    $attr_tr = $module->config->getAttributes("Contact", "table");
    $attr_td = $module->config->getAttributes("Contact", "td");
    $attr_fonttitle = $module->config->getAttributes("Contact", "fonttitle");
    $attr_fontcontent = $module->config->getAttributes("Contact", "fontcontent");

    $out = "<table$attr_table>\n";
    if (!$separate) {
        $out .= "<tr$attr_tr>";
        $out .= "<td colspan=\"2\"$attr_td>";
        $out .= "<font$attr_fonttitle>";
        if ($headline = $module->config->getValue("Contact", "headline"))
            $out .= "$headline</font>\n";
        else
            $out .= "</font>\n";

        $out .= "<font$attr_fontcontent>";

        if (!$module->config->getValue("Contact", "hidepersname"))
            $out .= "<br><br>" . htmlReady($db->f("fullname"), TRUE) . "\n";
        if ($module->config->getValue('Contact', 'showinstgroup')) {
            if ($gruppen = GetRoleNames(GetAllStatusgruppen($module->config->range_id, $db->f('user_id'))))
                $out .= "<br>" . htmlReady(join(", ", array_values($gruppen)));
        }
        // display name of institution (as link)
        if ($db->f("Name")) {
            $br_out = "";
            if ($module->config->getValue("Contact", "hideinstname") != '1') {
                if ($module->config->getValue("Contact", "hideinstname") == 'link' && $db->f('url')) {
                    $url = htmlReady(trim($db->f("url")));
                    if (!stristr($url, "http://"))
                        $url = "http://$url";
                    $out .= "<br><br><a href=\"$url\" target=\"_blank\">";
                    $out .= htmlReady($db->f("Name"), TRUE) . "</a><br>";
                }
                else
                    $out .= "<br><br>" . htmlReady($db->f("Name"), TRUE) . "<br>";
            }
            if ($module->config->getValue("Contact", "adradd"))
                $out .= "<br>" . $module->config->getValue("Contact", "adradd");
        }

        $out .= "<br>";
        if ($db->f("Strasse")) {
            $out .= "<br>" . htmlReady($db->f("Strasse"), TRUE);
            if($db->f("Plz"))
            $out .= "<br>" . htmlReady($db->f("Plz"), TRUE);
        }
      $out .= "<br><br></font></td></tr>\n";
    }
    $order = $module->config->getValue("Contact", "order");
    $visible = $module->config->getValue("Contact", "visible");
    $alias_contact = $module->config->getValue("Contact", "aliases");
    foreach ($order as $position) {
        $data_field = $module->data_fields["contact"][$position];
        if (!$visible[$position] || !$db->f($data_field))
            continue;
        switch ($data_field) {
            case 'Email' :
                if ($separate || !$module->config->getValue('Contact', 'separatelinks')) {
                    $email_address = get_visible_email($db->f("user_id"));
                    $out .= "<tr$attr_tr>";
                    $out .= "<td$attr_td>";
                    $out .= "<font$attr_fonttitle>";
                    $out .= $alias_contact[$position] . "</font></td>";
                    $out .= "<td$attr_td>";
                    $out .= "<font$attr_fontcontent>";
                    $mail = trim(htmlReady($email_address));
                    $out .= "<a href=\"mailto:$mail\">$mail</a>";
                }
                break;
            case 'Home' :
                if (($separate || !$module->config->getValue('Contact', 'separatelinks')) && 
                        is_element_visible_externally($db->f("user_id"), $module->owner_perm, 'homepage', $module->visibilities['homepage'])) {
                    $out .= "<tr$attr_tr>";
                    $out .= "<td$attr_td>";
                    $out .= "<font$attr_fonttitle>";
                    $out .= $alias_contact[$position] . "</font></td>";
                    $out .= "<td$attr_td>";
                    $out .= "<font$attr_fontcontent>";
                    $out .= trim(FixLinks(htmlReady($db->f("Home")), TRUE, TRUE, FALSE, TRUE));
                }
                break;
            default:
                if (!$separate) {
                    $out .= "<tr$attr_tr>";
                    $out .= "<td$attr_td>";
                    $out .= "<font$attr_fonttitle>";
                    $out .= $alias_contact[$position] . "</font></td>";
                    $out .= "<td$attr_td>";
                    $out .= "<font$attr_fontcontent>";
                    $out .= htmlReady($db->f($data_field), TRUE);
                }
        }
        if ($db->f($data_field))
            $out .= "</font></td></tr>\n";
    }
    $out .= "</table>\n";

    return $out;
}

/*
function literature (&$module, $content, $alias_content, $text_div, $text_div_end) {
    if (count($content)) {
        echo "<tr><td width=\"100%\">\n";
        echo "<table" . $module->config->getAttributes("TableParagraph", "table") . ">\n";
        echo "<tr" . $module->config->getAttributes("TableParagraphHeadline", "tr") . ">";
        echo "<td" . $module->config->getAttributes("TableParagraphHeadline", "td") . ">";
        echo "<font" . $module->config->getAttributes("TableParagraphHeadline", "font") . ">";
        echo "$alias_content</font></td></tr>\n";

        $tmpl = "\n<!-- BEGIN LITLIST -->\n   ";
        $tmpl .= '<tr' . $module->config->getAttributes('TableParagraphSubHeadline', 'tr') . '>';
        $tmpl .= '<td' . $module->config->getAttributes('TableParagraphSubHeadline', 'td') . '>';
        $tmpl .= $subheadline_div;
        $tmpl .= '<font' . $module->config->getAttributes('TableParagraphSubHeadline', 'font') . '>';
        $tmpl .= '###LITLIST_NAME###';
        $tmpl .= "</font>$subheadline_div_end</td></tr>\n";
        $tmpl .= "\n  <!-- BEGIN LITLIST_ITEM -->\n  ";
        $tmpl .=        '<tr' . $module->config->getAttributes('TableParagraphText', 'tr') . '>';
        $tmpl .=        '<td' . $module->config->getAttributes('TableParagraphText', 'td') . '>';
        $tmpl .=        "$text_div<font" . $module->config->getAttributes('TableParagraphText', 'font') . '>';
        $tmpl .=        '###LITLIST_ITEM_ELEMENT###';
        $tmpl .=        "</font>$text_div_end</td></tr>\n";
        $tmpl .= "\n   <!-- END LITLIST_ITEM -->\n   ";
        $tmpl .= "\n  <!-- END LITLIST -->\n";

        echo $module->elements['LitList']->renderTmpl($tmpl);

        echo "</table>\n</td></tr>\n";
    }
}
*/

?>
