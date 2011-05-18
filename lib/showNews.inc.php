<?php
# Lifter001: DONE
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
 * showNews.inc.php - Anzeigefunktion fuer News
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Ralf Stockmann <rstockm@gwdg.de>
 * @author      Cornelis Kater <ckater@gwdg.de>
 * @author      Stefan Suchi <suchi@gmx.de>
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     news
 */

require_once 'lib/functions.php';
require_once ('lib/visual.inc.php');
require_once ('lib/language.inc.php');
require_once ('lib/object.inc.php');
require_once ('lib/classes/StudipNews.class.php');
require_once ('lib/classes/StudipComments.class.php');
require_once ('lib/classes/Seminar.class.php');

/**
 *
 * @param unknown_type $cmd_data
 */
function process_news_commands(&$cmd_data)
{
    //Auf und Zuklappen News

    $cmd_data["comopen"]='';
    $cmd_data["comnew"]='';
    $cmd_data["comsubmit"]='';
    $cmd_data["comdel"]='';
    $cmd_data["comdelnews"]='';

    if ($_REQUEST['comsubmit']) $cmd_data["comsubmit"]=$_REQUEST['comopen']=$_REQUEST['comsubmit'];
    if ($_REQUEST['comdelnews']) $cmd_data["comdelnews"]=$_REQUEST['comopen']=$_REQUEST['comdelnews'];
    if ($_REQUEST['comopen']) $cmd_data["comopen"]=$_REQUEST['nopen']=$_REQUEST['comopen'];
    if ($_REQUEST['nopen']) $cmd_data["nopen"]=$_REQUEST['nopen'];
    if ($_REQUEST['nclose'])  $cmd_data["nopen"]='';
    if ($_REQUEST['comnew']) $cmd_data["comnew"]=$_REQUEST['comnew'];
    if ($_REQUEST['comdel']) $cmd_data["comdel"]=$_REQUEST['comdel'];
}

/**
 *
 * @param unknown_type $num
 * @param unknown_type $authorname
 * @param unknown_type $authoruname
 * @param unknown_type $date
 * @param unknown_type $dellink
 * @param unknown_type $content
 */
function commentbox($num, $authorname, $authoruname, $date, $dellink, $content)
{
    $out=array();
    $out[]="<table role=\"article\" style=\"border: 1px black solid;\" cellpadding=3 cellspacing=0 width=100%>";
    $out[].="<tr style=\"background:#ffffcc\">";
    $out[].="<td align=left style=\"border-bottom: 1px black dotted\">";
    $out[].="<font size=-1>#$num - ";
    $out[].="<a href=\"".URLHelper::getLink("about.php?username=$authoruname")."\">".htmlReady($authorname)."</a> ";
    $out[].=sprintf(_("hat am %s geschrieben:"),strftime('%x - %H:%M',$date));
    $out[].="</font>";
    $out[].="</td>";
    $out[].="<td align=right style=\"border-bottom: 1px black dotted\">";
    if ($dellink) {
        $out[].="<a href=\"$dellink\">".Assets::img('icons/16/blue/trash.png')."</a>";
    } else {
        $out[]=" ";
    }
    $out[].="</td></tr>";
    $out[].="<tr style=\"background:#ffffcc;\">";
    $out[].="<td colspan=2><font size=-1>".formatReady($content)."<br> </font></td></tr>";
    $out[].="</table>";
    return implode("\n",$out);
}

/**
 *
 * @param unknown_type $comment_id
 */
function delete_comment($comment_id)
{
    global $auth, $perm;

    $ok = 0;
    $comment = new StudipComments($comment_id);
    if (!$comment->isNew()) {
        if ($perm->have_perm("root")) {
            $ok = 1;
        } else {
            $news = new StudipNews($comment->getValue("object_id"));
            if (!$news->isNew() && $news->getValue("user_id") == $auth->auth["uid"]) {
                $ok = 1;
            }
        }
        if ($ok) {
            $ok = $comment->delete();
        }
    }
    return $ok;
}

/**
 *
 * @param unknown_type $range_id
 * @param unknown_type $show_admin
 * @param unknown_type $limit
 * @param unknown_type $open
 * @param unknown_type $width
 * @param unknown_type $last_visited
 * @param unknown_type $cmd_data
 */
