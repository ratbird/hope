<?php
# Lifter002: TODO
# Lifter003: TEST
# Lifter007: TODO
# Lifter010: TODO

use Studip\Button, Studip\LinkButton;

require '../lib/bootstrap.php';

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check("admin");
require_once ('lib/dbviews/literatur.view.php');
require_once ('lib/classes/StudipLitCatElement.class.php');

require_once ('lib/visual.inc.php');
require_once ('config.inc.php');

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

PageLayout::setTitle(_("�bersicht verwendeter Literatur"));
Navigation::activateItem('/tools/literature');

include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   //hier wird der "Kopf" nachgeladen

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

if(Request::option('_semester_id'))
     $_SESSION['_semester_id'] = Request::option('_semester_id');

if(Request::option('_inst_id'))
     $_SESSION['_inst_id'] = Request::option('_inst_id');

if(Request::option('_anker_id'))
     $_SESSION['_anker_id'] = Request::option('_anker_id');

if(Request::optionArray('_open'))
     $_SESSION['_open'] = Request::optionArray('_open');

if(Request::quotedArray('_lit_data'))
     $_SESSION['_lit_data'] = Request::quotedArray('_lit_data');

if(Request::option('_lit_data_id'))
     $_SESSION['_lit_data_id'] = Request::option('_lit_data_id');

$_check_list = Request::optionArray('_check_list');
if(!empty ($_check_list))
     $_SESSION['_check_list'] = Request::optionArray('_check_list');

if(Request::option('_check_plugin'))
     $_SESSION['_check_plugin'] = Request::option('_check_plugin');

$_semester = new SemesterData();
$element = new StudipLitCatElement();

if (Request::option('cmd') == 'check' && !isset($_check_list)){
    Request::set('_check_list',array());
}

//my_session_var(array('_semester_id','_inst_id','_anker_id','_open','_lit_data','_lit_data_id','_check_list','_check_plugin'));

if (Request::quoted('send')){
    $_SESSION['_anker_id'] = null;
    $_SESSION['_open'] = null;
    $_SESSION['_lit_data'] = null;
    $_SESSION['_lit_data_id'] = null;
    $_SESSION['_check_list'] = null;
}

if (Request::get('open_element')){
    $_SESSION['_open'][Request::option('open_element')] = true;
    $_anker_id = Request::option('open_element');
}
if (Request::get('close_element')){
    unset($_SESSION['_open'][Request::option('close_element')]);
    $_SESSION['_anker_id'] = Request::option('close_element');
}
if (Request::option('_catalog_id')){
    $_SESSION['_anker_id'] = Request::option('_catalog_id');
}

if (Request::option('cmd') == 'markall' && is_array($_SESSION['_lit_data'])){
    
    $_SESSION['_check_list'] = array_keys($_SESSION['_lit_data']);
}
if (Request::option('cmd') == 'open_all' && is_array($_SESSION['_lit_data'])){
    $_SESSION['_open'] = array_keys($_SESSION['_lit_data']);
    $_SESSION['_open'] = array_flip($_SESSION['_open']);
}
if (Request::option('cmd') == 'close_all'){
    $_SESSION['_anker_id'] = null;
    $_SESSION['_open'] = null;
}
if (Request::option('cmd') == 'check' && is_array($_SESSION['_check_list']) && is_array($_SESSION['_lit_data'])){
    foreach ($_SESSION['_check_list'] as $el){
        $check = StudipLitSearch::CheckZ3950($_SESSION['_lit_data'][$el]['accession_number'], $_SESSION['_check_plugin']);
        if (is_array($_SESSION['_lit_data'][$el]['check_accession'])){
            $_SESSION['_lit_data'][$el]['check_accession'] = array_merge((array)$_SESSION['_lit_data'][$el]['check_accession'],(array)$check);
        } else {
            $_SESSION['_lit_data'][$el]['check_accession'] = $check;
        }
    }
}

