<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

use Studip\Button, Studip\LinkButton;

require '../lib/bootstrap.php';
unregister_globals();

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
my_session_open();
$perm->check("admin");
require_once ('lib/classes/SemesterData.class.php');
require_once ('lib/dbviews/literatur.view.php');
require_once ('lib/classes/StudipLitCatElement.class.php');
require_once ('lib/classes/StudipLitSearch.class.php');

require_once ('lib/visual.inc.php');
require_once ('config.inc.php');

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

PageLayout::setTitle(_("Übersicht verwendeter Literatur"));
Navigation::activateItem('/tools/literature');

include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   //hier wird der "Kopf" nachgeladen

function my_session_open($id = false){
    if (!$id){
        $id = md5(basename($GLOBALS['PHP_SELF']));
    }
    if (!$GLOBALS['sess']->is_registered($id)){
        $GLOBALS['sess']->register($id);
    }
    if (isset($GLOBALS[$id])){
        $GLOBALS[$id] = unserialize($GLOBALS[$id]);
    }
}

function my_session_close($id = false){
    if (!$id){
        $id = md5(basename($GLOBALS['PHP_SELF']));
    }
    if (isset($GLOBALS[$id])){
        $GLOBALS[$id] = serialize($GLOBALS[$id]);
    }
}

function my_session_var($var, $id = false){
    if (!$id){
        $id = md5(basename($GLOBALS['PHP_SELF']));
    }
    if (is_array($var)){
        foreach ($var as $name){
            if (isset($_REQUEST[$name])){
                $GLOBALS[$id][$name] = $_REQUEST[$name];
            } else {
                $_REQUEST[$name] = $GLOBALS[$id][$name];
            }
            $GLOBALS[$name] =& $GLOBALS[$id][$name];
        }
    } else {
        if (isset($_REQUEST[$var])){
            $GLOBALS[$id][$var] = $_REQUEST[$var];
        } else {
            $_REQUEST[$var] = $GLOBALS[$id][$var];
        }
        $GLOBALS[$var] =& $GLOBALS[$id][$var];
    }
}

function get_lit_admin_ids($user_id = false)
{
    $found = DBManager::get()
           ->query("SHOW TABLES LIKE 'admin_perms'")
           ->fetchColumn();

    if (!$found) {
        return false;
    }

    $query = "SELECT range_id FROM admin_perms WHERE perms = 'lit_admin' AND user_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user_id ?: $GLOBALS['user']->id));
    return $statement->fetchAll(PDO::FETCH_COLUMN);
}

foreach ($GLOBALS['SEM_CLASS'] as $key => $value){
    if ($value['bereiche']){
        foreach($GLOBALS['SEM_TYPE'] as $type_key => $type_value){
            if($type_value['class'] == $key)
                $allowed_sem_status[] = $type_key;
        }
    }
}
$_sem_status_sql = ((is_array($allowed_sem_status)) ? " s.status IN('" . join("','",$allowed_sem_status) . "') AND " : "");

$db = new DB_Seminar();
$db2 = new DB_Seminar();
$_semester = new SemesterData();
$element = new StudipLitCatElement();

if ($_REQUEST['cmd'] == 'check' && !isset($_REQUEST['_check_list'])){
    $_REQUEST['_check_list'] = array();
}

my_session_var(array('_semester_id','_inst_id','_anker_id','_open','_lit_data','_lit_data_id','_check_list','_check_plugin'));

if (isset($_REQUEST['send'])){
    $_anker_id = null;
    $_open = null;
    $_lit_data = null;
    $_lit_data_id = null;
    $_check_list = null;
}