function show_news($range_id, $show_admin = FALSE, $limit = "", $open, $width = "100%", $last_visited = 0, $cmd_data)
{
    global $auth, $SessSemName;

    $db2 = new DB_Seminar;

    $aktuell=time();

    if (get_config('NEWS_RSS_EXPORT_ENABLE')){
        $rss_id = StudipNews::GetRssIdFromRangeId($range_id);
    }

    if($show_admin && $_REQUEST['touch_news']){
        StudipNews::TouchNews($_REQUEST['touch_news']);
    }

    $news = StudipNews::GetNewsByRange($range_id, true);

    if ($SessSemName[1] == $range_id){
        $admin_link = ($SessSemName["class"]=="sem") ? "new_sem=TRUE&view=news_sem" :  "new_inst=TRUE&view=news_inst";
    } else if ($range_id == $auth->auth['uid']){
        $admin_link = "range_id=self";
    } else if ($range_id == "studip"){
        $admin_link = "range_id=studip";
    } else if (isDeputyEditAboutActivated() && isDeputy($auth->auth["uid"], $range_id, true)) {
        $admin_link = "range_id=".$range_id;
    }

    if (!empty($width)) {
        $width = ' style="width: ' . $width . ';"';
    }

    if (!count($news)) {
        if ($show_admin) {
            // set skip link
            SkipLinks::addIndex(_("Ankündigungen"), 'news_box');

            echo"\n<table id=\"news_box\" role=\"article\" class=\"index_box\"$width>";
            echo"\n<tr><td class=\"topic\" colspan=\"2\"><img src=\"".Assets::image_path('icons/16/white/breaking-news.png')."\" ". tooltip(_("Newsticker. Klicken Sie rechts auf die Zahnräder, um neue News in diesen Bereich einzustellen. Klicken Sie auf die Pfeile am linken Rand, um den ganzen Nachrichtentext zu lesen.")) . "> <b> " . _("Ankündigungen") . "</b></td>";
            echo"\n<td align=\"right\" class=\"topic\">";
            echo "<a href=\"".URLHelper::getLink("admin_news.php?$admin_link&cmd=new_entry")."\"><img src=\"".Assets::image_path('icons/16/white/admin.png')."\" " . tooltip(_("Ankündigungen einstellen")) . "></a> ";
            echo"</td></tr>";
            echo "\n<tr><td class=\"steel1\" colspan=\"3\"><p class=\"info\">" . _("Es sind keine aktuellen Ankündigungen vorhanden. Um neue Ankündigungen zu erstellen, klicken Sie rechts auf die Zahnräder.") . "</p>";
            echo "\n</td></tr></table>";
            return TRUE;
        } else {
            return FALSE;
        }
    } else {
        // set skip link
        SkipLinks::addIndex(_("Ankündigungen"), 'news_box');

        $colspan=2;

        //Ausgabe der Kopfzeile vor erster auszugebener Ankündigungen
        echo"\n<table id=\"news_box\" role=\"article\" class=\"index_box\"$width>";
        echo"\n<tr><td class=\"topic\"><img src=\"".Assets::image_path('icons/16/white/breaking-news.png')."\" ". tooltip(_("Newsticker. Klicken Sie rechts auf die Zahnräder, um neue Ankündigungen in diesen Bereich einzustellen. Klicken Sie auf die Pfeile am linken Rand, um den ganzen Nachrichtentext zu lesen.")) . "> <b>" . _("Ankündigungen") . "</b></td>";
        if ($rss_id) {
            $colspan++;
            echo "\n<td align=\"right\" class=\"topic\">";
            echo "\n<a href=\"rss.php?id=$rss_id\"><img src=\"".Assets::image_path('icons/16/white/rss.png')."\" " . tooltip(_("RSS-Feed")) . "></a>";
            echo "\n</td>";
        }
        if ($show_admin) {
            $colspan++;
            echo "\n<td align=\"right\" class=\"topic\" width=\"1%\">";
            echo " <a href=\"".URLHelper::getLink("admin_news.php?$admin_link&modus=admin&cmd=show")."\"><img src=\"".Assets::image_path('icons/16/white/admin.png')."\" " . tooltip(_("Ankündigungen bearbeiten")) . "></a> ";
            echo "\n</td>";
        }
        echo "\n</tr>\n<tr><td class=\"blank\" colspan=\"$colspan\">";

        // Ausgabe der Daten
        foreach ($news as $id => $news_item) {
            $news_item['open'] = ($id == $open);
            echo '<div id="news_item_'.$id.'" role="article">';
            echo show_news_item($news_item, $cmd_data, $show_admin, $admin_link);
            echo '</div>';
        }

    }
    echo "</td></tr></table>";
    return true;
}

/**
 *
 * @param unknown_type $range_id
 * @param unknown_type $type
 */
