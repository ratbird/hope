<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/*
admin_news.php - Ändern der News von Stud.IP
Copyright (C) 2001  André Noack <andre.noack@gmx.net>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/
page_open(array("sess"=> "Seminar_Session", "auth" =>"Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$auth->login_if($auth->auth["uid"] == "nobody");
$perm->check("autor");

require_once 'lib/messaging.inc.php';
require_once 'lib/visual.inc.php';
require_once 'lib/functions.php';
require_once 'lib/classes/AdminNewsController.class.php';


include ('lib/seminar_open.php'); // initialise Stud.IP-Session

$_include_additional_header .= "\n" . cssClassSwitcher::GetHoverJSFunction() . "\n";

URLHelper::bindLinkParam('news_range_id',$news_range_id);
URLHelper::bindLinkParam('news_range_name',$news_range_name);

if ($_REQUEST['range_id'] == "self"){
    $news_range_id = $auth->auth['uid'];
} else if (isset($_REQUEST['range_id'])){
    $news_range_id = $_REQUEST['range_id'];
}

if (!$news_range_id){
    $news_range_id = $auth->auth['uid'];
}

$HELP_KEYWORD = "Basis.News";
$CURRENT_PAGE = _("Verwaltung von News");

if ($list || $view || ($news_range_id != $user->id && $news_range_id != 'studip') && $view_mode != 'user'){
    include 'lib/admin_search.inc.php';
    if ($links_admin_data['topkat'] == 'sem' && !SeminarCategories::getByTypeId($SessSemName['art_num'])->studygroup_mode) {
        Navigation::activateItem('/admin/course/news');
    } elseif ($links_admin_data['topkat'] == 'inst') {
        Navigation::activateItem('/admin/institute/news');
    } else {
        Navigation::activateItem('/tools/news');
    }
    $news_range_id = $SessSemName[1];
    $news_range_name = '';
} else {
    Navigation::activateItem('/tools/news');
}

$news = new AdminNewsController();

$CURRENT_PAGE = ($SessSemName[1] && ($list || $view || ($news_range_id != $user->id && $news_range_id != 'studip') && $view_mode != 'user' ) ?  $SessSemName["header_line"] : $news->range_name ) . " - " . _("Verwaltung von News");

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head
if($news_range_id == $SessSemName[1]){
    include 'lib/include/admin_search_form.inc.php';
}

function callback_cmp_newsarray($a, $b) {
    return strnatcasecmp($a['name'], $b['name']); // Case insensitive string comparisons using a "natural order" algorithm
}

?>
<table cellspacing="0" cellpadding="0" border="0" width="100%">
<?

if ($perm->have_perm("admin"))  {
    if ($cmd=="search") {
        if (!$search) {
            $news->msg .= "error§" . _("Sie haben keinen Suchbegriff eingegeben!") . "§";
            $cmd="";
        } else {
            $news->search_range($search);
            if (is_array($news->search_result) && !count($news->search_result))
                $news->msg.="info§" . _("Die Suche ergab keine Treffer!") . "§";
            $cmd="";
        }
    }
}

if ($cmd == 'news_edit'){
    if (isset($_REQUEST['news_submit_x'])) $cmd = 'news_submit';
    if (isset($_REQUEST['news_range_search_x'])){
        $cmd = 'edit';
        $edit_news = $_REQUEST['news_id'];
    }
}

if ($cmd=="news_submit") {
    if (!trim(stripslashes($topic)) && trim(stripslashes($body))) $topic = addslashes(substr(trim(stripslashes($body)),0,30) . '...');
    if ($topic != "") {
        $edit_news = $news->update_news($news_id, $author, $topic, $body, $user_id, $date, $expire, $add_range, $allow_comments);
        if ($edit_news) $cmd = "edit";
        else $cmd = "";
    } else {
        $cmd = "edit";
        $news->msg .= "error§"._("Leere News k&ouml;nnen nicht gespeichert werden! Geben Sie immer &Uuml;berschrift oder Inhalt an!")."§";
    }
}
if ($cmd=="new_entry" &&
    isset($_REQUEST['change_rss_x']) &&
    get_config('NEWS_RSS_EXPORT_ENABLE') &&
    $news->get_news_range_perm($news_range_id) > 1){
        if (StudipNews::GetRssIdFromRangeId($news_range_id)){
            StudipNews::UnSetRssId($news_range_id);
            $news->msg .= "info§" . _("Der RSS Export wurde für diesen Bereich ausgeschaltet!") . "§";
        } else {
            StudipNews::SetRssId($news_range_id);
            $news->msg .= "info§" . _("Der RSS Export wurde für diesen Bereich eingeschaltet!")
                        . '<br>' . _("Bitte beachten Sie, dass damit die News dieses Bereiches auch von Personen die nicht im Stud.IP angemeldet sind abgerufen werden k&ouml;nnen!") . "§";
        }
        $cmd = '';
}

if ($news->msg) {
    echo "<tr><td class=\"blank\"><br />";
    parse_msg($news->msg,"§","blank","1");
    echo "</td></tr>";
}
$news->msg="";

if ($cmd=="edit") {
    if ($perm->have_perm("admin") && $search) {
        $news->search_range($search);

        if (!count($news->search_result)) {
            echo "<tr><td class=\"blank\"><br />";
            parse_msg("info§" . _("Die Suche ergab keine Treffer!") . "§","§","blank","1",FALSE);
            echo "</td></tr>";
        }
    } else {
        $news->search_range();
    }

    $news->edit_news($edit_news);
}

if ($cmd=="kill") {
    $news->kill_news($kill_news);
    $cmd="";
}

if ($news->msg) {
    echo "<tr><td class=\"blank\"><br />";
    parse_msg($news->msg,"§","blank","1");
    echo "</td></tr>";
}
$news->msg="";

if ($cmd=="new_entry") {
    if ($auth->auth["perm"]=="dozent" OR $auth->auth["perm"]=="tutor" OR $auth->auth["perm"]=="autor") $news->search_range(); // allow autors, needed for studygroups
    $news->edit_news();

}

if (!$cmd OR $cmd=="show") {
    if ($news->sms)
        $news->send_sms();
    if ($perm->have_perm("autor")) {    // allow autors, needed for studygroups
        if ($perm->have_perm("admin")) {
            echo"\n<tr><td class=\"blank\"><blockquote><br /><b>" . _("Bereichsauswahl") . "</b><br />&nbsp; </blockquote></td></tr>\n";
            echo "<tr><td class=\"blank\"><blockquote>";
            echo "<table width=\"50%\" cellspacing=0 cellpadding=2 border=0>";
            echo "<form action=\"". URLHelper::getLink("?cmd=search") ."\" method=\"POST\">";
            echo "<tr><td class=\"steel1\">";
            echo "&nbsp; <font size=-1>" . _("Geben Sie einen Suchbegriff ein, um weitere Bereiche zu finden!") . "</font><br /><br />";
            echo "&nbsp; <INPUT TYPE=\"TEXT\" style=\"vertical-align:middle;\" name=\"search\" size=\"20\">&nbsp;&nbsp;";
            echo "<input type=\"IMAGE\" style=\"vertical-align:middle;\" name=\"submit\" " . makeButton("suchestarten","src") . tooltip( _("Suche starten")) ." border=\"0\">";
            echo "</td></tr></form></table>\n";
            echo "</blockquote>";
            echo "</td></tr>";
        } else
            $news->search_range("blah");
        echo "\n<tr><td class=\"blank\"><blockquote>";
        if ($perm->have_perm("admin"))
        echo "<hr>";
        echo "<br /><b>" . _("verf&uuml;gbare Bereiche");
        echo "</b></blockquote></td></tr>\n ";
        $typen = array( "user"=> array('name' => _("Benutzer"), 'view_mode' => 'user', 'id_param' => 'range_id'),
                        "sem"=>  array('name' => _("Veranstaltung"), 'view_mode' => 'sem', 'id_param' => 'select_sem_id'),
                        "inst"=> array('name' => _("Einrichtung"), 'view_mode' => 'inst', 'id_param' => 'admin_inst_id'),
                        "fak"=>  array('name' => _("Fakult&auml;t"), 'view_mode' => 'inst', 'id_param' => 'admin_inst_id'));
        $my_cols=3;
        if ($perm->have_perm("autor")) {   // allow autors, needed for studygroups
            echo "\n<tr><td class=\"blank\"><blockquote>";
            echo "<font size=\"-1\" style=\"vertical-align:middle;\">" . _("Sie k&ouml;nnen&nbsp; <b>Pers&ouml;nliche News</b> bearbeiten") . "</font>&nbsp;";
            echo "<a href=\"". URLHelper::getLink("?range_id=self") ."\">&nbsp; <img style=\"vertical-align:middle;\" " . makeButton("bearbeiten","src") . tooltip(_("Persönliche News bearbeiten")) ." border=\"0\"></a>";
        }
        if ($perm->have_perm("root")) {
            $my_cols=4;
            echo "<font size=\"-1\" style=\"vertical-align:middle;\">&nbsp; " . _("<i>oder</i> <b>Systemweite News</b> bearbeiten") . "</font>&nbsp;";
            echo "<a href=\"". URLHelper::getLink("?range_id=studip") ."\">&nbsp;<img style=\"vertical-align:middle;\" " . makeButton("bearbeiten","src") . tooltip(_("Systemweite News bearbeiten")) ." border=\"0\"></a>";
        }
        if ($news->search_result)
            echo "<br><br><font size=\"-1\" style=\"vertical-align:middle;\">" . _("<i>oder</i> <b>hier</b> einen der gefundenen Bereiche ausw&auml;hlen:") . "&nbsp;</font>";

        if ($perm->have_perm("autor"))   // allow autors, needed for studygroups
            echo "</blockquote></td></tr>";

        if ($news->search_result) {
            uasort($news->search_result, 'callback_cmp_newsarray');
            echo "\n".'<tr><td width="100%" class="blank"><blockquote>';
            echo '<table width="'.round(0.88*$news->xres).'" cellspacing="0" cellpadding="2" border="0">';
            $css = new CssClassSwitcher(array("steel1","steel1"));
            $css->hoverenabled = TRUE;
            $css->switchClass();
            while (list($typen_key,$typen_value)=each ($typen)) {
                if (!$perm->have_perm("root") AND $typen_key=="user")
                    continue;
                echo "\n".'<td class="steel1" width="'.floor(100/$my_cols).'%" align="center" valign="top"><b>'.$typen_value['name'].'</b><br><div style="font-size:smaller;text-align:left;"><ul>';
                reset($news->search_result);
                while (list ($range,$details) = each ($news->search_result)) {
                    if ($details['type'] == $typen_key) {
                        echo "\n<li " . $css->getHover() . '><a href="'. URLHelper::getLink("?{$typen_value['id_param']}=$range&range_id=$range&view_mode={$typen_value['view_mode']}") .'">' .htmlReady($details['name']);
                        echo ($details['anzahl']) ? ' ('.$details['anzahl'].')' : ' (0)';
                        echo '</a></li>';
                    }
                }
                echo "\n</ul></div></td>";
            }
            echo "\n</table></blockquote></td></tr>";
        }
    }
    echo "\n<tr><td class=\"blank\"><br /><blockquote>";
    echo "<form action=\"". URLHelper::getLink("?cmd=new_entry&range_id=$news_range_id&view_mode=$view_mode")."\" method=\"POST\">";
    echo "<hr width=\"100%\"><br /><b>" . _("gew&auml;hlter Bereich:") . " </b>".htmlReady($news_range_name). "<br /><br />";
    if (get_config('NEWS_RSS_EXPORT_ENABLE') && $news->get_news_range_perm($news_range_id) > 1){
        echo '<img src="'.$GLOBALS['ASSETS_URL'].'images/rss.gif" border="0" align="absmiddle">&nbsp;';
        echo "\n".'<font size="-1" style="vertical-align:middle;">' . _("Die News des gew&auml;hlten Bereiches als RSS-feed zur Verf&uuml;gung stellen") . '</font>&nbsp;';
        vprintf("\n".'<input type="image" src="'.$GLOBALS['ASSETS_URL'].'images/%s" %s border="0" name="change_rss" align="absmiddle"/>',
                (StudipNews::GetRssIdFromRangeId($news_range_id) ? array('haken.gif',tooltip(_("RSS Export ist eingeschaltet"))) : array('x2.gif',tooltip(_("RSS Export ist ausgeschaltet")))));
        echo "\n<br><br>";
    }
    echo "\n".'<font size="-1" style="vertical-align:middle;">' . _("Eine neue News im gew&auml;hlten Bereich erstellen") . '</font>&nbsp;';
    echo makeButton('erstellen', 'input', _("Eine neue News erstellen"), 'new_entry');
    echo "</b>\n</blockquote>\n</form>\n</td>\n</tr>\n ";
    if (!$news->show_news($news_range_id)) {
        echo "\n".'<tr><td class="blank"><blockquote>';
        echo '<font size="-1" style="vertical-align:middle;">' . _("Im gew&auml;hlten Bereich sind keine News vorhanden!") . '<br><br>';
        echo '</blockquote></td></tr>';
    }
}
echo "\n</table>";
include ('lib/include/html_end.inc.php');
page_close();
?>