if (Request::option('_semester_id') && Request::option('_semester_id') != 'all'){
    $_sem_sql = "  LEFT JOIN seminare s ON ($_sem_status_sql c.seminar_id=s.Seminar_id)
                LEFT JOIN semester_data sd
                ON (( s.start_time <= sd.beginn AND sd.beginn <= ( s.start_time + s.duration_time )
                OR ( s.start_time <= sd.beginn AND s.duration_time =  - 1 )) AND semester_id='" . Request::option('_semester_id') . "')
                LEFT JOIN lit_list d ON (s.Seminar_id = d.range_id AND semester_id IS NOT NULL)";
    $_sem_sql2 = "INNER JOIN semester_data sd
                ON (( s.start_time <= sd.beginn AND sd.beginn <= ( s.start_time + s.duration_time )
                OR ( s.start_time <= sd.beginn AND s.duration_time =  - 1 )) AND semester_id='" . Request::option('_semester_id') . "') ";
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
            <form name="choose_institute" action="<?=URLHelper::getLink('?send=1')?>" method="POST">
            <?= CSRFProtection::tokenTag() ?>
            <table cellpadding="0" cellspacing="0" border="0" width="99%" align="center">
                <tr>
                    <td class="table_row_even">
                        <font size=-1><br><b><?=_("Bitte w&auml;hlen Sie die Einrichtung und das Semester aus, f&uuml;r die Sie die Literaturliste anschauen wollen:")?></b><br>&nbsp; </font>
                    </td>
                </tr>
                <tr>
                    <td class="table_row_even">
                    <font size=-1><select name="_inst_id" size="1" style="vertical-align:middle">
                    <?
                    // Prepare inner statement that obtains all institutes
                    // for a given faculty
                    $query = "SELECT a.Institut_id, a.Name, COUNT(DISTINCT catalog_id) AS anzahl
                              FROM Institute AS a
                              LEFT JOIN seminar_inst AS c USING (Institut_id)
                              {$_sem_sql}
                              LEFT JOIN lit_list_content AS e USING (list_id)
                              WHERE fakultaets_id = ? AND a.institut_id != fakultaets_id
                              GROUP BY a.Institut_id
                              ORDER BY Name";
                    $institute_statement = DBManager::get()->prepare($query);

                    // Prepare and execute statement that obtains a list of
                    // all institutes and faculties the user has access to
                    $parameters = array();
                    if ($auth->auth['perm'] == 'root'){
                        $query = "SELECT a.Institut_id, a.Name, 1 AS is_fak, COUNT(DISTINCT catalog_id) AS anzahl
                                  FROM Institute AS a
                                  LEFT JOIN Institute AS b ON (a.Institut_id = b.fakultaets_id AND b.fakultaets_id != b.Institut_id)
                                  LEFT JOIN seminar_inst AS c ON (c.Institut_id = b.Institut_id)
                                  {$_sem_sql}
                                  LEFT JOIN lit_list_content AS e USING (list_id)
                                  WHERE a.Institut_id = a.fakultaets_id
                                  GROUP BY a.Institut_id
                                  ORDER BY Name";
                    } elseif (!$_is_lit_admin) {
                        $query = "SELECT a.Institut_id, b.Name, b.Institut_id = b.fakultaets_id AS is_fak,
                                         COUNT(DISTINCT catalog_id) AS anzahl
                                  FROM user_inst AS a
                                  LEFT JOIN Institute AS b USING (Institut_id)
                                  LEFT JOIN Institute AS f ON (b.institut_id IN (f.fakultaets_id, f.Institut_id))
                                  LEFT JOIN seminar_inst AS c ON (c.Institut_id = f.Institut_id)
                                  {$_sem_sql}
                                  LEFT JOIN lit_list_content AS e USING (list_id)
                                  WHERE a.user_id = ? AND a.inst_perms = 'admin'
                                  GROUP BY a.Institut_id
                                  ORDER BY is_fak, b.Name";
                        $parameters[] = $user->id;
                    } else {
                        $query = "SELECT b.Institut_id, b.Name, b.Institut_id = b.fakultaets_id AS is_fak,
                                         COUNT(DISTINCT catalog_id) AS anzahl
                                  FROM Institute AS b
                                  LEFT JOIN Institute AS f ON (b.institut_id IN (f.fakultaets_id, f.Institut_id))
                                  LEFT JOIN seminar_inst AS c ON (c.Institut_id = f.Institut_id)
                                  {$_sem_sql}
                                  LEFT JOIN lit_list_content AS e USING (list_id)
                                  WHERE b.Institut_id IN (?)
                                  GROUP BY b.Institut_id
                                  ORDER BY is_fak, b.Name";
                        $parameters[] = $_lit_admin_ids;
                    }
                    $statement = DBManager::get()->prepare($query);
                    $statement->execute($parameters);
                    $institutes = $statement->fetchAll(PDO::FETCH_ASSOC);

                    printf ("<option value=\"-1\">%s</option>\n", _("-- bitte Einrichtung ausw&auml;hlen --"));
                    foreach ($institutes as $institute) {
                        printf("<option value=\"%s\" style=\"%s\" %s>%s </option>\n",
                               $institute['Institut_id'],
                               $institute['is_fak'] ? 'font-weight:bold;' : '',
                               $institute['Institut_id'] == Request::option('_inst_id') ? ' selected ' : '',
                               htmlReady(substr($institute['Name'], 0, 70)) . ' (' . $institute['anzahl'] . ')');
                        if ($institute['is_fak']) {
                            if ($institute['Institut_id'] == Request::option('_inst_id')){
                                $_is_fak = true;
                            }

                            $institute_statement->execute(array($institute['Institut_id']));
                            while ($row = $institute_statement->fetch(PDO::FETCH_ASSOC)) {
                                printf("<option value=\"%s\" %s>&nbsp;&nbsp;&nbsp;&nbsp;%s </option>\n",
                                       $row['Institut_id'],
                                       $row['Institut_id'] == Request::option('_inst_id') ? ' selected ' : '',
                                       htmlReady(substr($row['Name'], 0, 70)) . ' (' . $row['anzahl'] . ')');
                            }
                            $institute_statement->closeCursor();
                        }
                    }
                    ?>
                </select>&nbsp;
                <select name="_semester_id" style="vertical-align:middle">
                    <option value="all"><?=_("alle")?></option>
                    <?
                    foreach($_semester->getAllSemesterData() as $sem){
                        ?>
                        <option value="<?=$sem['semester_id']?>" <?=($sem['semester_id'] == Request::option('_semester_id') ? " selected " : "")?>><?=htmlReady($sem['name'])?></option>
                        <?
                    }
                    ?>
                </select>
                </font>&nbsp;
                <?= Button::create(_('Ausw�hlen')) ?>
                </td>
            </tr>
            <tr>
                <td class="table_row_even">
                    &nbsp;
                </td>
            </tr>
        </form>
            <form name="check_elements" action="<?=URLHelper::getLink('?cmd=check')?>" method="POST">
            <?= CSRFProtection::tokenTag() ?>
            <tr>
                <td class="table_row_even" align="right">
                    <select name="_check_plugin" style="vertical-align:middle">
                    <?
                    foreach($_search_plugins as $sp){
                        ?>
                        <option <?=($sp == $_SESSION['_check_plugin'] ? " selected " : "")?>><?=htmlReady($sp)?></option>
                        <?
                    }
                    ?>
                </select>
                    <?= Button::create(_('Verf�gbarkeit'), array('title' => _("Alle markierten Eintr�ge im ausgew�hlten Katalog suchen"), 'style' => "vertical-align:middle")) ?>
                    &nbsp;&nbsp;&nbsp;
                    <?= LinkButton::create(_('Ausw�hlen'), URLHelper::getURL('?cmd=markall'), array('title' => _("Alle Eintr�ge markieren"))) ?>
                    <br>&nbsp;
                </td>
            </tr>

        </table>
        <?
    if ($_is_fak) {
        $sql = "SELECT f.*
                FROM Institute AS a
                INNER JOIN seminar_inst AS c USING (Institut_id)
                INNER JOIN seminare AS s ON ({$_sem_status_sql} c.seminar_id = s.Seminar_id)
                {$_sem_sql2}
                INNER JOIN lit_list AS d ON (c.seminar_id = d.range_id)
                INNER JOIN lit_list_content AS e USING (list_id)
                INNER JOIN lit_catalog AS f USING (catalog_id)
                WHERE fakultaets_id = ?
                GROUP BY e.catalog_id
                ORDER BY dc_date";
        $sql2 = "SELECT s.Name, s.Seminar_id, admission_turnout, COUNT(DISTINCT su.user_id) AS participants
                 FROM Institute AS a
                 INNER JOIN seminar_inst AS c USING (Institut_id)
                 INNER JOIN seminare AS s ON ({$_sem_status_sql} c.seminar_id = s.Seminar_id)
                 {$_sem_sql2}
                 INNER JOIN lit_list AS d ON (c.seminar_id = d.range_id)
                 INNER JOIN lit_list_content AS e USING (list_id)
                 LEFT JOIN seminar_user AS su ON (c.seminar_id = su.seminar_id)
                 WHERE fakultaets_id = ? AND catalog_id = ?
                 GROUP BY s.Seminar_id
                 ORDER BY s.Name";
    } else {
        $sql = "SELECT f.*
                FROM seminar_inst AS c
                INNER JOIN seminare AS s ON ({$_sem_status_sql} c.seminar_id = s.Seminar_id)
                {$_sem_sql2}
                INNER JOIN lit_list AS d ON (c.seminar_id = d.range_id)
                INNER JOIN lit_list_content AS e USING (list_id)
                INNER JOIN lit_catalog AS f USING (catalog_id)
                WHERE c.institut_id = ?
                GROUP BY e.catalog_id
                ORDER BY dc_date";
        $sql2 = "SELECT s.Name, s.Seminar_id, admission_turnout, COUNT(DISTINCT su.user_id) AS participants
                 FROM seminar_inst AS c
                 INNER JOIN seminare AS s ON ({$_sem_status_sql} c.seminar_id = s.Seminar_id)
                 {$_sem_sql2}
                 INNER JOIN lit_list AS d ON (c.seminar_id = d.range_id)
                 INNER JOIN lit_list_content AS e USING(list_id)
                 LEFT JOIN seminar_user AS su ON (c.seminar_id = su.seminar_id)
                 WHERE c.institut_id = ? AND catalog_id = ?
                 GROUP BY s.Seminar_id
                 ORDER BY s.Name";
    }
    if ($_SESSION['_lit_data_id'] != md5($sql . '#' . Request::option('_inst_id'))) {
        $_SESSION['_lit_data_id'] = md5($sql . '#' . Request::option('_inst_id'));

        $statement = DBManager::get()->prepare($sql);
        $statement->execute(array(Request::option('_inst_id')));
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $_SESSION['_lit_data'][$row['catalog_id']] = $row;
        }
    }
    if (is_array($_SESSION['_lit_data'])) {
        echo "\n<table width=\"99%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" align=\"center\"><tr><th align=\"left\">";
        if (is_array($_SESSION['_open']) && count($_SESSION['_open'])){
            echo "\n<a href=\"".URLHelper::getLink('?cmd=close_all')."\" class=\"tree\"><img class=\"text-top\" src=\"". Assets::image_path('icons/16/blue/arr_1down.png') ."\"> " . _("Alle Eintr�ge zuklappen") . "</a>";
        } else {
            echo "\n<a href=\"".URLHelper::getLink('?cmd=open_all')."\" class=\"tree\"><img class=\"text-top\" src=\"". Assets::image_path('icons/16/blue/arr_1right.png') ."\"> " . _("Alle Eintr�ge aufklappen") . "</a>";
        }
        echo "\n</th><th align=\"right\">";
        echo "<a href=\"lit_overview_print_view.php\" class=\"tree\" target=\"_blank\">" . Assets::img('icons/16/blue/print.png', array('class' => 'text-top')) . " " . _("Druckansicht") ."</a></th>";
        echo "</tr></table>";
        foreach ($_SESSION['_lit_data'] as $cid => $data){
            $element->setValues($data);
            if ($element->getValue('catalog_id')){
                if ($_SESSION['_anker_id'] == $element->getValue('catalog_id')){
                    $icon = "<a name=\"anker\">";
                    $icon .= Assets::img('icons/16/grey/literature.png', array('class' => 'text-top'));
                    $icon .= "</a>";
                } else {
                    $icon = Assets::img('icons/16/grey/literature.png', array('class' => 'text-top'));
                }
                $ampel = "";
                if ($_SESSION['_check_plugin'] && isset($_SESSION['_lit_data'][$cid]['check_accession'][$_SESSION['_check_plugin']])){
                    $check = $_SESSION['_lit_data'][$cid]['check_accession'][$_SESSION['_check_plugin']];
                    if ($check['found']){
                        $ampel_pic = 'icons/16/green/accept.png';
                        $tt = _("gefunden");
                    } else if (count($check['error'])){
                        $ampel_pic = 'icons/16/black/exclaim.png';
                        $tt = _("keine automatische Suche m�glich");
                    } else {
                        $ampel_pic = 'icons/16/red/decline.png';
                        $tt =_("nicht gefunden");
                    }
                    $ampel = '<span ' . tooltip($tt,false) . '><img class="middle" src="' . Assets::image_path($ampel_pic) . '" > (' . $_SESSION['_check_plugin'] . ')</span>&nbsp;&nbsp;';
                }
                $addon = $ampel . '<input type="checkbox" style="vertical-align:middle;" name="_check_list[]" value="' . $element->getValue('catalog_id') . '" '
                        . (is_array($_SESSION['_check_list']) && in_array($element->getValue('catalog_id'), $_SESSION['_check_list']) ? 'checked' : '') .' >';
                $open = isset($_open[$element->getValue('catalog_id')]) ? 'open' : 'close';
                $link = URLHelper::getLink('?' . (isset($_SESSION['_open'][$element->getValue('catalog_id')]) ? 'close' : 'open') . '_element=' . $element->getValue('catalog_id') . '#anker');
                $titel = '<a href="' . $link . '" class="tree">' . htmlReady(my_substr($element->getShortName(),0,85)) . '</a>';
                echo "\n<table width=\"99%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" align=\"center\"><tr>";
                printhead(0,0,$link,$open,true,$icon,$titel,$addon);
                echo "\n</tr></table>";
                if (!is_array($_SESSION['_lit_data'][$cid]['sem_data'])){
                    $statement = DBManager::get()->prepare($sql2);
                    $statement->execute(array(
                        Request::option('_inst_id'),
                        $element->getValue('catalog_id')
                    ));

                    $_SESSION['_lit_data'][$cid]['sem_data'] = array();
                    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                        $_SESSION['_lit_data'][$cid]['sem_data'][$row['Seminar_id']] = $row;
                    }
                }
                if (!is_array($_SESSION['_lit_data'][$cid]['doz_data'])) {
                    $query = "SELECT position, Nachname, username, user_id
                              FROM seminar_user
                              INNER JOIN auth_user_md5 USING (user_id)
                              WHERE status = 'dozent' AND seminar_id IN (?)
                              ORDER BY position, Nachname";
                    $statement = DBManager::get()->prepare($query);
                    $statement->execute(array(
                        array_keys($_SESSION['_lit_data'][$cid]['sem_data'])
                    ));

                    $_SESSION['_lit_data'][$cid]['doz_data'] = array();
                    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                        $_SESSION['_lit_data'][$cid]['doz_data'][$row['user_id']] = $row;
                    }
                }
                if ($open == 'open'){
                    $edit = "";
                    $content = "";
                    $estimated_p = 0;
                    $participants = 0;
                    $edit .= LinkButton::create(_('Verf�gbarkeit'), URLHelper::getURL('?_catalog_id=' . $element->getValue('catalog_id') . '#anker'), array('title' => _("Verf�gbarkeit �berpr�fen")));
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
                    foreach ($_SESSION['_lit_data'][$cid]['sem_data'] as $sem_data){
                        $content .= '<a href="details.php?sem_id=' . $sem_data['Seminar_id'] . '&send_from_search=1&send_from_search_page=' . URLHelper::getURL() . '">' . htmlReady(my_substr($sem_data["Name"],0,50)) . "</a>, ";
                        $estimated_p += $sem_data['admission_turnout'];
                        $participants += $sem_data['participants'];
                    }
                    $content = substr($content,0,-2);
                    $content .= "<br>";
                    $content .= "<b>" . _("Dozenten:") . "</b>&nbsp;&nbsp;";
                    foreach ($_SESSION['_lit_data'][$cid]['doz_data'] as $doz_data){
                        $content .= '<a href="dispatch.php/profile?username=' . $doz_data['username'] . '">' . htmlReady($doz_data["Nachname"]) . "</a>, ";
                    }
                    $content = substr($content,0,-2);
                    $content .= "<br>";
                    $content .= "<b>" . _("Teilnehmeranzahl (erwartet/angemeldet):") . "</b>&nbsp;&nbsp;";
                    $content .= ($estimated_p ? $estimated_p : _("unbekannt"));
                    $content .= ' / ' . (int)$participants;
                    $content .= "<br>";
                    if (Request::option('_catalog_id') == $element->getValue('catalog_id') ){
                        $_SESSION['_lit_data'][$cid]['check_accession'] = StudipLitSearch::CheckZ3950($element->getValue('accession_number'));
                    }
                    if (is_array($_SESSION['_lit_data'][$cid]['check_accession'])){
                        $content .= "<div style=\"margin-top: 10px;border: 1px solid black;padding: 5px; width:96%;\"<b>" ._("Verf&uuml;gbarkeit in externen Katalogen:") . "</b><br>";
                        foreach ( $_SESSION['_lit_data'][$cid]['check_accession'] as $plugin_name => $ret){
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
page_close();
?>