function show_rss_news($range_id, $type)
{
    $RssTimeFmt = '%Y-%m-%dT%H:%MZ';
    $last_changed = 0;
    switch ($type){
        case 'user':
            $studip_url = $GLOBALS['ABSOLUTE_URI_STUDIP'] . "about.php?again=yes&username=" . get_username($range_id);
            $title = get_fullname($range_id) . ' (Stud.IP - ' . $GLOBALS['UNI_NAME_CLEAN'] . ')';
            $RssChannelDesc = _("Persönliche Neuigkeiten") . ' ' . $title;
        break;
        case 'sem':
            $studip_url = $GLOBALS['ABSOLUTE_URI_STUDIP'] . "seminar_main.php?auswahl=" . $range_id;
            $sem_obj = Seminar::GetInstance($range_id);
            if ($sem_obj->read_level > 0) $studip_url .= "&again=yes";
            $title = $sem_obj->getName() . ' (Stud.IP - ' . $GLOBALS['UNI_NAME_CLEAN'] . ')';
            $RssChannelDesc = _("Neuigkeiten der Veranstaltung") . ' ' . $title;

        break;
        case 'inst':
            $studip_url = $GLOBALS['ABSOLUTE_URI_STUDIP'] . "institut_main.php?auswahl=" . $range_id;
            $object_name = get_object_name($range_id, $type);
            $title = $object_name['name'] . ' (Stud.IP - ' . $GLOBALS['UNI_NAME_CLEAN'] . ')';
            $RssChannelDesc = _("Neuigkeiten der Einrichtung") . ' ' . $title;
        break;
        case 'global':
            $studip_url = $GLOBALS['ABSOLUTE_URI_STUDIP'] . "index.php?again=yes";
            $title = 'Stud.IP - ' . $GLOBALS['UNI_NAME_CLEAN'];
            $RssChannelDesc = _("Allgemeine Neuigkeiten") . ' ' . $title;
        break;

    }

    foreach(StudipNews::GetNewsByRange($range_id, true) as  $news_id => $details) {
        list ($body,$admin_msg) = explode("<admin_msg>",$details["body"]);
        $items .= "<item>
        <title>".htmlspecialchars(studip_utf8encode($details["topic"]))."</title>
        <link>".htmlspecialchars(studip_utf8encode($studip_url . "&nopen=$news_id#anker"))."</link>";
        $items .= "<description>"."<![CDATA[".studip_utf8encode(formatready($body,1,1))."]]>"."</description>
        <dc:contributor>"."<![CDATA[".studip_utf8encode($details['author'])."]]>"."</dc:contributor>
        <dc:date>".gmstrftime($RssTimeFmt,($details['date'] > $details['chdate'] ? $details['date'] : $details['chdate']))."</dc:date>
        <pubDate>".date("r",($details['date'] > $details['chdate'] ? $details['date'] : $details['chdate']))."</pubDate>
        </item>\n";
        if ($last_changed < $details['chdate']) $last_changed = $details['chdate'];
    }
    header("Content-type: text/xml; charset=utf-8");
    echo "<?xml version=\"1.0\"?>
    <rss version=\"2.0\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\">
    <channel>
    <title>".htmlspecialchars(studip_utf8encode($title))."</title>
    <link>".htmlspecialchars(studip_utf8encode($studip_url))."</link>
    <image>
    <url>".Assets::image_path('logos/studipklein.gif')."</url>
    <title>".htmlspecialchars(studip_utf8encode($title))."</title>
    <link>".htmlspecialchars(studip_utf8encode($studip_url))."</link>
    </image>
    <description>".htmlspecialchars(studip_utf8encode($RssChannelDesc))."</description>
    <lastBuildDate>".date("r",$last_changed)."</lastBuildDate>
    <generator>". htmlspecialchars(studip_utf8encode('Stud.IP - ' . $GLOBALS['SOFTWARE_VERSION'])) . "</generator>";
    echo chr(10).$items;
    echo "</channel>\n</rss>";
    return true;
}

/**
 *
 * @param unknown_type $news_item
 * @param unknown_type $cmd_data
 * @param unknown_type $show_admin
 * @param unknown_type $admin_link
 */