if (isset($_REQUEST['open_element'])){
    $_open[$_REQUEST['open_element']] = true;
    $_anker_id = $_REQUEST['open_element'];
}
if (isset($_REQUEST['close_element'])){
    unset($_open[$_REQUEST['close_element']]);
    $_anker_id = $_REQUEST['close_element'];
}
if (isset($_GET['_catalog_id'])){
    $_anker_id = $_GET['_catalog_id'];
}
if ($_REQUEST['cmd'] == 'markall' && is_array($_lit_data)){
    $_check_list = array_keys($_lit_data);
}
if ($_REQUEST['cmd'] == 'open_all' && is_array($_lit_data)){
    $_open = array_keys($_lit_data);
    $_open = array_flip($_open);
}
if ($_REQUEST['cmd'] == 'close_all'){
    $_anker_id = null;
    $_open = null;
}
if ($_REQUEST['cmd'] == 'check' && is_array($_check_list) && is_array($_lit_data)){
    foreach ($_check_list as $el){
        $check = StudipLitSearch::CheckZ3950($_lit_data[$el]['accession_number'], $_check_plugin);
        if (is_array($_lit_data[$el]['check_accession'])){
            $_lit_data[$el]['check_accession'] = array_merge((array)$_lit_data[$el]['check_accession'],(array)$check);
        } else {
            $_lit_data[$el]['check_accession'] = $check;
        }
    }
}

if (isset($_REQUEST['_semester_id']) && $_REQUEST['_semester_id'] != 'all'){
    $_sem_sql = "  LEFT JOIN seminare s ON ($_sem_status_sql c.seminar_id=s.Seminar_id)
                LEFT JOIN semester_data sd
                ON (( s.start_time <= sd.beginn AND sd.beginn <= ( s.start_time + s.duration_time )
                OR ( s.start_time <= sd.beginn AND s.duration_time =  - 1 )) AND semester_id='" . $_REQUEST['_semester_id'] . "')
                LEFT JOIN lit_list d ON (s.Seminar_id = d.range_id AND semester_id IS NOT NULL)";
    $_sem_sql2 = "INNER JOIN semester_data sd
                ON (( s.start_time <= sd.beginn AND sd.beginn <= ( s.start_time + s.duration_time )
                OR ( s.start_time <= sd.beginn AND s.duration_time =  - 1 )) AND semester_id='" . $_REQUEST['_semester_id'] . "') ";
} else {
    $_sem_sql = "  LEFT JOIN seminare s ON ($_sem_status_sql c.seminar_id=s.Seminar_id)
                LEFT JOIN lit_list d ON (s.Seminar_id = d.range_id) ";
    $_sem_sql2 = "";
}

$_is_fak = false;
$_lit_admin_ids = get_lit_admin_ids();
$_is_lit_admin = (is_array($_lit_admin_ids) && count($_lit_admin_ids));

$_search_plugins = array_keys(StudipLitSearch::GetAvailablePlugins());
if (in_array('Studip', $_search_plugins)){
    array_splice($_search_plugins,  array_search('Studip', $_search_plugins), 1);
}
$preferred_plugin = StudipLitSearch::getPreferredPlugin();
if ($preferred_plugin && in_array($preferred_plugin, $_search_plugins)){
    array_splice($_search_plugins,  array_search($preferred_plugin, $_search_plugins), 1);
    array_unshift($_search_plugins,$preferred_plugin);
}

