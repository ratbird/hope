<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
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

require '../lib/bootstrap.php';

page_open(array("sess"=> "Seminar_Session", "auth" =>"Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$auth->login_if($auth->auth["uid"] == "nobody");
$perm->check("autor");

require_once 'lib/messaging.inc.php';
require_once 'lib/visual.inc.php';
require_once 'lib/functions.php';
require_once 'lib/classes/AdminNewsController.class.php';


include ('lib/seminar_open.php'); // initialise Stud.IP-Session

URLHelper::bindLinkParam('news_range_id',$news_range_id);
URLHelper::bindLinkParam('news_range_name',$news_range_name);

if (Request::get('admin_inst_id')) {
    $news_range_id = Request::get('admin_inst_id');
    $view_mode = 'inst';
}

if ($_REQUEST['range_id'] == "self"){
    $news_range_id = $auth->auth['uid'];
} else if (isset($_REQUEST['range_id'])){
    $news_range_id = $_REQUEST['range_id'];
} else if ($view == 'news_sem' || $view == 'news_inst') {
    $news_range_id = $SessSemName[1];
} else if (!$news_range_id){
    $news_range_id = $auth->auth['uid'];
}

PageLayout::setHelpKeyword("Basis.News");
PageLayout::setTitle(_("Verwaltung von Ankündigungen"));

if ($list || $view || ($news_range_id != $user->id && 
        $news_range_id != 'studip') && $view_mode != 'user' && 
        !(isDeputyEditAboutActivated() && 
        isDeputy($auth->auth["uid"], $news_range_id, true))){
    include 'lib/admin_search.inc.php';

    if ($perm->have_perm('admin')) {
        if ($links_admin_data['topkat'] == 'sem' && !SeminarCategories::getByTypeId($SessSemName['art_num'])->studygroup_mode) {
            Navigation::activateItem('/admin/course/news');
        } elseif ($links_admin_data['topkat'] == 'inst') {
            Navigation::activateItem('/admin/institute/news');
        } else {
            Navigation::activateItem('/tools/news');
        }
    } else {
        Navigation::activateItem('/course/admin/news');
    }
} else {
    Navigation::activateItem('/tools/news');
    $view_mode = 'user';
}

$news = new AdminNewsController();

PageLayout::setTitle(($SessSemName[1] && ($list || $view || ($news_range_id != $user->id && $news_range_id != 'studip' && !(isDeputyEditAboutActivated() && isDeputy($user->id, $news_range_id, true))) && $view_mode != 'user' ) ?  $SessSemName["header_line"] : $news->range_name ) . " - " . _("Verwaltung von Ankündigungen"));

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head

if ($view_mode != 'user') {
    include 'lib/include/admin_search_form.inc.php';
}

echo cssClassSwitcher::GetHoverJSFunction() . "\n";


function callback_cmp_newsarray($a, $b) {
    return strnatcasecmp($a['name'], $b['name']); // Case insensitive string comparisons using a "natural order" algorithm
}

?>
<div class="white">
<table style="padding: 0 0.5%" cellspacing="0" cellpadding="0" border="0" width="100%">
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
    if (!trim(stripslashes($topic)) && trim(stripslashes($body))) {
        $topic = addslashes(substr(trim(stripslashes($body)),0,30) . '...');
    }

    //Maximale Gültigkeitsdauer von News auf 24 Wochen festgelegt
    $max_expire = 24 * 7 * 24 * 60 * 60;

    if (Request::get('startdate') && Request::get('enddate')) {
        if (preg_match('/^(\d{2}).(\d{2}).(\d{4})$/',Request::get('startdate'))
            && preg_match('/^(\d{2}).(\d{2}).(\d{4})$/',Request::get('enddate'))) {

            $start_array = explode(".", Request::get('startdate'));
            $starttime = mktime(0, 0, 0, $start_array[1], $start_array[0], $start_array[2]);
            $end_array = explode(".", Request::get('enddate'));
            $endtime = mktime(23, 59, 59, $end_array[1], $end_array[0], $end_array[2]);
            $expire = $endtime - $starttime;
        }
    }


    $max_endtime = $starttime + $expire;
    if ($topic != "" && $add_range && $expire > 0 && $expire <= $max_expire) {
        $edit_news = $news->update_news($news_id, $author, $topic, $body, $user_id, $starttime, $expire, $add_range, $allow_comments);
        if ($edit_news) {
            $cmd = "edit";
        } else {
            $cmd = "";
        }
    } else if ($topic == "") {
        $cmd = "edit";
        $edit_news = Request::option('news_id');
        $news->msg .= "error§"._("Leere Ankündigungen k&ouml;nnen nicht gespeichert werden! Geben Sie immer &Uuml;berschrift oder Inhalt an!")."§";
    } else if ($expire < 0) {
        $cmd = "edit";
        $news->msg .= "error§"._("Das Einstelldatum muss vor dem Ablaufdatum liegen!")."§";
    } else if ($expire > $max_expire) {
        $cmd = "edit";
        $news->msg .= "error§".sprintf(_("Sie können Ankündigungen maximal bis zum %s einstellen!")."§", date('d M Y', $starttime + $max_expire));
    } else if (!$add_range) {
        $cmd = "edit";
        $news->msg .= "error§"._("Mindestens ein Bereich zum Anzeigen der Ankündigung muss gewählt sein!")."§";
    } else {
        $cmd = "edit";
        $news->msg .= "error§"._("Bitte geben Sie Start- und Ablaufdatum an")."§";
    }


}
if ($cmd=="new_entry" &&
    Request::submitted('change_rss') &&
    get_config('NEWS_RSS_EXPORT_ENABLE') &&
    $news->get_news_range_perm($news_range_id) > 1){
        if (Request::int('enable_rss')) {
            StudipNews::SetRssId($news_range_id);
            $news->msg .= "info§" . _("Der RSS Export wurde für diesen Bereich eingeschaltet!")
                        . '<br>' . _("Bitte beachten Sie, dass damit die Ankündigungen dieses Bereiches auch von Personen, die nicht im Stud.IP angemeldet sind, abgerufen werden können!") . "§";
        } else {
            StudipNews::UnSetRssId($news_range_id);
            $news->msg .= "info§" . _("Der RSS Export wurde für diesen Bereich ausgeschaltet!") . "§";
        }
        $cmd = '';
}

if ($news->msg) {
    echo "<tr><td class=\"blank\"><br>";
    parse_msg($news->msg,"§","blank","1");
    echo "</td></tr>";
}
$news->msg="";

if ($cmd=="edit") {
    if ($perm->have_perm("admin") && $search) {
        $news->search_range($search);

        if (!count($news->search_result)) {
            echo "<tr><td class=\"blank\"><br>";
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
    echo "<tr><td class=\"blank\"><br>";
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
    if ($perm->have_perm('admin') || $perm->have_perm('autor') && $view_mode == 'user') {    // allow autors, needed for studygroups
        if ($perm->have_perm("admin")) {
            echo"\n<tr><td class=\"blank\"><p class=\"info\"><br><b>" . _("Bereichsauswahl") . "</b><br>&nbsp; </p></td></tr>\n";
            echo "<tr><td class=\"blank\"><p class=\"info\">";
            echo "<table width=\"50%\" cellspacing=0 cellpadding=2 border=0>";
            echo "<form action=\"". URLHelper::getLink("?cmd=search") ."\" method=\"POST\">";
            echo CSRFProtection::tokenTag();
            echo "<tr><td class=\"steel1\">";
            echo "&nbsp; <font size=-1>" . _("Geben Sie einen Suchbegriff ein, um weitere Bereiche zu finden!") . "</font><br><br>";
            echo "&nbsp; <input type=\"TEXT\" style=\"vertical-align:middle;\" name=\"search\" size=\"20\">&nbsp;&nbsp;";
            echo "<input type=\"IMAGE\" style=\"vertical-align:middle;\" name=\"submit\" " . makeButton("suchestarten","src") . tooltip( _("Suche starten")) ." border=\"0\">";
            echo "</td></tr></form></table>\n";
            echo "</p>";
            echo "</td></tr>";
        } else
            $news->search_range("blah");
        echo "\n<tr><td class=\"blank\"><p class=\"info\">";
        if ($perm->have_perm("admin"))
        echo "<hr>";
        echo "<br><b>" . _("Verf&uuml;gbare Bereiche");
        echo "</b></p></td></tr>\n ";
        $typen = array( "user"=> array('name' => _("Benutzer"), 'view_mode' => 'user', 'id_param' => 'range_id'),
                        "sem"=>  array('name' => _("Veranstaltung"), 'view_mode' => 'sem', 'id_param' => 'range_id'),
                        "inst"=> array('name' => _("Einrichtung"), 'view_mode' => 'inst', 'id_param' => 'range_id'),
                        "fak"=>  array('name' => _("Fakult&auml;t"), 'view_mode' => 'inst', 'id_param' => 'range_id'));
        $my_cols=3;
        if ($perm->have_perm("autor")) {   // allow autors, needed for studygroups
            echo "\n<tr><td class=\"blank\"><p class=\"info\">";
            echo _("Sie k&ouml;nnen&nbsp; <b>Pers&ouml;nliche Ankündigungen</b> bearbeiten") . "&nbsp;";
            echo "<a href=\"". URLHelper::getLink("?range_id=self") ."\">&nbsp; <img style=\"vertical-align:middle;\" " . makeButton("bearbeiten","src") . tooltip(_("Persönliche Ankündigungen bearbeiten")) ." border=\"0\"></a>";
        }
        if ($perm->have_perm("root")) {
            $my_cols=4;
            echo "<font size=\"-1\" style=\"vertical-align:middle;\">&nbsp; " . _("<i>oder</i> <b>Systemweite Ankündigungen</b> bearbeiten") . "</font>&nbsp;";
            echo "<a href=\"". URLHelper::getLink("?range_id=studip") ."\">&nbsp;<img style=\"vertical-align:middle;\" " . makeButton("bearbeiten","src") . tooltip(_("Systemweite Ankündigungen bearbeiten")) ." border=\"0\"></a>";
        }
        if ($news->search_result)
            echo "<br><br><font size=\"-1\" style=\"vertical-align:middle;\">" . _("<i>oder</i> <b>hier</b> einen der gefundenen Bereiche ausw&auml;hlen:") . "&nbsp;</font>";

        if ($perm->have_perm("autor"))   // allow autors, needed for studygroups
            echo "</p></td></tr>";

        if ($news->search_result) {
            uasort($news->search_result, 'callback_cmp_newsarray');
            echo "\n".'<tr><td width="100%" class="blank"><p class="info">';
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
                    $link_view_mode = $perm->have_perm('admin') ? $typen_value['view_mode'] : 'user';
                    if ($details['type'] == $typen_key) {
                        echo "\n<li " . $css->getHover() . '><a href="'. URLHelper::getLink("?{$typen_value['id_param']}=$range&range_id=$range&view_mode={$link_view_mode}") .'">' .htmlReady($details['name']);
                        echo ($details['anzahl']) ? ' ('.$details['anzahl'].')' : ' (0)';
                        echo '</a></li>';
                    }
                }
                echo "\n</ul></div></td>";
            }
            echo "\n</table></p></td></tr>";
        }
    }
    echo "\n<tr><td class=\"blank\"><br><p class=\"info\">";
    echo "<form action=\"". URLHelper::getLink("?cmd=new_entry&range_id=$news_range_id&view_mode=$view_mode")."\" method=\"POST\">";
    echo CSRFProtection::tokenTag();
    if ($perm->have_perm('admin') || $perm->have_perm('autor') && $view_mode == 'user') {
        echo "<hr width=\"100%\"><br><b>" . _("Gew&auml;hlter Bereich:") . " </b>".htmlReady($news_range_name). "<br><br>";
    }
    if (get_config('NEWS_RSS_EXPORT_ENABLE') && $news->get_news_range_perm($news_range_id) > 1){
        echo Assets::img('icons/16/grey/rss.png', array('class' => 'text-top'));
        echo "\n".'<label>' . _("Die Ankündigungen des gew&auml;hlten Bereiches als RSS-feed zur Verf&uuml;gung stellen:") . '</font>&nbsp;';
        vprintf("\n".'<input type="checkbox" %s name="enable_rss" value="1"></label>',
                (StudipNews::GetRssIdFromRangeId($news_range_id) ? 'checked' : '' ));
        echo makeButton('uebernehmen', 'input', _("RSS Einstellungen übernehmen"), 'change_rss');
        echo "\n<br><br>";
    }
    echo "\n".'<font size="-1" style="vertical-align:middle;">' . _("Eine neue Ankündigung im gew&auml;hlten Bereich erstellen") . '</font>&nbsp;';
    echo makeButton('erstellen', 'input', _("Eine neue Ankündigung erstellen"), 'new_entry');
    echo "</b>\n</p>\n</form>\n</td>\n</tr>\n ";
    if (!$news->show_news($news_range_id)) {
        echo "\n".'<tr><td class="blank"><p class="info">';
        echo '<font size="-1" style="vertical-align:middle;">' . _("Im gew&auml;hlten Bereich sind keine Ankündigungen vorhanden!") . '<br><br>';
        echo '</p></td></tr>';
    }
}
echo "\n</table></div>";
include ('lib/include/html_end.inc.php');
page_close();
?>