function show_news_item($news_item, $cmd_data, $show_admin, $admin_link)
{
  global $auth, $_fullname_sql;

  $db2 = new DB_Seminar();

  $id = $news_item['news_id'];

  $tempnew = (($news_item['chdate'] >= object_get_visit($id,'news',false,false))
             && ($news_item['user_id'] != $auth->auth["uid"]));

  if ($tempnew && $_REQUEST["new_news"])
    $news_item["open"] = $tempnew;

  ob_start();

  $tmp_titel=htmlReady(mila($news_item['topic']));
  $titel='';

  if ($news_item['open']) {
    $link = "?nclose=true";

    if ($cmd_data['comopen'] != $id)
      $titel = $tmp_titel."<a name=\"anker\"> </a>";
    else
      $titel = $tmp_titel;

    if ($news_item['user_id'] != $auth->auth["uid"])
      object_add_view($id);  //Counter for news - not my own

    object_set_visit($id, "news"); //and, set a visittime
  } else {
    $link = "?nopen=".$id;
    $titel=$tmp_titel;
  }


  $db2->query("SELECT username, " . $_fullname_sql['full'] ." AS fullname FROM auth_user_md5 a LEFT JOIN user_info USING (user_id) WHERE a.user_id='".$news_item['user_id']."'");
  $db2->next_record();

  $link .= "&username=".$db2->f("username") . "#anker";
  $zusatz="<a href=\"".URLHelper::getLink("about.php?username=".$db2->f("username"))."\"><font size=-1 color=\"#333399\">".htmlReady($db2->f("fullname"))."</font></a><font size=-1> ".date("d.m.Y",$news_item['date'])." | <font color=\"#005500\">".object_return_views($id)."<font color=\"black\"> |</font>";

  $unamelink = '&username='.$db2->f('username');
  $uname = $db2->f('username');

  if ($news_item['allow_comments'] == 1) {
    $numcomments = StudipComments::NumCommentsForObject($id);
    $numnewcomments = StudipComments::NumCommentsForObjectSinceLastVisit($id, object_get_visit($id,'news',false,false), $auth->auth['uid']);
    $zusatz .= " <font ".($numnewcomments ? tooltip(sprintf(_("%s neue(r) Kommentar(e)"),$numnewcomments),false) : '')." color=\"".($numnewcomments ? 'red' : '#aaaa66')."\">".$numcomments."</font><font color=\"black\"> |</font>";
  }

  $open_or_close = $news_item['open'] ? 'close' : 'open';
  $ajax = "STUDIP.News.openclose('$id', '$admin_link')";
  $link=URLHelper::getLink($link);
  $link .= '" onClick="' . $ajax . ';return false;';

  if ($link)
    $titel = "<a href=\"$link\" class=\"tree\" >".$titel."</a>";

  echo "\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" align=\"center\"><tr>";

  $icon = Assets::img('icons/16/grey/news.png', array('class' => 'text-bottom'));

  if ($news_item['open'])
    printhead(0, 0, $link, "open", $tempnew, $icon, $titel, $zusatz, $news_item['date']);
  else
    printhead(0, 0, $link, "close", $tempnew, $icon, $titel, $zusatz, $news_item['date']);

  echo "</tr></table>   ";

  echo "<div id=\"news_item_".$id."_content\"".($news_item['open'] ? "" : " style=\"display:none\"").">";
  if ($news_item['open']) {
    echo show_news_item_content($news_item, $cmd_data, $show_admin, $admin_link);
  }
  echo "</div>";

  return ob_get_clean();
}

/**
 *
 * @param unknown_type $news_item
 * @param unknown_type $cmd_data
 * @param unknown_type $show_admin
 * @param unknown_type $admin_link
 */