?>
<table width="100%" cellspacing=0 cellpadding=0 border=0>
    <?
    if ($msg) {
        echo "<tr> <td class=\"blank\" colspan=2><br>";
        parse_msg ($msg);
        echo "</td></tr>";
    }
    ?>
    <tr>
        <td class="blank" colspan=2>&nbsp;
            <form name="choose_institute" action="<?=$PHP_SELF?>?send=1" method="POST">
            <?= CSRFProtection::tokenTag() ?>
            <table cellpadding="0" cellspacing="0" border="0" width="99%" align="center">
                <tr>
                    <td class="steel1">
                        <font size=-1><br><b><?=_("Bitte w&auml;hlen Sie die Einrichtung und das Semester aus, f&uuml;r die Sie die Literaturliste anschauen wollen:")?></b><br>&nbsp; </font>
                    </td>
                </tr>
                <tr>
                    <td class="steel1">
                    <font size=-1><select name="_inst_id" size="1" style="vertical-align:middle">
                    <?
                    if ($auth->auth['perm'] == "root"){
                        $db->query("SELECT a.Institut_id, a.Name, 1 AS is_fak, COUNT(DISTINCT(catalog_id)) as anzahl FROM Institute a
                                    LEFT JOIN Institute b ON (a.Institut_id = b.fakultaets_id AND b.fakultaets_id != b.Institut_id)
                                    LEFT JOIN seminar_inst c ON (c.Institut_id=b.Institut_id)
                                    $_sem_sql
                                    LEFT JOIN lit_list_content e USING(list_id)
                                    WHERE a.Institut_id=a.fakultaets_id
                                    GROUP BY a.Institut_id ORDER BY Name");
                    } elseif (!$_is_lit_admin) {
                        $db->query("SELECT a.Institut_id,b.Name, IF(b.Institut_id=b.fakultaets_id,1,0) AS is_fak,COUNT(DISTINCT(catalog_id)) as anzahl
                                    FROM user_inst a LEFT JOIN Institute b USING (Institut_id)
                                    LEFT JOIN Institute f ON (f.fakultaets_id=b.institut_id OR f.Institut_id=b.Institut_id)
                                    LEFT JOIN seminar_inst c ON (c.Institut_id=f.Institut_id)
                                    $_sem_sql
                                    LEFT JOIN lit_list_content e USING(list_id)
                                    WHERE a.user_id='$user->id' AND a.inst_perms='admin'
                                    GROUP BY a.Institut_id ORDER BY is_fak,b.Name");
                    } else {
                        $db->query("SELECT b.Institut_id,b.Name, IF(b.Institut_id=b.fakultaets_id,1,0) AS is_fak,COUNT(DISTINCT(catalog_id)) as anzahl
                                    FROM Institute b
                                    LEFT JOIN Institute f ON (f.fakultaets_id=b.institut_id OR f.Institut_id=b.Institut_id)
                                    LEFT JOIN seminar_inst c ON (c.Institut_id=f.Institut_id)
                                    $_sem_sql
                                    LEFT JOIN lit_list_content e USING(list_id)
                                    WHERE b.Institut_id IN('" . join("','", $_lit_admin_ids) . "')
                                    GROUP BY b.Institut_id ORDER BY is_fak,b.Name");
                    }

                    printf ("<option value=\"-1\">%s</option>\n", _("-- bitte Einrichtung ausw&auml;hlen --"));
                    while ($db->next_record()){
                        printf ("<option value=\"%s\" style=\"%s\" %s>%s </option>\n", $db->f("Institut_id"),($db->f("is_fak") ? "font-weight:bold;" : ""),
                                ($db->f("Institut_id") == $_REQUEST['_inst_id'] ? " selected " : ""), htmlReady(substr($db->f("Name"), 0, 70)) . " (" . $db->f("anzahl") . ")");
                        if ($db->f("is_fak")){
                            if ($db->f("Institut_id") == $_REQUEST['_inst_id']){
                                $_is_fak = true;
                            }
                            $db2->query("SELECT a.Institut_id, a.Name, COUNT(DISTINCT(catalog_id)) as anzahl FROM Institute a
                                        LEFT JOIN seminar_inst c USING(Institut_id)
                                        $_sem_sql
                                        LEFT JOIN lit_list_content e USING(list_id)
                                        WHERE fakultaets_id='" .$db->f("Institut_id") . "' AND a.institut_id!='" .$db->f("Institut_id") . "'
                                        GROUP BY a.Institut_id ORDER BY Name");
                            while ($db2->next_record()){
                                printf("<option value=\"%s\" %s>&nbsp;&nbsp;&nbsp;&nbsp;%s </option>\n", $db2->f("Institut_id"),
                                ($db2->f("Institut_id") == $_REQUEST['_inst_id'] ? " selected " : ""),htmlReady(substr($db2->f("Name"), 0, 70)) . " (" . $db2->f("anzahl") . ")");
                            }
                        }
                    }
                    ?>
                </select>&nbsp;
                <select name="_semester_id" style="vertical-align:middle">
                    <option value="all"><?=_("alle")?></option>
                    <?
                    foreach($_semester->getAllSemesterData() as $sem){
                        ?>
                        <option value="<?=$sem['semester_id']?>" <?=($sem['semester_id'] == $_REQUEST['_semester_id'] ? " selected " : "")?>><?=htmlReady($sem['name'])?></option>
                        <?
                    }
                    ?>
                </select>
                </font>&nbsp;
                <?= Button::create(_('Auswählen')) ?>
                </td>
            </tr>
            <tr>
                <td class="steel1">
                    &nbsp;
                </td>
            </tr>
        </form>
        <form name="check_elements" action="<?=$PHP_SELF?>?cmd=check" method="POST">
            <?= CSRFProtection::tokenTag() ?>
            <tr>
                <td class="steel1" align="right">
                    <select name="_check_plugin" style="vertical-align:middle">
                    <?
                    foreach($_search_plugins as $sp){
                        ?>
                        <option <?=($sp == $_check_plugin ? " selected " : "")?>><?=htmlReady($sp)?></option>
                        <?
                    }
                    ?>
                </select>
                    <?= Button::create(_('Verfügbarkeit'), array('title' => _("Alle markierten Einträge im ausgewählten Katalog suchen"), 'style' => "vertical-align:middle")) ?>
                    &nbsp;&nbsp;&nbsp;
                    <?= LinkButton::create(_('Auswählen'), $PHP_SELF.'?cmd=markal', array('title' => _("Alle Einträge markieren"))) ?>
                    <br>&nbsp;
                </td>
            </tr>

        </table>
        <?
    if ($_is_fak){
        $sql = "SELECT f.*
                FROM Institute a INNER JOIN seminar_inst c USING (Institut_id)
                INNER JOIN seminare s ON ($_sem_status_sql c.seminar_id=s.Seminar_id)
                $_sem_sql2
                INNER JOIN lit_list d ON (c.seminar_id = d.range_id)
                INNER JOIN lit_list_content e USING(list_id)
                INNER JOIN lit_catalog f USING(catalog_id)
                WHERE fakultaets_id='" . $_REQUEST['_inst_id'] . "' GROUP BY e.catalog_id ORDER BY dc_date";
        $sql2 = "SELECT s.Name,s.Seminar_id,admission_turnout, COUNT(DISTINCT(su.user_id)) AS participants FROM Institute a INNER JOIN seminar_inst c USING (Institut_id)
                INNER JOIN seminare s ON ($_sem_status_sql c.seminar_id=s.Seminar_id)
                $_sem_sql2
                INNER JOIN lit_list d ON (c.seminar_id = d.range_id)
                INNER JOIN lit_list_content e USING(list_id)
                LEFT JOIN seminar_user su ON (c.seminar_id = su.seminar_id)
                WHERE fakultaets_id='" . $_REQUEST['_inst_id'] . "' AND catalog_id='?' GROUP BY s.Seminar_id ORDER BY s.Name";
    } else {
        $sql = "SELECT f.*
                FROM seminar_inst c
                INNER JOIN seminare s ON ($_sem_status_sql c.seminar_id=s.Seminar_id)
                $_sem_sql2
                INNER JOIN lit_list d ON (c.seminar_id = d.range_id)
                INNER JOIN lit_list_content e USING(list_id)
                INNER JOIN lit_catalog f USING(catalog_id)
                WHERE c.institut_id='" . $_REQUEST['_inst_id'] . "' GROUP BY e.catalog_id ORDER BY dc_date";
        $sql2 = "SELECT s.Name,s.Seminar_id, admission_turnout, COUNT(DISTINCT(su.user_id)) AS participants FROM seminar_inst c
                INNER JOIN seminare s ON ($_sem_status_sql c.seminar_id=s.Seminar_id)
                $_sem_sql2
                INNER JOIN lit_list d ON (c.seminar_id = d.range_id)
                INNER JOIN lit_list_content e USING(list_id)
                LEFT JOIN seminar_user su ON (c.seminar_id = su.seminar_id)
                WHERE c.institut_id='" . $_REQUEST['_inst_id'] . "' AND catalog_id='?' GROUP BY s.Seminar_id ORDER BY s.Name";
    }
    if ($_lit_data_id != md5($sql)){
        $db->query($sql);
        $_lit_data_id = md5($sql);
        while ($db->next_record()){
            $_lit_data[$db->f('catalog_id')] = $db->Record;
        }
    }
    if (is_array($_lit_data)){
        echo "\n<table width=\"99%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" align=\"center\"><tr><th align=\"left\">";
        if (is_array($_open) && count($_open)){
            echo "\n<a href=\"$PHP_SELF?cmd=close_all\" class=\"tree\"><img class=\"text-top\" src=\"". Assets::image_path('icons/16/blue/arr_1down.png') ."\"> " . _("Alle Einträge zuklappen") . "</a>";
        } else {
            echo "\n<a href=\"$PHP_SELF?cmd=open_all\" class=\"tree\"><img class=\"text-top\" src=\"". Assets::image_path('icons/16/blue/arr_1right.png') ."\"> " . _("Alle Einträge aufklappen") . "</a>";
        }
        echo "\n</th><th align=\"right\">";
        echo "<a href=\"lit_overview_print_view.php\" class=\"tree\" target=\"_blank\">" . Assets::img('icons/16/blue/print.png', array('class' => 'text-top')) . " " . _("Druckansicht") ."</a></th>";
        echo "</tr></table>";
        foreach ($_lit_data as $cid => $data){
            $element->setValues($data);
            if ($element->getValue('catalog_id')){
                if ($_anker_id == $element->getValue('catalog_id')){
                    $icon = "<a name=\"anker\">";
                    $icon .= Assets::img('icons/16/grey/literature.png', array('class' => 'text-top'));
                    $icon .= "</a>";
                } else {
                    $icon = Assets::img('icons/16/grey/literature.png', array('class' => 'text-top'));
                }
                $ampel = "";
                if ($_check_plugin && isset($_lit_data[$cid]['check_accession'][$_check_plugin])){
                    $check = $_lit_data[$cid]['check_accession'][$_check_plugin];
                    if ($check['found']){
                        $ampel_pic = 'icons/16/green/accept.png';
                        $tt = _("gefunden");
                    } else if (count($check['error'])){
                        $ampel_pic = 'icons/16/black/exclaim.png';
                        $tt = _("keine automatische Suche möglich");
                    } else {
                        $ampel_pic = 'icons/16/red/decline.png';
                        $tt =_("nicht gefunden");
                    }
                    $ampel = '<span ' . tooltip($tt,false) . '><img class="middle" src="' . Assets::image_path($ampel_pic) . '" > (' . $_check_plugin . ')</span>&nbsp;&nbsp;';
                }
                $addon = $ampel . '<input type="checkbox" style="vertical-align:middle;" name="_check_list[]" value="' . $element->getValue('catalog_id') . '" '
                        . (is_array($_check_list) && in_array($element->getValue('catalog_id'), $_check_list) ? 'checked' : '') .' >';
                $open = isset($_open[$element->getValue('catalog_id')]) ? 'open' : 'close';
                $link = $PHP_SELF . '?' . (isset($_open[$element->getValue('catalog_id')]) ? 'close' : 'open') . '_element=' . $element->getValue('catalog_id') . '#anker';
                $titel = '<a href="' . $link . '" class="tree">' . htmlReady(my_substr($element->getShortName(),0,85)) . '</a>';
                echo "\n<table width=\"99%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" align=\"center\"><tr>";
                printhead(0,0,$link,$open,true,$icon,$titel,$addon);
                echo "\n</tr></table>";
                if (!is_array($_lit_data[$cid]['sem_data'])){
                        $db->query(str_replace('?', $element->getValue('catalog_id'), $sql2));
                        while ($db->next_record()){
                            $_lit_data[$cid]['sem_data'][$db->f('Seminar_id')] = $db->Record;
                        }
                    }
                if (!is_array($_lit_data[$cid]['doz_data'])){
                        $db->query("SELECT position, Nachname,username,su.user_id FROM seminar_user su INNER JOIN auth_user_md5 USING(user_id)
                                    WHERE status='dozent' AND seminar_id IN('" . join("','", array_keys($_lit_data[$cid]['sem_data'])) . "')
                                    ORDER BY position, Nachname");
                        $_lit_data[$cid]['doz_data'] = array();
                        while ($db->next_record()){
                            $_lit_data[$cid]['doz_data'][$db->f('user_id')] = $db->Record;
                        }
                }
                if ($open == 'open'){
                    $edit = "";
                    $content = "";
                    $estimated_p = 0;
                    $participants = 0;
                    $edit .= LinkButton::create(_('Verfügbarkeit'), $PHP_SELF.'?_catalog_id=' . $element->getValue('catalog_id') . '#anker', array('title' => _("Verfügbarkeit überprüfen")));
                    $edit .= "&nbsp;";
                    $edit .= LinkButton::create(_('Details'), 'admin_lit_element.php?_catalog_id=' . $element->getValue('catalog_id'), array('title' => _("Detailansicht dieses Eintrages ansehen.")));
                    $edit .= "&nbsp;";
                    echo "\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\">";
                    $content .= "<b>" . _("Titel:") ."</b>&nbsp;&nbsp;" . htmlReady($element->getValue("dc_title"),true,true) . "<br>";
                    $content .= "<b>" . _("Autor; weitere Beteiligte:") ."</b>&nbsp;&nbsp;" . htmlReady($element->getValue("authors"),true,true) . "<br>";
                    $content .= "<b>" . _("Erschienen:") ."</b>&nbsp;&nbsp;" . htmlReady($element->getValue("published"),true,true) . "<br>";
                    $content .= "<b>" . _("Identifikation:") ."</b>&nbsp;&nbsp;" . formatLinks($element->getValue("dc_identifier")) . "<br>";
                    if ($element->getValue("lit_plugin") != "Studip"){
                        $content .= "<b>" . _("Externer Link:") ."</b>&nbsp;&nbsp;";
                        if (($link = $element->getValue("external_link"))){
                            $content.= formatReady(" [" . $element->getValue("lit_plugin"). "]" . $link);
                        } else {
                            $content .= _("(Kein externer Link vorhanden.)");
                        }
                        $content .= "<br>";
                    }
                    $content .= "<b>" . _("Veranstaltungen:") . "</b>&nbsp;&nbsp;";
                    foreach ($_lit_data[$cid]['sem_data'] as $sem_data){
                        $content .= '<a href="details.php?sem_id=' . $sem_data['Seminar_id'] . '&send_from_search=1&send_from_search_page=' . URLHelper::getURL() . '">' . htmlReady(my_substr($sem_data["Name"],0,50)) . "</a>, ";
                        $estimated_p += $sem_data['admission_turnout'];
                        $participants += $sem_data['participants'];
                    }
                    $content = substr($content,0,-2);
                    $content .= "<br>";
                    $content .= "<b>" . _("Dozenten:") . "</b>&nbsp;&nbsp;";
                    foreach ($_lit_data[$cid]['doz_data'] as $doz_data){
                        $content .= '<a href="about.php?username=' . $doz_data['username'] . '">' . htmlReady($doz_data["Nachname"]) . "</a>, ";
                    }
                    $content = substr($content,0,-2);
                    $content .= "<br>";
                    $content .= "<b>" . _("Teilnehmeranzahl (erwartet/angemeldet):") . "</b>&nbsp;&nbsp;";
                    $content .= ($estimated_p ? $estimated_p : _("unbekannt"));
                    $content .= ' / ' . (int)$participants;
                    $content .= "<br>";
                    if ($_REQUEST['_catalog_id'] == $element->getValue('catalog_id') ){
                        $_lit_data[$cid]['check_accession'] = StudipLitSearch::CheckZ3950($element->getValue('accession_number'));
                    }
                    if (is_array($_lit_data[$cid]['check_accession'])){
                        $content .= "<div style=\"margin-top: 10px;border: 1px solid black;padding: 5px; width:96%;\"<b>" ._("Verf&uuml;gbarkeit in externen Katalogen:") . "</b><br>";
                        foreach ( $_lit_data[$cid]['check_accession'] as $plugin_name => $ret){
                            $content .= "<b>&nbsp;{$plugin_name}&nbsp;</b>";
                            if ($ret['found']){
                                $content .= _("gefunden") . "&nbsp;";
                                $element->setValue('lit_plugin', $plugin_name);
                                if (($link = $element->getValue("external_link"))){
                                    $content.= formatReady(" [" . $element->getValue("lit_plugin"). "]" . $link);
                                } else {
                                    $content .= _("(Kein externer Link vorhanden.)");
                                }
                            } elseif (count($ret['error'])){
                                $content .= '<span style="color:red;">' . htmlReady($ret['error'][0]['msg']) . '</span>';
                            } else {
                                $content .= _("<u>nicht</u> gefunden") . "&nbsp;";
                            }
                            $content .= "<br>";
                        }
                        $content .= "</div>";
                    }
                    printcontent(0,0,$content,$edit);
                    echo "\n</table>";
                }
            }
        }
    }
    ?>
    </td>
    </tr>
    <tr>
    <td class="blank">
    &nbsp;
    </td>
    </tr>
    </table>
    </form>
<?
my_session_close();
page_close();
?>