function show_news_item_content($news_item, $cmd_data, $show_admin, $admin_link)
{
    global $auth, $_fullname_sql;

    $db2 = new DB_Seminar();

    $id = $news_item['news_id'];

    $tempnew = (($news_item['chdate'] >= object_get_visit($id,'news',false,false))
    && ($news_item['user_id'] != $auth->auth["uid"]));

    if ($tempnew && $_REQUEST["new_news"])
    $news_item["open"] = $tempnew;
    $db2->query("SELECT username, " . $_fullname_sql['full'] ." AS fullname FROM auth_user_md5 a LEFT JOIN user_info USING (user_id) WHERE a.user_id='".$news_item['user_id']."'");
    $db2->next_record();

    $link .= "&username=".$db2->f("username") . "#anker";
    $zusatz="<a href=\"".URLHelper::getLink("about.php?username=".$db2->f("username"))."\"><font size=-1 color=\"#333399\">".htmlReady($db2->f("fullname"))."</font></a><font size=-1> ".date("d.m.Y",$news_item['date'])." | <font color=\"#005500\">".object_return_views($id)."<font color=\"black\"> |</font>";

    $unamelink = '&username='.$db2->f('username');
    $uname = $db2->f('username');

    list($content, $admin_msg) = explode("<admin_msg>", $news_item['body']);
    $content = formatReady($content);

    if ($news_item['chdate_uid']){
        $admin_msg = StudipNews::GetAdminMsg($news_item['chdate_uid'], $news_item['chdate']);
    }

    if ($admin_msg) {
        $content.="<br><br><i>".htmlReady($admin_msg)."</i>";
    }

    if (!$content)
      $content=_("Keine Beschreibung vorhanden.") . "\n";
    else
      $content.="<br>";

    if ($auth->auth["uid"] == $news_item['user_id'] || $show_admin) {
        $edit="<a href=\"".URLHelper::getLink("admin_news.php?cmd=edit&edit_news=".$id."&$admin_link")."\">" . makeButton("bearbeiten") . "</a>";
        $edit.=" <a href=\"".URLHelper::getLink("?touch_news=".$id."#anker")."\">" . makeButton("aktualisieren") . "</a>";
        if ($auth->auth["uid"] == $news_item['user_id'] || $GLOBALS['perm']->have_perm('admin')) {
            $edit.=" <a href=\"".URLHelper::getLink("admin_news.php?cmd=kill&kill_news=".$id."&$admin_link")."\">" . makeButton("loeschen") . "</a>";
        }
    }

    //
    // Kommentare
    //
    if ($news_item['allow_comments'] == 1) {
        $showcomments = 0;
        if ($cmd_data["comsubmit"] == $id) {
            if (trim($_REQUEST['comment_content'])) {
                $comment = new StudipComments();
                $comment->setValue('object_id', $id);
                $comment->setValue('user_id', $auth->auth['uid']);
                $comment->setValue('content', stripslashes(trim($_REQUEST['comment_content'])));
                $comment->store();
            }
            $showcomments = 1;
        } else if ($cmd_data["comdelnews"] == $id) {
            delete_comment($cmd_data["comdel"]);
            $showcomments = 1;
        }

        if ($showcomments || $cmd_data["comopen"] == $id) {
            $comments = "\n<table border=\"0\" cellpadding=\"2\" cellspacing=\"0\" width=\"90%\" align=\"center\" style=\"margin-top:10px\">";
            $comments .= "<tr align=center><td><font size=-1><b>"._("Kommentare")."<b></font><a name=\"anker\"> </a></td></tr>";
            $c=StudipComments::GetCommentsForObject($id);
            if (count($c)) {
                $num = 0;
                foreach ($c as $comment) {
                    $comments.="<tr><td>";
                    if ($show_admin) {
                        $dellink = URLHelper::getLink("?comdel=".$comment[4]."&comdelnews=".$id."#anker");
                    } else {
                        $dellink = NULL;
                    }

                    $comments .= commentbox(++$num, $comment[1], $comment[2], $comment[3], $dellink, $comment[0]);
                    $comments .= "</td></tr>";
                }
            }
            $comments .= "</table>";
            $content  .= $comments;
            $formular=" <br>\n<form action=\"".URLHelper::getLink("#anker")."\" method=\"POST\">";
            $formular.= CSRFProtection::tokenTag();
            $formular.="<input type=hidden name=\"comsubmit\" value=\"".$id."\">";
            $formular.="<input type=hidden name=\"username\" value=\"$uname\">";
            $formular.="<p align=\"center\">"._("Geben Sie hier Ihren Kommentar ein!")."</p>";
            $formular.="<div align=\"center\">";
            $formular.="<textarea name=\"comment_content\" style=\"width:70%\" rows=8 cols=38 wrap=virtual></textarea>";
            $formular.="<br><br>";
            $formular.="<input type=\"image\" ".makeButton("absenden","src").">";

            $help_url = format_help_url("Basis.VerschiedenesFormat");
            $formular.="   <a href=\"".URLHelper::getLink("show_smiley.php")."\" target=\"_blank\"><font size=\"-1\">"._("Smileys")."</a>  <a href=\"".$help_url."\" target=\"_blank\"><font size=\"-1\">"._("Formatierungshilfen")."</a><br><br>";
            $formular.="</div></form><p> </p>";
            $content.=$formular;
        } else {
            $numcomments = StudipComments::NumCommentsForObject($id);
            $cmdline = "<p align=center><font size=-1><a href=\"".URLHelper::getLink("?foo=".rand()."&comopen=".$id.$unamelink."#anker")."\">"
                        .sprintf(_("Kommentare lesen (%s) / Kommentar schreiben"), $numcomments)."</a></font></p>";
            $content .= $cmdline;
        }
    }
    ob_start();
    echo "\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" align=\"center\"><tr>";
    printcontent(0,0, $content, $edit);
    echo "</tr></table>";
    return ob_get_clean();
}